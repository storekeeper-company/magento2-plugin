<?php

namespace StoreKeeper\StoreKeeper\Console\Command\Sync;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use StoreKeeper\StoreKeeper\Helper\Api\Customers as CustomersHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Customers extends Command
{
    const STORES = 'stores';

    /**
     * @var State
     */
    private $state;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var CustomersHelper
     */
    private $customersHelper;

    public function __construct(
        State $state,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CustomerRepositoryInterface $customerRepository,
        CustomersHelper $customersHelper,
        string $name = null
    ) {
        parent::__construct($name);

        $this->state = $state;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerRepository = $customerRepository;
        $this->customersHelper = $customersHelper;
    }

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_ADMINHTML);

        $storeId = $input->getOption(self::STORES);

        $output->writeln('<info>Start customer sync</info>');
        $customers = $this->getCustomers($storeId);

        foreach ($customers->getItems() as $customer) {
            $customer = $this->customerRepository->getById($customer->getId());
            $customerEmail = $customer->getEmail();
            $relationDataId = $this->customersHelper->findCustomerRelationDataIdByEmail($customerEmail, $storeId);

            var_dump($relationDataId);
            if (!$relationDataId) {
                $relationDataId = $this->customersHelper->createStorekeeperCustomer($customer);
            }

            try {
                $customer->setCustomAttribute("relation_data_id", $relationDataId);
                $extensionAttributes = $customer->getExtensionAttributes();
                $extensionAttributes->setRelationDataId(14);
                $customer->setExtensionAttributes($extensionAttributes);
                $this->customerRepository->save($customer);
                var_dump($customer->getCustomAttributes());
            } catch (\Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
            }
        }
    }

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
