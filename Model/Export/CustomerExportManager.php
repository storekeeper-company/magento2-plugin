<?php

namespace StoreKeeper\StoreKeeper\Model\Export;

use Magento\Framework\Math\Random;
use Magento\Framework\Locale\Resolver;

class CustomerExportManager
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
        'Address name',
        'State',
        'City',
        'Zipcode',
        'Street',
        'Street number',
        'Flat number',
        'Country iso2',
        'Address name',
        'State',
        'City',
        'Zipcode',
        'Street',
        'Street number',
        'Flat number',
        'Country iso2'
    ];

    private Random $random;
    private Resolver $localeResolver;

    public function __construct(
        Random $random,
        Resolver $localeResolver
    ) {
        $this->random = $random;
        $this->localeResolver = $localeResolver;
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
                $billingAddress->getName(), // 'path://contact_address.name'
                $billingAddress->getRegion(), // 'path://contact_address.state'
                $billingAddress->getCity(), // 'path://contact_address.city'
                $billingAddress->getPostcode(), // 'path://contact_address.zipcode'
                implode(', ', $billingAddress->getStreet()), // 'path://contact_address.street'
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
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getMappedHeadersLabels(): array
    {
        return array_combine(self::HEADERS_PATHS, self::HEADERS_LABELS);
    }

    /**
     * @return string
     */
    private function getCurrentLocale(): string
    {
        $currentLocaleCode = $this->localeResolver->getLocale();
        $languageCode = strstr($currentLocaleCode, '_', true);

        return $languageCode;
    }
}
