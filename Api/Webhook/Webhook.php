<?php
namespace StoreKeeper\StoreKeeper\Api\Webhook;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Backend\Model\UrlInterface;
use Psr\Log\LoggerInterface;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;
use StoreKeeper\StoreKeeper\Helper\Config;
use StoreKeeper\StoreKeeper\Model\ResourceModel\StoreKeeperFailedSyncOrder\CollectionFactory as StoreKeeperFailedSyncOrderCollectionFactory;

class Webhook
{
    private const DATE_TIME_FORMAT = 'D, d M Y H:i:s O';
    private const STOCK_CHANGE_EVENT = 'stock_change';
    private Request $request;
    private Auth $authHelper;
    private Json $json;
    private PublisherInterface $publisher;
    private ProductMetadataInterface $productMetadata;
    private Config $configHelper;
    private LoggerInterface $logger;
    private TimezoneInterface $timezone;
    private CollectionFactory $orderCollectionFactory;
    private UrlInterface $backendUrl;
    private StoreKeeperFailedSyncOrderCollection $storeKeeperFailedSyncOrderCollection;

    /**
     * @param Request $request
     * @param Auth $authHelper
     * @param Json $json
     * @param PublisherInterface $publisher
     * @param ProductMetadataInterface $productMetadata
     * @param Config $configHelper
     * @param LoggerInterface $logger
     * @param TimezoneInterface $timezone
     * @param CollectionFactory $orderCollectionFactory
     * @param UrlInterface $backendUrl
     * @param StoreKeeperFailedSyncOrderCollectionFactory $storeKeeperFailedSyncOrderCollectionFactory
     */
    public function __construct(
        Request $request,
        Auth $authHelper,
        Json $json,
        PublisherInterface $publisher,
        ProductMetadataInterface $productMetadata,
        Config $configHelper,
        LoggerInterface $logger,
        TimezoneInterface $timezone,
        CollectionFactory $orderCollectionFactory,
        UrlInterface $backendUrl,
        StoreKeeperFailedSyncOrderCollectionFactory $storeKeeperFailedSyncOrderCollectionFactory
    ) {
        $this->request = $request;
        $this->authHelper = $authHelper;
        $this->json = $json;
        $this->publisher = $publisher;
        $this->productMetadata = $productMetadata;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
        $this->timezone = $timezone;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->backendUrl = $backendUrl;
        $this->storeKeeperFailedSyncOrderCollectionFactory = $storeKeeperFailedSyncOrderCollectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getExecute($storeId)
    {
        file_put_contents("get-webhook.log", json_encode([
            "storeId" => $storeId
        ], JSON_PRETTY_PRINT), FILE_APPEND);
    }

    /**
     * {@inheritdoc}
     */
    public function postExecute($storeId)
    {
        try {
            $bodyParams = $this->request->getBodyParams();
            $payload = $bodyParams['payload'] ?? [];
            $action = $bodyParams['action'] ?? null;
            $token = $this->configHelper->getToken($storeId);
            $requestToken = $this->request->getHeader('upxhooktoken');
            $response = [ "success" => true ];
            $status = 200;

            if ($action == "init" && $requestToken == $token) {
                $this->authHelper->setAuthDataForWebsite($storeId, $payload);

                $response = [
                    "success" => true
                ];
            } elseif ($requestToken == $token) {
                if (!$this->authHelper->isConnected($storeId)) {
                    return $this->response([
                        'success' => false,
                        'message' => __("Store is not connected")
                    ]);
                }

                if ($action == "info") {
                    // retrieve the current plugin version
                    $composerFile = file_get_contents(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "composer.json");
                    $composerJson = json_decode($composerFile, true);
                    $capabilities = [
                        'b2s_payment_method', // has the capability to use the payment method from backoffice
                        's2b_report_system_status' // display failed order and other(?) report stats on dashboard
                    ];

                    $sync_mode = null;

                    if ($this->configHelper->hasMode($storeId, Config::SYNC_NONE)) {
                        $sync_mode = 'sync-mode-none';
                    } elseif ($this->configHelper->hasMode($storeId, Config::SYNC_ALL)) {
                        $sync_mode = 'sync-mode-full-sync';
                    } elseif ($this->configHelper->hasMode($storeId, Config::SYNC_PRODUCTS)) {
                        $sync_mode = 'sync-mode-products-only';
                    } elseif ($this->configHelper->hasMode($storeId, Config::SYNC_ORDERS)) {
                        $sync_mode = 'sync-mode-order-only';
                    }

                    $response = [
                        "success" => true,
                        'vendor' => $this->authHelper->getVendor(),
                        'platform_name' => $this->authHelper->getPlatformName(),
                        'platform_version' => $this->productMetadata->getVersion(),
                        'software_name' => $this->authHelper->getSoftwareName(),
                        'software_version' => $composerJson['version'],
                        'task_failed_quantity' => $this->getAmountOfFailedTasks(),
                        'plugin_settings_url' => $this->getPluginSettingsUrl(),
                        'now_date' => $this->getCurrentDateTime(),
                        'system_status' => [
                            'order' => [
                                'last_date' => $this->getLastOrderDateTime(),
                                'last_synchronized_date' => $this->getLastSynchronizedOrderDateTime(),
                                'ids_with_failed_tasks' => $this->getIdsWithFailedTasks(),
                                'last_date_of_failed_task' => $this->getLastDateOfFailedTask() //'last_date_of_failed_task' it is my custom key
                            ]
                        ],
                        'extra' => [
                            'url' => $this->authHelper->getStoreBaseUrl(),
                            'sync_mode' => $sync_mode,
                            'active_capability' => $capabilities
                        ],
                    ];
                } elseif ($action == "events") {
                    preg_match("/(\w+)\::(\w+)\(([a-z]+)\=([0-9]+)\)/", $payload['backref'], $matches);

                    list($group, $module, $entity, $key, $value) = $matches;

                    $eventNames = array_map(function ($event) {
                        return $event['event'];
                    }, $payload['events']);
                    $eventNames = array_unique($eventNames);

                    $messages = [];
                    $success = false;
                    $skipPublish = false;
                    $message = [];
                    $isRefund = false;
                    foreach ($eventNames as $eventName) {
                        if ($eventName == "stock_change" && $this->configHelper->hasMode($storeId, Config::SYNC_ORDERS | Config::SYNC_PRODUCTS | Config::SYNC_ALL)) {
                            $skipPublish = $this->getIsOwnOrderStockChange($bodyParams);
                            if ($skipPublish) {
                                $messages[] = "Skipping product \"stock_change\" on order placing";
                            } else {
                                $messages[] = "Processing event \"stock_change\"";
                            }
                        } elseif ($entity == "ShopProduct" && $this->configHelper->hasMode($storeId, Config::SYNC_PRODUCTS | Config::SYNC_ALL)) {
                            $messages[] = "Processing entity \"ShopProduct\"";
                        } elseif ($entity == "Category" && $this->configHelper->hasMode($storeId, Config::SYNC_PRODUCTS | Config::SYNC_ALL)) {
                            $messages[] = "Processing entity \"Category\"";
                        } elseif ($entity == "Order" && $this->configHelper->hasMode($storeId, Config::SYNC_ORDERS | Config::SYNC_ALL)) {
                            foreach ($payload['events'] as $event) {
                                $isRefund = $this->isRefund($event);
                                break;
                            }
                            $messages[] = "Processing entity \"Order\"";
                        } else {
                            $messages[] = "Skipping {$entity}::{$eventName}: mode not allowed";
                            continue;
                        }

                        if (!$skipPublish) {
                            $success = true;
                            $message = [
                                "type" => $eventName,
                                "entity" => $entity,
                                "storeId" => $storeId,
                                "module" => $module,
                                "key" => $key,
                                "value" => $value,
                                "refund" => $isRefund
                            ];

                            $this->publisher->publish("storekeeper.queue.events", $this->json->serialize($message));
                        }

                        $response['success'] = $success;
                        $response['message'] = implode(', ', $messages);

                        if ($this->configHelper->isDebugLogs($storeId)) {
                            $this->logger->info("Received event {$eventName}: " . json_encode($message));
                        }
                    }

                } elseif ($action == "deactivated") {
                    preg_match("/(\w+)\::(\w+)\(([a-z]+)\=([0-9]+)\)/", $payload['backref'], $matches);

                    list($group, $module, $entity, $key, $value) = $matches;

                    foreach ($payload['events'] as $id => $eventData) {
                        $message = [
                            "type" => $eventData['event'],
                            "storeId" => $storeId,
                            "module" => $module,
                            "entity" => $entity,
                            "key" => $key,
                            "value" => $value
                        ];
                        $this->publisher->publish("storekeeper.queue.events", $this->json->serialize($message));
                    }
                }
            } else {
                $status = 403;
                $response = [
                    'success' => false,
                    'message' => __('Not allowed')
                ];
            }

            $this->logger->info("Received action {$action}: " . json_encode($response));

            return $this->response($response, $status);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->response([
                'success' => false,
                'message' => "An error occurred: {$e->getMessage()}"
            ]);
        }
    }

    private function response(array $response = [], $status = 200)
    {
        http_response_code($status);
        header("Content-Type: application/json");
        echo json_encode($response);
        exit;
    }

    /**
     * @param array $bodyParams
     * @return bool
     */
    private function getIsOwnOrderStockChange(array $bodyParams): bool
    {
        $events = $bodyParams['payload']['events'];
        foreach ($events as $event) {
            if ($event['event'] == self::STOCK_CHANGE_EVENT) {
                return $event['details']['is_own_order_stock_change'];
            }
        }
    }

    /**
     * @return string
     */
    private function getCurrentDateTime(): string
    {
        return $this->timezone->date()->format(self::DATE_TIME_FORMAT);
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function getLastOrderDateTime(): string
    {
        $orderCollection = $this->orderCollectionFactory->create()->addAttributeToSort('created_at', 'desc');
        $lastOrder = $orderCollection->getFirstItem();
        $lastOrderDateTime = $this->timezone->date($lastOrder->getCreatedAt())->format(self::DATE_TIME_FORMAT);

        return $lastOrderDateTime;
    }

    /**
     * @return string
     */
    private function getPluginSettingsUrl(): string
    {
        $sectionId = 'storekeeper_general';
        $params = [
            '_nosid' => true,
            '_query' => ['key' => $this->backendUrl->getSecretKey()]
        ];
        $url = $this->backendUrl->getUrl(
            'admin/system_config/edit',
            ['section' => $sectionId],
            $params
        );
        return $url;
    }

    /**
     * @return string
     */
    private function getLastSynchronizedOrderDateTime(): string
    {
        $orderCollection = $this->orderCollectionFactory->create()->addAttributeToSort('storekeeper_order_last_sync', 'desc');
        $lastSynchronizedOrder = $orderCollection->getFirstItem();
        $lastSynchronizedOrderDateTime = $this->timezone->date($lastSynchronizedOrder->getData('storekeeper_order_last_sync'))->format(self::DATE_TIME_FORMAT);

        return $lastSynchronizedOrderDateTime;
    }

    /**
     * @return array
     */
    private function getIdsWithFailedTasks(): array
    {
        return array_keys($this->storeKeeperFailedSyncOrderCollectionFactory->create()->addFieldToFilter('is_failed', 1)->getItems());
    }

    /**
     * @return string
     */
    private function getLastDateOfFailedTask(): string
    {
        $failedOrders = $this->storeKeeperFailedSyncOrderCollectionFactory->create()
            ->addFieldToFilter('is_failed', 1)
            ->addOrder('updated_at');
        $lastFailedOrder = $failedOrders->getFirstItem();
        $lastFailedOrderDateTime = $this->timezone->date($lastFailedOrder->getData('updated_at'))->format(self::DATE_TIME_FORMAT);

        return $lastFailedOrderDateTime;
    }

    /**
     * @return int
     */
    private function getAmountOfFailedTasks(): int
    {
        return count($this->getIdsWithFailedTasks());
    }

    /**
     * @param array $event
     * @return bool
     */
    private function isRefund(array $event): bool
    {
        $eventName = $event['event'];
        if ($eventName !== 'updated') {
            return false;
        }
        $orderData = $event['details']['order'];
        $paidValueWt = $orderData['paid_value_wt'];
        $paidBackValueWt = $orderData['paid_back_value_wt'];
        if ($paidBackValueWt && $paidValueWt - $paidBackValueWt == 0) {
            return true;
        }
    }
}
