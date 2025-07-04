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
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/Indicia-Team/client_helpers
 */

require_once 'includes/dynamic.php';

use IForm\prebuilt_forms\PageType;

/**
 * A prebuilt form for dynamically construction Elasticsearch content.
 *
 * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/prebuilt-forms/dynamic-elasticsearch.html
 */
class iform_dynamic_elasticsearch extends iform_dynamic {

  /**
   * Return the page metadata.
   *
   * @return array
   *   Form metadata.
   */
  public static function get_dynamic_elasticsearch_definition() {
    $description = <<<HTML
<p>Provides a dynamically output page which links to an index of occurrence data in an <a href="https://www.elastic.co">
Elasticsearch</a> cluster.</p>
<p>This page can generate controls for the following:</p>
<ul>
  <li>filtering</li>
  <li>downloading</li>
  <li>tabulating</li>
  <li>charting</li>
  <li>mapping</li>
  <li>verification</li>
</ul>
<p>Note that although this page supports linking to groups, you should build in any appropriate filtering manually rather
than assume the Elasticsearch requests will automatically filter to the viewed group.</p>
HTML;
    return [
      'title' => 'Elasticsearch outputs (customisable)',
      'category' => 'Experimental',
      'description' => $description,
      'helpLink' => 'https://indicia-docs.readthedocs.io/en/latest/site-building/iform/prebuilt-forms/dynamic-elasticsearch.html',
      'supportsGroups' => TRUE,
    ];
  }

  /**
   * {@inheritDoc}
   */
  public static function getPageType(): PageType {
    return PageType::Report;
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
        'options' => [
          'tabs' => 'Tabs',
          'wizard' => 'Wizard',
          'one_page' => 'All one page',
        ],
        'group' => 'User interface',
        'default' => 'tabs',
      ],
      [
        'name' => 'structure',
        'caption' => 'Form structure',
        'helpLink' => 'https://indicia-docs.readthedocs.io/en/latest/site-building/iform/prebuilt-forms/dynamic-elasticsearch.html#user-interface',
        'type' => 'textarea',
        'group' => 'User interface',
        'default' => '',
      ],
      [
        'name' => 'auth_method',
        'caption' => 'Elasticsearch authentication method',
        'description' => 'Authentication approach used to connect to the Elasticsearch warehouse proxy.',
        'type' => 'select',
        'options' => [
          'directClient' => 'Authenticate as a client configured in the Warehouse REST API',
          'directWebsite' => 'Authenticate as a website registered on the Warehouse',
          'jwtUser' => 'Authenticate as the logged in user using Java Web Tokens',
        ],
        'blankText' => '- Use site-wide configuration -',
        'group' => 'Elasticsearch settings',
      ],
      [
        'name' => 'endpoint',
        'caption' => 'Endpoint',
        'description' => 'Elasticsearch endpoint declared in the REST API. Alternatively, leave this blank to use the site wide setting on the Indicia configuration settings page.',
        'type' => 'text_input',
        'group' => 'Elasticsearch settings',
        'required' => FALSE,
      ],
      [
        'name' => 'alternative_endpoints',
        'caption' => 'Alternative endpoints',
        'description' => 'If this page allows sources to access any alternative Elasticsearch endpoints (e.g. for samples data), then specify the endpoints here, one per line.',
        'type' => 'textarea',
        'group' => 'Elasticsearch settings',
        'required' => FALSE,
      ],
      [
        'name' => 'scope',
        'caption' => 'Data scope (for identifying which websites will share records)',
        'description' => 'Scope or sharing mode. Not used when authenticating as a client configured in the Warehouse REST API.',
        'type' => 'select',
        'options' => [
          'reporting' => lang::get('Reporting'),
          'verification' => lang::get('Verification'),
          'data_flow' => lang::get('Data-flow'),
          'moderation' => lang::get('Moderation'),
          'peer_review' => lang::get('Peer review'),
          'user' => lang::get('My records'),
        ],
        'required' => TRUE,
        'group' => 'Elasticsearch settings',
      ],
      [
        'name' => 'user',
        'caption' => 'User',
        'description' => 'REST API client with Elasticsearch access when authentication as a client configured in the Warehouse REST API. Alternatively, leave this blank to use the site wide setting on the Indicia configuration settings page.',
        'type' => 'text_input',
        'group' => 'Elasticsearch settings',
        'required' => FALSE,
      ],
      [
        'name' => 'secret',
        'caption' => 'Secret',
        'description' => 'REST API user secret when authentication as a client configured in the Warehouse REST API. Alternatively, leave this blank to use the site wide setting on the Indicia configuration settings page.',
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
        'required' => FALSE,
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
        'default' => 'access iform content',
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
   *
   * @return string
   *   Page HTML.
   */
  public static function get_form($args, $nid) {
    iform_load_helpers(['ElasticsearchReportHelper']);
    $enabled = ElasticsearchReportHelper::enableElasticsearchProxy($nid);
    if ($enabled) {
      return parent::get_form($args, $nid);
    }
    global $indicia_templates;
    return str_replace('{message}', lang::get('This page cannot be accessed due to the server being unavailable.'), $indicia_templates['warningBox']);
  }

  /**
   * Initialises the JavaScript required for an Elasticsearch data source.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-source
   *
   * @return string
   *   Empty string as no HTML required.
   */
  protected static function get_control_source($auth, $args, $tabalias, $options) {
    return ElasticsearchReportHelper::source($options);
  }

  /**
   * Output a selector for a user's registered filters.
   *
   * @return string
   *   Select HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-userfilters
   */
  protected static function get_control_userFilters($auth, $args, $tabalias, $options) {
    return ElasticsearchReportHelper::userFilters(array_merge($options, [
      'readAuth' => $auth['read'],
    ]));
  }

  /**
   * Output a selector for filtering on survey.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-surveyfilter
   */
  protected static function get_control_surveyFilter($auth, $args, $tabalias, $options) {
    return ElasticsearchReportHelper::surveyFilter(array_merge($options, [
      'readAuth' => $auth['read'],
    ]));
  }

  /**
   * Output a selector for various high level permissions filtering options.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-permissionfilters
   */
  protected static function get_control_permissionFilters($auth, $args, $tabalias, $options) {
    return ElasticsearchReportHelper::permissionFilters(array_merge($options, [
      'readAuth' => $auth['read'],
      'my_records_permission' => $args['my_records_permission'],
      'all_records_permission' => $args['all_records_permission'],
      'location_collation_records_permission' => $args['location_collation_records_permission'],
    ]));
  }

  /**
   * Output simple summary of currently defined filters.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-filtersummary
   */
  protected static function get_control_filterSummary($auth, $args, $tabalias, $options) {
    return ElasticsearchReportHelper::filterSummary(array_merge($options, [
      'readAuth' => $auth['read'],
    ]));
  }

  /**
   * Output a selector for records with or without media.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-mediafilter
   */
  protected static function get_control_mediaFilter($auth, $args, $tabalias, $options) {
    return ElasticsearchReportHelper::mediaFilter(array_merge($options, [
      'readAuth' => $auth['read'],
    ]));
  }

  /**
   * Output a selector for record status.
   *
   * Output a selector for a general record access contexts based on permission
   * filters and group permissions etc.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-statusfilters
   */
  protected static function get_control_statusFilters($auth, $args, $tabalias, $options) {
    return ElasticsearchReportHelper::statusFilters(array_merge($options, [
      'readAuth' => $auth['read'],
    ]));
  }

  /**
   * A button that displays a form for performing bulk edit operations.
   *
   * Currently this feature is experimental and subject to change.
   *
   * @return string
   *   HTML for the container element.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-bulkeditor
   */
  protected static function get_control_bulkEditor($auth, $args, $tabalias, $options) {
    return ElasticsearchReportHelper::bulkEditor($options);
  }

  /**
   * A control for outputting a gallery of record cards.
   *
   * @return string
   *   HTML for the container element.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-cardgallery
   */
  protected static function get_control_cardGallery($auth, $args, $tabalias, $options) {
    return ElasticsearchReportHelper::cardGallery($options);
  }

  /**
   * A control for managing layout, e.g. for verification pages.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-controllayout
   */
  protected static function get_control_controlLayout($auth, $args, $tabalias, $options) {
    ElasticsearchReportHelper::controlLayout($options);
    return '';
  }

  /**
   * A control for flexibly outputting data formatted using a JS function.
   *
   * @return string
   *   HTML for the container element.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-customscript
   */
  protected static function get_control_customScript($auth, $args, $tabalias, $options) {
    return ElasticsearchReportHelper::customScript($options);
  }

  /**
   * A button for downloading the ES data from a source.
   *
   * @return string
   *   HTML for download button and progress display.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-download
   */
  protected static function get_control_download($auth, $args, $tabalias, $options) {
    return ElasticsearchReportHelper::download($options);
  }

  /**
   * An Elasticsearch or Indicia powered grid control.
   *
   * @return string
   *   Report container HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-datagrid
   */
  protected static function get_control_dataGrid($auth, $args, $tabalias, $options) {
    return ElasticsearchReportHelper::dataGrid($options);
  }

  /**
   * A scale for showing relationship between square opacity and record count.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-gridgquareopacityscale
   *
   * @return string
   *   Control HTML
   */
  protected static function get_control_gridSquareOpacityScale($auth, $args, $tabalias, $options) {
    return ElasticsearchReportHelper::gridSquareOpacityScale($options);
  }

  /**
   * Integrates the page with groups (activities).
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-groupintegration
   *
   * @return string
   *   Control HTML
   */
  protected static function get_control_groupIntegration($auth, $args, $tabalias, $options) {
    return ElasticsearchReportHelper::groupIntegration(array_merge($options, [
      'readAuth' => $auth['read'],
    ]));
  }

  /**
   * A select box for choosing from a list of higher geography boundaries.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-highergeographyselect
   *
   * @return string
   *   Control HTML
   */
  protected static function get_control_higherGeographySelect($auth, $args, $tabalias, $options) {
    return ElasticsearchReportHelper::higherGeographySelect(array_merge($options, [
      'readAuth' => $auth['read'],
    ]));
  }

  /**
   * A select box for choosing from a list of unindexed location boundaries.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-locationselect
   *
   * @return string
   *   Control HTML
   */
  protected static function get_control_locationSelect($auth, $args, $tabalias, $options) {
    return ElasticsearchReportHelper::locationSelect(array_merge($options, [
      'readAuth' => $auth['read'],
    ]));
  }

  /**
   * An Elasticsearch or Indicia powered map control.
   *
   * @deprecated Use leafletMap instead.
   *
   * @return string
   *   Map container HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-leafletmap
   */
  protected static function get_control_map($auth, $args, $tabalias, $options) {
    return self::get_control_leafletMap($auth, $args, $tabalias, $options);
  }

  /**
   * An Elasticsearch or Indicia data powered Leaflet map control.
   *
   * @return string
   *   Map container HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-leafletmap
   */
  protected static function get_control_leafletMap($auth, $args, $tabalias, $options) {
    $options = array_merge([
      'tools' => [
        'baseLayers',
        'overlayLayers',
        'dataLayerOpacity',
        'gridSquareSize',
        'queryLimitTo1kmOrBetter',
      ],
    ], $options);
    return ElasticsearchReportHelper::leafletMap($options);
  }

  /**
   * A tabbed control to show full record details and verification info.
   *
   * @return string
   *   Panel container HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-recorddetails
   */
  protected static function get_control_recordDetails($auth, $args, $tabalias, $options) {
    return ElasticsearchReportHelper::recordDetails(array_merge($options, [
      'readAuth' => $auth['read'],
    ]));
  }

  /**
   * A button for moving records from one website to another.
   *
   * @return string
   *   Panel container HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-recordsmover
   */
  protected static function get_control_recordsMover($auth, $args, $tabalias, $options) {
    return ElasticsearchReportHelper::recordsMover($options);
  }

  /**
   * A control for running custom verification rulesesets.
   *
   * @return string
   *   HTML for the container element.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-runcustomverificationrulesets
   */
  protected static function get_control_runCustomVerificationRulesets($auth, $args, $tabalias, $options) {
    return ElasticsearchReportHelper::runCustomVerificationRulesets(array_merge($options, [
      'readAuth' => $auth['read'],
    ]));
  }

  /**
   * A standard parameters filter toolbar for use on Elasticsearch pages.
   *
   * @return string
   *   Params toolbar HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-standardparams
   */
  protected static function get_control_standardParams($auth, $args, $tabalias, $options) {
    return ElasticsearchReportHelper::standardParams(array_merge($options, [
      'readAuth' => $auth['read'],
    ]));
  }

  /**
   * A control for flexibly outputting data formatted using HTML templates.
   *
   * @return string
   *   Report container HTML.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-templatedoutput
   */
  protected static function get_control_templatedOutput($auth, $args, $tabalias, $options) {
    return ElasticsearchReportHelper::templatedOutput($options);
  }

  /**
   * A panel containing buttons for record verification actions.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-verificationbuttons
   *
   * @return string
   *   Panel container HTML;
   */
  protected static function get_control_verificationButtons($auth, $args, $tabalias, $options) {
    return ElasticsearchReportHelper::verificationButtons(array_merge($options, [
      'readAuth' => $auth['read'],
    ]));
  }

  /**
   * Retrieve parameters from the URL and add to the ES requests.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/helpers/elasticsearch-report-helper.html#elasticsearchreporthelper-urlparams
   *
   * @return string
   *   Hidden input HTML which defines the appropriate filters.
   */
  protected static function get_control_urlParams($auth, $args, $tabalias, $options) {
    return ElasticsearchReportHelper::urlParams(array_merge($options, [
      'readAuth' => $auth['read'],
    ]));
  }

}
