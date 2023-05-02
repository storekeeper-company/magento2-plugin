<?php

namespace StoreKeeper\StoreKeeper\Console\Command\Sync;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Psr\Log\LoggerInterface;
use StoreKeeper\StoreKeeper\Helper\Api\Customers as CustomersHelper;
use StoreKeeper\StoreKeeper\Helper\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Customers extends Command
{
    const STORES = 'stores';

    private State $state;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private CustomerRepositoryInterface $customerRepository;
    private CustomersHelper $customersHelper;
    private Config $configHelper;
    private LoggerInterface $logger;

    /**
     * Constructor
     *
     * @param State $state
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomersHelper $customersHelper
     * @param Config $configHelper
     * @param LoggerInterface $logger
     * @param string|null $name
     */
    public function __construct(
        State $state,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CustomerRepositoryInterface $customerRepository,
        CustomersHelper $customersHelper,
        Config $configHelper,
        LoggerInterface $logger,
        string $name = null
    ) {
        parent::__construct($name);
        $this->state = $state;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerRepository = $customerRepository;
        $this->customersHelper = $customersHelper;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('storekeeper:sync:customers');
        $this->setDescription('Sync customers');
        $this->setDefinition([
            new InputOption(
                self::STORES,
                null,
                InputOption::VALUE_REQUIRED,
                'Store ID'
            )
        ]);

        parent::configure();
    }

    /**
     * Sync customers
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);

            $storeId = $input->getOption(self::STORES);

            if (!$this->configHelper->hasMode($storeId, Config::SYNC_ORDERS | Config::SYNC_ALL)) {
                return;
            }

            $output->writeln('<info>Start customer sync</info>');
            $customers = $this->getCustomers($storeId);
            foreach ($customers->getItems() as $customer) {
                try {
                    $output->writeln('<info>Sync customer with id: ' . $customer->getId() . '</info>');
                    $customer = $this->customerRepository->getById($customer->getId());
                    $customerEmail = $customer->getEmail();
                    $relationDataId = $this->customersHelper->findCustomerRelationDataIdByEmail($customerEmail, $storeId);

                    if (!$relationDataId && $customer->getDefaultBilling()) {
                        $relationDataId = $this->customersHelper->createStorekeeperCustomer($customer);
                    }

                    $extensionAttributes = $customer->getExtensionAttributes();
                    $extensionAttributes->setRelationDataId($relationDataId);
                    $customer->setExtensionAttributes($extensionAttributes);
                    $this->customerRepository->save($customer);
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Get store customers list
     *
     * @param $storeId
     * @return \Magento\Customer\Api\Data\CustomerSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCustomers($storeId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(
                'store_id',
                $storeId,
                'eq'
            )
            ->create();

        return $this->customerRepository->getList($searchCriteria);
    }
}
