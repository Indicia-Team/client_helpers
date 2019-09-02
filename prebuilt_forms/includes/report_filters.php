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
 * @package Client
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link http://code.google.com/p/indicia/
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
   * @throws \exception
   */
  public function get_controls($readAuth, $options) {
    $r = '';
    // There is only one tab when running on the Warehouse.
    if (!isset($options['runningOnWarehouse']) || $options['runningOnWarehouse'] == FALSE)
      $r .= "<p id=\"what-filter-instruct\">" . lang::get('You can filter by species group (first tab), a selection of families or other higher taxa (second tab), ' .
          'a selection of genera or species (third tab), the level within the taxonomic hierarchy (fourth tab) or other flags such as marine taxa (fifth tab).') . "</p>\n";
    $r .= '<div id="what-tabs">' . "\n";
    // Data_entry_helper::tab_header breaks inside fancybox. So output manually.
    $r .= '<ul>' .
        '<li id="species-group-tab-tab"><a href="#species-group-tab" rel="address:species-group-tab"><span>' . lang::get('Species groups') . '</span></a></li>';
    $r .= '<li id="species-tab-tab"><a href="#species-tab" rel="address:species-tab"><span>' . lang::get('Species or higher taxa') . '</span></a></li>';
    $r .= '<li id="designations-tab-tab"><a href="#designations-tab" rel="address:designations-tab"><span>' . lang::get('Designations') . '</span></a></li>' .
        '<li id="rank-tab-tab"><a href="#rank-tab" rel="address:rank-tab"><span>' . lang::get('Level') . '</span></a></li>' .
        '<li id="flags-tab-tab"><a href="#flags-tab" rel="address:flags-tab"><span>' . lang::get('Other flags') . '</span></a></li>' .
        '</ul>';
    $r .= '<div id="species-group-tab">' . "\n";
    if (function_exists('hostsite_get_user_field')) {
      $myGroupIds = hostsite_get_user_field('taxon_groups', array(), TRUE);
    }
    else {
      $myGroupIds = array();
    }
    if ($myGroupIds) {
      $r .= '<h3>' . lang::get('My groups') . '</h3>';
      $myGroupsData = data_entry_helper::get_population_data(array(
        'table' => 'taxon_group',
        'extraParams' => $readAuth + array('query' => json_encode(array('in' => array('id', $myGroupIds))))
      ));
      $myGroupNames = array();
      data_entry_helper::$javascript .= "indiciaData.myGroups = [];\n";
      foreach ($myGroupsData as $group) {
        $myGroupNames[] = $group['title'];
        data_entry_helper::$javascript .= "indiciaData.myGroups.push([$group[id],'$group[title]']);\n";
      }
      $r .= '<button type="button" id="my_groups">'.lang::get('Include my groups').'</button>';
      $r .= '<ul class="inline"><li>' . implode('</li><li>', $myGroupNames) . '</li></ul>';
      $r .= '<h3>' . lang::get('Build a list of groups') . '</h3>';
    }
    // Warehouse doesn't have master taxon list, so only need warning when not running on warehouse
    if (empty($options['taxon_list_id']) && (!isset($options['runningOnWarehouse'])||$options['runningOnWarehouse'] == FALSE)) {
      throw new exception('Please specify a @taxon_list_id option in the page configuration.');
    }
    $r .= '<p>' . lang::get('Search for and build a list of species groups to include') . '</p>' .
        ' <div class="context-instruct messages warning">' . lang::get('Please note that your access permissions are limiting the groups available to choose from.') . '</div>';
    $baseParams = empty($options['taxon_list_id']) ? $readAuth : $readAuth + array('taxon_list_id' => $options['taxon_list_id']);
    $r .= data_entry_helper::sub_list(array(
      'fieldname' => 'taxon_group_list',
      'report' => 'library/taxon_groups/taxon_groups_used_in_checklist_lookup',
      'captionField' => 'q',
      'valueField' => 'id',
      'extraParams' => $baseParams,
      'addToTable' => FALSE
    ));
    $r .= "</div>\n";
    $r .= '<div id="species-tab">' . "\n";
    $r .= '<p>' . lang::get('Search for and build a list of species or higher taxa to include.') . '</p>' .
        ' <div class="context-instruct messages warning">' . lang::get('Please note that your access permissions will limit the records returned to the species you are allowed to see.') . '</div>';
    $subListOptions = array(
      'fieldname' => 'taxa_taxon_list_list',
      'autocompleteControl' => 'species_autocomplete',
      'captionField' => 'searchterm',
      'captionFieldInEntity' => 'searchterm',
      'speciesIncludeBothNames' => TRUE,
      'speciesIncludeTaxonGroup' => TRUE,
      'valueField' => 'preferred_taxa_taxon_list_id',
      'extraParams' => $baseParams,
      'addToTable' => FALSE,
    );
    $r .= data_entry_helper::sub_list($subListOptions);
    $r .= "</div>\n";
    $r .= "<div id=\"designations-tab\">\n";
    $r .= '<p>' . lang::get('Search for and build a list of designations to filter against') . '</p>' .
      ' <div class="context-instruct messages warning">' . lang::get('Please note that your access permissions will limit the records returned to the species you are allowed to see.') . '</div>';
    $subListOptions = array(
      'fieldname' => 'taxon_designation_list',
      'table' => 'taxon_designation',
      'captionField' => 'title',
      'valueField' => 'id',
      'extraParams' => $readAuth,
      'addToTable' => FALSE,
      'autocompleteControl' => 'select',
      'extraParams' => $readAuth + array('orderby' => 'title')
    );
    $r .= data_entry_helper::sub_list($subListOptions);
    $r .= "</div>\n";
    $r .= "<div id=\"rank-tab\">\n";
    $r .= '<p id="level-label">' . lang::get('Include records where the level') . '</p>';
    $r .= data_entry_helper::select(array(
      'labelClass' => 'auto',
      'fieldname' => 'taxon_rank_sort_order_op',
      'lookupValues' => array(
        '=' => lang::get('is'),
        '>=' => lang::get('is the same or lower than'),
        '<=' => lang::get('is the same or higher than')
      )
    ));
    // we fudge this select rather than using data entry helper, since we want to allow duplicate keys which share the same sort order. We also
    // include both the selected ID and sort order in the key, and split it out later.
    $ranks = data_entry_helper::get_population_data(array(
      'table' => 'taxon_rank',
      'extraParams' => $readAuth + array('orderby' => 'sort_order', 'sortdir' => 'DESC')
    ));
    $r .= '<select id="taxon_rank_sort_order_combined" name="taxon_rank_sort_order_combined"><option value="">&lt;' . lang::get('Please select') . '&gt;</option>';
    foreach ($ranks as $rank) {
      $r .= "<option value=\"$rank[sort_order]:$rank[id]\">$rank[rank]</option>";
    }
    $r .= '</select>';
    $r .= "</div>\n";
    $r .= "<div id=\"flags-tab\">\n";
    $r .= '<p>' . lang::get('Select additional flags to filter for.') . '</p>' .
        ' <div class="context-instruct messages warning">' . lang::get('Please note that your access permissions limit the settings you can change on this tab.') . '</div>';
    $r .= data_entry_helper::select(array(
      'label' => lang::get('Marine species'),
      'fieldname' => 'marine_flag',
      'lookupValues' => array(
        'all' => lang::get('Include marine and non-marine species'),
        'Y' => lang::get('Only marine species'),
        'N' => lang::get('Exclude marine species')
      )
    ));
    if (!empty($options['allowConfidential'])) {
      $r .= data_entry_helper::select([
        'label' => lang::get('Confidential records'),
        'fieldname' => 'confidential',
        'lookupValues' => [
          'f' => lang::get('Exclude confidential records'),
          't' => lang::get('Only confidential records'),
          'all' => lang::get('Include both confidential and non-confidential records'),
        ],
        'defaultValue' => 'f'
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
            'query' => json_encode(['in' => ['id' => $attrIds]])
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
        ]);
        $idx++;
      }
      helper_base::$javascript .= 'indiciaData.taxaTaxonListAttributeIds = ' . json_encode($allAttrIds) . ";\n";
      helper_base::$javascript .= 'indiciaData.taxaTaxonListAttributeLabels = ' .
        json_encode(array_keys($options['taxaTaxonListAttributeTerms'])) . ";\n";
    }
    $r .= '</div>';
    $r .= "</div>\n";
    data_entry_helper::enable_tabs(array(
      'divId' => 'what-tabs'
    ));

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
  public function get_controls() {
    // Additional helptext in case it is needed when a context is applied.
    $r = '<p class="helpText context-instruct">' . lang::get('Please note that your access permissions are limiting the record dates available.').'</p>';
    $r .= '<fieldset><legend>' . lang::get('Which date field to filter on') . '</legend>';
    $r .= data_entry_helper::select(array(
      'label' => lang::get('Date field'),
      'fieldname' => 'date_type',
      'lookupValues' => array(
        'recorded' => lang::get('Field record date'),
        'input' => lang::get('Input date'),
        'edited' => lang::get('Last changed date'),
        'verified' => lang::get('Verification status change date'),
      )
    ));
    $r .= '</fieldset>';
    $r .= '<fieldset class="exclusive"><legend>' . lang::get('Specify a date range for the records to include') . '</legend>';
    $r .= data_entry_helper::date_picker(array(
      'label' => lang::get('Records from'),
      'fieldname' => 'date_from',
      'allowFuture' => TRUE,
    ));
    $r .= data_entry_helper::date_picker(array(
      'label' => lang::get('Records to'),
      'fieldname' => 'date_to',
      'allowFuture' => TRUE,
    ));
    $r .= '</fieldset>';
    $r .= '<fieldset class="exclusive" id="age"><legend>' . lang::get('Or, specify a maximum age for the records to include') . '</legend>';
    $r .= data_entry_helper::text_input(array(
      'label' => lang::get('Max. record age'),
      'helpText' => lang::get('How old records can be before they are dropped from the report? ' .
          'Enter a number followed by the unit (days, weeks, months or years), e.g. "2 days" or "1 year".'),
      'fieldname' => 'date_age',
      'validation' => array('regex[/^[0-9]+\s*(day|week|month|year)(s)?$/]')
    ));
    $r .= '</fieldset>';
    return $r;
  }

}

/**
 * Class defining a "where" filter - geographic selection.
 */
class filter_where extends FilterBase {

  public function getTitle() {
    return lang::get('Where');
  }

  /**
   * Define the HTML required for this filter's UI panel.
   * Options available:
   * * **personSiteAttrId** - a multi-value location attribute used to link users to their recording sites.
   * * **includeSitesCreatedByUser** - boolean which defines if sites that the user is the creator of are available. Default TRUE.
   * * **indexedLocationTypeIds** - array of location type IDs for types that are available and which are indexed in the spatial index builder
   * * **otherLocationTypeIds** - array of location type IDs for types that are available and which are indexed in the
   *
   * @param array $readAuth
   *   Read authorisation tokens
   *
   * @param array $options
   *   Control options array. Options include:
   *   * includeSitesCreatedByUser - Defines if user created sites are available for selection. True or false
   *   * indexedLocationTypeIds - array of location type IDs for sites which can be selected using the index
   *     system for filtering.
   *   * otherLocationTypeIds - as above, but does not use the spatial index builder. Should only be used for
   *     smaller or less complex site boundaries.
   *
   * @return string
   *   Controls HTML
   *
   * @throws \exception
   */
  public function get_controls($readAuth, $options) {
    if (function_exists('iform_load_helpers')) {
      iform_load_helpers(array('map_helper'));
    }
    else {
      // When running on warehouse we don't have iform_load_helpers.
      require_once DOCROOT . 'client_helpers/map_helper.php';
    }
    $options = array_merge(array(
      'includeSitesCreatedByUser' => TRUE,
      'indexedLocationTypeIds' => array(),
      'otherLocationTypeIds' => array()
    ), $options);
    data_entry_helper::$javascript .= "indiciaData.includeSitesCreatedByUser=" . ($options['includeSitesCreatedByUser'] ? 'true' : 'false') . ";\n";
    data_entry_helper::$javascript .= "indiciaData.personSiteAttrId=" . (empty($options['personSiteAttrId']) ? 'false' : $options['personSiteAttrId']) . ";\n";
    $r = '<fieldset class="inline"><legend>' . lang::get('Filter by site or place') . '</legend>';
    $r .= '<p>' . lang::get('Choose from the following place filtering options.') . '</p>' .
        '<div class="context-instruct messages warning">' . lang::get('Please note that your access permissions are limiting the areas you are able to include.') . '</div>';
    $r .= '<fieldset class="exclusive">';
    // Top level of sites selection.
    $sitesLevel1 = array();
    $this->addProfileLocation($readAuth, 'location', $sitesLevel1);
    $this->addProfileLocation($readAuth, 'location_expertise', $sitesLevel1);
    $this->addProfileLocation($readAuth, 'location_collation', $sitesLevel1);
    if (!empty($options['personSiteAttrId']) || $options['includeSitesCreatedByUser']) {
      $sitesLevel1['my'] = lang::get('My sites') . '...';
    }
    // The JS needs to know which location types are indexed so it can build the correct filter.
    data_entry_helper::$javascript .= "indiciaData.indexedLocationTypeIds=" . json_encode($options['indexedLocationTypeIds']) . ";\n";
    $locTypes = array_merge($options['indexedLocationTypeIds'], $options['otherLocationTypeIds']);
    $locTypes = data_entry_helper::get_population_data(array(
      'table' => 'termlists_term',
      'extraParams' => $readAuth + array('view' => 'cache', 'query' => json_encode(array('in' => array('id' => $locTypes))))
    ));
    foreach ($locTypes as $locType) {
      $sitesLevel1[$locType['id']] = $locType['term'] . '...';
    }
    $r .= '<div id="ctrl-wrap-location_list" class="form-row ctrl-wrap">';
    $r .= data_entry_helper::select(array(
      'fieldname' => 'site-type',
      'label' => lang::get('Choose an existing site or location'),
      'lookupValues' => $sitesLevel1,
      'blankText' => '<' . lang::get('Please select') . '>',
      'controlWrapTemplate' => 'justControl'
    ));
    $r .= data_entry_helper::sub_list(array(
      'fieldname' => 'location_list',
      'controlWrapTemplate' => 'justControl',
      'table' => 'location',
      'captionField' => 'name',
      'valueField' => 'id',
      'addToTable' => FALSE,
      'extraParams' => $readAuth
    ));

    $r .= '</div></fieldset>';
    $r .= '<br/><fieldset class="exclusive">';
    $r .= data_entry_helper::text_input(array(
      'label' => lang::get('Or, search for site names containing'),
      'fieldname' => 'location_name'
    ));
    $r .= '</fieldset>';
    $r .= '<fieldset class="exclusive">';
    // Build the array of spatial reference systems into a format Indicia can use.
    $systems = array();
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
    $r .= data_entry_helper::sref_and_system(array(
      'label' => lang::get('Or, find records in map reference'),
      'fieldname' => 'sref',
      'systems' => $systems
    ));
    $r .= '</fieldset></fieldset>';
    $r .= '<fieldset><legend>' . lang::get('Or, select a drawing tool in the map toolbar then draw a boundary to find intersecting records') . '</legend>';
    if (empty($options['linkToMapDiv'])) {
      if (function_exists('hostsite_get_config_value')) {
        $initialLat = hostsite_get_config_value('iform', 'map_centroid_lat', 55);
        $initialLong = hostsite_get_config_value('iform', 'map_centroid_long', -1);
        $initialZoom = (int) hostsite_get_config_value('iform', 'map_zoom', 5);
      } else {
        $initialLat = 55;
        $initialLong = -1;
        $initialZoom = 5;
      }
      // Need our own map on the popup.
      // The js wrapper around the map div does not help here, since it breaks fancybox and fancybox is js only anyway.
      global $indicia_templates;
      $oldwrap = $indicia_templates['jsWrap'];
      $indicia_templates['jsWrap'] = '{content}';
      $r .= map_helper::map_panel(array(
        'divId' => 'filter-pane-map',
        'presetLayers' => array('osm'),
        'editLayer' => TRUE,
        'initial_lat' => $initialLat,
        'initial_long' => $initialLong,
        'initial_zoom' => $initialZoom,
        'width' => '100%',
        'height' => 400,
        'standardControls' => array('layerSwitcher', 'panZoomBar', 'drawPolygon', 'drawLine', 'drawPoint',
          'modifyFeature', 'clearEditLayer'),
        'readAuth' => $readAuth,
        'gridRefHint' => TRUE
      ));
      $indicia_templates['jsWrap'] = $oldwrap;
    }
    else {
      // We are going to use an existing map for drawing boundaries etc. So prepare a container.
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
        $loc = data_entry_helper::get_population_data(array(
          'table' => 'location',
          'extraParams' => $readAuth + array('id' => $locality)
        ));
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

  public function getTitle() {
    return lang::get('Who');
  }

  /**
   * Define the HTML required for this filter's UI panel.
   */
  public function get_controls() {
    $r = '<div class="context-instruct messages warning">' . lang::get('Please note, you cannnot change this setting because of your access permissions in this context.') . '</div>';
    $r .= data_entry_helper::checkbox(array(
      'label' => lang::get('Only include my records'),
      'fieldname' => 'my_records'
    ));
    return $r;
  }

}

/**
 * Class defining a "id" filter - record selection by known id.
 */
class filter_occ_id extends FilterBase {

  public function getTitle() {
    return lang::get('Record ID');
  }

  /**
   * Define the HTML required for this filter's UI panel.
   */
  public function get_controls() {
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

  public function getTitle() {
    return lang::get('Sample ID');
  }

  /**
   * Define the HTML required for this filter's UI panel.
   */
  public function get_controls() {
    $r = '<div id="ctrl-wrap-smp_id" class="form-row ctrl-wrap">';
    $r .= data_entry_helper::select(array(
      'label' => lang::get('Sample ID'),
      'fieldname' => 'smp_id_op',
      'lookupValues' => array(
        '=' => lang::get('is'),
        '>=' => lang::get('is at least'),
        '<=' => lang::get('is at most'),
      ),
      'controlWrapTemplate' => 'justControl',
    ));
    $r .= data_entry_helper::text_input(array(
      'fieldname' => 'smp_id',
      'class' => 'control-width-2',
      'controlWrapTemplate' => 'justControl',
    ));
    $r .= '</div>';
    return $r;
  }

}

/**
 * Class defining a "quality" filter - record status, photos, verification rule check selection.
 */
class filter_quality extends FilterBase {

  public function getTitle() {
    return lang::get('Quality');
  }

  /**
   * Define the HTML required for this filter's UI panel.
   */
  public function get_controls() {
    $r = '<div class="context-instruct messages warning">' . lang::get('Please note, your options for quality filtering are restricted by your access permissions in this context.') . '</div>';
    $r .= data_entry_helper::select(array(
      'label' => lang::get('Records to include'),
      'fieldname' => 'quality',
      'id' => 'quality-filter',
      'lookupValues' => array(
        'V1' => lang::get('Accepted as correct records only'),
        'V' => lang::get('Accepted records only'),
        '-3' => lang::get('Reviewer agreed at least plausible'),
        'C3' => lang::get('Plausible records only'),
        'C' => lang::get('Recorder was certain'),
        'L' => lang::get('Recorder thought the record was at least likely'),
        'P' => lang::get('Not reviewed'),
        'T' => lang::get('Not reviewed but trusted recorder'),
        '!R' => lang::get('Exclude not accepted records'),
        '!D' => lang::get('Exclude queried or not accepted records'),
        'all' => lang::get('All records'),
        'D' => lang::get('Queried records only'),
        'A' => lang::get('Answered records only'),
        'R' => lang::get('Not accepted records only'),
        'R4' => lang::get('Not accepted as reviewer unable to verify records only'),
        'DR' => lang::get('Queried or not accepted records'),
      )
    ));
    $r .= data_entry_helper::select(array(
      'label' => lang::get('Automated checks'),
      'fieldname' => 'autochecks',
      'lookupValues' => array(
        '' => lang::get('Not filtered'),
        'P' => lang::get('Only include records that pass all automated checks'),
        'F' => lang::get('Only include records that fail at least one automated check'),
      )
    ));
    $r .= data_entry_helper::select(array(
      'label' => lang::get('Identification difficulty'),
      'fieldname' => 'identification_difficulty_op',
      'lookupValues' => array(
        '=' => lang::get('is'),
        '>=' => lang::get('is at least'),
        '<=' => lang::get('is at most'),
      ),
      'afterControl' => data_entry_helper::select(array(
        'fieldname' => 'identification_difficulty',
        'lookupValues' => array(
          '' => lang::get('Not filtered'),
          1 => 1,
          2 => 2,
          3 => 3,
          4 => 4,
          5 => 5,
        ),
        'controlWrapTemplate' => 'justControl',
      ))
    ));
    $r .= data_entry_helper::select([
      'label' => 'Photos',
      'fieldname' => 'has_photos',
      'lookupValues' => [
        '' => 'Include all records',
        '1' => 'Only include records which have photos',
        '0' => 'Exclude records which have photos',
      ],
    ]);
    return $r;
  }

}

/**
 * Class defining a "quality" filter for samples - based on record status.
 */
class filter_quality_sample extends FilterBase {

  public function getTitle() {
    return lang::get('Quality');
  }

  /**
   * Define the HTML required for this filter's UI panel.
   */
  public function get_controls() {
    $r = '<div class="context-instruct messages warning">' . lang::get('Please note, your options for quality filtering are restricted by your access permissions in this context.') . '</div>';
    $r .= data_entry_helper::select(array(
      'label' => lang::get('Samples to include'),
      'fieldname' => 'quality',
      'id' => 'quality-filter',
      'lookupValues' => array(
        'V' => lang::get('Accepted records only'),
        'P' => lang::get('Not reviewed'),
        '!R' => lang::get('Exclude not accepted records'),
        '!D' => lang::get('Exclude queried or not accepted records'),
        'all' => lang::get('All records'),
        'R' => lang::get('Not accepted records only'),
      )
    ));
    $r .= data_entry_helper::select(array(
      'label' => lang::get('Automated checks'),
      'fieldname' => 'autochecks',
      'lookupValues' => array(
        '' => lang::get('Not filtered'),
        'P' => lang::get('Only include records that pass all automated checks'),
        'F' => lang::get('Only include records that fail at least one automated check'),
      ),
    ));
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

  public function getTitle() {
    return lang::get('Source');
  }

  /**
   * Define the HTML required for this filter's UI panel.
   */
  public function get_controls($readAuth, $options) {
    if (function_exists('iform_load_helpers')) {
      iform_load_helpers(array('report_helper'));
    }
    else {
      // When running on warehouse we don't have iform_load_helpers.
      require_once DOCROOT . 'client_helpers/report_helper.php';
    }
    $r = '<div class="context-instruct messages warning">' . lang::get('Please note, or options for source filtering are limited by your access permissions in this context.') . '</div>';
    $r .= '<div>';
    // If running on the warehouse then use a single website (as the user is on the website details->milestones tab for
    // a single website) so don't diplay website selection (which is based on website sharing).
    if (!isset($options['runningOnWarehouse']) || $options['runningOnWarehouse'] == FALSE) {
      $sources = report_helper::get_report_data(array(
        'dataSource' => 'library/websites/websites_list',
        'readAuth' => $readAuth,
        'caching' => TRUE,
        'cachePerUser' => FALSE,
        'extraParams' => array('sharing' => $options['sharing'] === 'me' ? 'reporting' : $options['sharing']),
      ));
      if (count($sources) > 1) {
        $r .= '<div id="filter-websites" class="filter-popup-columns"><h3>' . lang::get('Websites') . '</h3><p>' .
            '<select id="filter-websites-mode" name="website_list_op"><option value="in">' . lang::get('Include') . '</option><option value="not in">'.lang::get('Exclude').'</option></select> '.
            lang::get('records from') . ':</p><ul id="website-list-checklist">';
        foreach ($sources as $source) {
          $r .= '<li><input type="checkbox" value="' . $source['id'] . '" id="check-website-' . $source['id'] . '"/>' .
              '<label for="check-website-' . $source['id'] . '">' . $source['title'] . '</label></li>';
        }
        $r .= '</ul></div>';
      }
    }
    // If running on the warehouse then use a single website id (as the user is on the website details->milestones tab
    // for a single website) so supply this as a param, we don't need to worry about sharing as other websites will
    // have their own milestones.
    if (isset($options['runningOnWarehouse']) && $options['runningOnWarehouse'] == TRUE) {
      $sources = data_entry_helper::get_population_data(array(
        'table' => 'survey',
        'extraParams' => $readAuth + array('view' => 'detail', 'website_id' => $options['website_id']),
      ));
      $titleToDisplay = 'title';
    }
    else {
      $sources = report_helper::get_report_data(array(
        'dataSource' => 'library/surveys/surveys_list',
        'readAuth' => $readAuth,
        'caching' => TRUE,
        'cachePerUser' => FALSE,
        'extraParams' => array('sharing' => $options['sharing'] === 'me' ? 'reporting' : $options['sharing']),
      ));
      $titleToDisplay = 'fulltitle';
    }
    $r .= '<div id="filter-surveys" class="filter-popup-columns"><h3>' . lang::get('Survey datasets') . '</h3><p>' .
          '<select id="filter-surveys-mode" name="survey_list_op"><option value="in">' . lang::get('Include') . '</option><option value="not in">'.lang::get('Exclude').'</option></select> '.
          lang::get('records from') . ':</p><ul id="survey-list-checklist">';
    foreach ($sources as $source) {
      $r .= '<li class="vis-website-' . $source['website_id'] . '">' .
          '<input type="checkbox" value="' . $source['id'] . '" id="check-survey-' . $source['id'] . '"/>' .
          '<label for="check-survey-' . $source['id'] . '">' . $source[$titleToDisplay] . '</label></li>';
    }
    $r .= '</ul></div>';
    $sourceOptions = array(
      'dataSource' => 'library/input_forms/input_forms_list',
      'readAuth' => $readAuth,
      'caching' => TRUE,
      'cachePerUser' => FALSE,
      'extraParams' => array('sharing' => $options['sharing'] === 'me' ? 'reporting' : $options['sharing']),
    );
    // If in the warehouse then we are only interested in the website for the milestone we are editing.
    if (isset($options['website_id'])) {
      $sourceOptions['extraParams'] = array_merge(array('website_id' => $options['website_id']), $sourceOptions['extraParams']);
    }
    $sources = report_helper::get_report_data($sourceOptions);
    $r .= '<div id="filter-input_forms" class="filter-popup-columns"><h3>' . lang::get('Input forms') . '</h3><p>' .
          '<select id="filter-input_forms-mode" name="input_forms_list_op"><option value="in">' . lang::get('Include') . '</option><option value="not in">'.lang::get('Exclude').'</option></select> '.
          lang::get('records from') . ':</p><ul id="input_form-list-checklist">';
    // Create an object to contain a lookup from id to form for JS, since forms don't have a real id.
    $obj = array();
    foreach ($sources as $idx => $source) {
      if (!empty($source['input_form'])) {
        $r .= '<li class="vis-survey-' . $source['survey_id'] . ' vis-website-' . $source['website_id'] . '">' .
            '<input type="checkbox" value="' . $source['input_form'] . '" id="check-form-' . $idx . '"/>' .
            '<label for="check-form-' . $idx . '">' . ucfirst(trim(preg_replace('/(http(s)?:\/\/)|[\/\-_]|(\?q=)/', ' ', $source['input_form']))).'</label></li>';
        $obj[$source['input_form']] = $idx;
      }
    }
    $r .= '</ul></div>';
    report_helper::$javascript .= 'indiciaData.formsList=' . json_encode($obj) . ";\n";
    $r .= '</div><p>' . lang::get('Leave any list unticked to leave that list unfiltered.') . '</p>';
    return $r;
  }

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
 *     defines_permissions=TRUE in the
 *   * filters table. Set to "default" to select their profile verification
 *     settings when sharing=verification.
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
 *     drop down. Choose from my-records, my-groups (uses your list of taxon
 *     groups in the user account), my-locality (uses your recording locality
 *     from the user  account), my-groups-locality (uses taxon groups and
 *     recording locality from the user account), my-queried-records,
 *     queried-records, answered-records, accepted-records,
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
    iform_load_helpers(array('report_helper'));
  }
  else {
    //When running on warehouse we don't have iform_load_helpers
    require_once DOCROOT . 'client_helpers/report_helper.php';
  }
  if (!empty($_POST['filter:sharing'])) {
    $options['sharing'] = $_POST['filter:sharing'];
  }
  $options = array_merge(array(
    'sharing' => 'reporting',
    'admin' => FALSE,
    'adminCanSetSharingTo' => array('R' => lang::get('reporting'), 'V' => lang::get('verification')),
    'allowLoad' => TRUE,
    'allowSave' => TRUE,
    'redirect_on_success' => '',
    'presets' => array(
      'my-records',
      'my-queried-records',
      'my-queried-or-not-accepted-records',
      'my-not-reviewed-records',
      'my-accepted-records',
      'my-groups',
      'my-locality',
      'my-groups-locality',
    ),
    'entity' => 'occurrence',
  ), $options);
  // Introduce some extra quick filters useful for verifiers.
  if ($options['sharing'] === 'verification') {
    $options['presets'] = array_merge(array(
      'queried-records',
      'answered-records',
      'accepted-records',
      'not-accepted-records',
    ), $options['presets']);
  }
  if ($options['entity'] === 'sample') {
    unset($options['presets']['my-groups']);
    unset($options['presets']['my-groups-locality']);
  }
  // If in the warehouse we don't need to worry about the iform master list.
  if (function_exists('hostsite_get_config_value')) {
    $options = array_merge(
      array('taxon_list_id' => hostsite_get_config_value('iform', 'master_checklist_id', 0)),
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
  if (function_exists('hostsite_add_library')) {
    hostsite_add_library('collapse');
  }
  $filterData = report_filters_load_existing($readAuth, $options['sharingCode']);
  $existing = '';
  $contexts = '';
  $contextDefs = array();
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
  // If in the warehouse we don't need to worry about user specific preferences when setting up milestones.
  if (function_exists('hostsite_get_user_field')) {
    foreach ($options['presets'] as $preset) {
      $title = FALSE;
      switch ($preset) {
        case 'my-records':
          if (hostsite_get_user_field('id')) {
            $title = lang::get('My records');
          }
          break;

        case 'my-queried-records':
          if (hostsite_get_user_field('id')) {
            $title = lang::get('My queried records');
          }
          break;

        case 'my-queried-or-not-accepted-records':
          if (hostsite_get_user_field('id')) {
            $title = lang::get('My not accepted or queried records');
          }
          break;

        case 'my-not-reviewed-records':
          if (hostsite_get_user_field('id')) {
            $title = lang::get('My not reviewed records');
          }
          break;

        case 'my-accepted-records':
          if (hostsite_get_user_field('id')) {
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
        $presetFilter = array(
          'id' => $preset,
          'title' => $title,
          'defines_permissions' => 'f'
        );
        $filterData[] = $presetFilter;
      }
    }
    if (count($options['presets'])) {
      if ($groups = hostsite_get_user_field('taxon_groups', FALSE, TRUE))
        data_entry_helper::$javascript .= "indiciaData.userPrefsTaxonGroups='" . implode(',', $groups) . "';\n";
      if ($location = hostsite_get_user_field('location'))
        data_entry_helper::$javascript .= "indiciaData.userPrefsLocation=" . $location . ";\n";
    }
    if ($options['sharing'] === 'verification') {
      // Apply legacy verification settings from their profile.
      $location_id = hostsite_get_user_field('location_expertise');
      $taxon_group_ids = hostsite_get_user_field('taxon_groups_expertise', FALSE, TRUE);
      $survey_ids = hostsite_get_user_field('surveys_expertise', FALSE, TRUE);
      if ($location_id || $taxon_group_ids || $survey_ids) {
        $selected = (!empty($options['context_id']) && $options['context_id']==='default') ? 'selected="selected" ' : '';
        $contexts .= "<option value=\"default\" $selected>".lang::get('My verification records')."</option>";
        $def = array();
        if ($location_id) {
          // User profile geographic limits should always be based on an
          // indexed location.
          $def['indexed_location_list'] = $location_id;
        }
        if ($taxon_group_ids) {
          $def['taxon_group_list'] = implode(',', $taxon_group_ids);
          $def['taxon_group_names'] = array();
          $groups = data_entry_helper::get_population_data(array(
            'table' => 'taxon_group',
            'extraParams' => $readAuth + array('id' => $taxon_group_ids)
          ));
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
      $selected = (!empty($options['filter_id']) && $options['filter_id']==$filter['id']) ? 'selected="selected" ' : '';
      $existing .= "<option value=\"$filter[id]\" $selected>$filter[title]</option>";
      if ($selected) {
        // Ensure the initially selected filter gets applied across all reports on the page.
        report_helper::$initialFilterParamsToApply = array_merge(
          report_helper::$initialFilterParamsToApply, json_decode($filter['definition'], TRUE)
        );
      }
    }
  }
  $r = '<div id="standard-params" class="ui-widget">';
  if ($options['allowSave'] && $options['admin']) {
    if (empty($_GET['filters_user_id'])) {
      // New filter to create, so sharing type can be edited.
      $reload = data_entry_helper::get_reload_link_parts();
      $reloadPath = $reload['path'];
      if (count($reload['params'])) {
        $reloadPath .= '?' . data_entry_helper::array_to_query_string($reload['params']);
      }
      $r .= "<form action=\"$reloadPath\" method=\"post\" >";
      $r .= data_entry_helper::select(array(
        'label' => lang::get('Select filter type'),
        'fieldname' => 'filter:sharing',
        'lookupValues' => $options['adminCanSetSharingTo'],
        'afterControl' => '<input type="submit" value="Go"/>',
        'default' => $options['sharingCode'],
      ));
      $r .= '</form>';
    }
    else {
      // Existing filter to edit, type is therefore fixed. JS will fill these values in.
      $r .= '<p>' . lang::get('This filter is for <span id="sharing-type-label"></span>.') . '</p>';
      $r .= data_entry_helper::hidden_text(array('fieldname' => 'filter:sharing'));
    }
  }
  if ($options['allowLoad']) {
    $r .= '<div class="header ui-toolbar ui-widget-header ui-helper-clearfix"><div><span id="active-filter-label">'.
        '</span></div><span class="changed" style="display:none" title="' .
        lang::get('This filter has been changed') . '">*</span>';
    $r .= '<div>';
    if ($customDefs) {
      data_entry_helper::$javascript .= "indiciaData.filterCustomDefs = " . json_encode($customDefs) . ";\n";
    }
    if ($contexts) {
      data_entry_helper::$javascript .= "indiciaData.filterContextDefs = " . json_encode($contextDefs) . ";\n";
      if (count($contextDefs) > 1) {
        $r .= '<label for="context-filter">' . lang::get('Context:') . "</label><select id=\"context-filter\">$contexts</select>";
      }
      else {
        $keys = array_keys($contextDefs);
        $r .= '<input type="hidden" id="context-filter" value="' . $keys[0] . '" />';
      }
      // Ensure the initially selected context filter gets applied across all reports on the page. Tag _context to the
      // end of param names so the filter system knows they are not user changeable.
      $contextFilterOrig = empty($options['context_id']) ?
          array_values($contextDefs)[0] : $contextDefs[$options['context_id']];
      $contextFilter = array();
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
    $r .= '<label for="select-filter">' . lang::get('Filter:') . '</label><select id="select-filter"><option value="" selected="selected">' .
        lang::get('Select filter') . "...</option>$existing</select>";
    global $indicia_templates;
    $r .= helper_base::apply_static_template('button', [
      'id' => 'filter-apply',
      'title' => lang::get('Apply filter'),
      'class' => ' class="' . $indicia_templates['buttonDefaultClass'] . '"',
      'caption' => lang::get('Apply'),
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
  $filters = array();
  if (isset($options['generateFilterListCallback'])) {
    $filters = call_user_func($options['generateFilterListCallback'], $options['entity']);
  }
  elseif ($options['entity'] === 'occurrence') {
    $filters = array(
      'filter_what' => new filter_what(),
      'filter_where' => new filter_where(),
      'filter_when' => new filter_when(),
      'filter_who' => new filter_who(),
      'filter_occ_id' => new filter_occ_id(),
      'filter_quality' => new filter_quality(),
      'filter_source' => new filter_source(),
    );
  }
  elseif ($options['entity'] === 'sample') {
    $filters = array(
      'filter_where' => new filter_where(),
      'filter_when' => new filter_when(),
      'filter_who' => new filter_who(),
      'filter_smp_id' => new filter_smp_id(),
      'filter_quality' => new filter_quality_sample(),
      'filter_source' => new filter_source(),
    );
  }
  if (!empty($options['filterTypes'])) {
    $filterModules = array();
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
    $filterModules = array('' => $filters);
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
    foreach ($list as $moduleName => $module) {
      $r .= "<div class=\"pane\" id=\"pane-$moduleName\"><a class=\"fb-filter-link\" href=\"#controls-$moduleName\"><span class=\"pane-title\">" . $module->getTitle() . '</span>';
      $r .= '<span class="filter-desc"></span></a>';
      $r .= "</div>";
    }
    if ($category) {
      $r .= '</div></fieldset>';
    }
  }
  $r .= '</div>'; // filter panes
  $r .= '<div class="toolbar">';
  if ($options['allowSave']) {
    $r .= '<label for="filter:title">' . lang::get('Save filter as') . ':</label> <input id="filter:title" class="control-width-5"/>';
    if ($options['admin']) {
      $r .= '<br/>';
      if (empty($options['adminCanSetSharingTo'])) {
        throw new exception('Report standard params panel in admin mode so adminCanSetSharingTo option must be populated.');
      }
      $r .= data_entry_helper::autocomplete(array(
        'label' => lang::get('For who?'),
        'fieldname' => 'filters_user:user_id',
        'table' => 'user',
        'valueField' => 'id',
        'captionField' => 'person_name',
        'formatFunction' => "function(item) { return item.person_name + ' (' + item.email_address + ')'; }",
        'extraParams' => $readAuth + array('view' => 'detail'),
        'class' => 'control-width-5',
      ));
      $r .= data_entry_helper::textarea(array(
        'label' => lang::get('Description'),
        'fieldname' => 'filter:description',
      ));
    }
    $r .= '<img src="' . data_entry_helper::$images_path . 'nuvola/save-22px.png" width="22" height="22" alt="Save filter" title="Save filter" class="button" id="filter-save"/>';
    $r .= '<img src="' . data_entry_helper::$images_path . 'trash-22px.png" width="22" height="22" alt="Bin this filter" title="Bin this filter" class="button disabled" id="filter-delete"/>';
  }
  $r .= '</div></div>'; // toolbar + clearfix
  if (!empty($options['filters_user_id'])) {
    // If we are preloading based on a filter user ID, we need to get the
    // information now so that the sharing mode can be known when loading
    // controls.
    $fu = data_entry_helper::get_population_data(array(
        'table' => 'filters_user',
        'extraParams' => $readAuth + array('id' => $options['filters_user_id']),
        'caching' => FALSE,
    ));
    if (count($fu) !== 1) {
      throw new exception('Could not find filter user record');
    }
    $options['sharing'] = report_filters_sharing_code_to_full_term($fu[0]['filter_sharing']);
  }
  report_helper::$javascript .= "indiciaData.lang={pleaseSelect:\"" . lang::get('Please select') . "\"};\n";
  // Create the hidden panels required to populate the popups for setting each type of filter up.
  $hiddenStuff = '';
  foreach ($filterModules as $category => $list) {
    foreach ($list as $moduleName => $module) {
      $hiddenStuff .= "<div style=\"display: none\"><div class=\"filter-popup\" id=\"controls-$moduleName\"><form action=\"#\" class=\"filter-controls\"><fieldset>" . $module->get_controls($readAuth, $options) .
        '<button class="fb-close" type="button">' . lang::get('Cancel') . '</button>' .
        '<button class="fb-apply" type="submit">' . lang::get('Apply') . '</button></fieldset></form></div></div>';
      $shortName = str_replace('filter_', '', $moduleName);
      report_helper::$javascript .= "indiciaData.lang.NoDescription$shortName='" . lang::get('Click to Filter ' . ucfirst($shortName)) . "';\n";
    }

  }
  $r .= '</div>';
  report_helper::addLanguageStringsToJs('reportFilters', [
    'CreateAFilter' => 'Create a filter',
    'ModifyFilter' => 'Modify filter',
    'FilterSaved' => 'The filter has been saved',
    'FilterDeleted' => 'The filter has been deleted',
    'ConfirmFilterChangedLoad' => 'Do you want to load the selected filter and lose your current changes?',
    'FilterExistsOverwrite' => 'A filter with that name already exists. Would you like to overwrite it?',
    'AutochecksFailed' => 'Automated checks failed',
    'AutochecksPassed' => 'Automated checks passed',
    'IdentificationDifficulty' => 'Identification difficulty',
    'HasPhotos' => 'Only include records which have photos',
    'HasNoPhotos' => 'Exclude records which have photos',
    'ConfirmFilterDelete' => 'Are you sure you want to permanently delete the {title} filter?',
    'MyRecords' => 'My records only',
    'OnlyConfidentialRecords' => 'Only confidential records',
    'AllConfidentialRecords' => 'Include both confidential and non-confidential records',
    'NoConfidentialRecords' => 'Exclude confidential records',
    'includeUnreleasedRecords' => 'Include unreleased records',
    'excludeUnreleasedRecords' => 'Exclude unreleased records',
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
  $getParams = array();
  $optionParams = array();
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
  $allParams = array_merge(['quality' => '!R'], $optionParams, $getParams);
  if (!empty($allParams)) {
    report_helper::$initialFilterParamsToApply = array_merge(report_helper::$initialFilterParamsToApply, $allParams);
    $json = json_encode($allParams);
    report_helper::$onload_javascript .= "var params = $json;\n";
    report_helper::$onload_javascript .= "indiciaData.filter.def=$.extend(indiciaData.filter.def, params);\n";
    report_helper::$onload_javascript .= "indiciaData.filter.resetParams = $.extend({}, params);\n";
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
  indiciaFns.applyFilterToReports(false);
}

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
 * $param bool $caching
 *   Pass TRUE to enable caching for faster responses.
 */
function report_filters_load_existing($readAuth, $sharing, $caching = FALSE) {
  if (function_exists('hostsite_get_user_field')) {
    $userId = hostsite_get_user_field('indicia_user_id');
  }
  else {
    $userId = $_SESSION['auth_user']->id;
  }
  if (function_exists('iform_load_helpers')) {
    iform_load_helpers(array('report_helper'));
  }
  else {
    // When running on warehouse we don't have iform_load_helpers.
    require_once DOCROOT . 'client_helpers/report_helper.php';
  }
  $filters = report_helper::get_report_data(array(
    'dataSource' => 'library/filters/filters_list_minimal',
    'readAuth' => $readAuth,
    'caching' => $caching,
    'extraParams' => array(
      'filter_sharing_mode' => $sharing === 'M' ? 'R' : $sharing,
      'defines_permissions' => '',
      'filter_user_id' => $userId,
    ),
  ));
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
