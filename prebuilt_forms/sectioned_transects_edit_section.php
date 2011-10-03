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

require_once 'includes/map.php';
require_once 'includes/form_generation.php';

/**
 * 
 * 
 * @package Client
 * @subpackage PrebuiltForms
 * Form for adding or editing the site details on a transect which contains a number of sections.
 */
class iform_sectioned_transects_edit_section {
  
  /** 
   * Return the form metadata. 
   * @return array The definition of the form.
   */
  public static function get_sectioned_transects_edit_section_definition() {
    return array(
      'title'=>'Section editor',
      'category' => 'Sectioned Transects',
      'description'=>'Form for adding or editing the details of a single section within a transect.'
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   * @todo: Implement this method
   */
  public static function get_parameters() {   
    return array_merge(
        iform_map_get_map_parameters(),
        array(
          array(
            'name'=>'survey_id',
            'caption'=>'Survey',
            'description'=>'The survey that data will be posted into.',
            'type'=>'select',
            'table'=>'survey',
            'captionField'=>'title',
            'valueField'=>'id',
            'siteSpecific'=>true
          ),
          array(
            'name'=>'sites_list_path',
            'caption'=>'Site list page path',
            'description'=>'Enter the path to the page which the site list is on.',
            'type'=>'text_input',
            'group'=>'Navigation Settings'
          ),
          array(
            'name'=>'transect_edit_path',
            'caption'=>'Transect edit page path',
            'description'=>'Enter the path to the page which the transect editor is on.',
            'type'=>'text_input',
            'group'=>'Navigation Settings'
          ),
        )
    );
  }
  
  /**
   * Return the generated form output.
   * @param array $args List of parameter values passed through to the form depending on how the form has been configured.
   * This array always contains a value for language.
   * @param object $node The Drupal node object.
   * @param array $response When this form is reloading after saving a submission, contains the response from the service call.
   * Note this does not apply when redirecting (in this case the details of the saved object are in the $_GET data).
   * @return Form HTML.
   * @todo: Implement this method 
   */
  public static function get_form($args, $node, $response=null) {
    require_once drupal_get_path('module', 'iform').'/client_helpers/map_helper.php';
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $settings = array(
      'locationTypes' => helper_base::get_termlist_terms($auth, 'indicia:location_types', array('Transect Section')),
      'locationId' => isset($_GET['section_id']) ? $_GET['section_id'] : null,
      'parentId' => isset($_GET['transect_id']) ? $_GET['transect_id'] : null
    );
    if ($settings['parentId']) {
      $parent = data_entry_helper::get_population_data(array(
        'table' => 'location',
        'extraParams' => $auth['read'] + array('view'=>'detail','id'=>$settings['parentId'],'deleted'=>'f'),
        'nocache' => true
      ));
      $settings['parent'] = $parent[0];
    }
    else
      return 'This form must be called with a parent transect_id parameter.';
    $settings['sections'] = data_entry_helper::get_population_data(array(
      'table' => 'location',
      'extraParams' => $auth['read'] + array('view'=>'detail','parent_id'=>$settings['parentId'],'deleted'=>'f'),
      'nocache' => true
    ));
    if ($settings['locationId']) 
      data_entry_helper::load_existing_record($auth['read'], 'location', $settings['locationId']);
    else
      data_entry_helper::$entity_to_load['location:code'] = 'S'.(count($settings['sections'])+1);
    $settings['attributes'] = data_entry_helper::getAttributes(array(
        'id' => $settings['locationId'],
        'valuetable'=>'location_attribute_value',
        'attrtable'=>'location_attribute',
        'key'=>'location_id',
        'fieldprefix'=>'locAttr',
        'extraParams'=>$auth['read'],
        'survey_id'=>$args['survey_id'],
        'location_type_id'=>$settings['locationTypes'][0]['id']
    ));
    if (data_entry_helper::$entity_to_load['location:code'])
    $r = '<form method="post" id="input-form">';    
    $r .= $auth['write'];
    $r .= '<div id="controls">';
    $customAttributeTabs = array_merge(array(
      'Section' => array('[*]'),
    ), get_attribute_tabs($settings['attributes']));
    if (count($customAttributeTabs)>1) {
      $headerOptions = array('tabs'=>array());
      foreach($customAttributeTabs as $tab=>$content) {
        $alias = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($tab));
        $headerOptions['tabs']['#'.$alias] = lang::get($tab); 
      }
      $r .= data_entry_helper::tab_header($headerOptions);
      data_entry_helper::enable_tabs(array(
          'divId'=>'controls',
          'style'=>$args['interface'],
          'progressBar' => isset($args['tabProgress']) && $args['tabProgress']==true
      ));
    }
    foreach($customAttributeTabs as $tab=>$content) {
      if ($tab=='Section')
        $r .= self::get_section_tab($auth, $args, $settings);
      else {
        $alias = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($tab));
        $r .= "\n<div id=\"$alias\">\n";
        $r .= get_attribute_html($settings['attributes'], $args, array('extraParams'=>$auth['read']), $tab); 
        $r .= "</div>\n";
      }
        
    }
    $r .= '</div>'; // controls
    $r .='</form>';
    data_entry_helper::link_default_stylesheet();
    if (function_exists('drupal_set_breadcrumb')) {
      $breadcrumb = array();
      $breadcrumb[] = l('Home', '<front>');
      $breadcrumb[] = l('Sites', $args['sites_list_path']);
      $breadcrumb[] = l($settings['parent']['name'], $args['transect_edit_path'], array('query'=>array('id'=>$settings['parentId'])));
      $breadcrumb[] = $settings['locationId'] ? data_entry_helper::$entity_to_load['location:name'] : lang::get('new section');
      drupal_set_breadcrumb($breadcrumb);
    }
    return $r;
  }
    
  private function get_section_tab($auth, $args, $settings) {
    $r = '<div id="section" class="ui-helper-clearfix">';  
    if ($settings['locationId'])
      $r .= '<input type="hidden" name="location:id" value="'.$settings['locationId']."\" />\n";
    $r .= "<input type=\"hidden\" name=\"website_id\" value=\"".$args['website_id']."\" />\n";
    $r .= "<input type=\"hidden\" name=\"location:location_type_id\" value=\"".$settings['locationTypes'][0]['id']."\" />\n";
    $r .= '<input type="hidden" name="location:parent_id" value="'.$settings['parentId']."\" />\n";
    // force a blank centroid, so that the Warehouse will recalculate it from the boundary
    $r .= "<input type=\"hidden\" name=\"location:centroid_geom\" value=\"\" />\n";
    $r .= '<div class="left" style="width: 45%"><fieldset><legend>'.lang::get('Section Details').'</legend>';
    $sectionName = lang::get('{1} - section {2}', $settings['parent']['name'], str_replace('S', '', data_entry_helper::$entity_to_load['location:code']));
    $r .= "<input type=\"hidden\" name=\"location:name\" value=\"$sectionName\" />\n";
    $r .= "<input type=\"hidden\" name=\"location:code\" id=\"location_code\" value=\"".data_entry_helper::$entity_to_load['location:code']."\" />\n";
    // if the from (calling) page is defined in the url, store this for use after the post
    if (!empty($_GET['from']))
      $r .= "<input type=\"hidden\" name=\"from\" value=\"".$_GET['from']."\" />\n";
    $r .= '<h2>'.$sectionName.'</h2>';   
    $list = explode(',', str_replace(' ', '', $args['spatial_systems']));
    // output a hidden to contain the parent Geom, so we have something to zoom to if nothing else
    $r .= '<input type="hidden" id="parent-geom" value="'.$settings['parent']['centroid_geom'].'" />';
    $r .= '<div id="section-geoms">';
    foreach($settings['sections'] as $section) {
      $code = $section['code'];
      $r .= '<input type="hidden" id="'.$code.'" name="'.$code.'" value="' . $section['boundary_geom'] . '"/>';
    }
    $r .= '</div>';
    // add an input for the boundary geom of the one we are editing
    $r .= '<input type="hidden" id="boundary_geom" name="location:boundary_geom" value="' . data_entry_helper::$entity_to_load['location:boundary_geom'] . '"/>';
    
    // setup the map options
    $options = iform_map_get_map_options($args, $auth['read']);
    $options['standardControls'][] = 'drawLine';
    $options['standardControls'][] = 'modifyFeature';
    $options['clickForSpatialRef'] = false;
    $options['toolbarDiv'] = 'top';
    $r .= get_attribute_html($settings['attributes'], $args, array('extraParams'=>$auth['read']), 'Section');
    $olOptions = iform_map_get_ol_options($args);
    $r .= '<input type="submit" value="'.lang::get('Save').'" class="ui-state-default ui-corner-all" />';
    $r .= '</fieldset></div>';
    $r .= '<div class="right" style="width: '.$options['width'].'px;">';
    $help = lang::get('Use the Draw Lines tool to draw the section by clicking on the map. Double click to finish the line. You can edit an '.
        'existing line by selecting the Modify Feature tool then dragging the markers to change the line shape.');
    $r .= '<p class="ui-state-highlight page-notice ui-corner-all">'.$help.'</p>';
    $r .= map_helper::map_panel($options, $olOptions);
    $r .= '</div></div>';
    return $r;
  }
  
  /**
   * Construct a submission for the location.
   * @param array $values Associative array of form data values. 
   * @param array $args iform parameters. 
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    $s = submission_builder::build_submission($values, 
      array(
        'model' => 'location'
      )
    );
    if (empty($values['location:id']))
      $s['subModels'] = array(
        array(
          'fkId' => 'location_id', 
          'model' => array(
            'id' => 'locations_website',
            'fields' => array(
              'website_id' => $args['website_id']
            )
          )
        )
      );
    return $s;
  }  
  
  /** 
   * Dynamically redirect after a successful save. This lets us redirect back to the parent transect.
   */
  public static function get_redirect_on_success($values, $args) {
    if (!empty($values['from']) && $values['from']=='transect' && !empty($args['transect_edit_path']))
      return $args['transect_edit_path'] . '?id='.$values['location:parent_id'];
  }

}
