<?php

namespace StoreKeeper\StoreKeeper\Console\Command\Sync;

use Magento\Framework\App\State;
use StoreKeeper\StoreKeeper\Logger\Logger;
use StoreKeeper\StoreKeeper\Helper\Api\Categories as CategoriesHelper;
use StoreKeeper\StoreKeeper\Helper\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Categories extends Command
{
    const STORES = 'stores';
    public $phpsessid = null;
    private State $state;
    private CategoriesHelper $categoriesHelper;
    private Config $configHelper;
    private Logger $logger;


    /**
     * Constructor
     *
     * @param State $state
     * @param CategoriesHelper $categoriesHelper
     * @param Config $configHelper
     * @param Logger $logger
     */
    public function __construct(
        State $state,
        CategoriesHelper $categoriesHelper,
        Config $configHelper,
        Logger $logger
    ) {
        parent::__construct();

        $this->state = $state;
        $this->categoriesHelper = $categoriesHelper;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName("storekeeper:sync:categories");
        $options = [
            new InputOption(
                self::STORES,
                null,
                InputOption::VALUE_REQUIRED,
                'Store IDs'
            )
        ];
        $this->setDescription('The Store IDs');
        $this->setDefinition($options);

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

            $language = $this->categoriesHelper->getLanguageForStore($storeId);

            $current = 0;
            $total = null;

            while (is_null($total) || $current < $total) {
                $response = $this->categoriesHelper->listTranslatedCategoryForHooks(
                    $storeId,
                    $language,
                    $current,
                    2,
                    [
                        [
                            "name" => "category_tree/path",
                            "dir" => "asc"
                        ],
                        [
                            'name' => 'id',
                            'dir' => 'asc'
                        ]
                    ],
                    []
                );

                $total = $response['total'];
                $current += $response['count'];

                $results = $response['data'];

                foreach ($results as $result) {
                    try {
                        if ($category = $this->categoriesHelper->exists($storeId, $result)) {
                            $category = $this->categoriesHelper->update($storeId, $category, $result);
                        } else {
                            $category = $this->categoriesHelper->create($storeId, $result);
                        }
                    } catch (\Exception $e) {
                        $this->logger->error($e->getMessage(), $this->logger->buildReportData($e));
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $this->logger->buildReportData($e));
        }
    }
}
