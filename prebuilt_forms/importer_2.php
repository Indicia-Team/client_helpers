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
 * @link http://code.google.com/p/indicia/
 */

/**
 * 2nd generation import data tool.
 */
class iform_importer_2 {

  /**
   * Disable form element wrapped around output.
   *
   * @return bool
   *   Always false.
   */
  protected static function isDataEntryForm() {
    return FALSE;
  }

  /**
   * Return the form metadata.
   *
   * Note the title of this method includes the name of the form file. This
   * ensures that if inheritance is used in the forms, subclassed forms don't
   * return their parent's form definition.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_importer_2_definition() {
    return [
      'title' => 'Importer 2',
      'category' => 'Experimental',
      'description' => 'A page containing a wizard for uploading CSV file data.',
      'supportsGroups' => TRUE,
      'recommended' => TRUE,
    ];
  }

  /**
   * Get the list of parameters for this form.
   *
   * @return array
   *   List of parameters that this form requires.
   */
  public static function get_parameters() {
    // @todo Entity
    // @todo Nested samples

    // Load for access to translations provided as lang::get might not be
    // available in AJAX call.
    require_once dirname(dirname(__FILE__)) . '/lang/import_helper_2.php';
    global $default_terms;
    $presetSettingsDescription = <<<TXT
Provide a list of predetermined settings which the user does not need to specify, one on each line in the form
name=value. The preset settings available should be chosen from those available for input on the Import settings page
of the import wizard, depending on the table you are inputting data for. It is also possible to specify preset settings
for the field names available for selection on the mappings page. You can use the following replacement tokens in the
values: {user_id}, {username}, {email} or {profile_*} (i.e. any field in the user profile data).
TXT;
    return [
      [
        'name' => 'presetSettings',
        'caption' => 'Preset Settings',
        'description' => $presetSettingsDescription,
        'type' => 'textarea',
        'required' => FALSE,
      ],
      [
        'name' => 'fileSelectFormIntro',
        'caption' => 'File select form introduction',
        'category' => 'Instruction texts',
        'type' => 'textarea',
        'default' => $default_terms['import2fileSelectFormIntro'],
        'required' => FALSE,
      ],
      [
        'name' => 'globalValuesFormIntro',
        'caption' => 'Global values form introduction',
        'category' => 'Instruction texts',
        'type' => 'textarea',
        'default' => $default_terms['import2globalValuesFormIntro'],
        'required' => FALSE,
      ],
      [
        'name' => 'mappingsFormIntro',
        'caption' => 'Mappings form introduction',
        'category' => 'Instruction texts',
        'type' => 'textarea',
        'default' => $default_terms['import2mappingsFormIntro'],
        'required' => FALSE,
      ],
      [
        'name' => 'lookupMatchingFormIntro',
        'caption' => 'Mappings form introduction',
        'category' => 'Instruction texts',
        'type' => 'textarea',
        'default' => $default_terms['import2lookupMatchingFormIntro'],
        'required' => FALSE,
      ],
      [
        'name' => 'validationFormIntro',
        'caption' => 'Validation form introduction',
        'category' => 'Instruction texts',
        'type' => 'textarea',
        'default' => $default_terms['import2validationFormIntro'],
        'required' => FALSE,
      ],
      [
        'name' => 'summaryPageIntro',
        'caption' => 'Summary page introduction',
        'category' => 'Instruction texts',
        'type' => 'textarea',
        'default' => $default_terms['import2summaryPageIntro'],
        'required' => FALSE,
      ],
      [
        'name' => 'doImportPageIntro',
        'caption' => 'Process the import page introduction',
        'category' => 'Instruction texts',
        'type' => 'textarea',
        'default' => $default_terms['import2doImportPageIntro'],
        'required' => FALSE,
      ],
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
    iform_load_helpers(['import_helper_2']);
    $connection = iform_get_connection_details($nid);
    $auth = import_helper_2::get_read_write_auth($connection['website_id'], $connection['password']);
    $options = array_merge([
      'uploadFileUrl' => hostsite_get_url('iform/ajax/importer_2') . "/upload_file/$nid",
      'sendFileToWarehouseUrl' => hostsite_get_url('iform/ajax/importer_2') . "/send_file_to_warehouse/$nid",
      'extractFileOnWarehouseUrl' => hostsite_get_url('iform/ajax/importer_2') . "/extract_file_on_warehouse/$nid",
      'loadChunkToTempTableUrl' => hostsite_get_url('iform/ajax/importer_2') . "/load_chunk_to_temp_table/$nid",
      'processLookupMatchingUrl' => hostsite_get_url('iform/ajax/importer_2') . "/process_lookup_matching/$nid",
      'saveLookupMatchesGroupUrl' => hostsite_get_url('iform/ajax/importer_2') . "/save_lookup_matches_group/$nid",
      'importChunkUrl' => hostsite_get_url('iform/ajax/importer_2') . "/import_chunk/$nid",
      'readAuth' => $auth['read'],
      'writeAuth' => $auth['write_tokens'],
      'entity' => 'occurrence',
    ], $args);
    return import_helper_2::importer($options);
  }

  /**
   * Ajax handler to handle file upload into interim location.
   */
  public static function ajax_upload_file($website_id, $password, $nid) {
    header('Content-type: application/json');
    try {
      if (!self::getWriteAuthFromHeaders()) {
        return;
      }
      iform_load_helpers(['import_helper_2']);
      echo json_encode([
        'status' => 'ok',
        'interimFile' => import_helper_2::uploadInterimFile(),
      ]);
    }
    catch (Exception $e) {
      // Something went wrong, send the error message as JSON.
      http_response_code(400);

      echo json_encode([
        'status' => 'error',
        'msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Ajax handler to handle transferring the file to the warehouse.
   */
  public static function ajax_send_file_to_warehouse($website_id, $password, $nid) {
    header('Content-type: application/json');
    try {
      $writeAuth = self::getWriteAuthFromHeaders();
      if (!$writeAuth) {
        return;
      }
      iform_load_helpers(['import_helper_2']);
      $r = import_helper_2::sendFileToWarehouse($_GET['interim-file'], $writeAuth);
      if ($r === TRUE) {
        echo json_encode([
          'status' => 'ok',
          // Warehouse lowercases the file name and replaces spaces.
          'uploadedFile' => strtolower(str_replace(' ', '_', $_GET['interim-file'])),
        ]);
      }
      else {
        throw new Exception(var_export($r, TRUE));
      }
    }
    catch (Exception $e) {
      // Something went wrong, send the error message as JSON.
      \Drupal::logger('iform')->error('Error in ajax_send_file_to_warehouse: ' . $e->getMessage());
      echo json_encode([
        'status' => 'error',
        'msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Ajax handler to handle transferring the file to the warehouse.
   */
  public static function ajax_extract_file_on_warehouse($website_id, $password, $nid) {
    header('Content-type: application/json');
    try {
      $writeAuth = self::getWriteAuthFromHeaders();
      if (!$writeAuth) {
        return;
      }
      iform_load_helpers(['import_helper_2']);
      echo json_encode(import_helper_2::extractFileOnWarehouse($_GET['uploaded-file'], $writeAuth));
    }
    catch (Exception $e) {
      // Something went wrong, send the error message as JSON.
      \Drupal::logger('iform')->error('Error in ajax_extract_file_on_warehouse: ' . $e->getMessage());
      echo json_encode([
        'status' => 'error',
        'msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Ajax handler to request that the warehouse loads a chunk of records.
   */
  public static function ajax_load_chunk_to_temp_table($website_id, $password, $nid) {
    header('Content-type: application/json');
    try {
      $writeAuth = self::getWriteAuthFromHeaders();
      if (!$writeAuth) {
        http_response_code(401);
        echo json_encode([
          'status' => 'error',
          'msg' => 'Unauthorized',
        ]);
      }
      iform_load_helpers(['import_helper_2']);
      echo json_encode(import_helper_2::loadChunkToTempTable($_GET['data-file'], $writeAuth));
    }
    catch (Exception $e) {
      // Something went wrong, send the error message as JSON.
      http_response_code(400);

      echo json_encode([
        'status' => 'error',
        'msg' => $e->getMessage(),
      ]);
    }
  }

  public static function ajax_process_lookup_matching($website_id, $password, $nid) {
    header('Content-type: application/json');
    try {
      $writeAuth = self::getWriteAuthFromHeaders();
      if (!$writeAuth) {
        return;
      }
      iform_load_helpers(['import_helper_2']);
      echo json_encode(import_helper_2::processLookupMatching($_GET['data-file'], $_GET['index'], $writeAuth));
    }
    catch (Exception $e) {
      // Something went wrong, send the error message as JSON.
      http_response_code(400);
      echo json_encode([
        'status' => 'error',
        'msg' => $e->getMessage(),
      ]);
    }
  }

  public static function ajax_save_lookup_matches_group($website_id, $password, $nid) {
    header('Content-type: application/json');
    try {
      $writeAuth = self::getWriteAuthFromHeaders();
      if (!$writeAuth) {
        return;
      }
      iform_load_helpers(['import_helper_2']);
      echo json_encode(import_helper_2::saveLookupMatchesGroup($_GET['data-file'], $_POST, $writeAuth));
    }
    catch (Exception $e) {
      // Something went wrong, send the error message as JSON.
      http_response_code(400);
      echo json_encode([
        'status' => 'error',
        'msg' => $e->getMessage(),
      ]);
    }
  }

  public static function ajax_import_chunk($website_id, $password, $nid) {
    header('Content-type: application/json');
    try {
      $writeAuth = self::getWriteAuthFromHeaders();
      if (!$writeAuth) {
        return;
      }
      iform_load_helpers(['import_helper_2']);
      echo json_encode(import_helper_2::importChunk($_GET['data-file'], isset($_POST['description']) ? $_POST['description'] : NULL, $writeAuth));
    }
    catch (Exception $e) {
      // Something went wrong, send the error message as JSON.
      http_response_code(400);
      echo json_encode([
        'status' => 'error',
        'msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Asserts that the request contains write tokens in the headers.
   *
   * @return array|bool
   *   Write tokens object, else false and echoes a suitable response.
   */
  private static function getWriteAuthFromHeaders() {
    $headers = getallheaders();
    if (!empty($headers['Authorization'])) {
      if (preg_match('/Bearer (?<auth_token>[a-z0-9]+(:\d+)?)\|(?<nonce>[a-z0-9]+)/', $headers['Authorization'], $matches)) {
        return [
          'auth_token' => $matches['auth_token'],
          'nonce' => $matches['nonce'],
        ];
      }
    }

    http_response_code(401);
    echo json_encode([
      'status' => 'error',
      'msg' => 'Unauthorized',
    ]);
    return FALSE;
  }

}
