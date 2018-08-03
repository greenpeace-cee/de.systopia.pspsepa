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
 * Class CRM_Pspsepa_AdyenPspRunner
 */
class CRM_Pspsepa_Plugins_AdyenPspRunner extends CRM_Pspsepa_PspRunner {
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
   * @return array
   *   An associative array with the following elements:
   *   - status: One of the statuses accepted by CRM_Core_Session::setStatus()
   *   - message: A message describing what happened
   *
   * @throws CiviCRM_API3_Exception
   *   When an API call failed.
   */
  public function processRecord($record, $params) {
    list($contribution_id, $payload) = explode(',', $record, 2);
    $request_params = json_decode($payload, TRUE);

    // Add merchantAccount from form input.
    $request_params['merchantAccount'] = $params['account_name'];

    require_once 'HTTP/Request.php';
    $request = new HTTP_Request($this->getSetting('adyen_authorise_api_url'));
    $request->setMethod('POST');
    // Add authentication token from form input.
    $request->addHeader('x-api-key', $params['authentication_token']);
    $request->addHeader('Content-Type', 'application/json');
    $request->setBody(json_encode($request_params));
    $request->sendRequest();
    $response = json_decode($request->getResponseBody(), TRUE);
    $response_code = $request->getResponseCode();
    if ($response_code != 200) {
      $result = array(
        'status' => 'error',
        'message' => E::ts(
          'HTTP connection status %1. Contribution ID: %2',
          array(
            1 => $response_code,
            2 => $contribution_id,
          )
        ),
      );
    }
    else {
      switch ($response['resultCode']) {
        case 'Authorised':
          // Update contribution, set status to "Completed".
          civicrm_api3('Contribution', 'create', array(
            'id' => $contribution_id,
            'contribution_status_id' => 'Completed',
          ));
          $result = array(
            'status' => 'success',
            'message' => E::ts(
              'Successfully processed contribution %1 with status "Completed".',
              array(
                1 => $contribution_id,
              )
            ),
          );
          break;
        case 'Refused':
        case 'Cancelled':
          switch ($response['refusalReason']) {
            case 'Expired Card':
              $cancel_reason = 'CC97'; // RDNCC: Card expired
              break;
            default:
              $cancel_reason = 'CC98'; // RDNCC: Declined
              break;
          }
          // Cancel contribution.
          civicrm_api3('Contribution', 'create', array(
            'id' => $contribution_id,
            'contribution_status_id' => 'Cancelled',
            'cancel_reason' => $cancel_reason,
            'cancel_date' => date('Y-m-d H:i:s'),
          ));
          $result = array(
            'status' => 'alert',
            'message' => E::ts(
              'Processed Contribution %1 with status "Cancelled" and reason "%2".',
              array(
                1 => $contribution_id,
                2 => $cancel_reason,
              )
            ),
          );
          break;
        default:
          $cancel_reason = 'CC98'; // RDNCC: Declined
          // Cancel contribution.
          civicrm_api3('Contribution', 'create', array(
            'id' => $contribution_id,
            'contribution_status_id' => 'Cancelled',
            'cancel_reason' => $cancel_reason,
            'cancel_date' => date('Y-m-d H:i:s'),
          ));
          $result = array(
            'status' => 'alert',
            'message' => E::ts(
              'Processed Contribution %1 with status "Cancelled" and reason "%2".',
              array(
                1 => $contribution_id,
                2 => $cancel_reason,
              )
            ),
          );
          break;
      }
    }

    return $result;
  }

}
