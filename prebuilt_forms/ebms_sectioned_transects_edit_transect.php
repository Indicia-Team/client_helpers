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

require_once('sectioned_transects_edit_transect.php');
require_once('includes/user.php');

/*
 * Although the concept of branches is replaced by countries in this form, as it uses inherited code, you may
 * see references to branches in the code: these are interchangeable with countries.
 *
 * Functionality summary:
 * TBD : Make a note of the referrer.
 *
 * Transect Tab:
 * If will be displaying Transect tab: check that map is displaying the correct maps, redisplay map:
 *      if transect outside the extent of the map, pan so transect is at the centre, keep zoom the same.
 * If leaving the Transect tab: Check if data has been modified: if yes put up a dialog:
 *      Save (submits the form), Ignore (moves to new tab - values left), Cancel (does not move or save).
 * When the country field is cleared (it is mandatory), remove the country feature.
 * When a country is selected in the country field, any existing country feature is removed, and replaced with the new feature.
 *      If the sref is filled in, check if the sref inside the country: if not give a warning, but nothing else.
 * When a transect feature is added: Fill in sref, Check if in the current country:
 *      if not, give a warning.
 *      Form validation will force the filling in of a country.
 *      DO NOT DO WFS LOOKUP AT MOMENT
 * When the sref is cleared: leave country field alone, leave country feature, remove transect feature
 * When the sref is filled in: [Add the feature to the map : DONE BY MAP PANEL -> leads to transect feature actions above being triggered for country check]
 * When any field is modified: flag the form as having changed,
 * When the add user/branch user button is clicked: Either add user to list, or un-delete any previously deleted user. flag form as changed
 * When the delete user button is clicked: Flag user as deleted: strikethrough, remove value. flag form as changed
 * When the Save button is pressed: normal form submit.
 * TBD : When Delete transect button is pressed: Ask for confirmation: if OK flag all samples as deleted, flag all sections as deleted: jump back to referrer.
 * TBD : When Cancel button is pressed: check if the form has been changed: if yes ask if want to save, else jump to referrer
 * TBD : location type change functionality.
 *
 * Route Tab
 * If will be displaying Route tab:
 *      copy the transect over from the main map.
 *      check that map is displaying the correct maps, redisplay map:
 *      Pan/zoom so display the transect/route at highest zoom possible.
 *      if there is a feature for the current section: activate the modify.
 *      else Activate the draw control.
 * If leaving the Route tab:
 *      Check if the route has been changed:
 *      if yes put up a dialog: Save (submits the change to the route, no move), Ignore (reset route, moves to new tab), Cancel (does not move or save).
 * Section Select buttons: check there is a change of route.
 *      Check if previous route has changed: if yes, dialog if want to save:
 *          Continue: carries on, discardin changes.
 *          Cancel: stops and allows the user to save the data.
 *      Set clases for new active select button
 *      Load all the attributes into the section details form, reset all the names;
 *      switch off all the route map controls;
 *      if there is no section record yet, or the geometry has not been filled in,
 *          activate the line draw control
 *          hide the modify control.
 *          disable the complete route details button and the section details tab
 *      otherwise
 *          show and activate the modify control
 *          highlight/select the newly selected route.
 *          enable the complete route details button and the section details tab
 * Save Route button
 *      post change to geometry - take care if geometry is now empty, flag as unchanged.
 *      Store new geometry in previous_geometry field for the section.
 *      Recalculate the section length: save the section. Change the value on the section details form, and the field name.
 *      Recalculate the transect length: save the transect. Change the value on the main form, and the field name.
 *      Enable the complete route details button and the section details tab
 *      If the draw control is active, deacivate it and activate the modify control: highlight this route.
 * TBD: Reset route button (this does not save the record)
 *      proceed only if route is changed and previous geomtry exists.
 *      Confirm that user wishes to reset the route to the last saved .
 *      if yes, flag route as unchanged, copy the previous geometry into the current geometry
 *      if draw control is active, leave it active
 *      if the modify control is active, re-highlight the restored geometry.
 * Insert section button
 *      Ask if the user wishes to insert before of after the currently selected section.
 *      Move all the routes after the insert point up one. (no need to change any samples)
 *      Add one to the number of sections.
 *      Reload the form
 * Remove section button
 *      TBD Confirm that there are at least 2 sections on the the transect
 *      TBD Count the number of samples this section has registered against it, and then the number of occurrences.
 *      Confirm the user wishes to delete this route.
 *      Delete all the section samples for this section.
 *      Delete the section
 *      Subtract one from the number of sections; recalculate the total length of the transect: save the transect record.
 *      Renumber all the subsequent sections down one: no need to alter their samples.
 *      Reload the page
 * Erase route button (does not save)
 *      If the line draw control is active, unwind the last vertex.
 *      Otherwise: (Clear whole route)
 *      Confirm the user wishes to clear this route.
 *      If yes, remove geometry.
 *      If the modify control is active, deactivate it and activate the draw control.
 * Complete route details button.
 *      Check if route has changed: if yes, dialog if want to save:
 *          Save (submits the change to the route, moves to section details tab),
 *          Continue (reset route to previous_geometry, moves to section details tab),
 *          Cancel (does not move or save)
 * Navigate control
 *      On activate: Ensure all other controls are deactivated, and no features selected: TBD current route highlighted.
 *      No action on deactivate.

 * Modify control
 *      On activate: Ensure all other controls are deactivated, and no features selected: current route highlighted.
 *      Event: When a feature is modified, flag it as changed.
 *      On deactivation: Check if route has changed: if yes, dialog if want to save:
 *          Save (submits the change to the route then deactivate),
 *          Ignore (reset route to previous_geometry then deactivate)
 *          Cancel (does not allow control to deactivate, no save)
 * Draw control
 *      On activation
 *      If the current geometry is not empty, confirm that users want to use draw control as this will remove the existing geometry.
 *          If No, activate the modify control. Exit
 *          If yes, remove current geometry, flag as route changed.
 *      Deeactivate all other controls.
 *      Event : When a vertex is added, flag as route changed.
 *      On deactivation: Check if route has changed: if yes, dialog if want to save:
 *          Save (submits the change to the route then deactivate),
 *          Ignore (reset route to previous_geometry then deactivate)
 *          Cancel (does not allow control to deactivate, no save)
 * Select control
 *      On activation: Deactivate all other controls.
 *      Leave currently select route highlighted.
 *      On selecting a feature, deactivate self, and activate the modify control, and indicate to modify control the selected feature.
 *      No action on deactivation.
 *
 * Details Tab
 * If leaving the section Details tab: Check if the details have been changed: if yes put up a dialog: Save (submits the form), Ignore (moves to new tab), Cancel (does not move or save).
 * Section Select buttons: check there is a change of section details.
 *      Check if previous route has changed: if yes, dialog if want to save:
 *          Save (submits the change to the details, set unchanged, no move),
 *          Ignore (reload section details values, set unchanged, moves to new route),
 *          Cancel (does not move or save - exit)
 *      Set clases for new active select button
 *      Load all the attributes into the section details form, reset all the names;
 *      switch off all the route map controls;
 *      Activate the navigation control on the route.
 *      if the geometry has not been filled in,
 *          hide the modify control.
 *      otherwise
 *          show the modify control
 * When any field is modified: flag the form as having changed,
 * When the Save button is pressed: AJAX form submit: update names of all attributes.
 */
/**
 * @package Client
 * @subpackage PrebuiltForms
 * Form for adding or editing the site details on a transect which contains a number of sections.
 */
class iform_ebms_sectioned_transects_edit_transect extends iform_sectioned_transects_edit_transect {

	protected static $countryAttrId;

  /**
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_ebms_sectioned_transects_edit_transect_definition() {
    return array(
      'title'=>'EBMS Location editor',
      'category' => 'Sectioned Transects',
      'description'=>'Form for adding or editing the site details on a transect style location which has a number of sub-sections, but which can have various location types.'
    );
  }

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    $retVal = array_merge(
      parent::get_parameters(),
      	array(
      		array(
      				'name' => 'country_configuration',
      				'caption' => 'Country specific configuration',
      				'description' => 'Country specific configuration',
      				'type' => 'jsonwidget',
      				'required' => false,
      				'group'=>'Transects Editor Settings',
      				'schema' => '{
  "type":"seq",
  "title":"Country Configuration List",
  "sequence":
  [{"type":"map",
    "title":"Country Configuration",
    "mapping": {
      "country_names":{
        "type":"seq",
        "title":"Country List",
        "sequence": [{"type":"map",
                     "title":"Countries",
                     "mapping": {"country":{"type":"str","desc":"Name of country this configuration applies to. Use &quot;Default&quot; for all/default"}}}]},
      "location_types":{
        "type":"seq",
        "title":"Location Types",
        "sequence": [{
            "type":"map",
            "title":"Location Type",
            "mapping": {
              "term": {"type":"str","desc":"Location Type term. Use &quot;Default&quot; for all/default"},
              "creation_permission": {"type":"str","desc":"Permission required to create."},
              "can_change_num_sections": {"type":"bool","title":"Can change number of sections"},
              "num_sections": {"type":"int","title":"Max or fixed number of sections."}}}]},
      "map":{
        "type":"map",
        "title":"Map",
        "mapping": {
          "tilecacheLayers":{"type":"seq",
            "title":"Tile Layers",
            "sequence": [{"type":"map",
              "title":"Layer",
              "mapping": {"caption":{"type":"str","title":"Layer name"},
                        "servers": { "type":"seq",
                            "title":"Servers",
                            "sequence": [{"type":"str","title":"Server"}]},
                        "layerName":{"type":"str","title":"Layer designation"},
                        "settings":{"type":"map",
                            "title":"Settings",
                            "mapping": {"format":{"type":"str","title":"Format"},
                                        "srs":{"type":"str","title":"SRS"},
                                        "isBaseLayer":{"type":"bool","title":"Is a base layer"},
                                        "transparent":{"type":"bool","title":"Is transparent"},
                                        "opacity": {"type":"number","title":"Layer opacity"},
                                        "resolutions" : {"type":"seq",
                                                         "title":"Resolutions",
                                                         "sequence": [{"type":"number","title":"Resolution"}]},
                                        "maxExtent" : {"type":"seq",
                                                         "title":"Extent Components",
                                                         "sequence": [{"type":"number","title":"Extent Component"}]} }},
                        "setInitialVisibility":{"type":"bool","title":"Initial Visibility"}
              }
           }]},
          "tile_cache": {"type":"str","title":"Tile cache JSON."},
          "openlayer_options": {"type":"str","title":"OpenLayers Options JSON."},
          "sref_systems": {"type":"str","title":"Allowed Spatial Ref Systems."}
        }
      }
    }
   }
  ]
}'
      		),
        array(
          'name'=>'country_location_type_id',
          'caption'=>'Country Location Type Id',
          'description'=>'The location type id of the Country location type.',
          'type' => 'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('view'=>'list', 'termlist_external_key'=>'indicia:location_types'),
          'required' => true,
          'group'=>'Transects Editor Settings'
        ),
        array(
          'name'=>'countries',
          'caption'=>'Valid Countries',
          'description'=>'A bar (&#124;) separated list of countries to be included as options in the country control. Leave black for all. These can be either names or Indicia location IDs.',
          'type'=>'string',
          'required' => false,
          'group'=>'Transects Editor Settings'
        ),
        array(
      		'name'=>'country_attr',
      		'caption'=>'Country Location attribute',
      		'description'=>'Location attribute that stores the Country. Single value integer.',
      		'type'=>'select',
      		'table'=>'location_attribute',
      		'valueField'=>'caption',
      		'captionField'=>'caption',
      		'group'=>'Transects Editor Settings',
      		'required'=>true
      	),
        array(
          'name'=>'country_layer_lookup',
          'caption'=>'WFS Layer specification for Country Lookup',
          'description'=>'Comma separated: proxiedurl,featurePrefix,featureType,featureNS,srsName. Leave blank for no lookup.',
          // http://biomonitor.mnhn.lu/?q=proxy&url=http://biomonitor.mnhn.lu:8080/geoserver/wfs,indicia,locations,http://dbtest.dyndns.info/indicia/,EPSG:2169
          'type'=>'textarea',
          'required' => false,
          'group'=>'Transects Editor Settings',
        ),

        array(
          'name'=>'autogenerateCode',
          'caption'=>'Autogenerate Code',
          'description'=>'Autogenerate Location Codes.',
          'type' => 'boolean',
          'required' => false,
          'group'=>'Transects Editor Settings'
        ),
      	array(
      		'name' => 'autogeneratePrefix',
      		'caption' => 'Autogenerate Prefix',
      		'description' => 'The prefix for the autogenerated code.',
      		'type'=>'string',
      		'group' => 'Transects Editor Settings',
          	'required' => false,
      		'default' => 'EBMS:'
      	),
        array(
          'name'=>'display_location_type',
          'caption'=>'Display location type',
          'description'=>'Where there is no location_type_id control displayed, check this to display the location type term.',
          'type' => 'boolean',
          'required' => false,
          'group'=>'Transects Editor Settings'
        ),
      	array(
      		'name'=>'autocalc_transect_length_attr_id',
      		'caption'=>'Location attribute to autocalc transect length',
      		'description'=>'Location attribute that stores the total transect length: summed from the lengths of the individual sections.',
      		'type'=>'select',
      		'table'=>'location_attribute',
      		'valueField'=>'id',
      		'captionField'=>'caption',
      		'group'=>'Transects Editor Settings',
      		'required'=>false
      	),
      	array(
      		'name'=>'cms_attr',
      		'caption'=>'CMS User Location attribute',
      		'description'=>'Location attribute that stores the CMS User ID. Multivalue integer attribute used to assign people to locations.',
      		'type'=>'select',
      		'table'=>'location_attribute',
      		'valueField'=>'caption',
      		'captionField'=>'caption',
      		'group'=>'Transects Editor Settings',
      		'required'=>true
      	),
        array(
          'name'=>'custom_attribute_options',
          'caption'=>'Options for custom attributes',
          'description'=>'A list of additional options to pass through to custom attributes, one per line. Each option should be specified as '.
              'the attribute name followed by | then the option name, followed by = then the value. For example, smpAttr:1|class=control-width-5.',
          'type'=>'textarea',
      	  'group'=>'Transects Editor Settings'
        )

    ));
    for($i= count($retVal)-1; $i>=0; $i--){
      switch($retVal[$i]['name']) {
        case 'transect_type_term':
        case 'location_boundary_id':
        case 'allow_user_assignment':
        case 'maxSectionCount':
        	unset($retVal[$i]);
        	break;
      	case 'survey_id':
          $retVal[$i]['description'] = 'The survey that data will be used to define custom attributes. This needs to match the survey used to submit visit data for sites of the first location type.';
          $retVal[$i]['group'] = 'Transects Editor Settings';
          break;
    	case 'georefDriver':
    	  $retVal[$i]['required']=false; // method of georef detection is to see if driver specified: allows ommision of area preferences.
          break;
    	case 'default_section_grid_ref':
    	  $retVal[$i]['lookupValues']['sectionCentroid1'] = 'Section centroid to 1m accuracy';
          break;
    	default:
          break;
      }
    }
    return $retVal;
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
  public static function get_form($args, $nid, $response=null) {

      $checks=self::check_prerequisites();
    $args = self::getArgDefaults($args);
    if ($checks!==true)
      return $checks;
    iform_load_helpers(array('map_helper'));
//    data_entry_helper::add_resource('jquery_form');
    self::$ajaxFormUrl = iform_ajaxproxy_url($nid, 'location');
    self::$ajaxFormSampleUrl = iform_ajaxproxy_url($nid, 'sample');
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);

    // The primary location is taken as the first location mentioned in the default group.
    $mainLocationType = false;
    $country_configurations = json_decode($args['country_configuration'],true);
    foreach($country_configurations as $idx=>$country_configuration) {
    	if(!isset($country_configuration['country_names'])) continue;
      for($j=0, $found = false; $j < count($country_configuration['country_names']); $j++) {
        if($country_configuration['country_names'][$j]['country'] == "Default")
          $found = true;
        else {
          $location = data_entry_helper::get_population_data(array(
              'table' => 'location',
              'extraParams' => $auth['read'] +
                    array('view'=>'detail',
                          'name'=>$country_configuration['country_names'][$j]['country'],
                          'deleted'=>'f',
                          'location_type_id'=>$args['country_location_type_id'],
                    )
          ));
          if(count($location) != 1)
//            throw new exception("Configuration error: looking for Country '".$country_configuration['country_names'][$j]['country']."' and ".count($location)." location records returned.");
            $country_configurations[$idx]['country_names'][$j]['id'] = -1;
          else
            $country_configurations[$idx]['country_names'][$j]['id'] = $location[0]['id'];
        }
      }
      if($found) $mainLocationType = $country_configuration['location_types'][0]['term'];
      // convert all location types to ids.
      foreach($country_configuration['location_types'] as $id2 => $location_type) {
        $term = helper_base::get_termlist_terms($auth, 'indicia:location_types', array($location_type['term']));
        $country_configurations[$idx]['location_types'][$id2]['id'] = $term[0]['id'];
        if(isset($country_configurations[$idx]['location_types'][$id2]['creation_permission']))
          $country_configurations[$idx]['location_types'][$id2]['can_create'] =
              hostsite_user_has_permission($country_configurations[$idx]['location_types'][$id2]['creation_permission']);
        else
          $country_configurations[$idx]['location_types'][$id2]['can_create'] = true;
      };
    }
    $settings = array(
      'mainLocationType' => helper_base::get_termlist_terms($auth, 'indicia:location_types', array($mainLocationType)),
      'sectionLocationType' => helper_base::get_termlist_terms($auth, 'indicia:location_types', array(empty($args['section_type_term']) ? 'Section' : $args['section_type_term'])),
      'locationId' => isset($_GET['id']) ? $_GET['id'] : null,
      'canEditBody' => true,
      'canEditSections' => true, // this is specifically the number of sections: so can't delete or change the attribute value.
      // Allocations of Country Manager are done by a person holding the managerPermission.
      'canAllocCountry' => $args['managerPermission']=="" || hostsite_user_has_permission($args['managerPermission']),
      // Allocations of Users are done by a person holding the managerPermission or the allocate Country Manager permission.
      // The extra check on this for Country managers is done later
      'canAllocUser' => $args['managerPermission']=="" || hostsite_user_has_permission($args['managerPermission']),
      'country_configurations' => $country_configurations,
      'country_location_type_id' => $args['country_location_type_id'],
      'country_layer_lookup' => explode(',', $args['country_layer_lookup'])
    );

    // WARNING!!!! we are making the assumption that the attributes are defined to be the same for all the location_types.
    $settings['attributes'] = data_entry_helper::getAttributes(array(
        'id' => $settings['locationId'],
        'valuetable'=>'location_attribute_value',
        'attrtable'=>'location_attribute',
        'key'=>'location_id',
        'fieldprefix'=>'locAttr',
        'extraParams'=>$auth['read'],
        'survey_id'=>$args['survey_id'],
        'location_type_id' => $settings['mainLocationType'][0]['id'],
        'multiValue' => true
    ));
    $settings['section_attributes'] = data_entry_helper::getAttributes(array(
        'valuetable'=>'location_attribute_value',
        'attrtable'=>'location_attribute',
        'key'=>'location_id',
        'fieldprefix'=>'locAttr',
        'extraParams'=>$auth['read'],
        'survey_id'=>$args['survey_id'],
        'location_type_id' => $settings['sectionLocationType'][0]['id'],
        'multiValue' => true
    ));

    // The following deals with special processing associate with specific attributes.
//    drupal_set_message(print_r($settings['attributes'],true));

    // TODO NTH Add functionality to allow selection between CMS Attr ID and Indicia User ID
    // We are assigning people to locations, rather than locations to people.
    // This version of the form assumes that user allocation and country allocation are givens.
    if (false == ($settings['cmsUserAttr'] = self::extract_attr($settings['attributes'], $args['cms_attr'])))
        return 'This form is designed to be used with an attribute setup to assign users to locations: '.
          'the current configuration names this attribute as '.$args['cms_attr'].
          '. This attribute should be shared with all location types.';
    // keep a copy of the cms user ID attribute so we can use it later.
    self::$cmsUserAttrId = $settings['cmsUserAttr']['attributeId'];

    // TODO NTH Add functionality to allow selection between CMS Attr ID and Indicia User ID
    if (false== ($settings['countryAttr'] = self::extract_attr($settings['attributes'], $args['country_attr'])))
      return 'This form is designed to be used with an attribute setup to assign the location to a country'.
             '. This attribute should be shared with all location types.';
    // keep a copy of the cms user ID attribute so we can use it later.
    self::$countryAttrId = $settings['countryAttr']['attributeId'];

    data_entry_helper::$javascript .= "indiciaData.sections = {};\n";
    $settings['sections']=array();
    $settings['numSectionsAttr'] = "";
    $settings['autocalcSectionLengthAttrId'] = empty($args['autocalc_section_length_attr_id']) ? 0 : $args['autocalc_section_length_attr_id'];
    if ($settings['autocalcSectionLengthAttrId'] > 0)
    	data_entry_helper::$javascript .= "$('#locAttr\\\\:".$settings['autocalcSectionLengthAttrId']."').attr('readonly','readonly').css('color','graytext').css('background-color','#d0d0d0');\n";
    $settings['autocalcTransectLengthAttrId'] = empty($args['autocalc_transect_length_attr_id']) ? 0 : $args['autocalc_transect_length_attr_id'];
    $settings['autocalcTransectLengthAttrName'] = 0;

    $settings['defaultSectionGridRef'] = empty($args['default_section_grid_ref']) ? 'parent' : $args['default_section_grid_ref'];
    if ($settings['locationId']) {
      data_entry_helper::load_existing_record($auth['read'], 'location', $settings['locationId']);
      $settings['walks'] = data_entry_helper::get_population_data(array(
        'table' => 'sample',
        'extraParams' => $auth['read'] + array('view'=>'detail','location_id'=>$settings['locationId'],'deleted'=>'f'),
        'nocache' => true
      ));
      // Work out permissions for this user: note that canAllocCountry setting effectively shows if a manager.
      if(!$settings['canAllocCountry']) {
        // Check whether I am a normal user and it is allocated to me, and also if I am a branch manager and it is allocated to me.
        $settings['canEditBody'] = false;
        $settings['canEditSections'] = false;
        if(count($settings['walks']) == 0 &&
            isset($settings['cmsUserAttr']['default']) &&
            !empty($settings['cmsUserAttr']['default'])) {
          foreach($settings['cmsUserAttr']['default'] as $value) { // multi value
            if($value['default'] == hostsite_get_user_field('id')) { // comparing string against int so no triple equals
              $settings['canEditBody'] = true;
              $settings['canEditSections'] = true;
              break;
            }
          }
        }
        // If a Country Manager and not a main manager, then can't edit the number of sections
        if(hostsite_user_has_permission($args['branch_assignment_permission'])) {
            $myCountryReplacement = '{ebms_scheme_country_ids}';
            $myCountry = apply_user_replacements($myCountryReplacement);
            if ($myCountry == $settings['countryAttr']['default']) {
              $settings['canEditBody'] = true;
              $settings['canAllocUser'] = true;
            }
        }
      } // for an admin user the defaults apply, which will be can do everything.
      // find the number of sections attribute.
      foreach($settings['attributes'] as $attr) {
        if ($attr['caption']==='No. of sections') {
          $settings['numSectionsAttr'] = $attr['fieldname'];
          $settings['numSectionsAttrOriginalValue'] = $attr['displayValue'];
          for ($i=1; $i<=$attr['displayValue']; $i++) {
            $settings['sections']["S$i"]=null;
          }
        }
        if (isset($args['autocalc_transect_length_attr_id']) &&
            $args['autocalc_transect_length_attr_id'] != '' &&
            $attr['attributeId']==$args['autocalc_transect_length_attr_id']) {
          $settings['autocalcTransectLengthAttrName'] = $attr['fieldname'];
          data_entry_helper::$javascript .= "$('#".str_replace(':','\\\\:',$attr['id'])."').attr('readonly','readonly').css('color','graytext').css('background-color','#d0d0d0');\n";
        }
      }
      $sections = data_entry_helper::get_population_data(array(
        'table' => 'location',
        'extraParams' => $auth['read'] + array('view'=>'detail','parent_id'=>$settings['locationId'],'deleted'=>'f','orderby'=>'id'),
        'nocache' => true
      ));
      foreach($sections as $section) {
        $code = $section['code'];
        if(in_array($section['centroid_sref_system'], array('osgb','osie')))
        	$section['centroid_sref_system'] = strtoupper($section['centroid_sref_system']);
        data_entry_helper::$javascript .= "indiciaData.sections.$code = {'geom':'".$section['boundary_geom']."','id':'".$section['id']."','sref':'".$section['centroid_sref']."','system':'".$section['centroid_sref_system']."'};\n";
        $settings['sections'][$code]=$section;

        if (isset($args['autocalc_transect_length_attr_id']) &&
        		$args['autocalc_transect_length_attr_id'] != '') {
    		$section_attributes = data_entry_helper::getAttributes(array(
		        'valuetable'=>'location_attribute_value',
		        'attrtable'=>'location_attribute',
		        'key'=>'location_id',
		        'fieldprefix'=>'locAttr',
		        'extraParams'=>$auth['read'] + array('id' => $args['autocalc_section_length_attr_id']),
		        'survey_id'=>$args['survey_id'],
		        'location_type_id' => $settings['sectionLocationType'][0]['id'],
		        'id' => $section['id']
		    ), false);
    		if(count($section_attributes) > 0 &&
    				$section_attributes[0]['default'] != '')
    			data_entry_helper::$javascript .= "indiciaData.sections.".$code.".sectionLen = ".$section_attributes[0]['default']." ;\n";
        }
      }
    } else { // not an existing site therefore no walks or sections. On initial save, no section data is created.
      foreach($settings['attributes'] as $attr) {
        if ($attr['caption']==='No. of sections') {
          $settings['numSectionsAttr'] = $attr['fieldname'];
          $settings['numSectionsAttrOriginalValue'] = 1;
        }
        if ($attr['attributeId']==$args['autocalc_transect_length_attr_id']) {
          $settings['autocalcTransectLengthAttrName'] = $attr['fieldname'];
          data_entry_helper::$javascript .= "$('#".str_replace(':','\\\\:',$attr['id'])."').attr('readonly','readonly').css('color','graytext').css('background-color','#d0d0d0');\n";
        }
      }
      $settings['walks'] = array();
    }
    $r = '<div id="controls">';
    $headerOptions = array('tabs'=>array('#site-details'=>lang::get('{1} Details', ($settings['locationId'] ? data_entry_helper::$entity_to_load['location:name'] : lang::get('Site')))));
    if ($settings['locationId']) {
      $headerOptions['tabs']['#your-route'] = lang::get('Your Route');
      if(count($settings['section_attributes']) > 0)
        $headerOptions['tabs']['#section-details'] = lang::get('Section Details');
    }
    $r .= data_entry_helper::tab_header($headerOptions);
    data_entry_helper::enable_tabs(array(
          'divId'=>'controls',
          'style'=>'Tabs',
          'progressBar' => isset($args['tabProgress']) && $args['tabProgress']==true
          ,'active' => (isset($_GET['route-tab']) || ($_SERVER['REQUEST_METHOD'] === 'POST' && $settings['locationId']) ? 'your-route' : 'site-details')
    ));
    $r .= self::get_site_tab($auth, $args, $settings);
    if ($settings['locationId']) {
      $r .= self::get_your_route_tab($auth, $args, $settings);
      if(count($settings['section_attributes']) > 0)
        $r .= self::get_section_details_tab($auth, $args, $settings);
    }
    $r .= '</div>'; // controls
    data_entry_helper::enable_validation('input-form');
    if (function_exists('drupal_set_breadcrumb')) {
      $breadcrumb = array();
      $breadcrumb[] = l(lang::get('Home'), '<front>');
      $breadcrumb[] = l(lang::get('Sites'), $args['sites_list_path']);
      if ($settings['locationId'])
        $breadcrumb[] = data_entry_helper::$entity_to_load['location:name'];
      else
        $breadcrumb[] = lang::get('New Site');
      drupal_set_breadcrumb($breadcrumb);
    }
    // Inform JS where to post data to for AJAX form saving
    data_entry_helper::$javascript .= 'indiciaData.ajaxUrl="'.hostsite_get_url('iform/ajax/ebms_sectioned_transects_edit_transect')."\";\n";

    data_entry_helper::$javascript .= 'indiciaData.ajaxFormPostUrl="'.self::$ajaxFormUrl."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.ajaxFormPostSampleUrl="'.self::$ajaxFormSampleUrl."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.autogeneratePrefix="'.$args['autogeneratePrefix']."\";\n";
    data_entry_helper::$javascript .= "indiciaData.indiciaSvc = '".data_entry_helper::getRootFolder() . data_entry_helper::client_helper_path() . "proxy.php?url=".data_entry_helper::$base_url."';\n";
    data_entry_helper::$javascript .= "indiciaData.readAuth = {nonce: '".$auth['read']['nonce']."', auth_token: '".$auth['read']['auth_token']."'};\n";
    data_entry_helper::$javascript .= "indiciaData.currentSection = '';\n";
    data_entry_helper::$javascript .= "indiciaData.settings = ".json_encode($settings).";\n";
    data_entry_helper::$javascript .= "indiciaData.sitesListPath = '" . $args['sites_list_path'] . "';\n";
    data_entry_helper::$javascript .= "indiciaData.sectionTypeId = '".$settings['sectionLocationType'][0]['id']."';\n";
    data_entry_helper::$javascript .= 'indiciaData.langStrings = {' .
            '"Yes":"'.lang::get('Yes').'",'.
            '"No":"'.lang::get('No').'",'.
            '"OK":"'.lang::get('OK').'",'.
            '"Cancel":"'.lang::get('Cancel').'",'.
            '"Continue":"'.lang::get('Continue').'",'.
            '"Before":"'.lang::get('Before').'",'.
            '"After":"'.lang::get('After').'"'.
            '};'.PHP_EOL;
    data_entry_helper::$javascript .= "indiciaData.sectionDeleteConfirm = \"".lang::get('Are you sure you wish to delete section')."\";\n";
    data_entry_helper::$javascript .= "indiciaData.sectionInsertConfirm = \"".lang::get('Are you sure you wish to insert a new section after section')."\";\n";
    data_entry_helper::$javascript .= "indiciaData.changeConfirm = \"".lang::get('Do you wish to save the currently unsaved modifications you have made to the route and/or the section details, before selecting the new Section? Select Yes to save the data, or No to discard the changes.')."\";\n";
    data_entry_helper::$javascript .= "indiciaData.changeConfirmCancel = \"".lang::get('Do you wish to save the currently unsaved modifications you have made to the route and/or the section details, before selecting the new Section? Select Yes to save the Route, No to discard the changes, or Cancel to keep the changes without saving them and without swapping to the new section.')."\";\n";
    data_entry_helper::$javascript .= "indiciaData.autocalcSectionLengthAttrId = ".$settings['autocalcSectionLengthAttrId'].";\n";
    data_entry_helper::$javascript .= "indiciaData.autocalcTransectLengthAttrId = ".$settings['autocalcTransectLengthAttrId'].";\n";
    data_entry_helper::$javascript .= "indiciaData.autocalcTransectLengthAttrName = \"".$settings['autocalcTransectLengthAttrName']."\";\n";
    data_entry_helper::$javascript .= "indiciaData.defaultSectionGridRef = '".$settings['defaultSectionGridRef']."';\n";
    data_entry_helper::$onload_javascript .= "\nbind_events();\n";

    return $r;
  }

  private static function get_site_tab($auth, $args, &$settings) {

  	$blockOptions = array();
  	if (isset($args['custom_attribute_options']) && $args['custom_attribute_options']) {
  		$blockOptionList = explode("\n", $args['custom_attribute_options']);
  		foreach($blockOptionList as $opt) {
  			$tokens = explode('|', $opt);
  			$optvalue = explode('=', $tokens[1]);
  			$blockOptions[$tokens[0]][$optvalue[0]] = $optvalue[1];
  		}
  	}

    // if location is predefined, can not change certain fields unless a 'managerPermission'
    $canEditFields = !$settings['locationId'] ||
        (isset($args['managerPermission']) && $args['managerPermission']!= '' && hostsite_user_has_permission($args['managerPermission']));

    $r = '<div id="site-details" class="ui-helper-clearfix">';
    $r .= '<form method="post" id="input-form">';
    $r .= $auth['write'];
    $r .= "<input type=\"hidden\" name=\"website_id\" value=\"".$args['website_id']."\" />\n";
    $r .= '<input type="hidden" name="read_nonce" value="' . $auth['read']['nonce'] . '"/>';
    $r .= '<input type="hidden" name="read_auth_token" value="' . $auth['read']['auth_token'] . '"/>';
    
    $r .= '<div id="cols" class="ui-helper-clearfix"><div class="left" style="width: 54%">';

    // Special for EBMS is the country block: this should just have the country attribute in it: a text location attribute; powered
    // as a drop down from the location list, location_type_id=Country.
    $r .= '<fieldset><legend>'.$settings['countryAttr']['caption'].'</legend>';
    if($canEditFields) {
      $extraParams = $auth['read'] + array('view'=>'detail', 'deleted'=>'f', 'location_type_id'=>$args['country_location_type_id'],'orderby'=>'name');
      $locations = data_entry_helper::get_population_data(array(
              'table' => 'location',
              'extraParams' => $extraParams,
              'columns'=>'id,name'
          ));
    	$values = array();
      if(isset($args['countries']) && $args['countries']!='') {
        $countries = explode('|', $args['countries']);
        foreach($locations as $location)
          if(in_array($location['id'], $countries) || in_array($location['name'], $countries))
            $values[$location['id']] = lang::get($location['name']);
      } else {
        foreach($locations as $location)
          $values[$location['id']] = lang::get($location['name']);
      }
      $r .= data_entry_helper::select(array(
        'id'=>$settings['countryAttr']['id'],
        'fieldname'=>$settings['countryAttr']['fieldname'],
        'label'=>$settings['countryAttr']['caption'], // already translated
        'lookupValues' => $values,
        'blankText'=>lang::get('<Please select>'),
        'helpText'=>lang::get('Although you can set this field yourself, it will be filled in automatically when you draw the site on the map.'),
        'validation'=>array('required'),
        'default'=>$settings['countryAttr']['default']
      ));
    } else {
      // Put in a dummy select
      $r .= data_entry_helper::hidden_text(array(
                'id'=>$settings['countryAttr']['id'],
                'fieldname'=>$settings['countryAttr']['fieldname'],
                'default'=>$settings['countryAttr']['default']
              )).
      data_entry_helper::select(array(
                'fieldname'=>'dummy-country',
                'label'=>$settings['countryAttr']['caption'], // already translated
                'table'=>'location',
                'valueField'=>'id',
                'captionField'=>'name',
                'blankText'=>'', // shouldn't really be used
                'extraParams'=>$auth['read']+array('view'=>'list',
                  'location_type_id'=>$args['country_location_type_id']),
                'default'=>$settings['countryAttr']['default'],
                'disabled'=>'disabled="disabled"' // so doesn't need the valid countries restriction
              ));
    }
    $r .= '</fieldset>';

    $r .= '<fieldset><legend>'.lang::get('Site Details').'</legend>';

    // Unless there is only one type of location, when the location type is hidden, the location types will be driven by the
    // Country.
    $typeTerms = array();
    $country_configurations = json_decode($args['country_configuration'],true);
    foreach($country_configurations as $country_configuration) {
      foreach($country_configuration['location_types'] as $location_type)
        $typeTerms[] = $location_type['term'];
    }
    $typeTerms = array_unique($typeTerms);
    $typeTermIDs = helper_base::get_termlist_terms($auth, 'indicia:location_types', $typeTerms);
    $lookUpValues = array('' => '<' . lang::get('please select') . '>');
    foreach($typeTermIDs as $termDetails){
      $lookUpValues[$termDetails['id']] = $termDetails['term'];
    }
    $display_location_type_id = false;
    if($canEditFields) {
      if(count($lookUpValues)>2) { // includes the "please select" empty option
        $r .= data_entry_helper::select(array(
            'label' => lang::get('Site Type'),
            'id' => 'location_type_id',
            'fieldname' => 'location:location_type_id',
            'lookupValues' => $lookUpValues
        ));
      } else if(!$settings['locationId']) { // new site, only one possible value
      	$r .= '<input type="hidden" name="location:location_type_id" id="location_type_id" value="'.$typeTermIDs[0]['id'].'" />';
      	$display_location_type_id = $typeTermIDs[0]['id'];
      } else { // else user can edit the location type for an existing site but only one option
      	$r .= '<input type="hidden" name="location:location_type_id" id="location_type_id" value="'.data_entry_helper::$entity_to_load['location:location_type_id'].'" />';
      	$display_location_type_id = data_entry_helper::$entity_to_load['location:location_type_id'];
      }
    } else {
    	// else user can't edit the location_type_id for an existing site -> just leave the location_type_id alone: don't even include in the data submitted.
      	$display_location_type_id = data_entry_helper::$entity_to_load['location:location_type_id'];
    }
    if($display_location_type_id && !empty($args['display_location_type']) && $args['display_location_type']) {
    	$list = data_entry_helper::get_population_data(array(
    			'table' => 'termlist',
    			'extraParams' => $auth['read'] + array('external_key' => 'indicia:location_types')
    	));
    	$terms = data_entry_helper::get_population_data(array(
          'table' => 'termlists_term',
          'extraParams' => $auth['read'] + array(
    				'view' => 'detail',
    				'termlist_id' => $list[0]['id'],
    				'id' => $display_location_type_id)
    	));
    	$r .= str_replace('%s', $terms[0]['term'], '<h3>'.lang::get('This site is of type &quot;%s&quot;.').'</h3>');
    }

    if ($settings['locationId'])
      $r .= '<input type="hidden" name="location:id" id="location-id" value="'.$settings['locationId']."\" />\n";
    $r .= data_entry_helper::text_input(array(
      'id' => 'location-name',
      'fieldname' => 'location:name',
      'label' => lang::get('Site Name'),
      'class' => 'control-width-4 required',
      'disabled' => $settings['canEditBody'] ? '' : ' disabled="disabled" '
    ));
    if (!$settings['canEditBody']){
      $r .= '<p>'.lang::get('This site cannot be edited because there are walks recorded on it. Please contact the site administrator if you think there are details which need changing.').'</p>';
    } else if(count($settings['walks']) > 0) { // can edit it
      $r .= '<p>'.lang::get('This site has walks recorded on it. Please do not change the site details without considering the impact on the existing data.').'</p>';
    }
    $list = explode(',', str_replace(' ', '', $args['spatial_systems'])); // these are the default systems.
    $settings['defaultSystems'] = $list;
    foreach($list as $system) {
      $systems[$system] = lang::get($system);
    }
    foreach($country_configurations as $country_configuration) {
      if(!isset($country_configuration['map'])) continue;
      $list = explode(',', str_replace(' ', '', $country_configuration['map']['sref_systems'])); // these are the default systems.
      foreach($list as $system) {
        if(!isset($systems[$system])) $systems[$system] = lang::get($system);
      }
    }

    if(isset(data_entry_helper::$entity_to_load['location:centroid_sref_system']) &&
        in_array(data_entry_helper::$entity_to_load['location:centroid_sref_system'], array('osgb','osie')))
      data_entry_helper::$entity_to_load['location:centroid_sref_system'] = strtoupper(data_entry_helper::$entity_to_load['location:centroid_sref_system']);
    $r .= data_entry_helper::sref_and_system(array(
      'fieldname' => 'location:centroid_sref',
      'geomFieldname' => 'location:centroid_geom',
      'label' => lang::get('Grid Ref.'),
      'systems' => $systems,
      'class' => 'required',
      'helpText' => lang::get('Click on the map to set the central grid reference.'),
      'disabled' => $settings['canEditBody'] ? '' : ' disabled="disabled" '
    ));
    $locCodeDefn = array(
        'id' => 'location-code',
        'fieldname' => 'location:code',
        'label' => lang::get('Site Code'),
        'class' => 'control-width-4'
    );
    if(!hostsite_user_has_permission($args['managerPermission'])) {
        $locCodeDefn['disabled'] = ' readonly="readonly" ';
        $locCodeDefn['helpText'] = lang::get('An internal reference; this value can only be edited by a manager.');
        data_entry_helper::$javascript .= "$('[name=location\\\\:code]').css('color','graytext').css('background-color','#d0d0d0');\n";
    }
    $r .= data_entry_helper::text_input($locCodeDefn);

    // setup the map options
    $options = iform_map_get_map_options($args, $auth['read']);
    // find the form blocks that need to go below the map.
    $bottom = '';
    $bottomBlocks = explode("\n", isset($args['bottom_blocks']) ? $args['bottom_blocks'] : '');
    foreach ($bottomBlocks as $block) {
      $bottom .= get_attribute_html($settings['attributes'], $args, array('extraParams'=>$auth['read'], 'disabled' => $settings['canEditBody'] ? '' : ' disabled="disabled" '), $block, $blockOptions);
    }
    // other blocks to go at the top, next to the map
    if(isset($args['site_help']) && $args['site_help'] != ''){
      $r .= '<p class="ui-state-highlight page-notice ui-corner-all">'.t($args['site_help']).'</p>';
    }
    $r .= get_attribute_html($settings['attributes'], $args, array('extraParams'=>$auth['read']), null, $blockOptions);
    $r .= '</fieldset>';
    $r .= "</div>"; // left
    $r .= '<div class="right" style="width: 44%">';
    if(isset($args['georefDriver']) && $args['georefDriver']!='' && !$settings['locationId']) {
      $help = t('Use the search box to find a nearby town or village, then drag the map to pan and click on the map to set the centre grid reference of the transect. '.
          'Alternatively if you know the grid reference you can enter it in the Grid Ref box on the left.');
      $r .= '<p class="ui-state-highlight page-notice ui-corner-all">'.$help.'</p>';
      $r .= data_entry_helper::georeference_lookup(iform_map_get_georef_options($args, $auth['read']));
    }
    if(isset($args['maxPrecision']) && $args['maxPrecision'] != ''){
      $options['clickedSrefPrecisionMax'] = $args['maxPrecision'];
    }
    if(isset($args['minPrecision']) && $args['minPrecision'] != ''){
      $options['clickedSrefPrecisionMin'] = $args['minPrecision'];
    }
    $olOptions = iform_map_get_ol_options($args);
    $options['clickForSpatialRef']=$settings['canEditBody'];
    $options['gridRefHintInFooter']=false;
    $r .= map_helper::map_panel($options, $olOptions);
    $r .= '</div></div>'; // right
    if (!empty($bottom))
      $r .= $bottom;
    if ($settings['canAllocUser']) {
        $r .= self::get_user_assignment_control($auth['read'], $settings['cmsUserAttr'], $args);
    } else if (!$settings['locationId']) {
        // for a new record, we need to link the current user to the location if they are not admin.
        $r .= '<input type="hidden" name="locAttr:'.self::$cmsUserAttrId.'" value="'.hostsite_get_user_field('id').'">';
    }
    if ($settings['canEditBody']) {
      if($settings['locationId']) {
          $r .= '<button type="button" class="indicia-button right" id="delete-site">'.lang::get('Delete').'</button>' ;
      }
      $r .= '<button type="submit" class="indicia-button right">'.lang::get('Save').'</button>';
    }
    $r .='</form></div>'; // site-details
    // This must go after the map panel, so it has created its toolbar
    // data_entry_helper::$onload_javascript .= "$('#current-section').change(selectSection);\n";
    if($settings['canEditBody'] && $settings['locationId']) {
      $walkIDs = array();
      foreach($settings['walks'] as $walk)
        $walkIDs[] = $walk['id'];
      $sectionIDs = array();
      foreach($settings['sections'] as $code=>$section)
        $sectionIDs[] = $section['id'];
/**
 * TODO
      data_entry_helper::$javascript .= "
deleteSurvey = function(){
  if(confirm(\"".(count($settings['walks']) > 0 ? count($settings['walks']).' '.lang::get('walks will also be deleted when you delete this location.').' ' : '').lang::get('Are you sure you wish to delete this location?')."\")){
    deleteWalks([".implode(',',$walkIDs)."]);
    deleteSections([".implode(',',$sectionIDs)."]);
    $('#delete-transect').html('Deleting Site');
    deleteLocation(".$settings['locationId'].");
    $('#delete-transect').html('Done');
    window.location='". hostsite_get_url($args['sites_list_path']) ."';
  };
};
$('#delete-transect').click(deleteSurvey);
";
*/
    }
    return $r;
  }

  protected static function get_section_details_tab($auth, $args, $settings) {
      $r = '<div id="section-details" class="ui-helper-clearfix">';
      $r .= '<form method="post" id="section-form" action="'.self::$ajaxFormUrl.'">';
      $r .= '<fieldset><legend>'.lang::get('Section Details').'</legend>';
      // Output a selector for the current section.
      $r .= self::section_selector($settings, 'section-select')."<br/>";
      if ($settings['canEditBody']){
          $r .= '<input type="hidden" name="website_id" value="'.$args['website_id'].'" />';
          $r .= '<input type="hidden" name="location:id" value="" id="section-location-id" />';
          $r .= '<input type="hidden" id="section-details-transaction-id" name="transaction_id" value="" />';
      }
      // for the SRef, we want to be able to edit the sref, but just display the system. Do not want the Geometry.
      $r .= '<label for="imp-sref">Section Grid Ref.:</label><input type="text" value="" class="required" name="location:centroid_sref" id="section-location-sref"><span class="deh-required">*</span>';
      // for the system we need to translate the system: easiest way is to have a disabled select plus a hidden field.
      $systems = array();
      $list = explode(',', str_replace(' ', '', $args['spatial_systems']));
      foreach($list as $system) {
          $systems[$system] = lang::get($system);
      }
      $options = array(
          'fieldname' => '',
          'systems' => $systems,
          'disabled' => ' disabled="disabled"',
          'id' => 'section-location-system-select');
      // Output the hidden system control
      $r .= '<input type="hidden" id="section-location-system" name="location:centroid_sref_system" value="" />';
      if(count($list)>1)
          $r .= data_entry_helper::sref_system_select($options);
          // force a blank centroid, so that the Warehouse will recalculate it from the boundary
          //$r .= "<input type=\"hidden\" name=\"location:centroid_geom\" value=\"\" />\n";

          $blockOptions = array();
          if (isset($args['custom_attribute_options']) && $args['custom_attribute_options']) {
              $blockOptionList = explode("\n", $args['custom_attribute_options']);
              foreach($blockOptionList as $opt) {
                  $tokens = explode('|', $opt);
                  $optvalue = explode('=', $tokens[1]);
                  // validation is a special case: the option is expected to be an array of validation rules
                  if($optvalue[0] === 'validation') $optvalue[1] = explode(',', $optvalue[1]);
                  $blockOptions[$tokens[0]][$optvalue[0]] = $optvalue[1];
              }
          }

          $r .= get_attribute_html($settings['section_attributes'], $args, array('extraParams'=>$auth['read'], 'disabled' => $settings['canEditBody'] ? '' : ' disabled="disabled" '), null, $blockOptions);
          if ($settings['canEditBody']) {
              if (lang::get('LANG_DATA_PERMISSION') !== 'LANG_DATA_PERMISSION') {
                  $r .= '<p>' . lang::get('LANG_DATA_PERMISSION') . '</p>';
              }
              $r .= '<input type="submit" value="'.lang::get('Save').'" class="form-button right" id="submit-section" />';
          }
          $r .= '</fieldset></form>';
          $r .= '</div>';
          return $r;
  }

  protected static function get_your_route_tab($auth, $args, $settings) {
  	$r = '<div id="your-route" class="ui-helper-clearfix">';
  	$olOptions = iform_map_get_ol_options($args);
  	$options = iform_map_get_map_options($args, $auth['read']);
  	$options['divId'] = 'route-map';
//  	$options['toolbarDiv'] = 'top';
  	// $options['tabDiv']='your-route';
  	$options['gridRefHint']=true;
    if ($settings['canEditBody']){

  		// also let the user click on a feature to select it. The highlighter just makes it easier to select one.
  		// these controls are not present in read-only mode: all you can do is look at the map.
  		$options['standardControls'][] = 'selectFeature';
  		$options['standardControls'][] = 'hoverFeatureHighlight';
  		$options['standardControls'][] = 'drawLine';
  		$options['standardControls'][] = 'modifyFeature';
  		$options['switchOffSrefRetrigger'] = true;
  		$help = lang::get('Select a section from the list then click on the map to draw the route and double click to finish. '.
  				'You can also select a section using the &quot;Query&quot; tool to click on the section lines. If you make a mistake in the middle '.
  				'of drawing a route, then you can use the &quot;Erase Route&quot; button to remove the last point drawn. After a route has been '.
  				'completed use the &quot;Modify feature&quot; tool to correct the line shape (either by dragging one of the circles along the '.
  				'line to form the correct shape, or by placing the mouse over a circle and pressing the &quotDelete&quot button on your keyboard '.
  				'to remove that point). Alternatively you could just redraw the line - this new line will then replace the old one '.
  				'completely. If you are not in the middle of drawing a line, the &quot;Erase Route&quot; button will erase the whole route for the '.
  				'currently selected section.').
                '<br />'.
                lang::get('To increase the number of sections, either return to the &quot;Site Details&quot; tab, and increase the value in the '.
                          '&quot;No. of sections&quot; field there (which will add new sections to the end of the list), or use the '.
                          '&quot;Insert Section&quot; button to add a new section immediately before or after the currently selected section.') . ' ' .
                (count($settings['sections']) > 1 ?
                    lang::get('The &quot;Remove Section&quot; button will remove the section completely, reducing the number of sections by one.')
                    : '') .
                '<br />'.lang::get('Once all route sections are drawn, select the &quot;Section Details&quot; tab (or use the &quot;Complete section details&quot; button) to complete the route setup.');
  		$r .= '<p class="ui-state-highlight page-notice ui-corner-all">'.$help.'</p>'.
  		  		parent::section_selector($settings, 'section-select-route') . '<br/>' .
  		  		'<input type="button" value="'.lang::get('Save Route').'" class="save-route form-button" >' .
  		  		' <input type="button" value="'.lang::get('Erase Route').'" class="erase-route form-button" title="'.lang::get('If the Draw Line control is active, this will erase each drawn point one at a time. If not active, then this will erase the whole highlighted route. This keeps the Section, allowing you to redraw the route for it.').'">' .
  		  		' <input type="button" value="'.lang::get('Insert Section').'" class="insert-section form-button" title="'.lang::get('This inserts an extra section after the currently selected section. All subsequent sections are renumbered, increasing by one. All associated occurrences are kept with the moved sections. This can be used to facilitate the splitting of this section.').'">' .
  		  		'<input type="button" value="'.lang::get('Remove Section').'" class="remove-section form-button" title="'.lang::get('Completely remove the highlighted section. The total number of sections will be reduced by one. The form will be reloaded after the section is deleted.').'">' .
  		  		' <input type="button" value="'.lang::get('Complete section details').'" class="complete-route-details form-button" title="'.lang::get('Jump to the Route Details Tab. The route must have been saved first.').'">' .
  		  		'';
  	}
  	$options['clickForSpatialRef'] = false;
  	// override the opacity so the parent square does not appear filled in.
  	$options['fillOpacity'] = 0;
  	// override the map height and buffer size, which are specific to this map.
  	$options['height'] = $args['route_map_height'];
  	$options['maxZoomBuffer'] = $args['route_map_buffer'];
  	$options['gridRefHintInFooter']=false;

  	$r .= map_helper::map_panel($options, $olOptions);
  	$r .= '<button class="indicia-button right" type="button" title="'.
  		lang::get('Returns to My Sites page. Remember to save any changes to the Site details, Route and/or Section details first.').
  		'" onclick="window.location.href=\'' . hostsite_get_url($args['sites_list_path']) . '\'">'.lang::get('Finish').'</button>';
  	$r .= '</div>';
  	return $r;
  }

  /**
   * Construct a submission for the location.
   * @param array $values Associative array of form data values.
   * @param array $args iform parameters.
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    $code = explode(':', trim($values['location:code']));
    $config = \Drupal::config('iform.settings');
    if (count($code) === 3 && $code[0] === "EBMS" && $code[2] === "[INDEX]") {
      // function overloading : can't use $nid. Ignore possibility of override
      $connection = array(
          'base_url'=>hostsite_get_config_value('iform', 'base_url', ''),
          'website_id'=>hostsite_get_config_value('iform', 'website_id', ''),
          'password'=>hostsite_get_config_value('iform', 'password', ''));
      $readAuth = data_entry_helper::get_read_auth($connection['website_id'], $connection['password']);
      // At the moment everyone is using EBMS_Transects...
      $locations = data_entry_helper::get_population_data(array(
          'table' => 'location',
          'extraParams' => $readAuth + array('view'=>'detail', 'columns'=>'code', 'location_type_id' => $values['location:location_type_id']),
          'caching' => FALSE,
      ));
      $newCode = array($code[0], $code[1], 1);
      foreach($locations as $country) {
        $parts = explode(':', trim($country['code']));
        if(count($parts) === 3 && $parts[0] === $newCode[0] && $parts[1] === $newCode[1] && is_numeric($parts[2]) &&
            (int)$parts[2] >= $newCode[2]) {
          $newCode[2] = (int)$parts[2] + 1;
        }
      }
      $values['location:code'] = implode(':',$newCode);
    }
    
    $submission = parent::get_submission($values, $args);
    
    // Build the sections
    // Create a list of section Codes needed for this value of number of sections.
    $read = array(
        'nonce' => $values['read_nonce'],
        'auth_token' => $values['read_auth_token']
    );
    $num_sections = 0;
    $transectLocationType = helper_base::get_termlist_terms(array('read'=>$read), 'indicia:location_types', array('Transect'));
    $transectAttributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'location_attribute_value',
        'attrtable'=>'location_attribute',
        'key'=>'location_id',
        'fieldprefix'=>'locAttr',
        'extraParams'=>$read,
        'survey_id'=>$args['survey_id'],
        'location_type_id'=>$transectLocationType[0]['id'],
        'multiValue'=>false // ensures that array_keys are the list of attribute IDs.
    ));
    foreach($transectAttributes as $transectAttribute) {
        if ($transectAttribute['caption']==='No. of sections') {
            foreach($values as $key => $value) {
                if ($key === $transectAttribute['fieldname'] ||
                        substr_compare($key, $transectAttribute['fieldname'].':' , 0, strlen($transectAttribute['fieldname'])+1) === 0) {
                    $num_sections = $value;
                }
            }
        }
    }
    if($num_sections>0) {
        $sectionList = array_map(function($i) { return 'S'.$i; }, range(1, $num_sections));

        // If this is an existing site, Fetch all the existing sections, and remove those codes from the array
        if (!empty($values['location:id'])) {
            $sections = data_entry_helper::get_population_data(array(
                'table' => 'location',
                'extraParams' => $read + array('view'=>'detail','parent_id'=>$values['location:id'],'deleted'=>'f'),
                'nocache' => true // may have recently added or removed a section
            ));
            $sectionList = array_filter($sectionList, function($i) use ($sections) {
                foreach($sections as $section) {
                    if($i === $section['code'])
                        return false;
                }
                return true;
            });
        }
        // Loop through all currently in the array, and create a section submodel array.
        $newSubSections = array();
        $sectionLocationType = helper_base::get_termlist_terms(array('read'=>$read), 'indicia:location_types', array('Transect Section'));
        foreach($sectionList as $section){
            $newSubSections[] = array('fkId' => 'parent_id',
                'model' => array('id' => 'location',
                    'fields' => array('name' => array('value' => $values['location:name'] . ' - ' . $section),
                        'code' => array('value' => $section),
                        'location_type_id' => array('value' => $sectionLocationType[0]['id'])),
                    'joinsTo' => array('website' => array($values['website_id']))),
                'copyFields' => array('centroid_sref'=>'centroid_sref','centroid_sref_system'=>'centroid_sref_system'));
        }
        // Bolt into the main model array.
        if(count($newSubSections)>0) {
            $submission['subModels'] = array_merge(!empty($submission['subModels']) ? $submission['subModels'] : array(),
                $newSubSections);
        }
    } else throw(1);
    return $submission;
  }

  /**
   * Ajax handler to insert a new section.
   * We assume that the form will reload itself after calling this.
   */
  public static function ajax_insertSection($website_id, $password, $nid) {
      $params = hostsite_get_node_field_value($nid, 'params');
      $conn = iform_get_connection_details($nid);
      iform_load_helpers(array('data_entry_helper'));
      data_entry_helper::$base_url = $conn['base_url'];
      $auth = data_entry_helper::get_read_write_auth($website_id, $password);

      $sectionLocationType = helper_base::get_termlist_terms($auth,
          'indicia:location_types',
          array(empty($params['section_type_term']) ? 'Section' : $params['section_type_term']));
      
      $parent = $_GET['parent_id'];
      $parentLocationTypeId = $_GET['parent_location_type_id'];
      $insertPoint = intval(substr($_GET['section'], 1)); // section comes in like "S1"
      if($parent === '') {
          header('Content-type: application/json');
          echo json_encode(array('error' => 'ID of main transect record not provided'));
      }
      if($parentLocationTypeId === '') {
          header('Content-type: application/json');
          echo json_encode(array('error' => 'Location type of main transect record not provided'));
      }
      if($insertPoint === '') {
          header('Content-type: application/json');
          echo json_encode(array('error' => 'Section code insert point not provided'));
      }

      $transect = data_entry_helper::get_population_data(array(
          'table' => 'location',
          'cachetimeout' => 0, // can't cache
          'extraParams' => $auth['read'] + array('view'=>'detail', 'id'=>$parent),
      ));
      if(!count($transect)) {
          header('Content-type: application/json');
          echo json_encode(array('error' => 'Main transect record does not exist'));
      }

      $transectAttributes = data_entry_helper::getAttributes(array(
          'valuetable'=>'location_attribute_value'
          ,'attrtable'=>'location_attribute'
          ,'key'=>'location_id'
          ,'fieldprefix'=>'locAttr'
          ,'id'=>$parent
          ,'location_type_id'=>$parentLocationTypeId
          ,'extraParams'=>$auth['read']
          ,'survey_id'=>$params['survey_id'] // see comment about attributes vs surveys
      ));
      // Note that the subsections may not have been saved, so may not exist.
      $sections = data_entry_helper::get_population_data(array(
          'table' => 'location',
          'cachetimeout' => 0, // can't cache
          'extraParams' => $auth['read'] + array('view'=>'detail', 'parent_id'=>$parent),
      ));


      // subsamples are attached to the location and parent, but the location_name is not filled in, so don't need to change that
      // Don't need to recalculate transect length: new section does not have route yet, so nothing to add.
      // loop through all the subsections with a greater section number
      foreach($sections as $section) {
          $sectionCode = intval(substr($section['code'], 1)); // code comes in like "S1"
        if($sectionCode > $insertPoint || ($sectionCode == $insertPoint && array_key_exists('before', $_GET))) {
            // Update the code and the name for the sections.
            $sectionPostData = ['location:id' => $section['id'],
                                'location:code' => 'S'.($sectionCode+1),
                                'location:name' => $transect[0]['name'] . ' - S' . ($sectionCode+1),
                                'website_id' => $website_id,
            ];
            $model = data_entry_helper::wrap($sectionPostData, 'location');
            $response = data_entry_helper::forward_post_to('save', $model, $auth['write_tokens']+['persist_auth' => 'true']);
            // TODO check response
        }
      }
      
      // insert the new section into gap,
      $sectionCode = array_key_exists('before', $_GET) ? $insertPoint : $insertPoint + 1;
      $sectionPostData = ['location:code' => 'S'.($sectionCode),
          'location:name' => $transect[0]['name'] . ' - S' . ($sectionCode),
          'location:parent_id' => $parent,
          'location:location_type_id' => $sectionLocationType[0]['id'],
          'location:centroid_sref' => $transect[0]['centroid_sref'],
          'location:centroid_sref_system' => $transect[0]['centroid_sref_system'],
          'locations_website:website_id' => $website_id,
          'website_id' => $website_id,
      ];
      $model = data_entry_helper::wrap($sectionPostData, 'location');
      $model['joinsTo'] = array('website' => array($website_id));
      
      $response = data_entry_helper::forward_post_to('save', $model, $auth['write_tokens']+['persist_auth' => 'true']);
      // TODO check response
      
      $transectPostData = ['location:id' => $parent,
          'website_id' => $website_id];
      // Add one to the section count on the transect
      foreach($transectAttributes as $transectAttribute) {
          if ($transectAttribute['caption']==='No. of sections') {
              // this should always have a value.
              $transectPostData[$transectAttribute['fieldname']] = $transectAttribute['displayValue'] + 1;
          }
      }
      $model = data_entry_helper::wrap($transectPostData, 'location');
      $response = data_entry_helper::forward_post_to('save', $model, $auth['write_tokens']);
      // TODO check response
      header('Content-type: application/json');
      echo json_encode(array('response' => $response));
  }

  /**
   * Ajax handler to delete a section.
   * We assume that the form will reload itself after calling this.
   */
  public static function ajax_deleteSection($website_id, $password, $nid) {
      $params = hostsite_get_node_field_value($nid, 'params');
      $conn = iform_get_connection_details($nid);
      iform_load_helpers(array('data_entry_helper'));
      data_entry_helper::$base_url = $conn['base_url'];
      $auth = data_entry_helper::get_read_write_auth($website_id, $password);

      $autocalcTransectLengthAttrId = empty($params['autocalc_transect_length_attr_id']) ? 0 : $params['autocalc_transect_length_attr_id'];
      $autocalcSectionLengthAttrId = empty($params['autocalc_section_length_attr_id']) ? 0 : $params['autocalc_section_length_attr_id'];

      $parent = $_GET['parent_id'];
      $parentLocationTypeId = $_GET['parent_location_type_id'];
      $deletePoint = intval(substr($_GET['section'], 1)); // section comes in like "S1"

      if($parent === '') {
          header('Content-type: application/json');
          echo json_encode(array('error' => 'ID of main transect record not provided'));
      }
      if($parentLocationTypeId === '') {
          header('Content-type: application/json');
          echo json_encode(array('error' => 'Location type of main transect record not provided'));
      }
      if($deletePoint === '') {
          header('Content-type: application/json');
          echo json_encode(array('error' => 'Section code delete point not provided'));
      }

      $transect = data_entry_helper::get_population_data(array(
          'table' => 'location',
          'cachetimeout' => 0, // can't cache
          'extraParams' => $auth['read'] + array('view'=>'detail', 'columns'=>'id,name', 'id'=>$parent),
      ));
      if(!count($transect)) {
          header('Content-type: application/json');
          echo json_encode(array('error' => 'Main transect record does not exist'));
      }

      $transectAttributes = data_entry_helper::getAttributes(array(
          'valuetable'=>'location_attribute_value'
          ,'attrtable'=>'location_attribute'
          ,'key'=>'location_id'
          ,'fieldprefix'=>'locAttr'
          ,'id'=>$parent
          ,'location_type_id'=>$parentLocationTypeId
          ,'extraParams'=>$auth['read']
          ,'survey_id'=>$params['survey_id'] // see comment about attributes vs surveys
      ));
      // Note that the subsections may not have been saved, so may not exist.
      $sections = data_entry_helper::get_population_data(array(
          'table' => 'location',
          'cachetimeout' => 0, // can't cache
          'extraParams' => $auth['read'] + array('view'=>'detail', 'columns'=>'id,code,name', 'parent_id'=>$parent),
      ));
      $sectionIdList = [];

      // subsamples are attached to the location and parent, but the location_name is not filled in, so don't need to change that
      // loop through all the subsections with a greater section number
      foreach($sections as $section) {
          $sectionCode = intval(substr($section['code'], 1)); // code comes in like "S1"
          if($sectionCode == $deletePoint) { // The deletion point
              // if the section has not been stored, it won't have a section record, and won't have any subsamples.
              // delete subsamples first (otherwise they are orphaned), then the section itself
              $samples = data_entry_helper::get_population_data(array(
                  'table' => 'sample',
                  'cachetimeout' => 0, // can't cache
                  'extraParams' => $auth['read'] + array('view'=>'detail', 'columns'=>'id', 'location_id'=>$section['id']),
              ));
              foreach($samples as $sample) {
                  $samplePostData = ['sample:id' => $sample['id'],
                      'sample:deleted' => 't',
                      'website_id' => $website_id,];
                  $model = data_entry_helper::wrap($samplePostData, 'sample');
                  $response = data_entry_helper::forward_post_to('save', $model, $auth['write_tokens']+['persist_auth' => 'true']);
              }
              $sectionPostData = ['location:id' => $section['id'],
                  'location:deleted' => 't',
                  'website_id' => $website_id,];
              $model = data_entry_helper::wrap($sectionPostData, 'location');
              $response = data_entry_helper::forward_post_to('save', $model, $auth['write_tokens']+['persist_auth' => 'true']);
              // TODO check response
          } else {
              $sectionIdList[] = $section['id'];
              if($sectionCode > $deletePoint) { // AFTER the delete point
                  // Update the code and the name for the sections.
                  $sectionPostData = ['location:id' => $section['id'],
                      'location:code' => 'S'.($sectionCode-1),
                      'location:name' => $transect[0]['name'] . ' - S' . ($sectionCode-1),
                      'website_id' => $website_id,];
                  $model = data_entry_helper::wrap($sectionPostData, 'location');
                  $response = data_entry_helper::forward_post_to('save', $model, $auth['write_tokens']+['persist_auth' => 'true']);
                  // TODO check response
              }
          }
      }
      $transectPostData = ['location:id' => $parent,
          'website_id' => $website_id,];
      // Subtract one to the section count on the transect
      foreach($transectAttributes as $transectAttribute) {
          if ($transectAttribute['caption']==='No. of sections') {
              // this should always have a value.
              $transectPostData[$transectAttribute['fieldname']] = $transectAttribute['displayValue'] - 1;
          }
      }
      // update the location_attribute value for the transect length
      if($autocalcTransectLengthAttrId && $autocalcSectionLengthAttrId) {
          $transectLen = 0;
          if(count($sectionIdList)) {
              $sectionLengthAttrs = data_entry_helper::get_population_data(array(
                  'table' => 'location_attribute_value',
                  'cachetimeout' => 0, // can't cache
                  'extraParams' =>$auth['read'] + array('view' => 'list',
                      'location_id' => $sectionIdList,
                      'location_attribute_id' => $autocalcSectionLengthAttrId),
              ));
              foreach($sectionLengthAttrs as $sectionLengthAttr) {
                  $transectLen += $sectionLengthAttr['value'];
              }
          }
          foreach($transectAttributes as $transectAttribute) {
              if ($transectAttribute['attributeId']==$autocalcTransectLengthAttrId) {
                  if(count($transectAttribute['values']))
                      // this should always have a value.
                      $transectPostData[$transectAttribute['fieldname']] = $transectLen;
                  else
                      $transectPostData[$transectAttribute['fieldname']] = $transectLen;
              }
          }
      }
      $model = data_entry_helper::wrap($transectPostData, 'location');
      $response = data_entry_helper::forward_post_to('save', $model, $auth['write_tokens']+['persist_auth' => 'true']);
      // TODO check response
      header('Content-type: application/json');
      echo json_encode(array('response' => $response));
  }

  /**
   * Ajax handler to clear the geometry of a section section.
   * hands back the new transect length
   */
  public static function ajax_clearSectionGeom($website_id, $password, $nid) {
      $params = hostsite_get_node_field_value($nid, 'params');
      $conn = iform_get_connection_details($nid);
      iform_load_helpers(array('data_entry_helper'));
      data_entry_helper::$base_url = $conn['base_url'];
      $auth = data_entry_helper::get_read_write_auth($website_id, $password);

      $autocalcTransectLengthAttrId = empty($params['autocalc_transect_length_attr_id']) ? 0 : $params['autocalc_transect_length_attr_id'];
      $autocalcSectionLengthAttrId = empty($params['autocalc_section_length_attr_id']) ? 0 : $params['autocalc_section_length_attr_id'];

      $parent = $_GET['parent_id'];
      $parentLocationTypeId = $_GET['parent_location_type_id'];
      $sectionId = $_GET['sectionId'];
      $geom = $_GET['geom']; // new centroid point

      if($parent === '') {
          header('Content-type: application/json');
          echo json_encode(array('error' => 'ID of main transect record not provided'));
      }
      if($parentLocationTypeId === '') {
          header('Content-type: application/json');
          echo json_encode(array('error' => 'Location type of main transect record not provided'));
      }
      if($sectionId === '') {
          header('Content-type: application/json');
          echo json_encode(array('error' => 'Section id not provided'));
      }
      if($geom === '') {
          header('Content-type: application/json');
          echo json_encode(array('error' => 'Replacement centroid geometry not provided'));
      }

      $transectAttributes = data_entry_helper::getAttributes(array(
          'valuetable'=>'location_attribute_value'
          ,'attrtable'=>'location_attribute'
          ,'key'=>'location_id'
          ,'fieldprefix'=>'locAttr'
          ,'id'=>$parent
          ,'location_type_id'=>$parentLocationTypeId
          ,'extraParams'=>$auth['read']
          ,'survey_id'=>$params['survey_id'] // see comment about attributes vs surveys
      ));
      // Note that the subsections may not have been saved, so may not exist.
      $sections = data_entry_helper::get_population_data(array(
          'table' => 'location',
          'cachetimeout' => 0, // can't cache
          'extraParams' => $auth['read'] + array('view'=>'detail', 'columns'=>'id,code,name', 'parent_id'=>$parent),
      ));
      $sectionIdList = [];

      // subsamples are attached to the location and parent, but the location_name is not filled in, so don't need to change that
      // loop through all the subsections with a greater section number
      foreach($sections as $section) {
          if($sectionId == $section['id']) { // The deletion point
              // if the section has not been stored, it won't have a section record.
              $sectionPostData = ['location:id' => $section['id'],
                  'location:boundary_geom' => '',
                  'location:centroid_geom' => $geom,
                  'website_id' => $website_id,
                  'persist_auth' => 'true',];
              $model = data_entry_helper::wrap($sectionPostData, 'location');
              $response = data_entry_helper::forward_post_to('save', $model, $auth['write_tokens']);
          } else {
              $sectionIdList[] = $section['id'];
          }
      }

      $transectPostData = ['location:id' => $parent,
          'website_id' => $website_id,
          'persist_auth' => 'true',];
      // Section count on the transect stays the same
      // update the location_attribute value for the transect length
      if($autocalcTransectLengthAttrId && $autocalcSectionLengthAttrId) {
          $transectLen = 0;
          if(count($sectionIdList)) {
              $sectionLengthAttrs = data_entry_helper::get_population_data(array(
                  'table' => 'location_attribute_value',
                  'cachetimeout' => 0, // can't cache
                  'extraParams' => $readAuth + array('view' => 'list',
                      'location_id' => $sectionIdList,
                      'location_attribute_id' => $autocalcSectionLengthAttrId),
              ));
              foreach($sectionLengthAttrs as $sectionLengthAttr) {
                  $transectLen += $sectionLengthAttr['value'];
              }
          }
          foreach($transectAttributes as $transectAttribute) {
              if ($transectAttribute['attributeId']==$autocalcTransectLengthAttrId) {
                  if(count($transectAttribute['values']))
                      // this should always have a value.
                      $transectPostData[$transectAttribute['values'][0]['fieldname']] = $transectLen;
                      else
                          $transectPostData[$transectAttribute['fieldname']] = $transectLen;
              }
          }
      }
      $model = data_entry_helper::wrap($transectPostData, 'location');
      $response = data_entry_helper::forward_post_to('save', $model, $auth['write_tokens']);
      // TODO check response
      header('Content-type: application/json');
      echo json_encode(array('response' => ["transaction_id" => $_GET['transaction_id'], "transectLen" => $transectLen]));
  }

  /**
   * Ajax handler to delete a site.
   * We assume that the form will reload itself after calling this.
   */
  public static function ajax_deleteSite($website_id, $password, $nid) {
      $params = hostsite_get_node_field_value($nid, 'params');
      $conn = iform_get_connection_details($nid);
      iform_load_helpers(array('data_entry_helper'));
      data_entry_helper::$base_url = $conn['base_url'];
      $auth = data_entry_helper::get_read_write_auth($website_id, $password);
      
      $parent = $_GET['id'];

      if($parent === '') {
          header('Content-type: application/json');
          echo json_encode(array('error' => 'ID of main transect record not provided'));
      }
      
      $transect = data_entry_helper::get_population_data(array(
          'table' => 'location',
          'cachetimeout' => 0, // can't cache
          'extraParams' => $auth['read'] + array('view'=>'detail', 'columns'=>'id,name', 'id'=>$parent),
      ));
      if(!count($transect)) {
          header('Content-type: application/json');
          echo json_encode(array('error' => 'Main transect record does not exist'));
      }
      
      // Note that the subsections may not have been saved, so may not exist.
      $sections = data_entry_helper::get_population_data(array(
          'table' => 'location',
          'cachetimeout' => 0, // can't cache
          'extraParams' => $auth['read'] + array('view'=>'detail', 'columns'=>'id,code,name', 'parent_id'=>$parent),
      ));
      $sectionIdList = [];
      
      // loop through all the subsections and delete all samples on the subsection, then delete the subsection itself
      foreach($sections as $section) {
          // delete subsamples first (otherwise they are orphaned), then the section itself
          $samples = data_entry_helper::get_population_data(array(
                  'table' => 'sample',
                  'cachetimeout' => 0, // can't cache
                  'extraParams' => $auth['read'] + array('view'=>'detail', 'columns'=>'id', 'location_id'=>$section['id']),
          ));
          foreach($samples as $sample) {
              $samplePostData = ['sample:id' => $sample['id'],
                      'sample:deleted' => 't',
                      'website_id' => $website_id,];
              $model = data_entry_helper::wrap($samplePostData, 'sample');
              $response = data_entry_helper::forward_post_to('save', $model, $auth['write_tokens']+['persist_auth' => 'true']);
          }
          $sectionPostData = ['location:id' => $section['id'],
                  'location:deleted' => 't',
                  'website_id' => $website_id,];
          $model = data_entry_helper::wrap($sectionPostData, 'location');
          $response = data_entry_helper::forward_post_to('save', $model, $auth['write_tokens']+['persist_auth' => 'true']);
              // TODO check response
      }
      $samples = data_entry_helper::get_population_data(array(
          'table' => 'sample',
          'cachetimeout' => 0, // can't cache
          'extraParams' => $auth['read'] + array('view'=>'detail', 'columns'=>'id', 'location_id'=>$parent),
      ));
      foreach($samples as $sample) {
          $samplePostData = ['sample:id' => $sample['id'],
              'sample:deleted' => 't',
              'website_id' => $website_id,];
          $model = data_entry_helper::wrap($samplePostData, 'sample');
          $response = data_entry_helper::forward_post_to('save', $model, $auth['write_tokens']+['persist_auth' => 'true']);
      }
      $transectPostData = ['location:id' => $parent,
          'location:deleted' => 't',
          'website_id' => $website_id,];
      $model = data_entry_helper::wrap($transectPostData, 'location');
      $response = data_entry_helper::forward_post_to('save', $model, $auth['write_tokens']+['persist_auth' => 'true']);
      // TODO check response
      header('Content-type: application/json');
      echo json_encode(array('response' => $response));
  }
  
  
  /**
   * After saving a new transect, reload the transect so that the user can continue to save the sections.
   */
  public static function get_redirect_on_success($values, $args) {
      if (!isset($values['location:id'])) {
          $current_path = \Drupal::service('path.current')->getPath();
          return \Drupal::service('path.alias_manager')->getAliasByPath($current_path) .'?route-tab=true#your-route';
      }
  }

}
