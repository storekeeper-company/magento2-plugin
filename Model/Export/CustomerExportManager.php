<?php

namespace StoreKeeper\StoreKeeper\Model\Export;

use Magento\Framework\Locale\Resolver;
use Magento\Framework\Math\Random;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\StoreConfigManagerInterface;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;
use StoreKeeper\StoreKeeper\Logger\Logger;

class CustomerExportManager extends AbstractExportManager
{
    const HEADERS_PATHS = [
        'path://shortname',
        'path://language_iso2',
        'path://business_data.name',
        'path://business_data.country_iso2',
        'path://business_data.vat_number',
        'path://contact_person.firstname',
        'path://contact_person.familyname',
        'path://contact_set.email',
        'path://contact_set.phone',
        'path://contact_set.fax',
        'path://contact_address.name',
        'path://contact_address.state',
        'path://contact_address.city',
        'path://contact_address.zipcode',
        'path://contact_address.street',
        'path://contact_address.streetnumber',
        'path://contact_address.flatnumber',
        'path://contact_address.country_iso2',
        'path://address_billing.name',
        'path://address_billing.state',
        'path://address_billing.city',
        'path://address_billing.zipcode',
        'path://address_billing.street',
        'path://address_billing.streetnumber',
        'path://address_billing.flatnumber',
        'path://address_billing.country_iso2'
    ];
    const HEADERS_LABELS = [
        'GUID',
        'Language (iso2)',
        'Company',
        'Company country',
        'Company vat',
        'First name',
        'Family name',
        'Email',
        'Phone',
        'Fax',
        'Contact Address name',
        'Contact State',
        'Contact City',
        'Contact Zipcode',
        'Contact Street',
        'Contact Street number',
        'Contact Flat number',
        'Contact Country iso2',
        'Billing Address name',
        'Billing State',
        'Billing City',
        'Billing Zipcode',
        'Billing Street',
        'Billing Street number',
        'Billing Flat number',
        'Billing Country iso2'
    ];

    private Random $random;
    private Auth $authHelper;
    private Logger $logger;

    public function __construct(
        Resolver $localeResolver,
        Random $random,
        StoreManagerInterface $storeManager,
        StoreConfigManagerInterface $storeConfigManager,
        Auth $authHelper,
        Logger $logger
    ) {
        parent::__construct($localeResolver, $storeManager, $storeConfigManager, $authHelper);
        $this->random = $random;
        $this->logger = $logger;
    }

    /**
     * @param array $customers
     * @return array
     */
    public function getCustomerExportData(array $customers): array
    {
        $result = [];
        foreach ($customers as $customer) {
            $billingAddress = $customer->getDefaultBillingAddress();
            $shippingAddress = $customer->getDefaultShippingAddress();
            if ($billingAddress && $shippingAddress) {
                $data = [
                    $this->random->getUniqueHash(), // 'path://shortname' - 'GUID'
                    $this->getCurrentLocale(), // 'path://language_iso2' - 'Language (iso2)'
                    $billingAddress->getCompany(), // 'path://business_data.name'
                    $billingAddress->getCountryId(), // 'path://business_data.country_iso2'
                    $billingAddress->getVatId(), // 'path://business_data.vat_number'
                    $billingAddress->getFirstname(), // 'path://contact_person.firstname'
                    $billingAddress->getLastname(), // 'path://contact_person.familyname'
                    $customer->getEmail(), // 'path://contact_set.email'
                    $billingAddress->getTelephone(), // 'path://contact_set.phone'
                    null, // 'path://contact_set.fax'
                    $shippingAddress->getName(), // 'path://contact_address.name'
                    $shippingAddress->getRegion(), // 'path://contact_address.state'
                    $shippingAddress->getCity(), // 'path://contact_address.city'
                    $shippingAddress->getPostcode(), // 'path://contact_address.zipcode'
                    implode(', ', $shippingAddress->getStreet()), // 'path://contact_address.street'
                    null, // 'path://contact_address.streetnumber'
                    null, // 'path://contact_address.flatnumber'
                    $billingAddress->getCountryId(), // 'path://contact_address.country_iso2'
                    $billingAddress->getName(), // 'path://address_billing.name'
                    $billingAddress->getRegion(), // 'path://address_billing.state'
                    $billingAddress->getCity(), // 'path://address_billing.city'
                    $billingAddress->getPostcode(), // 'path://address_billing.zipcode'
                    implode(', ', $billingAddress->getStreet()), // 'path://address_billing.street'
                    null, // 'path://address_billing.streetnumber'
                    null, // 'path://address_billing.flatnumber'
                    $billingAddress->getCountryId(), // 'path://address_billing.country_iso2'
                ];
                $result[] = array_combine(self::HEADERS_PATHS, $data);
            } else {
                $this->logger->info(
                    __(
                        'Skipped exporting customer due to missing address data, customer id: %1',
                        $customer->getId()
                    )
                );
            }
        }

        return $result;
    }
}
