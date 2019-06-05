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

require_once('includes/map.php');
require_once('includes/report.php');
require_once('includes/report_filters.php');

/**
 * Prebuilt Indicia data form that lists the output of a samples report with an option
 * to accept or reject.
 *
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_verification_samples {

  private static $statusTermsTranslated = false;

  private static $statusTerms = array(
    'V' => 'Accepted',
    'R' => 'Not accepted',
    'D' => 'Query', // deprecated
    'I' => 'In progress',
    'T' => 'Test record',
    'C' => 'Not reviewed'
  );

  /**
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_verification_samples_definition() {
    return array(
      'title'=>'Verification for samples',
      'category' => 'Verification',
      'description'=>'Verification form supporting verification of samples/submitted forms.'
    );
  }

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    $r = array_merge(
      iform_map_get_map_parameters(),
      iform_report_get_minimal_report_parameters(),
      array(
        array(
          'name'=>'mapping_report_name',
          'caption'=>'Report for map output',
          'description'=>'Report used to obtain the output for the map. Should have the same parameters as the grid report but only needs to '.
            'return the sample id, geom and any shape formatting.',
          'type'=>'report_helper::report_picker',
          'group'=>'Report Settings',
          'default'=>'library/samples/filterable_explore_list_mapping'
        ),
        array(
          'name'=>'record_details_report',
          'caption'=>'Report for record details',
          'description'=>'Report used to obtain the details of a sample. See reports_for_prebuilt_forms/verification_3/record_data.xml for an example.',
          'type'=>'report_helper::report_picker',
          'group'=>'Report Settings',
          'default'=>'reports_for_prebuilt_forms/verification_samples/record_data'
        ),
        array(
          'name'=>'record_attrs_report',
          'caption'=>'Report for record attributes',
          'description'=>'Report used to obtain the custom attributes of a record. See reports_for_prebuilt_forms/verification_3/record_data_attributes.xml for an example.',
          'type'=>'report_helper::report_picker',
          'group'=>'Report Settings',
          'default'=>'reports_for_prebuilt_forms/verification_samples/record_data_attributes'
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
            'Note that template columns cannot be sorted by clicking grid headers." }
        }
      }
    ]
  }',
          'group' => 'Report Settings',
          'required' => false
        ), array(
          'name'=>'email_subject_send_to_verifier',
          'caption'=>'Send to Expert Email Subject',
          'description'=>'Default subject for the send to expert email. Replacements allowed include %id%.',
          'type'=>'string',
          'default' => 'Requesting your opinion on a sample (ID:%id%)',
          'group' => 'Verifier emails'
        ), array(
          'name'=>'email_body_send_to_verifier',
          'caption'=>'Send to Expert Email Body',
          'description'=>'Default body for the send to expert email. Replacements allowed include %id% and %record% which is replaced to give details of the record.',
          'type'=>'textarea',
          'default' => 'We would appreciate your opinion on the following record. Please reply to this mail with "accepted", "not accepted" or "query" '.
            'in the email body, followed by any comments you have including the proposed re-identification if relevant on the next line.'.
            "\n\n%record%",
          'group' => 'Verifier emails'
        ), array(
          'name'=>'email_subject_send_to_recorder',
          'caption'=>'Send to Recorder Email Subject',
          'description'=>'Default subject for the send query to recorder email. Replacements allowed include %id%.',
          'type'=>'string',
          'default' => 'Query on your sample (ID:%id%)',
          'group' => 'Recorder emails'
        ), array(
          'name'=>'email_body_send_to_recorder',
          'caption'=>'Send to Recorder Email Body',
          'description'=>'Default body for the send to recorder email. Replacements allowed include %id% and %record% which is replaced to give details of the record.',
          'type'=>'textarea',
          'default' => 'The following record requires confirmation. Please could you reply to this email stating how confident you are that the record is correct '.
            'and any other information you have which may help to confirm this.'.
            "\n\n%record%",
          'group' => 'Recorder emails'
        ), array(
          'name'=>'auto_discard_rows',
          'caption'=>'Automatically remove rows',
          'description'=>'If checked, then when changing the status of a record the record is removed from the grid if it no '.
            'longer matches the grid filter.',
          'type'=>'checkbox',
          'default'=>'true',
          'required'=>false
        ),
        array(
          'name'=>'indexed_location_type_ids',
          'caption'=>'Indexed location type IDs',
          'description'=>'Comma separated list of location type IDs for location layers that are available to search against.',
          'type'=>'text_input',
          'required'=>false,
          'group'=>'Other Map Settings'
        ),
        array(
          'name'=>'other_location_type_ids',
          'caption'=>'Other location type IDs',
          'description'=>'Comma separated list of location type IDs for location layers that are available to search against.',
          'type'=>'text_input',
          'required'=>false,
          'group'=>'Other Map Settings'
        ),
        array(
          'name'=>'clear_verification_task_notifications',
          'caption'=>'Clear verification task notifications?',
          'description'=>'Automatically clear any verification task notifications when the user opens the verification screen.',
          'type'=>'boolean',
          'group'=>'Notification Settings',
          'default' => false,
          'required' => 'false'
        )
      )
    );
    // Set default values for the report
    foreach($r as &$param) {
      if ($param['name']=='report_name')
        $param['default']='library/samples/filterable_explore_list';
      elseif ($param['name']=='param_presets') {
        $param['default'] = 'survey_id=
date_from=
date_to=
smpattrs=';
      }
      elseif ($param['name']=='param_defaults')
        $param['default'] = 'id=
record_status=C
records=unverified
searchArea=
idlist=';

    }
    return $r;
  }

  /**
   * Returns the HTML for the standard set of tabs, excluding the details and optional map tab.
   * @return string HTML to insert onto the page
   */
  private static function other_tab_html() {
    $r = '<div id="media-tab"></div>';
    $r .= '<div id="comments-tab"></div>';
    return $r;
  }

  private static function get_template_with_map($args, $readAuth, $extraParams, $paramDefaults) {
    $r = '<div id="outer-with-map" class="ui-helper-clearfix">';
    $r .= '<div id="grid" class="left" style="width:65%">{paramsForm}{grid}</div>';
    $r .= '<div id="map-and-record" class="right" style="width: 34%"><div id="summary-map">';
    $options = iform_map_get_map_options($args, $readAuth);
    $olOptions = iform_map_get_ol_options($args);
    // This is used for drawing, so need an editlayer, but not used for input
    $options['editLayer'] = true;
    $options['editLayerInSwitcher'] = true;
    $options['clickForSpatialRef'] = false;
    $options['featureIdField']='sample_id';
    $r .= map_helper::map_panel(
      $options,
      $olOptions
    );
    $reportMapOpts=array(
      'dataSource' => !empty($args['mapping_report_name']) ? $args['mapping_report_name'] : $args['report_name'],
      'mode' => 'report',
      'readAuth' => $readAuth,
      'autoParamsForm' => false,
      'extraParams' => $extraParams,
      'paramDefaults' => $paramDefaults,
      'reportGroup' => 'verification',
      'clickableLayersOutputMode' => 'report',
      'rowId'=>'sample_id',
      'sharing'=>'verification',
      'ajax'=>TRUE
    );
    $r .= report_helper::report_map($reportMapOpts);
    $r .= '</div>';
    if (function_exists('hostsite_get_user_field') && $locationId=hostsite_get_user_field('location_expertise', false))
      iform_map_zoom_to_location($locationId, $readAuth);
    $r .= '<div id="record-details-wrap" class="ui-widget ui-widget-content">';
    $r .= self::instructions('grid on the left');
    $r .= '<div id="record-details-content" style="display: none">';
    $r .= '<div id="record-details-toolbar">';
    $r .= '<div id="action-buttons">';
    $r .= '<div id="action-buttons-status" class="action-buttons-row">';
    $r .= '<div class="col-1"><label>' . lang::get('Set status:'). '</label></div>';
    $imgPath = empty(data_entry_helper::$images_path) ? data_entry_helper::relative_client_helper_path()."../media/images/" : data_entry_helper::$images_path;
    $r .= self::status_buttons($imgPath);
    $r .= '</div>';
    $r .= '<div id="action-buttons-other" class="action-buttons-row"><div class="col-1"><label>Other actions:</label></div>';
    $r .= self::other_action_buttons($imgPath);
    $r .= '</div></div>';
    $r .= '<div id="record-details-tabs">';
    // note - there is a dependency in the JS that comments is the last tab and media the 2nd to last.
    $r .= data_entry_helper::tab_header(array(
      'tabs'=>array(
        '#details-tab'=>lang::get('Details'),
        '#media-tab'=>lang::get('Media'),
        '#comments-tab'=>lang::get('Comments')
      )
    ));
    data_entry_helper::$javascript .= "indiciaData.detailsTabs = ['details','media','comments'];\n";
    data_entry_helper::enable_tabs(array(
      'divId'=>'record-details-tabs'
    ));
    $r .= '<div id="details-tab"></div>';
    $r .= self::other_tab_html();
    $r .= '</div></div></div></div></div>';
    return $r;
  }

  /**
   * Constructs HTML for a block of instructions.
   * @param string $gridPos Pass in a description of where the records grid is relative to the instruction  block, e.g. 'grid below' or 'grid on the left'
   * @return string HTML for the instruction div
   */
  private static function instructions($gridpos) {
    $r = '<div id="instructions">'.lang::get('You can').":\n<ul>\n";
    $r .= '<li>'.lang::get('Select the records to include in the list of records to verify using the drop-down box above the grid.')."</li>\n";
    $r .= '<li>'.lang::get('Filter the list of records by using the <strong>Create a filter</strong> button and save filters for future use.')."</li>\n";
    $r .= '<li>'.lang::get("Click on a record in the $gridpos to view the details.")."</li>\n";
    $r .= '<li>'.lang::get('When viewing the record details, verify, reject, query or email the record details for confirmation.')."</li>\n";
    $r .= '<li>'.lang::get('When viewing the record details, view and add comments on the record.')."</li>\n";
    $r .= '<li>'.lang::get('Click on media files in the grid or on the record details Media tab to review the original file and check the record\'s identification.')."</li>\n";
    $r .= '<li>'.lang::get('Use the ... button to the left of each record to view bulk-verification options for similar records.')."</li>\n";
    $r .= '<li>'.lang::get('Use the <strong>Review grid</strong> button above the grid to apply changes to all the records loaded in the grid in one step.')."</li>\n";
    $r .= '<li>'.lang::get('Use the <strong>Review tick list</strong> button above the grid to apply changes to a selection of records from the grid in one step.')."</li>\n";
    $r .= '</ul></div>';
    return $r;
  }

  private static function check_prerequisites() {
    $msg = false;
    if (!function_exists('iform_ajaxproxy_url'))
      $msg = 'The AJAX Proxy module must be enabled to support saving filters on the verification page.';
    if (!hostsite_module_exists('easy_login'))
      $msg = 'The verification 4 page requires the Easy Login module to be enabled.';
    if (!function_exists('hostsite_get_user_field') || !hostsite_get_user_field('indicia_user_id'))
      $msg = 'Before verifying records, please visit your user account profile and ensure that you have entered your full name, then save it.';
    if ($msg)
      hostsite_show_message($msg, 'warning');
    return $msg ? false : true;
  }

  private static function status_buttons($imgPath) {
    $r = '<div id="actions-less" class="buttons-row">';
    $r .= '<button type="button" id="btn-accepted" title="'.lang::get('Set to accepted').'"><img width="18" height="18" src="'.$imgPath.'nuvola/ok-16px.png"/></button>';
    $r .= '<button type="button" id="btn-notaccepted" title="'.lang::get('Set to not accepted').'"><img width="18" height="18" src="'.$imgPath.'nuvola/cancel-16px.png"/></button>';
    // $r .= '<button type="button" id="btn-reject" title="'.lang::get('Reject').'"><img width="18" height="18" src="'.$imgPath.'nuvola/cancel-16px.png"/></button>';
    $r .= '</div>'; // action-less
    return $r;
  }

  /**
   * Returns the set of buttons with contact, query and edit tools
   * @param string $imgPath Path to the images folder.
   * @return string HTML
   */
  private static function other_action_buttons($imgPath) {
    $r = '<div id="other-actions" class="buttons-row">';
    // @todo Following button needs to be disabled if recorder cannot be contacted (email or notifications system). Exclude global emails like iSpot.
    // @todo Query icon - question mark in speech bubble
    $r .= '<button type="button" id="btn-query" class="default-button" title="'.
      lang::get('Raise a query against this record with the recorder').'">'.lang::get('Query').'</button>';
    // @todo Email icon with motion lines or arrow
    // send details to expert
    $r .= '<button type="button" id="btn-email-expert" class="default-button" title="'.
      lang::get('Email the record details to another expert for their opinion').'">'.lang::get('Send to expert').'</button>';
    // @todo following needs to be disabled if record is not on iRecord.
    $r .= '<button type="button" id="btn-edit-record" class="default-button" title="'.
      lang::get('Edit the record on its original data entry form.').'">'.lang::get('Edit').'</button>';
    // @todo remove the code related to btn-edit-verify
    $r .= '</div>';
    return $r;
  }

  /**
   * Return the Indicia form code.
   * Expects there to be a sample attribute with caption 'Email' containing the email
   * address.
   * @param array $args Input parameters.
   * @param array $nid Drupal node object's ID
   * @param array $response Response from Indicia services after posting a verification.
   * @return string HTML
   */
  public static function get_form($args, $nid, $response) {
    if (!self::check_prerequisites())
      return '';
    iform_load_helpers(array('data_entry_helper', 'map_helper', 'report_helper'));
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    //Clear Verifier Tasks automatically when they open the screen if the option is set.
    if ($args['clear_verification_task_notifications']&&hostsite_get_user_field('indicia_user_id'))
      self::clear_verifier_task_notifications($auth);
    // set some defaults, applied when upgrading from a form configured on a previous form version.
    if (empty($args['email_subject_send_to_recorder']))
      $args['email_subject_send_to_recorder'] = 'Sample requires confirmation (ID:%id%)';
    if (empty($args['email_body_send_to_recorder']))
      $args['email_body_send_to_recorder'] = 'The following record requires confirmation. Please could you reply to this email stating how confident you are that the record is correct '.
        'and any other information you have which may help to confirm this.'.
        "\n\n%record%";
    if (isset($_POST['enable'])) {
      module_enable(array('iform_ajaxproxy'));
      drupal_set_message(lang::get('The Indicia AJAX Proxy module has been enabled.', 'info'));
    }
    elseif (!defined('IFORM_AJAXPROXY_PATH')) {
      $r = '<p>'.lang::get('The Indicia AJAX Proxy module must be enabled to use this form. This lets the form save verifications to the '.
          'Indicia Warehouse without having to reload the page.').'</p>';
      $r .= '<form method="post">';
      $r .= '<input type="hidden" name="enable" value="t"/>';
      $r .= '<input type="submit" value="'.lang::get('Enable Indicia AJAX Proxy').'"/>';
      $r .= '</form>';
      return $r;
    }
    if (function_exists('drupal_add_js'))
      drupal_add_js('misc/collapse.js');
    // fancybox for popup comment forms etc
    data_entry_helper::add_resource('fancybox');
    data_entry_helper::add_resource('validation');
    $indicia_user_id=self::get_indicia_user_id($args);
    // Find a list of websites we are allowed verify
    if (function_exists('hostsite_module_exists') && hostsite_module_exists('easy_login')) {
      if (strpos($args['param_presets'].$args['param_defaults'], 'expertise_location')===false)
        $args['param_presets'].="\nexpertise_location={profile_location_expertise}";
      if (strpos($args['param_presets'].$args['param_defaults'], 'expertise_taxon_groups')===false)
        $args['param_presets'].="\nexpertise_taxon_groups={profile_taxon_groups_expertise}";
      if (strpos($args['param_presets'].$args['param_defaults'], 'expertise_surveys')===false)
        $args['param_presets'].="\nexpertise_surveys={profile_surveys_expertise}";
    }
    $args['sharing']='verification';
    $opts = array_merge(
      iform_report_get_report_options($args, $auth['read']),
      array(
        'id' => 'verification-grid',
        'reportGroup' => 'verification',
        'rowId' => 'sample_id',
        'paramsFormButtonCaption' => lang::get('Filter'),
        'paramPrefix'=>'<div class="report-param">',
        'paramSuffix'=>'</div>',
        'sharing'=>'verification',
        'ajax'=>TRUE,
        'callback' => 'verificationGridLoaded'
      )
    );
    $opts['columns'][] = array(
      'display'=>'',
      'template' => '<div class="nowrap">'.
          '<input type="hidden" class="row-input-form" value="{rootFolder}{input_form}"/><input type="hidden" class="row-belongs-to-site" value="{belongs_to_site}"/>'.
          '<input type="checkbox" class="check-row no-select" style="display: none" value="{occurrence_id}" /></div>'
    );
    $params = self::report_filter_panel($args, $auth['read']);
    $opts['zoomMapToOutput']=false;
    $grid = report_helper::report_grid($opts);
    $r = str_replace(array('{grid}','{paramsForm}'), array($grid, $params),
      self::get_template_with_map($args, $auth['read'], $opts['extraParams'], $opts['paramDefaults']));
    $link = data_entry_helper::get_reload_link_parts();
    global $user;
    data_entry_helper::$javascript .= 'indiciaData.username = "'.$user->name."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.rootUrl = "'.$link['path']."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.ajaxFormPostUrl="'.iform_ajaxproxy_url($nid, 'sample')."&user_id=$indicia_user_id&sharing=verification\";\n";
    data_entry_helper::$javascript .= 'indiciaData.ajaxUrl="'.url('iform/ajax/verification_samples')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.autoDiscard = '.$args['auto_discard_rows'].";\n";
    $imgPath = empty(data_entry_helper::$images_path) ? data_entry_helper::relative_client_helper_path()."../media/images/" : data_entry_helper::$images_path;
    data_entry_helper::$javascript .= 'indiciaData.imgPath = "' . $imgPath . "\";\n";
    // output some translations for JS to use
    // @todo: Check list for unused (e.g. query stuff)
    data_entry_helper::$javascript .= "indiciaData.popupTranslations = {};\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.title="'.lang::get('Add comment regarding setting status to {1}')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.save="'.lang::get('Save and {1}')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.verbV="'.lang::get('accept')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.verbR="'.lang::get('don\'t accept')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.verbC3="'.lang::get('mark as plausible')."\";\n";

    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.V="'.lang::get('accepted')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.R="'.lang::get('not accepted')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.sub1="'.lang::get('correct')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.sub2="'.lang::get('considered correct')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.sub3="'.lang::get('plausible')."\";\n";

    // @todo: Should this term be unable to accept
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.sub4="'.lang::get('unable to verify')."\";\n";


    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.sub5="'.lang::get('incorrect')."\";\n";

    // IS THIS REQUIRED
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.D="'.lang::get('Query')."\";\n";

    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.tab_email="'.lang::get('Send query as email')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.tab_comment="'.lang::get('Save query to comments log')."\";\n";

    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.emailTitle="'.lang::get('Email record details')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.emailInstruction="'.
      lang::get('Use this form to send an email a copy of the record, for example when you would ' .
        'like to get the opinion of another expert.')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.sendEmail="'.lang::get('Send Email')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.emailSent="'.lang::get('The email was sent successfully.')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.requestManualEmail="'.
      lang::get('The webserver is not correctly configured to send emails. Please send the following email usual your email client:')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.multipleWarning="'.
      lang::get('You are about to process multiple records. Please note that this comment will apply to all the ticked records. '.
        'If you did not intend to do this, please close this box and turn off the Select Records tool before proceeding.')."\";\n";
    // translations for querying
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.queryProbablyCantContact="'.
      lang::get('The record does not have sufficient information for us to be able to contact the recorder. You can leave a query ' .
        'in the box below but we cannot guarantee that they will see it.')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.queryNeedsEmail="'.
      lang::get('The recorder can be contacted by email. If you prefer you can just leave the query as a comment on the ' .
        'record but it is unlikely that they will see it as they haven\'t previously checked their notifications.')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.queryProbablyNeedsEmailNo="'.
      lang::get('The recorder can be contacted by email. If you prefer you can just leave the query as a comment on the ' .
        'record but it they are not known to check their notifications so may not spot the query.')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.queryProbablyNeedsEmailUnknown="'.
      lang::get('The recorder can be contacted by email. If you prefer you can just leave the query as a comment on the ' .
        'record though we don\'t have any information to confirm that they will receive the associated notification.')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.popupTranslations.queryProbablyWillGetNotified="'.
      lang::get('The recorder normally checks their notifications so your query can be posted as a comment ' .
        'against the record. If you prefer, you can send a direct email.')."\";\n";
    self::translateStatusTerms();
    data_entry_helper::$javascript .= "indiciaData.statusTranslations = " . json_encode(self::$statusTerms) . ";\n";
    data_entry_helper::$javascript .= "indiciaData.commentTranslations = {};\n";
    data_entry_helper::$javascript .= 'indiciaData.commentTranslations.emailed = "'.lang::get('I emailed this sample to {1} for checking.')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.commentTranslations.recorder = "'.lang::get('the recorder')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.commentTranslations.expert = "'.lang::get('an expert')."\";\n";

    data_entry_helper::$javascript .= 'indiciaData.email_subject_send_to_verifier = "'.$args['email_subject_send_to_verifier']."\";\n";
    $body = str_replace(array("\r", "\n", '"'), array('', '\n', '\"'), $args['email_body_send_to_verifier']);
    data_entry_helper::$javascript .= 'indiciaData.email_body_send_to_verifier = "'.$body."\";\n";

    data_entry_helper::$javascript .= 'indiciaData.email_subject_send_to_recorder = "'.$args['email_subject_send_to_recorder']."\";\n";
    $body = str_replace(array("\r", "\n"), array('', '\n'), $args['email_body_send_to_recorder']);
    data_entry_helper::$javascript .= 'indiciaData.email_body_send_to_recorder = "'.$body."\";\n";

    data_entry_helper::$javascript .= 'indiciaData.str_month = "'.lang::get('month')."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.expertise_location = "'.$opts['extraParams']['expertise_location']."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.expertise_surveys = "'.$opts['extraParams']['expertise_surveys']."\";\n";
    data_entry_helper::$javascript .= 'indiciaData.expertise_taxon_groups = "'.$opts['extraParams']['expertise_taxon_groups']."\";\n";
    data_entry_helper::add_resource('jqplot');
    data_entry_helper::add_resource('jqplot_bar');
    return $r;
  }

  /**
   * Convert the list of status terms into a translated version.
   */
  private static function translateStatusTerms() {
    if (!self::$statusTermsTranslated) {
      foreach (self::$statusTerms as &$term) {
        $term=lang::get($term);
      }
      self::$statusTermsTranslated = true;
    }
  }

  /*
   * When the user opens the Verification screen, clear any notifications of source_type VT (Verifier Task).
   * This method is only run if the user has configured the page to run with this behaviour.
   */
  private static function clear_verifier_task_notifications($auth) {
    //Using 'submission_list' and 'entries' allows us to specify several top-level submissions to the system
    //i.e. we need to be able to submit several notifications.
    $submission['submission_list']['entries'] = array();
    $submission['id']='notification';
    $notifications = data_entry_helper::get_population_data(array(
      'table' => 'notification',
      'extraParams' => $auth['read'] + array('acknowledged' => 'f', 'user_id'=>hostsite_get_user_field('indicia_user_id'),
          'query' => json_encode(array('in' => array('source_type' => array('VT'))))),
      'nocache' => true
    ));

    if (count($notifications)>0) {
      //Setup the structure we need to submit.
      foreach ($notifications as $notification) {
        $data['id']='notification';
        $data['fields']['id']['value'] = $notification['id'];
        $data['fields']['acknowledged']['value'] = 't';
        $submission['submission_list']['entries'][] = $data;
      }
      //Submit the stucture for processing
      $response = data_entry_helper::forward_post_to('save', $submission, $auth['write_tokens']);
      if (!is_array($response) || !array_key_exists('success', $response))
        drupal_set_message(print_r($response,true));
    }
  }

  /**
   * Use the mapping from Drupal to Indicia users to get the Indicia user ID for the current logged in Drupal user.
   * If there is a user profile field called profile_indicia_user_id then this value is used instead, for
   * example when the Easy Login feature is installed.
   */
  private static function get_indicia_user_id($args) {
    // Does the host site provide a warehouse user ID?
    if (function_exists('hostsite_get_user_field') && $userId = hostsite_get_user_field('indicia_user_id'))
      return $userId;
    else
      return 1; // default to admin
  }

  /**
   * Ajax handler to provide the content for the details of a single record.
   */
  public static function ajax_details($website_id, $password, $nid) {
    $params = hostsite_get_node_field_value($nid, 'params');
    $details_report = empty($params['record_details_report']) ? 'reports_for_prebuilt_forms/verification_samples/record_data' : $params['record_details_report'];
    $attrs_report = empty($params['record_attrs_report']) ? 'reports_for_prebuilt_forms/verification_samples/record_data_attributes' : $params['record_attrs_report'];
    iform_load_helpers(array('report_helper'));
    $readAuth = report_helper::get_read_auth($website_id, $password);
    $options = array(
      'dataSource' => $details_report,
      'readAuth' => $readAuth,
      'sharing' => 'verification',
      'extraParams' => array('sample_id'=>$_GET['sample_id'], 'wantColumns'=>1,
          'locality_type_id' => hostsite_get_config_value('iform', 'profile_location_type_id', 0))
    );
    $reportData = report_helper::get_report_data($options);
    // set some values which must exist in the record
    $record = array_merge(array(
      'wkt'=>'','sample_id'=>'','date'=>'','entered_sref'=>'','record_status'=>''
    ), $reportData['records'][0]);
    // build an array of all the data. This allows the JS to insert the data into emails etc. Note we
    // use an array rather than an assoc array to build the JSON, so that order is guaranteed.
    $data = array();
    $email='';
    foreach($reportData['columns'] as $col=>$def) {
      if ($def['visible']!=='false' && !empty($record[$col])) {
        $caption = explode(':', $def['display']);
        // is this a new heading?
        if (!isset($data[$caption[0]]))
          $data[$caption[0]]=array();
        $val = ($col==='record_status') ? self::status_label($record[$col]) : $record[$col];
        $data[$caption[0]][] = array('caption'=>$caption[1], 'value'=>$val);
      }
      if ($col==='email' && !empty($record[$col]))
        $email=$record[$col];
    }
    // Do the custom attributes
    $options = array(
      'dataSource' => $attrs_report,
      'readAuth' => $readAuth,
      'sharing' => 'verification',
      'extraParams' => array('sample_id'=>$_GET['sample_id'])
    );
    $reportData = report_helper::get_report_data($options);
    foreach ($reportData as $attribute) {
      if (!empty($attribute['value'])) {
        if (!isset($data[$attribute['attribute_type'] . ' attributes']))
          $data[$attribute['attribute_type'] . ' attributes']=array();
        $data[$attribute['attribute_type'] . ' attributes'][] = array('caption'=>$attribute['caption'], 'value'=>$attribute['value']);
      }
    }

    $r = "<table class=\"report-grid\">\n";
    foreach($data as $heading=>$items) {
      $r .= "<tr><td colspan=\"2\" class=\"header\">$heading</td></tr>\n";
      foreach ($items as $item) {
        if (!is_null($item['value']) && $item['value'] != '') {
          $r .= "<tr><td class=\"caption\">".$item['caption']."</td><td>".$item['value'] ."</td></tr>\n";
          if ($email==='' && (strtolower($item['caption'])==='email' || strtolower($item['caption'])==='email address'))
            $email=$item['value'];
        }
      }
    }
    $r .= "</table>\n";

    $extra=array();
    $extra['wkt'] = $record['wkt'];
    $extra['recorder'] = $record['recorder'];
    $extra['sample_id'] = $record['sample_id'];
    $extra['created_by_id'] = $record['created_by_id'];
    $extra['input_by_first_name'] = $record['input_by_first_name'];
    $extra['input_by_surname'] = $record['input_by_surname'];
    $extra['survey_title'] = $record['survey_title'];
    $extra['survey_id'] = $record['survey_id'];
    $extra['date'] = $record['date'];
    $extra['entered_sref'] = $record['entered_sref'];
    $extra['recorder_email'] = $email;
    $extra['localities'] = $record['localities'];
    $extra['locality_ids'] = $record['locality_ids'];
    header('Content-type: application/json');
    echo json_encode(array(
      'content' => $r,
      'data' => $data,
      'extra' => $extra
    ));
  }

  /**
   * Converts a status into a readable label (e.g. accepted,)
   * @param string $status Status code from database (e.g. 'C')
   * @return string
   */
  private static function status_label($status) {
    self::translateStatusTerms();
    return empty(self::$statusTerms[$status]) ? lang::get('Unknown') : self::$statusTerms[$status];
  }

  /**
   * Ajax method allowing the details pane to show the media tab content.
   * @param integer $website_id
   * @param string $password
   * @throws \exception
   */
  public static function ajax_media($website_id, $password) {
    iform_load_helpers(array('report_helper'));
    $readAuth = report_helper::get_read_auth($website_id, $password);
    echo self::get_media($readAuth);
  }

  private static function get_media($readAuth) {
    iform_load_helpers(array('data_entry_helper'));
    $media = data_entry_helper::get_population_data(array(
      'table' => 'sample_medium',
      'extraParams'=>$readAuth + array('sample_id'=>$_GET['sample_id']),
      'nocache'=>true,
      'sharing'=>'verification'
    ));
    $r = '';
    if (count($media)===0)
      $r .= lang::get('No media found for this record');
    else {
      $path = data_entry_helper::get_uploaded_image_folder();
      $r .= '<ul class="gallery">';
      foreach ($media as $file) {
        if (preg_match('/^http(s)?:\/\/(www\.)?([a-z]+)/', $file['path'], $matches)) {
          $media = "<a href=\"$file[path]\" class=\"social-icon $matches[3]\"></a>";
        } elseif (preg_match('/.(wav|mp3)$/', $file['path'])) {
          $media = "<audio controls src=\"$path$file[path]\" type=\"audio/mpeg\"/>";
        } else {
          $media = "<a href=\"$path$file[path]\" class=\"fancybox\"><img src=\"{$path}thumb-" .
            "$file[path]\"/><br/>$file[caption]</a>";
        }
        $r .= "<li>$media</li>";
      }
      $r .= '</ul>';
      $r .= '<p>'.lang::get('Click on thumbnails to view full size').'</p>';
    }
    return $r;
  }

  public static function ajax_comments($website_id, $password) {
    iform_load_helpers(array('report_helper'));
    $readAuth = report_helper::get_read_auth($website_id, $password);
    echo self::get_comments($readAuth);
  }

  private static function status_icons($status, $imgPath) {
    $r = '';
    if (!empty($status)) {
      $hint = self::status_label($status);
      $image = false;
      if ($status==='V') {
        $image = 'ok-16px';
      }
      elseif ($status==='R') {
        $image = 'cancel-16px';
      }
      if ($image) {
        $r = "<img width=\"12\" height=\"12\" src=\"{$imgPath}nuvola/$image.png\" title=\"$hint\" alt=\"$hint\"/>";
      }
    }
    return $r;
  }

  private static function get_comments($readAuth, $includeAddNew = true) {
    iform_load_helpers(array('data_entry_helper'));
    $comments = data_entry_helper::get_population_data(array(
      'table' => 'sample_comment',
      'extraParams' => $readAuth + array('sample_id'=>$_GET['sample_id'], 'sortdir'=>'DESC', 'orderby'=>'updated_on'),
      'nocache'=>true,
      'sharing'=>'verification'
    ));
    $imgPath = empty(data_entry_helper::$images_path) ? data_entry_helper::relative_client_helper_path()."../media/images/" : data_entry_helper::$images_path;
    $r = '';
    if (count($comments)===0)
      $r .= '<p id="no-comments">'.lang::get('No comments have been made.').'</p>';
    $r .= '<div id="comment-list">';
    foreach($comments as $comment) {
      $r .= '<div class="comment">';
      $r .= '<div class="header">';
      $r .= self::status_icons($comment['record_status'], $imgPath);
      if ($comment['query']==='t') {
        $hint = lang::get('This is a query');
        $r .= "<img width=\"12\" height=\"12\" src=\"{$imgPath}nuvola/dubious-16px.png\" title=\"$hint\" alt=\"$hint\"/>";
      }
      $r .= '<strong>'.(empty($comment['person_name']) ? $comment['username'] : $comment['person_name']).'</strong> ';
      $commentTime = strtotime($comment['updated_on']);
      // Output the comment time. Skip if in future (i.e. server/client date settings don't match)
      if ($commentTime < time())
        $r .= self::ago($commentTime);
      $r .= '</div>';
      $c = str_replace("\n", '<br/>', $comment['comment']);
      $r .= "<div>$c</div>";
      $r .= '</div>';
    }
    $r .= '</div>';
    if ($includeAddNew) {
      $r .= '<form><fieldset><legend>'.lang::get('Add new comment').'</legend>';
      $r .= '<textarea id="comment-text"></textarea><br/>';
      $r .= '<button type="button" class="default-button" onclick="saveComment(jQuery(\'#comment-text\').val());">'.lang::get('Save').'</button>';
      $r .= '</fieldset></form>';
    }
    return $r;
  }

  public static function ajax_mediaAndComments($website_id, $password) {
    iform_load_helpers(array('report_helper'));
    $readAuth = report_helper::get_read_auth($website_id, $password);
    header('Content-type: application/json');
    echo json_encode(array(
      'media' => self::get_media($readAuth),
      'comments' => self::get_comments($readAuth, false)
    ));
  }

  /**
   * Ajax method to send an email. Takes the subject and body in the $_GET parameters.
   * @return boolean True if the email was sent.
   */
  public static function ajax_email() {
    global $user;
    $site_email = hostsite_get_config_value('site', 'mail', '');
    $headers = array();
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8;';
    $headers[] = 'From: '. $site_email;
    $headers[] = 'Reply-To: '. $user->mail;
    $headers[] = 'Return-Path: '. $site_email;
    $headers = implode("\r\n", $headers) . PHP_EOL;
    $emailBody = $_POST['body'];
    $emailBody = str_replace("\n", "<br/>", $emailBody);
    // Send email. Depends upon settings in php.ini being correct
    $success = mail($_POST['to'],
      $_POST['subject'],
      wordwrap($emailBody, 70),
      $headers);
    if ($success)
      echo 'OK';
    else
      echo 'Fail';
  }

  /**
   * Ajax handler to determine if a user is likely to see a notification added to their comments.
   * @param $website_id
   * @param $password
   * @param $nid
   * @return string Either yes, no, maybe or unknown.
   * @throws \Exception
   */
  public static function ajax_do_they_see_notifications($website_id, $password, $nid) {
    iform_load_helpers(array('report_helper'));
    $readAuth = report_helper::get_read_auth($website_id, $password);
    $data = report_helper::get_report_data(array(
      'dataSource' => 'library/users/user_notification_response_likely',
      'readAuth' => $readAuth,
      'extraParams' => array('user_id'=>$_GET['user_id'])
    ));
    $acknowledged = 0;
    $unacknowledged = 0;
    $emailFrequency = false;
    foreach ($data as $row) {
      if ($row['key']==='acknowledged')
        $acknowledged = (int)$row['value'];
      elseif ($row['key']==='unacknowledged')
        $unacknowledged = (int)$row['value'];
      elseif ($row['key']==='email_frequency')
        $emailFrequency = $row['value'];
    }
    if ($emailFrequency) {
      // If they receive emails for comment notifications, we can assume they will see a comment
      echo 'yes';
    }
    elseif ($acknowledged + $unacknowledged > 0) {
      // otherwise, we need some info on the ratio of acknowledged to unacknowledged notifications over the last year
      $ratio = $acknowledged/($acknowledged+$unacknowledged);
      if ($ratio>0.3)
        echo 'yes';
      elseif ($ratio===0)
        echo 'no';
      else
        echo 'maybe';
    } else {
      // They don't have notifications in database, so we can't say
      echo 'unknown';
    }
  }

  /**
   * Ajax method to proxy requests for bulk verification on to the warehouse, attaching write auth
   * as it goes.
   */
  public static function ajax_bulk_verify($website_id, $password) {
    iform_load_helpers(array('data_entry_helper'));
    $auth = data_entry_helper::get_read_write_auth($website_id, $password);
    $url = data_entry_helper::$base_url."index.php/services/data_utils/bulk_verify_samples";
    $params = array_merge($_POST, $auth['write_tokens']);
    $response = data_entry_helper::http_post($url, $params);
    echo $response['output'];
  }

  /**
   * Convert a timestamp into readable format (... ago) for use on a comment list.
   * @param timestamp $timestamp The date time to convert.
   * @return string The output string.
   */
  private static function ago($timestamp) {
    $difference = time() - $timestamp;
    // Having the full phrase means that it is fully localisable if the phrasing is different.
    $periods = array(
      lang::get("{1} second ago"),
      lang::get("{1} minute ago"),
      lang::get("{1} hour ago"),
      lang::get("Yesterday"),
      lang::get("{1} week ago"),
      lang::get("{1} month ago"),
      lang::get("{1} year ago"),
      lang::get("{1} decade ago"));
    $periodsPlural = array(
      lang::get("{1} seconds ago"),
      lang::get("{1} minutes ago"),
      lang::get("{1} hours ago"),
      lang::get("{1} days ago"),
      lang::get("{1} weeks ago"),
      lang::get("{1} months ago"),
      lang::get("{1} years ago"),
      lang::get("{1} decades ago"));
    $lengths = array("60","60","24","7","4.35","12","10");
    for($j = 0; (($difference >= $lengths[$j]) && ($j < 7)) ; $j++) {
      $difference /= $lengths[$j];
    }
    $difference = round($difference);
    if($difference == 1)
      $text = str_replace('{1}', $difference, $periods[$j]);
    else
      $text = str_replace('{1}', $difference, $periodsPlural[$j]);
    return $text;
  }

  private static function report_filter_panel($args, $readAuth) {
    $options = array(
      'allowSave' => true,
      'sharing' => 'verification',
      'linkToMapDiv'=>'map',
      'filter-quality'=>'P',
      'entity'=>'sample'
    );
    if (!empty($args['indexed_location_type_ids']))
      $options['indexedLocationTypeIds'] = array_map('intval', explode(',', $args['indexed_location_type_ids']));
    if (!empty($args['other_location_type_ids']))
      $options['otherLocationTypeIds'] = array_map('intval', explode(',', $args['other_location_type_ids']));
    $r = report_filter_panel($readAuth, $options, $args['website_id'], $hiddenStuff);
    return $r . $hiddenStuff;
  }

}