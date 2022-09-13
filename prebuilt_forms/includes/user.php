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

/**
 * List of methods that can be used for building forms that include details of the logged in CMS user in Drupal.
 */

/**
 * Returns the suggested block of form parameters required to set up the saving of the user's CMS details into the
 * correct attributes.
 */
function iform_user_get_user_parameters() {
  return array(
    array (
        'name' => 'uid_attr_id',
        'caption' => 'User ID Attribute ID',
        'description' => 'Indicia ID for the sample attribute that stores the CMS User ID.',
        'type' => 'smpAttr',
        'group' => 'Sample Attributes'
    ),
    array(
        'name' => 'username_attr_id',
        'caption' => 'Username Attribute ID',
        'description' => 'Indicia ID for the sample attribute that stores the user\'s username.',
        'type' => 'smpAttr',
        'group' => 'Sample Attributes'
    ),
    array(
        'name' => 'email_attr_id',
        'caption' => 'Email Attribute ID',
        'description' => 'Indicia ID for the sample attribute that stores the user\'s email.',
        'type' => 'smpAttr',
        'group' => 'Sample Attributes'
    )
  );
}

/**
 * Return a block of hidden inputs that embed the user's information (user id, username and email)
 * into the record.
 * @param array $args Parameters passed to the form.
 */
function iform_user_get_hidden_inputs($args) {
  $uid = hostsite_get_user_field('id');
  $email = hostsite_get_user_field('mail');
  $username = hostsite_get_user_field('name');
  $uid_attr_id = $args['uid_attr_id'];
  $email_attr_id = $args['email_attr_id'];
  $username_attr_id = $args['username_attr_id'];
  $r = "<input type=\"hidden\" name=\"smpAttr:$uid_attr_id\" value=\"$uid\" />\n";
  $r .= "<input type=\"hidden\" name=\"smpAttr:$email_attr_id\" value=\"$email\" />\n";
  $r .= "<input type=\"hidden\" name=\"smpAttr:$username_attr_id\" value=\"$username\" />\n";
  return $r;
}

/**
 * Method to read a parameter from the arguments of a form that contains a list of key=value pairs on separate lines.
 * Each value is checked for references to the user's data (either {user_id}, {username}, {email} or {profile_*})
 * and if found these substitutions are replaced.
 * @param string $listData Form argument data, with each key value pair on a separate line.
 * @return array Associative array.
 */
function get_options_array_with_user_data($listData) {
  $r = [];
  if ($listData != ''){
    $params = helper_base::explode_lines($listData);
    foreach ($params as $param) {
      if (!empty($param)) {
        $tokens = explode('=', $param, 2);
        if (count($tokens)==2) {
          $tokens[1] = apply_user_replacements($tokens[1]);
        } else {
          throw new Exception('Some of the preset or default parameters defined for this page are not of the form param=value.');
        }
        $r[$tokens[0]]=$tokens[1];
      }
    }
  }
  return $r;
}

/**
 * Takes a piece of configuration text and replaces tokens with the relevant user profile information. The following
 * replacements are applied:
 * {user_id} - the content management system User ID.
 * {username} - the content management system username.
 * {email} - the email address stored for the user in the content management system.
 * {profile_*} - the respective field from the user profile stored in the content management system.
 * [permission] - does the user have this permission? Replaces with 1 if they have the permission, else 0.
 *
 * Can handle text and serialised arrays (which are returned as comma separated list), and also Drupal
 * vocabulary profile data: these are returned as stdClass objects from the hostsite_get_user_field call.
 * There are two possibilities for what the user may want to store when it comes to vocab data: either
 * the tid or the name (actual text value). The default is 'tid' (the vocabulary term id): this can be
 * overriden by appending :name to the field name - e.g. {profile_hub} will give hub tid, {profile_hub:name}
 * will give the hub text name.
 */
function apply_user_replacements($original) {
  if (!is_string($original)) {
    return $original;
  }
  $original = trim($original);
  $replace = array('{user_id}', '{username}', '{email}');
  $replaceWith = array(
    hostsite_get_user_field('id'),
    hostsite_get_user_field('name'),
    hostsite_get_user_field('mail'),
  );
  // Do basic replacements and trim the data.
  $text = str_replace($replace, $replaceWith, $original);
  // Allow other modules to hook in.
  if (function_exists('hostsite_invoke_alter_hooks')) {
    hostsite_invoke_alter_hooks('iform_user_replacements', $text);
  }
  // Look for any profile field replacments.
  if (preg_match_all('/{([a-zA-Z0-9\-_]+)}/', $text, $matches) && function_exists('hostsite_get_user_field')) {
    foreach ($matches[1] as $profileField) {
      // Got a request for a user profile field, so copy it's value across into
      // the report parameters.
      $fieldName = preg_replace('/^profile_/', '', $profileField);
      // Split off any field qualifier for vocabulary objects.
      $parts = explode(':', $fieldName);
      $fieldName = $parts[0];
      $objectField = count($parts) > 1 ? $parts[1] : 'tid';
      $value = hostsite_get_user_field($fieldName);
      if ($value) {
        // Unserialise the data if it is serialised, e.g. when using
        // profile_checkboxes to store a list of values.
        $unserialisedValue = @unserialize($value);
        // Arrays are returned as a comma separated list.
        if (is_array($unserialisedValue)) {
          $value = implode(',', $unserialisedValue);
        }
        elseif (is_object($value)) {
          // If the field is a vocabulary item, then $value is a object,
          // unserialize gives null.
          $value = get_object_vars($value);
          $value = $value[$objectField];
        }
        else {
          $value = $unserialisedValue ? $unserialisedValue : $value;
        }
        // Nulls must be passed as empty string params..
        $value = ($value === NULL ? '' : $value);
      }
      else {
        $value = '';
      }
      $text = str_replace('{' . $profileField . '}', $value, $text);
    }
  }
  // Look for any permission replacements
  if (preg_match_all('/\[([a-zA-Z0-9\-_ ]+)\]/', $text, $matches)) {
    foreach ($matches[1] as $permission) {
      $value = hostsite_user_has_permission($permission) ? '1' : '0';
      $text = str_replace("[$permission]", $value, $text);
    }
  }
  // Convert booleans to true booleans.
  $text = ($text === 'false') ? FALSE : (($text === 'true') ? TRUE : $text);
  // If the text was changed but we are not logged in then the whole value
  // should be cleared. Otherwise {profile_surname}, {profile_first_name} would
  // result in just a comma.
  if ($text !== $original && !hostsite_get_user_field('id', FALSE))
    return '';
  return $text;
}

/**
 * Function similar to get_options_array_with_user_data, but accepts input data in a format read from the form structure
 * definition for a block of attributes in a dynamic form and returns data in a format ready for passing to the code which
 * builds the attribute html.
 */
function get_attr_options_array_with_user_data($listData) {
  $r = [];
  $data=get_options_array_with_user_data($listData);
  foreach ($data as $key=>$value) {
    $tokens = explode('|', $key);
    $r[$tokens[0]][$tokens[1]] = $value;
  }
  return $r;
}