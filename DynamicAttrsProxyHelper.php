<?php

/**
 * @file
 * A helper class for dynamic attribute proxy code.
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
 * @link https://github.com/indicia-team/client_helpers
 */

/**
 * A helper class for requests from client JS for info on dynamic attributes.
 */
class DynamicAttrsProxyHelper {

  /**
   * Factory method for the different actions provided by this helper.
   */
  public static function callMethod($method) {
    switch ($method) {
      case 'getSpeciesChecklistAttrs':
        return self::getSpeciesChecklistAttrs();

      default:
        throw new \Exception('Method not found', 404);
    }
  }

  /**
   * Ajax handler to retrieve the dynamic attrs for a taxon.
   *
   * Attribute HTML is echoed to the client.
   */
  private static function getSpeciesChecklistAttrs() {
    iform_load_helpers([]);
    $conn = iform_get_connection_details();
    $readAuth = helper_base::get_read_auth($conn['website_id'], $conn['password']);
    // Get the attributes for this taxon.
    $attrList = self::getOccurrenceDynamicAttrsList(
      $readAuth,
      $_GET['survey_id'],
      $_GET['taxa_taxon_list_ids'],
      NULL,
      $_GET['language'],
    );
    // Convert to a response with control HTML.
    $attrData = [];
    if (count($attrList) > 0) {
      // Only load if needed.
      require_once 'data_entry_helper.php';
      foreach ($attrList as $attr) {
        // Correct default field names.
        // @todo Share code for this with dynamic.php. Or better, tidy so not
        // necessary.
        $attr['default'] = $attr['default_value'];
        $attr['displayValue'] = $attr['default_value_caption'];
        $attr['defaultUpper'] = $attr['default_upper_value'];
        // Fieldname will be replaced by client-side JS.
        $attr['fieldname'] = 'tempNameToEnableValidation';
        $extraParams = [];
        $translate = FALSE;
        if (isset($_GET['attributeTermlistLanguageFilter'])) {
          if ($_GET['attributeTermlistLanguageFilter'] === '1') {
            $extraParams['language_iso'] = $_GET['language'];
          }
          elseif ($_GET['attributeTermlistLanguageFilter'] === 'clientI18n') {
            $extraParams['preferred'] = 't';
            $translate = TRUE;
          }
        }
        if ($attr['system_function']) {
          $attrData[] = [
            'attr' => $attr,
            'control' => data_entry_helper::outputAttribute($attr, [
              'label' => '',
              'extraParams' => $readAuth + $extraParams,
              'translate' => $translate,
              'blankText' => !empty($attr['validation_rules']) && strpos($attr['validation_rules'], 'required') !== FALSE
                ? lang::get('- please select -') : NULL,
            ]),
          ];
        }
      }
    }
    return $attrData;
  }

  /**
   * Retrieve the dynamic occAttr data for this taxon from the db.
   */
  private static function getOccurrenceDynamicAttrsList($readAuth, $surveyId, $ttlId, $stageTermlistsTermIds, $language) {
    $params = [
      'survey_id' => $surveyId,
      'taxa_taxon_list_id' => $ttlId,
      'language' => $language,
    ];
    if (!empty($stageTermlistsTermIds)) {
      $params['stage_termlists_term_ids'] = implode(',', $stageTermlistsTermIds);
    }
    $r = helper_base::get_population_data([
      'report' => "library/occurrence_attributes/occurrence_attributes_for_form_2",
      'extraParams' => $params + $readAuth,
      'caching' => FALSE,
    ]);
    return $r;
  }

  /**
   * Dynamic taxon linked attribute duplicate removal.
   *
   * If a higher taxon has an attribute linked to it and a lower taxon has
   * a different attribute of the same type, then the lower taxon's attribute
   * should take precedence. For example, a stage linked to Animalia would
   * be superceded by a stage attribute linked to Insecta.
   *
   * @param array $list
   *   List of attributes which will be modified to remove duplicates.
   */
  private static function removeDuplicateAttrs(array &$list) {
    // First build a list of the different types of attribute and work out
    // the highest taxon_rank_sort_order (i.e. the lowest rank) which has
    // attributes for each attribute type. Whilst doing this we can also
    // discard duplicates, e.g. if same attribute linked at several taxonomic
    // levels. Note that this all has to happen on a per-occurrence ID basis if
    // loading a list of occurrences to edit.
    $occurrences = [];
    foreach ($list as $idx => $attr) {
      // Find a unique identifier for the occurrence, if loading for a list.
      $occIdent = $attr['occurrence_id'] ? $attr['occurrence_id'] : '-';
      if (!isset($occurrences[$occIdent])) {
        $occurrences[$occIdent] = [
          'attrTypeSortOrders' => [],
          'attrIds' => [],
        ];
      }
      if (in_array($attr['attribute_id'], $occurrences[$occIdent]['attrIds'])) {
        unset($list[$idx]);
      }
      else {
        $occurrences[$occIdent]['attrIds'][] = $attr['attribute_id'];
        $attrTypeKey = self::getAttrTypeKey($attr);
        if (!empty($attrTypeKey)) {
          if (!array_key_exists($attrTypeKey, $occurrences[$occIdent]['attrTypeSortOrders']) ||
              (integer) $attr['attr_taxon_rank_sort_order'] > $occurrences[$occIdent]['attrTypeSortOrders'][$attrTypeKey]) {
                $occurrences[$occIdent]['attrTypeSortOrders'][$attrTypeKey] = (integer) $attr['attr_taxon_rank_sort_order'];
          }
        }
      }
    }
    // Now discard any attributes of a type, where there are attributes of the
    // same type attached to a lower rank taxon. E.g. a genus stage attribute
    // will cause a family stage attribute to be discarded.
    foreach ($list as $idx => $attr) {
      $occIdent = $attr['occurrence_id'] ? $attr['occurrence_id'] : '-';
      $attrTypeKey = self::getAttrTypeKey($attr);
      if (!empty($attrTypeKey) && $occurrences[$occIdent]['attrTypeSortOrders'][$attrTypeKey] > (integer) $attr['attr_taxon_rank_sort_order']) {
        unset($list[$idx]);
      }
    }
  }

  /**
   * Get a key name which defines the type of an attribute.
   *
   * Since sex/stage and abundance attributes interact, treat them as the same
   * thing for the purposes of duplicate removal when dynamic attributes are
   * loaded from different levels in the taxonomic hierarchy. Otherwise we
   * use the attribute's system function or term name (i.e. Darwin Core term).
   *
   * @param array $attr
   *   Attribute definition.
   *
   * @return string
   *   Key name.
   */
  private static function getAttrTypeKey(array $attr) {
    $sexStageAttrs = ['sex_stage', 'sex_stage_count'];
    // For the purposes of duplicate handling, we treat sex, stage and count
    // related data as the same thing.
    if (in_array($attr['system_function'], $sexStageAttrs)) {
      $key = 'sex/stage/count';
    }
    else {
      $key = empty($attr['system_function']) ? $attr['term_name'] : $attr['system_function'];
    }
    return $key;
  }

}
