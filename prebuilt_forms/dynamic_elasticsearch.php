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
 * @link http://code.google.com/p/indicia/
 */

require_once 'includes/dynamic.php';

/**
 * A prebuilt form for dynamically construction Elasticsearch content.
 *
 * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/prebuilt-forms/dynamic-elasticsearch.html
 */
class iform_dynamic_elasticsearch extends iform_dynamic {

  private static $esMappings;

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
        'description' => 'Elasticsearch endpoint declared in the REST API.',
        'type' => 'text_input',
        'group' => 'Elasticsearch settings',
      ],
      [
        'name' => 'user',
        'caption' => 'User',
        'description' => 'REST API user with Elasticsearch access.',
        'type' => 'text_input',
        'group' => 'Elasticsearch settings',
      ],
      [
        'name' => 'secret',
        'caption' => 'Secret',
        'description' => 'REST API user secret.',
        'type' => 'text_input',
        'group' => 'Elasticsearch settings',
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
    // Retrieve the Elasticsearch mappings.
    self::getMappings($nid);
    // Prepare the stuff we need to pass to the JavaScript.
    $mappings = json_encode(self::$esMappings);
    $ajaxUrl = hostsite_get_url('iform/ajax/dynamic_elasticsearch');
    $userId = hostsite_get_user_field('indicia_user_id');
    $rootFolder = helper_base::getRootFolder(TRUE);
    $dateFormat = helper_base::$date_format;
    data_entry_helper::$javascript .= <<<JS
indiciaData.ajaxUrl = '$ajaxUrl';
indiciaData.esSources = [];
indiciaData.esMappings = $mappings;
indiciaData.userId = $userId;
indiciaData.rootFolder = '$rootFolder';
indiciaData.dateFormat = '$dateFormat';

JS;
    helper_base::add_resource('datacomponents');
    $r = parent::get_form($args, $nid);
    // The following function must fire after the page content is built.
    data_entry_helper::$onload_javascript .= <<<JS
indiciaFns.populateDataSources();

JS;
    return $r;
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
        if (isset($config['fields']) && isset($config['fields']['keyword'])) {
          $mappings[$field]['sort_field'] = "$field.keyword";
        }
        elseif ($config['type'] !== 'text') {
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
   * @param int $nid
   *   Node ID, used to retrieve the node parameters which contain ES settings.
   */
  private static function getMappings($nid) {
    $params = hostsite_get_node_field_value($nid, 'params');
    $url = helper_base::$base_url . 'index.php/services/rest/' . $params['endpoint'] . '/_mapping/doc';
    $session = curl_init($url);
    curl_setopt($session, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json',
      "Authorization: USER:$params[user]:SECRET:$params[secret]",
    ]);
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
    self::recurseMappings($mappingData['mappings']['doc']['properties'], $mappings);
    self::$esMappings = $mappings;
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
    $dataOptions = self::getOptionsForJs($options, [
      'id',
      'from',
      'size',
      'sort',
      'filterPath',
      'aggregation',
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
    $dataOptions = self::getOptionsForJs($options, [], empty($options['attachToId']));
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
      ['actions', 'columns']
    );
    if (empty($options['columns']) && empty($options['autogenColumns'])) {
      throw new Exception("Control [dataGrid] requires a parameter called @columns or must have @autogenColumns=true");
    }
    helper_base::add_resource('indiciaFootableReport');
    // Add footableSort for aggregation tables.
    if (!empty($options['simpleAggregation']) || !empty($options['sourceTable'])) {
      helper_base::add_resource('footableSort');
    }
    // Fancybox for image popups.
    helper_base::add_resource('fancybox');
    $dataOptions = self::getOptionsForJs($options, [
      'columns',
      'actions',
      'includeColumnHeadings',
      'includeFilterRow',
      'includePager',
      'sortable',
      'simpleAggregation',
      'sourceTable',
      'autogenColumns',
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
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/prebuilt-forms/dynamic-elasticsearch.html#[map]
   */
  protected static function get_control_leafletMap($auth, $args, $tabalias, $options) {
    self::checkOptions('leafletMap', $options, ['source'], ['styles']);
    $options = array_merge([
      'styles' => new stdClass(),
    ], $options);
    helper_base::add_resource('leaflet');
    $dataOptions = self::getOptionsForJs($options, [
      'styles',
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

  protected static function get_control_templatedOutput($auth, $args, $tabalias, $options) {
    self::checkOptions('templatedOutput', $options, ['source', 'content'], []);
    $dataOptions = self::getOptionsForJs($options, [
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

  private static function getControlContainer($controlName, $options, $dataOptions, $content='') {
    if (!empty($options['attachToId'])) {
      $source = json_encode($options['source']);
      // Use JS to attach to an existing element.
      helper_base::$javascript .= <<<JS
$('#$options[attachToId]')
  .addClass('idc-output')
  .addClass("idc-output-$controlName")
  .attr('data-idc-esSource', '$source')
  .attr('data-idc-output-config', '$dataOptions');

JS;
      return '';
    }
    // Escape the source so it can output as an attribute.
    $source = str_replace('"', '&quot;', json_encode($options['source']));
    return <<<HTML
<div id="$options[id]" class="idc-output idc-output-$controlName" data-es-source="$source" data-idc-config="$dataOptions">
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
   * Supports the following options:
   * * showSelectedRow - ID of the grid whose selected row should be shown.
   *   Required.
   * * explorePath - path to an Explore all records page that can be used to
   *   show filtered records, e.g. the records underlying the data on the
   *   experience tab. Optional.
   * * locationTypes - the record details pane will show all indexed location
   *   types unless you provide an array of the type names that you would
   *   like included, e.g. ["Country","Vice County"]. Optional.
   */
  protected static function get_control_recordDetails($auth, $args, $tabalias, $options) {
    self::checkOptions('recordDetails', $options, ['showSelectedRow'], ['locationTypes', 'externalKeyUrls']);
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
      'externalKeyUrls',
    ], TRUE);
    helper_base::add_resource('tabs');
    helper_base::$javascript .= <<<JS
$('#$options[id]').idcRecordDetailsPane({});

JS;
    return <<<HTML
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

  private static function getDefinitionFilter($definition, array $params) {
    foreach ($params as $param) {
      if (!empty($definition[$param])) {
        return [
          'value' => $definition[$param],
          'op' => empty($definition[$param . '_op']) ? FALSE : $definition[$param . '_op'],
        ];
      }
    }
    return [];
  }

  private static function applyPermissionsFilter(array &$bool) {
    if (!empty($_POST['permissions_filter'])) {
      switch ($_POST['permissions_filter']) {
        case 'my':
          $bool['must'][] = [
            'term' => ['metadata.created_by_id' => hostsite_get_user_field('indicia_user_id')],
          ];
          break;

        case 'location_collation':
          $bool['must'][] = [
            'nested' => [
              'path' => 'location.higher_geography',
              'query' => [
                'term' => ['location.higher_geography.id' => hostsite_get_user_field('location_collation')],
              ],
            ],
          ];
          break;

        default:
          // All records, no filter.
      }
    }
  }

  /**
   * Converts Indicia style filters in proxy request to ES query syntax.
   *
   * Support for filter definitions is incomplete. Currently only the following
   * parameters are converted:
   * * website_list & website_list_op
   * * survey_list & survey_list_op
   * * group_id
   * * taxon_group_list
   * * higher_taxa_taxon_list_list
   * * taxa_taxon_list_list
   * * indexed_location_list & indexed_location_list_op.
   *
   * @param array $readAuth
   *   Read authentication tokens.
   * @param array $query
   *   Query passed in proxy request, which may contain an array of
   *   user_filters (filter IDs) to convert.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFilters(array $readAuth, array $query, array &$bool) {
    foreach ($query['user_filters'] as $userFilter) {
      $filterData = data_entry_helper::get_population_data([
        'table' => 'filter',
        'extraParams' => [
          'id' => $userFilter,
        ] + $readAuth,
      ]);
      if (count($filterData) === 0) {
        throw new exception("Filter with ID $userFilter could not be loaded.");
      }
      $definition = json_decode($filterData[0]['definition'], TRUE);
      self::applyUserFiltersWebsiteList($readAuth, $definition, ['website_list', 'website_id'], $bool);
      self::applyUserFiltersSurveyList($readAuth, $definition, ['survey_list', 'survey_id'], $bool);
      self::applyUserFiltersGroupId($readAuth, $definition, ['group_id'], $bool);
      self::applyUserFiltersTaxonGroupList($readAuth, $definition, ['taxon_group_list', 'taxon_group_id'], $bool);
      self::applyUserFiltersTaxaTaxonList($readAuth, $definition, [
        'taxa_taxon_list_list',
        'higher_taxa_taxon_list_list',
        'taxa_taxon_list_id',
        'higher_taxa_taxon_list_id',
      ], $bool);
      self::applyUserFiltersTaxonRankSortOrder($readAuth, $definition, ['taxon_rank_sort_order'], $bool);
      self::applyUserFiltersIndexedLocationList($readAuth, $definition, [
        'indexed_location_list',
        'indexed_location_id',
      ], $bool);
      self::applyUserFiltersHasPhotos($readAuth, $definition, ['has_photos'], $bool);
    }
  }

  /**
   * Converts an Indicia filter definition website_list to an ES query.
   *
   * @param array $readAuth
   *   Read authentication tokens.
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $params
   *   List of parameter names that can be used for this type of filter
   *   (allowing for deprecated names etc).
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersWebsiteList(array $readAuth, array $definition, array $params, array &$bool) {
    $filter = self::getDefinitionFilter($definition, $params);
    if (!empty($filter)) {
      $boolClause = !empty($filter['op']) && $filter['op'] === 'not in' ? 'must_not' : 'must';
      $bool[$boolClause][] = [
        'terms' => ['metadata.website.id' => explode(',', $filter['value'])],
      ];
    }
  }

  /**
   * Converts an Indicia filter definition survey_list to an ES query.
   *
   * @param array $readAuth
   *   Read authentication tokens.
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $params
   *   List of parameter names that can be used for this type of filter
   *   (allowing for deprecated names etc).
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersSurveyList(array $readAuth, array $definition, array $params, array &$bool) {
    $filter = self::getDefinitionFilter($definition, $params);
    if (!empty($filter)) {
      $boolClause = !empty($filter['op']) && $filter['op'] === 'not in' ? 'must_not' : 'must';
      $bool[$boolClause][] = [
        'terms' => ['metadata.survey.id' => explode(',', $filter['value'])],
      ];
    }
  }

  /**
   * Converts an Indicia filter definition group_id to an ES query.
   *
   * @param array $readAuth
   *   Read authentication tokens.
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $params
   *   List of parameter names that can be used for this type of filter
   *   (allowing for deprecated names etc).
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersGroupId(array $readAuth, array $definition, array $params, array &$bool) {
    $filter = self::getDefinitionFilter($definition, $params);
    if (!empty($filter)) {
      $bool['must'][] = [
        'terms' => ['metadata.group.id' => explode(',', $filter['value'])],
      ];
    }
  }

  /**
   * Converts an Indicia filter definition taxon_group_list to an ES query.
   *
   * @param array $readAuth
   *   Read authentication tokens.
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $params
   *   List of parameter names that can be used for this type of filter
   *   (allowing for deprecated names etc).
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersTaxonGroupList(array $readAuth, array $definition, array $params, array &$bool) {
    $filter = self::getDefinitionFilter($definition, $params);
    if (!empty($filter)) {
      $bool['must'][] = [
        'terms' => ['taxon.group_id' => explode(',', $filter['value'])],
      ];
    }
  }

  /**
   * Converts an Indicia filter definition taxa_taxon_list_list to an ES query.
   *
   * @param array $readAuth
   *   Read authentication tokens.
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $params
   *   List of parameter names that can be used for this type of filter
   *   (allowing for deprecated names etc).
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersTaxaTaxonList(array $readAuth, array $definition, array $params, array &$bool) {
    $filter = self::getDefinitionFilter($definition, $params);
    if (!empty($filter)) {
      $taxonData = data_entry_helper::get_population_data([
        'table' => 'taxa_taxon_list',
        'extraParams' => [
          'view' => 'cache',
          'query' => json_encode(['in' => ['id' => explode(',', $filter['value'])]]),
        ] + $readAuth,
      ]);
      $keys = [];
      foreach ($taxonData as $taxon) {
        $keys[] = $taxon['external_key'];
      }
      $bool['must'][] = ['terms' => ['taxon.higher_taxon_ids' => $keys]];
    }
  }

  /**
   * Converts a filter definition taxon_rank_sort_order filter to an ES query.
   *
   * @param array $readAuth
   *   Read authentication tokens.
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $params
   *   List of parameter names that can be used for this type of filter
   *   (allowing for deprecated names etc).
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersTaxonRankSortOrder(array $readAuth, array $definition, array $params, array &$bool) {
    $filter = self::getDefinitionFilter($definition, $params);
    // Filter op can be =, >= or <=.
    if (!empty($filter)) {
      if ($filter['op'] === '=') {
        $bool['must'][] = [
          'match' => [
            'taxon.taxon_rank_sort_order' => [
              'query' => $filter['value'],
              'type' => 'phrase',
            ],
          ],
        ];
      }
      else {
        $gte = $filter['op'] === '>=' ? $filter['value'] : NULL;
        $lte = $filter['op'] === '<=' ? $filter['value'] : NULL;
        $bool['must'][] = [
          'range' => [
            'taxon.taxon_rank_sort_order' => [
              'gte' => $gte,
              'lte' => $lte,
            ],
          ],
        ];
      }
    }
  }

  /**
   * Converts an Indicia filter definition indexed_location_list to an ES query.
   *
   * @param array $readAuth
   *   Read authentication tokens.
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $params
   *   List of parameter names that can be used for this type of filter
   *   (allowing for deprecated names etc).
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersIndexedLocationList(array $readAuth, array $definition, array $params, array &$bool) {
    $filter = self::getDefinitionFilter($definition, $params);
    if (!empty($filter)) {
      $boolClause = $filter['value'] === '0' ? 'must_not' : 'must';
      $bool[$boolClause][] = [
        'nested' => [
          'path' => 'location.higher_geography',
          'query' => [
            'terms' => ['location.higher_geography.id' => explode(',', $filter['value'])],
          ],
        ],
      ];
    }
  }

  /**
   * Converts an Indicia filter definition has_photos filter to an ES query.
   *
   * @param array $readAuth
   *   Read authentication tokens.
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $params
   *   List of parameter names that can be used for this type of filter
   *   (allowing for deprecated names etc).
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersHasPhotos(array $readAuth, array $definition, array $params, array &$bool) {
    $filter = self::getDefinitionFilter($definition, $params);
    if (!empty($filter)) {
      $boolClause = !empty($filter['op']) && $filter['op'] === 'not in' ? 'must_not' : 'must';
      $bool[$boolClause][] = ['exists' => ['field' => 'occurrence.associated_media']];
    }
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

  private static function buildEsQueryFromRequest($website_id, $password) {
    $query = array_merge($_POST);
    unset($query['warehouse_url']);
    $bool = [
      'must' => [],
      'should' => [],
      'must_not' => [],
      'filter' => [],
    ];
    $basicQueryTypes = ['match_all', 'match_none'];
    $fieldQueryTypes = ['term', 'match', 'match_phrase', 'match_phrase_prefix'];
    $arrayFieldQueryTypes = ['terms'];
    $stringQueryTypes = ['query_string', 'simple_query_string'];
    if (isset($query['filters'])) {
      // Apply any filter row paramenters to the query.
      foreach ($query['filters'] as $field => $value) {
        $bool['must'][] = ['match_phrase_prefix' => [$field => $value]];
      }
      unset($query['filters']);
    }
    foreach ($query['bool_queries'] as $qryConfig) {
      if (!empty($qryConfig['query'])) {
        $bool[$qryConfig['bool_clause']][] = json_decode(
          str_replace('#value#', $qryConfig['value'], $qryConfig['query']), TRUE
        );
      }
      elseif (in_array($qryConfig['query_type'], $basicQueryTypes)) {
        $bool[$qryConfig['bool_clause']][] = [$qryConfig['query_type'] => new stdClass()];
      }
      elseif (in_array($qryConfig['query_type'], $fieldQueryTypes)) {
        // One of the standard ES field based query types (e.g. term or match).
        $bool[$qryConfig['bool_clause']][] = [$qryConfig['query_type'] => [$qryConfig['field'] => $qryConfig['value']]];
      }
      elseif (in_array($qryConfig['query_type'], $arrayFieldQueryTypes)) {
        // One of the standard ES field based query types (e.g. term or match).
        $bool[$qryConfig['bool_clause']][] = [$qryConfig['query_type'] => [$qryConfig['field'] => json_decode($qryConfig['value'], TRUE)]];
      }
      elseif (in_array($qryConfig['query_type'], $stringQueryTypes)) {
        // One of the ES query string based query types.
        $bool[$qryConfig['bool_clause']][] = [$qryConfig['query_type'] => ['query' => $qryConfig['value']]];
      }

    }
    unset($query['bool_queries']);
    iform_load_helpers([]);
    $readAuth = helper_base::get_read_auth($website_id, $password);
    self::applyPermissionsFilter($bool);
    if (!empty($query['user_filters'])) {
      self::applyUserFilters($readAuth, $query, $bool);
    }
    unset($query['user_filters']);
    unset($query['permissions_filter']);
    $bool = array_filter($bool, function ($k) {
      return count($k) > 0;
    });
    if (!empty($bool)) {
      $query['query'] = ['bool' => $bool];
    }
    return $query;
  }

  public static function ajax_esproxy_searchbyparams($website_id, $password, $nid) {
    $params = hostsite_get_node_field_value($nid, 'params');
    self::checkPermissionsFilter($params);
    $url = $_POST['warehouse_url'] . 'index.php/services/rest/' . $params['endpoint'] . '/_search';
    $query = self::buildEsQueryFromRequest($website_id, $password);
    self::curlPost($url, $query, $params);
  }

  /**
   * A search proxy that passes through the data as is.
   *
   * Should only be used with aggregations where the size parameter is zero to
   * avoid permissions issues, as it does not apply the basic permissions
   * filter. For example a report page that shows "my records" may also include
   * aggregated data across the entire dataset which is not limited by the page
   * permissions.
   */
  public static function ajax_esproxy_rawsearch($website_id, $password, $nid) {
    $params = hostsite_get_node_field_value($nid, 'params');
    $url = $_POST['warehouse_url'] . 'index.php/services/rest/' . $params['endpoint'] . '/_search';
    $query = array_merge($_POST);
    unset($query['warehouse_url']);
    $query['size'] = 0;
    self::curlPost($url, $query, $params);
  }

  /**
   * A search proxy that handles build of a CSV download file.
   */
  public static function ajax_esproxy_download($website_id, $password, $nid) {
    $params = hostsite_get_node_field_value($nid, 'params');
    $isScrollToNextPage = array_key_exists('scroll_id', $_POST);
    if (!$isScrollToNextPage) {
      self::checkPermissionsFilter($params);
    }
    $url = $_POST['warehouse_url'] . 'index.php/services/rest/' . $params['endpoint'] . '/_search?format=csv';
    $query = self::buildEsQueryFromRequest($website_id, $password);
    if ($isScrollToNextPage) {
      $url .= '&scroll_id=' . $_POST['scroll_id'];
    }
    else {
      $url .= '&scroll';
    }
    self::curlPost($url, $query, $params);
  }

  /**
   * Proxy method that receives a list of IDs to perform updates on in Elastic.
   *
   * Used by the verification system when in checklist mode to allow setting a
   * comment and status on multiple records in one go.
   */
  public static function ajax_esproxy_updateids($website_id, $password, $nid) {
    $params = hostsite_get_node_field_value($nid, 'params');
    $url = $_POST['warehouse_url'] . 'index.php/services/rest/' . $params['endpoint'] . "/_update_by_query";
    $scripts = [];
    if (!empty($_POST['doc']['identification']['verification_status'])) {
      $scripts[] = "ctx._source.identification.verification_status = '" . $_POST['doc']['identification']['verification_status'] . "'";
    }
    if (!empty($_POST['doc']['identification']['verification_substatus'])) {
      $scripts[] = "ctx._source.identification.verification_substatus = '" . $_POST['doc']['identification']['verification_substatus'] . "'";
    }
    if (!empty($_POST['doc']['identification']['query'])) {
      $scripts[] = "ctx._source.identification.query = '" . $_POST['doc']['identification']['query'] . "'";
    }
    $_ids = [];
    // Convert Indicia IDs to the document _ids for ES.
    foreach ($_POST['ids'] as $id) {
      $_ids[] = $params['warehouse_prefix'] . $id;
    }
    $doc = [
      'script' => [
        'source' => implode("; ", $scripts),
        'lang' => 'painless',
      ],
      'query' => [
        'terms' => [
          '_id' => $_ids,
        ],
      ],
    ];
    self::curlPost($url, $doc, $params);
  }

  /**
   * Confirm that a permissions filter in the request is allowed for the user.
   *
   * For example, permissions may be different for accessing my vs all records
   * so this method checks against the Drupal permissions defined on the edit
   * tab, ensuring calls to the proxy can't be easily hacked.
   *
   * @param array $params
   *   Form parameters from the Edit tab which include permission settings.
   */
  private static function checkPermissionsFilter(array $params) {

    $permissionsFilter = empty($_POST['permissions_filter']) ? 'all' : $_POST['permissions_filter'];
    $validPermissionsFilter = [
      'all',
      'my',
      'location_collation',
    ];
    if (!in_array($permissionsFilter, $validPermissionsFilter)
        || !hostsite_user_has_permission($params[$permissionsFilter . '_records_permission'])) {
      throw new Exception('Unauthorised');
    }
  }

  /**
   * A simple wrapper for the cUrl functionality to POST to Elastic.
   */
  private static function curlPost($url, $data, $params) {
    $allowedGetParams = ['filter_path'];
    $getParams = [];
    foreach ($allowedGetParams as $param) {
      if (!empty($_GET[$param])) {
        $getParams[$param] = $_GET[$param];
      }
    }
    if (count($getParams)) {
      $url .= '?' . http_build_query($getParams);
    }
    $session = curl_init($url);
    curl_setopt($session, CURLOPT_POST, 1);
    curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($session, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json',
      "Authorization: USER:$params[user]:SECRET:$params[secret]",
    ]);
    curl_setopt($session, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
    curl_setopt($session, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($session, CURLOPT_HEADER, FALSE);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
    // Do the POST and then close the session.
    $response = curl_exec($session);
    $headers = curl_getinfo($session);
    curl_close($session);
    if (array_key_exists('charset', $headers)) {
      $headers['content_type'] .= '; ' . $headers['charset'];
    }
    header('Content-type: ' . $headers['content_type']);
    echo $response;
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
