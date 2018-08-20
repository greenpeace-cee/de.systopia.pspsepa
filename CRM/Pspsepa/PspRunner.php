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
 * Class CRM_Pspsepa_PspRunner
 */
abstract class CRM_Pspsepa_PspRunner {

  /**
   * @var array $_plugins
   */
  protected static $_plugins;

  public $title     = NULL;
  protected $record = NULL;
  protected $params = NULL;

  /**
   * CRM_Pspsepa_PspRunner constructor.
   *
   * @param $record
   */
  protected function __construct($record, $params) {
    $this->record = $record;
    $this->params = $params;

    if ($record === NULL) {
      $this->title = ts("Initialising runner ...", array('domain' => 'de.systopia.pspsepa'));
    } else {
      $this->title = ts("Analysing contributions", array('domain' => 'de.systopia.pspsepa'));
    }
  }

  /**
   * @param $context
   *
   * @return bool
   */
  public function run($context) {
    if ($this->record) {
      $result = $this->processRecord($this->record, $this->params);
      switch ($result['status']) {
        case 'success':
          $message_title = E::ts('Processing record succeeded');
          break;
        case 'error':
          $message_title = E::ts('Processing record failed');
          break;
        case 'alert':
          $message_title = E::ts('Processing record threw a warning');
          break;
        default:
          $message_title = E::ts('Processed record');
      }
      CRM_Core_Session::setStatus(
        $result['message'],
        $message_title,
        'no-popup'
      );
    }

    return TRUE;
  }

  /**
   * Use CRM_Queue_Runner to apply the templates
   * This doesn't return, but redirects to the runner
   */
  public static function createRunner($filename, $params) {
    // create a queue
    $queue = CRM_Queue_Service::singleton()->create(array(
      'type'  => 'Sql',
      'name'  => 'pspsepa_runner',
      'reset' => TRUE,
    ));

    $file = new SplFileObject($filename);
    while($file->valid()) {
      $record = $file->fgets();
      $queue->createItem(new $params['psp_type']($record, $params));
    }

    // create a runner and launch it
    $runner = new CRM_Queue_Runner(array(
      'title'     => ts("Processing contribution", array('domain' => 'de.systopia.pspsepa')),
      'queue'     => $queue,
      'errorMode' => CRM_Queue_Runner::ERROR_CONTINUE,
      'onEndUrl'  => '/civicrm/pspsepa/submit',
    ));
    $runner->runAllViaWeb();
  }

  /**
   * @return array
   */
  public static final function getPlugins() {
    if (!isset(self::$_plugins)) {
      self::$_plugins = array();
      foreach (glob(__DIR__ . "/Plugins/*.php") as $filename) {
        include_once $filename;
        $class_name = 'CRM_Pspsepa_Plugins_' . pathinfo($filename)['filename'];
        if (class_exists($class_name) && method_exists($class_name,'getPluginName')) {
          self::$_plugins[$class_name] = $class_name::getPluginName();
        }
      }
    }
    return self::$_plugins;
  }

  /**
   * @return string
   */
  public static function getPluginName() {
    return static::class;
  }

  /**
   * @param $filename
   * @param int $limit
   * @param int $offset
   * @param array $params
   */
  public final function processRecords($filename, $limit = 0, $offset = 0, $params = array()) {
    $file = new SplFileObject($filename);
    $file->seek($offset);
    for ($l = 0; ($limit == 0 || $l < $limit); $l++) {
      if (!$file->valid()) {
        break;
      }
      $record = $file->fgets();
      if ($record) {
        $result = $this->processRecord($record, $params);
        switch ($result['status']) {
          case 'success':
            $message_title = E::ts('Processing record succeeded');
            break;
          case 'error':
            $message_title = E::ts('Processing record failed');
            break;
          case 'alert':
            $message_title = E::ts('Processing record threw a warning');
            break;
          default:
            $message_title = E::ts('Processed record');
        }
        CRM_Core_Session::setStatus(
          $result['message'],
          $message_title,
          'no-popup'
        );
      }
    }
  }

  /**
   * @param $record
   * @param $params
   *
   * @return mixed
   */
  public abstract function processRecord($record, $params);

  /**
   * Get the value of setting $name or its default value
   *
   * @param $name name of the setting
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  protected function getSetting($name) {
    $value = civicrm_api3('Setting', 'GetValue', [
      'name' => $name,
      'group' => 'PSP SEPA',
    ]);
    if (empty($value)) {
      $default = civicrm_api3('Setting', 'getdefaults', [
        'name' => $name,
        'group' => 'PSP SEPA',
      ]);
      $value = reset($default['values'])[$name];
    }
    return $value;
  }

}
