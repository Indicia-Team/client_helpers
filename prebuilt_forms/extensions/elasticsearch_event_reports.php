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

  private static $groupIntegrationDone = FALSE;

  private static $controlCount = 0;

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
    self::initControl($auth);
    iform_load_helpers(['report_helper']);
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
            'field' => 'taxon.group.keyword'
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
    self::initControl($auth);
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
    self::initControl($auth);
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
      ],
    ]);
    $r = ElasticsearchReportHelper::source($srcOptions);
    if ($options['title']) {
      $r .= "<h2>$options[title]</h2>\n";
    }
    $template = <<<HTML
<div id="$options[id]" class="$options[class]">
  <div class="$options[itemClass] occurrences"></div>
  <div class="$options[itemClass] species"></div>
  <div class="$options[itemClass] photos"></div>
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
    $options = array_merge([
      'id' => 'records-map-block-' . self::$controlCount,
      'title' => FALSE,
      'cacheTimeout' => 300,
    ], $options);
    $srcOptions = array_merge(self::getSourceOptions($options), [
      'mode' => 'mapGridSquare',
      'initialMapBounds' => TRUE,
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
    return $r;
  }

  public static function species_by_recorders_league($auth, $args, $tabalias, $options, $path) {
    self::initControl($auth);
    $options = array_merge([
      'id' => 'species-by-recorders-league-block-' . self::$controlCount,
      'title' => FALSE,
      'cacheTimeout' => 300,
    ], $options);
    $srcOptions = array_merge(self::getSourceOptions($options), [
      'size' => 0,
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
      'includePager' => FALSE,
      'columns' => [
        ['caption' => 'Recorder', 'field' => 'event.recorded_by'],
        ['caption' => 'No. of species', 'field' => 'species'],
      ],
    ]);
    return $r;
  }

  public static function records_by_recorders_league($auth, $args, $tabalias, $options, $path) {
    self::initControl($auth);
    $options = array_merge([
      'id' => 'records-by-recorders-league-block-' . self::$controlCount,
      'title' => FALSE,
      'cacheTimeout' => 300,
    ], $options);
    $srcOptions = array_merge(self::getSourceOptions($options), [
      'size' => 0,
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
      'includePager' => FALSE,
      'columns' => [
        ['caption' => 'Recorder', 'field' => 'event.recorded_by'],
        ['caption' => 'No. of records', 'field' => 'doc_count'],
      ],
    ]);
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

  private static function initControl($auth) {
    if (!self::$groupIntegrationDone) {
      iform_load_helpers(['ElasticsearchReportHelper']);
      ElasticsearchReportHelper::enableElasticsearchProxy();
      ElasticsearchReportHelper::groupIntegration(['readAuth' => $auth['read']]);
      self::$groupIntegrationDone = TRUE;
    }    
    self::$controlCount++;
  }

}