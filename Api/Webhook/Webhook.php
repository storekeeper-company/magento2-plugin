<?php
namespace StoreKeeper\StoreKeeper\Api\Webhook;

use Psr\Log\LoggerInterface;
use StoreKeeper\StoreKeeper\Helper\Config;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Backend\Model\UrlInterface;
use StoreKeeper\StoreKeeper\Model\ResourceModel\StoreKeeperFailedSyncOrder\Collection as StoreKeeperFailedSyncOrderCollection;

class Webhook
{
    private const DATE_TIME_FORMAT = 'D, d M Y H:i:s O';

    private const STOCK_CHANGE_EVENT = 'stock_change';

    private TimezoneInterface $timezone;

    private CollectionFactory $orderCollectionFactory;

    private UrlInterface $backendUrl;

    private StoreKeeperFailedSyncOrderCollection $storeKeeperFailedSyncOrderCollection;

    public function __construct(
        \Magento\Framework\Webapi\Rest\Request $request,
        \StoreKeeper\StoreKeeper\Helper\Api\Auth $authHelper,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        Config $configHelper,
        LoggerInterface $logger,
        TimezoneInterface $timezone,
        CollectionFactory $orderCollectionFactory,
        UrlInterface $backendUrl,
        StoreKeeperFailedSyncOrderCollection $storeKeeperFailedSyncOrderCollection
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
        $this->storeKeeperFailedSyncOrderCollection = $storeKeeperFailedSyncOrderCollection;
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
                        'vendor' => 'StoreKeeper',
                        'platform_name' => 'Magento2',
                        'platform_version' => $this->productMetadata->getVersion(),
                        'software_name' => 'magento2-plugin',
                        'software_version' => $composerJson['version'],
                        'plugin_settings_url' => $this->getPluginSettingsUrl(),
                        'now_date' => $this->getCurrentDateTime(),
                        'system_status' => [
                            'order' => [
                                'last_date' => $this->getLastOrderDateTime(),
                                'last_synchronized_date' => $this->getLastSynchronizedOrderDateTime(),
                                'ids_with_failed_tasks' => $this->getIdsWithFailedTasks()
                            ]
                        ],
                        'extra' => [
                            'url' => $this->authHelper->getStoreBaseUrl(),
                            'sync_mode' => $sync_mode
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
                                "value" => $value
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
    private function getPluginSettingsUrl():string
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
    private function getLastSynchronizedOrderDateTime():string
    {
        $orderCollection = $this->orderCollectionFactory->create()->addAttributeToSort('storekeeper_order_last_sync', 'desc');
        $lastSynchronizedOrder = $orderCollection->getFirstItem();
        $lastSynchronizedOrderDateTime = $this->timezone->date($lastSynchronizedOrder->getData('storekeeper_order_last_sync'))->format(self::DATE_TIME_FORMAT);

        return $lastSynchronizedOrderDateTime;
    }

    /**
     * @return array
     */
    private function getIdsWithFailedTasks():array
    {
        return array_keys($this->storeKeeperFailedSyncOrderCollection->addFieldToFilter('is_failed', 1)->getItems());
    }
}
