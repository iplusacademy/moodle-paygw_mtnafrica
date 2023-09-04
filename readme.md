# MTN

This plugin was developed thanks to funding from Medical Access Uganda (https://e-learning.medical-access.org)

The plugin allows a site to connect to MTN Africa to process payments.

Currently this plugin supports payment on following currencies:

| Country | Country Code | Currency | Currency Code |
| :---- | :----: | :---- | :----: |
| Benin | BJ | West African CFA franc | XOF |
| Cameroon | CM | CFA franc BEAC | XAF |
| Chad | TD | CFA franc BEAC | XAF |
| Congo-Brazzaville | CG | CFA franc BCEA | XAF |
| DR Congo | CD | Congolese franc | CDF |
| Ghana | GH | Ghanaian New Cedi | GHS |
| Guinea | GN | Guinean franc | GNF |
| Ivory Coast | CI | West African CFA franc | XOF |
| Liberia | LR | Liberian Dollar | LRD |
| Niger | NE | CFA franc BCEAO | XOF |
| Rwanda | RW | Rwandan Franc | RWF |
| South Africa | ZA | South African Rand | ZAR |
| Uganda | UG | Ugandan shilling | UGX |
| Zambia | ZM | Zambian kwacha | ZMW |
| Testing | sandbox | Euro | EUR |

## Rates of Using MoMo Pay

MoMoPay service is free for all customers.
Formal merchants will be charged 2% of the payment received.
Informal merchants will be charged 1% of the payment received.

## Setup MTN account

To set up access within Moodle you will need to:
* Register a new application (MTN Africa have their [own docs](https://momodeveloper.mtn.com/) on this.)
* Enable the Collections service so you can do remote collection of bills, fees or taxes.
* Create a new application where you can configure the callback url. The URL is in the format "https://example.com/payment/gateway/mtnafrica/callback.php".
* Visit [Go live](https://momodeveloper.mtn.com/go-live), enable the countries you want to work in, add KYC information, and you can use the plugin live.
* Make a phone call to your local MTN representative so your submitted resquest is accepted, yes, somebody at MTN Africa needs to turn a switch before your changes take effect.
* For every change, callback url, enable extra APIs, ... see previous line. 

## Install

You can install this plugin from the plugin directory or get the latest version on GitHub.

```bash
git clone https://github.com/iplusacademy/moodle-paygw_mtnafrica.git payment/gateway/mtnafrica
```

## Dependencies

* MOODLE_401_STABLE needs the [Amazon's SDK for PHP plugin](https://moodle.org/plugins/local_aws).
* MOODLE_402_STABLE+ relies on Guzzle (already a part of Moodle).

## Configure Moodle

* Go to site administration / Plugins / Manage payment gateways and enable the MTN payment gateway.
* Go to site administration / Payments / Payment accounts
* Click the button 'Create payment account' then enter an account name for identifying it when setting up enrolment on payment, then save changes.
* On the Payment accounts page, click the payment gateway link to configure mtn.
* In the configuration page, 
    * Enter your primary key from the collection service you have created in the MTN developer centre
    * Enter your secondary key from the collection service you have created in the MTN developer centre

## Add Enrolment on payment.

* Go to Go to Site administration > Plugins > Enrolments > Manage enrol plugins and click the eye icon opposite Enrolment on payment.
* Click the settings link, configure as required then click the 'Save changes' button.
* Go to the course you wish to enable payment for, and add the 'Enrolment on payment' enrolment method to the course.
* Select a payment account, amend the enrolment fee as necessary then click the button 'Add method'.

see also:  
[moodledocs: Payment Gateways](https://docs.moodle.org/en/Payment_gateways)  
[moodledocs: Enrolment on Payment](https://docs.moodle.org/en/Enrolment_on_payment)

## Theme support

This plugin is developed and tested on Moodle Core's Boost theme and Boost child themes, including Moodle Core's Classic theme.

## Database support

This plugin is developed and tested using

* MYSQL
* MariaDB
* PostgreSQL

## Testing

The easiest way to test this plugin is to configure your sandbox environment with the keys provided from MTN.
When you create a course and add a enrolment on fee with the cost of 40 EUR (the sandbox only accepts EURO).
Next create a user with '46733123454' as telephone number and log in as this user.  When asked for a payment,
you will be able to see the payment process completely, and after some waiting be automatically enrolled in the course.

This plugin can also be tested in PHPUnit and Behat, but you need to add your login - secret - secret1 keys as an environment variable.

```bash
* env login=???? secret=???? secret1=???? vendor/bin/phpunit --coverage-text payment/gateway/airtelafrica/
* env login=???? secret=???? secret1=???? vendor/bin/behat --tags='paygw_airtelafrica'
```

Or you can use secrets in Github actions:

```bash
* gh secret set login -b"?????"
* gh secret set secret -b"?????"
* gh secret set secret1 -b"?????"
```

## Plugin repositories

This plugin will be published and regularly updated on [Github](https://github.com/iplusacademy/moodle-paygw_mtnafrica)

## Bug and problem reports / Support requests

This plugin is carefully developed and only thoroughly tested in Uganda, but bugs and problems can always appear.
Please report bugs and problems on [Github](https://github.com/iplusacademy/moodle-paygw_mtnafrica/issues)
We will do our best to solve your problems, but please note that we can't provide per-case support.
Please contact you MTN Africa representative in case you get invalid transactionids or timeouts.

## Feature proposals

- Please issue feature proposals on [Github](https://github.com/iplusacademy/moodle-paygw_mtnafrica/issues)
- Please create pull requests on [Github](https://github.com/iplusacademy/moodle-paygw_mtnafrica/pulls)
- We are always interested to read about your feature proposals or even get a pull request from you, but please accept that we can handle your issues only as feature proposals and not as feature requests.

## Status

[![Build Status](https://github.com/iplusacademy/moodle-paygw_mtnafrica/actions/workflows/main.yml/badge.svg)](https://github.com/iplusacademy/moodle-paygw_mtnafrica/actions)
[![Coverage Status](https://coveralls.io/repos/github/iplusacademy/moodle-paygw_mtnafrica/badge.svg)](https://coveralls.io/github/iplusacademy/moodle-paygw_mtnafrica)

## License

2023 Medical Access Uganda

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
