# mod-marketpress_3

PayFast Marketpress Module v3.0
-------------------------------

INTEGRATION:
1. Unzip the module to a temporary location on your computer
2. Copy the “wp-content” folder in the archive to your base “wordpress” folder
- This should NOT overwrite any existing files or folders and merely supplement them with the PayFast files
- This is however, dependent on the FTP program you use
3. Login to the WordPress Administrator console
4. Using the main menu, navigate to Store Settings
5. Using the MarketPress menu, navigate to “Payments”
6. Enable PayFast by checking the PayFast box
7. The PayFast options will then be shown below.
8. Leave everything else as per default and click “Save Changes”
9. The module is now and ready to be tested with the Sandbox
10. When you are ready to go live input your PayFast merchant ID and Key, as well as passphrase if this is set on your PayFast account. Select ‘Live’ mode and save

How can I test that it is working correctly?
If you followed the installation instructions above, the module is in “test” mode and you can test it by purchasing from your site as a buyer normally would. You will be redirected to PayFast for payment and can login with the user account detailed above and make payment using the balance in their wallet.

You will not be able to directly “test” a credit card, Instant EFT or Ukash payment in the sandbox, but you don’t really need to. The inputs to and outputs from PayFast are exactly the same, no matter which payment method is used, so using the wallet of the test user will give you exactly the same results as if you had used another payment method.

I’m ready to go live! What do I do?
In order to make the module “LIVE”, follow the instructions below:

1. Login to the WordPress Administrator console
2. Using the main menu, navigate to Products > Store Settings
3. Using the MarketPress menu, navigate to “Payments”
4. Under “PayFast Checkout Settings”, change the configuration values as below:n
5. PayFast Mode = “LIVE”
6. Merchant ID = Integration Page>
7. Merchant Key = Integration Page>
8. Log Debugging Info = No
9. Change the other fields as per your preferences
10. Click Save


******************************************************************************

    Please see the URL below for all information concerning this module:

     https://www.payfast.co.za/shopping-carts/market-press-ecommerce/

******************************************************************************
