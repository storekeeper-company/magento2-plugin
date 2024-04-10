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

//Creating address
/** @var Address $customerAddress */
$customerAddress = $objectManager->create(Address::class);
$customerAddress->isObjectNew(true);
$customerAddress->setData(
    [
        'attribute_set_id' => 2,
        'telephone' => 3468676,
        'postcode' => 75477,
        'country_id' => 'US',
        'city' => 'CityM',
        'company' => 'CompanyName',
        'vat_id' => 9021090210,
        'street' => 'CustomerAddress1',
        'lastname' => 'Smith',
        'firstname' => 'John',
        'parent_id' => $customer->getId(),
        'region_id' => 1,
    ]
);
$customerAddress->save();
/** @var AddressRepositoryInterface $addressRepository */
$addressRepository = $objectManager->get(AddressRepositoryInterface::class);
$customerAddress = $addressRepository->getById($customerAddress->getId());
$customerAddress->setCustomerId($customer->getId());
$customerAddress->isDefaultBilling(true);
$customerAddress->setIsDefaultShipping(true);
$customerAddress = $addressRepository->save($customerAddress);

$customer->setDefaultBilling($customerAddress->getId());
$customer->setDefaultShipping($customerAddress->getId());
$customer->save();

$customerRegistry->remove($customerAddress->getCustomerId());
/** @var AddressRegistry $addressRegistry */
$addressRegistry = $objectManager->get(AddressRegistry::class);
$addressRegistry->remove($customerAddress->getId());
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

//Creating address
/** @var Address $customerAddress */
$customerAddress = $objectManager->create(Address::class);
$customerAddress->isObjectNew(true);
$customerAddress->setData(
    [
        'attribute_set_id' => 2,
        'telephone' => 1234567,
        'postcode' => 12345,
        'country_id' => 'US',
        'city' => 'CityC',
        'company' => 'CompanyName',
        'vat_id' => 9876543210,
        'street' => 'CustomerAddress2',
        'lastname' => 'Johnson',
        'firstname' => 'Robert',
        'parent_id' => $customer->getId(),
        'region_id' => 1,
    ]
);
$customerAddress->save();
/** @var AddressRepositoryInterface $addressRepository */
$addressRepository = $objectManager->get(AddressRepositoryInterface::class);
$customerAddress = $addressRepository->getById($customerAddress->getId());
$customerAddress->setCustomerId($customer->getId());
$customerAddress->isDefaultBilling(true);
$customerAddress->setIsDefaultShipping(true);
$customerAddress = $addressRepository->save($customerAddress);

$customer->setDefaultBilling($customerAddress->getId());
$customer->setDefaultShipping($customerAddress->getId());
$customer->save();

$customerRegistry->remove($customerAddress->getCustomerId());
$addressRegistry->remove($customerAddress->getId());
$revokedRepo->saveRevoked(
    new \Magento\JwtUserToken\Api\Data\Revoked(
        \Magento\Authorization\Model\UserContextInterface::USER_TYPE_CUSTOMER,
        (int) $customer->getId(),
        time() - 3600 * 24
    )
);
