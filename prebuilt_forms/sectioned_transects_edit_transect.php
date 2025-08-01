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
 * @link https://github.com/indicia-team/client_helpers/
 */

use IForm\prebuilt_forms\PageType;
use IForm\prebuilt_forms\PrebuiltFormInterface;

require_once 'includes/map.php';
require_once 'includes/form_generation.php';

/**
 * Form for adding or editing the site details on a transect which contains a number of sections.
 */
class iform_sectioned_transects_edit_transect implements PrebuiltFormInterface {

  /**
   * @var int Contains the id of the location attribute used to store the CMS user ID.
   */
  protected static $cmsUserAttrId;
  protected static $cmsUserList = NULL;

  /**
   * @var int Contains the id of the location attribute used to store the CMS user ID.
   */
  protected static $branchCmsUserAttrId;

  /**
   * @var string The Url to post AJAX form saves to.
   */
  protected static $ajaxFormUrl = NULL;
  protected static $ajaxFormSampleUrl = NULL;

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_sectioned_transects_edit_transect_definition() {
    return [
      'title' => 'Transect editor',
      'category' => 'Sectioned Transects',
      'description' => 'Form for adding or editing the site details on a transect which has a number of sub-sections.'
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
   * @return array List of parameters that this form requires.
   * @todo: Implement this method
   */
  public static function get_parameters() {
    return array_merge(
      iform_map_get_map_parameters(),
      iform_map_get_georef_parameters(),
      [
        [
          'name' => 'managerPermission',
          'caption' => 'Drupal Permission for Manager mode',
          'description' => 'Enter the Drupal permission name to be used to determine if this user is a manager. Entering this will allow the identified users to delete or modify the site even there are walks (samples) associated with it.',
          'type' => 'string',
          'required' => FALSE,
        ],
        [
          'name' => 'branch_assignment_permission',
          'label' => 'Drupal Permission name for Branch Manager',
          'type' => 'string',
          'description' => 'If you do not want to use the Branch Manager functionality, leave this blank. '.
                            'Otherwise, specify the name of a permission to which when assigned to a user determines that the user is a branch manager. '.
                            '<br />Requires a single-value Branch CMS User ID integer attribute on the locations.',
          'required' => FALSE,
          'group' => 'Transects Editor Settings',
        ],
        [
          'name' => 'maxSectionCount',
          'label' => 'Max. Section Count',
          'type' => 'int',
          'description' => 'The maximum number of sections a user is allowed to create for a transect site. If there is no user selectable attribute to set the number of sections, then the number is fixed at this value and the user will not be able to delete sections.',
          'group' => 'Transects Editor Settings',
        ],
        [
          'name' => 'survey_id',
          'caption' => 'Survey',
          'description' => 'The survey that data will be posted into.',
          'type' => 'select',
          'table' => 'survey',
          'captionField' => 'title',
          'valueField' => 'id',
          'siteSpecific' => TRUE,
        ],
        [
          'name' => 'sites_list_path',
          'caption' => 'Site list page path',
          'description' => 'Enter the path to the page which the site list is on.',
          'type' => 'string',
          'required' => TRUE,
          'group' => 'Transects Editor Settings',
        ],
        [
          'name' => 'transect_type_term',
          'caption' => 'Transect type term',
          'description' => 'Select the term used for transect location types.',
          'type' => 'select',
          'table' => 'termlists_term',
          'captionField' => 'term',
          'valueField' => 'term',
          'extraParams' => ['termlist_external_key' => 'indicia:location_types'],
          'required' => TRUE,
          'group' => 'Transects Editor Settings',
        ],
        [
          'name' => 'section_type_term',
          'caption' => 'Section type term',
          'description' => 'Select the term used for section location types.',
          'type' => 'select',
          'table' => 'termlists_term',
          'captionField' => 'term',
          'valueField' => 'term',
          'extraParams' => ['termlist_external_key' => 'indicia:location_types'],
          'required' => TRUE,
          'group' => 'Transects Editor Settings',
        ],
        [
          'name' => 'bottom_blocks',
          'caption' => 'Form blocks to place at bottom',
          'description' => 'A list of the blocks which need to be placed at the bottom of the form, below the map.',
          'type' => 'textarea',
          'group' => 'Transects Editor Settings',
          'siteSpecific' => TRUE,
          'required' => FALSE,
        ],
        [
          'name' => 'site_help',
          'caption' => 'Site Help Text',
          'description' => 'Help text to be placed on the Site tab, before the attributes.',
          'type' => 'textarea',
          'group' => 'Transects Editor Settings',
          'required' => FALSE,
        ],
        [
          'name' => 'spatial_systems',
          'caption' => 'Allowed Spatial Ref Systems',
          'description' => 'List of allowable spatial reference systems, comma separated. Use the spatial ref system code (e.g. OSGB or the EPSG code number such as 4326).',
          'type' => 'text_input',
          'group' => 'Other Map Settings',
        ],
        [
          'name' => 'maxPrecision',
          'caption' => 'Max Sref Precision',
          'description' => 'The maximum precision to be applied when determining the SREF. Leave blank to not set.',
          'type' => 'int',
          'required' => FALSE,
          'group' => 'Other Map Settings',
        ],
        [
          'name' => 'minPrecision',
          'caption' => 'Min Sref Precision',
          'description' => 'The minimum precision to be applied when determining the SREF. Leave blank to not set.',
          'type' => 'int',
          'required' => FALSE,
          'group' => 'Other Map Settings',
        ],
        [
          'name' => 'route_map_height',
          'caption' => 'Your Route Map Height (px)',
          'description' => 'Height in pixels of the map.',
          'type' => 'int',
          'group' => 'Initial Map View',
          'default' => 600,
        ],
        [
          'name' => 'route_map_buffer',
          'caption' => 'Your Route Map Buffer',
          'description' => 'Factor to multiple the size of the site by, in order to generate a margin around the site when displaying the site on the Your Route tab.',
          'type' => 'string',
          'group' => 'Initial Map View',
          'default' => '0.1',
        ],
        [
          'name' => 'allow_user_assignment',
          'label' => 'Allow users to be assigned to transects',
          'type' => 'boolean',
          'description' => 'Can administrators link users to transects that they are allowed to record at? Requires a multi-value CMS User ID attribute on the locations.',
          'default' => TRUE,
          'required' => FALSE,
          'group' => 'Transects Editor Settings',
        ],
        [
          'name' => 'autocalc_transect_length_attr_id',
          'caption' => 'Location attribute to autocalc transect length',
          'description' => 'Location attribute that stores the total transect length: summed from the lengths of the individual sections.',
          'type' => 'select',
          'table' => 'location_attribute',
          'valueField' => 'id',
          'captionField' => 'caption',
          'group' => 'Transects Editor Settings',
          'required' => FALSE,
        ],
        [
          'name' => 'autocalc_section_length_attr_id',
          'caption' => 'Location attribute to autocalc section length',
          'description' => 'Location attribute that stores the section length, if you want it to be autocalculated from the geometry.',
          'type' => 'select',
          'table' => 'location_attribute',
          'valueField' => 'id',
          'captionField' => 'caption',
          'group' => 'Transects Editor Settings',
          'required' => FALSE,
        ],
        [
          'name' => 'default_section_grid_ref',
          'caption' => 'Default grid ref for a section?',
          'description' => 'Default the grid ref for a section to what?',
          'type' => 'select',
          'lookupValues' => [
            'parent' => 'Same as parent transect',
            'sectionCentroid100' => '100 m grid square covering the centroid of the section',
            'sectionStart100' => '100 m grid square covering the start of the section'
          ],
          'default' => 'parent',
          'group' => 'Transects Editor Settings',
        ], [
          'name' => 'always_show_section_details',
          'caption' => 'Always show the Section Details tab',
          'description' => 'If ticked, then the section details tab is shown allowing the section map reference to be set, even when there are no attributes.',
          'type' => 'checkbox',
          'group' => 'Transects Editor Settings',
          'required' => FALSE,
        ],
        [
          'name' => 'check_location_name_unique',
          'caption' => 'Check location name is unique',
          'description' => 'If checked, then enforces that the given location name is unique within the list of locations that exist for this website and location type.',
          'type' => 'checkbox',
          'group' => 'Transects Editor Settings',
          'required' => FALSE,
        ],
      ]
    );
  }
  /**
   * When a form version is upgraded introducing new parameters, old forms will not get the defaults for the
   * parameters unless the Edit and Save button is clicked. So, apply some defaults to keep those old forms
   * working.
   */
  protected static function getArgDefaults($args) {

    if (!isset($args['route_map_height'])) $args['route_map_height'] = 600;
    if (!isset($args['route_map_buffer'])) $args['route_map_buffer'] = 0.1;
    if (!isset($args['allow_user_assignment'])) $args['allow_user_assignment'] = TRUE;
    if (!isset($args['managerPermission'])) $args['managerPermission'] = '';
    if (!isset($args['branch_assignment_permission'])) $args['branch_assignment_permission'] = '';
    if (!isset($args['always_show_section_details'])) $args['always_show_section_details'] = FALSE;

    return $args;
  }

  protected static function extract_attr(&$attributes, $caption, $unset=TRUE) {
  	$found=FALSE;
  	foreach($attributes as $idx => $attr) {
  	  if (strcasecmp($attr['untranslatedCaption'], $caption)===0) {
  			// found will pick up just the first one
  			if (!$found)
  				$found=$attr;
  			if ($unset)
  				unset($attributes[$idx]);
  			else
  				// don't bother looking further if not unsetting them all
  				break;
  		}
  	}
  	return $found;
  }

  /**
   * Return the generated form output.
   *
   * @param array $args
   *   Form configuration array.
   * @param int $nid
   *   The Drupal node object's ID.
   * @param array $response
   *   When this form is reloading after saving a submission, contains the
   *   response from the service call.
   *
   * @return string
   *   Form HTML.
   */
  public static function get_form($args, $nid, $response=NULL) {
    $checks = self::check_prerequisites();
    $args = self::getArgDefaults($args);
    if ($checks !== TRUE) {
      return '';
    }
    iform_load_helpers(['map_helper']);
    data_entry_helper::add_resource('jquery_form');
    data_entry_helper::add_resource('fancybox');
    self::$ajaxFormUrl = iform_ajaxproxy_url($nid, 'location');
    self::$ajaxFormSampleUrl = iform_ajaxproxy_url($nid, 'sample');
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $typeTerms = [
      empty($args['transect_type_term']) ? 'Transect' : $args['transect_type_term'],
      empty($args['section_type_term']) ? 'Section' : $args['section_type_term'],
    ];
    $settings = [
      'locationTypes' => helper_base::get_termlist_terms($auth, 'indicia:location_types', $typeTerms),
      // ID parameter is id immediately after first save of transect, but
      // location_id when accessed elsewhere.
      'locationId' => $_GET['id'] ?? $_GET['location_id'] ?? NULL,
      'canEditBody' => TRUE,
      'canEditSections' => TRUE, // this is specifically the number of sections: so can't delete or change the attribute value.
      // Allocations of Branch Manager are done by a person holding the managerPermission.
      'canAllocBranch' => $args['managerPermission'] == "" || hostsite_user_has_permission($args['managerPermission']),
      // Allocations of Users are done by a person holding the managerPermission or the allocate Branch Manager.
      // The extra check on this for branch managers is done later.
      'canAllocUser' => $args['managerPermission'] == "" || hostsite_user_has_permission($args['managerPermission']),
    ];
    $settings['attributes'] = data_entry_helper::getAttributes([
        'id' => $settings['locationId'],
        'valuetable' => 'location_attribute_value',
        'attrtable' => 'location_attribute',
        'key' => 'location_id',
        'fieldprefix' => 'locAttr',
        'extraParams'=>$auth['read'],
        'survey_id'=>$args['survey_id'],
        'location_type_id' => $settings['locationTypes'][0]['id'],
        'multiValue' => TRUE
    ]);
    $settings['section_attributes'] = data_entry_helper::getAttributes([
        'valuetable' => 'location_attribute_value',
        'attrtable' => 'location_attribute',
        'key' => 'location_id',
        'fieldprefix' => 'locAttr',
        'extraParams'=>$auth['read'],
        'survey_id'=>$args['survey_id'],
        'location_type_id' => $settings['locationTypes'][1]['id'],
        'multiValue' => TRUE
    ]);
    if ($args['allow_user_assignment']) {
      if (FALSE == ($settings['cmsUserAttr'] = extract_cms_user_attr($settings['attributes'])))
        return 'This form is designed to be used with the CMS User ID attribute setup for locations in the survey, or the "Allow users to be assigned to transects" option unticked.';
      // keep a copy of the cms user ID attribute so we can use it later.
      self::$cmsUserAttrId = $settings['cmsUserAttr']['attributeId'];
    }

    // need to check if branch allocation is active.
    if ($args['branch_assignment_permission'] != '') {
      if (FALSE== ($settings['branchCmsUserAttr'] = self::extract_attr($settings['attributes'], "Branch CMS User ID")))
        return '<br />This form is designed to be used with either<br />1) the Branch CMS User ID attribute setup for locations in the survey, or<br />2) the "Permission name for Branch Manager" option left blank.<br />';
      // keep a copy of the branch cms user ID attribute so we can use it later.
      self::$branchCmsUserAttrId = $settings['branchCmsUserAttr']['attributeId'];
    }

    data_entry_helper::$javascript .= "indiciaData.sections = {};\n";
    data_entry_helper::$javascript .= "indiciaData.insertingSection = false;\n";
    $settings['sections'] = [];
    $settings['numSectionsAttr'] = "";
    $settings['maxSectionCount'] = $args['maxSectionCount'];
    $settings['autocalcSectionLengthAttrId'] = empty($args['autocalc_section_length_attr_id']) ? 0 : $args['autocalc_section_length_attr_id'];
    $settings['autocalcTransectLengthAttrId'] = empty($args['autocalc_transect_length_attr_id']) ? 0 : $args['autocalc_transect_length_attr_id'];
    $settings['defaultSectionGridRef'] = empty($args['default_section_grid_ref']) ? 'parent' : $args['default_section_grid_ref'];
    $sectionIds = [];
    if ($settings['locationId']) {
      data_entry_helper::load_existing_record($auth['read'], 'location', $settings['locationId']);
      $settings['hasAnyWalks'] = count(data_entry_helper::get_population_data([
        'table' => 'sample',
        'extraParams' => $auth['read'] + ['view' => 'detail', 'location_id' => $settings['locationId'], 'limit' => 1, 'deleted' => 'f'],
        'nocache' => TRUE
      ])) > 0;
      // Work out permissions for this user: note that canAllocBranch setting effectively shows if a manager.
      if(!$settings['canAllocBranch']) {
        // Check whether I am a normal user and it is allocated to me, and also if I am a branch manager and it is allocated to me.
        $settings['canEditBody'] = FALSE;
        $settings['canEditSections'] = FALSE;
        if(!$args['allow_user_assignment'] && !$settings['hasAnyWalks']) {
          // when no sites assignments for this client, just allow editing for everyone if no samples recorded.
          $settings['canEditBody'] = TRUE;
          $settings['canEditSections'] = TRUE;
        } else if($args['allow_user_assignment'] &&
            !$settings['hasAnyWalks'] &&
            isset($settings['cmsUserAttr']['default']) &&
            !empty($settings['cmsUserAttr']['default'])) {
          foreach($settings['cmsUserAttr']['default'] as $value) { // multi value
            if($value['default'] == hostsite_get_user_field('id')) { // comparing string against int so no triple equals
              $settings['canEditBody'] = TRUE;
              $settings['canEditSections'] = TRUE;
              break;
            }
          }
        }
        // If a Branch Manager and not a main manager, then can't edit the number of sections
        if($args['branch_assignment_permission'] != '' &&
            hostsite_user_has_permission($args['branch_assignment_permission']) &&
            isset($settings['branchCmsUserAttr']['default']) &&
            !empty($settings['branchCmsUserAttr']['default'])) {
          foreach($settings['branchCmsUserAttr']['default'] as $value) { // now multi value
            if($value['default'] == hostsite_get_user_field('id')) { // comparing string against int so no triple equals
              $settings['canEditBody'] = TRUE;
              $settings['canAllocUser'] = TRUE;
              break;
            }
          }
        }
      } // for an admin user the defaults apply, which will be can do everything.
      // find the number of sections attribute.
      foreach ($settings['attributes'] as $attr) {
        if ($attr['caption']==='No. of sections') {
          $settings['numSectionsAttr'] = $attr['fieldname'];
          for ($i = 1; $i <= $attr['displayValue']; $i++) {
            $settings['sections']["S$i"] = NULL;
          }
          $existingSectionCount = empty($attr['displayValue']) ? 1 : $attr['displayValue'];
          data_entry_helper::$javascript .= "$('#".str_replace(':','\\\\:',$attr['id'])."').attr('min',$existingSectionCount).attr('max',".$args['maxSectionCount'].");\n";
          if (!$settings['canEditSections'])
            data_entry_helper::$javascript .= "$('#".str_replace(':','\\\\:',$attr['id'])."').attr('readonly','readonly').css('color','graytext');\n";
        }
      }
      $sections = data_entry_helper::get_population_data([
        'table' => 'location',
        'extraParams' => $auth['read'] + [
          'view' => 'detail',
          'parent_id' => $settings['locationId'],
          'deleted' => 'f',
          'orderby' => 'id',
          'location_type_id' => $settings['locationTypes'][1]['id'],
        ],
        'nocache' => TRUE,
      ]);
      foreach ($sections as $section) {
        $code = $section['code'];
        data_entry_helper::$javascript .= "indiciaData.sections.$code = {'geom':'".$section['boundary_geom']."','id':'".$section['id']."','sref':'".$section['centroid_sref']."','system':'".$section['centroid_sref_system']."'};\n";
        $settings['sections'][$code] = $section;
        $sectionIds[$section['code']] = $section['id'];
      }
    }
    else {
      // Not an existing site therefore no walks. On initial save, no section data is created.
      foreach($settings['attributes'] as $attr) {
        if ($attr['caption']==='No. of sections') {
          $settings['numSectionsAttr'] = $attr['fieldname'];
          data_entry_helper::$javascript .= "$('#".str_replace(':','\\\\:',$attr['id'])."').attr('min',1).attr('max',".$args['maxSectionCount'].");\n";
        }
      }
      $settings['hasAnyWalks'] = FALSE;
    }
    if ($settings['numSectionsAttr'] === '') {
      for ($i=1; $i<=$settings['maxSectionCount']; $i++) {
        $settings['sections']["S$i"]=NULL;
      }
    }
    $r = '<div id="controls">';
    $headerOptions = ['tabs' => ['#site-details' => lang::get('Site Details')]];
    if ($settings['locationId']) {
      $headerOptions['tabs']['#your-route'] = lang::get('Your Route');
      if ($args['always_show_section_details'] || count($settings['section_attributes']) > 0)
        $headerOptions['tabs']['#section-details'] = lang::get('Section Details');
    }
    if (count($headerOptions['tabs'])) {
      $r .= data_entry_helper::tab_header($headerOptions);
      data_entry_helper::enable_tabs([
          'divId' => 'controls',
          'style' => 'Tabs',
          'progressBar' => isset($args['tabProgress']) && $args['tabProgress']==TRUE
      ]);
    }
    $r .= self::getSiteTab($auth, $args, $settings, $sectionIds);
    if ($settings['locationId']) {
      $r .= self::get_your_route_tab($auth, $args, $settings);
      if ($args['always_show_section_details'] || count($settings['section_attributes']) > 0)
        $r .= self::get_section_details_tab($auth, $args, $settings);
    }
    $r .= '</div>'; // controls
    data_entry_helper::enable_validation('input-form');
    helper_base::addLanguageStringsToJs('sectionedTransectsEditTransect', [
      'duplicateNameWarning' => 'There is already a transect with this name in the system. Please make your transect name unique before saving.',
      'sectionChangeConfirm' => 'Do you wish to save the currently unsaved changes you have made to the Section Details?',
      'sectionDeleteConfirm' => 'Are you sure you wish to delete section {1}?',
      'sectionInsertConfirm' => 'Are you sure you wish to insert a new section after section {1}?',
    ]);

    // Inform JS where to post data to for AJAX form saving.
    data_entry_helper::$indiciaData['ajaxFormPostUrl'] = self::$ajaxFormUrl;
    data_entry_helper::$indiciaData['ajaxFormPostSampleUrl'] = self::$ajaxFormSampleUrl;
    data_entry_helper::$indiciaData['indiciaSvc'] = data_entry_helper::$base_url;
    data_entry_helper::$indiciaData['currentSection'] = '';
    data_entry_helper::$indiciaData['sectionTypeId'] = $settings['locationTypes'][1]['id'];
    data_entry_helper::$indiciaData['numSectionsAttrName'] = $settings['numSectionsAttr'];
    data_entry_helper::$indiciaData['maxSectionCount'] = $settings['maxSectionCount'];
    data_entry_helper::$indiciaData['autocalcTransectLengthAttrId'] = $settings['autocalcTransectLengthAttrId'];
    data_entry_helper::$indiciaData['autocalcSectionLengthAttrId'] = $settings['autocalcSectionLengthAttrId'];
    data_entry_helper::$indiciaData['defaultSectionGridRef'] = $settings['defaultSectionGridRef'];
    data_entry_helper::$indiciaData['checkLocationNameUnique'] = !empty($args['check_location_name_unique']);
    if ($settings['locationId']) {
      data_entry_helper::$javascript .= "selectSection('S1', true);\n";
    }
    return $r;
  }

  protected static function check_prerequisites() {
    // Check required modules installed.
    if (!hostsite_module_exists('iform_ajaxproxy')) {
       hostsite_show_message('This form must be used in Drupal with the Indicia AJAX Proxy module enabled.');
       return FALSE;
    }
    if (!function_exists('iform_ajaxproxy_url')) {
      hostsite_show_message(lang::get('The Indicia AJAX Proxy module must be enabled to use this form. This lets the form save verifications to the '.
          'Indicia Warehouse without having to reload the page.'));
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Retrieve the HTML for the site tab.
   *
   * @param array $auth
   *   Read and write auth tokens.
   * @param array $args
   *   Form arguments.
   * @param array $settings
   *   Settings data.
   * @param array $sectionIds
   *   List of child section IDs, keyed by code.
   *
   * @return string
   *   Tab HTML.
   */
  private static function getSiteTab($auth, $args, $settings, array $sectionIds) {
    $r = '<div id="site-details" class="ui-helper-clearfix">';
    $r .= '<form method="post" id="input-form">';
    $r .= $auth['write'];
    $r .= '<div id="cols" class="ui-helper-clearfix"><div class="left" style="width: 54%">';
    $r .= '<fieldset><legend>' . lang::get('Transect Details') . '</legend>';
    $r .= "<input type=\"hidden\" name=\"website_id\" value=\"$args[website_id]\" />\n";
    $r .= "<input type=\"hidden\" name=\"survey_id\" value=\"$args[survey_id]\" />\n";
    $r .= "<input type=\"hidden\" name=\"location:location_type_id\" value=\"" . $settings['locationTypes'][0]['id'] . "\" />\n";
    if ($settings['locationId']) {
      $r .= "<input type=\"hidden\" name=\"location:id\" id=\"location:id\" value=\"$settings[locationId]\" />\n";
      // Enable detecting changes to the site name.
      $r .= "<input type=\"hidden\" name=\"previous_name\" value=\"" . data_entry_helper::$entity_to_load['location:name'] . "\" />\n";
      // Include section IDs to make name update propogation simpler.
      $sectionIdsJson = htmlspecialchars(json_encode($sectionIds));
      $r .= "<input type=\"hidden\" name=\"section_ids\" value=\"$sectionIdsJson\" />\n";
    }
    // Pass through the group_id if set in URL parameters, so we can save the
    // location against the group.
    if (!empty($_GET['group_id'])) {
      $r .= "<input type=\"hidden\" id=\"group_id\" name=\"group_id\" value=\"" . $_GET['group_id'] . "\" />\n";
    }
    $r .= data_entry_helper::text_input([
      'fieldname' => 'location:name',
      'label' => lang::get('Transect Name'),
      'class' => 'control-width-4 required',
      'disabled' => $settings['canEditBody'] ? '' : ' disabled="disabled" '
    ]);
    if (!$settings['canEditBody']) {
      $r .= '<p>' . lang::get('This site cannot be edited because there are walks recorded on it. Please contact the site administrator if you think there are details which need changing.') . '</p>';
    }
    elseif ($settings['hasAnyWalks']) { // can edit it
      $r .= '<p>' . lang::get('This site has walks recorded on it. Please do not change the site details without considering the impact on the existing data.') . '</p>';
    }
    $list = explode(',', str_replace(' ', '', $args['spatial_systems']));
    foreach ($list as $system) {
      $systems[$system] = lang::get("sref:$system");
    }
    $r .= data_entry_helper::sref_and_system([
      'fieldname' => 'location:centroid_sref',
      'geomFieldname' => 'location:centroid_geom',
      'label' => 'Grid Ref.',
      'systems' => $systems,
      'class' => 'required',
      'helpText' => lang::get('Click on the map to set the central grid reference.'),
      'disabled' => $settings['canEditBody'] ? '' : ' disabled="disabled" '
    ]);
    if ($settings['locationId'] && data_entry_helper::$entity_to_load['location:code'] != '' && data_entry_helper::$entity_to_load['location:code'] != NULL) {
      $r .= data_entry_helper::text_input([
        'fieldname' => 'location:code',
        'label' => lang::get('Site Code'),
        'class' => 'control-width-4',
        'disabled' => ' readonly="readonly" '
      ]);
    }
    else {
      $r .= "<p>" . lang::get('The Site Code will be allocated by the Administrator.') . "</p>";
    }

    // Setup the map options.
    $options = iform_map_get_map_options($args, $auth['read']);
    // Find the form blocks that need to go below the map.
    $bottom = '';
    $bottomBlocks = explode("\n", isset($args['bottom_blocks']) ? $args['bottom_blocks'] : '');
    foreach ($bottomBlocks as $block) {
      $bottom .= get_attribute_html($settings['attributes'], $args, ['extraParams'=>$auth['read'], 'disabled' => $settings['canEditBody'] ? '' : ' disabled="disabled" '], $block);
    }
    // Other blocks to go at the top, next to the map.
    if (isset($args['site_help']) && $args['site_help'] != '') {
      $r .= '<p class="ui-state-highlight page-notice ui-corner-all">' . lang::get($args['site_help']) . '</p>';
    }
    $r .= get_attribute_html($settings['attributes'], $args, ['extraParams' => $auth['read']]);
    $r .= '</fieldset>';
    $r .= "</div>"; // left
    $r .= '<div class="right" style="width: 44%">';
    if (!$settings['locationId']) {
      $help = lang::get('Use the search box to find a nearby town or village, then drag the map to pan and click on the map to set the centre grid reference of the transect. '.
          'Alternatively if you know the grid reference you can enter it in the Grid Ref box on the left.');
      $r .= '<p class="ui-state-highlight page-notice ui-corner-all">' . $help . '</p>';
      $r .= data_entry_helper::georeference_lookup([
        'label' => lang::get('Search for place'),
        'driver' => $args['georefDriver'],
        'georefPreferredArea' => $args['georefPreferredArea'],
        'georefCountry' => $args['georefCountry'],
        'georefLang' => $args['language'],
        'readAuth' => $auth['read'],
      ]);
    }
    if (isset($args['maxPrecision']) && $args['maxPrecision'] != '') {
      $options['clickedSrefPrecisionMax'] = $args['maxPrecision'];
    }
    if (isset($args['minPrecision']) && $args['minPrecision'] != '') {
      $options['clickedSrefPrecisionMin'] = $args['minPrecision'];
    }
    $olOptions = iform_map_get_ol_options($args);
    $options['clickForSpatialRef'] = $settings['canEditBody'];
    $r .= map_helper::map_panel($options, $olOptions);
    $r .= '</div></div>'; // right
    if (!empty($bottom)) {
      $r .= $bottom;
    }
    if ($args['branch_assignment_permission'] != '') {
      if ($settings['canAllocBranch'] || $settings['locationId']) {
        $r .= self::get_branch_assignment_control($auth['read'], $settings['branchCmsUserAttr'], $args, $settings);
      }
    }
    if ($args['allow_user_assignment']) {
      if ($settings['canAllocUser']) {
        $r .= self::get_user_assignment_control($auth['read'], $settings['cmsUserAttr'], $args);
      }
      elseif (!$settings['locationId']) {
        // For a new record, we need to link the current user to the location if they are not admin.
        $r .= '<input type="hidden" name="locAttr:' . self::$cmsUserAttrId . '" value="' . hostsite_get_user_field('id') . '">';
      }
    }
    if ($settings['canEditBody']) {
      $r .= '<button type="submit" class="indicia-button right">' . lang::get('Save') . '</button>';
    }

    if ($settings['canEditBody'] && $settings['locationId']) {
      $r .= '<button type="button" class="indicia-button right" id="delete-transect">' . lang::get('Delete') . '</button>';
    }
    $r .= '</form>';
    $r .= '</div>'; // site-details
    // This must go after the map panel, so it has created its toolbar.
    data_entry_helper::$onload_javascript .= "$('#current-section').change(selectSection);\n";
    if ($settings['canEditBody'] && $settings['locationId']) {
      $sectionIDs = [];
      foreach($settings['sections'] as $code => $section)
        $sectionIDs[] = $section['id'];
      data_entry_helper::$javascript .= "
deleteSurvey = function(){
  if(confirm(\"" . lang::get('Are you sure you wish to delete this location?') . "\")){
    deleteSections([" . implode(',', $sectionIDs) . "]);
    $('#delete-transect').html('Deleting Transect');
    deleteLocation(" . $settings['locationId'] . ");
    $('#delete-transect').html('Done');
    window.location='" . hostsite_get_url($args['sites_list_path']) . "';
  };
};
$('#delete-transect').click(deleteSurvey);
";
    }
    return $r;
  }

  protected static function get_your_route_tab($auth, $args, $settings) {
    $r = '<div id="your-route" class="ui-helper-clearfix">';
    $olOptions = iform_map_get_ol_options($args);
    $options = iform_map_get_map_options($args, $auth['read']);
    $options['divId'] = 'route-map';
    $options['toolbarDiv'] = 'top';
    $options['tabDiv']='your-route';
    $options['gridRefHint']=TRUE;
    if ($settings['canEditBody']){
      $options['toolbarPrefix'] = self::section_selector($settings, 'section-select-route');
      if($settings['canEditSections'] && count($settings['sections'])>1 && $settings['numSectionsAttr'] != "") // do not allow deletion of last section, or if the is no section number attribute
        $options['toolbarSuffix'] = '<input type="button" value="'.lang::get('Remove Section').'" class="remove-section form-button right" title="'.lang::get('Completely remove the highlighted section. The total number of sections will be reduced by one. The form will be reloaded after the section is deleted.').'">';
      else $options['toolbarSuffix'] = '';
      $options['toolbarSuffix'] .= '<input type="button" value="'.lang::get('Erase Route').'" class="erase-route form-button right" title="'.lang::get('If the Draw Line control is active, this will erase each drawn point one at a time. If not active, then this will erase the whole highlighted route. This keeps the Section, allowing you to redraw the route for it.').'">';
      if($settings['canEditSections'] && count($settings['sections'])<$args['maxSectionCount'] && $settings['numSectionsAttr'] != "") // do not allow insertion of section if it exceeds max number, or if the is no section number attribute
        $options['toolbarSuffix'] .= '<input type="button" value="'.lang::get('Insert Section').'" class="insert-section form-button right" title="'.lang::get('This inserts an extra section after the currently selected section. All subsequent sections are renumbered, increasing by one. All associated occurrences are kept with the moved sections. This can be used to facilitate the splitting of this section.').'">';
      // also let the user click on a feature to select it. The highlighter just makes it easier to select one.
      // these controls are not present in read-only mode: all you can do is look at the map.
      $options['standardControls'][] = 'selectFeature';
      $options['standardControls'][] = 'hoverFeatureHighlight';
      $options['standardControls'][] = 'drawLine';
      $options['standardControls'][] = 'modifyFeature';
      $options['switchOffSrefRetrigger'] = TRUE;
      $help = lang::get('Select a section from the list then click on the map to draw the route and double click to finish. '.
        'You can also select a section using the query tool to click on the section lines. If you make a mistake in the middle '.
        'of drawing a route, then you can use the Erase Route button to remove the last point drawn. After a route has been '.
        'completed use the Modify a feature tool to correct the line shape (either by dragging one of the circles along the '.
        'line to form the correct shape, or by placing the mouse over a circle and pressing the Delete button on your keyboard '.
        'to remove that point). Alternatively you could just redraw the line - this new line will then replace the old one '.
        'completely. If you are not in the middle of drawing a line, the Erase Route button will erase the whole route for the '.
        'currently selected section.').
        ($settings['numSectionsAttr'] != "" ?
           '<br />'.(count($settings['sections'])>1 ?
             lang::get('The Remove Section button will remove the section completely, reducing the number of sections by one.').' '
             : '').
           lang::get('To increase the number of sections, return to the Site Details tab, and increase the value in the No. of sections field there.')
           : '');
      $r .= '<p class="ui-state-highlight page-notice ui-corner-all">'.$help.'</p>';
    }
    $options['clickForSpatialRef'] = FALSE;
    // override the opacity so the parent square does not appear filled in.
    $options['fillOpacity'] = 0;
    // override the map height and buffer size, which are specific to this map.
    $options['height'] = $args['route_map_height'];
    $options['maxZoomBuffer'] = $args['route_map_buffer'];

    $r .= map_helper::map_panel($options, $olOptions);
    if(count($settings['section_attributes']) == 0)
      $r .= '<button class="indicia-button right" type="button" title="'.
            lang::get('Returns to My Sites page. Any changes to sections carried out on this page (including creating new ones) are saved to the database as they are done, but changes to the Site Details must be saved using the Save button on that tab.').
            '" onclick="window.location.href=\'' . hostsite_get_url($args['redirect_on_success']) . '\'">'.lang::get('Return to My Sites').'</button>';
    $r .= '</div>';
    return $r;
  }

  protected static function get_section_details_tab($auth, $args, $settings) {
    $r = '<div id="section-details" class="ui-helper-clearfix">';
    $r .= '<form method="post" id="section-form" action="'.self::$ajaxFormUrl.'">';
    $r .= '<fieldset><legend>'.lang::get('Section Details').'</legend>';
    // Output a selector for the current section.
    $r .= self::section_selector($settings, 'section-select')."<br/>";
    if ($settings['canEditBody']) {
      $r .= "<input type=\"hidden\" name=\"location:id\" value=\"\" id=\"section-location-id\" />\n";
      $r .= '<input type="hidden" name="website_id" value="'.$args['website_id']."\" />\n";
    }
    // for the SRef, we want to be able to edit the sref, but just display the system. Do not want the Geometry.
    $r .= '<label for="imp-sref">Section Grid Ref.:</label><input type="text" value="" class="required" name="location:centroid_sref" id="section-location-sref"><span class="deh-required">*</span>';
    // for the system we need to translate the system: easiest way is to have a disabled select plus a hidden field.
    $systems = [];
    $list = explode(',', str_replace(' ', '', $args['spatial_systems']));
    foreach($list as $system) {
      $systems[$system] = lang::get("sref:$system");
    }
    $options = [
      'fieldname' => '',
      'systems' => $systems,
      'disabled' => ' disabled="disabled"',
      'id' => 'section-location-system-select',
    ];
    // Output the hidden system control
    $r .= '<input type="hidden" id="section-location-system" name="location:centroid_sref_system" value="" />';
    if(count($list)>1)
    	$r .= data_entry_helper::sref_system_select($options);
    // force a blank centroid, so that the Warehouse will recalculate it from the boundary
    //$r .= "<input type=\"hidden\" name=\"location:centroid_geom\" value=\"\" />\n";

    $blockOptions = [];
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

    $r .= get_attribute_html($settings['section_attributes'], $args, ['extraParams'=>$auth['read'], 'disabled' => $settings['canEditBody'] ? '' : ' disabled="disabled" '], NULL, $blockOptions);
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

  /**
   * Build a row of buttons for selecting the route.
   */
  protected static function section_selector($settings, $id) {
    $sectionArr = [];
    foreach ($settings['sections'] as $code => $section) {
      $sectionArr[$code] = $code;
    }
    $selector = '<label for="'.$id.'">' . lang::get('Select section') . ':</label><ol id="' . $id . '" class="section-select">';
    foreach ($sectionArr as $key => $value) {
      $classes = [];
      if ($key === 'S1') {
        $classes[] = 'selected';
      }
      if (!isset($settings['sections'][$key])) {
        $classes[] = 'missing';
      }
      $class = count($classes) ? ' class="'.implode(' ', $classes).'"' : '';
      $selector .= "<li id=\"$id-$value\"$class>$value</li>";
    }
    $selector .= '</ol>';
    return $selector;
  }

  /**
   * Returns the Drupal list of users.
   *
   * @return array
   *   Associative array of uid and user names.
   */
  private static function getUserList() {
    $users = [];
    $result = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->getQuery()
      ->sort('name', 'ASC')
      ->accessCheck(FALSE)
      ->execute();
    $userList = \Drupal\user\Entity\User::loadMultiple($result);
    foreach ($userList as $user) {
      if ($user->id() != 0) {
        $users[$user->id()] = $user->getDisplayName();
      }
    }
    return $users;
  }

  /**
   * If the user has permissions, then display a control so that they can specify the list of users associated with this site.
   */
  protected static function get_user_assignment_control($readAuth, $cmsUserAttr, $args) {
    if(self::$cmsUserList == NULL) {
      $users = self::getUserList();
      self::$cmsUserList = $users;
  	} else $users= self::$cmsUserList;
    $r = '<fieldset id="alloc-recorders"><legend>'.lang::get('Allocate recorders to the site').'</legend>';
    $r .= data_entry_helper::select([
      'label' => lang::get('Select user'),
      'fieldname' => 'cmsUserId',
      'lookupValues' => $users,
      'afterControl' => '<button id="add-user" type="button">'.lang::get('Add').'</button>'
    ]);
    $r .= '<table id="user-list" style="width: auto">';
    $rows = '';
    // cmsUserAttr needs to be multivalue
    if (isset($cmsUserAttr['default']) && !empty($cmsUserAttr['default'])) {
      foreach($cmsUserAttr['default'] as $value) {
        $rows .= '<tr><td id="user-'.$value['default'].'"><input type="hidden" name="'.$value['fieldname'].'" '.
            'value="'.$value['default'].'"/>'.(isset($users[$value['default']]) ? $users[$value['default']] : 'CMS User '.$value['default']).
            '</td><td><div class="ui-state-default ui-corner-all"><span class="remove-user ui-icon ui-icon-circle-close" title="Remove user '.(isset($users[$value['default']]) ? $users[$value['default']] : 'CMS User '.$value['default']).'"></span></div></td></tr>';
        }
    }
    if (empty($rows))
      $rows = '<tr><td colspan="2"></td></tr>';
    $r .= "$rows</table>\n";
    $r .= '</fieldset>';
    if ($args['allow_user_assignment']) {
      // tell the javascript which attr to save the user ID into
      data_entry_helper::$javascript .= "indiciaData.locCmsUsrAttr = " . self::$cmsUserAttrId . ";\n";
    }
    return $r;
  }

  protected static function get_branch_assignment_control($readAuth, $branchCmsUserAttr, $args, $settings) {
    if (!$branchCmsUserAttr) {
      // No attribute so don't display.
      return '<span style="display:none;">No branch location attribute</span>';
    }
    if (self::$cmsUserList == NULL) {
      $users = self::getUserList();
      self::$cmsUserList = $users;
    } else {
      $users= self::$cmsUserList;
    }

    // next reduce the list to branch users
    if($settings['canAllocBranch']){ // only check the users permissions if can change value - for performance reasons.
      $new_users = [];
      foreach ($users as $uid=>$name){
        if (hostsite_user_has_permission($args['branch_assignment_permission'], $uid))
          $new_users[$uid]=$name;
      }
      $users = $new_users;
    }

    $r = '<fieldset id="alloc-branch"><legend>' . lang::get('Site Branch Allocation') . '</legend>';
    if($settings['canAllocBranch']) {
      $r .= data_entry_helper::select([
        'label' => lang::get('Select Branch Manager'),
        'fieldname' => 'branchCmsUserId',
        'lookupValues' => $users,
        'afterControl' => '<button id="add-branch-coord" type="button">'.lang::get('Add').'</button>'
      ]);
      // tell the javascript which attr to save the user ID into
      data_entry_helper::$javascript .= "indiciaData.locBranchCmsUsrAttr = " . self::$branchCmsUserAttrId . ";\n";
    }
    $r .= '<table id="branch-coord-list" style="width: auto">';
    $rows = '';
    // cmsUserAttr needs to be multivalue
    if (isset($branchCmsUserAttr['default']) && !empty($branchCmsUserAttr['default'])) {
      foreach($branchCmsUserAttr['default'] as $value) {
        if($settings['canAllocBranch'])
          $rows .= '<tr><td id="branch-coord-'.$value['default'].'"><input type="hidden" name="'.$value['fieldname'].'" '.
            'value="'.$value['default'].'"/>'.(isset($users[$value['default']]) ? $users[$value['default']] : 'CMS User '.$value['default']).
            '</td><td><div class="ui-state-default ui-corner-all"><span class="remove-user ui-icon ui-icon-circle-close" title="Remove user '.(isset($users[$value['default']]) ? $users[$value['default']] : 'CMS User '.$value['default']).'"></span></div></td></tr>';
        else
          $rows .= '<tr><td>'.$users[$value['default']].'</td><td></td></tr>';
      }
    }
    if (empty($rows))
      $rows = '<tr><td colspan="2"></td></tr>';
    $r .= "$rows</table>\n";
    $r .= '</fieldset>';

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
      [
        'model' => 'location'
      ]
    );
    // On first save of a new transect, link it to the website.
    if (empty($values['location:id'])) {
      $s['subModels'] = [
        [
          'fkId' => 'location_id',
          'model' => [
            'id' => 'locations_website',
            'fields' => [
              'website_id' => $args['website_id']
            ],
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
    elseif (isset($values['previous_name']) && $values['previous_name'] !== $values['location:name'] && !empty($values['section_ids'])) {
      // Not the first save and the transect name has been updated. So, update
      // the section names to match the new transect name.
      $sectionIds = json_decode($values['section_ids'], TRUE);
      foreach ($sectionIds as $code => $sectionId) {
        // Add a sub-model for each section just to update the name.
        $s['subModels'][] = [
          'fkId' => 'parent_id',
          'model' => [
            'id' => 'location',
            'fields' => [
              'id' => $sectionId,
              'name' => trim($values['location:name']) . ' - ' . $code,
            ],
          ],
        ];
      }
    }
    return $s;
  }

  /**
   * After saving a new transect, reload the transect so that the user can continue to save the sections.
   */
  public static function get_redirect_on_success($values, $args) {
    if (!isset($values['location:id'])) {
      return hostsite_get_current_page_path() . '#your-route';
    }
  }

}
