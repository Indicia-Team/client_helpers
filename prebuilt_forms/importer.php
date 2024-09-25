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
 * @link https://github.com/indicia-team/client_helpers/
 */

use IForm\prebuilt_forms\PageType;
use IForm\prebuilt_forms\PrebuiltFormInterface;

require_once 'includes/user.php';
require_once 'includes/groups.php';

/**
 * Prebuilt Indicia data form that provides an import wizard
 */
class iform_importer implements PrebuiltFormInterface {

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_importer_definition() {
    return [
      'title' => 'Importer',
      'category' => 'Utilities',
      'description' => 'A page containing a wizard for uploading CSV file data.',
      'helpLink' => 'https://readthedocs.org/projects/indicia-docs/en/latest/site-building/iform/prebuilt-forms/importer.html',
      'supportsGroups' => TRUE,
      'recommended' => TRUE,
    ];
  }

  /**
   * {@inheritDoc}
   */
  public static function getPageType(): PageType {
    return PageType::Utility;
  }

  /**
   * Get the list of parameters for this form.
   *
   * @return array
   *   List of parameters that this form requires.
   */
  public static function get_parameters() {
    return [
      [
        'name' => 'model',
        'caption' => 'Type of data to import',
        'description' => 'Select the type of data that each row represents in the file you want to import.',
        'type' => 'select',
        'options' => [
          'url' => 'Use setting in URL (&type=...)',
          'occurrence' => 'Species records',
          'sample' => 'Samples without records',
          'location' => 'Locations',
          'other' => 'Other (specify below)',
        ],
        'required' => TRUE,
      ],
      [
        'name' => 'otherModel',
        'caption' => 'Other model',
        'description' => 'If type of data to import is set to other, then specify the singular name of the model to import into here.',
        'type' => 'text_input',
        'required' => FALSE,
      ],
      [
        'name' => 'allowDataDeletions',
        'caption' => 'Allow deletions?',
        'description' => 'Should the Deleted flag be exposed as a column mapping field during the import process.',
        'type' => 'boolean',
        'default' => FALSE,
      ],
      [
        'name' => 'presetSettings',
        'caption' => 'Preset Settings',
        'description' => 'Provide a list of predetermined settings which the user does not need to specify, one on each line in the form name=value. ' .
            'The preset settings available should be chosen from those available for input on the Import settings page of the import wizard, depending ' .
            'on the table you are inputting data for. It is also possible to specify preset settings for the field names available for selection on the ' .
            'mappings page. You can use the following replacement tokens in the values: {user_id}, {username}, {email} or {profile_*} (i.e. any ' .
            'field in the user profile data).',
        'type' => 'textarea',
        'required' => FALSE,
      ],
      [
        'name' => 'occurrenceAssociations',
        'caption' => 'Allow import of associated occurrences',
        'description' => 'If the data might include 2 associated occurrences in a single row then this option must be enabled.',
        'type' => 'boolean',
        'default' => FALSE,
      ],
      [
        'name' => 'fieldMap',
        'caption' => 'Field/column mappings',
        'description' => 'Use this control to predefine mappings between database fields and columns in the spreadsheet. ' .
            'You can then use the mappings to describe a complete spreadsheet template for a given survey dataset ' .
            'structure, so the mappings page can be skipped. Or you can use this to define the possible database fields ' .
            'that are available to pick from when importing into a particular survey dataset. For non-survey based ' .
            'imports (e.g. locations), do not fill in the survey field.',
        'type' => 'jsonwidget',
        'schema' => '{
          "type":"seq",
          "title":"Surveys list",
          "desc":"Add each survey for which you want to have a predefined spreadsheet template to this list",
          "sequence": [{
            "type":"map",
            "title":"Survey",
            "desc":"List of surveys which have associated automatic mappings",
            "mapping": {
              "survey_id": {
                "title":"Survey ID",
                "type":"str"
              },
              "fields": {
                "title":"Database field list",
                "type":"txt",
                "desc":"List of database fields with optional column names to map to them. Add an row ' .
                  'for each database field (e.g. sample:location_name or smpAttr:4). If a spreadsheet column ' .
                  'with a given title should be automatically mapped to this field then add an equals followed ' .
                  'by the column title, e.g. sample:date=Record date."
              }
            }
          }]
        }',
      ],
      [
        'name' => 'onlyAllowMappedFields',
        'caption' => 'Only allow mapped fields',
        'description' => 'If this box is ticked and a survey is chosen which has fields defined above ' .
            'then only fields which are listed will be available for selection. All other fields will ' .
            'be hidden from the mapping stage.',
        'type' => 'boolean',
        'default' => TRUE,
      ],
      [
        'name' => 'skipMappingIfPossible',
        'caption' => 'Skip the mapping step if possible',
        'description' => 'If this box is ticked and all the columns in the import spreadsheet can be automatically ' .
          'mapped to database fields, then the import mappings step is skipped. Therefore if you describe all the ' .
          'columns in the spreadsheet in the field/column mappings above the import process can be significantly ' .
          'simplified.',
        'type' => 'boolean',
        'default' => FALSE,
      ],
      [
        'name' => 'existingRecordLookupMethod',
        'caption' => 'Existing record lookup method',
        'description' => 'If a fixed method of looking up an existing record is required rather than allowing user ' .
          'selection then choose it here',
        'required' => FALSE,
        'type' => 'select',
        'options' => [
          'Occurrence: Occurrence ID' => 'Occurrence: Occurrence ID',
          'Occurrence: Occurrence External Key' => 'Occurrence: Occurrence External Key',
          'Occurrence: Sample and Taxon' => 'Occurrence: Sample and Taxon',
          'Sample: Sample ID' => 'Sample: Sample ID',
          'Sample: Sample External Key' => 'Sample: Sample External Key',
          'Sample: Grid Ref and Date' => 'Sample: Grid Ref and Date',
          'Sample: Location Record and Date' => 'Sample: Location Record and Date',
        ],
      ],
      [
        'name' => 'existingRecordLookupMethodCustom',
        'caption' => 'Custom existing record lookup method',
        'description' => 'To specify a lookup method for existing records that is not covered in the list above, ' .
          'paste its JSON here.',
        'required' => FALSE,
        'type' => 'textarea',
      ],
      [
        'name' => 'importMergeFields',
        'caption' => 'Merge Field mappings',
        'description' => 'Use this control to define virtual submission fields and how they are merged into a real field before submission.',
        'type' => 'jsonwidget',
        'schema' => '{
          "type":"seq",
          "title":"Models list",
          "desc":"A lists of models (e.g. &quot;location&quot;) for which you want to define one or more fields that are merged into another before submission.",
          "sequence": [{
            "type":"map",
            "title":"Model",
            "desc":"A model which has associated merged virtual fields",
            "mapping": {
              "model": {
                "title":"Model",
                "type":"str",
                "desc":"Name of the model (e.g. &quot;location&quot;, note singular, not plural) which has associated merged virtual fields",
              },
              "fields": {
                "type":"seq",
                "title":"Field Definition list",
                "desc":"A list of target field and the associated virtual fields which are merged to form it.",
                "sequence": [{
                  "type":"map",
                  "title":"Field Definition",
                  "desc":"A target field and the associated virtual fields.",
                  "mapping": {
                    "fieldName": {
                      "title":"Field",
                      "type":"str",
                      "desc":"The name of the target field: this should be a valid existing field.",
                    },
                    "description": {
                      "title":"Description",
                      "type":"str",
                      "desc":"Field description used in drop down entries for the virtual fields.",
                    },
                    "joiningString": {
                      "title":"Joining string",
                      "type":"str",
                      "desc":"A string which is placed between the virtual fields when they are merged. Use &quot;&lt;newline&gt;&quot; to include a CR/LF pair.",
                    },
                    "virtualFields": {
                      "type":"seq",
                      "title":"Virtual Field list",
                      "desc":"A List of virtual field definitions, which are merged to entry for each target field and the associated virtual fields which are merged to form it.",
                      "sequence": [{
                        "type":"map",
                        "title":"Field Definition",
                        "desc":"A target field and the associated virtual fields.",
                        "mapping": {
                          "fieldNameSuffix": {
                            "title":"Field Name Suffix",
                            "type":"str",
                            "desc":"An identifying string that is added to the end of the main field name, which forms the field name for the virtual field.",
                          },
                          "description": {
                            "title":"Description",
                            "type":"str",
                            "desc":"A description of this virtual field: this is what appears in the drop down list.",
                          },
                          "dataPrefix": {
                            "title":"Data Prefix",
                            "type":"str",
                            "desc":"Text which is added to the start of this field before it is merged into the main field.",
                          },
                          "dataSuffix": {
                            "title":"Data Suffix",
                            "type":"str",
                            "desc":"Text which is added to the start of this field before it is merged into the main field.",
                          }
                        }
                      }]
                    }
                  }
                }]
              }
            }
          }]
        }'
      ],
      [
        'name' => 'embedReupload',
        'caption' => 'Embed reupload form in last page',
        'description' => 'Use this option to control whether the last page, shown after an import, includes an embedded form for uploading another file or not.',
        'type' => 'select',
        'options' => [
          0 => 'No embedded form, provide link back to upload page',
          1 => 'Provide embedded upload form after an import that resulted in errors',
          2 => 'Provide embedded upload form after any import',
        ],
      ],
      [
        'name' => 'synonymProcessing',
        'caption' => 'Synonym Processing',
        'description' => 'Use this control to define how synonyms are handled. Currently only relevant for taxa imports.',
        'type' => 'jsonwidget',
        'schema' => '{
          "type":"map",
          "title":"Synonym Processing",
          "desc":"Synonym Processing Definition",
          "mapping": {
            "separateSynonyms": {
              "title":"Separate Synonyms",
              "type":"bool",
              "desc":"Are synonyms going to be input on separate rows?",
            },
            "mainValues": {
              "type":"seq",
              "title":"Main Record values",
              "desc":"There is a special column in the import used to identify which records are the main records and which are the synonyms. This is the list of values for the main records.",
              "sequence": [{
                "type":"str",
                "title":"Main Record Value",
                "desc":"A value used to identify a main record.",
              }]
            },
            "synonymValues": {
              "type":"seq",
              "title":"Synonym Record values",
              "desc":"There is a special column in the import used to identify which records are the main records and which are the synonyms. This is the list of values for the synonym records.",
              "sequence": [{
                "type":"str",
                "title":"Synonym Record Value",
                "desc":"A value used to identify a synonym record.",
              }]
            },
          },
        }',
      ],
      [
        'name' => 'importPreventCommitBehaviour',
        'caption' => 'Importer Prevent Commit Behaviour',
        'description' => '<em>Prevent all commits on error</em> - Rows are only imported once all errors are corrected. Please note: Functionality to update '
        . 'existing data is currently disabled when this option is selected, only new data can be imported.'
        . '<em>Only commit valid rows</em> - Import rows which do not error. '
        . '<em>Allow user to choose</em> - Give the user the option to choose which behaviour they want with a checkbox.',
        'type' => 'select',
        'options' => [
          'prevent' => 'Prevent all commits on error',
          'partial_import' => 'Only commit valid rows',
          'user_defined' => 'Allow user to choose',
        ],
        'required' => TRUE,
        'default' => 'partial_import',
        'group' => 'Import Behaviour',
      ],
      [
        'name' => 'importSampleLogic',
        'caption' => 'Importer Sample Logic (only applicable when using the Species Records import type)',
        'description' => '<em>Verify using sample external key. Rows with the same sample external key must be consistent</em> - '.
        'Allows verification of samples using the sample external key field to determine consistency between the import rows. Rows from same sample must still be placed on consecutive rows. '
          . '<em>Do not use sample key verification</em> - Rows are placed into the same sample based on comparison of '
          . 'sample related columns of consecutive rows without taking sample external key into account. '
          . '<em>Allow user to choose</em> - Give the user the option to choose which behaviour they want with a checkbox.',
        'type' => 'select',
        'options' => [
          'sample_ext_key' => 'Verify using sample external key. Rows with the same sample external key must be consistent',
          'consecutive_rows' => 'Do not use sample key verification',
          'user_defined' => 'Allow user to choose',
        ],
        'required' => TRUE,
        'default' => 'consecutive_rows',
        'group' => 'Import Behaviour',
      ],
    ];
  }

  /**
   * Return the Indicia form code.
   *
   * @param array $args
   *   Input parameters.
   * @param array $nid
   *   Drupal node object's ID.
   * @param array $response
   *   Response from Indicia services after posting a verification.
   *
   * @return string
   *   HTML string.
   */
  public static function get_form($args, $nid, $response) {
    if (empty($args['importPreventCommitBehaviour'])) {
      $args['importPreventCommitBehaviour'] = 'partial_import';
    }
    if (empty($args['importSampleLogic'])) {
      $args['importSampleLogic'] = 'consecutive_rows';
    }
    iform_load_helpers(['import_helper']);
    // Apply defaults.
    $args = array_merge([
      'occurrenceAssociations' => FALSE,
      'fieldMap' => [],
      'allowDataDeletions' => FALSE,
      'onlyAllowMappedFields' => TRUE,
      'skipMappingIfPossible' => FALSE,
      'importMergeFields' => [],
      'synonymProcessing' => new stdClass(),
    ], $args);
    $auth = import_helper::get_read_write_auth($args['website_id'], $args['password']);
    group_authorise_form($args, $auth['read']);
    if ($args['model'] === 'url') {
      if (!isset($_GET['type']))
        return "This form is configured so that it must be called with a type parameter in the URL";
      $model = $_GET['type'];
    }
    else {
      $model = $args['model'] === 'other' ? $args['otherModel'] : $args['model'];
    }
    if (empty($model)) {
      return "This form's import model is not properly configured.";
    }
    if (isset($args['presetSettings'])) {
      $presets = get_options_array_with_user_data($args['presetSettings']);
      $presets = array_merge(array('website_id' => $args['website_id'], 'password' => $args['password']), $presets);
    }
    else {
      $presets = ['website_id' => $args['website_id'], 'password' => $args['password']];
    }

    if (!empty($_GET['group_id'])) {
      // Loading data into a recording group.
      $group = data_entry_helper::get_population_data([
        'table' => 'group',
        'extraParams' => $auth['read'] + [
          'id' => $_GET['group_id'],
          'view' => 'detail',
        ],
      ]);
      $group = $group[0];
      if (!empty($model) && $model === 'groups_location') {
        $presets['groups_location:group_id'] = $_GET['group_id'];
      }
      else {
        $presets['sample:group_id'] = $_GET['group_id'];
      }
      hostsite_set_page_title(lang::get('Import data into the {1} group', $group['title']));
      // If a single survey specified for this group, then force the data into
      // the correct survey.
      $filterdef = json_decode($group['filter_definition'] ?? '', TRUE);
      if (!empty($filterdef['survey_list_op']) && $filterdef['survey_list_op'] === 'in' && !empty($filterdef['survey_list'])) {
        $surveys = explode(',', $filterdef['survey_list']);
        if (count($surveys) === 1) {
          $presets['survey_id'] = $surveys[0];
        }
      }
    }
    // If in training mode, set the flag on the imported records.
    if (function_exists('hostsite_get_user_field') && hostsite_get_user_field('training')) {
      $presets['sample:training'] = 't';
      $presets['occurrence:training'] = 't';
    }
    // Allow presets to come from URL params
    // Must be preceded with "dynamic-" (works in same way as report pages that use URL params)
    // e.g. A parameter such as dynamic-location:fk_parent:id=1 passed to the importer
    // would set a location's parent to location 1.
    if (!empty($_GET)) {
      foreach ($_GET as $getKey => $getValue) {
        if (substr($getKey,0,8) === 'dynamic-'
            && $getKey !== 'dynamic-website_id'
            && $getKey !== 'dynamic-password') {
          //Remove dynamic- from front of param before placing in presets
          $presets[str_replace("dynamic-", "", $getKey)] = $getValue;
        }
      }
    }
    try {
      $existingRecordLookupMethod = empty($args['existingRecordLookupMethod']) ?
        $args['existingRecordLookupMethodCustom'] : self::lookupConfig($args['existingRecordLookupMethod']);
      $options = [
        'model' => $model,
        'auth' => $auth,
        'presetSettings' => $presets,
        'allowDataDeletions' => $args['allowDataDeletions'],
        'occurrenceAssociations' => $args['occurrenceAssociations'],
        'fieldMap' => empty($args['fieldMap']) ? [] : json_decode($args['fieldMap'], TRUE),
        'onlyAllowMappedFields' => $args['onlyAllowMappedFields'],
        'skipMappingIfPossible' => $args['skipMappingIfPossible'],
        'existingRecordLookupMethod' => $existingRecordLookupMethod,
        'importPreventCommitBehaviour' => $args['importPreventCommitBehaviour'],
        'importSampleLogic' => $args['importSampleLogic'],
        'importMergeFields' => $args['importMergeFields'],
        'synonymProcessing' => $args['synonymProcessing'],
        'switches' => ['activate_global_sample_method' => 't'],
        'embed_reupload' => isset($args['embedReupload']) ? $args['embedReupload'] : EMBED_REUPLOAD_OFF,
      ];
      $r = import_helper::importer($options);
    }
    catch (Exception $e) {
      hostsite_show_message($e->getMessage(), 'warning');
      $reload = import_helper::get_reload_link_parts();
      unset($reload['params']['total']);
      unset($reload['params']['uploaded_csv']);
      $reloadpath = $reload['path'] . '?' . import_helper::array_to_query_string($reload['params']);
      $r = "<p>" . lang::get('Would you like to ') . "<a href=\"$reloadpath\">" . lang::get('import another file?') . "</a></p>";
    }
    return $r;
  }

  /**
   * Retrieve the configuration required for existing record lookups.
   */
  private static function lookupConfig($method) {
    $arr = [
      'Occurrence: Occurrence ID' => json_encode([
        ['fieldName' => 'website_id', 'notInMappings' => TRUE],
        ['fieldName' => 'occurrence:id'],
      ]),
      'Occurrence: Occurrence External Key' => json_encode([
        ['fieldName' => 'website_id', 'notInMappings' => TRUE],
        ['fieldName' => 'occurrence:external_key'],
      ]),
      'Occurrence: Sample and Taxon' => json_encode([
        ['fieldName' => 'website_id', 'notInMappings' => TRUE],
        ['fieldName' => 'occurrence:sample_id', 'notInMappings' => TRUE],
        ['fieldName' => 'occurrence:taxa_taxon_list_id'],
      ]),
      'Sample: Sample ID' => json_encode([
        ['fieldName' => 'survey_id', 'notInMappings' => TRUE],
        ['fieldName' => 'sample:id'],
      ]),
      'Sample: Sample External Key' => json_encode([
        ['fieldName' => 'survey_id', 'notInMappings' => TRUE],
        ['fieldName' => 'sample:sample_method_id'],
        ['fieldName' => 'sample:external_key'],
      ]),
      'Sample: Grid Ref and Date' => json_encode([
        ['fieldName' => 'survey_id', 'notInMappings' => TRUE],
        ['fieldName' => 'sample:sample_method_id'],
        ['fieldName' => 'sample:entered_sref'],
        ['fieldName' => 'sample:date'],
      ]),
      'Sample: Location Record and Date' => json_encode([
        ['fieldName' => 'survey_id', 'notInMappings' => TRUE],
        ['fieldName' => 'sample:sample_method_id'],
        ['fieldName' => 'sample:location_id'],
        ['fieldName' => 'sample:date'],
      ]),
    ];
    return $arr[$method];
  }

}
