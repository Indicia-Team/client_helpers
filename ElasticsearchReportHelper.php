<?php

/**
 * @file
 * A helper class for Elasticsearch reporting code.
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
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/indicia-team/client_helpers
 */

use IForm\IndiciaConversions;

/**
 * A helper class for Elasticsearch reporting code.
 */
class ElasticsearchReportHelper {

  /**
   * Count controls to make unique IDs.
   *
   * @var int
   */
  private static $controlIndex = 0;

  /**
   * Track control IDs so warning can be given if duplicate IDs are used.
   *
   * @var array
   */
  private static $controlIds = [];

  private static $esMappings;

  /**
   * Has the ES proxy been setup on this page?
   *
   * @var bool
   *   Set to true when done to prevent double-initialisation.
   */
  private static $proxyEnabled = FALSE;

  /**
   * Remember if an attempt made to enable which failed.
   *
   * @var bool
   */
  private static $proxyEnableFailed = FALSE;

  /**
   * List of ES fields with caption and description for each.
   *
   * @internal
   */
  const MAPPING_FIELDS = [
    '@timestamp' => [
      'caption' => 'Indexing timestamp',
      'description' => 'Timestamp when the record was indexed into to the reporting system.',
    ],
    'id' => [
      'caption' => 'ID',
      'description' => 'Unique record ID.',
    ],
    'event.event_id' => [
      'caption' => 'Sample ID',
      'description' => 'Unique sample ID.',
    ],
    '#datasource_code#' => [
      'caption' => 'Datasource codes',
      'description' => 'Website and survey dataset the record is sourced from, in abbreviated encoded form.',
    ],
    '#status_icons#' => [
      'caption' => 'Record status icons',
      'description' => "Icons showing the record's verification status and sensitivity information.",
    ],
    '#data_cleaner_icons#' => [
      'caption' => 'Automated checks',
      'description' => "Icons showing the results of automated checks on the record.",
    ],
    'metadata.created_on' => [
      'caption' => 'Submitted on',
      'description' => 'Date the record was submitted.',
    ],
    'metadata.licence_code' => [
      'caption' => 'Licence',
      'description' => 'Code for the licence that applies to the record.',
    ],
    'metadata.website.id' => [
      'caption' => 'Website ID',
      'description' => 'Unique ID of the website the record was submitted from.',
    ],
    'metadata.website.title' => [
      'caption' => 'Website title',
      'description' => 'Title of the website the record was submitted from.',
    ],
    'metadata.survey.id' => [
      'caption' => 'Survey dataset ID',
      'description' => 'Unique ID of the survey dataset the record was submitted to.',
    ],
    'metadata.survey.title' => [
      'caption' => 'Survey dataset title',
      'description' => 'Title of the survey dataset the record was submitted to.',
    ],
    'metadata.group.id' => [
      'caption' => 'Group ID',
      'description' => 'Unique ID of the recording group the record was submitted to.',
    ],
    'metadata.group.title' => [
      'caption' => 'Group title',
      'description' => 'Title of the recording group the record was submitted to.',
    ],
    'metadata.import_guid' => [
      'caption' => 'Import GUID',
      'description' => 'Unique identifier for the import that this records was added by, if relevant.',
    ],
    '#event_date#' => [
      'caption' => 'Date',
      'description' => 'Date of the record.',
    ],
    'event.day_of_year' => [
      'caption' => 'Day of year',
      'description' => 'Numeric day within the year of the record (1-366).',
    ],
    'event.month' => [
      'caption' => 'Month',
      'description' => 'Numeric month of the record.',
    ],
    'event.year' => [
      'caption' => 'Year',
      'description' => 'Year of the record.',
    ],
    'event.event_remarks' => [
      'caption' => 'Sample comment',
      'description' => 'Comment given for the sample by the recorder.',
    ],
    'event.habitat' => [
      'caption' => 'Habitat',
      'description' => 'Habitat recorded for the sample.',
    ],
    'event.recorded_by' => [
      'caption' => 'Recorder name(s)',
      'description' => 'Name of the people involved in the field record.',
    ],
    'event.sampling_protocol' => [
      'caption' => 'Sample method',
      'description' => 'Method for the sample if provided.',
    ],
    'identification.identified_by' => [
      'caption' => 'Identified by',
      'description' => 'Identifier (determiner) of the record.',
    ],
    'identification.recorder_certainty' => [
      'caption' => 'Recorder certainty',
      'description' => 'Certainty that the identification is correct as attributed by the recorder.',
    ],
    'identification.verifier.name' => [
      'caption' => 'Verifier name',
      'description' => "Name of the verifier responsible for record's current verification status.",
    ],
    'identification.verified_on' => [
      'caption' => 'Verified on',
      'description' => "Date/time of the current verification decision.",
    ],
    'identification.verification_decision_source' => [
      'caption' => 'Verification decision source',
      'description' => 'Either M for machine based verification or H for human verification decisions.',
    ],
    'taxon.taxon_name' => [
      'caption' => 'Taxon name',
      'description' => 'Name as recorded for the taxon.',
    ],
    'taxon.taxon_name_authorship' => [
      'caption' => 'Taxon name author',
      'description' => 'Author and date of the recorded accepted name.',
    ],
    'taxon.accepted_name' => [
      'caption' => 'Accepted name',
      'description' => 'Currently accepted name for the recorded taxon.',
    ],
    'taxon.accepted_name_authorship' => [
      'caption' => 'Accepted name author',
      'description' => 'Author and date of the published accepted name.',
    ],
    'taxon.vernacular_name' => [
      'caption' => 'Common name',
      'description' => 'Common name for the recorded taxon.',
    ],
    '#taxon_label#' => [
      'caption' => 'Taxon label',
      'description' => 'Combination of accepted and common name.',
    ],
    'taxon.group' => [
      'caption' => 'Taxon group',
      'description' => 'Taxon reporting group associated with the current identification of this record.',
    ],
    'taxon.kingdom' => [
      'caption' => 'Kingdom',
      'description' => 'Taxonomic kingdom associated with the current identification of this record.',
    ],
    'taxon.phylum' => [
      'caption' => 'Phylum',
      'description' => 'Taxonomic phylum associated with the current identification of this record.',
    ],
    'taxon.class' => [
      'caption' => 'Class',
      'description' => 'Taxonomic class associated with the current identification of this record.',
    ],
    'taxon.order' => [
      'caption' => 'Order',
      'description' => 'Taxonomic order associated with the current identification of this record.',
    ],
    'taxon.family' => [
      'caption' => 'Family',
      'description' => 'Taxonomic family associated with the current identification of this record.',
    ],
    'taxon.subfamily' => [
      'caption' => 'Subfamily',
      'description' => 'Taxonomic subfamily associated with the current identification of this record.',
    ],
    'taxon.taxon_rank' => [
      'caption' => 'Taxon rank',
      'description' => 'Taxonomic rank associated with the current identification of this record.',
    ],
    'taxon.genus' => [
      'caption' => 'Genus',
      'description' => 'Taxonomic genus associated with the current identification of this record.',
    ],
    'taxon.species' => [
      'caption' => 'Species',
      'description' => 'Species name associated with the current identification of this record. Will still return the species ranked name where the record is of a taxon below species level.',
    ],
    'taxon.species_authorship' => [
      'caption' => 'Species name author',
      'description' => 'Species name author and date associated with the current identification of this record. Will still return the species ranked name\'s author where the record is of a taxon below species level.',
    ],
    'taxon.species_vernacular' => [
      'caption' => 'Species common name',
      'description' => 'Species name associated with the current identification of this record. Will still return the species ranked name where the record is of a taxon below species level.',
    ],
    'location.verbatim_locality' => [
      'caption' => 'Location name',
      'description' => 'Location name associated with the record.',
    ],
    'location.name' => [
      'caption' => 'Location',
      'description' => 'Location associated with the record where the record was linked to a defined location.',
    ],
    'location.location_id' => [
      'caption' => 'Location ID',
      'description' => 'Unique ID of the location associated with the record where the record was linked to a defined location.',
    ],
    'location.parent_name' => [
      'caption' => 'Parent location',
      'description' => 'Parent location associated with the record where the record was linked to a defined location which has a hierarchical parent.',
    ],
    'location.parent_location_id' => [
      'caption' => 'Parent location ID',
      'description' => 'Unique ID of the parent location associated with the record where the record was linked to a defined location which has a hierarchical parent.',
    ],
    'location.output_sref' => [
      'caption' => 'Display spatial reference',
      'description' => 'Spatial reference in the recommended local grid system.',
    ],
    'location.output_sref_system' => [
      'caption' => 'Display spatial reference system',
      'description' => 'System used for the spatial reference in the recommended local grid system.',
    ],
    'location.input_sref' => [
      'caption' => 'Input spatial reference',
      'description' => 'Spatial reference as input by the recorder.',
    ],
    'location.input_sref_system' => [
      'caption' => 'Input spatial reference system',
      'description' => 'System used for the spatial reference as input by the recorder.',
    ],
    'location.coordinate_uncertainty_in_meters' => [
      'caption' => 'Coordinate uncertainty in metres',
      'description' => 'Uncertainty of a provided GPS point.',
    ],
    '#lat_lon#' => [
      'caption' => 'Lat/lon',
      'description' => 'Latitude and longitude of the record.',
    ],
    '#occurrence_media#' => [
      'caption' => 'Media',
      'description' => 'Thumbnails for any occurrence photos and other media.',
    ],
    'occurrence.sex' => [
      'caption' => 'Sex',
      'description' => 'Sex of the recorded organism',
    ],
    'occurrence.life_stage' => [
      'caption' => 'Life stage',
      'description' => 'Life stage of the recorded organism.',
    ],
    'occurrence.individual_count' => [
      'caption' => 'Count',
      'description' => 'Numeric abundance count of the recorded organism.',
    ],
    'occurrence.organism_quantity' => [
      'caption' => 'Quantity',
      'description' => 'Abundance of the recorded organism (numeric or text).',
    ],
    'occurrence.occurrence_remarks' => [
      'caption' => 'Occurrence comment',
      'description' => 'Comment given for the occurrence by the recorder.',
    ],
  ];

  /**
   * Prepares the page for interacting with the Elasticsearch proxy.
   *
   * @param int $nid
   *   Node ID or NULL if not on a node.
   *
   * @return bool
   *   True if enabled, false if there was an error and it failed to enable.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-enableelasticsearchproxy
   */
  public static function enableElasticsearchProxy($nid = NULL) {
    if (!self::$proxyEnabled && !self::$proxyEnableFailed) {
      // Retrieve the Elasticsearch mappings.
      try {
        $config = hostsite_get_es_config($nid);
        self::getMappings($config);
        helper_base::add_resource('datacomponents');
        // Prepare the stuff we need to pass to the JavaScript.
        $mappings = self::$esMappings;
        $esProxyAjaxUrl = hostsite_get_url('iform/esproxy');
        helper_base::$indiciaData['esProxyAjaxUrl'] = $esProxyAjaxUrl;
        helper_base::$indiciaData['esSources'] = [];
        helper_base::$indiciaData['esMappings'] = $mappings;
        helper_base::$indiciaData['gridMappingFields'] = self::MAPPING_FIELDS;
        helper_base::$indiciaData['esVersion'] = (int) $config['es']['version'];
        helper_base::$indiciaData['esScope'] = $config['es']['scope'];
        self::$proxyEnabled = TRUE;
      }
      catch (Exception $e) {
        self::$proxyEnableFailed = TRUE;
        \Drupal::logger('iform')->error('Elasticsearch proxy enable failed: ' . $e->getMessage());
      }
    }
    return self::$proxyEnabled;
  }

  /**
   * An Elasticsearch bulk editor tool.
   *
   * @return string
   *   Button container HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-bulkeditor
   */
  public static function bulkEditor(array $options) {
    self::checkOptions(
      'bulkEditor',
      $options,
      ['linkToDataControl'],
      []
    );
    $options = array_merge([
      'caption' => 'Bulk edit records',
      'restrictToOwnData' => TRUE,
    ], $options);
    $dataOptions = helper_base::getOptionsForJs($options, [
      'id',
      'linkToDataControl',
      'restrictToOwnData',
    ], TRUE);
    helper_base::addLanguageStringsToJs('bulkEditor', [
      'allowSampleSplitting' => 'Allow sample splitting?',
      'bulkEditorDialogMessageAll' => 'You are about to edit the entire list of {1} records.',
      'bulkEditorDialogMessageSelected' => 'You are about to edit {1} selected records.',
      'bulkEditProgress' => 'Edited {samples} samples and {occurrences} occurrences.',
      'cannotProceed' => 'Cannot proceed',
      'confirm' => 'Confirm',
      'done' => 'Records successfully edited. They will now be processed so they are available with their new values shortly.',
      'error' => 'An error occurred whilst trying to edit the records.',
      'errorEditNotFilteredToCurrentUser' => 'The records cannot be edited because the current page is not filtered to limit the records to only your data.',
      'preparing' => 'Preparing to edit the records...',
      'promptAllowSampleSplit' => '<p>The list of records to update contains occurrences which belong to samples that contain other occurrences which are not being updated. ' .
        'For example, sample {1} contains an occurrence {2} which is being updated, but it also contains occurrence {3} which is not being updated.</p>' .
        '<p>Please confirm that you would like to split the samples so that the data values for the list of records you are editing can be updated without affecting other occurrences in the same samples.</p>',
      'warningNoChanges' => 'Please define at least one field value that you would like to change when bulk editing the records.',
      'warningNothingToDo' => 'There are no selected records to edit.',
    ]);
    $lang = [
      'bulkEditRecords' => lang::get($options['caption']),
      'cancel' => lang::get('Cancel'),
      'close' => lang::get('Close'),
      'editing' => lang::get('Editing records'),
      'editInstructions' => 'Specify values to apply to all the edited records in the following controls, or leave blank for the data values to remain unchanged.',
      'proceed' => lang::get('Proceed'),
    ];
    helper_base::add_resource('fancybox');
    $recorderNameControl = data_entry_helper::text_input([
      'fieldname' => 'edit-recorder-name',
      'label' => lang::get('Recorder name'),
      'attributes' => ['placeholder' => lang::get('value not changed')],
    ]);
    $dateControl = data_entry_helper::text_input([
      'fieldname' => 'edit-date',
      'label' => lang::get('Date'),
      'attributes' => ['placeholder' => lang::get('value not changed')],
    ]);
    $locationNameControl = data_entry_helper::text_input([
      'fieldname' => 'edit-location-name',
      'label' => lang::get('Location name'),
      'attributes' => ['placeholder' => lang::get('value not changed')],
    ]);
    $srefControl = data_entry_helper::sref_and_system([
      'fieldname' => 'edit-sref',
      'label' => lang::get('Spatial reference'),
      'attributes' => ['placeholder' => lang::get('value not changed')],
      'findMeButton' => FALSE,
    ]);
    global $indicia_templates;
    $html = <<<HTML
<button type="button" class="bulk-edit-records-btn $indicia_templates[buttonHighlightedClass]">$lang[bulkEditRecords]</button>
<div style="display: none">
  <div id="$options[id]-dlg" class="bulk-editor-dlg">
    <div class="pre-bulk-edit-info">
      <h2>$lang[bulkEditRecords]</h2>
      <p class="message"></p>
      <p>$lang[editInstructions]</p>
      $recorderNameControl
      $dateControl
      $locationNameControl
      $srefControl
      <div class="form-buttons">
        <button type="button" class="$indicia_templates[buttonHighlightedClass] proceed-bulk-edit">$lang[proceed]</button>
        <button type="button" class="$indicia_templates[buttonHighlightedClass] close-bulk-edit-dlg">$lang[cancel]</button>
      </div>
    </div>
    <div class="post-bulk-edit-info">
      <h2>$lang[editing]</h2>
      <div class="output"></div>
      <div class="form-buttons">
        <button type="button" class="$indicia_templates[buttonHighlightedClass] close-bulk-edit-dlg" disabled="disabled">$lang[close]</button>
      </div>
    </div>
  </div>
</div>
HTML;
    return self::getControlContainer('bulkEditor', $options, $dataOptions, $html);
  }

  /**
   * An Elasticsearch records card gallery.
   *
   * @return string
   *   Gallery container HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-cardgallery
   */
  public static function cardGallery(array $options) {
    self::checkOptions(
      'cardGallery',
      $options,
      ['source'],
      [
        'actions',
        'columns',
        'rowsPerPageOptions',
      ]
    );
    $options = array_merge([
      'class' => 'flexgrid',
    ], $options);
    helper_base::addLanguageStringsToJs('cardGallery', [
      'checkToIncludeInList' => 'Check this box to include the record in the list which any verification actions will be applied to.',
      'collapseCard' => 'Return card to normal size (C or +)',
      'expandCard' => 'Expand this card (C or +)',
      'fullScreenToolHint' => 'Click to view grid in full screen mode',
      'noHeading' => 'no heading',
      'clickToSort' => 'Click on the data value to sort by:',
      'sortConfiguration' => 'Sort configuration',
      'sortToolHint' => 'Click to select the sort order',
    ]);
    $lang = [
      'next' => lang::get('Next record'),
      'prev' => lang::get('Previous record'),
    ];
    // Map options alias, so consistent with dataGrid.
    if (isset($options['sortable']) && !isset($options['includeSortTool'])) {
      $options['includeSortTool'] = $options['sortable'];
    }
    $dataOptions = helper_base::getOptionsForJs($options, [
      'actions',
      'columns',
      'class',
      'includeFieldCaptions',
      'includeFullScreenTool',
      'includeMultiSelectTool',
      'includePager',
      'includeSortTool',
      'keyboardNavigation',
      'rowsPerPageOptions',
      'source',
    ], TRUE);
    // Extra setup required after gallery loads.
    helper_base::$late_javascript .= <<<JS
$('#$options[id]').idcCardGallery('bindControls');

JS;
    return self::getControlContainer('cardGallery', $options, $dataOptions) . <<<HTML
<div id="card-nav-buttons-cntr" style="display: none">
  <div id="card-nav-buttons">
    <button class="nav-prev indicia-button" title="$lang[prev]"><span class="fas fa-caret-left"></span></button>
    <button class="nav-next indicia-button" title="$lang[next]"><span class="fas fa-caret-right"></span></button>
  </div>
</div>
HTML;
  }

  /**
   * A control for managing layout, e.g. for verification pages.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-controllayout
   */
  public static function controlLayout(array $options) {
    self::checkOptions(
      'controlLayout',
      $options,
      ['setOriginY'],
      [
        'alignTop',
        'alignBottom',
        'setHeightPercent',
      ]
    );
    $options = array_merge([
      'breakpoint' => 992,
      'alignTop' => [],
      'alignBottom' => [],
      'setHeightPercent' => [],
    ], $options);
    $options = array_intersect_key($options, [
      'alignTop' => NULL,
      'alignBottom' => NULL,
      'breakpoint' => NULL,
      'setHeightPercent' => NULL,
      'setOriginY' => NULL,
    ]);
    helper_base::$indiciaData['esControlLayout'] = $options;
  }

  /**
   * A control for flexibly outputting data formatted using a custom script.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-customscript
   */
  public static function customScript(array $options) {
    self::checkOptions('customScript', $options, ['source', 'functionName'], []);
    $options = array_merge([
      'template' => '',
    ], $options);
    $dataOptions = helper_base::getOptionsForJs($options, [
      'source',
      'functionName',
    ], TRUE);
    return self::getControlContainer('customScript', $options, $dataOptions, $options['template']);
  }

  /**
   * An Elasticsearch or Indicia powered grid control.
   *
   * @return string
   *   Grid container HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-datagrid
   */
  public static function dataGrid(array $options) {
    self::checkOptions(
      'dataGrid',
      $options,
      ['source'],
      [
        'actions',
        'applyFilterRowToSources',
        'availableColumns',
        'columns',
        'responsiveOptions',
        'rowClasses',
        'rowsPerPageOptions',
      ]
    );
    if (!empty($options['scrollY']) && !preg_match('/^-?\d+px$/', $options['scrollY'])) {
      throw new Exception('Control [dataGrid] @scrollY parameter must be of CSS pixel format, e.g. 100px');
    }
    if (isset($options['columns'])) {
      foreach ($options['columns'] as &$columnDef) {
        if (empty($columnDef['field'])) {
          throw new Exception('Control [dataGrid] @columns option does not contain a field for every item.');
        }
        if (!isset($columnDef['caption'])) {
          $columnDef['caption'] = '';
        }
        // To aid transition from older code versions, auto-enable the media
        // special field handling. This may be removed in future.
        if ($columnDef['field'] === 'occurrence.media') {
          $columnDef['field'] = '#occurrence_media#';
        }
      }
    }
    helper_base::add_resource('sortable');
    helper_base::add_resource('font_awesome');
    helper_base::add_resource('indiciaFootableReport');
    // Add footableSort for simple aggregation tables.
    if (!empty($options['aggregation']) && $options['aggregation'] === 'simple') {
      helper_base::add_resource('footableSort');
    }
    // Fancybox for image popups.
    helper_base::add_resource('fancybox');
    helper_base::addLanguageStringsToJs('dataGrid', [
      'checkToIncludeInList' => 'Check this box to include the record in the list which any verification actions will be applied to.',
      'columnSettingsToolHint' => 'Click to show grid column settings',
      'fullScreenToolHint' => 'Click to view grid in full screen mode',
      'noHeading' => 'no heading',
      'siteNameWitheld' => 'Site name witheld as record is sensitive or private.',
      'status' => 'Status',
    ]);
    $dataOptions = helper_base::getOptionsForJs($options, [
      'actions',
      'applyFilterRowToSources',
      'availableColumns',
      'autoResponsiveCols',
      'autoResponsiveExpand',
      'columns',
      'cookies',
      'includeColumnHeadings',
      'includeColumnSettingsTool',
      'includeFilterRow',
      'includeFullScreenTool',
      'includeMultiSelectTool',
      'includePager',
      'keyboardNavigation',
      'responsive',
      'responsiveOptions',
      'rowClasses',
      'rowsPerPageOptions',
      'scrollY',
      'source',
      'sortable',
    ], TRUE);
    // Extra setup required after grid loads.
    helper_base::$late_javascript .= <<<JS
$('#$options[id]').idcDataGrid('bindControls');

JS;
    $lang = [
      'cancel' => lang::get('Cancel'),
      'columnConfigIntro' => lang::get('The following columns are available for this table. Tick the ones you want to include. Drag and drop the columns into your preferred order.'),
      'columnConfiguration' => lang::get('Column configuration'),
      'restoreDefaults' => lang::get('Restore defaults'),
      'save' => lang::get('Save'),
      'toggleTick' => lang::get('Tick/untick all'),
    ];
    $content = <<<HTML
    <div class="loading-spinner" style="display: none">
      <div>Loading...</div>
    </div>
    <div class="data-grid-settings-cntr" style="display: none">
      <div class="data-grid-settings" data-el="$options[id]">
        <h3>$lang[columnConfiguration]</h3>
        <p>$lang[columnConfigIntro]</p>
        <div>
          <button class="btn btn-default toggle">$lang[toggleTick]</button>
          <button class="btn btn-default restore">$lang[restoreDefaults]</button>
          <button class="btn btn-default cancel">$lang[cancel]</button>
          <button class="btn btn-primary save">$lang[save]</button>
        </div>
        <ol></ol>
      </div>
    </div>
HTML;
    return self::getControlContainer('dataGrid', $options, $dataOptions, $content);
  }

  /**
   * A button for downloading the ES data from a source.
   *
   * @return string
   *   HTML for download button and progress display.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-download
   */
  public static function download(array $options) {
    // Compatibility with legacy config.
    if (!empty($options['linkToDataGrid'])) {
      $options['linkToDataControl'] = $options['linkToDataGrid'];
    }
    self::checkOptions('esDownload', $options,
      [['source', 'linkToDataControl']],
      ['addColumns', 'removeColumns', 'sort']
    );
    if (empty($options['source']) && !empty($options['columnsTemplate'])) {
      throw new Exception('Download control @source option must be specified if @columnsTemplate option is used (cannot be used with @linkToDataControl).');
    }

    $options = array_merge([
      'caption' => 'Download',
      'title' => 'Download information',
    ], $options);

    // If columnsTemplate options specifies an array, then create control
    // options for a select control that will be used to indicate the selected
    // columns template.
    if (!empty($options['columnsTemplate']) && is_array($options['columnsTemplate'])) {
      $availableColTypes = [
        "easy-download" => lang::get("Standard download format"),
        "mapmate" => lang::get("Simple download format"),
      ];
      $optionArr = [];
      foreach ($options['columnsTemplate'] as $colType) {
        $optionArr[$colType] = $availableColTypes[$colType];
      }
      $controlOptions = [
        'id' => "$options[id]-template",
        'fieldname' => 'columnsTemplate',
        'lookupValues' => $optionArr,
      ];
      unset($options['columnsTemplate']);
    }

    global $indicia_templates;
    $button = str_replace(
      [
        '{id}',
        '{title}',
        '{class}',
        '{caption}',
      ], [
        "$options[id]-button",
        lang::get($options['title']),
        "class=\"$indicia_templates[buttonHighlightedClass] do-download\"",
        lang::get($options['caption']) . '<span class="fas fa-file-download"></span>',
      ],
      $indicia_templates['button']
    );
    if (isset($controlOptions)) {
      $html = "<div class=\"idc-download-ctl-part\">$button</div>";
      $html .= '<div class="idc-download-ctl-part">' . data_entry_helper::select($controlOptions) . '</div>';
    }
    else {
      $html = $button;
    }
    $progress = <<<HTML
<div class="progress-circle-container">
  <svg>
    <circle class="circle"
            cx="-90"
            cy="90"
            r="80"
            style="stroke-dashoffset:503px;"
            stroke-dasharray="503"
            transform="rotate(-90)" />
  </svg>
  <div class="progress-text"></div>
</div>

HTML;
    $html .= str_replace(
      [
        '{attrs}',
        '{col-1}',
        '{col-2}',
      ], [
        '',
        $progress,
        '<div class="idc-download-files"><h2>' . lang::get('Files') . ':</h2></div>',
      ],
      $indicia_templates['two-col-50']);
    // This does nothing at the moment - just a placeholder for if and when we
    // add some download options.
    $dataOptions = helper_base::getOptionsForJs($options, [
      'addColumns',
      'aggregation',
      'buttonContainerElement',
      'columnsTemplate',
      'columnsSurveyId',
      'linkToDataControl',
      'removeColumns',
      'sort',
      'source',
    ], TRUE);
    return self::getControlContainer('esDownload', $options, $dataOptions, $html);
  }

  /**
   * A scale for grid square opacity.
   *
   * Showing the number of records for each level.
   *
   * @param array $options
   *   Control options.
   *
   * @return string
   *   Scale container HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-gridsquareopacityscale
   */
  public static function gridSquareOpacityScale(array $options) {
    self::checkOptions('gridSquareOpacityScale', $options,
      ['linkToDataControl', 'layer'],
      []
    );
    helper_base::addLanguageStringsToJs('gridSquareOpacityScale', [
      'noOfRecords' => 'No. of records',
    ]);
    $dataOptions = helper_base::getOptionsForJs($options, [
      'id',
      'linkToDataControl',
      'layer',
    ], TRUE);
    return self::getControlContainer('gridSquareOpacityScale', $options, $dataOptions);
  }

  /**
   * Integrates the page with groups (activities).
   *
   * @param array $options
   *   Control options.
   * @param bool $checkPage
   *   Set to false to disable checking that the current page path is an iform
   *   page linked to the group.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-groupintegration
   *
   * @return string
   *   Control HTML
   */
  public static function groupIntegration(array $options, $checkPage = TRUE) {
    $options = array_merge([
      'missingGroupIdBehaviour' => 'error',
      'showGroupSummary' => FALSE,
      'showGroupPages' => FALSE,
    ], $options);
    if (isset($options['group_id'])) {
      $group_id = $options['group_id'];
      $implicit = array_key_exists('implicit', $options) ? $options['implicit'] : FALSE;
    }
    elseif (!empty($_GET['group_id'])) {
      $group_id = $_GET['group_id'];
      $implicit = array_key_exists('implicit', $_GET) ? $_GET['implicit'] : FALSE;
    }
    if (empty($group_id) && $options['missingGroupIdBehaviour'] !== 'showAll') {
      hostsite_show_message(lang::get('The link you have followed is invalid.'), 'warning', TRUE);
      hostsite_goto_page('<front>');
      return '';
    }
    require_once 'prebuilt_forms/includes/groups.php';
    $membership = group_get_user_membership($group_id, $options['readAuth'], $checkPage);
    $output = '';
    if (!empty($group_id)) {
      $groups = data_entry_helper::get_population_data([
        'table' => 'group',
        'extraParams' => $options['readAuth'] + [
          'view' => 'detail',
          'id' => $group_id,
        ]
      ]);
      if (!count($groups)) {
        hostsite_show_message(lang::get('The link you have followed is invalid.'), 'warning', TRUE);
        hostsite_goto_page('<front>');
        return '';
      }
      $group = $groups[0];
      // Apply filtering by group.
      $groupFilterInfo = [
        'id' => $group['id'],
        'implicit' => IndiciaConversions::toBool($implicit),
        'container' => IndiciaConversions::toBool($group['container']),
        'contained_by_group_id' => $group['contained_by_group_id'],
      ];
      helper_base::$indiciaData['applyGroupFilter'] = $groupFilterInfo;
      if ($options['showGroupSummary'] || $options['showGroupPages']) {
        if ($options['showGroupSummary']) {
          $output .= self::getGroupSummaryHtml($group);
        }
        if ($options['showGroupPages']) {
          $output .= self::getGroupPageLinksHtml($group, $options, $membership);
        }
      }
      $filterBoundaries = helper_base::get_population_data([
        'report' => 'library/groups/group_boundary_transformed',
        'extraParams' => $options['readAuth'] + ['group_id' => $group_id],
        'cachePerUser' => FALSE,
      ]);
      if (count($filterBoundaries) > 0) {
        helper_base::$indiciaData['reportBoundaries'] = [];
        foreach ($filterBoundaries as $boundary) {
          helper_base::$indiciaData['reportBoundaries'][] = $boundary['boundary'];
        }
        helper_base::$late_javascript .= <<<JS
indiciaFns.loadReportBoundaries();

JS;
      }
    }
    return $output;
  }

  /**
   * Return the HTML for a summary panel for a group.
   *
   * @param array $group
   *   Group data loaded from the database.
   *
   * @return string
   *   HTML for the panel.
   */
  public static function getGroupSummaryHtml(array $group) {
    $path = data_entry_helper::get_uploaded_image_folder();
    $logo = empty($group['logo_path']) ? '' : "<img style=\"width: 30%; float: left; padding: 0 5% 5%;\" alt=\"Logo\" src=\"$path$group[logo_path]\"/>";
    $msg = "<h3>$group[title]</h3>";
    if (!empty($group['description'])) {
      $msg .= "<p>$group[description]</p>";
    }
    return $logo . $msg;
  }

  /**
   * Return an array with information required to create a group's page links.
   *
   * @param array $group
   *   Group data loaded from the database.
   * @param array $options
   *   [groupIntegration] control options. Can include joinLink=true to add a
   *   link for joining for non-members and a class name for the links in
   *   linkClass. Provide an option called editPath with a path to the group
   *   edit page, this will generate a link for admins to edit the group
   *   metadata.
   * @param GroupMembership $membership
   *   Current user's membership or admin status.
   *
   * @return array
   *   List of links with label and icon info.
   */
  public static function getGroupPageLinksArray(array $group, array $options, GroupMembership $membership): array {
    $pageData = data_entry_helper::get_population_data([
      'table' => 'group_page',
      'extraParams' => $options['readAuth'] + [
        'group_id' => $group['id'],
        'query' => json_encode(['in' => ['administrator' => ['', 'f']]]),
        'orderby' => 'caption',
      ],
    ]);
    $links = [];
    $options = array_merge([
      'containedGroupLabel' => 'sub-group',
    ], $options);
    if ($membership === GroupMembership::NonMember && ($group['joining_method'] === 'P' || $group['joining_method'] === 'I')) {
      $titleForLink = trim(preg_replace('/[^a-z0-9\-]/', '', preg_replace('/[ ]/', '-', strtolower($group['title']))), '-');
      $titleEscaped = htmlspecialchars($group['title']);
      $links["/join/$titleForLink"] = ['label' => "Join $titleEscaped"];
    }
    if ($membership === GroupMembership::Admin && isset($options['editPath'])) {
      $editLink = helper_base::getRootFolder() . $options['editPath'] . "?group_id=$group[id]&redirect_on_success=" . hostsite_get_current_page_path();
      $links[$editLink] = ['label' => lang::get('Edit'), 'icon' => '<i class="fas fa-pen"></i>'];
      if (!empty($group['container'])) {
        $addSubGroupLink = helper_base::getRootFolder() . $options['editPath'] . "?container_group_id=$group[id]&redirect_on_success=" . hostsite_get_current_page_path();
        $links[$addSubGroupLink] = ['label' => lang::get('Add {1}', $options['containedGroupLabel']), 'icon' => '<i class="fas fa-folder-plus"></i>'];
      }
    }
    $thisPage = empty($options['nid']) ? '' : hostsite_get_alias($options['nid']);
    foreach ($pageData as $page) {
      // Don't link to the current page, plus block member-only pages for
      // non-members.
      if ($page['path'] !== $thisPage && ($membership !== GroupMembership::NonMember || $page['administrator'] === NULL)) {
        $pageLink = hostsite_get_url($page['path'], [
          'group_id' => $group['id'],
          'implicit' => $group['implicit_record_inclusion'],
        ]);
        $links[$pageLink] = ['label' => lang::get($page['caption'])];
      }
    }
    return $links;
  }

  /**
   * Return the HTML for a list of page links for a group.
   *
   * @param array $group
   *   Group data loaded from the database.
   * @param array $options
   *   [groupIntegration] control options. Can include joinLink=true to add a
   *   link for joining for non-members and a class name for the links in
   *   linkClass. Provide an option called editPath with a path to the group
   *   edit page, this will generate a link for admins to edit the group
   *   metadata.
   * @param GroupMembership $membership
   *   Current user's membership or admin status.
   *
   * @return string
   *   HTML for the list of links.
   */
  public static function getGroupPageLinksHtml(array $group, array $options, GroupMembership $membership) {
    $array = self::getGroupPageLinksArray($group, $options, $membership);
    $pageLinks = [];
    $linkClassAttr = empty($options['linkClass']) ? '' : " class=\"$options[linkClass]\"";
    foreach ($array as $href => $linkInfo) {
      // Add space after icon.
      $linkInfo['icon'] = empty($linkInfo['icon']) ? '' : "$linkInfo[icon] ";
      $pageLinks[] = "<li><a href=\"$href\"$linkClassAttr>$linkInfo[icon]$linkInfo[label]</a></li>";
    }
    if (!empty($pageLinks)) {
      return '<ul>' . implode('', $pageLinks) . '</ul>';
    }
    return '';
  }

  /**
   * A location select control.
   *
   * Can be configured for higher geography (indexed) or normal locations,
   * which use a geom intersection query.
   *
   * @param array $options
   *   Control options. The class option determines the control behaviour.
   * @param bool $addGeomHiddenInput
   *   Set to TRUE to include a hidden input for the geometry of the location.
   *   Required for normal location searches.
   *
   * @return string
   *   Control HTML.
   */
  private static function internalLocationSelect(array $options, $addGeomHiddenInput) {
    if (!is_array($options['locationTypeId']) && !preg_match('/^\d+$/', $options['locationTypeId'])) {
      throw new Exception('An integer or integer array @locationTypeId parameter is required for location select controls');
    }
    $typeIds = is_array($options['locationTypeId']) ? $options['locationTypeId'] : [$options['locationTypeId']];
    $r = '';
    $options = array_merge([
      'blankText' => lang::get('<All locations shown>'),
    ], $options);
    $options['extraParams'] = array_merge([
      'orderby' => 'name',
    ], $options['extraParams'], $options['readAuth']);
    $baseId = $options['id'];
    $selectClass = $options['class'];
    foreach ($typeIds as $idx => $typeId) {
      $options['extraParams']['location_type_id'] = $typeId;
      if (count($typeIds) > 1) {
        $options['id'] = "$baseId-$idx";
        $options['class'] .= ' linked-select';
      }
      if ($idx > 0) {
        $options['parentControlId'] = "$baseId-" . ($idx - 1);
        // We don't want the query to apply to the child drop-downs ($idx > 0),
        // as the query can't apply to both parent/child, as they are very
        // different.
        unset($options['extraParams']['query']);
        if ($idx === 1) {
          $options['parentControlLabel'] = $options['label'];
          $options['filterField'] = 'parent_id';
          unset($options['label']);
        }
      }
      $r .= data_entry_helper::location_select($options);
      // If locations are unindexed we need a place to store the geometry for
      // filtering.
      if ($addGeomHiddenInput) {
        $r .= "<input type=\"hidden\" id=\"$options[id]-geom\" class=\"es-filter-param $selectClass-geom\" data-es-bool-clause=\"must\">";
      }
    }
    return "<div class=\"location-select-cntr\">$r</div>";
  }

  /**
   * A select box for choosing from a list of higher geography boundaries.
   *
   * @return string
   *   Control HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-highergeopgraphyselect
   */
  public static function higherGeographySelect(array $options) {
    self::checkOptions('higherGeographySelect', $options,
      ['locationTypeId'],
      []
    );
    $options = array_merge([
      'class' => 'es-higher-geography-select',
    ], $options);
    return self::internalLocationSelect($options, FALSE);
  }

  /**
   * A select box for choosing from a list of unindexed location boundaries.
   *
   * @return string
   *   Control HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-locationselect
   */
  public static function locationSelect(array $options) {
    self::checkOptions('locationSelect', $options,
      ['locationTypeId'],
      []
    );
    $options = array_merge([
      'class' => 'es-location-select',
    ], $options);
    return self::internalLocationSelect($options, TRUE);
  }

  /**
   * An Elasticsearch or Indicia data powered Leaflet map control.
   *
   * @return string
   *   Map container HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-leafletmap
   */
  public static function leafletMap(array $options) {
    self::checkOptions('leafletMap', $options,
      ['layerConfig'],
      ['baseLayerConfig', 'layerConfig', 'selectedFeatureStyle']
    );
    $options = array_merge([
      'initialLat' => hostsite_get_config_value('iform', 'map_centroid_lat', 54.093409),
      'initialLng' => hostsite_get_config_value('iform', 'map_centroid_long', -2.89479),
      'initialZoom' => hostsite_get_config_value('iform', 'map_zoom', 5),
    ], $options);
    helper_base::add_resource('leaflet');
    if (isset($options['baseLayerConfig'])) {
      foreach ($options['baseLayerConfig'] as $baseLayer) {
        if ($baseLayer['type'] === 'Google') {
          helper_base::add_resource('leaflet_google');
        }
      }
    }
    // Override map position from URL if needed.
    // (e.g. this allows control via an iFrame).
    if (!empty($_GET['initialLat'])) {
      $options['initialLat'] = $_GET['initialLat'];
    }
    if (!empty($_GET['initialLng'])) {
      $options['initialLng'] = $_GET['initialLng'];
    }
    if (!empty($_GET['initialZoom'])) {
      $options['initialZoom'] = $_GET['initialZoom'];
    }
    $dataOptions = helper_base::getOptionsForJs($options, [
      'baseLayerConfig',
      'cookies',
      'initialLat',
      'initialLng',
      'initialZoom',
      'layerConfig',
      'maxSqSizeKms',
      'minSqSizeKms',
      'selectedFeatureStyle',
      'showSelectedRow',
    ], TRUE);
    // Extra setup required after map loads.
    helper_base::$late_javascript .= <<<JS
$('#$options[id]').idcLeafletMap('bindControls');

JS;
    return self::getControlContainer('leafletMap', $options, $dataOptions);
  }

  /**
   * Output a selector for a survey.
   *
   * @return string
   *   Select HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-surveyfilter
   */
  public static function surveyFilter(array $options) {

    $options = array_merge([
      'label' => lang::get('Limit to survey'),
    ], $options);

    $controlOptions = [
      'label' => $options['label'],
      'fieldname' => 'es-survey-filter',
      'class' => 'es-filter-param survey-filter',
      'attributes' => [
        'data-es-bool-clause' => 'must',
        'data-es-query' => '{&quot;term&quot;: {&quot;metadata.survey.id&quot;: #value#}}',
        'data-es-summary' => 'limit to records in survey: #value#',
      ],
      'table' => 'survey',
      'valueField' => 'id',
      'captionField' => 'title',
      'extraParams' => $options['readAuth'] + [
        'orderby' => 'title',
        'sharing' => 'data_flow',
      ],
      'blankText' => '- Please select -',
    ];

    return data_entry_helper::select($controlOptions);
  }

  /**
   * Finds a list of the options available for permission filters.
   *
   * Can be used to populate the [permissionFilters] control or for the proxy
   * to verify that a requested permissions filter is authorised.
   *
   * @param array $options
   *   Options for the [permissionFilters] control.
   *
   * @return array
   *   Associative array of options.
   */
  public static function getPermissionFiltersOptions(array $options) {
    require_once 'prebuilt_forms/includes/report_filters.php';
    $options = array_merge([
      'includeFiltersForGroups' => FALSE,
      'includeFiltersForSharingCodes' => [],
    ], $options);
    $optionArr = [];
    // Add My records download permission if allowed.
    if (!empty($options['my_records_permission']) && hostsite_user_has_permission($options['my_records_permission'])) {
      $optionArr['p-my'] = lang::get('My records');
    }
    // Add All records if website permission allows.
    if (!empty($options['all_records_permission']) && hostsite_user_has_permission($options['all_records_permission'])) {
      $optionArr['p-all'] = lang::get('All records');
    }
    // Add collated location (e.g. LRC boundary) records if website
    // permissions allow.
    if (!empty($options['location_collation_records_permission'])
        && hostsite_user_has_permission($options['location_collation_records_permission'])) {
      $locationId = hostsite_get_user_field('location_collation');
      if ($locationId) {
        $locationData = data_entry_helper::get_population_data([
          'table' => 'location',
          'extraParams' => $options['readAuth'] + ['id' => $locationId],
        ]);
        if (count($locationData) > 0) {
          $optionArr['p-location_collation'] = lang::get('Records within location ' . $locationData[0]['name']);
        }
      }
    }

    // Add in permission filters.
    // Find allowed values only.
    $sharingCodes = array_intersect(
      $options['includeFiltersForSharingCodes'],
      ['R', 'V', 'D', 'M', 'P']
    );
    $sharingTypes = [
      'R' => lang::get('Reporting'),
      'V' => lang::get('Verification'),
      'D' => lang::get('Data-flow'),
      'M' => lang::get('Moderation'),
      'P' => lang::get('Peer review'),
    ];
    foreach ($sharingCodes as $sharingCode) {
      $filterData = report_filters_load_existing($options['readAuth'], $sharingCode, TRUE);
      foreach ($filterData as $filter) {
        if ($filter['defines_permissions'] === 't') {
          // If useSharingPrefix options specified, prefix type of sharing to
          // filter name.
          $filterTitle = $options['useSharingPrefix']
            ? $sharingTypes[$sharingCode] . ' - ' . $filter['title']
            : $filter['title'];
          $optionArr["f-$filter[id]"] = $filterTitle;
        }
      }
    }

    if ($options['includeFiltersForGroups']) {
      // Groups integration if user linked to warehouse.
      $params = [
        'user_id' => hostsite_get_user_field('indicia_user_id'),
        'view' => 'detail',
      ];

      if ($params['user_id']) {
        $groups = helper_base::get_population_data([
          'table' => 'groups_user',
          'extraParams' => helper_base::$js_read_tokens + $params,
        ]);
        foreach ($groups as $group) {
          $title = $group['group_title'] . (isset($group['group_expired']) && $group['group_expired'] === 't' ?
              ' (' . lang::get('finished') . ')' : '');
          if ($group['administrator'] === 't') {
            $optionArr["g-all-$group[group_id]"] = lang::get('All records added using a recording form for {1}', $title);
          }
          $optionArr["g-my-$group[group_id]"] = lang::get('My records added using a recording form for {1}', $title);
        }
      }
    }

    return $optionArr;
  }

  /**
   * Output a selector for sets of records defined by a permission.
   *
   * Allows user to select from:
   * * All records (if permission is set)
   * * My records
   * * Permission filters
   * * Groups.
   *
   * @todo Allow hide if only one option.
   *
   * @return string
   *   Select HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-permissionfilters
   */
  public static function permissionFilters(array $options) {
    $wrapperOptions = array_merge([
      'id' => "es-permissions-filter-wrapper",
    ], $options);

    $options = array_merge([
      'id' => "es-permissions-filter",
      'useSharingPrefix' => TRUE,
      'label' => lang::get('Records to access'),
      'notices' => '[]',
    ], $options);

    $optionArr = self::getPermissionFiltersOptions($options);
    // Return the select control. There will always be at least one option (my
    // records).
    $controlOptions = [
      'label' => lang::get($options['label']),
      'fieldname' => $options['id'],
      'lookupValues' => $optionArr,
      'class' => 'permissions-filter',
    ];

    $dropdown = data_entry_helper::select($controlOptions);
    $html = <<<HTML
<div>
  $dropdown
  <div id="permission-filters-notice"></div>
</div>

HTML;

    $dataOptions = helper_base::getOptionsForJs($options, ['notices'], TRUE);
    return self::getControlContainer('permissionFilters', $wrapperOptions, $dataOptions, $html);
  }

  /**
   * Output a summary of currently applied filters.
   *
   * @return string
   *   HTML summary text.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-filtersummary
   */
  public static function filterSummary(array $options) {

    require_once 'prebuilt_forms/includes/report_filters.php';
    report_filters_set_parser_language_strings();
    $options = array_merge([
      'id' => 'es-filter-summary',
      'label' => 'Filter summary',
    ], $options);

    $html = <<<HTML
<div>
  <h3>$options[label]</h3>
  <div class="filter-summary-contents"></div>
</div>

HTML;

    helper_base::$late_javascript .= <<<JS
$('#es-filter-summary').idcFilterSummary('populate');
$('.es-filter-param, .user-filter, .permissions-filter, .standalone-quality-filter select,.standalone-media-filter select').change(function () {
    // Update any summary output
    $('#es-filter-summary').idcFilterSummary('populate');
});

JS;

    return self::getControlContainer('filterSummary', $options, json_encode([]), $html);
  }

  /**
   * Output a selector for presence of media.
   *
   * Mirrors the 'quality - records and photos' drop-down in standardParams
   * control.
   *
   * @return string
   *   Select HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-mediafilter
   */
  public static function mediaFilter(array $options) {
    require_once 'prebuilt_forms/includes/report_filters.php';
    $options = array_merge([
      'standalone' => TRUE,
    ], $options);
    return media_filter_control($options['readAuth'], $options);
  }

  /**
   * Output a selector for record status.
   *
   * Mirrors the 'quality - records to include' drop-down in standardParams control.
   *
   * @return string
   *   Select HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-statusfilters
   */
  public static function statusFilters(array $options) {
    require_once 'prebuilt_forms/includes/report_filters.php';
    $options = array_merge([
      'sharing' => 'reporting',
      'elasticsearch' => TRUE,
      'standalone' => TRUE,
    ], $options);
    return status_filter_control($options['readAuth'], $options);
  }

  /**
   * A tabbed control to show full record details and verification info.
   *
   * @return string
   *   Panel container HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-recorddetails
   */
  public static function recordDetails(array $options) {
    $options = array_merge([
      'allowRedetermination' => FALSE,
    ], $options);
    self::checkOptions('recordDetails', $options, ['showSelectedRow'], ['locationTypes']);
    if (!empty($options['explorePath'])) {
      // Build  URL which overrides the default filters applied to many Explore
      // pages in order to be able to apply out own filter.
      $options['exploreUrl'] = hostsite_get_url(
        $options['explorePath'],
        [
          'filter-quality' => '-q-',
          'filter-quality_op' => 'in',
          'filter-date_from' => '-df-',
          'filter-date_to' => '-dt-',
          'filter-user_id' => '-userId-',
          'filter-date_age' => '',
          'filter-indexed_location_list' => '',
          'filter-indexed_location_id' => '',
          'filter-my_records' => 1,
        ]
      );
    }
    $dataOptions = helper_base::getOptionsForJs($options, [
      'allowRedetermination',
      'exploreUrl',
      'extraLocationTypes',
      'locationTypes',
      'showSelectedRow',
    ], TRUE);
    helper_base::add_resource('tabs');
    helper_base::addLanguageStringsToJs('recordDetails', [
      'actionByPersonOnDate' => '{1} by {2} on {3}',
      'comment' => 'Comment',
      'recordEntered' => 'Record entered',
      'redetermination' => 'Redetermination',
      'verificationDecision' => 'Verification',
    ]);
    // Record details pane must be initialised after the control acting as row
    // data source, so it can hook to events.
    helper_base::$javascript .= <<<JS
$('#$options[id]').idcRecordDetailsPane();

JS;
    helper_base::$late_javascript .= <<<JS
$('#$options[id]').idcRecordDetailsPane('bindControls');

JS;
    $r = <<<HTML
<div class="idc-control idc-recordDetails" data-idc-class="idcRecordDetails" id="$options[id]" data-idc-config="$dataOptions">
  <div class="empty-message alert alert-info"><span class="fas fa-info-circle fa-2x"></span>Select a row to view details</div>
  <div class="tabs" style="display: none">
    <ul>
      <li><a href="#tabs-details">Details</a></li>
      <li><a href="#tabs-comments">Comments</a></li>
      <li><a href="#tabs-recorder-experience">Recorder experience</a></li>
    </ul>
    <div id="tabs-details">
      <div class="record-details">
      </div>
    </div>
    <div id="tabs-comments">
      <div class="comments">
      </div>
    </div>
    <div id="tabs-recorder-experience">
      <div class="recorder-experience"></div>
      <div class="loading-spinner" style="display: none"><div>Loading...</div></div>
    </div>
  </div>
</div>

HTML;
    return $r;
  }

  /**
   * A button that allows records to be moved from one website to another.
   *
   * @return string
   *   Panel container HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-recordsmover
   */
  public static function recordsMover(array $options) {
    self::checkOptions(
      'recordsMover',
      $options,
      ['datasetMappings', 'linkToDataControl'],
      ['datasetMappings'],
    );
    $options = array_merge([
      'caption' => 'Move records',
      'restrictToOwnData' => TRUE,
    ], $options);
    $dataOptions = helper_base::getOptionsForJs($options, [
      'datasetMappings',
      'id',
      'linkToDataControl',
      'restrictToOwnData',
    ], TRUE);
    helper_base::addLanguageStringsToJs('recordsMover', [
      'cannotProceed' => 'Cannot proceed',
      'done' => 'Records successfully moved. They will now be processed so they are available in their new location shortly.',
      'error' => 'An error occurred whilst trying to move the records.',
      'errorNotFilteredToCurrentUser' => 'The records cannot be moved because the current page is not filtered to limit the records to only your data.',
      'moveProgress' => 'Moved {samples} samples and {occurrences} occurrences.',
      'moving' => 'Moving the records...',
      'precheckProgress' => 'Checked {samples} samples and {occurrences} occurrences.',
      'preparing' => 'Preparing to move the records...',
      'recordsMoverDialogMessageAll' => 'You are about to move the entire list of {1} records.',
      'recordsMoverDialogMessageSelected' => 'You are about to move {1} selected records.',
      'warningNothingToDo' => 'There are no selected records to move.',
    ]);
    $lang = [
      'cancel' => lang::get('Cancel'),
      'close' => lang::get('Close'),
      'moveRecords' => lang::get($options['caption']),
      'movingRecords' => lang::get('Moving the records'),
      'proceed' => lang::get('Proceed'),
    ];
    helper_base::add_resource('fancybox');
    global $indicia_templates;
    $html = <<<HTML
<button type="button" class="move-records-btn $indicia_templates[buttonHighlightedClass]">$lang[moveRecords]</button>
<div style="display: none">
  <div id="$options[id]-dlg" class="records-mover-dlg">
    <div class="pre-move-info">
      <h2>$lang[moveRecords]</h2>
      <p class="message"></p>
      <div class="form-buttons">
        <button type="button" class="$indicia_templates[buttonHighlightedClass] proceed-move">$lang[proceed]</button>
        <button type="button" class="$indicia_templates[buttonHighlightedClass] close-move-dlg">$lang[cancel]</button>
      </div>
    </div>
    <div class="post-move-info">
      <h2>$lang[movingRecords]</h2>
      <div class="output"></div>
      <div class="form-buttons">
        <button type="button" class="$indicia_templates[buttonHighlightedClass] close-move-dlg" disabled="disabled">$lang[close]</button>
      </div>
    </div>
  </div>
</div>
HTML;
    return self::getControlContainer('recordsMover', $options, $dataOptions, $html);
  }

  /**
   * A control for running custom verification rulesets.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-runcustomverificationrulesets
   */
  public static function runCustomVerificationRulesets(array $options) {
    global $indicia_templates;
    self::checkOptions('runCustomVerificationRulesets', $options, ['source'], []);
    $dataOptions = helper_base::getOptionsForJs($options, [
      'id',
      'source',
    ], TRUE);
    helper_base::addLanguageStringsToJs('runCustomVerificationRulesets', [
      'areYouSureClear' => 'Are you sure you wish to proceed? This will clear all your custom verification rule flags from the currently loaded set of records. It will not affect flags added by other users.',
      'clearResults' => 'Clear existing results',
      'clearResultsDoneMessage' => '{1} records had their flags removed.',
      'processComplete' => 'Processing complete',
      'processCompleteMessage' => 'The custom verification rules have been applied. {1} records were checked.',
    ]);
    $lang = [
      'clearResults' => lang::get('Clear previous results'),
      'clearResultsDescription' => lang::get('Clear the results of any previous ruleset runs.'),
      'customVerificationRulesetIntro' => lang::get('Select the custom verification ruleset to run from the list below. Rules will be applied to all <span class="msg-count"></span> records in the current filter.'),
      'manageRulesets' => lang::get('Manage rulesets'),
      'noRulesetsMessage' => lang::get('You have not created any verification rulesets. Click Manage rulesets to get started.'),
      'processing' => lang::get('Processing'),
      'runCustomVerificationRuleset' => lang::get('Run a custom verification ruleset'),
      'runSelectedRuleset' => lang::get('Run selected ruleset'),
    ];
    $rules = data_entry_helper::get_population_data([
      'table' => 'custom_verification_ruleset',
      'extraParams' => $options['readAuth'] + [
        'created_by_id' => hostsite_get_user_field('indicia_user_id'),
        'orderby' => 'title',
      ],
    ]);
    if (empty($rules)) {
      $rulesSelectUi = "<p>$lang[noRulesetsMessage]</p>";
    }
    else {
      $rulesSelectUi = "<p>$lang[customVerificationRulesetIntro]</p>";
      $rulesOptions = [];
      foreach ($rules as $rule) {
        $rulesOptions[$rule['id']] = $rule['title'];
      }
      $rulesSelectUi .= data_entry_helper::radio_group([
        'fieldname' => 'ruleset-list',
        'lookupValues' => $rulesOptions,
      ]);
    }
    if (!empty($options['manageRulesetsPagePath'])) {
      $manageUrl = hostsite_get_url($options['manageRulesetsPagePath']);
      $manageLink = empty($options['manageRulesetsPagePath'])
        ? ''
        : "<a href=\"$manageUrl\" target=\"_blank\" class=\"axaxa $indicia_templates[buttonDefaultClass] manage-rulesets\">$lang[manageRulesets]</a>";
    }
    else {
      $manageLink = '';
    }
    $html = <<<HTML
<button class="btn btn-warning custom-rule-popup-btn">Custom rules...</button>
<div style="display: none">
  <div id="$options[id]-dlg-cntr">
    <h3>$lang[runCustomVerificationRuleset]</h3>
    $rulesSelectUi
    <button type="button" class="$indicia_templates[buttonHighlightedClass] run-custom-verification-ruleset" disabled="disabled">$lang[runSelectedRuleset]</button>
    $manageLink
    <button type="button" class="$indicia_templates[floatRightClass] $indicia_templates[buttonWarningClass] clear-results" title="$lang[clearResultsDescription]">$lang[clearResults]</button>
    <div class="progress-cntr" style="display: none">
      $lang[processing]
      <div class="unknown-time-progressbar">
        <div></div>
      </div>
    </div>
  </div>
</div>
HTML;

    return self::getControlContainer('runCustomVerificationRulesets', $options, $dataOptions, $html);
  }

  /**
   * Initialises the JavaScript required for an Elasticsearch data source.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-source
   *
   * @return string
   *   Empty string as no HTML required.
   */
  public static function source(array $options) {
    self::applyReplacements(
      $options,
      ['aggregation', 'sortAggregation'],
      ['aggregation', 'sortAggregation']
    );
    self::checkOptions(
      'source',
      $options,
      ['id'],
      [
        'aggregation',
        'fields',
        'filterBoolClauses',
        'sort',
        'sortAggregation',
      ]
    );
    // Temporary support for deprecated @aggregationMapMode option. Will be
    // removed in a future version.
    if (empty($options['mode'])) {
      if (!empty($options['aggregationMapMode'])) {
        $options['mode'] = 'map' . ucfirst($options['aggregationMapMode']);
      }
      elseif (!empty($options['aggregation']) && !empty($options['filterBoundsUsingMap'])) {
        // Default aggregation mode to mapGeoHash (for legacy support till
        // deprecated code removed).
        $options['mode'] = 'mapGeoHash';
      }
    }
    $options = array_merge([
      'from' => 0,
      'mode' => 'docs',
    ], $options);
    self::applySourceModeDefaults($options);
    $jsOptions = [
      'aggregation',
      'endpoint',
      'fields',
      'filterBoolClauses',
      'filterBoundsUsingMap',
      'filterField',
      'filterPath',
      'filterSourceField',
      'filterSourceGrid',
      'from',
      'id',
      'initialMapBounds',
      'mapGridSquareSize',
      'mode',
      'proxyCacheTimeout',
      'sortAggregation',
      'shardSize',
      'size',
      'sort',
      'switchToGeomsAt',
      'uniqueField',
      'disabled',
    ];
    helper_base::$indiciaData['esSources'][] = array_intersect_key($options, array_combine($jsOptions, $jsOptions));
    // A source is entirely JS driven - no HTML.
    return '';
  }

  /**
   * A standard parameters filter toolbar for use on Elasticsearch pages.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-standardparams
   */
  public static function standardParams(array $options) {
    require_once 'prebuilt_forms/includes/report_filters.php';
    $options = array_merge([
      'allowSave' => TRUE,
      'sharing' => 'reporting',
      'elasticsearch' => TRUE,
    ], $options);
    foreach ($options as &$value) {
      $value = apply_user_replacements($value);
    }
    if ($options['allowSave'] && !function_exists('iform_ajaxproxy_url')) {
      return 'The AJAX Proxy module must be enabled to support saving filters. Set @allowSave=false to disable this in the [standard params] control.';
    }
    if (!function_exists('hostsite_get_user_field') || !hostsite_get_user_field('indicia_user_id')) {
      // If not logged in and linked to warehouse, we can't use standard params
      // functionality like saving, so...
      return '';
    }
    $hiddenStuff = '';
    $r = report_filter_panel($options['readAuth'], $options, helper_base::$website_id, $hiddenStuff);
    return $r . $hiddenStuff;
  }

  /**
   * A control for flexibly outputting data formatted using HTML templates.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-templatedoutput
   */
  public static function templatedOutput(array $options) {
    self::checkOptions('templatedOutput', $options, ['source', 'content'], []);
    $dataOptions = helper_base::getOptionsForJs($options, [
      'source',
      'content',
      'header',
      'footer',
      'repeatField',
    ], TRUE);
    return self::getControlContainer('templatedOutput', $options, $dataOptions);
  }

  /**
   * Retrieve parameters from the URL and add to the ES requests.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-urlparams
   *
   * @return string
   *   Hidden input HTML which defines the appropriate filters.
   */
  public static function urlParams(array $options) {
    self::checkOptions('urlParams', $options, [], ['fieldFilters']);
    $options = array_merge([
      'fieldFilters' => [
        'taxa_in_scratchpad_list_id' => [
          [
            'name' => 'taxon.higher_taxon_ids',
            'type' => 'integer',
            'process' => 'taxonIdsInScratchpad',
          ],
        ],
        // For legacy configurations.
        'taxon_scratchpad_list_id' => [
          [
            'name' => 'taxon.higher_taxon_ids',
            'type' => 'integer',
            'process' => 'taxonIdsInScratchpad',
          ],
        ],
        'sample_id' => [
          [
            'name' => 'event.event_id',
            'type' => 'integer',
          ],
        ],
        'taxa_in_sample_id' => [
          [
            // Use accepted taxon ID so this is not a hierarchical query.
            'name' => 'taxon.accepted_taxon_id',
            'type' => 'integer',
            'process' => 'taxonIdsInSample',
          ],
        ],
      ],
      // Other options, e.g. group_id or field params may be added in future.
    ], $options);
    $r = '';
    foreach ($options['fieldFilters'] as $field => $esFieldList) {
      if (!empty($_GET[$field])) {
        foreach ($esFieldList as $esField) {
          $value = trim($_GET[$field]);
          if ($esField['type'] === 'integer') {
            if (!preg_match('/^\d+$/', $value)) {
              // Disable this filter.
              $value = '-1';
              hostsite_show_message(
                "Data cannot be loaded because the value in the $field parameter is invalid",
                'warning'
              );
            }
          }
          $queryType = 'term';
          // Special processing for a taxon scratchpad ID.
          if (isset($esField['process'])) {
            if ($esField['process'] === 'taxonIdsInScratchpad') {
              $value = self::convertValueToFilterList(
                'library/taxa/external_keys_for_scratchpad',
                ['scratchpad_list_id' => $value],
                'external_key',
                $options['readAuth']
              );
            }
            elseif ($esField['process'] === 'taxonIdsInSample') {
              $value = self::convertValueToFilterList(
                'library/taxa/external_keys_for_sample',
                ['sample_id' => $value],
                'external_key',
                $options['readAuth']
              );
            }
            $queryType = 'terms';
          }
          $r .= <<<HTML
<input type="hidden" class="es-filter-param" value="$value"
  data-es-bool-clause="must" data-es-field="$esField[name]" data-es-query-type="$queryType" />

HTML;
        }
      }
    }
    return $r;
  }

  /**
   * Output a selector for a user's registered filters.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-userfilters
   */
  public static function userFilters(array $options) {
    require_once 'prebuilt_forms/includes/report_filters.php';
    self::$controlIndex++;
    $options = array_merge([
      'id' => "es-user-filter-" . self::$controlIndex,
      'definesPermissions' => FALSE,
      'sharingCode' => 'R',
    ], $options);
    $options = array_merge([
      'label' => $options['definesPermissions'] ? lang::get('Context') : lang::get('Filter'),
    ], $options);

    // Sharing code can be specified as a comma separated list of codes.
    $sharingCodes = explode(',', $options['sharingCode']);
    $optionArr = [];
    foreach ($sharingCodes as $sharingCode) {
      $filterData = report_filters_load_existing($options['readAuth'], $sharingCode, TRUE);
      foreach ($filterData as $filter) {
        if (($filter['defines_permissions'] === 't') === $options['definesPermissions']) {
          $optionArr[$filter['id']] = $filter['title'];
        }
      }
    }

    if (count($optionArr) === 0) {
      // No filters available. Until we support saving, doesn't make sense to
      // show the control.
      return '';
    }
    else {
      $classes = ['user-filter'];
      if ($options['definesPermissions']) {
        $classes[] = 'defines-permissions';
      }
      $controlOptions = [
        'label' => $options['label'],
        'fieldname' => $options['id'],
        'lookupValues' => $optionArr,
        'class' => implode(' ', $classes),
      ];
      if (!$options['definesPermissions']) {
        $controlOptions['blankText'] = '- ' . lang::get('Please select') . ' - ';
      }
      return data_entry_helper::select($controlOptions);
    }
  }

  /**
   * A panel containing buttons for record verification actions.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-verificationbuttons
   *
   * @return string
   *   Panel HTML;
   */
  public static function verificationButtons(array $options) {
    global $indicia_templates;
    if (!function_exists('iform_ajaxproxy_url')) {
      return 'The AJAX Proxy module must be enabled to use the [verificationButtons] control.';
    }
    $requiredOptions = ['showSelectedRow'];
    $config = hostsite_get_es_config($options['nid']);
    helper_base::$indiciaData['idPrefix'] = $config['es']['warehouse_prefix'];
    if (!empty($options['includeUploadButton'])) {
      helper_base::$indiciaData['esEndpoint'] = $config['es']['endpoint'];
      $requiredOptions[] = 'warehouseName';
    }
    self::checkOptions('verificationButtons', $options, $requiredOptions, []);
    if (!empty($options['includeUploadButton'])) {
      helper_base::$indiciaData['warehouseName'] = $options['warehouseName'];
    }
    $options = array_merge([
      'redeterminerNameAttributeHandling' => 'overwriteOnRedet',
      'taxon_list_id' => hostsite_get_config_value('iform', 'master_checklist_id'),
      'useLocalFormPaths' => FALSE,
      'verificationTemplates' => FALSE,
    ], $options);
    $dataOptions = helper_base::getOptionsForJs($options, [
      'editPath',
      'id',
      'keyboardNavigation',
      'showSelectedRow',
      'speciesPath',
      'uploadButtonContainerElement',
      'useLocalFormPaths',
      'verificationTemplates',
      'viewPath',
    ], TRUE);
    $verifyUrl = iform_ajaxproxy_url($options['nid'], 'list_verify');
    $userId = hostsite_get_user_field('indicia_user_id');
    $commentUrl = iform_ajaxproxy_url($options['nid'], 'occ-comment');
    $redetUrl = iform_ajaxproxy_url($options['nid'], 'list_redet');
    $quickReplyPageAuthUrl = iform_ajaxproxy_url($options['nid'], 'comment_quick_reply_page_auth');
    $siteEmail = hostsite_get_config_value('site', 'mail', '');
    helper_base::$indiciaData['ajaxFormPostVerify'] = "$verifyUrl&user_id=$userId&sharing=verification";
    helper_base::$indiciaData['ajaxFormPostComment'] = "$commentUrl&user_id=$userId&sharing=verification";
    helper_base::$indiciaData['ajaxFormPostRedet'] = "$redetUrl&user_id=$userId&sharing=verification";
    helper_base::$indiciaData['ajaxFormPostVerificationTemplate'] = iform_ajaxproxy_url(NULL, 'verification_template') . "&user_id=$userId";
    helper_base::$indiciaData['ajaxFormPostQuickReplyPageAuth'] = $quickReplyPageAuthUrl;
    helper_base::$indiciaData['siteEmail'] = $siteEmail;
    helper_base::$javascript .= "$('#$options[id]').idcVerificationButtons({});\n";
    if (isset($options['enableWorkflow']) && $options['enableWorkflow']) {
      iform_load_helpers(['VerificationHelper']);
      VerificationHelper::fetchTaxaWithLoggedCommunications($options['readAuth']);
    }
    helper_base::add_resource('fancybox');
    helper_base::add_resource('validation');
    $lang = [
      'accepted' => lang::get('Accepted'),
      'acceptedConsideredCorrect' => lang::get('Accepted :: considered correct'),
      'acceptedCorrect' => lang::get('Accepted :: correct'),
      'addComment' => lang::get('Add comment'),
      'all' => lang::get('all'),
      'applyThisDecisionToParentSample' => lang::get(
        'Only available for records that are part of a parent sample, such as a transect or timed count on a single date. Click to activate the button then verification ' .
        'decisions will apply to all unverified records of the same taxon within the parent sample.'),
      'applyTo' => lang::get('Apply to'),
      'applyRedetermination' => lang::get('Apply redetermination'),
      'cancel' => lang::get('Cancel'),
      'cancelSaveTemplate' => lang::get('Cancel saving the template'),
      'commentTabTitle' => 'Comment on the record',
      'contactExpert' => lang::get('Contact an expert'),
      'edit' => lang::get('Edit'),
      'editThisRecord' => lang::get('Edit this record'),
      'emailBody' => lang::get('Email body'),
      'emailPlaceholder' => lang::get('Enter the email address to send the record to'),
      'emailSubject' => lang::get('Email subject'),
      'emailTabTitle' => lang::get('Email record details'),
      'help' => lang::get('Help'),
      'notAccepted' => lang::get('Not accepted'),
      'notAcceptedIncorrect' => lang::get('Not accepted :: incorrect'),
      'notAcceptedUnableToVerify' => lang::get('Not accepted :: unable to verify'),
      'plausible' => lang::get('Plausible'),
      'raiseQuery' => lang::get('Raise a query with the recorder'),
      'saveStatus' => lang::get('Save status'),
      'saveTemplate' => lang::get('Save template'),
      'selected' => lang::get('selected'),
      'sendEmail' => lang::get('Send email'),
      'sendEmailTo' => lang::get('Send email to'),
      'showPreview' => lang::get('Preview'),
      'templateHelpIntroAvailableTokens' => lang::get('Available placeholders as follows - use the copy buttons (<i class="far fa-copy"></i>) to copy the placeholder to the clipboard so you can paste it into your comment:'),
      'templateHelpIntro1' => lang::get('You can create and save templates for your verification comments which can be used to provide the comment for future verification actions. ' .
        'To do this, enter the comment in the box as normal, then click the "Save template" button. You will then need to provide a name for your ' .
        'template and click the Save button in the controls that appear.'),
      'templateHelpIntro2' => lang::get('Note that templates saved when accepting records will only be available to select when accepting other records, likewise rejections, ' .
        'queries and redeterminations each have their own set of templates.'),
      'templateHelpIntro3' => lang::get('You can insert placeholders into the text which will be replaced by details about the current record, making your templates more flexible. Use ' .
        'the preview to check the behaviour of your template placeholders before saving the comment.'),
      'templateHelpTitle' => lang::get('Using templates for your comments'),
      'templateHelpTokenAction' => lang::get('will be replaced by the action you are taking, e.g. "queried" or "accepted as correct".'),
      'templateHelpTokenCommonName' => lang::get('will be replaced by the preferred common name for the organism, or the accepted scientific name if there is no common name available.'),
      'templateHelpTokenDate' => lang::get('will be replaced by the date of the original record.'),
      'templateHelpExample' => lang::get('Thanks for your record of {{ taxon full name }} which has been {{ action }}.'),
      'templateHelpTokenFullTaxonName' => lang::get('will be replaced by the accepted scientific name for the organism, with the common name appended in brackets if it is available.'),
      'templateHelpIntroExample' => lang::get('An example template might look like the following:'),
      'templateHelpTokenLocationName' => lang::get('will be replaced by the location name of the record.'),
      'templateHelpTokenNewCommonName' => lang::get('for redeterminations only, will be replaced by the new preferred common name for the organism, or the accepted scientific name if there is no common name available.'),
      'templateHelpTokenNewFullTaxonName' => lang::get('for redeterminations only, will be replaced by the new accepted scientific name for the organism, with the common name appended in brackets if it is available.'),
      'templateHelpTokenNewPreferredName' => lang::get('for redeterminations only, will be replaced by the new accepted scientific name for the organism.'),
      'templateHelpTokenNewTaxon' => lang::get('for redeterminations only, will be replaced by the new identification name given to the record as entered.'),
      'templateHelpTokenPreferredName' => lang::get('will be replaced by the accepted scientific name for the organism.'),
      'templateHelpTokenRank' => lang::get('will be replaced by the rank of the name given to the organism, e.g. species.'),
      'templateHelpTokenSref' => lang::get('will be replaced by the standardised output map reference of the record.'),
      'templateHelpTokenTaxon' => lang::get('will be replaced by the identification name given to the record as originally entered.'),
      'templateHelpClose' => lang::get('Close help'),
      'updatingMultipleWarning' => lang::get('You are updating multiple records!'),
      'upload' => lang::get('Upload'),
      'uploadVerificationDecisions' => lang::get('Upload a file of verification decisions'),
      'viewRecordDetails' => "View this record's details page",
      'viewSpeciesPage' => 'View species page',
    ];

    helper_base::addLanguageStringsToJs('verificationButtons', [
      'cancel' => 'Cancel',
      'close' => 'Close',
      'copyPlaceholder' => 'Copy &quot;{{ placeholder }}&quot; to the clipboard.',
      'deleteTemplateConfirm' => 'Delete template',
      'deleteTemplateMsg' => 'Are you sure you want to delete the "{{ title }}" template?',
      'delete' => 'Delete',
      'deleteTemplateError' => 'Delete template error',
      'deleteTemplateErrorMsg' => 'An error occurred when deleting your template from the database. Please try later.',
      'elasticsearchUpdateError' => 'An error occurred whilst updating the reporting index. It may not reflect your changes temporarily but will be updated automatically later.',
      'commentReplyInstruct' => 'Click here to add a publicly visible comment to the record on iRecord.',
      'csvDisallowedMessage' => 'Uploading verification decisions is only allowed when there is a filter that defines the scope of the records you can verify.',
      'duplicateTemplateMsg' => 'A template with that name already exists. Please specify a unique name for your template then save it again, or click Overwrite to update the existing template details.',
      'emailExpertBodyHeader' => "Verification query\n\nThe following record requires your assistance. Please could you reply to this email " .
        'with your opininion on whether the record is correct or not. You can reply to this message and it will be ' .
        'forwarded direct to the verifier.',
      'emailExpertInstruct' => 'Enter the email of an expert to request their assistance with this record.',
      'emailExpertLoggedAsComment' => 'This record was emailed to an expert for checking.',
      'emailExpertSubject' => 'Record of {{ taxon.taxon_name }} requires your assistance (ID:{{ id }})',
      'emailQueryBodyHeader' => "Verification query\n\nThe following record requires confirmation. Please could you reply to this email " .
        'stating how confident you are that the record is correct and any other information you have which may help ' .
        'to confirm this. You can reply to this message and it will be forwarded direct to the verifier.',
      'emailQueryLoggedAsComment' => 'This record was emailed to the recorder for checking.',
      'emailQuerySubject' => 'Record of {{ taxon.taxon_name }} requires confirmation (ID:{{ id }})',
      'emailReplyInstruct' => "Click on your email's reply button to send an email direct to the verifier.",
      'emailSent' => 'The email was sent successfully.',
      'emailTabTitle' => 'Email record details',
      'enterEmailAddress' => 'Enter the email address to send the record to',
      'nothingSelected' => 'There are no selected records. Either select some rows using the checkboxes in the leftmost column or set the "Apply decision to" mode to "all".',
      'overwrite' => 'Overwrite',
      'pleaseSupplyATemplateNameAndText' => 'Please supply a name and comment text for your template then click the Save button again.',
      'queryEmailTabAnonWithEmail' => 'This record was posted by a recorder who was not logged in but provided their email address so email is the best method of contact.',
      'queryEmailTabAnonWithoutEmail' => 'As this record does not have an email address for the recorder, the query is best added as a comment to the record unless you know the recorder and have their email address and permission to use it. There is no guarantee that the recorder will check their notifications.',
      'queryEmailTabUserIsNotified' => 'Although you can email this recorder directly, they check their notifications so adding a comment should be sufficient.',
      'queryEmailTabUserIsNotNotified' => 'Sending the query as an email is likely to be the best method of contact as the recorder does not check their notifications.',
      'queryCommentTabAnonWithEmail' => 'This record was posted by a recorder who was not logged in but provided their email address. You can add a comment using this tab but they are unlikely to see it so email is likely to be the best method of contact.',
      'queryCommentTabAnonWithoutEmail' => 'As this record does not have an email address for the recorder, the query can be added to the record as a comment. The query can only be sent to the recorder if you know their email address and have permission to use it.',
      'queryCommentTabUserIsNotified' => 'Adding your query as a comment should be OK as this recorder normally checks their notifications.',
      'queryCommentTabUserIsNotNotified' => 'Although you can add a comment, sending the query as an email is preferred as the recorder does not check their notifications.',
      'queryInMultiselectMode' => 'As you are in multi-select mode, email facilities cannot be used and queries can only be added as comments to the record.',
      'recordRedetermined' => 'This record has been redetermined.',
      'redetErrorMsg' => 'An error occurred on the server when redetermining the records.',
      'redetPartialListInfo' => 'This record was originally input using a taxon checklist which may not be a complete list of all species. If you cannot find the species you wish to redetermine it to using the search box below, then please tick the "Search all species" checkbox and try again.',
      'requestManualEmail' => 'The webserver is not correctly configured to send emails. Please send the following email usual your email client:',
      'saveQueryToComments' => 'Save query to comments log',
      'sendQueryAsEmail' => 'Send query as email',
      'saveTemplateError' => 'Save template error',
      'saveTemplateErrorMsg' => 'An error occurred when saving your template to the database. Please try later.',
      'templateNameTextRequired' => 'Template details required',
      'updatingMultipleInParentSampleWarning' => lang::get('This verification decision will be applied to a total of {1} records of the same taxon within the parent sample (e.g. within the transect or timed count)!'),
      'uploadError' => 'An error occurred whilst uploading your spreadsheet.',
      'C3' => 'marked as plausible',
      'DT' => 'redetermined',
    ]);
    if (!is_numeric($options['taxon_list_id'])) {
      throw new Exception('[verificationButtons] requires a @taxon_list_id option, or the Indicia setting Master Checklist ID to be set. This ' .
        'is required to provide a list to select the redetermination from.');
    }
    $btnClass = $indicia_templates['buttonHighlightedClass'];
    $btnClassDefault = $indicia_templates['buttonDefaultClass'];
    // Work out any extra buttons we need for provided links.
    $optionalLinkArray = [];
    if (!empty($options['editPath']) || $options['useLocalFormPaths'] !== 'never') {
      $optionalLinkArray[] = "<a class=\"edit $btnClassDefault\" title=\"$lang[editThisRecord]\" target=\"_blank\"><span class=\"fas fa-edit\"></span></a>";
    }
    if (!empty($options['viewPath'])) {
      $optionalLinkArray[] = "<a class=\"view $btnClassDefault\" target=\"_blank\" title=\"$lang[viewRecordDetails]\" target=\"_blank\"><span class=\"fas fa-file-invoice\"></span></a>";
    }
    if (!empty($options['speciesPath'])) {
      $optionalLinkArray[] = "<a class=\"species $btnClassDefault\" target=\"_blank\" title=\"$lang[viewSpeciesPage]\" target=\"_blank\"><span class=\"fas fa-file\"></span></a>";
    }
    $optionalLinks = implode("\n  ", $optionalLinkArray);
    // Build some controls for the various forms.
    $speciesInput = data_entry_helper::species_autocomplete([
      'label' => lang::get('Redetermine to'),
      'helpText' => lang::get('Select the new taxon name.'),
      'fieldname' => 'redet-species',
      // Default to the master list, but can switch if taxon from a different
      // list.
      'extraParams' => $options['readAuth'] + ['taxon_list_id' => $options['taxon_list_id']],
      'speciesIncludeAuthorities' => TRUE,
      'speciesIncludeBothNames' => TRUE,
      'speciesNameFilterMode' => 'all',
      'validation' => ['required'],
      'wrapClasses' => ['not-full-width-md'],
    ]);
    $altListCheckbox = data_entry_helper::checkbox([
      'fieldname' => 'redet-from-full-list',
      'label' => lang::get('Search all species'),
      'labelClass' => 'auto',
      'helpText' => lang::get('This record was identified against a restricted list of taxa. Check this box if ' .
          'you want to redetermine to a taxon selected from the unrestricted full list available.'),
      'wrapClasses' => ['alt-taxon-list-controls'],
    ]);
    // Remember which is the master list.
    helper_base::$indiciaData['mainTaxonListId'] = $options['taxon_list_id'];
    $verificationCommentInput = data_entry_helper::textarea([
      'id' => 'verification-comment-input',
      'label' => lang::get('Add the following comment'),
      'labelClass' => 'auto',
      'class' => 'comment-textarea',
      'wrapClasses' => ['not-full-width-lg'],
    ]);
    // Option to allow verified to control if determiner name updated after
    // redet.
    $redetNameBehaviourOption = '';
    if ($options['redeterminerNameAttributeHandling'] === 'allowChoice') {
      $redetNameBehaviourOption = data_entry_helper::checkbox([
        'label' => lang::get("Don't update the determiner"),
        'helpText' => lang::get('If you are changing the record determination on behalf of the original recorder and their name should be stored against the determination, please check this box.'),
        'fieldname' => 'no-update-determiner',
      ]);
    }
    $redetCommentInput = data_entry_helper::textarea([
      'id' => 'redet-comment-input',
      'label' => lang::get('Explanation comment'),
      'labelClass' => 'auto',
      'helpText' => lang::get('Please give reasons why you are changing this record.'),
      'helpTextClass' => 'helpTextLeft',
      'class' => 'comment-textarea',
      'wrapClasses' => ['not-full-width-lg'],
    ]);
    $queryCommentInput = data_entry_helper::textarea([
      'id' => 'query-comment-input',
      'label' => lang::get('Add the following comment'),
      'labelClass' => 'auto',
      'class' => 'comment-textarea',
      'default' => '',
    ]);
    $uploadButton = empty($options['includeUploadButton']) ? '' : <<<HTML
      <button class="upload-decisions $btnClass" title="$lang[uploadVerificationDecisions]"><span class="fas fa-file-upload"></span>$lang[upload]</button>
HTML;
    if ($options['verificationTemplates']) {
      $loadVerifyTemplateDropdown = self::getTemplateSelect('verify-template');
      $loadRedetTemplateDropdown = self::getTemplateSelect('redet-template');
      $loadQueryTemplateDropdown = self::getTemplateSelect('query-template');
      $saveTemplateButtons = <<<HTML
<button type="button" class="save-template $indicia_templates[buttonDefaultClass]"><i class="fas fa-save" title="$lang[saveTemplate]"></i></button>
<button type="button" class="cancel-save-template $indicia_templates[buttonDefaultClass]" title="$lang[cancelSaveTemplate]"><i class="fas fa-window-close"></i></button>
HTML;
      $templateSaveFilenameInput = data_entry_helper::text_input([
        'label' => lang::get('Enter a name for your template'),
        'fieldname' => 'template-name',
        'wrapClasses' => ['not-full-width-md'],
        'afterControl' => $saveTemplateButtons,
      ]);
      $commentTools = <<<HTML
<div class="comment-tools">
  <button type="button" class="comment-show-preview $indicia_templates[buttonDefaultClass] $indicia_templates[buttonSmallClass]">$lang[showPreview]</button>
  <button type="button" class="comment-edit $indicia_templates[buttonDefaultClass] $indicia_templates[buttonSmallClass]" style="display: none">$lang[edit]</button>
  <button type="button" class="comment-save-template $indicia_templates[buttonDefaultClass] $indicia_templates[buttonSmallClass]">$lang[saveTemplate]</button>
  <button type="button" class="comment-help $indicia_templates[buttonDefaultClass] $indicia_templates[buttonSmallClass]">$lang[help]</button>
</div>
<div class="template-save-cntr" style="display: none">
  $templateSaveFilenameInput
</div>
<div class="comment-preview" style="display: none"></div>
HTML;
    }
    else {
      $loadVerifyTemplateDropdown = '';
      $loadRedetTemplateDropdown = '';
      $loadQueryTemplateDropdown = '';
      $commentTools = '';
    }
    $r = <<<HTML
<div id="$options[id]" class="idc-control idc-verificationButtons" data-idc-class="idcVerificationButtons" style="display: none;" data-idc-config="$dataOptions">
  <div id="$options[id]-buttons" class="verification-buttons-cntr">
    <div class="selection-buttons-placeholder">
      <div class="all-selected-buttons idc-verificationButtons-row">
        Actions:
        <span class="fas fa-toggle-on toggle fa-2x" title="Toggle additional status levels"></span>
        <button class="verify l1 $btnClassDefault btn-sm" data-status="V" title="$lang[accepted]"><span class="far fa-check-circle status-V"></span></button>
        <button class="verify l2 $btnClassDefault" data-status="V1" title="$lang[acceptedCorrect]"><span class="fas fa-check-double status-V1"></span></button>
        <button class="verify l2 $btnClassDefault" data-status="V2" title="$lang[acceptedConsideredCorrect]"><span class="fas fa-check status-V2"></span></button>
        <button class="verify $btnClassDefault" data-status="C3" title="$lang[plausible]"><span class="fas fa-check-square status-C3"></span></button>
        <button class="verify l1 $btnClassDefault" data-status="R" title="$lang[notAccepted]"><span class="far fa-times-circle status-R"></span></button>
        <button class="verify l2 $btnClassDefault" data-status="R4" title="$lang[notAcceptedUnableToVerify]"><span class="fas fa-times status-R4"></span></button>
        <button class="verify l2 $btnClassDefault" data-status="R5" title="$lang[notAcceptedIncorrect]"><span class="fas fa-times status-R5"></span></button>
        <button class="apply-to-parent-sample-contents single-only $btnClassDefault" title="$lang[applyThisDecisionToParentSample]" disabled="disabled"><span class="fas fa-sitemap"></span></button>
        <span class="sep"></span>
        <button class="redet $btnClassDefault" title="Redetermine this record"><span class="fas fa-tag"></span></button>
        <button class="query $btnClassDefault" data-query="Q" title="$lang[raiseQuery]"><span class="fas fa-question-circle query-Q"></span></button>
        <div class="multi-only apply-to">
          <span>$lang[applyTo]:</span>
          <button class="multi-mode-selected active $btnClassDefault">$lang[selected]</button>
          |
          <button class="multi-mode-table $btnClassDefault">$lang[all]</button>
        </div>
      </div>
    </div>
    <div class="single-record-buttons idc-verificationButtons-row">
      <button class="email-expert $btnClassDefault" title="$lang[contactExpert]"><span class="fas fa-chalkboard-teacher"></span></button>
      $optionalLinks
      $uploadButton
    </div>
  </div>
</div>

<div id="verification-panel-wrap" style="display: none">
  <form id="verification-form" class="verification-popup comment-popup">
    <fieldset>
      <legend><span></span><span></span></legend>
      <p class="alert alert-warning multiple-warning"><i class="fas fa-exclamation-triangle"></i>$lang[updatingMultipleWarning]</p>
      <p class="alert alert-warning multiple-in-parent-sample-warning"></p>
      <p class="alert alert-info"></p>
      <div class="comment-cntr form-group">
        $commentTools
        $verificationCommentInput
      </div>
      $loadVerifyTemplateDropdown
      <div class="form-buttons">
        <button type="button" class="$btnClass save">$lang[saveStatus]</button>
        <button type="button" class="$btnClassDefault cancel">$lang[cancel]</button>
      </div>
    </fieldset>
  </form>
</div>

<div id="redet-panel-wrap" style="display: none">
  <form id="redet-form" class="verification-popup" data-status="DT">
    <p class="alert alert-warning multiple-warning"><i class="fas fa-exclamation-triangle"></i>$lang[updatingMultipleWarning]</p>
    <div class="alt-taxon-list-controls alt-taxon-list-message">$indicia_templates[messageBox]</div>
    $speciesInput
    $altListCheckbox
    $redetNameBehaviourOption
    <div class="comment-cntr form-group not-full-width-lg">
      $commentTools
      $redetCommentInput
    </div>
    $loadRedetTemplateDropdown
    <div class="form-buttons">
      <button type="button" class="$btnClass" id="apply-redet">$lang[applyRedetermination]</button>
      <button type="button" class="$btnClassDefault cancel">$lang[cancel]</button>
    </div>
  </form>
</div>

<div id="query-panel-wrap" style="display: none">
  <div id="query-form" class="verification-popup">
    <ul>
      <li id="tab-query-comment-tab"><a href="#tab-query-comment">$lang[addComment]</a></li>
      <li id="tab-query-email-tab"><a href="#tab-query-email">$lang[sendEmail]</a></li>
    </ul>
    <fieldset class="verification-popup comment-popup" id="tab-query-comment" data-query="Q">
      <legend>
        <span class="fas fa-question-circle fa-2x"></span>
        $lang[commentTabTitle]
      </legend>
      <p class="alert alert-info"></p>
      <div class="comment-cntr form-group not-full-width-lg">
        $commentTools
        $queryCommentInput
      </div>
      $loadQueryTemplateDropdown
      <div class="form-buttons">
        <button class="indicia-button btn btn-primary save">$lang[addComment]</button>
        <button class="indicia-button btn btn-default cancel">$lang[cancel]</button>
      </div>
    </fieldset>
    <fieldset class="verification-popup query-popup" id="tab-query-email">
      <legend>
        <span class="fas fa-question-circle fa-2x"></span>
        $lang[emailTabTitle]
      </legend>
      <form novalidate="novalidate">
        <p class="alert alert-info"></p>
        <div class="form-group">
          <label for="email-to">$lang[sendEmailTo]:</label>
          <input id="email-to" class="form-control email required" placeholder="$lang[emailPlaceholder]">
        </div>
        <div class="form-group">
          <label for="email-subject">$lang[emailSubject]:</label>
          <input id="email-subject" class="form-control subject required">
        </div>
        <div class="form-group">
          <label for="email-body">$lang[emailBody]:</label>
          <textarea id="email-body" class="form-control required valid" rows="12"></textarea>
        </div>
        <button type="submit" class="$btnClass">$lang[sendEmail]</button>
        <button type="button" class="$btnClassDefault cancel">$lang[cancel]</button>
      </form>
    </fieldset>
  </div>
</div>

<div id="template-help-cntr" style="display: none">
  <article>
    <h3>$lang[templateHelpTitle]</h3>
    <p>$lang[templateHelpIntro1]</p>
    <p>$lang[templateHelpIntro2]</p>
    <p>$lang[templateHelpIntro3]</p>
    <p>$lang[templateHelpIntroExample]</p>
    <code>$lang[templateHelpExample]</code>
    <p>$lang[templateHelpIntroAvailableTokens]</p>
    </p>
    <ul>
      <li><code>{{ date }}</code> $lang[templateHelpTokenDate]</li>
      <li><code>{{ sref }}</code> $lang[templateHelpTokenSref]</li>
      <li><code>{{ taxon }}</code> $lang[templateHelpTokenTaxon]</li>
      <li><code>{{ common name }}</code> $lang[templateHelpTokenCommonName]</li>
      <li><code>{{ preferred name }}</code> $lang[templateHelpTokenPreferredName]</li>
      <li><code>{{ taxon full name }}</code> $lang[templateHelpTokenFullTaxonName]</li>
      <li><code>{{ rank }}</code> $lang[templateHelpTokenRank]</li>
      <li><code>{{ action }}</code> $lang[templateHelpTokenAction]</li>
      <li><code>{{ location name }}</code> $lang[templateHelpTokenLocationName]</li>
      <li><code>{{ new taxon }}</code> $lang[templateHelpTokenNewTaxon]</li>
      <li><code>{{ new common name }}</code> $lang[templateHelpTokenNewCommonName]</li>
      <li><code>{{ new preferred name }}</code> $lang[templateHelpTokenNewPreferredName]</li>
      <li><code>{{ new taxon full name }}</code> $lang[templateHelpTokenNewFullTaxonName]</li>
    </ul>
  </article>
  <button type="button" class="help-close $btnClass $indicia_templates[buttonSmallClass]">$lang[templateHelpClose]</button>
</div>
HTML;
    if (!empty($options['includeUploadButton'])) {
      $instruct = <<<TXT
This form can be used to upload a spreadsheet of verification decisions. First, use the
Download button to obtain a list of the records in your current verification grid. This has columns
called <strong>Decision status</strong> and <strong>Decision comment</strong>. For rows you wish to
verify, enter one of the status terms in the <strong>Decision status</strong> column and optionally
fill in the <strong>Decision comment</strong>. Any comments without an associated status will be
attached to the record without changing the status. When ready, save the spreadsheet and upload it
using this tool. Valid status terms include:
<ul>
  <li>Accepted</li>
  <li>Accepted as correct</li>
  <li>Accepted as considered correct</li>
  <li>Plausible</li>
  <li>Not accepted</li>
  <li>Not accepted as unable to verify</li>
  <li>Not accepted as incorrect</li>
  <li>Queried</li>
</ul>
TXT;
      $instruct = lang::get($instruct);
      $r .= <<<HTML
<div id="upload-decisions-form-cntr" style="display: none">
  <div id="upload-decisions-form">
    <div class="instruct">$instruct</div>
    <div class="form-group">
      <label for="decisions-file">Excel or CSV file:</label>
      <input type="file" class="form-control" id="decisions-file" accept=".csv, .xls, .xlsx" />
    </div>
    <button type="button" id="upload-decisions-file" class="btn btn-primary">Upload</button>
    <div class="upload-output alert alert-info" style="display: none">
      <div class="msg"></div>
      <progress value="0" max="100" style="display: none"></progress>
      <dl class="dl-horizontal">
        <dt>Rows checked:</dt>
        <dd class="checked">0</dd>
        <dt>Verifications, comments or queries found:</dt>
        <dd class="verifications">0</dd>
        <dt>Errors found:</dt>
        <dd class="errors">0</dd>
      </dl>
    </div>
  </div>
</div>
HTML;

    }
    return $r;
  }

  /**
   * Return the HTML for a comment template select control.
   *
   * @param string $name
   *   Fieldname to allocate to the control.
   *
   * @return string
   *   Control HTML.
   */
  private static function getTemplateSelect($name) {
    $lang = [
      'deleteTemplate' => lang::get('Delete the selected template'),
    ];
    return data_entry_helper::select([
      'label' => lang::get('Or, load the following comment template'),
      'fieldname' => $name,
      'class' => 'comment-template',
      'lookupValues' => [],
      'blankText' => lang::get('- select template to load -'),
      'afterControl' => "<i class=\"fas fa-trash-alt delete-template disabled\" title=\"$lang[deleteTemplate]\"></i>",
      'wrapClasses' => ['template-select'],
    ]);
  }

  /**
   * Applies token replacements to one or more values in the $options array.
   *
   * Tokens are of the format "{{ name }}" where the token name is one of the
   * following:
   * * indicia_user_id - the user's warehouse user ID.
   * * a parameter from the URL query string.
   *
   * @param array $options
   *   Control options.
   * @param array $fields
   *   List of the fields in the options array that replacements should be
   *   applied to.
   * @param array $jsonFields
   *   Subset of $fields where the value should be a JSON object after
   *   replacements are applied.
   */
  private static function applyReplacements(array &$options, array $fields, array $jsonFields) {
    $replacements = ['{{ indicia_user_id }}' => hostsite_get_user_field('indicia_user_id')];
    foreach ($_GET as $field => $value) {
      $replacements["{{ $field }}"] = $value;
    }
    foreach ($fields as $field) {
      if (!empty($options[$field]) && is_string($options[$field])) {
        $options[$field] = str_replace(array_keys($replacements), array_values($replacements), $options[$field]);
        if (in_array($field, $jsonFields)) {
          $options[$field] = json_decode($options[$field]);
        }
      }
    }
  }

  /**
   * Apply default settings for the mapGeoHash mode.
   *
   * @param array $options
   *   Options passed to the [source]. Will be modified as appropriate.
   */
  private static function applySourceModeDefaultsMapGeoHash(array &$options) {
    // Disable loading of docs.
    $options = array_merge([
      'size' => 0,
    ], $options);
    // Note the geohash_grid precision will be overridden depending on map zoom.
    $aggText = <<<AGG
{
  "by_hash": {
    "geohash_grid": {
      "field": "location.point",
      "precision": 1
    },
    "aggs": {
      "by_centre": {
        "geo_centroid": {
          "field": "location.point"
        }
      }
    }
  }
}
AGG;
    $options = array_merge(['aggregation' => json_decode($aggText)], $options);
  }

  /**
   * Apply default settings for the mapGridSquare mode.
   *
   * @param array $options
   *   Options passed to the [source]. Will be modified as appropriate.
   */
  private static function applySourceModeDefaultsMapGridSquare(array &$options) {
    $options = array_merge([
      'mapGridSquareSize' => 'autoGridSquareSize',
      'size' => 0,
    ], $options);
    if ($options['mapGridSquareSize'] === 'autoGridSquareSize') {
      $geoField = 'autoGridSquareField';
    }
    else {
      $sizeInKm = $options['mapGridSquareSize'] / 1000;
      $geoField = "location.grid_square.{$sizeInKm}km.centre";
    }
    $aggText = <<<AGG
{
  "by_srid": {
    "terms": {
      "field": "location.grid_square.srid",
      "size": 1000,
      "order": {
        "_count": "desc"
      }
    },
    "aggs": {
      "by_square": {
        "terms": {
          "field": "$geoField",
          "size": 100000,
          "order": {
            "_count": "desc"
          }
        }
      }
    }
  }
}
AGG;
    $options = array_merge(['aggregation' => json_decode($aggText)], $options);
  }

  /**
   * Apply default settings for the compositeAggregation mode.
   *
   * @param array $options
   *   Options passed to the [source]. Will be modified as appropriate.
   */
  private static function applySourceModeDefaultsCompositeAggregation(array &$options) {
    $options = array_merge([
      'fields' => [],
      'aggregation' => [],
    ], $options);
  }

  /**
   * Apply default settings for the termAggregation mode.
   *
   * @param array $options
   *   Options passed to the [source]. Will be modified as appropriate.
   */
  private static function applySourceModeDefaultsTermAggregation(array &$options) {
    if (empty($options['uniqueField'])) {
      throw new Exception("Sources require a parameter called @uniqueField when @mode=termAggregation");
    }
    if (!empty($options['orderbyAggregation']) && !is_object($options['orderbyAggregation'])
        && !is_array($options['orderbyAggregation'])) {
      throw new Exception("@orderbyAggregation option for source is not a valid JSON object.");
    }
    $options = array_merge([
      'fields' => [],
      'aggregation' => [],
    ], $options);
  }

  /**
   * Apply default settings depending on the @mode option.
   *
   * @param array $options
   *   Options passed to the [source]. Will be modified as appropriate.
   */
  private static function applySourceModeDefaults(array &$options) {
    if (!empty($options['mode'])) {
      $method = 'applySourceModeDefaults' . ucfirst($options['mode']);
      if (method_exists('ElasticsearchReportHelper', $method)) {
        self::$method($options);
      }
    }
  }

  /**
   * Provide common option handling for controls.
   *
   * * Sets a unique ID for the control if not already set.
   * * Checks that required options are all populated.
   * * Checks that options which should contain JSON do so.
   * * Source option converted to array if not already.
   *
   * @param string $controlName
   *   Name of the type of control.
   * @param array $options
   *   Options passed to the control (key and value associative array). Will be
   *   modified.
   * @param array $requiredOptions
   *   Array of option names which must have a value. Items can also be an
   *   array of option names if only one of several must be specified.
   * @param array $jsonOptions
   *   Array of option names which must contain JSON.
   */
  private static function checkOptions($controlName, array &$options, array $requiredOptions, array $jsonOptions) {
    self::$controlIndex++;
    // Generate a unique ID if not defined.
    $options = array_merge([
      'id' => "idc-$controlName-" . self::$controlIndex,
    ], $options);
    // Fail if duplicate ID on page.
    if (in_array($options['id'], self::$controlIds)) {
      throw new Exception("Control ID $options[id] is duplicated in the page configuration");
    }

    self::$controlIds[] = $options['id'];
    foreach ($requiredOptions as $requiredOption) {
      if (is_array($requiredOption)) {
        $count = 0;
        foreach ($requiredOption as $item) {
          if (!empty($options[$item])) {
            $count++;
          }
        }
        if ($count !== 1) {
          throw new Exception("Control [$controlName] requires exactly one of the following parameters: " . implode(', ', $requiredOption));
        }
      }
      elseif (!isset($options[$requiredOption]) || $options[$requiredOption] === '') {
        throw new Exception("Control [$controlName] requires a parameter called @$requiredOption");
      }
    }
    foreach ($jsonOptions as $option) {
      if (!empty($options[$option]) && !is_object($options[$option]) && !is_array($options[$option])) {
        throw new Exception("@$option option for [$controlName] is not a valid JSON object.");
      }
    }
    // Source option can be either a single named source, or an array of key
    // value pairs where the key is the source name and the value is the title,
    // e.g. for a multi-layer map. So, standardise it to the array style.
    if (!empty($options['source'])) {
      $options['source'] = is_string($options['source']) ? [$options['source'] => 'Source data'] : $options['source'];
    }
  }

  /**
   * Uses an Indicia report to convert a URL param to a list of filter values.
   *
   * For example, converts a scratchpad list ID to the list of taxa in the
   * scratchpad list.
   *
   * @param string $report
   *   Report path.
   * @param array $params
   *   List of parameters to pass to the report.
   * @param string $outputField
   *   Name of the field output by the report to build the list from.
   * @param array $readAuth
   *   Read authorisation tokens.
   *
   * @return string
   *   List for placing in the url param's hidden input attribute.
   */
  private static function convertValueToFilterList($report, array $params, $outputField, array $readAuth) {
    // Load the scratchpad's list of taxa.
    iform_load_helpers(['report_helper']);
    $listEntries = report_helper::get_report_data([
      'dataSource' => $report,
      'readAuth' => $readAuth,
      'extraParams' => $params,
    ]);
    // Build a hidden input which causes filtering to this list.
    $keys = [];
    foreach ($listEntries as $row) {
      if ($row[$outputField]) {
        // Don't want nulls as they break ES terms filters.
        $keys[] = $row[$outputField];
      }
    }
    return str_replace('"', '&quot;', json_encode($keys));
  }

  /**
   * Returns the HTML required to act as a control container.
   *
   * Creates the common HTML structure required to wrap any data output
   * control.
   *
   * @param string $controlName
   *   Control type name (e.g. source, dataGrid).
   * @param array $options
   *   Options passed to the control. If the control's containerElement option
   *   is set then sets the required JavaScript to make the control inject
   *   itself into the existing element instead. Can include an option for the
   *   id and class to assign to the container if not using containerElement.
   * @param string $dataOptions
   *   Options to store in the HTML data-idc-config attribute on the container.
   *   These are made available to configure the JS behaviour of the control.
   * @param string $content
   *   HTML to add to the container.
   *
   * @return string
   *   Control HTML.
   */
  private static function getControlContainer($controlName, array $options, $dataOptions, $content = '') {
    $initFn = 'idc' . ucfirst($controlName);
    if (!empty($options['containerElement'])) {
      // Use JS to attach to an existing element.
      helper_base::$javascript .= <<<JS
if ($('$options[containerElement]').length === 0) {
  indiciaFns.controlFail($('#$options[id]'), 'Invalid @containerElement selector for $options[id]');
}
$($('$options[containerElement]')[0]).append($('#$options[id]'));

JS;
    }
    helper_base::$javascript .= <<<JS
$('#$options[id]').$initFn({});

JS;
    $class = "idc-control idc-$controlName" . (empty($options['class']) ? '' : ' ' . $options['class']);
    return <<<HTML
<div id="$options[id]" class="$class" data-idc-class="$initFn" data-idc-config="$dataOptions">
  $content
</div>

HTML;
  }

  /**
   * Converts nested mappings data from ES to a flat field list.
   *
   * ES returns the mappings for an index as a hierarchical structure
   * representing the JSON document fields. This recursive function converts
   * this structure to a flat associative array of fields and field
   * configuration where the keys are the field names with their parent
   * elements separated by periods.
   *
   * @param array $data
   *   Mappings data structure retrieved from ES.
   * @param array $mappings
   *   Array which will be populated by the field list.
   * @param array $path
   *   Array of parent fields which define the path to the current element.
   */
  private static function recurseMappings(array $data, array &$mappings, array $path = []) {
    foreach ($data as $field => $config) {
      $thisPath = array_merge($path, [$field]);
      if (isset($config['properties'])) {
        self::recurseMappings($config['properties'], $mappings, $thisPath);
      }
      else {
        $field = implode('.', $thisPath);
        $mappings[$field] = [
          'type' => $config['type'],
        ];
        // We can't sort on text unless a keyword is specified.
        if ($config['type'] !== 'text' || (isset($config['fields']) && isset($config['fields']['keyword']))) {
          $mappings[$field]['sort_field'] = $field;
        }
        else {
          // Disable sorting.
          $mappings[$field]['sort_field'] = FALSE;
        }
      }
    }
  }

  /**
   * Retrieves the ES index mappings data.
   *
   * A list of mapped fields is stored in self::$esMappings.
   *
   * @param array $config
   *   Elasticsearch configuration.
   */
  private static function getMappings(array $config) {
    require_once 'ElasticsearchProxyHelper.php';
    if (empty($config['es']['endpoint'])) {
      throw new Exception(lang::get('Elasticsearch configuration incomplete - endpoint not specified in Indicia settings.'));
    }
    // /doc added to URL only for Elasticsearch 6.x.
    $url = $config['indicia']['base_url'] . 'index.php/services/rest/' . $config['es']['endpoint'] . '/_mapping' .
      ($config['es']['version'] == 6 ? '/doc' : '');
    $session = curl_init($url);
    curl_setopt($session, CURLOPT_HTTPHEADER, ElasticsearchProxyHelper::getHttpRequestHeaders($config));
    curl_setopt($session, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
    curl_setopt($session, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($session, CURLOPT_HEADER, FALSE);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
    // Do the POST and then close the session.
    $response = curl_exec($session);
    $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
    if ($httpCode !== 200) {
      $error = json_decode($response, TRUE);
      $msg = !empty($error['message'])
        ? $error['message']
        : curl_errno($session) . ': ' . curl_error($session);
      throw new Exception(lang::get('An error occurred whilst connecting to Elasticsearch. {1}', $msg));
    }
    curl_close($session);
    $mappingData = json_decode($response, TRUE);
    $mappingData = array_pop($mappingData);
    $mappings = [];
    // ES 6.x has a type (doc) within the index, ES 7.x doesn't support this.
    $props = $config['es']['version'] == 6
      ? $mappingData['mappings']['doc']['properties']
      : $mappingData['mappings']['properties'];
    self::recurseMappings($props, $mappings);
    self::$esMappings = $mappings;
  }

}
