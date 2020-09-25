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
 * @package    Client
 * @subpackage PrebuiltForms
 * @author    Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link     http://code.google.com/p/indicia/
 */

/**
 * Prebuilt Indicia data entry form.
 * 
 * @package    Client
 * @subpackage PrebuiltForms
 */

require_once('dynamic_location.php');

class iform_dynamic_npms_plot_location extends iform_dynamic_location {

  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_dynamic_npms_plot_location_definition() {
    return array(
      'title'=>'Enter an NPMS plot',
      'category' => 'Miscellaneous',
      'description'=>'A data entry form specifically designed for NPMS plots.',
      'recommended'=>false
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
          'name' => 'square_location_type_id',
          'caption' => 'Square location type id',
          'description' => 'ID of the NPMS square location type.',
          'type'=>'string',
          'group' => 'IDs',
        ),
        array(
          'name' => 'user_square_attr_id',
          'caption' => 'User square attr id',
          'description' => 'ID of the person attribute that holds user square allocations.',
          'type'=>'string',
          'group' => 'IDs',
        ),
      )
    );
    return $retVal;
  }
  
  public static function get_form($args, $nid) {
    if (!empty($_GET['summary_mode']) && $_GET['summary_mode']=='true') {
      data_entry_helper::$javascript .= "indiciaData.summaryMode=true;\n";
    }
    if (empty($args['square_location_type_id']))
      return '<div><em>Please fill in the square location type id argument</em></div>';
    if (empty($args['user_square_attr_id']))
      return '<div><em>Please fill in the user squares attribute id argument</em></div>';
    return parent::get_form($args, $nid);
  }

  /**
   * Override the default submit buttons as delete needs to be hidden in summary mode.
   */
  protected static function getSubmitButtons($args) {
    $r = '';
    global $indicia_templates;
    $r .= '<input type="submit" class="' . $indicia_templates['buttonDefaultClass'] . '" id="save-button" value="'.lang::get('Submit')."\" />\n";
    if (!empty($_GET['location_id'])) {
      //Don't display delete if in view only mode
      if (empty($_GET['summary_mode']) || $_GET['summary_mode']=='false') {
        // use a button here, not input, as Chrome does not post the input value
        $r .= '<button type="submit" class="' . $indicia_templates['buttonWarningClass'] . '" id="delete-button" name="delete-button" value="delete" >'.lang::get('Delete')."</button>\n";
        data_entry_helper::$javascript .= "$('#delete-button').click(function(e) {
          if (!confirm(\"Are you sure you want to delete this location?\")) {
            e.preventDefault();
            return false;
          }
        });\n";
      }
    }
    return $r;
  }
  
  /*
   * Only load existing plot data if the user is assigned the plot
   */
  protected static function getEntity($args, $auth) {
    data_entry_helper::$entity_to_load = array();
    if (!empty($_GET['zoom_id'])) {
      self::zoom_map_when_adding($auth['read'], 'location', $_GET['zoom_id']); 
    } else {
      $accessAllowed=false;
      //If we can't get current user id then set to 0 so we don't retrieve any data
      if (function_exists('hostsite_get_user_field'))
        $currentUserId=hostsite_get_user_field('indicia_user_id');
      else 
        $currentUserId=0;
      //Get the squares/plots user has access to.
      $accessCheckData = data_entry_helper::get_report_data(array(
        'dataSource'=>'projects/npms/get_my_squares_and_plots',
        'readAuth'=>$auth['read'],
        'extraParams'=>array('core_square_location_type_id'=>$args['square_location_type_id'],'additional_square_location_type_id'=>$args['square_location_type_id'],'current_user_id'=>$currentUserId,'user_square_attr_id'=>$args['user_square_attr_id'],'no_vice_county_found_message'=>'','vice_county_location_attribute_id'=>0,'pss_mode'=>true)
      )); 
      //Go through each of their plot/square allocations and only load the data if there is a match for the
      //location_id in the URL params.
      if (!empty($accessCheckData)) {
        foreach ($accessCheckData as $accessCheckDataItem) {
          if (!empty($accessCheckDataItem['id'])&&$accessCheckDataItem['id']==$_GET['location_id'])
            $accessAllowed=true;
        }
      }
      if ($accessAllowed===true)
        data_entry_helper::load_existing_record($auth['read'], 'location', $_GET['location_id'], 'detail', false, true);   
    }
  }  
}

