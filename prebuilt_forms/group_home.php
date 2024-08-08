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

require_once 'dynamic_report_explorer.php';
require_once 'includes/report_filters.php';
require_once 'includes/groups.php';

/**
 * A page for editing or creating a user group report page.
 */
class iform_group_home extends iform_dynamic_report_explorer {

  public static function get_parameters() {
    $retVal = array_merge(
      parent::get_parameters(),
      array(
        array(
          'name' => 'hide_standard_param_filter',
          'caption' => 'Hide filter in standard_params control?',
          'description' => 'Hide the filter displayed when the standard_params control is specified. Still allows
            standard params such as the release_status_limiter to be used with the report.',
          'type' => 'boolean',
          'required' => FALSE,
          'group' => 'Other Settings',
        ),
      )
    );
    return $retVal;
  }

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_group_home_definition() {
    return array(
      'title' => 'Group report page',
      'category' => 'Recording groups',
      'description' => 'A report page for recording groups. This is based on a dynamic report explorer, but it applies '.
          'an automatic filter to the page output based on a group_id URL parameter.',
      'supportsGroups' => TRUE,
      'recommended' => TRUE,
    );
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
    if (empty($_GET['group_id']) && empty($_GET['dynamic-group_id'])) {
      return 'This page needs a group_id URL parameter.';
    }
    global $base_url;
    iform_load_helpers(array('data_entry_helper'));
    data_entry_helper::$javascript .= "indiciaData.nodeId = $nid;\n";
    data_entry_helper::$javascript .= "indiciaData.baseUrl = '$base_url';\n";
    data_entry_helper::$javascript .= "indiciaData.currentUsername='" . hostsite_get_user_field('name') . "';\n";
    // Translations for the comment that goes into occurrence_comments when a record is verified or rejected.
    data_entry_helper::$javascript .= 'indiciaData.verifiedTranslation = "' . lang::get('Verified') . "\";\n";
    data_entry_helper::$javascript .= 'indiciaData.rejectedTranslation = "' . lang::get('Rejected') . "\";\n";
    self::$auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $membership = group_authorise_form($args, self::$auth['read']);
    group_apply_report_limits($args, self::$auth['read'], $nid, $membership);
    if (!empty($args['hide_standard_param_filter'])) {
      data_entry_helper::$javascript .= "$('#standard-params').hide();\n";
    }
    return parent::get_form($args, $nid);
  }

}
