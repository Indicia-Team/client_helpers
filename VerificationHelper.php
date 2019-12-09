<?php

/**
 * @file
 * A helper class for Verification related code.
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

class VerificationHelper {

  public static function doesUserSeeNotifications($readAuth, $userId) {
    iform_load_helpers(['report_helper']);
    $data = report_helper::get_report_data(array(
      'dataSource' => 'library/users/user_notification_response_likely',
      'readAuth' => $readAuth,
      'extraParams' => array('user_id' => $userId)
    ));
    $acknowledged = 0;
    $unacknowledged = 0;
    $emailFrequency = FALSE;
    foreach ($data as $row) {
      if ($row['key'] === 'acknowledged') {
        $acknowledged = (int) $row['value'];
      }
      elseif ($row['key'] === 'unacknowledged') {
        $unacknowledged = (int) $row['value'];
      }
      elseif ($row['key'] === 'email_frequency') {
        $emailFrequency = $row['value'];
      }
    }
    if ($emailFrequency) {
      // If they receive emails for comment notifications, we can assume they will see a comment.
      return 'yes';
    }
    elseif ($acknowledged + $unacknowledged > 0) {
      // otherwise, we need some info on the ratio of acknowledged to unacknowledged notifications over the last year
      $ratio = $acknowledged / ($acknowledged + $unacknowledged);
      if ($ratio > 0.3) {
        return 'yes';
      }
      elseif ($ratio === 0) {
        return 'no';
      }
      else {
        return 'maybe';
      }
    }
    else {
      // They don't have notifications in database, so we can't say.
      return 'unknown';
    }
  }

}