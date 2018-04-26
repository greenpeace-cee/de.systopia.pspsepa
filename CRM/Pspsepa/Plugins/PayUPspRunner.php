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
 * Class CRM_Pspsepa_PayUPspRunner
 */
class CRM_Pspsepa_Plugins_PayUPspRunner extends CRM_Pspsepa_PspRunner {

  // TODO: Replace sandbox URL with production URL
  const API_URL = 'https://secure.snd.payu.com/api/v2_1/orders';
//  const API_URL = 'https://secure.payu.com/api/v2_1/orders';

  /**
   * @return string
   */
  public static function getPluginName() {
    return "PayU";
  }

  /**
   * @param $record
   * @param $params
   *
   * @return mixed|void
   */
  public function processRecord($record, $params) {
    require_once 'HTTP/Request.php';

    $request_params = json_decode($record, TRUE);

    // Add merchantAccount from form input.
    $request_params['merchantPosId'] = $params['account_name'];

    require_once 'HTTP/Request.php';
    $request = new HTTP_Request(self::API_URL, $request_params);
    $request->addHeader('Content-Type', 'application/json');
    // Add authentication token from form input.
    $request->addHeader('Authorization', 'Bearer ' . $params['authentication_token']);
    $request->sendRequest();
    $response = $request->getResponseBody();
  }

}
