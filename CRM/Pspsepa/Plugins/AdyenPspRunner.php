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
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

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
    // acquire a lock for this contribution to prevent a race on the following
    // trxn_id check.
    $lockKey = 'worker.contribute.adyen_' . $contribution_id;
    $lock = \Civi::lockManager()->acquire($lockKey);
    if (!$lock->isAcquired()) {
      return [
        'status' => 'error',
        'message' => E::ts(
          'Could not acquire lock for Contribution ID: %1. You may have submitted the file multiple times.',
          [
            1 => $contribution_id,
          ]
        ),
      ];
    }
    // fetch the trxn_id of this contribution. an existing value implies that
    // the contribution may have already been sent to Adyen.
    $trxn_id = civicrm_api3('Contribution', 'getvalue', [
      'return' => 'trxn_id',
      'id'     => $contribution_id,
    ]);
    if (!empty($trxn_id)) {
      $lock->release();
      return [
        'status' => 'error',
        'message' => E::ts(
          'Found existing trxn_id for Contribution ID: %1. The contribution may have already been sent to Adyen. Clear trxn_id to re-submit the contribution.',
          [
            1 => $contribution_id,
          ]
        ),
      ];
    }

    $request_params = json_decode($payload, TRUE);

    if (empty($request_params['merchantAccount']) || $request_params['merchantAccount'] == 'NOTPROVIDED') {
      // Add merchantAccount from form input.
      $request_params['merchantAccount'] = $params['account_name'];
    }

    $client = new Client([
      'timeout'  => 60,
      'http_errors' => FALSE,
    ]);
    $request = new Request(
      'POST',
      $this->getSetting('adyen_authorise_api_url'),
      [
        'Accept'        => 'application/json',
        'Content-Type'  => 'application/json',
      ],
      json_encode($request_params)
    );
    $request = $request->withHeader('x-api-key', $params['authentication_token']);
    $request = $request->withHeader('User-Agent', self::getUserAgent());
    $responseObject = $client->send($request);
    $response = json_decode($responseObject->getBody(), TRUE);
    $response_code = $responseObject->getStatusCode();
    if (defined('PSPSEPA_LOGGING') && PSPSEPA_LOGGING) {
      CRM_Core_Error::debug_log_message("Received Adyen Response for Contribution {$contribution_id}: HTTP {$response_code}: {$responseObject->getBody()}");
    }
    if (!empty($response['resultCode'])) {
      switch ($response['resultCode']) {
        case 'Authorised':
          // Update contribution, set status to "Completed".
          civicrm_api3('Contribution', 'create', array(
            'id' => $contribution_id,
            'contribution_status_id' => 'Completed',
            'trxn_id' => $request_params['reference'],
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
        case 'Received':
          // Transaction was received but has not yet been processed
          // If "Only Completed Contributions" is set in CiviSEPA, set/keep
          // at "Completed", otherwise set to "In Progress"
          $skip_closed = CRM_Core_BAO_Setting::getItem('SEPA Direct Debit Preferences', 'sdd_skip_closed');
          if ($skip_closed) {
            $contribution_status = 'Completed';
          } else {
            $contribution_status = 'In Progress';
          }
          civicrm_api3('Contribution', 'create', array(
            'id' => $contribution_id,
            'contribution_status_id' => $contribution_status,
            'trxn_id' => $request_params['reference'],
          ));
          $result = array(
            'status' => 'success',
            'message' => E::ts(
              'Successfully processed contribution %1 with status "%2".',
              array(
                1 => $contribution_id,
                2 => $contribution_status,
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
            'trxn_id' => $request_params['reference'],
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
            'trxn_id' => $request_params['reference'],
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
    elseif ($response_code == 422) {
      $cancel_reason = 'CC99'; // RDNCC: DATA ERROR
      // Cancel contribution.
      civicrm_api3('Contribution', 'create', array(
        'id' => $contribution_id,
        'contribution_status_id' => 'Cancelled',
        'cancel_reason' => $cancel_reason,
        'cancel_date' => date('Y-m-d H:i:s'),
        'trxn_id' => $request_params['reference'],
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
    }
    elseif ($response_code != 200) {
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
    $lock->release();
    return $result;
  }

}
