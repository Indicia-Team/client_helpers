<?php

/**
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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/Indicia-Team/client_helpers
 */

/**
 * A form for uploading custom verification rules into a ruleset.
 */
class iform_custom_verification_rules_upload {

  /**
   * Disable form element wrapped around output.
   *
   * @return bool
   *   False as not a standard data entry form.
   */
  protected static function isDataEntryForm() {
    return FALSE;
  }

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_custom_verification_rules_upload_definition() {
    return [
      'title' => 'Custom verification rules upload tool',
      'category' => 'Utilities',
      'description' => 'A tool for uploading spreadsheets of verification rules.',
      'helpLink' => 'https://indicia-docs.readthedocs.io/en/latest/site-building/iform/prebuilt-forms/custom-verification-rules-upload.html',
    ];
  }

  /**
   * Get the list of parameters for this form.
   *
   * @return array
   *   List of parameters that this form requires.
   */
  public static function get_parameters() {
    return [
      [
        'name' => 'taxon_list_id',
        'caption' => 'Taxon List ID',
        'description' => 'Taxon list that will be used to lookup taxa associated with rules.',
        'type' => 'int',
        'required' => TRUE,
      ]
    ];
  }

  /**
   * Return the generated form output.
   *
   * @param array $args
   *   List of parameter values passed through to the form depending on how the
   *   form has been configured. This array always contains a value for
   *   language.
   * @param object $nid
   *   The Drupal node object's ID.
   * @param array $response
   *   When this form is reloading after saving a submission, contains the
   *   response from the service call. Note this does not apply when
   *   redirecting (in this case the details of the saved object are in the
   *   $_GET data).
   *
   * @return string
   *   Form HTML.
   */
  public static function get_form($args, $nid, $response = NULL) {
    if (empty($_GET['custom_verification_ruleset_id']) || !preg_match('/^\d+$/', trim($_GET['custom_verification_ruleset_id']))) {
      return lang::get('The custom verification rules upload tool requires a URL parameter custom_verification_ruleset_id to identify the rule to upload into.');
    }
    $rulesetId = trim($_GET['custom_verification_ruleset_id']);
    $conn = iform_get_connection_details($nid);
    $auth = helper_base::get_read_write_auth($conn['website_id'], $conn['password']);

    $rulesets = data_entry_helper::get_population_data([
      'table' => 'custom_verification_ruleset',
      'extraParams' => $auth['read'] + [
        'id' => $rulesetId,
        'created_by_id' => hostsite_get_user_field('indicia_user_id'),
      ],
    ]);
    if (empty($rulesets)) {
      return lang::get('No ruleset exists with that ID which belongs to you.');
    }
    helper_base::add_resource('font_awesome');
    helper_base::add_resource('dmUploader');
    $ruleset = $rulesets[0];
    $lang = [
      'browseFiles' => lang::get('Browse files'),
      'clickToAdd' => lang::get('Click to add files'),
      'pageTitle' => lang::get('Upload rules into the {1} ruleset', $ruleset['title']),
      'progress' => lang::get('Progress'),
      'selectAFile' => lang::get('Select a CSV or Excel file or drag it over this area. If importing an Excel file, only the first worksheet will be imported.'),
      'uploadRules' => lang::get('Upload rules'),
    ];
    helper_base::addLanguageStringsToJs('custom_verification_rules_upload', [
      'done' => 'The rules have been successfully imported.',
      'importContents' => 'Importing the rules...',
      'invalidType' => 'The chosen file was not a type of file that can be imported.',
      'problemsFound' => 'The following problems were found in the rules upload file during validation checks. Please rectify them before attempting the upload again.',
      'removeUploadedFileHint' => 'Remove the uploaded file',
      'selectedFile' => 'Selected {1} file',
      'uploadFailedWithError' => 'The file upload failed. The error message was:<br/>{1}.',
      'uploadingFile' => 'Uploading rules file...',
      'validateContents' => 'Validating the rules...',
      'validateStructure' => 'Validating the file format...',
    ]);
    global $indicia_templates;
    hostsite_set_page_title($lang['pageTitle']);
    $r = <<<HTML
<form id="file-upload-form" method="POST">
  <div class="dm-uploader row">
    <div class="col-md-9">
      <div role="button" class="btn btn-primary">
        <i class="fas fa-file-upload"></i>
        $lang[browseFiles]
        <input type="file" title="$lang[clickToAdd]">
      </div>
      <small class="status text-muted">$lang[selectAFile]</small>
    </div>
    <div class="col-md-3" id="uploaded-files"></div>
  </div>
  <progress id="file-progress" class="progress" value="0" max="100" style="display: none"></progress>
  <button id="upload-rules" type="button" class="$indicia_templates[buttonHighlightedClass]" disabled>$lang[uploadRules]</button>
  <input type="hidden" name="next-import-step" value="globalValuesForm" />
  <input type="hidden" name="interim-file" id="interim-file" value="" />
</form>
<div class="panel panel-info" id="progress-output-cntr" style="display: none">
  <div class="panel panel-heading">$lang[progress]</div>
  <div class="panel panel-body" id="progress-output"></div>
</div>
HTML;
    helper_base::$indiciaData['write'] = $auth['write_tokens'];
    helper_base::$indiciaData['taxon_list_id'] = $args['taxon_list_id'];
    helper_base::$indiciaData['custom_verification_ruleset_id'] = $rulesetId;
    helper_base::$indiciaData['importerDropArea'] = '.dm-uploader';
    helper_base::$indiciaData['uploadFileUrl'] = hostsite_get_url('iform/ajax/custom_verification_rules_upload') . "/upload_interim_file/$nid";
    helper_base::$indiciaData['uploadRulesStepUrl'] = hostsite_get_url('iform/ajax/custom_verification_rules_upload') . "/upload_rules_step/$nid";
    helper_base::$indiciaData['templates'] = [
      'warningBox' => $indicia_templates['warningBox'],
    ];
    return $r;
  }

  /**
   * Upload interim file AJAX handler.
   *
   * Provides functionality that can receive an selected file and saves it to
   * the interim location on the client website's server.
   *
   * Echoes a JSON response including the name of the uploaded file.
   */
  public static function ajax_upload_interim_file() {
    iform_load_helpers(['helper_base']);
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
    $targetDir = helper_base::getInterimImageFolder('fullpath');
    $fileName = sprintf('%s_%s', uniqid(), $_FILES['file']['name']);
    $filePath = $targetDir . $fileName;

    if (!move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
      throw new RuntimeException('Failed to move uploaded file.');
    }

    // All good.
    echo json_encode([
      'status' => 'ok',
      'interimFile' => $fileName,
    ]);
  }

  /**
   * AJAX controller method to perform the next step in an upload.
   *
   * Performs steps in this sequence:
   * * sendFileToWarehouse
   * * validateStructure
   * * validateContents
   * * importContents.
   */
  public static function ajax_upload_rules_step() {
    header('Content-type: application/json');
    $state = $_POST['state'] ?? 'sendFileToWarehouse';
    if ($state === 'sendFileToWarehouse') {
      echo json_encode([
        'uploadedFile' => self::sendFileToWarehouse(),
        'status' => 'ok',
        'nextState' => 'validateStructure',
      ]);
    }
    elseif ($state === 'validateStructure') {
      self::echoProxyResponse(self::validateStructure(), 'validateContents');
    }
    elseif ($state === 'validateContents') {
      self::echoProxyResponse(self::validateContents(), 'importContents');
    }
    elseif ($state === 'importContents') {
      self::echoProxyResponse(self::importContents(), 'done');
    }
  }

  /**
   * Echoes the response from a warehouse request back to the proxy caller.
   *
   * @param array $r
   *   Warehouse response.
   * @param string $nextState
   *   Next step state name to return if the response indicates success.
   */
  private static function echoProxyResponse(array $r, $nextState) {
    if (isset($r['output'])) {
      $output = json_decode($r['output'], TRUE);
      if ($output) {
        $output['nextState'] = $nextState;
      }
    }
    if (empty($output)) {
      // Something really broken - no response output.
      $output = [
        'status' => 'error',
        'error' => 'An error occurred during validation of the spreadsheet contents.',
        'response' => $r,
      ];
    }
    echo json_encode($output);
  }

  /**
   * Handles the sendFileToWarehouse import step.
   *
   * Places the interim upload file into a folder on the warehouse, ready for
   * validation and import.
   */
  private static function sendFileToWarehouse() {
    $writeAuth = self::getAuthFromHeaders();
    iform_load_helpers(['helper_base']);
    $r = helper_base::send_file_to_warehouse($_POST['interimFile'], TRUE, $writeAuth, 'custom_verification_rules/upload_file', TRUE);
    if ($r === TRUE) {
      // Warehouse lowercases the file name and replaces spaces.
      return strtolower(str_replace(' ', '_', $_POST['interimFile']));
    }
    else {
      throw new Exception(var_export($r, TRUE));
    }
  }

  /**
   * Handles the validateStructure import step.
   *
   * Requests that the warehouse should validate the column structure of an
   * uploaded file.
   */
  private static function validateStructure() {
    $writeAuth = self::getAuthFromHeaders();
    iform_load_helpers(['helper_base']);
    $serviceUrl = helper_base::$base_url . "index.php/services/custom_verification_rules/validate_structure";
    $data = $writeAuth + [
      'uploadedFile' => $_POST['uploadedFile'],
      'persist_auth' => TRUE,
      'taxon_list_id' => $_POST['taxon_list_id'],
      'custom_verification_ruleset_id' => $_POST['custom_verification_ruleset_id'],
    ];
    return helper_base::http_post($serviceUrl, $data, FALSE);
  }

  /**
   * Handles the validateContents import step.
   *
   * Requests that the warehouse should validate the contents of an uploaded
   * file.
   */
  private static function validateContents() {
    $writeAuth = self::getAuthFromHeaders();
    iform_load_helpers(['helper_base']);
    $serviceUrl = helper_base::$base_url . "index.php/services/custom_verification_rules/validate_contents";
    $data = $writeAuth + [
      'uploadedFile' => $_POST['uploadedFile'],
      'persist_auth' => TRUE,
      'taxon_list_id' => $_POST['taxon_list_id'],
      'custom_verification_ruleset_id' => $_POST['custom_verification_ruleset_id'],
    ];
    return helper_base::http_post($serviceUrl, $data, FALSE);
  }

  /**
   * Handles the importContents import step.
   *
   * Requests that the warehouse should import the contents of an uploaded
   * file.
   */
  private static function importContents() {
    $writeAuth = self::getAuthFromHeaders();
    iform_load_helpers(['helper_base']);
    $serviceUrl = helper_base::$base_url . "index.php/services/custom_verification_rules/import_contents";
    $data = $writeAuth + [
      'uploadedFile' => $_POST['uploadedFile'],
      'persist_auth' => TRUE,
      'taxon_list_id' => $_POST['taxon_list_id'],
      'custom_verification_ruleset_id' => $_POST['custom_verification_ruleset_id'],
    ];
    return helper_base::http_post($serviceUrl, $data, FALSE);
  }

  /**
   * Asserts that the request contains write tokens in the headers.
   *
   * @return array|bool
   *   Write tokens object, else false and echoes a suitable response.
   */
  private static function getAuthFromHeaders() {
    $headers = getallheaders();
    if (!empty($headers['Authorization'])) {
      if (preg_match('/IndiciaTokens (?<auth_token>[a-z0-9]+(:\d+)?)\|(?<nonce>[a-z0-9]+)/', $headers['Authorization'], $matches)) {
        return [
          'auth_token' => $matches['auth_token'],
          'nonce' => $matches['nonce'],
        ];
      }
    }
    hostsite_access_denied();
  }

}
