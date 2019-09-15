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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/indicia-team/client_helpers
 */

class ElasticsearchReportHelper {

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

  public static function download($options) {
    self::checkOptions('esDownload', $options,
      ['source'],
      ['addColumns', 'removeColumns']
    );
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
    $dataOptions = helper_base::getOptionsForJs($options, [
      'source',
      'columnsTemplate',
      'addColumns',
      'removeColumns',
    ], empty($options['attachToId']));
    helper_base::$javascript .= <<<JS
$('#$options[id]').idcEsDownload({});

JS;
    return self::getControlContainer('esDownload', $options, $dataOptions, $r);
  }

  /**
   * Initialises the JavaScript required for an Elasticsearch data source.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/prebuilt-forms/dynamic-elasticsearch.html#[source]
   *
   * @return string
   *   Empty string as no HTML required.
   */
  public static function source($options) {
    self::applyReplacements($options, ['aggregation'], ['aggregation']);
    self::checkOptions(
      'source',
      $options,
      ['id'],
      ['aggregation', 'filterBoolClauses', 'buildTableXY', 'sort']
    );
    $options = array_merge([
      'aggregationMapMode' => 'geoHash',
    ], $options);
    $jsOptions = [
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
      'filterSourceField',
      'filterField',
      'filterBoundsUsingMap',
    ];
    helper_base::$indiciaData['esSources'][] = array_intersect_key($options, array_combine($jsOptions, $jsOptions));
    // A source is entirely JS driven - no HTML.
    return '';
  }

  /**
   * A panel containing buttons for record verification actions.
   *
   * @link https://indicia-docs.readthedocs.io/en/latest/site-building/iform/prebuilt-forms/dynamic-elasticsearch.html#[verificationButtons]
   *
   * @return string
   *   Panel HTML;
   */
  public static function verificationButtons($options) {
    self::checkOptions('verificationButtons', $options, ['showSelectedRow'], []);
    if (!empty($options['editPath'])) {
      $options['editPath'] = helper_base::getRootFolder(TRUE) . $options['editPath'];
    }
    if (!empty($options['viewPath'])) {
      $options['viewPath'] = helper_base::getRootFolder(TRUE) . $options['viewPath'];
    }
    $dataOptions = helper_base::getOptionsForJs($options, [
      'showSelectedRow',
      'editPath',
      'viewPath',
    ], TRUE);
    $userId = hostsite_get_user_field('indicia_user_id');
    $verifyUrl = iform_ajaxproxy_url($options['nid'], 'list_verify');
    $commentUrl = iform_ajaxproxy_url($options['nid'], 'occ-comment');
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
HTML;
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

}
