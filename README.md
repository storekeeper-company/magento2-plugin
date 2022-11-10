# Description

Connect your Magento 2 stores to StoreKeeper.

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
bin/magento cache:clear;
```

# Configuration

1. Log into your Magento 2 backend and go to `Stores` > `Configuration` > `StoreKeeper` > `General`

2. Select your store by navigating to the deepest level in the top left store navigation

3. Enable the plugin by setting the field `Enabled` to `Yes`

4. Fill in the `API URL` for your StoreKeeper environment and set the `Sync Mode` to `Order`

5. Copy your `Auth Key`

6. Press the `Save` button

7. Log into your StoreKeeper account

8. Select the StoreKeeper Sales Channel you want to connect with

9. Go to `Settings`

10. Scroll down to the `Synchronisation` button and click it

11. Paste the `Auth Key` you previously copied from Magento 2 into the `Api Key` field and click `Connect`

12. Once succesfully connected, the fields in your Magento 2 backend should be filled with data

## Cron commands

To synchronise data to StoreKeeper, the following commands have been made available

```
bin/magento storekeeper:sync:categories --stores={storeIds}
bin/magento storekeeper:sync:customers --stores={storeIds}
bin/magento storekeeper:sync:orders --stores={storeIds}
bin/magento storekeeper:sync:products --stores={storeIds}
```

It is recommended to add these commands to a `crontab` for them to be automatically executed. The preferred `cron` schedule would be

```
0 * * * * bin/magento storekeeper:sync:categories --stores=1 >> /magento2/var/log/storekeeper.log 2>&1
15 * * * * bin/magento storekeeper:sync:customers --stores=1 >> /magento2/var/log/storekeeper.log 2>&1
30 * * * * bin/magento storekeeper:sync:orders --stores=1 >> /magento2/var/log/storekeeper.log 2>&1
45 * * * * bin/magento storekeeper:sync:products --stores=1 >> /magento2/var/log/storekeeper.log 2>&1
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
