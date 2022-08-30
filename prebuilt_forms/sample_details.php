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
 * Displays the details of a single record.
 *
 * Takes an occurrence_id in the URL and displays the following using a
 * configurable page template:
 * * Record Details including custom attributes.
 * * A map including geometry.
 * * Any photos associated with the occurrence.
 * * Any comments associated with the occurrence including the ability to add
 *   comments.
 */


require_once 'includes/dynamic.php';
require_once 'includes/report.php';
require_once 'includes/groups.php';


class iform_sample_details extends iform_dynamic {

  /**
   * Details of the currently loaded sample.
   *
   * @var array
   */
  protected static $sample;

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
  public static function get_sample_details_definition() {
    return [
      'title' => 'View details of a sample',
      'category' => 'Utilities',
      'description' => 'A summary view of a sample. Pass a parameter in the URL called sample_id to define which occurrence to show.',
      'helpLink' => 'https://indicia-docs.readthedocs.io/en/latest/site-building/iform/prebuilt-forms/sample-details.html',
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
        // List of fields to hide in the Sample Details section.
        [
          'name' => 'fields',
          'caption' => 'Fields to include or exclude',
          'description' => 'List of data fields to hide, one per line. ' .
              'Type in the field name as seen exactly in the Record Details section. For custom attributes you should use the system function values ' .
              'to filter instead of the caption if defined below.',
          'type' => 'textarea',
          'default' =>
'CMS Username
CMS User ID
Email
Sample ID',
          'group' => 'Fields for sample details',
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
          'group' => 'Fields for sample details',
        ],
        [
          'name' => 'testagainst',
          'caption' => 'Test attributes against',
          'description' => 'For custom attributes, do you want to filter the list to show using the caption or the system function? If the latter, then ' .
              'any custom attributes referred to in the fields list above should be referred to by their system function which might be one of: email, ' .
              'cms_user_id, cms_username, first_name, last_name, full_name, biotope.',
          'type' => 'select',
          'options' => [
            'caption' => 'Caption',
            'system_function' => 'System Function',
          ],
          'default' => 'caption',
          'group' => 'Fields for sample details',
        ],
        // Allows the user to define how the page will be displayed.
        [
          'name' => 'structure',
          'caption' => 'Form Structure',
          'description' => 'Define the structure of the form. Each component must be placed on a new line. <br/>' .
            'The following types of component can be specified. <br/>' .
            '<strong>[control name]</strong> indicates a predefined control is to be added to the form with the following predefined controls available: <br/>' .
                '&nbsp;&nbsp;<strong>[sample details]</strong> - displays information relating to the sample<br/>' .
                '&nbsp;&nbsp;<strong>[records grid]</strong> - a grid of records associated with the sample<br/>' .
                '&nbsp;&nbsp;<strong>[records list]</strong> - a simple list of records associated with the sample<br/>' .
                '&nbsp;&nbsp;<strong>[sample photos]</strong> - photos associated with the sample<br/>' .
                "&nbsp;&nbsp;<strong>[parent sample photos]</strong> - photos associated with the sample's parent<br/>" .
                '&nbsp;&nbsp;<strong>[map]</strong> - a map that links to the spatial reference and location<br/>' .
            '<strong>=tab/page name=</strong> is used to specify the name of a tab or wizard page (alpha-numeric characters only). ' .
            'If the page interface type is set to one page, then each tab/page name is displayed as a seperate section on the page. ' .
            'Note that in one page mode, the tab/page names are not displayed on the screen.<br/>' .
            '<strong>|</strong> is used to split a tab/page/section into two columns, place a [control name] on the previous line and following line to split.<br/>',
          'type' => 'textarea',
          'default' =>
'=Sample Details=

[sample details]

|

[map]

=Records=

[records grid]
',
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
          'name' => 'allow_confidential',
          'caption' => 'Allow viewing of confidential records',
          'description' => 'Tick this box to enable viewing of confidential records. Ensure that the page is only available to logged in users with appropropriate permissions if using this option',
          'type' => 'checkbox',
          'required' => FALSE,
        ],
        [
          'name' => 'allow_sensitive_full_precision',
          'caption' => 'Allow viewing of sensitive records at full precision',
          'description' => 'Tick this box to enable viewing of sensitive records at full precision records. Ensure that the page is only available to logged in users with appropropriate permissions if using this option',
          'type' => 'checkbox',
          'required' => FALSE,
        ],
        [
          'name' => 'allow_unreleased',
          'caption' => 'Allow viewing of unreleased records',
          'description' => 'Tick this box to enable viewing of unreleased records. Ensure that the page is only available to logged in users with appropropriate permissions if using this option',
          'type' => 'checkbox',
          'required' => FALSE,
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
    if (empty($_GET['sample_id'])) {
      return 'This form requires an sample_id parameter in the URL.';
    }
    iform_load_helpers(['report_helper']);
    data_entry_helper::$indiciaData['username'] = hostsite_get_user_field('name');
    data_entry_helper::$indiciaData['ajaxFormPostUrl'] = iform_ajaxproxy_url(NULL, 'occurrence') . "&sharing=$args[sharing]";
    return parent::get_form($args, $nid);
  }

  /**
   * A set of buttons for navigating from the sample details.
   *
   * Options include an edit button (only shown for the sample owner) and
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
   *   * buttons - array containing 'edit' to include the edit button. other
   *   options may be added in future. Defaults to all buttons.
   *
   * @return string
   *   HTML for the buttons.
   */
  protected static function get_control_buttons($auth, $args, $tabalias, $options) {
    $options = array_merge([
      'buttons' => [
        'edit',
      ],
    ]);
    $r = '<div class="sample-details-buttons">';
    foreach ($options['buttons'] as $button) {
      if ($button === 'edit') {
        $r .= self::buttons_edit($auth, $args, $tabalias, $options);
      }
      else {
        throw new exception("Unknown button $button");
      }
    }
    $r .= '</div>';
    return $r;
  }

  /**
   * A report grid of occurrences in the sample.
   */
  protected static function get_control_recordsgrid($auth, $args, $tabalias, $options) {
    $columns = [
      ['fieldname' => 'occurrence_id'],
      ['fieldname' => 'taxon'],
      ['fieldname' => 'common'],
      ['fieldname' => 'taxon_group'],
    ];
    // If nothing sensitive, can show extra attribute data.
    if (empty(self::$sample['includes_sensitive'])) {
      $attrs = report_helper::get_population_data([
        'table' => 'occurrence_attribute',
        'extraParams' => $auth['read'] + [
          'restrict_to_survey_id' => self::$sample['survey_id'],
          'columns' => 'id,system_function,caption',
          'orderby' => 'taxon_restrictions is null desc,outer_block_weight,inner_block_weight,weight',
        ],
      ]);
      $occAttrIds = [];
      $systemFunctionsDone = [];
      foreach ($attrs as $attr) {
        if (!empty($attr['system_function'])) {
          if (!in_array($attr['system_function'], $systemFunctionsDone)) {
            if (empty($attr['taxon_restrictions'])) {
              $caption = $attr['caption'];
            }
            else {
              $caption = $attr['system_function'] === 'sex_stage_count'
                ? lang::get('Quantity')
                : lang::get(ucfirst(str_replace('_', ' ', $attr['system_function'])));
            }
            $columns[] = [
              'fieldname' => "attr_$attr[system_function]",
              'display' => $caption,
              'visible' => TRUE,
            ];
            $systemFunctionsDone[] = $attr['system_function'];
          }
        }
        else {
          $occAttrIds[] = $attr['id'];
          $columns[] = ['fieldname' => "attr_occurrence_$attr[id]"];
        }
      }
    }
    else {
      // If sensitive, hide all images.
      $columns[] = ['fieldname' => 'images', 'visible' => FALSE];
    }
    $r = '<div class="detail-panel" id="detail-panel-recordsgrid"><h3>' . lang::get('Occurrences') . '</h3>';
    $r .= report_helper::report_grid([
      'readAuth' => $auth['read'],
      'dataSource' => 'reports_for_prebuilt_forms/sample_details/occurrences_list',
      'ajax' => FALSE,
      'extraParams' => [
        'smpattrs' => '',
        'occattrs' => implode(',', $occAttrIds),
        'limit' => 200,
        'useJsonAttributes' => TRUE,
        'sample_id' => $_GET['sample_id'],
      ],
      'caching' => TRUE,
      'cacheTimeout' => 60,
      'cachePerUser' => FALSE,
      'columns' => $columns,
    ]);
    $r .= '</div>';
    return $r;
  }

  /**
   * A simplified list of the records in the sample.
   */
  protected static function get_control_recordslist($auth, $args, $tabalias, $options) {
    $records = report_helper::get_report_data([
      'readAuth' => $auth['read'],
      'dataSource' => 'reports_for_prebuilt_forms/sample_details/occurrences_list_simple',
      'extraParams' => [
        'smpattrs' => '',
        'occattrs' => '',
        'limit' => 200,
        'useJsonAttributes' => TRUE,
        'sample_id' => $_GET['sample_id'],
      ],
      'caching' => TRUE,
      'cacheTimeout' => 60,
      'cachePerUser' => FALSE,
    ]);
    $r = '<div class="detail-panel" id="detail-panel-recordslist"><h3>' . lang::get('Occurrences') . '</h3>';
    $r .= lang::get('{1} species seen', count($records));
    $r .= '<ul>';
    if (count($records) > 0) {
      foreach ($records as $record) {
        $label = $record['count'] === '' ? '' : "$record[count] ";
        $label .= "<em>$record[taxon]</em>";
        if (!empty($record['common']) && $record['common'] !== $record['taxon']) {
          $label .= " ($record[common])";
        }
        $r .= "<li>$label</li>";
      }
    }
    $r .= '</ul></div>';
    return $r;
  }

  /**
   * Render Sample Details section of the page.
   *
   * @return string
   *   The output freeform report.
   */
  protected static function get_control_sampledetails($auth, $args, $tabalias, $options) {
    global $indicia_templates;
    $options = array_merge([
      'dataSource' => 'reports_for_prebuilt_forms/sample_details/sample_data_attributes_with_hiddens',
      'outputFormatting' => FALSE,
    ], $options);
    $fields = helper_base::explode_lines($args['fields']);
    $fieldsLower = helper_base::explode_lines(strtolower($args['fields']));
    // Draw the Sample Details, but only if they aren't requested as hidden by
    // the administrator.
    $test = $args['operator'] === 'in';
    $availableFields = [
      'survey_title' => lang::get('Survey'),
      'recorder' => lang::get('Recorder'),
      'inputter' => lang::get('Input by'),
      'date' => lang::get('Date'),
      'entered_sref' => lang::get('Grid ref'),
      'sref_precision' => lang::get('Uncertainty (m)'),
      'location_name' => lang::get('Site name'),
      'sample_comment' => lang::get('Sample comment'),
    ];
    self::load_sample($auth, $args);

    $flags = [];
    if (!empty(self::$sample['includes_sensitive'])) {
      $flags[] = lang::get('Includes sensitive records');
    }
    if (self::$sample['includes_confidential'] == 1) {
      $flags[] = lang::get('Includes confidential records');
    }
    if (self::$sample['includes_unreleased'] == 1) {
      $flags[] = lang::get('Includes unreleased records');
    }
    if (!empty($flags)) {
      $details_report = '<div id="record-flags"><span>' . implode('</span><span>', $flags) . '</span></div>';
    }
    else {
      $details_report = '';
    }

    $details_report .= '<div class="sample-details-fields ui-helper-clearfix">';
    $title = lang::get('Sample at {1} on {2}', self::$sample['entered_sref'], self::$sample['date']);
    hostsite_set_page_title($title);
    foreach ($availableFields as $field => $caption) {
      if ($test === in_array(strtolower($caption), $fieldsLower) && !empty(self::$sample[$field])) {
        $value = lang::get(self::$sample[$field]);
        $details_report .= str_replace(
          ['{caption}', '{value}', '{class}'],
          [lang::get($caption), $value, ''],
          $indicia_templates['dataValue']
        );
      }
    }
    $created = date('jS F Y \a\t H:i', strtotime(self::$sample['created_on']));
    $updated = date('jS F Y \a\t H:i', strtotime(self::$sample['updated_on']));
    $dateInfo = lang::get('Entered on {1}', $created);
    if ($created !== $updated) {
      $dateInfo .= lang::get(' and last updated on {1}', $updated);
    }
    if ($test === in_array('submission date', $fieldsLower)) {
      $details_report .= str_replace(['{caption}', '{value}', '{class}'],
          [lang::get('Submission date'), $dateInfo, ''], $indicia_templates['dataValue']);
    }
    $details_report .= '</div>';
    if (empty(self::$sample['includes_sensitive']) || $args['allow_sensitive_full_precision']) {
      // Draw any custom attributes added by the user, but only for a
      // non-sensitive record.
      $attrs_report = report_helper::freeform_report([
        'readAuth' => $auth['read'],
        'class' => 'record-details-fields ui-helper-clearfix',
        'dataSource' => $options['dataSource'],
        'bands' => [['content' => str_replace(['{class}'], '', $indicia_templates['dataValue'])]],
        'extraParams' => [
          'sample_id' => $_GET['sample_id'],
          // The SQL needs to take a set of the hidden fields, so this needs to be converted from an array.
          'attrs' => strtolower(self::convert_array_to_set($fields)),
          'testagainst' => $args['testagainst'],
          'operator' => $args['operator'],
          'sharing' => $args['sharing'],
          'language' => iform_lang_iso_639_2(hostsite_get_user_field('language')),
          'output_formatting' => $options['outputFormatting'] ? 't' : 'f',
        ],
      ]);
    }

    $r = '<div class="detail-panel" id="detail-panel-sampledetails"><h3>' . lang::get('Sample details') . '</h3><dl class="dl-horizontal">';

    $r .= $details_report;
    if (isset($attrs_report)) {
      $r .= $attrs_report;
    }
    $r .= '</dl></div>';
    return $r;
  }

  /**
   * Convert an array of attributes to a string formatted like a set.
   *
   * This is then used by the record_data_attributes_with_hiddens report to return
   * custom attributes which aren't in the hidden attributes list.
   *
   * @return string
   *   The set of hidden custom attributes.
   */
  protected static function convert_array_to_set($theArray) {
    return "'" . implode("','", str_replace("'", "''", $theArray)) . "'";
  }

  /**
   * Render Sample Photos section of the page.
   *
   * @return string
   *   The output report grid.
   */
  protected static function get_control_samplephotos($auth, $args, $tabalias, $options) {
    iform_load_helpers(['data_entry_helper']);
    $options = array_merge([
      'title' => lang::get('Sample photos and media'),
    ], $options);
    $settings = [
      'type' => 'sample',
      'value' => $_GET['sample_id'],
    ];
    return self::commonControlPhotos($auth, $args, $options, $settings);
  }

  /**
   * Draw Parent Samples Photos section of the page.
   *
   * @return string
   *   The output report grid.
   */
  protected static function get_control_parentsamplephotos($auth, $args, $tabalias, $options) {
    if (empty($sample[0]['parent_sample_id'])) {
      return '<p>' . lang::get('No photos or media files available') . '</p>';
    }
    $options = array_merge([
      'title' => lang::get('Parent sample photos and media'),
    ], $options);
    $settings = [
      'type' => 'parentsample',
      'value' => $sample[0]['parent_sample_id'],
    ];
    return self::commonControlPhotos($auth, $args, $options, $settings);
  }

  /**
   * Draws a common control for all photos controls.
   *
   * @return string
   *   The output report grid.
   */
  private static function commonControlPhotos($auth, $args, $options, $settings) {
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
    $extraParams['sample_id'] = $settings['value'];
    $media = data_entry_helper::get_population_data([
      'table' => 'sample_medium',
      'extraParams' => $extraParams,
    ]);
    $r = <<<HTML
<div class="detail-panel" id="detail-panel-photos-$settings[type]">
  <h3>$options[title]</h3>
  <div class="$options[class]">

HTML;
    if (empty($media)) {
      $r .= '<p>' . lang::get('No photos or media files available') . '</p>';
    }
    else {
      if (isset($options['helpText'])) {
        $r .= '<p>' . $options['helpText'] . '</p>';
      }
      $r .= '<ul>';
      $firstImage = TRUE;
      foreach ($media as $medium) {
        if ($firstImage && substr($medium['media_type'], 0, 6) === 'Image:') {
          // First image can be flagged as the main content image. Used for FB
          // OpenGraph for example.
          global $iform_page_metadata;
          if (!isset($iform_page_metadata)) {
            $iform_page_metadata = [];
          }
          $imageFolder = data_entry_helper::get_uploaded_image_folder();
          $iform_page_metadata['image'] = "$imageFolder$medium[path]";
          $firstImage = FALSE;
        }
        $r .= iform_report_get_gallery_item('sample', $medium, $options['imageSize']);
      }
      $r .= '</ul>';
    }
    $r .= '</div></div>';
    return $r;
  }

  /**
   * Render the Map section of the page.
   *
   * Option @showParentChildSampleGeoms can be set to true for data in a
   * parent/child sample hierarchy (like a UKBMS transect walk) to show the
   * parent transect, child sections and occurrence data points.
   *
   * @return string
   *   The output map panel.
   */
  protected static function get_control_map($auth, $args, $tabalias, $options) {
    iform_load_helpers(['map_helper']);
    self::load_sample($auth, $args);
    $options = array_merge(
      iform_map_get_map_options($args, $auth['read']),
      [
        'maxZoom' => 16,
        'maxZoomBuffer' => 1,
        'showParentChildSampleGeoms' => FALSE,
        'clickForSpatialRef' => FALSE,
      ],
      $options
    );
    if (isset(self::$sample['geom'])) {
      $options['initialFeatureWkt'] = self::$sample['geom'];
    }
    if ($options['showParentChildSampleGeoms']) {
      $params = [
        'sample_id' => $_GET['sample_id'],
        'sharing' => $args['sharing'],
      ];
      $geoms = report_helper::get_report_data([
        'readAuth' => $auth['read'],
        'dataSource' => 'reports_for_prebuilt_forms/sample_details/extra_geoms_for_parent_child_sample_details',
        'extraParams' => $params,
      ]);
      if (count($geoms) > 0) {
        map_helper::$indiciaData['parentChildGeoms'] = $geoms;
      }
    }
    if (!empty(self::$sample['sref_precision'])) {
      // Set radius if imprecise.
      map_helper::$indiciaData['srefPrecision'] = self::$sample['sref_precision'];
    }

    if ($tabalias) {
      $options['tabDiv'] = $tabalias;
    }
    $olOptions = iform_map_get_ol_options($args);

    if (!isset($options['standardControls'])) {
      $options['standardControls'] = ['layerSwitcher', 'panZoom'];
    }
    return '<div class="detail-panel" id="detail-panel-map"><h3>' . lang::get('Map')  .'</h3>' . map_helper::map_panel($options, $olOptions) . '</div>';
  }

  /**
   * Outputs a login form. Not displayed if already logged in.
   *
   * @param array $auth
   *   Authentication tokens.
   * @param array $args
   *   Form arguments.
   * @param string $tabalias
   *   ID of the tab, if relevant.
   * @param array $options
   *   Options array passed in the configuration to the [login] control.
   *   Possible values include instruct - the instruction to display above
   *   the login form.
   *
   * @return string
   *   Control HTML, empty if logged in.
   */
  protected static function get_control_login($auth, $args, $tabalias, $options) {
    $options = array_merge([
      'instruct' => 'Please log in or <a href="user/register">register</a> to see more details of this record.',
    ], $options);
    if (hostsite_get_user_field('id') === 0) {
      return '<div class="detail-panel" id="detail-panel-login">' .
          '<h3>' . lang::get('Login') . '</h3>' .
          '<p>' . lang::get($options['instruct']) . '</p>' .
         hostsite_render_form('user_login', ['noredirect' => TRUE]) .
          '</div>';
    }
    else {
      return '';
    }
  }

  /**
   * Sets default values for arguments.
   *
   * When a form version is upgraded introducing new parameters, old forms will
   * not get the defaults for the parameters unless the Edit and Save button is
   * clicked. So, apply some defaults to keep those old forms working.
   *
   * @return array
   *   Updated form arguments.
   */
  protected static function getArgDefaults($args) {
    $defaultHiddenFields = <<<FIELDS
CMS Username
Email
Sample ID
Record ID
FIELDS;
    $defaultStructure = <<<STRUCT
=Record Details and Comments=
[sample details]
|
[comments]
=Map and Photos=
[map]
|
[sample photos]
STRUCT;
    $args = array_merge([
      'interface' => 'one_page',
      'allow_confidential' => FALSE,
      'allow_sensitive_full_precision' => FALSE,
      'allow_unreleased' => FALSE,
      'hide_fields' => $defaultHiddenFields,
      'structure' => $defaultStructure,
      'sharing' => 'reporting',
    ], $args);
    return $args;
  }

  /**
   * Loads the sample associated with the page if not already loaded.
   */
  protected static function load_sample($auth, $args) {
    if (!isset(self::$sample)) {
      $params = [
        'sample_id' => $_GET['sample_id'],
        'sharing' => $args['sharing'],
        'allow_confidential' => $args['allow_confidential'] ? 1 : 0,
        'allow_sensitive_full_precision' => $args['allow_sensitive_full_precision'] ? 1 : 0,
        'allow_unreleased' => $args['allow_unreleased'] ? 1 : 0,
      ];
      if (!$args['allow_confidential']) {
        $params['includes_confidential'] = '0';
      }
      if (!$args['allow_unreleased']) {
        $params['includes_unreleased'] = '0';
      }
      $samples = report_helper::get_report_data([
        'readAuth' => $auth['read'],
        'dataSource' => 'reports_for_prebuilt_forms/sample_details/sample_data',
        'extraParams' => $params,
      ]);
      if (!count($samples)) {
        hostsite_show_message(lang::get('The sample cannot be found.', 'warning'));
        throw new exception('');
      }
      self::$sample = $samples[0];
      // Set the page metadata.
      global $iform_page_metadata;
      if (!isset($iform_page_metadata)) {
        $iform_page_metadata = [];
      }
      $iform_page_metadata['title'] = lang::get('Sample at {1} on {2}', self::$sample['entered_sref'], self::$sample['date']);
      $iform_page_metadata['description'] = lang::get('Sample at {1} on {2}', self::$sample['entered_sref'], self::$sample['date']);
      if (!empty(self::$sample['sample_comment'])) {
        $iform_page_metadata['description'] .= '. ' . trim(self::$sample['sample_comment'], '. \t\n\r\0\x0B') . '.';
      }
      $iform_page_metadata['latitude'] = number_format((float) self::$sample['lat'], 5, '.', '');
      $iform_page_metadata['longitude'] = number_format((float) self::$sample['long'], 5, '.', '');
    }
  }

  /**
   * Override some default behaviour in dynamic.
   */
  protected static function getFirstTabAdditionalContent($args, $auth, &$attributes) {
    return '';
  }

  /**
   * Retrieve the HTML required for an edit record button.
   *
   * @param array $auth
   *   Read authorisation array.
   * @param array $args
   *   Form options.
   * @param string $tabalias
   *   The alias of the tab this appears on.
   * @param array $options
   *   Options configured for this control.
   *
   * @return string
   *   HTML for the button.
   */
  protected static function buttons_edit($auth, $args, $tabalias, $options) {
    global $indicia_templates;
    if (!$args['default_input_form']) {
      throw new exception('Please set the default input form path setting before using the [edit button] control');
    }
    self::load_sample($auth, $args);
    $sample = self::$sample;
    if (($user_id = hostsite_get_user_field('indicia_user_id')) && $user_id == $sample['created_by_id']
        && $args['website_id'] == $sample['website_id']) {
      if (empty($sample['input_form'])) {
        $sample['input_form'] = $args['default_input_form'];
      }
      $rootFolder = data_entry_helper::getRootFolder(TRUE);
      $paramJoin = strpos($rootFolder, '?') === FALSE ? '?' : '&';
      $url = "$rootFolder$sample[input_form]{$paramJoin}sample_id=$sample[sample_id]";
      return "<a class=\"$indicia_templates[buttonDefaultClass]\" href=\"$url\">" . lang::get('Edit this sample') . '</a>';
    }
    else {
      // No rights to edit, so button omitted.
      return '';
    }
  }

}
