<?php

namespace StoreKeeper\StoreKeeper\Model\Disconnect;

use Psr\Log\LoggerInterface;
use Magento\Store\Model\Store;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory  as ProductCollectionFactory ;

use StoreKeeper\StoreKeeper\Helper\Config;

class Consumer
{
    private ProductCollectionFactory $productCollectionFactory;
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger,
        ProductCollectionFactory $productCollectionFactory
    ) {
        $this->logger = $logger;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @return void
     */
    public function process(): void
    {
        try {
            $productCollection = $this->productCollectionFactory->create();
            $productCollection
                ->setStoreId(Store::DEFAULT_STORE_ID)
                ->addAttributeToFilter('storekeeper_product_id', ['neq' => null])
                ->setFlag('has_stock_status_filter', false)
                ->addAttributeToSelect('*');

            if ($productCollection->count()) {
                foreach ($productCollection->getItems() as $product) {
                    $product->setData('storekeeper_product_id', '');
                    $product->save();
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->logger->error($e->getTraceAsString());
        }
    }
}
