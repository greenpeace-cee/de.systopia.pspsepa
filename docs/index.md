# Overview

This extension utilises the workflow of SEPA Direct Debit payments to keep
track of payment contracts with external payment service providers and therefore
extends the
[CiviSEPA extension](https://github.com/project60/org.project60.sepa).

In short, the SEPA Direct Debit process works like the following:

1. Debtor issues a mandate to the creditor, stating the amount, date and
   frequency of installments the creditor is allowed to collect
2. Creditor instructs their bank to collect the mandated amount from the
   debtor's bank account
3. Debtor's bank transfers the amount from the debtor's bank account to the
   creditor's bank.
4. Creditor's bank transfers the amount to the creditor's bank account

This process is similar to how payment service providers (PSP) process their
payments. Instead of a mandate, PSP associate payment tokens to debtors, which
can be used by the creditor to collect recurring payments without the debtor
being present or entering their data for each single payment, or having to
maintain all the different payment methods offered by the PSP. With this token,
creditors may then issue payment requests to the PSP, who, in turn, process the
payment with the payment method and debtor information associated with the
payment contract.

This extension extends CiviSEPA with a general framework for individual PSP
implementations to plug in. Each implementation provides:

- A transaction group file format
- A runner implementation to send transactions to the PSP
