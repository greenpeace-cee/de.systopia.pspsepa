# Usage

## Prerequisites

Obtain an account reference (token) from the PSP for the associated contact.

## Creating a mandate

Go to the contact which to add a recurring PSP payment contract for

  - On the *SEPA Mandates* tab, click *Add new SEPA mandate*
  - Choose your previously configured *Creditor*
  - Enter the *Account Reference* obtained from the PSP previously
  - Enter *Amount*, *Frequency*, and any other information available
  - Click *Create* to create the mandate

## Preparing payments

When the payment is due (depending on creditor and mandate configuration), go to
*Contributions* → *CiviSEPA Dashboard*

  - Depending on the mandate type, click *Update One-Off* or
    *Update Recurring* to create transaction groups
  - Click *Close and Send* for the transaction group that corresponds to the
    creditor and mandate created previously
  - Download the transaction group file

## Submitting payment requests to the PSP

To submit transactions to the PSP, go to *Contributions* →
*Submit transactions (PSP SEPA)*

  - Choose the *PSP type*
  - Enter your PSP account credentials
  - Select the previously downloaded transaction group file
  - Click *Submit*
  - You will receive feedback about each contribution within the transaction
    group file
