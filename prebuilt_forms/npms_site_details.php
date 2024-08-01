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
 * ## DOCUMENTATION FOR NPMS DEVELOPERS #####################################
 * This pre-built form provides functionality that is specific to the NPMS
 * website. The form consists of:
 * 1. This PHP file (prebuilt_forms/npms_site_details.php)
 * 2. A Javascript file (prebuilt_forms/js/npms_site_details.js)
 * 3. A CSS file (prebuilt_forms/css/npms_site_details.css)
 * This form has a depenency on the custom npms_vis module.
 * It depends on libraries loaded by that module when the module detects that
 * an iForm page of this type (npms_site_details) is loaded. The npms_vis
 * module also includes a image resource used by this form.
 * Development of this custom form functionality was made easier by adding 
 * resources to the npms_vis module rather than the client_helpers module.
 * ##########################################################################
 *
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link http://code.google.com/p/indicia/
 */

    require_once('includes/report.php');

class iform_npms_site_details {

  /**
   * Return the form metadata.
   *
   * Note the title of this method includes the name of the form file. This
   * ensures that if inheritance is used in the forms, subclassed forms don't
   * return their parent's form definition.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_npms_site_details_definition() {
    return array(
      'title' => 'NPMS site details',
      'category' => 'NPMS Specific forms',
      'description' => 'Implements an NPMS core square summary page',
    );
  }

  /**
   * Get the list of parameters for this form.
   *
   * @return array
   *   List of parameters that this form requires.
   */
  public static function get_parameters() {
    $r = array_merge(
        iform_report_get_minimal_report_parameters(),
        array()
    );
    return $r;
  }

  /**
   * Return the generated form output.
   *
   * @param array $args
   *   List of parameter values passed through to the form depending on how the
   *   form has been configured. This array always contains a value for
   *   language.
   * @param object $nid
   *   The Drupal node object's ID.
   * @param array $response
   *   When this form is reloading after saving a submission, contains the
   *   response from the service call. Note this does not apply when
   *   redirecting (in this case the details of the saved object are in the
   *   $_GET data).
   *
   * @return string
   *   Form HTML.
   */
  public static function get_form($args, $nid, $response = NULL) {

    $errors=false;
    if ($args['report_name'] == null) {
        \Drupal::messenger()->addStatus(t('You must set a report that retrieves the site details.'));
        $errors=true;
    }
    if (empty($_GET['gr'])) {
        \Drupal::messenger()->addStatus(t('This form requires a gr parameter in the URL to identify the site.'));
        $errors=true;
    }
    if ($errors) {
        return;
    }

    helper_base::add_resource('leaflet');
    helper_base::add_resource('bigr');
    helper_base::add_resource('d3');
    // Note that BRC charts library is added from the NPMS custom module
    // because a newer version is needed than the one in iform and it
    // it is easier to control and update from NPMS custom module.
    // The npms_vis module also loads the lightgallery & assciated plugin 
    // libraries. Again it is easier to add these to the npms_vis module 
    // than to the iForm client_helpers module.

    iform_load_helpers(array('report_helper'));

    report_helper::$javascript.="indiciaData.npmsCoreSquareDetails={gr: '" . $_GET['gr'] . "'};";

    $read_auth=report_helper::get_read_auth($args['website_id'], $args['password']);
    $opts=array(
      'mode' => 'report',
      'dataSource' => $args['report_name'],
      'readAuth' => $read_auth,
      'extraParams' => array('gr' => $_GET['gr'])
    );
    $gridSquareOverview = report_helper::request_report($response, $opts, $currentParamValues, false);

    $rec=$response['records'][0];
    $cols=$response['columns'];

    report_helper::$javascript.="indiciaData.npmsCoreSquareReport=" . json_encode($response) . ";";

    $html="<div class='row'>";
    $html.="<div class='col-md-6'>";
    $html.="<h4>Core square " . $_GET['gr'] . "</h4>";
    $html.='<div id="mapid"></div>';
    $html.="<div style='font-size:0.7em'>Click a plot on the map to see details.</div>";
    $html.="<div><b>Core square coverage:</b></div>";
    $html.='<div id="core-square-layers"></div>';
    $html.="</div>";
    $html.="<div class='col-md-6'>";
    $html.="<h4>Summary info</h4>";
    $html.='<div id="summary-info"></div>';
    $html.='<div id="chart-taxa-by-year"></div>';
    $html.="</div>";
    $html.="</div>";
    $html.="<h4>Images</h4>";
    $html.='<div id="gallery" style="margin-bottom: 1.5em"></div>';
    $html.="<h4>Taxa</h4>";
    $html.='<div id="taxa"></div>';

    // Taxa by year report
    $opts['dataSource'] = 'projects/npms/npms_core_square_taxa_by_year';
    $taxaByYear = report_helper::get_report_data($opts);
    report_helper::$javascript.="indiciaData.npmsCoreSquareTaxaByYear=" . json_encode($taxaByYear) . ";";

    // Taxa by plot report
    $opts['dataSource'] = 'projects/npms/npms_core_square_taxa_by_plot';
    $taxaByPlot = report_helper::get_report_data($opts);
    report_helper::$javascript.="indiciaData.npmsCoreSquareTaxaByPlot=" . json_encode($taxaByPlot) . ";";

    // Layers for core square report
    // Uses an existing report that works on location id rather than location name (gr)
    // Get the id from core square details report. This can return a number of rows
    // matching the core square name, but only one of them has an ID - the others are null.
    // So first filter out the null records.
    $nonNullIDs = array_filter($response['records'], function ($var) {
      return (!empty($var['id']));
    });
    $core_square_id = array_column($nonNullIDs, null, 'name')[$_GET['gr']]['id'];
    $opts['dataSource'] = 'projects/npms/get_layers_for_square';
    $opts['extraParams'] = array(
      'location_id' => $core_square_id, 
      'core_square_location_type_id' => '4009',
      'layer_location_types' => '15,2412,5702,2187,17555,17558,17581'
    );
    $layers = report_helper::get_report_data($opts);
    report_helper::$javascript.="indiciaData.npmsCoreSquareLayers=" . json_encode($layers) . ";";

    // Location and sample media report
    $opts['dataSource'] = 'projects/npms/npms_core_square_location_sample_media';
    $opts['extraParams'] = array('gr' => $_GET['gr']);
    $media = report_helper::get_report_data($opts);
    report_helper::$javascript.="indiciaData.npmsCoreSquareLocationSampleMedia=" . json_encode($media) . ";";

helper_base::$late_javascript .= <<<JS

  npmsFns.displayInfo()
JS;

    return $html;
  }

  /**
   * Build a submission array.
   *
   * Optional. Handles the construction of a submission array from a set of
   * form values. Can be ommitted when the prebuilt form does not submit data
   * via a form post. For example, the following represents a submission
   * structure for a simple sample and 1 occurrence submission.
   * `return data_entry_helper::build_sample_occurrence_submission($values);`
   * `
   * @param array $values
   *   Associative array of form data values.
   *
   * @param array $args
   *   IForm parameters.
   *
   * @return array
   *   Submission structure.
   *
   * @todo: Implement or remove this method
   */
  public static function get_submission($values, $args) {

  }

  /**
   * Calculate redirection after submission.
   *
   * Optional method to override the page that is redirected to after a
   * successful save operation. This allows the destination to be chosen
   * dynamically.
   *
   * @param array $values
   *   Associative array of form data values.
   * @param array $args
   *   IForm parameters.
   *
   * @return string
   *   Destination URL.
   *
   * @todo: Implement or remove this method.
   */
  public static function get_redirect_on_success($values, $args) {
    return '';
  }

}
