<?php

/**
 * @file
 * Data entry extension to support DNA-derived occurrences.
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
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/Indicia-Team/client_helpers
 */

class extension_dna_occurrences {

  /**
   * A set of controls for inputting DNA information on an occurrence form.
   *
   * Options include:
   * * fields: an array of dna_occurrence fields to include. If not provided,
   *   all fields will be included. Note that dna_occurrence:dna_sequence,
   *   dna_occurrence:target_gene and dna_occurrence:pcr_primer_reference are
   *   required fields for this extension and must be included in the array if
   *   the array is provided.
   */
  public static function occurrence_form_controls($auth, $args, $tabalias, $options, $path) {
    // Load existing DNA data.
    if (!empty(data_entry_helper::$entity_to_load['occurrence:id'])) {
      $dnaOccurrences = data_entry_helper::get_population_data([
        'table' => 'dna_occurrence',
        'extraParams' => $auth['read'] + [
          'occurrence_id' => data_entry_helper::$entity_to_load['occurrence:id'],
        ],
        'caching' => FALSE,
      ]);
      if (count($dnaOccurrences) === 1) {
        foreach ($dnaOccurrences[0] as $field => $value) {
          if ($field === 'associated_sequences' && !empty($value)) {
            $value = implode("\n", json_decode($value));
          }
          data_entry_helper::$entity_to_load["dna_occurrence:$field"] = $value;
        }
      }
      elseif (count($dnaOccurrences) > 1) {
        // This should never happen due to unique constraints.
        throw new Exception('Data issue - multiple DNA occurrences attached to occurrence ' . data_entry_helper::$entity_to_load['occurrence:id']);
      }
    }
    $options['fields'] = $options['fields'] ?? [
      'dna_occurrence:associated_sequences',
      'dna_occurrence:dna_sequence',
      'dna_occurrence:target_gene',
      'dna_occurrence:pcr_primer_reference',
      'dna_occurrence:env_medium',
      'dna_occurrence:env_broad_scale',
      'dna_occurrence:otu_db',
      'dna_occurrence:otu_seq_comp_appr',
      'dna_occurrence:otu_class_appr',
      'dna_occurrence:env_local_scale',
      'dna_occurrence:target_subfragment',
      'dna_occurrence:pcr_primer_name_forward',
      'dna_occurrence:pcr_primer_forward',
      'dna_occurrence:pcr_primer_name_reverse',
      'dna_occurrence:pcr_primer_reverse',
    ];
    $mandatoryFields = [
      'dna_occurrence:dna_sequence',
      'dna_occurrence:target_gene',
      'dna_occurrence:pcr_primer_reference',
    ];
    self::checkMandatoryFieldsArePresent($options['fields'], $mandatoryFields);
    return self::buildControls($options, $mandatoryFields);
  }

  private static function checkMandatoryFieldsArePresent(array $fields, array $mandatoryFields) {
    foreach ($mandatoryFields as $mandatoryField) {
      if (!in_array($mandatoryField, $fields)) {
        throw new Exception("$mandatoryField is a required field for the DNA occurrence extension.");
      }
    }
  }

  /**
   * Builds the HTML for the DNA controls.
   *
   * @param array $options
   *   Control options, including which fields to include.
   * @param array $mandatoryFields
   *   List of mandatory fields - other fields will be output in a collapsible
   *   Optional section.
   *
   * @return string
   *   Control HTML.
   */
  private static function buildControls(array $options, array $mandatoryFields) {
    $mainFieldsHtml = data_entry_helper::hidden_text([
      'fieldname' => 'dna_occurrence:id',
      'default' => data_entry_helper::$entity_to_load['dna_occurrence:id'] ?? NULL,
    ]);
    $mainFieldsHtml .= '';
    $optionalFieldsHtml = '';

    foreach ($options['fields'] as $field) {
      self::checkFieldIsValid($field);
      $thisControl = NULL;
      if ($field === 'dna_occurrence:associated_sequences') {
        $thisControl = data_entry_helper::textarea([
          'fieldname' => 'dna_occurrence:associated_sequences',
          'label' => 'Associated sequences',
          'helpText' => 'A list (one per line) of identifiers (publication, global unique identifier, URI) of genetic sequence information associated with the record.',
          'lockable' => TRUE,
        ]);
      }
      elseif ($field === 'dna_occurrence:dna_sequence') {
        $thisControl = data_entry_helper::textarea([
          'fieldname' => 'dna_occurrence:dna_sequence',
          'label' => 'DNA sequence',
          'helpText' => 'The DNA sequence.',
          'validation' => ['required'],
          'lockable' => TRUE,
        ]);
      }
      elseif ($field === 'dna_occurrence:target_gene') {
        $thisControl = data_entry_helper::text_input([
          'fieldname' => 'dna_occurrence:target_gene',
          'label' => 'Target gene',
          'helpText' => 'Targeted gene or marker name for marker-based studies.',
          'validation' => ['required'],
          'lockable' => TRUE,
        ]);
      }
      elseif ($field === 'dna_occurrence:pcr_primer_reference') {
        $thisControl = data_entry_helper::text_input([
          'fieldname' => 'dna_occurrence:pcr_primer_reference',
          'label' => 'PCR primer reference',
          'helpText' => 'Reference for the primers.',
          'validation' => ['required'],
          'lockable' => TRUE,
        ]);
      }
      elseif ($field === 'dna_occurrence:env_medium') {
        $thisControl = data_entry_helper::text_input([
          'fieldname' => 'dna_occurrence:env_medium',
          'label' => 'Environmental medium',
          'helpText' => 'The environmental medium which surrounded your sample or specimen prior to sampling. Should be a subclass of an ENVO material.',
          'lockable' => TRUE,
        ]);
      }
      elseif ($field === 'dna_occurrence:env_broad_scale') {
        $thisControl = data_entry_helper::text_input([
          'fieldname' => 'dna_occurrence:env_broad_scale',
          'label' => 'Broad-scale environment',
          'helpText' => "The broad-scale environment the sample or specimen came from. Subclass of ENVO's biome class.",
          'lockable' => TRUE,
        ]);
      }
      elseif ($field === 'dna_occurrence:otu_db') {
        $thisControl = data_entry_helper::text_input([
          'fieldname' => 'dna_occurrence:otu_db',
          'label' => 'OTU database',
          'helpText' => 'The OTU database (i.e. sequences not generated as part of the current study) used to assigning taxonomy to OTUs or ASVs.',
          'lockable' => TRUE,
        ]);
      }
      elseif ($field === 'dna_occurrence:otu_seq_comp_appr') {
        $thisControl = data_entry_helper::text_input([
          'fieldname' => 'dna_occurrence:otu_seq_comp_appr',
          'label' => 'OTU sequence comparison approach',
          'helpText' => 'The OTU sequence comparison approach, such as tools and thresholds used to assign “species-level” names to OTUs or ASVs.',
          'lockable' => TRUE,
        ]);
      }
      elseif ($field === 'dna_occurrence:otu_class_appr') {
        $thisControl = data_entry_helper::text_input([
          'fieldname' => 'dna_occurrence:otu_class_appr',
          'label' => 'OTU classification approach',
          'helpText' => 'The OTU classification approach / algorithm and clustering level (if relevant) when defining OTUs or ASVs.',
          'lockable' => TRUE,
        ]);
      }
      elseif ($field === 'dna_occurrence:env_local_scale') {
        $thisControl = data_entry_helper::text_input([
          'fieldname' => 'dna_occurrence:env_local_scale',
          'label' => 'Local-scale environment',
          'helpText' => 'The local environmental context the sample or specimen came from. Please use terms that are present in ENVO and which are of smaller spatial grain than your entry for env_broad_scale.',
          'lockable' => TRUE,
        ]);
      }
      elseif ($field === 'dna_occurrence:target_subfragment') {
        $thisControl = data_entry_helper::text_input([
          'fieldname' => 'dna_occurrence:target_subfragment',
          'label' => 'Target subfragment',
          'helpText' => 'Name of subfragment of a gene or marker.',
          'lockable' => TRUE,
        ]);
      }
      elseif ($field === 'dna_occurrence:pcr_primer_name_forward') {
        $thisControl = data_entry_helper::text_input([
          'fieldname' => 'dna_occurrence:pcr_primer_name_forward',
          'label' => 'Forward PCR primer name',
          'helpText' => 'Name of the forward PCR primer that were used to amplify the sequence of the targeted gene, locus or subfragment.',
          'lockable' => TRUE,
        ]);
      }
      elseif ($field === 'dna_occurrence:pcr_primer_forward') {
        $thisControl = data_entry_helper::text_input([
          'fieldname' => 'dna_occurrence:pcr_primer_forward',
          'label' => 'Forward PCR primer',
          'helpText' => 'Forward PCR primer that was used to amplify the sequence of the targeted gene, locus or subfragment.',
          'lockable' => TRUE,
        ]);
      }
      elseif ($field === 'dna_occurrence:pcr_primer_name_reverse') {
        $thisControl = data_entry_helper::text_input([
          'fieldname' => 'dna_occurrence:pcr_primer_name_reverse',
          'label' => 'Reverse PCR primer name',
          'helpText' => 'Name of the reverse PCR primer that was used to amplify the sequence of the targeted gene, locus or subfragment.',
          'lockable' => TRUE,
        ]);
      }
      elseif ($field === 'dna_occurrence:pcr_primer_reverse') {
        $thisControl = data_entry_helper::text_input([
          'fieldname' => 'dna_occurrence:pcr_primer_reverse',
          'label' => 'Reverse PCR primer',
          'helpText' => 'Reverse PCR primer that was used to amplify the sequence of the targeted gene, locus or subfragment.',
          'lockable' => TRUE,
        ]);
      }
      if (in_array($field, $mandatoryFields)) {
        $mainFieldsHtml .= $thisControl;
      }
      else {
        $optionalFieldsHtml .= $thisControl;
      }
    }
    $r = $mainFieldsHtml;
    if (!empty($optionalFieldsHtml)) {
      $lang = [
        'Optional DNA fields' => lang::get('Optional DNA fields'),
        'hideOptionalFields' => lang::get('Hide optional fields'),
        'showOptionalFields' => lang::get('Show optional fields'),
      ];
      global $indicia_templates;
      $r .= <<<HTML
        <div>
          <button type="button" class="toggle-optional-dna-fields $indicia_templates[buttonDefaultClass]"
            data-lang-show="{$lang['showOptionalFields']}"
            data-lang-hide="{$lang['hideOptionalFields']}">{$lang['showOptionalFields']}</button>
          <div class="panel panel-info optional-dna-fields" style="display:none;">
            <div class="panel-heading">{$lang['Optional DNA fields']}</div>
            <div class="panel-body">
              $optionalFieldsHtml
            </div>
          </div>
        </div>
      HTML;
    }
    // Tell the submission code that we are going to add extra data.
    $r .= data_entry_helper::hidden_text([
      'fieldname' => 'submission_extensions[]',
      'default' => 'dna_occurrences.build_submission_dna_occurrence',
    ]);
    return $r;
  }

  /**
   * Check if a field named in @fields is valid.
   *
   * @param string $field
   *   The field name to check.
   */
  private static function checkFieldIsValid($field) {
    $validFields = [
      'dna_occurrence:associated_sequences',
      'dna_occurrence:dna_sequence',
      'dna_occurrence:target_gene',
      'dna_occurrence:pcr_primer_reference',
      'dna_occurrence:env_medium',
      'dna_occurrence:env_broad_scale',
      'dna_occurrence:otu_db',
      'dna_occurrence:otu_seq_comp_appr',
      'dna_occurrence:otu_class_appr',
      'dna_occurrence:env_local_scale',
      'dna_occurrence:target_subfragment',
      'dna_occurrence:pcr_primer_name_forward',
      'dna_occurrence:pcr_primer_forward',
      'dna_occurrence:pcr_primer_name_reverse',
      'dna_occurrence:pcr_primer_reverse',
    ];
    if (!in_array($field, $validFields)) {
      throw new Exception("Invalid field $field in DNA occurrence extension.");
    }
  }

  /**
   * Add DNA occurrence info to submission.
   *
   * @param array $values
   *   Submitted form values.
   * @param array $s_array
   *   Submission which dna_occurrence will be added to.
   */
  public static function build_submission_dna_occurrence(array $values, array $s_array) {
    $gotDna = FALSE;
    $dnaFields = [];
    foreach ($values as $field => $value) {
      if (explode(':', $field)[0] === 'dna_occurrence') {
        // Check if we've got any DNA metadata (excluding the primary key).
        if (!empty(trim($value)) && $field !== 'dna_occurrence:id') {
          $gotDna = TRUE;
        }
        $dnaFields[explode(':', $field)[1]] = trim($value);
      }
    }
    if ($gotDna) {
      foreach ($s_array as &$submittedRecord) {
        self::attachDnaToOccurrence($dnaFields, $submittedRecord);
      }
    }
    elseif (!empty($field['dna_occurrence:id'] ?? NULL)) {
      $dnaFields['deleted'] = FALSE;
      foreach ($s_array as &$submittedRecord) {
        self::attachDnaToOccurrence($dnaFields, $submittedRecord);
      }
    }
  }

  /**
   * Traverse down the submission to find the occurrence to add DNA to.
   *
   * A recursive function which attaches a dna_occurrence when it finds the
   * occurrence.
   *
   * @param array $dnaFields
   *   DNA fields and values.
   * @param array $submittedRecord
   *   The current branch of the submission being traversed down.
   */
  private static function attachDnaToOccurrence(array $dnaFields, array &$submittedRecord) {
    if ($submittedRecord['id'] === 'sample' && isset($submittedRecord['subModels'])) {
      // Can recurse into samples.
      foreach ($submittedRecord['subModels'] as &$subModel) {
        self::attachDnaToOccurrence($dnaFields, $subModel['model']);
      }
    }
    elseif ($submittedRecord['id'] === 'occurrence') {
      if (!isset($submittedRecord['subModels'])) {
        $submittedRecord['subModels'] = [];
      }
      $submittedRecord['subModels'][] = [
        'fkId' => 'occurrence_id',
        'model' => [
          'id' => 'dna_occurrence',
          'fields' => $dnaFields,
        ],
      ];
    }
  }

}
