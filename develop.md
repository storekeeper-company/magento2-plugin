# Guide to installing Magento 2 for developers on Ubuntu localhost

### Step 1: Check the requirements for Magento 2
        See if you have all the nessecary version of the needed software, and their required extensions installed on your system.
        You can find them here: https://experienceleague.adobe.com/docs/commerce-operations/installation-guide/system-requirements.html

### Step 2: Give your localhost directory the correct permissions
        Magento 2 will not work properly without read/write permission. Make sure to give these permissions to your localhost directory, eg; /var/www/html/
        This command will ensure you have the right permissions:
        ```
        sudo chown -R <your_root_name>:<your_root_name> <localhost_directory>
        ```
