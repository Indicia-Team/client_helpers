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

/**
 *
 *
 * @todo Provide form description in this comment block.
 * @todo Rename the form class to iform_...
 */
class iform_es_download {

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_es_download_definition() {
    return array(
      'title' => 'ElasticSearch downloader',
      'category' => 'Experimental',
      'description' => 'Download from ElasticSearch. Experimental, may be subject to bugs and changes.',
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
    ];
  }

  /**
   * Retrieve the form HTML.
   */
  public static function get_form($args, $nid, $response = NULL) {
    global $indicia_templates;
    helper_base::add_resource('font_awesome');
    $r = '<form id="es-settings">';
    $r .= data_entry_helper::textarea([
      'fieldname' => 'query',
      'label' => 'Query string',
      'helpText' => 'E.g. "Lasius", "2007" or "taxon.family:Apidae". <a href="https://github.com/Indicia-Team/support_files/blob/master/Elasticsearch/document-structure.md" target="_top">Available fields...</a>',
    ]);
    $r .= str_replace(
      [
        '{id}',
        '{title}',
        '{class}',
        '{caption}',
      ], [
        'do-download',
        lang::get('Run the download'),
        "class=\"$indicia_templates[buttonHighlightedClass]\"",
        lang::get('Download'),
      ],
      $indicia_templates['button']
    );
    $r .= '</form>';
    data_entry_helper::enable_validation('es-settings');
    $progress = <<<HTML
<div class="progress-container">
  <svg>
    <circle id="circle"
            cx="-90"
            cy="90"
            r="80"
            style="stroke-dashoffset:503px;"
            stroke-dasharray="503"
            stroke-width="12px"
            stroke="#2c7fb8"
            fill="#7fcdbb"
            transform="rotate(-90)" />
      </g>
      </text>
  </svg>
  <div class="progress-text"></div>
</div>

HTML;
    $r .= str_replace(
      ['{attrs}', '{col-1}', '{col-2}'],
      ['', $progress, '<div id="files"><h2>' . lang::get('Files') . ':</h2></div>'],
      $indicia_templates['two-col-50']);
    data_entry_helper::$javascript .= 'indiciaData.ajaxUrl="' . hostsite_get_url('iform/ajax/es_download') . "\";\n";
    return $r;
  }

  /**
   * Proxy method for calls to ElasticSearch.
   *
   * Attaches authorisation to the request. Also wraps the query string in an
   * appropriate request body.
   */
  public static function ajax_proxy($website_id, $password, $nid) {
    $params = hostsite_get_node_field_value($nid, 'params');
    $postData = file_get_contents('php://input');
    $urlParams = array_merge($_GET);
    unset($urlParams['q']);
    unset($urlParams['warehouse_url']);
    $session = curl_init($_GET['warehouse_url'] . 'index.php/services/rest/' . $params['endpoint'] . '/_search?' . http_build_query($urlParams));
    $query = [
      'query' => [
        'bool' => [
          'must' => [
            'query_string' => [
              'query' => $postData,
              'analyze_wildcard' => TRUE,
              'default_field' => '*',
            ],
          ],
        ],
      ],
    ];
    if (!empty($postData)) {
      curl_setopt($session, CURLOPT_POST, 1);
      curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($query));
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
      curl_setopt($session, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);
    }
    curl_setopt($session, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json',
      "Authorization: USER:$params[user]:SECRET:$params[secret]",
    ]);
    curl_setopt($session, CURLOPT_HEADER, FALSE);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
    // Do the POST and then close the session.
    $response = curl_exec($session);
    $headers = curl_getinfo($session);
    if (array_key_exists('charset', $headers)) {
      $headers['content_type'] .= '; ' . $headers['charset'];
    }
    header('Content-type: ' . $headers['content_type']);
    echo $response;
  }

}