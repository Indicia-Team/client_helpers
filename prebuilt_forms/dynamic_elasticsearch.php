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

/**
 * A prebuilt form for dynamically construction Elasticsearch content.
 *
 * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/prebuilt-forms/dynamic-elasticsearch.html
 */
class iform_dynamic_elasticsearch extends iform_dynamic {

  /**
   * Count controls to make unique IDs.
   *
   * @var integer
   */
  private static $controlIndex = 0;

  /**
   * Track control IDs so warning can be given if duplicate IDs are used.
   *
   * @var array
   */
  private static $controlIds = [];

  /**
   * Return the page metadata.
   *
   * @return array
   *   Form metadata.
   */
  public static function get_dynamic_elasticsearch_definition() {
    $description = <<<HTML
Provides a dynamically output page which links to an index of occurrence data in an <a href="https://www.elastic.co">
Elasticsearch</a> cluster.
This page can generate controls for the following:
<ul>
  <li>filtering</li>
  <li>downloading</li>
  <li>tabulating</li>
  <li>charting</li>
  <li>mapping</li>
  <li>verification</li>
</ul>
HTML;
    return array(
      'title' => 'Elasticsearch outputs (customisable)',
      'category' => 'Experimental',
      'description' => $description,
      'helpLink' => 'https://indicia-docs.readthedocs.io/en/latest/site-building/iform/prebuilt-forms/dynamic-elasticsearch.html',
    );
  }

  /**
   * Get the list of parameters for this form's Edit tab.
   *
   * @return array
   *   List of parameters that this form requires.
   */
  public static function get_parameters() {
    $collationPermissionDescription = <<<TXT
Permission required to access download of all records fall inside a location the user collates (e.g. a record centre
staff member). Requires a field_location_collation integer field holding a location ID in the user account.
TXT;
    return [
      [
        'name' => 'interface',
        'caption' => 'Interface Style Option',
        'description' => 'Choose the style of user interface, either dividing the form up onto separate tabs, wizard pages or having all controls on a single page.',
        'type' => 'select',
        'options' => array(
          'tabs' => 'Tabs',
          'wizard' => 'Wizard',
          'one_page' => 'All one page',
        ),
        'group' => 'User interface',
        'default' => 'tabs',
      ],
      [
        'name' => 'structure',
        'caption' => 'Form structure',
        'type' => 'textarea',
        'group' => 'User interface',
        'default' => '',
      ],
      [
        'name' => 'endpoint',
        'caption' => 'Endpoint',
        'description' => 'Elasticsearch endpoint declared in the REST API. Alternatively, leave this blank to use ' .
          'the site wide setting on the Indicia configuration settings page.',
        'type' => 'text_input',
        'group' => 'Elasticsearch settings',
        'required' => FALSE,
      ],
      [
        'name' => 'user',
        'caption' => 'User',
        'description' => 'REST API user with Elasticsearch access. Alternatively, leave this blank to use ' .
          'the site wide setting on the Indicia configuration settings page.',
        'type' => 'text_input',
        'group' => 'Elasticsearch settings',
        'required' => FALSE,
      ],
      [
        'name' => 'secret',
        'caption' => 'Secret',
        'description' => 'REST API user secret. Alternatively, leave this blank to use ' .
          'the site wide setting on the Indicia configuration settings page.',
        'type' => 'text_input',
        'group' => 'Elasticsearch settings',
        'required' => FALSE,
      ],
      [
        'name' => 'warehouse_prefix',
        'caption' => 'Warehouse ID prefix',
        'description' => 'Prefix given to numeric IDs to make them unique on the index.',
        'type' => 'text_input',
        'group' => 'Elasticsearch settings',
      ],
      [
        'name' => 'filter_json',
        'caption' => 'Filter JSON - Filter',
        'description' => 'JSON ',
        'type' => 'textarea',
        'required' => FALSE,
        'group' => 'Filter settings',
        'default' => '',
      ],
      [
        'name' => 'must_json',
        'caption' => 'Filter JSON - Must',
        'description' => 'JSON ',
        'type' => 'textarea',
        'required' => FALSE,
        'group' => 'Filter settings',
        'default' => '',
      ],
      [
        'name' => 'should_json',
        'caption' => 'Filter JSON - Should',
        'description' => 'JSON ',
        'type' => 'textarea',
        'required' => FALSE,
        'group' => 'Filter settings',
        'default' => '',
      ],
      [
        'name' => 'must_not_json',
        'caption' => 'Filter JSON - Must not',
        'description' => 'JSON ',
        'type' => 'textarea',
        'required' => FALSE,
        'group' => 'Filter settings',
        'default' => '',
      ],
      [
        'name' => 'my_records_permission',
        'caption' => 'My records download permission',
        'description' => "Permission required to access download of user's records.",
        'type' => 'text_input',
        'required' => FALSE,
        'group' => 'Permission settings',
        'default' => 'access iform',
      ],
      [
        'name' => 'all_records_permission',
        'caption' => 'All records download permission',
        'description' => 'Permission required to access download of all records that match the other filter criteria on the page.',
        'type' => 'text_input',
        'required' => FALSE,
        'group' => 'Permission settings',
        'default' => 'indicia data admin',
      ],
      [
        'name' => 'location_collation_records_permission',
        'caption' => 'Records in collated locality download permission',
        'description' => $collationPermissionDescription,
        'type' => 'text_input',
        'required' => FALSE,
        'group' => 'Permission settings',
        'default' => 'indicia data admin',
      ],
    ];
  }

  /**
   * Main build function to return the page HTML.
   *
   * @param array $args
   *   Page parameters.
   * @param int $nid
   *   Node ID.
   */
  public static function get_form($args, $nid) {
    require_once helper_base::client_helper_path() . 'ElasticsearchProxyHelper.php';
    ElasticsearchProxyHelper::enableElasticsearchProxy($nid);
    $ajaxUrl = hostsite_get_url('iform/ajax/dynamic_elasticsearch');
    data_entry_helper::$javascript .= <<<JS
indiciaData.ajaxUrl = '$ajaxUrl';

JS;
    $r = parent::get_form($args, $nid);
    // The following function must fire after the page content is built.
    data_entry_helper::$onload_javascript .= <<<JS
indiciaFns.populateDataSources();

JS;
    return $r;
  }

  /**
   * Provide common option handling for controls.
   *
   * * If attachToId specified, ensures that the control ID is set to the same
   *   value.
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
   *   Array of option names which must have a value.
   * @param array $jsonOptions
   *   Array of option names which must contain JSON.
   */
  private static function checkOptions($controlName, array &$options, array $requiredOptions, array $jsonOptions) {
    self::$controlIndex++;
    if (!empty($options['attachToId'])) {
      if (!empty($options['id']) && $options['id'] !== $options['attachToId']) {
        throw new Exception("Control ID $options[id] @attachToId does not match the @id option value.");
      }
      // If attaching to an existing element, force the ID.
      $options['id'] = $options['attachToId'];
    }
    else {
      // Otherwise, generate a unique ID if not defined.
      $options = array_merge([
        'id' => "idc-$controlName-" . self::$controlIndex,
      ], $options);
    }
    // Fail if duplicate ID on page.
    if (in_array($options['id'], self::$controlIds)) {
      throw new Exception("Control ID $options[id] is duplicated in the page configuration");
    }
    self::$controlIds[] = $options['id'];
    foreach ($requiredOptions as $option) {
      if (!isset($options[$option]) || $options[$option] === '') {
        throw new Exception("Control [$controlName] requires a parameter called @$option");
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
   * Initialises the JavaScript required for an Elasticsearch data source.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/prebuilt-forms/dynamic-elasticsearch.html#[source]
   *
   * @return string
   *   Empty string as no HTML required.
   */
  protected static function get_control_source($auth, $args, $tabalias, $options) {
    self::checkOptions(
      'esSource',
      $options,
      ['id'],
      ['aggregation', 'filterBoolClauses', 'buildTableXY', 'sort']
    );
    $options = array_merge([
      'aggregationMapMode' => 'geoHash',
    ], $options);
    $dataOptions = self::getOptionsForJs($options, [
      'id',
      'from',
      'size',
      'sort',
      'filterPath',
      'aggregation',
      'aggregationMapMode',
      'buildTableXY',
      'initialMapBounds',
      'filterBoolClauses',
      'filterSourceGrid',
      'filterField',
      'filterBoundsUsingMap',
    ]);
    data_entry_helper::$javascript .= <<<JS
indiciaData.esSources.push($dataOptions);

JS;
    // A source is entirely JS driven - no HTML.
    return '';
  }

  /**
   * Output a selector for a user's registered filters.
   */
  protected static function get_control_userFilters($auth, $args, $tabalias, $options) {
    require_once 'includes/report_filters.php';
    self::$controlIndex++;
    $options = array_merge([
      'id' => "es-user-filter-" . self::$controlIndex,
      'definesPermissions' => FALSE,
    ], $options);
    $filterData = report_filters_load_existing($auth['read'], $options['sharingCode'], TRUE);
    $optionArr = [];
    foreach ($filterData as $filter) {
      if (($filter['defines_permissions'] === 't') === $options['definesPermissions']) {
        $optionArr[$filter['id']] = $filter['title'];
      }
    }
    if (count($optionArr) === 0) {
      // No filters available. Until we support saving, doesn't make sense to
      // show the control.
      return '';
    }
    else {
      $controlOptions = [
        'label' => $options['definesPermissions'] ? lang::get('Context') : lang::get('Filter'),
        'fieldname' => $options['id'],
        'lookupValues' => $optionArr,
        'class' => 'user-filter',
      ];
      if (!$options['definesPermissions']) {
        $controlOptions['blankText'] = '- ' . lang::get('Please select') . ' - ';
      }
      return data_entry_helper::select($controlOptions);
    }
  }

  /**
   * Output a selector for various high level permissions filtering options.
   *
   * Options available will depend on the permissions set on the Permissions
   * section of the Edit tab.
   */
  protected static function get_control_permissionFilters($auth, $args, $tabalias, $options) {
    $allowedTypes = [];
    // Add My records download permission if allowed.
    if (!empty($args['my_records_permission']) && hostsite_user_has_permission($args['my_records_permission'])) {
      $allowedTypes['my'] = lang::get('My records');
    }
    // Add All records download permission if allowed.
    if (!empty($args['all_records_permission']) && hostsite_user_has_permission($args['all_records_permission'])) {
      $allowedTypes['all'] = lang::get('All records');
    }
    // Add collated location (e.g. LRC boundary) records download permission if
    // allowed.
    if (!empty($args['location_collation_records_permission'])
        && hostsite_user_has_permission($args['location_collation_records_permission'])) {
      $locationId = hostsite_get_user_field('location_collation');
      if ($locationId) {
        $locationData = data_entry_helper::get_population_data([
          'table' => 'location',
          'extraParams' => $auth['read'] + ['id' => $locationId],
        ]);
        if (count($locationData) > 0) {
          $allowedTypes['location_collation'] = lang::get('Records within location ' . $locationData[0]['name']);
        }
      }
    }
    if (count($allowedTypes) === 1) {
      $value = array_values($allowedTypes)[0];
      return <<<HTML
<input type="hidden" name="es-permissions-filter" value="$value" class="permissions-filter" />

HTML;
    }
    else {
      return data_entry_helper::select([
        'fieldname' => 'es-permissions-filter',
        'lookupValues' => $allowedTypes,
        'class' => 'permissions-filter',
      ]);
    }
  }

  /**
   * A button for downloading the ES data from a source.
   *
   * @return string
   *   HTML for download button and progress display.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/prebuilt-forms/dynamic-elasticsearch.html#[download]
   */
  protected static function get_control_download($auth, $args, $tabalias, $options) {
    self::checkOptions('esDownload', $options, ['source'], []);
    global $indicia_templates;
    $r = str_replace(
      [
        '{id}',
        '{title}',
        '{class}',
        '{caption}',
      ], [
        $options['id'],
        lang::get('Run the download'),
        "class=\"$indicia_templates[buttonHighlightedClass] do-download\"",
        lang::get('Download'),
      ],
      $indicia_templates['button']
    );
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
      </g>
      </text>
  </svg>
  <div class="progress-text"></div>
</div>

HTML;
    $r .= str_replace(
      [
        '{attrs}',
        '{col-1}',
        '{col-2}',
      ],
      [
        '',
        $progress,
        '<div class="idc-download-files"><h2>' . lang::get('Files') . ':</h2></div>',
      ],
      $indicia_templates['two-col-50']);
    // This does nothing at the moment - just a placeholder for if and when we
    // add some download options.
    $dataOptions = self::getOptionsForJs($options, ['source'], empty($options['attachToId']));
    helper_base::$javascript .= <<<JS
$('#$options[id]').idcEsDownload({});

JS;
    return self::getControlContainer('esDownload', $options, $dataOptions, $r);
  }

  /**
   * An Elasticsearch or Indicia powered grid control.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/prebuilt-forms/dynamic-elasticsearch.html#[dataGrid]
   */
  protected static function get_control_dataGrid($auth, $args, $tabalias, $options) {
    self::checkOptions(
      'dataGrid',
      $options,
      ['source'],
      ['actions', 'columns', 'responsiveOptions']
    );
    if (empty($options['columns']) && empty($options['autogenColumns'])) {
      throw new Exception('Control [dataGrid] requires a parameter called @columns or must have @autogenColumns=true');
    }
    if (!empty($options['scrollY']) && !preg_match('/^\d+px$/', $options['scrollY'])) {
      throw new Exception('Control [dataGrid] @scrollY parameter must be of CSS pixel format, e.g. 100px');
    }

    helper_base::add_resource('indiciaFootableReport');
    // Add footableSort for aggregation tables.
    if (!empty($options['simpleAggregation']) || !empty($options['sourceTable'])) {
      helper_base::add_resource('footableSort');
    }
    // Fancybox for image popups.
    helper_base::add_resource('fancybox');
    $dataOptions = self::getOptionsForJs($options, [
      'source',
      'columns',
      'actions',
      'includeColumnHeadings',
      'includeFilterRow',
      'includePager',
      'responsive',
      'responsiveOptions',
      'sortable',
      'simpleAggregation',
      'sourceTable',
      'autogenColumns',
      'scrollY',
    ], empty($options['attachToId']));
    helper_base::$javascript .= <<<JS
$('#$options[id]').idcDataGrid({});

JS;
    return self::getControlContainer('dataGrid', $options, $dataOptions);
  }

  /**
   * An Elasticsearch or Indicia powered map control.
   *
   * @deprecated Use leafletMap instead.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/prebuilt-forms/dynamic-elasticsearch.html#[map]
   */
  protected static function get_control_map($auth, $args, $tabalias, $options) {
    return self::get_control_leafletMap($auth, $args, $tabalias, $options);
  }

  /**
   * An Elasticsearch or Indicia data powered Leaflet map control.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/prebuilt-forms/dynamic-elasticsearch.html#[leafletMap]
   */
  protected static function get_control_leafletMap($auth, $args, $tabalias, $options) {
    self::checkOptions('leafletMap', $options, ['layerConfig'], ['layerConfig']);
    helper_base::add_resource('leaflet');
    $dataOptions = self::getOptionsForJs($options, [
      'layerConfig',
      'showSelectedRow',
      'initialLat',
      'initialLng',
      'initialZoom',
      'cookies',
    ], empty($options['attachToId']));
    helper_base::$javascript .= <<<JS
$('#$options[id]').idcLeafletMap({});
$('#$options[id]').idcLeafletMap('bindGrids');

JS;
    return self::getControlContainer('leafletMap', $options, $dataOptions);
  }

  /**
   * A control for flexibly outputting data formatted using HTML templates.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/prebuilt-forms/dynamic-elasticsearch.html#[templatedOutput]
   */
  protected static function get_control_templatedOutput($auth, $args, $tabalias, $options) {
    self::checkOptions('templatedOutput', $options, ['source', 'content'], []);
    $dataOptions = self::getOptionsForJs($options, [
      'source',
      'content',
      'header',
      'footer',
      'repeatField',
    ], empty($options['attachToId']));
    helper_base::$javascript .= <<<JS
$('#$options[id]').idcTemplatedOutput({});

JS;
    return self::getControlContainer('templatedOutput', $options, $dataOptions);
  }

  /**
   * Returns the HTML required to act as a control container.
   *
   * Creates the common HTML strucuture required to wrap any data output
   * control. If the control's @attachToId option is set then sets the
   * required JavaScript to make the control inject itself into the existing
   * element instead.
   *
   * @param string $controlName
   * @param array $options
   * @param string $dataOptions
   * @param string $content
   *
   * @return string
   *   Control HTML.
   */
  private static function getControlContainer($controlName, array $options, $dataOptions, $content='') {
    if (!empty($options['attachToId'])) {
      // Use JS to attach to an existing element.
      helper_base::$javascript .= <<<JS
$('#$options[attachToId]')
  .addClass('idc-output')
  .addClass("idc-output-$controlName")
  .attr('data-idc-output-config', '$dataOptions');

JS;
      return '';
    }
    return <<<HTML
<div id="$options[id]" class="idc-output idc-output-$controlName" data-idc-config="$dataOptions">
  $content
</div>

HTML;
  }

  /**
   * A panel containing buttons for record verification actions.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/prebuilt-forms/dynamic-elasticsearch.html#[verificationButtons]
   *
   * @return string
   *   Panel HTML;
   */
  protected static function get_control_verificationButtons($auth, $args, $tabalias, $options) {
    self::checkOptions('verificationButtons', $options, ['showSelectedRow'], []);
    if (!empty($options['editPath'])) {
      $options['editPath'] = helper_base::getRootFolder(TRUE) . $options['editPath'];
    }
    if (!empty($options['viewPath'])) {
      $options['viewPath'] = helper_base::getRootFolder(TRUE) . $options['viewPath'];
    }
    $dataOptions = self::getOptionsForJs($options, [
      'showSelectedRow',
      'editPath',
      'viewPath',
    ], TRUE);
    $userId = hostsite_get_user_field('indicia_user_id');
    $verifyUrl = iform_ajaxproxy_url(self::$nid, 'list_verify');
    $commentUrl = iform_ajaxproxy_url(self::$nid, 'occ-comment');
    helper_base::$javascript .= <<<JS
indiciaData.ajaxFormPostSingleVerify = '$verifyUrl&user_id=$userId&sharing=verification';
indiciaData.ajaxFormPostComment = '$commentUrl&user_id=$userId&sharing=verification';
$('#$options[id]').idcVerificationButtons({});

JS;
    $optionalLinkArray = [];
    if (!empty($options['editPath'])) {
      $optionalLinkArray[] = '<a class="edit" title="Edit this record"><span class="fas fa-edit"></span></a>';
    }
    if (!empty($options['viewPath'])) {
      $optionalLinkArray[] = '<a class="view" title="View this record\'s details page"><span class="fas fa-file-invoice"></span></a>';
    }
    $optionalLinks = implode("\n  ", $optionalLinkArray);
    helper_base::add_resource('fancybox');
    return <<<HTML
<div id="$options[id]" class="idc-verification-buttons" style="display: none;" data-idc-config="$dataOptions">
    <div class="selection-buttons-placeholder">
      <div class="all-selected-buttons idc-verification-buttons-row">
        Actions:
        <span class="fas fa-toggle-on toggle fa-2x" title="Toggle additional status levels"></span>
        <button class="verify l1" data-status="V" title="Accepted"><span class="far fa-check-circle status-V"></span></button>
        <button class="verify l2" data-status="V1" title="Accepted :: correct"><span class="far fa-check-double status-V1"></span></button>
        <button class="verify l2" data-status="V2" title="Accepted :: considered correct"><span class="fas fa-check status-V2"></span></button>
        <button class="verify" data-status="C3" title="Plausible"><span class="fas fa-check-square status-C3"></span></button>
        <button class="verify l1" data-status="R" title="Not accepted"><span class="far fa-times-circle status-R"></span></button>
        <button class="verify l2" data-status="R4" title="Not accepted :: unable to verify"><span class="fas fa-times status-R4"></span></button>
        <button class="verify l2" data-status="R5" title="Not accepted :: incorrect"><span class="fas fa-times status-R5"></span></button>
        <span class="sep"></span>
        <button class="query" data-query="Q" title="Raise a query"><span class="fas fa-question-circle query-Q"></span></button>
      </div>
    </div>
    <div class="single-record-buttons idc-verification-buttons-row">
      $optionalLinks
    </div>
  </div>
</div>
HTML;
  }

  /**
   * A tabbed control to show full record details and verification info.
   *
   * @return string
   *   Control HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/prebuilt-forms/dynamic-elasticsearch.html#[recordDetails]
   */
  protected static function get_control_recordDetails($auth, $args, $tabalias, $options) {
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
    $dataOptions = self::getOptionsForJs($options, [
      'showSelectedRow',
      'exploreUrl',
      'locationTypes',
      'allowRedetermination',
    ], TRUE);
    helper_base::add_resource('tabs');
    helper_base::$javascript .= <<<JS
$('#$options[id]').idcRecordDetailsPane({});

JS;
    $r = <<<HTML
<div class="details-container" id="$options[id]" data-idc-config="$dataOptions">
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
    if ($options['allowRedetermination']) {
      helper_base::add_resource('validation');
      $redetUrl = iform_ajaxproxy_url(self::$nid, 'occurrence');
      $userId = hostsite_get_user_field('indicia_user_id');
      helper_base::$javascript .= <<<JS
indiciaData.ajaxFormPostRedet = '$redetUrl&user_id=$userId&sharing=editing';

JS;
      $speciesInput = data_entry_helper::species_autocomplete([
        'label' => lang::get('Redetermine to'),
        'helpText' => lang::get('Select the new taxon name.'),
        'fieldname' => 'redet-species',
        'extraParams' => $auth['read'] + ['taxon_list_id' => 1],
        'speciesIncludeAuthorities' => TRUE,
        'speciesIncludeBothNames' => TRUE,
        'speciesNameFilterMode' => 'preferred',
        'validation' => ['required'],
      ]);
      $commentInput = data_entry_helper::textarea([
        'label' => lang::get('Explanation comment'),
        'helpText' => lang::get('Please give reasons why you are changing this record.'),
        'fieldname' => 'redet-comment',
      ]);
      $r .= <<<HTML
<div id="redet-panel-wrap" style="display: none">
  <form id="redet-form">
    $speciesInput
    $commentInput
    <button type="submit" class="btn btn-primary" id="apply-redet">Apply redetermination</button>
    <button type="button" class="btn btn-danger" id="cancel-redet">Cancel</button>
  </form>
</div>
HTML;
    }
    return $r;
  }

  /**
   * Retrieve parameters from the URL and add to the ES requests.
   *
   * Currently only supports taxon scratchpad list filtering.
   *
   * Options can include:
   * * @taxon_scratchpad_list_id - set to false to disable filtering species by
   *   a provided scratchpad list ID.
   *
   * @return string
   *   Hidden input HTML which defines the appropriate filters.
   */
  protected static function get_control_urlParams($auth, $args, $tabalias, $options) {
    $options = array_merge([
      'taxon_scratchpad_list_id' => TRUE,
      // Other options, e.g. group_id or field params may be added in future.
    ], $options);
    $r = '';
    if (!empty($options['taxon_scratchpad_list_id']) && !empty($_GET['taxon_scratchpad_list_id'])) {
      // Check the parameter is valid.
      $taxonScratchpadListId = $_GET['taxon_scratchpad_list_id'];
      if (!preg_match('/^\d+$/', $taxonScratchpadListId)) {
        hostsite_show_message(
          lang::get('The taxon_scratchpad_list_id parameter should be a whole number which is the ID of a scratchpad list.'),
          'warning'
        );
      }
      // Load the scratchpad's list of taxa.
      iform_load_helpers(['report_helper']);
      $listEntries = report_helper::get_report_data([
        'dataSource' => 'library/taxa/external_keys_for_scratchpad',
        'readAuth' => $auth['read'],
        'extraParams' => ['scratchpad_list_id' => $taxonScratchpadListId],
      ]);
      // Build a hidden input which causes filtering to this list.
      $keys = [];
      foreach ($listEntries as $row) {
        $keys[] = $row['external_key'];
      }
      $keyJson = str_replace('"', '&quot;', json_encode($keys));
      $r .= <<<HTML
<input type="hidden" class="es-filter-param" value="$keyJson"
  data-es-bool-clause="must" data-es-field="taxon.higher_taxon_ids" data-es-query-type="terms" />
HTML;
    }
    return $r;
  }

  /**
   * A select box for choosing from a list of higher geography boundaries.
   *
   * Lists indexed locations for a given type. When a location is chosen, the
   * boundary is shown and the ES data is filtered to records which intersect
   * the boundary.
   *
   * Options are:
   *
   * * @locationTypeId - ID of the location type of the locations to list. Must
   *   be a type indexed by the spatial index builder module.
   *
   * @return string
   *   Control HTML
   */
  protected static function get_control_higherGeographySelect($auth, $args, $tabalias, $options) {
    if (empty($options['locationTypeId']) || !preg_match('/^\d+$/', $options['locationTypeId'])) {
      throw new Exception('An integer @locationTypeId parameter is required for the [higherGeographySelect] control');
    }
    $options = array_merge([
      'id' => 'higher-geography-select',
    ], $options);
    return data_entry_helper::location_select([
      'id' => $options['id'],
      'class' => 'es-higher-geography-select',
      'extraParams' => $auth['read'] + [
        'location_type_id' => $options['locationTypeId'],
        'orderby' => 'name',
      ],
      'blankText' => lang::get('<All locations shown>'),
    ]);
  }

  /**
   * Ajax method which echoes custom attribute data to the client.
   *
   * At the moment, this info is built from the Indicia warehouse, not
   * Elasticsearch.
   *
   * @param int $website_id
   *   Warehouse website ID.
   * @param string $password
   *   Warehouse password.
   */
  public static function ajax_attrs($website_id, $password) {
    $readAuth = report_helper::get_read_auth($website_id, $password);
    $options = array(
      'dataSource' => 'reports_for_prebuilt_forms/dynamic_elasticsearch/record_details',
      'readAuth' => $readAuth,
      // @todo Sharing should be dynamically set in a form parameter (use $nid param).
      'sharing' => 'verification',
      'extraParams' => array('occurrence_id' => $_GET['occurrence_id']),
    );
    $reportData = report_helper::get_report_data($options);
    // Convert the output to a structured JSON object.
    $data = [];
    foreach ($reportData as $attribute) {
      if (!empty($attribute['value'])) {
        if (!isset($data[$attribute['attribute_type'] . ' attributes'])) {
          $data[$attribute['attribute_type'] . ' attributes'] = array();
        }
        $data[$attribute['attribute_type'] . ' attributes'][] = array('caption' => $attribute['caption'], 'value' => $attribute['value']);
      }
    }
    header('Content-type: application/json');
    echo json_encode($data);
  }

  /**
   * Ajax handler for the [recordDetails] comments tab.
   *
   * @param int $website_id
   *   Warehouse website ID.
   * @param string $password
   *   Warehouse password.
   */
  public static function ajax_comments($website_id, $password) {
    $readAuth = report_helper::get_read_auth($website_id, $password);
    $options = array(
      'dataSource' => 'reports_for_prebuilt_forms/verification_5/occurrence_comments_and_dets',
      'readAuth' => $readAuth,
      // @todo Sharing should be dynamically set in a form parameter (use $nid param).
      'sharing' => 'verification',
      'extraParams' => array('occurrence_id' => $_GET['occurrence_id']),
    );
    $reportData = report_helper::get_report_data($options);
    header('Content-type: application/json');
    echo json_encode($reportData);
  }

  protected static function getHeader($args) {
    return '';
  }

  protected static function getFooter($args) {
    return '';
  }

  protected static function getFirstTabAdditionalContent($args, $auth, &$attributes) {
    return '';
  }

}
