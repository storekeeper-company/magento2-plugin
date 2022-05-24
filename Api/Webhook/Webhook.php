<?php 
namespace StoreKeeper\StoreKeeper\Api\Webhook;

use SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser;

class Webhook
{

    public function __construct(
        \Magento\Framework\Webapi\Rest\Request $request,
        \StoreKeeper\StoreKeeper\Helper\Api\Auth $authHelper,
    	\Magento\Framework\Serialize\Serializer\Json $json,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher
    ) {
        $this->request = $request;
        $this->authHelper = $authHelper;
        $this->publisher = $publisher;
        $this->publisher = $publisher;
        $this->json = $json;
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
        $bodyParams = $this->request->getBodyParams();

        file_put_contents("post-webhook.log", json_encode($bodyParams, JSON_PRETTY_PRINT), FILE_APPEND);

        $payload = $bodyParams['payload'] ?? [];
        $action = $bodyParams['action'] ?? null;

        $response = [
            "success" => true
        ];


        if ($action == "init") {
            $this->authHelper->setAuthDataForWebsite($storeId, $payload);

            $response = [
                "success" => true,
                'vendor' => 'StoreKeeper',
                'platform_name' => 'Magento 2',
                'platform_version' => '1.0.0',
                'software_name' => 'storekeeper-magento2-b2c',
                'software_version' => '0.0.1',
                'extra' => [],
            ];

        } else if ($action == "events") {
            preg_match("/(\w+)\::(\w+)\(([a-z]+)\=([0-9]+)\)/", $payload['backref'], $matches);

            list($group, $module, $entity, $key, $value) = $matches;

            $eventNames = array_map(function ($event) {
                return $event['event'];
            }, $payload['events']);
            $eventNames = array_unique($eventNames);

            foreach ($eventNames as $eventName) {
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

        http_response_code(200);
        header("Content-Type: application/json");
        echo json_encode($response);
        exit;
        
    }
}