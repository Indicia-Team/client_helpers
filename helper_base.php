<?php

/**
 * @file
 * Base class for client helper classes.
 *
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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/Indicia-Team/client_helpers
 */

if (file_exists(dirname(__FILE__) . '/helper_config.php')) {
  require_once 'helper_config.php';
}
require_once 'lang.php';

global $indicia_templates;

/**
 * Provides control templates to define the output of the data entry helper class.
 */
$indicia_templates = [
  'blank' => '',
  'prefix' => '',
  'formControlClass' => 'form-control',
  'controlWrap' => "<div id=\"ctrl-wrap-{id}\" class=\"form-row ctrl-wrap{wrapClasses}\">{control}</div>\n",
  'controlWrapErrorClass' => '',
  // Template for control with associated buttons/icons to appear to the side.
  'controlAddonsWrap' => "{control}{addons}",
  'justControl' => "{control}\n",
  'label' => '<label for="{id}"{labelClass}>{label}:</label>',
  // Use if label ends with another punctuation mark.
  'labelNoColon' => '<label for="{id}"{labelClass}>{label}</label>',
  'labelAfter' => '<label for="{id}"{labelClass}>{label}</label>',
  'toplabel' => '<label data-for="{id}"{labelClass}>{label}:</label>',
  'toplabelNoColon' => '<label data-for="{id}"{labelClass}>{label}</label>',
  'suffix' => "\n",
  'requiredsuffix' => "<span class=\"deh-required\">*</span>",
  'button' => '<button id="{id}" type="button" title="{title}"{class}>{caption}</button>',
  // Button classes. If changing these, keep the indicia-button class to ensure functionality works.
  'buttonDefaultClass' => 'indicia-button',
  'buttonHighlightedClass' => 'indicia-button',
  'buttonWarningClass' => 'indicia-button',
  'buttonSmallClass' => 'btn-xs',
  // Classes applied to <a> when styled like a button.
  'anchorButtonClass' => 'indicia-button',
  'submitButton' => '<input id="{id}" type="submit"{class} name="{name}" value="{caption}" />',
  // Floats.
  'floatLeftClass' => 'left',
  'floatRightClass' => 'right',
  // Message boxes.
  'messageBox' => '<div class="page-notice ui-state-default ui-corner-all">{message}</div>',
  'warningBox' => '<div class="page-notice ui-state-highlight ui-corner-all"><span class="fas fa-exclamation-triangle"></span>{message}</div>',
  // Lock icons.
  'lock_icon' => '<span id="{id}_lock" class="unset-lock">&nbsp;</span>',
  'lock_javascript' => "indicia.locks.initControls (
      \"" . lang::get('locked tool-tip') . "\",
      \"" . lang::get('unlocked tool-tip') . "\",
      \"{lock_form_mode}\"
      );\n",
  'validation_message' => "<p class=\"{class}\">{error}</p>\n",
  'validation_icon' => '<span class="ui-state-error ui-corner-all validation-icon"><span class="ui-icon ui-icon-alert"></span></span>',
  'error_class' => 'inline-error',
  'invalid_handler_javascript' => "function(form, validator) {
          var tabselected=false;
          jQuery.each(validator.errorMap, function(ctrlId, error) {
            // select the tab containing the first error control
            var ctrl = jQuery('[name=' + ctrlId.replace(/:/g, '\\\\:').replace(/\[/g, '\\\\[').replace(/\\]/g, '\\\\]') + ']');
            if (!tabselected) {
              var tp=ctrl.filter('input,select,textarea').closest('.ui-tabs-panel');
              if (tp.length===1) {
                indiciaFns.activeTab($(tp).parent(), tp.id);
              }
              tabselected = true;
            }
            ctrl.parents('fieldset').removeClass('collapsed');
            ctrl.parents('.fieldset-wrapper').show();
          });
        }",
  'image_upload' => "<input type=\"file\" id=\"{id}\" name=\"{fieldname}\" accept=\"png|jpg|gif|jpeg|mp3|wav\" {title}/>\n" .
      "<input type=\"hidden\" id=\"{pathFieldName}\" name=\"{pathFieldName}\" value=\"{pathFieldValue}\"/>\n",
  'text_input' => '<input {attribute_list} id="{id}" name="{fieldname}"{class} {disabled} {readonly} value="{default|escape}" {title} {maxlength} />'."\n",
  'hidden_text' => '<input type="hidden" id="{id}" name="{fieldname}" {disabled} value="{default}" />',
  'password_input' => '<input type="password" id="{id}" name="{fieldname}"{class} {disabled} value="{default}" {title} />'."\n",
  'textarea' => '<textarea id="{id}" name="{fieldname}"{class} {disabled} cols="{cols}" rows="{rows}" {title}>{default}</textarea>'."\n",
  'checkbox' => '<input type="hidden" name="{fieldname}" value="0"/><input type="checkbox" id="{id}" name="{fieldname}" value="1"{class}{checked}{disabled} {title} />'."\n",
  'training' => '<input type="hidden" name="{fieldname}" value="{hiddenValue}"/><input type="checkbox" id="{id}" name="{fieldname}" value="1"{class}{checked}{disabled} {title} />'."\n",
  'date_picker' => '<input type="text" {attribute_list} {class} id="{id}" name="{fieldname}" value="{default}" style="display: none" {title}/>
      <input type="date" {attribute_list_date} class="{datePickerClass}" id="{id}:date">' . "\n",
  'date_picker_mode_toggle' => '<span>{vagueLabel}:</span> <label class="switch">
        <input type="checkbox" class="date-mode-toggle" id="{id}:toggle">
        <span class="slider round"></span>
      </label>' . "\n",
  'select' => '<select {attribute_list} id="{id}" name="{fieldname}"{class} {disabled} {title}>{items}</select>',
  'select_item' => '<option value="{value}"{selected}{attribute_list}>{caption}</option>',
  'select_species' => '<option value="{value}" {selected} >{caption} - {common}</option>',
  'listbox' => '<select id="{id}" name="{fieldname}"{class} {disabled} size="{size}" multiple="{multiple}" {title}>{items}</select>',
  'listbox_item' => '<option value="{value}"{selected}{attribute_list}>{caption}</option>',
  'list_in_template' => '<ul{class} {title}>{items}</ul>',
  'check_or_radio_group' => '<ul {class} id="{id}">{items}</ul>',
  'check_or_radio_group_item' => '<li>{sortHandle}<input type="{type}" name="{fieldname}" id="{itemId}" value="{value}"{class}{checked}{title} {disabled}/><label for="{itemId}">{caption}</label></li>',
  'map_panel' => '<div id="map-container" style="width: {width};"><div id="map-loading" class="loading-spinner" style="display: none"><div>Loading...</div></div><div id="{divId}" style="width: {width}; height: {height};"{class}></div></div>',
  'georeference_lookup' => '<input type="text" id="imp-georef-search"{class} />{searchButton}' .
    '<div id="imp-georef-div" class="ui-corner-all ui-widget-content ui-helper-hidden">' .
    '<div id="imp-georef-output-div"></div> ' .
    '{closeButton}' .
    '</div>',
  'tab_header' => "<ul class=\"tab-header\">{tabs}</ul>\n",
  'taxon_label' => '<div class="biota"><span class="nobreak sci binomial"><em class="taxon-name">{taxon}</em></span> {authority} '.
      '<span class="nobreak vernacular">{default_common_name}</span></div>',
  'single_species_taxon_label' => '{taxon}',
  'treeview_node' => '<span>{caption}</span>',
  'tree_browser' => '<div{outerClass} id="{divId}"></div><input type="hidden" name="{fieldname}" id="{id}" value="{default}"{class}/>',
  'tree_browser_node' => '<span>{caption}</span>',
  'autocomplete' => '<input type="hidden" class="hidden" id="{id}" name="{fieldname}" value="{default}" />' .
      '<input id="{inputId}" name="{inputId}" type="text" value="{defaultCaption}" {class} {disabled} {title} {attribute_list}/>' . "\n",
  'autocomplete_javascript' => "
$('input#{escaped_input_id}').change(function() {
  if ($('input#{escaped_id}').data('set-for') !== $('input#{escaped_input_id}').val()) {
    $('input#{escaped_id}').val('');
  }
});
$('input#{escaped_input_id}').autocomplete('{url}',
  {
    extraParams : {
      orderby : '{captionField}',
      mode : 'json',
      qfield : '{captionField}',
      {sParams}
    },
    simplify: {simplify},
    selectMode: {selectMode},
    warnIfNoMatch: {warnIfNoMatch},
    continueOnBlur: {continueOnBlur},
    matchContains: {matchContains},
    parse: function(data)
    {
      // Clear the current selected key as the user has changed the search text
      $('input#{escaped_id}').val('');
      var results = [], done = [];
      $.each(data, function(i, item) {
        if ({duplicateCheck}) {
          results.push({
            'data' : item,
            'result' : item.{captionField},
            'value' : item.{valueField}
          });
          {storeDuplicates}
        }
      });
      return results;
    },
  formatItem: {formatFunction}
  {max}
});
$('input#{escaped_input_id}').result(function(event, data) {
  $('input#{escaped_id}').attr('value', data.{valueField});
  // Remember what text string this value was for.
  $('input#{escaped_id}').data('set-for', $('input#{escaped_input_id}').val());
  $('.item-icon').remove();
  if (typeof data.icon!=='undefined') {
    $('input#{escaped_input_id}').after(data.icon).next().hover(indiciaFns.hoverIdDiffIcon);
  }
  $('input#{escaped_id}').trigger('change', data);
});
",
  'autocomplete_new_taxon_form' => '
<div style="display: none">
  <fieldset class="popup-form" id="new-taxon-form">
    <legend>{title}</legend>
    <p>{helpText}</p>
    <label for="new-taxon-name">Taxon name:</label>
    <input type="text" id="new-taxon-name" class="{required:true}"/><span class="deh-required">*</span><br />
    <label for="new-taxon-group">Taxon group:</label>
    <select id="new-taxon-group" class="{required:true}"><span class="deh-required">*</span>
      {taxonGroupOpts}
    </select><br/>
    <button type="button" class="indicia-button" id="do-add-new-taxon">Add taxon</button>
  </fieldset>
</div>
  ',
  'sub_list' => '<div id="{id}:box" class="control-box wide"><div>'."\n".
    '<div>'."\n".
    "{panel_control}\n".
    '</div>'."\n".
    '<ul id="{id}:sublist" class="ind-sub-list">{items}</ul>{subListAdd}'."\n".
    '</div></div>'."\n",
  'sub_list_item' => '<li class="ui-widget-content ui-corner-all"><span class="ind-delete-icon">&nbsp;</span>{caption}'.
    '<input type="hidden" name="{fieldname}" value="{value}" /></li>',
  'postcode_textbox' => '<input type="text" name="{fieldname}" id="{id}"{class} value="{default}" '.
        'onblur="javascript:indiciaFns.decodePostcode(\'{linkedAddressBoxId}\');" />'."\n",
  'sref_textbox' => '<input type="text" id="{id}" name="{fieldname}" {class} {disabled} value="{default}" />' .
        '<input type="hidden" id="{geomid}" name="{geomFieldname}" value="{defaultGeom}" />'."\n",
  'sref_textbox_latlong' => '<label for="{idLat}">{labelLat}:</label>'.
        '<input type="text" id="{idLat}" name="{fieldnameLat}" {class} {disabled} value="{defaultLat}" /><br />' .
        '<label for="{idLong}">{labelLong}:</label>'.
        '<input type="text" id="{idLong}" name="{fieldnameLong}" {class} {disabled} value="{defaultLong}" />' .
        '<input type="hidden" id="{geomid}" name="geomFieldname" value="{defaultGeom}" />'.
        '<input type="hidden" id="{id}" name="{fieldname}" value="{default}" />',
  'attribute_cell' => "\n<td class=\"scOccAttrCell ui-widget-content {class}\" headers=\"{headers}\">{content}</td>",
  'taxon_label_cell' => "\n<td class=\"scTaxonCell{editClass}\" headers=\"{tableId}-species-{idx}\" {colspan}>{content}</td>",
  'helpText' => "\n<p class=\"{helpTextClass}\">{helpText}</p>",
  'file_box' => '',                   // the JQuery plugin default will apply, this is just a placeholder for template overrides.
  'file_box_initial_file_info' => '', // the JQuery plugin default will apply, this is just a placeholder for template overrides.
  'file_box_uploaded_image' => '',    // the JQuery plugin default will apply, this is just a placeholder for template overrides.
  'paging_container' => "<div class=\"pager ui-helper-clearfix\">\n{paging}\n</div>\n",
  'paging' => '<div class="left">{first} {prev} {pagelist} {next} {last}</div><div class="right">{showing}</div>',
  'jsonwidget' => '<div id="{id}" {class}></div>',
  'report_picker' => '<div id="{id}" {class}>{reports}<div class="report-metadata"></div><button type="button" id="picker-more">{moreinfo}</button><div class="ui-helper-clearfix"></div></div>',
  'report_download_link' => '<div class="report-download-link"><a href="{link}"{class}>{caption}</a></div>',
  'verification_panel' => '<div id="verification-panel">{button}<div class="messages" style="display: none"></div></div>',
  'two-col-50' => '<div class="two columns"{attrs}><div class="column">{col-1}</div><div class="column">{col-2}</div></div>',
  'loading_overlay' => '<div class="loading-spinner" style="display: none"><div>Loading...</div></div>',
  'report-table' => '<table{class}>{content}</table>',
  'report-thead' => '<thead{class}>{content}</thead>',
  'report-thead-tr' => '<tr{class}{title}>{content}</tr>',
  'report-thead-th' => '<th>{content}</th>',
  'report-tbody' => '<tbody>{content}</tbody>',
  'report-tbody-tr' => '<tr{class}{rowId}{rowTitle}>{content}</tr>',
  'report-tbody-td' => '<td{class}>{content}</td>',
  'report-action-button' => '<a{class}{href}{onclick}>{content}</a>',
  'data-input-table' => '<table{class}{id}>{content}</table>',
  'review_input' => '<div{class}{id}><div{headerClass}{headerId}>{caption}</div>
<div id="review-map-container"></div>
<div{contentClass}{contentId}></div>
</div>',
  // Rows in a list of key-value pairs.
  'dataValueList' => '<div class="detail-panel" id="{id}"><h3>{title}</h3><dl class="dl-horizontal">{content}</dl></div>',
  'dataValue' => '<dt>{caption}</dt><dd{class}>{value}</dd>',
  'speciesDetailsThumbnail' => '<div class="gallery-item"><a data-fancybox="gallery" href="{imageFolder}{the_text}"><img src="{imageFolder}{imageSize}-{the_text}" title="{caption}" alt="{caption}"/><br/>{caption}</a></div>',
];

/**
 * Base class for the report and data entry helpers. Provides several generally useful methods and also includes
 * resource management.
 */
class helper_base {

  /*
   * Variables that can be specified in helper_config.php, or should be set by
   * the host system.
   */

  /**
   * Base URL of the warehouse we are linked to.
   *
   * @var string
   */
  public static $base_url = '';

  /**
   * Path to proxy script for calls to the warehouse.
   *
   * Allows the warehouse to sit behind a firewall only accessible from the
   * server.
   *
   * @var string
   */
  public static $warehouse_proxy = NULL;

  /**
   * Base URL of the GeoServer we are linked to if GeoServer is used.
   *
   * @var string
   */
  public static $geoserver_url = '';

  /**
   * A temporary location for uploaded images.
   *
   * Images are stored here when uploaded by a recording form but before they
   * are sent to the warehouse.
   *
   * @var string
   */
  public static $interim_image_folder;

  /**
   * Google API key for place searches.
   *
   * @var string
   */
  public static $google_api_key = '';

  /**
   * Google Maps JavaScript API key.
   *
   * @var string
   */
  public static $google_maps_api_key = '';

  /**
   * Bing Maps API key.
   *
   * @var string
   */
  public static $bing_api_key = '';

  /**
   * Ordnance Survey Maps API key.
   *
   * @var string
   */
  public static $os_api_key = '';

  /**
   * Breadcrumb info.
   *
   * @var array
   */
  public static $breadcrumb = NULL;

  /**
   * Force breadcrumb to display even on non-node based pages.
   *
   * @var array
   */
  public static $force_breadcrumb = FALSE;

  /**
   * Setting which allows the host site (e.g. Drupal) handle translation.
   *
   * For example, when TRUE, a call to lang::get() is delegated to Drupal's t()
   * function.
   *
   * @var bool
   */
  public static $delegate_translation_to_hostsite = FALSE;

  /**
   * Setting which allows the host site (e.g. Drupal) handle caching.
   *
   * Defaults to true but only delegates if there are hostsite_cache_get() and
   * hostsite_cache_get() functions available.
   *
   * @var bool
   */
  public static $delegate_caching_to_hostsite = TRUE;

  /**
   * Check on maximum file size for image uploads.
   *
   * @var string
   */
  public static $upload_max_filesize = '4M';

  /*
   * End of variables that can be specified in helper_config.php.
   */

  /**
   * @var boolean Flag set to true if returning content for an AJAX request. This allows the javascript to be returned
   * direct rather than embedding in document.ready and window.onload handlers.
   */
  public static $is_ajax = FALSE;

  /**
   * @var integer Website ID, stored here to assist with caching.
   */
  public static $website_id = NULL;

  /**
   * @var Array List of resources that have been identified as required by the
   * controls used. This defines the JavaScript and stylesheets that must be
   * added to the page. Each entry is an array containing stylesheets and
   * javascript sub-arrays. This has public access so the Drupal module can
   * perform Drupal specific resource output.
   */
  public static $required_resources = [];

  /**
   * @var Array List of all available resources known. Each resource is named, and contains a sub array of
   * deps (dependencies), stylesheets and javascripts.
   */
  public static $resource_list = NULL;

  /**
   * Any control that wants to access the read authorisation tokens from JavaScript can set them here. They will then
   * be available from indiciaData.auth.read.
   * @var Array
   */
  public static $js_read_tokens = NULL;

  /**
   * @var string Path to Indicia JavaScript folder. If not specified, then it is
   * calculated from the Warehouse $base_url.
   * This path should be a full path on the server (starting with '/' exluding
   * the domain and ending with '/').
   */
  public static $js_path = NULL;

  /**
   * @var string Path to Indicia CSS folder. If not specified, then it is calculated from the Warehouse $base_url.
   * This path should be a full path on the server (starting with '/' exluding the domain).
   */
  public static $css_path = NULL;

  /**
   * Path to Indicia Images folder.
   *
   * @var string
   */
  public static $images_path = NULL;

  /**
   * Path to Indicia cache folder. Defaults to client_helpers/cache.
   *
   * @var string
   */
  public static $cache_folder = FALSE;

  /**
   * List of resources that have already been dumped out.
   *
   * Avoids duplication. For example, if the site template includes JQuery set
   * $dumped_resources[]='jquery'.
   *
   * @var array
   */
  public static $dumped_resources = [];

  /**
   * Data to be added to the indiciaData JavaScript variable.
   *
   * @var array
   */
  public static $indiciaData = [
    'lang' => [],
    'templates' => [],
  ];

  /**
   * Inline JavaScript to be added to the page.
   *
   * Each control that needs custom JavaScript can append the script to this
   * variable. Will be enclosed in a document ready event hander.
   *
   * @var string
   */
  public static $javascript = '';

  /**
   * JavaScript text to be emitted after all other JavaScript.
   *
   * @var string
   */
  public static $late_javascript = '';

  /**
   * JavaScript text to be emitted during window.onload.
   *
   * @var string
   */
  public static $onload_javascript = '';

  /**
   * Setting to completely disable loading from the cache.
   *
   * @var bool
   */
  public static $nocache = FALSE;

  /**
   * Age of image files in seconds before they will be considered for purging.
   *
   * @var int
   */
  public static $interim_image_expiry = 14400;

  /**
   * File types and extensions allowed for upload.
   *
   * Contains elements for each media type that can be uploaded. Each element
   * is an array of allowed file extensions for that media type. Used for
   * filtering files to upload on client side. File extensions must be in lower
   * case. Each entry should have its mime type included in $upload_mime_types.
   *
   * @var array
   */
  public static $upload_file_types = [
    'image' => ['jpg', 'gif', 'png', 'jpeg'],
    'pdf' => ['pdf'],
    'audio' => ['mp3', 'wav'],
    'zerocrossing' => ['zc'],
  ];

  /**
   * Mime types allowed for upload.
   *
   * Contains elements for each media type that can be uploaded. Each element
   * is an array of the allowed mime subtypes for that media type. Used for
   * testing uploaded files. Each entry in $upload_file_types should have its
   * mime type in this list.
   *
   * @var array
   */
  public static $upload_mime_types = [
    'image' => ['jpeg', 'gif', 'png'],
    'application' => ['pdf', 'octet-stream'],
    'audio' => ['mpeg', 'x-wav'],
  ];

  /**
   * List of methods used to report a validation failure. Options are message, message, hint, icon, colour, inline.
   * The inline option specifies that the message should appear on the same line as the control.
   * Otherwise it goes on the next line, indented by the label width. Because in many cases, controls
   * on an Indicia form occupy the full available width, it is often more appropriate to place error
   * messages on the next line so this is the default behaviour.
   * @var array
   */
  public static $validation_mode = ['message', 'colour'];

  /**
   * Name of the form which has been set up for jQuery validation, if any.
   *
   * @var array
   */
  public static $validated_form_id = NULL;

  /**
   * jQuery Validation should only initialise once.
   *
   * @var bool
   */
  private static $validationInitialised = FALSE;

  /**
   * @var string Helptext positioning. Determines where the information is displayed when helpText is defined for a control.
   * Options are before, after.
   */
  public static $helpTextPos = 'after';

  /**
   * Form Mode. Initially unset indicating new input, but can be set to ERRORS or RELOAD.
   *
   * @var string
   */
  public static $form_mode = NULL;

  /**
   * List of all error messages returned from an attempt to save.
   *
   * @var array
   */
  public static $validation_errors = NULL;

  /**
   * Default validation rules to apply to the controls on the form.
   *
   * Used if the built in client side validation is used (with the jQuery
   * validation plugin). This array can be replaced if required.
   *
   * @var array
   *
   * @todo This array could be auto-populated with validation rules for a
   * survey's fields from the Warehouse.
   */
  public static $default_validation_rules = [
    'sample:date' => ['required', 'date'],
    'sample:entered_sref' => ['required'],
    'occurrence:taxa_taxon_list_id' => ['required'],
    'location:name' => ['required'],
    'location:centroid_sref' => ['required'],
  ];

  /**
   * List of messages defined to pass to the validation plugin.
   *
   * @var array
   */
  public static $validation_messages = [];


  /**
   * Length of time in seconds after which cached Warehouse responses will start to expire.
   *
   * @var int
   */
  public static $cache_timeout = 3600;

  /**
   * Chance of a cached file being refreshed after expiry.
   *
   * On average, every 1 in $cache_chance_expire times the Warehouse is called
   * for data which is cached but older than the cache timeout, the cached data
   * will be refreshed. This introduces a random element to cache refreshes so
   * that no single form load event is responsible for refreshing all cached
   * content.
   *
   * @var int
   */
  public static $cache_chance_refresh_file = 10;

  /**
   * Chance of a cache purge evemt.
   *
   * On average, every 1 in $cache_chance_purge times the Warehouse is called
   * for data, all files older than 5 times the cache_timeout will be purged,
   * apart from the most recent $cache_allowed_file_count files.
   *
   * @var int
   */
  public static $cache_chance_purge = 500;

  /**
   * Number of recent files allowed in the cache.
   *
   * Number of recent files allowed in the cache which the cache will not
   * bother clearing during a deletion operation. They will be refreshed
   * occasionally when requested anyway.
   *
   * @var int
   */
  public static $cache_allowed_file_count = 50;

  /**
   * A place to keep data and settings for Indicia code, to avoid using globals.
   *
   * @var array
   */
  public static $data = [];

  /*
   * Global format for display of dates such as sample date, date attributes in Drupal.
   * Note this only affects the loading of the date itself when a form in edit mode loads, the format displayed as soon as the
   * date picker is selected is determined by Drupal's settings. So make sure Drupal's date format and this option match up.
   * @todo Need to create a proper config option for this.
   * @todo Need to ensure this setting is utilised every where it should be.
   *
   */
  public static $date_format = 'd/m/Y';

  /**
   * Indicates if any form controls have specified the lockable option.
   *
   * If so, we will need to output some javascript.
   *
   * @var bool
   */
  protected static $using_locking = FALSE;

  /**
   * Are we linking in the default stylesheet?
   *
   * Handled sligtly different to the others so it can be added to the end of
   * the list, allowing our CSS to override other stuff.
   *
   * @var bool
   */
  protected static $default_styles = FALSE;

  /**
   * Array of html attributes. When replacing items in a template, these get automatically wrapped. E.g.
   * a template replacement for the class will be converted to class="value". The key is the parameter name,
   * and the value is the html attribute it will be wrapped into.
   */
  protected static $html_attributes = [
    'class' => 'class',
    'outerClass' => 'class',
    'selected' => 'selected',
  ];

  /**
   * List of error messages that have been displayed.
   *
   * So we don't duplicate them when dumping any remaining ones at the end.
   *
   * @var array
   */
  protected static $displayed_errors = [];

  /**
   * Track if we have already output the indiciaFunctions.
   *
   * @var bool
   */
  protected static $indiciaFnsDone = FALSE;

  /**
   * Returns the URL to access the warehouse by, respecting proxy settings.
   *
   * @return string
   *   URL.
   */
  public static function getProxiedBaseUrl() {
    return empty(self::$warehouse_proxy) ? self::$base_url : self::$warehouse_proxy;
  }

  /**
   * Returns the folder to store uploaded images in before submission.
   *
   * When an image has been uploaded on a form but not submitted to the
   * warehouse, it is stored in this folder location temporarily.
   *
   * @param string $mode
   *   Set to one of these options:
   *   * fullpath - full path from root of the server disk.
   *   * domain - path from the root of the domain.
   *   * relative - path relative to the current script location (default)
   *
   * @return string
   *   The folder location.
   */
  public static function getInterimImageFolder($mode = 'relative') {
    $folder = isset(self::$interim_image_folder)
      ? self::$interim_image_folder
      : self::client_helper_path() . 'upload/';
    switch ($mode) {
      case 'fullpath':
        return getcwd() . '/' . $folder;

      case 'domain':
        return self::getRootFolder() . $folder;

      default:
        return $folder;
    }
  }

  /**
   * Utility function to insert a list of translated text items for use in JavaScript.
   *
   * @param string $group
   *   Name of the group of strings.
   * @param array $strings
   *   Associative array of keys and texts to translate.
   */
  public static function addLanguageStringsToJs($group, array $strings) {
    $translations = [];
    foreach ($strings as $key => $text) {
      $translations[$key] = lang::get($text);
    }
    self::$indiciaData['lang'][$group] = $translations;
  }

  /**
   * Utility function to convert a list of strings into translated strings.
   *
   * @param array $strings
   *   Associative array of keys and texts to translate.
   *
   * @return array
   *   Associative array of translated strings keyed by untranslated string.
   */
  public static function getTranslations(array $strings) {
    $r = [];
    foreach ($strings as $string) {
      $r[$string] = lang::get($string);
    }
    return $r;
  }

  /**
   * Adds a resource to the page (e.g. a set of CSS and/or JS files).
   *
   * Method to link up the external css or js files associated with a set of code.
   * This is normally called internally by the control methods to ensure the required
   * files are linked into the page so does not need to be called directly. However
   * it can be useful when writing custom code that uses one of these standard
   * libraries such as jQuery.
   * Ensures each file is only linked once and that dependencies are included
   * first and in the order given.
   *
   * @param string $resource
   *   Name of resource to link. The following options are available:
   *   * indiciaFns
   *   * jquery
   *   * datepicker
   *   * sortable
   *   * openlayers
   *   * graticule
   *   * clearLayer
   *   * addrowtogrid
   *   * speciesFilterPopup
   *   * import
   *   * indiciaMapPanel
   *   * indiciaMapEdit
   *   * postcode_search
   *   * locationFinder
   *   * createPersonalSites
   *   * autocomplete
   *   * addNewTaxon
   *   * indicia_locks
   *   * jquery_cookie
   *   * jquery_ui
   *   * jquery_form
   *   * json
   *   * reportPicker
   *   * treeview
   *   * treeview_async
   *   * googlemaps
   *   * multimap
   *   * virtualearth
   *   * fancybox
   *   * treeBrowser
   *   * defaultStylesheet
   *   * validation
   *   * plupload
   *   * dmUploader
   *   * uploader
   *   * jqplot
   *   * jqplot_bar
   *   * jqplot_pie
   *   * jqplot_category_axis_renderer
   *   * jqplot_canvas_axis_label_renderer
   *   * jqplot_trendline
   *   * reportgrid
   *   * freeformReport
   *   * tabs
   *   * wizardprogress
   *   * spatialReports
   *   * jsonwidget
   *   * timeentry
   *   * verification
   *   * complexAttrGrid
   *   * footable
   *   * indiciaFootableReport
   *   * indiciaFootableChecklist
   *   * html2pdf
   *   * review_input
   *   * sub_list
   *   * georeference_default_geoportal_lu
   *   * georeference_default_nominatim
   *   * georeference_default_google_places
   *   * georeference_default_indicia_locations
   *   * sref_handlers_4326
   *   * sref_handlers_osgb
   *   * sref_handlers_osie
   *   * font_awesome
   *   * leaflet
   *   * leaflet_google
   *   * file_classifier
   *   * brc_atlas
   *   * brc_charts
   *   * bigr
   *   * d3
   *   * html2canvas
   */
  public static function add_resource($resource) {
    // Ensure indiciaFns is always the first resource added.
    if (!self::$indiciaFnsDone) {
      self::$indiciaFnsDone = TRUE;
      self::add_resource('indiciaFns');
    }
    $resourceList = self::get_resources();
    // If this is an available resource and we have not already included it,
    // then add it to the list.
    if (array_key_exists($resource, $resourceList) && !in_array($resource, self::$required_resources)) {
      if (isset($resourceList[$resource]['deps'])) {
        foreach ($resourceList[$resource]['deps'] as $dep) {
          self::add_resource($dep);
        }
      }
      self::$required_resources[] = $resource;
    }
  }

  /**
   * List of external resources including stylesheets and js files used by the data entry helper class.
   */
  public static function get_resources() {
    if (self::$resource_list === NULL) {
      $base = self::$base_url;
      if (!self::$js_path) {
        self::$js_path = $base . 'media/js/';
      }
      elseif (substr(self::$js_path, -1) != "/") {
        // Ensure a trailing slash.
        self::$js_path .= "/";
      }
      if (!self::$css_path) {
        self::$css_path = $base . 'media/css/';
      }
      elseif (substr(self::$css_path, -1) != '/') {
        // Ensure a trailing slash.
        self::$css_path .= "/";
      }
      global $indicia_theme, $indicia_theme_path;
      if (!isset($indicia_theme)) {
        // Use default theme if page does not specify it's own.
        $indicia_theme = 'default';
      }
      if (!isset($indicia_theme_path)) {
        // Use default theme path if page does not specify it's own.
        $indicia_theme_path = preg_replace('/css\/$/', 'themes/', self::$css_path);
      }
      // Ensure a trailing slash.
      if (substr($indicia_theme_path, -1) !== '/') {
        $indicia_theme_path .= '/';
      }
      self::$resource_list = [
        'indiciaFns' => [
          'deps' => ['jquery'],
          'javascript' => [self::$js_path . "indicia.functions.js"],
        ],
        'jquery' => [
          'javascript' => [
            self::$js_path . 'jquery.js',
          ],
        ],
        'datepicker' => [
          'deps' => ['jquery_cookie'],
          'javascript' => [
            self::$js_path . 'indicia.datepicker.js',
            self::$js_path . 'date.polyfill/better-dom/dist/better-dom.min.js',
            self::$js_path . 'date.polyfill/better-dateinput-polyfill/dist/better-dateinput-polyfill.min.js',
          ]
        ],
        'sortable' => [
          'javascript' => ['https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.js'],
        ],
        'proj4-lib' => [
          'javascript' => [
            self::$js_path . 'proj4js.js',
          ],
        ],
        'proj4' => [
          'javascript' => [
            self::$js_path . 'proj4defs.js',
          ],
          'deps' => ['proj4-lib'],
        ],
        'openlayers' => [
          'javascript' => [
            self::$js_path . (function_exists('iform_openlayers_get_file') ? iform_openlayers_get_file() : 'OpenLayers.js'),
            self::$js_path . 'lang/en.js',
          ],
          'deps' => ['proj4'],
        ],
        'graticule' => [
          'deps' => ['openlayers'],
          'javascript' => [self::$js_path . 'indiciaGraticule.js'],
        ],
        'clearLayer' => [
          'deps' => ['openlayers'],
          'javascript' => [self::$js_path . 'clearLayer.js'],
        ],
        'hoverControl' => [
          'deps' => ['openlayers'],
          'javascript' => [self::$js_path . 'hoverControl.js'],
        ],
        'addrowtogrid' => [
          'deps' => ['validation'],
          'javascript' => [self::$js_path . "addRowToGrid.js"],
        ],
        'speciesFilterPopup' => [
          'deps' => ['addrowtogrid'],
          'javascript' => [self::$js_path . "speciesFilterPopup.js"],
        ],
        'indiciaMapPanel' => [
          'deps' => ['jquery', 'openlayers', 'jquery_ui', 'jquery_cookie', 'hoverControl'],
          'javascript' => [self::$js_path . "jquery.indiciaMapPanel.js"],
        ],
        'indiciaMapEdit' => [
          'deps' => ['indiciaMapPanel'],
          'javascript' => [self::$js_path . "jquery.indiciaMap.edit.js"],
        ],
        'postcode_search' => [
          'deps' => ['indiciaMapPanel'],
          'javascript' => [self::$js_path . "postcode_search.js"],
        ],
        'locationFinder' => [
          'deps' => ['indiciaMapEdit'],
          'javascript' => [self::$js_path . "jquery.indiciaMap.edit.locationFinder.js"],
        ],
        'createPersonalSites' => [
          'deps' => ['jquery'],
          'javascript' => [self::$js_path . "createPersonalSites.js"],
        ],
        'autocomplete' => [
          'deps' => ['jquery'],
          'stylesheets' => [self::$css_path . "jquery.autocomplete.css"],
          'javascript' => [self::$js_path . "jquery.autocomplete.js"],
        ],
        'addNewTaxon' => [
          'javascript' => [self::$js_path . "addNewTaxon.js"],
        ],
        'import' => [
          'javascript' => [self::$js_path . "import.js"],
        ],
        'indicia_locks' => [
          'deps' => ['jquery_cookie', 'json'],
          'javascript' => [self::$js_path . "indicia.locks.js"],
        ],
        'jquery_cookie' => [
          'deps' => ['jquery'],
          'javascript' => [self::$js_path . "jquery.cookie.js"],
        ],
        'jquery_ui' => [
          'deps' => ['jquery'],
          'stylesheets' => [
            self::$css_path . 'jquery-ui.min.css',
            "$indicia_theme_path$indicia_theme/jquery-ui.theme.min.css",
          ],
          'javascript' => [
            self::$js_path . 'jquery-ui.min.js',
            self::$js_path . 'jquery-ui.effects.js',
          ]
        ],
        'jquery_ui_fr' => [
          'deps' => ['jquery_ui'],
          'javascript' => [self::$js_path . "jquery.ui.datepicker-fr.js"]
        ],
        'jquery_form' => [
          'deps' => ['jquery'],
          'javascript' => [self::$js_path . "jquery.form.min.js"],
        ],
        'reportPicker' => [
          'deps' => ['treeview', 'fancybox'],
          'javascript' => [self::$js_path . "reportPicker.js"],
        ],
        'treeview' => [
          'deps' => ['jquery'],
          'stylesheets' => [self::$css_path."jquery.treeview.css"],
          'javascript' => [self::$js_path."jquery.treeview.js"],
        ],
        'treeview_async' => [
          'deps' => ['treeview'],
          'javascript' => [
            self::$js_path."jquery.treeview.async.js",
            self::$js_path."jquery.treeview.edit.js",
          ],
        ],
        'googlemaps' => [
          'javascript' => ["https://maps.google.com/maps/api/js?v=3" . (empty(self::$google_maps_api_key) ? '' : '&key=' . self::$google_maps_api_key)],
        ],
        'fancybox' => [
          'deps' => ['jquery'],
          'stylesheets' => [self::$js_path . 'fancybox/dist/jquery.fancybox.min.css'],
          'javascript' => [self::$js_path . 'fancybox/dist/jquery.fancybox.min.js'],
        ],
        'treeBrowser' => [
          'deps' => ['jquery', 'jquery_ui'],
          'javascript' => [self::$js_path . 'jquery.treebrowser.js']
        ],
        'defaultStylesheet' => [
          'stylesheets' => [
            self::$css_path . 'default_site.css',
            self::$css_path . 'theme-generic.css'
          ],
        ],
        'validation-lib' => [
          'deps' => ['jquery'],
          'javascript' => [
            self::$js_path . 'jquery.metadata.js',
            self::$js_path . 'jquery.validate.js',
          ],
        ],
        'validation' => [
          'deps' => ['validation-lib'],
          'javascript' => [
            self::$js_path . 'additional-methods.js',
          ],
        ],
        'plupload' => [
          'deps' => ['jquery_ui', 'fancybox'],
          'javascript' => [
            self::$js_path . 'jquery.uploader.js',
            self::$js_path . 'plupload/js/plupload.full.min.js',
          ]
        ],
        'uploader' => [
          'deps' => ['jquery', 'dmUploader'],
          'javascript' => [
            self::$js_path . 'uploader.js',
          ],
        ],
        'dmUploader' => [
          'stylesheets' => [
            self::$js_path . 'uploader/dist/css/jquery.dm-uploader.min.css',
          ],
          'javascript' => [
            self::$js_path . 'uploader/dist/js/jquery.dm-uploader.min.js',
          ]
        ],
        'jqplot' => [
          'stylesheets' => [self::$js_path . 'jqplot/jquery.jqplot.min.css'],
          'javascript' => [
            self::$js_path . 'jqplot/jquery.jqplot.min.js',
          ],
        ],
        'jqplot_bar' => [
          'javascript' => [
            self::$js_path . 'jqplot/plugins/jqplot.barRenderer.js',
          ],
        ],
        'jqplot_pie' => [
          'javascript' => [
            self::$js_path . 'jqplot/plugins/jqplot.pieRenderer.js',
          ],
        ],
        'jqplot_category_axis_renderer' => [
          'javascript' => [self::$js_path . 'jqplot/plugins/jqplot.categoryAxisRenderer.js'],
        ],
        'jqplot_canvas_axis_label_renderer' => [
          'javascript' => [
            self::$js_path . 'jqplot/plugins/jqplot.canvasTextRenderer.js',
            self::$js_path . 'jqplot/plugins/jqplot.canvasAxisLabelRenderer.js',
          ],
        ],
        'jqplot_trendline' => [
          'javascript' => [
            self::$js_path . 'jqplot/plugins/jqplot.trendline.js',
          ],
        ],
        'reportgrid' => [
          'deps' => ['jquery_ui', 'jquery_cookie'],
          'javascript' => [
            self::$js_path . 'jquery.reportgrid.js',
          ]
        ],
        'freeformReport' => [
          'javascript' => [
            self::$js_path . 'jquery.freeformReport.js',
          ]
        ],
        'reportfilters' => [
          'deps' => ['reportgrid'],
          'stylesheets' => [self::$css_path . 'report-filters.css'],
          'javascript' => [self::$js_path . 'reportFilters.js'],
        ],
        'tabs' => [
          'deps' => ['jquery_ui'],
          'javascript' => [self::$js_path . 'tabs.js'],
        ],
        'wizardprogress' => [
          'deps' => ['tabs'],
          'stylesheets' => [self::$css_path . 'wizard_progress.css']
        ],
        'spatialReports' => [
          'javascript' => [self::$js_path . 'spatialReports.js'],
        ],
        'jsonwidget' => [
          'deps' => ['jquery'],
          'javascript' => [
            self::$js_path . 'jsonwidget/jsonedit.js',
            self::$js_path . 'jquery.jsonwidget.js',
          ],
          'stylesheets' => [self::$css_path . 'jsonwidget.css'],
        ],
        'timeentry' => [
          'javascript' => [self::$js_path . 'jquery.timeentry.min.js'],
        ],
        'verification' => [
          'javascript' => [self::$js_path . 'verification.js'],
        ],
        'control_speciesmap_controls' => [
          'deps' => [
            'jquery',
            'openlayers',
            'addrowtogrid',
            'validation',
          ],
          'javascript' => [
            self::$js_path . 'controls/speciesmap_controls.js',
          ],
        ],
        'complexAttrGrid' => [
          'javascript' => [self::$js_path . 'complexAttrGrid.js'],
        ],
        'footable' => [
          'stylesheets' => [self::$js_path . 'footable/css/footable.core.min.css'],
          // Note, the minified version not used as it does not contain bugfixes.
          // 'javascript' => [self::$js_path.'footable/dist/footable.min.js']
          'javascript' => [self::$js_path . 'footable/js/footable.js'],
          'deps' => ['jquery'],
        ],
        'footableSort' => [
          'javascript' => [self::$js_path . 'footable/dist/footable.sort.min.js'],
          'deps' => ['footable'],
        ],
        'footableFilter' => [
          'javascript' => [self::$js_path . 'footable/dist/footable.filter.min.js'],
          'deps' => ['footable'],
        ],
        'indiciaFootableReport' => [
          'javascript' => [self::$js_path . 'jquery.indiciaFootableReport.js'],
          'deps' => ['footable'],
        ],
        'indiciaFootableChecklist' => [
          'stylesheets' => [self::$css_path . 'jquery.indiciaFootableChecklist.css'],
          'javascript' => [self::$js_path . 'jquery.indiciaFootableChecklist.js'],
          'deps' => ['footable']
        ],
        'html2pdf' => [
          'javascript' => [
            self::$js_path . 'html2pdf/dist/html2pdf.bundle.min.js',
          ],
        ],
        'review_input' => [
          'javascript' => [self::$js_path . 'jquery.reviewInput.js'],
        ],
        'sub_list' => [
          'javascript' => [self::$js_path . 'sub_list.js'],
        ],
        'georeference_default_geoportal_lu' => [
          'javascript' => [self::$js_path . 'drivers/georeference/geoportal_lu.js'],
          'deps' => ['indiciaMapPanel'],
        ],
        'georeference_default_nominatim' => [
          'javascript' => [self::$js_path . 'drivers/georeference/nominatim.js'],
          'deps' => ['indiciaMapPanel'],
        ],
        'georeference_default_google_places' => [
          'javascript' => [self::$js_path . 'drivers/georeference/google_places.js'],
          'deps' => ['indiciaMapPanel'],
        ],
        'georeference_default_indicia_locations' => [
          'javascript' => [self::$js_path . 'drivers/georeference/indicia_locations.js'],
          'deps' => ['indiciaMapPanel'],
        ],
        'sref_handlers_2169' => [
          'javascript' => [self::$js_path . 'drivers/sref/2169.js'],
        ],
        'sref_handlers_4326' => [
          'javascript' => [self::$js_path . 'drivers/sref/4326.js'],
        ],
        'sref_handlers_osgb' => [
          'javascript' => [self::$js_path . 'drivers/sref/osgb.js'],
        ],
        'sref_handlers_osie' => [
          'javascript' => [self::$js_path . 'drivers/sref/osie.js'],
        ],
        'font_awesome' => [
          'stylesheets' => ['https://use.fontawesome.com/releases/v5.15.4/css/all.css']
        ],
        'leaflet' => [
          'stylesheets' => ['https://unpkg.com/leaflet@1.4.0/dist/leaflet.css'],
          'javascript' => [
            'https://unpkg.com/leaflet@1.4.0/dist/leaflet.js',
            'https://cdnjs.cloudflare.com/ajax/libs/wicket/1.3.3/wicket.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/wicket/1.3.3/wicket-leaflet.min.js',
            self::$js_path . 'leaflet.heat/dist/leaflet-heat.js',
          ],
        ],
        'leaflet_google' => [
          'deps' => [
            'googlemaps'
          ],
          'javascript' => [
            'https://unpkg.com/leaflet.gridlayer.googlemutant@latest/dist/Leaflet.GoogleMutant.js',
          ],
        ],
        'datacomponents' => [
          'deps' => [
            'font_awesome',
            'indiciaFootableReport',
            'jquery_cookie',
            'proj4',
          ],
          'javascript' => [
            self::$js_path . 'indicia.datacomponents/idc.core.js',
            self::$js_path . 'indicia.datacomponents/idc.controlLayout.js',
            self::$js_path . 'indicia.datacomponents/idc.esDataSource.js',
            self::$js_path . 'indicia.datacomponents/idc.pager.js',
            self::$js_path . 'indicia.datacomponents/jquery.idc.customScript.js',
            self::$js_path . 'indicia.datacomponents/jquery.idc.runCustomVerificationRulesets.js',
            self::$js_path . 'indicia.datacomponents/jquery.idc.bulkEditor.js',
            self::$js_path . 'indicia.datacomponents/jquery.idc.cardGallery.js',
            self::$js_path . 'indicia.datacomponents/jquery.idc.dataGrid.js',
            self::$js_path . 'indicia.datacomponents/jquery.idc.esDownload.js',
            self::$js_path . 'indicia.datacomponents/jquery.idc.gridSquareOpacityScale.js',
            self::$js_path . 'indicia.datacomponents/jquery.idc.leafletMap.js',
            self::$js_path . 'indicia.datacomponents/jquery.idc.recordsMover.js',
            self::$js_path . 'indicia.datacomponents/jquery.idc.recordDetailsPane.js',
            self::$js_path . 'indicia.datacomponents/jquery.idc.templatedOutput.js',
            self::$js_path . 'indicia.datacomponents/jquery.idc.verificationButtons.js',
            self::$js_path . 'indicia.datacomponents/jquery.idc.filterSummary.js',
            self::$js_path . 'indicia.datacomponents/jquery.idc.permissionFilters.js',
            'https://unpkg.com/@ungap/url-search-params',
          ],
        ],
        'file_classifier' => [
          'deps' => [
            'plupload',
            'jquery_ui',
          ],
          'javascript' => [
            self::$js_path . 'jquery.fileClassifier.js',
          ],
        ],
        'brc_atlas' => [
          'deps' => [
            'd3',
            'bigr',
            'leaflet',
          ],
          'stylesheets' => [
            'https://cdn.jsdelivr.net/gh/biologicalrecordscentre/brc-atlas@0.25.1/dist/brcatlas.umd.min.css',
          ],
          'javascript' => [
            'https://cdn.jsdelivr.net/gh/biologicalrecordscentre/brc-atlas@0.25.1/dist/brcatlas.umd.min.js',
          ],
        ],
        'brc_charts' => [
          'deps' => [
            'd3',
          ],
          'stylesheets' => [
            'https://cdn.jsdelivr.net/gh/biologicalrecordscentre/brc-charts@latest/dist/brccharts.umd.min.css',
          ],
          'javascript' => [
            'https://cdn.jsdelivr.net/gh/biologicalrecordscentre/brc-charts@latest/dist/brccharts.umd.min.js',
          ],
        ],
        'd3' => [
          'javascript' => [
            'https://d3js.org/d3.v5.min.js',
          ],
        ],
        'bigr' => [
          'javascript' => [
            'https://unpkg.com/brc-atlas-bigr@2.4.0/dist/bigr.min.umd.js',
          ],
        ],
        'html2canvas' => [
          'javascript' => [
            'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js',
           ],
        ],
        'brc_atlas_e' => [
          'deps' => [
            'd3_v7',
          ],
          'stylesheets' => [
            'https://cdn.jsdelivr.net/gh/biologicalrecordscentre/brc-atlas@1.1.6/dist/brcatlas_e.umd.css',
          ],
          'javascript' => [
            'https://cdn.jsdelivr.net/gh/biologicalrecordscentre/brc-atlas@1.1.6/dist/brcatlas_e.umd.min.js',
          ],
        ],
        'd3_v7' => [
          'javascript' => [
            'https://d3js.org/d3.v7.min.js',
          ],
        ],
      ];
    }
    return self::$resource_list;
  }

  /**
   * Causes the default_site.css stylesheet to be included in the list of resources on the
   * page. This gives a basic form layout.
   * This also adds default JavaScript to the page to cause buttons to highlight when you
   * hover the mouse over them.
   */
  public static function link_default_stylesheet() {
    // Make buttons highlight when hovering over them.
    self::$javascript .= "indiciaFns.enableHoverEffect();\n";
    self::$default_styles = TRUE;
  }

  /**
   * Returns a span containing any validation errors for a control.
   *
   * @param string $fieldname
   *   Fieldname of the control to retrieve errors for.
   * @param bool $plaintext
   *   Set to true to return just the error text, otherwise it is wrapped in a span.
   *
   * @return string
   *   HTML for the validation error output.
   */
  public static function check_errors($fieldname, $plaintext=FALSE) {
    $error = '';
    if (self::$validation_errors !== NULL) {
      if (array_key_exists($fieldname, self::$validation_errors)) {
        $errorKey = $fieldname;
      }
      elseif ($fieldname === 'sample:location_id' && array_key_exists('sample:location_name', self::$validation_errors)) {
        // Location autocompletes can have a linked location ID or a freetext
        // location name, so outptu both errors against the control.
        $errorKey = 'sample:location_name';
      }
      elseif (substr($fieldname, -4) === 'date') {
        // For date fields, we also include the type, start and end validation
        // problems.
        if (array_key_exists($fieldname . '_start', self::$validation_errors)) {
          $errorKey = $fieldname . '_start';
        }
        if (array_key_exists($fieldname . '_end', self::$validation_errors)) {
          $errorKey = $fieldname . '_end';
        }
        if (array_key_exists($fieldname . '_type', self::$validation_errors)) {
          $errorKey = $fieldname . '_type';
        }
      }
      if (isset($errorKey)) {
        $error = self::$validation_errors[$errorKey];
        // Track errors that were displayed, so we can tell the user about any others.
        self::$displayed_errors[] = $error;
      }
    }
    if ($error != '') {
      if ($plaintext) {
        return $error;
      }
      else {
        return self::apply_error_template($error, $fieldname);
      }
    }
    else {
      return '';
    }
  }

  /**
   * Sends a POST using the cUrl library.
   *
   * @param string $url
   *   The URL the POST request is sent to.
   * @param string|array $postargs
   *   Arguments to include in the POST data.
   * @param bool $output_errors
   *   Set to false to prevent echoing of errors. Defaults to true.
   *
   * @return array
   *   An array with a result element set to true or false for successful or
   *   failed posts respectively. The output is returned in an output element
   *   in the array. If there is an error, then an errorno element gives the
   *   cUrl error number (as generated by the cUrl library used for the post).
   */
  public static function http_post($url, $postargs = NULL, $output_errors = TRUE) {
    $session = curl_init();
    // Set the POST options.
    curl_setopt($session, CURLOPT_URL, $url);
    if ($postargs !== NULL) {
      curl_setopt($session, CURLOPT_POST, TRUE);
      if (is_array($postargs) && version_compare(phpversion(), '5.5.0') >= 0) {
        // Posting a file using @ prefix is deprecated as of version 5.5.0.
        foreach ($postargs as $key => $value) {
          // Loop through postargs to find files where the value is prefixed @.
          if (strpos($value ?? '', '@') === 0) {
            // Found a file - could be in form @path/to/file;type=mimetype.
            $fileparts = explode(';', substr($value, 1));
            $filename = $fileparts[0];
            if (count($fileparts) == 1) {
              // Only filename specified.
              $postargs[$key] = new CurlFile($filename);
            }
            else {
              // Mimetype may be specified too.
              $fileparam = explode('=', $fileparts[1]);
              if ($fileparam[0] == 'type' && isset($fileparam[1])) {
                // Found a mimetype.
                $mimetype = $fileparam[1];
                $postargs[$key] = new CurlFile($filename, $mimetype);
              }
              else {
                // The fileparam didn't seem to be a mimetype.
                $postargs[$key] = new CurlFile($filename);
              }
            }
          }
        }
      }
      curl_setopt($session, CURLOPT_POSTFIELDS, $postargs);
    }
    curl_setopt($session, CURLOPT_HEADER, FALSE);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
    // Do the POST and then close the session.
    $response = curl_exec($session);
    $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
    $curlErrno = curl_errno($session);
    // Check for an error, or check if the http response was not OK.
    if ($curlErrno || $httpCode != 200) {
      if ($output_errors) {
        echo '<div class="error">cUrl POST request failed. Please check cUrl is installed on the server and the $base_url setting is correct.<br/>URL:' . $url . '<br/>';
        if ($curlErrno) {
          echo 'Error number: ' . $curlErrno . '<br/>';
          echo 'Error message: ' . curl_error($session) . '<br/>';
        }
        echo "Server response<br/>";
        echo $response . '</div>';
      }
      $return = [
        'result' => FALSE,
        'output' => $curlErrno ? curl_error($session) : $response,
        'errno' => $curlErrno,
        'status' => $httpCode
      ];
    }
    else {
      $arr_response = explode("\r\n\r\n", $response);
      // Last part of response is the actual data.
      $return = ['result' => TRUE, 'output' => array_pop($arr_response)];
    }
    curl_close($session);
    return $return;
  }

  /**
   * Calculates the folder that submitted images end up in according to the helper_config.
   */
  public static function get_uploaded_image_folder() {
    if (!isset(self::$final_image_folder) || self::$final_image_folder === 'warehouse') {
      return self::getProxiedBaseUrl() . (isset(self::$indicia_upload_path) ? self::$indicia_upload_path : 'upload/');
    }
    else {
      return self::getRootFolder() . self::client_helper_path() . self::$final_image_folder;
    }
  }

  /**
   * Returns the client helper folder path, relative to the root folder.
   */
  public static function client_helper_path() {
    // Allow integration modules to control path handling, e.g. Drupal).
    if (function_exists('iform_client_helpers_path')) {
      return iform_client_helpers_path();
    }
    else {
      $fullpath = str_replace('\\', '/', realpath(__FILE__));
      $root = $_SERVER['DOCUMENT_ROOT'] . self::getRootFolder();
      $root = str_replace('\\', '/', $root);
      $client_helper_path = dirname(str_replace($root, '', $fullpath)) . '/';
      return $client_helper_path;
    }
  }

  /**
   * Calculates the relative path to the client_helpers folder from wherever the current PHP script is.
   */
  public static function relative_client_helper_path() {
    // Get the paths to the client helper folder and php file folder as an
    // array of tokens.
    $clientHelperFolder = explode(DIRECTORY_SEPARATOR, dirname(realpath(__FILE__)));
    $currentPhpFileFolder = explode(DIRECTORY_SEPARATOR, dirname(realpath($_SERVER['SCRIPT_FILENAME'])));
    // Find the first part of the paths that is not the same.
    for ($i = 0; $i < min(count($currentPhpFileFolder), count($clientHelperFolder)); $i++) {
      if ($clientHelperFolder[$i] != $currentPhpFileFolder[$i]) {
        break;
      }
    }
    // Step back up the path to the point where the 2 paths differ.
    $path = str_repeat('../', count($currentPhpFileFolder) - $i);
    // Add back in the different part of the path to the client helper folder.
    for ($j = $i; $j < count($clientHelperFolder); $j++) {
      $path .= $clientHelperFolder[$j] . '/';
    }
    return $path;
  }

  /**
   * Relative path from a script in client_helpers to the image upload path.
   *
   * Can be passed to the uploader as a JS setting safely, wherease an absolute
   * path would be a security risk.
   *
   * @return string
   *   Relative path.
   */
  public static function getImageRelativePath() {
    // Get the paths to the client helper folder and php file folder as an array of tokens.
    $clientHelperFolder = explode(DIRECTORY_SEPARATOR, dirname(realpath(__FILE__)));
    $imageFolder = explode(DIRECTORY_SEPARATOR, realpath(self::getInterimImageFolder('fullpath')));
    // Find the first part of the paths that is not the same.
    for ($i = 0; $i < min(count($clientHelperFolder), count($imageFolder)); $i++) {
      if ($imageFolder[$i] !== $clientHelperFolder[$i]) {
        break;
      }
    }
    // Step back up the path to the point where the 2 paths differ.
    $path = str_repeat('../', count($clientHelperFolder) - $i);
    // Add back in the different part of the path to the client helper folder.
    for ($j = $i; $j < count($imageFolder); $j++) {
      $path .= $imageFolder[$j] . '/';
    }
    return $path;
  }

  /**
   * Parameters forms are a quick way of specifying a simple form used to specify the input
   * parameters for a process. Returns the HTML required for a parameters form, e.g. the form
   * defined for input of report parameters or the default values for a csv import.
   *
   * @param array $options
   *   Options array with the following possibilities:
   *   * form - Associative array defining the form structure. The structure is
   *     the same as described for <em>fixedValuesForm</em> in a Warehouse
   *     model.
   *   * id - When used for report output, id of the report instance on the page
   *     if relevant, so that controls can be given unique ids.
   *   * form - Associative array defining the form content.
   *   * readAuth- Read authorisation array.
   *   * fieldNamePrefix - Optional prefix for form field names.
   *   * defaults - Associative array of default values for each form element.
   *   * paramsToHide - An optional array of parameter names for parameters that
   *     should be added to the form output as hidden inputs rather than visible
   *     controls.
   *   * paramsToExclude - An optional array of parameter names for parameters
   *     that should be skipped in the form output despite being in the form
   *     definition.
   *   * forceLookupParamAutocomplete - If true, forces lookup parameters to be
   *     an autocomplete instead of drop-down.
   *   * forceLookupParamAutocompleteSelectMode - Used in conjunction with f
   *     orceLookupParamAutocomplete, if true then autocomplete parameter
   *     control is put into selectMode.
   *   * extraParams - Optional array of param names and values that have a
   *     fixed value and are therefore output only as a hidden control.
   *   * inlineMapTools - Defaults to false. If true, then map drawing parameter
   *     tools are embedded into the report parameters form. If false, then the
   *     map drawing tools are added to a toolbar at the top of the map.
   *   * helpText - Defaults to true. Set to false to disable helpText being
   *     displayed alongside controls, useful for building compact versions of
   *     simple parameter forms.
   *   * nocache - Set to true to disable caching of lookups.
   * @param bool $hasVisibleContent
   *   On completion, this is set to true if there are visible controls in the
   *   params form. If not, then it may be appropriate to skip the displaying
   *   of this params form since it is not necessary.
   */
  public static function build_params_form(array $options, &$hasVisibleContent) {
    require_once 'data_entry_helper.php';
    global $indicia_templates;
    $javascript = '';
    // Track if there is anything other than hiddens on the form.
    $hasVisibleContent = FALSE;
    // Apply defaults.
    $options = array_merge([
      'inlineMapTools' => FALSE,
      'helpText' => TRUE
    ], $options);
    $r = '';
    // Any ignored parameters will not be in the requested parameter form
    // definition, but we do need hiddens.
    if (isset($options['paramsToHide'])) {
      foreach ($options['paramsToHide'] as $key) {
        $default = isset($options['defaults'][$key]) ? $options['defaults'][$key] : '';
        $fieldPrefix = (isset($options['fieldNamePrefix']) ? $options['fieldNamePrefix'] . '-' : '');
        $r .= "<input type=\"hidden\" name=\"$fieldPrefix$key\" value=\"$default\" class=\"test\"/>\n";
      }
    }
    // If doing map tools inline, they don't get added to the page until the
    // map initialises. So capture the JavaScript into a map initialisation
    // hook.
    if (isset($options['paramsInMapToolbar']) && $options['paramsInMapToolbar']) {
      self::$javascript .= "mapInitialisationHooks.push(function(div) {\n";
    }
    foreach ($options['form'] as $key => $info) {
      $tools = [];
      // Skip parameters if we have been asked to ignore them.
      if (!isset($options['paramsToExclude']) || !in_array($key, $options['paramsToExclude'])) {
        $r .= self::getParamsFormControl($key, $info, $options, $tools);
        // If that was a visible setting, then we have to tell the caller that
        // there is something to show.
        if (!isset($options['extraParams']) || !array_key_exists($key, $options['extraParams'])) {
          $hasVisibleContent = TRUE;
        }
      }
      // If the form has defined any tools to add to the map, we need to create JavaScript to add them to the map.
      if (count($tools)) {
        // Wrap JavaScript in a test that the map is on the page.
        if (isset($info['allow_buffer']) && $info['allow_buffer'] == 'true') {
          $javascript .= "if (typeof $.fn.indiciaMapPanel!=='undefined') {\n";
          $javascript .= "  indiciaFns.enableBuffering();\n";
        }
        $javascript .= "  indiciaFns.storeGeomsInFormOnSubmit();\n";
        $fieldname = (isset($options['fieldNamePrefix']) ? $options['fieldNamePrefix'] . '-' : '') . $key;
        self::add_resource('spatialReports');
        self::add_resource('clearLayer');
        if ($options['inlineMapTools']) {
          $ctrl = <<<HTML
<label>$info[display]:</label>
<div class="control-box">
  <div id="map-toolbar" class="olControlEditingToolbar clearfix"></div>
  <p class="helpText">Use the above tools to define the query area.</p>
</div>

HTML;
          $r .= str_replace(['{control}', '{id}', '{wrapClasses}'], [$ctrl, 'map-toolbar', ''], $indicia_templates['controlWrap']);
        }
        $r .= '<input type="hidden" name="' . $fieldname . '" id="hidden-wkt" value="' .
            (isset($_POST[$fieldname]) ? $_POST[$fieldname] : '') . '"/>';
        if (isset($info['allow_buffer']) && $info['allow_buffer'] === 'true') {
          $bufferInput = data_entry_helper::text_input([
            'label' => 'Buffer (m)',
            'fieldname' => 'geom_buffer',
            'prefixTemplate' => 'blank', // revert to default
            'suffixTemplate' => 'blank', // revert to default
            'class' => 'control-width-1',
            'default' => $_POST['geom_buffer'] ?? 0
          ]);
          if ($options['inlineMapTools']) {
            $r .= $bufferInput;
          }
          else {
            $bufferInput = str_replace(['<br/>', "\n"], '', $bufferInput);
            $javascript .= "$.fn.indiciaMapPanel.defaults.toolbarSuffix+='$bufferInput';\n";
          }
          // Keep a copy of the unbuffered polygons in this input, so that when
          // the page reloads both versions are available.
          $r .= '<input type="hidden" name="orig-wkt" id="orig-wkt" value="' . ($_POST['orig-wkt'] ?? '') . "\" />\n";
        }
        // Output some JavaScript to setup a toolbar for the map drawing tools.
        // Also JS to handle getting the polygons from the edit layer into the
        // report parameter when run report is clicked.
        $toolbarDiv = $options['inlineMapTools'] ? 'map-toolbar' : 'top';
        $javascript .= "
  $.fn.indiciaMapPanel.defaults.toolbarDiv='$toolbarDiv';
  mapInitialisationHooks.push(function(div) {
    var styleMap = new OpenLayers.StyleMap(OpenLayers.Util.applyDefaults(
          {fillOpacity: 0.05},
          OpenLayers.Feature.Vector.style['default']));
    div.map.editLayer.styleMap = styleMap;\n";

        if (isset($info['allow_buffer']) && $info['allow_buffer'] == 'true') {
          $origWkt = empty($_POST['orig-wkt']) ? '' : $_POST['orig-wkt'];
        }
        else {
          $origWkt = empty($_POST[$fieldname]) ? '' : $_POST[$fieldname];
        }

        if (!empty($origWkt)) {
          $javascript .= "  var geom=OpenLayers.Geometry.fromWKT('$origWkt');\n";
          $javascript .= "  if (div.map.projection.getCode() !== div.indiciaProjection.getCode()) {\n";
          $javascript .= "    geom.transform(div.indiciaProjection, div.map.projection);\n";
          $javascript .= "  }\n";
          $javascript .= "  div.map.editLayer.addFeatures([new OpenLayers.Feature.Vector(geom)]);\n";
        }
        $javascript .= "
  });
  var add_map_tools = function(opts) {\n";
        foreach ($tools as $tool) {
          $javascript .= "  opts.standardControls.push('draw$tool');\n";
        }
        $javascript .= "  opts.standardControls.push('clearEditLayer');
  };
  mapSettingsHooks.push(add_map_tools);\n";
        if (isset($info['allow_buffer']) && $info['allow_buffer']=='true')
          $javascript .= "}\n";
      }
    }
    // Closure for the map initialisation hooks.
    if (isset($options['paramsInMapToolbar']) && $options['paramsInMapToolbar']) {
      self::$javascript .= "});";
    }
    self::$javascript .= $javascript;
    return $r;
  }

  /**
   * Internal method to safely find the value of a preset parameter.
   *
   * Returns empty string if not defined.
   *
   * @param array $options
   *   The options array, containing a extraParams entry that the parameter
   *   should be found in.
   * @param string $name
   *   The key identifying the preset parameter to look for.
   *
   * @return string
   *   Value of preset parameter or empty string.
   */
  private static function get_preset_param($options, $name) {
    if (!isset($options['extraParams'])) {
      return '';
    }
    elseif (!isset($options['extraParams'][$name])) {
      return '';
    }
    return $options['extraParams'][$name];
  }

  /**
   * Returns a control to insert onto a parameters form.
   *
   * @param string $key
   *   The unique identifier of this control.
   * @param array $info
   *   Configuration options for the parameter as defined in the report,
   *   including the description, display (label), default and datatype.
   * @param array $options
   *   Control options array.
   * @param array $tools
   *   Any tools to be embedded in the map toolbar are returned in this
   *   parameter rather than as the return result of the function.
   *
   * @return string
   *   The HTML for the form parameter.
   */
  protected static function getParamsFormControl($key, array $info, array $options, &$tools) {
    $r = '';
    $fieldPrefix = (isset($options['fieldNamePrefix']) ? $options['fieldNamePrefix'] . '-' : '');
    $ctrlOptions = [
      'label' => lang::get($info['display']),
      // Note we can't fit help text in the toolbar versions of a params form.
      'helpText' => $options['helpText'] ? $info['description'] : '',
      'fieldname' => $fieldPrefix . $key,
      'nocache' => isset($options['nocache']) && $options['nocache'],
      'validation' => $info['validation'] ?? NULL,
    ];
    // If this parameter is in the URL or post data, put it in the control
    // instead of the original default.
    if (isset($options['defaults'][$key])) {
      $ctrlOptions['default'] = $options['defaults'][$key];
    }
    elseif (isset($info['default'])) {
      $ctrlOptions['default'] = $info['default'];
    }
    if ($info['datatype'] === 'idlist') {
      // Idlists are not for human input so use a hidden.
      $r .= "<input type=\"hidden\" name=\"$fieldPrefix$key\" value=\"" . self::get_preset_param($options, $key) . "\" class=\"{$fieldPrefix}idlist-param\" />\n";
    }
    elseif (isset($options['extraParams']) && array_key_exists($key, $options['extraParams'])) {
      $r .= "<input type=\"hidden\" name=\"$fieldPrefix$key\" value=\"" . self::get_preset_param($options, $key) . "\" />\n";
      // If the report parameter is a lookup and its population_call is set to
      // species_autocomplete. Options such as @speciesIncludeBothNames can be
      // included as a [params] control form structure option.
    }
    elseif ($info['datatype'] === 'lookup' && (isset($info['population_call']) && $info['population_call'] === 'autocomplete:species')) {
      $ctrlOptions['extraParams'] = $options['readAuth'];
      if (!empty($options['speciesTaxonListId'])) {
        $ctrlOptions['extraParams']['taxon_list_id'] = $options['speciesTaxonListId'];
      }
      if (!empty($options['speciesIncludeBothNames']) && $options['speciesIncludeBothNames'] == TRUE) {
        $ctrlOptions['speciesIncludeBothNames'] = TRUE;
      }
      if (!empty($options['speciesIncludeTaxonGroup']) && $options['speciesIncludeTaxonGroup'] == TRUE) {
        $ctrlOptions['speciesIncludeTaxonGroup'] = TRUE;
      }
      $r .= data_entry_helper::species_autocomplete($ctrlOptions);
    }
    elseif ($info['datatype'] == 'lookup' && isset($info['population_call'])) {
      // Population call is colon separated, of the form
      // direct|report:table|view|report:idField:captionField:params(key=value,key=value,...).
      $popOpts = explode(':', $info['population_call'], 5);
      $extras = [];
      // If there are any extra parameters on the report lookup call, apply
      // them.
      if (count($popOpts) > 4) {
        // 5th item is a list of parameters.
        $extraItems = explode(',', $popOpts[4]);
        foreach ($extraItems as $extraItem) {
          $extraItem = explode('=', $extraItem, 2);
          self::replacePopulationCallParamValueTags($options['extraParams'], $extraItem);
          $extras[$extraItem[0]] = $extraItem[1];
        }
      }
      // Allow local page configuration to apply extra restrictions on the
      // return values: e.g. only return some location_types from the termlist.
      if (isset($options['param_lookup_extras']) && isset($options['param_lookup_extras'][$key])) {
        foreach ($options['param_lookup_extras'][$key] as $param => $value) {
          // Direct table access can handle 'in' statements, reports can't.
          $extras[$param] = ($popOpts[0] == 'direct' ? $value : (is_array($value) ? implode(',', $value) : $value));
        }
      }
      if (!isset($extras['orderby'])) {
        $extras['orderby'] = $popOpts[3];
      }
      $ctrlOptions = array_merge($ctrlOptions, [
        'valueField' => $popOpts[2],
        'captionField' => $popOpts[3],
        'blankText' => '<please select>',
        'extraParams' => $options['readAuth'] + $extras,
      ]);
      if ($popOpts[0] == 'direct') {
        $ctrlOptions['table'] = $popOpts[1];
      }
      else {
        $ctrlOptions['report'] = $popOpts[1];
      }
      if (isset($info['linked_to']) && isset($info['linked_filter_field'])) {
        // Exclude null entries from filter field by default.
        $ctrlOptions['filterIncludesNulls'] = $info['filterIncludesNulls'] ?? FALSE;
        if (isset($options['extraParams']) && array_key_exists($info['linked_to'], $options['extraParams'])) {
          // If the control this is linked to is hidden because it has a preset
          // value, just use that value as a filter on the population call for
          // this control.
          $ctrlOptions = array_merge($ctrlOptions, [
            'extraParams' => array_merge($ctrlOptions['extraParams'], [
              'query' => json_encode([
                'in' => [
                  $info['linked_filter_field'] => [
                    $options['extraParams'][$info['linked_to']],
                    NULL,
                  ],
                ],
              ]),
            ]),
          ]);
        }
        else {
          // Otherwise link the 2 controls.
          $ctrlOptions = array_merge($ctrlOptions, [
            'parentControlId' => $fieldPrefix . $info['linked_to'],
            'filterField' => $info['linked_filter_field'],
            'parentControlLabel' => $options['form'][$info['linked_to']]['display'],
          ]);
        }
      }
      $r .= data_entry_helper::select($ctrlOptions);
    }
    elseif ($info['datatype'] == 'lookup' && isset($info['lookup_values'])) {
      // Convert the lookup values into an associative array.
      $lookups = explode(',', $info['lookup_values']);
      $lookupsAssoc = [];
      foreach ($lookups as $lookup) {
        $lookup = explode(':', $lookup);
        $lookupsAssoc[$lookup[0]] = $lookup[1];
      }
      $ctrlOptions = array_merge($ctrlOptions, [
        'blankText' => '<' . lang::get('please select') . '>',
        'lookupValues' => $lookupsAssoc,
      ]);
      $r .= data_entry_helper::select($ctrlOptions);
    }
    elseif ($info['datatype'] == 'date') {
      $r .= data_entry_helper::date_picker($ctrlOptions);
    }
    elseif ($info['datatype'] == 'geometry') {
      $tools = ['Polygon', 'Line', 'Point'];
    }
    elseif ($info['datatype'] == 'polygon') {
      $tools = ['Polygon'];
    }
    elseif ($info['datatype'] == 'line') {
      $tools = ['Line'];
    }
    elseif ($info['datatype'] == 'point') {
      $tools = ['Point'];
    }
    else {
      if (method_exists('data_entry_helper', $info['datatype'])) {
        $ctrl = $info['datatype'];
        $r .= data_entry_helper::$ctrl($ctrlOptions);
      }
      else {
        $r .= data_entry_helper::text_input($ctrlOptions);
      }
    }
    return $r;
  }

  /**
   * Allows the user to provide params in a population_call lookup.
   *
   * If the population_call param uses a same parameter as the main report,
   * then we need to swap it out. Before we do this we test that the
   * population_call param needs replacing and isn't just a simple value (i.e.
   * it has a # symbol at either end like #survey_id# - this works in same
   * way as replacements in the main report).
   *
   * @param $extraParams
   *   Associative array of params and values provided to main report.
   * @param $extraItem array
   *   Single item array containing the population_call param we are working on
   *   and its value replacement tag. The value will be modified if a matching
   *   report parameter is available.
   *
   * @return array
   *   Single item array containing the population_call param we are working on
   *   and its value after replacement.
   */
  private static function replacePopulationCallParamValueTags(array $extraParams, array &$extraItem) {
    if (preg_match('/^#(?P<param>.*)#$/', $extraItem[1], $matches)
        && array_key_exists($matches['param'], $extraParams)) {
      $extraItem[1] = $extraParams[$matches['param']];
    }
  }

  /**
   * Utility method that returns the parts required to build a link back to the current page.
   * @return array Associative array containing path and params (itself a key/value paired associative array).
   */
  public static function get_reload_link_parts() {
    $split = strpos($_SERVER['REQUEST_URI'], '?');
    // Convert the query parameters into an array.
    $gets = ($split !== FALSE && strlen($_SERVER['REQUEST_URI']) > $split + 1) ?
        explode('&', substr($_SERVER['REQUEST_URI'], $split + 1)) :
        [];
    $getsAssoc = [];
    foreach ($gets as $get) {
      $tokens = explode('=', $get);
      // Ensure a key without value in the URL gets an empty value.
      if (count($tokens) === 1) {
        $tokens[] = '';
      }
      $getsAssoc[$tokens[0]] = $tokens[1];
    }
    $path = $split !== FALSE ? substr($_SERVER['REQUEST_URI'], 0, $split) : $_SERVER['REQUEST_URI'];
    return array(
      'path' => $path,
      'params' => $getsAssoc,
    );
  }

  /**
   * Takes an associative array and converts it to a list of params for a query string. This is like
   * http_build_query but it does not url encode the & separator, and gives control over urlencoding the array values.
   *
   * @param array $array
   *   Associative array to convert.
   * @param bool $encodeValues
   *   Default false. Set to true to URL encode the values being added to the
   *   string.
   *
   * @return string
   *   The query string.
   */
  public static function array_to_query_string($array, $encodeValues = FALSE) {
    $params = [];
    if (is_array($array)) {
      arsort($array);
      foreach ($array as $a => $b) {
        if (!is_array($b)) {
          if ($encodeValues) {
            $b = urlencode($b ?? '');
          }
          $params[] = "$a=$b";
        }
      }
    }
    return implode('&', $params);
  }

  /**
   * Applies a output template to an array. This is used to build the output for each item in a list,
   * such as a species checklist grid or a radio group.
   *
   * @param array $params
   *   Array holding the parameters to merge into the template.
   * @param string $template
   *   Name of the template to use, or actual template text if $useTemplateAsIs
   *   is set to true.
   * @param bool $useTemplateAsIs
   *   If true then the template parameter contains the actual template text,
   *   otherwise it is the name of a template in the $indicia_templates array.
   *   Default false.
   * @param bool $allowHtml
   *   If true then HTML is emitted as is from the parameter values inserted
   *   into the template, otherwise they are escaped.
   * @param bool $allowEscapeQuotes
   *   If true then parameter names can be suffixes -esape-quote,
   *   -escape-dblquote, * -escape-htmlquote or -escape-htmldblquote to insert
   *   backslashes or html entities into the replacements for string escaping.
   *
   * @return string
   *   HTML for the item label.
   */
  public static function mergeParamsIntoTemplate($params, $template, $useTemplateAsIs=FALSE, $allowHtml=FALSE, $allowEscapeQuotes=FALSE) {
    global $indicia_templates;
    // Build an array of all the possible tags we could replace in the template.
    $replaceTags = [];
    $replaceValues = [];
    foreach ($params as $param => $value) {
      if (!is_array($value) && !is_object($value)) {
        array_push($replaceTags, '{' . $param . '}');
        if ($allowEscapeQuotes) {
          array_push($replaceTags, '{' . $param . '-escape-quote}');
          array_push($replaceTags, '{' . $param . '-escape-dblquote}');
          array_push($replaceTags, '{' . $param . '-escape-htmlquote}');
          array_push($replaceTags, '{' . $param . '-escape-htmldblquote}');
          array_push($replaceTags, '{' . $param . '-escape-urlpath}');
        }
        // Allow sep to have <br/>.
        $value = ($param == 'sep' || $allowHtml) ? $value : htmlspecialchars($value ?? '', ENT_QUOTES, "UTF-8");
        // HTML attributes get automatically wrapped.
        if (in_array($param, self::$html_attributes) && !empty($value)) {
          $value = " $param=\"$value\"";
        }
        array_push($replaceValues, $value);
        if ($allowEscapeQuotes) {
          array_push($replaceValues, str_replace("'", "\'", $value ?? ''));
          array_push($replaceValues, str_replace('"', '\"', $value ?? ''));
          array_push($replaceValues, str_replace("'", "&#39;", $value ?? ''));
          array_push($replaceValues, str_replace('"', '&quot;', $value ?? ''));
          array_push($replaceValues, trim(preg_replace('/[^a-z0-9\-]/', '', str_replace(' ', '-', strtolower($value ?? ''))), '-'));
        }
      }
    }
    if (!$useTemplateAsIs) {
      $template = $indicia_templates[$template];
    }
    return str_replace($replaceTags, $replaceValues, $template);
  }

  /**
   * Uploads a file to the warehouse.
   *
   * Takes a file that has been uploaded to the client website upload folder,
   * and moves it to the warehouse upload folder using the data services.
   *
   * @param string $path
   *   Path to the file to upload, relative to the interim  image path folder
   *   (normally the client_helpers/upload folder.
   * @param bool $persist_auth
   *   Allows the write nonce to be preserved after sending the file, useful
   *   when several files are being uploaded.
   * @param array $writeAuth
   *   Write authorisation tokens, if not supplied then the $_POST array should
   *   contain them.
   * @param string $service
   *   Path to the service URL used. Default is data/handle_media, but could be
   *   import/upload_csv.
   *
   * @return string
   *   Error message, or true if successful.
   */
  public static function send_file_to_warehouse($path, $persist_auth = FALSE, $writeAuth = NULL, $service = 'data/handle_media', $removeLocalCopy = TRUE) {
    if ($writeAuth == NULL) {
      $writeAuth = $_POST;
    }
    $interimPath = self::getInterimImageFolder('fullpath');
    if (!file_exists($interimPath . $path)) {
      return "The file $path does not exist and cannot be uploaded to the Warehouse.";
    }
    $serviceUrl = self ::$base_url . "index.php/services/$service";
    // This is used by the file box control which renames uploaded files using
    // a guid system, so disable renaming on the server.
    $postargs = ['name_is_guid' => 'true'];
    // Attach authentication details.
    if (array_key_exists('auth_token', $writeAuth)) {
      $postargs['auth_token'] = $writeAuth['auth_token'];
    }
    if (array_key_exists('nonce', $writeAuth)) {
      $postargs['nonce'] = $writeAuth['nonce'];
    }
    if ($persist_auth) {
      $postargs['persist_auth'] = 'true';
    }
    $file_to_upload = ['media_upload' => '@' . realpath($interimPath . $path)];
    $response = self::http_post($serviceUrl, $file_to_upload + $postargs, FALSE);
    $output = json_decode($response['output'], TRUE);
    // Default is success.
    $r = TRUE;
    if (is_array($output)) {
      // An array signals an error.
      if (array_key_exists('error', $output)) {
        // Return the most detailed bit of error information.
        if (isset($output['errors']['media_upload'])) {
          $r = $output['errors']['media_upload'];
        }
        else {
          $r = $output['error'];
        }
      }
    }
    if ($removeLocalCopy) {
      unlink(realpath($interimPath . $path));
    }
    return $r;
  }

  /**
   * Internal function to find the path to the root of the site.
   *
   * Includes the trailing slash.
   *
   * @param bool $allowForDirtyUrls
   *   Set to true to allow for the content management system's approach to
   *   dirty URLs.
   */
  public static function getRootFolder($allowForDirtyUrls = FALSE) {
    // $_SERVER['SCRIPT_NAME'] can, in contrast to $_SERVER['PHP_SELF'], not
    // be modified by a visitor.
    if ($dir = trim(dirname($_SERVER['SCRIPT_NAME']), '\,/')) {
      $r = "/$dir/";
    }
    else {
      $r = '/';
    }
    $pathParam = ($allowForDirtyUrls && function_exists('variable_get') && variable_get('clean_url', 0) == '0') ? 'q' : '';
    $r .= empty($pathParam) ? '' : "?$pathParam=";
    return $r;
  }


  /**
   * Retrieve the user ID suffix for the auth_token.
   *
   * Allows the warehouse to be certain of the authorised user ID.
   *
   * @return string
   *   Colon then user ID to append to auth token, or empty string.
   */
  private static function getAuthTokenUserId() {
    global $_iform_warehouse_override;
    if ($_iform_warehouse_override || !function_exists('hostsite_get_user_field')) {
      // If linking to a different warehouse, don't do user authentication as
      // it causes an infinite loop.
      return '';
    }
    else {
      $indiciaUserId = hostsite_get_user_field('indicia_user_id');
      // Include user ID if logged in.
      return $indiciaUserId ? ":$indiciaUserId" : '';
    }
  }

  /**
   * Retrieves a token and inserts it into a data entry form which authenticates that the
   * form was submitted by this website.
   *
   * @param string $website_id
   *   Indicia ID for the website.
   * @param string $password
   *   Indicia password for the website.
   */
  public static function get_auth($website_id, $password) {
    self::$website_id = $website_id;
    // Include user ID if logged in.
    $authTokenUserId = self::getAuthTokenUserId();
    $postargs = "website_id=$website_id";
    $response = self::http_post(self::$base_url . 'index.php/services/security/get_nonce', $postargs);
    if (isset($response['status'])) {
      if ($response['status'] === 404) {
        throw new Exception(lang::get('The warehouse URL {1} was not found. Either the warehouse is down or the ' .
          'Indicia configuration is incorrect.', self::$base_url), 404);
      }
      else {
        throw new Exception($response['output'], $response['status']);
      }
    }
    $nonce = $response['output'];
    $authToken = sha1("$nonce:$password$authTokenUserId") . $authTokenUserId;
    $result = <<<HTML
<input id="auth_token" name="auth_token" type="hidden" class="hidden" value="$authToken" />
<input id="nonce" name="nonce" type="hidden" class="hidden" value="$nonce" />

HTML;
    return $result;
  }

  /**
   * Retrieves a read token and passes it back as an array suitable to drop into the
   * 'extraParams' options for an Ajax call.
   *
   * @param string $website_id
   *   Indicia ID for the website.
   * @param string $password
   *   Indicia password for the website.
   *
   * @return array
   *   Read authorisation tokens array.
   */
  public static function get_read_auth($website_id, $password) {
    // Store this for use with data caching.
    self::$website_id = $website_id;
    $cacheKey = [
      'readauth-wid' => $website_id,
    ];
    $r = self::cacheGet($cacheKey);
    if ($r === FALSE) {
      if (empty(self::$base_url)) {
        throw new Exception(lang::get('Indicia configuration is incorrect. Warehouse URL is not configured.'));
      }
      $postargs = "website_id=$website_id";
      $response = self::http_post(self::$base_url . 'index.php/services/security/get_read_nonce', $postargs, FALSE);
      if (isset($response['status'])) {
        if ($response['status'] === 404) {
          throw new Exception(lang::get('The warehouse URL {1} was not found. Either the warehouse is down or the ' .
            'Indicia configuration is incorrect.', self::$base_url), 404);
        }
        else {
          throw new Exception($response['output'], $response['status']);
        }
      }
      $nonce = $response['output'];
      if (substr($nonce, 0, 9) === '<!DOCTYPE') {
        throw new Exception(lang::get('Could not authenticate against the warehouse. Is the server down?'));
      }
      $r = [
        'nonce' => $nonce,
      ];
      // Keep in cache for max 10 minutes. It MUST be shorter than the normal
      // cache lifetime so this expires more frequently.
      self::cacheSet($cacheKey, json_encode($r), 600);
    }
    else {
      $r = json_decode($r, TRUE);
    }
    if (function_exists('hostsite_get_user_field')) {
      // Include user ID if logged in.
      $authTokenUserId = self::getAuthTokenUserId();
      // Attach a user specific auth token.
      $r['auth_token'] = sha1("$r[nonce]:$password$authTokenUserId") . $authTokenUserId;
    }
    else {
      $r['auth_token'] = sha1("$r[nonce]:$password");
    }
    self::$js_read_tokens = $r;
    return $r;
  }

  /**
   * Retrieves read and write nonce tokens from the warehouse.
   *
   * @param string $website_id
   *   Indicia ID for the website.
   * @param string $password
   *   Indicia password for the website.
   *
   * @return array
   *   Returns an array containing:
   *   * 'read' => the read authorisation array,
   *   * 'write' => the write authorisation input controls to insert into your
   *     form.
   *   * 'write_tokens' => the write authorisation array, if needed as separate
   *     tokens rather than just placing in form.
   */
  public static function get_read_write_auth($website_id, $password) {
    self::$website_id = $website_id; /* Store this for use with data caching */
    // Include user ID if logged in.
    $authTokenUserId = self::getAuthTokenUserId();
    $postargs = "website_id=$website_id";
    $response = self::http_post(self::$base_url . 'index.php/services/security/get_read_write_nonces', $postargs);
    if (array_key_exists('status', $response)) {
      if ($response['status'] === 404) {
        throw new Exception(lang::get('The warehouse URL {1} was not found. Either the warehouse is down or the ' .
          'Indicia configuration is incorrect.', self::$base_url), 404);
      }
      else {
        throw new Exception($response['output'], $response['status']);
      }
    }
    $nonces = json_decode($response['output'], TRUE);
    $writeAuthToken = sha1("$nonces[write]:$password$authTokenUserId") . $authTokenUserId;
    $readAuthToken = sha1("$nonces[read]:$password$authTokenUserId") . $authTokenUserId;
    $write = <<<HTML
<input id="auth_token" name="auth_token" type="hidden" class="hidden" value="$writeAuthToken" />
<input id="nonce" name="nonce" type="hidden" class="hidden" value="$nonces[write]" />

HTML;
    self::$js_read_tokens = [
      'auth_token' => $readAuthToken,
      'nonce' => $nonces['read'],
    ];
    return [
      'write' => $write,
      'read' => self::$js_read_tokens,
      'write_tokens' => [
        'auth_token' => $writeAuthToken,
        'nonce' => $nonces['write'],
      ],
    ];
  }

  /**
   * Collects all inline JavaScript.
   *
   * Helper function to collect javascript code in a single location. Should be
   * called at the end of each HTML page which uses the data entry helper so
   * output all JavaScript required by previous calls.
   *
   * @param bool $closure
   *   Set to true to close the JS with a function to ensure $ will refer to
   *   jQuery.
   *
   * @return string
   *   JavaScript to insert into the page for all the controls added to the
   *   page so far.
   *
   * @link https://github.com/Indicia-Team/client_helperswiki/TutorialBuildingBasicPage#Build_a_data_entry_page
   */
  public static function dump_javascript($closure = FALSE) {
    // Add the default stylesheet to the end of the list, so it has highest CSS
    // priority.
    if (self::$default_styles) {
      self::add_resource('defaultStylesheet');
    }
    // Jquery validation js has to be added at this late stage, because only
    // then do we know all the messages required.
    self::setup_jquery_validation_js();
    $dump = self::internal_dump_resources(self::$required_resources);
    $dump .= "<script type='text/javascript'>/* <![CDATA[ */\n" . self::getIndiciaData() . "\n/* ]]> */</script>\n";
    $dump .= self::get_scripts(self::$javascript, self::$late_javascript, self::$onload_javascript, TRUE, $closure);
    // Ensure scripted JS does not output again if recalled.
    self::$javascript = "";
    self::$late_javascript = "";
    self::$onload_javascript = "";
    return $dump;
  }

  /**
   * Retreives JavaScript required to initialise indiciaData.
   *
   * Builds JavaScript to initialise an object containing any data added to
   * self::$indiciaData.
   *
   * @return string
   *   JavaScript.
   */
  public static function getIndiciaData() {
    require_once 'prebuilt_forms/includes/language_utils.php';
    global $indicia_templates;
    self::$indiciaData['imagesPath'] = self::$images_path;
    self::$indiciaData['warehouseUrl'] = self::$base_url;
    $proxyUrl = self::getRootFolder() . self::relative_client_helper_path() . 'proxy.php';
    self::$indiciaData['proxyUrl'] = $proxyUrl;
    $protocol = empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off' ? 'http' : 'https';
    self::$indiciaData['protocol'] = $protocol;
    // Add some useful templates.
    self::$indiciaData['templates'] = array_merge([
      'messageBox' => $indicia_templates['messageBox'],
      'warningBox' => $indicia_templates['warningBox'],
      'buttonDefaultClass' => $indicia_templates['buttonDefaultClass'],
      'buttonHighlightedClass' => $indicia_templates['buttonHighlightedClass'],
      'buttonSmallClass' => 'btn-xs',
      'jQueryValidateErrorClass' => $indicia_templates['error_class'],
    ], self::$indiciaData['templates']);
    self::$indiciaData['formControlClass'] = $indicia_templates['formControlClass'];
    self::$indiciaData['inlineErrorClass'] = $indicia_templates['error_class'];
    self::$indiciaData['dateFormat'] = self::$date_format;
    $rootFolder = helper_base::getRootFolder(TRUE);
    self::$indiciaData['rootFolder'] = $rootFolder;
    if (function_exists('hostsite_get_user_field')) {
      $language = hostsite_get_user_field('language');
      self::$indiciaData['currentLanguage'] = $language;
      self::$indiciaData['currentLanguage3'] = iform_lang_iso_639_2($language);
      self::$indiciaData['training'] = hostsite_get_user_field('training') === '1';
    }
    // Add language strings used in the indicia.functions.js file.
    self::addLanguageStringsToJs('indiciaFns', [
      'hideInfo' => 'Hide info',
    ]);
    $r = [];
    foreach (self::$indiciaData as $key => $data) {
      if (is_array($data)) {
        $value = json_encode($data);
      }
      elseif (is_string($data)) {
        $data = str_replace("'", "\\'", $data);
        $value = "'$data'";
      }
      elseif (is_bool($data)) {
        $value = $data ? 'true' : 'false';
      }
      elseif (is_null($data)) {
        $value = 'null';
      }
      else {
        $value = $data;
      }
      if (preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
        $r[] = "indiciaData.$key = $value;";
      }
      else {
        $r[] = "indiciaData['$key'] = $value;";
      }
    }
    $dataJs = implode("\n", $r) . "\n";
    return <<<JS
if (typeof indiciaData === 'undefined') {
  indiciaData = {};
}
$dataJs

JS;
  }

  /**
   * Internal implementation of the dump_javascript method.
   *
   * Takes the javascript and resources list as flexible parameters, rather
   * than using the globals.
   *
   * @param array $resources
   *   List of resources to include.
   */
  protected static function internal_dump_resources($resources) {
    $libraries = '';
    $stylesheets = '';
    if (isset($resources)) {
      $resourceList = self::get_resources();
      foreach ($resources as $resource) {
        if (!in_array($resource, self::$dumped_resources)) {
          if (isset($resourceList[$resource]['stylesheets'])) {
            foreach ($resourceList[$resource]['stylesheets'] as $s) {
              $stylesheets .= "<link rel='stylesheet' type='text/css' href='$s' />\n";
            }
          }
          if (isset($resourceList[$resource]['javascript'])) {
            foreach ($resourceList[$resource]['javascript'] as $j) {
              $libraries .= "<script type=\"text/javascript\" src=\"$j\"></script>\n";
            }
          }
          // Record the resource as being dumped, so we don't do it again.
          array_push(self::$dumped_resources, $resource);
        }
      }
    }
    return $stylesheets . $libraries;
  }

  /**
   * Retrieve inline JavaScript to add to the page.
   *
   * A utility function for building the inline script content which should be
   * inserted into a page from the javaascript, late javascript and onload
   * javascript. Can optionally include the script tags wrapper around the
   * script generated.
   *
   * @param string $javascript
   *   JavaScript to run when the page is ready, i.e. in $(document).ready.
   * @param string $late_javascript
   *   JavaScript to run at the end of $(document).ready.
   * @param string $onload_javascript
   *   JavaScript to run in the window.onLoad handler which comes later in the
   *   page load process.
   * @param bool $includeWrapper
   *   If true then includes script tags around the script.
   * @param bool $closure
   *   Set to true to close the JS with a function to ensure $ will refer to
   *   jQuery.
   *
   * @return string
   *   The JavaScript.
   */
  public static function get_scripts($javascript, $late_javascript, $onload_javascript, $includeWrapper = FALSE, $closure = FALSE) {
    if (!empty($javascript) || !empty($late_javascript) || !empty($onload_javascript)) {
      $script = '';
      if (!empty(self::$website_id)) {
        // Not on warehouse.
        $script .= "indiciaData.website_id = " . self::$website_id . ";\n";
        if (function_exists('hostsite_get_user_field')) {
          $userId = hostsite_get_user_field('indicia_user_id');
          if ($userId) {
            $script .= "indiciaData.user_id = $userId;\n";
          }
        }
      }

      if (self::$js_read_tokens) {
        self::$js_read_tokens['url'] = self::getProxiedBaseUrl();
        $script .= "indiciaData.read = " . json_encode(self::$js_read_tokens) . ";\n";
      }
      if (!self::$is_ajax) {
        $script .= "\n$(document).ready(function() {\n";
      }
      $script .= <<<JS
indiciaData.documentReady = 'started';
if (typeof indiciaFns.initDataSources !== 'undefined') {
  indiciaFns.initDataSources();
}
$javascript
$late_javascript
indiciaFns.setupTabLazyLoad();
// Elasticsearch source population.
if (typeof indiciaFns.hookupDataSources !== 'undefined') {
  indiciaFns.hookupDataSources();
  // Populate unless a report filter builder present as that will do it for us.
  if (!indiciaData.lang.reportFilters) {
    indiciaFns.populateDataSources();
  }
}
// if window.onload already happened before document.ready, ensure any hooks are still run.
if (indiciaData.windowLoaded === 'done') {
  $.each(indiciaData.onloadFns, function(idx, fn) {
    fn();
  });
}
indiciaData.documentReady = 'done';

JS;
      if (!self::$is_ajax) {
        $script .= "});\n";
      }
      if (!empty($onload_javascript)) {
        if (self::$is_ajax) {
          // Ajax requests are simple - page has already loaded so just return
          // the javascript.
          $script .= "$onload_javascript\n";
        }
        else {
          // Create a function that can be called from window.onLoad. Don't put
          // it directly in the onload in case another form is added to the
          // same page which overwrites onload.
          $script .= <<<JS
indiciaData.onloadFns.push(function() {
  $onload_javascript
});
JS;
        }
      }
      $script .= <<<JS
window.onload = function() {
  indiciaData.windowLoad = 'started';
  // Ensure this is only run after document.ready.
  if (indiciaData.documentReady === 'done') {
    $.each(indiciaData.onloadFns, function(idx, fn) {
      fn();
    });
  }
  indiciaData.windowLoaded = 'done';
}

JS;
      if ($closure) {
        $script = <<<JS
(function ($) {
  $script
})(jQuery);

JS;
      }
      if ($includeWrapper) {
        $script = <<<JS
<script type='text/javascript'>/* <![CDATA[ */
  $script
/* ]]> */</script>

JS;
      }
    }
    else {
      $script = '';
    }
    return $script;
  }

  /**
   * If required, setup jQuery validation. This JavaScript must be added at the end of form preparation otherwise we would
   * not know all the control messages. It will normally be called by dump_javascript automatically, but is exposed here
   * as a public method since the iform Drupal module does not call dump_javascript, but is responsible for adding JavaScript
   * to the page via drupal_add_js.
   */
  public static function setup_jquery_validation_js() {
    // In the following block, we set the validation plugin's error class to our template.
    // We also define the error label to be wrapped in a <p> if it is on a newline.
    if (self::$validated_form_id && !self::$validationInitialised) {
      self::$validationInitialised = TRUE;
      global $indicia_templates;
      self::$javascript .= "
indiciaData.controlWrapErrorClass = '$indicia_templates[controlWrapErrorClass]';
var validator = $('#" . self::$validated_form_id . "').validate({
  ignore: \":hidden:not(.date-text),.inactive\",
  errorClass: \"$indicia_templates[error_class]\",
  " . (in_array('inline', self::$validation_mode) ? "" : "errorElement: 'p',") ."
  highlight: function(el, errorClass) {
    var controlWrap = $(el).closest('.ctrl-wrap');
    if (controlWrap.length > 0) {
      $(controlWrap).addClass(indiciaData.controlWrapErrorClass);
    }
    if ($(el).is(':radio') || $(el).is(':checkbox')) {
      //if the element is a radio or checkbox group then highlight the group
      var jqBox = $(el).parents('.control-box');
      if (jqBox.length !== 0) {
        jqBox.eq(0).addClass('ui-state-error');
      } else {
        $(el).addClass('ui-state-error');
      }
    } else {
      $(el).addClass('ui-state-error');
    }
  },
  unhighlight: function(el, errorClass) {
    var controlWrap = $(el).closest('.ctrl-wrap');
    if ($(el).is(':radio') || $(el).is(':checkbox')) {
      //if the element is a radio or checkbox group then highlight the group
      var jqBox = $(el).parents('.control-box');
      if (jqBox.length !== 0) {
        jqBox.eq(0).removeClass('ui-state-error');
      } else {
        $(el).removeClass('ui-state-error');
      }
    } else {
      $(el).removeClass('ui-state-error');
    }
    if (controlWrap.length > 0 && $(controlWrap).find('.ui-state-error').length === 0) {
      $(controlWrap).removeClass(indiciaData.controlWrapErrorClass);
    }
  },
  invalidHandler: $indicia_templates[invalid_handler_javascript],
  messages: " . json_encode(self::$validation_messages) . ",".
  // Do not place errors if 'message' not in validation_mode
  // if it is present, put radio button messages at start of list:
  // radio and checkbox elements come before their labels, so putting the error after the invalid element
  // places it between the element and its label.
  // most radio button validation will be "required"
  (in_array('message', self::$validation_mode) ? "
  errorPlacement: function(error, element) {
    var jqBox, nexts;
    // If using Bootstrap input-group class, put the message after the group
    var inputGroup = $(element).closest('.input-group');
    if (inputGroup.length) {
      element = inputGroup;
    } else {
      if (element.is(':radio')||element.is(':checkbox')) {
        jqBox = element.parents('.control-box');
        element=jqBox.length === 0 ? element : jqBox;
      }
      nexts=element.nextAll(':visible');
      if (nexts) {
        $.each(nexts, function() {
          if ($(this).hasClass('deh-required') || $(this).hasClass('locked-icon') || $(this).hasClass('unlocked-icon')) {
            element = this;
          }
        });
      }
    }
    error.insertAfter(element);
  }" : "
  errorPlacement: function(error, element) {}") ."
});
//Don't validate whilst user is still typing in field
if (typeof validator!=='undefined') {
  validator.settings.onkeyup = false;
}
\n";
    }
  }

  /**
   * Apply options to a templaet.
   *
   * Internal method to build a control from its options array and its
   * template. Outputs the prefix template, a label (if in the options),
   * a control, the control's errors and a suffix template.
   *
   * @param string $template
   *   Name of the control template, from the global $indicia_templates
   *   variable.
   * @param array $options
   *   Options array containing the control replacement values for the
   *   templates. Options can contain a setting for prefixTemplate or
   *   suffixTemplate to override the standard templates.
   */
  public static function apply_template($template, $options) {
    global $indicia_templates;
    // Don't need the extraParams - they are just for service communication.
    $options['extraParams'] = NULL;
    // Set default validation error output mode.
    if (!array_key_exists('validation_mode', $options)) {
      $options['validation_mode'] = self::$validation_mode;
    }
    // Decide if the main control has an error. If so, highlight with the error
    // class and set it's title.
    $error = '';
    if (self::$validation_errors !== NULL) {
      if (array_key_exists('fieldname', $options)) {
        $error = self::check_errors($options['fieldname'], TRUE);
      }
    }
    // Add a hint to the control if there is an error and this option is set,
    // or a hint option.
    if (($error && in_array('hint', $options['validation_mode'])) || isset($options['hint'])) {
      $hint = ($error && in_array('hint', $options['validation_mode'])) ? [$error] : [];
      if (isset($options['hint'])) {
        $hint[] = $options['hint'];
      }
      $options['title'] = 'title="' . implode(' : ', $hint) . '"';
    }
    else {
      $options['title'] = '';
    }
    $options = array_merge([
      'class' => '',
      'disabled' => '',
      'readonly' => '',
      'wrapClasses' => [],
    ], $options);
    $options['wrapClasses'] = empty($options['wrapClasses']) ? '' : ' ' . implode(' ', $options['wrapClasses']);
    if (array_key_exists('maxlength', $options)) {
      $options['maxlength'] = "maxlength=\"$options[maxlength]\"";
    }
    else {
      $options['maxlength'] = '';
    }
    // Add an error class to colour the control if there is an error and this
    // option is set.
    if ($error && in_array('colour', $options['validation_mode'])) {
      $options['class'] .= ' ui-state-error';
      if (array_key_exists('outerClass', $options)) {
        $options['outerClass'] .= ' ui-state-error';
      }
      else {
        $options['outerClass'] = 'ui-state-error';
      }
    }
    // Allows a form control to have a class specific to the base theme.
    if (isset($options['isFormControl'])) {
      $options['class'] .= " $indicia_templates[formControlClass]";
    }
    // Add validation metadata to the control if specified, as long as control
    // has a fieldname.
    if (array_key_exists('fieldname', $options)) {
      $validationClasses = self::build_validation_class($options);
      $options['class'] .= " $validationClasses";
    }

    // Replace html attributes with their wrapped versions, e.g. a class
    // becomes class="...".
    foreach (self::$html_attributes as $name => $attr) {
      if (!empty($options[$name])) {
        $options[$name] = ' ' . $attr . '="' . $options[$name] . '"';
      }
    }

    // If options contain a help text, output it at the end if that is the
    // preferred position.
    $r = self::get_help_text($options, 'before');
    // Add prefix.
    $r .= self::apply_static_template('prefix', $options);

    // Add a label only if specified in the options array. Link the label to
    // the inputId if available, otherwise the fieldname (as the fieldname
    // control could be a hidden control).
    if (!empty($options['label'])) {
      $labelTemplate = isset($options['labelTemplate']) ? $indicia_templates[$options['labelTemplate']] :
      	(substr($options['label'], -1) == '?' ? $indicia_templates['labelNoColon'] : $indicia_templates['label']);
      $label = str_replace(
          ['{label}', '{id}', '{labelClass}'],
          [
            $options['label'],
            $options['inputId'] ?? $options['id'] ?? '',
            array_key_exists('labelClass', $options) ? " class=\"$options[labelClass]\"" : '',
          ],
          $labelTemplate
      );
    }
    if (!empty($options['label']) && (!isset($options['labelPosition']) || $options['labelPosition'] != 'after')) {
      $r .= $label;
    }
    // Output the main control.
    $control = self::apply_replacements_to_template($indicia_templates[$template], $options);
    $addons = '';

    if (isset($options['afterControl'])) {
      $addons .= $options['afterControl'];
    }
    // Add a lock icon to the control if the lockable option is set to true.
    if (array_key_exists('lockable', $options) && $options['lockable'] === TRUE) {
      $addons .= self::apply_replacements_to_template($indicia_templates['lock_icon'], $options);
      if (!self::$using_locking) {
        self::$using_locking = TRUE;
        $options['lock_form_mode'] = self::$form_mode ? self::$form_mode : 'NEW';
        // Write lock javascript at the start of the late javascript so after
        // control setup but before any other late javascript.
        self::$late_javascript = self::apply_replacements_to_template($indicia_templates['lock_javascript'], $options) . self::$late_javascript;
        self::add_resource('indicia_locks');
      }
    }
    if (isset($validationClasses) && !empty($validationClasses) && strpos($validationClasses, 'required') !== FALSE) {
      $addons .= self::apply_static_template('requiredsuffix', $options);
    }
    // Add an error icon to the control if there is an error and this option
    // set.
    if ($error && in_array('icon', $options['validation_mode'])) {
      $addons .= $indicia_templates['validation_icon'];
    }
    // If addons are going to be placed after the control, give the template a
    // chance to wrap them together with the main control in an element.
    if ($addons) {
      $r .= self::apply_replacements_to_template($indicia_templates['controlAddonsWrap'], [
        'control' => $control,
        'addons' => $addons,
      ]);
    }
    else {
      $r .= $control;
    }
    // Label can sometimes be placed after the control.
    if (!empty($options['label']) && isset($options['labelPosition']) && $options['labelPosition'] == 'after') {
      $r .= $label;
    }
    // Add a message to the control if there is an error and this option is
    // set.
    if ($error && in_array('message', $options['validation_mode'])) {
      $r .= self::apply_error_template($error, $options['fieldname']);
    }

    // Add suffix
    $r .= self::apply_static_template('suffix', $options);

    // If options contain a help text, output it at the end if that is the
    // preferred position.
    $r .= self::get_help_text($options, 'after');
    if (isset($options['id'])) {
      $wrap = empty($options['controlWrapTemplate']) ? $indicia_templates['controlWrap'] : $indicia_templates[$options['controlWrapTemplate']];
      $r = str_replace([
        '{id}',
        '{wrapClasses}',
        '{control}',
      ], [
        str_replace(':', '-', $options['id']),
        $options['wrapClasses'],
        "\n$r",
      ], $wrap);
    }
    if (!empty($options['tooltip'])) {
      // preliminary support for
      $id = str_replace(':', '\\\\:', array_key_exists('inputId', $options) ? $options['inputId'] : $options['id']);
      $options['tooltip'] = addcslashes($options['tooltip'], "'");
      self::$javascript .= "$('#$id').attr('title', '$options[tooltip]');\n";
    }
    return $r;
  }

  /**
   * Enable browser validation for forms.
   *
   * Call the enable_validation method to turn on client-side validation for
   * any controls with validation rules defined.
   * To specify validation on each control, set the control's options array
   * to contain a 'validation' entry. This must be set to an array of
   * validation rules in Indicia validation format. For example,
   * `'validation' => array('required', 'email')`.
   *
   * @param string $form_id
   *   @form_id Id of the form the validation is being attached to.
   */
  public static function enable_validation($form_id) {
    self::$validated_form_id = $form_id;
    self::$javascript .= "indiciaData.validatedFormId = '" . self::$validated_form_id . "';\n";
    // Prevent double submission of the form.
    self::$javascript .= "$('#$form_id').submit(function(e) {
  if (typeof $('#$form_id').valid === 'undefined' || $('#$form_id').valid()) {
    if (typeof indiciaData.formSubmitted==='undefined' || !indiciaData.formSubmitted) {
      indiciaData.formSubmitted = true;
    } else {
      e.preventDefault();
      return false;
    }
  }
});\n";
    self::add_resource('validation');
    // Allow i18n on validation messages.
    if (lang::get('validation_required') != 'validation_required') {
      self::$late_javascript .= "$.validator.messages.required = \"" . lang::get('validation_required') . "\";\n";
    }
    if (lang::get('validation_max') != 'validation_max') {
      self::$late_javascript .= "$.validator.messages.max = $.validator.format(\"" . lang::get('validation_max') . "\");\n";
    }
    if (lang::get('validation_min') != 'validation_min') {
      self::$late_javascript .= "$.validator.messages.min = $.validator.format(\"" . lang::get('validation_min') . "\");\n";
    }
    if (lang::get('validation_number') != 'validation_number') {
      self::$late_javascript .= "$.validator.messages.number = $.validator.format(\"" . lang::get('validation_number') . "\");\n";
    }
    if (lang::get('validation_digits') != 'validation_digits') {
      self::$late_javascript .= "$.validator.messages.digits = $.validator.format(\"" . lang::get('validation_digits') . "\");\n";
    }
    if (lang::get('validation_integer') != 'validation_integer') {
      self::$late_javascript .= "$.validator.messages.integer = $.validator.format(\"" . lang::get('validation_integer') . "\");\n";
    }
  }

  /**
   * Explodes a value on several lines into an array split on the lines. Tolerates any line ending.
   * @param string $value A multi-line string to be split.
   * @return array An array with one entry per line in $value.
   */
  public static function explode_lines($value) {
    $structure = str_replace("\r\n", "\n", $value);
    $structure = str_replace("\r", "\n", $structure);
    return explode("\n", trim($structure));
  }

  /**
   * Explodes a value with key=value several lines into an array split on the lines. Tolerates any line ending.
   * @param string $value A multi-line string to be split.
   * @return array An associative array with one entry per line in $value. Array keys are the items before the = on each line,
   * and values are the data after the = on each line.
   */
  public static function explode_lines_key_value_pairs($value) {
    preg_match_all("/([^=\r\n]+)=([^\r\n]+)/", $value, $pairs);
    $pairs[1] = array_map('trim', $pairs[1]);
    $pairs[2] = array_map('trim', $pairs[2]);
    if (count($pairs[1]) == count($pairs[2]) && count($pairs[1]) != 0) {
      return array_combine($pairs[1], $pairs[2]);
    }
    else {
      return [];
    }
  }

  /**
   * Prepares options for a control are to be passed through to JavaScript.
   *
   * Picks the entries from the options array which are in $keysToPassThrough
   * then returns a JSON encoded string that can be added to indiciaData.
   *
   * @param array $options
   *   Control options array.
   * @param array $keysToPassThrough
   *   Array of the names of the options which are going to be passed into
   *   JavaScript settings.
   * @param bool $encodeSpecialChars
   *   If set to TRUE, then the result is encoded using htmlspecialchars()
   *   e.g. for when the response is to be included in an HTML attribute.
   *
   * @return string
   *   JSON encoded string.
   */
  public static function getOptionsForJs(array $options, array $keysToPassThrough, $encodeSpecialChars = FALSE) {
    $dataOptions = array_intersect_key($options, array_combine($keysToPassThrough, $keysToPassThrough));
    $r = json_encode($dataOptions);
    return $encodeSpecialChars ? htmlspecialchars($r) : $r;
  }

  /**
   * Utility function to load a list of terms from a termlist.
   *
   * @param array $auth
   *   Read authorisation array.
   * @param mixed $termlist
   *   Either the id or external_key of the termlist to load.
   * @param array $filter
   *   List of the terms that are required, or null for all terms.
   * @return array
   *   Output of the Warehouse data services request for the terms.
   *
   * @throws \Exception
   */
  public static function get_termlist_terms($auth, $termlist, $filter = NULL) {
    if (!is_int($termlist)) {
      $termlistFilter=array('external_key' => $termlist);
      $list = self::get_population_data(array(
        'table' => 'termlist',
        'extraParams' => $auth['read'] + $termlistFilter
      ));
      if (count($list)==0)
        throw new Exception("Termlist $termlist not available on the Warehouse");
      if (count($list)>1)
        throw new Exception("Multiple termlists identified by $termlist found on the Warehouse");
      $termlist = $list[0]['id'];
    }
    $extraParams = $auth['read'] + array(
      'view' => 'detail',
      'termlist_id' => $termlist
    );
    // apply a filter for the actual list of terms, if required.
    if ($filter)
      $extraParams['query'] = urlencode(json_encode(['in' => ['term', $filter]]));
    $terms = self::get_population_data([
      'table' => 'termlists_term',
      'extraParams' => $extraParams,
    ]);
    return $terms;
  }

  /**
   * Apply a set of replacements to a string.
   *
   * Useful when configuration allows the specification of strings that will
   * be output in the HTML (e.g dynamic content or report grid footers). The
   * following tokens are replaced:
   * * {rootFolder} - relative URL to prefix links generated within the site.
   * * {currentUrl} - relative URL of the current page.
   * * {sep} - either ? or & as required to append to links before adding
   *     parameters. Will be ? unless using dirty URLs.
   * * {warehouseRoot} - root URL of the warehouse.
   * * {geoserverRoot} - root URL of the GeoServer instance if configured.
   * * {nonce} - read nonce token, used to generate reporting links.
   * * {auth} - read auth token, used to generate reporting links.
   * * {indicia_user_id} - Indicia warehouse user ID.
   * * {uid} - Drupal uid.
   * * {website_id} - Indicia warehouse website ID.
   * * {t:<phrase>} - returns translated version of <phrase>.
   *
   * @param string $string
   *   String to have tokens replaced.
   * @param array $readAuth
   *   Read authorisation tokens.
   *
   * @return string
   *   String with tokens replaced.
   */
  public static function getStringReplaceTokens($string, $readAuth) {
    $rootFolder = self::getRootFolder(TRUE);
    $currentUrl = self::get_reload_link_parts();
    // Amend currentUrl path if we have Drupal 7 dirty URLs so javascript will
    // work properly.
    if (isset($currentUrl['params']['q']) && strpos($currentUrl['path'], '?') === FALSE) {
      $currentUrl['path'] = $currentUrl['path'] . '?q=' . $currentUrl['params']['q'];
    }
    // Do translations.
    if (preg_match_all('/{t:([^}]+)}/', $string, $matches)) {
      for ($i = 0; $i < count($matches[0]); $i++) {
        $string = str_replace($matches[0][$i], lang::get($matches[1][$i]), $string);
      }
    }
    // Note a couple of repeats in the list for legacy reasons.
    return str_replace(
      [
        '{rootFolder}',
        '{currentUrl}',
        '{sep}',
        '{warehouseRoot}',
        '{geoserverRoot}',
        '{nonce}',
        '{auth}',
        '{iUserID}',
        '{indicia_user_id}',
        '{user_id}',
        '{uid}',
        '{website_id}',
      ],
      [
        $rootFolder,
        $currentUrl['path'],
        strpos($rootFolder, '?') === FALSE ? '?' : '&',
        self::$base_url,
        self::$geoserver_url,
        "nonce=$readAuth[nonce]",
        "auth_token=$readAuth[auth_token]",
        hostsite_get_user_field('indicia_user_id'),
        hostsite_get_user_field('indicia_user_id'),
        hostsite_get_user_field('id'),
        hostsite_get_user_field('id'),
        self::$website_id,
      ],
      $string
    );
  }

  /**
   * Converts the validation rules in an options array into a string that can be used as the control class,
   * to trigger the jQuery validation plugin.
   * @param $options. Control options array. For validation to be applied should contain a validation entry,
   * containing a single validation string or an array of strings.
   * @return string The validation rules formatted as a class.
   */
  protected static function build_validation_class($options) {
    global $custom_terms;
    $rules = (array_key_exists('validation', $options) ? $options['validation'] : []);
    if (!is_array($rules)) $rules = array($rules);
    if (array_key_exists($options['fieldname'], self::$default_validation_rules)) {
      $rules = array_merge($rules, self::$default_validation_rules[$options['fieldname']]);
    }
    // Build internationalised validation messages for jQuery to use, if the fields have internationalisation strings specified
    foreach ($rules as $rule) {
      if (isset($custom_terms) && array_key_exists($options['fieldname'], $custom_terms))
        self::$validation_messages[$options['fieldname']][$rule] = sprintf(lang::get("validation_$rule"),
          lang::get($options['fieldname']));
    }
    // Convert these rules into jQuery format.
    return self::convertToJqueryValMetadata($rules, $options);
  }

  /**
   * Returns templated help text for a control, but only if the position matches the $helpTextPos value, and
   * the $options array contains a helpText entry.
   * @param array $options Control's options array. Can specify the class for the help text item using option helpTextClass.
   * @param string $pos Either before or after. Defines the position that is being requested.
   * @return string Templated help text, or nothing.
   */
  protected static function get_help_text($options, $pos) {
    $options = array_merge(array('helpTextClass' => 'helpText'), $options);
    if (array_key_exists('helpText', $options) && !empty($options['helpText']) && self::$helpTextPos == $pos) {
      $options['helpText'] = lang::get($options['helpText']);
      return str_replace('{helpText}', $options['helpText'], self::apply_static_template('helpText', $options));
    }
    else {
      return '';
    }
  }

  /**
   * Takes a template string (e.g. <div id="{id}">) and replaces the tokens with the equivalent values looked up from
   * the $options array. Tokens suffixed |escape have HTML escaping applied, e.g. <div id="{id}">{value|escape}</div>
   * @param string $template
   *   The templatable string.
   * @param array $options
   *   The array of items which can be merged into the template.
   */
  protected static function apply_replacements_to_template($template, $options) {
    // Build an array of all the possible tags we could replace in the template.
    $replaceTags=[];
    $replaceValues=[];
    foreach (array_keys($options) as $option) {
      $value = is_array($options[$option]) || is_object($options[$option]) || is_null($options[$option]) ? '' : $options[$option];
      array_push($replaceTags, '{'.$option.'}');
      array_push($replaceValues, $value);
      array_push($replaceTags, '{'.$option.'|escape}');
      array_push($replaceValues, htmlspecialchars($value ?? ''));
    }
    // Use strtr instead of preg_replace so earlier replacements get polluted
    // by later ones, e.g. {id} in default value picking up the control ID.
    // See https://www.php.net/manual/en/function.str-replace.php#88569
    return strtr($template, array_combine($replaceTags, $replaceValues));
  }

   /**
  * Takes a list of validation rules in Kohana/Indicia format, and converts them to the jQuery validation
  * plugin metadata format.
  * @param array $rules List of validation rules to be converted.
  * @param array $options Options passed to the validated control.
  * @return string Validation metadata classes to add to the input element.
  * @todo Implement a more complete list of validation rules.
  */
  protected static function convertToJqueryValMetadata($rules, $options) {
    $converted = [];
    foreach ($rules as $rule) {
      // Detect the rules that can simply be passed through
      $rule = trim($rule);
      $mappings = [
        'required' => ['jqRule' => 'required'],
        'dateISO' => ['jqRule' => 'dateISO'],
        'email' => ['jqRule' => 'email'],
        'url' => ['jqRule' => 'url'],
        'time' => ['jqRule' => 'time'],
        'integer' => ['jqRule' => 'integer'],
        'digit' => ['jqRule' => 'digits'],
        'numeric' => ['jqRule' => 'number'],
        'maximum' => ['jqRule' => 'max', 'valRegEx' => '-?\d*(\.\d+)?'],
        'minimum' => ['jqRule' => 'min', 'valRegEx' => '-?\d*(\.\d+)?'],
        'mingridref' => ['jqRule' => 'mingridref', 'valRegEx' => '\d+'],
        'maxgridref' => ['jqRule' => 'maxgridref', 'valRegEx' => '\d+'],
        'regex' => ['jqRule' => 'pattern', 'valRegEx' => '.*'],
      ];
      $arr = explode('[', $rule);
      $ruleName = $arr[0];
      if (!empty($mappings[$ruleName])) {
        $config = $mappings[$ruleName];
        if (isset($config['valRegEx'])) {
          if (preg_match("/$ruleName\[(?P<val>$config[valRegEx])\]/", $rule, $matches)) {
            $converted[] = "$config[jqRule]:$matches[val]";
          }
        }
        else {
          $converted[] = "$config[jqRule]:true";
        }
      } elseif ($ruleName === 'date') {
        $converted[] = 'indiciaDate:true';
      } elseif ($ruleName === 'length' && preg_match("/length\[(?P<val>\d+(,\d+)?)\]/", $rule, $matches)) {
        // Special case for length Kohana rule which can map to jQuery minlenth
        // and maxlength rules.
        $range = explode(',', $matches['val']);
        if (count($range) === 1) {
          $converted[] = "maxlength:$range[0]";
        } elseif (count($range) === 2) {
          $converted[] = "minlength:$range[0]";
          $converted[] = "maxlength:$range[1]";
        }
      }
    }
    if (count($converted) === 0) {
      return '';
    }
    else {
      return '{'. implode(', ', $converted) .'}';
    }
  }

 /**
  * Returns a static template which is either a default template or one specified in the options.
  *
  * @param string $name
  *   The static template type. e.g. prefix or suffix.
  * @param array $options
  *   Array of options which may contain a template name.
  *
  * @return string
  *   Template value.
  */
  public static function apply_static_template($name, $options) {
    global $indicia_templates;
    $key = $name .'Template';
    if (array_key_exists($key, $options)) {
      //a template has been specified
      if (array_key_exists($options[$key], $indicia_templates))
        //the specified template exists
        $template = $indicia_templates[$options[$key]];
      else
        $template = $indicia_templates[$name] .
        '<span class="ui-state-error">Code error: suffix template '.$options[$key].' not in list of known templates.</span>';
    }
    else {
      //no template specified
      $template = $indicia_templates[$name];
    }
    return self::apply_replacements_to_template($template, $options);
  }

  /**
   * Returns a string escaped for use in jQuery selectors.
   *
   * @param string $name
   *   The string to be escaped.
   *
   * @return string
   *   Escaped name.
   */
  protected static function jq_esc($name) {
    // Not complete, only escapes :[], add other characters as needed.
    $from = [':', '[', ']'];
    $to = ['\\\\:', '\\\\[', '\\\\]'];
    return $name ? str_replace($from, $to, $name) : $name;
  }

  /**
   * Method to format a control error message inside a templated span.
   * @param string $error The error message.
   * @param string $fieldname The name of the field which the error is being attached to.
   */
  private static function apply_error_template($error, $fieldname) {
    if (empty($error))
      return '';
    global $indicia_templates;
    if (empty($error)) return '';
    $template = str_replace('{class}', $indicia_templates['error_class'], $indicia_templates['validation_message']);
    $template = str_replace('{for}', $fieldname, $template);
    return str_replace('{error}', lang::get($error), $template);
  }

  /**
   * Requests data from the warehouse data or reporting services.
   *
   * Issue a request to get the population data required for a control either
   * from direct access to a data entity (table) or via a report query. The
   * response will be cached locally unless the caching option is set to false.
   *
   * @param array $options
   *   Options array with the following possibilities:
   *   * **table** - Singular table name used when loading from a database
   *     entity.
   *   * **report** - Path to the report file to use when loading data from a
   *     report, e.g. "library/occurrences/explore_list", excluding the .xml
   *     extension.
   *   * **extraParams** - Array of extra URL parameters to send with the web
   *     service request. Should include key value pairs for the field filters
   *     (for table data) or report parameters (for the report data) as well as
   *     the read authorisation tokens. Can also contain a parameter for:
   *     * orderby - for a non-default sort order, provide the field name to
   *       sort by. Can be comma separated to sort by several fields in
   *       descending order of precedence.
   *     * sortdir - specify ASC or DESC to define ascending or descending sort
   *       order respectively. Can be comma separated if several sort fields
   *       are specified in the orderby parameter.
   *     * limit - number of records to return.
   *     * offset - number of records to offset by into the dataset, useful
   *       when paginating through the records.
   *     * view - use to specify which database view to load for an entity
   *       (e.g. list, detail, gv or cache). Defaults to list.
   *   * **caching** - Set to one of the following to control the caching
   *     behaviour:
   *       * true - default. The response will be cached and the cached copy
   *         used for future calls. Default true.
   *       * store - although the response is not fetched from a cache, the
   *         response will be stored in the cache for possible later use.
   *         Replaces the legacy nocache parameter.
   *       * expire - expires the cache entry and does not return any data.
   *         Use helper_base::expireCacheEntry() rather than setting this
   *         option directly.
   *   * **cachePerUser** - if the data are not specific to the logged in user,
   *     then set to false so that a single cached response can be shared by
   *     multiple users.
   *   * **sharing** - Optional. Set to verification, reporting, peer_review,
   *     moderation, data_flow or editing to request data sharing with other
   *     websites for the task. Further information is given in the link below.
   * </li>
   * </ul>
   * @link https://indicia-docs.readthedocs.org/en/latest/developing/web-services/data-services-entity-list.html
   * @link https://indicia-docs.readthedocs.org/en/latest/administrating/warehouse/website-agreements.html
   */
  public static function get_population_data($options) {
    $useQueryParam = FALSE;
    if (isset($options['report']))
      $serviceCall = 'report/requestReport?report='.$options['report'].'.xml&reportSource=local&mode=json';
    elseif (isset($options['table'])) {
      $useQueryParam = $options['table'] !== 'taxa_search';
      $serviceCall = 'data/'.$options['table'].'?mode=json';
      if (isset($options['columns']))
        $serviceCall .= '&columns='.$options['columns'];
    }
    $request = "index.php/services/$serviceCall";
    if (array_key_exists('extraParams', $options)) {
      // make a copy of the extra params
      $params = array_merge($options['extraParams']);
      // process them to turn any array parameters into a query parameter for the service call
      $filterToEncode = array('where' => [[]]);
      $otherParams = [];
      // For data services calls to entities (i.e. not taxa_search), array
      // parameters need to be modified into a query parameter.
      if ($useQueryParam) {
        foreach ($params as $param=>$value) {
          if (is_array($value))
            $filterToEncode['in'] = array($param, $value);
          elseif ($param=='orderby' || $param=='sortdir' || $param=='auth_token' || $param=='nonce' || $param=='view')
            // these params are not filters, so can't go in the query
            $otherParams[$param] = $value;
          else
            $filterToEncode['where'][0][$param] = $value;
        }
      }
      // use advanced querying technique if we need to
      if (isset($filterToEncode['in']))
        $request .= '&query='.urlencode(json_encode($filterToEncode)).'&'.self::array_to_query_string($otherParams, TRUE);
      else
        $request .= '&'.self::array_to_query_string($options['extraParams'], TRUE);
    }
    if (isset($options['sharing']))
      $request .= '&sharing='.$options['sharing'];
    if (isset($options['attrs']))
      $request .= '&attrs='.$options['attrs'];
    if (!isset($options['caching']))
      $options['caching'] = TRUE; // default
    return self::getCachedServicesCall($request, $options);
  }

  /**
   * Expire the cache entry associated with a set of options.
   *
   * The options passed to this should be the same as the options used for a
   * get_population_data call. Removes the associated cache entry ensuring the
   * next call to get_population_data will be current. Use this function after
   * changing data that is loaded via a cached call, where you need to see the
   * update immediately.
   *
   * @param array $options
   *   Options as for get_population_data.
   */
  public static function expireCacheEntry(array $options) {
    $options['caching'] = 'expire';
    // With caching set to expire, get_population_data doesn't actually do the
    // service request.
    self::get_population_data($options);
  }

  /**
   * Utility function for access to the iform cache.
   *
   * @param array $cacheOpts
   *   Options array which defines the cache "key", i.e. the unique set of
   *   options being cached.
   *
   * @return mixed
   *   String read from the cache, or false if not read.
   */
  public static function cacheGet(array $cacheOpts) {
    $key = self::getCacheKey($cacheOpts);
    $r = self::getCachedResponse($key, $cacheOpts);
    return $r === FALSE ? $r : $r['output'];
  }

  /**
   * Utility function for external writes to the iform cache.
   *
   * @param array $cacheOpts Options array which defines the cache "key", i.e. the unique set of options being cached.
   * @param string $toCache String data to cache.
   * @param integer $cacheTimeout Timeout in seconds, if overriding the default cache timeout.
   */
  public static function cacheSet($cacheOpts, $toCache, $cacheTimeout = 0) {
    if (!$cacheTimeout) {
      $cacheTimeout = self::getCacheTimeOut([]);
    }
    $cacheKey = self::getCacheKey($cacheOpts);
    self::cacheResponse($cacheKey, ['output' => $toCache], $cacheOpts, $cacheTimeout);
  }

  /**
   * Wrapped up handler for a cached call to the data or reporting services.
   *
   * @param string $request
   *   Request URL.
   * @param array $options
   *   Control options, which may include a caching option and/or cachePerUser
   *   option.
   *
   * @return mixed
   *   Service call response.
   *
   * @throws \Exception
   */
  protected static function getCachedServicesCall($request, $options) {
    $cacheLoaded = FALSE;
    // Allow use of the legacy nocache parameter.
    if (isset($options['nocache']) && $options['nocache'] === TRUE) {
      $options['caching'] = FALSE;
    }
    $useCache = !self::$nocache && !isset($_GET['nocache']) && !empty($options['caching']) && $options['caching'];
    if ($useCache) {
      // Get the URL params, so we know what the unique thing is we are caching.
      $parsedURL = parse_url(self::$base_url . $request);
      parse_str($parsedURL["query"], $cacheOpts);
      unset($cacheOpts['auth_token']);
      unset($cacheOpts['nonce']);
      $cacheOpts['serviceCallPath'] = $parsedURL['path'];
      if (isset($options['cachePerUser']) && !$options['cachePerUser']) {
        // Don't want to include any user ID int the cache key.
        unset($cacheOpts['user_id']);
        unset($cacheOpts['currentUser']);
      }
      $cacheTimeout = self::getCacheTimeOut($options);
      $cacheKey = self::getCacheKey($cacheOpts);
      if ($options['caching'] === 'expire') {
        if (self::$delegate_caching_to_hostsite && function_exists('hostsite_cache_expire_entry')) {
          hostsite_cache_expire_entry($cacheKey);
        }
        else {
          $cacheFolder = self::$cache_folder ? self::$cache_folder : self::relative_client_helper_path() . 'cache/';
          unlink($cacheFolder.$cacheKey);
        }
        return [];
      }
      if ($options['caching'] !== 'store' && !isset($_GET['refreshcache'])) {
        $response = self::getCachedResponse($cacheKey, $cacheOpts);
        if ($response !== FALSE) {
          $cacheLoaded = TRUE;
        }
      }
    }
    if (!isset($response) || $response === FALSE) {
      $postArgs = NULL;
      $parsedURL = parse_url(self::$base_url . $request);
      parse_str($parsedURL["query"], $postArgs);
      $url = explode('?', self::$base_url . $request);
      $newURL = [$url[0]];

      $getArgs = [];
      if (isset($postArgs['report'])) {
        // Using the reports rather than direct? If this is case report params
        // go into special params postarg.
        // There is a place in the data services report handling that uses a
        // $_GET on the report parameter, so separate that out from the
        // postargs.
        $getArgs[] = 'report=' . $postArgs['report'];
        unset($postArgs['report']);
        // move other REQUESTED fields into POST.
        $postArgs = ['params' => $postArgs];
        $fieldsToCopyUp = [
          'reportSource',
          'mode',
          'auth_token',
          'nonce',
          'persist_auth',
          'filename',
          'callback',
          'xsl',
          'wantRecords',
          'wantColumns',
          'wantCount',
          'wantParameters',
          'knownCount',];
        foreach ($fieldsToCopyUp as $field) {
          if (isset($postArgs['params'][$field])) {
            $postArgs[$field] = $postArgs['params'][$field];
            unset($postArgs['params'][$field]);
          }
        }
        if (isset($postArgs['params']['user_id'])) {
          // user_id is different as this is used in an explicit _REQUEST in the service_base but
          // also can be proper param to the report - so don't unset.
          $postArgs['user_id'] = $postArgs['params']['user_id'];
        }
        $postArgs['params'] = json_encode((object)$postArgs['params']);
      }

      if (count($getArgs) > 0) {
        $newURL[] = implode('&', $getArgs);
      }
      $newURL = implode('?', $newURL);
      $response = self::http_post($newURL, $postArgs);
    }
    $r = json_decode($response['output'], TRUE);
    if (!is_array($r)) {
      $response['request'] = $request;
      throw new Exception('Invalid response received from Indicia Warehouse. '.print_r($response, TRUE));
    }
    // Only cache valid responses and when not already cached
    if ($useCache && !isset($r['error']) && !$cacheLoaded) {
      self::cacheResponse($cacheKey, $response, $cacheOpts, $cacheTimeout);
    }
    self::purgeCache();
    self::purgeImages();
    return $r;
  }

  /**
   * A less-opinionated method for calling a service URL with caching.
   *
   * Doesn't do anything with the GET and POST data, just passes it through,
   * which isn't the case for getCachedServicesCall.
   *
   * @param string $url
   *   Service URL (without domain prefix).
   * @param array $get
   *   Query parameters to add to the URL - key/value pairs.
   * @param array $post
   *   Key value pairs to POST.
   * @param array $options
   *   Options, which can include caching settings -
   *   * caching - set to TRUE to enable caching.
   *   * cachePerUser - set to FALSE to make cache hit for all users.
   *   * cacheTimeout - timeout in seconds.
   *
   * @return array
   *   Service response data.
   */
  public static function getCachedGenericCall($url, array $get, array $post, array $options) {
    $cacheLoaded = FALSE;
    $useCache = !self::$nocache && !isset($_GET['nocache']) && !empty($options['caching']) && $options['caching'];
    if ($useCache) {
      $excludedParams = [
        'nonce',
        'auth_token',
      ];
      if (isset($options['cachePerUser']) && !$options['cachePerUser']) {
        $excludedParams[] = 'user_id';
        $excludedParams[] = 'currentUser';
      }
      $cacheOpts = array_diff_key(array_merge($get, $post), array_combine($excludedParams, $excludedParams));
      $cacheOpts['serviceCallPath'] = self::$base_url . $url;
      $cacheKey = self::getCacheKey($cacheOpts);
      if ($options['caching'] !== 'store' && !isset($_GET['refreshcache'])) {
        $response = self::getCachedResponse($cacheKey, $cacheOpts);
        if ($response !== FALSE) {
          $cacheLoaded = TRUE;
        }
      }
    }
    if (!isset($response) || $response === FALSE) {
      $requestUrlParts = [self::$base_url . $url];
      if (!empty($get)) {
        $requestUrlParts[] = http_build_query($get);
      }
      $response = self::http_post(implode('?', $requestUrlParts), $post);
    }
    // Only cache valid responses and when not already cached;
    if ($useCache && !empty($response['success']) && !$cacheLoaded) {
      $cacheTimeout = self::getCacheTimeOut($options);
      self::cacheResponse($cacheKey, $response, $cacheOpts, $cacheTimeout);
    }
    $r = json_decode($response['output'], TRUE);
    if (!is_array($r)) {
      $response['request'] = self::$base_url . $url;
      throw new Exception('Invalid response received from Indicia Warehouse. '. print_r($response, TRUE));
    }
    self::purgeCache();
    self::purgeImages();
    return $r;
  }

  /**
   * Fetch a validated timeout value from passed in options array.
   *
   * @param array $options
   *   Options array with the following possibilities:
   *   * **cachetimeout** - Optional. The length in seconds before the cache
   *     times out and is refetched.
   * @return int
   *   Timeout in number of seconds, else FALSE if data is not to be cached.
   */
  private static function getCacheTimeOut($options) {
    if (is_numeric(self::$cache_timeout) && self::$cache_timeout > 0) {
      $ret_value = self::$cache_timeout;
    }
    else {
      $ret_value = FALSE;
    }
    if (isset($options['cachetimeout'])) {
      if (is_numeric($options['cachetimeout']) && $options['cachetimeout'] > 0) {
        $ret_value = $options['cachetimeout'];
      }
      else {
        $ret_value = FALSE;
      }
    }
    return $ret_value;
  }

  /**
   * Protected function to generate a key to be used for a cache enttrye.
   *
   * @param array $options
   *   Options array : contents are used along with md5 to generate the
   *   filename.
   *
   * @return string
   *   Filename, else FALSE if data is not to be cached.
   */
  private static function getCacheKey(array $options) {
    return self::$website_id . '_' . md5(self::array_to_query_string($options));
  }

  /**
   * Protected function to return the cached data stored in the specified local file.
   *
   * @param string $key
   *   Cache key to be used.
   * @param integer $timeout
   *   Will be false if no caching to take place.
   * @param array $options
   *   Options array : contents used to confirm what this data is.
   * @param boolean $random
   *   Should a random element be introduced to prevent simultaneous expiry of multiple
   *   caches? Default true.
   *
   * @return bool|array
   *   Equivalent of call to http_post, else FALSE if data not read from the
   *   cache.
   */
  private static function getCachedResponse($key, $options) {

    if (self::$delegate_caching_to_hostsite && function_exists('hostsite_cache_get')) {
      return hostsite_cache_get($key, $options);
    }
    else {
      $cacheFolder = self::$cache_folder ? self::$cache_folder : self::relative_client_helper_path() . 'cache/';
      if (!is_dir($cacheFolder) || !is_writeable($cacheFolder)) {
        return FALSE;
      }
      $cacheFile = $cacheFolder . "iform_cache_$key";
      if (is_file($cacheFile)) {

        $handle = fopen($cacheFile, 'rb');
        if (!$handle) {
          return FALSE;
        }
        // Make double sure this cache entry was for the same request options.
        $tags = fgets($handle);
        if ($tags !== http_build_query($options)."\n") {
          return FALSE;
        }
        // Check not expired.
        $expiry = trim(fgets($handle));
        if ($expiry < time()) {
          return FALSE;
        }
        if (self::getProbabilisticEarlyExpiration($expiry)) {
          return FALSE;
        }
        return [
          'output' => fread($handle, filesize($cacheFile)),
        ];
      }
    }
    return FALSE;
  }

  /**
   * Decide if a cache entry should expire early.
   *
   * Check for probabilistic early expiration to avoid cache stampede, see
   * https://en.wikipedia.org/wiki/Cache_stampede#Probabilistic_early_expiration.
   *
   * @param int $expiry
   *   Unix timestamp the entry is due to expire.
   *
   * @return bool
   *   Return true if the entry should be expired early.
   */
  private static function getProbabilisticEarlyExpiration($expiry) {
    return time() - 10 * log(mt_rand() / mt_getrandmax()) >= $expiry;
  }

  /**
   * Protected function to create a cache entry.
   *
   * @param string $key
   *   Cache key to save into.
   * @param array $response
   *   Http_post return value.
   * @param array $options
   *   Options array : contents used to tag what this data is.
   */
  private static function cacheResponse($key, $response, array $options, $expiry) {
    if ($key && isset($response['output'])) {
      if (self::$delegate_caching_to_hostsite && function_exists('hostsite_cache_set')) {
        hostsite_cache_set($key, $response['output'], $options, $expiry);
      }
      else {
        $cacheFolder = self::$cache_folder ? self::$cache_folder : self::relative_client_helper_path() . 'cache/';
        if (!is_dir($cacheFolder) || !is_writeable($cacheFolder)) {
          return;
        }
        $cacheFile = $cacheFolder . "iform_cache_$key";
        // Need to finish building the file before making the old one
        // unavailable - so create a temp file and move across.
        $handle = fopen($cacheFile . getmypid(), 'wb');
        // Add a line for the request options, for double checking on get.
        fputs($handle, http_build_query($options)."\n");
        // Add a line for the expiry.
        fputs($handle, (time() + $expiry) . "\n");
        // Add the data.
        fwrite($handle, $response['output']);
        fclose($handle);
        rename($cacheFile . getmypid(), $cacheFile);
      }
    }
  }

  /**
   * Helper function to clear the Indicia cache files.
   */
  public static function clearCache() {
    if (self::$delegate_caching_to_hostsite && function_exists('hostsite_cache_clear')) {
      hostsite_cache_clear();
    }
    else {
      $cacheFolder = self::$cache_folder ? self::$cache_folder : self::relative_client_helper_path() . 'cache/';
      if (!$dh = @opendir($cacheFolder)) {
        return;
      }
      while (FALSE !== ($obj = readdir($dh))) {
        if ($obj != '.' && $obj != '..')
          @unlink($cacheFolder . '/' . $obj);
      }
      closedir($dh);
    }
  }

  /**
   * Internal function to ensure old cache files are purged periodically.
   */
  private static function purgeCache() {
    if (self::$delegate_caching_to_hostsite && function_exists('hostsite_cache_purge')) {
      hostsite_cache_purge();
    }
    else {
      // Don't do this every time and skip if running inside Drupal as we use hook_cron.
      if (rand(1, self::$cache_chance_purge) === 1 && !function_exists('iform_cron')) {
        $cacheFolder = self::$cache_folder ? self::$cache_folder : self::relative_client_helper_path() . 'cache/';
        self::purgeFiles($cacheFolder, self::$cache_timeout * 5, self::$cache_allowed_file_count);
      }
    }
  }

  /**
   * Internal function to ensure old image files are purged periodically.
   */
  private static function purgeImages() {
    // Don't do this every time and skip if running inside Drupal as we use hook_cron.
    if (rand(1, self::$cache_chance_purge) === 1 && !function_exists('iform_cron')) {
      self::purgeFiles(self::getInterimImageFolder(), self::$interim_image_expiry);
    }
  }

  /**
   * Performs a periodic purge of cached or interim image upload files.
   *
   * @param string $folder
   *   Path to the folder to purge cache files from.
   * @param integer $timeout
   *   Age of files in seconds before they will be considered for purging.
   * @param integer $allowedFileCount
   *   Number of most recent files to not bother purging from the cache.
   */
  public static function purgeFiles($folder, $timeout, $allowedFileCount = 0) {
    // First, get an array of files sorted by date.
    $files = [];
    $dir =  opendir($folder);
    // Skip certain file names.
    $exclude = ['.', '..', '.htaccess', 'web.config', '.gitignore'];
    if ($dir) {
      while ($filename = readdir($dir)) {
        if (in_array($filename, $exclude) || !is_file($folder . $filename)) {
          continue;
        }
        $lastModified = filemtime($folder . $filename);
        $files[] = array($folder . $filename, $lastModified);
      }
    }
    // Sort the file array by date, oldest first.
    usort($files, ['helper_base', 'DateCmp']);
    // Iterate files, ignoring the number of files we allow in the cache
    // without caring.
    for ($i = 0; $i < count($files) - $allowedFileCount; $i++) {
      // If we have reached a file that is not old enough to expire, don't go
      // any further.
      if ($files[$i][1] > (time() - $timeout)) {
        break;
      }
      // Clear out the old file.
      if (is_file($files[$i][0])) {
        unlink($files[$i][0]);
      }
    }
  }

  /**
   * A custom PHP sorting function which uses the 2nd element in the compared array to
   * sort by. The sorted array normally contains a list of files, with the first element
   * of each array entry being the file path and the second the file date stamp.
   * @param int $a Datestamp of the first file to compare.
   * @param int $b Datestamp of the second file to compare.
   */
  private static function DateCmp($a, $b)
  {
    if ($a[1]<$b[1])
      $r = -1;
    else if ($a[1]>$b[1])
      $r = 1;
    else $r=0;
    return $r;
  }

}

/**
 * For PHP 5.2, declare the get_called_class method which allows us to use subclasses of this form.
 */
if (!function_exists('get_called_class')) {
  function get_called_class() {
    $matches=[];
    $bt = debug_backtrace();
    $l = 0;
    do {
        $l++;
        if (isset($bt[$l]['class']) AND !empty($bt[$l]['class'])) {
            return $bt[$l]['class'];
        }
        $lines = file($bt[$l]['file']);
        $callerLine = $lines[$bt[$l]['line']-1];
        preg_match('/([a-zA-Z0-9\_]+)::'.$bt[$l]['function'].'/',
                   $callerLine,
                   $matches);
        if (!isset($matches[1])) $matches[1]=NULL; //for notices
        if ($matches[1] == 'self') {
               $line = $bt[$l]['line']-1;
               while ($line > 0 && strpos($lines[$line], 'class') === FALSE) {
                   $line--;
               }
               preg_match('/class[\s]+(.+?)[\s]+/si', $lines[$line], $matches);
       }
    }
    while ($matches[1] == 'parent'  && $matches[1]);
    return $matches[1];
  }
}

// If a helper_config class is specified, then copy over the settings.
if (class_exists('helper_config')) {
  if (isset(helper_config::$base_url)) {
    helper_base::$base_url = helper_config::$base_url;
  }
  if (isset(helper_config::$warehouse_proxy)) {
    helper_base::$warehouse_proxy = helper_config::$warehouse_proxy;
  }
  if (isset(helper_config::$geoserver_url)) {
    helper_base::$geoserver_url = helper_config::$geoserver_url;
  }
  if (isset(helper_config::$interim_image_folder)) {
    helper_base::$interim_image_folder = helper_config::$interim_image_folder;
  }
  if (isset(helper_config::$google_api_key)) {
    helper_base::$google_api_key = helper_config::$google_api_key;
  }
  if (isset(helper_config::$google_maps_api_key)) {
    helper_base::$google_maps_api_key = helper_config::$google_maps_api_key;
  }
  if (isset(helper_config::$bing_api_key)) {
    helper_base::$bing_api_key = helper_config::$bing_api_key;
  }
  if (isset(helper_config::$os_api_key)) {
    helper_base::$os_api_key = helper_config::$os_api_key;
  }
  if (isset(helper_config::$delegate_translation_to_hostsite)) {
    helper_base::$delegate_translation_to_hostsite = helper_config::$delegate_translation_to_hostsite;
  }
  if (isset(helper_config::$delegate_caching_to_hostsite)) {
    helper_base::$delegate_caching_to_hostsite = helper_config::$delegate_caching_to_hostsite;
  }
  if (isset(helper_config::$upload_max_filesize)) {
    helper_base::$upload_max_filesize = helper_config::$upload_max_filesize;
  }
}
