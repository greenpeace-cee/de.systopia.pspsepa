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
    list($contribution_id, $payload) = explode(',', $record, 2);
    $request_params = json_decode($payload, TRUE);

    // Add merchantAccount from form input.
    $request_params['merchantAccount'] = $params['account_name'];

    require_once 'HTTP/Request.php';
    $request = new HTTP_Request(self::API_URL);
    $request->setMethod('POST');
    // Add authentication token from form input.
    $request->addHeader('x-api-key', $params['authentication_token']);
    $request->addHeader('Content-Type', 'application/json');
    $request->setBody(json_encode($request_params));
    $request->sendRequest();
    $response = json_decode($request->getResponseBody(), TRUE);
    if ($response['status'] != 200) {
      // TODO: Error handling.
      switch ($response['errorCode']) {
        default:
          break;
      }
    }
    else {
      switch ($response['resultCode']) {
        case 'Authorised':
          // Update contribution, set status to "Completed".
          civicrm_api3('Contribution', 'create', array(
            'id' => $contribution_id,
            'contribution_status_id' => 'Completed',
          ));
          break;
        case 'Refused':
        case 'Cancelled':
          // TODO: Zuwendungen/Mandate bearbeiten - welche Status?.
          switch ($response['refusalReason']) {
            case 'Expired Card':
              $cancel_reason = 'CC97'; // RDNCC: Card expired
              break;
            default:
              $cancel_reason = 'XX02'; // Cancellation without explanation
              break;
          }
          civicrm_api3('Contribution', 'create', array(
            'id' => $contribution_id,
            'contribution_status_id' => 'Cancelled',
            'cancel_reason' => $cancel_reason,
          ));
          break;
      }
    }
  }

}
