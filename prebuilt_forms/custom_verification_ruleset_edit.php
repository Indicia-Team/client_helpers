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
 * An edit form for custom verification rulesets and the contained rules.
 */
class iform_custom_verification_ruleset_edit {

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_custom_verification_ruleset_edit_definition() {
    return [
      'title' => 'Custom verification ruleset edit form',
      'category' => 'General Purpose Data Entry Forms',
      'description' => 'A form for editing a custom verification ruleset.',
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
        'name' => 'grid_ref_systems',
        'caption' => lang::get('Grid reference systems'),
        'type' => 'textfield',
        'default' => 'OSGB,OSIE',
        'description' => lang::get('Comma separaterd list of grid reference systems available for picking a grid reference in.'),
      ],
      [
        'name' => 'higher_geography_location_type_ids',
        'caption' => lang::get('Location type IDs for selecting named places'),
        'type' => 'textfield',
        'description' => lang::get('Comma separated list of IDs of location types which can be used to select a named place to limit a ruleset to, or a place to use within an individual verification rule. Must be an indexed location type.'),
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
    if (!hostsite_get_user_field('indicia_user_id')) {
      return 'Please ensure that you\'ve filled in your surname on your user profile before creating or editing custom verification rules.';
    }
    global $indicia_templates;
    helper_base::add_resource('font_awesome');
    iform_load_helpers(['map_helper']);
    $conn = iform_get_connection_details($nid);
    $auth = data_entry_helper::get_read_write_auth($conn['website_id'], $conn['password']);
    $config = \Drupal::config('iform.settings');
    $reloadPath = self::getReloadPath();
    data_entry_helper::enable_validation('entry_form');
    if (!empty($_GET['custom_verification_ruleset_id'])) {
      self::loadExistingRuleset($_GET['custom_verification_ruleset_id'], $auth);
    }
    helper_base::addLanguageStringsToJs('customVerificationRulesetEdit', [
      'areYouSureDelete' => 'Are you sure you want to delete this ruleset?',
    ]);
    $hiddenValues = <<<HTML
  $auth[write]
  <input type="hidden" name="website_id" value="$args[website_id]" />

HTML;
    if (!empty(data_entry_helper::$entity_to_load['custom_verification_ruleset:id'])) {
      $hiddenValues .= '  <input type="hidden" name="custom_verification_ruleset:id" value="' .
        data_entry_helper::$entity_to_load['custom_verification_ruleset:id'] . '" />';
    }
    $metadataInputs = data_entry_helper::text_input([
      'fieldname' => 'custom_verification_ruleset:title',
      'label' => lang::get('Title'),
      'helpText' => lang::get('Provide a descriptive title for your ruleset to help you identify it in future.'),
      'validation' => ['required'],
    ]);
    $metadataInputs .= data_entry_helper::textarea([
      'fieldname' => 'custom_verification_ruleset:description',
      'label' => lang::get('Description'),
      'helpText' => lang::get('Provide a brief description of the ruleset.'),
    ]);
    $metadataInputs .= data_entry_helper::select([
      'fieldname' => 'custom_verification_ruleset:fail_icon',
      'label' => lang::get('Default failure icon'),
      'lookupValues' => [
        'bar-chart' => 'Bar chart',
        'bug' => 'Bug',
        'calendar' => 'Calendar',
        'calendar-cross' => 'Calendar with cross',
        'calendar-tick' => 'Calendar with tick',
        'clock' => 'Clock',
        'count' => 'Count up',
        'cross' => 'Cross',
        'exclamation' => 'Exclamation',
        'flag' => 'Flag',
        'globe' => 'Globe',
        'history' => 'History',
        'leaf' => 'Leaf',
        'spider' => 'Spider',
        'tick' => 'Tick',
        'tick-in-box' => 'Tick in box',
        'warning-triangle' => 'Warning triangle',
      ],
      'validation' => ['required'],
      'blankText' => lang::get('- Please select -'),
    ]);
    $metadataInputs .= data_entry_helper::text_input([
      'fieldname' => 'custom_verification_ruleset:fail_message',
      'label' => lang::get('Default fail message'),
      'helpText' => lang::get('Default message associated with a failure of this rule. This can be overridden on a rule by rule basis.'),
      'validation' => ['required'],
    ]);
    $stageLimitInputs = data_entry_helper::textarea([
      // Fieldname is *_list so the warehouse can convert to an array.
      'fieldname' => 'custom_verification_ruleset:limit_to_stages_list',
      'label' => lang::get('Limit to stages'),
      'helpText' => lang::get('If you would only like this ruleset to apply to certain life stages, list them here (case insensitive, one per line).'),
    ]);
    $lang = [
      'geographicLimits' => lang::get('Geographic limits'),
      'limitByGridRef' => lang::get('Limit by grid reference'),
      'limitByGridRefInfo' => lang::get('Records will only be covered by this ruleset if they are inside at least one of the grid references listed below.'),
      'limitByLatLng' => lang::get('Limit by bounding box or lat/long'),
      'limitByNamedPlace' => lang::get('Limit by named place'),
      'limitByNamedPlaceInfo' => lang::get('Records will only be covered by this ruleset if they are inside at least one of the locations listed below.'),
      'rulesetMetadata' => lang::get('Ruleset metadata'),
      'stageLimits' => lang::get('Stage limits'),
    ];
    $accordionSections = '';
    // Selection by grid ref only shown if some systems specified.
    if (!empty(trim($args['grid_ref_systems']))) {
      $gridRefListCntrl = data_entry_helper::textarea([
        'fieldname' => 'geography:grid_refs',
        'label' => lang::get('Limit to grid references'),
        'helpText' => lang::get('If you would only like this ruleset to apply to records inside one or more grid references, list them here, one per line.'),
      ]);
      $systems = [];
      $systemCodes = explode(',', $args['grid_ref_systems']);
      foreach ($systemCodes as $code) {
        $systems[trim($code)] = lang::get(trim($code));
      }
      $systemCntrl = data_entry_helper::sref_system_select([
        'fieldname' => 'geography:grid_ref_system',
        'id' => 'imp-sref-system',
        'label' => lang::get('Grid reference system'),
        'systems' => $systems,
      ]);
      // Add a class if there are existing values, so the panel is uncollapsed.
      $isOpen = !empty(data_entry_helper::$entity_to_load['geography:grid_refs']) ? ' in' : '';
      $accordionSections .= <<<HTML
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title">
        <a data-toggle="collapse" data-parent="#geo-accordion" href="#grid-ref-collapse">
          $lang[limitByGridRef]
        </a>
      </h4>
    </div>
    <div id="grid-ref-collapse" class="panel-collapse collapse$isOpen">
      <div class="panel-body">
        <p>$lang[limitByGridRefInfo]</p>
        $gridRefListCntrl
        $systemCntrl
      </div>
    </div>
  </div>
HTML;
    }
    // Selection by lat/long or bounding box always available.
    $latLngControls = self::getLatLngControls();
    // Add a class if there are existing values, so the panel is uncollapsed.
    $isOpen = !empty(data_entry_helper::$entity_to_load['geography:min_lat']) || !empty(data_entry_helper::$entity_to_load['geography:max_lat'])
        || !empty(data_entry_helper::$entity_to_load['geography:min_lng']) || !empty(data_entry_helper::$entity_to_load['geography:max_lng']) ? ' in' : '';
    $accordionSections .= <<<HTML
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title">
        <a data-toggle="collapse" data-parent="#geo-accordion" href="#latlng-collapse">
          $lang[limitByLatLng]
        </a>
      </h4>
    </div>
    <div id="latlng-collapse" class="panel-collapse collapse$isOpen">
      <div class="panel-body">
        $latLngControls
      </div>
    </div>
  </div>
HTML;
    // Selection by higher geography only shown if there are some location
    // types specified.
    if (!empty(trim($args['higher_geography_location_type_ids']))) {
      if (!preg_match('/^\d+(,\d+)*$/', trim($args['higher_geography_location_type_ids']))) {
        throw new exception('Location type IDs configuration not in correct format.');
      }
      $locationTypeIds = explode(',', str_replace(' ', '', $args['higher_geography_location_type_ids']));
      $typeCntrl = data_entry_helper::select([
        'fieldname' => 'geography:location_type',
        'label' => lang::get('First, select the type of location to search in'),
        'table' => 'termlists_term',
        'extraParams' => $auth['read'] + [
          'view' => 'cache',
          'query' => json_encode(['in' => ['id' => $locationTypeIds]]),
        ],
        'valueField' => 'id',
        'captionField' => 'term',
        'blankText' => '<' . lang::get('Please select') . '>',
        'controlWrapTemplate' => 'justControl',
      ]);
      $locationListControl = data_entry_helper::sub_list([
        'fieldname' => 'geography:location_list',
        'label' => 'Search for a location to add',
        'table' => 'location',
        'captionField' => 'name',
        'valueField' => 'id',
        'addToTable' => FALSE,
        'extraParams' => $auth['read'],
      ]);
      // Add a class if there are existing values, so the panel is uncollapsed.
      $isOpen = !empty(data_entry_helper::$entity_to_load['geography:location_list']) ? ' in' : '';
      $accordionSections .= <<<HTML
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title">
        <a data-toggle="collapse" data-parent="#geo-accordion" href="#named-place-collapse">
          $lang[limitByNamedPlace]
        </a>
      </h4>
    </div>
    <div id="named-place-collapse" class="panel-collapse collapse$isOpen">
      <div class="panel-body">
        <p>$lang[limitByNamedPlaceInfo]</p>
        $typeCntrl
        $locationListControl
      </div>
    </div>
  </div>
HTML;
    }
    $leftCol = <<<HTML
<div class="panel-group" id="geo-accordion">
  $accordionSections
</div>
HTML;

    $mapCol = map_helper::map_panel([
      'readAuth' => $auth['read'],
      'presetLayers' => ['osm'],
      'editLayer' => FALSE,
      'layers' => [],
      'initial_lat' => $config->get('map_centroid_lat'),
      'initial_long' => $config->get('map_centroid_long'),
      'initial_zoom' => $config->get('map_zoom'),
      'width' => '100%',
      'height' => '450px',
      'standardControls' => ['layerSwitcher', 'panZoomBar'],
    ]);

    $geoLimitInputs = str_replace(
      ['attrs', '{col-1}', '{col-2}'],
      ['', $leftCol, $mapCol],
      $indicia_templates['two-col-50']
    );
    $submitButtons = self::getSubmitButtons($args);
    return <<<HTML
<form method="post" id="entry_form" action="$reloadPath">
  $hiddenValues
  <fieldset><legend>$lang[rulesetMetadata]</legend>
    $metadataInputs
  </fieldset>
  <fieldset><legend>$lang[stageLimits]</legend>
    $stageLimitInputs
  </fieldset>
  <fieldset><legend>$lang[geographicLimits]</legend>
    $geoLimitInputs
  </fieldset>
  $submitButtons
</form>
HTML;
  }

  /**
   * Save and delete buttons.
   *
   * @param array $args
   *   Form configuration arguments.
   */
  private static function getSubmitButtons(array $args) {
    global $indicia_templates;
    $lang = [
      'delete' => lang::get('Delete'),
      'save' => lang::get('Save'),
    ];
    $r = "<input type=\"submit\" class=\"$indicia_templates[buttonDefaultClass]\" id=\"save-button\" value=\"$lang[save]\" />\n";
    if (!empty(data_entry_helper::$entity_to_load['custom_verification_ruleset:id'])) {
      // Use a button here, not input, as Chrome does not post the input value.
      $r .= "<button type=\"submit\" class=\"$indicia_templates[buttonWarningClass]\" id=\"delete-button\" name=\"delete-button\" value=\"delete\" >$lang[delete]</button>\n";
    }
    return $r;
  }

  /**
   * Build a submission array.
   *
   * @param array $values
   *   Associative array of form data values.
   * @param array $args
   *   IForm parameters.
   *
   * @return array
   *   Submission structure.
   */
  public static function get_submission($values, $args) {
    $structure = [
      'model' => 'custom_verification_ruleset',
      'submodel' => [
        'model' => 'custom_verification_rule',
        'fk' => 'custom_verification_ruleset_id',
      ],
    ];
    // @todo Convert stages to JSON
    $geoLimits = [];
    if (!empty($values['geography:grid_refs'])) {
      $geoLimits['grid_refs'] = array_map('trim', helper_base::explode_lines($values['geography:grid_refs']));
      $geoLimits['grid_ref_system'] = $values['geography:grid_ref_system'];
    }
    if (!empty($values['geography:min_lng'])) {
      $geoLimits['min_lng'] = $values['geography:min_lng'];
    }
    if (!empty($values['geography:min_lat'])) {
      $geoLimits['min_lat'] = $values['geography:min_lat'];
    }
    if (!empty($values['geography:max_lng'])) {
      $geoLimits['max_lng'] = $values['geography:max_lng'];
    }
    if (!empty($values['geography:max_lat'])) {
      $geoLimits['max_lat'] = $values['geography:max_lat'];
    }
    if (!empty($values['geography:location_list'])) {
      $geoLimits['location_ids'] = $values['geography:location_list'];
    }
    $values['custom_verification_ruleset:limit_to_geography'] = json_encode($geoLimits);
    // Force the cache to clear so the popup on the verification page refreshes.
    $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
    data_entry_helper::get_population_data([
      'table' => 'custom_verification_ruleset',
      'extraParams' => $readAuth + [
        'created_by_id' => hostsite_get_user_field('indicia_user_id'),
        'orderby' => 'title',
      ],
      'caching' => 'expire',
    ]);
    return submission_builder::build_submission($values, $structure);
  }

  /**
   * Retrieve the path to the current page, so the form can submit to itself.
   *
   * @return string
   *   Reload path.
   */
  private static function getReloadPath() {
    $reload = data_entry_helper::get_reload_link_parts();
    $reloadPath = $reload['path'];
    if (count($reload['params'])) {
      // Decode params prior to encoding to prevent double encoding.
      foreach ($reload['params'] as $key => $param) {
        $reload['params'][$key] = urldecode($param);
      }
      $reloadPath .= '?' . http_build_query($reload['params']);
    }
    return $reloadPath;
  }

  /**
   * Build the controls required for lat/long or bounding box limits.
   */
  private static function getLatLngControls() {
    $maxLatInput = data_entry_helper::text_input([
      'fieldname' => 'geography:max_lat',
      'label' => lang::get('Maximum latitude'),
      'class' => 'lat-lng-input',
      'attributes' => [
        'type' => 'number',
        'step' => 0.1,
        'min' => -90,
        'max' => 90,
      ],
    ]);
    $maxLngInput = data_entry_helper::text_input([
      'fieldname' => 'geography:max_lng',
      'label' => lang::get('Maximum longitude'),
      'class' => 'lat-lng-input',
      'attributes' => [
        'type' => 'number',
        'step' => 0.1,
        'min' => -90,
        'max' => 90,
      ],
    ]);
    $minLngInput = data_entry_helper::text_input([
      'fieldname' => 'geography:min_lng',
      'label' => lang::get('Minimum longitude'),
      'class' => 'lat-lng-input',
      'attributes' => [
        'type' => 'number',
        'step' => 0.1,
        'min' => -90,
        'max' => 90,
      ],
    ]);
    $minLatInput = data_entry_helper::text_input([
      'fieldname' => 'geography:min_lat',
      'label' => lang::get('Minimum latitude'),
      'class' => 'lat-lng-input',
      'attributes' => [
        'type' => 'number',
        'step' => 0.1,
        'min' => -90,
        'max' => 90,
      ],
    ]);
    $lang = [
      'bboxInvalid' => lang::get('The entered coordinates are invalid.'),
      'instruct' => lang::get('Enter coordinates or drag a box on the map'),
    ];
    return <<<HTML
<p>$lang[instruct]</p>
<div class="row">
  <div class="col-md-4"></div>
  <div class="col-md-4">$maxLatInput</div>
</div>
<div class="row">
  <div class="col-md-4">$minLngInput</div>
  <div class="col-md-4"></div>
  <div class="col-md-4">$maxLngInput</div>
</div>
<div class="row">
  <div class="col-md-4"></div>
  <div class="col-md-4">$minLatInput</div>
</div>
<p id="invalid-bbox-message" class="alert alert-danger" style="display: none">$lang[bboxInvalid]</p>
HTML;
  }

  /**
   * Load an existing ruleset for editing.
   *
   * Checks that the ruleset is created by this user, then sets the values into
   * `data_entry_helper::$entity_to_load`.
   *
   * @param int $id
   *   Ruleset ID.
   * @param array $auth
   *   Authorisation tokens, including the read tokens.
   */
  private static function loadExistingRuleset($id, array $auth) {
    $ruleset = data_entry_helper::get_population_data([
      'table' => 'custom_verification_ruleset',
      'extraParams' => $auth['read'] + ['id' => $id],
      'nocache' => TRUE,
    ]);
    if (empty($ruleset)) {
      hostsite_show_message("The ruleset with ID $id could not be found.");
      return;
    }
    $ruleset = $ruleset[0];
    // Basic permissions check that they own the ruleset.
    if ($ruleset['created_by_id'] !== hostsite_get_user_field('indicia_user_id')) {
      hostsite_show_message('You cannot edit a custom_verification_ruleset that was created by another user.');
      return;
    }
    $geoLimits = empty($ruleset['limit_to_geography']) ? [] : json_decode($ruleset['limit_to_geography'], TRUE);
    data_entry_helper::$entity_to_load = [
      'custom_verification_ruleset:id' => $ruleset['id'],
      'custom_verification_ruleset:title' => $ruleset['title'],
      'custom_verification_ruleset:description' => $ruleset['description'],
      'custom_verification_ruleset:fail_icon' => $ruleset['fail_icon'],
      'custom_verification_ruleset:fail_message' => $ruleset['fail_message'],
      'custom_verification_ruleset:limit_to_stages_list' => empty($ruleset['limit_to_stages'])
        ? NULL : implode("\n", str_getcsv(substr($ruleset['limit_to_stages'], 1, strlen($ruleset['limit_to_stages']) - 2))),
      'geography:grid_refs' => empty($geoLimits['grid_refs']) ? NULL : implode("\n", $geoLimits['grid_refs']),
      'geography:grid_ref_system' => $geoLimits['grid_ref_system'] ?? NULL,
      'geography:min_lat' => $geoLimits['min_lat'] ?? NULL,
      'geography:max_lat' => $geoLimits['max_lat'] ?? NULL,
      'geography:min_lng' => $geoLimits['min_lng'] ?? NULL,
      'geography:max_lng' => $geoLimits['max_lng'] ?? NULL,
    ];
    if (!empty($geoLimits['location_ids'])) {
      $locations = data_entry_helper::get_population_data([
        'table' => 'location',
        'extraParams' => $auth['read'] + ['query' => json_encode(['in' => ['id' => $geoLimits['location_ids']]])],
      ]);
      $cntrlData = [];
      foreach ($locations as $location) {
        $cntrlData[] = [
          'fieldname' => 'geography:location_ids[]',
          'caption' => $location['name'],
          'default' => $location['id'],
        ];
      }
      data_entry_helper::$entity_to_load['geography:location_ids'] = $cntrlData;
    }
  }

}
