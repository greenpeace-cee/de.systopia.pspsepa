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
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function pspsepa_civicrm_xmlMenu(&$files) {
  _pspsepa_civix_civicrm_xmlMenu($files);
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
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function pspsepa_civicrm_postInstall() {
  _pspsepa_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function pspsepa_civicrm_uninstall() {
  _pspsepa_civix_civicrm_uninstall();
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
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function pspsepa_civicrm_disable() {
  _pspsepa_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function pspsepa_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _pspsepa_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function pspsepa_civicrm_managed(&$entities) {
  _pspsepa_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function pspsepa_civicrm_caseTypes(&$caseTypes) {
  _pspsepa_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function pspsepa_civicrm_angularModules(&$angularModules) {
  _pspsepa_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function pspsepa_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _pspsepa_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function pspsepa_civicrm_entityTypes(&$entityTypes) {
  _pspsepa_civix_civicrm_entityTypes($entityTypes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function pspsepa_civicrm_preProcess($formName, &$form) {

} // */

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
    'weight' => $menu_items['weight'],
  ));
  _pspsepa_civix_navigationMenu($menu);
}
