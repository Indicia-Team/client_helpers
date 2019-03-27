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
 * @package Client
 * @subpackage PrebuiltForms
 * Form for adding or editing the site details on a transect which contains a number of sections.
 */
class iform_ebms_transects_allocation {

  /**
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_ebms_transects_allocation_definition() {
    return array(
      'title'=>'EBMS Location allocator',
      'category' => 'UKBMS Specific forms',
      'description'=>'Form assigning locations to normal users or region/country controllers.'
    );
  }

  // TODO add allocation be person attribute functionality
  //      need to check if ajaxProxy can handle people attributes.

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    $retVal = array_merge(
      array(
        array(
          'name' => 'allocation_config_list',
          'caption' => 'Allocation Configuration List',
          'description' => 'Definitions of how people are assigned to locations.',
          'type' => 'jsonwidget',
          'schema' => '{
"type":"seq",
"title":"Allocation Configuration List",
"desc":"A list of definitions for the allocation type, seen by the user as a select control.",
"sequence":
[ { "type":"map",
      "title":"Allocation Definition",
      "desc":"A definition of the relationship between users and locations, and how they are assigned to each other.",
      "mapping": {
        "permission":       {"type":"str",  "title": "Permission", "desc":"The hostsite permission name required to be able to select this option. If omitted, defaults to available."},
        "website_id":       {"type":"str",  "title": "Website ID", "desc":"Warehouse Website ID, when overriding the default form configuration."},
        "website_password": {"type":"str",  "title": "Password",   "desc":"Warehouse Website password, when overriding the default form configuration."},
        "label":            {"type":"str",  "title": "Label",      "desc":"Label used for this option in the type dropdown box."},
        "indicia_location": {"type":"bool", "title": "Indicia User ID", "desc":"Is the assignment stored as the Indicia User ID in an attribute against the location?"},
        "cms_location":     {"type":"bool", "title": "CMS User ID", "desc":"Is the assignment stored as the CMS User ID in an attribute against the location?"},
        "indicia_person":   {"type":"bool", "title": "Location ID", "desc":"Is the assignment stored as the Indicia Location ID in an attribute against the person?"},
        "attr_id":          {"type":"str",  "title": "Attribute ID", "desc":"The attribute id (location or person as appropriate) in which the assignment is stored."},
        "multiple_people":  {"type":"bool", "title": "Multiple People", "desc":"Can each location be assigned to more than one person?"},
        "location_types" :  {"type":"seq",  "title": "Location Types", "desc":"List of the location types included in the grid when viewing this option. If omitted, no filter applied.",
          "sequence": [ {"type":"str", "title": "Location Type", "desc":"Location Type Term: this will be translated to the appropriate ID."} ] },
        "edit_link_path":   {"type":"str",  "title": "Edit Path", "desc":"Path to page used for editing Locations. If omitted, link not provided." },
        "regional_control_location_type" : {"type":"str", "title": "Regional Control Location Type", "desc":"Location Type Term used to provide a regional filter control. If omitted then no regional control will be displayed. This will be translated to the appropriate ID."}
      }
    }
]
}',
              'required' => true,
              'group'=>'Report Settings'
          ),
          array(
              'name'=>'county_location_attr_id',
              'caption'=>'County Location Attrbute Id',
              'description'=>'Indicia ID for the location attribute that holds the names of counties for the square.',
              'type'=>'select',
              'table'=>'location_attribute',
              'valueField'=>'id',
              'captionField'=>'caption',
              'required' => true,
          ),
          
    ));
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
    global $user;

    // FutureDev: data drive the report name ebms_country_locations. May need to be replaced as a report - is there a library report I can use?
    // FutureDev? Add map and allow filtering by displayed area.

    $auth = array();
    $current = self::_identify_current_type($args, $auth); // config object

    data_entry_helper::add_resource('jquery_ui');
    $settings = array(
      'grid_id' => 'site-allocation-grid-'.$nid,
      'region_select_id' => 'region-control-'.$nid,
      'site_select_id' => 'location-control-'.$nid,
      'user_select_id' => 'user-control-'.$nid,
      'allocation_select_id' => 'allocation-control-'.$nid,
      'search_id' => 'search-button-'.$nid,
      'select_all_class' => 'select-all-button',
      'deselect_all_class' => 'deselect-all-button',
      'download_class' => 'download-button',
      'auth' => $auth,
      'base_url' => data_entry_helper::getProxiedBaseUrl(),
      'config' => $current,
      'alt_row_class' => 'odd',
//      'ajax_location_post_URL' => url('iform/ajax/ebms_transects_allocation') . '/saveLocationAttribute/' . $nid,
//      Drupal 8
      'ajax_location_post_URL' => hostsite_get_url('iform/ajax/ebms_transects_allocation' . '/saveLocationAttribute/' . $nid),
//        'ajax_person_post_URL' => iform_ajaxproxy_url($nid, 'person_attribute_value'),
      'website_id' => $args['website_id'],
      'select_all_button' => lang::get('Select All'),
      'deselect_all_button' => lang::get('Deselect All'),
      'download_button' => lang::get('Download Allocation Report'),
      'county_location_attr_id' => $args['county_location_attr_id'],
    );

    $r = self::_get_control_bar($auth, $args, $nid, $settings) .
      self::_grid($auth, $args, $nid, $settings);

    data_entry_helper::$javascript .= 'eta_prep('.json_encode($settings).");\n";

    return $r;
  }

  private static function _identify_current_type(&$args, &$auth) {
    $config = json_decode($args['allocation_config_list']);
    $selected = false;
    foreach($config as $idx=>$config_entry) {
      if((empty($config_entry->permission) || hostsite_user_has_permission($config_entry->permission)) &&
          ($selected === false || (!empty($_GET['type']) && $_GET['type'] == $idx)))
        $selected = $idx;
    }
    if($selected === false)
      throw new exception("You do not have sufficient privileges to assign any locations. You can't use this form.");

    if((!empty($config[$selected]->website_id) && empty($config[$selected]->website_password)) ||
       (empty($config[$selected]->website_id) && !empty($config[$selected]->website_password)))
      throw new exception("Form configuration error: both website ID and website password must be provided when overriding: allocation index ".$selected);
    if(!empty($config[$selected]->website_id) && !empty($config[$selected]->website_password)) {
      $args['website_id'] = $config[$selected]->website_id;
      $args['password'] = $config[$selected]->website_password;
    }

    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $config[$selected]->index = $selected;
    if(!empty($config[$selected]->regional_control_location_type)) {
      $terms = helper_base::get_termlist_terms($auth, 'indicia:location_types', array($config[$selected]->regional_control_location_type));
      if(count($terms) == 0)
        throw new exception("Form configuration error: could not find region location type ".$config[$selected]->regional_control_location_type." for allocation index ".$selected);
      $config[$selected]->regional_control_location_type_id = $terms[0]['id'];
    }
    if(empty($config[$selected]->location_types) || count($config[$selected]->location_types) == 0)
      throw new exception("Form configuration error: missing location_types for allocation index ".$selected);
    $terms = helper_base::get_termlist_terms($auth, 'indicia:location_types', $config[$selected]->location_types);
    if(count($terms) == 0)
      throw new exception("Form configuration error: could not find location types ".print_r($config[$selected]->location_types, true)." for allocation index ".$selected);
    $config[$selected]->location_type_ids = array();
    foreach($terms as $term)
      $config[$selected]->location_type_ids[] = $term['id'];
    if(empty($config[$selected]->attr_id) || count($config[$selected]->location_types) == 0)
      throw new exception("Form configuration error: missing attr_id for allocation index ".$selected);

    return $config[$selected];
  }

  private static function _get_control_bar($auth, $args, $nid, $settings) {
  return '<table id="controls-table" class="ui-widget ui-widget-content ui-corner-all controls-table">' .
        '<thead class="ui-widget-header">' .
        self::_allocation_type_control($auth, $args, $nid, $settings).
        '<tr>' .
        self::_region_control($auth, $args, $nid, $settings).
        self::_location_control($auth, $args, $nid, $settings).
        self::_user_control($auth, $args, $nid, $settings).
        self::_search_button($auth, $args, $nid, $settings).
        '</tr></thead></table>';
  }

  private static function _allocation_type_control($auth, $args, $nid, $settings) {
    $config = json_decode($args['allocation_config_list']);
    $lookup_values = array();
    foreach($config as $idx=>$config_entry) {
      if(empty($config_entry->permission) || hostsite_user_has_permission($config_entry->permission))
        $lookup_values[$idx] = empty($config_entry->label) ? $idx : $config_entry->label;
    }
    if(count($lookup_values) > 1)
      return '<tr><th colspan='.(empty($settings['config']->regional_control_location_type) ? '3' : '4').'>' .
          data_entry_helper::select(array(
              'label'=>lang::get('Allocation Type'),
              'fieldname'=>$settings['allocation_select_id'],
              'lookupValues' => $lookup_values,
              'default' => $settings['config']->index
          )) .
          '</th></tr>';
    else {
      $lookup_values = array_keys($lookup_values);
      return '<tr style="display:none;"><th>' .
          '<input type="hidden" id="'.$settings['allocation_select_id'].'" value="'.$lookup_values[0].'" />' .
          '</th></tr>';
    }
  }

  private static function _region_control($auth, $args, $nid, $settings) {
    if(empty($settings['config']->regional_control_location_type)) return '';
    return '<th>' .
        data_entry_helper::select(array(
          'fieldname'=>$settings['region_select_id'],
          'label'=>lang::get($settings['config']->regional_control_location_type),
          'table'=>'location',
          'valueField'=>'id',
          'captionField'=>'name',
          'blankText'=>lang::get('<Please select '.($settings['config']->regional_control_location_type).'>'),
          'extraParams'=>$auth['read'] +
                array('view'=>'detail',
                    'location_type_id'=>$settings['config']->regional_control_location_type_id,
                    'orderby'=>'name')
             )) .
        '</th>';
  }

  private static function _location_control($auth, $args, $nid, $settings) {
    if(empty($settings['config']->regional_control_location_type))
      return '<th>' .
        data_entry_helper::select(array(
          'fieldname'=>$settings['site_select_id'],
          'label' => lang::get('Site'),
          'table'=>'location',
          'valueField'=>'id',
          'captionField'=>'name',
          'blankText'=>lang::get('<Please select Site>'),
          'extraParams'=>$auth['read'] +
                array('view'=>'detail',
                    'location_type_id'=>$settings['config']->location_type_ids,
                    'orderby'=>'name'),
          'caching'=>false
             )) .
        '</th>';
    else
      return '<th>' .
        data_entry_helper::select(array(
            'fieldname' => $settings['site_select_id'],
            'label' => lang::get('Site'),
            'report' => 'reports_for_prebuilt_forms/UKBMS/ebms_region_locations',
            'valueField' => 'location_id',
            'captionField' => 'name',
            'parentControlId' => 'region-control-'.$nid,
            'parentControlLabel' => lang::get($settings['config']->regional_control_location_type),
            'filterField' => 'region_location_id',
            'blankText'=>lang::get('<Please select Site>'),
            'extraParams' => array_merge($auth['read'],
                array('location_type_ids' => implode(',', $settings['config']->location_type_ids),
                      'region_type_id'=>$settings['config']->regional_control_location_type_id
                )),
            'caching' => false
        )) .
        '</th>';
  }

  private static function _user_control($auth, $args, $nid, &$settings) {
    // user is assumed to be a manager, and has access to the full list of users. Do not cache.
    // Want users available as soon as they are created.
    $ctrl = '';
    $user_list=array();
    $user_list_arr = array();

    if((empty($settings['config']->indicia_location) || !$settings['config']->indicia_location) &&
        (empty($settings['config']->cms_location) || !$settings['config']->cms_location))
      throw new exception('Form configuration error: A attribute assignment methodology must be defined for allocation index '.$settings['config']->index);

    if(!empty($settings['config']->indicia_location) && $settings['config']->indicia_location)
      $settings['config']->user_prefix = lang::get("Indicia User ");
    else
      $settings['config']->user_prefix = lang::get("CMS User ");

    // look up all users, not just those that have entered data.
    if(version_compare(VERSION, '8', '<')) {
      $results = db_query('SELECT uid, name FROM {users}'); // assume drupal7
    } else {
      $results = db_query('SELECT uid, name FROM {users_field_data}'); // drupal8
    }
    foreach ($results as $result) {
      if($result->uid){ // ignore unauthorised user, uid zero
        // FutureDev: confirm they have data entry permission
        // FutureDev: use first/surname rather than account name
        if(!empty($settings['config']->indicia_location) && $settings['config']->indicia_location) {
          // for Indicia id allocation, can't add users if they don't have a indicia user id (yet).
          $indicia_user_id = hostsite_get_user_field('indicia_user_id', 0, false, $result->uid);
          if($indicia_user_id>0)
            $user_list[$indicia_user_id] = array('name'=>$result->name.' ('.$indicia_user_id.')');
        } else
          $user_list[$result->uid] = array('name'=>$result->name.' ('.$result->uid.')');
      }
    }
    foreach($user_list as $id => $account)
      $user_list_arr[$id] = $account['name'];

    data_entry_helper::$javascript .= "indiciaData.full_user_list = ".json_encode(array_keys($user_list)).";\n";

    natcasesort($user_list_arr);

    return '<th>' .
      data_entry_helper::select(array(
        'fieldname' => $settings['user_select_id'],
        'label' => lang::get('User'),
        'lookupValues' => $user_list_arr,
        'blankText' => lang::get('<Please select user>')
      )) .
      '</th>';
  }

  private static function _search_button($auth, $args, $nid, $settings) {
    return '<th>' .
        '<input type="button" id="'.$settings['search_id'].'" class="ui-corner-all ui-widget-content" value="'.lang::get('search').'" />' .
        '</th>';
  }

  private static function _grid($auth, $args, $nid, $settings) {
    return '<table class="ui-widget ui-widget-content reallocate-grid" id="'.$settings['grid_id'].'">' .
        '<thead class="ui-widget-header">' .
          '<tr>' .
            '<td colspan=6>'.
              '<input type="button" disabled="disabled" class="ui-corner-all ui-widget-content '.$settings['select_all_class'].'" value="'.$settings['select_all_button'].'" />' .
              '<input type="button" disabled="disabled" class="ui-corner-all ui-widget-content '.$settings['deselect_all_class'].'" value="'.$settings['deselect_all_button'].'" />' .
            '</td>' .
          '</tr>' .
          '<tr>' .
            '<th>'.lang::get('Allocated?').'</th>' .
            (empty($settings['config']->regional_control_location_type) ? '' : '<th>'.lang::get($settings['config']->regional_control_location_type).'</th>') .
            '<th>'.lang::get('Site').'</th>' .
            '<th>'.lang::get('SRef').'</th>' .
            '<th>'.lang::get('User').'</th>' .
            '<th></th>' .
          '</tr>' .
        '</thead>' .
        '<tbody>' .
          '<tr>' .
            '<td colspan=6>'.lang::get('Use Search button to populate grid').'</td>' .
          '</tr>' .
        '</tbody>' .
        '<tfoot>' .
          '<tr>' .
            '<td colspan=4>'.
              '<input type="button" disabled="disabled" class="ui-corner-all ui-widget-content '.$settings['select_all_class'].'" value="'.$settings['select_all_button'].'" />' .
              '<input type="button" disabled="disabled" class="ui-corner-all ui-widget-content '.$settings['deselect_all_class'].'" value="'.$settings['deselect_all_button'].'" />' .
            '</td>' .
            '<td colspan=2>'.
              '<form action="'. hostsite_get_url('iform/ajax/ebms_transects_allocation' . '/downloadSiteAllocations/' . $nid) . '">' .
                '<input type="submit" class="ui-corner-all ui-widget-content '.$settings['download_class'].'" value="'.$settings['download_button'].'" />' .
                '<input type="hidden" name="location_type_ids" value="'.implode(',',$settings['config']->location_type_ids).'" />' .
                '<input type="hidden" name="attribute_id" value="'.$settings['config']->attr_id.'" />' .
                '<input type="hidden" name="region_location_type_id" value="'.$settings['config']->regional_control_location_type_id.'" />' .
                '</form>' .
            '</td>' .
          '</tr>' .
        '</tfoot>' .
      '</table>';
  }

  public static function ajax_saveLocationAttribute($website_id, $password, $nid) {
    $conn = iform_get_connection_details($nid);
    iform_load_helpers(array('data_entry_helper'));
    data_entry_helper::$base_url = $conn['base_url'];
    $auth = data_entry_helper::get_read_write_auth($website_id, $password);
    $writeTokens = $auth['write_tokens'];
    $Model = data_entry_helper::wrap($_POST, 'location_attribute_value');
    // pass through the user ID as this can then be used to set created_by and updated_by_ids
    //if (isset($_REQUEST['user_id'])) $writeTokens['user_id'] = $_REQUEST['user_id'];
    //if (isset($_REQUEST['sharing'])) $writeTokens['sharing'] = $_REQUEST['sharing'];
    $response = data_entry_helper::forward_post_to('save', $Model, $writeTokens);
    header('Content-type: application/json');
    echo json_encode(array('response' => $response));
  }

  public static function ajax_downloadSiteAllocations($website_id, $password, $nid) {
      // UKBMS Issue 80
      // Requirement: a report to output the square, allocated recorder (user name, proper name), email address, branch and county
      // Have to do this in 2 stages, as the allocation within UKBMS is via the CMS user ID, not the Indicia ID, so
      // the link to the user name etc is not held on the warehouse.
      $formParams = hostsite_get_node_field_value($nid, 'params');
      $conn = iform_get_connection_details($nid);
      iform_load_helpers(array('data_entry_helper', 'report_helper'));
      data_entry_helper::$base_url = $conn['base_url'];
      $readAuth = data_entry_helper::get_read_auth($website_id, $password);
      $params = array(
          'location_type_ids' => $_GET['location_type_ids'],
          'locattrs' => $_GET['attribute_id'].','.$formParams['county_location_attr_id'],
          'region_location_type_id' => $_GET['region_location_type_id'],
      );
      header('Content-type: text/csv; utf-8');
      header('Content-Disposition: attachment; filename="allocations.csv"');
      header("Pragma: no-cache");
      header("Expires: 0");
      // Output a byte order mark for proper CSV UTF-8.
      echo chr(239) . chr(187) . chr(191);
      $out = fopen('php://output', 'w');
      fputcsv ( $out, array('Square', 'Recorder username', 'Recorder name', 'Recorder email', 'Branch', 'County') );
      $r = report_helper::get_report_data(array(
          'dataSource' => "projects/ukbms/locations_allocations_list",
          'readAuth' => $readAuth,
          'extraParams' => $params,
          'caching' => FALSE,
      ));
      foreach($r as $row) {
          if(empty($row['attr_location_'.$_GET['attribute_id']])) {
              fputcsv ( $out, array($row['name'], "", "", "", $row['region_name'], $row['attr_location_term_'.$formParams['county_location_attr_id']]) );
          } else {
              $users = explode(',',$row['attr_location_'.$_GET['attribute_id']]);
              foreach($users as $thisUser) {
                  $thisUser = trim($thisUser);
                  try {
                      fputcsv ( $out,
                          array($row['name'],
                              hostsite_get_user_field('name', 0, false, $thisUser),
                              hostsite_get_user_field('field_first_name', '', false, $thisUser) . ' ' .
                                hostsite_get_user_field('field_last_name', '', false, $thisUser),
                              hostsite_get_user_field('mail', '', false, (int)$thisUser),
                              //hostsite_get_user_field('mail', '', false, $thisUser),
                              $row['region_name'],
                              $row['attr_location_term_'.$formParams['county_location_attr_id']]));
                  } catch(Exception $e) {
                      fputcsv ( $out, array($row['name'], "[CMS User $thisUser]", "", "", $row['region_name'], $row['attr_location_term_'.$formParams['county_location_attr_id']]) );
                  }
              }
          }
      }
      fclose($out);
      return;
  }
  
}
