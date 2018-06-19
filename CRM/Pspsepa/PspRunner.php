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
 * Class CRM_Pspsepa_PspRunner
 */
abstract class CRM_Pspsepa_PspRunner {

  /**
   * @var array $_plugins
   */
  protected static $_plugins;

  /**
   * @return array
   */
  public static function getPlugins() {
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
        $this->processRecord($record, $params);
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

}
