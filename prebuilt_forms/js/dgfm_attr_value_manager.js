// AVBH NEed to change to get taxa_taxon_list_id from URL
jQuery(document).ready(function docReady($) {
$('#attr-management-controls-container').hide();
$('#taxon-controls-container').hide();
var lastSavedTaxonAttributeId;
//AVBOCC
//var lastSavedOccAttributeId;

var standardTextFieldTypes=["id", "caption", "caption_i18n", /*"term_name", "term_identifier"*/ "description"];
var standardDropDownFieldTypes=["reporting_category_id", /*"source_id"*/, "data_type", "termlist_id"];
var standardCheckboxFieldTypes=["multi_value", "allow_ranges", "public"];
var validationCheckboxFieldTypesWithoutTextFields =  ["valid_required", "valid_alpha", "valid_numeric", "valid_alpha_numeric", "valid_digit", "valid_integer",
  "valid_standard_text", "valid_email", "valid_url", "valid_date_in_past", "valid_time"]; 


//AVB Need to resinstate this
var validationCheckboxFieldTypesThatHaveTextFields = [];
/*var validationCheckboxFieldTypesThatHaveTextFields=[
    ["valid_length", "valid_length_min", "valid_length_max"],
    ["valid_decimal","valid_dec_format"], 
    ["valid_regex", "valid_regex_format"],
    ["valid_min","valid_min_value"],
    ["valid_max", "valid_max_value"]
];*/

var showValidationFieldsText = 
  ["valid_required", "valid_length", "valid_length_min", "valid_length_max", "valid_alpha", "valid_numeric", 
      "valid_alpha_numeric", "valid_standard_text", "valid_email", "valid_url", "valid_time", "valid_decimal", "valid_dec_format",
      "valid_regex", "valid_regex_format"];
var showValidationFieldsLookup = ["valid_required"];
var showValidationFieldsInt = ["valid_required", "valid_digit", "valid_decimal", "valid_dec_format", "valid_regex", "valid_regex_format",
      "valid_min", "valid_min_value", "valid_max", "valid_max_value"];
var showValidationFieldsFloat = ["F", "valid_required", "valid_numeric", "valid_decimal", "valid_dec_format", "valid_regex", "valid_regex_format",
      "valid_min", "valid_min_value", "valid_max", "valid_max_value"];
var showValidationFieldsDate = ["valid_required", "valid_length", "valid_length_min", "valid_length_max", "valid_min", "valid_min_value", 
      "valid_max", "valid_max_value", "valid_date_in_past"];
var showValidationFieldsVague = ["valid_required", "valid_length", "valid_length_min", "valid_length_max", "valid_min", "valid_min_value", 
      "valid_max", "valid_max_value", "valid_date_in_past"];
var showValidationFieldsBool = ["valid_required", "valid_length", "valid_length_min", "valid_length_max"];

/*
 * Returns true if an item is found in an array
 */
inArray = function inArray(needle, haystack) {
  var length = haystack.length;
  for(var i = 0; i < length; i++) {
      if(haystack[i] == needle) return true;
  }
  return false;
}

detect_data_type_to_show_validation_fields_for();

$('#data_type').change(function() {
  detect_data_type_to_show_validation_fields_for();
});

function detect_data_type_to_show_validation_fields_for() {
  for (var idx=0; idx < validationCheckboxFieldTypesWithoutTextFields.length; idx++) {
    if ($('#data_type').val()=='T') {
      show_or_hide_validation_field(validationCheckboxFieldTypesWithoutTextFields[idx], showValidationFieldsText);
    }
    if ($('#data_type').val()=='L') {
      show_or_hide_validation_field(validationCheckboxFieldTypesWithoutTextFields[idx], showValidationFieldsLookup);
    }
    if ($('#data_type').val()=='I') {
      show_or_hide_validation_field(validationCheckboxFieldTypesWithoutTextFields[idx], showValidationFieldsInt);
    }
    if ($('#data_type').val()=='F') {
      show_or_hide_validation_field(validationCheckboxFieldTypesWithoutTextFields[idx], showValidationFieldsFloat);
    }
    if ($('#data_type').val()=='D') {
      show_or_hide_validation_field(validationCheckboxFieldTypesWithoutTextFields[idx], showValidationFieldsDate);
    }
    if ($('#data_type').val()=='V') {
      show_or_hide_validation_field(validationCheckboxFieldTypesWithoutTextFields[idx], showValidationFieldsVague);
    }
    if ($('#data_type').val()=='B') {
      show_or_hide_validation_field(validationCheckboxFieldTypesWithoutTextFields[idx], showValidationFieldsBool);
    }
  }
  //AVB need to enable and then do this for validation checkboxes that have text fields
}

function show_or_hide_validation_field(validation_field_to_check, validationFieldsAllowedToShow) {
  if (inArray(validation_field_to_check, validationFieldsAllowedToShow)) {
    $('#ctrl-wrap-'+validation_field_to_check).show(); 
  } else {
    $('#ctrl-wrap-'+ validation_field_to_check).hide();
    switch_off_checkbox('#' + validation_field_to_check);  
  }    
}

$('#save-attribute').click(function() {
    if (!$('#data_type').val()) {
      alert('Please select a data type from the drop-down list first');
      return false;
    }
    if ($('#data_type').val()=='L' && !$('#termlist_id').val()) {
      alert('You must select a termlist to use when the Lookup List data type is used for an attribute');
      return false;
    }
    $('#attr-management-controls-container').hide();
    $('#add-new-attribute-button').show();
    $('#attributes-grids-container').show();
    $('#existing-template-control-container').show();
    $('#template-management-controls-container').show();
    $('#display-taxon-section-button').show();
    $('#taxon-controls-container').hide();
    //AVBL, images currently excluded from saving
    var data = {'website_id':indiciaData.website_id};
    for (var idx=0; idx < standardTextFieldTypes.length; idx++) {  
      data = add_standard_text_field_to_save_object(data, standardTextFieldTypes[idx]);
    }
    for (var idx2=0; idx2 < standardDropDownFieldTypes.length; idx2++) {
      add_standard_drop_down_field_to_save_object(data, standardDropDownFieldTypes[idx2]);
    }
    for (var idx3=0; idx3 < standardCheckboxFieldTypes.length; idx3++) {
      add_standard_checkbox_field_to_save_object(data, standardCheckboxFieldTypes[idx3])
    }
    data["validation_rules"]='';
    for (var idx4=0; idx4 < validationCheckboxFieldTypesWithoutTextFields.length; idx4++) {  
      add_validation_checkbox_without_text_fields_to_save_object(data, validationCheckboxFieldTypesWithoutTextFields[idx4]);
    }
    for (var idx5=0; idx5 < validationCheckboxFieldTypesThatHaveTextFields.length; idx5++) {  
      //add_validation_checkbox_with_text_fields_to_save_object(data, validationCheckboxFieldTypesThatHaveTextFields[idx5]);
      //alert('Returned data from add_validation_checkbox_with_text_fields_to_save_object is coming new');
      //alert(data.toSource());
    }
    var proxy_url = indiciaData.taxon_attr_ajax_proxy_url;
    if (data['taxa_taxon_list_attribute:id']=='') {
      delete data['taxa_taxon_list_attribute:id'];
    }
    post_data_to_db(data, proxy_url, 'taxa_taxon_list_attribute');
    //AVBL Need to do this
    //add_attribute_to_list_for_my_taxon_group();
});

function add_standard_text_field_to_save_object(data, field_name) {
  if ($('#taxa_taxon_list_attribute\\:'+field_name).val()) {
    data['taxa_taxon_list_attribute:' + field_name]=$('#taxa_taxon_list_attribute\\:' + field_name).val();
    //AVBOCC
    //data['occurrence_attribute:' + field_name]=data['taxa_taxon_list_attribute:' + field_name];
  } else {
    data['taxa_taxon_list_attribute:' + field_name]=$('#taxa_taxon_list_attribute\\:' + field_name).val();
    //AVBOCC
    //data['occurrence_attribute:' + field_name]=data['taxa_taxon_list_attribute:' + field_name];  
  }
  return data;
}

function add_standard_drop_down_field_to_save_object(data, field_name) {
  //These special cases don't have a prefix at front, not sure why though!
  if (field_name=="data_type" || field_name=="termlist_id") {
    var taxonAttrEntityNamePrefix="";
    //AVBOCC
    //var occurrenceAttrEntityNamePrefix="";
    var jqueryColon="";
    var colonForDb="";
  } else {
    var taxonAttrEntityNamePrefix="taxa_taxon_list_attribute"; 
    //AVBOCC
    //var occurrenceAttrEntityNamePrefix="occurrence_attribute";
    var jqueryColon="\\:";
    var colonForDb=":";
  }
  if ($('#' + taxonAttrEntityNamePrefix + jqueryColon + field_name).val()) {
    data[taxonAttrEntityNamePrefix + colonForDb + field_name]=$('#' + taxonAttrEntityNamePrefix + jqueryColon + field_name).val();  
    //Note that for the field_names without a prefix this line is redundant and just overwrites previous line, but that is ok.
    //AVBOCC
    //data[occurrenceAttrEntityNamePrefix + colonForDb + field_name]=data[taxonAttrEntityNamePrefix + colonForDb + field_name]; 
  }
  return data;
}

function add_standard_checkbox_field_to_save_object(data, field_name) {
  if ($('#taxa_taxon_list_attribute\\:' + field_name).is(":checked")) {
    data['taxa_taxon_list_attribute:' + field_name]="1";
    //AVBOCC
    //data['occurrence_attribute:' + field_name]=data['taxa_taxon_list_attribute:' + field_name];
  } else {
    data['taxa_taxon_list_attribute:' + field_name]="0";
    //AVBOCC
    //data['occurrence_attribute:' + field_name]=data['taxa_taxon_list_attribute:' + field_name];
  }
  return data;
}

function add_validation_checkbox_without_text_fields_to_save_object(data, field_name) {
  if ($('#'+field_name).is(":checked")) {
    data["validation_rules"]+=field_name.replace('valid_','')+'\r\n';
  }
  return data;
}

function add_validation_checkbox_with_text_fields_to_save_object(data, validation_rule) {
  if ($('#'+field_name).is(":checked")) {
    data["validation_rules"]+=validation_rule.replace('valid_','')+'\r\n';
  }
  //AVB, saving lengths and text fields now needs sorting out.
  for (var idx5B=1; idx5B < validation_rule.length; idx5B++) {  
    if ($('#'+validation_rule[idx5B]).val()) {
      data[validation_rule[idx5B]]=$('#' + validation_rule[idx5B]).val();
    } else {
      data[validation_rule[idx5B]]='';  
    }
  }
  return data;
}

$('#add-new-attribute-button').click(function() {
  blank_out_taxon_attr_data_before_load();
  $('html, body').animate({ scrollTop: 0 }, 'fast');
  $('#attr-management-controls-container').show();
  $('#add-new-attribute-button').hide();
  $('#attributes-grids-container').hide();
  $('#existing-template-control-container').hide();
  $('#template-management-controls-container').hide();
  $('#display-taxon-section-button').hide();
  $('#taxon-controls-container').hide();
});

$(document).on('click', '.load-attr-button', function(){
  var txt;
  var r = confirm("Load new attribute for editing? This will overwrite any unsaved changes to the attribute currently being edited.");
  $('#attr-management-controls-container').show();
  $('#attributes-grids-container').hide();
  $('#existing-template-control-container').hide();
  $('#template-management-controls-container').hide();
  $('#taxon-controls-container').hide();
  if (r == true) {
    blank_out_taxon_attr_data_before_load();
    load_data_from_db($(this).attr('attributeId'), 'taxa_taxon_list_attribute')  
  }
});

$('#save-template').click(function() {
  var proxy_url = indiciaData.save_template_ajax_proxy_url;
  data = {
    'website_id': indiciaData.website_id,
    'attribute_set:title': $('#attribute_set\\:title').val(),
    'attribute_set:description': $('#attribute_set\\:description').val()
  }; 
  if ($('#existing_attribute_set').val()) {
    data['attribute_set:id']=$('#existing_attribute_set').val();
  }
  post_data_to_db(data, proxy_url, 'attribute_set'); 
});

$('#delete-template').click(function() {
  var proxy_url = indiciaData.save_template_ajax_proxy_url;
  var id_to_remove = $('#existing_attribute_set').val();
  data = {
    'website_id': indiciaData.website_id,
    'attribute_set:id': id_to_remove,
    'attribute_set:deleted': 't'
  }; 
  $('#existing_attribute_set').find('option[value='+id_to_remove+']').remove();
  $("#attribute_set\\:title").val('');
  $("#attribute_set\\:description").val('');
  post_data_to_db(data,proxy_url, 'attribute_set'); 
});

$('#existing_attribute_set').change(function() {
  if ($('#existing_attribute_set').val()) {
    indiciaData.reports.template_attributes_grid.grid_template_attributes_grid[0].settings.extraParams.attribute_set_id = $('#existing_attribute_set').val(); 
    indiciaData.reports.template_taxa_grid.grid_template_taxa_grid[0].settings.extraParams.attribute_set_id = $('#existing_attribute_set').val(); 
    load_data_from_db($('#existing_attribute_set').val(), 'attribute_set');
  } else {
    // If no template selected then set the id to -1 so that nothing is returned.
    indiciaData.reports.template_attributes_grid.grid_template_attributes_grid[0].settings.extraParams.attribute_set_id = -1; 
    indiciaData.reports.template_taxa_grid.grid_template_taxa_grid[0].settings.extraParams.attribute_set_id = -1; 
    $('#attribute_set\\:title').val('');
    $('#attribute_set\\:description').val('');
  }
  refresh_page_after_save()  
});

$('#save-attributes-into-template').click(function() {
  if (!$('#existing_attribute_set').val()) {
    alert('Please select a group from the drop-down list first');
    return false;
  }
  //Need this url setup
  var proxy_url = indiciaData.save_attributes_into_template_ajax_proxy_url;
  $.each($('.attr-template-selection-checkbox'), function() {
    if ($(this).is(":checked")) {
      data = {
        'website_id': indiciaData.website_id,
        'attribute_sets_taxa_taxon_list_attribute:attribute_set_id': $('#existing_attribute_set').val(),
        'attribute_sets_taxa_taxon_list_attribute:taxa_taxon_list_attribute_id': $(this).val()
      }; 
      post_data_to_db(data,proxy_url, 'attribute_sets_taxa_taxon_list_attribute');
    }
  });
});

$('#save-taxa-into-template').click(function() {
  if (!$('#existing_attribute_set').val()) {
    alert('Please select a group from the drop-down list first');
    return false;
  }
  $.each($('.taxa-template-selection-checkbox'), function() {
    if ($(this).is(":checked")) {
      data = {
        'website_id': indiciaData.website_id,
        //AVB this is wrong, we don't have a response at this point, shouldn't bharded code but don't know
        // how to get this otherwise at the moment
        'attribute_sets_taxon_restriction:attribute_sets_survey_id': $('#existing_attribute_set option:selected').attr('attribute_sets_survey'),
        'attribute_sets_taxon_restriction:restrict_to_taxon_meaning_id': $(this).val()
      }; 
      if ($('#life_stage').val()) {
        data['attribute_sets_taxon_restriction:restrict_to_taxon_meaning_id'] = $('#life_stage').val();
      }
      post_data_to_db(data,indiciaData.save_taxa_into_template_ajax_proxy_url, 'attribute_sets_taxon_restriction');
    }
  });
});

$('#display-taxon-section-button').click(function() {
  $('html, body').animate({ scrollTop: 0 }, 'fast');
  $('#attr-management-controls-container').hide();
  $('#add-new-attribute-button').hide();
  $('#attributes-grids-container').hide();
  $('#existing-template-control-container').show();
  $('#template-management-controls-container').hide();
  $('#display-taxon-section-button').hide();
  $('#taxon-controls-container').show();
  
});

$('#return-to-start-button').click(function() {
  $('html, body').animate({ scrollTop: 0 }, 'fast');
  $('#attr-management-controls-container').hide();
  $('#add-new-attribute-button').show();
  $('#attributes-grids-container').show();
  $('#existing-template-control-container').show();
  $('#template-management-controls-container').show();
  $('#display-taxon-section-button').show();
  $('#taxon-controls-container').hide();
});

function post_data_to_db(data,proxy_url, type) {
  $.post(
    proxy_url,
    //AVBOCC
    data,
    function (response) {
      if (typeof response.success==='undefined') {
        alert('An error occurred whilst saving the data. The error message returned by the server will be displayed next.');
        alert(JSON.stringify(response));
      } else {
        // Only display save alert when it isn't a recursive call to something
        // like taxon_lists_taxa_taxon_list_attribute
        if (type=='taxa_taxon_list_attribute' || 
            type=='attribute_set' ||
            type=='attribute_sets_taxon_restriction'||
            type=='attribute_sets_taxa_taxon_list_attribute') {
          alert('Save complete');
        }
        if (type=='taxa_taxon_list_attribute') {
          lastSavedTaxonAttributeId=response.outer_id
        }
        //AVBOCC
        //if (type=='occurrence_attribute') {
        //  lastSavedOccAttributeId=response.outer_id
        //}
        if (type=='taxa_taxon_list_attribute') {
          // Only need to add this when editing
          if (!data['taxa_taxon_list_attribute:id']) {
            taxon_lists_taxa_taxon_list_attribute_data = {
              'website_id': indiciaData.website_id,
              'taxon_lists_taxa_taxon_list_attribute:taxon_list_id': indiciaData.taxon_list_id,
              'taxon_lists_taxa_taxon_list_attribute:taxa_taxon_list_attribute_id': response.outer_id
            }; 
            post_data_to_db(taxon_lists_taxa_taxon_list_attribute_data, indiciaData.taxon_lists_taxon_attr_ajax_proxy_url, 'taxon_lists_taxa_taxon_list_attribute')    
          }
        }
        //AVBOCC
        if (type=='taxon_lists_taxa_taxon_list_attribute' && $('#add_occ_attr_checkbox').is(":checked")) {
          // This is a special case attribute control which is formatted differently as we are asking user if that want to
    // create an occurrence attribute, we are not trying to save a field of taxa_taxon_list_attribute data
          proxy_url = indiciaData.occ_attr_ajax_proxy_url;
          //AVBH what abount an "ID" for overwriting existing data
          // This will automatically create the occurrence attribute in the database
          occurrence_attributes_taxa_taxon_list_attribute_data = {
            'website_id': indiciaData.website_id,
            //AVBOCC
            //'occurrence_attributes_taxa_taxon_list_attribute:occurrence_attribute_id': response.outer_id,
            'occurrence_attributes_taxa_taxon_list_attribute:taxa_taxon_list_attribute_id': lastSavedTaxonAttributeId,
            'occurrence_attributes_taxa_taxon_list_attribute:restrict_occurrence_attribute_to_single_value': 't'
            //AVB, I think we want to also set the force validate_occurrence_attribute_values_against_taxon_attribute here also
          };
          post_data_to_db(occurrence_attributes_taxa_taxon_list_attribute_data,indiciaData.occ_attr_ttl_attr_ajax_proxy_url, 'occurrence_attributes_taxa_taxon_list_attribute') 
        }  
        if (type=='attribute_set') {
          if (!data['attribute_set:id']) {
            attribute_sets_surveys_data = {
              'website_id': indiciaData.website_id,
              'attribute_sets_survey:survey_id': indiciaData.survey_id,
              //AVB is there a bug here in that we need an existing attribute set, when a new one should do
              'attribute_sets_survey:attribute_set_id': response.outer_id,
              //'attribute_sets_survey:attribute_set_id': $('#existing_attribute_set').val()
            };

            $("#existing_attribute_set").append('<option value="'+response.outer_id+'">'+$('#attribute_set\\:title').val()+'</option>');
            post_data_to_db(attribute_sets_surveys_data,indiciaData.save_attribute_sets_survey_ajax_proxy_url, 'attribute_sets_survey');
          } else {
            $('#existing_attribute_set :selected').text($('#attribute_set\\:title').val());
            $('#existing_attribute_set :selected').val(response.outer_id);
          }
        }
        if (type=='attribute_sets_survey') {
          $('#existing_attribute_set [value='+data['attribute_sets_survey:attribute_set_id']+']').attr('attribute_sets_survey',response.outer_id);
        }
        refresh_page_after_save();
      }
    },
    'json'
  );
}

function refresh_page_after_save() {
  indiciaData.reports.all_attributes_grid.grid_all_attributes_grid.reload();
  indiciaData.reports.template_attributes_grid.grid_template_attributes_grid.ajaxload();   
  //indiciaData.reports.template_attributes_grid.grid_template_attributes_grid.reload();  
  indiciaData.reports.all_taxa_grid.grid_all_taxa_grid.reload();
  indiciaData.reports.template_taxa_grid.grid_template_taxa_grid.ajaxload(); 
  //indiciaData.reports.template_taxa_grid.grid_template_taxa_grid.reload(); 
}

function load_data_from_db(id_for_data_load, table) {
  $.getJSON(indiciaData.indiciaSvc + "index.php/services/data/" + table + "?id=" + id_for_data_load +
    "&mode=json&view=list&callback=?&auth_token=" + indiciaData.readAuth.auth_token + "&nonce=" + indiciaData.readAuth.nonce, 
    function(data) {
      // As we are only ever loading one record, it will always be data[0], cycle through each field
      if (table==='taxa_taxon_list_attribute') {
        $.each(data[0], function( key, value ) { 
          // Decode string if it is the validation rules special case
          if (key === 'validation_rules') {
            // Split the results up as they are stored on different lines
            var arrayOfValidationRules;
            if (value) {
              arrayOfValidationRules = value.match(/[^\r\n]+/g);
            } else {
              arrayOfValidationRules = [];
            }
            // Cycle through each validation rule
            if (arrayOfValidationRules) {
              for (var validation_rules_idx=0; validation_rules_idx<arrayOfValidationRules.length; validation_rules_idx++) {
                load_validation_field(arrayOfValidationRules[validation_rules_idx]);
              }
            }
          } else {    
            // Load the data into an non-valication field
            load_standard_field(key, value, table)
          }
        });
      } else if (table==='attribute_set') {
        $('#attribute_set\\:title').val(data[0].title);
        $('#attribute_set\\:description').val(data[0].description);
      }
    }
  );  
}

function blank_out_taxon_attr_data_before_load() {
  for (var idx=0; idx < standardTextFieldTypes.length; idx++) {
    $('#taxa_taxon_list_attribute\\:' + standardTextFieldTypes[idx]).val("");
  }
  for (var idx=0; idx < standardDropDownFieldTypes.length; idx++) {
    //These special cases don't have a prefix at front, not sure why though!
    if (standardDropDownFieldTypes[idx]=="data_type" || standardDropDownFieldTypes[idx]=="termlist_id") {
      var taxonAttrEntityNamePrefix="";
      //AVBOCC
      //var occurrenceAttrEntityNamePrefix="";
      var jqueryColon="";
      var colonForDb="";
    } else {
      var taxonAttrEntityNamePrefix="taxa_taxon_list_attribute"; 
      //AVBOCC
      //var occurrenceAttrEntityNamePrefix="occurrence_attribute";
      var jqueryColon="\\:";
      var colonForDb=":";
    }
    $('#' + taxonAttrEntityNamePrefix + jqueryColon + standardDropDownFieldTypes[idx]).val(null).change();
  }
  for (var idx=0; idx < standardCheckboxFieldTypes.length; idx++) {
    switch_off_checkbox('#taxa_taxon_list_attribute\\:' + standardCheckboxFieldTypes[idx]);
  }
  for (var idx=0; idx < validationCheckboxFieldTypesWithoutTextFields.length; idx++) { 
    switch_off_checkbox('#' + validationCheckboxFieldTypesWithoutTextFields[idx]);
  }
  for (var idx=0; idx < validationCheckboxFieldTypesThatHaveTextFields.length; idx++) {
    switch_off_checkbox('#' + validationCheckboxFieldTypesThatHaveTextFields[idx][0]);
    for (var idx2=1; idx2 < validationCheckboxFieldTypesThatHaveTextFields[idx].length; idx2++) {
      $('#'+validationCheckboxFieldTypesThatHaveTextFields[idx][idx2]).val('');
    }
  }
}

function switch_off_checkbox(selector) {
  $(selector).prop('checked', false);
  $(selector).val(0);   
}

function load_validation_field(specificValidationRule) {
  var specificValidationRuleArray = get_array_of_specific_validation_rule(specificValidationRule);
  $('#valid_' + specificValidationRuleArray[0]).prop('checked', true);
  $('#valid_' + specificValidationRuleArray[0]).val(1);  
  // If there is a value assoicated with the rule
  if (specificValidationRuleArray[1]) {
    // Then cycle through the values (noting we starting at index 1 in the array, as 0 in the validation rule name
    for (var idx=1; idx < specificValidationRuleArray.length; idx++) {
      load_validation_value_into_textbox(specificValidationRuleArray[0], idx, specificValidationRuleArray[idx])
    }    
  }
}

function load_validation_value_into_textbox(rule_name, value_position, value) {
  var rule_name_control_id='valid_'+rule_name;
  // Find the validation rule we are working on
  for (var idx=0; idx < validationCheckboxFieldTypesThatHaveTextFields.length; idx++) {
    //if we find a match
    //AVBL need to test regex that includes [, or ,
    if (validationCheckboxFieldTypesThatHaveTextFields[idx][0]===rule_name_control_id) {
      // The validationCheckboxFieldTypesThatHaveTextFields array sub-array has the value field names in the same
      // position as the values from the DB, so we can use the same position if the different arrays to get the ID and associated value
      $('#' + validationCheckboxFieldTypesThatHaveTextFields[idx][value_position]).val(value);   
    }      
  }   
}

function get_array_of_specific_validation_rule(specificValidationRule) {
  var specificValidationRuleArray;
  specificValidationRule = specificValidationRule.replace('[', ',');
  specificValidationRule = specificValidationRule.replace(']', '');
  // Remove all white space just in case  
  specificValidationRule = specificValidationRule.replace(' ', ''); 
  specificValidationRuleArray = specificValidationRule.split(",");
  return specificValidationRuleArray;
}

function load_standard_field(key, value, table) {
  // Is it a text field
  if (inArray(key, standardTextFieldTypes)) {
    load_text_field(key, value, table);
  }    
  // Is it a drop-down
  if (inArray(key, standardDropDownFieldTypes)) {
    load_drop_down_field(key, value, table);
  }
  if (inArray(key, standardCheckboxFieldTypes)) {
    load_checkbox_field(key, value, table);
  }    
}

function load_text_field(key, value, table) {
    $('#'+ table + '\\:' + key).val(value);
}

function load_drop_down_field(key, value, table) {
  $('#'+key).val(value).change();
}

function load_checkbox_field(key, value, table) {
  if (value=='t') {
    $('#'+ table + '\\:' + key).prop('checked', true);
    $('#'+ table + '\\:' + key).val(1);
  } else if (value=='f') {
    switch_off_checkbox('#'+ table + '\\:' + key);
  }    
}
 
  
function add_new_attribute_to_existing_list_for_template() {

}

});
