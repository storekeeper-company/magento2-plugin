<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Customer\Model\CustomerRegistry;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Address;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\AddressRegistry;

$objectManager = Bootstrap::getObjectManager();
//Creating customer
/** @var $repository CustomerRepositoryInterface */
$repository = $objectManager->create(CustomerRepositoryInterface::class);
/** @var Customer $customer */
$customer = $objectManager->create(Customer::class);
/** @var CustomerRegistry $customerRegistry */
$customerRegistry = $objectManager->get(CustomerRegistry::class);

//Customer #1
$customer->setWebsiteId(1)
    ->setEmail('customer_one_address@test.com')
    ->setPassword('password')
    ->setGroupId(1)
    ->setStoreId(1)
    ->setIsActive(1)
    ->setPrefix('Mr.')
    ->setFirstname('John')
    ->setMiddlename('A')
    ->setLastname('Smith')
    ->setSuffix('Esq.')
    ->setTaxvat('12')
    ->setGender(0)
    ->setId(1);

$customer->isObjectNew(true);
$customer->save();
$customerRegistry->remove($customer->getId());

//Creating billing address
/** @var Address $customerBillingAddress */
$customerBillingAddress = $objectManager->create(Address::class);
$customerBillingAddress->isObjectNew(true);
$customerBillingAddress->setData(
    [
        'attribute_set_id' => 2,
        'telephone' => 3468676,
        'postcode' => 75477,
        'country_id' => 'US',
        'city' => 'Business City',
        'company' => 'CompanyName',
        'vat_id' => 9021090210,
        'street' => 'Customer 1 Billing Address street',
        'lastname' => 'Smith',
        'firstname' => 'John',
        'parent_id' => $customer->getId(),
        'region_id' => 1,
    ]
);
$customerBillingAddress->save();
/** @var AddressRepositoryInterface $addressRepository */
$addressRepository = $objectManager->get(AddressRepositoryInterface::class);
$customerBillingAddress = $addressRepository->getById($customerBillingAddress->getId());
$customerBillingAddress->setCustomerId($customer->getId());
$customerBillingAddress->isDefaultBilling(true);
$customerBillingAddress->setIsDefaultShipping(false);
$customerBillingAddress = $addressRepository->save($customerBillingAddress);

$customer->setDefaultBilling($customerBillingAddress->getId());
$customer->save();

$customerRegistry->remove($customerBillingAddress->getCustomerId());
/** @var AddressRegistry $addressRegistry */
$addressRegistry = $objectManager->get(AddressRegistry::class);
$addressRegistry->remove($customerBillingAddress->getId());

//Creating shipping address
/** @var Address $customerShippingAddress */
$customerShippingAddress = $objectManager->create(Address::class);
$customerShippingAddress->isObjectNew(true);
$customerShippingAddress->setData(
    [
        'attribute_set_id' => 2,
        'telephone' => 3468677,
        'postcode' => 75478,
        'country_id' => 'US',
        'city' => 'Home City',
        'company' => 'CompanyName',
        'vat_id' => 9021090210,
        'street' => 'Customer 1 Shipping Address street',
        'lastname' => 'Smith',
        'firstname' => 'John',
        'parent_id' => $customer->getId(),
        'region_id' => 1,
    ]
);
$customerShippingAddress->save();
/** @var AddressRepositoryInterface $addressRepository */
$addressRepository = $objectManager->get(AddressRepositoryInterface::class);
$customerShippingAddress = $addressRepository->getById($customerShippingAddress->getId());
$customerShippingAddress->setCustomerId($customer->getId());
$customerShippingAddress->isDefaultShipping(true);
$customerShippingAddress->setIsDefaultShipping(true);
$customerShippingAddress = $addressRepository->save($customerShippingAddress);

$customer->setDefaultShipping($customerShippingAddress->getId());
$customer->save();

$customerRegistry->remove($customerShippingAddress->getCustomerId());
/** @var AddressRegistry $addressRegistry */
$addressRegistry = $objectManager->get(AddressRegistry::class);
$addressRegistry->remove($customerShippingAddress->getId());

/** @var \Magento\JwtUserToken\Api\RevokedRepositoryInterface $revokedRepo */
$revokedRepo = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->get(\Magento\JwtUserToken\Api\RevokedRepositoryInterface::class);
$revokedRepo->saveRevoked(
    new \Magento\JwtUserToken\Api\Data\Revoked(
        \Magento\Authorization\Model\UserContextInterface::USER_TYPE_CUSTOMER,
        (int) $customer->getId(),
        time() - 3600 * 24
    )
);

//Customer #2
$customer->setWebsiteId(1)
    ->setEmail('customer_two_address@test.com')
    ->setPassword('password')
    ->setGroupId(1)
    ->setStoreId(1)
    ->setIsActive(1)
    ->setPrefix('Mr.')
    ->setFirstname('Robert')
    ->setMiddlename('B')
    ->setLastname('Johnson')
    ->setSuffix('Esq.')
    ->setTaxvat('34')
    ->setGender(0)
    ->setId(2);

$customer->isObjectNew(true);
$customer->save();
$customerRegistry->remove($customer->getId());

//Creating billing address
/** @var Address $customerBillingAddress */
$customerBillingAddress = $objectManager->create(Address::class);
$customerBillingAddress->isObjectNew(true);
$customerBillingAddress->setData(
    [
        'attribute_set_id' => 2,
        'telephone' => 1234567,
        'postcode' => 12345,
        'country_id' => 'US',
        'city' => 'Business City',
        'company' => 'CompanyName',
        'vat_id' => 9876543210,
        'street' => 'Customer 2 Billing Address street',
        'lastname' => 'Johnson',
        'firstname' => 'Robert',
        'parent_id' => $customer->getId(),
        'region_id' => 1,
    ]
);
$customerBillingAddress->save();
/** @var AddressRepositoryInterface $addressRepository */
$addressRepository = $objectManager->get(AddressRepositoryInterface::class);
$customerBillingAddress = $addressRepository->getById($customerBillingAddress->getId());
$customerBillingAddress->setCustomerId($customer->getId());
$customerBillingAddress->isDefaultBilling(true);
$customerBillingAddress->setIsDefaultShipping(false);
$customerBillingAddress = $addressRepository->save($customerBillingAddress);

$customer->setDefaultBilling($customerBillingAddress->getId());
$customer->save();

$customerRegistry->remove($customerBillingAddress->getCustomerId());
/** @var AddressRegistry $addressRegistry */
$addressRegistry = $objectManager->get(AddressRegistry::class);
$addressRegistry->remove($customerBillingAddress->getId());

//Creating shipping address
/** @var Address $customerShippingAddress */
$customerShippingAddress = $objectManager->create(Address::class);
$customerShippingAddress->isObjectNew(true);
$customerShippingAddress->setData(
    [
        'attribute_set_id' => 2,
        'telephone' => 1234568,
        'postcode' => 12346,
        'country_id' => 'US',
        'city' => 'Home City',
        'company' => 'CompanyName',
        'vat_id' => 9876543210,
        'street' => 'Customer 2 Shipping Address street',
        'lastname' => 'Johnson',
        'firstname' => 'Robert',
        'parent_id' => $customer->getId(),
        'region_id' => 1,
    ]
);
$customerShippingAddress->save();
/** @var AddressRepositoryInterface $addressRepository */
$addressRepository = $objectManager->get(AddressRepositoryInterface::class);
$customerShippingAddress = $addressRepository->getById($customerShippingAddress->getId());
$customerShippingAddress->setCustomerId($customer->getId());
$customerShippingAddress->isDefaultShipping(true);
$customerShippingAddress->setIsDefaultShipping(true);
$customerShippingAddress = $addressRepository->save($customerShippingAddress);

$customer->setDefaultShipping($customerShippingAddress->getId());
$customer->save();

$customerRegistry->remove($customerShippingAddress->getCustomerId());
/** @var AddressRegistry $addressRegistry */
$addressRegistry = $objectManager->get(AddressRegistry::class);
$addressRegistry->remove($customerShippingAddress->getId());

$revokedRepo->saveRevoked(
    new \Magento\JwtUserToken\Api\Data\Revoked(
        \Magento\Authorization\Model\UserContextInterface::USER_TYPE_CUSTOMER,
        (int) $customer->getId(),
        time() - 3600 * 24
    )
);
