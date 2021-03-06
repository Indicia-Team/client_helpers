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
   * @var int
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
   * Has the ES proxy been setup on this page?
   *
   * @var bool
   *   Set to true when done to prevent double-initialisation.
   */
  private static $proxyEnabled = false;

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
    'metadata.created_on' => [
      'caption' => 'Submitted on',
      'description' => 'Date the record was submitted.',
    ],
    '#event_date#' => [
      'caption' => 'Date',
      'description' => 'Date of the record.',
    ],
    'event.day_of_year' => [
      'caption' => 'Day of year',
      'description' => 'Numeric day within the year of the record (1-366).',
    ],
    'event.month' => [
      'caption' => 'Month',
      'description' => 'Numeric month of the record.',
    ],
    'event.year' => [
      'caption' => 'Year',
      'description' => 'Year of the record.',
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
    'taxon.taxon_name' => [
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
    '#taxon_label#' => [
      'caption' => 'Taxon label',
      'description' => 'Combination of accepted and common name.',
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
    '#occurrence_media#' => [
      'caption' => 'Media',
      'description' => 'Thumbnails for any occurrence photos and other media.',
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
    'occurrence.occurrence_remarks' => [
      'caption' => 'Occurrence comment',
      'description' => 'Comment given for the occurrence by the recorder.',
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
    if (!self::$proxyEnabled) {
      helper_base::add_resource('datacomponents');
      // Retrieve the Elasticsearch mappings.
      self::getMappings($nid);
      // Prepare the stuff we need to pass to the JavaScript.
      $mappings = self::$esMappings;
      $rootFolder = helper_base::getRootFolder(TRUE);
      $esProxyAjaxUrl = hostsite_get_url('iform/esproxy');
      helper_base::$indiciaData['esProxyAjaxUrl'] = $esProxyAjaxUrl;
      helper_base::$indiciaData['esSources'] = [];
      helper_base::$indiciaData['esMappings'] = $mappings;
      helper_base::$indiciaData['rootFolder'] = $rootFolder;
      helper_base::$indiciaData['currentLanguage'] = hostsite_get_user_field('language');
      helper_base::$indiciaData['gridMappingFields'] = self::MAPPING_FIELDS;
      $config = hostsite_get_es_config($nid);
      helper_base::$indiciaData['esVersion'] = (int) $config['es']['version'];
      self::$proxyEnabled = TRUE;
    }
  }

  /**
   * An Elasticsearch records card gallery.
   *
   * @return string
   *   Gallery container HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-cardgallery
   */
  public static function cardGallery(array $options) {
    self::checkOptions(
      'cardGallery',
      $options,
      ['source'],
      [
        'actions',
        'columns',
        'rowsPerPageOptions',
      ]
    );
    $dataOptions = helper_base::getOptionsForJs($options, [
      'actions',
      'columns',
      'includeFieldCaptions',
      'includeFullScreenTool',
      'includePager',
      'keyboardNavigation',
      'rowsPerPageOptions',
      'source',
    ], TRUE);
    return self::getControlContainer('cardGallery', $options, $dataOptions);
  }

  /**
   * A control for flexibly outputting data formatted using a custom script.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-customScript
   */
  public static function customScript(array $options) {
    self::checkOptions('customScript', $options, ['source', 'functionName'], []);
    $options = array_merge([
      'template' => '',
    ], $options);
    $dataOptions = helper_base::getOptionsForJs($options, [
      'source',
      'functionName',
    ], TRUE);
    return self::getControlContainer('customScript', $options, $dataOptions, $options['template']);
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
      [
        'actions',
        'applyFilterRowToSources',
        'availableColumns',
        'columns',
        'responsiveOptions',
        'rowClasses',
        'rowsPerPageOptions',
      ]
    );
    if (!empty($options['scrollY']) && !preg_match('/^-?\d+px$/', $options['scrollY'])) {
      throw new Exception('Control [dataGrid] @scrollY parameter must be of CSS pixel format, e.g. 100px');
    }
    if (isset($options['columns'])) {
      foreach ($options['columns'] as &$columnDef) {
        if (empty($columnDef['field'])) {
          throw new Exception('Control [dataGrid] @columns option does not contain a field for every item.');
        }
        if (!isset($columnDef['caption'])) {
          $columnDef['caption'] = '';
        }
        // To aid transition from older code versions, auto-enable the media
        // special field handling. This may be removed in future.
        if ($columnDef['field'] === 'occurrence.media') {
          $columnDef['field'] = '#occurrence_media#';
        }
      }
    }
    helper_base::add_resource('sortable');
    helper_base::add_resource('indiciaFootableReport');
    // Add footableSort for simple aggregation tables.
    if (!empty($options['aggregation']) && $options['aggregation'] === 'simple') {
      helper_base::add_resource('footableSort');
    }
    // Fancybox for image popups.
    helper_base::add_resource('fancybox');
    $dataOptions = helper_base::getOptionsForJs($options, [
      'actions',
      'applyFilterRowToSources',
      'availableColumns',
      'autoResponsiveCols',
      'autoResponsiveExpand',
      'columns',
      'cookies',
      'includeColumnHeadings',
      'includeColumnSettingsTool',
      'includeFilterRow',
      'includeFullScreenTool',
      'includePager',
      'includeMultiSelectTool',
      'keyboardNavigation',
      'responsive',
      'responsiveOptions',
      'rowClasses',
      'rowsPerPageOptions',
      'scrollY',
      'source',
      'sortable',
    ], TRUE);
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
      [],
      ['addColumns', 'removeColumns']
    );
    if (empty($options['source']) && empty($options['linkToDataGrid'])) {
      throw new Exception('Download control requires a value for either the @source or @linkToDataGrid option.');
    }
    if (!empty($options['source']) && !empty($options['linkToDataGrid'])) {
      throw new Exception('Download control requires only one of the @source or @linkToDataGrid options to be specified.');
    }
    if (empty($options['source']) && !empty($options['columnsTemplate'])) {
      throw new Exception('Download control @source option must be specified if @columnsTemplate option is used (cannot be used with @linkToDataGrid).');
    }

    $options = array_merge([
      'caption' => 'Download',
      'title' => 'Run the download',
    ], $options);

    // If columnsTemplate options specifies an array, then create control options for
    // a select control that will be used to indicate the selected columns template.
    if (!empty($options['columnsTemplate']) && is_array($options['columnsTemplate'])) {
      $availableColTypes = array(
        "easy-download" => lang::get("Standard download format"),
        "mapmate" => lang::get("Simple download format"),
      );
      $optionArr = array();
      foreach ($options['columnsTemplate'] as $colType) {
        $optionArr[$colType] = $availableColTypes[$colType];
      }
      $controlOptions = [
        'id' => "$options[id]-template",
        'fieldname' => 'columnsTemplate',
        'lookupValues' => $optionArr,
      ];
      unset($options['columnsTemplate']);
    }

    global $indicia_templates;
    $button = str_replace(
      [
        '{id}',
        '{title}',
        '{class}',
        '{caption}',
      ], [
        "$options[id]-button",
        lang::get($options['title']),
        "class=\"$indicia_templates[buttonHighlightedClass] do-download\"",
        lang::get($options['caption']),
      ],
      $indicia_templates['button']
    );
    if (isset($controlOptions)) {
      $html = "<div class='idc-download-ctl-part'>".$button."</div>";
      $html .= "<div class='idc-download-ctl-part'>".data_entry_helper::select($controlOptions)."</div>";
    } else {
      $html = $button;
    }
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
    $html .= str_replace(
      [
        '{attrs}',
        '{col-1}',
        '{col-2}',
      ], [
        '',
        $progress,
        '<div class="idc-download-files"><h2>' . lang::get('Files') . ':</h2></div>',
      ],
      $indicia_templates['two-col-50']);
    // This does nothing at the moment - just a placeholder for if and when we
    // add some download options.
    $dataOptions = helper_base::getOptionsForJs($options, [
      'addColumns',
      'aggregation',
      'buttonContainerElement',
      'columnsTemplate',
      'columnsSurveyId',
      'linkToDataGrid',
      'removeColumns',
      'source',
    ], TRUE);
    return self::getControlContainer('esDownload', $options, $dataOptions, $html);
  }

  /**
   * Integrates the page with groups (activities).
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-groupintegration
   *
   * @return string
   *   Control HTML
   */
  public static function groupIntegration(array $options) {
    $options = array_merge([
      'missingGroupIdBehaviour' => 'error',
      'showGroupSummary' => FALSE,
      'showGroupPages' => FALSE,
    ], $options);
    if (isset($options['group_id'])) {
      $group_id = $options['group_id'];
      $implicit = isset($options['implicit']) ? $options['implicit'] : FALSE;
    }
    elseif (!empty($_GET['group_id'])) {
      $group_id = $_GET['group_id'];
      $implicit = isset($_GET['implicit']) ? $_GET['implicit'] : 'f';
    }
    if (empty($group_id) && $options['missingGroupIdBehaviour'] !== 'showAll') {
      hostsite_show_message(lang::get('The link you have followed is invalid.'), 'warning', TRUE);
      hostsite_goto_page('<front>');
    }
    require_once 'prebuilt_forms/includes/groups.php';
    $member = group_authorise_group_id($group_id, $options['readAuth']);
    $output = '';
    if (!empty($group_id)) {
      // Apply filtering by group.
      helper_base::$indiciaData['filter_group_id'] = $group_id;
      $implicitVal = ['f' => FALSE, 't' => TRUE, '' => NULL][$implicit];
      helper_base::$indiciaData['filter_group_implicit'] = $implicitVal;
      if ($options['showGroupSummary'] || $options['showGroupPages']) {
        $groups = data_entry_helper::get_population_data(array(
          'table' => 'group',
          'extraParams' => $options['readAuth'] + [
            'view' => 'detail',
            'id' => $group_id,
          ]
        ));
        if (!count($groups)) {
          hostsite_show_message(lang::get('The link you have followed is invalid.'), 'warning', TRUE);
          hostsite_goto_page('<front>');
        }
        $group = $groups[0];
        if ($options['showGroupSummary']) {
          $output .= self::getGroupSummaryHtml($group);
        }
        if ($options['showGroupPages']) {
          $output .= self::getGroupPageLinks($group, $options, $member);
        }
      }
    }
    $filterBoundaries = helper_base::get_population_data([
      'report' => 'library/groups/group_boundary_transformed',
      'extraParams' => $options['readAuth'] + ['group_id' => $_GET['group_id']],
      'cachePerUser' => FALSE,
    ]);
    if (count($filterBoundaries) > 0) {
      helper_base::$indiciaData['reportBoundaries'] = [];
      foreach ($filterBoundaries as $boundary) {
        helper_base::$indiciaData['reportBoundaries'][] = $boundary['boundary'];
      }
      helper_base::$late_javascript .= <<<JS
indiciaFns.loadReportBoundaries();

JS;
    }
    return $output;
  }

  /**
   * Return the HTML for a summary panel for a group.
   *
   * @param array $group
   *   Group data loaded from the database.
   *
   * @return string
   *   HTML for the panel.
   */
  public static function getGroupSummaryHtml(array $group) {
    $path = data_entry_helper::get_uploaded_image_folder();
    $logo = empty($group['logo_path']) ? '' : "<img style=\"width: 30%; float: left; padding: 0 5% 5%;\" alt=\"Logo\" src=\"$path$group[logo_path]\"/>";
    $msg = "<h3>$group[title]</h3>";
    if (!empty($group['description'])) {
      $msg .= "<p>$group[description]</p>";
    }
    return $logo . $msg;
  }

  /**
   * Return the HTML for a list of page links for a group.
   *
   * @param array $group
   *   Group data loaded from the database.
   * @param array $options
   *   [groupIntegration] control options.
   * @param bool $member
   *   True if member of the group.
   *
   * @return string
   *   HTML for the list of links.
   */
  public static function getGroupPageLinks(array $group, array $options, $member) {
    $pageData = data_entry_helper::get_population_data(array(
      'table'=>'group_page',
      'extraParams' => $options['readAuth'] + array(
          'group_id' => $group['id'],
          'query' => json_encode(array('in'=>array('administrator'=>array('', 'f')))),
          'orderby' => 'caption'
        )
    ));
    $pageLinks = [];
    $thisPage = empty($options['nid']) ? '' : hostsite_get_alias($options['nid']);;
    foreach ($pageData as $page) {
      // Don't link to the current page, plus block member-only pages for
      // non-members.
      if ($page['path'] !== $thisPage && ($member || $page['administrator'] === NULL)) {
        $pageLinks[] = '<li><a href="' .
          hostsite_get_url($page['path'], array('group_id'=>$group['id'], 'implicit'=>$group['implicit_record_inclusion'])) .
          '">' . lang::get($page['caption']) . '</a></li>';
      }
    }
    if (!empty($pageLinks)) {
      return '<ul>' . implode('', $pageLinks) . '</ul>';
    }
    return '';
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
    self::checkOptions('leafletMap', $options,
      ['layerConfig'],
      ['baseLayerConfig', 'layerConfig', 'selectedFeatureStyle']
    );
    $options = array_merge([
      'initialLat' => hostsite_get_config_value('iform', 'map_centroid_lat', 54.093409),
      'initialLng' => hostsite_get_config_value('iform', 'map_centroid_long', -2.89479),
      'initialZoom' => hostsite_get_config_value('iform', 'map_zoom', 5),
    ], $options);
    helper_base::add_resource('leaflet');
    if (isset($options['baseLayerConfig'])) {
      foreach ($options['baseLayerConfig'] as $baseLayer) {
        if ($baseLayer['type'] === 'Google') {
          helper_base::add_resource('leaflet_google');
        }
      }
    }
    $dataOptions = helper_base::getOptionsForJs($options, [
      'baseLayerConfig',
      'cookies',
      'initialLat',
      'initialLng',
      'initialZoom',
      'layerConfig',
      'selectedFeatureStyle',
      'showSelectedRow',
    ], TRUE);
    // Extra setup required after map loads.
    helper_base::$late_javascript .= <<<JS
$('#$options[id]').idcLeafletMap('bindRecordListControls');

JS;
    return self::getControlContainer('leafletMap', $options, $dataOptions);
  }

  /**
   * Output a selector for a survey.
   *
   * @return string
   *   Select HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-surveyFilter
   */
  public static function surveyFilter(array $options) {

    $options = array_merge([
      'label' => lang::get('Limit to survey'),
    ], $options);

    $controlOptions = [
      'label' => $options['label'],
      'fieldname' => 'es-survey-filter',
      'class' => 'es-filter-param survey-filter',
      'attributes' => array (
        'data-es-bool-clause' => 'must',
        'data-es-query' => '{&quot;term&quot;: {&quot;metadata.survey.id&quot;: #value#}}',
        'data-es-summary' => 'limit to records in survey: #value#'
      ),
      'table' => 'survey',
      'valueField' => 'id',
      'captionField' => 'title',
      'extraParams' => $options['readAuth'] + array(
        'orderby' => 'title',
        'sharing' => 'data_flow'
      ),
      'blankText' => '- Please select -',
    ];

    return data_entry_helper::select($controlOptions);
  }

  /**
   * Finds a list of the options available for permission filters.
   *
   * Can be used to populate the [permissionFilters] control or for the proxy
   * to verify that a requested permissions filter is authorised.
   *
   * @param array $options
   *   Options for the [permissionFilters] control.
   *
   * @return array
   *   Associative array of options.
   */
  public static function getPermissionFiltersOptions(array $options) {
    require_once 'prebuilt_forms/includes/report_filters.php';
    $options = array_merge([
      'includeFiltersForGroups' => FALSE,
      'includeFiltersForSharingCodes' => [],
    ], $options);
    $optionArr = [];
    // Add My records download permission if allowed.
    if (!empty($options['my_records_permission']) && hostsite_user_has_permission($options['my_records_permission'])) {
      $optionArr['p-my'] = lang::get('My records');
    }
    // Add All records if website permission allows.
    if (!empty($options['all_records_permission']) && hostsite_user_has_permission($options['all_records_permission'])) {
      $optionArr['p-all'] = lang::get('All records');
    }
    // Add collated location (e.g. LRC boundary) records if website
    // permissions allow.
    if (!empty($options['location_collation_records_permission'])
        && hostsite_user_has_permission($options['location_collation_records_permission'])) {
      $locationId = hostsite_get_user_field('location_collation');
      if ($locationId) {
        $locationData = data_entry_helper::get_population_data([
          'table' => 'location',
          'extraParams' => $options['readAuth'] + ['id' => $locationId],
        ]);
        if (count($locationData) > 0) {
          $optionArr['p-location_collation'] = lang::get('Records within location ' . $locationData[0]['name']);
        }
      }
    }

    // Add in permission filters.
    // Find allowed values only.
    $sharingCodes = array_intersect(
      $options['includeFiltersForSharingCodes'],
      ['R', 'V', 'D', 'M', 'P']
    );
    $sharingTypes = [
      'R' => lang::get('Reporting'),
      'V' => lang::get('Verification'),
      'D' => lang::get('Data-flow'),
      'M' => lang::get('Moderation'),
      'P' => lang::get('Peer review'),
    ];
    foreach ($sharingCodes as $sharingCode) {
      $filterData = report_filters_load_existing($options['readAuth'], $sharingCode, TRUE);
      foreach ($filterData as $filter) {
        if ($filter['defines_permissions'] === 't') {
          // If useSharingPrefix options specified, prefix type of sharing to
          // filter name.
          $filterTitle = $options['useSharingPrefix']
            ? $sharingTypes[$sharingCode] . ' - ' . $filter['title']
            : $filter['title'];
          $optionArr["f-$filter[id]"] = $filterTitle;
        }
      }
    }

    if ($options['includeFiltersForGroups']) {
      // Groups integration if user linked to warehouse.
      $params = [
        'user_id' => hostsite_get_user_field('indicia_user_id'),
        'view' => 'detail',
      ];

      if ($params['user_id']) {
        $groups = helper_base::get_population_data(array(
          'table' => 'groups_user',
          'extraParams' => helper_base::$js_read_tokens + $params,
        ));
        foreach ($groups as $group) {
          $title = $group['group_title'] . (isset($group['group_expired']) && $group['group_expired'] === 't' ?
              ' (' . lang::get('finished') . ')' : '');
          if ($group['administrator'] === 't') {
            $optionArr["g-all-$group[group_id]"] = lang::get('All records added using a recording form for {1}', $title);
          }
          $optionArr["g-my-$group[group_id]"] = lang::get('My records added using a recording form for {1}', $title);
        }
      }
    }

    return $optionArr;
  }

  /**
   * Output a selector for sets of records defined by a permission.
   *
   * Allows user to select from:
   * * All records (if permission is set)
   * * My records
   * * Permission filters
   * * Groups.
   *
   * @todo Allow hide if only one option.
   *
   * @return string
   *   Select HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-permissionFilters
   */
  public static function permissionFilters(array $options) {
    $wrapperOptions = array_merge([
      'id' => "es-permissions-filter-wrapper",
    ], $options);

    $options = array_merge([
      'id' => "es-permissions-filter",
      'useSharingPrefix' => TRUE,
      'label' => lang::get('Records to access'),
      'notices' => '[]',
    ], $options);

    $optionArr = self::getPermissionFiltersOptions($options);
    // Return the select control. There will always be at least one option (my
    // records).
    $controlOptions = [
      'label' => lang::get($options['label']),
      'fieldname' => $options['id'],
      'lookupValues' => $optionArr,
      'class' => 'permissions-filter',
    ];

    $dropdown = data_entry_helper::select($controlOptions);
    $html = <<<HTML
<div>
  $dropdown
  <div id="permission-filters-notice"></div>
</div>

HTML;

    $dataOptions = helper_base::getOptionsForJs($options, ['notices'], TRUE);
    return self::getControlContainer('permissionFilters', $wrapperOptions, $dataOptions, $html);
  }

  /**
   * Output a summary of currently applied filters.
   *
   * @return string
   *   HTML summary text.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-filterSummary
   */
  public static function filterSummary(array $options) {

    require_once 'prebuilt_forms/includes/report_filters.php';
    report_filters_set_parser_language_strings();
    $options = array_merge([
      'id' => 'es-filter-summary',
      'label' => 'Filter summary',
    ], $options);

    $html = <<<HTML
<div>
  <h3>$options[label]</h3>
  <div class="filter-summary-contents"></div>
</div>

HTML;

    helper_base::$late_javascript .= <<<JS
$('#es-filter-summary').idcFilterSummary('populate');
$('.es-filter-param, .user-filter, .permissions-filter, .standalone-quality-filter select').change(function () {
    // Update any summary output
    $('#es-filter-summary').idcFilterSummary('populate');
});

JS;

    return self::getControlContainer('filterSummary', $options, json_encode([]), $html);
  }

  /**
   * Output a selector for record status.
   *
   * Mirrors the 'quality - records to include' drop-down in standardParams control.
   *
   * @return string
   *   Select HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-statusFilters
   */
  public static function statusFilters(array $options) {
    require_once 'prebuilt_forms/includes/report_filters.php';
    $options = array_merge([
      'sharing' => 'reporting',
      'elasticsearch' => TRUE,
    ], $options);

    return status_control($options['readAuth'], $options);
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
      'allowRedetermination',
      'exploreUrl',
      'extraLocationTypes',
      'locationTypes',
      'showSelectedRow',
    ], TRUE);
    helper_base::add_resource('tabs');
    helper_base::$late_javascript .= <<<JS
$('#$options[id]').idcRecordDetailsPane();

JS;
    $r = <<<HTML
<div class="record-details-container" id="$options[id]" data-idc-config="$dataOptions">
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
    self::applyReplacements(
      $options,
      ['aggregation', 'sortAggregation'],
      ['aggregation', 'sortAggregation']
    );
    self::checkOptions(
      'source',
      $options,
      ['id'],
      [
        'aggregation',
        'fields',
        'filterBoolClauses',
        'sort',
        'sortAggregation',
      ]
    );
    // Temporary support for deprecated @aggregationMapMode option. Will be
    // removed in a future version.
    if (empty($options['mode'])) {
      if (!empty($options['aggregationMapMode'])) {
        $options['mode'] = 'map' . ucfirst($options['aggregationMapMode']);
      }
      elseif (!empty($options['aggregation']) && !empty($options['filterBoundsUsingMap'])) {
        // Default aggregation mode to mapGeoHash (for legacy support till
        // deprecated code removed).
        $options['mode'] = 'mapGeoHash';
      }
    }
    $options = array_merge([
      'mode' => 'docs',
    ], $options);
    self::applySourceModeDefaults($options);
    $jsOptions = [
      'aggregation',
      'fields',
      'filterBoolClauses',
      'filterBoundsUsingMap',
      'filterField',
      'filterPath',
      'filterSourceField',
      'filterSourceGrid',
      'from',
      'id',
      'initialMapBounds',
      'mapGridSquareSize',
      'mode',
      'proxyCacheTimeout',
      'sortAggregation',
      'size',
      'sort',
      'switchToGeomsAt',
      'uniqueField',
      'disabled',
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
    $options = array_merge([
      'allowSave' => TRUE,
      'sharing' => 'reporting',
      'elasticsearch' => TRUE,
    ], $options);
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
    ], TRUE);
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
    $options = array_merge([
      'label' => $options['definesPermissions'] ? lang::get('Context') : lang::get('Filter'),
    ], $options);

    // Sharing code can be specified as a comma separated list of codes.
    $sharingCodes = explode(',', $options['sharingCode']);
    $optionArr = [];
    foreach ($sharingCodes as $sharingCode) {
      $filterData = report_filters_load_existing($options['readAuth'], $sharingCode, TRUE);
      foreach ($filterData as $filter) {
        if (($filter['defines_permissions'] === 't') === $options['definesPermissions']) {
          $optionArr[$filter['id']] = $filter['title'];
        }
      }
    }

    if (count($optionArr) === 0) {
      // No filters available. Until we support saving, doesn't make sense to
      // show the control.
      return '';
    }
    else {
      $classes = ['user-filter'];
      if ($options['definesPermissions']) {
        $classes[] = 'defines-permissions';
      }
      $controlOptions = [
        'label' => $options['label'],
        'fieldname' => $options['id'],
        'lookupValues' => $optionArr,
        'class' => implode(' ', $classes),
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
    global $indicia_templates;
    if (!empty($options['includeUploadButton'])) {
      $config = hostsite_get_es_config($nid);
      helper_base::$indiciaData['esEndpoint'] = $config['es']['endpoint'];
      helper_base::$indiciaData['idPrefix'] = $config['es']['warehouse_prefix'];
    }
    self::checkOptions('verificationButtons', $options, ['showSelectedRow'], []);
    $options = array_merge([
      'editPath' => helper_base::getRootFolder(TRUE) . $options['editPath'],
      'taxon_list_id' => hostsite_get_config_value('iform', 'master_checklist_id'),
      'viewPath' => helper_base::getRootFolder(TRUE) . $options['viewPath'],
    ], $options);
    $dataOptions = helper_base::getOptionsForJs($options, [
      'editPath',
      'keyboardNavigation',
      'showSelectedRow',
      'uploadButtonContainerElement',
      'viewPath',
    ], TRUE);
    $userId = hostsite_get_user_field('indicia_user_id');
    $verifyUrl = iform_ajaxproxy_url($options['nid'], 'list_verify');
    $commentUrl = iform_ajaxproxy_url($options['nid'], 'occ-comment');
    $quickReplyPageAuthUrl = iform_ajaxproxy_url($options['nid'], 'comment_quick_reply_page_auth');
    $siteEmail = hostsite_get_config_value('site', 'mail', '');
    helper_base::$indiciaData['ajaxFormPostSingleVerify'] = "$verifyUrl&user_id=$userId&sharing=verification";
    helper_base::$indiciaData['ajaxFormPostComment'] = "$commentUrl&user_id=$userId&sharing=verification";
    helper_base::$indiciaData['ajaxFormPostQuickReplyPageAuth'] = $quickReplyPageAuthUrl;
    helper_base::$indiciaData['siteEmail'] = $siteEmail;
    helper_base::$javascript .= "$('#$options[id]').idcVerificationButtons({});\n";
    if (isset($options['enableWorkflow']) && $options['enableWorkflow']) {
      iform_load_helpers(['VerificationHelper']);
      VerificationHelper::fetchTaxaWithLoggedCommunications($options['readAuth']);
    }
    $optionalLinkArray = [];
    if (!empty($options['editPath'])) {
      $optionalLinkArray[] = '<a class="edit" title="Edit this record"><span class="fas fa-edit"></span></a>';
    }
    $optionalLinkArray[] = '<button class="redet" title="Redetermine this record"><span class="fas fa-tag"></span></button>';
    if (!empty($options['viewPath'])) {
      $optionalLinkArray[] = '<a class="view" title="View this record\'s details page"><span class="fas fa-file-invoice"></span></a>';
    }
    $optionalLinks = implode("\n  ", $optionalLinkArray);
    helper_base::add_resource('fancybox');
    helper_base::add_resource('validation');
    helper_base::addLanguageStringsToJs('verificationButtons', [
      'commentTabTitle' => 'Comment on the record',
      'elasticsearchUpdateError' => 'An error occurred whilst updating the reporting index. It may not reflect your changes temporarily but will be updated automatically later.',
      'commentReplyInstruct' => 'Click here to add a publicly visible comment to the record on iRecord.',
      'csvDisallowedMessage' => 'Uploading verification decisions is only allowed when there is a filter that defines the scope of the records you can verify.',
      'emailLoggedAsComment' => 'I emailed this record to the recorder for checking.',
      'emailQueryBodyHeader' => 'The following record requires confirmation. Please could you reply to this email ' .
        'stating how confident you are that the record is correct and any other information you have which may help ' .
        'to confirm this. You can reply to this message and it will be forwarded direct to the verifier.',
      'emailQuerySubject' => 'Record of {{ taxon.taxon_name }} requires confirmation (ID:{{ id }})',
      'emailReplyInstruct' => "Click on your email's reply button to send an email direct to the verifier.",
      'emailSent' => 'The email was sent successfully.',
      'emailTabTitle' => 'Email record details',
      'nothingSelected' => 'There are no selected records. Either select some rows using the checkboxes in the leftmost column or set the "Apply decision to" mode to "all".',
      'queryEmailTabAnonWithEmail' => 'This record was posted by a recorder who was not logged in but provided their email address so email is the best method of contact.',
      'queryEmailTabAnonWithoutEmail' => 'As this record does not have an email address for the recorder, the query is best added as a comment to the record unless you know the recorder and have their email address and permission to use it. There is no guarantee that the recorder will check their notifications.',
      'queryEmailTabUserIsNotified' => 'Although you can email this recorder directly, they check their notifications so adding a comment should be sufficient.',
      'queryEmailTabUserIsNotNotified' => 'Sending the query as an email is likely to be the best method of contact as the recorder does not check their notifications.',
      'queryCommentTabAnonWithEmail' => 'This record was posted by a recorder who was not logged in but provided their email address. You can add a comment using this tab but they are unlikely to see it so email is likely to be the best method of contact.',
      'queryCommentTabAnonWithoutEmail' => 'As this record does not have an email address for the recorder, the query can be added to the record as a comment. The query can only be sent to the recorder if you know their email address and have permission to use it.',
      'queryCommentTabUserIsNotified' => 'Adding your query as a comment should be OK as this recorder normally checks their notifications.',
      'queryCommentTabUserIsNotNotified' => 'Although you can add a comment, sending the query as an email is preferred as the recorder does not check their notifications.',
      'queryInMultiselectMode' => 'As you are in multi-select mode, email facilities cannot be used and queries can only be added as comments to the record.',
      'redetPartialListInfo' => 'This record was originally input using a taxon checklist which may not be a complete list of all species. If you cannot find the species you wish to redetermine it to using the search box below, then please tick the "Search all species" checkbox and try again.',
      'requestManualEmail' => 'The webserver is not correctly configured to send emails. Please send the following email usual your email client:',
      'saveQueryToComments' => 'Save query to comments log',
      'sendQueryAsEmail' => 'Send query as email',
      'uploadError' => 'An error occurred whilst uploading your spreadsheet.',
    ]);
    if (empty($options['taxon_list_id'])) {
      throw new Exception('[verificationButtons] requires a @taxon_list_id option, or the Indicia setting Master Checklist ID to be set. This ' .
        'is required to provide a list to select the redetermination from.');
    }
    $redetUrl = iform_ajaxproxy_url(NULL, 'occurrence');
    $userId = hostsite_get_user_field('indicia_user_id');
    helper_base::$indiciaData['ajaxFormPostRedet'] = "$redetUrl&user_id=$userId&sharing=editing";
    $speciesInput = data_entry_helper::species_autocomplete([
      'label' => lang::get('Redetermine to'),
      'helpText' => lang::get('Select the new taxon name.'),
      'fieldname' => 'redet-species',
      // Default to the master list, but can switch if taxon from a different
      // list.
      'extraParams' => $options['readAuth'] + ['taxon_list_id' => $options['taxon_list_id']],
      'speciesIncludeAuthorities' => TRUE,
      'speciesIncludeBothNames' => TRUE,
      'speciesNameFilterMode' => 'all',
      'validation' => ['required'],
      'class' => 'control-width-5',
    ]);
    $altListCheckbox = data_entry_helper::checkbox([
      'fieldname' => 'redet-from-full-list',
      'label' => lang::get('Search all species'),
      'labelClass' => 'auto',
      'helpText' => lang::get('This record was identified against a restricted list of taxa. Check this box if ' .
          'you want to redetermine to a taxon selected from the unrestricted full list available.'),
      'wrapClasses' => ['alt-taxon-list-controls'],
    ]);
    // Remember which is the master list.
    helper_base::$indiciaData['mainTaxonListId'] = $options['taxon_list_id'];
    $commentInput = data_entry_helper::textarea([
      'label' => lang::get('Explanation comment'),
      'helpText' => lang::get('Please give reasons why you are changing this record.'),
      'fieldname' => 'redet-comment',
      'class' => 'control-width-5',
    ]);
    $btnClass = $indicia_templates['buttonDefaultClass'];
    $uploadButton = empty($options['includeUploadButton']) ? '' : <<<HTML
      <button class="upload-decisions $btnClass" title="Upload a CSV file of verification decisions"><span class="fas fa-file-upload"></span></button>
HTML;
    $r = <<<HTML
<div id="$options[id]" class="idc-verification-buttons" style="display: none;" data-idc-config="$dataOptions">
  <div class="selection-buttons-placeholder">
    <div class="all-selected-buttons idc-verification-buttons-row">
      Actions:
      <span class="fas fa-toggle-on toggle fa-2x" title="Toggle additional status levels"></span>
      <button class="verify l1 $btnClass" data-status="V" title="Accepted"><span class="far fa-check-circle status-V"></span></button>
      <button class="verify l2 $btnClass" data-status="V1" title="Accepted :: correct"><span class="fas fa-check-double status-V1"></span></button>
      <button class="verify l2 $btnClass" data-status="V2" title="Accepted :: considered correct"><span class="fas fa-check status-V2"></span></button>
      <button class="verify $btnClass" data-status="C3" title="Plausible"><span class="fas fa-check-square status-C3"></span></button>
      <button class="verify l1 $btnClass" data-status="R" title="Not accepted"><span class="far fa-times-circle status-R"></span></button>
      <button class="verify l2 $btnClass" data-status="R4" title="Not accepted :: unable to verify"><span class="fas fa-times status-R4"></span></button>
      <button class="verify l2 $btnClass" data-status="R5" title="Not accepted :: incorrect"><span class="fas fa-times status-R5"></span></button>
      <div class="multi-only apply-to">
        <span>Apply decision to:</span>
        <button class="multi-mode-selected active $btnClass">selected</button>
        |
        <button class="multi-mode-table $btnClass">all</button>
      </div>
      <span class="sep"></span>
      <button class="query $btnClass" data-query="Q" title="Raise a query"><span class="fas fa-question-circle query-Q"></span></button>
      $uploadButton
    </div>
  </div>
  <div class="single-record-buttons idc-verification-buttons-row">
    $optionalLinks
  </div>
</div>
<div id="redet-panel-wrap" style="display: none">
  <form id="redet-form" class="verification-popup">
    <div class="alt-taxon-list-controls alt-taxon-list-message">$indicia_templates[messageBox]</div>
    $speciesInput
    $altListCheckbox
    $commentInput
    <button type="submit" class="btn btn-primary" id="apply-redet">Apply redetermination</button>
    <button type="button" class="btn btn-danger" id="cancel-redet">Cancel</button>
  </form>
</div>
HTML;
    if (!empty($options['includeUploadButton'])) {
      $instruct = <<<TXT
This form can be used to upload a spreadsheet of verification decisions. First, use the
Download button to obtain a list of the records in your current verification grid. This has columns
called *Decision status* and *Decision comment*. For rows you wish to verify, enter one of the
status terms in the *Decision status* column and optionally fill in the *Decision comment*. Any
comments without an associated status will be attached to the record without changing the status.
When ready, save the spreadsheet and upload it using this tool. Valid status terms include:
<ul>
  <li>Accepted</li>
  <li>Accepted as correct</li>
  <li>Accepted as considered correct</li>
  <li>Plausible</li>
  <li>Not accepted</li>
  <li>Not accepted as unable to verify</li>
  <li>Not accepted as incorrect</li>
  <li>Queried</li>
</ul>
TXT;
      $instruct = lang::get($instruct);
      $r .= <<<HTML
<div id="upload-decisions-form-cntr" style="display: none">
  <div id="upload-decisions-form">
    <div class="instruct">$instruct</div>
    <div class="form-group">
      <label for="decisions-file">Excel or CSV file:</label>
      <input type="file" class="form-control" id="decisions-file" accept=".csv, .xls, .xlsx" />
    </div>
    <button type="button" id="upload-decisions-file" class="btn btn-primary">Upload</button>
    <div class="upload-output alert alert-info" style="display: none">
      <div class="msg"></div>
      <progress value="0" max="100" style="display: none"></progress>
      <dl class="dl-horizontal">
        <dt>Rows checked:</dt>
        <dd class="checked">0</dd>
        <dt>Verifications, comments or queries found:</dt>
        <dd class="verifications">0</dd>
        <dt>Errors found:</dt>
        <dd class="errors">0</dd>
      </dl>
    </div>
  </div>
</div>
HTML;

    }
    return $r;
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
   * Apply default settings for the mapGeoHash mode.
   *
   * @param array $options
   *   Options passed to the [source]. Will be modified as appropriate.
   */
  private static function applySourceModeDefaultsMapGeoHash(array &$options) {
    // Disable loading of docs.
    $options = array_merge([
      'size' => 0,
    ], $options);
    // Note the geohash_grid precision will be overridden depending on map zoom.
    $aggText = <<<AGG
{
  "by_hash": {
    "geohash_grid": {
      "field": "location.point",
      "precision": 1
    },
    "aggs": {
      "by_centre": {
        "geo_centroid": {
          "field": "location.point"
        }
      }
    }
  }
}
AGG;
    $options = array_merge(['aggregation' => json_decode($aggText)], $options);
  }

  /**
   * Apply default settings for the mapGridSquare mode.
   *
   * @param array $options
   *   Options passed to the [source]. Will be modified as appropriate.
   */
  private static function applySourceModeDefaultsMapGridSquare(array &$options) {
    $options = array_merge([
      'mapGridSquareSize' => 'autoGridSquareSize',
      'size' => 0,
    ], $options);
    if ($options['mapGridSquareSize'] === 'autoGridSquareSize') {
      $geoField = 'autoGridSquareField';
    }
    else {
      $sizeInKm = $options['mapGridSquareSize'] / 1000;
      $geoField = "location.grid_square.{$sizeInKm}km.centre";
    }
    $aggText = <<<AGG
{
  "by_srid": {
    "terms": {
      "field": "location.grid_square.srid",
      "size": 1000,
      "order": {
        "_count": "desc"
      }
    },
    "aggs": {
      "by_square": {
        "terms": {
          "field": "$geoField",
          "size": 100000,
          "order": {
            "_count": "desc"
          }
        }
      }
    }
  }
}
AGG;
    $options = array_merge(['aggregation' => json_decode($aggText)], $options);
  }

  /**
   * Apply default settings for the compositeAggregation mode.
   *
   * @param array $options
   *   Options passed to the [source]. Will be modified as appropriate.
   */
  private static function applySourceModeDefaultsCompositeAggregation(array &$options) {
    $options = array_merge([
      'fields' => [],
      'aggregation' => [],
    ], $options);
  }

  /**
   * Apply default settings for the termAggregation mode.
   *
   * @param array $options
   *   Options passed to the [source]. Will be modified as appropriate.
   */
  private static function applySourceModeDefaultsTermAggregation(array &$options) {
    if (empty($options['uniqueField'])) {
      throw new Exception("Sources require a parameter called @uniqueField when @mode=termAggregation");
    }
    if (!empty($options['orderbyAggregation']) && !is_object($options['orderbyAggregation'])
        && !is_array($options['orderbyAggregation'])) {
      throw new Exception("@orderbyAggregation option for source is not a valid JSON object.");
    }
    $options = array_merge([
      'fields' => [],
      'aggregation' => [],
    ], $options);
  }

  /**
   * Apply default settings depending on the @mode option.
   *
   * @param array $options
   *   Options passed to the [source]. Will be modified as appropriate.
   */
  private static function applySourceModeDefaults(array &$options) {
    $method = 'applySourceModeDefaults' . ucfirst($options['mode']);
    if (method_exists('ElasticsearchReportHelper', $method)) {
      self::$method($options);
    }
  }

  /**
   * Provide common option handling for controls.
   *
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
    // Generate a unique ID if not defined.
    $options = array_merge([
      'id' => "idc-$controlName-" . self::$controlIndex,
    ], $options);
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
   * control.
   *
   * @param string $controlName
   *   Control type name (e.g. source, dataGrid).
   * @param array $options
   *   Options passed to the control. If the control's @containerElement option
   *   is set then sets the required JavaScript to make the control inject
   *   itself into the existing element instead.
   * @param string $dataOptions
   *   Options to store in the HTML data-idc-config attribute on the container.
   *   These are made available to configure the JS behaviour of the control.
   * @param string $content
   *   HTML to add to the container.
   *
   * @return string
   *   Control HTML.
   */
  private static function getControlContainer($controlName, array $options, $dataOptions, $content = '') {
    $initFn = 'idc' . ucfirst($controlName);
    if (!empty($options['containerElement'])) {
      // Use JS to attach to an existing element.
      helper_base::$javascript .= <<<JS
if ($('$options[containerElement]').length === 0) {
  indiciaFns.controlFail($('#$options[id]'), 'Invalid @containerElement selector for $options[id]');
}
$($('$options[containerElement]')[0]).append($('#$options[id]'));

JS;
    }
    helper_base::$javascript .= <<<JS
$('#$options[id]').$initFn({});

JS;
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
        if ($config['type'] !== 'text' || (isset($config['fields']) && isset($config['fields']['keyword']))) {
          $mappings[$field]['sort_field'] = $field;
        }
        else {
          // Disable sorting.
          $mappings[$field]['sort_field'] = FALSE;
        }
      }
    }
  }

  /**
   * Retrieves the ES index mappings data.
   *
   * A list of mapped fields is stored in self::$esMappings.
   *
   * @param int $nid
   *   Node ID, used to retrieve the node parameters which contain ES settings.
   */
  private static function getMappings($nid) {
    require_once 'ElasticsearchProxyHelper.php';
    $config = hostsite_get_es_config($nid);
    if (empty($config['es']['endpoint'])) {
      throw new Exception(lang::get('Elasticsearch configuration incomplete - endpoint not specified in Indicia settings.'));
    }
    // /doc added to URL only for Elasticsearch 6.x.
    $url = $config['indicia']['base_url'] . 'index.php/services/rest/' . $config['es']['endpoint'] . '/_mapping' .
      ($config['es']['version'] == 6 ? '/doc' : '');
    $session = curl_init($url);
    curl_setopt($session, CURLOPT_HTTPHEADER, ElasticsearchProxyHelper::getHttpRequestHeaders($config));
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
