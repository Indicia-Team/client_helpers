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
 * @link https://github.com/indicia-team/client_helpers
 */

/**
 * List of methods that can be used for a prebuilt form report configuration.
 */

/**
 * Return a minimal list of parameter definitions for a form that includes definition of a report.
 *
 * @return array
 *   List of parameter definitions.
 */
function iform_report_get_minimal_report_parameters() {
  return array(
    array(
      'name' => 'report_name',
      'caption' => 'Report Name',
      'description' => 'Select the report to provide the output for this page.',
      'type' => 'report_helper::report_picker',
      'group' => 'Report Settings',
    ), array(
      'name' => 'param_presets',
      'caption' => 'Preset parameter values',
      'description' => 'To provide preset values for any report parameter and avoid the user having to enter them, '.
          'enter each parameter into this box one per line. Each parameter is followed by an equals then the value, ' .
          'e.g. survey_id=6. You can use {user_id} as a value which will be replaced by the user ID from the CMS ' .
          'logged in user or {username} as a value replaces with the logged in username. If you have installed the ' .
          'Profile module then you can also use {profile_*} to refer to the value of a field in the user\'s profile ' .
          '(replace the asterisk to make the field name match the field created in the profile). Finally, use [*] to ' .
          'set a report parameter to 1 or 0 depending on whether the user has the permission, replacing * with the ' .
          'permission name. Parameters with preset values are not shown in the parameters form and therefore can\'t ' .
          'be overridden by the user.',
      'type' => 'textarea',
      'required' => FALSE,
      'group' => 'Report Settings',
    ), array(
      'name' => 'param_defaults',
      'caption' => 'Default parameter values',
      'description' => 'To provide default values for any report parameter which allow the report to run initially ' .
          'but can be overridden, enter each parameter into this box one per line. Each parameter is followed by an ' .
          'equals then the value, e.g. survey_id=6. You can use {user_id} as a value which will be replaced by the '.
          'user ID from the CMS logged in user or {username} as a value replaces with the logged in username. If ' .
          'you have installed the Profile module then you can also use {profile_*} to refer to the value of a field ' .
          'in the user\'s profile (replace the asterisk to make the field name match the field created in the profile). '.
          'Finally, use [*] to set a report parameter to 1 or 0 depending on whether the user has the permission, ' .
          'replacing * with the permission name. Unlike preset parameter values, parameters referred to by default ' .
          'parameter values are displayed in the parameters form and can therefore be changed by the user.',
      'type' => 'textarea',
      'required' => FALSE,
      'group' => 'Report Settings',
    ), array(
      'name' => 'param_ignores',
      'caption' => 'Default params to exclude from the form',
      'description' => 'Provide a list of the parameter names which are in the Default Parameter Values but should ' .
          'not appear in the parameters form. An example usage of this is to provide parameters that can be ' .
          'overridden via a URL parameter.',
      'type' => 'textarea',
      'required' => FALSE,
      'group' => 'Report Settings',
    ), array(
      'name' => 'items_per_page',
      'caption' => 'Items per page',
      'description' => 'Maximum number of rows shown on each page of the table',
      'type' => 'int',
      'default' => 20,
      'required' => TRUE,
      'group' => 'Report Settings',
    ),
  );
}

/**
 * Return a list of parameter definitions for a form that includes definition of a report.
 * @return array List of parameter definitions.
 */
function iform_report_get_report_parameters() {
  return array_merge(
    iform_report_get_minimal_report_parameters(),
    array(
      array(
        'name' => 'output',
        'caption' => 'Output Mode',
        'description' => 'Select what combination of the params form and report output will be output. This can be ' .
            'used to develop a single page with several reports linked to the same parameters form, e.g. using the ' .
            'Drupal panels module.',
        'type' => 'select',
        'required' => TRUE,
        'options' => array(
          'default' => 'Include a parameters form and output',
          'form' => 'Parameters form only - the output will be displayed elsewhere.',
          'output' => 'Output only - the params form will be output elsewhere.',
        ),
        'default' => 'default',
        'group' => 'Report Settings',
      ), array(
        'name' => 'param_lookup_extras',
        'caption' => 'Params Lookup Extras',
        'description' => 'When a report parameter is a lookup, the option allows the setting of the extras that get ' .
            'sent to the warehouse to generate the list. For example it would allow a restriction to be placed on ' .
            'the list of location type terms in a select, rather than showing the full set, some of which may not ' .
            'be appropriate in this instance. Format is paraKey:extraField=values. The values can be comma ' .
            'separated list, but when used with a report (rather than a direct table), the report must be able to ' .
            'handle it as a single string. Care should be taken with string values - depending on the report, they ' .
            'may need to be delimited by single inverted commas.',
        'type' => 'textarea',
        'required' => FALSE,
        'group' => 'Report Settings',
      ), array(
        'name' => 'report_group',
        'caption' => 'Report group',
        'description' => 'When using several reports on a single page (e.g. ' .
            '<a href="https://github.com/Indicia-Team/client_helperswiki/DrupalDashboardReporting">dashboard reporting</a>) ' .
            'you must ensure that all reports that share a set of input parameters have the same report group as ' .
            'the parameters report.',
        'type' => 'text_input',
        'default' => 'report',
        'group' => 'Report Settings',
      ), array(
        'name' => 'remember_params_report_group',
        'caption' => 'Remember report parameters group',
        'description' => 'Enter any value in this parameter to allow the report to save its parameters for the next ' .
            'time the report is loaded. The parameters are saved site wide, so if several reports share the same ' .
            'value and the same report group then the parameter settings will be shared across the reports even if ' .
            'they are on different pages of the site. This functionality requires cookies to be enabled on the ' .
            'browser.',
        'type' => 'text_input',
        'required' => FALSE,
        'default' => '',
        'group' => 'Report Settings',
      ), array(
        'name' => 'params_in_map_toolbar',
        'caption' => 'Params in map toolbar',
        'description' => 'Should the report input parameters be inserted into a map toolbar instead of displaying a ' .
            'panel of input parameters at the top? This is only useful when there is a map output onto the page ' .
            'which has a toolbar in the top or bottom position.',
        'type' => 'checkbox',
        'required' => FALSE,
        'group' => 'Report Settings',
      ), array(
        'name' => 'row_class',
        'caption' => 'Row Class',
        'description' => 'A CSS class to add to each row in the grid. Can include field value replacements in ' .
            'braces, e.g. {certainty} to construct classes from field values, e.g. to colour rows in the grid ' .
            'according to the data.',
        'type' => 'text_input',
        'default' => '',
        'required' => 'FALSE',
        'group' => 'Report Settings',
      ), array(
        'name' => 'refresh_timer',
        'caption' => 'Automatic reload seconds',
        'description' => 'Set this value to the number of seconds you want to elapse before the report will be ' .
            'automatically reloaded, useful for displaying live data updates at BioBlitzes. Combine this with Page ' ,
            'to reload to define a sequence of pages that load in turn.',
        'type' => 'int',
        'required' => FALSE,
        'group' => 'Page Refreshing',
      ), array(
        'name' => 'load_on_refresh',
        'caption' => 'Page to reload',
        'description' => 'Provide the full URL of a page to reload after the number of seconds indicated above.',
        'type' => 'string',
        'required' => FALSE,
        'group' => 'Page Refreshing',
      )
    )
  );
}

/**
 * Retrieves the options array required to set up a report.
 *
 * Uses to the report settings provided in the configuration of a prebuilt form
 * to determin the settings required for a report.

 * @param string $args
 * @param <type> $readAuth
 *
 * @return array
 *   Options for the report request.
 */
function iform_report_get_report_options($args, $readAuth) {
  // Handle auto_params_form for backwards compatibility.
  if (empty($args['output']) && !empty($args['auto_params_form'])) {
    if (!$args['auto_params_form']) {
      $args['output'] = 'output';
    }
  }
  if (isset($args['map_toolbar_pos']) && $args['map_toolbar_pos'] === 'map') {
    // Report params cannot go in the map toolbar if displayed as overlay on map.
    $args['params_in_map_toolbar'] = FALSE;
  }
  require_once 'user.php' ;
  $presets = get_options_array_with_user_data($args['param_presets']);
  $defaults = get_options_array_with_user_data($args['param_defaults']);
  $ignores = (isset($args['param_ignores']) && trim($args['param_ignores']) != '') ?
    helper_base::explode_lines($args['param_ignores']) : [];
  $param_lookup_extras = [];
  if (isset($args['param_lookup_extras'])) {
    $paramlx = helper_base::explode_lines($args['param_lookup_extras']);
    foreach ($paramlx as $param) {
      if (!empty($param)) {
        $tokens = explode(':', $param, 2);
        if (count($tokens) === 2) {
          $tokens2 = explode('=', $tokens[1], 2);
          if (count($tokens2) === 2) {
            if (!isset($param_lookup_extras[$tokens[0]])) $param_lookup_extras[$tokens[0]] = [];
            $param_lookup_extras[$tokens[0]][$tokens2[0]] = explode(',', $tokens2[1]);
          }
          else {
            throw new Exception('One of the param_lookup_extras defined for this page are not of the form key:param=value[,value...] : ' . $param . '. (No equals)');
          }
        }
        else {
          throw new Exception('One of the param_lookup_extras defined for this page are not of the form key:param=value[,value...] : ' . $param . '. (No colon)');
        }
      }
    }
  }
  else {
    $param_lookup_extras = [];
  }

  // Default columns behaviour is to just include anything returned by the report.
  $columns = [];
  // This can be overridden.
  if (isset($args['columns_config']) && !empty($args['columns_config'])) {
    $columns = json_decode($args['columns_config'], TRUE);
  }
  // Do the form arguments request that certain columns are globally skipped?
  if (!empty($args['skipped_report_columns'])) {
    // Look for configured columns that should be skipped.
    foreach ($columns as &$column) {
      if (isset($column['fieldname'])) {
        $index = array_search($column['fieldname'], $args['skipped_report_columns']);
        if ($index !== FALSE) {
          if (!array_key_exists('visible', $column)) {
            $column['visible'] = FALSE;
          }
          unset($args['skipped_report_columns'][$column['fieldname']]);
        }
      }
    }
    // Add configurations to hide any remaining columns that should be skipped.
    if (!empty($args['skipped_report_columns'])) {
      foreach ($args['skipped_report_columns'] as $fieldname) {
        $columns[] = array('fieldname' => $fieldname, 'visible' => FALSE);
      }
    }
  }
  $reportOptions = array(
    'id' => 'report-grid',
    'reportGroup' => isset($args['report_group']) ? $args['report_group'] : '',
    'rememberParamsReportGroup' => isset($args['remember_params_report_group']) ? $args['remember_params_report_group'] : '',
    'dataSource' => isset($args['report_name']) ? $args['report_name'] : '',
    'mode' => 'report',
    'readAuth' => $readAuth,
    'columns' => $columns,
    'itemsPerPage' => empty($args['items_per_page']) ? 20 : $args['items_per_page'],
    'extraParams' => $presets,
    'paramDefaults' => $defaults,
    'ignoreParams' => $ignores,
    'param_lookup_extras' => $param_lookup_extras,
    'galleryColCount' => isset($args['gallery_col_count']) ? $args['gallery_col_count'] : 1,
    'headers' => isset($args['gallery_col_count']) && $args['gallery_col_count'] > 1 ? FALSE : TRUE,
    'paramsInMapToolbar' => isset($args['params_in_map_toolbar']) ? $args['params_in_map_toolbar'] : FALSE
  );
  // Put each param control in a div, which makes it easier to layout with CSS.
  if (!isset($args['params_in_map_toolbar']) || !$args['params_in_map_toolbar']) {
    $reportOptions['paramPrefix'] = '<div id="container-{fieldname}" class="param-container">';
    $reportOptions['paramSuffix'] = '</div>';
  }
  // If in Drupal, allow the params panel to collapse.
  if (function_exists('drupal_add_js')) {
    if (function_exists('hostsite_add_library') && (!defined('DRUPAL_CORE_COMPATIBILITY') || DRUPAL_CORE_COMPATIBILITY!=='7.x')) {
      hostsite_add_library('collapse');
      $reportOptions['fieldsetClass'] = 'collapsible';
    }
  }

  if (empty($args['output']) || $args['output'] === 'default') {
    $reportOptions['autoParamsForm'] = TRUE;
  }
  elseif ($args['output'] == 'form') {
    $reportOptions['autoParamsForm'] = TRUE;
    $reportOptions['paramsOnly'] = TRUE;
  }
  else {
    $reportOptions['autoParamsForm'] = FALSE;
  }
  if (!empty($args['row_class'])) {
    $reportOptions['rowClass'] = $args['row_class'];
  }
  // Set up a page refresh for dynamic update of the report at set intervals.
  if (isset($args['refresh_timer']) && $args['refresh_timer'] !== 0 && is_numeric($args['refresh_timer'])) { // is_numeric prevents injection
    if (isset($args['load_on_refresh']) && !empty($args['load_on_refresh'])) {
      report_helper::$javascript .= "setTimeout('window.location=\"" . $args['load_on_refresh'] . "\";', " . $args['refresh_timer']."*1000 );\n";
    }
    else {
      report_helper::$javascript .= "setTimeout('window.location.reload(false);', " . $args['refresh_timer'] . "*1000 );\n";
    }
  }
  return $reportOptions;
}

/**
 * Takes a set of report parameters and applies preferences from the user's
 * EasyLogin profile to the report parameters. Assumes parameters called
 * ownData, ownLocality, ownGroups. See the library/occurrences/explore_list
 * report for an example.
 */
function iform_report_apply_explore_user_own_preferences(&$reportOptions) {
  $allParams = array_merge($reportOptions['paramDefaults'], $reportOptions['extraParams']);
  /* Unless ownData explicitly set, we either default it to unchecked, or we
    set it unchecked and hidden if the user account is not on the warehouse. */
  if (!array_key_exists('ownData', $allParams)) {
    $indicia_user_id = hostsite_get_user_field('indicia_user_id');
    if (!empty($indicia_user_id)) {
      $reportOptions['paramDefaults']['ownData'] = 0;
    }
    else {
      $reportOptions['extraParams']['ownData'] = 0;
    }
  }
  /* Unless ownLocality explicitly set, we either default it to checked, or we
    set it unchecked and hidden if the user account has no location preferences
    set. */
  if (!array_key_exists('ownLocality', $allParams)) {
    $location_id = hostsite_get_user_field('location');
    if (!empty($location_id))
      $reportOptions['paramDefaults']['ownLocality'] = 1;
    else
      $reportOptions['extraParams']['ownLocality'] = 0;
  }
  /* Unless ownGroups explicitly set, we either default it to checked, or we
    set it unchecked and hidden if the user account has no taxon groups set. */
  if (!array_key_exists('ownGroups', $allParams)) {
    $taxon_groups = hostsite_get_user_field('taxon_groups');
    if (!empty($taxon_groups))
      $reportOptions['paramDefaults']['ownGroups']=1;
    else
      $reportOptions['extraParams']['ownGroups']=0;
  }
}

/**
 * Retrieve HTML for a media file in a gallery.
 *
 * @param string $entity
 *   Root entity name (occurrence, sample, location etc).
 * @param array $medium
 *   Media file data as loaded from a *_media table's list view.
 * @param string $imageSize
 *   Output file size, e.g. thumb or med.
 *
 * @return string
 *   HTML.
 */
function iform_report_get_gallery_item($entity, array $medium, $imageSize = 'thumb') {
  $imageFolder = data_entry_helper::get_uploaded_image_folder();
  $captionItems = [];
  // Find the licence and caption info for the file.
  $info = [
    'id' => $medium['id'],
    'entity' => $entity,
    'loaded' => [
      'type' => $medium['media_type'],
      'caption' => $medium['caption'],
      'licence_code' => $medium['licence_code'],
      'licence_title' => $medium['licence_title'],
    ],
  ];
  $mediaAttr = 'data-media-info="' . htmlspecialchars(json_encode($info)) . '"';
  if (!empty($medium['caption'])) {
    $captionItems[] = $medium['caption'];
  }
  if (!empty($medium['licence_title'])) {
    $captionItems[] = "Licence is $medium[licence_title]";
  }
  elseif (!empty($medium['licence_code'])) {
    $captionItems[] = "Licence is $medium[licence_code]";
  }
  $captionAttr = count($captionItems) > 0 ? ' title="' . htmlspecialchars(implode(' | ', $captionItems)) . '"' : '';
  if ($medium['media_type'] === 'Image:Local') {
    // Standard link to Indicia image.
    return <<<HTML
<li class="gallery-item">
  <a $mediaAttr$captionAttr
      href="$imageFolder$medium[path]"
      data-fancybox="gallery" class="single">
    <img src="$imageFolder$imageSize-$medium[path]" />
  </a>
HTML;
  }
  if ($medium['media_type'] === 'Audio:Local') {
    // Output the media file content, with the info attached.
    return <<<HTML
<li class="gallery-item">
  <audio $mediaAttr$captionAttr
      controls src="$imageFolder$medium[path]" type="audio/mpeg"></audio>
</li>
HTML;
  }
  if ($medium['media_type'] === 'Image:iNaturalist') {
    $imgLarge = str_replace('/square.', '/original.', $medium['path']);
    $path = $imageSize === 'med' ? str_replace('/square.', '/medium.', $medium['path']) : $medium['path'];
    return <<<HTML
<li class="gallery-item">
  <a $mediaAttr$captionAttr
      href="$imgLarge"
      data-fancybox="gallery" class="single">
    <img src="$path" />
  </a>
</li>
HTML;
  }
  // Other local files can be displayed as a file icon.
  if (substr($medium['media_type'], -6) === ':Local') {
    helper_base::add_resource('font_awesome');
    $fileType = substr($medium['media_type'], 0, strlen($medium['media_type']) - 6);
    return <<<HTML
<li class="gallery-item">
  <a $mediaAttr$captionAttr
      href="$imageFolder$medium[path]">
    <span class="fas fa-file-invoice fa-2x"></span><br/>
    $fileType
  </a>
</li>
HTML;
  }
  // Everything else will be treated using noembed on the popup.
  // Build icon class using web domain.
  $matches = preg_match('/^http(s)?:\/\/(www\.)?([a-z]+(\.kr)?)/', $medium['path']);
  $domainClass = str_replace('.', '', $matches[3]);
  return <<<HTML
<li class="gallery-item">
  <a $mediaAttr$captionAttr
      href="$medium[path]" class="social-icon $domainClass"></a>
</li>
HTML;
}