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
   * Load user's permissions filters from the database.
   *
   * @param string $sharingCode
   *   E.g. V (verification) or D (download)
   * @param array $auth
   *   Auth tokens.
   *
   * @return array
   *   List of <option> elements ready to add to a <select>.
   */
  private static function getFilterTypeOptions($sharingCode, $auth) {
    $filters = report_filters_load_existing($auth['read'], $sharingCode, TRUE);
    $options = [];
    foreach ($filters as $filter) {
      if ($filter['defines_permissions'] === 't') {
        $encodedFilter = htmlspecialchars($filter['definition']);
        $options[] = "<option value=\"filter_id:$filter[id]\" data-filter-def=\"$encodedFilter\">$filter[title]</option>";
      }
    }
    return $options;
  }

  private static function getSchemeCoordinatorOptions() {
    $options = [];
    if (hostsite_user_has_permission('country manager')) {
      $account = \Drupal::currentUser();
      if ($account->id()) {
        $user = \Drupal\user\Entity\User::load($account->id());
        $referenceItem = $user->get('field_associated_scheme')->first();
        if ($referenceItem) {
          $entityReference = $referenceItem->get('entity');
          $entityAdapter = $entityReference->getTarget();
          $referencedEntity = $entityAdapter->getValue();
          $schemeName = $referencedEntity->label();
          $countryLocationId = $referencedEntity->get('field_associated_country')->value;
          $options[] = "<option value=\"location_id:$countryLocationId\">$schemeName</option>";
        }
      }
    }
    return $options;
  }

  private static function getOptionsHtml(array $optionsArray, $needOptGroups, $title) {
    if (count($optionsArray) === 0) {
      return '';
    }
    else {
      $options = implode('', $optionsArray);
      if ($needOptGroups) {
        $options = "<optgroup label=\"$title\">$options</optgroup>";
      }
      return $options;
    }
  }

  public static function download_permissions_control($auth, $args, $tabalias, $options, $path) {
    iform_load_helpers(['ElasticsearchReportHelper']);
    require_once helper_base::relative_client_helper_path() . 'prebuilt_forms/includes/report_filters.php';
    $verificationOptionsArray = self::getFilterTypeOptions('V', $auth);
    $downloadOptionsArray = self::getFilterTypeOptions('D', $auth);
    $schemeOptionsArray = self::getSchemeCoordinatorOptions();
    $langDataset = lang::get('Dataset');
    $langPleaseSelect = lang::get('Please select');
    $numberOfDifferentDownloadCategories =
      (count($verificationOptionsArray) > 0 ? 1 : 0) +
      (count($downloadOptionsArray) > 0 ? 1 : 0) +
      (count($schemeOptionsArray) > 0 ? 1 : 0);
    if ($numberOfDifferentDownloadCategories === 0) {
      throw new Exception(lang::get('You do not have permission to download data on this page.'));
    }
    $needOptGroups = $numberOfDifferentDownloadCategories > 1;
    $options = self::getOptionsHtml($verificationOptionsArray, $needOptGroups, lang::get('Verification datasets'));
    $options .= self::getOptionsHtml($downloadOptionsArray, $needOptGroups, lang::get('Download datasets'));
    $options .= self::getOptionsHtml($schemeOptionsArray, $needOptGroups, lang::get('Scheme coordinator datasets'));
    helper_base::addLanguageStringsToJs('downloadPermissionsControl', [
      'DownloadDataFor' => 'This page is filtered to download data for {1}.',
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
<input type="hidden" id="select-dataset-filter" class="user-filter" value="" />
HTML;
  }

}
