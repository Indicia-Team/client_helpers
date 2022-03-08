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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/indicia-team/client_helpers
 */

/**
 * Authorise the current page.
 *
 * If accessing a page for a group you don't belong to, or a page via a group
 * which is not linked to the page then a message is shown and you are
 * redirected to home.
 *
 * @return bool
 *   True if the user is a member of the group associated with the page.
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
    return group_authorise_group_id($_GET['group_id'], $readAuth);
  }
  return FALSE;
}

/**
 * Authorise the user/page combination against a specified group ID.
 *
 * @param int $group_id
 *   ID of the group.
 * @param array $readAuth
 *   Authentication tokens.
 */
function group_authorise_group_id($group_id, $readAuth) {
  $gu = [];
  // Loading data into a recording group. Are they a member or is the page
  // public?
  // @todo: consider performance - 2 web services hits required to check permissions.
  if (hostsite_get_user_field('indicia_user_id')) {
    $gu = data_entry_helper::get_population_data(array(
      'table' => 'groups_user',
      'extraParams' => $readAuth + array(
        'group_id' => $group_id,
        'user_id' => hostsite_get_user_field('indicia_user_id'),
        'pending' => 'f',
      ),
      'nocache' => true
    ));
  }
  $gp = data_entry_helper::get_population_data(array(
    'table' => 'group_page',
    'extraParams' => $readAuth + array('group_id' => $group_id, 'path' => hostsite_get_current_page_path())
  ));
  if (count($gp) === 0) {
    hostsite_show_message(lang::get('You are trying to access a page which is not available for this group.'), 'warning', TRUE);
    hostsite_goto_page('<front>');
  }
  elseif (count($gu) === 0 && $gp[0]['administrator'] !== NULL) {
    // Administrator field is null if the page is fully public. Else if not
    // a group member, then throw them out.
    hostsite_show_message(lang::get('You are trying to access a page for a group you do not belong to.'), 'warning', TRUE);
    hostsite_goto_page('<front>');
  }
  return count($gu) > 0;
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
 * @param bool $isMember
 *
 * @return array
 *   Group field values loaded from the database.
 */
function group_apply_report_limits(array &$args, $readAuth, $nid, $isMember) {
  $group = data_entry_helper::get_population_data([
    'table' => 'group',
    'extraParams' => $readAuth + ['id' => $_GET['group_id'], 'view' => 'detail']
  ]);
  $group = $group[0];
  hostsite_set_page_title("$group[title]: " . hostsite_get_page_title($nid));
  $def = json_decode($group['filter_definition'], TRUE);
  $defstring = '';
  // Reconstruct this as a string to feed into dynamic report explorer.
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
        $args['skipped_report_columns'] = array('taxon_group', 'taxonomy');
      }
    }
  }
  // If records private, need to show them on a group report but only if user
  // is group member, which might not be the case if page accidentally made
  // fully public.
  if ($isMember && $group['private_records'] === 't') {
    $defstring .= "release_status=A\n";
  }
  if (empty($_GET['implicit'])) {
    // No need for a group user filter.
    $args['param_presets'] = implode("\n", array($args['param_presets'], $defstring));
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
