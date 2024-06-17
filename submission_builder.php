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
 * Link in other required php files.
 */
require_once 'lang.php';
require_once 'data_entry_helper.php';

/**
 * Provides a helper to build submissions.
 */

class submission_builder {

  /**
   * Helper function to simplify building of a submission. Does simple submissions that do not involve
   * species checklist grids.
   *
   * @param array $values
   *   List of the posted values to create the submission from.
   * @param array $structure
   *   Describes the structure of the submission. The form should be:
   *   [
   *     'model' => 'main model name',
   *     'fieldPrefix' => 'Optional prefix for HTML form fields in the main model. If not specified then the main model name is used.',
   *     'subModels' => ['child model name' => [
   *         'fieldPrefix' => 'Optional prefix for HTML form fields in the sub model. If not specified then the sub model name is used.',
   *         'fk' => 'foreign key name'
   *     [[]],
   *     'superModels' => ['parent model name' => [
   *         'fieldPrefix' => 'Optional prefix for HTML form fields in the sub model. If not specified then the sub model name is used.',
   *         'fk' => 'foreign key name'
   *     ]],
   *     'metaFields' => ['fieldname1', 'fieldname2', ...],
   *     'joinsTo' => ['model that this has a many to many join with', ...]
   *   ]
   */
  public static function build_submission($values, $structure) {
    // Handle metaFields and joinsTo first so they don't end up in other parts of the submission (specially handled fields)
    if (array_key_exists('metaFields', $structure)) {
      $metaFields = [];
      foreach ($structure['metaFields'] as $metaField) {
        if (array_key_exists("metaFields:$metaField", $values)) {
          $metaFields[$metaField] = ['value' => $values["metaFields:$metaField"]];
          unset($values["metaFields:$metaField"]);
        }
      }
    }
    if (array_key_exists('joinsTo', $structure)) {
      $joinsTo = [];
      foreach ($structure['joinsTo'] as $joinsToTable) {
        // Find any POST data that indicates a join to this table
        // (key=joinsTo:table:id).
        $joinModel = inflector::singular($joinsToTable);
        $joinsTo[$joinModel] = [];
        $joinsToModel = preg_grep('/^joinsTo:' . $joinModel . ':.+$/', array_keys($values) );
        foreach ($joinsToModel as $key => $value) {
          $joinId = substr($value, strlen("joinsTo:$joinModel:"));
          if (is_numeric($joinId)) {
            array_push($joinsTo[$joinModel], $joinId);
          }
          elseif ($joinId === 'id' || $joinId === 'id[]') {
            if (is_array($values[$value])) {
              foreach ($values[$value] as $innerValue) {
                if (is_numeric($innerValue)) {
                  array_push($joinsTo[$joinModel], $innerValue);
                }
              }
            }
            else {
              if (is_numeric($values[$value])) {
                array_push($joinsTo[$joinModel], $values[$value]);
              }
            }
          }
          // array_push($joinsTo[$joinModel], substr($value, strlen("joinsTo:$joinModel:")));
          // Remove the handled joinFields so they don't clutter the rest of
          // the submission.
          unset($values[$value]);
        }
      }
    }
    // Wrap the main model and attrs into JSON.
    $modelWrapped = self::wrap_with_images($values, array_key_exists('fieldPrefix', $structure) ? $structure['fieldPrefix'] : $structure['model']);
    // Attach the specially handled fields to the model.
    if (array_key_exists('metaFields', $structure)) {
      // Need to be careful merging metafields in the structure and those auto
      // generated in wrap_with_images (ie sample/location/occurrence
      // attributes).
      if (!array_key_exists('metaFields', $modelWrapped)) {
        $modelWrapped['metaFields'] = [];
      }
      foreach ($metaFields as $key => $value) {
        $modelWrapped['metaFields'][$key] = $value;
      }
    }
    if (array_key_exists('joinsTo', $structure)) {
      $modelWrapped['joinsTo'] = $joinsTo;
    }
    // Handle the child model if present.
    if (array_key_exists('subModels', $structure)) {
      // Need to be careful merging submodels in the structure and those auto
      // generated in wrap_with_images (ie images).
      if (!array_key_exists('subModels', $modelWrapped)) {
        $modelWrapped['subModels'] = [];
      }
      foreach ($structure['subModels'] as $name => $struct) {
        $submodelWrapped = self::wrap_with_images($values, array_key_exists('fieldPrefix', $struct) ? $struct['fieldPrefix'] : $name);
        // Join the parent and child models together.
        array_push($modelWrapped['subModels'], ['fkId' => $struct['fk'], 'model' => $submodelWrapped]);
      }
    }
    if (array_key_exists('superModels', $structure)) {
      $modelWrapped['superModels'] = [];
      foreach ($structure['superModels'] as $name => $struct) {
        // Skip the supermodel if the foreign key is already populated in the
        // main table.
        if (!isset($modelWrapped['fields'][$struct['fk']]['value']) || empty($modelWrapped['fields'][$struct['fk']]['value'])) {
          $supermodelWrapped = self::wrap_with_images($values, array_key_exists('fieldPrefix', $struct) ? $struct['fieldPrefix'] : $name);
          // Join the parent and child models together.
          array_push($modelWrapped['superModels'], [
            'fkId' => $struct['fk'],
            'model' => $supermodelWrapped,
          ]);
        }
      }
    }
    return $modelWrapped;

  }

  /**
   * Wraps an array for submission.
   *
   * E.g. Post or Session data generated by a form is converted into a
   * structure suitable for submission.
   *
   * The attributes in the array are all included, unless they are named using
   * the form entity:attribute (e.g. sample:date) in which case they are only
   * included if wrapping the matching entity. This allows the content of the
   * wrap to be limited to only the appropriate information.
   *
   * Do not prefix the survey_id or website_id attributes being posted with an
   * entity name as these IDs are used by Indicia for all entities.
   *
   * @param array $array
   *   Array of data generated from data entry controls.
   * @param string $entity
   *   Name of the entity to wrap data for.
   * @param string $field_prefix
   *   Name of the prefix each field on the form has. Used to construct an
   *   error message array that can be linked back to the source fields easily.
   */
  public static function wrap($array, $entity, $field_prefix = NULL) {
    if (array_key_exists('save-site-flag', $array) && $array['save-site-flag'] === '1' && $entity === 'sample') {
      self::createPersonalSite($array);
    }
    // Initialise the wrapped array.
    $sa = [
      'id' => $entity,
      'fields' => [],
    ];
    if ($field_prefix) {
      $sa['field_prefix'] = $field_prefix;
    }
    $attrEntity = self::getAttrEntityPrefix($entity, FALSE) . 'Attr';
    // Complex json multivalue attributes need special handling.
    $complexAttrs = [];
    // Iterate through the array.
    foreach ($array as $key => $value) {
      // Don't wrap the authentication tokens, or any attributes tagged as
      // belonging to another entity.
      if ($key !== 'auth_token' && $key !== 'nonce') {
        if (strpos($key, "$entity:") === 0 || !strpos($key, ':')) {
          // Strip the entity name tag if present, as should not be in the
          // submission attribute names.
          $key = str_replace("$entity:", '', $key);
          // This should be a field in the model.
          // Add a new field to the save array.
          $sa['fields'][$key] = ['value' => $value];
        }
        elseif ($attrEntity && (strpos($key, "$attrEntity:") === 0) && substr_count($key, ':') < 4) {
          // Skip fields smpAttr:atrrId:attrValId:uniqueIdx:controlname because :controlname indicates this is the extra control used for autocomplete, not the data to post.
          // Custom attribute data can also go straight into the submission for the "master" table. Array data might need
          // special handling to link it to existing database records.
          if (is_array($value) && count($value) > 0) {
            // The value is an array.
            foreach ($value as $idx => $arrayItem) {
              // Does the entry contain the fieldname (required for existing
              // values in controls which post arrays, like multiselect
              // selects)?
              if (preg_match("/\d+:$attrEntity:\d+:\d+/", $arrayItem)) {
                $tokens = explode(':', $arrayItem, 2);
                $sa['fields'][$tokens[1]] = ['value' => $tokens[0]];
                // Additional handling for multi-value controls such as
                // easyselect lists in species grids where the selected items
                // are displayed under the main control. These items have both
                // the value itself and the attribute_value id in the value
                // field.
              }
              elseif (preg_match("/^\d+:\d+$/", $arrayItem)) {
                $tokens = explode(':', $arrayItem);
                $sa['fields']["$key:$tokens[1]:$idx"] = ['value' => $tokens[0]];
              }
              else {
                $sa['fields']["$key::$idx"] = ['value' => $arrayItem];
              }
            }
          }
          else {
            $sa['fields'][$key] = ['value' => $value];
          }
        }
        elseif ($attrEntity && (strpos($key, "$attrEntity"."Complex:") === 0)) {
          // A complex custom attribute data value which will need to be json
          // encoded.
          $tokens = explode(':', $key);
          $attrKey = str_replace('Complex', '', $tokens[0]) . ':' . $tokens[1];
          if (!empty($tokens[2])) {
            // Existing value record.
            $attrKey .= ":$tokens[2]";
          }
          if ($tokens[4] === 'deleted') {
            if ($value === 't') {
              $complexAttrs[$attrKey] = 'deleted';
            }
          }
          else {
            $exists = isset($complexAttrs[$attrKey]) ? $complexAttrs[$attrKey] : [];
            if ($exists !== 'deleted') {
              $exists[$tokens[3]][$tokens[4]] = $value;
              $complexAttrs[$attrKey] = $exists;
            }
          }
        }
      }
    }
    foreach ($complexAttrs as $attrKey => $data) {
      if ($data === 'deleted') {
        $sa['fields'][$attrKey] = ['value' => ''];
      }
      else {
        $sa['fields'][$attrKey] = ['value' => []];
        $tokens = explode(':', $attrKey);
        $exists = count($tokens) === 3;
        $encoding = $array["complex-attr-grid-encoding-$tokens[0]-$tokens[1]"];
        foreach (array_values($data) as $row) {
          // Find any term submissions in form id:term, and split into 2 json
          // fields. Also process checkbox groups into suitable array form.
          $terms = [];
          foreach ($row as $key => &$val) {
            if (is_array($val)) {
              // Array from a checkbox_group.
              $subvals = [];
              $subterms = [];
              foreach ($val as $subval) {
                $split = explode(':', $subval, 2);
                $subvals[] = $split[0];
                $subterms[] = $split[1];
              }
              $val = $subvals;
              $terms[$key . '_term'] = $subterms;
            }
            else {
              if (preg_match('/^[0-9]+\:.+$/', $val)) {
                $split = explode(':', $val, 2);
                $val = $split[0];
                $terms[$key . '_term'] = $split[1];
              }
            }
          }
          // JSON only - include the terms in the saved value.
          if ($encoding === 'json') {
            $row += $terms;
          }
          if (implode('', array_values($row)) <> '') {
            $encoded = $encoding === 'json' ? json_encode($row) : implode($encoding, $row);
            if ($exists) {
              // Existing value, so no need to send an array.
              $sa['fields'][$attrKey]['value'] = $encoded;
            }
            else {
              // Could be multiple new values, so send an array.
              $sa['fields'][$attrKey]['value'][] = $encoded;
            }
          }
          elseif ($exists) {
            // Submitting an empty set for existing row, so deleted.
            $sa['fields'][$attrKey] = ['value' => ''];
          }
        }
      }
    }
    if (($entity === 'sample' || $entity === 'occurrence') && function_exists('hostsite_get_user_field') && hostsite_get_user_field('training')) {
      $sa['fields']['training'] = ['value' => 'on'];
    }
    // UseLocationName is a special flag to indicate that an unmatched location
    // can go in the location_name field.
    if (isset($array['useLocationName'])) {
      if ($entity === 'sample') {
        if ((empty($sa['fields']['location_id']) || empty($sa['fields']['location_id']['value']))
            && !empty($array['imp-location:name'])) {
          $sa['fields']['location_name'] = ['value' => $array['imp-location:name']];
        }
        else {
          $sa['fields']['location_name'] = ['value' => ''];
        }
      }
      unset($array['useLocationName']);
    }
    return $sa;
  }

  /**
   * Create a recorder owned site.
   *
   * Creates a site using the form submission data and attaches the location_id
   * to the sample information in the submission.
   *
   * @param array $array
   *   Form submission data.
   */
  private static function createPersonalSite(array &$array) {
    // Check we don't already have a location ID, and have the other stuff
    // we require.
    if (!empty($array['sample:location_id']) || !array_key_exists('imp-location:name', $array)
        || !array_key_exists('sample:entered_sref', $array) || !array_key_exists('sample:entered_sref_system', $array)) {
      return;
    }
    $loc = [
      'location:name' => $array['imp-location:name'],
      'location:centroid_sref' => $array['sample:entered_sref'],
      'location:centroid_sref_system' => $array['sample:entered_sref_system'],
      'locations_website:website_id' => $array['website_id'],
    ];
    if (!empty($array['sample:geom'])) {
      $loc['location:centroid_geom'] = $array['sample:geom'];
    }
    $submission = self::build_submission($loc, [
      'model' => 'location',
      'subModels' => [
        'locations_website' => ['fk' => 'location_id'],
      ],
    ]);
    $request = data_entry_helper::$base_url . "index.php/services/data/save";
    $postargs = 'submission=' . urlencode(json_encode($submission));
    // Setting persist_auth allows the write tokens to be re-used.
    $postargs .= '&persist_auth=true&auth_token=' . $array['auth_token'];
    $postargs .= '&nonce=' . $array['nonce'];
    if (function_exists('hostsite_get_user_field')) {
      $postargs .= '&user_id=' . hostsite_get_user_field('indicia_user_id');
    }
    $response = data_entry_helper::http_post($request, $postargs);
    // The response should be in JSON if it worked.
    if (isset($response['output'])) {
      $output = json_decode($response['output'], TRUE);
      if (!$output) {
        throw new exception(print_r($response, TRUE));
      }
      elseif (isset($output['success']) && $output['success'] === 'multiple records') {
        $array['sample:location_id'] = $output['outer_id'];
      }
      elseif (isset($output['success'])) {
        $array['sample:location_id'] = $output['success'];
      }
      else {
        throw new exception(print_r($response, TRUE));
      }
    }
  }

  /**
   * Wraps a set of values for a model into JSON suitable for submission.
   *
   * JSON is ready for submission to the Indicia data services. Also grabs the
   * images and links them to the model.
   *
   * @param array $values
   *   Array of form data (e.g. $_POST).
   * @param string $modelName
   *   Name of the model to wrap data for. If this is sample, occurrence or
   *   location then custom attributes will also be wrapped. Furthermore, any
   *   attribute called $modelName:image can contain an image upload (as long
   *   as a suitable entity is available to store the image in).
   * @param string $fieldPrefix
   *   Name of the prefix each field on the form has. Used to construct an
   *   error message array that can be linked back to the source fields
   *   easily.
   *
   * @return array
   *   Wrapped data structure.
   */
  public static function wrap_with_images(array $values, $modelName, $fieldPrefix = NULL) {
    // Now search for an input control values which imply that an image file
    // will need to be either moved to the warehouse, or if already on the
    // warehouse, processed to create thumbnails.
    switch ($modelName) {
      case 'taxon_meaning':
        $mediaModelName = 'taxon';
        break;

      case 'taxon':
        $mediaModelName = '-';
        break;

      default:
        $mediaModelName = $modelName;
    }
    foreach ($_FILES as $fieldname => &$file) {
      if ($file['name'] && is_string($file['name'])) {
        // Get the original file's extension.
        $parts = explode(".", $file['name']);
        $fext = array_pop($parts);
        // Generate a file id to store the image as.
        $filename = time() . rand(0, 1000) . "." . strtolower($fext);
        if ($fieldname === 'image_upload') {
          // Image_upload is a special case only used on the warehouse, so can
          // move the file directly to its final place.
          // @todo Should this special case exist?
          $uploadpath = dirname($_SERVER['SCRIPT_FILENAME']) . '/' .
            (isset(data_entry_helper::$indicia_upload_path) ? data_entry_helper::$indicia_upload_path : 'upload/');
          if (move_uploaded_file($file['tmp_name'], $uploadpath . $filename)) {
            // Record the new file name, also note it in the $_POST data so it
            // can be tracked after a validation failure.
            $file['name'] = $filename;
            $values['path'] = $filename;
            // This is the final file destination, so create the image files.
            Image::create_image_files($uploadpath, $filename);
          }
        }
        elseif (preg_match('/^(' . $mediaModelName . ':)?[a-z_]+_path$/', $fieldname)) {
          // Image fields can be of form {model}:{qualifier}_path (e.g.
          // group:logo_path) if they are directly embedded in the entity,
          // rather than in a child media entity. These files need to be moved
          // to interim upload folder and will be sent to the warehouse after a
          // successful save.
          $values[$fieldname] = $filename;
          $uploadpath = helper_base::getInterimImageFolder('fullpath');
          $tempFile = isset($file['tmp_name']) ? $file['tmp_name'] : '';
          if (!move_uploaded_file($tempFile, $uploadpath . $filename)) {
            throw new exception('Failed to move uploaded file from temporary location');
          }
          $file['name'] = $filename;
        }
      }
    }
    // Get the parent model into JSON.
    $modelWrapped = self::wrap($values, $modelName, $fieldPrefix);

    // Build sub-models for the media files. Don't post to the warehouse until after validation success. This
    // also moves any simple uploaded files to the interim image upload folder.
    $media = data_entry_helper::extract_media_data($values, $mediaModelName . '_medium', TRUE, TRUE);
    foreach ($media as $item) {
      $wrapped = self::wrap($item, $mediaModelName . '_medium');
      $modelWrapped['subModels'][] = array(
        'fkId' => $modelName . '_id',
        'model' => $wrapped,
      );
    }
    return $modelWrapped;
  }

  /**
   * Returns a 3 character prefix representing an entity name.
   *
   * @param string $entity
   *   Entity name (location, sample, occurrence, taxa_taxon_list,
   *   termlists_term or person). Also 3 entities from the Individuals and
   *   Associations module (known_subject, subject_observation and mark).
   * @param bool $except
   *   If true, raises an exception if the entity name does not have custom
   *   attributes. Otherwise returns false. Default true.
   */
  private static function getAttrEntityPrefix($entity, $except = TRUE) {
    switch ($entity) {
      case 'occurrence':
        return 'occ';

      case 'location':
        return 'loc';

      case 'sample':
        return 'smp';

      case 'survey':
        return 'srv';

      case 'taxa_taxon_list':
        return 'tax';

      case 'termlists_term':
        return 'trm';

      case 'person':
        return 'psn';

      case 'known_subject':
        return 'ksj';

      case 'subject_observation':
        return 'sjo';

      case 'identifier':
        return 'idn';

      case 'identifiers_subject_observation':
        return 'iso';

      default:
        if ($except) {
          throw new Exception('Unknown attribute type. ');
        }
        else {
          return FALSE;
        }
    }
  }

}
