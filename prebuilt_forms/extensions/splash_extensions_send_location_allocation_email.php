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

/*
 * Send an email to a user who has been allocated a location
 */
if (isset($_REQUEST['personName'])&&isset($_REQUEST['subject'])&&isset($_REQUEST['message'])
        &&isset($_REQUEST['emailTo'])&&isset($_REQUEST['locationName'])) {
  $personName = $_REQUEST['personName'];
  $subject = $_REQUEST['subject'];
  $message = $_REQUEST['message'];
  $emailTo = $_REQUEST['emailTo'];
  $locationName = $_REQUEST['locationName'];
  //Replacements for the person's name and the location name tags in the message with the real location and person name.
  $message = str_replace("{person_name}", $personName, $message);
  $message = str_replace("{location_name}", $locationName, $message);

  $sent = mail($emailTo, $subject, wordwrap($message, 70));
  if ($sent) {
    echo 'sent';
  } else {
    echo 'not sent';
  }
} else {
  echo 'not sent';
}
