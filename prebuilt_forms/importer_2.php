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

require_once 'includes/user.php';

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
    $fixedValuesDescription = <<<TXT
Provide a list of fixed values which the user does not need to specify in the import, one on each line in the form
key=value, where the key is a field name. The fixed values key field names should be chosen from those available for
input on the Global values page of the import wizard, depending on the table you are inputting data for. If a single
value is provided for a field key, then that field is removed from the Global values form and the value will be applied
to all rows created by the import. If multiple values are provided as a semi-colon separated list, then the user will
be able to choose the value to apply from a drop-down with a restricted range of options. Each entry in the list should
be the raw value to store in the database (e.g. a termlists_term_id rather than the term) and you can override the
label shown for the item by adding a colon followed by the required term. For example, to limit the default list and
further extend the available mapping systems with a custom projection you can specify the following, which includes
only 4326 (GPS lat long) from the default list, but adds an extra system for local use.<br/>
<code>sample:entered_sref_system=4326;32730:WGS84 UTM30S</code><br/>
It is also possible to specify single fixed values for the field names available for selection on the mappings
form.<br/>
You can use the following replacement tokens in the values: {user_id}, {username}, {email} or {profile_*} (i.e. any
field in the user profile data).
TXT;
    return [
      [
        'name' => 'fixedValues',
        'caption' => 'Fixed values',
        'description' => $fixedValuesDescription,
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
      [
        'name' => 'requiredFieldsIntro',
        'caption' => 'Required fields introduction',
        'category' => 'Instruction texts',
        'type' => 'textarea',
        'default' => $default_terms['import2requiredFieldsIntro'],
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
      'initServerConfigUrl' => hostsite_get_url('iform/ajax/importer_2') . "/init_server_config/$nid",
      'loadChunkToTempTableUrl' => hostsite_get_url('iform/ajax/importer_2') . "/load_chunk_to_temp_table/$nid",
      'getRequiredFieldsUrl' => hostsite_get_url('iform/ajax/importer_2') . "/get_required_fields/$nid",
      'processLookupMatchingUrl' => hostsite_get_url('iform/ajax/importer_2') . "/process_lookup_matching/$nid",
      'saveLookupMatchesGroupUrl' => hostsite_get_url('iform/ajax/importer_2') . "/save_lookup_matches_group/$nid",
      'importChunkUrl' => hostsite_get_url('iform/ajax/importer_2') . "/import_chunk/$nid",
      'getErrorFileUrl' => helper_base::$base_url . 'index.php/services/import_2/get_errors_file',
      'readAuth' => $auth['read'],
      'writeAuth' => $auth['write_tokens'],
      'entity' => 'occurrence',
      'fixedValues' => self::getDefaultFixedValues($auth),
    ], $args);
    $options['fixedValues'] = get_options_array_with_user_data($options['fixedValues']);
    return import_helper_2::importer($options);
  }

  /**
   * Ajax handler to handle file upload into interim location.
   */
  public static function ajax_upload_file($website_id, $password, $nid) {
    header('Content-type: application/json');
    iform_load_helpers(['import_helper_2']);
    echo json_encode([
      'status' => 'ok',
      'interimFile' => import_helper_2::uploadInterimFile(),
    ]);
  }

  /**
   * Ajax handler to handle transferring the file to the warehouse.
   */
  public static function ajax_send_file_to_warehouse($website_id, $password, $nid) {
    header('Content-type: application/json');
    $writeAuth = self::getAuthFromHeaders();
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

  /**
   * Ajax handler to handle transferring the file to the warehouse.
   */
  public static function ajax_extract_file_on_warehouse($website_id, $password, $nid) {
    header('Content-type: application/json');
    $writeAuth = self::getAuthFromHeaders();
    iform_load_helpers(['import_helper_2']);
    echo json_encode(import_helper_2::extractFileOnWarehouse($_GET['uploaded-file'], $writeAuth));
  }

  /**
   * Ajax handler to initialise the JSON config file on the warehouse.
   */
  public static function ajax_init_server_config($website_id, $password, $nid) {
    header('Content-type: application/json');
    $writeAuth = self::getAuthFromHeaders();
    iform_load_helpers(['import_helper_2']);
    echo json_encode(import_helper_2::initServerConfig(
      $_GET['data-file'],
      isset($_GET['import_template_id']) ? $_GET['import_template_id'] : NULL,
      $writeAuth
    ));
  }

  /**
   * Ajax handler to request that the warehouse loads a chunk of records.
   */
  public static function ajax_load_chunk_to_temp_table($website_id, $password, $nid) {
    header('Content-type: application/json');
    $writeAuth = self::getAuthFromHeaders();
    iform_load_helpers(['import_helper_2']);
    echo json_encode(import_helper_2::loadChunkToTempTable($_GET['data-file'], $writeAuth));
  }

  public static function ajax_get_required_fields($website_id, $password, $nid) {
    header('Content-type: application/json');
    $readAuth = self::getAuthFromHeaders();
    iform_load_helpers(['import_helper_2']);
    echo json_encode(import_helper_2::getRequiredFields($_GET['data-file'], $readAuth));
  }

  public static function ajax_process_lookup_matching($website_id, $password, $nid) {
    header('Content-type: application/json');
    $writeAuth = self::getAuthFromHeaders();
    iform_load_helpers(['import_helper_2']);
    echo json_encode(import_helper_2::processLookupMatching($_GET['data-file'], $_GET['index'], $writeAuth));
  }

  public static function ajax_save_lookup_matches_group($website_id, $password, $nid) {
    header('Content-type: application/json');
    $writeAuth = self::getAuthFromHeaders();
    iform_load_helpers(['import_helper_2']);
    echo json_encode(import_helper_2::saveLookupMatchesGroup($_GET['data-file'], $_POST, $writeAuth));
  }

  public static function ajax_import_chunk($website_id, $password, $nid) {
    header('Content-type: application/json');
    $writeAuth = self::getAuthFromHeaders();
    iform_load_helpers(['import_helper_2']);
    $params = array_merge($_POST);
    // Convert string data to bool.
    $params['restart'] = !empty($params['restart']) && $params['restart'] === 'true';
    $params['precheck'] = !empty($params['precheck']) && $params['precheck'] === 'true';
    $params['forceTemplateOverwrite'] = !empty($params['forceTemplateOverwrite']) && $params['forceTemplateOverwrite'] === 'true';
    $response = import_helper_2::importChunk(
      $_GET['data-file'],
      $params,
      $writeAuth
    );
    echo json_encode($response);
  }

  /**
   * Retrieve any default fixed values.
   *
   * Values that apply to all rows, e.g. group_id for group linked forms.
   *
   * @param array $auth
   *   Authorisation tokens.
   *
   * @return array
   *   Key/value pairs of fixed values.
   */
  private static function getDefaultFixedValues($auth) {
    $fixedValues = [];
    if (!empty($_GET['group_id'])) {
      // Loading data into a recording group.
      $group = data_entry_helper::get_population_data([
        'table' => 'group',
        'extraParams' => $auth['read'] + [
          'id' => $_GET['group_id'],
          'view' => 'detail',
        ],
      ]);
      $group = $group[0];
      if (!empty($model) && $model === 'groups_location') {
        $presets['groups_location:group_id'] = $_GET['group_id'];
      }
      else {
        $presets['sample:group_id'] = $_GET['group_id'];
      }
      hostsite_set_page_title(lang::get('Import data into the {1} group', $group['title']));
      // If a single survey specified for this group, then force the data into
      // the correct survey.
      $filterdef = json_decode($group['filter_definition'], TRUE);
      if (!empty($filterdef['survey_list_op']) && $filterdef['survey_list_op'] === 'in' && !empty($filterdef['survey_list'])) {
        $surveys = explode(',', $filterdef['survey_list']);
        if (count($surveys) === 1) {
          $presets['survey_id'] = $surveys[0];
        }
      }
    }
    return $fixedValues;
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
