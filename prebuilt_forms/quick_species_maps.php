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
 * @package Client
 * @subpackage PrebuiltForms
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link http://code.google.com/p/indicia/
 */

require_once 'includes/report.php';
require_once 'includes/map.php';

/**
 * A list of species, with simple distribution mapping capability.
 *
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_quick_species_maps {
  /**
   * Return the form metadata.
   *
   * Note the title of this method includes the name of the form file. This
   * ensures that if inheritance is used in the forms, subclassed forms don't
   * return their parent's form definition.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_quick_species_maps_definition() {
    return array(
      'title' => 'Quick Species Maps',
      'category' => 'Reporting',
      'description' => 'A list of species that can quickly be added to a distribution map.',
      'helpLink' => 'https://indicia-docs.readthedocs.org/en/latest/site-building/iform/prebuilt-forms/quick-species-maps.html',
      'recommended' => TRUE,
    );
  }

  /**
   * Get the list of parameters for this form.
   *
   * @return array
   *   List of parameters that this form requires.
   */
  public static function get_parameters() {
    $r = array_merge(
      iform_report_get_report_parameters(),
      iform_map_get_map_parameters(),
      array(
        array(
          'name' => 'indicia_species_layer_feature_type',
          'caption' => 'Feature type for Indicia species layer',
          'description' => 'Set to the name of a feature type on GeoServer that will be loaded to display the ' .
              'Indicia species data for the selected record. Leave empty for no layer. Normally this should be set ' .
              'to a feature type that exposes the cache_occurrences table.',
          'type' => 'text_input',
          'required' => TRUE,
          'default' => 'indicia:cache_occurrences',
          'group' => 'Other Map Settings',
        ), array(
          'name' => 'indicia_species_layer_filter_field',
          'caption' => 'Field to filter on',
          'description' => 'Set to the name of a field exposed by the feature type which contains the external key ' .
              'defined for the species and can therefore be used to filter the layer.',
          'type' => 'text_input',
          'required' => TRUE,
          'default' => 'taxa_taxon_list_external_key',
          'group' => 'Other Map Settings',
        ), array(
          'name' => 'indicia_species_layer_slds',
          'caption' => 'SLD files from GeoServer for Indicia species layer',
          'description' => 'Set to the names of SLD files available on the GeoServer for the rendering of the ' .
              'Indicia species layer, or leave blank for default. Provide one per species layer you are going ' .
              'to allow on the map. Layer styles will be cycled through.',
          'type' => 'textarea',
          'required' => FALSE,
          'default' => "dist_point_red\ndist_point_blue",
          'group' => 'Other Map Settings',
        ), array(
          'name' => 'indicia_species_layer_extra_cql',
          'caption' => 'Additional CQL filters for Indicia species layer',
          'description' => 'There should be consistency between the report filters and the map filters. ' .
              'Enter any additional filters for the map on separate lines e.g. survey_id in (142,143,511).',
          'type' => 'textarea',
          'required' => FALSE,
          'default' => "",
          'group' => 'Other Map Settings',
        ), array(
          'name' => 'species_details_page_path',
          'caption' => 'Species details page path',
          'description' => 'Path to the species details page, if any. Adds a link to the page to the table.',
          'type' => 'text_input',
        ),
      )
    );
    foreach ($r as &$param) {
      if ($param['name'] === 'report_name') {
        $param['default'] = 'library/taxa/occurrence_counts_summary_by_external_key';
      }
      elseif ($param['name'] === 'indicia_species_layer_filter_field') {
        $param['default'] = 'taxa_taxon_list_external_key';
      }
      elseif ($param['name'] === 'param_presets') {
        $param['default'] = "date_from=\ndate_to=\nsurvey_id=\nquality=C\nlocation_id={profile_location}\ntaxon_groups={profile_taxon_groups}\ncurrentUser={profile_indicia_user_id}";
      }
    }
    return $r;
  }

  /**
   * Return the generated form output.
   *
   * @param array $args
   *   List of parameter values from form configuration.
   *   This array always contains a value for language.
   * @param object $nid
   *   The Drupal node object's ID.
   * @param array $response
   *   Not used in this form.
   *
   * @return string
   *   Form HTML.
   */
  public static function get_form($args, $nid, $response = NULL) {
    iform_load_helpers(array('report_helper', 'map_helper'));
    $conn = iform_get_connection_details($nid);
    $readAuth = report_helper::get_read_auth($conn['website_id'], $conn['password']);
    $r = '<div id="leftcol">';
    $reportOptions = iform_report_get_report_options($args, $readAuth);
    iform_report_apply_explore_user_own_preferences($reportOptions);
    $reportOptions = array_merge(array(
      'rowId' => 'external_key',
      'columns' => array(),
      'callback' => 'grid_load',
      'rememberParamsReportGroup' => 'explore',
      'paramsFormButtonCaption' => lang::get('Filter'),
    ), $reportOptions);
    // Disable own groups filter if nothing in your user profile.
    if (empty(hostsite_get_user_field('taxon_groups', FALSE, TRUE))) {
      $reportOptions['extraParams']['ownGroups'] = 0;
    }
    $reportOptions['rowId'] = 'external_key';
    $imgPath = empty(report_helper::$images_path) ? report_helper::relative_client_helper_path() . "../media/images/" : report_helper::$images_path;
    $actions = array(
      array(
        'img' => "{$imgPath}add.png",
        'caption' => 'Click to add this species to the map',
      ),
    );
    // Add an action column for the species details page.
    if (!empty($args['species_details_page_path'])) {
      $actions[] = array(
        'img' => "{$imgPath}nuvola/find-22px.png",
        'caption' => 'View details of this species',
        'url' => $args['species_details_page_path'],
        'urlParams' => array('taxon_meaning_id' => '{taxon_meaning_id}'),
      );
    }
    $reportOptions['columns'][] = array(
      'actions' => $actions,
    );
    $r .= report_helper::report_grid($reportOptions);
    $r .= '</div>';
    $args['indicia_species_layer_slds'] = report_helper::explode_lines($args['indicia_species_layer_slds']);
    $r .= '<div id="rightcol">';
    $r .= '<div id="layerbox">';

    $r .= '<div style="margin:0.5em 0 0.5em 0"><input type="checkbox" checked id="showInstructions" style="margin-right:1em">'. lang::get('Show instructions');
    $r .= '<input type="checkbox" id="showLegend" style="margin:0 1em 0 3em" checked>'. lang::get('Show legend') . '</div>';
    $r .= '<p id="instruct">' . lang::get('Click on the + buttons in the grid to add species layers to the map. You can add up to {1} layers at a time.',
         count($args['indicia_species_layer_slds']));
    $r .= lang::get('Use the - buttons to permanently remove layers, or untick the box in the legend to temporarily hide them.');

    // $r .= '<p id="instruct">' . lang::get('Click on the + buttons in the grid to add species layers to the map. You can add up to {1} layers at a time.',
    //     count($args['indicia_species_layer_slds']));
    // $r .= '<p id="instruct2" style="display: none">' . lang::get('Use the - buttons to permanently remove layers, or untick the box in the legend to temporarily hide them.');
    $mapOptions = iform_map_get_map_options($args, $readAuth);
    $mapOptions['clickForSpatialRef'] = FALSE;
    $olOptions = iform_map_get_ol_options($args, $readAuth);
    $r .= map_helper::layer_list(array(
      'layerTypes' => array('overlay'),
      'includeSwitchers' => TRUE,
      'includeHiddenLayers' => TRUE,
    ));
    $r .= '</div>';
    $r .= map_helper::map_panel($mapOptions, $olOptions);
    $r .= '</div>';
    $websiteIds = iform_get_allowed_website_ids($readAuth);
    if (!empty($args['indicia_species_layer_feature_type']) && !empty(report_helper::$geoserver_url)) {
      $training = (function_exists('hostsite_get_user_field') && hostsite_get_user_field('training')) ? 't' : 'f';
      $cql = 'website_id IN (' . implode(',', $websiteIds) . ') AND ' . $args['indicia_species_layer_filter_field'] .
          "='{filterValue}' AND record_status NOT IN ('R', 'I', 'T') AND training='$training'";
      if (isset($_POST[$reportOptions['reportGroup'] . '-quality'])) {
        $quality = $_POST[$reportOptions['reportGroup'] . '-quality'];
      }
      else {
        $quality = $reportOptions['extraParams']['quality'];
      }
      // Logic here must match the quality_check function logic on the database.
      switch ($quality) {
        case 'V':
          $cql .= " AND record_status='V'";
          break;

        case 'C':
          $cql .= " AND (record_status='V' OR certainty='C')";
          break;

        case 'L':
          $cql .= " AND (record_status='V' OR ((certainty <> 'U' OR certainty IS NULL) AND record_status <> 'D'))";
          break;

        case '!D':
          $cql .= " AND record_status<>'D'";
          break;

        case '!R':
          // Nothing to add - rejects are always excluded.
      }

      /* Add any further CQL filter options from $args.
      For the map to to correctly represent the species listed in the report
      then both report and map should use the same filters. */
      if (isset($args['indicia_species_layer_extra_cql'])) {
        $extraCql = report_helper::explode_lines($args['indicia_species_layer_extra_cql']);
        foreach ($extraCql as $filter) {
          if (trim($filter) !== '') {
            $cql .= " AND $filter";
          }
        }
      }

      report_helper::$javascript .= "indiciaData.indiciaSpeciesLayer = {\n" .
          '  "title":"' . lang::get('{1}') . "\",\n" .
          '  "myRecords":"' . lang::get('my records') . "\",\n" .
          '  "userId":"' . hostsite_get_user_field('indicia_user_id') . "\",\n" .
          '  "featureType":"' . $args['indicia_species_layer_feature_type'] . "\",\n" .
          '  "wmsUrl":"' . data_entry_helper::$geoserver_url . "wms\",\n" .
          "  \"cqlFilter\":\"$cql\",\n" .
          "  \"filterField\":\"taxon_meaning_id\",\n" .
          '  "slds":' . json_encode($args['indicia_species_layer_slds']) . "\n" .
          "};\n";
    }
    return $r;
  }

}
