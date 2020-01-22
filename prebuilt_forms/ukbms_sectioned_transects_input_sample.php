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
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link  http://code.google.com/p/indicia/
 */

require_once 'includes/map.php';
require_once 'includes/user.php';
require_once 'includes/language_utils.php';
require_once 'includes/form_generation.php';

// HOWTO configure default species view to be selected from user field.
// Home >> Administration >> Configuration >> People >> Account settings, Manage Fields
// Add a new "Vertical Tab" Under "Recording settings", called "Preferences"/group_preferences beneath "Who you are"
// Add a new field "Default species view" (field_default_species_view) under this, type "List (text)"
// Set the allowed list to :
//  branch|The species list associated with the branch the user records in.
//  full|The full taxa list
//  common|Common taxa list
//  mine|Taxa from the list previously recorded by me
//  here|Taxa from the list previously recorded at this site.
// Leave "Required field" and "Display on user registration form" unchecked.
// Help Text : This selects what default species list is displayed when you enter first data for a walk. Common and Branch are only available on the first tab (Butterflies): if you choose one of these options, the other tabs will display the full list. The full list will not be displayed on the final (Others) tab, as this is the full UK list and is very big: in this case, those taxa previously recorded here will be displayed. When viewing data subsequently, only those taxa who have data recorded will initially be displayed. Leaving unselected will default to the standard form configuration setting.
// Default: N/A
// Number of values: 1

// @todo
// add filtering ability to auto complete look up and discriminator between subsidiary grids.
/**
 * A custom function for usort which sorts by the location code of a list of sections.
 */
function ukbms_stis_sectionSort($a, $b)
{
  $aCode = substr($a['code'], 1);
  $bCode = substr($b['code'], 1);
  if ($aCode===$bCode) {
    return 0;
  }
  return ((int)$aCode < (int)$bCode) ? -1 : 1;
}

/**
 *
 *
 * @package Client
 * @subpackage PrebuiltForms
 * A form for data entry of transect data by entering counts of each for sections along the transect.
 */
class iform_ukbms_sectioned_transects_input_sample {

  private static $auth;
  private static $userId;
  private static $sampleID;
  private static $locationID;

  /**
   * Return the form metadata. Note the title of this method includes the name of the form file. This ensures
   * that if inheritance is used in the forms, subclassed forms don't return their parent's form definition.
   * @return array The definition of the form.
   */
  public static function get_ukbms_sectioned_transects_input_sample_definition() {
    return array(
      'title'=>'UKBMS Sectioned Transects Sample Input',
      'category' => 'Sectioned Transects',
      'description'=>'A form for inputting the counts of species observed at each section along a transect. Can be called with site=<id> in the URL to force the '.
          'selection of a fixed site, or sample=<id> to edit an existing sample.'
    );
  }

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    return array_merge(
      iform_map_get_map_parameters(),
      array(
        array(
          'name'=>'survey_id',
          'caption'=>'Survey',
          'description'=>'The survey that data will be posted into.',
          'type'=>'select',
          'table'=>'survey',
          'captionField'=>'title',
          'valueField'=>'id',
          'extraParams' => array('orderby'=>'title'),
          'siteSpecific'=>true
        ),
        array(
          'name'=>'occurrence_attribute_id',
          'caption'=>'Occurrence Attribute',
          'description'=>'The attribute (typically an abundance attribute) that will be presented in the grid for input. Entry of an attribute value will create '.
              ' an occurrence.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'captionField'=>'caption',
          'valueField'=>'id',
          'extraParams' => array('orderby'=>'caption'),
          'required' => true,
          'siteSpecific'=>true,
          'group'=>'Transects Editor Settings'
        ),
        array(
          'name'=>'transect_type_term',
          'caption'=>'Transect Location type term',
          'description'=>'Select the location type for the Transects.',
          'type' => 'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'term',
          'extraParams' => array('termlist_external_key'=>'indicia:location_types','orderby'=>'title'),
          'required' => true,
          'group'=>'Transects Editor Settings'
        ),
        array(
          'name'=>'section_type_term',
          'caption'=>'Section Location type term',
          'description'=>'Select the location type for the Transect Sections.',
          'type' => 'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'term',
          'extraParams' => array('termlist_external_key'=>'indicia:location_types','orderby'=>'title'),
          'required' => true,
          'group'=>'Transects Editor Settings'
        ),
        array(
          'name'=>'transect_sample_method_term',
          'caption'=>'Transect Sample method term',
          'description'=>'Select the sample method used for samples registered on the Transects.',
          'type' => 'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'term',
          'extraParams' => array('termlist_external_key'=>'indicia:sample_methods','orderby'=>'title'),
          'required' => true,
          'group'=>'Transects Editor Settings'
        ),
        array(
          'name'=>'section_sample_method_term',
          'caption'=>'Section Sample method term',
          'description'=>'Select the sample method used for samples registered on the Transect Sections.',
          'type' => 'select',
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>'term',
          'extraParams' => array('termlist_external_key'=>'indicia:sample_methods','orderby'=>'title'),
          'required' => true,
          'group'=>'Transects Editor Settings'
        ),
        array(
          'name' => 'attribute_configuration',
          'caption' => 'Custom configuration for attributes',
          'description' => 'Custom configuration for attributes',
          'type' => 'jsonwidget',
          'required' => false,
          'group'=>'Transects Editor Settings',
          'schema' => '{
  "type":"seq",
  "title":"Attribute Custom Configuration List",
  "sequence":
  [
    {
      "type":"map",
      "title":"Attribute",
      "mapping": {
        "id": {"type":"str","desc":"ID of the Sample Attribute."},
        "required": {
              "type":"map",
              "title":"Required",
              "desc":"Provide overrides as to whether the attribute is set (client side) mandatory in the main page or species grid. This allows this to be controlled separately for the two areas, something not possible through the normal functionality. Required validation must be switched off on the warehouse.",
              "mapping": {
                "main_page": {"type":"bool","title":"Main page"},
                "species_grid": {"type":"bool","title":"Species Grid"}
              }},
        "filter": {
              "type":"map",
              "title":"Presence Filter",
              "desc":"Provides ability to control whether the attribute appears in the Species grids, dependant on value(s) of an attribute on the Site.",
              "mapping": {
                "id": {"type":"str","desc":"ID of the Location Attribute."},
                "values": {
                  "type":"seq",
                  "title":"Values to enable",
                  "sequence": [{"type":"str","title":"Value"}]
                }
              }
            }
          }
    }
  ]
}'
          ),
        array(
          'name' => 'species_sort',
          'caption' => 'Species Grid Sort',
          'description' => 'Select options for Species Grid Sort',
          'type' => 'jsonwidget',
          'required' => false,
          'group'=>'Transects Editor Settings',
          'schema' => '{
  "type":"map",
  "title":"Sort Order Options",
  "mapping": {
    "taxonomic": {
      "type":"map",
      "title":"Taxonomic Sort Order",
      "mapping": {
        "enabled": {"type":"bool","title":"Enabled"}
      }
    },
    "preferred": {
      "type":"map",
      "title":"Sort by preferred taxon name",
      "mapping": {
        "enabled": {"type":"bool","title":"Enabled"},
        "default": {"type":"bool","title":"Default"}
      }
    },
    "common": {
      "type":"map",
      "title":"Sort by taxon common name",
      "mapping": {
        "enabled": {"type":"bool","title":"Enabled"},
        "default": {"type":"bool","title":"Default"}
      }
    },
    "taxon": {
      "type":"map",
      "title":"Sort by taxon",
      "mapping": {
        "enabled": {"type":"bool","title":"Enabled"},
        "default": {"type":"bool","title":"Default"}
      }
    }
  }
}'

          ),

          array(
              'name'=>'taxon_column',
              'caption'=>'Display Taxon field',
              'description'=>'When displaying a taxon, choose what to use.',
              'type' => 'select',
              'lookupValues' => array('taxon'=>'Common Name',
                  'preferred_taxon'=>'Preferred Taxon (usually Latin)'),
              'required' => true,
              'default' => 'taxon',
              'group'=>'Transects Editor Settings'
          ),

        array(
          'name' => 'out_of_range_validation',
          'caption' => 'Out of range validation',
          'description' => 'Custom configuration for out of range validation',
          'type' => 'jsonwidget',
          'required' => false,
          'group'=>'Transects Editor Settings',
          'schema' =>
'{
  "type":"seq",
  "title":"Out of Range Validation Custom Configuration List",
  "sequence":
  [
    {
      "type":"map",
      "title":"Taxon List Definition",
      "mapping": {
        "taxon": {"type":"str","title":"Taxon","desc":"Taxon: included for commenting purposes only."},
        "taxon_meaning_id": {"type":"str","title":"Taxon Meaning ID","required":true,"desc":"Meaning ID of the Taxon."},
        "walk_limit": {"type":"str","title":"Walk Limit","desc":"The threshold number of the species on a single walk, across all sections in the walk: any value above this will trigger a confirmation dialog."},
        "section_limit": {"type":"str","title":"Section Limit","desc":"The threshold number of the species on a single transect section: any value above this will trigger a confirmation dialog."},
      }
    }
  ]
}'
        ),

        array(
          'name'=>'species_tab_1',
          'caption'=>'Species Tab 1 Title',
          'description'=>'The title to be used on the species checklist for the main tab.',
          'type'=>'string',
          'required' => true,
          'group'=>'Species'
        ),
        array(
          'name'=>'taxon_list_id',
          'caption'=>'All Species List',
          'description'=>'The species checklist used to populate the grid on the main grid when All Species is selected. Also used to drive the autocomplete when other options selected.',
          'type'=>'select',
          'table'=>'taxon_list',
          'captionField'=>'title',
          'valueField'=>'id',
          'extraParams' => array('orderby'=>'title'),
          'required' => true,
          'siteSpecific'=>true,
          'group'=>'Species'
        ),
        array(
          'name' => 'taxon_min_rank_1',
          'caption' => 'Min Rank',
          'description' => 'Some species lists implement taxon ranks. These ranks are sorted, and have a sort order, where the most general have a low value, and the most specific have a high value. Setting this field allows things like Order classifications to be excluded from the Pick List.',
          'type' => 'int',
          'required' => FALSE,
          'siteSpecific' => TRUE,
          'group' => 'Species'
        ),
        array(
          'name'=>'main_taxon_filter_field',
          'caption'=>'All Species List: Field used to filter taxa',
          'description'=>'If you want to allow recording for just part of the selected All Species List, then select which field you will '.
              'use to specify the filter by.',
          'type'=>'select',
          'options' => array(
            'taxon' => 'Taxon',
            'taxon_meaning_id' => 'Taxon Meaning ID',
            'taxon_group' => 'Taxon group title'
          ),
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species'
        ),
        array(
          'name'=>'main_taxon_filter',
          'caption'=>'All Species List: Taxon filter items',
          'description'=>'When filtering the list of available taxa, taxa will not be available for recording unless they match one of the '.
              'values you input in this box. Enter one value per line. E.g. enter a list of taxon group titles if you are filtering by taxon group.',
          'type' => 'textarea',
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species'
        ),
        array(
          'name'=>'common_taxon_list_id',
          'caption'=>'Common Species List',
          'description'=>'The species checklist used to populate the grid on the main grid when Common Species is selected.',
          'type'=>'select',
          'table'=>'taxon_list',
          'captionField'=>'title',
          'valueField'=>'id',
          'extraParams' => array('orderby'=>'title'),
          'required'=>false,
          'siteSpecific'=>true,
          'group'=>'Species'
        ),
        array(
          'name'=>'common_taxon_filter_field',
          'caption'=>'Common Species List: Field used to filter taxa',
          'description'=>'If you want to allow recording for just part of the selected Common Species List, then select which field you will '.
              'use to specify the filter by.',
          'type'=>'select',
          'options' => array(
            'taxon' => 'Taxon',
            'taxon_meaning_id' => 'Taxon Meaning ID',
            'taxon_group' => 'Taxon group title'
          ),
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species'
        ),
        array(
          'name'=>'common_taxon_filter',
          'caption'=>'Common Species List: Taxon filter items',
          'description'=>'When filtering the list of available taxa, taxa will not be available for recording unless they match one of the '.
              'values you input in this box. Enter one value per line. E.g. enter a list of taxon group titles if you are filtering by taxon group.',
          'type' => 'textarea',
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species'
        ),
        array(
          'name' => 'start_list_1',
          'caption' => 'Start with species list',
          'description' => 'Preselect which species list to polulate the first species grid with.',
          'type'=>'select',
          'options' => array(
            'branch' => 'Branch specific species list',
            'full' => 'Full species list',
            'common' => 'Common species list', // only available on tab 1
            'here' => 'Previous species recorded at this location',
            'mine' => 'Previous species recorded by user'
            // no filled entry, will always do this, plue "here" as a minimum
          ),
          'required' => true,
          'default' => 'full',
          'group' => 'Species'
        ),
        array(
          'name' => 'disable_full_1',
          'caption' => 'Disable Full List',
          'description' => 'Some lists (e.g. the full UK Species List) may be too big to allow the grid to populate the full list. Select this to prevent the full list being displayed in the first tab.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Species'
        ),
        array(
          'name' => 'disable_tso_1',
          'caption' => 'Disable Taxonomic Sort Order',
          'description' => 'Some lists (e.g. the full UK Species List) may not have the taxonomic sort order filled in. In this case, there is no point in sorting by the TSO. The default will be the next available SO from the list common name, preferred taxon, taxon.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Species'
        ),
        array(
          'name' => 'branch_taxon_list_configuration',
          'caption' => 'Branch specific taxon lists',
          'description' => 'Custom configuration for branch specific taxon lists',
          'type' => 'jsonwidget',
          'required' => false,
          'group'=>'Species',
          'schema' =>
'{
  "type":"seq",
  "title":"Branch Specific Taxon List Custom Configuration List",
  "sequence":
  [
    {
      "type":"map",
      "title":"Taxon List Definition",
      "mapping": {
        "location_id_list": {"type":"seq",
          "title":"Location IDs",
          "desc":"List of Location IDs this Taxon List definition applies to.",
          "required":true,
          "sequence":[{
            "type":"int",
            "title":"ID",
            "desc":"ID of the Branch Location."
          }]},
        "taxon_list_id": {"type":"str","title":"Taxon List ID","required":true,"desc":"ID of the Taxon List."},
        "taxon_filter_field": {"type":"str","title":"Filter Field","desc":"Field used to filter taxa, e.g. taxon, taxon_meaning_id or taxon_group."},
        "taxon_filter": {"type":"txt","title":"Filter Values","desc":"When filtering the list of available taxa, taxa will not be available for recording unless they match one of the values you input in this box. Enter one value per line. E.g. enter a list of taxon group titles if you are filtering by taxon group."},
      }
    }
  ]
}'
        ),

        array(
          'name'=>'species_tab_2',
          'caption'=>'Species Tab 2 Title',
          'description'=>'The title to be used on the species checklist for the second tab.',
          'type'=>'string',
          'required'=>false,
          'group'=>'Species 2'
        ),
        array(
          'name' => 'start_list_2',
          'caption' => 'Start with species list',
          'description' => 'Preselect which species list to populate the second species grid with: if not Full a species control will be provided to allow the addition of extra taxa to the list.',
          'type'=>'select',
          'options' => array(
            'full' => 'Full species list',
            'here' => 'Previous species recorded at this location',
            'none' => 'Empty List'
          ),
          'required' => true,
          'default' => 'here',
          'group' => 'Species 2'
        ),
        array(
          'name' => 'disable_full_2',
          'caption' => 'Disable Full List',
          'description' => 'Some lists (e.g. the full UK Species List) may be too big to allow the grid to populate the full list. Select this to prevent the full list being displayed in the second tab.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Species 2'
        ),
        array(
          'name'=>'second_taxon_list_id',
          'caption'=>'Second Tab Species List',
          'description'=>'The species checklist used to drive the autocomplete in the optional second grid. If not provided, the second grid and its tab are omitted.',
          'type'=>'select',
          'table'=>'taxon_list',
          'captionField'=>'title',
          'valueField'=>'id',
          'extraParams' => array('orderby'=>'title'),
          'required'=>false,
          'siteSpecific'=>true,
          'group'=>'Species 2'
        ),
        array(
          'name' => 'taxon_min_rank_2',
          'caption' => 'Min Rank',
          'description' => 'Some species lists implement taxon ranks. These ranks are sorted, and have a sort order, where the most general have a low value, and the most specific have a high value. Setting this field allows things like Order classifications to be excluded from the Pick List.',
          'type' => 'int',
          'required' => FALSE,
          'siteSpecific' => TRUE,
          'group' => 'Species 2'
        ),
        array(
          'name'=>'second_taxon_filter_field',
          'caption'=>'Second Tab Species List: Field used to filter taxa',
          'description'=>'If you want to allow recording for just part of the selected Species List, then select which field you will '.
              'use to specify the filter by.',
          'type'=>'select',
          'options' => array(
            'taxon' => 'Taxon',
            'taxon_meaning_id' => 'Taxon Meaning ID',
            'taxon_group' => 'Taxon group title'
          ),
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species 2'
        ),
        array(
          'name'=>'second_taxon_filter',
          'caption'=>'Second Tab Species List: Taxon filter items',
          'description'=>'When filtering the list of available taxa, taxa will not be available for recording unless they match one of the '.
              'values you input in this box. Enter one value per line. E.g. enter a list of taxon group titles if you are filtering by taxon group.',
          'type' => 'textarea',
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species 2'
        ),
        array(
          'name'=>'occurrence_attribute_id_2',
          'caption'=>'Second Tab Occurrence Attribute',
          'description'=>'The attribute that will be presented in the Second Species Tab grid for input, if different to the Occurrence Attribute above. Omit if using the same.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'captionField'=>'caption',
          'valueField'=>'id',
          'extraParams' => array('orderby'=>'caption'),
          'required' => false,
          'siteSpecific'=>true,
          'group'=>'Species 2'
        ),
        array(
          'name' => 'disable_tso_2',
          'caption' => 'Disable Taxonomic Sort Order',
          'description' => 'Some lists (e.g. the full UK Species List) may not have the taxonomic sort order filled in. In this case, there is no point in sorting by the TSO. The default will be the next available SO from the list common name, preferred taxon, taxon.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Species 2'
        ),
        array(
          'name'=>'species_tab_3',
          'caption'=>'Species Tab 3 Title',
          'description'=>'The title to be used on the species checklist for the third tab.',
          'type'=>'string',
          'required'=>false,
          'group'=>'Species 3'
        ),
        array(
          'name' => 'start_list_3',
          'caption' => 'Start with species list',
          'description' => 'Preselect which species list to polulate the third species grid with: if not Full a species control will be provided to allow the addition of extra taxa to the list.',
          'type'=>'select',
          'options' => array(
            'full' => 'Full species list',
            'here' => 'Previous species recorded at this location',
            'none' => 'Empty List'
          ),
          'required' => true,
          'default' => 'here',
          'group' => 'Species 3'
        ),
        array(
          'name' => 'disable_full_3',
          'caption' => 'Disable Full List',
          'description' => 'Some lists (e.g. the full UK Species List) may be too big to allow the grid to populate the full list. Select this to prevent the full list being displayed in the third tab.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Species 3'
        ),
        array(
          'name'=>'third_taxon_list_id',
          'caption'=>'Third Tab Species List',
          'description'=>'The species checklist used to drive the autocomplete in the optional third grid. If not provided, the third grid and its tab are omitted.',
          'type'=>'select',
          'table'=>'taxon_list',
          'captionField'=>'title',
          'valueField'=>'id',
          'extraParams' => array('orderby'=>'title'),
          'required'=>false,
          'siteSpecific'=>true,
          'group'=>'Species 3'
        ),
        array(
          'name' => 'taxon_min_rank_3',
          'caption' => 'Min Rank',
          'description' => 'Some species lists implement taxon ranks. These ranks are sorted, and have a sort order, where the most general have a low value, and the most specific have a high value. Setting this field allows things like Order classifications to be excluded from the Pick List.',
          'type' => 'int',
          'required' => FALSE,
          'siteSpecific' => TRUE,
          'group' => 'Species 3'
        ),
        array(
          'name'=>'third_taxon_filter_field',
          'caption'=>'Third Tab Species List: Field used to filter taxa',
          'description'=>'If you want to allow recording for just part of the selected Species List, then select which field you will '.
              'use to specify the filter by.',
          'type'=>'select',
          'options' => array(
            'taxon' => 'Taxon',
            'taxon_meaning_id' => 'Taxon Meaning ID',
            'taxon_group' => 'Taxon group title'
          ),
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species 3'
        ),
        array(
          'name'=>'third_taxon_filter',
          'caption'=>'Third Tab Species List: Taxon filter items',
          'description'=>'When filtering the list of available taxa, taxa will not be available for recording unless they match one of the '.
              'values you input in this box. Enter one value per line. E.g. enter a list of taxon group titles if you are filtering by taxon group.',
          'type' => 'textarea',
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species 3'
        ),
        array(
          'name'=>'occurrence_attribute_id_3',
          'caption'=>'Third Tab Occurrence Attribute',
          'description'=>'The attribute that will be presented in the Third Species Tab grid for input, if different to the Occurrence Attribute above. Omit if using the same.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'captionField'=>'caption',
          'valueField'=>'id',
          'extraParams' => array('orderby'=>'caption'),
          'required' => false,
          'siteSpecific'=>true,
          'group'=>'Species 3'
        ),
        array(
          'name' => 'disable_tso_3',
          'caption' => 'Disable Taxonomic Sort Order',
          'description' => 'Some lists (e.g. the full UK Species List) may not have the taxonomic sort order filled in. In this case, there is no point in sorting by the TSO. The default will be the next available SO from the list common name, preferred taxon, taxon.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Species 3'
        ),
        array(
          'name'=>'species_tab_4',
          'caption'=>'Fourth Species Tab Title',
          'description'=>'The title to be used on the species checklist for the fourth tab.',
          'type'=>'string',
          'required'=>false,
          'group'=>'Species 4'
        ),
        array(
          'name' => 'start_list_4',
          'caption' => 'Start with species list',
          'description' => 'Preselect which species list to polulate the fourth species grid with: if not Full a species control will be provided to allow the addition of extra taxa to the list.',
          'type'=>'select',
          'options' => array(
            'full' => 'Full species list',
            'here' => 'Previous species recorded at this location',
            'none' => 'Empty List'
          ),
          'required' => true,
          'default' => 'here',
          'group' => 'Species 4'
        ),
        array(
          'name' => 'disable_full_4',
          'caption' => 'Disable Full List',
          'description' => 'Some lists (e.g. the full UK Species List) may be too big to allow the grid to populate the full list. Select this to prevent the full list being displayed in the fourth tab.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Species 4'
        ),
        array(
          'name'=>'fourth_taxon_list_id',
          'caption'=>'Fourth Tab Species List',
          'description'=>'The species checklist used to drive the autocomplete in the optional fourth grid. If not provided, the fourth grid and its tab are omitted.',
          'type'=>'select',
          'table'=>'taxon_list',
          'captionField'=>'title',
          'valueField'=>'id',
          'extraParams' => array('orderby'=>'title'),
          'required'=>false,
          'siteSpecific'=>true,
          'group'=>'Species 4'
        ),
        array(
          'name' => 'taxon_min_rank_4',
          'caption' => 'Min Rank',
          'description' => 'Some species lists implement taxon ranks. These ranks are sorted, and have a sort order, where the most general have a low value, and the most specific have a high value. Setting this field allows things like Order classifications to be excluded from the Pick List.',
          'type' => 'int',
          'required' => FALSE,
          'siteSpecific' => TRUE,
          'group' => 'Species 4'
        ),
        array(
          'name'=>'fourth_taxon_filter_field',
          'caption'=>'Fourth Tab Species List: Field used to filter taxa',
          'description'=>'If you want to allow recording for just part of the selected Species List, then select which field you will '.
              'use to specify the filter by.',
          'type'=>'select',
          'options' => array(
            'taxon' => 'Taxon',
            'taxon_meaning_id' => 'Taxon Meaning ID',
            'taxon_group' => 'Taxon group title'
          ),
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species 4'
        ),
        array(
          'name'=>'fourth_taxon_filter',
          'caption'=>'Fourth Tab Species List: Taxon filter items',
          'description'=>'When filtering the list of available taxa, taxa will not be available for recording unless they match one of the '.
              'values you input in this box. Enter one value per line. E.g. enter a list of taxon group titles if you are filtering by taxon group.',
          'type' => 'textarea',
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species 4'
        ),
        array(
          'name'=>'occurrence_attribute_id_4',
          'caption'=>'Fourth Tab Occurrence Attribute',
          'description'=>'The attribute that will be presented in the Fourth Species Tab grid for input, if different to the Occurrence Attribute above. Omit if using the same.',
          'type'=>'select',
          'table'=>'occurrence_attribute',
          'captionField'=>'caption',
          'valueField'=>'id',
          'extraParams' => array('orderby'=>'caption'),
          'required' => false,
          'siteSpecific'=>true,
          'group'=>'Species 4'
        ),
        array(
          'name' => 'disable_tso_4',
          'caption' => 'Disable Taxonomic Sort Order',
          'description' => 'Some lists (e.g. the full UK Species List) may not have the taxonomic sort order filled in. In this case, there is no point in sorting by the TSO. The default will be the next available SO from the list common name, preferred taxon, taxon.',
          'type'=>'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Species 4'
        ),
        array(
          'fieldname'=>'cache_lookup',
          'label'=>'Cache lookups',
          'helpText'=>'Tick this box to select to use a cached version of the lookup list when '.
              'searching for extra species names to add to the grid, or set to false to use the '.
              'live version (default). The latter is slower and places more load on the warehouse so should only be '.
              'used during development or when there is a specific need to reflect taxa that have only '.
              'just been added to the list.',
          'type'=>'checkbox',
          'required'=>false,
          'group'=>'Species Map',
          'siteSpecific'=>false
        ),
        array(
          'name'=>'species_ctrl',
          'caption'=>'Single Species Selection Control Type',
          'description'=>'The type of control that will be available to select a single species.',
          'type'=>'select',
          'options' => array(
            'autocomplete' => 'Autocomplete',
            'select' => 'Select',
            'listbox' => 'List box',
            'radio_group' => 'Radio group',
            'treeview' => 'Treeview',
            'tree_browser' => 'Tree browser'
          ),
          'default' => 'autocomplete',
          'group'=>'Species Map'
        ),
        array(
          'name'=>'defaults',
          'caption'=>'Default Values',
          'description'=>'Supply default values for each field as required. On each line, enter fieldname=value. For custom attributes, '.
              'the fieldname is the untranslated caption. For other fields, it is the model and fieldname, e.g. occurrence.record_status. '.
              'For date fields, use today to dynamically default to today\'s date. NOTE, currently only supports occurrence:record_status and '.
              'sample:date but will be extended in future.',
              'type'=>'textarea',
              'default'=>'occurrence:record_status=C',
          'group'=>'Species Map',
          'required' => false
        ),
        array(
          'name'=>'custom_attribute_options',
          'caption'=>'Options for custom attributes',
          'description'=>'A list of additional options to pass through to custom attributes, one per line. Each option should be specified as '.
              'the attribute name followed by | then the option name, followed by = then the value. For example, smpAttr:1|class=control-width-5.',
          'type'=>'textarea',
          'required'=>false,
          'siteSpecific'=>true
        ),
        array(
          'name'=>'my_walks_page',
          'caption'=>'Path to My Walks',
          'description'=>'Path used to access the My Walks page after a successful submission. This is the default if not from URL parameter provided.',
          'type'=>'text_input',
          'required'=>true,
          'siteSpecific'=>true
        ),
        array(
            'name'=>'managerPermission',
            'caption'=>'Drupal Permission for Manager mode',
            'description'=>'Enter the Drupal permission name to be used to determine if this user is a manager. Entering this will allow the identified users access to the full locations list when entering a walk.',
            'type'=>'string',
            'required' => false,
            'group' => 'Transects Editor Settings'
        ),
        array(
            'name' => 'branch_assignment_permission',
            'label' => 'Drupal Permission name for Branch Manager',
            'type' => 'string',
            'description' => 'Enter the Drupal permission name to be used to determine if this user is a Branch Manager. Entering this will allow the identified users access to locations identified as theirs using the Branch CMS User ID integer attribute on the locations.',
            'required'=>false,
            'group' => 'Transects Editor Settings'
        ),
        array(
          'name' => 'user_locations_filter',
          'caption' => 'User locations filter',
          'description' => 'Should the locations available be filtered to those which the user is linked to, by a multivalue CMS User ID attribute ' .
              'in the location data? If not ticked, then all locations are available.',
          'type' => 'boolean',
          'required' => false,
          'default' => true,
          'group' => 'Transects Editor Settings'
        ),
        array(
          'name' => 'supress_tab_msg',
          'caption' => 'Supress voluntary message',
          'description' => 'On the 2nd, 3rd and 4th Species tabs there is a optional message stating that completing the data on the tab is optional. Select this option to remove this message.',
          'type' => 'boolean',
          'required' => false,
          'default' => false,
          'group' => 'Transects Editor Settings'
        ),
        array(
          'name'=>'confidentialAttrID',
          'caption' => 'Location attribute used to indicate confidential sites',
          'description' => 'A boolean location attribute, set to true if a site is confidential.',
          'type'=>'select',
          'table'=>'location_attribute',
          'captionField'=>'caption',
          'valueField'=>'id',
          'extraParams' => array('orderby'=>'caption'),
          'required' => false,
          'group' => 'Confidential and Sensitivity Handling'
        ),
        array(
          'name'=>'sensitiveAttrID',
          'caption' => 'Location attribute used to indicate sensitive sites',
          'description' => 'A boolean location attribute, set to true if a site is sensitive.',
          'type'=>'select',
          'table'=>'location_attribute',
          'captionField'=>'caption',
          'valueField'=>'id',
          'extraParams' => array('orderby'=>'caption'),
          'required' => false,
          'group' => 'Confidential and Sensitivity Handling'
        ),
        array(
          'name' => 'sensitivityPrecision',
          'caption' => 'Sensitivity Precision',
          'description' => 'Precision to be applied to new occurrences recorded at sensitive sites. Existing occurrences are not changed. A number representing the square size in metres - e.g. enter 1000 for 1km square.',
          'type' => 'int',
          'required' => false,
          'group' => 'Confidential and Sensitivity Handling'
        ),
        array(
          'name'=>'finishedAttrID',
          'caption' => 'Sample attribute used to flag walk as finished.',
          'description' => 'A boolean sample attribute, which is set to true if data entry on the walk has been finished. Should be flagged as applies_to_location, single value, integer. Cant flag integers attributes as hidden fields, so this is done by the form. This stores the year for which entry of walks for the location is finished.',
          'type'=>'select',
          'table'=>'sample_attribute',
          'captionField'=>'caption',
          'valueField'=>'id',
          'extraParams' => array('orderby'=>'caption'),
          'siteSpecific'=>true,
          'required' => false
        )
      )
    );
  }

  /**
   * Return the generated form output.
   * @param array $args List of parameter values passed through to the form depending on how the form has been configured.
   * This array always contains a value for language.
   * @param object $nid The Drupal node object's ID.
   * @param array $response When this form is reloading after saving a submission, contains the response from the service call.
   * Note this does not apply when redirecting (in this case the details of the saved object are in the $_GET data).
   * @return Form HTML.
   */
  public static function get_form($args, $nid, $response=null) {
    iform_load_helpers(array('map_helper'));

    $r = (isset($response['error']) ? data_entry_helper::dump_errors($response) : '');
    if (!function_exists('hostsite_module_exists') || !hostsite_module_exists('iform_ajaxproxy')) {
      return $r.'This form must be used in Drupal with the Indicia AJAX Proxy module enabled.';
    }
    if (!hostsite_module_exists('easy_login')) {
      return $r.'This form must be used in Drupal with the Easy Login module enabled.';
    }
    self::$userId = hostsite_get_user_field('indicia_user_id');
    if (empty(self::$userId)) {
      return '<p>Easy Login active but could not identify user</p>'; // something is wrong
    // TODO REPLACE
    // self::$userId = 1;
    // $r .= '<p>Easy Login active but could not identify user</p>';
    }

    self::$auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);

    self::$sampleID = isset($_GET['sample_id']) ? $_GET['sample_id'] : null;
    // TODO if error, the entity to load should already be filled in.
    if (self::$sampleID) {
      data_entry_helper::load_existing_record(self::$auth['read'], 'sample', self::$sampleID);
      self::$locationID = data_entry_helper::$entity_to_load['sample:location_id'];
    } else {
      self::$locationID = isset($_GET['site']) ? $_GET['site'] : null;
      // location ID also might be in the $_POST data after a validation save of a new record
      if (!self::$locationID && isset($_POST['sample:location_id']))
        self::$locationID = $_POST['sample:location_id'];
    }

    if(isset($_POST['from'])) $url = $_POST['from'];
    else if(isset($_GET['from'])) $url = $_GET['from'];
    else $url = $args['my_walks_page'];
    $url = explode('?', $url, 2);
    $params = NULL;
    $fragment = NULL;
    // fragment is always at the end.
    if(count($url)>1){
      $params = explode('#', $url[1], 2);
      if(count($params)>1) $fragment=$params[1];
      $params=$params[0];
    } else {
      $url = explode('#', $url[0], 2);
      if (count($url)>1) $fragment=$url[1];
    }
    $args['return_page'] = hostsite_get_url($url[0], array('query' => $params, 'fragment' => $fragment, 'absolute' => TRUE));

    data_entry_helper::$javascript .= 'indiciaData.ajaxUrl="' . hostsite_get_url('iform/ajax/ukbms_sectioned_transects_input_sample') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.nid = "' . $nid . "\";\n";

    if (((isset($_REQUEST['page']) && $_REQUEST['page']==='mainSample') || isset($_REQUEST['occurrences'])) && !isset(data_entry_helper::$validation_errors) && !isset($response['error'])) {
      // we have just saved the sample page, so move on to the occurrences list,
      return $r.self::get_occurrences_form($args, $nid, $response);
    } else {
      return $r.self::get_sample_form($args, $nid, $response);
    }
  }

  public static function get_sample_form($args, $nid, $response) {

    data_entry_helper::add_resource('autocomplete');

    $formOptions = array(
        'userID' => self::$userId,
        'surveyID' => $args['survey_id']
      );

    $sampleMethods = helper_base::get_termlist_terms(self::$auth, 'indicia:sample_methods', array(empty($args['transect_sample_method_term']) ? 'Transect' : $args['transect_sample_method_term']));
    if (count($sampleMethods) != 1) {
      return 'Did not recognise sample method '.$args['transect_sample_method_term'];
    }
    $locationType = helper_base::get_termlist_terms(self::$auth, 'indicia:location_types', array(empty($args['transect_type_term']) ? 'Transect' : $args['transect_type_term']));
    if (count($locationType) != 1) {
      return 'Did not recognise location type '.$args['transect_type_term'];
    }

    $attributes = data_entry_helper::getAttributes(array(
        'id' => self::$sampleID,
        'valuetable'=>'sample_attribute_value',
        'attrtable'=>'sample_attribute',
        'key'=>'sample_id',
        'fieldprefix'=>'smpAttr',
        'extraParams'=>self::$auth['read'],
        'survey_id'=>$args['survey_id'],
        'sample_method_id'=>$sampleMethods[0]['id']
    ));

    if (!empty($args['attribute_configuration'])) {
      $attribute_configuration = json_decode($args['attribute_configuration'], true);
      foreach ($attribute_configuration as $attrConfig) {
        if(empty($attributes[$attrConfig['id']])) continue; // may be a section only attribute
        $rules = explode("\n", $attributes[$attrConfig['id']]['validation_rules']);
        $req = array_search('required', $rules);
        if($req !== false) { // required validation must be switched off on the warehouse
          throw new exception("Form Config error: warehouse enabled required validation detected on sample attribute ".$attrConfig['id']);
        }
        if(isset($attrConfig['required']['main_page']) && $attrConfig['required']['main_page'] && $req === false) {
          $rules[] = 'required';
          $attributes[$attrConfig['id']]['validation_rules'] = implode("\n", $rules);
        } // required validation must be switched off on the warehouse so don't check reverse.
      }
    }

    if(isset($args['include_map_samples_form']) && $args['include_map_samples_form'])
      $r .= '<div id="cols" class="ui-helper-clearfix"><div class="left" style="width: '.(98-(isset($args['percent_width']) ? $args['percent_width'] : 50)).'%">';

    // we pass through the read auth. This makes it possible for the get_submission method to authorise against the warehouse
    // without an additional (expensive) warehouse call, so it can get location details.
    $r = '<form method="post" id="sample">' .
        self::$auth['write'] .
        '<input type="hidden" name="page" value="mainSample"/>' .
        '<input type="hidden" name="from" value="'.$args['return_page'].'"/>' .
        '<input type="hidden" name="read_nonce" value="'.self::$auth['read']['nonce'].'"/>' .
        '<input type="hidden" name="read_auth_token" value="'.self::$auth['read']['auth_token'].'"/>' .
        '<input type="hidden" name="website_id" value="'.$args['website_id'].'"/>' .
        '<input type="hidden" name="sample:survey_id" value="'.$args['survey_id'].'"/>' .
      (isset(data_entry_helper::$entity_to_load['sample:id']) ?
        '<input type="hidden" name="sample:id" value="'.data_entry_helper::$entity_to_load['sample:id'].'"/>' : '') .
      '<input type="hidden" name="sample:sample_method_id" value="'.$sampleMethods[0]['id'].'" />' .
        get_user_profile_hidden_inputs($attributes, $args, isset(data_entry_helper::$entity_to_load['sample:id']), self::$auth['read']).
        '<p id="finishedMessage" class="ui-state-highlight page-notice ui-corner-all" style="display: none;">' .
          lang::get('The Walks for this location have been flagged as finished for the year') . ' <span id="finishedMessageYear">X</span>. ' .
          // lang::get('This was done during the entry of the walk data for <date>. ') .
          lang::get('This is an information message only - you can continue to enter data') . '. ' .
          // lang::get('Alternatively you can click the following button to clear the finished flag for year <X>[ when you save this walk] ') .
          // lang::get('<Clear Finished flag>') .
          '</p>';

    if (self::$locationID) {
      $site = data_entry_helper::get_population_data(array(
        'table' => 'location',
        'extraParams' => self::$auth['read'] + array('view'=>'detail','id'=>self::$locationID,'deleted'=>'f', 'location_type_id'=>$locationType[0]['id'])
      ));
      if (count($site) !== 1) {
        throw new exception("Could not identify specific site");
      }
      $site = $site[0];
      if(in_array($site['centroid_sref_system'], array('osgb','osie')))
        $site['centroid_sref_system'] = strtoupper($site['centroid_sref_system']);
      $r .= '<input type="hidden" name="sample:location_id" value="'.self::$locationID.'"/>' .
            '<input type="hidden" name="sample:entered_sref" value="'.$site['centroid_sref'].'"/>' .
            '<input type="hidden" name="sample:entered_sref_system" value="'.$site['centroid_sref_system'].'"/>';
    }

    // location control
    if (self::$locationID && (isset(data_entry_helper::$entity_to_load['sample:id']) || isset($_GET['site']))) {
      // for reload of existing data or if the site is specified in the URL, don't let the user change the transect as that would mess everything up.
      $r .= '<label>'.lang::get('Transect').':</label> <span class="value-label">'.$site['name'].'</span><br/>';
    } else {
      // Output only the locations for this website and transect type. Note we load both transects and sections, just so that
      // we always use the same warehouse call and therefore it uses the cache.
      // Allocation of locations to user is done by the CMS User Attribute on the Location record.
      // @todo convert this to use the Indicia user ID.
      $siteParams = self::$auth['read'] + array('website_id' => $args['website_id'], 'location_type_id'=>$locationType[0]['id']);
      if ((!isset($args['user_locations_filter']) || $args['user_locations_filter']) &&
          (!isset($args['managerPermission']) || !hostsite_user_has_permission($args['managerPermission']))) {
        $siteParams += array('locattrs'=>'CMS User ID', 'attr_location_cms_user_id'=>hostsite_get_user_field('id'));
      } else
        $siteParams += array('locattrs'=>'');
      $availableSites = data_entry_helper::get_population_data(array(
        'report'=>'library/locations/locations_list',
        'extraParams' => $siteParams,
        'nocache' => true
      ));
      // convert the report data to an array for the lookup, plus one to pass to the JS so it can keep the hidden sref fields updated
      $sitesLookup = array();
      $sitesJs = array();
      foreach ($availableSites as $site) {
        $sitesLookup[$site['location_id']]=$site['name'];
        $sitesJs[$site['location_id']] = array('centroid_sref'=>$site['centroid_sref'], 'centroid_sref_system'=>$site['centroid_sref_system']);
      }
      // bolt in branch locations. Don't assume that branch list is superset of normal sites list.
      // Only need to do if not a manager - they have already fetched the full list anyway.
      if(isset($args['branch_assignment_permission']) && hostsite_user_has_permission($args['branch_assignment_permission']) && $siteParams['locattrs']!='') {
        $siteParams['locattrs']='Branch CMS User ID';
        $siteParams['attr_location_branch_cms_user_id']=hostsite_get_user_field('id');
        unset($siteParams['attr_location_cms_user_id']);
        $availableSites = data_entry_helper::get_population_data(array(
            'report'=>'library/locations/locations_list',
            'extraParams' => $siteParams,
            'nocache' => true
        ));
        foreach ($availableSites as $site) {
          $sitesLookup[$site['location_id']]=$site['name']; // note no duplicates if assigned as user and branch
          $sitesJs[$site['location_id']] = $site;
        }
        natcasesort($sitesLookup); // merge into original list in alphabetic order.
      }
      $formOptions['sites'] = $sitesJs;
      $options = array(
        'label' => lang::get('Select Transect'),
        'validation' => array('required'),
        'blankText'=>lang::get('please select'),
        'lookupValues' => $sitesLookup,
      );
      if (self::$locationID)
        $options['default'] = self::$locationID;
      $r .= data_entry_helper::location_select($options);
    }
    if (!self::$locationID) {
      $r .= '<input type="hidden" name="sample:entered_sref" value="" id="entered_sref"/>' .
      '<input type="hidden" name="sample:entered_sref_system" value="" id="entered_sref_system"/>';
      // sref values for the sample will be populated automatically when the submission is built.
    }

    // Date entry field.
    if (isset(data_entry_helper::$entity_to_load['sample:date']) && preg_match('/^(\d{4})/', data_entry_helper::$entity_to_load['sample:date'])) {
      // Date has 4 digit year first (ISO style) - convert date to expected output format
      // @todo The date format should be a global configurable option. It should also be applied to reloading of custom date attributes.
      $d = new DateTime(data_entry_helper::$entity_to_load['sample:date']);
      data_entry_helper::$entity_to_load['sample:date'] = $d->format('d/m/Y');
    }
    if (!isset(data_entry_helper::$entity_to_load['sample:date']) && isset($_GET['date'])){
      $r .= '<input type="hidden" name="sample:date" value="'.$_GET['date'].'"/>' .
            '<label>'.lang::get('Date').':</label> <span class="value-label">'.$_GET['date'].'</span><br/>';
    } else {
      $r .= data_entry_helper::date_picker(array('label' => lang::get('Date'), 'fieldname' => 'sample:date'));
    }
  // remainder of form : sample attributes, comment, and buttons.
  if(isset($args['finishedAttrID']) && $args['finishedAttrID']!= '') {
    $attributes[$args['finishedAttrID']]['handled'] = true;
    $attrOptions = array(
        'fieldname' => $attributes[$args['finishedAttrID']]['fieldname'],
        'id' => $attributes[$args['finishedAttrID']]['id'],
        'disabled' => '',
        'default' => $attributes[$args['finishedAttrID']]['default'],
        'controlWrapTemplate' => 'justControl');
       $r .= data_entry_helper::apply_template('hidden_text', $attrOptions);
  }
    $r .= get_attribute_html($attributes, $args, array('extraParams'=>self::$auth['read']), null,
          (isset($args['custom_attribute_options']) && $args['custom_attribute_options'] ?
          get_attr_options_array_with_user_data($args['custom_attribute_options']) : array())) .
        data_entry_helper::textarea(array(
            'fieldname'=>'sample:comment',
            'label'=>lang::get('Notes'),
            'helpText'=>lang::get("Use this space to input comments about this week's walk.")
          )) .
        (lang::get('LANG_DATA_PERMISSION') !== 'LANG_DATA_PERMISSION' ? '<p>' . lang::get('LANG_DATA_PERMISSION') . '</p>' : '') .
        '<input type="submit" value="'.lang::get('Next').'" />' .
        '<a href="'.$args['return_page'].'" class="button">'.lang::get('Cancel').'</a>' .
        (isset(data_entry_helper::$entity_to_load['sample:id']) ?
            '<button id="delete-button" type="button" />'.lang::get('Delete').'</button>' : '') .
        '</form>';

    // Include side by side map if requested.
    if(isset($args['include_map_samples_form']) && $args['include_map_samples_form']){
      // no place search
      $options = iform_map_get_map_options($args, self::$auth['read']);
      $olOptions = iform_map_get_ol_options($args);
      if (!empty(data_entry_helper::$entity_to_load['sample:wkt'])) {
        $options['initialFeatureWkt'] = data_entry_helper::$entity_to_load['sample:wkt'];
      }
      $r .= '</div>' .
            '<div class="right" style="width: '.(isset($args['percent_width']) ? $args['percent_width'] : 50).'%">' .
          map_helper::map_panel($options, $olOptions) .
          '</div>';
    }

    // Find Recorder Name attribute for use with autocomplete.
    foreach($attributes as $attrID => $attr){
      if(strcasecmp('Recorder Name', $attr["untranslatedCaption"]) == 0){
        $formOptions['recorderNameAttrID'] = $attrID; // will be undefined if not present.
      } else if(strcasecmp('Start Time', $attr["untranslatedCaption"]) == 0 || strcasecmp('End Time', $attr["untranslatedCaption"]) == 0){
        // want to convert the time fields to html5 type=time: can't use JS to checnge type
        // Don't want to use the time control type  as doesn't meet customer requirements.
        // Am fully aware that functionality is very browser dependant.
        $safeId = str_replace(':','\\\\:',$attr["id"]);
        data_entry_helper::$javascript .= "$('#".$safeId."').prop('type', 'time').prop('placeholder', '__:__');\n";
      }
    }

    // Extra form to delete sample.
    $formOptions['deleteConfirm'] = lang::get('Are you sure you want to delete this walk?');
    if (isset(data_entry_helper::$entity_to_load['sample:id'])){
      // note we only require bare minimum in order to flag a sample as deleted.
      $r .= '<form method="post" id="delete-form" style="display: none;">' .
            self::$auth['write'] .
            '<input type="hidden" name="page" value="delete"/>' .
            '<input type="hidden" name="website_id" value="'.$args['website_id'].'"/>' .
            '<input type="hidden" name="sample:id" value="'.data_entry_helper::$entity_to_load['sample:id'].'"/>' .
            '<input type="hidden" name="sample:deleted" value="t"/>' .
            '</form>';
    }

    $formOptions['finishedAttrID'] = false;
    if(isset($args['finishedAttrID']) && $args['finishedAttrID']!= '') {
      $formOptions['finishedAttrID'] = $args['finishedAttrID'];
    }

    data_entry_helper::$javascript .= "\nsetUpSamplesForm(".json_encode((object)$formOptions).");\n\n";

    data_entry_helper::enable_validation('sample');
    return $r;
  }

  public static function get_occurrences_form($args, $nid, $response) {
    global $indicia_templates;

    $formOptions = array(
        'userID' => self::$userId,
        'surveyID' => $args['survey_id'],
        'autoCompletes' => array(),
        'speciesList' => array(),
        'speciesListForce' => array(),
        'speciesListFilterField' => array(),
        'speciesListFilterValues' => array(),
        'speciesMinRank' => array(),
        'duplicateTaxonMessage' => lang::get('LANG_Duplicate_Taxon'),
        'requiredMessage' => lang::get('This field is required'),
        'existingOccurrences' => array(),
        'occurrence_attribute' => array(),
        'occurrence_attribute_ctrl' => array(),
        'maxTabs' => 4,
        'branchSpeciesLists' => array(),
        'branchTaxonMeaningIDs' => array(),
        'commonTaxonMeaningIDs' => array(),
        'allTaxonMeaningIDsAtTransect' => array(),
        'existingTaxonMeaningIDs' => array(),
        'myTaxonMeaningIDs' => array(),
        'attribute_configuration' => (!empty($args['attribute_configuration']) ? json_decode($args['attribute_configuration'], true) : array()),
        'species_sort' => (!empty($args['species_sort']) ? json_decode($args['species_sort'], true) : array()),
        'taxon_column' => (isset($args['taxon_column']) ? $args['taxon_column'] : 'taxon'),
        'verificationTitle' => lang::get('Warnings'),
        'verificationSectionLimitMessage' => lang::get('The value entered for this taxon on this transect section ({{ value }}) exceeds the expected maximum ({{ limit }})'),
        'verificationWalkLimitMessage' => lang::get('The total seen for this taxon on this walk ({{ total }}) exceeds the expected maximum ({{ limit }})'),
        'outOfRangeVerification' => array()
    );

    // remove the ctrlWrap as it complicates the grid & JavaScript unnecessarily
    $oldCtrlWrapTemplate = $indicia_templates['controlWrap'];
    $indicia_templates['controlWrap'] = '{control}';

    // drupal_add_js('misc/tableheader.js'); // for sticky heading
    data_entry_helper::add_resource('jquery_form');
    data_entry_helper::add_resource('jquery_ui');
    data_entry_helper::add_resource('autocomplete');

    // did the parent sample previously exist? Default is no.
    $existing=false;

    // TODO move this to main function.
    if (isset($_POST['sample:id'])) {
      // have just posted an edit to the existing parent sample, so can use it to get the parent location id.
      $parentSampleId = $_POST['sample:id'];
      $existing=true;
    } else {
      if (isset($response['outer_id']))
        // have just posted a new parent sample, so can use it to get the parent location id.
        $parentSampleId = $response['outer_id'];
      else {
        $parentSampleId = $_GET['sample_id'];
        $existing=true;
      }
    }
    $formOptions['parentSampleId'] = $parentSampleId;
    data_entry_helper::load_existing_record(self::$auth['read'], 'sample', $parentSampleId);

    $parentLocId = data_entry_helper::$entity_to_load['sample:location_id']; // TODO should already be in self::$locationID
    data_entry_helper::$javascript .= 'indiciaData.parentLocId = "' . $parentLocId . "\";\n";
    $date = data_entry_helper::$entity_to_load['sample:date_start'];
    $formOptions['parentSampleDate'] = $date;

    // find any attributes that apply to transect section samples.
    $sampleMethods = helper_base::get_termlist_terms(self::$auth, 'indicia:sample_methods', array('Transect Section'));
    $attributes = data_entry_helper::getAttributes(array(
      'valuetable'=>'sample_attribute_value',
      'attrtable'=>'sample_attribute',
      'key'=>'sample_id',
      'fieldprefix'=>'smpAttr',
      'extraParams'=>self::$auth['read'],
      'survey_id'=>$args['survey_id'],
      'sample_method_id'=>$sampleMethods[0]['id'],
      'multiValue'=>false // ensures that array_keys are the list of attribute IDs.
    ));
    //  the parent sample and sub-samples have already been created: can't cache in case a new section added.
    // need to specify sample_method as this must be different to those used in species map.
    // Only returns section based subsamples, not map.
    $subSamples = data_entry_helper::get_population_data(array(
      'report' => 'library/samples/samples_list_for_parent_sample',
      'extraParams' => self::$auth['read'] + array('sample_id'=>$parentSampleId,'date_from'=>'','date_to'=>'', 'sample_method_id'=>$sampleMethods[0]['id'], 'smpattrs'=>implode(',', array_keys($attributes))),
      'nocache'=>true
    ));
    // transcribe the response array into a couple of forms that are useful elsewhere - one for outputting JSON so the JS knows about
    // the samples, and another for lookup of sample data by code later.
    $subSampleList = array();
    $subSamplesByCode = array();
    foreach ($subSamples as $subSample) {
      $subSampleList[$subSample['code']] = $subSample['sample_id'];
      $subSamplesByCode[$subSample['code']] = $subSample;
    }
    $formOptions['subSamples'] = (object)$subSampleList;

    $occurrences = array();
    if ($existing) {
      // Only need to load the occurrences for a pre-existing sample
      $attrs = array($args['occurrence_attribute_id']);
      if(isset($args['occurrence_attribute_id_2']) && $args['occurrence_attribute_id_2'] != "") $attrs[] = $args['occurrence_attribute_id_2'];
      if(isset($args['occurrence_attribute_id_3']) && $args['occurrence_attribute_id_3'] != "") $attrs[] = $args['occurrence_attribute_id_3'];
      if(isset($args['occurrence_attribute_id_4']) && $args['occurrence_attribute_id_4'] != "") $attrs[] = $args['occurrence_attribute_id_4'];
      $o = data_entry_helper::get_population_data(array(
        'report' => 'reports_for_prebuilt_forms/UKBMS/ukbms_occurrences_list_for_parent_sample',
        'extraParams' => self::$auth['read'] + array('view'=>'detail','sample_id'=>$parentSampleId,'survey_id'=>$args['survey_id'],'date_from'=>'','date_to'=>'','taxon_group_id'=>'',
            'smpattrs'=>'', 'occattrs'=>implode(',',$attrs)),
        // don't cache as this is live data
        'nocache' => true
      ));
      // build an array keyed for easy lookup
      foreach($o as $occurrence) {
        $occurrences[$occurrence['sample_id'].':'.$occurrence['taxon_meaning_id']] = array(
          'ttl_id'=>$occurrence['taxa_taxon_list_id'],
          'taxon_meaning_id'=>$occurrence['taxon_meaning_id'],
          'o_id'=>$occurrence['occurrence_id'],
          'processed'=>false
        );
        foreach($attrs as $attr){
          $occurrences[$occurrence['sample_id'].':'.$occurrence['taxon_meaning_id']]['value_'.$attr] = $occurrence['attr_occurrence_'.$attr];
          $occurrences[$occurrence['sample_id'].':'.$occurrence['taxon_meaning_id']]['a_id_'.$attr] = $occurrence['attr_id_occurrence_'.$attr];
        }
    $formOptions['existingTaxonMeaningIDs'][$occurrence['taxon_meaning_id']] = true;
      }
      $formOptions['existingTaxonMeaningIDs'] = array_keys($formOptions['existingTaxonMeaningIDs']);
  }
    // store it in data for JS to read when populating the grid
    $formOptions['existingOccurrences'] = $occurrences;

    // The occurrence attribute must be flagged as numeric:true in the survey specific validation rules in order for totals to be worked out.
    $occ_attributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'occurrence_attribute_value',
        'attrtable'=>'occurrence_attribute',
        'key'=>'occurrence_id',
        'fieldprefix'=>'occAttr',
        'extraParams'=>self::$auth['read'],
        'survey_id'=>$args['survey_id'],
        'multiValue'=>false // ensures that array_keys are the list of attribute IDs.
    ));
    $defAttrOptions = array('extraParams'=>self::$auth['read']);
    foreach(array($args['occurrence_attribute_id'],
              (isset($args['occurrence_attribute_id_2']) && $args['occurrence_attribute_id_2']!="" ? $args['occurrence_attribute_id_2'] : $args['occurrence_attribute_id']),
              (isset($args['occurrence_attribute_id_3']) && $args['occurrence_attribute_id_3']!="" ? $args['occurrence_attribute_id_3'] : $args['occurrence_attribute_id']),
              (isset($args['occurrence_attribute_id_4']) && $args['occurrence_attribute_id_4']!="" ? $args['occurrence_attribute_id_4'] : $args['occurrence_attribute_id']))
            as $idx => $attr){
      unset($occ_attributes[$attr]['caption']);
      $ctrl = data_entry_helper::outputAttribute($occ_attributes[$attr], $defAttrOptions);
      $formOptions['occurrence_attribute'][$idx+1] = $attr;
    $formOptions['occurrence_attribute_ctrl'][$idx+1] = str_replace("\n","",$ctrl);
    }

    if(isset($args['out_of_range_validation']) && $args['out_of_range_validation'] !== "") {
        $formOptions['outOfRangeVerification'] = json_decode($args['out_of_range_validation'], true);
    }

    // Fetch the sections
    $sectionLocationType = helper_base::get_termlist_terms(self::$auth, 'indicia:location_types', array(empty($args['section_type_term']) ? 'Section' : $args['section_type_term']));
    $sections = data_entry_helper::get_population_data(array(
      'table' => 'location',
      'extraParams' => self::$auth['read'] + array('view'=>'detail','parent_id'=>$parentLocId,'deleted'=>'f','location_type_id'=>$sectionLocationType[0]['id']),
      'nocache' => true
    ));
    usort($sections, "ukbms_stis_sectionSort");
    $formOptions['sections'] = $sections;

    $location = data_entry_helper::get_population_data(array(
      'table' => 'location',
      'extraParams' => self::$auth['read'] + array('view'=>'detail','id'=>$parentLocId)
    ));

    $dateObj   = DateTime::createFromFormat('!Y#m#d', $date);
    $displayDate = $dateObj->format('l jS F Y');
    $r = '<h2 id="ukbms_stis_header">'.$location[0]['name']." on ".$displayDate."</h2>";
    if (lang::get('LANG_DATA_PERMISSION') !== 'LANG_DATA_PERMISSION') {
        $r .= '<p>' . lang::get('LANG_DATA_PERMISSION') . '</p>';
    }
    $r .= "<div id=\"tabs\">\n";

    $formOptions['finishedAttrID'] = false;
    if(isset($args['finishedAttrID']) && $args['finishedAttrID']!= '') {
      // Get all the samples for this location for this year
      $samples = data_entry_helper::get_population_data(array(
          'table' => 'sample',
          'extraParams' => self::$auth['read'] + array('view'=>'detail','location_id'=>$parentLocId),
            'nocache' => true
      ));
      $thisYearsSamples = array();
      $sampleDates = array();
      foreach($samples as $sample) {
        $sampleDates[$sample['id']] = $sample['display_date'];
        if(substr($sample['display_date'],0,4) == substr($date,0,4))
          $thisYearsSamples[] = $sample['id'];
      }
      $sample_finished_attributes = data_entry_helper::get_population_data(array(
          'table' => 'sample_attribute_value',
          'extraParams' => self::$auth['read'] + array('view'=>'list',
              'sample_id'=>$thisYearsSamples,
              'sample_attribute_id' => $args['finishedAttrID']),
            'nocache' => true
      ));
      $displayMessage = false;
      foreach($sample_finished_attributes as $sample_finished_attribute) {
        if($sample_finished_attribute['id'] != null) {
          $displayMessage = $sample_finished_attribute['sample_id'];
          break;
        }
      }
      // Get all the finished flags for those samples.
      if($displayMessage)
        $r .= '<p id="finishedMessage" class="ui-state-highlight page-notice ui-corner-all">' .
          lang::get('The Walks for this location have been flagged as finished for the year') . ' ' .substr($date,0,4) . '. ' .
          // lang::get('This was done during the entry of the walk data for') . ' ' . $sampleDates[$displayMessage] . '. ' .
          lang::get('This is an information message only - you can continue to enter data.') .
          '</p>';
      else
        $formOptions['finishedAttrID'] = $args['finishedAttrID'];

    }

    // bit of a bodge here but converts backwardly compatible args into useful ones.
    $args['taxon_list_id_1']    = $args['taxon_list_id'];
    $args['taxon_filter_field_1']  = (isset($args['main_taxon_filter_field']) ? $args['main_taxon_filter_field'] : '');
    $args['taxon_filter_1']      = (isset($args['main_taxon_filter']) ? $args['main_taxon_filter'] : '');
    $args['taxon_list_id_2']    = (isset($args['second_taxon_list_id']) ? $args['second_taxon_list_id'] : '');
    $args['taxon_filter_field_2']  = (isset($args['second_taxon_filter_field']) ? $args['second_taxon_filter_field'] : '');
    $args['taxon_filter_2']      = (isset($args['second_taxon_filter']) ? $args['second_taxon_filter'] : '');
    $args['taxon_list_id_3']    = (isset($args['third_taxon_list_id']) ? $args['third_taxon_list_id'] : '');
    $args['taxon_filter_field_3']  = (isset($args['third_taxon_filter_field']) ? $args['third_taxon_filter_field'] : '');
    $args['taxon_filter_3']      = (isset($args['third_taxon_filter']) ? $args['third_taxon_filter'] : '');
    $args['taxon_list_id_4']    = (isset($args['fourth_taxon_list_id']) ? $args['fourth_taxon_list_id'] : '');
    $args['taxon_filter_field_4']  = (isset($args['fourth_taxon_filter_field']) ? $args['fourth_taxon_filter_field'] : '');
    $args['taxon_filter_4']      = (isset($args['fourth_taxon_filter']) ? $args['fourth_taxon_filter'] : '');

    $tabs = array('#grid1'=>t($args['species_tab_1'])); // tab 1 is required.
    if($args['taxon_list_id_2']!='')
      $tabs['#grid2']=t(isset($args['species_tab_2']) && $args['species_tab_2'] != '' ? $args['species_tab_2'] : 'Species Tab 2');
    if(isset($args['third_taxon_list_id']) && $args['third_taxon_list_id']!='')
      $tabs['#grid3']=t(isset($args['species_tab_3']) && $args['species_tab_3'] != '' ? $args['species_tab_3'] : 'Species Tab 3');
    if(isset($args['fourth_taxon_list_id']) && $args['fourth_taxon_list_id']!='')
      $tabs['#grid4']=t(isset($args['species_tab_4']) && $args['species_tab_4'] != '' ? $args['species_tab_4'] : 'Species Tab 4');
    $r .= data_entry_helper::tab_header(array('tabs'=>$tabs));
    data_entry_helper::enable_tabs(array(
        'divId'=>'tabs',
        'style'=>'Tabs'
    ));

    // Produce special lists required for grid one, where user can choose branch list, full list, common, here or mine.
    // other grids are fixed by form configuration.

    // The branch list is a branch specific taxon list, where the branch is defined by the a multivalue field in the user account
    // It is only available in the first species tab.
    // It is assumed that the meaning_ids it comes up with are a subset of the main tab1 list: this assumption also applies for the common list.
    $branches = hostsite_get_user_field('branch_record_in', array(), true);
    // $branches = array("1953") ;  // TODOconfirm comes in as a string
    if (count($branches) > 0 && isset($args['branch_taxon_list_configuration']) && $args['branch_taxon_list_configuration'] != '') {
      $branchesConfiguration = json_decode($args['branch_taxon_list_configuration'], true);
      foreach ($branchesConfiguration as $branchConfig) {
        $intersect = array_intersect($branches, $branchConfig["location_id_list"]);
        if(count($intersect) > 0) {
          $extraParams = array_merge(self::$auth['read'],
              array('taxon_list_id' => $branchConfig["taxon_list_id"],
                    'preferred' => 't',
                    'allow_data_entry' => 't',
                    'view' => 'cache'));
          if (!empty($branchConfig['taxon_filter_field']) && !empty($branchConfig['taxon_filter'])) {
            $extraParams[$branchConfig['taxon_filter_field']] = helper_base::explode_lines($branchConfig['taxon_filter']);
          }
          $taxa = data_entry_helper::get_population_data(array('table' => 'taxa_taxon_list', 'extraParams' => $extraParams, 'columns' => 'taxon_meaning_id'));
          foreach ($intersect as $branch) {
            $formOptions['branchSpeciesLists']['branch'.$branch] = count($formOptions['branchTaxonMeaningIDs']);
          }
          $taxaList = array();
          foreach($taxa as $taxon){
            $taxaList[] = $taxon['taxon_meaning_id'];
          }
          $formOptions['branchTaxonMeaningIDs'][] = $taxaList;
        }
      }
    }

    // Common is only used on first species tab.
    if (!empty($args['common_taxon_list_id'])) {
      $extraParams = array_merge(self::$auth['read'],
          array('taxon_list_id' => $args['common_taxon_list_id'],
              'preferred' => 't',
              'allow_data_entry' => 't',
              'view' => 'cache'));
      if (!empty($args['common_taxon_filter_field']) && !empty($args['common_taxon_filter']))
        $extraParams[$args['common_taxon_filter_field']] = helper_base::explode_lines($args['common_taxon_filter']);
      $taxa = data_entry_helper::get_population_data(array('table' => 'taxa_taxon_list', 'extraParams' => $extraParams, 'columns' => 'taxon_meaning_id'));
      foreach($taxa as $taxon){
        $formOptions['commonTaxonMeaningIDs'][] = $taxon['taxon_meaning_id'];
      }
    }

    $taxa = data_entry_helper::get_population_data(array(
        'report' => 'reports_for_prebuilt_forms/UKBMS/ukbms_taxon_meanings_at_transect',
        'extraParams' => self::$auth['read'] + array('location_id' => $parentLocId, 'survey_id'=>$args['survey_id']),
        'nocache' => true // don't cache as this is live data
    ));
    foreach($taxa as $taxon){
      $formOptions['allTaxonMeaningIDsAtTransect'][] = $taxon['taxon_meaning_id'];
    } // all meanings will include currently filled in data.

    $extraParams = array_merge(self::$auth['read'],
          array('created_by_id' => self::$userId,
              'survey_id'=>$args['survey_id'],
        'view' => 'cache'));
  $taxa = data_entry_helper::get_population_data(array('table' => 'occurrence', 'extraParams' => $extraParams, 'columns' => 'taxon_meaning_id'));
  foreach($taxa as $taxon){
    $formOptions['myTaxonMeaningIDs'][$taxon['taxon_meaning_id']] = true;
  }
  $formOptions['myTaxonMeaningIDs'] = array_keys($formOptions['myTaxonMeaningIDs']);

     $r .= self::_buildGrid ($formOptions, 1, $args, $sections, $occ_attributes, count($occurrences)>0, true, $attributes, $subSamplesByCode) .
      ($args['taxon_list_id_2'] !='' ? self::_buildGrid ($formOptions, 2, $args, $sections, $occ_attributes, count($occurrences)>0) : '') .
      ($args['taxon_list_id_3'] !='' ? self::_buildGrid ($formOptions, 3, $args, $sections, $occ_attributes, count($occurrences)>0) : '') .
      ($args['taxon_list_id_4'] !='' ? self::_buildGrid ($formOptions, 4, $args, $sections, $occ_attributes, count($occurrences)>0) : '') .
      '</div>';

    // stub form to attach validation to.
    $r .= '<form style="display: none" id="validation-form"></form>';
    data_entry_helper::enable_validation('validation-form');
    // A stub form for AJAX posting when we need to create an occurrence
    $locationType = helper_base::get_termlist_terms(self::$auth, 'indicia:location_types', array(empty($args['transect_type_term']) ? 'Transect' : $args['transect_type_term']));
    if(!empty($args["sensitiveAttrID"]) && isset($args["sensitivityPrecision"]) && $args["sensitivityPrecision"] != "") {
      $sensitive_site_attributes = data_entry_helper::getAttributes(array(
            'valuetable'=>'location_attribute_value'
            ,'attrtable'=>'location_attribute'
            ,'key'=>'location_id'
            ,'fieldprefix'=>'locAttr'
            ,'extraParams'=>self::$auth['read'] + array('id'=>$args["sensitiveAttrID"])
            ,'location_type_id'=>$locationType[0]['id']
            ,'survey_id'=>$args['survey_id']
            ,'id' => $parentLocId // location ID
      ));
    }
    if(!empty($args["confidentialAttrID"])) {
        $confidential_site_attributes = data_entry_helper::getAttributes(array(
            'valuetable'=>'location_attribute_value'
            ,'attrtable'=>'location_attribute'
            ,'key'=>'location_id'
            ,'fieldprefix'=>'locAttr'
            ,'extraParams'=>self::$auth['read'] + array('id'=>$args["confidentialAttrID"])
            ,'location_type_id'=>$locationType[0]['id']
            ,'survey_id'=>$args['survey_id']
            ,'id' => $parentLocId // location ID
        ));
    }
    $defaults = helper_base::explode_lines_key_value_pairs($args['defaults']);
    $record_status = isset($defaults['occurrence:record_status']) ? $defaults['occurrence:record_status'] : 'C';
    $r .= '<form style="display: none" id="occ-form" method="post" action="'.iform_ajaxproxy_url($nid, 'occurrence').'">' .
        '<input name="website_id" value="'.$args['website_id'].'"/>' .
        '<input name="survey_id" value="'.$args["survey_id"].'" />' .
        '<input name="occurrence:id" id="occid" />' .
        '<input name="occurrence:deleted" id="occdeleted" />' .
        '<input name="occurrence:zero_abundance" id="occzero" />' .
        '<input name="occurrence:taxa_taxon_list_id" id="ttlid" />' .
        '<input name="occurrence:record_status" value="'.$record_status.'" />' .
        '<input name="occurrence:sample_id" id="occ_sampleid"/>' .
        (isset($args["sensitiveAttrID"]) && $args["sensitiveAttrID"] != "" && isset($args["sensitivityPrecision"]) && $args["sensitivityPrecision"] != "" ?
            '<input name="occurrence:sensitivity_precision" id="occSensitive" value="'.(count($sensitive_site_attributes)>0 && $sensitive_site_attributes[$args["sensitiveAttrID"]]['default']=="1" ? $args["sensitivityPrecision"] : '').'"/>' : '') .
        (!empty($args["confidentialAttrID"]) ?
            '<input name="occurrence:confidential" id="occConfidential" value="'.(count($confidential_site_attributes)>0 && $confidential_site_attributes[$args["confidentialAttrID"]]['default']=="1" ? '1' : '0').'"/>' : '') .
      '<input name="occAttr:' . $args['occurrence_attribute_id'] . '" id="occattr"/>' .
      '<input name="transaction_id" id="occurrence_transaction_id"/>' .
      '<input name="user_id" value="'.self::$userId.'"/>' .
       '</form>';

    // A stub form for AJAX posting when we need to update a subsample
    $r .= '<form style="display: none" id="smp-form" method="post" action="'.iform_ajaxproxy_url($nid, 'sample').'">' .
        '<input name="website_id" value="'.$args['website_id'].'"/>' .
        '<input name="sample:id" id="smpid" />' .
        '<input name="sample:parent_id" value="'.$parentSampleId.'" />' .
        '<input name="sample:survey_id" value="'.$args['survey_id'].'" />' .
        '<input name="sample:sample_method_id" value="'.$sampleMethods[0]['id'].'" />' .
        '<input name="sample:entered_sref" id="smpsref" />' .
        '<input name="sample:entered_sref_system" id="smpsref_system" />' .
        '<input name="sample:location_id" id="smploc" />' .
        '<input name="sample:date" value="'.$date.'" />';
    // include a stub input for each transect section sample attribute
    foreach ($attributes as $attr) {
      $r .= '<input id="'.$attr['fieldname'].'" />';
    }
    $r .= '<input name="transaction_id" id="sample_transaction_id"/>' .
      '<input name="user_id" value="'.self::$userId.'"/>' .
        '</form>';

    if(isset($args['finishedAttrID']) && $args['finishedAttrID']!= '' && $displayMessage === false) {
      // A stub form for posting when we need to flag a super sample as finished
      $formOptions['return_page'] = $args['return_page'];
      $sampleMethods = helper_base::get_termlist_terms(self::$auth, 'indicia:sample_methods', array(empty($args['transect_sample_method_term']) ? 'Transect' : $args['transect_sample_method_term']));
      $finished_attributes = data_entry_helper::getAttributes(array(
            'valuetable'=>'sample_attribute_value'
            ,'attrtable'=>'sample_attribute'
            ,'key'=>'sample_id'
            ,'fieldprefix'=>'smpAttr'
            ,'extraParams'=>self::$auth['read']
            ,'sample_method_id'=>$sampleMethods[0]['id']
            ,'survey_id'=>$args['survey_id']
            ,'id' => $parentSampleId
      ));
      // The finished attribute is a sample attribute that applies to the location.
      // If present, the attribute holds the year value, and is multi value: it is a finished_for_year flag.
    // TODO Can they reset the finished flag? Not yet
      // At this point the date has been filled in, so can fill in the form
      $r .= "\n".'<form style="display: none" id="finished-form" method="post" action="'.iform_ajaxproxy_url($nid, 'sample').'">' .
          '<input name="website_id" value="'.$args['website_id'].'"/>' .
          '<input name="sample:id" value="'.$parentSampleId.'" />' .
          '<input name="sample:survey_id" value="'.$args['survey_id'].'" />' .
          '<input name="sample:sample_method_id" value="'.$sampleMethods[0]['id'].'" />' .
          '<input name="sample:location_id" id="smploc" value="'.$parentLocId.'"/>' .
          '<input name="sample:date" value="'.$date.'" />';
      // include a stub input for each transect sample attribute: need to include all as some will be mandatory
      foreach ($finished_attributes as $attr) {
        if($attr['attributeId'] == $args["finishedAttrID"]) {
           $attr['default'] = substr($date,0,4);
        }
        $r .= '<input name="'.$attr['fieldname'].'" value="'.$attr['default'].'"/>'; //TODO html safe for text?
      }
    $r .=   '<input name="user_id" value="'.self::$userId.'"/>' .
          '</form>';
    }

    $r .= '<div style="display: none"><div id="warning-dialog"><p>' .
          lang::get('The following warnings have been identified') .
          '</p><ul id="warning-dialog-list"></ul><p>' .
          lang::get('Do you still wish to enter this data?') .
          '</p></div></div>';

    data_entry_helper::$javascript .= "\nsetUpOccurrencesForm(".json_encode((object)$formOptions).");\n\n";

    $indicia_templates['controlWrap'] = $oldCtrlWrapTemplate;
    return $r;
  }

  private static function _buildSortControl ($tabNum, $args) {
    $default = "taxonomic_sort_order";
    $r = '<input name="species-sort-order-'.$tabNum.'" type="hidden" value="'.$default.'">';
    if(isset($args['species_sort'])) {
      $configuration = json_decode($args['species_sort'], true);
      $options = array();
      if(isset($configuration['taxonomic']) && (!isset($args['disable_tso_'.$tabNum]) || $args['disable_tso_'.$tabNum] == false)) {
        if(isset($configuration['taxonomic']['enabled']) && $configuration['taxonomic']['enabled'])
          $options['taxonomic_sort_order'] = lang::get('Taxonomic Sort Order');
        else $default = false;
      } else $default = false;
      if(isset($configuration['common']))
        if(isset($configuration['common']['enabled']) && $configuration['common']['enabled']) {
          $options['default_common_name'] = lang::get('Common name');
          if($default == false || (isset($configuration['common']['default']) && $configuration['common']['default']))
            $default = 'default_common_name';
        }
      if(isset($configuration['preferred']))
        if(isset($configuration['preferred']['enabled']) && $configuration['preferred']['enabled']) {
          $options['preferred_taxon'] = lang::get('Species name');
          if($default == false || (isset($configuration['preferred']['default']) && $configuration['preferred']['default']))
            $default = 'preferred_taxon';
        }
      if(isset($configuration['taxon']))
        if(isset($configuration['taxon']['enabled']) && $configuration['taxon']['enabled']) {
          $options['taxon'] = lang::get('Taxon');
          if($default == false || (isset($configuration['taxon']['default']) && $configuration['taxon']['default']))
            $default = 'taxon';
        }
      if(count($options) == 1) {
        $r = '<input name="species-sort-order-'.$tabNum.'" type="hidden" value="'.$default.'">';
      } else if (count($options) > 1) {
        $r = '<br/>'.data_entry_helper::radio_group(array(
              'label'=>lang::get('Species Sort Order'),
              'fieldname'=>'species-sort-order-'.$tabNum,
              'lookupValues' => $options,
              'default'=>$default,
              'class'=>'species-sort-order'
        ));
      } // count=0 -> defaults to initial value of $r, i.e. taxonomic sort order.
    }
    return $r;
  }

  protected static function _buildGrid (&$formOptions, $tabNum, $args, $sections, $occ_attributes, $existing, $includeControl = false, $attributes = array(), $subSamplesByCode = array()) {
    $isNumber = ($occ_attributes[(isset($args['occurrence_attribute_id_'.$tabNum]) && $args['occurrence_attribute_id_'.$tabNum]!="" ?
        $args['occurrence_attribute_id_'.$tabNum] : $args['occurrence_attribute_id'])]["data_type"] == 'I');

    $listSelected = hostsite_get_user_field('default_species_view');
    if (empty($listSelected)) {
      $listSelected = isset($args['start_list_'.$tabNum]) ? $args['start_list_'.$tabNum] : 'here';
    }
    if(!in_array($listSelected, array('branch','full','common','mine','here'))) {
      $listSelected = 'here';
    }
    if($listSelected === 'branch' && ($tabNum != 1 || count($formOptions['branchTaxonMeaningIDs']) === 0)) {
      // branch is only available on tab 1, provided it is defined
      $listSelected = 'full';
    }
    if($listSelected == 'common' && ($tabNum != 1 || count($formOptions['commonTaxonMeaningIDs']) == 0)) { // common is only available on tab 1, provided it is defined
      $listSelected = 'full';
    }
    if($listSelected == 'full' && isset($args['disable_full_'.$tabNum]) && $args['disable_full_'.$tabNum]) {
      $listSelected = 'here';
    }

    $r = '<div id="grid'.$tabNum.'">' .
         '<p id="grid'.$tabNum.'-loading"><b>' . lang::get('Loading - Please Wait') . '</b></p>';
    if ($includeControl) {
      $r .= '<label for="listSelect'.$tabNum.'">'.lang::get('Use species list').' :</label>'.
            '<select id="listSelect'.$tabNum.'">';
      if ($tabNum == 1 && count($formOptions['branchTaxonMeaningIDs']) > 0) {
        foreach ($formOptions['branchSpeciesLists'] as $branch => $idx) {
          $branchID = substr($branch,6);
          $branchRecord = data_entry_helper::get_population_data(
                array('table' => 'location',
                      'extraParams' => array_merge(self::$auth['read'], array('id' => $branchID, 'view' => 'detail')),
                      'columns' => 'name'));
          $r .= '<option value="'.$branch.'">'.$branchRecord[0]['name'].' '.lang::get('specific species list').'</option>';
        }
      }
      $r .=  '<option value="full"'.($listSelected == 'full' ? ' selected="selected"' : '').'>'.lang::get('All species').'</option>'.
             ($tabNum == 1 && (count($formOptions['commonTaxonMeaningIDs']) > 0) ? '<option value="common"'.($listSelected == 'common' ? ' selected="selected"' : '').'>'.lang::get('Common species').'</option>' : '').
             '<option value="here"'.($listSelected == 'here' ? ' selected="selected"' : '').'>'.lang::get('Species known at this site').'</option>'.
             '<option value="mine"'.($listSelected == 'mine' ? ' selected="selected"' : '').'>'.lang::get('Species I have recorded').'</option>'.
           '</select>';
    } else {
      $r .= (isset($args['supress_tab_msg']) && $args['supress_tab_msg'] ? '' : '<p>' . lang::get('LANG_Tab_Msg') . '</p>');
    }

    $r .= self::_buildSortControl($tabNum, $args) .
          '<table id="transect-input'.$tabNum.'" class="ui-widget species-grid"><thead class="table-header">' .
          '<tr><th class="ui-widget-header">' . lang::get('Sections') . '</th>';
    foreach ($sections as $idx=>$section) {
      $r .= '<th class="ui-widget-header col-'.($idx+1).'">' . $section['code'] . '</th>';
    }
    $r .= ($isNumber ? '<th class="ui-widget-header">' . lang::get('Total') . '</th>' : '').'</tr></thead>';

    // output rows at the top for any transect section level sample attributes
    $rowClass='';
    $r .= '<tbody class="ui-widget-content">';
    $attribute_configuration = (!empty($args['attribute_configuration']) ? json_decode($args['attribute_configuration'], true) : array());
//    var_dump($attributes);
    foreach ($attributes as $attrID => $attr) {
      $inc = true;
      $mandatory = true;
      foreach ($attribute_configuration as $attrConfig) {
        if($attrID != $attrConfig['id']) continue;
        if(!empty($attrConfig['required']) &&
            isset($attrConfig['required']['species_grid']) &&
            $attrConfig['required']['species_grid'] == false)
          $mandatory = false;
        if(empty($attrConfig['filter'])) continue;
        $inc = false;
        $locAttrs = data_entry_helper::get_population_data(array(
            'table' => 'location_attribute_value',
            'extraParams' => self::$auth['read'] + array('location_id' => data_entry_helper::$entity_to_load['sample:location_id'],
                'location_attribute_id' => $attrConfig['filter']['id']),
            'caching' => false
        ));
        foreach($locAttrs as $locAttr) {
          $inc |= (in_array($locAttr['value'], $attrConfig['filter']['values']));
        }
      }
      if(!$inc) continue;
      $r .= '<tr '.$rowClass.' id="smp-'.$attr['attributeId'].'"><td>'.$attr['caption'].'</td>';
      $rowClass=$rowClass=='' ? 'class="alt-row"':'';
      unset($attr['caption']);
      foreach ($sections as $idx=>$section) {
        // output a cell with the attribute - tag it with a class & id to make it easy to find from JS.
        $attrOpts = array(
            'class' => 'smp-input smpAttr-'.$section['code'],
            'id' => $attr['fieldname'].':'.$section['code'],
            'extraParams'=>self::$auth['read']
        );
        // if there is an existing value, set it and also ensure the attribute name reflects the attribute value id.
        if (isset($subSamplesByCode[$section['code']])) {
          // but have to take into account possibility that this field has been blanked out, so deleting the attribute.
          if(isset($subSamplesByCode[$section['code']]['attr_id_sample_'.$attr['attributeId']]) && $subSamplesByCode[$section['code']]['attr_id_sample_'.$attr['attributeId']] != ''){
            $attrOpts['fieldname'] = $attr['fieldname'] . ':' . $subSamplesByCode[$section['code']]['attr_id_sample_'.$attr['attributeId']];
            $attr['default'] = $subSamplesByCode[$section['code']]['attr_sample_'.$attr['attributeId']];
          } else
            $attr['default']=isset($_POST[$attr['fieldname']]) ? $_POST[$attr['fieldname']] : '';
        } else {
          $attr['default']=isset($_POST[$attr['fieldname']]) ? $_POST[$attr['fieldname']] : '';
        }
        if($mandatory && $attr['default']=='')
          $attrOpts['class'] .= ' ui-state-error';
        $r .= '<td class="col-'.($idx+1).' '.($idx % 5 == 0 ? 'first' : '').'">' .
            data_entry_helper::outputAttribute($attr, $attrOpts) .
            ($mandatory && $attr['default']=='' ? '<p htmlfor="'.$attrOpts['id'].'" class="inline-error">' . lang::get('This field is required') . '</p>' : '') .
            '</td>';
      }
      $r .= ($isNumber ? '<td class="ui-state-disabled first"></td>' : '').'</tr>';
    }
    $r .= '</tbody>';
    $r .= '<tbody class="ui-widget-content occs-body"></tbody>';
    if($isNumber) { // add a totals row only if the attribute is a number
      $r .= '<tfoot><tr><td>Total</td>';
      foreach ($sections as $idx=>$section) {
        $r .= '<td class="col-'.($idx+1).' '.($idx % 5 == 0 ? 'first' : '').' col-total"></td>';
      }
      $r .= '<td class="ui-state-disabled first"></td></tr></tfoot>';
    }
    $r .= '</table>';

    if($listSelected !='full' || $includeControl || $tabNum == 1)
      $r .= '<span id="taxonLookupControlContainer'.$tabNum.'"><label for="taxonLookupControl'.$tabNum.'" class="auto-width">'.lang::get('Add species to list').':</label> <input id="taxonLookupControl'.$tabNum.'" name="taxonLookupControl'.$tabNum.'" ></span>';
    $r .= '<br />';
    $reloadUrl = data_entry_helper::get_reload_link_parts();
    $reloadUrl['params']['sample_id'] = $formOptions['parentSampleId'];
    foreach ($reloadUrl['params'] as $key => $value) {
      $reloadUrl['path'] .= (strpos($reloadUrl['path'],'?')===false ? '?' : '&')."$key=$value";
    }
    if (lang::get('LANG_DATA_PERMISSION') !== 'LANG_DATA_PERMISSION') {
      $r .= '<p>' . lang::get('LANG_DATA_PERMISSION') . '</p>';
    }
    $r .= '<a href="'.$reloadUrl['path'].'" class="button">'.lang::get('Back to visit details').'</a>';
    $r .= '<a href="'.$args['return_page'].'" class="button">'.lang::get('Finish and return to walk list').'</a>';
    if(isset($formOptions['finishedAttrID']) && $formOptions['finishedAttrID'] != '')
      $r .= '<input type="button" class="button smp-finish" value="'. str_replace('%s',substr($formOptions['parentSampleDate'],0,4), lang::get('Flag walk entry for year %s as finished for this location, and return to walk list')) .'"/>';
    $r .= '</div>';
    // page is ajax so no submit button.
    $formOptions['speciesList'][$tabNum] = $args['taxon_list_id_'.$tabNum];
    $formOptions['speciesMinRank'][$tabNum] = (isset($args['taxon_min_rank_'.$tabNum]) && $args['taxon_min_rank_'.$tabNum] !== "" ?
                                                $args['taxon_min_rank_'.$tabNum] : -1);
    $formOptions['speciesListForce'][$tabNum] = $listSelected; // this may hold branch, which indicates the first branch list
    if ($args['taxon_filter_'.$tabNum] != '') {
      $filterLines = helper_base::explode_lines($args['taxon_filter_'.$tabNum]);
      $formOptions['speciesListFilterField'][$tabNum] = $args['taxon_filter_field_'.$tabNum];
      $formOptions['speciesListFilterValues'][$tabNum] = $filterLines;
    }
    if($listSelected !='full' || $includeControl || $tabNum == 1) {
      $autoComplete = new stdClass();
      $autoComplete->tabNum = $tabNum;
      $formOptions['autoCompletes'][] = $autoComplete;
    }

    return $r;
  }

  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values.
   * @param array $args iform parameters.
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    $subsampleModels = array();
    if (!isset($values['page']) || ($values['page']=='mainSample')) {
      // submitting the first page, with top level sample details
      $read = array(
        'nonce' => $values['read_nonce'],
        'auth_token' => $values['read_auth_token']
      );
      if (empty($values['sample:entered_sref'])) {
        // the sample does not have sref data, as the user has just picked a transect site at this point. Copy the
        // site's centroid across to the sample. Should this be cached?
        $site = data_entry_helper::get_population_data(array(
          'table' => 'location',
          'extraParams' => $read + array('view'=>'detail','id'=>$values['sample:location_id'],'deleted'=>'f'),
          'caching' => false
        ));
        $site = $site[0];
        $values['sample:entered_sref'] = $site['centroid_sref'];
        if(in_array($site['centroid_sref_system'], array('osgb','osie')))
          $site['centroid_sref_system'] = strtoupper($site['centroid_sref_system']);
        $values['sample:entered_sref_system'] = $site['centroid_sref_system'];
      }
      // Build the subsamples
      $sections = data_entry_helper::get_population_data(array(
        'table' => 'location',
        'extraParams' => $read + array('view'=>'detail','parent_id'=>$values['sample:location_id'],'deleted'=>'f'),
        'nocache' => true // may have recently added or removed a section
      ));
      if(isset($values['sample:id'])){
        $existingSubSamples = data_entry_helper::get_population_data(array(
          'table' => 'sample',
          'extraParams' => $read + array('view'=>'detail','parent_id'=>$values['sample:id'],'deleted'=>'f'),
          'nocache' => true  // may have recently added or removed a section
        ));
      } else $existingSubSamples = array();
      $sampleMethods = helper_base::get_termlist_terms(array('read'=>$read), 'indicia:sample_methods', array('Transect Section'));
      $attributes = data_entry_helper::getAttributes(array(
        'valuetable'=>'sample_attribute_value',
        'attrtable'=>'sample_attribute',
        'key'=>'sample_id',
        'fieldprefix'=>'smpAttr',
        'extraParams'=>$read,
        'survey_id'=>$values['sample:survey_id'],
        'sample_method_id'=>$sampleMethods[0]['id'],
        'multiValue'=>false // ensures that array_keys are the list of attribute IDs.
      ));
      $smpDate = self::parseSingleDate($values['sample:date']);
      foreach($sections as $section){
        $smp = false;
        $exists=false;
        foreach($existingSubSamples as $existingSubSample){
          if($existingSubSample['location_id'] == $section['id']){
            $exists = $existingSubSample;
            break;
          }
        }
        if(!$exists){
          $smp = array('fkId' => 'parent_id',
                   'model' => array('id' => 'sample',
                     'fields' => array('survey_id' => array('value' => $values['sample:survey_id']),
                                       'website_id' => array('value' => $values['website_id']),
                                       'date' => array('value' => $values['sample:date']),
                                       'location_id' => array('value' => $section['id']),
                                       'entered_sref' => array('value' => $section['centroid_sref']),
                                       'entered_sref_system' => array('value' => $section['centroid_sref_system']),
                                       'sample_method_id' => array('value' => $sampleMethods[0]['id'])
                     )),
                   'copyFields' => array('date_start'=>'date_start','date_end'=>'date_end','date_type'=>'date_type'));
          // for a new subsample, fill in the attributes: set any default, or if none, copy from same main sample attr
          foreach ($attributes as $attr) {
            if (!empty($attr['default'])) {
              $smp['model']['fields']['smpAttr:'.$attr['attributeId']] = array('value' => $attr['default']);
              continue;
            }
            foreach ($values as $key => $value){
              $parts = explode(':',$key);
              if(count($parts)>1 && $parts[0]=='smpAttr' && $parts[1]==$attr['attributeId']){
                $smp['model']['fields']['smpAttr:'.$attr['attributeId']] = array('value' => $value);
              }
            }
          }
        } else { // need to ensure any date change is propagated: only do if date has changed for performance reasons.
          $subSmpDate = self::parseSingleDate($exists['date_start']);
          if(strcmp($smpDate,$subSmpDate))
          $smp = array('fkId' => 'parent_id',
              'model' => array('id' => 'sample',
                  'fields' => array('survey_id' => array('value' => $values['sample:survey_id']),
                      'website_id' => array('value' => $values['website_id']),
                      'id' => array('value' => $exists['id']),
                                    'date' => array('value' => $values['sample:date']),
                      'location_id' => array('value' => $exists['location_id'])
                  )),
              'copyFields' => array('date_start'=>'date_start','date_end'=>'date_end','date_type'=>'date_type'));
        }
        if($smp) $subsampleModels[] = $smp;
      }
    }
    $submission = submission_builder::build_submission($values, array('model' => 'sample'));
    if(count($subsampleModels)>0)
      $submission['subModels'] = $subsampleModels;
    return($submission);
  }

  // we assume that this is not quite vague: we are looking for variations of YYYY-MM-DD, with diff separators
  // and possibly reverse ordered: month always in middle.
  protected static function parseSingleDate($string){
    if(preg_match('#^\d{2}/\d{2}/\d{4}$#', $string)){ // DD/MM/YYYY
      $results = preg_split('#/#', $string);
      return $results[2].'-'.$results[1].'-'.$results[0];
    }
    return $string;
  }

  /**
   * Override the form redirect to go back to My Walks after the grid is submitted. Leave default redirect (current page)
   * for initial submission of the parent sample.
   */
  public static function get_redirect_on_success($values, $args) {
    return  ($values['page']==='delete') ? $args['my_walks_page'] : '';
  }

  /*
   * Ajax function call: provided with taxon_meaning_id, location_id and date
   */
  public static function ajax_check_verification_rules($website_id, $password, $nid) {
    $ruleIDs = array();
    $warnings = array();
    $info = array();
    $ruleTypesDone = array('WithoutPolygon' => false, 'PeriodWithinYear' => false);

    iform_load_helpers(array('data_entry_helper', 'report_helper'));
    $readAuth = report_helper::get_read_auth($website_id, $password);

    $cttl = data_entry_helper::get_population_data(array(
        'table' => 'taxa_taxon_list',
        'extraParams' => $readAuth + array('view' => 'cache',
            'taxon_meaning_id' => $_GET['taxon_meaning_id'],
            'preferred' => 't')
      ));
    if (count($cttl) > 0) {
      $fieldsToCheck = array(array('key' => 'DataRecordId', 'field' => 'external_key'), // without polygon
          array('key' => 'Taxon', 'field' => 'preferred_taxon'), // without polygon
          array('key' => 'Tvk', 'field' => 'external_key'), // period within year
          array('key' => 'TaxonMeaningId', 'field' => 'taxon_meaning_id'), // period within year
          array('key' => 'Taxon', 'field' => 'preferred_taxon') // period within year
      );
      foreach ($fieldsToCheck as $entry) {
        $metadata = data_entry_helper::get_population_data(array(
            'table' => 'verification_rule_metadatum',
            'extraParams' => $readAuth + array('view'=>'detail',
                'key' => $entry['key'],
                'value' => $cttl[0][$entry['field']],
                'columns' => 'verification_rule_id')
        ));
        foreach ($metadata as $meta) {
          $ruleIDs[] = $meta['verification_rule_id'];
        }
      };
      if (count($ruleIDs) > 0) {
        $rules = data_entry_helper::get_population_data(array(
            'table' => 'verification_rule',
            'extraParams' => $readAuth + array('view' => 'detail',
                'orderby' => 'created_on',
                'sortdir'=>'DESC',
                'query' => json_encode(array('in' => array('id' => $ruleIDs, 'test_type' => array('WithoutPolygon', 'PeriodWithinYear')))))
        ));
        // we are assuming no reverse rules
        foreach ($rules as $rule) {
            if ($ruleTypesDone[$rule['test_type']] === true) {
              continue;
            }
            $ruleTypesDone[$rule['test_type']] = true;
            $metadata = data_entry_helper::get_population_data(array(
                'table' => 'verification_rule_metadatum',
                'extraParams' => $readAuth + array('view'=>'detail',
                    'verification_rule_id' => $rule['id'])
            ));
            switch ($rule['test_type']) {
                case 'WithoutPolygon' :
                    $vrmfield = self::vr_extract('metadata', $metadata, 'DataFieldName');
                    $info[] = array($vrmfield);
                    if ($vrmfield === null || $vrmfield !== 'Species') {
                      break;
                    }
                    // report is cacheable: the geometry for a particular parent location won't change, and the
                    // verification rule geometry will rarely change
                    $reportResult = data_entry_helper::get_population_data(array(
                        'report'=>'projects/ukbms/location_verification_intersection',
                        'extraParams' => $readAuth + array('location_id' => $_GET['location_id'],
                            'verification_rule_id' => $rule['id'])
                    ));
                    $info[] = array($reportResult);
                    // report returns the id of the location in a single record if the location intersects
                    if (count($reportResult) === 0 xor $rule['reverse_rule'] === 't') {
                      $warnings[] = $rule['error_message'];
                    }
                    break;
                case 'PeriodWithinYear' :
                    // we ignore the periods filtered by stage as only from Hoverfly and spider at moment.
                    $vrmstart = self::vr_extract('metadata', $metadata, 'StartDate', 4);
                    $vrmend = self::vr_extract('metadata', $metadata, 'EndDate', 4);
                    $vrmsurvey = self::vr_extract('metadata', $metadata, 'SurveyId');
                    $info[] = array($vrmstart, $vrmend, $vrmsurvey);
                    // Check survey
                    if ($vrmsurvey !== null && $vrmsurvey != $args['survey_id']) {
                      break;
                    }
                    $md = substr($_GET['date'], 5, 2) . substr($_GET['date'], 8, 2); // cut off year from front: Assume YYYY-MM-DD
                    // following logic copied from SQL in module
                    $fails = ($rule['reverse_rule'] === 't') !==
                      ((($vrmstart === null || $vrmend === null || $vrmstart <= $vrmend)
                            && (($vrmstart !== null && $md < $vrmstart) || ($vrmend !== null && $md > $vrmend)))
                        || (($vrmstart !== null && $vrmend !== null && $vrmstart > $vrmend)
                            && (($vrmstart !== null && $md < $vrmstart) && ($vrmend !== null && $md > $vrmend))));
                    if ($fails) {
                      $dateObj   = DateTime::createFromFormat('!m', substr($vrmstart, 0, 2));
                      $monthName1 = $dateObj->format('M');
                      $dateObj   = DateTime::createFromFormat('!m', substr($vrmend, 0, 2));
                      $monthName2 = $dateObj->format('M');
                      $warnings[] = $rule['error_message'] .
                          ' (' . ($vrmstart !== null ? substr($vrmstart, 2, 2) . ' ' . $monthName1 : '') .
                          '-' . ($vrmend !== null ? substr($vrmend, 2, 2) . ' ' . $monthName2 : '') . ')';
                    }
                    break;
            }
        }
      }
    }
    header('Content-type: application/json');
    echo json_encode(array('taxon_meaning_id' => $_GET['taxon_meaning_id'], 'warnings'=> $warnings,
//        'debug' => array('rules' => $rules, 'info' => $info),
    ));
  }

  private static function vr_extract($type, $data, $key, $length = null) {
      foreach($data as $pair) {
          if ($pair['key'] == $key && ($length === null || strlen($pair['value']) === $length)) {
              return $pair['value'];
          }
      }
      return null;
  }

}
