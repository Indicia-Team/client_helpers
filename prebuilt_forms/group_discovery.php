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
 * @link https://github.com/indicia-team/client_helpers
 */

/**
 * A report page for assisting users in discovering relevant recording groups.
 */
class iform_group_discovery {

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_group_discovery_definition() {
    return [
      'title' => 'Group discovery page',
      'category' => 'Recording groups',
      'description' => 'A report page for assisting users in discovering relevant recording groups. Requires the Group Landing Pages Drupal module.',
      'recommended' => TRUE,
    ];
  }

  public static function get_parameters() {
    return [
      [
        'name' => 'group_home_path',
        'caption' => 'Path to the group home page',
        'description' => 'Path to the Drupal page which hosts group home pages.',
        'type' => 'text_input',
        'required' => FALSE,
      ],
      [
        'name' => 'default_group_label_plural',
        'caption' => 'Default group label (plural)',
        'description' => 'What should a group be referred to as? E.g. project, activity etc.',
        'type' => 'text_input',
        'default' => 'groups',
      ],
      [
        'name' => 'location_type_id',
        'caption' => 'Location type ID',
        'description' => 'ID of the location type used to spatially relate recording groups to user records. This defines how granular the preferencing of nearby activities will be.',
        'type' => 'text_input',
        'required' => TRUE,
        'group' => 'Other Settings',
      ],
    ];
  }

  /**
   * Return the generated form output.
   *
   * @param array $args
   *   List of parameter values passed through to the form depending on how
   *   the form has been configured. This array always contains a value for
   *   language.
   * @param int $nid
   *   The Drupal node object's ID.
   *
   * @return string
   *   Form HTML.
   */
  public static function get_form($args, $nid) {
    iform_load_helpers(['data_entry_helper', 'report_helper']);
    helper_base::add_resource('font_awesome');
    $readAuth = data_entry_helper::get_read_auth($args['website_id'], $args['password']);
    $lang = [
      'recentAndActiveGroups' => lang::get("Recent and active $args[default_group_label_plural]"),
      'suggestedGroups' => lang::get("Suggested $args[default_group_label_plural]"),
    ];
    $searchControl = data_entry_helper::text_input([
      'label' => lang::get('Search'),
      'fieldname' => 'group-search',
    ]);
    $rootFolder = helper_base::getRootFolder(TRUE);
    $itemPanel = <<<HTML
      <li>
        <a href="{$rootFolder}groups/{fn:groupGetTitleForLink}">
          <div class="panel panel-primary">
            <div class="panel-heading">{fn:groupHeadingImage}</div>
            <div class="panel-body">
              <h3>{title}</h3>
              <p>{description}</p>
            </div>
          </div>
        </a>
      </li>
HTML;
    $reportOptions = [
      'readAuth' => $readAuth,
      'extraParams' => [
        'location_type_id' => $args['location_type_id'],
        'currentUser' => hostsite_get_user_field('indicia_user_id'),
        'limit' => 12,
      ],
      'header' => '<div class="card-gallery"><ul>',
      'bands' => [
        [
          'content' => $itemPanel,
        ],
      ],
      'footer' => '</ul></div>',
      'ajax' => TRUE,
      'customFieldFns' => [
        'groupHeadingImage',
        'groupGetTitleForLink',
      ],
    ];
    $suggestedGroupsGrid = report_helper::freeform_report(array_merge([
      'proxy' => hostsite_get_url("iform/ajax/group_discovery/suggested_groups/$nid"),
    ], $reportOptions));
    $recentAndActiveGroupsGrid = report_helper::freeform_report(array_merge([
      'proxy' => hostsite_get_url("iform/ajax/group_discovery/active_recent_groups/$nid"),
    ], $reportOptions));
    $r = <<<HTML
      $searchControl

      <h3>$lang[suggestedGroups]</h3>

      $suggestedGroupsGrid

      <h3>$lang[recentAndActiveGroups]</h3>

      $recentAndActiveGroupsGrid
HTML;
    return $r;
  }

  /**
   * Fetch suggested groups report output.
   *
   * Use a proxy for grid population to allow request caching.
   *
   * @param int $website_id
   *   Website ID.
   * @param string $password
   *   Password.
   * @param int $nid
   *   Node ID.
   *
   * @return array
   *   Report response.
   */
  public static function ajax_suggested_groups($website_id, $password, $nid) {
    iform_load_helpers(['report_helper']);
    $readAuth = report_helper::get_read_auth($website_id, $password);
    $options = [
      'dataSource' => 'library/groups/groups_discovery',
      'readAuth' => $readAuth,
      'extraParams' => $_GET,
      'caching' => TRUE,
      // This report is per user.
      'cachePerUser' => TRUE,
    ];
    return report_helper::get_report_data($options);
  }

  /**
   * Fetch active / recent groups report output.
   *
   * Use a proxy for grid population to allow request caching.
   *
   * @param int $website_id
   *   Website ID.
   * @param string $password
   *   Password.
   * @param int $nid
   *   Node ID.
   *
   * @return array
   *   Report response.
   */
  public static function ajax_active_recent_groups($website_id, $password, $nid) {
    iform_load_helpers(['report_helper']);
    $readAuth = report_helper::get_read_auth($website_id, $password);
    $options = [
      'dataSource' => 'library/groups/recent_and_active_groups',
      'readAuth' => $readAuth,
      'extraParams' => $_GET,
      'caching' => TRUE,
      // This report is global, not per user.
      'cachePerUser' => FALSE,
    ];
    return report_helper::get_report_data($options);
  }

}
