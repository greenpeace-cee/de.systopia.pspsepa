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

require_once 'pspsepa.civix.php';
use CRM_Pspsepa_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function pspsepa_civicrm_config(&$config) {
  _pspsepa_civix_civicrm_config($config);
  // de.systopia.pspsepa alters the civicrm_sdd_mandate.bic column to allow up
  // to 25 characters. This change needs to be pushed to the DAO or it'll cause
  // validation errors for BICs with more than 11 characters. This is quite
  // hacky, unfortunately ...
  $fields = CRM_Sepa_DAO_SEPAMandate::fields();
  $fields['bic']['maxlength'] = 25;
  if (isset(CRM_Sepa_DAO_SEPAMandate::$_fields)) {
    CRM_Sepa_DAO_SEPAMandate::$_fields = $fields;
  }
  else {
    Civi::$statics['CRM_Sepa_DAO_SEPAMandate']['fields'] = $fields;
  }

}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function pspsepa_civicrm_install() {
  _pspsepa_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function pspsepa_civicrm_enable() {
  _pspsepa_civix_civicrm_enable();

  $customData = new CRM_Pspsepa_CustomData('de.systopia.pspsepa');
  $customData->syncOptionGroup(__DIR__ . '/resources/formats_option_group.json');
  $customData->syncOptionGroup(__DIR__ . '/resources/banking_reference_types_option_group.json');
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *

 // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 */
function pspsepa_civicrm_navigationMenu(&$menu) {
  $menu_item_search = array(
    'name' => 'Submit transactions',
  );
  $menu_items = array();
  CRM_Core_BAO_Navigation::retrieve($menu_item_search, $menu_items);
  _pspsepa_civix_insert_navigation_menu($menu, 'Contributions', array(
    'label' => E::ts('Submit transactions (PSP SEPA)', array('domain' => 'de.systopia.pspsepa')),
    'name' => 'Submit transactions (PSP SEPA)',
    'url' => 'civicrm/pspsepa/submit',
    'permission' => 'batch sepa groups',
    'operator' => 'OR',
    'separator' => 0,
    // See https://github.com/civicrm/civicrm-core/pull/11772 for weight.
    'weight' => (isset($menu_items['weight']) ? $menu_items['weight'] : 0),
  ));
  _pspsepa_civix_navigationMenu($menu);
}
