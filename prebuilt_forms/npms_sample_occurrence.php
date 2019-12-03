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
 * @package    Client
 * @subpackage PrebuiltForms
 * @author    Indicia Team
 * @license    http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link     http://code.google.com/p/indicia/
 */

/**
 * Prebuilt Indicia data entry form.
 * NB has Drupal specific code.
 * 
 * @package    Client
 * @subpackage PrebuiltForms
 */

require_once('dynamic_sample_occurrence.php');

class iform_npms_sample_occurrence extends iform_dynamic_sample_occurrence {
  public static function get_parameters() {    
    return array_merge(
      parent::get_parameters(),
      array(
        array(
          'name'=>'survey_1_attr',
          'caption'=>'Survey 1 attribute ID',
          'description'=>'The sample attribute ID that will store the ID of survey 1.',
          'type'=>'string',
          'group'=>'Other IForm Parameters',
          'required'=>true
        ),
        array(
          'name'=>'person_square_attr_id',
          'caption'=>'Person attribute ID',
          'description'=>'The person attribute ID that stores the squares assigned to the user.',
          'type'=>'string',
          'group'=>'Other IForm Parameters',
          'required'=>true
        ),
        array(
          'name'=>'core_square_location_type_id',
          'caption'=>'Core square location type ID',
          'description'=>'NPMS square location type ID',
          'type'=>'string',
          'group'=>'Other IForm Parameters',
          'required'=>true
        ),
        array(
          'name'=>'npms_years_termlist_id',
          'caption'=>'NPMS years termlist ID',
          'description'=>'ID of termlist containing NPMS years',
          'type'=>'string',
          'group'=>'Other IForm Parameters',
          'required'=>true
        ),
        array(
          'name'=>'locking_date',
          'caption'=>'Locking Date',
          'description'=>'The date to lock the form from. Samples "created on" earlier than this date are read-only (use format yyyy-mm-dd)',
          'type'=>'string',
          'group'=>'Dates',
          'required'=>false
        ),
        array(
          'name'=>'override_locking_date_text',
          'caption'=>'Locking date text',
          'description'=>'Override the warning text shown to the user when the form is locked from editing 
          because the locking date has past.',
          'type'=>'string',
          'group'=>'Dates',
          'required'=>false
        ), 
        array(
          'name'=>'override_locking_wrong_user_text',
          'caption'=>'Locking wrong user text',
          'description'=>'Override the warning text shown to the user when the form is locked from editing
          because it is being viewed by someone who did not create the original sample.',
          'type'=>'string',
          'group'=>'Other IForm Parameters',
          'required'=>false
        ),   
        array(
          'name'=>'plot_number_attr_id',
          'caption'=>'Plot number/label attribute id',
          'description'=>'The attribute which holds the plot number/label.',
          'type'=>'select',
          'table'=>'location_attribute',
          'valueField'=>'id',
          'captionField'=>'caption',
          'group'=>'Other IForm Parameters',
          'required'=>false
        ),
        array(
          'name'=>'ignore_grid_sample_dates_before',
          'caption'=>'Ignore grid sample date before',
          'description'=>'Exclude any samples before this date on the initial grid of data.',
          'type'=>'string',
          'group'=>'Dates',
          'required'=>false
        ),
        array(
          'name'=>'genus_entry_found_msg',
          'caption'=>'Genus entry made message',
          'description'=>'Message to display to the user '
            . 'if they have entered a species at genus taxon rank.'
            . 'Only applies to Inventory, Indicator (without a static species grid).',
          'type'=>'textarea',
          'group'=>'Other IForm Parameters',
          'required'=>false
        ),
        array(
          'name'=>'abun_photo_msg',
          'caption'=>'Abundance missing message',
          'description'=>'Message to display to the user '
            . 'if they have added a photo with no abundance. '
            . 'Disabled if this and the Abundance Missing Message are not filled in.'
            . 'Only applies to Wildflower (with a static species grid).',
          'type'=>'textarea',
          'group'=>'Other IForm Parameters',
          'required'=>false
        ),
        array(
          'name'=>'abun_attr_id',
          'caption'=>'Abundance occurrence attribute ID',
          'description'=>'Occurrence attribute ID for abundance.'
            . 'Disabled if this and the Abundance Occurrence Attribute ID are not filled in.'
            . 'Only applies to Wildflower (with a static species grid).',
          'type'=>'textarea',
          'group'=>'Other IForm Parameters',
          'required'=>false
        ),
        array(
          'name'=>'override_indicia_perms',
          'caption'=>'Override Indicia editing permissions',
          'description'=>'This form has its own custom rules for when data is read-only, override the Indicia '
            . 'editing permissions to prevent users being prevented from opening samples created by someone else.'
            . 'Leave on if the samples grid is using npms_sample_occurrence_samples_2.xml report.',
          'type'=>'boolean',
          'default'=>true,
          'group'=>'Permissions',
          'required'=>true
        )  
      )
    ); 
  }

  /** 
   * Return the form metadata.
   * @return array The definition of the form.
   */
  public static function get_npms_sample_occurrence_definition() {
    return array(
      'title'=>'Sample-occurrence entry form for NPMS',
      'category' => 'Forms for specific surveying methods',
      'description'=>'A sample and occurrence entry form with an optional grid listing the user\'s samples so forms can be ' .
        'reloaded for editing. Can be used for entry of a single occurrence, ticking species off a checklist, or entering ' .
        'species into a grid. The attributes on the form are dynamically generated from the survey setup on the Indicia Warehouse.'.
        'With customisations for the Plant Surveillance Scheme.'
    );
  }

  /*
   * This can't be run inside get_form_html, as that code is only run after the grid page
   */
  public static function get_form($args, $nid) {
    // Change the grid params to submit as soon as a change is made without the need for a run button
    data_entry_helper::$javascript .= "
    $('#run-report').hide();
     
    $(document).ready(function($) {
      $('#samples_grid-square_id').change(function(e) {
        $('#samples_grid-params').submit();
      });  

      $('#samples_grid-year').change(function(e) {
        $('#samples_grid-params').submit();
      });
    });";
    return parent::get_form($args, $nid);
  } 

  protected static function get_form_html($args, $auth, $attributes) {
    $r='';
    data_entry_helper::$javascript .= "
      var sampleCreatedOn;
      var lockingDate;
      
      $(document).ready(function($) {
        $('#imp-sref-system').hide();
        $('#tab-submit').val('Submit');
        $('#tab-delete').click(function(){
          if (confirm('Are you sure you wish to delete this survey?')) {
            $('#tab-delete').trigger();
          } else {
            return false;
          }
        });";
    

      // If the user enters a Wildflower photo without an abundance then stop them.
      // If the user enters a genus on Inventory, Indicator photo then warn them with the option to continue
      // (to detect a genus, we simply count if it is a one word entry)
      if ((!empty($args['abun_photo_msg']) && !empty($args['abun_attr_id'])) || !empty($args['genus_entry_found_msg'])) {   
        data_entry_helper::$javascript .= "
        function WordCount(str) {
          return str.split(' ')
                .filter(function(n) { return n != '' })
                .length;
        }  

        $(document).on('click', '#tab-submit', function(e){ 
          var photoWithoutAbunFound=false;
          var unwantedGenusEntryFound=false;
          // Cycle through each row on grid
          $(\"table[id^='species-grid'] tr\").filter('.scClonableRow, .added-row').each(function() {";
            if (!empty($args['genus_entry_found_msg'])) {
              data_entry_helper::$javascript .= "
              $(this).prev().find(\".scTaxonCell\").each(function( index ) {
                // Only count items in an emphasis tag as we don't want to count the taxon group and other labels
                if (WordCount($(this).find('em').text())===1) {
                  unwantedGenusEntryFound=true;
                }
              });";
            }

            if (!empty($args['abun_photo_msg']) && !empty($args['abun_attr_id'])) {
              data_entry_helper::$javascript .= "
              //Image row is called supplementary-row in edit mode
              if ($(this).hasClass('image-row') || $(this).hasClass('supplementary-row')) {
                //Image row might not actually have any image if Add Photo button wasn't clicked
                if ($(this).find('img').length) {
                  //Current row is image row, so to get abundance we need to look in previous row and do a check for no value.
                  //As we are doing a contains selector, we need to loop even though there should only be one result
                  //Difficult to use an exact selector, as edit mode has attribute values IDs in the selector, this makes life complicated.
                  $(this).prev().find(\"input[id*='occAttr\\\\:".$args['abun_attr_id']."']\").each(function( index ) {
                    if (!$(this).val()) {
                      photoWithoutAbunFound=true;
                    }
                  });
                } 
              }";
            }
          data_entry_helper::$javascript .= "  
          });"; 

          if (!empty($args['genus_entry_found_msg'])) {
            data_entry_helper::$javascript .= "
            if (unwantedGenusEntryFound===true) {
              var txt;
              var r = confirm('".$args['genus_entry_found_msg']."');
              if (r == true) {
                // If user wants to continue anyway, them the genus is no longer considered unwanted
                unwantedGenusEntryFound=false;
              } else {
                unwantedGenusEntryFound=true;
              } 
            }";
          }
          if (!empty($args['abun_photo_msg']) && !empty($args['abun_attr_id'])) {
            data_entry_helper::$javascript .= "
            if (photoWithoutAbunFound===true) {
              alert('".$args['abun_photo_msg']."');
            }";
          }
        data_entry_helper::$javascript .= "
          if (unwantedGenusEntryFound === true || photoWithoutAbunFound === true) {
            return false;
          }
        });";
      }
      data_entry_helper::$javascript .= "  
      });";


    if (function_exists('hostsite_get_user_field')) {
      $iUserId = hostsite_get_user_field('indicia_user_id');
    }
    if (!empty($_GET['sample_id'])&&!empty($iUserId)) {
      $r .= self::form_lock_logic($args, $auth, $attributes, $iUserId);
    }
    $r .= '<div id = "disableDiv">';
    $r .= parent::get_form_html($args, $auth, $attributes);
    $r .= '</div>';
    return $r;
  }
  
  /**
   * Preparing to display an existing sample with occurrences.
   * When displaying a grid of occurrences, just load the sample and data_entry_helper::species_checklist
   * will load the occurrences.
   * When displaying just one occurrence we must load the sample and the occurrence
   */
  protected static function getEntity(&$args, $auth) {
    data_entry_helper::$entity_to_load = array();
    if ((call_user_func(array(self::$called_class, 'getGridMode'), $args))) {
      // multi-record mode using a checklist grid. We really just need to know the sample ID.
      if (self::$loadedOccurrenceId && !self::$loadedSampleId) {
        $response = data_entry_helper::get_population_data(array(
            'table' => 'occurrence',
            'extraParams' => $auth['read'] + array('id' => self::$loadedOccurrenceId, 'view' => 'detail'),
            'caching' => false,
            'sharing' => 'editing'
        ));
        if (count($response) !== 0) {
          //we found an occurrence so use it to detect the sample
          self::$loadedSampleId = $response[0]['sample_id'];
        }
      }
    } else {
      // single record entry mode. We want to load the occurrence entity and to know the sample ID.
      if (self::$loadedOccurrenceId) {
        data_entry_helper::load_existing_record(
            $auth['read'], 'occurrence', self::$loadedOccurrenceId, 'detail', 'editing', true);
        if (isset($args['multiple_occurrence_mode']) && $args['multiple_occurrence_mode'] === 'either') {
          // Loading a single record into a form that can do single or multi. Switch to multi if the sample contains
          // more than one occurrence.
          $response = data_entry_helper::get_population_data(array(
            'table' => 'occurrence',
            'extraParams' => $auth['read'] + array(
                'sample_id' => data_entry_helper::$entity_to_load['occurrence:sample_id'],
                'view' => 'detail',
                'limit' => 2
              ),
            'caching' => false,
            'sharing' => 'editing'
          ));
          if (count($response) > 1) {
            data_entry_helper::$entity_to_load['gridmode'] = true;
            // Swapping to grid mode for edit, so use species list as the grid's extra species list rather than load the
            // whole lot.
            if (!empty($args['list_id']) && empty($args['extra_list_id'])) {
              $args['extra_list_id'] = $args['list_id'];
              $args['list_id'] = '';
            }
          }
        }
      }
      elseif (self::$loadedSampleId) {
        $response = data_entry_helper::get_population_data(array(
          'table' => 'occurrence',
          'extraParams' => $auth['read'] + array('sample_id' => self::$loadedSampleId, 'view' => 'detail'),
          'caching' => false,
          'sharing' => 'editing'
        ));
        self::$loadedOccurrenceId = $response[0]['id'];
        data_entry_helper::load_existing_record_from(
            $response[0], $auth['read'], 'occurrence', self::$loadedOccurrenceId, 'detail', 'editing', true);
      }
      self::$loadedSampleId = data_entry_helper::$entity_to_load['occurrence:sample_id'];
    }

    // Load the sample record
    if (self::$loadedSampleId) {
      data_entry_helper::load_existing_record($auth['read'], 'sample', self::$loadedSampleId, 'detail', 'editing', true);
      // If there is a parent sample and we are not force loading the child sample then load it next so the details
      // overwrite the child sample.
      if (!empty(data_entry_helper::$entity_to_load['sample:parent_id']) && empty($args['never_load_parent_sample'])) {
        data_entry_helper::load_existing_record(
            $auth['read'], 'sample', data_entry_helper::$entity_to_load['sample:parent_id'], 'detail', 'editing');
        self::$loadedSampleId = data_entry_helper::$entity_to_load['sample:id'];
      }
    }
    // Ensure that if we are used to load a different survey's data, then we get the correct survey attributes. We can
    // change args because the caller passes by reference.
    $args['survey_id']=data_entry_helper::$entity_to_load['sample:survey_id'];
    $args['sample_method_id']=data_entry_helper::$entity_to_load['sample:sample_method_id'];
    // enforce that people only access their own data, unless explicitly have permissions
    $editor = !empty($args['edit_permission']) && hostsite_user_has_permission($args['edit_permission']);
    // See notes in the args code for information on override_indicia_perms
    if($editor||(isset($args['override_indicia_perms'])&&$args['override_indicia_perms']==true))
      return;
    $readOnly = !empty($args['ro_permission']) && hostsite_user_has_permission($args['ro_permission']);
    if (function_exists('hostsite_get_user_field') &&
        data_entry_helper::$entity_to_load['sample:created_by_id'] != hostsite_get_user_field('indicia_user_id')) {
      if($readOnly)
        self::$mode = self::MODE_EXISTING_RO;
      else
        throw new exception(lang::get('Attempt to access a record you did not create'));
    }
  }
  
  /*
   * Logic for setting form to read-only. This currently happens if the data was created before the custom
   * locking date, or if the data was created by another user.
   */
protected static function form_lock_logic($args, $auth, $attribute,$iUserId) {
  global $user;
  $r='';
  //No need to lock form for administrator users
  if (!(in_array('administrator', $user->roles))) {
    //Test if the sample date is less than the locking date, if it is then lock the form.
    if (!empty($args['locking_date'])) {
      $sampleData = data_entry_helper::get_population_data(array(
        'table' => 'sample',
        'extraParams' => $auth['read'] + array('id' => $_GET['sample_id'], 'view' => 'detail'),
      ));
      //The date also has a time element. However this breaks javascript new date, so just get first part of the date (remove time).
      //(the line below just gets the part of the string before the space).
      $sampleCreatedOn = strtok($sampleData[0]['created_on'],  ' ');
    }
    // Find the samples for the squares the user has rights too
    $reportOptions=array(
      'readAuth' => $auth['read'],
      'dataSource'=> 'projects/npms/npms_get_minimum_sample_details_for_viewable_samples',
      'extraParams'=>array(
        'survey_id' => $args['survey_id'],
        'person_square_attr_id' => $args['person_square_attr_id'],
        's1AttrID' => $args['survey_1_attr'],
        'iUserID' => $iUserId)     
    );
    if (!empty($args['plot_number_attr_id'])) {
      $reportOptions = array_merge($reportOptions,array('plot_number_attr_id' => $args['plot_number_attr_id']));
    }
    $userCreatedSample=false;
    $mySamples = data_entry_helper::get_report_data($reportOptions);
    // Cycle through each sample associated with the user's squares and
    // find out if current user created it
    foreach ($mySamples as $sampleData) {
      if ($sampleData['sample_id']===$_GET['sample_id']&&
        (!empty($sampleData['created_by_id'])&&$sampleData['created_by_id']===$iUserId)) {
        $userCreatedSample=true; 
      } 
    }
    // Lock form if past locking date
    // This is always the message shown if this has happened regardless if they created sample or not
    if (!empty($sampleCreatedOn) && !empty($args['locking_date']) && $sampleCreatedOn<$args['locking_date']) {
        data_entry_helper::$javascript .= "
          var lockForm=true;
        ";
      if (!empty($args['override_locking_date_text']))
        $r .= '<em style="color: red;">'.$args['override_locking_date_text'].'</em>';
      else
        $r .= '<em style="color: red;">This form can no longer be edited as it was created before the data locking date specified by the administrator.</em>';
    //Lock form if user didn't create sample
    } elseif ($userCreatedSample===false) {
      if (!empty($args['override_locking_wrong_user_text']))
        $r .= '<em style="color: red;">'.$args['override_locking_wrong_user_text'].'</em>';
      else
        $r .= '<em style="color: red;">The data on this form has been locked for editing because you are no longer assigned the square or you did not originally create the visit.</em>';
      data_entry_helper::$javascript .= "
      var lockForm=true;
      ";
    } else {  
      // Otherwise don't lock
      data_entry_helper::$javascript .= "
        var lockForm=false;
      ";  
    }
    // Lock the form controls
    data_entry_helper::$javascript .= "
      if (lockForm) {  
        $(window).load(function () {
          $('[id*=_lock]').remove();\n $('.remove-row').remove();\n
          $('.scImageLink,.scClonableRow').hide();
          $('.edit-taxon-name,.remove-row').hide();
          $('#disableDiv').find('input, textarea, text, button, select').attr('disabled','disabled');
        });
      }";
    }
    //remove default validation mode of 'message' as species grid goes wrong
    data_entry_helper::$validation_mode = array('colour');
    //Div that can be used to disable page when required
    return $r;    
  }

  /**
   * Override function to output species name for checklist
   */
  protected static function build_grid_taxon_label_function($args, $options) {
    global $indicia_templates;
    // This bit optionally adds '- common' or '- latin' depending on what was being searched
    if (isset($args['species_include_both_names']) && $args['species_include_both_names']) {
      $php = '$r = "<span class=\"scTaxon\"><em>{taxon}</em></span> <span class=\"scCommon\">{default_common_name}</span>";' . "\n";
    } else {
      $php = '$r = "<em>{taxon}</em>";' . "\n";
    }
    // this bit optionally adds the taxon group
    if (isset($args['species_include_taxon_group']) && $args['species_include_taxon_group']) {
      $php .= '$r .= "<br/><strong>{taxon_group}</strong>";' . "\n";
    }
    if (isset($options['useCommonName'])&&$options['useCommonName']==true) 
      $php = '$r = "<span class=\"scCommon\">{default_common_name}</span>";' . "\n";
    // Close the function
    $php .= 'return $r;' . "\n";
    // Set it into the indicia templates
    $indicia_templates['taxon_label'] = $php;
  }

  /**
   * Override function to include actions to add or edit the linked sample
   * Depends upon a report existing, e.g. npms_sample_occurrence_samples_2, that 
   * returns the field show_add_sample_2 where
   * show_add_sample_2 is true if there is no second sample linked to the first 
   */
  protected static function getReportActions() {
    return array(array('display' => 'Actions', 
                       'actions' => array(array('caption' => lang::get('Edit this Survey'), 
                                                'url'=>'{currentUrl}', 
                                                'urlParams' => array('sample_id' => '{sample_id}')
                                               )
    )));
  }
  
  /**
   * Override function to add the report parameter for the ID of the custom attribute which holds the linked sample.
   * Depends upon a report existing that uses the parameter e.g. npms_sample_occurrence_samples
   */
  protected static function getSampleListGrid($args, $nid, $auth, $attributes) {
    global $user;
    // User must be logged in before we can access their records.
    if ($user->uid===0) {
      // Return a login link that takes you back to this form when done.
      return lang::get('Before using this facility, please <a href="'.url('user/login', array('query'=>array('destination=node/'.$nid))).'">login</a> to the website.');
    }

    // Get the Indicia User ID to filter on.
    if (function_exists('hostsite_get_user_field')) {
      $iUserId = hostsite_get_user_field('indicia_user_id');
      if (isset($iUserId)) {
        $repOptions=array(
            'survey_id' => $args['survey_id'],
            'person_square_attr_id' => $args['person_square_attr_id'],
            's1AttrID' => $args['survey_1_attr'],
            'iUserID' => $iUserId);
        if (!empty($args['ignore_grid_sample_dates_before'])) 
          $repOptions=array_merge($repOptions,array('ignore_dates_before'=>$args['ignore_grid_sample_dates_before']));
        $filter = $repOptions;
      }
    }
    if (!empty($filter) && !empty($args['plot_number_attr_id'])) {
      $filter = array_merge($filter,array('plot_number_attr_id' => $args['plot_number_attr_id']));
    }
    
    $filter = array_merge($filter,array('year_termlist_id' => $args['npms_years_termlist_id']));
    $filter = array_merge($filter,array('core_square_location_type_id' => $args['core_square_location_type_id']));
    // Return with error message if we cannot identify the user records
    if (!isset($filter)) {
      return lang::get('LANG_No_User_Id');
    }

    // An option for derived classes to add in extra html before the grid
    if(method_exists(self::$called_class, 'getSampleListGridPreamble'))
      $r = call_user_func(array(self::$called_class, 'getSampleListGridPreamble'));
    else
      $r = '';

    $r .= data_entry_helper::report_grid(array(
      'id' => 'samples-grid',
      'dataSource' => $args['grid_report'],
      'mode' => 'report',
      'readAuth' => $auth['read'],
      'columns' => call_user_func(array(self::$called_class, 'getReportActions')),
      'itemsPerPage' =>(isset($args['grid_num_rows']) ? $args['grid_num_rows'] : 10),
      'autoParamsForm' => true,
      'paramDefaults' => array('square_id' => '0', 'year' => ''),
      'extraParams' => $filter
    ));
    $r .= '<form>';
    if (isset($args['multiple_occurrence_mode']) && $args['multiple_occurrence_mode']=='either') {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Single').'" onclick="window.location.href=\''.url('node/'.$nid, array('query' => array('new'))).'\'">';
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Grid').'" onclick="window.location.href=\''.url('node/'.$nid, array('query' => array('new&gridmode'))).'\'">';
    } else {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample').'" onclick="window.location.href=\''.url('node/'.$nid, array('query' => array('new'=>''))).'\'">';
    }
    $r .= '</form>';
    return $r;
  }
  
  /* Overrides function in class iform_dynamic.
   * 
   * This function removes ID information from the entity_to_load, fooling the 
   * system in to building a form for a new record with default values from the entity_to_load.
   * Note that for NPMS occurrences are loaded for survey 2, however we don't load the occurrence attributes.
   */
  protected static function cloneEntity($args, $auth, &$attributes) {
    // First modify the sample attribute information in the $attributes array.
    // Set the sample attribute fieldnames as for a new record
    foreach($attributes as $attributeKey => $attributeValue){
      if ($attributeValue['multi_value'] == 't') {
         // Set the attribute fieldname to the attribute id plus brackets for multi-value attributes
        $attributes[$attributeKey]['fieldname'] = $attributeValue['id'] . '[]';
        foreach($attributeValue['default'] as $defaultKey => $defaultValue) {
          $attributes[$attributeKey]['default'][$defaultKey]['fieldname']=null;   
        }
      } else {
        // Set the attribute fieldname to the attribute id for single values
        $attributes[$attributeKey]['fieldname'] = $attributeValue['id'];
      }
    }
    // Now load the occurrences and their attributes.
    // @todo: Convert to occurrences media capabilities.
    $loadImages = $args['occurrence_images'];
    $subSamples = array();
    self::preload_species_checklist_occurrences(data_entry_helper::$entity_to_load['sample:id'], 
              $auth['read'], $loadImages, array(), $subSamples, false);

    // If using a species grid $entity_to_load will now contain elements in the form
    //  sc:row_num:occ_id:occurrence:field_name
    //  sc:row_num:occ_id:present
    //  sc:row_num:occ_id:occAttr:occAttr_id:attrValue_id
    // We are going to strip out the occ_id and the attrValue_id
    $keysToDelete = array();
    $elementsToAdd = array();
    foreach(data_entry_helper::$entity_to_load as $key => $value) {
      $parts = explode(':', $key);
      // Is this an occurrence?
      if ($parts[0] === 'sc') {
        // We'll be deleting this
        $keysToDelete[] = $key;
        // And replacing it
        $parts[2] = '';
        if (count($parts) == 6) unset($parts[5]);
        $keyToCreate = implode(':', $parts);
        $elementsToAdd[$keyToCreate] = $value;
      }
      //Remove any sample pictures, as we don't want these preloaded
      if ($parts[0] === 'sample_medium') {
        // We'll be deleting this
        $keysToDelete[] = $key;
      }
    }
    //Don't clone the date as the date on survey 2 will always be different
    $keysToDelete[]='sample:date_start';
    $keysToDelete[]='sample:date_end';
    $keysToDelete[]='sample:date_type';
    $keysToDelete[]='sample:date';
    $keysToDelete[]='sample:display_date';
    foreach($keysToDelete as $key) {
      unset(data_entry_helper::$entity_to_load[$key]);
    }
    data_entry_helper::$entity_to_load = array_merge(data_entry_helper::$entity_to_load, $elementsToAdd);
    
    // Unset the sample and occurrence id from entitiy_to_load as for a new record.
    if (isset(data_entry_helper::$entity_to_load['sample:id']))
      unset(data_entry_helper::$entity_to_load['sample:id']);
    if (isset(data_entry_helper::$entity_to_load['occurrence:id']))
      unset(data_entry_helper::$entity_to_load['occurrence:id']);   
  }
  
  /**
   * Override preload_species_checklist_occurrences so we remove elements that would cause occurrence
   * attributes to be loaded into survey 2.
   */
  public static function preload_species_checklist_occurrences($sampleId, $readAuth, $loadMedia, $extraParams, &$subSamples, $useSubSamples, $subSampleMethodID='') {
    $occurrenceIds = array();
    $taxonCounter = array();
    // don't load from the db if there are validation errors, since the $_POST will already contain all the
    // data we need.
    if (is_null(data_entry_helper::$validation_errors)) {
      // strip out any occurrences we've already loaded into the entity_to_load, in case there are other
      // checklist grids on the same page. Otherwise we'd double up the record data.
      foreach(data_entry_helper::$entity_to_load as $key => $value) {
        $parts = explode(':', $key);
        if (count($parts) > 2 && $parts[0] == 'sc' && $parts[1]!='-idx-') {
          unset(data_entry_helper::$entity_to_load[$key]);
        }
      }
      $extraParams += $readAuth + array('view'=>'detail','sample_id'=>$sampleId,'deleted'=>'f', 'orderby'=>'id', 'sortdir'=>'ASC' );
      $sampleCount = 1;

      if($sampleCount>0) {
        $occurrences = data_entry_helper::get_population_data(array(
          'table' => 'occurrence',
          'extraParams' => $extraParams,
          'nocache' => true
        ));
        foreach($occurrences as $idx => $occurrence){
          if($useSubSamples){
            foreach($subSamples as $sidx => $subsample){
              if($subsample['id'] == $occurrence['sample_id'])
                data_entry_helper::$entity_to_load['sc:'.$idx.':'.$occurrence['id'].':occurrence:sampleIDX'] = $sidx;
            }
          }
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$occurrence['id'].':present'] = $occurrence['taxa_taxon_list_id'];
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$occurrence['id'].':record_status'] = $occurrence['record_status'];
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$occurrence['id'].':occurrence:comment'] = $occurrence['comment'];
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$occurrence['id'].':occurrence:sensitivity_precision'] = $occurrence['sensitivity_precision'];
          // Warning. I observe that, in cases where more than one occurrence is loaded, the following entries in 
          // $entity_to_load will just take the value of the last loaded occurrence.
          data_entry_helper::$entity_to_load['occurrence:record_status']=$occurrence['record_status'];
          data_entry_helper::$entity_to_load['occurrence:taxa_taxon_list_id']=$occurrence['taxa_taxon_list_id'];
          data_entry_helper::$entity_to_load['occurrence:taxa_taxon_list_id:taxon']=$occurrence['taxon'];
          // Keep a list of all Ids
          $occurrenceIds[$occurrence['id']] = $idx;
        }
      }
    }
    return $occurrenceIds;
  }

  /*
   * At the submission point we need to insert values to automatically tell the system if this
   * is going to be a survey 1 or survey 2
   */
  public static function get_submission($values, $args, $nid) {
    $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
    $surveyOneFieldName = 'smpAttr:' . $args['survey_1_attr'];
    $selectedPlotId=$values['sample:location_id'];
    // Need to find out how many samples have been created for this plot so far this year
    $reportOptions =
    array(
      'readAuth' => $readAuth,
      'dataSource'=> $args['grid_report'],
      'extraParams'=> array(
        // Effectively ignore square in this situation
        'square_id' => 0,
        'plot_id' => $selectedPlotId,
        // Need to check based on the year of the sample, not the year today
        'year' => substr($values['sample:date'], -4),
        'year_termlist_id' => $args['npms_years_termlist_id'],
        'survey_id' => $args['survey_id'],
        'iUserID' =>  hostsite_get_user_field('indicia_user_id'),
        'person_square_attr_id' => $args['person_square_attr_id'],
        'core_square_location_type_id' => $args['core_square_location_type_id'],
        's1AttrID' => $args['survey_1_attr'])
    );
    $samplesForThisPlotThisYear=data_entry_helper::get_report_data($reportOptions);
    // If no samples have been created so far this year and it is a new sample (i.e. not editing)
    // then we know we need to create this as a Survey 1.
    // (noting we store an ID of 0 as the linking sample ID if it is survey 1)
    if (count($samplesForThisPlotThisYear)==0 && empty($values['sample:id'])) {
      $values[$surveyOneFieldName]='0';
    // If only 1 sample for the plot already exists this year, then need to copy the ID of that sample into 
    // the submission so it can be linked to this new sample. The new sample becomes sample 2 that is linked
    // to 1 through an attribute 
    } elseif (count($samplesForThisPlotThisYear)==1 && empty($values['sample:id'])) {
      $values[$surveyOneFieldName] = $samplesForThisPlotThisYear[0]['sample_id'];
    }
    return parent::get_submission($values, $args, $nid);
  }
}

?>
