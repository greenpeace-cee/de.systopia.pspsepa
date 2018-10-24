# Getting started

## Requirements

- CiviCRM 4.6 / 4.7 / 5.x
- CiviSEPA 1.4

## Installation

Install the extension as described in the
[CiviCRM System Administrator Guide](https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/#installing-a-new-extension).

## Configuration

- Create a CiviSEPA creditor for the desired payment servidce provider (PSP):
    - Go to *Administration* → *CiviContribute* → *CiviSEPA Settings*
    - Under section "Creditors", *Add* a new creditor or *Copy* an existing one
    - Choose "PSP" as the *Type*
    - Choose a *PAIN data format* associated to the PSP to use
