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
      $r .= '<section class="panel panel-default import-info">';
      $r .= '<header class="panel-heading">Import ' . ($idx + 1) . ' created on ' . $import['created_on'] . '</header>';
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
      $errorFileLink = $import['errorsCount'] > 0 ? '<a href="' . helper_base::$base_url . 'index.php/services/import_2/get_errors_file?config-id=' . $import['config-id'] . '" title="Download error file"><i class="fas fa-file-download"></i></a>' : '';
      $r .= <<<HTML
        <div class="import-dashboard$stateClass">
          <div class="dashboard-panel dashboard-status">
            <h2>$status</h2>
          </div>
          <div class="dashboard-panel">
            <h2>Total rows</h2>
            <p>$import[totalRows]</p>
          </div>
          <div class="dashboard-panel">
            <h2>Processed rows</h2>
            <p>$processed</p>
          </div>
          <div class="dashboard-panel dashboard-errors">
            <h2>Errors</h2>
            <p>$import[errorsCount]</p>
            $errorFileLink
          </div>
        </div>

      HTML;

      $precheckProgress = $import['state'] === 'prechecking' ? $import['rowsProcessed'] * 100 / $import['totalRows'] : ($import['state'] === 'importing' ? 100 : 0);
      $importProgress = $import['state'] === 'importing' ? $import['rowsProcessed'] * 100 / $import['totalRows'] : 0;
      $r .= '<div class="progress-container"><span>Precheck progress (' . round($precheckProgress) . '%)</span><progress class="progress" max="100" value="' . $precheckProgress . '"></progress></div>';
      $r .= '<div class="progress-container"><span>Import progress (' . round($importProgress) . '%)</span><progress class="progress" max="100" value="' . $importProgress . '"></progress></div>';
      if ($import['errorCount'] > 0) {
        $r .= str_replace('{message}', "$import[errorCount] errors have been found", $indicia_templates['warningBox']);
      }
      if (!empty($import['error_detail'])) {
        if (hostsite_user_has_permission('indicia data admin')) {
          $decodedError = json_decode($import['error_detail'], TRUE);
          if ($decodedError) {
            $r .= str_replace('{message}', 'Error detail: <pre>' . htmlspecialchars(var_export($decodedError, TRUE)) . '</pre>', $indicia_templates['warningBox']);
          }
          else {
            // Error detail not JSON, just display it raw.
            $r .= str_replace('{message}', "Error detail: $import[error_detail]", $indicia_templates['warningBox']);
          }
        }
        else {
          $r .= str_replace('{message}', 'An error occurred during the import. Please contact your site administrator for more details providing the import reference "' . $import['config-id'] . '".', $indicia_templates['warningBox']);
        }
      }
      $r .= '</div>';
      $r .= '</div>';
    }

    return $r;
  }

}
