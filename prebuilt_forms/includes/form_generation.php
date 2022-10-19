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
 * List of methods that can be used for a prebuilt form control generation.
 */

/**
 * Retrieve the html for a block of attributes.
 *
 * @param array $attributes
 *   Array of attributes as returned from a call to
 *   data_entry_helper::getAttributes.
 * @param array $args
 *   Form argument array.
 * @param array $ctrlOptions
 *   Array of default options to apply to every attribute control.
 * @param string $outerFilter
 *   Name of the outer block to get controls for. Leave null for all outer
 *   blocks.
 * @param array $attrSpecificOptions
 *   Associative array of control names that have non-default options. Each
 *   entry is keyed by the control name and has an array of the options and
 *   values to override.
 * @param array $idPrefix
 *   Optional prefix to give to IDs (e.g. for fieldsets) to allow you to ensure
 *   they remain unique.
 */

function get_attribute_html(&$attributes, $args, $ctrlOptions, $outerFilter = NULL,
    $attrSpecificOptions = NULL, $idPrefix = '', $helperClass = 'data_entry_helper') {
  $lastOuterBlock = '';
  $lastInnerBlock = '';
  $r = '';
  foreach ($attributes as &$attribute) {
    if (in_array($attribute['id'], data_entry_helper::$handled_attributes)) {
      $attribute['handled'] = 1;
    }
    $outerBlock = $attribute['outer_structure_block'] === NULL ? '' : $attribute['outer_structure_block'];
    // Apply filter to only output 1 block at a time. Also hide controls that have already been handled.
    if (($outerFilter === NULL || strcasecmp($outerFilter, $outerBlock) == 0) && !isset($attribute['handled'])) {
      if (empty($outerFilter) && $lastOuterBlock != $outerBlock) {
        if (!empty($lastInnerBlock)) {
          $r .= '</fieldset>';
        }
        if (!empty($lastOuterBlock)) {
          $r .= '</fieldset>';
        }
        if (!empty($outerBlock)) {
          $r .= '<fieldset id="' . get_fieldset_id($outerBlock, $idPrefix) .
              '"><legend>' . lang::get($outerBlock) . '</legend>';
        }
        if (!empty($attribute['inner_structure_block'])) {
          $r .= '<fieldset id="' . get_fieldset_id($outerBlock, $attribute['inner_structure_block'], $idPrefix) .
              '"><legend>' . lang::get($attribute['inner_structure_block']) . '</legend>';
        }
      }
      elseif ($lastInnerBlock != $attribute['inner_structure_block']) {
        if (!empty($lastInnerBlock)) {
          $r .= '</fieldset>';
        }
        if (!empty($attribute['inner_structure_block'])) {
          $r .= '<fieldset id="' . get_fieldset_id($lastOuterBlock, $attribute['inner_structure_block'], $idPrefix) .
              '"><legend>' . lang::get($attribute['inner_structure_block']) . '</legend>';
        }
      }
      $lastInnerBlock = $attribute['inner_structure_block'];
      $lastOuterBlock = $outerBlock;
      $options = $ctrlOptions + get_attr_validation($attribute, $args);
      // When getting the options, only use the first 2 parts of the fieldname
      // as any further imply an existing record ID so would differ.
      $fieldNameParts = explode(':', $attribute['fieldname']);
      if (preg_match('/[a-z][a-z][a-z]Attr/', $fieldNameParts[count($fieldNameParts) - 2])) {
        $optionFieldName = $fieldNameParts[count($fieldNameParts) - 2] . ':' . $fieldNameParts[count($fieldNameParts) - 1];
      }
      elseif (preg_match('/[a-za-za-z]Attr/', $fieldNameParts[count($fieldNameParts) - 3])) {
        $optionFieldName = $fieldNameParts[count($fieldNameParts) - 3] . ':' . $fieldNameParts[count($fieldNameParts) - 2];
      }
      else {
        throw new exception('Option fieldname not found ' . $attribute['fieldname']);
      }
      if (isset($attrSpecificOptions[$optionFieldName])) {
        $options = array_merge($options, $attrSpecificOptions[$optionFieldName]);
      }
      $r .= call_user_func($helperClass . '::outputAttribute', $attribute, $options);
      $attribute['handled'] = TRUE;
    }
  }
  if (!empty($lastInnerBlock)) {
    $r .= '</fieldset>';
  }
  if (!empty($lastOuterBlock) && strcasecmp($outerFilter, $lastOuterBlock) !== 0) {
    $r .= '</fieldset>';
  }
  return $r;
}

/**
 * When attributes are fetched from the database the validation isn't passed through. In particular
 * validation isn't defined at a website/survey level yet, so validation may be specific to this form.
 * This allows the validation rules to be defined by a $args entry.
 */
function get_attr_validation($attribute, $args) {
  $retVal = [];
  if (!empty($args['attributeValidation'])) {
    $rules = [];
    $argRules = explode(';', $args['attributeValidation']);
    foreach ($argRules as $rule) {
      $rules[] = explode(',', $rule);
    }
    foreach ($rules as $rule) {
      if ($attribute['fieldname'] == $rule[0] || substr($attribute['fieldname'], 0, strlen($rule[0]) + 1) == $rule[0] . ':') {
        // But only do if no parameter given as rule:param - eg min:-40, these
        // have to be treated as attribute validation rules.
        // It is much easier to deal with these elsewhere.
        for ($i = 1; $i < count($rule); $i++) {
          if (strpos($rule[$i], ':') === FALSE) {
            $retVal[] = $rule[$i];
          }
        }
      }
    }
    if (count($retVal) > 0) {
      return ['validation' => $retVal];
    }
  }
  return $retVal;
}

/**
 * Function to build an id for a fieldset from the block nesting data. Giving them a unique id helps if
 * you want to do interesting things with JavaScript for example.
 */
function get_fieldset_id($outerBlock, $innerBlock = '', $idPrefix = '') {
  $parts = [];
  if (!empty($idPrefix)) {
    $parts[] = $idPrefix;
  }
  $parts[] = 'fieldset';
  if (!empty($outerBlock)) {
    $parts[] = substr($outerBlock, 0, 20);
  }
  if (!empty($innerBlock)) {
    $parts[] = substr($innerBlock, 0, 20);
  }
  $r = implode('-', $parts);
  // Make it lowercase and no whitespace or other special chars.
  $r = strtolower(preg_replace('/[\s\'()]+/', '-', $r));
  $r = strtolower(preg_replace('/[()]+/', '', $r));
  return $r;
}

/**
 * Finds the list of tab names that are going to be required by the custom attributes.
 *
 * @param array $attributes
 *   List of attributes. Any attributes with no outer structure block are
 *   assigned to a tab called Other Information.
 */
function get_attribute_tabs(&$attributes) {
  $r = [];
  foreach ($attributes as &$attribute) {
    if (!isset($attribute['handled']) || $attribute['handled'] != TRUE) {
      // Assign any ungrouped attributes to a block called Other Information.
      if (empty($attribute['outer_structure_block'])) {
        $attribute['outer_structure_block'] = 'Other Information';
      }
      if (!array_key_exists($attribute['outer_structure_block'], $r)) {
        // Create a tab for this structure block and mark it with [*] so the content goes in.
        $r[$attribute['outer_structure_block']] = ["[*]"];
      }
    }
  }
  return $r;
}

/**
 * Find the attribute called CMS User ID, or return false.
 *
 * @param array $attributes
 *   List of attributes returned by a call to data_entry_helper::getAttributes.
 * @param bool $unset
 *   If true (default) then the CMS User ID attributes are removed from the
 *   array.
 *
 * @return array
 *   Single attribute definition, or false if none found.
 */
function extract_cms_user_attr(&$attributes, $unset = TRUE) {
  $found = FALSE;
  foreach ($attributes as $idx => $attr) {
    if (strcasecmp($attr['caption'], 'CMS User ID') === 0) {
      // Found will pick up just the first one.
      if (!$found)
        $found = $attr;
      if ($unset) {
        unset($attributes[$idx]);
      }
      else {
        // Don't bother looking further if not unsetting them all.
        break;
      }
    }
  }
  return $found;
}

/**
 * Gets user profile related hidden inputs.
 *
 * Returns a list of hidden inputs which are extracted from the form attributes
 * which can be extracted from the user's profile information in Drupal. The
 * attributes which are used are marked as handled so they don't need to be
 * output elsewhere on the form. This function also handles non-profile based
 * CMS User ID, Username, and Email; and also special processing for names.
 *
 * @param array $attributes
 *   List of form attributes.
 * @param array $args
 *   List of form arguments. Can include values called:
 *   * copyFromProfile - boolean indicating if values should be copied from the
 *     profile when the names match.
 *   * nameShow - boolean, if true then name values should be displayed rather
 *     than hidden. In fact this extends to all profile fields whose names match
 *     attribute captions. E.g. in D7, field_age would populate an attribute
 *     with caption age.
 *   * emailShow - boolean, if true then email values should be displayed rather
 *     than hidden.
 * @param bool $exists
 *   Pass true for an existing record. If the record exists, then the
 *   attributes are marked as handled but are not output to avoid overwriting
 *   metadata about the original creator of the record.
 * @param array $readAuth
 *   Read authorisation tokens.
 *
 * @return string
 *   HTML for the hidden inputs.
 */
function get_user_profile_hidden_inputs(array &$attributes, array $args, $exists, $readAuth) {
  // This is Drupal specific code.
  $logged_in = hostsite_get_user_field('id') > 0;
  // If the user is not logged in there is no profile so return early.
  if (!$logged_in) {
    // Mark CMS related profile fields as handled as they can't be filled in
    // anyway.
    foreach ($attributes as &$attribute) {
      if ($attribute['system_function'] === 'cms_user_id' || $attribute['system_function'] === 'cms_username') {
        $attribute['handled'] = TRUE;
      }
    }
    return '';
  }

  $hiddens = '';
  foreach ($attributes as &$attribute) {
    $value = hostsite_get_user_field(strtolower(str_replace(' ', '_', $attribute['untranslatedCaption'])));
    if ($value && isset($args['copyFromProfile']) && $args['copyFromProfile'] == TRUE) {
      // lookups need to be translated to the termlist_term_id, unless they are already IDs
      if ($attribute['data_type'] === 'L' && !preg_match('/^[\d]+$/', $value)) {
        $terms = data_entry_helper::get_population_data(array(
          'table' => 'termlists_term',
          'extraParams' => $readAuth + array('termlist_id' => $attribute['termlist_id'], 'term' => $value)
        ));
        $value = (count($terms) > 0) ? $terms[0]['id'] : '';
      }

      if (isset($args['nameShow']) && $args['nameShow'] == TRUE) {
        // Show the attribute with default value providing we aren't editing, in which case the value should be collected
        // from the saved data (even if that data is blank) so we don't want to overwrite it
        if (!isset($attribute['default']) && !isset($_GET['sample_id']) && !isset($_GET['occurrence_id']))
          $attribute['default'] = $value;
      }
      else {
        // Hide the attribute value
        $attribute['handled']=TRUE;
        $attribute['value'] = $value;
      }
    }
    elseif (strcasecmp($attribute['untranslatedCaption'], 'cms user id') == 0) {
      $attribute['value'] = hostsite_get_user_field('id');
      $attribute['handled']=TRUE; // user id attribute is never displayed
    }
    elseif (strcasecmp($attribute['untranslatedCaption'], 'cms username') == 0) {
      $attribute['value'] = hostsite_get_user_field('name');
      $attribute['handled']=TRUE; // username attribute is never displayed
    }
    elseif (strcasecmp($attribute['untranslatedCaption'], 'email') == 0) {
      if (isset($args['emailShow']) && $args['emailShow'] == TRUE) {
        // Show the email attribute with default value providing we aren't editing, in which case the value should be collected
        // from the saved data (even if that data is blank) so we don't want to overwrite it
        if (!isset($attribute['default']) && !isset($_GET['sample_id']) && !isset($_GET['occurrence_id']))
          $attribute['default'] = hostsite_get_user_field('mail');
      }
      else {
        // Hide the email value
        $attribute['value'] = hostsite_get_user_field('mail');
        $attribute['handled'] = TRUE;
      }
    }
    elseif ((strcasecmp($attribute['caption'], 'first name') == 0 ||
        strcasecmp($attribute['caption'], 'last name') == 0 ||
        strcasecmp($attribute['caption'], 'surname') == 0)) {
      // This would be the case where the warehouse is configured to store these
      // values but there are no matching profile fields
      if (!isset($args['nameShow']) || $args['nameShow'] != TRUE) {
        // Name attributes are not displayed because we have the users login.
        $attribute['handled'] = TRUE;
      }
    }
    // If we have a value for one of the user login attributes then we need to
    // output this value. BUT, for existing data we must not overwrite the user
    // who created the record. Note that we don't do this at the beginning of
    // the method as we still wanted to mark the attributes as handled.
    if (isset($attribute['value']) && !$exists) {
      $hiddens .= '<input type="hidden" name="' . $attribute['fieldname'] . '" value="' . $attribute['value'] . '" />' . "\n";
    }
  }
  return $hiddens;
}