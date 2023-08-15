<?php

/**
 * @file
 * A helper class for Elasticsearch proxy code.
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
 * Exception class for request abort.
 */
class ElasticsearchProxyAbort extends Exception {
}

/**
 * Helper class with library functions to support Elasticsearch proxying.
 */
class ElasticsearchProxyHelper {

  /**
   * Elasticsearch config.
   *
   * @var bool
   */
  private static $config;

  /**
   * Track if filter applied specifies confidential flag.
   *
   * If not specified, then code can apply a default confidential=f filter.
   *
   * @var bool
   */
  private static $confidentialFilterApplied = FALSE;

  /**
   * Track if filter applied specifies releast_status flag.
   *
   * If not specified, then code can apply a default releast_status=R filter.
   *
   * @var bool
   */
  private static $releaseStatusFilterApplied = FALSE;

  /**
   * If a filter is selected in a permissionFilters control, apply scope.
   *
   * E.g. if a verification filter selected, apply the verification scope.
   *
   * @var int
   *   Filter ID.
   */
  private static $setScopeUsingFilter;

  /**
   * Route into the functions provided by the proxy.
   *
   * @param string $method
   *   Method name.
   * @param int $nid
   *   Node ID.
   */
  public static function callMethod($method, $nid) {
    self::$config = hostsite_get_es_config($nid);
    if (empty(self::$config['es']['endpoint']) ||
        (self::$config['es']['auth_method'] === 'directClient' && (empty(self::$config['es']['user']) || empty(self::$config['es']['secret'])))) {
      header("HTTP/1.1 405 Method not allowed");
      echo json_encode(['error' => 'Method not allowed as server configuration incomplete']);
      throw new ElasticsearchProxyAbort('Configuration incomplete');
    }

    switch ($method) {
      case 'attrs':
        self::proxyAttrDetails($nid);
        break;

      case 'comments':
        self::proxyComments($nid);
        break;

      case 'doesUserSeeNotifications':
        self::proxyDoesUserSeeNotifications($nid);
        break;

      case 'download':
        self::proxyDownload($nid);
        break;

      case 'mediaAndComments':
        self::proxyMediaAndComments($nid);
        break;

      case 'rawsearch':
        self::proxyRawsearch();
        break;

      case 'searchbyparams':
        self::proxySearchByParams($nid);
        break;

      case 'verifyall':
        self::proxyVerifyAll($nid);
        break;

      case 'verifyspreadsheet':
        self::proxyVerifySpreadsheet();
        break;

      case 'verifyids':
        self::proxyVerifyIds();
        break;

      case 'verificationQueryEmail':
        self::proxyVerificationQueryEmail();
        break;

      case 'redetall':
        self::proxyRedetAll($nid);
        break;

      case 'redetids':
        self::proxyRedetIds();
        break;

      case 'bulkmoveall':
        self::proxyBulkMoveAll($nid);
        break;

      case 'bulkmoveids':
        self::proxyBulkMoveIds($nid);
        break;

      case 'clearcustomresults':
        self::proxyClearCustomResults($nid);
        break;

      case 'runcustomruleset':
        self::proxyRunCustomRuleset($nid);
        break;

      default:
        header("HTTP/1.1 404 Not found");
        echo json_encode(['error' => 'Method not found']);
    }
  }

  /**
   * Retrieve the Elasticsearch endpoint to use.
   *
   * @return string
   *   The endpoint name (e.g. es-occurrences).
   */
  private static function getEsEndpoint() {
    // Request can modify the endpoint, but only if on a list of allowed
    // endpoints.
    if (!empty($_GET['endpoint']) && !empty(self::$config['es']['alternative_endpoints'])
        && in_array($_GET['endpoint'], helper_base::explode_lines(self::$config['es']['alternative_endpoints']))) {
      return $_GET['endpoint'];
    }
    else {
      return self::$config['es']['endpoint'];
    }
  }

  /**
   * Returns the URL required to call the Elasticsearch service.
   *
   * @return string
   *   URL.
   */
  private static function getEsUrl() {
    $endpoint = self::getEsEndpoint();
    return self::$config['indicia']['base_url'] . "index.php/services/rest/$endpoint";
  }

  /**
   * Ajax method which echoes custom attribute data to the client.
   *
   * At the moment, this info is built from the Indicia warehouse, not
   * Elasticsearch.
   *
   * @param int $nid
   *   Node ID to obtain connection info from.
   */
  private static function proxyAttrDetails($nid) {
    iform_load_helpers(['report_helper']);
    $conn = iform_get_connection_details($nid);
    $readAuth = helper_base::get_read_auth($conn['website_id'], $conn['password']);
    $options = [
      'dataSource' => 'reports_for_prebuilt_forms/dynamic_elasticsearch/record_details',
      'readAuth' => $readAuth,
      // @todo Sharing should be dynamically set in a form parameter (use $nid param).
      'sharing' => 'verification',
      'extraParams' => ['occurrence_id' => $_GET['occurrence_id']],
    ];
    $reportData = report_helper::get_report_data($options);
    // Convert the output to a structured JSON object, including fresh read
    // auth tokens.
    $data = ['auth' => $readAuth];
    // Organise some attributes by system function, so we can make output
    // consistent.
    $sysFuncAttrs = [];
    $sysFuncList = [
      'Additional occurrence' => [
        'behaviour',
        'certainty',
        'reproductive_condition',
        'sex_stage_count',
        'sex',
        'stage',
        'sex_stage',
      ],
      'Additional sample' => ['biotope'],
    ];
    foreach ($reportData as $key => $attribute) {
      if (isset($sysFuncList[$attribute['attribute_type']])
          && in_array($attribute['system_function'], $sysFuncList[$attribute['attribute_type']])) {
        $sysFuncAttrs[$attribute['system_function']] = $attribute;
        unset($reportData[$key]);
      }
    }
    // Now build the special system function output first.
    foreach ($sysFuncList as $heading => $sysFuncs) {
      $headingData = [];
      foreach ($sysFuncs as $sysFunc) {
        if (isset($sysFuncAttrs[$sysFunc])) {
          $headingData[] = [
            'caption' => $sysFuncAttrs[$sysFunc]['caption'],
            'value' => $sysFuncAttrs[$sysFunc]['value'],
            'system_function' => $sysFuncAttrs[$sysFunc]['system_function'],
          ];
        }
      }
      if (!empty($headingData)) {
        $data["$heading attributes"] = $headingData;
      }
    }
    // Now the rest.
    foreach ($reportData as $attribute) {
      if (!empty($attribute['value'])) {
        if (!isset($data[$attribute['attribute_type'] . ' attributes'])) {
          $data[$attribute['attribute_type'] . ' attributes'] = [];
        }
        $data[$attribute['attribute_type'] . ' attributes'][] = [
          'caption' => $attribute['caption'],
          'value' => $attribute['value'],
          'system_function' => $attribute['system_function'],
        ];
      }
    }
    header('Content-type: application/json');
    echo json_encode($data);
  }

  /**
   * Provides information on a user's ability to see notifications.
   *
   * Used when querying records for verification.
   */
  private static function proxyDoesUserSeeNotifications($nid) {
    iform_load_helpers(['VerificationHelper']);
    $conn = iform_get_connection_details($nid);
    $readAuth = helper_base::get_read_auth($conn['website_id'], $conn['password']);
    header('Content-type: application/json');
    echo json_encode(['msg' => VerificationHelper::doesUserSeeNotifications($readAuth, $_GET['user_id'])]);
  }

  /**
   * Ajax handler for the [recordDetails] comments tab.
   *
   * @param int $nid
   *   Node ID to obtain connection info from.
   *
   * @todo Consider switch to using VerificationHelper::getComments().
   */
  private static function proxyComments($nid) {
    iform_load_helpers(['report_helper']);
    $conn = iform_get_connection_details($nid);
    $readAuth = helper_base::get_read_auth($conn['website_id'], $conn['password']);
    $options = [
      'dataSource' => 'reports_for_prebuilt_forms/verification_5/occurrence_comments_and_dets',
      'readAuth' => $readAuth,
      // @todo Sharing should be dynamically set in a form parameter (use $nid param).
      'sharing' => 'verification',
      'extraParams' => [
        'occurrence_id' => $_GET['occurrence_id'],
      ],
    ];
    $reportData = report_helper::get_report_data($options);
    header('Content-type: application/json');
    echo json_encode($reportData);
  }

  private static function proxySearchByParams($nid) {
    iform_load_helpers(['helper_base']);
    $conn = iform_get_connection_details($nid);
    $readAuth = helper_base::get_read_auth($conn['website_id'], $conn['password']);
    self::checkPermissionsFilter($_POST, $readAuth, $nid);
    $url = self::getEsUrl() . '/_search';
    $query = self::buildEsQueryFromRequest($_POST);
    echo self::curlPost($url, $query);
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
  private static function proxyRawsearch() {
    $url = self::getEsUrl() . '/_search';
    $query = array_merge($_POST);
    $query['size'] = 0;
    echo self::curlPost($url, $query);
  }

  /**
   * A search proxy that handles build of a CSV download file.
   */
  private static function proxyDownload($nid) {
    $isScrollToNextPage = array_key_exists('scroll_id', $_GET);
    if (!$isScrollToNextPage) {
      iform_load_helpers(['helper_base']);
      $conn = iform_get_connection_details($nid);
      $readAuth = helper_base::get_read_auth($conn['website_id'], $conn['password']);
      self::checkPermissionsFilter($_POST, $readAuth, $nid);
    }
    $url = self::getEsUrl() . '/_search?' . self::getPassThroughUrlParams(['format' => 'csv'], [
      'aggregation_type',
      'uniq_id',
      'state',
    ]);
    if (isset($_GET['aggregation_type']) && isset($_GET['uniq_id']) && isset($_GET['state'])) {
      // Pass through parameters for aggregation download file chunking.
      $url .= "&aggregation_type=$_GET[aggregation_type]";
      $url .= "&uniq_id=$_GET[uniq_id]";
      $url .= "&state=$_GET[state]";
    }
    else {
      // Download will use Elasticsearch scroll if doing documents.
      $url .= '&scroll';
      if ($isScrollToNextPage) {
        $url .= '&scroll_id=' . $_GET['scroll_id'];
      }
    }
    $query = self::buildEsQueryFromRequest($_POST);
    echo self::curlPost($url, $query);
  }

  /**
   * Proxy method to retrieve media and comments for emails.
   *
   * When an email is sent to query a record, the comments and media are
   * injected into the HTML. Echoes an array with a media entry and a comments
   * entry, both containing the required HTML.
   */
  private static function proxyMediaAndComments($nid) {
    iform_load_helpers(['VerificationHelper']);
    $conn = iform_get_connection_details($nid);
    $readAuth = helper_base::get_read_auth($conn['website_id'], $conn['password']);
    $params = array_merge(['sharing' => 'verification'], hostsite_get_node_field_value($nid, 'params'));
    header('Content-type: application/json');
    echo json_encode([
      'media' => VerificationHelper::getMedia($readAuth, $params, $_GET['occurrence_id'], $_GET['sample_id']),
      'comments' => VerificationHelper::getComments($readAuth, $params, $_GET['occurrence_id'], TRUE),
    ]);
  }

  /**
   * Proxy method that receives a list of IDs to verify in Elastic.
   *
   * Used by the verification system when in checklist mode to allow setting a
   * comment and status on multiple records in one go.
   */
  private static function proxyVerifyIds() {
    if (empty(self::$config['es']['warehouse_prefix'])) {
      header("HTTP/1.1 405 Method not allowed");
      echo json_encode(['error' => 'Method not allowed as server configuration incomplete']);
      throw new ElasticsearchProxyAbort('Configuration incomplete');
    }
    $statuses = $_POST['doc']['identification'] ?? [];
    echo self::internalModifyListOnES($_POST['ids'], $statuses,
      isset($_POST['doc']['metadata']['website']['id']) ? $_POST['doc']['metadata']['website']['id'] : NULL);
  }

  /**
   * Retrieve the list of occurrence IDs for an ES filter.
   *
   * @return array
   *   List of occurrence IDs.
   */
  private static function getOccurrenceIdsFromFilter($nid, $filter) {
    iform_load_helpers(['helper_base']);
    $conn = iform_get_connection_details($nid);
    $readAuth = helper_base::get_read_auth($conn['website_id'], $conn['password']);
    self::checkPermissionsFilter($filter, $readAuth, $nid);
    $url = self::getEsUrl() . '/_search';
    $query = self::buildEsQueryFromRequest($filter);
    // Limit response for efficiency.
    $_GET['filter_path'] = 'hits.hits._source.id';
    // Maximum 10000.
    $query['size'] = 10000;
    $r = self::curlPost($url, $query);
    $esResponse = json_decode($r);
    unset($_GET['filter_path']);
    $ids = [];
    foreach ($esResponse->hits->hits as $item) {
      $ids[] = $item->_source->id;
    }
    return $ids;
  }

  /**
   * Retrieve a page of occurrence IDs for an ES filter.
   *
   * Similar to getOccurrenceIdsFromFilter but retrieves a smaller batch of
   * record IDs, with search_after information that can be used in the next
   * request in order to paginate through the data. Ensures each batch of
   * records is divided on a sample ID (event.event_id) boundary so that
   * checks for complete samples don't fail.
   *
   * @param int $nid
   *   Node ID.
   * @param array $filter
   *   Filted definition.
   * @param array $searchAfter
   *   Values to pass to the search_after option on ES, in order to request the
   *   next page of data, or NULL for the first page.
   *
   * @return array
   *   Associative array containing a search_after property to be used in the
   *   next request and and ids property containing a list of occurrence IDs.
   */
  private static function getOccurrenceIdPageFromFilter($nid, $filter, $searchAfter) {
    iform_load_helpers(['helper_base']);
    $conn = iform_get_connection_details($nid);
    $readAuth = helper_base::get_read_auth($conn['website_id'], $conn['password']);
    self::checkPermissionsFilter($filter, $readAuth, $nid);
    $url = self::getEsUrl() . '/_search';
    $query = self::buildEsQueryFromRequest($filter);
    $query['sort'] = [
      ['event.event_id' => 'asc'],
      ['id' => 'asc'],
    ];
    if ($searchAfter) {
      $query['search_after'] = $searchAfter;
    }
    // Limit response for efficiency.
    $_GET['filter_path'] = 'hits.hits._source.id,hits.hits._source.event.event_id';
    // Maximum 1000 - should be more than the max size of a sample.
    $query['size'] = 1000;
    $r = self::curlPost($url, $query);
    $esResponse = json_decode($r);
    unset($_GET['filter_path']);
    if (empty($esResponse->hits) || empty($esResponse->hits->hits)) {
      return ['ids' => []];
    }
    if (count($esResponse->hits->hits) >= $query['size']) {
      // Find the last event_id as we need to skip records for this sample, in
      // case there is a paging split within the sample.
      $lastEventId = $esResponse->hits->hits[count($esResponse->hits->hits) - 1]->_source->event->event_id;
    }
    else {
      // Set dummy value to disable event ID filter, as all remaining records
      // have been found.
      $lastEventId = -1;
    }
    $ids = [];
    $searchAfter = [];
    foreach ($esResponse->hits->hits as $item) {
      if ($item->_source->event->event_id !== $lastEventId) {
        $ids[] = $item->_source->id;
        $searchAfter = [$item->_source->event->event_id, $item->_source->id];
      }
    }
    return [
      'ids' => $ids,
      'search_after' => $searchAfter,
    ];
  }

  /**
   * Apply verification or redet action to all records in the current filter.
   *
   * @param int $nid
   *   Node ID.
   * @param array $statuses
   *   Record status data to update.
   * @param int $websiteIdToModify
   *   Set to 0 if clearing the website ID as a temporary measure to disable
   *   records after redetermination, until Logstash fills in the taxonomy
   *   again.
   */
  private static function processWholeEsFilter($nid, array $statuses, $websiteIdToModify = NULL) {
    if (empty(self::$config['es']['warehouse_prefix'])) {
      header("HTTP/1.1 405 Method not allowed");
      echo json_encode(['error' => 'Method not allowed as server configuration incomplete']);
      throw new ElasticsearchProxyAbort('Configuration incomplete');
    }
    if (empty($_POST['website_id'])) {
      header("HTTP/1.1 400 Missing parameter");
      echo json_encode(['error' => 'Missing website_id parameter']);
      throw new ElasticsearchProxyAbort('Parameter missing');
    }
    $ids = self::getOccurrenceIdsFromFilter($nid, $_POST['occurrence:idsFromElasticFilter']);

    self::internalModifyListOnES($ids, $statuses, $websiteIdToModify);
    try {
      self::updateWarehouseVerificationAction($ids, $nid);
    }
    catch (Exception $e) {
      header("HTTP/1.1 500 Internal server error");
      echo json_encode(['error' => 'Error whilst updating warehouse records: ' . $e->getMessage()]);
      throw new ElasticsearchProxyAbort('Internal server error');
    }
    echo json_encode([
      'updated' => count($ids),
    ]);
  }

  /**
   * Proxy method to apply verification change to entire results set.
   *
   * Uses a filter definition passed in the post to retrieve the records from
   * ES then applies the decision to all aof them.
   */
  private static function proxyVerifyAll($nid) {
    $statuses = [
      'verification_status' => $_POST['occurrence:record_status'],
      'verification_substatus' => empty($_POST['occurrence:record_substatus']) ? 0 : $_POST['occurrence:record_substatus'],
    ];
    self::processWholeEsFilter($nid, $statuses);
  }

  /**
   * Applies an uploaded spreadsheet containing verification decisions.
   *
   * Forwards the spreadsheet to the /occurrences/verify-spreadsheet end-point,
   * applying the decisons to the records on the warehouse. The client JS code
   * should call this multiple times, the first time with the file in a POSTed
   * field called decisions, then subsequently send back the fileId (returned
   * in the response metadata). This will process the file in chunks, which
   * should continue until the response contains state=done.
   */
  private static function proxyVerifySpreadsheet() {
    $url = self::$config['indicia']['base_url'] . 'index.php/services/rest/occurrences/verify-spreadsheet';

    if (isset($_FILES['decisions'])) {
      // Initial file upload.
      $file = $_FILES['decisions'];
      $payload = [
        'decisions' => curl_file_create($file['tmp_name'], $file['type'], $file['name']),
        'filter_id' => $_POST['filter_id'],
        'user_id' => hostsite_get_user_field('indicia_user_id'),
        'es_endpoint' => $_POST['es_endpoint'],
        'id_prefix' => $_POST['id_prefix'],
        'warehouse_name' => $_POST['warehouse_name'],
      ];
    }
    else {
      if (!empty($_POST['fileId'])) {
        // Subsequent processing request.
        $payload = [
          'fileId' => $_POST['fileId'],
        ];
      }
    }
    if (empty($payload)) {
      header("HTTP/1.1 400 Bad request");
      echo json_encode(['error' => 'Missing decisions file or fileId parameter']);
      throw new ElasticsearchProxyAbort('Missing decisions file or fileId parameter');
    }
    echo self::curlPost($url, $payload, [], TRUE);
  }

  /**
   * Proxy method that receives a list of IDs to redet in Elastic.
   *
   * Used by the verification system when in checklist mode to allow setting a
   * redetermination on multiple records in one go.
   */
  private static function proxyRedetIds() {
    if (empty(self::$config['es']['warehouse_prefix'])) {
      header("HTTP/1.1 405 Method not allowed");
      echo json_encode(['error' => 'Method not allowed as server configuration incomplete']);
      throw new ElasticsearchProxyAbort('Configuration incomplete');
    }
    // Set website ID to 0, basically disabling the ES copy of the record until
    // a proper update with correct taxonomy information comes through.
    echo self::internalModifyListOnES($_POST['ids'], [], 0);
  }

  /**
   * Proxy method that redetermines all records in the current filter.
   */
  private static function proxyRedetAll($nid) {
    // Set website ID to 0, basically disabling the ES copy of the record until
    // a proper update with correct taxonomy information comes through.
    self::processWholeEsFilter($nid, [], 0);
  }

  /**
   * Proxy method to send an email querying a record.
   */
  private static function proxyVerificationQueryEmail() {
    $headers = [
      'MIME-Version: 1.0',
      'Content-type: text/html; charset=UTF-8;',
      'From: ' . hostsite_get_config_value('site', 'mail', ''),
      'Reply-To: ' . hostsite_get_user_field('mail'),
    ];
    $headers = implode("\r\n", $headers) . PHP_EOL;
    $emailBody = $_POST['body'];
    // Send email. Depends upon settings in php.ini being correct.
    $success = mail($_POST['to'],
         $_POST['subject'],
         wordwrap($emailBody, 70),
         $headers);
    header('Content-type: application/json');
    echo json_encode([
      'status' => $success ? 'OK' : 'Fail',
    ]);
  }

  /**
   * Creates a query string for the URL to pass through.
   *
   * Only passes through parameters in $_GET where the key matches one of the
   * provided.
   *
   * @param array $default
   *   List of paramaters that should appear in the query string no matter
   *   what.
   * @param array $params
   *   Names of $_GET keys that will be copied into the resulting query string
   *   if provided.
   */
  private static function getPassThroughUrlParams(array $default, array $params) {
    $query = $default;
    foreach ($params as $param) {
      if (!empty($_GET[$param])) {
        $query[$param] = $_GET[$param];
      }
    }
    return http_build_query($query);
  }

  /**
   * Apply verification result to a list of occurrences on ES.
   *
   * Used by both update for multi-select checkboxes and the entire data table.
   *
   * @param array $ids
   *   List of occurrence IDs.
   * @param array $statuses
   *   Status data to apply (verification_status, verification_substatus,
   *   query).
   * @param int $websiteIdToModify
   *   If changing the website ID (i.e. setting to 0 to temporarily hide the
   *   record), set it here.
   *
   * @return string
   *   Result of the POST to ES.
   */
  private static function internalModifyListOnES(array $ids, array $statuses, $websiteIdToModify) {
    $url = self::getEsUrl() . "/_update_by_query";
    $scripts = [];
    if (!empty($statuses['verification_status'])) {
      $scripts[] = "ctx._source.identification.verification_status = '" . $statuses['verification_status'] . "'";
    }
    if (!empty($statuses['verification_substatus'])) {
      $scripts[] = "ctx._source.identification.verification_substatus = '" . $statuses['verification_substatus'] . "'";
    }
    if (!empty($statuses['query'])) {
      $scripts[] = "ctx._source.identification.query = '" . $statuses['query'] . "'";
    }
    if ($websiteIdToModify !== NULL) {
      $scripts[] = "ctx._source.metadata.website.id = '" . $websiteIdToModify . "'";
    }
    if (empty($scripts)) {
      throw new exception('Unsupported field for update. ' . var_export($_POST['doc'], TRUE));
    }
    $_ids = [];
    // Convert Indicia IDs to the document _ids for ES. Also make a 2nd version
    // for full precision copies of sensitive records.
    foreach ($ids as $id) {
      $_ids[] = self::$config['es']['warehouse_prefix'] . $id;
      $_ids[] = self::$config['es']['warehouse_prefix'] . "$id!";
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
    // Update index and overwrite update conflicts.
    $r = self::curlPost($url, $doc, [
      'conflicts' => 'proceed',
    ]);
    $rObj = json_decode($r);
    // Since the verification alias can only see 1 copy of each record (e.g.
    // full precision), the total in the response will correspond to the number
    // of occurrences updated.
    return json_encode(['updated' => $rObj->updated]);
  }

  /**
   * When updating an entire ES filter result, also update the warehouse.
   *
   * Since we want to be sure that the warehouse update changes exactly the
   * same set of records as in the current ES filter, the proxy takes
   * responsibility for the warehouse update as well.
   *
   * @param array $ids
   *   List of occurrence IDs.
   * @param int $nid
   *   Node ID for authentication.
   */
  private static function updateWarehouseVerificationAction(array $ids, $nid) {
    $data = [
      'website_id' => $_POST['website_id'],
      'user_id' => $_POST['user_id'],
      'occurrence:ids' => implode(',', $ids),
    ];
    if (!empty($_POST['occurrence_comment:comment'])) {
      $data['occurrence_comment:comment'] = $_POST['occurrence_comment:comment'];
    }
    if (!empty($_POST['occurrence:record_status'])) {
      $data['occurrence:record_decision_source'] = 'H';
      $data['occurrence:record_status'] = $_POST['occurrence:record_status'];
    }
    if (!empty($_POST['occurrence:record_substatus'])) {
      $data['occurrence:record_substatus'] = $_POST['occurrence:record_substatus'];
    }
    if (!empty($_POST['occurrence:taxa_taxon_list_id'])) {
      $data['occurrence:taxa_taxon_list_id'] = $_POST['occurrence:taxa_taxon_list_id'];
      // Switch endpoint if redetermining.
      $action = 'list_redet';
    }
    else {
      $action = 'list_verify';
    }
    $conn = iform_get_connection_details($nid);
    $auth = helper_base::get_read_write_auth($conn['website_id'], $conn['password']);
    $request = helper_base::$base_url . "index.php/services/data_utils/$action";
    $postargs = helper_base::array_to_query_string(array_merge($data, $auth['write_tokens']), TRUE);
    $response = helper_base::http_post($request, $postargs);
    if ($response['output'] !== 'OK') {
      throw new exception($response['output']);
    }
  }

  /**
   * Retrieves the required HTTP headers for an Elasticsearch request.
   *
   * Header sets content type to application/json and adds an Authorization
   * header appropriate to the method.
   *
   * @param array $config
   *   Elasticsearch configuration.
   * @param string $contentType
   *   Content type, defaults to application/json.
   *
   * @return array
   *   Header strings.
   */
  public static function getHttpRequestHeaders($config, $contentType = 'application/json') {
    $headers = [
      "Content-Type: $contentType",
    ];
    if (empty($config['es']['auth_method']) || $config['es']['auth_method'] === 'directClient') {
      $headers[] = 'Authorization: USER:' . $config['es']['user'] . ':SECRET:' . $config['es']['secret'];
    }
    elseif ($config['es']['auth_method'] === 'directWebsite') {
      iform_load_helpers(['helper_base']);
      $conn = iform_get_connection_details();
      $tokens = [
        'WEBSITE_ID',
        $conn['website_id'],
        'SECRET',
        $conn['password'],
      ];
      if (isset($config['es']['scope'])) {
        $tokens[] = 'SCOPE';
        $tokens[] = $config['es']['scope'];
        $userId = hostsite_get_user_field('indicia_user_id');
        if ($userId) {
          $tokens[] = 'USER_ID';
          $tokens[] = $userId;
        }
      }
      $headers[] = 'Authorization: ' . implode(':', $tokens);
    }
    else {
      $keyFile = \Drupal::service('file_system')->realpath("private://") . '/rsa_private.pem';
      if (!file_exists($keyFile)) {
        \Drupal::logger('iform')->error('Missing private key file for jwtUser Elasticsearch authentication.');
        echo json_encode(['error' => 'Method not allowed as server configuration incomplete']);
        throw new ElasticsearchProxyAbort('Configuration incomplete');
      }
      $privateKey = file_get_contents($keyFile);
      $payload = [
        'iss' => hostsite_get_url('<front>', [], FALSE, TRUE),
        'http://indicia.org.uk/user:id' => hostsite_get_user_field('indicia_user_id'),
        'scope' => $config['es']['scope'],
        'exp' => time() + 300,
      ];
      $modulePath = \Drupal::service('module_handler')->getModule('iform')->getPath();
      // @todo persist the token in the cache?
      require_once "$modulePath/lib/php-jwt/vendor/autoload.php";
      $token = \Firebase\JWT\JWT::encode($payload, $privateKey, 'RS256');
      $headers[] = "Authorization: Bearer $token";
    }
    return $headers;
  }

  /**
   * A simple wrapper for the cUrl functionality to POST to Elastic.
   */
  private static function curlPost($url, $data, $getParams = [], $multipart = FALSE) {
    $curlResponse = FALSE;
    $cacheTimeout = FALSE;
    if (!empty($data['proxyCacheTimeout'])) {
      $cacheKey = [
        'post' => json_encode($data),
        'get' => json_encode($getParams),
      ];
      $curlResponse = helper_base::cache_get($cacheKey);
      if ($curlResponse) {
        $curlResponse = json_decode($curlResponse, TRUE);
      }
      else {
        $cacheTimeout = $data['proxyCacheTimeout'];
      }
      unset($data['proxyCacheTimeout']);
    }
    if (!$curlResponse) {
      $allowedGetParams = ['filter_path'];
      // Additional GET params should only be used if valid for ES.
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
      curl_setopt($session, CURLOPT_POSTFIELDS, $multipart ? $data : json_encode($data));
      curl_setopt($session, CURLOPT_HTTPHEADER, self::getHttpRequestHeaders(self::$config, $multipart ? 'multipart/form-data' : 'application/json'));
      curl_setopt($session, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);
      curl_setopt($session, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($session, CURLOPT_HEADER, FALSE);
      curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
      // Do the POST and then close the session.
      $response = curl_exec($session);
      $curlResponse = [
        'output' => $response,
        'headers' => curl_getinfo($session),
        'httpCode' => curl_getinfo($session, CURLINFO_HTTP_CODE),
      ];
      curl_close($session);
    }
    // Check for an error, or check if the http response was not OK.
    if ($curlResponse['httpCode'] != 200) {
      http_response_code($curlResponse['httpCode']);
    }
    elseif ($cacheTimeout) {
      helper_base::array_to_query_string($cacheKey);
      helper_base::cache_set($cacheKey, json_encode($curlResponse), $cacheTimeout);
    }
    if (array_key_exists('charset', $curlResponse['headers'])) {
      $curlResponse['headers']['content_type'] .= '; ' . $curlResponse['headers']['charset'];
    }
    header('Content-type: ' . $curlResponse['headers']['content_type']);
    return $curlResponse['output'];
  }

  /**
   * Confirm that a permissions filter in the request is allowed for the user.
   *
   * For example, permissions may be different for accessing my vs all records
   * so this method checks against the Drupal permissions defined on the edit
   * tab, ensuring calls to the proxy can't be easily hacked.
   *
   * @param array $post
   *   Section of the $_POST data which holds the filter info.
   * @param array $readAuth
   *   Read authentication tokens.
   * @param int $nid
   *   Node ID to load configuration from.
   */
  private static function checkPermissionsFilter(array $post, array $readAuth, $nid) {
    $permissionsFilter = empty($post['permissions_filter']) ? 'p-all' : $post['permissions_filter'];
    $roleBasedPermissionsFilters = [
      'p-all',
      'p-my',
      'p-location_collation',
    ];
    $permissionName = substr($permissionsFilter, 2) . '_records_permission';
    if (in_array($permissionsFilter, $roleBasedPermissionsFilters)) {
      if (!hostsite_user_has_permission(self::$config['es'][$permissionName])) {
        header("HTTP/1.1 401 Unauthorised");
        echo json_encode(['error' => "User does not have permission to $permissionName"]);
        throw new ElasticsearchProxyAbort('Unauthorised');
      }
    }
    else {
      $options = ['readAuth' => $readAuth];
      iform_load_helpers(['ElasticsearchReportHelper']);
      if ($nid) {
        // Fetch available permissions filters for node.
        $nodeParams = hostsite_get_node_field_value($nid, 'params');
        if (!empty($nodeParams['structure'])) {
          $structure = helper_base::explode_lines($nodeParams['structure']);
          $state = 'search';
          foreach ($structure as $line) {
            if ($line === '[permissionFilters]') {
              $state = 'foundControl';
            }
            elseif ($state === 'foundControl') {
              if (substr($line, 0, 1) === '@') {
                $parts = explode('=', $line, 2);
                $decoded = json_decode($parts[1]);
                $options[substr($parts[0], 1)] = $decoded === NULL ? $parts[1] : $decoded;
              }
              else {
                // Finish loop as done permissionFilters control options.
                break;
              }
            }
          }
        }
      }
      $availablePermissionFilters = ElasticsearchReportHelper::getPermissionFiltersOptions($options);
      // Check $permissionsFilter in list, else access denied.
      if (!array_key_exists($permissionsFilter, $availablePermissionFilters)) {
        header("HTTP/1.1 401 Unauthorised");
        echo json_encode(['error' => "User does not have permission to $permissionsFilter records"]);
        throw new ElasticsearchProxyAbort("Unauthorised - $permissionsFilter not in " . var_export($availablePermissionFilters, TRUE));
      }
    }
  }

  private static function buildEsQueryFromRequest($post) {
    $query = array_merge([
      'bool_queries' => [],
    ], $post);
    $bool = [
      'must' => [],
      'should' => [],
      'must_not' => [],
      'filter' => [],
    ];
    $basicQueryTypes = ['match_all', 'match_none'];
    $fieldValueQueryTypes = [
      'term',
      'match',
      'match_phrase',
      'match_phrase_prefix',
    ];
    $fieldQueryTypes = [
      'exists',
    ];
    $arrayFieldQueryTypes = ['terms'];
    $stringQueryTypes = ['query_string', 'simple_query_string'];
    $objectQueryTypes = ['geo_bounding_box'];
    if (isset($query['textFilters'])) {
      // Apply any filter row parameters to the query.
      foreach ($query['textFilters'] as $field => $value) {
        // Exclamation mark reverses logic.
        $logic = substr($value, 0, 1) === '!' ? 'must_not' : 'must';
        $value = preg_replace('/^!/', '', $value);
        $bool[$logic][] = [
          'simple_query_string' => [
            'query' => $value,
            'fields' => [$field],
            'default_operator' => 'AND',
          ],
        ];
      }
      unset($query['textFilters']);
    }
    if (isset($query['numericFilters'])) {
      // Apply any filter row parameters to the query.
      foreach ($query['numericFilters'] as $field => $value) {
        $value = str_replace(' ', '', $value);
        if (preg_match('/^(\d+(\.\d+)?)\-(\d+(\.\d+)?)$/', $value, $matches)) {
          $bool['must'][] = [
            'range' => [
              $field => [
                'gte' => $matches[1],
                'lte' => $matches[3],
              ],
            ],
          ];
        }
        else {
          // Exclamation mark reverses logic.
          $logic = substr($value, 0, 1) === '!' ? 'must_not' : 'must';
          $value = preg_replace('/^!/', '', $value);
          $bool[$logic][] = ['match' => [$field => $value]];
        }
      }
      unset($query['numericFilters']);
    }
    foreach ($query['bool_queries'] as $qryConfig) {
      if (!empty($qryConfig['query']) && $qryConfig['query'] !== 'null') {
        $queryDef = json_decode(
          str_replace('#value#', $qryConfig['value'], $qryConfig['query']), TRUE
        );
      }
      elseif (in_array($qryConfig['query_type'], $basicQueryTypes)) {
        $queryDef = [$qryConfig['query_type'] => new stdClass()];
      }
      elseif (in_array($qryConfig['query_type'], $fieldValueQueryTypes)) {
        // One of the standard ES field based query types (e.g. term or match).
        $queryDef = [$qryConfig['query_type'] => [$qryConfig['field'] => $qryConfig['value']]];
      }
      elseif (in_array($qryConfig['query_type'], $fieldQueryTypes)) {
        // A query type that just needs a field name.
        $queryDef = [$qryConfig['query_type'] => ['field' => $qryConfig['field']]];
      }
      elseif (in_array($qryConfig['query_type'], $arrayFieldQueryTypes)) {
        // One of the standard ES field based query types (e.g. term or match).
        $queryDef = [$qryConfig['query_type'] => [$qryConfig['field'] => json_decode($qryConfig['value'], TRUE)]];
      }
      elseif (in_array($qryConfig['query_type'], $stringQueryTypes)) {
        // One of the ES query string based query types.
        $queryDef = [$qryConfig['query_type'] => ['query' => $qryConfig['value']]];
      }
      elseif (in_array($qryConfig['query_type'], $objectQueryTypes)) {
        $queryDef = [$qryConfig['query_type'] => $qryConfig['value']];
      }
      else {
        header("HTTP/1.1 400 Bad request");
        echo json_encode(['error' => 'Incorrect filter type parameter']);
        throw new ElasticsearchProxyAbort('Incorrect filter type parameter: ' . $qryConfig['query_type']);
      }
      if (!empty($qryConfig['nested']) && $qryConfig['nested'] !== 'null') {
        // Must not nested queries should be handled at outer level.
        $outerBoolClause = $qryConfig['bool_clause'] === 'must_not' ? 'must_not' : 'must';
        $innerBoolClause = $qryConfig['bool_clause'] === 'must_not' ? 'must' : $qryConfig['bool_clause'];
        $bool[$outerBoolClause][] = [
          'nested' => [
            'path' => $qryConfig['nested'],
            'query' => [
              'bool' => [
                $innerBoolClause => [$queryDef],
              ],
            ],
          ],
        ];
      }
      else {
        $bool[$qryConfig['bool_clause']][] = $queryDef;
      }

    }
    unset($query['bool_queries']);
    // Apply a training mode filter.
    $bool['must'][] = [
      'term' => ['metadata.trial' => hostsite_get_user_field('training') ? TRUE : FALSE],
    ];
    iform_load_helpers([]);
    $readAuth = helper_base::get_read_auth(self::$config['indicia']['website_id'], self::$config['indicia']['password']);
    if (!empty($query['permissions_filter'])) {
      self::applyPermissionsFilter($readAuth, $query, $bool);
    }
    if (!empty($query['group_filter'])) {
      self::applyGroupFilter($readAuth, $query['group_filter'], $bool, $query);
    }
    if (!empty($query['user_filters'])) {
      self::applyUserFilters($readAuth, $query, $bool);
    }

    if (!empty($query['filter_def'])) {
      self::applyFilterDef($readAuth, $query['filter_def'], $bool);
    }
    // Apply default restrictions.
    if (!self::$confidentialFilterApplied) {
      // Unless explicitly specified in a filter, hide confidential.
      $bool['must'][] = ['term' => ['metadata.confidential' => FALSE]];
    }
    if (!self::$releaseStatusFilterApplied) {
      // Unless explicitly specified in a filter, limit to released.
      $bool['must'][] = ['term' => ['metadata.release_status' => 'R']];
    }
    unset($query['user_filters']);
    unset($query['refresh_user_filters']);
    unset($query['permissions_filter']);
    unset($query['filter_def']);
    unset($query['group_filter']);

    $bool = array_filter($bool, function ($k) {
      return count($k) > 0;
    });
    if (!empty($bool)) {
      $query['query'] = ['bool' => $bool];
    }
    return $query;
  }

  /**
   * Applies an option from the [permissionFilters] control.
   *
   * Converts the option to a user ID filter, geo filter, group filter, or
   * applies the selected filter ID.
   *
   * @param array $readAuth
   *   Read authorisation tokens.
   * @param array $query
   *   Request query object. May be updated, e.g. to append a user filter ID.
   * @param array $bool
   *   Constructed bool query for Elasticsearch.
   */
  private static function applyPermissionsFilter(array $readAuth, array &$query, array &$bool) {
    switch ($query['permissions_filter']) {
      case 'p-all':
        // No filter.
        break;

      case 'p-my':
        $bool['must'][] = [
          'term' => ['metadata.created_by_id' => hostsite_get_user_field('indicia_user_id')],
        ];
        self::$config['es']['scope'] = 'user';
        break;

      case 'p-location_collation':
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
        // Filter possible formats.
        // g-my-ID (my group records)
        // g-ID (group records)
        // f-ID (filter)
        if (preg_match('/^(?P<type>[fg])(?P<users>-(my|all))?-(?P<id>\d+)$/', $query['permissions_filter'], $matches)) {
          if ($matches['type'] === 'f') {
            // Add filter ID to list that will be applied later.
            $query['user_filters'][] = $matches['id'];
            self::$setScopeUsingFilter = $matches['id'];
          }
          elseif ($matches['type'] === 'g') {
            // Group filter, should allow reporting access.
            self::$config['es']['scope'] = 'reporting';
            self::applyGroupFilter($readAuth, [
              'id' => $matches['id'],
              'implicit' => FALSE,
            ], $bool, $query);
          }
          if (!empty($matches['users']) && $matches['users'] === '-my') {
            // If limited to my records.
            $bool['must'][] = [
              'term' => ['metadata.created_by_id' => hostsite_get_user_field('indicia_user_id')],
            ];
          }
        }
        else {
          // This shouldn't happen.
          header("HTTP/1.1 400 Bad request");
          echo json_encode(['error' => 'Incorrect permissions_filter parameter']);
          throw new ElasticsearchProxyAbort('Incorrect permissions_filter parameter: ' . $query['permissions_filter']);
        }

    }
  }

  /**
   * Converts a sharing term to a scope term for the REST API.
   *
   * @param string $term
   *   Sharing term, e.g. 'Data Flow', 'My'.
   *
   * @return string
   *   Scope term, e.g. 'data_flow', 'user'.
   */
  private static function sharingTermToScope($term) {
    $scope = str_replace(' ', '_', strtolower($term));
    $scope = $scope === 'my' ? 'user' : $scope;
    return $scope;
  }

  /**
   * Converts Indicia style filters in proxy request to ES query syntax.
   *
   * @param array $readAuth
   *   Read authentication tokens.
   * @param array $query
   *   Query passed in proxy request, which may contain an array of
   *   user_filters (filter IDs) to convert.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  public static function applyUserFilters(array $readAuth, array $query, array &$bool) {
    if (count($query['user_filters']) > 0) {
      require_once 'report_helper.php';
      foreach ($query['user_filters'] as $userFilter) {
        $filterData = report_helper::get_report_data([
          'dataSource' => '/library/filters/filter_with_transformed_searcharea',
          'extraParams' => [
            'filter_id' => $userFilter,
          ],
          'readAuth' => $readAuth,
          'caching' => $query['refresh_user_filters'] === 'true' ? 'store' : TRUE,
        ]);
        if (count($filterData) === 0) {
          throw new exception("Filter with ID $userFilter could not be loaded.");
        }
        $definition = json_decode($filterData[0]['definition'], TRUE);
        // Can't be both searchArea (freehand) and location area.
        $definition['searchArea'] = $filterData[0]['search_area']
          ? $filterData[0]['search_area'] : $filterData[0]['location_area'];
        self::applyFilterDef($readAuth, $definition, $bool);
        if ($userFilter == self::$setScopeUsingFilter) {
          self::$config['es']['scope'] = self::sharingTermToScope($filterData[0]['sharing']);
        }
      }
    }
  }

  /**
   * For group filter where implicit=true, apply a members filter.
   */
  private static function applyGroupMembersFilter(array $readAuth, $groupFilter, array &$bool) {
    $groupUsersData = helper_base::get_population_data([
      'table' => 'groups_user',
      'extraParams' => $readAuth + [
        'group_id' => $groupFilter['id'],
        'columns' => 'user_id',
      ],
      'cachePerUser' => FALSE,
    ]);
    $userIds = [];
    foreach ($groupUsersData as $user) {
      $userIds[] = (integer) $user['user_id'];
    }
    $bool['must'][] = [
      'terms' => ['metadata.created_by_id' => $userIds],
    ];
  }

  /**
   * Applies a group's filter to the request.
   */
  private static function applyGroupFilter(array $readAuth, $groupFilter, array &$bool, &$query) {
    /*
     * Modes
     * - match group ID + match filter (implicit=f)
     * - match group members + match filter (implicit=t)
     * - match filter only (implicit=null)
     * Bool implicit value may be read from URL, or code, so need to be
     * flexible.
     */
    if ($groupFilter['implicit'] === FALSE || $groupFilter['implicit'] == 'f' || $groupFilter['implicit'] == 'false') {
      // Records added to group linked form.
      $bool['must'][] = [
        'term' => ['metadata.group.id' => $groupFilter['id']],
      ];
    }
    elseif ($groupFilter['implicit'] === TRUE || $groupFilter['implicit'] == 't' || $groupFilter['implicit'] == 'true') {
      // Records added by group members.
      self::applyGroupMembersFilter($readAuth, $groupFilter, $bool);
    }
    // Apply the filter.
    $groupData = helper_base::get_population_data([
      'table' => 'group',
      'extraParams' => $readAuth + [
        'id' => $groupFilter['id'],
      ],
      'cachePerUser' => FALSE,
    ]);
    if (count($groupData) === 0) {
      throw new exception("Group $groupFilter[id] not found");
    }
    // Load the filter into user filters, so it gets applied with the rest.
    if (!empty($groupData[0]['filter_id'])) {
      $query['user_filters'][] = $groupData[0]['filter_id'];
    }
  }

  /**
   * Converts from an old style PG filter def to ES bool query.
   *
   * @param array $readAuth
   *   Read authentication tokens.
   * @param array $definition
   *   PG filter definition.
   * @param array $bool
   *   ES bool query definintion.
   */
  private static function applyFilterDef(array $readAuth, array $definition, array &$bool) {
    self::convertLocationListToSearchArea($definition, $readAuth);
    self::applyUserFiltersTaxonGroupList($definition, $bool);
    self::applyUserFiltersTaxaTaxonList($definition, $bool, $readAuth);
    self::applyUserFiltersTaxonMeaning($definition, $bool, $readAuth);
    self::applyUserFiltersTaxaTaxonListExternalKey($definition, $bool);
    self::applyUserFiltersTaxonRankSortOrder($definition, $bool);
    self::applyFlagFilter('marine', $definition, $bool);
    self::applyFlagFilter('freshwater', $definition, $bool);
    self::applyFlagFilter('terrestrial', $definition, $bool);
    self::applyFlagFilter('non_native', $definition, $bool);
    self::applyUserFiltersSearchArea($definition, $bool);
    self::applyUserFiltersLocationName($definition, $bool);
    self::applyUserFiltersIndexedLocationList($definition, $bool);
    self::applyUserFiltersIndexedLocationTypeList($definition, $bool, $readAuth);
    self::applyUserFiltersDate($definition, $bool);
    self::applyUserFiltersWho($definition, $bool);
    self::applyUserFiltersOccId($definition, $bool);
    self::applyUserFiltersOccExternalKey($definition, $bool);
    self::applyUserFiltersSmpId($definition, $bool);
    self::applyUserFiltersQuality($definition, $bool);
    self::applyUserFiltersIdentificationDifficulty($definition, $bool);
    self::applyUserFiltersRuleChecks($definition, $bool);
    self::applyUserFiltersAutoCheckRule($definition, $bool);
    self::applyUserFiltersHasPhotos($definition, ['has_photos'], $bool, $readAuth);
    self::applyUserFiltersWebsiteList($definition, $bool);
    self::applyUserFiltersSurveyList($definition, $bool);
    self::applyUserFiltersImportGuidList($definition, $bool);
    self::applyUserFiltersInputFormList($definition, $bool);
    self::applyUserFiltersGroupId($definition, $bool);
    self::applyUserFiltersAccessRestrictions($definition, $bool);
    self::applyUserFiltersTaxaScratchpadList($definition, $bool, $readAuth);
  }

  /**
   * If there is a location_list in the filter, convert to searchArea.
   *
   * This requires fetching the combined boundaries from the warehouse,
   * transformed to EPSG:4326.
   */
  private static function convertLocationListToSearchArea(array &$definition, array $readAuth) {
    $filter = self::getDefinitionFilter($definition, [
      'location_list',
      'location_id',
    ]);
    if (!empty($filter)) {
      require_once 'report_helper.php';
      $boundaryData = report_helper::get_report_data([
        'dataSource' => '/library/locations/locations_combined_boundary_transformed',
        'extraParams' => [
          'location_ids' => $filter['value'],
        ],
        'readAuth' => $readAuth,
        'caching' => TRUE,
      ]);
      $definition['searchArea'] = $boundaryData[0]['geom'];
    }
  }

  /**
   * Converts an Indicia filter definition taxon_group_list to an ES query.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersTaxonGroupList(array $definition, array &$bool) {
    $filter = self::getDefinitionFilter($definition, [
      'taxon_group_list',
      'taxon_group_id',
    ]);
    if (!empty($filter)) {
      $bool['must'][] = [
        'terms' => ['taxon.group_id' => explode(',', $filter['value'])],
      ];
    }
  }

  /**
   * Generic function to apply a taxonomy filter to ES query.
   *
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   * @param array $readAuth
   *   Read authentication tokens.
   * @param string $filterField
   *   Name of the field to filter on ('id' or 'taxon_meaning_id').
   * @param string $filterValues
   *   Comma separated list of IDs to filter against.
   */
  private static function applyTaxonomyFilter(array &$bool, array $readAuth, $filterField, $filterValues) {
    // Convert the IDs to external keys, stored in ES as taxon_ids.
    $taxonData = helper_base::get_population_data([
      'report' => 'library/taxa/convert_ids_to_external_keys',
      'extraParams' => [
        $filterField => $filterValues,
        'master_checklist_id' => hostsite_get_config_value('iform', 'master_checklist_id', 0),
      ] + $readAuth,
      'cachePerUser' => FALSE,
    ]);
    $keys = [];
    foreach ($taxonData as $taxon) {
      $keys[] = $taxon['external_key'];
    }
    $keys = array_unique($keys);
    $bool['must'][] = ['terms' => ['taxon.higher_taxon_ids' => $keys]];
  }

  /**
   * Converts an Indicia filter definition taxa_taxon_list_list to an ES query.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   * @param array $readAuth
   *   Read authentication tokens.
   */
  private static function applyUserFiltersTaxaTaxonList(array $definition, array &$bool, array $readAuth) {
    $filter = self::getDefinitionFilter($definition, [
      'taxa_taxon_list_list',
      'higher_taxa_taxon_list_list',
      'taxa_taxon_list_id',
      'higher_taxa_taxon_list_id',
    ]);
    if (!empty($filter)) {
      self::applyTaxonomyFilter($bool, $readAuth, 'id', $filter['value']);
    }
  }

  /**
   * Converts an Indicia filter definition taxon_meaning_list to an ES query.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   * @param array $readAuth
   *   Read authentication tokens.
   */
  private static function applyUserFiltersTaxonMeaning(array $definition, array &$bool, array $readAuth) {
    $filter = self::getDefinitionFilter($definition, [
      'taxon_meaning_list',
      'taxon_meaning_id',
    ]);
    if (!empty($filter)) {
      self::applyTaxonomyFilter($bool, $readAuth, 'taxon_meaning_id', $filter['value']);
    }
  }

  /**
   * Converts an filter def taxa_taxon_list_external_key_list to an ES query.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersTaxaTaxonListExternalKey(array $definition, array &$bool) {
    $filter = self::getDefinitionFilter($definition, [
      'taxa_taxon_list_external_key_list',
    ]);
    if (!empty($filter)) {
      $bool['must'][] = ['terms' => ['taxon.higher_taxon_ids' => explode(',', $filter['value'])]];
    }
  }

  /**
   * Converts a filter definition taxon_rank_sort_order filter to an ES query.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersTaxonRankSortOrder(array $definition, array &$bool) {
    $filter = self::getDefinitionFilter($definition, ['taxon_rank_sort_order']);
    // Filter op can be =, >= or <=.
    if (!empty($filter)) {
      if ($filter['op'] === '=') {
        $bool['must'][] = [
          'match' => [
            'taxon.taxon_rank_sort_order' => $filter['value'],
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
   * Converts a filter definition flag filter to an ES query.
   *
   * @param string $flag
   *   Flag name, e.g. marine, terrestrial, freshwater, non_native.
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyFlagFilter($flag, array $definition, array &$bool) {
    $filter = self::getDefinitionFilter($definition, ["{$flag}_flag"]);
    // Filter op can be =, >= or <=.
    if (!empty($filter) && $filter['value'] !== 'all') {
      $bool['must'][] = [
        'match' => [
          "taxon.$flag" => $filter['value'] === 'Y',
        ],
      ];
    }
  }

  /**
   * Converts an Indicia filter definition search_area to an ES query.
   *
   * For ES purposes, any location_list filter is modified to a searchArea
   * filter beforehand.
   *
   * @param string $definition
   *   WKT for the searchArea in EPSG:4326.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersSearchArea($definition, array &$bool) {
    if (!empty($definition['searchArea'])) {
      $bool['must'][] = [
        'geo_shape' => [
          'location.geom' => [
            'shape' => $definition['searchArea'],
            'relation' => 'intersects',
          ],
        ],
      ];
    }
  }

  /**
   * Converts an Indicia filter definition location_name to an ES query.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersLocationName(array $definition, array &$bool) {
    $filter = self::getDefinitionFilter($definition, ['location_name']);
    if (!empty($filter)) {
      $bool['must'][] = ['match_phrase' => ['location.verbatim_locality' => $filter['value']]];
    }
  }

  /**
   * Converts an Indicia filter definition indexed_location_list to an ES query.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersIndexedLocationList(array $definition, array &$bool) {
    $filter = self::getDefinitionFilter($definition, [
      'indexed_location_list',
      'indexed_location_id',
    ]);
    if (!empty($filter)) {
      $boolClause = !empty($filter['op']) && $filter['op'] === 'not in' ? 'must_not' : 'must';
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
   * Converts a filter definition indexed_location_type_list to an ES query.
   *
   * Returns all records in locations of the given type(s).
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   * @param array $readAuth
   *   Read authentication tokens.
   */
  private static function applyUserFiltersIndexedLocationTypeList(array $definition, array &$bool, array $readAuth) {
    $filter = self::getDefinitionFilter($definition, ['indexed_location_type_list']);
    if (!empty($filter)) {
      // Convert the location type IDs to terms that are used in the ES
      // document.
      $typeRows = helper_base::get_population_data([
        'table' => 'termlists_term',
        'extraParams' => [
          'id' => $filter,
          'view' => 'cache',
        ] + $readAuth,
      ]);
      $types = [];
      foreach ($typeRows as $typeRow) {
        $types[] = $typeRow['term'];
      }
      if (count($types) > 0) {
        $bool['must'][] = [
          'nested' => [
            'path' => 'location.higher_geography',
            'query' => [
              'terms' => ['location.higher_geography.type' => $types],
            ],
          ],
        ];
      }
    }
  }

  /**
   * Converts an Indicia filter definition date filter to an ES query.
   *
   * Date range, year or date age filters supported. Support for recorded
   * (default), input, edited, verified dates. Age is supported as long as
   * format specifies age in minutes, hours, days, weeks, months or years.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersDate(array $definition, array &$bool) {
    $esFields = [
      'recorded' => 'event.date_start',
      'input' => 'metadata.created_on',
      'edited' => 'metadata.updated_on',
      'verified' => 'identification.verified_on',
    ];
    // Default to recorded date.
    $definition['date_type'] = empty($definition['date_type']) ? 'recorded' : $definition['date_type'];
    // Check to see if we have a year filter.
    $fieldName = $definition['date_type'] === 'recorded' ? "date_year" : "$definition[date_type]_date_year";
    if (!empty($definition[$fieldName]) && !empty($definition[$fieldName . '_op'])) {
      if ($definition[$fieldName . '_op'] === '=') {
        $bool['must'][] = [
          'term' => [
            'event.year' => $definition[$fieldName],
          ],
        ];
      }
      else {
        $esOp = $definition[$fieldName . '_op'] === '>=' ? 'gte' : 'lte';
        $bool['must'][] = [
          'range' => [
            'event.year' => [
              $esOp => $definition[$fieldName],
            ],
          ],
        ];
      }
    }
    else {
      // Check for other filters that work off the precise date fields.
      $dateTypes = [
        'from' => 'gte',
        'to' => 'lte',
        'age' => 'gte',
      ];
      foreach ($dateTypes as $type => $esOp) {
        $fieldName = $definition['date_type'] === 'recorded' ? "date_$type" : "$definition[date_type]_date_$type";
        if (!empty($definition[$fieldName])) {
          $value = $definition[$fieldName];
          // Convert date format.
          if (preg_match('/^(?P<d>\d{2})\/(?P<m>\d{2})\/(?P<Y>\d{4})$/', $value, $matches)) {
            $value = "$matches[Y]-$matches[m]-$matches[d]";
          }
          elseif ($type === 'age') {
            $value = 'now-' . str_replace(
              ['minute', 'hour', 'day', 'week', 'month', 'year', 's', ' '],
              ['m', 'H', 'd', 'w', 'M', 'y', '', ''],
              strtolower($value)
            );
          }
          $bool['must'][] = [
            'range' => [
              $esFields[$definition['date_type']] => [
                $esOp => $value,
              ],
            ],
          ];
        }
      }
    }
  }

  /**
   * Converts an Indicia filter definition my_records filter to an ES query.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersWho(array $definition, array &$bool) {
    if (!empty($definition['my_records']) && $definition['my_records'] === '1') {
      $bool['must'][] = [
        'match' => ['metadata.created_by_id' => hostsite_get_user_field('indicia_user_id')],
      ];
    }
  }

  /**
   * Converts an Indicia filter definition idlist or occ_id to an ES query.
   *
   * Both occ_id and idlist are filters on occurrence.id so we treat them the
   * same here.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersOccId(array $definition, array &$bool) {
    $filter = self::getDefinitionFilter($definition, ['idlist', 'occ_id']);
    if (!empty($filter)) {
      $op = empty($filter['op']) ? '=' : $filter['op'];
      if ($op === '=') {
        $bool['must'][] = [
          'terms' => ['id' => explode(',', $filter['value'])],
        ];
      }
      else {
        $translate = ['>=' => 'gte', '<=' => 'lte'];
        $bool['must'][] = [
          'range' => [
            'id' => [
              $translate[$op] => $filter['value'],
            ],
          ],
        ];
      }
    }
  }

  /**
   * Converts an Indicia filter definition smp_id to an ES query.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersSmpId(array $definition, array &$bool) {
    $filter = self::getDefinitionFilter($definition, ['smp_id']);
    if (!empty($filter)) {
      $op = empty($filter['op']) ? '=' : $filter['op'];
      if ($op === '=') {
        $bool['must'][] = [
          'terms' => ['event.event_id' => explode(',', $filter['value'])],
        ];
      }
      else {
        $translate = ['>=' => 'gte', '<=' => 'lte'];
        $bool['must'][] = [
          'range' => [
            'event.event_id' => [
              $translate[$op] => $filter['value'],
            ],
          ],
        ];
      }
    }
  }

  /**
   * Converts an filter definition occurrence_external_key to an ES query.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersOccExternalKey(array $definition, array &$bool) {
    $filter = self::getDefinitionFilter($definition, ['occurrence_external_key']);
    if (!empty($filter)) {
      $bool['must'][] = [
        'terms' => ['occurrence.source_system_key' => explode(',', $filter['value'])],
      ];
    }
  }

  /**
   * Converts an Indicia filter definition quality filter to an ES query.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersQuality(array $definition, array &$bool) {
    $filter = self::getDefinitionFilter($definition, ['quality']);
    if (!empty($filter)) {
      switch ($filter['value']) {
        case 'V1':
          $bool['must'][] = ['match' => ['identification.verification_status' => 'V']];
          $bool['must'][] = ['match' => ['identification.verification_substatus' => 1]];
          break;

        case 'V':
          $bool['must'][] = ['match' => ['identification.verification_status' => 'V']];
          break;

        case '-3':
          $bool['must'][] = [
            'bool' => [
              'should' => [
                [
                  'bool' => [
                    'must' => [
                      ['term' => ['identification.verification_status' => 'V']],
                    ],
                  ],
                ],
                [
                  'bool' => [
                    'must' => [
                      ['term' => ['identification.verification_status' => 'C']],
                      ['term' => ['identification.verification_substatus' => 3]],
                    ],
                  ],
                ],
              ],
            ],
          ];
          break;

        case 'C3':
          $bool['must'][] = ['match' => ['identification.verification_status' => 'C']];
          $bool['must'][] = ['match' => ['identification.verification_substatus' => 3]];
          break;

        case 'C':
          $bool['must'][] = ['match' => ['identification.recorder_certainty' => 'Certain']];
          $bool['must_not'][] = ['match' => ['identification.verification_status' => 'R']];
          break;

        case 'L':
          $bool['must'][] = [
            'terms' => [
              'identification.recorder_certainty.keyword' => [
                'Certain',
                'Likely',
              ],
            ],
          ];
          $bool['must_not'][] = ['match' => ['identification.verification_status' => 'R']];
          break;

        case 'P':
          $bool['must'][] = ['match' => ['identification.verification_status' => 'C']];
          $bool['must'][] = ['match' => ['identification.verification_substatus' => 0]];
          $bool['must_not'][] = ['exists' => ['field' => 'identification.query']];
          break;

        case '!R':
          $bool['must_not'][] = ['match' => ['identification.verification_status' => 'R']];
          break;

        case '!D':
          $bool['must_not'][] = [
            'match' => ['identification.verification_status' => 'R'],
          ];
          $bool['must_not'][] = [
            'terms' => ['identification.query.keyword' => ['Q', 'A']],
          ];
          break;

        case 'D':
          $bool['must'][] = ['match' => ['identification.query' => 'Q']];
          break;

        case 'A':
          $bool['must'][] = ['match' => ['identification.query' => 'A']];
          break;

        case 'R':
          $bool['must'][] = ['match' => ['identification.verification_status' => 'R']];
          break;

        case 'R4':
          $bool['must'][] = ['match' => ['identification.verification_status' => 'R']];
          $bool['must'][] = ['match' => ['identification.verification_substatus' => 4]];
          break;

        case 'DR':
          // Queried or not accepted.
          $bool['must'][] = [
            'bool' => [
              'should' => [
                [
                  'bool' => [
                    'must' => [
                      ['term' => ['identification.verification_status' => 'R']],
                    ],
                  ],
                ],
                [
                  'bool' => [
                    'must' => [
                      ['match' => ['identification.query' => 'Q']],
                    ],
                  ],
                ],
              ],
            ],
          ];
          break;

        default:
          // Nothing to do for 'all'.
      }
    }
  }

  /**
   * Converts an Indicia filter id difficulty filter to an ES query.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersIdentificationDifficulty(array $definition, array &$bool) {
    $filter = self::getDefinitionFilter($definition, ['identification_difficulty']);
    if (!empty($filter) && !empty($filter['op'])) {
      if (in_array($filter['op'], ['>=', '<='])) {
        $test = $filter['op'] === '>=' ? 'gte' : 'lte';
        $bool['must'][] = [
          'range' => [
            'identification.auto_checks.identification_difficulty' => [
              $test => $filter['value'],
            ],
          ],
        ];
      }
      else {
        $bool['must'][] = ['term' => ['identification.auto_checks.identification_difficulty' => $filter['value']]];
      }
    }
  }

  /**
   * Converts an Indicia filter definition rule checks filter to an ES query.
   *
   * Handles both automatic checks and a user's custom verification rule flags.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersRuleChecks(array $definition, array &$bool) {
    $filter = self::getDefinitionFilter($definition, ['autochecks']);
    if (!empty($filter)) {
      if (in_array($filter['value'], ['P', 'F'])) {
        // Pass or Fail options are auto-checks from the Data Cleaner module.
        $bool['must'][] = [
          'match' => [
            'identification.auto_checks.result' => $filter['value'] === 'P',
          ],
        ];
        if ($filter['value'] === 'P') {
          $bool['must'][] = [
            'query_string' => ['query' => '_exists_:identification.auto_checks.verification_rule_types_applied'],
          ];
        }
      }
      elseif (in_array($filter['value'], ['PC', 'FC'])) {
        // Pass Custom or Fail Custom options are for custom verification rule
        // checks.
        $test = $filter['value'] === 'PC' ? 'must_not' : 'must';
        $bool[$test][] = [
          'nested' => [
            'path' => 'identification.custom_verification_rule_flags',
            'query' => [
              'term' => [
                'identification.custom_verification_rule_flags.created_by_id' => hostsite_get_user_field('indicia_user_id'),
              ],
            ],
          ],
        ];
      }
    }
  }

  /**
   * Converts an Indicia filter definition auto checks filter to an ES query.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersAutoCheckRule(array $definition, array &$bool) {
    $filter = self::getDefinitionFilter($definition, ['autocheck_rule']);
    if (!empty($filter)) {
      $value = str_replace('_', '', $filter['value']);
      $bool['must'][] = [
        'term' => ['identification.auto_checks.output.rule_type' => $value],
      ];
    }

  }

  /**
   * Converts an Indicia filter definition website_list to an ES query.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersWebsiteList(array $definition, array &$bool) {
    $filter = self::getDefinitionFilter($definition, [
      'website_list',
      'website_id',
    ]);
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
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersSurveyList(array $definition, array &$bool) {
    $filter = self::getDefinitionFilter($definition, [
      'survey_list',
      'survey_id',
    ]);
    if (!empty($filter)) {
      $boolClause = !empty($filter['op']) && $filter['op'] === 'not in' ? 'must_not' : 'must';
      $bool[$boolClause][] = [
        'terms' => ['metadata.survey.id' => explode(',', $filter['value'])],
      ];
    }
  }

  /**
   * Converts an Indicia filter definition import_guid_list to an ES query.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersImportGuidList(array $definition, array &$bool) {
    $filter = self::getDefinitionFilter($definition, ['import_guid_list']);
    if (!empty($filter)) {
      $boolClause = !empty($filter['op']) && $filter['op'] === 'not in' ? 'must_not' : 'must';
      $bool[$boolClause][] = [
        'terms' => [
          'metadata.import_guid' => explode(',', str_replace("'", '', $filter['value'])),
        ],
      ];
    }
  }

  /**
   * Converts an Indicia filter definition input_form_list to an ES query.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersInputFormList(array $definition, array &$bool) {
    $filter = self::getDefinitionFilter($definition, ['input_form_list']);
    if (!empty($filter)) {
      $boolClause = !empty($filter['op']) && $filter['op'] === 'not in' ? 'must_not' : 'must';
      $bool[$boolClause][] = [
        'terms' => [
          'metadata.input_form' => explode(',', str_replace("'", '', $filter['value'])),
        ],
      ];
    }
  }

  /**
   * Converts an Indicia filter definition group_id to an ES query.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersGroupId(array $definition, array &$bool) {
    $filter = self::getDefinitionFilter($definition, ['group_id']);
    if (!empty($filter)) {
      $bool['must'][] = [
        'terms' => ['metadata.group.id' => explode(',', $filter['value'])],
      ];
    }
  }

  /**
   * Converts an Indicia filter definition has_photos filter to an ES query.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $params
   *   List of parameter names that can be used for this type of filter
   *   (allowing for deprecated names etc).
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   * @param array $readAuth
   *   Read authentication tokens.
   */
  private static function applyUserFiltersHasPhotos(array $definition, array $params, array &$bool, array $readAuth) {
    $filter = self::getDefinitionFilter($definition, $params);
    if (!empty($filter)) {
      $boolClause = !empty($filter['op']) && $filter['op'] === 'not in' ? 'must_not' : 'must';
      $bool[$boolClause][] = [
        'nested' => [
          'path' => 'occurrence.media',
          'query' => [
            'bool' => [
              'must' => ['exists' => ['field' => 'occurrence.media']],
            ],
          ],
        ],
      ];
    }
  }

  /**
   * Converts an Indicia filter access restrictions to an ES query.
   *
   * Covers the following fields:
   * * confidential
   * * exclude_sensitive
   * * release_status.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersAccessRestrictions(array $definition, array &$bool) {
    $filter = self::getDefinitionFilter($definition, ['confidential']);
    if (!empty($filter)) {
      if ($filter['value'] !== 'all') {
        $bool['must'][] = [
          'term' => ['metadata.confidential' => $filter['value'] === 't' ? TRUE : FALSE],
        ];
      }
      self::$confidentialFilterApplied = TRUE;
    }
    $filter = self::getDefinitionFilter($definition, ['exclude_sensitive']);
    if (!empty($filter)) {
      $bool['must'][] = ['term' => ['metadata.sensitive' => FALSE]];
    }
    $filter = self::getDefinitionFilter($definition, ['release_status']);
    $userId = hostsite_get_user_field('indicia_user_id');
    if (!empty($filter)) {
      switch ($filter['value']) {
        case 'R':
          // Released.
          $bool['must'][] = ['term' => ['metadata.release_status' => 'R']];
          break;

        case 'RM':
          // Released by other recorders plus my own unreleased records.
          $bool['must'][] = [
            'query_string' => ['query' => "metadata.release_status:R OR metadata.created_by_id:$userId"],
          ];
          break;

        case 'U':
          // Unreleased because records belong of a project that has not yet
          // released the records.
          $bool['must'][] = ['term' => ['metadata.release_status' => 'U']];
          break;

        case 'RU':
          // Released plus unreleased because records belong to a project that
          // has not yet released the records.
          $bool['must_not'][] = ['term' => ['metadata.release_status' => 'P']];
          break;

        case 'P':
          // Recorder has requested a precheck before release.
          $bool['must'][] = ['term' => ['metadata.release_status' => 'P']];
          break;

        case 'RP':
          // Released plus records where recorder has requested a precheck
          // before release.
          $bool['must_not'][] = ['term' => ['metadata.release_status' => 'U']];
          break;

        case 'A':
          // All.
          break;

        default:
          throw new ElasticsearchProxyAbort("Invalid release_status filter value $filter[value]");
      }
      self::$releaseStatusFilterApplied = TRUE;
    }
  }

  /**
   * Converts a filter definition taxa_scratchpad_list_id to an ES query.
   *
   * Finds all records for a list of taxa (using external_key as unique ID),
   * including taxonomic children.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   * @param array $readAuth
   *   Read authentication tokens.
   */
  private static function applyUserFiltersTaxaScratchpadList(array $definition, array &$bool, array $readAuth) {
    $filter = self::getDefinitionFilter($definition, [
      'taxa_scratchpad_list_id',
    ]);
    if (!empty($filter)) {
      require_once 'report_helper.php';
      // Convert the IDs to external keys, stored in ES as taxon_ids.
      $taxonData = report_helper::get_report_data([
        'dataSource' => '/library/taxa/external_keys_for_scratchpad',
        'extraParams' => [
          'scratchpad_list_id' => $filter['value'],
        ],
        'readAuth' => $readAuth,
        'caching' => TRUE,
      ]);
      $keys = [];
      foreach ($taxonData as $taxon) {
        $keys[] = $taxon['external_key'];
      }
      $bool['must'][] = ['terms' => ['taxon.higher_taxon_ids' => $keys]];
    }
  }

  /**
   * Works out the filter value and associated operation for a set of params.
   */
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

  /**
   * Receives a list of IDs to move between websites/datasets.
   *
   * Used by the recordsMover button.
   */
  private static function bulkMoveIds($nid, array $ids, $datasetMappings, $precheck) {
    // Now do the move on the warehouse.
    iform_load_helpers(['helper_base']);
    $request = helper_base::$base_url . "index.php/services/data_utils/bulk_move";
    $conn = iform_get_connection_details($nid);
    $auth = helper_base::get_read_write_auth($conn['website_id'], $conn['password']);
    $postargs = helper_base::array_to_query_string(array_merge([
      'occurrence:ids' => implode(',', $ids),
      'datasetMappings' => $datasetMappings,
      'precheck' => $precheck ? 't' : 'f',
    ], $auth['write_tokens']), TRUE);
    $response = helper_base::http_post($request, $postargs, FALSE);
    // The response should be in JSON.
    header('Content-type: application/json');
    $output = json_decode($response['output']);
    if (!$precheck && $output->code === 200) {
      // Set website ID to 0, basically disabling the ES copy of the record
      // until a proper update with correct taxonomy information comes through.
      self::internalModifyListOnES($ids, [], 0);
    }
    return $response['output'];
  }

  /**
   * Receives a filter defining records to move between websites/datasets.
   *
   * Used by the recordsMover button.
   */
  private static function proxyBulkMoveAll($nid) {
    $batchInfo = self::getOccurrenceIdPageFromFilter(
      $nid,
      $_POST['occurrence:idsFromElasticFilter'],
      $_POST['search_after'] ?? NULL,
    );
    if (empty($batchInfo['ids'])) {
      echo json_encode([
        'code' => 204,
        'message' => 'No Content',
      ]);
      return;
    }
    $response = self::bulkMoveIds($nid, $batchInfo['ids'], $_POST['datasetMappings'], !empty($_POST['precheck']));
    // Attach the search_after pagination info to the response.
    $responseArr = json_decode($response, TRUE);
    if (!empty($batchInfo['search_after'])) {
      // Set pagination info, but not if empty array returned (which implies
      // all done).
      $responseArr['search_after'] = $batchInfo['search_after'];
    }
    echo json_encode($responseArr);
  }

  /**
   * Receives a list of IDs to move between websites/datasets.
   *
   * Used by the recordsMover button.
   */
  private static function proxyBulkMoveIds($nid) {
    echo self::bulkMoveIds($nid, explode(',', $_POST['occurrence:ids']), $_POST['datasetMappings'], !empty($_POST['precheck']));
  }

  /**
   * Proxy method that receives a filter and clears the user's custom flags.
   *
   * All custom verification rule flags created by the user within the records
   * identified by the current filter will be cleared.
   *
   * @param int $nid
   *   Node ID for accessing config.
   */
  private static function proxyClearCustomResults($nid) {
    iform_load_helpers(['helper_base']);
    $alias = self::getEsEndpoint();
    $userId = hostsite_get_user_field('indicia_user_id');
    $conn = iform_get_connection_details($nid);
    $readAuth = helper_base::get_read_auth($conn['website_id'], $conn['password']);
    self::checkPermissionsFilter($_POST, $readAuth, $nid);
    $url = self::$config['indicia']['base_url'] . "index.php/services/rest/custom_verification_rulesets/clear-flags?alias=$alias&user_id=$userId";
    $query = self::buildEsQueryFromRequest($_POST);
    echo self::curlPost($url, $query);
  }

  /**
   * Proxy method which runs a custom verification ruleset.
   *
   * Used by the runCustomVerificationRulesets control.
   *
   * @param int $nid
   *   Node ID for accessing config.
   */
  private static function proxyRunCustomRuleset($nid) {
    iform_load_helpers(['helper_base']);
    $alias = self::getEsEndpoint();
    $userId = hostsite_get_user_field('indicia_user_id');
    $rulesetId = $_GET['ruleset_id'];
    $conn = iform_get_connection_details($nid);
    $readAuth = helper_base::get_read_auth($conn['website_id'], $conn['password']);
    self::checkPermissionsFilter($_POST, $readAuth, $nid);
    $url = self::$config['indicia']['base_url'] . "index.php/services/rest/custom_verification_rulesets/$rulesetId/run-request?alias=$alias&user_id=$userId";
    $query = self::buildEsQueryFromRequest($_POST);
    echo self::curlPost($url, $query);
  }

}
