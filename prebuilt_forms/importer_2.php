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
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/Indicia-Team/client_helpers
 */

use IForm\prebuilt_forms\PageType;
use IForm\prebuilt_forms\PrebuiltFormInterface;

require_once 'includes/user.php';

/**
 * Second generation import data tool.
 */
class iform_importer_2 implements PrebuiltFormInterface {

  /**
   * Return the form metadata.
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
   * {@inheritDoc}
   */
  public static function getPageType(): PageType {
    return PageType::Utility;
  }

  /**
   * Get the list of parameters for this form.
   *
   * @param array $readAuth
   *   Read authorisation tokens.
   *
   * @return array
   *   List of parameters that this form requires.
   */
  public static function get_parameters(array $readAuth) {
    // @todo Entity
    // @todo Nested samples

    // Load for access to translations provided as lang::get might not be
    // available in AJAX call.
    require_once dirname(dirname(__FILE__)) . '/lang/import_helper_2.php';
    global $default_terms;
    $fixedValuesDescription = <<<TXT
<p>Provide a list of fixed values which the user does not need to specify in the import, one on each line in the form
key=value, where the key is a field name. The fixed values key field names should be chosen from those available for
input on the Import settings page of the import wizard or the fields available on the Mappings page. </p>
<p>Fields on the Import settings page behave as follows. If a single value is provided for a field key, then that field
is removed from the Import settings form and the value will be applied to all rows created by the import. If multiple
values are provided as a semi-colon separated list, then the user will be able to choose the value to apply from a
drop-down with a restricted range of options. Each entry in the list should be the raw value to store in the database
(e.g. a termlists_term_id rather than the term) and you can override the label shown for the item by adding a colon
followed by the required term. A further possibility is to specify an asterisk '*' as a special value, if present in
the list for a control then the user is given the option to remove the restrictions and select from any possible value
for the control. For example, to limit the default list and further extend the available mapping systems with a custom
projection you can specify the following, which includes only 4326 (GPS lat long) from the default list, but adds an
extra system for local use. The * at the end gives the user an option to show the whole list of available reference
systems:</p>
<code>sample:entered_sref_system=4326;32730:WGS84 UTM30S;*</code><br/>
<p>If the field keys are from the Import mappings page, then a single value can be provided which sets the value for
every imported record.</p>
<p>You can use the following replacement tokens in the values: {user_id}, {username}, {email} or {profile_*} (i.e. any
field in the user profile data).</p>
TXT;

// @todo Make sure the state of the advanced toggle is saved in a template

    $advancedFieldsDefault = <<<TXT
occurrence:confidential
occurrence:fk_taxa_taxon_list:external_key
occurrence:fk_taxa_taxon_list:genus
occurrence:fk_taxa_taxon_list:id
occurrence:fk_taxa_taxon_list:specific
occurrence:import_guid
occurrence:record_status
occurrence:record_substatus
occurrence:release_status
occurrence:zero_abundance
occurrence_medium:caption:1
occurrence_medium:caption:2
occurrence_medium:caption:3
occurrence_medium:caption:4
occurrence_medium:path:1
occurrence_medium:path:2
occurrence_medium:path:3
occurrence_medium:path:4
sample:date_end
sample:date_start
sample:date_type
sample:date:day
sample:date:month
sample:date:year
sample:entered_sref_system
sample:external_key
sample:fk_group
sample:fk_licence
sample:fk_licence:code
sample:fk_location
sample:fk_location:code
sample:fk_location:external_key
sample:fk_location:id
sample:input_form
sample:privacy_precision
sample:record_status
sample:sensitivity_precision
TXT;
    $params = [
      [
        'name' => 'fixedValues',
        'caption' => 'Fixed values',
        'description' => $fixedValuesDescription,
        'type' => 'textarea',
        'required' => FALSE,
      ],
      [
        'name' => 'fixedValueDefaults',
        'caption' => 'Fixed value defaults',
        'description' => 'Specify default values for any item in the Fixed values list above where the key is a control from the Import Settings page that has a list of available options.',
        'type' => 'textarea',
        'required' => FALSE,
      ],
      [
        'name' => 'advancedFields',
        'caption' => 'Advanced fields',
        'description' => 'Fields that will be hidden unless the option to show advanced fields is selected.',
        'type' => 'textarea',
        'required' => FALSE,
        'default' => $advancedFieldsDefault,
      ],
      [
        'name' => 'allowUpdates',
        'caption' => 'Allow updates?',
        'description' => 'Allows existing records to be update by allowing an import field to be mapped to either an "id" field or an "External Key" field. Only allows updates of the user\'s own records.',
        'type' => 'boolean',
        'default' => FALSE,
        'required' => FALSE,
      ],
      [
        'name' => 'allowDeletes',
        'caption' => 'Allow deletions?',
        'description' => 'Allows existing records to be deleted by enabling the Deleted flag field as a destination column which an import field can be mapped to. Requires the Allow updates option to be set and only allows deletes of the user\'s own records.',
        'type' => 'boolean',
        'default' => FALSE,
        'required' => FALSE,
        'enableIf' => ['allowUpdates' => ['1']],
      ],
      [
        'name' => 'fileSelectFormIntro',
        'caption' => 'File select form introduction',
        'group' => 'Instruction texts',
        'type' => 'textarea',
        'default' => $default_terms['import2fileSelectFormIntro'],
        'required' => FALSE,
      ],
      [
        'name' => 'globalValuesFormIntro',
        'caption' => 'Global values form introduction',
        'group' => 'Instruction texts',
        'type' => 'textarea',
        'default' => $default_terms['import2globalValuesFormIntro'],
        'required' => FALSE,
      ],
      [
        'name' => 'mappingsFormIntro',
        'caption' => 'Mappings form introduction',
        'group' => 'Instruction texts',
        'type' => 'textarea',
        'default' => $default_terms['import2mappingsFormIntro'],
        'required' => FALSE,
      ],
      [
        'name' => 'lookupMatchingFormIntro',
        'caption' => 'Mappings form introduction',
        'group' => 'Instruction texts',
        'type' => 'textarea',
        'default' => $default_terms['import2lookupMatchingFormIntro'],
        'required' => FALSE,
      ],
      [
        'name' => 'preprocessPageIntro',
        'caption' => 'Validation form introduction',
        'group' => 'Instruction texts',
        'type' => 'textarea',
        'default' => $default_terms['import2preprocessPageIntro'],
        'required' => FALSE,
      ],
      [
        'name' => 'summaryPageIntro',
        'caption' => 'Summary page introduction',
        'group' => 'Instruction texts',
        'type' => 'textarea',
        'default' => $default_terms['import2summaryPageIntro'],
        'required' => FALSE,
      ],
      [
        'name' => 'doImportPageIntro',
        'caption' => 'Process the import page introduction',
        'group' => 'Instruction texts',
        'type' => 'textarea',
        'default' => $default_terms['import2doImportPageIntro'],
        'required' => FALSE,
      ],
      [
        'name' => 'requiredFieldsIntro',
        'caption' => 'Required fields introduction',
        'group' => 'Instruction texts',
        'type' => 'textarea',
        'default' => $default_terms['import2requiredFieldsIntro'],
        'required' => FALSE,
      ],
    ];
    $requestParams = $readAuth + ['entity' => 'occurrence'];
    $request = helper_base::$base_url . 'index.php/services/import_2/get_plugins?' . http_build_query($requestParams);
    $pluginsResponse = helper_base::http_post($request);
    if (isset($pluginsResponse['output'])) {
      $plugins = json_decode($pluginsResponse['output'], TRUE);
      if (count($plugins) > 0) {
        // Add a checkbox plus a parameters input for each.
        foreach ($plugins as $name => $description) {
          $pluginLabel = preg_replace('/(?<!\ )[A-Z]/', ' $0', $name);
          $params[] = [
            'name' => "plugin-$name",
            'caption' => "Enable plugin <em>$pluginLabel</em>",
            'description' => $description,
            'type' => 'checkbox',
            'required' => FALSE,
          ];
          $params[] = [
            'name' => "pluginparams-$name",
            'caption' => "Parameters for plugin <em>$pluginLabel</em>",
            'description' => 'Comma separated list of parameters for the plugin.',
            'type' => 'text_input',
            'required' => FALSE,
          ];
        }
      }
    }

    return $params;
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
      'processLookupMatchingUrl' => hostsite_get_url('iform/ajax/importer_2') . "/process_lookup_matching/$nid",
      'preprocessUrl' => hostsite_get_url('iform/ajax/importer_2') . "/preprocess/$nid",
      'saveLookupMatchesGroupUrl' => hostsite_get_url('iform/ajax/importer_2') . "/save_lookup_matches_group/$nid",
      'importChunkUrl' => hostsite_get_url('iform/ajax/importer_2') . "/import_chunk/$nid",
      'getErrorFileUrl' => helper_base::$base_url . 'index.php/services/import_2/get_errors_file',
      'readAuth' => $auth['read'],
      'writeAuth' => $auth['write_tokens'],
      'fixedValues' => [],
      'entity' => 'occurrence',
      'advancedFields' => '',
    ], $args);
    $options['fixedValues'] = empty($options['fixedValues']) ? [] : get_options_array_with_user_data($options['fixedValues']);
    $options['fixedValues'] = array_merge($options['fixedValues'], self::getAdditionalFixedValues($auth, $options['entity']));
    if (!empty($options['fixedValueDefaults'])) {
      $options['fixedValueDefaults'] = get_options_array_with_user_data($options['fixedValueDefaults']);
    }
    $options['advancedFields'] = helper_base::explode_lines($options['advancedFields']);
    return import_helper_2::importer($options);
  }

  /**
   * Ajax handler to handle file upload into interim location.
   *
   * @param int $website_id
   *   Warehouse website ID.
   * @param string $password
   *   Warehouse website password.
   * @param int $nid
   *   Node ID.
   *
   * @return array
   *   Progress data from the warehouse.
   */
  public static function ajax_upload_file($website_id, $password, $nid) {
    iform_load_helpers(['import_helper_2']);
    return [
      'status' => 'ok',
      'interimFile' => import_helper_2::uploadInterimFile(),
      'originalName' => $_FILES['file']['name'],
    ];
  }

  /**
   * Ajax handler to handle transferring the file to the warehouse.
   *
   * @param int $website_id
   *   Warehouse website ID.
   * @param string $password
   *   Warehouse website password.
   * @param int $nid
   *   Node ID.
   *
   * @return array
   *   Progress data from the warehouse.
   */
  public static function ajax_send_file_to_warehouse($website_id, $password, $nid) {
    $writeAuth = self::getAuthFromHeaders();
    iform_load_helpers(['import_helper_2']);
    $interimPath = import_helper_2::getInterimImageFolder('fullpath');
    if (!file_exists($interimPath . $_GET['interim-file'])) {
      // Assume the page has been loaded twice and the file already sent. If
      // not we will get other errors anyway.
      return [
        'status' => 'ok',
        // Warehouse lowercases the file name and replaces spaces.
        'uploadedFile' => strtolower(str_replace(' ', '_', $_GET['interim-file'])),
      ];
    }
    else {
      $r = import_helper_2::sendFileToWarehouse($_GET['interim-file'], $writeAuth);
      if ($r === TRUE) {
        return [
          'status' => 'ok',
          // Warehouse lowercases the file name and replaces spaces.
          'uploadedFile' => strtolower(str_replace(' ', '_', $_GET['interim-file'])),
        ];
      }
      else {
        throw new Exception(var_export($r, TRUE));
      }
    }
  }

  /**
   * Ajax handler to handle transferring the file to the warehouse.
   *
   * @param int $website_id
   *   Warehouse website ID.
   * @param string $password
   *   Warehouse website password.
   * @param int $nid
   *   Node ID.
   *
   * @return array
   *   Progress data from the warehouse.
   */
  public static function ajax_extract_file_on_warehouse($website_id, $password, $nid) {
    $writeAuth = self::getAuthFromHeaders();
    iform_load_helpers(['import_helper_2']);
    return import_helper_2::extractFileOnWarehouse($_GET['uploaded-file'], $writeAuth);
  }

  /**
   * Ajax handler to initialise the JSON config file on the warehouse.
   *
   * @param int $website_id
   *   Warehouse website ID.
   * @param string $password
   *   Warehouse website password.
   * @param int $nid
   *   Node ID.
   *
   * @return array
   *   Progress data from the warehouse.
   */
  public static function ajax_init_server_config($website_id, $password, $nid) {
    $writeAuth = self::getAuthFromHeaders();
    iform_load_helpers(['import_helper_2']);
    // Pass the settings for enabled plugins and params.
    $plugins = [];
    $nodeParams = hostsite_get_node_field_value($nid, 'params');
    foreach ($nodeParams as $param => $value) {
      if (substr($param, 0, 7) === 'plugin-' && $value === '1') {
        $paramCsv = $nodeParams['pluginparams-' . substr($param, 7)];
        $plugins[substr($param, 7)] = empty($paramCsv) ? [] : explode(',', $paramCsv);
      }
    }
    return import_helper_2::initServerConfig(
      $_GET['data-file'],
      isset($_GET['import_template_id']) ? $_GET['import_template_id'] : NULL,
      $writeAuth,
      $plugins
    );
  }

  /**
   * Ajax handler to request that the warehouse loads a chunk of records.
   *
   * @param int $website_id
   *   Warehouse website ID.
   * @param string $password
   *   Warehouse website password.
   * @param int $nid
   *   Node ID.
   *
   * @return array
   *   Progress data from the warehouse.
   */
  public static function ajax_load_chunk_to_temp_table($website_id, $password, $nid) {
    $writeAuth = self::getAuthFromHeaders();
    iform_load_helpers(['import_helper_2']);
    return import_helper_2::loadChunkToTempTable($_GET['data-file'], $writeAuth);
  }

  /**
   * Perform processing on lookup matching.
   *
   * @param int $website_id
   *   Warehouse website ID.
   * @param string $password
   *   Warehouse website password.
   * @param int $nid
   *   Node ID.
   *
   * @return array
   *   Progress data from the warehouse.
   */
  public static function ajax_process_lookup_matching($website_id, $password, $nid) {
    $writeAuth = self::getAuthFromHeaders();
    iform_load_helpers(['import_helper_2']);
    return import_helper_2::processLookupMatching($_GET['data-file'], $_GET['index'], $writeAuth);
  }

  /**
   * Save a group of lookup matches.
   *
   * @param int $website_id
   *   Warehouse website ID.
   * @param string $password
   *   Warehouse website password.
   * @param int $nid
   *   Node ID.
   *
   * @return array
   *   Progress data from the warehouse.
   */
  public static function ajax_save_lookup_matches_group($website_id, $password, $nid) {
    $writeAuth = self::getAuthFromHeaders();
    iform_load_helpers(['import_helper_2']);
    return import_helper_2::saveLookupMatchesGroup($_GET['data-file'], $_POST, $writeAuth);
  }

  /**
   * Perform processing that can be done before the final import stage.
   *
   * @param int $website_id
   *   Warehouse website ID.
   * @param string $password
   *   Warehouse website password.
   * @param int $nid
   *   Node ID.
   *
   * @return array
   *   Progress data from the warehouse.
   */
  public static function ajax_preprocess($website_id, $password, $nid) {
    $writeAuth = self::getAuthFromHeaders();
    iform_load_helpers(['import_helper_2']);
    return import_helper_2::preprocess($_GET['data-file'], $_GET['index'], $writeAuth);
  }

  /**
   * Import a single chunk of data.
   *
   * @param int $website_id
   *   Warehouse website ID.
   * @param string $password
   *   Warehouse website password.
   * @param int $nid
   *   Node ID.
   *
   * @return array
   *   Progress data from the warehouse.
   */
  public static function ajax_import_chunk($website_id, $password, $nid) {
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
    return $response;
  }

  /**
   * Retrieve any additional fixed values.
   *
   * Values that apply to all rows, e.g. group_id for group linked forms, or
   * training mode values.
   *
   * @param array $auth
   *   Authorisation tokens.
   *
   * @return array
   *   Key/value pairs of fixed values.
   */
  private static function getAdditionalFixedValues(array $auth, $model) {
    $fixedValues = [];
    // If in training mode, set the flag on the imported records.
    if (function_exists('hostsite_get_user_field') && hostsite_get_user_field('training')) {
      $fixedValues['sample:training'] = 't';
      $fixedValues['occurrence:training'] = 't';
    }
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
        $fixedValues['groups_location:group_id'] = $_GET['group_id'];
      }
      else {
        $fixedValues['sample:group_id'] = $_GET['group_id'];
      }
      hostsite_set_page_title(lang::get('Import data into the {1} group', $group['title']));
      // If a single survey specified for this group, then force the data into
      // the correct survey.
      $filterdef = json_decode($group['filter_definition'], TRUE);
      if (!empty($filterdef['survey_list_op']) && $filterdef['survey_list_op'] === 'in' && !empty($filterdef['survey_list'])) {
        $surveys = explode(',', $filterdef['survey_list']);
        if (count($surveys) === 1) {
          $fixedValues['survey_id'] = $surveys[0];
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
