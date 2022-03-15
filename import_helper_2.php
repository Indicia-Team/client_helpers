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
   * A file import component.
   *
   * Options include:
   * * entity
   * * fileSelectFormIntro
   * * globalValuesFormIntro
   * * mappingsFormIntro
   * * lookupMatchingFormIntro
   * * validationFormIntro
   * * summaryPageIntro
   * * doImportFormIntro
   * * uploadFileUrl - path to a script that handles the initial upload of a
   *   file to the interim file location. The script can call
   *   import_helper_2::uploadFile for a complete implementation.
   * * sendFileToWarehouseUrl - path to a script that forwards the import file
   *   from the interim location to the warehouse.  The script can call
   *   import_helper_2::sendFileToWarehouse for a complete implementation.
   * * loadChunkToTempTableUrl - path to a script that triggers the load of the
   *   next chunk of records into a temp table on the warehouse. The script can
   *   call import_helper_2::loadChunkToTempTable for a complete implementation.
   * * processLookupMatchingUrl - path to a script that performs steps in the
   *   process of identifying lookup destination fields that need their values
   *   to be matched to obtain an ID.
   * * saveLookupMatchesGroupUrl - path to a script that saves a set of
   *   matching data value/matched termlist term ID pairs for a lookup custom
   *   attribute.
   * * importChunkUrl - path to a script that imports the next chunk of records
   *   from the temp table into the database.
   * * readAuth - read authorisation tokens.
   * * writeAuth - write authorisation tokens.
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
    self::add_resource('uploader');
    self::add_resource('font_awesome');
    self::add_resource('fancybox');
    self::getImportHelperOptions($options);
    self::$indiciaData['uploadFileUrl'] = $options['uploadFileUrl'];
    self::$indiciaData['sendFileToWarehouseUrl'] = $options['sendFileToWarehouseUrl'];
    self::$indiciaData['extractFileOnWarehouseUrl'] = $options['extractFileOnWarehouseUrl'];
    self::$indiciaData['loadChunkToTempTableUrl'] = $options['loadChunkToTempTableUrl'];
    self::$indiciaData['processLookupMatchingUrl'] = $options['processLookupMatchingUrl'];
    self::$indiciaData['saveLookupMatchesGroupUrl'] = $options['saveLookupMatchesGroupUrl'];
    self::$indiciaData['importChunkUrl'] = $options['importChunkUrl'];
    self::$indiciaData['write'] = $options['writeAuth'];
    $nextImportStep = empty($_POST['next-import-step']) ? 'fileSelectForm' : $_POST['next-import-step'];
    switch ($nextImportStep) {
      case 'fileSelectForm':
        return self::fileSelectForm($options);

      case 'globalValuesForm':
        return self::globalValuesForm($options);

      case 'mappingsForm':
        return self::mappingsForm($options);

      case 'lookupMatchingForm':
        return self::lookupMatchingForm($options);

      case 'validationForm':
        return self::validationForm($options);

      case 'summaryPage':
        return self::summaryPage($options);

      case 'doImportPage':
        return self::doImportPage($options);

      default:
        throw new exception('Invalid next-import-step parameter');
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
  public static function sendFileToWarehouse($fileName, array $writeAuth) {;
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
      throw new exception(isset($output['msg']) ? $output['msg'] : $response['output']);
    }
    return $output;
  }

  /**
   * Triggers the load of a chunk of records from the file to the temp table.
   *
   * @param string $fileName
   *   Name of the file to transfer.
   * @param array $writeAuth
   *   Write authorisation tokens.
   */
  public static function loadChunkToTempTable($fileName, array $writeAuth) {
    $serviceUrl = self ::$base_url . 'index.php/services/import_2/load_chunk_to_temp_table';
    $data = $writeAuth + ['data-file' => $fileName];
    $response = self::http_post($serviceUrl, $data, FALSE);
    $output = json_decode($response['output'], TRUE);
    if (!$response['result']) {
      \Drupal::logger('iform')->notice('Error in loadChunkToTempTable: ' . var_export($response, TRUE));
      throw new exception(isset($output['msg']) ? $output['msg'] : $response['output']);
    }
    return $output;
  }

  /**
   * Actions the next step in the process of linking data values to lookup IDs.
   *
   * @param string $fileName
   *   Name of the file to process.
   * @param int $index
   *   Index of the request - start at 0 then increment by one for each request
   *   to fetch each lookup that needs matching in turn.
   * @param array $writeAuth
   *   Write authorisation tokens.
   */
  public static function processLookupMatching($fileName, $index, array $writeAuth) {
    $serviceUrl = self ::$base_url . 'index.php/services/import_2/process_lookup_matching';
    $data = $writeAuth + [
      'data-file' => $fileName,
      'index' => $index,
    ];
    $response = self::http_post($serviceUrl, $data, FALSE);
    $output = json_decode($response['output'], TRUE);
    if (!$response['result']) {
      \Drupal::logger('iform')->notice('Error in processLookupMatching: ' . var_export($response, TRUE));
      throw new exception(isset($output['msg']) ? $output['msg'] : $response['output']);
    }
    return $output;
  }

  /**
   * Saves the manually matched value / term ID pairings for a custom attr.
   *
   * @param string $fileName
   *   Name of the file to process.
   * @param array $matchesInfo
   *   Matching data.
   * @param array $writeAuth
   *   Write authorisation tokens.
   */
  public static function saveLookupMatchesGroup($fileName, array $matchesInfo, array $writeAuth) {
    $serviceUrl = self ::$base_url . 'index.php/services/import_2/save_lookup_matches_group';
    $data = $writeAuth + [
      'data-file' => $fileName,
      'matches-info' => json_encode($matchesInfo),
    ];
    $response = self::http_post($serviceUrl, $data, FALSE);
    $output = json_decode($response['output'], TRUE);
    if (!$response['result']) {
      \Drupal::logger('iform')->notice('Error in saveLookupMatchesGroup: ' . var_export($response, TRUE));
      throw new exception(isset($output['msg']) ? $output['msg'] : $response['output']);
    }
    return $output;
  }

  /**
   * Import the next chunk of records to the main database.
   *
   * @param string $fileName
   *   Name of the file to process.
   * @param string $description
   *   Description if saving the import metadata for the first time.
   */
  public static function importChunk($fileName, $description, array $writeAuth) {
    $serviceUrl = self ::$base_url . 'index.php/services/import_2/import_chunk';
    $data = $writeAuth + [
      'data-file' => $fileName,
    ];
    if ($description !== NULL) {
      $data['save-import-record'] = json_encode([
        'description' => $description,
      ]);
    }
    $response = self::http_post($serviceUrl, $data, FALSE);
    $output = json_decode($response['output'], TRUE);
    if (!$response['result']) {
      \Drupal::logger('iform')->notice('Error in importChunk: ' . var_export($response, TRUE));
      throw new exception(isset($output['msg']) ? $output['msg'] : $response['output']);
    }
    return $output;
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
      'selectedFile' => 'Selected {1} file',
      'uploadFailedWithError' => 'The file upload failed. The error message was:<br/>{1}.',
    ]);
    $lang = [
      'browseFiles' => lang::get('Browse files'),
      'clickToAdd' => lang::get('Click to add files'),
      'instructions' => lang::get($options['fileSelectFormIntro']),
      'next' => lang::get('Next step'),
      'selectAFile' => lang::get('Select a CSV or Excel file or drag it over this area. The file can optionally be a zip archive.'),
      'uploadFileToImport' => lang::get('Upload a file to import'),
    ];
    $r = <<<HTML
<h3>$lang[uploadFileToImport]</h3>
<p>$lang[instructions]</p>
<form id="file-upload-form" method="POST">
  <div class="dm-uploader row">
    <div class="col-md-6">
      <div role="button" class="btn btn-primary">
        <i class="fas fa-file-upload"></i>
        $lang[browseFiles]
        <input type="file" title="$lang[clickToAdd]">
      </div>
      <small class="status text-muted">$lang[selectAFile]</small>
    </div>
    <div class="col-md-6" id="uploaded-files"></div>
  </div>
  <progress id="file-progress" class="progress" value="0" max="100" style="display: none"></progress>
  <input type="submit" class="btn btn-primary" id="next-step" value="$lang[next]" disabled />
  <input type="hidden" name="next-import-step" value="globalValuesForm" />
  <input type="hidden" name="interim-file" id="interim-file" value="" />
</form>
HTML;
    self::$indiciaData['importerDropArea'] = '.dm-uploader';
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
      'extractingFile' => 'Extracting the data from the Zip file.',
      'fileExtracted' => 'Data extracted from Zip file.',
      'fileUploaded' => 'File uploaded to the server.',
      'loadingRecords' => 'Loading records.',
      'loaded' => 'Records loaded ready for matching.',
      'preparingToLoadRecords' => 'Preparing to load records.',
      'uploadError' => 'Upload error',
      'uploadingFile' => 'Uploading the file to the server.',
    ]);
    $lang = [
      'backgroundProcessing' => lang::get('Background processing'),
      'instructions' => lang::get($options['globalValuesFormIntro']),
      'moreInfo' => lang::get('More info...'),
      'next' => lang::get('Next step'),
      'title' => lang::get('Import settings'),
    ];
    // Find the controls that we can accept global values for, depending on the
    // entity we are importing into.
    $response = self::cache_get(['entityImportSettings' => $options['entity']]);
    if ($response === FALSE) {
      $request = parent::$base_url . "index.php/services/import_2/get_globalvalues_form/" . $options['entity'];
      $request .= '?' . self::array_to_query_string($options['readAuth']);
      $response = self::http_post($request, []);
      if (!isset($response['error'])) {
        self::cache_set(['entityImportSettings' => $options['entity']], json_encode($response));
      }
    }
    else {
      $response = json_decode($response, TRUE);
    }
    if (!empty($response['output'])) {
      $formArray = json_decode($response['output'], TRUE);
      $form = self::globalValuesFormControls($formArray, $options);
    }
    self::$indiciaData['processUploadedInterimFile'] = $_POST['interim-file'];
    return <<<HTML
<h3>$lang[title]</h3>
<p>$lang[instructions]</p>
<form id="settings-form" method="POST">
  $form
  <div class="panel panel-info background-processing">
    <div class="panel-heading">
      $lang[backgroundProcessing]
      <progress id="file-progress" class="progress" value="0" max="100"></progress>
      <a data-toggle="collapse" class="small" href="#background-extra">$lang[moreInfo]</a>
    </div>
    <div id="background-extra" class="panel-body panel-collapse collapse"></div>
  </div>
  <input type="submit" class="btn btn-primary" id="next-step" value="$lang[next]" disabled />
  <input type="hidden" name="next-import-step" value="mappingsForm" />
  <input type="hidden" name="data-file" id="data-file" value="" />
</form>
HTML;
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
    foreach ($formArray as $key => $info) {
      $r .= self::getParamsFormControl($key, $info, $options, $tools);
    }
    return $r;
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
      'instructions' => lang::get($options['mappingsFormIntro']),
      'next' => lang::get('Next step'),
      'title' => lang::get('Map import columns to destination database fields'),
    ];
    // Save the results of the previous global values form.
    $settings = self::savePostedFormValuesToConfig($options, 'global-values');
    $availableFields = self::getAvailableDbFields($options, $settings);
    $htmlList = [];
    $dbFieldOptions = self::getAvailableDbFieldsAsOptions($availableFields);
    $request = parent::$base_url . "index.php/services/import_2/get_config";
    $request .= '?' . self::array_to_query_string($options['readAuth'] + ['data-file' => $_POST['data-file']]);
    $response = self::http_post($request, []);
    $config = json_decode($response['output'], TRUE);
    if (!is_array($config)) {
      throw new Exception('Service call to get_config failed.');
    }

    foreach ($config['columns'] as $column) {
      $select = "<select class=\"form-control\" name=\"$column\">$dbFieldOptions</select>";
      $htmlList[] = <<<HTML
<tr>
  <td>$column</td>
  <td>$select</td>
</tr>
HTML;
    }
    $tableRowsHtml = implode('', $htmlList);
    return <<<HTML
<h3>$lang[title]</h3>
<p>$lang[instructions]</p>
<form method="POST">
  <table class="table">
    <thead>
      <tr>
        <th>Column in import file</th>
        <th>Destination database field</th>
      <tr>
    </thead>
    <tbody>
      $tableRowsHtml
    </tbody>
  </table>
  <input type="submit" class="btn btn-primary" id="next-step" value="$lang[next]" />
  <input type="hidden" name="next-import-step" value="lookupMatchingForm" />
  <input type="hidden" name="data-file" id="data-file" value="{$_POST['data-file']}" />
</form>
HTML;
  }

  /**
   * Convert the list of available db fields to <option> elements.
   */
  private function getAvailableDbFieldsAsOptions(array $availableFields) {
    $lang = [
      'notImported' => lang::get('not imported'),
    ];
    $colsByGroup = [];
    $optGroup = '';
    foreach ($availableFields as $field => $caption) {
      $fieldParts = explode(':', $field);
      if ($optGroup !== ucfirst($fieldParts[0])) {
        $optGroup = ucfirst($fieldParts[0]);
        $colsByGroup[$optGroup] = [];
      }
      if (empty($caption)) {
        $caption = ucfirst(str_replace('_', ' ', $fieldParts[1]));
      }
      $colsByGroup[$optGroup][] = "<option value=\"$field\">$caption</option>";
    }
    $optGroupHtmlList = ["<option value=\"\">- $lang[notImported] -</option>"];
    foreach ($colsByGroup as $optgroup => $optionsList) {
      $options = implode('', $optionsList);
      $optGroupHtmlList[] = <<<HTML
<optgroup label="$optgroup">
  $options
</optgroup>
HTML;
    }
    return implode('', $optGroupHtmlList);
  }

  private static function savePostedFormValuesToConfig(array $options, $configFieldName) {
    $settings = array_merge([], $_POST);
    unset($settings['data-file']);
    unset($settings['next-import-step']);
    $request = parent::$base_url . 'index.php/services/import_2/save_config';
    self::http_post($request, $options['writeAuth'] + [
      'data-file' => $_POST['data-file'],
      $configFieldName => json_encode($settings),
    ]);
    return $settings;
  }

  private static function lookupMatchingForm($options) {
    helper_base::add_resource('autocomplete');
    helper_base::add_resource('validation');
    // Save the results of the previous mappings form.
    self::savePostedFormValuesToConfig($options, 'mappings');
    self::addLanguageStringsToJs('import_helper_2', [
      'dataValue' => 'Data value',
      'findingLookupFieldsThatNeedMatching' => 'Finding lookup fields that need matching.',
      'findLookupFieldsDone' => 'Finding lookup fields done.',
      'lookupFieldFound' => 'Lookup field found: {1}',
      'matchesToTaxon' => 'Matches to species or taxon name',
      'matchesToTerm' => 'Matches to term',
      'matchingPanelFor' => 'List of values to match for {1}',
      'pleaseMatchAllValues' => 'Matches saved, but there are more matches required for {1}.',
      'pleaseMatchValues' => 'Please match the values in the list before saving them.',
      'pleaseSelect' => '- Please select -',
      'savingMatchesFor' => 'Saving matches for {1}',
      'savedMatches' => 'Matches saved',
      'typeSpeciesNameToSearch' => 'Type the start of a species or taxon name to search',
    ]);
    $lang = [
      'backgroundProcessing' => lang::get('Background processing'),
      'instructions' => lang::get($options['lookupMatchingFormIntro']),
      'moreInfo' => lang::get('More info...'),
      'next' => lang::get('Next step'),
      'title' => lang::get('Value matching'),
    ];
    $loaderGif = self::$images_path . 'ajax-loader2.gif';
    self::$indiciaData['processLookupMatchingForFile'] = $_POST['data-file'];
    return <<<HTML
<h3>$lang[title]</h3>
<p>$lang[instructions]</p>
<form method="POST" id="lookup-matching-form">
  <div id="matching-area">
  </div>
  <div class="panel panel-info background-processing">
    <div class="panel-heading">
      $lang[backgroundProcessing]
      <br/><a data-toggle="collapse" class="small" href="#background-extra">$lang[moreInfo]</a>
    </div>
    <div id="background-extra" class="panel-body panel-collapse collapse"></div>
  </div>
  <input type="submit" class="btn btn-primary" id="next-step" value="$lang[next]" disabled />
  <input type="hidden" name="next-import-step" value="validationForm" />
  <input type="hidden" name="data-file" id="data-file" value="{$_POST['data-file']}" />
</form>
HTML;
  }

  private static function validationForm($options) {
    // @todo Validate the data before attempting the import.
    return self::summaryPage($options);
  }

  private static function summaryPage($options) {
    $lang = [
      'columnMappings' => lang::get('Column mappings'),
      'databaseField' => lang::get('Database field'),
      'dataValuesCopied' => lang::get('Values in this column are copied to this field'),
      'dataValuesIgnored' => lang::get('Values in this column are ignored'),
      'dataValuesMatched' => lang::get('Values in this column are matched to equivalent database values using the rules you supplied.'),
      'globalValues' => lang::get('Fixed global values'),
      'instructions' => lang::get($options['summaryPageIntro']),
      'importColumn' => lang::get('Import column'),
      'startImport' => lang::get('Start importing records'),
      'title' => lang::get('Import summary'),
      'value' => lang::get('Value'),
    ];
    $request = parent::$base_url . "index.php/services/import_2/get_config";
    $request .= '?' . self::array_to_query_string($options['readAuth'] + ['data-file' => $_POST['data-file']]);
    $response = self::http_post($request, []);
    $config = json_decode($response['output'], TRUE);
    if (!is_array($config)) {
      throw new Exception('Service call to get_config failed.');
    }
    $ext = pathinfo($config['fileName'], PATHINFO_EXTENSION);
    $mappingRows = [];
    foreach ($config['columns'] as $column => $tempFieldname) {
      $tempFieldNameIfFk = $tempFieldname . '_id';
      if (isset($config['mappings'][$tempFieldname])) {
        $mappedTo = $config['mappings'][$tempFieldname];
        $arrow = empty($mappedTo) ? "<i class=\"fas fa-stop\" title=\"$lang[dataValuesIgnored]\"></i>" : "<i class=\"fas fa-play\" title=\"$lang[dataValuesCopied]\"></i>";
        $mappingRows[] = "<tr><th scope=\"row\">$column</th><td>$arrow</td><td>$mappedTo</td></tr>";
      }
      elseif (isset($config['mappings'][$tempFieldNameIfFk])) {
        // Might be an FK/lookup field, so check if it is. We need to
        // reconstitute the fk_* version of the lookup field to check.
        $lookupFieldNameParts = explode(':', $config['mappings'][$tempFieldNameIfFk]);
        if (preg_match('/^[a-x]{3}Attr$/', $lookupFieldNameParts[0])) {
          // Attr lookups don't have _id in field name.
          $lookupFieldName = "$lookupFieldNameParts[0]:fk_" . $lookupFieldNameParts[1];
        }
        else {
          $lookupFieldName = "$lookupFieldNameParts[0]:fk_" . substr($lookupFieldNameParts[1], 0, strlen($lookupFieldNameParts[1]) - 3);
        }
        if (in_array($lookupFieldName, $config['lookupFields'])) {
          $mappedTo = $config['mappings'][$tempFieldNameIfFk];
          $arrow = "<i class=\"fas fa-random\" title=\"$lang[dataValuesMatched]\"></i><i class=\"fas fa-play\" title=\"$lang[dataValuesCopied]\"></i>";
          $mappingRows[] = "<tr><th scope=\"row\">$column</th><td>$arrow</td><td>$mappedTo</td></tr>";
        }
      }
      // @todo Correct mapping to stage attribute display
    }
    $mappings = implode('', $mappingRows);
    $globalRows = [];
    $arrow = "<i class=\"fas fa-play\" title=\"$lang[dataValuesCopied]\"></i>";
    foreach ($config['global-values'] as $field => $value) {
      // Foreign key filters were used during matching, not actually for import.
      if (strpos($field, 'fkFilter') === FALSE) {
        $globalRows[] = "<tr><th scope=\"row\">$value</th><td>$arrow</td><td>$field</td></tr>";
      }
    }
    $globals = implode('', $globalRows);
    $infoRows = [
      "<dt>File type</dt><dd>$ext</dd>",
      "<dt>Number of records</dt><dd>$config[totalRows]</dd>",
    ];
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
      $importFieldTableContent .= <<<HTML
<tbody>
  <tr>
    <th colspan="3" class="body-title">$lang[globalValues]</th>
  </tr>
  <tr>
    <th>$lang[value]</th><th></th><th>$lang[databaseField]</th>
  </tr>
  $globals
</tbody>
HTML;
    }
    $info .= "<table class=\"table\">$importFieldTableContent</table>";
    return <<<HTML
<h3>$lang[title]</h3>
<p>$lang[instructions]</p>
<form method="POST" id="summary-form">
  <div>
    $info
  </div>
  <div class="form-group">
    <label for="description">Description of your import:</label>
    <textarea class="form-control" rows="5" name="description"></textarea>
    <p class="helpText">Please describe the data you are about to import.</p>
  </div>
  <input type="submit" class="btn btn-primary" id="next-step" value="$lang[startImport]" />
  <input type="hidden" name="next-import-step" value="doImportPage" />
  <input type="hidden" name="data-file" id="data-file" value="{$_POST['data-file']}" />
</form>
HTML;
  }

  /**
   * Outputs the page that shows import progress.
   */
  private static function doImportPage($options) {
    $lang = [
      'errorsInImportFile' => lang::get('{1} rows with problems have been found in the import file.'),
      'importProgress' => lang::get('Import progress'),
    ];
    $lang['errorsInImportFile'] = str_replace('{1}', '<span class="error-count">0</span>', $lang['errorsInImportFile']);
    self::$indiciaData['readyToImport'] = TRUE;
    self::$indiciaData['dataFile'] = $_POST['data-file'];
    // Put the import description somewhere so it can be saved.
    self::$indiciaData['importDescription'] = $_POST['description'];
    return <<<HTML
<h3>$lang[importProgress]:</h3>
<progress id="file-progress" class="progress" value="0" max="100"></progress>
<div class="alert alert-danger clearfix" id="error-info" style="display: none">
  $lang[errorsInImportFile]
  <i class="fa-solid fa-triangle-exclamation fa-2x pull-right"></i>
</div>
HTML;
  }

  private static function getImportHelperOptions(&$options) {
    // Apply default options.
    $defaults = [
      'entity' => 'occurrence',
      'fileSelectFormIntro' => lang::get('import2fileSelectFormIntro'),
      'globalValuesFormIntro' => lang::get('import2globalValuesFormIntro'),
      'mappingsFormIntro' => lang::get('import2mappingsFormIntro'),
      'lookupMatchingFormIntro' => lang::get('lookupMatchingFormIntro'),
      'validationFormIntro' => lang::get('import2validationFormIntro'),
      'summaryPageIntro' => lang::get('summaryPageIntro'),
      'doImportFormIntro' => lang::get('import2doImportFormIntro'),
    ];
    $options = array_merge($defaults, $options);
    $requiredOptions = [
      'uploadFileUrl',
      'sendFileToWarehouseUrl',
      'loadChunkToTempTableUrl',
      'processLookupMatchingUrl',
      'saveLookupMatchesGroupUrl',
      'importChunkUrl',
    ];
    foreach ($requiredOptions as $requiredOption) {
      if (empty($options[$requiredOption])) {
        throw new exception("Import_helper_2::importer needs a $requiredOption option");
      }
    }
  }

  private static function getAvailableDbFields(array $options, array $settings) {
    $request = parent::$base_url . "index.php/services/import_2/get_fields/$options[entity]";
    $request .= '?' . self::array_to_query_string($options['readAuth']);
    // Include survey and website information in the request if available, as
    // this limits the availability of custom attributes.
    if (!empty($settings['website_id'])) {
      $request .= '&website_id=' . trim($settings['website_id']);
    }
    if (!empty($settings['survey_id'])) {
      $request .= '&survey_id=' . trim($settings['survey_id']);
    }
    if ($options['entity'] === 'sample'
        && isset($settings['sample:sample_method_id'])
        && trim($settings['sample:sample_method_id']) !== '') {
      $request .= '&sample_method_id=' . trim($settings['sample:sample_method_id']);
    }
    elseif ($options['entity'] === 'location'
        && isset($settings['location:location_type_id'])
        && trim($settings['location:location_type_id']) !== '') {
      $request .= '&location_type_id=' . trim($settings['location:location_type_id']);
    }
    elseif ($options['entity'] === 'taxa_taxon_list'
        && isset($settings['taxa_taxon_list:taxon_list_id'])
        && trim($settings['taxa_taxon_list:taxon_list_id']) !== '') {
      $request .= '&taxon_list_id=' . trim($settings['taxa_taxon_list:taxon_list_id']);
    }
    $response = self::http_post($request, []);
    return json_decode($response['output'], TRUE);
  }

}