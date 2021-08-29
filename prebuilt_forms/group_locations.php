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
 * @link https://github.com/indicia-team/client_helpers/
 */

/**
 * Page for configuring the locations used by a recording group.
 *
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_group_locations {

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_group_locations_definition() {
    return [
      'title' => 'Group locations',
      'category' => 'Recording groups',
      'description' => 'A page listing the locations that are linked to a recording group. Can be configured to allow group admins to manage the list of group locations or for group users to view the locations.',
      'supportsGroups' => TRUE,
      'recommended' => TRUE,
    ];
  }

  /**
   * Get the list of parameters for this form.
   *
   * @return array
   *   List of parameters that this form requires.
   */
  public static function get_parameters() {
    require_once 'includes/map.php';
    $r = array_merge([
      [
        'name' => 'edit_location_path',
        'caption' => 'Path to edit location page',
        'description' => 'Path to a page allowing locations to be edited and created. Should be a page built using the Dynamic Locaiton prebuilt form.',
        'type' => 'string',
        'required' => FALSE,
      ],
      [
        'name' => 'explore_path',
        'caption' => 'Path to explore records page',
        'description' => '',
        'type' => 'string',
        'required' => FALSE,
      ],
      [
        'name' => 'allow_edit',
        'caption' => 'Allow editing',
        'description' => 'Enable or disable addition/deletion/editing of locations. If unticked this page is view only.',
        'type' => 'boolean',
        'required' => FALSE,
        'default' => TRUE,
      ],
    ], iform_map_get_map_parameters());
    return $r;
  }

  /**
   * Return the generated form output.
   *
   * @param array $args
   *   List of parameter values passed through to the form depending on how the
   *   form has been configured. This array always contains a value for
   *   language.
   * @param object $nid
   *   The Drupal node object's ID.
   * @param array $response
   *   When this form is reloading after saving a submission, contains the
   *   response from the service call. Note this does not apply when
   *   redirecting (in this case the details of the saved object are in the
   *   $_GET data).
   *
   * @return string
   *   Form HTML.
   */
  public static function get_form($args, $nid, $response = NULL) {
    if (empty($_GET['group_id'])) {
      return 'This page needs a group_id URL parameter.';
    }
    require_once 'includes/map.php';
    require_once 'includes/groups.php';
    global $indicia_templates;
    global $base_url;
    iform_load_helpers(['report_helper', 'map_helper']);
    $conn = iform_get_connection_details($nid);
    $readAuth = report_helper::get_read_auth($conn['website_id'], $conn['password']);
    report_helper::$javascript .= "indiciaData.nodeId = $nid;\n";
    data_entry_helper::$javascript .= "indiciaData.baseUrl = '" . $base_url . "';\n";
    group_authorise_form($args, $readAuth);
    $args = array_merge([
      'allow_edit' => TRUE,
    ], $args);
    $group = data_entry_helper::get_population_data([
      'table' => 'group',
      'extraParams' => $readAuth + ['id' => $_GET['group_id'], 'view' => 'detail'],
    ]);
    $group = $group[0];
    $title = hostsite_get_page_title($nid);
    hostsite_set_page_title("$group[title]: $title");
    $actions = array();
    if ($args['allow_edit']) {
      if (!empty($args['edit_location_path'])) {
        $actions[] = [
          'caption' => 'edit',
          'url' => '{rootFolder}' . $args['edit_location_path'],
          'urlParams' => [
            'group_id' => $_GET['group_id'],
            'location_id' => '{location_id}',
          ],
        ];
      }
      $actions[] = [
        'caption' => 'remove',
        'javascript' => 'indiciaFns.removeLocationFromGroup({groups_location_id});',
      ];
    }
    if (!empty($args['explore_path'])) {
      $actions[] = [
        'caption' => 'explore records',
        'url' => '{rootFolder}' . $args['explore_path'],
        'urlParams' => [
          'group_id' => $_GET['group_id'],
          'filter-location_id' => '{location_id}',
          'filter-date_age' => '',
        ],
      ];
    }

    $leftcol = report_helper::report_grid([
      'readAuth' => $readAuth,
      'dataSource' => 'library/locations/locations_for_groups',
      'sendOutputToMap' => true,
      'extraParams' => ['group_id' => $_GET['group_id']],
      'rowId' => 'location_id',
      'columns' => [
        [
          'display' => 'Actions',
          'actions' => $actions,
          'caption' => 'edit',
          'url' => '{rootFolder}',
        ],
      ],
    ]);
    if ($args['allow_edit']) {
      $leftcol .= '<fieldset><legend>' . lang::Get('Add sites to the group') . '</legend>';
      $leftcol .= '<p>' . lang::get('LANG_Add_Sites_Instruct') . '</p>';
      if (!empty($args['edit_location_path'])) {
        $leftcol .= lang::get('Either') .
          ' <a class="button" href="' . hostsite_get_url($args['edit_location_path'], array('group_id' => $_GET['group_id'])) .
          '">' . lang::get('enter details of a new site') . '</a><br/>';
      }
      $leftcol .= data_entry_helper::select([
        'label' => lang::get('Or, add an existing site'),
        'fieldname' => 'add_existing_location_id',
        'report' => 'library/locations/locations_available_for_group',
        'caching' => FALSE,
        'blankText' => lang::get('<please select>'),
        'valueField' => 'location_id',
        'captionField' => 'name',
        'extraParams' => $readAuth + [
          'group_id' => $_GET['group_id'],
          'user_id' => hostsite_get_user_field('indicia_user_id', 0),
        ],
        'afterControl' => '<button id="add-existing">Add</button>',
        ]);
      $leftcol .= '</fieldset>';
    }
    // @todo Link existing My Site to group. Need a new report to list sites I created, with sites already in the group
    // removed. Show in a drop down with an add button. Adding must create the groups_locations record, plus refresh
    // the grid and refresh the drop down.
    // @todo set destination after saving added site
    $map = map_helper::map_panel(iform_map_get_map_options($args, $readAuth), iform_map_get_ol_options($args));
    $r = str_replace(['{col-1}', '{col-2}', '{attrs}'], [$leftcol, $map, ''], $indicia_templates['two-col-50']);
    data_entry_helper::$javascript .= "indiciaData.group_id=$_GET[group_id];\n";
    return $r;
  }

}
