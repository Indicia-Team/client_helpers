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
 *
 */
class iform_dynamic_elasticsearch extends iform_dynamic {

  private static $esMappings;

  private static $controlIndex = 0;

  /**
   * Return the form metadata.
   */
  public static function get_dynamic_elasticsearch_definition() {
    return array(
      'title' => 'Elasticsearch outputs (customisable)',
      'category' => 'Experimental',
      'description' => 'Provides a dynamically output page which can generate controls for filtering, downloading, ' .
        'tabulating, charting and mapping Elasticsearch content.',
      'recommended' => TRUE,
    );
  }

  /**
   * Get the list of parameters for this form.
   *
   * @return array
   *   List of parameters that this form requires.
   */
  public static function get_parameters() {
    return [
      [
        'name' => 'interface',
        'caption' => 'Interface Style Option',
        'description' => 'Choose the style of user interface, either dividing the form up onto separate tabs, wizard pages or having all controls on a single page.',
        'type' => 'select',
        'options' => array(
          'tabs' => 'Tabs',
          'wizard' => 'Wizard',
          'one_page' => 'All One Page',
        ),
        'group' => 'User Interface',
        'default' => 'tabs',
      ],
      [
        'name' => 'structure',
        'caption' => 'Form Structure',
        'type' => 'textarea',
        'group' => 'User Interface',
        'default' => '',
      ],
      [
        'name' => 'endpoint',
        'caption' => 'Endpoint',
        'description' => 'ElasticSearch endpoint declared in the REST API.',
        'type' => 'text_input',
        'group' => 'ElasticSearch Settings',
      ],
      [
        'name' => 'user',
        'caption' => 'User',
        'description' => 'REST API user with ElasticSearch access.',
        'type' => 'text_input',
        'group' => 'ElasticSearch Settings',
      ],
      [
        'name' => 'secret',
        'caption' => 'Secret',
        'description' => 'REST API user secret.',
        'type' => 'text_input',
        'group' => 'ElasticSearch Settings',
      ],
      [
        'name' => 'warehouse_prefix',
        'caption' => 'Warehouse ID prefix',
        'description' => 'Prefix given to numeric IDs to make them unique on the index.',
        'type' => 'text_input',
        'group' => 'ElasticSearch Settings',
      ],
      [
        'name' => 'filter_json',
        'caption' => 'Filter JSON - Filter',
        'description' => 'JSON ',
        'type' => 'textarea',
        'required' => FALSE,
        'group' => 'Filter Settings',
        'default' => '',
      ],
      [
        'name' => 'must_json',
        'caption' => 'Filter JSON - Must',
        'description' => 'JSON ',
        'type' => 'textarea',
        'required' => FALSE,
        'group' => 'Filter Settings',
        'default' => '',
      ],
      [
        'name' => 'should_json',
        'caption' => 'Filter JSON - Should',
        'description' => 'JSON ',
        'type' => 'textarea',
        'required' => FALSE,
        'group' => 'Filter Settings',
        'default' => '',
      ],
      [
        'name' => 'must_not_json',
        'caption' => 'Filter JSON - Must not',
        'description' => 'JSON ',
        'type' => 'textarea',
        'required' => FALSE,
        'group' => 'Filter Settings',
        'default' => '',
      ],
    ];
  }

  /**
   * Override the get_form to fetch our own auth tokens. This skips the write auth as it is unnecessary,
   * which makes the tokens cachable therefore faster. It does mean that $auth['write'] will not be available.
   */
  public static function get_form($args, $nid) {
    $ajaxUrl = hostsite_get_url('iform/ajax/dynamic_elasticsearch');
    self::getMappings($nid);
    $mappings = json_encode(self::$esMappings);
    $url = iform_ajaxproxy_url($nid, 'single_verify');
    $userId = hostsite_get_user_field('indicia_user_id');
    data_entry_helper::$javascript .= <<<JS
indiciaData.ajaxUrl = '$ajaxUrl';
indiciaData.esSources = [];
indiciaData.esMappings = $mappings;
indiciaData.userId = $userId;
indiciaData.ajaxFormPostSingleVerify = "$url&user_id=$userId&sharing=verification";

JS;
    helper_base::add_resource('font_awesome');
    return parent::get_form($args, $nid);
  }

  private static function recurseMappings($data, &$mappings, $path = []) {
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
    curl_close($session);
    $mappingData = json_decode($response, TRUE);
    $mappingData = array_pop($mappingData);
    $mappings = [];
    self::recurseMappings($mappingData['mappings']['doc']['properties'], $mappings);
    self::$esMappings = $mappings;
  }

  private static function checkOptions($controlName, &$options, $requiredOptions, $jsonOptions) {
    self::$controlIndex++;
    $options = array_merge([
      'id' => "es-output-$controlName" . self::$controlIndex,
    ], $options);
    foreach ($requiredOptions as $option) {
      if (empty($options[$option]) && $options[$option] !== FALSE) {
        throw new Exception("Control [$controlName] requires a parameter called @$option");
      }
    }
    foreach ($jsonOptions as $option) {
      if (!empty($options[$option]) && !is_object($options[$option]) && !is_array($options[$option])) {
        throw new exception("@$option option for [$controlName] is not a valid JSON object.");
      }
    }
    // Source option can be either a single named source, or an array of key
    // value pairs where the key is the source name and the value is the title,
    // e.g. for a multi-layer map. So, standardise it to the array style.
    if (!empty($options['source'])) {
      $options['source'] = is_string($options['source']) ? [$options['source'] => 'Source data'] : $options['source'];
    }
  }

  protected static function get_control_source($auth, $args, $tabalias, $options) {
    if (empty($options['id'])) {
      throw new exception('A [source] requires an @id option.');
    }
    if (!empty($options['aggregation'])) {
      if (!is_object($options['aggregation']) && !is_array($options['aggregation'])) {
        throw new exception('@aggregation option for [source] is not a valid JSON object.');
      }
    }
    $dataOptions = self::getOptionsForJs($options, [
      'id',
      'paged',
      'from',
      'size',
      'aggregation',
      'initialMapBounds',
    ]);
    data_entry_helper::$javascript .= <<<JS
indiciaData.esSources.push($dataOptions);

JS;
    return '';
  }

  /**
   * Retrieves a single parameter control for the Elasticsearch query.
   *
   * Options:
   * * @filterJson - required.
   * * @boolType - must, must_not, should or filter. Default must.
   * * @label
   *
   * @return string
   *   Control HTML
   */
  protected static function get_control_param($auth, $args, $tabalias, $options) {
    $options = array_merge([
      'boolType' => 'must',
    ], $options);
    if (empty($options['filterJson'])) {
      throw new exception('@filterJson option must be set for [param]');
    }
    $ctrlOptions = [
      'fieldname' => '',
      'class' => 'param boolType-' . $options['boolType'],
    ];
    if (!empty($options['label'])) {
      $ctrlOptions['label'] = $options['label'];
    }
    return data_entry_helper::text_input($ctrlOptions);
  }

  protected static function get_control_userFilters($auth, $args, $tabalias, $options) {
    require_once 'includes/report_filters.php';
    self::$controlIndex++;
    $options = array_merge([
      'id' => "es-user-filter-" . self::$controlIndex,
      'definesPermissions' => FALSE,
    ], $options);
    $filterData = report_filters_load_existing($auth['read'], $options['sharingCode']);
    $optionArr = [];
    foreach ($filterData as $filter) {
      if (($filter['defines_permissions'] === 't') === $options['definesPermissions']) {
        $optionArr[$filter['id']] = $filter['title'];
      }
    }
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

  protected static function get_control_dataGrid($auth, $args, $tabalias, $options) {
    self::checkOptions('dataGrid', $options, ['source', 'columns'], ['columns']);
    helper_base::add_resource('fancybox');
    $dataOptions = self::getOptionsForJs($options, [
      'columns',
      'columnTitles',
      'filterRow',
      'pager',
      'sortable',
    ]);
    $encodedOptions = htmlspecialchars($dataOptions);
    // Escape the source so it can output as an attribute.
    $source = str_replace('"', '&quot;', json_encode($options['source']));
    return <<<HTML
<div id="$options[id]" class="es-output es-output-dataGrid" data-es-source="$source" data-es-output-config="$encodedOptions"></div>

HTML;
  }

  protected static function get_control_map($auth, $args, $tabalias, $options) {
    self::checkOptions('map', $options, ['source'], ['styles']);
    $options = array_merge([
      'styles' => new stdClass(),
    ], $options);
    helper_base::add_resource('leaflet');
    $dataOptions = self::getOptionsForJs($options, [
      'styles',
      'showSelectedRow',
      'applyBoundsTo',
      'initialLat',
      'initialLng',
      'initialZoom',
    ]);
    $encodedOptions = htmlspecialchars($dataOptions);
    // Escape the source so it can output as an attribute.
    $source = str_replace('"', '&quot;', json_encode($options['source']));

    return <<<HTML
<div id="$options[id]" class="es-output es-output-map" data-es-source="$source" data-es-output-config="$encodedOptions"></div>

HTML;
  }

  protected static function get_control_verificationButtons($auth, $args, $tabalias, $options) {
    self::checkOptions('recordDetails', $options, ['showSelectedRow'], []);
    $dataOptions = self::getOptionsForJs($options, [
      'showSelectedRow',
    ]);
    $encodedOptions = htmlspecialchars($dataOptions);
    helper_base::add_resource('fancybox');
    return <<<HTML
<div class="verification-buttons" style="display: none;" data-es-output-config="$encodedOptions">
Actions:
  <span class="fas fa-toggle-on toggle fa-2x" title="Toggle additional status levels"></span>
  <button class="verify l1" data-status="V" title="Accepted"><span class="far fa-check-circle status-V"></span></button>
  <button class="verify l2" data-status="V1" title="Accepted :: correct"><span class="fas fa-check-double status-V1"></span></button>
  <button class="verify l2" data-status="V2" title="Accepted :: considered correct"><span class="fas fa-check status-V2"></span></button>
  <button class="verify" data-status="C3" title="Plausible"><span class="fas fa-question status-C3"></span></button>
  <button class="verify l1" data-status="R" title="Not accepted"><span class="far fa-times-circle status-R"></span></button>
  <button class="verify l2" data-status="R4" title="Not accepted :: unable to verify"><span class="fas fa-times status-R4"></span></button>
  <button class="verify l2" data-status="R5" title="Not accepted :: incorrect"><span class="fas fa-times status-R5"></span></button>
</div>
HTML;
  }

  protected static function get_control_recordDetails($auth, $args, $tabalias, $options) {
    self::checkOptions('recordDetails', $options, ['showSelectedRow'], []);
    $dataOptions = self::getOptionsForJs($options, [
      'showSelectedRow',
    ]);
    $encodedOptions = htmlspecialchars($dataOptions);
    helper_base::add_resource('tabs');
    return <<<HTML
<div class="details-container" data-es-output-config="$encodedOptions">
  <div class="empty-message alert alert-info">Select a row to view details</div>
  <div class="tabs" style="display: none">
    <ul>
      <li><a href="#tabs-details">Details</a></li>
      <li><a href="#tabs-comments">Comments</a></li>
    </ul>
    <div id="tabs-details">
      <div class="record-details">
      </div>
    </div>
    <div id="tabs-comments">
      <div class="comments">
      </div>
    </div>
  </div>
</div>

HTML;
  }

  private static function applyUserFilters($readAuth, $query, &$bool) {
    foreach ($query['user_filters'] as $userFilter) {
      $filterData = data_entry_helper::get_population_data([
        'table' => 'filter',
        'extraParams' => [
          'id' => $userFilter,
        ] + $readAuth,
      ]);
      $definition = json_decode($filterData[0]['definition'], TRUE);
      if (!empty($definition['taxon_group_list'])) {
        self::applyUserFiltersTaxonGroupList($readAuth, $definition['taxon_group_list'], $bool);
      }
      if (!empty($definition['higher_taxa_taxon_list_list'])) {
        self::applyUserFiltersTaxaTaxonList($readAuth, $definition['higher_taxa_taxon_list_list'], $bool);
      }
      if (!empty($definition['taxa_taxon_list_list'])) {
        self::applyUserFiltersTaxaTaxonList($readAuth, $definition['taxa_taxon_list_list'], $bool);
      }
      if (!empty($definition['indexed_location_list'])) {
        self::applyUserFiltersIndexedLocationList($readAuth, $definition['indexed_location_list'], $bool);
      }
    }
  }

  private static function applyUserFiltersTaxonGroupList($readAuth, $data, &$bool) {
    $groupData = data_entry_helper::get_population_data([
      'table' => 'taxon_group',
      'extraParams' => [
        'query' => json_encode(['in' => ['id' => explode(',', $data)]]),
      ] + $readAuth,
    ]);
    $groups = [];
    foreach ($groupData as $group) {
      $groups[] = $group['title'];
    }
    $bool['must'][] = ['terms' => ['taxon.group.keyword' => $groups]];
  }

  private static function applyUserFiltersTaxaTaxonList($readAuth, $data, &$bool) {
    $taxonData = data_entry_helper::get_population_data([
      'table' => 'taxa_taxon_list',
      'extraParams' => [
        'view' => 'cache',
        'query' => json_encode(['in' => ['id' => explode(',', $data)]]),
      ] + $readAuth,
    ]);
    $keys = [];
    foreach ($taxonData as $taxon) {
      $keys[] = $taxon['external_key'];
    }
    $bool['must'][] = ['terms' => ['taxon.higher_taxon_ids' => $keys]];
  }

  private static function applyUserFiltersIndexedLocationList($readAuth, $data, &$bool) {
    $bool['must'][] = [
      'nested' => [
        'path' => 'location.higher_geography',
        'query' => [
          'terms' => ['location.higher_geography.id' => explode(',', $data)],
        ],
      ],
    ];
  }

  /**
   * Ajax method which echoes custom attribute data to the client.
   *
   * At the moment, this info is built from the Indicia warehouse, not
   * ElasticSearch.
   *
   * @param int $website_id
   *   Warehouse website ID.
   * @param string $password
   *   Warehouse password.
   */
  public static function ajax_attrs($website_id, $password) {
    $readAuth = report_helper::get_read_auth($website_id, $password);
    $options = array(
      'dataSource' => 'reports_for_prebuilt_forms/verification_3/record_data_attributes',
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

  public static function ajax_esproxy($website_id, $password, $nid) {
    $params = hostsite_get_node_field_value($nid, 'params');
    $url = $_POST['warehouse_url'] . 'index.php/services/rest/' . $params['endpoint'] . '/_search';
    $query = array_merge($_POST);
    unset($query['warehouse_url']);
    $bool = [
      'must' => [],
      'should' => [],
      'must_not' => [],
      'filter' => [],
    ];
    if (isset($query['filters'])) {
      // Apply any filter row paramenters to the query.
      foreach ($query['filters'] as $field => $value) {
        $bool['must'][] = ['match' => [$field => $value]];
      }
      unset($query['filters']);
    }
    foreach ($query['bool_queries'] as $qryConfig) {
      if ($qryConfig['query_type'] === 'query_string') {
        $bool[$qryConfig['bool_clause']][] = ['query_string' => ['query' => $qryConfig['value']]];
      }

    }
    unset($query['bool_queries']);
    $readAuth = data_entry_helper::get_read_auth($website_id, $password);
    if (!empty($query['user_filters'])) {
      $readAuth = data_entry_helper::get_read_auth($website_id, $password);
      self::applyUserFilters($readAuth, $query, $bool);
    }
    unset($query['user_filters']);
    $bool = array_filter($bool, function ($k) {
      return count($k) > 0;
    });
    if (!empty($bool)) {
      $query['query'] = ['bool' => $bool];
    }
    self::curlPost($url, $query, $params);
  }

  public static function ajax_esproxyupdate($website_id, $password, $nid) {
    $params = hostsite_get_node_field_value($nid, 'params');
    $occurrenceId = $_POST['id'];
    watchdog('debug', json_encode($params));
    $url = $_POST['warehouse_url'] . 'index.php/services/rest/' . $params['endpoint'] . "/doc/$params[warehouse_prefix]$occurrenceId/_update";
    $doc = ['doc' => array_merge($_POST['doc'])];
    self::curlPost($url, $doc, $params);
  }

  private static function curlPost($url, $data, $params) {
    $session = curl_init($url);
    curl_setopt($session, CURLOPT_POST, 1);
    curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($data));
    watchdog('es_post', json_encode($data));
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

  private static function getOptionsForJs($options, $keysToPassThrough) {
    $dataOptions = array_intersect_key($options, array_combine($keysToPassThrough, $keysToPassThrough));
    return json_encode($dataOptions);
  }

}
