<?php

/**
 * @file
 * Dynamic form base class for details pages (record, species, location etc).
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

require_once 'dynamic.php';

class BaseDynamicDetails extends iform_dynamic {

  protected static function controlContainer($defaultTitle, $htmlContent, $options) {
    $r = '';
    if ($options['title'] === TRUE) {
      $title = lang::get($defaultTitle);
    }
    elseif (is_string($options['title'])) {
      $title = lang::get($options['title']);
    }
    $titleEl = isset($title) ? "<h3>$title</h3>" : '';
    $class = preg_replace('/[^a-z0-9]/', '-', strtolower($defaultTitle));
    $r .= <<<HTML
<div class="details-block details-$class">
  $titleEl
  <div class="details-block-content">
    $htmlContent
  </div>
</div>
HTML;
    return $r;
  }

  /**
   * Used to convert an array of attributes to a string formatted like a set.
   *
   * This is then used by the record_data_attributes_with_hiddens report to
   * return custom attributes which aren't in the hidden attributes list.
   *
   * @param array
   *   Attributes.
   *
   * @return string
   *   The set of hidden custom attributes.
   */
  protected static function convertArrayToSet(array $theArray) {
    return "'" . implode("','", str_replace("'", "''", $theArray)) . "'";
  }

  /**
   * Draws a common control for all photos controls.
   *
   * @param array $auth
   *   Authorisation tokens.
   * @param array $args
   *   Form configuration.
   * @param array $options
   *   Control configuration. Options include:
   *   * itemsPerPage - number of photos to show.
   *   * imageSize - e.g. thumb (default) or med.
   *   * class - wrapper class.
   *   * helpText - information shown above the images.
   *   * title - block title, or false to hide it.
   *
   * @param array $settings
   *   Configuration for the type of media being output.
   *   * type - photo type, e.g. parentsample or sample.
   *   * table - table for the media, e.g. sample_medium.
   *   * key - report parameter for filtering
   *   * value - ID value to filter to, e.g. the sample ID.
   *
   * @return string
   *   The output report grid.
   */
  protected static function getControlPhotos(array $auth, array $args, array $options, array $settings) {
    helper_base::add_resource('fancybox');
    require_once 'report.php';
    $options = array_merge([
      'itemsPerPage' => 20,
      'imageSize' => 'thumb',
      'class' => 'media-gallery',
    ], $options);
    $extraParams = $auth['read'] + [
      'sharing' => $args['sharing'],
      'limit' => $options['itemsPerPage'],
    ];

    $extraParams[$settings['key']] = $settings['value'];
    $media = data_entry_helper::get_population_data([
      'table' => $settings['table'],
      'extraParams' => $extraParams,
    ]);
    $html = "<div class=\"$options[class] photos-$settings[type]\">";
    if (empty($media)) {
      $html = '<p>' . lang::get('No photos or media files available') . '</p>';
    }
    else {
      if (isset($options['helpText'])) {
        $html .= '<p>' . $options['helpText'] . '</p>';
      }
      $html .= '<ul>';
      $firstImage = TRUE;
      foreach ($media as $medium) {
        if ($firstImage && substr($medium['media_type'], 0, 6) === 'Image:') {
          // First image can be flagged as the main content image. Used for FB
          // OpenGraph for example.
          global $iform_page_metadata;
          if (!isset($iform_page_metadata)) {
            $iform_page_metadata = [];
          }
          $imageFolder = helper_base::get_uploaded_image_folder();
          $iform_page_metadata['image'] = "$imageFolder$medium[path]";
          $firstImage = FALSE;
        }
        $html .= iform_report_get_gallery_item('sample', $medium, $options['imageSize']);
      }
      $html .= '</ul>';
    }
    $html .= '</div>';
    return self::controlContainer('Photos', $html, $options);
  }

  /**
   * A method for implementing the get_control_singleattribute output.
   *
   * Works for any entity (location, occurrence, sample etc).
   *
   * @param string
   *   Entity, e.g. location, sample, occurrence or taxa_taxon_list.
   *
   * @return string
   *   Control HTML.
   */
  protected static function getControlSingleattribute($entity, $auth, $args, $options) {
    if (empty($options["{$entity}_attribute_id"])) {
      hostsite_show_message(lang::get("A {$entity}_attribute_id option is required for the single attribute control.", 'warning'));
      return;
    }
    $options = array_merge([
      'format' => 'text',
      'ifEmpty' => 'hide',
      'outputFormatting' => FALSE,
      'title' => TRUE,
    ], $options);
    $attrData = report_helper::get_report_data([
      'readAuth' => $auth['read'],
      'dataSource' => "reports_for_prebuilt_forms/{$entity}_details/{$entity}_attribute_value",
      'extraParams' => [
        "{$entity}_id" => $_GET["{$entity}_id"],
        "{$entity}_attribute_id" => $options["{$entity}_attribute_id"],
        'sharing' => $args['sharing'],
        'language' => iform_lang_iso_639_2(hostsite_get_user_field('language')),
        'output_formatting' => $options['outputFormatting'] && $options['format'] === 'text' ? 't' : 'f',
      ],
    ]);
    if (count($attrData) === 0) {
      return '';
    }
    $html = '';
    $valueInfo = $attrData[0];
    if ($valueInfo['value'] === NULL || $valueInfo['value'] === '') {
      $html = '<p>' . lang::get($options['ifEmpty']) . '</p>';
    }
    else {
      switch ($options['format']) {
        case 'text':
          $html = "<p>$valueInfo[value]</p>";
          break;

        case 'complex_attr_grid':
          $valueRows = explode('; ', $valueInfo['value']);
          $decoded = json_decode($valueRows[0], TRUE);
          $html = '<table class="table"><thead>';
          $html .= '<tr><th>' . implode('</th><th>', array_keys($decoded)) . '</th></tr>';
          $html .= '</thead><tbody>';
          foreach ($valueRows as $valueRow) {
            $decoded = json_decode($valueRow, TRUE);
            if ($options['outputFormatting']) {
              foreach ($decoded as &$value) {
                $value = str_replace("\n", '<br/>', $value);
                $value = preg_replace('/(http[^\s]*)/', '<a href="$1">$1</a>', $value);
              }
            }
            $html .= '<tr><td>' . implode('</td><td>', $decoded) . '</td></tr>';
          }
          $html .= '</tbody>';
          $html .= '</table>';
          break;
      }
    }
    return self::controlContainer($valueInfo['caption'], $html, $options);
  }

}