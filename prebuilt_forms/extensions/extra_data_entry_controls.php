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
 * @package	Client
 * @subpackage PrebuiltForms
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Extension class that supplies some extra controls for data entry.
 */
class extension_extra_data_entry_controls {

  /**
   * A control which provides autocomplete functionality to lookup against the list of
   * people who are users of this website.
   * @return string THML
   */
  public static function person_autocomplete($auth, $args, $tabalias, $options, $path) {
    if (empty($options['fieldname']))
      return 'A @fieldname option is required for the ' .
          '[extra_data_entry_controls.person_autocomplete] control.';
    if (!empty($options['default']) && empty($options['defaultCaption']))
      $options['defaultCaption'] = $options['default'];
    $options = array_merge(array(
      'label' => 'Person',
      'table'=>'user',
      'valueField' => 'id',
      'captionField' => 'person_name',
      'formatFunction'=>"format_person_autocomplete",
      'extraParams' => $auth['read'] + array('view'=>'detail'),
      'class'=>'control-width-5',
      'inputId' => $options['fieldname']
    ), $options);
    // we swap the input ID for the fieldname so that the visible control contains the text value to save
    // if not looking up a known person. The fieldname gets assigned to the hidden control which only
    // gets used after a lookup operation.
    $options['fieldname'] = "$options[fieldname]:lookup";
    return data_entry_helper::autocomplete($options);
  }

  /**
   * A variation on the species autocomplete control that allows an additional associated
   * occurrence to be attached to the submission. Use for single species record forms.
   * @param array $options Options array for the control with the following possibilities:
   *   * association_type_id - ID of the association type term to tag against the association.
   *   * copy_attributes - comma separated list of occurrence attribute IDs whose values are
   *     to be cloned from the main occurrence to the association.
   * @return string HTML for the control
   */
  public static function associated_occurrence($auth, $args, $tabalias, $options, $path) {
    if (empty($options['association_type_id']))
      return 'A @association_type_id option is required for the [extra_data_entry_controls.associated_occurrence] control.';
    $r = '';
    $index = (empty($options['index']) || !preg_match('/^\d+$/', $options['index'])) ? 0 : $options['index'];
    $options = array_merge(array(
      'fieldname' => 'occurrence:associated_taxa_taxon_list_id',
      'type_fieldname' => 'occurrence_association:occurrence_type_id',
      'id_fieldname' => 'occurrence_association:id',
      'to_fieldname' => 'occurrence_association:to_occurrence_id',
      'taxon_list_id' => $args['list_id']
    ), $options);
    $options['fieldname'] .= ":$index";
    $options['type_fieldname'] .= ":$index";
    $options['id_fieldname'] .= ":$index";
    $options['to_fieldname'] .= ":$index";
    if (!empty(data_entry_helper::$entity_to_load['occurrence:id'])) {
      // loading an existing record so pull in the existing associations IDs, to prevent duplication
      // of records when we save
      static $associated_occurrence_data = false;
      if ($associated_occurrence_data===false) {
        $associated_occurrence_data = data_entry_helper::get_population_data(array(
          'table' => 'occurrence_association',
          'extraParams' => $auth['read'] + array(
              'from_occurrence_id' => data_entry_helper::$entity_to_load['occurrence:id'],
              'orderby' => 'id'
            ),
          'caching' => FALSE
        ));
      }
      if (isset($associated_occurrence_data[$index])) {
        $r .= data_entry_helper::hidden_text(array(
          'fieldname' => $options['id_fieldname'],
          'default' => $associated_occurrence_data[$index]['id']
        ));
        $r .= data_entry_helper::hidden_text(array(
          'fieldname' => $options['to_fieldname'],
          'default' => $associated_occurrence_data[$index]['to_occurrence_id']
        ));
        // store the taxa_taxon_list_id as -1. This indicates to the submission code
        // that the ttlID has not changed, so no need to update the association.
        $options['default'] = -1;
        $options['defaultCaption'] = $associated_occurrence_data[$index]['to_taxon'];
      }
    }
    $r .= data_entry_helper::hidden_text(array(
      'fieldname' => $options['type_fieldname'],
      'default' => $options['association_type_id']
    ));
    $options['extraParams'] = $auth['read'] + array('taxon_list_id' => $options['taxon_list_id']);
    $r .= data_entry_helper::species_autocomplete($options);
    // flag to tell the submission build code to run code to include the association
    if ($index==0) {
      $r .= data_entry_helper::hidden_text(array(
        'fieldname' => 'submission_extensions[]',
        'default' => 'extra_data_entry_controls.build_submission_associations'
      ));
    }
    if (!empty($options['copy_attributes']) && preg_match('/^\d+(?:,\d+)*$/', $options['copy_attributes'])) {
      $r .= data_entry_helper::hidden_text(array(
        'fieldname' => "association_copy_attributes:$index" ,
        'default' => $options['copy_attributes']
      ));
    }
    return $r;
  }

  /**
   * Extension method that processes a submission to add the occurrence association
   * for the above associated_occurrence control.
   * @param $values
   * @param $s_array
   * @throws \exception
   */
  public static function build_submission_associations($values, $s_array) {
    $index = 0;
    // @todo Not as simple as the following, as could be deleting?
    while (!empty($values["occurrence:associated_taxa_taxon_list_id:$index"])) {
      self::build_submission_association($values, $s_array, $index);
      $index++;
    }
  }

  private static function build_submission_association($values, $s_array, $index) {
    if (empty($values["occurrence_association:occurrence_type_id:$index"]))
      throw new exception('A value for occurrence_association:occurrence_type_id is needed in the submission ' .
        'in order to save an association.');
    if ($values["occurrence:associated_taxa_taxon_list_id:$index"]==='-1')
      // special value indicates an existing association which has not been changed. We
      // therefore don't need to post it.
      return;
    // clone and clean up the main species record by removing unwanted attributes and media
    $copiedAttrs = array();
    if (!empty($values["association_copy_attributes:$index"]))
      $copiedAttrs = explode(',', $values["association_copy_attributes:$index"]);
    $assoc = array_merge($s_array[0]['subModels'][0]);
    unset($assoc['model']['fields']['comment']);
    foreach ($assoc['model']['fields'] as $field => $value) {
      if (substr($field, 0, 8)==='occAttr:' && !in_array(substr($field, 8), $copiedAttrs))
        unset($assoc['model']['fields'][$field]);
    }
    unset ($assoc['model']['subModels']);
    // convert this to a record of the associated species
    $assoc['model']['fields']['taxa_taxon_list_id'] = array('value' => $values["occurrence:associated_taxa_taxon_list_id:$index"]);
    $assoc['model']['fields']['taxa_taxon_list_id:taxon'] = array('value' => $values["occurrence:associated_taxa_taxon_list_id:$index:taxon"]);
    // overwrite existing if resaving
    if (!empty($values["occurrence_association:to_occurrence_id:$index"]))
      $assoc['model']['fields']['id'] = array('value' => $values["occurrence_association:to_occurrence_id:$index"]);
    // attach the associated species to the submission. The key 'assoc' allows the association to link to the
    // association when we don't already know it's ID - only needed if new record.
    $s_array[0]['subModels']["assoc:$index"] = $assoc;
    // add an association between the 2 records
    if (!isset($s_array[0]['subModels'][0]['model']['subModels']))
      $s_array[0]['subModels'][0]['model']['subModels'] = array();
    $fields = array(
      'to_occurrence_id' => array('value' => empty($values["occurrence_association:to_occurrence_id:$index"])
          ? "||assoc:$index||" : $values["occurrence_association:to_occurrence_id:$index"]),
      'association_type_id' => array('value' => $values["occurrence_association:occurrence_type_id:$index"])
    );
    if (!empty($values["occurrence_association:id:$index"]))
      $fields['id'] = array('value' => $values["occurrence_association:id:$index"]);
    $s_array[0]['subModels'][0]['model']['subModels'][] = array(
      'fkId' => 'from_occurrence_id',
      'model' => array(
        'id' => 'occurrence_association',
        'fields' => $fields
      )
    );
  }

}