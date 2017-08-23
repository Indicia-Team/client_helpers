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

require_once('includes/map.php');
require_once('includes/report.php');

// TODO DEV
// picture of species in corner.
// Add speed control
// Sort colour of control table.
// preload values in controls from optional URL params: Year, Species, Compare
// Add function to allow user to copy URL.

/**
 * Prebuilt Indicia data form that lists the output of any report on a map.
 *
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_time_lapse_map {

  /** 
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_time_lapse_map_definition() {
    return array(
      'title'=>'Time-lapse map',
      'category' => 'Reporting',
      'description'=>'Outputs data from a report onto a map which can show the spread of a species population over time.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    $paramArray = array_merge(
        iform_map_get_map_parameters(), 
        iform_report_get_minimal_report_parameters(),
        array(
          array(
              'name' => 'firstYear',
              'caption' => 'First Year of Data',
              'description' => 'Used to determine first year displayed in the year control. Final Year will be current year.',
              'type' => 'int',
              'group' => 'Controls'
          ),
          array(
              'name' => 'yearSelector',
              'caption' => 'Year selector',
              'description' => 'Does the user choose a single year to show data for, or is the full dataset shown?',
              'type' => 'boolean',
              'group' => 'Controls',
              'default' => true,
              'required' => false
          ),
          array(
              'name' => 'dotSize',
              'caption' => 'Dot Size',
              'description' => 'Initial size in pixels of observation dots on map. Can be overriden by a control.',
              'type' => 'select',
              'options' => array(
                '2' => '2',
                '3' => '3',
                '4' => '4',
                '5' => '5'
              ),
              'default' => '3',
              'group' => 'Controls'
          ),
          array(
              'name' => 'numberOfDates',
              'caption' => 'Number of Dates',
              'description' => 'The maximum number of dates displayed on the X-axis. Used to prevent crowding. The minimum spacing is one date displayed per week. Date range is determined by the data.',
              'type' => 'int',
              'default' => 11,
              'group' => 'Controls'
          ),
          array(
              'name' => 'frameRate',
              'caption' => 'Animation Frame Rate',
              'description' => 'Number of frames displayed per second.',
              'type' => 'int',
              'default' => 4,
              'group' => 'Controls'
          )
        )
    );
    $retVal = array();
    foreach ($paramArray as $param) {
      if (!in_array($param['name'], array('map_width', 'remember_pos', 'location_boundary_id', 'items_per_page', 'param_ignores', 'param_defaults'/* , 'message_after_save', 'redirect_on_success' */)))
        $retVal[] = $param;
      if ($param['name'] === 'report_name') {
        $param['default'] = 'reports_for_prebuilt_forms/time_lapse_map/filterable_time_lapse_map_data';
      }
    }
    return $retVal;
  }

  /**
   * Return the Indicia form code
   * @param array $args Input parameters.
   * @param array $nid Drupal node object's ID
   * @param array $response Response from Indicia services after posting.
   * @return HTML string
   */
  public static function get_form($args, $nid, $response) {
    $r = "";
    $args = array_merge(array(
        'yearSelector' => true
    ), $args);
    data_entry_helper::add_resource('jquery_ui');
    hostsite_add_library('jquery-ui-slider');
    $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
    
    $now = new DateTime('now');
    $year = (isset($_REQUEST['year']) ? $_REQUEST['year'] : $year = $now->format('Y'));

    iform_load_helpers(array('report_helper','map_helper'));
    $args['param_defaults'] = '';
    $options = iform_report_get_report_options($args, $readAuth);
    
    $currentParamValues = array();
    if (isset($options['extraParams'])) {
      foreach ($options['extraParams'] as $key=>$value) {
        // trim data to ensure blank lines are not handled.
        $key = trim($key);
        $value = trim($value);
        // We have found a parameter, so put it in the request to the report service
        if (!empty($key))
          $currentParamValues[$key]=$value;
      }
    }
    $extras = '&wantColumns=1&wantParameters=1&'.report_helper::array_to_query_string($currentParamValues, true);
    $canIDuser = false;
  
    $r .= '<div id="errorMsg"></div>';
    $r .= '<div id="controls-toolbar">';
    if ($args['yearSelector']) {
      $r .= '<label for="yearControl">' . lang::get("Year") . ' : </label><select id="yearControl" name="year">';
      for($i = $now->format('Y'); $i >= $args['firstYear']; $i--){
        $r .= '<option value="'.$i.'">'.$i.'</option>';
      }
      $r .= "</select>\n";
    } else {
      $r .= "<input type=\"hidden\" id=\"yearControl\" name=\"123year\" value=\"all\" />\n";
    }
    $r .= '<label for="speciesControl">'.lang::get("Species").' : </label>' . 
        '<select id="speciesControl"><option value="">'.lang::get("Please select species").'</option></select>';
    $r .= "\n";
    
    $args['map_width']="auto";
    $options = iform_map_get_map_options($args, $readAuth);
    $olOptions = iform_map_get_ol_options($args);
    $options['editLayer'] = false;
    $options['clickForSpatialRef'] = false;
    $options['scroll_wheel_zoom'] = false;
    $r .= '<div class="leftMap mapContainers leftMapOnly">'.map_helper::map_panel($options, $olOptions).'</div>';
    $options['divId']='map2';
    
    $r .= '<div class="ui-helper-clearfix"></div><div id="timeControls">'.
        '<div id="timeSlider"></div>' .
        '<div id="toolbar">'.
        '<span id="dotControlLabel">' . lang::get('Dot Size') . ' :</span><div id="dotSlider"></div>' .
        '<button id="beginning">go to beginning</button><button id="playMap">play</button><button id="end">go to end</button>'.
        '<span id="dateControlLabel">'.lang::get("Date currently displayed").' : <span id="displayDate" ></span>'.
        '</div>';
    
    $imgPath = empty(data_entry_helper::$images_path) ? data_entry_helper::relative_client_helper_path()."../media/images/" : data_entry_helper::$images_path;    
    data_entry_helper::$javascript .= "
indiciaFns.initTimeLapseMap({
  dotSize: $args[dotSize],
  lat: $args[map_centroid_lat],
  long: $args[map_centroid_long],
  zoom: $args[map_zoom],
  base_url: '".data_entry_helper::$base_url."',
  report_name: '$args[report_name]',
  auth_token: '$readAuth[auth_token]',
  nonce: '$readAuth[nonce]',
  reportExtraParams: '$extras',
  indicia_user_id: ".(hostsite_get_user_field('indicia_user_id') ? hostsite_get_user_field('indicia_user_id') : 'false').",
  timeControlSelector: '#timeSlider',
  dotControlSelector: '#dotSlider',
  timerDelay: ".((int)1000/$args['frameRate']).",
  imgPath: '$imgPath',
  yearSelector: " . ($args['yearSelector'] ? 'true' : 'false') . ",
  firstYear: $args[firstYear]
});
";
    $r .= '</div>';
    return $r;
  }
}
