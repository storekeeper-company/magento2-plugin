<?php

namespace StoreKeeper\StoreKeeper\Console\Command\Sync;

use Magento\Framework\App\State;
use Magento\Store\Model\StoreManagerInterface;
use StoreKeeper\StoreKeeper\Logger\Logger;
use StoreKeeper\StoreKeeper\Helper\Api\Products as ProductsHelper;
use StoreKeeper\StoreKeeper\Helper\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Products extends Command
{
    const STORES = 'stores';
    public $phpsessid = null;
    private State $state;
    private ProductsHelper $productsHelper;
    private StoreManagerInterface $storeManager;
    private Config $configHelper;
    private Logger $logger;

    /**
     * @param State $state
     * @param ProductsHelper $productsHelper
     * @param StoreManagerInterface $storeManager
     * @param Config $configHelper
     * @param Logger $logger
     * @param string|null $name
     */
    public function __construct(
        State $state,
        ProductsHelper $productsHelper,
        StoreManagerInterface $storeManager,
        Config $configHelper,
        Logger $logger,
        string $name = null
    ) {
        parent::__construct($name);

        $this->state = $state;
        $this->productsHelper = $productsHelper;
        $this->storeManager = $storeManager;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName("storekeeper:sync:products");
        $this->setDescription('The Store IDs');
        $this->setDefinition([
            new InputOption(
                self::STORES,
                null,
                InputOption::VALUE_REQUIRED,
                'Store IDs'
            )
        ]);

        parent::configure();
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return null|int
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        try {
            $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

            $storeId = $input->getOption(self::STORES);

            if (!$this->configHelper->hasMode($storeId, Config::SYNC_PRODUCTS | Config::SYNC_ALL)) {
                return;
            }

            $language = $this->productsHelper->getLanguageForStore($storeId);

            $current = 0;
            $total = null;

            while (is_null($total) || $current < $total) {
                $response = $this->productsHelper->naturalSearchShopFlatProductForHooks(
                    $storeId,
                    ' ',
                    $language,
                    $current,
                    5,
                    [],
                    []
                );

                $total = $response['total'];
                $current += $response['count'];

                $results = $response['data'];

                foreach ($results as $result) {
                    try {
                        if ($product = $this->productsHelper->exists($storeId, $result)) {
                            $product = $this->productsHelper->update($storeId, $product, $result);
                        } else {
                            $product = $this->productsHelper->onCreate($storeId, $result);
                        }
                    } catch (\Exception $e) {
                        $this->logger->error($e->getMessage(), $this->logger->buildReportData($e));
                    }
                }
            }
        } catch(\Exception $e) {
            $this->logger->error(
                $e->getFile() . ' at ' . $e->getLine() . ' : ' . $e->getMessage(),
                $this->logger->buildReportData($e)
            );
        }
    }
}
