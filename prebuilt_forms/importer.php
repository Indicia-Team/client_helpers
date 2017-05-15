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
 
require_once('includes/user.php');
require_once('includes/groups.php');
/**
 * Prebuilt Indicia data form that provides an import wizard
 *
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_importer {
  
  /** 
   * Return the form metadata. 
   * @return array The definition of the form.
   */
  public static function get_importer_definition() {
    return array(
      'title'=>'Importer',
      'category' => 'Utilities',
      'description'=>'A page containing a wizard for uploading CSV file data.',
      'helpLink'=>'https://readthedocs.org/projects/indicia-docs/en/latest/site-building/iform/prebuilt-forms/importer.html',
      'supportsGroups'=>true,
      'recommended' => true
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    return array(
      array(
        'name'=>'model',
        'caption'=>'Type of data to import',
        'description'=>'Select the type of data that each row represents in the file you want to import.',
        'type'=>'select',
        'options'=>array(
          'url' => 'Use setting in URL (&type=...)',
          'occurrence' => 'Species records',
          'sample' => 'Samples without records',
          'location' => 'Locations',
          'other' => 'Other (specify below)'
        ),
        'required'=>true
      ),
      array('name'=>'otherModel',
        'caption'=>'Other model',
        'description'=>'If type of data to import is set to other, then specify the singular name of the model to import into here.',
        'type'=>'text_input',
        'required'=>FALSE
      ),
      array(
        'name'=>'presetSettings',
        'caption'=>'Preset Settings',
        'description'=>'Provide a list of predetermined settings which the user does not need to specify, one on each line in the form name=value. '.
            'The preset settings available are those which are available for input on the first page of the import wizard, depending on the table you '.
            'are inputting data for. You can use the following replacement tokens in the values: {user_id}, {username}, {email} or {profile_*} (i.e. any '.
            'field in the user profile data).',
        'type'=>'textarea',
        'required'=>false
      ),
      array(
        'name'=>'occurrenceAssociations',
        'caption'=>'Allow import of associated occurrences',
        'description'=>'If the data might include 2 associated occurrences in a single row then this option must be enabled.',
        'type'=>'boolean',
        'default'=>false
      ),
      array(
        'name'=>'fieldMap',
        'caption'=>'Field/column mappings',
        'description'=>'Use this control to predefine mappings between database fields and columns in the spreadsheet. ' .
            'You can then use the mappings to describe a complete spreadsheet template for a given survey dataset ' .
            'structure, so the mappings page can be skipped. Or you can use this to define the possible database fields ' .
            'that are available to pick from when importing into a particular survey dataset.',
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
        }'
      ),
      array(
        'name'=>'onlyAllowMappedFields',
        'caption'=>'Only allow mapped fields',
        'description'=>'If this box is ticked and a survey is chosen which has fields defined above ' .
            'then only fields which are listed will be available for selection. All other fields will '.
            'be hidden from the mapping stage.',
        'type'=>'boolean',
        'default'=>true
      ),
      array(
        'name'=>'skipMappingIfPossible',
        'caption'=>'Skip the mapping step if possible',
        'description'=>'If this box is ticked and all the columns in the import spreadsheet can be automatically ' .
          'mapped to database fields, then the import mappings step is skipped. Therefore if you describe all the ' .
          'columns in the spreadsheet in the field/column mappings above the import process can be significantly ' .
          'simplified.',
        'type'=>'boolean',
        'default'=>false
      ),
    );
  }
  /**
   * Return the Indicia form code
   * @param array $args Input parameters.
   * @param array $nid Drupal node object's ID
   * @param array $response Response from Indicia services after posting a verification.
   * @return HTML string
   */
  public static function get_form($args, $nid, $response) {
    iform_load_helpers(array('import_helper'));
    // apply defaults
    $args = array_merge(array(
      'occurrenceAssociations' => false,
      'fieldMap' => array(),
      'onlyAllowMappedFields' => true,
      'skipMappingIfPossible' => false
    ), $args);
    $auth = import_helper::get_read_write_auth($args['website_id'], $args['password']);
    group_authorise_form($args, $auth['read']);
    if ($args['model']=='url') {
      if (!isset($_GET['type']))
        return "This form is configured so that it must be called with a type parameter in the URL";
      $model = $_GET['type'];
    } else
      $model = $args['model'] === 'other' ? $args['otherModel'] : $args['model'];
    if (empty($model))
      return "This form's import model is not properly configured.";
    if (isset($args['presetSettings'])) {
      $presets = get_options_array_with_user_data($args['presetSettings']);
      $presets = array_merge(array('website_id'=>$args['website_id'], 'password'=>$args['password']), $presets);
    } else {
      $presets = array('website_id'=>$args['website_id'], 'password'=>$args['password']);
    }
    
    if (!empty($_GET['group_id'])) {
      // loading data into a recording group. 
      $group = data_entry_helper::get_population_data(array(
        'table'=>'group',
        'extraParams'=>$auth['read'] + array('id'=>$_GET['group_id'], 'view'=>'detail')
      ));
      $group = $group[0];
      $presets['sample:group_id'] = $_GET['group_id'];      
      hostsite_set_page_title(lang::get('Import data into the {1} group', $group['title']));
      // if a single survey specified for this group, then force the data into the correct survey
      $filterdef = json_decode($group['filter_definition'], true);
      if (!empty($filterdef['survey_list_op']) && $filterdef['survey_list_op']==='in' && !empty($filterdef['survey_list'])) {
        $surveys = explode(',', $filterdef['survey_list']);
        if (count($surveys)===1)
          $presets['survey_id'] = $surveys[0];
      }
    }
    // If in training mode, set the flag on the imported records
    if (function_exists('hostsite_get_user_field') && hostsite_get_user_field('training'))
      $presets['occurrence:training'] = 't';
    try {
      $options = array(
        'model' => $model,
        'auth' => $auth,
        'presetSettings' => $presets,
        'occurrenceAssociations' => $args['occurrenceAssociations'],
        'fieldMap' => empty($args['fieldMap']) ? array() : json_decode($args['fieldMap'], true),
        'onlyAllowMappedFields' => $args['onlyAllowMappedFields'],
        'skipMappingIfPossible' => $args['skipMappingIfPossible']
      );
      $r = import_helper::importer($options);
    } catch (Exception $e) {
      hostsite_show_message($e->getMessage(), 'warning');
      $reload = import_helper::get_reload_link_parts();
      unset($reload['params']['total']);
      unset($reload['params']['uploaded_csv']);
      $reloadpath = $reload['path'] . '?' . import_helper::array_to_query_string($reload['params']);
      $r = "<p>".lang::get('Would you like to ')."<a href=\"$reloadpath\">".lang::get('import another file?')."</a></p>";
    }
    return $r;
  }
}