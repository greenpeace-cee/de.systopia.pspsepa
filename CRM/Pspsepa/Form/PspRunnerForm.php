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
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Pspsepa_Form_PspRunnerForm extends CRM_Core_Form {

  /**
   *
   */
  const BATCH_LIMIT = 10;

  /**
   * @inheritdoc
   */
  public function buildQuickForm() {
    $config = CRM_Core_Config::singleton();

    $this->add(
      'select',
      'psp_type',
      E::ts('PSP type'),
      $this->getPspTypes(),
      TRUE
    );

    $uploadFileSize = CRM_Utils_Number::formatUnitSize(
      $config->maxFileSize . 'm',
      TRUE
    );
    // Fetch uploadFileSize from php_ini when $config->maxFileSize is set to
    // "no limit".
    if (empty($uploadFileSize)) {
      $uploadFileSize = CRM_Utils_Number::formatUnitSize(
        ini_get('upload_max_filesize'),
        TRUE
      );
    }
    $uploadSize = round(($uploadFileSize / (1024 * 1024)), 2);
    $this->assign('uploadSize', $uploadSize);
    $this->add(
      'File',
      'uploadFile',
      ts('Import Data File'),
      'size=30 maxlength=255',
      TRUE
    );
    $this->setMaxFileSize($uploadFileSize);
    $this->addRule(
      'uploadFile',
      ts('File size should be less than %1 MBytes (%2 bytes)',
        array(
          1 => $uploadSize,
          2 => $uploadFileSize,
        )
      ),
      'maxfilesize',
      $uploadFileSize
    );
    $this->addRule(
      'uploadFile',
      ts('A valid file must be uploaded.'),
      'uploadedfile'
    );
    $this->addRule(
      'uploadFile',
      ts('Input file must be in UTF-8 format'),
      'utf8File'
    );

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    // Export form elements.
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();

    if (isset($this->_submitFiles['uploadFile'])) {
      $uploadFile = $this->_submitFiles['uploadFile'];
    }
    else {
      CRM_Core_Session::setStatus(
        E::ts('No file was uploaded.'),
        E::ts('Import failure'),
        'no-popup'
      );
      return;
    }

    $file = $uploadFile['tmp_name'];
    $filename = $uploadFile['name'];

    $fd = fopen($file, 'r');
    if (!$fd) {
      CRM_Core_Session::setStatus(
        E::ts('Could not read import file %1.', array(1 => $filename)),
        E::ts('Import failure'),
        'no-popup'
      );
      return;
    }
    if (filesize($file) == 0) {
      CRM_Core_Session::setStatus(
        E::ts('Import file %1 is empty. Please upload a valid file.', array(1 => $filename)),
        E::ts('Import failure'),
        'no-popup'
      );
      return;
    }
    fclose($fd);

    if (class_exists($values['psp_type'])) {
      $runner = new $values['psp_type']();
      if (method_exists($runner, 'processRecords')) {
        $runner->processRecords($file, self::BATCH_LIMIT);
      }
    }

    parent::postProcess();
  }

  public function getPspTypes() {
    return CRM_Pspsepa_PspRunner::getPlugins();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
