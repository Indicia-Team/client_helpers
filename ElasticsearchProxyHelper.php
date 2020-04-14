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

  private static $config;

  private static $confidentialFilterApplied = FALSE;

  private static $releaseStatusFilterApplied = FALSE;

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
    if (empty(self::$config['es']['endpoint']) || empty(self::$config['es']['user']) || empty(self::$config['es']['secret'])) {
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
        self::proxyDownload();
        break;

      case 'mediaAndComments':
        self::proxyMediaAndComments($nid);
        break;

      case 'rawsearch':
        self::proxyRawsearch();
        break;

      case 'searchbyparams':
        self::proxySearchByParams();
        break;

      case 'updateall':
        self::proxyUpdateAll($nid);
        break;

      case 'updateids':
        self::proxyUpdateIds();
        break;

      case 'verificationQueryEmail':
        self::proxyVerificationQueryEmail();
        break;

      default:
        header("HTTP/1.1 404 Not found");
        echo json_encode(['error' => 'Method not found']);
    }
  }

  /**
   * Returns the URL required to call the Elasticsearch service.
   *
   * @return string
   *   URL.
   */
  private static function getEsUrl() {
    return self::$config['indicia']['base_url'] . 'index.php/services/rest/' . self::$config['es']['endpoint'];
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
    // Organise some attributes by system function, so we can make output consistent.
    $sysFuncAttrs = [];
    $sysFuncList = [
      'Additional occurrence' => ['certainty', 'sex_stage_count', 'sex', 'stage', 'sex_stage'],
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

  private static function proxySearchByParams() {
    self::checkPermissionsFilter($_POST);
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
  private static function proxyDownload() {
    $isScrollToNextPage = array_key_exists('scroll_id', $_GET);
    if (!$isScrollToNextPage) {
      self::checkPermissionsFilter($_POST);
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
   * injected into the HTML. Returns an array with a media entry and a comments
   * entry, both containing the required HTML.
   *
   * @return array
   *   Media and comments information.
   */
  private static function proxyMediaAndComments($nid) {
    iform_load_helpers(['VerificationHelper']);
    $conn = iform_get_connection_details($nid);
    $readAuth = helper_base::get_read_auth($conn['website_id'], $conn['password']);
    $params = array_merge(['sharing' => 'verification'], hostsite_get_node_field_value($nid, 'params'));
    header('Content-type: application/json');
    echo json_encode(array(
      'media' => VerificationHelper::getMedia($readAuth, $params, $_GET['occurrence_id'], $_GET['sample_id']),
      'comments' => VerificationHelper::getComments($readAuth, $params, $_GET['occurrence_id'], TRUE),
    ));
  }

  /**
   * Proxy method that receives a list of IDs to perform updates on in Elastic.
   *
   * Used by the verification system when in checklist mode to allow setting a
   * comment and status on multiple records in one go.
   */
  private static function proxyUpdateIds() {
    if (empty(self::$config['es']['warehouse_prefix'])) {
      header("HTTP/1.1 405 Method not allowed");
      echo json_encode(['error' => 'Method not allowed as server configuration incomplete']);
      throw new ElasticsearchProxyAbort('Configuration incomplete');
    }
    $statuses = isset($_POST['doc']['identification']) ? $_POST['doc']['identification'] : [];
    echo self::internalUpdateIds($_POST['ids'], $statuses,
      isset($_POST['doc']['metadata']['website']['id']) ? $_POST['doc']['metadata']['website']['id'] : NULL);
  }

  /**
   * Proxy method to apply verification change to entire results set.
   *
   * Uses a filter definition passed in the post to retrieve the records from
   * ES then applies the decision to all aof them.
   */
  private static function proxyUpdateAll($nid) {
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
    self::checkPermissionsFilter($_POST['occurrence:idsFromElasticFilter']);
    $url = self::getEsUrl() . '/_search';
    $query = self::buildEsQueryFromRequest($_POST['occurrence:idsFromElasticFilter']);
    // Limit response for efficiency.
    $_GET['filter_path'] = 'hits.hits._source.id';
    // Maximum 10000.
    $query['size'] = 10000;
    $esResponse = json_decode(self::curlPost($url, $query));
    $ids = [];
    foreach ($esResponse->hits->hits as $item) {
      $ids[] = $item->_source->id;
    }
    $statuses = [
      'verification_status' => $_POST['occurrence:record_status'],
      'verification_substatus' => empty($_POST['occurrence:record_substatus']) ? 0 : $_POST['occurrence:record_substatus'],
    ];
    self::internalUpdateIds($ids, $statuses, NULL);
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
    $emailBody = str_replace("\n", "<br/>", $emailBody);
    // Send email. Depends upon settings in php.ini being correct.
    $success = mail($_POST['to'],
         $_POST['subject'],
         wordwrap($emailBody, 70),
         $headers);
    echo $success ? 'OK' : 'Fail';
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
   * Apply verification result to a list of IDs.
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
  private static function internalUpdateIds(array $ids, array $statuses, $websiteIdToModify) {
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
    $sensitive_Ids = [];
    // Convert Indicia IDs to the document _ids for ES. Also make a 2nd version
    // for full precision copies of sensitive records.
    foreach ($ids as $id) {
      $_ids[] = self::$config['es']['warehouse_prefix'] . $id;
      $sensitive_Ids[] = self::$config['es']['warehouse_prefix'] . "$id!";
    }
    $doc = [
      'script' => [
        'source' => implode("; ", $scripts),
        'lang' => 'painless',
      ],
      'query' => [
        'terms' => [
          '_id' => $sensitive_Ids,
        ],
      ],
    ];
    // Update index immediately and overwrite update conflicts.
    // Sensitive records first.
    $r1 = self::curlPost($url, $doc, ['refresh' => 'true', 'conflicts' => 'proceed']);
    $r1js = json_decode($r1);
    // Now normal records/blurred records.
    $doc['query']['terms']['_id'] = $_ids;
    $r2 = self::curlPost($url, $doc, ['refresh' => 'true', 'conflicts' => 'proceed']);
    $r2js = json_decode($r2);
    // Since the verification alias can only see 1 copy of each record (e.g.
    // full precision), combine the totals to report the total records changed.
    return json_encode(['updated' => $r1js->updated + $r2js->updated]);
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
      'occurrence:record_decision_source' => 'H',
      'occurrence:record_status' => $_POST['occurrence:record_status'],
      'occurrence:ids' => implode(',', $ids),
    ];
    if (!empty($_POST['occurrence_comment:comment'])) {
      $data['occurrence_comment:comment'] = $_POST['occurrence_comment:comment'];
    }
    if (!empty($_POST['occurrence:record_substatus'])) {
      $data['occurrence:record_substatus'] = $_POST['occurrence:record_substatus'];
    }
    $conn = iform_get_connection_details($nid);
    $auth = helper_base::get_read_write_auth($conn['website_id'], $conn['password']);
    $request = helper_base::$base_url . "index.php/services/data_utils/list_verify";
    $postargs = helper_base::array_to_query_string(array_merge($data, $auth['write_tokens']), TRUE);
    $response = helper_base::http_post($request, $postargs);
    if ($response['output'] !== 'OK') {
      throw new exception($response['output']);
    }
  }

  /**
   * A simple wrapper for the cUrl functionality to POST to Elastic.
   */
  private static function curlPost($url, $data, $getParams = []) {
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
    curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($session, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json',
      'Authorization: USER:' . self::$config['es']['user'] . ':SECRET:' . self::$config['es']['secret'],
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
    return $response;
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
   */
  private static function checkPermissionsFilter(array $post) {
    $permissionsFilter = empty($post['permissions_filter']) ? 'all' : $post['permissions_filter'];
    $validPermissionsFilter = [
      'all',
      'my',
      'location_collation',
    ];
    if (!in_array($permissionsFilter, $validPermissionsFilter)
        || !hostsite_user_has_permission(self::$config['es'][$permissionsFilter . '_records_permission'])) {
      header("HTTP/1.1 401 Unauthorised");
      echo json_encode(['error' => "User does not have permission to $permissionsFilter records"]);
      throw new ElasticsearchProxyAbort('Unauthorised');
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
    $fieldQueryTypes = [
      'term',
      'match',
      'match_phrase',
      'match_phrase_prefix',
      'exists',
    ];
    $arrayFieldQueryTypes = ['terms'];
    $stringQueryTypes = ['query_string', 'simple_query_string'];
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
      if (!empty($qryConfig['query'])) {
        $queryDef = json_decode(
          str_replace('#value#', $qryConfig['value'], $qryConfig['query']), TRUE
        );
      }
      elseif (in_array($qryConfig['query_type'], $basicQueryTypes)) {
        $queryDef = [$qryConfig['query_type'] => new stdClass()];
      }
      elseif (in_array($qryConfig['query_type'], $fieldQueryTypes)) {
        // One of the standard ES field based query types (e.g. term or match).
        $queryDef = [$qryConfig['query_type'] => [$qryConfig['field'] => $qryConfig['value']]];
      }
      elseif (in_array($qryConfig['query_type'], $arrayFieldQueryTypes)) {
        // One of the standard ES field based query types (e.g. term or match).
        $queryDef = [$qryConfig['query_type'] => [$qryConfig['field'] => json_decode($qryConfig['value'], TRUE)]];
      }
      elseif (in_array($qryConfig['query_type'], $stringQueryTypes)) {
        // One of the ES query string based query types.
        $queryDef = [$qryConfig['query_type'] => ['query' => $qryConfig['value']]];
      }
      if (!empty($qryConfig['nested'])) {
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
    self::applyPermissionsFilter($post, $bool);
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

    $bool = array_filter($bool, function ($k) {
      return count($k) > 0;
    });
    if (!empty($bool)) {
      $query['query'] = ['bool' => $bool];
    }
    return $query;
  }

  private static function applyPermissionsFilter($post, array &$bool) {
    if (!empty($post['permissions_filter'])) {
      switch ($post['permissions_filter']) {
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
      $filterData = data_entry_helper::get_report_data([
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
    self::applyUserFiltersTaxonRankSortOrder($definition, $bool);
    self::applyUserFiltersTaxonMarineFlag($definition, $bool);
    self::applyUserFiltersSearchArea($definition, $bool);
    self::applyUserFiltersLocationName($definition, $bool);
    self::applyUserFiltersIndexedLocationList($definition, $bool);
    self::applyUserFiltersIndexedLocationTypeList($definition, $bool, $readAuth);
    self::applyUserFiltersDate($definition, $bool);
    self::applyUserFiltersWho($definition, $bool);
    self::applyUserFiltersOccId($definition, $bool);
    self::applyUserFiltersOccExternalKey($definition, $bool);
    self::applyUserFiltersQuality($definition, $bool);
    self::applyUserFiltersAutoChecks($definition, $bool);
    self::applyUserFiltersHasPhotos($readAuth, $definition, ['has_photos'], $bool);
    self::applyUserFiltersWebsiteList($definition, $bool);
    self::applyUserFiltersSurveyList($definition, $bool);
    self::applyUserFiltersInputFormList($definition, $bool);
    self::applyUserFiltersGroupId($definition, $bool);
    self::applyUserFiltersAccessRestrictions($definition, $bool);
  }

  /**
   * If there is a location_list in the filter, convert to searchArea.
   *
   * This requires fetching the combined boundaries from the warehouse,
   * transformed to EPSG:4326.
   */
  private static function convertLocationListToSearchArea(array &$definition, array $readAuth) {
    $filter = self::getDefinitionFilter($definition, ['location_list', 'location_ids']);
    if (!empty($filter)) {
      $boundaryData = data_entry_helper::get_report_data([
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
    $filter = self::getDefinitionFilter($definition, ['taxon_group_list', 'taxon_group_id']);
    if (!empty($filter)) {
      $bool['must'][] = [
        'terms' => ['taxon.group_id' => explode(',', $filter['value'])],
      ];
    }
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
      // Convert the IDs to external keys, stored in ES as taxon_ids.
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
   * Converts a filter definition marine flag filter to an ES query.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersTaxonMarineFlag(array $definition, array &$bool) {
    $filter = self::getDefinitionFilter($definition, ['marine_flag']);
    // Filter op can be =, >= or <=.
    if (!empty($filter) && $filter['value'] !== 'all') {
      $bool['must'][] = [
        'match' => [
          'taxon.marine' => $filter['value'] === 'Y',
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
      $typeRows = data_entry_helper::get_population_data([
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
   * Support for recorded, input, edited, verified dates. Age is supported as
   * long as format specifies age in minutes, hours, days, weeks, months or
   * years.
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
    $dateTypes = [
      'from' => 'gte',
      'to' => 'lte',
      'age' => 'gte',
    ];
    if (!empty($definition['date_type'])) {
      foreach ($dateTypes as $type => $op) {
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
                $op => $value,
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
          $bool['must'][] = ['terms' => ['identification.recorder_certainty.keyword' => ['Certain', 'Likely']]];
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
          $bool['must_not'][] = ['match' => ['identification.verification_status' => 'R']];
          $bool['must_not'][] = ['terms' => ['identification.query.keyword' => ['Q', 'A']]];
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
   * Converts an Indicia filter definition auto checks filter to an ES query.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersAutoChecks(array $definition, array &$bool) {
    $filter = self::getDefinitionFilter($definition, ['autochecks']);
    if (!empty($filter) && in_array($filter['value'], ['P', 'F'])) {
      $bool['must'][] = ['match' => ['identification.auto_checks.result' => $filter['value'] === 'P']];
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
    $filter = self::getDefinitionFilter($definition, ['website_list', 'website_id']);
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
    $filter = self::getDefinitionFilter($definition, ['survey_list', 'survey_id']);
    if (!empty($filter)) {
      $boolClause = !empty($filter['op']) && $filter['op'] === 'not in' ? 'must_not' : 'must';
      $bool[$boolClause][] = [
        'terms' => ['metadata.survey.id' => explode(',', $filter['value'])],
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
          'metadata.input_form.keyword' => explode(',', str_replace("'", '', $filter['value'])),
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

}
