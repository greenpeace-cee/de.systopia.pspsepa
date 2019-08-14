<?php
use CRM_Pspsepa_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Pspsepa_Upgrader extends CRM_Pspsepa_Upgrader_Base {

  /**
   * Change CiviSEPA BIC field to allow up to 25 characters
   *
   * The BIC field may be used to store PSP-specific data like Adyen merchants
   *
   * @return bool
   * @throws Exception
   */
  public function upgrade_0110() {
    $this->ctx->log->info('Applying update 0110');
    CRM_Core_DAO::executeQuery(
      'ALTER TABLE civicrm_sdd_mandate MODIFY bic VARCHAR(25)'
    );
    $logging = new CRM_Logging_Schema();
    $logging->fixSchemaDifferences();
    return TRUE;
  }

}
