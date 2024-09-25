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

require_once 'includes/report_filters.php';

/**
 * A quick and easy way to download data you have access to.
 */
class iform_easy_download implements PrebuiltFormInterface {

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_easy_download_definition() {
    return [
      'title' => 'Easy download',
      'category' => 'Utilities',
      'description' => 'A page for quick and easy download of the data you have access to.',
      'helpLink' => 'https://indicia-docs.readthedocs.org/en/latest/site-building/iform/prebuilt-forms/easy-download.html',
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
   *
   * @return array
   *   List of parameters that this form requires.
   */
  public static function get_parameters() {
    return [
      [
        'name' => 'permission',
        'caption' => 'Permission required for downloading other people\'s data if you have expertise',
        'description' => 'Set to the name of a permission which is required in order to be able to download other people\'s data. Also enables the NBN download format.',
        'type' => 'text_input',
        'required' => TRUE,
        'default' => 'verification',
      ],
      [
        'name' => 'allow_my_data',
        'caption' => 'Allow my data download?',
        'description' => 'Does this page include an option to download your own personal data?',
        'type' => 'checkbox',
        'required' => FALSE,
        'default' => 1,
      ],
      [
        'name' => 'allow_experts_data',
        'caption' => 'Allow expert\'s data download?',
        'description' => 'Does this page include an option to download data that the user is an expert for, if they are an expert?',
        'type' => 'checkbox',
        'required' => FALSE,
        'default' => 1,
      ],
      [
        'name' => 'allow_all_data',
        'caption' => 'Allow all data download?',
        'description' => 'Does this page include an option to download all data that matches the filter?',
        'type' => 'checkbox',
        'required' => FALSE,
        'default' => 0,
      ],
      [
        'name' => 'csv_format',
        'caption' => 'CSV format download?',
        'description' => 'Is CSV format available as a download option?',
        'type' => 'select',
        'options' => [
          'no' => 'No',
          'yes' => 'Yes',
          'expert' => 'Yes, but only for experts',
        ],
        'required' => TRUE,
        'default' => 'yes',
      ],
      [
        'name' => 'tsv_format',
        'caption' => 'TSV format download?',
        'description' => 'Is TSV format available as a download option?',
        'type' => 'select',
        'options' => [
          'no' => 'No',
          'yes' => 'Yes',
          'expert' => 'Yes, but only for experts',
        ],
        'required' => TRUE,
        'default' => 'no',
      ],
      [
        'name' => 'kml_format',
        'caption' => 'KML format download?',
        'description' => 'Is KML format available as a download option?',
        'type' => 'select',
        'options' => [
          'no' => 'No',
          'yes' => 'Yes',
          'expert' => 'Yes, but only for experts',
        ],
        'required' => TRUE,
        'default' => 'no',
      ],
      [
        'name' => 'gpx_format',
        'caption' => 'GPX format download?',
        'description' => 'Is GPX format available as a download option?',
        'type' => 'select',
        'options' => [
          'no' => 'No',
          'yes' => 'Yes',
          'expert' => 'Yes, but only for experts',
        ],
        'required' => TRUE,
        'default' => 'no',
      ],
      [
        'name' => 'nbn_format',
        'caption' => 'NBN format download?',
        'description' => 'Is NBN format available as a download option?',
        'type' => 'select',
        'options' => [
          'no' => 'No',
          'yes' => 'Yes',
          'expert' => 'Yes, but only for experts',
        ],
        'required' => TRUE,
        'default' => 'expert',
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
        'default' => 'library/occurrences/occurrences_download_2',
        'siteSpecific' => TRUE,
      ],
      [
        'name' => 'report_csv',
        'caption' => 'CSV download format report',
        'description' => 'Choose the report used for CSV downloads. Report should be compatible with the standard report parameters or explore reports.',
        'type' => 'report_helper::report_picker',
        'required' => TRUE,
        'default' => 'library/occurrences/occurrences_download_2',
      ],
      [
        'name' => 'report_params_csv',
        'caption' => 'CSV Additional parameters',
        'description' => 'Additional parameters to provide to the report when doing a CSV download. One per line, param=value format.',
        'type' => 'textarea',
        'required' => FALSE,
        'default' => "smpattrs=\noccattrs=\nsearchArea=\nidlist=\nquality=!R\n",
      ],
      [
        'name' => 'report_tsv',
        'caption' => 'TSV download format report',
        'description' => 'Choose the report used for TSV downloads. Report should be compatible with the standard report parameters or explore reports.',
        'type' => 'report_helper::report_picker',
        'required' => TRUE,
        'default' => 'library/occurrences/occurrences_download_2',
      ],
      [
        'name' => 'report_params_tsv',
        'caption' => 'TSV Additional parameters',
        'description' => 'Additional parameters to provide to the report when doing a TSV download. One per line, param=value format.',
        'type' => 'textarea',
        'required' => FALSE,
        'default' => "smpattrs=\noccattrs=\nsearchArea=\nidlist=\nquality=!R\n",
      ],
      [
        'name' => 'report_kml',
        'caption' => 'KML download format report',
        'description' => 'Choose the report used for KML downloads. Report should be compatible with the standard report parameters or explore reports and return a WKT for the geometry of the record transformed to EPSG:4326.',
        'type' => 'report_helper::report_picker',
        'required' => TRUE,
        'default' => 'library/occurrences/occurrences_download_2_gis',
      ],
      [
        'name' => 'report_params_kml',
        'caption' => 'KML Additional parameters',
        'description' => 'Additional parameters to provide to the report when doing a KML download. One per line, param=value format.',
        'type' => 'textarea',
        'required' => FALSE,
        'default' => "smpattrs=\noccattrs=\nsearchArea=\nidlist=\nquality=!R\n",
      ],
      [
        'name' => 'report_gpx',
        'caption' => 'GPX download format report',
        'description' => 'Choose the report used for GPX downloads. Report should be compatible with the standard report parameters or explore reports and return a WKT for the geometry of the record transformed to EPSG:4326.',
        'type' => 'report_helper::report_picker',
        'required' => TRUE,
        'default' => 'library/occurrences/occurrences_download_2_gis',
      ],
      [
        'name' => 'report_params_gpx',
        'caption' => 'GPX Additional parameters',
        'description' => 'Additional parameters to provide to the report when doing a GPX download. One per line, param=value format.',
        'type' => 'textarea',
        'required' => FALSE,
        'default' => "smpattrs=\noccattrs=\nsearchArea=\nidlist=\nquality=!R\n",
      ],
      [
        'name' => 'report_nbn',
        'caption' => 'NBN download format report',
        'description' => 'Choose the report used for NBN downloads. Report should be standard report parameters or compatible with the explore reports.',
        'type' => 'report_helper::report_picker',
        'required' => TRUE,
        'default' => 'library/occurrences/nbn_exchange',
      ],
      [
        'name' => 'report_params_nbn',
        'caption' => 'NBN Additional parameters',
        'description' => 'Additional parameters to provide to the report when doing an NBN download. One per line, param=value format.',
        'type' => 'textarea',
        'required' => FALSE,
        'default' => "smpattrs=\noccattrs=\nsearchArea=\nidlist=\nquality=V\n",
      ],
      [
        'name' => 'limit',
        'caption' => 'Limit to number of records',
        'description' => 'For performance reasons, unlimited downloads are not recommended. Set this to control the number of records' .
            'that can be downloaded at one time, or set to 0 for no limit.',
        'type' => 'text_input',
        'required' => FALSE,
        'default' => 20000,
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
  public static function get_form($args, $nid, $response = NULL) {
    // Do they have expert access?
    $expert = hostsite_user_has_permission($args['permission']);
    $conn = iform_get_connection_details($nid);
    $readAuth = data_entry_helper::get_read_auth($conn['website_id'], $conn['password']);
    // Find out which types of filters and formats are available to the user.
    $filters = self::get_filters($args, $readAuth);
    $formats = [];
    if ($args['csv_format'] === 'yes' || ($args['csv_format'] === 'expert' && $expert)) {
      $formats[] = 'csv';
    }
    if ($args['tsv_format'] === 'yes' || ($args['tsv_format'] === 'expert' && $expert)) {
      $formats[] = 'tsv';
    }
    if ($args['kml_format'] === 'yes' || ($args['kml_format'] === 'expert' && $expert)) {
      $formats[] = 'kml';
    }
    if ($args['gpx_format'] === 'yes' || ($args['gpx_format'] === 'expert' && $expert)) {
      $formats[] = 'gpx';
    }
    if ($args['nbn_format'] === 'yes' || ($args['nbn_format'] === 'expert' && $expert)) {
      $formats[] = 'nbn';
    }
    if (count($filters) === 0) {
      return 'This download page is configured so that no filter options are available.';
    }
    if (count($formats) === 0) {
      return 'This download page is configured so that no download format options are available.';
    }

    if (!empty($_POST)) {
      self::do_download($args, $nid);
    }

    iform_load_helpers(['data_entry_helper']);
    $reload = data_entry_helper::get_reload_link_parts();
    $reloadPath = $reload['path'];
    if (count($reload['params'])) {
      $reloadPath .= '?' . helper_base::array_to_query_string($reload['params']);
    }
    $r = '<form method="POST" action="' . $reloadPath . '">';
    $r .= '<fieldset><legend>' . lang::get('Filters') . '</legend>';
    if (count($filters) === 0) {
      return 'This download page is configured so that no filter options are available.';
    }
    elseif (count($filters) === 1) {
      $r .= '<input type="hidden" name="user-filter" value="' . implode('', array_keys($filters)) . '"/>';
      // Since there is only one option, we may as well tell the user what it
      // is.
      hostsite_set_page_title(implode('', array_values($filters)));
      if (implode('', array_keys($filters)) === 'mine') {
        $r .= '<p>' . lang::get('Use this form to download your own records.') . '</p>';
      }
    }
    else {
      $r .= data_entry_helper::radio_group([
        'label' => lang::get('User filter'),
        'fieldname' => 'user-filter',
        'lookupValues' => $filters,
        'default' => (empty($_POST['user-filter']) ? 'mine' : $_POST['user-filter']),
      ]);
    }
    if (empty($args['survey_id'])) {
      // A survey picker when downloading my data.
      $r .= '<div id="survey_all">';
      $r .= data_entry_helper::select([
        'fieldname' => 'survey_id_all',
        'label' => lang::get('Survey to include'),
        'table' => 'survey',
        'valueField' => 'id',
        'captionField' => 'full_title',
        'helpText' => 'Choose a survey, or <all> to not filter by survey.',
        'blankText' => '<all>',
        'class' => 'control-width-4',
        'extraParams' => $readAuth + ['sharing' => 'data_flow', 'orderby' => 'full_title', 'view' => 'detail'],
      ]);
      $r .= '</div>';
      // A survey picker when downloading data you are an expert for.
      $surveys_expertise = hostsite_get_user_field('surveys_expertise');
      if ($surveys_expertise) {
        $surveys_expertise = unserialize($surveys_expertise);
        $surveysFilter = ['query' => json_encode(['in' => ['id' => $surveys_expertise]])];
      }
      else {
        // No filter as there are no specific surveys this user is an expert
        // for.
        $surveysFilter = [];
      }
      $r .= '<div id="survey_expertise">';
      $r .= data_entry_helper::select([
        'fieldname' => 'survey_id_expert',
        'label' => lang::get('Survey to include'),
        'table' => 'survey',
        'valueField' => 'id',
        'captionField' => 'full_title',
        'helpText' => 'Choose a survey, or <all> to not filter by survey.',
        'blankText' => '<all>',
        'class' => 'control-width-4',
        'extraParams' => $readAuth + [
          'sharing' => 'verification',
          'orderby' => 'full_title',
        ] + $surveysFilter,
      ]);
      $r .= '</div>';
    }
    // Let the user pick the date range to download.
    $r .= data_entry_helper::date_picker([
      'fieldname' => 'date_from',
      'label' => lang::get('Start Date'),
      'helpText' => 'Leave blank for no start date filter',
      'class' => 'control-width-4',
    ]);
    $r .= data_entry_helper::date_picker([
      'fieldname' => 'date_to',
      'label' => lang::get('End Date'),
      'helpText' => 'Leave blank for no end date filter',
      'class' => 'control-width-4',
    ]);
    $r .= '</fieldset>';
    $r .= '<fieldset><legend>' . lang::get('Downloads') . '</legend>';
    $r .= '<label>Download options:</label>';
    if (in_array('csv', $formats)) {
      $r .= '<input class="inline-control" type="submit" name="format" value="' . lang::get('Spreadsheet (CSV)') . '"/>';
    }
    if (in_array('tsv', $formats)) {
      $r .= '<input class="inline-control" type="submit" name="format" value="' . lang::get('Tab Separated File (TSV)') . '"/>';
    }
    if (in_array('kml', $formats)) {
      $r .= '<input class="inline-control" type="submit" name="format" value="' . lang::get('Google Earth File') . '"/>';
    }
    if (in_array('gpx', $formats)) {
      $r .= '<input class="inline-control" type="submit" name="format" value="' . lang::get('GPS Track File') . '"/>';
    }
    if (in_array('nbn', $formats)) {
      $r .= '<input class="inline-control" type="submit" name="format" value="' . lang::get('NBN Format') . '"/>';
      $r .= '<p class="helpText">' .
        lang::get('Note that the NBN format download will only include verified data and excludes records where the date or spatial reference is not compatible with the NBN Gateway.') .
        '</p>';
    }
    $r .= '</fieldset></form>';
    return $r;
  }

  /**
   * Returns an array of the available types of filter (my records, expert records, all records).
   *
   * @param array $args
   *   Form arguments.
   * @param array $readAuth
   *   Read authorisation tokens.
   *
   * @return array
   *   Associative array of filter type suitable for loading into a picker control.
   */
  private static function get_filters($args, $readAuth) {
    $filters = [];
    if ($args['allow_my_data']) {
      $filters['mine']=lang::get('Download my records');
    }
    if ($args['allow_experts_data']) {
      if (preg_match('/^library\/occurrences\/filterable_/', $args["report_csv"])) {
        // Using the new standard params style of report. So, we can support
        // verification permissions via filters. First, apply legacy
        // verification settings from their profile.
        $location_id = hostsite_get_user_field('location_expertise');
        $taxon_group_ids = hostsite_get_user_field('taxon_groups_expertise');
        $survey_ids = hostsite_get_user_field('surveys_expertise');
        if ($location_id || $taxon_group_ids || $survey_ids) {
          $filters['expert'] = lang::get('Download all records I have access to as an expert');
        }
        // Now get their verification contexts.
        $filterData = report_filters_load_existing($readAuth, 'V');
        foreach ($filterData as $filter) {
          // Does the filter define a verification permissions set?
          if ($filter['defines_permissions'] === 't') {
            $filters['expert-id-' . $filter['id']] = lang::get('Download my verification records') . ' - ' . $filter['title'];
          }
        }
      }
      else {
        $filters['expert'] = lang::get('Download all records I have access to as an expert');
      }
    }
    if ($args['allow_all_data']) {
      $filters['all'] = lang::get('Download all records');
    }
    return $filters;
  }

  /**
   * Handles a request for download.
   *
   * Works out which type of request it is and calls the appropriate function.
   *
   * @param array $args
   *   Form configuration arguments.
   * @param object $nid
   *   Node object's ID.
   */
  private static function do_download($args, $nid) {
    if ($_POST['format'] === lang::get('Spreadsheet (CSV)')) {
      self::do_data_services_download($args, $nid, 'csv');
    }
    elseif ($_POST['format'] === lang::get('Tab Separated File (TSV)')) {
      self::do_data_services_download($args, $nid, 'tsv');
    }
    elseif ($_POST['format'] === lang::get('Google Earth File')) {
      self::do_data_services_download($args, $nid, 'kml');
    }
    elseif ($_POST['format'] === lang::get('GPS Track File')) {
      self::do_data_services_download($args, $nid, 'gpx');
    }
    elseif ($_POST['format'] === lang::get('NBN Format')) {
      self::do_data_services_download($args, $nid, 'nbn');
    }
  }

  private static function do_data_services_download($args, $nid, $format) {
    iform_load_helpers(['report_helper']);
    $conn = iform_get_connection_details($nid);
    $readAuth = data_entry_helper::get_read_auth($conn['website_id'], $conn['password']);
    if (preg_match('/^library\/occurrences\/filterable/', $args["report_$format"])) {
      $filter = self::build_filter($args, $readAuth, $format, TRUE);
    }
    else {
      $filter = self::build_filter($args, $readAuth, $format, FALSE);
    }
    global $indicia_templates;
    // Let's just get the URL, not the whole anchor element.
    $indicia_templates['report_download_link'] = '{link}';
    $limit = ($args['limit'] == 0 ? '' : $args['limit']); // unlimited or limited
    $sharing = preg_match('/^expert/', $_POST['user-filter']) ? 'verification' : 'data_flow';
    $url = report_helper::report_download_link([
      'readAuth' => $readAuth,
      'dataSource' => $args["report_$format"],
      'extraParams' => $filter,
      'format' => $format,
      'sharing' => $sharing,
      'itemsPerPage' => $limit,
    ]);
    header("Location: $url");
    exit;
  }

  /**
   * Builds a filter to send in the request extraParams for the download data.
   *
   * @param array $args
   *   Form configuration.
   * @param array $readAuth
   *   Read authorisation tokens.
   * @param string $format
   *   File format being requested, e.g. csv.
   * @param bool $useStandardParams
   *   True if the download report supports the standard parameters for
   *   occurrence reporting.
   *
   * @return array
   *   List of filter key value pairs.
   */
  private static function build_filter($args, $readAuth, $format, $useStandardParams) {
    require_once 'includes/user.php';
    $filterToApply = $_POST['user-filter'];
    $availableFilters = self::get_filters($args, $readAuth);
    if (!array_key_exists($filterToApply, $availableFilters)) {
      throw new exception('Selected filter type not authorised');
    }
    if ($filterToApply === 'expert') {
      require_once 'includes/user.php';
      $location_expertise = hostsite_get_user_field('location_expertise');
      $taxon_groups_expertise = hostsite_get_user_field('taxon_groups_expertise', [], TRUE);
      $surveys_expertise = hostsite_get_user_field('surveys_expertise', [], TRUE);
    }
    else {
      // Default is no filter by survey, locality, taxon group.
      $location_expertise = '';
      $taxon_groups_expertise = [];
      $surveys_expertise = [];
    }
    // We are downloading either a configured survey, a selected single survey,
    // or the surveys the user can see. The field name used will depend on
    // which of the survey selects were active - either we are selecting from a
    // list of surveys the user is an expert for, or a list of all surveys.
    $surveyFieldName = 'survey_id_' . (preg_match('/^expert/', $filterToApply) ? 'expert' : 'all');
    if (empty($args['survey_id'])) {
      $surveys = empty($_POST[$surveyFieldName]) ? implode(',', $surveys_expertise) : $_POST[$surveyFieldName];
    }
    else {
      // Srvey to load is preconfigured for the form.
      $surveys = $args['survey_id'];
    }

    $ownData = $filterToApply === 'mine' ? 1 : 0;
    // Depending on if we are using the old explore report format or the new
    // filterable format, the filter field names differ.
    $userIdField = $useStandardParams ? 'user_id' : 'currentUser';
    $myRecordsField = $useStandardParams ? 'my_records' : 'ownData';
    $locationIdField = $useStandardParams ? 'indexed_location_list' : 'location_list';
    $surveysListField = $useStandardParams ? 'survey_list' : 'surveys';
    $taxonGroupListField = $useStandardParams ? 'taxon_group_list' : 'taxon_groups';
    $filters = array_merge(
      [
        $userIdField => hostsite_get_user_field('indicia_user_id'),
        $myRecordsField => $ownData,
        $locationIdField => $location_expertise,
        'ownLocality' => !empty($location_expertise) && $filterToApply === 'expert' ? 1 : 0,
        $taxonGroupListField => !empty($taxon_groups_expertise) ? implode(',', $taxon_groups_expertise) : '',
        'ownGroups' => !empty($taxon_groups_expertise) && $taxon_groups_expertise && $filterToApply === 'expert' ? 1 : 0,
        $surveysListField => $surveys,
        'ownSurveys' => empty($surveys) ? 0 : 1,
      ], get_options_array_with_user_data($args["report_params_$format"])
    );
    // some of these filter fields are not required for standard params
    if ($useStandardParams) {
      unset($filters['ownLocality']);
      unset($filters['ownGroups']);
      unset($filters['ownSurveys']);
    }
    if (!empty($_POST['date_from']) && $_POST['date_from'] !== lang::get('Click here')) {
      $filters['date_from'] = $_POST['date_from'];
    }
    elseif (!$useStandardParams) {
      $filters['date_from'] = '';
    }
    if (!empty($_POST['date_to']) && $_POST['date_to'] !== lang::get('Click here')) {
      $filters['date_to'] = $_POST['date_to'];
    }
    elseif (!$useStandardParams) {
      $filters['date_to'] = '';
    }
    // Now, if they have a verification context filter in force, then apply it.
    $filters = array_merge(
      self::get_filter_verification_context($filterToApply, $readAuth),
      $filters
    );
    return $filters;
  }

  /**
   * If filtering by a verification context, then load the filter and return the array of
   * filter data ready to use as a permissions context.
   *
   * @param string $filterToApply
   *   Identifier for the filter we are applying. A filter with ID expert-id-n
   *   means that filter ID n will be loaded and returned.
   * @param array $readAuth
   *   Read authorisation tokens.
   *
   * @return array
   *   List of filters.
   */
  private static function get_filter_verification_context($filterToApply, $readAuth) {
    $filters = [];
    if (preg_match('/^expert-id-(\d+)/', $filterToApply, $matches)) {
      $filterData = report_filters_load_existing($readAuth, 'V');
      foreach ($filterData as $filterDef) {
        if ($filterDef['id'] === $matches[1]) {
          $contextFilter = json_decode($filterDef['definition'], TRUE);
          foreach ($contextFilter as $field => $value) {
            // To enforce this as the overall context, defining the maximum
            // limit of the query results, append _context to the field names.
            $filters["{$field}_context"] = $value;
          }
          break;
        }
      }
    }
    return $filters;
  }

}
