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
// Help Text : This selects what default species list is displayed when you enter first data for a walk. Common and
//   Branch are only available on the first tab (Butterflies): if you choose one of these options, the other tabs will
//   display the full list. The full list will not be displayed on the final (Others) tab, as this is the full UK list
//   and is very big: in this case, those taxa previously recorded here will be displayed. When viewing data
//   subsequently, only those taxa who have data recorded will initially be displayed. Leaving unselected will default
//   to the standard form configuration setting.
// Default: N/A
// Number of values: 1

// @todo
// ABLE/EBMS : Allow the scheme to choose from multiple location types.
//    Alter the location page to allow Country to pick a location from within the various location types.
//    add field to scheme to store the taxon attribute id
//    add a field to the scheme for the NUTS2 location level: optional, config value is attribute id
//    add field to the location record for NUTS2
//    extend API to handle additional field as geographic filter.
//    Add branch coordinator role, allocated as per UKBMS. Extend transect allocation config.
// For ABLE/EBMS - look up scheme for site, look up taxon attribute on it.
//
// Extras
// Attribute required flag - occurrence, subsample, supersample
// If an image caption is changed - have to save the image.
// If the image is deleted - have to do a save
// Extend supersample hideGrid to radio inputs.
// i18n
// Image resize definition
// Add subsample images: branch configurable.
// Rework location control
// Highlight the row/column of field with focus
// Switch off totals columns and rows.
// function comments
//
// Future Dev
// Species grid sub sample attributes: branch configurable: attributes which are associated with the
//   subsample, displayed with the species grids: can only appear on one species grid. Ensure don't appear in globals subsample attributes
// add filtering ability to auto complete look up and discriminator between subsidiary grids.


/**
 * A custom function for usort which sorts by the location code of a list of sections.
 */
function ukbms_stis2_sectionSort($a, $b)
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
class iform_ukbms_sectioned_transects_input_sample_2
{

  private static $auth;
  private static $userId;
  private static $sampleId;
  private static $locationID;
  private static $translations;
  private static $translated;
  private static $timings;

  /**
   * Return the form metadata. Note the title of this method includes the name of the form file. This ensures
   * that if inheritance is used in the forms, subclassed forms don't return their parent's form definition.
   * @return array The definition of the form.
   */
  public static function get_ukbms_sectioned_transects_input_sample_2_definition()
  {
    return [
      'title' => 'UKBMS Sectioned Transects Sample Input 2',
      'category' => 'BMS Specific forms',
      'description' => 'A form for inputting the counts of species observed at each section along a transect. ' .
        'Can be called with site=<id> in the URL to force the selection of a fixed site, or sample=<id> to edit ' .
        'an existing sample. Can handle multiple occurrence attributes and media. Bootstrap themed.'
    ];
  }

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters()
  {
    self::setupTranslations();
    
    return [
        // Although UKBMS has 3 different surveys which are fundamentally the same, they differ in the main location
        // type, and there are differences in the sample attributes: we need three different instances of this page.
        [
          'name' => 'managerPermission',
          'caption' => 'Drupal Permission for Manager mode',
          'description' => 'Enter the Drupal permission name to be used to determine if this user is a manager. Entering this will allow the identified users access to the full locations list when entering a walk.',
          'type' => 'string',
          'required' => FALSE,
          'group' => 'Permissions'
        ],
        [
          'name' => 'branch_assignment_permission',
          'label' => 'Drupal Permission name for Branch/Country Manager',
          'type' => 'string',
          'description' => 'Enter the Drupal permission name to be used to determine if this user is a Branch/Country Manager. Entering this will allow the identified users access to locations identified as theirs using the Branch CMS User ID integer attribute on the locations.',
          'required' => FALSE,
          'group' => 'Permissions'
        ],
        [
          'name' => 'survey_id',
          'caption' => 'Survey',
          'description' => 'The survey that data will be posted into.',
          'type' => 'select',
          'table' => 'survey',
          'captionField' => 'title',
          'valueField' => 'id',
          'extraParams' => ['orderby' => 'title'],
          'siteSpecific' => TRUE,
          'group' => 'Data Entry Settings'
        ],
        [
          'name' => 'transect_type_term_id',
          'caption' => 'Transect Location type term',
          'description' => 'Select the location type for the Transects.',
          'type' => 'select',
          'table' => 'termlists_term',
          'captionField' => 'term',
          'valueField' => 'id',
          'extraParams' => ['termlist_external_key' => 'indicia:location_types','orderby' => 'title'],
          'required' => TRUE,
          'group' => 'Data Entry Settings'
        ],
        [
          'name' => 'section_type_term_id',
          'caption' => 'Section Location type term',
          'description' => 'Select the location type for the Transect Sections.',
          'type' => 'select',
          'table' => 'termlists_term',
          'captionField' => 'term',
          'valueField' => 'id',
          'extraParams' => ['termlist_external_key' => 'indicia:location_types','orderby' => 'title'],
          'required' => TRUE,
          'group' => 'Data Entry Settings'
        ],
        [
          'name' => 'transect_sample_method_term_id',
          'caption' => 'Transect Sample method term',
          'description' => 'Select the sample method used for samples registered on the Transects.',
          'type' => 'select',
          'table' => 'termlists_term',
          'captionField' => 'term',
          'valueField' => 'id',
          'extraParams' => ['termlist_external_key' => 'indicia:sample_methods','orderby' => 'title'],
          'required' => TRUE,
          'group' => 'Data Entry Settings'
        ],
        [
          'name' => 'section_sample_method_term_id',
          'caption' => 'Section Sample method term',
          'description' => 'Select the sample method used for samples registered on the Transect Sections.',
          'type' => 'select',
          'table' => 'termlists_term',
          'captionField' => 'term',
          'valueField' => 'id',
          'extraParams' => ['termlist_external_key' => 'indicia:sample_methods','orderby' => 'title'],
          'required' => TRUE,
          'group' => 'Data Entry Settings'
        ],
        [
          'name' => 'my_walks_page',
          'caption' => 'Path to My Walks',
          'description' => 'Path used to access the My Walks page after a successful submission. This is the default if not from URL parameter provided.',
          'type' => 'text_input',
          'required' => TRUE,
          'siteSpecific' => TRUE,
          'group' => 'Data Entry Settings'
        ],
        [
          'name' => 'add_species_position',
          'caption' => 'Add species position',
          'description' => 'Is the &quot;Add species&quot; box above or below the species grid it refers to?',
          'type' => 'select',
          'lookupValues' => [
            'above' => 'Above (sticky)',
            'below' => 'Below'
          ],
          'default' => 'below',
          'group' => 'Data Entry Settings',
        ],
        [
          'name' => 'zero_abundance',
          'caption' => 'Allow zero abundance records',
          'description' => 'Will entering a zero in a occurrence count field be treated as a zero abundance record? (The alternative is that the record is deleted)',
          'type' => 'boolean',
          'required' => FALSE,
          'default' => TRUE,
          'group' => 'Data Entry Settings'
        ],
        [
          'name' => 'media_upload_size',
          'caption' => 'Media file upload size',
          'description' => 'Maximum size for an uploaded media file. Human readable: can use 100K or 4M. Default value depends on local configuration.',
          'type' => 'string',
          'required' => FALSE,
          'group' => 'Data Entry Settings'
        ],
        [
          'name' => 'user_locations_filter',
          'caption' => 'User locations filter',
          'description' => 'Should the locations available be filtered to those which the user is linked to, by a multivalue CMS User ID attribute ' .
          'in the location data? If not ticked, then all locations are available.',
          'type' => 'boolean',
          'required' => FALSE,
          'default' => TRUE,
          'group' => 'Location Filtering'
        ],
        [
          'name' => 'confidentialAttrID',
          'caption' => 'Location attribute used to indicate confidential sites',
          'description' => 'A boolean location attribute, set to true if a site is confidential.',
          'type' => 'select',
          'table' => 'location_attribute',
          'captionField' => 'caption',
          'valueField' => 'id',
          'extraParams' => ['orderby' => 'caption'],
          'required' => FALSE,
          'group' => 'Location Filtering'
        ],
        [
          'name' => 'sensitiveAttrID',
          'caption' => 'Location attribute used to indicate sensitive sites',
          'description' => 'A boolean location attribute, set to true if a site is sensitive.',
          'type' => 'select',
          'table' => 'location_attribute',
          'captionField' => 'caption',
          'valueField' => 'id',
          'extraParams' => ['orderby' => 'caption'],
          'required' => FALSE,
          'group' => 'Location Filtering'
        ],
        [
          'name' => 'sensitivityPrecision',
          'caption' => 'Sensitivity Precision',
          'description' => 'Precision to be applied to new occurrences recorded at sensitive sites. Existing occurrences are not changed. A number representing the square size in metres - e.g. enter 1000 for 1km square.',
          'type' => 'int',
          'required' => FALSE,
          'group' => 'Location Filtering'
        ],
        [
          'name' => 'defaults',
          'caption' => 'Default Values',
          'description' => 'Supply default values for each field as required. On each line, enter fieldname=value. ' .
          'For custom attributes, the fieldname is the untranslated caption. For other fields, it is the model and ' .
          'fieldname, e.g. occurrence.record_status. For date fields, use today to dynamically default to today\'s ' .
          'date. NOTE, currently only supports occurrence:record_status and sample:date but will be extended in ' .
          'future.',
          'type' => 'textarea',
          'default' => 'occurrence:record_status=C',
          'group' => 'Data',
          'required' => FALSE
        ],
        [
          'name' => 'branch_label',
          'caption' => 'Branch label',
          'description' => 'Phrase used to describe a branch: e.g. Branch or Region',
          'type' => 'text_input',
          'required' => TRUE,
          'default' => 'Branch',
          'group' => 'Branch settings'
        ],
        [
          'name' => 'branch_type',
          'caption' => 'Branch processing',
          'description' => 'How do we determine the branch for this person/location',
          'type' => 'select',
          'lookupValues' => [
            'none' => 'No branch processing',
            'user' => 'User account field', // Person allocated to a branch using a CMS account field: possible multiple location_ids (UKBMS)
            'site' => 'Site attribute' // Site is allocated to a branch location (EBMS/ABLE)
          ],
          'required' => TRUE,
          'default' => 'none',
          'group' => 'Branch settings',
        ],
        [
          'name' => 'branch_type_key',
          'caption' => 'Branch key',
          'description' => 'Key to use when looking up branch: for User account - the field name, for sites - the site attribute ID',
          'type' => 'text_input',
          'required' => FALSE,
          'siteSpecific' => TRUE,
          'group' => 'Branch settings'
        ],
        [
          'name' => 'species_type',
          'caption' => 'Branch/Common species selection',
          'description' => 'How do we determine a branch specific or common taxon list for this person/location',
          'type' => 'select',
          'lookupValues' => [
            'none' => 'No branch/common species lists',
            // 'taxonlist' => 'Taxon List ID', // A specific taxon list_id per branch, provided below
            // 'subset' => 'Taxon List subset', // configured list of taxon meanings, provided below
            'attribute' => 'Taxon Attribute', // use a tttl attribute, ID provided below
            // 'scheme' => 'Taxon Attribute, Scheme provided', // use a tttl attribute, ID provided by scheme
          ],
          'required' => TRUE,
          'default' => 'none',
          'group' => 'Branch settings',
        ],
        [
          'name' => 'species_attr_values',
          'caption' => 'Species attribute values',
          'description' => 'Which are the values of the species attribute that indicate it should be included in the branch or common lists. Pipe (|) separated.',
          'type' => 'text_input',
          'required' => FALSE,
          'group' => 'Branch settings'
        ],
        [
          'name' => 'branch_settings',
          'caption' => 'Branch/country/scheme specific settings',
          'description' => 'Branch/country/scheme specific settings',
          'type' => 'jsonwidget',
          'required' => TRUE,
          'group' => 'Branch settings',
          'schema' =>
'{
  "type":"seq",
  "title":"Branch/country/scheme specific configuration list",
  "sequence":
  [
    {
      "type":"map",
      "title":"Branch/country/scheme specific setting",
      "mapping": {
        "branch": {"type":"str","title":"Branch","desc":"Branch/country/scheme ID, zero for default settings","required":true},
        "sample_gdpr_message_position": {
          "type":"map",
          "title":"Main GDPR position",
          "desc":"Position of GDPR message on main page.",
          "mapping": {
            "top": {"type":"bool","title":"Top"},
            "bottom": {"type":"bool","title":"Bottom"}
          }
        },
        "occurrence_gdpr_message_position": {
          "type":"map",
          "title":"Occurrence GDPR position",
          "desc":"Position of GDPR message on occurrence page.",
          "mapping": {
            "top": {"type":"bool","title":"Top"},
            "bottom": {"type":"bool","title":"Bottom"}
          }
        },
        "gdpr_link": {"type":"str","title":"GDPR Link","desc":"URL to be used in the GDPR message for licence or privacy"},
        "super_sample_images": {"type":"bool","title":"Transect level images"},
        "sub_sample_images": {"type":"bool","title":"Section level images"},
        "occurrence_images": {"type":"bool","title":"Occurrence images"},
        "occurrence_comments": {"type":"bool","title":"Occurrence comments"},
        "occurrence_attributes": {
          "type":"seq",
          "title":"Occurrence attributes",
          "sequence": [{"type":"str","desc":"ID of the Occurrence Attribute."}]
        },
        "global_subsample_attributes": {
          "type":"seq",
          "title":"Global subsample attributes",
          "desc":"Subsample attributes, independent of species grids",
          "sequence": [{"type":"str","desc":"ID of the Subsample Attribute."}]
        },
        "species_supersample_attributes":{
          "type":"seq",
          "title":"Species supersample attributes",
          "desc":"Supersample attributes associated with the species grid",
          "sequence":[
            {
              "type":"map",
              "title":"Attribute details",
              "mapping":{
                "id":{
                  "type":"str",
                  "title":"Attribute ID"
                },
                "grid":{
                  "type":"str",
                  "title":"Grid index"
                },
                "hideFront":{
                  "type":"bool",
                  "title":"Hide from main page"
                },
                "hideGridValues":{
                  "type":"str",
                  "title":"Hide Grid values"
                }
              }
            }
          ]
        },
        "common_species_attr_id":{
          "type":"str",
          "title":"Common Taxa Attribute ID",
          "desc":"When the Branch species selection is Taxon Attribute, this is the attribute to use for this branch."
        },
        "branch_species_attr_id":{
          "type":"str",
          "title":"Branch Taxa Attribute ID",
          "desc":"When the Branch species selection is Taxon Attribute, this is the attribute to use for this branch. Do not use on branch zero."
        },
      }
    }
  ]
}'
        ],
        [
          'name' => 'attribute_configuration',
          'caption' => 'Custom configuration for attributes',
          'description' => 'Custom configuration for attributes',
          'type' => 'jsonwidget',
          'required' => FALSE,
          'group' => 'Attributes',
          'schema' =>
'{
  "type":"seq",
  "title":"Attribute Custom Configuration List",
  "sequence":
  [
    {
      "type":"map",
      "title":"Attribute",
      "mapping": {
        "id": {"type":"str","desc":"ID of the Sample Attribute."},
        "permission": {"type":"str","title":"Visibility permission","desc":"Permission required to allow access to the attribute. Attribute must not be defined as required on the warehouse."},
        "beforeDate": {"type":"bool","title":"Insert before Date","desc":"Whether this attribute appears before the date field on the main page."},
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
        ],
        [
          'name' => 'custom_attribute_options',
          'caption' => 'Options for custom attributes',
          'description' => 'A list of additional options to pass through to custom attributes, one per line. Each ' .
          'option should be specified as the attribute name followed by | then the option name, followed by = then ' .
          'the value. For example, smpAttr:1|class=control-width-5.',
          'type' => 'textarea',
          'required' => FALSE,
          'siteSpecific' => TRUE,
          'group' => 'Attributes'
        ],
        [
          'name' => 'interacting_sample_attributes',
          'caption' => 'Interacting sample attributes',
          'description' => 'Comma separated list of subsample attribute ids, which are percentage values which ' .
          'interact - entering one as 75 will cause the other to be set to 25.',
          'type' => 'text_input',
          'required' => FALSE,
          'siteSpecific' => TRUE,
          'group' => 'Attributes'
        ],
        [
          'name' => 'taxon_column',
          'caption' => 'Display Taxon field',
          'description' => 'When displaying a taxon, choose what to use.',
          'type' => 'select',
          'lookupValues' => [
            'taxon' => 'Common Name',
            'preferred_taxon' => 'Preferred Taxon (usually Latin)'
          ],
          'required' => TRUE,
          'default' => 'taxon',
          'group' => 'Species Definitions',
        ],

        [
          'name' => 'server_range_validation',
          'caption' => 'Server range validation',
          'description' => 'Include range checks defined on the warehouse',
          'type' => 'checkbox',
          'required' => FALSE,
          'group' => 'Species Definitions'
        ],
        [
          'name' => 'occurrenceValueRangeAttributeID',
          'caption' => 'Occurrence attribute used to value range checks',
          'description' => 'A numeric occurrence attribute.',
          'type' => 'select',
          'table' => 'occurrence_attribute',
          'captionField' => 'caption',
          'valueField' => 'id',
          'extraParams' => ['orderby' => 'caption'],
          'required' => FALSE,
          'group' => 'Species Definitions'
        ],
        [
          'name' => 'walkRangeAttrID',
          'caption' => 'Walk Range Taxon attribute',
          'description' => 'A numeric location attribute, holds the limit for the total number of a particular taxon on a walk (multiple sections)',
          'type' => 'select',
          'table' => 'taxa_taxon_list_attribute',
          'captionField' => 'caption',
          'valueField' => 'id',
          'extraParams' => ['orderby' => 'caption'],
          'required' => FALSE,
          'group' => 'Species Definitions'
        ],
        [
          'name' => 'sectionRangeAttrID',
          'caption' => 'Section Range Taxon attribute',
          'description' => 'A numeric location attribute, holds the limit for a particular taxon on a single section',
          'type' => 'select',
          'table' => 'taxa_taxon_list_attribute',
          'captionField' => 'caption',
          'valueField' => 'id',
          'extraParams' => ['orderby' => 'caption'],
          'required' => FALSE,
          'group' => 'Species Definitions'
        ],
        [
          'name' => 'species_tab_definition',
          'caption' => 'Species tab definitions',
          'description' => 'Define the configurations for each species tab.',
          'type' => 'jsonwidget',
          'required' => FALSE,
          'group' => 'Species Definitions',
          'schema' =>
'{
  "type":"seq",
  "title":"Species tab definitions",
  "sequence":
  [
    {
      "type":"map",
      "title":"Species tab definition",
      "mapping": {
        "title": {"type":"str","title":"Title","desc":"Text to be used as the title of this species tab"},
        "taxon_list_id": {"type":"int","title":"Taxon list ID","desc":"The species list used to populate the grid on the main grid when All Species is selected. Also used to drive the autocomplete when other options selected."},
        "taxon_min_rank": {"type":"int","title":"Min Rank","desc":"Some species lists implement taxon ranks. These ranks are sorted, and have a sort order, where the most general have a low value, and the most specific have a high value. Setting this field allows things like Order classifications to be excluded from the Pick List."},
        "taxon_filter_field": {"type":"str","title":"Field used to filter taxa","desc":"If you want to allow recording for just part of the selected All Species List, then enter which field you will use to specify the filter by.", "enum":["taxon", "taxon_meaning_id", "taxon_group"]},
        "taxon_filter": {
          "type":"seq",
          "title":"Taxon filter values",
          "desc":"When filtering the list of available taxa, taxa will not be available for recording unless they match one of the values you input in this list. E.g. enter a list of taxon group titles if you are filtering by taxon group.",
          "sequence":[{"type":"str","title":"Filter value"}]
        },
        "preselect_list": {"type":"str","title":"Preselect list","desc":"Preselect which species list to populate the species grid with (branch => Branch specific species list, full => Full species list, common => Common species list, here => Species recorded at this location (default), mine => Species recorded by user", "enum":["branch", "full", "common", "here", "mine"]},
        "sort_order": {
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
        },
        "species_list": {
          "type":"map",
          "title":"Species List Options",
          "desc":"Which preset species lists are available. The branch list is determined by the presence of taxa with the branch attribute set (if any provided). The common list is similarly defined, though optionally at a global level, so can be disabled here if required.",
          "mapping": {
            "full_enabled": {"type":"bool","title":"All species Enabled"},
            "common_enabled": {"type":"bool","title":"Common species Enabled"},
            "mine_enabled": {"type":"bool","title":"My Species Enabled"},
            "here_enabled": {"type":"bool","title":"Here Enabled"}
          }
        }
      }
    }
  ]
}'
        ]
      ];
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
  public static function get_form($args, $nid, $response=NULL)
  {

    data_entry_helper::add_resource('jquery_form');
    data_entry_helper::add_resource('jquery_ui');
    data_entry_helper::add_resource('autocomplete');
    
    self::setupTranslations();
    self::$timings = 'Form Start '.date('Y-m-d H:i:s');

    $r = (isset($response['error']) ? data_entry_helper::dump_errors($response) : '');
    if (!function_exists('hostsite_module_exists') || !hostsite_module_exists('iform_ajaxproxy')) {
      throw new exception('This form must be used in Drupal with the Indicia AJAX Proxy module enabled.');
    }
    if (!hostsite_module_exists('easy_login')) {
      throw new exception('This form must be used in Drupal with the Easy Login module enabled.');
    }
    self::$userId = hostsite_get_user_field('indicia_user_id');
    // self::$userId = 1; // TODO replace before commit
    if (empty(self::$userId)) {
      throw new exception(self::$translated['user_identification_error']);
    }

    self::$auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);

    $existing = FALSE; // Does sample already exist - used for performance optimisation
    self::$sampleId = NULL;
    if (isset($_POST['sample:id']) && intval($_POST['sample:id']) == $_POST['sample:id']) {
      // have just posted an edit to the existing parent sample: either good or error
      self::$sampleId = $_POST['sample:id'];
      $existing = TRUE;
    }
    else {
      if (isset($response['outer_id'])) {
        // have just successfully posted a new parent sample
        self::$sampleId = $response['outer_id'];
      }
      else {
        // either a clean call to create a new sample or edit an existing one, or an error POST for a new sample
        if (!empty($_GET['sample_id']) && intval($_GET['sample_id']) == $_GET['sample_id']) {
          self::$sampleId = $_GET['sample_id'];
          $existing = TRUE;
        }
      }
    }

    self::$locationID = NULL;
    if (!empty(self::$sampleId)) {
      // if in error, the $entity_to_load should already be filled in.
      if (!isset($response['error'])) {
        data_entry_helper::load_existing_record(self::$auth['read'], 'sample', self::$sampleId , 'detail', FALSE, TRUE);
      }
      self::$locationID = data_entry_helper::$entity_to_load['sample:location_id'];
    }
    else {
      // new sample
      // if in error, the $entity_to_load should already be filled in.
      if (!isset($response['error'])) {
        self::$locationID = !empty($_GET['site']) && intval($_GET['site']) == $_GET['site'] ? $_GET['site'] : NULL;
      }
      else {
        self::$locationID = !empty($_POST['sample:location_id']) && intval($_POST['sample:location_id']) == $_POST['sample:location_id'] ? $_POST['sample:location_id'] : NULL;
      }
    }
           
    // TODO protect against injection
    $parsedURL = parse_url(isset($_REQUEST['from']) ? $_REQUEST['from'] : $args['my_walks_page']);
    $scheme   = isset($parsedURL['scheme']) ? $parsedURL['scheme'] . '://' : '';
    $host     = isset($parsedURL['host']) ? $parsedURL['host'] : '';
    $path     = isset($parsedURL['path']) ? $parsedURL['path'] : '';
    $query    = isset($parsedURL['query']) ? $parsedURL['query'] : '';
    $fragment = isset($parsedURL['fragment']) ? $parsedURL['fragment'] : ''; 
    $args['return_page'] = hostsite_get_url("$scheme$host$path", ['query' => $query, 'fragment' => $fragment, 'absolute' => TRUE]);

    data_entry_helper::$javascript .= 'indiciaData.nid = "' . $nid . '";' . PHP_EOL;
    data_entry_helper::$javascript .= 'indiciaData.ajaxUrl = "' .
      hostsite_get_url('iform/ajax/ukbms_sectioned_transects_input_sample_2') . '";' . PHP_EOL;

    // TODO protect against injection
    if (((isset($_REQUEST['page']) && $_REQUEST['page'] === 'mainSample') ||
            isset($_REQUEST['occurrences'])) &&
          !isset(data_entry_helper::$validation_errors) &&
          !isset($response['error'])) {
      // we have just successfully saved the sample page, so move on to the occurrences list,
      return $r . self::getOccurrencesForm($args, $nid, $response, $existing);
    }
    else {
      return $r . self::getSampleForm($args, $nid, $response, $existing);
    }
  }


  private static function setupTranslations()
  {
    // All configuration errors are left as English only: they should not occur during normal operation by normal users
    self::$translations = [
      // First group do not have PHP params, so can be used straight away.
      // This includes the translations which are used in JS
      "yes" => "Yes",
      "no" => "No",
      "date" => "Date",
      "notes" => "Notes",
      "notes_help" => "Use this space to input comments about this week's walk.",
      "next" => "Next",
      "cancel" => "Cancel",
      "delete" => "Delete",
      "user_identification_error" => "ERROR : Easy Login active but could not identify user.",
      "sample_media" => "Sample photos and media",
      "transect" => "Transect",
      "select_transect" => "Select Transect",
      "please_select" => "Please select",
      "confirm_delete_walk" => "Are you sure you want to delete this walk?",
      // second group have params, so translation can't be used straight away.
      "gdpr_message" => "By submitting these records you confirm that they contain data that you have collected, " .
        "give permission for the records to be used for research, education and public information, " .
        "and to be made generally available for re-use for any other legal purpose under the terms of the creative " .
        "commons license '{1}', and agree that your name will be associated with the record. " .
        "National co-ordinators are responsible for sharing data for their region."
    ];
    foreach (self::$translations as $index => $value) {
      self::$translated[$index] = lang::get($value, " ");
    }
  }
  
  private static function mergeSettings($settings, $toMerge) {
    if (!is_array($settings)) {
      return $toMerge;
    }
    $newSettings = [];
    foreach ($settings as $key => $value) {
      if (isset($toMerge[$key])) {
        $newSettings[$key] = self::mergeSettings($settings[$key], $toMerge[$key]);
      }
      else {
        $newSettings[$key] = $settings[$key];
      }
    }
    foreach ($toMerge as $key => $value) {
      if (!isset($settings[$key])) {
        $newSettings[$key] = $toMerge[$key];
      }
    }
    return $newSettings;
  }

  private static function getBranchSettings($args, $occurrenceAttributes, $transectSectionAttributes) {
    // First populate with the baseline settings
    $settings = [
      "sample_gdpr_message_position" => ["top" => FALSE, "bottom" => TRUE],
      "occurrence_gdpr_message_position" => ["top" => FALSE, "bottom" => TRUE],
      "gdpr_link" => "http://www.nationalarchives.gov.uk/doc/open-government-licence/",
      "super_sample_images" => FALSE,
      "sub_sample_images" => FALSE,
      "occurrence_images" => FALSE,
      "occurrence_comments" => FALSE,
      "occurrence_attributes" => array_keys($occurrenceAttributes), // NB order not set.
      "global_subsample_attributes" => array_keys($transectSectionAttributes), // NB order not set.
      "species_supersample_attributes" => [], // NB order not set.
      "common_species_attr_id" => FALSE,
      "branch" => FALSE,
      "branch_species_attr_id" => FALSE
    ];
    // Overlay with the form config default settings
    $branchConfiguration = json_decode($args['branch_settings'], TRUE);
    foreach ($branchConfiguration as $branch) {
      if (empty($branch['branch'])) {
        if (isset($branch['occurrence_attributes'])) {
          unset($settings['occurrence_attributes']); // Do not merge - overwrite
        }
        if (isset($branch['global_subsample_attributes'])) {
          unset($settings['global_subsample_attributes']); // Do not merge - overwrite
        }
        if (isset($branch['species_supersample_attributes'])) {
          unset($settings['species_supersample_attributes']); // Do not merge - overwrite
        }
        $settings = self::mergeSettings($settings, $branch); // Initial values: branch zero
        unset($settings['branch_species_attr_id']);
      }
    }
    $settings['branch'] = FALSE;
    // Determine which branch we are a part of
    if (empty($args['branch_type']) || empty($args['branch_type_key'])) {
      return $settings;
    }
    switch ($args['branch_type']) {
      case 'user':
        $branchToUse = hostsite_get_user_field($args['branch_type_key']);
        break;
      case 'site':
        if (empty(self::$locationID)) {
          return $settings;
        }
        self::$timings .= '(1) Start '.date('Y-m-d H:i:s'); // OK
        $branchToUse = data_entry_helper::get_population_data([
          'table' => 'location_attribute_value',
          'extraParams' => self::$auth['read'] + ['location_id' => self::$locationID, 'location_attribute_id' => $args['branch_type_key']],
//          'nocache' => TRUE
        ]);
        if (count($branchToUse) < 1) {
          return $settings;
        }
        $branchToUse = $branchToUse[0]['value'];
        break;
      case 'none':
      default:
        return $settings;
    }
    // If part of a branch, overlay the branch's settings.
    if (empty($branchToUse)) {
      return $settings;
    }
    foreach ($branchConfiguration as $branch) {
      if (!empty($branch['branch']) && $branch['branch'] == $branchToUse) {
        if (isset($branch['occurrence_attributes'])) {
          unset($settings['occurrence_attributes']); // Do not merge - overwrite
        }
        if (isset($branch['global_subsample_attributes'])) {
          unset($settings['global_subsample_attributes']); // Do not merge - overwrite
        }
        if (isset($branch['species_supersample_attributes'])) {
          unset($settings['species_supersample_attributes']); // Do not merge - overwrite
        }
        $settings = self::mergeSettings($settings, $branch);
      }
    }
    return $settings;
  }

  /**
   * Utility method to convert a memory size string (e.g. 1K, 1M) into the number of bytes.
   * Copied from data entry helper
   *
   * @param string $size Size string to convert. Valid suffixes as G (gigabytes), M (megabytes), K (kilobytes) or nothing.
   * @return integer Number of bytes.
   */
  private static function convert_to_bytes($size) {
      // Make the size into a power of 1024
      switch (substr($size, -1))
      {
          case 'G': $size = intval($size) * pow(1024, 3); break;
          case 'M': $size = intval($size) * pow(1024, 2); break;
          case 'K': $size = intval($size) * pow(1024, 1); break;
          default:  $size = intval($size);                break;
      }
      return $size;
  }

  /**************************************************************
   * The following functions are specific to the core Sample Page
   **************************************************************/
  
  private static function getSampleForm($args, $nid, $response) {

    global $indicia_templates;

    $settings = self::getBranchSettings($args, [], []);
    
    $formOptions = [
      'userID' => self::$userId,
      'surveyID' => $args['survey_id'],
      'interactingSampleAttributes' =>
        (!empty($args['interacting_sample_attributes']) &&
          count(explode(',', $args['interacting_sample_attributes'])) === 2) ?
        explode(',', $args['interacting_sample_attributes']) :
        [],
      'sites' => [],
      'langStrings' => [
          'startTimeRange' => 'Warning: Start time is outside expected hours of 08:00 to 18:00',
          'startTimeAfter' => 'Warning: Start time is after End time',
          'endTimeRange' => 'Warning: End time is outside expected hours of 08:00 to 18:00',
          'endTimeBefore' => 'Warning: End time is before Start time',
      ]
    ];

    $attributes = data_entry_helper::getAttributes([
      'id' => self::$sampleId,
      'valuetable' => 'sample_attribute_value',
      'attrtable' => 'sample_attribute',
      'key' => 'sample_id',
      'fieldprefix' => 'smpAttr',
      'extraParams' => self::$auth['read'],
      'survey_id' => $args['survey_id'],
      'sample_method_id' => $args['transect_sample_method_term_id']
    ]);

    // Check for required flag for attributes on the main page: It can't be set on any attribute that is being removed
    // from the front page for any branch for display on the occurrence page.
    // Do this for all branches, not just this one.
    $attributesToCheck = [];
    $branchConfiguration = json_decode($args['branch_settings'], TRUE);
    foreach ($branchConfiguration as $branch) {
      if (!empty($branch['species_supersample_attributes'])) {
        foreach ($branch['species_supersample_attributes'] as $attrDefinition) {
          if (!empty($attrDefinition['id']) && !empty($attrDefinition['hideFront'])) {
            $attributesToCheck[] = $attrDefinition['id'];
          }
        }
      }
    }    
    foreach ($attributesToCheck as $attributeId) {
      $attribute = $attributes[$attributeId];
      $rules = explode("\n", $attribute['validation_rules']);
      $isRequired = array_search('required', $rules);
      if($isRequired !== FALSE) { // required validation must be switched off on the warehouse
        throw new exception("Form Config error: warehouse enabled required validation detected on Transect sample attribute " . $attributeId);
      }
    }

    if (!empty($settings['species_supersample_attributes'])) {
      foreach ($settings['species_supersample_attributes'] as $attrDefinition) {
        if (!empty($attrDefinition['id']) && !empty($attrDefinition['hideFront'])) {
          unset($attributes[$attrDefinition['id']]);
        }
      }
    }
    
    $attributeConfiguration = (!empty($args['attribute_configuration']) ? json_decode($args['attribute_configuration'], TRUE) : []);
    foreach ($attributes as $key => $attribute) {
      $rules = explode("\n", $attribute['validation_rules']);
      foreach ($attributeConfiguration as $attrConfig) {
        if ($attrConfig['id'] == $key) {
          if (!empty($attrConfig['permission']) && !hostsite_user_has_permission($attrConfig['permission'])) {
            unset($attributes[$key]); // remove the attribute if we dont have permission to see it
            continue 2;
          }
          if (!empty($attrConfig['required']) && !empty($attrConfig['required']['main_page'])) {
            $rules[] = 'required';
            $attributes[$key]['validation_rules'] = implode("\n", array_unique($rules));
          }
        }
      }
    }
    
    // we pass through the read auth. This makes it possible for the get_submission method to authorise against the warehouse
    // without an additional (expensive) warehouse call, so it can get location details.
    $pageHtml = '<form method="post" id="sample">' .
      self::$auth['write'] .
      '<input type="hidden" name="page" value="mainSample"/>' .
      '<input type="hidden" name="from" value="' . $args['return_page'] . '"/>' .
      '<input type="hidden" name="read_nonce" value="' . self::$auth['read']['nonce'] . '"/>' .
      '<input type="hidden" name="read_auth_token" value="' . self::$auth['read']['auth_token'] . '"/>' .
      '<input type="hidden" name="website_id" value="' . $args['website_id'] . '"/>' .
      '<input type="hidden" name="sample:survey_id" value="' . $args['survey_id'] . '"/>' .
      '<input type="hidden" name="sample:sample_method_id" value="' . $args['transect_sample_method_term_id'] . '" />' .
      get_user_profile_hidden_inputs($attributes, $args, isset(data_entry_helper::$entity_to_load['sample:id']), self::$auth['read']);
    
    if (!empty(data_entry_helper::$entity_to_load['sample:id'])) {
      $pageHtml .= '<input type="hidden" name="sample:id" value="' . data_entry_helper::$entity_to_load['sample:id'] . '"/>';
    }

    // GDPR message: positioning is potentially branch/country/scheme dependent
    if ($settings['sample_gdpr_message_position']['top']) {
        $pageHtml .= str_replace('{1}',
            '<a href="' . $settings['gdpr_link'] .'">'  . $settings['gdpr_link'] .'</a>',
            '<p class="gdpr">' .  lang::get(self::$translations['gdpr_message']) . '</p>');
        // have to do this this way so response leaves in the link
    }
    // Container div already exists
    $pageHtml .= '<div class="row"><div class="col-sm-6">' . self::getLocationControl($args, $formOptions) . '</div></div>';

    $pageHtml .= '<div class="row">';
    $oldControlWrapTemplate = $indicia_templates['controlWrap'];
    $indicia_templates['controlWrap'] = '<div id="ctrl-wrap-{id}" class="form-group ctrl-wrap col-sm-6">{control}</div>';
    
    // first deal with any attributes before the date. we ignore the structure blocks
    $ctrlOptions = ['extraParams' => self::$auth['read']];
    $attrSpecificOptions= (!empty($args['custom_attribute_options']) ? get_attr_options_array_with_user_data($args['custom_attribute_options']) : []);
    foreach($attrSpecificOptions as $attrID => $attrSpecificOption) {
      if (!empty($attrSpecificOption['label'])) {
        $attrSpecificOptions[$attrID]['label'] = lang::get($attrSpecificOptions[$attrID]['label']);
      }
    }
    $r = '';
    foreach ($attributes as $key => $attribute) {
      foreach ($attributeConfiguration as $attrConfig) {
        if ($attrConfig['id'] == $key && !empty($attrConfig['beforeDate'])) {
          $options = $ctrlOptions + get_attr_validation($attribute, $args);
          if (isset($attrSpecificOptions[$attribute['id']])) {
            $options = array_merge($options, $attrSpecificOptions[$attribute['id']]);
          }
          $pageHtml .= data_entry_helper::outputAttribute($attribute, $options);
          $attributes[$key]['handled']=true;
        }
      }
    }

    // Date entry field.
    if (isset(data_entry_helper::$entity_to_load['sample:date']) &&
          preg_match('/^(\d{4})/', data_entry_helper::$entity_to_load['sample:date'])) {
      // Date has 4 digit year first (ISO style) - convert date to expected output format
      // @todo The date format should be a global configurable option. It should also be applied to reloading of custom date attributes.
      $d = new DateTime(data_entry_helper::$entity_to_load['sample:date']);
      data_entry_helper::$entity_to_load['sample:date'] = $d->format('d/m/Y');
    }
    // TODO protect against injection
    if (!isset(data_entry_helper::$entity_to_load['sample:date']) && isset($_GET['date'])) {
      // if handed in from URL, user can't change the date
      $pageHtml .= '<div class="col-sm-6"><input type="hidden" name="sample:date" value="' . $_GET['date'] . '"/>' .
        '<label>' . self::$translated['date'] . ':</label> <div class="value-label">' . $_GET['date'] . '</div></div>';
    }
    else {
      $pageHtml .= data_entry_helper::date_picker(['label' => self::$translated['date'], 'fieldname' => 'sample:date']);
    }

    $pageHtml .= '</div><div class="row">';
    // sample attributes
    $pageHtml .= get_attribute_html($attributes, $args, ['extraParams' => self::$auth['read']], NULL, $attrSpecificOptions);
    $pageHtml .= '</div>';

    $indicia_templates['controlWrap'] = $oldControlWrapTemplate;

    // comment
    $pageHtml .= data_entry_helper::textarea([
      'fieldname' => 'sample:comment',
      'label' => self::$translated['notes'],
      'helpText' => self::$translated['notes_help']
    ]);
    
    // images
    if ($settings["super_sample_images"]) {
      $maxUploadSize = !empty($args['media_upload_size']) ? $args['media_upload_size'] :
          (isset(helper_base::$maxUploadSize) ? helper_base::$maxUploadSize : '4M');
      $maxUploadSize = self::convert_to_bytes($maxUploadSize);
      $pageHtml .= data_entry_helper::file_box([
        'table' => 'sample_medium',
        'caption' => self::$translated["sample_media"],
        'readAuth' => self::$auth['read'],
        'msgFileTooBig' => lang::get('file too big for warehouse') . ' ' . lang::get('[{1} bytes]', $maxUploadSize),
        'maxUploadSize' => $maxUploadSize,
      ]);
    }
    
    // GDPR message: positioning is potentially branch/country/scheme dependent
    if ($settings['sample_gdpr_message_position']['bottom']) {
      $pageHtml .= str_replace('{1}',
          '<a href="' . $settings['gdpr_link'] .'">'  . $settings['gdpr_link'] .'</a>',
          '<p class="gdpr">' .  lang::get(self::$translations['gdpr_message']) . '</p>');
      // have to do this this way so response leaves in the link
      
    }
    
    // Buttons
    $pageHtml .= '<div><input type="submit" value="' . self::$translated['next'] . '" class="btn btn-primary btn-lg" />' .
      '<a href="'.$args['return_page'].'" class="btn btn-warning btn-lg">' . self::$translated['cancel'] . '</a>';
    if (isset(data_entry_helper::$entity_to_load['sample:id'])) {
      $pageHtml .= '<button id="delete-button" type="button" class="btn btn-danger btn-lg" />' . self::$translated['delete'] . '</button>';
    }
    
    $pageHtml .= '</div></form>';

    // Extra form to delete sample.
    $pageHtml .= self::deleteSampleForm(self::$auth, $args, $formOptions);

    // Find Recorder Name attribute for use with autocomplete.
    foreach ($attributes as $attrID => $attr) {
      if (strcasecmp('Recorder Name', $attr["untranslatedCaption"]) == 0) {
        $formOptions['recorderNameAttrID'] = $attrID; // will be undefined if not present.
      } 
      // Want to convert the time fields to html5 type=time
      // Don't want to use the time control type as doesn't meet customer requirements.
      // Am fully aware that functionality is very browser dependant.
      elseif (strcasecmp('Start Time', $attr["untranslatedCaption"]) == 0 ||
          strcasecmp('Start Time (hh:mm)', $attr["untranslatedCaption"]) == 0) {
        $formOptions['startTimeAttrID'] = $attrID; // will be undefined if not present.
        $safeId = str_replace(':', '\\\\:', $attr["id"]);
        data_entry_helper::$javascript .= "$('#".$safeId."').prop('type', 'time').prop('placeholder', '__:__');\n";
      } elseif (strcasecmp('End Time', $attr["untranslatedCaption"]) == 0 ||
          strcasecmp('End Time (hh:mm)', $attr["untranslatedCaption"]) == 0) {
        $formOptions['endTimeAttrID'] = $attrID; // will be undefined if not present.
        $safeId = str_replace(':', '\\\\:', $attr["id"]);
        data_entry_helper::$javascript .= "$('#".$safeId."').prop('type', 'time').prop('placeholder', '__:__');\n";
      }
      
    }
    
    data_entry_helper::$javascript .= "\nsetUpSamplesForm(".json_encode((object)$formOptions).");\n";
    data_entry_helper::enable_validation('sample');

    return $pageHtml;
  }

  
  private static function getLocationControl($args, &$formOptions)
  {
    if (!empty(self::$locationID)) {
      self::$timings .= '(2) Start '.date('Y-m-d H:i:s');
      $site = data_entry_helper::get_population_data([
        'table' => 'location',
        'extraParams' => self::$auth['read'] +
        [
          'view' => 'detail',
          'id' => self::$locationID,
          'deleted' => 'f',
          'location_type_id' => $args['transect_type_term_id']
        ]
      ]
        );
      if (count($site) !== 1) {
        throw new exception("Could not identify specific site");
      }
      $site = $site[0];
      if (in_array($site['centroid_sref_system'], ['osgb','osie'])) {
        $site['centroid_sref_system'] = strtoupper($site['centroid_sref_system']);
      }
      $locationHTML = '<input type="hidden" name="sample:location_id" value="' . self::$locationID .  '"/>' .
        '<input type="hidden" name="sample:entered_sref" value="' . $site['centroid_sref']  .'"/>' .
        '<input type="hidden" name="sample:entered_sref_system" value="' . $site['centroid_sref_system'] . '"/>';
    }
    else {
      $locationHTML = '<input type="hidden" name="sample:entered_sref" value="" id="entered_sref"/>' .
        '<input type="hidden" name="sample:entered_sref_system" value="" id="entered_sref_system"/>';
      // sref values for the sample will be populated automatically when the submission is built.
    }
    
    // location control
    if (self::$locationID && (isset(data_entry_helper::$entity_to_load['sample:id']) || isset($_GET['site']))) {
      // for reload of existing data or if the site is specified in the URL, user can't change the transect.
      $locationHTML .= '<label>' . self::$translated['transect'] . ':</label> <span class="value-label">' . $site['name'] . '</span><br/>';
    } else {
      // Output only the locations for this website and transect type. Note we load both transects and sections, just so that
      // we always use the same warehouse call and therefore it uses the cache.
      // Allocation of locations to user is done by the CMS User Attribute on the Location record.
      // @todo convert this to use the Indicia user ID.
      $siteParams = self::$auth['read'] +
      [
        'website_id' => $args['website_id'],
        'location_type_id' => $args['transect_type_term_id']
      ];
      if ((!isset($args['user_locations_filter']) || $args['user_locations_filter']) &&
          (!isset($args['managerPermission']) || !hostsite_user_has_permission($args['managerPermission']))) {
        $siteParams += ['locattrs' => 'CMS User ID', 'attr_location_cms_user_id' => hostsite_get_user_field('id')];
      } else {
        $siteParams += ['locattrs' => ''];
      }
      self::$timings .= '(3) Start '.date('Y-m-d H:i:s');
      $availableSites = data_entry_helper::get_population_data([
        'report' => 'library/locations/locations_list',
        'extraParams' => $siteParams,
//        'nocache' => TRUE
      ]);
      // convert the report data to an array for the lookup, plus one to pass to the JS so it can keep the hidden sref fields updated
      $sitesLookup = [];
      $sitesJs = [];
      foreach ($availableSites as $site) {
        $sitesLookup[$site['location_id']] = $site['name'];
        $sitesJs[$site['location_id']] = [
          'centroid_sref' => $site['centroid_sref'],
          'centroid_sref_system' => $site['centroid_sref_system']
        ];
      }
      // bolt in branch locations. Don't assume that branch list is superset of normal sites list.
      // Only need to do if not a manager - they have already fetched the full list anyway.
      if (isset($args['branch_assignment_permission']) &&
          hostsite_user_has_permission($args['branch_assignment_permission']) &&
          $siteParams['locattrs']!='') {
        $siteParams['locattrs'] = 'Branch CMS User ID';
        $siteParams['attr_location_branch_cms_user_id'] = hostsite_get_user_field('id');
        unset($siteParams['attr_location_cms_user_id']);
        self::$timings .= '(4) Start '.date('Y-m-d H:i:s');
        $availableSites = data_entry_helper::get_population_data([
          'report' => 'library/locations/locations_list',
          'extraParams' => $siteParams,
//          'nocache' => TRUE
        ]);
        foreach ($availableSites as $site) {
          $sitesLookup[$site['location_id']] = $site['name']; // note no duplicates if assigned as user and branch
          $sitesJs[$site['location_id']] = $site;
        }
        natcasesort($sitesLookup); // merge into original list in alphabetic order.
      }
      $formOptions['sites'] = $sitesJs;
      $options = [
        'label' => self::$translated['select_transect'],
        'validation' => ['required'],
        'blankText' => self::$translated['please_select'],
        'lookupValues' => $sitesLookup,
      ];
      if (self::$locationID) {
        $options['default'] = self::$locationID;
      }
      $locationHTML .= data_entry_helper::location_select($options);
    }
    return $locationHTML;
  }

  private static function deleteSampleForm($auth, $args, &$formOptions)
  {
    $formOptions['deleteConfirm'] = self::$translated['confirm_delete_walk'];
    if (isset(data_entry_helper::$entity_to_load['sample:id'])) {
      // note we only require bare minimum in order to flag a sample as deleted.
      return '<form method="post" id="delete-form" style="display: none;">' .
        $auth['write'] .
        '<input type="hidden" name="page" value="delete"/>' .
        '<input type="hidden" name="website_id" value="' . $args['website_id'] . '"/>' .
        '<input type="hidden" name="sample:id" value="' . data_entry_helper::$entity_to_load['sample:id'] . '"/>' .
        '<input type="hidden" name="sample:deleted" value="t"/>' .
        '</form>';
    }
  }

  /******************************************************************
   * The following functions are specific to the core Occurrence Page
   ******************************************************************/
  
  private static function getOccurrencesForm($args, $nid, $response, $existing) {
    global $indicia_templates;

    // remove the ctrlWrap as it complicates the grid & JavaScript unnecessarily
    $oldCtrlWrapTemplate = $indicia_templates['controlWrap'];
    $indicia_templates['controlWrap'] = '{control}';

    $transectAttributes = self::getTransectAttributes($args);
    $sectionAttributes = self::getSectionAttributes($args);
    $occurrenceAttributes = self::getOccurrenceAttributes($args);
    $settings = self::getBranchSettings($args, $occurrenceAttributes, $sectionAttributes);
    
	// Check for required flag for subsample attributes
    foreach ($sectionAttributes as $key => $attribute) {
      $rules = explode("\n", $attribute['validation_rules']);
      $isRequired = array_search('required', $rules);
      if($isRequired !== FALSE) { // required validation must be switched off on the warehouse
        throw new exception("Form Config error: warehouse enabled required validation detected on Section sample attribute " . $key);
      }
    }
    // Check for required flag for occurrence attributes
    foreach ($occurrenceAttributes as $key => $attribute) {
      $rules = explode("\n", $attribute['validation_rules']);
      $isRequired = array_search('required', $rules);
      if($isRequired !== FALSE) { // required validation must be switched off on the warehouse
        throw new exception("Form Config error: warehouse enabled required validation detected on occurrence attribute " . $key);
      }
    }

    $subSampleList = [];
    $subSamplesByCode = [];
    
    $subSamples = self::getSubSamples($args, self::$sampleId, $sectionAttributes);
    
    self::transcribeSubSamples($subSamples, $subSampleList, $subSamplesByCode);
    
    $maxUploadSize = !empty($args['media_upload_size']) ? $args['media_upload_size'] :
        (isset(helper_base::$maxUploadSize) ? helper_base::$maxUploadSize : '4M');
    $maxUploadSize = self::convert_to_bytes($maxUploadSize);

    $formOptions = [
      'userID' => self::$userId,
      'surveyID' => $args['survey_id'],
      'parentSampleId' => self::$sampleId,
      'parentLocId' => self::$locationID,
      'parentSampleDate' => data_entry_helper::$entity_to_load['sample:date_start'],
      'settings' => $settings,
      'sections' => self::getSections($args),
      'subSamples' => (object)$subSampleList,
      'subSamplesByCode' => $subSamplesByCode,
      'occurrenceAttributes' => $occurrenceAttributes,
      'occurrenceAttributeControls' => self::getOccurrenceAttributeControls($args, $occurrenceAttributes),
      'subSampleAttributes' => $sectionAttributes,
      'superSampleAttributes' => $transectAttributes,
      'branchTaxonMeaningIDs' => self::branchSpeciesLists($args, $settings),
      'commonTaxonMeaningIDs' => self::commonSpeciesList($args, $settings),
      'allTaxonMeaningIDsAtTransect' => self::allSpeciesAtTransect($args),
      'myTaxonMeaningIDs' => self::mySpecies($args),
      'attributeConfiguration' =>
        (!empty($args['attribute_configuration']) ? json_decode($args['attribute_configuration'], TRUE) : []),
      'speciesTabDefinition' =>
        (!empty($args['species_tab_definition']) ? json_decode($args['species_tab_definition'], TRUE) : []),
      'taxon_column' => (!empty($args['taxon_column']) ? $args['taxon_column'] : 'taxon'),
      'outOfRangeVerification' => self::rangeCheckLists($args),
      'serverRangeVerification' => !empty($args['server_range_validation']),
      'interactingSampleAttributes' =>
        (!empty($args['interacting_sample_attributes']) &&
          count(explode(',', $args['interacting_sample_attributes'])) === 2) ?
        explode(',', $args['interacting_sample_attributes']) :
        [],
      'zeroAbundance' => !isset($args['zero_abundance']) || $args['zero_abundance'],
      'maxUploadSize' => $maxUploadSize,
      'langStrings' => [
        'verificationTitle' => lang::get('Warnings'),
        'verificationSectionLimitMessage' => lang::get('The value entered for this taxon on this transect section ({{ value }}) exceeds the expected maximum ({{ limit }})'),
        'verificationWalkLimitMessage' => lang::get('The total seen for this taxon on this walk ({{ total }}) exceeds the expected maximum ({{ limit }})'),
        'duplicateTaxonMessage' => lang::get('This taxon is already included in one of the lists on this form'),
        'requiredMessage' => lang::get('This field is required'),
        'msgFileTooBig' => lang::get('file too big for warehouse') . ' ' . lang::get('[{1} bytes]', $maxUploadSize),
        'msgUploadError' => lang::get('An error occurred uploading the file.'),
        'Photos' => lang::get('Photos'),
        'Files' => lang::get('Files'),
        'Comments' => lang::get('Comments'),
        'Yes' => lang::get('Yes'),
        'commentsLabel' => lang::get('Notes'),
        'commentsHelp' => lang::get('Use this space to input comments about this occurrence.'),
      ]
    ];

    self::getOccurrences($formOptions, $args, $existing);
    
    $date = data_entry_helper::$entity_to_load['sample:date_start'];
    $pageHtml = '<div id="grid-loading"><div class="spinner" ></div></div>';
    $pageHtml .= '<div>' . self::occurrencePageHeader($args, self::$locationID, $date);

    if ($settings['occurrence_gdpr_message_position']['top']) {
        $pageHtml .= str_replace('{1}',
            '<a href="' . $settings['gdpr_link'] .'">'  . $settings['gdpr_link'] .'</a>',
            '<p class="gdpr">' .  lang::get(self::$translations['gdpr_message']) . '</p>');
        // have to do this this way so response leaves in the link
    }
    
    $pageHtml .= self::buildMultiGrid($args, $formOptions, $settings, $subSamplesByCode);
    
    if ($settings['occurrence_gdpr_message_position']['bottom']) {
        $pageHtml .= str_replace('{1}',
            '<a href="' . $settings['gdpr_link'] .'">'  . $settings['gdpr_link'] .'</a>',
            '<p class="gdpr">' .  lang::get(self::$translations['gdpr_message']) . '</p>');
        // have to do this this way so response leaves in the link
    }
    
    $pageHtml .= self::occurrenceLinks($args);
    
    $pageHtml .= '</div>';

    // stub form to attach validation to.
    $pageHtml .= '<form style="display: none" id="validation-form"></form>';
    data_entry_helper::enable_validation('validation-form');
    
    // A stub forms for AJAX posting when we need to update a supersample, subsample or occurrence
    $pageHtml .= self::occurrenceStubForm($nid, $args, $formOptions, $occurrenceAttributes);
    $pageHtml .= self::subSampleStubForm($nid, $args, $settings, self::$sampleId, $date, $sectionAttributes);
    $pageHtml .= self::superSampleStubForm($nid, $args, $settings, $date, $transectAttributes);

    $pageHtml .= self::warningDialog($nid, $args, self::$sampleId, $date, $sectionAttributes);
    
    data_entry_helper::$javascript .= "\nsetUpOccurrencesForm(".json_encode((object)$formOptions).");\n\n";

    $indicia_templates['controlWrap'] = $oldCtrlWrapTemplate;
    self::$timings .= 'Form End '.date('Y-m-d H:i:s');
    return $pageHtml . '<span style="display: none;">' . self::$timings . '</span>';
  }

  private static function occurrenceLinks($args) {
    $reloadUrl = data_entry_helper::get_reload_link_parts();
    $reloadUrl['params']['sample_id'] = self::$sampleId;
    foreach ($reloadUrl['params'] as $key => $value) {
      $reloadUrl['path'] .= (strpos($reloadUrl['path'], '?') === false ? '?' : '&') . "$key=$value";
    }
    
    return '<a href="' . $reloadUrl['path'] . '" class="btn btn-info btn-lg">' .
      lang::get('Back to visit details') .
      '</a>' .
      '<a href="' . $args['return_page'] . '" class="btn btn-success btn-lg">' .
      lang::get('Finish and return to walk list') .
      '</a>';
  }
  
  private static function superSampleStubForm($nid, $args, $settings, $date, $superSampleAttributes)
  {
    // A stub form for AJAX posting when we need to update a supersample: only do one attribute at a time
    $formHtml = '<form style="display: none" id="super-sample-form" method="post" action="' . iform_ajaxproxy_url($nid, 'sample') . '">' .
      '<input name="website_id" value="' . $args['website_id'] . '"/>' .
      '<input name="sample:id" value="' . self::$sampleId . '"/>' .
      '<input name="sample:survey_id" value="' . $args['survey_id'] . '" />' .
      '<input name="sample:sample_method_id" value="' . $args['transect_sample_method_term_id'] . '"/>' .
      '<input name="sample:entered_sref"  value="' . data_entry_helper::$entity_to_load['sample:entered_sref'] . '"/>' .
      '<input name="sample:entered_sref_system" value="' . data_entry_helper::$entity_to_load['sample:entered_sref_system'] . '"/>' .
      '<input name="sample:location_id"  value="' . data_entry_helper::$entity_to_load['sample:location_id'] . '"/>' .
      '<input name="sample:date" value="' . $date . '"/>';
    // include a stub input for each super sample attribute: need inputs for all other attributes on main page
    foreach ($settings['species_supersample_attributes'] as $attrDefinition) {
      if (!empty($attrDefinition['id']) && !empty($attrDefinition['hideFront'])) {
        unset($superSampleAttributes[$attrDefinition['id']]);
      }
    }
    foreach ($superSampleAttributes as $attr) {
      if ($attr['id'] != $attr['fieldname']) {        
        $formHtml .= '<input id="' . $attr['id'] . '" name="' . $attr['fieldname'] . '" value="' . $attr['default'] . '"/>';
      }
    }
    $formHtml .= '<input id="superSampleAttr"/>' .
      '<input name="transaction_id" id="super_sample_transaction_id"/>' .
      '<input name="user_id" value="' . self::$userId . '"/>' .
      '</form>';
    return $formHtml;
  }
  
  private static function subSampleStubForm($nid, $args, $settings, $parentSampleId, $date, $attributes)
  {
    // A stub form for AJAX posting when we need to update a subsample
    $formHtml =
      '<form style="display: none" id="sub-sample-form" method="post" action="' . iform_ajaxproxy_url($nid, 'sample') . '">' .
      '<input name="website_id" value="' . $args['website_id'] . '"/>' .
      '<input name="sample:id" id="smpid"/>' .
      '<input name="sample:parent_id" value="' . $parentSampleId . '"/>' .
      '<input name="sample:survey_id" value="' . $args['survey_id'] . '"/>' .
      '<input name="sample:sample_method_id" value="' . $args['section_sample_method_term_id'] . '"/>' .
      '<input name="sample:entered_sref" id="smpsref"/>' .
      '<input name="sample:entered_sref_system" id="smpsref_system"/>' .
      '<input name="sample:location_id" id="smploc"/>' .
      '<input name="sample:date" value="' . $date . '"/>';
    // include a stub input for each sub sample attribute: need inputs for all other attributes on main page
    foreach ($settings['global_subsample_attributes'] as $attrId) {
      $formHtml .= '<input id="subSmpAttr-' . $attrId . '"/>';
    }
    $formHtml .= '<input name="transaction_id" id="sub_sample_transaction_id"/>' .
      '<input name="user_id" value="' . self::$userId . '"/>' .
      '</form>';
      /*
       *     foreach ($attributes as $attr) {
      $formHtml .= '<input id="' . $attr['fieldname'] . '"/>';
    }
*/
    return $formHtml;
  }
  
  private static function occurrenceStubForm($nid, $args, $formOptions, $occurrenceAttributes) {
    // A stub form for AJAX posting when we need to create an occurrence
    if (!empty($args["sensitiveAttrID"]) && !empty($args["sensitivityPrecision"])) {
      $sensitiveSiteAttributes = data_entry_helper::getAttributes([
        'valuetable' => 'location_attribute_value',
        'attrtable' => 'location_attribute',
        'key' => 'location_id',
        'fieldprefix' => 'locAttr',
        'extraParams' => self::$auth['read'] +['id' => $args['sensitiveAttrID']],
        'location_type_id' => $args['transect_type_term_id'],
        'survey_id' => $args['survey_id'],
        'id' => self::$locationID
      ]);
    }
    if(!empty($args["confidentialAttrID"])) {
      $confidentialSiteAttributes = data_entry_helper::getAttributes([
        'valuetable'=>'location_attribute_value',
        'attrtable'=>'location_attribute',
        'key'=>'location_id',
        'fieldprefix'=>'locAttr',
        'extraParams'=>self::$auth['read'] + ['id'=>$args['confidentialAttrID']],
        'location_type_id'=>$args['transect_type_term_id'],
        'survey_id'=>$args['survey_id'],
        'id' => self::$locationID // location ID
      ]);
    }
    $defaults = helper_base::explode_lines_key_value_pairs($args['defaults']);
    $record_status = isset($defaults['occurrence:record_status']) ? $defaults['occurrence:record_status'] : 'C';
    $formHtml = '<form style="display: none" id="occurrence-form" method="post" action="' . iform_ajaxproxy_url($nid, 'occurrence') . '">' .
      '<input name="website_id" value="' . $args['website_id'] . '"/>' .
      '<input name="survey_id" value="' . $args["survey_id"] . '" />' .
      '<input name="occurrence:id" id="occid" />' .
      '<input name="occurrence:deleted" id="occdeleted" />' .
      '<input name="occurrence:zero_abundance" id="occzero" />' .
      '<input name="occurrence:comment" id="occcomment" />' .
      '<input name="occurrence:taxa_taxon_list_id" id="ttlid" />' .
      '<input name="occurrence:record_status" value="' . $record_status . '" />' .
      '<input name="occurrence:sample_id" id="occ_sampleid"/>' .
      (!empty($args["sensitiveAttrID"]) && !empty($args["sensitivityPrecision"]) ?
        '<input name="occurrence:sensitivity_precision" id="occSensitive" value="'.(count($sensitiveSiteAttributes)>0 && $sensitiveSiteAttributes[$args["sensitiveAttrID"]]['default']=="1" ? $args["sensitivityPrecision"] : '').'"/>' : '') .
      (!empty($args["confidentialAttrID"]) ?
        '<input name="occurrence:confidential" id="occConfidential" value="'.(count($confidentialSiteAttributes)>0 && $confidentialSiteAttributes[$args["confidentialAttrID"]]['default']=="1" ? '1' : '0').'"/>' : '') .
      '<input name="transaction_id" id="occurrence_transaction_id"/>' .
      '<input name="user_id" value="' . self::$userId . '"/>';
    
    foreach ($formOptions['settings']['occurrence_attributes'] as $occurrenceAttributeID) {
      $formHtml .= '<input name="occAttr:' . $occurrenceAttributeID . '" id="occattr-' . $occurrenceAttributeID . '"/>';
    }
    $formHtml .= '</form>';
    
    return $formHtml;
  }
  
  private static function getTransectAttributes($args) {
    // find any attributes that apply to transect samples.
    return data_entry_helper::getAttributes([
      'id' => self::$sampleId,
      'valuetable' => 'sample_attribute_value',
      'attrtable' => 'sample_attribute',
      'key' => 'sample_id',
      'fieldprefix' => 'smpAttr',
      'extraParams' => self::$auth['read'],
      'survey_id' => $args['survey_id'],
      'sample_method_id' => $args['transect_sample_method_term_id'],
      'multiValue' => FALSE // ensures that array_keys are the list of attribute IDs.
    ]);
  }
  
  private static function getSectionAttributes($args) {
    // find any attributes that apply to transect section samples.
    return data_entry_helper::getAttributes([
      'valuetable' => 'sample_attribute_value',
      'attrtable' => 'sample_attribute',
      'key' => 'sample_id',
      'fieldprefix' => 'subSmpAttr',
      'extraParams' => self::$auth['read'],
      'survey_id' => $args['survey_id'],
      'sample_method_id' => $args['section_sample_method_term_id'],
      'multiValue' => FALSE // ensures that array_keys are the list of attribute IDs.
    ]);    
  }
  
  private static function getSubSamples($args, $sampleId, $transectSectionAttributes) {
    // The parent sample and sub-samples have already been created: can't cache in case a new section added.
    self::$timings .= '(5) Start '.date('Y-m-d H:i:s'); // OK
    return data_entry_helper::get_population_data([
      'report' => 'library/samples/samples_list_for_parent_sample',
      'extraParams' => self::$auth['read'] + [
        'sample_id' => self::$sampleId,
        'date_from' => '',
        'date_to' => '',
        'sample_method_id' => $args['section_sample_method_term_id'],
        'smpattrs' => implode(',', array_keys($transectSectionAttributes))
      ],
      'nocache' => TRUE
    ]);
  }
  
  private static function transcribeSubSamples($subSamples, &$subSampleList, &$subSamplesByCode) {
    // transcribe the response array into a couple of forms that are useful elsewhere - one for outputting JSON so the JS knows about
    // the samples, and another for lookup of sample data by code later.
    foreach ($subSamples as $subSample) {
      $subSampleList[$subSample['code']] = $subSample['sample_id'];
      $subSamplesByCode[$subSample['code']] = $subSample;
    }
  }
  
  private static function getOccurrenceAttributes($args) {
    // The occurrence attribute must be flagged as numeric:true in the survey specific validation rules in order for totals to be worked out.
    return data_entry_helper::getAttributes([
      'valuetable' => 'occurrence_attribute_value',
      'attrtable' => 'occurrence_attribute',
      'key' => 'occurrence_id',
      'fieldprefix' => 'occAttr',
      'extraParams' => self::$auth['read'],
      'survey_id' => $args['survey_id'],
      'multiValue' => FALSE // ensures that array_keys are the list of attribute IDs.
    ]);
  }
  
  private static function getSections($args) {
    // Fetch the sections
    self::$timings .= '(6) Start '.date('Y-m-d H:i:s'); // OK
    $sections = data_entry_helper::get_population_data([
      'table' => 'location',
      'extraParams' => self::$auth['read'] + [
        'view' => 'detail',
        'parent_id' => self::$locationID,
        'deleted' => 'f',
        'location_type_id' => $args['section_type_term_id']
      ],
//      'nocache' => TRUE
    ]);
    usort($sections, "ukbms_stis2_sectionSort");
    return $sections;
  }
  
  private static function getOccurrences(&$formOptions, $args, $existing) {
    $formOptions['existingTaxonMeaningIDs'] = [];
    $formOptions['existingOccurrences'] = [];
    if ($existing) {
      // Only need to load the occurrences for a pre-existing sample
      $attrs = array_keys($formOptions['occurrenceAttributes']);
      self::$timings .= '(7) Start '.date('Y-m-d H:i:s');
      $records = data_entry_helper::get_population_data([
          'report' => 'projects/ukbms/ukbms_occurrences_list_for_parent_sample',
          'extraParams' => self::$auth['read'] + [
            'sample_id' => self::$sampleId,
            'survey_id' => $args['survey_id'],
            'smpattrs' => '',
            'occattrs' => implode(',', $attrs)
          ],
          // don't cache as this is live data
          'nocache' => true
        ]);
      // build an array keyed for easy lookup
      foreach($records as $occurrence) {
        $key = $occurrence['sample_id'].':'.$occurrence['taxon_meaning_id'];
        $formOptions['existingOccurrences'][$key] = [
          'ttl_id' => $occurrence['taxa_taxon_list_id'],
          'taxon_meaning_id' => $occurrence['taxon_meaning_id'],
          'occurrence_id' => $occurrence['occurrence_id'],
          'comment' => $occurrence['comment'],
          'processed' => false
        ];
        foreach($attrs as $attr){
          $formOptions['existingOccurrences'][$key]['value_'.$attr] = $occurrence['attr_occurrence_'.$attr];
          $formOptions['existingOccurrences'][$key]['value_id_'.$attr] = $occurrence['attr_id_occurrence_'.$attr];
        }
        $formOptions['existingTaxonMeaningIDs'][$occurrence['taxon_meaning_id']] = true;
      }
      $formOptions['existingTaxonMeaningIDs'] = array_keys($formOptions['existingTaxonMeaningIDs']);
    }
  }
  
  private static function getOccurrenceAttributeControls($args, $occurrenceAttributes) {
    $defaults = helper_base::explode_lines_key_value_pairs($args['defaults']);
    $defAttrOptions = ['extraParams'=>self::$auth['read']];
    $controls = [];
    foreach($occurrenceAttributes as $idx => $attr){
      unset($attr['caption']);
      if (isset($defaults[$attr['id']])) {
      //    ? $defaults['occurrence:record_status'] : 'C';
        $attr['default'] = $defaults[$attr['id']];
      }
      $ctrl = data_entry_helper::outputAttribute($attr, $defAttrOptions);
      $controls[$idx] = str_replace("\n","",$ctrl);
    }
    return $controls;
  }
  
  private static function branchSpeciesLists($args, $settings) {
    $branchSpeciesList = [];
    $speciesTabs = json_decode($args['species_tab_definition'], TRUE);
    foreach ($speciesTabs as $idx => $speciesTab) {
        $branchSpeciesList[$idx] = [];
    }
    if (empty($settings['branch'])) {
      return $branchSpeciesList;
    }
    switch ($args['species_type']) {
        case 'attribute': // 'Taxon Attribute', // use a ttl attribute, ID provided below
            if (empty($settings['branch_species_attr_id'])) {
                return $branchSpeciesList;
            }
            $attr_id = $settings['branch_species_attr_id'];
            break;
        case 'scheme': // 'Taxon Attribute, Scheme provided', // use a ttl attribute, ID provided by scheme
            return $branchSpeciesList;
            break;
        default:
          return $branchSpeciesList;
    }
    if (empty($args['species_attr_values'])) {
      return $branchSpeciesList;
    }
    
    $taxaAttrValues = data_entry_helper::get_population_data([
        'table' => 'taxa_taxon_list_attribute_value',
        'extraParams' => array_merge(self::$auth['read'], ['view' => 'list', 'taxa_taxon_list_attribute_id' => $attr_id]),
        'columns' => 'taxa_taxon_list_id,value'
    ]);
    $possibleTaxa = [];
    $possibleAttrValues = explode('|', $args['species_attr_values']);
    foreach ($taxaAttrValues as $taxaAttrValue) {
        if (in_array($taxaAttrValue['value'], $possibleAttrValues)){
            $possibleTaxa[] = $taxaAttrValue['taxa_taxon_list_id'];
        }
    }
    sort($possibleTaxa);
    foreach ($speciesTabs as $idx => $speciesTab) {
        $branchSpeciesList[$idx] = [];
        $extraParams = array_merge(self::$auth['read'],
            [
                'taxon_list_id' => $speciesTab['taxon_list_id'],
                'id' => $possibleTaxa,
                'preferred' => 't',
                'allow_data_entry' => 't',
                'view' => 'cache'
            ]);
        if (!empty($speciesTab['taxon_filter_field']) && !empty($speciesTab['taxon_filter'])) {
            $extraParams[$speciesTab['taxon_filter_field']] = $speciesTab['taxon_filter'];
        }
        $taxa = data_entry_helper::get_population_data([
            'table' => 'taxa_taxon_list',
            'extraParams' => $extraParams,
            'columns' => 'taxon_meaning_id'
        ]);
        foreach ($taxa as $taxon) {
            $branchSpeciesList[$idx][] = $taxon['taxon_meaning_id'];
        }
        $branchSpeciesList[$idx] = array_unique($branchSpeciesList[$idx]);
    }
    return $branchSpeciesList;
  }
  
  private static function commonSpeciesList($args, $settings) {
      $commonSpeciesList = [];
      $speciesTabs = json_decode($args['species_tab_definition'], TRUE);
      foreach ($speciesTabs as $idx => $speciesTab) {
          $commonSpeciesList[$idx] = [];
      }
      switch ($args['species_type']) {
          case 'attribute': // 'Taxon Attribute', // use a ttl attribute, ID provided below
              if (empty($settings['common_species_attr_id'])) {
                  return $commonSpeciesList;
              }
              $attr_id = $settings['common_species_attr_id'];
              break;
          case 'scheme': // 'Taxon Attribute, Scheme provided', // use a ttl attribute, ID provided by scheme
              return $commonSpeciesList;
              break;
          default:
              return $commonSpeciesList;
      }
      if (empty($args['species_attr_values'])) {
          return $commonSpeciesList;
      }
      
      $taxaAttrValues = data_entry_helper::get_population_data([
          'table' => 'taxa_taxon_list_attribute_value',
          'extraParams' => array_merge(self::$auth['read'], ['view' => 'list', 'taxa_taxon_list_attribute_id' => $attr_id]),
          'columns' => 'taxa_taxon_list_id,value'
      ]);
      $possibleTaxa = [];
      $possibleAttrValues = explode('|', $args['species_attr_values']);
      foreach ($taxaAttrValues as $taxaAttrValue) {
          if (in_array($taxaAttrValue['value'], $possibleAttrValues)){
              $possibleTaxa[] = $taxaAttrValue['taxa_taxon_list_id'];
          }
      }
      sort($possibleTaxa);
      foreach ($speciesTabs as $idx => $speciesTab) {
          $commonSpeciesList[$idx] = [];
          $extraParams = array_merge(self::$auth['read'],
              [
                  'taxon_list_id' => $speciesTab['taxon_list_id'],
                  'id' => $possibleTaxa,
                  'preferred' => 't',
                  'allow_data_entry' => 't',
                  'view' => 'cache'
              ]);
          if (!empty($speciesTab['taxon_filter_field']) && !empty($speciesTab['taxon_filter'])) {
              $extraParams[$speciesTab['taxon_filter_field']] = $speciesTab['taxon_filter'];
          }
          $taxa = data_entry_helper::get_population_data([
              'table' => 'taxa_taxon_list',
              'extraParams' => $extraParams,
              'columns' => 'taxon_meaning_id'
          ]);
          foreach ($taxa as $taxon) {
              $commonSpeciesList[$idx][] = $taxon['taxon_meaning_id'];
          }
          $commonSpeciesList[$idx] = array_unique($commonSpeciesList[$idx]);
      }
      return $commonSpeciesList;
  }

  private static function rangeCheckLists($args) {
    $rangeList = new stdClass();
    $rangeList->attrId = FALSE;
    $rangeList->walk = [];
    $rangeList->section = [];
    
    if (empty($args['occurrenceValueRangeAttributeID'])) {
      return $rangeList;
    }
    $rangeList->attrId = $args['occurrenceValueRangeAttributeID'];
    if(!empty($args['walkRangeAttrID'])) {
      $taxaAttrValues = data_entry_helper::get_population_data([
          'table' => 'taxa_taxon_list_attribute_value',
          'extraParams' => array_merge(self::$auth['read'], ['view' => 'list', 'taxa_taxon_list_attribute_id' => $args['walkRangeAttrID']]),
          'columns' => 'taxa_taxon_list_id,value'
      ]);
      foreach ($taxaAttrValues as $taxaAttrValue) {
        if ($taxaAttrValue['value'] != '')
          $possibleTaxa[$taxaAttrValue['taxa_taxon_list_id']] = $taxaAttrValue['value'];
      }
      $keys = array_keys($possibleTaxa);
      sort($keys);
      $extraParams = array_merge(self::$auth['read'],
        [
          'id' => $keys,
          'preferred' => 't',
          'allow_data_entry' => 't',
          'view' => 'cache'
        ]);
      $taxa = data_entry_helper::get_population_data([
        'table' => 'taxa_taxon_list',
        'extraParams' => $extraParams,
        'columns' => 'id,taxon_meaning_id'
      ]);
      foreach ($taxa as $taxon) {
        if (!empty($possibleTaxa[$taxon['id']])) {
          $rangeList->walk[$taxon['taxon_meaning_id']] = $possibleTaxa[$taxon['id']];
        }
      }
    }
    if(!empty($args['sectionRangeAttrID'])) {
        $taxaAttrValues = data_entry_helper::get_population_data([
            'table' => 'taxa_taxon_list_attribute_value',
            'extraParams' => array_merge(self::$auth['read'], ['view' => 'list', 'taxa_taxon_list_attribute_id' => $args['sectionRangeAttrID']]),
            'columns' => 'taxa_taxon_list_id,value'
        ]);
        foreach ($taxaAttrValues as $taxaAttrValue) {
            if ($taxaAttrValue['value'] != '')
                $possibleTaxa[$taxaAttrValue['taxa_taxon_list_id']] = $taxaAttrValue['value'];
        }
        $keys = array_keys($possibleTaxa);
        sort($keys);
        $extraParams = array_merge(self::$auth['read'],
            [
                'id' => $keys,
                'preferred' => 't',
                'allow_data_entry' => 't',
                'view' => 'cache'
            ]);
        $taxa = data_entry_helper::get_population_data([
            'table' => 'taxa_taxon_list',
            'extraParams' => $extraParams,
            'columns' => 'id,taxon_meaning_id'
        ]);
        foreach ($taxa as $taxon) {
            if (!empty($possibleTaxa[$taxon['id']])) {
                $rangeList->section[$taxon['taxon_meaning_id']] = $possibleTaxa[$taxon['id']];
            }
        }
    }
    return $rangeList;
  }
  
  private static function allSpeciesAtTransect($args) {
    
    $allTaxonMeaningIDsAtTransect = [];
    
    self::$timings .= '(10) Start '.date('Y-m-d H:i:s'); // OK
    $taxa = data_entry_helper::get_population_data([
      'report' => 'projects/ukbms/ukbms_taxon_meanings_at_transect',
      'extraParams' => self::$auth['read'] + ['location_id' => self::$locationID, 'survey_id'=>$args['survey_id']],
//      'nocache' => true // ?? don't cache as this is live data
    ]);
    
    foreach($taxa as $taxon){
      $allTaxonMeaningIDsAtTransect[] = $taxon['taxon_meaning_id'];
    } // all meanings is list independant.
    
    return $allTaxonMeaningIDsAtTransect;
  }
  
  private static function mySpecies($args) {
    
    $mySpecies = [];
    
    self::$timings .= '(11) Start '.date('Y-m-d H:i:s');
    $taxa = data_entry_helper::get_population_data([
        'report' => 'projects/ukbms/ukbms_taxon_meanings_for_user',
        'extraParams' => self::$auth['read'] + ['user_id' => self::$userId, 'survey_id'=>$args['survey_id']],
//        'nocache' => true // ?? don't cache as this is live data
    ]);
    
    foreach($taxa as $taxon){
        $mySpecies[] = $taxon['taxon_meaning_id'];
    } // all meanings is list independant.
    
    return $mySpecies;
  }
  
  private static function occurrencePageHeader($args, $locationID, $date) {
    self::$timings .= '(12) Start '.date('Y-m-d H:i:s');
    $location = data_entry_helper::get_population_data([
      'table' => 'location',
      'extraParams' => self::$auth['read'] + ['view' => 'detail','id' => $locationID]
    ]);
    $dateObj   = DateTime::createFromFormat('!Y#m#d', $date);
    $displayDate = $dateObj->format('l jS F Y');
    return '<h2 id="ukbms_stis_header">' . $location[0]['name'] . " on " . $displayDate . "</h2>";
  }
  
  private static function warningDialog($nid, $args, $sampleId, $date, $transect_section_attributes) {
    return '<div style="display: none"><div id="warning-dialog"><p>' .
      lang::get('The following warnings have been identified') .
      '</p><ul id="warning-dialog-list"></ul><p>' .
      lang::get('Do you still wish to enter this data?') .
      '</p></div></div>';
  }
  

  private static function buildMultiGrid ($args, &$formOptions, $settings, $subSamplesByCode)
  {
    // In the following cases we switch to the complex grid: >1 occurrence attribute, occurrence media, subsample media
    if (count($settings['occurrence_attributes']) > 1 ||
        !empty($settings['sub_sample_images']) ||
        !empty($settings['occurrence_images']) ||
        !empty($settings['occurrence_comments'])) {
      return self::buildMultiGridComplex($args, $formOptions, $settings, $subSamplesByCode);
    } else {
      return self::buildMultiGridSimple($args, $formOptions, $settings, $subSamplesByCode);
    }
  }

  private static function buildMultiGridSimple ($args, &$formOptions, $settings, $subSamplesByCode)
  {
    global $indicia_templates;
    
    $format = 'simple';
    $formOptions['format'] = $format;
    $formOptions['addSpeciesPosition'] = (!empty($args['add_species_position']) ? $args['add_species_position'] : 'below');

    $numberOfColumns = self::numberOfColumnsInMultiGrid($formOptions, $settings);
    $gridHtml = '<div id="multi-grid">';
    $gridHtml .= '<div class="species_grid_flex">' . self::speciesTabSelectorControl($formOptions['speciesTabDefinition']) . '</div>';
    foreach ($formOptions['speciesTabDefinition'] as $index => $tabDefinition) {
        $style = ($index ? 'style="display: none;"' : '');
        $gridHtml .= '<div class="species_grid_flex species_grid_title species_grid_title_' . $index . '" ' . $style . '>' .
            '<h3>' . lang::get($tabDefinition['title']) . '</h3></div>' .
            '<div id="species_grid_supersample_attributes_' . $index . '" class="species_grid_flex species_grid_supersample_attributes" ' . $style . '>';
        
        foreach ($settings['species_supersample_attributes'] as $speciesSupersampleAttribute) {
            if ($speciesSupersampleAttribute['grid'] == $index) {
                $gridHtml .= '<div>' .
                    self::buildMultiGridSupersampleAttribute($formOptions, $settings, $speciesSupersampleAttribute['id']) .
                    "</div>";
            }
        }
        $gridHtml .= '</div>';
        $speciesControlDisplay = FALSE;
        $speciesControl = self::speciesListControl($index, $tabDefinition, $args, $formOptions, empty($style), '<div>{{control}}</div>', $speciesControlDisplay) .
        $sortControlDisplay = FALSE;
        $sortControl = self::buildSortControl($index, $tabDefinition, $args, empty($style), '<div>{{control}}</div>', $sortControlDisplay);
        if ($speciesControlDisplay || $sortControlDisplay) {
            $gridHtml .= '<div class="species_grid_flex species_grid_controls species_grid_controls_' . $index . '" ' . $style . '>';
            if ($speciesControlDisplay) {
                $gridHtml .= $speciesControl;
            }
            if ($sortControlDisplay) {
                $gridHtml .= $sortControl;
            }
            $gridHtml .= '</div>' . PHP_EOL;
        }
    }
    $gridHtml .= '<table class="species_grid table-hover table-striped sticky-enabled simple"><thead>';
    if ($args['add_species_position'] === 'above') {
      foreach ($formOptions['speciesTabDefinition'] as $index => $tabDefinition) {
        $style = ($format === 'simple' && $index ? 'style="display: none;"' : '');
        $gridHtml .= self::autocompleteControl($index, $tabDefinition, $args, '<tr class="species_grid_selector_' . $index . ' species_grid_selector" ' . $style . '><td colspan=' . $numberOfColumns . '>{{control}}</td></tr>');
    }
    }
    // In order for the sticky headings to work, only one row in the header can have <th>s
    // This is due to the use of .index() in function recalculateSticky()
    // In order for the Section headings to line up, this has to be the row with the <th>s
    $gridHtml .= self::buildMultiGridSectionHeader($formOptions, $settings, true);
    $gridHtml .= '</thead>'. PHP_EOL;
    $gridHtml .= '<tbody id="global_subsample_attributes">';
    foreach ($settings['global_subsample_attributes'] as $idx => $globalSubsampleAttribute) {
        $gridHtml .= self::buildMultiGridGlobalSubsampleAttribute($formOptions, $settings, $globalSubsampleAttribute, ($idx+1) === count($settings['global_subsample_attributes']));
    }
    $gridHtml .= '</tbody>'. PHP_EOL;
    foreach ($formOptions['speciesTabDefinition'] as $index => $tabDefinition) {
      $style = ($format === 'simple' && $index ? 'style="display: none;"' : '');
      $gridHtml .= '<tbody id="species_grid_' . $index . '" class="species_grid" ' . $style . '>' .
        '<tr class="totals-row">' . self::buildMultiGridTotalsRow($formOptions, $settings) . '</tr>' .
        '</tbody>'. PHP_EOL;
      if ($args['add_species_position'] !== 'above') {
        $gridHtml .= '<tbody class="species_grid_selector_' . $index . ' species_grid_selector" ' . $style . '>' .
          self::autocompleteControl($index, $tabDefinition, $args, '<tr><td colspan=' . $numberOfColumns . '>{{control}}</td></tr>') .
        '</tbody>'. PHP_EOL;
      }
    }
    $gridHtml .= '</table></div>';
    return $gridHtml;
  }

  private static function buildMultiGridComplex ($args, &$formOptions, $settings, $subSamplesByCode)
  {
      global $indicia_templates;
      
      // In the following cases we switch to the complex grid: >1 occurrence attribute, occurrence media or comments, subsample media
      $format = 'complex';
      $formOptions['format'] = $format;
      $formOptions['addSpeciesPosition'] = (!empty($args['add_species_position']) ? $args['add_species_position'] : 'below');
      
      $numberOfColumns = self::numberOfColumnsInMultiGrid($formOptions, $settings);
      $gridHtml = '<div id="multi-grid">';
   
      $gridHtml .= '<div class="species_grid_flex">' . self::sectionSelectorControl($formOptions['sections']) . '</div>';

      $gridHtml .= '<div class="species_grid_flex"><table class="species_grid table-hover table-striped sticky-enabled"><thead>';
      // In order for the sticky headings to work, only one row in the header can have <th>s
      // This is due to the use of .index() in function recalculateSticky()
      // In order for the Section headings to line up, this has to be the row with the <th>s
      $gridHtml .= self::buildMultiGridSectionHeader($formOptions, $settings, true);
      $gridHtml .= '</thead>'. PHP_EOL;
      
      $gridHtml .= '<tbody id="global_subsample_attributes">';
      foreach ($settings['global_subsample_attributes'] as  $idx => $globalSubsampleAttribute) {
          $gridHtml .= self::buildMultiGridGlobalSubsampleAttribute($formOptions, $settings, $globalSubsampleAttribute, FALSE);
      }
      $gridHtml .= '</tbody></table></div>'. PHP_EOL;
      
      foreach ($formOptions['speciesTabDefinition'] as $index => $tabDefinition) {
          $gridHtml .= '<hr><div class="species_grid_flex species_grid_title">' .
              '<h3>' . lang::get($tabDefinition['title']) . '</h3></div>' .
              '<div id="species_grid_supersample_attributes_' . $index . '" class="species_grid_flex species_grid_supersample_attributes">';

          foreach ($settings['species_supersample_attributes'] as $speciesSupersampleAttribute) {
              if ($speciesSupersampleAttribute['grid'] == $index) {
                  $gridHtml .= '<div>' .
                      self::buildMultiGridSupersampleAttribute($formOptions, $settings, $speciesSupersampleAttribute['id']) .
                      "</div>";
              }
          }
          
          $gridHtml .= "</div>";
          $speciesControlDisplay = FALSE;
          $speciesControl = self::speciesListControl($index, $tabDefinition, $args, $formOptions, TRUE, '<div>{{control}}</div>', $speciesControlDisplay) .
          $sortControlDisplay = FALSE;
          $sortControl = self::buildSortControl($index, $tabDefinition, $args, TRUE, '<div>{{control}}</div>', $sortControlDisplay);
          if ($speciesControlDisplay || $sortControlDisplay) {
              $gridHtml .= '<div class="species_grid_flex species_grid_controls species_grid_controls_' . $index . '">';
              if ($speciesControlDisplay) {
                  $gridHtml .= $speciesControl;
              }
              if ($sortControlDisplay) {
                  $gridHtml .= $sortControl;
              }
              $gridHtml .= '</div>' . PHP_EOL;
          }
          $gridHtml .= '<table id="species_grid_table_' . $index . '" class="species_grid table-hover table-striped sticky-enabled"><thead>';
          if ($args['add_species_position'] === 'above') {
              $gridHtml .= self::autocompleteControl($index,
                  $tabDefinition,
                  $args,
                  '<tr class="species_grid_selector_' . $index . ' species_grid_selector"><td colspan=' . $numberOfColumns . '>{{control}}</td></tr>');
          }
          // In order for the sticky headings to work, only one row in the header can have <th>s
          // This is due to the use of .index() in function recalculateSticky()
          // In order for the Section headings to line up, this has to be the row with the <th>s
          $gridHtml .= self::buildMultiGridSectionHeader($formOptions, $settings, true);
          $gridHtml .= '</thead>'. PHP_EOL;
          $gridHtml .= '<tbody id="species_grid_' . $index . '" class="species_grid">' .
              self::buildMultiGridAttributeHeader($args, $formOptions, $settings) .
              '<tr class="totals-row">' . self::buildMultiGridTotalsRow($formOptions, $settings) . '</tr>' .
              '</tbody>'. PHP_EOL;
          if ($args['add_species_position'] !== 'above') {
              $gridHtml .= '<tbody class="species_grid_selector_' . $index . ' species_grid_selector">' .
                  self::autocompleteControl($index, $tabDefinition, $args, '<tr><td colspan=' . $numberOfColumns . '>{{control}}</td></tr>') .
                  '</tbody>'. PHP_EOL;
          }
          $gridHtml .= '</table>';
      }
      $gridHtml .= '</div>';
          
      if ($settings['occurrence_images']) {
          data_entry_helper::add_resource('plupload');
          // store some globals that we need later when creating uploaders
          $relpath = data_entry_helper::getRootFolder() . data_entry_helper::client_helper_path();
          $interimImageFolder = data_entry_helper::getInterimImageFolder('domain');
          $relativeImageFolder = data_entry_helper::getImageRelativePath();
          data_entry_helper::$javascript .= "indiciaData.uploadSettings = {\n";
          data_entry_helper::$javascript .= "  uploadScript: '" . $relpath . "upload.php',\n";
          data_entry_helper::$javascript .= "  destinationFolder: '$interimImageFolder',\n";
          data_entry_helper::$javascript .= "  relativeImageFolder: '$relativeImageFolder',\n";
          data_entry_helper::$javascript .= "  jsPath: '".data_entry_helper::$js_path."'";
          // if (isset($options['resizeWidth'])) {
          //data_entry_helper::$javascript .= ",\n  resizeWidth: ".$options['resizeWidth'];
          //}
          //if (isset($options['resizeHeight'])) {
          //  data_entry_helper::$javascript .= ",\n  resizeHeight: ".$options['resizeHeight'];
          //}
          //if (isset($options['resizeQuality'])) {
          //  data_entry_helper::$javascript .= ",\n  resizeQuality: ".$options['resizeQuality'];
          //}
          data_entry_helper::$javascript .= "\n}\n";
          $mediaTypes = ['Image:Local'];
          data_entry_helper::$javascript .= "indiciaData.uploadSettings.mediaTypes=".json_encode($mediaTypes) . ";\n";
          if ($indicia_templates['file_box']!='')
            data_entry_helper::$javascript .= "file_boxTemplate = '".str_replace('"','\"', $indicia_templates['file_box'])."';\n";
          if ($indicia_templates['file_box_initial_file_info']!='')
            data_entry_helper::$javascript .= "file_box_initial_file_infoTemplate = '".str_replace('"','\"', $indicia_templates['file_box_initial_file_info'])."';\n";
          if ($indicia_templates['file_box_uploaded_image']!='')
            data_entry_helper::$javascript .= "file_box_uploaded_imageTemplate = '".str_replace('"','\"', $indicia_templates['file_box_uploaded_image'])."';\n";
      }
      return $gridHtml;
  }

  /**
   * Build a row of buttons for selecting the species tab.
   * Bootstrap 3
   */
  private static function speciesTabSelectorControl($tabs) {
    $ctrlHTML = '<div class="btn-group btn-group-toggle" data-toggle="buttons">';
    foreach ($tabs as $index => $tab) {
      $active = !$index;
      $ctrlHTML .= '<label class="btn btn-primary' . ($active ? ' active' : '') . '">' .
        '<input type="radio" name="speciesTab" id="speciesTab-' . $index . '" autocomplete="off" value="' .
        $index . ($active ? '" checked>' : '">') . $tab['title'] . '</label>';
    }
    $ctrlHTML .= '</div>';
    return $ctrlHTML;
  }

  /**
   * Build a row of buttons for selecting the route.
   * Bootstrap 3
   */
  private static function sectionSelectorControl($sections) {
    $ctrlHTML = '<div><label>' . lang::get('Select section') . ':</label> ' .
      '<div class="btn-group btn-group-sm btn-group-toggle" data-toggle="buttons">';
    foreach ($sections as $section) {
      $code = $section['code'];
      $active = ($code == 'S1');
      $ctrlHTML .= '<label class="btn btn-primary' . ($active ? ' active' : '') . '">' .
        '<input type="radio" name="section" id="section' . $code . '" autocomplete="off" value="' .
        $code . ($active ? '" checked>' : '">') . $code . '</label>';
    }
    $ctrlHTML .= '</div></div>';
    return $ctrlHTML;
  }
  
  private static function numberOfColumnsInMultiGrid($formOptions, $settings) {
    $numberOfColumns = 1; // for row label: taxon or attribute label
    $numberOfColumns += count($formOptions['sections']) * count($settings['occurrence_attributes']);
    $numberOfColumns += (!empty($settings['occurrence_images']) ? count($formOptions['sections']) : 0);
    $numberOfColumns += (!empty($settings['occurrence_comments']) ? count($formOptions['sections']) : 0);
    foreach ($settings['occurrence_attributes'] as $attribute) {
      if (strpos($formOptions['occurrenceAttributeControls'][$attribute], 'number:true') !== FALSE) {
        $numberOfColumns++;
      }
    }
    return $numberOfColumns;
  }

  private static function buildMultiGridGlobalSubsampleAttribute($formOptions, $settings, $globalSubsampleAttribute, $lastRow) {
      // Currently no application of custom_attribute_options
    $row = '<tr id="smp-' . $globalSubsampleAttribute . '">';
    $row .= '<th class="' . ($lastRow ? 'last-row' : '') . '">' . $formOptions['subSampleAttributes'][$globalSubsampleAttribute]['caption'] . '</th>';
    $attr = $formOptions['subSampleAttributes'][$globalSubsampleAttribute];
    
    unset($attr['caption']);
    foreach ($formOptions['sections'] as $index => $section) {
      $style = ($formOptions['format'] === 'simple' || $section['code'] === 'S1' ? '' : 'style="display: none;"');
      // output a cell with the attribute - tag it with a class & id to make it easy to find from JS.
      // id = subSmpAttr-[S<N>]-[SampleId]-[AttributeId]-[AttributeValueId]
	    $parts = ['subSmpAttr', $section['code'], 'NEW', $globalSubsampleAttribute, 'NEW'];
      $attrOpts = [
        'class' => 'subSampleInput subSmpAttr-' . $section['code'],
        'extraParams' => self::$auth['read']
      ];
      if ($formOptions['subSampleAttributes'][$globalSubsampleAttribute]['data_type'] === 'I') {
        $attrOpts['class'] .= ' count-input';
      }
      // if there is an existing value, set it and also ensure the attribute name reflects the attribute value id.
      if (isset($formOptions['subSamplesByCode'][$section['code']])) {
        $subsample = $formOptions['subSamplesByCode'][$section['code']];
        $parts[2] = $subsample['sample_id'];
        // but have to take into account possibility that this field has been blanked out, so deleting the attribute.
        if (isset($subsample['attr_id_sample_' . $globalSubsampleAttribute]) &&
            $subsample['attr_id_sample_' . $globalSubsampleAttribute] != '') {
    		  $parts[4] = $subsample['attr_id_sample_' . $globalSubsampleAttribute];
          $attr['default'] = $subsample['attr_sample_' . $globalSubsampleAttribute];
        } else {
          $attr['default'] = isset($_POST[$attr['fieldname']]) ? $_POST[$attr['fieldname']] : '';
        }
      } else {
        throw new exception("Missing Section sample " . $section['code']);
      }
      $attrOpts['id'] = implode('-', $parts);
      $row .= '<td class="section-' . $section['code'] . ' sub-sample-cell' . ($lastRow ? ' last-row' : '') . '" ' . $style .
      ' colspan=' . (count($settings['occurrence_attributes']) + (!empty($settings['occurrence_images']) ? 1 : 0) + (!empty($settings['occurrence_comments']) ? 1 : 0)) .
        '>' . data_entry_helper::outputAttribute($attr, $attrOpts) .
        '</td>';
    }
    foreach ($settings['occurrence_attributes'] as $attribute) {
      if (strpos($formOptions['occurrenceAttributeControls'][$attribute], 'number:true') !== FALSE) {
        $row .= '<td class="' . ($lastRow ? 'last-row' : '') . '"></td>';
      }
    }
    $row .= '</tr>';
    return $row;
  }
  
  private static function buildMultiGridSupersampleAttribute($formOptions, $settings, $supersampleAttribute) {
    // Currently no application of custom_attribute_options
    $row = '<tr id="superSampleAttribute-' . $supersampleAttribute . '">';
    $row .= '<th>' . $formOptions['superSampleAttributes'][$supersampleAttribute]['caption'] . '</th>';
    $attr = $formOptions['superSampleAttributes'][$supersampleAttribute];
    unset($attr['caption']);

    // output a cell with the attribute - tag it with a class & id to make it easy to find from JS.
    // id = superSampleAttr-[AttributeId], name holds any value id
    $parts = explode(':', $attr['id']);
    $parts[0] = 'superSampleAttr';
    $attrOpts = [
      'class' => 'superSampleInput',
      'extraParams' => self::$auth['read'],
      'id' => implode('-', $parts)
    ];
    if ($formOptions['superSampleAttributes'][$supersampleAttribute]['data_type'] === 'I') {
      $attrOpts['class'] .= ' count-input';
    }
    $row .= '<td class="super-sample-cell"' .
      ' colspan=' . (self::numberOfColumnsInMultiGrid($formOptions, $settings) - 1) .
      '>' . data_entry_helper::outputAttribute($attr, $attrOpts) .
      '</td>';
    foreach ($settings['occurrence_attributes'] as $attribute) {
      if (strpos($formOptions['occurrenceAttributeControls'][$attribute], 'number:true') !== FALSE) {
        $row .= '<td></td>'; // Dummy cells for the totals columns.
      }
    }
    $row .= '</tr>';
    return $row;
  }
  
  private static function buildMultiGridSectionHeader($formOptions, $settings, $includeTotals) {
    $row = '<tr class="section-header"><th><a class="top-link" href="#main-content">' . lang::get('Top') . '<span class="glyphicon glyphicon-arrow-up" aria-hidden="true"></span></a></th>';
    foreach ($formOptions['sections'] as $index => $section) {
      $style = ($formOptions['format'] === 'simple' || $section['code'] === 'S1' ? '' : 'style="display: none;"');
      $row .= '<th class="section-' . $section['code'] . ' label-cell" ' . $style .
        ' colspan=' . (count($settings['occurrence_attributes']) + (!empty($settings['occurrence_images']) ? 1 : 0) + (!empty($settings['occurrence_comments']))) .
        '>' . $section['code'] . '</th>';
    }
	  $totals = 0;
    foreach ($settings['occurrence_attributes'] as $attribute) {
      if (strpos($formOptions['occurrenceAttributeControls'][$attribute], 'number:true') !== FALSE) {
        $totals++;
      }
    }
    if ($totals) {
	    if ($formOptions['format'] === "simple" && $includeTotals) {
        $row .= '<th colspan=' . $totals . '>' . lang::get('Total') . '</th>';
	    } else {
        $row .= '<th colspan=' . $totals . '></th>';
	    }
    }
    return $row . '</tr>';
  }

  private static function buildMultiGridAttributeHeader($args, $formOptions, $settings) {
    $attrSpecificOptions= (!empty($args['custom_attribute_options']) ? get_attr_options_array_with_user_data($args['custom_attribute_options']) : []);
    $row = '<tr><td>' . lang::get('Species') . '</td>';
    foreach ($settings['occurrence_attributes'] as $attribute) {
      $row .= '<td>' . 
          (!empty($attrSpecificOptions[$formOptions['occurrenceAttributes'][$attribute]['id']]) && 
              !empty($attrSpecificOptions[$formOptions['occurrenceAttributes'][$attribute]['id']]['label']) ?
              lang::get($attrSpecificOptions[$formOptions['occurrenceAttributes'][$attribute]['id']]['label']) :
              $formOptions['occurrenceAttributes'][$attribute]['caption']) . 
        '</td>';
    }
    if (!empty($settings['occurrence_images'])) {
      $row .= '<td></td>';
    }
    if (!empty($settings['occurrence_comments'])) {
      $row .= '<td></td>';
    }
    foreach ($settings['occurrence_attributes'] as $attribute) {
      if (strpos($formOptions['occurrenceAttributeControls'][$attribute], 'number:true') !== FALSE) {
        $row .= '<td>' . lang::get('{1} Species Total', $formOptions['occurrenceAttributes'][$attribute]['caption']) . '</td>';
      }
    }
    return $row . '</tr>';
  }

  private static function buildMultiGridTotalsRow($formOptions, $settings) {
    $row = '<th>' . lang::get('Totals') . '</th>';
    foreach ($formOptions['sections'] as $section) {
      foreach ($settings['occurrence_attributes'] as $attribute) {
        $row .= '<td class="section-' . $section['code'] . ' total-cell col-' . $attribute . '" ' .
          ($formOptions['format'] === 'simple' || $section['code'] === 'S1' ? '' : 'style="display: none;"') . '></td>';
      }
    }
    if (!empty($settings['occurrence_images'])) {
      $row .= '<td></td>';
    }
    if (!empty($settings['occurrence_comments'])) {
      $row .= '<td></td>';
    }
    foreach ($settings['occurrence_attributes'] as $attribute) {
      if (strpos($formOptions['occurrenceAttributeControls'][$attribute], 'number:true') !== FALSE) {
        $row .= '<td class="total-' . $attribute . '"></td>';
      }
    }
    return $row;
  }
  
  /****************************************************************************
   * The following functions are common to both flavours of the Occurrence page
   ****************************************************************************/
  
  private static function speciesListControl($tabNum, $tabDefinition, $args, $formOptions, $showRow, $template, &$showControl) {
    
    $listSelected = hostsite_get_user_field('default_species_view');
    if (empty($listSelected)) {
      $listSelected = !empty($tabDefinition['start_list']) ? $tabDefinition['start_list'] : 'here';
    }
    $listSelected = in_array($listSelected, array('branch','full','common','mine','here')) ? $listSelected : 'here';
    
    $default = FALSE;
    $options = [];
    // The use of the branch list is toggled purely on the provision of a branch species attribute
    if (count($formOptions['branchTaxonMeaningIDs'][$tabNum]) > 0) {
        $options['branch'] = lang::get($args['branch_label'] . ' specific list');
    }
    elseif ($listSelected === 'branch') {
      $listSelected = 'here';
    }
    if (!empty($tabDefinition['species_list']['full_enabled'])) {
      $options['full'] = lang::get('All');
    }
    if (!empty($tabDefinition['species_list']['common_enabled']) &&
        count($formOptions['commonTaxonMeaningIDs'][$tabNum]) > 0) {
      $options['common'] = lang::get('Common');
    }
    elseif ($listSelected === 'common') {
      $listSelected = 'here';
    }
    if ($listSelected === 'full' && empty($tabDefinition['species_list']['full_enabled'])) {
      $listSelected = 'here';
    }
    if (!empty($tabDefinition['species_list']['here_enabled'])) {
      $options['here'] = lang::get('Species known at this site');
    }
    if (!empty($tabDefinition['species_list']['mine_enabled'])) {
      $options['mine'] = lang::get('Species I have recorded');
    }
    if (count($options) == 0) {
      $options['here'] = lang::get('Species known at this site');
    }
    $showControl = FALSE;
    if(count($options) === 0) {
      $ctrlHTML = '<input name="species-list-' . $tabNum . '" type="radio" value="here" style="display: none;" checked>';
    } else if(count($options) === 1) {
      $ctrlHTML = '<input name="species-list-' . $tabNum . '" type="radio" value="' . $listSelected . '" style="display: none;" checked>';
    } else {
      $ctrlHTML = '<label>' . lang::get('Preload species list') . ':</label> ' .
        '<div class="btn-group btn-group-sm btn-group-toggle" data-toggle="buttons">';
      foreach ($options as $key => $option) {
        $active = ($key === $listSelected);
        $ctrlHTML .= '<label class="btn btn-primary' . ($active ? ' active' : '') . '">' .
          '<input type="radio" name="species-list-' . $tabNum . '" autocomplete="off" value="' .
          $key . ($active ? '" checked>' : '">') . $option . '</label>';
      }
      $ctrlHTML .= '</div>';
      $showControl = TRUE;
    }
    return str_replace(
        ['{{class}}', '{{control}}', '{{display}}'],
        [$showControl ? '' : ' keepHidden', $ctrlHTML, $showRow ? '' : 'display: none;'],
        $template);
  }
  
  private static function buildSortControl($tabNum, $tabDefinition, $args, $showRow, $template, &$showControl) {
    $default = FALSE;
    $options = [];
    if(isset($tabDefinition['sort_order']['taxonomic']) && !empty($tabDefinition['sort_order']['taxonomic']['enabled'])) {
      $options['taxonomic_sort_order'] = lang::get('Taxonomic Sort Order');
      $default = "taxonomic_sort_order";
    }
    if(isset($tabDefinition['sort_order']['common']) && !empty($tabDefinition['sort_order']['common']['enabled'])) {
      $options['default_common_name'] = lang::get('Common name');
      $default = !empty($tabDefinition['sort_order']['common']['default']) ? 'default_common_name' : $default;
    }
    if(isset($tabDefinition['sort_order']['preferred']) && !empty($tabDefinition['sort_order']['preferred']['enabled'])) {
      $options['preferred_taxon'] = lang::get('Species name');
      $default = !empty($tabDefinition['sort_order']['preferred']['default']) ? 'preferred_taxon' : $default;
    }
    if(isset($tabDefinition['sort_order']['taxon']) && !empty($tabDefinition['sort_order']['taxon']['enabled'])) {
      $options['taxon'] = lang::get('Taxon');
      $default = !empty($tabDefinition['sort_order']['taxon']['default']) ? 'taxon' : $default;
    }
    $showControl = FALSE;
    if(count($options) === 0) {
      $options['taxonomic_sort_order'] = lang::get('Taxonomic Sort Order');
    }
    if(count($options) === 1 && !empty($args['add_species_position']) &&  $args['add_species_position'] === 'above') {
      $ctrlHTML = '<input name="species-sort-order-' . $tabNum . '" type="radio" value="' . $default . '" style="display: none;" checked>';
    } else {
      $ctrlHTML = '<label>' . lang::get('Species sort order') . ':</label> ' .
        '<input type="radio" id="species-sort-order-' . $tabNum . '-none" name="species-sort-order-' . $tabNum . '" value="none" style="display:none;">' .
        '<div class="btn-group btn-group-sm btn-group-toggle" data-toggle="buttons">';
      foreach ($options as $key => $option) {
        $active = ($key === $default);
        $ctrlHTML .= '<label class="btn btn-primary' . ($active ? ' active' : '') . '">' .
          '<input type="radio" name="species-sort-order-' . $tabNum . '" autocomplete="off" value="' .
          $key . ($active ? '" checked>' : '">') . $option . '</label>';
      }
      $ctrlHTML .= '</div>';
      $showControl = TRUE;
    }
    return str_replace(
      ['{{class}}', '{{control}}', '{{display}}'],
      [$showControl ? '' : ' keepHidden', $ctrlHTML, $showRow ? '' : 'display: none;'],
      $template);
  }
  
  /*
   * Build HTML for species list autocompletes.
   * 
   * @param $tabNum
   * @param $tabDefinition
   * @param $args
   * @param $template
   * @return String
   */  
  private static function autocompleteControl($tabNum, $tabDefinition, $args, $template) {
    $ctrlHTML = '<span id="taxonLookupControlContainer' . $tabNum . '">' .
      '<label for="taxonLookupControl' . $tabNum . '" class="auto-width">' .
      lang::get('Search for or add species to list ({1})', lang::get($tabDefinition['title'])) .
      ':</label> ' .
      '<input id="taxonLookupControl' . $tabNum . '" name="taxonLookupControl' . $tabNum . '" class="willAutocomplete"></span>';
      
    return str_replace('{{control}}', $ctrlHTML, $template);
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
          'extraParams' => $read + array('view' => 'detail','id'=>$values['sample:location_id'],'deleted' => 'f'),
//          'caching' => FALSE
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
        'extraParams' => $read + array('view' => 'detail','parent_id'=>$values['sample:location_id'],'deleted' => 'f'),
        'nocache' => TRUE // may have recently added or removed a section
      ));
      if(isset($values['sample:id'])){
        $existingSubSamples = data_entry_helper::get_population_data(array(
          'table' => 'sample',
          'extraParams' => $read + array('view' => 'detail','parent_id'=>$values['sample:id'],'deleted' => 'f'),
          'nocache' => TRUE  // may have recently added or removed a section
        ));
      } else $existingSubSamples = array();
      $sampleMethods = helper_base::get_termlist_terms(array('read'=>$read), 'indicia:sample_methods', array('Transect Section'));
      $attributes = data_entry_helper::getAttributes(array(
        'valuetable' => 'sample_attribute_value',
        'attrtable' => 'sample_attribute',
        'key' => 'sample_id',
        'fieldprefix' => 'smpAttr',
        'extraParams'=>$read,
        'survey_id'=>$values['sample:survey_id'],
        'sample_method_id'=>$sampleMethods[0]['id'],
        'multiValue' => FALSE // ensures that array_keys are the list of attribute IDs.
      ));
      $smpDate = self::parseSingleDate($values['sample:date']);
      foreach($sections as $section){
        $smp = FALSE;
        $exists=FALSE;
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
                   'copyFields' => array('date_start' => 'date_start','date_end' => 'date_end','date_type' => 'date_type'));
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
              'copyFields' => array('date_start' => 'date_start','date_end' => 'date_end','date_type' => 'date_type'));
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
    return ($values['page'] === 'delete') ? $args['my_walks_page'] : '';
  }

  /*
   * Ajax function call: provided with taxon_meaning_id, location_id and date
   */
  public static function ajax_check_verification_rules($website_id, $password, $nid) {
    $ruleIDs = [];
    $warnings = [];
    $info = [];
    $ruleTypesDone = array('WithoutPolygon' => FALSE, 'PeriodWithinYear' => FALSE);

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
            'extraParams' => $readAuth + array('view' => 'detail',
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
                'sortdir' => 'DESC',
                'query' => json_encode(array('in' => array('id' => $ruleIDs, 'test_type' => array('WithoutPolygon', 'PeriodWithinYear')))))
        ));
        // we are assuming no reverse rules
        foreach ($rules as $rule) {
          if ($ruleTypesDone[$rule['test_type']] === TRUE) {
              continue;
            }
            $ruleTypesDone[$rule['test_type']] = TRUE;
            $metadata = data_entry_helper::get_population_data(array(
                'table' => 'verification_rule_metadatum',
                'extraParams' => $readAuth + array('view' => 'detail',
                    'verification_rule_id' => $rule['id'])
            ));
            switch ($rule['test_type']) {
                case 'WithoutPolygon' :
                    $vrmfield = self::vr_extract('metadata', $metadata, 'DataFieldName');
                    $info[] = array($vrmfield);
                    if ($vrmfield === NULL || $vrmfield !== 'Species') {
                      break;
                    }
                    // report is cacheable: the geometry for a particular parent location won't change, and the
                    // verification rule geometry will rarely change
                    $reportResult = data_entry_helper::get_population_data(array(
                        'report' => 'projects/ukbms/location_verification_intersection',
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
                    if ($vrmsurvey !== NULL && $vrmsurvey != $args['survey_id']) {
                      break;
                    }
                    $md = substr($_GET['date'], 5, 2) . substr($_GET['date'], 8, 2); // cut off year from front: Assume YYYY-MM-DD
                    // following logic copied from SQL in module
                    $fails = ($rule['reverse_rule'] === 't') !==
                    ((($vrmstart === NULL || $vrmend === NULL || $vrmstart <= $vrmend)
                      && (($vrmstart !== NULL && $md < $vrmstart) || ($vrmend !== NULL && $md > $vrmend)))
                      || (($vrmstart !== NULL && $vrmend !== NULL && $vrmstart > $vrmend)
                        && (($vrmstart !== NULL && $md < $vrmstart) && ($vrmend !== NULL && $md > $vrmend))));
                    if ($fails) {
                      $dateObj   = DateTime::createFromFormat('!m', substr($vrmstart, 0, 2));
                      $monthName1 = $dateObj->format('M');
                      $dateObj   = DateTime::createFromFormat('!m', substr($vrmend, 0, 2));
                      $monthName2 = $dateObj->format('M');
                      $warnings[] = $rule['error_message'] .
                      ' (' . ($vrmstart !== NULL ? substr($vrmstart, 2, 2) . ' ' . $monthName1 : '') .
                      '-' . ($vrmend !== NULL ? substr($vrmend, 2, 2) . ' ' . $monthName2 : '') . ')';
                    }
                    break;
            }
        }
      }
    }
    header('Content-type: application/json');
    echo json_encode(['taxon_meaning_id' => $_GET['taxon_meaning_id'], 'warnings'=> $warnings]);
  }

  /*
   * Ajax function call: provided with taxon_meaning_id, location_id and date
   */
  public static function ajax_taxon_autocomplete($website_id, $password, $nid) {
      
      $qField = $_GET['qfield'];
      $limit = $_GET['limit'];
      $_GET['q'] = trim($_GET['q']);
      if (strpos($_GET['q'], ' ') !== FALSE) {
          $_GET['q'] = str_replace(' ', '* ', $_GET['q']);
      } else if ($_GET['q'][0] !== '*') {
          $_GET['q'] = '*' . $_GET['q'];
      }
      $callback = $_GET['callback'];
      unset($_GET['callback']);
      if ($qField !== 'taxon') {
          $_GET['limit'] = 10 * $limit;
      }
      // qfield is the original value.
      $url = data_entry_helper::$base_url."/index.php/services/data/taxa_taxon_list";
      $url .= '?' . http_build_query($_GET);
      $session = curl_init($url);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
      $response1 = curl_exec($session);
      $entities = json_decode($response1, true);

      if ($qField !== 'taxon') {
          // if searching by preferred, can get many enties for the same preferred
          // In this case we search the normal taxon as well, but apply a filter to only allow
          // those entries that are the preferred, or have the taxon lang = local language

          // Map Drupal codes to 3 digit codes held in warehouses
          $langMap = [
            // "bg" > Bulgarian
            'cs' => 'ces', // Czech
            // => "cym", // Welsh
            'de' => 'deu', // German
            // 'el' => // Greek
            'en' => 'eng',
            'es' => 'spa', // Spanish
            // => 'fin', // Finnish
            // 'fr' => // French
            // => 'gla' // Gaelic (Scots)
            // => 'gle' // Irish
            'hr' => 'hrv', // Croatian
            'hu' => 'hun', // Hungarian
            // 'it' => // Italian
            // => 'lat', // Latin
            // => 'lit', // Lithuanian
            'nl' => 'nld', // Dutch
            'pl' => 'pol', // Polish
            // 'pt-pt' => // Portuguese
            // => 'rus' // Russian
            // 'sl' => // Slovenian
            'sv' => 'swe', // Swedish
            // 'tr' => // Turkish
          ];

          $account = \Drupal::currentUser();
          $lang = (isset($langMap[$account->getPreferredLangcode()]) ? $langMap[$account->getPreferredLangcode()] : 'eng');
          $_GET['language_iso'] = $lang;
          $_GET['qfield'] = 'taxon';
          $url = data_entry_helper::$base_url."/index.php/services/data/taxa_taxon_list";
          $url .= '?' . http_build_query($_GET);
          $session = curl_init($url);
          curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
          $response2 = curl_exec($session);
          $entities2 = json_decode($response2, true);
          // Want to keep all entries which have the correct taxon language
          // then remove any duplicates for them.
          $preferred = [];
          for ($i = 0; $i < count($entities2); $i++) {
              $preferred[] = $entities2[$i]['preferred_taxa_taxon_list_id'];
          }
          for ($i = 0; $i < count($entities); $i++) {
              if (!in_array($entities[$i]['preferred_taxa_taxon_list_id'], $preferred) &&
                  $entities[$i]['taxon'] !== $entities[$i]['preferred_taxon'] &&
                  $entities[$i]['language_iso'] === $lang) {
                $entities2[] = $entities[$i];
                $preferred[] = $entities[$i]['preferred_taxa_taxon_list_id'];
              }
          }
          for ($i = 0; $i < count($entities); $i++) {
              if (!in_array($entities[$i]['preferred_taxa_taxon_list_id'], $preferred) &&
                  $entities[$i]['taxon'] === $entities[$i]['preferred_taxon']) {
                $entities2[] = $entities[$i];
                $preferred[] = $entities[$i]['preferred_taxa_taxon_list_id'];
              }
          }
          usort($entities2, "ukbms_stis2_taxonSort_preferred");
          $entities = $entities2;
          if (count($entities) > $limit) {
              $entities = array_slice($entities, 0, $limit);
          }
      }
      
      header('Content-type: application/json');
      echo $callback . '(' . json_encode($entities) . ')';
  }
  
  private static function vr_extract($type, $data, $key, $length = NULL) {
    foreach ($data as $pair) {
      if ($pair['key'] == $key && ($length === NULL || strlen($pair['value']) === $length)) {
        return $pair['value'];
      }
    }
    return NULL;
  }

  /**
   * Declare the list of permissions we've got set up to pass to the CMS' permissions code.
   * @param int $nid Node ID, not used
   * @param array $args Form parameters array, used to extract the defined permissions.
   * @return array List of distinct permissions.
   */
  public static function get_perms($nid, $args) {
      $perms = array();
      if (!empty($args['managerPermission'])) {
          $perms[] = $args['managerPermission'];
      }
      if (!empty($args['branch_assignment_permission'])) {
          $perms[] = $args['branch_assignment_permission'];
      }
      $attributeConfiguration = json_decode($args['attribute_configuration'], TRUE);
      foreach ($attributeConfiguration as $attribute) {
          if (!empty($attribute['permission'])) {
              $perms[] = $attribute['permission'];
          }
      }
      return array_unique($perms);
  }
}

/**
 * custom functions for usort
 */
function ukbms_stis2_taxonSort_preferred($a, $b)
{
    return strcasecmp($a['preferred_taxon'], $b['preferred_taxon']);
}
function ukbms_stis2_taxonFilter($a)
{
    return strcasecmp($a['preferred_taxon'], $b['preferred_taxon']);
}

