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

class CRM_Sepa_Logic_Format_adyen extends CRM_Sepa_Logic_Format {

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
    return 'Adyen-';
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
      // Get shopperEmail.
      $trxn['shopperEmail'] = $this->getShopperEmailFromContact($trxn['contact_id']);

      // Get shopperIP.
      $trxn['shopperIP'] = $this->getIPAddress();

      // Get shopperReference and merchant
      $trxn['shopperReference'] = $trxn['iban'];
      $trxn['merchantAccount'] = $trxn['bic'];

      // Set total amount.
      // TODO: Adapt according to Adyen's specification:
      // @link https://docs.adyen.com/developers/currency-codes
      $trxn['total_amount'] *= 100;

      // Set reference to <shopperReference>_<contribution_id>.
      $trxn['reference'] = $trxn['shopperReference'] . '_' . $trxn['contribution_id'];

      // Get selectedRecurringDetailReference from contact.
      $trxn['selectedRecurringDetailReference'] = $this->getSelectedRecurringDetailReferenceFromContact($trxn['contact_id']);
    }
    catch (Exception $exception) {
      // TODO: Skip item?
    }
  }

  /**
   * @param $contact_id
   *
   * @return mixed
   * @throws Exception
   */
  protected function getShopperEmailFromContact($contact_id) {
    $email = civicrm_api3('Email', 'get', array(
      'contact_id'     => $contact_id,
      'is_primary' => 1,
      'return' => 'email',
    ));
    if ($email['count'] > 0) {
      return reset($email['values'])['email'];
    }
    return NULL;
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
   * @return string
   */
  protected function getSelectedRecurringDetailReferenceFromContact($contact_id) {
    // TODO: Retrieve from Adyen API.
    return 'LATEST';
  }

}
