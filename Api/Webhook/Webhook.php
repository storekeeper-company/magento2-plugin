<?php
namespace StoreKeeper\StoreKeeper\Api\Webhook;

use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Stdlib\DateTime\DateTime;
use StoreKeeper\StoreKeeper\Logger\Logger;
use StoreKeeper\StoreKeeper\Api\Data\EventLogInterface;
use StoreKeeper\StoreKeeper\Api\Data\EventLogInterfaceFactory;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;
use StoreKeeper\StoreKeeper\Helper\Config;
use StoreKeeper\StoreKeeper\Model\EventLogRepository;
use StoreKeeper\StoreKeeper\Helper\Info;
use Symfony\Component\HttpFoundation\JsonResponse;

class Webhook
{
    public const DATE_TIME_FORMAT = 'D, d M Y H:i:s O';
    private const STOCK_CHANGE_EVENT = 'stock_change';
    private Request $request;
    private Auth $authHelper;
    private Json $json;
    private PublisherInterface $publisher;
    private Config $configHelper;
    private Logger $logger;
    private StoreKeeperFailedSyncOrderCollection $storeKeeperFailedSyncOrderCollection;
    private JsonResponse $jsonResponse;
    private EventLogInterfaceFactory $eventLogFactory;
    private EventLogRepository $eventLogRepository;
    private Info $infoHelper;
    private DateTime $dateTime;

    /**
     * Constructor
     *
     * @param Request $request
     * @param Auth $authHelper
     * @param Json $json
     * @param PublisherInterface $publisher
     * @param Config $configHelper
     * @param Logger $logger
     * @param JsonResponse $jsonResponse
     * @param EventLogInterfaceFactory $eventLogFactory
     * @param EventLogRepository $eventLogRepository
     * @param Info $infoHelper
     * @param DateTime $dateTime
     */
    public function __construct(
        Request $request,
        Auth $authHelper,
        Json $json,
        PublisherInterface $publisher,
        Config $configHelper,
        Logger $logger,
        JsonResponse $jsonResponse,
        EventLogInterfaceFactory $eventLogFactory,
        EventLogRepository $eventLogRepository,
        Info $infoHelper,
        DateTime $dateTime
    ) {
        $this->request = $request;
        $this->authHelper = $authHelper;
        $this->json = $json;
        $this->publisher = $publisher;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
        $this->jsonResponse = $jsonResponse;
        $this->eventLogFactory = $eventLogFactory;
        $this->eventLogRepository = $eventLogRepository;
        $this->infoHelper = $infoHelper;
        $this->dateTime = $dateTime;
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
     * @param string $storeId
     */
    public function postExecute(string $storeId)
    {
        try {
            $response = $this->postExecuteWithResponse($this->authHelper->getStoreId($storeId));
            return $this->response($response->getContent(), $response->getStatusCode());
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $this->logger->buildReportData($e));
            $response = $this->jsonResponse->setData([
                'success' => false,
                'message' => "An error occurred: {$e->getMessage()}"
            ]);

            return $this->response($response->getContent(), $response->getStatusCode());
        }
    }

    /**
     * @param string $storeId
     * @return JsonResponse
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function postExecuteWithResponse(string $storeId): JsonResponse
    {
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
                return $this->jsonResponse->setData([
                    'success' => false,
                    'message' => __("Store is not connected")
                ]);
            }

            if ($action == "info") {
                $response = $this->infoHelper->getInfoHookData($storeId);
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
                            if ($isRefund) {
                                break;
                            }
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
            } elseif ($action == "disconnect") {
                $message = [
                    "type" => $action,
                    "storeId" => $storeId
                ];

                $this->publisher->publish("storekeeper.queue.events", $this->json->serialize($message));
            }
        } else {
            $status = 403;
            $response = [
                'success' => false,
                'message' => __('Not allowed')
            ];
        }

        $this->addEventLog($action, $status);
        $this->logger->info("Received action {$action}: " . json_encode($response));

        return $this->jsonResponse->setData($response);
    }

    /**
     * @param string $response
     * @param int|null $status
     */
    private function response(string $response = '', ?int $status = 200): void
    {
        http_response_code($status);
        header("Content-Type: application/json");
        echo $response;
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
        return false;
    }

    /**
     * @param string $action
     * @param string $status
     * @return void
     */
    private function addEventLog(string $action, string $status): void
    {
        $eventLog = $this->eventLogFactory->create();
        $eventLog->addData([
            'request_route' => $this->request->getPathInfo(),
            'request_body' => $this->request->getContent(),
            'request_method' => $this->request->getMethod(),
            'request_action' => $action,
            'response_code' => $status,
            'date' => $this->dateTime->gmtDate()
        ]);

        $this->eventLogRepository->save($eventLog);
    }
}
