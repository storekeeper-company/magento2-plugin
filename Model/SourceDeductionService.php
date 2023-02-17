<?php

namespace StoreKeeper\StoreKeeper\Model;

use Magento\Inventory\Model\SourceItem\Command\DecrementSourceItemQty;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventorySalesApi\Api\GetStockBySalesChannelInterface;
use Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterface;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionServiceInterface;

class SourceDeductionService implements SourceDeductionServiceInterface
{
    /**
     * Constant for zero stock quantity value.
     */
    private const ZERO_STOCK_QUANTITY = 0.0;

    /**
     * @var GetSourceItemBySourceCodeAndSku
     */
    private $getSourceItemBySourceCodeAndSku;

    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @var GetStockBySalesChannelInterface
     */
    private $getStockBySalesChannel;

    /**
     * @var DecrementSourceItemQty
     */
    private $decrementSourceItem;

    /**
     * @param GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param GetStockBySalesChannelInterface $getStockBySalesChannel
     * @param DecrementSourceItemQty $decrementSourceItem
     */
    public function __construct(
        GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku,
        GetStockItemConfigurationInterface $getStockItemConfiguration,
        GetStockBySalesChannelInterface $getStockBySalesChannel,
        DecrementSourceItemQty $decrementSourceItem
    ) {
        $this->getSourceItemBySourceCodeAndSku = $getSourceItemBySourceCodeAndSku;
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->getStockBySalesChannel = $getStockBySalesChannel;
        $this->decrementSourceItem = $decrementSourceItem;
    }

    /**
     * @inheritdoc
     */
    public function execute(SourceDeductionRequestInterface $sourceDeductionRequest): void
    {
        $sourceCode = $sourceDeductionRequest->getSourceCode();
        $salesChannel = $sourceDeductionRequest->getSalesChannel();
        $stockId = $this->getStockBySalesChannel->execute($salesChannel)->getStockId();
        $sourceItemDecrementData = [];
        foreach ($sourceDeductionRequest->getItems() as $item) {
            $itemSku = $item->getSku();
            $qty = $item->getQty();
            $stockItemConfiguration = $this->getStockItemConfiguration->execute(
                $itemSku,
                $stockId
            );

            if (!$stockItemConfiguration->isManageStock()) {
                //We don't need to Manage Stock
                continue;
            }

            $sourceItem = $this->getSourceItemBySourceCodeAndSku->execute($sourceCode, $itemSku);
            //Removed if statement that prevents negative stock
            $sourceItem->setQuantity($sourceItem->getQuantity() - $qty);
            $stockStatus = $this->getSourceStockStatus(
                $stockItemConfiguration,
                $sourceItem
            );
            $sourceItem->setStatus($stockStatus);
            $sourceItemDecrementData[] = [
                'source_item' => $sourceItem,
                'qty_to_decrement' => $qty
            ];
        }

        if (!empty($sourceItemDecrementData)) {
            $this->decrementSourceItem->execute($sourceItemDecrementData);
        }
    }

    /**
     * Get source item stock status after quantity deduction.
     *
     * @param StockItemConfigurationInterface $stockItemConfiguration
     * @param SourceItemInterface $sourceItem
     * @return int
     */
    private function getSourceStockStatus(
        StockItemConfigurationInterface $stockItemConfiguration,
        SourceItemInterface $sourceItem
    ): int {
        $sourceItemQty = $sourceItem->getQuantity() ?: self::ZERO_STOCK_QUANTITY;
        $currentStatus = (int)$stockItemConfiguration->getExtensionAttributes()->getIsInStock();
        $calculatedStatus = $sourceItemQty === $stockItemConfiguration->getMinQty() && !$stockItemConfiguration->getBackorders()
            ? SourceItemInterface::STATUS_OUT_OF_STOCK
            : SourceItemInterface::STATUS_IN_STOCK;

        return $currentStatus === SourceItemInterface::STATUS_OUT_OF_STOCK ? $currentStatus : $calculatedStatus;
    }
}
