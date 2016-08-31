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
 * Prebuilt Indicia data form that provides an import wizard for the Plant Portal project.
 *
 * @package Client
 * @subpackage PrebuiltForms
 */

//AVB To Do - Need to test import file with a mixture of spatial reference systems
//AVB To DO  - Test importer with various attributes


//Importer changed to extend helper_base as the plant portal importer needs customer versions of many of the functions
//that were previously in the import_helper (and the import_helper extends helper_base)
class iform_plant_portal_user_data_importer extends helper_base {  
  //AVB To Do - This variable's functionality comes from the standard importer, needs testing if it works with Plant Portal importer
  /**
   * @var boolean Flag set to true if the host system is capable of storing our user's remembered import mappings
   * for future imports.
   */
  private static $rememberingMappings=true;

  /**
   * @var array List of field to column mappings that we managed to set automatically
   */
  private static $automaticMappings=array();
  
  /** 
   * Return the form metadata. 
   * @return array The definition of the form.
   */
  /** 
   * Return the form metadata. 
   * @return array The definition of the form.
   */
  public static function get_plant_portal_user_data_importer_definition() {
    return array(
      'title'=>'Plant Portal Data Importer',
      'category' => 'Utilities',
      'description'=>'A page for importing samples and occurrences for the Plant Portal project.',
      'recommended' => false
    );
  }
  
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    return array(
      //AVB To DO - Need to test if this functionality still works with the Plant Portal importer
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
     //AVB To DO - Need to test if this functionality still works with the Plant Portal importer
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
        'name'=>'override_survey_id',
        'caption'=>'Survey ID',
        'description'=>'ID of the survey that the records should go into.',
        'type'=>'string',
        'required'=>true,
        'group'=>'Database IDs Required By Form'
      ),
      array(
        'name'=>'override_taxon_list_id',
        'caption'=>'Taxon list ID',
        'description'=>'ID of the taxon list records should go into.',
        'type'=>'string',
        'required'=>true,
        'group'=>'Database IDs Required By Form'
      ),
      array(
        'name'=>'sample_group_identifier_name_attr_id',
        'caption'=>'Sample group identifier name attribute ID',
        'description'=>'ID of the sample group identifier name sample attribute.',
        'type'=>'string',
        'required'=>true,
        'group'=>'Database IDs Required By Form'
      ),
      array(
        'name'=>'plot_group_identifier_name_attr_id',
        'caption'=>'Plot group identifier name location attribute ID',
        'description'=>'ID of the plot group identifier name sample attribute.',
        'type'=>'string',
        'required'=>true,
        'group'=>'Database IDs Required By Form'
      ),
      array(
        'name'=>'sample_group_permission_person_attr_id',
        'caption'=>'Sample group permissions person attribute ID',
        'description'=>'ID of person attribute that holds a user\'s sample group permissions.',
        'type'=>'string',
        'required'=>true,
        'group'=>'Database IDs Required By Form'
      ),
      array(
        'name'=>'plot_group_permission_person_attr_id',
        'caption'=>'Plot group permissions person attribute ID',
        'description'=>'ID of person attribute that holds a user\'s plot group permissions.',
        'type'=>'string',
        'required'=>true,
        'group'=>'Database IDs Required By Form'
      ),
      array(
        'name'=>'sample_group_termlist_id',
        'caption'=>'Id of the sample group termlist',
        'description'=>'ID of termlist used to store sample groups.',
        'type'=>'string',
        'required'=>true,
        'group'=>'Database IDs Required By Form'
      ),
      array(
        'name'=>'plot_group_termlist_id',
        'caption'=>'Id of the plot group termlist',
        'description'=>'ID of termlist used to store plot groups.',
        'type'=>'string',
        'required'=>true,
        'group'=>'Database IDs Required By Form'
      ),
       array(
        'name'=>'import_fatal_errors_screen_instructions',
        'caption'=>'Warnings screen instructions for fatal error',
        'description'=>'Override the instructions given on the warnings screen when a fatal issue has occurred with the import.',
        'type'=>'textarea',
        'group'=>'Customisable form text'
      ),
      array(
        'name'=>'import_warnings_screen_instructions',
        'caption'=>'Warnings screen instructions',
        'description'=>'Override the default instructions given on the warnings screen.',
        'type'=>'textarea',
        'group'=>'Customisable form text'
      ),
      array(
        'name'=>'reupload_label',
        'caption'=>'Text next to upload file again button',
        'description'=>'Override the default label next to the upload again button on the warnings screen.',
        'type'=>'string',
        'group'=>'Customisable form text'
      ),
      array(
        'name'=>'continue_import_label',
        'caption'=>'Text next to the continue with import button',
        'description'=>'Override the default label next to the continue import button on the warnings screen.',
        'type'=>'string',
        'group'=>'Customisable form text'
      ),
      array(
        'name'=>'reupload_button',
        'caption'=>'Upload file again button text',
        'description'=>'Override the default text shown on the upload again button on the warnings screen.',
        'type'=>'string',
        'group'=>'Customisable form text'
      ),
      array(
        'name'=>'continue_button',
        'caption'=>'Continue with import button text',
        'description'=>'Override the default text shown on the continue with import button on the warnings screen.',
        'type'=>'string',
        'group'=>'Customisable form text'
      ),
      array(
        'name'=>'loading_import_wait_text',
        'caption'=>'Loading import wait text',
        'description'=>'Text shown next to button while the import loads.',
        'type'=>'string',
        'group'=>'Customisable form text'
      ),
      array(
        'name'=>'nS-nSG-nP-nPG_message',
        'caption'=>'Custom message for nS-nSG-nP-nPG import situation',
        'description'=>'The message shown to the user for rows where new samples, news sample groups (where applicable), new plots and new plot groups (where applicable) are all required.',
        'type'=>'textarea',
        'required'=>true,
        'group'=>'Custom import warnings'
      ),
       array(
        'name'=>'eS_message',
        'caption'=>'Custom message for eS import situation',
        'description'=>'The message shown to the user for rows where an existing sample is to have occurrences attached to it.',
        'type'=>'textarea',
        'required'=>true,
        'group'=>'Custom import warnings'
      ),
      array(
        'name'=>'eP-nSG_message',
        'caption'=>'Custom message for eP-nSG import situation',
        'description'=>'The message shown to the user for rows where a new sample with a new sample group (where applicable) and existing plot are required.',
        'type'=>'textarea',
        'required'=>true,
        'group'=>'Custom import warnings'
      ),
      array(
        'name'=>'eP-eSG_message',
        'caption'=>'Custom message for eP-eSG import situation',
        'description'=>'The message shown to the user for rows where a new sample with a existing sample group and existing plot are required.',
        'type'=>'textarea',
        'required'=>true,
        'group'=>'Custom import warnings'
      ),
      array(
        'name'=>'eSG_message',
        'caption'=>'Custom message for eSG import situation',
        'description'=>'The message shown to the user for rows where a new sample with existing sample group and new plot is required.',
        'type'=>'textarea',
        'required'=>true,
        'group'=>'Custom import warnings'
      ),
      array(
        'name'=>'eSG-ePG_message',
        'caption'=>'Custom message for eSG-ePG import situation',
        'description'=>'The message shown to the user for rows where a new sample with existing sample group and new plot with existing plot group is required.',
        'type'=>'textarea',
        'required'=>true,
        'group'=>'Custom import warnings'
      ),
      array(
        'name'=>'ePG_message',
        'caption'=>'Custom message for ePG import situation',
        'description'=>'The message shown to the user for rows where a new sample with new sample group (where applicable) and new plot with existing plot group is required.',
        'type'=>'textarea',
        'required'=>true,
        'group'=>'Custom import warnings'
      ),
      array(
        'name'=>'eSwD_message',
        'caption'=>'Custom message for eSwD import situation',
        'description'=>'The message shown to the user for rows where multiple matching existing samples have been detected and therefore the import cannot continue until '
          . 'this has been resolved.',
        'type'=>'textarea',
        'required'=>true,
        'group'=>'Custom fatal import errors'
      ),
      array(
        'name'=>'ePwD_message',
        'caption'=>'Custom message for ePwD import situation',
        'description'=>'The message shown to the user for rows where a new sample is required but there are '
          . ' multiple matching existing plots available to use, and therefore the import cannot continue until '
          . 'this has been resolved.',
        'type'=>'textarea',
        'required'=>true,
        'group'=>'Custom fatal import errors'
      ),
      array(
        'name'=>'ePwD-eSGwD_message',
        'caption'=>'Custom message for ePwD-eSGwD import situation',
        'description'=>'The message shown to the user for rows where a new sample is required but there are '
          . ' both multiple matching existing sample groups as well as multiple matching existing plots available to use'
          . ' and therefore the import cannot continue until this has been resolved.',
        'type'=>'textarea',
        'required'=>true,
        'group'=>'Custom fatal import errors'
      ),
      array(
        'name'=>'eSGwD_message',
        'caption'=>'Custom message for eSGwD import situation',
        'description'=>'The message shown to the user for rows where a new sample is required but there are '
          . ' multiple matching existing sample groups available to use'
          . ' and therefore the import cannot continue until this has been resolved.',
        'type'=>'textarea',
        'required'=>true,
        'group'=>'Custom fatal import errors'
      ),
      array(
        'name'=>'ePGwD_message',
        'caption'=>'Custom message for ePGwD import situation',
        'description'=>'The message shown to the user for rows where a new sample and plot is required but there are '
          . ' multiple matching existing plot groups available to use'
          . ' and therefore the import cannot continue until this has been resolved.',
        'type'=>'textarea',
        'required'=>true,
        'group'=>'Custom fatal import errors'
      ),
      array(
        'name'=>'eSGwD-ePGwD_message',
        'caption'=>'Custom message for eSGwD-ePGwD import situation',
        'description'=>'The message shown to the user for rows where a new sample and plot is required but there are '
          . ' multiple matching existing sample groups and plot groups available to use'
          . ' and therefore the import cannot continue until this has been resolved.',
        'type'=>'textarea',
        'required'=>true,
        'group'=>'Custom fatal import errors'
      )  
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
    $args['nonFatalImportTypes'] = array(
            'nS-nSG-nP-nPG'=>array('existingSample'=>0,'existingPlot'=>0,'newSampleExistingSampleGroup'=>0,'newPlotExistingPlotGroup'=>0),
            'eS'=>array('existingSample'=>1,'existingPlot'=>0,'newSampleExistingSampleGroup'=>0,'newPlotExistingPlotGroup'=>0),
            'eP-nSG'=>array('existingSample'=>0,'existingPlot'=>1,'newSampleExistingSampleGroup'=>0,'newPlotExistingPlotGroup'=>0),
            'eP-eSG'=>array('existingSample'=>0,'existingPlot'=>1,'newSampleExistingSampleGroup'=>1,'newPlotExistingPlotGroup'=>0),
            'eSG'=>array('existingSample'=>0,'existingPlot'=>0,'newSampleExistingSampleGroup'=>1,'newPlotExistingPlotGroup'=>0),
            'eSG-ePG'=>array('existingSample'=>0,'existingPlot'=>0,'newSampleExistingSampleGroup'=>1,'newPlotExistingPlotGroup'=>1),
            'ePG'=>array('existingSample'=>0,'existingPlot'=>0,'newSampleExistingSampleGroup'=>0,'newPlotExistingPlotGroup'=>1));
    
    $args['fatalImportTypes'] = array(
            'eSwD'=>array('existingSample'=>2,'existingPlot'=>0,'newSampleExistingSampleGroup'=>0,'newPlotExistingPlotGroup'=>0),
            'eSGwD'=>array('existingSample'=>0,'existingPlot'=>0,'newSampleExistingSampleGroup'=>2,'newPlotExistingPlotGroup'=>0),
            'ePwD'=>array('existingSample'=>0,'existingPlot'=>2,'newSampleExistingSampleGroup'=>0,'newPlotExistingPlotGroup'=>0),
            'ePwD-eSGwD'=>array('existingSample'=>0,'existingPlot'=>2,'newSampleExistingSampleGroup'=>2,'newPlotExistingPlotGroup'=>0),
            'ePGwD'=>array('existingSample'=>0,'existingPlot'=>0,'newSampleExistingSampleGroup'=>0,'newPlotExistingPlotGroup'=>2),
            'eSGwD-ePGwD'=>array('existingSample'=>0,'existingPlot'=>0,'newSampleExistingSampleGroup'=>2,'newPlotExistingPlotGroup'=>2));

    //AVB To DO - At the moment the importer will only support occurrences with samples, but we might need to open this up to
    //allow location (plot) only data to be imported. Location import is already supported by the standard importer, so we might
    //be able to grab quite a lot of code from there.
    $args['model']='occurrence';
    if (empty($args['override_survey_id'])||empty($args['override_taxon_list_id'])||empty($args['sample_group_identifier_name_attr_id'])||empty($args['plot_group_identifier_name_attr_id'])||
            empty($args['sample_group_permission_person_attr_id'])||empty($args['plot_group_permission_person_attr_id'])||
            empty($args['sample_group_termlist_id'])||empty($args['plot_group_termlist_id']))
    return '<div>Not all the parameters for the page have been filled in. Please filled in all the parameters on the Edit Tab.</div>';
    
    foreach ($args['nonFatalImportTypes'] as $importTypeCode=>$importTypeStates) {
      if (empty($args[$importTypeCode.'_message'])) {
        return '<div>Please make sure all the non-fatal import warnings have been specified on the Edit Tab.</div>';
      }
    }
    
    foreach ($args['fatalImportTypes'] as $importTypeCode=>$importTypeStates) {
      if (empty($args[$importTypeCode.'_message'])) {
        return '<div>Please make sure all the fatal import error messages have been specified on the Edit Tab.</div>';
      }
    }

    // apply defaults
    $args = array_merge(array(
      'occurrenceAssociations' => false,
      'fieldMap' => array(),
      'onlyAllowMappedFields' => true,
      'skipMappingIfPossible' => false
    ), $args);
    $auth = self::get_read_write_auth($args['website_id'], $args['password']);
    group_authorise_form($args, $auth['read']);

    $model = $args['model'];

    $presets = array('website_id'=>$args['website_id'], 'password'=>$args['password']);

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
      
      $r = self::importer($args,$options);
    } catch (Exception $e) {
      hostsite_show_message($e->getMessage(), 'warning');
      $reload = self::get_reload_link_parts();
      unset($reload['params']['total']);
      unset($reload['params']['uploaded_csv']);
      $reloadpath = $reload['path'] . '?' . self::array_to_query_string($reload['params']);
      $r = "<p>".lang::get('Would you like to ')."<a href=\"$reloadpath\">".lang::get('import another file?')."</a></p>";
    }
    return $r;
  }
  
    /**
   * Returns the HTML for a simple file upload form.
   */
  private static function upload_form() {
    $reload = self::get_reload_link_parts();
    $reloadpath = $reload['path'] . '?' . self::array_to_query_string($reload['params']);
    $r = '<form action="'.$reloadpath.'" method="post" enctype="multipart/form-data">';
    $r .= '<label for="upload">'.lang::get('Select *.csv (comma separated values) file to upload').':</label>';
    $r .= '<input type="file" name="upload" id="upload"/>';
    $r .= '<input type="Submit" value="'.lang::get('Upload').'"></form>';
    return $r;
  }

  /**
   * Generates the import settings form. If none available, then outputs the upload mappings form.
   * @param array $options Options array passed to the import control.
   */
  private static function import_settings_form($args,$options) {
    $_SESSION['uploaded_file'] = self::get_uploaded_file($options);

    // by this time, we should always have an existing file
    if (empty($_SESSION['uploaded_file'])) throw new Exception('File to upload could not be found');
    $request = parent::$base_url."index.php/services/plant_portal_import/get_plant_portal_import_settings/".$options['model'];

    $request .= '?'.self::array_to_query_string($options['auth']['read']);

    $response = self::http_post($request, array());
    if (!empty($response['output'])) {
      // get the path back to the same page
      $reload = self::get_reload_link_parts();

      $reloadpath = $reload['path'] . '?' . self::array_to_query_string($reload['params']);
      $r = "<form method=\"post\" id=\"entry_form\" action=\"$reloadpath\" class=\"iform\">\n".
          "<fieldset><legend>".lang::get('Import Settings')."</legend>\n";
      $formArray = json_decode($response['output'], true);
      if (!is_array($formArray)) {
        if (class_exists('kohana')) {
          kohana::log('error', 'Problem occurred during upload. Sent request to get_plant_portal_import_settings and received invalid response.');
          kohana::log('error', "Request: $request");
          kohana::log('error', 'Response: '.print_r($response, true));
        }
        return 'Could not upload file. Please check that the plant_portal_import module is enabled on the Warehouse.';
      }
      $formOptions = array(
        'form' => $formArray,
        'readAuth' => $options['auth']['read'],
        'nocache'=>true
      );
      if (isset($options['presetSettings'])) {
        // skip parts of the form we have a preset value for
        $formOptions['extraParams'] = $options['presetSettings'];
      }
      
      //Don't display the survey drop-down on the setting form as this has been specified in the $args
      if (!empty($args['override_survey_id'])) {
        unset($formOptions['form']['survey_id']);
        $r .= '<input id="survey_id" name="survey_id" type="hidden" value = "'.$args['override_survey_id'].'">';
      }
      
      //Don't display the species list drop-down on the setting form as this has been specified in the $args
      if (!empty($args['override_taxon_list_id'])) {
        unset($formOptions['form']['occurrence:fkFilter:taxa_taxon_list:taxon_list_id']);
        $r .= '<input id="occurrence:fkFilter:taxa_taxon_list:taxon_list_id" name="occurrence:fkFilter:taxa_taxon_list:taxon_list_id" type="hidden" value = "'.$args['override_taxon_list_id'].'">';
      }
      
      //For Plant Portal, all records are uploaded as in-progress, until the user approves the records for final submission.
      unset($formOptions['form']['occurrence:record_status']);
      $r .= '<input id="occurrence:record_status" name="occurrence:record_status" type="hidden" value = "I">';
      
      $form = self::build_params_form($formOptions, $hasVisibleContent);
      // If there are no settings required, skip to the next step.
      if (!$hasVisibleContent)
        return self::upload_mappings_form($options);
      $r .= $form;      
      if (isset($options['presetSettings'])) {
        // The presets might contain some extra values to apply to every row - must be output as hiddens
        $extraHiddens = array_diff_key($options['presetSettings'], $formArray);
        foreach ($extraHiddens as $hidden=>$value)
          $r .= "<input type=\"hidden\" name=\"$hidden\" value=\"$value\" />\n";
      }
      $r .= '<input type="hidden" name="import_step" value="1" />';
      $r .= '<input type="submit" name="submit" value="'.lang::get('Next').'" class="ui-corner-all ui-state-default button" />';
      // copy any $_POST data into the form, as this would mean preset values that are provided by the form which the uploader
      // was triggered from.
      foreach ($_POST as $key=>$value)
        $r .= "<input type=\"hidden\" name=\"$key\" value=\"$value\" />\n";
      $r .= '</fieldset></form>';
      return $r;
    } else {
      // No settings form, so output the mappings form instead which is the next step.
      return self::upload_mappings_form($options);
    }
  }
  

  /**
   * Outputs the form for mapping columns to the import fields.
   * @param array $options Options array passed to the import control.
   */
  private static function upload_mappings_form($options) {
    //Need to save the spatial reference system else it is lost as we move through the import stages
    if (!empty($_POST['sample:entered_sref_system']))
      $_SESSION['sample:entered_sref_system']=$_POST['sample:entered_sref_system'];
    ini_set('auto_detect_line_endings',1);
    if (!file_exists($_SESSION['uploaded_file']))
      return lang::get('upload_not_available');
    self::add_resource('jquery_ui');
    $filename=basename($_SESSION['uploaded_file']);
    // If the last step was skipped because the user did not have any settings to supply, presetSettings contains the presets.
    // Otherwise we'll use the settings form content which already in $_POST so will overwrite presetSettings.
    if (isset($options['presetSettings'])) {
      $settings = array_merge(
        $options['presetSettings'],
        $_POST
      );
    } else 
      $settings = $_POST;
    // only want defaults that actually have a value - others can be set on a per-row basis by mapping to a column
    foreach ($settings as $key => $value) {
      if (empty($value)) {
        unset($settings[$key]);
      }
    }
    // cache the mappings
    $metadata = array('settings' => json_encode($settings));
    $post = array_merge($options['auth']['write_tokens'], $metadata);
    $request = parent::$base_url."index.php/services/plant_portal_import/cache_upload_metadata?uploaded_csv=$filename";
    $response = self::http_post($request, $post);
    if (!isset($response['output']) || $response['output'] != 'OK')
      return "Could not upload the settings metadata. <br/>".print_r($response, true);
    $request = parent::$base_url."index.php/services/plant_portal_import/get_plant_portal_import_fields/".$options['model'];

    $request .= '?'.self::array_to_query_string($options['auth']['read']);
    // include survey and website information in the request if available, as this limits the availability of custom attributes
    if (!empty($settings['website_id']))
      $request .= '&website_id='.trim($settings['website_id']);
    if (!empty($settings['survey_id']))
      $request .= '&survey_id='.trim($settings['survey_id']);
    if(isset($settings['sample:sample_method_id']) && trim($settings['sample:sample_method_id']) != '')
    	$request .= '&sample_method_id='.trim($settings['sample:sample_method_id']);
    $response = self::http_post($request, array());
    //Most of this code matches the "normal" importer page.
    $fields = json_decode($response['output'], true);
    
    
    //To Do AVB clean up this bit to get the location group attribute
    $options['model']='location';
    $request = parent::$base_url."index.php/services/plant_portal_import/get_plant_portal_import_fields/".$options['model'];
    $request .= '?'.self::array_to_query_string($options['auth']['read']);
    // include survey and website information in the request if available, as this limits the availability of custom attributes
    if (!empty($settings['website_id']))
      $request .= '&website_id='.trim($settings['website_id']);
    if (!empty($settings['survey_id']))
      $request .= '&survey_id='.trim($settings['survey_id']);
    if(isset($settings['sample:sample_method_id']) && trim($settings['sample:sample_method_id']) != '')
    	$request .= '&sample_method_id='.trim($settings['sample:sample_method_id']);
    $response = self::http_post($request, array());
    //Most of this code matches the "normal" importer page.
    $locationfields = json_decode($response['output'], true);
    $fields = array_merge($fields,$locationfields);
    
    
   
    if (!is_array($fields))
      return "curl request to $request failed. Response ".print_r($response, true);
    // Restrict the fields to ones relevant for the survey
    if (!empty($settings['survey_id']))
      self::limitFields($fields, $options, $settings['survey_id']);
    $request = str_replace('get_plant_portal_import_fields', 'get_plant_portal_required_fields', $request);
    $response = self::http_post($request);
    $responseIds = json_decode($response['output'], true);
    if (!is_array($responseIds))
      return "curl request to $request failed. Response ".print_r($response, true);
    $model_required_fields = self::expand_ids_to_fks($responseIds);
    if (!empty($settings))
      $preset_fields = self::expand_ids_to_fks(array_keys($settings));
    else
      $preset_fields=array();
    if (!empty($preset_fields))
      $unlinked_fields = array_diff_key($fields, array_combine($preset_fields, $preset_fields));
    else
      $unlinked_fields = $fields;
    
    // only use the required fields that are available for selection - the rest are handled somehow else
    $unlinked_required_fields = array_intersect($model_required_fields, array_keys($unlinked_fields));

    $handle = fopen($_SESSION['uploaded_file'], "r");
    $columns = fgetcsv($handle, 1000, ",");
    $reload = self::get_reload_link_parts();

    $reloadpath = $reload['path'] . '?' . self::array_to_query_string($reload['params']);

    self::clear_website_survey_fields($unlinked_fields, $settings);
    self::clear_website_survey_fields($unlinked_required_fields, $settings);
    $autoFieldMappings = self::getAutoFieldMappings($options, $settings);
    //AVB To Do, need to test if Remember All works with Plant Portal importer.
    //  if the user checked the Remember All checkbox need to remember this setting
    $checkedRememberAll=isset($autoFieldMappings['RememberAll']) ? ' checked="checked"' : '';;

    $r = "<form method=\"post\" id=\"entry_form\" action=\"$reloadpath\" class=\"iform\">\n".
      '<p>'.lang::get('column_mapping_instructions').'</p>'.
      '<div class="ui-helper-clearfix import-mappings-table"><table class="ui-widget ui-widget-content">'.
      '<thead class="ui-widget-header">'.
      "<tr><th>".lang::get('Column in CSV File')."</th><th>".lang::get('Maps to attribute')."</th>";
    if (self::$rememberingMappings) {
      $r .= "<th>".lang::get('Remember choice?').
         "<br/><input type='checkbox' name='RememberAll' id='RememberAll' value='1' title='".
         lang::get('Tick all boxes to remember every column mapping next time you import.')."'$checkedRememberAll/></th>";
      self::$javascript .= "$('#RememberAll').change(function() {
  if (this.checked) {
   $(\".rememberField\").attr(\"checked\",\"checked\")
  } else {
   $(\".rememberField\").removeAttr(\"checked\")
  }
});\n";
    }
    $r .= '</tr></thead><tbody>';
    $colCount = 0;
    foreach ($columns as $column) {
      $column = trim($column);
      if (!empty($column)) {
        $colCount ++;
        $colFieldName = preg_replace('/[^A-Za-z0-9]/', '_', $column);
        $r .= "<tr><td>$column</td><td><select name=\"$colFieldName\" id=\"$colFieldName\">";
        $r .= self::get_column_options($options['model'], $unlinked_fields, $column, $autoFieldMappings);
        $r .= "</select></td></tr>\n";
      }
    }
    $r .= '</tbody>';
    $r .= '</table>';
    
    $r .= '<div><div id="required-instructions" class="import-mappings-instructions"><b>'.lang::get('Tasks: ').'</b><span>'.
      lang::get('The following database attributes must be matched to a column in your import file before you can continue').':</span><ul></ul><br/></div><br>';
    
    $r .= '<div id="duplicate-instructions" class="import-mappings-duplicate-instructions"><span id="duplicate-instruct"><em>'.
      lang::get('There are currently two or more drop-downs allocated to the same value.').'</em></span><ul></ul><br/></div></div></div>';
    
    $r .= '<input type="hidden" name="import_step" value="2" />';
    $r .= '<input type="submit" name="submit" id="submit" value="'.lang::get('Continue').'" class="ui-corner-all ui-state-default button" />';
    $r .= '</form>';
    
    self::$javascript .= "function detect_duplicate_fields() {
      var valueStore = [];
      var duplicateStore = [];
      var valueStoreIndex = 0;
      var duplicateStoreIndex = 0;
      $.each($('#entry_form select'), function(i, select) {
        if (valueStoreIndex==0) {
          valueStore[valueStoreIndex] = select.value;
          valueStoreIndex++;
        } else {
          for(i=0; i<valueStoreIndex; i++) {
            if (select.value==valueStore[i] && select.value != '<".lang::get('Not imported').">') {
              duplicateStore[duplicateStoreIndex] = select.value;
              duplicateStoreIndex++;
            }
             
          }
          valueStore[valueStoreIndex] = select.value;
          valueStoreIndex++;
        }      
      })
      if (duplicateStore.length==0) {
        DuplicateAllowsUpload = 1;
        $('#duplicate-instruct').css('display', 'none');
      } else {
        DuplicateAllowsUpload = 0;
        $('#duplicate-instruct').css('display', 'inline');
      }
    }\n";  
    self::$javascript .= "function update_required_fields() {
      // copy the list of required fields
      var fields = $.extend(true, {}, required_fields),
          sampleVagueDates = [],
    	  locationReference = false,
          fieldTokens, thisValue;
      $('#required-instructions li').remove();
      // strip out the ones we have already allocated
      $.each($('#entry_form select'), function(i, select) {
        thisValue = select.value;
        // If there are several options of how to search a single lookup then they
        // are identified by a 3rd token, e.g. occurrence:fk_taxa_taxon_list:search_code.
        // These cases fulfil the needs of a required field so we can remove them.
        fieldTokens = thisValue.split(':');
        if (fieldTokens.length>2) {
          fieldTokens.pop();
          thisValue = fieldTokens.join(':');
        }
        delete fields[thisValue];
        // special case for vague dates - if we have a complete sample vague date, then can strike out the sample:date required field
        if (select.value.substr(0,12)=='sample:date_') {
          sampleVagueDates.push(thisValue);
        }
    	// and another special case for samples: can either include the sref or a foreign key reference to a location.
    	if (select.value.substr(0,18)=='sample:fk_location') { // catches the code based fk as well
          locationReference = true;
        }
      });
      if (sampleVagueDates.length==3) {
        // got a full vague date, so can remove the required date field
        delete fields['sample:date'];
      }
      if (locationReference) {
        // got a location foreign key reference, so can remove the required entered sref fields
        delete fields['sample:entered_sref'];
    	delete fields['sample:entered_sref_system']
      }
      //To Do AVB - May need to clean this, remove these from being mandatory mapping fields as we get spatial references from 
      //the sample itself.
      delete fields['location:centroid_sref'];
      delete fields['location:centroid_sref_system']
      var output = '';
      $.each(fields, function(field, caption) {
        output += '<li>'+caption+'</li>';
      });
      if (output==='') {
        $('#required-instructions').css('display', 'none');
        RequiredAllowsUpload = 1;
      } else {
        $('#required-instructions').css('display', 'inline');
        RequiredAllowsUpload = 0;
      }
      if (RequiredAllowsUpload == 1 && DuplicateAllowsUpload == 1) {
        $('#submit').attr('disabled', false);
      } else {
        $('#submit').attr('disabled', true);
      }
      $('#required-instructions ul').html(output);
    }\n";
    self::$javascript .= "required_fields={};\n";
    foreach ($unlinked_required_fields as $field) {
      $caption = $unlinked_fields[$field];
      if (empty($caption)) {
        $tokens = explode(':', $field);
        $fieldname = $tokens[count($tokens)-1];
        $caption = lang::get(self::processLabel(preg_replace(array('/^fk_/', '/_id$/'), array('', ''), $fieldname)));
      }
      $caption = self::translate_field($field, $caption);
      self::$javascript .= "required_fields['$field']='$caption';\n";
    }
    self::$javascript .= "detect_duplicate_fields();\n";
    self::$javascript .= "update_required_fields();\n";
    self::$javascript .= "$('#entry_form select').change(function() {detect_duplicate_fields(); update_required_fields();});\n";
    return $r;
  }
  
  /**
   * As the Plant Portal importal contains an extra step, we need a hidden version of the upload_mappings_form to be displayed on the Plant Portal specific
   * step otherwise the information is not posted and is lost.
   * @param array $options Options array passed to the import control.
   */
  private static function get_hidden_upload_mappings_form($options) {
    if (isset($options['presetSettings'])) {
      $settings = array_merge(
        $options['presetSettings'],
        $_POST
      );
    } else 
      $settings = $_POST;

    $request = parent::$base_url."index.php/services/plant_portal_import/get_plant_portal_import_fields/".$options['model'];

    $request .= '?'.self::array_to_query_string($options['auth']['read']);
    // include survey and website information in the request if available, as this limits the availability of custom attributes
    if (!empty($settings['website_id']))
      $request .= '&website_id='.trim($settings['website_id']);
    if (!empty($settings['survey_id']))
      $request .= '&survey_id='.trim($settings['survey_id']);

    if(isset($settings['sample:sample_method_id']) && trim($settings['sample:sample_method_id']) != '')
    	$request .= '&sample_method_id='.trim($settings['sample:sample_method_id']);
    $response = self::http_post($request, array());

    $fields = json_decode($response['output'], true);

    if (!is_array($fields))
      return "curl request to $request failed. Response ".print_r($response, true);
    // Restrict the fields if there is a setting for this survey Id
    if (!empty($settings['survey_id']))
      self::limitFields($fields, $options, $settings['survey_id']);
    $request = str_replace('get_plant_portal_import_fields', 'get_plant_portal_required_fields', $request);
    $response = self::http_post($request);
    $responseIds = json_decode($response['output'], true);
    if (!is_array($responseIds))
      return "curl request to $request failed. Response ".print_r($response, true);
    $model_required_fields = self::expand_ids_to_fks($responseIds);
    if (!empty($settings))
      $preset_fields = self::expand_ids_to_fks(array_keys($settings));
    else
      $preset_fields=array();
    if (!empty($preset_fields))
      $unlinked_fields = array_diff_key($fields, array_combine($preset_fields, $preset_fields));
    else
      $unlinked_fields = $fields;
    // only use the required fields that are available for selection - the rest are handled somehow else
    $unlinked_required_fields = array_intersect($model_required_fields, array_keys($unlinked_fields));

    $handle = fopen($_SESSION['uploaded_file'], "r");
    $columns = fgetcsv($handle, 1000, ",");
    $reload = self::get_reload_link_parts();
    $reloadpath = $reload['path'] . '?' . self::array_to_query_string($reload['params']);

    self::clear_website_survey_fields($unlinked_fields, $settings);
    self::clear_website_survey_fields($unlinked_required_fields, $settings);

    //Place the inputs from the mappings page (hidden) onto the page
    $r = '<div>';
    $colCount = 0;
    foreach ($columns as $column) {
      $column = trim($column);
      if (!empty($column)) {
        $colCount ++;
        $colFieldName = preg_replace('/[^A-Za-z0-9]/', '_', $column);
        $r .= "<input type=\"hidden\" name=\"$colFieldName\" id=\"$colFieldName\" name=\"$colFieldName\" value=\"$_POST[$colFieldName]\">\n";
      }
    }
    $r .= '</div>';
    return $r;
  }

  //AVB To DO - If the Plant Portal importer is found not to work with Remember Mappings after testing, then we can remove this function
  /**
   * Returns an array of field to column title mappings that were previously stored in the user profile,
   * or mappings that were provided via the page's configuration form.
   * If the user profile does not support saving mappings then sets self::$rememberingMappings to false.
   * @param array $options Options array passed to the import helper which might contain a fieldMap.
   * @param array $settings Settings array for this import which might contain the survey_id.
   * @return array|mixed
   */
  private static function getAutoFieldMappings($options, $settings) {
    $autoFieldMappings=array();
    //get the user's checked preference for the import page
    if (function_exists('hostsite_get_user_field')) {
      $json = hostsite_get_user_field('import_field_mappings');
      if ($json===false) {
        if (!hostsite_set_user_field('import_field_mappings', '[]'))
          self::$rememberingMappings=false;
      } else {
        $json=trim($json);
        $autoFieldMappings=json_decode(trim($json), true);
      }
    } else
      // host does not support user profiles, so we can't remember mappings
      self::$rememberingMappings=false;
    if (!empty($settings['survey_id']) && !empty($options['fieldMap'])) {
      foreach($options['fieldMap'] as $surveyFieldMap) {
        if (isset($surveyFieldMap['survey_id']) && isset($surveyFieldMap['fields']) &&
            $surveyFieldMap['survey_id'] == $settings['survey_id']) {
          // The fieldMap config is a list of database fields with optional '=column titles' added to them.
          // Need a list of column titles mapped to fields so swap this around.
          $fields = self::explode_lines($surveyFieldMap['fields']);
          foreach ($fields as $field) {
            $tokens = explode('=', $field);
            if (count($tokens)===2)
              $autoFieldMappings[$tokens[1]] = $tokens[0];
          }
        }
      }
    }
    return $autoFieldMappings;
  }

  //To Do AVB - This function can be removed if onlyAllowMappedFields is not found to work with the Plant Portal importer
  /**
   * List of available fields retrieve from the warehouse to ones for this survey.
   * @param array $fields Field list obtained from the warehouse for this survey. Disallowed fields
   * will be removed.
   * @param array $options Import helper options array
   * @param integer $survey_id ID of the survey being imported
   */
  private static function limitFields(&$fields, $options, $survey_id) {
    if (isset($options['onlyAllowMappedFields']) && $options['onlyAllowMappedFields'] && isset($options['fieldMap'])) {
      foreach($options['fieldMap'] as $surveyFieldMap) {
        if (isset($surveyFieldMap['survey_id']) && isset($surveyFieldMap['fields']) &&
            $surveyFieldMap['survey_id']==$survey_id) {
          $allowedFields = self::explode_lines($surveyFieldMap['fields']);
          $trimEqualsValue = create_function('&$val', '$tokens = explode("=",$val); $val=$tokens[0];');
          array_walk($allowedFields, $trimEqualsValue);
          $fields = array_intersect_key($fields, array_combine($allowedFields, $allowedFields));
        }
      }
    }
  }

  /**
   * When an array (e.g. $_POST containing preset import values) has values with actual ids in it, we need to
   * convert these to fk_* so we can compare the array of preset data with other arrays of expected data.
   * @param array $arr Array of IDs.
   */
  private static function expand_ids_to_fks($arr) {
    $ids = preg_grep('/_id$/', $arr);
    foreach ($ids as &$id) {
      $id = str_replace('_id', '', $id);
      if (strpos($id, ':')===false)
        $id = "fk_$id";
      else
        $id = str_replace(':', ':fk_', $id);
    }
    return array_merge($arr, $ids);
  }

  /**
   * Takes an array of fields, and removes the website ID or survey ID fields within the arrays if
   * the website and/or survey id are set in the $settings data.
   * @param array $array Array of fields.
   * @param array $settings Global settings which apply to every row, which may include the website_id 
   * and survey_id.
   */
  private static function clear_website_survey_fields(&$array, $settings) {
    foreach ($array as $idx => $field) {
      if (!empty($settings['website_id']) && (preg_match('/:fk_website$/', $idx) || preg_match('/:fk_website$/', $field))) {
        unset($array[$idx]);
      }
      if (!empty($settings['survey_id']) && (preg_match('/:fk_survey$/', $idx) || preg_match('/:fk_survey$/', $field))) {
        unset($array[$idx]);
      }
    }
  }
  
  /**
   * Displays the upload result page.
   * @param array $options Array of options passed to the import control.
   */
  private static function upload_result($options) {
    $request = parent::$base_url."index.php/services/plant_portal_import/get_upload_result?uploaded_csv=".$_GET['uploaded_csv'];

    $request .= '&'.self::array_to_query_string($options['auth']['read']);
    $response = self::http_post($request, array());

    if (isset($response['output'])) {
      $output = json_decode($response['output'], true);
      if (!is_array($output) || !isset($output['problems']))
        return 'An error occurred during the upload.<br/>'.print_r($response, true);

      if ($output['problems']!==0) {
        $r = $output['problems'].' problems were detected during the import. <a href="'.$output['file'].'">Download the records that did not import.</a>';
      } else {
        $r = 'The upload was successful.';
      }
    } else {
      $r = 'An error occurred during the upload.<br/>'.print_r($response, true);
    }
    $reload = self::get_reload_link_parts();
    unset($reload['params']['total']);
    unset($reload['params']['uploaded_csv']);

    $reloadpath = $reload['path'] . '?' . self::array_to_query_string($reload['params']);
    $r = "<p>$r</p><p>".lang::get('Would you like to ')."<a href=\"$reloadpath\">".lang::get('import another file?')."</a></p>";
    return $r;
  }

 /**
  * Returns a list of columns as an list of <options> for inclusion in an HTML drop down,
  * loading the columns from a model that are available to import data into
  * (excluding the id and metadata). Triggers the handling of remembered checkboxes and the
  * associated labelling. 
  * This method also attempts to automatically find a match for the columns based on a number of rules
  * and gives the user the chance to save their settings for use the next time they do an import (TO DO AVB - Note might need to alter this text if remember fields is not found to work with Plant Portal)
  * @param string $model Name of the model
  * @param array  $fields List of the available possible import columns
  * @param string $column The name of the column from the CSV file currently being worked on.
  * @param array $autoFieldMappings An array containing the automatic field mappings for the page.
  */
  private static function get_column_options($model, $fields, $column, $autoFieldMappings) {
    $skipped = array('id', 'created_by_id', 'created_on', 'updated_by_id', 'updated_on',
      'fk_created_by', 'fk_updated_by', 'fk_meaning', 'fk_taxon_meaning', 'deleted', 'image_path');
    //strip the column of spaces for use in html ids
    $idColumn = str_replace(" ", "", $column);
    $r = '';
    $heading='';
    $labelListIndex = 0;
    $labelList = array();
    $itWasSaved[$column] = 0;

    foreach ($fields as $field=>$caption) {
      if (strpos($field,":"))
        list($prefix,$fieldname)=explode(':',$field);
      else {
        $prefix=$model;
        $fieldname=$field;
      }
      // Skip the metadata fields
      if (!in_array($fieldname, $skipped)) {
        // make a clean looking caption
        $caption = self::make_clean_caption($caption, $prefix, $fieldname, $model);
       /*
        * The following creates an array called $labelList which is a list of all captions
        * in the drop-down lists. Using array_count_values the array values are calculated as the number of times
        * each caption occurs for use in duplicate detection.
        * $labelListHeading is an array where the keys are each column we work with concatenated to the heading of the caption we
        * are currently working on. 
        */
        $strippedScreenCaption = str_replace(" (lookup existing record)","",self::translate_field($field, $caption));
        $labelList[$labelListIndex] = strtolower($strippedScreenCaption);
        $labelListIndex++;
        if (isset ($labelListHeading[$column.$prefix]))
          $labelListHeading[$column.$prefix] = $labelListHeading[$column.$prefix].':'.strtolower($strippedScreenCaption);
        else
          $labelListHeading[$column.$prefix] = strtolower($strippedScreenCaption); 
      }
    } 
    $labelList = array_count_values($labelList);
    $multiMatch=array();
    foreach ($fields as $field=>$caption) {
      if (strpos($field,":"))
        list($prefix,$fieldname)=explode(':',$field);
      else {
        $prefix=$model;
        $fieldname=$field;
      }
      // make a clean looking default caption. This could be provided by the $fields array, or we have to construct it.
      $defaultCaption = self::make_clean_caption($caption, $prefix, $fieldname, $model);
      // Allow the default caption to be translated or overridden by language files.
      $translatedCaption=self::translate_field($field, $defaultCaption);
      //need a version of the caption without "Lookup existing record" as we ignore that for matching.
      $strippedScreenCaption = str_replace(" (lookup existing record)","",$translatedCaption);
      $fieldname=str_replace(array('fk_','_id'), array('',''), $fieldname);
      unset($option);
      // Skip the metadata fields
      if (!in_array($fieldname, $skipped)) {
        $selected = false;
        //get user's saved settings, last parameter is 2 as this forces the system to explode into a maximum of two segments.
        //This means only the first occurrence for the needle is exploded which is desirable in the situation as the field caption
        //contains colons in some situations.
        if (!empty($autoFieldMappings[$column]) && $autoFieldMappings[$column]!=='<Not imported>') {
          $savedData = explode(':',$autoFieldMappings[$column],2);
          $savedSectionHeading = $savedData[0];
          $savedMainCaption = $savedData[1];
        } else {
          $savedSectionHeading = '';
          $savedMainCaption = '';
        }
        //
        //Detect if the user has saved a column setting that is not 'not imported' then call the method that handles the auto-match rules.
        if (strcasecmp($prefix,$savedSectionHeading)===0 && strcasecmp($field,$savedSectionHeading.':'.$savedMainCaption)===0) {
          $selected=true;
          $itWasSaved[$column] = 1;
          //even though we have already detected the user has a saved setting, we need to call the auto-detect rules as if it gives the same result then the system acts as if it wasn't saved.
          $saveDetectRulesResult = self::auto_detection_rules($column, $defaultCaption, $strippedScreenCaption, $prefix, $labelList, $itWasSaved[$column], true);
          $itWasSaved[$column] = $saveDetectRulesResult['itWasSaved'];
        } else {
          //only use the auto field selection rules to select the drop-down if there isn't a saved option
          if (!isset($autoFieldMappings[$column])) {
            $nonSaveDetectRulesResult = self::auto_detection_rules($column, $defaultCaption, $strippedScreenCaption, $prefix, $labelList, $itWasSaved[$column], false);
            $selected = $nonSaveDetectRulesResult['selected'];
          }
        }
        //As a last resort. If we have a match and find that there is more than one caption with this match, then flag a multiMatch to deal with it later
        if (strcasecmp($strippedScreenCaption, $column)==0 && $labelList[strtolower($strippedScreenCaption)] > 1) {
          $multiMatch[] = $column;
          $optionID = $idColumn.'Duplicate';  
        } else 
          $optionID = $idColumn.'Normal';
        $option = self::model_field_option($field, $defaultCaption, $selected, $optionID);
        if ($selected)
          self::$automaticMappings[$column] = $field;
      }
      
      // if we have got an option for this field, add to the list
      if (isset($option)) {
        // first check if we need a new heading
        if ($prefix!=$heading) {
          $heading = $prefix;
          $class = '';
          if (isset($labelListHeading[$column.$heading])) {
            $subOptionList = explode(':', $labelListHeading[$column.$heading]);
            $foundDuplicate=false;
            foreach ($subOptionList as $subOption) {
              if (isset($labelList[$subOption]) && $labelList[$subOption] > 1) {
                $class = $idColumn.'Duplicate';
                $foundDuplicate = true;
              }
              if (isset($labelList[$subOption]) && $labelList[$subOption] == 1 and $foundDuplicate == false)
                $class = $idColumn.'Normal';
            }
          }
          if (!empty($r)) 
            $r .= '</optgroup>';
          $r .= "<optgroup class=\"$class\" label=\"";
          $r .= self::processLabel(lang::get($heading)).'">';
        }
        $r .= $option;
      }
    }  
    $r = self::items_to_draw_once_per_import_column($r, $column, $itWasSaved, isset($autoFieldMappings['RememberAll']), $multiMatch);
    return $r;
  }

  
 /**
  * This method is used by the mode_field_options method.
  * It has two modes:
  * When $saveDetectedMode is false, the method uses several rules in an attempt to automatically determine
  * a value for one of the csv column drop-downs on the import page.
  * When $saveDetectedMode is true, the method uses the same rules to see if the system would have retrieved the
  * same drop-down value as the one that was saved by the user. If this is the case, the system acts
  * as if the value had been automatically determined rather than saved.
  * 
  * @param string $column The CSV column we are currently working with from the import file.
  * @param string $defaultCaption The default, untranslated caption.
  * @param string $strippedScreenCaption A version of an item in the column selection drop-down that has 'lookup existing record'stripped
  * @param string $prefix Caption prefix
  * each item having a list of regexes to match against
  * @param array $labelList A list of captions and the number of times they occur.
  * @param integer $itWasSaved This is set to 1 if the system detects that the user has a custom saved preference for a csv column drop-down.
  * @param boolean $saveDetectedMode Determines the mode the method is running in
  * @return array Depending on the mode, we either are interested in the $selected value or the $itWasSaved value.
  */ 
  private static function auto_detection_rules($column, $defaultCaption, $strippedScreenCaption, $prefix, $labelList, $itWasSaved, $saveDetectedMode) {
    $column=trim($column);
    /*
    * This is an array of drop-down options with a list of possible column headings the system will use to match against that option.
    * The key is in the format heading:option, all lowercase e.g. occurrence:comment 
    * The value is an array of regexes that the system will automatically match against.
    */
    $alternatives = array(
      "sample:entered sref"=>array("/(sample)?(spatial|grid)ref(erence)?/"),
      "occurrence_2:taxa taxon list (lookup existing record)"=>array("/(2nd|second)(species(latin)?|taxon(latin)?|latin)(name)?/"),
      "occurrence:taxa taxon list (lookup existing record)"=>array("/(species(latin)?|taxon(latin)?|latin)(name)?/"),
      "sample:location name"=>array("/(site|location)(name)?/"),
      "smpAttr:eunis habitat (lookup existing record)" => array("/(habitat|eunishabitat)/")
    );
    $selected=false;
    //handle situation where there is a unique exact match
    if (strcasecmp($strippedScreenCaption, $column)==0 && $labelList[strtolower($strippedScreenCaption)] == 1) {
      if ($saveDetectedMode) 
        $itWasSaved = 0; 
      else 
        $selected=true;
    } else {
      //handle the situation where a there isn' a unqiue match, but there is if you take the heading into account also
      if (strcasecmp($prefix.' '.$strippedScreenCaption, $column)==0) {
        if ($saveDetectedMode) 
          $itWasSaved = 0; 
        else 
          $selected=true;
      }
      //handle the situation where there is a match with one of the items in the alternatives array.
      if (isset($alternatives[$prefix.':'.strtolower($defaultCaption)])) {
        foreach ($alternatives[$prefix.':'.strtolower($defaultCaption)] as $regexp) {
          if (preg_match($regexp, strtolower(str_replace(' ', '', $column)))) {
            if ($saveDetectedMode) 
              $itWasSaved = 0; 
            else 
              $selected=true;
            break;
          }
        } 
      }
    }
    return array (
      'itWasSaved'=>$itWasSaved,
      'selected'=>$selected
    );
  }
  
  
 /**
  * Used by the get_column_options to draw the items that appear once for each of the import columns on the import page.
  * These are the checkboxes, the warning the drop-down setting was saved and also the non-unique match warning
  * @param string $r The HTML to be returned.
  * @param string $column Column from the import CSV file we are currently working with
  * @param integer $itWasSaved This is 1 if a setting is saved for the column and the column would not have been automatically calculated as that value anyway.
  * @param boolean $rememberAll Is the remember all mappings option set?.
  * @param array $multiMatch Array of columns where there are multiple matches for the column and this cannot be resolved.
  * @return string HTMl string 
  */
  private static function items_to_draw_once_per_import_column($r, $column, $itWasSaved, $rememberAll, $multiMatch) {
    $checked = ($itWasSaved[$column] == 1 || $rememberAll) ? ' checked="checked"' : '';
    $optionID = str_replace(" ", "", $column).'Normal';
    $r = "<option value=\"&lt;Not imported&gt;\">&lt;".lang::get('Not imported').'&gt;</option>'.$r.'</optgroup>';
    //To DO AVB - Only need to keep if we can support Remember Mappings in Plant Portal
    if (self::$rememberingMappings) 
      $r .= "<td class=\"centre\"><input type='checkbox' name='$column.Remember' class='rememberField'id='$column.Remember' value='1'$checked onclick='
      if (!this.checked) {
        $(\"#RememberAll\").removeAttr(\"checked\");
      }' 
      title='If checked, your selection for this particular column will be saved and automatically selected during future imports. ".
          "Any alterations you make to this default selection in the future will also be remembered until you deselect the checkbox.'></td>";

    if ($itWasSaved[$column] == 1) {
      $r .= "<tr><td></td><td class=\"note\">Please check the suggested mapping above is correct.</td></tr>";
    }
    //If we find there is a match we cannot resolve uniquely, then give the user a checkbox to reduce the drop-down to suggestions only.
    //Do this by hiding items whose class has "Normal" at the end as these are the items that do not contain the duplicates.
    if (in_array($column, $multiMatch) && $itWasSaved[$column] == 0) {
      $r .= "<tr><td></td><td class=\"note\">There are multiple possible matches for ";
      $r .= "\"$column\"";
      $r .=  "<br/><form><input type='checkbox' id='$column.OnlyShowMatches' value='1' onclick='
       if (this.checked) {
         $(\".$optionID\").hide();
       } else {
         $(\".$optionID\").show();
      }'
      > Only show likely matches in drop-down<br></form></td></tr>";
    }
    return $r;
  }
  
  
 /**
  * Used by the get_column_options method to add "lookup existing record" to the appropriate captions
  * in the drop-downs on the import page.
  * @param string $caption The drop-down item currently being worked on
  * @param string $prefix Caption prefix
  * @param string $fieldname The database field that the caption relates to.
  * @param string $model Name of the model
  * @return string $caption A caption for the column drop-down on the import page.
  */
  private static function make_clean_caption($caption, $prefix, $fieldname, $model) {
  	$captionSuffix = (substr($prefix,strlen($prefix)-2,2)=='_2' ? // in a association situation with a second record
  						' (2)' : '');
    if (empty($caption)) {
      if (substr($fieldname,0,3)=='fk_') {
        $captionSuffix .= ' ('.lang::get('lookup existing record').')';
      }   
      $fieldname=str_replace(array('fk_','_id'), array('',''), $fieldname);
      if ($prefix==$model || $prefix=="metaFields" || $prefix==substr($fieldname,0,strlen($prefix))) {
        $caption = self::processLabel($fieldname).$captionSuffix;
      } else {
        $caption = self::processLabel("$fieldname").$captionSuffix;
      }
    } else {
        $caption .= (substr($fieldname,0,3)=='fk_' ? ' ('.lang::get('lookup existing record').')' : ''); 
    }
    return $caption;
  }


  /**
   * Method to upload the file in the $_FILES array, or return the existing file if already uploaded.
   * @param array $options Options array passed to the import control.
   * @return string
   * @throws \Exception
   * @access private
   */
  private static function get_uploaded_file($options) {
    if (!isset($options['existing_file']) && !isset($_POST['import_step'])) {
      // No existing file, but on the first step, so the $_POST data must contain the single file.
      if (count($_FILES)!=1) throw new Exception('There must be a single file uploaded to import');
      // reset gets the first array element
      $file = reset($_FILES);
      // Get the original file's extension
      $parts = explode(".",$file['name']);
      $fext = array_pop($parts);
      if ($fext!='csv') throw new Exception('Uploaded file must be a csv file');
      // Generate a file id to store the upload as
      $destination = time().rand(0,1000).".".$fext;
      $interim_image_folder = isset(parent::$interim_image_folder) ? parent::$interim_image_folder : 'upload/';
      //In the original import_helper code the upload folder was in the same folder as the php file, however this prebuilt form is a level down the folder
      //hierarchy so we need to chop "prebuilt_forms" from the file path.
      $uploadFolderPath=str_replace('prebuilt_forms','',dirname(__FILE__));
      $interim_path = $uploadFolderPath.'/'.$interim_image_folder;
      if (move_uploaded_file($file['tmp_name'], "$interim_path$destination")) {
        return "$interim_path$destination";
      }
    } elseif (isset($options['existing_file'])) {
      return $options['existing_file'];
    }
    return isset($_POST['existing_file']) ? $_POST['existing_file'] : '';
  }

  /**
   * Humanize a piece of text by inserting spaces instead of underscores, and making first letter
   * of each phrase a capital.
   *
   * @param string $text The text to alter.
   * @return string The altered string.
   */
  private static function processLabel($text) {
    return ucFirst(preg_replace('/[\s_]+/', ' ', $text));
  }

  /**
   * Private method to build a single select option for the model field options.
   * Option is selected if selected=caption (case insensitive).
   * @param string $field Name of the field being output.
   * @param string $caption Caption of the field being output.
   * @param boolean $selected Set to true if outputing the currently selected option.
   * @param string $optionID Id of the current option.
   */
  private static function model_field_option($field, $caption, $selected, $optionID) {
    $selHtml = ($selected) ? ' selected="selected"' : '';
    $caption = self::translate_field($field, $caption);
    $r =  '<option class=';
    $r .= $optionID;
    $r .= ' value="'.htmlspecialchars($field)."\"$selHtml>".htmlspecialchars($caption).'</option>';
    return $r;
  }

  /**
   * Provides optional translation of field captions by looking for a translation code dd:model:fieldname. If not
   * found returns the original caption.
   * @param string $field Name of the field being output.
   * @param string $caption Untranslated caption of the field being output.
   * @return string Translated caption.
   */
  private static function translate_field($field, $caption) {
    // look in the translation settings to see if this column name needs overriding
    $trans = lang::get("dd:$field");
    // Only update the caption if this actually did anything
    if ($trans != "dd:$field" ) {
      return $trans;
    } else {
      return $caption;
    }
  }
  
  /**
   * Takes a file that has been uploaded to the client website upload folder, and moves it to the warehouse upload folder using the
   * data services. 
   *
   * @param string $path Path to the file to upload, relative to the interim image path folder (normally the
   * client_helpers/upload folder.
   * @param boolean $persist_auth Allows the write nonce to be preserved after sending the file, useful when several files
   * are being uploaded.
   * @param array $readAuth Read authorisation tokens, if not supplied then the $_POST array should contain them.
   * @param string $service Path to the service URL used. Default is data/handle_media, but could be import/upload_csv.
   * @return string Error message, or true if successful.
   */
  protected static function send_file_to_warehouse_plant_portal_importer($path, $persist_auth=false, $readAuth = null, $service='data/handle_media',$model) {
    if ($readAuth==null) $readAuth=$_POST;
    $interim_image_folder = isset(parent::$interim_image_folder) ? parent::$interim_image_folder : 'upload/';
    //This code is slightly different for the Plant Portal importer. Customised because the importer php file is a prebuilt form, so we can't simply get the current 
    //directory as the Upload folder isn't actually there
    $uploadFolderPath=str_replace('prebuilt_forms','',dirname(__FILE__));
    $interim_path = $uploadFolderPath.'/'.$interim_image_folder;
    if (!file_exists($interim_path.$path))
      return "The file $interim_path$path does not exist and cannot be uploaded to the Warehouse.";
    $serviceUrl = parent::$base_url."index.php/services/".$service;
    // This is used by the file box control which renames uploaded files using a guid system, so disable renaming on the server.
    $postargs = array('name_is_guid' => 'true');
    // attach authentication details
    if (array_key_exists('auth_token', $readAuth))
      $postargs['auth_token'] = $readAuth['auth_token'];
    if (array_key_exists('nonce', $readAuth))
      $postargs['nonce'] = $readAuth['nonce'];
    if ($persist_auth)
      $postargs['persist_auth'] = 'true';
    $file_to_upload = array('media_upload'=>'@'.realpath($interim_path.$path));
    $response = self::http_post($serviceUrl, $file_to_upload + $postargs);
    $output = json_decode($response['output'], true);
    $r = true; // default is success
    if (is_array($output)) {
      //an array signals an error
      if (array_key_exists('error', $output)) {
        // return the most detailed bit of error information
        if (isset($output['errors']['media_upload']))
          $r = $output['errors']['media_upload'];
        else
          $r = $output['error'];
      }
    }
    //remove local copy
    unlink(realpath($interim_path.$path));
    return $r;
  }
  


  /**
   * Outputs the import wizard steps. 
   *
   * @param array $options Options array with the following possibilities:
   *
   * * **model** - Required. The name of the model data is being imported into.
   // To Do AVB - Do we need the existing file option for Plant Portal?
   * * **existing_file** - Optional. The full path on the server to an already uploaded file to import.
   * * **auth** - Read and write authorisation tokens.
   * * **fieldMap** - array of configurations of the fields available to import, one per survey.
   *   The importer will generate a list of all possible fields in the database to import into
   *   for a given survey. This typically includes all the standard "core" database fields such
   *   as species name and sample date, as well as a list of all custom attributes for a survey.
   *   This list is quite long and some of the default core database fields provided might not be
   *   appropriate to your survey dataset, leading to possible confusion. So you can use this parameter
   *   to define database fields and column titles in the spreadsheet that will automatically map to them.
   *   Provide an array, with each array entry being an associative array containing the definition of the
   *   fields for 1 survey dataset. In the associative array provide a value called survey_id to link
   *   this definition to a survey dataset. Also provide a value called fields containing a list of
   *   database fields you are defining for this dataset, one per line. If you want to link this field
   *   to a column title then follow the database field name with an equals, then the column title,
   *   e.g. sample:date=Record date.
   * * **onlyAllowMappedFields** - set to true and supply field mappings in the fieldMap parameter
   *   to ensure that only the fields you have specified for the selected survey will be available for
   *   selection. This allows you to hide all the import fields that you don't want to be used for
   *   importing into a given survey dataset, thus tidying up the list of options to improve ease of
   *   use. Default true.
   * @return string
   * @throws \exception
   */
  public static function importer($args,$options) {
    if (isset($_GET['total'])) {
      return self::upload_result($options);
    } elseif (!isset($_POST['import_step'])) {
      //Need to set these to null at first step of wizard otherwise it persists even after restarting import process
      $_SESSION['chosen_column_headings']=null;
      $_SESSION['sample:entered_sref_system']=null;
      if (count($_FILES)==1)
        return self::import_settings_form($args,$options);
      else
        return self::upload_form();
    } elseif ($_POST['import_step']==1) {
      return self::upload_mappings_form($options);
    } elseif ($_POST['import_step']==2) {
      return self::plant_portal_import_logic($args,$options);
    } elseif ($_POST['import_step']==3) {
      // To Do AVB, this might need to change if we are going to support plot-only upload
      $options['model']='occurrence';
      return self::run_plant_portal_upload($options, $_POST);
    }
    else throw new exception('Invalid importer state');
  }
  
  /**
   * Display the page which outputs the upload progress bar. Adds JavaScript to the page which performs the chunked upload. 
   * @param array $options Array of options passed to the import control.
   * @param array $mappings List of column title to field mappings
   */
  private static function run_plant_portal_upload($options, $mappings) {
    self::add_resource('jquery_ui');
    if (!file_exists($_SESSION['uploaded_file']))
      return lang::get('upload_not_available');
    $filename=basename($_SESSION['uploaded_file']);
    // move file to server
    $r = self::send_file_to_warehouse_plant_portal_importer($filename, false, $options['auth']['write_tokens'], 'plant_portal_import/upload_csv',$options['model']);
    if ($r===true) {
      $reload = self::get_reload_link_parts();
      $reload['params']['uploaded_csv']=$filename;

      $reloadpath = $reload['path'] . '?' . self::array_to_query_string($reload['params']);

      // initiate local javascript to do the upload with a progress feedback
      $r = '
  <div id="progress" class="ui-widget ui-widget-content ui-corner-all">
  <div id="progress-bar" style="width: 400px"></div>
  <div id="progress-text">Preparing to upload.</div>
  </div>
  ';
      $metadata = array('mappings' => json_encode($mappings));
      // cache the mappings
      if (function_exists('hostsite_set_user_field')) {
        $userSettings = array();
        foreach ($mappings as $column => $setting) {
          $userSettings[str_replace("_", " ", $column)] = $setting;
        }
        //if the user has not selected the Remember checkbox for a column setting and the Remember All checkbox is not selected
        //then forget the user's saved setting for that column.
        //To Do AVB - Again this is another piece of code that code be removed if Remember All doesn't even up being used for Plant Portal
        foreach ($userSettings as $column => $setting) {
          if (!isset($userSettings[$column.' '.'Remember']) && $column!='RememberAll')
            unset($userSettings[$column]);
        }
        hostsite_set_user_field("import_field_mappings", json_encode($userSettings));
      }
      $post = array_merge($options['auth']['write_tokens'], $metadata);
      // store the warehouse user ID if we know it.
      if (function_exists('hostsite_get_user_field')) 
        $post['user_id'] = hostsite_get_user_field('indicia_user_id');
      $request = parent::$base_url."index.php/services/plant_portal_import/cache_upload_metadata?uploaded_csv=$filename";
      $response = self::http_post($request, $post);
      if (!isset($response['output']) || $response['output'] != 'OK')
        return "Could not upload the mappings metadata. <br/>".print_r($response, true);
      $warehouseUrl = self::get_warehouse_url();
            
      self::$onload_javascript .= "
    /**
    * Upload a single chunk of a file, by doing an AJAX get. If there is more, then on receiving the response upload the
    * next chunk.
    */
    uploadChunk = function() {
      var limit=50;
      $.ajax({
        url: '".$warehouseUrl."index.php/services/plant_portal_import/upload?offset='+total+'&limit='+limit+'&filepos='+filepos+'&uploaded_csv=$filename&model=".$options['model']."',
        dataType: 'jsonp',
        success: function(response) {
          total = total + response.uploaded;
          filepos = response.filepos;
          jQuery('#progress-text').html(total + ' records uploaded.');
          $('#progress-bar').progressbar ('option', 'value', response.progress);
          if (response.uploaded>=limit) {
            uploadChunk();
          } else {
            jQuery('#progress-text').html('Upload complete.');
            window.location = '$reloadpath&total='+total;
          }
        }
      });
    };
    var total=0, filepos=0;
    jQuery('#progress-bar').progressbar ({value: 0});
    uploadChunk();
    ";
    }
    return $r;
  }
  
  /* Plant Portal has quite a lot of extra logic that the "normal" importer doesn't have, and this is handled here. For instance, it creates groups and trys to attach occurrences to existing samples.
   * @param array $args Array of arguments passed from the Edit Tab.
   * @param array $options Array of options passed to the import control.
   */
  private static function plant_portal_import_logic($args,$options) {  
    $reload = self::get_reload_link_parts();
    unset($reload['params']['total']);
    unset($reload['params']['uploaded_csv']);
    $reloadpath = $reload['path'] . '?' . self::array_to_query_string($reload['params']);
    $r =  "<form method=\"post\" id=\"entry_form\" action=\"$reloadpath\" class=\"iform\">\n".
          "<fieldset><legend>".lang::get('Import allocations')."</legend><br>\n";
    //As we have an extra import step, create a hidden version of the settings and mappings form otherwise the $_POST data used for mappings gets lost because of the extra wizard step
    $r .= self::get_hidden_upload_mappings_form($options);
    $auth = self::get_read_write_auth($args['website_id'], $args['password']);
    $fileArray = file($_SESSION['uploaded_file']);
    if (empty($_SESSION['chosen_column_headings'])) {
      $_SESSION['chosen_column_headings']=self::store_column_header_names_for_existing_match_checks($args);
      $chosenColumnHeadings=$_SESSION['chosen_column_headings']; 
    }
    $headerLineItems = explode(',',$fileArray[0]);
    //Remove the header row from the file
    unset($fileArray[0]);
    //Get the position of each of the columns required for existing match checks. For instance we can know that the Plot Group is in column 3
    $columnHeadingIndexPositions=self::get_column_heading_index_positions($headerLineItems,$chosenColumnHeadings);

    //Cycle through each row excluding the header row and convert into an array
    foreach ($fileArray as $fileLine) {
      //Trim first otherwise we will attempt to process rows which might be just whitespace
      if (!empty(trim($fileLine))) {
        $explodedLine = explode(',',$fileLine);
        //Remove white space start and end of items
        foreach ($explodedLine as $lineItemIdx => $lineItem) {
          $explodedLine[$lineItemIdx]=trim($lineItem);
        }
        $fileRowsAsArray[]=$explodedLine;
      }
    }
    
    //Collect the samples and groups the user has rights to, from here we can also work out which samples and plots they have rights to
    $sampleGroupsAndPlotGroupsUserHasRightsTo = self::get_sample_groups_and_plot_groups_user_has_rights_to($auth,$args);
    $fileArrayForImportRowsToProcess = self::check_user_samples_against_import_data($args,$fileRowsAsArray,$sampleGroupsAndPlotGroupsUserHasRightsTo,$columnHeadingIndexPositions);
    //The screen for displaying import details has two sections, one for warnings, and the other for problems that are is mandatory to correct before allowing continue
    $r .= self::display_import_warnings($args,$fileArrayForImportRowsToProcess,$headerLineItems,$args['nonFatalImportTypes']);
    $rFatal =self::display_import_warnings($args,$fileArrayForImportRowsToProcess,$headerLineItems,$args['fatalImportTypes']);
    if (empty($rFatal))
      $fatalErrorsFound=false;
    else 
      $fatalErrorsFound=true;
    //If fatal warning are found, then we cannot continue the import. Warn the user in red.
    if ($fatalErrorsFound===true) {
      $r .= '<div style="color:red">The import cannot currently continue because of the following problems:</div>'.$rFatal;
      $r .=self::get_upload_reupload_buttons_with_fatal_errors($args);
    } else {
      $r .= $rFatal;
      //Display upload buttons and also code for clicking the actual upload button
      $r .=self::get_upload_reupload_buttons_when_no_fatal_errors($args);
      $r .=self::get_upload_click_function($args,$fileArrayForImportRowsToProcess,$columnHeadingIndexPositions);
    }
    return $r;
  }
  
  /*
   * Buttons at the end of the wizard to allow the user to continue with their upload, or alternatively, make corrections to the file before importing
   */
  private static function get_upload_reupload_buttons_with_fatal_errors($args) {
    //User can customise text on screen, provide defaults if customised text not provided
    if (empty($args['import_fatal_errors_screen_instructions'])) {
      $args['import_fatal_errors_screen_instructions']='Please check the above sections as some of the import data is in a state which means the import cannot currently continue. '
              . 'You will need to correct the data in the original upload file before clicking the reupload button which'
            . ' in your import files needs correcting, you can use the information provided above as a guide before <em>editing the original file and uploading the file again.</em>';
    }
    if (empty($args['reupload_label']))
      $args['reupload_label']='Upload again with corrections made';
    if (empty($args['reupload_button']))
      $args['reupload_button']='Upload file';

    $r = '';
    //Need import step one to return to mappings page
    $r .= '<input type="hidden" id="import_step" name="import_step" value="1" />';
    $r .= '<div>'.$args['import_fatal_errors_screen_instructions'].'<br><br></div>';
    $r .= '<input type="submit" name="submit" id="re-upload-import" value="'.$args['reupload_button'].'" class="ui-corner-all ui-state-default button" />';
    $r .= '<div id="import-loading-msg" style="display:none"/> '.$args['loading_import_wait_text'].'</div>';
    $r .= '</fieldset></form>';
    return $r;
  }
  
  /*
   * Buttons at the end of the wizard to allow the user to continue with their upload, or alternatively, make corrections to the file before importing
   */
  private static function get_upload_reupload_buttons_when_no_fatal_errors($args) {
    //User can customise text on screen, provide defaults if customised text not provided
    if (empty($args['import_warnings_screen_instructions'])) {
      $args['import_warnings_screen_instructions']='Please check the above sections. It gives details on what will happen to your records once they are imported. If you believe any of the data'
            . ' in your import files needs correcting, you can use the information provided above as a guide before <em>editing the original file and uploading the file again.</em>';
    }
    if (empty($args['reupload_label']))
      $args['reupload_label']='Upload again with corrections made';
    if (empty($args['continue_import_label']))
      $args['continue_import_label']='Continue with existing';
    if (empty($args['reupload_button']))
      $args['reupload_button']='Upload file';
    if (empty($args['continue_button']))
      $args['continue_button']='Continue with import';
    if (empty($args['loading_import_wait_text']))
      $args['loading_import_wait_text']='Please wait while we import your data...';
    $r = '';
    $r .= '<input type="hidden" id="import_step" name="import_step" value="3" />';
    $r .= '<div>'.$args['import_warnings_screen_instructions'].'<br><br></div>';
    $r.='<form action="">
      <input type="radio" id="reupload" name="reupload-choice" value="reupload"> '.$args['reupload_label'].'<br>
      <input type="radio" id="continue" name="reupload-choice" value="continue"> '.$args['continue_import_label'].'<br>
    </form>';
    //Show appropriate button depending on what user wants to do. Also make sure the import step is correct for the option the user chooses
    data_entry_helper::$javascript .= "
      $('#reupload').click(function () {
        $('#import_step').val(1);
        $('#re-upload-import').show();
        $('#create-import-data').hide();
      });
      $('#continue').click(function () {
        $('#import_step').val(3);
        $('#re-upload-import').hide();
        $('#create-import-data').show();
      });\n";
    $r .= '<input type="submit" name="submit" id="re-upload-import" value="'.$args['reupload_button'].'" class="ui-corner-all ui-state-default button" style="display:none"/>';
    //If we use a normal button here (Rather than submit). We can use a click function to do some processing logic before forcing the submit using the same function.
    $r .= '<input type="button" name="submit" id="create-import-data" value="'.$args['continue_button'].'" class="ui-corner-all ui-state-default button" style="display:none"/>';
    $r .= '<input type="submit" name="submit" id="submit-import" style="display:none"/>';
    $r .= '<div id="import-loading-msg" style="display:none"/> '.$args['loading_import_wait_text'].'</div>';
    $r .= '</fieldset></form>';
    return $r;
  }
  
  /*
   * Perform the upload when the user clicks on the import button
   */
  private static function get_upload_click_function($args,$fileArrayForImportRowsToProcess,$columnHeadingIndexPositions) {
    $plotNamesToProcess=array();
    $plotSrefsToProcess=array();
    $plotSrefSystemsToProcess=array();
    $sampleGroupNamesToProcess=array();
    $plotGroupNamesToProcess=array();
    //If we are going to add plots groups and sample groups to those termlists then we need their termlist ID
    $sampleGroupTermlistId=$args['sample_group_termlist_id'];
    $plotGroupTermlistId=$args['plot_group_termlist_id'];

    $websiteId=$args['website_id'];
    //If we create the plots before we upload to the warehouse, then the new plots will already be available for the warehouse to use.
    //Extract the data we are going to need to create, keeping in mind it needs to be distinct.
    //The data is split into different sections to be treated in different ways.
    //We may need to create data in certain situations before sending to the warehouse.
    //This is because the warehouse will do things like lookup locations for the sample, but not create them.
    //For plots situations are:
    //If a new sample and new sample group and new plot and new plot group is required, as all these need creating apart from the sample (which is created by the warehouse)
    //or if an existing sample group is to be used (as the other elements will need to be new)
    //or if an existing sample group and existing plot group is to be used (as the other elements will need to be new)
    //or if an existing plot group is to be used (as the other elements will need to be new)
    //To Do AVB - Double check this import types are correct
    $importTypesToProcess=array('nS-nSG-nP-nPG','eSG','eSG-ePG','ePG');
    $plotDataToProcess=self::extract_data_to_create_from_import_rows($fileArrayForImportRowsToProcess,$columnHeadingIndexPositions,'plot',$importTypesToProcess);
    //Do the same for both sample group and plot group data but using different import situations
    $importTypesToProcess=array('nS-nSG-nP-nPG','eP-nSG','ePG');
    $sampleGroupDataToProcess=self::extract_data_to_create_from_import_rows($fileArrayForImportRowsToProcess,$columnHeadingIndexPositions,'sample_group',$importTypesToProcess);
    $importTypesToProcess=array('nS-nSG-nP-nPG','eSG');
    $plotGroupDataToProcess=self::extract_data_to_create_from_import_rows($fileArrayForImportRowsToProcess,$columnHeadingIndexPositions,'plot_group',$importTypesToProcess);
    //Cycle through each plot we need to create and get its name and spatial reference and spatail reference system (if available,
    //else we fall back on the sref system supplied on the Settings page
    foreach ($plotDataToProcess as $plotDataToAdd) {
      $plotNamesToProcess[]=$plotDataToAdd['name'];
      $plotSrefsToProcess[]=$plotDataToAdd['sref'];
      $plotSrefSystemsToProcess[]=$plotDataToAdd['sref_system'];
    }
    foreach ($sampleGroupDataToProcess as $groupToAdd) {
      $sampleGroupNamesToProcess[]=$groupToAdd['name'];
    }
    foreach ($plotGroupDataToProcess as $groupToAdd) {
      $plotGroupNamesToProcess[]=$groupToAdd['name'];
    }
    //When the import button is clicked do the following
    //- Disable the button to prevent double-clicking
    //- Show a Please Wait message to the user
    //- Create any new plots, sample groups, plot groups that are required.
    //- Submit the import to the warehouse
    if (!empty($websiteId))
      data_entry_helper::$javascript .= "var websiteId = ".$websiteId.";";
    
    if (!empty($plotNamesToProcess) && !empty($plotSrefsToProcess) && !empty($plotSrefSystemsToProcess)) {
      data_entry_helper::$javascript .= "
          var plotNamesToProcess = ".json_encode($plotNamesToProcess).";
          var plotSrefsToProcess = ".json_encode($plotSrefsToProcess).";
          var plotSrefSystemsToProcess = ".json_encode($plotSrefSystemsToProcess).";";
    } else {
      data_entry_helper::$javascript .= "
          var plotNamesToProcess = [];
          var plotSrefsToProcess = [];
          var plotSrefSystemsToProcess = [];";
    }
      
    if (!empty($sampleGroupDataToProcess) && !empty($sampleGroupTermlistId)) {
      data_entry_helper::$javascript .= "
        var sampleGroupNamesToProcess = ".json_encode($sampleGroupNamesToProcess).";
        var sampleGroupTermlistId = ".$sampleGroupTermlistId.";";
    } else {
      data_entry_helper::$javascript .= "
        var sampleGroupNamesToProcess = [];
        var sampleGroupTermlistId = [];";
    }
    
    if (!empty($plotGroupDataToProcess) && !empty($plotGroupTermlistId)) {
      data_entry_helper::$javascript .= "
        var plotGroupNamesToProcess = ".json_encode($plotGroupNamesToProcess).";
        var plotGroupTermlistId = ".$plotGroupTermlistId.";";
    } else {
      data_entry_helper::$javascript .= "
        var plotGroupNamesToProcess = [];
        var plotGroupTermlistId = [];";
    }
    
    $warehouseUrl = self::get_warehouse_url();
    
    data_entry_helper::$javascript .= "$('#create-import-data').click(function () {
      $('#create-import-data').attr('disabled','true');
      $('#import-loading-msg').show();
      if (websiteId && plotNamesToProcess && plotSrefsToProcess && plotSrefSystemsToProcess) {
        send_new_plots_to_warehouse('".$warehouseUrl."',websiteId,plotNamesToProcess,plotSrefsToProcess,plotSrefSystemsToProcess);
      }
      if (websiteId && sampleGroupNamesToProcess && sampleGroupTermlistId) {
        send_new_groups_to_warehouse('".$warehouseUrl."',websiteId,sampleGroupNamesToProcess,sampleGroupTermlistId);
      }
      if (websiteId && plotGroupNamesToProcess && plotGroupTermlistId) {
        send_new_groups_to_warehouse('".$warehouseUrl."',websiteId,plotGroupNamesToProcess,plotGroupTermlistId);
      }
      $('#submit-import').click();
    });";
  }
  
  //To Do AVB - Currently the mappings drop-down contains mappings we will probably not use
  
  /*
   * Function looks at all the rows in the import, and returns an array of data of a particular type that needs to be created.
   * e.g. ['Sample group 1','Sample group 2','Sample group 3']
   * The possibilities are plot, sample_group, plot_group
   */
  private static function extract_data_to_create_from_import_rows($fileArrayForImportRowsToProcess,$columnHeadingIndexPositions,$extractionType,$importTypesToProcess) {
    $dataExtractedReadyToProcess=array();
    foreach ($importTypesToProcess as $importTypeToProcess) {
      //Don't need to proceed if there is no data for a particular import sitation
      if (!empty($fileArrayForImportRowsToProcess[$importTypeToProcess])) {
        //Cycle through each data row in the import situations we need to create data for
        foreach ($fileArrayForImportRowsToProcess[$importTypeToProcess] as $rowToExtractDataFrom) {
          $dataFromRow=array();
          if ($extractionType==='plot') {
            $dataFromRow = self::extract_plot_to_create_from_import_row_if_we_need_to($rowToExtractDataFrom,$dataExtractedReadyToProcess,$columnHeadingIndexPositions);
          }
          if ($extractionType==='sample_group'||$extractionType==='plot_group') {
            $dataFromRow = self::extract_group_to_create_from_import_row_if_we_need_to($rowToExtractDataFrom,$dataExtractedReadyToProcess,$columnHeadingIndexPositions,$extractionType);
          }
          //If there is some data to create for the row then it can be stored in an array for processing.
          if (!empty($dataFromRow['name'])) {
            $dataExtractedReadyToProcess[]=$dataFromRow;
          }
        }
      }
    }  
    return $dataExtractedReadyToProcess;
  }
  
  /* 
   * Extract the data relating to new plots so that they can be created before passing the import to the warehouse 
   * @param array $rowToExtractDataFrom Array of columns from row we want to extract data from
   * @param array $dataExtractedReadyToProcess Array of data already extracted (so we can make sure the plot hasn't already been listed for creation)
   * @param array $columnHeadingIndexPositions Array which contains the position of each of the columns relevant to the duplicate check.
   */
  private static function extract_plot_to_create_from_import_row_if_we_need_to($rowToExtractDataFrom,$dataExtractedReadyToProcess,$columnHeadingIndexPositions) {
    $doNotCreatePlotForRow=false;
    //Get spatial reference system from row if we can, else get from Settings page
    if ($columnHeadingIndexPositions['sampleSrefSystemHeaderIdx']!=-1)
      $spatialReferenceSystem=$rowToExtractDataFrom[$columnHeadingIndexPositions['sampleSrefSystemHeaderIdx']];
    else
      $spatialReferenceSystem=$_SESSION['sample:entered_sref_system'];
    //If the index position is -1, it means that column does not appear in the data file
    //We can only create plots if there is a plot name, and a spatial reference (we know there is a spatial reference system as we set it above)
    if ($columnHeadingIndexPositions['plotNameHeaderIdx']!=-1&&$columnHeadingIndexPositions['sampleSrefHeaderIdx']!=-1) { 
      if (!empty($dataExtractedReadyToProcess)) {
        //Check the existing data that we have already extracted to see if the plot already exists, if it does, it doesn't need to be re-extracted
        foreach ($dataExtractedReadyToProcess as $plotAlreadyMarkedForCreation) {
          //Only need to continue if not already true, otherwise we already have a true result and don't need further processing
          if ($doNotCreatePlotForRow==false) {
            if (!empty($rowToExtractDataFrom[$columnHeadingIndexPositions['plotNameHeaderIdx']])&&!empty($rowToExtractDataFrom[$columnHeadingIndexPositions['sampleSrefHeaderIdx']])) {
              $doNotCreatePlotForRow=self::detect_if_plot_already_listed_for_creation($plotAlreadyMarkedForCreation,$rowToExtractDataFrom,$columnHeadingIndexPositions,$doNotCreatePlotForRow,$spatialReferenceSystem);
            } else {
              //Do not create a plot if the plot doesn't have its name and spatial reference specified
              $doNotCreatePlotForRow=true;
            }
          }
        }
      }
    } else {
      //We don't want to attempt to create a plot if the columns are not even defined in the import data
      $doNotCreatePlotForRow=true;
    }
    //Only continue if the data hasn't already been extracted
    if ($doNotCreatePlotForRow===true) {
      //If we know the data has already extracted, mark with null
      $dataFromRow['name']=null;
      $dataFromRow['sref']=null;
    } else {
      $dataFromRow['name']=$rowToExtractDataFrom[$columnHeadingIndexPositions['plotNameHeaderIdx']];
      $dataFromRow['sref']=$rowToExtractDataFrom[$columnHeadingIndexPositions['sampleSrefHeaderIdx']];
      $dataFromRow['sref_system']=$spatialReferenceSystem;
      if (!empty($rowToExtractDataFrom[$columnHeadingIndexPositions['plotGroupNameHeaderIdx']]))
        $dataFromRow['plot_group_name']=$rowToExtractDataFrom[$columnHeadingIndexPositions['plotGroupNameHeaderIdx']];
    }
    return $dataFromRow;
  }
  
  /*
   * Function detects if a plot in the existing data has already be stored so that it can be created, if it has, we don't have to store it again
   * @param array $rowToExtractDataFrom Array of columns from row we want to extract data from
   * @param array $plotAlreadyMarkedForCreation Array A plot marked for creation already, if the data row's plot matches this, we know we don't have to recreate it
   * @param array $columnHeadingIndexPositions Array which contains the position of each of the columns relevant to the duplicate check.
   * @param boolean $doNotCreatePlotForRow Track whether the plot needs to be created
   * @param string $spatialReferenceSystem The spatial reference for the $rowToExtractDataFrom
   */
  private static function detect_if_plot_already_listed_for_creation($plotAlreadyMarkedForCreation,$rowToExtractDataFrom,$columnHeadingIndexPositions,$doNotCreatePlotForRow,$spatialReferenceSystem) {
    //Detect if the plot matches the plot we are checking in the plot stored for creation
    //Note we cannot do this just using $doNotCreatePlotForRow, as we don't want that variable to be false once it is true and the $matchFound variable can be set to false again
    //in given situations below
    $matchFound=false;
    if ($plotAlreadyMarkedForCreation['name']==$rowToExtractDataFrom[$columnHeadingIndexPositions['plotNameHeaderIdx']]&&
        $plotAlreadyMarkedForCreation['sref']==$rowToExtractDataFrom[$columnHeadingIndexPositions['sampleSrefHeaderIdx']]) {
      $matchFound=true;
      //The Plot Group is not mandatory, we can't match empty variables, so set temporary variables to -1 if empty to allow a test for empty
      if (empty($plotAlreadyMarkedForCreation['plot_group_name']))
        $plotAlreadyMarkedForCreationTempPlotGroupTest = -1;
      else 
        $plotAlreadyMarkedForCreationTempPlotGroupTest = $plotAlreadyMarkedForCreation['plot_group_name'];
      if (empty($rowToExtractDataFrom[$columnHeadingIndexPositions['plotGroupNameHeaderIdx']]))
        $rowToExtractDataFromTempPlotGroupTest = -1;
      else 
        $rowToExtractDataFromTempPlotGroupTest = $rowToExtractDataFrom[$columnHeadingIndexPositions['plotGroupNameHeaderIdx']];
      if ($plotAlreadyMarkedForCreationTempPlotGroupTest!=$rowToExtractDataFromTempPlotGroupTest) {
        $matchFound=false;
      }
      //The spatial reference system row needs to be treated slightly differently as it is not mandatory on the row, it might of been specified in the
      //initial import settings. If we find that we had a match, but in fact the system is different, then we need to reflag it as a none match.
      //In reality we probably wouldn't have the situation where the sref's were the same but the system was different
      if (!empty($plotAlreadyMarkedForCreation['sref_system']) && $plotAlreadyMarkedForCreation['sref_system']!=$spatialReferenceSystem) {
        $matchFound=false;
      }
    }
    if ($matchFound===true)
      $doNotCreatePlotForRow=true;
    return $doNotCreatePlotForRow;
  }
  
  /* 
   * Extract the data relating to new groups so that they can be created before passing the import to the warehouse 
   * @param array $rowToExtractDataFrom Array of columns from row we want to extract data from
   * @param array $dataExtractedReadyToProcess Array of data already extracted (so we can make sure the group hasn't already been listed for creation)
   * @param array $columnHeadingIndexPositions Array which contains the position of each of the columns relevant to the duplicate check.
   * @param string $extractionType String which tells us whether it is sample or plot groups we are dealing with
   */
  private static function extract_group_to_create_from_import_row_if_we_need_to($rowToExtractDataFrom,$dataExtractedReadyToProcess,$columnHeadingIndexPositions,$extractionType) {
    $doNotCreateGroupForRow=false;
    if ($extractionType==='sample_group')
      $groupColoumnIdx=$columnHeadingIndexPositions['sampleGroupNameHeaderIdx'];
    if ($extractionType==='plot_group')
      $groupColoumnIdx=$columnHeadingIndexPositions['plotGroupNameHeaderIdx'];
    //If the index position is -1, it means that column does not appear in the data file
    //We can only create groups if there is a group name
    if ($groupColoumnIdx!=-1) { 
      if (!empty($dataExtractedReadyToProcess)) {
        foreach ($dataExtractedReadyToProcess as $groupAlreadyMarkedForCreation) {
          //Only need to continue if not already true, otherwise we already have a true result and don't need further processing
          if ($doNotCreateGroupForRow==false) {
            //Do not create a plot if there is no group name on the row we are extracting
            if (!empty($rowToExtractDataFrom[$groupColoumnIdx])) {
              //If the the group has already been listed for creation, then there is no need to relist it
              if ($groupAlreadyMarkedForCreation['name']==$rowToExtractDataFrom[$groupColoumnIdx]) {
                $doNotCreateGroupForRow=true;
              }
            } else {
              $doNotCreateGroupForRow=true;
            }
          }
        }
      }
    } else {
      //We don't want to attempt to create a group if the column is not even defined in the import row
      $doNotCreateGroupForRow=true;
    }
    //Only continue if the data hasn't already been extracted
    if ($doNotCreateGroupForRow===true) {
      //If we know the data has already extracted, mark with null
      $dataFromRow['name']=null;
    } else {
      $dataFromRow['name']=$rowToExtractDataFrom[$groupColoumnIdx];
    }
    return $dataFromRow;
  }
  
  /*
   * Display messages to the user describing how the import will proceed
   * @param Array $args Array of all the arguments from the edit tab
   * @param Array $fileArrayForImportRowsToProcesss contains all the rows that we need to import
   * @param Array $headerLineItems Array of all headers in the first row of the file
   * @param Array $importTypes Array of all the import situations we want to display messages for
   */
  private static function display_import_warnings($args,$fileArrayForImportRowsToProcess,$headerLineItems,$importTypes) {
    $r='';    
    foreach ($importTypes as $importTypeCode=>$importTypeStates) {
      if (!empty($args[$importTypeCode.'_message']))
        $warningText=$args[$importTypeCode.'_message'];
      $r .= self::display_individual_message($fileArrayForImportRowsToProcess,$warningText,$importTypeCode,$headerLineItems);
    }
    return $r;
  }

  /*
   * Display a warning of a particular type before the user continues with the import
   * @param Array $fileArrayForImportRowsToProcess Array all the import rows applicable to the warning
   * @param String $warningText Warning to display to the user
   * @param $warningCode The code for the import situation we are dealing with
   * @param $headerLineItems All the import headers so that the import rows we want to display can be listed
   * 
   */
  private static function display_individual_message($fileArrayForImportRowsToProcess,$warningText,$warningCode,$headerLineItems) {
    $r='';
    if (!empty($fileArrayForImportRowsToProcess[$warningCode])) {
      $r.='<div>'.$warningText.'</div>';
      $r .= '<table><tr>';
      foreach ($headerLineItems as $headerLineItem) {
        $r.='<th>'.$headerLineItem.'</th>';
      }
      $r .= '</tr>'; 
      foreach ($fileArrayForImportRowsToProcess[$warningCode] as $theRow) { 
        $r .= '<tr>';
        foreach ($theRow as $theRowCellNum => $theRowCell) {
          $r.='<td>'.$theRowCell.'</td>';
        }
        $r .= '</tr>';     
      }
      $r .= '</table><br>';
      return $r;
    }
  }

  /*
   * Store the custom header names in the CSV file to an array with standardised array
   * key names (e.g. so we know that $chosenColumnHeadings['sampleDateHeaderName'] will always hold the
   * sample data column header).
   * This can only be done after the user has mapped the columns.
   * This is only required for columns we are going to use for matching samples, plots, sample groups
   * and plot groups against existing ones.
   * @param Array $args Arguments from Edit Tab
   */
  private static function store_column_header_names_for_existing_match_checks($args) {   
    $chosenColumnHeadings=array();
    foreach ($_POST as $amendedTableHeaderWith_ => $chosenField) {
      if ($chosenField==='sample:date') {
        //As all the column headings in the post have underscores, we need to replace these with spaces to get back to the original
        //column heading
        $chosenColumnHeadings['sampleDateHeaderName'] = str_replace('_', ' ', $amendedTableHeaderWith_);
      }
      if ($chosenField==='sample:entered_sref')
        $chosenColumnHeadings['sampleSrefHeaderName'] = str_replace('_', ' ', $amendedTableHeaderWith_);
      if ($chosenField==='sample:entered_sref_system')
        $chosenColumnHeadings['sampleSrefSystemHeaderName'] = str_replace('_', ' ', $amendedTableHeaderWith_);
      if ($chosenField==='smpAttr:'.$args['sample_group_identifier_name_attr_id'])
        $chosenColumnHeadings['sampleGroupNameHeaderName'] = str_replace('_', ' ', $amendedTableHeaderWith_);
      if ($chosenField==='sample:fk_location')
        $chosenColumnHeadings['plotNameHeaderName'] = str_replace('_', ' ', $amendedTableHeaderWith_);
      if ($chosenField==='locAttr:'.$args['plot_group_identifier_name_attr_id'])
        $chosenColumnHeadings['plotGroupNameHeaderName'] = str_replace('_', ' ', $amendedTableHeaderWith_);
    }
    return $chosenColumnHeadings;
  }  
 
 /*
   * Save the horizontal position of each column that is to be used when matching existing Samples, Plots, Sample
   * Groups and Plot Groups.
   * This allows us to count along a data row and know what we are looking at without re-examining the headings.
   * Headings are saved as an index from zero.
   * 
   * @param Array $headerLineItems Array of header names from first line of import file
   * @param Array $chosenColumnHeadings The actual header names used in the file stored with their meaning
   * as the array key
   */
  private static function get_column_heading_index_positions($headerLineItems,$chosenColumnHeadings) {   
    $columnHeadingIndexPositions=array();
     //We can't leave these uninitialised as we will get loads of not initialised errors.
    //Overcome this by setting to -1, which is an index we will never actually use.
    $columnHeadingIndexPositions['sampleDateHeaderIdx']=-1;
    $columnHeadingIndexPositions['sampleSrefHeaderIdx']=-1;
    $columnHeadingIndexPositions['sampleSrefSystemHeaderIdx']=-1;
    $columnHeadingIndexPositions['sampleGroupNameHeaderIdx']=-1;
    $columnHeadingIndexPositions['plotNameHeaderIdx']=-1;
    $columnHeadingIndexPositions['plotGroupNameHeaderIdx']=-1;
    //Cycle through all the names from the header line, then check to see if there is a match in the array holding the 
    //header names meanings. If there is a match, it means we have identified the header and can save its position as
    //and index starting from zero.
    foreach ($headerLineItems as $idx=>$header) {
      //Remove white space from the ends of the headers
      $header=trim($header);
      if (!empty($chosenColumnHeadings['sampleDateHeaderName']) && $header == $chosenColumnHeadings['sampleDateHeaderName']) 
        $columnHeadingIndexPositions['sampleDateHeaderIdx'] = $idx;
      if (!empty($chosenColumnHeadings['sampleSrefHeaderName']) && $header == $chosenColumnHeadings['sampleSrefHeaderName']) 
        $columnHeadingIndexPositions['sampleSrefHeaderIdx'] = $idx;
      if (!empty($chosenColumnHeadings['sampleSrefSystemHeaderName']) && $header == $chosenColumnHeadings['sampleSrefSystemHeaderName']) 
        $columnHeadingIndexPositions['sampleSrefSystemHeaderIdx'] = $idx;
      if (!empty($chosenColumnHeadings['sampleGroupNameHeaderName']) && $header == $chosenColumnHeadings['sampleGroupNameHeaderName'])
        $columnHeadingIndexPositions['sampleGroupNameHeaderIdx'] = $idx;
      if (!empty($chosenColumnHeadings['plotNameHeaderName']) && $header == $chosenColumnHeadings['plotNameHeaderName'])
        $columnHeadingIndexPositions['plotNameHeaderIdx'] = $idx;
      if (!empty($chosenColumnHeadings['plotGroupNameHeaderName']) && $header == $chosenColumnHeadings['plotGroupNameHeaderName'])
       $columnHeadingIndexPositions['plotGroupNameHeaderIdx'] = $idx;
    }
    return $columnHeadingIndexPositions;
  }
    
  /*
   * Check the samples/plots the user has permission to use against the import data.
   * Once that is done, we are able to determine and report to the user how the data will be imported
   * e.g. (will we use new samples, existing examples etc)
   * @param Array $args Arguments from the Edit Tab
   * @param Array $fileRowsAsArray Rows from import file
   * @param Array $sampleGroupsAndPlotGroupsUserHasRightsTo Samples and plots the user has rights to
   * @param Array $columnHeadingIndexPositions The position from the left of some of the more important columns starting at position 0
   */
  private static function check_user_samples_against_import_data($args,$fileRowsAsArray,$sampleGroupsAndPlotGroupsUserHasRightsTo,$columnHeadingIndexPositions) {
    //Store the rows into the different import categories ready to process
    $fileArrayForImportRowsToProcess=array();  
    if (!empty($fileRowsAsArray)) {
      //Cycle through each row in the import data
      foreach ($fileRowsAsArray as $idx => &$fileRowsAsArrayLine) {
        $lineState=array('existingSample'=>0,'existingPlot'=>0,'newSampleExistingSampleGroup'=>0,'newPlotExistingPlotGroup'=>0);
        //Check the data on each list to see if it falls into the category of existing sample, existing sample group, existing plot, existing plot group,
        //The $lineState is then altered by each function
        self::existing_sample_check_for_line($fileRowsAsArrayLine,$sampleGroupsAndPlotGroupsUserHasRightsTo['samplesUserHasRightsTo'],$lineState,$columnHeadingIndexPositions);
        self::existing_plot_check_for_line($fileRowsAsArrayLine,$sampleGroupsAndPlotGroupsUserHasRightsTo['plotsUserHasRightsTo'],$lineState,$columnHeadingIndexPositions);
        //Only need to set newSampleExistingSampleGroup flag if existingSample is 0
        if ($lineState['existingSample']==0)
          self::existing_group_check_for_line($fileRowsAsArrayLine,$sampleGroupsAndPlotGroupsUserHasRightsTo['sampleGroupsUserHasRightsTo'],$lineState,$columnHeadingIndexPositions,'sample');
        //Only need to set newPlotExistingPlotGroup flag if existingPlot is 0
        if ($lineState['existingPlot']==0)
          self::existing_group_check_for_line($fileRowsAsArrayLine,$sampleGroupsAndPlotGroupsUserHasRightsTo['plotGroupsUserHasRightsTo'],$lineState,$columnHeadingIndexPositions,'plot');
        //Save rows into the import categories which are stored as keys in the $fileArrayForImportRowsToProcess array
        self::assign_import_row_into_import_category($fileArrayForImportRowsToProcess,$fileRowsAsArrayLine,$lineState,$args['nonFatalImportTypes']);
        self::assign_import_row_into_import_category($fileArrayForImportRowsToProcess,$fileRowsAsArrayLine,$lineState,$args['fatalImportTypes']);
      }
    }
    return $fileArrayForImportRowsToProcess;
  }
  
  /*
   * Return whether a particular import row matches an existing sample.
   * If there is more that one match, then 2 is return instead of 1.
   * @param Array $fileRowsAsArrayLine Line was are looking at from the import file
   * @param Array $samplesUserHasRightsTo The samples the user has rights to use
   * @param Array @lineState The array to return the result to
   * @param Array $columnHeadingIndexPositions The positions from the left of the most important columns in the import file, starting with 0 at the left
   * 
   */
  private static function existing_sample_check_for_line(&$fileRowsAsArrayLine,$samplesUserHasRightsTo,&$lineState,$columnHeadingIndexPositions) {
    //Only interested in the samples the user has rights to, if they don't have any anyway, we don't need to return anything from this function
    foreach ($samplesUserHasRightsTo as $aSampleUserHasRightsTo) {
      //If spatial reference system isn't on a import row, then get it from the mappings page
      if ($columnHeadingIndexPositions['sampleSrefSystemHeaderIdx']!=-1)
         $spatialReferenceSystem=$fileRowsAsArrayLine[$columnHeadingIndexPositions['sampleSrefSystemHeaderIdx']];
       else
        $spatialReferenceSystem=$_SESSION['sample:entered_sref_system'];
      //Only indicate existing sample, if the sample the user has rights to matches the row we are using from the import file.
      //Here we can pass the test if both comparison columns are empty, the other pass scenario is if the columns are both filled in and they match as well.
      //All comparisons using the relevant columns must match for sample to count as existing
      if (((empty($fileRowsAsArrayLine[$columnHeadingIndexPositions['sampleSrefHeaderIdx']])&&empty($aSampleUserHasRightsTo['entered_sref']))||
          ((!empty($fileRowsAsArrayLine[$columnHeadingIndexPositions['sampleSrefHeaderIdx']])&&!empty($aSampleUserHasRightsTo['entered_sref'])) &&
          strtolower($fileRowsAsArrayLine[$columnHeadingIndexPositions['sampleSrefHeaderIdx']])==strtolower($aSampleUserHasRightsTo['entered_sref']))) &&
              
          ((empty($spatialReferenceSystem)&&empty($aSampleUserHasRightsTo['entered_sref_system']))||
          ((!empty($spatialReferenceSystem)&&!empty($aSampleUserHasRightsTo['entered_sref_system'])) &&
          strtolower($spatialReferenceSystem)==strtolower($aSampleUserHasRightsTo['entered_sref_system']))) &&   
              
          ((empty($fileRowsAsArrayLine[$columnHeadingIndexPositions['plotNameHeaderIdx']])&&empty($aSampleUserHasRightsTo['plot_name']))||
          ((!empty($fileRowsAsArrayLine[$columnHeadingIndexPositions['plotNameHeaderIdx']])&&!empty($aSampleUserHasRightsTo['plot_name'])) &&
          strtolower($fileRowsAsArrayLine[$columnHeadingIndexPositions['plotNameHeaderIdx']])==strtolower($aSampleUserHasRightsTo['plot_name']))) &&
              
          //To DO AVB, we are going to need to convert any dates before comparisons are made
          ((empty($fileRowsAsArrayLine[$columnHeadingIndexPositions['sampleDateHeaderIdx']])&&empty($aSampleUserHasRightsTo['sample_date']))||
              ((!empty($fileRowsAsArrayLine[$columnHeadingIndexPositions['sampleDateHeaderIdx']])&&!empty($aSampleUserHasRightsTo['sample_date'])) &&
              strtolower($fileRowsAsArrayLine[$columnHeadingIndexPositions['sampleDateHeaderIdx']])==strtolower($aSampleUserHasRightsTo['sample_date']))) &&
              
          ((empty($fileRowsAsArrayLine[$columnHeadingIndexPositions['sampleGroupNameHeaderIdx']])&&empty($aSampleUserHasRightsTo['sample_group_identifier_name']))||
              ((!empty($fileRowsAsArrayLine[$columnHeadingIndexPositions['sampleGroupNameHeaderIdx']])&&!empty($aSampleUserHasRightsTo['sample_group_identifier_name']))&&
              strtolower($fileRowsAsArrayLine[$columnHeadingIndexPositions['sampleGroupNameHeaderIdx']])==strtolower($aSampleUserHasRightsTo['sample_group_identifier_name']))) &&
          ((empty($fileRowsAsArrayLine[$columnHeadingIndexPositions['plotGroupNameHeaderIdx']])&&empty($aSampleUserHasRightsTo['plot_group_identifier_name']))||
              ((!empty($fileRowsAsArrayLine[$columnHeadingIndexPositions['plotGroupNameHeaderIdx']])&&!empty($aSampleUserHasRightsTo['plot_group_identifier_name']))&&
              strtolower($fileRowsAsArrayLine[$columnHeadingIndexPositions['plotGroupNameHeaderIdx']])==strtolower($aSampleUserHasRightsTo['plot_group_identifier_name'])))) {
        //Return the code 2 if there are duplicate samples (even if there are more than 2, this is just to indicate duplicates)
        if ($aSampleUserHasRightsTo['sample_count']>1)
          $lineState['existingSample']  = 2;
        else 
          $lineState['existingSample']  = 1;
      } 
    }
  }
  
  /*
   * Return whether a particular import row matches an existing group when a new sample/plot is required.
   * If there is more that one match, then 2 is return instead of 1.
   * @param Array $fileRowsAsArrayLine Line was are looking at from the import file
   * @param Array $groupsUserHasRightsTo The groups the user has rights to use
   * @param Array @lineState The array to return the result to
   * @param Array $columnHeadingIndexPositions The positions from the left of the most important columns in the import file, starting with 0 at the left
   * 
   */
  private static function existing_group_check_for_line(&$fileRowsAsArrayLine,$groupsUserHasRightsTo,&$lineState,$columnHeadingIndexPositions,$groupType) {
    if ($groupType==='sample')
      $lineStateArrayKey='newSampleExistingSampleGroup';
    if ($groupType==='plot')
      $lineStateArrayKey='newPlotExistingPlotGroup';
    //Only interested in the groups the user has rights to, if they don't have any anyway, we don't need to return anything from this function
    foreach ($groupsUserHasRightsTo as $aGroupUserHasRightsTo) {
      //Only indicate existing group, if the group the user has rights to matches the row we are using from the import file.
      //Here we can pass the test if both comparison columns are empty, the other pass scenario is if the columns are both filled in and they match as well.
      if (((empty($fileRowsAsArrayLine[$columnHeadingIndexPositions[$groupType.'GroupNameHeaderIdx']])&&empty($aGroupUserHasRightsTo['group_identifier_name']))||
              ((!empty($fileRowsAsArrayLine[$columnHeadingIndexPositions[$groupType.'GroupNameHeaderIdx']])&&!empty($aGroupUserHasRightsTo['group_identifier_name']))&&
              strtolower($fileRowsAsArrayLine[$columnHeadingIndexPositions[$groupType.'GroupNameHeaderIdx']])==strtolower($aGroupUserHasRightsTo['group_identifier_name'])))) {
        //Return the code 2 if there are duplicate groups (even if there are more than 2, this is just to indicate duplicates)
        if ($aGroupUserHasRightsTo['group_count']>1)
          $lineState[$lineStateArrayKey]  = 2;
        else 
          $lineState[$lineStateArrayKey]  = 1;
      } 
    }
  }
  
  /*
   * Return whether a particular import row matches an existing plot.
   * If there is more that one match, then code 2 is returned instead of 1.
   * @param Array $fileRowsAsArrayLine Line was are looking at from the import file
   * @param Array $plotsUserHasRightsTo The plots the user has rights to use
   * @param Array @lineState The array to return the result to
   * @param Array $columnHeadingIndexPositions The positions from the left of the most important columns in the import file, starting with 0 at the left
   * 
   */
  private static function existing_plot_check_for_line(&$fileRowsAsArrayLine,$plotsUserHasRightsTo,&$lineState,$columnHeadingIndexPositions) {
    //Only interested in the plots the user has rights to, if they don't have any anyway, we don't need to return anything from this function
    foreach ($plotsUserHasRightsTo as $aPlotUserHasRightsTo) {
      //If spatial reference system isn't on a import row, then get it from the mappings page
      if ($columnHeadingIndexPositions['sampleSrefSystemHeaderIdx']!=-1)
         $spatialReferenceSystem=$fileRowsAsArrayLine[$columnHeadingIndexPositions['sampleSrefSystemHeaderIdx']];
      else
        $spatialReferenceSystem=$_SESSION['sample:entered_sref_system'];
      //Only indicate existing plot if the plot the user has rights to matches the row we are using from the import file.
      //Here we can pass the test if both comparison columns are empty, the other pass scenario is if the columns are both filled in and they match as well.
      if (((empty($fileRowsAsArrayLine[$columnHeadingIndexPositions['sampleSrefHeaderIdx']])&&empty($aPlotUserHasRightsTo['entered_sref']))||
          ((!empty($fileRowsAsArrayLine[$columnHeadingIndexPositions['sampleSrefHeaderIdx']])&&!empty($aPlotUserHasRightsTo['entered_sref'])) &&
          strtolower($fileRowsAsArrayLine[$columnHeadingIndexPositions['sampleSrefHeaderIdx']])==strtolower($aPlotUserHasRightsTo['entered_sref']))) &&
              
          ((empty($spatialReferenceSystem)&&empty($aPlotUserHasRightsTo['entered_sref_system']))||
          ((!empty($spatialReferenceSystem)&&!empty($aPlotUserHasRightsTo['entered_sref_system'])) &&
          strtolower($spatialReferenceSystem)==strtolower($aPlotUserHasRightsTo['entered_sref_system']))) &&   
              
          ((empty($fileRowsAsArrayLine[$columnHeadingIndexPositions['plotNameHeaderIdx']])&&empty($aPlotUserHasRightsTo['plot_name']))||
          ((!empty($fileRowsAsArrayLine[$columnHeadingIndexPositions['plotNameHeaderIdx']])&&!empty($aPlotUserHasRightsTo['plot_name']))&&
          strtolower($fileRowsAsArrayLine[$columnHeadingIndexPositions['plotNameHeaderIdx']])==strtolower($aPlotUserHasRightsTo['plot_name']))) &&
          ((empty($fileRowsAsArrayLine[$columnHeadingIndexPositions['plotGroupNameHeaderIdx']])&&empty($aPlotUserHasRightsTo['plot_group_identifier_name']))||
          ((!empty($fileRowsAsArrayLine[$columnHeadingIndexPositions['plotGroupNameHeaderIdx']])&&!empty($aPlotUserHasRightsTo['plot_group_identifier_name']))&&
          strtolower($fileRowsAsArrayLine[$columnHeadingIndexPositions['plotGroupNameHeaderIdx']])==strtolower($aPlotUserHasRightsTo['plot_group_identifier_name'])))) {
        //Return the code 2 if there are duplicate plots (even if there are more than 2, this is just to indicate duplicates)
        if ($aPlotUserHasRightsTo['plot_count']>1)
          $lineState['existingPlot']  = 2;
        else 
          $lineState['existingPlot']  = 1;
      } 
    }
  }
  
  /*
   * Function is given a row for the import. The flags for the row (which have already been set to say whether it is an existing sample, plot, sample group, plot group) are
   * checked against the import situations. When we find a matching situation, we store it against that situation.
   */
  private static function assign_import_row_into_import_category(&$fileArrayForImportRowsToProcess,$fileRowsAsArrayLine,$lineState,$importTypes) {
    foreach ($importTypes as $importTypeCode=>$importTypeStates) {
      if ($lineState==$importTypeStates) {
        $fileArrayForImportRowsToProcess[$importTypeCode][]=$fileRowsAsArrayLine; 
      }
    }
    return $fileArrayForImportRowsToProcess;
  }
  
  private static function get_sample_groups_and_plot_groups_user_has_rights_to($auth,$args) {
    $sampleGroupsAndPlotGroupsUserHasRightsTo=array();
    global $user;
    if (function_exists('hostsite_get_user_field'))
      $currentUserId = hostsite_get_user_field('indicia_user_id');
    $sampleGroupsAndPlotGroupsUserHasRightsTo['samplesUserHasRightsTo']= data_entry_helper::get_report_data(array(
      'dataSource'=>'reports_for_prebuilt_forms/plant_portal/get_samples_from_groups_for_user',
      'readAuth'=>$auth['read'],
      'extraParams'=>array(
                          'sample_group_permission_person_attr_id'=>$args['sample_group_permission_person_attr_id'],
                          'plot_group_permission_person_attr_id'=>$args['plot_group_permission_person_attr_id'],
                          'plot_group_attr_id'=>$args['plot_group_identifier_name_attr_id'],
                          'user_id'=>$currentUserId)
    ));
    $sampleGroupsAndPlotGroupsUserHasRightsTo['plotsUserHasRightsTo']= data_entry_helper::get_report_data(array(
      'dataSource'=>'reports_for_prebuilt_forms/plant_portal/get_plots_from_groups_for_user',
      'readAuth'=>$auth['read'],
      'extraParams'=>array(
                          'plot_group_permission_person_attr_id'=>$args['plot_group_permission_person_attr_id'],
                          'user_id'=>$currentUserId)
    ));
    $sampleGroupsAndPlotGroupsUserHasRightsTo['sampleGroupsUserHasRightsTo']= data_entry_helper::get_report_data(array(
      'dataSource'=>'reports_for_prebuilt_forms/plant_portal/get_groups_for_user',
      'readAuth'=>$auth['read'],
      'extraParams'=>array(
                          'group_permission_person_attr_id'=>$args['sample_group_permission_person_attr_id'],
                          'user_id'=>$currentUserId)
    ));
    $sampleGroupsAndPlotGroupsUserHasRightsTo['plotGroupsUserHasRightsTo']= data_entry_helper::get_report_data(array(
      'dataSource'=>'reports_for_prebuilt_forms/plant_portal/get_groups_for_user',
      'readAuth'=>$auth['read'],
      'extraParams'=>array(
                          'group_permission_person_attr_id'=>$args['plot_group_permission_person_attr_id'],
                          'user_id'=>$currentUserId)
    ));
    return $sampleGroupsAndPlotGroupsUserHasRightsTo;
  }
  
  private static function get_warehouse_url() {
    if (!empty(parent::$warehouse_proxy))
      $warehouseUrl = parent::$warehouse_proxy;
    else
      $warehouseUrl = parent::$base_url;
    return $warehouseUrl;
  }
}