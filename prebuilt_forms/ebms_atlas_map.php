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
 * @link http://code.google.com/p/indicia/
 */

use IForm\prebuilt_forms\PageType;

require_once 'includes/dynamic.php';
//require_once 'includes/BaseDynamicDetails.php';
require_once 'includes/report.php';

/**
 * Displays the details of a single taxon. Takes an taxa_taxon_list_id in the URL and displays the following using a configurable
 * page template:
 * Species Details including custom attributes
 * An Explore Species' Records button that links to a custom URL
 * Any photos of occurrences with the same meaning as the taxon
 * A map displaying occurrences of taxa with the same meaning as the taxon.
 */
class iform_ebms_atlas_map extends iform_dynamic {
//class iform_ebms_atlas_map extends BaseDynamicDetails {

  /**
   * Stores the external_key of the taxon.
   *
   * @var externalKey
   */
  private static $externalKey;

  /**
   * Stores the taxa_taxon_list_id of the taxon.
   *
   * @var taxaTaxonListId
   */
  private static $taxaTaxonListId;

  /**
   * Stores the preferred name of the taxon.
   *
   * @var preferred
   */
  private static $preferred;

  /**
   * Stores the required year for the data.
   *
   * @var dataYearFilter
   */
  private static $dataYearFilter;

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_ebms_atlas_map_definition() {
    return array(
      'title' => 'eBMS atlas maps',
      'category' => 'Utilities',
      'description' => 'eBMS atlas map.',
      'recommended' => TRUE,
    );
  }

  /**
   * {@inheritDoc}
   */
  public static function getPageType(): PageType {
    return PageType::Report;
  }

  /**
   * Return an array of parameters for the edit tab.
   * @return array The parameters for the form.
   */
  public static function get_parameters() {
    $retVal = array_merge(
      array(
      //Allows the user to define how the page will be displayed.
      array(
        'name' => 'structure',
        'caption' => 'Form Structure',
        'description' => 'Define the structure of the form. Each component must be placed on a new line. <br/>'.
          "The following types of component can be specified. <br/>".
          "<strong>[control name]</strong> indicates a predefined control is to be added to the form with the following predefined controls available: <br/>".
              "&nbsp;&nbsp;<strong>[tracemap]</strong> - displays a tracemap showing seasonal distribution with date slider control<br/>".
              "&nbsp;&nbsp;<strong>[year]</strong> - allows a year filter to be applied to the data.<br/>".
              "&nbsp;&nbsp;<strong>[species]</strong> - adds a species selection control<br/>".
          "<strong>=tab/page name=</strong> is used to specify the name of a tab or wizard page (alpha-numeric characters only). ".
          "If the page interface type is set to one page, then each tab/page name is displayed as a seperate section on the page. ".
          "Note that in one page mode, the tab/page names are not displayed on the screen.<br/>".
          "<strong>|</strong> is used to split a tab/page/section into two columns, place a [control name] on the previous line and following line to split.<br/>",
        'type' => 'textarea',
        'default' => '
=General=
[species]
[tracemap]
[year]',
        'group' => 'User Interface'
      ),
      // Initial species.
      [
        'name' => 'taxa_taxon_list_id',
        'caption' => 'Taxa taxon list ID',
        'description' => 'Taxa taxon list ID to initialise the visualisation.',
        'type' => 'string',
        'required' => FALSE,
        'default' => '',
        'group' => 'Initialise species',
      ],
      [
        'name' => 'external_key',
        'caption' => 'External key',
        'description' => 'External key to initialise the visualisation.',
        'type' => 'string',
        'required' => FALSE,
        'default' => '',
        'group' => 'Initialise species',
      ],
      // Taxon selector.
      [
        'fieldname' => 'list_id',
        'label' => 'Species List',
        'helpText' => 'The species list that species can be selected from.',
        'type' => 'select',
        'table' => 'taxon_list',
        'valueField' => 'id',
        'captionField' => 'title',
        'required' => FALSE,
        'group' => 'Species selection',
        'siteSpecific'  => TRUE,
      ],
      [
        'name' => 'species_include_authorities',
        'caption' => 'Include species authors in the search string',
        'description' => 'Should species authors be shown in the search
          results when searching for a species name?',
        'type' => 'boolean',
        'required' => FALSE,
        'group' => 'Species selection',
      ],
      [
        'name' => 'species_include_both_names',
        'caption' => 'Include both names in species controls and added rows',
        'description' => 'When using a species grid with the ability to add
          new rows, the autocomplete control by default shows just the
          searched taxon name in the drop down. Set this to include both the
          latin and common names, with the searched one first. This also
          controls the label when adding a new taxon row into the grid.',
        'type' => 'boolean',
        'required' => FALSE,
        'group' => 'Species selection',
      ],
      // Other stuff
      [
        'name' => 'use_get',
        'caption' => 'Enable setting parameters on URL',
        'description' => 'Check this box if species/year to be set in URL.',
        'type' => 'boolean',
        'required' => FALSE,
        'group' => 'Other',
      ],
      [
        'name' => 'geohash_level',
        'caption' => 'Indicate the geohash level - must be a number between 1 and 6. ',
        'description' => 'Indicates the level at which data will be gridded smaller ' .
            'numbers indicate bigger areas. If any other number is specified, the default of 4 is used.',
        'type' => 'int',
        'default' => 4,
        'required' => TRUE,
        'group' => 'Other',
      ],
      )
    );
    return $retVal;
  }

  /**
   * Main build function to return the page HTML.
   *
   * @param array $args
   *   Page parameters.
   * @param int $nid
   *   Node ID.
   *
   * @return string
   *   Page HTML.
   */
  public static function get_form($args, $nid) {

    iform_load_helpers(['ElasticsearchReportHelper']);
    ElasticsearchReportHelper::enableElasticsearchProxy($nid);
    //$enabled = ElasticsearchReportHelper::enableElasticsearchProxy($nid); // Available in the newer BaseDynamicDetails base class - not yet on eBMS

    $enabled = TRUE;
    if ($enabled) {
      return parent::get_form($args, $nid);
    }
    global $indicia_templates;
    return str_replace('{message}', lang::get('This page cannot be accessed due to the server being unavailable.'), $indicia_templates['warningBox']);
  }

  /**
   * Override the get_form_html function.
   * getForm in dynamic.php will now call this.
   * Vary the display of the page based on the interface type
   *
   * @package    Client
   * @subpackage PrebuiltForms
   */
  protected static function get_form_html($args, $auth, $attributes) {

    // First set variables from parameters if set
    $getForm = isset($args['use_get']) ? $args['use_get'] : FALSE;

    if ($getForm) {
      empty($_GET['external_key']) ? self::$externalKey='' : self::$externalKey=$_GET['external_key'];
      empty($_GET['taxa_taxon_list_id']) ? self::$taxaTaxonListId='' : self::$taxaTaxonListId=$_GET['taxa_taxon_list_id'];
      empty($_GET['data_year_filter']) ? self::$dataYearFilter=0 : self::$dataYearFilter=$_GET['data_year_filter'];
    } else {
      empty($_POST['external_key']) ? self::$externalKey='' : self::$externalKey=$_POST['external_key'];
      empty($_POST['taxa_taxon_list_id']) ? self::$taxaTaxonListId='' : self::$taxaTaxonListId=$_POST['taxa_taxon_list_id'];
      empty($_POST['data_year_filter']) ? self::$dataYearFilter=0 : self::$dataYearFilter=$_POST['data_year_filter'];
    }
    // Otherwise get from form fields if set
    if (self::$externalKey == '' && isset($args['external_key'])) {
      self::$externalKey = $args['external_key'];
    }
    if (self::$taxaTaxonListId == '' && isset($args['taxa_taxon_list_id'])) {
      self::$taxaTaxonListId = $args['taxa_taxon_list_id'];
    }

    self::getNames($auth);

    // In Drupal 9, markup cannot be used in page title, so remove em tags."
    // $repArray = ['<em>', '</em>'];
    // if (self::$preferred <> '') {
    //   $preferredName = lang::get('Seasonal occurrence for {1}', str_replace($repArray, '', self::$preferred));
    // } else {
    //   $preferredName = lang::get('Seasonal occurrence');
    // }

    // hostsite_set_page_title($preferredName);
    // hostsite_set_page_title not working for some reason - set as hidden filed for JS to do instead.
    // $hidden = '<input type="hidden" id="preferred-name" value="' . $title . '"/>';
    // Make the external-key available to JS.

    $hidden = '<input type="hidden" id="external-key" value="' . self::$externalKey . '"/>';

    $getOrPost = $getForm ? 'GET' : 'POST';
    $form = '<form method="' . $getOrPost . '" id="ebms-atlas-map-form">';
    // The species_autocomplete control returns a taxa_taxon_list_id. Create a hidden input
    // on the GET form (URL parameter) that  javascript will populate with the selected value
    // but initialise it with current value if.
    $form .= '<input type="hidden" id="taxa_taxon_list_id" name="taxa_taxon_list_id" value="' . self::$taxaTaxonListId . '"></input>';
    $form .= '<input type="hidden" id="data_year_filter" name="data_year_filter"></input>';
    $form .= '</form>';

    if (self::$externalKey <> '') {
      // Add any general ES taxon filters for taxon specified in URL. We use the ES field taxon.accepted_taxon_id
      // which is the external_key. We can't use the *preferred* taxa_taxon_list_id as this is not available on the
      // ES index.
      $esFilter = self::createEsFilterHtml('taxon.accepted_taxon_id', self::$externalKey, 'match_phrase', 'must');
      // Exclude rejected records in ES queries.
      $esFilter .= self::createEsFilterHtml('identification.verification_status', 'R', 'term', 'must_not');
      // Only those that pass auto-checks should be included.
      $esFilter .= self::createEsFilterHtml('identification.auto_checks.result', 'true', 'term', 'must');
      // Do not include any immature life stages
      $esFilter .= self::createEsFilterHtml('occurrence.life_stage', 'egg', 'term', 'must_not');
      $esFilter .= self::createEsFilterHtml('occurrence.life_stage', 'Egg', 'term', 'must_not');
      $esFilter .= self::createEsFilterHtml('occurrence.life_stage', 'larva', 'term', 'must_not');
      $esFilter .= self::createEsFilterHtml('occurrence.life_stage', 'Larva', 'term', 'must_not');
      $esFilter .= self::createEsFilterHtml('occurrence.life_stage', 'larvae', 'term', 'must_not');
      $esFilter .= self::createEsFilterHtml('occurrence.life_stage', 'Larvae', 'term', 'must_not');
      $esFilter .= self::createEsFilterHtml('occurrence.life_stage', 'pupa', 'term', 'must_not');
      $esFilter .= self::createEsFilterHtml('occurrence.life_stage', 'Pupa', 'term', 'must_not');
      $esFilter .= self::createEsFilterHtml('occurrence.life_stage', 'pupae', 'term', 'must_not');
      $esFilter .= self::createEsFilterHtml('occurrence.life_stage', 'Pupae', 'term', 'must_not');
      $esFilter .= self::createEsFilterHtml('occurrence.life_stage', 'cocoon', 'term', 'must_not');
      $esFilter .= self::createEsFilterHtml('occurrence.life_stage', 'Cocoon', 'term', 'must_not');
      $esFilter .= self::createEsFilterHtml('occurrence.life_stage', 'caterpillar', 'term', 'must_not');
      $esFilter .= self::createEsFilterHtml('occurrence.life_stage', 'Caterpillar', 'term', 'must_not');
    } else {
      self::$taxaTaxonListId = '';
      $esFilter = '';
    }


    if (self::$dataYearFilter != 0) {
      $esFilter .= self::createEsFilterHtml('event.year', self::$dataYearFilter, 'match_phrase', 'must');
    }

    return $hidden . $form . $esFilter . parent::get_form_html($args, $auth, $attributes);
  }


  /**
   * Initialises the JavaScript required for an Elasticsearch data source.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-source
   *
   * @return string
   *   Empty string as no HTML required.
   */
  protected static function get_control_source($auth, $args, $tabalias, $options) {
    return ElasticsearchReportHelper::source($options);
  }

  /**
   * Returns a control to display a trace map.
   *
   * @global type $indicia_templates
   * @param array $auth
   *   Read authorisation tokens.
   * @param array $args
   *   Form configuration.
   * @param array $tabAlias
   * @param array $options
   *   Additional options for the control, e.g. those configured in the form
   *   structure.
   *
   * @return string
   *   HTML for the control.
   */

  protected static function get_control_tracemap($auth, $args, $tabalias, $options) {

    $glevel = isset($args['geohash_level']) ? $args['geohash_level'] : 4;
    if ($glevel < 1 || $glevel > 6) {
      $glevel = 4;
    }

    $options = array_merge([
      'dotcolour' => 'magenta',
      'initplay' => 'no'
    ], $options);

    // Hidden controls to store map parameters
    $hidden = '<input type="hidden" id="dot-colour" value="' . $options['dotcolour'] . '"/>';
    $hidden .= '<input type="hidden" id="initplay" value="' . $options['initplay'] . '"/>';

    // Loact brc_atlas_e library resource
    data_entry_helper::add_resource('brc_atlas_e');

    if (self::$externalKey <> '') {

      // $optionsSource = [
      //   'extraParams' => $options['extraParams'],
      //   'nid' => $options['nid'],
      //   'id' => 'tracemapSource',
      //   'filterPath' => 'hits.total,hits.hits._source.event.week, hits.hits._source.event.year, hits.hits._source.location.point',
      //   'size' => 10000,
      //   'mode' => 'docs',
      // ];

      // ElasticsearchReportHelper::source($optionsSource);
      // $optionsCustomScript = [
      //   'extraParams' => $options['extraParams'],
      //   'nid' => $options['nid'],
      //   'source' => 'tracemapSource',
      //   'functionName' => 'processTraceMapData',
      // ];

      // Set cache for query
      if (self::$dataYearFilter == date("Y") || self::$dataYearFilter == 0) {
        $cacheTimeout = 86400; // Set cache for one day
      } else {
        $cacheTimeout = 86400 * 7;
      }

      $optionsSource = [
        'extraParams' => $options['extraParams'],
        'nid' => $options['nid'],
        'id' => 'tracemapSource',
        'size' => 0,
        'proxyCacheTimeout' => $cacheTimeout,
        'aggregation' => [
          'aggs' => [
            'terms' => [
              'field' => 'event.week',
              'size' => '53'
            ],
            'aggs' => [
              'geohash' => [
                'geohash_grid' => [
                  'field' => 'location.point',
                  'precision' => $glevel
                ],
                // Getting lat/lon from ES aggregation doesn't seem
                // to give centroid of geohash as espected, so instead
                // we use one worked out directly from geohash.
                // 'aggs' => [
                //   'centroid' => [
                //     'geo_centroid' => [
                //       'field' => 'location.point'
                //     ]
                //   ]
                // ]
              ]
            ]
          ]
        ]
      ];

      ElasticsearchReportHelper::source($optionsSource);
      $optionsCustomScript = [
        'extraParams' => $options['extraParams'],
        'nid' => $options['nid'],
        'source' => 'tracemapSource',
        'functionName' => 'processTraceMapData2',
      ];

      $r = ElasticsearchReportHelper::customScript($optionsCustomScript);
    } else {
      $r = '';
    }
    $r .= '<div id="ebms-tracemap"></div>';
    $r .= '<div style="height: 40px">';
    $r .= '<div id="playPause" onclick="indiciaFns.playPause()">></div>';
    $r .= '<div class="slidecontainer">';
    $r .= '<input type="range" min="1" max="52" value="1" class="slider" oninput="indiciaFns.displayWeek(this.value)"></input>';
    $r .= '</div>';
    $r .= '<div id="weekNo" style="text-align: center;"></div>';
    $r .= '</div>';
    $r .= $hidden;

    return $r;
  }

  /**
   * Returns a control for picking a year or using data from all years.
   *
   * @global type $indicia_templates
   * @param array $auth
   *   Read authorisation tokens.
   * @param array $args
   *   Form configuration.
   * @param array $tabAlias
   * @param array $options
   *   Additional options for the control, e.g. those configured in the form
   *   structure.
   *
   * @return string
   *   HTML for the control.
   */
  protected static function get_control_year($auth, $args, $tabAlias, $options) {

    $options = array_merge([
      'minyear' => 2015,
    ], $options);

    $currentYear = date("Y");

    if (self::$dataYearFilter == 0) {
      $checked = 'checked';
      $initYear = '';
      $disabled = 'disabled';
    } else {
      $checked = '';
      $initYear = self::$dataYearFilter;
      $disabled = '';
    }

    // Dynamically generate the species selection control required.
    $r = '<div>';

    $r .= '<button onclick="indiciaFns.speciesDetailsSub()" style="width: 100px; margin: 0 1em 0 0">Fetch data</button>';
    $r .= '<input onclick="indiciaFns.allYearsCheckboxClicked()" ' . $checked . ' type="checkbox" id="data-year-allyears" name="data-year-allyears">';
    $r .= '<label for="data-year-allyears" style="margin-left: 0.3em">All years</label>';

    $r .= '<input ' . $disabled . ' style="margin-left: 1em" type="number" id="data-year-filter" name="data-year-filter" value="'. $initYear . '" min="' . $options['minyear'] . '" max="'. $currentYear . '">';

    $r .= '</div>';

    return $r;
  }


  /**
   * Returns an h2 title based on the name of the selected taxon.
   *
   * @global type $indicia_templates
   * @param array $auth
   *   Read authorisation tokens.
   * @param array $args
   *   Form configuration.
   * @param array $tabAlias
   * @param array $options
   *   Additional options for the control, e.g. those configured in the form
   *   structure.
   *
   * @return string
   *   HTML for the control.
   */
  protected static function get_control_title($auth, $args, $tabAlias, $options) {

    if (self::$preferred <> '') {
      $year = self::$dataYearFilter == 0 ? 'all years' : self::$dataYearFilter;
      $r = '<h2>' . self::$preferred . ' - ' . $year . '</h2>';
    } else {
      $r = '';
    }
    return $r;
  }

  /**
   * Returns a control for picking a species.
   *
   * @global type $indicia_templates
   * @param array $auth
   *   Read authorisation tokens.
   * @param array $args
   *   Form configuration.
   * @param array $extraParams
   *   Extra parameters pre-configured with taxon and taxon name type filters.
   * @param array $options
   *   Additional options for the control, e.g. those configured in the form
   *   structure.
   *
   * @return string
   *   HTML for the control.
   */
  protected static function get_control_species($auth, $args, $tabAlias, $options) {

    $extraParams = $auth['read'];
    if ($args['list_id'] !== '') {
      $extraParams['taxon_list_id'] = $args['list_id'];
    }
    $species_ctrl_opts = array_merge([
      'fieldname' => 'occurrence:taxa_taxon_list_id',
    ], $options);
    if (isset($species_ctrl_opts['extraParams'])) {
      $species_ctrl_opts['extraParams'] = array_merge($extraParams, $species_ctrl_opts['extraParams']);
    }
    else {
      $species_ctrl_opts['extraParams'] = $extraParams;
    }
    $species_ctrl_opts['extraParams'] = array_merge([
      'orderby' => 'taxonomic_sort_order',
      'sortdir' => 'ASC',
    ], $species_ctrl_opts['extraParams']);

    // Apply options and build species autocomplete formatting function
    $opts = [
      'speciesIncludeAuthorities' => isset($args['species_include_authorities']) ?
        $args['species_include_authorities'] : FALSE,
      'speciesIncludeBothNames' => $args['species_include_both_names'],
    ];
    data_entry_helper::build_species_autocomplete_item_function($opts);

    // Dynamically generate the species selection control required.
    $r = '<div style="height: 40px">';
    $r .= '<button onclick="indiciaFns.speciesDetailsSub()" style="float:left; width: 100px; margin: 1px 5px 0 0">Fetch data</button>';
    $r .= '<div style="float:left; width: calc(100% - 105px)">';
    $r .= call_user_func(['data_entry_helper', 'species_autocomplete'], $species_ctrl_opts);
    $r .= '</div>';
    $r .= '</div>';

    return $r;
  }

  /**
   * When a form version is upgraded introducing new parameters, old forms will not get the defaults for the
   * parameters unless the Edit and Save button is clicked. So, apply some defaults to keep those old forms
   * working.
   */
  protected static function getArgDefaults($args) {
    if (!isset($args['interface']) || empty($args['interface']))
      $args['interface'] = 'one_page';

    if (!isset($args['structure']) || empty($args['structure'])) {
      $args['structure'] =
'=General=
[tracemap]';
    }
    return $args;
  }

  /**
   * Make a hidden input specifing a page-wide filter for ES queries.
   *
   * @return string
   *   Hidden input HTML.
   */
  protected static function createEsFilterHtml($field, $value, $queryType, $boolClause) {
    $r = <<<HTML
<input type="hidden" class="es-filter-param" value="$value"
  data-es-bool-clause="$boolClause" data-es-field="$field" data-es-query-type="$queryType" />

HTML;
    return $r;
  }

  /**
   * Obtains details of all names for this species from the database.
   */
  protected static function getNames($auth) {

    if (self::$taxaTaxonListId <> '' || self::$externalKey <> '') {
      iform_load_helpers(['report_helper']);
      $extraParams = ['sharing' => 'reporting'];
      $extraParams['preferred'] = TRUE;
      if (self::$taxaTaxonListId <> '') {
        $extraParams['taxa_taxon_list_id'] = self::$taxaTaxonListId;
      }
      if (self::$externalKey <> '') {
        $extraParams['external_key'] = self::$externalKey;
      }
      $species_details = report_helper::get_report_data([
        'readAuth' => $auth['read'],
        'dataSource' => 'projects/ebms/ebms_taxon_names',
        'useCache' => FALSE,
        'extraParams' => $extraParams,
      ]);

      if (self::$externalKey == '') {
        self::$externalKey = $species_details[0]['external_key'];
      }

      self::$preferred = $species_details[0]['taxon'];
    }
  }
}