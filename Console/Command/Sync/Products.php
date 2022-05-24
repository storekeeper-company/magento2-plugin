<?php

namespace StoreKeeper\StoreKeeper\Console\Command\Sync;

use StoreKeeper\ApiWrapper\ApiWrapper;
use StoreKeeper\ApiWrapper\Wrapper\FullJsonAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SomeCommand
 */
class Products extends Command
{
    const STORES = 'stores';

    public $phpsessid = null;

    public function __construct(
        \Magento\Framework\App\State $state,
        \StoreKeeper\StoreKeeper\Helper\Api\Products $productsHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct();

        $this->state = $state;
        $this->productsHelper = $productsHelper;
        $this->storeManager = $storeManager;
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
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

        $storeId = 1;
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

            echo "\nProcessing " . ($current + $response['count']) . ' out of ' . $response['total'] . " results\n\n";

            $total = $response['total'];
            $current += $response['count'];

            $results = $response['data'];

            foreach ($results as $result) {

                if ($product = $this->productsHelper->exists($storeId, $result)) {
                    $product = $this->productsHelper->update($storeId, $product, $result);
                } else {
                    $product = $this->productsHelper->create($storeId, $result);
                }
            }
        }

        echo "\nDone!\n";
    }
}
