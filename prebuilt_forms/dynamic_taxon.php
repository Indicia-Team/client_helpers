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
 * Prebuilt Indicia data entry form for adding and editing taxa.
 */

require_once 'includes/dynamic.php';

class iform_dynamic_taxon extends iform_dynamic {

  private static $attributes;

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_dynamic_taxon_definition() {
    return [
      'title' => 'Enter a taxon (customisable)',
      'category' => 'Data entry forms',
      'description' => 'A data entry form for defining species and higher taxa that will become available for data entry of occurrences.',
      'recommended' => TRUE,
    ];
  }

  /**
   * Get the list of parameters for this form.
   *
   * @return array
   *   List of parameters that this form requires.
   */
  public static function get_parameters() {
    $structureDescription = <<<HTML
Define the structure of the form. Each component goes on a new line and is nested inside the previous component where
appropriate. The following types of component can be specified. <br/>
<strong>=tab/page name=</strong> is used to specify the name of a tab or wizard page. (Alpha-numeric characters
only)<br/>
<strong>=*=</strong> indicates a placeholder for putting any custom attribute tabs not defined in this form
structure.<br/>
<strong>[control name]</strong> indicates a predefined control is to be added to the form with the following predefined
controls available: <br/>
<strong>[taxon]</strong> - an input for the accepted taxon name.<br/>
<strong>[language]</strong> - a drop down for selecting the language of the accepted name. Or, provide an option
@code=lat (or other ISO code for a supported language) to force the name to saved as a particular language.<br/>
<strong>[attribute]</strong> - an input for the taxon attribute (e.g. sensu lato).<br/>
<strong>[authority]</strong> - a text input for the taxon name's authority information.<br/>
<strong>[common names]</strong> - a text area for inputting a list of common names into.<br/>
<strong>[synonyms]</strong> - a text area for inputting a list of synonyms.<br/>
<strong>[parent]</strong> - a search box for choosing the taxon's parent. Alternatively the parent's ID can be forced by
providing a URL parameter parent_id containing the parent's taxa_taxon_list_id.<br/>
<strong>[taxon group]</strong> - a drop down for choosing the taxon group.<br/>
<strong>[taxon rank]</strong> - a drop down for choosing the taxon rank.<br/>
<strong>[description]</strong> - a text area for inputting a description which will be stored against the taxon.<br/>
<strong>[description in list]</strong> - a text area for inputting a description which will be stored against the taxon
within the context of this list.<br/>
<strong>[external key]</strong> - an input for an externally provided key where the taxon is derived from an external
source.<br/>
<strong>[search code]</strong> - an input for a taxon search code.<br/>
<strong>[sort order]</strong> - an input for a taxonomic sort order numeric value.<br/>
<strong>[taxon dynamic attributes]</strong> - a placeholder where any dynamically linked attributes will be placed,
e.g. attributes that are associated with one of the taxon's parents.<br/>
<strong>@option=value</strong> on the line(s) following any control allows you to override one of the options passed to
the control. The options available depend on the control. For example @label=Abundance would set the untranslated label
of a control to Abundance. Where the option value is an array, use valid JSON to encode the value. For example an array
of strings could be passed as @classes=["class1","class2"] or a keyed array as
@extraParams={"preferred":"true","orderby":"term"}. Other common options include helpText (set to a piece of additional
text to display alongside the control) and class (to add css classes to the control such as control-width-3). <br/>
<strong>[*]</strong> is used to make a placeholder for putting any custom attributes that should be inserted into the
current tab. When this option is used, you can change any of the control options for an individual custom attribute
control by putting @control|option=value on the subsequent line(s). For example, if a control is for smpAttr:4 then you
can update it's label by specifying @smpAttr:4|label=New Label on the line after the [*].<br/>
<strong>[taxcAttr:<i>n</i>]</strong> is used to insert a particular custom attribute identified by its ID number<br/>
<strong>?help text?</strong> is used to define help text to add to the tab, e.g. ?Enter the name of the site.? <br/>
<strong>all else</strong> is copied to the output html so you can add structure for styling.
HTML;
    $structureDefault = <<<TXT
=Taxon=

[taxon]

[language]
@code=lat

[attribute]

[authority]

[common names]

[synonyms]

[parent]

[taxon group]

[taxon rank]

[photos]

[description]

[description in list]

[external key]

[search code]

[sort order]

[taxon dynamic attributes]
TXT;
    $defaultsDescription = <<<TXT
Supply default values for each field as required. On each line, enter fieldname=value. For custom attributes, the
fieldname is the untranslated caption. For other fields, it is the model and fieldname, e.g. taxon.authority.
TXT;
    $retVal = array_merge(
      parent::get_parameters(),
      [
        [
          'name' => 'taxon_list_id',
          'caption' => 'Taxon list',
          'type' => 'select',
          'table' => 'taxon_list',
          'captionField' => 'title',
          'valueField' => 'id',
          'helpText' => 'The taxon list that will be used for all created taxa',
        ],
        [
          'name' => 'structure',
          'caption' => 'Form Structure',
          'description' => $structureDescription,
          'type' => 'textarea',
          'default' => $structureDefault,
          'group' => 'User Interface',
        ],
        [
          'name' => 'defaults',
          'caption' => 'Default Values',
          'description' => $defaultsDescription,
          'type' => 'textarea',
          'required' => FALSE,
        ],
      ]
    );
    $controlsToSkip = [
      'survey_id',
      'no_grid',
      'grid_num_rows',
    ];
    $controlsGroupsToSkip = [
      'initial map view',
      'base map layers',
      'advanced base map layers',
      'georeferencing',
      'other map settings',
    ];
    for ($i = count($retVal) - 1; $i >= 0; $i--) {
      if (in_array($retVal[$i]['name'], $controlsToSkip)) {
        unset($retVal[$i]);
      }
      elseif (!empty($retVal[$i]['group']) && in_array(strtolower($retVal[$i]['group']), $controlsGroupsToSkip)) {
        unset($retVal[$i]);
      }
    }
    return $retVal;
  }

  /**
   * Determine whether to show a form for either adding a new record or editing an existing one.
   *
   * @param array $args
   *   Iform parameters.
   * @param object $nid
   *   ID of node being shown.
   *
   * @return const
   *   The mode [MODE_NEW|MODE_EXISTING].
   */
  protected static function getMode($args, $nid) {
    if ($_POST && !is_null(data_entry_helper::$entity_to_load)) {
      // Errors with new sample or entity populated with post, so display this
      // data.
      $mode = self::MODE_EXISTING;
    }
    elseif (array_key_exists('taxa_taxon_list_id', $_GET)) {
      // Request for display of existing record.
      $mode = self::MODE_EXISTING;
    }
    else {
      // Request to create new record .
      $mode = self::MODE_NEW;
      data_entry_helper::$entity_to_load = [];
    }
    return $mode;
  }

  /**
   * Load an existing taxon's details for editing.
   *
   * @param array $args
   *   Form parameters.
   * @param array $auth
   *   Authorisation tokens.
   */
  protected static function getEntity($args, $auth) {
    data_entry_helper::$entity_to_load = [];
    data_entry_helper::load_existing_record($auth['read'], 'taxa_taxon_list', $_GET['taxa_taxon_list_id'], 'detail', FALSE, TRUE);
    // Load common names and synonyms.
    $otherNames = data_entry_helper::get_population_data([
      'table' => 'taxa_taxon_list',
      'extraParams' => $auth['read'] + [
        'view' => 'list',
        'taxon_meaning_id' => data_entry_helper::$entity_to_load['taxa_taxon_list:taxon_meaning_id'],
        'taxon_list_id' => $args['taxon_list_id'],
        'preferred' => 'f',
      ],
      'caching' => FALSE,
    ]);
    $commonNames = [];
    $synonyms = [];
    foreach ($otherNames as $name) {
      if ($name['language'] === 'lat') {
        $synonyms[] = "$name[taxon]|$name[authority]";
      }
      else {
        $commonNames[] = "$name[taxon]|$name[language]";
      }
    }
    data_entry_helper::$entity_to_load['metaFields:commonNames'] = implode("\n", $commonNames);
    data_entry_helper::$entity_to_load['metaFields:synonyms'] = implode("\n", $synonyms);
  }

  /**
   * Load dynamic attributes required by the form.
   *
   * @param array $args
   *   Form configuration.
   * @param array $auth
   *   Authorisation tokens.
   *
   * @return array
   *   List of attribute data from the database.
   */
  protected static function getAttributes(array $args, array $auth) {
    if (!isset(self::$attributes)) {
      $id = isset(data_entry_helper::$entity_to_load['taxa_taxon_list:id']) ?
        data_entry_helper::$entity_to_load['taxa_taxon_list:id'] : NULL;
      $attrOpts = [
        'id' => $id,
        'valuetable' => 'taxa_taxon_list_attribute_value',
        'attrtable' => 'taxa_taxon_list_attribute',
        'key' => 'taxa_taxon_list_id',
        'fieldprefix' => 'taxAttr',
        'extraParams' => $auth['read'] + ['taxon_list_id' => $args['taxon_list_id']],
      ];
      self::$attributes = data_entry_helper::getAttributes($attrOpts, FALSE);
    }
    return self::$attributes;
  }

  /**
   * Retrieve the additional HTML to appear at the top form.
   *
   * Content for the top of the first tab or form section. This is a set of
   * hidden inputs containing the website ID andsurvey ID as well as an
   * existing location's ID.
   *
   * @return string
   *   Additional HTML for the first tab.
   */
  protected static function getFirstTabAdditionalContent($args, $auth, &$attributes) {
    // Get authorisation tokens to update the Warehouse, plus any other hidden
    // data.
    $r = $auth['write'] . <<<HTML
<input type="hidden" id="website_id" name="website_id" value="$args[website_id]" />
<input type="hidden" id="taxa_taxon_list:taxon_list_id" name="taxa_taxon_list:taxon_list_id" value="$args[taxon_list_id]" />
<input type="hidden" id="taxa_taxon_list:preferred" name="taxa_taxon_list:preferred" value="t" />

HTML;
    if (isset(data_entry_helper::$entity_to_load['taxa_taxon_list:id'])) {
      $defaults = [
        'taxa_taxon_list_id' => data_entry_helper::$entity_to_load['taxa_taxon_list:id'],
        'taxon_id' => data_entry_helper::$entity_to_load['taxon:id'],
        'taxon_meaning_id' => data_entry_helper::$entity_to_load['taxon_meaning:id'],
      ];
      $r .= <<<HTML
<input type="hidden" id="taxa_taxon_list:id" name="taxa_taxon_list:id" value="$defaults[taxa_taxon_list_id]" />
<input type="hidden" id="taxon:id" name="taxon:id" value="$defaults[taxon_id]" />
<input type="hidden" id="taxon_meaning:id" name="taxon_meaning:id" value="$defaults[taxon_meaning_id]" />

HTML;
    }
    $r .= get_user_profile_hidden_inputs(
      $attributes,
      $args,
      isset(data_entry_helper::$entity_to_load['taxa_taxon_list:id']), $auth['read']
    );
    return $r;
  }

  /**
   * Get the taxon name input control.
   *
   * @return string
   *   Control HTML.
   */
  protected static function get_control_taxon($auth, $args, $tabAlias, $options) {
    $r = data_entry_helper::text_input(array_merge([
      'fieldname' => 'taxon:taxon',
      'label' => lang::get('Taxon accepted name'),
      'helpText' => lang::get('Accepted name of this taxon, excluding the author.'),
      'validation' => ['required'],
    ], $options));
    return $r;
  }

  /**
   * Get the taxon attribute input control, e.g. for sensu lato and similar.
   *
   * @return string
   *   Control HTML.
   */
  protected static function get_control_attribute($auth, $args, $tabAlias, $options) {
    $r = data_entry_helper::text_input([
      'fieldname' => 'taxon:attribute',
      'label' => 'Accepted name attribute',
      'helpText' => 'E.g. sensu stricto or leave blank',
    ]);
    return $r;
  }

  /**
   * Get the taxon authority input control.
   *
   * @return string
   *   Control HTML.
   */
  protected static function get_control_authority($auth, $args, $tabAlias, $options) {
    $r = data_entry_helper::text_input(array_merge([
      'fieldname' => 'taxon:authority',
      'label' => lang::get('Taxon authority'),
      'helpText' => lang::get('Author text for the taxon (including year etc).'),
    ], $options));
    return $r;
  }

  /**
   * Get an input for the language of the accepted name.
   *
   * If an option @code is provided containing the ISO code of a supported
   * language, then a hidden input is returned with the language's ID.
   * Otherwise a drop down is returned so the user can choose.
   *
   * @return string
   *   Control HTML.
   */
  protected static function get_control_language($auth, $args, $tabAlias, $options) {
    // If the user specifies a language code, then no need for a control.
    if (!empty($options['code'])) {
      $languages = data_entry_helper::get_population_data([
        'table' => 'language',
        'extraParams' => $auth['read'] + ['iso' => $options['code']],
      ]);
      if (count($languages === 1)) {
        return data_entry_helper::hidden_text([
          'fieldname' => 'taxon:language_id',
          'default' => $languages[0]['id'],
        ]);
      }
    }
    // No code specified so need a control.
    return data_entry_helper::select(array_merge([
      'fieldname' => 'taxon:language_id',
      'label' => lang::get('Accepted name language'),
      'helpText' => lang::get('Reporting group the taxon belongs to.'),
      'table' => 'language',
      'captionField' => 'language',
      'valueField' => 'id',
      'extraParams' => $auth['read'] + ['orderby' => 'language'],
      'blankText' => '<' . lang::get('Please select') . '>',
      'validation' => ['required'],
    ], $options));
  }

  /**
   * A text area for inputting a list of common names.
   *
   * @return string
   *   Control HTML.
   */
  protected static function get_control_commonnames($auth, $args, $tabAlias, $options) {
    $r = data_entry_helper::textarea(array_merge([
      'fieldname' => 'metaFields:commonNames',
      'label' => lang::get('Common names'),
      'helpText' => lang::get(
        "Enter common names one per line. Optionally follow each name by a | character then the 3 character code for the language, e.g. 'Lobworm|eng'."
      ),
    ], $options));
    return $r;
  }

  /**
   * A text area for inputting a list of synonyms,
   *
   * @return string
   *   Control HTML.
   */
  protected static function get_control_synonyms($auth, $args, $tabAlias, $options) {
    $r = data_entry_helper::textarea(array_merge([
      'fieldname' => 'metaFields:synonyms',
      'label' => lang::get('Synonyms'),
      'helpText' => lang::get(
        "Enter common names one per line. Optionally follow each name by a | character then the authority."
      ),
    ], $options));
    return $r;
  }

  /**
   * A search box for choosing the taxon's parent.
   *
   * lternatively the parent's ID can be forced by providing a URL parameter
   * parent_id containing the parent's taxa_taxon_list_id.
   *
   * @return string
   *   Control HTML.
   */
  protected static function get_control_parent($auth, $args, $tabAlias, $options) {
    $parentId = NULL;
    if (!empty($_GET['parent_id'])) {
      $parentId = $_GET['parent_id'];
    }
    elseif (!empty(data_entry_helper::$entity_to_load['taxa_taxon_list:parent_id'])) {
      $parentId = data_entry_helper::$entity_to_load['taxa_taxon_list:parent_id'];
    }
    if (!empty($parentId)) {
      $parent = data_entry_helper::get_population_data([
        'table' => 'cache_taxa_taxon_list',
        'extraParams' => $auth['read'] + ['id' => $parentId],
      ]);
      if (count($parent)) {
        $parentName = $parent[0]['taxon'];
        if (!empty($parent[0]['authority'])) {
          $parentName .= ' ' . $parent[0]['authority'];
        }
      }
      else {
        $parentName = lang::get('-not found-');
      }
    }
    if (empty($_GET['parent_id'])) {
      $r = data_entry_helper::species_autocomplete([
        'label' => lang::get('Parent taxon'),
        'helpText' => lang::get('Search for the taxonomic parent.'),
        'fieldname' => 'taxa_taxon_list:parent_id',
        'extraParams' => $auth['read'] + ['taxon_list_id' => $args['taxon_list_id']],
        'defaultCaption' => isset($parentName) ? $parentName : NULL,
        'speciesIncludeAuthorities' => TRUE,
        'speciesIncludeBothNames' => TRUE,
        'speciesNameFilterMode' => 'preferred',
      ]);
    }
    else {
      global $indicia_templates;
      $r = str_replace('{message}', lang::get('This taxon is a child of {1}', $parentName), $indicia_templates['messageBox']);
      $r .= data_entry_helper::hidden_text([
        'fieldname' => 'taxa_taxon_list:parent_id',
        'default' => $_GET['parent_id'],
      ]);
    }
    return $r;
  }

  /**
   * A drop down for choosing the taxon's group.
   *
   * @return string
   *   Control HTML.
   */
  protected static function get_control_taxongroup($auth, $args, $tabAlias, $options) {
    $r = data_entry_helper::select(array_merge([
      'fieldname' => 'taxon:taxon_group_id',
      'label' => lang::get('Taxon group'),
      'helpText' => lang::get('Reporting group the taxon belongs to.'),
      'table' => 'taxon_group',
      'captionField' => 'title',
      'valueField' => 'id',
      'extraParams' => $auth['read'],
      'blankText' => '<' . lang::get('Please select') . '>',
      'validation' => ['required'],
    ], $options));
    return $r;
  }

  /**
   * A drop down for choosing the taxon's rank.
   *
   * @return string
   *   Control HTML.
   */
  protected static function get_control_taxonrank($auth, $args, $tabAlias, $options) {
    $r = data_entry_helper::select(array_merge([
      'fieldname' => 'taxon:taxon_rank_id',
      'label' => lang::get('Taxon rank'),
      'helpText' => lang::get('Rank of the taxon.'),
      'table' => 'taxon_rank',
      'captionField' => 'rank',
      'valueField' => 'id',
      'extraParams' => $auth['read'] + ['orderby' => 'sort_order'],
      'blankText' => '<' . lang::get('Please select') . '>',
    ], $options));
    return $r;
  }

  /**
   * A photo upload control.
   *
   * @return string
   *   Control HTML.
   */
  protected static function get_control_photos($auth, $args, $tabAlias, $options) {
    $opts = [
      'table' => 'taxon_medium',
      'readAuth' => $auth['read'],
      'resizeWidth' => 1600,
      'resizeHeight' => 1600,
    ];
    if ($tabalias) {
      $opts['tabDiv'] = $tabAlias;
    }
    foreach ($options as $key => $value) {
      // Skip attribute specific options as they break the JavaScript.
      if (strpos($key, ':') === FALSE) {
        $opts[$key] = $value;
      }
    }
    return data_entry_helper::file_box($opts);
  }

  /**
   * A text area for inputting a description of the taxon.
   *
   * @return string
   *   Control HTML.
   */
  protected static function get_control_description($auth, $args, $tabAlias, $options) {
    $r = data_entry_helper::textarea(array_merge([
      'fieldname' => 'taxon:description',
      'label' => lang::get('Description'),
      'helpText' => lang::get(
        "Description given to the taxon name record."
      ),
    ], $options));
    return $r;
  }

  /**
   * A text area for inputting a description of the taxon in the context of this list.
   *
   * @return string
   *   Control HTML.
   */
  protected static function get_control_descriptioninlist($auth, $args, $tabAlias, $options) {
    $r = data_entry_helper::textarea(array_merge([
      'fieldname' => 'taxa_taxon_list:description',
      'label' => lang::get('Description within this list'),
      'helpText' => lang::get(
        "Description given to the taxon within the context of this list."
      ),
    ], $options));
    return $r;
  }

  /**
   * An input for the external key when a taxon is sourced from another system.
   *
   * @return string
   *   Control HTML.
   */
  protected static function get_control_externalkey($auth, $args, $tabAlias, $options) {
    $r = data_entry_helper::text_input(array_merge([
      'fieldname' => 'taxon:external_key',
      'label' => lang::get('Taxon external key'),
      'helpText' => lang::get('Key given to this taxon name in the external system from which it was derived, e.g. Catalogue of Life ID or NBN Taxon Version Key.'),
    ], $options));
    return $r;
  }

  /**
   * An input for the taxon search code field.
   *
   * @return string
   *   Control HTML.
   */
  protected static function get_control_searchcode($auth, $args, $tabAlias, $options) {
    $r = data_entry_helper::text_input(array_merge([
      'fieldname' => 'taxon:search_code',
      'label' => lang::get('Taxon search code'),
      'helpText' => lang::get('Search code for the taxon name.'),
    ], $options));
    return $r;
  }

  /**
   * An input for the taxonomic sort order.
   *
   * @return string
   *   Control HTML.
   */
  protected static function get_control_sortorder($auth, $args, $tabAlias, $options) {
    $r = data_entry_helper::text_input(array_merge([
      'fieldname' => 'taxa_taxon_list:taxonomic_sort_order',
      'label' => lang::get('Taxonomic sort order'),
      'helpText' => lang::get('Numeric sort order for this name within the list.'),
      'validation' => ['integer'],
    ], $options));
    return $r;
  }

  /**
   * Returns a div for dynamic attributes.
   *
   * Returns div into which any attributes associated with the chosen taxon
   * will be inserted. If loading an existing record, then the attributes are
   * pre-loaded into the div. For new records, the parent is used to identify
   * appropriate attributes.
   *
   * @return string
   *   HTML for the div.
   *
   * @todo Changing the parent should reload the attributes dynamically.
   */
  protected static function get_control_taxondynamicattributes($auth, $args, $tabAlias, $options) {
    $ajaxUrl = hostsite_get_url('iform/ajax/dynamic_taxon');
    $language = iform_lang_iso_639_2(hostsite_get_user_field('language'));
    data_entry_helper::$javascript .= <<<JS
indiciaData.ajaxUrl='$ajaxUrl';
indiciaData.userLang = '$language';

JS;
    // Create a div to hold the controls, pre-populated only when loading
    // existing data.
    $r = '';
    $controls = '';
    if (!empty(data_entry_helper::$entity_to_load['taxa_taxon_list:id'])) {
      $idToLoad = data_entry_helper::$entity_to_load['taxa_taxon_list:id'];
    }
    elseif (!empty($_GET['parent_id'])) {
      // If the parent ID is provided for a new taxon, we can load the
      // attributes associated with the parent.
      $idToLoad = $_GET['parent_id'];
    }
    if (isset($idToLoad)) {
      $controls = self::getDynamicAttrs(
        $auth['read'],
        $args['taxon_list_id'],
        $idToLoad,
        $options,
        $language
      );
      // Other options need to pass through to AJAX loaded controls.
      $optsJson = json_encode($options);
      data_entry_helper::$javascript .= <<<JS
indiciaData.dynamicAttrOptions=$optsJson;
// Call any load hooks.
$.each(indiciaFns.hookDynamicAttrsAfterLoad, function callHook() {
  this($('.taxon-dynamic-attrs'));
});
JS;
      // Add a container div.
      $r .= "<div class=\"taxon-dynamic-attributes\">$controls</div>";
    }
    return $r;
  }

  /**
   * Retrieves a list of dynamically loaded attributes from the database.
   *
   * @return array
   *   List of attribute data.
   */
  private static function getDynamicAttrsList($readAuth, $taxonListId, $language, $ttlId) {
    $params = [
      'taxon_list_id' => $taxonListId,
      'taxa_taxon_list_id' => $ttlId,
      'master_checklist_id' => hostsite_get_config_value('iform', 'master_checklist_id', 0),
      'language' => $language,
    ];
    $r = report_helper::get_report_data([
      'dataSource' => "library/taxa_taxon_list_attributes/taxa_taxon_list_attributes_for_form",
      'readAuth' => $readAuth,
      'extraParams' => $params,
      'caching' => FALSE,
    ]);
    self::removeDuplicateAttrs($r);
    return $r;
  }

  /**
   * Retrieves a list of dynamically loaded attributes as control HTML.
   *
   * @return string
   *   Controls as an HTML string.
   */
  private static function getDynamicAttrs($readAuth, $taxonListId, $ttlId, $options, $language = NULL) {
    iform_load_helpers(['data_entry_helper', 'report_helper']);
    $attrs = self::getDynamicAttrsList($readAuth, $taxonListId, $language, $ttlId);
    return self::getDynamicAttrsOutput('tax', $readAuth, $attrs, $options, $language);
  }

  /**
   * Ajax handler to retrieve the dynamic attrs for a taxon.
   *
   * Attribute HTML is echoed to the client.
   */
  public static function ajax_dynamicattrs($website_id, $password) {
    iform_load_helpers(['data_entry_helper']);
    $readAuth = data_entry_helper::get_read_auth($website_id, $password);
    echo self::getDynamicAttrs(
      $readAuth,
      $_GET['taxon_list_id'],
      $_GET['taxa_taxon_list_id'],
      json_decode($_GET['options'], TRUE),
      $_GET['language']
    );
  }

  /**
   * Handles the construction of a submission array from a set of form values.
   *
   * @param array $values
   *   Associative array of form data values.
   * @param array $args
   *   Iform parameters.
   *
   * @return array
   *   Submission structure.
   */
  public static function get_submission(array $values, array $args) {
    $structure = [
      'model' => 'taxa_taxon_list',
      'superModels' => [
        'taxon' => [
          'fk' => 'taxon_id',
        ],
        'taxon_meaning' => [
          'fk' => 'taxon_meaning_id',
        ],
      ],
      'metaFields' => [
        'synonyms',
        'commonNames',
      ],
    ];
    $s = submission_builder::build_submission($values, $structure);
    return $s;
  }

  /**
   * Override the default submit buttons to add a delete button when editing.
   */
  protected static function getSubmitButtons($args) {
    $r = '';
    global $indicia_templates;
    $lang = [
      'Submit' => lang::get('Submit'),
      'Delete' => lang::get('Delete'),
    ];
    $r .= <<<HTML
<input type="submit" class="$indicia_templates[buttonHighlightedClass]" id="save-button" value="$lang[Submit]" />

HTML;
    if (!empty(data_entry_helper::$entity_to_load['taxa_taxon_list:id'])) {
      // Use a button here, not input, as Chrome does not post the input value.
      $r .= <<<HTML
<button type="submit" class="$indicia_templates[buttonWarningClass]" id="delete-button" name="delete-button" value="delete">
  $lang[Delete]
</button>

HTML;
      data_entry_helper::$javascript .= "$('#delete-button').click(function(e) {
        if (!confirm(\"Are you sure you want to delete this taxon?\")) {
          e.preventDefault();
          return FALSE;
        }
      });\n";
    }
    return $r;
  }

}


