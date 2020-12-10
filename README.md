# Swedbank Pay Checkout for Magento 2

[![Build Status][build-badge]][build]
[![Latest Stable Version][version-badge]][packagist]
[![Total Downloads][downloads-badge]][packagist]
[![License][license-badge]][packagist]

![Swedbank Pay Magento 2 Checkout][og-image]

## About

**UNSUPPORTED**: This extensions is at an early stage of development and is not
supported as of yet by Swedbank Pay. It is provided as a convenience to speed
up your development, so please feel free to play around. However, if you need
support, please wait for a future, stable release.

The Official Swedbank Pay Checkout Extension for Magento 2 provides seamless
integration with Swedbank Pay Checkout, allowing your customers to pay swiftly
and securely with credit card, invoice (Norway and Sweden), Vipps (Norway)
and Swish (Sweden). Credit card payments are available world-wide.

## Requirements

* Magento Open Source/Commerce version 2.2 or newer
* A Swedbank Pay merchant account, read more and [contact us][contact] to get one.

**Please Note:** When your Swedbank Pay Merchant Account is created, there are a
[few things you need to attend to][admin] before you can start using it.

## Installation

Swedbank Pay Checkout for Magento 2 may be installed via Magento Marketplace or
Composer.

### Magento Marketplace

If you have linked your Marketplace account to your Magento 2 store, you may
install the Swedbank Pay Checkout for Magento 2 with the Magento Component Manager.

For installation using the Component Manager, please see the
[official guide][cmpmgr].

### Composer

Swedbank Pay Checkout for Magento 2 can alternatively be installed via composer with
the following instructions:

1. In the Magento root directory enter command:

    ```sh
    composer require swedbank-pay/magento2-checkout --no-update
    ```

2. Install module and required packages:

    ```sh
    composer update swedbank-pay/magento2-checkout --with-dependencies
    ```

3. Enable the modules:

    ```sh
    bin/magento module:enable --clear-static-content SwedbankPay_Core SwedbankPay_Checkout
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

Swedbank Pay Checkout configuration can be found under **Stores** >
**Configuration** > **Sales** > **Payment Methods** > **Swedbank Pay** >
**Configure**.

As parts of the Swedbank Pay Checkout installation we have **Core**, **Checkout** and **Payment Menu**
with configurable options as follows:

### Core

* **Enabled**: Status of the module.
* **Merchant Account**: Your Swedbank Pay Merchant Account ID.
* **Payee ID**: Your Swedbank Pay Payee ID.
* **Payee Name**: Your Swedbank Pay Payee Name.
* **Test Mode**: Only disable in live production site.
* **Debug Mode**: Enable this for more in-depth logging, should be off by default.

### Checkout

* **Enabled**: Status of the module.
* **Required**: Enable to require checkin in checkout.

### Payment Menu

* **Enabled**: Enable to active Swedbank Pay payment menu.
* **Terms of Service Page**: Set page to link as terms of service page in checkout.

## FAQ
**Q:** Is it possible to disable customer's checkin?  
**A:** The checkin part of the module can be made optional by allowing guest login. This can be done by setting **Checkin Required** to **No** inside **Checkout Configuration**

**Q:** How can we test the module using test credentials?  
**A:** To be able to use test credentials, you can enable test mode by setting **Test Mode** to **Yes** inside **Core Configuration**. For in-depth logging, you can also enable debug mode by setting **Debug Mode** to **Yes**  
  
Please note: Remember to disable **Test Mode** in production site as test credentials should only be used for testing purposes.  

## Support

To find the customer service available in your country, please visit
[the Swedbank Pay website][support].

## Release Notes

* **1.1.0**: October 2019 - Now known as Swedbank Pay Checkout and with improved 1-phase payments support.

* **1.0.0**: May 2019 - First official release.

## License

Swedbank Pay Checkout for Magento 2 is released under [Apache V2.0 licence][license].

  [contact]:            https://www.swedbankpay.no/vare-losninger/ta-betalt-pa-nettet/checkout
  [admin]:              https://developer.payex.com/xwiki/wiki/developer/view/Main/ecommerce/resources/admin/
  [cmpmgr]:             https://docs.magento.com/marketplace/user_guide/buyers/install-extension.html
  [support]:            https://www.swedbankpay.com/
  [license]:            LICENSE
  [build-badge]:        https://travis-ci.org/SwedbankPay/swedbank-pay-magento2-checkout.svg?branch=master
  [build]:              https://travis-ci.org/SwedbankPay/swedbank-pay-magento2-checkout
  [version-badge]:      https://poser.pugx.org/swedbank-pay/magento2-checkout/version
  [downloads-badge]:    https://poser.pugx.org/swedbank-pay/magento2-checkout/downloads
  [license-badge]:      https://poser.pugx.org/swedbank-pay/magento2-checkout/license
  [packagist]:          https://packagist.org/packages/swedbank-pay/magento2-checkout
  [og-image]:           https://repository-images.githubusercontent.com/211832269/616bd480-53ee-11ea-96f1-83ad4b3bf643
