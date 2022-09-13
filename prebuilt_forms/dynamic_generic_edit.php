<?php

/**
 * @file
 * A prebuilt form for editing the details of a group.
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
 * @link https://github.com/Indicia-Team/client_helpers
 */

require_once 'includes/dynamic.php';

/**
 * A page for editing or creating data in any entity.
 *
 * Allows data entry forms for any entity, e.g. can be used to create forms for
 * non-standard warehouse tables added via extension modules.
 */
class iform_dynamic_generic_edit extends iform_dynamic {

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_dynamic_generic_edit_definition() {
    return [
      'title' => 'Enter a generic data record',
      'category' => 'Data entry forms',
      'description' => 'A form for creating or editing records in any entity that you specify in the configuration. To edit a record pass a query string parameter called `id`.',
    ];
  }

  /**
   * Get the list of parameters for this form.
   *
   * @return array
   *   List of parameters that this form requires.
   */
  public static function get_parameters() {
    return array_merge(
      parent::get_parameters(),
      [
        [
          'name' => 'entity',
          'caption' => 'Database entity',
          'description' => 'Name of the warehouse database entity in singular form.',
          'type' => 'textfield',
          'required' => TRUE,
        ],
        [
          'name' => 'view_prefix',
          'caption' => 'Database view prefix',
          'description' => 'Loads existing data from the detail_<entity> view associated with the entity. If the prefix is different, alter it here, or set an empty string if the warehouse allows loading directly from the table data.',
          'type' => 'textfield',
          'default' => 'detail',
        ],
        [
          'name' => 'structure',
          'caption' => 'Form Structure',
          'description' => 'Use [misc_extensions.data_entry_helper_control] to construct the form.',
          'type' => 'textarea',
          'default' => '',
          'group' => 'User Interface',
          'required' => TRUE,
        ],
      ]
    );
  }

  /**
   * Determine whether to show the form in create or edit mode.
   *
   * @param array $args
   *   Iform parameters.
   * @param object $nid
   *   ID of node being shown.
   *
   * @return const
   *   The mode [MODE_NEW|MODE_EXISTING].
   */
  protected static function getMode($args, $nid) {
    if ($_POST && !is_null(data_entry_helper::$entity_to_load)) {
      // Errors with new sample or entity populated with post, so display this
      // data.
      $mode = self::MODE_EXISTING;
    }
    elseif (array_key_exists("$args[entity]_id", $_GET)) {
      // Request for display of existing record.
      $mode = self::MODE_EXISTING;
    }
    else {
      // Request to create new record .
      $mode = self::MODE_NEW;
      data_entry_helper::$entity_to_load = [];
    }
    return $mode;
  }

  /**
   * Load an existing record's details for editing.
   *
   * @param array $args
   *   Form parameters.
   * @param array $auth
   *   Authorisation tokens.
   */
  protected static function getEntity(array $args, array $auth) {
    data_entry_helper::$entity_to_load = [];
    data_entry_helper::load_existing_record($auth['read'], $args['entity'], $_GET["$args[entity]_id"], $args['view_prefix']);
  }

  /**
   * Converts the posted form values into a warehouse submission.
   *
   * @param array $values
   *   Form values.
   * @param array $args
   *   Form configuration arguments.
   *
   * @return array
   *   Submission data.
   */
  public static function get_submission($values, $args) {
    $struct = [
      'model' => $args['entity'],
    ];
    return submission_builder::build_submission($values, $struct);
  }

  /**
   * Retrieve the additional HTML to appear at the top form.
   *
   * Content for the top of the first tab or form section. This is a set of
   * hidden inputs containing the website ID and survey ID as well as an
   * existing entity's ID.
   *
   * @return string
   *   Additional HTML for the first tab.
   */
  protected static function getFormHiddenInputs($args, $auth, &$attributes) {
    // Get authorisation tokens to update the Warehouse, plus any other hidden
    // data.
    $r = $auth['write'] . <<<HTML
<input type="hidden" id="website_id" name="website_id" value="$args[website_id]" />

HTML;
    if (isset(data_entry_helper::$entity_to_load["$args[entity]:id"])) {
      $existingId = data_entry_helper::$entity_to_load["$args[entity]:id"];
      $r .= <<<HTML
<input type="hidden" id="$args[entity]:id" name="$args[entity]:id" value="$existingId" />

HTML;
    }
    return $r;
  }

}
