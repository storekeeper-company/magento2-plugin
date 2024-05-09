<?php

namespace StoreKeeper\StoreKeeper\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

class StockSource implements OptionSourceInterface
{
    /**
     * @var SourceRepositoryInterface
     */
    protected $sourceRepository;

    /**
     * StockSources constructor
     *
     * @param SourceRepositoryInterface $sourceRepository
     */
    public function __construct(
        SourceRepositoryInterface $sourceRepository
    ) {
        $this->sourceRepository = $sourceRepository;
    }

    public function toOptionArray()
    {
        $options = [];
        $sources = $this->sourceRepository->getList();
        foreach ($sources->getItems() as $source) {
            $options[] = [
                'value' => $source->getSourceCode(),
                'label' => $source->getName()
            ];
        }
        return $options;
    }
}
