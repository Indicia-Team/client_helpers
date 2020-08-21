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
require_once 'includes/report.php';
require_once 'includes/report_filters.php';
require_once 'includes/groups.php';

/**
 * Verification 5 prebuilt form.
 *
 * Prebuilt Indicia data form that lists the output of an occurrences report with an option
 * to verify, reject or flag dubious each record.
 */
class iform_verification_5 {

  /**
   * Flag that can be set when the user's permissions filters are to be ignored.
   *
   * @var bool
   */
  private static $overridePermissionsFilters = FALSE;

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_verification_5_definition() {
    return array(
      'title' => 'Verification 5',
      'category' => 'Verification',
      'description' => 'Verification form supporting 2 tier verification statuses. Requires the ' .
        'Easy Login module and Indicia AJAX Proxy module to both be enabled.',
      'recommended' => TRUE,
      'supportsGroups' => TRUE,
    );
  }

  /**
   * Get the list of parameters for this form.
   *
   * @return array
   *   List of parameters that this form requires.
   */
  public static function get_parameters() {
    $r = array_merge(
      iform_map_get_map_parameters(),
      iform_report_get_minimal_report_parameters(),
      array(
        array(
          'name' => 'group_type_ids',
          'caption' => 'Group types',
          'description' => 'If this page is going to be used by recording groups to facilitate verification, ' .
            'it is strongly recommended that you limit this feature to certain groups by choosing appropriate ' .
            'group types here. Note that a group linked verification page MUST be accessed via a group - it ' .
            'cannot be accessed directly to remove the risk of users with no permissions filters accessing ' .
            'all records for verification by removing the group ID from the link.',
          'type' => 'checkbox_group',
          'table' => 'termlists_term',
          'valueField' => 'id',
          'captionField' => 'term',
          'extraParams' => array('termlist_external_key' => 'indicia:group_types'),
          'class' => 'group-field',
        ),
        array(
          'name' => 'report_row_class',
          'caption' => 'Report row class',
          'description' => 'CSS class to attach to report grid rows. Fieldnames from the report output wrapped in {} ' .
            'are replaced by field values',
          'type' => 'text_input',
          'group' => 'Report Settings',
          'default' => 'zero-{zero_abundance}',
        ),
        array(
          'name' => 'mapping_report_name',
          'caption' => 'Report for map output',
          'description' => 'Report used to obtain the output for the map. Should have the same parameters as the grid report but only needs to '.
              'return the occurrence id, geom and any shape formatting.',
          'type' => 'report_helper::report_picker',
          'group' => 'Report Settings',
          'default' => 'library/occurrences/filterable_explore_list_mapping',
        ),
        array(
          'name' => 'mapping_report_name_lores',
          'caption' => 'Report for lo res map output',
          'description' => 'Report used to obtain the output for the map at low zoom levels.',
          'type' => 'report_helper::report_picker',
          'group' => 'Report Settings',
          'default' => 'library/occurrences/filterable_explore_list_mapping_lores',
        ),
        array(
          'name' => 'record_details_report',
          'caption' => 'Report for record details',
          'description' => 'Report used to obtain the details of a record. See reports_for_prebuilt_forms/verification_5/record_data.xml for an example.',
          'type' => 'report_helper::report_picker',
          'group' => 'Report Settings',
          'default' => 'reports_for_prebuilt_forms/verification_5/record_data',
        ),
        array(
          'name' => 'record_attrs_report',
          'caption' => 'Report for record attributes',
          'description' => 'Report used to obtain the custom attributes of a record. See reports_for_prebuilt_forms/verification_3/record_data_attributes.xml for an example.',
          'type' => 'report_helper::report_picker',
          'group' => 'Report Settings',
          'default' => 'reports_for_prebuilt_forms/verification_3/record_data_attributes',
        ),
        array(
          'name' => 'min_taxon_rank_sort_order',
          'caption' => 'Minimum taxon rank sort order',
          'description' => 'For the experience report, specify the minimum taxon rank sort order to include.',
          'type' => 'text_input',
          'group' => 'Report Settings',
          'required' => FALSE,
        ),
        array(
            'name' => 'columns_config',
            'caption' => 'Columns Configuration',
            'description' => 'Define a list of columns with various configuration options when you want to override the '.
                'default output of the report.',
            'type' => 'jsonwidget',
            'default' => '[]',
            'schema' => '{
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
          "template": {"type":"str","desc":"Allows you to create columns that contain dynamic content using a template, rather than just the output '.
          'of a field. The template text can contain fieldnames in braces, which will be replaced by the respective field values. '.
          'Note that template columns cannot be sorted by clicking grid headers." },
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
  }',
          'group' => 'Report Settings',
          'required' => FALSE,
        ), array(
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
          'default' => 'verification',
          'group' => 'Report Settings',
        ),
        array(
          'name' => 'report_download_link',
          'caption' => 'Report download link',
          'description' => 'Include a link for downloading the current report grid containing the list of records.',
          'type' => 'checkbox',
          'group' => 'Report Settings',
          'default' => FALSE,
          'required' => FALSE,
        ),
        array(
          'name' => 'email_from_address',
          'caption' => 'Email from address',
          'description' => 'Specify the email address which emails should be sent from. This must be an address on ' .
            'the same domain as the site to avoid the emails being blocked as spam. If blank then the site email ' .
            'address is used.',
          'type' => 'string',
          'required' => FALSE,
          'group' => 'Verifier emails',
        ), array(
          'name' => 'email_subject_send_to_verifier',
          'caption' => 'Send to Expert Email Subject',
          'description' => 'Default subject for the send to expert email. Replacements allowed include %taxon% and %id%.',
          'type' => 'string',
          'default' => 'Requesting your opinion on a record of %taxon% (ID:%id%)',
          'group' => 'Verifier emails',
        ), array(
          'name' => 'email_body_send_to_verifier',
          'caption' => 'Send to Expert Email Body',
          'description' => 'Default body for the send to expert email. Replacements allowed include %taxon%, %id% and %record% which is replaced to give details of the record. '
           . 'Use the %commentQuickReplyPageLink% replacement if you wish the recipient to be able to quickly reply to comments using a linked page, '
           . 'setup the options for this using the COMMENT QUICK REPLY PAGE LINK section of this page',
          'type' => 'textarea',
          'default' => 'We would appreciate your opinion on the following record. Please reply to this mail with "accepted", "not accepted" or "query" ' .
              'in the email body, followed by any comments you have including the proposed re-identification if relevant on the next line.' .
              "\n\n%record%",
          'group' => 'Verifier emails',
        ), array(
          'name' => 'email_subject_send_to_recorder',
          'caption' => 'Send to Recorder Email Subject',
          'description' => 'Default subject for the send query to recorder email. Replacements allowed include %taxon% and %id%.',
          'type' => 'string',
          'default' => 'Query on your record of %taxon% (ID:%id%)',
          'group' => 'Recorder emails',
        ), array(
          'name' => 'email_body_send_to_recorder',
          'caption' => 'Send to Recorder Email Body',
          'description' => 'Default body for the send to recorder email. Replacements allowed include %taxon%, %id% and %record% which is replaced to give details of the record. ' .
            'Use the %commentQuickReplyPageLink% replacement if you wish the recipient to be able to quickly reply to queries using a linked page, ' .
            'setup the options for this using the COMMENT QUICK REPLY PAGE LINK section of this page',
          'type' => 'textarea',
          'default' => 'The following record requires confirmation. Please could you reply to this email stating how confident you are that the record is correct ' .
            'and any other information you have which may help to confirm this.' .
            "\n\n%record%",
          'group' => 'Recorder emails',
        ), array(
          'name' => 'auto_discard_rows',
          'caption' => 'Automatically remove rows',
          'description' => 'If checked, then when changing the status of a record the record is removed from the grid if it no ' .
            'longer matches the grid filter.',
          'type' => 'checkbox',
          'default' => 'true',
          'required' => FALSE,
        ),
        array(
          'name' => 'indicia_species_layer_feature_type',
          'caption' => 'Feature type for Indicia species layer',
          'description' => 'Set to the name of a feature type on GeoServer that will be loaded to display the Indicia species data for the selected record. '.
            'Leave empty for no layer. Normally this should be set to a feature type that exposes the cache_occurrences view.',
          'type' => 'text_input',
          'required' => FALSE,
          'group' => 'Other Map Settings',
        ),
        array(
          'name' => 'indicia_species_layer_ds_filter_field',
          'caption' => 'Filter method',
          'description' => 'Method of filtering taxa to display the species layer.',
          'type' => 'select',
          'options' => array(
            'taxon_meaning_id' => 'Meaning ID',
            'taxon_external_key' => 'External Key',
          ),
          'required' => FALSE,
          'group' => 'Other Map Settings',
        ), array(
          'name' => 'indicia_species_layer_filter_field',
          'caption' => 'Field to filter on',
          'description' => 'Set to the name of a field exposed by the feature type which can be used to filter for the species data to display. Examples include '.
            'taxon_external_key, taxon_meaning_id.',
          'type' => 'text_input',
          'required' => FALSE,
          'group' => 'Other Map Settings',
        ), array(
          'name' => 'indicia_species_layer_sld',
          'caption' => 'SLD file from GeoServer for Indicia species layer',
          'description' => 'Set to the name of an SLD file available on the GeoServer for the rendering of the Indicia species layer, or leave blank for default.',
          'type' => 'text_input',
          'required' => FALSE,
          'group' => 'Other Map Settings',
        ),
        array(
          'name' => 'additional_wms_species_layer_title',
          'caption' => 'Additional WMS layer title',
          'description' => 'Title of an additional species layer to load from a WMS service',
          'type' => 'text_input',
          'required' => FALSE,
          'group' => 'Other Map Settings',
        ),
        array(
          'name' => 'additional_wms_species_layer_url',
          'caption' => 'Additional WMS layer URL',
          'description' => 'URL of an additional species layer to load from a WMS service. {external_key} is replaced by the species external key.',
          'type' => 'text_input',
          'required' => FALSE,
          'group' => 'Other Map Settings',
        ),
        array(
          'name' => 'additional_wms_species_layer_settings',
          'caption' => 'Additional WMS layer settings',
          'description' => 'JSON settings object for an additional species layer to load from a WMS service. {external_key} is replaced by the species external key.',
          'type' => 'textarea',
          'required' => FALSE,
          'group' => 'Other Map Settings',
        ),
        array(
          'name' => 'additional_wms_species_layer_ol_settings',
          'caption' => 'Additional WMS layer OpenLayers settings',
          'description' => 'JSON settings object for the Open Layers settings object for an additional species layer to load from a WMS service.',
          'type' => 'textarea',
          'required' => FALSE,
          'group' => 'Other Map Settings',
        ),
        array(
          'name' => 'indexed_location_type_ids',
          'caption' => 'Indexed location type IDs',
          'description' => 'Comma separated list of location type IDs for location layers that are available to search against.',
          'type' => 'text_input',
          'required' => FALSE,
          'group' => 'Other Map Settings',
        ),
        array(
          'name' => 'other_location_type_ids',
          'caption' => 'Other location type IDs',
          'description' => 'Comma separated list of location type IDs for location layers that are available to search against.',
          'type' => 'text_input',
          'required' => FALSE,
          'group' => 'Other Map Settings'
        ),
        array(
          'name' => 'view_records_report_path',
          'caption' => 'View records report path',
          'description' => 'Path to page used to show a list of records, e.g. when clicking on the record counts on the Experience tab',
          'type' => 'string',
          'required' => FALSE,
        ),
        array(
          'name' => 'record_details_path',
          'caption' => 'Record details page path',
          'description' => 'Path to page used to show details of a single record, e.g. an instance of the Record Details 2 prebuilt form.',
          'type' => 'string',
          'required' => FALSE,
        ),
        array(
          'name' => 'enableWorkflow',
          'caption' => 'Enable Workflow?',
          'description' => 'Is the Workflow module enabled on the warehouse?',
          'type' => 'boolean',
          'group' => 'Notification Settings',
          'default' => FALSE,
          'required' => FALSE,
        ),
        array(
          'name' => 'clear_verification_task_notifications',
          'caption' => 'Clear verification task notifications?',
          'description' => 'Automatically clear any verification task notifications when the user opens the verification screen.',
          'type' => 'boolean',
          'group' => 'Notification Settings',
          'default' => FALSE,
          'required' => FALSE,
        ),
        array(
          'name' => 'custom_occurrence_metadata',
          'caption' => 'Custom occurrence metadata',
          'description' => 'Definition of any additional fields that can be stored in the occurrence metadata which will not be shown to the recorder',
          'type' => 'jsonwidget',
          'schema' => '{
  "type":"seq",
  "title":"Fields list",
  "sequence":
  [
    {
      "type":"map",
      "title":"Field",
      "mapping": {
        "title": {"type":"str","desc":"The field title"},
        "values": {"type":"txt","desc":"Any key=value pairs to limit the options to. Otherwise free text is accepted."}
      }
    }
  ]
}',
        ),
        array(
          'name' => 'comment_quick_reply_page_link_label',
          'caption' => 'Comment quick reply page link label',
          'description' => 'Label for the link to the Comment Quick Reply page.',
          'type' => 'text_input',
          'group' => 'Comment quick reply page link',
          'required' => 'false'
        ),
        array(
          'name' => 'comment_quick_reply_page_link_url',
          'caption' => 'Comment quick reply page link URL',
          'description' => 'URL link to the Comment Quick Reply page.',
          'type' => 'text_input',
          'group' => 'Comment quick reply page link',
          'required' => 'false'
        )
      )
    );
    // Set default values for the report.
    foreach ($r as &$param) {
      if ($param['name'] === 'report_name') {
        $param['default'] = 'library/occurrences/filterable_explore_list';
      }
      elseif ($param['name'] === 'param_presets') {
        $param['default'] = 'survey_id=
date_from=
date_to=
smpattrs=
occattrs=';
      }
      elseif ($param['name'] === 'param_defaults') {
        $param['default'] = 'id=
record_status=C
records=unverified
searchArea=
idlist=';
      }
    }
    return $r;
  }

  /**
   * Returns the HTML for the standard set of tabs, excluding the details and optional map tab.
   *
   * @return string
   *   HTML to insert onto the page
   */
  private static function otherTabHtml() {
    $r = '<div id="experience-tab"><p>' . lang::get('Recorder\'s other records of this species and species group. Click to explore:') . '</p><div id="experience-div"></div></div>';
    $r .= '<div id="phenology-tab"><p>' . lang::get('The following phenology chart shows the relative abundance of records through the '.
        'year for this species, <em>from the verified online recording data only.</em>') . '</p><div id="chart-div"></div></div>';
    $r .= '<div id="media-tab"></div>';
    $r .= '<div id="comments-tab"></div>';
    return $r;
  }

  private static function getTemplateWithMap($args, $readAuth, $extraParams, $paramDefaults) {
    $r = '<div id="outer-with-map" class="ui-helper-clearfix">';
    $r .= '<div id="grid" class="left" style="width:65%">{paramsForm}<div id="grids-tabs">';
    // Note - there is a dependency in the JS that comments is the last tab and media the 2nd to last.
    $r .= data_entry_helper::tab_header(array(
      'tabs' => array(
        '#records-tab' => lang::get('Records'),
        '#log-tab' => lang::get('Log'),
      )
    ));
    data_entry_helper::enable_tabs(array(
      'divId' => 'grids-tabs',
    ));
    $r .= '<div id="records-tab">{grid}</div>';
    $r .= '<div id="log-tab">{log}</div>';
    $r .= '</div></div>';
    $r .= '<div id="map-and-record" class="right" style="width: 34%"><div id="summary-map">';
    $options = iform_map_get_map_options($args, $readAuth);
    $olOptions = iform_map_get_ol_options($args);
    $options['editLayerName'] = 'Selected record';
    // This is used for drawing, so need an editlayer, but not used for input.
    $options['editLayer'] = TRUE;
    $options['editLayerInSwitcher'] = TRUE;
    $options['clickForSpatialRef'] = FALSE;
    $options['featureIdField'] = 'occurrence_id';
    $r .= map_helper::map_panel(
      $options,
      $olOptions
    );
    $reportMapOpts = array(
      'dataSource' => !empty($args['mapping_report_name']) ? $args['mapping_report_name'] : $args['report_name'],
      'mode' => 'report',
      'readAuth' => $readAuth,
      'autoParamsForm' => FALSE,
      'extraParams' => $extraParams,
      'paramDefaults' => $paramDefaults,
      'reportGroup' => 'verification',
      'clickableLayersOutputMode' => 'report',
      'rowId' => 'occurrence_id',
      'sharing' => 'verification',
      'ajax' => TRUE,
    );
    if (!empty($args['mapping_report_name_lores'])) {
      $reportMapOpts['dataSourceLoRes'] = $args['mapping_report_name_lores'];
    }
    $r .= report_helper::report_map($reportMapOpts);
    $r .= '</div>';
    if (function_exists('hostsite_get_user_field') && $locationId = hostsite_get_user_field('location_expertise', FALSE)) {
      iform_map_zoom_to_location($locationId, $readAuth);
    }
    $r .= '<div id="record-details-wrap" class="ui-widget ui-widget-content">';
    $r .= self::instructions('grid on the left');
    $r .= '<div id="record-details-content" style="display: none">';
    $r .= '<div id="record-details-toolbar">';
    $r .= '<div id="action-buttons">';
    $r .= '<div id="action-buttons-status" class="action-buttons-row">';
    $r .= '<div class="col-1"><label>' . lang::get('Set status:') . '</label> <a id="more-status-buttons">[' . lang::get('less') .']</a></div>';
    data_entry_helper::$javascript .= "indiciaData.langLess='" . lang::get('less') . "';\n";
    data_entry_helper::$javascript .= "indiciaData.langMore='" . lang::get('more') . "';\n";
    $imgPath = empty(data_entry_helper::$images_path) ? data_entry_helper::relative_client_helper_path() . "../media/images/" : data_entry_helper::$images_path;
    $r .= self::statusButtonsLess($imgPath);
    $r .= self::statusButtonsMore($imgPath);
    $r .= self::hidden_redetermination_dropdown($readAuth);
    $r .= '</div>';
    $r .= '<div id="action-buttons-other" class="action-buttons-row"><div class="col-1"><label>Other actions:</label></div>';
    $r .= self::otherActionButtons($imgPath);
    $r .= '</div></div>';
    $r .= '<div id="record-details-tabs">';
    // Note - there is a dependency in the JS that comments is the last tab and media the 2nd to last.
    $r .= data_entry_helper::tab_header(array(
      'tabs' => array(
        '#details-tab' => lang::get('Details'),
        '#experience-tab' => lang::get('Experience'),
        '#phenology-tab' => lang::get('Phenology'),
        '#media-tab' => lang::get('Media'),
        '#comments-tab' => lang::get('Comments')
      )
    ));
    data_entry_helper::$javascript .= "indiciaData.detailsTabs = ['details','experience','phenology','media','comments'];\n";
    data_entry_helper::enable_tabs(array(
      'divId' => 'record-details-tabs'
    ));
    $r .= '<div id="details-tab"></div>';
    $r .= self::otherTabHtml();
    $r .= '<span id="details-zoom" title="' . lang::get('Click to expand record details to full screen') . '">&#8689;</span>';
    $r .= '</div></div></div></div></div></div>';
    return $r;
  }

  /**
   * Constructs HTML for a block of instructions.
   *
   * @param string $gridpos
   *   Pass in a description of where the records grid is relative to the instruction  block, e.g. 'grid below' or
   *   'grid on the left'.
   *
   * @return string
   *   HTML for the instruction div
   */
  private static function instructions($gridpos) {
    $r = '<div id="instructions">' . lang::get('You can') . ":\n<ul>\n";
    $r .= '<li>' . lang::get('Select the records to include in the list of records to verify using the drop-down box above the grid.') . "</li>\n";
    $r .= '<li>' . lang::get('Filter the list of records by using the <strong>Create a filter</strong> button and save filters for future use.') . "</li>\n";
    $r .= '<li>' . lang::get("Click on a record in the $gridpos to view the details.") . "</li>\n";
    $r .= '<li>' . lang::get('When viewing the record details, verify, reject, query or email the record details for confirmation.') . "</li>\n";
    $r .= '<li>' . lang::get('When viewing the record details, view and add comments on the record.') . "</li>\n";
    $r .= '<li>' . lang::get('Click on media files in the grid or on the record details Media tab to review the original file and check the record\'s identification.') . "</li>\n";
    $r .= '<li>' . lang::get('Use the ... button to the left of each record to view bulk-verification options for similar records.') . "</li>\n";
    $r .= '<li>' . lang::get('Use the <strong>Review grid</strong> button above the grid to apply changes to all the records loaded in the grid in one step.') . "</li>\n";
    $r .= '<li>' . lang::get('Use the <strong>Review tick list</strong> button above the grid to apply changes to a selection of records from the grid in one step.') . "</li>\n";
    $r .= '</ul></div>';
    return $r;
  }

  private static function check_prerequisites() {
    $msg = FALSE;
    if (!function_exists('iform_ajaxproxy_url')) {
      $msg = 'The AJAX Proxy module must be enabled to support saving filters on the verification page.';
    }
    if (!function_exists('hostsite_get_user_field') || !hostsite_get_user_field('indicia_user_id')) {
      $msg = 'Before verifying records, please visit your user account profile and ensure that you have entered your ' .
        'full name, then save it.';
    }
    if ($msg)
      hostsite_show_message($msg, 'warning');
    return $msg ? FALSE : TRUE;
  }

  /**
   * Returns the HTML for the Less mode status buttons.
   *
   * @param string $imgPath
   *   Path to the images folder.
   *
   * @return string
   *   HTML
   */
  private static function statusButtonsLess($imgPath) {
    $r = '<div id="actions-less" class="buttons-row" style="display: none;">';
    $r .= '<button type="button" id="btn-accepted" title="' . lang::get('Set to accepted') . '"><img width="18" height="18" src="' . $imgPath . 'nuvola/ok-16px.png"/></button>';
    $r .= '<button type="button" id="btn-notaccepted" title="' . lang::get('Set to not accepted') . '"><img width="18" height="18" src="' . $imgPath . 'nuvola/cancel-16px.png"/></button>';
    $r .= '</div>';
    return $r;
  }

  /**
   * Returns the HTML for the More mode status buttons.
   *
   * @param string $imgPath
   *   Path to the images folder.
   *
   * @return string
   *   HTML
   */
  private static function statusButtonsMore($imgPath) {
    $r = '<div id="actions-more" class="buttons-row">';
    $r .= '<button type="button" id="btn-accepted-correct" title="' . lang::get('Set to accepted::correct') . '">' .
        '<img width="18" height="18" src="' . $imgPath . 'nuvola/ok-16px.png"/><img width="18" height="18" src="' . $imgPath . 'nuvola/ok-16px.png"/></button>';
    $r .= '<button type="button" id="btn-accepted-considered-correct" title="' . lang::get('Set to accepted::considered correct') . '">' .
        '<img width="18" height="18" src="' . $imgPath . 'nuvola/ok-16px.png"/></button>';
    $r .= '<button type="button" id="btn-plausible" title="' . lang::get('Set to plausible') . '"><img width="18" height="18" src="' . $imgPath . 'nuvola/quiz-22px.png"/></button>';
    $r .= '<button type="button" id="btn-notaccepted-unable" title="' . lang::get('Set to not accepted::unable to verify') . '"><img width="18" height="18" src="' . $imgPath . 'nuvola/cancel-16px.png"/></button>';
    $r .= '<button type="button" id="btn-notaccepted-incorrect" title="' . lang::get('Set to not accepted::incorrect') . '">' .
        '<img width="18" height="18" src="' . $imgPath . 'nuvola/cancel-16px.png"/><img width="18" height="18" src="' . $imgPath . 'nuvola/cancel-16px.png"/></button>';
    $r .= '</div>';
    return $r;
  }

  /**
   * Returns the set of buttons with contact, query and redetermine/edit tools.
   *
   * @param string $imgPath
   *   Path to the images folder.
   *
   * @return string
   *   HTML
   */
  private static function otherActionButtons($imgPath) {
    global $indicia_templates;

    $r = '<div id="other-actions" class="buttons-row">';
    // @todo Following button needs to be disabled if recorder cannot be contacted (email or notifications system). Exclude global emails like iSpot.
    // @todo Query icon - question mark in speech bubble
    $r .= helper_base::apply_static_template('button', [
      'id' => 'btn-query',
      'title' => lang::get('Raise a query against this record with the recorder'),
      'class' => ' class="' . $indicia_templates['buttonDefaultClass'] . '"',
      'caption' => lang::get('Query'),
    ]);
    // @todo Email icon with motion lines or arrow
    // send details to expert
    $r .= helper_base::apply_static_template('button', [
      'id' => 'btn-email-expert',
      'title' => lang::get('Email the record details to another expert for their opinion'),
      'class' => ' class="' . $indicia_templates['buttonDefaultClass'] . '"',
      'caption' => lang::get('Send to expert'),
    ]);
    // @todo icon
    $r .= helper_base::apply_static_template('button', [
      'id' => 'btn-redetermine',
      'title' => lang::get('Propose a new determination for this record.'),
      'class' => ' class="' . $indicia_templates['buttonDefaultClass'] . '"',
      'caption' => lang::get('Redet.'),
    ]);
    // @todo following needs to be disabled if record is not on iRecord.
    $r .= '<a id="btn-edit-record" class="' . $indicia_templates['anchorButtonClass'] . '" title="' .
      lang::get('Edit the record on its original data entry form.') . '">' . lang::get('Edit') . '</a>';
    $r .= helper_base::apply_static_template('button', [
      'id' => 'btn-log-response',
      'title' => lang::get('Log an email or other response.'),
      'class' => ' class="' . $indicia_templates['buttonDefaultClass'] . '"',
      'caption' => lang::get('Log reply'),
    ]);
    $r .= '</div>';
    return $r;
  }

  /**
   * Returns a hidden div to insert onto the page, which can hold the redetermination control when it is
   * not in use.
   *
   * @param array $readAuth
   *   Authorisation tokens.
   *
   * @return string HTML
   */
  private static function hidden_redetermination_dropdown($readAuth) {
    $r = '<div id="redet-dropdown-ctnr" style="display: none"><div id="redet-dropdown">';
    $taxon_list_id = hostsite_get_config_value('iform', 'master_checklist_id', 0);
    if ($taxon_list_id) {
      global $indicia_templates;
      $r .= '<div class="redet-partial-list">' .
        str_replace('{message}', lang::get('Redet partial list info'), $indicia_templates['messageBox']) .
        '</div>';
    }
    $r .= data_entry_helper::species_autocomplete([
      'fieldname' => 'redet',
      'label' => lang::get('New determination'),
      'labelClass' => 'auto',
      'helpText' => lang::get('Enter a new determination for this record before verifying it. The previous determination will be stored with the record.'),
      // Taxon list ID will be updated when it is used.
      'extraParams' => $readAuth + ['taxon_list_id' => 1],
      'speciesIncludeBothNames' => TRUE,
      'speciesIncludeTaxonGroup' => TRUE,
      'validation' => ['required'],
    ]);
    if ($taxon_list_id && preg_match('/^\d+$/', $taxon_list_id)) {
      data_entry_helper::$javascript .= "indiciaData.mainTaxonListId=$taxon_list_id\n;";
      $r .= '<div class="redet-partial-list">' .
        data_entry_helper::checkbox([
          'fieldname' => 'redet-from-full-list',
          'label' => lang::get('Search all species'),
          'labelClass' => 'auto',
          'helpText' => lang::get('This record was identified against a restricted list of taxa. Check this box if ' .
              'you want to redetermine to a taxon selected from the unrestricted full list available.'),
        ]) .
        '</div>';
    }
    $r .= '</div></div>';
    return $r;
  }

  /**
   * Return the Indicia form code.
   *
   * Expects there to be a sample attribute with caption 'Email' containing the email
   * address.
   *
   * @param array $args
   *   Input parameters.
   * @param array $nid
   *   Drupal node object's ID.
   * @param array $response
   *   Response from Indicia services after posting a verification.
   *
   * @return HTML string
   */
  public static function get_form($args, $nid, $response) {
    if (!self::check_prerequisites()) {
      return '';
    }
    iform_load_helpers(['data_entry_helper', 'map_helper', 'report_helper', 'VerificationHelper']);
    $args = array_merge([
      'sharing' => 'verification',
      'report_row_class' => 'zero-{zero_abundance}',
    ], $args);
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    if (group_authorise_form($args, $auth['read'])) {
      $group = group_apply_report_limits($args, $auth['read'], $nid, TRUE);
      if (!empty($args['group_type_ids']) && !in_array($group['group_type_id'], $args['group_type_ids'])) {
        // Group type is not authorised for verification.
        hostsite_show_message(lang::get('This group is not allowed to perform verification tasks.'), 'alert', TRUE);
        hostsite_goto_page('<front>');
      }
      else {
        self::$overridePermissionsFilters = TRUE;
      }
    }
    elseif ($args['available_for_groups'] === '1') {
      hostsite_show_message(lang::get('This page must be accessed via a group.'), 'alert', TRUE);
      hostsite_goto_page('<front>');
    }
    // Clear Verifier Tasks automatically when they open the screen if the option is set.
    if ($args['clear_verification_task_notifications']&&hostsite_get_user_field('indicia_user_id')) {
      self::clear_verifier_task_notifications($auth);
    }
    // Set some defaults, applied when upgrading from a form configured on a previous form version.
    if (empty($args['email_subject_send_to_recorder'])) {
      $args['email_subject_send_to_recorder'] = 'Record of %taxon% requires confirmation (ID:%id%)';
    }
    if (empty($args['email_body_send_to_recorder'])) {
      $args['email_body_send_to_recorder'] = 'The following record requires confirmation. Please could you reply to this email stating how confident you are that the record is correct '.
              'and any other information you have which may help to confirm this.' .
              "\n\n%record%";
    }
    if (function_exists('drupal_add_js')) {
      drupal_add_js('misc/collapse.js');
    }
    // Fancybox for popup comment forms etc.
    data_entry_helper::add_resource('fancybox');
    data_entry_helper::add_resource('validation');
    $indicia_user_id = self::get_indicia_user_id($args);
    // Find a list of websites we are allowed verify.
    $websiteIds = iform_get_allowed_website_ids($auth['read'], 'verification');
    $gotEasyLogin = function_exists('hostsite_module_exists') && hostsite_module_exists('easy_login');
    if (strpos($args['param_presets'] . $args['param_defaults'], 'expertise_location') === FALSE)
      $args['param_presets'] .= "\nexpertise_location=" . ($gotEasyLogin ? '{profile_location_expertise}' : '');
    if (strpos($args['param_presets'] . $args['param_defaults'], 'expertise_taxon_groups') === FALSE)
      $args['param_presets'] .= "\nexpertise_taxon_groups=" . ($gotEasyLogin ? '{profile_taxon_groups_expertise}' : '');
    if (strpos($args['param_presets'] . $args['param_defaults'], 'expertise_surveys') === FALSE)
      $args['param_presets'] .= "\nexpertise_surveys=" . ($gotEasyLogin ? '{profile_surveys_expertise}' : '');
    $params = self::reportFilterPanel($args, $auth['read']);
    $opts = array_merge(
      iform_report_get_report_options($args, $auth['read']),
      array(
        'id' => 'verification-grid',
        'reportGroup' => 'verification',
        'rowId' => 'occurrence_id',
        'paramsFormButtonCaption' => lang::get('Filter'),
        'paramPrefix' => '<div class="report-param">',
        'paramSuffix' => '</div>',
        'sharing' => $args['sharing'],
        'ajax' => TRUE,
        'callback' => 'verificationGridLoaded',
        'rowClass' => $args['report_row_class'],
        'downloadLink' => empty($args['report_download_link']) ? FALSE : TRUE,
        'responsiveOpts' => array(
          'breakpoints' => array(
            'phone' => 480,
            'tablet-portrait' => 768,
            'tablet-landscape' => 1300,
          ),
        ),
        'includeColumnsPicker' => TRUE,
      )
    );
    array_unshift($opts['columns'], array(
      'display' => '',
      'template' => <<<HTML
<div class="nowrap">
  <button class="default-button quick-verify tools-btn" type="button" id="quick-{occurrence_id}" title="Record tools">...</button>
  <input type="hidden" class="row-input-form-link" value="{rootFolder}{input_form}"/>
  <input type="hidden" class="row-input-form-raw" value="{input_form}"/>
  <ul class="verify-tools">
    <li><a href="#" class="quick-verify-tool">Bulk verify similar records</a></li>
    <li><a href="#" class="trust-tool">Recorder's trust settings</a></li>
    <li><a href="#" class="edit-record">Edit record</a></li>
  </ul>
<input type="checkbox" class="check-row no-select" style="display: none" value="{occurrence_id}" /></div>
HTML
    ));
    $opts['zoomMapToOutput']=FALSE;
    $grid = report_helper::report_grid($opts);
    $log = '<div id="log-filter">' .
      data_entry_helper::radio_group(array(
        'label'=>lang::get('Show'),
        'fieldname'=>'log-created-by',
        'lookupValues' => array('all'=>lang::get('All log entries'), 'mine'=>lang::get('Only my actions'), 'others'=>lang::get("Only other verifiers' actions")),
        'default'=>'all',
        'class'=>'radio-log-created-by inline'
      )) .

      data_entry_helper::checkbox(array(
        'label'=>lang::get('Only verification decisions'),
        'fieldname'=>'verification-only',
        'class'=>'checkbox-log-verification-comments'
      )) .

      '</div>' .

      report_helper::report_grid(array(
        'dataSource' => 'library/occurrence_comments/filterable_explore_list',
        'id' => 'comments-log',
        'rowId' => 'occurrence_id',
        'linkFilterToMap' => FALSE,
        'reportGroup' => 'verification',
        'ajax' => TRUE,
        'sharing' => $args['sharing'],
        'mode' => 'report',
        'readAuth' => $auth['read'],
        'itemsPerPage' => 20,
        'extraParams' => array_merge($opts['extraParams'], array('data_cleaner_filter' => 'f')),
        'immutableParams' => array('quality_context' => 'all', 'quality' => 'all'),
        'columns' => array(
          array(
            'display' => '',
            'template' => '<input type="hidden" class="row-input-form-link" value="{rootFolder}{input_form}"/>' .
              '<input type="hidden" class="row-input-form-raw" value="{input_form}"/>'
          )
        )
      ));
    if (!empty($args['comment_quick_reply_page_link_label']) && !empty($args['comment_quick_reply_page_link_url'])) {
      data_entry_helper::$javascript .= 'indiciaData.commentQuickReplyPageLinkLabel = "' . $args['comment_quick_reply_page_link_label'] . "\";\n";
      data_entry_helper::$javascript .= 'indiciaData.commentQuickReplyPageLinkURL = "' . $args['comment_quick_reply_page_link_url'] . "\";\n";
    }
    $r = str_replace(array('{grid}', '{log}', '{paramsForm}'), array($grid, $log, $params),
        self::getTemplateWithMap($args, $auth['read'], $opts['extraParams'], $opts['paramDefaults']));
    $link = data_entry_helper::get_reload_link_parts();
    data_entry_helper::$javascript .= 'indiciaData.username = "' . hostsite_get_user_field('name') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.rootUrl = "' . $link['path'] . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.ajaxFormPostUrl="' . iform_ajaxproxy_url($nid, 'occurrence') . "&user_id=$indicia_user_id&sharing=$args[sharing]\";\n";
    data_entry_helper::$javascript .= 'indiciaData.ajaxUrl="' . hostsite_get_url('iform/ajax/verification_5') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.autoDiscard = ' . $args['auto_discard_rows'] . ";\n";
    $imgPath = empty(data_entry_helper::$images_path) ? data_entry_helper::relative_client_helper_path() . "../media/images/" : data_entry_helper::$images_path;
    data_entry_helper::$javascript .= 'indiciaData.imgPath = "' . $imgPath . "\";\n";
    if (!empty($args['indicia_species_layer_feature_type']) && !empty(data_entry_helper::$geoserver_url)) {
      data_entry_helper::$javascript .= "indiciaData.indiciaSpeciesLayer = {\n" .
          '  "title":"' . lang::get('Online recording data for this species') . "\",\n" .
          '  "featureType":"' . $args['indicia_species_layer_feature_type'] . "\",\n" .
          '  "wmsUrl":"' . data_entry_helper::$geoserver_url . "wms\",\n" .
          '  "cqlFilter":"website_id IN (' . implode(',', $websiteIds) . ') AND ' . $args['indicia_species_layer_filter_field'] . "='{filterValue}'\",\n" .
          '  "filterField":"' . $args['indicia_species_layer_ds_filter_field'] . "\",\n" .
          '  "sld":"' . (isset($args['indicia_species_layer_sld']) ? $args['indicia_species_layer_sld'] : '') . "\"\n" .
          "};\n";
    }
    if (!empty($args['additional_wms_species_layer_title'])) {
      data_entry_helper::$javascript .= 'indiciaData.wmsSpeciesLayers = [{"title":"' . $args['additional_wms_species_layer_title'] . '",' .
          '"url":"' . $args['additional_wms_species_layer_url'] . '",' .
          '"settings":' . $args['additional_wms_species_layer_settings'] . ',' .
          '"olSettings":' . $args['additional_wms_species_layer_ol_settings'] .
          "}];\n";
    }
    // Output some translations for JS to use.
    // @todo: Check list for unused (e.g. query stuff)
    data_entry_helper::$javascript .= "indiciaData.popupTranslations = {};\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.title="' . lang::get('Add comment regarding setting status to {1}') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.redetermineTitle="' . lang::get('Provide new determination for this record') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.redetermine="' . lang::get('Redetermine') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.save="' . lang::get('Save and {1}') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.verbV="' . lang::get('accept') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.verbR="' . lang::get('don\'t accept') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.verbC3="' . lang::get('mark as plausible') . "\";\n";

    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.V="' . lang::get('accepted') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.V1="' . lang::get('accepted as correct') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.V2="' . lang::get('accepted as considered correct') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.C3="' . lang::get('plausible') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.R="' . lang::get('not accepted') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.R4="' . lang::get('not accepted as unable to verify') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.R5="' . lang::get('not accepted as incorrect') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.DT="' . lang::get('redetermined') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.sub1="' . lang::get('correct') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.sub2="' . lang::get('considered correct') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.sub3="' . lang::get('plausible') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.templateLabel="' . lang::get('Use comment template') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.pleaseSelect="' . lang::get('Please select if required...') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.commentLabel="' . lang::get('Comment') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.referenceLabel="' . lang::get('External reference or other source information') . "\";\n";

    // @todo: Should this term be unable to accept
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.sub4="' . lang::get('unable to verify') . "\";\n";


    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.sub5="' . lang::get('incorrect') . "\";\n";

    // IS THIS REQUIRED?
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.D="' . lang::get('Query') . "\";\n";

    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.tab_email="' . lang::get('Send query as email') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.tab_comment="' . lang::get('Save query to comments log') . "\";\n";

    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.emailTitle="' . lang::get('Email record details') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.emailInstruction="' .
      lang::get('Use this form to send an email a copy of the record, for example when you would ' .
        'like to get the opinion of another expert.') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.sendEmail="' . lang::get('Send Email') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.emailSent="' . lang::get('The email was sent successfully.') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.requestManualEmail="' .
        lang::get('The webserver is not correctly configured to send emails. Please send the following email usual your email client:') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.multipleWarning="' .
        lang::get('You are about to process multiple records. Please note that this comment will apply to all the ticked records. ' .
        'If you did not intend to do this, please close this box and turn off the Select Records tool before proceeding.') . "\";\n";
    // Translations for querying.
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.queryProbablyCantContact="' .
        lang::get('The record does not have sufficient information for us to be able to contact the recorder. You can leave a query ' .
        'in the box below but we cannot guarantee that they will see it.') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.queryNeedsEmail="' .
      lang::get('The recorder can be contacted by email. If you prefer you can just leave the query as a comment on the ' .
      'record but it is unlikely that they will see it as they haven\'t previously checked their notifications.') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.queryProbablyNeedsEmailNo="' .
      lang::get('The recorder can be contacted by email. If you prefer you can just leave the query as a comment on the ' .
        'record but it they are not known to check their notifications so may not spot the query.') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.queryProbablyNeedsEmailUnknown="' .
      lang::get('The recorder can be contacted by email. If you prefer you can just leave the query as a comment on the ' .
        'record though we don\'t have any information to confirm that they will receive the associated notification.') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.queryProbablyWillGetNotified="' .
      lang::get('The recorder normally checks their notifications so your query can be posted as a comment ' .
      'against the record. If you prefer, you can send a direct email.') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.confidential="' . lang::get('Confidential?') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.logResponseTitle="' . lang::get('Log a response to a Query') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.logResponse="' . lang::get('Save Response') . "\";\n";

    data_entry_helper::$javascript .= "indiciaData.statusTranslations = " . json_encode(VerificationHelper::getTranslatedStatusTerms()) . ";\n";
    data_entry_helper::$javascript .= "indiciaData.commentTranslations = {};\n";
    data_entry_helper::$javascript .= 'indiciaData.commentTranslations.emailed = "' . lang::get('I emailed this record to {1} for checking.') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.commentTranslations.recorder = "' . lang::get('the recorder') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.commentTranslations.expert = "' . lang::get('an expert') . "\";\n";

    data_entry_helper::$javascript .= 'indiciaData.email_subject_send_to_verifier = "' . $args['email_subject_send_to_verifier'] . "\";\n";
    $body = str_replace(array("\r", "\n", '"'), array('', '\n', '\"'), $args['email_body_send_to_verifier']);
    data_entry_helper::$javascript .= 'indiciaData.email_body_send_to_verifier = "' . $body . "\";\n";

    data_entry_helper::$javascript .= 'indiciaData.email_subject_send_to_recorder = "' . $args['email_subject_send_to_recorder'] . "\";\n";
    $body = str_replace(array("\r", "\n"), array('', '\n'), $args['email_body_send_to_recorder']);
    data_entry_helper::$javascript .= 'indiciaData.email_body_send_to_recorder = "' . $body . "\";\n";

    data_entry_helper::$javascript .= 'indiciaData.str_month = "' . lang::get('month') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.expertise_location = "' . $opts['extraParams']['expertise_location'] . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.expertise_surveys = "' . $opts['extraParams']['expertise_surveys'] . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.expertise_taxon_groups = "' . $opts['extraParams']['expertise_taxon_groups'] . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.siteEmail = "' . $site_email = hostsite_get_config_value('site', 'mail', '') . "\";\n";

    if (isset($args['enableWorkflow']) && $args['enableWorkflow']) {
      VerificationHelper::fetchTaxaWithLoggedCommunications($auth['read'], $args['sharing']);
    }
    else {
      data_entry_helper::$javascript .= "indiciaData.workflowEnabled = false;\n";
      data_entry_helper::$javascript .= "indiciaData.workflowTaxonMeaningIDsLogAllComms = [];\n";
    }
    data_entry_helper::add_resource('jqplot');
    data_entry_helper::add_resource('jqplot_bar');
    return $r;
  }

  /*
   * When the user opens the Verification screen, clear any notifications of source_type VT (Verifier Task).
   * This method is only run if the user has configured the page to run with this behaviour.
   */
  private static function clear_verifier_task_notifications($auth) {
    // Using 'submission_list' and 'entries' allows us to specify several top-level submissions to the system
    // i.e. we need to be able to submit several notifications.
    $submission['submission_list']['entries'] = array();
    $submission['id'] = 'notification';
    $notifications = data_entry_helper::get_population_data(array(
      'table' => 'notification',
      'extraParams' => $auth['read'] + array(
        'acknowledged' => 'f',
        'user_id' => hostsite_get_user_field('indicia_user_id'),
        'query' => json_encode(array('in' => array('source_type' => array('VT')))),
      ),
      'nocache' => TRUE
    ));

    if (count($notifications) > 0) {
      // Setup the structure we need to submit.
      foreach ($notifications as $notification) {
        $data['id'] = 'notification';
        $data['fields']['id']['value'] = $notification['id'];
        $data['fields']['acknowledged']['value'] = 't';
        $submission['submission_list']['entries'][] = $data;
      }
      // Submit the stucture for processing.
      $response = data_entry_helper::forward_post_to('save', $submission, $auth['write_tokens']);
      if (!is_array($response) || !array_key_exists('success', $response)) {
        drupal_set_message(print_r($response, TRUE));
      }
    }
  }

  /**
   * Use the mapping from Drupal to Indicia users to get the Indicia user ID for the current logged in Drupal user.
   * If there is a user profile field called profile_indicia_user_id then this value is used instead, for
   * example when the Easy Login feature is installed.
   */
  private static function get_indicia_user_id($args) {
    // Does the host site provide a warehouse user ID?
    if (function_exists('hostsite_get_user_field') && $userId = hostsite_get_user_field('indicia_user_id')) {
      return $userId;
    } else {
      // Default to admin.
      return 1;
    }
  }

  /**
   * Ajax handler to provide the content for the details of a single record.
   */
  public static function ajax_details($website_id, $password, $nid) {
    require_once 'extensions/misc_extensions.php';
    $params = array_merge(['sharing' => 'verification'], hostsite_get_node_field_value($nid, 'params'));
    $details_report = empty($params['record_details_report']) ? 'reports_for_prebuilt_forms/verification_5/record_data' : $params['record_details_report'];
    $attrs_report = empty($params['record_attrs_report']) ? 'reports_for_prebuilt_forms/verification_3/record_data_attributes' : $params['record_attrs_report'];
    iform_load_helpers(array('report_helper', 'VerificationHelper'));
    $readAuth = report_helper::get_read_auth($website_id, $password);
    $options = array(
      'dataSource' => $details_report,
      'readAuth' => $readAuth,
      'sharing' => $params['sharing'],
      'extraParams' => array('occurrence_id' => $_GET['occurrence_id'], 'wantColumns' => 1,
          'locality_type_id' => hostsite_get_config_value('iform', 'profile_location_type_id', 0))
    );
    $reportData = report_helper::get_report_data($options);
    // Set some values which must exist in the record.
    $record = array_merge([
      'wkt' => '',
      'taxon' => '',
      'sample_id' => '',
      'date' => '',
      'entered_sref' => '',
      'taxon_external_key' => '',
      'taxon_meaning_id' => '',
      'record_status' => '',
      'zero_abundance' => '',
      'sref_precision' => '0',
    ], $reportData['records'][0]);
    // Build an array of all the data. This allows the JS to insert the data into emails etc. Note we
    // use an array rather than an assoc array to build the JSON, so that order is guaranteed.
    $data = array();
    $email = '';
    foreach ($reportData['columns'] as $col => $def) {
      if (!empty($def['display']) && $def['visible'] !== 'false' && !empty($record[$col])) {
        $caption = explode(':', $def['display']);
        // Is this a new heading?
        if (!isset($data[$caption[0]])) {
          $data[$caption[0]] = array();
        }
        $val = ($col === 'record_status') ?
          VerificationHelper::getStatusLabel($record[$col], $record['record_substatus'], $record['query']) : $record[$col];
        $data[$caption[0]][] = array('caption' => $caption[1], 'value' => $val);
      }
      if ($col === 'email' && !empty($record[$col])) {
        $email = $record[$col];
      }
    }
    if ($record['zero_abundance'] === 't') {
      $data['Key facts'][] = array(
        'caption' => lang::get('Absence'),
        'value' => lang::get('This is a record indicating absence.'),
      );
    }

    // Do the custom attributes.
    $options = array(
      'dataSource' => $attrs_report,
      'readAuth' => $readAuth,
      'sharing' => $params['sharing'],
      'extraParams' => array('occurrence_id' => $_GET['occurrence_id'])
    );
    $reportData = report_helper::get_report_data($options);
    foreach ($reportData as $attribute) {
      if (!empty($attribute['value'])) {
        if (!isset($data[$attribute['attribute_type'] . ' attributes'])) {
          $data[$attribute['attribute_type'] . ' attributes'] = array();
        }
        $data[$attribute['attribute_type'] . ' attributes'][] = array('caption' => $attribute['caption'], 'value' => $attribute['value']);
      }
    }
    $r = extension_misc_extensions::occurrence_flag_icons(['read' => $readAuth], NULL, NULL, ['record' => $record]);
    $occMetadata = empty($record['metadata']) ? [] : json_decode($record['metadata'], TRUE);
    $r .= self::customMetadataFields($params, $occMetadata);
    $r .= "<table class=\"report-grid\">\n";
    $first = TRUE;
    foreach ($data as $heading => $items) {
      if ($first && !empty($params['record_details_path'])) {
        $heading .= ' <a title="View full details of the record" target="_blank" href="' .
          hostsite_get_url($params['record_details_path'], array('occurrence_id' => $_GET['occurrence_id'])) .
          '"><img src="' . report_helper::$images_path . 'nuvola/find-22px.png" width="22" height="22" /></a>';
        $first = FALSE;
      }
      $r .= "<tr><td colspan=\"2\" class=\"header\">$heading</td></tr>\n";
      foreach ($items as $item) {
        if (!is_null($item['value']) && $item['value'] != '') {
          $value = preg_match('/^http(s)?:\/\//', $item['value']) ? "<a href=\"$item[value]\" target=\"_blank\">$item[value]</a>" : $item['value'];
          $valueClass = strtolower($item['caption']) === 'record status' ? ' class="status"' : '';
          $r .= "<tr><td class=\"caption\">" . $item['caption'] . "</td><td$valueClass>$value</td></tr>\n";
          if ($email === '' && (strtolower($item['caption']) === 'email' || strtolower($item['caption']) === 'email address')) {
            $email = $item['value'];
          }
        }
      }
    }
    $r .= "</table>\n";

    $extra = array();
    $extra['wkt'] = $record['wkt'];
    $extra['taxon'] = $record['taxon'];
    $extra['preferred_taxon'] = $record['preferred_taxon'];
    $extra['default_common_name'] = $record['default_common_name'];
    $extra['recorder'] = $record['recorder'];
    $extra['sample_id'] = $record['sample_id'];
    $extra['created_by_id'] = $record['created_by_id'];
    $extra['input_by_first_name'] = $record['input_by_first_name'];
    $extra['input_by_surname'] = $record['input_by_surname'];
    $extra['website_id'] = $record['website_id'];
    $extra['survey_title'] = $record['survey_title'];
    $extra['survey_id'] = $record['survey_id'];
    $extra['date'] = $record['date'];
    $extra['entered_sref'] = $record['entered_sref'];
    $extra['taxon_external_key'] = $record['taxon_external_key'];
    $extra['taxon_meaning_id'] = $record['taxon_meaning_id'];
    $extra['recorder_email'] = $email;
    $extra['taxon_group'] = $record['taxon_group'];
    $extra['taxon_group_id'] = $record['taxon_group_id'];
    $extra['taxon_list_id'] = $record['taxon_list_id'];
    $extra['localities'] = $record['localities'];
    $extra['locality_ids'] = $record['locality_ids'];
    $extra['location_name'] = $record['location_name'];
    $extra['sref_precision'] = $record['sref_precision'];
    $extra['query'] = $record['query'] === NULL ? '' : $record['query'];
    $extra['metadata'] = isset($record['metadata']) ? json_decode($record['metadata']) : json_decode('{}');
    header('Content-type: application/json');
    echo json_encode(array(
      'content' => $r,
      'data' => $data,
      'extra' => $extra,
    ));
  }

  /**
   * Output custom metadata field controls.
   *
   * If the form configures any extra custom metadata fields to store data in
   * for each occurrence, output the input controls to appear on the details
   * pane.
   *
   * @param array $params
   *   Form configuration.
   * @param array $occMetadata
   *   Occurrences.metadata value from the db for the existing record.
   *
   * @return string
   *   Control HTML.
   */
  private static function customMetadataFields(array $params, array $occMetadata) {
    if (empty($params['custom_occurrence_metadata'])) {
      return '';
    }
    $fields = json_decode($params['custom_occurrence_metadata'], TRUE);
    if (empty($fields)) {
      return '';
    }
    $r = '<fieldset id="metadata"><legend>' . lang::get('Record metadata') . '</legend>';
    foreach ($fields as $idx => $field) {
      $safeTitle = htmlspecialchars($field['title']);
      $safeVal = empty($occMetadata[$field['title']]) ? '' : htmlspecialchars($occMetadata[$field['title']]);
      $r .= "<div><label>$safeTitle";
      if (empty($field['values'])) {
        $r .= "<input class=\"metadata-field\" type=\"text\" data-title=\"$safeTitle\" value=\"$safeVal\"/>";
      }
      else {
        $r .= "<select class=\"metadata-field\" data-title=\"$field[title]\">" .
          '<option value="">&lt;' . lang::get('Please select') . '&gt;</option>';
        $values = report_helper::explode_lines_key_value_pairs($field['values']);
        foreach ($values as $value => $caption) {
          $selected = empty($occMetadata[$field['title']]) || $occMetadata[$field['title']] !== $value
            ? '' : ' selected="selected"';
          $r .= "<option value=\"$value\"$selected>$caption</option>";
        }
        $r .= "</select>";
      }
      $r .= ' <span class="metadata-msg"></span>';
      $r .= '</label></div>';
    }
    global $indicia_templates;
    $r .= "<button type=\"button\" id=\"save-metadata\" class=\"$indicia_templates[buttonDefaultClass]\">" . lang::get('Save') . '</button>';
    $r .= '</fieldset>';
    return $r;
  }

  /**
   * Ajax method allowing the details pane to show the media tab content.
   *
   * @param int $website_id
   *   Website ID for auth.
   * @param string $password
   *   Website password for auth.
   * @param int $nid
   *   Node ID.
   *
   * @throws \exception
   */
  public static function ajax_media($website_id, $password, $nid) {
    iform_load_helpers(['helper_base', 'VerificationHelper']);
    $params = array_merge(['sharing' => 'verification'], hostsite_get_node_field_value($nid, 'params'));
    $readAuth = helper_base::get_read_auth($website_id, $password);
    echo VerificationHelper::getMedia($readAuth, $params, $_GET['occurrence_id'], $_GET['sample_id']);
  }

  /**
   * Ajax handler to get comments for the details pane tab.
   */
  public static function ajax_comments($website_id, $password, $nid) {
    iform_load_helpers(array('VerificationHelper'));
    $params = array_merge(['sharing' => 'verification'], hostsite_get_node_field_value($nid, 'params'));
    $readAuth = helper_base::get_read_auth($website_id, $password);
    echo VerificationHelper::getComments($readAuth, $params, $_GET['occurrence_id']);
    echo self::getCommentsForm();
  }

  /**
   * Ajax handler to get media and comments for a record.
   *
   * E.g. when creating a query email, the comments and photos are injected
   * into the email body.
   */
  public static function ajax_mediaAndComments($website_id, $password, $nid) {
    iform_load_helpers(array('VerificationHelper'));
    $readAuth = helper_base::get_read_auth($website_id, $password);
    $params = array_merge(['sharing' => 'verification'], hostsite_get_node_field_value($nid, 'params'));
    header('Content-type: application/json');
    echo json_encode(array(
      'media' => VerificationHelper::getMedia($readAuth, $params, $_GET['occurrence_id'], $_GET['sample_id']),
      'comments' => VerificationHelper::getComments($readAuth, $params, $_GET['occurrence_id'], TRUE),
    ));
  }

  /**
   * Ajax method to send an email. Takes the subject and body in the $_GET parameters.
   *
   * Response is OK or Fail depending on whether the email was sent or not.
   */
  public static function ajax_email($website_id, $password, $nid) {
    $params = hostsite_get_node_field_value($nid, 'params');
    if (empty($params['email_from_address'])) {
      $fromEmail = hostsite_get_config_value('site', 'mail', '');
    }
    else {
      $fromEmail = $params['email_from_address'];
    }
    $headers = array();
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8;';
    $headers[] = 'From: ' . $fromEmail;
    $headers[] = 'Reply-To: ' . hostsite_get_user_field('mail');
    $headers = implode("\r\n", $headers) . PHP_EOL;
    $emailBody = $_POST['body'];
    $emailBody = str_replace("\n", "<br/>", $emailBody);
    // Send email. Depends upon settings in php.ini being correct.
    $success = mail($_POST['to'],
         $_POST['subject'],
         wordwrap($emailBody, 70),
         $headers);
    echo $success ? 'OK' : 'Fail';
  }

  /**
   * AJAX callback method to fill in the record's experience tab.
   *
   * Echoes a report detailing the total number of records of the species and
   * species group, as well as a breakdown by verified and rejected records.
   * Records link to the Explore report if view_records_report_path is filled
   * in.
   *
   * @param int $website_id
   *   Website warehouse ID.
   * @param string $password
   *   Website warehouse password.
   * @param int $nid
   *   Node ID.
   */
  public static function ajax_experience($website_id, $password, $nid) {
    iform_load_helpers(array('report_helper'));
    $params = array_merge(['sharing' => 'verification'], hostsite_get_node_field_value($nid, 'params'));
    $readAuth = report_helper::get_read_auth($website_id, $password);
    $filter = array('occurrence_id' => $_GET['occurrence_id']);
    if (!empty($params['min_taxon_rank_sort_order'])) {
      $filter['minimum_taxon_rank_sort_order'] = $params['min_taxon_rank_sort_order'];
    }
    $data = report_helper::get_report_data(array(
      'dataSource' => 'library/totals/user_experience_for_record',
      'readAuth' => $readAuth,
      'extraParams' => $filter,
    ));
    $contextId = empty($_GET['context_id']) ? NULL : $_GET['context_id'];
    $r = '';
    foreach ($data as $row) {
      if ($row['v_total'] === 0) {
        $r .= '<p>This recorder has not recorded this ' . $row['type'] . ' before.</p>';
      }
      else {
        $links = [
          'v_3months' => self::recordsLink($row, 'v_3months', $params, $contextId),
          'v_1year' => self::recordsLink($row, 'v_1year', $params, $contextId),
          'v_total' => self::recordsLink($row, 'v_total', $params, $contextId),
          'r_3months' => self::recordsLink($row, 'r_3months', $params, $contextId),
          'r_1year' => self::recordsLink($row, 'r_1year', $params, $contextId),
          'r_total' => self::recordsLink($row, 'r_total', $params, $contextId),
          'total_3months' => self::recordsLink($row, 'total_3months', $params, $contextId),
          'total_1year' => self::recordsLink($row, 'total_1year', $params, $contextId),
          'total_total' => self::recordsLink($row, 'total_total', $params, $contextId),
        ];
        $r .= <<<HTML
<h3>Records of $row[what]</h3>
<table>
  <thead>
    <tr>
      <th></th>
      <th>Last 3 months</th>
      <th>Last year</th>
      <th>All time</th>
    </tr>
  </thead>
  <tbody>
    <tr class="verified">
      <th scope="row">Verified</th>
      <td>$links[v_3months]</td>
      <td>$links[v_1year]</td>
      <td>$links[v_total]</td>
    </tr>
    <tr class="rejected">
      <th scope="row">Rejected</th>
      <td>$links[r_3months]</td>
      <td>$links[r_1year]</td>
      <td>$links[r_total]</td>
    </tr>
    <tr class="total">
      <th scope="row">Total</th>
      <td>$links[total_3months]</td>
      <td>$links[total_1year]</td>
      <td>$links[total_total]</td>
    </tr>
  </tbody>
</table>

HTML;

      }
    }
    // See if there is a filled in profile_experience field for the user. If so, add
    // the statement to the response.
    // @todo Drupal 8 compatibility
    if (!empty($_GET['user_id']) && class_exists('EntityFieldQuery')) {
      // User ID is a warehouse ID, we need the associated Drupal account.
      $query = new EntityFieldQuery();
      $query->entityCondition('entity_type', 'user')
        ->fieldCondition('field_indicia_user_id', 'value', $_GET['user_id'], '=');
      $result = $query->execute();
      if ($result) {
        $users = array_keys($result['user']);
        $experience = hostsite_get_user_field('experience', FALSE, FALSE, $users[0]);
        if ($experience) {
          $r .= "<h3>User's description of their experience</h3><p>$experience</p>\n";
        }
      }
    }
    if (empty($r)) {
      $r = lang::get("No information available on this recorder's experience");
    }
    echo $r;
  }

  /**
   * Ajax handler to determine if a user is likely to see a notification added to their comments.
   *
   * @return string
   *   yes, no or maybe.
   */
  public static function ajax_do_they_see_notifications($website_id, $password) {
    iform_load_helpers(array('VerificationHelper'));
    $readAuth = helper_base::get_read_auth($website_id, $password);
    echo VerificationHelper::doesUserSeeNotifications($readAuth, $_GET['user_id']);
  }

  /**
   * Returns the HTML for a comments form.
   *
   * @return string
   *   Form HTML.
   */
  private static function getCommentsForm() {
    $allowConfidential = isset($_GET['allowconfidential']) && $_GET['allowconfidential'] === 'true';
    $r = '<form><fieldset><legend>' . lang::get('Add new comment') . '</legend>';
    if ($allowConfidential) {
      $r .= '<label><input type="checkbox" id="comment-confidential" /> ' . lang::get('Confidential?') . '</label><br>';
    }
    else {
      $r .= '<input type="hidden" id="comment-confidential" value="f" />';
    }
    $r .= data_entry_helper::textarea([
      'fieldname' => 'comment-text'
    ]);
    $r .= data_entry_helper::text_input([
      'label' => lang::get('External reference or other source'),
      'fieldname' => 'comment-reference'
    ]);
    $r .= '<button type="button" class="default-button" ' .
      'onclick="indiciaFns.saveComment(jQuery(\'#comment-text\').val(), jQuery(\'#comment-reference\').val(), jQuery(\'#comment-confidential\:checked\').length, false);">' . lang::get('Save') . '</button>';
    $r .= '</fieldset></form>';
    return $r;
  }

  /**
   * Provide a link to explore records.
   *
   * Convert a number on the Experience tab into a link to the Explore page for
   * the underlying records.
   *
   * @return string
   *   Link HTML.
   */
  private static function recordsLink($row, $value, $nodeParams, $contextId) {
    if (!empty($nodeParams['view_records_report_path']) && !empty($_GET['user_id'])) {
      $tokens = explode('_', $value);
      $params = array(
        'filter-date_age' => '',
        'filter-indexed_location_list' => '',
        'filter-indexed_location_id' => '',
        'filter-taxon_group_list' => '',
        'filter-user_id' => $_GET['user_id'],
        'filter-my_records' => 1,
      );
      // Preserve the current verification context in any links to reports.
      if (!empty($contextId)) {
        $params['context_id'] = $contextId;
      }
      switch ($tokens[0]) {
        case 'r':
          $params['filter-quality'] = 'R';
          break;

        case 'v':
          $params['filter-quality'] = 'V';
          break;
      }
      switch ($tokens[1]) {
        case '3months':
          $params['filter-input_date_age'] = '3 months';
          break;

        case '1year':
          $params['filter-input_date_age'] = '1 year';
          break;
      }
      if ($row['type'] === 'species') {
        $params['filter-taxon_meaning_list'] = $row['what_id'];
      }
      else {
        $params['filter-taxon_group_list'] = $row['what_id'];
      }
      $linkTo = hostsite_get_url($nodeParams['view_records_report_path'], $params);
      return <<<HTML
<a target="_blank" href="$linkTo">{$row[$value]}</a>
HTML;
    }
    else {
      return $row[$value];
    }
  }

  /**
   * Ajax method to retrieve phenology data for a species by external key.
   */
  public static function ajax_phenology($website_id, $password, $nid) {
    iform_load_helpers(array('report_helper'));
    $params = array_merge(['sharing' => 'verification'], hostsite_get_node_field_value($nid, 'params'));
    $readAuth = report_helper::get_read_auth($website_id, $password);
    $extraParams = array(
      'external_key' => (empty($_GET['external_key']) || $_GET['external_key'] === 'null') ? '' : $_GET['external_key'],
      'taxon_meaning_id' => (empty($_GET['external_key']) || $_GET['external_key'] === 'null') ? $_GET['taxon_meaning_id'] : '',
      'date_from' => '',
      'date_to' => '',
      'survey_id' => '',
      'quality' => 'V'
    );
    $data = report_helper::get_report_data(array(
      'dataSource' => 'library/months/phenology',
      'readAuth' => $readAuth,
      'extraParams' => $extraParams,
      'sharing' => $params['sharing']
    ));
    // Must output all months.
    $output = array();
    for ($i = 1; $i <= 12; $i++) {
      $output[] = array($i, 0);
    }
    foreach ($data as $month) {
      // -1 here, because our array is zero indexed, but the report returns a real month number.
      $output[$month['name'] - 1][1] = intval($month['value']);
    }
    echo json_encode($output);
  }

  /**
   * Ajax method to proxy requests for bulk verification on to the warehouse, attaching write auth
   * as it goes.
   */
  public static function ajax_bulk_verify($website_id, $password) {
    iform_load_helpers(array('data_entry_helper'));
    $auth = data_entry_helper::get_read_write_auth($website_id, $password);
    $url = data_entry_helper::$base_url . "index.php/services/data_utils/bulk_verify";
    $params = array_merge($_POST, $auth['write_tokens']);
    $response = data_entry_helper::http_post($url, $params);
    echo $response['output'];
  }

  private static function reportFilterPanel($args, $readAuth) {
    $options = array(
      'allowSave' => TRUE,
      'sharing' => $args['sharing'],
      'linkToMapDiv' => 'map',
      'filter-quality' => 'P',
      'overridePermissionsFilters' => self::$overridePermissionsFilters,
    );
    $defaults = report_helper::explode_lines_key_value_pairs($args['param_defaults']);
    foreach ($defaults as $field => $value) {
      $options["filter-$field"] = $value;
    }
    if (!empty($args['indexed_location_type_ids'])) {
      $options['indexedLocationTypeIds'] = array_map('intval', explode(',', $args['indexed_location_type_ids']));
    }
    if (!empty($args['other_location_type_ids'])) {
      $options['otherLocationTypeIds'] = array_map('intval', explode(',', $args['other_location_type_ids']));
    }
    $options['taxon_list_id'] = hostsite_get_config_value('iform', 'master_checklist_id', 0);
    $hiddenStuff = '';
    $r = report_filter_panel($readAuth, $options, $args['website_id'], $hiddenStuff);
    return $r . $hiddenStuff;
  }

}
