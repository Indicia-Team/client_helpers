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
          'name'=>'locking_date',
          'caption'=>'Locking Date',
          'description'=>'The date to lock the form from. Samples "created on" earlier than this date are read-only (use format yyyy-mm-dd)',
          'type'=>'string',
          'group'=>'Locking Date',
          'required'=>false
        ),
        array(
          'name'=>'override_locking_date_text',
          'caption'=>'Override locked form text',
          'description'=>'Override the warning text shown to the user when the form is locked from editing.',
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
          'group'=>'Other IForm Parameters',
          'required'=>false
        ),
        array(
          'name'=>'abun_photo_msg',
          'caption'=>'Abundance missing message',
          'description'=>'Message to display to the user '
            . 'if they have added a photo with no abundance. '
            . 'Disabled if this and the Abundance Missing Message are not filled in.',
          'type'=>'textarea',
          'group'=>'Other IForm Parameters',
          'required'=>false
        ),
        array(
          'name'=>'abun_attr_id',
          'caption'=>'Abundance occurrence attribute ID',
          'description'=>'Occurrence attribute ID for abundance.'
            . 'Disabled if this and the Abundance Occurrence Attribute ID are not filled in.',
          'type'=>'textarea',
          'group'=>'Other IForm Parameters',
          'required'=>false
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

  protected static function get_form_html($args, $auth, $attributes) {
    global $user;
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
    if (!empty($args['abun_photo_msg']) && !empty($args['abun_attr_id'])) {
      //If the user enters a photo without an abundance warn them.
      data_entry_helper::$javascript .= "
        $('#tab-submit').click(function(e) {
          var photoWithoutAbunFound=false;
          var rowCounter=-1;
          $(\"table[id^='species-grid'] tr\").each(function() {
            if ($(this).hasClass('image-row')) {
              //Image row might not actually have any image if Add Photo button wasn't clicked
              if ($(this).find('img').length) {
                //Current row is image row, so to get abundance we need to look in previous row and do a no value check
                if (!$(this).prev().find('#sc\\\\:'+$(this).parents('table').attr('id')+'-'+rowCounter+'\\\\:\\\\:occAttr\\\\:'+'".$args['abun_attr_id']."').val()) {
                  photoWithoutAbunFound=true;
                }
              } 
            } else {
              //If row isn't an image row, then it is a real grid row that we need to keep track of
              rowCounter++;
            }
          });
          if (photoWithoutAbunFound===true) {
            alert('".$args['abun_photo_msg']."') 
            e.preventDefault();
          }
        });";  
    }
      data_entry_helper::$javascript .= "
      });
    ";
    //Test if the sample date is less than the locking date, if it is then lock the form.
    if (!empty($_GET['sample_id'])&&!empty($args['locking_date']) && !(in_array('administrator', $user->roles))) {
      $sampleData = data_entry_helper::get_population_data(array(
        'table' => 'sample',
        'extraParams' => $auth['read'] + array('id' => $_GET['sample_id'], 'view' => 'detail'),
      ));
      //The date also has a time element. However this breaks javascript new date, so just get first part of the date (remove time).
      //(the line below just gets the part of the string before the space).
      $sampleCreatedOn = strtok($sampleData[0]['created_on'],  ' ');
      if (!empty($sampleCreatedOn)) {
        data_entry_helper::$javascript .= "
          sampleCreatedOn = new Date('".$sampleCreatedOn."');
          lockingDate = new Date('".$args['locking_date']."');
        ";
      }
    }
    $r='';
    if (!empty($sampleCreatedOn) && !empty($args['locking_date']) && $sampleCreatedOn<$args['locking_date']) {
      if (!empty($args['override_locking_date_text']))
        $r .= '<em style="color: red;">'.$args['override_locking_date_text'].'</em>';
      else
        $r .= '<em style="color: red;">This form can no longer be edited as it was created before the data locking date specified by the administrator.</em>';
    }
    //If the date the sample was created is less than the threshold date set by the user, then
    //lock the form (put simply, old data cannot be edited by the user).
    data_entry_helper::$javascript .= "
      if (sampleCreatedOn&&lockingDate&&sampleCreatedOn<lockingDate) {  
        $(window).load(function () {
          $('[id*=_lock]').remove();\n $('.remove-row').remove();\n
          $('.scImageLink,.scClonableRow').hide();
          $('.edit-taxon-name,.remove-row').hide();
          $('#disableDiv').find('input, textarea, text, button, select').attr('disabled','disabled');
        });
      }";
    //remove default validation mode of 'message' as species grid goes spazzy
    data_entry_helper::$validation_mode = array('colour');
    //Div that can be used to disable page when required
    $r .= '<div id = "disableDiv">';
    $r .= parent::get_form_html($args, $auth, $attributes);
    $r .= '</div>';
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
   * Override function to add hidden attribute to store linked sample id
   * When adding a survey 1 record this is given the value 0
   * When adding a survey 2 record this is given the sample_id of the corresponding survey 1 record.
   * @param type $args
   * @param type $auth
   * @param type $attributes
   * @return string The hidden inputs that are added to the start of the form
   */
  protected static function getFirstTabAdditionalContent($args, $auth, &$attributes) {
    $r = parent::getFirstTabAdditionalContent($args, $auth, $attributes);    
    $linkAttr = 'smpAttr:' . $args['survey_1_attr'];
    if (array_key_exists('new', $_GET)) {
      if (array_key_exists('sample_id', $_GET)) {
        // Adding a survey 2 record
        $r .= '<input id="' . $linkAttr. '" type="hidden" name="' . $linkAttr. '" value="' . $_GET['sample_id'] . '"/>' . PHP_EOL;
      } else {
        // Adding a survey 1 record
        $r .= '<input id="' . $linkAttr. '" type="hidden" name="' . $linkAttr. '" value="0"/>' . PHP_EOL;
      }
    }
    return $r;
  }

  /**
   * Override function to include actions to add or edit the linked sample
   * Depends upon a report existing, e.g. npms_sample_occurrence_samples, that 
   * returns the fields done1 and done2 where
   * done1 is true if there is no second sample linked to the first and
   * done2 is true when there is a second sample.
   */
  protected static function getReportActions() {
    return array(array('display' => 'Actions', 
                       'actions' => array(array('caption' => lang::get('Edit Survey 1'), 
                                                'url'=>'{currentUrl}', 
                                                'urlParams' => array('edit' => '', 'sample_id' => '{sample_id1}')
                                               ),
                                          array('caption' => lang::get('Add Survey 2'), 
                                                'url'=>'{currentUrl}', 
                                                'urlParams' => array('new' => '', 'sample_id' => '{sample_id1}'),
                                                'visibility_field' => 'done1'
                                               ),
                                          array('caption' => lang::get('Edit Survey 2'), 
                                                'url'=>'{currentUrl}', 
                                                'urlParams' => array('edit' => '', 'sample_id' => '{sample_id2}'),
                                                'visibility_field' => 'done2'
                                               ),
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
}

?>
