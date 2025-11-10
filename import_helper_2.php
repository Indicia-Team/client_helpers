<?php

/**
 * @file
 * Helper class for data imports version 2.
 *
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/indicia-team/warehouse/
 */

use IForm\IndiciaConversions;

/**
 * Link in other required php files.
 */

require_once 'lang.php';
require_once 'lang/import_helper_2.php';
require_once 'helper_base.php';

/**
 * Static helper class that provides methods for dealing with imports.
 *
 * Version 2 which uses a temp table on the warehouse as a place to prepare
 * the imported data before actually importing.
 */
class import_helper_2 extends helper_base {

  /**
   * Array of field names which are disallowed for import.
   *
   * Can be altered before calling the importer.
   *
   * @var array
   */
  public static array $blockedFields = [
    'occurrence:all_info_in_determinations',
    'occurrence:fk_classification_event',
    'occurrence:downloaded_flag',
    'occurrence:downloaded_on',
    'occurrence:last_verification_check_date',
    'occurrence:machine_involvement',
    'occurrence:metadata',
    'occurrence:record_decision_source',
    'sample:fk_parent',
    'sample:fk_parent:external_key',
    'id',
    'created_by_id',
    'deleted',
    'fk_created_by',
    'fk_determiner',
    'fk_updated_by',
    'fk_verified_by',
    'created_on',
    'updated_on',
    'verified_on',
    'verifier_only_data',
    'survey_id',
    'website_id',
    'fk_survey',
    'fk_website',
    'training',
  ];

  /**
   * A file import component.
   *
   * Options include:
   * * blockedFields - array of database field names that should not be listed
   *   as available for mapping to. Defaults to a list of "advanced" fields
   *   that are not considered suitable for non-expert imports. Field names may
   *   be fully qualified ('sample.deleted') or just the field name alone, in
   *   which case it applies to all tables.
   * * entity
   * * fileSelectFormIntro
   * * globalValuesFormIntro
   * * mappingsFormIntro
   * * lookupMatchingFormIntro
   * * preprocessPageIntro
   * * summaryPageIntro
   * * doImportPageIntro
   * * requiredFieldsIntro
   * * backgroundImportStatusPath - path to a Drupal page, normally built using
   *   Utilities > Background Import Status, which can be provided as a link
   *   when a large import gets queued for bacgrkound importing. This page
   *   should show progress information for a user's background imports.
   * * uploadFileUrl - path to a script that handles the initial upload of a
   *   file to the interim file location. The script can call
   *   import_helper_2::uploadFile for a complete implementation.
   * * sendFileToWarehouseUrl - path to a script that forwards the import file
   *   from the interim location to the warehouse.  The script can call
   *   import_helper_2::sendFileToWarehouse for a complete implementation.
   * * initServerConfigUrl - path to a script that initialises the JSON config
   *   file for the import on the warehouse.
   * * loadChunkToTempTableUrl - path to a script that triggers the load of the
   *   next chunk of records into a temp table on the warehouse. The script can
   *   call import_helper_2::loadChunkToTempTable for a complete implementation.
   * * preprocessUrl - path to a script that performs processing
   *   that can be done after the file is loaded and mappings done.
   * * processLookupMatchingUrl - path to a script that performs steps in the
   *   process of identifying lookup destination fields that need their values
   *   to be matched to obtain an ID.
   * * saveLookupMatchesGroupUrl - path to a script that saves a set of
   *   matching data value/matched termlist term ID pairs for a lookup custom
   *   attribute.
   * * importChunkUrl - path to a script that imports the next chunk of records
   * * getErrorFileUrl - location of the end-point that fetches the errors data
   *   file.
   * * readAuth - read authorisation tokens.
   * * writeAuth - write authorisation tokens.
   * * fixedValues - array of fixed key/value pairs that apply to all rows. Can
   *   also be used to filter the available lookups, by specifying a key/value
   *   pair where the key is of form
   *   `<table>:fkFilter:<lookupTable>:<lookupTableFieldToFilter>` and the
   *   provided value will be applied as a filter. For example, to limit the
   *   available taxon lists when importing into the locations to list ID 1,
   *   use `occurrence:fkFilter:taxa_taxon_list:taxon_list_id=1`. To filter the
   *   location types looked up against when importing samples that link to
   *   locations, specify `sample:fkFilter:location:location_type_id=n` where
   *   n is the location type to filter to.
   * * fixedValueDefaults - default values for fixedValues that present a list
   *   of options to the user.
   * * allowUpdates - set to true to enable updating existing rows based on an
   *   ID or external key field mapping. Only affects the user's own data.
   * * allowDeletes = set to true to enable mapping to a deleted flag for the
   *   user's own data. Requires the allowUpdates option to be set.
   * * importReverse - Form mode with respect to allowing import reversal. If
   *   reversal is allowed, the previous import can either be selected via a
   *   control on the form or by passing a URL parameter called
   *   "reverse_import_guid". Options are:
   *     * import - only importing new files allowed
   *     * import_and_reverse - allow either importing new files or reversal of
   *       previous imports allowed.
   *     * import_and_reverse_via_query - as import_and_reverse, but the
   *       control for selecting an import to reverse is not shown and reversal
   *       is triggered by passing a URL parameter called
   *       "reverse_import_guid".
   *     * reverse - only reversal of previous imports allowed.
   */
  public static function importer($options) {
    if (empty($options['entity'])) {
      throw new exception('The import_helper_2::importer control needs an entity option.');
    }
    if (empty($options['readAuth'])) {
      throw new exception('The import_helper_2::importer control needs a readAuth option.');
    }
    if (empty($options['writeAuth'])) {
      throw new exception('The import_helper_2::importer control needs a writeAuth option.');
    }
    if (empty(hostsite_get_user_field('indicia_user_id'))) {
      throw new exception('The import_helper_2::importer control requires the Easy Login module with an account that has been linked to the warehouse.');
    }
    self::add_resource('uploader');
    self::add_resource('font_awesome');
    self::add_resource('fancybox');
    self::getImportHelperOptions($options);
    self::$indiciaData['uploadFileUrl'] = $options['uploadFileUrl'];
    self::$indiciaData['sendFileToWarehouseUrl'] = $options['sendFileToWarehouseUrl'];
    self::$indiciaData['extractFileOnWarehouseUrl'] = $options['extractFileOnWarehouseUrl'];
    self::$indiciaData['initServerConfigUrl'] = $options['initServerConfigUrl'];
    self::$indiciaData['loadChunkToTempTableUrl'] = $options['loadChunkToTempTableUrl'];
    self::$indiciaData['preprocessUrl'] = $options['preprocessUrl'];
    self::$indiciaData['processLookupMatchingUrl'] = $options['processLookupMatchingUrl'];
    self::$indiciaData['saveLookupMatchesGroupUrl'] = $options['saveLookupMatchesGroupUrl'];
    self::$indiciaData['importChunkUrl'] = $options['importChunkUrl'];
    self::$indiciaData['getErrorFileUrl'] = $options['getErrorFileUrl'];
    self::$indiciaData['write'] = $options['writeAuth'];
    self::$indiciaData['advancedFields'] = $options['advancedFields'];
    $nextImportStep = $_POST['next-import-step'] ?? (empty($_GET['reverse_import_guid']) ? 'fileSelectForm' : 'reversalModeWarningOrSkipToResult');
    self::$indiciaData['step'] = $nextImportStep;
    if ($options['advancedMode']) {
      // Unblock some fields in advanced mode.
      $options['blockedFields'] = array_diff($options['blockedFields'], [
        'created_by_id',
        'fk_created_by',
        'fk_verified_by',
        'verified_on',
      ]);
    }
    switch ($nextImportStep) {
      case 'fileSelectForm':
        $r = self::fileSelectForm($options);
        // The reverser currently assumes occurrence entity.
        if ($options['entity'] === 'occurrence' && in_array($options['importReverse'], ['import_and_reverse', 'reverse'])) {
          $r .= self::importToReverseDropDown($options);
        }
        return $r;

      case 'globalValuesForm':
        return self::globalValuesForm($options);

      case 'mappingsForm':
        return self::mappingsForm($options);

      case 'lookupMatchingForm':
        return self::lookupMatchingForm($options);

      case 'preprocessPage':
        return self::preprocessPage($options);

      case 'summaryPage':
        return self::summaryPage($options);

      case 'doImportPage':
        return self::doImportPage($options);

      case 'reversalModeWarningOrSkipToResult':
        return self::reversalModeWarningOrSkipToResult($options);

      case 'reversalResult':
        return self::reversalResult($options);

      default:
        throw new exception('Invalid next-import-step parameter');
    }
  }

  /**
   * Checks the file type of the uploaded file.
   *
   * @param array $file
   *   The uploaded file array as provided by PHP.
   *
   * @throws RuntimeException
   *   If the file type is not allowed.
   */
  private static function checkImportFileType(array $file) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    // Only allow spreadsheet or CSV files.
    $allowedMimeTypes = [
      'text/csv',
      'application/vnd.ms-excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'application/zip',
      'application/x-zip-compressed',
      'multipart/x-zip',
      // Some browsers use this for .xlsx/.xls.
      'application/octet-stream',
      // Sometimes CSVs are uploaded as text/plain.
      'text/plain',
    ];
    if (!in_array($mimeType, $allowedMimeTypes)) {
      throw new RuntimeException('Invalid file type. Only CSV, Excel, or Zip files are allowed.');
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['csv', 'xls', 'xlsx', 'zip'];
    if (!in_array($ext, $allowedExtensions)) {
      throw new RuntimeException('Invalid file type. Only CSV, Excel, or Zip files are allowed.');
    }
  }

  /**
   * Upload interim file AJAX handler.
   *
   * Provides functionality that can receive an selected file and saves it to
   * the interim location on the client website's server. Suitable for calling
   * from a web-service endpoint that can be called from JS (see `importer_2`'s
   * `ajax_upload_file` method for an example.)
   *
   * @return string
   *   The file name of the saved file.
   */
  public static function uploadInterimFile() {
    if (!isset($_FILES['file']['error']) || is_array($_FILES['file']['error'])) {
      throw new RuntimeException('Invalid parameters.');
    }
    switch ($_FILES['file']['error']) {
      case UPLOAD_ERR_OK:
        break;

      case UPLOAD_ERR_NO_FILE:
        throw new RuntimeException('No file sent.');

      case UPLOAD_ERR_INI_SIZE:
      case UPLOAD_ERR_FORM_SIZE:
        throw new RuntimeException('Exceeded filesize limit.');

      default:
        throw new RuntimeException('Unknown errors.');
    }
    self::checkImportFileType($_FILES['file']);
    $targetDir = self::getInterimImageFolder('fullpath');
    $fileName = sprintf('%s_%s', uniqid(), $_FILES['file']['name']);
    $filePath = $targetDir . $fileName;
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
      throw new RuntimeException('Failed to move uploaded file.');
    }

    // All good.
    return $fileName;
  }

  /**
   * Sends a file from the interim location to the warehouse.
   *
   * Files are initially uploaded to the Drupal server's files dir, this
   * method sends the file to the warehouse's import folder.
   *
   * @param string $fileName
   *   Name of the file to transfer.
   * @param array $writeAuth
   *   Write authorisation tokens.
   */
  public static function sendFileToWarehouse($fileName, array $writeAuth) {
    return self::send_file_to_warehouse($fileName, TRUE, $writeAuth, 'import_2/upload_file', TRUE);
  }

  /**
   * Where an import file was zipped, requests unzipping at the warehouse end.
   *
   * @param string $fileName
   *   Name of the file.
   * @param array $writeAuth
   *   Write authorisation tokens.
   *
   * @return array
   *   Web-service response containing a data-file property with the name of
   *   the extracted file.
   */
  public static function extractFileOnWarehouse($fileName, array $writeAuth) {
    $serviceUrl = self ::$base_url . 'index.php/services/import_2/extract_file';
    $data = $writeAuth + ['uploaded-file' => $fileName, 'persist_auth' => TRUE];
    $response = self::http_post($serviceUrl, $data, FALSE);
    $output = json_decode($response['output'], TRUE);
    if (!$response['result']) {
      throw new exception($output['msg'] ?? $response['output']);
    }
    return $output;
  }

  /**
   * Sets up the config JSON file on the server.
   *
   * @param array $files
   *   List of file names being imported.
   * @param int $importTemplateId
   *   Template ID if one was selected.
   * @param array $writeAuth
   *   Write authorisation tokens.
   * @param array $plugins
   *   List of enabled plugins as keys, with parameter arrays as values.
   * @param array $options
   *   Additional options for the init_server_config API call. Options include:
   *   * enable-background-imports - True if background importing of large
   *     files is enabled.
   *   * support-dna - True if DNA-derived occurrence fields are to be
   *     supported.
   *
   * @return array
   *   Output of the web service request.
   */
  public static function initServerConfig(array $files, $importTemplateId, array $writeAuth, array $plugins = [], array $options = []) {
    $serviceUrl = self ::$base_url . 'index.php/services/import_2/init_server_config';
    $data = $writeAuth + [
      'data-files' => json_encode($files),
      'import_template_id' => $importTemplateId,
      'plugins' => json_encode($plugins),
      'enable-background-imports' => $options['enable-background-imports'] ?? 'f',
      'support-dna' => $options['support-dna'] ?? 'f',
    ];
    $response = self::http_post($serviceUrl, $data, FALSE);
    hostsite_show_message(var_export($data, TRUE));
    $output = json_decode($response['output'], TRUE);
    if (!$response['result']) {
      \Drupal::logger('iform')->notice('Error in initServerConfig: ' . var_export($response, TRUE));
      throw new exception($output['msg'] ?? $response['output'], $response['status'] ?? 0);
    }
    return $output;
  }

  /**
   * Triggers the load of a chunk of records from the file to the temp table.
   *
   * @param string $file
   *   Name of the file to transfer.
   * @param string $configId
   *   Configuration ID which holds the import settings.
   * @param array $writeAuth
   *   Write authorisation tokens.
   */
  public static function loadChunkToTempTable($file, $configId, array $writeAuth) {
    $serviceUrl = self ::$base_url . 'index.php/services/import_2/load_chunk_to_temp_table';
    $data = $writeAuth + [
      'data-file' => $file,
      'config-id' => $configId,
    ];
    $response = self::http_post($serviceUrl, $data, FALSE);
    $output = json_decode($response['output'], TRUE);
    if (!$response['result']) {
      \Drupal::logger('iform')->notice('Error in loadChunkToTempTable: ' . var_export($response, TRUE));
    }
    return $output;
  }

  /**
   * Actions the next step in the process of linking data values to lookup IDs.
   *
   * @param string $configId
   *   ID of the configuration for this import.
   * @param int $index
   *   Index of the request - start at 0 then increment by one for each request
   *   to fetch each lookup that needs matching in turn.
   * @param array $writeAuth
   *   Write authorisation tokens.
   */
  public static function processLookupMatching($configId, $index, array $writeAuth) {
    $serviceUrl = self ::$base_url . 'index.php/services/import_2/process_lookup_matching';
    $data = $writeAuth + [
      'config-id' => $configId,
      'index' => $index,
    ];
    $response = self::http_post($serviceUrl, $data, FALSE);
    $output = json_decode($response['output'], TRUE);
    if (!$response['result']) {
      \Drupal::logger('iform')->notice('Error in processLookupMatching: ' . var_export($response, TRUE));
    }
    return $output;
  }

  /**
   * Saves the manually matched value / term ID pairings for a custom attr.
   *
   * @param string $configId
   *   ID of the current import's config.
   * @param array $matchesInfo
   *   Matching data.
   * @param array $writeAuth
   *   Write authorisation tokens.
   */
  public static function saveLookupMatchesGroup($configId, array $matchesInfo, array $writeAuth) {
    $serviceUrl = self ::$base_url . 'index.php/services/import_2/save_lookup_matches_group';
    $data = $writeAuth + [
      'config-id' => $configId,
      'matches-info' => json_encode($matchesInfo),
    ];
    $response = self::http_post($serviceUrl, $data, FALSE);
    $output = json_decode($response['output'], TRUE);
    if (!$response['result']) {
      \Drupal::logger('iform')->notice('Error in saveLookupMatchesGroup: ' . var_export($response, TRUE));
      throw new exception($output['msg'] ?? $response['output']);
    }
    return $output;
  }

  /**
   * Perform processing that can be done before the actual import.
   *
   * Includes global validation and linking to existing records.
   *
   * @param string $configId
   *   ID of the config of this import.
   * @param int $index
   *   Index of the request - start at 0 then increment by one for each request
   *   to perform each processing step in turn.
   * @param array $writeAuth
   *   Write authorisation tokens.
   */
  public static function preprocess($configId, $index, array $writeAuth) {
    $serviceUrl = self ::$base_url . 'index.php/services/import_2/preprocess';
    $data = $writeAuth + [
      'config-id' => $configId,
      'index' => $index,
    ];
    $response = self::http_post($serviceUrl, $data, FALSE);
    $output = json_decode($response['output'], TRUE);
    if (!$response['result']) {
      \Drupal::logger('iform')->notice('Error in preprocess: ' . var_export($response, TRUE));
    }
    return $output;
  }

  /**
   * Import the next chunk of records to the main database.
   *
   * @param string $configId
   *   ID of the config of the current import.
   * @param array $params
   *   List of options passed to the import chunk AJAX proxy. Includes:
   *   * description - Description if saving the import metadata for the first
   *     time.
   *   * training - training mode flag if saving the import metadata for the
   *     first time.
   *   * importTemplateTitle - Title if saving the import configuration as a
   *     template for future use.
   *   * forceTemplateOverwrite - Set to true if the template title provided
   *     can be used to overwrite an existing one of the same name for this
   *     user.
   *   * precheck - precheck, which retrieves validation errors without
   *     importing.
   *   * restart - forces the warehouse to start from the first row in the
   *     import.
   * @param array $writeAuth
   *   Write authorisation tokens.
   */
  public static function importChunk($configId, array $params, array $writeAuth) {
    $params = array_merge([
      'description' => NULL,
      'training' => FALSE,
      'importTemplateTitle' => NULL,
      'forceTemplateOverwrite' => FALSE,
      'precheck' => FALSE,
      'restart' => FALSE,
    ], $params);

    $serviceUrl = self ::$base_url . 'index.php/services/import_2/import_chunk';
    $data = $writeAuth + [
      'config-id' => $configId,
    ];
    if (!empty(trim($params['description'] ?? ''))) {
      $data['save-import-record'] = json_encode([
        'description' => trim($params['description']),
        'training' => $params['training'] === 'true' || $params['training'] === TRUE,
      ]);
    }
    if (!empty(trim($params['importTemplateTitle'] ?? ''))) {
      $data['save-import-template'] = json_encode([
        'title' => trim($params['importTemplateTitle']),
        'forceTemplateOverwrite' => $params['forceTemplateOverwrite'],
      ]);
    }
    if ($params['precheck']) {
      $data['precheck'] = 't';
    }
    if ($params['restart']) {
      $data['restart'] = 't';
    }
    $response = self::http_post($serviceUrl, $data, FALSE);
    $output = json_decode($response['output'], TRUE);
    if (!$response['result']) {
      if (isset($response['status']) && $response['status'] === 409 && $output['msg'] === 'An import template with that title already exists') {
        // Duplicate conflict in import template title.
        return [
          'status' => 'conflict',
          'msg' => $output['msg'],
          'title' => trim($params['importTemplateTitle'] ?? ''),
        ];
      }
      else {
        \Drupal::logger('iform')->notice('Error in importChunk: ' . var_export($response, TRUE));
        throw new exception($output['msg'] ?? $response['output']);
      }
    }
    return $output;
  }

  /**
   * Returns the maximum files size that can be uploaded.
   *
   * @return int
   *   File size in bytes.
   */
  private static function getMaximumFileUploadSize() {
    return min(
      self::convertPHPSizeToBytes(ini_get('post_max_size')),
      self::convertPHPSizeToBytes(ini_get('upload_max_filesize'))
    );
  }

  /**
   * Transform php.ini notation for numbers (like '2M') to an integer.
   *
   * @param string $size
   *   Size string as read from PHP ini file, e.g. '2M'.
   *
   * @return int
   *   The value in bytes.
   */
  private static function convertPhpSizeToBytes($size) {
    $suffix = strtoupper(substr($size, -1));
    if (!in_array($suffix, ['P', 'T', 'G', 'M', 'K'])) {
      return (int) $size;
    }
    $iValue = substr($size, 0, -1);
    switch ($suffix) {
      case 'P':
        $iValue *= 1024;
        // Fallthrough intended.
      case 'T':
        $iValue *= 1024;
        // Fallthrough intended.
      case 'G':
        $iValue *= 1024;
        // Fallthrough intended.
      case 'M':
        $iValue *= 1024;
        // Fallthrough intended.
      case 'K':
        $iValue *= 1024;
    }
    return (int) $iValue;
  }

  /**
   * Fetch the HTML for the file select form.
   *
   * @param array $options
   *   Options array for the control.
   *
   * @return string
   *   HTML.
   */
  private static function fileSelectForm(array $options) {
    self::addLanguageStringsToJs('import_helper_2', [
      'invalidType' => 'The chosen file was not a type of file that can be imported.',
      'removeUploadedFileHint' => 'Remove the uploaded file',
      'uploadFailedWithError' => 'The file upload failed. The error message was:<br/>{1}.',
    ]);
    $lang = [
      'browseFiles' => lang::get('Browse files'),
      'clickToAdd' => lang::get('Click to add files'),
      'instructions' => lang::get($options['fileSelectFormIntro']),
      'instructionsSelectTemplate' => lang::get('Choose one of the templates you saved previously if repeating a similar import.'),
      'maxFileSize' => lang::get('The maximum size of an individual file you can upload is {1}.', IndiciaConversions::bytesToReadable(self::getMaximumFileUploadSize())),
      'next' => lang::get('Next step'),
      'selectAFile' => lang::get('Select a CSV or Excel file or drag it over this area. The file can optionally be a zip archive. If importing an Excel file, only the first worksheet will be imported.'),
      'uploadFileToImport' => lang::get('Upload a file to import'),
    ];
    $templates = self::loadTemplates($options);
    $templatePickerHtml = '';
    if (count($templates)) {
      $templateOptions = [];
      foreach ($templates as $template) {
        $templateOptions[$template['id']] = $template['title'];
      }
      $templatePickerHtml = data_entry_helper::select([
        'fieldname' => 'import_template_id',
        'label' => lang::get('Template'),
        'helpText' => lang::get('If you would like to import similar data to a previous import, choose one of the templates you saved previously to re-use the settings'),
        'lookupValues' => $templateOptions,
        'blankText' => lang::get('-no template selected-'),
      ]);
    }
    $r = <<<HTML
      <h3>$lang[uploadFileToImport]</h3>
      <p>$lang[instructions]</p>
      <form id="file-upload-form" method="POST">
        <div class="dm-uploader row">
          <div class="col-md-9">
            <div role="button" class="btn btn-primary">
              <i class="fas fa-file-upload"></i>
              $lang[browseFiles]
              <input type="file" title="$lang[clickToAdd]">
            </div>
            <small class="status text-muted">$lang[selectAFile]</small>
            <small class="status text-muted">$lang[maxFileSize]</small>
          </div>
          <div class="col-md-3" id="uploaded-files"></div>
        </div>
        $templatePickerHtml
        <progress id="file-progress" class="progress" value="0" max="100" style="display: none"></progress>
        <input type="submit" class="btn btn-primary" id="next-step" value="$lang[next]" disabled />
        <input type="hidden" name="next-import-step" value="globalValuesForm" />
      </form>
    HTML;
    self::$indiciaData['importerDropArea'] = '.dm-uploader';
    return $r;
  }

  /**
   * Fetch the HTML for the import reversal drop-down.
   *
   * @param array $options
   *   Options array for the control.
   *
   * @return string
   *   HTML.
   */
  private static function importToReverseDropDown(array $options) {
    iform_load_helpers(['report_helper']);
    if (!function_exists('hostsite_get_user_field') || !hostsite_get_user_field('indicia_user_id')) {
      return '';
    }
    // We only show user their own imports.
    $indiciaUserID = hostsite_get_user_field('indicia_user_id');
    $extraParams = [
      'currentUser' => $indiciaUserID,
    ];
    // This will be empty if importer is running on Warehouse.
    // In that case all imports for the logged-in user will be shown.
    if (!empty($options['website_id'])) {
      $extraParams = array_merge(
        $extraParams, ['website_id' => $options['website_id']]
      );
    }
    // Get a list of imports that are reversible.
    $lookupData = report_helper::get_report_data([
      'readAuth' => $options['readAuth'],
      'dataSource' => 'library/imports/reversible_imports_list',
      'extraParams' => $extraParams,
    ]);
    self::addLanguageStringsToJs('import_helper_2', [
      'are_you_sure_reverse' => 'Are you sure you want to reverse the selected import?',
    ]);
    $lang = [
      'select_a_previous_import_to_reverse' => lang::get('Or select a previous import to reverse'),
      'no_previous_imports_available_for_you_to_reverse' => lang::get('There are no previous imports available for you to reverse'),
      'imports_are_not_always_reversible' => lang::get('Please note that imports are not always reversible.'),
      'non_reversible_import_reasons' => lang::get('This may include old imports, imports that have been updated by another import,
                or imports where a reversal has already been attempted.'),
      'reverse_import' => lang::get('Reverse import'),
      'new_records_will_be_reversed' => lang::get('Only new records created by the original import will be reversed.'),
      'updated_records_will_not_be_reversed' => lang::get('Any records that were selected for an update or deletion during that import will not be reversed.'),
    ];
    // Construct label for the drop-down from the date/time and import_guid.
    $reversableImports = [];
    foreach ($lookupData as $importRow) {
      $affected = $importRow['inserted'] + $importRow['updated'];
      $reversableImports[$importRow['import_guid']] = lang::get('{1}, {2} rows (Import ID: {3}', $importRow['import_date_time'], $affected, $importRow['import_guid']);
    }
    $r = <<<HTML
      <hr>
      <p>
        $lang[select_a_previous_import_to_reverse]
      </p>
    HTML;
    if (empty($reversableImports)) {
      $r .= <<<HTML
      <div>
        <em>
          $lang[no_previous_imports_available_for_you_to_reverse]
        </em>
      </div>
      HTML;
    }
    // If there are some reversible imports, show user a drop-down of imports
    // and a run the reverse button.
    else {
      $r .= <<<HTML
      <form id="reverse-import-form" method="POST">
      HTML;
      $r .= data_entry_helper::select([
        'id' => 'reverse-guid',
        'fieldname' => 'reverse-guid',
        'label' => lang::get('Import to reverse'),
        'lookupValues' => $reversableImports,
        'blankText' => lang::get('<please select>'),
      ]);
      $r .= <<<HTML
        <p>
          <small>
            <em>
              $lang[imports_are_not_always_reversible]
            <br>
              $lang[non_reversible_import_reasons]
            </em>
          </small>
        </p>
        <input type="submit" class="btn btn-primary" id="run-reverse" value="$lang[reverse_import]" disabled />
        <p style="display:none;" class="reverse-instructions-1">
          <strong>
            $lang[new_records_will_be_reversed]
          <br>
            $lang[updated_records_will_not_be_reversed]
          </strong>
        </p>
        <input type="hidden" name="next-import-step" value="reversalModeWarningOrSkipToResult" />
      </form>
      HTML;
    }
    return $r;
  }

  /**
   * Allow user to select import reverse options.
   *
   * If applicable, allow the user to select whether to reverse all data,
   * or just data that has not been changed since importing.
   * Skips directly to result page if there is nothing to ask the user.
   *
   * @param array $options
   *   Options array for the control.
   *
   * @return string
   *   HTML.
   */
  private static function reversalModeWarningOrSkipToResult(array $options) {
    $reverseGuid = $_POST['reverse-guid'] ?? $_GET['reverse_import_guid'];
    if (empty($reverseGuid)) {
      throw new Exception('Reversal GUID not found.');
    }
    iform_load_helpers(['report_helper']);
    self::addLanguageStringsToJs('import_helper_2', [
      'abort' => 'Abort',
      'continue' => 'Continue',
      'are_you_sure_reverse' => 'Are you sure you wish to continue?',
    ]);
    $lang = [
      'abort_the_reverse' => lang::get('Abort the reverse'),
      'cancel' => lang::get('Cancel'),
      'changes_have_been_made' => lang::get('Changes have been made to the data since it was imported.'),
      'confirm_reverse' => 'Are you sure you want to reverse the following import?',
      'continue' => lang::get('Continue'),
      'how_do_you_wish_to_proceed' => lang::get('How do you wish to proceed?'),
      'reverse_all_rows' => lang::get('Reverse all rows'),
      'reverse_unchanged_rows' => lang::get('Reverse unchanged rows'),
    ];
    $extraParams = [
      'import_guid' => $reverseGuid,
    ];
    // Has any of the data been changed since the import was done.
    $smpsChangedSinceImport = report_helper::get_report_data([
      'dataSource' => 'library/imports/changed_smps_since_import',
      'readAuth' => $options['readAuth'],
      'extraParams' => $extraParams,
    ]);
    $occsChangedSinceImport = report_helper::get_report_data([
      'dataSource' => 'library/imports/changed_occs_since_import',
      'readAuth' => $options['readAuth'],
      'extraParams' => $extraParams,
    ]);
    $imports = report_helper::get_report_data([
      'dataSource' => 'library/imports/occurrence_imports_list',
      'readAuth' => $options['readAuth'],
      'extraParams' => $extraParams + ['currentUser' => hostsite_get_user_field('indicia_user_id')],
    ]);
    if (count($imports) !== 1) {
      throw new exception('Failed to find unique import using provided GUID.');
    }
    $importToReverse = $imports[0];
    $recordDetails = lang::get('{1} (IDs between {2} and {3})', $importToReverse['records'], $importToReverse['from_id'], $importToReverse['to_id']);
    $importDetails = <<<HTML
      <dl class="dl-horizontal">
        <dt>Import ID:</dt>
        <dd>$importToReverse[import_guid]<dd>
        <dt>Date/time:</dt>
        <dd>$importToReverse[date_time]<dd>
        <dt>By:</dt>
        <dd>$importToReverse[imported_by]<dd>
        <dt>Records:</dt>
        <dd>$recordDetails<dd>
      </dl>
    HTML;
    if (empty($smpsChangedSinceImport) && empty($occsChangedSinceImport)) {
      // If the reversal was triggered via selecting a GUID in the UI and no
      // changes have been made to the imported data, then we can skip
      // straight to the result page.
      if (empty($_GET['reverse_import_guid'])) {
        return self::reversalResult($options);
      }
      // If we get here, then the user followed a link containing the GUID to
      // reverse in the URL, so won't have had a chance to confirm the action.
      // Therefore show a confirmation form. We can add a button for cancel if
      // we know where we were referred from.
      $cancelButton = empty($_SERVER['HTTP_REFERER']) ? '' : "<a class=\"btn btn-primary\" href=\"$_SERVER[HTTP_REFERER]\">$lang[cancel]</a>";
      return <<<HTML
        <form id="reversal-mode-warning-form" method="POST">
          <p>$lang[confirm_reverse]</p>
          $importDetails
          <input type="submit" class="btn btn-primary" id="run-reverse" value="$lang[continue]" />
          $cancelButton
          <input type="hidden" name="next-import-step" value="reversalResult" />
          <input type="hidden" name="reverse-guid" value="$reverseGuid" />
        </form>
      HTML;
    }
    // If import data has been changed since import, then give the user
    // the following options.
    // Reverse all data, reverse only unchanged data, or abort reverse.
    $r = <<<HTML
    <form id="reversal-mode-warning-form" method="POST">
      <h3>
        $lang[changes_have_been_made]
      </h3>
      $importDetails
      <p>
        $lang[how_do_you_wish_to_proceed]
      </p>
      <div>
        <p>
          <label for="reverse_all" style="display: inline-flex; align-items: center;">
          <input style="margin: 0 0.5em 0;" type="radio" id="reverse_all" name="reverse-mode" value="reverse_all">
          <em>
            $lang[reverse_all_rows]
          </em>
          </label>
        </p>
        <p>
          <label for="do_not_reverse_updated" style="display: inline-flex; align-items: center;">
            <input style="margin: 0 0.5em 0;" type="radio" id="do_not_reverse_updated" name="reverse-mode" value="do_not_reverse_updated">
            <em>
              $lang[reverse_unchanged_rows]
            </em>
          </label>
        </p>
        <p>
          <label for="abort_reverse" style="display: inline-flex; align-items: center;">
            <input style="margin: 0 0.5em 0;" type="radio" id="abort_reverse" name="reverse-mode" value="abort_reverse">
            <em>
              $lang[abort_the_reverse]
            </em>
          </label>
        </p>
        <br>
        <input type="submit" class="btn btn-primary" id="run-reverse" value="$lang[continue]" />
        <input type="hidden" name="next-import-step" value="reversalResult" />
        <input type="hidden" name="reverse-guid" value="$reverseGuid" />
      </div>
    </form>
    HTML;
    return $r;
  }

  /**
   * Send reversal information to the Warehouse, and display result.
   *
   * @param array $options
   *   Options array for the control.
   *
   * @return string
   *   HTML.
   */
  private static function reversalResult(array $options) {
    if (empty($_POST['reverse-guid'])) {
      throw new exception('POST should contain a value for reverse-guid.');
    }
    $indiciaUserID = hostsite_get_user_field('indicia_user_id');
    $data['warehouse_user_id'] = $indiciaUserID;
    $serviceUrl = self ::$base_url . 'index.php/services/import_2/import_reverse';
    $data['guid_to_reverse'] = $_POST['reverse-guid'];
    if (!empty($_POST['reverse-mode'])) {
      $data['reverse_mode'] = $_POST['reverse-mode'];
    }
    $response = self::http_post($serviceUrl, $data, FALSE);
    $output = json_decode($response['output'], TRUE);
    $r = <<<HTML
      <form id="reversal-result-form" method="POST">
    HTML;
    global $indicia_templates;
    if (!isset($response['result']) || $output['status'] !== 'OK') {
      $printedResponse = empty($output['msg']) ? print_r($response, TRUE) : $output['msg'];
      $r .= str_replace('{message}', 'An error has occurred during the import reversal.', $indicia_templates['warningBox']);
      $r .= <<<HTML
        <p>
          <em>The response from the database is:</em>
        </p>
        <pre>$printedResponse</pre>
      HTML;
    }
    else {
      // Result includes links to files which contain changed and unchanged
      // sample/occurrence rows.
      $samplesDetails = '<p>' . implode('</p><p>', $output['samplesDetails']);
      $occurrencesDetails = '<p>' . implode('</p><p>', $output['occurrencesDetails']);
      $r .= <<<HTML
        <h3>The reversal of import {$_POST['reverse-guid']} is complete</h3>
        <h4>$output[samplesOutcome]</h4>
        $samplesDetails
        <h4>$output[occurrencesOutcome]</h4>
        $occurrencesDetails
      HTML;
    }
    $r .= <<<HTML
        <br>
        <div>
          <input type="submit" class="btn btn-primary" id="return-to-start" value="Return to start" />
          <input type="hidden" name="next-import-step" value="fileSelectForm" />
        </div>
      </form>
    HTML;
    return $r;
  }

  /**
   * Fetch the HTML form for the settings form that captures global values.
   *
   * Global values are imported data values that apply to every record in the
   * impore.
   *
   * @param array $options
   *   Options array for the control.
   *
   * @return string
   *   HTML.
   */
  private static function globalValuesForm(array $options) {
    self::addLanguageStringsToJs('import_helper_2', [
      'backgroundProcessingDone' => 'Background processing done',
      'extractingFile' => 'Extracting the data from the Zip file.',
      'errorExtractingZip' => 'An error occurred on the server whilst extracting the Zip file',
      'errorUploadingFile' => 'An error occurred on the server whilst uploading the file',
      'extractedFile' => 'Unzipped file {1}.',
      'loadingRecords' => 'Loading records into temporary processing area.',
      'loaded' => 'Records loaded ready for matching.',
      'mixedFileTypesError' => 'The list of files you have uploaded contains different file types. Please upload either a set of zip files or a set of data files (csv, xls, xlsx) of the same type.',
      'preparingToLoadRecords' => 'Preparing to load records.',
      'uploadedFile' => 'Uploaded file {1}.',
      'uploadError' => 'Upload error',
      'uploadingFile' => 'Uploading the file {1} to the server.',
    ]);
    $lang = [
      'backgroundProcessing' => lang::get('Background processing in progress...'),
      'instructions' => lang::get($options['globalValuesFormIntro']),
      'moreInfo' => lang::get('More info...'),
      'next' => lang::get('Next step'),
      'setting' => lang::get('Setting'),
      'settingsFromTemplate' => lang::get('The following settings required for this page are being loaded from the selected template.'),
      'title' => lang::get('Import settings'),
      'value' => lang::get('Value'),
    ];
    $template = self::loadSelectedTemplate($options);
    if (!empty($template) && !empty($template['global_values'])) {
      // Merge the template global values into the configuration's fixed
      // values.
      $globalValuesFromTemplate = json_decode($template['global_values'], TRUE);
      $options['fixedValues'] = array_merge(
        $options['fixedValues'],
        $globalValuesFromTemplate
      );
    }
    // Find the controls that we can accept global values for, depending on the
    // entity we are importing into.
    $formArray = self::getGlobalValuesFormControlArray($options);
    $form = self::globalValuesFormControls($formArray, $options);
    self::$indiciaData['processUploadedInterimFiles'] = $_POST['interim-file'];
    return <<<HTML
      <h3>$lang[title]</h3>
      <p>$lang[instructions]</p>
      <form id="settings-form" method="POST">
        $form
        <div class="panel panel-info background-processing">
          <div class="panel-heading">
            <span>$lang[backgroundProcessing]</span>
            <progress id="file-progress" class="progress" value="0" max="100"></progress>
            <br/><a data-toggle="collapse" class="small" href="#background-extra">$lang[moreInfo]</a>
          </div>
          <div id="background-extra" class="panel-body panel-collapse collapse"></div>
        </div>
        <input type="submit" class="btn btn-primary" id="next-step" value="$lang[next]" disabled />
        <input type="hidden" name="next-import-step" value="mappingsForm" />
        <input type="hidden" name="config-id" id="config-id" value="" />
      </form>
    HTML;
  }

  /**
   * Retreives the array of control definitions for the global values form.
   *
   * Fetches the information from the warehouse model.
   *
   * @param array $options
   *   Importer options array.
   *
   * @return array
   *   List of control info.
   */
  private static function getGlobalValuesFormControlArray($options) {
    $response = self::cacheGet(['entityImportSettings' => $options['entity']]);
    if ($response === FALSE) {
      $request = parent::$base_url . "index.php/services/import_2/get_globalvalues_form/" . $options['entity'];
      $request .= '?' . self::array_to_query_string($options['readAuth'] + [
        'activate_global_sample_method' => 't',
      ]);
      $response = self::http_post($request, []);
      if (!isset($response['error'])) {
        self::cacheSet(['entityImportSettings' => $options['entity']], json_encode($response));
      }
    }
    else {
      $response = json_decode($response, TRUE);
    }
    return !empty($response['output']) ? json_decode($response['output'], TRUE) : [];
  }

  /**
   * Returns a list of controls for the global values form.
   *
   * Controls in the list will depend on the entity settings on the warehouse.
   */
  private static function globalValuesFormControls($formArray, $options) {
    $r = '';
    $options['helpText'] = TRUE;
    $options['form'] = $formArray;
    $options['param_lookup_extras'] = [];
    $visibleControlsFound = FALSE;
    $tools = [];
    global $indicia_templates;
    foreach ($formArray as $key => $info) {
      // @todo Description should really have i18n inside getParamsFormControl,
      // though this would require changing how the show unrestricted button UI
      // is done.
      $info['description'] = lang::get($info['description']);
      $unrestrictedControl = NULL;
      if (isset($options['fixedValueDefaults'][$key])) {
        $info['default'] = $options['fixedValueDefaults'][$key];
      }
      if (!empty($options['fixedValues'][$key])) {
        $optionList = explode(';', $options['fixedValues'][$key]);
        // * indicates user needs option to select from full list.
        if (in_array('*', $optionList)) {
          unset($optionList[array_search('*', $optionList)]);
          $origDisplayLabel = $info['display'];
          $origDescription = $info['description'];
          $info['display'] .= ' (' . lang::get('unrestricted') . ')';
          $info['description'] .= ' ' . lang::get('Showing all available options.') . ' ' .
            "<button type=\"button\" class=\"show-restricted $indicia_templates[buttonDefaultClass] $indicia_templates[buttonSmallClass]\">" . lang::get('Show preferred options') . '</button>';
          $unrestrictedControl = self::getParamsFormControl("$key-unrestricted", $info, $options, $tools);
          $info['display'] = $origDisplayLabel;
          $info['description'] = $origDescription . ' ' . lang::get('Currently only showing preferred options.') . ' ' .
            "<button type=\"button\" class=\"show-unrestricted $indicia_templates[buttonDefaultClass] $indicia_templates[buttonSmallClass]\">" . lang::get('Show all options') . '</button>';
        }
        if (isset($info['lookup_values'])) {
          $info['lookup_values'] = self::getRestrictedLookupValues($info['lookup_values'], $optionList);
        }
        elseif (isset($info['population_call'])) {
          $tokens = explode(':', $info['population_call']);
          // 3rd part of population call is the ID field ($tokens[2]).
          if ($tokens[0] === 'direct') {
            $options['param_lookup_extras'][$key] = ['query' => json_encode(['in' => [$tokens[2] => $optionList]])];
          }
          elseif ($tokens[0] === 'report') {
            $options['param_lookup_extras'][$key] = [$tokens[2] => implode(',', $optionList)];
          }
          $options['param_lookup_extras'][$key]['sharing'] = 'editing';
        }
        if (count($optionList) === 1 && !$unrestrictedControl) {
          $r .= data_entry_helper::hidden_text([
            'fieldname' => $key,
            'default' => $optionList[0],
          ]);
          continue;
        }
      }
      $r .= '<div class="restricted ctrl-cntr" >' . self::getParamsFormControl($key, $info, $options, $tools) . '</div>';
      if ($unrestrictedControl) {
        $r .= "<div class=\"unrestricted ctrl-cntr\" style=\"display: none\">$unrestrictedControl</div>";
      }
      $visibleControlsFound = TRUE;
    }
    $updateOrDeleteOptions = self::updateOrDeleteOptions($options);
    $r .= $updateOrDeleteOptions['html'];
    $visibleControlsFound = $visibleControlsFound || $updateOrDeleteOptions['visibleControls'];
    if (!$visibleControlsFound) {
      // All controls had a fixed value provided in config or the loaded
      // template, so show a message instead of the form.
      $r .= '<p class="alert alert-info">' . lang::get('None of the import settings require your input, so click <strong>Next step</strong> when the background processing is complete.') . ' </p>';
    }
    return $r;
  }

  /**
   * Adds controls allowing the user to enable updates or deletes.
   *
   * @param array $options
   *   Configuration options.
   *
   * @return array
   *   Entry containing the control HTML (html) and a boolean flag set to true
   *   if any of the controls are visible, as this affects the UI behaviour.
   */
  private static function updateOrDeleteOptions(array $options) {
    $html = '';
    $visibleControlsAdded = FALSE;
    if (!empty($options['allowUpdates'])) {
      // Allow Updates can be set in the fixed values given on the Edit tab.
      $ctrlType = isset($options['fixedValues']['config:allowUpdates']) ? 'hidden_text' : 'checkbox';
      $html .= data_entry_helper::$ctrlType([
        'fieldname' => 'config:allowUpdates',
        'label' => lang::get('Import file contains updates for existing data'),
        'helpText' => lang::get('Tick this box if your import file contains updates for existing data.'),
        'default' => $options['fixedValues']['config:allowUpdates'] ?? 0,
      ]);
      $visibleControlsAdded = $ctrlType !== 'hidden_text';
      if (!empty($options['allowDeletes'])) {
        // Allow Deletes can be set in the fixed values given on the Edit tab.
        $ctrlType = isset($options['fixedValues']['config:allowDeletes']) ? 'hidden_text' : 'checkbox';
        $html .= data_entry_helper::$ctrlType([
          'fieldname' => 'config:allowDeletes',
          'label' => lang::get('Import file contains a flag for deleting existing data'),
          'helpText' => lang::get('Tick this box if your import file contains a flag for deleting existing data.'),
          'default' => $options['fixedValues']['config:allowDeletes'] ?? 0,
        ]);
        $visibleControlsAdded = $visibleControlsAdded || ($ctrlType !== 'hidden_text');
        if (!isset($options['fixedValues']['config:allowDeletes'])) {
          // If not set by the template, the UI should only enable the deletes
          // control when updates are enabled.
          data_entry_helper::$indiciaData['enableControlIf']['config:allowDeletes'] = ['config:allowUpdates' => ['1']];
        }
      }
    }
    return [
      'html' => $html,
      'visibleControls' => $visibleControlsAdded,
    ];
  }

  /**
   * Processes a lookup_values string to only include those for provided keys.
   *
   * @param string $lookupValues
   *   A lookup values string, in format key:value,key:value.
   * @param array $restrictToKeys
   *   A list of keys to include in the returned lookup values string. Keys can
   *   have the label specified if a colon appended.
   *
   * @return string
   *   Lookup values string which only includes entries whose keys are in the
   *   $restrictToKeys array.
   */
  private static function getRestrictedLookupValues($lookupValues, array $restrictToKeys) {
    $originalLookups = explode(',', $lookupValues);
    $originalLookupsAssoc = [];
    foreach ($originalLookups as $lookup) {
      $lookup = explode(':', $lookup);
      $originalLookupsAssoc[$lookup[0]] = $lookup[1];
    }
    $newLookupList = [];
    foreach ($restrictToKeys as $key) {
      if (strpos($key, ':') === FALSE) {
        $newLookupList[] = "$key:" . $originalLookupsAssoc[$key];
      }
      else {
        $newLookupList[] = $key;
      }
    }
    return implode(',', $newLookupList);
  }

  /**
   * Fetch the HTML form for the form that maps import columns to db fields.
   *
   * @param array $options
   *   Options array for the control.
   *
   * @return string
   *   HTML.
   */
  private static function mappingsForm(array $options) {
    $lang = [
      'columnInImportFile' => lang::get('Column in import file'),
      'destinationDatabaseField' => lang::get('Destination database field'),
      'display' => lang::get('Display'),
      'instructions' => lang::get($options['mappingsFormIntro']),
      'next' => lang::get('Next step'),
      'requiredFields' => lang::get('Required fields'),
      'requiredFieldsInstructions' => lang::get($options['requiredFieldsIntro']),
      'standardFieldsOnly' => lang::get('standard fields'),
      'standardAndAdvancedFields' => lang::get('standard and advanced fields'),
      'title' => lang::get('Map import columns to destination database fields'),
    ];
    self::addLanguageStringsToJs('import_helper_2', [
      'fieldSelectionConflict' => 'You have selected the following fields which conflict, please only select one: {1}',
      'incompleteFieldGroupRequired' => 'In order to complete the group of related fields, please also map the following: {1}',
      'incompleteFieldGroupSelected' => 'You have selected a mapping for the following field(s): {1}',
      'suggestions' => 'Suggestions',
    ]);
    $configId = $_POST['config-id'];
    // Load the config for this import.
    $request = parent::$base_url . "index.php/services/import_2/get_config";
    $request .= '?' . http_build_query($options['readAuth'] + ['config-id' => $configId]);

    $response = self::http_post($request, []);
    $config = json_decode($response['output'], TRUE);
    // Save the results of the previous global values form, which can be merged
    // into the fixed values provided in the form options.
    $globalValues = array_merge($options['fixedValues'], $_POST);
    $globalValues = self::saveFormValuesToConfig($globalValues, $options, 'global-values');
    if (!is_array($config)) {
      throw new Exception('Service call to get_config failed.');
    }
    self::$indiciaData['globalValues'] = $globalValues;
    $availableFields = self::getAvailableDbFields($options, $configId, $globalValues);
    self::hideCoreFieldsIfReplaced($options, $globalValues['survey_id'], $availableFields);
    $requiredFields = self::getAvailableDbFields($options, $configId, $globalValues, TRUE);
    // Only include required fields that are available for selection. Others
    // get populated by some other means.
    $requiredFields = array_intersect_key($requiredFields, $availableFields);
    if (!empty($config['columns'])) {
      // Save column info data for JS to use.
      self::$indiciaData['columns'] = $config['columns'];
    }
    if (!empty($config['importTemplateId'])) {
      // Inform JS if a template is being used, so it disables auto-column
      // name matching.
      self::$indiciaData['import_template_id'] = $config['importTemplateId'];
    }
    $htmlList = [];
    if (isset($globalValues['config:allowUpdates']) && $globalValues['config:allowUpdates'] == '1') {
      unset($options['blockedFields'][array_search('id', $options['blockedFields'])]);
      $options['blockedFields'][] = 'sample:id';
      $requiredFields['occurrence:id|occurrence:external_key'] = 'Occurrence ID or external key';
      if (isset($globalValues['config:allowDeletes']) && $globalValues['config:allowDeletes'] == '1') {
        unset($options['blockedFields'][array_search('deleted', $options['blockedFields'])]);
        $options['blockedFields'][] = 'sample:deleted';
        $requiredFields['occurrence:deleted'] = 'Occurrence deleted';
      }
    }
    // Ensure captions correctly translated.
    foreach ($requiredFields as $field => &$caption) {
      $caption = self::getWarehouseFieldLabel($field, $availableFields);
    }
    self::$indiciaData['requiredFields'] = $requiredFields;
    $dbFieldOptions = self::getAvailableDbFieldsAsOptions($options, $availableFields);
    foreach ($config['columns'] as $columnLabel => $info) {
      $select = "<select class=\"form-control mapped-field\" name=\"$info[tempDbField]\">$dbFieldOptions</select>";
      $htmlList[] = <<<HTML
<tr>
  <td>$columnLabel</td>
  <td>$select</td>
</tr>
HTML;
    }
    $tableRowsHtml = implode('', $htmlList);
    return <<<HTML
<h3>$lang[title]</h3>
<p>$lang[instructions]</p>
<div class="inline field-type-selector">
  <span>$lang[display]</span>
  <label class="radio-inline auto"><input type="radio" name="field-type-toggle" value="standard" checked> $lang[standardFieldsOnly]</label>
  <label class="radio-inline auto"><input type="radio" name="field-type-toggle" value="advanced"> $lang[standardAndAdvancedFields]</label>
</div>
<form method="POST">
  <div class="row">
    <div class="col-md-8">
      <table class="table" id="mappings-table">
        <thead>
          <tr>
            <th>$lang[columnInImportFile]</th>
            <th>$lang[destinationDatabaseField]</th>
          <tr>
        </thead>
        <tbody>
          $tableRowsHtml
        </tbody>
      </table>
    </div>
    <div class="col-md-4">
      <div class="panel panel-info" id="required-fields" style="display: none">
        <div class="panel-heading">$lang[requiredFields]</div>
        <div class="panel-body">
          <p>$lang[requiredFieldsInstructions]</p>
          <ul>
          </ul>
          <p class="alert alert-info" id="selected-field-messages" style="display: none"></p>
        </div>
      </div>
    </div>
  </div>
  <input type="submit" class="btn btn-primary" id="next-step" value="$lang[next]" disabled="disabled" />
  <input type="hidden" name="next-import-step" value="lookupMatchingForm" />
  <input type="hidden" name="config-id" id="config-id" value="{$_POST['config-id']}" />
</form>
HTML;
  }

  /**
   * Hides a single core field from mapping if not required.
   *
   * @param string $entity
   *   Sample or occurrence.
   * @param string $field
   *   Field name to check.
   * @param string $sysFunc
   *   System function for the attribute that might replace the field.
   * @param array $options
   *   Import options, including read auth and blocked fields list.
   * @param int $surveyId
   *   Survey ID.
   * @param array $availableFields
   *   List of available fields which will be modified.
   */
  private static function hideCoreFieldIfReplaced($entity, $field, $sysFunc, array $options, $surveyId, array &$availableFields) {
    // Don't bother if field already on blocked list.
    if (!self::fieldIsBlocked($options, "$entity:$field")) {
      $attr = data_entry_helper::get_population_data([
        'table' => "{$entity}_attribute",
        'extraParams' => $options['readAuth'] + [
          'restrict_to_survey_id' => $surveyId,
          'system_function' => $sysFunc,
        ],
        'cachePerUser' => FALSE,
        'cachetimeout' => 24 * 60 * 60,
        'sharing' => 'editing',
      ]);
      if (count($attr) > 0) {
        unset($availableFields["$entity:$field"]);
      }
    }
  }

  /**
   * Hides core fields from mapping if not required.
   *
   * E.g. if sample:recorder_names replaced by a custom attribute, then hide
   * the core version. Also applies to occurrence:determiner_id.
   *
   * @param array $options
   *   Import options, including read auth and blocked fields list.
   * @param int $surveyId
   *   Survey ID.
   * @param array $availableFields
   *   List of available fields which will be modified.
   */
  private static function hideCoreFieldsIfReplaced(array $options, $surveyId, array &$availableFields) {
    self::hideCoreFieldIfReplaced('sample', 'recorder_names', 'full_name', $options, $surveyId, $availableFields);
    self::hideCoreFieldIfReplaced('occurrence', 'fk_determiner', 'det_full_name', $options, $surveyId, $availableFields);
  }

  /**
   * Convert the list of available db fields to <option> elements.
   *
   * @param array $options
   *   Options array for the control.
   * @param array $availableFields
   *   List of field names and captions that are available for the imported
   *   entity.
   *
   * @return string
   *   HTML for the list of <optgroup>s containing <option>s.
   */
  private static function getAvailableDbFieldsAsOptions(array $options, array $availableFields) {
    $lang = [
      'notImported' => lang::get('not imported'),
    ];
    $colsByGroup = [];
    $optGroup = '';
    $shortGroupLabels = [];
    // A set of variations on custom attribute captions that can match. E.g.
    // determiner column can match an identified by attribute and vice versa.
    $customAttrVariations = [
      ['abundance', 'count', 'qty', 'quantity'],
      ['vc', 'vicecounty', 'vicecountynumber'],
      ['recorder', 'recorders', 'recordername', 'recordernames'],
      ['determinedby', 'determiner', 'identifiedby', 'identifier'],
      ['lifestage', 'stage'],
    ];
    foreach ($availableFields as $field => $caption) {
      // Skip fields that are not suitable for non-expert imports.
      if (self::fieldIsBlocked($options, $field)) {
        continue;
      }
      $fieldParts = explode(':', $field);
      if ($optGroup !== lang::get("optionGroup-$fieldParts[0]")) {
        $optGroup = lang::get("optionGroup-$fieldParts[0]");
        $colsByGroup[$optGroup] = [];
        $shortGroupLabels[$optGroup] = lang::get("optionGroup-$fieldParts[0]-shortLabel");
      }
      // Find variants of field names for auto matching.
      $alts = [];
      switch ($field) {
        case 'occurrence:comment':
          $alts = ['comment', 'comments', 'notes'];
          break;

        case 'occurrence:external_key':
          $alts = ['recordkey', 'ref', 'referenceno', 'referencenumber'];
          break;

        case 'occurrence:fk_taxa_taxon_list':
          $alts = ['commonname', 'scientificname', 'species', 'speciesname', 'taxon', 'taxonname', 'vernacular'];
          break;

        case 'occurrence:fk_taxa_taxon_list:search_code':
          $alts = ['searchcode', 'tvk', 'taxonversionkey'];
          break;

        case 'sample:date':
          $alts = ['eventdate'];
          break;

        case 'sample:entered_sref':
          $alts = ['gridref', 'gridreference', 'spatialref', 'spatialreference', 'gridreforotherspatialref', 'mapref', 'mapreference', 'coords', 'coordinates'];
          break;

        case 'sample:location_name':
          $alts = ['location', 'site', 'sitename'];
          break;

        case 'sample:recorder_names':
          $alts = ['recorder', 'recorders', 'recordername', 'recordernames'];
          break;

        default:
          $alt = '';
      }
      // Matching variations for some potential custom attribute captions.
      if (preg_match('/(.+) \(.+\)/', $caption, $matches)) {
        // Strip anything in brackets from caption we are checking.
        $captionSimplified = preg_replace('/[^a-z]/', '', strtolower($matches[1]));
      }
      else {
        $captionSimplified = preg_replace('/[^a-z]/', '', strtolower($caption));
      }
      // Allow for variations in custom attribute naming.
      if (substr($field, 3, 4) === 'Attr') {
        foreach ($customAttrVariations as $variationSet) {
          if (in_array(strtolower($captionSimplified), $variationSet)) {
            unset($variationSet[array_search($captionSimplified, $variationSet)]);
            $alts = $alts + $variationSet;
          }
        }
      }
      // Build the data attribute.
      $alt = empty($alts) ? '' : ' data-alt="' . implode(',', $alts) . '"';
      $label = self::getWarehouseFieldLabel($field, $availableFields);
      $advanced = in_array($field, $options['advancedFields']) ? ' class="advanced" ' : '';
      $colsByGroup[$optGroup][$label] = "<option value=\"$field\"$advanced data-untranslated=\"$caption\"$alt>$label</option>";
    }
    $optGroupHtmlList = ["<option value=\"\">- $lang[notImported] -</option>"];
    foreach ($colsByGroup as $thisColOptionGroup => $optionsList) {
      ksort($optionsList);
      $options = implode('', $optionsList);
      $shortLabel = $shortGroupLabels[$thisColOptionGroup];
      $optGroupHtmlList[] = <<<HTML
<optgroup label="$thisColOptionGroup" data-short-label="$shortLabel">
  $options
</optgroup>
HTML;
    }
    return implode('', $optGroupHtmlList);
  }

  /**
   * Determine if a field should be excluded from the options to map to.
   *
   * @param array $options
   *   Options array for the control, including a blockedFields option.
   * @param string $field
   *   Field name.
   *
   * @return bool
   *   True if blocked.
   */
  private static function fieldIsBlocked(array $options, $field) {
    // Is the entity/field name combination blocked?
    if (in_array($field, $options['blockedFields'])) {
      return TRUE;
    }
    // Also check if the field name is blocked with the entity unspecified.
    $parts = explode(':', $field);
    if (count($parts) > 1) {
      if (in_array($parts[1], $options['blockedFields'])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Saves a posted form's values into the config object.
   *
   * @param array $values
   *   Array of data to save (e.g. $_POST).
   * @param array $options
   *   Options including writeAuth.
   * @param string $configFieldName
   *   Name of the field in the config to save the data to.
   *
   * @return array
   *   Saved values.
   */
  private static function saveFormValuesToConfig(array $values, array $options, $configFieldName) {
    $settings = array_merge([], $values);
    unset($settings['config-id']);
    unset($settings['next-import-step']);
    $request = parent::$base_url . 'index.php/services/import_2/save_config';
    self::http_post($request, $options['writeAuth'] + [
      'config-id' => $_POST['config-id'],
      $configFieldName => json_encode($settings),
    ]);
    return $settings;
  }

  /**
   * After posting the mappings form, save them.
   *
   * @param array $values
   *   Posted mappings values.
   * @param array $options
   *   Import control options including the write auth tokens.
   */
  private static function saveMappings(array $values, array $options) {
    $settings = array_merge([], $values);
    unset($settings['config-id']);
    unset($settings['next-import-step']);
    $request = parent::$base_url . 'index.php/services/import_2/save_mappings';
    self::http_post($request, $options['writeAuth'] + [
      'config-id' => $_POST['config-id'],
      'mappings' => json_encode($settings),
    ]);
  }

  /**
   * Import page for matching lookups.
   *
   * E.g. species, attributes with termlist lookups, or other foreign keys.
   *
   * @param array $options
   *   Import control options array.
   */
  private static function lookupMatchingForm(array $options) {
    helper_base::add_resource('autocomplete');
    helper_base::add_resource('validation');
    // Save the results of the previous mappings form.
    self::saveMappings($_POST, $options);
    self::addLanguageStringsToJs('import_helper_2', [
      'backgroundProcessingDone' => 'Background processing done',
      'dataValue' => 'Data value',
      'findingLookupFieldsThatNeedMatching' => 'Finding lookup fields that need matching.',
      'findLookupFieldsDone' => 'Finding lookup fields done.',
      'lookupFieldFound' => 'Lookup field found: {1}',
      'lookupMatchingFormNothingToDo' => 'All data values that need to be mapped to an exact term in the database have been successfully automatically matched. Please click Next to continue.',
      'matchesToLocation' => 'Matches to location',
      'matchesToTaxon' => 'Matches to species or taxon name',
      'matchesToTerm' => 'Matches to term',
      'matchingPanelFor' => 'List of values to match for {1} column',
      'pleaseMatchAllValues' => 'Matches saved, but there are more matches required for {1}.',
      'pleaseMatchValues' => 'Please match the values in the list before saving them.',
      'pleaseSelect' => '- Please select -',
      'savingMatchesFor' => 'Saving matches for {1}',
      'savedMatches' => 'Matches saved',
      'severalMatches' => 'Several possible matches were found for {1}. Click on the panel below to select the correct match, or search for a match using the search box above.',
      'synOf' => 'Syn. of',
      'typeLocationNameToSearch' => 'Type the start of a location name to search',
      'typeSpeciesNameToSearch' => 'Type the start of a species or taxon name to search',
    ]);
    $lang = [
      'backgroundProcessing' => lang::get('Background processing in progress...'),
      'instructions' => lang::get($options['lookupMatchingFormIntro']),
      'moreInfo' => lang::get('More info...'),
      'next' => lang::get('Next step'),
      'title' => lang::get('Value matching'),
    ];
    self::$indiciaData['configId'] = $_POST['config-id'];
    return <<<HTML
<h3>$lang[title]</h3>
<p id="instructions">$lang[instructions]</p>
<form method="POST" id="lookup-matching-form">
  <div id="matching-area">
  </div>
  <div class="panel panel-info background-processing">
    <div class="panel-heading">
      <span>$lang[backgroundProcessing]</span>
      <br/><a data-toggle="collapse" class="small" href="#background-extra">$lang[moreInfo]</a>
    </div>
    <div id="background-extra" class="panel-body panel-collapse collapse"></div>
  </div>
  <input type="submit" class="btn btn-primary" id="next-step" value="$lang[next]" disabled />
  <input type="hidden" name="next-import-step" value="preprocessPage" />
  <input type="hidden" name="config-id" id="config-id" value="{$_POST['config-id']}" />
</form>
HTML;
  }

  /**
   * Returns a page for preprocessing the uploaded information.
   *
   * Not implemented yet so skips to the next page.
   */
  private static function preprocessPage($options) {
    self::addLanguageStringsToJs('import_helper_2', [
      'downloadPreprocessingErrorsExplanationsFile' => 'Download preprocessing errors explanations',
      'importCannotProceed' => 'The import cannot proceed due to problems found in the data:',
      'preprocessingError' => 'Preprocessing error',
      'preprocessingErrorInfo' => 'An error occurred on the server whilst preprocessing your data:',
      'preprocessingImport' => 'Preparing to import',
    ]);
    $lang = [
      'backgroundProcessing' => lang::get('Background processing in progress...'),
      'instructions' => lang::get($options['preprocessPageIntro']),
      'moreInfo' => lang::get('More info...'),
      'next' => lang::get('Next step'),
      'title' => lang::get('Preparing to import'),
    ];
    self::$indiciaData['configId'] = $_POST['config-id'];
    return <<<HTML
<h3>$lang[title]</h3>
<p id="instructions">$lang[instructions]</p>
<form method="POST" id="preprocessing-form">
  <div class="panel panel-info background-processing">
    <div class="panel-heading">
      <span>$lang[backgroundProcessing]</span>
      <br/><a data-toggle="collapse" class="small" href="#background-extra">$lang[moreInfo]</a>
    </div>
    <div id="background-extra" class="panel-body panel-collapse collapse"></div>
  </div>
  <input type="submit" class="btn btn-primary" id="next-step" value="$lang[next]" style="display: none" />
  <input type="hidden" name="next-import-step" value="summaryPage" />
  <input type="hidden" name="config-id" id="config-id" value="{$_POST['config-id']}" />
</form>
HTML;
  }

  /**
   * Returns the appropriate arrow icon for a mapped column.
   *
   * @param array $info
   *   Column information.
   *
   * @return string
   *   HTML for the arrow icon.
   */
  private static function getSummaryColumnArrow(array $info) {
    $lang = [
      'dataValuesCopied' => lang::get('Values in this column are copied to this field'),
      'dataValuesIgnored' => lang::get('Values in this column are ignored'),
      'dataValuesMatched' => lang::get('Values in this column are matched against predefined lists of terms.'),
    ];
    if (empty($info['warehouseField'])) {
      $arrow = "<i class=\"fas fa-stop\" title=\"$lang[dataValuesIgnored]\"></i>";
    }
    elseif (!empty($info['isFkField'])) {
      $arrow = "<i class=\"fas fa-list\" title=\"$lang[dataValuesMatched]\"></i>";
    }
    else {
      $arrow = "<i class=\"fas fa-play\" title=\"$lang[dataValuesCopied]\"></i>";
    }
    return $arrow;
  }

  /**
   * Convert warehouse field name to readable form.
   *
   * E.g. sample:comment is returned as Comment.
   *
   * @param string $warehouseField
   *   Field name.
   *
   * @return string
   *   Readable form of the field name.
   */
  private static function getReadableWarehouseField($warehouseField) {
    $parts = explode(':', $warehouseField);
    return str_replace('_', ' ', ucfirst(array_pop($parts)));
  }

  /**
   * Retrieve a readable label for a destination warehouse field.
   *
   * @param string $field
   *   Database field name.
   * @param array $availableFields
   *   List of available fields for the import entity, with their display
   *   labels.
   *
   * @return string
   *   Field label to display.
   */
  private static function getWarehouseFieldLabel($field, array $availableFields, $includeTablePrefix = FALSE) {
    $label = lang::get($field);
    if ($label === $field) {
      // No translation provided.
      if (isset($availableFields[$field])) {
        $label = lang::get($availableFields[$field]);
      }
      else {
        $label = self::getReadableWarehouseField($field);
      }
    }
    if ($includeTablePrefix) {
      $fieldParts = explode(':', $field);
      $tablePrefix = lang::get("optionGroup-$fieldParts[0]-shortLabel");
      if (substr($tablePrefix, 0, 12) !== 'optionGroup-') {
        $label = "$tablePrefix $label";
      }
    }
    return str_replace(' id', ' ID', ucfirst(strtolower($label)));
  }

  /**
   * Build the summary page HTML output.
   *
   * @param array $options
   *   Importer control options array.
   *
   * @return string
   *   HTML for the page.
   */
  private static function summaryPage(array $options) {
    $lang = [
      'backgroundProcessing' => lang::get('Importing large files...'),
      'backgroundProcessingInfo' => lang::get('As your import is fairly large, the data will be added to a queue of records to import and processed in the background. ' .
        'After clicking the Queue import button you can close this page and continue working, the import will continue in the background and you will receive an email once complete.'),
      'columnMappings' => lang::get('Column mappings'),
      'databaseField' => lang::get('Database field'),
      'deletionExplanation' => lang::get('Existing records will be deleted if the Deleted field is set to "1", "true" or "t".'),
      'existingRecords' => lang::get('Existing records'),
      'fileType' => lang::get('File type'),
      'globalValues' => lang::get('Fixed values that apply to all rows'),
      'importMetadata' => lang::get('Import metadata'),
      'importMetadataHelp' => lang::get('Please provide any metadata to describe this import, such as the source of the records and the date of the import (optional).'),
      'importTemplate' => 'Import template',
      'instructions' => lang::get($options['summaryPageIntro']),
      'importColumn' => lang::get('Import column'),
      'numberOfRecords' => lang::get('Number of records'),
      'queueImport' => lang::get('Queue import'),
      'recordDeletion' => lang::get('Record deletion'),
      'saveImportTemplate' => lang::get('Save import template'),
      'saveImportTemplateHelp' => lang::get('If you would like to save the column mappings and fixed values so they can be re-used when importing other files in future, please provide a descriptive name for your settings here.'),
      'startImport' => lang::get('Start importing records'),
      'title' => lang::get('Import summary'),
      'value' => lang::get('Value'),
    ];
    $configId = $_POST['config-id'];
    $request = parent::$base_url . "index.php/services/import_2/get_config";
    $request .= '?' . http_build_query($options['readAuth'] + ['config-id' => $configId]);
    $response = self::http_post($request, []);
    $config = json_decode($response['output'], TRUE);
    if (!is_array($config)) {
      throw new Exception('Service call to get_config failed.');
    }
    $ext = pathinfo(array_keys($config['files'])[0], PATHINFO_EXTENSION);
    $availableFields = self::getAvailableDbFields($options, $configId, $config['global-values']);
    $mappingRows = [];
    $existingMatchFields = [];
    foreach ($config['columns'] as $columnLabel => $info) {
      $arrow = self::getSummaryColumnArrow($info);
      $warehouseFieldLabel = isset($info['warehouseField']) ? self::getWarehouseFieldLabel($info['warehouseField'], $availableFields, TRUE) : '';
      $mappingRows[] = "<tr><td><em>$columnLabel</td></em><td>$arrow</td><td>$warehouseFieldLabel</td></tr>";
      if (preg_match('/:(id|external_key)$/', $info['warehouseField'] ?? '')) {
        $existingMatchFields[] = $warehouseFieldLabel;
      }
    }
    $mappings = implode('', $mappingRows);
    $globalRows = self::globalValuesAsTableRows($config, $options['readAuth'], $availableFields);
    $infoRows = [
      "<dt>$lang[fileType]</dt><dd>$ext</dd>",
      "<dt>$lang[numberOfRecords]</dt><dd>$config[totalRows]</dd>",
    ];
    if (!empty($config['importTemplateTitle'])) {
      $infoRows[] = "<dt>$lang[importTemplate]</dt><dd>$config[importTemplateTitle]</dd>";
    }
    if (isset($config['global-values']['config:allowUpdates']) && $config['global-values']['config:allowUpdates'] == '1') {
      $matchFieldExplanation = lang::get('Existing records will be updated if there is a match on {1}.', implode('; ', $existingMatchFields));
      $infoRows[] = "<dt>$lang[existingRecords]</dt><dd class=\"alert alert-warning\">$matchFieldExplanation</dd>";
      if (isset($config['global-values']['config:allowDeletes']) && $config['global-values']['config:allowDeletes'] == '1') {
        $infoRows[] = "<dt>$lang[recordDeletion]</dt><dd class=\"alert alert-warning\">$lang[deletionExplanation]</dd>";
      }
    }
    $info = '<dl>' . implode("\n", $infoRows) . '</dl>';
    $importFieldTableContent = <<<HTML
      <tbody>
        <tr>
          <th colspan="3" class="body-title">$lang[columnMappings]</th>
        </tr>
        <tr>
          <th>$lang[importColumn]</th><th></th><th>$lang[databaseField]</th>
        </tr>
        $mappings
      </tbody>
      HTML;
    if (count($globalRows) > 0) {
      $globalRowsJoined = implode('', $globalRows);
      $importFieldTableContent .= <<<HTML
        <tbody>
          <tr>
            <th colspan="3" class="body-title">$lang[globalValues]</th>
          </tr>
          <tr>
            <th>$lang[value]</th><th></th><th>$lang[databaseField]</th>
          </tr>
          $globalRowsJoined
        </tbody>
        HTML;
    }
    $info .= "<table class=\"table\">$importFieldTableContent</table>";
    $startButtonCaption = $config['processingMode'] === 'background' ? $lang['queueImport'] : $lang['startImport'];
    $backgroundProcessingInfo = '';
    if ($config['processingMode'] === 'background') {
      $backgroundProcessingInfo = <<<HTML
        <div class="alert alert-info">
          <strong>$lang[backgroundProcessing]</strong><br/>
          <span id="background-processing-info">$lang[backgroundProcessingInfo]</span>
        </div>
        HTML;
    }
    return <<<HTML
      <h3>$lang[title]</h3>
      <p>$lang[instructions]</p>
      <form method="POST" id="summary-form">
        <div>
          $info
        </div>
        <div class="form-group">
          <label for="description">$lang[importMetadata]:</label>
          <textarea class="form-control" rows="5" name="description"></textarea>
          <p class="helpText">$lang[importMetadataHelp]</p>
        </div>
        <div class="form-group">
          <label for="template_title">$lang[saveImportTemplate]:</label>
          <input type="text" class="form-control" name="template_title" />
          <p class="helpText">$lang[saveImportTemplateHelp]</p>
        </div>
        $backgroundProcessingInfo
        <input type="submit" class="btn btn-primary" id="next-step" value="$startButtonCaption" />
        <input type="hidden" name="next-import-step" value="doImportPage" />
        <input type="hidden" name="config-id" id="config-id" value="$configId" />
        <input type="hidden" name="processing-mode" value="$config[processingMode]" />
      </form>
      HTML;
  }

  /**
   * Retrieves an array of trs to explain the global values being applied.
   *
   * @param array $config
   *   Upload config.
   * @param array $readAuth
   *   Read authorisation.
   * @param array $availableFields
   *   List of field names and captions to use.
   *
   * @return array
   *   Each entry is the HTML for a <tr> to show on the summary page,
   *   explaining one of the global values being applied to the import.
   */
  private static function globalValuesAsTableRows(array $config, array $readAuth, array $availableFields) {
    $globalRows = [];
    $lang = [
      'dataValuesCopied' => lang::get('This value is copied to this field for all records created.'),
    ];
    $arrow = "<i class=\"fas fa-play\" title=\"$lang[dataValuesCopied]\"></i>";
    $formArray = self::getGlobalValuesFormControlArray($config);
    foreach ($config['global-values'] as $field => $value) {
      // Default to use value as label, but preferably use the global values
      // control lookup info to get a better one.
      $displayLabel = $value;
      if (isset($formArray[$field]) && $formArray[$field]['datatype'] === 'lookup') {
        if (!empty($formArray[$field]['lookup_values'])) {
          // Convert the lookup values into an associative array.
          $lookups = explode(',', $formArray[$field]['lookup_values']);
          $lookupsAssoc = [];
          foreach ($lookups as $lookup) {
            $lookup = explode(':', $lookup);
            $lookupsAssoc[$lookup[0]] = $lookup[1];
          }
          $displayLabel = $lookupsAssoc[$value] ?? $value;
        }
        elseif (!empty($formArray[$field]['population_call'])) {
          $populationOptions = explode(':', $formArray[$field]['population_call']);
          // Only support direct at this point (not report population).
          if (count($populationOptions) >= 4 && $populationOptions[0] === 'direct') {
            $lookupData = self::get_population_data([
              'table' => $populationOptions[1],
              'extraParams' => $readAuth + [
                $populationOptions[2] => $value,
              ],
            ]);
            if (count($lookupData) === 1) {
              $displayLabel = $lookupData[0][$populationOptions[3]] . " ($populationOptions[2] = $value)";
            }
          }
        }
      }
      // Foreign key filters were used during matching, not actually for import.
      // Also exclude settings such as allowUpdates/deletes.
      if (strpos($field, 'fkFilter') === FALSE && strpos($field, 'config:') !== 0) {
        $displayField = self::getWarehouseFieldLabel($field, $availableFields, TRUE);

        $globalRows[] = "<tr><td>$displayLabel</td><td>$arrow</td><td>$displayField</td></tr>";
      }
    }
    return $globalRows;
  }

  /**
   * Outputs the page that shows import progress.
   */
  private static function doImportPage($options) {
    self::$indiciaData['readyToImport'] = TRUE;
    self::$indiciaData['configId'] = $_POST['config-id'];
    // Put the import description somewhere so it can be saved.
    self::$indiciaData['importDescription'] = $_POST['description'];
    self::$indiciaData['importTemplateTitle'] = $_POST['template_title'];
    if (!empty($_POST['template_title'])) {
      // Force a cache reload so the new template is instantly available.
      self::clearTemplateCache($options);
    }
    if (($_POST['processing-mode'] ?? '') === 'background') {
      return self::backgroundImportPage($options['backgroundImportStatusPath'] ?? NULL);
    }
    else {
      return self::immediateImportPage();
    }
  }

  /**
   * Shows the page which informs the user that a large import has been queued.
   *
   * @param mixed $statusPagePath
   *   Optional path to a page which shows the progress of a user's import
   *   tasks.
   *
   * @return string
   *   Page HTML.
   */
  private static function backgroundImportPage($statusPagePath) {
    global $indicia_templates;
    $lang = [
      'importQueued' => lang::get('Import queued'),
      'importQueuedInfo' => lang::get('Your import has been queued for processing. You can close this page and continue working, the import will continue in the background and you will receive an email once complete.'),
      'startAnotherImport' => lang::get('Start another import'),
      'viewBackgroundImportStatus' => 'View status of my background imports',
    ];
    $urlInfo = self::get_reload_link_parts();
    $restartUrl = $urlInfo['path'] . '?' . self::array_to_query_string($urlInfo['params']);
    $message = $lang['importQueuedInfo'];
    if ($statusPagePath) {
      $message .= "<br/><a href=\"$statusPagePath\" class=\"$indicia_templates[anchorButtonClass]\">$lang[viewBackgroundImportStatus]</a>";
    }
    $messageBox = str_replace('{message}', $message, $indicia_templates['messageBox']);
    return <<<HTML
      <h3>$lang[importQueued]</h3>
      <div id="import-queued-info" style="display: none">
        $messageBox
      </div>
      <a href="$restartUrl" class="btn btn-primary">$lang[startAnotherImport]</a>
    HTML;
  }

  private static function immediateImportPage() {
    global $indicia_templates;
    self::addLanguageStringsToJs('import_helper_2', [
      'cancel' => 'Cancel',
      'completeMessage' => 'The import is complete',
      'confirmTemplateOverwrite' => 'There is already an import template with the same name. Would you like to overwrite it?',
      'downloadErrors' => 'Download the rows that had errors',
      'errorInImportFile' => 'Errors were found in {1} row.',
      'errorsInImportFile' => 'Errors were found in {1} rows.',
      'importingCrashInfo' => 'An error occurred on the server whilst importing your data. You can download the errors file which includes all rows that have errors or were not imported. The file also includes an [errors] column with row-level error information and an [imported] column indicating which rows have been successfully imported.',
      'importingData' => 'Importing data',
      'importingDetails' => '{rowsProcessed} of {totalRows} rows imported, {errorsCount} rows with errors found.',
      'importingFoundErrors' => 'Errors were found during the import stage which means that data was imported but rows with errors were skipped. Please download the errors spreadsheet using the button below and correct the data then upload just the errors spreadsheet again.',
      'overwriteTemplate' => 'Overwrite the template',
      'precheckDetails' => '{rowsProcessed} of {totalRows} rows checked, {errorsCount} rows with errors found.',
      'precheckFoundErrors' => 'Because validation errors were found, no data has been imported. Please download the errors spreadsheet using the button below and correct the data in your original file accordingly, then upload it again.',
      'saveTemplate' => 'Save template',
      'skipSavingTemplate' => 'Skip saving the template',
    ]);
    $lang = [
      'checkingData' => lang::get('Checking data'),
      'importAnother' => lang::get('Import another file'),
      'importingDone' => lang::get('Import complete'),
      'importingTitle' => lang::get('Importing the data...'),
      'precheckDone' => 'Checking complete',
      'precheckTitle' => 'Checking the import data for validation errors...',
      'specifyUniqueTemplateName' => 'Please specify a unique name for the import template',
    ];
    $urlInfo = self::get_reload_link_parts();
    $restartUrl = $urlInfo['path'] . '?' . self::array_to_query_string($urlInfo['params']);
    return <<<HTML
      <h3 id="current-task">$lang[checkingData]</h3>
      <progress id="file-progress" class="progress" value="0" max="100"></progress>
      <div class="panel panel-info">
        <div class="panel-heading">
          <h4>Import details</h4>
        </div>
        <div id="import-details" class="panel-body">
          <p id="import-details-precheck-title" style="display: none">$lang[precheckTitle]</p>
          <p id="import-details-precheck-details" style="display: none"></p>
          <p id="import-details-precheck-done" style="display: none"><i class="fas fa-check"></i>$lang[precheckDone]</p>
          <p id="import-details-importing-title" style="display: none">$lang[importingTitle]</p>
          <p id="import-details-importing-details" style="display: none"></p>
          <p id="import-details-importing-done" style="display: none"><i class="fas fa-check"></i>$lang[importingDone]</p>
          <p id="import-details-import-another" style="display: none"><a href="$restartUrl" class="btn btn-primary">$lang[importAnother]</a></p>
        </div>
      </div>
      <div class="alert alert-danger clearfix" id="error-info" style="display: none">
        <i class="fas fa-exclamation-triangle fa-2x $indicia_templates[floatRightClass]"></i>
      </div>
      <div style="display: none">
        <div id="template-title-form">
          <p>$lang[specifyUniqueTemplateName]</p>
          <div class="form-group">
            <label for="description">Import template name:</label>
            <input class="form-control" id="import-template-title" />
          </div>
        </div>
      </div>
    HTML;
  }

  /**
   * Ensures the importer options defaults are filled in.
   *
   * Also checks that required options are present.
   *
   * @param array $options
   *   Importer options array.
   */
  private static function getImportHelperOptions(&$options) {
    // Apply default options.
    $defaults = [
      'advancedMode' => FALSE,
      'allowDeletes' => FALSE,
      'allowUpdates' => FALSE,
      'blockedFields' => self::$blockedFields,
      'doImportPageIntro' => lang::get('import2doImportPageIntro'),
      'entity' => 'occurrence',
      'fileSelectFormIntro' => lang::get('import2fileSelectFormIntro'),
      'fixedValues' => [],
      'globalValuesFormIntro' => lang::get('import2globalValuesFormIntro'),
      'importReverse' => 'import',
      'lookupMatchingFormIntro' => lang::get('lookupMatchingFormIntro'),
      'mappingsFormIntro' => lang::get('import2mappingsFormIntro'),
      'preprocessPageIntro' => lang::get('import2preprocessPageIntro'),
      'requiredFieldsIntro' => lang::get('import2requiredFieldsIntro'),
      'summaryPageIntro' => lang::get('summaryPageIntro'),
    ];
    $options = array_merge($defaults, $options);
    $requiredOptions = [
      'uploadFileUrl',
      'sendFileToWarehouseUrl',
      'initServerConfigUrl',
      'loadChunkToTempTableUrl',
      'preprocessUrl',
      'processLookupMatchingUrl',
      'saveLookupMatchesGroupUrl',
      'importChunkUrl',
      'getErrorFileUrl',
    ];
    foreach ($requiredOptions as $requiredOption) {
      if (empty($options[$requiredOption])) {
        throw new exception("Import_helper_2::importer needs a $requiredOption option");
      }
    }
  }

  /**
   * Retrieive a list of all fields available for the entity being imported.
   *
   * @param array $options
   *   Options array for the control.
   * @param string $configId
   *   Import config identifier, allows the server to lookup config.
   * @param array $globalValues
   *   Values that apply to every import row, including website_id and
   *   survey__id where available.
   * @param bool $requiredOnly
   *   Set to TRUE to only return required fields.
   *
   * @return array
   *   Associated array with key/value pairs of field names and captions.
   */
  private static function getAvailableDbFields(array $options, $configId, array $globalValues, $requiredOnly = FALSE) {
    $url = "index.php/services/import_2/get_fields/$options[entity]";
    $get = array_merge($options['readAuth']);
    // Include survey and website information in the request if available, as
    // this limits the availability of custom attributes.
    if (!empty($globalValues['website_id'])) {
      $get['website_id'] = trim($globalValues['website_id']);
    }
    if (!empty($globalValues['survey_id'])) {
      $get['survey_id'] = trim($globalValues['survey_id']);
    }
    if (($options['entity'] === 'sample' || $options['entity'] === 'occurrence')
        && isset($globalValues['sample:sample_method_id'])
        && trim($globalValues['sample:sample_method_id'] ?? '') !== '') {
      $get['sample_method_id'] = trim($globalValues['sample:sample_method_id']);
    }
    elseif ($options['entity'] === 'location'
        && isset($globalValues['location:location_type_id'])
        && trim($globalValues['location:location_type_id'] ?? '') !== '') {
      $get['location_type_id'] = trim($globalValues['location:location_type_id']);
    }
    elseif ($options['entity'] === 'taxa_taxon_list'
        && isset($globalValues['taxa_taxon_list:taxon_list_id'])
        && trim($globalValues['taxa_taxon_list:taxon_list_id'] ?? '') !== '') {
      $get['taxon_list_id'] = trim($globalValues['taxa_taxon_list:taxon_list_id']);
    }
    if ($options['advancedMode']) {
      // In advanced mode, allow user to import directly into a foreign key
      // field by ID, without using a lookup term.
      $get['keep_fk_ids'] = 'true';
    }
    if ($requiredOnly) {
      $get['required'] = 'true';
    }
    $r = self::getCachedGenericCall($url, $get, ['config-id' => $configId], [
      'caching' => TRUE,
      'cachePerUser' => FALSE,
    ]);
    return $r;
  }

  /**
   * Retrieves previously saved import templates.
   *
   * @param array $options
   *   Import options array.
   *
   * @return array
   *   Array of template data.
   */
  private static function loadTemplates(array $options) {
    return helper_base::get_population_data([
      'table' => 'import_template',
      'extraParams' => $options['readAuth'] + [
        'entity' => $options['entity'],
        'created_by_id' => hostsite_get_user_field('indicia_user_id'),
        'orderby' => 'title',
      ],
    ]);
  }

  /**
   * If list of templates updated, clear cache for next time.
   *
   * @param array $options
   *   Import options array.
   */
  private static function clearTemplateCache(array $options) {
    helper_base::expireCacheEntry([
      'table' => 'import_template',
      'extraParams' => $options['readAuth'] + [
        'entity' => $options['entity'],
        'created_by_id' => hostsite_get_user_field('indicia_user_id'),
        'orderby' => 'title',
      ],
    ]);
  }

  /**
   * Retrieves previously saved import templates.
   *
   * @param array $options
   *   Import options array.
   *
   * @return array
   *   Array of template data.
   */
  private static function loadSelectedTemplate(array $options) {
    if (!empty($_POST['import_template_id'])) {
      $r = helper_base::get_population_data([
        'table' => 'import_template',
        'extraParams' => $options['readAuth'] + [
          'entity' => $options['entity'],
          'id' => $_POST['import_template_id'],
        ],
        'caching' => FALSE,
      ]);
      if (count($r) === 1) {
        helper_base::$indiciaData['import_template_id'] = (int) $r[0]['id'];
        return $r[0];
      }
      else {
        throw new Exception('Failed to load selected template');
      }
    }
    return [];
  }

}
