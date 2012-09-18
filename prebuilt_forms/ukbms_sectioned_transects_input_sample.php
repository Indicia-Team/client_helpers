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
require_once 'includes/form_generation.php';


// TODO
// Convert List creation on subsidiary grids to look up taxon details for the taxons we have rather than driving from list.
// Add species auto completes to bottom of grids
// When auto complete selected, new row added.
// Add check to prevent duplicate rows
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
  watchdog('compare', "$aCode = $bCode - ".((int)$aCode < (int)$bCode ? '-1' : '1'));
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
   * @todo: Implement this method
   */
  public static function get_parameters() {
    return array_merge(
      array(
        array(
          'name'=>'survey_id',
          'caption'=>'Survey',
          'description'=>'The survey that data will be posted into.',
          'type'=>'select',
          'table'=>'survey',
          'captionField'=>'title',
          'valueField'=>'id',
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
          'siteSpecific'=>true
        ),
        array(
          'name'=>'taxon_list_id',
          'caption'=>'All Species List',
          'description'=>'The species checklist used to populate the grid on the main grid when All Species is selected. Also used to drive the autocomplete when other options selected.',
          'type'=>'select',
          'table'=>'taxon_list',
          'captionField'=>'title',
          'valueField'=>'id',
          'siteSpecific'=>true,
          'group'=>'Species'
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
          'name'=>'second_taxon_list_id',
          'caption'=>'Second Tab Species List',
          'description'=>'The species checklist used to drive the autocomplete in the optional second grid. If not provided, the second grid and its tab are omitted.',
          'type'=>'select',
          'table'=>'taxon_list',
          'captionField'=>'title',
          'valueField'=>'id',
          'required'=>false,
          'siteSpecific'=>true,
          'group'=>'Species'
        ),
        array(
          'name'=>'second_taxon_filter_field',
          'caption'=>'Second Tab Species List: Field used to filter taxa',
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
          'name'=>'second_taxon_filter',
          'caption'=>'Second Tab Species List: Taxon filter items',
          'description'=>'When filtering the list of available taxa, taxa will not be available for recording unless they match one of the '.
              'values you input in this box. Enter one value per line. E.g. enter a list of taxon group titles if you are filtering by taxon group.',
          'type' => 'textarea',
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species'
        ),
        array(
          'name'=>'third_taxon_list_id',
          'caption'=>'Third Tab Species List',
          'description'=>'The species checklist used to drive the autocomplete in the optional third grid. If not provided, the third grid and its tab are omitted.',
          'type'=>'select',
          'table'=>'taxon_list',
          'captionField'=>'title',
          'valueField'=>'id',
          'required'=>'false',
          'siteSpecific'=>true,
          'group'=>'Species'
        ),
        array(
          'name'=>'third_taxon_filter_field',
          'caption'=>'Third Tab Species List: Field used to filter taxa',
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
          'name'=>'third_taxon_filter',
          'caption'=>'Third Tab Species List: Taxon filter items',
          'description'=>'When filtering the list of available taxa, taxa will not be available for recording unless they match one of the '.
              'values you input in this box. Enter one value per line. E.g. enter a list of taxon group titles if you are filtering by taxon group.',
          'type' => 'textarea',
          'siteSpecific'=>true,
          'required'=>false,
          'group'=>'Species'
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
          'description'=>'Path used to access the My Walks page after a successful submission.',
          'type'=>'text_input',
          'required'=>true,
          'siteSpecific'=>true
        ),
      )
    );
  }

  /**
   * Return the generated form output.
   * @param array $args List of parameter values passed through to the form depending on how the form has been configured.
   * This array always contains a value for language.
   * @param object $node The Drupal node object.
   * @param array $response When this form is reloading after saving a submission, contains the response from the service call.
   * Note this does not apply when redirecting (in this case the details of the saved object are in the $_GET data).
   * @return Form HTML.
   * @todo: Implement this method
   */
  public static function get_form($args, $node, $response=null) {
    if (isset($response['error']))
      data_entry_helper::dump_errors($response);
    if (isset($_REQUEST['page']) && $_REQUEST['page']==='transect' && !isset(data_entry_helper::$validation_errors)) {
      // we have just saved the sample page, so move on to the occurrences list
      return self::get_occurrences_form($args, $node, $response);
    } else {
      return self::get_sample_form($args, $node, $response);
    }
  }

  public static function get_sample_form($args, $node, $response) {
    global $user;
    if (!module_exists('iform_ajaxproxy'))
      return 'This form must be used in Drupal with the Indicia AJAX Proxy module enabled.';
    iform_load_helpers(array('map_helper'));
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $sampleId = isset($_GET['sample_id']) ? $_GET['sample_id'] : null;
    if ($sampleId) {
      data_entry_helper::load_existing_record($auth['read'], 'sample', $sampleId);
      $locationId = data_entry_helper::$entity_to_load['sample:location_id'];
    } else {
      $locationId = isset($_GET['site']) ? $_GET['site'] : null;
      // location ID also might be in the $_POST data after a validation save of a new record
      if (!$locationId && isset($_POST['sample:location_id']))
        $locationId = $_POST['sample:location_id'];

    }
    $r .= '<form method="post" id="sample">';
    $r .= $auth['write'];
    // we pass through the read auth. This makes it possible for the get_submission method to authorise against the warehouse
    // without an additional (expensive) warehouse call, so it can get location details.
    $r .= '<input type="hidden" name="read_nonce" value="'.$auth['read']['nonce'].'"/>';
    $r .= '<input type="hidden" name="read_auth_token" value="'.$auth['read']['auth_token'].'"/>';
    $r .= '<input type="hidden" name="website_id" value="'.$args['website_id'].'"/>';
    if (isset(data_entry_helper::$entity_to_load['sample:id'])) {
      $r .= '<input type="hidden" name="sample:id" value="'.data_entry_helper::$entity_to_load['sample:id'].'"/>';
    }
    $r .= '<input type="hidden" name="sample:survey_id" value="'.$args['survey_id'].'"/>';
    // pass a param that sets the next page to display
    $r .= '<input type="hidden" name="page" value="transect"/>';
    if ($locationId) {
      $site = data_entry_helper::get_population_data(array(
        'table' => 'location',
        'extraParams' => $auth['read'] + array('view'=>'detail','id'=>$locationId,'deleted'=>'f')
      ));
      $site = $site[0];
      $r .= '<input type="hidden" name="sample:location_id" value="'.$locationId.'"/>';
      $r .= '<input type="hidden" name="sample:entered_sref" value="'.$site['centroid_sref'].'"/>';
      $r .= '<input type="hidden" name="sample:entered_sref_system" value="'.$site['centroid_sref_system'].'"/>';
    }
    if ($locationId && (isset(data_entry_helper::$entity_to_load['sample:id']) || isset($_GET['site']))) {
      // for reload of existing or the the site is specified in the URL, don't let the user switch the transect as that would mess everything up.
      $r .= '<label>'.lang::get('Transect').':</label> <span class="value-label">'.$site['name'].'</span><br/>';
    } else {
      // Output only the locations for this website and transect type. Note we load both transects and sections, just so that
      // we always use the same warehouse call and therefore it uses the cache.
      $locationTypes = helper_base::get_termlist_terms($auth, 'indicia:location_types', array('Transect', 'Transect Section'));
      $availableSites = data_entry_helper::get_population_data(array(
        'report'=>'library/locations/locations_list',
        'extraParams' => $auth['read'] + array('website_id' => $args['website_id'], 'location_type_id'=>$locationTypes[0]['id'],
            'locattrs'=>'CMS User ID', 'attr_location_cms_user_id'=>$user->uid),
        'nocache' => true
      ));
      // convert the report data to an array for the lookup, plus one to pass to the JS so it can keep the hidden sref fields updated
      $sitesLookup = array();
      $sitesJs = array();
      foreach ($availableSites as $site) {
        $sitesLookup[$site['location_id']]=$site['name'];
        $sitesJs[$site['location_id']] = $site;
      }
      data_entry_helper::$javascript .= "indiciaData.sites = ".json_encode($sitesJs).";\n";
      $options = array(
        'label' => lang::get('Select Transect'),
        'validation' => array('required'),
        'blankText'=>lang::get('please select'),
        'lookupValues' => $sitesLookup,
      );
      if ($locationId)
        $options['default'] = $locationId;
      $r .= data_entry_helper::location_select($options);
    }
    if (!$locationId) {
      $r .= '<input type="hidden" name="sample:entered_sref" value="" id="entered_sref"/>';
      $r .= '<input type="hidden" name="sample:entered_sref_system" value="" id="entered_sref_system"/>';
      // sref values for the sample will be populated automatically when the submission is built.
    }
    $sampleMethods = helper_base::get_termlist_terms($auth, 'indicia:sample_methods', array('Transect'));
    $attributes = data_entry_helper::getAttributes(array(
      'id' => $sampleId,
      'valuetable'=>'sample_attribute_value',
      'attrtable'=>'sample_attribute',
      'key'=>'sample_id',
      'fieldprefix'=>'smpAttr',
      'extraParams'=>$auth['read'],
      'survey_id'=>$args['survey_id'],
      'sample_method_id'=>$sampleMethods[0]['id']
    ));
    $r .= get_user_profile_hidden_inputs($attributes, $args, '', $auth['read']);
    if(isset($_GET['date'])){
      $r .= '<input type="hidden" name="sample:date" value="'.$_GET['date'].'"/>';
      $r .= '<label>'.lang::get('Date').':</label> <span class="value-label">'.$_GET['date'].'</span><br/>';
    } else {
      if (isset(data_entry_helper::$entity_to_load['sample:date']) && preg_match('/^(\d{4})/', data_entry_helper::$entity_to_load['sample:date'])) {
        // Date has 4 digit year first (ISO style) - convert date to expected output format
        // @todo The date format should be a global configurable option. It should also be applied to reloading of custom date attributes.
        $d = new DateTime(data_entry_helper::$entity_to_load['sample:date']);
        data_entry_helper::$entity_to_load['sample:date'] = $d->format('d/m/Y');
      }
      $r .= data_entry_helper::date_picker(array(
        'label' => lang::get('Date'),
        'fieldname' => 'sample:date',
      ));
    }
    // are there any option overrides for the custom attributes?
    if (isset($args['custom_attribute_options']) && $args['custom_attribute_options']) 
      $blockOptions = get_attr_options_array_with_user_data($args['custom_attribute_options']);
    else 
      $blockOptions=array();
    $r .= get_attribute_html($attributes, $args, array('extraParams'=>$auth['read']), null, $blockOptions);
    $r .= '<input type="hidden" name="sample:sample_method_id" value="'.$sampleMethods[0]['id'].'" />';
    $r .= '<input type="submit" value="'.lang::get('Next').'" />';
    $r .= '<a href="'.$args['my_walks_page'].'"><button type="button" class="ui-state-default ui-corner-all" />'.lang::get('Cancel').'</button></a>';
    if (isset(data_entry_helper::$entity_to_load['sample:id']))
      $r .= '<button id="delete-button" type="button" class="ui-state-default ui-corner-all" />'.lang::get('Delete').'</button>';
    $r .= '</form>';
    if (isset(data_entry_helper::$entity_to_load['sample:id'])){
      // allow deletes if sample id is present.
      data_entry_helper::$javascript .= "jQuery('#delete-button').click(function(){
  if(confirm(\"".lang::get('Are you sure you want to delete this walk?')."\")){
    jQuery('#delete-form').submit();
  } // else do nothing.
});\n";
      // note we only require bare minimum in order to flag a sample as deleted.
      $r .= '<form method="post" id="delete-form" style="display: none;">';
      $r .= $auth['write'];
      $r .= '<input type="hidden" name="page" value="delete"/>';
      $r .= '<input type="hidden" name="website_id" value="'.$args['website_id'].'"/>';
      $r .= '<input type="hidden" name="sample:id" value="'.data_entry_helper::$entity_to_load['sample:id'].'"/>';
      $r .= '<input type="hidden" name="sample:deleted" value="t"/>';
      $r .= '</form>';
    }
    data_entry_helper::enable_validation('sample');
    return $r;
  }

  public static function get_occurrences_form($args, $node, $response) {
    global $user;
  	if (!module_exists('iform_ajaxproxy'))
      return 'This form must be used in Drupal with the Indicia AJAX Proxy module enabled.';
    data_entry_helper::add_resource('jquery_form');
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    // did the parent sample previously exist? Default is no.
    $existing=false;
    if (isset($_POST['sample:id'])) {
      // have just posted an edit to the existing parent sample, so can use it to get the parent location id.
      $parentSampleId = $_POST['sample:id'];
      $parentLocId = $_POST['sample:location_id'];
      $date = $_POST['sample:date'];
      $existing=true;
      data_entry_helper::load_existing_record($auth['read'], 'sample', $parentSampleId);
    } else {
      if (isset($response['outer_id']))
        // have just posted a new parent sample, so can use it to get the parent location id.
        $parentSampleId = $response['outer_id'];
      else {
        $parentSampleId = $_GET['sample_id'];
        $existing=true;
      }
      $sample = data_entry_helper::get_population_data(array(
        'table' => 'sample',
        'extraParams' => $auth['read'] + array('view'=>'detail','id'=>$parentSampleId,'deleted'=>'f')
      ));
      $sample=$sample[0];
      $parentLocId = $sample['location_id'];
      $date=$sample['date_start'];
    }
    $sampleMethods = helper_base::get_termlist_terms($auth, 'indicia:sample_methods', array('Transect'));
    $attributes = data_entry_helper::getAttributes(array(
      'valuetable'=>'sample_attribute_value',
      'attrtable'=>'sample_attribute',
      'key'=>'sample_id',
      'fieldprefix'=>'smpAttr',
      'extraParams'=>$auth['read'],
      'survey_id'=>$args['survey_id'],
      'sample_method_id'=>$sampleMethods[0]['id']
    ));
    if (false== ($cmsUserAttr = extract_cms_user_attr($attributes)))
      return 'This form is designed to be used with the CMS User ID attribute setup for samples in the survey.';
    // find any attributes that apply to transect section samples.
    $sampleMethods = helper_base::get_termlist_terms($auth, 'indicia:sample_methods', array('Transect Section'));
    $attributes = data_entry_helper::getAttributes(array(
      'id' => $sampleId,
      'valuetable'=>'sample_attribute_value',
      'attrtable'=>'sample_attribute',
      'key'=>'sample_id',
      'fieldprefix'=>'smpAttr',
      'extraParams'=>$auth['read'],
      'survey_id'=>$args['survey_id'],
      'sample_method_id'=>$sampleMethods[0]['id'],
      'multiValue'=>false // ensures that array_keys are the list of attribute IDs.
    ));
    //  the parent sample and sub-samples have already been created: can't cache in case a new section added.
    $subSamples = data_entry_helper::get_population_data(array(
      'report' => 'library/samples/samples_list_for_parent_sample',
      'extraParams' => $auth['read'] + array('sample_id'=>$parentSampleId,'date_from'=>'','date_to'=>'', 'sample_method_id'=>'', 'smpattrs'=>implode(',', array_keys($attributes))),
      'nocache'=>true
    ));
    // transcribe the response array into a couple of forms that are useful elsewhere - one for outputting JSON so the JS knows about
    // the samples, and another for lookup of sample data by code later.
    $subSampleJson = array();
    $subSamplesByCode = array();
    foreach ($subSamples as $subSample) {
      $subSampleJson[] = '"'.$subSample['code'].'": '.$subSample['sample_id'];
      $subSamplesByCode[$subSample['code']] = $subSample;
    }
    data_entry_helper::$javascript .= "indiciaData.samples = { ".implode(', ', $subSampleJson)."};\n";
    if ($existing) {
      // Only need to load the occurrences for a pre-existing sample
      $o = data_entry_helper::get_population_data(array(
        'report' => 'library/occurrences/occurrences_list_for_parent_sample',
        'extraParams' => $auth['read'] + array('view'=>'detail','sample_id'=>$parentSampleId,'survey_id'=>'','date_from'=>'','date_to'=>'','taxon_group_id'=>'',
            'smpattrs'=>'', 'occattrs'=>$args['occurrence_attribute_id']),
        // don't cache as this is live data
        'nocache' => true
      ));
      // build an array keyed for easy lookup
      $occurrences = array();
      foreach($o as $occurrence) {
        $occurrences[$occurrence['sample_id'].':'.$occurrence['taxa_taxon_list_id']] = array(
          'ttl_id'=>$occurrence['taxa_taxon_list_id'],
          'value'=>$occurrence['attr_occurrence_'.$args['occurrence_attribute_id']],
          'o_id'=>$occurrence['occurrence_id'],
          'a_id'=>$occurrence['attr_id_occurrence_'.$args['occurrence_attribute_id']],
          'processed'=>false
        );
      }
      // store it in data for JS to read when populating the grid
      data_entry_helper::$javascript .= "indiciaData.existingOccurrences = ".json_encode($occurrences).";\n";
    } else {
      data_entry_helper::$javascript .= "indiciaData.existingOccurrences = {};\n";
    }
    $sections = data_entry_helper::get_population_data(array(
      'table' => 'location',
      'extraParams' => $auth['read'] + array('view'=>'detail','parent_id'=>$parentLocId,'deleted'=>'f'),
      'nocache' => true
    ));
    usort($sections, "ukbms_stis_sectionSort");
    $location = data_entry_helper::get_population_data(array(
      'table' => 'location',
      'extraParams' => $auth['read'] + array('view'=>'detail','id'=>$parentLocId)
    ));
    $r = "<h2>".$location[0]['name']." on ".$date."</h2><div id=\"tabs\">\n";
    $tabs = array('#grid1'=>lang::get('Enter Transect Species Data 1'));
    if(isset($args['second_taxon_list_id']) && $args['second_taxon_list_id']!='')
      $tabs['#grid2']=lang::get('Enter Transect Species Data 2');
    if(isset($args['third_taxon_list_id']) && $args['third_taxon_list_id']!='')
      $tabs['#grid3']=lang::get('Enter Transect Species Data 3');
    $tabs['#notes']=lang::get('Notes');
    $r .= data_entry_helper::tab_header(array('tabs'=>$tabs));
    data_entry_helper::enable_tabs(array(
        'divId'=>'tabs',
        'style'=>$args['interface']
    ));
    $r .= "<div id=\"grid1\">\n";
    $r .= '<table id="transect-input1" class="ui-widget"><thead>';
    $r .= '<tr><th class="ui-widget-header">' . lang::get('Sections') . '</th>';
    foreach ($sections as $idx=>$section) {
      $r .= '<th class="ui-widget-header col-'.($idx+1).'">' . $section['code'] . '</th>';
    }
    $r .= '<th class="ui-widget-header">' . lang::get('Total') . '</th>';
    $r .= '</tr></thead>';
    $r .= '<tbody class="ui-widget-content">';
    // output rows at the top for any transect section level sample attributes
    $rowClass='';
    foreach ($attributes as $attr) {
      $r .= '<tr '.$rowClass.'><td>'.$attr['caption'].'</td>';
      $rowClass=$rowClass=='' ? 'class="alt-row"':'';
      unset($attr['caption']);
      foreach ($sections as $idx=>$section) {
        // output a cell with the attribute - tag it with a class & id to make it easy to find from JS.
        $attrOpts = array(
            'class' => 'smp-input smpAttr-'.$section['code'],
            'id' => $attr['fieldname'].':'.$section['code'],
            'extraParams'=>$auth['read']
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
        $r .= '<td class="col-'.($idx+1).'">' . data_entry_helper::outputAttribute($attr, $attrOpts) . '</td>';
      }
      $r .= '<td class="ui-state-disabled"></td>';
      $r .= '</tr>';
    }
    $r .= '</tbody>';
    $r .= '<tbody class="ui-widget-content occs-body"></tbody>';
    $r .= '<tfoot><tr><td>Total</td>';
    foreach ($sections as $idx=>$section) {
      $r .= '<td class="col-'.($idx+1).' col-total"></td>';
    }
    $r .= '<td class="ui-state-disabled"></td></tr></tfoot>';
    $r .= '</table>'.
          '<label for="listSelect">'.lang::get('Use species list').' :</label><select id="listSelect"><option value="full">'.lang::get('All species').'</option><option value="common">'.lang::get('Common species').'</option><option value="here">'.lang::get('Species known at this site').'</option><option value="mine">'.lang::get('Species I have recorded').'</option></select>';
    $r .= '<span id="listSelectMsg"></span><br /><span id="taxonLookupControlContainer"><label for="taxonLookupControl" class="auto-width">'.lang::get('Add species to list').':</label> <input id="taxonLookupControl" name="taxonLookupControl" ></span></div>';

    if(isset($args['second_taxon_list_id']) && $args['second_taxon_list_id']!=''){
      $r .= '<div id="grid2"><p>' . lang::get('LANG_Tab_Msg') . '</p><table id="transect-input2" class="ui-widget"><thead>';
      $r .= '<tr><th class="ui-widget-header">' . lang::get('Sections') . '</th>';
      foreach ($sections as $idx=>$section) {
        $r .= '<th class="ui-widget-header col-'.($idx+1).'">' . $section['code'] . '</th>';
      }
      $r .= '<th class="ui-widget-header">' . lang::get('Total') . '</th></tr></thead>';
      // No output rows at the top for any transect section level sample attributes in second grid.
      $r .= '<tbody class="ui-widget-content occs-body"></tbody>';
      $r .= '<tfoot><tr><td>Total</td>';
      foreach ($sections as $idx=>$section) {
        $r .= '<td class="col-'.($idx+1).' col-total"></td>';
      }
      $r .= '<td class="ui-state-disabled"></td></tr></tfoot></table>';
      $r .= '<label for="taxonLookupControl2" class="auto-width">'.lang::get('Add species to list').':</label> <input id="taxonLookupControl2" name="taxonLookupControl2" ></div>';
    }
    if(isset($args['third_taxon_list_id']) && $args['third_taxon_list_id']!=''){
      $r .= '<div id="grid3"><p>' . lang::get('LANG_Tab_Msg') . '</p><table id="transect-input3" class="ui-widget"><thead>';
      $r .= '<tr><th class="ui-widget-header">' . lang::get('Sections') . '</th>';
      foreach ($sections as $idx=>$section) {
        $r .= '<th class="ui-widget-header col-'.($idx+1).'">' . $section['code'] . '</th>';
      }
      $r .= '<th class="ui-widget-header">' . lang::get('Total') . '</th></tr></thead>';
      // No output rows at the top for any transect section level sample attributes in second grid.
      $r .= '<tbody class="ui-widget-content occs-body"></tbody>';
      $r .= '<tfoot><tr><td>Total</td>';
      foreach ($sections as $idx=>$section) {
        $r .= '<td class="col-'.($idx+1).' col-total"></td>';
      }
      $r .= '<td class="ui-state-disabled"></td></tr></tfoot></table>';
      $r .= '<label for="taxonLookupControl3" class="auto-width">'.lang::get('Add species to list').':</label> <input id="taxonLookupControl3" name="taxonLookupControl3" ></div>';
    }

    $r .= "<div id=\"notes\">\n";
    $r .= "<form method=\"post\">\n";
    $r .= $auth['write'];
    $r .= '<input type="hidden" name="sample:id" value="'.$parentSampleId.'" />';
    $r .= '<input type="hidden" name="website_id" value="'.$args['website_id'].'"/>';
    $r .= '<input type="hidden" name="survey_id" value="'.$args['survey_id'].'"/>';
    $r .= '<input type="hidden" name="page" value="grid"/>';
    $r .= data_entry_helper::textarea(array(
      'fieldname'=>'sample:comment',
      'label'=>lang::get('Notes'),
      'helpText'=>"Use this space to input comments about this week's walk."
    ));    
    $r .= '<input type="submit" value="'.lang::get('Submit').'" id="save-button"/>';
    $r .= '</form>';
    $r .= '</div></div>';
    // A stub form for AJAX posting when we need to create an occurrence
    $r .= '<form style="display: none" id="occ-form" method="post" action="'.iform_ajaxproxy_url($node, 'occurrence').'">';
    $r .= '<input name="website_id" value="'.$args['website_id'].'"/>';
    $r .= '<input name="occurrence:id" id="occid" />';
    $r .= '<input name="occurrence:taxa_taxon_list_id" id="ttlid" />';
    $r .= '<input name="occurrence:sample_id" id="occ_sampleid"/>';
    $r .= '<input name="occAttr:' . $args['occurrence_attribute_id'] . '" id="occattr"/>';
    $r .= '<input name="transaction_id" id="transaction_id"/>';
    $r .= '</form>';
    // A stub form for AJAX posting when we need to update a sample
    $r .= '<form style="display: none" id="smp-form" method="post" action="'.iform_ajaxproxy_url($node, 'sample').'">';
    $r .= '<input name="website_id" value="'.$args['website_id'].'"/>';
    $r .= '<input name="sample:id" id="smpid" />';
    $r .= '<input name="sample:parent_id" value="'.$parentSampleId.'" />';
    $r .= '<input name="sample:survey_id" value="'.$args['survey_id'].'" />';
    $r .= '<input name="sample:sample_method_id" value="'.$sampleMethods[0]['id'].'" />';
    $r .= '<input name="sample:entered_sref" id="smpsref" />';
    $r .= '<input name="sample:entered_sref_system" id="smpsref_system" />';
    $r .= '<input name="sample:location_id" id="smploc" />';
    $r .= '<input name="sample:date" value="'.$date.'" />';
    // include a stub input for each transect section sample attribute
    foreach ($attributes as $attr) {
      $r .= '<input id="'.$attr['fieldname'].'" />';
    }
    $r .= '</form>';
    // tell the Javascript where to get species from.
    // @todo handle diff species lists.
    data_entry_helper::add_resource('jquery_ui');
    data_entry_helper::add_resource('json');
    data_entry_helper::add_resource('autocomplete');
    data_entry_helper::$javascript .= "indiciaData.speciesList1 = ".$args['taxon_list_id'].";\n";
    if (!empty($args['main_taxon_filter_field']) && !empty($args['main_taxon_filter'])) {
      data_entry_helper::$javascript .= "indiciaData.speciesList1FilterField = '".$args['main_taxon_filter_field']."';\n";
      $filterLines = helper_base::explode_lines($args['main_taxon_filter']);
      data_entry_helper::$javascript .= "indiciaData.speciesList1FilterValues = '".json_encode($filterLines)."';\n";
    }
    data_entry_helper::$javascript .= "bindSpeciesAutocomplete(\"taxonLookupControl\",\"table#transect-input1\",\"".data_entry_helper::$base_url."index.php/services/data\", \"".$args['taxon_list_id']."\",
  indiciaData.speciesList1FilterField, indiciaData.speciesList1FilterValues, {\"auth_token\" : \"".$auth['read']['auth_token']."\", \"nonce\" : \"".$auth['read']['nonce']."\"},
  \"".lang::get('LANG_Duplicate_Taxon')."\", 25);

indiciaData.speciesList1Subset = ".(isset($args['common_taxon_list_id'])&&$args['common_taxon_list_id']!=""?$args['common_taxon_list_id']:"-1").";\n";
    if (!empty($args['common_taxon_filter_field']) && !empty($args['common_taxon_filter'])) {
      data_entry_helper::$javascript .= "indiciaData.speciesList1SubsetFilterField = '".$args['common_taxon_filter_field']."';\n";
      $filterLines = helper_base::explode_lines($args['common_taxon_filter']);
      data_entry_helper::$javascript .= "indiciaData.speciesList1SubsetFilterValues = '".json_encode($filterLines)."';\n";
    }
    data_entry_helper::$javascript .= "indiciaData.speciesList2 = ".(isset($args['second_taxon_list_id'])&&$args['second_taxon_list_id']!=""?$args['second_taxon_list_id']:"-1").";\n";
    if (!empty($args['second_taxon_filter_field']) && !empty($args['second_taxon_filter'])) {
      data_entry_helper::$javascript .= "indiciaData.speciesList2FilterField = '".$args['second_taxon_filter_field']."';\n";
      $filterLines = helper_base::explode_lines($args['second_taxon_filter']);
      data_entry_helper::$javascript .= "indiciaData.speciesList2FilterValues = '".json_encode($filterLines)."';\n";
    }
    data_entry_helper::$javascript .= "bindSpeciesAutocomplete(\"taxonLookupControl2\",\"table#transect-input2\",\"".data_entry_helper::$base_url."index.php/services/data\", indiciaData.speciesList2,
  indiciaData.speciesList2FilterField, indiciaData.speciesList2FilterValues, {\"auth_token\" : \"".$auth['read']['auth_token']."\", \"nonce\" : \"".$auth['read']['nonce']."\"},
  \"".lang::get('LANG_Duplicate_Taxon')."\", 25);
  
indiciaData.speciesList3 = ".(isset($args['third_taxon_list_id'])&&$args['third_taxon_list_id']!=""?$args['third_taxon_list_id']:"-1").";\n";
    if (!empty($args['third_taxon_filter_field']) && !empty($args['third_taxon_filter'])) {
      data_entry_helper::$javascript .= "indiciaData.speciesList3FilterField = '".$args['third_taxon_filter_field']."';\n";
      $filterLines = helper_base::explode_lines($args['third_taxon_filter']);
      data_entry_helper::$javascript .= "indiciaData.speciesList3FilterValues = '".json_encode($filterLines)."';\n";
    }
    // allow js to do AJAX by passing in the information it needs to post forms
    data_entry_helper::$javascript .= "bindSpeciesAutocomplete(\"taxonLookupControl3\",\"table#transect-input3\",\"".data_entry_helper::$base_url."index.php/services/data\", indiciaData.speciesList3,
  indiciaData.speciesList3FilterField, indiciaData.speciesList3FilterValues, {\"auth_token\" : \"".$auth['read']['auth_token']."\", \"nonce\" : \"".$auth['read']['nonce']."\"},
  \"".lang::get('LANG_Duplicate_Taxon')."\", 25);

indiciaData.indiciaSvc = '".data_entry_helper::$base_url."';\n";
    data_entry_helper::$javascript .= "indiciaData.readAuth = {nonce: '".$auth['read']['nonce']."', auth_token: '".$auth['read']['auth_token']."'};\n";
    data_entry_helper::$javascript .= "indiciaData.transect = ".$parentLocId.";\n";
    data_entry_helper::$javascript .= "indiciaData.parentSample = ".$parentSampleId.";\n";
    data_entry_helper::$javascript .= "indiciaData.sections = ".json_encode($sections).";\n";
    data_entry_helper::$javascript .= "indiciaData.occAttrId = ".$args['occurrence_attribute_id'] .";\n";
    data_entry_helper::$javascript .= "indiciaData.CMSUserAttrID = ".$cmsUserAttr['attributeId'] .";\n";
    data_entry_helper::$javascript .= "indiciaData.CMSUserID = ".$user->uid.";\n";
    // Do an AJAX population of the grid rows.
    data_entry_helper::$javascript .= "loadSpeciesList();";
    
    return $r;
  }

  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values.
   * @param array $args iform parameters.
   * @return array Submission structure.
   * @todo: Implement this method
   */
  public static function get_submission($values, $args) {
    $subsampleModels = array();
    if (!isset($values['page']) || ($values['page']!='grid' && $values['page']!='delete')) {
      // submitting the first page, with top level sample details
      $read = array(
        'nonce' => $values['read_nonce'],
        'auth_token' => $values['read_auth_token']
      );
      if (!isset($values['sample:entered_sref'])) {
        // the sample does not have sref data, as the user has just picked a transect site at this point. Copy the
        // site's centroid across to the sample. Should this be cached?
        $site = data_entry_helper::get_population_data(array(
          'table' => 'location',
          'extraParams' => $read + array('view'=>'detail','id'=>$values['sample:location_id'],'deleted'=>'f')
        ));
        $site = $site[0];
        $values['sample:entered_sref'] = $site['centroid_sref'];
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
      foreach($sections as $section){
        $exists=false;
        foreach($existingSubSamples as $existingSubSample){
          if($existingSubSample['location_id'] == $section['id']){
            $exists = true;
            break;
          }
        }
        if(!$exists){
          $smp = array('fkId' => 'parent_id',
                   'model' => array('id' => 'sample',
                     'fields' => array('survey_id' => array('value' => $values['sample:survey_id']),
                                       'website_id' => array('value' => $values['website_id']),
                                       'location_id' => array('value' => $section['id']),
                                       'entered_sref' => array('value' => $section['centroid_sref']),
                                       'entered_sref_system' => array('value' => $section['centroid_sref_system']),
                                       'sample_method_id' => array('value' => $sampleMethods[0]['id'])
                     )),
                   'copyFields' => array('date_start'=>'date_start','date_end'=>'date_end','date_type'=>'date_type'));
          foreach ($attributes as $attr) {
            foreach ($values as $key => $value){
              $parts = explode(':',$key);
              if(count($parts)>1 && $parts[0]=='smpAttr' && $parts[1]==$attr['attributeId']){
                $smp['model']['fields']['smpAttr:'.$attr['attributeId']] = array('value' => $value);
              }
            }
          }
          $subsampleModels[] = $smp;
        }
      }
    }
    $submission = submission_builder::build_submission($values, array('model' => 'sample'));
    if(count($subsampleModels)>0)
      $submission['subModels'] = $subsampleModels;
    return($submission);
  }
  
  /**
   * Override the form redirect to go back to My Walks after the grid is submitted. Leave default redirect (current page)
   * for initial submission of the parent sample.
   */
  public static function get_redirect_on_success($values, $args) {
    return  ($values['page']==='grid' || $values['page']==='delete') ? $args['my_walks_page'] : '';
  }

}
