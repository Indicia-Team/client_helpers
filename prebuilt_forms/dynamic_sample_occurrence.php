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
 * @link https://github.com/indicia-team/client_helpers/
 */

/**
 * Prebuilt Indicia data entry form.
 * NB has Drupal specific code. Relies on presence of IForm Proxy.
 */

require_once 'includes/dynamic.php';
require_once 'includes/groups.php';

/**
 * Store remembered field settings, since these need to be accessed from a hook function which runs outside the class.
 *
 * @var string
 */
global $remembered;

class iform_dynamic_sample_occurrence extends iform_dynamic {

  // The ids we are loading if editing existing data.
  protected static $loadedSampleId;
  protected static $loadedOccurrenceId;
  protected static $group = false;

  protected static $availableForGroups = false;
  protected static $limitToGroupId = 0;

  /**
   * The list of attributes loaded for occurrences. Keep a class level variable, so that we can track the ones we have already
   * emitted into the form globally.
   *
   * @var array
   */
  protected static $occAttrs;

  /**
   * Return the form metadata.
   *
   * @return string
   *   The definition of the form.
   */
  public static function get_dynamic_sample_occurrence_definition() {
    return array(
      'title' => 'Enter single record or list of records (customisable)',
      'category' => 'Data entry forms',
      'helpLink' => 'http://indicia-docs.readthedocs.org/en/latest/site-building/iform/prebuilt-forms/dynamic-sample-occurrence.html',
      'description' => 'A data entry form for records (taxon occurrences). Can be used for entry of a single record, ' .
        'ticking species off a checklist or entering user selected species into a list. Highly customisable.',
      'supportsGroups' => TRUE,
      'recommended' => TRUE
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
    $formStructureDescription = <<<TXT
<h3>Form structure overview</h3>
Define the structure of the form. Each component goes on a new line and is nested inside the
previous component where appropriate. The following types of component can be specified. <br/>
<strong>=tab/block name=</strong> is used to specify the name of a tab or wizard page. For All One
Page mode, you must still specify a single block name in order to provide a block wrapper for the
form. (Alpha-numeric characters only)<br/>
<strong>=*=</strong> indicates a placeholder for putting any custom attribute tabs not defined in
this form structure. <br/>
<strong>[control name]</strong> indicates a predefined control is to be added to the
form.<br/>
<h3>Controls available</h3>
<ul>
  <li><strong>[species]</strong> - a species grid or input control. You can change any of the
  control options for an individual custom attribute control in a grid by putting
  @control|option=value on the subsequent line(s). For example, if a control is for occAttr:4 then
  you can set it's default value by specifying @occAttr:4|default=7 on the line after the
  [species]<br/>
  If you want to specify a custom template for a grid's species label cell, then override the
  taxon_label template. If you have multiple grids on the form, you can override each one
  individually by setting the @taxonLabelTemplate for each grid to the name of a template that
  you've added to the\$indicia_templates global array. If in single species entry mode and using a
  select box for data input, you can put a taxon group select above the species input select by
  setting the option @taxonGroupSelect=true. Control the label and helptext for this control using
  the options @taxonGroupSelectLabel and @taxonGroupSelectHelpText.</li>
  <li><strong>[species map]</strong> - a species grid or input control: this is the same as the
  species control, but the sample is broken down into subsamples, each of which has its own
  location picked from the map. Only the part of the species grid which is being added to or
  modified at the time is displayed. This control should be placed after the map control, with
  which it integrates. Species recording must be set to a List (grid mode) rather than single
  entry. This control does not currently support mixed spatial reference systems, only the first
  specified will be used. You do not need a [spatial reference] control on the page when using a
  [species map] control.</li>
  <li><strong>[species map summary]</strong> - a read only grid showing a summary of the data
  entered using the species map control.</li>
  <li><strong>[species attributes]</strong> - any custom attributes for the occurrence, if not
  using the grid. Also includes a file upload box and sensitivity input control if relevant. The
  attrubutes @resizeWidth and @resizeHeight can specified on subsequent lines, otherwise they
  default to 1600. Set @useDescriptionAsHelpText=true to load the descriptions of attribute
  definitions on the server into the help text displayed with the control. Note that this control
  provides a quick way to output all occurrence custom attributes plus photo and sensitivity input
  controls and outputs all attributes irrespective of the form block or tab. For finer control of
  the output, see the [occAttr:n], [photos] and [sensitivity] controls.</li>
  <li><strong>[species dynamic attributes]</strong> - any custom attributes that have been
  configured to only show for certain branches of the taxonomic hierarchy. Set @types to an array
  containing either sample or occurrence to limit the block of attributes to those associated at
  the sample or occurrence level only.</li>
  <li><strong>[date]</strong> - date picker control. A sample must always have a date.</li>
  <li><strong>[map]</strong> - a map that links to the spatial reference and location
  select/autocomplete controls</li>
  <li><strong>[spatial reference]</strong> - spatial reference input text box. A sample must always
  have a spatial reference.</li>
  <li><strong>[location name]</strong> - a text box to enter a place name.</li>
  <li><strong>[location autocomplete]</strong> - an autocomplete control for picking a stored
  location. A spatial reference is still required.</li>
  <li><strong>[location url param]</strong> - a set of hidden inputs that insert the location ID
  read from a URL parameter called location_id into the form. Uses the location's centroid as the
  sample map reference.</li>
  <li><strong>[location select]</strong> - a select control for picking a stored location. A
  spatial reference is still required.</li>
  <li><strong>[location map]</strong> - combines location select, map and spatial reference
  controls for recording only at stored locations.</li>
  <li><strong>[occurrence comment]</strong> - a text box for occurrence level comment.
  Alternatively use the [species attributes] control to output all input controls for the species
  automatically.</li>
  <li><strong>[photos]</strong> - use when in single record entry mode to provide a control for
  uploading occurrence photos. Alternatively use the [species attributes] control to output all
  input controls for the species automatically. The [photos] control overrides the setting
  <strong>Occurrence Images</strong>.</li>
  <li><strong>[place search]</strong> - zooms the map to the entered location.</li>
  <li><strong>[recorder names]</strong> - a text box for names. The logged-in user's id is always
  stored with the record.</li>
  <li><strong>[record status]</strong> - allow recorder to mark record as in progress or
  complete.</li>
  <li><strong>[review input]</strong>. - a panel showing all the currently input form values which
  can be placed on the last tab of the form to allow the submission to be reviewed</li>
  <li><strong>[sample comment]</strong> - a text box for sample level comment. (Each occurrence may
  also have a comment.)</li>
  <li><strong>[sample photo]</strong>. - a photo upload for sample level images. (Each occurrence
  may also have photos.)</li>
  <li><strong>[sensitivity]</strong> - outputs a control for setting record sensitivity and the
  public viewing precision. This control will also output
  any other occurrence custom attributes which are on an outer block called Sensitivity. Any such
  attributes will then be disabled when the record is not sensitive, so they can be used to capture
  information that only relates to sensitive records.</li>
  <li><strong>[zero abundance]</strong>. - use when in single record entry mode to provide a
  checkbox for specifying negative records.</li>
  <li><strong>[smpAttr:<i>n</i>]</strong></li> is used to insert a particular custom sample
  attribute identified by its ID number</li>
  <li><strong>[occAttr:<i>n</i>]</strong> is used to insert a particular custom occurrence
  attribute identified by its ID number when inputting single records at a time. Or use [species
  attributes] to output the whole lot.</li>
</ul>
<h3>Options for controls</h3>
<strong>@option=value</strong> on the line(s) following any control allows you to override one of
the options passed to the control. The options available depend on the control. For example
@label=Abundance would set the untranslated label of a control to Abundance. Where the option value
is an array, use valid JSON to encode the value. For example an array of strings could be passed as
@occAttrClasses=["class1","class2"] or a keyed array as
@extraParams={"preferred":"true","orderby":"term"}. Other common options include helpText (set to a
piece of additional text to display alongside the control) and class (to add css classes to the
control such as control-width-3). Specify @permision=... to create a Drupal permission which you
can use to control the visibility of this specific control.<br/>
<strong>[*]</strong> is used to make a placeholder for putting any custom attributes that should be
inserted into the current tab. When this option is used, you can change any of the control options
for an individual custom attribute control by putting @control|option=value on the subsequent
line(s). For example, if a control is for smpAttr:4 then you can update it's label by specifying
@smpAttr:4|label=New Label on the line after the [*]. You can also set an option for all the
controls output by the [*] block by specifying @option=value as for non-custom controls, e.g. set
@label=My label to define the same label for all controls in this custom attribute block. You can
define the value for a control using the standard replacement tokens for user data, namely
{user_id}, {username}, {email} and {profile_*}; replace * in the latter to construct an existing
profile field name. For example you could set the default value of an email input using
@smpAttr:n|default={email} where n is the attribute ID.<br/>
For any attribute controls you can:
<ul>
  <li>Set the default value to load from a parameter provided in the URL query string by setting
  @urlParam to the name of the parameter in the URL which will contain the default value.</li>
  <li>Set @useDescriptionAsHelpText=true to load the descriptions of attribute definnitions on the
  server into the help text displayed with the control.</li>
  <li>* Set @attrImageSize = 'thumb', 'med' or 'original' to display the image defined for the
  attribute on the server alongside the caption.</li>
</ul>
<strong>?help text?</strong> is used to define help text to add to the tab, e.g. ?Enter the name of
the site.?<br/>
<strong>|</strong> is used insert a split so that controls before the split go into a left column
and controls after the split go into a right column.<br/>
<strong>all else</strong> is copied to the output html so you can add structure for styling.
TXT;
    $retVal = array_merge(
        parent::get_parameters(),
      array(
        array(
          'name' => 'never_load_parent_sample',
          'caption' => 'Never load parent sample',
          'description' => 'When editing a record in a parent/child sample hierarchy, tick this box to prevent loading ' .
              'the parent sample into the form instead of the child sample.',
          'type' => 'boolean',
          'default' => FALSE,
          'required' => FALSE
        ),
        array(
          'name' => 'emailShow',
          'caption' => 'Show email field even if logged in',
          'description' => 'If the survey requests an email address, it is sent implicitly for logged in users. Check this box to show it explicitly.',
          'type' => 'boolean',
          'default' => FALSE,
          'required' => FALSE,
          'group' => 'User Interface'
        ),
        array(
          'name' => 'nameShow',
          'caption' => 'Show user profile fields even if logged in',
          'description' => 'If the survey requests first name and last name or any field which matches a field in the users profile, these are hidden. '.
              'Check this box to show these fields. Always show these fields if they are required at the warehouse unless the profile module is enabled, '.
              '<em>copy field values from user profile</em> is selected and the fields are required in the profile.',
          'type' => 'boolean',
          'default' => false,
          'required' => false,
          'group' => 'User Interface'
        ),
        array(
          'name' => 'copyFromProfile',
          'caption' => 'Copy field values from user profile',
          'description' => 'Copy any matching fields from the user\'s profile into the fields with matching names in the sample data. '
            . 'This works for fields defined in the Drupal Profile module (version 6) or Fields module (version 7) which must be '
            . 'enabled to use this feature. Applies whether fields are shown or not.',
          'type' => 'boolean',
          'default' => false,
          'required' => false,
          'group' => 'User Interface',
          // Note that we can't test Drupal module availability whilst loading this form for a new iform, using Ajax. So
          // in this case we show the control even though it is not usable (the help text explains the module requirement).
          'visible' => !function_exists('hostsite_module_exists') || hostsite_module_exists('field')
        ),
        array(
          'name' => 'structure',
          'caption' => 'Form structure',
          'description' => $formStructureDescription,
          'type' => 'textarea',
          'default' => "=Species=\r\n" .
              "?Please enter the species you saw and any other information about them.?\r\n" .
              "[species]\r\n" .
              "@resizeWidth=1500\r\n" .
              "@resizeHeight=1500\r\n" .
              "[species attributes]\r\n" .
              "[*]\r\n" .
              "=Place=\r\n" .
              "?Please provide the spatial reference of the record. You can enter the reference directly, or search for a place then click on the map to set it.?\r\n" .
              "[spatial reference]\r\n" .
              "[place search]\r\n" .
              "[map]\r\n" .
              "[*]\r\n" .
              "=Other Information=\r\n" .
              "?Please provide the following additional information.?\r\n" .
              "[date]\r\n" .
              "[sample comment]\r\n" .
              "[*]\r\n" .
              "=*=",
          'group' => 'User Interface'
        ),
        array(
          'name' => 'edit_taxa_names',
          'caption' => 'Include option to edit entered taxa',
          'description' => 'Include an icon to allow taxa to be edited after they has been entered into the species grid.',
          'type' => 'checkbox',
          'default'=>false,
          'required'=>false,
          'group' => 'User Interface',
        ),
        array(
          'name' => 'include_species_grid_link_page',
          'caption' => 'Include icon to link to another page from a species grid row?',
          'description' => 'Include an icon which links to a page from each row on the species grid e.g. to display details about a taxon. Use the "Species grid page link URL" option to provide a URL.',
          'type' => 'checkbox',
          'default'=>false,
          'required'=>false,
          'group' => 'User Interface',
        ),
        array(
          'name' => 'species_grid_page_link_url',
          'caption' => 'Species grid page link URL',
          'description' => 'URL path of the page being linked to by the "Include icon to link to another page from a species grid row" option.
                          The URL path should not include server details or a preceeding slash
                          e.g. node/216. Note that the current version of the software will only supply a taxa_taxon_list_id value to this page as the parameter.',
          'type' => 'textfield',
          'default' => false,
          'required' => false,
          'group' => 'User Interface'
        ),
        array(
          'name' => 'species_grid_page_link_parameter',
          'caption' => 'Species grid page link parameter',
          'description' => 'Parameter name used by the page being linked to by the "Species grid page link URL" option. Note in the current version of the software the value
                          of the parameter will always be a taxa_taxon_list_id.',
          'type' => 'textfield',
          'default' => false,
          'required' => false,
          'group' => 'User Interface'
        ),
        array(
          'name' => 'species_grid_page_link_tooltip',
          'caption' => 'Species grid page link tooltip',
          'description' => 'Mouse pointer tooltip for the "Include icon to link to another page from a species grid row" option.',
          'type' => 'textfield',
          'default' => false,
          'required' => false,
          'group' => 'User Interface'
        ),
        array(
          'name' => 'grid_report',
          'caption' => 'Grid Report',
          'description' => 'Name of the report to use to populate the grid for selecting existing data from. The report must return a sample_id '.
              'field or occurrence_id field for linking to the data entry form. As a starting point, try ' .
              'reports_for_prebuilt_forms/dynamic_sample_occurrence_samples for a list of samples.',
          'type' => 'string',
          'group' => 'User Interface',
          'default' => 'reports_for_prebuilt_forms/dynamic_sample_occurrence_samples'
        ),
        array(
          'name' => 'verification_panel',
          'caption' => 'Include verification precheck button',
          'description' => 'Include a "Precheck my records" button which allows the user to request an automated '.
              'verification check to be run against their records before submission, enabling them to provide '.
              'additional information for any records which are likely to be contentious.',
          'type' => 'checkbox',
          'group' => 'User Interface',
          'default' => false,
          'required' => false
        ),
        array(
          'name' => 'users_manage_own_sites',
          'caption' => 'Users can save sites',
          'description' => 'Allow users to save named sites for recall when they add records in future. Users '.
              'are only able to use their own sites. To use this option, make sure you include a '.
              '[location autocomplete] control in the User Interface - Form Structure setting. Use @searchUpdatesSref=true '.
              'on the next line in the form structure to specify that the grid reference for the site should be automatically filled '.
              'in after a site has been selected. You can also add @useLocationName=true on a line after the location autocomplete '.
              'to force any unmatched location names to be stored as a free-text location name against the sample.',
          'type' => 'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Locations'
        ),
        array(
          'name' => 'multiple_occurrence_mode',
          'caption' => 'Allow a single ad-hoc record or a list of records',
          'description' => 'Method of data entry, one occurrence at a time, via a grid allowing '.
              'entry of multiple occurrences at the same place and date, or allow the user to choose.',
          'type' => 'select',
          'options' => array(
            'single' => 'Only allow entry of one occurrence at a time',
            'multi' => 'Only allow entry of multiple occurrences using a grid',
            'either' => 'Allow the user to choose single or multiple occurrence data entry.'
          ),
          'default' => 'multi',
          'group' => 'Species'
        ),
        array(
          'fieldname' => 'list_id',
          'label' => 'Species List ',
          'helpText' => 'The species list that species can be selected from. This list is pre-populated '.
              'into the grid when doing grid based data entry, or provides the list which a species '.
              'can be picked from when doing single occurrence data entry.',
          'type' => 'select',
          'table' => 'taxon_list',
          'valueField' => 'id',
          'captionField' => 'title',
          'required'=>false,
          'group' => 'Species',
          'siteSpecific'=>true
        ),
        array(
          'fieldname' => 'extra_list_id',
          'label' => 'Extra Species List',
          'helpText' => 'The second species list that species can be selected from. This list is available for additional '.
              'taxa being added to the grid when doing grid based data entry. When using the single record input mode, if you '.
              'provide a list here as well as in the Species List option above, then both will be available for selection from. '.
              'You might like to use this when you need to augment the species available from a main species list with a few '.
              'additional specialist taxa for example.',
          'type' => 'select',
          'table' => 'taxon_list',
          'valueField' => 'id',
          'captionField' => 'title',
          'required'=>false,
          'group' => 'Species',
          'siteSpecific'=>true
        ),
        array(
          'fieldname' => 'copy_species_row_data_to_new_rows',
          'label' => 'New species grid rows use previous row\'s data',
          'helpText' => 'Use this option to enable newly added rows on the species grid to have their default data ' .
              'taken from the previous row rather than being initially blank. Use the "Columns to include" option ' .
              'to specify which columns you wish to include. The data is copied automatically when the new row is ' .
              'created and also when edits are made to the previous row.',
          'type' => 'checkbox',
          'default'=>false,
          'required'=>false,
          'group' => 'Species',
        ),
        array(
          'name' => 'previous_row_columns_to_include',
          'caption' => 'Columns to include',
          'description' => 'Comma seperated list of columns you wish to include when using the "New species grid rows use previous row\'s data" option. ' .
                'Non-case or white space sensitive. Any unrecognised columns are ignored and the images column cannot be copied.',
          'type' => 'textarea',
          'required'=>false,
          'group' => 'Species'
        ),
        array(
          'fieldname' => 'user_controls_taxon_filter',
          'label' => 'User can filter the Extra Species List',
          'helpText' => 'Tick this box to enable a filter button in the species column title which allows the user to control ' .
              'which species groups are available for selection when adding new species to the grid, e.g. the user can filter ' .
              'to allow selection from just one species group.',
          'type' => 'checkbox',
          'default' => false,
          'required' => false,
          'group' => 'Species'
        ),
        array(
          'name' => 'species_ctrl',
          'caption' => 'Single Species Selection Control Type',
          'description' => 'The type of control that will be available to select a single species.',
          'type' => 'select',
          'options' => array(
            'autocomplete' => 'Autocomplete',
            'select' => 'Select',
            'hierarchical_select' => 'Hierarchical select',
            'listbox' => 'List box',
            'radio_group' => 'Radio group',
            'treeview' => 'Treeview',
            'tree_browser' => 'Tree browser'
          ),
          'default' => 'autocomplete',
          'group' => 'Species'
        ),
        array(
          'name' => 'sub_species_column',
          'caption' => 'Include sub-species in a separate column?',
          'description' => 'If checked and doing grid based data entry letting the recorder add species they choose to '.
            'the bottom of the grid, sub-species will be displayed in a separate column so the recorder picks the species '.
            'first then the subspecies. The species checklist must be configured so that species are parents of the subspecies. '.
            'This setting also forces the Cache Lookups option therefore it requires the Cache Builder module to be installed '.
            'on the Indicia warehouse.',
          'type' => 'boolean',
          'default' => false,
          'required' => false,
          'group' => 'Species'
        ),
        array(
          'name' => 'species_include_authorities',
          'caption' => 'Include species authors in the search string',
          'description' => 'Should species authors be shown in the search results when searching for a species name?',
          'type' => 'boolean',
          'required' => false,
          'group' => 'Species'
        ),
        array(
          'name' => 'species_include_both_names',
          'caption' => 'Include both names in species controls and added rows',
          'description' => 'When using a species grid with the ability to add new rows, the autocomplete control by default shows just the searched taxon name in the drop down. '.
              'Set this to include both the latin and common names, with the searched one first. This also controls the label when adding a new taxon row into the grid.',
          'type' => 'boolean',
          'required' => false,
          'group' => 'Species'
        ),
        array(
          'name' => 'species_include_taxon_group',
          'caption' => 'Include taxon group name in species autocomplete and added rows',
          'description' => 'When using a species grid with the ability to add new rows, the autocomplete control by default shows just the searched taxon name in the drop down. '.
              'Set this to include the taxon group title.  This also controls the label when adding a new taxon row into the grid.',
          'type' => 'boolean',
          'required' => false,
          'group' => 'Species'
        ),
        array(
          'name' => 'species_include_id_diff',
          'caption' => 'Include identification_difficulty icons in species autocomplete and added rows',
          'description' => 'Use data cleaner identification difficulty rules to generate icons indicating when '.
              'hard to ID taxa have been selected.',
          'type' => 'boolean',
          'required' => false,
          'default'=>true,
          'group' => 'Species'
        ),
        array(
          'name' => 'occurrence_comment',
          'caption' => 'Occurrence Comment',
          'description' => 'Should an input box be present for a comment against each occurrence?',
          'type' => 'boolean',
          'required' => false,
          'default'=>false,
          'group' => 'Species'
        ),
        array(
          'name' => 'occurrence_sensitivity',
          'caption' => 'Occurrence Sensitivity',
          'description' => 'Should a control be present for sensitivity of each record?  This applies when using grid entry mode or when using the [species attributes] control '.
              'to output all the occurrence related input controls automatically. The [sensitivity] control outputs a sensitivity input control independently of this setting.',
          'type' => 'boolean',
          'required' => false,
          'default'=>false,
          'group' => 'Species'
        ),
        array(
          'name' => 'occurrence_images',
          'caption' => 'Occurrence Images',
          'description' => 'Should occurrences allow images to be uploaded? This applies when using grid entry mode or when using the [species attributes] control '.
              'to output all the occurrence related input controls automatically. The [photos] control outputs a photos input control independently of this setting.',
          'type' => 'boolean',
          'required' => false,
          'default'=>false,
          'group' => 'Species'
        ),
        array(
          'name' => 'col_widths',
          'caption' => 'Grid Column Widths',
          'description' => 'Provide percentage column widths for each species checklist grid column as a comma separated list. To leave a column at its default with, put a blank '.
              'entry in the list. E.g. "25,,20" would set the first column to 25% width and the 3rd column to 20%, leaving the other columns as they are.',
          'type' => 'string',
          'group' => 'Species',
          'required' => false
        ),
        array(
          'name' => 'taxon_filter_field',
          'caption' => 'Field used to filter taxa',
          'description' => 'If you want to allow recording for just part of the selected list(s), then select which field you will '.
              'use to specify the filter by.',
          'type' => 'select',
          'options' => array(
            'preferred_name' => 'Preferred name of the taxa',
            'taxon_meaning_id' => 'Taxon Meaning ID',
            'taxa_taxon_list_id' => 'Taxa Taxon List ID',
            'taxon_group' => 'Taxon group title',
            'external_key' => 'Taxon external key',
            'id' => 'Taxon ID'
          ),
          'required'=>false,
          'group' => 'Species'
        ),
        array(
          'name' => 'use_url_taxon_parameter',
          'caption' => 'Use URL taxon parameter',
          'description' => 'Use a URL parameter called taxon to get the filter? Case sensitive. Uses the "Field used to filter taxa" setting to control '.
            'what is being filtered against, e.g. &taxon=Passer+domesticus,Turdus+merula',
          'type' => 'boolean',
          'required' => false,
          'default'=>false,
          'group' => 'Species'
        ),
        array(
          'name' => 'taxon_filter',
          'caption' => 'Taxon filter items',
          'description' => 'Taxa can be filtered by entering values into this box. '.
              'Enter one value per line. E.g. enter a list of taxon group titles if you are filtering by taxon group. '.
              'If you provide a single taxon preferred name, taxon meaning ID or external key in this box and you set the <strong>Allow a single '.
              'ad-hoc record or a list of records</strong> setting to "Only allow entry of one occurrence at a time" then the form is set up for '.
              'recording just this single species. Therefore there will be no species picker control or input grid, and the form will always operate '.
              'in the single record, non-grid mode. You may like to include information about what is being recorded in the body text for the page or by using '.
              'the <strong>Include a message stating which species you are recording in single species mode?</strong> checkbox to automatically add a message to the screen. '.
              'You may also want to configure the User Interface section of the form\'s Form Structure to move the [species] and [species] controls '.
              'to a different tab and remove the =species= tab, especially if there are no other occurrence attributes on the form.'.
              'The \'Use URL taxon parameter\' option can be used to override the filters specified here.',
          'type' => 'textarea',
          'required'=>false,
          'group' => 'Species'
        ),
        array(
          'name' => 'single_species_message',
          'caption' => 'Include a message stating which species you are recording in single species mode?',
          'description' => 'Message which displays the species you are recording against in single species mode. When selected, this will automatically be displayed where applicable.',
          'type' => 'boolean',
          'required' => false,
          'default'=>false,
          'group' => 'Species'
        ),
        array(
          'name' => 'species_names_filter',
          'caption' => 'Species Names Filter',
          'description' => 'Select the filter to apply to the species names which are available to choose from.',
          'type' => 'select',
          'options' => array(
            'all' => 'All names are available',
            'language' => 'Only allow selection of species using common names in the user\'s language',
            'preferred' => 'Only allow selection of species using names which are flagged as preferred',
            'excludeSynonyms' => 'Allow common names or preferred latin names'
          ),
          'default' => 'all',
          'group' => 'Species'
        ),
        array(
          'name' => 'link_species_popups',
          'caption' => 'Create popups for certain species',
          'description' => 'You can mark some blocks of the form to only be shown as a popup when a certain species is entered into the species grid. For each popup block, '.
              'put the species name on a newline, followed by | then the outer block name, followed by | then the inner block name if relevant. For example, '.
              '"Lasius niger|Additional info|Colony info" pops up the controls from the block Additional Info > Colony info when a species is entered with this '.
              'name. For the species name, specify the preferred name from list.',
          'type' => 'textarea',
          'required' => FALSE,
          'group' => 'Species'
        ),
        array(
          'name' => 'sample_method_id',
          'caption' => 'Sample Method',
          'type' => 'select',
          'table' => 'termlists_term',
          'captionField' => 'term',
          'valueField' => 'id',
          'extraParams' => array('termlist_external_key' => 'indicia:sample_methods'),
          'required' => false,
          'helpText' => 'The sample method that will be used for created samples.'
        ),
        array(
          'name' => 'defaults',
          'caption' => 'Default Values',
          'description' => 'Supply default values for each field as required. On each line, enter fieldname=value. For custom attributes, '.
              'the fieldname is the untranslated caption. For other fields, it is the model and fieldname, e.g. occurrence.record_status. '.
              'For date fields, use today to dynamically default to today\'s date. NOTE, currently only supports occurrence:record_status and '.
              'sample:date but will be extended in future.',
          'type' => 'textarea',
          'default' => 'occurrence:record_status=C'
        ),
        array(
          'name' => 'remembered',
          'caption' => 'Remembered Fields',
          'description' => 'Supply a list of field names that should be remembered in a cookie, saving re-inputting them if they are likely to repeat. '.
              'For greater flexibility use the @lockable=true option on each control instead.',
          'type' => 'textarea',
          'required'=>false
        ),
        array(
          'name' => 'edit_permission',
          'caption' => 'Permission required for editing other people\'s data',
          'description' => 'Set to the name of a permission which is required in order to be able to edit other people\'s data.',
          'type' => 'text_input',
          'required'=>false,
          'default' => 'indicia data admin'
        ),
        array(
          'name' => 'ro_permission',
          'caption' => 'Permission required for viewing other people\'s data',
          'description' => 'Set to the name of a permission which is required in order to be able to view other people\'s data (not edit).',
          'type' => 'text_input',
          'required'=>false,
          'default' => 'indicia data view'
        )
      )
    );
    return $retVal;
  }

  /**
   * Declare the list of permissions we've got set up to pass to the CMS' permissions code.
   * @param int $nid Node ID, not used
   * @param array $args Form parameters array, used to extract the defined permissions.
   * @return array List of distinct permissions.
   */
  public static function get_perms($nid, $args) {
    $perms = array();
    if (!empty($args['edit_permission']))
      $perms[] = $args['edit_permission'];
    if (!empty($args['ro_permission']))
      $perms[] = $args['ro_permission'];
    $perms += parent::get_perms($nid, $args);
    return $perms;
  }

  /**
   * Override get_form_html so we can store the remembered argument in a global, to make
   * it available to a hook function which exists outside the form.
   */
  protected static function get_form_html($args, $auth, $attributes) {
    group_authorise_form($args, $auth['read']);
    // We always want an autocomplete formatter function for species lookups. The form implementation can
    // specify its own if required
    if (method_exists(self::$called_class, 'build_grid_autocomplete_function'))
      call_user_func(array(self::$called_class, 'build_grid_autocomplete_function'), $args);
    else {
      $opts = array(
        'speciesIncludeAuthorities' => isset($args['species_include_authorities']) ?
            $args['species_include_authorities'] : false,
        'speciesIncludeBothNames' => $args['species_include_both_names'],
        'speciesIncludeTaxonGroup' => $args['species_include_taxon_group'],
        'speciesIncludeIdDiff' => $args['species_include_id_diff']
      );
      data_entry_helper::build_species_autocomplete_item_function($opts);
    }
    global $remembered;
    $remembered = isset($args['remembered']) ? $args['remembered'] : '';
    if (empty(data_entry_helper::$entity_to_load['sample:group_id']) && !empty($_GET['group_id']))
      data_entry_helper::$entity_to_load['sample:group_id']=$_GET['group_id'];
    if (!empty(data_entry_helper::$entity_to_load['sample:group_id'])) {
      self::$group = data_entry_helper::get_population_data(array(
          'table' => 'group',
          'extraParams' => $auth['read']+array('view' => 'detail','id'=>data_entry_helper::$entity_to_load['sample:group_id'])
      ));
      self::$group=self::$group[0];
      $filterDef = json_decode(self::$group['filter_definition']);
      if (empty($args['location_boundary_id'])) {
        // Does the group filter define a site or boundary for the recording? If so and the form
        // is not locked to a boundary, we need to show it and limit the map extent.
        // This code grabs the first available value from the list of fields that could hold the value
        $locationIDToLoad = @$filterDef->indexed_location_list
          ?: @$filterDef->indexed_location_id
          ?: @$filterDef->location_list
          ?: @$filterDef->location_id;

        if ($locationIDToLoad) {
          $response = data_entry_helper::get_population_data(array(
            'table' => 'location',
            'extraParams' => $auth['read'] + array(
                'query' => json_encode(array('in' => array('id' => explode(',', $locationIDToLoad)))),
                'view' => 'detail'
              )
          ));
          $geoms = array();
          foreach ($response as $loc) {
            $geoms[] = $loc['boundary_geom'] ? $loc['boundary_geom'] : $loc['centroid_geom'];
          }
          $geom = count($geoms) > 1 ? 'GEOMETRYCOLLECTION(' . implode(',', $geoms) . ')' : $geoms[0];
          $layerName = count($geoms) > 1 ? lang::get('Boundaries') : lang::get('Boundary of {1}', $response[0]['name']);
          iform_map_zoom_to_geom($geom, lang::get('{1} for the {2} group', $layerName, self::$group['title']));
          self::hide_other_boundaries($args);
        }
        elseif (!empty($filterDef->searchArea)) {
          iform_map_zoom_to_geom($filterDef->searchArea, lang::get('Recording area for the {1} group', self::$group['title']));
          self::hide_other_boundaries($args);
        }
      }
      if (!empty($filterDef->taxon_group_names) && !empty((array) $filterDef->taxon_group_names)) {
        $args['taxon_filter'] = implode("\n", array_values((array) $filterDef->taxon_group_names));
        $args['taxon_filter_field'] = 'taxon_group';
      }
      // @todo Consider other types of species filter, e.g. family or species list?
    }
    $r = parent::get_form_html($args, $auth, $attributes);
    // Are there checked records being edited? If so, warn.
    if (data_entry_helper::$checkedRecordsCount) {
      if (data_entry_helper::$checkedRecordsCount === 1 && data_entry_helper::$uncheckedRecordsCount === 0) {
        $msg = 'You are editing a record which has already been checked. Please only make changes if they are ' .
          'important, otherwise the record will need to be rechecked.';
      }
      elseif (data_entry_helper::$checkedRecordsCount > 1 && data_entry_helper::$uncheckedRecordsCount === 0) {
        $msg = 'You are editing a sample containing records which have already been checked. Please only make ' .
        'changes if they are important, otherwise the records will need to be rechecked.';
      }
      else {
        $msg = 'You are editing a sample containing some records which have already been checked. Changing details ' .
          'relating to the visit will require all records to be rechecked but changing details relating to a single ' .
          'species or other record attributes will only affect that record. Please only make changes if they are ' .
          'important, otherwise the records will need to be rechecked.';
      }
      hostsite_show_message(lang::get($msg));
    }
    return $r;
  }

  /**
   * If zooming to a group site, we don't want to display the user's own profile locality or other map setting location.
   */
  private static function hide_other_boundaries(&$args) {
    $args['remember_pos']=false;
    unset($args['location_boundary_id']);
    $args['display_user_profile_location']=false;
  }

  /**
   * Determine whether to show a grid of existing records or a form for either adding a new record, editing an existing one,
   * or creating a new record from an existing one.
   * @param array $args iform parameters.
   * @param object $nid node being shown.
   * @return int The mode [MODE_GRID|MODE_NEW|MODE_EXISTING|MODE_CLONE].
   */
  protected static function getMode($args, $nid) {
    // Default to mode MODE_GRID or MODE_NEW depending on no_grid parameter
    $mode = (isset($args['no_grid']) && $args['no_grid']) ? self::MODE_NEW : self::MODE_GRID;
    self::$loadedSampleId = null;
    self::$loadedOccurrenceId = null;
    self::$availableForGroups = $args['available_for_groups'];
    self::$limitToGroupId = isset($args['limit_to_group_id']) ? $args['limit_to_group_id'] : 0;
    if ($_POST && array_key_exists('website_id', $_POST) && !is_null(data_entry_helper::$entity_to_load)) {
      // errors with new sample or entity populated with post, so display this data.
      $mode = self::MODE_EXISTING;
    } // else valid save, so go back to gridview: default mode 0
    if (!empty($_GET['sample_id']) && $_GET['sample_id']!='{sample_id}') {
      $mode = self::MODE_EXISTING;
      self::$loadedSampleId = $_GET['sample_id'];
    }
    if (!empty($_GET['occurrence_id']) && $_GET['occurrence_id']!='{occurrence_id}'){
      $mode = self::MODE_EXISTING;
      self::$loadedOccurrenceId = $_GET['occurrence_id'];
    }
    if ($mode != self::MODE_EXISTING && array_key_exists('new', $_GET)){
      $mode = self::MODE_NEW;
      data_entry_helper::$entity_to_load = array();
    }
    if ($mode == self::MODE_EXISTING && array_key_exists('new', $_GET)){
      $mode = self::MODE_CLONE;
    }
    return $mode;
  }

  /**
   * Construct a grid of existing records.
   * @param array $args iform parameters.
   * @param object $nid ID of node being shown.
   * @param array $auth authentication tokens for accessing the warehouse.
   * @return string HTML for grid.
   */
  protected static function getGrid($args, $nid, $auth) {
    $r = '';
    $attributes = self::getAttributesForEntity('sample', $args, $auth['read'], false);

    $tabs = array('#sampleList'=>lang::get('LANG_Main_Samples_Tab'));

    // An option for derived classes to add in extra tabs
    if (method_exists(self::$called_class, 'getExtraGridModeTabs')) {
      $extraTabs = call_user_func(
          array(self::$called_class, 'getExtraGridModeTabs'), false, $auth['read'], $args, $attributes);
      if(is_array($extraTabs))
        $tabs = $tabs + $extraTabs;
    }

    // Only actually need to show tabs if there is more than one
    if(count($tabs) > 1){
      $active = isset($_GET['page']) ? '#setLocations' : '#sampleList'; // ??? setLocations
      data_entry_helper::enable_tabs(array('divId' => 'controls','active' => $active));
      $r .= '<div id="controls"><div id="temp"></div>';
      $r .= data_entry_helper::tab_header(array('tabs' => $tabs));
    }

    // Here is where we get the table of samples
    $r .= "<div id=\"sampleList\">" .call_user_func(
        array(self::$called_class, 'getSampleListGrid'), $args, $nid, $auth, $attributes)."</div>";

    // Add content to extra tabs that derived classes may have added
    if (method_exists(self::$called_class, 'getExtraGridModeTabs')) {
      $r .= call_user_func(array(self::$called_class, 'getExtraGridModeTabs'), true, $auth['read'], $args, $attributes);
    }

    // Close tabs div if present
    if(count($tabs) > 1){
      $r .= "</div>";
    }
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
      // Multi-record mode using a checklist grid. We really just need to know
      // the sample ID.
      if (self::$loadedOccurrenceId && !self::$loadedSampleId) {
        $response = data_entry_helper::get_population_data(array(
            'table' => 'occurrence',
            'extraParams' => $auth['read'] + array('id' => self::$loadedOccurrenceId, 'view' => 'detail'),
            'caching' => FALSE,
            'sharing' => 'editing'
        ));
        if (count($response) !== 0) {
          // We found an occurrence so use it to detect the sample.
          self::$loadedSampleId = $response[0]['sample_id'];
          if ($args['taxon_filter']) {
            // If a taxon filter is set, check to see if the specified occurrence would
            // pass the filtering. If not, then turn filtering off for this occurrence
            // otherwise it cannot be edited.
            $filtered_taxa = data_entry_helper::get_population_data(array(
              'table' => 'taxa_search',
              'extraParams' => $auth['read'] + array(
                  'taxa_taxon_list_id'=>$response[0]['taxa_taxon_list_id'],
                  $args['taxon_filter_field'] => json_encode($args['taxon_filter'])
                )
            ));
            if (count($filtered_taxa) == 0) {
              $args['taxon_filter']='';
            }
          }
        }
      }
    }
    else {
      // Single record entry mode. We want to load the occurrence entity and to know the sample ID.
      if (self::$loadedOccurrenceId) {
        data_entry_helper::load_existing_record(
            $auth['read'], 'occurrence', self::$loadedOccurrenceId, 'detail', 'editing', TRUE);
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
            'caching' => FALSE,
            'sharing' => 'editing'
          ));
          if (count($response) > 1) {
            data_entry_helper::$entity_to_load['gridmode'] = TRUE;
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
          'caching' => FALSE,
          'sharing' => 'editing'
        ));
        self::$loadedOccurrenceId = $response[0]['id'];
        data_entry_helper::load_existing_record_from(
            $response[0], $auth['read'], 'occurrence', self::$loadedOccurrenceId, 'detail', 'editing', TRUE);
      }
      self::$loadedSampleId = data_entry_helper::$entity_to_load['occurrence:sample_id'];
    }

    // Load the sample record.
    if (self::$loadedSampleId) {
      data_entry_helper::load_existing_record($auth['read'], 'sample', self::$loadedSampleId, 'detail', 'editing', TRUE);
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
    $args['survey_id'] = data_entry_helper::$entity_to_load['sample:survey_id'];
    $args['sample_method_id'] = data_entry_helper::$entity_to_load['sample:sample_method_id'];
    // Enforce that people only access their own data, unless explicitly
    // have permissions.
    $editor = !empty($args['edit_permission']) && hostsite_user_has_permission($args['edit_permission']);
    if ($editor) {
      return;
    }
    $readOnly = !empty($args['ro_permission']) && hostsite_user_has_permission($args['ro_permission']);
    if (function_exists('hostsite_get_user_field') &&
        data_entry_helper::$entity_to_load['sample:created_by_id'] != hostsite_get_user_field('indicia_user_id')) {
      if ($readOnly) {
        self::$mode = self::MODE_EXISTING_RO;
      }
      else {
        throw new exception(lang::get('Attempt to access a record you did not create'));
      }
    }
  }

  /**
   * Load the attributes for the sample defined by $entity_to_load
   * @param array $args Form configuration arguments
   * @param array $auth Authorisation tokens
   * @return array List of attribute definitions
   */
  protected static function getAttributes($args, $auth) {
    return self::getAttributesForEntity('sample', $args, $auth['read'],
        isset(data_entry_helper::$entity_to_load['sample:id']) ? data_entry_helper::$entity_to_load['sample:id'] : '');
  }

  /**
   * Load the attributes for the sample defined by a supplied Id.
   * @param string $entity Associated entity name, either sample or occurrence.
   * @param array $args Form configuration arguments
   * @param array $readAuth Authorisation tokens
   * @param integer $id ID of the sample or occurrence record being reloaded if relevant
   * @return array List of attribute definitions
   */
  protected static function getAttributesForEntity($entity, $args, $readAuth, $id=null) {
    $prefix = $entity==='sample' ? 'smp' : 'occ';
    $attrOpts = array(
      'valuetable'=>"{$entity}_attribute_value",
      'attrtable'=>"{$entity}_attribute",
      'key'=>"{$entity}_id",
      'fieldprefix'=>"{$prefix}Attr",
      'extraParams' => $readAuth,
      'survey_id' => $args['survey_id']
    );
    if (!empty($id))
      $attrOpts['id'] = $id;
    // select only the custom attributes that are for this sample method or all sample methods, if this
    // form is for a specific sample method.
    if ($entity==='sample' && !empty($args['sample_method_id']))
      $attrOpts['sample_method_id']=$args['sample_method_id'];
    return data_entry_helper::getAttributes($attrOpts, false);
  }

  /* Overrides function in class iform_dynamic.
   *
   * This function removes ID information from the entity_to_load, fooling the
   * system in to building a form for a new record with default values from the entity_to_load.
   * This feels like it could be easily broken by changes to how the form is built,
   * particularly the species checklist.
   * I would have preferred to modify the completed html but I perceived a problem with
   * multi-value inputs and knowing whether to replace e.g. smpAttr:123:12345 with
   * smpAttr:123 or smpAttr:123[]
   *
   * At the time of calling, the entity_to_load contains the sample and the
   * $attributes array contains the sample attributes. No occurrences are loaded.
   * This function calls preload_species_checklist_occurrences which loads the
   * occurrence and occurrence attribute information in to the entity_to_load. Having
   * modified the occurrence information in entity_to_load the species checklist must
   * be called with option['useLoadedExistingRecords'] = true so that the modifications
   * are not overwritten
   */
  protected static function cloneEntity($args, $auth, &$attributes) {
    // First modify the sample attribute information in the $attributes array.
    // Set the sample attribute fieldnames as for a new record
    foreach($attributes as $attributeKey => $attributeValue){
      if ($attributeValue['multi_value'] == 't') {
         // Set the attribute fieldname to the attribute id plus brackets for multi-value attributes
        $attributes[$attributeKey]['fieldname'] = $attributeValue['id'] . '[]';
        foreach($attributeValue['default'] as $defaultKey => $defaultValue) {
          //Fixed a problem with a checkbox_group that the client reported as not saving after cloning. The problem is the value field was also including the fieldname which was
          //preventing save, so I have removed the fieldname from the defaults list here. I don't have time to test all the scenarios for this, so to be safe I have just made it
          //so the fix is only applied to the checkbox_group, if we find there are problems with other types of multi-value control then this check can be removed, but as we only
          //have one reported issue and I can't test all the scenarios I have left in this checkbox_group check to avoid breaking existing code that I can't test.
          if (isset($attributeValue['control_type']) && $attributeValue['control_type']==='checkbox_group')
            unset($attributes[$attributeKey]['default'][$defaultKey]['fieldname']);
          // Set the fieldname in the defaults array to the attribute id plus brackets as well
          else
           $attributes[$attributeKey]['default'][$defaultKey]['fieldname'] = $attributeValue['id'] . '[]';
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
    data_entry_helper::preload_species_checklist_occurrences(data_entry_helper::$entity_to_load['sample:id'],
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
    foreach($keysToDelete as $key) {
      unset(data_entry_helper::$entity_to_load[$key]);
    }
    data_entry_helper::$entity_to_load = array_merge(data_entry_helper::$entity_to_load, $elementsToAdd);

    // Unset the sample and occurrence id from entitiy_to_load as for a new record.
    unset(data_entry_helper::$entity_to_load['sample:id']);
    unset(data_entry_helper::$entity_to_load['occurrence:id']);
  }

  protected static function getFormHiddenInputs($args, $auth, &$attributes) {
    // Get authorisation tokens to update the Warehouse, plus any other hidden data.
    $r = <<<HTML
$auth[write]
<input type="hidden" id="website_id" name="website_id" value="$args[website_id]" />
<input type="hidden" id="survey_id" name="survey_id" value="$args[survey_id]" />

HTML;
    if (!empty($args['sample_method_id'])) {
      $r .= '<input type="hidden" name="sample:sample_method_id" value="' . $args['sample_method_id'] . '"/>' . PHP_EOL;
    }
    if (isset(data_entry_helper::$entity_to_load['sample:id'])) {
      $r .= '<input type="hidden" id="sample:id" name="sample:id" value="' . data_entry_helper::$entity_to_load['sample:id'] . '" />' . PHP_EOL;
    }
    $gridMode = call_user_func(array(self::$called_class, 'getGridMode'), $args);
    if (isset(data_entry_helper::$entity_to_load['occurrence:id']) && !$gridMode) {
      $r .= '<input type="hidden" id="occurrence:id" name="occurrence:id" value="' . data_entry_helper::$entity_to_load['occurrence:id'] . '" />' . PHP_EOL;
    }
    $r .= self::get_group_licence_html();
    if (!empty(data_entry_helper::$entity_to_load['sample:group_id'])) {
      $r .= "<input type=\"hidden\" id=\"group_id\" name=\"sample:group_id\" value=\"" . data_entry_helper::$entity_to_load['sample:group_id'] . "\" />\n";
      // If the group does not release it's records, set the release_status
      // flag.
      if (self::$group['private_records'] === 't') {
        $r .= "<input type=\"hidden\" id=\"occurrence:release_status\" name=\"occurrence:release_status\" value=\"U\" />\n";
      }
      if (empty(data_entry_helper::$entity_to_load['sample:group_title'])) {
        data_entry_helper::$entity_to_load['sample:group_title'] = self::$group['title'];
      }
      // If a possibility of confusion when using this form, add info to
      // clarify which group you are posting to
      if (empty(self::$limitToGroupId)) {
        $msg = empty(self::$loadedSampleId) ?
            'The records you enter using this form will be added to the <strong>{1}</strong> group.' :
            'The records on this form are part of the <strong>{1}</strong> group.';
        $r .= '<p>' . lang::get($msg, data_entry_helper::$entity_to_load['sample:group_title']) . '</p>';
      }
    }
    elseif (self::$availableForGroups && !isset(data_entry_helper::$entity_to_load['sample:id'])) {
      // Group enabled form being used to add new records, but no group specified in URL path, so give
      // the user a chance to pick from their list of possible groups for this form.
      // Get the list of possible groups they might be posting into using this form. To do this we need the page
      // path without the initial leading /.
      $reload = data_entry_helper::get_reload_link_parts();
      // Slightly messy path handling. Ideally we'd call the same code as group_edit::get_path but we don't know the nid.
      $reload['path'] = preg_replace('/^\//', '', $reload['path']);
      $dirname = preg_replace('/^\//', '', dirname($_SERVER['SCRIPT_NAME'])) . '/';
      $reload['path'] = str_replace($dirname, '', $reload['path']);
      $possibleGroups = data_entry_helper::get_report_data(array(
        'dataSource' => 'library/groups/groups_for_page',
        'readAuth' => $auth['read'],
        'caching' => TRUE,
        'extraParams' => array(
            'currentUser' => hostsite_get_user_field('indicia_user_id'),
            'path' => $reload['path']
        )
      ));
      // Output a drop down so they can select the appropriate group.
      if (count($possibleGroups) > 1) {
        $options = array('' => lang::get('Ad-hoc non-group records'));
        foreach ($possibleGroups as $group) {
          $options[$group['id']] = "$group[group_type]: $group[title]";
        }
        $r .= data_entry_helper::select(array(
            'label' => lang::get('Record destination'),
            'helpText' => lang::get('Choose whether to post your records into a group that you belong to.'),
            'fieldname' => 'sample:group_id',
            'lookupValues' => $options
        ));
      }
      elseif (count($possibleGroups) === 1) {
        $r .= data_entry_helper::radio_group(array(
            'label' => lang::get('Post to {1}', $possibleGroups[0]['title']),
            'labelClass' => 'auto',
            'helpText' => lang::get('Choose whether to post your records into {1}.', $possibleGroups[0]['title']),
            'fieldname' => 'sample:group_id',
            'lookupValues' => array('' => lang::get('No'), $possibleGroups[0]['id'] => lang::get('Yes'))
        ));
      }
    }
    // Check if Record Status is included as a control. If not, then add it as a hidden.
    $arr = helper_base::explode_lines($args['structure']);
    if (!in_array('[record status]', $arr)) {
      $value = isset($args['defaults']['occurrence:record_status']) ? $args['defaults']['occurrence:record_status'] : 'C';
      $r .= '<input type="hidden" id="occurrence:record_status" name="occurrence:record_status" value="' . $value . '" />' . PHP_EOL;
    }
    if (!empty($args['defaults']['occurrence:release_status'])) {
      $r .= '<input type="hidden" id="occurrence:release_status" name="occurrence:release_status" value="' . $args['defaults']['occurrence:release_status'] . '" />' . PHP_EOL;
    }
    $r .= get_user_profile_hidden_inputs($attributes, $args, isset(data_entry_helper::$entity_to_load['sample:id']), $auth['read']);
    if ($gridMode) {
      $r .= '<input type="hidden" value="true" name="gridmode" />';
    }
    return $r;
  }

  /**
   * Retrieves the licence message and licence ID to add to the page, if relevant.
   * E.g. if the sample is already licenced, or the group you are posting to has selected
   * a licence.
   * @return string
   */
  private static function get_group_licence_html() {
    $r = '';
    if (!empty(data_entry_helper::$entity_to_load['sample:licence_id']) || !empty(self::$group['licence_id'])) {
      if (!empty(data_entry_helper::$entity_to_load['sample:licence_id'])) {
        $msg = 'The records on this form are licenced as <strong>{1}</strong>.';
        $licence_id = data_entry_helper::$entity_to_load['sample:licence_id'];
        $code = data_entry_helper::$entity_to_load['sample:licence_code'];
      } else {
        $msg = 'The records you enter using this form will be licenced as <strong>{1}</strong>.';
        $licence_id = self::$group['licence_id'];
        $code =  self::$group['licence_code'];
      }
      $licence = self::licence_code_to_text($code);
      $r .= '<p class="licence licence-' . strtolower($code) . '">' . lang::get($msg, $licence) . '</p>';
      $r .= "<input type=\"hidden\" name=\"sample:licence_id\" value=\"$licence_id\" />";
      $r .= "<input type=\"hidden\" name=\"sample:licence_code\" value=\"licence_code\" />";
    }
    return $r;
  }

  /**
   * Converts a licence code (e.g. CC BY) to readable text.
   * @param string $code
   * @return string
   */
  private static function licence_code_to_text($code) {
    return str_replace([
        'CC',
        'BY',
        'NC',
        'ND',
        'SA',
        '0',
        'OGL',
      ], [
        lang::get('Creative Commons'),
        lang::get('By Attribution'),
        lang::get('Non-Commercial'),
        lang::get('No Derivatives'),
        lang::get('Share Alike'),
        lang::get(' (no rights reserved)'),
        lang::get('Open Government Licence'),
      ],
      $code
    );
  }

  /**
   * Implement the link_species_popups parameter.
   *
   * This hides any identified blocks and pops them up when a certain species
   * is entered.
   */
  protected static function linkSpeciesPopups($args) {
    $r = '';
    if (isset($args['link_species_popups']) && !empty($args['link_species_popups'])) {
      data_entry_helper::add_resource('fancybox');
      $popups = helper_base::explode_lines($args['link_species_popups']);
      foreach ($popups as $popup) {
        $tokens = explode("|", $popup);
        if (count($tokens) === 2) {
          $fieldset = get_fieldset_id($tokens[1]);
        }
        elseif (count($tokens) === 3) {
          $fieldset = get_fieldset_id($tokens[1], $tokens[2]);
        }
        else {
          throw new Exception('The link species popups form argument contains an invalid value');
        }

        // insert a save button into the fancyboxed fieldset, since the normal close X looks like it cancels changes
        data_entry_helper::$javascript .= "$('#$fieldset').append('<input type=\"button\" value=\"".lang::get('Close')."\" onclick=\"jQuery.fancybox.close();\" ?>');\n";
        // create an empty link that we can fire to fancybox the popup fieldset
        $r .= "<a href=\"#$fieldset\" id=\"click-$fieldset\"></a>\n";
        // add a hidden div to the page so we can put the popup fieldset into it when not popped up
        data_entry_helper::$javascript .= "$('#$fieldset').after('<div style=\"display:none;\" id=\"hide-$fieldset\"></div>');\n";
        // put the popup fieldset into the hidden div
        data_entry_helper::$javascript .= "$('#hide-$fieldset').append($('#$fieldset'));\n";
        // capture new row events on the grid
        data_entry_helper::$javascript .= "hook_species_checklist_new_row.push(function(data) {
  if (data.preferred_taxon === '$tokens[0]') {
    $('#click-$fieldset').fancybox({closeBtn: false}).trigger('click');
  }
});\n";
      }
    }
    return $r;
  }

  /**
   * Get the map control.
   */
  protected static function get_control_map($auth, $args, $tabalias, $options) {
    $options = array_merge(
      iform_map_get_map_options($args, $auth['read']),
      $options
    );
    if (!empty(data_entry_helper::$entity_to_load['sample:wkt']))
      $options['initialFeatureWkt'] = data_entry_helper::$entity_to_load['sample:wkt'];
    if ($tabalias)
      $options['tabDiv'] = $tabalias;
    $olOptions = iform_map_get_ol_options($args);
    if (!isset($options['standardControls']))
      $options['standardControls']=array('layerSwitcher','panZoom');
    iform_load_helpers(array('map_helper'));
    return map_helper::map_panel($options, $olOptions);
  }

  /**
   * Get the control for map based species input, assumed to be multiple entry: ie a grid. Can be single species though.
   * Uses the normal species grid, so all options that apply to that, apply to this.
   * An option called sampleMethodId can be used to specify the sample method used for subsamples, and therefore the
   * controls to show for subsamples.
   */
  protected static function get_control_speciesmap($auth, $args, $tabAlias, $options) {
    $options = array_merge([
      'extraParams' => [],
      'readAuth' => $auth['read'],
    ], $options);
    $gridmode = call_user_func(array(self::$called_class, 'getGridMode'), $args);
    if (!$gridmode) {
      return "<b>The SpeciesMap control must be used in gridmode.</b><br/>";
    }
    if (!empty($args['taxon_filter_field'])) {
      $options['taxonFilterField'] = $args['taxon_filter_field'];
      $filterLines = helper_base::explode_lines($args['taxon_filter']);
      $options['taxonFilter'] = $filterLines;
    }
    $occAttrOptions = self::extractOccurrenceAttributeOptions($options);
    $systems = explode(',', str_replace(' ', '', $args['spatial_systems']));
    call_user_func(array(self::$called_class, 'build_grid_taxon_label_function'), $args, $options);
    return data_entry_helper::multiple_places_species_checklist(array_merge([
      'readAuth' => $auth['read'],
      'survey_id' => $args['survey_id'],
      'listId' => $args['list_id'],
      'lookupListId' => $args['extra_list_id'],
      'occAttrOptions' => $occAttrOptions,
      'systems' => $args['spatial_systems'],
      'occurrenceComment' => $args['occurrence_comment'],
      'occurrenceSensitivity' => (isset($args['occurrence_sensitivity']) ? $args['occurrence_sensitivity'] : false),
      'occurrenceImages' => $args['occurrence_images'],
      'sample_method_id' => isset($options['sampleMethodId']) ? $options['sampleMethodId'] : NULL,
      'speciesNameFilterMode' => self::getSpeciesNameFilterMode($args),
      'userControlsTaxonFilter' => isset($args['user_controls_taxon_filter']) ? $args['user_controls_taxon_filter'] : false,
      'subSpeciesColumn' => $args['sub_species_column'],
      'copyDataFromPreviousRow' => !empty($args['copy_species_row_data_to_new_rows']) && $args['copy_species_row_data_to_new_rows'],
      'previousRowColumnsToInclude' => empty($args['previous_row_columns_to_include']) ? '' : $args['previous_row_columns_to_include'],
      'editTaxaNames' => !empty($args['edit_taxa_names']) && $args['edit_taxa_names'],
      'includeSpeciesGridLinkPage' => !empty($args['include_species_grid_link_page']) && $args['include_species_grid_link_page'],
      'speciesGridPageLinkUrl' => $args['species_grid_page_link_url'],
      'speciesGridPageLinkParameter' => $args['species_grid_page_link_parameter'],
      'speciesGridPageLinkTooltip' => $args['species_grid_page_link_tooltip'],
      'spatialSystem' => $systems[0],
      'label' => lang::get('occurrence:taxa_taxon_list_id'),
      'columns' => 1,
      'PHPtaxonLabel' => TRUE,
      'speciesInLabel' => FALSE,
      'language' => iform_lang_iso_639_2(hostsite_get_user_field('language')),
    ], $options));
  }

  /**
   * Get the control for the summary for the map based species input.
   */
  protected static function get_control_speciesmapsummary($auth, $args, $tabAlias, $options) {
    return data_entry_helper::multiple_places_species_checklist_summary();
  }

  /**
   * The species filter can be taken from the edit tab or overridden by a URL filter.
   * This method determines the filter to be used.
   * @param array $args Form arguments
   * @return array List of items to filter against, e.g. species names or meaning IDs.
   */
  protected static function get_species_filter($args) {
    // we must have a filter field specified in order to apply a filter
    if (!empty($args['taxon_filter_field'])) {
      // if URL params are enabled and we have one, then this is the top priority filter to apply
      if (!empty($_GET['taxon']) && $args['use_url_taxon_parameter'])
        // convert commas to newline, so url provided filters are the same format as those
        // on the edit tab, also allowing for url encoding.
        return explode(',', urldecode($_GET['taxon']));
      elseif (!empty($args['taxon_filter']))
        // filter is provided on the edit tab
        return helper_base::explode_lines($args['taxon_filter']);
    }
    // default - no filter to apply
    return array();
  }

  /**
   * Get the species data for the page in single species mode
   */
  protected static function get_single_species_data($auth, $args, $filterLines) {
    //The form is configured for filtering by taxon name, meaning id or external key. If there is only one specified, then the form
    //cannot display a species checklist, as there is no point. So, convert our preferred taxon name, meaning ID or external_key to find the
    //preferred taxa_taxon_list_id from the selected checklist
    if (empty($args['list_id']))
      throw new exception(lang::get('Please configure the Initial Species List parameter to define which list the species to record is selected from.'));
    $filter = array(
      'preferred' => 't',
      'taxon_list_id' => $args['list_id']
    );
    if ($args['taxon_filter_field']=='preferred_name') {
      $filter['taxon']=$filterLines[0];
    } else {
      $filter[$args['taxon_filter_field']]=$filterLines[0];
    }
    $options = array(
      'table' => 'taxa_taxon_list',
      'extraParams' => $auth['read'] + $filter
    );
    $response =data_entry_helper::get_population_data($options);
    // Call code that handles the error logs
    self::get_single_species_logging($auth, $args, $filterLines, $response);
    return $response;
  }

  /**
   * Error logging code for the page in single species mode
   */
  protected static function get_single_species_logging($auth, $args, $filterLines, $response) {
    //Go through each filter line and add commas between the values so it looks nice in the log
    $filters = implode(', ', $filterLines);
    //If only one filter is supplied but more than one match is found, we can't continue as we don't know which one to match against.
    if (count($response)>1 and count($filterLines)==1 and empty($response['error'])) {
      if (function_exists('watchdog')) {
        watchdog('indicia', 'Multiple matches have been found when using the filter \''.$args['taxon_filter_field'].'\'. '.
          'The filter was passed the following value(s)'.$filters);
        throw new exception(lang::get('This form is setup for single species recording, but more than one species matching the criteria exists in the list.'));
      }
    }
    //If our filter returns nothing at all, we log it, we return string 'no matches' which the system then uses to clear the filter
    if (count($response)==0) {
      if (function_exists('watchdog'))
        watchdog('missing sp.', 'No matches were found when using the filter \''.$args['taxon_filter_field'].'\'. '.
          'The filter was passed the following value(s)'.$filters);
    }
  }

  /**
   * Get the control for species input, either a grid or a single species input control.
   */
  protected static function get_control_species($auth, $args, $tabAlias, $options) {
    $gridmode = call_user_func(array(self::$called_class, 'getGridMode'), $args);
    //The filter can be a URL or on the edit tab, so do the processing to work out the filter to use
    $filterLines = self::get_species_filter($args);
    // store in the argument so that it can be used elsewhere
    $args['taxon_filter'] = implode("\n", $filterLines);
    //Single species mode only ever applies if we have supplied only one filter species and we aren't in taxon group mode
    if ($args['taxon_filter_field']!=='taxon_group' && count($filterLines)===1 && ($args['multiple_occurrence_mode']!=='multi')) {
      $response = self::get_single_species_data($auth, $args, $filterLines);
      //Optional message to display the single species on the page
      if ($args['single_species_message']) {
        self::$singleSpeciesName = data_entry_helper::apply_static_template('single_species_taxon_label', $response[0]);
      }
      if (count($response)==0)
        //if the response is empty there is no matching taxon, so clear the filter as we can try and display the checklist with all data
        $args['taxon_filter']='';
      elseif (count($response)==1)
        //Keep the id of the single species in a hidden field for processing if in single species mode
        return '<input type="hidden" name="occurrence:taxa_taxon_list_id" value="'.$response[0]['id']."\"/>\n";
    }
    $extraParams = $auth['read'];
    if ($gridmode)
      return self::get_control_species_checklist($auth, $args, $extraParams, $options);
    else
      return self::get_control_species_single($auth, $args, $extraParams, $options);
  }



  /**
   * Returns the species checklist input control.
   * @param array $auth Read authorisation tokens
   * @param array $args Form configuration
   * @param array $extraParams Extra parameters array, pre-configured with filters for taxa and name types.
   * @param array $options additional options for the control, e.g. those configured in the form structure.
   * @return string HTML for the species_checklist control.
   */
  protected static function get_control_species_checklist($auth, $args, $extraParams, $options) {
    // Build the configuration options
    if (isset($options['view']))
      $extraParams['view'] = $options['view'];
    $occAttrOptions = self::extractOccurrenceAttributeOptions($options);
    // make sure that if extraParams is specified as a config option, it does not replace the essential stuff
    if (isset($options['extraParams']))
      $options['extraParams'] = array_merge($extraParams, $options['extraParams']);
    $species_ctrl_opts=array_merge(array(
        'occAttrOptions' => $occAttrOptions,
        'listId' => $args['list_id'],
        'label' => lang::get('occurrence:taxa_taxon_list_id'),
        'columns' => 1,
        'extraParams' => $extraParams,
        'survey_id' => $args['survey_id'],
        'occurrenceComment' => $args['occurrence_comment'],
        'occurrenceSensitivity' => (isset($args['occurrence_sensitivity']) ? $args['occurrence_sensitivity'] : false),
        'occurrenceImages' => $args['occurrence_images'],
        'PHPtaxonLabel' => true,
        'speciesInLabel' => false,
        'language' => iform_lang_iso_639_2(hostsite_get_user_field('language')), // used for termlists in attributes
        'speciesNameFilterMode' => self::getSpeciesNameFilterMode($args),
        'userControlsTaxonFilter' => isset($args['user_controls_taxon_filter']) ? $args['user_controls_taxon_filter'] : false,
        'subSpeciesColumn' => $args['sub_species_column'],
        'copyDataFromPreviousRow' => !empty($args['copy_species_row_data_to_new_rows']) && $args['copy_species_row_data_to_new_rows'],
        'previousRowColumnsToInclude' => empty($args['previous_row_columns_to_include']) ? '' : $args['previous_row_columns_to_include'],
        'editTaxaNames' => !empty($args['edit_taxa_names']) && $args['edit_taxa_names'],
        'includeSpeciesGridLinkPage' => !empty($args['include_species_grid_link_page']) && $args['include_species_grid_link_page'],
        'speciesGridPageLinkUrl' => $args['species_grid_page_link_url'],
        'speciesGridPageLinkParameter' => $args['species_grid_page_link_parameter'],
        'speciesGridPageLinkTooltip' => $args['species_grid_page_link_tooltip'],
    ), $options);
    if ($groups=hostsite_get_user_field('taxon_groups')) {
      $species_ctrl_opts['usersPreferredGroups'] = unserialize($groups);
    }
    if ($args['extra_list_id'] && !isset($options['lookupListId']))
      $species_ctrl_opts['lookupListId']=$args['extra_list_id'];
    //We only do the work to setup the filter if the user has specified a filter in the box
    if (!empty($args['taxon_filter_field']) && (!empty($args['taxon_filter']))) {
      $species_ctrl_opts['taxonFilterField']=$args['taxon_filter_field'];
      $filterLines = helper_base::explode_lines($args['taxon_filter']);
      $species_ctrl_opts['taxonFilter']=$filterLines;
    }
    if (isset($args['col_widths']) && $args['col_widths']) $species_ctrl_opts['colWidths'] = explode(',', $args['col_widths']);
    call_user_func(array(self::$called_class, 'build_grid_taxon_label_function'), $args, $options);
    if (self::$mode == self::MODE_CLONE)
      $species_ctrl_opts['useLoadedExistingRecords'] = true;

    // Set speciesInLabel flag on indiciaData.
    $speciesInLabel = !empty($options['speciesInLabel']) ? 'true' : 'false';
    data_entry_helper::$javascript .= "\nindiciaData.speciesInLabel=".$speciesInLabel.";\n";

    return data_entry_helper::species_checklist($species_ctrl_opts);
  }

  protected static function extractOccurrenceAttributeOptions(&$options) {
    // There may be options in the form occAttr:n|param => value targetted at specific attributes
    $occAttrOptions = array();
    $optionToUnset = array();
    foreach ($options as $option => $value) {
      // split the id of the option into the attribute name and option name.
      $optionParts = explode('|', $option);
      if ($optionParts[0] != $option) {
        // an occurrence attribute option was found
        $attrName = $optionParts[0];
        $optName = $optionParts[1];
        // split the attribute name into the type and id (type will always be occAttr)
        $attrParts = explode(':', $attrName);
        $attrId = $attrParts[1];
        if (!isset($occAttrOptions[$attrId])) $occAttrOptions[$attrId]=array();
        $occAttrOptions[$attrId][$optName] = apply_user_replacements($value);
        $optionToUnset[] = $option;
      }
    }
    // tidy up options array
    foreach ($optionToUnset as $value) {
      unset($options[$value]);
    }
    return $occAttrOptions;
  }

  /**
   * Returns a control for picking a single species
   * @global type $indicia_templates
   * @param array $auth Read authorisation tokens
   * @param array $args Form configuration
   * @param array $extraParams Extra parameters pre-configured with taxon and taxon name type filters.
   * @param array $options additional options for the control, e.g. those configured in the form structure.
   * @return string HTML for the control.
   */
  protected static function get_control_species_single($auth, $args, $extraParams, $options) {
    $r = '';
    if ($args['extra_list_id'] === '' && $args['list_id'] !== '')
      $extraParams['taxon_list_id'] = $args['list_id'];
    elseif ($args['extra_list_id'] !== '' && $args['list_id'] === '')
      $extraParams['taxon_list_id'] = $args['extra_list_id'];
    elseif ($args['extra_list_id'] !== '' && $args['list_id'] !== '')
      $extraParams['taxon_list_id'] = json_encode([$args['list_id'], $args['extra_list_id']]);

    // Add a taxon group selector if that option was chosen
    if (isset($options['taxonGroupSelect']) && $options['taxonGroupSelect']) {
      $label = isset($options['taxonGroupSelectLabel']) ? $options['taxonGroupSelectLabel'] : 'Species Group';
      $helpText = isset($options['taxonGroupSelectHelpText']) ? $options['taxonGroupSelectHelpText'] : 'Choose which species group you want to pick a species from.';
      if (!empty(data_entry_helper::$entity_to_load['occurrence:taxa_taxon_list_id'])) {
        // need to find the default value
        $species = data_entry_helper::get_population_data(array(
          'table' => 'cache_taxa_taxon_list',
          'extraParams' => $auth['read'] + array('id' => data_entry_helper::$entity_to_load['occurrence:taxa_taxon_list_id'])
        ));
        data_entry_helper::$entity_to_load['taxon_group_id'] = $species[0]['taxon_group_id'];
      }
      $r .= data_entry_helper::select(array(
        'fieldname' => 'taxon_group_id',
        'id' => 'taxon_group_id',
        'label' => lang::get($label),
        'helpText' => lang::get($helpText),
        'report' => 'library/taxon_groups/taxon_groups_used_in_checklist',
        'valueField' => 'id',
        'captionField' => 'title',
        'extraParams' => $auth['read'] + array('taxon_list_id' => $extraParams['taxon_list_id']),
      ));
      // update the select box to link to the species group picker. It must be a select box!
      $args['species_ctrl'] = 'select';
      $options['parentControlId'] = 'taxon_group_id';
      $options['parentControlLabel'] = lang::get($label);
      $options['filterField'] = 'taxon_group_id';
    }

    $options['speciesNameFilterMode'] = self::getSpeciesNameFilterMode($args);
    $ctrl = $args['species_ctrl'] === 'autocomplete' ? 'species_autocomplete' : $args['species_ctrl'];
    $species_ctrl_opts = array_merge(array(
        'fieldname' => 'occurrence:taxa_taxon_list_id',
        'label' => lang::get('occurrence:taxa_taxon_list_id'),
        'columns' => 2, // applies to radio buttons
        'parentField' => 'parent_id', // applies to tree browsers
        'view' => 'detail', // required for tree browsers to get parent id
        'blankText' => lang::get('Please select') // applies to selects
    ), $options);
    if (isset($species_ctrl_opts['extraParams'])) {
      $species_ctrl_opts['extraParams'] = array_merge($extraParams, $species_ctrl_opts['extraParams']);
    }
    else {
      $species_ctrl_opts['extraParams'] = $extraParams;
    }
    $species_ctrl_opts['extraParams'] = array_merge(array(
        'view' => 'detail', //required for hierarchical select to get parent id
        'orderby' => 'taxonomic_sort_order',
        'sortdir' => 'ASC'
    ), $species_ctrl_opts['extraParams']);
    if (!empty($args['taxon_filter'])) {
      $species_ctrl_opts['taxonFilterField'] = $args['taxon_filter_field']; // applies to autocompletes
      $species_ctrl_opts['taxonFilter'] = helper_base::explode_lines($args['taxon_filter']); // applies to autocompletes
    }
    if ($ctrl !== 'species_autocomplete') {
      // The species autocomplete has built in support for the species name filter.
      // For other controls we need to apply the species name filter to the params used for population
      if (!empty($species_ctrl_opts['taxonFilter']) || $options['speciesNameFilterMode']) {
        $species_ctrl_opts['extraParams'] = array_merge(
            $species_ctrl_opts['extraParams'],
            data_entry_helper::getSpeciesNamesFilter($species_ctrl_opts)
        );
      }
      // for controls which don't know how to do the lookup, we need to tell them
      $species_ctrl_opts = array_merge(array(
        'table' => 'taxa_search',
        'captionField' => 'taxon',
        'valueField' => 'taxa_taxon_list_id',
      ), $species_ctrl_opts);
    }
    // if using something other than an autocomplete, then set the caption template to include the appropriate names. Autocompletes
    // use a JS function instead.
    global $indicia_templates;
    if ($ctrl!=='autocomplete' && isset($args['species_include_both_names']) && $args['species_include_both_names']
        && !isset($species_ctrl_opts['captionTemplate'])) {
      if ($args['species_names_filter']==='all') {
        $indicia_templates['species_caption'] = "{taxon}";
      } elseif ($args['species_names_filter']==='language') {
        $indicia_templates['species_caption'] = "{taxon} - {preferred_taxon}";
      } else {
        $indicia_templates['species_caption'] = "{taxon} - {default_common_name}";
      }
      $species_ctrl_opts['captionTemplate'] = 'species_caption';
    }

    if ($ctrl=='tree_browser') {
      // change the node template to include images
      $indicia_templates['tree_browser_node']='<div>'.
          '<img src="'.data_entry_helper::$base_url.'/upload/thumb-{image_path}" alt="Image of {caption}" width="80" /></div>'.
          '<span>{caption}</span>';
    }

    // Dynamically generate the species selection control required.
    $r .= call_user_func(array('data_entry_helper', $ctrl), $species_ctrl_opts);
    return $r;
  }

  /**
   * Function to map from the species_names_filter argument to the speciesNamesFilterMode required by the
   * checklist grid. For legacy reasons they don't quite match.
   */
  protected static function getSpeciesNameFilterMode($args) {
    if (isset($args['species_names_filter'])) {
      switch ($args['species_names_filter']) {
        case 'language':
          return 'currentLanguage';
        default:
          return $args['species_names_filter'];
      }
    }
    // default is no species name filter.
    return false;
  }

  /**
   * Build a JavaScript function  to format the display of existing taxa added to the species input grid
   * when an existing sample is loaded.
   */
  protected static function build_grid_taxon_label_function($args, $options) {
    global $indicia_templates;
    if (!empty($options['taxonLabelTemplate']) && !empty($indicia_templates[$options['taxonLabelTemplate']])) {
      $indicia_templates['taxon_label'] = $indicia_templates[$options['taxonLabelTemplate']];
      return;
    }
    // Set up the indicia templates for taxon labels according to options, as long as the template has been left at it's default state
    if ($indicia_templates['taxon_label'] == '<div class="biota"><span class="nobreak sci binomial"><em class="taxon-name">{taxon}</em></span> {authority} '.
        '<span class="nobreak vernacular">{default_common_name}</span></div>') {
      // always include the searched name
      $php = '$r="";'."\n".
          'if ("{language}"=="lat" || "{language_iso}"=="lat") {'."\n".
          '  $r = "<em class=\'taxon-name\'>{taxon}</em>";'."\n".
          '} else {'."\n".
          '  $r = "<span class=\'taxon-name\'>{taxon}</span>";'."\n".
          '}'."\n";
      // This bit optionally adds '- common' or '- latin' depending on what was being searched
      if (isset($args['species_include_both_names']) && $args['species_include_both_names']) {
        $php .= "\n\n".'if ("{preferred}"=="t" && "{default_common_name}"!="{taxon}" && "{default_common_name}"!="") {'."\n\n\n".
          '  $r .= " - {default_common_name}";'."\n".
          '} else if ("{preferred}"=="f" && "{preferred_taxon}"!="{taxon}" && "{preferred_taxon}"!="") {'."\n".
          '  $r .= " - <em>{preferred_taxon}</em>";'."\n".
          '}'."\n";
      }
      // this bit optionally adds the taxon group
      if (isset($args['species_include_taxon_group']) && $args['species_include_taxon_group']) {
        $php .= '$r .= "<br/><strong>{taxon_group}</strong>";'."\n";
      }
      // Close the function
      $php .= 'return $r;'."\n";
      $indicia_templates['taxon_label'] = $php;
    }
  }

  /**
   * Get the sample comment control
   */
  protected static function get_control_samplecomment($auth, $args, $tabAlias, $options) {
    return data_entry_helper::textarea(array_merge(array(
      'fieldname' => 'sample:comment',
      'label'=>lang::get('Overall comment')
    ), $options));
  }

  /**
   * Get the sample photo control
   */
  protected static function get_control_samplephoto($auth, $args, $tabAlias, $options) {
    if (!empty($options['label']))
      $label = $options['label'];
    else
      $label = 'Overall Photo';
    //Create a list of arrays. Each item is itself an array with a control id and associated sub type.
    //This allows there to be multiple photo controls, each saving its own type of image. This can keep the
    //photos from each control independent of each other.
    if (!empty($options['id'])&&!empty($options['subType'])) {
      data_entry_helper::$javascript.="
      if (indiciaData.subTypes) {
        indiciaData.subTypes.push(['".$options['id']."','".$options['subType']."']);
      } else {
        indiciaData.subTypes=[['".$options['id']."','".$options['subType']."']];
      }\n";
    }
    return data_entry_helper::file_box(array_merge(array(
      'table' => 'sample_medium',
      'readAuth' => $auth['read'],
      'caption'=>lang::get($label)
    ), $options));
  }

  /**
   * Get the block of custom attributes at the species (occurrence) level
   */
  protected static function get_control_speciesattributes($auth, $args, $tabAlias, $options) {
    if (!(call_user_func(array(self::$called_class, 'getGridMode'), $args))) {
      self::load_custom_occattrs($auth['read'], $args['survey_id']);
      $ctrlOptions = array('extraParams' => $auth['read']);
      $attrSpecificOptions = array();
      self::parseForAttrSpecificOptions($options, $ctrlOptions, $attrSpecificOptions);
      $r = '';
      if ($args['occurrence_sensitivity']) {
        $sensitivity_controls = get_attribute_html(self::$occAttrs, $args, $ctrlOptions, 'sensitivity', $attrSpecificOptions);
        $r .= data_entry_helper::sensitivity_input(array(
          'additionalControls' => $sensitivity_controls
        ));
      }
      $r .= get_attribute_html(self::$occAttrs, $args, $ctrlOptions, '', $attrSpecificOptions);
      if ($args['occurrence_comment'])
        $r .= data_entry_helper::textarea(array(
          'fieldname' => 'occurrence:comment',
          'label'=>lang::get('Record comment')
        ));
      if ($args['occurrence_images']){
        $r .= self::occurrence_photo_input($auth['read'], $options, $tabAlias);
      }
      return $r;
    } else
      // in grid mode the attributes are embedded in the grid.
      return '';
  }

  /**
   * Returns a div for dynamic attributes.
   *
   * Returns div into which any attributes associated with the chosen taxon
   * and/or stage will be inserted. JavaScript is added to the page which
   * detects a chosen taxon (single record forms only) and adds the appropriate
   * attributes. If loading an existing record, then the attributes are
   * pre-loaded into the div.
   *
   * The [species dynamic attributes] control can be called with a @types option
   * which can be set to a JSON array containing either occurrence and/or
   * sample depending on which type of custom attributes to load. Defaults to
   * ["occurrence"] but you might want to include "sample" in the array when
   * there are taxon specific habitat attributes for example.
   *
   * Set the @validateAgainstTaxa option to true to enable validation of
   * attribute values against the equivalent attributes defined for the taxon
   * which requires the warehouse attribute_sets module to be enabled and
   * configured.
   *
   * Any other options are passed through to the controls.
   *
   * @return string
   *   HTML for the div.
   */
  protected static function get_control_speciesdynamicattributes($auth, $args, $tabAlias, $options) {
    $types = isset($options['types']) ? $options['types'] : ['occurrence'];
    $validateAgainstTaxa = empty($options['validateAgainstTaxa']) ? 'false' : 'true';
    unset($options['types']);
    unset($options['validateAgainstTaxa']);
    $ajaxUrl = hostsite_get_url('iform/ajax/dynamic_sample_occurrence');
    $language = iform_lang_iso_639_2(hostsite_get_user_field('language'));
    data_entry_helper::$javascript .= <<<JS
indiciaData.ajaxUrl="$ajaxUrl";
indiciaData.validateAgainstTaxa = $validateAgainstTaxa;
indiciaData.userLang = '$language';

JS;

    // If loading existing data, we need to know the sex/stage attrs so we can
    // find the value to filter to when retrieving attrs.
    if (!empty(self::$loadedOccurrenceId)) {
      self::load_custom_occattrs($auth['read'], $args['survey_id']);
      $stageTermlistTermIds = [];
      foreach (self::$occAttrs as $attr) {
        if (!empty($attr['default']) && ($attr['system_function'] === 'sex'
            || $attr['system_function'] === 'stage'
            || $attr['system_function'] === 'sex_stage')) {
          $stageTermlistTermIds[] = $attr['default'];
        }
      }
    }
    // For each type (occurrence/sample) create a div to hold the controls,
    // pre-populated only when loading existing data.
    $r = '';
    foreach ($types as $type) {
      $controls = '';
      if (!empty(self::$loadedOccurrenceId)) {
        $controls = self::getDynamicAttrs(
          $auth['read'],
          $args['survey_id'],
          data_entry_helper::$entity_to_load['occurrence:taxa_taxon_list_id'],
          $stageTermlistTermIds,
          $type,
          $options,
          self::$loadedOccurrenceId
        );
      }
      // Other options need to pass through to AJAX loaded controls.
      $optsJson = json_encode($options);
      data_entry_helper::$javascript .= <<<JS
indiciaData.dynamicAttrOptions$type=$optsJson;
// Call any load hooks.
$.each(indiciaFns.hookDynamicAttrsAfterLoad, function callHook() {
  this($('.species-dynamic-attrs.attr-type-$type'), '$type');
});
JS;
      // Add a container div.
      $r .= "<div class=\"species-dynamic-attributes attr-type-$type\">$controls</div>";
    }
    return $r;
  }

  /**
   * Retrieves a list of dynamically loaded attributes from the database.
   *
   * @return array
   *   List of attribute data.
   */
  private static function getDynamicAttrsList($readAuth, $surveyId, $ttlId, $stageTermlistsTermIds, $type, $language, $occurrenceId = NULL) {
    $params = [
      'survey_id' => $surveyId,
      'taxa_taxon_list_id' => $ttlId,
      'master_checklist_id' => hostsite_get_config_value('iform', 'master_checklist_id', 0),
      'language' => $language,
    ];
    if (!empty($stageTermlistsTermIds)) {
      $params['stage_termlists_term_ids'] = implode(',', $stageTermlistsTermIds);
    }
    if (!empty($occurrenceId)) {
      $params['occurrence_id'] = $occurrenceId;
    }
    $r = report_helper::get_report_data([
      'dataSource' => "library/{$type}_attributes/{$type}_attributes_for_form",
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
  private static function getDynamicAttrs($readAuth, $surveyId, $ttlId, $stageTermlistsTermIds, $type, $options,
      $occurrenceId = NULL, $language = NULL) {
    iform_load_helpers(['data_entry_helper', 'report_helper']);
    $attrs = self::getDynamicAttrsList($readAuth, $surveyId, $ttlId, $stageTermlistsTermIds, $type, $language, $occurrenceId);
    $prefix = $type === 'sample' ? 'smp' : 'occ';
    return self::getDynamicAttrsOutput($prefix, $readAuth, $attrs, $options, $language);
  }

  /**
   * Ajax handler to retrieve the dynamic attrs for a taxon.
   *
   * Attribute HTML is echoed to the client.
   */
  public static function ajax_dynamicattrs($website_id, $password) {
    iform_load_helpers(['report_helper']);
    $readAuth = report_helper::get_read_auth($website_id, $password);
    echo self::getDynamicAttrs(
      $readAuth,
      $_GET['survey_id'],
      $_GET['taxa_taxon_list_id'],
      json_decode($_GET['stage_termlists_term_ids']),
      $_GET['type'],
      json_decode($_GET['options'], TRUE),
      empty($_GET['occurrence_id']) ? NULL : $_GET['occurrence_id'],
      $_GET['language']
    );
    helper_base::$is_ajax = TRUE;

    if (!empty($_GET['validate_against_taxa']) && $_GET['validate_against_taxa'] === 't') {
      $r = report_helper::get_report_data([
        'dataSource' => "library/$_GET[type]_attributes/$_GET[type]_attributes_for_taxon_with_taxon_validation_rules",
        'readAuth' => $readAuth,
        'extraParams' => ['taxa_taxon_list_id' => $_GET['taxa_taxon_list_id']],
      ]);
      if (!empty($r)) {
        $data = json_encode($r);
        $typeAbbr = $_GET['type'] === 'occurrence' ? 'occ' : 'smp';
        $langExpected = lang::get('Expected values for {1}');
        helper_base::addLanguageStringsToJs('dynamicattrs', ['expected' => 'Expected values for {1}']);
        report_helper::$javascript .= <<<JS
indiciaData.{$typeAbbr}TaxonValidationRules = $data;
indiciaFns.applyTaxonValidationRules('$typeAbbr', '$_GET[type]');

JS;
      }
    }

    $scripts = helper_base::get_scripts(
      helper_base::$javascript,
      helper_base::$late_javascript,
      helper_base::$onload_javascript,
      FALSE, TRUE
    );
    if ($scripts) {
      echo <<<JS
<script type="text/javascript">
$scripts
</script>

JS;
    }
  }

  /**
   * Get the date control.
   */
  protected static function get_control_date($auth, $args, $tabAlias, $options) {
    if($args['language'] != 'en')
      data_entry_helper::add_resource('jquery_ui_'.$args['language']); // this will autoload the jquery_ui resource. The date_picker does not have access to the args.
    if(lang::get('LANG_Date_Explanation')!='LANG_Date_Explanation')
      data_entry_helper::$javascript .= "\njQuery('[name=sample\\:date]').next().after('<span class=\"date-explanation\"> ".lang::get('LANG_Date_Explanation')."</span>');\n";
    return data_entry_helper::date_picker(array_merge(array(
      'label'=>lang::get('LANG_Date'),
      'fieldname' => 'sample:date',
      'default' => isset($args['defaults']['sample:date']) ? $args['defaults']['sample:date'] : ''
    ), $options));
  }

  /**
   * Get the location control as an autocomplete.
   *
   * If the form is configured to allow saving of personal sites, then this
   * will revert to a text input in situations where the user is not logged on.
   * This behaviour can be overridden using the @autocompleteIfLoggedOut as
   * described below.
   *
   * As well as the standard location_autocomplete options, set:
   * * @personSiteAttrId to the attribute ID of a multi-value person attribute
   *   used to link people to the sites they record at.
   * * @autocompleteIfLoggedOut - set to true to prevent this control reverting
   *   to a text input if the user is logged out. Use an alternative report or
   *   reporting filtering to prevent all the complete list of locations for
   *   the website being available for selection.
   */
  protected static function get_control_locationautocomplete($auth, $args, $tabAlias, $options) {
    if (isset($options['extraParams'])) {
      foreach ($options['extraParams'] as &$value)
        $value = apply_user_replacements($value);
    }

    $extraParams = array_merge(array('orderby' => 'name'), $auth['read']);
    if (isset($options['extraParams'])) {
      $extraParams = array_merge($extraParams, $options['extraParams']);
    }
    $location_list_args = array_merge($options, array('extraParams' => $extraParams));

    if (!isset($location_list_args['label']))
      $location_list_args['label'] = lang::get('LANG_Location_Label');
    if ((isset($args['users_manage_own_sites']) && $args['users_manage_own_sites'])
        || (!empty($_GET['group_id']))) {
      $userId = hostsite_get_user_field('indicia_user_id');
      if (empty($userId) && empty($options['autocompleteIfLoggedOut'])) {
        $location_list_args['fieldname'] = 'sample:location_name';
        return data_entry_helper::text_input($location_list_args);
      }
      $userId = empty($userId) ? 0 : $userId;
      if (!empty($options['personSiteAttrId'])) {
        $location_list_args['extraParams']['user_id']=$userId;
        $location_list_args['extraParams']['person_site_attr_id']=$options['personSiteAttrId'];
        if(!isset($options['report'])) $location_list_args['report'] = 'library/locations/my_sites_lookup';
      } else
        $location_list_args['extraParams']['created_by_id']=$userId;
      $location_list_args['extraParams']['view']='detail';
      $location_list_args['allowCreate']=true;
      // pass through the group we are recording in plus its parent, if any, so we can show group sites
      if (!empty($_GET['group_id'])) {
        $parent = data_entry_helper::get_report_data(array(
          'dataSource' => 'library/groups/groups_list',
          'readAuth' => $auth['read'],
          'extraParams' => array('to_group_id' => $_GET['group_id'], 'userFilterMode' => 'all', 'currentUser' => ''),
          'caching' => true
        ));
        $groups = $_GET['group_id'];
        if (count($parent))
          $groups .= ','.$parent[0]['id'];
        $location_list_args['extraParams']['group_id'] = $groups;
      }
    }
    if (empty($location_list_args['numValues']))
      // set a relatively high number until we sort out the "more" handling like species autocomplete.
      $location_list_args['numValues'] = 200;
    return data_entry_helper::location_autocomplete($location_list_args);
  }

  /**
   * Implements the [location url param] control, for accepting the site to record against using a location_id URL parameter.
   *
   * Outputs hidden inputs into the form to specify the location_id for the sample. Uses the location's centroid and spatial ref system to
   * fill in the sample's geometry data. If loading an existing sample, then the location_id in the URL is ignored.
   */
  protected static function get_control_locationurlparam($auth, $args, $tabAlias, $options) {
    $location_id=isset(data_entry_helper::$entity_to_load['sample:location_id']) ? data_entry_helper::$entity_to_load['sample:location_id'] :
        (empty($_GET['location_id']) ? '' : $_GET['location_id']);
    if (empty($location_id))
      return 'This form requires a URL parameter called location_id to specify which site to record against.';
    if (!preg_match('/^[0-9]+$/', $location_id))
      return 'The location_id parameter must be an integer.';
    if (isset(data_entry_helper::$entity_to_load['sample:location_id'])) {
      // no need for values as the entity to load will override any defaults.
      $location=array('id' => '', 'centroid_sref' => '', 'centroid_sref_system' => '');
    } else {
      $response = data_entry_helper::get_population_data(array(
        'table' => 'location',
        'extraParams' => $auth['read'] + array('id' => $_GET['location_id'], 'view' => 'detail')
      ));
      $location=$response[0];
    }
    $r = data_entry_helper::hidden_text(array('fieldname' => 'sample:location_id', 'default' => $location['id']));
    $r .= data_entry_helper::hidden_text(array('fieldname' => 'sample:entered_sref', 'default' => $location['centroid_sref']));
    $r .= data_entry_helper::hidden_text(array('fieldname' => 'sample:entered_sref_system', 'default' => $location['centroid_sref_system']));
    return $r;
  }

  /**
   * Get the location control as a select dropdown.
   * Default control ordering is by name.
   * reportProvidesOrderBy option should be set to true if the control is populated by a report that
   * provides its own Order By statement, if the reportProvidesOrderBy option is not set in this situation, then the report
   * will have two Order By statements and will fail.
   */
  protected static function get_control_locationselect($auth, $args, $tabAlias, $options) {
    if (isset($options['extraParams'])) {
      foreach ($options['extraParams'] as $key => &$value)
        $value = apply_user_replacements($value);
      $options['extraParams'] = array_merge($auth['read'], $options['extraParams']);
    } else
      $options['extraParams'] = array_merge($auth['read']);
    if (empty($options['reportProvidesOrderBy'])||$options['reportProvidesOrderBy']==0) {
      $options['extraParams']['orderby'] = 'name';
    }
    $location_list_args = array_merge(array(
        'label'=>lang::get('LANG_Location_Label'),
        'view' => 'detail'
    ), $options);
    return data_entry_helper::location_select($location_list_args);
  }

  /**
   * Get the sref by way of choosing a location.
   */
  protected static function get_control_locationmap($auth, $args, $tabAlias, $options) {
    // add a location select control
    $options = array_merge(array(
        'searchUpdatesSref' => true,
        'validation' => "required",
        'blankText' => "Select...",
    ), $options);
    // Choose autocomplete control if specified in $options
if($options["locationControl"]="autocomplete")
    $r = self::get_control_locationautocomplete($auth, $args, $tabAlias, $options);
else
    $r = self::get_control_locationselect($auth, $args, $tabAlias, $options);

    //only show helpText once
    unset($options['helpText']);

    // add hidden sref controls
    $r .= data_entry_helper::sref_hidden($options);

    // add a map control
    $options = array_merge(array(
        'locationLayerName' => 'indicia:detail_locations',
        'locationLayerFilter' => "website_id=" . $args['website_id'],
        'clickForSpatialRef' => false,
    ), $options);
    $r .= self::get_control_map($auth, $args, $tabAlias, $options);

    return $r;
  }
//
  /**
   * Get the location name control.
   */
  protected static function get_control_locationname($auth, $args, $tabAlias, $options) {
    return data_entry_helper::text_input(array_merge(array(
      'label' => lang::get('LANG_Location_Name'),
      'fieldname' => 'sample:location_name',
      'class' => 'control-width-5'
    ), $options));
  }

  /**
   * Get an occurrence attribute control.
   */
  protected static function get_control_occattr($auth, $args, $tabAlias, $options) {
    $attribName = 'occAttr:' . $options['ctrlId'];
    if ($args['multiple_occurrence_mode']==='single') {
      self::load_custom_occattrs($auth['read'], $args['survey_id']);
      foreach (self::$occAttrs as $idx => $attr) {
        if ($attr['id'] === $attribName) {
          self::$occAttrs[$idx]['handled'] = true;
          return data_entry_helper::outputAttribute(self::$occAttrs[$idx], $options);
        }
      }
      return "Occurrence attribute $attribName not found.";
    }
    else
      return "Occurrence attribute $attribName cannot be included in form when in grid entry mode.";
  }

  /**
   * Get the occurrence comment control
   */
  protected static function get_control_occurrencecomment($auth, $args, $tabAlias, $options) {
    if (!(call_user_func(array(self::$called_class, 'getGridMode'), $args))) {
      return data_entry_helper::textarea(array_merge(array(
        'fieldname' => 'occurrence:comment',
        'label'=>lang::get('Record comment')
      ), $options));
    } else {
      return '';
    }
  }

  /**
   * Get the photos control
   */
  protected static function get_control_photos($auth, $args, $tabAlias, $options) {
    if ($args['multiple_occurrence_mode']==='single') {
      return self::occurrence_photo_input($auth['read'], $options, $tabAlias);
    }
    else
      return "[photos] control cannot be included in form when in grid entry mode, since photos are automatically included in the grid.";
  }

  /**
   * Get the recorder names control
   * @param array $auth Read authorisation tokens
   * @param array $args Form configuration
   * @param array $tabAlias
   * @param array $options additional options for the control with the following possibilities
   * <li><b>defaultToCurrentUser</b><br/>
   * Set to true if the currently logged in user's name should be the default</li>
   * @return string HTML for the control.
   */
  protected static function get_control_recordernames($auth, $args, $tabAlias, $options) {
    //We don't need to touch the control in edit mode. Make the current user's name the default in add mode if the user has selected that option.
    if (empty($_GET['sample_id']) && empty($_GET['occurrence_id']) && !empty($options['defaultToCurrentUser'])&& $options['defaultToCurrentUser']==true) {
      $defaultUserData = data_entry_helper::get_report_data(array(
        'dataSource' => 'library/users/get_people_details_for_website_or_user',
        'readAuth' => $auth['read'],
        'extraParams' => array('user_id' => hostsite_get_user_field('indicia_user_id'), 'website_id' => $args['website_id'])
      ));
      //Need to escape characters otherwise a name like O'Brian will break the page HTML
      $defaultUserData[0]['fullname_firstname_first']=addslashes($defaultUserData[0]['fullname_firstname_first']);
      data_entry_helper::$javascript .= "$('#sample\\\\:recorder_names').val('".$defaultUserData[0]['fullname_firstname_first']."');";
    }
    return data_entry_helper::textarea(array_merge(array(
      'fieldname' => 'sample:recorder_names',
      'label'=>lang::get('Recorder names')
    ), $options));
  }

  protected static function get_control_reviewinput($auth, $args, $tabAlias, $options) {
    return data_entry_helper::review_input($options);
  }

  /**
   * Get the control for the record status.
   */
  protected static function get_control_recordstatus($auth, $args) {
    $default = isset(data_entry_helper::$entity_to_load['occurrence:record_status']) ?
        data_entry_helper::$entity_to_load['occurrence:record_status'] :
        (isset($args['defaults']['occurrence:record_status']) ? $args['defaults']['occurrence:record_status'] : 'C');
    $values = array('I', 'C'); // not initially doing V=Verified
    $r = '<label for="occurrence:record_status">'.lang::get('LANG_Record_Status_Label')."</label>\n";
    $r .= '<select id="occurrence:record_status" name="occurrence:record_status">';
    foreach($values as $value){
      $r .= '<option value="'.$value.'"';
      if ($value == $default){
        $r .= ' selected="selected"';
      }
      $r .= '>'.lang::get('LANG_Record_Status_'.$value).'</option>';
    }
    $r .= "</select><br/>\n";
      return $r;
  }

  /**
   * Get the sensitivity control
   */
  protected static function get_control_sensitivity($auth, $args, $tabAlias, $options) {
    if ($args['multiple_occurrence_mode']==='single') {
      self::load_custom_occattrs($auth['read'], $args['survey_id']);
      $ctrlOptions = array('extraParams' => $auth['read']);
      $attrSpecificOptions = array();
      self::parseForAttrSpecificOptions($options, $ctrlOptions, $attrSpecificOptions);
      $sensitivity_controls = get_attribute_html(self::$occAttrs, $args, $ctrlOptions, 'sensitivity', $attrSpecificOptions);
      return data_entry_helper::sensitivity_input(array_merge(
        $options,
        array('additionalControls' => $sensitivity_controls)
      ));
    }
    else
      return "[sensitivity] control cannot be included in form when in grid entry mode, since photos are automatically included in the grid.";
  }

  /**
   * Get the zero abundance checkbox control
   */
  protected static function get_control_zeroabundance($auth, $args, $tabAlias, $options) {
    if ($args['multiple_occurrence_mode']==='single') {
      $options = array_merge(array(
        'label' => 'Zero Abundance',
        'fieldname' => 'occurrence:zero_abundance',
        'helpText' => 'Tick this box if this is a record that the species was not found.'
      ), $options);
      return data_entry_helper::checkbox($options);
    }
    else
      return "[zero abundance] control cannot be included in form when in grid entry mode.";
  }

  /*
   * A checkbox which when selected will set the record to having a Pending release_status. This is useful if (for instance) an
   * untrained user wishes to have their worked checked.
   * Control always defaults to selected.
   * Note this control has not been tested with and probably won't work with sub-samples mode.
   */
  protected static function get_control_pendingreleasecheckbox($auth, $args, $tabalias, $options) {
    if (empty($options['label']))
      $options['label']='Always override Release Status to be P (pending release)?';
    $r = '<div><input id="pending_release_status" type="checkbox" checked="checked" name="occurrence:release_status" value="P">'.$options['label'].'</div>';
    data_entry_helper::$javascript .= "
    $('#pending_release_status').change(function() {
      if ($('#pending_release_status').is(':checked')) {
        $('#pending_release_status').val('P');
      } else {
        $('#pending_release_status').val('');
      }
    });";
    return $r;
  }

  /**
   * Force senstivity on added records if the selected species in a given scratchpad.
   * Options are:
   * * @scratchpad_list_id - ID of the scratchpad_list containing the sensitive
   *   taxa.
   * * @taxon_list_ids - limit output to these lists (JSON). Will default to
   *   those configured for the form.
   * * @blur - level to blur to (100000, 10000, 2000, 1000).
   */
  protected static function get_control_sensitivityscratchpad($auth, $args, $tabalias, $options) {
    if (empty($options['scratchpad_list_id']) || !preg_match('/^\d+$/', trim($options['scratchpad_list_id']))) {
      throw new exception('The [sensitivity scratchpad] control needs an @scratchpad_list_id parameter containing ' .
        'the ID of the scratchpad list to load');
    }
    helper_base::addLanguageStringsToJs('sensitivityScratchpad', [
      'sensitiveMessage' => 'This species is sensitive so has been blurred for you.'
    ]);
    // Find the taxon lists this form uses. We can limit the taxa found
    // accordingly.
    $configuredLists = [];
    if ($args['taxon_list_id']) {
      $configuredLists[] = $args['taxon_list_id'];
    }
    if ($args['extra_list_id']) {
      $configuredLists[] = $args['extra_list_id'];
    }
    $options = array_merge([
      'blur' => 10000,
      'taxon_list_ids' => $configuredLists,
    ], $options);
    if (!preg_match('/^(100|10|2|1)000$/', trim($options['blur']))) {
      throw new exception('Invalid @blur setting for [sensitivity scratchpad]');
    }
    iform_load_helpers(['report_helper']);
    $data = report_helper::get_report_data([
      'dataSource' => 'library/taxa/taxa_taxon_list_ids_for_scratchpad',
      'readAuth' => $auth['read'],
      'extraParams' => [
        'scratchpad_list_id' => $options['scratchpad_list_id'],
        'taxon_list_ids' => implode(',', $options['taxon_list_ids']),
      ],
      'caching' => TRUE,
      'cachePerUser' => FALSE,
    ]);
    $idArray = [];
    foreach ($data as $row) {
      $idArray[] = $row['taxa_taxon_list_id'];
    }
    $idJson = json_encode($idArray);
    report_helper::$javascript .= <<<JS
indiciaData.scratchpadBlursTo = $options[blur];
indiciaData.scratchpadBlurList = $idJson;
indiciaFns.enableScratchpadBlurList();

JS;
    // No control HTML required.
    return '';
  }

  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values.
   * @param array $args iform parameters.
   * @param integer $nid The node's ID
   * @return array Submission structure.
   */
  public static function get_submission($values, $args, $nid) {
    // Any remembered fields need to be made available to the hook function outside this class.
    global $remembered;
    $remembered = isset($args['remembered']) ? $args['remembered'] : '';
    $extensions = array();
    if (isset($values['submission_extensions'])) {
      $extensions = $values['submission_extensions'];
      unset($values['submission_extensions']);
    }
    // default for forms setup on old versions is grid - list of occurrences
    // Can't call getGridMode in this context as we might not have the $_GET value to indicate grid
    if (isset($values['speciesgridmapmode']))
      $submission = data_entry_helper::build_sample_subsamples_occurrences_submission($values);
    else {
      // Work out the attributes that are for abundance, so could contain a zero
      $connection = iform_get_connection_details($nid);
      $readAuth = data_entry_helper::get_read_auth($connection['website_id'], $connection['password']);
      $abundanceAttrs = [];
      $occIdToLoad = isset(self::$loadedOccurrenceId) ? self::$loadedOccurrenceId : '';
      $occAttrs = self::getAttributesForEntity('occurrence', $args, $readAuth, $occIdToLoad);
      foreach ($occAttrs as &$attr) {
        if ($attr['system_function']==='sex_stage_count') {
          // If we have any lookups, we need to load the terms so we can compare the data properly
          // as term Ids are never zero
          if ($attr['data_type']==='L') {
            $attr['terms'] = data_entry_helper::get_population_data([
              'table' => 'termlists_term',
              'extraParams' => $readAuth + ['termlist_id' => $attr['termlist_id'], 'view' => 'cache', 'columns' => 'id,term'],
              'cachePerUser' => false
            ]);
          }
          $abundanceAttrs[$attr['attributeId']] = $attr;
        }
      }
      if (isset($values['gridmode'])) {
        $submission = data_entry_helper::build_sample_occurrences_list_submission($values, false, $abundanceAttrs);
      }
      else {
        $submission = data_entry_helper::build_sample_occurrence_submission($values, $abundanceAttrs);
      }
    }
    foreach ($extensions as $extension) {
      if (!empty($extension)) {
        $class_method = explode('.', "$extension");
        require_once("extensions/$class_method[0].php");
        $class_method[0] = "extension_$class_method[0]";
        // array on 3rd parameter is a way of forcing pass by reference
        call_user_func($class_method, $values, array(&$submission));
      }
    }
    return($submission);
  }

  /**
   * Retrieves a list of the css files that this form requires in addition to the standard
   * Drupal, theme or Indicia ones.
   *
   * @return array List of css files to include for this form.
   */
  public static function get_css() {
    return array('dynamic_sample_occurrence.css');
  }

  /**
   * Returns true if this form should be displaying a multiple occurrence entry grid.
   */
  protected static function getGridMode($args) {
    // if loading an existing sample and we are allowed to display a grid or single species selector
    if (isset($args['multiple_occurrence_mode']) && $args['multiple_occurrence_mode'] === 'either') {
      // Either we are in grid mode because we were instructed to externally, or because the form is reloading
      // after a validation failure with a hidden input indicating grid mode.
      return isset($_GET['gridmode']) ||
          isset(data_entry_helper::$entity_to_load['gridmode']) ||
          ((array_key_exists('sample_id', $_GET) && $_GET['sample_id'] !== '{sample_id}') &&
           (!array_key_exists('occurrence_id', $_GET) || $_GET['occurrence_id'] === '{occurrence_id}'));
    } else
      return
          // a form saved using a previous version might not have this setting, so default to grid mode=true
          (!isset($args['multiple_occurrence_mode'])) ||
          // Are we fixed in grid mode?
          $args['multiple_occurrence_mode']=='multi';
  }

  /**
   * When viewing the list of samples for this user, get the grid to insert into the page.
   */
  protected static function getSampleListGrid($args, $nid, $auth, $attributes) {
    global $user;
    // User must be logged in before we can access their records.
    if ($user->uid===0) {
      // Return a login link that takes you back to this form when done.
      return lang::get('Before using this facility, please <a href="'.hostsite_get_url('user/login', array('destination'=>"node/$nid")).'">login</a> to the website.');
    }
    $filter = array();
    // Get the CMS User ID attribute so we can filter the grid to this user
    foreach($attributes as $attrId => $attr) {
      if (strcasecmp($attr['caption'],'CMS User ID')==0) {
        $filter = array (
          'survey_id' => $args['survey_id'],
          'userID_attr_id' => $attr['attributeId'],
          'userID' => $user->uid,
          'iUserID' => 0);
        break;
      }
    }
    // Alternatively get the Indicia User ID and use that instead
    if (function_exists('hostsite_get_user_field')) {
      $iUserId = hostsite_get_user_field('indicia_user_id');
      if (isset($iUserId) && $iUserId!=false) $filter = array (
          'survey_id' => $args['survey_id'],
          'userID_attr_id' => 0,
          'userID' => 0,
          'iUserID' => $iUserId);
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
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Single').'" onclick="window.location.href=\''.hostsite_get_url('node/'.$nid, array('new' => '1')).'\'">';
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample_Grid').'" onclick="window.location.href=\''.hostsite_get_url('node/'.$nid, array('new' => '1', 'gridmode' => '1')).'\'">';
    } else {
      $r .= '<input type="button" value="'.lang::get('LANG_Add_Sample').'" onclick="window.location.href=\''.hostsite_get_url('node/'.$nid, array('new' => '1')).'\'">';
    }
    $r .= '</form>';
    return $r;
  }

  /**
   * When a form version is upgraded introducing new parameters, old forms will not get the defaults for the
   * parameters unless the Edit and Save button is clicked. So, apply some defaults to keep those old forms
   * working.
   */
  protected static function getArgDefaults($args) {
     if (!isset($args['structure']) || empty($args['structure']))
      $args['structure'] = "=Species=\r\n".
              "?Please enter the species you saw and any other information about them.?\r\n".
              "[species]\r\n".
              "[species attributes]\r\n".
              "[*]\r\n".
              "=Place=\r\n".
              "?Please provide the spatial reference of the record. You can enter the reference directly, or search for a place then click on the map.?\r\n".
              "[place search]\r\n".
              "[spatial reference]\r\n".
              "[map]\r\n".
              "[*]\r\n".
              "=Other Information=\r\n".
              "?Please provide the following additional information.?\r\n".
              "[date]\r\n".
              "[sample comment]\r\n".
              "[*]\r\n".
              "=*=";
    if (!isset($args['occurrence_comment']))
      $args['occurrence_comment'] == false;
    if (!isset($args['occurrence_images']))
      $args['occurrence_images'] == false;
    if (!isset($args['attribute_termlist_language_filter']))
      $args['attribute_termlist_language_filter'] == false;
    if (!isset($args['grid_report']))
      $args['grid_report'] = 'reports_for_prebuilt_forms/simple_sample_list_1';
    return $args;
  }

  protected static function getReportActions() {
    return array(array('display' => 'Actions', 'actions' =>
        array(array('caption' => lang::get('Edit'), 'url' => '{currentUrl}', 'urlParams' => array('sample_id' => '{sample_id}','occurrence_id' => '{occurrence_id}')))));
  }

  /**
   * Load the list of occurrence attributes into a static variable.
   *
   * By maintaining a single list of attributes we can track which have already been output.
   *
   * @param array $readAuth
   *   Read authorisation tokens.
   * @param integer $surveyId
   *   ID of the survey to load occurrence attributes for.
   * @return array
   *   List of occurrence attribute definitions.
   */
  protected static function load_custom_occattrs($readAuth, $surveyId) {
    if (!isset(self::$occAttrs)) {
      self::$occAttrs = self::getAttributesForEntity('occurrence', array('survey_id' => $surveyId), $readAuth,
        isset(data_entry_helper::$entity_to_load['occurrence:id']) ? data_entry_helper::$entity_to_load['occurrence:id'] : '');
    }
    return self::$occAttrs;
  }

  /**
   * Provides a control for inputting photos against the record, when in single record mode.
   *
   * @param array $readAuth Read authorisation tokens
   * @param array $options Options array for the control.
   * @param string $tabalias ID of the tab's div if this is being loaded onto a div.
   * @return string HTML for the control.
   */
  protected static function occurrence_photo_input($readAuth, $options, $tabalias) {
    $opts = array(
      'table' => 'occurrence_medium',
      'readAuth' => $readAuth,
      'resizeWidth' => 1600,
      'resizeHeight' => 1600
    );
    if ($tabalias)
      $opts['tabDiv']=$tabalias;
    foreach ($options as $key => $value) {
      // skip attribute specific options as they break the JavaScript.
      if (strpos($key, ':')===false)
        $opts[$key]=$value;
    }
    return data_entry_helper::file_box($opts);
  }

  /**
   * Parses the options provided to a control in the user interface definition and splits the options which
   * apply to the entire control (@label=Grid Ref) from ones which apply to a specific custom attribute
   * (smpAttr:3|label=Quantity).
   */
  protected static function parseForAttrSpecificOptions($options, &$ctrlOptions, &$attrSpecificOptions) {
    // look for options specific to each attribute
    foreach ($options as $option => $value) {
      // split the id of the option into the control name and option name.
      if (strpos($option, '|')!==false) {
        $optionId = explode('|', $option);
        if (!isset($attrSpecificOptions[$optionId[0]])) $attrSpecificOptions[$optionId[0]]=array();
        $attrSpecificOptions[$optionId[0]][$optionId[1]] = $value;
      } else {
        $ctrlOptions[$option]=$value;
      }
    }
  }

  /**
   * Override the default submit buttons to add a delete button where appropriate.
   */
  protected static function getSubmitButtons($args) {
    global $indicia_templates;
    $r = '';
    if(self::$mode === self::MODE_EXISTING_RO) return $r; // don't allow users to submit if in read only mode.
    $r .= '<input type="submit" class="' . $indicia_templates['buttonHighlightedClass'] . '" id="save-button" value="'.lang::get('Submit')."\" />\n";
    if (!empty(self::$loadedSampleId)) {
      // use a button here, not input, as Chrome does not post the input value
      $formType = $args['multiple_occurrence_mode'] === 'single' ? lang::get('record') : lang::get('list of records');
      $btnLabel = lang::get('Delete {1}', $formType);
      $r .= <<<HTML
<button type="submit" class="$indicia_templates[buttonWarningClass]" id="delete-button" name="delete-button" value="delete" >
  $btnLabel
</button>

HTML;
      $msg = str_replace("'", "\'", lang::get('Are you sure you want to delete this {1}?', $formType));
      data_entry_helper::$javascript .= <<<JS
$('#delete-button').click(function(e) {
  if (!confirm('$msg')) {
    e.preventDefault();
    return false;
  }
});

JS;
    }
    return $r;
  }

}

/**
 * A hook function to setup remembered fields whose values are stored in a cookie.
 */
function indicia_define_remembered_fields() {
  global $remembered;
  $remembered = trim($remembered);
  if (!empty($remembered))
    data_entry_helper::setRememberedFields(helper_base::explode_lines($remembered));
}


