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
      'description'=>'Form assigning locations to normal users or country controllers.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    $retVal = array_merge(
      array(
      	array(
          'name'=>'country_type_term',
          'caption'=>'Country type term',
          'description'=>'Select the term used for the country location type.',
          'type' => 'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:location_types'),
          'required' => true,
          'group'=>'Settings'
        ),
        array(
          'name'=>'site_type_term',
          'caption'=>'Site type term',
          'description'=>'Select the term used for the location type for the locations which are being allocated.',
          'type' => 'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'id',
          'extraParams' => array('termlist_external_key'=>'indicia:location_types'),
          'required' => true,
          'group'=>'Settings'
        ),
        array(
          'name'=>'assignment_attr_id',
          'caption'=>'Assignment Location attribute',
          'description'=>'Location attribute used to assign users to the location.',
          'type'=>'select',
          'table'=>'location_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Settings',
          'required'=>true
        ),
        array(
          'name'=>'manager_assignment_attr_id',
          'caption'=>'Manager Assignment Location attribute',
          'description'=>'Location attribute used to assign managers to the location. Optional',
          'type'=>'select',
          'table'=>'location_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Settings',
          'required'=>false
        ),
        array(
          'name' => 'editLinkPath',
          'caption' => 'Path to page used for editing Locations',
          'description' => 'The path to the page used for editing Locations. This is just the site relative path, e.g. http://www.example.com/index.php?q=enter-records needs '.
              'to be input as just enter-records. The path is called with the id of the location in a parameter called location_id.',
          'type' => 'text_input',
          'default' => ''
        )
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

    // TODO? data drive the report name ebms_country_locations.
    // TODO? allow extension to choose location type of lookup: initially for EBMS, only 1 site location type. May
    //       require multiple edit paths.
    // TODO? allow the choice (in form config) of whether to store CMS or Indicia ID in attribute. EBMS initially is CMS attribute
    // TODO? Add map and allow filtering by displayed area.

    if (!function_exists('iform_ajaxproxy_url'))
    	return 'The IForm AJAX Proxy module must be enabled for this form to work.';
    
  	data_entry_helper::add_resource('jquery_ui');
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $settings = array(
    	'gridID' => 'site-allocation-grid-'.$nid,
    	'countrySelectID' => 'country-control-'.$nid,
    	'siteSelectID' => 'location-control-'.$nid,
    	'userSelectID' => 'user-control-'.$nid,
    	'allocationSelectID' => 'allocation-control-'.$nid,
    	'searchID' => 'search-button-'.$nid,
    	'selectAllClass' => 'select-all-button',
    	'deselectAllClass' => 'deselect-all-button',
    	'auth' => $auth,
    	'base_url' => data_entry_helper::$base_url,
    	'site_location_type_id' => $args['site_type_term'],
    	'country_location_type_id' => $args['country_type_term'],
    	'assignment_attr_id' => $args['assignment_attr_id'],
      'manager_assignment_attr_id' => (isset($args['manager_assignment_attr_id']) && $args['manager_assignment_attr_id'] != "" ?
          $args['manager_assignment_attr_id'] : 0),
    	'altRowClass' => 'odd',
    	'ajaxFormPostUrl' => iform_ajaxproxy_url($nid, 'location_attribute_value'),
    	'website_id' => $args['website_id'],
    	'editLinkPath' => url($args['editLinkPath']),
    	'selectAllButton' => lang::get('Select All'),
    	'deselectAllButton' => lang::get('Deselect All')
    );
    
    $r = self::get_control_bar($auth, $args, $nid, $settings) .
    	self::grid($auth, $args, $nid, $settings);
    
    data_entry_helper::$javascript .= 'etaPrep('.json_encode($settings).");\n";

    return $r;
  }

  private static function get_control_bar($auth, $args, $nid, $settings) {
	return '<table id="controls-table" class="ui-widget ui-widget-content ui-corner-all controls-table">' .
				'<thead class="ui-widget-header"><tr>' .
				self::allocation_type_control($auth, $args, $nid, $settings).
				self::location_type_control($auth, $args, $nid, $settings).
				self::country_control($auth, $args, $nid, $settings).
				self::location_control($auth, $args, $nid, $settings).
				self::user_control($auth, $args, $nid, $settings).
				self::search_button($auth, $args, $nid, $settings).
				'</tr></thead></table>';
  }

  private static function allocation_type_control($auth, $args, $nid, $settings) {
    if($settings['manager_assignment_attr_id'])
      return '<th>' .
          data_entry_helper::select(array(
              'fieldname'=>$settings['allocationSelectID'],
              'lookupValues' => array(
                  $settings['assignment_attr_id'] => lang::get('Normal User'),
                  $settings['manager_assignment_attr_id'] => lang::get('Country Manager')
              )
          )) .
          '</th>';
    else
      return '<th style="display:none;">' .
          '<input type="hidden" id="'.$settings['allocationSelectID'].'" value="'.$settings['assignment_attr_id'].'" />' .
          '</th>';
  }

  private static function location_type_control($auth, $args, $nid, $settings) {
  	return '<th style="display:none;">' .
  			'[location_type_control TBD]' .
  			'</th>';
  }

  private static function country_control($auth, $args, $nid, $settings) {
  	return '<th>' .
    		data_entry_helper::select(array(
    			'fieldname'=>$settings['countrySelectID'],
    			'table'=>'location',
    			'valueField'=>'id',
    			'captionField'=>'name',
    			'blankText'=>lang::get('<Please select country>'),
    			'extraParams'=>$auth['read'] +
    						array('view'=>'detail',
  									'location_type_id'=>$args['country_type_term'],
  									'deleted'=>'f',
  									'orderby'=>'name'),
  				'caching'=>false
           	)) .
    		'</th>';
  }
  
  private static function location_control($auth, $args, $nid, $settings) {
  	return '<th>' .
  			data_entry_helper::select(array(
  					'fieldname' => $settings['siteSelectID'],
  					'label' => lang::get('Site'),
  					'report' => 'reports_for_prebuilt_forms/UKBMS/ebms_country_locations',
  					'valueField' => 'location_id',
  					'captionField' => 'name',
  					'parentControlId' => 'country-control-'.$nid,
  					'parentControlLabel' => lang::get('Country'),
  					'filterField' => 'country_location_id',
  					'extraParams' => $auth['read'] +
  									array('country_type_id'=>$args['country_type_term'],
  											'location_type_id'=>$args['site_type_term']),
  					'caching' => false
  			)) .
  			'</th>';
  }

  private static function user_control($auth, $args, $nid, $settings) {
    // The option values are CMS User ID, not Indicia ID. Want users avaiable as soon as they are created.
    $ctrl = '';
    $userList=array();
    $userListArr = array();

    // user is assumed to be a manager, and has access to the full list of users. Do not cache.
    $userList=array(); // make sure I'm on list
    // look up all users, not just those that have entered data.
    $results = db_query('SELECT uid, name FROM {users}');
    // assume drupal7
    foreach ($results as $result) {
      if($result->uid){ // ignore unauthorised user, uid zero
        $account = user_load($result->uid);
        // TODO confirm they have data entry permission
        $userList[$account->uid] = array('name'=>$account->name);
      }
    }
    foreach($userList as $id => $account) {
      $userListArr[$id] = $account['name'];
    }
    data_entry_helper::$javascript .= "indiciaData.fullUserList = ".json_encode(array_keys($userList)).";\n";

    natcasesort($userListArr);

    return '<th>' .
      data_entry_helper::select(array(
        'fieldname' => $settings['userSelectID'],
        'lookupValues' => $userListArr,
        'blankText' => lang::get('<Please select user>')
      )) .
      '</th>';
  }

  private static function search_button($auth, $args, $nid, $settings) {
  	return '<th>' .
  			'<input type="button" id="'.$settings['searchID'].'" class="ui-corner-all ui-widget-content" value="'.lang::get('search').'" />' .
  			'</th>';
  }

  private static function grid($auth, $args, $nid, $settings) {
  	return '<table class="ui-widget ui-widget-content reallocate-grid" id="'.$settings['gridID'].'">' .
  			'<thead class="ui-widget-header">' .
  			'<tr>' .
  			'<td colspan=6>'.
  			'<input type="button" disabled="disabled" class="ui-corner-all ui-widget-content '.$settings['selectAllClass'].'" value="'.lang::get('Select All').'" />' .
  			'<input type="button" disabled="disabled" class="ui-corner-all ui-widget-content '.$settings['deselectAllClass'].'" value="'.lang::get('Deselect All').'" />' .
  			'</td>' .
  			'</tr>' .
  			'<tr>' .
  			'<th>'.lang::get('Allocated?').'</th>' .
  			'<th>'.lang::get('Country').'</th>' .
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
  			'<td colspan=6>'.
  			'<input type="button" disabled="disabled" class="ui-corner-all ui-widget-content '.$settings['selectAllClass'].'" value="'.$settings['selectAllButton'].'" />' .
  			'<input type="button" disabled="disabled" class="ui-corner-all ui-widget-content '.$settings['deselectAllClass'].'" value="'.$settings['deselectAllButton'].'" />' .
  			'</td>' .
  			'</tr>' .
  			'</tfoot>' .
  			'</table>';
  }
  		 
  
}
