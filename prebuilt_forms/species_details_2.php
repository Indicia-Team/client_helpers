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
 * @link https://github.com/Indicia-Team/client_helpers
 */

require_once 'includes/BaseDynamicDetails.php';
require_once 'includes/report.php';

/**
 * Displays the details of a single taxon.
 *
 * Takes an taxon identifier in the URL which can be either:
 * - taxa_taxon_list_id
 * - taxon_meaning_id
 * - external_key (tvk)
 *
 * Displays the following using a configurable page template:
 * - Species Details including custom attributes.
 * - An Explore Species' Records button that links to a custom URL.
 * - Any photos of occurrences with the same meaning as the taxon.
 * - A list of recording schemes & societies associated by the taxon (in the UKSI).
 * - A map displaying occurrences of taxa with the same meaning as the taxon.
 * - A bar chart indicating the accummulation of records over time.
 * - A phenology chart showing the accummulation of records through the year.
 */
class iform_species_details_2 extends BaseDynamicDetails {

  /**
   * Stores a value to indicate of no taxon identified.
   *
   * @var bool
   */
  private static $notaxon;

  /**
   * Stores the preferred name of the taxon with markup and authority.
   *
   * @var string
   */
  private static $preferred;

  /**
   * Stores the preferred name of the taxon.
   *
   * @var string
   */
  private static $preferredPlain;

  /**
   * Stores the default common name of the taxon.
   *
   * @var string
   */
  private static $defaultCommonName;

  /**
   * Stores the synonyms of the taxon.
   *
   * @var array
   */
  private static $synonyms = [];

  /**
   * Stores the common names of the taxon.
   *
   * @var array
   */
  private static $commonNames = [];

  /**
   * Stores the taxonomy of the taxon.
   *
   * @var array
   */
  private static $taxonomy = [];

  /**
   * Stores the taxa_taxon_list_id of the taxon.
   *
   * @var int
   */
  private static $taxaTaxonListId;

  /**
   * Stores the taxon_meaning_id of the taxon.
   *
   * @var int
   */
  private static $taxonMeaningId;

  /**
   * Stores the exter_key of the taxon.
   *
   * @var string
   */
  private static $externalKey;

  /**
   * The definition of the form.
   *
   * @return array
   *   An array of required information.
   */
  public static function get_species_details_2_definition() {
    return [
      'title' => 'View details of a species (2)',
      'category' => 'Utilities',
      'description' => 'A summary view of a species including records. Pass a parameter in the URL called taxon, ' .
      'containing a taxa_taxon_list_id which defines which species to show.',
      'recommended' => TRUE,
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
        [
          'name' => 'interface',
          'caption' => 'Interface Style Option',
          'description' => 'Choose the style of user interface, either dividing the form up onto separate tabs, ' .
          'wizard pages or having all controls on a single page.',
          'type' => 'select',
          'options' => [
            'tabs' => 'Tabs',
            'wizard' => 'Wizard',
            'one_page' => 'All One Page',
          ],
          'default' => 'one_page',
          'group' => 'User Interface',
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
        // List of fields to hide in the Species Details section.
        [
          'name' => 'fields',
          'caption' => 'Fields to include or exclude',
          'description' => 'List of data fields to include/exclude, one per line.' .
          'Type in the field name as seen exactly in the Species Details section. For custom attributes you should use the system function values ' .
          'to filter instead of the caption if defined below. If this is a list of fields to include, for custom fields you can provide ' .
          'a different display name for the attribute, separating it from the actual attribute name with the pipe symbol, e.g. BR Habitats | Broad habitats.',
          'type' => 'textarea',
          'required' => FALSE,
          'default' => '',
          'group' => 'Fields for Species details',
        ],
        [
          'name' => 'operator',
          'caption' => 'Include or exclude',
          'description' => "Do you want to include only the list of fields you've defined, or exclude them?",
          'type' => 'select',
          'options' => [
            'in' => 'Include',
            'not in' => 'Exclude',
          ],
          'default' => 'not in',
          'group' => 'Fields for Species details',
        ],
        [
          'name' => 'testagainst',
          'caption' => 'Test attributes against',
          'description' => 'For custom attributes, do you want to filter the list to show using the caption or the system function? If the latter, then ' .
          'any custom attributes referred to in the fields list above should be referred to by their system function which might be one of: email, ' .
          'cms_user_id, cms_username, first_name, last_name, full_name, biotope, behaviour, reproductive_condition, sex_stage, sex_stage_count, ' .
          'certainty, det_first_name, det_last_name.',
          'type' => 'select',
          'options' => [
            'caption' => 'Caption',
            'system_function' => 'System Function',
          ],
          'default' => 'caption',
          'group' => 'Fields for Species details',
        ],
        // Allows the user to define how the page will be displayed.
        [
          'name' => 'structure',
          'caption' => 'Form Structure',
          'description' => 'Define the structure of the form. Each component must be placed on a new line. <br/>' .
          'The following types of component can be specified. <br/>' .
          "<strong>[control name]</strong> indicates a predefined control is to be added to the form with the following predefined controls available: <br/>" .
          "&nbsp;&nbsp;<strong>[species]</strong> - a taxon selection control.<br/>" .
          "&nbsp;&nbsp;<strong>[speciesdetails]</strong> - displays information relating to the occurrence and its sample<br/>" .
          "&nbsp;&nbsp;<strong>[rss]</strong> - displays a list of recording schemes & societies associated with the taxon<br/>" .
          "&nbsp;&nbsp;<strong>[explore]</strong> - a button “Explore this species' records” which takes you to explore all records, filtered to the species.<br/>" .
          "&nbsp;&nbsp;<strong>[photos]</strong> - photos associated with the occurrence<br/>" .
          "&nbsp;&nbsp;<strong>[hectadmap]</strong> - a hectad distribution overview map for the taxon<br/>" .
          "&nbsp;&nbsp;<strong>[exploremap]</strong> - an interactive leaflet map that links to the spatial reference<br/>" .
          "&nbsp;&nbsp;<strong>[dualemap]</strong> - a toggle that switches between hectadmap and exploremap<br/>" .
          "&nbsp;&nbsp;<strong>[map]</strong> - a map that links to the spatial reference and location<br/>" .
          "&nbsp;&nbsp;<strong>[occurrenceassociations]</strong> - a list of associated species, drawn from the occurrence associations data.<br/>" .
          "<strong>=tab/page name=</strong> is used to specify the name of a tab or wizard page (alpha-numeric characters only). " .
          "If the page interface type is set to one page, then each tab/page name is displayed as a seperate section on the page. " .
          "Note that in one page mode, the tab/page names are not displayed on the screen.<br/>" .
          "<strong>|</strong> is used to split a tab/page/section into two columns, place a [control name] on the previous line and following line to split.<br/>",
          'type' => 'textarea',
          'default' => '
=General=
[speciesdetails]
[rss]
[photos]
[explore]
|
[hectadmap]
[map]',
          'group' => 'User Interface',
        ],
        [
          'name' => 'explore_url',
          'caption' => 'Explore URL',
          'description' => 'When you click on the Explore this species\' records button you are taken to this URL. Use {rootfolder} as a replacement ' .
          'token for the site\'s root URL.',
          'type' => 'string',
          'required' => FALSE,
          'default' => '',
          'group' => 'User Interface',
        ],
        [
          'name' => 'explore_param_name',
          'caption' => 'Explore Parameter Name',
          'description' => 'Name of the parameter added to the Explore URL to pass through the taxon_meaning_id of the species being explored. ' .
          'The default provided (filter-taxon_meaning_list) is correct if your report uses the standard parameters configuration.',
          'type' => 'string',
          'required' => FALSE,
          'default' => 'filter-taxon_meaning_list',
          'group' => 'User Interface',
        ],
        [
          'name' => 'include_layer_list',
          'caption' => 'Include Legend',
          'description' => 'Should a legend be shown on the page?',
          'type' => 'boolean',
          'required' => FALSE,
          'default' => FALSE,
          'group' => 'Other Map Settings',
        ],
        [
          'name' => 'include_layer_list_switchers',
          'caption' => 'Include Layer switchers',
          'description' => 'Should the legend include checkboxes and/or radio buttons for controlling layer visibility?',
          'type' => 'boolean',
          'required' => FALSE,
          'default' => FALSE,
          'group' => 'Other Map Settings',
        ],
        [
          'name' => 'include_layer_list_types',
          'caption' => 'Types of layer to include in legend',
          'description' => 'Select which types of layer to include in the legend.',
          'type' => 'select',
          'options' => [
            'base,overlay' => 'All',
            'base' => 'Base layers only',
            'overlay' => 'Overlays only',
          ],
          'default' => 'base,overlay',
          'group' => 'Other Map Settings',
        ],
        [
          'name' => 'layer_title',
          'caption' => 'Layer Caption',
          'description' => 'Caption to display for the species distribution map layer. Can contain replacement strings {species} or {survey}.',
          'type' => 'textfield',
          'group' => 'Distribution Layer',
        ],
        [
          'name' => 'wms_feature_type',
          'caption' => 'Feature Type',
          'description' => 'Name of the feature type (layer) exposed in GeoServer to contain the occurrences. This must expose a taxon_meaning_id and a website_id attribute. ' .
          'for the filtering. The detail_occurrences view is suitable for this purpose, though make sure you include the namespace, e.g. indicia:detail_occurrences. ' .
          'The list of feature type names can be viewed by clicking on the Layer Preview link in the GeoServer installation.',
          'type' => 'textfield',
          'group' => 'Distribution Layer',
        ],
        [
          'name' => 'wms_style',
          'caption' => 'Style',
          'description' => 'Name of the SLD style file that describes how the distribution points are shown. Leave blank if not sure.',
          'type' => 'textfield',
          'required' => FALSE,
          'group' => 'Distribution Layer',
        ],
        [
          'name' => 'cql_filter',
          'caption' => 'Distribution layer filter.',
          'description' => 'Any additional filter to apply to the loaded data, using the CQL format. For example "record_status<>\'R\'"',
          'type' => 'textarea',
          'group' => 'Distribution Layer',
          'required' => FALSE,
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
   *
   * Vary the display of the page based on the interface type.
   */
  protected static function get_form_html($args, $auth, $attributes) {

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

    if (self::$notaxon) {
      return parent::get_form_html($args, $auth, $attributes);
    } else {
      // Get information on species names
      self::getNames($auth);

      // In Drupal 9, markup cannot be used in page title, so remove em tags.
      $repArray = ['<em>', '</em>'];
      $preferredClean = str_replace($repArray, '', self::$preferred);
      $titleName = isset(self::$defaultCommonName) ? self::$defaultCommonName . " ($preferredClean)" : $preferredClean;
      hostsite_set_page_title(lang::get('Summary details for {1}', $titleName));

      // Make the preferred and default common name available via
      // hidden controls.
      $taxonNames = '<input type="hidden" id="species-details-preferred-name" value="' . self::$preferred . '"/>';
      if (isset(self::$defaultCommonName)) {
        $taxonNames .= '<input type="hidden" id="species-details-default-common-name" value="' . self::$defaultCommonName . '"/>';
      }

      // Add any general ES taxon filters for taxon specified in URL.
      $esFilter = self::createEsFilterHtml('taxon.accepted_taxon_id', self::$externalKey, 'match_phrase', 'must');

      // Exclude rejected records in ES queries.
      $esFilter .= self::createEsFilterHtml('identification.verification_status', 'R', 'term', 'must_not');

      // Exclude unverified records if 'xu' GET URL param is set to on
      if (isset($_GET['xu']) && $_GET['xu'] === 'on') {
        $esFilter .= self::createEsFilterHtml('identification.verification_status', '', 'term', 'must_not');
        $esFilter .= self::createEsFilterHtml('identification.verification_status', 'C', 'term', 'must_not');
      }
      $groupIntegration = !empty($_GET['group_id']) ? ElasticsearchReportHelper::groupIntegration(array_merge([
        'missingGroupIdBehaviour' => 'showAll',
      ], [
        'readAuth' => $auth['read'],
      ]), FALSE) : '';

      return $taxonNames . $esFilter . $groupIntegration . parent::get_form_html($args, $auth, $attributes);
    }
  }

  /**
   * Obtains details of all names for this species from the database.
   */
  protected static function getNames($auth) {
    iform_load_helpers(['report_helper']);
    self::$preferred = lang::get('Unknown');
    // Get all the different names for the species.
    $extraParams = ['sharing' => 'reporting'];
    if (isset($_GET['taxa_taxon_list_id'])) {
      $extraParams['taxa_taxon_list_id'] = $_GET['taxa_taxon_list_id'];
      self::$taxaTaxonListId = $_GET['taxa_taxon_list_id'];
    }
    elseif (isset($_GET['occurrence:taxa_taxon_list_id'])) {
      $extraParams['taxa_taxon_list_id'] = $_GET['occurrence:taxa_taxon_list_id'];
      self::$taxaTaxonListId = $_GET['occurrence:taxa_taxon_list_id'];
    }
    elseif (isset($_GET['taxon_meaning_id'])) {
      $extraParams['taxon_meaning_id'] = $_GET['taxon_meaning_id'];
      self::$taxonMeaningId = $_GET['taxon_meaning_id'];
    }
    elseif (isset($_GET['external_key'])) {
      $extraParams['external_key'] = $_GET['external_key'];
      self::$externalKey = $_GET['external_key'];
    }
    $species_details = report_helper::get_report_data([
      'readAuth' => $auth['read'],
      'class' => 'species-details-fields',
      'dataSource' => 'library/taxa/taxon_names_2',
      'useCache' => FALSE,
      'extraParams' => $extraParams,
    ]);

    foreach ($species_details as $speciesData) {
      if ($speciesData['preferred'] === 't') {
        self::$preferred = $speciesData['taxon'];
        self::$preferredPlain = $speciesData['taxon_plain'];
        if (!isset(self::$taxonMeaningId)) {
          self::$taxonMeaningId = $speciesData['taxon_meaning_id'];
        }
        if (!isset(self::$taxaTaxonListId)) {
          self::$taxaTaxonListId = $speciesData['id'];
        }
        if (!isset(self::$externalKey)) {
          self::$externalKey = $speciesData['external_key'];
        }
      }
      elseif ($speciesData['language_iso'] === 'lat' && !in_array($speciesData['taxon'], self::$synonyms)) {
        self::$synonyms[] = $speciesData['taxon'];
      }
      elseif ($speciesData['language_iso'] !== 'lat' && !in_array($speciesData['taxon'], self::$commonNames)) {
        self::$commonNames[] = $speciesData['taxon'];
      }
      if (!isset(self::$defaultCommonName) && !empty($speciesData['default_common_name'])) {
        self::$defaultCommonName = $speciesData['default_common_name'];
      }
    }
    // Remove default common name from $commonNames array.
    if (isset(self::$defaultCommonName)) {
      if (($key = array_search(self::$defaultCommonName, self::$commonNames)) !== FALSE) {
        unset(self::$commonNames[$key]);
      }
    }

    /*
     * Fix a problem on the fungi-without-borders site where providing a
     * taxa_taxon_list_id doesn't work (the system would try and return all
     * records). This makes sense because the cache_table doesn't have a
     * taxa_taxon_list_id. However, I am not sure why this is fine on other
     * sites when the $extraParams are the same. At worst these two lines
     * make the page more robust.
     */

    if (!empty($extraParams['taxa_taxon_list_id'])) {
      $extraParams['id'] = $extraParams['taxa_taxon_list_id'];
    }
    $taxon = data_entry_helper::get_population_data([
      'table' => 'cache_taxa_taxon_list',
      'extraParams' => $auth['read'] + $extraParams,
    ]);
    if (!empty($taxon[0]['kingdom_taxon'])) {
      self::$taxonomy[] = $taxon[0]['kingdom_taxon'];
    }
    if (!empty($taxon[0]['order_taxon'])) {
      self::$taxonomy[] = $taxon[0]['order_taxon'];
    }
    if (!empty($taxon[0]['family_taxon'])) {
      self::$taxonomy[] = $taxon[0]['family_taxon'];
    }
  }

  /**
   * Draw the Species Details section of the page.
   *
   * Available options include:
   * * @includeAttributes - defaults to true. If false, then the custom
   *   attributes are not included in the block.
   *
   * @return string
   *   The output html string.
   */
  protected static function get_control_speciesdetails($auth, $args, $tabalias, $options) {
    if (self::$notaxon) {
      return '';
    }
    global $indicia_templates;
    $options = array_merge([
      'includeAttributes' => TRUE,
    ], $options);

    /*
     * Deal with field name translations.
     * This is a temporary workaround - currently dealt with in JS.
     * because to do it in PHP would require much more work in the depths of
     * Indicia.
     */
    $fields = helper_base::explode_lines($args['fields']);
    $fieldsTranslated = '';
    foreach ($fields as $key => $field) {
      if (strpos($field, "|") !== FALSE) {
        // Store mapping for use in JS.
        $fieldsTranslated .= $field . ';';
        // Remove the translated value from the field.
        $fields[$key] = trim(substr($field, 0, strpos($field, "|")));
      }
    }
    $fieldsLower = helper_base::explode_lines(strtolower($args['fields']));

    /*
     * If the user sets the option to exclude particular fields then we set to
     * the hide flag on the name types they have specified.
     */
    if ($args['operator'] == 'not in') {
      $hidePreferred = FALSE;
      $hideCommon = FALSE;
      $hideOtherCommon = FALSE;
      $hideSynonym = FALSE;
      $hideTaxonomy = FALSE;
      foreach ($fieldsLower as $theField) {
        if ($theField == 'preferred names'|| $theField == 'preferred name'|| $theField == 'preferred' || $theField == 'species name') {
          $hidePreferred = TRUE;
        }
        elseif ($theField == 'common names' || $theField == 'common name'|| $theField == 'common') {
          $hideCommon = TRUE;
        }
        elseif ($theField == 'other common names') {
          $hideOtherCommon = TRUE;
        }
        elseif ($theField == 'synonym names' || $theField == 'synonym name'|| $theField == 'synonym' || $theField == 'synonyms') {
          $hideSynonym = TRUE;
        }
        elseif ($theField == 'taxonomy') {
          $hideTaxonomy = TRUE;
        }
      }
    }

    /*
     * If the user sets the option to only include particular fields then we set
     * to the hide flag to true unless they have specified the name type.
     */
    if ($args['operator'] == 'in') {
      $hidePreferred = TRUE;
      $hideCommon = TRUE;
      $hideOtherCommon = TRUE;
      $hideSynonym = TRUE;
      $hideTaxonomy = TRUE;
      foreach ($fieldsLower as $theField) {
        if ($theField == 'preferred names'|| $theField == 'preferred name'|| $theField == 'preferred' || $theField == 'species name') {
          $hidePreferred = FALSE;
        }
        elseif ($theField == 'common names' || $theField == 'common name'|| $theField == 'common') {
          $hideCommon = FALSE;
        }
        elseif ($theField == 'other common names') {
          $hideOtherCommon = FALSE;
        }
        elseif ($theField == 'synonym names' || $theField == 'synonym name'|| $theField == 'synonyms' || $theField == 'synonym') {
          $hideSynonym = FALSE;
        }
        elseif ($theField == 'taxonomy') {
          $hideTaxonomy = FALSE;
        }
      }
    }
    // Draw the names on the page.
    $details_report = self::drawNames($auth['read'], $hidePreferred, $hideCommon, $hideOtherCommon, $hideSynonym, $hideTaxonomy);

    if ($options['includeAttributes']) {
      // Draw any custom attributes for the species added by the user.
      $attrs_report = report_helper::freeform_report([
        'readAuth' => $auth['read'],
        'class' => 'species-details-fields',
        'dataSource' => 'library/taxa/taxon_attributes_with_hiddens',
        'bands' => [['content' => str_replace(['{class}'], '', '<dt style="white-space:break-spaces;display:none">{caption}</dt><dd>{value}</dd>')]],
        'extraParams' => [
          'taxa_taxon_list_id' => self::$taxaTaxonListId,
          // The SQL takes a set of the hidden fields, so convert from an array.
          'attrs' => strtolower(self::convertArrayToSet($fields)),
          'testagainst' => $args['testagainst'],
          'operator' => $args['operator'],
          'sharing' => 'reporting',
          'language' => iform_lang_iso_639_2(hostsite_get_user_field('language')),
        ]
      ]);
    }

    $r = '';
    // Draw the species names and custom attributes.
    if (isset($details_report)) {
      $r .= $details_report;
    }
    if (isset($attrs_report)) {
      $r .= $attrs_report;
    }
    $r = str_replace(
      ['{id}', '{title}', '{content}'],
      ['detail-panel-speciesdetails', lang::get('Species Details'), $r],
      $indicia_templates['dataValueList']
    );
    $r .= '<input type="hidden" id="species-details-fields-translate" value="' . $fieldsTranslated . '"/>';
    return $r;
  }

  /**
   * Draw the names in the Species Details section of the page.
   *
   * @return string
   *   The output html.
   */
  protected static function drawNames($auth, $hidePreferred, $hideCommon, $hideOtherCommon, $hideSynonym, $hideTaxonomy) {
    global $indicia_templates;
    $r = '';
    if (!$hidePreferred) {
      $r .= str_replace(
        ['{caption}', '{value}', '{class}'],
        [lang::get('Species name'), self::$preferred, ''],
        $indicia_templates['dataValue']);
    }
    if ($hideCommon == FALSE && !empty(self::$defaultCommonName)) {
      $r .= str_replace(
        ['{caption}', '{value}', '{class}'],
        [lang::get('Common name'), self::$defaultCommonName, ''],
        $indicia_templates['dataValue']);
    }
    if ($hideOtherCommon == FALSE && !empty(self::$commonNames)) {
      $label = 'Other common names';
      $otherCommonNames = array_merge(self::$commonNames);
      array_shift($otherCommonNames);
      if (!empty($otherCommonNames)) {
        $r .= str_replace(
          ['{caption}', '{value}', '{class}'],
          [lang::get($label), implode(', ', $otherCommonNames), ''],
          $indicia_templates['dataValue']);
      }
    }
    if ($hideSynonym == FALSE && !empty(self::$synonyms)) {
      $label = (count(self::$synonyms) === 1) ? 'Synonym' : 'Synonyms';
      $r .= str_replace(
        ['{caption}', '{value}', '{class}'],
        [lang::get($label), implode(', ', self::$synonyms), ''],
        $indicia_templates['dataValue']
      );
    }
    if ($hideTaxonomy == FALSE && !empty(self::$taxonomy)) {
      $r .= str_replace(
        ['{caption}', '{value}', '{class}'],
        [lang::get('Taxonomy'), implode(' :: ', self::$taxonomy), ''],
        $indicia_templates['dataValue']
      );
    }
    return $r;
  }

  /**
   * Generate attributes table.
   *
   * Available options include:
   * - @includeCaptions - set to false to exclude attribute captions from the
   *   grouped data.
   * - @headingsToInclude - CSV list of heading names that are to be included.
   *   To include a particular sub-category only, supply the heading name and
   *   then a slash and then the sub-category name e.g. the following includes
   *   just the hoglets sub-category and the entire rabbits section:
   *   - @headingsToInclude=Hedgehogs/Hoglets,Rabbits
   * - @headingsToExclude - Same as @headingsToInclude but items are exluded
   *   instead (any items that appear in both headingsToInclude and
   *   headingsToExclude will be excluded).
   *
   * @return string
   *   Html for the description.
   */
  protected static function get_control_attributedescription($auth, $args, $tabalias, $options) {
    if (self::$notaxon) {
      return '';
    }
    global $indicia_templates;
    $options = array_merge([
      'includeCaptions' => TRUE,
    ], $options);
    $sharing = empty($args['sharing']) ? 'reporting' : $args['sharing'];
    if (!empty($options['headingsToInclude'])) {
      $headingsToInclude = explode(',', $options['headingsToInclude']);
    }
    else {
      $headingsToInclude = [];
    }
    if (!empty($options['headingsToExclude'])) {
      $headingsToExclude = explode(',', $options['headingsToExclude']);
    }
    else {
      $headingsToExclude = [];
    }

    $mainHeadingsToInclude = $subHeadingsToInclude = $mainHeadingsToExclude = $subHeadingsToExclude = [];
    // Cycle through all the headings we want to include.
    foreach ($headingsToInclude as $headingSubCatToInclude) {
      // See if a sub-category has been specified.
      if (strpos($headingSubCatToInclude, '/') !== FALSE) {
        /* If a sub-category has been specified, then get the main heading and
         * sub-category and save them (we still need the main heading as we need
         * to display it, even if we are only going to be showing one of the
         * sub-categories)
         */
        $headingSubCatSplit = explode('/', $headingSubCatToInclude);
        $mainHeadingsToInclude[] = $headingSubCatSplit[0];
        $subHeadingsToInclude[] = $headingSubCatToInclude;
      }
      else {
        /* If we are including the whole section, then indicate this explicitly
         * to the system using the word unlimited.
         */
        $mainHeadingsToInclude[] = $headingSubCatToInclude;
        $subHeadingsToInclude[] = $headingSubCatToInclude . '/unlimited';
      }
    }

    /* Do similar for excluding, however in this case there is one difference,
     * if we are exluding a sub-category, we don't automatically
     * exclude the main heading as it is needed for the other sub-categories.
     */
    foreach ($headingsToExclude as $headingSubCatToExclude) {
      if (strpos($headingSubCatToExclude, '/') !== FALSE) {
        $headingSubCatSplit = explode('/', $headingSubCatToExclude);
        $subHeadingsToExclude[] = $headingSubCatToExclude;
      } else {
        $mainHeadingsToExclude[] = $headingSubCatToExclude;
        $subHeadingsToExclude[] = $headingSubCatToExclude . '/unlimited';
      }
    }

    $args['param_presets'] = '';
    $args['param_defaults'] = '';
    $params = [
      'taxa_taxon_list_id' => empty($_GET['taxa_taxon_list_id']) ? '' : $_GET['taxa_taxon_list_id'],
      'taxon_meaning_id' => empty($_GET['taxon_meaning_id']) ? '' : $_GET['taxon_meaning_id'],
      'include_captions' => $options['includeCaptions'] ? '1' : '0',
      'language' => iform_lang_iso_639_2(hostsite_get_user_field('language')),
    ];
    $reportOptions = array_merge(
      iform_report_get_report_options($args, $auth['read']),
      [
        'sharing' => $sharing,
        'readAuth' => $auth['read'],
        'dataSource' => 'reports_for_prebuilt_forms/species_details/species_attr_description',
        'extraParams' => $params,
        'wantCount' => '0',
      ]
    );
    // Ensure supplied extraParams are merged, not overwritten.
    if (!empty($options['extraParams'])) {
      $options['extraParams'] = array_merge($reportOptions['extraParams'], $options['extraParams']);
    }
    $data = report_helper::get_report_data($reportOptions);
    $r = '';
    $currentHeading = '';
    $currentHeadingContent = '';
    foreach ($data as $idx => $row) {
      if ($row['category'] !== $currentHeading) {
        if (!empty($currentHeadingContent)) {
          /*
           * Only display a section if:
           * - The user hasn't specified any options regarding which sections
           * should be displayed.
           * - The user has specified to include the section, and not specified
           * to exclude it.
           * - The user hasn't specified any options regarding what to include,
           * and it isn't in the list of items to exclude.
           */
          if ($currentHeading === ''
              || (in_array($currentHeading, $mainHeadingsToInclude) && !in_array($currentHeading, $mainHeadingsToExclude))
              || (empty($mainHeadingsToInclude) && !in_array($currentHeading, $mainHeadingsToExclude))
              || (empty($mainHeadingsToInclude) && empty($mainHeadingsToExclude))) {
            $r .= str_replace(
              ['{id}', '{title}', '{content}'],
              [
                "detail-panel-description-$idx",
                $currentHeading,
                $currentHeadingContent,
              ],
              $indicia_templates['dataValueList']
            );
          }
          $currentHeadingContent = '';
        }
        $currentHeading = $row['category'];
      }
      $currentHeadingAndSubCat = $currentHeading . '/' . $row['subcategory'];
      /*
       * Only display a sub-category if:
       * - The sub-category is in the list of sub-categories to display and not
       * in the list of sub-categories to exclude.
       * - The user has not specified any options regarding sub-categories to
       * include and the sub-category is not in the list to exclude.
       * - The user has not specified any options regarding sub-categories to
       * include or exclude.
       * - The user has specified to include all sub-categories under a
       * particular heading and the sub-category is not listed for exclusion.
       */
      if ($row['subcategory'] === '' ||
          (in_array($currentHeadingAndSubCat, $subHeadingsToInclude) && !in_array($currentHeadingAndSubCat, $subHeadingsToExclude)) ||
          (empty($subHeadingsToInclude) && !in_array($currentHeadingAndSubCat, $subHeadingsToExclude)) ||
          (empty($subHeadingsToInclude) && empty($subHeadingsToExclude)) ||
          (in_array($currentHeading . '/unlimited', $subHeadingsToInclude) && !in_array($currentHeading . '/unlimited', $subHeadingsToExclude))
      ) {
        $currentHeadingContent .= str_replace(
          ['{caption}', '{value}'],
          [$row['subcategory'], $row['values']],
          $indicia_templates['dataValue']
        );
      }
    }
    if (!empty($currentHeadingContent)) {
      // See comments above for explanation of IF statement.
      if ($currentHeading === ''
          || (in_array($currentHeading, $mainHeadingsToInclude) && !in_array($currentHeading, $mainHeadingsToExclude))
          || (empty($mainHeadingsToInclude) && !in_array($currentHeading, $mainHeadingsToExclude))
          || (empty($mainHeadingsToInclude) && empty($mainHeadingsToExclude))) {
        $r .= str_replace(
          ['{id}', '{title}', '{content}'],
          [
            "detail-panel-description-$idx",
            $currentHeading,
            $currentHeadingContent,
          ],
          $indicia_templates['dataValueList']
        );
      }
    }
    return $r;
  }

  /**
   * Chart to display temporal distribution of records by year.
   *
   * @return string
   *   The output chart.
   */
  protected static function get_control_recsbyyear($auth, $args, $tabalias, $options) {
    if (self::$notaxon) {
      return '';
    }
    data_entry_helper::add_resource('brc_charts');
    $options = array_merge([
      'title' => 'Number of records by year',
    ], $options);
    $optionsSource = [
      'extraParams' => $options['extraParams'],
      'nid' => $options['nid'],
      'id' => 'recsbyyearSource',
      'size' => 0,
      'mode' => 'compositeAggregation',
      'uniqueField' => 'event.year',
      'fields' => ['event.year'],
      'proxyCacheTimeout' => 300,
    ];
    ElasticsearchReportHelper::source($optionsSource);
    $optionsCustomScript = [
      'extraParams' => $options['extraParams'],
      'nid' => $options['nid'],
      'source' => 'recsbyyearSource',
      'functionName' => 'populateRecsByYearChart',
    ];
    $customScript = ElasticsearchReportHelper::customScript($optionsCustomScript);
    $title = lang::get($options['title']);
    $r = <<<HTML
      <div class="detail-panel" id="detail-panel-recsbyyear">
        <h3>$title</h3>
        $customScript
        <div>Records that span more than one year are not included in this chart.</div>
        <div class="brc-recsbyyear-chart"></div>
      </div>
HTML;
    return $r;
  }

  /**
   * Chart to display temporal distribution of records through the year.
   *
   * @return string
   *   The output chart.
   */
  protected static function get_control_recsthroughyear($auth, $args, $tabalias, $options) {
    if (self::$notaxon) {
      return '';
    }
    data_entry_helper::add_resource('brc_charts');
    $options = array_merge([
      'title' => 'Number of records through the year',
      'period' => 'week'
    ], $options);
    if ($options['period'] !== 'week' && $options['period'] !== 'month') {
      $options['period'] = 'week';
    }
    $optionsSource = [
      'extraParams' => $options['extraParams'],
      'nid' => $options['nid'],
      'id' => 'recsthroughyearSource',
      'size' => 0,
      'mode' => 'compositeAggregation',
      'uniqueField' => 'event.' . $options['period'],
      'fields' => ['event.' . $options['period']],
      'proxyCacheTimeout' => 300,
    ];
    ElasticsearchReportHelper::source($optionsSource);
    $title = lang::get($options['title']);
    $optionsCustomScript = [
      'extraParams' => $options['extraParams'],
      'nid' => $options['nid'],
      'source' => 'recsthroughyearSource',
      'functionName' => 'populateRecsThroughYearChart',
    ];
    $customScript = ElasticsearchReportHelper::customScript($optionsCustomScript);
    $r = <<<HTML
      <div class="detail-panel" id="detail-panel-recsthroughyear">
        <h3>$title</h3>
        $customScript
        <div class="brc-recsby$options[period]-chart"></div>
      </div>
HTML;
    return $r;
  }

  /**
   * Leaflet ES map to explore records.
   *
   * Options:
   * * minSqSizeKms - minimum square size to show (10, 2 or 1). Set this to
   *   limit the precision of data shown.
   * * maxSqSizeKms - maximum square size to show (10, 2 or 1). Set this to
   *   force a higher precision when zoomed out.
   * * switchToGeomsAt - layer zoom level below which full precision geometries
   *   are shown. Defaults to 13, set to NULL to disable full precision
   *   geometries.
   *
   * @return string
   *   The output map.
   */
  protected static function get_control_exploremap($auth, $args, $tabalias, $options) {
    if (self::$notaxon) {
      return '';
    }
    $r = '<div class="detail-panel" id="detail-panel-exploremap"><h3>' . lang::get('Explore map') . '</h3>';
    $r .= self::get_exploremap_html($auth, $args, $tabalias, $options);
    return $r;
  }

  protected static function get_exploremap_html($auth, $args, $tabalias, $options) {
    $optionsMapSource = [
      'extraParams' => $options['extraParams'],
      'nid' => $options['nid'],
      'id' => 'recordsGridSquares',
      'mode' => 'mapGridSquare',
      'switchToGeomsAt' => $options['switchToGeomsAt'] ?? 13,
    ];
    ElasticsearchReportHelper::source($optionsMapSource);
    $optionsLeafletMap = [
      'extraParams' => $options['extraParams'],
      'nid' => $options['nid'],
      'id' => 'leaflet-explore-map',
      'layerConfig' => [
        'recordsMap' => [
          'title' => 'All records in current filter (grid map)',
          'source' => 'recordsGridSquares',
          'enabled' => TRUE,
          'forceEnabled' => TRUE,
          'type' => 'circle',
          'style' => [
            'colour' => '#3333FF',
            'weight' => 1,
            'size' => 'autoGridSquareSize',
          ],
        ],
      ],
    ];
    if (!empty($options['minSqSizeKms'])) {
      $optionsLeafletMap['minSqSizeKms'] = $options['minSqSizeKms'];
    }
    if (!empty($options['maxSqSizeKms'])) {
      $optionsLeafletMap['maxSqSizeKms'] = $options['maxSqSizeKms'];
    }
    return ElasticsearchReportHelper::leafletMap($optionsLeafletMap);
  }

  /**
   * Hectad overview and leaflet explore map with a toggle switch.
   *
   * @return string
   *   The output html.
   */
  protected static function get_control_dualmap($auth, $args, $tabalias, $options) {
    if (self::$notaxon) {
      return '';
    }
    $r = '<div class="detail-panel" id="detail-panel-dualmap"><h3>' . lang::get('Spatial distribution of records') . '</h3>';
    $r .= '<div class="btn-group btn-group-toggle" data-toggle="buttons" style="margin-bottom: 0.5em">';
    $r .= '<label class="btn btn-primary active">';
    $r .= '<input type="radio" name="dualMap-button-group" id="dualMap-hectad-button" value="hectad" checked>';
    $r .= 'Hectad overview map</label>';
    $r .= '<label class="btn btn-primary">';
    $r .= '<input type="radio" name="dualMap-button-group" id="dualMap-explore-button" value="explore">';
    $r .= 'Explore map</label>';
    $r .= '</div></div>';
    $r .= '<div id="dualMap-hectad-map">' . self::get_hectadmap_html($auth, $args, $tabalias, $options) . '</div>';
    $r .= '<div id="dualMap-explore-map" style="display: none">' . self::get_exploremap_html($auth, $args, $tabalias, $options) . '</div>';

    return $r;
  }

  /**
   * Draw atlas style hectad map.
   *
   * @return string
   *   The output map.
   */
  protected static function get_control_hectadmap($auth, $args, $tabalias, $options) {
    $options = array_merge([
      'title' => 'Hectad distribution map',
      'colour1' => '#1b9e77',
      'colour2' => '#7570b3',
      'colour3' => '#d95f02',
      'legendmouse' => 'none',
      'highstyle' => 'fill-opacity: 1; fill: black',
      'lowstyle' => 'fill-opacity: 0.2 ',
      'info' => '',
      'download' => 'false',
    ], $options);
    $r = '<div class="detail-panel" id="detail-panel-hectadmap"><h3>' . lang::get($options['title']) . '</h3>';
    $r .= self::get_hectadmap_html($auth, $args, $tabalias, $options);
    return $r;
  }

  protected static function get_hectadmap_html($auth, $args, $tabalias, $options) {
    if (self::$notaxon) {
      return '';
    }
    $defaultThresh1 = 2010;
    $defaultThresh2 = date('Y') - 1;
    $options = array_merge([
      'threshold1' => $defaultThresh1,
      'threshold2' => $defaultThresh2,
    ], $options);
    data_entry_helper::add_resource('brc_atlas');
    $optionsSource = [
      'extraParams' => $options['extraParams'],
      'nid' => $options['nid'],
      'id' => 'hectadMapSource',
      'mode' => 'compositeAggregation',
      'uniqueField' => 'location.grid_square.10km.centre',
      'aggregation' => [
        'minYear' => ['min' => ['field' => 'event.date_start']],
        'maxYear' => ['max' => ['field' => 'event.date_end']],
      ],
      'proxyCacheTimeout' => 300,
    ];
    ElasticsearchReportHelper::source($optionsSource);
    $optionsCustomScript = [
      'extraParams' => $options['extraParams'],
      'nid' => $options['nid'],
      'source' => 'hectadMapSource',
      'functionName' => 'populateHectadMap',
    ];
    // Set sensible initial values for colour banding thresholds.
    $thresh1 = $options['threshold1'];
    $thresh2 = $options['threshold2'];
    if (!is_numeric($thresh1)) {
      $thresh1 = $defaultThresh1;
    }
    if (!is_numeric($thresh2)) {
      $thresh2 = $defaultThresh2;
    }
    if ($thresh1 < 0) {
      $thresh1 = date('Y') + $thresh1;
    }
    if ($thresh2 < 0) {
      $thresh2 = date('Y') + $thresh2;
    }
    if ($thresh1 >= $thresh2) {
      $thresh1 = $thresh2 - 1;
    }
    if ($thresh2 <= $thresh1) {
      $thresh2 = $thresh1 + 1;
    }

    $r = ElasticsearchReportHelper::customScript($optionsCustomScript);

    $r .= '<input type="hidden" id="brc-hectad-map-colour1" value="' . $options['colour1'] . '"/>';
    $r .= '<input type="hidden" id="brc-hectad-map-colour2" value="' . $options['colour2'] . '"/>';
    $r .= '<input type="hidden" id="brc-hectad-map-colour3" value="' . $options['colour3'] . '"/>';

    $r .= '<input type="hidden" id="brc-hectad-map-legendmouse" value="' . $options['legendmouse'] . '"/>';
    $r .= '<input type="hidden" id="brc-hectad-map-highstyle" value="' . $options['highstyle'] . '"/>';
    $r .= '<input type="hidden" id="brc-hectad-map-lowstyle" value="' . $options['lowstyle'] . '"/>';

    if ($options['info'] !== '') {
      $r .= '<div>' . $options['info'] . '</div>';
    }
    $r .= '<div style="margin-bottom: 0.5em; position: relative">';
    $r .= '<div style="display: inline-block">' . lang::get('Thresholds') . ':</div>';
    $r .= '<input style="width:5em; margin-left: 0.5em" type="number" id="brc-hectad-map-thresh1" class="brc-hectad-map-thresh" value="' . $thresh1 . '">';
    $r .= '<input style="width:5em; margin-left: 0.5em" type="number" id="brc-hectad-map-thresh2" class="brc-hectad-map-thresh" value="' . $thresh2 . '">';
    $r .= '<select style="margin-left: 0.5em; position: relative; top: 1px; height: 27px" id="brc-hectad-map-priority">';
    $r .= '<option value="recent">' . lang::get('Recent on top') . '</option>';
    $r .= '<option value="oldest">' . lang::get('Oldest on top') . '</option>';
    $r .= '</select>';
    if ($options['download'] === TRUE) {
      global $indicia_templates;
      $r .= "<button style=\"margin-left: 0.5em\" class=\"$indicia_templates[buttonDefaultClass] $indicia_templates[buttonSmallClass] brc-hectad-map-image-download\">" . lang::get('Download') . '</button>';
    }
    $r .= '</div>';
    $r .= '<div class="brc-hectad-map"></div>';
    $r .= '<div id="brc-hectad-map-dot-details">Move mouse cursor over dot for info</div>';
    if (isset($_GET['xu']) && $_GET['xu'] === 'on') {
      $r .= '<div style="font-size: small">Rejected and unverified records are excluded from this map.</div>';
    } else {
      $r .= '<div style="font-size: small">Rejected records are excluded from this map. Unverified records are included.</div>';
    }
    $r .= '<form method="GET" id="unverified-checkbox-form">';
    $r .= '<div style="margin: 0 0 2em 0;">';
    if (isset($_GET['xu']) && $_GET['xu'] === 'on') {
      $r .= '<input type="checkbox" onChange="this.form.submit()" id="xu" name="xu" style="position: relative; top: 2px" checked>';
    } else {
      $r .= '<input type="checkbox" onChange="this.form.submit()" id="xu" name="xu" style="position: relative; top: 2px">';
    }
    $r .= '<label for="brc-hectad-map-exclude-unverified" style="margin-left: 0.5em">Exclude unverified records</label>';
    $r .= '</div>';
    $r .= '<input type="hidden" id="taxa_taxon_list_id" name="taxa_taxon_list_id" value="' . self::$taxaTaxonListId . '"></input>';
    $r .= '</form>';
    return $r;
  }

  /**
   * Output RSS information.
   *
   * @return string
   */
  protected static function get_control_rss($auth, $args, $tabalias, $options) {

    if (isset(self::$taxonMeaningId)) {
      //dpm('taxonMeaningId' . ' ' . self::$taxonMeaningId);

      iform_load_helpers(['report_helper']);

      $extraParams['taxon_meaning_id'] = self::$taxonMeaningId;
      $extraParams['taxon_list_id'] = 15;

      $rss = report_helper::get_report_data([
        'readAuth' => $auth['read'],
        'dataSource' => 'library/taxa/taxon_rss',
        'useCache' => TRUE,
        'extraParams' => $extraParams,
      ]);

      //dpm($rss);

      $r = '<div class="detail-panel" id="detail-panel-rss"><h3>' . lang::get('Recording schemes & societies') . '</h3>';

      if (count($rss) > 0) {
        $r .= '<div class="' . $options['class'] . '"><ul>';
        foreach ($rss as $scheme) {
          $r .= '<li>' . $scheme['title'] . '</li>';
        }
        $r .= '</ul></div>';
      } else {
        $r .= '<div>No societies are listed in the UK Species Inventory for this taxon.</div>';
      }
      $r .= '</div>';
    } else {
      $r = '';
    }
    return $r;
  }

  /**
   * Draw Photos section of the page.
   *
   * @return string
   *   The output report grid.
   */
  protected static function get_control_photos($auth, $args, $tabalias, $options) {
    if (self::$notaxon) {
      return '';
    }
    iform_load_helpers(['report_helper']);
    data_entry_helper::add_resource('fancybox');
    $options = array_merge([
      'itemsPerPage' => 20,
      'imageSize' => 'thumb',
      'class' => 'media-gallery',
    ], $options);
    // Use this report to return the photos.
    $reportName = 'reports_for_prebuilt_forms/species_details/occurrences_thumbnails_2';
    $media = report_helper::get_report_data([
      'readAuth' => $auth['read'],
      'dataSource' => $reportName,
      'itemsPerPage' => $options['itemsPerPage'],
      'extraParams' => [
        'external_key' => self::$externalKey,
        'limit' => $options['itemsPerPage'],
        'wantCount' => 0,
      ],
    ]);
    $r = '<div class="detail-panel" id="detail-panel-photos"><h3>' . lang::get('Photos and media') . '</h3>';
    $r .= '<div class="' . $options['class'] . '"><ul>';

    if (count($media) === 0) {
      $r .= '<p>No photos or media files available</p>';
    }
    else {
      foreach ($media as $medium) {
        $r .= iform_report_get_gallery_item('occurrence', $medium, $options['imageSize']);
      }
    }
    $r .= '</ul></div></div>';
    return $r;
  }

  /**
   * Taxa associated with the species from the taxon_associations table.
   *
   * @return string
   *   A comma seperated list of taxa associated with the species.
   */
  protected static function get_control_taxonassociations($auth, $args, $tabalias, $options) {
    if (self::$notaxon) {
      return '';
    }
    $params = [
      'taxa_taxon_list_id' => empty($_GET['taxa_taxon_list_id']) ? '' : $_GET['taxa_taxon_list_id'],
      'taxon_meaning_id' => empty($_GET['taxon_meaning_id']) ? '' : $_GET['taxon_meaning_id'],
    ];
    $reportOptions = array_merge(
      iform_report_get_report_options($args, $auth['read']),
      [
        'readAuth' => $auth['read'],
        'dataSource' => 'library/taxon_associations/get_taxon_associations_as_string',
        'extraParams' => $params,
        'wantCount' => '0',
      ]
    );
    $data = report_helper::get_report_data($reportOptions);
    if (!empty($data[0]['associated_taxa'])) {
      $r = '<div class="detail-panel" id="detail-panel-taxonassociations"><h3>' . lang::get('Hosts') . '</h3>';
      $r .= '<div><i>' . $data[0]['associated_taxa'] . '</i></div>';
      $r .= '</div>';
    }
    else {
      $r = '';
    }
    return $r;
  }

  /**
   * Taxa associated with the occurrence.
   *
   * @return string
   *   A comma seperated list of taxa associated with the occurence.
   */
  protected static function get_control_occurrenceassociations($auth, $args, $tabalias, $options) {
    if (self::$notaxon) {
      return '';
    }
    iform_load_helpers(['report_helper']);
    $currentUrl = report_helper::get_reload_link_parts();
    // Amend currentUrl path if we have drupal dirty URLs so JS will work.
    if (isset($currentUrl['params']['q']) && strpos($currentUrl['path'], '?') === FALSE) {
      $currentUrl['path'] .= '?q=' . $currentUrl['params']['q'] . '&taxon_meaning_id=';
    }
    else {
      $currentUrl['path'] .= '&taxon_meaning_id=';
    }
    $options = array_merge([
      'dataSource' => 'library/occurrence_associations/filterable_associated_species_list_cloud',
      'itemsPerPage' => 20,
      'class' => 'cloud',
      'header' => '<ul>',
      'footer' => '</ul>',
      'bands' => [
        ['content' => '<li style="font-size: {font_size}px">' . "<a href=\"$currentUrl[path]{taxon_meaning_id}\">{species}<a/></li>"],
      ],
      'emptyText' => '<p>No association species information available</p>',
      'extraParams' => [],
    ], $options);
    $extraParams = array_merge(
        $options['extraParams'],
        ['taxon_meaning_list' => self::$taxonMeaningId]
    );
    return '<div class="detail-panel" id="detail-panel-occurrenceassociations"><h3>' . lang::get('Associated species') . '</h3>' .
    report_helper::freeform_report([
      'readAuth' => $auth['read'],
      'dataSource' => $options['dataSource'],
      'itemsPerPage' => $options['itemsPerPage'],
      'class' => $options['class'],
      'header' => $options['header'],
      'footer' => $options['footer'],
      'bands' => $options['bands'],
      'emptyText' => $options['emptyText'],
      'mode' => 'report',
      'autoParamsForm' => FALSE,
      'extraParams' => $extraParams,
    ]) . '</div>';
  }

  /**
   * Draw Map section of the page.
   *
   * @return string
   *   The output map panel.
   */
  protected static function get_control_map($auth, $args, $tabalias, $options) {
    if (self::$notaxon) {
      return '';
    }
    // Draw a map by calling Indicia report when Geoserver isn't available.
    if (isset($options['noGeoserver']) && $options['noGeoserver'] === TRUE) {
      return self::mapWithoutGeoserver($auth, $args, $tabalias, $options);
    }
    iform_load_helpers(['map_helper', 'data_entry_helper']);
    // Set up the map options.
    $options = iform_map_get_map_options($args, $auth['read']);
    if ($tabalias) {
      $options['tabDiv'] = $tabalias;
    }
    $olOptions = iform_map_get_ol_options($args);
    $url = map_helper::$geoserver_url . 'wms';
    // Get the style if there is one selected.
    $style = $args["wms_style"] ? ", styles: '" . $args["wms_style"] . "'" : '';
    map_helper::$onload_javascript .= "\n    var filter='website_id=" . $args['website_id'] . "';";

    $layerTitle = str_replace('{species}', self::get_best_name(), $args['layer_title']);
    map_helper::$onload_javascript .= "\n    filter += ' AND taxon_meaning_id=" . self::$taxonMeaningId . "';\n";

    if ($args['cql_filter']) {
      map_helper::$onload_javascript .= "\n    filter += ' AND(" . str_replace("'", "\'", $args['cql_filter']) . ")';\n";
    }

    $layerTitle = str_replace("'", "\'", $layerTitle);

    map_helper::$onload_javascript .= "\n    var distLayer = new OpenLayers.Layer.WMS(
      '" . $layerTitle . "',
      '$url',
      {layers: '" . $args["wms_feature_type"] . "', transparent: true, CQL_FILTER: filter $style},
      {isBaseLayer: false, sphericalMercator: true, singleTile: true}
    );\n";
    $options['layers'][] = 'distLayer';

    // This is not a map used for input.
    $options['editLayer'] = FALSE;
    // If Drupal IForm proxy is installed, use this path as OpenLayers proxy.
    if (function_exists('hostsite_module_exists') && hostsite_module_exists('iform_proxy')) {
      $options['proxy'] = data_entry_helper::getRootFolder(TRUE) . hostsite_get_config_value('iform', 'proxy_path', 'proxy') . '&url=';
    }

    // Output a legend.
    if (isset($args['include_layer_list_types'])) {
      $layerTypes = explode(',', $args['include_layer_list_types']);
    }
    else {
      $layerTypes = ['base', 'overlay'];
    }
    $r = '<div class="detail-panel" id="detail-panel-map"><h3>' . lang::get('Map') . '</h3>';
    // Legend options set by the user.
    if (!isset($args['include_layer_list']) || $args['include_layer_list']) {
      $r .= map_helper::layer_list([
        'includeSwitchers' => $args['include_layer_list_switchers'] ?? TRUE,
        'includeHiddenLayers' => TRUE,
        'layerTypes' => $layerTypes,
      ]);
    }

    $r .= map_helper::map_panel($options, $olOptions);
    $r .= '</div>';

    // Set up a page refresh for dynamic update of the map at set intervals.
    // is_int prevents injection.
    if ($args['refresh_timer'] !== 0 && is_numeric($args['refresh_timer'])) {
      if (isset($args['load_on_refresh']) && !empty($args['load_on_refresh'])) {
        map_helper::$javascript .= "setTimeout('window.location=\"" . $args['load_on_refresh'] . "\";', " . $args['refresh_timer'] . "*1000 );\n";
      }
      else {
        map_helper::$javascript .= "setTimeout('window.location.reload( false );', " . $args['refresh_timer'] . "*1000 );\n";
      }
    }
    return $r;
  }

  /**
   * Draw a map by calling Indicia report when Geoserver isn't available.
   *
   * @return string
   *   The output map panel.
   */
  protected static function mapWithoutGeoserver($auth, $args, $tabalias, $options) {
    iform_load_helpers(['map_helper', 'report_helper']);
    if (isset($options['hoverShowsDetails'])) {
      $options['hoverShowsDetails'] = TRUE;
    }
    // $_GET data for standard params can override displayed location.
    $locationIDToLoad = @$_GET['filter-indexed_location_list']
      ?: @$_GET['filter-indexed_location_id']
      ?: @$_GET['filter-location_list']
      ?: @$_GET['filter-location_id'];
    if (!empty($locationIDToLoad) && preg_match('/^\d+$/', $locationIDToLoad)) {
      $args['display_user_profile_location'] = FALSE;
      $args['location_boundary_id'] = $locationIDToLoad;
    }
    /*
     * aAlow us to call iform_report_get_report_options to get a default
     * report setup, then override report_name.
     */
    $args['report_name'] = '';
    $sharing = empty($args['sharing']) ? 'reporting' : $args['sharing'];
    $params = [
      'taxa_taxon_list_id' => empty($_GET['taxa_taxon_list_id']) ? '' : $_GET['taxa_taxon_list_id'],
      'taxon_meaning_id' => empty($_GET['taxon_meaning_id']) ? '' : $_GET['taxon_meaning_id'],
      'reportGroup' => 'dynamic',
      'autoParamsForm' => FALSE,
      'sharing' => $sharing,
      'rememberParamsReportGroup' => 'dynamic',
      'clickableLayersOutputMode' => 'report',
      'rowId' => 'occurrence_id',
      'ajax' => TRUE,
    ];
    $args['param_presets'] = '';
    $args['param_defaults'] = '';
    $reportOptions = array_merge(
      iform_report_get_report_options($args, $auth['read']),
      [
        'reportGroup' => 'dynamic',
        'autoParamsForm' => FALSE,
        'sharing' => $sharing,
        'readAuth' => $auth['read'],
        'dataSource' => 'reports_for_prebuilt_forms/species_details/species_record_data',
        'extraParams' => $params,
      ]
    );
    // Ensure supplied extraParams are merged, not overwritten.
    if (!empty($options['extraParams'])) {
      $options['extraParams'] = array_merge($reportOptions['extraParams'], $options['extraParams']);
    }
    $reportOptions = array_merge($reportOptions, $options);
    $r = report_helper::report_map($reportOptions);
    $options = array_merge(
      iform_map_get_map_options($args, $auth['read']),
      [
        'featureIdField' => 'occurrence_id',
        'clickForSpatialRef' => FALSE,
        'reportGroup' => 'explore',
        'toolbarDiv' => 'top',
      ],
      $options
    );
    $olOptions = iform_map_get_ol_options($args);
    if ($tabalias) {
      $options['tabDiv'] = $tabalias;
    }
    $r .= map_helper::map_panel($options, $olOptions);
    return $r;
  }

  /**
   * Retrieves the best name to display for a species.
   */
  protected static function get_best_name() {
    if (isset(self::$defaultCommonName)) {
      return self::$defaultCommonName;
    }
    elseif (count(self::$commonNames) > 0) {
      return self::$commonNames[0];
    }
    else {
      return self::$preferred;
    }
  }

  /**
   * Draw the explore button on the page.
   *
   * @return string
   *   The output HTML string.
   */
  protected static function get_control_explore($auth, $args) {
    if (self::$notaxon) {
      return '';
    }
    if (!empty($args['explore_url']) && !empty($args['explore_param_name'])) {
      $url = $args['explore_url'];
      if (strcasecmp(substr($url, 0, 12), '{rootfolder}') !== 0 && strcasecmp(substr($url, 0, 4), 'http') !== 0) {
        $url = '{rootFolder}' . $url;
      }
      $url = str_replace('{rootFolder}', data_entry_helper::getRootFolder(TRUE), $url);
      $url .= (strpos($url, '?') === FALSE) ? '?' : '&';
      $url .= $args['explore_param_name'] . '=' . self::$taxonMeaningId;
      global $indicia_templates;
      $btnLabel = lang::get('Explore records of {1}', self::get_best_name());
      $r = <<<HTML
<div id="taxon-explore-records">
  <a class="$indicia_templates[buttonHighlightedClass]"  href="$url">$btnLabel</a>
</div>
HTML;
    }
    else {
      throw new exception('The page has been setup to use an explore records button, but an "Explore URL" or "Explore Parameter Name" has not been specified.');
    }
    return $r;
  }

  /*
   * Control gets the description of a taxon and displays it on the screen.
   */
  protected static function get_control_speciesnotes($auth, $args) {
    if (self::$notaxon) {
      return '';
    }
    /*
     * We can't return the notes for a specific taxon unless we have an
     * taxa_taxon_list_id, as the meaning could apply to several taxa. In
     * this case ignore the notes control.
     */
    if (empty(self::$taxaTaxonListId)) {
      return '';
    }
    $reportResult = report_helper::get_report_data([
      'readAuth' => $auth['read'],
      'dataSource' => 'library/taxa/species_notes_and_images',
      'useCache' => FALSE,
      'extraParams' => [
        'taxa_taxon_list_id' => self::$taxaTaxonListId,
        'taxon_meaning_id' => self::$taxonMeaningId,
      ],
    ]);
    if (!empty($reportResult[0]['the_text'])) {
      return '<div class="detail-panel" id="detail-panel-speciesnotes"><h3>' .
          lang::get('Species Notes') . '</h3><p>' . $reportResult[0]['the_text'] . '</p></div>';
    }
  }

  /*
   * Control returns all the images associated with a particular taxon meaning
   * in the taxon_images table.
   * These are the the general dictionary images of a species as opposed to
   * the photos control which returns photos associated with occurrences of
   * this species.
   */
  protected static function get_control_speciesphotos($auth, $args, $tabalias, $options) {
    if (self::$notaxon) {
      return '';
    }
    iform_load_helpers(['report_helper']);
    data_entry_helper::add_resource('fancybox');
    $options = array_merge([
      'imageSize' => 'thumb',
      'itemsPerPage' => 6,
      'galleryColCount' => 2,
    ], $options);
    global $indicia_templates;
    // Use this report to return the photos.
    $reportName = 'library/taxa/species_notes_and_images';
    $reportResults = report_helper::report_grid([
      'readAuth' => $auth['read'],
      'dataSource' => $reportName,
      'itemsPerPage' => $options['itemsPerPage'],
      'columns' => [
        [
          'fieldname' => 'the_text',
          'template' => str_replace('{imageSize}', $options['imageSize'], $indicia_templates['speciesDetailsThumbnail']),
        ],
      ],
      'mode' => 'report',
      'autoParamsForm' => FALSE,
      'includeAllColumns' => FALSE,
      'headers' => FALSE,
      'galleryColCount' => $options['galleryColCount'],
      'extraParams' => [
        'taxa_taxon_list_id' => self::$taxaTaxonListId,
        'taxon_meaning_id' => self::$taxonMeaningId,
      ],
    ]);
    return '<div class="detail-panel" id="detail-panel-speciesphotos"><h3>' . lang::get('Photos and media') . '</h3>' .
        $reportResults . '</div>';
  }

  /**
   * Returns a control for picking a species.
   *
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

    // Dynamically generate the species selection control required.
    $r = call_user_func(['data_entry_helper', 'species_autocomplete'], $species_ctrl_opts);

    $r .= '<form method="GET" id="taxon-search-form">';
    if (isset($_GET['xu']) && $_GET['xu'] === 'on') {
      // If xu param set on URL, then set hidden param. This preserves
      // the value of the hectad map exclude unverified if used.
      $r .= '<input type="hidden" id="xu" name="xu" value="on">';
    }
    $r .= '<input type="hidden" id="taxa_taxon_list_id" name="taxa_taxon_list_id"></input>';
    $r .= '</form>';
    global $indicia_templates;
    $r .= "<button id=\"submit-taxon-search-form\" type=\"button\" onclick=\"indiciaFns.speciesDetailsSub()\" class=\"$indicia_templates[buttonHighlightedClass]\">Get details</button>";
    return $r;
  }

  /**
   * Maintain form integrity.
   *
   * @return array
   *   Array of args.
   */
  protected static function getArgDefaults($args) {
    /*
     * When a form version is upgraded introducing new parameters, old forms
     * will not get the defaults for the parameters unless the Edit and Save
     * button is clicked. So, apply some defaults to keep those old forms
     * working.
     */
    if (!isset($args['interface']) || empty($args['interface'])) {
      $args['interface'] = 'one_page';
    }

    if (!isset($args['hide_fields']) || empty($args['hide_fields'])) {
      $args['hide_fields'] = '';
    }

    if (!isset($args['structure']) || empty($args['structure'])) {
      $args['structure'] = '=General=
[speciesdetails]
[rss]
[photos]
[explore]
|
[hectadmap]
[map]';
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

}
