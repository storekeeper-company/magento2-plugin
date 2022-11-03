<?php
namespace StoreKeeper\StoreKeeper\Api\Webhook;

use Psr\Log\LoggerInterface;
use SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser;
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

            $response = [
                "success" => true
            ];

            if ($action == "info") {

                // retrieve the current plugin version
                $composerFile = file_get_contents(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . "composer.json");
                $composerJson = json_decode($composerFile, true);

                $response = [
                    "success" => true,
                    'vendor' => 'StoreKeeper',
                    'platform_name' => 'Magento 2',
                    'platform_version' => $this->productMetadata->getVersion(),
                    'software_name' => 'storekeeper-magento2-b2c',
                    'software_version' => $composerJson['version'],
                    'extra' => [],
                ];
                
            } else if ($action == "init") {
                $this->authHelper->setAuthDataForWebsite($storeId, $payload);

                $response = [
                    "success" => true
                ];

            } else if ($action == "events") {
                preg_match("/(\w+)\::(\w+)\(([a-z]+)\=([0-9]+)\)/", $payload['backref'], $matches);

                list($group, $module, $entity, $key, $value) = $matches;

                $eventNames = array_map(function ($event) {
                    return $event['event'];
                }, $payload['events']);
                $eventNames = array_unique($eventNames);

                $messages = [];
                $success = false;

                foreach ($eventNames as $eventName) {

                    if ($eventName == "stock_change" && !$this->configHelper->hasMode($storeId, Config::SYNC_ORDERS | Config::SYNC_PRODUCTS | Config::SYNC_ALL)) {
                        $messages[] = "Skipping stock changes: mode not allowed";
                        continue;
                    } else if ($entity == "ShopProduct" && !$this->configHelper->hasMode($storeId, Config::SYNC_PRODUCTS | Config::SYNC_ALL)) {
                        $messages[] = "Skipping products: mode not allowed";
                        continue;
                    } else if ($entity == "Category" && !$this->configHelper->hasMode($storeId, Config::SYNC_PRODUCTS | Config::SYNC_ALL)) {
                        $messages[] = "Skipping categories: mode not allowed";
                        continue;
                    } else if ($entity == "Order" && !$this->configHelper->hasMode($storeId, Config::SYNC_ORDERS | Config::SYNC_ALL)) {
                        $messages[] = "Skipping orders: mode not allowed";
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

            } else if ($action == "deactivated") {
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

            return $this->response($response);
        } catch (\Exception | \Error $e) {
            $this->logger->error($e->getMessage());
            return $this->response([
                'success' => false,
                'message' => "An error occurred: {$e->getMessage()}"
            ]);
        }
    }

    private function response(array $response = [])
    {
        http_response_code(200);
        header("Content-Type: application/json");
        echo json_encode($response);
        exit;
    }
}
