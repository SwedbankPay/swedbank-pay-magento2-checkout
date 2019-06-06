# PayEx Checkout for Magento 2

[![Build Status][build-badge]][build]

The Official PayEx Checkout Extension for Magento 2 provides seamless
integration with PayEx Checkout, allowing your customers to pay swiftly
and securely with credit card, invoice (Norway and Sweden), Vipps (Norway)
and Swish (Sweden). Credit card payments are available world-wide.

## Requirements

* Magento Open Source/Commerce version 2.2 or newer
* A PayEx merchant account, read more and [contact us][contact] to get one.

**Please Note:** When your PayEx Merchant Account is created, there are a
[few things you need to attend to][admin] before you can start using it.

## Installation

PayEx Checkout for Magento 2 may be installed via Magento Marketplace or
Composer.

### Magento Marketplace

If you have linked your Marketplace account to your Magento 2 store, you may
install the PayEx Checkout for Magento 2 with the Magento Component Manager.

For installation using the Component Manager, please see the
[official guide][cmpmgr].

### Composer

PayEx Checkout for Magento 2 can alternatively be installed via composer with
the following instructions:

1. In the Magento root directory enter command:

    ```sh
    composer require payex/magento2-checkout
    ```

2. Make sure everything is up to date:

    ```sh
    composer update
    ```

3. Enable the modules:

    ```sh
    bin/magento module:enable --clear-static-content PayEx_Core PayEx_Client PayEx_Checkin PayEx_PaymentMenu PayEx_Checkout
    ```

4. Upgrade setup:

    ```sh
    bin/magento setup:upgrade
    ```

5. Compile:

    ```sh
    bin/magento setup:di:compile
    ```

6. Clear the cache:

    ```sh
    bin/magento cache:clean
    ```

## Configuration

PayEx Checkout configuration can be found under **Stores** >
**Configuration** > **Sales** > **Payment Methods** > **PayEx** >
**Configure**.

As parts of the PayEx Checkout installation we have **Client**, **Checkout**, **Checkin** and **Payment Menu**
with configurable options as follows:

### Client

* **Enabled**: Status of the module.
* **Merchant Account**: Your PayEx Merchant Account ID.
* **Payee ID**: Your PayEx Payee ID.
* **Payee Name**: Your PayEx Payee Name.
* **Test Mode**: Only disable in live production site.
* **Debug Mode**: Enable this for more in-depth logging, should be off by default.

### Checkout

* **Enabled**: Status of the module.

### Checkin

* **Enabled**: Status of the module.
* **Required**: Enable to require checkin in checkout.

### Payment Menu

* **Enabled**: Status of the module.
* **Terms of Service Page**: Set page to link as terms of service page in checkout.

## Support

To find the customer service available in your country, please visit
[the PayEx website][support].

## Release Notes

* **1.0.0**: May 2019 - First official release.

## License

PayEx Checkout for Magento 2 is released under [Apache V2.0 licence][license].

  [contact]:            https://payex.se/tjanster/payex-checkout/
  [admin]:              https://developer.payex.com/xwiki/wiki/developer/view/Main/ecommerce/resources/admin/
  [cmpmgr]:             http://docs.magento.com/marketplace/user_guide/quick-tour/install-extension.html
  [support]:            https://payex.com/customer-service/
  [license]:            LICENSE
  [build-badge]:        https://travis-ci.org/PayEx/payex-magento2-checkout.svg?branch=master
  [build]:              https://travis-ci.org/PayEx/payex-magento2-checkout