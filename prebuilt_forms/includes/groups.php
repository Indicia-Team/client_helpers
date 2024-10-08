<?php
/**
 * @file
 * List of methods that assist with handling recording groups.
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
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/indicia-team/client_helpers
 */

/**
 * Group membership statuses.
 */
enum GroupMembership {
  case NonMember;
  case Member;
  case Admin;
}
/**
 * Authorise the current page.
 *
 * If accessing a page for a group you don't belong to, or a page via a group
 * which is not linked to the page then a message is shown and you are
 * redirected to home.
 *
 * @return GroupMembership
 *   Current user's group membership status.
 */
function group_authorise_form($args, $readAuth) {
  if (!empty($args['limit_to_group_id']) && $args['limit_to_group_id'] !== (empty($_GET['group_id']) ? '' : $_GET['group_id'])) {
    if (empty($_GET['group_id'])) {
      hostsite_access_denied();
    }
    else {
      // Page owned by a different group, so throw them out.
      hostsite_show_message(lang::get('This page is a private recording group page which you cannot access.'), 'alert', true);
      hostsite_goto_page('<front>');
    }
  }

  if (!empty($_GET['group_id'])) {
    return group_get_user_membership($_GET['group_id'], $readAuth);
  }
  return GroupMembership::NonMember;
}

/**
 * Authorise the user/page combination against a specified group ID.
 *
 * @param int $group_id
 *   ID of the group.
 * @param array $readAuth
 *   Authentication tokens.
 * @param bool $checkPage
 *   Set to false to disable checking that the current page path is an iform
 *   page linked to the group.
 *
 * @return GroupMembership
 *   Group membership status.
 */
function group_get_user_membership($group_id, $readAuth, $checkPage = TRUE) {
  $gu = [];
  // Loading data into a recording group. Are they a member or is the page
  // public?
  // @todo: consider performance - 2 web services hits required to check permissions.
  if (hostsite_get_user_field('indicia_user_id')) {
    $gu = helper_base::get_population_data([
      'table' => 'groups_user',
      'extraParams' => $readAuth + [
        'group_id' => $group_id,
        'user_id' => hostsite_get_user_field('indicia_user_id'),
        'pending' => 'f',
      ],
      'nocache' => TRUE,
    ]);
  }
  if ($checkPage) {
    $gp = helper_base::get_population_data([
      'table' => 'group_page',
      'extraParams' => $readAuth + [
        'group_id' => $group_id,
        'path' => hostsite_get_current_page_path(),
      ]
    ]);
    if (count($gp) === 0) {
      hostsite_show_message(lang::get('You are trying to access a page which is not available for this group.'), 'warning', TRUE);
      hostsite_goto_page('<front>');
      return FALSE;
    }
    if (count($gu) === 0 && $gp[0]['administrator'] !== NULL) {
      // Administrator field is null if the page is fully public. Else if not
      // a group member, then throw them out.
      hostsite_show_message(lang::get('You are trying to access a page for a group you do not belong to.'), 'warning', TRUE);
      hostsite_goto_page('<front>');
      return FALSE;
    }
    elseif (isset($gu[0]['administrator']) && isset($gp[0]['administrator'])) {
      // Use isn't an administrator, and page is administration
      // Note: does not work if using TRUE as bool test, only string 't'
      if ($gu[0]['administrator'] !== 't' && $gp[0]['administrator'] === 't') {
        hostsite_show_message(lang::get('You are trying to open a group page that you do not have permission to access.'));
        hostsite_goto_page('<front>');
        return FALSE;
      }
    }
  }
  if (count($gu) === 0) {
    return GroupMembership::NonMember;
  }
  elseif ($gu[0]['administrator'] === 't') {
    return GroupMembership::Admin;
  }
  else {
    return GroupMembership::Member;
  }
}

/**
 * Applies any limits defined by a group's filters to the report config for the current page.
 *
 * Also adds the group name to the page title.
 *
 * @param array $args
 *   Form arguments.
 * @param array $readAuth
 *   Read authorisation tokens.
 * @param int $nid
 *   Node ID.
 * @param GroupMembership $membership
 *   Current user group membership info.
 *
 * @return array
 *   Group field values loaded from the database.
 */
function group_apply_report_limits(array &$args, $readAuth, $nid, GroupMembership $membership) {
  $group = helper_base::get_population_data([
    'table' => 'group',
    'extraParams' => $readAuth + ['id' => $_GET['group_id'], 'view' => 'detail']
  ]);
  $group = $group[0];
  hostsite_set_page_title("$group[title]: " . hostsite_get_page_title($nid));
  $def = json_decode($group['filter_definition'] ?? '', TRUE);
  $defstring = '';
  // Reconstruct this as a string to feed into dynamic report explorer.
  if (!empty($def)) {
    foreach ($def as $key => $value) {
      if ($key) {
        $value = is_array($value) ? json_encode($value) : $value;
        $defstring .= "{$key}_context=$value\n";
        if (!empty($value) && in_array($key, [
          'indexed_location_id',
          'indexed_location_list',
          'location_id',
          'location_list',
        ])) {
          $args['location_boundary_id'] = $value;
        }
        elseif (!empty($value) && $key === 'searchArea') {
          // A search area needs to be added to the map.
          require_once 'map.php';
          iform_map_zoom_to_geom($value, lang::get('Boundary'));
        }
        elseif (($key === 'taxon_group_id' || $key === 'taxon_group_list') && !empty($value) && strpos($value, ',') === FALSE) {
          // If the report is locked to a single taxon group, then we don't
          // need taxonomy columns.
          $args['skipped_report_columns'] = ['taxon_group', 'taxonomy'];
        }
      }
    }
  }
  // If records private, need to show them on a group report but only if user
  // is group member, which might not be the case if page accidentally made
  // fully public.
  if ($membership !== GroupMembership::NonMember && $group['private_records'] === 't') {
    $defstring .= "release_status=A\n";
  }
  if (empty($_GET['implicit'])) {
    // No need for a group user filter.
    $args['param_presets'] = implode("\n", [$args['param_presets'], $defstring]);
  }
  else {
    // Filter to group users - either implicitly, or only if they explicitly
    // submitted to the group.
    $prefix = ($_GET['implicit'] === 'true' || $_GET['implicit'] === 't') ? 'implicit_' : '';
    // Add the group parameters to the preset parameters passed to all reports
    // on this page.
    $args['param_presets'] = implode("\n", [
      $args['param_presets'],
      $defstring,
      "{$prefix}group_id=" . $_GET['group_id']
    ]);
  }
  $args['param_presets'] .= "\n";
  return $group;
}
