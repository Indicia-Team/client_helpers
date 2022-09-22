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
 * //AVBL, is this licensing correct
 * @package Client
 * @subpackage PrebuiltForms
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link http://code.google.com/p/indicia/
 */

/**
 * Prebuilt Indicia data entry form.
 * NB has Drupal specific code. Relies on presence of IForm Proxy.
 *
 * @package Client
 * @subpackage PrebuiltForms
 */

require_once 'includes/dynamic.php';

/**
 * Store remembered field settings, since these need to be accessed from a hook function which runs outside the class.
 *
 * @var string
 */
global $remembered;

class iform_dgfm_attr_manager extends iform_dynamic {

  /**
   * Return the form metadata.
   *
   * @return string
   *   The definition of the form.
   */
  
  public static function get_dgfm_attr_manager_definition() {
    return array(
      'title' => 'DGfM Attr Manager',
      'category' => 'Utilities',
      'description' => 'Tool for creating new attributes, and organising attributes into groups (templates) for DGfM.',
      'recommended' => false
    );
  }

  /* TODO
   *
   *   Survey List
   *     Put in "loading" message functionality.
   *    Add a map and put samples on it, clickable
   *
   *  Sort out {common}.
   *
   * The report paging will not be converted to use LIMIT & OFFSET because we want the full list returned so
   * we can display all the occurrences on the map.
   * When displaying transects, we should display children locations as well as parent.
   */
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    $retVal = array_merge(
        parent::get_parameters(),
      array(
        array(
          'name' => 'taxon_list_id',
          'caption' => 'Taxon List_id',
          'description' => 'ID of the taxon list to load taxa taxon list attributes from.',
          'type' => 'string',
          'required' => true,
          'group' => 'Config IDs'
        ),
        array(
          'name' => 'survey_id',
          'caption' => 'Survey ID',
          'description' => 'ID of the survey for attribute_set taxon restrictions.',
          'type' => 'string',
          'required' => true,
          'group' => 'Config IDs'
        ),
        //AVB I think this is incorrectly being used instead of the Repoerting Catergory, search for source_termlist_id in code to see
        array(
          'name' => 'source_termlist_id',
          'caption' => 'Attribute Sources termlist ID',
          'description' => 'ID of attribute sources termlist.',
          'type' => 'string',
          'required' => true,
          'group' => 'Config IDs'
        ),
        array(
          'name' => 'reporting_category_termlist_id',
          'caption' => 'Attribute Reporting Category termlist ID',
          'description' => 'ID of attribute sources termlist.',
          'type' => 'string',
          'required' => true,
          'group' => 'Config IDs'
        ),
        array(
          'name' => 'life_stage_termlist_id',
          'caption' => 'Life stages termlist ID',
          'description' => 'ID of the Life Stages termlist.',
          'type' => 'string',
          'required' => true,
          'group' => 'Config IDs'
        )
      )
    );
    return $retVal;
  }

  public static function get_form($args, $nid) {
    $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
    global $indicia_templates;
    iform_load_helpers(['data_entry_helper', 'report_helper']);
    data_entry_helper::$javascript .= "indiciaData.taxon_attr_ajax_proxy_url = '".iform_ajaxproxy_url(null, 'taxa_taxon_list_attribute')."';\n";
    data_entry_helper::$javascript .= "indiciaData.taxon_lists_taxon_attr_ajax_proxy_url = '".iform_ajaxproxy_url(null, 'taxon_lists_taxa_taxon_list_attribute')."';\n";
    data_entry_helper::$javascript .= "indiciaData.occ_attr_ajax_proxy_url = '".iform_ajaxproxy_url(null, 'occurrence_attribute')."';\n";
    data_entry_helper::$javascript .= "indiciaData.occ_attr_website_ajax_proxy_url = '".iform_ajaxproxy_url(null, 'occurrence_attributes_website')."';\n";
    data_entry_helper::$javascript .= "indiciaData.occ_attr_ttl_attr_ajax_proxy_url = '".iform_ajaxproxy_url(null, 'occurrence_attributes_taxa_taxon_list_attribute')."';\n";
    data_entry_helper::$javascript .= "indiciaData.save_template_ajax_proxy_url = '".iform_ajaxproxy_url(null, 'attribute_set')."';\n"; 
    data_entry_helper::$javascript .= "indiciaData.save_attributes_into_template_ajax_proxy_url = '".iform_ajaxproxy_url(null, 'attribute_sets_taxa_taxon_list_attribute')."';\n";
    data_entry_helper::$javascript .= "indiciaData.save_taxa_into_template_ajax_proxy_url = '".iform_ajaxproxy_url(null, 'attribute_sets_taxon_restriction')."';\n";
    data_entry_helper::$javascript .= "indiciaData.save_attribute_sets_survey_ajax_proxy_url = '".iform_ajaxproxy_url(null, 'attribute_sets_survey')."';\n";
    data_entry_helper::$javascript .= "indiciaData.taxon_list_id = '".$args['taxon_list_id']."';\n";
    data_entry_helper::$javascript .= "indiciaData.survey_id = '".$args['survey_id']."';\n";
    data_entry_helper::$javascript .= "indiciaData.indiciaSvc = '".data_entry_helper::getRootFolder() . data_entry_helper::client_helper_path() . "proxy.php?url=".data_entry_helper::$base_url."';\n";
    data_entry_helper::$javascript .= "indiciaData.readAuth = {nonce: '".$readAuth['nonce']."', auth_token: '".$readAuth['auth_token']."'};\n";
    $id = self::initial_value($values, "taxa_taxon_list_attribute:id");
    $disabled_input = self::initial_value($values, 'metaFields:disabled_input');
    $enabled = ($disabled_input === 'YES') ? 'disabled="disabled"' : '';
    if ($disabled_input === 'YES') : 
      $r .= '<div class="alert alert-warning">The attribute was created by another user so you don\'t have permission to change the
      attribute\'s specification, although you can change the attribute assignments at the bottom of the page. Please contact
      the warehouse owner to request changes.</div>';
    endif; 
    $r .= '<br>';
    $r .= $metadata;
    $r .= '<div id="attr-management-controls-container">';
      $r .= self::draw_attribute_controls($readAuth, $args['source_termlist_id'], $args['reporting_category_termlist_id']);
      $r .= '<hr>';
    $r .= '</div>';
    $r .= self::draw_template_controls($args['website_id'], $args['password'], $args['taxon_list_id'], $args['survey_id']);
    // AVB put this into its own function
    $r .= '<hr>';
    $r .= '<input id="add-new-attribute-button" class="btn-primary" value="'.lang::get('Create a new attribute').'" type="button">';
    $r .= '<div id="attributes-grids-container">';
      $checkboxTemplate='<input class="attr-template-selection-checkbox" value="{attribute_id}" type="checkbox">';
      //$loadForEditingButtonTemplate='<button attributeId={attribute_id} class="load-attr-button">Edit</button>';
      $loadForEditingButtonTemplate='
      <a attributeId={attribute_id} class="load-attr-button">
        <img src="/modules/iform/media/images/nuvola/package_editors-22px.png" title="Edit this attribute">
      </a>';

      $htmlGridTitle = '<h4>'.lang::get('All characters').'</h4>';
      $dataSource = 'reports_for_prebuilt_forms/dgfm/show_all_attributes';
      $gridId = 'all_attributes_grid';
      $saveIntoTemplateButtonId = "save-attributes-into-template";
      $saveIntoTemplateButtonLabel = lang::get("Save characters into group");
      $extraParams = array(
        'taxon_list_id'=>$args['taxon_list_id']);
      $r .= self::draw_all_grid_and_button($args['website_id'], $args['password'], $checkboxTemplate, $loadForEditingButtonTemplate, $htmlGridTitle, $dataSource, $gridId, 
      $saveIntoTemplateButtonId, $saveIntoTemplateButtonLabel, $extraParams, false,  $args['life_stage_termlist_id']);
      $htmlGridTitle = '<h4>'.lang::get('Characters in group').'</h4>';
      $dataSource = 'reports_for_prebuilt_forms/dgfm/show_attributes_for_group';
      $gridId = 'template_attributes_grid';
      $extraParams = array(
        'taxon_list_id'=>$args['taxon_list_id'],
        // Initially set to -1 so we don't load anything onto the grid until a template is selected
        'attribute_set_id'=>-1);
      $checkboxTemplate='<input class="remove-attr-from-group-checkbox" value="{attribute_sets_taxa_taxon_list_attribute_id}" type="checkbox">';
      $deleteButtonHtml = '<input type="button" class="btn-default" id="delete-attr-set-taxa-taxon-list-attr" value="'.lang::get('Remove attribute from group')."\" />\n"; 
      $r .= self::draw_template_attributes_grid($args['website_id'], $args['password'], $checkboxTemplate, $htmlGridTitle, $dataSource, $gridId, $deleteButtonHtml, $extraParams);
      $r .= '<hr>';
    $r .= '</div>';
    // AVB put this into its own function
    $r .= '<div id="display-taxon-section-button">';
    $r .= '<input class="btn-primary" value="'.lang::get('Add taxa to a group').'" type="button"><br>';
    $r .= '<small><em>'.lang::get('Click on this button to open a screen that lets you add taxa into a group').'</em></small><br>';
    $r .= '</div>';
    $r .= '<div id="taxon-controls-container">';
    $checkboxTemplate='<input class="taxa-template-selection-checkbox" value="{taxon_meaning_id}" type="checkbox">';
    $htmlGridTitle = '<h4>'.lang::get('All Taxa').'</h4>';
    $dataSource = 'reports_for_prebuilt_forms/dgfm/load_taxa';
    $gridId = 'all_taxa_grid';
    $saveIntoTemplateButtonId = "save-taxa-into-template";
    $saveIntoTemplateButtonLabel = lang::get("Associate these taxa with this group of characters");
    $extraParams = array(
      'taxon_list_id'=>$args['taxon_list_id'],
      'survey_id'=>$args['survey_id']);
    $r .= self::draw_all_grid_and_button($args['website_id'], $args['password'], $checkboxTemplate, null, $htmlGridTitle, 
        $dataSource, $gridId, $saveIntoTemplateButtonId, $saveIntoTemplateButtonLabel, $extraParams, true, $args['life_stage_termlist_id']);
    $htmlGridTitle = '<h4>'.lang::get('Taxa associated with the currently selected group of characters').'</h4>';
    $dataSource = 'reports_for_prebuilt_forms/dgfm/load_taxa_with_restrictions';
    $gridId = 'template_taxa_grid';
    $extraParams = array(
      'taxon_list_id'=>$args['taxon_list_id'],
      'survey_id'=>$args['survey_id'],
      // Initially set to -1 so we don't load anything onto the grid until a template is selected
      'attribute_set_id'=>-1);
    $checkboxTemplate='<input class="attr-set-taxon-restrict-checkbox" value="{attribute_sets_taxon_restriction_id}" type="checkbox">';
    $deleteButtonHtml = '<input type="button" class="btn-default" id="delete-attr-set-taxon-restrict" value="'.lang::get('Remove taxa from group')."\" />\n"; 
    $r .= self::draw_template_attributes_grid($args['website_id'], $args['password'], $checkboxTemplate, $htmlGridTitle, $dataSource, $gridId, $deleteButtonHtml, $extraParams);
    $r .= '</div">';

    
//AVBL this was from warehouse and had document
/*data_entry_helper::$javascript .= "
$('#quick_termlist_create').change(function (e) {
  if ($(e.currentTarget).is(':checked')) {
    $('#quick-termlist-terms').show();
    $('#termlist-picker').hide();
  } else {
    $('#quick-termlist-terms').hide();
    $('#termlist-picker').show();
  }'});";*/

//JS;

//AVBL URL is wrong here, call to url:: not working
/*data_entry_helper::$javascript .= 
"function showHideTermlistLink() {
  $('#termlist-link').attr('href', '".'AVBURL'."'termlist/edit/'; ?>'+$('#termlist_id').val());
  if ($('#termlist_id').val()!=='' && $('#data_type').val()==='L') {
    $('#termlist-link').show();
  } else {
    $('#termlist-link').hide();
  }
  }";*/
/*data_entry_helper::$javascript .= 
"function showHideTermlistLink() {
  $('#termlist-link').attr('href', '".url::site()."'termlist/edit/'; ?>'+$('#termlist_id').val());
  if ($('#termlist_id').val()!=='' && $('#data_type').val()==='L') {
    $('#termlist-link').show();
  } else {
    $('#termlist-link').hide();
  }
  }";*/

data_entry_helper::$javascript .= 
"function toggleOptions() {
  var enable = [];
  var data_type = $('select#data_type').val();
  var allRules = [
    'required',
    'length',
    'alpha',
    'alpha_numeric',
    'numeric',
    'email',
    'url',
    'digit',
    'integer',
    'standard_text',
    'decimal',
    'regex',
    'min',
    'max',
    'date_in_past',
    'time'
  ];
  $('select#termlist_id').attr('disabled', 'disabled');
  $('#termlist-link').hide();
  $('#quick-termlist').hide();";
data_entry_helper::$javascript .= "
  switch(data_type) {
    case 'T': // text
      enable = [
        'required',
        'length',
        'alpha',
        'email',
        'url',
        'alpha_numeric',
        'numeric',
        'standard_text',
        'decimal',
        'regex',
        'time'
      ];
      break;
    case 'L': // Lookup List
      $('select#termlist_id').removeAttr('disabled');
      enable = [
        'required'
      ];";
      if (empty($id)) : 
        data_entry_helper::$javascript .= "$('#quick-termlist').show();";
      endif;
      data_entry_helper::$javascript .= "break;
    case 'I': // Integer
      enable = [
        'required',
        'digit',
        'decimal',
        'regex',
        'min',
        'max'
      ];
      break;
    case 'F': // Float
      enable = [
        'required',
        'numeric',
        'decimal',
        'regex',
        'min',
        'max'
      ];
      break;
    case 'D': // Specific Date
    case 'V': // Vague Date
      enable = [
        'required',
        'min',
        'max',
        'date_in_past'
      ];
      break;
    case 'B': // Boolean
      enable = [
        'required'
      ];
      break;
    default:
      enable = [];
      break;
  };
  $.each(allRules, function(i, item) {
    if ($.inArray(item, enable) === -1) {
      $('#ctrl-wrap-valid_' + item).hide();
    } else {
      $('#ctrl-wrap-valid_' + item).show();
    }
  });
  showHideTermlistLink();
};";
    return $r;
  }

  //AVB, don't think we need this anymore
  private static function draw_file_upload() {
    $r = '<h5>Upload Attributes From A File</h5>';
    $r .= '<a href="/fungi-without-borders/web/en/attribute-importer" target="_blank"><input type="button" value="Upload"></a>';
    $r .= data_entry_helper::checkbox([
      'fieldname' => 'upload_into_selected_template',
      'id' => 'upload_into_selected_template',
      'label' => 'Upload into selected template',
      'helpText' => 'Select this box to upload the attributes into the template currently displayed in the Existing Template box.
      Leave this off to upload into the database\'s general attributes list'
    ]);
    $r .= '<hr><br>';
    return $r;
  }  

  private static function draw_attribute_controls($readAuth, $sourceTermlistId, $reportingCategoryTermlistId) {
    $r = '<form>';
    $r .= '<input type="hidden" name="'.taxa_taxon_list_attribute.'":id" value="'.$id.'" />';
    $r .= '<input type="hidden" name="metaFields:disabled_input" value="'.$disabled_input.'" />';
    $r .= '<fieldset>';
    $r .= '<legend>Attribute Details</legend>';
    // AVBH there is a bug here, as system tries to save occurrence using this ID too
    $r .= data_entry_helper::text_input([
      'fieldname' => "taxa_taxon_list_attribute:id",
      'label' => 'ID (blank if new attribute)',
      'readonly' => 'readonly'
    ]);
    $r .= data_entry_helper::text_input([
      'fieldname' => "taxa_taxon_list_attribute:caption",
      'label' => 'Caption',
      //'default' => self::initial_value($values, "taxa_taxon_list_attribute:caption"),
      'validation' => ['required'],
      'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
    ]);
    //if (array_key_exists('caption_i18n', $this->model->as_array())) {
      //AVBL, form needs to be multi-lingual
      //$defaultLang = kohana::config('indicia.default_lang');     
      $defaultLang='eng';
      $helpText = 'If you need to specify the localise the attribute caption into different languages for use in report outputs, specify
          the caption above using language code $defaultLang and enter additional translations here. Enter one per line, followed
          by a pipe (|) character then the ISO language code. E.g.<br/>
          Compter|fra<br/>
          Anzahl|deu<br/>';
      $r .= data_entry_helper::textarea([
        'fieldname' => "taxa_taxon_list_attribute:caption_i18n",
        'label' => 'Caption in other languages',
        'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
        'helpText' => $helpText,
      ]);
    $helpText = 'Specify the unit or unit abbreviation where appropriage (e.g. mm).<br>';
    $r .= data_entry_helper::text_input([
      'fieldname' => "taxa_taxon_list_attribute:unit",
      'label' => 'Unit',
      //'default' => self::initial_value($values, "taxa_taxon_list_attribute:caption"),
      'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
      'helpText' => $helpText,
    ]);
    //}
      //AVB do we even need this?
      /*$helpText = 'If the attribute is linked to a standardised glossary such as Darwin Core then provide the term name. Otherwise provide
          a brief alphanumeric only (with no spaces) version of the attribute name to give it a unique identifier within the
          context of the survey dataset to make it easier to refer to in configuration.';
      $r .= data_entry_helper::text_input([
        'fieldname' => "taxa_taxon_list_attribute:term_name",
        'label' => 'Term name',
        'default' => self::initial_value($values, "taxa_taxon_list_attribute:term_name"),
        'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
        'helpText' => $helpText,
      ]);*/
      //AVB do we even need this?
      /*$helpText = 'If the attribute is linked to a standardised glossary such as Darwin Core then provide the term identifier, typically
          the URL to the term definition.';
      $r .= data_entry_helper::text_input([
        'fieldname' => "taxa_taxon_list_attribute:term_identifier",
        'label' => 'Term identifier',
        'default' => self::initial_value($values, "taxa_taxon_list_attribute:term_identifier"),
        'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
        'helpText' => $helpText,
        'class' => 'control-width-6',
      ]);*/
      $r .= data_entry_helper::textarea([
        'fieldname' => "taxa_taxon_list_attribute:description",
        'label' => 'Description',
        //'default' => self::initial_value($values, "taxa_taxon_list_attribute:description"),
        'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
      ]);

    //AVBL start of image code
    //if (array_key_exists('image_path', $this->model->as_array())) {
      /*$helpText = <<<TXT
If an image is required to explain the attribute, select it here. The image can be displayed alongside the input control
on the data entry form.
TXT;
      $r .=  data_entry_helper::image_upload(array(
        'fieldname' => "image_upload",
        'label' => 'Image',
        'helpText' => $helpText,
        'existingFilePreset' => 'med',
      ));
      if (self::initial_value($values, "taxa_taxon_list_attribute:image_path")) {
        $r .= self::sized_image(self::initial_value($values, "taxa_taxon_list_attribute:image_path")) . '</br>';
      }
      $r .= data_entry_helper::hidden_text([
        'fieldname' => "taxa_taxon_list_attribute:image_path",
        'default' => self::initial_value($values, "taxa_taxon_list_attribute:image_path"),
      ]);
    //}      
    //AVBL End of image code  
      */
    //AVBH
    /*if (method_exists($this->model, 'get_system_functions')) {
      $options = [];
      $hints = [];
      foreach ($this->model->get_system_functions() as $function => $def) {
        $options[$function] = $def['title'];
        $hints[$def['title']] = $def['description'];
      }
      $indicia_templates['sys_func_item'] = '<option value="{value}" {selected} {title}>{caption}</option>';
      $r .= data_entry_helper::select([
        'fieldname' => "taxa_taxon_list_attribute:system_function",
        'label' => 'System function',
        'default' => self::initial_value($values, "taxa_taxon_list_attribute:system_function"),
        'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
        'blankText' => '-none-',
        'lookupValues' => $options,
        'optionHints' => $hints,
        'itemTemplate' => 'sys_func_item',
      ]);
    }*/
      $r .= data_entry_helper::select([
        'fieldname' => "taxa_taxon_list_attribute:reporting_category_id",
        'label' => 'Area/Sub-area',
        //'default' => self::initial_value($values, "taxa_taxon_list_attribute:reporting_category_id"),
        'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
        'blankText' => '-none-',
        'lookupValues' => self::get_area_sub_area_data($readAuth, $reportingCategoryTermlistId, 'id'),
      ]);
      // AVBL Don't think we need this field for DGFM, plus the list_taxa_taxon_list_attributes view doesn't currently have it, so can't repload
      //without db script
      /*$r .= data_entry_helper::select([
        'fieldname' => "taxa_taxon_list_attribute:source_id",
        'label' => 'Source of attribute',
        'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
        'blankText' => '-none-',
        'lookupValues' => self::get_termlist_terms_data($readAuth, $sourceTermlistId),
      ]);*/
    $r .= data_entry_helper::select([
      'fieldname' => "taxa_taxon_list_attribute:data_type",
      'id' => 'data_type',
      'label' => 'Data type',
      //'default' => self::initial_value($values, "taxa_taxon_list_attribute:data_type"),
      'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
      'lookupValues' => [
        'T' => 'Text',
        'L' => 'Lookup List',
        'I' => 'Integer',
        'F' => 'Float',
        'D' => 'Specific Date',
        'V' => 'Vague Date',
        'B' => 'Boolean',
      ],
      'validation' => ['required'],
    ]);
    $r .= "<div id=\"quick-termlist\" style=\"display: none;\">\n";
    $r .= data_entry_helper::checkbox([
      'fieldname' => 'metaFields:quick_termlist_create',
      'id' => 'quick_termlist_create',
      'label' => 'Create a new termlist',
      'helpText' => 'Tick this box to create a new termlist with the same name as this attribute and populate it with a provided list of terms.',
    ]);
    $r .= "<div id=\"quick-termlist-terms\" style=\"display: none;\">\n";
    $r .= data_entry_helper::textarea([
      'fieldname' => 'metaFields:quick_termlist_terms',
      'label' => 'Terms',
      'helpText' => 'Enter terms into this box, one per line. A termlist with the same name as the attribute will be created and populated with this list of terms in the order provided.',
    ]);
    $r .= '</div>';
    $r .= '</div>';
    $r .= data_entry_helper::select([
      'fieldname' => "taxa_taxon_list_attribute:termlist_id",
      'id' => 'termlist_id',
      'label' => 'Termlist',
      'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
      'blankText' => '<Please select>',
      'table' => 'termlist',
      'captionField' => 'title',
      'valueField' => 'id',
		'extraParams' => $readAuth + array('orderby'=>'title'),
      'nocache' => true
    ]);
    //AVBH, need to do a terms setup page
    /* $r .= '<a id="termlist-link" target="_blank" href="">edit terms in new tab</a>';*/
    //AVBH Multi-value attributes which allow ranges are not currently supported. Please untick Allow multiple values or Allow ranges.. Need to code
    $r .= data_entry_helper::checkbox([
      'fieldname' => "taxa_taxon_list_attribute:multi_value",
      'label' => 'Allow multiple values',
      'helpText' => 'Does termlist support entry of multiple values?',
      //'default' => self::initial_value($values, "taxa_taxon_list_attribute:multi_value"),
    ]);
    $r .= data_entry_helper::checkbox([
      'fieldname' => "taxa_taxon_list_attribute:allow_ranges",
      'label' => 'Allow ranges',
      //'default' => self::initial_value($values, "taxa_taxon_list_attribute:allow_ranges"),
      'helpText' => 'Allow a range to be specified as a value, e.g. 0.4 - 1.6',
    ]);
    /*$r .= data_entry_helper::checkbox([
      'fieldname' => "taxa_taxon_list_attribute:public",
      //AVBL - This needs converting to $other_data['publicFieldName']
      'label' => 'Public',
      'default' => self::initial_value($values, "taxa_taxon_list_attribute:public"),
    ]);*/
    if (taxa_taxon_list_attribute === 'sample_attribute') {
      $r .= data_entry_helper::checkbox([
        'fieldname' => "taxa_taxon_list_attribute:applies_to_location",
        'label' => 'Applies to location',
        'helpText' => 'Tick this box for attributes which describe something inherent to the site/location itself',
      ]);
    }
    elseif (taxa_taxon_list_attribute === 'person_attribute') {
      $r .= data_entry_helper::checkbox([
        'fieldname' => "taxa_taxon_list_attribute:synchronisable",
        'label' => 'Synchronisable with client website user profiles',
        'helpText' => 'Tick this box for attributes which can be linked to a user account profile on a client site.',
      ]);
    }
    $r .= '</fieldset>';
    $r .= '<fieldset id="validation-rules"'.$disabled_input === 'YES' ? ' class="ui-state-disabled"' : '';
    $r .= '<legend>Validation rules</legend>';
    $r .= data_entry_helper::checkbox([
      'fieldname' => 'valid_required',
      'label' => 'Required',
      'helpText' => 'Note, checking this option will make the attribute GLOBALLY required for all surveys which use it. ' .
        'Consider making it required on a survey dataset basis instead.',
    ]);
    //AVB need to resinstate this
    /*$r .= data_entry_helper::checkbox([
      'fieldname' => 'valid_length',
      'label' => 'Length',
      'helpText' => 'Enforce the minimum and/or maximum length of a text value.',
    ]);*/
    // AVBL
    //$valMin = self::specialchars($model->valid_length_min);
    // AVBL
    //$valMax = self::specialchars($model->valid_length_max);
    //AVB need to resinstate this
    /*$r .= "<div id=\"valid_length_inputs\"> length between <input type=\"text\" id=\"valid_length_min\" name=\"valid_length_min\" value=\"$valMin\"/>
        and <input type=\"text\" id=\"valid_length_max\" name=\"valid_length_max\" value=\"$valMax\"/> characters
        </div>";*/
    /*$r .= data_entry_helper::checkbox([
      'fieldname' => 'valid_alpha',
      'label' => 'Alphabetic characters only',
      'helpText' => 'Enforce that any value provided consists of alphabetic characters only.',
    ]);
    $r .= data_entry_helper::checkbox([
      'fieldname' => 'valid_numeric',
      'label' => 'Numeric characters only',
      'helpText' => 'Enforce that any value provided consists of numeric characters only.',
    ]);
    $r .= data_entry_helper::checkbox([
      'fieldname' => 'valid_alpha_numeric',
      'label' => 'Alphanumeric characters only',
      'helpText' => 'Enforce that any value provided consists of alphabetic and numeric characters only.',
    ]);
    $r .= data_entry_helper::checkbox([
      'fieldname' => 'valid_digit',
      'label' => 'Digits only',
      'helpText' => 'Enforce that any value provided consists of digits (0-9) only, with no decimal points or dashes.',
    ]);
    $r .= data_entry_helper::checkbox([
      'fieldname' => 'valid_integer',
      'label' => 'Integer',
      'helpText' => 'Enforce that any value provided is a valid whole number. Consider using an integer data type instead of text.',
    ]);
    $r .= data_entry_helper::checkbox([
      'fieldname' => 'valid_standard_text',
      'label' => 'Standard text',
      'helpText' => 'Enforce that any value provided is valid text (Letters, numbers, whitespace, dashes, full-stops and underscores are allowed..',
    ]);
    $r .= data_entry_helper::checkbox([
      'fieldname' => 'valid_email',
      'label' => 'Email address',
      'helpText' => 'Enforce that any value provided is a valid email address format.',
    ]);
    $r .= data_entry_helper::checkbox([
      'fieldname' => 'valid_url',
      'label' => 'URL',
      'helpText' => 'Enforce that any value provided is a valid URL format.',
    ]);*/
    
    
    
    //AVB need to resinstate this
    /*$r .= data_entry_helper::checkbox([
      'fieldname' => 'valid_decimal',
      'label' => 'Formatted decimal',
      'helpText' => 'Validate a decimal format against the provided pattern, e.g. 2 (2 digits) or 2,2 (2 digits before and 2 digits after the decimal point).',
    ]);
    $r .= "<div id=\"valid_decimal_inputs\"> Format <input type=\"text\" id=\"valid_dec_format\" name=\"valid_dec_format\" value=\"$val\"/></div>";*/
    //AVB need to resinstate this
    /*$r .= data_entry_helper::checkbox([
      'fieldname' => 'valid_regex',
      'label' => 'Regular expression',
      'helpText' => 'Validate the supplied value against a regular expression, e.g. /^(sunny|cloudy)$/',
    ]);
    $r .= "<div id=\"valid_regex_inputs\"><input type=\"text\" id=\"valid_regex_format\" name=\"valid_regex_format\" value=\"$val\"/></div>";*/
    
    
    
    
    //AVB need to resinstate these fields
    /*$r .= data_entry_helper::checkbox([
      'fieldname' => 'valid_min',
      'label' => 'Minimum value',
      'helpText' => 'Ensure the value is at least the minimum that you specify',
    ]);
    $r .= "<div id=\"valid_min_inputs\">Value must be at least <input type=\"text\" id=\"valid_min_value\" name=\"valid_min_value\" value=\"$val\"/></div>";
    $r .= data_entry_helper::checkbox([
      'fieldname' => 'valid_max',
      'label' => 'Maximum value',
      'helpText' => 'Ensure the value is at most the maximum that you specify',
    ]);
    $r .= "<div id=\"valid_max_inputs\">Value must be at most <input type=\"text\" id=\"valid_max_value\" name=\"valid_max_value\" value=\"$val\"/></div>";*/
   
   
   
    /*$r .= data_entry_helper::checkbox([
      'fieldname' => 'valid_date_in_past',
      'label' => 'Date in past',
      'helpText' => 'Ensure that the date values provided are in the past.',
    ]);
    $r .= data_entry_helper::checkbox([
      'fieldname' => 'valid_time',
      'label' => 'Time',
      'helpText' => 'Ensure that the value provided is a valid time format.',
    ]);*/
    $r .= '</fieldset>';
    //AVB need to resinstate this field
    $r .= '<fieldset>';
    $r .= data_entry_helper::checkbox([
      'fieldname' => 'add_occ_attr_checkbox',
      'label' => 'Add an occurrence attribute?',
      'default'=>true,
      'helpText' => 'Create a occurrence attribute as well as taxon attribute?',
    ]);
    $r .= '</fieldset>';
  // Output the view that lets this custom attribute associate with websites,
  // surveys, checklists or whatever is appropriate for the attribute type.
  //AVBL need to fix this comment out
  //$this->associationsView->other_data = $other_data;
  //AVBL need to fix this comment out
  //$this->associationsView->model = $model;
  //AVBL - call to html not working
  //$r .= html::form_buttons(!empty($id), FALSE, FALSE);
  // JVB - suspect the following line not required
    data_entry_helper::enable_validation('custom-attribute-edit');
  //$r .= data_entry_helper::dump_javascript();
  $r .= '</form>';
    //$r = '';
    //$r .= self::existing_templates_list();
    //$r .= self::select_existing_template_button()();
    //$r .= self::delete_existing_template_button();
    //$r .= self::save_new_template_button();
    $r .= '<br>';
    $r .= self::save_new_attribute_button();
  return $r;
  }
  
  public static function get_area_sub_area_data($readAuth, $termlist, $idField) {
    $extraParams = $readAuth + array(
      'termlist_id' => $termlist
    );
    $termlists_terms = data_entry_helper::get_report_data(array(
      'dataSource' => 'reports_for_prebuilt_forms/dgfm/get_area_sub_area_drop_down_data',
      'extraParams' => $extraParams
    ));
    foreach ($termlists_terms as $idx => $termlists_term) {
      $minified[$termlists_term[$idField]]=$termlists_term['area_sub_area_fullname'];
    }
    return $minified;
  }

  public static function get_termlist_terms_data($readAuth, $termlist, $idField) {
    $extraParams = $readAuth + array(
      'view' => 'detail',
      'termlist_id' => $termlist
    );
    $termlists_terms = data_entry_helper::get_population_data(array(
      'table' => 'termlists_term',
      'extraParams' => $extraParams
    ));
    foreach ($termlists_terms as $idx => $termlists_term) {
      $minified[$termlists_term[$idField]]=$termlists_term['term'];
    }
    return $minified;
  }

  protected static function draw_existing_attribute_set_drop_down($website_id, $password, $survey_id) {
    $readAuth = data_entry_helper::get_read_auth($website_id, $password);
    $attributeSetData = data_entry_helper::get_report_data(array (
      'dataSource' => 'reports_for_prebuilt_forms/dgfm/load_attribute_sets',
      'readAuth' => $readAuth,
      'extraParams' => array(
          'website_id' => $website_id,
          'survey_id' => $survey_id
      )
    ));
    $r = '<label for="existing_attribute_set">'.lang::get('Existing groups of characters')."</label>\n";
    $r .= '<select id="existing_attribute_set" class=" form-control " name="existing_attribute_set">';
    $r .= '<option value="">-none-</option>';
    foreach($attributeSetData as $attributeSetData){
      $r .= '<option value="'.$attributeSetData['attribute_set_id'].'"';
      $r .= ' attribute_sets_survey='.$attributeSetData['attribute_sets_survey_id'].'>'.$attributeSetData['attribute_set_name'].'</option>';
    }
    $r .= "</select><br/>\n";
    return $r;
  }

  private static function draw_template_controls($website_id, $password, $taxonListId, $survey_id) {
    $r='';
    $r .= '<div id="existing-template-control-container">';
    $r .= self::draw_existing_attribute_set_drop_down($website_id, $password, $survey_id);
    $r .= '</div>';
    $r .= '<div id="template-management-controls-container">';
    $r .= self::delete_existing_template_button();
    $r .= '<hr>';
    $helpText = lang::get('Edit the title for an existing group of characters or add a new one.');
    $r .= data_entry_helper::text_input([
      'fieldname' => "attribute_set:title",
      'label' => lang::get('Title for the group of characters'),
      //'default' => self::initial_value($values, "taxa_taxon_list_attribute:term_identifier"),
      'helpText' => $helpText,
      'class' => 'control-width-6',
    ]);
    $helpText = lang::get('Edit the description for an existing group of characters or add a new one.');
    $r .= data_entry_helper::textarea([
      'fieldname' => "attribute_set:description",
      'label' => lang::get('Description of the group of characters'),
      'helpText' => $helpText,
      'class' => 'control-width-6',
    ]);
    $r .= self::save_new_template_button();
    $r .= '</div>';
    return $r;
  }

  private static function draw_all_grid_and_button($website_id, $password, $checkboxTemplate, $loadForEditingButtonTemplate, 
      $htmlGridTitle, $dataSource, $gridId, $saveIntoTemplateButtonId, $saveIntoTemplateButtonLabel, $extraParams, $includeTaxaButtons, 
      $lifeStageTermlistId) {
    $r = $htmlGridTitle;
    $readAuth = data_entry_helper::get_read_auth($website_id, $password);
    $columns = array(
      array(
        'display'=>'Select',
        'template'=>$checkboxTemplate
      )
    );

    if (!empty($loadForEditingButtonTemplate)) {
      $columns = array_merge ($columns, array(
        array(
          'display'=>'Load',
          'template'=>$loadForEditingButtonTemplate
        )
      ));
    }
     $r .= report_helper::report_grid(array(
      'dataSource' => $dataSource,
      'id' => $gridId,
      //'rowId' => 'attribute_id',
      'ajax' => TRUE,
      'sharing' => $args['sharing'],
      'mode' => 'report',
      'readAuth' => $readAuth,
      'itemsPerPage' => 20,
      'extraParams'=>$extraParams,
      'columns' => $columns
    ));
    $r .= '<br>';
    if ($includeTaxaButtons===true) {
      $r .= data_entry_helper::select([
        'fieldname' => "life_stage",
        'label' => lang::get('Associated life stage'),
        'disabled' => $disabled_input === 'YES' ? 'disabled' : '',
        'blankText' => '-none-',
        'lookupValues' => self::get_termlist_terms_data($readAuth, $lifeStageTermlistId, 'meaning_id'),
      ]);
    }
    $r .= '<input id="'.$saveIntoTemplateButtonId.'" class="btn-default" type="button" class="btn-default" value="'.$saveIntoTemplateButtonLabel.'">';
    if ($includeTaxaButtons===true) {
      $r .= '<input id="return-to-start-button" class="btn-primary" value="Back" type="button">';
    }
    $r .= '<br><small><em>'.lang::get('Select checkboxes in the grid above and then click the "').$saveIntoTemplateButtonLabel.lang::get('" button to save them into the grid below').'</em></small><br>';
    $r .= '<br>';
    return $r;
  }

  private static function draw_template_attributes_grid($website_id, $password, $checkboxTemplate, $htmlGridTitle, $dataSource, $gridId, $deleteButtonHtml, $extraParams) {
    $r = $htmlGridTitle;
    $readAuth = data_entry_helper::get_read_auth($website_id, $password);
    $reportOptions=array(
      'dataSource' => $dataSource,
      'id' => $gridId,
      'rowId' => 'attribute_id',
      'ajax' => TRUE,
      'sharing' => $args['sharing'],
      'mode' => 'report',
      'readAuth' => $readAuth,
      'itemsPerPage' => 20,
      'extraParams'=>$extraParams
    );
    if (!empty($checkboxTemplate)) {
      $columns = array(
        array(
          'display'=>'Select',
          'template'=>$checkboxTemplate
        )
      );
      $reportOptions = array_merge($reportOptions, array(
        'columns' => $columns  
      ));
    }
    $r .= report_helper::report_grid($reportOptions);
    if (!empty($deleteButtonHtml)) {
      $r .= $deleteButtonHtml;
    }
    return $r;
  }

  /*
  private static function draw_all_attributes_grid_and_button($website_id, $password, $taxonListId) {
    $r = '<h4>All attributes</h4>';
    $readAuth = data_entry_helper::get_read_auth($website_id, $password);
    $checkboxTemplate='<input class="template-selection-checkbox" value="{attribute_id}" type="checkbox">';
    $loadForEditingButtonTemplate='<button attributeId={attribute_id} class="load-attr-button">Edit</button>';

    $r .= report_helper::report_grid(array(
      'dataSource' => 'reports_for_prebuilt_forms/dgfm/load_attributes',
      'id' => 'all_attributes_grid',
      //'rowId' => 'attribute_id',
      'ajax' => TRUE,
      'sharing' => $args['sharing'],
      'mode' => 'report',
      'readAuth' => $readAuth,
      'itemsPerPage' => 20,
      'extraParams'=>array(
        'taxon_list_id'=>$taxonListId,
      ),    
      //'columns'=>array(
      //  'fieldname'=>'attribute_caption', 'visible'=>false
      //)
      'columns' => array(
        array(
          'display'=>'Select',
          'template'=>$checkboxTemplate
        ),
        array(
          'display'=>'Load',
          'template'=>$loadForEditingButtonTemplate
        )
      )
    ));
    $r .= '<br>';
    $r .= '<input id="save-attributes-into-template" type="button" value="Save attributes into group">';
    $r .= '<br>';
    return $r;
  }

  private static function draw_template_attributes_grid($website_id, $password, $taxonListId) {
    $r = '<h4>Characters in group</h4>';
    $readAuth = data_entry_helper::get_read_auth($website_id, $password);
    $r .= report_helper::report_grid(array(
      'dataSource' => 'reports_for_prebuilt_forms/dgfm/load_attributes',
      'id' => 'template_attributes_grid',
      'rowId' => 'attribute_id',
      'ajax' => TRUE,
      'sharing' => $args['sharing'],
      'mode' => 'report',
      'readAuth' => $readAuth,
      'itemsPerPage' => 20,
      'extraParams'=>array(
        'taxon_list_id'=>$taxonListId,
        // Initially set to -1 so we don't load anything onto the grid until a template is selected
        'attribute_set_id'=>-1),
      )
    );
    return $r;
  }*/
  
  private static function existing_templates_list() {
    $r='';
    
    return $r;
  }
  
  /*private static function select_existing_template_button() {
    $r = '';
    $r .= '<input type="button" class="' . $indicia_templates['buttonDefaultClass'] . '" id="select-template-button" value="'.lang::get('Load template')."\" />\n";
    return $r;
  } */
 
  private static function delete_existing_template_button() {
    $r='';
    $r = '<input type="button" class="btn-default" id="delete-template" value="'.lang::get('Delete character group')."\" />\n"; 
    $r .= '<br>';
    return $r;
  }
  
  private static function save_new_attribute_button() {
    $r = '<input id="save-attribute" type="button" value='.lang::get("Save attribute").'>';
    $r .= '<br>';
    return $r;
  }

  private static function save_new_template_button() {
    $r='';
    $r .= '<input type="button" id="save-template" class="btn-default" value="'.lang::get('Save character group')."\" /><br>\n";
    $r .= '<small><em>'.lang::get('You can create a new group by filling in the boxes above when no existing group is selected in the drop-down.').'</em></small><br>';
    $r .= '<br>';
    return $r;
  } 
  
  private static function control_addnewattributes() {
    $r='';
    
    return $r;
  }
  
  //Don't display attribute already in the template we are showing
  private static function control_list_attributes_in_template() {
    $r='';
    
    return $r;
  }
  
  private static function selectattributetoaddtotemplate() {
      
  }
  
  private static function control_createorselecttemplatetouse() {
    $r='';
    
    $r=savetemplatebutton();
    return $r;
  }


  /**
   * Override the default submit buttons to add a delete button where appropriate.
   */
  protected static function getSubmitButtons($args) {
    return $r;
  }
  
  /**
   * Returns the initial value for an edit control on a page. This is either loaded from the $_POST
   * array (if reloading after a failed attempt to save) or from the model or initial default value
   * otherwise.
   *
   * @param ORM $values
   *   List of values to load in an array
   * @param string $fieldname
   *   The fieldname should be of form model:fieldname. If the model part indicates a different model
   *   then the field value will be loaded from the other model (assuming that model is linked to the
   *   main one. E.g.'taxon:description' would load the $model->taxon->description field.
   */
  public static function initial_value($values, $fieldname) {
    if (array_key_exists($fieldname, $values)) {
      return self::specialchars($values[$fieldname]);
    }
    else {
      return NULL;
    }
  }
  
    /**
   * Outputs an image.
   *
   * Output a thumbnail or other size of an image, with a link to the full
   * sized image suitable for the fancybox jQuery plugin.
   *
   * @param string $filename
   *   Name of a file within the upload folder.
   * @param string $size
   *   Name of the file size, normally thumb or med depending on the image
   *   handling config.
   *
   * @return string
   *   HTML to insert into the page, with the anchored image element.
   */
  public static function sized_image($filename, $size = 'thumb') {
    helper_base::add_resource('fancybox');
    //AVBL how to deal with kohana $img_config
    //$img_config = kohana::config('indicia.image_handling');
    // Dynamically build the HTML sizing attrs for the thumbnail from the
    // config. We may not know both dimensions.
    $sizing = '';
    if ($img_config && array_key_exists($size, $img_config)) {
      if (array_key_exists('width', $img_config['thumb'])) {
        $sizing = ' width="' . $img_config[$size]['width'] . '"';
      }
      if (array_key_exists('height', $img_config[$size])) {
        $sizing .= ' height="'.$img_config[$size]['height'].'"';
      }
    }
    $base = url::base();
    return <<<HTML
<a href="{$base}upload/$filename" class="fancybox">
  <img src="{$base}upload/$size-$filename"$sizing />
</a>
HTML;
  }
  
  
    /**
     * Convert special characters to HTML entities
     *
     * @param   string   string to convert
     * @param   boolean  encode existing entities
     * @return  string
     */
    public static function specialchars($str, $double_encode = TRUE)
    {
            // Force the string to be a string
            $str = (string) $str;

            // Do encode existing HTML entities (default)
            if ($double_encode === TRUE)
            {
                    $str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
            }
            else
            {
                    // Do not encode existing HTML entities
                    // From PHP 5.2.3 this functionality is built-in, otherwise use a regex
                    if (version_compare(PHP_VERSION, '5.2.3', '>='))
                    {
                            $str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8', FALSE);
                    }
                    else
                    {
                            $str = preg_replace('/&(?!(?:#\d++|[a-z]++);)/ui', '&amp;', $str);
                            $str = str_replace(array('<', '>', '\'', '"'), array('&lt;', '&gt;', '&#39;', '&quot;'), $str);
                    }
            }

            return $str;
    }
}


