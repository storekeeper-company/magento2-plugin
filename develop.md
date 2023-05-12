# Guide to installing Magento 2 for developers on Ubuntu localhost

### Step 1: Check the requirements for Magento 2
See if you have all the nessecary version of the needed software, and their required extensions installed on your system.
You can find them here: https://experienceleague.adobe.com/docs/commerce-operations/installation-guide/system-requirements.html

### Step 2: Install Magento 2 via Composer
Open your terminal inside your localhost directory, and run this command:

        composer create-project --repository-url=https://repo.magento.com/ magento/project-community-edition <install-directory-name>
        
### Step 3: Disable OpenSearch and ElastiSearch modules
In the current version of Magento 2.4, these 2 modules have certain issues seting up. Don't worry, we'll enable them later.
In your project folder, run this command:
        
        sudo php bin/magento module:disable {Magento_OpenSearch,Magento_Elasticsearch,Magento_InventoryElasticsearch,Magento_Elasticsearch7}
        
This will disable these modules, and allows to set up our configuration without worry.

### Step 4: Create a MYSQL database
Either using MYSQL CLI or other supported methods for Magento 2, Create a database with the name 'magento'
Make sure that your database user which you will be using has the correct permissions as well.

### Step 5: Run your setup command
In your project root folder (for example /var/www/html/magento/) run the following command:

        sudo php bin/magento setup:install --base-url="http://localhost/<project_folder>/pub/" --db-host=localhost --db-name=magento --db-user=<db_username> --db-password=<db_password> --admin-firstname=<your_firstname> --admin-lastname=<your_lastname> --admin-email=<your_email> --admin-user=<your_username> --admin-password=<your_password> --language=en_US --currency=<your_currency> --timezone=<your_timezone> --use-rewrites=0
        
Fill out all the < ... > parts with your own information.

### Step 6: Deploy Static files
In your project folder, run the following command:

        sudo bin/magento setup:static-content:deploy -f

This will generate our Static files so we actually get some pages to look at.

### Step 7: Give your localhost directory the correct permissions
Magento 2 will not work properly without read/write permission. Make sure to give these permissions to your localhost directory, eg; /var/www/html/
This command will ensure you have the right permissions:

        sudo chown -R <your_root_name>:<your_root_name> <localhost_directory>
        
### Step 8: Set Magento to Developer mode
In your project folder, run the following command:

        sudo bin/magento deploy:mode:set developer

Now check your lcoalhost in your browser at your base url (for example: localhost/magento/pub/) to see if your setup was succesfull!




