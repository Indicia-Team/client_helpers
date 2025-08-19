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

/*
 * Future enhancements:
 * Aggregate sample based attrs?
 * Extand to allow user to select between line and bar charts.
 * Extend Header processing on table to allow configuration so that user can choose whether to have week numbers or dates.
 * Extend X label processing on chart to allow configuration so that user can choose whether to have week numbers or dates.
 * Put grid downloads in as data urls
 * Add titles to summary, estimates and raw tables and charts - if only when copying to clipboard
 */
require_once 'includes/form_generation.php';
require_once 'includes/report.php';
require_once 'includes/user.php';

/**
 * Prebuilt Indicia data form that lists the output of any report.
 */
class iform_report_calendar_summary_2 implements PrebuiltFormInterface {

  /* This is the URL parameter used to pass the user_id filter through */
  private static $dataSet = 'dataSet';

  /* This is the URL parameter used to pass the location_id filter through */
  private static $locationKey = 'locationID';

  /* This is the URL parameter used to pass the location_type_id filter through */
  private static $locationTypeKey = 'location_type_id';

  /* This is the URL parameter used to pass the year filter through */
  private static $yearKey = 'year';

  /* This is the URL parameter used to pass the caching filter through */
  private static $cacheKey = 'caching';

  // internal key, not used on URL: maps the location_id to the survey_id.
  private static $SurveyKey = 'survey_id';

  // internal key, not used on URL: maps the location_id to a url extension.
  private static $URLExtensionKey = 'URLExtension';

  private static $removableParams = [];

  private static $siteUrlParams = [];

  private static $branchLocationList = [];

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_report_calendar_summary_2_definition() {
    return [
      'title' => 'Report Calendar Summary 2',
      'category' => 'Reporting',
      'description' => 'Outputs a grid of sumary data loaded from the Summary Builder Module. Can be displayed as a table, or a line or bar chart.',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public static function getPageType(): PageType {
    return PageType::Report;
  }

  /* Installation/configuration notes
   * get_css function is now reducdant: use form arguments to add link to report_calendar_summary_2.css file
   * */
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    $params =
      [
        [
          'name' => 'taxon_column',
          'caption' => 'Display Taxon field',
          'description' => 'When displaying a taxon, choose what to use.',
          'type' => 'select',
          'lookupValues' => [
            'common_name' => 'Common Name',
            'preferred_taxon' => 'Preferred Taxon (usually Latin)'
          ],
          'required' => TRUE,
          'default' => 'taxon',
          'group' => 'General Settings'
        ],
      ];
    // EBMS only
    if (\Drupal::moduleHandler()->moduleExists('ebms_scheme')) {
      $params = array_merge($params,
        [
          [
            'name' => 'taxon_column_overrides',
            'caption' => 'Display Taxon field overrides',
            'description' => 'If user is logged in with one of these schemes, then display common names to them in their own language ' .
                'instead of using the "Display Taxon Field" selection. ' .
                'Supply as JSON where the key is the scheme ID, and the value is the Warehouse language ID to use ' .
                'e.g. {"1" : "2", "3" : "4"} means scheme 1 should use language 2, and scheme 3 should use language 4.',
            'type' => 'textarea',
            'required' => FALSE,
            'group'=>'General Settings'
          ]
        ]
      );
    }
    $params = array_merge($params,
      [
        [
          'name' => 'information_block',
          'caption' => 'Report information',
          'description' => 'Text to be included as a pop-up from an appropriate link.',
          'type' => 'textarea',
          'required' => FALSE,
          'group'=>'General Settings'
        ],

        [
          'name' => 'includeRawData',
          'caption' => 'Include raw data',
          'description' => 'Defines whether to include raw data in the chart/grid.',
          'type' => 'boolean',
          'required' => FALSE,
          'default' => TRUE,
          'group' => 'Data Inclusion'
        ],
        [
          'name' => 'includeSummaryData',
          'caption' => 'Include summary data',
          'description' => 'Defines whether to include summary data in the chart/grid.',
          'type' => 'boolean',
          'required' => FALSE,
          'default' => TRUE,
          'group' => 'Data Inclusion'
        ],
        [
          'name' => 'includeEstimatesData',
          'caption' => 'Include estimates data',
          'description' => 'Define whether to include summary data with estimates in the chart/grid.',
          'type' => 'boolean',
          'required' => FALSE,
          'default' => FALSE,
          'group' => 'Data Inclusion'
        ],


        [
          'name' => 'manager_permission',
          'caption' => 'Drupal Permission for Super Manager mode',
          'description' => 'Enter the Drupal permission name to be used to determine if this user is a super manager (i.e. full access to full data set). This primarily determines the functionality of the User and Location filters, if selected.',
          'type' => 'string',
          'required' => FALSE,
          'group' => 'Access Control'
        ],
        [
          'name' => 'scheme_manager_permission',
          'caption' => 'Drupal Permission for Scheme Coordinator mode',
          'description' => 'Enter the Drupal permission name to be used to determine if this user is a Scheme Coordinator. This primarily determines the functionality of the User and Location filters, if selected.',
          'type' => 'string',
          'required' => FALSE,
          'group' => 'Access Control'
        ],
        [
          'name' => 'scheme_manager_can_see',
          'caption' => 'Scheme manager can see',
          'description' => 'Scheme manager can see up to and including',
          'type' => 'select',
          'lookupValues' => ['scheme' => 'Scheme data', 'all' => 'Full data set'],
          'required' => FALSE,
          'default' => 'scheme',
          'group'=>'Access Control'
        ],
        [
          'name' => 'scheme_manager_summarised_data_level',
          'caption' => 'Scheme manager can see summarised data',
          'description' => 'Scheme manager can see summarised data from other people up to and including',
          'type' => 'select',
          'lookupValues' => ['scheme' => 'Scheme data', 'all' => 'Full data set'],
          'required' => FALSE,
          'default' => 'scheme',
          'group'=>'Access Control'
        ],
        [
          'name' => 'scheme_manager_other_sites_level',
          'caption' => 'Scheme manager can see other sites',
          'description' => 'Scheme manager can see other sites up to and including',
          'type' => 'select',
          'lookupValues' => ['scheme' => 'Scheme sites', 'all' => 'Full sites list'],
          'required' => FALSE,
          'default' => 'scheme',
          'group' => 'Access Control'
        ],
        // branch == regional for EBMS
        [
          'name' => 'branch_manager_permission',
          'caption' => 'Drupal Permission for Branch/Regional Coordinator mode',
          'description' => 'Enter the Drupal permission name to be used to determine if this user is a Branch/Regional Coordinator. This primarily determines the functionality of the User and Location filters, if selected.',
          'type' => 'string',
          'required' => FALSE,
          'group' => 'Access Control'
        ],
        [
          'name' => 'branch_manager_can_see',
          'caption' => 'Branch/Regional manager can see',
          'description' => 'Branch/Regional manager can see up to and including',
          'type' => 'select',
          'lookupValues' => ['branch' => 'Branch/Regional data', 'scheme' => 'Scheme data', 'all' => 'Full data set'],
          'required' => FALSE,
          'default' => 'branch',
          'group'=>'Access Control'
        ],
        [
          'name' => 'branch_manager_summarised_data_level',
          'caption' => 'Branch/Regional manager can see summarised data',
          'description' => 'Branch/Regional manager can see summarised data from other people up to and including',
          'type' => 'select',
          'lookupValues' => ['branch' => 'Branch/Regional data', 'scheme' => 'Scheme data', 'all' => 'Full data set'],
          'required' => FALSE,
          'default' => 'branch',
          'group'=>'Access Control'
        ],
        [
          'name' => 'branch_manager_other_sites_level',
          'caption' => 'Branch/Regional manager can see other sites',
          'description' => 'Branch/Regional manager can see other sites up to and including',
          'type' => 'select',
          'lookupValues' => ['branch' => 'Branch/Regional data', 'scheme' => 'Scheme sites', 'all' => 'Full sites list'],
          'required' => FALSE,
          'default' => 'branch',
          'group'=>'Access Control'
        ],
        [
          'name' => 'recorder_can_see',
          'caption' => 'Recorder can see',
          'description' => 'Recorder can see up to and including',
          'type' => 'select',
          'lookupValues' => ['own' => 'Own data', 'branch' => 'Branch/Regional data', 'scheme' => 'Scheme data', 'all' => 'Full data set'],
          'required' => FALSE,
          'default' => 'own',
          'group'=>'Access Control'
        ],
        [
          'name' => 'recorder_summarised_data_level',
          'caption' => 'Recorder can see summarised data',
          'description' => 'Recorder can see summarised data from other people up to and including',
          'type' => 'select',
          'lookupValues' => ['none' => 'None', 'own' => 'Own sites', 'branch' => 'Branch/Regional data', 'scheme' => 'Scheme data', 'all' => 'Full data set'],
          'required' => FALSE,
          'default' => 'own',
          'group'=>'Access Control'
        ],
        [
          'name' => 'recorder_other_sites_level',
          'caption' => 'Recorder can see other sites',
          'description' => 'Recorder can see other sites up to and including',
          'type' => 'select',
          'lookupValues' => ['none' => 'No other sites', 'branch' => 'Branch/Regional data', 'scheme' => 'Scheme sites', 'all' => 'Full sites list'],
          'required' => FALSE,
          'default' => 'own',
          'group' => 'Access Control'
        ],
        [
          'name' => 'countryAttribute',
          'caption' => 'Country Location attribute',
          'description' => 'Location attribute that stores the Country. Single value integer. (Scheme coordinator)',
          'type' => 'select',
          'table' => 'location_attribute',
          'valueField' => 'id',
          'captionField' => 'caption',
          'required' => FALSE,
          'group' => 'Access Control'
        ],
        [
          'name' => 'countryAttributeValue',
          'caption' => 'Country Location attribute value',
          'description' => 'Replacement string',
          'type' => 'string',
          'required' => FALSE,
          'group' => 'Access Control'
        ],
        [
          'name' => 'branchFilterAttribute',
          'caption' => 'Location Branch Coordinator Attribute',
          'description' => 'Location attribute used to assign locations to Branch/Region Coordinators. (Leave empty for EBMS Region coordinator - will use default location.id)',
          'type' => 'select',
          'table' => 'location_attribute',
          'valueField' => 'id',
          'captionField' => 'caption',
          'required' => FALSE,
          'group' => 'Access Control'
        ],
        [
          'name' => 'branchFilterValue',
          'caption' => 'Location Branch Coordinator Value',
          'description' => 'Location field value used to assign locations to Branch/Region Coordinators. (Leave empty for UKBMS Branch coordinator - will use default {id})',
          'type' => 'string',
          'required' => FALSE,
          'group' => 'Access Control'
        ],

        [
          'name' => 'dateFilter',
          'caption' => 'Date Filter type',
          'description' => 'Type of control used to select the start and end dates.',
          'type' => 'select',
          'options' => [
//            'none' => 'None',
            'year' => 'User selectable year'
          ],
          'default' => 'year',
          'group' => 'Controls'
        ],
        [
          'name' => 'first_year',
          'caption' => 'First Year of Data',
          'description' => 'Used to determine first year displayed in the year select control. Final Year will be current year.',
          'type' => 'int',
          'required' => FALSE,
          'group' => 'Controls'
        ],
        [
          'name' => 'locationTypesFilter',
          'caption' => 'Restrict locations to types',
          'description' => 'Implies a location type selection control. Comma separated list of the location types definitions to be included in the control, of form {Location Type Term}:{Survey ID}[:{Link URL Extension}]. Restricts the locations in the user specific location filter to the selected location type, and restricts the data retrieved to the defined survey. In the Raw Data grid, the Links to the data entry page have the optional extension added. The CMS User ID attribute must be defined for all location types selected or all location types.',
          'type' => 'string',
          'default' => FALSE,
          'required' => FALSE,
          'group' => 'Controls'
        ],
        [
          'name' => 'includeSrefInLocationFilter',
          'caption' => 'Include Sref in location filter name',
          'description' => 'When including the user specific location filter, choose whether to include the sref when generating the select name.',
          'type' => 'boolean',
          'default' => TRUE,
          'required' => FALSE,
          'group' => 'Controls'
        ],
        [
          'name' => 'removable_params',
          'caption' => 'Removable report parameters',
          'description' => 'Provide a list of any report parameters from the Preset Parameter Values list that can be set to a "blank" value by '.
            'use of a checkbox. For example the report might allow a taxon_list_id parameter to filter for a taxon list or to return all taxon list data '.
            'if an empty value is provided, so the taxon_list_id parameter can be listed here to provide a checkbox to remove this filter. Provide each '.
            'parameter on one line, followed by an equals then the caption of the check box, e.g. taxon_list_id=Check this box to include all species.',
          'type' => 'textarea',
          'required' => FALSE,
          'group' => 'Controls'
        ],
        [
          'name' => 'report_group',
          'caption' => 'Report group',
          'description' => 'When using several reports on a single page (e.g. <a href="http://code.google.com/p/indicia/wiki/DrupalDashboardReporting">dashboard reporting</a>) '.
          'you must ensure that all reports that share a set of input parameters have the same report group as the parameters report.',
          'type' => 'text_input',
          'default' => 'report',
          'group' => 'Controls'
        ],
        [
          'name' => 'remember_params_report_group',
          'caption' => 'Remember report parameters group',
          'description' => 'Enter any value in this parameter to allow the report to save its parameters for the next time the report is loaded. '.
          'The parameters are saved site wide, so if several reports share the same value and the same report group then the parameter '.
          'settings will be shared across the reports even if they are on different pages of the site. This functionality '.
          'requires cookies to be enabled on the browser.',
          'type' => 'text_input',
          'required'=>FALSE,
          'default' => '',
          'group' => 'Controls'
        ],

        [
          'name' => 'sampleFields',
          'caption' => 'Sample Fields',
          'description' => 'Comma separated list of the sample level fields in the report. Format is Caption:field. Use field = smpattr:<x> to run this '.
               'processing against a sample attribute.',
          'type' => 'string',
          'required' => FALSE,
          'group' => 'Raw Data Report Settings'
        ],
        [
          'name' => 'report_name',
          'caption' => 'Raw Data Report Name',
          'description' => 'Select the report to provide the raw data for this page. If not provided, then Raw Data will not be available.',
          'type' => 'report_helper::report_picker',
          'required' => FALSE,
          'group' => 'Raw Data Report Settings'
        ],
        [
          'name' => 'param_presets',
          'caption' => 'Raw Data Preset Parameter Values',
          'description' => 'To provide preset values for any report parameter and avoid the user having to enter them, enter each parameter into this '.
              'box one per line. Each parameter is followed by an equals then the value, e.g. survey_id=6. You can use {user_id} as a value which will be replaced by the '.
              'user ID from the CMS logged in user or {username} as a value replaces with the logged in username.',
          'type' => 'textarea',
          'required' => FALSE,
          'group' => 'Raw Data Report Settings'
        ],

        [
          'name' => 'includeRawGridDownload',
          'caption' => 'Raw Grid Download',
          'description' => 'Choose whether to include the ability to download the Raw data as a grid. The inclusion of raw data is a pre-requisite for this. May not be required if copy to clipboard is sufficient.',
          'type' => 'boolean',
          'default' => FALSE,
          'required' => FALSE,
          'group' => 'Downloads'
        ],
        [
          'name' => 'includeSummaryGridDownload',
          'caption' => 'Summary Grid Download',
          'description' => 'Choose whether to include the ability to download the Summary data as a grid. The inclusion of Summary data is a pre-requisite for this. May not be required if copy to clipboard is sufficient.',
          'type' => 'boolean',
          'default' => FALSE,
          'required' => FALSE,
          'group' => 'Downloads'
        ],
        [
          'name' => 'includeEstimatesGridDownload',
          'caption' => 'Estimates Grid Download',
          'description' => 'Choose whether to include the ability to download the Estimates data as a grid. The inclusion of Estimates data is a pre-requisite for this. May not be required if copy to clipboard is sufficient.',
          'type' => 'boolean',
          'default' => FALSE,
          'required' => FALSE,
          'group' => 'Downloads'
        ],
        [
          'name' => 'download_reports',
          'caption' => 'Download reports',
          'description' => 'Definition of reports to be used on the downloads tab.',
          'type' => 'jsonwidget',
          'schema' =>
'{
  "type":"seq",
  "title":"Download reports configuration list",
  "sequence":
  [
    {
      "type":"map",
      "title":"Download report configuration",
      "mapping": {
        "caption": {"type":"str","title":"Caption","desc":"Caption","required":true},
        "report": {"type":"str","title":"Report","desc":"Report","required":true},
        "format": {"type":"str","desc":"Report format","enum":["json","xml","csv","tsv","nbn","gpx","kml"],"required":true},
        "permission": {"type":"str","title":"Permission","desc":"Permission"},
        "presets": {
          "type":"seq",
          "title":"Presets",
          "sequence": [{
            "type":"map",
            "title":"Preset",
            "mapping": {
                "parameter": {"type":"str","desc":"Parameter"},
                "value": {"type":"str","desc":"Value"},
            },
          }],
        },
      },
    }
  ]
}',
          'required' => FALSE,
          'group' => 'Downloads'
        ],

        [
          'name' => 'includeFilenameTimestamps',
          'caption' => 'Include Timestamp in Filename',
          'description' => 'Include a Timestamp (YYYYMMDDHHMMSS) in the download filenames.',
          'type' => 'boolean',
          'default' => FALSE,
          'required' => FALSE,
          'group' => 'Downloads'
        ],

        [
          'name' => 'weekNumberFilter',
          'caption' => 'Restrict displayed weeks',
          'description' => 'Restrict displayed weeks to between 2 weeks defined by their week numbers. Colon separated.<br />'.
                         'Leaving an empty value means the end of the year. Blank means no restrictions.<br />'.
                         'Examples: "1:30" - Weeks one to thirty inclusive. "4:" - Week four onwards. ":5" - Upto and including week five.',
          'type' => 'string',
          'required' => FALSE,
          'group' => 'Date Axis Options'
        ],
        [
          'name' => 'inSeasonFilter',
          'caption' => 'In-season weeks',
          'description' => 'Optional colon separated number pair. Used to produce an additional In-season total column in Estimates grid. <br />' .
            'Leave blank to omit the column. If provided, both numbers must be given.<br />' .
            'Examples: "1:26" - Weeks one to twemty-six inclusive.',
          'type' => 'string',
          'required' => FALSE,
          'group' => 'Date Axis Options'
        ],

        [
          'name' => 'tableHeaders',
          'caption' => 'Type of header rows to include in the table output',
          'description' => 'Choose whether to include either the week comence date, week number or both as rows in the table header for each column.',
          'type' => 'select',
          'options' => [
            'date' => 'Date Only',
            'number' => 'Week number only',
            'both' => 'Both'
          ],
          'group' => 'Table Options'
        ],
        [
          'name' => 'linkURL',
          'caption' => 'Link URL',
          'description' => 'Used when generating link URLs to associated samples. If not included, no links will be generated.',
          'type' => 'string',
          'required' => FALSE,
          'group' => 'Raw Data Report Settings'
        ],
        [
          'name' => 'chartType',
          'caption' => 'Chart Type',
          'description' => 'Type of chart.',
          'type' => 'select',
          'lookupValues' => ['line'=>lang::get('Line'), 'bar'=>lang::get('Bar')],
          'required' => TRUE,
          'default' => 'line',
          'group' => 'Chart Options'
        ],
        [
          'name' => 'chartLabels',
          'caption' => 'Chart X-axis labels',
          'description' => 'Choose whether to have either the week commence date or week number as the chart X-axis labels.',
          'type' => 'select',
          'options' => [
            'date' => 'Date Only',
            'number' => 'Week number only'
          ],
          'group' => 'Chart Options'
        ],
        [
          'name' => 'includeChartTotalSeries',
          'caption' => 'Include Total Series',
          'description' => 'Choose whether to generate a series which gives the totals for each week.',
          'type' => 'boolean',
          'default' => TRUE,
          'required' => FALSE,
          'group' => 'Chart Options'
        ],
        [
          'name' => 'includeChartItemSeries',
          'caption' => 'Include Item Series',
          'description' => 'Choose whether to individual series for the counts of each species for each week on the charts. Summary (with optional estimates) data only.',
          'type' => 'boolean',
          'default' => TRUE,
          'required' => FALSE,
          'group' => 'Chart Options'
        ],
        [
          'name' => 'width',
          'caption' => 'Chart Width',
          'description' => 'Width of the output chart in pixels: if not set then it will automatically to fill the space.',
          'type' => 'text_input',
          'required' => FALSE,
          'group' => 'Chart Options'
        ],
        [
          'name' => 'height',
          'caption' => 'Chart Height',
          'description' => 'Height of the output chart in pixels.',
          'type' => 'text_input',
          'required' => TRUE,
          'default' => 500,
          'group' => 'Chart Options'
        ],
        [
          'name' => 'disableableSeries',
          'caption' => 'Switchable Series',
          'description' => 'User can switch off display of individual Series.',
          'type' => 'boolean',
          'required' => FALSE,
          'default' => TRUE,
          'group' => 'Chart Options'
        ],
        [
          'name' => 'renderer_options',
          'caption' => 'Renderer Options',
          'description' => 'Editor for the renderer options to pass to the chart. For full details of the options available, '.
              'see <a href="http://www.jqplot.com/docs/files/plugins/jqplot-barRenderer-js.html">bar chart renderer options</a> or '.
              '<a href="http://www.jqplot.com/docs/files/plugins/jqplot-lineRenderer-js.html">line charts rendered options<a/>.',
          'type' => 'jsonwidget',
          'schema' => '{
  "type":"map",
  "title":"Renderer Options",
  "mapping":{
    "barPadding":{"title":"Bar Padding", "type":"int","desc":"Number of pixels between adjacent bars at the same axis value."},
    "barMargin":{"title":"Bar Margin", "type":"int","desc":"Number of pixels between groups of bars at adjacent axis values."},
    "barDirection":{"title":"Bar Direction", "type":"str","desc":"Select vertical for up and down bars or horizontal for side to side bars","enum":["vertical","horizontal"]},
    "barWidth":{"title":"Bar Width", "type":"int","desc":"Width of the bar in pixels (auto by devaul)."},
    "shadowOffset":{"title":"Bar Slice Shadow Offset", "type":"number","desc":"Offset of the shadow from the slice and offset of each succesive stroke of the shadow from the last."},
    "shadowDepth":{"title":"Bar Slice Shadow Depth", "type":"int","desc":"Number of strokes to apply to the shadow, each stroke offset shadowOffset from the last."},
    "shadowAlpha":{"title":"Bar Slice Shadow Alpha", "type":"number","desc":"Transparency of the shadow (0 = transparent, 1 = opaque)"},
    "waterfall":{"title":"Bar Waterfall","type":"bool","desc":"Check to enable waterfall plot."},
    "groups":{"type":"int","desc":"Group bars into this many groups."},
    "varyBarColor":{"type":"bool","desc":"Check to color each bar of a series separately rather than have every bar of a given series the same color."},
    "highlightMouseOver":{"type":"bool","desc":"Check to highlight slice, bar or filled line plot when mouse over."},
    "highlightMouseDown":{"type":"bool","desc":"Check to highlight slice, bar or filled line plot when mouse down."},
    "highlightColors":{"type":"seq","desc":"An array of colors to use when highlighting a bar.",
        "sequence":[{"type":"str"}]
    },
    "highlightColor":{"type":"str","desc":"A colour to use when highlighting an area on a filled line plot."}
  }
}',
          'required' => FALSE,
          'group' => 'Advanced Chart Options'
        ],
        [
          'name' => 'axes_options',
          'caption' => 'Axes Options',
          'description' => 'Editor for axes options to pass to the chart. Provide entries for yaxis and xaxis as required. '.
              'Applies to line and bar charts only. For full details of the options available, see '.
              '<a href="http://www.jqplot.com/docs/files/jqplot-core-js.html#Axis">chart axes options</a>. '.
              'For example, <em>{"yaxis":{"min":0,"max":100}}</em>.',
          'type' => 'jsonwidget',
          'required' => FALSE,
          'group' => 'Advanced Chart Options',
          'schema' => '{
  "type":"map",
  "title":"Axis options",
  "mapping":{
    "xaxis":{
      "type":"map",
      "mapping":{
        "show":{"type":"bool"},
        "tickOptions":{"type":"map","mapping":{
          "mark":{"type":"str","desc":"Tick mark type on the axis.","enum":["inside","outside","cross"]},
          "showMark":{"type":"bool"},
          "showGridline":{"type":"bool"},
          "isMinorTick":{"type":"bool"},
          "markSize":{"type":"int","desc":"Length of the tick marks in pixels.  For �cross� style, length will be stoked above and below axis, so total length will be twice this."},
          "show":{"type":"bool"},
          "showLabel":{"type":"bool"},
          "formatString":{"type":"str","desc":"Text used to construct the tick labels, with %s being replaced by the label."},
          "fontFamily":{"type":"str","desc":"CSS spec for the font-family css attribute."},
          "fontSize":{"type":"str","desc":"CSS spec for the font-size css attribute."},
          "textColor":{"type":"str","desc":"CSS spec for the color attribute."},
        }},
        "labelOptions":{"type":"map","mapping":{
          "label":{"type":"str","desc":"Label for the axis."},
          "show":{"type":"bool","desc":"Check to show the axis label."},
          "escapeHTML":{"type":"bool","desc":"Check to escape HTML entities in the label."},
        }},
        "min":{"type":"number","desc":"minimum value of the axis (in data units, not pixels)."},
        "max":{"type":"number","desc":"maximum value of the axis (in data units, not pixels)."},
        "autoscale":{"type":"bool","desc":"Autoscale the axis min and max values to provide sensible tick spacing."},
        "pad":{"type":"number","desc":"Padding to extend the range above and below the data bounds.  The data range is multiplied by this factor to determine minimum '.
            'and maximum axis bounds.  A value of 0 will be interpreted to mean no padding, and pad will be set to 1.0."},
        "padMax":{"type":"number","desc":"Padding to extend the range above data bounds.  The top of the data range is multiplied by this factor to determine maximum '.
            'axis bounds.  A value of 0 will be interpreted to mean no padding, and padMax will be set to 1.0."},
        "padMin":{"type":"numer","desc":"Padding to extend the range below data bounds.  The bottom of the data range is multiplied by this factor to determine minimum '.
            'axis bounds.  A value of 0 will be interpreted to mean no padding, and padMin will be set to 1.0."},
        "numberTicks":{"type":"int","desc":"Desired number of ticks."},
        "tickInterval":{"type":"number","desc":"Number of units between ticks."},
        "showTicks":{"type":"bool","desc":"Whether to show the ticks (both marks and labels) or not."},
        "showTickMarks":{"type":"bool","desc":"Wether to show the tick marks (line crossing grid) or not."},
        "showMinorTicks":{"type":"bool","desc":"Wether or not to show minor ticks."},
        "useSeriesColor":{"type":"bool","desc":"Use the color of the first series associated with this axis for the tick marks and line bordering this axis."},
        "borderWidth":{"type":"int","desc":"Width of line stroked at the border of the axis."},
        "borderColor":{"type":"str","desc":"Color of the border adjacent to the axis."},
        "syncTicks":{"type":"bool","desc":"Check to try and synchronize tick spacing across multiple axes so that ticks and grid lines line up."},
        "tickSpacing":{"type":"","desc":"Approximate pixel spacing between ticks on graph.  Used during autoscaling.  This number will be an upper bound, actual spacing will be less."}
      }
    },
    "yaxis":{
      "type":"map",
      "mapping":{
        "show":{"type":"bool"},
        "tickOptions":{"type":"map","mapping":{
          "mark":{"type":"str","desc":"Tick mark type on the axis.","enum":["inside","outside","cross"]},
          "showMark":{"type":"bool"},
          "showGridline":{"type":"bool"},
          "isMinorTick":{"type":"bool"},
          "markSize":{"type":"int","desc":"Length of the tick marks in pixels.  For �cross� style, length will be stoked above and below axis, so total length will be twice this."},
          "show":{"type":"bool"},
          "showLabel":{"type":"bool"},
          "formatString":{"type":"str","desc":"Text used to construct the tick labels, with %s being replaced by the label."},
          "fontFamily":{"type":"str","desc":"CSS spec for the font-family css attribute."},
          "fontSize":{"type":"str","desc":"CSS spec for the font-size css attribute."},
          "textColor":{"type":"str","desc":"CSS spec for the color attribute."},
        }},
        "labelOptions":{"type":"map","mapping":{
          "label":{"type":"str","desc":"Label for the axis."},
          "show":{"type":"bool","desc":"Check to show the axis label."},
          "escapeHTML":{"type":"bool","desc":"Check to escape HTML entities in the label."},
        }},
        "min":{"type":"number","desc":"minimum value of the axis (in data units, not pixels)."},
        "max":{"type":"number","desc":"maximum value of the axis (in data units, not pixels)."},
        "autoscale":{"type":"bool","desc":"Autoscale the axis min and max values to provide sensible tick spacing."},
        "pad":{"type":"number","desc":"Padding to extend the range above and below the data bounds.  The data range is multiplied by this factor to determine minimum '.
            'and maximum axis bounds.  A value of 0 will be interpreted to mean no padding, and pad will be set to 1.0."},
        "padMax":{"type":"number","desc":"Padding to extend the range above data bounds.  The top of the data range is multiplied by this factor to determine maximum '.
            'axis bounds.  A value of 0 will be interpreted to mean no padding, and padMax will be set to 1.0."},
        "padMin":{"type":"numer","desc":"Padding to extend the range below data bounds.  The bottom of the data range is multiplied by this factor to determine minimum '.
            'axis bounds.  A value of 0 will be interpreted to mean no padding, and padMin will be set to 1.0."},
        "numberTicks":{"type":"int","desc":"Desired number of ticks."},
        "tickInterval":{"type":"number","desc":"Number of units between ticks."},
        "showTicks":{"type":"bool","desc":"Whether to show the ticks (both marks and labels) or not."},
        "showTickMarks":{"type":"bool","desc":"Wether to show the tick marks (line crossing grid) or not."},
        "showMinorTicks":{"type":"bool","desc":"Wether or not to show minor ticks."},
        "useSeriesColor":{"type":"bool","desc":"Use the color of the first series associated with this axis for the tick marks and line bordering this axis."},
        "borderWidth":{"type":"int","desc":"Width of line stroked at the border of the axis."},
        "borderColor":{"type":"str","desc":"Color of the border adjacent to the axis."},
        "syncTicks":{"type":"bool","desc":"Check to try and synchronize tick spacing across multiple axes so that ticks and grid lines line up."},
        "tickSpacing":{"type":"","desc":"Approximate pixel spacing between ticks on graph.  Used during autoscaling.  This number will be an upper bound, actual spacing will be less."}
      }
    }
  }
}',
        ],

        [
          'name' => 'rowGroupColumn',
          'caption' => 'Vertical Axis',
          'description' => 'The column in the report which is used as the data series label.',
          'type' => 'string',
          'default' => 'taxon',
          'group' => 'Raw Data Report Settings'
        ],
        [
          'name' => 'rowGroupID',
          'caption' => 'Vertical Axis ID',
          'description' => 'The column in the report which is used as the data series id. This is used to pass the series displayed as part of a URL which has a restricted length.',
          'type' => 'string',
          'default' => 'taxon_meaning_id',
          'group' => 'Raw Data Report Settings'
        ],
        [
          'name' => 'countColumn',
          'caption' => 'Count Column',
          'description' => 'The column in the report which is used as the count associated with the occurrence. If not proviced then each occurrence has a count of one.',
          'type' => 'string',
          'required' => FALSE,
          'group' => 'Raw Data Report Settings'
        ],

        [
          'name' => 'sensitivityLocAttrId',
          'caption' => 'Location attribute used to filter out sensitive or confidential sites',
          'description' => 'A boolean location attribute, set to TRUE if a site is sensitive or confidential.',
          'type' => 'locAttr',
          'required' => FALSE,
          'group' => 'Controls'
        ],
      ],
    );
    return $params;
  }

    /**
   * When a form version is upgraded introducing new parameters, old forms will not get the defaults for the
   * parameters unless the Edit and Save button is clicked. So, apply some defaults to keep those old forms
   * working.
   */
  protected static function getArgDefaults($args) {

    if (!isset($args['includeRawData']) && !isset($args['includeSummaryData']) && !isset($args['includeEstimatesData'])) {
      $args['includeRawData'] = TRUE;
    }

    return $args;
  }

  /**
   * Retreives the options array required to set up a report according to the default
   * report parameters.
   * @param string $args
   * @param <type> $readAuth
   * @return string
   */
  private static function get_report_calendar_2_options($args, $readAuth) {
    $reportOptions = [
      'id' => 'report-summary',
      'dataSource' => $args['report_name'], // Raw data report name
      'mode' => 'report',
      'readAuth' => $readAuth,
      'extraParams' => get_options_array_with_user_data($args['param_presets']), // Raw data preset
      'canDownload' => FALSE,
      'downloadFilePrefix' => [],
      'reportGroup' => isset($args['report_group']) ? $args['report_group'] : '',
      'rememberParamsReportGroup' => isset($args['remember_params_report_group']) ? $args['remember_params_report_group'] : '',
      'paramsToExclude' => [],
      'paramDefaults' => [],
      'downloadExtraParams' => [] // Raw data preset
    ];
    $reportOptions['extraParams']['survey_id'] = self::$siteUrlParams[self::$SurveyKey]; // catch if not in presets: location_type control
    return $reportOptions;
  }

  // There are 2 options:
  // 1) easy login: in this case the user restriction is on created_by_id, and refers to the Indicia id
  // 2) non-easy login: in this case the user restriction is based on the CMS user ID sample attribute, and refers to the CMS ID.
  // It is left to the report called to handle the user_id parameter as appropriate.
  // The report helper does the conversion from CMS to Easy Login ID if appropriate, so the user_id passed into the
  // report helper is always the CMS one.
  // Locations are always assigned by a CMS user ID attribute, not by who created them.

  private static function set_up_survey($args, $readAuth)
  {
    self::get_site_url_params($args);
    $presets = get_options_array_with_user_data($args['param_presets']);
    if (isset($presets['survey_id'])) {
      self::$siteUrlParams[self::$SurveyKey]=$presets['survey_id'];
    }
    if (isset($args['locationTypesFilter']) && $args['locationTypesFilter']!="") {
      $types = explode(',',$args['locationTypesFilter']);
      $types1=[];
      $types2=[];
      foreach($types as $type){
        $parts = explode(':',$type);
        $types1[] = $parts[0];
        $types2[] = $parts;
      }
      $terms = self::get_sorted_termlist_terms(['read'=>$readAuth], 'indicia:location_types', $types1);
      $default = (self::$siteUrlParams[self::$locationTypeKey]['value'] == '' ? $terms[0]['id'] : self::$siteUrlParams[self::$locationTypeKey]['value']);
      self::$siteUrlParams[self::$locationTypeKey]['value'] = $default;
      for ($i = 0; $i < count($terms); $i++) {
        if ($terms[$i]['id'] == $default && count($types2[$i])>1 && $types2[$i][1]!='') {
          self::$siteUrlParams[self::$SurveyKey] = $types2[$i][1];
        }
        if ($terms[$i]['id'] == $default && count($types2[$i])>2 && $types2[$i][2]!='') {
          self::$siteUrlParams[self::$URLExtensionKey] = $types2[$i][2];
        }
      }
    }
    return isset(self::$siteUrlParams[self::$SurveyKey]);
  }

  // TODO
  // Alter summary_builder module to handle training flag.
  // Capture the SQL required to do a full rebuild
  // Alter all the reports for training

  // Order of options: mine, region, branch, country/scheme, all
  // Descriptions                                                                                             Control Values                  Data filters (User, location)                         Download Prefix
  // Normal user: Recorder Control = My Data (user=me); "Combine data for all recorders" (user=all)
  //   My Data: Available options in sites list:                                                              (dataSet = user:<N>)
  //       "Combine all my sites together";                                                                    (location = user:<N>)          U=<N>   L=0                                           User_N_
  //       (EBMS) N x "combine my region <Xn> sites together" (if N>1)                                         (location = myregion:<R>:<N>)  U=<N>   L=[Region sites] intersect [Users sites]      User_N_Region_R_
  //       (EBMS) N x my sites in region <Xn> (including sensitive); (optgroup)                                (location = site:<M>)          U=<N>   L=<M>                                         User_N_Site_M_
  //       (EBMS) my other sites (not in my regions) (including sensitive); (optgroup)                         (location = site:<M>)          U=<N>   L=<M>                                         User_N_Site_M_
  //       (UKBMS) any site assigned to me (including sensitive) (optgroup)                                    (location = site:<M>)          U=<N>   L=<M>                                         User_N_Site_M_
  //       FUTURE: any other site that I have recorded data against (including sensitive) (optgroup)
  //   Configurable: (EBMS) N x "Region <Xn> - all users" (regions allocated to me)                           (dataSet = region:<R>)
  //       Configurable: (EBMS) "combine all region <Xn> sites together"                                       (location = region:<R>)        U=0     L=[Region sites]                              Region_R_
  //       (EBMS) "Combine all my region <Xn> sites together";                                                 (location = myregion:<R>:<N>)  U=0     L=[Region sites] intersect [Users sites]      Region_R_User_N_
  //       (EBMS) my sites in region <Xn> (including sensitive); (optgroup)                                    (location = site:<M>)          U=0     L=<M>                                         Site_M_
  //       FUTURE?: Configurable: (EBMS) other sites in region <Xn> (excluding sensitive); (optgroup)
  //   Configurable: (EBMS) scheme "<Y> - all users"                                                          (dataSet = scheme:<S>)
  //       "Combine all my sites together";                                                                    (location = myscheme:<S>:<N>)  U=0     L=[Scheme sites] intersect [Users sites]      Scheme_S_User_N_
  //       any site assigned to me (including sensitive); (optgroup)                                           (location = site:<M>)          U=0     L=<M>                                         Site_M_
  //       Configurable: (EBMS) "combine all country/scheme <Y> sites together" (scheme allocated to me)       (location = scheme:<S>)        U=0     L=[Scheme sites]                              Scheme_S_
  //       FUTURE?: Configurable: (EBMS) any other site in my country/scheme (excluding sensitive) (optgroup)
  //       FUTURE?: Configurable: any other site (excluding sensitive); (optgroup)
  //   Configurable: "Full data set - all users"                                                              (dataSet = all)
  //       "Combine all my sites together";                                                                    (location = myall:<N>)         U=0     L=[Users sites]                               All_User_N_
  //       any site assigned to me (including sensitive); (optgroup)                                           (location = site:<M>)          U=0     L=<M>                                         Site_M_
  //       "Combine all sites together"                                                                        (location = all)               U=0     L=0                                           All_
  //       any other site (including sensitive); (optgroup)                                                    (location = site:<M>)          U=0     L=<M>                                         Site_M_
  //
  // EBMS Region user: My Data; N x Region Data; "Combine data for all recorders" (user=all)
  //   My Data: Available options in sites list:                                                              (dataSet = user:<N>)
  //       "Combine all my sites together"                                                                     (location = user:<N>)          U=<N>   L=0                                           User_N_
  //       N x "combine my region <Xn> sites together" (if N>1)                                                (location = myregion:<R>:<N>)  U=<N>   L=[Region sites] intersect [Users sites]      User_N_Region_R_
  //       N x my sites in region <Xn> (including sensitive) (optgroup)                                        (location = site:<M>)          U=<N>   L=<M>                                         User_N_Site_M_
  //       my other sites (not in my regions) (including sensitive); (optgroup)                                (location = site:<M>)          U=<N>   L=<M>                                         User_N_Site_M_
  //       FUTURE: any other site that I have recorded data against (including sensitive) (optgroup)
  //   N x "Region <Xn> - all users" (regions allocated to me)                                                (dataSet = region:<N>)
  //       "Combine all my region <Xn> sites together";                                                        (location = myregion:<R>:<N>)  U=0     L=[Region sites] intersect [Users sites]      Region_R_User_N_
  //       "combine all region <Xn> sites together"                                                            (location = region:<R>)        U=0     L=[Region sites]                              Region_R_
  //       all sites in region <Xn> (including sensitive, flag if allocated to me); (optgroup)                 (location = site:<M>)          U=0     L=<M>                                         Site_M_
  //   Scheme "<Y>" (scheme:<N>)                                                                              (dataSet = scheme:<S>)
  //       "Combine all my sites together";                                                                    (location = myscheme:<S>:<N>)  U=0     L=[Scheme sites] intersect [Users sites]      Scheme_S_User_N_
  //       any site assigned to me (including sensitive); (optgroup)                                           (location = site:<M>)          U=0     L=<M>                                         Site_M_
  //       Configurable: "combine all country/scheme <Y> sites together" (scheme allocated to me)              (location = scheme:<S>)        U=0     L=[Scheme sites]                              Scheme_S_
  //       Configurable: any site in my country/scheme (excluding sensitive) (optgroup)                        (location = site:<M>)          U=0     L=<M>                                         Site_M_
  //
  // EBMS Country/Scheme user: My Data; Region Data; "Combine data for all recorders" (user=all); specific user
  //   My Data: Available options in sites list:                                                              (dataSet = user:<N>)
  //       "Combine all my sites together"                                                                     (location = user:<N>)          U=<N>   L=0                                           User_N_
  //       N x "combine my region <Xn> sites together" (if N>1)                                                (location = myregion:<R>:<N>)  U=<N>   L=[Region sites] intersect [Users sites]      User_N_Region_R_
  //       N x my sites in region <Xn> (including sensitive) (optgroup)                                        (location = site:<M>)          U=<N>   L=<M>                                         User_N_Site_M_
  //       my other sites (not in my regions) (including sensitive); (optgroup)                                (location = site:<M>)          U=<N>   L=<M>                                         User_N_Site_M_
  //       FUTURE: any other site that I have recorded data against (including sensitive) (optgroup)
  //   N x "Region <Xn> - all users" (all regions in scheme - flag if allocated to me)                        (dataSet = region:<N>)
  //       "Combine all my region <Xn> sites together"; (if any)                                               (location = myregion:<R>:<N>)  U=0     L=[Region sites] intersect [Users sites]      Region_R_User_N_
  //       "combine all region <Xn> sites together" (flag if region allocated to me)                           (location = region:<R>)        U=0     L=[Region sites]                              Region_R_
  //       all sites in region <Xn> (including sensitive, flag if allocated to me); (optgroup)                 (location = site:<M>)          U=0     L=<M>                                         Site_M_
  //   Scheme "<Y>"                                                                                           (dataSet = scheme:<S>)
  //       "Combine all my sites together";                                                                    (location = myscheme:<S>:<N>)  U=0     L=[Scheme sites] intersect [Users sites]      Scheme_S_User_N_
  //       any site assigned to me (including sensitive); (optgroup)                                           (location = site:<M>)          U=0     L=<M>                                         Site_M_
  //       "combine all country/scheme <Y> sites together" (scheme allocated to me)                            (location = scheme:<S>)        U=0     L=[Scheme sites]                              Scheme_S_
  //       any site in my country/scheme (including sensitive) (optgroup)                                      (location = site:<M>)          U=0     L=<M>                                         Site_M_
  //   Specific User (optgroup):                                                                              (dataSet = user:<N>)
  //       "Combine all user's sites together"                                                                 (location = user:<N>)          U=<N>   L=0                                           User_N_
  //       N x "combine user's region <Xn> sites together" (if N>1)                                            (location = myregion:<R>:<N>)  U=<N>   L=[Region sites] intersect [Users sites]      User_N_Region_R_
  //       N x user's sites in region <Xn> (including sensitive) (optgroup)                                    (location = site:<M>)          U=<N>   L=<M>                                         User_N_Site_M_
  //       user's other sites (not in my regions) (including sensitive); (optgroup)                            (location = site:<M>)          U=<N>   L=<M>                                         User_N_Site_M_
  //       FUTURE: any other site that user has recorded data against (including sensitive) (optgroup)
  //
  // UKBMS Branch user: My Data; Branch Data (UKBMS); All Data
  //   My Data: Available options in sites list:                                                              (dataSet = user:<N>)
  //       "Combine all my sites together";                                                                    (location = user:<N>)          U=<N>   L=0                                           User_N_
  //       any site assigned to me (including sensitive) (optgroup)                                            (location = site:<M>)          U=<N>   L=<M>                                         User_N_Site_M_
  //       FUTURE: any other site that I have recorded data against (including sensitive) (optgroup)
  //  "Branch Data - all users"                                                                               (dataSet = branch)
  //       "Combine all my branch sites together"                                                              (location = branch)            U=0     L=[Users branch sites]                        Branch_N_
  //       any site assigned to me at a branch level (including sensitive); (optgroup)                         (location = site:<M>)          U=0     L=<M>                                         Site_M_
  //   Configurable: "Full data set - all users"                                                              (dataSet = all)
  //       "Combine all my sites together";                                                                    (location = myall:<N>)         U=0     L=[Users sites]                               All_User_N_
  //       any site assigned to me (including sensitive); (optgroup)                                           (location = site:<M>)          U=0     L=<M>                                         Site_M_
  //       Configurable: "Combine all sites together"                                                          (location = all)               U=0     L=0                                           All_
  //       Configurable: any other site (excluding sensitive); (optgroup)                                      (location = site:<M>)          U=0     L=<M>                                         Site_M_
  //
  // Supermanager user: My Data; Branch Data (UKBMS); All Data; Specific User
  //   My Data: Available options in sites list:                                                              (dataSet = user:<N>)
  //       "Combine all my sites together";                                                                    (location = user:<N>)          U=<N>   L=0                                           User_N_
  //       (EBMS) N x "combine my region <Xn> sites together" (if N>1)                                         (location = myregion:<R>:<N>)  U=<N>   L=[Region sites] intersect [Users sites]      User_N_Region_R_
  //       (EBMS) N x my sites in region <Xn> (including sensitive); (optgroup)                                (location = site:<M>)          U=<N>   L=<M>                                         User_N_Site_M_
  //       (EBMS) my other sites (not in my regions) (including sensitive); (optgroup)                         (location = site:<M>)          U=<N>   L=<M>                                         User_N_Site_M_
  //       (UKBMS) any site assigned to me (including sensitive) (optgroup)                                    (location = site:<M>)          U=<N>   L=<M>                                         User_N_Site_M_
  //       FUTURE: any other site that I have recorded data against (including sensitive) (optgroup)
  //   (UKBMS) "Branch Data - all users" (if any) (branch)                                                    (dataSet = branch)
  //       "Combine all my branch sites together" - all users.                                                 (location = branch)            U=0     L=[Users branch sites]                        Branch_N_
  //       any site assigned to me at a branch level (including sensitive); (optgroup)                         (location = site:<M>)          U=0     L=<M>                                         Site_M_
  //   (EBMS) N x "Region <Xn> - all users" (all regions)                                                     (dataSet = region:<N>)
  //       "Combine all my region <Xn> sites together"; (if any)                                               (location = myregion:<R>:<N>)  U=0     L=[Region sites] intersect [Users sites]      Region_R_User_N_
  //       "combine all region <Xn> sites together" (flag if region allocated to me)                           (location = region:<R>)        U=0     L=[Region sites]                              Region_R_
  //       all sites in region <Xn> (including sensitive, flag if allocated to me); (optgroup)                 (location = site:<M>)          U=0     L=<M>                                         Site_M_
  //   (EBMS) M x "<Ym>" - all users (all schemes)                                                            (dataSet = scheme:<S>)
  //       "Combine all my scheme <Ym> sites together"; (if any)                                               (location = myscheme:<S>:<N>)  U=0     L=[Scheme sites] intersect [Users sites]      Scheme_S_User_N_
  //       "combine all country/scheme <Ym> sites together" (scheme allocated to me)                           (location = scheme:<S>)        U=0     L=[Scheme sites]                              Region_R_
  //       all sites in country/scheme <Ym> (including sensitive, flag if allocated to me); (optgroup)         (location = site:<M>)          U=0     L=<M>                                         Site_M_
  //   (UKBMS) "Full data set - all users"                                                                    (dataSet = all)
  //       "Combine all my sites together";                                                                    (location = myall:<N>)         U=0     L=[Users sites]                               All_User_N_
  //       any site assigned to me (including sensitive); (optgroup)                                           (location = site:<M>)          U=0     L=<M>                                         Site_M_
  //       "Combine all sites together"                                                                        (location = all)               U=0     L=0                                           All_
  //       any other site (including sensitive); (optgroup)                                                    (location = site:<M>)          U=0     L=<M>                                         Site_M_
  //   Specific User:                                                                                         (dataSet = user:<N>)
  //       "Combine all user's sites together";                                                                (location = user:<N>)          U=<N>   L=0                                           User_N_
  //       FUTURE: "Combine all user's sites together - all users' walks";
  //       FUTURE: "Combine all user's branch together - all users' walks";
  //       (EBMS) N x "combine user's region <Xn> sites together" (if N>1)                                     (location = myregion:<R>:<N>)  U=<N>   L=[Region sites] intersect [Users sites]      User_N_Region_R_
  //       FUTURE: (EBMS) N x "combine user's region <Xn> sites together - all users' walks" (if N>1)
  //       (EBMS) N x user's sites in region <Xn> (including sensitive); (optgroup)                            (location = site:<M>)          U=<N>   L=<M>                                         User_N_Site_M_
  //       (EBMS) user's other sites (not in my regions) (including sensitive); (optgroup)                     (location = site:<M>)          U=<N>   L=<M>                                         User_N_Site_M_
  //       (UKBMS) any site assigned to user (including sensitive) (optgroup)                                  (location = site:<M>)          U=<N>   L=<M>                                         User_N_Site_M_
  //       FUTURE: any other site that user has recorded data against (including sensitive) (optgroup)

  // Configuration
  // 'see' determines what is in the data set control.
  // 'summarised' determines what is available as 'combined' in the location control
  // 'other' determines to what level a user can see others' sites.
  // EBMS Scheme User can see dataSets for Users assigned to the scheme
  // EBMS SuperUser can see dataSets for all Users
  // UKBMS SuperUser can see dataSets for all Users
  // Normal user can see up to and including: own, regional/branch, scheme, all                     : UKBMS=all : EBMS=scheme
  // Max level Normal user can see summarised data from all: None, own sites, regional, scheme, all : UKBMS=all : EBMS=scheme
  // Max level Normal user can see other sites: No other sites, regional, scheme, all sites         : UKBMS=all : EBMS=No other sites
  // Regional/Branch user can see up to and including (implies own): regional/branch, scheme, all   : UKBMS=all : EBMS=scheme
  // Max level Regional/Branch user can see summarized data from all: regional/branch, scheme, all  : UKBMS=all : EBMS=scheme
  // Max level Regional/Branch user can see other sites: regional/branch, scheme, all sites         : UKBMS=all : EBMS=regional/branch
  // Scheme user can see up to and including (implies own, regional/branch): scheme, all            : UKBMS=NA  : EBMS=scheme
  // Max level Scheme user can see summarized data from all: scheme, all                            : UKBMS=NA  : EBMS=scheme
  // Max level Scheme user can see other sites: scheme, all sites                                   : UKBMS=NA  : EBMS=scheme
  // Superuser can access full data lists: yes, no                                                  : UKBMS=yes : EBMS=no

  // TODO Go through UKBMS normal user, branch user (T.Munro), superuser, admin: check each option combination for data set/location, and the downloads: filename and data is produced (no errors)
  // TODO Go through EBMS normal user, region user, scheme user, superuser, admin: check each option combination for data set/location, and the downloads

  private static function location_control($args, $readAuth, $nid, &$options)
  {
    // note that when in user specific mode it returns the list currently assigned to the user: it does not give
    // locations which the user previously recorded data against, but is no longer allocated to.
    $ctrl = '';
    $isSuperManager = (!empty($args['manager_permission']) && hostsite_user_has_permission($args['manager_permission']));
    $isSchemeManager = (!empty($args['scheme_manager_permission']) && hostsite_user_has_permission($args['scheme_manager_permission']));
    $isBranchManager = (!empty($args['branch_manager_permission']) && hostsite_user_has_permission($args['branch_manager_permission']));
    $myId = hostsite_get_user_field('id');
    // Set up common data.
    $locationListArgs = [
      'extraParams' => array_merge(
        [
          'website_id' => $args['website_id'],
          'location_type_id' => '',
          'locattrs' => ''
        ],
      $readAuth),
      'readAuth' => $readAuth,
      'caching' => self::$siteUrlParams[self::$cacheKey]['value'],
      'dataSource' => 'library/locations/locations_list_exclude_sensitive'
    ];
    if (!empty($args['sensitivityLocAttrId'])) {
      $locationListArgs['extraParams']['sensattr'] = $args['sensitivityLocAttrId'];
      $locationListArgs['extraParams']['exclude_sensitive'] = 0;
    }
    $attrArgs = [
      'valuetable' => 'location_attribute_value',
      'attrtable' => 'location_attribute',
      'key' => 'location_id',
      'fieldprefix' => 'locAttr',
      'extraParams' => $readAuth,
      'survey_id' => self::$siteUrlParams[self::$SurveyKey],
      'caching' => self::$siteUrlParams[self::$cacheKey]['value']
    ];

    if (isset($args['locationTypesFilter']) && $args['locationTypesFilter'] !== "") {
      $types = explode(',',$args['locationTypesFilter']);
      $types1 = [];
      foreach ($types as $type) {
        $parts = explode(':',$type);
        $types1[] = $parts[0];
      }
      $terms = self::get_sorted_termlist_terms(['read' => $readAuth], 'indicia:location_types', $types1);
      if (empty(self::$siteUrlParams[self::$locationTypeKey]['value'])) {
        self::$siteUrlParams[self::$locationTypeKey]['value'] = $terms[0]['id'];
      }
      else if(!in_array(self::$siteUrlParams[self::$locationTypeKey]['value'], array_map(function($a){ return $a['id'];}, $terms))) {
        self::$siteUrlParams[self::$locationTypeKey]['value'] = $terms[0]['id'];
        hostsite_show_message(lang::get('{1} is not a valid site type for use with this form, defaulting to first in list.', self::$siteUrlParams[self::$locationTypeKey]['value']), 'warning');
      }
      $attrArgs['location_type_id'] = self::$siteUrlParams[self::$locationTypeKey]['value'];
      $locationListArgs['extraParams']['location_type_id'] = self::$siteUrlParams[self::$locationTypeKey]['value'];

      if (count($types)>1) {
        $lookUpValues = [];
        foreach ($terms as $termDetails) {
          $lookUpValues[$termDetails['id']] = $termDetails['term'];
        }
        $ctrlid = 'calendar-location-type-'.$nid;
        $ctrl .= data_entry_helper::select([
          'label' => lang::get('Site Type'),
          'id' => $ctrlid,
          'class' => 'sitetype-select',
          'fieldname' => self::$siteUrlParams[self::$locationTypeKey]['name'],
          'lookupValues' => $lookUpValues,
          'default' => self::$siteUrlParams[self::$locationTypeKey]['value']
        ]) . '</th><th>';
        self::set_up_control_change($ctrlid, self::$siteUrlParams[self::$locationTypeKey]['name'], [self::$locationKey]);
        $options['downloadFilePrefix']['type'] = preg_replace('/[^A-Za-z0-9]/i', '', $lookUpValues[self::$siteUrlParams[self::$locationTypeKey]['value']]);
      }
    }

    $locationAttributes = data_entry_helper::getAttributes($attrArgs, FALSE);
    $cmsAttr = extract_cms_user_attr($locationAttributes,FALSE);
    if (!$cmsAttr) {
      return lang::get('Location control: CMS User ID Attribute missing from locations.');
    }

    $dataSet = self::$siteUrlParams[self::$dataSet]['value'];
    $ctrlValue = self::$siteUrlParams[self::$locationKey]['value'];
    $parts = explode(':', $dataSet);
    $ctrlParts = explode(':', $ctrlValue);
    $siteIds = [];
    $optionList = [];
    $locationLists = [];

    // First get either my, or users, list of sites.
    if (count($parts) === 2 && $parts[0] === 'user') {
      $lookupUser = $parts[1];
      if (count($ctrlParts) === 2 && $ctrlParts[0] === 'user') {
        self::$siteUrlParams[self::$locationKey]['value'] = $ctrlValue = $dataSet;
        $ctrlParts[0] = $parts[1];
      }
    } else {
      $lookupUser = $myId;
    }

    if ($dataSet === 'branch') {
      $locationListArgs['extraParams']['locattrs'] = $args['branchFilterAttribute'];
      $locationListArgs['extraParams']['attr_location_' . $args['branchFilterAttribute']] = $myId;
      $myLabel = "My branch sites";
    } else {
      $locationListArgs['extraParams']['locattrs'] = $cmsAttr['attributeId'];
      $locationListArgs['extraParams']['attr_location_' . $cmsAttr['attributeId']] = $lookupUser;
      $myLabel = "My sites";
    }
    $locationList = report_helper::get_report_data($locationListArgs); // this is sorted by name
    if (isset($locationList['error'])) {
      return $locationList['error'] . print_r($locationListArgs, TRUE);
    }
    $siteIds = array_map(function($s) { return $s['id']; }, $locationList);
    if (count($locationList) > 0 && empty($args['countryAttribute']) && empty($args['branchFilterValue'])) {
      $optionList[] = [
        "caption" => lang::get($myLabel),
        "children" => array_map(function($s) { return ["value" => "site:{$s['id']}", "caption" => $s['name']]; }, $locationList),
        "index" => count($optionList)
      ];
    }
    unset($locationListArgs['extraParams']['attr_location_' . $cmsAttr['attributeId']]);

    $userLabel = ($parts[0] !== 'user' || $parts[1] == $myId ? 'all my' : 'user&apos;s');
    $userTitle = ($parts[0] !== 'user' || $parts[1] == $myId ? 'My' : 'User&apos;s');

    // need to have at least one valid location control option, even if returns no data.
    if ((count($parts) === 2 && ($parts[0] === 'user' || $parts[0] === 'region')) || $parts[0] === 'branch') {
      if ($parts[0] === 'user') {
        $optionList[] = [
          "value" => $dataSet,
          "caption" => lang::get("Combine data for {$userLabel} sites"),
          "index" => count($optionList)
        ];
      }
      if (!empty($args['branchFilterValue'])) {
        // EBMS Region Details : Get list of my regions
        $leftIds = $siteIds;
        // $locationListArgs['extraParams']['location_type_id'] = self::$siteUrlParams[self::$locationTypeKey]['value'];
        $locationListArgs['extraParams']['location_type_id'] = '';
        if (!empty($args['sensitivityLocAttrId'])) {
          $locationListArgs['extraParams']['exclude_sensitive'] = 0;
        }
        if ($parts[0] === 'user') {
          $locationListArgs['extraParams']['idlist'] = apply_user_replacements($args['branchFilterValue']);
        } else {
          $locationListArgs['extraParams']['idlist'] = $parts[1];
        }
        $regionLocations = [];
        $myOtherSitesLabel = lang::get("{$userTitle} sites");
        if ($locationListArgs['extraParams']['idlist'] !== '') {
          $regionLocations = report_helper::get_report_data($locationListArgs); // this is sorted by name
          if (isset($regionLocations['error'])) {
            return $regionLocations['error'] . print_r($regionLocations, TRUE);
          }
          foreach ($regionLocations as $region) {
            $regionLocationList = ebms_scheme_list_region_locations([$region['id']]);
            $locationLists["region:{$region['id']}"] = $regionLocationList;
            $fetchList = array_intersect($siteIds, $regionLocationList);
            if (count($fetchList) > 0) {
              if (count($regionLocations) > 1) {
                $optionList[] = [
                  "value" => "myregion:{$region['id']}:{$lookupUser}",
                  "caption" => lang::get("Combine {$userLabel} Region {1} sites together", $region['name']),
                  "index" => count($optionList)
                ];
              }
              $locationLists["myregion:{$region['id']}:{$lookupUser}"] = $fetchList;
              $locationListArgs['extraParams']['idlist'] = implode(',', $fetchList);
              if (!empty($args['sensitivityLocAttrId'])) {
                $locationListArgs['extraParams']['exclude_sensitive'] = 0;
              }
              $myRegionLocations = report_helper::get_report_data($locationListArgs); // this is sorted by name
              $optionList[] = [
                "caption" => lang::get("{$userTitle} sites in Region {1}", $region['name']),
                "children" => array_map(function($s) { return ["value" => "site:{$s['id']}", "caption" => $s['name']]; }, $myRegionLocations),
                "index" => count($optionList)
              ];
              $leftIds = array_diff($leftIds, $fetchList);
            }
            if ($parts[0] === 'region' && self::data_set_access($args, $isSuperManager, $isSchemeManager, $isBranchManager, 'summary', 'branch')) {
              $optionList[] = [
                "value" => "region:{$region['id']}",
                "caption" => lang::get("Combine all Region {1} sites together", $region['name']),
                "index" => count($optionList)
              ];
            }
            $fetchList = array_diff($regionLocationList, $fetchList);
            if ($parts[0] === 'region' && count($fetchList) > 0 && self::data_set_access($args, $isSuperManager, $isSchemeManager, $isBranchManager, 'other', 'branch')) {
              $locationListArgs['extraParams']['idlist'] = implode(',', $fetchList);
              if (!$isSuperManager && !empty($args['sensitivityLocAttrId'])) {
                $locationListArgs['extraParams']['exclude_sensitive'] = 1;
              }
              $otherRegionLocations = report_helper::get_report_data($locationListArgs); // this is sorted by name
              $optionList[] = [
                "caption" => lang::get('Other sites in Region {1}', $region['name']),
                "children" => array_map(function($s) { return ["value" => "site:{$s['id']}", "caption" => $s['name']]; }, $otherRegionLocations),
                "index" => count($optionList)
              ];
            }
          }
          $myOtherSitesLabel = lang::get($userTitle . ' other sites');
        }
        if ($parts[0] === 'user' && count($leftIds) > 0) {
          $locationListArgs['extraParams']['idlist'] = implode(',', $leftIds);
          $myRegionLocations = report_helper::get_report_data($locationListArgs); // this is sorted by name
          $optionList[] = [
            "caption" => lang::get($myOtherSitesLabel),
            "children" => array_map(function($s) { return ["value" => "site:{$s['id']}", "caption" => $s['name']]; }, $myRegionLocations),
            "index" => count($optionList)
          ];
        }
      } else {
        if ($dataSet === 'branch') {
          $optionList[] = [
            "value" => "branch",
            "caption" => lang::get("Combine data for all sites in branch"),
            "index" => count($optionList)
          ];
        }
      }
    }

    if ((count($parts) === 2 && $parts[0] === 'scheme') || $parts[0] === 'all') {
      if (!empty($args['countryAttribute'])) {
        // EBMS Scheme Details : user can only have one
        if ($parts[0] === 'scheme') {
          $schemes = ebms_scheme_list_all_schemes();
          $area = array_values(array_filter($schemes, function($scheme) use ($parts) { return $scheme['id'] == $parts[1]; }));
          if (count($area) > 0) {
            $locationListArgs['extraParams']['locattrs'] = $args['countryAttribute'];
            $locationListArgs['extraParams']['attr_location_' . $args['countryAttribute']] = $area[0]['country_id'];
          }
          $field = "scheme:" . $parts[1];
          $level = "scheme";
          $myLabel = "Combine all my {1} sites together";
          $mySitesLabel = "My sites in {1}";
          $otherSitesLabel = "Other sites in {1}";
          $allSitesLabel = "Combine all sites in {1}";
        } else {
          $area = [['id' => '', 'name' => '']];
          $field = $level = "all";
          $myLabel = "Combine all my sites";
          $mySitesLabel = "My sites";
          $otherSitesLabel = "Other sites";
          $allSitesLabel = "Combine all sites";
        }
        $leftIds = $siteIds;
        if (!empty($args['sensitivityLocAttrId'])) {
          $locationListArgs['extraParams']['exclude_sensitive'] = 0;
        }
        $locationListArgs['extraParams']['location_type_id'] = self::$siteUrlParams[self::$locationTypeKey]['value'];;
        if (count($area) > 0) {
          $areaLocationList = report_helper::get_report_data($locationListArgs); // this is sorted by name
          $areaLocationList = array_map(function ($loc) { return $loc['id']; }, $areaLocationList);
          $fetchList = array_intersect($siteIds, $areaLocationList);
          if (count($fetchList) > 0) {
            $locationListArgs['extraParams']['idlist'] = implode(',', $fetchList);
            $myAreaLocations = report_helper::get_report_data($locationListArgs); // this is sorted by name
            if (count($myAreaLocations) > 1) {
              $optionList[] = [
                "value" => "my{$field}:{$lookupUser}",
                "caption" => lang::get($myLabel, $area[0]['name']),
                "index" => count($optionList)
              ];
            }
            $locationLists["my{$field}"] = $fetchList;
            $optionList[] = [
              "caption" => lang::get($mySitesLabel, $area[0]['name']),
              "children" => array_map(function($s) { return ["value" => "site:{$s['id']}", "caption" => $s['name']]; }, $myAreaLocations),
              "index" => count($optionList)
            ];
            $leftIds = array_diff($leftIds, $fetchList);
          }
          if (self::data_set_access($args, $isSuperManager, $isSchemeManager, $isBranchManager, 'summary', $level)) {
            $optionList[] = [
              "value" => $field,
              "caption" => lang::get($allSitesLabel, $area[0]['name']),
              "index" => count($optionList)
            ];
            $locationLists[$field] = $areaLocationList;
          }
          $fetchList = array_diff($areaLocationList, $fetchList);
          if (count($fetchList) > 0 &&
              self::data_set_access($args, $isSuperManager, $isSchemeManager, $isBranchManager, 'other', $level)) {
            $locationListArgs['extraParams']['idlist'] = implode(',', $fetchList);
            if (!$isSuperManager && !empty($args['sensitivityLocAttrId'])) {
              $locationListArgs['extraParams']['exclude_sensitive'] = 1;
            }
            $otherAreaLocations = report_helper::get_report_data($locationListArgs); // this is sorted by name
            $optionList[] = [
              "caption" => lang::get($otherSitesLabel, $area[0]['name']),
              "children" => array_map(function($s) { return ["value" => "site:{$s['id']}", "caption" => $s['name']]; }, $otherAreaLocations),
              "index" => count($optionList)
            ];
          }
        }
      } else {
        if (count($siteIds) > 1) {
          $optionList[] = [
            "value" => "myall:{$lookupUser}",
            "caption" => lang::get("Combine data for {$userLabel} sites together"),
            "index" => count($optionList)
          ];
        }
        $optionList[] = [
          "value" => "all",
          "caption" => lang::get("Combine data for all sites together"),
          "index" => count($optionList)
        ];
        $locationListArgs['extraParams']['location_type_id'] = self::$siteUrlParams[self::$locationTypeKey]['value'];
        if (!$isSuperManager && !empty($args['sensitivityLocAttrId'])) {
          $locationListArgs['extraParams']['exclude_sensitive'] = 1;
        }
        unset($locationListArgs['extraParams']['idlist']);
        $myOtherLocations = report_helper::get_report_data($locationListArgs); // this is sorted by name
        $myOtherLocations = array_filter($myOtherLocations, function ($s) use ($siteIds) { return !in_array($s['id'], $siteIds);});
        if (count($myOtherLocations) > 0) {
          $optionList[] = [
            "caption" => lang::get('Other sites'),
            "children" => array_map(function($s) { return ["value" => "site:{$s['id']}", "caption" => $s['name']]; }, $myOtherLocations),
            "index" => count($optionList)
          ];
        }
      }
    }
    $cmp = function ($a, $b) { return !empty($a["children"]) && empty($b["children"]) ? 1 : ($a["index"] < $b["index"] ? -1 : 1); };
    usort($optionList, $cmp);
    // Check control value is valid (dataSet key is checked in dataSet control function)
    $ctrlOptions = call_user_func_array("array_merge",
        array_map(function ($a) {
            if (!empty($a['value'])) {
              return [$a['value']];
            } else {
              return array_map(function ($b) { return $b['value']; },
                $a['children']);
            }
          },
          $optionList));
    if (count($ctrlOptions) === 0) {
      $optionList = [[
        "value" => "none",
        "caption" => lang::get("&lt;No location option available&gt;"),
        "index" => 1
      ]];
      $ctrlValue = self::$siteUrlParams[self::$locationKey]['value'] = "none";
    } else if (!in_array($ctrlValue = self::$siteUrlParams[self::$locationKey]['value'], $ctrlOptions)) {
      // default data is the first in the list.
      if (!empty($optionList[0]['value'])) {
        hostsite_show_message(lang::get("The attempted location control value is not valid: setting to {1}", $optionList[0]['caption']), 'error');
        $ctrlValue = self::$siteUrlParams[self::$locationKey]['value'] = $optionList[0]['value'];
      } else {
        if (!empty($optionList[0]['children']) && !empty($optionList[0]['children'][0]['value'])) {
          hostsite_show_message(lang::get("The attempted location control value is not valid: setting to {1}", $optionList[0]['children'][0]['caption']), 'error');
          $ctrlValue = self::$siteUrlParams[self::$locationKey]['value'] = $optionList[0]['children'][0]['value'];
        } else {
          throw(lang::get("The attempted location control value is not valid: could not set default"));
        }
      }
    }
    // Build the control options
    $items = "";
    foreach($optionList as $topLevelOption) {
      if (empty($topLevelOption['children'])) {
        $items .= "<option value=\"{$topLevelOption['value']}\" " .
          ($ctrlValue == $topLevelOption['value'] ? 'selected="selected"' : '') .
          ">{$topLevelOption['caption']}</option>";
      } else {
        $items .= "<optgroup label=\"{$topLevelOption['caption']}\">" .
          implode("", array_map(
              function ($s) use ($ctrlValue) {
                return "<option value=\"{$s['value']}\" " .
                  ($ctrlValue == $s['value'] ? 'selected="selected"' : '') .
                  ">{$s['caption']}</option>";
              },
              $topLevelOption['children'])) .
          "</optgroup>";
      }
    }
    // setup params into report based on actual control values, not requested ones.
    // main options are used for the fetching of the data, extra params are used in the raw data and report downloads
    $locValueParts     = explode(':', self::$siteUrlParams[self::$locationKey]['value']);
    $dataSetValueParts = explode(':', self::$siteUrlParams[self::$dataSet]['value']);
    unset($options['extraParams']['location_id']);
    unset($options['summary_location_id']);
    unset($options['extraParams']['location_list']);
    $options['valid'] = TRUE;

    switch ($locValueParts[0]) {
      case 'user' :
        $options['summary_location_id'] = 0;
        // no additional download prefix: location control value = data set value = user:<N>
        break;
      case 'site' : // specified location.
        $options['summary_location_id'] = $locValueParts[1];
        if ($dataSetValueParts[0] !== "user") {
          unset($options['downloadFilePrefix']['dataset']);
        }
        $options['downloadFilePrefix']['location'] = lang::get('Site') . '_' . $locValueParts[1]; // could use site name?
        break;
      case 'branch' :
        $options['extraParams']['location_list'] = $siteIds;
        // no additional download prefix: data set value is complete
        break;
      case 'region' :
        $options['extraParams']['location_list'] = $locationLists[$ctrlValue];
        // no additional download prefix: location control value = data set value = region:<R>
        break;
      case 'myregion' :
        $options['extraParams']['location_list'] = $locationLists[$ctrlValue];
        if ($dataSetValueParts[0] === "user") {
          $options['downloadFilePrefix']['location'] = lang::get('Region').'_'.$locValueParts[1];
        } else {
          $options['downloadFilePrefix']['location'] = lang::get('User').'_'.$locValueParts[2];
        }
        break;
      case 'scheme' :
        $options['extraParams']['location_list'] = $locationLists[$ctrlValue];
        // no additional download prefix: location control value = data set value = scheme:<S>
        break;
      case 'myscheme' :
        // only appears in scheme dataset.
        $options['extraParams']['location_list'] = $locationLists[$ctrlValue];
        $options['downloadFilePrefix']['location'] = lang::get('User').'_'.$locValueParts[2];
        break;
      case 'myall' :
        $options['extraParams']['location_list'] = $siteIds;
        $options['downloadFilePrefix']['location'] = lang::get('User').'_'.$locValueParts[1];
        break;
      case 'all' :
        $options['summary_location_id'] = 0;
        // no additional download prefix: location control value = data set value = all
        break;
      case 'none' : // error case
      default :
        $options['valid'] = $options['canDownload'] = FALSE;
        break;
    }
    if (isset($options['extraParams']['location_list'])) {
      $options['extraParams']['location_list'] = implode(',', $options['extraParams']['location_list']);
    }
    // Generate the control.
    // The user option values are CMS User ID, not Indicia ID.
    // This implies that $siteUrlParams[self::$dataSet] is also CMS User ID.
    $ctrlid = 'calendar-location-select-'.$nid;
    $ctrlOptions = [
      'label' => lang::get('Filter by site'),
      'fieldname' => self::$siteUrlParams[self::$locationKey]['name'],
      'id' => $ctrlid,
      'class' => '',
      'multiple' => '',
      'items' => $items,
      'isFormControl' => TRUE
    ];
    $ctrl .= data_entry_helper::apply_template('select', $ctrlOptions);
    self::set_up_control_change($ctrlid, self::$locationKey, []);
    return $ctrl;
  }

  private static function data_set_access($args, $isSuperManager, $isSchemeManager, $isBranchManager, $type, $level) {
    if ($isSuperManager) {
      return TRUE;
    }
    if ($isSchemeManager) {
      $prefix = 'scheme_manager';
    } else if ($isBranchManager) {
      $prefix = 'branch_manager';
    } else {
      $prefix = 'recorder';
    }
    switch ($type) {
      case 'see' :
        $myLevel = $args[$prefix . '_can_see'];
        $order = ['own', 'branch', 'scheme', 'all'];
        break;
      case 'summary' :
        $myLevel = $args[$prefix . '_summarised_data_level'];
        $order = ['none', 'own', 'branch', 'scheme', 'all'];
        break;
      case 'other' :
        $myLevel = $args[$prefix . '_other_sites_level'];
        $order = ['none', 'branch', 'scheme', 'all'];
        break;
    }
    $requestedLevel = array_search($level, $order);
    $myLevel = array_search($myLevel, $order);
    return $myLevel >= $requestedLevel;
  }

  private static function data_set_control(&$args, $readAuth, $nid, &$options)
  {
    // data set filter is keyed on the CMS User ID; converted to cms_user_id/Indicia user_id pair by report_helper, if applicable.
    // we don't use the userID option as the user_id can be blank, and will force the parameter request if left as a blank

    $ctrl = '';
    $myId = hostsite_get_user_field('id');
    $isSuperManager = (!empty($args['manager_permission']) && hostsite_user_has_permission($args['manager_permission']));
    $isSchemeManager = (!empty($args['scheme_manager_permission']) && hostsite_user_has_permission($args['scheme_manager_permission']));
    $isBranchManager = (!empty($args['branch_manager_permission']) && hostsite_user_has_permission($args['branch_manager_permission']));
    $optionList = ["user:$myId" => lang::get('My data')];
    $allUserList = [];

    $locationListArgs = [
      'extraParams' => array_merge(
        [
          'website_id' => $args['website_id'],
          'location_type_id' => self::$siteUrlParams[self::$locationTypeKey]['value'],
          'locattrs' => ''
        ],
        $readAuth),
      'readAuth' => $readAuth,
      'caching' => self::$siteUrlParams[self::$cacheKey]['value'],
      'dataSource' => 'library/locations/locations_list_exclude_sensitive'
    ];
    if (!empty($args['sensitivityLocAttrId'])) {
      $locationListArgs['extraParams']['sensattr'] = $args['sensitivityLocAttrId'];
      $locationListArgs['extraParams']['exclude_sensitive'] = 0;
    }

    if (!empty($args['branchFilterAttribute']) && empty($args['branchFilterValue']) &&
        self::data_set_access($args, $isSuperManager, $isSchemeManager, $isBranchManager, 'see', 'branch')) {
      // UKBMS Branch Details : Get list of branch locations
      $locationListArgs['extraParams']['locattrs'] = $args['branchFilterAttribute'];
      $locationListArgs['extraParams']['attr_location_' . $args['branchFilterAttribute']] = $myId;
      $locationList = report_helper::get_report_data($locationListArgs); // this is sorted by name
      if (isset($locationList['error'])) {
        return $locationList['error'] . print_r($locationList, TRUE);
      }
      if (count($locationList) > 0) {
        $optionList["branch"] = lang::get('Branch data (all users)');
      }
      unset($locationListArgs['extraParams']['attr_location_' . $args['branchFilterAttribute']]);
      $locationListArgs['extraParams']['locattrs'] = '';
    }

    if (empty($args['branchFilterAttribute']) && !empty($args['branchFilterValue']) &&
        self::data_set_access($args, $isSuperManager, $isSchemeManager, $isBranchManager, 'see', 'branch')) {
      // EBMS Region Details : Get list of user's region locations - need name so have to fetch records
      $locationListArgs['extraParams']['location_type_id'] = '';
      $locationListArgs['extraParams']['idlist'] = apply_user_replacements($args['branchFilterValue']);
      if ($locationListArgs['extraParams']['idlist'] !== '') {
        $regionLocations = report_helper::get_report_data($locationListArgs); // this is sorted by name
        if (isset($regionLocations['error'])) {
          return $regionLocations['error'] . print_r($regionLocations, TRUE);
        }
        if (count($regionLocations) > 0) {
          foreach ($regionLocations as $region) {
            $optionList["region:" . $region['id']] = lang::get('Region {1} (all users)', $region['name']);
          }
        }
      }
    }

    if (function_exists('ebms_scheme_list_all_schemes') &&
        self::data_set_access($args, $isSuperManager, $isSchemeManager, $isBranchManager, 'see', 'all')) {
      $schemeList = ebms_scheme_list_all_schemes();
      foreach($schemeList as $scheme) {
        $optionList["scheme:" . $scheme['id']] = lang::get('{1} (all users)', $scheme['name']);
      }
    }
    else if (function_exists('ebms_scheme_list_user_schemes') &&
        self::data_set_access($args, $isSuperManager, $isSchemeManager, $isBranchManager, 'see', 'scheme')) {
      $schemeList = ebms_scheme_list_user_schemes($myId);
      foreach($schemeList as $scheme) {
        $optionList["scheme:" . $scheme['id']] = lang::get('{1} (all users)', $scheme['name']);
      }
    }

    if (self::data_set_access($args, $isSuperManager, $isSchemeManager, $isBranchManager, 'see', 'all')) {
      $optionList['all'] = lang::get('Full data set (all users)');
    }

    if($isSuperManager) {
      $results = \Drupal::database()->query('SELECT uid, name FROM {users_field_data} WHERE uid <> 0');
      foreach ($results as $result) {
        $allUserList["user:{$result->uid}"] = ["uid" => $result->uid, "name" => $result->name, "key" => "user:{$result->uid}"];
      }
    } else if ($isSchemeManager && function_exists('ebms_scheme_list_user_scheme_users')) {
      $schemeUserList = ebms_scheme_list_user_scheme_users($myId);
      $results = \Drupal::database()->query('SELECT uid, name FROM {users_field_data} WHERE uid <> 0');
      foreach ($results as $result) {
        if(in_array("user:{$result->uid}", array_keys($schemeUserList))) {
          $allUserList["user:{$result->uid}"] = ["uid" => $result->uid, "name" => $result->name, "key" => "user:{$result->uid}"];
        }
      }
    }
    $cmp = function ($a, $b) { return strnatcasecmp($a['name'], $b['name']); };
    usort($allUserList, $cmp);
    // Check that selected data set is in the list of options: if not default to My Data.
    if (empty($optionList[self::$siteUrlParams[self::$dataSet]['value']]) &&
        !in_array(self::$siteUrlParams[self::$dataSet]['value'], array_map(function ($a) { return $a["key"]; }, $allUserList))) {
      hostsite_show_message(lang::get('You do not have sufficient privileges to see the {1} data set - defaulting to your own data.', self::$siteUrlParams[self::$dataSet]['value']), 'warning');
      self::$siteUrlParams[self::$dataSet]['value'] = "user:$myId";
      // if the user is changed then we must reset the location
      self::$siteUrlParams[self::$locationKey]['value'] = "user:$myId";
    }

    // main options are used for the fetching of the data, extra params are used in the raw data and report downloads
    $parts = explode(':', self::$siteUrlParams[self::$dataSet]['value']);
    // data set can by user:N, branch; region:N, scheme:N, all
    // For a normal user, the data set restricts the locations to users own list. Restricted the user id.
    // For UKBMS branch, the data set restricts the locations to users own branch list. Data set Users are set to all.
    // For EBMS region, the data set restricts the locations to the region list. Data set Users are set to all.
    // For EBMS scheme, the data set restricts the locations to the scheme list. Data set Users are set to all.
    // For all, the data set restricts Nothing for locations. Data set Users are set to all.
    unset($options['extraParams']['user_id']);
    $options['summary_user_id'] = 0;
    unset($locationListArgs['extraParams']['idlist']);
    unset($options['extraParams']['location_id']);
    // The determination of the Location ID list for the various optinos can only be done after
    // the location type control has checked the location type parameter.
    switch($parts[0]) {
      case 'user':
        $options['extraParams']['user_id'] = $parts[1];
        $options['summary_user_id'] = hostsite_get_user_field('indicia_user_id', FALSE, FALSE, $options["extraParams"]['user_id']);
        $options['canDownload'] = TRUE;
        $options['downloadFilePrefix']['dataset'] = lang::get('User').'_'.$parts[1];
        break;
      case 'branch':
        $options['canDownload'] = $isSuperManager || $isBranchManager;
        $options['downloadFilePrefix']['dataset'] = lang::get('Branch').'_'.$myId;
        break;
      case 'region':
        $options['canDownload'] = $isSuperManager || $isSchemeManager || $isBranchManager;
        $options['downloadFilePrefix']['dataset'] = lang::get('Region').'_'.$parts[1];
        break;
      case 'scheme':
        $options['canDownload'] = $isSuperManager || $isSchemeManager;
        $options['downloadFilePrefix']['dataset'] = lang::get('Scheme').'_'.$parts[1];
        break;
      case 'all':
        $options['canDownload'] = $isSuperManager;
        $options['downloadFilePrefix']['dataset'] = lang::get('FullDataSet');
        break;
    }
    // Generate the control.
    // The option values are based on CMS User ID, not Indicia ID.
    // This implies that $siteUrlParams[self::$dataSet] is also CMS User ID.
    $ctrlid = 'calendar-data-select-'.$nid;
    $items = '';
    foreach($optionList as $key => $option) {
      $items .= '<option value="' . $key . '" ' . (self::$siteUrlParams[self::$dataSet]['value'] == $key ? 'selected="selected"' : '') . '>' .
        $option . '</option>';
    }
    if (count($allUserList) > 0) {
      $allUserList =  array_filter($allUserList, function($user) use ($myId) { return $user['uid'] !== $myId; });
      $items .= '<optgroup label="' . lang::get('Other users') . '">' .
        implode('', array_map(function($user) use ($args) {
            return '<option value="' . $user['key'] . '" ' .
              (self::$siteUrlParams[self::$dataSet]['value'] == $user['key'] ? 'selected="selected" ' : '') .
              '>' . $user['name'] . '</option>';
          }, $allUserList)) .
        '</optgroup>';
    }
    $ctrlOptions = [
      'label' => lang::get('Data set'),
      'fieldname' => self::$siteUrlParams[self::$dataSet]['name'],
      'id' => $ctrlid,
      'class' => '',
      'multiple'=>'',
      'items' => $items,
      'isFormControl' => TRUE
    ];
    $ctrl .= data_entry_helper::apply_template('select', $ctrlOptions);
    self::set_up_control_change($ctrlid, self::$dataSet, []);

    // If a normal user, switch off the links if not my Data.
    if (!$isSuperManager && !$isBranchManager && !$isSchemeManager && self::$siteUrlParams[self::$dataSet]['value'] !== "user:$myId") {
      unset($args['linkURL']);
      $options['linkMessage'] = '<p>'.
        lang::get('In order to have the column headings as links to the data entry pages for the Visit, you must set the &apos;Data set&apos; control to your data.') .
        '</p>';
    }
    // Also switch off links for branch manager if not branch data or own data: UKBMS style mode
    if (!$isSuperManager &&
        $isBranchManager &&
        self::$siteUrlParams[self::$dataSet]['value'] !== "user:$myId" &&
        self::$siteUrlParams[self::$dataSet]['value'] !== "branch" &&
        !function_exists('ebms_scheme_list_user_scheme_users')) {
      unset($args['linkURL']);
      $options['linkMessage'] = '<p>'.
        lang::get('In order to have the column headings as links to the data entry pages for the Visit, you must set the &apos;Data set&apos; control to your data or branch data.') .
        '</p>';
    }

    return $ctrl;
  }

  /**
   * Get the parameters required for the current filter.
   */
  private static function get_site_url_params($args) {
    if (!self::$siteUrlParams) {
      $locationTypeKey = (isset($args['report_group']) ? $args['report_group'].'-' : '').self::$locationTypeKey;
      self::$siteUrlParams = [
          self::$dataSet => [
          'name' => self::$dataSet,
          'value' => empty($_GET[self::$dataSet]) ? "user:".hostsite_get_user_field('id') : $_GET[self::$dataSet]
        ],
        self::$locationTypeKey => [
          'name' => $locationTypeKey,
          'value' => isset($_GET[$locationTypeKey]) ? $_GET[$locationTypeKey] : ''
        ],
        self::$yearKey => [
          'name' => self::$yearKey,
          'value' => isset($_GET[self::$yearKey]) ? $_GET[self::$yearKey] : date('Y')
        ],
        self::$cacheKey => [
          'name' => self::$cacheKey,
          'value' => (isset($_GET[self::$cacheKey]) && $_GET[self::$cacheKey] == 'FALSE')  ? 'store' : TRUE // cache by default
        ]
      ];
      self::$siteUrlParams[self::$locationKey] = [
          'name' => self::$locationKey,
          'value' => empty($_GET[self::$locationKey]) ?
                            self::$siteUrlParams[self::$dataSet]['value'] :
                            $_GET[self::$locationKey]
      ];
      $isSuperManager = (!empty($args['manager_permission']) && hostsite_user_has_permission($args['manager_permission']));
      $isSchemeManager = (!empty($args['scheme_manager_permission']) && hostsite_user_has_permission($args['scheme_manager_permission']));
      $isBranchManager = (!empty($args['branch_manager_permission']) && hostsite_user_has_permission($args['branch_manager_permission']));
      $parts = explode(':', self::$siteUrlParams[self::$dataSet]['value']);
      $invalidCombinations = [
        [
          "arg" => 'scheme_manager_can_see',
          "check" => $isSchemeManager && !$isSuperManager,
          "illegal"=> ["scheme" => ["all"]]
        ],
        [
          "arg" => 'branch_manager_can_see',
          "check" => $isBranchManager && !$isSchemeManager && !$isSuperManager,
          "illegal"=> ["scheme" => ["all"], "branch" => ["scheme", "all"]]
        ],
        [
          "arg" => 'recorder_can_see',
          "check" => !$isBranchManager && !$isSchemeManager && !$isSuperManager,
          "illegal"=> ["scheme" => ["all"], "branch" => ["scheme", "all"], "own" => ["branch", "region", "scheme", "all"]]
        ]
      ];
      $fallBack = "user:" . hostsite_get_user_field('id');
      if (!$isSuperManager) {
        foreach ($invalidCombinations as $combination) {
          if ($combination['check']) {
            if (empty($args[$combination['arg']])) {
              hostsite_show_message($combination['arg'] . ' form parameter not set up.', 'error');
              self::$siteUrlParams[self::$dataSet]['value'] = $fallBack;
            } else {
              foreach ($combination['illegal'] as $value => $combos) {
                if ($value == $args[$combination['arg']] && in_array($parts[0], $combos)) {
                  hostsite_show_message(lang::get('You do not have permission to see the {1} data set, defaulting to your own.', $parts[0]), 'warning');
                  self::$siteUrlParams[self::$dataSet]['value'] = $fallBack;
                  break;
                }
              }
            }
            break;
          }
        }
      }
      foreach (array_keys(self::$removableParams) as $param) {
        self::$siteUrlParams[$param] = [
          'name' => $param,
          'value' => isset($_GET[$param]) ? $_GET[$param] : ''
        ];
      }
      if (hostsite_get_cookie('providedParams') && !empty($args['remember_params_report_group'])) {
        $cookieData = json_decode(hostsite_get_cookie('providedParams'), TRUE);
        // guard against a corrupt cookie
        if (is_array($cookieData) && !empty($cookieData[$args['remember_params_report_group']])) {
          $cookieParams = $cookieData[$args['remember_params_report_group']];
          if (is_array($cookieParams) && isset($cookieParams[$locationTypeKey]) && self::$siteUrlParams[self::$locationTypeKey]['value'] == '') {
            self::$siteUrlParams[self::$locationTypeKey]['value'] = $cookieParams[$locationTypeKey];
          }
        }
      }
    }
    return self::$siteUrlParams;
  }

  private static function extract_attr(&$attributes, $caption, $unset=TRUE) {
    $found = FALSE;
    foreach ($attributes as $idx => $attr) {
      if (strcasecmp($attr['caption'], $caption)===0) { // should this be untranslated?
        // found will pick up just the first one
        if (!$found) {
          $found=$attr;
        }
        if ($unset) {
          unset($attributes[$idx]);
        } else {
          // don't bother looking further if not unsetting them all
          break;
        }
      }
    }
    return $found;
  }

  private static function set_up_control_change($ctrlid, $urlparam, $skipParams, $checkBox=FALSE) {
    // Need to use a global for pageURI as the internal controls may have changed, and we want
    // their values to be carried over.
    data_entry_helper::$javascript .= "
jQuery('#".$ctrlid."').on('change', function(){
  $.fancyDialog({ title: '" . lang::get("Loading...") . "',
    message: '" . lang::get("Please wait whilst the next set of data is loaded.") . "',
    cancelButton: null });\n";
  // no need to update other controls on the page, as we jump off it straight away.
    if($checkBox) {
      data_entry_helper::$javascript .= "  window.location = rebuild_page_url(pageURI, \"".$urlparam."\", jQuery(this).filter(':checked').length > 0 ? 'TRUE' : 'FALSE', ['".implode("','",$skipParams)."']);\n});\n";
    } else {
      data_entry_helper::$javascript .= "  window.location = rebuild_page_url(pageURI, \"".$urlparam."\", jQuery(this).val(), ['".implode("','",$skipParams)."']);\n});\n";
    }
  }

  private static function copy_args($args, &$options, $list){
    foreach ($list as $arg) {
      if (isset($args[$arg]) && $args[$arg]!="") {
        $options[$arg] = $args[$arg];
      }
    }
  }

  private static function year_control($args, $readAuth, $nid, &$options)
  {
    global $indicia_templates;
    $baseTheme = hostsite_get_config_value('iform.settings', 'base_theme', 'generic');
    $r = '';

    switch($args['dateFilter']){
      case 'none': return '';
      default: // case year
        // Add year paginator where it can have an impact for both tables and plots.
        $reloadUrl = data_entry_helper::get_reload_link_parts();
        // find the names of the params we must not include
        foreach ($reloadUrl['params'] as $key => $value) {
          if (!array_key_exists($key, self::$siteUrlParams)){
            $reloadUrl['path'] .= (strpos($reloadUrl['path'],'?') === FALSE ? '?' : '&') . "$key=$value";
          }
        }
        $param=(strpos($reloadUrl['path'],'?') === FALSE ? '?' : '&') . self::$yearKey.'=';

        if ($baseTheme === 'generic') {
          $r = '<th><a id="year-control-previous" title="' . (self::$siteUrlParams[self::$yearKey]['value']-1) . '" rel="nofollow" href="' . $reloadUrl['path'] . $param.(self::$siteUrlParams[self::$yearKey]['value']-1) . '" class="ui-datepicker-prev ui-corner-all"><span class="ui-icon ui-icon-circle-triangle-w">' . lang::get('Prev') . '</span></a></th>';
        } else {
          $oldWrap = $indicia_templates['controlWrap'];
          $indicia_templates['controlWrap'] =
            '<div id="ctrl-wrap-{id}" class="form-group ctrl-wrap">' .
            '<div class="input-group">' .
            '<div class="input-group-addon ctrl-addons">' .
            '<a id="year-control-previous" title="' . (self::$siteUrlParams[self::$yearKey]['value']-1) . '" rel="nofollow" href="' . $reloadUrl['path'] . $param . (self::$siteUrlParams[self::$yearKey]['value']-1)  .'">' .
            '<span class="glyphicon glyphicon-step-backward"></span>' .
            '</a>' .
            '</div>' .
            '{control}' .
            (self::$siteUrlParams[self::$yearKey]['value'] < date('Y') ?
                '<div class="input-group-addon ctrl-addons">' .
                '<a id="year-control-next" title="' . (self::$siteUrlParams[self::$yearKey]['value']+1) . '" rel="nofollow" href="' . $reloadUrl['path'] . $param.(self::$siteUrlParams[self::$yearKey]['value']+1) . '">' .
                '<span class="glyphicon glyphicon-step-forward" aria-hidden="true"></span>' .
                '</a>' .
                '</div>' : '') .
            '</div>' .
            '</div>' . PHP_EOL;
        }
        if (empty($args["first_year"])) {
          $r .= '<th><span class="thisYear">' . self::$siteUrlParams[self::$yearKey]['value'] . '</span></th>';
        } else {
          $ctrlid = 'year-select-'.$nid;
          $firstYear = self::$siteUrlParams[self::$yearKey]['value'] >= $args["first_year"] ? $args["first_year"] : self::$siteUrlParams[self::$yearKey]['value'];
          $lookUpValues = range(date('Y'), $firstYear, -1);
          $lookUpValues = array_combine($lookUpValues, $lookUpValues);
          // can't do standard label, as DEH puts it in the wrong place
          $r .= '<th><div><label>' . lang::get('Year') . ':</label>' .
            data_entry_helper::select([
              'id' => $ctrlid,
              'class' => 'year-select',
              'fieldname' => self::$siteUrlParams[self::$yearKey]['name'],
              'lookupValues' => $lookUpValues,
              'default' => self::$siteUrlParams[self::$yearKey]['value']
            ]) . '</div></th>';
            self::set_up_control_change($ctrlid, self::$yearKey, []);
        }

        if ($baseTheme === 'generic') {
          if (self::$siteUrlParams[self::$yearKey]['value']  <date('Y')) {
            $r .= '<th><a id="year-control-next" title="' . (self::$siteUrlParams[self::$yearKey]['value']+1) . '" rel="nofollow" href="' . $reloadUrl['path'] . $param . (self::$siteUrlParams[self::$yearKey]['value']+1) . '" class="ui-datepicker-next ui-corner-all"><span class="ui-icon ui-icon-circle-triangle-e">' . lang::get('Next') . '</span></a></th>';
          } else {
            $r .= '<th/>';
          }
        }
        $options['year'] = self::$siteUrlParams[self::$yearKey]['value'];
        // ISO Date d/m/Y, due to change in report engine
        $options['date_start'] = '01/01/' . self::$siteUrlParams[self::$yearKey]['value'];
        $options['date_end'] = '31/12/' . self::$siteUrlParams[self::$yearKey]['value'];
        $options['downloadFilePrefix']['year'] = self::$siteUrlParams[self::$yearKey]['value'];
        if ($baseTheme !== 'generic') {
          $indicia_templates['controlWrap'] = $oldWrap;
        }
        return $r;
    }
  }

  public static function get_sorted_termlist_terms($auth, $key, $filter){
    $terms = helper_base::get_termlist_terms($auth, $key, $filter);
    $retVal = [];
    foreach ($filter as $f) {
      foreach ($terms as $term) {
        if ($f == $term['term']) {
          $retVal[] = $term;
        }
      }
    }
    return $retVal;
  }

  /**
   * Return the Indicia form code.
   *
   * @param array $args
   *   Input parameters.
   * @param array $nid
   *   Drupal node number
   * @param array $response
   *   Response from Indicia services after posting a verification.
   *
   * @return HTML string
   *   Page HTML content.
   */
  public static function get_form($args, $nid, $response) {
    $retVal = '';

    $logged_in = hostsite_get_user_field('id') > 0;
    if (!$logged_in) {
      return('<p>'.lang::get('Please log in before attempting to use this form.').'</p>');
    }
    // can't really do this automatically: better to give warning
    if(isset($args['locationTypeFilter'])) {
      return('<p>Please contact the site administrator. This version of the form uses a different method of specifying the location types.</p>');
    }
    //EBMS only
    if (\Drupal::moduleHandler()->moduleExists('ebms_scheme')  &&
          !empty($args['taxon_column_overrides'])) {
      $args = self::forceCommonNameLanguageOptionIfNeeded($args);
    }
    iform_load_helpers(['report_helper']);
    report_helper::add_resource('fancybox');
    report_helper::add_resource('html2canvas');
    $auth = report_helper::get_read_auth($args['website_id'], $args['password']);
    if (!self::set_up_survey($args, $auth)) { // this sets up the siteUrlParams for first time.
      return('set_up_survey returned false: survey_id missing from presets or location_type definition.');
    }
    $reportOptions = self::get_report_calendar_2_options($args, $auth);
    $reportOptions['id']='calendar-summary-'.$nid;
    if (!empty($args['removable_params'])) {
      self::$removableParams = get_options_array_with_user_data($args['removable_params']);
    }
    self::copy_args($args, $reportOptions,
      ['weekstart','weekOneContains','weekNumberFilter','inSeasonFilter',
        'outputTable','outputChart',
        'tableHeaders','chartLabels','disableableSeries',
        'chartType','rowGroupColumn','rowGroupID','width','height',
        'includeChartTotalSeries','includeChartItemSeries',
        'includeRawData', 'includeSummaryData', 'includeEstimatesData',
        'includeRawGridDownload', 'includeSummaryGridDownload',
        'includeEstimatesGridDownload', 'sampleFields',
        'taxon_column'
      ]);
    if (!empty($args['taxon_column_overrides'])) {
      $reportOptions['taxon_column_overrides'] = $args['taxon_column_overrides'];
    }

    // Chart options
    if (isset($_GET['outputSeries'])) {
      $reportOptions['outputSeries']=$_GET['outputSeries']; // default is all
    }
    $rendererOptions = trim($args['renderer_options']);
    if (!empty($rendererOptions)) {
      $reportOptions['rendererOptions'] = json_decode($rendererOptions, TRUE);
    }
    $axesOptions = trim($args['axes_options']);
    if (!empty($axesOptions)) {
      $reportOptions['axesOptions'] = json_decode($axesOptions, TRUE);
    }
    if(isset($args['countColumn']) && $args['countColumn']!='') {
      $reportOptions['countColumn']= 'attr_occurrence_'.str_replace(' ', '_', strtolower($args['countColumn'])); // assume that this is an occurrence attribute.
      $reportOptions['extraParams']['occattrs']=$args['countColumn'];
    }

    // Add info link:
    if (!empty($args['information_block'])) {
      $retVal .= PHP_EOL . '<div><a class="report-information right">' . lang::get("Information about this report") . '</a></div>';
      report_helper::$javascript .= 'indiciaData.information = "' . preg_replace("/\r|\n/", "", $args['information_block']) . '";';
      report_helper::$javascript .= 'indiciaData.informationDialogTitle = "' . lang::get("Report information") . '";';
      report_helper::$javascript .= 'indiciaData.informationCloseButton = "' . lang::get("Close") . '";';
    }

    // Add controls first: set up a control bar
    $baseTheme = hostsite_get_config_value('iform.settings', 'base_theme', 'generic');
    if ($baseTheme === 'generic') {
      $retVal .= PHP_EOL . '<table id="controls-table" class="ui-widget ui-widget-content ui-corner-all controls-table"><thead class="ui-widget-header"><tr>';
    } else {
      $retVal .= PHP_EOL . '<table id="controls-table" class="ui-corner-all controls-table"><thead><tr>';
    }
    $retVal .= self::year_control($args, $auth, $nid, $reportOptions);
    $retVal .= '<th>' . self::data_set_control($args, $auth, $nid, $reportOptions) . '</th>';
    $retVal .= '<th>' . self::location_control($args, $auth, $nid, $reportOptions) . '</th>'; // note this includes the location_type control if needed
    $retVal .= '<th>';
    if (!empty($args['removable_params'])) {
      foreach(self::$removableParams as $param=>$caption) {
        $checked = (isset($_GET[$param]) && $_GET[$param]==='TRUE');
        $retVal .= data_entry_helper::checkbox([
            'label' => lang::get($caption),
            'labelClass' => 'inline',
            'fieldname' => 'removeParam-' . $param,
            'id' => 'removeParam-' . $param,
            'class' => 'removableParam',
            'default' => $checked,
            'labelPosition' => 'after'
        ]);

        if ($checked) {
          $reportOptions['downloadFilePrefix'][$param] = 'RM'.preg_replace('/[^A-Za-z0-9]/i', '', $param);
        }
      }
      self::set_up_control_change('removeParam-'.$param, $param, [], TRUE);
    }
    // Caching
    $retVal .= data_entry_helper::checkbox([
        'label' => lang::get("Use cached data"),
        'labelClass' => 'inline',
        'fieldname' => 'cachingParam',
        'id' => 'cachingParam',
        'class' => 'cachingParam',
        'title' => lang::get("When fetching the full data set, selecting this improves performance by not going to the warehouse to get the data. Occassionally, even when selected, the data will be refreshed, which will appear to slow down the response."),
        'default' => self::$siteUrlParams[self::$cacheKey]['value'],
        'labelPosition' => 'after',
        'labelTemplate' => 'labelNoColon'
    ]);
    $retVal .= '</th></tr></thead></table>';

    $reportOptions['caching'] = self::$siteUrlParams[self::$cacheKey]['value'];
    self::set_up_control_change('cachingParam', self::$cacheKey, [], TRUE);
    // are there any params that should be set to blank using one of the removable params tickboxes?
    foreach (self::$removableParams as $param=>$caption) {
      if (isset($_GET[$param]) && $_GET[$param]==='TRUE') {
        $reportOptions[$param]='';
        $reportOptions['extraParams'][$param]='';
      }
    }
    if(self::$siteUrlParams[self::$locationTypeKey]['value'] == '') {
      if(isset($args['locationTypesFilter']) && $args['locationTypesFilter']!="" ){
        $types = explode(',',$args['locationTypesFilter']);
        $terms = self::get_sorted_termlist_terms(['read' => $auth], 'indicia:location_types', [$types[0]]);
        $reportOptions['paramDefaults'][self::$locationTypeKey] = $terms[0]['id'];
        $reportOptions['extraParams'][self::$locationTypeKey] = $terms[0]['id'];
      }
    } else {
      $reportOptions['paramDefaults'][self::$locationTypeKey] = self::$siteUrlParams[self::$locationTypeKey]['value'];
      $reportOptions['extraParams'][self::$locationTypeKey] = self::$siteUrlParams[self::$locationTypeKey]['value'];
    }
    if(isset($args['linkURL'])) {
      $reportOptions['linkURL'] = $args['linkURL'] . (isset(self::$siteUrlParams[self::$URLExtensionKey]) ? self::$siteUrlParams[self::$URLExtensionKey] : '');
      $reportOptions['linkURL'] .= (strpos($reportOptions['linkURL'], '?') !== FALSE ? '&' : '?') . 'sample_id=';
    }
    $reportOptions['includeReportTimeStamp']=isset($args['includeFilenameTimestamps']) && $args['includeFilenameTimestamps'];

    $reportOptions['survey_id'] = self::$siteUrlParams[self::$SurveyKey]; // Sort of assuming that only one location type recorded against per survey.
    $reportOptions['downloads'] = [];

    $reportOptions['downloadFilePrefix'] = implode('_', $reportOptions['downloadFilePrefix']) . '_';
    // add the additional downloads.
    if ($reportOptions['canDownload']) {
      $download_reports = json_decode($args['download_reports'], TRUE);
      foreach ($download_reports as $download_report) {
        if (empty($download_report['permission']) || hostsite_user_has_permission($download_report['permission'])) {
          $reportOpts = [
            'caption' => lang::get($download_report['caption']),
            'dataSource' => $download_report['report'],
            'filename' => $reportOptions['downloadFilePrefix'] .
              preg_replace('/[^A-Za-z0-9]/i', '', lang::get($download_report['caption'])) .
              (isset($reportOptions['includeReportTimeStamp']) && $reportOptions['includeReportTimeStamp'] ? '_'.date('YmdHis') : ''),
            'format' => $download_report['format'],
            'param_presets' => [],
          ];
          if (isset($download_report['presets'])) {
            foreach ($download_report['presets'] as $preset) {
              $reportOpts['param_presets'][$preset['parameter']] = apply_user_replacements($preset['value']);
            }
          }
          $reportOptions['downloads'][] = $reportOpts;
        }
      }
    }

    $retVal .= report_helper::report_calendar_summary2($reportOptions);

    return $retVal;
  }

  /**
   * Override the type of taxon label to make sure it is common name for some EBMS schemes
   *
   * @param array $args Argument options for the page.
   *
   * @return array Altered page arguments.
   */
  private static function forceCommonNameLanguageOptionIfNeeded($args) {
    // Get the schemes that will force use of common names in own language
    $schemesThatUseCommonNameOwnLanguage = json_decode($args['taxon_column_overrides'], TRUE);
    // Get the ID of the current user's scheme
    if (function_exists('ebms_scheme_list_user_schemes')) {
      $myId = hostsite_get_user_field('id');
      $mySchemes = ebms_scheme_list_user_schemes($myId);
      if (count($mySchemes) > 0) {
        // User's currently only have 1 scheme, but cycle them anyway.
        foreach($mySchemes as $myScheme) {
          // If user's scheme is one we want to override the names for,
          // then force that we will use common name option
          if (in_array($myScheme['id'], $schemesThatUseCommonNameOwnLanguage)) {
            $args['taxon_column'] === 'common_name';
          }
        }
      }
    }
    return $args;
  }
}