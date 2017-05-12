Marello Bridge Magento 2 Extension
=============

Marello Bridge extension creates the ability to push entities from Magento to Marello via the Marello API.
The extension will create a queued record for sending order (updates) to Marello. This includes creating a customer if
the customer is not in the Marello application.

## Features:

- Queued processing of orders
- Import products from Marello
- Update orders in Magento (including creation of shipments)


## Requirements

* PHP 5.5.0 or above with command line interface
* Magento 2.1.0 or above**
* Marello Bridge Api 1.1 or above


## Installation instructions

In order to get the Marello Bridge, you can easily install this through composer. If you don't have composer installed globally, you can get it by running the following command:
```bash
curl -s https://getcomposer.org/installer | php
```

```bash
php composer.phar require "marellocommerce/magento2-marello-bridge"
```

- Install dependencies with composer. If installation process seems too slow you can use `--prefer-dist` option.

```bash
php composer.phar install --prefer-dist --no-dev
```


## Usage
Developers are able to create their own processors to process different entities through some configuration and
implementation of certain classes. More on extending and configuring own processors in the see [USAGE.md](doc/USAGE.md)


## Contact
Questions? Problems? Improvements?

Feel free to contact us either through [http://www.marello.com/contact/](http://www.marello.com/contact/), forum [http://www.marello.com/forum/marello/](http://www.marello.com/forum/marello/) or open an issue in the repository :) Thanks!