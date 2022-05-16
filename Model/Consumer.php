<?php


namespace StoreKeeper\StoreKeeper\Model;
 
use Magento\Framework\MessageQueue\ConsumerConfiguration;
use Magento\Framework\App\Config\ScopeConfigInterface;
use StoreKeeper\StoreKeeper\Helper\Api\Categories;
use StoreKeeper\StoreKeeper\Helper\Api\Products;

/**
 * Class Consumer used to process OperationInterface messages.
 */
class Consumer
{
    const CONSUMER_NAME = "storekeeper.queue.events";
 
    const QUEUE_NAME = "storekeeper.queue.events";
    
    public function __construct(
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        Products $productsHelper,
        Categories $categoriesHelper,
        \Magento\Framework\App\State $state
    ) {
        
        $this->jsonHelper = $jsonHelper;
        $this->productsHelper = $productsHelper;
        $this->categoriesHelper = $categoriesHelper;    
        $this->state = $state; 
        echo "construct";
    }
 
    /**
     * Process 
     * 
     * @param string $request
     * @return void
     */
    public function process($request)
    {   
        // $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

        echo "work\n";

        $data = json_decode($request, true);
        $storeId = $data['storeId'] ?? null;
        $module = $data['module'];
        $entity = $data['entity'];
        $key = $data['key'];
        $value = $data['value'];
        $type = $data['type'];

        if (is_null($storeId)) {
            throw new \Exception("Missing store ID");
        }

        try {
            if ($type == "updated") {
                if ($entity == "ShopProduct") {
                    $this->productsHelper->updateById($storeId, $value);
                } else if ($entity == 'Category') {
                    $this->categoriesHelper->updateById($storeId, $value);
                }
            } else if ($type == "deactivated") {
                if ($entity == "ShopProduct") {
                    $this->productsHelper->deactivate($storeId, $value);
                }
            } else if ($type == "activated") {
                if ($entity == "ShopProduct") {
                    $this->productsHelper->activate($storeId, $value);
                }
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
    }
}