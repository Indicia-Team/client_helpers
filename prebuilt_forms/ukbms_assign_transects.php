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
 * @link https://github.com/indicia-team/client_helpers
 */

require_once 'includes/map.php';
require_once 'includes/form_generation.php';

/**
 * Form for adding or editing the site details on a transect which contains a number of sections.
 */
class iform_ukbms_assign_transects {
  
  /**
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_ukbms_assign_transects_definition() {
    return array(
      'title'=>'UKBMS Location assigner',
      'category' => 'UKBMS custom forms',
      'description'=>'Form for requesting exsting sites be assigned to a user.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   * @todo: Implement this method
   */
  public static function get_parameters() {
    $retVal = array_merge(
      iform_map_get_map_parameters(),
      iform_map_get_georef_parameters(),
      array(
        array(
          'name' => 'limit',
          'caption' => 'Limit',
          'description' => 'Max number of sites displayed at once in table.',
          'type' => 'int',
          'required' => true,
          'default' => 30,
        ),
        array(
          'name'=>'main_type_term_1',
          'caption'=>'Location type term 1',
          'description'=>'Select the term used for the first main site location type.',
          'type' => 'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'term',
          'extraParams' => array('termlist_external_key'=>'indicia:location_types'),
          'required' => true,
          'group'=>'Location Types',
        ),
        array(
          'name'=>'main_type_term_2',
          'caption'=>'Location type term 2',
          'description'=>'Select the term used for the second main site location type.',
          'type' => 'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'term',
          'extraParams' => array('termlist_external_key'=>'indicia:location_types'),
          'required' => false,
          'group'=>'Location Types',
        ),
        array(
          'name'=>'main_type_term_3',
          'caption'=>'Location type term 3',
          'description'=>'Select the term used for the third main site location type.',
          'type' => 'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'term',
          'extraParams' => array('termlist_external_key'=>'indicia:location_types'),
          'required' => false,
          'group'=>'Location Types',
        ),
        array(
          'name'=>'cms_attr_id',
          'caption'=>'CMS Attribute',
          'description'=>'Indicia multi-value location attribute that records the CMS user assigned to a location.',
          'type'=>'select',
          'table'=>'location_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'required' => true,
          'group'=>'Location Attributes',
        ),
        array(
          'name'=>'holiday_attr_id',
          'caption'=>'Holiday square Attribute',
          'description'=>'Indicia boolean location attribute that records whether a location is a holiday square.',
          'type'=>'select',
          'table'=>'location_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'required' => true,
          'group'=>'Location Attributes',
        ),
        array(
          'name'=>'email',
          'caption'=>'Email to send messages to',
          'description'=>'Email to send messages to.',
          'type'=>'string',
          'required' => true,
        )
      ));
    for($i= count($retVal)-1; $i>=0; $i--){
      switch($retVal[$i]['name']) {
        case 'georefDriver':
          $retVal[$i]['required']=false; // method of georef detection is to see if driver specified: allows ommision of area preferences.
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
   * @todo: Implement this method
   */
  public static function get_form($args, $nid, $response=null) {
    global $user;
    
    $userID = $user->uid;
    
    iform_load_helpers(array('map_helper'));
    data_entry_helper::add_resource('jquery_form');
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $auth['write_tokens']['persist_auth'] = true;
    
    $typeTerms = array();
    if(!empty($args['main_type_term_1'])) $typeTerms[] = $args['main_type_term_1'];
    if(!empty($args['main_type_term_2'])) $typeTerms[] = $args['main_type_term_2'];
    if(!empty($args['main_type_term_3'])) $typeTerms[] = $args['main_type_term_3'];
    $typeTermIDs = helper_base::get_termlist_terms($auth, 'indicia:location_types', $typeTerms);
    $lookUpValues = array('' => '<' . lang::get('Please select') . '>');
    foreach($typeTermIDs as $termDetails){
      $lookUpValues[$termDetails['id']] = $termDetails['term'];
    }
    
    $preLoad=array();
    $headers = array();
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8;';
    $emailFrom = variable_get('site_mail', '');
    if (!empty($emailFrom)) {
      $headers[] = 'From: '. $emailFrom;
      $headers[] = 'Reply-To: '. $emailFrom;
      $headers[] = 'Return-Path: '. $emailFrom;
    }
    $headers = implode("\r\n", $headers) . PHP_EOL;
    if ($_POST) {
      foreach($_POST as $key => $value) {
        if(preg_match("/^([a-z\_]+)\_(\d+)\_(\d+)$/", $key, $matches)) {
          $preLoad[] = '"'.$matches[2].'"';
          if($value == 0) continue;
          switch($matches[1]) {
            case 'assign_holiday':
              $Model = data_entry_helper::wrap(array(
              'website_id' => $args['website_id'],
              'location_attribute_value:location_id' => $matches[2],
              'location_attribute_value:location_attribute_id' => $args['cms_attr_id'],
              'location_attribute_value:int_value' => $matches[3]
              ), 'location_attribute_value');
              $response = data_entry_helper::forward_post_to('save', $Model, $auth['write_tokens']);
              
              if (array_key_exists('email', $args) && !empty($args['email'])) {
                $subject = 'Holiday square allocation : {first_name} {last_name} : {location_name}';
                $message = '{first_name} {last_name} {email} has allocated themselves site {location_name} as a Holiday square.';
                $emailTo = $args['email'];
                $locationRecords = data_entry_helper::get_population_data(array(
                  'table' => 'location',
                  'extraParams' => $auth['read'] + array('id' => $matches[2]),
                  'nocache' => true,
                ));
                $locationName = $locationRecords[0]['name'];
                // Replacements for the person's name and the location name tags in the subject and
                // message with the real location and person name.
                $subject = str_replace(array("{first_name}", "{last_name}", "{email}", "{location_name}"),
                  array(hostsite_get_user_field('first_name'),
                    hostsite_get_user_field('last_name'),
                    hostsite_get_user_field('mail'),
                    $locationName),
                  $subject);
                $message = str_replace(array("{first_name}", "{last_name}", "{email}", "{location_name}"),
                  array(hostsite_get_user_field('first_name'),
                    hostsite_get_user_field('last_name'),
                    hostsite_get_user_field('mail'),
                    $locationName),
                  $message);
                $sent = mail($emailTo, $subject, wordwrap($message, 70), $headers);
                if ($sent) {
                  drupal_set_message('Management notification email sent succesfully');
                } else {
                  drupal_set_message('Management notification email send failed');
                }
              }
              break;
            case 'unassign_holiday':
              $location_attr_list_args=array(
              'nocache'=>true,
              'extraParams'=>array_merge(array('view'=>'list',
              'website_id'=>$args['website_id'],
              'location_id' => $matches[2],
              'location_attribute_id' => $args['cms_attr_id'],
              'value' => $matches[3]), $auth['read']),
              'table'=>'location_attribute_value');
              $locAttrList = data_entry_helper::get_population_data($location_attr_list_args);
              foreach($locAttrList as $locAttr) {
                $Model = data_entry_helper::wrap(array(
                  'website_id' => $args['website_id'],
                  'location_attribute_value:id' => $locAttr['id'],
                  'location_attribute_value:location_id' => $matches[2],
                  'location_attribute_value:location_attribute_id' => $args['cms_attr_id'],
                  'location_attribute_value:int_value' => '',
                  'location_attribute_value:deleted' => 't'
                ), 'location_attribute_value');
                $response = data_entry_helper::forward_post_to('save', $Model, $auth['write_tokens']);
              }
              if (array_key_exists('email', $args) && !empty($args['email'])) {
                $subject = 'Holiday square revoked : {first_name} {last_name} : {location_name}';
                $message = '{first_name} {last_name} {email} has un-allocated themselves site {location_name} as a Holiday square.';
                $emailTo = $args['email'];
                $locationRecords = data_entry_helper::get_population_data(array(
                  'table' => 'location',
                  'extraParams' => $auth['read'] + array('id' => $matches[2]),
                  'nocache' => true,
                ));
                $locationName = $locationRecords[0]['name'];
                // Replacements for the person's name and the location name tags in the subject and
                // message with the real location and person name.
                $subject = str_replace(array("{first_name}", "{last_name}", "{email}", "{location_name}"),
                  array(hostsite_get_user_field('first_name'),
                    hostsite_get_user_field('last_name'),
                    hostsite_get_user_field('mail'),
                    $locationName),
                  $subject);
                $message = str_replace(array("{first_name}", "{last_name}", "{email}", "{location_name}"),
                  array(hostsite_get_user_field('first_name'),
                    hostsite_get_user_field('last_name'),
                    hostsite_get_user_field('mail'),
                    $locationName),
                  $message);
                $sent = mail($emailTo, $subject, wordwrap($message, 70), $headers);
                if ($sent) {
                  drupal_set_message('Management notification email sent succesfully');
                } else {
                  drupal_set_message('Management notification email send failed');
                }
              }
              break;
          }
        }
      }
    }
    
    // Set up the form
    data_entry_helper::enable_validation('input-form');
    $r = '<div id="site-details" class="ui-helper-clearfix">' . PHP_EOL;
    $r .= '<form  method="post">' . PHP_EOL;
    $r .= $auth['write'] . PHP_EOL;
    $r .= '<input type="hidden" name="website_id" value="'.$args['website_id'].'"/>' . PHP_EOL;
    
    $help = '<p>' . t('The aim of the holiday squares webpage is to improve coverage of WCBS squares in the uplands of ' .
      'northern and western Britain. Few recorders live in these areas, but they are very popular holiday ' .
      'destinations. Squares are currently available in Cumbria, Lancashire, North East England, Scotland, ' .
      'Wales and Yorkshire.') . '</p>';
    
    $help2 = '<ol>' .
      '<li>' . t('Use the &quot;Find place&quot; control above the map to find a town or village near the area you ' .
        'are interested in, then tailor your search area by dragging and/or zooming the map.') . '</li>' .
        '<li>' . t('Press the &quotList closest sites&quot button (beneath the map) to list the @limit squares closest to the ' .
          'centre of the map - these will be displayed in a grid below the map in a table in ascending distance order.' ,
          array('@limit' => $args['limit'])) . ' ' .
          t('If there are no squares in the area you have selected, the map will be automatically panned and/or zoomed to ' .
            'show the nearest square.') . '</li>' ;
          if($userID > 0) {
            $help2 .= '<li>' .t('The table will display an individual control per site, indicating what action you may request ' .
              'for each of square. Use the checkboxes to pick what you wish to happen.') . '</li>' .
              '<li>' . t('When you have selected what action(s) you wish to carry out, click on the &quot;Carry out checked actions&quot; button.'). '</li></ol>' ;
            
            $help2 .= '<p>' . t('Squares that are already allocated to you will be shown in red; assigned to someone else in green, and unallocated in blue.') . '</p>';
          } else {
            $help2 .= '<li>' .t('In order to request a square to be assigned to you, you must be registered and logged in.') . '</li></ol>';
            $help2 .= '<p>' . t('Squares that are assigned to someone are shown in green, and unallocated in blue.') . '</p>';
          }
          $help2 .= '<p>' . t('If you change the view (e.g. search on different location), you will need to use the ' .
            '&quotList closest site&quot button again to refresh the grid for the new map view.') . '</p>';
          if($userID > 0) {
            if($args['email'] !== null && !empty($args['email'])) {
              $help2 .= '<p>' . t('An email will be sent to @email to provide management visibility of the Holiday square allocation.', array('@email' => $args['email'])) . '</p>';
            }
          }
          
          // TODO : check de-assignment for normal users, as well as admin.
          
          if(count($lookUpValues)>2) { // includes the "please select" empty option
            $help .= '<p>' . t('First pick the site type you are interested in.') . '</p>' . $help2;
            
            $r .= '<div class="ui-state-highlight page-notice ui-corner-all">'.$help.'</div>';
            $value = '';
            if ($_POST && array_key_exists('location:location_type_id', $_POST))
              $value = $_POST['location:location_type_id'];
              
              $r .= data_entry_helper::select(array(
                'label' => lang::get('Site Type'),
                'id' => 'location_type_id',
                'fieldname' => 'location:location_type_id',
                'lookupValues' => $lookUpValues,
                'validation'=>array('required'),
                'default' => $value
              ));
          } else {
            
            $help .= $help2 .
            '<p>' . t('Currently this form is configured to handle @type sites only.', array('@type' => $typeTermIDs[0]['term'])) . '</p>';
            $r .= '<div class="ui-state-highlight page-notice ui-corner-all">'.$help.'</div>';
            $r .= '<input type="hidden" name="location:location_type_id" value="'.$typeTermIDs[0]['id'].'" />' . PHP_EOL;
          }
          if(isset($args['georefDriver']) && $args['georefDriver']!='') {
            $r .= data_entry_helper::georeference_lookup(iform_map_get_georef_options($args, $auth['read']));
          }
          // setup the map options
          $options = iform_map_get_map_options($args, $auth['read']);
          $olOptions = iform_map_get_ol_options($args);
          $options['clickForSpatialRef']=false;
          $options['scroll_wheel_zoom'] = false;
          $r .= map_helper::map_panel($options, $olOptions);
          $r .= '<input type="button" class="indicia-button allocation-search-button" onclick="indiciaFns.listClosestSites([])" value="' . t('List closest sites') . '">';
          $r .= '<table id="featureTable" class="ui-widget ui-widget-content" style="display:none;"><thead class="ui-widget-header"><tr><th class="ui-widget-header">'.t('Site').
          //            '</th><th class="ui-widget-header">'.t('Holiday square?').
          '</th><th class="ui-widget-header">'.t('Distance').
          '</th><th class="ui-widget-header">'.t('Status').
          '</th><th class="ui-widget-header">'.t('Actions').
          '</th></thead><tbody id="featureTableBody"></tbody></table>'.
          '</form>';
          $r .= '</div>'; // site-details
          // This must go after the map panel, so it has created its toolbar
          
          // Inform JS where to post data to for AJAX form saving
          data_entry_helper::$javascript .= 'indiciaData.limit = ' . $args['limit'] . ';' . PHP_EOL . // max number of features in feature grid
          'indiciaData.userID = ' . $userID . ';' . PHP_EOL .
          'indiciaData.cms_attr_id = ' . $args['cms_attr_id'] . ';' . PHP_EOL .
          'indiciaData.holiday_attr_id = ' . $args['holiday_attr_id'] . ';' . PHP_EOL .
          'indiciaData.indiciaSvc = "' . data_entry_helper::$base_url . '";' . PHP_EOL .
          'indiciaData.siteDetails = "' . hostsite_get_url('/site-details') . '";' . PHP_EOL .
          'indiciaData.initFeatureIds = [' . implode(', ', $preLoad) . '];' . PHP_EOL .
          'indiciaData.user_id = ' . (($user_id = hostsite_get_user_field('indicia_user_id')) ? $user_id : 0) . ';' . PHP_EOL ;
          
          return $r;
  }
  
  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values.
   * @param array $args iform parameters.
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    return null;
  }
  
}
