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

/**
 * Class CRM_Pspsepa_AdyenPspRunner
 */
class CRM_Pspsepa_Plugins_AdyenPspRunner extends CRM_Pspsepa_PspRunner {

  /**
   *
   */
  const API_URL = 'https://pal-test.adyen.com/pal/servlet/Payment/v25/authorise';

  /**
   * @return string
   */
  public static function getPluginName() {
    return "Adyen";
  }

  /**
   * @param $record
   *
   * @return mixed|void
   */
  public function processRecord($record) {
    // TODO.
//    $params = array(
//      'amount' => array(
//        'value' => 2000,
//        'currency' => 'EUR',
//      ),
//      'reference' => 'Your Reference Here',
//      'merchantAccount' => 'TestMerchant',
//      'shopperEmail' => 's.hopper@test.com',
//      'shopperIP' => '61.294.12.12',
//      'shopperReference' => 'Simon Hopper',
//      'selectedRecurringDetailReference' => 'LATEST',
//      'recurring' => array(
//        'contract' => 'RECURRING',
//      ),
//      'shopperInteraction' => 'ContAuth',
//    );

    $params = json_decode($record, TRUE);

    require_once 'HTTP/Request.php';
    $request = new HTTP_Request(self::API_URL, $params);
    $request->sendRequest();
  }

}
