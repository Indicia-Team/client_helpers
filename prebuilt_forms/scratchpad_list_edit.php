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
 * @package Client
 * @subpackage PrebuiltForms
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link  http://code.google.com/p/indicia/
 */

/**
 * Form for editing a scratchpad list (a list of pointers to entities in the database, e.g. a list of species or locations).
 *
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_scratchpad_list_edit {

  /**
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_scratchpad_list_edit_definition() {
    return array(
      'title' => 'Enter a scratchpad list',
      'category' => 'Data entry forms',
      'description' => 'Form for creating or editing an existing scratchpad list. This allows creation of a list of ' .
          'pointers to entities in the database, e.g. a list of species or locations',
      'recommended' => true
    );
  }

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    return array(
      array(
        'name' => 'entity',
        'caption' => 'Type of data to create a list for',
        'description' => 'Select the type of data the scratchpad list will contain. ' .
            'Currently only species or other taxa are supported.',
        'type' => 'select',
        'options' => array(
          'taxa_taxon_list' => 'Species or other taxa',
        ),
        'required' => TRUE,
      ),
      array(
        'name' => 'scratchpad_type_id',
        'caption' => 'Scratchpad type',
        'description' => 'Select the type or category of scratchpad that new scratchpads will be saved as.',
        'type' => 'select',
        'table' => 'termlists_term',
        'captionField' => 'term',
        'valueField' => 'id',
        'extraParams' => array('termlist_external_key' => 'indicia:scratchpad_list_types'),
        'required' => FALSE,
      ),
      array(
        'name' => 'duplicates',
        'caption' => 'Duplicate handling',
        'description' => 'Select the type of data the scratchpad list will contain. ' .
          'Currently only species or other taxa are supported.',
        'type' => 'select',
        'options' => array(
          'allow' => 'Allow duplicates',
          'highlight' => 'Allow duplicates but highlight them',
          'warn' => 'Allow duplicates but warn when they occur',
          'disallow' => 'Disallow duplicates',
        ),
        'default' => 'highlight',
        'required' => TRUE,
      ),
      array(
        'name' => 'filters',
        'caption' => 'Filters for search query',
        'description' => 'Additional filters to apply to the search query, e.g. taxon_list_id=&lt;n&gt; to limit to a ' .
            'single list. Key=value pairs, one per line',
        'type' => 'textarea',
        'required' => FALSE,
      )
    );
  }

  /**
   * Return the generated form output.
   * @param array $args List of parameter values passed through to the form depending on how the form has been configured.
   * This array always contains a value for language.
   * @param object $nid The Drupal node object's ID.
   * @param array $response When this form is reloading after saving a submission, contains the response from the service call.
   * Note this does not apply when redirecting (in this case the details of the saved object are in the $_GET data).
   * @return Form HTML.
   */
  public static function get_form($args, $nid, $response = NULL) {
    data_entry_helper::add_resource('fancybox');
    data_entry_helper::add_resource('jquery_form');
    $conn = iform_get_connection_details($nid);
    $auth = data_entry_helper::get_read_write_auth($conn['website_id'], $conn['password']);
    $filters = data_entry_helper::explode_lines_key_value_pairs($args['filters']);
    $options = [
      'entity' => $args['entity'],
      'extraParams' => [],
      'duplicates' => $args['duplicates'],
      'filters' => $filters,
      'ajaxProxyUrl' => iform_ajaxproxy_url(NULL, 'scratchpad_list'),
      'websiteId' => $args['website_id'],
      'returnPath' => hostsite_get_url($args['redirect_on_success']),
    ];
    $checkLabel = lang::get('Check');
    $removeDuplicatesLabel = lang::get('Remove duplicates');
    $saveLabel = lang::get('Save');
    $cancelLabel = lang::get('Cancel');
    $reloadPath = self::getReloadPath();
    $defaultList = '';
    $r = "<form method=\"post\" id=\"entry_form\" action=\"$reloadPath\" enctype=\"multipart/form-data\">\n";
    data_entry_helper::enable_validation('entry_form');
    if (!empty($args['scratchpad_type_id'])) {
      $r .= data_entry_helper::hidden_text([
        'fieldname' => 'scratchpad_list:scratchpad_type_id',
        'default' => $args['scratchpad_type_id'],
      ]);
    }
    if (!empty($_GET['scratchpad_list_id'])) {
      $list = data_entry_helper::get_population_data([
        'table' => 'scratchpad_list',
        'extraParams' => $auth['read'] + ['id' => $_GET['scratchpad_list_id']],
        'caching' => FALSE,
      ]);
      $entries = data_entry_helper::get_population_data([
        'table' => 'scratchpad_list_entry',
        'extraParams' => $auth['read'] + [
          'scratchpad_list_id' => $_GET['scratchpad_list_id'],
          'orderby' => 'id',
        ],
        'caching' => FALSE,
      ]);
      $sortedTaxa = [];
      $batchIds = [];
      $taxa = [];
      foreach ($entries as $entry) {
        $batchIds[] = $entry['entry_id'];
        $sortedTaxa["ttlId:$entry[entry_id]"] = NULL;
        // Grab the taxon list in batches of 50.
        if (count($batchIds) > 50) {
          $taxa = array_merge($taxa, data_entry_helper::get_population_data([
            'table' => 'taxa_taxon_list',
            'extraParams' => $auth['read'] + [
              'view' => 'cache',
              'query' => json_encode(['in' => ['id' => $batchIds]]),
            ],
          ]));
          $batchIds = [];
        }
      };
      // Grab the final batch.
      if (count($batchIds)) {
        $taxa = array_merge($taxa, data_entry_helper::get_population_data([
          'table' => 'taxa_taxon_list',
          'extraParams' => $auth['read'] + [
            'view' => 'cache',
            'query' => json_encode(['in' => ['id' => $batchIds]]),
          ],
        ]));
      }
      // Assign taxa to sorted array.
      foreach ($taxa as $taxon) {
        $sortedTaxa["ttlId:$taxon[id]"] = $taxon;
      }
      data_entry_helper::$entity_to_load = array(
        'scratchpad_list:title' => $list[0]['title'],
        'scratchpad_list:description' => $list[0]['description']
      );
      foreach ($sortedTaxa as $taxonInList) {
        $defaultList .= "<span class=\"matched\" data-id=\"$taxonInList[id]\">" .
          "$taxonInList[taxon]</span><br/>";
      }
      $r .= data_entry_helper::hidden_text(array(
        'fieldname' => 'scratchpad_list:id',
        'default' => $_GET['scratchpad_list_id']
      ));
    }
    $r .= data_entry_helper::hidden_text(array(
      'fieldname' => 'website_id',
      'default' => $conn['website_id']
    ));
    $r .= $auth['write'];
    $r .= data_entry_helper::text_input(array(
      'fieldname' => 'scratchpad_list:title',
      'label' => lang::get('List title'),
      'helpText' => lang::get(
          'Provide a title that will help you remember what the list is for when you access it in future'),
      'class' => 'control-width-6',
      'validation' => array('required')
    ));
    $r .= data_entry_helper::textarea(array(
      'fieldname' => 'scratchpad_list:description',
      'label' => lang::get('List description'),
      'class' => 'control-width-6'
    ));
    global $indicia_templates;
    $langListOk = lang::get('List OK');
    $langTotal = lang::get('Total');
    $langMatched = lang::get('Matched');
    $langQueried = lang::get('Queried');
    $langUnmatched = lang::get('Unmatched');
    $indicia_templates['scratchpad_input'] = <<<DIV
  <div id="scratchpad-container">
  <div contenteditable="true" id="{id}"{class}>{default}</div>
  <div id="scratchpad-stats" class="ui-helper-clearfix ui-helper-hidden">
    <div id="scratchpad-stats-total"><span class="all-done">$langListOk</span> $langTotal: <span class="stat">0</span></div>
    <div id="scratchpad-stats-matched">$langMatched: <span class="stat">0</span></div>
    <div id="scratchpad-stats-queried">$langQueried: <span class="stat">0</span></div>
    <div id="scratchpad-stats-unmatched">$langUnmatched: <span class="stat">0</span></div>
  </div>
</div>
DIV;
    $r .= data_entry_helper::apply_template('scratchpad_input', array(
      'id' => 'scratchpad-input',
      'label' => lang::get('Enter the list of items'),
      'helpText' => lang::get('Type in or paste items separated by commas or on separate lines.'),
      'default' => $defaultList
    ));
    $r .= data_entry_helper::hidden_text(array(
      'id' => 'hidden-entries-list',
      'fieldname' => 'metaFields:entries'
    ));
    $r .= data_entry_helper::hidden_text(array(
      'fieldname' => 'scratchpad_list:entity',
      'default' => $args['entity']
    ));
    $r .= <<<HTML
<button id="scratchpad-check" type="button">$checkLabel</button>
<button id="scratchpad-remove-duplicates" type="button" style="display: none">$removeDuplicatesLabel</button>
<button id="scratchpad-save" type="submit" disabled="disabled">$saveLabel</button>
HTML;
    if (!empty($args['redirect_on_success']))
      $r .= "\n<button id=\"scratchpad-cancel\" type=\"button\">$cancelLabel</button>\n";
    $r .= "</form>\n";
    data_entry_helper::$javascript .= 'indiciaData.scratchpadSettings = ' . json_encode($options) . ";\n";
    data_entry_helper::$javascript .= 'indiciaData.ajaxUrl="'.hostsite_get_url('iform/ajax/scratchpad_list_edit')."\";\n";
    return $r;
  }

  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values.
   * @param array $args iform parameters.
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    $structure = array('model' => 'scratchpad_list', 'metaFields' => array('entries'));
    return submission_builder::build_submission($values, $structure);

  }

  protected static function getReloadPath() {
    $reload = data_entry_helper::get_reload_link_parts();
    unset($reload['params']['sample_id']);
    unset($reload['params']['occurrence_id']);
    unset($reload['params']['location_id']);
    unset($reload['params']['new']);
    unset($reload['params']['newLocation']);
    $reloadPath = $reload['path'];
    if(count($reload['params'])) {
      // decode params prior to encoding to prevent double encoding.
      foreach ($reload['params'] as $key => $param) {
        $reload['params'][$key] = urldecode($param);
      }
      $reloadPath .= '?'.http_build_query($reload['params']);
    }
    return $reloadPath;
  }

  /**
   * AJAX handler for the Check button's web request. Proxies to the warehouse and requests the list of provided names
   * is checked against the database.
   * @param $website_id
   * @param $password
   * @return string
   */
  public static function ajax_check($website_id, $password) {
    iform_load_helpers(array('data_entry_helper'));
    if (empty($_POST['params'])) {
      return 'Report parameters not provided';
    }
    $auth = data_entry_helper::get_read_auth($website_id, $password);
    $url = data_entry_helper::$base_url.'index.php/services/report/requestReport?' .
      data_entry_helper::array_to_query_string($_GET);
    $params = array_merge($_POST, $auth);
    $response = data_entry_helper::http_post($url, $params);
    echo $response['output'];
  }
}
