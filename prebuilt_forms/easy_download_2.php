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

require_once ('includes/report_filters.php');

/**
 * A quick and easy way to download data you have access to.
 */
class iform_easy_download_2 implements PrebuiltFormInterface {

  /**
   * @var array List of sets of filters loaded from the db, one per sharing type code.
   */
  private static $filterSets=[];

  /**
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_easy_download_2_definition() {
    return [
      'title' => 'Easy download 2',
      'category' => 'Utilities',
      'description' => 'A page for quick and easy download of the data you have access to. Improved integration with record sharing and permissions.',
      'helpLink' => 'https://indicia-docs.readthedocs.org/en/latest/site-building/iform/prebuilt-forms/easy-download.html',
      'supportsGroups' => TRUE,
      'recommended' => TRUE
    ];
  }

  /**
   * {@inheritDoc}
   */
  public static function getPageType(): PageType {
    return PageType::Utility;
  }

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    return [
      [
        'name' => 'download_my_records',
        'caption' => 'Enable downloading of my records',
        'description' => 'Allow a user to download their own records.',
        'type' => 'checkbox',
        'required' => FALSE,
        'default' => TRUE
      ],
      [
        'name' => 'download_all_users_reporting',
        'caption' => 'Download all users for reporting permission',
        'description' => 'Provide the name of the permission required to allow download of all records for reporting (as opposed to just my records).',
        'type' => 'text_input',
        'required' => FALSE,
        'default' => 'indicia data admin'
      ],
      [
        'name' => 'download_administered_groups',
        'caption' => 'Download administered group records permission',
        'description' => 'Provide the name of the permission required to allow download of records contributed ' .
            'to recording groups you are an administrator of using a group recording form.',
        'type' => 'text_input',
        'required' => FALSE,
        'default' => 'indicia data admin'
      ],
      [
        'name' => 'download_member_groups',
        'caption' => 'Download any group records permission',
        'description' => 'Provide the name of the permission required to allow download of records contributed ' .
            'to recording groups you are a member of using a group recording form.',
        'type' => 'text_input',
        'required' => FALSE,
        'default' => ''
      ],
      [
        'name' => 'download_group_types',
        'caption' => 'Downloadable group type IDs',
        'description' => 'Comma separated list of the IDs of group types that can be downloaded. Leave blank to allow any.',
        'type' => 'text_input',
        'required' => FALSE
      ],
      [
        'name' => 'reporting_type_permission',
        'caption' => 'Download type permission - reporting',
        'description' => 'Provide the name of the permission required to allow download of reporting recordsets. '.
            'Leave blank to disallow this download type.',
        'type' => 'text_input',
        'required' => FALSE,
        'default' => 'access iform content'
      ],
      [
        'name' => 'peer_review_type_permission',
        'caption' => 'Download type permission - peer review',
        'description' => 'Provide the name of the permission required to allow download of peer review recordsets. '.
            'Leave blank to disallow this download type.',
        'type' => 'text_input',
        'required' => FALSE,
        'default' => 'access iform content'
      ],
      [
        'name' => 'verification_type_permission',
        'caption' => 'Download type permission - verification',
        'description' => 'Provide the name of the permission required to allow download of verification recordsets. '.
            'Leave blank to disallow this download type.',
        'type' => 'text_input',
        'required' => FALSE,
        'default' => 'verification'
      ],
      [
        'name' => 'data_flow_type_permission',
        'caption' => 'Download type permission - data flow',
        'description' => 'Provide the name of the permission required to allow download of data flow recordsets. '.
            'Leave blank to disallow this download type.',
        'type' => 'text_input',
        'required' => FALSE,
        'default' => 'indicia data admin'
      ],
      [
        'name' => 'moderation_type_permission',
        'caption' => 'Download type permission - moderation',
        'description' => 'Provide the name of the permission required to allow download of moderation recordsets. '.
            'Leave blank to disallow this download type.',
        'type' => 'text_input',
        'required' => FALSE,
        'default' => 'access iform content'
      ],
      [
        'name' => 'editing_type_permission',
        'caption' => 'Download type permission - editing',
        'description' => 'Provide the name of the permission required to allow download of editing recordsets. '.
            'Leave blank to disallow this download type.',
        'type' => 'text_input',
        'required' => FALSE,
        'default' => 'access iform content'
      ],
      [
        'name' => 'csv_format_permission',
        'caption' => 'Download format permission - CSV',
        'description' => 'Provide the name of the permission required to allow download of CSV format. '.
            'Leave blank to disallow this download format.',
        'type' => 'text_input',
        'required' => FALSE,
        'default' => 'access iform content'
      ],
      [
        'name' => 'tsv_format_permission',
        'caption' => 'Download format permission - TSV',
        'description' => 'Provide the name of the permission required to allow download of TSV format. '.
            'Leave blank to disallow this download format.',
        'type' => 'text_input',
        'required' => FALSE,
        'default' => 'access iform content'
      ],
      [
        'name' => 'kml_format_permission',
        'caption' => 'Download format permission - KML',
        'description' => 'Provide the name of the permission required to allow download of KML format. '.
            'Leave blank to disallow this download format.',
        'type' => 'text_input',
        'required' => FALSE,
        'default' => 'access iform content'
      ],
      [
        'name' => 'gpx_format_permission',
        'caption' => 'Download format permission - GPX',
        'description' => 'Provide the name of the permission required to allow download of GPX format. '.
            'Leave blank to disallow this download format.',
        'type' => 'text_input',
        'required' => FALSE,
        'default' => 'access iform content'
      ],
      [
        'name' => 'nbn_format_permission',
        'caption' => 'Download format permission - NBN',
        'description' => 'Provide the name of the permission required to allow download of NBN format. '.
            'Leave blank to disallow this download format.',
        'type' => 'text_input',
        'required' => FALSE,
        'default' => 'access iform content'
      ],
      [
          'name' => 'custom_formats',
          'caption' => 'Custom formats',
          'description' => 'Define a list of custom download formats.',
          'type' => 'jsonwidget',
          'schema' => '{
  "type":"seq",
  "title":"Formats List",
  "sequence":
  [
    {
      "type":"map",
      "title":"Format",
      "mapping": {
        "title": {"type":"str","desc":"The report format title (untranslated)."},
        "permission": {"type":"str","desc":"The CMS permission that the user must have in order for this report format to be available."},
        "report": {"type":"str","desc":"The path to the report which will be downloaded, e.g. \'library/occurrences/filterable_occurrences_list\'. Should be standard params enabled.."},
        "format": {"type":"str","desc":"The download format specifier, one of csv, tsv, xml, gpx, kml, nbn."},
        "params": {"type":"txt","desc":"Any key=value parameter pairs to pass to the report, one per line."}
      }
    }
  ]
}'
      ],
      [
        'name' => 'survey_id',
        'caption' => 'Survey for download',
        'description' => 'Select the survey to download data for, or leave blank to allow user selection.',
        'type' => 'select',
        'required' => FALSE,
        'table' => 'survey',
        'valueField' => 'id',
        'captionField' => 'title',
        'sharing' => 'data_flow',
        'blankText' => '<allow user to choose>',
        'siteSpecific' => TRUE,
      ],
      [
        'name' => 'report_csv',
        'caption' => 'CSV download format report',
        'description' => 'Choose the report used for CSV downloads. Report should be compatible with the standard report parameters.',
        'type' => 'report_helper::report_picker',
        'required' => TRUE,
        'default' => 'library/occurrences/filterable_occurrences_download',
      ],
      [
        'name' => 'report_params_csv',
        'caption' => 'CSV Additional parameters',
        'description' => 'Additional parameters to provide to the report when doing a CSV download. One per line, param=value format.',
        'type' => 'textarea',
        'required' => FALSE,
        'default'=>"smpattrs=\noccattrs=\n",
      ],
      [
        'name' => 'report_tsv',
        'caption' => 'TSV download format report',
        'description' => 'Choose the report used for TSV downloads. Report should be compatible with the standard report parameters.',
        'type' => 'report_helper::report_picker',
        'required' => TRUE,
        'default' => 'library/occurrences/filterable_occurrences_download',
      ],
      [
        'name' => 'report_params_tsv',
        'caption' => 'TSV Additional parameters',
        'description' => 'Additional parameters to provide to the report when doing a TSV download. One per line, param=value format.',
        'type' => 'textarea',
        'required' => FALSE,
        'default'=>"smpattrs=\noccattrs=\n",
      ],
      [
        'name' => 'report_kml',
        'caption' => 'KML download format report',
        'description' => 'Choose the report used for KML downloads. Report should be compatible with the standard report parameters and return a WKT for the geometry of the record '.
            'transformed to EPSG:4326.',
        'type' => 'report_helper::report_picker',
        'required' => TRUE,
        'default' => 'library/occurrences/filterable_occurrences_download_gis',
      ],
      [
        'name' => 'report_params_kml',
        'caption' => 'KML Additional parameters',
        'description' => 'Additional parameters to provide to the report when doing a KML download. One per line, param=value format.',
        'type' => 'textarea',
        'required' => FALSE,
        'default'=>"smpattrs=\noccattrs=\n",
      ],
      [
        'name' => 'report_gpx',
        'caption' => 'GPX download format report',
        'description' => 'Choose the report used for GPX downloads. Report should be compatible with the standard report parameters and return a WKT for the geometry of the record '.
            'transformed to EPSG:4326.',
        'type' => 'report_helper::report_picker',
        'required' => TRUE,
        'default' => 'library/occurrences/filterable_occurrences_download_gis',
      ],
      [
        'name' => 'report_params_gpx',
        'caption' => 'GPX Additional parameters',
        'description' => 'Additional parameters to provide to the report when doing a GPX download. One per line, param=value format.',
        'type' => 'textarea',
        'required' => FALSE,
        'default'=>"smpattrs=\noccattrs=\n",
      ],
      [
        'name' => 'report_nbn',
        'caption' => 'NBN download format report',
        'description' => 'Choose the report used for NBN downloads. Report should be compatible with the standard report parameters.',
        'type' => 'report_helper::report_picker',
        'required' => TRUE,
        'default' => 'library/occurrences/filterable_nbn_exchange',
      ],
      [
        'name' => 'report_params_nbn',
        'caption' => 'NBN Additional parameters',
        'description' => 'Additional parameters to provide to the report when doing an NBN download. One per line, param=value format.',
        'type' => 'textarea',
        'required' => FALSE,
        'default'=>"smpattrs=\noccattrs=\n",
      ],
      [
        'name' => 'limit',
        'caption' => 'Limit to number of records',
        'description' => 'For performance reasons, unlimited downloads are not recommended. Set this to control the number of records '.
            'that can be downloaded at one time, or set to 0 for no limit.',
        'type' => 'text_input',
        'required' => FALSE,
        'default'=>20000,
      ],
    ];
  }

  /**
   * Return the generated form output.
   *
   * @param array $args
   *   List of parameter values passed through to the form depending on how the
   *   form has been configured. This array always contains a value for
   *   language.
   * @param object $nid
   *   The Drupal node object's ID.
   * @param array $response
   *   When this form is reloading after saving a submission, contains the
   *   response from the service call. Note this does not apply when
   *   redirecting (in this case the details of the saved object are in the
   *   $_GET data).
   *
   * @return string
   *   Form HTML.
   */
  public static function get_form($args, $nid, $response=null) {
    $conn = iform_get_connection_details($nid);
    $args = array_merge([
      'download_my_records' => TRUE,
      'download_administered_groups' => 'indicia data admin',
      'download_member_groups' => '',
      'download_group_types' => ''
    ], $args);
    data_entry_helper::get_read_auth($conn['website_id'], $conn['password']);
    if (!empty($_POST) && !empty($_POST['format']))
      self::do_data_services_download($args, $nid);
    $types = self::get_download_types($args);
    $formats = self::get_download_formats($args);
    if (count($types)===0)
      return 'This download page is configured so that no download type options are available.';
    if (count($formats)===0)
      return 'This download page is configured so that no download format options are available.';
    $reload = data_entry_helper::get_reload_link_parts();
    $reloadPath = $reload['path'];
    if(count($reload['params'])) $reloadPath .= '?'.helper_base::array_to_query_string($reload['params']);
    $r = '<form method="POST" action="'.$reloadPath.'">';
    $r .= '<fieldset id="download-type-fieldset"><legend>'.lang::get('Records to download').'</legend>';
    if (count($types)===1) {
      $r .= '<input type="hidden" name="download-type" id="download-type" value="'.implode('', array_keys($types)).'"/>';
      hostsite_set_page_title(lang::get('Download {1}', strtolower(implode('', $types))));
    }
    else {
      $r .= data_entry_helper::select([
        'fieldname' => 'download-type',
        'label'=>lang::get('Download type'),
        'lookupValues'=>$types,
        'class' => 'control-width-5',
        'helpText'=>lang::get('Select the type of download you require, i.e. ' .
            'the purpose for the data. This defines which records are ' .
            'available to download.')
      ]);
    }
    $r .= data_entry_helper::select([
      'fieldname' => 'download-subfilter',
      'label'=>lang::get('Filter to apply'),
      'lookupValues'=>[],
      'class' => 'control-width-5',
      'helpText'=>lang::get('Optionally select from the available filters. ' .
          'Filters you create on the Explore pages will be available here.')
    ]);
    $r .= "</fieldset>\n";
    $r .= '<fieldset><legend>'.lang::get('Limit the records').'</legend>';
    if (empty($args['survey_id'])) {
      // put up an empty surveys drop down. AJAX will populate it.
      $r .= data_entry_helper::select([
        'fieldname' => 'survey_id',
        'label' => lang::get('Survey to include'),
        'helpText' => lang::get('Choose a survey, or &lt;all&gt; to not filter by survey.'),
        'lookupValues' => [],
        'class' => 'control-width-5'
      ]);
    }
    else {
      $r .= '<input type="hidden" name="survey_id" value="' . $args['survey_id'] . '"/>';
    }
    // Let the user pick the date range to download.
    $r .= data_entry_helper::select([
      'label'=>lang::get('Date field'),
      'fieldname' => 'date_type',
      'lookupValues' => [
        'recorded' => lang::get('Field record date'),
        'input' => lang::get('Input date'),
        'edited' => lang::get('Last changed date'),
        'verified' => lang::get('Verification status change date'),
      ],
      'helpText' => lang::get('If filtering on date, which date field would you ' .
          'like to filter on?'),
    ]);
    $r .= data_entry_helper::date_picker([
      'fieldname' => 'date_from',
      'label' => lang::get('Start Date'),
      'helpText' => lang::get('Leave blank for no start date filter'),
      'class' => 'control-width-4',
    ]);
    $r .= data_entry_helper::date_picker([
      'fieldname' => 'date_to',
      'label' => lang::get('End Date'),
      'helpText' => lang::get('Leave blank for no end date filter'),
      'class' => 'control-width-4',
    ]);
    $r .= '</fieldset>';
    if (!empty($args['custom_formats'])) {
      $customFormats = json_decode($args['custom_formats'], TRUE);
      foreach ($customFormats as $idx => $format) {
        if (empty($format['permission']) || hostsite_user_has_permission($format['permission']))
          $formats["custom-$idx"] = lang::get(isset($format['title']) ? $format['title'] : 'Untitled format');
      }
    }
    if (count($formats) > 1) {
      $r .= '<fieldset><legend>' . lang::get('Select a format to download') . '</legend>';
      $keys = array_keys($formats);
      $r .= data_entry_helper::radio_group([
        'fieldname' => 'format',
        'lookupValues' => $formats,
        'default' => $keys[0],
      ]);
      $r .= '</fieldset>';
    } else {
      // Only allowed 1 format, so no need for a selection control.
      $keys = array_keys($formats);
      $r .= '<input type="hidden" name="format" value="' . array_pop($keys) . '"/>';
    }
    $r .= '<input type="submit" value="' . lang::get('Download') . '"/></form>';
    data_entry_helper::$javascript .= 'indiciaData.ajaxUrl="' . hostsite_get_url('iform/ajax/easy_download_2') . "\";\n";
    data_entry_helper::$javascript .= "setAvailableDownloadFilters();\n";
    return $r;
  }

  /**
   * Works out the list of download type options available to the user.
   *
   * This is the list of sharing modes they have permission for, combined with
   * any filters defined for the user which define their permissions for that
   * sharing type.
   *
   * @param array $args
   *   Form parameters.
   *
   * @return array
   *   Associative array of download types.
   */
  private static function get_download_types($args) {
    $r = [];
    // We'll store any standard params filters that are user optional ones into
    // js data, so the UI can allow selection as appropriate.
    data_entry_helper::$javascript .= "indiciaData.optionalFilters={};\n";
    // If loaded via group link, don't load up the other download types.
    if (empty($_GET['group_id'])) {
      foreach ($args as $arg => $value) {
        if ($value && preg_match('/^([a-z_]+)_type_permission$/', $arg, $matches) && hostsite_user_has_permission($value)) {
          // Download type available. What they can actually download might be
          // limited by a context filter...
          $sharingType = ucwords(str_replace('_', ' ', $matches[1]));
          $sharingTypeCode = substr($sharingType, 0, 1);
          $gotPermissionsFilterForThisType = FALSE;
          // A place to store optional filters of this type in the js data.
          data_entry_helper::$javascript .= "indiciaData.optionalFilters.$sharingTypeCode={};\n";
          // Load their filters.
          $filters = self::load_filter_set($sharingTypeCode);
          foreach ($filters as $filter) {
            // The filter either defines their permissions, or is a user defined
            // filter which they can optionally apply.
            if ($filter['defines_permissions'] === 't') {
              $r["$sharingTypeCode filter $filter[id]"] = "$sharingType - $filter[title]";
              $gotPermissionsFilterForThisType = TRUE;
            }
            else {
              data_entry_helper::$javascript .= "indiciaData.optionalFilters.$sharingTypeCode.filter_$filter[id]=\"$filter[title]\";\n";
            }
          }
          if ($sharingTypeCode === 'R') {
            if ($args['download_my_records']) {
              $r['R my'] = lang::get('My records for reporting');
            }
            if (hostsite_user_has_permission($args['download_all_users_reporting'])) {
              $r['R'] = lang::get('All records for reporting');
            }
          }
          elseif ($sharingTypeCode === 'V') {
            // Load their profile settings for verification.
            $location_id = hostsite_get_user_field('location_expertise');
            $taxon_group_ids = hostsite_get_user_field('taxon_groups_expertise');
            if ($taxon_group_ids) {
              $taxon_group_ids = unserialize($taxon_group_ids);
            }
            $survey_ids = hostsite_get_user_field('surveys_expertise');
            if ($survey_ids) {
              $survey_ids = unserialize($survey_ids);
            }
            if ($location_id || !empty($taxon_group_ids) || !empty($survey_ids)) {
              $r['V profile'] = lang::get('Verification - my verification records');
            }
          }
          elseif (!$gotPermissionsFilterForThisType) {
            // If no permissions defined for this sharing type for this user,
            // then allow an all-access download.
            $r[$sharingTypeCode] = $sharingType;
          }
        }
      }
    }
    $canDownloadAdministeredGroups = !empty($args['download_administered_groups'])
        && hostsite_user_has_permission($args['download_administered_groups']);
    $canDownloadMemberGroups = !empty($args['download_member_groups'])
      && hostsite_user_has_permission($args['download_member_groups']);
    $params = [
      'user_id' => hostsite_get_user_field('indicia_user_id'),
      'view' => 'detail',
    ];
    if ($params['user_id']) {
      // Group page integration if user linked to warehouse.
      if (!empty($_GET['group_id'])) {
        $params['group_id'] = $_GET['group_id'];
      }
      if (!empty($args['download_group_types'])) {
        $params['query'] = json_encode([
          'in' => [
            'group_type_id' => explode(',', $args['download_group_types']),
          ],
        ]);
      }
      // User has access to a download records from the groups they administer.
      $groups = data_entry_helper::get_population_data([
        'table' => 'groups_user',
        'extraParams' => data_entry_helper::$js_read_tokens + $params,
      ]);
      foreach ($groups as $group) {
        $title = $group['group_title'] .
            (isset($group['group_expired']) && $group['group_expired'] === 't' ? ' (' . lang::get('finished') . ')' : '');
        if (($canDownloadAdministeredGroups && $group['administrator'] === 't') || $canDownloadMemberGroups) {
          $r["R group $group[group_id]"] = lang::get('All records added using a recording form for {1}', $title);
        }
        if ($args['download_my_records']) {
          $r["R group my $group[group_id]"] = lang::get('My records added using a recording form for {1}', $title);
        }
      }
    }
    return $r;
  }

  /**
   * Works out the list of download format options available to the user. This depends on the
   * permissions settings in the form configuration
   * @param array $args Form parameters
   * @return array Associative array of download formats
   */
  private static function get_download_formats($args) {
    $r = [];
    foreach ($args as $arg=>$value) {
      if ($value && preg_match('/^([a-z_]+)_format_permission$/', $arg, $matches) && hostsite_user_has_permission($value)) {
        $r[$matches[1]]=lang::get("format_$matches[1]");
      }
    }
    return $r;
  }

  /**
   * An ajax handler which returns the surveys that are available for a given sharing type.
   *
   * @param int $website_id
   *   Warehouse website ID.
   * @param string $password
   *   Warehouse website password.
   * @param int $nid
   *   Node ID.
   *
   * @return array
   *   Survey list.
   */
  public static function ajax_surveys_for_sharing_type($website_id, $password, $nid) {
    iform_load_helpers(['data_entry_helper']);
    // @todo filter by the available context filters if appropriate
    $readAuth = [
      'nonce' => $_GET['nonce'],
      'auth_token' => $_GET['auth_token']
    ];
    $surveys = data_entry_helper::get_population_data([
      'table' => 'survey',
      'extraParams' => $readAuth + [
        'view' => 'detail', 'orderby' => 'website,title',
      ],
      'sharing' => self::expand_sharing_mode($_GET['sharing_type'])
    ]);
    $r = [];
    foreach ($surveys as $survey) {
      $r["survey-$survey[id]"]="$survey[website] &gt; $survey[title]";
    }
    return $r;
  }

  /**
   * Performs the download.
   *
   * @param array $args
   * @param int $nid
   */
  private static function do_data_services_download($args, $nid) {
    iform_load_helpers(['report_helper']);
    $format=$_POST['format'];
    $isCustom = preg_match('/^custom-(\d+)$/', $_POST['format'], $matches);
    if ($isCustom) {
      $customFormats = json_decode($args['custom_formats'], true);
      $customFormat = $customFormats[$matches[1]];
      $report = $customFormat['report'];
      // strip unnecessary .xml from end of report name if provided
      $report = preg_replace('/\.xml$/', '', $report);
      $format = $customFormat['format'];
      $additionalParamText = $customFormat['params'];
    }
    else {
      $report = $args["report_$format"];
      $additionalParamText = $args["report_params_$format"];
    }
    $params = self::build_params($args);
    $params = array_merge($params, get_options_array_with_user_data($additionalParamText));
    $conn = iform_get_connection_details($nid);

    global $indicia_templates;
    // let's just get the URL, not the whole anchor element
    $indicia_templates['report_download_link'] = '{link}';
    $limit = ($args['limit'] == 0 ? '' : $args['limit']); // unlimited or limited
    $sharing = substr($_POST['download-type'], 0, 1);

    $url = report_helper::report_download_link([
      'readAuth' => data_entry_helper::$js_read_tokens,
      'dataSource' => $report,
      'extraParams' => $params,
      'format' => $format,
      'sharing' => self::expand_sharing_mode($sharing),
      'itemsPerPage' => $limit,
    ]);
    header("Location: $url");
    exit;
  }

  /**
   * Expand a single character sharing mode code (e.g. R) to the full term (e.g. reporting).
   * @param string $sharing Sharing mode code to expand.
   * @return string Expanded term.
   */
  private static function expand_sharing_mode($sharing) {
    switch ($sharing) {
      case 'R':
        return 'reporting';
      case 'P':
        return 'peer_review';
      case 'V':
        return 'verification';
      case 'D':
        return 'data_flow';
      case 'M':
        return 'moderation';
      case 'E':
        return 'editing';
    }
  }

  /**
   * Builds the parameters array to apply which filters the download report according to the report type, subfilter,
   * date range and survey selected.
   * @param array Form parameters.
   * @return array Parameters array to apply to the report.
   * @throws exception Thrown if requested download type not allowed for this user.
   */
  private static function build_params($args) {
    require_once 'includes/user.php';
    $availableTypes = self::get_download_types($args);
    if (!array_key_exists($_POST['download-type'], $availableTypes))
      throw new exception('Selected download type not authorised');
    $sharing = substr($_POST['download-type'], 0, 1);
    $params=[];
    // Have we got any filters to apply?
    if ($_POST['download-type']==='V profile') {
      // the user's profile defined verification settings
      $location_id = hostsite_get_user_field('location_expertise');
      if ($location_id)
        $params['location_list_context']=$location_id;
      $taxon_groups_ids = hostsite_get_user_field('taxon_groups_expertise');
      if ($taxon_groups_ids)
        $params['taxon_group_list_context']=implode(',', unserialize($taxon_groups_ids));
      $survey_ids = hostsite_get_user_field('surveys_expertise');
      if ($survey_ids)
        $params['survey_list_context']=implode(',', unserialize($survey_ids));
      $_POST['download-type']='V';
    }
    elseif (preg_match('/^[RPVDM] my$/', $_POST['download-type'])) {
      // autogenerated context for my records
      $params['my_records_context']=1;
      $_POST['download-type'] = substr($_POST['download-type'], 0, 1);
    }
    elseif (preg_match('/^R group (?P<my>(my )?)(?P<id>\d+)$/', $_POST['download-type'], $matches)) {
      // downloading records for a group
      $group = data_entry_helper::get_population_data([
        'table' => 'group',
        'extraParams' => data_entry_helper::$js_read_tokens + [
          'id' => $matches['id'],
          'view' => 'detail',
        ],
      ]);
      $group = $group[0];
      $filter = json_decode($group['filter_definition'] ?? '', TRUE);
      foreach ($filter as $field=>$value) {
        // Values shouldn't be arrays. Those which are are stray data from the filter save form.
        if (!is_array($value)) {
          $params["{$field}_context"]=$value;
        }
      }
      // Force explicitly contributed records only - otherwise easy to create a "download all" group overriding
      // intended permissions.
      $params['group_id'] = $matches['id'];
    }
    // if not doing a group download then we might need to apply one of the user's filters
    if (!preg_match('/^R group/', $_POST['download-type']) &&
        (strlen($_POST['download-type'])>1 || !empty($_POST['download-subfilter']))) {
      // use the saved filters system to filter the records
      $filterData = self::load_filter_set($sharing);
      if (preg_match('/^[RPVDM] filter (\d+)$/', $_POST['download-type'], $matches))
        // download type includes a context filter from the database
        self::apply_filter_to_params($filterData, $matches[1], '_context', $params);
      if (!empty($_POST['download-subfilter'])) {
        // a download subfilter has been selected
        self::apply_filter_to_params($filterData, $_POST['download-subfilter'], '', $params);
      }
    }
    if (!empty($_POST['survey_id']))
      $params['survey_list']=$_POST['survey_id'];
    $datePrefix = (!empty($_POST['date_type']) && $_POST['date_type']!=='recorded') ? "$_POST[date_type]_" : '';
    if (!empty($_POST['date_from']) && $_POST['date_from']!==lang::get('Click here'))
      $params[$datePrefix.'date_from']=$_POST['date_from'];
    if (!empty($_POST['date_to']) && $_POST['date_to']!==lang::get('Click here'))
      $params[$datePrefix.'date_to']=$_POST['date_to'];
    return $params;
  }

  /**
   * Loads the definition of a saved filter onto the params we are using to filter the report.
   * @param array $filterData List of filters loaded from the db
   * @param integer $filterId ID of the filter to load
   * @param string $paramSuffix Suffix for the parameter names to build. If this is a context filter, then
   * set this to '_context'.
   * @param type $params Params array which will be updated with those loaded from the saved filter.
   */
  private static function apply_filter_to_params($filterData, $filterId, $paramSuffix, &$params) {
    foreach ($filterData as $filterDef) {
      if ($filterDef['id']===$filterId) {
        $filter = json_decode($filterDef['definition'], true);
        foreach ($filter as $field=>$value) {
          // Values shouldn't be arrays. Those which are are stray data from the filter save form.
          if (!is_array($value)) {
            // to enforce this as the overall context, defining the maximum limit of the query results, append _context to the field names.
            // This prevents the filter negating the survey or date filter defined on the page.
            $params["$field$paramSuffix"]=$value;
          }
        }
        break;
      }
    }
  }

  /**
   * Declare the list of permissions we've got set up to pass to the CMS' permissions code.
   * @param int $nid Node ID, not used
   * @param array $args Form parameters array, used to extract the defined permissions.
   * @return array List of distinct permissions.
   */
  public static function get_perms($nid, $args) {
    $perms = [];
    if (!empty($args['download_all_users_reporting']))
      $perms[$args['download_all_users_reporting']] = '';
    if (!empty($args['download_administered_groups']))
      $perms[$args['download_administered_groups']] = '';
    if (!empty($args['reporting_type_permission']))
      $perms[$args['reporting_type_permission']] = '';
    if (!empty($args['peer_review_type_permission']))
      $perms[$args['peer_review_type_permission']] = '';
    if (!empty($args['verification_type_permission']))
      $perms[$args['verification_type_permission']] = '';
    if (!empty($args['data_flow_type_permission']))
      $perms[$args['data_flow_type_permission']] = '';
    if (!empty($args['moderation_type_permission']))
      $perms[$args['moderation_type_permission']] = '';
    if (!empty($args['editing_type_permission']))
      $perms[$args['editing_type_permission']] = '';
    if (!empty($args['csv_format_permission']))
      $perms[$args['csv_format_permission']] = '';
    if (!empty($args['tsv_format_permission']))
      $perms[$args['tsv_format_permission']] = '';
    if (!empty($args['kml_format_permission']))
      $perms[$args['kml_format_permission']] = '';
    if (!empty($args['gpx_format_permission']))
      $perms[$args['gpx_format_permission']] = '';
    if (!empty($args['nbn_format_permission']))
      $perms[$args['nbn_format_permission']] = '';
    if (!empty($args['custom_formats'])) {
      $customFormats = json_decode($args['custom_formats'], true);
      foreach ($customFormats as $idx=>$format) {
        if (!empty($format['permission']))
          $perms[$format['permission']]='';
      }
    }
    return array_keys($perms);
  }

  /**
   * Loads the set of report filters available for a given sharing type code. Avoids multiple loads.
   * @param string $sharingTypeCode A sharing mode, i.e. R(eporting), M(oderation), V(erification), P(eer review) or D(ata flow).
   * @return array Filters loaded from the database, available for this user & mode combination.
   */
  private static function load_filter_set($sharingTypeCode) {
    if (!isset(self::$filterSets[$sharingTypeCode]))
      self::$filterSets[$sharingTypeCode]=report_filters_load_existing(data_entry_helper::$js_read_tokens, $sharingTypeCode);
    return self::$filterSets[$sharingTypeCode];
  }

}
