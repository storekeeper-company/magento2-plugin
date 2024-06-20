# Description

Connect your Magento 2 stores to StoreKeeper.

# Important Notices

Before using module make sure that your shop have all tax rules, classes and rates configured accordingly to your shop's region. Please follow this [official Magento guide](https://experienceleague.adobe.com/en/docs/commerce-admin/stores-sales/site-store/taxes/international-tax-guidelines#eu-tax-configuration).

If your project is using Multi-Source Inventory (MSI) functionality and/or MSI-related modules are enabled, please install
this [addon module](https://github.com/storekeeper-company/magento2-plugin-msi-addon).
# Installation

1. Go to your Magento 2 directory and install the plugin via `composer`:
```
composer require storekeeper/magento2-plugin
```

2. Recompile your Magento 2 installation by running:
```
bin/magento setup:upgrade;
bin/magento setup:di:compile;
bin/magento setup:static-content:deploy;
bin/magento cache:clean;
```

# Configuration

1. Log into your Magento 2 backend and go to `Stores` -> `Configuration` -> `StoreKeeper` -> `General`

2. Select your store by navigating to the deepest level in the top left store navigation

3. Enable the plugin by setting the field `Enabled` to `Yes`

4. Click button `StoreKeeper Connect` next to `Enabled`, and you will be redirected to StoreKeeper Connection page

5. Follow the onscreen instructions and enter your StoreKeeper account name

6. If it is valid - click `Connect` and you will be redirected to your StoreKeeper account Dashboard

7. Get back to Magento admin `Stores` -> `Configuration` -> `StoreKeeper` -> `General` and click `Refresh store information` in order to receive Store ID and Store Name reserved by StoreKeeper for current integration

## Sync Modes clarification
Current extension operates in one of 4 modes: 
 - **None** - No data exchange between M2 and Storekeeper systems 
 - **Products** - Only product related data will be synced (products creation, activation, deactivation and stock updates)
 - **Orders** - Only orders related data will be synced (order body, statuses, items, payments, shipments) **and** products stock
 - **All** - Combination of two methods described above

Sync mode can be adjusted under `Stores` -> `Configuration` -> `StoreKeeper` -> `General` -> `Sync Mode` section

## Delivery Methods Configuration
In order to use **StoreKeeper Multi-Carrier Shipping** delivery option activate it under:<br/>
_Stores -> Configuration -> Sales -> Delivery Methods -> **StoreKeeper Multi-Carrier Shipping**_<br/>

After that all Shipping offers configured on your StoreKeeper backoffice will be available on Magento checout

## Payment Methods Configuration
Payment methods avaliable via Storekeeper Payment Gateway can be activated in two places:
1. As an option of **StoreKeeper Payments** payment method, available under:<br/>
   _Stores -> Configuration -> Sales -> Payment Methods -> Other Payment Methods -> **StoreKeeper Payments** (Yes/No)_

In this case customer will see all Payment options activated on their storekeeper account.
![Storekeeper Payments available in Magento as single Payment option](docs/storekeeper_payments.png)
2. As separate Payment option:<br/>
   In order to display in Magento Checkout any of Payment Methods available on storekeeper account as an individual payment option, admin user needs to activate  method under:<br />
   _Stores->Configuration->Storekeeper->**StoreKeeper Payments**_
   ![Payment method iDEAL available as separate Payment option](docs/sk_payment_individually_adminarea.png)

In this case activated Payment Method(s) will appear as individual Payment Method option, and dissapear as sub-option on **StoreKeeper Payments**
![Payment method iDEAL available as separate Payment option](docs/sk_payment_individually.png)

_Payment methods that does not have own logo will receive current store logo set in Content->Design->Configuration area of Magento admin panel_

## Queues

This plugin uses the Magento 2 queue consumer functionality. If you want to run queues manually you can use following commands:

Run consumer that handles StoreKeeper webhook events
```
bin/magento queue:consumer:start storekeeper.queue.events
```

Run consumer that handles Magento entities export for StoreKeeper
```
bin/magento queue:consumer:start storekeeper.data.export
```

Run consumer that orders sync process with StoreKeeper
```
bin/magento queue:consumer:start storekeeper.queue.sync.orders
```

# Disconnecting

Disconnecting your Magento 2 store can be done in two ways

## Disconnect from StoreKeeper

1. Log into your StoreKeeper environment

2. Select your StoreKeeper Sales Channel

3. Go to `Settings`

4. Scroll down to the `Disconnect` button and click it

## Disconnect from Magento 2

1. Log into your Magento 2 backend

2. Log into your Magento 2 backend and go to `Stores` -> `Configuration` -> `StoreKeeper` -> `General`

3. Select your store by navigating to the deepest level in the top left store navigation

4. Click on `Disconnect from StoreKeeper` button at the bottom of current config section

# Troubleshooting
## Debugging

If you're having any issues using the plugin, the first thing to do would be checking the `magento2/var/log/storekeeper.log` for any errors.

## Tasks and Events Logging
Every incoming webhook event data are logged and can be reviewed under:<br/>
**_System -> Action Logs -> StoreKeeper Event Log_** tab.<br/>
This grid collects info about request route, body, method, action, response code and timesteamp of every incoming webhook.

Every StoreKeeper related task from mesage queue are logged under:<br/>
**_System -> Action Logs -> StoreKeeper Task Log_** tab.<br/>
This grid collects info about topic name, json-formatted request body, update time, status and number of trials made by Magento core queue managed in order to complete task.<br/>
Task log registers every addition of order to sync queue, its processing, as well as every operation with product sync (import, export, updates)

# Running integration tests

1. Prepare your enviroment according to Magento 2 integration testing documentation https://developer.adobe.com/commerce/testing/guide/integration/

2. To run integration tests from a specific directory tree in Magento 2, use the following command:
```
cd dev/tests/integration

../../../vendor/bin/phpunit ../../../vendor/storekeeper/magento2-plugin/Test/Integration
```

2. To run a single test class in Magento 2, use the following command:
```
cd dev/tests/integration

../../../vendor/bin/phpunit ../../../vendor/storekeeper/magento2-plugin/Test/Integration/OrderCreationTest.php
```
