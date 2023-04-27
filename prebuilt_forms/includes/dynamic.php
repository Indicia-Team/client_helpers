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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/indicia-team/client_helpers
 */

/**
 * Parent class for dynamic prebuilt Indicia data entry forms.
 * NB has Drupal specific code.
 */

require_once 'map.php';
require_once 'user.php';
require_once 'language_utils.php';
require_once 'form_generation.php';

define('HIGH_VOLUME_CACHE_TIMEOUT', 30);
define('HIGH_VOLUME_CONTROL_CACHE_TIMEOUT', 5);

class iform_dynamic {
  // Hold the single species name to be shown on the page to the user.
  // Inherited by dynamic_sample_occurrence.
  protected static $singleSpeciesName;

  // The node id upon which this form appears.
  protected static $nid;

  // The class called by iform.module which may be a subclass of iform_location_dynamic.
  protected static $called_class;

  // The authorisation tokens for accessing the warehouse.
  protected static $auth = [];

  // The form mode. Stored in case other inheriting forms need it.
  protected static $mode;

  // Values that $mode can take.
  // MODE_GRID, default mode when no grid set to false - display grid of existing data.
  // MODE_NEW, default mode when no_grid set to true - display an empty form for adding a new sample.
  // MODE_EXISTING, display existing sample for editing.
  // MODE_EXISTING_RO, display existing sample for reading only.
  // MODE_CLONE, display form for adding a new sample containing values of an existing sample.
  const MODE_GRID = 0;
  const MODE_NEW = 1;
  const MODE_EXISTING = 2;
  const MODE_EXISTING_RO = 3;
  const MODE_CLONE = 4;

  /**
   * Controls whether a form element wrapped around output.
   *
   * @return bool
   */
  protected static function isDataEntryForm() {
    return TRUE;
  }

  /**
   * Function get_parameters()
   */
  public static function get_parameters() {
    $retVal = array_merge(
      iform_map_get_map_parameters(),
      iform_map_get_georef_parameters(),
      [
        [
          'name' => 'interface',
          'caption' => 'Interface Style Option',
          'description' => 'Choose the style of user interface, either dividing
          the form up onto separate tabs, wizard pages or having all controls on
          a single page.',
          'type' => 'select',
          'options' => [
            'tabs' => 'Tabs',
            'wizard' => 'Wizard',
            'one_page' => 'All One Page',
          ],
          'group' => 'User Interface',
          'default' => 'tabs',
        ],
        [
          'name' => 'tabProgress',
          'caption' => 'Show Progress through Wizard/Tabs',
          'description' => 'For Wizard or Tabs interfaces, check this option to
          show a progress summary above the controls.',
          'type' => 'boolean',
          'default' => FALSE,
          'required' => FALSE,
          'group' => 'User Interface',
        ],
        [
          'name' => 'force_next_previous',
          'caption' => 'Next/previous buttons shown in tab mode?',
          'description' => 'Should the wizard style Next & Previous buttons be
          shown even when in tab mode? This option does not apply when the
          option "Submit button below all pages" is set.',
          'type' => 'boolean',
          'default' => FALSE,
          'required' => FALSE,
          'group' => 'User Interface',
        ],
        [
          'name' => 'clientSideValidation',
          'caption' => 'Client Side Validation',
          'description' => 'Enable client side validation of controls using
          JavaScript.',
          'type' => 'boolean',
          'default' => TRUE,
          'required' => FALSE,
          'group' => 'User Interface',
        ],
        [
          'name' => 'attribute_termlist_language_filter',
          'caption' => 'Internationalise lookups mode',
          'description' => 'In lookup custom attribute controls, how should term translation be handled?',
          'type' => 'select',
          'lookupValues' => [
            '0' => 'Show all terms in the termlist',
            '1' => 'Show only terms in selected language',
            'clientI18n' => 'Show only preferred terms but enable localisation
            (e.g. Drupal or Indicia translation)',
          ],
          'default' => '0',
          'group' => 'User Interface',
        ],
        [
          'name' => 'no_grid',
          'caption' => 'Skip initial grid of data',
          'description' => 'If checked, then when initially loading the form the
          data entry form is immediately displayed, as opposed to the default of
          displaying a grid of the user\'s data which they can add to. By
          ticking this box, it is possible to use this form for data entry by
          anonymous users though they cannot then list the data they have
          entered.',
          'type' => 'boolean',
          'default' => TRUE,
          'required' => FALSE,
          'group' => 'User Interface',
        ],
        [
          'name' => 'grid_num_rows',
          'caption' => 'Number of rows displayed in grid',
          'description' => 'Number of rows display on each page of the grid.',
          'type' => 'int',
          'default' => 10,
          'group' => 'User Interface',
        ],
        [
          'name' => 'save_button_below_all_pages',
          'caption' => 'Submit button below all pages?',
          'description' => 'Should the submit button be present below all the
          pages (checked), or should it be only on the last page (unchecked)?
          Only applies to the Tabs interface style.',
          'type' => 'boolean',
          'default' => FALSE,
          'required' => FALSE,
          'group' => 'User Interface',
        ],
        [
          'name' => 'spatial_systems',
          'caption' => 'Allowed Spatial Ref Systems',
          'description' => 'List of allowable spatial reference systems, comma
          separated. Use the spatial ref system code (e.g. OSGB or the EPSG code
          number such as 4326). Set to "default" to use the settings defined in
          the IForm Settings page.',
          'type' => 'string',
          'default' => 'default',
          'group' => 'Other Map Settings',
        ],
        [
          'name' => 'survey_id',
          'caption' => 'Survey dataset',
          'description' => 'The survey dataset that data will be posted into and
          that defines custom attributes.',
          'type' => 'select',
          'table' => 'survey',
          'captionField' => 'title',
          'valueField' => 'id',
          'siteSpecific' => TRUE,
        ],
        [
          'name' => 'high_volume',
          'caption' => 'High volume reporting',
          'description' => 'Tick this box to enable caching which prevents
          reporting pages with a high number of hits from generating excessive
          server load. Currently compatible only with reporting pages that do
          not integrate with the user profile.',
          'type' => 'boolean',
          'default' => FALSE,
          'required' => FALSE,
        ],
      ]
    );
    return $retVal;
  }

  /**
   * Declare the list of permissions we've got set up to pass to the CMS' permissions code.
   *
   * @param int $nid
   *   Node ID, not used.
   * @param array $args
   *   Form parameters array, used to extract the defined permissions.
   *
   * @return array
   *   List of distinct permissions.
   */
  public static function get_perms($nid, $args) {
    $perms = [];
    if (!empty($args['structure'])) {
      // Scan for @permission=... in the form structure.
      $structure = data_entry_helper::explode_lines($args['structure']);
      $permissions = preg_grep('/^@((smp|occ|loc)Attr:\d+|)?permission=/', $structure);
      foreach ($permissions as $permission) {
        $parts = explode('=', $permission, 2);
        $perms[] = array_pop($parts);
      }
    }
    return $perms;
  }

  /**
   * Return the generated form output.
   *
   * @return string
   *   Form HTML.
   */
  public static function get_form($args, $nid) {
    data_entry_helper::$website_id = $args['website_id'];
    if (!empty($args['high_volume']) && $args['high_volume']) {
      // Node level caching for most page hits.
      $cached = data_entry_helper::cache_get(['node' => $nid], HIGH_VOLUME_CACHE_TIMEOUT);
      if ($cached !== FALSE) {
        $cached = explode('|!|', $cached);
        data_entry_helper::$javascript = $cached[1];
        data_entry_helper::$late_javascript = $cached[2];
        data_entry_helper::$onload_javascript = $cached[3];
        data_entry_helper::$required_resources = json_decode($cached[4], TRUE);
        return $cached[0];
      }
    }
    self::$nid = $nid;
    self::$called_class = 'iform_' . hostsite_get_node_field_value($nid, 'iform');

    // Convert parameter, defaults, into structured array.
    self::parse_defaults($args);
    // Supply parameters that may be missing after form upgrade.
    if (method_exists(self::$called_class, 'getArgDefaults')) {
      $args = call_user_func([self::$called_class, 'getArgDefaults'], $args);
    }

    // Get authorisation tokens to update and read from the Warehouse. We allow
    // child classes to generate this first if subclassed.
    if (self::$auth) {
      $auth = self::$auth;
    }
    else {
      $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
      self::$auth = $auth;
    }
    // Determine how the form was requested and therefore what to output.
    $mode = (method_exists(self::$called_class, 'getMode')) ?
      call_user_func([self::$called_class, 'getMode'], $args, $nid) : '';
    self::$mode = $mode;
    if ($mode === self::MODE_GRID) {
      // Output a grid of existing records.
      $r = call_user_func([self::$called_class, 'getGrid'], $args, $nid, $auth);
    }
    else {
      if (($mode === self::MODE_EXISTING || $mode === self::MODE_EXISTING_RO || $mode === self::MODE_CLONE) && is_null(data_entry_helper::$entity_to_load)) {
        // Only load if not in error situation.
        call_user_func_array([self::$called_class, 'getEntity'], [&$args, $auth]);
        // When editing, no need to step through all the pages to save a change.
        if ($mode === self::MODE_EXISTING) {
          $args['save_button_below_all_pages'] = TRUE;
        }
      }
      // Attributes must be fetched after the entity to load is filled in - this
      // is because the id gets filled in then!
      $attributes = (method_exists(self::$called_class, 'getAttributes'))
          ? call_user_func([self::$called_class, 'getAttributes'], $args, $auth)
          : [];
      $r = call_user_func([self::$called_class, 'get_form_html'], $args, $auth, $attributes);
    }
    if (!empty($args['high_volume']) && $args['high_volume']) {
      $c = $r . '|!|' . data_entry_helper::$javascript . '|!|' . data_entry_helper::$late_javascript . '|!|' .
          data_entry_helper::$onload_javascript . '|!|' . json_encode(data_entry_helper::$required_resources);
      data_entry_helper::cache_set(['node' => $nid], $c, HIGH_VOLUME_CACHE_TIMEOUT);
    }
    return $r;
  }

  /**
   * For groups of attributes output together, prepare their options.
   *
   * Either for [*] elements in a form which output all remaining custom
   * attributes, or for dynamic attribute groups linked to the chosen taxon,
   * the options list can contain options which apply to all (e.g.
   * @class=this-class) or options which only apply to one attribute control,
   * (e.g. @occAttr:123|class=that-class). This prepares the array of default
   * options to apply to all, plus a list of the attribute specific options.
   *
   * @param array $options
   *   List of options passed to the control block.
   * @param array $defAttrOptions
   *   Returns the list of default options to apply to all controls.
   * @param array $attrSpecificOptions
   *   Returns the list of controls that have custom options, each containing
   *   an associative array of the options to apply.
   */
  protected static function prepare_multi_attribute_options(array $options, array &$defAttrOptions, array &$attrSpecificOptions) {
    foreach ($options as $option => $value) {
      $optionId = explode('|', $option);
      if (count($optionId) === 1) {
        $defAttrOptions[$option] = apply_user_replacements($value);
      }
      elseif (count($optionId) === 2) {
        if (!isset($attrSpecificOptions[$optionId[0]])) {
          $attrSpecificOptions[$optionId[0]] = [];
        }
        $attrSpecificOptions[$optionId[0]][$optionId[1]] = apply_user_replacements($value);
      }
    }
  }

  /**
   * Prepares the list of options for a single attribute control in a group.
   *
   * Either for [*] elements in a form which output all remaining custom
   * attributes, or for dynamic attribute groups linked to the chosen taxon,
   * prepares the options for a single control.
   *
   * @param string $baseAttrId
   *   The attribute control's ID, excluding the part which identifies an
   *   existing database record, e.g. occAttr:123 (not occAttr:123:456).
   * @param array $defAttrOptions
   *   List of default options to apply to all controls.
   * @param array $attrSpecificOptions
   *   List of controls that have custom options, each containing an
   *   associative array of the options to apply.
   *
   * @return array
   *   Options array for this control
   */
  protected static function extract_ctrl_multi_value_options($baseAttrId, array $defAttrOptions, array $attrSpecificOptions) {
    $ctrlOptions = array_merge($defAttrOptions);
    if (!empty($attrSpecificOptions[$baseAttrId])) {
      // Make sure extraParams is merged.
      if (!empty($ctrlOptions['extraParams']) && !empty($attrSpecificOptions[$baseAttrId]['extraParams'])) {
        $attrSpecificOptions[$baseAttrId]['extraParams'] = array_merge(
          $ctrlOptions['extraParams'],
          $attrSpecificOptions[$baseAttrId]['extraParams']
        );
      }
      $ctrlOptions = array_merge(
        $ctrlOptions,
        $attrSpecificOptions[$baseAttrId]
      );
    }
    return $ctrlOptions;
  }

  protected static function get_form_html($args, $auth, $attributes) {
    global $indicia_templates;
    $params = [$args, $auth, &$attributes];
    $r = call_user_func([self::$called_class, 'getHeader'], $args);
    $r .= call_user_func_array([self::$called_class, 'getFormHiddenInputs'], $params);
    if (self::$mode === self::MODE_CLONE) {
      call_user_func_array([self::$called_class, 'cloneEntity'], $params);
    }
    $customAttributeTabs = get_attribute_tabs($attributes);
    $tabs = self::get_all_tabs($args['structure'], $customAttributeTabs);
    if (isset($tabs['-'])) {
      $hasControls = FALSE;
      $r .= self::get_tab_content($auth, $args, '-', $tabs['-'], NULL, $attributes, $hasControls);
      unset($tabs['-']);
    }

    $r .= "<div id=\"controls\">\n";
    // Build a list of the tabs that actually have content.
    $tabHtml = self::get_tab_html($tabs, $auth, $args, $attributes);
    // Output the dynamic tab headers.
    if ($args['interface'] !== 'one_page') {
      $headerOptions = ['tabs' => []];
      foreach ($tabHtml as $tab => $tabContent) {
        $alias = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($tab));
        $tabtitle = lang::get("LANG_Tab_$alias");
        if ($tabtitle === "LANG_Tab_$alias") {
          // If no translation provided, we'll just use the standard heading.
          $tabtitle = $tab;
        }
        $headerOptions['tabs']['#tab-' . $alias] = $tabtitle;
      }
      $r .= data_entry_helper::tab_header($headerOptions);
      data_entry_helper::enable_tabs([
        'divId' => 'controls',
        'style' => $args['interface'],
        'progressBar' => isset($args['tabProgress']) && $args['tabProgress'] == TRUE,
        'progressBarOptions' => isset($args['progressBarOptions']) ? $args['progressBarOptions'] : [],
        'navButtons' => isset($args['force_next_previous']) && $args['force_next_previous'],
      ]);
    }
    else {
      // Ensure client side validation is activated if requested on single page
      // forms. This is done in the enable_tabs bit above. This is useful if we
      // have custom client side validation.
      if (isset(data_entry_helper::$validated_form_id)) {
        data_entry_helper::$javascript .= "
$('#" . data_entry_helper::$validated_form_id . "').submit(function() {
  var tabinputs = $('#" . data_entry_helper::$validated_form_id . "').find('input,select,textarea').not(':disabled,[name=\"\"],.scTaxonCell,.inactive');
  var tabtaxoninputs = $('#" . data_entry_helper::$validated_form_id . " .scTaxonCell').find('input,select').not(':disabled');
  if ((tabinputs.length>0 && !tabinputs.valid()) || (tabtaxoninputs.length>0 && !tabtaxoninputs.valid())) {
    alert('" . lang::get('Before you can save the data on this form, some of the values in the input boxes need checking. ' .
            'These have been highlighted on the form for you.') . "');
    return false;
  }
  return true;
});\n";
      }
    }
    // Output the dynamic tab content.
    $pageIdx = 0;
    $singleSpeciesLabel = self::$singleSpeciesName;
    foreach ($tabHtml as $tab => $tabContent) {
      // Get a machine readable alias for the heading.
      $tabalias = 'tab-' . preg_replace('/[^a-zA-Z0-9]/', '', strtolower($tab));
      $r .= "<div id=\"$tabalias\">\n";
      // We only want to show the single species message to the user if they
      // have selected the option and we are in single species mode. We also
      // want to only show it on the species tab otherwise in 'All one page'
      // mode it will appear multiple times.
      if (isset($args['single_species_message']) && $args['single_species_message'] && $tabalias === 'tab-species' && isset($singleSpeciesLabel)) {
        $r .= str_replace('{message}', lang::get('You are submitting a record of {1}', $singleSpeciesLabel), $indicia_templates['messageBox']);
      }
      // For wizard include the tab title as a header.
      if ($args['interface'] === 'wizard') {
        $r .= '<h1>' . $headerOptions['tabs']["#$tabalias"] . '</h1>';
      }
      $r .= $tabContent;
      if (isset($args['verification_panel']) && $args['verification_panel'] && $pageIdx == count($tabHtml) - 1) {
        $r .= data_entry_helper::verification_panel([
          'readAuth' => $auth['read'],
          'panelOnly' => TRUE,
        ]);
      }
      // Add any buttons required at the bottom of the tab.
      if ($args['interface'] === 'wizard' || ($args['interface'] === 'tabs' && isset($args['force_next_previous']) && $args['force_next_previous'])) {
        $r .= data_entry_helper::wizard_buttons([
          'divId' => 'controls',
          'page' => $pageIdx === 0 ? 'first' : (($pageIdx === count($tabHtml) - 1) ? 'last' : 'middle'),
          'includeVerifyButton' => isset($args['verification_panel']) && $args['verification_panel'] && ($pageIdx === count($tabHtml) - 1),
          'includeSubmitButton' => (self::$mode !== self::MODE_EXISTING_RO),
          'includeDeleteButton' => (self::$mode === self::MODE_EXISTING),
          'includeDraftButton' => !empty($args['includeDraftButton']),
        ]);
      }
      elseif ($pageIdx === count($tabHtml) - 1) {
        // We need the verify button as well if this option is enabled
        if (isset($args['verification_panel']) && $args['verification_panel']) {
          $r .= '<button type="button" class="' . $indicia_templates['buttonDefaultClass'] . '" id="verify-btn">' . lang::get('Precheck my records') . "</button>\n";
        }
        if (call_user_func([self::$called_class, 'isDataEntryForm']) && method_exists(self::$called_class, 'getSubmitButtons')
            && !($args['interface'] === 'tabs' && !empty($args['save_button_below_all_pages']))) {
          // Last part of a non wizard interface must insert a save button,
          // unless it is tabbed interface with save button beneath all pages.
          $r .= call_user_func([self::$called_class, 'getSubmitButtons'], $args);
        }
      }
      $pageIdx++;
      $r .= "</div>\n";
    }

    // Add a save button for a one-page form with no =tabs= in form structure.
    if (
      call_user_func([self::$called_class, 'isDataEntryForm']) &&
      method_exists(self::$called_class, 'getSubmitButtons') &&
      $args['interface'] === 'one_page' &&
      $pageIdx === 0
    ) {
      $r .= call_user_func([self::$called_class, 'getSubmitButtons'], $args);
    }

    $r .= "</div>\n";
    $r .= call_user_func([self::$called_class, 'getFooter'], $args);
    if (method_exists(self::$called_class, 'linkSpeciesPopups')) {
      $r .= call_user_func([self::$called_class, 'linkSpeciesPopups'], $args);
    }
    return $r;
  }

  /**
   * Overridable function to retrieve the HTML to appear above the dynamically constructed form,
   * which by default is an HTML form for data submission.
   *
   * @param array $args
   *   Form parameters.
   */
  protected static function getHeader(array $args) {
    if (call_user_func([self::$called_class, 'isDataEntryForm'])) {
      // Make sure the form action points back to this page.
      $reloadPath = call_user_func([self::$called_class, 'getReloadPath'], $args['available_for_groups']);
      // Request automatic JS validation.
      if (!isset($args['clientSideValidation']) || $args['clientSideValidation']) {
        data_entry_helper::enable_validation('entry_form');
      }
      return "<form method=\"post\" id=\"entry_form\" action=\"$reloadPath\" enctype=\"multipart/form-data\">\n";
    }
    return '';
  }

  /**
   * Overridable function to retrieve the HTML to appear below the dynamically constructed form,
   * which by default is the closure of the HTML form for data submission.
   *
   * @param type $args
   */
  protected static function getFooter($args) {
    $r = '';
    if (call_user_func([self::$called_class, 'isDataEntryForm'])) {
      // Add a single submit button outside the tabs if a button needs to be
      // visible all the time.
      if ($args['interface'] === 'tabs' && $args['save_button_below_all_pages'] && method_exists(self::$called_class, 'getSubmitButtons')) {
        $r .= call_user_func([self::$called_class, 'getSubmitButtons'], $args);
      }
      if (!empty(data_entry_helper::$validation_errors)) {
        $r .= data_entry_helper::dump_remaining_errors();
      }
      $r .= "</form>";
    }
    return $r;
  }

  /**
   * Retrieve hidden inputs to add to a form.
   *
   * Overridable function to retrieve the additional HTML to appear at the top
   * of the first tab or form section. This is normally a set of hidden inputs,
   * containing things like the website ID to post with a form submission.
   *
   * @param type $args
   */
  protected static function getFormHiddenInputs($args, $auth, &$attributes) {
    $r = '';
    if (call_user_func([self::$called_class, 'isDataEntryForm'])) {
      // Get authorisation tokens to update the Warehouse, plus any other hidden data.
      $r = $auth['write'] .
            "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"$args[website_id]\" />\n" .
            "<input type=\"hidden\" id=\"survey_id\" name=\"survey_id\" value=\"$args[survey_id]\" />\n";
      $r .= get_user_profile_hidden_inputs($attributes, $args, isset(data_entry_helper::$entity_to_load['sample:id']), $auth['read']);
    }
    return $r;
  }

  /**
   * Overridable function to supply default values to a new record from the entity_to_load.
   *
   * @param type $args
   */
  protected static function cloneEntity($args, $auth, &$attributes) {
  }

  /**
   * Overridable method to get the buttons to include for form submission.
   *
   * Might be overridden to include a delete button for example.
   */
  protected static function getSubmitButtons($args) {
    global $indicia_templates;
    return '<input type="submit" class="' . $indicia_templates['buttonHighlightedClass'] .
      '" id="save-button" value="' . lang::get('Submit') . "\" />\n";
  }

  protected static function getReloadPath($availableForGroups) {
    $reload = data_entry_helper::get_reload_link_parts();
    unset($reload['params']['sample_id']);
    unset($reload['params']['occurrence_id']);
    unset($reload['params']['location_id']);
    unset($reload['params']['new']);
    unset($reload['params']['newLocation']);
    // On group enabled forms, if editing a group record, ensure group in URL
    // on form post so it carries on to the next record input.
    if (!empty(data_entry_helper::$entity_to_load['sample:group_id']) && $availableForGroups) {
      $reload['params']['group_id'] = data_entry_helper::$entity_to_load['sample:group_id'];
    }
    $reloadPath = $reload['path'];
    if (count($reload['params'])) {
      // Decode params prior to encoding to prevent double encoding.
      foreach ($reload['params'] as $key => $param) {
        $reload['params'][$key] = urldecode($param);
      }
      $reloadPath .= '?' . http_build_query($reload['params']);
    }
    return $reloadPath;
  }

  protected static function get_tab_html($tabs, $auth, $args, $attributes) {
    $tabHtml = [];
    foreach ($tabs as $tab => $tabContent) {
      // Keep track on if the tab actually has real content, so we can avoid
      // floating instructions if all the controls were removed by user profile
      // integration for example.
      $hasControls = FALSE;
      // Get a machine readable alias for the heading, if we are showing tabs
      // and we are loading anything other than the first tab.
      if ($args['interface'] === 'one_page' || count($tabHtml) === 0) {
        $tabalias = NULL;
      }
      else {
        $tabalias = 'tab-' . preg_replace('/[^a-zA-Z0-9]/', '', strtolower($tab));
      }
      $html = self::get_tab_content($auth, $args, $tab, $tabContent, $tabalias, $attributes, $hasControls);
      if (!empty($html) && $hasControls) {
        $tabHtml[$tab] = $html;
      }
    }
    return $tabHtml;
  }

  protected static function get_tab_content($auth, $args, $tab, $tabContent, $tabalias, &$attributes, &$hasControls) {
    global $indicia_templates;
    // Cols array used if we find | splitters.
    $cols = [];
    $defAttrOptions = ['extraParams' => $auth['read']];
    if (isset($args['attribute_termlist_language_filter'])) {
      if ($args['attribute_termlist_language_filter'] === '1') {
        $defAttrOptions['language'] = iform_lang_iso_639_2($args['language']);
      }
      elseif ($args['attribute_termlist_language_filter'] === 'clientI18n') {
        $defAttrOptions['extraParams']['preferred'] = 't';
        $defAttrOptions['translate'] = 't';
      }
    }
    // Create array of attribute field names to test against later.
    $attribNames = [];
    foreach ($attributes as $key => $attrib) {
      $attribNames[$key] = $attrib['id'];
    }
    $html = '';
    // Now output the content of the tab. Use a for loop, not each, so we can
    // treat several rows as one object.
    for ($i = 0; $i < count($tabContent); $i++) {
      $component = trim($tabContent[$i]);
      if (preg_match('/\A\?[^�]*\?\z/', $component) === 1) {
        // Component surrounded by ? so represents a help text.
        $helpText = substr($component, 1, -1);
        $html .= str_replace('{message}', lang::get($helpText), $indicia_templates['messageBox']);
      }
      elseif (preg_match('/\A\[[^�]*\]\z/', $component) === 1) {
        // Component surrounded by [] so represents a control or control block
        // Anything following the component that starts with @ is an option to
        // pass to the control.
        $options = [
          'nid' => self::$nid,
        ];
        while ($i < count($tabContent) - 1 && substr($tabContent[$i + 1], 0, 1) === '@' || trim($tabContent[$i]) === '') {
          $i++;
          // Ignore empty lines or lines which don't have key/value pairing.
          if (trim($tabContent[$i]) !== '' && strpos($tabContent[$i], '=') !== FALSE) {
            $option = explode('=', substr($tabContent[$i], 1), 2);
            if (substr(trim($option[1]), 0, 4) === '<!--') {
              $option[1] = preg_replace('/^<!\-\-/', '', trim($option[1]));
              while (substr(trim($option[1]), -3) !== '-->' && $i < count($tabContent) - 1) {
                $i++;
                $option[1] .= "\n" . $tabContent[$i];
              }
              $option[1] = preg_replace('/\-\->$/', '', trim($option[1]));
            }
            if (!isset($option[1])||$option[1] === 'false') {
              $options[$option[0]] = FALSE;
            }
            else {
              $options[$option[0]] = json_decode($option[1], TRUE);
              // If not json then need to use option value as it is.
              if ($options[$option[0]] == '') {
                $options[$option[0]] = $option[1];
              }
            }
            // UrlParam is special as it loads the control's default value from
            // $_GET.
            if ($option[0] === 'urlParam' && isset($_GET[$option[1]])) {
              $options['default'] = $_GET[$option[1]];
            }
            // Label and helpText should both get translated.
            if (preg_match('/^([a-z]{3}Attr:\d+\|)?label$/', $option[0])) {
              $options[$option[0]] = lang::get($options[$option[0]]);
            }
          }
        }
        // If @permission specified as an option, then check that the user has
        // access to this control.
        if (!empty($options['permission'])) {
          // @hideIfHasPermission=true reverses the permissions check.
          if (empty($options['hideIfHasPermission']) !== hostsite_user_has_permission($options['permission'])) {
            continue;
          }
        }
        $parts = explode('.', str_replace(['[', ']'], '', $component));
        $method = 'get_control_' . preg_replace('/[^a-zA-Z0-9]/', '', strtolower($component));
        if (!empty($args['high_volume']) && $args['high_volume']) {
          // Enable control level report caching when in high_volume mode.
          $options['caching'] = empty($options['caching']) ? TRUE : $options['caching'];
          $options['cachetimeout'] = empty($options['cachetimeout']) ? HIGH_VOLUME_CONTROL_CACHE_TIMEOUT : $options['cachetimeout'];
        }
        // Allow user settings to override the control - see
        // iform_user_ui_options.module.
        if (isset(data_entry_helper::$data['structureControlOverrides']) && !empty(data_entry_helper::$data['structureControlOverrides'][$component])) {
          $options = array_merge($options, data_entry_helper::$data['structureControlOverrides'][$component]);
        }
        if (count($parts) === 1 && method_exists(self::$called_class, $method)) {
          // Outputs a control for which a specific output function has been
          // written.
          $html .= call_user_func([self::$called_class, $method], $auth, $args, $tabalias, array_merge(['extraParams' => $auth['read']], $options));
          $hasControls = TRUE;
        }
        elseif (count($parts) === 2) {
          $extensionPath = dirname($_SERVER['SCRIPT_FILENAME']) . '/' .
            data_entry_helper::relative_client_helper_path() . "prebuilt_forms/extensions/$parts[0].php";
          include_once $extensionPath;
          if (method_exists("extension_$parts[0]", $parts[1])) {
            if (!empty($options['fieldname'])) {
              // If reloading an existing attribute, set the default and
              // fieldname to contain the value ID.
              // First, if this is an occurrence attribute then the list of
              // attributes might not be loaded.
              if (substr($options['fieldname'], 0, 7) === 'occAttr'
                && method_exists(self::$called_class, 'load_custom_occattrs')
                && !empty(data_entry_helper::$entity_to_load['occurrence:id'])) {
                $occAttrs = call_user_func(
                  [self::$called_class, 'load_custom_occattrs'], $auth['read'], $args['survey_id']);
                foreach ($occAttrs as $attr) {
                  if ($options['fieldname'] === $attr['id']) {
                    $options['fieldname'] = $attr['fieldname'];
                    $options['default'] = $attr['default'];
                  }
                }
              }
              else {
                $attribKey = array_search($options['fieldname'], $attribNames);
                if ($attribKey !== FALSE) {
                  $options['fieldname'] = $attributes[$attribKey]['fieldname'];
                  $options['default'] = $attributes[$attribKey]['default'];
                }
              }
            }
            // Outputs a control for which a specific extension function has
            // been written.
            $path = call_user_func([self::$called_class, 'getReloadPath'], $args['available_for_groups']);
            // Pass the classname of the form through to the extension control
            // method to allow access to calling class functions and variables.
            $args['calling_class'] = self::$called_class;
            $html .= call_user_func(['extension_' . $parts[0], $parts[1]], $auth, $args, $tabalias, $options, $path, $attributes);
            $hasControls = TRUE;
            // Auto-add JavaScript for the extension.
            $d6 = (defined('DRUPAL_CORE_COMPATIBILITY') && DRUPAL_CORE_COMPATIBILITY === '6.x');
            $d7 = (defined('DRUPAL_CORE_COMPATIBILITY') && DRUPAL_CORE_COMPATIBILITY === '7.x');
            // Ignore D8+ as it uses asset libraries instead of drupal_add_js.
            if (file_exists(iform_client_helpers_path() . 'prebuilt_forms/extensions/' . $parts[0] . '.js')) {
              if ($d6) {
                drupal_add_js(iform_client_helpers_path() . 'prebuilt_forms/extensions/' . $parts[0] . '.js', 'module', 'header', FALSE, TRUE, FALSE);
              }
              elseif ($d7) {
                drupal_add_js(iform_client_helpers_path() . 'prebuilt_forms/extensions/' . $parts[0] . '.js', ['preprocess' => FALSE]);
              }
            }
            if (file_exists(iform_client_helpers_path() . 'prebuilt_forms/extensions/' . $parts[0] . '.css')) {
              if ($d6) {
                drupal_add_css(iform_client_helpers_path() . 'prebuilt_forms/extensions/' . $parts[0] . '.css', 'module', 'all', FALSE);
              }
              elseif ($d7) {
                drupal_add_css(iform_client_helpers_path() . 'prebuilt_forms/extensions/' . $parts[0] . '.css', ['preprocess' => FALSE]);
              }
            }
          }
          else {
            hostsite_show_message("The form structure references an unrecognised extension control $component.", 'error');
            $html .= lang::get("The $component extension cannot be found.");
          }
        }
        elseif (($attribKey = array_search(substr($component, 1, -1), $attribNames)) !== FALSE
            || preg_match('/^\[[a-zA-Z]+:(?P<ctrlId>[0-9]+)\]/', $component, $matches)) {
          // Control is a smpAttr or other attr control.
          if (empty($options['extraParams'])) {
            $options['extraParams'] = array_merge($defAttrOptions['extraParams']);
          }
          else {
            $options['extraParams'] = array_merge($defAttrOptions['extraParams'], (array) $options['extraParams']);
          }
          // Merge extraParams first so we don't loose authentication.
          $options = array_merge($defAttrOptions, $options);
          foreach ($options as $key => &$value) {
            $value = apply_user_replacements($value);
          }
          if ($attribKey !== FALSE) {
            // A smpAttr control.
            $html .= data_entry_helper::outputAttribute($attributes[$attribKey], $options);
            $attributes[$attribKey]['handled'] = TRUE;
          }
          else {
            // If the control name of form name:id, then we will call
            // get_control_name passing the id as a parameter.
            $method = 'get_control_' . preg_replace('/[^a-zA-Z]/', '', strtolower($component));
            if (method_exists(self::$called_class, $method)) {
              $options['ctrlId'] = $matches['ctrlId'];
              $html .= call_user_func([self::$called_class, $method], $auth, $args, $tabalias, $options);
            }
            else {
              $html .= "Unsupported control $component<br/>";
            }
          }
          $hasControls = TRUE;
        }
        elseif ($component === '[*]') {
          // This outputs any custom attributes that remain for this tab. The
          // custom attributes can be configured in the settings text using
          // something like @smpAttr:4|label=My label. The next bit of code
          // parses these out into an array used when building the html.
          // Alternatively, a setting like @option=value is applied to all the
          // attributes.
          $attrSpecificOptions = [];
          $attrGenericOptions = [];
          foreach ($options as $option => $value) {
            // Split the id of the option into the control name and option name.
            $optionId = explode('|', $option);
            if (count($optionId) > 1) {
              // Found an option like @smpAttr:4|label=My label.
              if (!isset($attrSpecificOptions[$optionId[0]])) {
                $attrSpecificOptions[$optionId[0]] = [];
              }
              // Ensure default extraParams (such as auth tokens) not
              // overwritten by a custom option.
              if ($optionId[1] === 'extraParams') {
                $value = array_merge($defAttrOptions['extraParams'], (array) $value);
              }
              $attrSpecificOptions[$optionId[0]][$optionId[1]] = apply_user_replacements($value);
            }
            else {
              // Found an option like @option=value
              $attrGenericOptions = array_merge($attrGenericOptions, [$option => $value]);
            }
          }
          $attrHtml = get_attribute_html($attributes, $args, $defAttrOptions, $tab,
            array_merge($attrGenericOptions, $attrSpecificOptions));
          if (!empty($attrHtml)) {
            $hasControls = TRUE;
          }
          $html .= $attrHtml;
        }
        else {
          $html .= "The form structure includes a control called $component which is not recognised.<br/>";
          // Ensure $hasControls is true so that the error message is shown.
          $hasControls = TRUE;
        }
      }
      elseif ($component === '|') {
        // Column splitter. So, store the col html and start on the next
        // column.
        $cols[] = $html;
        $html = '';
      }
      else {
        // Output anything else as is. This allow us to add html to the form
        // structure.
        $html .= helper_base::getStringReplaceTokens($component, $auth['read']);
      }
    }
    if (count($cols) > 0) {
      $cols[] = $html;
      // A splitter in the structure so put the stuff so far in a 50% width left
      // float div, and the stuff that follows in a 50% width right float div.
      $html = str_replace(['{col-1}', '{col-2}', '{attrs}'], array_merge($cols, ['']), $indicia_templates['two-col-50']);
      if (count($cols) > 2) {
        unset($cols[1]);
        unset($cols[0]);
        $html .= '<div class="follow_on_block" style="clear:both;">' . implode('', $cols) . '</div>';
      }
      else {
        // Needed so any tab div is stretched around them.
        $html .= '<div class="follow_on_block" style="clear:both;"></div>';
      }
    }
    return $html;
  }

  /**
   * Finds the list of all tab names that are going to be required, either by the form
   * structure, or by custom attributes.
   */
  protected static function get_all_tabs($structure, $attrTabs) {
    $structureArr = helper_base::explode_lines($structure);
    $structureTabs = [];
    // A default 'tab' for content that must appear above the set of tabs.
    $currentTab = '-';
    foreach ($structureArr as $component) {
      if (preg_match('/^=[A-Za-z0-9, \'\-\*\?]+=$/', trim($component), $matches) === 1) {
        $currentTab = substr($matches[0], 1, -1);
        $structureTabs[$currentTab] = [];
      }
      else {
        $structureTabs[$currentTab][] = $component;
      }
    }
    // If any additional tabs are required by attributes, add them to the
    // position marked by a dummy tab named [*].
    // First get rid of any tabs already in the structure.
    foreach ($attrTabs as $tab => $tabContent) {
      // Case-insensitive check if attribute tab already in form structure..
      if (in_array(strtolower($tab), array_map('strtolower', array_keys($structureTabs)))) {
        unset($attrTabs[$tab]);
      }
    }
    // Now we have a list of form structure tabs, with the position of the
    // $attrTabs marked by *. So join it all together.
    // Maybe there is a better way to do this?
    $allTabs = [];
    foreach ($structureTabs as $tab => $tabContent) {
      if ($tab == '*') {
        $allTabs += $attrTabs;
      }
      else {
        $allTabs[$tab] = $tabContent;
      }
    }
    return $allTabs;
  }

  /**
   * Convert the unstructured textarea of default values into a structured array.
   */
  protected static function parse_defaults(&$args) {
    $result = [];
    if (isset($args['defaults'])) {
      $result = helper_base::explode_lines_key_value_pairs($args['defaults']);
    }
    $args['defaults'] = $result;
  }

  /**
   * Get the spatial reference control.
   * Defaults to sample:entered_sref. Supply $options['fieldname'] for submission to other database fields.
   */
  protected static function get_control_spatialreference($auth, $args, $tabalias, $options) {
    // Build the array of spatial reference systems into a format Indicia can use.
    $systems = [];
    $list = explode(',', str_replace(' ', '', $args['spatial_systems']));
    foreach ($list as $system) {
      $systems[$system] = lang::get("sref:$system");
    }
    return data_entry_helper::sref_and_system(array_merge([
      'label' => lang::get('LANG_SRef_Label'),
      'systems' => $systems,
    ], $options));
  }

  /**
   * Get the location search control.
   */
  protected static function get_control_placesearch($auth, $args, $tabalias, $options) {
    $georefOpts = iform_map_get_georef_options($args, $auth['read']);
    return data_entry_helper::georeference_lookup(array_merge(
      $georefOpts,
      $options
    ));
  }

  /**
   * Get a key name which defines the type of an attribute.
   *
   * Since sex, stage and abundance attributes interact, treat them as the same
   * thing for the purposes of duplicate removal when dynamic attributes are
   * loaded from different levels in the taxonomic hierarchy. Otherwise we
   * use the attribute's system function or term name (i.e. Darwin Core term).
   *
   * @param array
   *   Attribute definition.
   *
   * @return string
   *   Key name.
   */
  protected static function getAttrTypeKey($attr) {
    $sexStageAttrs = ['sex', 'stage', 'sex_stage', 'sex_stage_count'];
    // For the purposes of duplicate handling, we treat sex, stage and count
    // related data as the same thing.
    if (in_array($attr['system_function'], $sexStageAttrs)) {
      return 'sex/stage/count';
    }
    else {
      return empty($attr['system_function']) ? $attr['term_name'] : $attr['system_function'];
    }
  }

  /**
   * Dynamic taxon linked attribute duplicate removal.
   *
   * If a higher taxon has an attribute linked to it and a lower taxon has
   * a different attribute of the same type, then the lower taxon's attribute
   * should take precedence. For example, a stage linked to Animalia would
   * be superceded by a stage attribute linked to Insecta.
   *
   * @param array $list
   *   List of attributes which will be modified to remove duplicates.
   */
  protected static function removeDuplicateAttrs(&$list) {
    // First build a list of the different types of attribute and work out
    // the highest taxon_rank_sort_order (i.e. the lowest rank) which has
    // attributes for each attribute type. Whilst doing this we can also
    // discard duplicates, e.g. if same attribute linked at several taxonomic
    // levels.
    $attrTypeSortOrders = [];
    $attrIds = [];
    foreach ($list as $idx => $attr) {
      if (in_array($attr['attribute_id'], $attrIds)) {
        unset($list[$idx]);
      }
      else {
        $attrIds[] = $attr['attribute_id'];
        $attrTypeKey = self::getAttrTypeKey($attr);
        if (!empty($attrTypeKey)) {
          if (!array_key_exists($attrTypeKey, $attrTypeSortOrders) ||
              (integer) $attr['attr_taxon_rank_sort_order'] > $attrTypeSortOrders[$attrTypeKey]) {
            $attrTypeSortOrders[$attrTypeKey] = (integer) $attr['attr_taxon_rank_sort_order'];
          }
        }
      }
    }

    // Now discard any attributes of a type, where there are attributes of the
    // same type attached to a lower rank taxon. E.g. a genus stage attribute
    // will cause a family stage attribute to be discarded.
    foreach ($list as $idx => $attr) {
      $attrTypeKey = self::getAttrTypeKey($attr);
      if (!empty($attrTypeKey) && $attrTypeSortOrders[$attrTypeKey] > (integer) $attr['attr_taxon_rank_sort_order']) {
        unset($list[$idx]);
      }
    }
  }

  /**
   * Builds the output for a set of dynamically taxon-linked attributes.
   *
   * @return string
   *   HTML output.
   */
  protected static function getDynamicAttrsOutput($prefix, $readAuth, $attrs, $options, $language) {
    // A tracker for the 4 possible levels of fieldset so we can detect changes.
    $fieldsetTracking = [
      'l1_category' => '',
      'l2_category' => '',
      'outer_block_name' => '',
      'inner_block_name' => '',
    ];
    $fieldsetFieldNames = array_keys($fieldsetTracking);
    $attrSpecificOptions = [];
    $defAttrOptions = ['extraParams' => $readAuth];
    self::prepare_multi_attribute_options($options, $defAttrOptions, $attrSpecificOptions);
    $r = '';
    foreach ($attrs as $attr) {
      // Output any nested fieldsets required. Iterate through the possible 4
      // levels.
      foreach ($fieldsetFieldNames as $idx => $fieldsetFieldName) {
        // Is there a change at this level?
        if ($fieldsetTracking[$fieldsetFieldName] !== $attr[$fieldsetFieldName]) {
          // Unwind all the fieldsets that are open at this level and below.
          for ($i = $idx; $i < count($fieldsetTracking); $i++) {
            if (!empty($fieldsetTracking[$fieldsetFieldNames[$i]])) {
              $r .= '</fieldset>';
              $fieldsetTracking[$fieldsetFieldNames[$i]] = '';
            }
          }
          // Open a new fieldset for this level, if one is needed.
          if (!empty($attr[$fieldsetFieldName])) {
            $r .= '<fieldset class="attrs-container"><legend>' . lang::get($attr[$fieldsetFieldName]) . '</legend>';
          }
          $fieldsetTracking[$fieldsetFieldName] = $attr[$fieldsetFieldName];
        }
      }
      $values = json_decode($attr['values']);
      $baseAttrId = "{$prefix}Attr:$attr[attribute_id]";
      $ctrlOptions = self::extract_ctrl_multi_value_options($baseAttrId, $defAttrOptions, $attrSpecificOptions);
      if ($language) {
        $ctrlOptions['language'] = $language;
      }
      $attr['id'] = $baseAttrId;
      $attr['caption'] = data_entry_helper::getTranslatedAttrField('caption', $attr, $language);
      $attr['fieldname'] = $baseAttrId;
      if ($attr['multi_value'] === 'f') {
        if (empty($values) || (count($values) === 1 && $values[0] === NULL)) {
          $attr['default'] = $attr['default_value'];
          $attr['displayValue'] = $attr['default_value_caption'];
          $attr['defaultUpper'] = $attr['default_upper_value'];
        }
        else {
          $value = $values[0];
          $attr['fieldname'] = "$baseAttrId:$value->id";
          $attr['default'] = $value->raw_value;
          $attr['displayValue'] = $value->value;
          $attr['defaultUpper'] = $value->upper_value;
        }
      }
      else {
        $doneValues = [];
        $default = [];
        foreach ($values as $value) {
          // Values may be duplicated if an attribute is linked to a taxon
          // twice in the taxon hierarchy, so we mitigate against it here
          // (otherwise SQL would be complex)
          if (!in_array($value->id, $doneValues)) {
            $default[] = [
              'fieldname' => "$baseAttrId:$value->id",
              'default' => $value->raw_value,
              'defaultUpper' => NULL,
              'caption' => $value->value,
            ];
            $doneValues[] = $value->id;
          }
        }
        $attr['default'] = $default;
      }
      $ctrlOptions['class'] = 'dynamic-attr';
      $r .= data_entry_helper::outputAttribute($attr, $ctrlOptions);
    }
    foreach ($fieldsetTracking as $fieldsetName) {
      if (!empty($fieldsetName)) {
        $r .= '</fieldset>';
      }
    }
    return $r;
  }

  /**
   * Checks a subset of the options passed to a control against a pattern.
   *
   * E.g. could check the subset of options that should be integers against a
   * regex to validate this. Throws errors when matches fail.
   *
   * @param string $regex
   *   Regular expression that defines the pattern options must match.
   * @param array $options
   *   Control options.
   * @param array $keysToCheck
   *   List of option names that contain values that need to match the format.
   * @param string $controlName
   *   Name of the control used to provide meaningful feedback in any errors
   *   raised.
   */
  protected static function checkOptionFormat($regex, $options, array $keysToCheck, $controlName) {
    $toCheck = array_intersect_key($options, array_combine($keysToCheck, $keysToCheck));
    foreach ($toCheck as $key => $value) {
      if ($value !== '' && !preg_match($regex, $value)) {
        throw new exception("Option @$key must be an integer for control [$controlName]");
      }
    }
  }

}
