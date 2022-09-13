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
 * @link https://github.com/Indicia-Team/client_helpers
 */

require_once 'includes/dynamic.php';
require_once 'includes/report.php';

/**
 * Displays the details of a single taxon. Takes an taxa_taxon_list_id in the URL and displays the following using a configurable
 * page template:
 * Species Details including custom attributes
 * An Explore Species' Records button that links to a custom URL
 * Any photos of occurrences with the same meaning as the taxon
 * A map displaying occurrences of taxa with the same meaning as the taxon.
 */
class iform_species_details extends iform_dynamic {

  private static $preferred;
  private static $synonyms = [];
  private static $commonNames = [];
  private static $taxonomy = [];
  private static $taxa_taxon_list_id;
  private static $taxon_meaning_id;

  /**
   * Disable form element wrapped around output.
   *
   * @return bool
   */
  protected static function isDataEntryForm() {
    return FALSE;
  }

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_species_details_definition() {
    return array(
      'title' => 'View details of a species',
      'category' => 'Utilities',
      'description' => 'A summary view of a species including records. Pass a parameter in the URL called taxon, ' .
        'containing a taxa_taxon_list_id which defines which species to show.',
      'recommended' => TRUE,
    );
  }

  /**
   * Return an array of parameters for the edit tab.
   * @return array The parameters for the form.
   */
  public static function get_parameters() {
    $retVal = array_merge(
      iform_map_get_map_parameters(),
      array(array(
        'name' => 'interface',
        'caption' => 'Interface Style Option',
        'description' => 'Choose the style of user interface, either dividing the form up onto separate tabs, ' .
          'wizard pages or having all controls on a single page.',
        'type' => 'select',
        'options' => array(
          'tabs' => 'Tabs',
          'wizard' => 'Wizard',
          'one_page' => 'All One Page',
        ),
        'default' => 'one_page',
        'group' => 'User Interface',
      ),
      // List of fields to hide in the Species Details section.
      array(
        'name' => 'fields',
        'caption' => 'Fields to include or exclude',
        'description' => 'List of data fields to hide, one per line.' .
            'Type in the field name as seen exactly in the Species Details section. For custom attributes you should use the system function values ' .
            'to filter instead of the caption if defined below.',
        'type' => 'textarea',
        'required' => FALSE,
        'default' => '',
        'group' => 'Fields for Species details',
      ),
      array(
        'name' => 'operator',
        'caption' => 'Include or exclude',
        'description' => "Do you want to include only the list of fields you've defined, or exclude them?",
        'type' => 'select',
        'options' => array(
          'in' => 'Include',
          'not in' => 'Exclude',
        ),
        'default' => 'not in',
        'group' => 'Fields for Species details',
      ),
      array(
        'name' => 'testagainst',
        'caption' => 'Test attributes against',
        'description' => 'For custom attributes, do you want to filter the list to show using the caption or the system function? If the latter, then '.
            'any custom attributes referred to in the fields list above should be referred to by their system function which might be one of: email, '.
            'cms_user_id, cms_username, first_name, last_name, full_name, biotope, behaviour, reproductive_condition, sex_stage, sex_stage_count, ' .
            'certainty, det_first_name, det_last_name.',
        'type' =>'select',
        'options' => array(
          'caption' => 'Caption',
          'system_function' => 'System Function',
        ),
        'default' => 'caption',
        'group' => 'Fields for Species details',
      ),
      //Allows the user to define how the page will be displayed.
      array(
        'name' => 'structure',
        'caption' => 'Form Structure',
        'description' => 'Define the structure of the form. Each component must be placed on a new line. <br/>'.
          "The following types of component can be specified. <br/>".
          "<strong>[control name]</strong> indicates a predefined control is to be added to the form with the following predefined controls available: <br/>".
              "&nbsp;&nbsp;<strong>[speciesdetails]</strong> - displays information relating to the occurrence and its sample<br/>".
              "&nbsp;&nbsp;<strong>[explore]</strong> - a button “Explore this species' records” which takes you to explore all records, filtered to the species.<br/>".
              "&nbsp;&nbsp;<strong>[photos]</strong> - photos associated with the occurrence<br/>".
              "&nbsp;&nbsp;<strong>[map]</strong> - a map that links to the spatial reference and location<br/>".
              "&nbsp;&nbsp;<strong>[occurrenceassociations]</strong> - a list of associated species, drawn from the occurrence associations data.<br/>".
          "<strong>=tab/page name=</strong> is used to specify the name of a tab or wizard page (alpha-numeric characters only). ".
          "If the page interface type is set to one page, then each tab/page name is displayed as a seperate section on the page. ".
          "Note that in one page mode, the tab/page names are not displayed on the screen.<br/>".
          "<strong>|</strong> is used to split a tab/page/section into two columns, place a [control name] on the previous line and following line to split.<br/>",
        'type' => 'textarea',
        'default' => '
=General=
[speciesdetails]
[photos]
[explore]
|
[map]',
        'group' => 'User Interface'
      ),
      array(
        'name' => 'explore_url',
        'caption' => 'Explore URL',
        'description' => 'When you click on the Explore this species\' records button you are taken to this URL. Use {rootfolder} as a replacement '.
            'token for the site\'s root URL.',
        'type' => 'string',
        'required' => FALSE,
        'default' => '',
        'group' => 'User Interface'
      ),
      array(
        'name' => 'explore_param_name',
        'caption' => 'Explore Parameter Name',
        'description' => 'Name of the parameter added to the Explore URL to pass through the taxon_meaning_id of the species being explored. '.
            'The default provided (filter-taxon_meaning_list) is correct if your report uses the standard parameters configuration.',
        'type' => 'string',
        'required' => FALSE,
        'default' => 'filter-taxon_meaning_list',
        'group' => 'User Interface'
      ),
      array(
        'name' => 'include_layer_list',
        'caption' => 'Include Legend',
        'description' => 'Should a legend be shown on the page?',
        'type' => 'boolean',
        'required' => FALSE,
        'default' => FALSE,
        'group' => 'Other Map Settings'
      ),
      array(
        'name' => 'include_layer_list_switchers',
        'caption' => 'Include Layer switchers',
        'description' => 'Should the legend include checkboxes and/or radio buttons for controlling layer visibility?',
        'type' => 'boolean',
        'required' => FALSE,
        'default' => FALSE,
        'group' => 'Other Map Settings'
      ),
      array(
        'name' => 'include_layer_list_types',
        'caption' => 'Types of layer to include in legend',
        'description' => 'Select which types of layer to include in the legend.',
        'type' => 'select',
        'options' => array(
          'base,overlay' => 'All',
          'base' => 'Base layers only',
          'overlay' => 'Overlays only'
        ),
        'default' => 'base,overlay',
        'group' => 'Other Map Settings'
      ),
      array(
        'name' => 'layer_title',
        'caption' => 'Layer Caption',
        'description' => 'Caption to display for the species distribution map layer. Can contain replacement strings {species} or {survey}.',
        'type' => 'textfield',
        'group' => 'Distribution Layer'
      ),
      array(
        'name' => 'wms_feature_type',
        'caption' => 'Feature Type',
        'description' => 'Name of the feature type (layer) exposed in GeoServer to contain the occurrences. This must expose a taxon_meaning_id and a website_id attribute. '.
            'for the filtering. The detail_occurrences view is suitable for this purpose, though make sure you include the namespace, e.g. indicia:detail_occurrences. '.
            'The list of feature type names can be viewed by clicking on the Layer Preview link in the GeoServer installation.',
        'type' => 'textfield',
        'group' => 'Distribution Layer'
      ),
      array(
        'name' => 'wms_style',
        'caption' => 'Style',
        'description' => 'Name of the SLD style file that describes how the distribution points are shown. Leave blank if not sure.',
        'type' => 'textfield',
        'required' => FALSE,
        'group' => 'Distribution Layer'
      ),
      array(
        'name' => 'cql_filter',
        'caption' => 'Distribution layer filter.',
        'description' => 'Any additional filter to apply to the loaded data, using the CQL format. For example "record_status<>\'R\'"',
        'type' => 'textarea',
        'group' => 'Distribution Layer',
        'required' => FALSE
      ),
      array(
        'name' => 'refresh_timer',
        'caption' => 'Automatic reload seconds',
        'description' => 'Set this value to the number of seconds you want to elapse before the report will be automatically reloaded, useful for '.
            'displaying live data updates at BioBlitzes. Combine this with Page to reload to define a sequence of pages that load in turn.',
        'type' => 'int',
        'required' => FALSE
      ),
      array(
        'name' => 'load_on_refresh',
        'caption' => 'Page to reload',
        'description' => 'Provide the full URL of a page to reload after the number of seconds indicated above.',
        'type' => 'string',
        'required' => FALSE
      ))
    );
    return $retVal;
  }


  /**
   * Override the getHidden function.
   * getForm in dynamic.php will now call this and return an empty array when creating a list of hidden input
   * controls for form submission as this functionality is not being used for the Species Details page.
   */
  protected static function getHidden() {
    return NULL;
  }


  /**
   * Override the getMode function.
   * getForm in dynamic.php will now call this and return an empty array when creating a mode list
   * as this functionality is not being used for the Species Details page.
   */
  protected static function getMode() {
    return [];
  }


 /**
  * Override the getAttributes function.
  * getForm in dynamic.php will now call this and return an empty array when creating an attributes list
  * as this functionality is not being used for the Species Details page.
  */
 protected static function getAttributes() {
   return [];
 }

  /**
   * Override the get_form_html function.
   * getForm in dynamic.php will now call this.
   * Vary the display of the page based on the interface type
   */
  protected static function get_form_html($args, $auth, $attributes) {
    if (empty($_GET['taxa_taxon_list_id']) && empty($_GET['taxon_meaning_id'])) {
      return 'This form requires a taxa_taxon_list_id or taxon_meaning_id parameter in the URL.';
    }

    self::get_names($auth);
    $titleName = str_replace(['<em>', '</em>'], '', self::$preferred);
    hostsite_set_page_title(lang::get('Summary details for {1}', $titleName));

    return parent::get_form_html($args, $auth, $attributes);
  }

  /**
   * Obtains details of all names for this species from the database.
   */
  protected static function get_names($auth) {
    iform_load_helpers(array('report_helper'));
    self::$preferred = lang::get('Unknown');
    //Get all the different names for the species
    $extraParams = array('sharing' => 'reporting');
    if (isset($_GET['taxa_taxon_list_id'])) {
      $extraParams['taxa_taxon_list_id'] = $_GET['taxa_taxon_list_id'];
      self::$taxa_taxon_list_id=$_GET['taxa_taxon_list_id'];
    }
    elseif (isset($_GET['taxon_meaning_id'])) {
      $extraParams['taxon_meaning_id'] = $_GET['taxon_meaning_id'];
      self::$taxon_meaning_id=$_GET['taxon_meaning_id'];
    }
    $species_details = report_helper::get_report_data(array(
      'readAuth' => $auth['read'],
      'class' => 'species-details-fields',
      'dataSource' => 'library/taxa/taxon_names',
      'useCache' => FALSE,
      'extraParams' => $extraParams,
    ));
    foreach ($species_details as $speciesData) {
      if ($speciesData['preferred'] === 't') {
        self::$preferred = $speciesData['taxon'];
        if (!isset(self::$taxon_meaning_id))
          self::$taxon_meaning_id = $speciesData['taxon_meaning_id'];
        if (!isset(self::$taxa_taxon_list_id)) {
          self::$taxa_taxon_list_id = $speciesData['id'];
        }
      }
      elseif ($speciesData['language_iso'] === 'lat') {
        self::$synonyms[] = $speciesData['taxon'];
      }
      else {
        self::$commonNames[] = $speciesData['taxon'];
      }
    }
    /* Fix a problem on the fungi-without-borders site where providing a
       taxa_taxon_list_id doesn't work (the system would try and return all records).
       This makes sense because the cache_table doesn't have a taxa_taxon_list_id.
       However, I am not sure why this is fine on other sites when the $extraParams
       are the same. At worst these two lines make the page more robust. */
    if (!empty($extraParams['taxa_taxon_list_id']))
      $extraParams['id']=$extraParams['taxa_taxon_list_id'];
    $taxon = data_entry_helper::get_population_data(array(
      'table' => 'cache_taxa_taxon_list',
      'extraParams'=>$auth['read']+$extraParams
    ));
    if (!empty($taxon[0]['kingdom_taxon']))
      self::$taxonomy[] = $taxon[0]['kingdom_taxon'];
    if (!empty($taxon[0]['order_taxon']))
      self::$taxonomy[] = $taxon[0]['order_taxon'];
    if (!empty($taxon[0]['family_taxon']))
      self::$taxonomy[] = $taxon[0]['family_taxon'];
  }


  /**
   * Draw the Species Details section of the page.
   *
   * Available options include:
   * * @includeAttributes - defaults to true. If false, then the custom
   *   attributes are not included in the block.
   *
   * @return string
   *   The output html string.
   */
  protected static function get_control_speciesdetails($auth, $args, $tabalias, $options) {
    global $indicia_templates;
    $options = array_merge([
      'includeAttributes' => TRUE,
      'outputFormatting' => FALSE,
    ], $options);
    $fields = helper_base::explode_lines($args['fields']);
    $fieldsLower = helper_base::explode_lines(strtolower($args['fields']));

    //If the user sets the option to exclude particular fields then we set to the hide flag
    //on the name types they have specified.
    if ($args['operator']=='not in') {
      $hidePreferred = FALSE;
      $hideCommon = FALSE;
      $hideSynonym = FALSE;
      $hideTaxonomy = FALSE;
      foreach ($fieldsLower as $theField) {
        if ($theField=='preferred names'|| $theField=='preferred name'|| $theField=='preferred')
          $hidePreferred = true;
        elseif ($theField=='common names' || $theField=='common name'|| $theField=='common')
          $hideCommon = true;
        elseif ($theField=='synonym names' || $theField=='synonym name'|| $theField=='synonym')
          $hideSynonym = true;
        elseif ($theField=='taxonomy')
          $hideTaxonomy = true;
      }
    }

    //If the user sets the option to only include particular fields then we set to the hide flag
    //to true unless they have specified the name type.
    if ($args['operator']=='in') {
      $hidePreferred = true;
      $hideCommon = true;
      $hideSynonym = true;
      $hideTaxonomy = true;
      foreach ($fieldsLower as $theField) {
        if ($theField=='preferred names'|| $theField=='preferred name'|| $theField=='preferred')
          $hidePreferred = FALSE;
        elseif ($theField=='common names' || $theField=='common name'|| $theField=='common')
          $hideCommon = FALSE;
        elseif ($theField=='synonym names' || $theField=='synonym name'|| $theField=='synonym')
          $hideSynonym = FALSE;
        elseif ($theField=='taxonomy')
          $hideTaxonomy = FALSE;
      }
    }
    // Draw the names on the page.
    $details_report = self::draw_names($auth['read'], $hidePreferred, $hideCommon, $hideSynonym, $hideTaxonomy);

    if ($options['includeAttributes']) {
      // Draw any custom attributes for the species added by the user.
      $attrs_report = report_helper::freeform_report(array(
        'readAuth' => $auth['read'],
        'class' => 'species-details-fields',
        'dataSource' => 'library/taxa/taxon_attributes_with_hiddens',
        'bands' => array(array('content' => str_replace(['{class}'], '', $indicia_templates['dataValue']))),
        'extraParams' => array(
          'taxa_taxon_list_id' => self::$taxa_taxon_list_id,
          // The SQL needs to take a set of the hidden fields, so this needs to
          // be converted from an array.
          'attrs' => strtolower(self::convert_array_to_set($fields)),
          'testagainst' => $args['testagainst'],
          'operator' => $args['operator'],
          'sharing' => 'reporting',
          'language' => iform_lang_iso_639_2(hostsite_get_user_field('language')),
          'output_formatting' => $options['outputFormatting'] ? 't' : 'f',
        )
      ));
    }
    $r = '';
    // Draw the species names and custom attributes.
    if (isset($details_report)) {
      $r .= $details_report;
    }
    if (isset($attrs_report)) {
      $r .= $attrs_report;
    }
    return str_replace(
      ['{id}', '{title}', '{content}'],
      ['detail-panel-speciesdetails', lang::get('Species Details'), $r],
      $indicia_templates['dataValueList']
    );
  }

  /**
   * A control for outputting a block containing a single attribute's value.
   *
   * Provides more layout control than the list of attribute values and other
   * details provided by the species details control.
   *
   * Options include:
   * * format - control formatting of the value. Default is "text". Set to
   *   "complex_attr_grid" to output tabular data created by a multi-value
   *   attribute using the complex_attr_grid control type.
   * * ifEmpty - Behaviour when no data present. Default is "hide", but can be
   *   set to text which will be output in place of the value when there is no
   *   data value.
   * * outputFormatting - default false. Set to true to enable auto-formatting
   *   of HTML links and line-feeds.
   * * taxa_taxon_list_attribute_id - required. ID of the attribute to output
   *   the value for.
   * * title - default true, which shows the attribute's caption as a block
   *   title. Set to a string to override the title, or false to hide it.
   */
  protected static function get_control_singleattribute($auth, $args, $tabalias, $options) {
    if (empty($options['taxa_taxon_list_attribute_id'])) {
      hostsite_show_message(lang::get('A taxa_taxon_list_attribute_id option is required for the single attribute control.', 'warning'));
      return;
    }
    $options = array_merge([
      'format' => 'text',
      'ifEmpty' => 'hide',
      'outputFormatting' => FALSE,
      'title' => TRUE,
    ], $options);
    $attrData = report_helper::get_report_data([
      'readAuth' => $auth['read'],
      'dataSource' => 'reports_for_prebuilt_forms/species_details/taxa_taxon_list_attribute_value',
      'extraParams' => [
        'taxa_taxon_list_id' => self::$taxa_taxon_list_id,
        'taxa_taxon_list_attribute_id' => $options['taxa_taxon_list_attribute_id'],
        'sharing' => $args['sharing'],
        'language' => iform_lang_iso_639_2(hostsite_get_user_field('language')),
        'output_formatting' => $options['outputFormatting'] && $options['format'] === 'text' ? 't' : 'f',
      ],
    ]);
    if (count($attrData) === 0) {
      hostsite_show_message("Attribute ID $options[taxa_taxon_list_attribute_id] not found", 'warning');
      return '';
    }
    $r = '';
    $valueInfo = $attrData[0];
    if ($options['title'] === TRUE) {
      $r .= "<h3>$valueInfo[caption]</h3>";
    }
    elseif (is_string($options['title'])) {
      $r .= '<h3>' . lang::get($options['title']) . '</h3>';
    }
    if ($valueInfo['value'] === NULL || $valueInfo['value'] === '') {
      $r .= '<p>' . lang::get($options['ifEmpty']) . '</p>';
    }
    else {
      switch ($options['format']) {
        case 'text':
          $r .= "<p>$valueInfo[value]</p>";
          break;

        case 'complex_attr_grid':
          $valueRows = explode('; ', $valueInfo['value']);
          $decoded = json_decode($valueRows[0], TRUE);
          $r .= '<table class="table"><thead>';
          $r .= '<tr><th>' . implode('</th><th>', array_keys($decoded)) . '</th></tr>';
          $r .= '</thead><tbody>';
          foreach ($valueRows as $valueRow) {
            $decoded = json_decode($valueRow, TRUE);
            if ($options['outputFormatting']) {
              foreach ($decoded as &$value) {
                $value = str_replace("\n", '<br/>', $value);
                $value = preg_replace('/(http[^\s]*)/', '<a href="$1">$1</a>', $value);
              }
            }
            $r .= '<tr><td>' . implode('</td><td>', $decoded) . '</td></tr>';
          }
          $r .= '</tbody>';
          $r .= '</table>';
          break;
      }
    }

    return $r;
  }


  /**
   * Draw the names in the Species Details section of the page.
   *
   * @return string
   *   The output html.
   */
  protected static function draw_names($auth, $hidePreferred, $hideCommon, $hideSynonym, $hideTaxonomy) {
    global $indicia_templates;
    $r = '';
    if (!$hidePreferred) {
      $r .= str_replace(
        array('{caption}', '{value}', '{class}'),
        array(lang::get('Species name'), self::$preferred, ''),
        $indicia_templates['dataValue']);
    }
    if ($hideCommon == FALSE && !empty(self::$commonNames)) {
      $label = (count(self::$commonNames) === 1) ? 'Common name' : 'Common names';
      $r .= str_replace(
        array('{caption}', '{value}', '{class}'),
        array(lang::get($label), implode(', ', self::$commonNames), ''),
        $indicia_templates['dataValue']);
    }
    if ($hideSynonym == FALSE && !empty(self::$synonyms)) {
      $label = (count(self::$synonyms) === 1) ? 'Synonym' : 'Synonyms';
      $r .= str_replace(
        array('{caption}', '{value}', '{class}'),
        array(lang::get($label), implode(', ', self::$synonyms), ''),
        $indicia_templates['dataValue']
      );
    }
    if ($hideTaxonomy == FALSE && !empty(self::$taxonomy)) {
      $r .= str_replace(
        array('{caption}', '{value}', '{class}'),
        array(lang::get('Taxonomy'), implode(' :: ', self::$taxonomy), ''),
        $indicia_templates['dataValue']
      );
    }
    return $r;
  }

  /**
   * Undocumented function
   *
   * Available options include:
   * * @includeCaptions - set to false to exclude attribute captions from the
   *   grouped data.
   *   @headingsToInclude - CSV list of heading names that are to be included. To include a particular sub-category only, supply the
   *   heading name and then a slash and then the sub-category name e.g.  the following includes just the hoglets sub-category
   *   and the entire rabbits section @headingsToInclude=Hedgehogs/Hoglets,Rabbits
   *   @headingsToExclude - Same as @headingsToInclude but items are exluded instead (any items that appear in both headingsToInclude and headingsToExclude will be excluded).
   *
   * @deprecated in 8.5.0.
   *
   * @return string
   *   Html for the description.
   */
  protected static function get_control_attributedescription($auth, $args, $tabalias, $options) {
    global $indicia_templates;
    $options = array_merge([
      'includeCaptions' => TRUE,
    ], $options);
    $sharing = empty($args['sharing']) ? 'reporting' : $args['sharing'];
    if (!empty($options['headingsToInclude'])) {
      $headingsToInclude = explode(',', $options['headingsToInclude']);
    } else {
      $headingsToInclude = [];
    }
    if (!empty($options['headingsToExclude'])) {
      $headingsToExclude = explode(',', $options['headingsToExclude']);
    } else {
      $headingsToExclude = [];
    }

    $mainHeadingsToInclude = $subHeadingsToInclude = $mainHeadingsToExclude = $subHeadingsToExclude = [];
    // Cycle through all the headings we want to include
    foreach ($headingsToInclude as $headingSubCatToInclude) {
      // See if a sub-category has been specified
      if (strpos($headingSubCatToInclude, '/') !== false) {
        // If a sub-category has been specified, then get the main heading and sub-category and save them
        // (we still need the main heading as we need to display it, even if we are only going to be showing one of the sub-categories)
        $headingSubCatSplit=explode('/',$headingSubCatToInclude);
        $mainHeadingsToInclude[]=$headingSubCatSplit[0];
        $subHeadingsToInclude[]=$headingSubCatToInclude;
      } else {
        // If we are including the whole section, then indicate this explicitely to the system using the word unlimited
        $mainHeadingsToInclude[]=$headingSubCatToInclude;
        $subHeadingsToInclude[]=$headingSubCatToInclude.'/unlimited';
      }
    }

    // Do similar for excluding, however in this case there is one difference, if we are exluding a sub-category, we don't automatically
    // exclude the main heading as it is needed for the other sub-categories
    foreach ($headingsToExclude as $headingSubCatToExclude) {
      if (strpos($headingSubCatToExclude, '/') !== false) {
        $headingSubCatSplit=explode('/',$headingSubCatToExclude);
        $subHeadingsToExclude[]=$headingSubCatToExclude;
      } else {
        $mainHeadingsToExclude[]=$headingSubCatToExclude;
        $subHeadingsToExclude[]=$headingSubCatToExclude.'/unlimited';
      }
    }

    $args['param_presets'] = '';
    $args['param_defaults'] = '';
    $params = [
      'taxa_taxon_list_id' => empty($_GET['taxa_taxon_list_id']) ? '' : $_GET['taxa_taxon_list_id'],
      'taxon_meaning_id' => empty($_GET['taxon_meaning_id']) ? '' : $_GET['taxon_meaning_id'],
      'include_captions' => $options['includeCaptions'] ? '1' : '0',
      'language' => iform_lang_iso_639_2(hostsite_get_user_field('language')),
    ];
    $reportOptions = array_merge(
      iform_report_get_report_options($args, $auth['read']),
      [
        'sharing' => $sharing,
        'readAuth' => $auth['read'],
        'dataSource' => 'reports_for_prebuilt_forms/species_details/species_attr_description',
        'extraParams' => $params,
        'wantCount' => '0',
      ]
    );
    // Ensure supplied extraParams are merged, not overwritten.
    if (!empty($options['extraParams'])) {
      $options['extraParams'] = array_merge($reportOptions['extraParams'], $options['extraParams']);
    }
    $data = report_helper::get_report_data($reportOptions);
    $r = '';
    $currentHeading = '';
    $currentHeadingContent = '';
    foreach ($data as $idx => $row) {
        if ($row['category'] !== $currentHeading) {
          if (!empty($currentHeadingContent)) {
            // Only display a section if
            // - The user hasn't specified any options regarding which sections should be displayed
            // - The user has specified to include the section, and not specified to exclude it
            // - The user hasn't specified any options regarding what to include, and it isn't in the list of items to exclude.
            if ($currentHeading === ''
                || (in_array($currentHeading, $mainHeadingsToInclude) && !in_array($currentHeading, $mainHeadingsToExclude))
                || (empty($mainHeadingsToInclude) && !in_array($currentHeading, $mainHeadingsToExclude))
                || (empty($mainHeadingsToInclude) && empty($mainHeadingsToExclude))) {
              $r .= str_replace(
                ['{id}', '{title}', '{content}'],
                [
                  "detail-panel-description-$idx",
                  $currentHeading,
                  $currentHeadingContent,
                ],
                $indicia_templates['dataValueList']
              );
            }
            $currentHeadingContent = '';
          }
          $currentHeading = $row['category'];
        }
        $currentHeadingAndSubCat=$currentHeading.'/'.$row['subcategory'];
        // Only display a sub-category if
        // - The sub-category is in the list of sub-categories to display and not in the list of sub-categories to exclude.
        // - The user has not specified any options regarding sub-categories to include and the sub-category is not in the list to exclude
        // - The user has not specified any options regarding sub-categories to include or exclude
        // - The user has specified to include all sub-categories under a particular heading and the sub-category is not listed for exclusion
        if  ($row['subcategory'] === '' ||
            (in_array($currentHeadingAndSubCat, $subHeadingsToInclude) && !in_array($currentHeadingAndSubCat, $subHeadingsToExclude)) ||
            (empty($subHeadingsToInclude) && !in_array($currentHeadingAndSubCat, $subHeadingsToExclude)) ||
            (empty($subHeadingsToInclude) && empty($subHeadingsToExclude)) ||
            (in_array($currentHeading.'/unlimited', $subHeadingsToInclude) && !in_array($currentHeading.'/unlimited', $subHeadingsToExclude))
        ) {
          $currentHeadingContent .= str_replace(
            array('{caption}', '{value}'),
            array($row['subcategory'], $row['values']),
            $indicia_templates['dataValue']
          );
        }
   	  }
      if (!empty($currentHeadingContent)) {
        // See comments above for explanation of IF statement
        if ($currentHeading === ''
            || (in_array($currentHeading, $mainHeadingsToInclude) && !in_array($currentHeading, $mainHeadingsToExclude))
            || (empty($mainHeadingsToInclude) && !in_array($currentHeading, $mainHeadingsToExclude))
            || (empty($mainHeadingsToInclude) && empty($mainHeadingsToExclude))) {
          $r .= str_replace(
            ['{id}', '{title}', '{content}'],
            [
              "detail-panel-description-$idx",
              $currentHeading,
              $currentHeadingContent,
            ],
            $indicia_templates['dataValueList']
          );
        }
      }
    return $r;
  }

  /**
   * Draw Photos section of the page.
   *
   * @return string
   *   The output report grid.
   */
  protected static function get_control_photos($auth, $args, $tabalias, $options) {
    iform_load_helpers(['report_helper']);
    data_entry_helper::add_resource('fancybox');
    $options = array_merge([
      'itemsPerPage' => 20,
      'imageSize' => 'thumb',
      'class' => 'media-gallery',
    ], $options);

    // Use this report to return the photos.
    $reportName = 'reports_for_prebuilt_forms/species_details/occurrences_thumbnails';
    $media = report_helper::get_report_data([
      'readAuth' => $auth['read'],
      'dataSource' => $reportName,
      'itemsPerPage' => $options['itemsPerPage'],
      'extraParams' => [
        'taxon_meaning_list' => self::$taxon_meaning_id,
        'limit' => $options['itemsPerPage'],
        'wantCount' => 0,
      ],
    ]);
    $r = '<div class="detail-panel" id="detail-panel-photos"><h3>' . lang::get('Photos and media') . '</h3>';
    $r .= '<div class="' . $options['class'] . '"><ul>';

    if (count($media) === 0) {
      $r .= '<p>No photos or media files available</p>';
    }
    else {
      foreach ($media as $medium) {
        $r .= iform_report_get_gallery_item('occurrence', $medium, $options['imageSize']);
      }
    }
    $r .= '</ul></div></div>';
    return $r;
  }

  /*
   * Gets a comma seperated list of taxa associated with the species by using the taxon_associations table
   */
  protected static function get_control_taxonassociations($auth, $args, $tabalias, $options) {
    $params = [
      'taxa_taxon_list_id' => empty($_GET['taxa_taxon_list_id']) ? '' : $_GET['taxa_taxon_list_id'],
      'taxon_meaning_id' => empty($_GET['taxon_meaning_id']) ? '' : $_GET['taxon_meaning_id'],
    ];
    $reportOptions = array_merge(
      iform_report_get_report_options($args, $auth['read']),
      [
        'readAuth' => $auth['read'],
        'dataSource' => 'library/taxon_associations/get_taxon_associations_as_string',
        'extraParams' => $params,
        'wantCount' => '0',
      ]
    );
    $data = report_helper::get_report_data($reportOptions);
	if (!empty($data[0]['associated_taxa'])) {
      $r = '<div class="detail-panel" id="detail-panel-taxonassociations"><h3>'.lang::get('Hosts').'</h3>';
      $r .= '<div><i>'.$data[0]['associated_taxa'].'</i></div>';
      $r .= '</div>';
    } else {
      $r = '';
    }
    return $r;
  }

  protected static function get_control_occurrenceassociations($auth, $args, $tabalias, $options) {
    iform_load_helpers(['report_helper']);
    $currentUrl = report_helper::get_reload_link_parts();
    // amend currentUrl path if we have drupal dirty URLs so javascript will work properly
    if (isset($currentUrl['params']['q']) && strpos($currentUrl['path'], '?') === FALSE) {
      $currentUrl['path'] .= '?q='.$currentUrl['params']['q'].'&taxon_meaning_id=';
    } else {
      $currentUrl['path'] .= '&taxon_meaning_id=';
    }
    $options = array_merge([
      'dataSource' => 'library/occurrence_associations/filterable_associated_species_list_cloud',
      'itemsPerPage' => 20,
      'class' => 'cloud',
      'header' => '<ul>',
      'footer' => '</ul>',
      'bands' => array(array('content' => '<li style="font-size: {font_size}px">' .
          "<a href=\"$currentUrl[path]{taxon_meaning_id}\">{species}<a/></li>")),
      'emptyText' => '<p>No association species information available</p>',
      'extraParams' => [],
    ], $options);
    $extraParams = array_merge(
        $options['extraParams'],
        array('taxon_meaning_list'=> self::$taxon_meaning_id)
    );
    return '<div class="detail-panel" id="detail-panel-occurrenceassociations"><h3>'.lang::get('Associated species').'</h3>' .
    report_helper::freeform_report(array(
      'readAuth' => $auth['read'],
      'dataSource'=> $options['dataSource'],
      'itemsPerPage' => $options['itemsPerPage'],
      'class' => $options['class'],
      'header'=> $options['header'],
      'footer'=> $options['footer'],
      'bands'=> $options['bands'],
      'emptyText' => $options['emptyText'],
      'mode' => 'report',
      'autoParamsForm' => FALSE,
      'extraParams' => $extraParams
    )).'</div>';
  }

  /**
   * Draw Map section of the page.
   *
   * @return string
   *   The output map panel.
   */
  protected static function get_control_map($auth, $args, $tabalias, $options) {
    // Draw a distribution map by calling Indicia report when Geoserver isn't
    // available
    if (isset($options['noGeoserver']) && $options['noGeoserver'] === true) {
      return self::mapWithoutGeoserver($auth, $args, $tabalias, $options);
    }
    iform_load_helpers(array('map_helper', 'data_entry_helper'));
    global $user;
    // setup the map options
    $options = iform_map_get_map_options($args, $auth['read']);
    if ($tabalias)
      $options['tabDiv'] = $tabalias;
    $olOptions = iform_map_get_ol_options($args);
    $url = map_helper::$geoserver_url.'wms';
    // Get the style if there is one selected
    $style = $args["wms_style"] ? ", styles: '".$args["wms_style"]."'" : '';
    map_helper::$onload_javascript .= "\n    var filter='website_id=".$args['website_id']."';";

    $layerTitle = str_replace('{species}', self::get_best_name(), $args['layer_title']);
    map_helper::$onload_javascript .= "\n    filter += ' AND taxon_meaning_id=".self::$taxon_meaning_id."';\n";

    if ($args['cql_filter'])
      map_helper::$onload_javascript .= "\n    filter += ' AND(".str_replace("'","\'",$args['cql_filter']).")';\n";

    $layerTitle = str_replace("'","\'",$layerTitle);

    map_helper::$onload_javascript .= "\n    var distLayer = new OpenLayers.Layer.WMS(
      '".$layerTitle."',
      '$url',
      {layers: '".$args["wms_feature_type"]."', transparent: true, CQL_FILTER: filter $style},
      {isBaseLayer: false, sphericalMercator: true, singleTile: true}
    );\n";
    $options['layers'][]='distLayer';

    // This is not a map used for input
    $options['editLayer'] = FALSE;
    // if in Drupal, and IForm proxy is installed, then use this path as OpenLayers proxy
    if (function_exists('hostsite_module_exists') && hostsite_module_exists('iform_proxy')) {
      $options['proxy'] = data_entry_helper::getRootFolder(true) .
          hostsite_get_config_value('iform', 'proxy_path', 'proxy') . '&url=';
    }

    // output a legend
    if (isset($args['include_layer_list_types']))
      $layerTypes = explode(',', $args['include_layer_list_types']);
    else
      $layerTypes = array('base', 'overlay');
    $r = '<div class="detail-panel" id="detail-panel-map"><h3>'.lang::get('Map').'</h3>';
    //Legend options set by the user
    if (!isset($args['include_layer_list']) || $args['include_layer_list'])
      $r .= map_helper::layer_list(array(
        'includeSwitchers' => isset($args['include_layer_list_switchers']) ? $args['include_layer_list_switchers'] : true,
        'includeHiddenLayers' => true,
        'layerTypes' => $layerTypes
      ));

    $r .= map_helper::map_panel($options, $olOptions);
    $r .= '</div>';

    // Set up a page refresh for dynamic update of the map at set intervals
    if ($args['refresh_timer']!==0 && is_numeric($args['refresh_timer'])) { // is_int prevents injection
      if (isset($args['load_on_refresh']) && !empty($args['load_on_refresh']))
        map_helper::$javascript .= "setTimeout('window.location=\"".$args['load_on_refresh']."\";', ".$args['refresh_timer']."*1000 );\n";
      else
        map_helper::$javascript .= "setTimeout('window.location.reload( false );', ".$args['refresh_timer']."*1000 );\n";
    }
    return $r;
  }

  /**
   * Draw a distribution map by calling Indicia report when Geoserver isn't available
   *
   * @return string
   *   The output map panel.
   */
  protected static function mapWithoutGeoserver($auth, $args, $tabalias, $options) {
    iform_load_helpers(array('map_helper', 'report_helper'));
    if (isset($options['hoverShowsDetails'])) {
      $options['hoverShowsDetails'] = TRUE;
    }
    // $_GET data for standard params can override displayed location.
    $locationIDToLoad = @$_GET['filter-indexed_location_list']
      ?: @$_GET['filter-indexed_location_id']
      ?: @$_GET['filter-location_list']
      ?: @$_GET['filter-location_id'];
    if (!empty($locationIDToLoad) && preg_match('/^\d+$/', $locationIDToLoad)) {
      $args['display_user_profile_location'] = FALSE;
      $args['location_boundary_id'] = $locationIDToLoad;
    }
    // aAlow us to call iform_report_get_report_options to get a default report setup, then override report_name
    $args['report_name'] = '';
    $sharing = empty($args['sharing']) ? 'reporting' : $args['sharing'];
    $params = array(
      'taxa_taxon_list_id' => empty($_GET['taxa_taxon_list_id']) ? '' : $_GET['taxa_taxon_list_id'],
      'taxon_meaning_id' => empty($_GET['taxon_meaning_id']) ? '' : $_GET['taxon_meaning_id'],
      'sharing' => 'reporting',
      'reportGroup' => 'dynamic',
      'autoParamsForm' => FALSE,
      'sharing' => $sharing,
      'rememberParamsReportGroup' => 'dynamic',
      'clickableLayersOutputMode' => 'report',
      'rowId' => 'occurrence_id',
      'ajax' => TRUE,
    );
    $args['param_presets'] = '';
    $args['param_defaults'] = '';
    $reportOptions = array_merge(
      iform_report_get_report_options($args, $auth['read']),
      array(
        'reportGroup' => 'dynamic',
        'autoParamsForm' => FALSE,
        'sharing' => $sharing,
        'readAuth' => $auth['read'],
        'dataSource' => 'reports_for_prebuilt_forms/species_details/species_record_data',
        'extraParams' => $params,
      )
    );
    // Ensure supplied extraParams are merged, not overwritten.
    if (!empty($options['extraParams'])) {
      $options['extraParams'] = array_merge($reportOptions['extraParams'], $options['extraParams']);
    }
    $reportOptions = array_merge($reportOptions, $options);
    $r = report_helper::report_map($reportOptions);
    $options = array_merge(
      iform_map_get_map_options($args, $auth['read']),
      array(
        'featureIdField' => 'occurrence_id',
        'clickForSpatialRef' => FALSE,
        'reportGroup' => 'explore',
        'toolbarDiv' => 'top',
      ),
      $options
    );
    $olOptions = iform_map_get_ol_options($args);
    if ($tabalias) {
      $options['tabDiv'] = $tabalias;
    }
    $r .= map_helper::map_panel($options, $olOptions);
    return $r;
  }

  /**
   * Retrieves the best name to display for a species.
   */
  protected static function get_best_name() {
    return (count(self::$commonNames)>0) ? self::$commonNames[0] : self::$preferred;
  }

  /**
   * Draw the explore button on the page.
   *
   * @return string
   *   The output HTML string.
   */
  protected static function get_control_explore($auth, $args) {
    if (!empty($args['explore_url']) && !empty($args['explore_param_name'])) {
      $url = $args['explore_url'];
      if (strcasecmp(substr($url, 0, 12), '{rootfolder}')!==0 && strcasecmp(substr($url, 0, 4), 'http')!==0)
          $url='{rootFolder}'.$url;
      $url = str_replace('{rootFolder}', data_entry_helper::getRootFolder(true), $url);
      $url .= (strpos($url, '?')===FALSE) ? '?' : '&';
      $url .= $args['explore_param_name'] . '=' . self::$taxon_meaning_id;
      $r='<a class="button" href="'.$url.'">' . lang::get('Explore records of {1}', self::get_best_name()) . '</a>';
    }
    else
      throw new exception('The page has been setup to use an explore records button, but an "Explore URL" or "Explore Parameter Name" has not been specified.');
    return $r;
  }

  /*
   * Control gets the description of a taxon and displays it on the screen.
   */
  protected static function get_control_speciesnotes($auth, $args) {
    //We can't return the notes for a specific taxon unless we have an taxa_taxon_list_id, as the meaning could apply
    //to several taxa. In this case ignore the notes control.
    if (empty(self::$taxa_taxon_list_id))
      return '';
    $reportResult = report_helper::get_report_data(array(
      'readAuth' => $auth['read'],
      'dataSource' => 'library/taxa/species_notes_and_images',
      'useCache' => FALSE,
      'extraParams'=>array(
        'taxa_taxon_list_id'=>self::$taxa_taxon_list_id,
        'taxon_meaning_id'=>self::$taxon_meaning_id,
      )
    ));
    if (!empty($reportResult[0]['the_text']))
      return '<div class="detail-panel" id="detail-panel-speciesnotes"><h3>'.
          lang::get('Species Notes').'</h3><p>'.$reportResult[0]['the_text'].'</p></div>';
  }

  /*
   * Control returns all the images associated with a particular taxon meaning in the taxon_images table.
   * These are the the general dictionary images of a species as opposed to the photos control which returns photos
   * associated with occurrences of this species.
   */
  protected static function get_control_speciesphotos($auth, $args, $tabalias, $options) {
    iform_load_helpers(array('report_helper'));
    data_entry_helper::add_resource('fancybox');
    $options = array_merge([
      'imageSize' => 'thumb',
      'itemsPerPage' => 6,
      'galleryColCount' => 2,
    ], $options);
    global $user;
    global $indicia_templates;
    // Use this report to return the photos.
    $reportName = 'library/taxa/species_notes_and_images';
    $reportResults = report_helper::report_grid(array(
      'readAuth' => $auth['read'],
      'dataSource' => $reportName,
      'itemsPerPage' => $options['itemsPerPage'],
      'columns' => array(
        array(
          'fieldname' => 'the_text',
          'template' => str_replace('{imageSize}', $options['imageSize'], $indicia_templates['speciesDetailsThumbnail']),
        )
      ),
      'mode' => 'report',
      'autoParamsForm' => FALSE,
      'includeAllColumns' => FALSE,
      'headers' => FALSE,
      'galleryColCount' => $options['galleryColCount'],
      'extraParams' => array(
        'taxa_taxon_list_id' => self::$taxa_taxon_list_id,
        'taxon_meaning_id' => self::$taxon_meaning_id,
      )
    ));
    return '<div class="detail-panel" id="detail-panel-speciesphotos"><h3>' . lang::get('Photos and media') . '</h3>' .
        $reportResults . '</div>';
  }

  /**
   * When a form version is upgraded introducing new parameters, old forms will not get the defaults for the
   * parameters unless the Edit and Save button is clicked. So, apply some defaults to keep those old forms
   * working.
   */
  protected static function getArgDefaults($args) {
    if (!isset($args['interface']) || empty($args['interface']))
      $args['interface'] = 'one_page';

    if (!isset($args['hide_fields']) || empty($args['hide_fields']))
      $args['hide_fields'] = '';

    if (!isset($args['structure']) || empty($args['structure'])) {
      $args['structure'] =
'=General=
[speciesdetails]
[photos]
[explore]
|
[map]';
    }
    return $args;
  }

  /**
   * Used to convert an array of attributes to a string formatted like a set,
   * this is then used by the species_data_attributes_with_hiddens report to return
   * custom attributes which aren't in the hidden attributes list.
   *
   * @return string
   *   The set of hidden custom attributes.
   */
  protected static function convert_array_to_set($theArray) {
    return "'".implode("','", str_replace("'", "''", $theArray))."'";
  }
}