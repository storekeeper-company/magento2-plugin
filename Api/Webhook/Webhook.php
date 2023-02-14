<?php
namespace StoreKeeper\StoreKeeper\Api\Webhook;

use Psr\Log\LoggerInterface;
use StoreKeeper\StoreKeeper\Helper\Config;

class Webhook
{
    public function __construct(
        \Magento\Framework\Webapi\Rest\Request $request,
        \StoreKeeper\StoreKeeper\Helper\Api\Auth $authHelper,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        Config $configHelper,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->authHelper = $authHelper;
        $this->json = $json;
        $this->publisher = $publisher;
        $this->productMetadata = $productMetadata;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
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

            file_put_contents("post-webhook.log", json_encode($bodyParams, JSON_PRETTY_PRINT), FILE_APPEND);

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
                        'platform_name' => 'Magento 2',
                        'platform_version' => $this->productMetadata->getVersion(),
                        'software_name' => 'storekeeper-magento2-b2c',
                        'software_version' => $composerJson['version'],
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
                    foreach ($eventNames as $eventName) {
                        if ($eventName == "stock_change" && $this->configHelper->hasMode($storeId, Config::SYNC_ORDERS | Config::SYNC_PRODUCTS | Config::SYNC_ALL)) {
                            $messages[] = "Processing event \"stock_change\"";
                        // continue;
                        } elseif ($entity == "ShopProduct" && $this->configHelper->hasMode($storeId, Config::SYNC_PRODUCTS | Config::SYNC_ALL)) {
                            $messages[] = "Processing entity \"ShopProduct\"";
                        // $messages[] = "Skipping products: mode not allowed";
                        // continue;
                        } elseif ($entity == "Category" && $this->configHelper->hasMode($storeId, Config::SYNC_PRODUCTS | Config::SYNC_ALL)) {
                            $messages[] = "Processing entity \"Category\"";
                        // $messages[] = "Skipping categories: mode not allowed";
                        // continue;
                        } elseif ($entity == "Order" && $this->configHelper->hasMode($storeId, Config::SYNC_ORDERS | Config::SYNC_ALL)) {
                            $messages[] = "Processing entity \"Order\"";
                        // $messages[] = "Skipping orders: mode not allowed";
                        // continue;
                        } else {
                            $messages[] = "Skipping {$entity}::{$eventName}: mode not allowed";
                            continue;
                        }

                        $success = true;

                        $n = [
                            "type" => $eventName,
                            "entity" => $entity,
                            "storeId" => $storeId,
                            "module" => $module,
                            "key" => $key,
                            "value" => $value
                        ];
                        file_put_contents("queue.log", $this->json->serialize($n) . "\n", FILE_APPEND);
                        $this->publisher->publish("storekeeper.queue.events", $this->json->serialize($n));
                    }

                    $response['success'] = $success;
                    $response['message'] = implode(', ', $messages);
                } elseif ($action == "deactivated") {
                    preg_match("/(\w+)\::(\w+)\(([a-z]+)\=([0-9]+)\)/", $payload['backref'], $matches);

                    list($group, $module, $entity, $key, $value) = $matches;

                    foreach ($payload['events'] as $id => $eventData) {
                        $n = [
                            "type" => $eventData['event'],
                            "storeId" => $storeId,
                            "module" => $module,
                            "entity" => $entity,
                            "key" => $key,
                            "value" => $value
                        ];
                        $this->publisher->publish("storekeeper.queue.events", $this->json->serialize($n));
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
        } catch (\Exception | \Error $e) {
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
}
