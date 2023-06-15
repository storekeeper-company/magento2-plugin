<?php

namespace StoreKeeper\StoreKeeper\Model\Export;

class CustomerExportManager
{
    const HEADERS_PATHS = [
        'path://id',
        'path://shortname',
        'path://language_iso2',
        'path://business_data.name',
        'path://business_data.coc_number',
        'path://business_data.country_iso2',
        'path://business_data.vat_number',
        'path://contact_person.firstname',
        'path://contact_person.familyname_prefix',
        'path://contact_person.familyname',
        'path://contact_set.email',
        'path://contact_set.phone',
        'path://contact_set.fax',
        'path://contact_set.www',
        'path://contact_set.allow_general_communication',
        'path://contact_set.allow_offer_communication',
        'path://contact_set.allow_special_communication',
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
        'Relation number',
        'GUID',
        'Language (iso2)',
        'Company',
        'Company number',
        'Company country',
        'Company vat',
        'First name',
        'Family name prefix',
        'Family name',
        'Email',
        'Phone',
        'Fax',
        'Website',
        'Communication: general',
        'Communication: sales',
        'Communication: special',
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
                null, // 'path://id' - 'Relation number' - probably SK customer id or what?
                null, // 'path://shortname' - 'GUID'
                null, // 'path://language_iso2' - 'Language (iso2)'
                $billingAddress->getCompany(), // 'path://business_data.name'
                null, // 'path://business_data.coc_number',
                $billingAddress->getCountryId(), // 'path://business_data.country_iso2'
                $billingAddress->getVatId(), // 'path://business_data.vat_number'
                $billingAddress->getFirstname(), // 'path://contact_person.firstname'
                null, // 'path://contact_person.familyname_prefix'
                $billingAddress->getLastname(), // 'path://contact_person.familyname'
                $customer->getEmail(), // 'path://contact_set.email'
                $billingAddress->getTelephone(), // 'path://contact_set.phone'
                null, // 'path://contact_set.fax'
                null, // 'path://contact_set.www'
                null, // 'path://contact_set.allow_general_communication'
                null, // 'path://contact_set.allow_offer_communication'
                null, // 'path://contact_set.allow_special_communication'
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
}
