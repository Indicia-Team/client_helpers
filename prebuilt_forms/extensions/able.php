<?php

/**
 * @file
 * Extension controls specific to ABLE/EBMS.
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
 * @link https://github.com/indicia-team/
 */

/**
 * Extension controls specific to ABLE/EBMS.
 */
class extension_able {

  /**
   * Load countries from a user's permissions filters from the database.
   *
   * @param string $sharingCode
   *   E.g. V (verification) or D (download)
   * @param array $auth
   *   Auth tokens.
   *
   * @return array
   *   Array of location IDs for country boundaries associated with filters.
   */
  private static function getCountriesInFilters($sharingCode, $auth) {
    $filters = report_filters_load_existing($auth['read'], $sharingCode, TRUE);
    $countryIds = [];
    foreach ($filters as $filter) {
      if ($filter['defines_permissions'] === 't') {
        $filterDef = json_decode($filter['definition']);
        if (isset($filterDef->indexed_location_list)) {
          $countryIds = array_merge(explode(',', $filterDef->indexed_location_list), $countryIds);
        }
        else {
          // A filter with no geographic bounds allows all.
          $countryIds[] = '-';
        }
      }
    }
    return $countryIds;
  }

  private static function getSchemeCoordinatorCountries() {
    $countryIds = [];
    if (hostsite_user_has_permission('country manager')) {
      $account = \Drupal::currentUser();
      if ($account->id()) {
        $user = \Drupal\user\Entity\User::load($account->id());
        $referenceItem = $user->get('field_associated_scheme')->first();
        if ($referenceItem) {
          $entityReference = $referenceItem->get('entity');
          $entityAdapter = $entityReference->getTarget();
          $referencedEntity = $entityAdapter->getValue();
          // Force string so array_unique will work later.
          $countryIds[] = (string) $referencedEntity->get('field_associated_country')->value;
        }
      }
    }
    return $countryIds;
  }

  private static function getOptions($countryIds, $auth) {
    $options = [];
    // Is there an All option?
    if (in_array('-', $countryIds)) {
      $options[] = '<option value="-">' . lang::get('All data') . '</option>';
    }
    $countryIdList = array_diff($countryIds, ['-']);
    if (count($countryIdList)) {
      $countries = helper_base::get_population_data([
        'table' => 'location',
        'orderby' => 'name',
        'extraParams' => ['query' => json_encode(['in' => ['id' => $countryIdList]])] + $auth['read'],
      ]);
      foreach ($countries as $country) {
        $options[] = "<option value=\"$country[id]\">" . lang::get($country['name']) . '</option>';
      }
    }
    return implode("\n    ", $options);
  }

  public static function download_permissions_control($auth, $args, $tabalias, $options, $path) {
    iform_load_helpers(['ElasticsearchReportHelper']);
    require_once helper_base::relative_client_helper_path() . 'prebuilt_forms/includes/report_filters.php';
    $verificationCountries = self::getCountriesInFilters('V', $auth);
    $downloadCountries = self::getCountriesInFilters('D', $auth);
    $schemeCountries = self::getSchemeCoordinatorCountries();
    $countryIds = array_unique(array_merge($verificationCountries, $downloadCountries, $schemeCountries));
    $langDataset = lang::get('Dataset');
    $langPleaseSelect = lang::get('Please select');
    $options = self::getOptions($countryIds, $auth);
    helper_base::addLanguageStringsToJs('downloadPermissionsControl', [
      'DownloadDataFor' => 'This page is filtered to download data for {1}.',
      'NoAccessRights' => 'You do not have any permissions to download data via this page.',
    ]);
    return <<<HTML
<div class="form-group">
  <label for="select-dataset">$langDataset:</label>
  <select class="form-control" id="select-dataset">
    <option value="">$langPleaseSelect</option>
    $options
  </select>
</div>
<input type="hidden" class="es-filter-param" id="select-dataset-location" data-es-bool-clause="must" value=""
  data-es-query="{&quot;nested&quot;:{&quot;path&quot;:&quot;location.higher_geography&quot;,&quot;query&quot;:{&quot;bool&quot;:{&quot;must&quot;:[{&quot;match&quot;:{&quot;location.higher_geography.id&quot;:&quot;#value#&quot;}}]}}}}" />
HTML;
  }

}
