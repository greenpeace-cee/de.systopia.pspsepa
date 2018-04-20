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

  const API_URL = 'https://secure.payu.com/api/v2_1/orders';

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
    // TODO.
    require_once 'HTTP/Request.php';

    $request_params = json_decode($record);
    // TODO: Merge JSON record with authentication params etc.
    $request_params['grant_type'] = 'client_credentials';
    $request_params['client_id'] = 145227;
    $request_params['client_secret'] = '12f071174cb7eb79d4aac5bc2f07563f';
    $request_params['customerIp'];
    $request_params['merchantPosId'];
    $request_params['description'];
    $request_params['currencyCode'];
    $request_params['products'];

    $request = new HTTP_Request(self::API_URL, $request_params);
    $request->sendRequest();
  }

}
