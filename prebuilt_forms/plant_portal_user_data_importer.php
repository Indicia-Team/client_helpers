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
 * @link https://github.com/indicia-team/client_helpers/
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
class iform_plant_portal_user_data_importer extends helper_base {
  //AVB To Do - Currently unknown if remember mappings will work, not currently supported until tested
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
        'name'=>'plot_location_type_id',
        'caption'=>'Plot location type id',
        'description'=>'Id of the Plant Portal plot location type.',
        'type'=>'string',
        'required'=>true,
        'group'=>'Database IDs Required By Form'
      ),
      //AVB is this really needed as don't we create the group first anyway?
      array(
        'name'=>'plot_group_identifier_name_text_attr_id',
        'caption'=>'Plot group identifier name text location attribute ID',
        'description'=>'ID of the location attribute that stores the plot group identifier name as text to assist with the import process.',
        'type'=>'string',
        'required'=>true,
        'group'=>'Database IDs Required By Form'
      ),
      array(
        'name'=>'plot_group_identifier_name_lookup_loc_attr_id',
        'caption'=>'Plot group identifier name lookup location attribute ID',
        'description'=>'ID of the location attribute that stores the plot group identifier name as a lookup ID. This is needed in additional to the text attribute '
          . 'as the lookup id is unknown during the import itself as the group has not been created yet',
        'type'=>'string',
        'required'=>true,
        'group'=>'Database IDs Required By Form'
      ),
      array(
        'name'=>'plot_width_attr_id',
        'caption'=>'Plot width location attribute ID',
        'description'=>'ID of the location attribute that stores the plot width.',
        'type'=>'string',
        'required'=>true,
        'group'=>'Database IDs Required By Form'
      ),
      array(
        'name'=>'plot_length_attr_id',
        'caption'=>'Plot length location attribute ID',
        'description'=>'ID of the location attribute that stores the plot length.',
        'type'=>'string',
        'required'=>true,
        'group'=>'Database IDs Required By Form'
      ),
      array(
        'name'=>'plot_radius_attr_id',
        'caption'=>'Plot radius location attribute ID',
        'description'=>'ID of the location attribute that stores the plot radius.',
        'type'=>'string',
        'required'=>true,
        'group'=>'Database IDs Required By Form'
      ),
      array(
        'name'=>'plot_shape_attr_id',
        'caption'=>'Plot shape location attribute ID',
        'description'=>'ID of the location attribute that stores the plot shape.',
        'type'=>'string',
        'required'=>true,
        'group'=>'Database IDs Required By Form'
      ),
      array(
        'name'=>'vice_county_attr_id',
        'caption'=>'Vice county sample attribute ID',
        'description'=>'ID of the sample attribute that stores the vice county name.',
        'type'=>'string',
        'required'=>true,
        'group'=>'Database IDs Required By Form'
      ),
      array(
        'name'=>'country_attr_id',
        'caption'=>'Country sample attribute ID',
        'description'=>'ID of the sample attribute that stores the country.',
        'type'=>'string',
        'required'=>true,
        'group'=>'Database IDs Required By Form'
      ),
      array(
        'name'=>'general_occ_attr_ids',
        'caption'=>'General occurrence attribute IDS',
        'description'=>'Comma separated list of general occurrence attributes IDS to use with the importer.'
          . 'These attributes should not have any custom functionality associated with them.',
        'type'=>'string',
        'required'=>true,
        'group'=>'Database IDs Required By Form'
      ),
      array(
        'name'=>'spatial_reference_type_attr_id',
        'caption'=>'Spatial reference type sample attribute ID',
        'description'=>'ID of the sample attribute that holds the spatial reference type (e.g. vague).',
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
        'description'=>'Override the default label next to the upload again radio button on the warnings screen.',
        'type'=>'string',
        'group'=>'Customisable form text'
      ),
      array(
        'name'=>'continue_import_label',
        'caption'=>'Text next to the continue with import button',
        'description'=>'Override the default label next to the continue import radio button on the warnings screen.',
        'type'=>'string',
        'group'=>'Customisable form text'
      ),
      array(
        'name'=>'reupload_link_text',
        'caption'=>'Upload file again text',
        'description'=>'Text here precedes the "reupload" link e.g If you wish the link to say "Click here to reupload" then '
          . 'you just need to type "Click here to" in this box.',
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
        'description'=>'Text shown next to button while the importer reloads the page to show the main progress bar.',
        'type'=>'string',
        'group'=>'Customisable form text'
      ),
      array(
        'name'=>'nP-nPG_message',
        'caption'=>'Custom message for nP-nPG import situation',
        'description'=>'The message shown to the user for rows where new plots and new plot groups are both required.',
        'type'=>'textarea',
        'required'=>true,
        'group'=>'Custom import warnings'
      ),
      array(
        'name'=>'eP_message',
        'caption'=>'Custom message for eP import situation',
        'description'=>'The message shown to the user for rows where an existing plot is required.',
        'type'=>'textarea',
        'required'=>true,
        'group'=>'Custom import warnings'
      ),
      array(
        'name'=>'ePG_message',
        'caption'=>'Custom message for ePG import situation',
        'description'=>'The message shown to the user for rows where a new plot within an existing plot group is required.',
        'type'=>'textarea',
        'required'=>true,
        'group'=>'Custom import warnings'
      ),
      array(
        'name'=>'spref_message',
        'caption'=>'Custom message for spref import situation',
        'description'=>'The message shown to the user for rows where the spatial reference is missing and cannot be '
          . 'generated from the Vice County or Country.',
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
        'name'=>'vice_counties_list',
        'caption'=>'Vice counties list',
        'description'=>'A list of vice counties and associated grid references to use, format is name|grid ref,name|grid ref,name|grid ref,name|grid ref e.g.'
          . 'Shetland|60.38951N 1.21625W,Orkney|59.06504N 2.92039W,Caithness|58.45297N 3.41048W',
        'type'=>'textarea',
        'required'=>true,
        'group'=>'Vice counties list'
      ),
      array(
        'name'=>'countries_list',
        'caption'=>'Countries list',
        'description'=>'A list of countries and associated grid references to use, format is name|grid ref,name|grid ref,name|grid ref,name|grid ref e.g.'
          . 'England|52.62865N 1.46538W,Wales|52.35471N 3.86321W',
        'type'=>'textarea',
        'required'=>true,
        'group'=>'Countries list'
      )
    );
  }

    /**
   * Outputs the import wizard steps.
   *
   * @param array $options Options array with the following possibilities:
   *
   * * **model** - Required. The name of the model data is being imported into.
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
   * @return string
   * @throws \exception
   */
  public static function importer($args,$options) {
    //Include in the options so we don't have to keep passing the $args everywhere that $options have already been passed
    $options=self::set_options_from_args($args, $options);
    //For plant portal we know the import logic we want to use, so these don't need setting in the $args
    //We prevent any commits if there are any errors at all. We also use the sample external key to match
    //the sample to put occurrences into
    $options['importOccurrenceIntoSampleLogic']='sample_ext_key';
    $options['importPreventCommitBehaviour']='prevent';
    if (isset($_POST['total']) && empty($_POST['import_step'])) {
      return self::display_result_if_success_or_failure_when_error_check_stage_was_not_run($options);
    } elseif (!isset($_POST['import_step'])) {
      //Need to set these to null at first step of wizard otherwise it persists even after restarting import process
      $_SESSION['chosen_column_headings']=null;
      $_SESSION['sample:entered_sref_system']=null;
      if (count($_FILES)==1)
        return self::import_settings_form($args,$options);
      else
        return self::upload_form($options);
    } elseif ($_POST['import_step']==1) {
      //If we have the Prevent Commits On Any Error option on, then the first pass of the upload
      //process will always be to check errors and not commit to DB, so indicate this with option.
      //We still need to apply this at the mappings form stage even through it isn't used by this screen,
      //this is because the mappings form can be auto-skipped so needs to pass the flag to the next stage
      if ((isset($_POST['preventCommitsOnError'])&&$_POST['preventCommitsOnError']==true)||
         (isset($_POST['setting']['preventCommitsOnError'])&&$_POST['setting']['preventCommitsOnError']==true)) {
        $options['allowCommitToDB']=false;
      } else {
        $options['allowCommitToDB']=true;
      }
      return self::upload_mappings_form($options);
      //We only ever go to step 2 if we are going to do an error checking stage
      //We call plant_portal_import_logic as seperate steps (2 and 3) even though they
      //are really just the same step, however depending on whether it is step 2 or 3
      //then steps 4 or 5 are run (error checking and upload)
    } elseif (isset($_POST['import_step']) && $_POST['import_step']==2) {
      $options['allowCommitToDB']=false;
      return self::plant_portal_import_logic($args,$options);
      //Stage 3 is always present and is the upload to the database itself
    } elseif (isset($_POST['import_step']) && $_POST['import_step']==3) {
      $options['allowCommitToDB']=true;
      return self::plant_portal_import_logic($args,$options);
    } elseif (isset($_POST['import_step']) && $_POST['import_step']==4) {
      $options['allowCommitToDB']=false;
      $options['model']='occurrence';
      return self::run_plant_portal_upload($options);
    } elseif (isset($_POST['import_step']) && $_POST['import_step']==5) {
      $options['allowCommitToDB']=true;
      $options['model']='occurrence';
      return self::run_plant_portal_upload($options);
    }
    else throw new exception('Invalid importer state');
  }

  /*
   * Function adds some of the args to the options array so we don't have to keep passing both arrays
   * @param array $args Arguments from the edit tab.
   * @param array $args $options passed to the function.
   */
  private static function set_options_from_args($args, $options) {
    $options['plot_group_identifier_name_text_attr_id']=$args['plot_group_identifier_name_text_attr_id'];
    $options['plot_width_attr_id']=$args['plot_width_attr_id'];
    $options['plot_length_attr_id']=$args['plot_length_attr_id'];
    $options['plot_radius_attr_id']=$args['plot_radius_attr_id'];
    $options['plot_shape_attr_id']=$args['plot_shape_attr_id'];
    $options['vice_county_attr_id']=$args['vice_county_attr_id'];
    $options['country_attr_id']=$args['country_attr_id'];
    $options['general_occ_attr_ids']=$args['general_occ_attr_ids'];
    $options['spatial_reference_type_attr_id']=$args['spatial_reference_type_attr_id'];
    return $options;
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
    //Array of arrays. Array of codes to indicate the various error types that can be encountered.
    //The flags indicate the states required to end up in the error situation. The number 2 indicates
    //multiples. For intance 'existingPlot'=>1 indicates multiple existing plots, whilst 'existingPlot'=>2
    //indicates multiple matching existing plots. For definitions of the different error codes see the
    //get_parameters function at the top of this form.
    $args['nonFatalImportTypes'] = array(
            'nP-nPG'=>array('spatialRefPresent'=>1,'existingPlot'=>0,'newPlotExistingPlotGroup'=>0),
            'eP'=>array('spatialRefPresent'=>1,'existingPlot'=>1,'newPlotExistingPlotGroup'=>0),
            'ePG'=>array('spatialRefPresent'=>1,'existingPlot'=>0,'newPlotExistingPlotGroup'=>1));

    $args['fatalImportTypes'] = array(
            //Note for spref error type, we never use the existingPlot, newPlotExistingPlotGroup flags in the code,
            //This means we always trap any row with a missing grid reference into this
            //category regardless of whether existingPlot, newPlotExistingPlotGroup should of been set as 1 or 2
            'spref'=>array('spatialRefPresent'=>0,'existingPlot'=>0,'newPlotExistingPlotGroup'=>0),
            'ePwD'=>array('spatialRefPresent'=>1,'existingPlot'=>2,'newPlotExistingPlotGroup'=>0),
            'ePGwD'=>array('spatialRefPresent'=>1,'existingPlot'=>0,'newPlotExistingPlotGroup'=>2));
    $args['model']='occurrence';
    if (empty($args['override_survey_id'])||empty($args['override_taxon_list_id'])||empty($args['plot_location_type_id'])||
            empty($args['plot_group_identifier_name_text_attr_id'])||empty($args['plot_group_identifier_name_lookup_loc_attr_id'])||
            empty($args['plot_width_attr_id'])||empty($args['plot_length_attr_id'])||
            empty($args['plot_radius_attr_id'])||empty($args['plot_shape_attr_id'])||
            empty($args['vice_county_attr_id'])||empty($args['country_attr_id'])||
            empty($args['general_occ_attr_ids'])||empty($args['spatial_reference_type_attr_id'])||
            empty($args['plot_group_permission_person_attr_id'])||empty($args['plot_group_termlist_id'])||
            empty($args['vice_counties_list'])||empty($args['countries_list']))
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
  private static function upload_form($options) {
    //Default the behaviour types to the old form behaviours if the new options are not set
    if (empty($options['importPreventCommitBehaviour']))
      $options['importPreventCommitBehaviour']='partial_import';
    if (empty($options['importOccurrenceIntoSampleLogic']))
      $options['importOccurrenceIntoSampleLogic']='consecutive_rows';
    $reload = self::get_reload_link_parts();
    $reloadpath = $reload['path'] . '?' . self::array_to_query_string($reload['params']);
    $r = '<form action="'.$reloadpath.'" method="post" enctype="multipart/form-data">';
    //Import has two modes, only commit if no errors, or commit valid rows.
    //This can be user controlled, or provided by the administrator as an argument
    if ($options['importPreventCommitBehaviour']==='prevent')
      $r .= '<input type="checkbox" style="display:none;" name="preventCommitsOnError" checked>';
    if ($options['importPreventCommitBehaviour']==='partial_import')
      $r .= '<input type="checkbox" style="display:none;" name="preventCommitsOnError" >';
    //Only show the prevent commits on error import option on screen if the administrator has set this
    //option to be user defined. This option can be used for any model (e.g. locations) so we don't
    //need to check the model.
    if ($options['importPreventCommitBehaviour']==='user_defined') {
      $r .= data_entry_helper::checkbox(array(
        'label' => lang::get('Prevent commits on error'),
        'fieldname' => 'preventCommitsOnError',
        'helpText'=>'Select this checkbox to prevent the importing of any rows '
          . 'if there are any errors at all. Leave this checkbox switched off to import valid rows.'
      ));
    }
    if ($options['importOccurrenceIntoSampleLogic']==='sample_ext_key')
      $r .= '<input type="checkbox" style="display:none;" name="importOccurrenceIntoSampleUsingExternalKey" checked>';
    //If we are not using the import using the new sample external key option then we still need to place this hidden onto the page,
    //but leave it unchecked
    if ($options['importOccurrenceIntoSampleLogic']==='consecutive_rows')
      $r .= '<input type="checkbox" style="display:none;" name="importOccurrenceIntoSampleUsingExternalKey" >';
    //Only show sample external key import option on screen for occurrence or sample imports and if the administrator has set this
    //option to be user defined.
    if ($options['importOccurrenceIntoSampleLogic']==='user_defined' && ($options['model']==='occurrence'||$options['model']==='sample')) {
      $r .= data_entry_helper::checkbox(array(
        'label' => lang::get('Use sample external key to match occurrences into sample'),
        'fieldname' => 'importOccurrenceIntoSampleUsingExternalKey',
        'helpText'=>'Select this checkbox to import occurrences onto samples using the sample external key to match between rows. '
          . 'Leave this checkbox off to import similar consecutive rows in the import file onto the same sample.'
      ));
    }
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
    $_SESSION['uploaded_file'] = import_helper::get_uploaded_file($options);
    // by this time, we should always have an existing file
    if (empty($_SESSION['uploaded_file'])) throw new Exception('File to upload could not be found');
    $request = parent::$base_url."index.php/services/plant_portal_import/get_plant_portal_import_settings/".$options['model'];
    $request .= '?'.self::array_to_query_string($options['auth']['read']);
    $response = self::http_post($request, array());
    if (!empty($response['output'])) {
      // get the path back to the same page
      $reload = self::get_reload_link_parts();
      $reloadpath = $reload['path'] . '?' . self::array_to_query_string($reload['params']);
      $r = '<div class="page-notice ui-state-highlight ui-corner-all">'.lang::get('import_settings_instructions')."</div>\n".
           "<div class=\"page-notice ui-state-highlight ui-corner-all\"><em>Important: If you have missing spatial references in your data, please leave this option off.</em></div>\n".
          "<form method=\"post\" id=\"entry_form\" action=\"$reloadpath\" class=\"iform\">\n".
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
        unset($extraHiddens['password']);
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
    self::add_resource('jquery_ui');
    $filename=basename($_SESSION['uploaded_file']);
    $mappingsAndSettings=self::get_mappings_and_settings($options);
    $settings=$mappingsAndSettings['settings'];
    //Request the columsn we are going to map
    $request = parent::$base_url."index.php/services/plant_portal_import/get_plant_portal_import_fields/".$options['model'];
    $request .= '?'.self::array_to_query_string($options['auth']['read']);
    $fields = self::upload_mappings_form_get_smp_occ_fields($options,$settings,$request);
    //For Plant Portal we need to show some of the location fields on the mappings form even though we might be using another model
    $options['model']='location';
    $request = parent::$base_url."index.php/services/plant_portal_import/get_plant_portal_import_fields/".$options['model'];
    $request .= '?'.self::array_to_query_string($options['auth']['read']);
    $fields=self::upload_mappings_form_get_location_fields($options,$settings,$fields,$request);

    if (!is_array($fields))
      return "curl request to $request failed. Response ".print_r($response, true);
    $request = str_replace('get_import_fields', 'get_required_fields', $request);
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
    $r .= '<div id="required-instructions" class="import-mappings-instructions"><h2>'.lang::get('Tasks').'</h2><span>'.
      lang::get('The following database attributes must be matched to a column in your import file before you can continue').':</span><ul></ul><br/></div>';
    $r .= '<div id="duplicate-instructions" class="import-mappings-instructions"><span id="duplicate-instruct">'.
      lang::get('There are currently two or more drop-downs allocated to the same value.').'</span><ul></ul><br/></div></div>';
    //We need to rerun this even though we run this earlier in this function
    //The earlier call wouldn't have retrieved any mappings as get_column_options wouldn't have been run yet, however
    //we still need to get the settings at that earlier point
    $mappingsAndSettings=self::get_mappings_and_settings($options);
    self::send_mappings_and_settings_to_warehouse($filename,$options,$mappingsAndSettings);
    //If skip mapping is on, then we don't actually need to show this page and can skip straight to the
    //upload or error checking stage (which will be determined by run_upload using the allowCommitToDB option)
    if (!empty($options['skipMappingIfPossible']) && $options['skipMappingIfPossible']==true && count(self::$automaticMappings) === $colCount) {
      //Need to pass true to stop the mappings and settings being sent to the warehouse during the run_upload function
      //as we have already done that here
      return self::run_plant_portal_upload($options,true);
    }
    //Preserve the post from the website/survey selection screen
    if (isset($options['allowCommitToDB'])&&$options['allowCommitToDB']===false) {
      //If we are error checking before upload we do an extra step, which is import step 2
      $r .= self::preserve_fields($options,$filename,2);
    } else {
      $r .= self::preserve_fields($options,$filename,3);
    }
    $r .= '<input type="submit" name="submit" id="submit" value="'.lang::get('Upload').'" class="ui-corner-all ui-state-default button" />';
    $r .= '</form>';
    self::$javascript .= self::upload_mappings_form_javascript_detect_duplicate_fields_function();
    self::$javascript .= self::upload_mappings_form_javascript_update_required_fields_function();
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

  private static function upload_mappings_form_get_smp_occ_fields($options,$settings,$request) {
    // include survey and website information in the request if available, as this limits the availability of custom attributes
    if (!empty($settings['website_id']))
      $request .= '&website_id='.trim($settings['website_id']);
    if (!empty($settings['survey_id']))
      $request .= '&survey_id='.trim($settings['survey_id']);
    if (!empty($settings['useAssociations']) && $settings['useAssociations'])
    	$request .= '&use_associations=true';
    if($options['model'] == 'sample' && isset($settings['sample:sample_method_id']) && trim($settings['sample:sample_method_id']) != '')
    	$request .= '&sample_method_id='.trim($settings['sample:sample_method_id']);
    else if($options['model'] == 'location' && isset($settings['location:location_type_id']) && trim($settings['location:location_type_id']) != '')
    	$request .= '&location_type_id='.trim($settings['location:location_type_id']);
    $response = self::http_post($request, array());
    $fields = json_decode($response['output'], true);
    //Limit fields that can be selected from to ones we are interested in for this project
    //Firstly explode a list of all occurrence attributes
    $explodedOccAttrIds=explode(',',$options['general_occ_attr_ids']);
    //Cycle through all the fields
    foreach ($fields as $key=>$data) {
      //Assume we are going to use the field unless we field otherwise
      $canUnset=false;
      //Fields with "fk_" in name are foreign key lookups
      if ($key!=='occurrence:fk_taxa_taxon_list'&&$key!=='occurrence:fk_taxa_taxon_list'&&$key!=='occurrence:fk_website'&&
              $key!=='occurrence:id'&&$key!=='sample:comment'&&$key!=='sample:date'&&$key!=='sample:entered_sref'
              &&$key!=='sample:entered_sref_system'&&$key!=='sample:external_key'
              &&$key!=='sample:fk_location'&&$key!=='website_id'&&$key!=='survey_id'
              //Note that these need to be sample attributes as they need to be passed to the
              //warehouse as part of the sample so that the spatial reference can be calculated
              &&$key!=='smpAttr:'.$options['vice_county_attr_id']
              &&$key!=='smpAttr:'.$options['country_attr_id']
              &&$key!=='smpAttr:fk_'.$options['spatial_reference_type_attr_id']
              ) {
          //If the field isn't in the list of what we want to keep we are probably not going to use it
          $canUnset=true;
      } else {
        $canUnset=false;
      }
      //Cycle through the occurrence attributes we are going to use
      //and if the field is in the list, we mark it for keeping
      foreach ($explodedOccAttrIds as $occAttrId) {
        if ($key==='occAttr:'.$occAttrId)
          $canUnset=false;
        if ($key==='occAttr:fk_'.$occAttrId)
          $canUnset=false;
      }
      if ($canUnset===true)
        unset($fields[$key]);
    }
    return $fields;
  }

  private static function upload_mappings_form_get_location_fields($options,$settings,$fields,$request) {
    // include survey and website information in the request if available, as this limits the availability of custom attributes
    if (!empty($settings['website_id']))
      $request .= '&website_id='.trim($settings['website_id']);
    if (!empty($settings['survey_id']))
      $request .= '&survey_id='.trim($settings['survey_id']);
    if(isset($settings['sample:sample_method_id']) && trim($settings['sample:sample_method_id']) != '')
    	$request .= '&sample_method_id='.trim($settings['sample:sample_method_id']);
    $response = self::http_post($request, array());
    $locationfields = json_decode($response['output'], true);
    //We only want to include some of the location attributes
    $locationFieldsToUse['locAttr:'.$options['plot_group_identifier_name_text_attr_id']] = $locationfields['locAttr:'.$options['plot_group_identifier_name_text_attr_id']];
    $locationFieldsToUse['locAttr:'.$options['plot_width_attr_id']] = $locationfields['locAttr:'.$options['plot_width_attr_id']];
    $locationFieldsToUse['locAttr:'.$options['plot_length_attr_id']] = $locationfields['locAttr:'.$options['plot_length_attr_id']];
    $locationFieldsToUse['locAttr:'.$options['plot_radius_attr_id']] = $locationfields['locAttr:'.$options['plot_radius_attr_id']];
    //fk for foreigh key lookup list
    $locationFieldsToUse['locAttr:fk_'.$options['plot_shape_attr_id']] = $locationfields['locAttr:fk_'.$options['plot_shape_attr_id']];
    $fields = array_merge($fields,$locationFieldsToUse);
    return $fields;
  }

  /*
   * Javascript function that were in the upload_mappings_form function but I have moved out of that function as it was too long
   */
  private static function upload_mappings_form_javascript_detect_duplicate_fields_function() {
    return "function detect_duplicate_fields() {
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
  }

    /*
   * Javascript function that were in the upload_mappings_form function in the normal importer but I have moved out of thatfunction as it was too long
   */
  private static function upload_mappings_form_javascript_update_required_fields_function() {
    return "function update_required_fields() {
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
  }

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
    //AVB To Do - Unknown if remember mappings works with the plant portal importer - test as low priority, comment out until test
    /*if (function_exists('hostsite_get_user_field')) {
      $json = hostsite_get_user_field('import_field_mappings');
      if ($json===false) {
        if (!hostsite_set_user_field('import_field_mappings', '[]'))
          self::$rememberingMappings=false;
      } else {
        $json=trim($json);
        $autoFieldMappings=json_decode(trim($json), true);
      }
    } else */
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
   * Displays the upload result page in the following situations
   * -If the import was a success
   * -If there was a failure but the error check stage was never used
   * @param array $options Array of options passed to the import control.
   */
  private static function display_result_if_success_or_failure_when_error_check_stage_was_not_run($options) {
    $request = parent::$base_url."index.php/services/plant_portal_import/get_upload_result?uploaded_csv=".$_GET['uploaded_csv'];
    $request .= '&'.self::array_to_query_string($options['auth']['read']);
    $response = self::http_post($request, array());
    if (isset($response['output'])) {
      $output = json_decode($response['output'], true);
      if (!is_array($output) || !isset($output['problems']))
        return lang::get('An error occurred during the upload.') . '<br/>' . print_r($response, true);
      if ($output['problems']>0) {
        $downloadInstructions=lang::get('partial_commits_download_error_file_instructions');
        $r = lang::get('{1} problems were detected during the import.', $output['problems']) . ' ' .
          $downloadInstructions .
          " <a href=\"$output[file]\">" . lang::get('Download the records that did not import.') . '</a>';
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
  * and gives the user the chance to save their settings for use the next time they do an import.
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
        $strippedScreenCaption = str_replace(" (from controlled termlist)","",self::translate_field($field, $caption));
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
      //need a version of the caption without "from controlled termlist" as we ignore that for matching.
      $strippedScreenCaption = str_replace(" (from controlled termlist)","",$translatedCaption);
      $fieldname=str_replace(array('fk_','_id'), array('',''), $fieldname);
      unset($option);
      // Skip the metadata fields
      if (!in_array($fieldname, $skipped)) {
        $selected = false;
        //get user's saved settings, last parameter is 2 as this forces the system to explode into a maximum of two segments.
        //This means only the first occurrence for the needle is exploded which is desirable in the situation as the field caption
        //contains colons in some situations.
        $colKey = preg_replace('/[^A-Za-z0-9]/', ' ', $column);
        if (!empty($autoFieldMappings[$colKey]) && $autoFieldMappings[$colKey]!=='<Not imported>') {
          $savedData = explode(':',$autoFieldMappings[$colKey],2);
          $savedSectionHeading = $savedData[0];
          $savedMainCaption = $savedData[1];
        } else {
          $savedSectionHeading = '';
          $savedMainCaption = '';
        }
        //Detect if the user has saved a column setting that is not 'not imported' then call the method that handles the auto-match rules.
        if (strcasecmp($prefix, $savedSectionHeading) === 0 &&
            strcasecmp($field, $savedSectionHeading . ':' . $savedMainCaption) === 0) {
          $selected=true;
          $itWasSaved[$column] = 1;
          //even though we have already detected the user has a saved setting, we need to call the auto-detect rules as if it gives the same result then the system acts as if it wasn't saved.
          $saveDetectRulesResult = self::auto_detection_rules($column, $defaultCaption, $strippedScreenCaption, $prefix, $labelList, $itWasSaved[$column], true);
          $itWasSaved[$column] = $saveDetectRulesResult['itWasSaved'];
        } else {
          //only use the auto field selection rules to select the drop-down if there isn't a saved option
          if (!isset($autoFieldMappings[$colKey])) {
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
        //Change captions based on requirements of Plant Portal project
        if ($defaultCaption==='Location (from controlled termlist)')
          $defaultCaption='Unique plot ID';
        if ($defaultCaption==='External key')
          $defaultCaption='Unique sample ID';
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
  * @param string $strippedScreenCaption A version of an item in the column selection drop-down that has 'from controlled termlist'stripped
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
      "occurrence_2:taxa taxon list (from controlled termlist)"=>array("/(2nd|second)(species(latin)?|taxon(latin)?|latin)(name)?/"),
      "occurrence:taxa taxon list (from controlled termlist)"=>array("/(species(latin)?|taxon(latin)?|latin)(name)?/"),
      "sample:location name"=>array("/(site|location)(name)?/"),
      "smpAttr:eunis habitat (from controlled termlist)" => array("/(habitat|eunishabitat)/")
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
    $optionID = str_replace(" ", "", $column).'Normal';
    $r = "<option value=\"&lt;Not imported&gt;\">&lt;".lang::get('Not imported').'&gt;</option>'.$r.'</optgroup>';
    if (self::$rememberingMappings) {
      $inputName = preg_replace('/[^A-Za-z0-9]/', '_', $column) . '.Remember';
      $checked = ($itWasSaved[$column] == 1 || $rememberAll) ? ' checked="checked"' : '';
      $r .= <<<TD
<td class="centre">
<input type="checkbox" name="$inputName" class="rememberField" id="$inputName" value="1"$checked
  onclick="if (!this.checked) { $('#RememberAll').removeAttr('checked'); }"
  title="If checked, your selection for this particular column will be saved and automatically selected during future
    imports. Any alterations you make to this default selection in the future will also be remembered until you deselect
    the checkbox.">
</td>
TD;
    }
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
  * Used by the get_column_options method to add "from controlled termlist" to the appropriate captions
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
        $captionSuffix .= ' ('.lang::get('from controlled termlist').')';
      }
      $fieldname=str_replace(array('fk_','_id'), array('',''), $fieldname);
      if ($prefix==$model || $prefix=="metaFields" || $prefix==substr($fieldname,0,strlen($prefix))) {
        $caption = self::processLabel($fieldname).$captionSuffix;
      } else {
        $caption = self::processLabel("$fieldname").$captionSuffix;
      }
    } else {
        $caption .= (substr($fieldname,0,3)=='fk_' ? ' ('.lang::get('from controlled termlist').')' : '');
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
      if (count($_FILES)!=1)
        throw new Exception('There must be a single file uploaded to import');
      // reset gets the first array element
      $file = reset($_FILES);
      // Get the original file's extension
      $parts = explode(".",$file['name']);
      $fext = array_pop($parts);
      if ($fext!='csv')
        throw new Exception('Uploaded file must be a csv file');
      // Generate a file id to store the upload as
      $destination = time() . rand(0,1000) . "." . $fext;
      $interimPath = self::getInterimImageFolder('fullpath');
      if (move_uploaded_file($file['tmp_name'], "$interimPath$destination")) {
        return "$interimPath$destination";
      }
    }
    elseif (isset($options['existing_file']))
      return $options['existing_file'];
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
  protected static function send_file_to_warehouse_plant_portal_importer($path, $persist_auth=false, $readAuth = null, $service='data/handle_media',$removeLocalCopy=true) {
    if ($readAuth == NULL) {
      $readAuth = $_POST;
    }
    $interimPath = self::getInterimImageFolder('fullpath');
    if (!file_exists($interimPath.$path))
      return "The file $interimPath$path does not exist and cannot be uploaded to the Warehouse.";
    $serviceUrl = self ::$base_url . "index.php/services/$service";
    // This is used by the file box control which renames uploaded files using a guid system, so disable renaming on the server.
    $postargs = array('name_is_guid' => 'true');
    // attach authentication details
    if (array_key_exists('auth_token', $readAuth))
      $postargs['auth_token'] = $readAuth['auth_token'];
    if (array_key_exists('nonce', $readAuth))
      $postargs['nonce'] = $readAuth['nonce'];
    if ($persist_auth)
      $postargs['persist_auth'] = 'true';
    $file_to_upload = array('media_upload'=>'@'.realpath($interimPath.$path));
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
    if ($removeLocalCopy==true) {
      unlink(realpath($interimPath.$path));
    }
    return $r;
  }

  /**
   * Display the page which outputs the upload progress bar. Adds JavaScript to the page which performs the chunked upload.
   * @param array $options Array of options passed to the import control.
   * @param array $mappings List of column title to field mappings
   */
  private static function run_plant_portal_upload($options, $calledFromSkippedMappingsPage=false) {
    $r='';
    self::add_resource('jquery_ui');
    if (!file_exists($_SESSION['uploaded_file']))
      return lang::get('upload_not_available');
    $filename=basename($_SESSION['uploaded_file']);
    $reload = self::get_reload_link_parts();
    $reload['params']['uploaded_csv']=$filename;
    $reloadpath = $reload['path'] . '?' . self::array_to_query_string($reload['params']);
    $mappingsAndSettings=self::get_mappings_and_settings($options);
    if ($calledFromSkippedMappingsPage===false) {
      self::send_mappings_and_settings_to_warehouse($filename,$options,$mappingsAndSettings);
    }
    if (isset($options['allowCommitToDB'])&&$options['allowCommitToDB']===false) {
      //If we hit this line it means we are doing the error checking step and the next step
      //is step 5 which is the actual upload. Preserve the fields from previous steps in the post
      $r .= self::preserve_fields($options,$filename,5);
    } else {
      //This line is hit if we are doing the actual upload now (rather than error check).
      //The next step is the results step which does not have an import_step number
      $r .= self::preserve_fields($options,$filename,null);
    }
    //If there is an upload total as this point, it means an error check stage must of just been run, so we
    //need to check for errors in the response
    if (isset($_POST['total'])) {
      //If we have reached this line, it means the previous step was the error check stage and we are
      //about to attempt to upload, however we need to skip straight to results if we detected any errors
      $output=self::collect_errors($options,$filename);
      if (!is_array($output) || (isset($output['problems'])&&$output['problems']>0)) {
        return self::display_result_as_error_check_stage_failed($options,$output);
      }
      //Need to resend metadata as we need to call warehouse again for upload (rather than error check)
      $mappingsAndSettings=self::get_mappings_and_settings($options);
      self::send_mappings_and_settings_to_warehouse($filename,$options,$mappingsAndSettings);
    }
    //AVB To Do - If a warning is returned in $r it is currently unhandled
    $transferFileDataToWarehouseSuccess = self::send_file_to_warehouse_plant_portal_importer($filename, false, $options['auth']['write_tokens'], 'plant_portal_import/upload_csv',$options['allowCommitToDB']);
    if ($transferFileDataToWarehouseSuccess===true) {
      //Progress message depends if we are uploading or simply checking for errors
      if ($options['allowCommitToDB']==true) {
        $progressMessage = ' records uploaded.';
      } else {
        $progressMessage = ' records checked.';
      }
      // initiate local javascript to do the upload with a progress feedback
      $r .= '
  <div id="progress" class="ui-widget ui-widget-content ui-corner-all">
  <progress id="progress-bar" class="progress" value="0" max="100">0 %</progress>';
  if (isset($options['allowCommitToDB'])&&$options['allowCommitToDB']==true) {
    $actionMessage='Preparing to upload.';
  } else {
    $actionMessage='Checking file for errors..';
  }
  $r .= "<div id='progress-text'>$actionMessage.</div>
  </div>
  ";
      if (!empty(parent::$warehouse_proxy))
        $warehouseUrl = parent::$warehouse_proxy;
      else
        $warehouseUrl = parent::$base_url;
      self::$onload_javascript .= "
    /**
    * Upload a single chunk of a file, by doing an AJAX get. If there is more, then on receiving the response upload the
    * next chunk.
    */
    uploadChunk = function() {
      var limit=50;
      $.ajax({
        url: '".$warehouseUrl."index.php/services/plant_portal_import/upload?offset='+total+'&limit='+limit+'"
              . "&filepos='+filepos+'&uploaded_csv=$filename"
              . "&model=".$options['model']."&allow_commit_to_db=".$options['allowCommitToDB']."',
        dataType: 'jsonp',
        success: function(response) {
          var allowCommitToDB = '".$options['allowCommitToDB']."';
          total = total + response.uploaded;
          filepos = response.filepos;
          jQuery('#progress-text').html(total + '$progressMessage');
          jQuery('#progress-bar').val(response.progress);
          jQuery('#progress-bar').text(response.progress + ' %');
          if (response.uploaded>=limit) {
            uploadChunk();
          } else {
            if (allowCommitToDB==1) {
              jQuery('#progress-text').html('Upload complete.');
              //We only need total at end of wizard, so we can just refresh page with total as param to use in the post of next step
            } else {
              jQuery('#progress-text').html('Checks complete.');
            }
            $('#fields_to_retain_form').append('<input type=\"hidden\" name=\"total\" id=\"total\" value=\"'+total+'\"/>');
            $('#fields_to_retain_form').submit();
          }
        }
      });
    };

    var total=0, filepos=0;
    jQuery('#progress-bar').val(0);
    jQuery('#progress-bar').text('0 %');
    uploadChunk();
    ";
    }
    return $r;
  }

  /* Plant Portal has quite a lot of extra logic that the "normal" importer doesn't have, and this is handled here. For instance, it creates groups and trys to attach occurrences to existing plots.
   * @param array $args Array of arguments passed from the Edit Tab.
   * @param array $options Array of options passed to the import control.
   */
  private static function plant_portal_import_logic($args,$options) {
    $mappingsAndSettings=self::get_mappings_and_settings($options);
    $r = '';
    //If we are using the sample external key as the indicator of which samples
    //the occurrences go into, then we need to check the sample data is consistant between the
    //rows which share the same external key, if not, warn the user.
    //If not using that mode we can just continue without the warning screen.
    $reload = self::get_reload_link_parts();
    unset($reload['params']['total']);
    unset($reload['params']['uploaded_csv']);
    $reloadpath = $reload['path'] . '?' . self::array_to_query_string($reload['params']);
    $r =  "<form method=\"post\" id=\"entry_form\" action=\"$reloadpath\" class=\"iform\">\n".
          "<div style='width: 700px; overflow: auto;'>".
          "<fieldset><legend>".lang::get('Import allocations')."</legend><br>\n";
    $filename=basename($_SESSION['uploaded_file']);
    $auth = self::get_read_write_auth($args['website_id'], $args['password']);
    ini_set('auto_detect_line_endings',TRUE);
    $fileArray = file($_SESSION['uploaded_file']);
    //Keep a copy of the header as we will be removing it in a minute
    $originalHeader=$fileArray[0];
    $headerLineItems = explode(',',$fileArray[0]);
    //Keep a count of the number of headers there are the file, before we
    //process the file which could include adding further headers such as
    //spatial reference system
    $originalHeaderLineCount=count($headerLineItems);
    //If the user has selected a spatial reference system from the drop-down
    //at the start, we need to create an extra column on the grid to put the spatial
    //reference system into.
    $last_key = key(array_slice($headerLineItems, -1, 1, TRUE ));
    if (!empty($_SESSION['sample:entered_sref_system'])) {
      $headerLineItems[$last_key+1]='Spatial reference system (auto-generated)';
      $last_key=$last_key+1;
    }
    //Always needs an extra column for the spatial reference type which is always set automatically
    $headerLineItems[$last_key+1]='Spatial reference type (auto-generated)';
    //Remove the header row from the file
    unset($fileArray[0]);

    //Cycle through each row excluding the header row and convert into an array
    foreach ($fileArray as $fileLine) {
      //Trim first otherwise we will attempt to process rows which might be just whitespace
      if (!empty($fileLine)) {
        $explodedLine = explode(',',$fileLine);
        //Remove white space start and end of items
        foreach ($explodedLine as $lineItemIdx => $lineItem) {
          $explodedLine[$lineItemIdx]=$lineItem;
        }
        //Sometimes the CSV file we are processing might have rows which don't have anything
        //at the end of the line at all instead of commas indicating empty cells like this ,,,
        //To avoid errors we can add these in. If we find the line has fewer cells than the number
        //off headers in the file.
        if (count($explodedLine)<$originalHeaderLineCount) {
          //Then find how many cells are missing
          $rowDescrepancyCount=$originalHeaderLineCount-count($explodedLine);
          //Add the required number of empty cells
          for ($i=0;$i<$rowDescrepancyCount;$i++) {
            $explodedLine[]=null;
          }
        }
        $fileRowsAsArray[]=$explodedLine;
      }
    }
    //If we are going to compare the headers with the $_POST we need to remove the spaces and underscores as they are inconsistent between the two
    $headerLineItemsWithoutSpacesOrUnderscores=array();
    foreach ($headerLineItems as $idx=>$headerLineItem) {
      $headerLineItemsWithoutSpacesOrUnderscores[$idx]=str_replace(' ','',$headerLineItem);
      $headerLineItemsWithoutSpacesOrUnderscores[$idx] = str_replace('_','',$headerLineItemsWithoutSpacesOrUnderscores[$idx]);
      $headerLineItemsWithoutSpacesOrUnderscores[$idx] = trim($headerLineItemsWithoutSpacesOrUnderscores[$idx]);
    }
    //Do the same with the post
    $postWithoutSpacesUnderscoresInKeys=array();
    foreach ($_POST as $amendedTableHeaderWith_ => $fieldData) {
      $newKey = str_replace(' ','',$amendedTableHeaderWith_);
      $newKey = str_replace('_','',$newKey);
      $postWithoutSpacesUnderscoresInKeys[$newKey]=$fieldData;
    }
    //Remove any columns the user hasn't mapped on the column mappings page
    foreach ($headerLineItemsWithoutSpacesOrUnderscores as $idx => $headerToCheck) {
      //Unmapped columns include html to say they haven't been mapped, so
      //we need to remove this html so we can check they are empty
      if (isset($postWithoutSpacesUnderscoresInKeys[$headerToCheck]))
        $postWithoutSpacesUnderscoresInKeys[$headerToCheck]=strip_tags($postWithoutSpacesUnderscoresInKeys[$headerToCheck]);
      //If a header isn't in use then we can safely remove it from the columns we are working with.
      //Remove from headers and also the column from the file data array
      if ((!array_key_exists($headerToCheck,$postWithoutSpacesUnderscoresInKeys)||
              $postWithoutSpacesUnderscoresInKeys[$headerToCheck]==''||
              !isset($postWithoutSpacesUnderscoresInKeys[$headerToCheck]))
              && $headerToCheck!='Spatialreferencesystem(auto-generated)'
              && $headerToCheck!='Spatialreferencetype(auto-generated)') {
        unset($headerLineItems[$idx]);
        unset($headerLineItemsWithoutSpacesOrUnderscores[$idx]);
        foreach ($fileRowsAsArray AS &$fileLineArray) {
          unset($fileLineArray[$idx]);
        }
      }
    }
    if (empty($_SESSION['chosen_column_headings']))
      $_SESSION['chosen_column_headings']=self::store_column_header_names_for_existing_match_checks($args,$postWithoutSpacesUnderscoresInKeys);
    $chosenColumnHeadings=$_SESSION['chosen_column_headings'];
    //Get the position of each of the columns required for existing match checks. For instance we can know that the Plot Group is in column 3
    $columnHeadingIndexPositions=self::get_column_heading_index_positions($headerLineItemsWithoutSpacesOrUnderscores,$chosenColumnHeadings);
    $fileRowsAsArray=self::auto_generate_grid_references($fileRowsAsArray,$columnHeadingIndexPositions,$args['vice_counties_list'],$args['countries_list']);
    if ($options['model']==='occurrence'||$options['model']==='sample') {
      if (!empty($mappingsAndSettings['settings']['importOccurrenceIntoSampleUsingExternalKey'])&&$mappingsAndSettings['settings']['importOccurrenceIntoSampleUsingExternalKey']==true) {
        $checkArrays = self::sample_external_key_issue_checks($options,$fileRowsAsArray);
        $inconsistencyFailureRows = $checkArrays['inconsistencyFailureRows'];
        $clusteringFailureRows = $checkArrays['clusteringFailureRows'];
        if (!empty($inconsistencyFailureRows)||!empty($clusteringFailureRows)) {
          $r.= self::display_sample_external_key_data_mismatches($inconsistencyFailureRows,$clusteringFailureRows);
          if (!empty($r))
            return $r;
        }
      }
    }
    //As we have applied processing to the file (such as auto spatial reference generation), we need to store the changes to send to the warehouse. Note the doesn't affect the file on the disc
    $implodedFileArray[0]=$originalHeader;
    foreach ($fileRowsAsArray as $fileRowsAsArrayLine) {
      $implodedLine = implode(',',$fileRowsAsArrayLine);
      $implodedFileArray[]=$implodedLine;
    }
    //As we have auto generated spatial references, we need to put them back for the warehouse to use (note this does not affect the original file)
    //AVB To DO: Auto generation is currently only supported when a general spatial reference system isn't supplied.
    if (empty($_SESSION['sample:entered_sref_system'])) {
      file_put_contents ($_SESSION['uploaded_file'],$implodedFileArray);
    }
    //Collect the plots and groups the user has rights to so we can use existing ones where needed
    $plotsAndPlotGroupsUserHasRightsTo = self::get_plots_and_groups_user_has_rights_to($auth,$args);
    $fileArrayForImportRowsToProcessForImport = self::check_existing_user_data_against_import_data($args,$fileRowsAsArray,$plotsAndPlotGroupsUserHasRightsTo,$columnHeadingIndexPositions);
    //The screen for displaying import details has two sections, one for warnings, and the other for problems that are is mandatory to correct before allowing continue
    $r .= self::display_import_warnings($args,$fileArrayForImportRowsToProcessForImport,$headerLineItems,$args['nonFatalImportTypes']);
    $rFatal =self::display_import_warnings($args,$fileArrayForImportRowsToProcessForImport,$headerLineItems,$args['fatalImportTypes']);
    if (empty($rFatal))
      $fatalErrorsFound=false;
    else
      $fatalErrorsFound=true;
    if (isset($options['allowCommitToDB'])&&$options['allowCommitToDB']===false) {
      $r .= self::preserve_fields($options,$filename,4);
    } else {
      $r .= self::preserve_fields($options,$filename,5);
    }
    //If fatal warning are found, then we cannot continue the import. Warn the user in red.
    if ($fatalErrorsFound===true) {
      $r .= '<div style="color:red">The import cannot currently continue because of the following problems:</div>'.$rFatal;
      $r .=self::get_upload_reupload_buttons_with_fatal_errors($args,$reloadpath);
    } else {
      $r .= $rFatal;
      //Display upload buttons and also code for clicking the actual upload button
      $r .=self::get_upload_reupload_buttons_when_no_fatal_errors($args,$reloadpath);
      $r .=self::get_upload_click_function($args,$fileArrayForImportRowsToProcessForImport,$columnHeadingIndexPositions,$plotsAndPlotGroupsUserHasRightsTo);
    }
    return $r;
  }

  /*
   * Buttons at the end of the wizard to allow the user to continue with their upload, or alternatively, make corrections to the file before importing
   */
  private static function get_upload_reupload_buttons_with_fatal_errors($args,$reloadpath) {
    //User can customise text on screen, provide defaults if customised text not provided
    if (empty($args['import_fatal_errors_screen_instructions'])) {
      $args['import_fatal_errors_screen_instructions']='Please check the above sections as some of the import data is in a state which means the import cannot currently continue. '
              . 'You will need to correct the data in the original upload file before clicking the reupload button which'
            . ' in your import files needs correcting, you can use the information provided above as a guide before <em>editing the original file and uploading the file again.</em>';
    }
    if (empty($args['reupload_label']))
      $args['reupload_label']='Upload again with corrections made';
    if (empty($args['reupload_link_text']))
      $args['reupload_link_text']='Click on the link to';
    $r = '';
    //Need import step one to return to mappings page
    $r .= '<input type="hidden" id="import_step" name="import_step" value="1" />';
    $r .= '<div>'.$args['import_fatal_errors_screen_instructions'].'<br><br></div>';
    $r .= "<p id='re-upload-import'>".$args['reupload_link_text']."<a href=\"$reloadpath\">".lang::get(' reupload')."</a></p>";
    //$r .= '<input type="submit" name="submit" id="re-upload-import" value="'.$args['reupload_link_text'].'" class="ui-corner-all ui-state-default button" />';
    $r .= '<div id="import-loading-msg" style="display:none"/> '.$args['loading_import_wait_text'].'</div>';
    $r .= '</fieldset></div></form>';
    return $r;
  }

  /*
   * Buttons at the end of the wizard to allow the user to continue with their upload, or alternatively, make corrections to the file before importing
   */
  private static function get_upload_reupload_buttons_when_no_fatal_errors($args,$reloadpath) {
    //User can customise text on screen, provide defaults if customised text not provided
    if (empty($args['import_warnings_screen_instructions'])) {
      $args['import_warnings_screen_instructions']='Please check the above sections. It gives details on what will happen to your records once they are imported. If you believe any of the data'
            . ' in your import files needs correcting, you can use the information provided above as a guide before <em>editing the original file and uploading the file again.</em>';
    }
    if (empty($args['reupload_label']))
      $args['reupload_label']='Upload again with corrections made';
    if (empty($args['continue_import_label']))
      $args['continue_import_label']='Continue with existing';
    if (empty($args['reupload_link_text']))
      $args['reupload_link_text']='Upload file';
    if (empty($args['continue_button']))
      $args['continue_button']='Continue with import';
    if (empty($args['loading_import_wait_text']))
      $args['loading_import_wait_text']='Please wait while we import your data...';
    $r = '';
    $r .= '<div>'.$args['import_warnings_screen_instructions'].'<br><br></div>';
    $r.='<form action="">
      <input type="radio" id="reupload" name="reupload-choice" value="reupload"> '.$args['reupload_label'].'<br>
      <input type="radio" id="continue" name="reupload-choice" value="continue"> '.$args['continue_import_label'].'<br>
    </form>';
    //Show appropriate button depending on what user wants to do. Also make sure the import step is correct for the option the user chooses
    data_entry_helper::$javascript .= "
      $('#reupload').on('click', function() {
        $('#import_step').val(1);
        $('#re-upload-import').show();
        $('#create-import-data').hide();
      });
      $('#continue').on('click', function() {
        $('#re-upload-import').hide();
        $('#create-import-data').show();
      });\n";
    $r .= "<p id='re-upload-import'>".$args['reupload_link_text']."<a href=\"$reloadpath\">".lang::get(' reupload')."</a></p>";
    //If we use a normal button here (Rather than submit). We can use a click function to do some processing logic before forcing the submit using the same function.
    $r .= '<input type="button" name="submit" id="create-import-data" value="'.$args['continue_button'].'" class="ui-corner-all ui-state-default button" style="display:none"/>';
    $r .= '<input type="submit" name="submit" id="submit-import" style="display:none"/>';
    $r .= '<div id="import-loading-msg" style="display:none"/> '.$args['loading_import_wait_text'].'</div>';
    $r .= '</fieldset></div></form>';
    return $r;
  }

  /*
   * Perform the upload when the user clicks on the import button
   */
  private static function get_upload_click_function($args,$fileArrayForImportRowsToProcessForImport,$columnHeadingIndexPositions,$plotsAndPlotGroupsUserHasRightsTo) {
    // store the warehouse user ID if we know it.
    if (function_exists('hostsite_get_user_field'))
      $currentUserId = hostsite_get_user_field('indicia_user_id');
    $plotsToCreateNames=array();
    $plotsToCreateSrefs=array();
    $plotsToCreateSrefSystems=array();
    $distinctPlotGroupNamesToCreate=array();
    $websiteId=$args['website_id'];
    //If we create the plots, plot groups before we upload to the warehouse, then the new plots will already be available for the warehouse to use.
    $importTypesToCreate=array('nP-nPG','ePG');
    $plotDataToCreate=self::extract_data_to_create_from_import_rows($fileArrayForImportRowsToProcessForImport,$columnHeadingIndexPositions,'plot',$importTypesToCreate);
    $importTypesToCreate=array('nP-nPG');
    $plotGroupDataToCreate=self::extract_data_to_create_from_import_rows($fileArrayForImportRowsToProcessForImport,$columnHeadingIndexPositions,'plot_group',$importTypesToCreate);
    //To Do AVB - Check the logic of this, I don't think this is right, as we are not checking the group
    //to find existing plots (as they might have many groups), so code like 'eP' MIGHT need including here....double check
    $importTypesToCreate=array('nP-nPG','ePG');
    $plotGrouptoPlotAttachmentsToCreate=self::extract_plot_group_to_plot_attachments_to_create($fileArrayForImportRowsToProcessForImport,$columnHeadingIndexPositions,$importTypesToCreate,$plotDataToCreate,$plotGroupDataToCreate);
    //Cycle through each plot we need to create and get its name and spatial reference and spatial reference system (if available,
    //else we fall back on the sref system supplied on the Settings page)
    foreach ($plotDataToCreate as $plotDataToAdd) {
      $plotsToCreateNames[]=$plotDataToAdd['name'];
      $plotsToCreateSrefs[]=$plotDataToAdd['sref'];
      $plotsToCreateSrefSystems[]=$plotDataToAdd['sref_system'];
    }
    foreach ($plotGroupDataToCreate as $groupToAdd) {
      $distinctPlotGroupNamesToCreate[]=$groupToAdd['name'];
    }
    foreach ($plotGrouptoPlotAttachmentsToCreate as $plotGrouptoPlotAttachmentToCreate) {
      $plotPairsForPlotGroupAttachment[]=$plotGrouptoPlotAttachmentToCreate;
    }
    //When the import button is clicked do the following
    //- Disable the button to prevent double-clicking
    //- Show a Please Wait message to the user
    //- Create any new plots, plot groups that are required.
    //- Submit the import to the warehouse
    if (!empty($websiteId)) {
      data_entry_helper::$javascript .= "var websiteId = ".$websiteId.";";
    }
    $warehouseUrl = self::get_warehouse_url();

    data_entry_helper::$javascript .= "
    $('#create-import-data').on('click', function() {
    $('#create-import-data').attr('disabled','true');
    $('#import-loading-msg').show();";
    // Send new plots, groups, and plot group attachments to the Warehouse
    if (empty($plotsToCreateNames) || empty($plotsToCreateSrefs) || empty($plotsToCreateSrefSystems)) {
      $plotsToCreateNames = array();
      $plotsToCreateSrefs = array();
      $plotsToCreateSrefSystems = array();
    }
    if (empty($distinctPlotGroupNamesToCreate)) {
      $distinctPlotGroupNamesToCreate = array();
    }
    if (empty($plotPairsForPlotGroupAttachment)) {
      $plotPairsForPlotGroupAttachment = array();
    }
    data_entry_helper::$javascript .= "create_warehouse_ajax_requests(
      '".$warehouseUrl."',websiteId,".$currentUserId.",
      ".json_encode($plotsToCreateNames).",".json_encode($plotsToCreateSrefs).",".json_encode($plotsToCreateSrefSystems).",".$args['plot_group_identifier_name_lookup_loc_attr_id'].",".$args['plot_location_type_id'].",
      ".json_encode($distinctPlotGroupNamesToCreate).",
      ".json_encode($plotPairsForPlotGroupAttachment).",".$args['plot_group_identifier_name_lookup_loc_attr_id'].",".$args['plot_group_permission_person_attr_id']."
    );\n";
    data_entry_helper::$javascript .= "});";

  }

  private static function extract_plot_group_to_plot_attachments_to_create($fileArrayForImportRowsToProcessForImport,$columnHeadingIndexPositions,$importTypesToCreate,$plotDataToCreate,$plotGroupDataToCreate) {
    $arrayIdx=0;
    $plotGrouptoPlotAttachmentsToCreate=array();
    $plotNamesToCheck=array();
    $plotGroupNamesToCheck=array();
    foreach ($plotDataToCreate as $plotDataItemToCreate)
      $plotNamesToCheck[]=$plotDataItemToCreate['name'];
    foreach ($plotGroupDataToCreate as $plotGroupDataItemToCreate)
      $plotGroupNamesToCheck[]=$plotGroupDataItemToCreate['name'];
    foreach ($fileArrayForImportRowsToProcessForImport as $importSituationRows) {
      foreach ($importSituationRows as $arrayImportRowToProcess) {
        if (!empty($arrayImportRowToProcess[$columnHeadingIndexPositions['plotNameHeaderIdx']])&&!empty($arrayImportRowToProcess[$columnHeadingIndexPositions['plotGroupNameHeaderIdx']])) {
          if (in_array($arrayImportRowToProcess[$columnHeadingIndexPositions['plotNameHeaderIdx']],$plotNamesToCheck)||
              in_array($arrayImportRowToProcess[$columnHeadingIndexPositions['plotGroupNameHeaderIdx']],$plotGroupNamesToCheck)) {
            $nameGroupPair=$arrayImportRowToProcess[$columnHeadingIndexPositions['plotNameHeaderIdx']].'|'.$arrayImportRowToProcess[$columnHeadingIndexPositions['plotGroupNameHeaderIdx']];
            if (!in_array($nameGroupPair,$plotGrouptoPlotAttachmentsToCreate))
              $plotGrouptoPlotAttachmentsToCreate[$arrayIdx]=$arrayImportRowToProcess[$columnHeadingIndexPositions['plotNameHeaderIdx']].'|'.$arrayImportRowToProcess[$columnHeadingIndexPositions['plotGroupNameHeaderIdx']];
          }
          $arrayIdx++;
        }
      }
    }
    return $plotGrouptoPlotAttachmentsToCreate;
  }

  /*
   * Function looks at all the rows in the import, and returns an array of data of a particular type that needs to be created.
   * e.g. ['Plot group 1','Plot group 2','Plot group 3']
   * The possibilities are plot, plot_group
   */
  private static function extract_data_to_create_from_import_rows($fileArrayForImportRowsToProcessForImport,$columnHeadingIndexPositions,$extractionType,$importTypesToCreate) {
    $dataExtractedReadyToCreate=array();
    foreach ($importTypesToCreate as $possibleImportTypeToCreate) {
      $continue = false;
      foreach ($fileArrayForImportRowsToProcessForImport as $key => $importRow) {
        if ($key==$possibleImportTypeToCreate) {
          $continue=true;
          $importTypeToCreate = $key;
        }
      }
      //Don't need to proceed if there is no data for a particular import sitation
      if ($continue===true) {
        //Cycle through each data row in the import situations we need to create data for
        foreach ($fileArrayForImportRowsToProcessForImport[$importTypeToCreate] as $rowToExtractDataFrom) {
          $dataFromRow=array();
          if ($extractionType==='plot')
            $dataFromRow = self::extract_plot_to_create_from_import_row_if_we_need_to($rowToExtractDataFrom,$dataExtractedReadyToCreate,$columnHeadingIndexPositions);
          if ($extractionType==='plot_group')
            $dataFromRow = self::extract_group_to_create_from_import_row_if_we_need_to($rowToExtractDataFrom,$dataExtractedReadyToCreate,$columnHeadingIndexPositions['plotGroupNameHeaderIdx'],$extractionType);
          if ($extractionType==='plot_group_attachment')
            $dataFromRow = self::extract_plot_group_attachment_to_create_from_import_row_if_we_need_to($rowToExtractDataFrom,$dataExtractedReadyToCreate,$columnHeadingIndexPositions);
          //If there is some data to create for the row then it can be stored in an array for processing.
          if (!empty($dataFromRow['name'])) {
            $dataExtractedReadyToCreate[]=$dataFromRow;
          }
        }
      }
    }
    return $dataExtractedReadyToCreate;
  }

  /*
   * Extract the data relating to new plots so that they can be created before passing the import to the warehouse
   * @param array $rowToExtractDataFrom Array of columns from row we want to extract data from
   * @param array $dataExtractedReadyToCreate Array of data already extracted (so we can make sure the plot hasn't already been listed for creation)
   * @param array $columnHeadingIndexPositions Array which contains the position of each of the columns relevant to the duplicate check.
   */
  private static function extract_plot_to_create_from_import_row_if_we_need_to($rowToExtractDataFrom,$dataExtractedReadyToCreate,$columnHeadingIndexPositions) {
    $doNotCreatePlotForRow=false;
    //Get spatial reference system from row if we can, else get from Settings page
    if ($columnHeadingIndexPositions['sampleSrefSystemHeaderIdx']!=-1)
      $spatialReferenceSystem=$rowToExtractDataFrom[$columnHeadingIndexPositions['sampleSrefSystemHeaderIdx']];
    else
      $spatialReferenceSystem=$_SESSION['sample:entered_sref_system'];
    //If the index position is -1, it means that column does not appear in the data file
    //We can only create plots if there is a plot name, and a spatial reference (we know there is a spatial reference system as we set it above)
    if ($columnHeadingIndexPositions['plotNameHeaderIdx']!=-1&&$columnHeadingIndexPositions['sampleSrefHeaderIdx']!=-1) {
      if (!empty($dataExtractedReadyToCreate)) {
        //Check the existing data that we have already extracted to see if the plot already exists, if it does, it doesn't need to be re-extracted
        foreach ($dataExtractedReadyToCreate as $plotAlreadyMarkedForCreation) {
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
   * @param array $dataExtractedReadyToCreate Array of data already extracted (so we can make sure the group hasn't already been listed for creation)
   * @param array $groupColoumnIdx Integer Position from left of file of the column we want to work with.
   * @param string $extractionType String which tells us whether it is sample or plot groups we are dealing with
   */
  private static function extract_group_to_create_from_import_row_if_we_need_to($rowToExtractDataFrom,$dataExtractedReadyToCreate,$groupColoumnIdx,$extractionType) {
    $doNotCreateGroupForRow=false;
    //If the index position is -1, it means that column does not appear in the data file
    //We can only create groups if there is a group name
    if ($groupColoumnIdx!=-1) {
      if (!empty($dataExtractedReadyToCreate)) {
        foreach ($dataExtractedReadyToCreate as $groupAlreadyMarkedForCreation) {
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
   * @param Array $fileArrayForImportRowsToProcessForImports contains all the rows that we need to import
   * @param Array $headerLineItems Array of all headers in the first row of the file
   * @param Array $importTypes Array of all the import situations we want to display messages for
   */
  private static function display_import_warnings($args,$fileArrayForImportRowsToProcessForImport,$headerLineItems,$importTypes) {
    $r='';
    foreach ($importTypes as $importTypeCode=>$importTypeStates) {
      if (!empty($args[$importTypeCode.'_message']))
        $warningText=$args[$importTypeCode.'_message'];
      $r .= self::display_individual_message($fileArrayForImportRowsToProcessForImport,$warningText,$importTypeCode,$headerLineItems);
    }
    return $r;
  }
  /*
   * Display a warning of a particular type before the user continues with the import
   * @param Array $fileArrayForImportRowsToProcessForImport Array all the import rows applicable to the warning
   * @param String $warningText Warning to display to the user
   * @param $warningCode The code for the import situation we are dealing with
   * @param $headerLineItems All the import headers so that the import rows we want to display can be listed
   *
   */
  private static function display_individual_message($fileArrayForImportRowsToProcessForImport,$warningText,$warningCode,$headerLineItems) {
    $r='';
    if (!empty($fileArrayForImportRowsToProcessForImport[$warningCode])) {
      $r.='<div>'.$warningText.'</div>';
      $r .= '<table><tr>';
      foreach ($headerLineItems as $headerLineItem) {
        $r.='<th>'.$headerLineItem.'</th>';
      }
      $r .= '</tr>';
      foreach ($fileArrayForImportRowsToProcessForImport[$warningCode] as $theRow) {
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
   * This is only required for columns we are going to use for matching plots and plot groups against existing ones.
   * @param Array $args Arguments from Edit Tab
   */
  private static function store_column_header_names_for_existing_match_checks($args,$postWithoutSpacesUnderscoresInKeys) {
    $chosenColumnHeadings=array();
    //Cycle through the mappings that has been posted and store the header against its meaning.
    //We need to do this as the names the user chooses as column titles can be anything,
    //so we need to store their meaning
    foreach ($postWithoutSpacesUnderscoresInKeys as $newKey => $chosenField) {
      if ($chosenField==='sample:date') {
        $chosenColumnHeadings['sampleDateHeaderName'] = $newKey;
      }
      if ($chosenField==='sample:entered_sref')
        $chosenColumnHeadings['sampleSrefHeaderName'] = $newKey;
      if ($chosenField==='sample:entered_sref_system')
        $chosenColumnHeadings['sampleSrefSystemHeaderName'] = $newKey;
      if ($chosenField==='sample:fk_location')
        $chosenColumnHeadings['plotNameHeaderName'] = $newKey;
      if ($chosenField==='locAttr:'.$args['plot_group_identifier_name_text_attr_id'])
        $chosenColumnHeadings['plotGroupNameHeaderName'] = $newKey;
      //Note that these need to be sample attributes as they need to be passed to the
      //warehouse as part of the sample so that the spatial reference can be calculate
      if ($chosenField==='smpAttr:'.$args['vice_county_attr_id'])
        $chosenColumnHeadings['plotViceCountyHeaderName'] = $newKey;
      if ($chosenField==='smpAttr:'.$args['country_attr_id'])
        $chosenColumnHeadings['plotCountryHeaderName'] = $newKey;
    }
    return $chosenColumnHeadings;
  }

 /*
   * Save the horizontal position of each column that is to be used when matching existing Samples, Plots, Sample
   * Groups and Plot Groups.
   * This allows us to count along a data row and know what we are looking at without re-examining the headings.
   * Headings are saved as an index from zero.
   *
   * @param Array $headerLineItemsWithoutSpacesOrUnderscores Array of header names without spaces or underscores so we can match the $_Post data
   * @param Array $chosenColumnHeadings The actual header names used in the file stored with their meaning
   * as the array key
   */
  private static function get_column_heading_index_positions($headerLineItemsWithoutSpacesOrUnderscores,$chosenColumnHeadings) {
    $last_key = key(array_slice($headerLineItemsWithoutSpacesOrUnderscores, -1, 1, TRUE ));
    $columnHeadingIndexPositions=array();
     //We can't leave these uninitialised as we will get loads of not initialised errors.
    //Overcome this by setting to -1, which is an index we will never actually use.
    $columnHeadingIndexPositions['sampleDateHeaderIdx']=-1;
    $columnHeadingIndexPositions['sampleSrefHeaderIdx']=-1;
    $columnHeadingIndexPositions['sampleSrefSystemHeaderIdx']=-1;
    $columnHeadingIndexPositions['plotNameHeaderIdx']=-1;
    $columnHeadingIndexPositions['plotGroupNameHeaderIdx']=-1;
    $columnHeadingIndexPositions['spatialReferenceTypeIdx']=-1;
    //Cycle through all the names from the header line, then check to see if there is a match in the array holding the
    //header names meanings. If there is a match, it means we have identified the header and can save its position as
    //and index starting from zero.
    foreach ($headerLineItemsWithoutSpacesOrUnderscores as $idx=>$header) {
      //Remove white space from the ends of the headers
      $header=trim($header);
      if (!empty($chosenColumnHeadings['sampleDateHeaderName']) && $header == $chosenColumnHeadings['sampleDateHeaderName'])
        $columnHeadingIndexPositions['sampleDateHeaderIdx'] = $idx;
      if (!empty($chosenColumnHeadings['sampleSrefHeaderName']) && $header == $chosenColumnHeadings['sampleSrefHeaderName'])
        $columnHeadingIndexPositions['sampleSrefHeaderIdx'] = $idx;
      //If column is the user selected spatial reference system column, or the spatial reference system column
      //automatically created by the computer (when the user selects a spatial reference system
      //as the very start of the wizard) then save the position of the column
      if ((!empty($chosenColumnHeadings['sampleSrefSystemHeaderName'])&&$header == $chosenColumnHeadings['sampleSrefSystemHeaderName'])
              ||$header=='Spatialreferencesystem(auto-generated)') {
        if (!empty($_SESSION['sample:entered_sref_system'])) {
          //If automatically created column save the position as one after the last existing column
          $columnHeadingIndexPositions['sampleSrefSystemHeaderIdx'] = $last_key+1;
          $last_key=$last_key+1;
        } else {
          $columnHeadingIndexPositions['sampleSrefSystemHeaderIdx']=$idx;
        }
      }
      if (!empty($chosenColumnHeadings['plotNameHeaderName']) && $header == $chosenColumnHeadings['plotNameHeaderName'])
        $columnHeadingIndexPositions['plotNameHeaderIdx'] = $idx;
      if (!empty($chosenColumnHeadings['plotGroupNameHeaderName']) && $header == $chosenColumnHeadings['plotGroupNameHeaderName'])
        $columnHeadingIndexPositions['plotGroupNameHeaderIdx'] = $idx;
      if (!empty($chosenColumnHeadings['plotViceCountyHeaderName']) && $header == $chosenColumnHeadings['plotViceCountyHeaderName'])
        $columnHeadingIndexPositions['plotViceCountyHeaderIdx'] = $idx;
      if (!empty($chosenColumnHeadings['plotCountryHeaderName']) && $header == $chosenColumnHeadings['plotCountryHeaderName'])
        $columnHeadingIndexPositions['plotCountryHeaderIdx'] = $idx;
    }
    //If a plot name has been specified (Unique Plot ID) we can use that.
    //Otherwise we just put the spatial reference into the plot name which allows import
    //without the required location name being missing
    if ($columnHeadingIndexPositions['plotNameHeaderIdx']===-1)
      $columnHeadingIndexPositions['plotNameHeaderIdx']=$columnHeadingIndexPositions['sampleSrefHeaderIdx'];
    //Spatial reference type is not set by user, so set to last column so we have a place to show
    //it when it is set automatically
    if ($columnHeadingIndexPositions['spatialReferenceTypeIdx']===-1)
      $columnHeadingIndexPositions['spatialReferenceTypeIdx']=$last_key+1;
    return $columnHeadingIndexPositions;
  }

  /*
   * Check the samples/plots the user has permission to use against the import data.
   * Once that is done, we are able to determine and report to the user how the data will be imported
   * e.g. (will we use new samples, existing plots etc)
   * @param Array $args Arguments from the Edit Tab
   * @param Array $fileRowsAsArray Rows from import file
   * @param Array $plotsAndPlotGroupsUserHasRightsTo Plots and Plot Groups the user has rights to
   * @param Array $columnHeadingIndexPositions The position from the left of some of the more important columns starting at position 0
   */
  private static function check_existing_user_data_against_import_data($args,$fileRowsAsArray,$plotsAndPlotGroupsUserHasRightsTo,$columnHeadingIndexPositions) {
    //Store the rows into the different import categories ready to process
    $fileArrayForImportRowsToProcessForImport=array();
    if (!empty($fileRowsAsArray)) {
      //Cycle through each row in the import data
      foreach ($fileRowsAsArray as $idx => &$fileRowsAsArrayLine) {
        $lineState=array('spatialRefPresent'=>1,'existingPlot'=>0,'newPlotExistingPlotGroup'=>0);
        //If a spatial reference is missing or cannot be generated, always through a fatal error
        if (empty($fileRowsAsArrayLine[$columnHeadingIndexPositions['sampleSrefHeaderIdx']])) {
          $lineState['spatialRefPresent']=0;
        }
        //Only need to continue if there is a spatial reference detected for the line, otherwise we have a no spatial reference fatal failure
        if ($lineState['spatialRefPresent']===1) {
          //Check the data on each list to see if it falls into the category of existing plot or existing plot group,
          //The $lineState is then altered by each function
          self::existing_plot_check_for_line($fileRowsAsArrayLine,$plotsAndPlotGroupsUserHasRightsTo['plotsUserHasRightsTo'],$lineState,$columnHeadingIndexPositions);
          //Only need to set newPlotExistingPlotGroup flag if existingPlot is 0
          if ($lineState['existingPlot']==0)
            self::existing_group_check_for_line($fileRowsAsArrayLine,$plotsAndPlotGroupsUserHasRightsTo['plotGroupsUserHasRightsTo'],$lineState,$columnHeadingIndexPositions,'plot');
          //Save rows into the import categories which are stored as keys in the $fileArrayForImportRowsToProcessForImport array
        }
        self::assign_import_row_into_import_category($fileArrayForImportRowsToProcessForImport,$fileRowsAsArrayLine,$lineState,$args['nonFatalImportTypes']);
        self::assign_import_row_into_import_category($fileArrayForImportRowsToProcessForImport,$fileRowsAsArrayLine,$lineState,$args['fatalImportTypes']);
      }
    }
    return $fileArrayForImportRowsToProcessForImport;
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
    $fileRowsAsArrayLine[$columnHeadingIndexPositions['sampleSrefHeaderIdx']]=trim($fileRowsAsArrayLine[$columnHeadingIndexPositions['sampleSrefHeaderIdx']]);
    $fileRowsAsArrayLine[$columnHeadingIndexPositions['plotNameHeaderIdx']]=trim($fileRowsAsArrayLine[$columnHeadingIndexPositions['plotNameHeaderIdx']]);
    if (!empty($fileRowsAsArrayLine[$columnHeadingIndexPositions['plotGroupNameHeaderIdx']]))
      $fileRowsAsArrayLine[$columnHeadingIndexPositions['plotGroupNameHeaderIdx']]=trim($fileRowsAsArrayLine[$columnHeadingIndexPositions['plotGroupNameHeaderIdx']]);
    //Only interested in the plots the user has rights to, if they don't have any anyway, we don't need to return anything from this function
    foreach ($plotsUserHasRightsTo as $aPlotUserHasRightsTo) {
      //If spatial reference system isn't on a import row, then get it from the mappings page
      if ($columnHeadingIndexPositions['sampleSrefSystemHeaderIdx']!=-1)
         $spatialReferenceSystem=$fileRowsAsArrayLine[$columnHeadingIndexPositions['sampleSrefSystemHeaderIdx']];
      else
        $spatialReferenceSystem=$_SESSION['sample:entered_sref_system'];
      $spatialReferenceSystem=trim($spatialReferenceSystem);
      //Only indicate existing plot if the plot the user has rights to matches the row we are using from the import file.
      //Here we can pass the test if both comparison columns are empty, the other pass scenario is if the columns are both filled in and they match as well.
      if (((empty($fileRowsAsArrayLine[$columnHeadingIndexPositions['sampleSrefHeaderIdx']])&&empty($aPlotUserHasRightsTo['centroid_sref']))||
          ((!empty($fileRowsAsArrayLine[$columnHeadingIndexPositions['sampleSrefHeaderIdx']])&&!empty($aPlotUserHasRightsTo['centroid_sref'])) &&
          strtolower($fileRowsAsArrayLine[$columnHeadingIndexPositions['sampleSrefHeaderIdx']])==strtolower($aPlotUserHasRightsTo['centroid_sref']))) &&

          ((empty($spatialReferenceSystem)&&empty($aPlotUserHasRightsTo['centroid_sref_system']))||
          ((!empty($spatialReferenceSystem)&&!empty($aPlotUserHasRightsTo['centroid_sref_system'])) &&
          strtolower($spatialReferenceSystem)==strtolower($aPlotUserHasRightsTo['centroid_sref_system']))) &&

          ((empty($fileRowsAsArrayLine[$columnHeadingIndexPositions['plotNameHeaderIdx']])&&empty($aPlotUserHasRightsTo['plot_name']))||
          ((!empty($fileRowsAsArrayLine[$columnHeadingIndexPositions['plotNameHeaderIdx']])&&!empty($aPlotUserHasRightsTo['plot_name'])) &&
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
   * Function is given a row for the import. The flags for the row (which have already been set to say whether it is an existing sample, plot, or plot group) are
   * checked against the import situations. When we find a matching situation, we store it against that situation.
   */
  private static function assign_import_row_into_import_category(&$fileArrayForImportRowsToProcessForImport,$fileRowsAsArrayLine,$lineState,$importTypes) {
    foreach ($importTypes as $importTypeCode=>$importTypeStates) {
      if ($lineState==$importTypeStates) {
        $fileArrayForImportRowsToProcessForImport[$importTypeCode][]=$fileRowsAsArrayLine;
      }
    }
    return $fileArrayForImportRowsToProcessForImport;
  }

  /*
   * If spatial reference is missing then automatically generate one using the vice county name or country name.
   * Note this has an equivalent function with the same name in the Plant Portal Warehouse module.
   * Changes to the logic here should also occur in that function
   */
  private static function auto_generate_grid_references($fileRowsAsArray,$columnHeadingIndexPositions,$viceCountiesList,$countriesList) {
    $viceCountyPairs = explode(',',$viceCountiesList);
    $countryPairs = explode(',',$countriesList);
    //Cycle through all the data rows
    foreach ($fileRowsAsArray as &$fileRowToProcess) {
      //If the spatial reference is empty we need to do some work to try and get it from the vice county
      if (empty($fileRowToProcess[$columnHeadingIndexPositions['sampleSrefHeaderIdx']])) {
        //All the stored vice counties are a name with a grid reference (separated by a |)
        foreach ($viceCountyPairs as $viceCountyNameGridRefPair) {
          $viceCountyNameGridRefPairExploded=explode('|',$viceCountyNameGridRefPair);
          //If we find a match for the vice county then we can set the spatial reference and spatial reference system from the vice county
          if (!empty($columnHeadingIndexPositions['plotViceCountyHeaderIdx'])&&
                  !empty($fileRowToProcess[$columnHeadingIndexPositions['plotViceCountyHeaderIdx']])&&
                  !empty($viceCountyNameGridRefPairExploded[0]) &&
                  trim($fileRowToProcess[$columnHeadingIndexPositions['plotViceCountyHeaderIdx']])==trim($viceCountyNameGridRefPairExploded[0])) {
            $fileRowToProcess[$columnHeadingIndexPositions['sampleSrefHeaderIdx']]=$viceCountyNameGridRefPairExploded[1];
            $fileRowToProcess[$columnHeadingIndexPositions['sampleSrefSystemHeaderIdx']]='4326';
            $fileRowToProcess[$columnHeadingIndexPositions['spatialReferenceTypeIdx']]='boundary';
          }
        }
      }
      //If spatial reference is still empty we can do the same with countries
      if (empty($fileRowToProcess[$columnHeadingIndexPositions['sampleSrefHeaderIdx']])) {
        foreach ($countryPairs as $countryNameGridRefPair) {
          $countryNameGridRefPairExploded=explode('|',$countryNameGridRefPair);
          if (!empty($columnHeadingIndexPositions['plotCountryHeaderIdx'])&&
                  !empty($fileRowToProcess[$columnHeadingIndexPositions['plotCountryHeaderIdx']])&&
                  !empty($countryNameGridRefPairExploded[0]) &&
                  trim($fileRowToProcess[$columnHeadingIndexPositions['plotCountryHeaderIdx']])==trim($countryNameGridRefPairExploded[0])) {
            $fileRowToProcess[$columnHeadingIndexPositions['sampleSrefHeaderIdx']]=$countryNameGridRefPairExploded[1];
            $fileRowToProcess[$columnHeadingIndexPositions['sampleSrefSystemHeaderIdx']]='4326';
            $fileRowToProcess[$columnHeadingIndexPositions['spatialReferenceTypeIdx']]='boundary';
          }
        }
      }
      ////If the spatial reference has not been filled in by some kind of override then as a last resort
      //collect it from the drop-down at the start of the wizard.
      if (empty($fileRowToProcess[$columnHeadingIndexPositions['sampleSrefSystemHeaderIdx']])) {
        if (!empty($_SESSION['sample:entered_sref_system'])) {
          $fileRowToProcess[$columnHeadingIndexPositions['sampleSrefSystemHeaderIdx']]=$_SESSION['sample:entered_sref_system'];
        }
      }
      if (empty($fileRowToProcess[$columnHeadingIndexPositions['spatialReferenceTypeIdx']]))
       $fileRowToProcess[$columnHeadingIndexPositions['spatialReferenceTypeIdx']]='grided';
    }
    return $fileRowsAsArray;
  }

  /*
   * Get plots and plot groups user has rights to so we can check for existing ones
   */
  private static function get_plots_and_groups_user_has_rights_to($auth,$args) {
    iform_load_helpers(['report_helper']);
    $plotsAndPlotGroupsUserHasRightsTo=array();
    global $user;
    if (function_exists('hostsite_get_user_field'))
      $currentUserId = hostsite_get_user_field('indicia_user_id');
    $plotsAndPlotGroupsUserHasRightsTo['plotsUserHasRightsTo'] = report_helper::get_report_data(array(
      'dataSource'=>'projects/plant_portal/get_plots_from_groups_for_user',
      'readAuth'=>$auth['read'],
      'extraParams'=>array(
                          'plot_group_permission_person_attr_id'=>$args['plot_group_permission_person_attr_id'],
                          'user_id'=>$currentUserId)
    ));
    $plotsAndPlotGroupsUserHasRightsTo['plotGroupsUserHasRightsTo'] = report_helper::get_report_data(array(
      'dataSource'=>'projects/plant_portal/get_groups_for_user',
      'readAuth'=>$auth['read'],
      'extraParams'=>array(
                          'group_permission_person_attr_id'=>$args['plot_group_permission_person_attr_id'],
                          'user_id'=>$currentUserId)
    ));
    return $plotsAndPlotGroupsUserHasRightsTo;
  }

  private static function get_warehouse_url() {
    if (!empty(parent::$warehouse_proxy))
      $warehouseUrl = parent::$warehouse_proxy;
    else
      $warehouseUrl = parent::$base_url;
    return $warehouseUrl;
  }

  /* Function used to preserve the post from previous stages as we move through the importer otherwise values are lost from
     2 steps ago. Also preserves the automatic mappings used to skip the mapping stage by saving it to the post */
  private static function preserve_fields($options,$filename,$importStep=null) {
    $mappingsAndSettings=self::get_mappings_and_settings($options);
    $settingFields=$mappingsAndSettings['settings'];
    $mappingFields=$mappingsAndSettings['mappings'];
    $reload = self::get_reload_link_parts();
    $reload['params']['uploaded_csv']=$filename;
    $reloadpath = $reload['path'] . '?' . self::array_to_query_string($reload['params']);
    $r =  "<div><form method=\"post\" id=\"fields_to_retain_form\" action=\"$reloadpath\" class=\"iform\" onSubmit=\"window.location = '$reloadpath;\">\n";
    foreach ($settingFields as $field=>$value) {
      if (!empty($settingFields[$field])) {
        if (!empty($value) && $field!=='import_step' && $field !=='submit') {
          $r .= '<input type="hidden" name="setting['.$field.']" id="setting['.$field.']" value="'.$value.'"/>'."\n";
        }
      }
    }

    foreach ($mappingFields as $field=>$value) {
      if (!empty($mappingFields[$field])) {
        if (is_string($field)&&is_string($value)&&!empty($value) && $field!=='import_step' && $field !=='submit') {
          $r .= '<input type="hidden" name="mapping['.$field.']" id="mapping['.$field.']" value="'.$value.'"/>'."\n";
        }
      }
    }
    if (!empty($importStep)&&$importStep!==null) {
      $r .= '<input type="hidden" name="import_step" value="'.$importStep.'" />';
    }
    $r .= '<input id="hidden_submit" type="submit" style="display: none" value="'.lang::get('Upload').'"></form>';
    $r .=  "</form><div>\n";
    return $r;
  }

  //Collect errors from error checking stage
  private static function collect_errors($options,$filename) {
    $errorsDetected=false;
    $request = parent::$base_url."index.php/services/plant_portal_import/get_upload_result?uploaded_csv=".$filename;
    $request .= '&'.self::array_to_query_string($options['auth']['read']);
    $response = self::http_post($request, array());
    if (isset($response['output'])) {
      $output = json_decode($response['output'], true);
    } else {
      $output = array();
    }
    return $output;
  }

  //Jump to the results screen if errors have been detected during the error checking stage
  //This only applies if we are preventing all commits if any errors are detected.
  //(otherwise upload_result function is called instead)
  private static function display_result_as_error_check_stage_failed($options,$output) {
    // get the path back to the same page
    $reload = self::get_reload_link_parts();
    $reloadpath = $reload['path'] . '?' . self::array_to_query_string($reload['params']);
    $downloadInstructions=lang::get('no_commits_download_error_file_instructions');
    $r = lang::get('{1} problems were detected during the import.', $output['problems']) . ' ' .
        $downloadInstructions .
        " <a href=\"$output[file]\">" . lang::get('Download the records that did not import.') . '</a>';
    $r .= "<p>".lang::get('Once you have finished making corrections ')."<a href=\"$reloadpath\">".lang::get('please reupload the file.')."</a></p>";
    return $r;
  }

  private static function send_mappings_and_settings_to_warehouse($filename,$options,$mappingsAndSettings) {
    $mappings=$mappingsAndSettings['mappings'];
    $settings=$mappingsAndSettings['settings'];
    if (empty($settings['useAssociations']) || !$settings['useAssociations']) {
      $settings=self::remove_unused_associations($options,$settings);
    }

    $settings=self::remove_unused_settings($settings);

    $metadata=self::create_metadata_array($mappings,$settings);

    if (function_exists('hostsite_set_user_field')) {
      self::save_user_import_mappings($mappings);
    }
    $post = array_merge($options['auth']['write_tokens'], $metadata);
    $request = parent::$base_url."index.php/services/plant_portal_import/cache_upload_metadata?uploaded_csv=$filename";
    $response = self::http_post($request, $post);
    if (!isset($response['output']) || $response['output'] != 'OK')
      return "Could not upload the settings metadata. <br/>".print_r($response, true);
    else
      return $mappingsAndSettings;
  }

  //Collect the mappings and settings from various places depending on importer mode, wizard stage.
  //These can be held in variables, option variable, or the post. Collect as appropriate
  private static function get_mappings_and_settings($options) {
    $mappingsAndSettings=array();
    $mappingsAndSettings['mappings']=array();
    $mappingsAndSettings['settings']=array();
    // If the last step was skipped because the user did not have any settings to supply, presetSettings contains the presets.
    // Otherwise we'll use the settings form content which already in $_POST so will overwrite presetSettings.
    if (isset($options['presetSettings']))
      $mappingsAndSettings['settings'] = $options['presetSettings'];
    //Collect settings from a designated array in the post if available
    if (!empty($_POST['setting']))
      $mappingsAndSettings['settings']=array_merge($mappingsAndSettings['settings'],$_POST['setting']);
    //If the post does not contain a specific array for settings, then just ge the settings as the general post fields
    if (!isset($_POST['setting']))
      $mappingsAndSettings['settings']=array_merge($mappingsAndSettings['settings'],$_POST);
    //The settings should simply be the settings, so remove any mappings or settings sub-arrays if these have become jumbled
    //up inside our settings array
    if (!empty($mappingsAndSettings['settings']['mapping']))
      unset($mappingsAndSettings['settings']['mapping']);
    if (!empty($mappingsAndSettings['settings']['setting']))
      unset($mappingsAndSettings['settings']['setting']);
    //If we are skipping the mappings page, then the mapping will be in the automatic mappings variable
    //Change the keys in this array so that spaces are replaced with underscores so the mappings are the same
    //as if they had been stored in the post
    if (!empty(self::$automaticMappings) && !empty($options['skipMappingIfPossible']) && $options['skipMappingIfPossible']==true ) {
      $adjustedAutomaticMappings=array();
      foreach (self::$automaticMappings as $key=>$automaticMap) {
        $adjustedAutomaticMappings[str_replace(" ", "_", $key)]=$automaticMap;
      }
      $mappingsAndSettings['mappings']=$adjustedAutomaticMappings;
    }
    //Collect mappings from a designated array in the post if available
    if (!empty($_POST['mapping']))
      $mappingsAndSettings['mappings']=array_merge($mappingsAndSettings['mappings'],$_POST['mapping']);
    //If there is a settings sub-array we know that there won't be any settings outside this sub-array in the post,
    //so we can cleanup any remaining fields in the post as they will be mappings not settings
    if (isset($_POST['setting']))
      $mappingsAndSettings['mappings']=array_merge($mappingsAndSettings['mappings'],$_POST);
    //The mappings should simply be the mappings, so remove any mappings or settings sub-arrays if these have become jumbled
    //up inside our mappings array
    if (!empty($mappingsAndSettings['mappings']['mapping']))
      unset($mappingsAndSettings['mappings']['mapping']);
    if (!empty($mappingsAndSettings['mappings']['setting']))
      unset($mappingsAndSettings['mappings']['setting']);
    //Remove any unused mappings or settings
    foreach ($mappingsAndSettings as $key=>$mappingsOrSetting) {
      if (empty($mappingsOrSetting)&&$key!=='mappings'&&$key!=='settings')
        unset($mappingsAndSettings[$key]);
    }
    foreach ($mappingsAndSettings['mappings'] as $key=>$mapping) {
      if (empty($mapping))
        unset($mappingsAndSettings['mappings'][$key]);
    }
    foreach ($mappingsAndSettings['settings'] as $key=>$setting) {
      if (empty($setting))
        unset($mappingsAndSettings['settings'][$key]);
    }
    return $mappingsAndSettings;
  }

  private static function create_metadata_array($mappings,$settings) {
    $metadata=array();
    if (!empty($mappings)) {
      $mappingsArray = array('mappings' => json_encode($mappings));
      $metadata=array_merge($metadata,$mappingsArray);
    }
    if (!empty($settings)) {
      $settingsArray = array('settings' => json_encode($settings));
      $metadata=array_merge($metadata,$settingsArray);
    }
    return $metadata;
  }

  private static function save_user_import_mappings($mappings) {
    $userSettings = array();
    foreach ($mappings as $column => $setting) {
      $userSettings[str_replace("_", " ", $column)] = $setting;
    }
    //if the user has not selected the Remember checkbox for a column setting and the Remember All checkbox is not selected
    //then forget the user's saved setting for that column.
    foreach ($userSettings as $column => $setting) {
      if (!isset($userSettings[$column.' '.'Remember']) && $column!='RememberAll')
        unset($userSettings[$column]);
    }
    hostsite_set_user_field("import_field_mappings", json_encode($userSettings));
  }

  //If we are using the sample external key as the indicator of which samples
  //the occurrences go into, then we need to check the sample data is consistant between the
  //rows which share the same external key, if not, warn the user.
  //We also need to check that rows with the same sample external key appear on consecutive
  //rows (otherwise the importer would create separate samples)
  private static function sample_external_key_issue_checks($options,$rows) {
    $mappingsAndSettings=self::get_mappings_and_settings($options);
    $columnIdx=0;
    $columnIdxsToCheck=array();
    //Cycle through each of the column mappings and get the position of the sample external key column
    foreach ($mappingsAndSettings['mappings'] as $columnName=>$mapping) {
      if ($mapping==='sample:external_key') {
        $sampleKeyIdx=$columnIdx;
      }
      //Need to check the plot group also, as the same plot name with different plot group would be considered as a different plot
      if ((substr($mapping,0,7) === 'sample:'||substr($mapping,0,8) === 'smpAttr:'||$mapping==='locAttr:'.$options['plot_group_identifier_name_text_attr_id'])&&$mapping!=='sample:external_key') {
        array_push($columnIdxsToCheck,$columnIdx);
      }
      $columnIdx++;
    }
    //Hold the latest row which has a given sample external key. All rows with matching external keys must have consistant
    //sample data, so we only need to hold one for examination
    $latestRowForEachSampleKey=array();
    //Rows which have inconsistancies
    $inconsistencyFailureRows=array();
    $clusteringFailureRows=array();
    //Flag individual rows which have the same sample external key but the sample data such as the date is inconsistent
    $rowInconsistencyFailure=false;
    //Flag individual rows which have the same sample external key but are not on consecutive rows as this would cause
    //two separate samples which would not be intended (note this flag is only used in the Sample External Key matching mode)
    $rowClusteringFailure=false;
    $rowNumber=1;
    foreach ($rows as $rowNum=>$rowArray) {
      //Reset flags for each row
      $rowInconsistencyFailure=false;
      $rowClusteringFailure=false;
      //Explode individual columns
      //If the sample key isn't empty on the row continue to work on the row
      if (!empty($sampleKeyIdx)) {
        //If the row we are working on has the same sample external key as one of the previous rows then
        //continue
        if (array_key_exists($rowArray[$sampleKeyIdx],$latestRowForEachSampleKey)) {
          //Cycle through each colum on the row
          foreach ($rowArray as $dataCellIdx=>$dataCell) {
            //If any of the row sample columns mismatches an earlier row that has same external key, then flag failure
            if (in_array($dataCellIdx,$columnIdxsToCheck)&&trim($dataCell)!==trim($latestRowForEachSampleKey[$rowArray[$sampleKeyIdx]][$dataCellIdx])) {
              $rowInconsistencyFailure=true;
            }
            //If the current row number minus the row number of the last row with the same sample external key is bigger
            //than 1 then we know the rows are not consecutive so we can flag a clustering failure to warn the user about
            if ((integer)$rowNumber-(integer)$latestRowForEachSampleKey[$rowArray[$sampleKeyIdx]]['row_number']>1) {
              $rowClusteringFailure=true;
            }
          }
        }
        //Save the most recent row for each Sample External Key
        $latestRowForEachSampleKey[$rowArray[$sampleKeyIdx]]=$rowArray;
        $latestRowForEachSampleKey[$rowArray[$sampleKeyIdx]]['row_number']=$rowNumber;
      }
      //Flag rows with the same sample external key but different sample data such as dates
      if ($rowInconsistencyFailure===true) {
        $fileRow=implode(',',$rowArray);
        $inconsistencyFailureRows[$rowNumber]=$fileRow;
      }
      //Flag rows with same sample external key which are not on consecutive rows
      if ($rowClusteringFailure===true) {
        $fileRow=implode(',',$rowArray);
        $clusteringFailureRows[$rowNumber]=$fileRow;
      }
      $rowNumber++;
    }
    $returnArray=array();
    $returnArray['inconsistencyFailureRows']=$inconsistencyFailureRows;
    $returnArray['clusteringFailureRows']=$clusteringFailureRows;
    return $returnArray;
  }

  /*
   * Show results of any sample issues between rows with the same sample external key
   * if using that import mode
   */
  private static function display_sample_external_key_data_mismatches($inconsistencyFailureRows=array(),$clusteringFailureRows=array()) {
    $r='';
    $r.='<div><p>You have selected to use the Sample External Key to determine which samples '
            . 'your occurrences are placed into. A scan has been made of your data and problems have been found. '
            . '<b><i>Note that the listed row numbers start with 0 being the header row and the first data row being row 1.</i></b></p></div>';
    if (!empty($inconsistencyFailureRows)) {
      $r.='<div><p><b>Inconsistancies have been found in the sample data on your rows which '
              . 'have a matching external key. Please correct your original file and select the '
              . 're-upload option.</b></p><p>The following rows have been found to have inconsistancies:</p></div>';
      foreach ($inconsistencyFailureRows as $rowNum=>$inconsistencyFailureRow) {
        $r.= '<em>'.$inconsistencyFailureRow.' (row number '.$rowNum.')</em><br>';
      }
      $r.= '<div><p>A row is considered to be inconsistant if the sample key matches an earlier row but some of the sample '
              . 'data (such as the date) is different. Note that rows without a sample external key at all are considered to have the same sample external key as each other.</p></div>';
    }
    if (!empty($clusteringFailureRows)) {
      $r.='<div><p><b>The following rows have been found to have matching sample external keys but are separated from earlier rows with the same external key inside the import file. '
              . 'Rows which you need to go into the same sample should be placed on consecutive rows. If the sample external key is not present for the row, then the row '
              . 'needs to be placed next to other rows that also don\'t have a sample external key.</b></p></div>';
      foreach ($clusteringFailureRows as $rowNum=>$clusteringFailureRow) {
        $r.= '<em>'.$clusteringFailureRow.' (row number '.$rowNum.')</em><br>';
      }
    }

    $reload = self::get_reload_link_parts();
    unset($reload['params']['total']);
    unset($reload['params']['uploaded_csv']);
    $reloadpath = $reload['path'] . '?' . self::array_to_query_string($reload['params']);
    $r .= "<p>".lang::get('Once you have finished making corrections to the original file ')."<a href=\"$reloadpath\">".lang::get('please reupload the file.')."</a></p>";
    return $r;
  }

  // when not using associations make sure that the association fields are not passed through.
  // These fields would confuse the association detection logic.
  private static function remove_unused_associations($options,$settings) {
    foreach($settings as $key => $value) {
        $parts = explode(':', $key);
        if($parts[0]==$options['model'].'_association' || $parts[0]==$options['model'].'_2')
          unset($settings[$key]);
    }
    return $settings;
  }

  // only want defaults that actually have a value - others can be set on a per-row basis by mapping to a column
  private static function remove_unused_settings($settings) {
    foreach ($settings as $key => $value) {
      if (empty($value)) {
        unset($settings[$key]);
      }
    }
    return $settings;
  }
}