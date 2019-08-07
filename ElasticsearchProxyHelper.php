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

class ElasticsearchProxyAbort extends Exception {
}

class ElasticsearchProxyHelper {

  private static $config;

  private static $esMappings;

  public static function enableElasticsearchProxy($nid = NULL) {
    self::$config = hostsite_get_es_config($nid);
    helper_base::add_resource('datacomponents');
    // Retrieve the Elasticsearch mappings.
    self::getMappings();
    // Prepare the stuff we need to pass to the JavaScript.
    $mappings = json_encode(self::$esMappings);
    $dateFormat = helper_base::$date_format;
    $rootFolder = helper_base::getRootFolder(TRUE);
    $esProxyAjaxUrl = hostsite_get_url('iform/esproxy');
    helper_base::$javascript .= <<<JS
indiciaData.esProxyAjaxUrl = '$esProxyAjaxUrl';
indiciaData.esSources = [];
indiciaData.esMappings = $mappings;
indiciaData.dateFormat = '$dateFormat';
indiciaData.rootFolder = '$rootFolder';

JS;
  }

  public static function callMethod($method, $nid) {
    self::$config = hostsite_get_es_config($nid);
    if (empty(self::$config['es']['endpoint']) || empty(self::$config['es']['user']) || empty(self::$config['es']['secret'])) {
      header("HTTP/1.1 405 Method not allowed");
      echo json_encode(['error' => 'Method not allowed as server configuration incomplete']);
      return;
    }

    switch ($method) {
      case 'searchbyparams':
        self::proxySearchByParams();
        break;

      case 'rawsearch':
        self::proxyRawsearch();
        break;

      case 'download':
        self::proxyDownload();
        break;

      case 'updateids':
        self::proxyUpdateIds();
        break;

      default:
        header("HTTP/1.1 404 Not found");
        echo json_encode(['error' => 'Method not found']);
    }
  }

  private static function getEsUrl() {
    return self::$config['indicia']['base_url'] . 'index.php/services/rest/' . self::$config['es']['endpoint'];
  }

  private static function proxySearchByParams() {
    self::checkPermissionsFilter(self::$config['es']);
    $url = self::getEsUrl() . '/_search';
    $query = self::buildEsQueryFromRequest();
    self::curlPost($url, $query);
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
  public static function proxyRawsearch() {
    $url = self::getEsUrl() . '/_search';
    $query = array_merge($_POST);
    $query['size'] = 0;
    self::curlPost($url, $query);
  }

  /**
   * A search proxy that handles build of a CSV download file.
   */
  public static function proxyDownload() {
    $isScrollToNextPage = array_key_exists('scroll_id', $_POST);
    if (!$isScrollToNextPage) {
      self::checkPermissionsFilter();
    }
    $url = self::getEsUrl() . '/_search?format=csv';
    $query = self::buildEsQueryFromRequest();
    if ($isScrollToNextPage) {
      $url .= '&scroll_id=' . $_POST['scroll_id'];
    }
    else {
      $url .= '&scroll';
    }
    self::curlPost($url, $query);
  }

  /**
   * Proxy method that receives a list of IDs to perform updates on in Elastic.
   *
   * Used by the verification system when in checklist mode to allow setting a
   * comment and status on multiple records in one go.
   */
  public static function proxyUpdateIds() {
    if (empty(self::$config['es']['warehouse_prefix'])) {
      header("HTTP/1.1 405 Method not allowed");
      echo json_encode(['error' => 'Method not allowed as server configuration incomplete']);
      return;
    }
    $url = self::getEsUrl() . "/_update_by_query";
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
    if (isset($_POST['doc']['metadata']['website']['id'])) {
      $scripts[] = "ctx._source.metadata.website.id = '" . $_POST['doc']['metadata']['website']['id'] . "'";
    }
    if (empty($scripts)) {
      throw new exception('Unsupported field for update. ' . var_export($_POST['doc'], true));
    }
    $_ids = [];
    // Convert Indicia IDs to the document _ids for ES.
    foreach ($_POST['ids'] as $id) {
      $_ids[] = self::$config['es']['warehouse_prefix'] . $id;
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
    self::curlPost($url, $doc);
  }

  /**
   * A simple wrapper for the cUrl functionality to POST to Elastic.
   */
  private static function curlPost($url, $data) {
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
    echo $response;
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
  private static function checkPermissionsFilter() {
    $permissionsFilter = empty($_POST['permissions_filter']) ? 'all' : $_POST['permissions_filter'];
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

  private static function buildEsQueryFromRequest() {
    $query = array_merge([
      'bool_queries' => [],
    ], $_POST);
    $bool = [
      'must' => [],
      'should' => [],
      'must_not' => [],
      'filter' => [],
    ];
    $nested = [
      'occurrence.media',
    ];
    $basicQueryTypes = ['match_all', 'match_none'];
    $fieldQueryTypes = ['term', 'match', 'match_phrase', 'match_phrase_prefix'];
    $arrayFieldQueryTypes = ['terms'];
    $stringQueryTypes = ['query_string', 'simple_query_string'];
    if (isset($query['textFilters'])) {
      // Apply any filter row parameters to the query.
      foreach ($query['textFilters'] as $field => $value) {
        // Exclamation mark reverses logic.
        $logic = substr($value, 0, 1) === '!' ? 'must_not' : 'must';
        $value = preg_replace('/^!/', '', $value);
        $bool[$logic][] = ['match_phrase_prefix' => [$field => $value]];
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
   * * idlist
   * * occ_id
   * * occurrence_external_key
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
      $filterData = data_entry_helper::get_report_data([
        'dataSource' => '/library/filters/filter_with_transformed_searcharea',
        'extraParams' => [
          'filter_id' => $userFilter,
        ],
        'readAuth' => $readAuth,
      ]);
      if (count($filterData) === 0) {
        throw new exception("Filter with ID $userFilter could not be loaded.");
      }
      $definition = json_decode($filterData[0]['definition'], TRUE);
      self::applyUserFiltersOccId($definition, $bool);
      self::applyUserFiltersOccExternalKey($definition, $bool);
      self::applyUserFiltersSearchArea($filterData[0]['search_area'], $bool);
      self::applyUserFiltersLocationName($definition, $bool);
      self::applyUserFiltersLocationList($definition, $bool);
      self::applyUserFiltersIndexedLocationList($definition, $bool);
      self::applyUserFiltersIndexedLocationTypeList($definition, $bool, $readAuth);
      self::applyUserFiltersWebsiteList($definition, $bool);
      self::applyUserFiltersSurveyList($definition, $bool);
      self::applyUserFiltersGroupId($definition, $bool);
      self::applyUserFiltersTaxonGroupList($definition, $bool);
      self::applyUserFiltersTaxaTaxonList($definition, $bool, $readAuth);
      self::applyUserFiltersTaxonRankSortOrder($definition, $bool);
      self::applyUserFiltersHasPhotos($readAuth, $definition, ['has_photos'], $bool);
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
    $filter = self::getDefinitionFilter($definition, ['idlist', 'oc_id']);
    $boolClause = !empty($filter['op']) && $filter['op'] === 'not in' ? 'must_not' : 'must';
    if (!empty($filter)) {
      $bool[$boolClause][] = [
        'terms' => ['id' => explode(',', $filter['value'])],
      ];
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
   * Converts an Indicia filter definition location_name to an ES query.
   *
   * @param string $searchArea
   *   WKT for the searchArea in EPSG:4326.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersSearchArea($searchArea, array &$bool) {
    if (!empty($searchArea)) {
      $bool['must'][] = [
        'geo_shape' => [
          'location.geom' => [
            'shape' => $searchArea,
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
   * Converts an Indicia filter definition location_list to an ES query.
   *
   * @param array $definition
   *   Definition loaded for the Indicia filter.
   * @param array $bool
   *   Bool clauses that filters can be added to (e.g. $bool['must']).
   */
  private static function applyUserFiltersLocationList(array $definition, array &$bool) {
    $filter = self::getDefinitionFilter($definition, [
      'location_list',
      'location_id',
    ]);
    if (!empty($filter)) {
      $boolClause = !empty($filter['op']) && $filter['op'] === 'not in' ? 'must_not' : 'must';
      $bool[$boolClause][] = ['terms' => ['location.location_ids' => $filter['value']]];
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
      $bool[$boolClause][] = ['exists' => ['field' => 'occurrence.media']];
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
  private static function getMappings() {
    $url = self::getEsUrl() . '/_mapping/doc';
    $session = curl_init($url);
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

}