# Swedbank Pay Checkout for Magento 2

[![Build Status][build-badge]][build]
[![Latest Stable Version][version-badge]][packagist]
[![Total Downloads][downloads-badge]][packagist]
[![License][license-badge]][packagist]

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
    composer require swedbank-pay/magento2-checkout
    ```

2. Make sure everything is up to date:

    ```sh
    composer update
    ```

3. Enable the modules:

    ```sh
    bin/magento module:enable --clear-static-content SwedbankPay_Core SwedbankPay_Client SwedbankPay_Checkin SwedbankPay_PaymentMenu SwedbankPay_Checkout
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

As parts of the Swedbank Pay Checkout installation we have **Client**, **Checkout**, **Checkin** and **Payment Menu**
with configurable options as follows:

### Client

* **Enabled**: Status of the module.
* **Merchant Account**: Your Swedbank Pay Merchant Account ID.
* **Payee ID**: Your Swedbank Pay Payee ID.
* **Payee Name**: Your Swedbank Pay Payee Name.
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
[the Swedbank Pay website][support].

## Release Notes

* **1.0.0**: May 2019 - First official release.

## License

Swedbank Pay Checkout for Magento 2 is released under [Apache V2.0 licence][license].

  [contact]:            https://swedbankpay.se/tjanster/swedbank-pay-checkout/
  [admin]:              https://developer.swedbankpay.com/xwiki/wiki/developer/view/Main/ecommerce/resources/admin/
  [cmpmgr]:             http://docs.magento.com/marketplace/user_guide/quick-tour/install-extension.html
  [support]:            https://swedbankpay.com/customer-service/
  [license]:            LICENSE
  [build-badge]:        https://travis-ci.org/SwedbankPay/swedbank-pay-magento2-checkout.svg?branch=master
  [build]:              https://travis-ci.org/SwedbankPay/swedbank-pay-magento2-checkout
  [version-badge]:      https://poser.pugx.org/swedbank-pay/magento2-checkout/version
  [downloads-badge]:    https://poser.pugx.org/swedbank-pay/magento2-checkout/downloads
  [license-badge]:      https://poser.pugx.org/swedbank-pay/magento2-checkout/license
  [packagist]:          https://packagist.org/packages/swedbank-pay/magento2-checkout
