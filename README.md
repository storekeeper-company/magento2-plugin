# Description

Connect your Magento 2 stores to StoreKeeper.

# Important Notice

Before using module make sure that your shop have all tax rules, classes and rates configured accordingly to your shop's region. Please follow this [official Magento guide](https://experienceleague.adobe.com/docs/commerce-admin/stores-sales/site-store/taxes/international-tax-guidelines.html#eu-tax-configuration).

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

1. Log into your Magento 2 backend and go to `Stores` > `Configuration` > `StoreKeeper` > `General`

2. Select your store by navigating to the deepest level in the top left store navigation

3. Enable the plugin by setting the field `Enabled` to `Yes`

6. Copy your `Auth Key`

7. Press the `Save` button

8. Log into your StoreKeeper account

9. Select the StoreKeeper Sales Channel you want to connect with

10. Go to `Settings`

11. Scroll down to the `Synchronisation` button and click it

12. Paste the `Auth Key` you previously copied from Magento 2 into the `Api Key` field and click `Connect`

13. Once succesfully connected, the fields in your Magento 2 backend should be filled with data

## Cron commands

To synchronise data to StoreKeeper, the following commands have been made available

```
bin/magento storekeeper:sync:orders --stores={storeIds}
```

It is recommended to add these commands to a `crontab` for them to be automatically executed. The preferred `cron` schedule would be

```
* * * * * bin/magento storekeeper:sync:orders --stores=1 >> /magento2/var/log/storekeeper.log 2>&1
```

## Queue

This plugin uses the Magento 2 queue consumer functionality. If you want to run the queue manually you can use the following command:

```
bin/magento queue:consumer:start storekeeper.queue.events
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

2. Log into your Magento 2 backend and go to `Stores` > `Configuration` > `StoreKeeper` > `General`

3. Select your store by navigating to the deepest level in the top left store navigation

4. Empty the value in the `Token` field

5. Press the `Save` button

# Debugging

If you're having any issues using the plugin, the first thing to do would be checking the `magento2/var/log/storekeeper.log` for any errors.
