<?php

namespace StoreKeeper\StoreKeeper\Test\Integration\Export;

use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Math\Random;
use Magento\TestFramework\Helper\Bootstrap;
use StoreKeeper\StoreKeeper\Model\Export\CustomerExportManager;
use StoreKeeper\StoreKeeper\Test\Integration\AbstractTestCase;

class CustomerExportDataTest extends AbstractTestCase
{
    const CUSTOMER_ONE = [
        "path://language_iso2" => "nl",
        "path://business_data.name" => "CompanyName",
        "path://business_data.country_iso2" => "US",
        "path://business_data.vat_number" => "9021090210",
        "path://contact_person.firstname" => "John",
        "path://contact_person.familyname" => "Smith",
        "path://contact_set.email" => "customer_one_address@test.com",
        "path://contact_set.phone" => "3468676",
        "path://contact_set.fax" => null,
        "path://contact_address.name" => "John Smith",
        "path://contact_address.state" => "Alabama",
        "path://contact_address.city" => "Home City",
        "path://contact_address.zipcode" => "75478",
        "path://contact_address.street" => "Customer 1 Shipping Address street",
        "path://contact_address.streetnumber" => null,
        "path://contact_address.flatnumber" => null,
        "path://contact_address.country_iso2" => "US",
        "path://address_billing.name" => "John Smith",
        "path://address_billing.state" => "Alabama",
        "path://address_billing.city" => "Business City",
        "path://address_billing.zipcode" => "75477",
        "path://address_billing.street" => "Customer 1 Billing Address street",
        "path://address_billing.streetnumber" => null,
        "path://address_billing.flatnumber" => null,
        "path://address_billing.country_iso2" => "US"
    ];
    const CUSTOMER_TWO = [
        "path://language_iso2" => "nl",
        "path://business_data.name" => "CompanyName",
        "path://business_data.country_iso2" => "US",
        "path://business_data.vat_number" => "9876543210",
        "path://contact_person.firstname" => "Robert",
        "path://contact_person.familyname" => "Johnson",
        "path://contact_set.email" => "customer_two_address@test.com",
        "path://contact_set.phone" => "1234567",
        "path://contact_set.fax" => null,
        "path://contact_address.name" => "Robert Johnson",
        "path://contact_address.state" => "Alabama",
        "path://contact_address.city" => "Home City",
        "path://contact_address.zipcode" => "12346",
        "path://contact_address.street" => "Customer 2 Shipping Address street",
        "path://contact_address.streetnumber" => null,
        "path://contact_address.flatnumber" => null,
        "path://contact_address.country_iso2" => "US",
        "path://address_billing.name" => "Robert Johnson",
        "path://address_billing.state" => "Alabama",
        "path://address_billing.city" => "Business City",
        "path://address_billing.zipcode" => "12345",
        "path://address_billing.street" => "Customer 2 Billing Address street",
        "path://address_billing.streetnumber" => null,
        "path://address_billing.flatnumber" => null,
        "path://address_billing.country_iso2" => "US"
    ];

    protected $customerExportManager;
    protected $random;
    protected $localeResolver;
    protected $customerCollectionFactory;

    protected function setUp(): void
    {
        $this->random = Bootstrap::getObjectManager()->create(Random::class);
        $this->localeResolver = Bootstrap::getObjectManager()->create(Resolver::class);
        $this->customerCollectionFactory = Bootstrap::getObjectManager()->create(CollectionFactory::class);
        $this->customerExportManager = Bootstrap::getObjectManager()->create(
            CustomerExportManager::class,
            [
                'random' => $this->random,
                'localeResolver' => $this->localeResolver
            ]
        );
    }

    /**
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/customers_for_export.php
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_shop_language nl
     * @magentoDbIsolation enabled
     */
    public function testGetCustomerExportData()
    {
        $this->assertEquals(self::CUSTOMER_ONE, array_slice($this->getCustomerExportData()[0], 1));
        $this->assertEquals(self::CUSTOMER_TWO, array_slice($this->getCustomerExportData()[1], 1));
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCustomerExportData(): array
    {
        $customerCollection = $this->customerCollectionFactory->create();
        $customers = $customerCollection->addAttributeToSelect('*')->getItems();
        $customerExportData = $this->customerExportManager->getCustomerExportData($customers);

        return $customerExportData;
    }
}
