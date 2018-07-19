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

/**
 * Class CRM_Pspsepa_PayUPspRunner
 */
class CRM_Pspsepa_Plugins_PayUPspRunner extends CRM_Pspsepa_PspRunner {

  /**
   * API URL to send requests to.
   */
  //  const API_URL = 'https://secure.snd.payu.com/api/v2_1/orders';
  const API_URL = 'https://secure.payu.com/api/v2_1/orders';

  /**
   * API URL to send authorization requests to.
   */
  //  const AUTHORIZE_URL = 'https://secure.snd.payu.com/pl/standard/user/oauth/authorize';
  const AUTHORIZE_URL = 'https://secure.payu.com/pl/standard/user/oauth/authorize';

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

    list($contribution_id, $payload) = explode(',', $record, 2);
    $request_params = json_decode($payload, TRUE);

    // Add merchantAccount from form input.
    $request_params['merchantPosId'] = $params['account_name'];

    // Request access token with client credentials.
    $auth_request_params = array(
      'grant_type' => 'client_credentials',
      'client_id' => $params['client_id'],
      'client_secret' => $params['authentication_token'],
    );
    $auth_request = new HTTP_Request(self::AUTHORIZE_URL);
    $auth_request->setMethod('POST');
    foreach ($auth_request_params as $auth_request_param_name => $auth_request_param_value) {
      $auth_request->addPostData($auth_request_param_name, $auth_request_param_value);
    }
    $auth_request->addHeader('Content-Type', 'application/x-www-form-urlencoded');
    $auth_request->sendRequest();
    $auth_response = json_decode($auth_request->getResponseBody(), TRUE);
    if (isset($auth_response['access_token'])) {
      $request = new HTTP_Request(self::API_URL);
      $request->setMethod('POST');
      $request->addHeader('Content-Type', 'application/json');
      $request->setBody(json_encode($request_params));
      // Add authentication token from form input.
      $request->addHeader('Authorization', 'Bearer ' . $auth_response['access_token']);
      $request->sendRequest();
      $response_code = $request->getResponseCode();
      $response = json_decode($request->getResponseBody(), TRUE);
      if ($response_code != 200 && $response_code != 201) {
        CRM_Core_Session::setStatus(
          E::ts('HTTP connection status %1. Contribution ID: %2', array(
            1 => $response_code,
            2 => $contribution_id,
          )),
          E::ts('Processing record failed'),
          'no-popup'
        );
      }
      else {
        switch ($response['status']['statusCode']) {
          case 'SUCCESS':
            // Update contribution, set status to "Completed".
            civicrm_api3('Contribution', 'create', array(
              'id' => $contribution_id,
              'contribution_status_id' => 'Completed',
            ));
            break;
          default:
            $cancel_reason = 'CC98'; // RDNCC: Declined
            civicrm_api3('Contribution', 'create', array(
              'id' => $contribution_id,
              'contribution_status_id' => 'Cancelled',
              'cancel_reason' => $cancel_reason,
              'cancel_date' => date('Y-m-d H:i:s'),
            ));
            break;
        }
      }
    }
    else {
      CRM_Core_Session::setStatus(
        E::ts('Could not retrieve authorization token. Contribution ID: %1', array(
          1 => $contribution_id,
        )),
        E::ts('Processing record failed'),
        'no-popup'
      );
    }
  }

}
