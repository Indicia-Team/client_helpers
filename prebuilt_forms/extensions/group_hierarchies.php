<?php

class extension_group_hierarchies {

  /**
   * Outputs a link from a groups list page to a create group edit page. Can be used
   * when showing the list of child groups for a selected group (e.g. projects for
   * a consultancy, or centres for an organisation). If so, then the link is only
   * shown if you are admin of that group.
   * @param $auth
   * @param $args
   * @param $tabalias
   * @param array $options Options array with the following parameters:
   *   * caption - string caption to ouput.
   *   * class - CSS class to add to the link.
   *   * parent_parameter - name of the parameter in the query string that holds the parent
   *     group ID. If specified, then you must be an admin of that group for the link
   *     to be output.
   *   * path - string, path to link to.
   * @param $path
   */
  public static function create_group_link($auth, $args, $tabalias, $options, $path) {
    if (empty($options['path']) || empty($options['caption']))
      return 'group_hierarchies.create_group_link control requires a path and caption option.';
    // Are we linking to a parent group to check permissions to do this?
    if (!empty($options['parent_parameter'])) {
      if (empty($_GET[$options['parent_parameter']]) || !is_numeric($_GET[$options['parent_parameter']]))
        return '';
      $data = data_entry_helper::get_population_data(array(
        'table' => 'groups_user',
        'extraParams' => $auth['read'] + array(
            'user_id' => hostsite_get_user_field('indicia_user_id'),
            'group_id' => $_GET[$options['parent_parameter']],
            'administrator' => 't'
          )
      ));
      if (empty($data)) {
        return '';
      }
    }
    $rootFolder = data_entry_helper::getRootFolder(true);
    $path = str_replace('{rootFolder}', $rootFolder, $options['path']);
    if (!empty($options['parent_parameter'])) {
      $sep = strpos($rootFolder, '?')===FALSE ? '?' : '&';
      $path .= $sep . 'from_group_id=' . $_GET[$options['parent_parameter']];
      // also pass through the actual parent parameter as it is, so that the current page can
      // reload OK after saving.
      if ($options['parent_parameter']!=='from_group_id')
        $path .= "&$options[parent_parameter]=" . $_GET[$options['parent_parameter']];
    }
    $class = empty($options['class']) ? '' : " class=\"$options[class]\"";
    $r = "<a href=\"$path\"$class>$options[caption]</a>";
    return $r;
  }

  /**
   * Replaces {group} in the page title with the title of a parent group.
   *
   * Used on a page which lists the child groups of a parent. Options include:
   * * parent_parameter - set to the name of a URL query parameter that
   *   contains the parent group ID.
   */
  public static function set_page_title($auth, $args, $tabalias, $options, $path) {
    if (empty($options['parent_parameter']) || empty($_GET[$options['parent_parameter']]) ||
      !is_numeric($_GET[$options['parent_parameter']])
    ) {
      return 'group_hierarchies.set_page_title control requires a numeric parent_parameter option which matches to a URL parameter of the same name.';
    }
    if (!empty($options['nid'])) {
      $data = data_entry_helper::get_population_data([
        'table' => 'group',
        'extraParams' => $auth['read'] + ['id' => $_GET[$options['parent_parameter']]],
      ]);
      if (count($data)) {
        $title = hostsite_get_page_title($options['nid']);
        $title = str_replace('{group}', $data[0]['title'], $title);
        hostsite_set_page_title($title);
      }
      return '';
    }
  }

  /**
   * Adds a Drupal breadcrumb to the page.
   *
   * Wrapper for misc_extensions.breadcrumb with extra support for hierarchies
   * of groups.
   * The $options array can contain the following parameters:
   * * path - an associative array of paths and captions. The paths can contain replacements
   *   wrapped in # characters which will be replaced by the $_GET parameter of the same name.
   *   Either the key or the value in this array can contain additional replacements {parent_title}
   *   and {parent_id} which are replaced by the group title and ID respectively for the parent
   *   of the group loaded onto the current page by the dynamic-from_group_id parameter.
   * * includeCurrentPage - set to false to disable addition of the current page title to the end
   *   of the breadcrumb.
   */
  public static function breadcrumb($auth, $args, $tabalias, $options, $path) {
    require_once 'misc_extensions.php';
    if (!empty($options['path']) && !empty($_GET['dynamic-from_group_id'])) {
      iform_load_helpers(['report_helper']);
      $parent = report_helper::get_report_data([
        'dataSource' => 'library/groups/groups_list',
        'readAuth' => $auth['read'],
        'extraParams' => ['to_group_id' => $_GET['dynamic-from_group_id'], 'userFilterMode' => 'all', 'currentUser' => ''],
        'caching' => TRUE,
        'cachePerUser' => FALSE,
      ]);
      if (count($parent)) {
        $path = [];
        foreach ($options['path'] as $key => $value) {
          $key = str_replace(['{parent}', '{parent_id}'], [$parent[0]['title'], $parent[0]['id']], $key);
          $value = str_replace(['{parent}', '{parent_id}'], [$parent[0]['title'], $parent[0]['id']], $value);
          $path[$key] = $value;
        }
      }
      $options['path'] = $path;
    }
    return extension_misc_extensions::breadcrumb($auth, $args, $tabalias, $options, $path);
  }

}