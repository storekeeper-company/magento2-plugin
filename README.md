
# Config
The plugin should be configurable

## Products
Add allow product sync config + implementation

## Categories
Add allow category sync config + implementation

## Orders
Add allow order sync config + implementation

# Shipping
Retrieve shipping methods from API 
Register them via a custom carrier (see dev/etc/config.xml + dev/Model/Carrier/MyCarrier.php

# Payments
Retrieve payment methods from API
Register them via a payment method model (see https://ccbill.com/kb/magento-add-payment-method)
Upon submit, retrieve payment link + redirect
Create custom controller (i.e. /checkout/payment/storekeeper)
Receive failed or accepted payments and set order status accordingly
