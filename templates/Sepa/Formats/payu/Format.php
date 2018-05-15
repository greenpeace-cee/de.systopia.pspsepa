<?php
/*-------------------------------------------------------+
| SYSTOPIA CiviSEPA PSP Extension                        |
| Copyright (C) 2018 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
|         J. Schuppe (schuppe@systopia.de)               |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+-------------------------------------------------------*/

use CRM_Pspsepa_ExtensionUtil as E;

class CRM_Sepa_Logic_Format_payu extends CRM_Sepa_Logic_Format {

  /** cache for Creditor ID to IBAN mapping */
  protected $creditor2iban = array();

  /** cached generator version */
  protected $generator = NULL;

  /**
   * gives the option of setting extra variables to the template
   */
  public function assignExtraVariables($template) {

  }

  /**
   * proposed group prefix
   */
  public function getDDFilePrefix() {
    return 'PAYU-';
  }

  /**
   * proposed file name
   */
  public function getFilename($variable_string) {
    return $variable_string.'.txt';
  }

  /**
   * Lets the format add extra information to each individual
   *  transaction (contribution + extra data)
   */
  public function extendTransaction(&$trxn, $creditor_id) {
    try {
      $trxn['customerIp'] = $this->getIPAddress();

      $contact = $this->getContact($trxn['contact_id']);
      $trxn['buyerFirstName'] = $contact['first_name'];
      $trxn['buyerLastName'] = $contact['last_name'];
      $trxn['buyerLanguage'] = (!empty($contact['preferred_language']) ? substr($contact['preferred_language'], 0, 2) : '');
      $trxn['buyerEmail'] = $contact['email'];

      // Get shopperReference.
      $trxn['payMethods'] = array(
        'payMethod' => array(
          'type' => 'CARD_TOKEN',
          'value' => $this->getpayMethodTokenFromIBAN($trxn['iban']),
        ),
      );

      // Set order description.
      $trxn['description'] = 'Recurring contribution';

      // Set products (incl. name, unitPrice, quantity).
      $trxn['productName'] = 'Recurring contribution';
      $trxn['productUnitPrice'] = $trxn['total_amount'];
      $trxn['productQuantity'] = 1;
    }
    catch (Exception $exception) {
      // TODO: Skip item?
    }
  }

  /**
   * @return mixed
   */
  protected function getIPAddress() {
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
  }

  /**
   * @param $contact_id
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  protected function getContact($contact_id) {
    $contact = civicrm_api3('Contact', 'getsingle', array(
      'id' => $contact_id,
    ));
    if (!empty($contact['is_error'])) {
      throw new Exception(E::ts('Contact not found.'));
    }
    return $contact;
  }

  /**
   * @param $iban
   * @param string $format
   *
   * @return mixed
   * @throws \Exception
   */
  protected function getPayMethodTokenFromIBAN($iban, $format = "/[_]/") {
    $matches = preg_split($format, $iban);
    if (empty($matches[1])) {
      throw new Exception(E::ts('Could not extract PayMethod token from IBAN.'));
    }
    return $matches[1];
  }
}
