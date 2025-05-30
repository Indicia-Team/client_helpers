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

require_once 'includes/dynamic.php';

/**
 * Flexible form for data entry of locations.
 */
class iform_dynamic_location extends iform_dynamic {

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_dynamic_location_definition() {
    return [
      'title' => 'Enter a location (customisable)',
      'category' => 'Data entry forms',
      'description' => 'A data entry form for defining locations that can ' .
      'later be used to enter samples against. An optional grid listing the ' .
      'user\'s locations allows them to be reloaded for editing. The ' .
      'attributes on the form are dynamically generated from the survey setup ' .
      'on the Indicia Warehouse.',
      'recommended' => TRUE,
    ];
  }

  /**
   * {@inheritDoc}
   */
  public static function getPageType(): PageType {
    return PageType::DataEntry;
  }

  /**
   * Get the list of parameters for this form.
   *
   * @return array
   *   List of parameters that this form requires.
   */
  public static function get_parameters() {
    $retVal = array_merge(
      parent::get_parameters(),
      [
        [
          'name' => 'location_type_id',
          'caption' => 'Location type',
          'type' => 'select',
          'table' => 'termlists_term',
          'captionField' => 'term',
          'valueField' => 'id',
          'extraParams' => ['termlist_external_key' => 'indicia:location_types'],
          'required' => FALSE,
          'helpText' => 'The location type that will be used for all created ' .
          'locations. Alternatively use a [location type] control in the form ' .
          'structure.',
        ],
        [
          'name' => 'structure',
          'caption' => 'Form Structure',
          'description' => 'Define the structure of the form. Each component ' .
          'goes on a new line and is nested inside the previous component ' .
          'where appropriate. The following types of component can be ' .
          'specified. <br/>' .
          '<strong>=tab/page name=</strong> is used to specify the name of a tab or wizard page. (Alpha-numeric characters only)<br/>' .
          '<strong>=*=</strong> indicates a placeholder for putting any custom attribute tabs not defined in this form structure. <br/>' .
          '<strong>[control name]</strong> indicates a predefined control is to be added to the form with the following predefined controls available: <br/>' .
              '&nbsp;&nbsp;<strong>[map]</strong> - a map that links to the spatial reference control<br/>' .
              '&nbsp;&nbsp;<strong>[place search]</strong> - zooms the map to the entered location.<br/>' .
              '&nbsp;&nbsp;<strong>[spatial reference]</strong> - a location must always have a spatial reference.<br/>' .
              '&nbsp;&nbsp;<strong>[location name]</strong> - a text box to enter a descriptive name for the locataion.<br/>' .
              '&nbsp;&nbsp;<strong>[location code]</strong> - a text box to enter an identifying code for the location.<br/>' .
              '&nbsp;&nbsp;<strong>[location type]</strong> - a list to select the location type (hidden if a filter limits this to a single type).<br/>' .
              '&nbsp;&nbsp;<strong>[location parent]</strong> - a list to select the location parent (use e.g. @extraParams={"location_type_id":16516} to filter the list).<br/>' .
              '&nbsp;&nbsp;<strong>[location comment]</strong> - a text box for comments.<br/>' .
              '&nbsp;&nbsp;<strong>[location photo]</strong> - a photo upload for location images. <br/>' .
          '<strong>@option=value</strong> on the line(s) following any control allows you to override one of the options passed to the control. The options ' .
          'available depend on the control. For example @label=Abundance would set the untranslated label of a control to Abundance. Where the ' .
          'option value is an array, use valid JSON to encode the value. For example an array of strings could be passed as @occAttrClasses=["class1","class2"] ' .
          'or a keyed array as @extraParams={"preferred":"true","orderby":"term"}. ' .
          'Other common options include helpText (set to a piece of additional text to display alongside the control) and class (to add css ' .
          'classes to the control such as control-width-3). <br/>' .
          '<strong>[*]</strong> is used to make a placeholder for putting any custom attributes that should be inserted into the current tab. When this option is ' .
          'used, you can change any of the control options for an individual custom attribute control by putting @control|option=value on the subsequent line(s). ' .
          'For example, if a control is for smpAttr:4 then you can update its label by specifying @smpAttr:4|label=New Label on the line after the [*].<br/>' .
          '<strong>[locAttr:<i>n</i>]</strong> is used to insert a particular custom attribute identified by its ID number<br/>' .
          '<strong>?help text?</strong> is used to define help text to add to the tab, e.g. ?Enter the name of the site.? <br/>' .
          '<strong>all else</strong> is copied to the output html so you can add structure for styling.',
          'type' => 'textarea',
          'default' =>
              "=Place=\r\n" .
              "?Please provide the spatial reference of the location. You can enter the reference directly, or search for a place then click on the map to set it.?\r\n".
              "[location name]\r\n" .
              "[location code]\r\n" .
              "[location type]\r\n" .
              "[spatial reference]\r\n" .
              "[place search]\r\n" .
              "[map]\r\n" .
              "[*]\r\n" .
              "=Other Information=\r\n" .
              "?Please provide the following additional information.?\r\n" .
              "[location comment]\r\n" .
              "[*]\r\n" .
              "=*=",
          'group' => 'User Interface',
        ],
        [
          'name' => 'grid_report',
          'caption' => 'Grid Report',
          'description' => 'Name of the report to use to populate the grid for ' .
          'selecting existing data from. The report must return a location_id ' .
          'field for linking to the data entry form. As a starting point, try ' .
          'reports_for_prebuilt_forms/simple_location_list.',
          'type' => 'string',
          'group' => 'User Interface',
          'default' => 'reports_for_prebuilt_forms/simple_location_list',
        ],
        [
          'name' => 'list_all_locations',
          'caption' => 'List all locations',
          'description' => 'Should the user be given the option to list all ' .
          'locations in the grid rather than just their own? To use this, the ' .
          'selected report must have an ownData parameter and return an ' .
          'editable field. See reports_for_prebuilt_forms/simple_location_list_2 ' .
          'for an example.',
          'type' => 'boolean',
          'required' => FALSE,
          'default' => FALSE,
          'group' => 'User Interface',
        ],
        [
          'name' => 'defaults',
          'caption' => 'Default Values',
          'description' => 'Supply default values for each field as required. ' .
          'On each line, enter fieldname=value. For custom attributes, the ' .
          'fieldname is the untranslated caption. For other fields, it is the ' .
          'model and fieldname, e.g. occurrence.record_status. For date ' .
          'fields, use today to dynamically default to today\'s date. NOTE, ' .
          'currently only supports occurrence:record_status and sample:date ' .
          'but will be extended in future.',
          'type' => 'textarea',
          'required' => FALSE,
        ],
        [
          'name' => 'edit_permission',
          'caption' => 'Permission required for editing other people\'s data',
          'description' => 'Set to the name of a permission which is required ' .
          'in order to be able to edit other people\'s data.',
          'type' => 'text_input',
          'required' => FALSE,
          'default' => 'indicia data admin',
        ],
        [
          'name' => 'linkToParent',
          'caption' => 'Link the location to a parent',
          'description' => 'Allow the location to be saved under a parent by providing a location_id parameter in the URL query parameters.',
          'type' => 'checkbox',
          'required' => FALSE,
        ],
      ]
    );
    return $retVal;
  }

  /**
   * Gets the form mode.
   *
   * Determine whether to show a gird of existing records or a form for either
   * adding a new record or editing an existing one.
   *
   * @param array $args
   *   Iform parameters.
   * @param object $nid
   *   ID of node being shown.
   *
   * @return const
   *   The mode [MODE_GRID|MODE_NEW|MODE_EXISTING].
   */
  protected static function getMode($args, $nid) {
    // Default to mode MODE_GRID or MODE_NEW depending on no_grid parameter.
    $mode = (isset($args['no_grid']) && $args['no_grid']) ? self::MODE_NEW : self::MODE_GRID;

    if ($_POST && !is_null(data_entry_helper::$entity_to_load)) {
      // Errors with new sample or entity populated with post, so display this
      // data.
      $mode = self::MODE_EXISTING;
    }
    elseif (array_key_exists('location_id', $_GET) || array_key_exists('zoom_id', $_GET)) {
      // Request for display of existing record.
      $mode = self::MODE_EXISTING;
    }
    elseif (array_key_exists('new', $_GET)) {
      // Request to create new record (e.g. by clicking on button in grid view).
      $mode = self::MODE_NEW;
      data_entry_helper::$entity_to_load = [];
    }
    return $mode;
  }

  /**
   * Construct a grid of existing records.
   *
   * @param array $args
   *   Iform parameters.
   * @param object $nid
   *   ID of node being shown.
   * @param array $auth
   *   Authentication tokens for accessing the warehouse.
   *
   * @return string
   *   HTML for grid.
   */
  protected static function getGrid($args, $nid, array $auth) {
    $r = '<div id="locationList">' .
            call_user_func([self::$called_class, 'getLocationListGrid'], $args, $nid, $auth) .
          '</div>';
    return $r;
  }

  // Get an existing location.
  protected static function getEntity($args, $auth) {
    data_entry_helper::$entity_to_load = [];
    if (!empty($_GET['zoom_id'])) {
      self::zoom_map_when_adding($auth['read'], 'location', $_GET['zoom_id']);
    }
    else {
      data_entry_helper::load_existing_record(
        $auth['read'], 'location', $_GET['location_id'], 'detail', FALSE, TRUE
      );
    }
  }

  /**
   * Declare the list of permissions we've got set up.
   *
   * @param int $nid
   *   Node ID, not used.
   * @param array $args
   *   Form parameters array, used to extract the defined permissions.
   *
   * @return array
   *   List of distinct permissions.
   */
  public static function get_perms($nid, $args) {
    $perms = [];
    if (!empty($args['edit_permission'])) {
      $perms[] = $args['edit_permission'];
    }
    $perms += parent::get_perms($nid, $args);
    return $perms;
  }

  /**
   * Zoom into location that is not saved in the final location geometry.
   *
   * This function is used when a dynamic_location screen is in add mode, and we just
   * want to automatically zoom the map to a region/site we are adding a
   * location to. This boundary is purely visual and isn't submitted.
   *
   * @param array $readAuth
   *   Read authorisation array.
   * @param string $entity
   *   The table to collect the location data from (probably 'location').
   * @param int $id
   *   ID of the location to display.
   * @param string $view
   *   Type of view used to get the location data from
   *
   * @todo Investigate if zoom_id does the same thing as location_boundary_id
   * (perhaps zoom_id is not needed). Note at the time of writing, both Plant Portal
   * and NPMS use custom forms that rely on this parameter.
   */
  public static function zoom_map_when_adding($readAuth, $entity, $id, $view = 'detail') {
    // Get the zoom_id from the url to allow us to zoom to a specific region in
    // add mode.
    data_entry_helper::$javascript .= "indiciaData.zoomid='" . $_GET['zoom_id'] . "';\n";
    $loc = data_entry_helper::get_population_data([
      'table' => $entity,
      'extraParams' => $readAuth + ['id' => $id, 'view' => $view],
      'nocache' => TRUE,
    ]);

    if (isset($loc['error'])) {
      throw new Exception($loc['error']);
    }
    $loc = $loc[0];
    // Just put the feature onto the map, set the feature type to zoomToBoundary
    // so it isn't used for anything other than being a visual cue to zoom to.
    data_entry_helper::$javascript .= "
mapInitialisationHooks.push(function(mapdiv) {
  var feature, geom;
  if ('" . $loc['boundary_geom'] . "') {
    geom=OpenLayers.Geometry.fromWKT('" . $loc['boundary_geom'] . "');
  } else {
    geom=OpenLayers.Geometry.fromWKT('" . $loc['centroid_geom'] . "');
  }
  if (indiciaData.mapdiv.map.projection.getCode() != indiciaData.mapdiv.indiciaProjection.getCode()) {
      geom.transform(indiciaData.mapdiv.indiciaProjection, indiciaData.mapdiv.map.projection);
  }
  feature = new OpenLayers.Feature.Vector(geom);
  feature.attributes.type = 'zoomToBoundary';
  indiciaData.mapdiv.map.editLayer.addFeatures([feature]);
  mapdiv.map.zoomToExtent(feature.geometry.bounds);
  // If we are zooming closer than the helpToPickPrecisionSwitchAt setting, then
  var handler = indiciaData.srefHandlers[_getSystem().toLowerCase()];
  info = handler.getPrecisionInfo(handler.valueToAccuracy('" . $loc['centroid_sref'] . "'));
  if (mapdiv.settings.helpToPickPrecisionSwitchAt && info.metres <= mapdiv.settings.helpToPickPrecisionSwitchAt
    && !mapdiv.map.baseLayer.dynamicLayerIndex) {
    switchToSatelliteBaseLayerForZoom(mapdiv.map);
  }
});

/**
 * Return the system, by loading from the system control. If not present, revert to the default.
 */
function _getSystem() {
  var opts = $.fn.indiciaMapPanel.defaults;
  var selector=$('#' + opts.srefSystemId);
  if (selector.length===0) {
    return opts.defaultSystem;
  }
  else {
    return selector.val();
  }
}

/**
 * Switch to satellite layer if zoomed in far enough.
 *
 * Switch to satellite layer if the initial location we are zooming into
 * is closer than the helpToPickPrecisionSwitchAt setting.
 *
 * @param object map
 *   Map object.
 */
function switchToSatelliteBaseLayerForZoom(map) {
  $.each(map.layers, function eachLayer() {
    if (this.isBaseLayer
        && (this.name.indexOf('Satellite') !== -1 || this.name.indexOf('Hybrid') !== -1)
        && map.baseLayer !== this) {
      indiciaData.mapdiv.map.setBaseLayer(this);
      return false;
    }
  });
}";
  }

  protected static function getAttributes($args, $auth) {
    $id = isset(data_entry_helper::$entity_to_load['location:id']) ?
            data_entry_helper::$entity_to_load['location:id'] : NULL;
    $attrOpts = [
      'id' => $id,
      'valuetable' => 'location_attribute_value',
      'attrtable' => 'location_attribute',
      'key' => 'location_id',
      'fieldprefix' => 'locAttr',
      'extraParams' => $auth['read'],
      'survey_id' => $args['survey_id'],
    ];
    if (!empty($args['location_type_id'])) {
      $attrOpts['location_type_id'] = $args['location_type_id'];
    }
    $attributes = data_entry_helper::getAttributes($attrOpts, FALSE);
    return $attributes;
  }

  /**
   * Retrieve the additional HTML to appear at the top of the first
   * tab or form section. This is a set of hidden inputs containing the website
   * ID and survey ID as well as an existing location's ID.
   *
   * @param type $args
   */
  protected static function getFormHiddenInputs($args, $auth, &$attributes) {
    // Get authorisation tokens to update the Warehouse, plus any other hidden
    // data.
    $r = $auth['write'] .
          "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"" . $args['website_id'] . "\" />\n" .
          "<input type=\"hidden\" id=\"survey_id\" name=\"survey_id\" value=\"" . $args['survey_id'] . "\" />\n";
    if (isset(data_entry_helper::$entity_to_load['location:id'])) {
      $r .= '<input type="hidden" id="location:id" name="location:id" value="' . data_entry_helper::$entity_to_load['location:id'] . '" />' . PHP_EOL;
    }
    $r .= get_user_profile_hidden_inputs($attributes, $args, isset(data_entry_helper::$entity_to_load['location:id']), $auth['read']);
    // Pass through the group_id if set in URL parameters, so we can save the
    // location against the group.
    if (!empty($_GET['group_id'])) {
      $r .= "<input type=\"hidden\" id=\"group_id\" name=\"group_id\" value=\"" . $_GET['group_id'] . "\" />\n";
    }
    // Allow the location to be saved against a parent.
    if (!empty($args['linkToParent']) && !empty($_GET['parent_id'])) {
      $r .= "<input type=\"hidden\" name=\"location:parent_id\" value=\"" . $_GET['parent_id'] . "\" />\n";
      self::zoom_map_when_adding($auth['read'], 'location', $_GET['parent_id']);
    }
    if (!empty($args['location_type_id'])) {
      $r .= "<input type=\"hidden\" name=\"location:location_type_id\" value=\"$args[location_type_id]\" />";
    }
    return $r;
  }

  /**
   * Get the map control.
   */
  protected static function get_control_map($auth, $args, $tabalias, $options) {
    $options = array_merge(
      iform_map_get_map_options($args, $auth['read']),
      $options
    );
    // If a drawing tool is on the map we can support boundaries or if automatic
    // plot creation is enabled.
    $boundaries = FALSE;
    if (!empty($options['clickForPlot']) && $options['clickForPlot'] == TRUE) {
      $boundaries = TRUE;
    }
    foreach ($options['standardControls'] as $ctrl) {
      if (substr($ctrl, 0, 4) === 'draw') {
        $boundaries = TRUE;
        break;
      }
    }
    if (isset(data_entry_helper::$entity_to_load['location:centroid_geom'])) {
      $options['initialFeatureWkt'] = data_entry_helper::$entity_to_load['location:centroid_geom'];
    }
    if ($boundaries && isset(data_entry_helper::$entity_to_load['location:boundary_geom'])) {
      $options['initialBoundaryWkt'] = data_entry_helper::$entity_to_load['location:boundary_geom'];
    }
    if ($tabalias) {
      $options['tabDiv'] = $tabalias;
    }
    $olOptions = iform_map_get_ol_options($args);
    if (!self::editLocationPermitted($args)) {
      $options['standardControls'] = NULL;
      $r = '<p><strong>You cannot edit this location because you do not own it.</strong></p>';
    }
    else {
      $r = '';
    }
    if (!isset($options['standardControls'])) {
      $options['standardControls'] = ['layerSwitcher', 'panZoom'];
    }
    iform_load_helpers(['map_helper']);
    $r .= map_helper::map_panel($options, $olOptions);
    // Add a geometry hidden field for boundary support.
    if ($boundaries) {
      if (!empty(data_entry_helper::$entity_to_load['location:boundary_geom'])) {
        $impBoundaryGeomVal = data_entry_helper::$entity_to_load['location:boundary_geom'];
      }
      else {
        $impBoundaryGeomVal = '';
      }
      $r .= '<input type="hidden" name="location:boundary_geom" ' .
        'id="imp-boundary-geom" ' .
        'value="' . $impBoundaryGeomVal . '"/>';
    }
    return $r;
  }

  protected static function get_control_locationname($auth, $args, $tabalias, $options) {
    return data_entry_helper::text_input(array_merge([
      'label' => lang::get('LANG_Location_Name'),
      'fieldname' => 'location:name',
    ], $options));
  }

  protected static function get_control_locationcode($auth, $args, $tabalias, $options) {
    return data_entry_helper::text_input(array_merge([
      'label' => lang::get('LANG_Location_Code'),
      'fieldname' => 'location:code',
    ], $options));
  }

  protected static function get_control_locationtype($auth, $args, $tabalias, $options) {
    // To limit the terms listed add a terms option to the Form Structure as a
    // JSON array. The terms must exist in the termlist that has external key
    // indidia:location_types e.g.
    // [location type]
    // @terms=["City","Town","Village"]

    // Get the list of terms.
    $filter = NULL;
    if (array_key_exists('terms', $options)) {
      $filter = $options['terms'];
    }
    $terms = helper_base::get_termlist_terms($auth, 'indicia:location_types', $filter);

    if (count($terms) == 1) {
      // Only one location type so output as hidden control.
      return '<input type="hidden" id="location:location_type_id" ' .
        'name="location:location_type_id" ' .
        'value="' . $terms[0]['id'] . '" />' . PHP_EOL;
    }
    elseif (count($terms) > 1) {
      // Convert the $terms to an array of id => term.
      $lookup = [];
      foreach ($terms as $term) {
        $lookup[$term['id']] = $term['term'];
      }
      return data_entry_helper::select(array_merge([
        'label' => lang::get('LANG_Location_Type'),
        'fieldname' => 'location:location_type_id',
        'lookupValues' => $lookup,
        'blankText' => lang::get('LANG_Blank_Text'),
      ], $options));
    }
  }

  protected static function get_control_locationparent($auth, $args, $tabalias, $options) {
    helper_base::addLanguageStringsToJs('locationParent', [
      'newLocationOutsideParent' => 'LANG_New_location_outside_parent',
      'locationOutsideNewParent' => 'LANG_Location_outside_new_parent',
      'locationOutsideParent' => 'LANG_Location_outside_parent',
      'OK' => 'LANG_OK',
      'parentLayerTitle' => 'LANG_Parent_layer_title',
    ]);
    $extraParams = $auth['read'];
    if (array_key_exists('extraParams', $options)) {
      $extraParams = array_merge($extraParams, $options['extraParams']);
    }
    $options['extraParams'] = $extraParams;
    return data_entry_helper::select(array_merge([
      'label' => lang::get('LANG_Location_Parent'),
      'fieldname' => 'location:parent_id',
      'table' => 'location',
      'valueField' => 'id',
      'captionField' => 'name',
      'blankText' => lang::get('LANG_Blank_Text'),
    ], $options));
  }

  protected static function get_control_locationcomment($auth, $args, $tabalias, $options) {
    return data_entry_helper::textarea(array_merge([
      'fieldname' => 'location:comment',
      'label' => lang::get('LANG_Comment'),
    ], $options));
  }

  protected static function get_control_spatialreference($auth, $args, $tabalias, $options) {
    $options = array_merge($options, [
      'fieldname' => 'location:centroid_sref',
      'geomFieldname' => 'centroid_geom',
    ]);
    return parent::get_control_spatialreference($auth, $args, $tabalias, $options);
  }

  /**
   * Get the location photo control.
   */
  protected static function get_control_locationphoto($auth, $args, $tabalias, $options) {
    return data_entry_helper::file_box(array_merge([
      'table' => 'location_medium',
      'readAuth' => $auth['read'],
      'caption' => lang::get('File upload'),
    ], $options));
  }

  /**
   * Handles the construction of a submission array from a set of form values.
   *
   * @param array $values
   *   Associative array of form data values.
   * @param array $args
   *   Iform parameters.
   *
   * @return array
   *   Submission structure.
   */
  public static function get_submission($values, $args) {
    // If the location_type_id is supplied in the url, then use this.
    if (!empty($_GET['location_type_id'])) {
      $values['location:location_type_id'] = $_GET['location_type_id'];
    }
    $structure = [
      'model' => 'location',
    ];
    // Either an uploadable file, or a link to a Flickr external detail means
    // include the submodel.
    // (Copied from data_entry_helper::build_sample_occurrence_submission. If
    // file_box control is used then build_submission calls wrap_with_images
    // instead)
    if ((array_key_exists('location:medium', $values) && $values['location:medium'])
        || array_key_exists('location_medium:external_details', $values)
        && $values['location_medium:external_details']) {
      $structure['submodel'] = [
        'model' => 'location_medium',
        'fk' => 'location_id',
      ];
    }
    $s = submission_builder::build_submission($values, $structure);

    // On first save of a new location, link it to the website.
    // Be careful not to over-write other subModels (e.g. images)
    if (empty($values['location:id'])) {
      $s['subModels'][] = [
        'fkId' => 'location_id',
        'model' => [
          'id' => 'locations_website',
          'fields' => [
            'website_id' => $args['website_id'],
          ],
        ],
      ];
      // Also, on first save we might be linking to a group.
      if (!empty($values['group_id'])) {
        $s['subModels'][] = [
          'fkId' => 'location_id',
          'model' => [
            'id' => 'groups_location',
            'fields' => [
              'group_id' => $values['group_id'],
            ],
          ],
        ];
      }
    }
    return $s;
  }

  /**
   * When viewing the list of locations for this user, get the grid to insert
   * into the page. Filtering of locations is by Indicia User ID stored in the
   * user profile. Enable Easy Login module to achieve this function.
   */
  protected static function getLocationListGrid($args, $nid, $auth) {
    // User must be logged in before we can access their records.
    if (!hostsite_get_user_field('id')) {
      // Return a login link that takes you back to this form when done.
      return lang::get('Before using this facility, please <a href="' .
          hostsite_get_url( 'user/login', ['destination' => "node/$nid"]) .
          '">login</a> to the website.'
          );
    }

    // Get the Indicia User ID attribute so we can filter the grid to this user.
    if (function_exists('hostsite_get_user_field')) {
      $iUserId = hostsite_get_user_field('indicia_user_id');
    }

    if (!isset($iUserId) || !$iUserId) {
      return lang::get('LANG_No_User_Id');
    }

    // Subclassed forms may provide a getLocationListGridPreamble function.
    if (method_exists(self::$called_class, 'getLocationListGridPreamble')) {
      $r = call_user_func([self::$called_class, 'getLocationListGridPreamble']);
    }
    else {
      $r = '';
    }

    $extraParams = [
      'website_id' => $args['website_id'],
      'iUserID' => $iUserId,
    ];
    if (!$args['list_all_locations']) {
      // The option to list all locations is denied so enforce selection of own data.
      $extraParams['ownData'] = '1';
    }
    iform_load_helpers(['report_helper']);
    $r .= report_helper::report_grid([
      'id' => 'locations-grid',
      'dataSource' => $args['grid_report'],
      'mode' => 'report',
      'readAuth' => $auth['read'],
      'columns' => call_user_func([self::$called_class, 'getReportActions']),
      'itemsPerPage' => (isset($args['grid_num_rows']) ? $args['grid_num_rows'] : 10),
      'autoParamsForm' => TRUE,
      'extraParams' => $extraParams,
      'paramDefaults' => ['ownData' => '1'],
    ]);
    $r .= '<form>';
    $r .= '<input type="button" value="' . lang::get('LANG_Add_Location') . '" ' .
            'onclick="window.location.href=\'' . hostsite_get_url('node/' . ($nid->nid), ['new' => '1']) . '\'">';
    $r .= '</form>';
    return $r;
  }

  /**
   * When a form version is upgraded introducing new parameters, old forms will
   * not get the defaults for the parameters unless the Edit and Save button is
   * clicked. So, apply some defaults to keep those old forms working.
   */
  protected static function getArgDefaults($args) {
    if (!isset($args['structure']) || empty($args['structure'])) {
      $args['structure'] =
        "=Place=\r\n" .
        "?Please provide the spatial reference of the location. You can enter the reference directly, or search for a place then click on the map to set it.?\r\n" .
        "[location name]\r\n" .
        "[location code]\r\n" .
        "[location type]\r\n" .
        "[spatial reference]\r\n" .
        "[place search]\r\n" .
        "[map]\r\n" .
        "[*]\r\n" .
        "=Other Information=\r\n" .
        "?Please provide the following additional information.?\r\n" .
        "[location comment]\r\n" .
        "[*]\r\n" .
        "=*=";
    }
    if (!isset($args['grid_report'])) {
      $args['grid_report'] = 'reports_for_prebuilt_forms/simple_location_list';
    }
    return $args;
  }

  protected static function getReportActions() {
    return [
      [
        'display' => 'Actions',
        'actions' => [
          [
            'caption' => lang::get('Edit'),
            'url' => '{currentUrl}',
            'urlParams' => ['location_id' => '{id}'],
            'visibility_field' => 'editable',
          ],
        ],
      ],
    ];
  }

  /**
   * Override the default submit buttons to add a delete button where appropriate.
   */
  protected static function getSubmitButtons($args) {
    if (!self::editLocationPermitted($args)) {
      return '';
    }
    $r = '';
    global $indicia_templates;
    $r .= '<input type="submit" ' .
      'class="' . $indicia_templates['buttonDefaultClass'] . '" ' .
      'id="save-button" ' .
      'value="' . lang::get('Submit') . '" ' .
    "/>\n";
    if (!empty(data_entry_helper::$entity_to_load['location:id'])) {
      // Use a button here, not input, as Chrome does not post the input value.
      $r .= '<button type="submit" ' .
        'class="' . $indicia_templates['buttonWarningClass'] . '" ' .
        'id="delete-button" name="delete-button" value="delete" >' .
        lang::get('Delete') .
      "</button>\n";
      data_entry_helper::$javascript .= "$('#delete-button').click(function(e) {
        if (!confirm(\"Are you sure you want to delete this location?\")) {
          e.preventDefault();
          return false;
        }
      });\n";
    }
    return $r;
  }

  /**
   * Indicates whether or not the current user is permitted to edit the location
   * entity.
   *
   * @param array $args
   *   Form options array.
   *
   * @return bool
   *   TRUE if permitted, otherwise FALSE.
   */
  private static function editLocationPermitted(array $args) {
    if (empty(data_entry_helper::$entity_to_load['location:id'])) {
      return TRUE;
    }
    elseif (!empty($args['edit_permission']) && hostsite_user_has_permission($args['edit_permission'])) {
      return TRUE;
    }
    elseif (function_exists('hostsite_get_user_field')) {
      $iUserId = hostsite_get_user_field('indicia_user_id');
      if ($iUserId === '1' || $iUserId === data_entry_helper::$entity_to_load['location:created_by_id']) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
