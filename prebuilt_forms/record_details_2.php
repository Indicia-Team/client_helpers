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
 * Displays the details of a single record. Takes an occurrence_id in the URL and displays the following using a configurable
 * page template:
 * Record Details including custom attributes
 * A map including geometry
 * Any photos associated with the occurrence
 * Any comments associated with the occurrence including the ability to add comments
 */


require_once 'includes/dynamic.php';
require_once 'includes/report.php';
require_once 'includes/groups.php';


class iform_record_details_2 extends iform_dynamic {

  protected static $record;

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_record_details_2_definition() {
    return array(
      'title' => 'View details of a record 2',
      'category' => 'Utilities',
      'description' => 'A summary view of a record with commenting capability. Pass a parameter in the URL called ' .
        'occurrence_id to define which occurrence to show.',
      'supportsGroups' => TRUE,
      'recommended' => TRUE,
    );
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
      array(
        array(
          'name' => 'interface',
          'caption' => 'Interface Style Option',
          'description' => 'Choose the style of user interface, either dividing the form up onto separate tabs, ' .
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
        // List of fields to hide in the Record Details section.
        array(
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
Sample ID
Record ID',
          'group' => 'Fields for record details'
        ),
        array(
          'name' => 'operator',
          'caption' => 'Include or exclude',
          'description' => "Do you want to include only the list of fields you've defined, or exclude them?",
          'type' => 'select',
          'options' => array(
            'in' => 'Include',
            'not in' => 'Exclude'
          ),
          'default' => 'not in',
          'group' => 'Fields for record details'
        ),
        array(
          'name' => 'testagainst',
          'caption' => 'Test attributes against',
          'description' => 'For custom attributes, do you want to filter the list to show using the caption or the system function? If the latter, then ' .
              'any custom attributes referred to in the fields list above should be referred to by their system function which might be one of: email, ' .
              'cms_user_id, cms_username, first_name, last_name, full_name, biotope, sex_stage, sex, stage, sex_stage_count, certainty, det_first_name, det_last_name.',
          'type' => 'select',
          'options' => array(
            'caption' => 'Caption',
            'system_function' => 'System Function'
          ),
          'default' => 'caption',
          'group' => 'Fields for record details'
        ),
        // Allows the user to define how the page will be displayed.
        array(
        'name' => 'structure',
          'caption' => 'Form Structure',
          'description' => 'Define the structure of the form. Each component must be placed on a new line. <br/>' .
            "The following types of component can be specified. <br/>" .
            "<strong>[control name]</strong> indicates a predefined control is to be added to the form with the following predefined controls available: <br/>" .
                "&nbsp;&nbsp;<strong>[recorddetails]</strong> - displays information relating to the occurrence and its sample<br/>" .
                "&nbsp;&nbsp;<strong>[buttons]</strong> - outputs a row of edit and explore buttons. Use the @buttons option to change the list of buttons to output ".
                "by setting this to an array, e.g. [edit] will output just the edit button, [explore] outputs just the explore button, [species details] outputs a species details button. ".
                "The edit button is automatically skipped if the user does not have rights to edit the record.<br/>" .
                "&nbsp;&nbsp;<strong>[comments]</strong> - lists any comments associated with the occurrence. Also includes the ability to add a comment<br/>" .
                "&nbsp;&nbsp;<strong>[photos]</strong> - photos associated with the occurrence<br/>" .
                "&nbsp;&nbsp;<strong>[map]</strong> - a map that links to the spatial reference and location<br/>" .
                "&nbsp;&nbsp;<strong>[previous determinations]</strong> - a list of previous determinations for this record<br/>" .
                 "&nbsp;&nbsp;<strong>[occurrence associations]</strong> - a list of associated occurrence information (recorded interactions)<br/>" .
            "<strong>=tab/page name=</strong> is used to specify the name of a tab or wizard page (alpha-numeric characters only). " .
            "If the page interface type is set to one page, then each tab/page name is displayed as a seperate section on the page. " .
            "Note that in one page mode, the tab/page names are not displayed on the screen.<br/>" .
            "<strong>|</strong> is used to split a tab/page/section into two columns, place a [control name] on the previous line and following line to split.<br/>",
          'type' => 'textarea',
          'default' =>
'=Record Details and Comments=
[recorddetails]
[buttons]
|
[previous determinations]
[comments]
=Map and Photos=
[map]
|
[photos]',
          'group' => 'User Interface',
        ),
        array(
          'name' => 'default_input_form',
          'caption' => 'Default input form path',
          'description' => 'Default path to use to the edit form for old records which did not have their input form recorded in the database. Specify the ' .
              'path to a general purpose list entry form.',
          'type' => 'text_input',
          'group' => 'Path configuration',
        ),
        array(
          'name' => 'explore_url',
          'caption' => 'Explore URL',
          'description' => 'When you click on the Explore this species\' records button you are taken to this URL. Use {rootfolder} as a replacement ' .
              'token for the site\'s root URL.',
          'type' => 'string',
          'required' => FALSE,
          'default' => '',
          'group' => 'Path configuration',
        ),
        array(
          'name' => 'species_details_url',
          'caption' => 'Species details URL',
          'description' => 'When you click on the ... species page button you are taken to this URL with taxon_meaning_id as a parameter. Use {rootfolder} as a replacement ' .
              'token for the site\'s root URL.',
          'type' => 'string',
          'required' => FALSE,
          'default' => '',
          'group' => 'Path configuration',
        ),
        array(
          'name' => 'explore_param_name',
          'caption' => 'Explore Parameter Name',
          'description' => 'Name of the parameter added to the Explore URL to pass through the taxon_meaning_id of the species being explored. ' .
            'The default provided (filter-taxon_meaning_list) is correct if your report uses the standard parameters configuration.',
          'type' => 'string',
          'required' => FALSE,
          'default' => '',
          'group' => 'Path configuration',
        ),
        array(
          'name' => 'map_geom_precision',
          'caption' => 'Map geometry precision',
          'description' => 'If you want to output a lower precision map geometry than was actually recorded, ' .
              'select the precision here',
          'type' => 'select',
          'options' => array('1' => '1km', '2' => '2km', '10' => '10km'),
          'required' => FALSE,
          'group' => 'Other Map Settings',
        ),
        array(
          'name' => 'allow_confidential',
          'caption' => 'Allow viewing of confidential records',
          'description' => 'Tick this box to enable viewing of confidential records. Ensure that the page is only ' .
            'available to logged in users with appropropriate permissions if using this option',
          'type' => 'checkbox',
          'required' => FALSE,
        ),
        array(
          'name' => 'allow_sensitive_full_precision',
          'caption' => 'Allow viewing of sensitive records at full precision',
          'description' => 'Tick this box to enable viewing of sensitive records at full precision records. Ensure ' .
            'that the page is only available to logged in users with appropropriate permissions if using this option',
          'type' => 'checkbox',
          'required' => FALSE,
        ),
        array(
          'name' => 'allow_unreleased',
          'caption' => 'Allow viewing of unreleased records',
          'description' => 'Tick this box to enable viewing of unreleased records. Ensure that the page is only ' .
            'available to logged in users with appropropriate permissions if using this option',
          'type' => 'checkbox',
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
        ),
      )
    );
    return $retVal;
  }

  public static function get_form($args, $nid) {
    if (empty($_GET['occurrence_id'])) {
      return 'This form requires an occurrence_id parameter in the URL.';
    }
    iform_load_helpers(array('report_helper'));
    if ($args['available_for_groups'] === '1') {
      if (empty($_GET['group_id'])) {
        return 'This page needs a group_id URL parameter.';
      }
      $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
      $isMember = group_authorise_form($args, $readAuth);
      // If groups support is enabled, then do a count report to check access.
      $argArray = [];
      group_apply_report_limits($argArray, $readAuth, $nid, $isMember);
      $accessCheck = report_helper::get_report_data([
        'readAuth' => $readAuth,
        'dataSource' => 'library/occurrences/filterable_explore_list',
        'extraParams' => get_options_array_with_user_data($argArray['param_presets']) + [
          'occurrence_id' => $_GET['occurrence_id'],
          'wantCount' => '1',
          'wantRecords' => 0,
          'confidential' => $args['allow_confidential'] ? 'all' : 'f',
          'release_status' => $args['allow_unreleased'] ? 'A' : 'R',
        ],
      ]);
      if ($accessCheck['count'] === 0) {
        // If the record has been redetermined out of this group, double check
        // as it can be shown still if public.
        $accessCheck = report_helper::get_report_data([
          'readAuth' => $readAuth,
          'dataSource' => 'library/occurrences/filterable_explore_list',
          'extraParams' => $readAuth + [
            'occurrence_id' => $_GET['occurrence_id'],
            'wantCount' => '1',
            'wantRecords' => 0,
          ],
        ]);
        if ($accessCheck['count'] === 0) {
          return 'You do not have permission to view this record.';
        }
      }
    }
    data_entry_helper::$javascript .= 'indiciaData.username = "' . hostsite_get_user_field('name') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.ajaxFormPostUrl="' . iform_ajaxproxy_url(NULL, 'occurrence') .
      "&sharing=$args[sharing]\";\n";
    return parent::get_form($args, $nid);
  }

  /**
   * Draw Record Details section of the page.
   *
   * @return string
   *   The output freeform report.
   */
  protected static function get_control_recorddetails($auth, $args, $tabalias, $options) {
    $options = array_merge(array(
      'dataSource' => 'reports_for_prebuilt_forms/record_details_2/record_data_attributes_with_hiddens'
    ), $options);
    $fields = helper_base::explode_lines($args['fields']);
    $fieldsLower = helper_base::explode_lines(strtolower($args['fields']));
    // Draw the Record Details, but only if they aren't requested as hidden by the administrator.
    $attrsTemplate = '<div class="field ui-helper-clearfix"><span>{caption}:</span>{anchorfrom}<span{class}>{value|escape}</span>{anchorto}</div>';
    $test = $args['operator'] === 'in';
    $availableFields = array(
      'occurrence_id' => lang::get('Record ID'),
      'occurrence_external_key' => lang::get('Record external key'),
      'preferred_taxon' => lang::get('Recommended name'),
      'common_name' => lang::get('Common name'),
      'taxon' => lang::get('Name as entered'),
      'taxonomy' => lang::get('Taxonomy'),
      'survey_title' => lang::get('Survey'),
      'recorder' => lang::get('Recorder'),
      'inputter' => lang::get('Input by'),
      'record_status' => lang::get('Record status'),
      'verifier' => lang::get('Verified by'),
      'date' => lang::get('Date'),
      'entered_sref' => lang::get('Grid ref'),
      'occurrence_comment' => lang::get('Record comment'),
      'location_name' => lang::get('Site name'),
      'sample_comment' => lang::get('Sample comment'),
      'licence_code' => lang::get('Licence')
    );
    self::load_record($auth, $args);
    if (!empty(self::$record['sensitivity_precision'] && !$args['allow_sensitive_full_precision'])) {
      unset($availableFields['recorder']);
      unset($availableFields['inputter']);
      unset($availableFields['entered_sref']);
      unset($availableFields['occurrence_comment']);
      unset($availableFields['location_name']);
      unset($availableFields['sample_comment']);
    }

    $flags = array();
    if (!empty(self::$record['sensitive'])) {
      $flags[] = lang::get('sensitive');
    }
    if (self::$record['confidential'] === 't') {
      $flags[] = lang::get('confidential');
    }
    if (self::$record['release_status'] !== 'R') {
      $flags[] = lang::get(self::$record['release_status'] === 'P' ? 'pending release' : 'unreleased');
    }
    if (!empty($flags)) {
      $details_report = '<div id="record-flags"><span>' . implode('</span><span>', $flags) . '</span></div>';
    } else {
      $details_report = '';
    }

    $details_report .= '<div class="record-details-fields ui-helper-clearfix">';
    $nameLabel = self::$record['taxon'];
    if (self::$record['taxon'] !== self::$record['preferred_taxon']) {
      $nameLabel .= ' (' . self::$record['preferred_taxon'] . ')';
    }
    $title = lang::get('Record of {1}', $nameLabel);
    hostsite_set_page_title($title);
    foreach ($availableFields as $field => $caption) {
      if ($test === in_array(strtolower($caption), $fieldsLower) && !empty(self::$record[$field])) {
        $class = self::getFieldClass($field);
        $caption = self::$record[$field] === 'This record is sensitive' ? '' : $caption;
        $anchorfrom = $field === 'licence_code' ? '<a href="' . self::$record['licence_url'] . '">' : '';
        $anchorto = $field === 'licence_code' ? '</a>' : '';
        $value = lang::get(self::$record[$field]);
        // Italices latin names.
        if (($field === 'taxon' && self::$record['language_iso'] === 'lat')
            || ($field === 'preferred_taxon' && self::$record['preferred_language_iso'] === 'lat')) {
          $value = "<em>$value</em>";
        }
        if ($field === 'preferred_taxon' && !empty(self::$record['preferred_authority'])) {
          $value = "$value " . self::$record['preferred_authority'];
        }
        if ($field !== 'taxon' && $field !== 'preferred_taxon') {
          $value = htmlspecialchars($value);
        }
        $details_report .= str_replace(
          array('{caption}', '{value|escape}', '{class}', '{anchorfrom}', '{anchorto}'),
          array(lang::get($caption), $value, $class, $anchorfrom, $anchorto),
          $attrsTemplate
        );
      }
    }
    $created = date('jS F Y \a\t H:i', strtotime(self::$record['created_on']));
    $updated = date('jS F Y \a\t H:i', strtotime(self::$record['updated_on']));
    $dateInfo = lang::get('Entered on {1}', $created);
    if ($created !== $updated) {
      $dateInfo .= lang::get(' and last updated on {1}', $updated);
    }
    if ($test === in_array('submission date', $fieldsLower)) {
      $details_report .= str_replace(array('{caption}', '{value|escape}', '{class}', '{anchorfrom}', '{anchorto}'),
          array(lang::get('Submission date'), $dateInfo, '', '', ''), $attrsTemplate);
    }
    $details_report .= '</div>';

    if (!self::$record['sensitivity_precision'] || $args['allow_sensitive_full_precision']) {
      // Draw any custom attributes added by the user, but only for a non-sensitive record.
      $attrs_report = report_helper::freeform_report(array(
        'readAuth' => $auth['read'],
        'class' => 'record-details-fields ui-helper-clearfix',
        'dataSource' => $options['dataSource'],
        'bands' => array(array('content' => str_replace(array('{class}', '{anchorfrom}', '{anchorto}'), '', $attrsTemplate))),
        'extraParams' => array(
          'occurrence_id' => $_GET['occurrence_id'],
          // The SQL needs to take a set of the hidden fields, so this needs to be converted from an array.
          'attrs' => strtolower(self::convert_array_to_set($fields)),
          'testagainst' => $args['testagainst'],
          'operator' => $args['operator'],
          'sharing' => $args['sharing'],
          'language' => iform_lang_iso_639_2(hostsite_get_user_field('language')),
        ),
      ));
    }

    $r = '<div class="detail-panel" id="detail-panel-recorddetails"><h3>' . lang::get('Record Details') . '</h3>';

    $r .= $details_report;
    if (isset($attrs_report)) {
      $r .= $attrs_report;
    }
    $r .= '</div>';
    return $r;
  }

  /**
   * Used to convert an array of attributes to a string formatted like a set,
   * this is then used by the record_data_attributes_with_hiddens report to return
   * custom attributes which aren't in the hidden attributes list.
   *
   * @return string
   *   The set of hidden custom attributes.
   */
  protected static function convert_array_to_set($theArray) {
    return "'" . implode("','", str_replace("'", "''", $theArray)) . "'";
  }

  /**
   * Draw Photos section of the page.
   *
   * @return string
   *   The output report grid.
   */
  protected static function get_control_photos($auth, $args, $tabalias, $options) {
    iform_load_helpers(array('data_entry_helper'));
    $options = array_merge(array(
      'title' => lang::get('Photos and media'),
    ), $options);
    $settings = array(
      'table' => 'occurrence_image',
      'key' => 'occurrence_id',
      'value' => $_GET['occurrence_id'],
    );
    return self::commonControlPhotos($auth, $args, $options, $settings);
  }

  /**
   * Render Sample Photos section of the page.
   *
   * @return string
   *   The output report grid.
   */
  protected static function get_control_samplephotos($auth, $args, $tabalias, $options) {
    iform_load_helpers(array('data_entry_helper'));
    $options = array_merge(array(
      'title' => lang::get('Sample photos and media'),
    ), $options);
    $occurrence = data_entry_helper::get_population_data(array(
      'table' => 'occurrence',
      'extraParams' => $auth['read'] + array('id' => $_GET['occurrence_id'], 'view' => 'detail'),
    ));
    $settings = array(
      'table' => 'sample_image',
      'key' => 'sample_id',
      'value' => $occurrence[0]['sample_id'],
    );
    return self::commonControlPhotos($auth, $args, $options, $settings);
  }

  /**
   * Draw Parent Samples Photos section of the page.
   *
   * @return string
   *   The output report grid.
   */
  protected static function get_control_parentsamplephotos($auth, $args, $tabalias, $options) {
    iform_load_helpers(array('data_entry_helper'));
    $options = array_merge(array(
      'title' => lang::get('Parent sample photos and media')
    ), $options);
    $occurrence = data_entry_helper::get_population_data(array(
      'table' => 'occurrence',
      'extraParams' => $auth['read'] + array('id' => $_GET['occurrence_id'], 'view' => 'detail'),
    ));
    $sample = data_entry_helper::get_population_data(array(
      'table' => 'sample',
      'extraParams' => $auth['read'] + array('id' => $occurrence[0]['sample_id'], 'view' => 'detail'),
    ));
    $settings = array(
        'table' => 'sample_image',
        'key' => 'sample_id',
        'value' => $sample[0]['parent_id'],
    );
    return self::commonControlPhotos($auth, $args, $options, $settings);
  }

  /**
   * Returns the class attribute HTML to apply to attribute list data in the details pane.
   * @param string $field Field name
   * @return string
   */
  private static function getFieldClass($field) {
    if (self::$record[$field] === 'This record is sensitive') {
      $class = ' class="ui-state-error"';
    }
    elseif ($field === 'licence_code') {
      $class = ' class="licence licence-' . strtolower(self::$record['licence_code']) . '"';
    }
    else {
      $class = '';
    }
    return $class;
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
    $extraParams = $auth['read'] + array(
      'sharing' => $args['sharing'],
      'limit' => $options['itemsPerPage'],
    );
    $extraParams[$settings['key']] = $settings['value'];
    $media = data_entry_helper::get_population_data(array(
      'table' => $settings['table'],
      'extraParams' => $extraParams,
    ));
    $r = <<<HTML
<div class="detail-panel" id="detail-panel-photos">
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
      foreach ($media as $idx => $medium) {
        // iNat only uses a thumb or full size image. So force thumb for
        // preview.
        $imageSize = $medium['media_type'] === 'Image:iNaturalist' ? 'thumb' : $options['imageSize'];
        if ($firstImage && substr($medium['media_type'], 0, 6) === 'Image:') {
          // First image can be flagged as the main content image. Used for FB OpenGraph for example.
          global $iform_page_metadata;
          if (!isset($iform_page_metadata)) {
            $iform_page_metadata = array();
          }
          $imageFolder = data_entry_helper::get_uploaded_image_folder();
          $iform_page_metadata['image'] = "$imageFolder$medium[path]";
          $firstImage = FALSE;
        }
        $r .= iform_report_get_gallery_item($medium, $imageSize);
      }
      $r .= '</ul>';
    }
    $r .= '</div></div>';
    return $r;
  }

  /**
   * Render the Map section of the page.
   *
   * @return string
   *   The output map panel.
   */
  protected static function get_control_map($auth, $args, $tabalias, $options) {
    iform_load_helpers(array('map_helper'));
    self::load_record($auth, $args);
    $options = array_merge(
      iform_map_get_map_options($args, $auth['read']),
      array('maxZoom' => 14, 'maxZoomBuffer' => 4),
      $options
    );
    if (isset(self::$record['geom'])) {
      $options['initialFeatureWkt'] = self::$record['geom'];
    }
    if (!empty(self::$record['sref_precision'])) {
      // Set radius if imprecise.
      $p = self::$record['sref_precision'];
      map_helper::$javascript .= <<<JS
indiciaData.srefPrecision = $p;

JS;
    }

    if ($tabalias) {
      $options['tabDiv'] = $tabalias;
    }
    $olOptions = iform_map_get_ol_options($args);

    if (!isset($options['standardControls'])) {
      $options['standardControls'] = array('layerSwitcher', 'panZoom');
    }
    return '<div class="detail-panel" id="detail-panel-map"><h3>' . lang::get('Map') .'</h3>' . map_helper::map_panel($options, $olOptions) . '</div>';
  }

  /**
   * Draw the Comments section of the page.
   *
   * @return string
   *   The output HTML string.
   */
  protected static function get_control_comments($auth, $args, $tabalias, $options) {
    iform_load_helpers(array('data_entry_helper'));
    $r = '<div>';
    $params = [
      'occurrence_id' => $_GET['occurrence_id'],
      'sortdir' => 'DESC',
      'orderby' => 'updated_on',
    ];
    if (!$args['allow_confidential']) {
      $params['confidential'] = 'f';
    }
    $comments = data_entry_helper::get_population_data(array(
      'table' => 'occurrence_comment',
      'extraParams' => $auth['read'] + $params,
      'nocache' => TRUE,
      'sharing' => $args['sharing'],
    ));
    $r .= '<div id="comment-list">';
    if (count($comments) === 0) {
      $r .= '<p id="no-comments">' . lang::get('No comments have been made.') . '</p>';
    }
    else {
      foreach ($comments as $comment) {
        $r .= '<div class="comment">';
        $r .= '<div class="header">';
        $r .= "<strong>$comment[person_name]</strong> ";
        $commentTime = strtotime($comment['updated_on']);
        // Output the comment time. Skip if in future (i.e. server/client date settings don't match).
        if ($commentTime < time()) {
          $r .= self::ago($commentTime);
        }
        $r .= '</div>';
        $c = str_replace("\n", '<br/>', $comment['comment']);
        $r .= "<div>$c</div>";
        if (!empty($options['showCorrespondence']) && !empty($comment['correspondence_data'])) {
          $data = str_replace("\n", '<br/>', $comment['correspondence_data']);
          $correspondenceData = json_decode($data, TRUE);
          foreach ($correspondenceData as $type => $items) {
            $r .= '<div class="correspondance">' . ucfirst($type) . '<br/>';
            foreach ($items as $item) {
              foreach ($item as $field => $value) {
                $field = $field === 'body' ? '' : '<span>' . ucfirst($field) . ':</span>';
                $r .= "<div>$field $value</div>";
              }
              $r .= '</div></div>';
            }
          }
        }
        $r .= '</div>';
      }
    }
    $r .= '</div>';
    $r .= '<form><fieldset><legend>' . lang::get('Add new comment') . '</legend>';
    $r .= '<input type="hidden" id="comment-by" value="' . hostsite_get_user_field('name') . '"/>';
    $r .= '<textarea id="comment-text"></textarea><br/>';
    $r .= '<button type="button" class="default-button" onclick="indiciaFns.saveComment(';
    $r .= $_GET['occurrence_id'] . ');">' . lang::get('Save') . '</button>';
    $r .= '</fieldset></form>';
    $r .= '</div>';

    return '<div class="detail-panel" id="detail-panel-comments"><h3>' . lang::get('Comments') . '</h3>' . $r . '</div>';
  }

  /**
   * Displays a list of determinations associated with an occurrence record. This particular panel
   * is ommitted if there are no determinations.
   *
   * @return string
   *   The determinations report grid.
   */
  protected static function get_control_previousdeterminations($auth, $args, $tabalias, $options) {
    $options = array_merge(array(
      'report' => 'library/determinations/determinations_list'
    ));
    return report_helper::freeform_report(array(
      'readAuth' => $auth['read'],
      'dataSource' => $options['report'],
      'mode' => 'report',
      'autoParamsForm' => FALSE,
      'header' => '<div class="detail-panel" id="detail-panel-previousdeterminations"><h3>' . lang::get('Previous determinations') . '</h3>',
      'bands' => array(array('content' => '<div class="field ui-helper-clearfix">{taxon_html} by {person_name} on {date}</div>')),
      'footer' => '</div>',
      'extraParams' => array(
        'occurrence_id' => $_GET['occurrence_id'],
        'sharing' => $args['sharing'],
      ),
    ));
  }

  /**
   * Outputs a list of associated occurrence information (recorded interactions).
   *
   * @param $auth
   * @param $args
   * @param $tabalias
   * @param $options
   *
   * @return string
   *
   * @throws \exception
   */
  protected static function get_control_occurrenceassociations($auth, $args, $tabalias, $options) {
    $options = array_merge(array(
      'dataSource' => 'library/occurrence_associations/filterable_explore_list',
      'itemsPerPage' => 100,
      'header' => '<ul>',
      'footer' => '</ul>',
      'bands' => array(array('content' => '<li>{association_detail}</li>')),
      'emptyText' => '<p>No association information available</p>'
    ), $options);
    return '<div class="detail-panel" id="detail-panel-occurrenceassociations"><h3>' . lang::get('Associations') . '</h3>' .
    report_helper::freeform_report(array(
      'readAuth' => $auth['read'],
      'dataSource' => $options['dataSource'],
      'itemsPerPage' => $options['itemsPerPage'],
      'header' => $options['header'],
      'footer' => $options['footer'],
      'bands' => $options['bands'],
      'emptyText' => $options['emptyText'],
      'mode' => 'report',
      'autoParamsForm' => FALSE,
      'extraParams' => array(
        'occurrence_id' => $_GET['occurrence_id'],
        'sharing' => $args['sharing'],
      )
    )) . '</div>';
  }

  /**
   * Outputs a login form. Not displayed if already logged in.
   *
   * @param array $auth
   * @param array $args
   * @param string $tabalias
   * @param array $options
   *   Options array passed in the configuration to the [login] control. Possible values include instruct - the
   *   instruction to display above the login form.
   *
   * @return string
   */
  protected static function get_control_login($auth, $args, $tabalias, $options) {
    $options = array_merge(array(
      'instruct' => 'Please log in or <a href="user/register">register</a> to see more details of this record.'
    ), $options);
    if (hostsite_get_user_field('id') === 0) {
      $form_state = array('noredirect' => TRUE);
      $form = drupal_build_form('user_login', $form_state);
      return '<div class="detail-panel" id="detail-panel-login">' .
          '<h3>' . lang::get('Login') . '</h3>' .
          '<p>' . lang::get($options['instruct']) . '</p>' .
          drupal_render($form) .
          '</div>';
    }
    else {
      return '';
    }
  }

  protected static function get_control_block($auth, $args, $tabalias, $options) {
    if (!empty($options['accepted'])) {
      self::load_record($auth, $args);
      if (!preg_match('/^Accepted/', self::$record['record_status'])) {
        return '';
      }
    }
    if ($options['module'] === 'addtoany') {
      self::load_record($auth, $args);
      // lets not promote sharing of sensitive stuff
      if (self::$record['sensitivity_precision']) {
        return '';
      }
      report_helper::$javascript .= "$('.a2a_kit').attr('data-a2a-url', window.location.href);\n";
      $title = str_replace("'", "\'", 'Check out this record of ' . self::$record['taxon']);
      report_helper::$javascript .= "$('.a2a_kit').attr('data-a2a-title', '$title');\n";
    }
    $r = '';
    if (!empty($options['title'])) {
      $r .= "<fieldset><legend>$options[title]</legend>";
    }
    if (function_exists('module_invoke')) {
      $block = module_invoke($options['module'], $options['hook'], $options['args']);
      $r .= render($block['content']);
    }
    else {
      $block_manager = \Drupal::service('plugin.manager.block');
      $plugin_block = $block_manager->createInstance($options['block'], $options['args']);
      $render = $plugin_block->build();
      $r .= $render[$options['rendered_item_key']];
    }
    if (!empty($options['title'])) {
      $r .= "</fieldset>";
    }
    return $r;
  }

  /**
   * An edit button control which only displays if the user owns the record.
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
   *   HTML for the buttons.
   */
  protected static function get_control_buttons($auth, $args, $tabalias, $options) {
    $options = array_merge(array(
      'buttons' => array(
        'edit',
        'explore',
        'species details',
      ),
    ));
    $r = '';
    foreach ($options['buttons'] as $button) {
      if ($button === 'edit') {
        $r .= self::buttons_edit($auth, $args, $tabalias, $options);
      }
      elseif ($button === 'explore') {
        $r .= self::buttons_explore($auth, $args, $tabalias, $options);
      }
      elseif ($button === 'species details') {
        $r .= self::buttons_species_details($auth, $args, $tabalias, $options);
      }
      else {
        throw new exception("Unknown button $button");
      }
    }
    return $r;
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
    self::load_record($auth, $args);
    $record = self::$record;
    if (($user_id = hostsite_get_user_field('indicia_user_id')) && $user_id == self::$record['created_by_id']
        && $args['website_id'] == self::$record['website_id']) {
      if (empty($record['input_form'])) {
        $record['input_form'] = $args['default_input_form'];
      }
      $rootFolder = data_entry_helper::getRootFolder(TRUE);
      $paramJoin = strpos($rootFolder, '?') === FALSE ? '?' : '&';
      $url = "$rootFolder$record[input_form]{$paramJoin}occurrence_id=$record[occurrence_id]";
      return "<a class=\"$indicia_templates[buttonDefaultClass]\" href=\"$url\">" . lang::get('Edit this record') . '</a>';
    }
    else {
      // No rights to edit, so button omitted.
      return '';
    }
  }

  /**
   * Retrieve the HTML required for an explore records of the same species button.
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
  protected static function buttons_explore($auth, $args, $tabalias, $options) {
    global $indicia_templates;
    if (!empty($args['explore_url']) && !empty($args['explore_param_name'])) {
      $url = $args['explore_url'];
      if (strcasecmp(substr($url, 0, 12), '{rootfolder}') !== 0 && strcasecmp(substr($url, 0, 4), 'http') !== 0) {
        $url = '{rootFolder}' . $url;
      }
      $rootFolder = data_entry_helper::getRootFolder(TRUE);
      $url = str_replace('{rootFolder}', $rootFolder, $url);
      $url .= (strpos($url, '?') === FALSE) ? '?' : '&';
      $url .= $args['explore_param_name'] . '=' . self::$record['taxon_meaning_id'];
      $taxon = empty(self::$record['preferred_taxon']) ? self::$record['taxon'] : self::$record['preferred_taxon'];
      $taxon = str_replace(' - zero abundance found', '', $taxon);
      $r = "<a class=\"$indicia_templates[buttonDefaultClass]\" href=\"$url\">" . lang::get('Explore records of {1}', $taxon) . '</a>';
    }
    else {
      throw new exception('The page has been setup to use an explore records button, but an "Explore URL" or ' .
          '"Explore Parameter Name" has not been specified.');
    }
    return $r;
  }

  /**
   * Retrieve the HTML required for an details of the same species button.
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
  protected static function buttons_species_details($auth, $args, $tabalias, $options) {
    global $indicia_templates;
    if (!empty($args['species_details_url'])) {
      $url = $args['species_details_url'];
      if (strcasecmp(substr($url, 0, 12), '{rootfolder}') !== 0 && strcasecmp(substr($url, 0, 4), 'http') !== 0) {
        $url = '{rootFolder}' . $url;
      }
      $rootFolder = data_entry_helper::getRootFolder(TRUE);
      $url = str_replace('{rootFolder}', $rootFolder, $url);
      $url .= (strpos($url, '?') === FALSE) ? '?' : '&';
      $url .= 'taxon_meaning_id=' . self::$record['taxon_meaning_id'];
      $taxon = empty(self::$record['preferred_taxon']) ? self::$record['taxon'] : self::$record['preferred_taxon'];
      $taxon = str_replace(' - zero abundance found', '', $taxon);
      return "<a class=\"$indicia_templates[buttonDefaultClass]\" href=\"$url\">" . lang::get('{1} details page', $taxon) . '</a>';
    }
    return '';
  }

  /**
   * Convert a timestamp into readable format (... ago) for use on a comment list.
   *
   * @param timestamp $timestamp
   *   The date time to convert.
   *
   * @return string
   *   The output string.
   */
  protected static function ago($timestamp) {
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
      lang::get("{1} decade ago"),
    );
    $periodsPlural = array(
      lang::get("{1} seconds ago"),
      lang::get("{1} minutes ago"),
      lang::get("{1} hours ago"),
      lang::get("{1} days ago"),
      lang::get("{1} weeks ago"),
      lang::get("{1} months ago"),
      lang::get("{1} years ago"),
      lang::get("{1} decades ago"),
    );
    $lengths = array("60", "60", "24", "7", "4.35", "12", "10");

    for ($j = 0; $difference >= $lengths[$j]; $j++) {
      $difference /= $lengths[$j];
    }
    $difference = round($difference);
    if ($difference == 1) {
      $text = str_replace('{1}', $difference, $periods[$j]);
    }
    else {
      $text = str_replace('{1}', $difference, $periodsPlural[$j]);
    }
    return $text;
  }

  /**
   * Sets default values for arguments.
   *
   * When a form version is upgraded introducing new parameters, old forms will not get the defaults for the
   * parameters unless the Edit and Save button is clicked. So, apply some defaults to keep those old forms
   * working.
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
[recorddetails]
|
[comments]
=Map and Photos=
[map]
|
[photos]
STRUCT;
    $args = array_merge(array(
      'interface' => 'one_page',
      'allow_confidential' => FALSE,
      'allow_sensitive_full_precision' => FALSE,
      'allow_unreleased' => FALSE,
      'hide_fields' => $defaultHiddenFields,
      'structure' => $defaultStructure,
      'sharing' => 'reporting',
    ), $args);
    return $args;
  }

  /**
   * Disable save buttons for this form class. Not a data entry form...
   *
   * @return boolean
   */
  protected static function include_save_buttons() {
    return FALSE;
  }

  /**
   * Override the standard header as this is not an HTML form.
   */
  protected static function getHeader($args) {
    return '';
  }

  /**
   * Override the standard footer as this is not an HTML form.
   */
  protected static function getFooter($args) {
    return '';
  }

  /**
   * Loads the record associated with the page if not already loaded.
   */
  protected static function load_record($auth, $args) {
    if (!isset(self::$record)) {
      $params = array(
        'occurrence_id' => $_GET['occurrence_id'],
        'sharing' => $args['sharing'],
        'allow_confidential' => $args['allow_confidential'] ? 1 : 0,
        'allow_sensitive_full_precision' => $args['allow_sensitive_full_precision'] ? 1 : 0,
        'allow_unreleased' => $args['allow_unreleased'] ? 1 : 0,
      );
      if (!empty($args['map_geom_precision'])) {
        $params['geom_precision'] = $args['map_geom_precision'];
      }
      $records = report_helper::get_report_data(array(
        'readAuth' => $auth['read'],
        'dataSource' => 'reports_for_prebuilt_forms/record_details_2/record_data',
        'extraParams' => $params,
      ));
      if (!count($records)) {
        hostsite_show_message(lang::get('The record cannot be found.', 'warning'));
        throw new exception('');
      }
      self::$record = $records[0];
      if (!$args['allow_confidential'] &&
        isset(self::$record['confidential']) && self::$record['confidential'] === 't') {
        hostsite_show_message(lang::get('This record is confidential so cannot be displayed', 'warning'));
        throw new exception('');
      }
      // Set the page metadata.
      global $iform_page_metadata;
      if (!isset($iform_page_metadata)) {
        $iform_page_metadata = array();
      }
      $species = self::$record['taxon'];
      if (!empty(self::$record['preferred_taxon']) && $species !== self::$record['preferred_taxon']) {
        $species .= ' (' . self::$record['preferred_taxon'] . ')';
      }
      $iform_page_metadata['title'] = lang::get('Record of {1}', $species);
      $iform_page_metadata['description'] = lang::get('Record of {1} on {2}', $species, self::$record['date']);
      if (!empty(self::$record['sample_comment'])) {
        $iform_page_metadata['description'] .= '. ' . trim(self::$record['sample_comment'], '. \t\n\r\0\x0B') . '.';
      }
      if (!empty(self::$record['occurrence_comment'])) {
        $iform_page_metadata['description'] .= ' ' . trim(self::$record['occurrence_comment'], '. \t\n\r\0\x0B') . '.';
      }
      if (empty(self::$record['sensitivity_precision']) || $args['allow_sensitive_full_precision']) {
        $iform_page_metadata['latitude'] = number_format((float) self::$record['lat'], 5, '.', '');
        $iform_page_metadata['longitude'] = number_format((float) self::$record['long'], 5, '.', '');
      }
    }
  }

  /**
   * Override some default behaviour in dynamic.
   */
  protected static function getFirstTabAdditionalContent($args, $auth, &$attributes) {
    return '';
  }

}
