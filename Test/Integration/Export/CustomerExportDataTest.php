<?php

namespace StoreKeeper\StoreKeeper\Test\Integration\Export;

use StoreKeeper\StoreKeeper\Test\Integration\AbstractTest;
use Magento\TestFramework\Helper\Bootstrap;

class CustomerExportDataTest extends AbstractTest
{
    const CUSTOMER_ONE = [
        "path://language_iso2" => "en",
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
        "path://contact_address.city" => "CityM",
        "path://contact_address.zipcode" => "75477",
        "path://contact_address.street" => "CustomerAddress1",
        "path://contact_address.streetnumber" => null,
        "path://contact_address.flatnumber" => null,
        "path://contact_address.country_iso2" => "US",
        "path://address_billing.name" => "John Smith",
        "path://address_billing.state" => "Alabama",
        "path://address_billing.city" => "CityM",
        "path://address_billing.zipcode" => "75477",
        "path://address_billing.street" => "CustomerAddress1",
        "path://address_billing.streetnumber" => null,
        "path://address_billing.flatnumber" => null,
        "path://address_billing.country_iso2" => "US"
    ];
    const CUSTOMER_TWO = [
        "path://language_iso2" => "en",
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
        "path://contact_address.city" => "CityC",
        "path://contact_address.zipcode" => "12345",
        "path://contact_address.street" => "CustomerAddress2",
        "path://contact_address.streetnumber" => null,
        "path://contact_address.flatnumber" => null,
        "path://contact_address.country_iso2" => "US",
        "path://address_billing.name" => "Robert Johnson",
        "path://address_billing.state" => "Alabama",
        "path://address_billing.city" => "CityC",
        "path://address_billing.zipcode" => "12345",
        "path://address_billing.street" => "CustomerAddress2",
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
        $this->random = Bootstrap::getObjectManager()->create(\Magento\Framework\Math\Random::class);
        $this->localeResolver = Bootstrap::getObjectManager()->create(\Magento\Framework\Locale\Resolver::class);
        $this->customerCollectionFactory = Bootstrap::getObjectManager()->create(\Magento\Customer\Model\ResourceModel\Customer\CollectionFactory::class);
        $this->customerExportManager = Bootstrap::getObjectManager()->create(
            \StoreKeeper\StoreKeeper\Model\Export\CustomerExportManager::class,
            [
                'random' => $this->random,
                'localeResolver' => $this->localeResolver
            ]
        );
    }

    /**
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/customers_for_export.php
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_shop_language en
     * @magentoDbIsolation enabled
     */
    public function testGetCustomerExportData()
    {
        $customerCollection = $this->customerCollectionFactory->create();
        $customers = $customerCollection->addAttributeToSelect('*')->getItems();
        $customerExportData = $this->customerExportManager->getCustomerExportData($customers);
        $this->assertEquals(self::CUSTOMER_ONE, array_slice($customerExportData[0], 1));
        $this->assertEquals(self::CUSTOMER_TWO, array_slice($customerExportData[1], 1));
    }
}
