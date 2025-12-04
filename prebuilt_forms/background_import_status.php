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
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/Indicia-Team/client_helpers
 */

use IForm\prebuilt_forms\PageType;
use IForm\prebuilt_forms\PrebuiltFormInterface;

/**
 * A page which shows the status of the user's background import tasks.
 */
class iform_background_import_status implements PrebuiltFormInterface {

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_background_import_status_definition() {
    return [
      'title' => 'Background import status',
      'category' => 'Utilities',
      'description' => "A summary of a user's background import tasks.",
      'helpLink' => 'http://indicia-docs.readthedocs.org/en/latest/site-building/iform/prebuilt-forms/background-import-status.html',
    ];
  }

  /**
   * Declare that this is a report page.
   *
   * {@inheritDoc}
   *
   * @return IForm\prebuilt_forms\PageType
   *   Page type.
   */
  public static function getPageType(): PageType {
    return PageType::Report;
  }

  /**
   * Get the list of parameters for this form.
   *
   * @param array $readAuth
   *   Read authorisation tokens, only required if the parameters form needs to
   *   access warehouse web-services.
   *
   * @return array
   *   List of parameters that this form requires.
   */
  public static function get_parameters(array $readAuth) {
    return [];
  }

  /**
   * Return the generated output.
   *
   * @param array $args
   *   List of parameter values passed through to the page.
   * @param object $nid
   *   The Drupal node object's ID.
   * @param array $response
   *   POST response, not used here.
   *
   * @return string
   *   Form HTML.
   */
  public static function get_form($args, $nid, $response = NULL) {
    global $indicia_templates;
    helper_base::add_resource('font_awesome');
    $conn = iform_get_connection_details($nid);
    $auth = helper_base::get_read_auth($conn['website_id'], $conn['password']);
    helper_base::$indiciaData['abandonUrl'] = hostsite_get_url('iform/ajax/background_import_status') . "/abandon_import/$nid";
    $data = [
      'website_id' => $conn['website_id'],
      'nonce' => $auth['nonce'],
      'auth_token' => $auth['auth_token'],
    ];
    if (hostsite_user_has_permission('indicia data admin') && !empty($_GET['config-id'])) {
      // Admins can search for an import by config-id.
      $data['config-id'] = $_GET['config-id'];
    }
    $serviceUrl = helper_base::$base_url . 'index.php/services/import_2/background_import_status';
    $response = helper_base::http_post($serviceUrl, $data);
    $importList = json_decode($response['output'], TRUE);
    $lang = [
      'abandonImport' => lang::get('Abandon import'),
      'errors' => lang::get('Errors'),
      'importProgress' => lang::get('Import progress'),
      'precheckProgress' => lang::get('Precheck progress'),
      'processedRows' => lang::get('Processed rows'),
      'totalRows' => lang::get('Total rows'),
    ];
    helper_base::addLanguageStringsToJs('backgroundImportStatus', [
      'confirmAbandon' => 'Are you sure you want to abandon this import? All details will be removed and cannot be recovered.',
      'errorOnAbort' => 'An error occurred whilst attempting to abandon the import: {1}',
      'errorOnAbortGenericMessage' => 'Internal Server Error.',
      'importAbandoned' => 'The import has been removed from the queue.',
    ]);
    $r = '';
    if (hostsite_user_has_permission('indicia data admin')) {
      // Admins are allowed to search for an import by config-id.
      $r .= '<form method="get">';
      iform_load_helpers(['data_entry_helper']);
      $r .= data_entry_helper::text_input([
        'label' => 'Import reference',
        'fieldname' => 'config-id',
        'default' => isset($_GET['config-id']) ? $_GET['config-id'] : '',
        'afterControl' => '<button type="submit"">Search</button>'
      ]);
      $r .= '</form>';
    }
    foreach ($importList as $idx => $import) {
      $importCreatedOnHeader = lang::get('Import {1} created on {2}', $idx + 1, $import['created_on']);
      $r .= '<div class="panel-body">';
      if ($import['error_detail']) {
        $status = 'Import failed.';
      }
      else {
        if ($import['state'] === 'prechecking' && $import['rowsProcessed'] === 0) {
          $status = 'Awaiting validation';
        }
        elseif ($import['state'] === 'prechecking') {
          $status = 'Validating data';
        }
        elseif ($import['state'] === 'importing' && $import['rowsProcessed'] === 0) {
          $status = 'Validation complete, awaiting import';
        }
        elseif ($import['state'] === 'importing' && $import['rowsProcessed'] < $import['totalRows']) {
          $status = 'Importing data';
        }
        elseif ($import['state'] === 'importing' && $import['rowsProcessed'] === $import['totalRows']) {
          $status = 'Import complete';
        }
      }
      $processed = $import['rowsProcessed'] ?? 0;
      $stateClass = $import['errorsCount'] > 0 ? ' error' : ($import['state'] === 'importing' && $import['rowsProcessed'] === $import['totalRows'] ? ' done' : '');
      $errorFileLink = $import['errorsCount'] > 0 ? '<a href="' . helper_base::$base_url . 'index.php/services/import_2/get_errors_file?config-id=' . $import['config-id'] . '" title="Download error file"><i class="fas fa-file-download"></i> Download</a>' : '';
      $precheckProgress = $import['state'] === 'prechecking' ? $import['rowsProcessed'] * 100 / $import['totalRows'] : ($import['state'] === 'importing' ? 100 : 0);
      $precheckProgressRounded = round($precheckProgress);
      $importProgress = $import['state'] === 'importing' ? $import['rowsProcessed'] * 100 / $import['totalRows'] : 0;
      $importProgressRounded = round($importProgress);
      $errorWarningBox = $import['errorsCount'] > 0 ? str_replace('{message}', lang::get('{1} errors have been found', $import['errorsCount']), $indicia_templates['warningBox']) : '';
      $errorDetailBox = '';
      if (!empty($import['error_detail'])) {
        if (hostsite_user_has_permission('indicia data admin')) {
          $decodedError = json_decode($import['error_detail'], TRUE);
          if ($decodedError) {
            $errorDetailBox = str_replace(
              '{message}',
              lang::get('Error detail: {1}', '<pre>' . htmlspecialchars(var_export($decodedError, TRUE)) . '</pre>'),
              $indicia_templates['warningBox']
            );
          }
          else {
            // Error detail not JSON, just display it raw.
            $errorDetailBox = str_replace('{message}', "Error detail: $import[error_detail]", $indicia_templates['warningBox']);
          }
        }
        else {
          $errorDetailBox = str_replace(
            '{message}',
            lang::get('An error occurred during the import. Please contact your site administrator for more details providing the import reference "{1}".', $import['config-id']),
            $indicia_templates['warningBox']
          );
        }
      }
      // If the import is stuck with an error, allow the user to remove the
      // work queue entry.
      $abandonButton = $import['errorsCount'] > 0 ? "<button class=\"abandon-btn $indicia_templates[buttonWarningClass]\" data-config-id=\"{$import['config-id']}\">$lang[abandonImport]</button>" : '';
      $r .= <<<HTML
        <section class="panel panel-default import-info">
          <header class="panel-heading">
            <p>$importCreatedOnHeader</p>
            <p class="helpText">Ref: {$import['config-id']}</p>
          </header>
          <div class="panel-body">
            <div class="import-dashboard$stateClass">
              <div class="dashboard-panel dashboard-status">
                <h2>$status</h2>
              </div>
              <div class="dashboard-panel">
                <h2>$lang[totalRows]</h2>
                <p>$import[totalRows]</p>
              </div>
              <div class="dashboard-panel">
                <h2>$lang[processedRows]</h2>
                <p>$processed</p>
              </div>
              <div class="dashboard-panel dashboard-errors">
                <h2>$lang[errors]</h2>
                <p>$import[errorsCount]</p>
                $errorFileLink
              </div>
            </div>
            <div class="progress-container">
              <span>$lang[precheckProgress] ($precheckProgressRounded)</span>
              <progress class="progress" max="100" value="$precheckProgress"></progress>
            </div>
            <div class="progress-container">
              <span>$lang[importProgress] ($importProgressRounded)</span>
              <progress class="progress" max="100" value="$importProgress"></progress>
            </div>
            $errorWarningBox
            $errorDetailBox
            $abandonButton
          </div>
        </section>
      HTML;
    }

    return $r;
  }

  /**
   * Ajax handler for when the user clicks to abandon a failed import.
   *
   * @param int $website_id
   *   Website ID for auth.
   * @param string $password
   *   Website password for auth.
   * @param int $nid
   *   Node ID.
   *
   * @return string
   *   JSON AJAX response.
   */
  public static function ajax_abandon_import($website_id, $password, $nid) {
    if (!hostsite_get_user_field('indicia_user_id')) {
      throw new Exception('User must be logged in and connected to the warehouse in order to abandon an import.');
    }
    if (empty($_GET['config-id'])) {
      throw new Exception('Attempt to abandon an import without specifying the import config-id parameter.');
    }
    iform_load_helpers(['import_helper_2']);
    $auth = import_helper_2::get_read_write_auth($website_id, $password);
    // @todo Error and response handling.
    import_helper_2::abandonBackgroundImport($_GET['config-id'], $auth['write_tokens']);
    return '{"status":"ok"}';
  }

}
