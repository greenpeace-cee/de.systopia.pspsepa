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
   * @param $params
   *
   * @return mixed|void
   */
  public function processRecord($record, $params) {
    $request_params = json_decode($record, TRUE);

    // Add merchantAccount from form input.
    $request_params['merchantAccount'] = $params['account_name'];

    require_once 'HTTP/Request.php';
    $request = new HTTP_Request(self::API_URL, $request_params);
    $request->addHeader('Content-Type', 'application/json');
    // Add authentication token from form input.
    $request->addHeader('x-api-key', $params['authentication_token']);
    $request->sendRequest();
    $response = $request->getResponseBody();
  }

}
