<?php

/**
 * @file
 * A helper class for Elasticsearch reporting code.
 *
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

class ElasticsearchReportHelper {

  /**
   * Count controls to make unique IDs.
   *
   * @var integer
   */
  private static $controlIndex = 0;

  /**
   * Track control IDs so warning can be given if duplicate IDs are used.
   *
   * @var array
   */
  private static $controlIds = [];

  private static $esMappings;

  /**
   * List of ES fields with caption and description for each.
   *
   * @internal
   */
  const MAPPING_FIELDS = [
    '@timestamp' => [
      'caption' => 'Indexing timestamp',
      'description' => 'Timestamp when the record was indexed into to the reporting system.',
    ],
    'id' => [
      'caption' => 'ID',
      'description' => 'Unique record ID.',
    ],
    'event.event_id' => [
      'caption' => 'Sample ID',
      'description' => 'Unique sample ID.',
    ],
    '#datasource_code#' => [
      'caption' => 'Datasource codes',
      'description' => 'Website and survey dataset the record is sourced from, in abbreviated encoded form.',
    ],
    '#status_icons#' => [
      'caption' => 'Record status icons',
      'description' => "Icons showing the record's verification status and sensitivity information.",
    ],
    '#data_cleaner_icons#' => [
      'caption' => 'Automated checks',
      'description' => "Icons showing the results of automated checks on the record.",
    ],
    '#event_date#' => [
      'caption' => 'Date',
      'description' => 'Date of the record',
    ],
    'event.day_of_year' => [
      'caption' => 'Day of year',
      'description' => 'Numeric day within the year of the record (1-366)',
    ],
    'event.month' => [
      'caption' => 'Month',
      'description' => 'Numeric month of the record',
    ],
    'event.year' => [
      'caption' => 'Year',
      'description' => 'Year of the record',
    ],
    'event.event_remarks' => [
      'caption' => 'Sample comment',
      'description' => 'Comment given for the sample by the recorder.',
    ],
    'event.habitat' => [
      'caption' => 'Habitat',
      'description' => 'Habitat recorded for the sample.',
    ],
    'event.recorded_by' => [
      'caption' => 'Recorder name(s)',
      'description' => 'Name of the people involved in the field record.',
    ],
    'event.sampling_protocol' => [
      'caption' => 'Sample method',
      'description' => 'Method for the sample if provided.',
    ],
    'identification.identified_by' => [
      'caption' => 'Identified by',
      'description' => 'Identifier (determiner) of the record.',
    ],
    'identification.recorder_certainty' => [
      'caption' => 'Recorder certainty',
      'description' => 'Certainty that the identification is correct as attributed by the recorder.',
    ],
    'identification.verifier.name' => [
      'caption' => 'Verifier name',
      'description' => "Name of the verifier responsible for record's current verification status.",
    ],
    'identification.verified_on' => [
      'caption' => 'Verified on',
      'description' => "Date/time of the current verification decision.",
    ],
    'identification.verification_decision_source' => [
      'caption' => 'Verification decision source',
      'description' => 'Either M for machine based verification or H for human verification decisions.',
    ],
    'taxon.name' => [
      'caption' => 'Taxon name',
      'description' => 'Name as recorded for the taxon.',
    ],
    'taxon.accepted_name' => [
      'caption' => 'Accepted name',
      'description' => 'Currently accepted name for the recorded taxon.',
    ],
    'taxon.vernacular_name' => [
      'caption' => 'Common name',
      'description' => 'Common name for the recorded taxon.',
    ],
    'taxon.group' => [
      'caption' => 'Taxon group',
      'description' => 'Taxon reporting group associated with the current identification of this record.',
    ],
    'taxon.kingdom' => [
      'caption' => 'Kingdom',
      'description' => 'Taxonomic kingdom associated with the current identification of this record.',
    ],
    'taxon.phylum' => [
      'caption' => 'Phylum',
      'description' => 'Taxonomic phylum associated with the current identification of this record.',
    ],
    'taxon.class' => [
      'caption' => 'Class',
      'description' => 'Taxonomic class associated with the current identification of this record.',
    ],
    'taxon.order' => [
      'caption' => 'Order',
      'description' => 'Taxonomic order associated with the current identification of this record.',
    ],
    'taxon.family' => [
      'caption' => 'Family',
      'description' => 'Taxonomic family associated with the current identification of this record.',
    ],
    'taxon.subfamily' => [
      'caption' => 'Subfamily',
      'description' => 'Taxonomic subfamily associated with the current identification of this record.',
    ],
    'taxon.taxon_rank' => [
      'caption' => 'Taxon rank',
      'description' => 'Taxonomic rank associated with the current identification of this record.',
    ],
    'taxon.genus' => [
      'caption' => 'Genus',
      'description' => 'Taxonomic genus associated with the current identification of this record.',
    ],
    'location.verbatim_locality' => [
      'caption' => 'Location name',
      'description' => 'Location name associated with the record.',
    ],
    'location.name' => [
      'caption' => 'Location',
      'description' => 'Location associated with the record where the record was linked to a defined location.',
    ],
    'location.location_id' => [
      'caption' => 'Location ID',
      'description' => 'Unique ID of the location associated with the record where the record was linked to a defined location.',
    ],
    'location.parent_name' => [
      'caption' => 'Parent location',
      'description' => 'Parent location associated with the record where the record was linked to a defined location which has a hierarchical parent.',
    ],
    'location.parent_location_id' => [
      'caption' => 'Parent location ID',
      'description' => 'Unique ID of the parent location associated with the record where the record was linked to a defined location which has a hierarchical parent.',
    ],
    'location.output_sref' => [
      'caption' => 'Display spatial reference',
      'description' => 'Spatial reference in the recommended local grid system.',
    ],
    'location.coordinate_uncertainty_in_meters' => [
      'caption' => 'Coordinate uncertainty in metres',
      'description' => 'Uncertainty of a provided GPS point.',
    ],
    '#lat_lon#' => [
      'caption' => 'Lat/lon',
      'description' => 'Latitude and longitude of the record.',
    ],
    'occurrence.media' => [
      'caption' => 'Media',
      'description' => 'Thumbnails for any occurrence photos and other media.',
      'handler' => 'media',
    ],
    'occurrence.sex' => [
      'caption' => 'Sex',
      'description' => 'Sex of the recorded organism',
    ],
    'occurrence.life_stage' => [
      'caption' => 'Life stage',
      'description' => 'Life stage of the recorded organism.',
    ],
    'occurrence.individual_count' => [
      'caption' => 'Count',
      'description' => 'Numeric abundance count of the recorded organism.',
    ],
    'occurrence.organism_quantity' => [
      'caption' => 'Quantity',
      'description' => 'Abundance of the recorded organism (numeric or text).',
    ],
  ];

  /**
   * Prepares the page for interacting with the Elasticsearch proxy.
   *
   * @param int $nid
   *   Node ID or NULL if not on a node.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-enableElasticsearchProxy
   */
  public static function enableElasticsearchProxy($nid = NULL) {
    helper_base::add_resource('datacomponents');
    // Retrieve the Elasticsearch mappings.
    self::getMappings($nid);
    // Prepare the stuff we need to pass to the JavaScript.
    $mappings = self::$esMappings;
    $dateFormat = helper_base::$date_format;
    $rootFolder = helper_base::getRootFolder(TRUE);
    $esProxyAjaxUrl = hostsite_get_url('iform/esproxy');
    helper_base::$indiciaData['esProxyAjaxUrl'] = $esProxyAjaxUrl;
    helper_base::$indiciaData['esSources'] = [];
    helper_base::$indiciaData['esMappings'] = $mappings;
    helper_base::$indiciaData['dateFormat'] = $dateFormat;
    helper_base::$indiciaData['rootFolder'] = $rootFolder;
    $config = hostsite_get_es_config($nid);
    helper_base::$indiciaData['esVersion'] = (int) $config['es']['version'];
  }

  /**
   * A control for flexibly outputting data formatted using a custom script.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-customScript
   */
  public static function customScript(array $options) {
    self::checkOptions('customScript', $options, ['source', 'functionName'], []);
    $dataOptions = helper_base::getOptionsForJs($options, [
      'source',
      'functionName',
    ], empty($options['attachToId']));
    helper_base::$javascript .= <<<JS
$('#$options[id]').idcCustomScript({});

JS;
    return self::getControlContainer('customScript', $options, $dataOptions);
  }

  /**
   * An Elasticsearch or Indicia powered grid control.
   *
   * @return string
   *   Grid container HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-datagrid
   */
  public static function dataGrid(array $options) {
    self::checkOptions(
      'dataGrid',
      $options,
      ['source'],
      ['actions', 'columns', 'responsiveOptions', 'availableColumns', 'applyFilterRowToSources']
    );
    if (empty($options['columns'])) {
      throw new Exception('Control [dataGrid] requires a parameter called @columns.');
    }
    if (!empty($options['scrollY']) && !preg_match('/^\d+px$/', $options['scrollY'])) {
      throw new Exception('Control [dataGrid] @scrollY parameter must be of CSS pixel format, e.g. 100px');
    }
    $options = array_merge([
      'availableColumns' => !empty($options['aggregation']) ? [] : array_keys(self::MAPPING_FIELDS),
    ], $options);
    $columnsByField = [];
    foreach ($options['columns'] as $columnDef) {
      if (empty($columnDef['field'])) {
        throw new Exception('Control [dataGrid] @columns option does not contain a field for every item.');
      }
      if (!isset($columnDef['caption'])) {
        $columnDef['caption'] = '';
      }
      $field = $columnDef['field'];
      unset($columnDef['field']);
      $columnsByField[$field] = $columnDef;
    }
    $options['columns'] = array_keys($columnsByField);
    foreach ($options['availableColumns'] as $field) {
      if (array_key_exists($field, self::MAPPING_FIELDS)) {
        if (!isset($columnsByField[$field])) {
          $columnsByField[$field] = self::MAPPING_FIELDS[$field];
        }
        else {
          $columnsByField[$field] = array_merge(self::MAPPING_FIELDS[$field], $columnsByField[$field]);
        }
      }
    }
    $options['availableColumnInfo'] = $columnsByField;
    helper_base::add_resource('jquery_ui');
    helper_base::add_resource('indiciaFootableReport');
    // Add footableSort for aggregation tables.
    if ((!empty($options['aggregation']) && $options['aggregation'] === 'simple') || !empty($options['sourceTable'])) {
      helper_base::add_resource('footableSort');
    }
    // Fancybox for image popups.
    helper_base::add_resource('fancybox');
    $dataOptions = helper_base::getOptionsForJs($options, [
      'source',
      'columns',
      'availableColumnInfo',
      'actions',
      'cookies',
      'includeColumnHeadings',
      'includeFilterRow',
      'includePager',
      'includeMultiSelectTool',
      'responsive',
      'responsiveOptions',
      'autoResponsiveCols',
      'autoResponsiveExpand',
      'sortable',
      'aggregation',
      'sourceTable',
      'scrollY',
      'applyFilterRowToSources',
    ], empty($options['attachToId']));
    helper_base::$javascript .= <<<JS
$('#$options[id]').idcDataGrid({});

JS;
    return self::getControlContainer('dataGrid', $options, $dataOptions);
  }

  /**
   * A button for downloading the ES data from a source.
   *
   * @return string
   *   HTML for download button and progress display.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-download
   */
  public static function download(array $options) {
    self::checkOptions('esDownload', $options,
      ['source'],
      ['addColumns', 'removeColumns']
    );
    global $indicia_templates;
    $r = str_replace(
      [
        '{id}',
        '{title}',
        '{class}',
        '{caption}',
      ], [
        $options['id'],
        lang::get('Run the download'),
        "class=\"$indicia_templates[buttonHighlightedClass] do-download\"",
        lang::get('Download'),
      ],
      $indicia_templates['button']
    );
    $progress = <<<HTML
<div class="progress-circle-container">
  <svg>
    <circle class="circle"
            cx="-90"
            cy="90"
            r="80"
            style="stroke-dashoffset:503px;"
            stroke-dasharray="503"
            transform="rotate(-90)" />
      </g>
      </text>
  </svg>
  <div class="progress-text"></div>
</div>

HTML;
    $r .= str_replace(
      [
        '{attrs}',
        '{col-1}',
        '{col-2}',
      ],
      [
        '',
        $progress,
        '<div class="idc-download-files"><h2>' . lang::get('Files') . ':</h2></div>',
      ],
      $indicia_templates['two-col-50']);
    // This does nothing at the moment - just a placeholder for if and when we
    // add some download options.
    $dataOptions = helper_base::getOptionsForJs($options, [
      'source',
      'aggregation',
      'columnsTemplate',
      'addColumns',
      'removeColumns',
    ], empty($options['attachToId']));
    helper_base::$javascript .= <<<JS
$('#$options[id]').idcEsDownload({});

JS;
    return self::getControlContainer('esDownload', $options, $dataOptions, $r);
  }

  /**
   * A select box for choosing from a list of higher geography boundaries.
   *
   * @return string
   *   Control HTML
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-higherGeopgraphySelect
   */
  public static function higherGeographySelect(array $options) {
    if (empty($options['locationTypeId']) ||
        (!is_array($options['locationTypeId']) && !preg_match('/^\d+$/', $options['locationTypeId']))) {
      throw new Exception('An integer or integer array @locationTypeId parameter is required for the [higherGeographySelect] control');
    }
    $typeIds = is_array($options['locationTypeId']) ? $options['locationTypeId'] : [$options['locationTypeId']];
    $r = '';
    $options = array_merge([
      'class' => 'es-higher-geography-select',
      'blankText' => lang::get('<All locations shown>'),
      'id' => 'higher-geography-select',
    ], $options);
    $options['extraParams'] = array_merge([
      'orderby' => 'name',
    ], $options['extraParams'], $options['readAuth']);
    $baseId = $options['id'];
    foreach ($typeIds as $idx => $typeId) {
      $options['extraParams']['location_type_id'] = $typeId;
      if (count($typeIds) > 0) {
        $options['id'] = "$baseId-$idx";
        $options['class'] .= ' linked-select';
      }
      if ($idx > 0) {
        $options['parentControlId'] = "$baseId-" . ($idx - 1);
        if ($idx === 1) {
          $options['parentControlLabel'] = $options['label'];
          $options['filterField'] = 'parent_id';
          unset($options['label']);
        }
      }
      $r .= data_entry_helper::location_select($options);
    }
    return $r;
  }

  /**
   * An Elasticsearch or Indicia data powered Leaflet map control.
   *
   * @return string
   *   Map container HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-leafletMap
   */
  public static function leafletMap(array $options) {
    self::checkOptions('leafletMap', $options, ['layerConfig'], ['layerConfig']);
    $options = array_merge([
      'initialLat' => hostsite_get_config_value('iform', 'map_centroid_lat', 54.093409),
      'initialLng' => hostsite_get_config_value('iform', 'map_centroid_long', -2.89479),
      'initialZoom' => hostsite_get_config_value('iform', 'map_zoom', 5),
    ], $options);
    helper_base::add_resource('leaflet');
    $dataOptions = helper_base::getOptionsForJs($options, [
      'layerConfig',
      'showSelectedRow',
      'initialLat',
      'initialLng',
      'initialZoom',
      'cookies',
    ], empty($options['attachToId']));
    helper_base::$javascript .= <<<JS
$('#$options[id]').idcLeafletMap({});

JS;
    helper_base::$late_javascript .= <<<JS
$('#$options[id]').idcLeafletMap('bindGrids');

JS;
    return self::getControlContainer('leafletMap', $options, $dataOptions);
  }

  /**
   * Output a selector for various high level permissions filtering options.
   *
   * @return string
   *   Select HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-permissionFilters
   */
  public static function permissionFilters(array $options) {
    $allowedTypes = [];
    // Add My records download permission if allowed.
    if (!empty($options['my_records_permission']) && hostsite_user_has_permission($options['my_records_permission'])) {
      $allowedTypes['my'] = lang::get('My records');
    }
    // Add All records download permission if allowed.
    if (!empty($options['all_records_permission']) && hostsite_user_has_permission($options['all_records_permission'])) {
      $allowedTypes['all'] = lang::get('All records');
    }
    // Add collated location (e.g. LRC boundary) records download permission if
    // allowed.
    if (!empty($options['location_collation_records_permission'])
        && hostsite_user_has_permission($options['location_collation_records_permission'])) {
      $locationId = hostsite_get_user_field('location_collation');
      if ($locationId) {
        $locationData = data_entry_helper::get_population_data([
          'table' => 'location',
          'extraParams' => $options['readAuth'] + ['id' => $locationId],
        ]);
        if (count($locationData) > 0) {
          $allowedTypes['location_collation'] = lang::get('Records within location ' . $locationData[0]['name']);
        }
      }
    }
    if (count($allowedTypes) === 1) {
      $value = array_values($allowedTypes)[0];
      return <<<HTML
<input type="hidden" name="es-permissions-filter" value="$value" class="permissions-filter" />

HTML;
    }
    else {
      return data_entry_helper::select([
        'fieldname' => 'es-permissions-filter',
        'lookupValues' => $allowedTypes,
        'class' => 'permissions-filter',
      ]);
    }
  }

  /**
   * A tabbed control to show full record details and verification info.
   *
   * @return string
   *   Panel container HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-recordDetails
   */
  public static function recordDetails(array $options) {
    $options = array_merge([
      'allowRedetermination' => FALSE,
    ], $options);
    self::checkOptions('recordDetails', $options, ['showSelectedRow'], ['locationTypes']);
    if (!empty($options['explorePath'])) {
      // Build  URL which overrides the default filters applied to many Explore
      // pages in order to be able to apply out own filter.
      $options['exploreUrl'] = hostsite_get_url(
        $options['explorePath'],
        [
          'filter-quality' => '-q-',
          'filter-date_from' => '-df-',
          'filter-date_to' => '-dt-',
          'filter-user_id' => '-userId-',
          'filter-date_age' => '',
          'filter-indexed_location_list' => '',
          'filter-indexed_location_id' => '',
          'filter-my_records' => 1,
        ]
      );
    }
    $dataOptions = helper_base::getOptionsForJs($options, [
      'showSelectedRow',
      'exploreUrl',
      'locationTypes',
      'allowRedetermination',
    ], TRUE);
    helper_base::add_resource('tabs');
    helper_base::$javascript .= <<<JS
$('#$options[id]').idcRecordDetailsPane({});

JS;
    $r = <<<HTML
<div class="details-container" id="$options[id]" data-idc-config="$dataOptions">
  <div class="empty-message alert alert-info"><span class="fas fa-info-circle fa-2x"></span>Select a row to view details</div>
  <div class="tabs" style="display: none">
    <ul>
      <li><a href="#tabs-details">Details</a></li>
      <li><a href="#tabs-comments">Comments</a></li>
      <li><a href="#tabs-recorder-experience">Recorder experience</a></li>
    </ul>
    <div id="tabs-details">
      <div class="record-details">
      </div>
    </div>
    <div id="tabs-comments">
      <div class="comments">
      </div>
    </div>
    <div id="tabs-recorder-experience">
      <div class="recorder-experience"></div>
      <div class="loading-spinner" style="display: none"><div>Loading...</div></div>
    </div>
  </div>
</div>

HTML;
    if ($options['allowRedetermination']) {
      $redetTaxonListId = hostsite_get_config_value('iform', 'master_checklist_id');
      if (!$redetTaxonListId) {
        throw new Exception('[recordDetails] control has @allowRedetermination option but the Indicia setting ' .
          'Master Checklist ID is not set. This is required to provide a list to select the redetermination from.');
      }
      helper_base::add_resource('validation');
      $redetUrl = iform_ajaxproxy_url(NULL, 'occurrence');
      $userId = hostsite_get_user_field('indicia_user_id');
      helper_base::$indiciaData['ajaxFormPostRedet'] = "$redetUrl&user_id=$userId&sharing=editing";
      $speciesInput = data_entry_helper::species_autocomplete([
        'label' => lang::get('Redetermine to'),
        'helpText' => lang::get('Select the new taxon name.'),
        'fieldname' => 'redet-species',
        'extraParams' => $options['readAuth'] + ['taxon_list_id' => $redetTaxonListId],
        'speciesIncludeAuthorities' => TRUE,
        'speciesIncludeBothNames' => TRUE,
        'speciesNameFilterMode' => 'all',
        'validation' => ['required'],
      ]);
      $commentInput = data_entry_helper::textarea([
        'label' => lang::get('Explanation comment'),
        'helpText' => lang::get('Please give reasons why you are changing this record.'),
        'fieldname' => 'redet-comment',
      ]);
      $r .= <<<HTML
<div id="redet-panel-wrap" style="display: none">
  <form id="redet-form">
    $speciesInput
    $commentInput
    <button type="submit" class="btn btn-primary" id="apply-redet">Apply redetermination</button>
    <button type="button" class="btn btn-danger" id="cancel-redet">Cancel</button>
  </form>
</div>
HTML;
    }
    return $r;
  }

  /**
   * Initialises the JavaScript required for an Elasticsearch data source.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-source
   *
   * @return string
   *   Empty string as no HTML required.
   */
  public static function source(array $options) {
    self::applyReplacements($options, ['aggregation'], ['aggregation']);
    self::checkOptions(
      'source',
      $options,
      ['id'],
      ['aggregation', 'filterBoolClauses', 'buildTableXY', 'sort']
    );
    $options = array_merge([
      'aggregationMapMode' => 'geoHash',
    ], $options);
    $jsOptions = [
      'id',
      'from',
      'size',
      'sort',
      'filterPath',
      'aggregation',
      'aggregationMapMode',
      'buildTableXY',
      'initialMapBounds',
      'filterBoolClauses',
      'filterSourceGrid',
      'filterSourceField',
      'filterField',
      'filterBoundsUsingMap',
    ];
    helper_base::$indiciaData['esSources'][] = array_intersect_key($options, array_combine($jsOptions, $jsOptions));
    // A source is entirely JS driven - no HTML.
    return '';
  }

  /**
   * A standard parameters filter toolbar for use on Elasticsearch pages.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-standardParams
   */
  public static function standardParams(array $options) {
    require_once 'prebuilt_forms/includes/report_filters.php';
    $options = array_merge(array(
      'allowSave' => TRUE,
      'sharing' => 'reporting',
      'elasticsearch' => TRUE,
    ), $options);
    foreach ($options as &$value) {
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
    $r = report_filter_panel($options['readAuth'], $options, helper_base::$website_id, $hiddenStuff);
    return $r . $hiddenStuff;
  }

  /**
   * A control for flexibly outputting data formatted using HTML templates.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-templatedOutput
   */
  public static function templatedOutput(array $options) {
    self::checkOptions('templatedOutput', $options, ['source', 'content'], []);
    $dataOptions = helper_base::getOptionsForJs($options, [
      'source',
      'content',
      'header',
      'footer',
      'repeatField',
    ], empty($options['attachToId']));
    helper_base::$javascript .= <<<JS
$('#$options[id]').idcTemplatedOutput({});

JS;
    return self::getControlContainer('templatedOutput', $options, $dataOptions);
  }

  /**
   * Retrieve parameters from the URL and add to the ES requests.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-urlParams
   *
   * @return string
   *   Hidden input HTML which defines the appropriate filters.
   */
  public static function urlParams(array $options) {
    self::checkOptions('urlParams', $options, [], ['fieldFilters']);
    $options = array_merge([
      'fieldFilters' => [
        'taxa_in_scratchpad_list_id' => [
          [
            'name' => 'taxon.higher_taxon_ids',
            'type' => 'integer',
            'process' => 'taxonIdsInScratchpad',
          ],
        ],
        // For legacy configurations.
        'taxon_scratchpad_list_id' => [
          [
            'name' => 'taxon.higher_taxon_ids',
            'type' => 'integer',
            'process' => 'taxonIdsInScratchpad',
          ],
        ],
        'sample_id' => [
          [
            'name' => 'event.event_id',
            'type' => 'integer',
          ],
        ],
        'taxa_in_sample_id' => [
          [
            // Use accepted taxon ID so this is not a hierarchical query.
            'name' => 'taxon.accepted_taxon_id',
            'type' => 'integer',
            'process' => 'taxonIdsInSample',
          ],
        ],
      ],
      // Other options, e.g. group_id or field params may be added in future.
    ], $options);
    $r = '';
    foreach ($options['fieldFilters'] as $field => $esFieldList) {
      if (!empty($_GET[$field])) {
        foreach ($esFieldList as $esField) {
          $value = trim($_GET[$field]);
          if ($esField['type'] === 'integer') {
            if (!preg_match('/^\d+$/', $value)) {
              // Disable this filter.
              $value = '-1';
              hostsite_show_message(
                "Data cannot be loaded because the value in the $field parameter is invalid",
                'warning'
              );
            }
          }
          $queryType = 'term';
          // Special processing for a taxon scratchpad ID.
          if (isset($esField['process'])) {
            if ($esField['process'] === 'taxonIdsInScratchpad') {
              $value = self::convertValueToFilterList(
                'library/taxa/external_keys_for_scratchpad',
                ['scratchpad_list_id' => $value],
                'external_key',
                $options['readAuth']
              );
            }
            elseif ($esField['process'] === 'taxonIdsInSample') {
              $value = self::convertValueToFilterList(
                'library/taxa/external_keys_for_sample',
                ['sample_id' => $value],
                'external_key',
                $options['readAuth']
              );
            }
            $queryType = 'terms';
          }
          $r .= <<<HTML
<input type="hidden" class="es-filter-param" value="$value"
  data-es-bool-clause="must" data-es-field="$esField[name]" data-es-query-type="$queryType" />

HTML;
        }
      }
    }
    return $r;
  }

  /**
   * Output a selector for a user's registered filters.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-userFilters
   */
  public static function userFilters(array $options) {
    require_once 'prebuilt_forms/includes/report_filters.php';
    self::$controlIndex++;
    $options = array_merge([
      'id' => "es-user-filter-" . self::$controlIndex,
      'definesPermissions' => FALSE,
      'sharingCode' => 'R',
    ], $options);
    $filterData = report_filters_load_existing($options['readAuth'], $options['sharingCode'], TRUE);
    $optionArr = [];
    foreach ($filterData as $filter) {
      if (($filter['defines_permissions'] === 't') === $options['definesPermissions']) {
        $optionArr[$filter['id']] = $filter['title'];
      }
    }
    if (count($optionArr) === 0) {
      // No filters available. Until we support saving, doesn't make sense to
      // show the control.
      return '';
    }
    else {
      $controlOptions = [
        'label' => $options['definesPermissions'] ? lang::get('Context') : lang::get('Filter'),
        'fieldname' => $options['id'],
        'lookupValues' => $optionArr,
        'class' => 'user-filter',
      ];
      if (!$options['definesPermissions']) {
        $controlOptions['blankText'] = '- ' . lang::get('Please select') . ' - ';
      }
      return data_entry_helper::select($controlOptions);
    }
  }

  /**
   * A panel containing buttons for record verification actions.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-verificationButtons
   *
   * @return string
   *   Panel HTML;
   */
  public static function verificationButtons(array $options) {
    self::checkOptions('verificationButtons', $options, ['showSelectedRow'], []);
    if (!empty($options['editPath'])) {
      $options['editPath'] = helper_base::getRootFolder(TRUE) . $options['editPath'];
    }
    if (!empty($options['viewPath'])) {
      $options['viewPath'] = helper_base::getRootFolder(TRUE) . $options['viewPath'];
    }
    $dataOptions = helper_base::getOptionsForJs($options, [
      'showSelectedRow',
      'editPath',
      'viewPath',
    ], TRUE);
    $userId = hostsite_get_user_field('indicia_user_id');
    $verifyUrl = iform_ajaxproxy_url($options['nid'], 'list_verify');
    $commentUrl = iform_ajaxproxy_url($options['nid'], 'occ-comment');
    helper_base::$javascript .= <<<JS
indiciaData.ajaxFormPostSingleVerify = '$verifyUrl&user_id=$userId&sharing=verification';
indiciaData.ajaxFormPostComment = '$commentUrl&user_id=$userId&sharing=verification';
$('#$options[id]').idcVerificationButtons({});

JS;
    $optionalLinkArray = [];
    if (!empty($options['editPath'])) {
      $optionalLinkArray[] = '<a class="edit" title="Edit this record"><span class="fas fa-edit"></span></a>';
    }
    if (!empty($options['viewPath'])) {
      $optionalLinkArray[] = '<a class="view" title="View this record\'s details page"><span class="fas fa-file-invoice"></span></a>';
    }
    $optionalLinks = implode("\n  ", $optionalLinkArray);
    helper_base::add_resource('fancybox');
    return <<<HTML
<div id="$options[id]" class="idc-verification-buttons" style="display: none;" data-idc-config="$dataOptions">
  <div class="selection-buttons-placeholder">
    <div class="all-selected-buttons idc-verification-buttons-row">
      Actions:
      <span class="fas fa-toggle-on toggle fa-2x" title="Toggle additional status levels"></span>
      <button class="verify l1" data-status="V" title="Accepted"><span class="far fa-check-circle status-V"></span></button>
      <button class="verify l2" data-status="V1" title="Accepted :: correct"><span class="fas fa-check-double status-V1"></span></button>
      <button class="verify l2" data-status="V2" title="Accepted :: considered correct"><span class="fas fa-check status-V2"></span></button>
      <button class="verify" data-status="C3" title="Plausible"><span class="fas fa-check-square status-C3"></span></button>
      <button class="verify l1" data-status="R" title="Not accepted"><span class="far fa-times-circle status-R"></span></button>
      <button class="verify l2" data-status="R4" title="Not accepted :: unable to verify"><span class="fas fa-times status-R4"></span></button>
      <button class="verify l2" data-status="R5" title="Not accepted :: incorrect"><span class="fas fa-times status-R5"></span></button>
      <div class="multi-only apply-to">
        <span>Apply decision to:</span>
        <button class="multi-mode-selected active">selected</button>
        |
        <button class="multi-mode-table">all</button>
      </div>
      <span class="sep"></span>
      <button class="query" data-query="Q" title="Raise a query"><span class="fas fa-question-circle query-Q"></span></button>
    </div>
  </div>
  <div class="single-record-buttons idc-verification-buttons-row">
    $optionalLinks
  </div>
</div>
HTML;
  }

  /**
   * Applies token replacements to one or more values in the $options array.
   *
   * Tokens are of the format "{{ name }}" where the token name is one of the
   * following:
   * * indicia_user_id - the user's warehouse user ID.
   * * a parameter from the URL query string.
   *
   * @param array $options
   *   Control options.
   * @param array $fields
   *   List of the fields in the options array that replacements should be
   *   applied to.
   * @param array $jsonFields
   *   Subset of $fields where the value should be a JSON object after
   *   replacements are applied.
   */
  private static function applyReplacements(array &$options, array $fields, array $jsonFields) {
    $replacements = ['{{ indicia_user_id }}' => hostsite_get_user_field('indicia_user_id')];
    foreach ($_GET as $field => $value) {
      $replacements["{{ $field }}"] = $value;
    }
    foreach ($fields as $field) {
      if (!empty($options[$field]) && is_string($options[$field])) {
        $options[$field] = str_replace(array_keys($replacements), array_values($replacements), $options[$field]);
        if (in_array($field, $jsonFields)) {
          $options[$field] = json_decode($options[$field]);
        }
      }
    }
  }

  /**
   * Provide common option handling for controls.
   *
   * * If attachToId specified, ensures that the control ID is set to the same
   *   value.
   * * Sets a unique ID for the control if not already set.
   * * Checks that required options are all populated.
   * * Checks that options which should contain JSON do so.
   * * Source option converted to array if not already.
   *
   * @param string $controlName
   *   Name of the type of control.
   * @param array $options
   *   Options passed to the control (key and value associative array). Will be
   *   modified.
   * @param array $requiredOptions
   *   Array of option names which must have a value.
   * @param array $jsonOptions
   *   Array of option names which must contain JSON.
   */
  private static function checkOptions($controlName, array &$options, array $requiredOptions, array $jsonOptions) {
    self::$controlIndex++;
    if (!empty($options['attachToId'])) {
      if (!empty($options['id']) && $options['id'] !== $options['attachToId']) {
        throw new Exception("Control ID $options[id] @attachToId does not match the @id option value.");
      }
      // If attaching to an existing element, force the ID.
      $options['id'] = $options['attachToId'];
    }
    else {
      // Otherwise, generate a unique ID if not defined.
      $options = array_merge([
        'id' => "idc-$controlName-" . self::$controlIndex,
      ], $options);
    }
    // Fail if duplicate ID on page.
    if (in_array($options['id'], self::$controlIds)) {
      throw new Exception("Control ID $options[id] is duplicated in the page configuration");
    }
    self::$controlIds[] = $options['id'];
    foreach ($requiredOptions as $option) {
      if (!isset($options[$option]) || $options[$option] === '') {
        throw new Exception("Control [$controlName] requires a parameter called @$option");
      }
    }
    foreach ($jsonOptions as $option) {
      if (!empty($options[$option]) && !is_object($options[$option]) && !is_array($options[$option])) {
        throw new Exception("@$option option for [$controlName] is not a valid JSON object.");
      }
    }
    // Source option can be either a single named source, or an array of key
    // value pairs where the key is the source name and the value is the title,
    // e.g. for a multi-layer map. So, standardise it to the array style.
    if (!empty($options['source'])) {
      $options['source'] = is_string($options['source']) ? [$options['source'] => 'Source data'] : $options['source'];
    }
  }


  /**
   * Uses an Indicia report to convert a URL param to a list of filter values.
   *
   * For example, converts a scratchpad list ID to the list of taxa in the
   * scratchpad list.
   *
   * @param string $report
   *   Report path.
   * @param array $params
   *   List of parameters to pass to the report.
   * @param string $outputField
   *   Name of the field output by the report to build the list from.
   * @param array $readAuth
   *   Read authorisation tokens.
   *
   * @return string
   *   List for placing in the url param's hidden input attribute.
   */
  private static function convertValueToFilterList($report, array $params, $outputField, array $readAuth) {
    // Load the scratchpad's list of taxa.
    iform_load_helpers(['report_helper']);
    $listEntries = report_helper::get_report_data([
      'dataSource' => $report,
      'readAuth' => $readAuth,
      'extraParams' => $params,
    ]);
    // Build a hidden input which causes filtering to this list.
    $keys = [];
    foreach ($listEntries as $row) {
      $keys[] = $row[$outputField];
    }
    return str_replace('"', '&quot;', json_encode($keys));
  }

  /**
   * Returns the HTML required to act as a control container.
   *
   * Creates the common HTML strucuture required to wrap any data output
   * control. If the control's @attachToId option is set then sets the
   * required JavaScript to make the control inject itself into the existing
   * element instead.
   *
   * @param string $controlName
   * @param array $options
   * @param string $dataOptions
   * @param string $content
   *
   * @return string
   *   Control HTML.
   */
  private static function getControlContainer($controlName, array $options, $dataOptions, $content='') {
    if (!empty($options['attachToId'])) {
      // Use JS to attach to an existing element.
      helper_base::$javascript .= <<<JS
$('#$options[attachToId]')
  .addClass('idc-output')
  .addClass("idc-output-$controlName")
  .attr('data-idc-output-config', '$dataOptions');

JS;
      return '';
    }
    return <<<HTML
<div id="$options[id]" class="idc-output idc-output-$controlName" data-idc-config="$dataOptions">
  $content
</div>

HTML;
  }

  /**
   * Converts nested mappings data from ES to a flat field list.
   *
   * ES returns the mappings for an index as a hierarchical structure
   * representing the JSON document fields. This recursive function converts
   * this structure to a flat associative array of fields and field
   * configuration where the keys are the field names with their parent
   * elements separated by periods.
   *
   * @param array $data
   *   Mappings data structure retrieved from ES.
   * @param array $mappings
   *   Array which will be populated by the field list.
   * @param array $path
   *   Array of parent fields which define the path to the current element.
   */
  private static function recurseMappings(array $data, array &$mappings, array $path = []) {
    foreach ($data as $field => $config) {
      $thisPath = array_merge($path, [$field]);
      if (isset($config['properties'])) {
        self::recurseMappings($config['properties'], $mappings, $thisPath);
      }
      else {
        $field = implode('.', $thisPath);
        $mappings[$field] = [
          'type' => $config['type'],
        ];
        // We can't sort on text unless a keyword is specified.
        if (isset($config['fields']) && isset($config['fields']['keyword'])) {
          $mappings[$field]['sort_field'] = "$field.keyword";
        }
        elseif ($config['type'] !== 'text') {
          $mappings[$field]['sort_field'] = $field;
        }
        else {
          // Disable sorting.
          $mappings[$field]['sort_field'] = FALSE;
        }
      }
    }
  }

  /* Retrieves the ES index mappings data.
   *
   * A list of mapped fields is stored in self::$esMappings.
   *
   * @param int $nid
   *   Node ID, used to retrieve the node parameters which contain ES settings.
   */
  private static function getMappings($nid) {
    $config = hostsite_get_es_config($nid);
    // /doc added to URL only for Elasticsearch 6.x.
    $url = $config['indicia']['base_url'] . 'index.php/services/rest/' . $config['es']['endpoint'] . '/_mapping' .
      ($config['es']['version'] == 6 ? '/doc' : '');
    $session = curl_init($url);
    curl_setopt($session, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json',
      'Authorization: USER:' . $config['es']['user'] . ':SECRET:' . $config['es']['secret'],
    ]);
    curl_setopt($session, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
    curl_setopt($session, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($session, CURLOPT_HEADER, FALSE);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
    // Do the POST and then close the session.
    $response = curl_exec($session);
    $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
    if ($httpCode !== 200) {
      $error = json_decode($response, TRUE);
      $msg = !empty($error['message'])
        ? $error['message']
        : curl_errno($session) . ': ' . curl_error($session);
      throw new Exception(lang::get('An error occurred whilst connecting to Elasticsearch. {1}', $msg));

    }
    curl_close($session);
    $mappingData = json_decode($response, TRUE);
    $mappingData = array_pop($mappingData);
    $mappings = [];
    // ES 6.x has a type (doc) within the index, ES 7.x doesn't support this.
    $props = $config['es']['version'] == 6
      ? $mappingData['mappings']['doc']['properties']
      : $mappingData['mappings']['properties'];
    self::recurseMappings($props, $mappings);
    self::$esMappings = $mappings;
  }

}
