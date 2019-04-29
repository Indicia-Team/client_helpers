<?php

/**
 * @file
 * A helper class for parameters forms.
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
 * @link https://github.com/indicia-team/client_helpers
 */

/**
 * Link in other required php files.
 */
require_once 'lang.php';
require_once 'helper_base.php';

/**
 * A class with helper methods for handling prebuilt forms and generating complete parameters entry forms from
 * simple input arrays.
 *
 * @package Client
 */
class form_helper extends helper_base {

  /**
   * Outputs a pair of linked selects, for picking a prebuilt form from the library. The first select is for picking a form
   * category and the second select is populated by AJAX for picking the actual form.
   * @param array $readAuth Read authorisation tokens
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>form</b><br/>
   * Optional. The name of the form to select as a default value.</li>
   * <li><b>includeOutputDiv</b><br/>
   * Set to true to generate a div after the controls which will receive the form parameter
   * controls when a form is selected.</li>
   * <li><b>allowConnectionOverride</b><br/>
   * Defaults to false. In this state, the website ID and password controls are not displayed
   * when both the values are already specified, though hidden inputs are put into the form.
   * When set to true, the website ID and password input controls are always included in the form output.
   * </li>
   * </ul>
   * @return string HTML for the form picker.
   * @throws \Exception
   */
  public static function prebuilt_form_picker($readAuth, $options) {
    require_once 'data_entry_helper.php';
    form_helper::add_resource('jquery_ui');
    $path = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . self::relative_client_helper_path();
    $r = '';
    if (!$dir = opendir($path . 'prebuilt_forms/')) {
      throw new Exception('Cannot open path to prebuilt form library.');
    }
    $forms = array();
    $groupForms = array();
    $recommendedForms = array();
    while (FALSE !== ($file = readdir($dir))) {
      $parts = explode('.', $file);
      if ($file != "." && $file != ".." && strtolower($parts[count($parts) - 1]) === 'php') {
        $file_tokens = explode('.', $file);
        try {
          require_once $path . 'prebuilt_forms/' . $file;
        }
        catch (Throwable $e) {
          // Add a stub to tell the user this form is broken. Recommended so it
          // is not hidden away.
          $forms['Broken forms'][$file_tokens[0]] = [
            'title' => "$file_tokens[0] has a syntax error",
            'recommended' => TRUE,
          ];
          if (!isset($recommendedForms['Broken forms'])) {
            $recommendedForms['Broken forms'] = [];
          }
          $recommendedForms['Broken forms'][] = $file_tokens[0];
          continue;
        }
        ob_start();
        if (is_callable(array('iform_' . $file_tokens[0], 'get_' . $file_tokens[0] . '_definition'))) {
          $definition = call_user_func(array('iform_' . $file_tokens[0], 'get_' . $file_tokens[0] . '_definition'));
          $definition['title'] = lang::get($definition['title']);
          $forms[$definition['category']][$file_tokens[0]] = $definition;
          if (isset($options['form']) && $file_tokens[0] === $options['form']) {
            $defaultCategory = $definition['category'];
          }
          if (!empty($definition['supportsGroups'])) {
            if (!isset($groupForms[$definition['category']])) {
              $groupForms[$definition['category']] = array();
            }
            $groupForms[$definition['category']][] = $file_tokens[0];
          }
          if (!empty($definition['recommended'])) {
            if (!isset($recommendedForms[$definition['category']])) {
              $recommendedForms[$definition['category']] = array();
            }
            $recommendedForms[$definition['category']][] = $file_tokens[0];
          }
        }
        elseif (is_callable(array('iform_' . $file_tokens[0], 'get_title'))) {
          $title = call_user_func(array('iform_' . $file_tokens[0], 'get_title'));
          $forms['Miscellaneous'][$file_tokens[0]] = array('title' => $title);
          if (isset($options['form']) && $file_tokens[0] === $options['form']) {
            $defaultCategory = 'Miscellaneous';
          }
        }
        ob_end_clean();
      }
    }
    if (isset($defaultCategory)) {
      $availableForms = array();
      foreach ($forms[$defaultCategory] as $form => $def) {
        $availableForms[$form] = $def['title'];
      }
    }
    else {
      $defaultCategory = '';
      $availableForms = array('' => '<Please select a category first>');
    }
    closedir($dir);
    // Makes an assoc array from the categories.
    $categories = array_merge(
      array('' => '<Please select>'),
      array_combine(array_keys($forms), array_keys($forms))
    );
    // Translate categories.
    foreach ($categories as $key => &$value) {
      $value = lang::get($value);
    }
    asort($categories);
    if (count($groupForms) > 0) {
      $r .= self::link_to_group_fields($readAuth, $options);
    }
    if (isset($options['allowConnectionOverride']) && !$options['allowConnectionOverride']
        && !empty($options['website_id']) && !empty($options['password'])) {
      $r .= '<input type="hidden" id="website_id" name="website_id" value="' . $options['website_id'] . '"/>';
      $r .= '<input type="hidden" id="password" name="password" value="' . $options['password'] . '"/>';
    }
    else {
      $r .= data_entry_helper::text_input(array(
        'label' => lang::get('Warehouse URL'),
        'fieldname' => 'base_url',
        'helpText' => lang::get('Enter the URL of the warehouse you are using if you want to override the site default. ' .
            'Include the trailing slash, e.g. http://myexamplewarehouse.com/. This option can be used to provide pages ' .
            'that use an alternative reporting warehouse. It should not be used for recording as the user\'s warehouse ' .
            'user ID will differ between the 2 warehouses, therefore any data posted to this warehouse will be associated ' .
            'with the admin user account.'),
        'default' => isset($options['base_url']) ? $options['base_url'] : '',
        'class' => 'control-width-5'
      ));
      $r .= data_entry_helper::text_input(array(
        'label' => lang::get('Website ID'),
        'fieldname' => 'website_id',
        'helpText' => lang::get('Enter the ID of the website record on the Warehouse you are using.'),
        'default' => isset($options['website_id']) ? $options['website_id'] : ''
      ));
      $r .= data_entry_helper::text_input(array(
        'label' => lang::get('Password'),
        'fieldname' => 'password',
        'helpText' => lang::get('Enter the password for the website record on the Warehouse you are using.'),
        'default' => isset($options['password']) ? $options['password'] : ''
      ));
    }
    $r .= data_entry_helper::checkbox(array(
      'label' => lang::get('Only show recommended page types'),
      'fieldname' => 'recommended',
      'helpText' => lang::get('Tick this box to limit the available page types to the recommended ones provided ' .
          'within the Indicia core. Unticking the box will allow you to select additional page types, such as ' .
          'those for specific survey methodologies or previous versions of the code.'),
      'default' => TRUE,
      'labelClass' => 'auto'
    ));
    $r .= data_entry_helper::select(array(
      'id' => 'form-category-picker',
      'fieldname' => 'iform-cetegory',
      'label' => lang::get('Select page category'),
      'helpText' => lang::get('Select the category for the type of page you are building'),
      'lookupValues' => $categories,
      'default' => $defaultCategory
    ));

    $r .= data_entry_helper::select(array(
      'id' => 'form-picker',
      'fieldname' => 'iform',
      'label' => lang::get('Page type'),
      'helpText' => lang::get('Select the page type you want to use.'),
      'lookupValues' => $availableForms,
      'default' => isset($options['form']) ? $options['form'] : ''
    ));

    // Div for the form instructions.
    $details = '';
    // Default - we are only going to show recommended page types in the category and page type drop downs.
    $showRecommendedPageTypes = TRUE;
    if (isset($options['form'])) {
      if (isset($forms[$defaultCategory][$options['form']]['description'])) {
        $details .= '<p>' . $forms[$defaultCategory][$options['form']]['description'] . '</p>';
      }
      if (isset($forms[$defaultCategory][$options['form']]['helpLink'])) {
        $details .= '<p><a href="' . $forms[$defaultCategory][$options['form']]['helpLink'] . '">Find out more...</a></p>';
      }
      if ($details !== '') {
        $details = "<div class=\"ui-state-highlight ui-corner-all page-notice\">$details</div>";
      }
      // If selecting an existing non-core form, then we need to override the default and show all categories and pages.
      $showRecommendedPageTypes = !empty($forms[$defaultCategory][$options['form']]['recommended']);
    }
    $r .= "<div id=\"form-def\">$details</div>\n";
    $r .= '<input type="button" value="' . lang::get('Load Settings Form') . '" id="load-params" disabled="disabled" />';
    if (isset($options['includeOutputDivs']) && $options['includeOutputDivs']) {
      $r .= '<div id="form-params"></div>';
    }
    self::add_form_picker_js($forms, $groupForms, $recommendedForms, $showRecommendedPageTypes);
    return $r;
  }

  /**
   * If there are any recording groups, then add controls to the config to allow the forms to be linked to the recording group
   * functionality.
   * @param $readAuth Read authorisation tokens
   * @param $options Control options array. Set $options['available_for_groups'] to true to set the
   * available for groups checkbox default and $options['limit_to_group_id'] to set the default
   * group to limit this form to if any.
   * @return string
   */
  private static function link_to_group_fields($readAuth, $options) {
    $r = '';
    if (hostsite_has_group_functionality()) {
      $r .= data_entry_helper::checkbox(array(
        'label' => lang::get('This page is going to be used by recording groups'),
        'fieldname' => 'available_for_groups',
        'helpText' => lang::get('Tick this box if this page will be is made available for use by ' .
          'recording groups for their own record collection or reporting.'),
        'default' => isset($options['available_for_groups']) ? $options['available_for_groups'] : FALSE,
        'labelClass' => 'auto',
      ));
      $r .= data_entry_helper::select(array(
        'label' => lang::get('Which recording group?'),
        'fieldname' => 'limit_to_group_id',
        'helpText' => lang::get('If this form is being built specifically for the use of a ' .
            'single recording group, then choose that group here.'),
        'blankText' => '<' . lang::get('Any group') . '>',
        'table' => 'group',
        'valueField' => 'id',
        'captionField' => 'title',
        'extraParams' => $readAuth + array('orderby' => 'title'),
        'default' => isset($options['limit_to_group_id']) ? $options['limit_to_group_id'] : FALSE,
        'caching' => FALSE
      ));
    }
    return $r;
  }

  /**
   * Adds the JavaScript required to drive the prebuilt form picker.
   * @param array $forms List of prebuilt forms and their associated settings required
   * by the picker.
   */
  private static function add_form_picker_js($forms, $groupForms, $coreForms, $showRecommendedPageTypes) {
    $jsParams = [
      'baseUrl' => self::$base_url,
      'forms' => json_encode($forms),
      'formParamsAjaxPath' => self::getRootFolder(FALSE) . self::client_helper_path() . 'prebuilt_forms_ajax.php',
      'groupForms' => json_encode($groupForms),
      'coreForms' => json_encode($coreForms),
      'showRecommended' => $showRecommendedPageTypes ? 'true' : 'false',
      'langEnterLoginAndForm' => lang::get('Please specify a website ID, password and select a form before proceeding.'),
      'langFindOutMore' => lang::get('Find out more...'),
      'langPleaseSelect' => lang::get('&lt;Please select&gt;'),
      'langPleaseSelectCategory' => lang::get('&lt;Please select a category first&gt;'),
    ];
    self::$javascript .= <<<JS
var prebuilt_forms = $jsParams[forms];
var prebuilt_group_forms = $jsParams[groupForms];
var prebuilt_recommended_forms = $jsParams[coreForms];
var showRecommended = $jsParams[showRecommended];

function setCategoryAndPageVisibility() {
  $.each($('#form-category-picker option'), function() {
    // Hide pages that are not recommended or not group pages, unless specifically allowed. Ignore the <please select> option.
    if ($(this).attr('value') !== '') {
      if (($('#available_for_groups:checked').length && typeof prebuilt_group_forms[$(this).attr('value')] === 'undefined')
          || ($('#recommended:checked').length && typeof prebuilt_recommended_forms[$(this).attr('value')] === 'undefined')) {
        $(this).hide();
        $(this).attr('disabled', 'disabled');
        if ($(this).attr('selected')) {
          $('#form-category-picker option[value=""]').attr('selected', TRUE);
        }
      } else {
        $(this).show();
        $(this).removeAttr('disabled');
      }
    }
  });
  $('#form-category-picker').change();
}

function changeGroupEnabledStatus() {
  if ($('#available_for_groups:checked').length) {
    $('#ctrl-wrap-limit_to_group_id').slideDown();
    // If the config form has group-only related controls, show them.
    $('.group-field').closest('.ctrl-wrap').show();
  } else {
    $('#ctrl-wrap-limit_to_group_id').slideUp();
    // If the config form has group-only related controls, hidez them.
    $('.group-field').closest('.ctrl-wrap').hide();
  }
  setCategoryAndPageVisibility();
  $('#form-category-picker').change();
}

$('#available_for_groups').change(changeGroupEnabledStatus);
$('#recommended').change(setCategoryAndPageVisibility);

changeGroupEnabledStatus();

/**
 * Disallow base map layer selection if controlled by iform_user_ui_options.
 */
function hideMapsIfOverridden() {
  // Hide the basemap selection options if the dynamic basemap layers override
  // is used and this is *not* a verification form.
  iformCategory = $('#form-category-picker option:selected').val();
  if (iformCategory !== 'Verification' && indiciaData.basemapLayersOverride) {
    $('#ctrl-wrap-preset_layers').hide();
    $('#ctrl-wrap-preset_layers').after(
      '<div class="alert alert-info">The base map layers are locked by the iform_user_ui_options module.</div>'
    );
  }
}

/**
 * Handling for dynamic switching layers which are treated as one.
 */
function combineDynamicMapLayers() {
  // Tie the selection of the paired dynamic layer checkboxes together and make
  // one of them invisible.
  var dyn1 = $("input[value='dynamicOSleisureGoogleSat']");
  var dyn2 = $("input[value='dynamicOSleisureGoogleSatZoomed']");
  dyn2.parent().hide(); //Parent list item
  dyn1.change(function dyn1Change() {
    dyn2.attr('checked', this.checked);
  });
  dyn2.change(function dyn2Change() {
    dyn1.attr('checked', this.checked);
  });
}

combineDynamicMapLayers();
hideMapsIfOverridden();

$('#form-category-picker').change(function(e) {
  var opts = '<option value="">$jsParams[langPleaseSelect]</option>';
  var current = $('#form-picker').val();
  var isGroupPageType;
  var isRecommendedPageType;
  if (typeof prebuilt_forms[e.currentTarget.value]==='undefined') {
    $('#form-picker').html('<option value="">$jsParams[langPleaseSelectCategory]</option>');
  } else {
    $.each(prebuilt_forms[e.currentTarget.value], function(form, def) {
      isGroupPageType = typeof prebuilt_group_forms[e.currentTarget.value]!=='undefined'
          && $.inArray(form, prebuilt_group_forms[e.currentTarget.value])>-1;
      isRecommendedPageType = typeof prebuilt_recommended_forms[e.currentTarget.value]!=='undefined'
          && $.inArray(form, prebuilt_recommended_forms[e.currentTarget.value])>-1;
      if ((!$('#available_for_groups:checked').length || isGroupPageType)
          && (!$('#recommended:checked').length || isRecommendedPageType)) {
        opts += '<option value="'+form+'">'+def.title+'</option>';
      }
    });
    $('#form-picker').html(opts);
  }
  $('#form-picker').val(current);
  if ($('#form-picker').val() !== current) {
    $('#form-picker').change();
  }
});

$('#form-picker').change(function(e) {
  var details='', def;
  $('#load-params').attr('disabled', false);
  $('#form-params').html('');
  if ($(e.target).val()!=='') {
    def = prebuilt_forms[$('#form-category-picker').val()][$('#form-picker').val()];
    if (def) {
      if (def.description) {
        details += '<p>'+def.description+'</p>';
      }
      if (typeof def.helpLink !== 'undefined') {
        details += '<p><a href="' + def.helpLink + '" target="_blank">$jsParams[langFindOutMore]</a></p>';
      }
      if (details!=='') {
        details = '<div class="ui-state-highlight ui-corner-all page-notice">' + details + '</div>';
      }
    }
  }
  $('#form-def').hide().html(details).fadeIn();
});

$('#load-params').click(function() {
  if ($('#form-picker').val()==='' || $('#website_id').val()==='' || $('#form-picker').val()==='') {
    alert('$jsParams[langEnterLoginAndForm]');
  } else {
    if (typeof prebuilt_forms[$('#form-category-picker').val()][$('#form-picker').val()] !== 'undefined') {
      // now use an Ajax request to get the form params
      $.post(
        '$jsParams[formParamsAjaxPath]',
        {form: $('#form-picker').val(),
            website_id: $('#website_id').val(),
            password: $('#password').val(),
            base_url: '$jsParams[baseUrl]',
            generator: $('meta').filter(function() {
              return typeof $(this).attr('name')!=='undefined' && $(this).attr('name').toLowerCase() === 'generator';
            }).attr('content')
        },
        function(data) {
          $('#form-params').hide().html(data).fadeIn();
          Drupal.attachBehaviors();
          hideMapsIfOverridden();
          combineDynamicMapLayers();
        }
      );
    } else {
      $('#form-params').hide();
    }
  }
});

JS;
  }

  /**
   * Generates the parameters form required for configuring a prebuilt form.
   * Fieldsets are given classes which define that they are collapsible and normally initially
   * collapsed, though the css for handling this must be defined elsewhere. For Drupal usage this
   * css is normally handled by default in the template.
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>form</b>
   * Name of the form file without the .php extension, e.g. mnhnl_dynamic_1.</li>
   * <li><b>currentSettings</b>
   * Associative array of default values to load into the form controls.</li>
   * <li><b>expandFirst</b>
   * Optional. If set to true, then the first fieldset on the form is initially expanded.</li>
   * <li><b>siteSpecific</b>
   * Optional. Defaults to false. If true then only parameters marked as specific to a site
   * are loaded. Used to provide a reduced version of the params form after migrating a
   * form between sites (e.g. when installing a Drupal feature).</li>
   * <li><b>generator</b>
   * Optional. A string which, if it contains 'Drupal 7' is used to output
   * html specific to that CMS. </li>
   * </ul>
   */
  public static function prebuilt_form_params_form($options) {
    if (function_exists('hostsite_add_library')) {
      hostsite_add_library('collapse');
    }
    require_once 'data_entry_helper.php';
    // Temporarily disable caching because performance is not as important as reflecting
    // the latest available parameters, surveys etc. in the drop downs.
    $oldnocache = self::$nocache;
    if (!isset($options['siteSpecific'])) {
      $options['siteSpecific'] = FALSE;
    }
    self::$nocache = TRUE;
    $formparams = self::get_form_parameters($options['form']);
    $fieldsets = array();
    $r = '';
    foreach ($formparams as $control) {
      // Skip hidden controls or non-site specific controls when displaying the reduced site specific
      // version of the form
      if ((isset($control['visible']) && !$control['visible']) ||
          ($options['siteSpecific'] && !(isset($control['siteSpecific']) && $control['siteSpecific']))) {
        continue;
      }
      $fieldset = isset($control['group']) ? $control['group'] : 'Other IForm Parameters';
      // Apply default options to the control.
      $ctrlOptions = array_merge(array(
        'id' => $control['fieldname'],
        'sep' => '<br/>',
        'class' => '',
        'blankText' => '<' . lang::get('please select') . '>',
        'extraParams' => array(),
        'readAuth' => $options['readAuth']
      ), $control);
      $type = self::mapType($control);

      // Current form settings will overwrite the default.
      if (isset($options['currentSettings']) && isset($options['currentSettings'][$control['fieldname']])) {
        $ctrlOptions['default'] = $options['currentSettings'][$control['fieldname']];
      }

      $ctrlOptions['extraParams'] = array_merge($ctrlOptions['extraParams'], $options['readAuth']);
      // Standardise the control width unless specified already in the control
      // options.
      if (strpos($ctrlOptions['class'], 'control-width') == FALSE && $type != 'checkbox'
        && $type !== 'report_helper::report_picker') {
        $ctrlOptions['class'] .= ' control-width-6';
      }
      if (!isset($fieldsets[$fieldset])) {
        $fieldsets[$fieldset] = '';
      }
      // Form controls can specify the report helper class.
      if (substr($type, 0, 15) === 'report_helper::') {
        $type = substr($type, 15);
        require_once 'report_helper.php';
        $fieldsets[$fieldset] .= report_helper::$type($ctrlOptions);
      }
      else {
        $fieldsets[$fieldset] .= data_entry_helper::$type($ctrlOptions);
      }

    }
    $class = (isset($options['expandFirst']) && $options['expandFirst']) ? 'collapsible' : 'collapsible collapsed';
    foreach ($fieldsets as $fieldset => $content) {
      $r .= "<fieldset class=\"$class\">\n";
      // In Drupal 7 the fieldset output includes an extra span
      // When called from within Drupal, DRUPAL_CORE_COMPATIBILITY can determine
      // version. When called by Ajax version has to be sent in $options.
      if ((defined('DRUPAL_CORE_COMPATIBILITY') && DRUPAL_CORE_COMPATIBILITY==='7.x') ||
          (isset($options['generator']) && stristr($options['generator'], 'Drupal 7'))) {
        $legendContent = "<span class=\"fieldset-legend\">$fieldset</span>";
      }
      else {
        $legendContent = $fieldset;
      }
      $r .= "<legend>$legendContent</legend>\n";
      $r .= "<div class=\"fieldset-wrapper\">\n";
      $r .= $fieldsets[$fieldset];
      $r .= "</div>\n";
      $r .= "\n</fieldset>\n";
      // Any subsequent fieldset should be collapsed.
      $class = 'collapsible collapsed';
    }
    self::$nocache = $oldnocache;
    return $r;
  }

  /**
   * Version 0.6 of Indicia converted from using a specific format for defining
   * prebuilt form parameters forms to arrays which map directly onto the options
   * for controls defined in the data entry helper. This makes the forms much more
   * powerful with built in AJAX support etc. However, old forms need to have the
   * control options mapped to the newer option names.
   * @param array $controlList List of controls as defined by the prebuilt form.
   * @return array List of modified controls.
   */
  private static function map_control_options($controlList) {
    $mappings = array(
        'name' => 'fieldname',
        'caption' => 'label',
        'options' => 'lookupValues',
        'description' => 'helpText'
    );
    foreach ($controlList as &$options) {
      foreach ($options as $option => $value) {
        if (isset($mappings[$option])) {
          $options[$mappings[$option]] = $value;
          unset($options[$option]);
        }
      }
      if (!isset($options['required']) || $options['required']===true) {
        if (!isset($options['class'])) $options['class']='';
        $options['class'] .= ' required';
        $options['suffixTemplate'] = 'requiredsuffix';
      }
    }
    return $controlList;
  }

  /**
   * Maps control types to actual control names.
   *
   * Maps control types in simple form definition arrays (e.g. parameter forms
   * for prebuilt forms or reports) to their constituent controls.
   *
   * @param array $control
   *   Control definition array, which includes a type entry defining the
   *   control type.
   *
   * @return string
   *   Data_entry_helper control name.
   */
  private static function mapType($control) {
    $mapping = array(
      // In case there is any Drupal hangover code.
      'textfield' => 'text_input',
      'string' => 'text_input',
      'int' => 'text_input',
      'float' => 'text_input',
      'smpAttr' => 'text_input',
      'occAttr' => 'text_input',
      'locAttr' => 'text_input',
      'taxAttr' => 'text_input',
      'psnAttr' => 'text_input',
      'termlist' => 'text_input',
      'boolean' => 'checkbox',
      'list' => 'checkbox_group',
    );
    return array_key_exists($control['type'], $mapping) ? $mapping[$control['type']] : $control['type'];
  }

  /**
   * Retrieve the parameters for an iform. This is defined by each iform individually.
   * @param object $form The name of the form we are retrieving the parameters for.
   * @return array list of parameter definitions.
   */
  public static function get_form_parameters($form) {
    $path = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . self::relative_client_helper_path();
    require_once $path . "prebuilt_forms/$form.php";
    // First some parameters that are always required to configure the website.
    $params = array(
      array(
        'fieldname' => 'view_access_control',
        'label' => 'View access control',
        'helpText' => 'If ticked, then a Drupal permission is created for this form to allow you to specify which '.
            'roles are able to view the form.',
        'type' => 'checkbox',
        'required' => FALSE,
      ),
      array(
        'fieldname' => 'permission_name',
        'label' => 'Permission name for view access control',
        'helpText' => 'If you want to use a default permission name when using view access control, leave this blank. Otherwise, specify the name of '.
            'a permission to define for accessing this form. One use of this is to create a single permission which is shared between several forms '.
            '(e.g. a permission could be called "Online Recording". Another situation where this should be used is when creating a feature for Instant Indicia '.
            'so the permission name can be consistent across sites which share this form.',
        'type' => 'text_input',
        'required' => FALSE,
      )
    );
    // now get the specific parameters from the form
    if (!is_callable(array('iform_' . $form, 'get_parameters'))) {
      throw new Exception("Form $form does not implement the get_parameters method.");
    }
    $formParams = self::map_control_options(call_user_func(array('iform_' . $form, 'get_parameters')));
    $params = array_merge($params, $formParams);
    // Add in a standard parameter for specifying a redirection.
    $params[] = array(
      'fieldname' => 'redirect_on_success',
      'label' => 'Redirect to page after successful data entry',
      'helpText' => 'The url of the page that will be navigated to after a successful data entry. ' .
          'leave blank to just display a success message on the same page so further records can be entered. if the site is internationalised, '.
          'make sure that the page you want to go to has a url specified that is the same for all language versions. also ensure your site uses '.
          'a path prefix for the language negotiation (administer > site configuration > languages > configure). then, specify the url that you attached to the node '.
          'so that the language prefix is not included.',
      'type' => 'text_input',
      'required' => FALSE,
    );
    $params[] = array(
      'fieldname' => 'message_after_save',
      'label' => 'Display notification after save',
      'helpText' => 'After saving an input form, should a message be added to the page stating that the record has been saved? This should be left '.
          'unchecked if the page is redirected to a page that has information about the record being saved inherent in the page content. Otherwise ticking '.
          'this box can help to make it clear that a record was saved.',
      'type' => 'checkbox',
      'required' => FALSE,
      'default' => TRUE,
    );
    $params[] = array(
      'fieldname' => 'additional_css',
      'label' => 'Additional CSS files to include',
      'helpText' => 'Additional CSS files to include on the page. You can use the following replacements to simplify the setting '.
          'of the correct paths. {mediacss} is replaced by the media/css folder in the module. {theme} is replaced by the '.
          'current theme folder. {prebuiltformcss} is replaced by the prebuilt_forms/css folder. Specify one CSS file per '.
          'line.',
      'type' => 'textarea',
      'required' => FALSE,
    );
    $params[] = array(
      'fieldname' => 'additional_templates',
      'label' => 'Additional template files to include',
      'helpText' => 'Additional templates files to include on the page. You can use the following replacements to simplify the setting '.
          'of the correct paths. {prebuiltformtemplates} is replaced by the prebuilt_forms/templates folder. Specify one template file per '.
          'line. The structure of template files is described <a target="_blank" href="http://indicia-docs.readthedocs.org/en/latest/site-building/' .
          'iform/customising-page-functionality.html#overridding-the-html-templates-used-to-output-the-input-controls">in the documentation</a>.',
      'type' => 'textarea',
      'required' => FALSE,
    );
    // allow the user ui options module to add it's own param. This could probably be refactored as a proper Drupal hook...
    if (function_exists('iform_user_ui_options_additional_params')) {
      $params = array_merge($params, iform_user_ui_options_additional_params());
    }
    return $params;
  }

}
