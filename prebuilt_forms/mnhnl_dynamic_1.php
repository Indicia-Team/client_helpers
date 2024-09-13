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
 * Prebuilt Indicia data entry form.
 * NB has Drupal specific code. Relies on presence of IForm Proxy.
 */

require_once 'dynamic_sample_occurrence.php';
require_once 'includes/mnhnl_common.php';

class iform_mnhnl_dynamic_1 extends iform_dynamic_sample_occurrence {

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_mnhnl_dynamic_1_definition() {
    return [
      'title' => 'MNHNL Dynamic 1 - dynamically generated data entry form',
      'category' => 'MNHNL forms',
      'helpLink' => 'https://github.com/Indicia-Team/client_helperswiki/TutorialDynamicForm',
      'description' => 'Derived from the Dynamic Sample Occurrence Form with custom headers and footers.',
      'supportsGroups' => TRUE,
    ];
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
          'name' => 'headerAndFooter',
          'caption' => 'Use Header and Footer',
          'description' => 'Include MNHNL header and footer html.',
          'type' => 'boolean',
          'group' => 'User Interface',
          'default' => false,
          'required' => false
        ),
        array(
          'name' => 'uses_location_assignment',
          'caption' => 'Uses Location Assignment',
          'description' => 'Does the form use locations assigned to users?',
          'type' => 'boolean',
          'group' => 'User Interface',
          'default' => false,
          'required' => false
        ),
        array(
          'name' => 'location_assignment_type',
          'caption' => 'Location Assignment Type',
          'description' => 'Choose the method by which locations are assigned to users. The Indicia User ID option requires easy_login.',
          'type' => 'select',
          'options' => array(
            'indicia' => 'Indicia Warehouse User ID recorded in a location attribute',
            'cms' => 'CMS User ID recorded in a location attribute'
          ),
          'group' => 'User Interface',
          'default' => 'indicia',
          'required' => false
        ),
        array(
          'name' => 'location_assignment_attr_id',
          'caption' => 'Location Assignment Attribute',
          'description' => 'The Location attribute that stores the user ID (as defined in the type above). ' .
                         'Depending on the exact configuration, this may need to be a multi-value one.',
          'type' => 'select',
          'table' => 'location_attribute',
          'valueField' => 'id',
          'captionField' => 'caption',
          'group' => 'User Interface',
          'required' => false
        ),
        array(
          'name' => 'location_assignment_location_type_id',
          'caption' => 'Location Assignment Location Type',
          'description' => 'When performing location assignment to users, filter available locations by this location type',
          'type' => 'select',
          'table' => 'termlists_term',
          'captionField' => 'term',
          'valueField' => 'id',
          'extraParams' => array('view' => 'list', 'termlist_external_key' => 'indicia:location_types'),
          'required' => false,
          'group' => 'User Interface'
        )
      )
    );
    return $retVal;
 }

  protected static function get_form_html($args, $auth, $attributes) {
    $squares = iform_mnhnl_listLocations($auth, $args);
    if($squares != "all" && count($squares)==0)
      return lang::get('Error: You do not have any squares allocated to you. Please contact your manager.');

    $r = call_user_func(array(self::$called_class, 'getHeaderHTML'), $args);
    $r .= parent::get_form_html($args, $auth, $attributes);
    $r .= call_user_func(array(self::$called_class, 'getTrailerHTML'), $args);
    return $r;
  }

  protected static function getGrid($args, $nid, array $auth) {
    $r = call_user_func(array(self::$called_class, 'getHeaderHTML'), $args);
    $r .= parent::getGrid($args, $nid, $auth);
    $r .= call_user_func(array(self::$called_class, 'getTrailerHTML'), $args);
    return $r;
  }

  protected static function getHeaderHTML($args) {
    $base = base_path();
    if(substr($base, -1)!='/') $base.='/';
    return (isset($args['headerAndFooter']) && $args['headerAndFooter'] ?
      '<div id="iform-header">
        <div id="iform-logo-left"><a href="http://www.environnement.public.lu" target="_blank"><img border="0" class="government-logo" alt="'.lang::get('Gouvernement').'" src="'.$base.'sites/all/files/gouv.png"></a></div>
        <div id="iform-logo-right"><a href="http://www.crpgl.lu" target="_blank"><img border="0" class="gabriel-lippmann-logo" alt="'.lang::get('Gabriel Lippmann').'" src="'.$base.\Drupal::service('extension.path.resolver')->getPath('module', 'iform').'/client_helpers/prebuilt_forms/images/mnhnl-gabriel-lippmann-logo.jpg"></a></div>
        </div>' : '');
  }

  protected static function getTrailerHTML($args) {
    return (isset($args['headerAndFooter']) && $args['headerAndFooter'] ?
      '<p id="iform-trailer">'.lang::get('LANG_Trailer_Text').'</p>' : '');
  }

  /*
   * Hide a control if a user is not in a particular group.
   *
   * $options Options array with the following possibilities:<ul>
   * <li><b>controlId</b><br/>
   * The control to hide. ID used as a jQuery selector.</li>
   * <li><b>groupId</b><br/>
   * Group to check the user is a member of.</li>
   */
  protected static function get_control_hideControlForNonGroupMembers($auth, $args, $tabalias, $options) {
    iform_load_helpers(array('report_helper'));
    $currentUserId=hostsite_get_user_field('indicia_user_id');
    if (empty($options['controlId'])) {
      hostsite_show_message('The option to hide a control based on group has been specified, but no option to indicate which control has been provided.');
      return false;
    }
    if (empty($options['groupId'])) {
      hostsite_show_message('The option to hide a control based on group has been specified, but no group id has been provided.');
      return false;
    }
    $reportOptions = array(
      'dataSource' => 'library/groups/group_members',
      'readAuth'=>$auth['read'],
      'mode' => 'report',
      'extraParams' => array('group_id'=>$options['groupId'])
    );
    $usersInGroup = report_helper::get_report_data($reportOptions);
    //Check all members in the group, if the current user is in the group, then there is no need to hide the control.
    $userFoundInGroup=false;
    foreach ($usersInGroup as $userInGroup) {
      if ($userInGroup['id']===$currentUserId)
        $userFoundInGroup=true;
    }
    if ($userFoundInGroup!==true||empty($_GET['group_id'])||$_GET['group_id']!=$options['groupId']) {
      //Parent hide control stops the control and label from showing on screen.
      //Disable control stops it appearing in the POST and getting submitted.
      data_entry_helper::$javascript .= "$('#".$options['controlId']."').attr('disabled', true);\n";
      data_entry_helper::$javascript .= "$('#".$options['controlId']."').parent().hide();\n";
    }
  }
}