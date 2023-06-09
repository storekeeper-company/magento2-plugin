<?php

namespace StoreKeeper\StoreKeeper\Model\Export;

use Psr\Log\LoggerInterface;
use StoreKeeper\StoreKeeper\Model\ProductExporter;

class Consumer
{
    private LoggerInterface $logger;
    private ProductExporter $productExporter;

    public function __construct(
        LoggerInterface $logger,
        ProductExporter $productExporter
    ) {
        $this->logger = $logger;
        $this->productExporter = $productExporter;
    }

    /**
     * @param string $request
     * @return void
     */
    public function process($request): void
    {
        try {
            $data = json_decode($request, true);
            if ($data['entity'] == 'catalog_product') {
                $this->productExporter->exportProductToCsv();
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
