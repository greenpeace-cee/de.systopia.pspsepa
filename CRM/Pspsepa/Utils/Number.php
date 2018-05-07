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
 * Class CRM_Pspsepa_Utils_Number
 *
 * This class extends the core CRM_Utils_Number class with methods added in
 * CiviCRM 4.7 for compatibility.
 */
class CRM_Pspsepa_Utils_Number extends CRM_Utils_Number {

  /**
   * Some kind of numbery-looky-printy thing.
   *
   * @param string $size
   * @param bool $checkForPostMax
   *
   * @return int
   */
  public static function formatUnitSize($size, $checkForPostMax = FALSE) {
    if (method_exists(parent::class, 'formatUnitSize')) {
      return parent::formatUnitSize($size, $checkForPostMax);
    }

    if ($size) {
      $last = strtolower($size{strlen($size) - 1});
      switch ($last) {
        // The 'G' modifier is available since PHP 5.1.0

        case 'g':
          $size *= 1024;
        case 'm':
          $size *= 1024;
        case 'k':
          $size *= 1024;
      }

      if ($checkForPostMax) {
        $maxImportFileSize = self::formatUnitSize(ini_get('upload_max_filesize'));
        $postMaxSize = self::formatUnitSize(ini_get('post_max_size'));
        if ($maxImportFileSize > $postMaxSize && $postMaxSize == $size) {
          CRM_Core_Session::setStatus(ts("Note: Upload max filesize ('upload_max_filesize') should not exceed Post max size ('post_max_size') as defined in PHP.ini, please check with your system administrator."), ts("Warning"), "alert");
        }
        // respect php.ini upload_max_filesize
        if ($size > $maxImportFileSize && $size !== $postMaxSize) {
          $size = $maxImportFileSize;
          CRM_Core_Session::setStatus(ts("Note: Please verify your configuration for Maximum File Size (in MB) <a href='%1'>Administrator >> System Settings >> Misc</a>. It should support 'upload_max_size' as defined in PHP.ini.Please check with your system administrator.", array(1 => CRM_Utils_System::url('civicrm/admin/setting/misc', 'reset=1'))), ts("Warning"), "alert");
        }
      }
      return $size;
    }
  }

}
