# Guide to installing Magento 2 for developers on Ubuntu localhost

### Step 1: Check the requirements for Magento 2.4.6
See if you have all the nessecary versions of the required software, and their required extensions installed on your system.
You can find them here: https://experienceleague.adobe.com/docs/commerce-operations/installation-guide/system-requirements.html

But the most important ones are:
```
-PHP 8.2

-Apache 2.4

-MySQL 8.0 

-Composer 2.5.5

-ElastiSearch 7 <- Requires Java
```

### Step 2: Create an Adobe account and generate keys
To install Magento, you will some authentication.
Create an account here: https://account.magento.com/customer/account/login/
Then once you have your account, go to: https://marketplace.magento.com/customer/accessKeys/
Press "Generate a new access key"
Now you should get a public and a private key.

### Step 3: Install Magento 2 via Composer
Open a terminal inside your localhost directory, and run this command:

```
composer create-project --repository-url=https://repo.magento.com/ magento/project-community-edition <install-directory-name>
```

You will be asked to give authentication credentials, these are the keys we made in step 2.
Your username is your public key,
your password is your private key.

When it asks you to save these credentials, type 'y' for yes.
        
### Step 4: Disable OpenSearch and ElastiSearch modules
In the current version of Magento 2.4, these 2 modules have certain issues setting up. Don't worry, we'll enable them later.
In your project folder, run this command:

```
sudo php bin/magento module:disable {Magento_OpenSearch,Magento_Elasticsearch,Magento_InventoryElasticsearch,Magento_Elasticsearch7}
```   
   
This will disable these modules, and allows to set up our configuration without worry.

### Step 5: Create a database
Either using MYSQL CLI or other supported methods for Magento 2, Create a database with the name 'magento'
Also make a new user for MySQL that you'll be using for this project.
Afterwards grant all permission on our new database.

### Step 6: Run your setup command
In your project root folder (for example /var/www/html/magento/) run the following command:
Fill out all the < ... > parts with your own information.

```
sudo php bin/magento setup:install --base-url="http://localhost/<project_folder>/pub/" --db-host=localhost --db-name=magento --db-user=<db_username> --db-password=<db_password> --admin-firstname=<your_firstname> --admin-lastname=<your_lastname> --admin-email=<your_email> --admin-user=<your_username> --admin-password=<your_password> --language=en_US --currency=<your_currency> --timezone=<your_timezone> --use-rewrites=1
```

### Step 7: Deploy Static files
In your project folder, run the following command:

```
sudo bin/magento setup:static-content:deploy -f
```

This will generate our Static files so we actually get some pages to look at.

### Step 8: Give your localhost directory the correct permissions
Magento 2 will not work properly without read/write permission. Make sure to give these permissions to your localhost directory, eg; /var/www/html/
This command will ensure you have the right permissions:

```
sudo chown -R <linux_user>:<linux_user> <localhost_directory>
```
You can find who is the current linux user by typing "woami" in any terminal.

### Step 9: Set Magento to Developer mode
In your project folder, run the following command:

```
sudo bin/magento deploy:mode:set developer
```

Now check your localhost in your browser at your base url (for example: localhost/magento/pub/) to see if your setup was succesfull!


### Step 10: Enable ElastiSearch
In your project folder, run the following command:

```
sudo php bin/magento module:enable {Magento_Elasticsearch,Magento_InventoryElasticsearch,Magento_Elasticsearch7}
```

Since OpenSearch is supposed to run on Docker, and has very little to none supporting documentation for running it locally on Ubuntu, we will be using ElastiSearch 7.






