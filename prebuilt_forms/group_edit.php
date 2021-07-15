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
 * @link http://code.google.com/p/indicia/
 */

require_once 'includes/report_filters.php';

/**
 * A page for editing or creating a group of people.
 *
 * Any grouping of people can be defined for any purpose, e.g. as a recording
 * group, organisation or project.
 */
class iform_group_edit {

  private static $groupType = 'group';

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_group_edit_definition() {
    return array(
      'title' => 'Create or edit a group',
      'category' => 'Recording groups',
      'description' => 'A form for creating or editing groups of recorders.',
      'recommended' => TRUE,
    );
  }

  /**
   * Get the list of parameters for this form.
   *
   * @return array
   *   List of parameters that this form requires.
   */
  public static function get_parameters() {
    return array(
      array(
        'name' => 'group_type',
        'caption' => 'Group type',
        'description' => 'Type of group this form will be used to create or edit. Leave blank to let the group creator choose.',
        'type' => 'checkbox_group',
        'table' => 'termlists_term',
        'valueField' => 'id',
        'captionField' => 'term',
        'extraParams' => array('termlist_external_key' => 'indicia:group_types'),
      ), array(
        'name' => 'parent_group_type',
        'caption' => 'Parent group type',
        'description' => 'Type of group that this form can create children of. Requires that you set the parent relationship type ' .
            'as well. Only used when accessing the form without a from_group_id, in which case a drop down control allows the user ' .
            'to pick the parent group.',
        'type' => 'select',
        'table' => 'termlists_term',
        'valueField' => 'id',
        'captionField' => 'term',
        'extraParams' => array('termlist_external_key' => 'indicia:group_types'),
        'required' => FALSE,
      ), array(
        'name' => 'parent_group_relationship_type',
        'caption' => 'Parent relationship type',
        'description' => 'If you are using this form to create groups which will be the children of other groups, then when you call this '.
            'page pass from_group_id=... in the URL to set the parent group\'s ID, which must of course exist. Set the parent relationship '.
            'type here to define what relationship type to create between the parent and child groups. If this is set, then the from_group_id '.
            'in the URL parameters is required.',
        'type' => 'select',
        'table' => 'termlists_term',
        'valueField' => 'id',
        'captionField' => 'term',
        'extraParams' => array('termlist_external_key' => 'indicia:group_relationship_types'),
        'required' => FALSE,
      ), array(
        'name' => 'can_attach_to_multiple_parents',
        'caption' => 'Can attach to multiple parents',
        'description' => 'If this option is set and the page loads with a from_group_id in the URL parameters, ' .
          'then it is possible to attach the activity to multiple parents. The list of parents offered is the ' .
          'hierarchy of children and other descendants of the group pointed to by from_group_id.',
        'type' => 'boolean',
        'required' => FALSE,
      ), array(
        'name' => 'allowed_multiple_parent_group_types',
        'caption' => 'Allowed multiple parent group types',
        'description' => 'Comma separated list of group type IDs that are allowed to be set as one of the ' .
            'multiple parents.',
        'type' => 'text_input',
        'required' => FALSE,
      ), array(
        'name' => 'inherit_admin_privileges',
        'caption' => 'Inherit admin privileges from parents',
        'description' => 'If this option is set then you can edit the group if you are an admin of the group or any of ' .
            'its parents.',
        'type' => 'boolean',
        'required' => FALSE,
      ), array(
        'name' => 'join_methods',
        'caption' => 'Available joining methods',
        'description' => 'Which joining methods are available for created groups? Put one option per line, with the option code ' .
            '(P, R, I, A) followed by an equals sign then the text description given. Option P is a public group which ' .
            'anyone can join, R is a group which anyone can browse to find and request to join but the admin must approve ' .
            'new members, I is an invite only group and A is a group where the administrator creates the list of members ' .
            'manually. The latter should only be used in cases where it is appropriate for a group membership to be setup ' .
            'without explicit member approval. If you allow only one joining method, then the group creator will not need ' .
            'to pick one so the options control will be hidden on the edit form.',
        'type' => 'textarea',
        'default' => "P=Anyone can join without needing approval\nR=Anyone can request to join but a group administrator must approve their membership\n" .
            "I=The group is closed and membership is by invite only\nA=Administrator will set up the members manually",
        'required' => TRUE,
      ),
      array(
        'name' => 'include_code',
        'caption' => 'Include code field',
        'description' => 'Include the optional field for setting a group code?',
        'type' => 'checkbox',
        'default' => FALSE,
        'required' => FALSE,
      ),
      array(
        'name' => 'include_dates',
        'caption' => 'Include date fields',
        'description' => 'Include the optional fields for setting the date range the group operates for?',
        'type' => 'checkbox',
        'default' => FALSE,
        'required' => FALSE,
      ),
      array(
        'name' => 'include_logo_controls',
        'caption' => 'Include logo upload controls',
        'description' => 'Include the controls for uploading and attaching a logo image to the group?',
        'type' => 'checkbox',
        'default' => TRUE,
        'required' => FALSE,
      ),
      array(
        'name' => 'include_sensitivity_controls',
        'caption' => 'Include sensitive records options',
        'description' => 'Include the options for controlling viewing of sensitive records within the group?',
        'type' => 'checkbox',
        'default' => TRUE,
        'required' => FALSE,
      ),
      array(
        'name' => 'include_report_filter',
        'caption' => 'Include report filter',
        'description' => 'Include the optional panel for defining a report filter?',
        'type' => 'checkbox',
        'default' => TRUE,
        'required' => FALSE
      ),
      array(
        'name' => 'include_linked_pages',
        'caption' => 'Include linked pages',
        'description' => 'Include the optional panel for defining a data entry and reporting pages linked to this group?',
        'type' => 'checkbox',
        'default' => TRUE,
        'required' => FALSE
      ),
      array(
        'name' => 'include_page_access_levels',
        'caption' => 'Include page access level controls',
        'description' => 'Include the option to specify the access level required for a user to view a group page?',
        'type' => 'checkbox',
        'default' => FALSE,
        'required' => FALSE
      ),
      array(
        'name' => 'include_private_records',
        'caption' => 'Include private records field',
        'description' => 'Include the optional field for withholding records from release?',
        'type' => 'checkbox',
        'default' => TRUE,
        'required' => FALSE,
      ),
      array(
        'name' => 'include_administrators',
        'caption' => 'Include admins control',
        'description' => 'Include a control for setting up a list of the admins for this group? If not set, then the group ' .
          'creator automatically gets assigned as the administrator.',
        'type' => 'checkbox',
        'default' => FALSE,
        'required' => FALSE,
      ),
      array(
        'name' => 'include_members',
        'caption' => 'Include members control',
        'description' => 'Include a control for setting up a list of the members for this group? If not set, then the group ' .
          'creator automatically gets assigned as the administrator. Do not use this option for group joining methods that ' .
          'involve the members requesting or being invited - this is only appropriate when the group admin explicitly controls ' .
          'the group membership.',
        'type' => 'checkbox',
        'default' => FALSE,
        'required' => FALSE,
      ),
      array(
        'name' => 'include_licence',
        'caption' => 'Include licence control',
        'description' => 'Include a control for selecting a licence to apply to all records in the group. Licences must be ' .
          'configured for the website on the warehouse first.',
        'type' => 'checkbox',
        'default' => FALSE,
        'required' => FALSE,
      ),
      array(
        'name' => 'data_inclusion_mode',
        'caption' => 'Group data inclusion',
        'description' => 'How will the decision regarding how records are included in group data be made',
        'type' => 'select',
        'lookupValues' => array(
          'implicit' => 'Implicit. Records posted by group members which meet the filter criteria will be included in group data.',
          'explicit' => 'Explicit. Records must be deliberately posted into the group.',
          'choose' => 'Let the group administrator decide this',
        ),
        'default' => 'choose',
        'required' => FALSE,
        'blankText' => 'All matching filter. All data matching the filter are included on reports.'
      ),
      array(
        'name' => 'filter_types',
        'caption' => 'Filter Types',
        'description' => 'JSON describing the filter types that are available if the include report filter option is checked.',
        'type' => 'textarea',
        'default' => '{"":"what,where,when","Advanced":"source,quality"}',
        'required' => FALSE,
      ),
      array(
        'name' => 'indexed_location_type_ids',
        'caption' => 'Indexed location types',
        'description' => 'Comma separated list of location type IDs that are available for selection as a filter boundary, where the location type is indexed.',
        'type' => 'text_input',
        'required' => FALSE,
      ),
      array(
        'name' => 'other_location_type_ids',
        'caption' => 'Other location types',
        'description' => 'Comma separated list of location type IDs that are available for selection as a filter boundary, where the location type is not indexed.',
        'type' => 'text_input',
        'required' => FALSE,
      ),
      array(
        'name' => 'include_sites_created_by_user',
        'caption' => 'Include sites created by the user',
        'description' => "Are a user's own sites (e.g. My Sites) available for selection as a filter.",
        'type' => 'boolean',
        'required' => FALSE,
        'default' => TRUE,
      ),
      array(
        'name' => 'taxon_list_id',
        'caption' => 'Taxon list ID',
        'description' => 'If you need to override the default taxon list used on this site for the filter builder, ' .
          'specify the ID here. This allows you to filter to species, higher taxa and families from the ' .
          'alternative list',
        'type' => 'text_input',
      ),
      array(
        'name' => 'default_linked_pages',
        'caption' => 'Default linked pages',
        'description' => "Create a list of pages you would like to be added to each group's page list as a default starting point.",
        'type' => 'jsonwidget',
        'schema' => '
{
  "type":"seq",
  "title":"Pages list",
  "sequence":
  [
    {
      "type":"map",
      "title":"Page",
      "mapping": {
        "path": {"type":"str","desc":"Path to the page which should be a group-enabled Indicia page."},
        "caption": {"type":"str","desc":"Caption to display for this page."},
        "administrator": {"type":"bool","desc":"Tick if this page is only for administrator use."}
      }
    }
  ]
}'
      ),
      array(
        'name' => 'groups_page_path',
        'caption' => 'Path to main groups page',
        'description' => 'Path to the Drupal page which my groups are listed on.',
        'type' => 'text_input',
        'required' => FALSE
      ),
    );
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
   *
   * @return string
   *   Form HTML.
   */
  public static function get_form($args, $nid) {
    global $indicia_templates;
    if (!hostsite_get_user_field('indicia_user_id')) {
      return 'Please ensure that you\'ve filled in your surname on your user profile before creating or editing groups.';
    }
    // The following allows for different ways of setting the main parent group
    // in URL params so this can tie into report filtering when required.
    if (empty($_GET['from_group_id']) && !empty($_GET['dynamic-from_group_id'])) {
      $_GET['from_group_id'] = $_GET['dynamic-from_group_id'];
    }
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    self::createBreadcrumb($args, $auth);
    iform_load_helpers(array('report_helper', 'map_helper'));
    $args = array_merge(array(
      'include_code' => FALSE,
      'include_dates' => FALSE,
      'include_logo_controls' => TRUE,
      'include_sensitivity_controls' => TRUE,
      'include_report_filter' => TRUE,
      'include_linked_pages' => TRUE,
      'include_page_access_levels' => FALSE,
      'include_private_records' => FALSE,
      'include_administrators' => FALSE,
      'include_members' => FALSE,
      'include_licence' => FALSE,
      'filter_types' => '{"":"what,where,when","Advanced":"source,quality"}',
      'indexed_location_type_ids' => '',
      'other_location_type_ids' => '',
      'include_sites_created_by_user' => TRUE,
      'data_inclusion_mode' => 'choose',
    ), $args);
    $args['filter_types'] = json_decode($args['filter_types'], TRUE);
    $reloadPath = self::getReloadPath();
    data_entry_helper::$website_id = $args['website_id'];
    // Maintain compatibility with form settings from before group type became
    // multiselect.
    if (empty($args['group_type'])) {
      $args['group_type'] = [];
    }
    elseif (!is_array($args['group_type'])) {
      $args['group_type'] = array($args['group_type']);
    }
    if (!empty($_GET['group_id'])) {
      self::loadExistingGroup($_GET['group_id'], $auth, $args);
      // If reloading a group, the group type must be available for selection.
      if (!in_array(data_entry_helper::$entity_to_load['group:group_type_id'], $args['group_type'])) {
        $args['group_type'][] = data_entry_helper::$entity_to_load['group:group_type_id'];
      }
    }
    if (count($args['group_type']) === 1) {
      $terms = data_entry_helper::get_population_data(array(
        'table' => 'termlists_term',
        'extraParams' => $auth['read'] + array('id' => $args['group_type'][0]),
      ));
      self::$groupType = strtolower($terms[0]['term']);
    }
    self::$groupType = lang::get(self::$groupType);
    $r = "<form method=\"post\" id=\"entry_form\" action=\"$reloadPath\" enctype=\"multipart/form-data\">\n";
    $r .= '<fieldset id="group-details-fieldset"><legend>' . lang::get('Fill in details of your {1} below', self::$groupType) . '</legend>';
    $r .= $auth['write'] .
          "<input type=\"hidden\" id=\"website_id\" name=\"website_id\" value=\"" . $args['website_id'] . "\" />\n";
    $r .= data_entry_helper::hidden_text(array('fieldname' => 'group:id'));
    // if a fixed choice of group type, can use a hidden input to put the value in the form.
    if (count($args['group_type']) === 1) {
      $r .= '<input type="hidden" name="group:group_type_id" value="' . $args['group_type'][0] . '"/>';
    }
    if (!empty(data_entry_helper::$entity_to_load['group:title'])) {
      hostsite_set_page_title(lang::get('Edit {1}', data_entry_helper::$entity_to_load['group:title']));
    }
    $r .= data_entry_helper::text_input(array(
      'label' => lang::get('{1} name', ucfirst(self::$groupType)),
      'fieldname' => 'group:title',
      'validation' => array('required'),
      'class' => 'control-width-6',
      'helpText' => lang::get('Provide the full title of the {1}', self::$groupType),
    ));
    if ($args['include_code']) {
      $r .= data_entry_helper::text_input(array(
        'label' => lang::get('Code'),
        'fieldname' => 'group:code',
        'class' => 'control-width-4',
        'helpText' => lang::get(
          'Provide a code or abbreviation identifying the {1}, up to 20 characters long.',
          self::$groupType
        ),
        'validation' => ['length[20]'],
      ));
    }
    $r .= data_entry_helper::textarea(array(
      'label' => ucfirst(lang::get('{1} description', self::$groupType)),
      'fieldname' => 'group:description',
      'helpText' => lang::get('LANG_Description_Field_Instruct', self::$groupType),
      'class' => 'control-width-6',
    ));
    // If adding a new group which should have a parent group of some type or other, but no parent
    // group is specified in the from_group_id parameter, then let the user pick a group to link as the parent.
    if (empty($_GET['group_id']) && !empty($args['parent_group_type']) &&
        !empty($args['parent_group_relationship_type']) && empty($_REQUEST['from_group_id'])) {
      // There should be a parent group, but none provided, so allow the user to pick one.
      $r .= data_entry_helper::select(array(
        'label' => ucfirst(lang::get('{1} parent', self::$groupType)),
        'fieldname' => 'from_group_id',
        'table' => 'groups_user',
        'captionField' => 'group_title',
        'valueField' => 'group_id',
        'extraParams' => $auth['read'] + array(
          'group_type_id' => $args['parent_group_type'],
          'user_id' => hostsite_get_user_field('indicia_user_id'),
          'view' => 'detail',
          'pending' => 'f',
        ),
        'validation' => array('required'),
        'blankText' => lang::get('<please select>'),
      ));
    }
    if (!empty($args['can_attach_to_multiple_parents']) &&
        (!empty($_REQUEST['from_group_id']) || !empty($_REQUEST['group_id']))) {
      $r .= self::chooseParentsFromHierarchyBlock($args, $auth);
    }
    if (count($args['group_type']) !== 1) {
      $params = array(
        'termlist_external_key' => 'indicia:group_types',
        'orderby' => 'sortorder,term',
      );
      if (!empty($args['group_type'])) {
        $params['query'] = json_encode(array('in' => array('id' => array_values($args['group_type']))));
      }
      $r .= data_entry_helper::select(array(
        'label' => ucfirst(lang::get('{1} type', self::$groupType)),
        'fieldname' => 'group:group_type_id',
        'validation' => array('required'),
        'table' => 'termlists_term',
        'valueField' => 'id',
        'captionField' => 'term',
        'extraParams' => $auth['read'] + $params,
        'class' => 'control-width-4',
        'blankText' => lang::get('<please select>'),
        'helpText' => lang::get('What sort of {1} is it?', self::$groupType),
      ));
    }
    $r .= self::groupLogoControl($args);
    $r .= self::joinMethodsControl($args);
    if ($args['include_sensitivity_controls']) {
      $r .= data_entry_helper::checkbox(array(
        'label' => lang::get('Show records at full precision'),
        'fieldname' => 'group:view_full_precision',
        'helpText' => lang::get('Any sensitive records added to the system are normally shown blurred to a lower grid reference precision. If this box ' .
            'is checked, then group members can see sensitive records explicitly posted for the {1} at full precision.', self::$groupType),
      ));
    }
    $r .= self::dateControls($args);
    if ($args['include_private_records']) {
      $r .= data_entry_helper::checkbox(array(
        'label' => lang::get('Records are private'),
        'fieldname' => 'group:private_records',
        'helpText' => lang::get('Tick this box if you want to withold the release of the records from this {1} until a ' .
          'later point in time, e.g. when a project is completed.', self::$groupType),
      ));
      // If an existing group with private records, then we might need to display a message warning the user about releasing the records.
      // Initially hidden, we use JS to display it when appropriate.
      if (!empty(data_entry_helper::$entity_to_load['group:id']) && data_entry_helper::$entity_to_load['group:private_records'] === 't')
        $r .= '<p class="warning" style="display: none" id="release-warning">' .
            lang::get('You are about to release the records belonging to this group. Do not proceed unless you intend to do this!') . '</p>';
    }
    $r .= self::memberControls($args, $auth);
    $r .= '</fieldset>';
    $r .= self::reportFilterBlock($args, $auth, $hiddenPopupDivs);
    $r .= self::inclusionMethodControl($args);
    $r .= self::formsBlock($args, $auth);
    // Auto-insert the creator as an admin of the new group, unless the admins
    // are manually specified.
    if (!$args['include_administrators'] && empty($_GET['group_id'])) {
      $r .= '<input type="hidden" name="groups_user:admin_user_id[]" value="' . hostsite_get_user_field('indicia_user_id') . '"/>';
    }
    $r .= '<input type="hidden" name="groups_user:administrator" value="t"/>';
    $r .= '<input type="submit" class="' . $indicia_templates['buttonDefaultClass'] . '" id="save-button" value="' .
        (empty(data_entry_helper::$entity_to_load['group:id']) ?
        lang::get('Create {1}', self::$groupType) : lang::get('Update {1} settings', self::$groupType))
        . "\" />\n";
    $r .= '</form>';
    $r .= $hiddenPopupDivs;

    data_entry_helper::enable_validation('entry_form');
    // JavaScript to grab the filter definition and store in the form for
    // posting when the form is submitted.
    data_entry_helper::$javascript .= "
$('#entry_form').submit(function() {
  $('#filter-title-val').val('" . lang::get('Filter for user group') . " ' + $('#group\\\\:title').val() + ' ' + new Date().getTime());
  $('#filter-def-val').val(JSON.stringify(indiciaData.filter.def));
});\n";
    // For existing groups, prevent removal of yourself as a member. Someone
    // else will have to do this for you so we don't orphan groups.
    if (!empty(data_entry_helper::$entity_to_load['group:id'])) {
      data_entry_helper::$javascript .= "$('#groups_user\\\\:admin_user_id\\\\:sublist input[value=" .
        hostsite_get_user_field('indicia_user_id') . "]').closest('li').children('span').remove();\n";
    }
    data_entry_helper::$javascript .= 'indiciaData.ajaxUrl="' . hostsite_get_url('iform/ajax/group_edit') . "\";\n";
    return $r;
  }

  private static function loadExistingMultipleParents($auth) {
    $r = [];
    $parents = data_entry_helper::get_population_data(array(
      'table' => 'group_relation',
      'extraParams' => $auth['read'] + array('to_group_id' => $_GET['group_id']),
      'caching' => FALSE
    ));
    foreach ($parents as $parent) {
      $r[$parent['from_group_id']] = $parent['id'];
    }
    return $r;
  }

  /**
   * @param $args
   * @param $auth
   * @return string
   * @todo need to reload properly on edit
   * @todo save to database
   */
  private static function chooseParentsFromHierarchyBlock($args, $auth) {
    if (!empty($_GET['group_id'])) {
      $existing = self::loadExistingMultipleParents($auth);
    }
    $r = '<fieldset id="group-parents-fieldset"><legend>' . lang::get('{1} parents', ucfirst(self::$groupType)) . ':</legend><ul>';
    // Retrieve list of entire hierarchy.
    $params = array('parent_group_id' => $_GET['from_group_id']);
    if (!empty($args['allowed_multiple_parent_group_types'])) {
      $params['group_type_ids'] = $args['allowed_multiple_parent_group_types'];
    }
    $groups = report_helper::get_report_data(array(
      'readAuth' => $auth['read'],
      'dataSource' => 'library/groups/groups_list_hierarchy',
      'extraParams' => $params
    ));
    // Output the checkboxes.
    $lastLevel = 0;
    foreach ($groups as $group) {
      while ($lastLevel < $group['level']) {
        $r .= '<ul>';
        $lastLevel++;
      }
      while ($lastLevel > $group['level']) {
        $r .= '</ul>';
        $lastLevel--;
      }
      if ($lastLevel === 0) {
        // Link to top level group of hierarchy is mandatory.
        $r .= '<span>' . lang::get('This {1} is linked to <strong>{2}</strong>. ' .
            'If you want to also link the {1} to other descendents of <strong>{2}</strong> ' .
            'you can select them below.', self::$groupType, $group['title']) . '</span>';
        $r .= data_entry_helper::checkbox(array(
          'fieldname' => "check-all-groups",
          'afterControl' => "<label for=\"check-all-groups\" class=\"auto\">Check/uncheck all</label>"
        ));
      }
      else {
        $existingGroupRelationId = array_key_exists($group['id'], $existing) ? $existing[$group['id']] : '';
        $r .= data_entry_helper::checkbox(array(
            'fieldname' => "parent_group:$existingGroupRelationId:$group[id]",
            'afterControl' => "<label for=\"parent_group:$existingGroupRelationId:$group[id]\" class=\"auto\">$group[title]</label>",
            'default' => $existingGroupRelationId ? TRUE : FALSE,
            'class' => 'parent-checkbox'
          ));
      }
    }
    while ($lastLevel > 0) {
      $r .= '</ul>';
      $lastLevel--;
    }
    $r .= '</ul></fieldset>';
    return $r;
  }

  private static function formsBlock($args, $auth) {
    $r = '';
    if ($args['include_linked_pages']) {
      $r = '<fieldset id="group-pages-fieldset"><legend>' . lang::get('{1} pages', ucfirst(self::$groupType)) . '</legend>';
      $r .= '<p>' . lang::get('LANG_Pages_Instruct', self::$groupType, lang::get('groups')) . '</p>';
      $pages = hostsite_get_group_compatible_pages(empty($_GET['group_id']) ? NULL : $_GET['group_id']);
      if (empty($_GET['group_id'])) {
        $default = [];
        if (isset($args['default_linked_pages'])) {
          $defaultPages = json_decode($args['default_linked_pages'], TRUE);
          foreach ($defaultPages as $page) {
            $page['administrator'] = (isset($page['administrator']) && $page['administrator']) ? 't' : 'f';
            if (!isset($page['caption']))
              $page['caption'] = $page['path'];
            $default[] = [
              'fieldname' => "group+:pages:",
              'default' => json_encode(array($page['path'], $page['caption'], $page['administrator'])),
            ];
          }
        }
      }
      else {
        $default = self::getGroupPages($auth);
      }
      $columns = array(
        array(
          'label' => lang::get('Form'),
          'datatype' => 'lookup',
          'lookupValues' => $pages,
          'validation' => array('unique'),
        ), array(
          'label' => lang::get('Link caption'),
          'datatype' => 'text',
        ), array(
          'label' => lang::get('Who can access the page?'),
          'datatype' => 'lookup',
          'lookupValues' => array(
            '' => lang::get('Available to anyone'),
            'f' => lang::get('Available only to group members'),
            't' => lang::get('Available only to group admins'),
          ),
          'default' => 'f',
        ),
      );
      if ($args['include_page_access_levels']) {
        $values = array(
          '0' => lang::get('0 - no additional access level required'),
        );
        for ($i = 1; $i <= 10; $i++) {
          $values[$i] = lang::get('Requires access level {1} or higher', $i);
        }
        $columns[] = array(
          'label' => lang::get('Additional minimum page access level'),
          'datatype' => 'lookup',
          'lookupValues' => $values,
          'default' => '0',
        );
      }
      $r .= data_entry_helper::complex_attr_grid(array(
        'fieldname' => 'group:pages[]',
        'columns' => $columns,
        'default' => $default,
        'defaultRows' => min(3, count($pages)),
      ));
      $r .= '</fieldset>';
    }
    return $r;
  }

  /**
   * Retrieve the pages linked to this group from the database.
   */
  private static function getGroupPages($auth) {
    $pages = data_entry_helper::get_population_data(array(
      'table' => 'group_page',
      'extraParams' => $auth['read'] + array('group_id' => $_GET['group_id']),
      'nocache' => TRUE,
    ));
    $r = [];
    foreach ($pages as $page) {
      $r[] = array(
        'fieldname' => "group+:pages:$page[id]",
        'default' => json_encode(array($page['path'], $page['caption'], $page['administrator'], $page['access_level'])),
      );
    }
    return $r;
  }

  private static function groupLogoControl($args) {
    if ($args['include_logo_controls']) {
      return data_entry_helper::image_upload(array(
        'fieldname' => 'group:logo_path',
        'label' => lang::get('Logo'),
        'existingFilePreset' => 'med',
      ));
    }
    else {
      return '';
    }
  }

  /**
   * Returns a control for picking one of the allowed joining methods.
   *
   * If there is only one method available then this method returns a single
   * hidden input.
   *
   * @param array $args
   *   Form configuration arguments.
   *
   * @return string
   *   HTML to output.
   */
  private static function joinMethodsControl($args) {
    $r = '';
    $joinMethods = data_entry_helper::explode_lines_key_value_pairs($args['join_methods']);
    if (count($joinMethods) === 1) {
      $methods = array_keys($joinMethods);
      $r .= '<input type="hidden" name="group:joining_method" value="' . $methods[0] . '"/>';
    }
    else {
      $r .= data_entry_helper::radio_group(array(
        'label' => ucfirst(lang::get('How users join this {1}', self::$groupType)),
        'fieldname' => 'group:joining_method',
        'lookupValues' => $joinMethods,
        'sep' => '<br/>',
        'validation' => ['required'],
      ));
    }
    return $r;
  }

  /**
   * Returns a control for picking one of the allowed record inclusion methods methods. If there is only one allowed,
   * then this is output as a single hidden input.
   *
   * @param array $args
   *   Form configuration arguments.
   *
   * @return string
   *   HTML to output.
   */
  private static function inclusionMethodControl($args) {
    if ($args['data_inclusion_mode'] !== 'choose') {
      $mappings = array('' => '', 'implicit' => 't', 'explicit' => 'f');
      $r = data_entry_helper::hidden_text(array(
        'fieldname' => 'group:implicit_record_inclusion',
        'default' => $mappings[$args['data_inclusion_mode']]
      ));
    } else {
      $r = '<fieldset id="group-records-fieldset"><legend>' . lang::get('How to decide which records to include in the {1} reports', self::$groupType) . '</legend>';
      $r .= '<p>' . lang::get('LANG_Record_Inclusion_Instruct_1', self::$groupType, lang::get(self::$groupType . "'s")) . ' ';
      if ($args['include_sensitivity_controls'])
        $r .= lang::get('LANG_Record_Inclusion_Instruct_Sensitive', self::$groupType) . ' ';
      $r .= lang::get('LANG_Record_Inclusion_Instruct_2', self::$groupType, ucfirst(self::$groupType))  . '</p>';
      if (isset(data_entry_helper::$entity_to_load) &&
          array_key_exists('group:implicit_record_inclusion', data_entry_helper::$entity_to_load) &&
          is_null(data_entry_helper::$entity_to_load['group:implicit_record_inclusion']))
        data_entry_helper::$entity_to_load['group:implicit_record_inclusion'] = '';
      $r .= data_entry_helper::radio_group(array(
        'fieldname' => 'group:implicit_record_inclusion',
        'label' => lang::get('Include records on reports if'),
        'lookupValues' => array(
          'f' => lang::get('they were posted by a group member and match the filter defined above ' .
            'and they were submitted via a data entry form for the {1}', self::$groupType),
          't' => lang::get('they were posted by a group member and match the filter defined above, ' .
            'but it doesn\'t matter which recording form was used', self::$groupType),
          '' => lang::get('they match the filter defined above but it doesn\'t matter who ' .
            'posted the record or via which form', self::$groupType),
        ),
        'default' => 'f',
        'validation' => array('required'),
      ));
      $r .= ' </fieldset>';
    }
    return $r;
  }

  /**
   * Returns controls for defining the date range of a group if this option is enabled.
   *
   * @param array $args
   *   Form configuration arguments.
   *
   * @return string
   *   HTML to output.
   */
  private static function dateControls($args) {
    $r = '';
    if ($args['include_dates']) {
      $r .= '<p>' . lang::get('If the {1} will only be active for a limited period of time (e.g. an event or bioblitz) ' .
          'then please fill in the start and or end date of this period in the controls below. This helps to prevent people joining after '.
          'the {2}.', self::$groupType, lang::get('group is no longer active')) . '</p>';
      $r .= '<div id="ctrl-wrap-group-from-to" class="form-row ctrl-wrap">';
      $r .= data_entry_helper::date_picker(array(
        'label' => ucfirst(lang::get('{1} active from', self::$groupType)),
        'fieldname' => 'group:from_date',
        'controlWrapTemplate' => 'justControl',
        'helpText' => lang::get('LANG_From_Field_Instruct'),
        'allowFuture' => TRUE,
      ));
      $r .= data_entry_helper::date_picker(array(
        'label' => lang::get('to'),
        'fieldname' => 'group:to_date',
        'labelClass' => 'auto',
        'controlWrapTemplate' => 'justControl',
        'helpText' => lang::get('LANG_To_Field_Instruct'),
        'allowFuture' => TRUE,
      ));
      $r .= '</div>';
    }
    return $r;
  }

  /**
   * Returns controls for defining the list of group members and administrators if this option is enabled.
   * @param array $args Form configuration arguments
   * @param array $auth Authorisation tokens
   * @return string HTML to output
   */
  private static function memberControls($args, $auth) {
    $r = '';
    $class = empty(data_entry_helper::$validation_errors['groups_user:general']) ? 'control-width-5' : 'ui-state-error control-width-5';
    if ($args['include_administrators']) {
      global $user;
      $me = hostsite_get_user_field('last_name') . ', ' . hostsite_get_user_field('first_name') . ' (' . $user->mail . ')';
      $r .= data_entry_helper::sub_list(array(
        'fieldname' => 'groups_user:admin_user_id',
        'label' => ucfirst(lang::get('{1} administrators', self::$groupType)),
        'table' => 'user',
        'captionField' => 'person_name_unique',
        'valueField' => 'id',
        'extraParams' => $auth['read']+array('view' => 'detail'),
        'helpText' => lang::get('LANG_Admins_Field_Instruct', self::$groupType),
        'addToTable' => FALSE,
        'class' => $class,
        'default' => [[
          'fieldname' => 'groups_user:admin_user_id[]',
          'default' => hostsite_get_user_field('indicia_user_id'),
          'caption' => $me,
        ]],
      ));
    }
    if ($args['include_members']) {
      $r .= data_entry_helper::sub_list(array(
        'fieldname' => 'groups_user:user_id',
        'label' => lang::get('Other {1} members', self::$groupType),
        'table' => 'user',
        'captionField' => 'person_name_unique',
        'valueField' => 'id',
        'extraParams' => $auth['read'] + array('view' => 'detail'),
        'helpText' => lang::get('LANG_Members_Field_Instruct'),
        'addToTable' => FALSE,
        'class' => $class,
      ));
    }
    if ($args['include_licence']) {
      $r .= data_entry_helper::select(array(
        'blankText' => '<' . lang::get('No licence selected') . '>',
        'label' => lang::get('Licence for records'),
        'helpText' => lang::get('Choose a licence to apply to all records added explicitly to this {1}.', self::$groupType),
        'fieldname' => 'group:licence_id',
        'table' => 'licence',
        'extraParams' => $auth['read'],
        'captionField' => 'title',
        'valueField' => 'id',
        'validation' => ['required'],
      ));
    }
    if (!empty(data_entry_helper::$validation_errors['groups_user:general'])) {
      global $indicia_templates;
      $fieldname = $args['include_administrators'] ? 'groups_user:admin_user_id' :
          ($args['include_members'] ? 'groups_user:user_id' : '');
      $template = str_replace('{class}', $indicia_templates['error_class'], $indicia_templates['validation_message']);
      $template = str_replace('{for}', $fieldname, $template);
      $r .= str_replace('{error}', lang::get(data_entry_helper::$validation_errors['groups_user:general']), $template);
      $r .= '<br/>';
    }
    return $r;
  }

  /**
   * Returns controls allowing a records filter to be defined and associated with the group.
   *
   * @param array $args
   *   Form configuration arguments
   *
   * @return string
   *   HTML to output
   */
  private static function reportFilterBlock($args, $auth, &$hiddenPopupDivs) {
    $r = '';
    $hiddenPopupDivs = '';
    if ($args['include_report_filter']) {
      $r .= '<fieldset id="group-filter-fieldset"><legend>' . lang::get('Records that are of interest to the {1}', lang::get(self::$groupType)) . '</legend>';
      $r .= '<p>' . lang::get('LANG_Filter_Instruct', lang::get(self::$groupType), lang::get(self::$groupType . "'s")) . '</p>';
      $indexedLocationTypeIds = array_map('intval', explode(',', $args['indexed_location_type_ids']));
      $otherLocationTypeIds = array_map('intval', explode(',', $args['other_location_type_ids']));
      $options = array(
        'allowLoad' => FALSE,
        'allowSave' => FALSE,
        'filterTypes' => $args['filter_types'],
        'embedInExistingForm' => TRUE,
        'indexedLocationTypeIds' => $indexedLocationTypeIds,
        'otherLocationTypeIds' => $otherLocationTypeIds,
        'includeSitesCreatedByUser' => $args['include_sites_created_by_user'],
      );
      if (!empty($args['taxon_list_id']) && preg_match('/^\d+$/', trim($args['taxon_list_id']))) {
        $options['taxon_list_id'] = $args['taxon_list_id'];
      }
      $r .= report_filter_panel($auth['read'], $options, $args['website_id'], $hiddenPopupDivs);
      // Fields to auto-create a filter record for this group's defined set of
      // records.
      $r .= data_entry_helper::hidden_text(array('fieldname' => 'filter:id'));
      $r .= '<input type="hidden" name="filter:title" id="filter-title-val"/>';
      $r .= '<input type="hidden" name="filter:definition" id="filter-def-val"/>';
      $r .= '<input type="hidden" name="filter:sharing" value="R"/>';
      $r .= '</fieldset>';
    }
    return $r;
  }

  /**
   * Converts the posted form values for a group into a warehouse submission.
   * @param array $values
   *   Form values.
   * @param array $args
   *   Form configuration arguments.
   * @return array
   *   Submission data.
   *
   * @todo On resave, clear any unchecked multiple parents
   */
  public static function get_submission($values, $args) {
    $struct = array(
      'model' => 'group'
    );
    if (!empty($values['filter:title']))
      $struct['superModels'] = array(
        'filter' => array('fk' => 'filter_id')
      );
    // for new group records, auto join to the parent identified by from_group_Id
    if (!empty($args['parent_group_relationship_type']) && !empty($_REQUEST['from_group_id']) &&
        empty($_GET['group_id'])) {
      // $from_group_id could be posted in the form if user selectable or provided in the URL if fixed.
      $from_group_id = empty($_GET['from_group_id'])
          ? $_POST['from_group_id']
          : $_GET['from_group_id'];
      $struct['subModels'] = array(
        'group_relation' => array('fk' => 'to_group_id')
      );
      $values['group_relation:from_group_id'] = $from_group_id;
      $values['group_relation:relationship_type_id'] = $args['parent_group_relationship_type'];
    }
    $s = submission_builder::build_submission($values, $struct);
    // Add in any additional parents (if multiple parents allowed).
    if (!empty($args['parent_group_relationship_type']) && !empty($args['can_attach_to_multiple_parents'])) {
      $parentKeys = preg_grep('/^parent_group:\d*:\d+$/', array_keys($values));
      foreach ($parentKeys as $key) {
        preg_match('/^parent_group:(?P<group_relation_id>\d*):(?P<parent_group_id>\d+)$/', $key, $matches);
        // if a checked parent, or a previously existing one that is now unchecked
        if ($values[$key] === '1' || !empty($matches['group_relation_id'])) {
          $fields = array(
            'website_id' => array('value' => $args['website_id']),
            'from_group_id' => array('value' => $matches['parent_group_id']),
            'relationship_type_id' => array('value' => $args['parent_group_relationship_type']),
          );
          if ($values[$key] === '0') {
            $fields['deleted'] = 't';
          }
          if (!empty($matches['group_relation_id'])) {
            $fields['id'] = $matches['group_relation_id'];
          }
          $s['subModels'][] = array(
            'fkId' => 'to_group_id',
            'model' => [
              'id' => 'group_relation',
              'fields' => $fields,
            ],
          );
        }
      }
    }
    // Scan the posted values for group pages. This search grabs the first
    // column value keys, or if this is disabled then the hidden deleted field.
    $pageKeys = preg_grep('/^group\+:pages:\d*:\d+:(0|deleted)$/', array_keys($values));
    $pages = [];
    foreach ($pageKeys as $key) {
      // Skip empty rows, unless they were rows loaded for an existing
      // group_pages record. Also skip deletions of non-existing rows or
      // non-deletions of any row.
      if ((!empty($values[$key]) || preg_match('/^group\+:pages:(\d+)/', $key))
          && !preg_match('/::(\d+):deleted$/', $key)
          && !(preg_match('/:deleted$/', $key) && $values[$key]==='f')) {
        // Get the key without the column index, so we can access any column we want.
        $base = preg_replace('/(0|deleted)$/', '', $key);
        if ((isset($values[$base . 'deleted']) && $values[$base . 'deleted']==='t') || empty($values[$base . '0'])) {
          $page = ['deleted' => 't'];
        }
        else {
          $tokens = explode(':', $values[$base.'0']);
          $path = $tokens[0];
          $caption = empty($values[$base . '1']) ? $tokens[1] : $values[$base . '1'];
          $administrator = explode(':', $values[$base.'2']);
          $administrator = empty($administrator) ? NULL : $administrator[0];
          $access_level = isset($values[$base . '3']) ? explode(':', $values[$base . '3']) : NULL;
          $access_level = empty($access_level) ? NULL : $access_level[0];
          $page = [
            'caption' => $caption,
            'path' => $path,
            'administrator' => $administrator,
            'access_level' => $access_level,
          ];
        }
        // If existing group page, hook up to the id.
        if (preg_match('/^group\+:pages:(\d+)/', $key, $matches)) {
          $page['id'] = $matches[1];
        }
        $pages[] = $page;
      }
    }
    if (!empty($pages)) {
      if (!isset($s['subModels'])) {
        $s['subModels'] = [];
      }
      foreach ($pages as $page) {
        $s['subModels'][] = [
          'fkId' => 'group_id',
          'model' => ['id' => 'group_page', 'fields' => $page],
        ];
      }
    }
    // Need to manually build the submission for the admins sub_list, since we
    // are hijacking what is intended to be a custom attribute control.
    if (self::extractUserInfoFromFormValues($s, $values, 'admin_user_id', 't') === 0 && empty($values['group:id'])) {
      // No admins created when setting up the group initially, so need to set
      // the current user as an admin.
      $s['subModels'][] = [
        'fkId' => 'group_id',
        'model' => submission_builder::wrap([
          'user_id' => hostsite_get_user_field('indicia_user_id'),
          'administrator' => 't',
        ], 'groups_user'),
      ];
    };
    self::extractUserInfoFromFormValues($s, $values, 'user_id', 'f');
    self::deleteExistingUsers($s, $values);
    return $s;
  }

  private static function deleteExistingUsers(&$s, $values) {
    $existingUsers = preg_grep("/^groups_user\:user_id\:[0-9]+$/", array_keys($values));
    // For existing, we just need to look for deletions which will have an
    // empty value.
    foreach ($existingUsers as $user) {
      if (empty($values[$user])) {
        $id = substr($user, 20);
        $s['subModels'][] = [
          'fkId' => 'group_id',
          'model' => submission_builder::wrap(['id' => $id, 'deleted' => 't'], 'groups_user'),
        ];
      }
    }
  }

  /**
   * Extracts the sub-models required to populate member and administrator info from the form data.
   */
  private static function extractUserInfoFromFormValues(&$s, $values, $fieldname, $isAdmin) {
    $count = 0;
    if (!empty($values["groups_user:$fieldname"])) {
      if (!isset($s['subModels'])) {
        $s['subModels'] = [];
      }
      if (!empty($values["groups_user:$fieldname"])) {
        foreach ($values["groups_user:$fieldname"] as $userId) {
          if ($userId) {
            $values = ['user_id' => $userId, 'administrator' => $isAdmin];
            $s['subModels'][] = [
              'fkId' => 'group_id',
              'model' => submission_builder::wrap($values, 'groups_user')
            ];
            $count++;
          }
        }
      }
    }
    return $count;
  }

  /**
   * Perform some duplication checking on the members list.
   */
  public static function get_validation_errors($values) {
    $duplicate = FALSE;
    $existingUsers = preg_grep("/^groups_user\:user_id\:[0-9]+$/", array_keys($values));
    $newUsers = preg_grep("/^groups_user\:(admin_)?user_id$/", array_keys($values));
    $users = array_merge(array_values($existingUsers), array_values($newUsers));
    if (count($users)) {
      $userData = array_intersect_key($values, array_combine($users, $users));
      $foundUsers = [];
      foreach ($userData as $value) {
        if (is_array($value)) {
          foreach ($value as $item) {
            if (in_array($item, $foundUsers)) {
              $duplicate = TRUE;
            }
            $foundUsers[] = $item;
          }
        }
        else {
          if (in_array($value, $foundUsers)) {
            $duplicate = TRUE;
          }
          $foundUsers[] = $value;
        }
      }
      if ($duplicate) {
        return ['groups_user:general' => lang::get("Please ensure that the list of administrators and group members only includes each person once.")];
      }
    }
    // Default is no errors.
    return [];
  }

  /**
   * Retrieve the path to the current page, so the form can submit to itself.
   *
   * @return string
   *   Reload path.
   */
  private static function getReloadPath() {
    $reload = data_entry_helper::get_reload_link_parts();
    $reloadPath = $reload['path'];
    if (count($reload['params'])) {
      // Decode params prior to encoding to prevent double encoding.
      foreach ($reload['params'] as $key => $param) {
        $reload['params'][$key] = urldecode($param);
      }
      $reloadPath .= '?' . http_build_query($reload['params']);
    }
    return $reloadPath;
  }

  /**
   * Fetch an existing group's information from the database when editing.
   *
   * @param int $id
   *   Group ID.
   * @param array $auth
   *   Authorisation tokens.
   * @param $args
   *
   * @throws \exception
   */
  private static function loadExistingGroup($id, $auth, $args) {
    $group = data_entry_helper::get_population_data([
      'table' => 'group',
      'extraParams' => $auth['read'] + ['view' => 'detail', 'id' => $id],
      'nocache' => TRUE,
    ]);
    if (empty($group)) {
      hostsite_show_message("The group with ID $id could not be found.");
      return;
    }
    $group = $group[0];
    self::checkAdminRights($group, $args, $auth);
    data_entry_helper::$entity_to_load = []
      'group:id' => $group['id'],
      'group:title' => $group['title'],
      'group:code' => $group['code'],
      'group:group_type_id' => $group['group_type_id'],
      'group:joining_method' => $group['joining_method'],
      'group:description' => $group['description'],
      'group:from_date' => $group['from_date'],
      'group:to_date' => $group['to_date'],
      'group:view_full_precision' => $group['view_full_precision'],
      'group:private_records' => $group['private_records'],
      'group:filter_id' => $group['filter_id'],
      'group:logo_path' => $group['logo_path'],
      'group:implicit_record_inclusion' => $group['implicit_record_inclusion'],
      'group:licence_id' => $group['licence_id'],
      'filter:id' => $group['filter_id'],
    ];
    if ($args['include_report_filter']) {
      $def = $group['filter_definition'] ? $group['filter_definition'] : '{}';
      data_entry_helper::$javascript .=
          "indiciaData.filter.def=$def;\n";
    }
    if ($args['include_administrators'] || $args['include_members']) {
      $members = data_entry_helper::get_population_data(array(
        'table' => 'groups_user',
        'extraParams' => $auth['read'] + array('view' => 'detail', 'group_id' => $id),
        'nocache' => TRUE
      ));
      $admins = [];
      $others = [];
      foreach ($members as $member) {
        if ($member['administrator'] === 't') {
          $admins[] = array(
              'fieldname' => 'groups_user:user_id:' . $member['id'],
              'caption' => $member['person_name_unique'],
              'default' => $member['user_id']
          );
        }
        else {
          $others[] = array(
              'fieldname' => 'groups_user:user_id:' . $member['id'],
              'caption' => $member['person_name_unique'],
              'default' => $member['user_id']
          );
        }
      }
      data_entry_helper::$entity_to_load['groups_user:admin_user_id'] = $admins;
      data_entry_helper::$entity_to_load['groups_user:user_id'] = $others;
    }
  }

  /**
   * Checks that the user is allowed to administer this group and throws an exception if not. The user can be granted
   * admin rights either by explicitly setting the flag in their groups_users record, being the original creator of the
   * group, having 'IForm groups admin' permissions, or being an admin of a parent group if the inherit_admin_privileges
   * flag is set.
   * @param $group
   * @param $args
   * @param $auth
   * @throws \exception
   */
  private static function checkAdminRights($group, $args, $auth) {
    // Check permissions. The group creator or people with global groups admin permissions get a pass.
    if ($group['created_by_id'] !== hostsite_get_user_field('indicia_user_id') &&
      !hostsite_user_has_permission('IForm groups admin')) {
      // User did not create group. So, check they are an admin, either in just this group, or if the option is
      // enabled also look in the hierarchical parents.
      if (!empty($args['inherit_admin_privileges']) && $args['inherit_admin_privileges']) {
        $groupUsersCheck = report_helper::get_report_data(array(
          'dataSource' => 'library/groups/group_membership_by_parents',
          'readAuth' => $auth['read'],
          'extraParams' => array(
            'group_id' => $group['id'],
            'user_id' => hostsite_get_user_field('indicia_user_id')
          )
        ));
        $isAdmin = $groupUsersCheck[0]['admin'] === 't';
      }
      else {
        $groupUsersCheck = data_entry_helper::get_population_data(array(
          'table' => 'groups_user',
          'extraParams' => $auth['read'] + array(
              'group_id' => $group['id'],
              'administrator' => 't',
              'user_id' => hostsite_get_user_field('indicia_user_id')
            ),
          'nocache' => TRUE
        ));
        $isAdmin = count($groupUsersCheck);
      }
      if (!$isAdmin)
        throw new exception(lang::get('You are trying to edit a group you don\'t have admin rights to.'));
    }
  }

  public static function get_perms() {
    return array('IForm groups admin');
  }

  private static function createBreadcrumb($args, $auth) {
    if (!empty($args['groups_page_path']) && function_exists('hostsite_set_breadcrumb') && function_exists('drupal_get_normal_path')) {
      $path = drupal_get_normal_path($args['groups_page_path']);
      $node = menu_get_object('node', 1, $path);
      $parentPageTitle = $node->title;
      if (strpos($parentPageTitle, '{group}') !== FALSE && !empty($_GET['dynamic-from_group_id'])) {
        // Parent page title has a token in it that should be replaced by the
        // group's title.
        $data = data_entry_helper::get_population_data(array(
          'table' => 'group',
          'extraParams' => $auth['read'] + array('id' => $_GET['dynamic-from_group_id'])
        ));
        if (count($data)) {
          $parentPageTitle = str_replace('{group}', $data[0]['title'], $parentPageTitle);
        }
      }
      $breadcrumb[$parentPageTitle] = $args['groups_page_path'];
      hostsite_set_breadcrumb($breadcrumb);
    }
  }

  /**
   * Ajax handler allowing the sub list controls for member lookup to be extended to
   * search for people by email address.
   * @param $website_id
   * @param $password
   * @param $nid
   * @throws \Exception
   */
  public static function ajax_lookup_email($website_id, $password) {
    if (empty($_GET['email'])) {
      echo 'Email value not provided';
      return;
    }
    iform_load_helpers(array('data_entry_helper'));
    $readAuth = data_entry_helper::get_read_auth($website_id, $password);
    $data = data_entry_helper::get_population_data(array(
      'table' => 'user',
      'extraParams' => $readAuth + array('view' => 'detail', 'email_address' => $_GET['email'])
    ));
    echo json_encode($data);
  }

}
