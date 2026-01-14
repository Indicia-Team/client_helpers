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
 * @link https://github.com/Indicia-Team/client_helpers
 */

require_once 'includes/BaseDynamicDetails.php';
require_once 'includes/report.php';

/**
 * Displays the the BAS atlas.
 *
 * Takes an taxon identifier in the URL which can be either:
 * - taxa_taxon_list_id
 * - taxon_meaning_id
 * - external_key (tvk)
 *
 * Displays the atlas page for the taxon.
 */
class iform_bas_atlas extends BaseDynamicDetails {

  /**
   * Stores a value to indicate of no taxon identified.
   *
   * @var notaxon
   */
  private static $notaxon;

  /**
   * Stores the external_key of the taxon.
   *
   * @var externalKey
   */
  private static $externalKey;


  /**
   * Stores the groups (orders) array
   *
   * @var orders
   */

  private static $orders = [
    'spider' => ['tcFilter' => 'spider (Araneae)', 'esFilter' => 'Araneae'],
    'harvestman' => ['tcFilter' => 'harvestman (Opiliones)', 'esFilter' => 'Opiliones'],
    'pseudoscorpion' => ['tcFilter' => 'false scorpion (Pseudoscorpiones)', 'esFilter' => 'Pseudoscorpiones'],
  ];


  /**
   * Disable form element wrapped around output.
   *
   * @return bool
   *   Indicates that this is not a data entry form.
   */
  protected static function isDataEntryForm() {
    return FALSE;
  }

  /**
   * The definition of the form.
   *
   * @return array
   *   An array of required information.
   */
  public static function get_bas_atlas_definition() {
    return [
      'title' => 'BAS atlas',
      'category' => 'Utilities',
      'description' => 'Atlas for the British Arachnological Society.',
      'recommended' => FALSE,
    ];
  }

  /**
   * Return value.
   *
   * @return array
   *   Array of parameters for the edit tab.
   */
  public static function get_parameters() {
    $retVal = array_merge(
      iform_map_get_map_parameters(),
      [
        // Records
        [
          'name' => 'exclude_rejected',
          'caption' => 'Exclude rejected records',
          'description' => 'Indicates whether or not to exclude rejected records from atlas data.',
          'type' => 'boolean',
          'required' => TRUE,
          'default'  => TRUE,
          'group' => 'Record selection',
        ],
        [
          'name' => 'exclude_unverified',
          'caption' => 'Exclude unverified records',
          'description' => 'Indicates whether or not to exclude unverified records from atlas data.',
          'type' => 'boolean',
          'required' => TRUE,
          'default'  => FALSE,
          'group' => 'Record selection',
        ],
        // Mapping
        [
          'name' => 'map_types',
          'caption' => 'Map types to include',
          'description' => 'Indicates which map types will appear in selector. Space separated list of any of the following: standard, timeslice, tetradfreq, density.',
          'type' => 'string',
          'required' => TRUE,
          'default' => 'standard timeslice tetradfreq',
          'group' => 'Atlas mapping options',
        ],
        [
          'name' => 'time_colour1',
          'caption' => 'Colour 1',
          'description' => 'Indicates the colour 1 for the timeslice map (any CSS format).',
          'type' => 'string',
          'required' => TRUE,
          'default'  => '#1b9e77',
          'group' => 'Atlas mapping options',
        ],
        [
          'name' => 'time_colour2',
          'caption' => 'Colour 2',
          'description' => 'Indicates the colour 2 for the timeslice map (any CSS format).',
          'type' => 'string',
          'required' => TRUE,
          'default' => '#7570b3',
          'group' => 'Atlas mapping options',
        ],
        [
          'name' => 'time_colour3',
          'caption' => 'Colour 3',
          'description' => 'Indicates the colour 3 for the timeslice map (any CSS format).',
          'type' => 'string',
          'required' => TRUE,
          'default' => '#d95f02',
          'group' => 'Atlas mapping options',
        ],
        [
          'name' => 'time_thresh1',
          'caption' => 'Threshold 1',
          'description' => 'Indicates the first year threshold for the timeslice map.',
          'type' => 'int',
          'required' => TRUE,
          'default' => 1999,
          'group' => 'Atlas mapping options',
        ],
        [
          'name' => 'time_thresh2',
          'caption' => 'Threshold 2',
          'description' => 'Indicates the second year threshold for the timeslice map.',
          'type' => 'int',
          'required' => TRUE,
          'default' => 2009,
          'group' => 'Atlas mapping options',
        ],
        [
          'name' => 'time_highstyle',
          'caption' => 'Highlight CSS styling',
          'description' => 'Indicates CSS style for highlighted dots on the timeslice map.',
          'type' => 'string',
          'required' => TRUE,
          'default' => 'fill-opacity: 1; fill: black',
          'group' => 'Atlas mapping options',
        ],
        [
          'name' => 'time_lowstyle',
          'caption' => 'Lowlight CSS styling',
          'description' => 'Indicates CSS style for lowlighted dots on the timeslice map.',
          'type' => 'string',
          'required' => TRUE,
          'default' => 'fill-opacity: 0.2',
          'group' => 'Atlas mapping options',
        ],
        [
          'name' => 'time_legendmouse',
          'caption' => 'Legend interactvity behaviour',
          'description' => 'Indicates the interactive behaviour when user clicks on the timeslice map legend: none, mouseclick or mousemove.',
          'type' => 'string',
          'required' => TRUE,
          'default' => 'mouseclick',
          'group' => 'Atlas mapping options',
        ],
        [
          'name' => 'map_height',
          'caption' => 'Map height',
          'description' => 'Indicates the height of the overview and zoom map in pixels.',
          'type' => 'int',
          'required' => TRUE,
          'default' => 600,
          'group' => 'Atlas mapping options',
        ],
        [
          'name' => 'map_download',
          'caption' => 'Include map image download',
          'description' => 'Indicates whether or not to a button to allow users to download altas map image.',
          'type' => 'boolean',
          'required' => TRUE,
          'default' => FALSE,
          'group' => 'Atlas mapping options',
        ],
        [
          'name' => 'download_info',
          'caption' => 'Include standard info on downloaded map',
          'description' => 'Includes standard information to include in footer downloaded maps such as date and URL.',
          'type' => 'boolean',
          'required' => TRUE,
          'default' => TRUE,
          'group' => 'Atlas mapping options',
        ],
        [
          'name' => 'download_text',
          'caption' => 'Configurable text for downloaded map',
          'description' => 'Specifies text to include in the footer of downloaded maps.',
          'type' => 'textarea',
          'required' => FALSE,
          'group' => 'Atlas mapping options',
        ],
        // Other atlas options
		[
		  'name' => 'tab_types',
		  'caption' => 'Optional atlas tabs to include',
		  'description' => 'Indicates which optional tabs will appear. Space separated list of any of the following: temporal data.',
		  'type' => 'string',
		  'required' => TRUE,
		  'default' => 'temporal data', // ✅ Include Data tab by default
		  'group' => 'Other atlas options',
		],
        [
          'name' => 'accounts_location',
          'caption' => 'Path to taxon accounts',
          'description' => 'Specifies the path to the species accounts files.',
          'type' => 'text_input',
          'required' => TRUE,
          'default' => '/sites/default/files/atlas/accounts',
          'group' => 'Other atlas options',
        ],
        [
          'name' => 'conservation_csv',
          'caption' => 'Path to conservation designations',
          'description' => 'Specifies the path to the CSV derived from the JNCC designation spreadsheet.',
          'type' => 'text_input',
          'required' => TRUE,
          'default' => '/sites/default/files/atlas/conservation/taxon-designations-20231206.csv',
          'group' => 'Other atlas options',
        ],
        // Taxon selectors
        [
          'fieldname' => 'list_id',
          'label' => 'Taxon list',
          'helpText' => 'The species list from whihc the species will be selected.',
          'type' => 'select',
          'table' => 'taxon_list',
          'valueField' => 'id',
          'captionField' => 'title',
          'required' => TRUE,
          'default' => 15,
          'group' => 'Species selection',
          'siteSpecific'  => TRUE,
        ],
        // [
        //   'fieldname' => 'list_id_spider',
        //   'label' => 'Spider Species List',
        //   'helpText' => 'The species list that spider species can be selected from.',
        //   'type' => 'select',
        //   'table' => 'taxon_list',
        //   'valueField' => 'id',
        //   'captionField' => 'title',
        //   'required' => FALSE,
        //   'group' => 'Species selection',
        //   'siteSpecific'  => TRUE,
        // ],
        // [
        //   'fieldname' => 'list_id_harvestman',
        //   'label' => 'harvestman Species List',
        //   'helpText' => 'The species list that harvestman species can be selected from.',
        //   'type' => 'select',
        //   'table' => 'taxon_list',
        //   'valueField' => 'id',
        //   'captionField' => 'title',
        //   'required' => FALSE,
        //   'group' => 'Species selection',
        //   'siteSpecific'  => TRUE,
        // ],
        // [
        //   'fieldname' => 'list_id_pseudoscorpion',
        //   'label' => 'Pseudoscorpion Species List',
        //   'helpText' => 'The species list that pseudoscorpion species can be selected from.',
        //   'type' => 'select',
        //   'table' => 'taxon_list',
        //   'valueField' => 'id',
        //   'captionField' => 'title',
        //   'required' => FALSE,
        //   'group' => 'Species selection',
        //   'siteSpecific'  => TRUE,
        // ],
        [
          'name' => 'species_filter_mode',
          'caption' => 'Filter mode to restrict taxa that appear in the list',
          'description' => 'Select a filter mode to restrict the taxa that appear in the taxon list.',
          'type' => 'select',
          'options' => [
            'preferred' => 'Use only preferred names in the list',
            'excludeSynonyms' => 'All names except synonyms (non-preferred latin names) are included',
            'currentLanguage' => 'Only names in the language identified by the language option are included',
          ],
          'blankText' => 'No restriction',
          'required' => FALSE,
          'group' => 'Species selection',
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
        [
          'name' => 'species_include_taxon_group',
          'caption' => 'Include taxon group name in species autocomplete and added rows',
          'description' => 'When using a species grid with the ability to add
            new rows, the autocomplete control by default shows just the
            searched taxon name in the drop down. Set this to include the taxon
            group title.  This also controls the label when adding a new taxon
            row into the grid.',
          'type' => 'boolean',
          'required' => FALSE,
          'group' => 'Species selection',
        ],
        [
          'name' => 'species_include_id_diff',
          'caption' => 'Include identification_difficulty icons in species
            autocomplete and added rows',
          'description' => 'Use data cleaner identification difficulty rules to
            generate icons indicating when ' .
          'hard to ID taxa have been selected.',
          'type' => 'boolean',
          'required' => FALSE,
          'default'  => TRUE,
          'group' => 'Species selection',
        ],
        [
          'name' => 'auth_method',
          'caption' => 'Elasticsearch authentication method',
          'description' => 'Authentication approach used to connect to the Elasticsearch warehouse proxy.',
          'type' => 'select',
          'options' => [
            'directClient' => 'Authenticate as a client configured in the Warehouse REST API',
            'directWebsite' => 'Authenticate as a website registered on the Warehouse',
            'jwtUser' => 'Authenticate as the logged in user using Java Web Tokens',
          ],
          'blankText' => '- Use site-wide configuration -',
          'group' => 'Elasticsearch settings',
        ],
        [
          'name' => 'endpoint',
          'caption' => 'Endpoint',
          'description' => 'Elasticsearch endpoint declared in the REST API. Alternatively, leave this blank to use the site wide setting on the Indicia configuration settings page.',
          'type' => 'text_input',
          'group' => 'Elasticsearch settings',
          'required' => FALSE,
        ],
        [
          'name' => 'alternative_endpoints',
          'caption' => 'Alternative endpoints',
          'description' => 'If this page allows sources to access any alternative Elasticsearch endpoints (e.g. for samples data), then specify the endpoints here, one per line.',
          'type' => 'textarea',
          'group' => 'Elasticsearch settings',
          'required' => FALSE,
        ],
        [
          'name' => 'scope',
          'caption' => 'Data scope (for identifying which websites will share records)',
          'description' => 'Scope or sharing mode. Not used when authenticating as a client configured in the Warehouse REST API.',
          'type' => 'select',
          'options' => [
            'reporting' => lang::get('Reporting'),
            'verification' => lang::get('Verification'),
            'data_flow' => lang::get('Data-flow'),
            'moderation' => lang::get('Moderation'),
            'peer_review' => lang::get('Peer review'),
            'user' => lang::get('My records'),
//            'public' => lang::get('Public'),
          ],
          'required' => TRUE,
          'group' => 'Elasticsearch settings',
        ],
        [
          'name' => 'user',
          'caption' => 'User',
          'description' => 'REST API client with Elasticsearch access when authentication as a client configured in the Warehouse REST API. Alternatively, leave this blank to use the site wide setting on the Indicia configuration settings page.',
          'type' => 'text_input',
          'group' => 'Elasticsearch settings',
          'required' => FALSE,
        ],
        [
          'name' => 'secret',
          'caption' => 'Secret',
          'description' => 'REST API user secret when authentication as a client configured in the Warehouse REST API. Alternatively, leave this blank to use the site wide setting on the Indicia configuration settings page.',
          'type' => 'text_input',
          'group' => 'Elasticsearch settings',
          'required' => FALSE,
        ],
        [
          'name' => 'warehouse_prefix',
          'caption' => 'Warehouse ID prefix',
          'description' => 'Prefix given to numeric IDs to make them unique on the index.',
          'type' => 'text_input',
          'group' => 'Elasticsearch settings',
          'required' => FALSE,
        ],
        [
          'name' => 'refresh_timer',
          'caption' => 'Automatic reload seconds',
          'description' => 'Set this value to the number of seconds you want to elapse before the report will be automatically reloaded, useful for ' .
          'displaying live data updates at BioBlitzes. Combine this with Page to reload to define a sequence of pages that load in turn.',
          'type' => 'int',
          'required' => FALSE,
        ],
        [
          'name' => 'load_on_refresh',
          'caption' => 'Page to reload',
          'description' => 'Provide the full URL of a page to reload after the number of seconds indicated above.',
          'type' => 'string',
          'required' => FALSE,
        ],
      ]
    );
    return $retVal;
  }

  /**
   * Override the getHidden function.
   */
  protected static function getHidden() {
    return NULL;
  }

  /**
   * Override the getMode function.
   */
  protected static function getMode() {
    return [];
  }

  /**
   * Override the getAttributes function.
   */
  protected static function getAttributes() {
    return [];
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
    $enabled = ElasticsearchReportHelper::enableElasticsearchProxy($nid);
    if ($enabled) {
      return parent::get_form($args, $nid);
    }
    global $indicia_templates;
    return str_replace('{message}', lang::get('This page cannot be accessed due to the server being unavailable.'), $indicia_templates['warningBox']);
  }

  /**
   * Override the get_form_html function.
   */
  protected static function get_form_html($args, $auth, $attributes) {

    // For the atlas, the controls and layout interface are not configurable
    // and are set here.
    $args['interface'] = 'one_page';
    $args['structure'] = '[atlas]';

    // In Drupal 9, markup cannot be used in page title, so remove em tags.
    // $repArray = ['<em>', '</em>'];
    // $preferredClean = str_replace($repArray, '', self::$preferred);
    // $titleName = isset(self::$defaultCommonName) ? self::$defaultCommonName . " ($preferredClean)" : $preferredClean;
    hostsite_set_page_title(' ');

    // Set up object for JS data
    iform_load_helpers(array('report_helper'));
    report_helper::$javascript.="indiciaData.basAtlas={};";
    report_helper::$javascript.="indiciaData.basAtlas.taxon={};";
    report_helper::$javascript.="indiciaData.basAtlas.taxon.commonNames=[];";
    report_helper::$javascript.="indiciaData.basAtlas.taxon.synonyms=[];";
    report_helper::$javascript.="indiciaData.basAtlas.map = {};";
    report_helper::$javascript.="indiciaData.basAtlas.other = {};";
    report_helper::$javascript.="indiciaData.basAtlas.other.vcs = [];";
    report_helper::$javascript.="indiciaData.basAtlas.data = {};";

    report_helper::$javascript.="indiciaData.basAtlas.other.accounts_location = '" . $args['accounts_location'] . "';";
    report_helper::$javascript.="indiciaData.basAtlas.other.conservation_csv = '" . $args['conservation_csv'] . "';";
    report_helper::$javascript.="indiciaData.basAtlas.other.tab_types = '" . $args['tab_types'] . "';";




		if (!empty($_GET['os'])) {
			$osGridRef = $_GET['os'];
			$esFilter .= self::createEsFilterHtml('location.grid_reference', $osGridRef, 'match_phrase', 'must');
			$esFilter .= self::createEsFilterHtml('taxon.order', self::$orders[$tg]['esFilter'], 'match_phrase', 'must');
			$esFilter .= self::createEsFilterHtml('taxon.accepted_taxon_id', self::$externalKey, 'match_phrase', 'must');	
			      return $esFilter . parent::get_form_html($args, $auth, $attributes);
		}
		


    // Set global flag if no taxon is specified in URL or form.
    if (empty($_GET['taxa_taxon_list_id']) &&
      empty($_GET['taxon_meaning_id']) &&
      empty($_GET['external_key']) &&
      empty($_GET['occurrence:taxa_taxon_list_id']) ) {
      self::$notaxon = TRUE;
    } else {
      self::$notaxon = FALSE;
    }

    // Apply options and build species autocomplete formatting function
    $opts = [
      'speciesIncludeAuthorities' => isset($args['species_include_authorities']) ?
        $args['species_include_authorities'] : FALSE,
      'speciesIncludeBothNames' => $args['species_include_both_names'],
      'speciesIncludeTaxonGroup' => $args['species_include_taxon_group'],
      'speciesIncludeIdDiff' => $args['species_include_id_diff'],
    ];
    data_entry_helper::build_species_autocomplete_item_function($opts);

    // Datasets
    $ds='';
	$tg='';
	if (isset($_GET['ds'])) {
		$ds = $_GET['ds'];
	}
	if(isset($_GET['tg'])) {
		$tg = $_GET['tg'];
	}
    $tlid = $_GET['taxa_taxon_list_id'];
    $esFilter = '';
	
    if ((self::$notaxon && $tlid != 'grp') || is_null($ds)) {
      return parent::get_form_html($args, $auth, $attributes);
    } else {
      // Get information on species names
      self::getNames($auth);

      // VC filter
      $vcId = $_GET['vc'];
      if ($vcId) {
        $esFilter = self::vcFilter($vcId);
      }

      // Add any general ES taxon filters for taxon/group specified in URL.
      if ($tlid == 'grp') {
        $esFilter .= self::createEsFilterHtml('taxon.order', self::$orders[$tg]['esFilter'], 'match_phrase', 'must');
      } else {
        $esFilter .= self::createEsFilterHtml('taxon.accepted_taxon_id', self::$externalKey, 'match_phrase', 'must');
      }

      // Filter to exclude zero abundance records
      $esFilter .= self::createEsFilterHtml('occurrence.zero_abundance', 'false', 'term', 'must');

      // Exclude rejected records in ES queries?
      if($args['exclude_rejected'] == "1") {
        $esFilter .= self::createEsFilterHtml('identification.verification_status', 'R', 'term', 'must_not');
      };
      // Exclude unverified records in ES queries?
      if($args['exclude_unverified'] == "1") {
        $esFilter .= self::createEsFilterHtml('identification.verification_status', 'V', 'term', 'must');
      };


      // Dataset filters
      $dsa = str_split($ds);
      $srs = $dsa[0]; // 729
      $irec = $dsa[1]; // All other datasets available
      $inat = $dsa[2]; // 510
	  $ver  = $dsa[3];  // verified 
      if ($irec == "1") {
        if ($srs == "0") {
          $esFilter .= self::createEsFilterHtml('metadata.survey.id', '729', 'term', 'must_not');
        }
        if ($inat == "0") {
          $esFilter .= self::createEsFilterHtml('metadata.survey.id', '510', 'term', 'must_not');
        }
      } else {
        if ($srs == "1" && $inat == "1") {
          $esFilter .= self::createEsFilterHtml('metadata.survey.id', '[729, 510]', 'terms', 'must');
        } else if ($srs == "1") {
          $esFilter .= self::createEsFilterHtml('metadata.survey.id', '729', 'term', 'must');
        } else if ($inat == "1") {
          $esFilter .= self::createEsFilterHtml('metadata.survey.id', '510', 'term', 'must');
        }
      }
	  
	  if ($ver=="1") {
        $esFilter .= self::createEsFilterHtml('identification.verification_status', 'V', 'term', 'must');		  
	  }



      return $esFilter . parent::get_form_html($args, $auth, $attributes);
    }
  }

  /**
   * Obtains details of all names for this species from the database.
   */
  protected static function getNames($auth) {

    // Don't run this if
    if ($_GET['taxa_taxon_list_id'] == 'grp') {
      report_helper::$javascript.="indiciaData.basAtlas.taxon.preferred='All ". self::$orders[$_GET['tg']]['tcFilter'] ."';";
      return;
    }

    iform_load_helpers(['report_helper']);
    $preferred = lang::get('Unknown');
    // Get all the different names for the species.
    $extraParams = ['sharing' => 'reporting'];
    if (isset($_GET['taxa_taxon_list_id'])) {
      $extraParams['taxa_taxon_list_id'] = $_GET['taxa_taxon_list_id'];
      $taxaTaxonListId = $_GET['taxa_taxon_list_id'];
    }
    elseif (isset($_GET['occurrence:taxa_taxon_list_id'])) {
      $extraParams['taxa_taxon_list_id'] = $_GET['occurrence:taxa_taxon_list_id'];
      $taxaTaxonListId = $_GET['occurrence:taxa_taxon_list_id'];
    }
    elseif (isset($_GET['taxon_meaning_id'])) {
      $extraParams['taxon_meaning_id'] = $_GET['taxon_meaning_id'];
      $taxonMeaningId = $_GET['taxon_meaning_id'];
    }
    elseif (isset($_GET['external_key'])) {
      $extraParams['external_key'] = $_GET['external_key'];
      self::$externalKey = $_GET['external_key'];
    }
    $species_details = report_helper::get_report_data([
      'readAuth' => $auth['read'],
      'class' => 'species-details-fields',
      'dataSource' => 'library/taxa/taxon_names_2',
      'extraParams' => $extraParams,
    ]);

    $synonyms = [];
    $commonNames = [];
    foreach ($species_details as $speciesData) {
      if ($speciesData['preferred'] === 't') {
        $preferred = $speciesData['taxon'];
        $preferredPlain = $speciesData['taxon_plain'];
        if (!isset($taxonMeaningId)) {
          $taxonMeaningId = $speciesData['taxon_meaning_id'];
        }
        if (!isset($taxaTaxonListId)) {
          $taxaTaxonListId = $speciesData['id'];
        }
        if (!isset(self::$externalKey)) {
          self::$externalKey = $speciesData['external_key'];
        }
      }
      elseif ($speciesData['language_iso'] === 'lat' && !in_array($speciesData['taxon'], $synonyms)) {
        $synonyms[] = $speciesData['taxon'];
      }
      elseif ($speciesData['language_iso'] !== 'lat' && !in_array($speciesData['taxon'], $commonNames)) {
        $commonNames[] = $speciesData['taxon'];
      }
      if (!isset($defaultCommonName) && !empty($speciesData['default_common_name'])) {
        $defaultCommonName = $speciesData['default_common_name'];
      }
    }
    // Remove default common name from $commonNames array.
    if (isset($defaultCommonName)) {
      if (($key = array_search($defaultCommonName, $commonNames)) !== FALSE) {
        unset($commonNames[$key]);
      }
    }

    // Make all the name information available in JS
    iform_load_helpers(array('report_helper'));
    report_helper::$javascript.="indiciaData.basAtlas.taxon.notaxon=".var_export(self::$notaxon, true).";";
    report_helper::$javascript.="indiciaData.basAtlas.taxon.preferred='". addslashes($preferred) ."';";
    report_helper::$javascript.="indiciaData.basAtlas.taxon.preferredPlain='". addslashes($preferredPlain) ."';";
	if (!empty($defaultCommonName) ) {
		report_helper::$javascript.="indiciaData.basAtlas.taxon.defaultCommonName='". addslashes($defaultCommonName) ."';";
	}
	
    foreach ($commonNames as $key => $value) {
      $name = addslashes($value);
      report_helper::$javascript.="indiciaData.basAtlas.taxon.commonNames.push('".$name."');";
    }
    foreach ($synonyms as $key => $value) {
      $name = addslashes($value);
      report_helper::$javascript.="indiciaData.basAtlas.taxon.synonyms.push('".$name."');";
    }
    report_helper::$javascript.="indiciaData.basAtlas.taxon.taxaTaxonListId='".$taxaTaxonListId."';";
    report_helper::$javascript.="indiciaData.basAtlas.taxon.taxonMeaningId='".$taxonMeaningId."';";
    report_helper::$javascript.="indiciaData.basAtlas.taxon.externalKey='".self::$externalKey."';";
  }

  /**
   * Atlas control - contains everything else.
   *
   * @return string
   *   The atlas.
   */
  protected static function get_control_atlas($auth, $args, $tabalias, $options) {


	/*if (!empty($_GET['os'])) {
		$r=self::get_filtered_occurrences($args, $options);
		echo "<pre>$r</pre>";exit;
	}*/

    $r='<div id="bas-atlas-main">';

    $r.='<div id="bas-atlas-controls">';

    $r.='<div id="bas-atlas-species">';

    $r.=self::areaSelection($auth);
    $r.=self::taxonGroupSelection();
    $r.=self::groupSpeciesRadio();
    $r.=self::get_control_species($auth, $args, $tabalias, $options);

    $r .= '<form method="GET" id="taxon-search-form">';
    $r .= '<input type="hidden" id="taxa_taxon_list_id" name="taxa_taxon_list_id"></input>';
    $r .= '<input type="hidden" id="ds" name="ds"></input>';
    $r .= '<input type="hidden" id="tg" name="tg"></input>';
    $r .= '<input type="hidden" id="vc" name="vc"></input>';
    $r .= '</form>';
    $r .= '<button id="submit-taxon-search-form" type="button" class="btn btn-primary btn-block" onclick="indiciaFns.speciesDetailsSub()">Update</button>';

    $r.='</div>'; // bas-atlas-species


    $r.='<div id="bas-atlas-other-controls"></div>';
    $r.='</div>'; // bas-atlas-controls
    $r.='<div id="bas-atlas-tabs"></div>';
    $r.='</div>'; // bas-atlas-main

    $r.=self::get_atlas_data($args, $options);

    iform_load_helpers(array('report_helper'));
    report_helper::$javascript.="indiciaFns.createAtlas();";

    return $r;
  }

  protected static function get_atlas_data1($args, $options) {

    // Make options etc available in JS - also providing defaults
    iform_load_helpers(array('report_helper'));
    report_helper::$javascript.="indiciaData.basAtlas.map.mapTypes = '" . $args['map_types'] . "';";
    report_helper::$javascript.="indiciaData.basAtlas.map.colour1 = '" . $args['time_colour1'] . "';";
    report_helper::$javascript.="indiciaData.basAtlas.map.colour2 = '" . $args['time_colour2'] . "';";
    report_helper::$javascript.="indiciaData.basAtlas.map.colour3 = '" . $args['time_colour3'] . "';";
    report_helper::$javascript.="indiciaData.basAtlas.map.thresh1 =" . $args['time_thresh1'] . ";";
    report_helper::$javascript.="indiciaData.basAtlas.map.thresh2 =" . $args['time_thresh2'] . ";";
    report_helper::$javascript.="indiciaData.basAtlas.map.highstyle = '" . $args['time_highstyle'] . "';";
    report_helper::$javascript.="indiciaData.basAtlas.map.lowstyle = '" . $args['time_lowstyle'] . "';";
    report_helper::$javascript.="indiciaData.basAtlas.map.legendmouse = '" . $args['time_legendmouse'] . "';";
    report_helper::$javascript.="indiciaData.basAtlas.map.mapheight =" . $args['map_height'] . ";";
    report_helper::$javascript.="indiciaData.basAtlas.map.download =" . $args['map_download']. ";";
    report_helper::$javascript.="indiciaData.basAtlas.map.downloadtext = '" . $args['download_text'] . "';";
    report_helper::$javascript.="indiciaData.basAtlas.map.downloadinfo =" . $args['download_info'] . ";";

    report_helper::$javascript.="indiciaData.basAtlas.data.exclude_rejected=". $args['exclude_rejected'] . ";";
    report_helper::$javascript.="indiciaData.basAtlas.data.exclude_unverified=" . $args['exclude_unverified'] . ";";

    // 'download' => 'false',

    // Set up the data source and custom script

    data_entry_helper::add_resource('brc_atlas');

    // Return now if no taxon specified
    if (self::$notaxon) {
      return $r;
    }



$vc = filter_input(INPUT_GET, 'vc', FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);

// Ensure extraParams exists and is an array
$extraParams = isset($options['extraParams']) && is_array($options['extraParams'])
  ? $options['extraParams']
  : [];

// Ensure we have a consistently shaped ES DSL container:
// extraParams['query']['bool']['filter'] is an array we can push into.
if (empty($extraParams['query']) || !is_array($extraParams['query'])) {
  $extraParams['query'] = [];
}
if (empty($extraParams['query']['bool']) || !is_array($extraParams['query']['bool'])) {
  $extraParams['query']['bool'] = [];
}
if (empty($extraParams['query']['bool']['filter']) || !is_array($extraParams['query']['bool']['filter'])) {
  $extraParams['query']['bool']['filter'] = [];
}

// If vc > 0, add the precision filter as a filter clause (non-scoring)
if ($vc > 0) {
  $extraParams['query']['bool']['filter'][] = [
    'range' => [
      'location.coordinate_uncertainty_in_meters' => [
        'lte' => 2000 // change to 1000 for 1km-only
      ]
    ]
  ];
}

// Put back into $options
$options['extraParams'] = $extraParams;


	// $options['extraParams'] = array_merge($options['extraParams'] ?? [], $precisionFilter);
    
	$optionsSourceMap = [
      'extraParams' => $options['extraParams'],
      'nid' => $options['nid'],
      'id' => 'mapSource',
      'mode' => 'compositeAggregation',
      'uniqueField' => $_GET['vc'] ? 'location.grid_square.2km.centre' : 'location.grid_square.10km.centre',
      'aggregation' => [
        'minYear' => ['min' => ['field' => 'event.date_start']],
        'maxYear' => ['max' => ['field' => 'event.date_end']],
      ],
      'proxyCacheTimeout' => 300,
    ];


		// Field to inspect: OSGB-style display spatial reference
		$gridrefField = 'location.output_sref';

		if (!$_GET['vc'] ) {
		  // For national hectad maps, count the number of tetrads in each hectad
		  $optionsSourceMap['aggregation']['tetrads'] = ['cardinality' => ['field' => 'location.grid_square.2km.centre']];
		}

    ElasticsearchReportHelper::source($optionsSourceMap);
    $optionsCustomScriptMap = [
      'extraParams' => $options['extraParams'],
      'nid' => $options['nid'],
      'source' => 'mapSource',
      'functionName' => 'populateMap',
    ];
	
    $r = ElasticsearchReportHelper::customScript($optionsCustomScriptMap);


    if(str_contains($args['tab_types'], 'temporal')) {

      data_entry_helper::add_resource('brc_charts');

      $optionsSourceYearly = [
        'extraParams' => $options['extraParams'],
        'nid' => $options['nid'],
        'id' => 'recsbyyearSource',
        'size' => 0,
        'mode' => 'compositeAggregation',
        'uniqueField' => 'event.year',
        'fields' => ['event.year'],
        'proxyCacheTimeout' => 300,
      ];
	  
      ElasticsearchReportHelper::source($optionsSourceYearly);
      $optionsCustomScriptYearly = [
        'extraParams' => $options['extraParams'],
        'nid' => $options['nid'],
        'source' => 'recsbyyearSource',
        'functionName' => 'populateRecsByYearChart',
      ];
      $r .= ElasticsearchReportHelper::customScript($optionsCustomScriptYearly);

      $optionsSourceWeekly = [
        'extraParams' => $options['extraParams'],
        'nid' => $options['nid'],
        'id' => 'recsthroughyearSource',
        'size' => 0,
        'mode' => 'compositeAggregation',
        'uniqueField' => 'event.week',
        'fields' => ['event.week'],
        'proxyCacheTimeout' => 300,
      ];
	  
      ElasticsearchReportHelper::source($optionsSourceWeekly);
      $optionsCustomScriptWeekly = [
        'extraParams' => $options['extraParams'],
        'nid' => $options['nid'],
        'source' => 'recsthroughyearSource',
        'functionName' => 'populateRecsThroughYearChart',
      ];
      $r .= ElasticsearchReportHelper::customScript($optionsCustomScriptWeekly);
    }


	if (str_contains($args['tab_types'], 'data')) {
		data_entry_helper::add_resource('fancyDialog'); // Required for ES error handling
		data_entry_helper::add_resource('brc_data');    // Optional for your custom tab


			$optionsSourceSpecies = [
				'extraParams'       => $options['extraParams'],
				'nid'               => $options['nid'],
				'id'                => 'speciesListSource',
				'size'              => 0,
				'mode'              => 'compositeAggregation',
				'uniqueField'       => 'taxon.accepted_name.keyword', // ✅ Use keyword field
				'fields'            => [
				                          'taxon.accepted_name.keyword'	,
										  'taxon.genus'	,
										  'taxon.species_vernacular.keyword'
									    ],
				'aggregation' => [
					'species' => [
						'terms' => [
							'field' => 'taxon.accepted_name.keyword',
							'size'  => 10000
						],
						'aggs' => [
							'first_record' => [
								'min' => [
									'field' => 'event.date_start'
								]
							],
							'last_record' => [
								'max' => [
									'field' => 'event.date_start'
								]
							]
						]
					]
				],
				'proxyCacheTimeout' => 300,
			];


		// Apply Vice County filter if vc is set
		if (!empty($_GET['vc'])) {
			$optionsSourceSpecies['extraParams']['query']['bool']['filter'][] = [
				'term' => ['location.vice_county' => $_GET['vc']]
			];
		}

		ElasticsearchReportHelper::source($optionsSourceSpecies);

		$optionsCustomScriptSpecies = [
			'extraParams' => $options['extraParams'],
			'nid'         => $options['nid'],
			'source'      => 'speciesListSource',
			'functionName'=> 'populateSpeciesList', // JS function you define
		];

		$r .= ElasticsearchReportHelper::customScript($optionsCustomScriptSpecies);
	}

    return $r;
  }


protected static function get_atlas_data($args, $options) {
  // Make options etc available in JS - also providing defaults
  iform_load_helpers(['report_helper']);
  report_helper::$javascript .= "indiciaData.basAtlas.map.mapTypes = '" . $args['map_types'] . "';";
  report_helper::$javascript .= "indiciaData.basAtlas.map.colour1 = '" . $args['time_colour1'] . "';";
  report_helper::$javascript .= "indiciaData.basAtlas.map.colour2 = '" . $args['time_colour2'] . "';";
  report_helper::$javascript .= "indiciaData.basAtlas.map.colour3 = '" . $args['time_colour3'] . "';";
  report_helper::$javascript .= "indiciaData.basAtlas.map.thresh1 =" . $args['time_thresh1'] . ";";
  report_helper::$javascript .= "indiciaData.basAtlas.map.thresh2 =" . $args['time_thresh2'] . ";";
  report_helper::$javascript .= "indiciaData.basAtlas.map.highstyle = '" . $args['time_highstyle'] . "';";
  report_helper::$javascript .= "indiciaData.basAtlas.map.lowstyle = '" . $args['time_lowstyle'] . "';";
  report_helper::$javascript .= "indiciaData.basAtlas.map.legendmouse = '" . $args['time_legendmouse'] . "';";
  report_helper::$javascript .= "indiciaData.basAtlas.map.mapheight =" . $args['map_height'] . ";";
  report_helper::$javascript .= "indiciaData.basAtlas.map.download =" . $args['map_download'] . ";";
  report_helper::$javascript .= "indiciaData.basAtlas.map.downloadtext = '" . $args['download_text'] . "';";
  report_helper::$javascript .= "indiciaData.basAtlas.map.downloadinfo =" . $args['download_info'] . ";";

  report_helper::$javascript .= "indiciaData.basAtlas.data.exclude_rejected=" . $args['exclude_rejected'] . ";";
  report_helper::$javascript .= "indiciaData.basAtlas.data.exclude_unverified=" . $args['exclude_unverified'] . ";";

  data_entry_helper::add_resource('brc_atlas');

  // Return now if no taxon specified
  if (self::$notaxon) {
    return $r;
  }

  // Validated VC (defaults to 0 = national)
  $vc = filter_input(INPUT_GET, 'vc', FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);

	// Ensure bool_queries exists
	$extraParams = isset($options['extraParams']) && is_array($options['extraParams'])
	  ? $options['extraParams']
	  : [];
	if (!isset($extraParams['bool_queries']) || !is_array($extraParams['bool_queries'])) {
	  $extraParams['bool_queries'] = [];
	}


	if ($vc > 0) {
	  $extraParams['bool_queries'][] = [
		'query_type'  => 'range',
		'field'       => 'location.coordinate_uncertainty_in_meters',
		'value'       => [0, 2000], // 1km-only? set to [0, 1000]
		'bool_clause' => 'filter'
	  ]; 

		$extraParams['bool_queries'][] = [
			'query_type'  => 'nested',
			'path'        => 'location.higher_geography',
			'bool_clause' => 'filter',
			'query'       => [
				'bool' => [
					'filter' => [
						['term' => ['location.higher_geography.id' => $vc]],
						['term' => ['location.higher_geography.type.keyword' => 'Vice County']]
					]
				]
			]
		];

	}


  // Put back into options
  $options['extraParams'] = $extraParams;

  // MAP SOURCE
  $optionsSourceMap = [
    'extraParams'       => $options['extraParams'],
    'nid'               => $options['nid'],
    'id'                => 'mapSource',
    'mode'              => 'compositeAggregation',
    'uniqueField'       => $vc > 0
                            ? 'location.grid_square.2km.centre'
                            : 'location.grid_square.10km.centre',
    'aggregation'       => [
      'minYear' => ['min' => ['field' => 'event.date_start']],
      'maxYear' => ['max' => ['field' => 'event.date_end']],
    ],
    'proxyCacheTimeout' => 300,
  ];



  // For national hectad maps, count number of tetrads in each hectad
  if ($vc <= 0) {
    $optionsSourceMap['aggregation']['tetrads'] = [
      'cardinality' => ['field' => 'location.grid_square.2km.centre']
    ];
    // Optional: if the field is text, use the keyword subfield:
    // 'cardinality' => ['field' => 'location.grid_square.2km.centre.keyword']
  }

  ElasticsearchReportHelper::source($optionsSourceMap);

  $optionsCustomScriptMap = [
    'extraParams'  => $options['extraParams'],
    'nid'          => $options['nid'],
    'source'       => 'mapSource',
    'functionName' => 'populateMap',
  ];
  $r = ElasticsearchReportHelper::customScript($optionsCustomScriptMap);

  // TEMPORAL TABS
  if (str_contains($args['tab_types'], 'temporal')) {
    data_entry_helper::add_resource('brc_charts');

    $optionsSourceYearly = [
      'extraParams'       => $options['extraParams'],
      'nid'               => $options['nid'],
      'id'                => 'recsbyyearSource',
      'size'              => 0,
      'mode'              => 'compositeAggregation',
      'uniqueField'       => 'event.year',
      'fields'            => ['event.year'],
      'proxyCacheTimeout' => 300,
    ];
    ElasticsearchReportHelper::source($optionsSourceYearly);

    $optionsCustomScriptYearly = [
      'extraParams'  => $options['extraParams'],
      'nid'          => $options['nid'],
      'source'       => 'recsbyyearSource',
      'functionName' => 'populateRecsByYearChart',
    ];
    $r .= ElasticsearchReportHelper::customScript($optionsCustomScriptYearly);

    $optionsSourceWeekly = [
      'extraParams'       => $options['extraParams'],
      'nid'               => $options['nid'],
      'id'                => 'recsthroughyearSource',
      'size'              => 0,
      'mode'              => 'compositeAggregation',
      'uniqueField'       => 'event.week',
      'fields'            => ['event.week'],
      'proxyCacheTimeout' => 300,
    ];
    ElasticsearchReportHelper::source($optionsSourceWeekly);

    $optionsCustomScriptWeekly = [
      'extraParams'  => $options['extraParams'],
      'nid'          => $options['nid'],
      'source'       => 'recsthroughyearSource',
      'functionName' => 'populateRecsThroughYearChart',
    ];
    $r .= ElasticsearchReportHelper::customScript($optionsCustomScriptWeekly);
  }

  // DATA TAB (species list)
  if (str_contains($args['tab_types'], 'data')) {
    data_entry_helper::add_resource('fancyDialog');
    data_entry_helper::add_resource('brc_data');

    $optionsSourceSpecies = [
      'extraParams'       => $options['extraParams'],
      'nid'               => $options['nid'],
      'id'                => 'speciesListSource',
      'size'              => 0,
      'mode'              => 'compositeAggregation',
      'uniqueField'       => 'taxon.accepted_name.keyword',
      'fields'            => [
        'taxon.accepted_name.keyword',
        'taxon.genus',
        'taxon.species_vernacular.keyword',
      ],
      'aggregation'       => [
        'species' => [
          'terms' => [
            'field' => 'taxon.accepted_name.keyword',
            'size'  => 10000
          ],
          'aggs'  => [
            'first_record' => ['min' => ['field' => 'event.date_start']],
            'last_record'  => ['max' => ['field' => 'event.date_start']],
          ]
        ]
      ],
      'proxyCacheTimeout' => 300,
    ];

    // VC filter for species tab via bool_queries (builder-safe)
    if ($vc > 0) {
      if (!isset($optionsSourceSpecies['extraParams']['bool_queries'])) {
        $optionsSourceSpecies['extraParams']['bool_queries'] = [];
      }
      $optionsSourceSpecies['extraParams']['bool_queries'][] = [
        'query_type'  => 'term',
        'field'       => 'location.vice_county',
        'value'       => $vc,
        'bool_clause' => 'filter'
      ];
    }

    ElasticsearchReportHelper::source($optionsSourceSpecies);

    $optionsCustomScriptSpecies = [
      'extraParams'  => $options['extraParams'],
      'nid'          => $options['nid'],
      'source'       => 'speciesListSource',
      'functionName' => 'populateSpeciesList',
    ];
    $r .= ElasticsearchReportHelper::customScript($optionsCustomScriptSpecies);
  }
  


  return $r;
}



	protected static function get_filtered_occurrences($args, $options) {
	  $filters = [
		['field' => 'location.gridref.keyword', 'value' => $_GET['os'], 'queryType' => 'term']
	  ];

	  if (!empty($_GET['taxa_taxon_list_id'])) {
		$filters[] = ['field' => 'taxon.taxa_taxon_list_id', 'value' => $_GET['taxa_taxon_list_id'], 'queryType' => 'term'];
	  }
	  if (!empty($_GET['ds'])) {
		$filters[] = ['field' => 'dataset.id', 'value' => $_GET['ds'], 'queryType' => 'term'];
	  }
	  if (!empty($_GET['tg'])) {
		$filters[] = ['field' => 'taxon_group.name', 'value' => $_GET['tg'], 'queryType' => 'term'];
	  }


	  $sourceDefinition = [
		'extraParams' => $options['extraParams'],
		'nid' => $options['nid'],
		'id' => 'occurrencesSource',
		'mode' => 'json',
		'fields' => [
		  'sample.date_start',
		  'sample.date_end',
		  'taxon.preferred_name',
		  'entered_sref',
		  'recorded_by',
		  'sample.method',
		  'occurrence.comment'
		],
		'filters' => $filters,
		'proxyCacheTimeout' => 300
	  ];


	  //  Register the source first
	  ElasticsearchReportHelper::source($sourceDefinition);

	  // Now fetch the data
	  $r = ElasticsearchReportHelper::dataGrid( [
		'extraParams' => $options['extraParams'],
		'mode' => 'json',
		'nid' => $options['nid'],
		'source' => 'occurrencesSource',
		'columns' => [
		  ['field' => 'sample.date_start', 'caption' => 'Start Date'],
		  ['field' => 'sample.date_end', 'caption' => 'End Date'],
		  ['field' => 'taxon.preferred_name', 'caption' => 'Species'],
		  ['field' => 'location.gridref', 'caption' => 'Grid Ref'],
		  ['field' => 'recorded_by', 'caption' => 'Recorder'],
		  ['field' => 'sample.method', 'caption' => 'Method'],
		  ['field' => 'occurrence.comment', 'caption' => 'Comment']
		],
		'itemsPerPage' => 1000,
		'sortField' => 'sample.date_start',
		'sortOrder' => 'desc'
	  ]);



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
    $extraParams['taxon_list_id'] = $args['list_id'];

    $species_ctrl_opts = array_merge([
      'fieldname' => 'occurrence:taxa_taxon_list_id',
      'helpText' => "Start typing, select species, click 'Fetch'",
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

    // Filter the taxon list based on the selected group
    $tg = $_GET['tg'] ? $tg = $_GET['tg'] : 'spider';
    $species_ctrl_opts['taxonFilterField']='taxon_group';
    $species_ctrl_opts['taxonFilter']=[self::$orders[$tg]['tcFilter']];

    // Name filter
    if (isset($args['species_filter_mode'])) {
      $species_ctrl_opts['speciesNameFilterMode'] = $args['species_filter_mode'];
    }

    // Dynamically generate the species selection control required.
    $r = call_user_func(['data_entry_helper', 'species_autocomplete'], $species_ctrl_opts);

   return $r;
  }

  protected static function areaSelection($auth) {

    // Get VCs
    iform_load_helpers(['report_helper']);
    $vcs = report_helper::get_report_data([
      'readAuth' => $auth['read'],
      'dataSource' => 'projects/bas/list-vcs',
    ]);
    // Delegate creation of options to JS
    foreach ($vcs as $vc) {
      report_helper::$javascript.="indiciaData.basAtlas.other.vcs.push({id:".$vc["id"].",code:'".$vc["code"]."',name:'".$vc["name"]."'});";
    }
    $r = <<<HTML
<select id="map-area-selector" class="form-control"></select>

HTML;

    return $r;
  }

  protected static function taxonGroupSelection() {
    $r = <<<HTML
<select id="taxon-list-selection" class="form-control">
<option value="spider">Spiders</option>
<option value="harvestman">Harvestmen</option>
<option value="pseudoscorpion">Pseudoscorpions</option>
</select>

HTML;

    return $r;
  }

  protected static function groupSpeciesRadio() {
    $r = <<<HTML
<div id="species-group-switch">
<label style="width: 40px">Map:</label>
<input type="radio" id="switch-species" name="species-group-switch" value="species">
<label for="switch-species" style="width: 60px">Species</label>
<input type="radio" id="switch-group" name="species-group-switch" value="group">
<label for="switch-group" style="width: 60px">Group</label>
</div>

HTML;

    return $r;
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
   * Make a hidden input specifing a page-wide nested filter on VC id for ES queries.
   *
   * @return string
   *   Hidden input HTML.
   */
  protected static function vcFilter($id) {
    $r = <<<HTML
  <input type="hidden" class="es-filter-param" id="select-dataset-area"
  data-es-bool-clause="must" value="$id"
  data-es-query="{&quot;nested&quot;:{&quot;path&quot;:&quot;location.higher_geography&quot;,&quot;query&quot;:{&quot;bool&quot;:{&quot;must&quot;:[{&quot;match&quot;:{&quot;location.higher_geography.id&quot;:&quot;#value#&quot;}}]}}}}" />
HTML;
    return $r;
  }
}