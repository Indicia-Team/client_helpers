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
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Elasticsearch controls to support reporting on public events such as bioblitzes.
 */
class extension_elasticsearch_event_reports {

  private static $esIntegrationDone = FALSE;

  private static $controlCount = 0;

  private static $rangeLimitApplied = FALSE;

  /**
   * Outputs a pie chart of taxon groups.

   * @param array $auth
   *   Authorisation tokens.
   * @param array $args
   *   Form arguments (the settings on the form edit tab).
   * @param string $tabalias
   *   The alias of the tab this is being loaded onto.
   * @param array $options
   *   The options passed to this control using @option=value settings in the
   *   form structure. Options supported include:
   *   * title - set to include a heading in the output.
   *   * cacheTimeout - number of seconds after which the data will refresh.
   *     Default to 300.
   *   Any other options supported by report_helper::report_chart().
   *
   * @return string
   *   HTML to insert into the page for the chart. JavaScript is added
   *   to the variables in helper_base.
   */
  public static function groups_pie($auth, $args, $tabalias, $options, $path) {
    self::initControl($auth, $args);
    iform_load_helpers(['report_helper']);
    helper_base::addLanguageStringsToJs('esGroupsPie', [
      'other' => 'Other groups',
    ]);
    $options = array_merge([
      'id' => 'groups-pie-' . self::$controlCount,
      'title' => FALSE,
      'cacheTimeout' => 300,
      'width' => 340,
      'height' => 340,
      'responsive' => TRUE,
      'chartType' => 'pie',
      'rendererOptions' => [
        'sliceMargin' => 4,
        'showDataLabels' => TRUE,
        'dataLabelThreshold' => 2,
        'dataLabels' => 'label',
        'dataLabelPositionFactor' => 1
      ],
    ], $options);
    $srcOptions = array_merge(self::getSourceOptions($options), [
      'size' => 0,
      'aggregation' => [
        'taxon_group' => [
          'terms' => [
            'field' => 'taxon.group.keyword',
            'size' => 8,
          ],
        ],
      ],
    ]);
    $r = ElasticsearchReportHelper::source($srcOptions);
    if ($options['title']) {
      $r .= "<h2>$options[title]</h2>\n";
    }
    // Clean up options to pass to chart.
    unset($options['cacheTimeout']);
    unset($options['title']);
    $r .= ElasticsearchReportHelper::customScript([
      'id' => $options['id'],
      'source' => "source-$options[id]",
      'functionName' => 'outputGroupsPie',
      'template' => report_helper::report_chart(array_merge($options, [
        'id' => "chart-$options[id]",
        'class' => '',
        'gridOptions' => [
          'drawBorder' => FALSE,
          'background' => '#FFFFFF',
          'shadow' => FALSE,
        ],
        // Data will be filled in by AJAX, but need a dummy value to load the chart.
        'dataSource' => 'static',
        'xLabels' => 'group',
        'yValues' => 'value',
        'staticData' => [['group' => 'Loading', 'value' => 1]],
      ])),
    ]);
    return $r;
  }

  /**
   * Outputs a block of recent photo thumbnails.

   * @param array $auth
   *   Authorisation tokens.
   * @param array $args
   *   Form arguments (the settings on the form edit tab).
   * @param string $tabalias
   *   The alias of the tab this is being loaded onto.
   * @param array $options
   *   The options passed to this control using @option=value settings in the
   *   form structure. Options supported include:
   *   * title - set to include a heading in the output.
   *   * cacheTimeout - number of seconds after which the data will refresh.
   *     Default to 300.
   *   * size - number of photos to display. Defaults to 9.
   *
   * @return string
   *   HTML to insert into the page for the table. JavaScript is added to the
   *   variables in helper_base.
   */
  public static function photos_block($auth, $args, $tabalias, $options, $path) {
    self::initControl($auth, $args);
    helper_base::add_resource('fancybox');
    $options = array_merge([
      'id' => 'photos-block-' . self::$controlCount,
      'title' => FALSE,
      'cacheTimeout' => 300,
      'size' => 9,
    ], $options);
    $srcOptions = array_merge(self::getSourceOptions($options), [
      'size' => $options['size'],
      'filterPath' => 'hits.total,hits.hits._source.id,hits.hits._source.occurrence.media,hits.hits._source.taxon,hits.hits._source.event.recorded_by',
    ]);
    $srcOptions['filterBoolClauses']['must'] = [
      [
        'nested' => 'occurrence.media',
        'query_type' => 'exists',
        'field' => 'occurrence.media.path',
      ]
    ];
    $r = ElasticsearchReportHelper::source($srcOptions);
    if ($options['title']) {
      $r .= "<h2>$options[title]</h2>\n";
    }
    $r .= ElasticsearchReportHelper::customScript([
      'id' => $options['id'],
      'source' => "source-$options[id]",
      'functionName' => 'outputPhotos',
    ]);

    return $r;
  }

  /**
   * Outputs a table of recorded species.

   * @param array $auth
   *   Authorisation tokens.
   * @param array $args
   *   Form arguments (the settings on the form edit tab).
   * @param string $tabalias
   *   The alias of the tab this is being loaded onto.
   * @param array $options
   *   The options passed to this control using @option=value settings in the
   *   form structure. Options supported include:
   *   * title - set to include a heading in the output.
   *   * cacheTimeout - number of seconds after which the data will refresh.
   *     Default to 300.
   *   * speciesOnly - defaults to true. Set to false to include taxonomic
   *     levels other than species.
   *
   * @return string
   *   HTML to insert into the page for the table. JavaScript is added to the
   *   variables in helper_base.
   */
  public static function species_table($auth, $args, $tabalias, $options) {
    self::initControl($auth, $args);
    $options = array_merge([
      'id' => 'species-table-' . self::$controlCount,
      'title' => FALSE,
      'cacheTimeout' => 300,
      'speciesOnly' => TRUE,
    ], $options);
    $srcOptions = array_merge(self::getSourceOptions($options), [
      'mode' => 'termAggregation',
      'size' => 50,
      'sort' => ['doc_count' => 'desc'],
      'uniqueField' => $options['speciesOnly'] ? 'taxon.species_taxon_id' : 'taxon.accepted_taxon_id',
      'fields' => [
        'taxon.kingdom',
        'taxon.order',
        'taxon.family',
        'taxon.group',
        $options['speciesOnly'] ? 'taxon.species' : 'taxon.accepted_name',
        'taxon.vernacular_name',
        'taxon.taxon_meaning_id',
      ],
      'aggregation' => [
        'first_date' => [
          'min' => [
            'field' => 'event.date_start',
            'format' => 'dd/MM/yyyy',
          ],
        ],
        'last_date' => [
          'max' => [
            'field' => 'event.date_end',
            'format' => 'dd/MM/yyyy',
          ],
        ],
      ],
    ]);

    $r = ElasticsearchReportHelper::source($srcOptions);
    if ($options['title']) {
      $r .= "<h2>$options[title]</h2>\n";
    }
    $r .= ElasticsearchReportHelper::dataGrid([
      'id' => $options['id'],
      'source' => "source-$options[id]",
      'columns' => [
        ["caption" => "Accepted name", "field" => ($options['speciesOnly'] ? 'taxon.species' : 'taxon.accepted_name')],
        ["caption" => "Common name", "field" => "taxon.vernacular_name"],
        ["caption" => "Group", "field" => "taxon.group"],
        ["caption" => "Kingdom", "field" => "taxon.kingdom"],
        ["caption" => "Order", "field" => "taxon.order"],
        ["caption" => "Family", "field" => "taxon.family"],
        ["caption" => "No. of records", "field" => "doc_count"],
        ["caption" => "First record", "field" => "first_date"],
        ["caption" => "Last record", "field" => "last_date"]
      ],
    ]);
    return $r;
  }

  /**
   * Outputs a block with total records, species and photos for the event.

   * @param array $auth
   *   Authorisation tokens.
   * @param array $args
   *   Form arguments (the settings on the form edit tab).
   * @param string $tabalias
   *   The alias of the tab this is being loaded onto.
   * @param array $options
   *   The options passed to this control using @option=value settings in the
   *   form structure. Options supported include:
   *   * title - set to include a heading in the output.
   *   * cacheTimeout - number of seconds after which the data will refresh.
   *     Default to 300.
   *   * class - class to attach to the container element.
   *   * itemClass - class to attach to each count's element.
   *
   * @return string
   *   HTML to insert into the page for the block. JavaScript is added to the
   *   variables in helper_base.
   */
  public static function totals_block($auth, $args, $tabalias, $options) {
    self::initControl($auth, $args);
    $options = array_merge([
      'id' => 'totals-block-' . self::$controlCount,
      'title' => FALSE,
      'class' => 'totals-block',
      'itemClass' => 'count',
      'cacheTimeout' => 300,
    ], $options);
    helper_base::addLanguageStringsToJs('esTotalsBlock', [
      'speciesSingle' => '{1} species',
      'speciesMulti' => '{1} species',
      'occurrencesSingle' => '{1} record',
      'occurrencesMulti' => '{1} records',
      'photosSingle' => '{1} photo',
      'photosMulti' => '{1} photos',
      'recordersSingle' => '{1} recorder',
      'recordersMulti' => '{1} recorders',
    ]);
    $srcOptions = array_merge(self::getSourceOptions($options), [
      'size' => 0,
      'aggregation' => [
        'species_count' => [
          'cardinality' => [
            'field' => 'taxon.species_taxon_id',
          ],
        ],
        'photo_count' => [
          'nested' => [ 'path' => 'occurrence.media' ],
        ],
        'recorder_count' => [
          'cardinality' => [
            'field' => 'event.recorded_by.keyword',
          ],
        ],
      ],
    ]);
    $r = ElasticsearchReportHelper::source($srcOptions);
    if ($options['title']) {
      $r .= "<h2>$options[title]</h2>\n";
    }
    $template = <<<HTML
<div id="$options[id]-container" class="$options[class]">
  <div class="$options[itemClass] occurrences"></div>
  <div class="$options[itemClass] species"></div>
  <div class="$options[itemClass] photos"></div>
  <div class="$options[itemClass] recorders"></div>
</div>

HTML;
    $r .= ElasticsearchReportHelper::customScript([
      'id' => $options['id'],
      'source' => "source-$options[id]",
      'functionName' => 'outputTotals',
      'template' => $template,
    ]);

    return $r;
  }

  /**
   * Outputs a word cloud of recorder names.
   *
   * Sized by activity within most recent 1000 records..

   * @param array $auth
   *   Authorisation tokens.
   * @param array $args
   *   Form arguments (the settings on the form edit tab).
   * @param string $tabalias
   *   The alias of the tab this is being loaded onto.
   * @param array $options
   *   The options passed to this control using @option=value settings in the
   *   form structure. Options supported include:
   *   * title - set to include a heading in the output.
   *   * cacheTimeout - number of seconds after which the data will refresh.
   *     Default to 300.
   *
   * @return string
   *   HTML to insert into the page for the cloud. JavaScript is added to the
   *   variables in helper_base.
   */
  public static function trending_recorders_cloud($auth, $args, $tabalias, $options, $path) {
    self::initControl($auth, $args);
    $options = array_merge([
      'id' => 'trending-recorders-cloud-' . self::$controlCount,
      'title' => FALSE,
    ]);
    $srcOptions = array_merge(self::getSourceOptions($options), [
      'size' => 0,
      'disabled' => true,
      'aggregation' => [
        'recorders' => [
          'terms' => [
            'field' => 'event.recorded_by.keyword',
            'size' => 20,
          ],
        ],
      ],
    ]);
    // Source for the cloud data - initially disabled.
    $r = ElasticsearchReportHelper::source($srcOptions);
    if ($options['title']) {
      $r .= "<h2>$options[title]</h2>\n";
    }
    $r .= ElasticsearchReportHelper::customScript([
      'id' => $options['id'],
      'source' => "source-$options[id]",
      'functionName' => 'outputTrendingRecordersCloud',
    ]);
    $r .= self::applyRangeLimit("source-$options[id]", $options);
    return $r;
  }

  /**
   * Outputs a word cloud of taxon names.
   *
   * Sized by prevalence within most recent 1000 records..

   * @param array $auth
   *   Authorisation tokens.
   * @param array $args
   *   Form arguments (the settings on the form edit tab).
   * @param string $tabalias
   *   The alias of the tab this is being loaded onto.
   * @param array $options
   *   The options passed to this control using @option=value settings in the
   *   form structure. Options supported include:
   *   * title - set to include a heading in the output.
   *   * cacheTimeout - number of seconds after which the data will refresh.
   *     Default to 300.
   *
   * @return string
   *   HTML to insert into the page for the cloud. JavaScript is added to the
   *   variables in helper_base.
   */
  public static function trending_taxa_cloud($auth, $args, $tabalias, $options, $path) {
    self::initControl($auth, $args);
    $options = array_merge([
      'id' => 'trending-taxa-cloud-' . self::$controlCount,
      'title' => FALSE,
    ]);
    $srcOptions = array_merge(self::getSourceOptions($options), [
      'size' => 0,
      'disabled' => true,
      'aggregation' => [
        'species' => [
          'terms' => [
            'field' => 'taxon.species.keyword',
            'size' => 20,
          ],
          'aggs' => [
            'vernacular' => [
              'terms' => [
                'field' => 'taxon.vernacular_name.keyword',
                'size' => 1
              ],
            ],
          ],
        ],
      ],
    ]);
    // Source for the cloud data - initially disabled.
    $r = ElasticsearchReportHelper::source($srcOptions);
    if ($options['title']) {
      $r .= "<h2>$options[title]</h2>\n";
    }
    $r .= ElasticsearchReportHelper::customScript([
      'id' => $options['id'],
      'source' => "source-$options[id]",
      'functionName' => 'outputTrendingTaxaCloud',
    ]);
    $r .= self::applyRangeLimit("source-$options[id]", $options);
    return $r;
  }

  /**
   * Outputs a records grid map.

   * @param array $auth
   *   Authorisation tokens.
   * @param array $args
   *   Form arguments (the settings on the form edit tab).
   * @param string $tabalias
   *   The alias of the tab this is being loaded onto.
   * @param array $options
   *   The options passed to this control using @option=value settings in the
   *   form structure. Options supported include:
   *   * title - set to include a heading in the output.
   *   * cacheTimeout - number of seconds after which the data will refresh.
   *     Default to 300.
   *
   * @return string
   *   HTML to insert into the page for the map. JavaScript is added to the
   *   variables in helper_base.
   */
  public static function records_map($auth, $args, $tabalias, $options, $path) {
    self::initControl($auth, $args);
    iform_load_helpers(['report_helper']);
    $options = array_merge([
      'id' => 'records-map-block-' . self::$controlCount,
      'title' => FALSE,
      'cacheTimeout' => 300,
    ], $options);
    $filterBoundary = report_helper::get_report_data([
      'dataSource' => 'library/groups/group_boundary_transformed',
      'readAuth' => $auth['read'],
      'extraParams' => ['group_id' => $_GET['group_id']],
    ]);
    $srcOptions = array_merge(self::getSourceOptions($options), [
      'mode' => 'mapGridSquare',
      // If group has no boundary filter, use the data to zoom the map.
      'initialMapBounds' => count($filterBoundaries) === 0,
      'switchToGeomsAt' => 12,
    ]);
    $r = ElasticsearchReportHelper::source($srcOptions);
    if ($options['title']) {
      $r .= "<h2>$options[title]</h2>\n";
    }
    $r .= ElasticsearchReportHelper::leafletMap([
      'layerConfig' => [
        'recordsMap' => [
        'title' => 'Records map',
        'source' => "source-$options[id]",
        'type' => 'circle',
        'enabled' => TRUE,
        ],
      ],
    ]);
    if (count($filterBoundary) > 0) {
      report_helper::$indiciaData['reportBoundary'] = $filterBoundary[0]['boundary'];
      report_helper::$late_javascript .= <<<JS
indiciaFns.loadReportBoundaries();

JS;
    }
    return $r;
  }

  /**
   * Outputs a league table of recorder names order by record count.

   * @param array $auth
   *   Authorisation tokens.
   * @param array $args
   *   Form arguments (the settings on the form edit tab).
   * @param string $tabalias
   *   The alias of the tab this is being loaded onto.
   * @param array $options
   *   The options passed to this control using @option=value settings in the
   *   form structure. Options supported include:
   *   * title - set to include a heading in the output.
   *   * cacheTimeout - number of seconds after which the data will refresh.
   *     Default to 300.
   *   * size - number of records to return. Defaults to 50.
   *
   * @return string
   *   HTML to insert into the page for the table. JavaScript is added to the
   *   variables in helper_base.
   */
  public static function records_by_recorders_league($auth, $args, $tabalias, $options, $path) {
    self::initControl($auth, $args);
    $options = array_merge([
      'id' => 'records-by-recorders-league-block-' . self::$controlCount,
      'title' => FALSE,
      'cacheTimeout' => 300,
      'size' => 50,
    ], $options);
    $srcOptions = array_merge(self::getSourceOptions($options), [
      'size' => $options['size'],
      'mode' => 'termAggregation',
      'sort' => ['doc_count' => 'desc'],
      'uniqueField' => 'event.recorded_by',
    ]);
    $r = ElasticsearchReportHelper::source($srcOptions);
    if ($options['title']) {
      $r .= "<h2>$options[title]</h2>\n";
    }
    $r .= ElasticsearchReportHelper::dataGrid([
      'id' => $options['id'],
      'source' => "source-$options[id]",
      'includeColumnSettingsTool' => FALSE,
      'includeFullScreenTool' => FALSE,
      'columns' => [
        ['caption' => 'Recorder', 'field' => 'event.recorded_by'],
        ['caption' => 'No. of records', 'field' => 'doc_count'],
      ],
    ]);
    return $r;
  }

  /**
   * Outputs a league table of recorder names order by species count.

   * @param array $auth
   *   Authorisation tokens.
   * @param array $args
   *   Form arguments (the settings on the form edit tab).
   * @param string $tabalias
   *   The alias of the tab this is being loaded onto.
   * @param array $options
   *   The options passed to this control using @option=value settings in the
   *   form structure. Options supported include:
   *   * title - set to include a heading in the output.
   *   * cacheTimeout - number of seconds after which the data will refresh.
   *     Default to 300.
   *   * size - number of records to return. Defaults to 50.
   *
   * @return string
   *   HTML to insert into the page for the table. JavaScript is added to the
   *   variables in helper_base.
   */
  public static function species_by_recorders_league($auth, $args, $tabalias, $options, $path) {
    self::initControl($auth, $args);
    $options = array_merge([
      'id' => 'species-by-recorders-league-block-' . self::$controlCount,
      'title' => FALSE,
      'cacheTimeout' => 300,
      'size' => 50,
    ], $options);
    $srcOptions = array_merge(self::getSourceOptions($options), [
      'size' => $options['size'],
      'mode' => 'termAggregation',
      'sort' => ['species' => 'desc'],
      'uniqueField' => 'event.recorded_by',
      'aggregation' => [
        'species' => [
          'cardinality' => [
            'field' => 'taxon.species_taxon_id',
          ],
        ],
      ],
    ]);
    $r = ElasticsearchReportHelper::source($srcOptions);
    if ($options['title']) {
      $r .= "<h2>$options[title]</h2>\n";
    }
    $r .= ElasticsearchReportHelper::dataGrid([
      'id' => $options['id'],
      'source' => "source-$options[id]",
      'includeColumnSettingsTool' => FALSE,
      'includeFullScreenTool' => FALSE,
      'columns' => [
        ['caption' => 'Recorder', 'field' => 'event.recorded_by'],
        ['caption' => 'No. of species', 'field' => 'species'],
      ],
    ]);
    return $r;
  }

  /**
   * Applies a recent 1000 records limit for some outputs.
   *
   * Some outputs (e.g. "trending data") limit the analysis to the most recent
   * 1000 records added to the group. This sets up a source to find the ID of
   * the first record to include in the analysis. The JavaScript fn that fires
   * when the source populates then applies a filter to and populates the
   * sources for these outputs.
   *
   * @param string $srcId
   *   ID of the output being range limited.
   * @param array $options
   *   Options for the output being range limited.
   *
   * @return string
   *   HTML for the source that finds the ID range to apply as a limit.
   */
  private static function applyRangeLimit($srcId, $options) {
    $r = '';
    // Only need the range limit source once.
    if (!self::$rangeLimitApplied) {
      // A 2nd source to find the 1000th most recent record in this group's data,
      // which will eventually be applied as a filter on the cloud (so it is
      // recent trending data only).
      $recent1000srcOptions = array_merge(self::getSourceOptions($options), [
        'id' => "recent-1000-source",
        'size' => 1,
        'from' => 1000,
        'sort' => ['id' => 'desc'],
      ]);
      $r .= ElasticsearchReportHelper::source($recent1000srcOptions);
      $r .= ElasticsearchReportHelper::customScript([
        'id' => "recent-1000",
        'source' => "recent-1000-source",
        'functionName' => 'rangeLimitAndPopulateSources',
      ]);
      self::$rangeLimitApplied = TRUE;
    }
    // Remember the source that needs filtering.
    if (!isset(helper_base::$indiciaData['applyRangeLimitTo'])) {
      helper_base::$indiciaData['applyRangeLimitTo'] = [];
    }
    helper_base::$indiciaData['applyRangeLimitTo'][] = $srcId;
    return $r;
  }

  /**
   * Function to return default options for source controls.
   *
   * Sets the options ID, proxy caching and filters out absence records.
   */
  private static function getSourceOptions($options) {
    return [
      'id' => "source-$options[id]",
      'proxyCacheTimeout' => $options['cacheTimeout'],
      'filterBoolClauses' => [
        'must_not' => [
          [
            'query_type' => 'term',
            'field' => 'occurrence.zero_abundance',
            'value' => 'true',
          ],
        ],
      ],
    ];
  }

  /**
   * Generic control initialisation.
   */
  private static function initControl($auth, $args) {
    if (!self::$esIntegrationDone) {
      iform_load_helpers(['ElasticsearchReportHelper']);
      ElasticsearchReportHelper::enableElasticsearchProxy();
      // Apply group filter if this is a group page.
      if ($args['available_for_groups'] === '1') {
        ElasticsearchReportHelper::groupIntegration(['readAuth' => $auth['read']]);
      }
      self::$esIntegrationDone = TRUE;
    }
    self::$controlCount++;
  }

}