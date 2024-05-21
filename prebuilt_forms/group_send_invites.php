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

require_once 'includes/report_filters.php';

/**
 * A form that allows a group admin to send email invitations to a group.
 *
 * A page for send invites to a user group.
 */
class iform_group_send_invites {

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_group_send_invites_definition() {
    return [
      'title' => 'Send invites to a group',
      'category' => 'Recording groups',
      'description' => 'A form for emailing out invites to recording groups.',
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
    return [
      [
        'name' => 'accept_invite_path',
        'caption' => 'Accept Invite Path',
        'description' => 'Path to the Drupal page which invitation acceptances should be routed to.',
        'type' => 'text_input',
      ],
    ];
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
    global $indicia_templates;
    $reloadPath = self::getReloadPath();
    data_entry_helper::$website_id = $args['website_id'];
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    $group = self::loadGroup($auth);
    if (!empty($_POST['invitee_emails'])) {
      self::sendInvites($args, $auth);
    }
    $r = "<form method=\"post\" id=\"entry_form\" action=\"$reloadPath\">\n";
    $r .= data_entry_helper::textarea([
      'label' => lang::get('Send invites to'),
      'helpText' => 'Enter email addresses for the people you want to invite, one per line',
      'fieldname' => 'invitee_emails',
      'validation' => ['required'],
    ]);
    $r .= data_entry_helper::textarea([
      'label' => lang::get('Invitation message'),
      'helpText' => 'What message would you like to send to your invitees?',
      'fieldname' => 'invite_message',
      'validation' => ['required'],
      'default' => lang::get('Would you like to join the {1}?', $group['title']),
    ]);
    $r .= '<button type="submit" class="' . $indicia_templates['buttonDefaultClass'] . '" id="save-button">' .
      lang::get('Send Invites') . "</button>\n";
    $r .= '<button type="button" class="' . $indicia_templates['buttonDefaultClass'] . '" id="not-now-button" ' .
        'onclick="window.location.href=\'' . hostsite_get_url($args['redirect_on_success']) . '\'">' . lang::get('Not Now') . "</button>\n";
    $r .= '</form>';
    data_entry_helper::enable_validation('entry_form');
    return $r;
  }

  /**
   * Loads the group record from the database.
   *
   * Also checks that the user is an admin of the group.
   *
   * @param array $auth
   *   Authorisation tokens.
   *
   * @return array
   *   Group record loaded from the db
   */
  private static function loadGroup(array $auth) {
    if (empty($_GET['group_id']) && !empty($_GET['id'])) {
      $_GET['group_id'] = $_GET['id'];
    }
    if (empty($_GET['group_id'])) {
      throw new exception('Form must be called with an group_id parameter for the group.');
    }
    // Check the logged in user is admin of this group.
    $response = data_entry_helper::get_population_data([
      'table' => 'groups_user',
      'extraParams' => $auth['read'] + [
        'group_id' => $_GET['group_id'],
        'user_id' => hostsite_get_user_field('indicia_user_id'),
      ],
      'nocache' => TRUE,
    ]);
    if (count($response) === 0 || $response[0]['administrator'] === 'f') {
      throw new exception('Attempt to send invites for a group you are not administrator of.');
    }
    $response = data_entry_helper::get_population_data([
      'table' => 'group',
      'extraParams' => $auth['read'] + ['id' => $_GET['group_id']],
      'nocache' => TRUE,
    ]);
    return $response[0];
  }

  /**
   * Retrieve the path to the current page, so the form can submit to itself.
   *
   * @return string
   */
  private static function getReloadPath () {
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
   * Performs the sending of invitation emails.
   *
   * @param array $args
   *   Form configuration arguments.
   * @param array $auth
   *   Authorisation tokens.
   *
   * @todo Integrate with notifications for logged in users.
   */
  private static function sendInvites(array $args, array $auth) {
    $lang = [
      'acceptInvitiation' => lang::get('Accept this invitation'),
      'invitationToJoinRecordingGroup' => lang::get('Invitation to join a recording group'),
    ];
    $emails = helper_base::explode_lines($_POST['invitee_emails']);
    // First task is to populate the groups_invitations table.
    $base = uniqid();
    $success = TRUE;
    $failedRecipients = [];
    foreach ($emails as $idx => $email) {
      if (!empty($email)) {
        $trimmedEmail = trim($email);
      }
      if (!empty($trimmedEmail)) {
        $values = [
          'group_invitation:group_id' => $_GET['group_id'],
          'group_invitation:email' => $trimmedEmail,
          'group_invitation:token' => $base . $idx,
          'website_id' => $args['website_id'],
        ];
        $s = submission_builder::build_submission($values, ['model' => 'group_invitation']);
        $auth['write_tokens']['persist_auth'] = $idx < count($emails) - 1;
        data_entry_helper::forward_post_to('group_invitation', $s, $auth['write_tokens']);
        $rootFolder = data_entry_helper::getRootFolder(TRUE);
        $protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $clean = strpos($rootFolder, '?') === FALSE;
        $acceptUrl = $protocol . $_SERVER['HTTP_HOST'] . $rootFolder . $args['accept_invite_path'] . ($clean ? '?' : '&') . 'token=' . $base . $idx;
        $messageHtml = str_replace("\n", '<br/>', $_POST['invite_message']);
        $emailHtml = <<<HTML
          $messageHtml
          <br/>
          <br/>
          <a href="$acceptUrl">$lang[acceptInvitiation]</a>
HTML;
        // Send email. Depends upon settings in php.ini being correct.
        $thismailsuccess = hostsite_send_email($trimmedEmail, $lang['invitationToJoinRecordingGroup'], $emailHtml);
        if (!$thismailsuccess) {
          $failedRecipients[$trimmedEmail] = $acceptUrl;
        }
        $success = $success && $thismailsuccess;
      }
    }
    if ($success) {
      hostsite_show_message(lang::get('Invitation emails sent'));
    }
    else {
      hostsite_show_message(lang::get('The emails could not be sent due to a server configuration issue. Please contact the site admin. ' .
          'The list below gives the emails and the links you need to send to each invitee which they need to click on in order to join the group.'), 'warning');
      $list = [];
      foreach ($failedRecipients as $email => $link) {
        $list[] = lang::get("Send link {1} to {2}.", $link, $email);
      }
      hostsite_show_message(implode('<br/>', $list), 'warning');
    }
    hostsite_goto_page($args['redirect_on_success']);
  }

}
