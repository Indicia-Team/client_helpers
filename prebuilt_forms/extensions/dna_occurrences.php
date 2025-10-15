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
    $r = data_entry_helper::hidden_text([
      'fieldname' => 'dna_occurrence:id',
      'default' => data_entry_helper::$entity_to_load['dna_occurrence:id'] ?? NULL,
    ]);
    $r .= data_entry_helper::textarea([
      'fieldname' => 'dna_occurrence:associated_sequences',
      'label' => 'Associated sequences',
      'helpText' => 'A list (one per line) of identifiers (publication, global unique identifier, URI) of genetic sequence information associated with the record.',
    ]);
    $r .= data_entry_helper::textarea([
      'fieldname' => 'dna_occurrence:dna_sequence',
      'label' => 'DNA sequence',
      'helpText' => 'The DNA sequence.',
      'validation' => ['required'],
    ]);
    $r .= data_entry_helper::text_input([
      'fieldname' => 'dna_occurrence:target_gene',
      'label' => 'Target gene',
      'helpText' => 'Targeted gene or marker name for marker-based studies.',
      'validation' => ['required'],
    ]);
    $r .= data_entry_helper::text_input([
      'fieldname' => 'dna_occurrence:pcr_primer_reference',
      'label' => 'PCR primer reference',
      'helpText' => 'Reference for the primers.',
      'validation' => ['required'],
    ]);
    $r .= data_entry_helper::text_input([
      'fieldname' => 'dna_occurrence:env_medium',
      'label' => 'Environmental medium',
      'helpText' => 'The environmental medium which surrounded your sample or specimen prior to sampling. Should be a subclass of an ENVO material.',
    ]);
    $r .= data_entry_helper::text_input([
      'fieldname' => 'dna_occurrence:env_broad_scale',
      'label' => 'Broad-scale environment',
      'helpText' => "The broad-scale environment the sample or specimen came from. Subclass of ENVO's biome class.",
    ]);
    $r .= data_entry_helper::text_input([
      'fieldname' => 'dna_occurrence:otu_db',
      'label' => 'OTU database',
      'helpText' => 'The OTU database (i.e. sequences not generated as part of the current study) used to assigning taxonomy to OTUs or ASVs.',
    ]);
    $r .= data_entry_helper::text_input([
      'fieldname' => 'dna_occurrence:otu_seq_comp_appr',
      'label' => 'OTU sequence comparison approach',
      'helpText' => 'The OTU sequence comparison approach, such as tools and thresholds used to assign “species-level” names to OTUs or ASVs.',
    ]);
    $r .= data_entry_helper::text_input([
      'fieldname' => 'dna_occurrence:otu_class_appr',
      'label' => 'OTU classification approach',
      'helpText' => 'The OTU classification approach / algorithm and clustering level (if relevant) when defining OTUs or ASVs.',
    ]);
    $r .= data_entry_helper::text_input([
      'fieldname' => 'dna_occurrence:env_local_scale',
      'label' => 'Local-scale environment',
      'helpText' => 'The local environmental context the sample or specimen came from. Please use terms that are present in ENVO and which are of smaller spatial grain than your entry for env_broad_scale.',
    ]);
    $r .= data_entry_helper::text_input([
      'fieldname' => 'dna_occurrence:target_subfragment',
      'label' => 'Target subfragment',
      'helpText' => 'Name of subfragment of a gene or marker.',
    ]);
    $r .= data_entry_helper::text_input([
      'fieldname' => 'dna_occurrence:pcr_primer_name_forward',
      'label' => 'Forward PCR primer name',
      'helpText' => 'Name of the forward PCR primer that were used to amplify the sequence of the targeted gene, locus or subfragment.',
    ]);
    $r .= data_entry_helper::text_input([
      'fieldname' => 'dna_occurrence:pcr_primer_forward',
      'label' => 'Forward PCR primer',
      'helpText' => 'Forward PCR primer that was used to amplify the sequence of the targeted gene, locus or subfragment.',
    ]);
    $r .= data_entry_helper::text_input([
      'fieldname' => 'dna_occurrence:pcr_primer_name_reverse',
      'label' => 'Reverse PCR primer name',
      'helpText' => 'Name of the reverse PCR primer that was used to amplify the sequence of the targeted gene, locus or subfragment.',
    ]);
    $r .= data_entry_helper::text_input([
      'fieldname' => 'dna_occurrence:pcr_primer_reverse',
      'label' => 'Reverse PCR primer',
      'helpText' => 'Reverse PCR primer that was used to amplify the sequence of the targeted gene, locus or subfragment.',
    ]);
    // Tell the submission code that we are going to add extra data.
    $r .= data_entry_helper::hidden_text([
      'fieldname' => 'submission_extensions[]',
      'default' => 'dna_occurrences.build_submission_dna_occurrence',
    ]);
    return $r;
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
