<?php

/**
 * @file
 * Standard report filter tool server-side code.
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

/**
 * A stub base class for filter definition code.
 */
class FilterBase {

}

/**
 * Class defining a "what" filter - species selection.
 */
class filter_what extends FilterBase {

  public function getTitle() {
    return lang::get('What');
  }

  /**
   * Define the HTML required for this filter's UI panel.
   *
   * @param array $readAuth
   *   Read authorisation tokens.
   * @param array $options
   *
   * @return string
   */
  public function getControls(array $readAuth, array $options) {
    global $indicia_templates;
    $r = '';
    // Optional tab texts.
    $orDesignations = $options['elasticsearch'] ? '' : 'or designations, ';
    $orScratchpads = empty($options['scratchpadSearch']) ? '' : 'or scratchpads, ';
    // Language strings for emmitted HTML.
    $lang = [
      'buildListOfGroups' => lang::get('Build a list of groups'),
      'designations' => lang::get('Designations'),
      'includeMyGroups' => lang::get('Include my groups'),
      'level' => lang::get('Level'),
      'myGroups' => lang::get('My groups'),
      'otherFlags' => lang::get('Other flags'),
      'scratchpads' => lang::get('Scratchpads'),
      'selectAnyTab' => lang::get("Select the appropriate tab to filter by species group; or taxon name; {$orDesignations}or taxon level; {$orScratchpads}or other flags (such as marine, or non-native)."),
      'speciesGroups' => lang::get('Species groups'),
      'speciesGroupsFullListLink' => lang::get('Click here to show the full list'),
      'speciesGroupsIntro1' => lang::get('This tab allows you to choose one or more broad species groups. These have to match the group names that we use. Find a group by typing any part of its name, e.g. type "moth" to find "insect - moth".'),
      'speciesGroupsIntro2' => lang::get('Click on the group name from the dropdown list, then click on Add. You can add multiple groups. When you have added all the groups that you want, click Apply to filter the records.'),
      'speciesGroupsIntro3' => lang::get('Go to the "Species or higher taxa" tab to chose an individual species, genus or family etc.'),
      'speciesGroupsLimited' => lang::get('Please note that your access permissions are limiting the groups available to choose from.'),
      'speciesIntro1' => lang::get('This tab allows you to choose one or more individual species, or you can use genus, family, order etc. Type in a scientific or English name and click on the name you want from the dropdown list that appears. Then click on Add.'),
      'speciesIntro2' => lang::get('You can add multiple species or higher taxa. When you have added all the names that you want, click Apply to filter the records.'),
      'speciesLimited' => lang::get('Please note that your access permissions will limit the records returned to the species you are allowed to see.'),
      'speciesOrHigherTaxa' => lang::get('Species or higher taxa'),
    ];
    // There is only one tab when running on the Warehouse.
    if (!isset($options['runningOnWarehouse']) || $options['runningOnWarehouse'] == FALSE) {
      if (!empty($options['scratchpadSearch']) && $options['scratchpadSearch'] == TRUE) {
        $r .= "<p id=\"what-filter-instruct\">" . lang::get('Select the appropriate tab to filter by species group, taxon name, ' .
            'the level within the taxonomic hierarchy, a species scratchpad or other flags such as marine/terrestrial/freshwater or non-native taxa.') . "</p>\n";
      }
      else {
        $r .= "<p id=\"what-filter-instruct\">$lang[selectAnyTab]</p>\n";
      }
    }
    $r .= "<div id=\"what-tabs\">\n";
    // Designations filter currently not available in ES.
    $designationsTab = $options['elasticsearch'] ? '' : "<li id=\"designations-tab-tab\"><a href=\"#designations-tab\" rel=\"address:designations-tab\"><span>$lang[designations]</span></a></li>";
    // Scratchpads tab an optional extra.
    $scratchpadsTab = empty($options['scratchpadSearch']) ? '' : "<li id=\"scratchpad-tab-tab\"><a href=\"#scratchpad-tab\" rel=\"address:scratchpad-tab\"><span>$lang[scratchpads]</span></a></li>";
    // Data_entry_helper::tab_header breaks inside fancybox. So output manually.
    $r .= <<<HTML
<div id="what-tabs">
  <ul>
    <li id="species-group-tab-tab"><a href="#species-group-tab" rel="address:species-group-tab"><span>$lang[speciesGroups]</span></a></li>
    <li id="species-tab-tab"><a href="#species-tab" rel="address:species-tab"><span>$lang[speciesOrHigherTaxa]</span></a></li>
    $designationsTab
    <li id="rank-tab-tab"><a href="#rank-tab" rel="address:rank-tab"><span>$lang[level]</span></a></li>
    $scratchpadsTab
    <li id="flags-tab-tab"><a href="#flags-tab" rel="address:flags-tab"><span>$lang[otherFlags]</span></a></li>
  </ul>
HTML;

    // Species groups tab.
    if (function_exists('hostsite_get_user_field')) {
      $myGroupIds = hostsite_get_user_field('taxon_groups', [], TRUE);
    }
    else {
      $myGroupIds = [];
    }
    $myGroupsPanel = '';
    if ($myGroupIds) {
      $myGroupsData = data_entry_helper::get_population_data([
        'table' => 'taxon_group',
        'extraParams' => $readAuth + [
          'query' => json_encode([
            'in' => ['id', $myGroupIds],
          ]),
          'columns' => 'id,title',
        ],
      ]);
      $myGroupNamesLis = [];
      data_entry_helper::$indiciaData['myGroups'] = $myGroupsData;
      foreach ($myGroupsData as $group) {
        $myGroupNamesLis[] = "<li>$group[title]</li>";
      }
      $myGroupNamesList = implode('', $myGroupNamesLis) . '</li>';
      $myGroupsPanel = <<<HTML
<h3>$lang[myGroups]</h3>
<button type="button" id="my_groups">$lang[includeMyGroups]</button>
<ul class="inline">
  $myGroupNamesList
</ul>
HTML;
    }
    // Warehouse doesn't have master taxon list, so only need warning when not
    // running on warehouse.
    if (empty($options['taxon_list_id']) && (!isset($options['runningOnWarehouse']) || $options['runningOnWarehouse'] == FALSE)) {
      throw new exception('Please specify a @taxon_list_id option in the page configuration.');
    }
    $baseParams = empty($options['taxon_list_id']) ? $readAuth : $readAuth + ['taxon_list_id' => $options['taxon_list_id']];
    $taxonGroupsSubListCtrl = data_entry_helper::sub_list([
      'fieldname' => 'taxon_group_list',
      'report' => 'library/taxon_groups/taxon_groups_used_in_checklist_lookup',
      'captionField' => 'q',
      'valueField' => 'id',
      'extraParams' => $baseParams,
      'addToTable' => FALSE,
      'continueOnBlur' => FALSE,
      'matchContains' => TRUE,
    ]);
    data_entry_helper::$indiciaData['allTaxonGroups'] = data_entry_helper::get_population_data([
      'report' => 'library/taxon_groups/taxon_groups_used_in_checklist',
      'extraParams' => $baseParams,
      // Long cache timeout.
      'cacheTimeout' => 7 * 24 * 60 * 60,
    ]);
    $columns = str_replace(['{attrs}', '{col-1}', '{col-2}'], [
      '',
      "<h3>$lang[buildListOfGroups]</h3>\n$taxonGroupsSubListCtrl",
      $myGroupsPanel,
    ], $indicia_templates['two-col-50']);
    $r .= <<<HTML
<div id="species-group-tab">
  <ul>
    <li>$lang[speciesGroupsIntro1] <a id="show-species-groups">$lang[speciesGroupsFullListLink]</a></li>
    <li>$lang[speciesGroupsIntro2]</li>
    <li>$lang[speciesGroupsIntro3]</li>
  </ul>
  <div class="context-instruct messages warning">$lang[speciesGroupsLimited]</div>
  $columns
</div>
HTML;

    // Species tab.
    $subListOptions = [
      'fieldname' => 'taxa_taxon_list_list',
      'autocompleteControl' => 'species_autocomplete',
      'captionField' => 'searchterm',
      'captionFieldInEntity' => 'searchterm',
      'speciesIncludeBothNames' => TRUE,
      'speciesIncludeTaxonGroup' => TRUE,
      'valueField' => 'preferred_taxa_taxon_list_id',
      'extraParams' => $baseParams,
      'addToTable' => FALSE,
      'continueOnBlur' => FALSE,
    ];
    $taxaSubListCtrl = data_entry_helper::sub_list($subListOptions);
    $r .= <<<HTML
<div id="species-tab">
  <ul>
    <li>$lang[speciesIntro1]</li>
    <li>$lang[speciesIntro2]</li>
  </ul>
  <div class="context-instruct messages warning">$lang[speciesLimited]</div>
  $taxaSubListCtrl
</div>
HTML;

    // Designations tab.
    if (!$options['elasticsearch']) {
      try {
        $r .= "<div id=\"designations-tab\">\n";
        $r .= '<p>' . lang::get('Search for and build a list of designations to filter against') . '</p>' .
          ' <div class="context-instruct messages warning">' . lang::get('Please note that your access permissions will limit the records returned to the species you are allowed to see.') . '</div>';
        $subListOptions = [
          'fieldname' => 'taxon_designation_list',
          'table' => 'taxon_designation',
          'captionField' => 'title',
          'valueField' => 'id',
          'extraParams' => $readAuth,
          'addToTable' => FALSE,
          'autocompleteControl' => 'select',
          'extraParams' => $readAuth + ['orderby' => 'title'],
          'continueOnBlur' => FALSE,
        ];
        $r .= data_entry_helper::sub_list($subListOptions);
        $r .= "</div>\n";
      }
      catch (Exception $e) {
        if (strpos($e->getMessage(), 'Unrecognised entity') !== FALSE) {
          $r .= '<p>' . str_replace(['{message}'], [lang::get('Designations functionality is not enabled on this server.')], $indicia_templates['messageBox']);
        }
        else {
          throw $e;
        }
      }
    }

    // Ranks tab.
    $r .= "<div id=\"rank-tab\">\n";
    $r .= '<p id="level-label">' . lang::get('Include records where the level') . '</p>';
    $r .= data_entry_helper::select([
      'labelClass' => 'auto',
      'fieldname' => 'taxon_rank_sort_order_op',
      'lookupValues' => [
        '=' => lang::get('is'),
        '>=' => lang::get('is the same or lower than'),
        '<=' => lang::get('is the same or higher than'),
      ],
    ]);
    // We fudge this select rather than using data entry helper, since we want
    // to allow duplicate keys which share the same sort order. We also include
    // both the selected ID and sort order in the key, and split it out later.
    $ranks = data_entry_helper::get_population_data([
      'table' => 'taxon_rank',
      'extraParams' => $readAuth + [
        'orderby' => 'sort_order',
        'sortdir' => 'DESC',
      ],
      'cachePerUser' => FALSE,
    ]);
    $r .= '<select id="taxon_rank_sort_order_combined" name="taxon_rank_sort_order_combined" class="' . $indicia_templates['formControlClass'] . '"><option value="">&lt;' . lang::get('Please select') . '&gt;</option>';
    foreach ($ranks as $rank) {
      $r .= "<option value=\"$rank[sort_order]:$rank[id]\">$rank[rank]</option>";
    }
    $r .= '</select>';
    $r .= "</div>\n";

    // Scratchpads tab.
    if (!empty($options['scratchpadSearch']) && $options['scratchpadSearch'] == TRUE) {
      $r .= "<div id=\"scratchpad-tab\">\n";
      $r .= '<p>' . lang::get('Select a species scratchpad to filter against') . '</p>';
      $r .= data_entry_helper::select([
        'blankText' => lang::get('<Please select>'),
        'fieldname' => 'taxa_scratchpad_list_id',
        'id' => 'taxa_scratchpad_list_id',
        'table' => 'scratchpad_list',
        'captionField' => 'title',
        'valueField' => 'id',
        'extraParams' => $readAuth + ['orderby' => 'title'],
        'sharing' => 'reporting',
      ]);
      $r .= "</div>\n";
    }

    // Flags tab.
    $r .= "<div id=\"flags-tab\">\n";
    $r .= '<p>' . lang::get('Select additional flags to filter for.') . '</p>' .
        ' <div class="context-instruct messages warning">' . lang::get('Please note that your access permissions limit the settings you can change on this tab.') . '</div>';
    $r .= data_entry_helper::select([
      'label' => lang::get('Marine species'),
      'fieldname' => 'marine_flag',
      'lookupValues' => [
        'all' => lang::get('Include marine and non-marine species'),
        'Y' => lang::get('Only marine species'),
        'N' => lang::get('Exclude marine species'),
      ],
    ]);
    $r .= data_entry_helper::select([
      'label' => lang::get('Freshwater species'),
      'fieldname' => 'freshwater_flag',
      'lookupValues' => [
        'all' => lang::get('Include freshwater and non-freshwater species'),
        'Y' => lang::get('Only freshwater species'),
        'N' => lang::get('Exclude freshwater species'),
      ],
    ]);
    $r .= data_entry_helper::select([
      'label' => lang::get('Terrestrial species'),
      'fieldname' => 'terrestrial_flag',
      'lookupValues' => [
        'all' => lang::get('Include terrestrial and non-terrestrial species'),
        'Y' => lang::get('Only terrestrial species'),
        'N' => lang::get('Exclude terrestrial species'),
      ],
    ]);
    $r .= data_entry_helper::select([
      'label' => lang::get('Non-native species'),
      'fieldname' => 'non_native_flag',
      'lookupValues' => [
        'all' => lang::get('Include native and non-native species'),
        'Y' => lang::get('Only non-native species'),
        'N' => lang::get('Exclude non-native species'),
      ],
    ]);
    if (!empty($options['allowConfidential'])) {
      $r .= data_entry_helper::select([
        'label' => lang::get('Confidential records'),
        'fieldname' => 'confidential',
        'lookupValues' => [
          'f' => lang::get('Exclude confidential records'),
          't' => lang::get('Only confidential records'),
          'all' => lang::get('Include both confidential and non-confidential records'),
        ],
        'defaultValue' => 'f',
      ]);
    }
    if (!empty($options['allowUnreleased'])) {
      $r .= data_entry_helper::select([
        'label' => lang::get('Unreleased records'),
        'fieldname' => 'release_status',
        'lookupValues' => [
          'R' => lang::get('Exclude unreleased records'),
          'A' => lang::get('Include unreleased records'),
        ],
      ]);
    }
    if (!empty($options['taxaTaxonListAttributeTerms'])) {
      $allAttrIds = [];
      $idx = 0;
      foreach ($options['taxaTaxonListAttributeTerms'] as $label => $attrIds) {
        $allAttrIds += $attrIds;
        $attrs = data_entry_helper::get_population_data([
          'table' => 'taxa_taxon_list_attribute',
          'extraParams' => $readAuth + [
            'query' => json_encode(['in' => ['id' => $attrIds]]),
          ],
        ]);
        $termlistIds = [];
        foreach ($attrs as $attr) {
          if (!empty($attr['termlist_id'])) {
            $termlistIds[] = $attr['termlist_id'];
          }
        }
        $r .= data_entry_helper::sub_list([
          'label' => $label,
          'fieldname' => "taxa_taxon_list_attribute_termlist_term_ids:$idx",
          'table' => 'termlists_term',
          'captionField' => 'term',
          'valueField' => 'id',
          'addToTable' => FALSE,
          'extraParams' => $readAuth + [
            'view' => 'cache',
            'query' => json_encode(['in' => ['termlist_id' => $termlistIds]]),
          ],
          'continueOnBlur' => FALSE,
        ]);
        $idx++;
      }
      helper_base::$javascript .= 'indiciaData.taxaTaxonListAttributeIds = ' . json_encode($allAttrIds) . ";\n";
      helper_base::$javascript .= 'indiciaData.taxaTaxonListAttributeLabels = ' .
        json_encode(array_keys($options['taxaTaxonListAttributeTerms'])) . ";\n";
    }
    $r .= '</div>';
    $r .= "</div>\n";
    data_entry_helper::enable_tabs([
      'divId' => 'what-tabs',
    ]);

    return $r;
  }

}

/**
 * Class defining a "when" filter - date selection.
 */
class filter_when extends FilterBase {

  public function getTitle() {
    return lang::get('When');
  }

  /**
   * Define the HTML required for this filter's UI panel.
   */
  public function getControls() {
    // Additional helptext in case it is needed when a context is applied.
    $r = '<p class="helpText context-instruct">' . lang::get('Please note that your access permissions are limiting the record dates available.').'</p>';
    $r .= '<fieldset><legend>' . lang::get('Which date field to filter on') . '</legend>';
    $r .= data_entry_helper::select([
      'label' => lang::get('Date field'),
      'fieldname' => 'date_type',
      'lookupValues' => [
        'recorded' => lang::get('Field record date'),
        'input' => lang::get('Date of record input'),
        'edited' => lang::get('Date of last edit or verification'),
        'verified' => lang::get('Date of last verification'),
      ],
    ]);
    $r .= '</fieldset>';
    $r .= '<fieldset class="exclusive"><legend>' . lang::get('Specify a year') . '</legend>';
    $r .= '<div class="form-inline">';
    $r .= data_entry_helper::select([
      'label' => lang::get('Year'),
      'fieldname' => 'date_year_op',
      'lookupValues' => [
        '=' => lang::get('equals'),
        '<=' => lang::get('is in or before'),
        '>=' => lang::get('is in or after'),
      ],
      'blankText' => '- Select year filter -',
    ]);
    $r .= data_entry_helper::text_input([
      'fieldname' => 'date_year',
      'attributes' => [
        'type' => 'number',
        'min' => 0,
        'max' => date('Y'),
      ],
      'default' => date('Y'),
    ]);

    // Add date_year and date_year_op support to standard params.
    // Proxy
    // Documentation

    $r .= '</div></fieldset>';
    $r .= '<fieldset class="exclusive"><legend>' . lang::get('Or, specify a date range for the records to include') . '</legend>';
    $r .= data_entry_helper::date_picker([
      'label' => lang::get('Records from'),
      'fieldname' => 'date_from',
      'allowFuture' => TRUE,
    ]);
    $r .= data_entry_helper::date_picker([
      'label' => lang::get('Records to'),
      'fieldname' => 'date_to',
      'allowFuture' => TRUE,
    ]);
    $r .= '</fieldset>';
    $r .= '<fieldset class="exclusive" id="age"><legend>' . lang::get('Or, specify a maximum age for the records to include') . '</legend>';
    $r .= data_entry_helper::text_input([
      'label' => lang::get('Max. record age'),
      'helpText' => lang::get('How old records can be before they are dropped from the report? ' .
          'Enter a number followed by the unit (days, weeks, months or years), e.g. "2 days" or "1 year".'),
      'fieldname' => 'date_age',
      'validation' => ['regex[/^[0-9]+\s*(day|week|month|year)(s)?$/]'],
    ]);
    $r .= '</fieldset>';
    return $r;
  }

}

/**
 * Class defining a "where" filter - geographic selection.
 */
class filter_where extends FilterBase {

  /**
   * Define the pane's title.
   *
   * @return string
   *   Title.
   */
  public function getTitle() {
    return lang::get('Where');
  }

  /**
   * Define the HTML required for this filter's UI panel.
   *
   * Options available:
   * * **personSiteAttrId** - a multi-value location attribute used to link
   *   users to their recording sites.
   * * **includeSitesCreatedByUser** - boolean which defines if sites that the
   *   user is the creator of are available. Default TRUE.
   * * **indexedLocationTypeIds** - array of location type IDs for types that
   *   are available and which are indexed in the spatial index builder.
   * * **otherLocationTypeIds** - array of location type IDs for types that are
   *   available and which are not indexed.
   *
   * @param array $readAuth
   *   Read authorisation tokens.
   * @param array $options
   *   Control options array. Options include:
   *   * includeSitesCreatedByUser - Defines if user created sites are
   *     available for selection. True or false.
   *   * indexedLocationTypeIds - array of location type IDs for sites which
   *     can be selected using the index system for filtering.
   *   * otherLocationTypeIds - as above, but does not use the spatial index
   *     builder. Should only be used for smaller or less complex site
   *     boundaries.
   *
   * @return string
   *   Controls HTML
   *
   * @throws \exception
   */
  public function getControls(array $readAuth, array $options) {
    if (function_exists('iform_load_helpers')) {
      iform_load_helpers(['map_helper']);
    }
    else {
      // When running on warehouse we don't have iform_load_helpers.
      require_once DOCROOT . 'client_helpers/map_helper.php';
    }
    $options = array_merge([
      'includeSitesCreatedByUser' => TRUE,
      'indexedLocationTypeIds' => [],
      'otherLocationTypeIds' => [],
    ], $options);
    data_entry_helper::$javascript .= "indiciaData.includeSitesCreatedByUser=" . ($options['includeSitesCreatedByUser'] ? 'true' : 'false') . ";\n";
    data_entry_helper::$javascript .= "indiciaData.personSiteAttrId=" . (empty($options['personSiteAttrId']) ? 'false' : $options['personSiteAttrId']) . ";\n";
    $r = '<fieldset class="inline"><legend>' . lang::get('Filter by site or place') . '</legend>';
    $r .= '<p>' . lang::get('Choose from the following place filtering options.') . '</p>' .
        '<div class="context-instruct messages warning">' . lang::get('Please note that your access permissions are limiting the areas you are able to include.') . '</div>';
    $r .= '<fieldset class="exclusive">';
    // Top level of sites selection.
    $sitesLevel1 = [];
    $this->addProfileLocation($readAuth, 'location', $sitesLevel1);
    $this->addProfileLocation($readAuth, 'location_expertise', $sitesLevel1);
    $this->addProfileLocation($readAuth, 'location_collation', $sitesLevel1);
    if (!empty($options['personSiteAttrId']) || $options['includeSitesCreatedByUser']) {
      $sitesLevel1['my'] = lang::get('My sites') . '...';
    }
    // The JS needs to know which location types are indexed so it can build the correct filter.
    data_entry_helper::$javascript .= "indiciaData.indexedLocationTypeIds=" . json_encode($options['indexedLocationTypeIds']) . ";\n";
    $locTypes = array_merge($options['indexedLocationTypeIds'], $options['otherLocationTypeIds']);
    $locTypes = data_entry_helper::get_population_data([
      'table' => 'termlists_term',
      'extraParams' => $readAuth + [
        'view' =>
        'cache',
        'query' => json_encode(['in' => ['id' => $locTypes]]),
      ],
      'cachePerUser' => FALSE,
    ]);
    foreach ($locTypes as $locType) {
      $sitesLevel1[$locType['id']] = $locType['term'] . '...';
    }
    $r .= '<div id="ctrl-wrap-location_list" class="form-row ctrl-wrap">';
    $r .= data_entry_helper::select([
      'fieldname' => 'site-type',
      'label' => lang::get('Choose an existing site or location'),
      'lookupValues' => $sitesLevel1,
      'blankText' => '<' . lang::get('Please select') . '>',
      'controlWrapTemplate' => 'justControl',
    ]);
    $r .= data_entry_helper::sub_list([
      'fieldname' => 'location_list',
      'controlWrapTemplate' => 'justControl',
      'table' => 'location',
      'captionField' => 'name',
      'valueField' => 'id',
      'addToTable' => FALSE,
      'extraParams' => $readAuth,
      'matchContains' => TRUE,
    ]);

    $r .= '</div></fieldset>';
    $r .= '<br/><fieldset class="exclusive">';
    $r .= data_entry_helper::text_input([
      'label' => lang::get('Or, search for site names containing'),
      'fieldname' => 'location_name',
    ]);
    $r .= '</fieldset>';
    $r .= '<fieldset class="exclusive">';
    // Build the array of spatial reference systems into a format Indicia can use.
    $systems = [];
    // Set default system.
    $systemsConfig = '4326';
    if (!empty($options['sref_systems'])) {
      $systemsConfig = $options['sref_systems'];
    }
    elseif (function_exists('hostsite_get_config_value')) {
      $systemsConfig = hostsite_get_config_value('iform', 'spatial_systems', '4326');
    }
    $list = explode(',', str_replace(' ', '', $systemsConfig));
    foreach ($list as $system) {
      $systems[$system] = lang::get("sref:$system");
    }
    $r .= data_entry_helper::sref_and_system([
      'label' => lang::get('Or, find records in map reference'),
      'fieldname' => 'sref',
      'systems' => $systems,
    ]);
    $r .= '</fieldset></fieldset>';
    $r .= '<fieldset><legend>' . lang::get('Or, select a drawing tool in the map toolbar then draw a boundary to find intersecting records') . '</legend>';
    if (empty($options['linkToMapDiv'])) {
      if (function_exists('hostsite_get_config_value')) {
        $initialLat = hostsite_get_config_value('iform', 'map_centroid_lat', 55);
        $initialLong = hostsite_get_config_value('iform', 'map_centroid_long', -1);
        $initialZoom = (int) hostsite_get_config_value('iform', 'map_zoom', 5);
      }
      else {
        $initialLat = 55;
        $initialLong = -1;
        $initialZoom = 5;
      }
      // Need our own map on the popup.
      $mapOpts = [
        'divId' => 'filter-pane-map',
        'presetLayers' => ['osm'],
        'editLayer' => TRUE,
        'initial_lat' => $initialLat,
        'initial_long' => $initialLong,
        'initial_zoom' => $initialZoom,
        'width' => '100%',
        'height' => 400,
        'standardControls' => [
          'panZoomBar',
          'drawPolygon',
          'drawLine',
          'drawPoint',
          'modifyFeature',
          'clearEditLayer',
        ],
        'allowPolygonRecording' => TRUE,
        'readAuth' => $readAuth,
        'gridRefHint' => TRUE,
      ];
      // Enable Google layers if API key available.
      if (!empty(helper_base::$google_maps_api_key)) {
        $mapOpts['presetLayers'][] = 'google_streets';
        $mapOpts['presetLayers'][] = 'google_satellite';
        $mapOpts['standardControls'][] = 'layerSwitcher';
      }
      // Pass through buffering option.
      if (!empty($options['selectFeatureBufferProjection'])) {
        $mapOpts['selectFeatureBufferProjection'] = $options['selectFeatureBufferProjection'];
      }
      $r .= map_helper::map_panel($mapOpts);
    }
    else {
      // We are going to use an existing map for drawing boundaries etc. So
      // prepare a container.
      $r .= '<div id="filter-map-container"></div>';
      data_entry_helper::$javascript .= "indiciaData.linkToMapDiv='" . $options['linkToMapDiv'] . "';\n";
    }
    $r .= '</fieldset>';
    return $r;
  }

  /**
   * Utility method to add one of the user profile location fields to an array of options.
   */
  private function addProfileLocation($readAuth, $profileField, &$outputArr) {
    if (function_exists('hostsite_get_user_field')) {
      $locality = hostsite_get_user_field($profileField);
      if ($locality) {
        $loc = data_entry_helper::get_population_data([
          'table' => 'location',
          'extraParams' => $readAuth + ['id' => $locality],
        ]);
        $loc = $loc[0];
        $outputArr["loc:$loc[id]"] = $loc['name'];
      }
    }
  }

}

/**
 * Class defining a "who" filter - recorder selection.
 */
class filter_who extends FilterBase {

  /**
   * Define the pane's title.
   *
   * @return string
   *   Title.
   */
  public function getTitle() {
    return lang::get('Who');
  }

  /**
   * Define the HTML required for this filter's UI panel.
   */
  public function getControls() {
    $r = '<div class="context-instruct messages warning">' . lang::get('Please note, you cannnot change this setting because of your access permissions in this context.') . '</div>';
    $r .= data_entry_helper::radio_group([
      'label' => lang::get('Recorders'),
      'fieldname' => 'my_records',
      'lookupValues' => [
        '' => lang::get('Records from all recorders'),
        '1' => lang::get('Only include my records'),
        '0' => lang::get('Exclude my records'),
      ],
    ]);
    $r .= data_entry_helper::text_input([
      'label' => lang::get('Or, filter by name or part of name'),
      'fieldname' => 'recorder_name',
    ]);
    return $r;
  }

}

/**
 * Class defining a "id" filter - record selection by known id.
 */
class filter_occ_id extends FilterBase {

  /**
   * Define the pane's title.
   *
   * @return string
   *   Title.
   */
  public function getTitle() {
    return lang::get('Record ID');
  }

  /**
   * Define the HTML required for this filter's UI panel.
   */
  public function getControls() {
    $r = '<div id="ctrl-wrap-occ_id" class="form-row ctrl-wrap">';
    $r .= data_entry_helper::select([
      'label' => lang::get('Record ID'),
      'fieldname' => 'occ_id_op',
      'lookupValues' => [
        '=' => lang::get('is'),
        '>=' => lang::get('is at least'),
        '<=' => lang::get('is at most'),
      ],
      'controlWrapTemplate' => 'justControl',
    ]);
    $r .= data_entry_helper::text_input([
      'fieldname' => 'occ_id',
      'class' => 'control-width-2',
      'controlWrapTemplate' => 'justControl',
      'helpText' => lang::get('Filter by the system assigned record ID.'),
    ]);
    $r .= '</div>';
    $r .= '<div>' . lang::get('or') . '</div>';
    $r .= data_entry_helper::text_input([
      'label' => lang::get('External key is'),
      'fieldname' => 'occurrence_external_key',
      'class' => 'control-width-2',
      'helpText' => lang::get("Filter by a key assigned by the record's originating system - for imported records."),
    ]);

    return $r;
  }

}

/**
 * Class defining a "id" filter - sample selection by known id.
 */
class filter_smp_id extends FilterBase {

  /**
   * Define the pane's title.
   *
   * @return string
   *   Title.
   */
  public function getTitle() {
    return lang::get('Sample ID');
  }

  /**
   * Define the HTML required for this filter's UI panel.
   */
  public function getControls() {
    $r = '<div id="ctrl-wrap-smp_id" class="form-row ctrl-wrap">';
    $r .= data_entry_helper::select([
      'label' => lang::get('Sample ID'),
      'fieldname' => 'smp_id_op',
      'lookupValues' => [
        '=' => lang::get('is'),
        '>=' => lang::get('is at least'),
        '<=' => lang::get('is at most'),
      ],
      'controlWrapTemplate' => 'justControl',
    ]);
    $r .= data_entry_helper::text_input([
      'fieldname' => 'smp_id',
      'class' => 'control-width-2',
      'controlWrapTemplate' => 'justControl',
    ]);
    $r .= '</div>';
    return $r;
  }

}

/**
 * Class defining a "quality" filter - record status, photos, verification rule check selection.
 */
class filter_quality extends FilterBase {

  /**
   * Define the pane's title.
   *
   * @return string
   *   Title.
   */
  public function getTitle() {
    return lang::get('Quality');
  }

  /**
   * Define the HTML required for this filter's UI panel.
   */
  public function getControls($readAuth, $options, $ctls = [
    'status',
    'certainty',
    'auto',
    'difficulty',
    'photo',
    'licences',
    'media_licences',
  ]) {
    global $indicia_templates;
    $r = '';
    if (in_array('status', $ctls)) {
      $r .= '<div class="context-instruct messages warning">' . lang::get('Please note, your options for quality filtering are restricted by your access permissions in this context.') . '</div>';
      $qualityOptions = [
        'P' => lang::get('Pending'),
        'V' => lang::get('Accepted (all)'),
        'V1' => lang::get('Accepted - correct only'),
        'V2' => lang::get('Accepted - considered correct only'),
        'R' => lang::get('Not accepted (all)'),
        'R4' => lang::get('Not accepted - unable to verify only'),
        'R5' => lang::get('Not accepted - incorrect only'),
        'C3' => lang::get('Plausible'),
        'D' => lang::get('Queried'),
        'all' => lang::get('All records'),
      ];
      if ($options['sharing'] === 'verification') {
        $qualityOptions['OV'] = lang::get('Verified by other verifiers');
        $qualityOptions['A'] = lang::get('Answered');
      }
      $qualityOptions['all'] = lang::get('All records');
      if ($options['elasticsearch']) {
        // Elasticsearch doesn't currently support recorder trust.
        unset($qualityOptions['T']);
        // Additional option available for Elasticsearch verification.
        if ($options['sharing'] === 'verification') {
          $qualityOptions['OV'] = lang::get('Verified by other verifiers');
        }
      }
      $options = array_merge([
        'label' => lang::get('Record status'),
      ], $options);
      $includeExcludeRadios = data_entry_helper::radio_group([
        'fieldname' => 'quality_op',
        'id' => 'quality_op' . (empty($options['standalone']) ? '' : '--standalone'),
        'lookupValues' => [
          'in' => lang::get('Include'),
          'not in' => lang::get('Exclude'),
        ],
        'default' => 'in',
      ]);
      $qualityCheckboxes = data_entry_helper::checkbox_group([
        'fieldname' => 'quality',
        'id' => 'quality' . (empty($options['standalone']) ? '' : '--standalone'),
        'lookupValues' => $qualityOptions,
      ]);
      $lang = [
        'cancel' => lang::get('Cancel'),
        'ok' => lang::get('Ok'),
        'recordStatus' => lang::get('Record status'),
      ];
      $qualityInput = data_entry_helper::text_input([
        'label' => $lang['recordStatus'],
        'fieldname' => 'quality-filter',
        'class' => 'quality-filter',
      ]);
      $r .= <<<HTML
<div class="quality-cntr">
  $qualityInput
  <input type="hidden" name="quality" class="quality-value $indicia_templates[formControlClass]" />
  <div class="quality-pane" style="display: none">
    $includeExcludeRadios
    $qualityCheckboxes
    <div class="pull-right">
      <button type="button" class="$indicia_templates[buttonHighlightedClass] btn-xs ok">$lang[ok]</button>
      <button type="button" class="$indicia_templates[buttonDefaultClass] btn-xs cancel">$lang[cancel]</button>
    </div>
  </div>
</div>
HTML;
    }
    if (in_array('certainty', $ctls)) {
      $r .= data_entry_helper::checkbox_group([
        'label' => lang::get('Recorder certainty'),
        'fieldname' => 'certainty[]',
        'id' => 'certainty-filter',
        'lookupValues' => [
          'C' => lang::get('Certain'),
          'L' => lang::get('Likely'),
          'U' => lang::get('Uncertain'),
          'NS' => lang::get('Not stated'),
        ],
      ]);
    }
    if (in_array('auto', $ctls)) {
      $checkOptions = [
        '' => lang::get('No filtering on checks'),
        'P' => lang::get('All checks passed'),
        'F' => lang::get('Any checks failed'),
      ];
      if (!empty($options['customRuleCheckFilters'])) {
        $checkOptions['PC'] = lang::get('All custom rule checks passed');
        $checkOptions['FC'] = lang::get('Any custom rule checks failed');
      }
      if (!empty($options['autocheck_rules'])) {
        foreach ($options['autocheck_rules'] as $rule) {
          $checkOptions[$rule] = lang::get("$rule failed");
        };
      }
      $r .= data_entry_helper::select([
        'label' => empty($options['customRuleCheckFilters']) ? lang::get('Automated checks') : lang::get('Automated or custom rule checks'),
        'fieldname' => 'autochecks',
        'lookupValues' => $checkOptions,
      ]);
    }
    if (in_array('difficulty', $ctls)) {
      global $indicia_templates;
      $s1 = data_entry_helper::select([
        'label' => lang::get('Identification difficulty'),
        'fieldname' => 'identification_difficulty_op',
        'lookupValues' => [
          '=' => lang::get('is'),
          '>=' => lang::get('is at least'),
          '<=' => lang::get('is at most'),
        ],
      ]);
      $s2 = data_entry_helper::select([
        'label' => lang::get('Level'),
        'fieldname' => 'identification_difficulty',
        'lookupValues' => [
          '' => lang::get('any'),
          1 => lang::get('difficulty 1 - easiest to ID'),
          2 => lang::get('difficulty 2'),
          3 => lang::get('difficulty 3'),
          4 => lang::get('difficulty 4'),
          5 => lang::get('difficulty 5 - hardest to ID'),
          6 => lang::get('difficulty 1 - custom rule'),
        ],
        'controlWrapTemplate' => 'justControl',
      ]);
      $r .= str_replace(
        ['{attrs}', '{col-1}', '{col-2}'],
        [' id="id-diff-cntr" style="display: none"', $s1, $s2],
        $indicia_templates['two-col-50']
      );
    }
    if (in_array('photo', $ctls)) {
      $r .= data_entry_helper::select([
        'label' => lang::get('Records and photos'),
        'fieldname' => 'has_photos',
        'lookupValues' => [
          '' => lang::get('-No filter-'),
          '1' => lang::get('With'),
          '0' => lang::get('Without'),
        ],
      ]);
    }
    if (in_array('licences', $ctls)) {
      $r .= data_entry_helper::checkbox_group([
        'label' => lang::get('Include records with'),
        'fieldname' => 'licences',
        'lookupValues' => [
          'none' => lang::get('-No licence-'),
          'open' => lang::get('Open licence (OGL, CCO, CC BY)'),
          'restricted' => lang::get('Restricted licence (CC BY-NC)'),
        ],
      ]);
    }
    if (in_array('media_licences', $ctls)) {
      $r .= data_entry_helper::checkbox_group([
        'label' => lang::get('Include records with photos that have'),
        'fieldname' => 'media_licences',
        'lookupValues' => [
          'none' => lang::get('-No licence-'),
          'open' => lang::get('Open licence (OGL, CCO, CC BY)'),
          'restricted' => lang::get('Restricted licence (CC BY-NC)'),
        ],
      ]);
    }
    return $r;
  }

}

/**
 * Class defining a "quality" filter for samples - based on record status.
 */
class filter_quality_sample extends FilterBase {

  /**
   * Define the pane's title.
   *
   * @return string
   *   Title.
   */
  public function getTitle() {
    return lang::get('Quality');
  }

  /**
   * Define the HTML required for this filter's UI panel.
   */
  public function getControls() {
    $r = '<div class="context-instruct messages warning">' . lang::get('Please note, your options for quality filtering are restricted by your access permissions in this context.') . '</div>';
    $r .= data_entry_helper::select([
      'label' => lang::get('Samples to include'),
      'fieldname' => 'quality',
      'class' => 'quality-filter',
      'lookupValues' => [
        'V' => lang::get('Accepted records only'),
        'P' => lang::get('Not reviewed'),
        '!R' => lang::get('Exclude not accepted records'),
        '!D' => lang::get('Exclude queried or not accepted records'),
        'all' => lang::get('All records'),
        'R' => lang::get('Not accepted records only'),
      ],
    ]);
    $r .= data_entry_helper::select([
      'label' => lang::get('Automated checks'),
      'fieldname' => 'autochecks',
      'lookupValues' => [
        '' => lang::get('Not filtered'),
        'P' => lang::get('Only include records that pass all automated checks'),
        'F' => lang::get('Only include records that fail at least one automated check'),
      ],
    ]);
    $r .= data_entry_helper::select([
      'label' => 'Photos',
      'fieldname' => 'has_photos',
      'lookupValues' => [
        '' => 'Include records with or without photosq',
        '1' => 'Only include records which have photos',
        '0' => 'Exclude records which have photos',
      ],
    ]);
    return $r;
  }

}

/**
 * Class defining a "source" filter - website, survey, input form selection.
 */
class filter_source extends FilterBase {

  /**
   * Define the pane's title.
   *
   * @return string
   *   Title.
   */
  public function getTitle() {
    return lang::get('Source');
  }

  /**
   * Define the HTML required for this filter's UI panel.
   */
  public function getControls(array $readAuth, array $options) {
    if (function_exists('iform_load_helpers')) {
      iform_load_helpers(['report_helper']);
    }
    else {
      // When running on warehouse we don't have iform_load_helpers.
      require_once DOCROOT . 'client_helpers/report_helper.php';
    }
    $r = '<div class="context-instruct messages warning">' . lang::get('Please note, or options for source filtering are limited by your access permissions in this context.') . '</div>';
    $r .= '<div>';
    $lang = [
      'exclude' => lang::get('Exclude'),
      'include' => lang::get('Include'),
      'inputForms' => lang::get('Input forms'),
      'recordsFrom' => lang::get('records from'),
      'selectSurveyToLoadInputForms' => lang::get('All records for the selected websites will be shown. Select survey datasets to include to choose from the associated recording forms.'),
      'selectWebsiteToLoadSurveys' => lang::get('Select websites to include to choose from the associated survey datasets.'),
      'surveyDatasets' => lang::get('Survey datasets'),
      'websites' => lang::get('Websites'),
    ];
    // If running on the warehouse then use a single website (as the user is on
    // the website details->milestones tab for a single website) so don't
    // display website selection (which is based on website sharing).
    if (!isset($options['runningOnWarehouse']) || $options['runningOnWarehouse'] == FALSE) {
      $websites = report_helper::get_report_data([
        'dataSource' => 'library/websites/websites_list',
        'readAuth' => $readAuth,
        'caching' => TRUE,
        'cachePerUser' => FALSE,
        'extraParams' => [
          'sharing' => $options['sharing'] === 'me' ? 'reporting' : $options['sharing'],
          'orderby' => 'title',
        ],
      ]);
      // Build the list filter control HTML.
      $websitesFilterInput = data_entry_helper::text_input([
        'fieldname' => 'websites-search',
        'class' => 'filter-exclude',
        'attributes' => ['placeholder' => lang::get('Type here to filter')],
      ]);
      $surveysFilterInput = data_entry_helper::text_input([
        'fieldname' => 'surveys-search',
        'class' => 'filter-exclude',
        'attributes' => ['placeholder' => lang::get('Type here to filter')],
      ]);
      $inputFormsFilterInput = data_entry_helper::text_input([
        'fieldname' => 'input_forms-search',
        'class' => 'filter-exclude',
        'attributes' => ['placeholder' => lang::get('Type here to filter')],
      ]);
      // Build the filter operation controls.
      $websitesOpSelect = $this->getOperationSelectInput('website');
      $surveysOpSelect = $this->getOperationSelectInput('survey');
      $inputFormsOpSelect = $this->getOperationSelectInput('input_form');
      // If only 1 website, can skip the first column.
      if (count($websites) > 1) {
        $websiteListItems = '';
        foreach ($websites as $website) {
          $websiteListItems .= <<<HTML
    <li>
      <input type="checkbox" value="$website[id]" id="check-website-$website[id]"/>
      <label for="check-website-$website[id]">$website[title]</label>
    </li>
HTML;
        }
        $r .= <<<HTML
<div id="filter-websites" class="filter-popup-columns">
  <h3>$lang[websites]</h3>
  $websitesFilterInput
  $websitesOpSelect
  <ul id="website-list-checklist">
    $websiteListItems
  </ul>
</div>

HTML;
      }
    }
    else {
      // If running on the warehouse then use a single website id (as the user
      // is on the website details->milestones tab for a single website) so
      // supply this as a param, we don't need to worry about sharing as other
      // websites will have their own milestones.
      $r .= '<input type="hidden" value="' . $source['id'] . '" id="check-website-' . $source['id'] . '" checked/>';
    }
    // Put a cached list of all available surveys in indiciaData to save hits
    // when building source pane descriptions.
    $surveys = report_helper::get_report_data([
      'dataSource' => 'library/surveys/surveys_list',
      'readAuth' => $readAuth,
      'caching' => TRUE,
      'cachePerUser' => FALSE,
      'extraParams' => [
        'sharing' => $options['sharing'] === 'me' ? 'reporting' : $options['sharing'],
        'orderby' => 'title',
      ],
    ]);
    helper_base::$indiciaData['allSurveysList'] = [];
    foreach ($surveys as $survey) {
      helper_base::$indiciaData['allSurveysList'][$survey['id']] = $survey['title'];
    }
    $r .= <<<HTML
<div id="filter-surveys" class="filter-popup-columns">
  <h3>$lang[surveyDatasets]</h3>
  $surveysFilterInput
  $surveysOpSelect
  <p class="alert alert-info" style="display: none">$lang[selectWebsiteToLoadSurveys]</p>
  <ul id="survey-list-checklist">
  </ul>
</div>
<div id="filter-input_forms" class="filter-popup-columns">
  <h3>$lang[inputForms]</h3>
  $inputFormsFilterInput
  $inputFormsOpSelect
  <p class="alert alert-info" style="display: none">$lang[selectSurveyToLoadInputForms]</p>
  <ul id="input_form-list-checklist">
  </ul>
</div>
HTML;
    $r .= '</div><p>' . lang::get('Leave any list unticked to leave that list unfiltered.') . '</p>';
    return $r;
  }

  /**
   * Build a select control for the source operation columns.
   *
   * @param string $type
   *   Column type, website, survey or input_form.
   *
   * @return string
   *   Control HTML.
   */
  private function getOperationSelectInput($type) {
    return data_entry_helper::select([
      'fieldname' => "{$type}_list_op",
      'id' => "filter-{$type}s-mode",
      'lookupValues' => [
        'in' => lang::get('Include records from'),
        'not in' => lang::get('Exclude records from'),
      ],
    ]);
  }

}

/**
 * Output a standalone media/photos drop-down filter.
 */
function media_filter_control($readAuth, $options) {
  iform_load_helpers(['report_helper']);
  report_helper::add_resource('reportfilters');
  $ctl = new filter_quality();
  $r = '<div class="standalone-media-filter">';
  $r .= $ctl->getControls($readAuth, $options, ['photo']);
  $r .= '</div>';
  return $r;
}

/**
 * Output a standalone status drop-down filter.
 */
function status_filter_control($readAuth, $options) {
  iform_load_helpers(['report_helper']);
  report_helper::add_resource('reportfilters');
  $ctl = new filter_quality();
  $r = '<div class="standalone-quality-filter">';
  $r .= $ctl->getControls($readAuth, $options, ['status']);
  $r .= '</div>';
  return $r;
}

/**
 * Code to output a standardised report filtering panel.
 *
 * Filters can be saved and loaded by each user. Additionally, filters can
 * define permissions to a certain task, e.g. they can be used to define the
 * context within which someone can verify. In this case they provide the
 * "outer limit" of the available records.
 * Requires a [map] control on the page. If you don't want a map, the current
 * option is to include one anyway and use css to hide the #map-container div.
 *
 * @param array $readAuth
 *   Pass read authorisation tokens.
 * @param array $options
 *   Options array with the following possibilities:
 *   * sharing - define the record sharing task that is being filtered against.
 *     Options are reporting (default), peer_review, verification, moderation,
 *     data_flow, editing.
 *   * context_id - can also be passed as URL parameter. Force the initial
 *     selection of a particular context (a record which has
 *     defines_permissions=TRUE in the filters table. Set to "default" to
 *     select their profile verification settings when sharing=verification.
 *   * entity - defaults to occurrence. Set to sample to use for sample based
 *     reports, which removes the filtering options relating to the occurrence
 *     level of data.
 *   * filter_id - can also be passed as URL parameter. Force the initial
 *     selection of a particular filter record in the filters table.
 *   * filterTypes - allows control of the list of filter panels available,
 *     e.g. to turn one off. Associative array keyed by category  so that the
 *     filter panels can be grouped (use a blank key if not required). The
 *     array values are an array of or strings with a comma separated list of
 *     the filter types to included in the category - options are what, where,
 *     when, who, quality, source.
 *   * filter-#name# - set the initial value of a report filter parameter
 *     #name#.
 *   * overridePermissionsFilters - set to true to ignore any permissions
 *     filters defined for this user for this sharing mode. Use with
 *     caution as it prevents permissions from applying. An example usage
 *     is for a report page that gets it's filter from the group it is
 *     linked to rather than the user's permissions.
 *   * allowLoad - set to FALSE to disable the load bar at the top of the panel.
 *   * allowSave - set to FALSE to disable the save bar at the foot of the
 *     panel.
 *   * presets - provide an array of preset filters to provide in the filters
 *     drop down. Choose from all-records, my-records, my-groups (uses your
 *     list of taxon groups in the user account), my-locality (uses your
 *     recording locality from the user  account), my-groups-locality (uses
 *     taxon groups and recording locality from the user account),
 *     my-queried-records, queried-records, answered-records, accepted-records,
 *     not-accepted-records.
 *   * generateFilterListCallback - a callback to allow custom versions of the
 *     filters to be used, utilising the standard filter user interface.
 *   * taxaTaxonListAttributeTerms - a JSON encoded list of groups of taxa
 *     taxon list attributes. Each group will be added to the What panel's
 *     Other flags tab allowing the user to filter for terms linked via the
 *     attributes to the species. For example, if habitat data can be in
 *     taxa_taxon_list_attributes ID 1 or 2 and food data in attributes 3 or 4
 *     then the value can be set to {"Habitat":[1,2],"Food":[3,4]} resulting
 *     in 2 controls being added to the Other flags tab for filtering on this
 *     information.
 *   * elasticsearch - set to TRUE to disable search options which are not
 *     compatible with data in elasticsearch.
 *   * customRuleCheckFilters - set to TRUE if using Elasticsearch and custom
 *     verification rule tools are available for the user to apply their own
 *     data checks. Enables options for filtering on the outcome of custom
 *     rule checks.
 * @param int $website_id
 *   The current website's warehouse ID.
 * @param string $hiddenStuff
 *   Output parameter which will contain the hidden popup HTML that will be
 *   shown using fancybox during filter editing. Should be appended AFTER any
 *   form element on the page as nested forms are not allowed.
 *
 * @return string
 *   HTML for the report filter panel
 *
 * @throws \exception
 *   If attempting to use an unrecognised filter preset.
 */
function report_filter_panel(array $readAuth, $options, $website_id, &$hiddenStuff) {
  if (function_exists('iform_load_helpers')) {
    iform_load_helpers(['report_helper']);
  }
  else {
    // When running on warehouse we don't have iform_load_helpers.
    require_once DOCROOT . 'client_helpers/report_helper.php';
  }
  global $indicia_templates;
  if (!empty($_POST['filter:sharing'])) {
    $options['sharing'] = $_POST['filter:sharing'];
  }
  $options = array_merge([
    'sharing' => 'reporting',
    'admin' => FALSE,
    'adminCanSetSharingTo' => [
      'R' => lang::get('reporting'),
      'V' => lang::get('verification'),
    ],
    'allowLoad' => TRUE,
    'allowSave' => TRUE,
    'redirect_on_success' => '',
    'presets' => [
      'all-records',
      'my-records',
      'my-queried-records',
      'my-queried-or-not-accepted-records',
      'my-not-reviewed-records',
      'my-accepted-records',
      'my-groups',
      'my-locality',
      'my-groups-locality',
    ],
    'entity' => 'occurrence',
    'elasticsearch' => FALSE,
    'customRuleCheckFilters' => FALSE,
    'autocheck_rules' => [
      'identification_difficulty',
      'period',
      'period_within_year',
      'without_polygon',
    ],
  ], $options);
  // Introduce some extra quick filters useful for verifiers.
  if ($options['sharing'] === 'verification') {
    $options['presets'] = array_merge([
      'queried-records',
      'answered-records',
      'accepted-records',
      'not-accepted-records',
    ], $options['presets']);
  }
  if ($options['entity'] === 'sample') {
    unset($options['presets']['my-groups']);
    unset($options['presets']['my-groups-locality']);
  }
  // If in the warehouse we don't need to worry about the iform master list.
  if (function_exists('hostsite_get_config_value')) {
    $options = array_merge(
      ['taxon_list_id' => hostsite_get_config_value('iform', 'master_checklist_id', 0)],
      $options
    );
  }
  $options['sharing'] = report_filters_sharing_code_to_full_term($options['sharing']);
  $options['sharingCode'] = report_filters_full_term_to_sharing_code($options['sharing']);
  if (!preg_match('/^(reporting|peer_review|verification|data_flow|moderation|editing|me)$/', $options['sharing'])) {
    return 'The @sharing option must be one of reporting, peer_review, verification, data_flow, moderation, ' .
        "editing or me (currently $options[sharing]).";
  }
  report_helper::add_resource('reportfilters');
  report_helper::add_resource('validation');
  report_helper::add_resource('fancybox');
  report_helper::add_resource('font_awesome');
  if (function_exists('hostsite_add_library')) {
    hostsite_add_library('collapse');
  }
  $lang = [
    'context' => lang::get('Context'),
    'deleteFilter' => lang::get('Delete filter'),
    'filter' => lang::get('Filter'),
    'filterChanged' => lang::get('This filter has been changed'),
    'filterSaveInstruct' => lang::get('To save these filter settings for future use, give the filter a name then click Save Filter.'),
    'filterName' => lang::get('Filter name'),
    'saveFilter' => lang::get('Save filter'),
    'selectStoredFilter' => lang::get('Select stored filter'),
    'storedFilters' => lang::get('Stored filters'),
  ];
  $filterData = report_filters_load_existing($readAuth, $options['sharingCode']);
  $existing = '';
  $contexts = '';
  $contextDefs = [];
  $customDefs = [];
  if (!empty($_GET['context_id'])) {
    $options['context_id'] = $_GET['context_id'];
  }
  if (!empty($_GET['filter_id'])) {
    $options['filter_id'] = $_GET['filter_id'];
  }
  if (!empty($_GET['filters_user_id'])) {
    $options['filters_user_id'] = $_GET['filters_user_id'];
  }
  if (!empty($options['customFilters'])) {
    foreach ($options['customFilters'] as $idx => $filter) {
      $filterData[] = [
        'id' => "custom-$idx",
        'title' => $filter['title'],
        'defines_permissions' => isset($filter['defines_permissions']) ? $filter['defines_permissions'] : 'f',
      ];
      $customDefs["custom-$idx"] = $filter['definition'];
    }
  }
  // Add some preset filters in.
  // If in the warehouse we don't need to worry about user specific preferences
  // when setting up milestones.
  if (function_exists('hostsite_get_user_field')) {
    foreach ($options['presets'] as $preset) {
      $title = FALSE;
      switch ($preset) {
        case 'all-records':
          $title = lang::get('All records');
          break;

        case 'my-records':
          if (hostsite_get_user_field('indicia_user_id')) {
            $title = lang::get('My records');
          }
          break;

        case 'my-queried-records':
          if (hostsite_get_user_field('indicia_user_id')) {
            $title = lang::get('My queried records');
          }
          break;

        case 'my-queried-or-not-accepted-records':
          if (hostsite_get_user_field('indicia_user_id')) {
            $title = lang::get('My not accepted or queried records');
          }
          break;

        case 'my-not-reviewed-records':
          if (hostsite_get_user_field('indicia_user_id')) {
            $title = lang::get('My not reviewed records');
          }
          break;

        case 'my-accepted-records':
          if (hostsite_get_user_field('indicia_user_id')) {
            $title = lang::get('My accepted records');
          }
          break;

        case 'my-groups':
          if (hostsite_get_user_field('taxon_groups', FALSE, TRUE)) {
            $title = lang::get('Records in species groups I like to record');
          }
          break;

        case 'my-locality':
          if (hostsite_get_user_field('location')) {
            $title = lang::get('Records in the locality I generally record in');
          }
          break;

        case 'my-groups-locality':
          if (hostsite_get_user_field('taxon_groups', FALSE, TRUE) && hostsite_get_user_field('location')) {
            $title = lang::get('Records of my species groups in my locality');
          }
          break;

        case 'queried-records':
          $title = lang::get('Queried records');
          break;

        case 'answered-records':
          $title = lang::get('Records with answers');
          break;

        case 'accepted-records':
          $title = lang::get('Accepted records');
          break;

        case 'not-accepted-records':
          $title = lang::get('Not accepted records');
          break;

        default:
          throw new exception("Unsupported preset $preset for the filter panel");
      }
      if ($title) {
        $presetFilter = [
          'id' => $preset,
          'title' => $title,
          'defines_permissions' => 'f',
        ];
        $filterData[] = $presetFilter;
      }
    }
    if (count($options['presets'])) {
      if ($groups = hostsite_get_user_field('taxon_groups', FALSE, TRUE)) {
        data_entry_helper::$javascript .= "indiciaData.userPrefsTaxonGroups='" . implode(',', $groups) . "';\n";
      }
      if ($location = hostsite_get_user_field('location')) {
        data_entry_helper::$javascript .= "indiciaData.userPrefsLocation=" . $location . ";\n";
      }
    }
    if ($options['sharing'] === 'verification') {
      // Apply legacy verification settings from their profile.
      $location_id = hostsite_get_user_field('location_expertise');
      $taxon_group_ids = hostsite_get_user_field('taxon_groups_expertise', FALSE, TRUE);
      $survey_ids = hostsite_get_user_field('surveys_expertise', FALSE, TRUE);
      if ($location_id || $taxon_group_ids || $survey_ids) {
        $selected = (!empty($options['context_id']) && $options['context_id'] === 'default') ? 'selected="selected" ' : '';
        $contexts .= "<option value=\"default\" $selected>" . lang::get('My verification records') . "</option>";
        $def = [];
        if ($location_id) {
          // User profile geographic limits should always be based on an
          // indexed location.
          $def['indexed_location_list'] = $location_id;
        }
        if ($taxon_group_ids) {
          $def['taxon_group_list'] = implode(',', $taxon_group_ids);
          $def['taxon_group_names'] = [];
          $groups = data_entry_helper::get_population_data([
            'table' => 'taxon_group',
            'extraParams' => $readAuth + ['id' => $taxon_group_ids],
          ]);
          foreach ($groups as $group) {
            $def['taxon_group_names'][$group['id']] = $group['title'];
          }
        }
        if ($survey_ids) {
          $def['survey_list'] = implode(',', array_filter($survey_ids));
        }
        $contextDefs['default'] = $def;
      }
    }
  }
  foreach ($filterData as $filter) {
    if ($filter['defines_permissions'] === 't') {
      if (empty($options['overridePermissionsFilters'])) {
        $selected = (!empty($options['context_id']) && $options['context_id'] == $filter['id']) ? 'selected="selected" ' : '';
        $contexts .= "<option value=\"$filter[id]\" $selected>$filter[title]</option>";
        $contextDefs[$filter['id']] = json_decode($filter['definition'], TRUE);
      }
    }
    else {
      $selected = (!empty($options['filter_id']) && $options['filter_id'] == $filter['id']) ? 'selected="selected" ' : '';
      $existing .= "<option value=\"$filter[id]\" $selected>$filter[title]</option>";
      if ($selected) {
        // Ensure the initially selected filter gets applied across all reports
        // on the page.
        report_helper::$initialFilterParamsToApply = array_merge(
          report_helper::$initialFilterParamsToApply, json_decode($filter['definition'], TRUE)
        );
      }
    }
  }
  $r = '<div id="standard-params">';
  if ($options['allowSave'] && $options['admin']) {
    if (empty($_GET['filters_user_id'])) {
      // New filter to create, so sharing type can be edited.
      $reload = data_entry_helper::get_reload_link_parts();
      $reloadPath = $reload['path'];
      if (count($reload['params'])) {
        $reloadPath .= '?' . data_entry_helper::array_to_query_string($reload['params']);
      }
      $r .= "<form action=\"$reloadPath\" method=\"post\" >";
      $r .= data_entry_helper::select([
        'label' => lang::get('Select filter type'),
        'fieldname' => 'filter:sharing',
        'lookupValues' => $options['adminCanSetSharingTo'],
        'afterControl' => '<input type="submit" value="Go"/>',
        'default' => $options['sharingCode'],
      ]);
      $r .= '</form>';
    }
    else {
      // Existing filter to edit, type is therefore fixed. JS will fill these
      // values in.
      $r .= '<p>' . lang::get('This filter is for <span id="sharing-type-label"></span>.') . '</p>';
      $r .= data_entry_helper::hidden_text(['fieldname' => 'filter:sharing']);
    }
  }
  if ($options['allowLoad']) {
    $r .= <<<HTML
<div class="header ui-toolbar ui-widget-header ui-helper-clearfix form-inline">
  <span id="active-filter-label">$lang[storedFilters]</span>
  <span class="changed" style="display:none" title="$lang[filterChanged]">*</span>
HTML;
    $r .= '<div>';
    if ($customDefs) {
      data_entry_helper::$javascript .= "indiciaData.filterCustomDefs = " . json_encode($customDefs) . ";\n";
    }
    if ($contexts) {
      data_entry_helper::$javascript .= "indiciaData.filterContextDefs = " . json_encode($contextDefs) . ";\n";
      if (count($contextDefs) > 1) {
        $r .= <<<HTML
<label for="context-filter">$lang[context]:</label>
<select id="context-filter" class="$indicia_templates[formControlClass]">$contexts</select>
HTML;
      }
      else {
        $keys = array_keys($contextDefs);
        $r .= '<input type="hidden" id="context-filter" value="' . $keys[0] . '" />';
      }
      // Ensure the initially selected context filter gets applied across all
      // reports on the page. Tag _context to the end of param names so the
      // filter system knows they are not user changeable.
      $contextFilterOrig = empty($options['context_id']) ?
          array_values($contextDefs)[0] : $contextDefs[$options['context_id']];
      $contextFilter = [];
      foreach ($contextFilterOrig as $key => $value) {
        if ($value !== '') {
          $contextFilter["{$key}_context"] = $value;
        }
      }
      // A context filter is loaded initially. It doesn't need to be set in the fixedFilterParamsToApply since the
      // report filter panel enforces the correct context is applied at all times.
      report_helper::$initialFilterParamsToApply = array_merge(report_helper::$initialFilterParamsToApply, $contextFilter);
    }
    // Remove bits of the definition that shouldn't go in as a report filter
    // parameter as they contain arrays.
    $definitionKeysToExcludeFromFilter = [
      'taxon_group_names',
      'higher_taxa_taxon_list_names',
      'taxa_taxon_list_names',
      'taxon_designation_list_names',
    ];
    foreach ($definitionKeysToExcludeFromFilter as $key) {
      unset(report_helper::$initialFilterParamsToApply[$key]);
      unset(report_helper::$initialFilterParamsToApply["{$key}_context"]);
    }
    $r .= <<<HTML
<label for="select-filter">$lang[filter]:</label>
<select id="select-filter" class="$indicia_templates[formControlClass]">
  <option value="" selected="selected">$lang[selectStoredFilter]...</option>
  $existing
</select>
HTML;
    $r .= helper_base::apply_static_template('button', [
      'id' => 'filter-apply',
      'title' => lang::get('Apply filter'),
      'class' => ' class="' . $indicia_templates['buttonDefaultClass'] . '"',
      'caption' => lang::get('Apply filter'),
    ]);
    $r .= helper_base::apply_static_template('button', [
      'id' => 'filter-reset',
      'title' => lang::get('Reset filter'),
      'class' => ' class="' . $indicia_templates['buttonDefaultClass'] . '"',
      'caption' => lang::get('Reset'),
    ]);
    $r .= helper_base::apply_static_template('button', [
      'id' => 'filter-build',
      'title' => lang::get('Create a custom filter'),
      'class' => ' class="' . $indicia_templates['buttonDefaultClass'] . '"',
      'caption' => lang::get('Create a filter'),
    ]);
    $r .= '</div></div>';
    $r .= '<div id="filter-details" style="display: none">';
    $r .= '<img src="' . data_entry_helper::$images_path . 'nuvola/close-22px.png" width="22" height="22" alt="Close filter builder" title="' .
        lang::get('Close filter builder') . '" class="button" id="filter-done"/>' . "\n";
  }
  else {
    $r .= '<div id="filter-details">';
    if (!empty($options['filter_id'])) {
      $r .= "<input type=\"hidden\" id=\"select-filter\" value=\"$options[filter_id]\"/>";
    }
    elseif (!empty($options['filters_user_id'])) {
      $r .= "<input type=\"hidden\" id=\"select-filters-user\" value=\"$options[filters_user_id]\"/>";
    }
  }
  $r .= '<div id="filter-panes">';
  $filters = [];
  if (isset($options['generateFilterListCallback'])) {
    $filters = call_user_func($options['generateFilterListCallback'], $options['entity']);
  }
  elseif ($options['entity'] === 'occurrence') {
    $filters = [
      'filter_what' => new filter_what(),
      'filter_where' => new filter_where(),
      'filter_when' => new filter_when(),
      'filter_who' => new filter_who(),
      'filter_occ_id' => new filter_occ_id(),
      'filter_quality' => new filter_quality(),
      'filter_source' => new filter_source(),
    ];
  }
  elseif ($options['entity'] === 'sample') {
    $filters = [
      'filter_where' => new filter_where(),
      'filter_when' => new filter_when(),
      'filter_who' => new filter_who(),
      'filter_smp_id' => new filter_smp_id(),
      'filter_quality' => new filter_quality_sample(),
      'filter_source' => new filter_source(),
    ];
  }
  if (!empty($options['filterTypes'])) {
    $filterModules = [];
    foreach ($options['filterTypes'] as $category => $list) {
      // $list can be an array or comma separated list.
      if (is_array($list)) {
        $list = implode(',', $list);
      }
      $paneNames = 'filter_' . str_replace(',', ',filter_', $list);
      $paneList = explode(',', $paneNames);
      $filterModules[$category] = array_intersect_key($filters, array_fill_keys($paneList, 1));
    }
  }
  else {
    $filterModules = ['' => $filters];
  }
  foreach ($filterModules as $category => $list) {
    if ($category) {
      $r .= <<<HTML
<fieldset class="collapsible collapsed">'
   <legend>
     <span class="fieldset-legend">'
      $category
    </span>
  </legend>
<div class="fieldset-wrapper">

HTML;
    }
    $r .= '<div class="pane-row">';
    $done = 0;
    foreach ($list as $moduleName => $module) {
      $r .= "<div class=\"pane\" id=\"pane-$moduleName\"><a class=\"fb-filter-link\" href=\"#controls-$moduleName\"><span class=\"pane-title\">" . $module->getTitle() . '</span>';
      $r .= '<span class="filter-desc"></span></a>';
      $r .= "</div>";
      $done++;
      // Split rows if necessary.
      if (count($list) >= 5 && $done === 3) {
        $r .= '</div><div class="pane-row">';
      }
    }
    $r .= '</div>';
    if ($category) {
      $r .= '</div></fieldset>';
    }
  }
  // End of filter panes div.
  $r .= '</div>';
  if ($options['allowSave']) {
    $inline = $options['admin'] ? '' : ' form-inline';
    $r .= <<<HTML
<p>$lang[filterSaveInstruct]</p>
<div class="save-controls$inline">
<label for="filter:title">$lang[filterName]:</label> <input id="filter:title" class="$indicia_templates[formControlClass]" />
HTML;

    if ($options['admin']) {
      $r .= '<br/>';
      if (empty($options['adminCanSetSharingTo'])) {
        throw new exception('Report standard params panel in admin mode so adminCanSetSharingTo option must be populated.');
      }
      $r .= data_entry_helper::autocomplete([
        'label' => lang::get('For who?'),
        'fieldname' => 'filters_user:user_id',
        'table' => 'user',
        'valueField' => 'id',
        'captionField' => 'person_name',
        'formatFunction' => "function(item) { return item.person_name + ' (' + item.email_address + ')'; }",
        'extraParams' => $readAuth + ['view' => 'detail'],
        'class' => 'control-width-5',
      ]);
      $r .= data_entry_helper::textarea([
        'label' => lang::get('Description'),
        'fieldname' => 'filter:description',
      ]);
    }
    $r .= <<<HTML
<button class="$indicia_templates[buttonHighlightedClass]" id="filter-save"><i class="fas fa-save"></i> $lang[saveFilter]</button>
<button class="$indicia_templates[buttonWarningClass] disabled" id="filter-delete"><i class="fas fa-trash-alt"></i> $lang[deleteFilter]</button>
HTML;
    $r .= '</div>';
  }
  // End of clearfix div.
  $r .= '</div>';
  $r .= '</div>';
  if (!empty($options['filters_user_id'])) {
    // If we are preloading based on a filter user ID, we need to get the
    // information now so that the sharing mode can be known when loading
    // controls.
    $fu = data_entry_helper::get_population_data([
      'table' => 'filters_user',
      'extraParams' => $readAuth + ['id' => $options['filters_user_id']],
      'caching' => FALSE,
    ]);
    if (count($fu) !== 1) {
      throw new exception('Could not find filter user record');
    }
    $options['sharing'] = report_filters_sharing_code_to_full_term($fu[0]['filter_sharing']);
  }
  // Create the hidden panels required to populate the popups for setting each
  // type of filter up.
  $hiddenStuff = '';
  $noDescriptionLangStrings = [];
  foreach ($filterModules as $category => $list) {
    foreach ($list as $moduleName => $module) {
      $hiddenStuff .= "<div style=\"display: none\"><div class=\"filter-popup\" id=\"controls-$moduleName\"><form action=\"#\" class=\"filter-controls\"><fieldset>" . $module->getControls($readAuth, $options) .
        '<button class="' . $indicia_templates['buttonDefaultClass'] . '" type="button" onclick="jQuery.fancybox.close();">' . lang::get('Cancel') . '</button>' .
        '<button class="' . $indicia_templates['buttonHighlightedClass'] . '" type="submit">' . lang::get('Apply') . '</button></fieldset></form></div></div>';
      $shortName = str_replace('filter_', '', $moduleName);
      $noDescriptionLangStrings[$shortName] = 'Click to Filter ' . ucfirst($shortName);
    }
  }
  report_helper::addLanguageStringsToJs('reportFiltersNoDescription', $noDescriptionLangStrings);
  report_filters_set_parser_language_strings();
  report_helper::addLanguageStringsToJs('reportFilters', [
    'back' => 'Back',
    'cannotDeselectAllLicences' => 'You cannot deselect all licence options otherwise no records will be returned.',
    'cannotDeselectAllMediaLicences' => 'You cannot deselect all photo licence options otherwise no records with photos will be returned.',
    'confirmFilterChangedLoad' => 'Do you want to load the selected filter and lose your current changes?',
    'confirmFilterDelete' => 'Are you sure you want to permanently delete the {title} filter?',
    'createAFilter' => 'Create a filter',
    'quality:P' => 'Pending',
    'quality:V' => 'Accepted (all)',
    'quality:V1' => 'Accepted - correct only',
    'quality:V2' => 'Accepted - considered correct only',
    'quality:R' => 'Not accepted (all)',
    'quality:R4' => 'Not accepted - unable to verify only',
    'quality:R5' => 'Not accepted - incorrect only',
    'quality:C3' => 'Plausible',
    'quality:D' => 'Queried',
    'quality:A' => 'Answered',
    'quality:all' => 'All records',
    'quality_op:in' => 'Include',
    'quality_op:not in' => 'Exclude',
    'filterDeleted' => 'The filter has been deleted',
    'filterExistsOverwrite' => 'A filter with that name already exists. Would you like to overwrite it?',
    'filterSaved' => 'The filter has been saved',
    'licenceIs' => 'Licence is',
    'mediaLicenceIs' => 'Media licence is',
    'modifyFilter' => 'Modify filter',
    'orListJoin' => ' or ',
    'pleaseSelect' => 'Please select',
    'recorderCertaintyWas' => 'Recorder certainty was',
  ]);
  if (function_exists('iform_ajaxproxy_url')) {
    report_helper::$javascript .= "indiciaData.filterPostUrl='" . iform_ajaxproxy_url(NULL, 'filter') . "';\n";
    report_helper::$javascript .= "indiciaData.filterAndUserPostUrl='" . iform_ajaxproxy_url(NULL, 'filter_and_user') . "';\n";
  }
  report_helper::$javascript .= "indiciaData.filterSharing='" . strtoupper(substr($options['sharing'], 0, 1)) . "';\n";
  if (function_exists('hostsite_get_user_field')) {
    report_helper::$javascript .= "indiciaData.user_id='" . hostsite_get_user_field('indicia_user_id') . "';\n";
  }
  else {
    report_helper::$javascript .= "indiciaData.user_id='" . $_SESSION['auth_user']->id . "';\n";
  }
  report_helper::$javascript .= "indiciaData.admin='" . $options['admin'] . "';\n";
  report_helper::$javascript .= "indiciaData.redirectOnSuccess='$options[redirect_on_success]';\n";
  // Load up the filter, BEFORE any AJAX load of the grid code. First fetch any URL param overrides.
  $getParams = [];
  $optionParams = [];
  foreach ($_GET as $key => $value) {
    if (substr($key, 0, 7) === 'filter-') {
      $getParams[substr($key, 7)] = $value;
    }
  }
  foreach ($options as $key => $value) {
    if (substr($key, 0, 7) === 'filter-') {
      // The parameter value might be json encoded.
      $decoded = json_decode($value, TRUE);
      // If not json then need to use option value as it is.
      $value = $decoded ? $decoded : $value;
      $optionParams[substr($key, 7)] = $value;
    }
  }
  $allParams = array_merge(['quality' => 'R', 'quality_op' => 'not in'], $optionParams, $getParams);
  if (!empty($allParams)) {
    report_helper::$initialFilterParamsToApply = array_merge(report_helper::$initialFilterParamsToApply, $allParams);
    $json = json_encode($allParams);
    report_helper::$onload_javascript .= <<<JS
var params = $json;
indiciaData.filter.def = $.extend(indiciaData.filter.def, params);
indiciaData.filter.resetParams = $.extend({}, params);

JS;
  }
  $getParams = empty($getParams) ? '{}' : json_encode($getParams);
  if (!empty($options['filters_user_id']) && isset($fu)) {
    report_helper::$onload_javascript .= "loadFilterUser(" . json_encode($fu[0]) . ", $getParams);\n";
  }
  else {
    report_helper::$onload_javascript .= <<<JS
if ($('#select-filter').val()) {
  loadFilter($('#select-filter').val(), $getParams);
} else {
  $.each($('#filter-panes .pane'), function (idx, pane) {
    var name = pane.id.replace(/^pane-filter_/, '');
    if (indiciaData.filterParser[name].fixLegacyFilter) {
      indiciaData.filterParser[name].fixLegacyFilter(indiciaData.filter.def);
    }
  });
  indiciaFns.applyFilterToReports(false);
}
// Set initial description in the quality filter input.
$('.quality-filter').val(indiciaData.filterParser.quality.statusDescriptionFromFilter(
    indiciaData.filter.def.quality, indiciaData.filter.def.quality_op));

JS;
  }
  // Any standard parameters we supply get activated, so ensure they don't
  // appear on a params form.
  report_helper::$filterParamsToGloballySkip = report_helper::$initialFilterParamsToApply;
  return $r;
}

/**
 * Gets the report data for the list of existing filters this user can access.
 *
 * @param array $readAuth
 *   Read authentication tokens.
 * @param string $sharing
 *   Sharing mode.
 * @param bool $caching
 *   Pass TRUE to enable caching for faster responses.
 */
function report_filters_load_existing(array $readAuth, $sharing, $caching = FALSE) {
  if (function_exists('hostsite_get_user_field')) {
    $userId = hostsite_get_user_field('indicia_user_id');
    if ($userId === NULL) {
      return [];
    }
  }
  else {
    $userId = $_SESSION['auth_user']->id;
  }
  if (function_exists('iform_load_helpers')) {
    iform_load_helpers(['report_helper']);
  }
  else {
    // When running on warehouse we don't have iform_load_helpers.
    require_once DOCROOT . 'client_helpers/report_helper.php';
  }
  $filters = report_helper::get_report_data([
    'dataSource' => 'library/filters/filters_list_minimal',
    'readAuth' => $readAuth,
    'caching' => $caching,
    'extraParams' => [
      'filter_sharing_mode' => $sharing === 'M' ? 'R' : $sharing,
      'defines_permissions' => '',
      'filter_user_id' => $userId,
    ],
  ]);
  return $filters;
}

/**
 * Convert a sharing mode code into the full term for that sharing mode.
 *
 * @param string $code
 *   Sharing code, e.g. 'M'.
 *
 * @return string
 *   Full term, e.g. 'moderation'. Returns the input parameter as-is, if not a sharing code.
 */
function report_filters_sharing_code_to_full_term($code) {
  if (preg_match('/^[RVPDM]$/', $code)) {
    switch ($code) {
      case 'R':
        return 'reporting';

      case 'V':
        return 'verification';

      case 'P':
        return 'peer_review';

      case 'D':
        return 'data_flow';

      case 'M':
        return 'moderation';

      case 'E':
        return 'editing';

    }
  }
  return $code;
}

/**
 * Convert a sharing mode full term into the single letter code that sharing mode.
 *
 * @param string $term
 *   Full term, e.g. 'moderation'.
 *
 * @return string
 *   Sharing code, e.g. 'M'.
 */
function report_filters_full_term_to_sharing_code($term) {
  return strtoupper(substr($term, 0, 1));
}

/**
 * Utility function to add laguage strings to Javascript for the JS filter parser.
 */
function report_filters_set_parser_language_strings() {
  report_helper::addLanguageStringsToJs('reportFilterParser', [
    'Autochecks_F' => 'Automated checks failed',
    'Autochecks_FC' => 'Any custom verification rule check failed',
    'Autochecks_identification_difficulty' => 'ID difficulty check failed',
    'Autochecks_P' => 'Automated checks passed.',
    'Autochecks_period' => 'Year range check failed',
    'Autochecks_period_within_year' => 'Date range check failed',
    'Autochecks_PC' => 'All custom verification rule checks passed.',
    'Autochecks_without_polygon' => 'Distribution check failed',
    'IdentificationDifficulty' => 'Identification difficulty',
    'HasPhotos' => 'Only include records which have photos',
    'HasNoPhotos' => 'Exclude records which have photos',
    'ListJoin' => ' or ',
    'MyRecords' => 'Only include my records',
    'NotMyRecords' => 'Exclude my records',
    'RecorderNameContains' => 'Recorder name contains {1}',
    'OnlyConfidentialRecords' => 'Only confidential records',
    'AllConfidentialRecords' => 'Include both confidential and non-confidential records',
    'NoConfidentialRecords' => 'Exclude confidential records',
    'includeUnreleasedRecords' => 'Include unreleased records',
    'excludeUnreleasedRecords' => 'Exclude unreleased records',
  ]);
}
