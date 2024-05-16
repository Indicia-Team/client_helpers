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
 * @link https://github.com/Indicia-Team/client_helpers
 */

/**
 * Extension class providing controls for viewing system or user notifications.
 */
class extension_notifications_centre {

  static $initialised = FALSE;

  static $dataServicesUrl;

  /*
   * Draw the control that displays auto-check notifications.
   *
   * Pass the following options:
   * * @default_edit_page_path = path to the default page to load for record
   *   editing, where the input form used is unknown (normally this affects old
   *   records only).
   * * @view_record_page_path = path to the view record details page.
   */
  public static function auto_check_messages_grid($auth, $args, $tabalias, $options, $path) {
    // Set default to show comment and verification notifications.
    $options = array_merge([
      'id' => 'auto-check-notifications',
      'title' => 'automatic check notifications',
      'sourceType' => 'A',
      'allowReply' => FALSE,
      'allowEditRecord' => TRUE,
    ], $options);
    return self::messages_grid($auth, $args, $tabalias, $options, $path);
  }

  /*
   * Draw the control that displays user notifications.
   *
   * These are notifications of source type 'C' or 'V' (comments and
   * verifications), 'S' species alerts, 'VT' verification task, 'GU' pending
   * members in groups you administer, 'AC  by default - control this using the
   * @sourceType option.
   *
   * Pass the following options:
   * * @default_edit_page_path = path to the default page to load for record
   *   editing, where the input form used is unknown (normally this affects old
   *   records only).
   * * @view_record_page_path = path to the view record details page.
   */
  public static function user_messages_grid($auth, $args, $tabalias, $options, $path) {
    // Set default to show comment and verification notifications.
    $options = array_merge([
      'id' => 'user-notifications',
      'title' => 'user message notifications',
      'sourceType' => 'C,V,S,VT,GU,M',
      'allowReply' => TRUE,
      'allowEditRecord' => TRUE,
    ], $options);
    return self::messages_grid($auth, $args, $tabalias, $options, $path);
  }

  /**
   * Outputs HTML for a generic notifications grid.
   *
   * Use options @sourceType=S,V to show specific source types on the grid, the
   * "S,V" in this example can be replaced with any source_type letter comma
   * separated list. Removing the @sourceType option will display a filter
   * drop-down that the user can select from.
   *
   * Set @manage_members_page_path to specify the path to a page for managing
   * activity/group members, which will be used for notifications to group
   * admins when there is a pending membership request.
   *
   * Use the @dataSource option to override the default report used to display
   * the grid
   */
  public static function messages_grid($auth, $args, $tabalias, $options, $path) {
    if (empty($options['id'])) {
      $options['id'] = 'notifications-grid';
    }
    $indicia_user_id = hostsite_get_user_field('indicia_user_id');
    if (empty($options['dataSource'])) {
      $options['dataSource'] = 'library/notifications/notifications_list_for_notifications_centre';
    }
    self::initialise($auth, $args, $tabalias, $options, $path, $indicia_user_id);
    if ($indicia_user_id) {
      return self::notifications_grid($auth, $options, $args['website_id'], $indicia_user_id);
    }
    else {
      return '<p>' . lang::get('The notifications system will be enabled when you fill in at least your surname on your account.') . '</p>';
    }
  }

  private static function initialise($auth, $args, $tabalias, &$options, $path, $user_id) {
    if (!self::$initialised) {
      $indicia_user_id = hostsite_get_user_field('indicia_user_id');
      if ($indicia_user_id) {
        iform_load_helpers(['report_helper']);
        // The proxy url used when interacting with the notifications table in
        // the database.
        report_helper::$javascript .= "indiciaData.notification_proxy_url = '" . iform_ajaxproxy_url(NULL, 'notification') . "';\n";
        // The proxy url used when interacting with the occurrence comment
        // table in the database.
        report_helper::$javascript .= "indiciaData.occurrence_comment_proxy_url = '" . iform_ajaxproxy_url(NULL, 'occ-comment') . "';\n";
        // The url used for direct access to data services.
        self::$dataServicesUrl = data_entry_helper::getProxiedBaseUrl() . "index.php/services/data";
        report_helper::$javascript .= "indiciaData.data_services_url = '" . self::$dataServicesUrl . "';\n";
        // If the user clicks the Acknowlegde Notifications submit button, then a
        // hidden field called acknowledge-notifications is set. We can check for
        // this when the page reloads and then call the remove notifications
        // code.
        if (!empty($_POST['acknowledge-notifications']) && $_POST['acknowledge-notifications'] == 1) {
          self::acknowledgeNotificationsInGrid($user_id, $args, $options);
        }
      }

      // Build $options['columns'] from $args.
      if (array_key_exists('columns_config_list', $args)) {
        // From e.g.Dynamic report explorer which supports multiple grids.
        $columnLists = json_decode($args['columns_config_list'], TRUE);
        if (!empty($columnsLists)) {
          // Assume we want the config for the first grid.
          $options['columns'] = $columnLists[0];
        }
      }
      elseif (array_key_exists('columns_config', $args)) {
        // From e.g. Report grid or the above.
        $options['columns'] = json_decode($args['columns_config'], TRUE);
      }

      self::$initialised = TRUE;
    }
  }

  /**
   * Return the actual grid code.
   */
  private static function notifications_grid($auth, $options, $website_id, $user_id) {
    iform_load_helpers(['report_helper']);
    // $sourceType is a user provided option for the grid to preload rather
    // than the user selecting from the filter drop-down. When the source types
    // are provided like this, the filter drop-down is not displayed.
    // There can be more than one sourcetype, this is supplied as a comma-
    // seperated list and needs putting into an array.
    $sourceType = empty($options['sourceType']) ? [] : explode(',', $options['sourceType']);
    // Reload path to current page.
    $reloadPath = self::getReloadPath();
    $r = self::getNotificationsHtml($auth, $sourceType, $website_id, $user_id, $options);
    $r .= "<form method = \"POST\" action=\"$reloadPath\">\n";
    // Hidden field is set when Remove Notifications for user notifications is
    // clicked, when the page reloads this is then checked for.
    $r .= '<input type="hidden" name="acknowledge-notifications" class="acknowledge-notifications"/>';
    // A hidden input to pass the current source type filter through.
    $sourceFilter = $_POST['notifications_' . preg_replace('/[^a-z0-9]+/', '_', $options['id']) . '-source_filter'] ?? 'all';
    $r .= "<input type=\"hidden\" name=\"source-filter\" value=\"$sourceFilter\" />";
    // Plus a hidden input to pass the current filter row values through.
    $r .= '<input type="hidden" name="filter-row-data"/>';
    $r .= self::acknowledgeButton($options);
    $r .= "</form>";
    return "<div class=\"notifications-cntr\">$r</div>";
  }

  /**
   * Acknowledges the notifications that were visible in the grid.
   *
   * @param int $user_id
   *   User's warehouse ID.
   * @param array $args
   *   Form configuration arguments.
   * @param array $options
   *   Control options.
   */
  private static function acknowledgeNotificationsInGrid($user_id, array $args, array $options) {
    // Rebuild the auth token since this is a reporting page but we need to
    // submit data.
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    // Using 'submission_list' and 'entries' allows us to specify several top-
    // level submissions to the system, i.e. we need to be able to submit
    // several notifications.
    $submission['submission_list']['entries'] = [];
    $submission['id'] = 'notification';
    $extraParams = [
      'user_id' => $user_id,
      'system_name' => 'indicia',
      'default_edit_page_path' => '',
      'view_record_page_path' => '',
      'website_id' => $args['website_id'],
    ];
    // If the page is using a filter drop-down option, then collect the type of
    // notification to remove from the filter drop-down.
    $extraParams['source_filter'] = empty($_POST['source-filter']) ? 'all' : $_POST['source-filter'];
    // If the page has a list of source types in the config, also apply that
    // filter.
    if (!empty($options['sourceType'])) {
      $sourceTypesToClearFromConfig = explode(',', $options['sourceType']);
      foreach ($sourceTypesToClearFromConfig as &$type) {
        $type = "'$type'";
      }
      $extraParams['source_types'] = implode(',', $sourceTypesToClearFromConfig);
    }
    // Only include notifications associated with a set of recording group ids
    // if option is supplied.
    if (!empty($options['groupIds'])) {
      $extraParams['group_ids'] = $options['groupIds'];
    }
    if (!empty($_POST['filter-row-data'])) {
      $filterRowData = json_decode($_POST['filter-row-data'], TRUE);
      foreach ($filterRowData as $col => $value) {
        $extraParams[$col] = $value;
      }
    }
    $notifications = report_helper::get_report_data([
      'dataSource' => $options['dataSource'],
      'readAuth' => $auth['read'],
      'extraParams' => $extraParams,
    ]);
    $count = 0;
    if (count($notifications) > 0) {
      // Setup the structure we need to submit.
      foreach ($notifications as $notification) {
        $data['id'] = 'notification';
        $data['fields']['id']['value'] = $notification['notification_id'];
        $data['fields']['acknowledged']['value'] = 't';
        $submission['submission_list']['entries'][] = $data;
        $count++;
      }
      // Submit the stucture for processing.
      $response = data_entry_helper::forward_post_to('save', $submission, $auth['write_tokens']);
      if (is_array($response) && array_key_exists('success', $response)) {
        if ($count === 1) {
          hostsite_show_message(lang::get("1 notification has been removed."));
        }
        else {
          hostsite_show_message(lang::get("{1} notifications have been removed.", $count));
        }
      } else {
        hostsite_show_message(print_r($response, TRUE));
      }
    }
  }

  /*
   * Build HTML for the remove notifications button.
   */
  private static function acknowledgeButton($options) {
    global $indicia_templates;
    $lang = [
      'buttonCaption' => lang::get('Acknowledge this list of notifications'),
    ];
    return <<<HTML
<input id="remove-all" type="submit"
       class="$indicia_templates[buttonDefaultClass]" value="$lang[buttonCaption]"
       onclick="return indiciaFns.acknowledgeNotificationsList('$options[id]')" />
HTML;
  }

  /*
   * Draw the notifications grid.
   */
  private static function getNotificationsHtml($auth, $sourceType, $website_id, $user_id, $options) {
    iform_load_helpers(['report_helper']);
    $imgPath = empty(data_entry_helper::$images_path) ? data_entry_helper::relative_client_helper_path() . "../media/images/" : data_entry_helper::$images_path;
    $sendReply = $imgPath . 'nuvola/mail_send-22px.png';
    $cancelReply = $imgPath . 'nuvola/mail_delete-22px.png';
    $lang = [
      'enterReply' => lang::get('Enter your reply below'),
    ];
    // When the user wants to reply to a message, we have to add a new row.
    report_helper::$javascript .= <<<JS
indiciaData.reply_to_message = function(notification_id, occurrence_id) {
  if (!$('#reply-row-'+occurrence_id).length) {
    rowHtml = '<tr id='+"reply-row-"+occurrence_id+'><td><label for="">$lang[enterReply]:</label><textarea style="width: 95%" id="reply-' +occurrence_id+'"></textarea></td>';
    rowHtml += '<td class="actions">';
    rowHtml += '<div><img class="action-button" src="$sendReply" onclick="reply('+occurrence_id+','+notification_id+',true);" title="Send reply">';
    rowHtml += '<img class="action-button" src="$cancelReply" onclick="reply('+occurrence_id+','+notification_id+',false);" title="Cancel reply">';
    rowHtml += '</div></td></tr>';
    $(rowHtml).insertAfter('tr#row'+notification_id);
    $('tr#row'+notification_id+' .action-button').hide();
  }
};

JS;

    $urlParams = ['occurrence_id' => '{occurrence_id}'];
    if (!empty($options['recordLinkingParamOverride'])) {
      $urlParams = [$options['recordLinkingParamOverride'] => '{' . $options['recordLinkingParamOverride'] . '}'];
    }
    if (!empty($_GET['group_id'])) {
      $urlParams['group_id'] = $_GET['group_id'];
    }
    $availableActions = [
      [
        'caption' => lang::get('Edit this record'),
        'class' => 'edit-notification',
        'url' => '{rootFolder}{editing_form}',
        'urlParams' => $urlParams,
        'img' => $imgPath . 'nuvola/package_editors-22px.png',
        'visibility_field' => 'editable_flag',
      ]
    ];
    $urlParams = ['occurrence_id' => '{occurrence_id}'];
    if (!empty($_GET['group_id'])) {
      $urlParams['group_id'] = $_GET['group_id'];
    }
    if (!empty($options['view_record_page_path'])) {
      $availableActions[] = [
        'caption' => lang::get('View this record'),
        'class' => 'view-notification',
        'url' => '{rootFolder}{viewing_form}',
        'urlParams' => $urlParams,
        'img' => $imgPath . 'nuvola/find-22px.png',
        'visibility_field' => 'viewable_flag',
      ];
    }
    $availableActions[] = [
      'caption' => lang::get('Mark as read'),
      'javascript' => 'remove_message({notification_id});',
      'img' => $imgPath . 'nuvola/kmail-22px.png',
    ];
    if (!empty($options['manage_members_page_path'])) {
      $availableActions[] = [
        'caption' => lang::get('Manage members'),
        'class' => 'manage-members',
        'visibility_field' => 'manage_members_flag',
        'img' => $imgPath . 'nuvola/invite-22px.png',
        'url' => '{rootFolder}' . $options['manage_members_page_path'] . '{linked_id}',
      ];
    }
    // Only allow replying for 'user' messages.
    if (isset($options['allowReply']) && $options['allowReply'] === TRUE) {
      $availableActions = array_merge($availableActions, [
        [
          'caption' => lang::get('Reply to this message'),
          'img' => $imgPath . 'nuvola/mail_reply-22px.png',
          'visibility_field' => 'reply_flag',
          'javascript' => 'indiciaData.reply_to_message(\'{notification_id}\', \'{occurrence_id}\');',
        ],
      ]);
    }
    $extraParams = [
      'user_id' => $user_id,
      'system_name' => 'indicia',
      'orderby' => 'triggered_on',
      'sortdir' => 'DESC',
      'default_edit_page_path' => $options['default_edit_page_path'] ?? '',
      'view_record_page_path' => $options['view_record_page_path'] ?? '',
      'website_id' => $website_id,
    ];
    // Implode the source types so we can submit to the database in one text
    // field.
    if (!empty($sourceType)) {
      $extraParams['source_types'] = "'" . implode("','", $sourceType) . "'";
      // If the user has supplied some config options for the different source
      // types then we don't need the source filter drop down.
      $extraParams['source_filter'] = 'all';
    }
    // Only include notifications associated with a set of recording group ids
    // if option is supplied.
    if (!empty($options['groupIds'])) {
      $extraParams['group_ids'] = $options['groupIds'];
    }
    // Other optional parameters.
    if (!empty($options['taxon_meaning_id'])) {
      $extraParams['taxon_meaning_id'] = $options['taxon_meaning_id'];
    }
    if (!empty($options['taxon_group_id'])) {
      $extraParams['taxon_group_id'] = $options['taxon_group_id'];
    }
    $columns = [
      'data' => [
        'fieldname' => 'data',
        'json' => TRUE,
        'template' => '<div class="type-{source_type}"><div class="status-{record_status}"></div></div><div class="note-type-{source_type}">{comment}</div>' .
          '<div class="comment-from helpText" style="margin-left: 34px; display: block;">from {username} on {triggered_date}</div>',
        'display' => 'Message',
      ],
      'occurrence_id' => ['fieldname' => 'occurrence_id'],
      'actions' => [
        'actions' => $availableActions,
      ],
      'triggered_date' => [
        'fieldname' => 'triggered_date',
        'visible' => FALSE,
      ],
      'linked_id' => [
        'visible' => FALSE,
      ],
    ];
    // Allow columns config to override our default setup.
    if (!empty($options['columns'])) {
      foreach ($options['columns'] as $column) {
        if (!empty($column['actions'])) {
          $columns['actions'] = $column;
        }
        elseif (!empty($column['fieldname'])) {
          $columns[$column['fieldname']] = $column;
        }
      }
    }
    $r = report_helper::report_grid([
      'id' => "notifications-$options[id]",
      'readAuth' => $auth['read'],
      'itemsPerPage' => 10,
      'dataSource' => $options['dataSource'],
      'rowId' => 'notification_id',
      'ajax' => TRUE,
      'mode' => 'report',
      'extraParams' => $extraParams,
      'paramDefaults' => ['source_filter' => 'all'],
      'paramsFormButtonCaption' => lang::get('Filter'),
      'columns' => array_values($columns),
      'fieldsetClass' => 'filter-bar',
      'responsiveOpts' => [
        'breakpoints' => [
          'phone' => 480,
          'tablet-portrait' => 768,
          'tablet-landscape' => 1024,
        ],
      ],
    ]);
    return $r;
  }

  /**
   * Get the node path to reload the page with.
   */
  protected static function getReloadPath() {
    $reload = data_entry_helper::get_reload_link_parts();
    unset($reload['params']['sample_id']);
    unset($reload['params']['occurrence_id']);
    unset($reload['params']['location_id']);
    unset($reload['params']['new']);
    unset($reload['params']['newLocation']);
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

}
