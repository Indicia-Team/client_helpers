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

/**
 * Displays the details of a single location.
 *
 * Takes an location_id in the URL and displays the following using a
 * configurable page template:
 * * Location details including custom attributes.
 * * A map including geometry.
 * * Any photos associated with the location.
 * * Subsite information.
 */

require_once 'includes/BaseDynamicDetails.php';
require_once 'includes/report.php';
require_once 'includes/groups.php';

class iform_location_details extends BaseDynamicDetails {

  /**
   * Details of the currently loaded location.
   *
   * @var array
   */
  protected static $location;

  /**
   * Disable form element wrapped around output.
   *
   * @return bool
   *   Always FALSE.
   */
  protected static function isDataEntryForm() {
    return FALSE;
  }

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_location_details_definition() {
    return [
      'title' => 'View details of a location',
      'category' => 'Utilities',
      'description' => 'A summary view of a location with commenting capability. Pass a parameter in the URL called location_id to define which occurrence to show.',
      'helpLink' => 'https://indicia-docs.readthedocs.io/en/latest/site-building/iform/prebuilt-forms/location-details.html',
      'supportsGroups' => TRUE,
      'recommended' => TRUE,
    ];
  }

  /**
   * Return an array of parameters for the edit tab.
   *
   * @return array
   *   The parameters for the form.
   */
  public static function get_parameters() {
    $retVal = array_merge(
      iform_map_get_map_parameters(),
      [
        [
          'name' => 'interface',
          'caption' => 'Interface Style Option',
          'description' => 'Choose the style of user interface, either dividing the form up onto separate tabs, wizard pages or having all controls on a single page.',
          'type' => 'select',
          'options' => [
            'tabs' => 'Tabs',
            'wizard' => 'Wizard',
            'one_page' => 'All One Page',
          ],
          'default' => 'one_page',
          'group' => 'User Interface',
        ],
        // List of fields to hide in the Record Details section.
        [
          'name' => 'fields',
          'caption' => 'Fields to include or exclude',
          'description' => 'List of data fields to hide, one per line. ' .
              'Type in the field name as seen exactly in the Record Details section. For custom attributes you should use the system function values ' .
              'to filter instead of the caption if defined below.',
          'type' => 'textarea',
          'default' => '',
          'group' => 'Fields for location details',
        ],
        [
          'name' => 'operator',
          'caption' => 'Include or exclude',
          'description' => "Do you want to include only the list of fields you've defined, or exclude them?",
          'type' => 'select',
          'options' => [
            'in' => 'Include',
            'not in' => 'Exclude',
          ],
          'default' => 'not in',
          'group' => 'Fields for location details',
        ],
        // Allows the user to define how the page will be displayed.
        [
          'name' => 'structure',
          'caption' => 'Form Structure',
          'description' => 'Define the structure of the form. Each component must be placed on a new line. <br/>' .
            'The following types of component can be specified. <br/>' .
            '<strong>[control name]</strong> indicates a predefined control is to be added to the form with the following predefined controls available: <br/>' .
                '&nbsp;&nbsp;<strong>[locationdetails]</strong> - displays information relating to the location. Set @fieldsToExcludeIfLoggedOut to an array of field names to skip for anonymous users.<br/>' .
                '&nbsp;&nbsp;<strong>[buttons]</strong> - outputs a row of edit and explore buttons. Use the @buttons option to change the list of buttons to output ' .
                'by setting this to an array, e.g. ["edit"] will output just the edit button, ["explore"] outputs just the explore button, ["edit","record"] outputs an edit and record button. ' .
                'The edit button is automatically skipped if the user does not have rights to edit the record.<br/>' .
                "&nbsp;&nbsp;<strong>[photos]</strong> - photos associated with the occurrence<br/>" .
                "&nbsp;&nbsp;<strong>[map]</strong> - a map that links to the spatial reference and location<br/>" .
            "<strong>=tab/page name=</strong> is used to specify the name of a tab or wizard page (alpha-numeric characters only). " .
            "If the page interface type is set to one page, then each tab/page name is displayed as a seperate section on the page. " .
            "Note that in one page mode, the tab/page names are not displayed on the screen.<br/>" .
            "<strong>|</strong> is used to split a tab/page/section into two columns, place a [control name] on the previous line and following line to split.<br/>",
          'type' => 'textarea',
          'default' =>
'=Location Details and Comments=

[locationdetails]

|

[buttons]

[map]

=Photos=

[photos]',
          'group' => 'User Interface',
        ],
        [
          'name' => 'default_input_form',
          'caption' => 'Default input form path',
          'description' => 'Default path to use to the edit form for old records which did not have their input form recorded in the database. Specify the path to a general purpose list entry form.',
          'type' => 'text_input',
          'group' => 'Path configuration',
        ],
        [
          'name' => 'explore_url',
          'caption' => 'Explore URL',
          'description' => 'When you click on the Explore this species\' records button you are taken to this URL. Use {rootfolder} as a replacement token for the site\'s root URL.',
          'type' => 'string',
          'required' => FALSE,
          'default' => '',
          'group' => 'Path configuration',
        ],
        [
          'name' => 'explore_param_name',
          'caption' => 'Explore Parameter Name',
          'description' => 'Name of the parameter added to the Explore URL to pass through the taxon_meaning_id of the species being explored. ' .
            'The default provided (filter-taxon_meaning_list) is correct if your report uses the standard parameters configuration.',
          'type' => 'string',
          'required' => FALSE,
          'default' => '',
          'group' => 'Path configuration',
        ],
        [
          'name' => 'sharing',
          'caption' => 'Record sharing mode',
          'description' => 'Identify the task this page is being used for, which determines the websites that will share records for use here.',
          'type' => 'select',
          'options' => [
            'reporting' => 'Reporting',
            'peer_review' => 'Peer review',
            'verification' => 'Verification',
            'data_flow' => 'Data flow',
            'moderation' => 'Moderation',
            'editing' => 'Editing',
            'me' => 'My records only',
          ],
          'default' => 'reporting',
        ],
      ]
    );
    return $retVal;
  }

  public static function get_form($args, $nid) {
    if (empty($_GET['location_id'])) {
      return 'This form requires an location_id parameter in the URL.';
    }
    if (!preg_match('/^\d+$/', trim($_GET['location_id']))) {
      return 'The location_id parameter in the URL must be a valid location ID.';
    }
    iform_load_helpers(['report_helper']);
    data_entry_helper::$indiciaData['username'] = hostsite_get_user_field('name');
    data_entry_helper::$indiciaData['ajaxFormPostUrl'] = iform_ajaxproxy_url(NULL, 'occurrence') . "&sharing=$args[sharing]";
    $conn = iform_get_connection_details($nid);
    $readAuth = data_entry_helper::get_read_auth($conn['website_id'], $conn['password']);
    self::loadLocation($readAuth, $args);
    return parent::get_form($args, $nid);
  }

  /**
   * A set of buttons for navigating from the location details.
   *
   * Options include an edit button (only shown for the location owner) and
   * explore link.
   *
   * @param array $auth
   *   Read authorisation array.
   * @param array $args
   *   Form options.
   * @param string $tabalias
   *   The alias of the tab this appears on.
   * @param array $options
   *   Options configured for this control. Specify the following options:
   *   * buttons - array containing 'edit' to include the edit button,
   *     'explore' to include an explore link or 'record' to include a link to
   *     a recording form for the site. Other, options may be added in future.
   *   * enterRecordsPath - The 'record' button requires an option
   *     @enterRecordsPath set to the path of a form page for entering a list
   *     of records at this location. The form should use the
   *     [location url param] control to allow it to use the location_id
   *     parameter passed to the form. Defaults to edit and explore buttons.
   *   * classes - associative array of each button name (edit, explore or
   *     record), with the value being the class to apply to the button if
   *     overriding the default.
   *
   * @return string
   *   HTML for the buttons.
   */
  protected static function get_control_buttons(array $auth, array $args, $tabalias, array $options) {
    $options = array_merge([
      'buttons' => [
        'edit',
        'explore',
      ],
      'classes' => [],
      'title' => FALSE,
    ], $options);
    $buttons = [];
    foreach ($options['buttons'] as $button) {
      if ($button === 'edit') {
        $buttons[] = self::buttonsEdit($args, $options);
      }
      elseif ($button === 'explore') {
        $buttons[] = self::buttonsExplore($args);
      }
      elseif ($button === 'record') {
        $buttons[] = self::buttonsRecord($options);
      }
      else {
        throw new exception("Unknown button $button");
      }
    }
    return self::controlContainer('Buttons', implode(' ', $buttons), $options);
  }

  /**
   * Retrieve the HTML required for an edit location button.
   *
   * @param array $args
   *   Form options.
   * @param array $options
   *   Options configured for this control as per get_control_buttons().
   *
   * @return string
   *   HTML for the button.
   */
  protected static function buttonsEdit(array $args, array $options) {
    global $indicia_templates;
    if (!$args['default_input_form']) {
      throw new exception('Please set the default input form path setting before using the [edit button] control');
    }
    $location = self::$location;
    if (($user_id = hostsite_get_user_field('indicia_user_id')) && $user_id == $location['created_by_id']
        && $args['website_id'] == $location['website_id']) {
      $rootFolder = data_entry_helper::getRootFolder(TRUE);
      $paramJoin = strpos($rootFolder, '?') === FALSE ? '?' : '&';
      $url = "$rootFolder$args[default_input_form]{$paramJoin}location_id=$location[location_id]";
      $class = isset($options['classes']['edit']) ? $options['classes']['edit'] : $indicia_templates['buttonDefaultClass'];
      return "<a class=\"$class\" href=\"$url\">" . lang::get('Edit this location') . '</a>';
    }
    // User does not have rights to edit the location.
    return '';
  }

  /**
   * Retrieve the HTML required for an explore records in the location button.
   *
   * @param array $args
   *   Form options.
   *
   * @return string
   *   HTML for the button.
   */
  protected static function buttonsExplore(array $args) {
    global $indicia_templates;
    if (!empty($args['explore_url']) && !empty($args['explore_param_name'])) {
      $url = $args['explore_url'];
      if (strcasecmp(substr($url, 0, 12), '{rootfolder}') !== 0 && strcasecmp(substr($url, 0, 4), 'http') !== 0) {
        $url = '{rootFolder}' . $url;
      }
      $rootFolder = data_entry_helper::getRootFolder(TRUE);
      $url = str_replace('{rootFolder}', $rootFolder, $url);
      $url .= (strpos($url, '?') === FALSE) ? '?' : '&';
      $url .= "$args[explore_param_name]=" . self::$location['location_id'];
      $class = isset($options['classes']['explore']) ? $options['classes']['explore'] : $indicia_templates['buttonDefaultClass'];
      $r = "<a class=\"$class\" href=\"$url\">" . lang::get('Explore records in ', self::$location['name']) . '</a>';
    }
    else {
      throw new exception('The page has been setup to use an explore records button, but an "Explore URL" or ' .
          '"Explore Parameter Name" has not been specified.');
    }
    return $r;
  }

  /**
   * Retrieve the HTML for an enter list of records for location button.
   *
   * @param array $options
   *   Options configured for this control as per get_control_buttons().
   *
   * @return string
   *   HTML for the button.
   */
  protected static function buttonsRecord(array $options) {
    global $indicia_templates;
    $location = self::$location;
    if (!empty($options['enterRecordsPath'])) {
      $class = isset($options['classes']['record']) ? $options['classes']['record'] : $indicia_templates['buttonDefaultClass'];
      return "<a class=\"$class\" href=\"$options[enterRecordsPath]?location_id=$location[location_id]\">" . lang::get('Enter observations at this location') . '</a>';
    }
    else {
      throw new exception('The page has been setup to use a record button, but the @enterRecordsPath option is missing.');
    }
  }

  protected static function get_control_locationdetails($auth, $args, $tabalias, $options) {
    global $indicia_templates;
    $options = array_merge([
      'dataSource' => 'reports_for_prebuilt_forms/location_details/location_data_attributes_with_hiddens',
      'fieldsToExcludeIfLoggedOut' => [],
      'outputFormatting' => FALSE,
      'title' => TRUE,
    ], $options);
    $fieldsToExcludeIfLoggedOut = array_map('strtolower', $options['fieldsToExcludeIfLoggedOut']);
    $loggedIn = hostsite_get_user_field('id') !== 0;
    $fields = helper_base::explode_lines($args['fields']);
    $fieldsLower = helper_base::explode_lines(strtolower($args['fields']));
    // Draw the Record Details, but only if they aren't requested as hidden by
    // the administrator.
    $test = $args['operator'] === 'in';
    $availableFields = [
      'location_id' => lang::get('Location ID'),
      'location_external_key' => lang::get('Location external key'),
      'name' => lang::get('Location name'),
      'centroid_sref' => lang::get('Centre grid ref'),
      'comment' => lang::get('Location comment'),
      'location_type' => lang::get('Location type'),
    ];
    $details_report = '';
    $details_report .= '<div class="location-details-fields ui-helper-clearfix">';
    hostsite_set_page_title(self::$location['name']);
    foreach ($availableFields as $field => $caption) {
      // Skip some fields if logged out.
      if (!$loggedIn && in_array(strtolower($caption), $fieldsToExcludeIfLoggedOut)) {
        continue;
      }
      if ($test === in_array(strtolower($caption), $fieldsLower) && !empty(self::$location[$field])) {
        $value = lang::get(self::$location[$field]);
        $details_report .= str_replace(
          ['{caption}', '{value}', '{class}'],
          [$caption, $value, ''],
          $indicia_templates['dataValue']
        );
      }
    }
    $created = date('jS F Y \a\t H:i', strtotime(self::$location['created_on']));
    $updated = date('jS F Y \a\t H:i', strtotime(self::$location['updated_on']));
    $dateInfo = lang::get('Entered on {1}', $created);
    if ($created !== $updated) {
      $dateInfo .= lang::get(' and last updated on {1}', $updated);
    }
    if ($test === in_array('submission date', $fieldsLower)) {
      $details_report .= str_replace(['{caption}', '{value}', '{class}'],
          [lang::get('Submission date'), $dateInfo, ''], $indicia_templates['dataValue']);
    }
    $details_report .= '</div>';

    // Draw any custom attributes.
    $attrs_report = report_helper::freeform_report([
      'readAuth' => $auth['read'],
      'class' => 'location-details-fields ui-helper-clearfix',
      'dataSource' => $options['dataSource'],
      'bands' => [['content' => str_replace(['{class}'], '', $indicia_templates['dataValue'])]],
      'extraParams' => [
        'location_id' => $_GET['location_id'],
        // The SQL needs to take a set of the hidden fields, so this needs to
        // be converted from an array.
        'attrs' => strtolower(self::convertArrayToSet($fields)),
        'operator' => $args['operator'],
        'sharing' => $args['sharing'],
        'language' => iform_lang_iso_639_2(hostsite_get_user_field('language')),
        'output_formatting' => $options['outputFormatting'] ? 't' : 'f',
      ],
    ]);
    $html = '<dl class="dl-horizontal">';
    $html .= $details_report;
    if (isset($attrs_report)) {
      $html .= $attrs_report;
    }
    $html .= '</dl>';
    return self::controlContainer('Location details', $html, $options);
  }

  /**
   * A control for outputting a block containing a single attribute's value.
   *
   * Provides more layout control than the list of attribute values and other
   * details provided by the location details control.
   *
   * Options include:
   * * format - control formatting of the value. Default is "text". Set to
   *   "complex_attr_grid" to output tabular data created by a multi-value
   *   attribute using the complex_attr_grid control type.
   * * ifEmpty - Behaviour when no data present. Default is "hide", but can be
   *   set to text which will be output in place of the value when there is no
   *   data value.
   * * location_attribute_id - required. ID of the attribute to output the
   *   value for.
   * * outputFormatting - default false. Set to true to enable auto-formatting
   *   of HTML links and line-feeds.
   * * title - default true, which shows the attribute's caption as a block
   *   title. Set to a string to override the title, or false to hide it.
   */
  protected static function get_control_singleattribute($auth, $args, $tabalias, $options) {
    return self::getControlSingleattribute('location', $auth, $args, $options);
  }

  /**
   * Render the Map section of the page.
   *
   * @return string
   *   The output map panel.
   */
  protected static function get_control_map($auth, $args, $tabalias, $options) {
    iform_load_helpers(['map_helper']);
    $options = array_merge(
      iform_map_get_map_options($args, $auth['read']),
      [
        'clickForSpatialRef' => FALSE,
        'title' => TRUE,
      ],
      $options
    );
    if (isset(self::$location['boundary_geom'])) {
      $options['initialFeatureWkt'] = self::$location['boundary_geom'];
    }
    elseif (isset(self::$location['centroid_geom'])) {
      $options['initialFeatureWkt'] = self::$location['centroid_geom'];
    }

    if ($tabalias) {
      $options['tabDiv'] = $tabalias;
    }
    $olOptions = iform_map_get_ol_options($args);

    if (!isset($options['standardControls'])) {
      $options['standardControls'] = ['layerSwitcher', 'panZoom'];
    }
    return self::controlContainer('Map', map_helper::map_panel($options, $olOptions), $options);
  }

  /**
   * Render Photos section of the page.
   *
   * Options include:
   * * @title - block title.
   * * @itemsPerPage - count of images to load.
   * * @imageSize, e.g. thumb or med(ium).
   *
   * @return string
   *   The output report grid.
   */
  protected static function get_control_photos($auth, $args, $tabalias, $options) {
    iform_load_helpers(['data_entry_helper']);
    $options = array_merge([
      'title' => TRUE,
    ], $options);
    $settings = [
      'table' => 'location_medium',
      'key' => 'location_id',
      'value' => self::$location['location_id'],
    ];
    data_entry_helper::add_resource('fancybox');
    require_once 'includes/report.php';
    $options = array_merge([
      'itemsPerPage' => 20,
      'imageSize' => 'thumb',
      'class' => 'media-gallery',
    ], $options);
    $extraParams = $auth['read'] + [
      'sharing' => $args['sharing'],
      'limit' => $options['itemsPerPage'],
    ];
    $extraParams[$settings['key']] = $settings['value'];
    $media = data_entry_helper::get_population_data([
      'table' => $settings['table'],
      'extraParams' => $extraParams,
    ]);
    $html = <<<HTML
  <div class="$options[class]">

HTML;
    if (empty($media)) {
      $html .= '<p>' . lang::get('No photos or media files available') . '</p>';
    }
    else {
      if (isset($options['helpText'])) {
        $html .= '<p>' . $options['helpText'] . '</p>';
      }
      $html .= '<ul>';
      $firstImage = TRUE;
      foreach ($media as $medium) {
        if ($firstImage && substr($medium['media_type'], 0, 6) === 'Image:') {
          // First image can be flagged as the main content image. Used for FB OpenGraph for example.
          global $iform_page_metadata;
          if (!isset($iform_page_metadata)) {
            $iform_page_metadata = [];
          }
          $imageFolder = data_entry_helper::get_uploaded_image_folder();
          $iform_page_metadata['image'] = "$imageFolder$medium[path]";
          $firstImage = FALSE;
        }
        $html .= iform_report_get_gallery_item('occurrence', $medium, $options['imageSize']);
      }
      $html .= '</ul>';
    }
    $html .= '</div>';
    return self::controlContainer('Photos and media', $html, $options);
  }

  /**
   * A control for rendering a Drupal block.
   *
   * Options are:
   * * @title - output a block title.
   * * @module - machine name of the module providing the block.
   * * @block - machine name of the block.
   */
  protected static function get_control_block($auth, $args, $tabalias, $options) {
    $options = array_merge([
      'title' => FALSE,
    ], $options);
    if ($options['module'] === 'addtoany') {
      report_helper::$javascript .= "$('.a2a_kit').attr('data-a2a-url', window.location.href);\n";
      $title = str_replace("'", "\'", 'Check out ' . self::$location['name']);
      report_helper::$javascript .= "$('.a2a_kit').attr('data-a2a-title', '$title');\n";
    }
    $html = '';
    if (function_exists('module_invoke')) {
      $block = module_invoke($options['module'], $options['hook'], $options['args']);
      $html .= render($block['content']);
    }
    else {
      $block_manager = \Drupal::service('plugin.manager.block');
      $plugin_block = $block_manager->createInstance($options['block'], empty($options['args']) ? [] : $options['args']);
      $render = $plugin_block->build();
      $renderer = \Drupal::service('renderer');
      $html .= $renderer->render($render);
    }
    return self::controlContainer($options['module'], $html, $options);
  }

  /**
   * Render Photos section of the page.
   *
   * Options include:
   * * @title - default to true. Set to a string to override the title, or
   *   false to remove it.
   * * @addChildrenEditFormPaths - Allows addition of buttons for adding a
   *   child site. A JSON object where the property names are button labels
   *   and the values are paths, e.g.:
   *   ```
   *   @addChildrenEditFormPaths=<!--{
   *     "Add a habitat": "built-environment-sites/habitats/edit",
   *     "Add a feature": "built-environment-sites/features/edit"
   *   }-->
   *   ```
   *   Paths should point to an "Enter a location (customisable)" with the
   *   "Link the location to a parent" option checked.
   * * @columns - report_grid @columns option setting, if overriding the
   *   default.
   * * @dataSource - report to use if overriding the default. Should accept a
   *   `parent_location_id` parameter for filtering.
   *
   * @return string
   *   The output report grid.
   */
  protected static function get_control_subsites($auth, $args, $tabalias, $options) {
    iform_load_helpers(['report_helper']);
    $options = array_merge([
      'title' => TRUE,
      'dataSource' => 'reports_for_prebuilt_forms/location_details/location_data',
      'extraParams' => [],
    ], $options);
    foreach ($options['extraParams'] as &$value) {
      $value = apply_user_replacements($value);
    }
    $options['extraParams'] += [
      'location_id' => '',
      'parent_location_id' => self::$location['location_id'],
    ];
    $mapOutput = report_helper::report_map([
      'readAuth' => $auth['read'],
      'dataSource' => $options['dataSource'],
      'extraParams' => $options['extraParams'],
      'zoomMapToOutput' => FALSE,
    ]);
    $columns = isset($options['columns']) ? $options['columns'] : [
      [
        'fieldname' => 'name',
        'display' => 'Name',
      ],
      [
        'fieldname' => 'centroid_sref',
        'display' => 'Grid ref.',
      ],
    ];
    // Apply column title i18n.
    foreach ($options['columns'] as $col) {
      if (isset($col['display'])) {
        $col['display'] = lang::get($col['display']);
      }
    }
    $reportOptions = [
      'readAuth' => $auth['read'],
      'dataSource' => $options['dataSource'],
      'extraParams' => $options['extraParams'],
      'rowId' => 'location_id',
      'includeAllColumns' => FALSE,
      'columns' => $columns,
    ];
    if (isset($options['class'])) {
      $reportOptions['class'] = $options['class'];
    }
    $grid = report_helper::report_grid($reportOptions);
    $html = "$mapOutput\n$grid";
    if (!empty($options['addChildrenEditFormPaths'])) {
      global $indicia_templates;
      foreach ($options['addChildrenEditFormPaths'] as $label => $path) {
        $href = helper_base::getRootFolder(TRUE) . "$path?parent_id=$_GET[location_id]";
        $html .= "\n<a class=\"$indicia_templates[anchorButtonClass]\" href=\"$href\" title=\"" .
          lang::get('Add a subsite to this location.') . '">' . lang::get($label) . '</a>';
      }
    }
    return self::controlContainer('Sub-sites', $html, $options);
  }

  /**
   * Loads the record associated with the page if not already loaded.
   */
  protected static function loadLocation(array $readAuth, array $args) {
    if (!isset(self::$location)) {
      $params = [
        'location_id' => $_GET['location_id'],
      ];
      $locations = report_helper::get_report_data([
        'readAuth' => $readAuth,
        'dataSource' => 'reports_for_prebuilt_forms/location_details/location_data',
        'extraParams' => $params,
      ]);
      if (!count($locations)) {
        hostsite_show_message(lang::get('The location cannot be found.', 'warning'));
        throw new exception('');
      }
      self::$location = $locations[0];
      // Set the page metadata.
      global $iform_page_metadata;
      if (!isset($iform_page_metadata)) {
        $iform_page_metadata = [];
      }
      $iform_page_metadata['title'] = self::$location['name'];
      $iform_page_metadata['description'] = lang::get('Details of {1}', self::$location['name']);
      if (!empty(self::$location['location_comment'])) {
        $iform_page_metadata['description'] .= '. ' . trim(self::$location['location_comment'], '. \t\n\r\0\x0B') . '.';
      }
      $iform_page_metadata['latitude'] = number_format((float) self::$location['lat'], 5, '.', '');
      $iform_page_metadata['longitude'] = number_format((float) self::$location['long'], 5, '.', '');
    }
  }

}
