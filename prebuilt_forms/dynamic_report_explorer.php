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
 * @link http://code.google.com/p/indicia/
 */

require_once('includes/dynamic.php');
require_once('includes/report.php');
require_once('includes/report_filters.php');

/**
 * Provides a dynamically output page which can contain a map and several reports, potentially
 * organised onto several tabs.
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_dynamic_report_explorer extends iform_dynamic {

  /**
   * Disable wrapping output in <form>.
   *
   * @var bool
   */
  protected static $isDataEntryForm = FALSE;

  /**
   * Count the total number of reports for this page request so we can generate unique ids.
   * @var integer
   */
  private static $requestReportCount = 0;

  /**
   * Count the reports on this node instance to link reports to the correct set of column configurations.
   * * @var integer
   */
  private static $reportCount = 0;

  /**
   * If using the standard params system then the way of supplying user prefs is different. Default to
   * use the old ownData/ownGroups/ownLocality way.
   * @var bool
   */
  private static $applyUserPrefs = TRUE;

  /**
   * Return the form metadata.
   */
  public static function get_dynamic_report_explorer_definition() {
    return array(
      'title' => 'Reporting page (customisable)',
      'category' => 'Reporting',
      'description' => 'Provides a dynamically output page which can contain a map and several reports, potentially '.
          'organised onto several tabs.',
      'recommended' => TRUE
    );
  }

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    return array_merge(
      iform_map_get_map_parameters(),
      array(
        array(
          'name' => 'interface',
          'caption' => 'Interface Style Option',
          'description' => 'Choose the style of user interface, either dividing the form up onto separate tabs, '.
            'wizard pages or having all controls on a single page.',
          'type' => 'select',
          'options' => array(
            'tabs' => 'Tabs',
            'wizard' => 'Wizard',
            'one_page' => 'All One Page'
          ),
          'default' => 'one_page',
          'group' => 'User Interface'
        ),
        array(
          'name' => 'structure',
          'caption' => 'Form Structure',
          'description' => 'Define the structure of the form. Each component must be placed on a new line. <br/>'.
              "The following types of component can be specified. <br/>".
                  "<strong>[control name]</strong> indicates a predefined control is to be added to the form with the following predefined controls available: <br/>".
                  "&nbsp;&nbsp;<strong>[params]</strong> - a parameters input form for the reports/map<br/>".
                  "&nbsp;&nbsp;<strong>[standard params]</strong> - a standard params filter bar. Use with reports that support standard params.<br/>".
                  "&nbsp;&nbsp;<strong>[map]</strong> - outputs report content as a map.<br/>".
                  "&nbsp;&nbsp;<strong>[reportgrid]</strong> - outputs report content in tabular form.<br/>".
                  "&nbsp;&nbsp;<strong>[reportdownloadlink]</strong> - a simple link to download the report's records.<br/>".
                  "&nbsp;&nbsp;<strong>[reportchart]</strong> - outputs report content in chart form.<br/>".
                  "&nbsp;&nbsp;<strong>[reportfreeform]</strong> - outputs report content in flexible HTML.<br/>".
                  "&nbsp;&nbsp;<strong>[reportaddtojs]</strong> - outputs report data to JavaScript for use by custom " .
                    "code. The data are added to the indiciaData object, in a property with the name given in the " .
                    "@name option.<br/>".
              "<strong>=tab/page name=</strong> is used to specify the name of a tab or wizard page (alpha-numeric characters only). ".
              "If the page interface type is set to one page, then each tab/page name is displayed as a seperate section on the page. ".
              "Note that in one page mode, the tab/page names are not displayed on the screen.<br/>".
              "<strong>|</strong> is used to split a tab/page/section into two columns, place a [control name] on the previous line and following line to split.<br/>",
          'type' => 'textarea',
          'default' =>
'[params]
@dataSource=library/occurrences/explore_list
=Map=
[map]
@dataSource=library/occurrences/explore_list
=Records=
[report_grid]
@dataSource=library/occurrences/explore_list',
          'group' => 'User Interface'
        ),
        array(
          'name' => 'param_presets',
          'caption' => 'Preset parameter values',
          'description' => 'To provide preset values for any report parameter and avoid the user having to enter them, enter each parameter into this '.
              'box one per line. Each parameter is followed by an equals then the value, e.g. survey_id=6. You can use {user_id} as a value which will be replaced by the '.
              'user ID from the CMS logged in user or {username} as a value replaces with the logged in username. If you have installed the Profile module then you can also '.
              'use {profile_*} to refer to the value of a field in the user\'s profile (replace the asterisk to make the field name match the field created in the profile). '.
              'Parameters with preset values are not shown in the parameters form and therefore can\'t be overridden by the user.',
          'type' => 'textarea',
          'required' => FALSE,
          'group' => 'Report Settings',
          'default' => "smpattrs=\noccattrs=\nlocation_id={profile_location}\ntaxon_groups={profile_taxon_groups}\ncurrentUser={profile_indicia_user_id}"
        ), array(
          'name' => 'param_defaults',
          'caption' => 'Default parameter values',
          'description' => 'To provide default values for any report parameter which allow the report to run initially but can be overridden, enter each parameter into this '.
              'box one per line. Each parameter is followed by an equals then the value, e.g. survey_id=6. You can use {user_id} as a value which will be replaced by the '.
              'user ID from the CMS logged in user or {username} as a value replaces with the logged in username. If you have installed the Profile module then you can also '.
              'use {profile_*} to refer to the value of a field in the user\'s profile (replace the asterisk to make the field name match the field created in the profile). '.
              'Unlike preset parameter values, parameters referred to by default parameter values are displayed in the parameters form and can therefore be changed by the user.',
          'type' => 'textarea',
          'required' => FALSE,
          'group' => 'Report Settings',
          'default' => "idlist=\nsearchArea="
        ), array(
          'name' => 'param_ignores',
          'caption' => 'Default params to exclude from the form',
          'description' => 'Provide a list of the parameter names which are in the Default Parameter Values but should not appear in the parameters form. An example usage of this '.
              'is to provide parameters that can be overridden via a URL parameter.',
          'type' => 'textarea',
          'required' => FALSE,
          'group' => 'Report Settings'
        ), array(
          'name' => 'columns_config_list',
          'caption' => 'Columns Configuration List',
          'description' => 'For each report on the user interface output, define a list of columns with various '.
              'configuration options when you want to override the default output of the report. The ordering of each '.
              'set of columns should match the ordering of each [reportgrid] in the User Interface configuration. ',
          'type' => 'jsonwidget',
          'schema' => '{
"type":"seq",
"title":"Column Configuration List",
"sequence":
[
  {
    "type":"seq",
    "title":"Columns List",
    "sequence":
    [
      {
        "type":"map",
        "title":"Column",
        "mapping": {
          "fieldname": {"type":"str","desc":"Name of the field to output in this column. Does not need to be specified when using the template option."},
          "display": {"type":"str","desc":"Caption of the column, which defaults to the fieldname if not specified."},
          "actions": {
            "type":"seq",
            "title":"Actions List",
            "sequence": [{
              "type":"map",
              "title":"Actions",
              "desc":"List of actions to make available for each row in the grid.",
              "mapping": {
                "caption": {"type":"str","desc":"Display caption for the action\'s link."},
                "img": {"type":"str","desc":"Set img to the path to an image to use an image for the action instead of a text caption - the caption '.
                    'then becomes the image\'s title. The image path can contain {rootFolder} to be replaced by the root folder of the site, in this '.
                    'case it excludes the path parameter used in Drupal when dirty URLs are used (since this is a direct link to a URL)."},
                "visibility_field": {"type":"str","desc":"Optional name of a field in the data which contains true or false to define the visibility of this action."},
                "url": {"type":"str","desc":"A url that the action link will point to, unless overridden by JavaScript. The url can contain tokens which '.
                    'will be subsituted for field values, e.g. for http://www.example.com/image/{id} the {id} is replaced with a field called id in the current row. '.
                'Can also use the subsitution {currentUrl} to link back to the current page, {rootFolder} to represent the folder on the server that the current PHP page is running from, and '.
                '{imageFolder} for the image upload folder"},
                "urlParams": {
                  "type":"map",
                  "subtype":"str",
                  "desc":"List of parameters to append to the URL link, with field value replacements such as {id} begin replaced '.
                      'by the value of the id field for the current row."
                },
                "class": {"type":"str","desc":"CSS class to attach to the action link."},
                "javascript": {"type":"str","desc":"JavaScript that will be run when the link is clicked. Can contain field value substitutions '.
                    'such as {id} which is replaced by the value of the id field for the current row. Because the javascript may pass the field values as parameters to functions, '.
                    'there are escaped versions of each of the replacements available for the javascript action type. Add -escape-quote or '.
                    '-escape-dblquote to the fieldname. For example this would be valid in the action javascript: foo(\"{bar-escape-dblquote}\"); '.
                    'even if the field value contains a double quote which would have broken the syntax."}
              }
            }]
          },
          "visible": {"type":"bool","desc":"Should this column be shown? Hidden columns can still be used in templates or actions."},
          "template": {"type":"txt","desc":"Allows you to create columns that contain dynamic content using a template, rather than just the output '.
            'of a field. The template text can contain fieldnames in braces, which will be replaced by the respective field values. '.
            'Note that template columns cannot be sorted by clicking grid headers." },
          "json": {"type":"bool","desc":"Set to true if the column contains a json string object with properties that can be decoded to give strings that '.
            'can be used as replacements in a template."},
          "update": {
            "type":"map",
            "title":"Update Specification",
            "desc":"Defines the configuration to allow this field to update the database via AJAX. This assumes assume that we have access through iform_ajaxproxy.",
            "mapping": {
              "permission": {"type":"str","desc":"The CMS permission that the user must have in order for the field to be editable. If left blank then all users may update it."},
              "method": {"type":"str","desc":"Ajax proxy method, e.g. loc"},
              "tablename": {"type":"str","desc":"Submission table name: used to create the form field names from which the submission is built; e.g. location"},
              "fieldname": {"type":"str","desc":"Field name for this field in submission; e.g. code"},
              "website_id": {"type":"str","desc":"website_id"},
              "class": {"type":"str","desc":"Class name to apply to input control."},
              "parameters": {
                "type":"map",
                "subtype":"str",
                "desc":"List of parameters to copy from the report to the submission; with field value replacements such as {id} begin replaced '.
                    'by the value of the id field for the current row."
              }
            }
          },
          "responsive-hide": {
            "type":"map",
            "title":"Responsive hide",
            "desc":"List of breakpoint names where this column will be hidden if the display is smaller than the breakpoint size.",
            "mapping": {
              "phone": {"type":"bool","required":true,"desc":"Hidden if screen <= 480px."},
              "tablet-portrait": {"type":"bool","required":true,"desc":"Hidden if 480px < screen <= 768px."},
              "tablet-landscape": {"type":"bool","required":true,"desc":"Hidden if 768px < screen <= 1024px."},
            }
          }
        }
      }
    ]
  }
]
}',
          'required' => FALSE,
          'group' => 'Report Settings'
        ),
        array(
          'name' => 'high_volume',
          'caption' => 'High volume reporting',
          'description' => 'Tick this box to enable caching which prevents reporting pages with a high number of hits from generating ' .
              'excessive server load. Currently compatible only with reporting pages that do not integrate with the user profile.',
          'type' => 'boolean',
          'default' => FALSE,
          'required' => FALSE
        ),
        array(
          'name' => 'sharing',
          'caption' => 'Record sharing mode',
          'description' => 'Identify the task this page is being used for, which determines the websites that will ' .
            'share records for use here.',
          'type' => 'select',
          'options' => array(
            'reporting' => 'Reporting',
            'peer_review' => 'Peer review',
            'verification' => 'Verification',
            'data_flow' => 'Data flow',
            'moderation' => 'Moderation',
            'editing' => 'Editing',
            'me' => 'My records only',
          ),
          'default' => 'reporting',
          'group' => 'Report Settings',
        )
      )
    );
  }

  /**
   * Override the get_form to fetch our own auth tokens. This skips the write auth as it is unnecessary,
   * which makes the tokens cachable therefore faster. It does mean that $auth['write'] will not be available.
   */
  public static function get_form($args, $nid) {
    // Count of reports for this node instance. If multiple nodes on 1 page then this counter gets reset for each.
    self::$reportCount = 0;
    $conn = iform_get_connection_details($nid);
    self::$auth = array('read' => data_entry_helper::get_read_auth($conn['website_id'], $conn['password']));
    data_entry_helper::$javascript .= 'indiciaData.ajaxUrl="' . hostsite_get_url('iform/ajax/dynamic_report_explorer') . "\";\n";
    return parent::get_form($args, $nid);
  }

  /**
   * Retrieves a [params] control, containing the report parameters.
   *
   * @param array $auth
   *   Authorisation tokens.
   * @param array $args
   *   Form configuration parameters from the Drupal edit page.
   * @param string $tabalias
   *   Unique identified for the tab we are loading onto.
   * @param array $options
   *   Options for this control as configured using @ overrides in the Drupal
   *   edit page's User Interface configuration.
   *
   * @return string
   *   Control HTML
   */
  protected static function get_control_params($auth, $args, $tabalias, $options) {
    iform_load_helpers(array('report_helper'));
    $sharing = empty($args['sharing']) ? 'reporting' : $args['sharing'];
    // Allow us to call iform_report_get_report_options to get a default report setup, then override report_name.
    $args['report_name'] = '';
    $reportOptions = array_merge(
      iform_report_get_report_options($args, $auth['read']),
      array(
        'reportGroup' => 'dynamic',
        'paramsOnly' => TRUE,
        'sharing' => $sharing,
        'paramsFormButtonCaption' => lang::get('Filter')
      )
    );
    // Ensure supplied extraParams are merged, not overwritten.
    if (!empty($options['extraParams'])) {
      $options['extraParams'] = array_merge($reportOptions['extraParams'], $options['extraParams']);
    }
    $reportOptions = array_merge($reportOptions, $options);
    if (self::$applyUserPrefs) {
      iform_report_apply_explore_user_own_preferences($reportOptions);
    }
    return report_helper::report_grid($reportOptions);
  }

  protected static function get_control_map($auth, $args, $tabalias, $options) {
    iform_load_helpers(array('map_helper', 'report_helper'));
    if (isset($options['hoverShowsDetails'])) {
      $options['hoverShowsDetails'] = TRUE;
    }
    // $_GET data for standard params can override displayed location.
    $locationIDToLoad = @$_GET['filter-indexed_location_list']
      ?: @$_GET['filter-indexed_location_id']
      ?: @$_GET['filter-location_list']
      ?: @$_GET['filter-location_id'];
    if (!empty($locationIDToLoad) && preg_match('/^\d+$/', $locationIDToLoad)) {
      $args['display_user_profile_location'] = FALSE;
      $args['location_boundary_id'] = $locationIDToLoad;
    }
    $r = '';
    if (!empty($options['dataSource'])) {
      // allow us to call iform_report_get_report_options to get a default report setup, then override report_name
      $args['report_name'] = '';
      $sharing = empty($args['sharing']) ? 'reporting' : $args['sharing'];
      $reportOptions = array_merge(
        iform_report_get_report_options($args, $auth['read']),
        array(
          'reportGroup' => 'dynamic',
          'autoParamsForm' => FALSE,
          'sharing' => $sharing,
          'readAuth' => $auth['read'],
          'dataSource' => $options['dataSource'],
          'rememberParamsReportGroup' => 'dynamic',
          'clickableLayersOutputMode' => 'report',
          'rowId' => 'occurrence_id',
          'ajax' => TRUE,
        )
      );
      // Ensure supplied extraParams are merged, not overwritten.
      if (!empty($options['extraParams'])) {
        $options['extraParams'] = array_merge($reportOptions['extraParams'], $options['extraParams']);
      }
      $reportOptions = array_merge($reportOptions, $options);
      if (self::$applyUserPrefs) {
        iform_report_apply_explore_user_own_preferences($reportOptions);
      }
      $r = report_helper::report_map($reportOptions);
    }
    $options = array_merge(
      iform_map_get_map_options($args, $auth['read']),
      array(
        'featureIdField' => 'occurrence_id',
        'clickForSpatialRef' => FALSE,
        'reportGroup' => 'explore',
        'toolbarDiv' => 'top',
      ),
      $options
    );
    $olOptions = iform_map_get_ol_options($args);
    if ($tabalias) {
      $options['tabDiv'] = $tabalias;
    }
    $r .= map_helper::map_panel($options, $olOptions);
    return $r;
  }

  protected static function get_control_reportgrid($auth, $args, $tabalias, $options) {
    iform_load_helpers(array('report_helper'));
    $columnLists = json_decode($args['columns_config_list']);
    if (self::$reportCount < count($columnLists)) {
      $args['columns_config'] = json_encode($columnLists[self::$reportCount]);
    }
    else {
      unset($args['columns_config']);
    }
    $args['report_name'] = '';
    $sharing = empty($args['sharing']) ? 'reporting' : $args['sharing'];
    $reportOptions = array_merge(
      iform_report_get_report_options($args, $auth['read']),
      array(
        'reportGroup' => 'dynamic',
        'autoParamsForm' => FALSE,
        'sharing' => $sharing,
        'ajax' => TRUE,
        'id' => 'report-grid-' . self::$requestReportCount,
        'responsiveOpts' => array(
          'breakpoints' => array(
            'phone' => 480,
            'tablet-portrait' => 768,
            'tablet-landscape' => 1024,
          ),
        ),
      )
    );
    // Ensure supplied extraParams are merged, not overwritten.
    if (!empty($options['extraParams'])) {
      $options['extraParams'] = array_merge($reportOptions['extraParams'], $options['extraParams']);
    }
    $reportOptions = array_merge($reportOptions, $options);
    if (self::$applyUserPrefs) {
      iform_report_apply_explore_user_own_preferences($reportOptions);
    }
    self::$requestReportCount++;
    self::$reportCount++;
    return report_helper::report_grid($reportOptions);
  }

  /**
   * A control for adding a simple download link to a page.
   *
   * Options available are:
   *   * dataSource - the path to the report file to download from.
   *   * limit - maximum number of records, defaults to 20,000.
   *
   * @return string
   *   Link HTML.
   */
  protected static function get_control_reportdownloadlink($auth, $args, $tabalias, $options) {
    iform_load_helpers(array('report_helper'));
    $args['report_name'] = '';
    $sharing = empty($args['sharing']) ? 'reporting' : $args['sharing'];
    // Set default limit.
    $args['items_per_page'] = isset($options['limit']) ? $options['limit'] : 20000;
    $reportOptions = array_merge(
      iform_report_get_report_options($args, $auth['read']),
      array(
        'reportGroup' => 'dynamic',
        'autoParamsForm' => FALSE,
        'sharing' => $sharing,
      )
    );
    // Ensure supplied extraParams are merged, not overwritten.
    if (!empty($options['extraParams'])) {
      $options['extraParams'] = array_merge($reportOptions['extraParams'], $options['extraParams']);
    }
    $reportOptions = array_merge($reportOptions, $options);
    if (self::$applyUserPrefs) {
      iform_report_apply_explore_user_own_preferences($reportOptions);
    }
    return report_helper::report_download_link($reportOptions);
  }

  /*
   * Freeform report
   * Allows flexible HTML output from report content.
   */
  protected static function get_control_reportfreeform($auth, $args, $tabalias, $options) {
    iform_load_helpers(array('report_helper'));
    $args['report_name'] = '';
    $sharing = empty($args['sharing']) ? 'reporting' : $args['sharing'];
    $reportOptions = array_merge(
      iform_report_get_report_options($args, $auth['read']),
      array(
        'reportGroup' => 'dynamic',
        'autoParamsForm' => FALSE,
        'sharing' => $sharing,
        'id' => 'report-freeform-' . self::$reportCount
      )
    );
    // ensure supplied extraParams are merged, not overwritten
    if (!empty($options['extraParams']))
      $options['extraParams'] = array_merge($reportOptions['extraParams'], $options['extraParams']);
    $reportOptions = array_merge($reportOptions, $options);
    if (self::$applyUserPrefs)
      iform_report_apply_explore_user_own_preferences($reportOptions);
    self::$reportCount++;
    return report_helper::freeform_report($reportOptions);
  }

  /**
   * A control which dumps the data for a report into the JavaScript.
   *
   * Provides no output itself, but facilitates JavaScript which needs to
   * access report data by adding the data to the indiciaData object. The name
   * of the entry in indiciaData must be specified in a @name option. Other
   * options are identical to other report controls.
   *
   * @return string
   *   Empty HTML for the control.
   */
  protected static function get_control_reportaddtojs($auth, $args, $tabalias, $options) {
    if (empty($options['name'])) {
      return 'The Report Add to JS control requires a @name parameter.';
    }
    iform_load_helpers(array('report_helper'));
    $sharing = empty($args['sharing']) ? 'reporting' : $args['sharing'];
    $reportOptions = array_merge(
      iform_report_get_report_options($args, $auth['read']),
      [
        'reportGroup' => 'dynamic',
        'sharing' => $sharing,
      ]
    );
    // Ensure supplied extraParams are merged, not overwritten.
    if (!empty($options['extraParams'])) {
      $options['extraParams'] = array_merge($reportOptions['extraParams'], $options['extraParams']);
    }
    $reportOptions = array_merge($reportOptions, $options);
    if (self::$applyUserPrefs) {
      iform_report_apply_explore_user_own_preferences($reportOptions);
    }
    report_helper::request_report($response, $reportOptions, $currentParamValues, FALSE);
    report_helper::$javascript .= "indiciaData.$options[name] = " . json_encode($response['records']) . ";\n";
    return '';
  }

  /*
   * Report chart control.
   * Currently take its parameters from $options in the Form Structure.
   */
  protected static function get_control_reportchart($auth, $args, $tabalias, $options) {
    if (!isset($options['chartType'])||!isset($options['yValues'])||!isset($options['dataSource'])
        ||(!isset($options['xLabels']) && !isset($options['xValues']))) {
      return '<p>Please fill in the following options for the chart parameters control: chartType, dataSource,
            yValues and either xLabels or xValues.</p>';
    }
    if (isset($options['xLabels']) && isset($options['xValues'])) {
      return '<p>Please provide either a value for xLabels or xValues.</p>';
    }
    iform_load_helpers(array('report_helper'));
    $args['report_name'] = '';
    $reportOptions = array_merge(
      iform_report_get_report_options($args, $auth['read']),
      array(
        'id' => 'chart-' . self::$reportCount,
        'reportGroup' => 'dynamic',
        'width' => '100%',
        'height' => 500,
        'autoParamsForm' => FALSE,
      )
    );
    // ensure supplied extraParams are merged, not overwritten
    if (!empty($options['extraParams']))
      $options['extraParams'] = array_merge($reportOptions['extraParams'], $options['extraParams']);
    $reportOptions = array_merge($reportOptions, $options);
    // values and labels should be provided as a json array, but just in case it is a comma separated list
    if (!is_array($reportOptions['yValues']))
      $reportOptions['yValues']=explode(',', trim($reportOptions['yValues']));
    if (!empty($reportOptions['xValues']) && !is_array($reportOptions['xValues']))
      $reportOptions['xValues']=explode(',', $reportOptions['xValues']);
    if (!empty($reportOptions['xLabels']) && !is_array($reportOptions['xLabels']))
      $reportOptions['xLabels']=explode(',', $reportOptions['xLabels']);

    return report_helper::report_chart($reportOptions);
  }

  /*
   * Report chart params control.
   * Currently take its parameters from $options in the Form Structure.
   */
  protected static function get_control_reportchartparams($auth, $args, $tabalias, $options) {
    if (!isset($options['yValues'])||!isset($options['dataSource'])||!isset($options['chartType'])) {
      $r = '<h4>Please fill in the following options for the chart parameters control: yValues, dataSource, chartType</h4>';
      return $r;
    }
    iform_load_helpers(array('report_helper'));
    $sharing=empty($args['sharing']) ? 'reporting' : $args['sharing'];
    $args['report_name']='';
    $options = array_merge(
      iform_report_get_report_options($args, $auth),
      $options,
      array(
        'reportGroup' => 'dynamic',
        //as we aren't returning the report set paramsOnly
        'paramsOnly'=>TRUE,
        'sharing'=>$sharing,
        'paramsFormButtonCaption'=>lang::get('Filter'),
        'yValues'=>explode(',', $options['yValues']),
        'readAuth'=>$auth['read'],
        'dataSource'=>$options['dataSource'],
      )
    );
    $r = '<br/>'.report_helper::report_chart($options);
    return $r;
  }

  protected static function get_control_standardparams($auth, $args, $tabalias, $options) {
    self::$applyUserPrefs = FALSE;
    $options = array_merge(array(
      'allowSave' => TRUE,
      'sharing' => empty($args['sharing']) ? 'reporting' : $args['sharing'],
    ), $options);
    if ($args['redirect_on_success']) {
      $options['redirect_on_success'] = hostsite_get_url($args['redirect_on_success']);
    }
    // Any preset params on the report page should be loaded as initial
    // settings for the filter.
    if (!empty($args['param_presets'])) {
      $params = data_entry_helper::explode_lines_key_value_pairs($args['param_presets']);
      foreach ($params as $key => $val) {
        if (!isset($options["filter-$key"])) {
          $options["filter-$key"] = $val;
        }
      }
    }
    foreach ($options as $key => &$value) {
      $value = apply_user_replacements($value);
    }
    if ($options['allowSave'] && !function_exists('iform_ajaxproxy_url')) {
      return 'The AJAX Proxy module must be enabled to support saving filters. Set @allowSave=false to disable this in the [standard params] control.';
    }
    if (!function_exists('hostsite_get_user_field') || !hostsite_get_user_field('indicia_user_id')) {
      // If not logged in and linked to warehouse, we can't use standard params
      // functionality like saving, so...
      return '';
    }
    $hiddenStuff = '';
    $r = report_filter_panel($auth['read'], $options, $args['website_id'], $hiddenStuff);
    return $r . $hiddenStuff;
  }

  /**
   * Ajax handler to provide the content for the details of a single record.
   */
  public static function ajax_get_feature_popup_details($website_id, $password, $nid) {
    require_once 'extensions/misc_extensions.php';
    // TODO extend to include images back from report.
    $columns = [
      'taxon',
      'source',
      'date',
      'date_start',
      'date_end',
      'date_type',
      'recorder',
    ];
    $params = hostsite_get_node_field_value($nid, 'params');
    $details_report = 'library/occurrences/filterable_explore_list';
    iform_load_helpers(array('report_helper'));
    $readAuth = report_helper::get_read_auth($website_id, $password);
    $options = [
      'dataSource' => $details_report,
      'readAuth' => $readAuth,
      'sharing' => 'verification',
      'extraParams' => [
        'idlist' => $_GET['occurrence_ids'],
        'columns' => implode(',', $columns),
      ],
    ];
    $reportData = report_helper::get_report_data($options);
    // Set some values which must exist in the record.
    if (count($reportData) === 0) {
      echo '';
      return;
    }
    header('Content-type: application/json');
    echo json_encode($reportData);
  }

}
