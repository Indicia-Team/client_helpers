<?php

class extension_inns_mapper {

  public static function init_regions_param($auth, $args, $tabalias, $options, $path) {
    iform_load_helpers(['report_helper']);
    $reportOptions = array(
      'dataSource' => 'projects/inns_mapper/get_user_regions',
      'readAuth' => $auth['read'],
      'extraParams' => array('user_id' => hostsite_get_user_field('indicia_user_id')),
    );
    $regions = json_encode(report_helper::get_report_data($reportOptions));
    report_helper::$javascript .= "indiciaData.userInnsRegions = $regions;\n";
    if (!empty($_REQUEST['dynamic-region_location_id'])) {
      report_helper::$javascript .= "indiciaData.regionLocationId = {$_REQUEST['dynamic-region_location_id']};\n";
    }
    return '';
  }

  public static function treatment_details($auth, $args, $tabalias, $options, $path) {
    return <<<HTML
<div id="treatment-details"></div>
HTML;
  }

}
