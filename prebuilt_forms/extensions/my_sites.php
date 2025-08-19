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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/Indicia-Team/client_helpers
 */

/**
 * Extension class that supplies new controls to support My Sites selection for dynamic forms.
 */
class extension_my_sites {

  /**
   * A form for selecting existing sites.
   *
   * Options include:
   * * @includeSelectExistingSitesControls - default true. Set to false to
   *   disable the form controls for adding existing sites, in which case this
   *   control just adds JavaScript to support the removal of sites from a
   *   user's list.
   */
  public static function my_sites_form($auth, $args, $tabalias, $options, $path) {
    if (!function_exists('iform_ajaxproxy_url')) {
      return 'An AJAX Proxy module must be enabled for the My Sites Form to work.';
    }
    if (empty($options['mySitesPsnAttrId']) || !preg_match('/^[0-9]+$/', $options['mySitesPsnAttrId'])) {
      return 'The My sites form is not correctly configured. Please provide the person attribute ID used to store My Sites.';
    }
    $options = array_merge([
      'includeSelectExistingSitesControls' => TRUE,
    ], $options);
    $r = '';
    if ($options['includeSelectExistingSitesControls']) {
      if (empty($options['locationTypes']) || !preg_match('/^([0-9]+,( )?)*[0-9]+$/', $options['locationTypes'])) {
        return 'The My sites form is not correctly configured. Please provide the location types to allow search by.';
      }
      if (empty($options['locationTypeResults']) || !preg_match('/^([0-9]+,( )?)*[0-9]+$/', $options['locationTypeResults'])) {
        return 'The My sites form is not correctly configured. Please provide the location types to allow results to be returned for.';
      }
      $locationTypes = explode(',', str_replace(' ', '', $options['locationTypes']));
      $r = "<fieldset><legend>" . lang::get('Find additional sites to store in your sites list') . "</legend>";
      $localityOpts = [
        'fieldname' => 'locality_id',
        'id' => 'locality_id',
        'extraParams' => $auth['read'] + ['orderby' => 'name'],
        'blankText' => '<' . lang::get('all') . '>',
      ];
      if (count($locationTypes) > 1) {
        $r .= '<label>' . lang::get('Select site by type then locality:') . '</label> ';
        $r .= data_entry_helper::select([
          'fieldname' => 'location_type_id',
          'table' => 'termlists_term',
          'valueField' => 'id',
          'captionField' => 'term',
          'extraParams' => $auth['read'] + [
            'orderby' => 'term',
            'query' => urlencode(json_encode(['in' => ['id', $locationTypes]])),
          ],
          'blankText' => '<' . lang::get('please select') . '>',
        ]);
        // Link the locality select to the location type select.
        $localityOpts = array_merge([
          'parentControlId' => 'location_type_id',
          'parentControlLabel' => lang::get('Site type to search'),
          'filterField' => 'location_type_id',
          'emptyFilterIsUnfiltered' => TRUE,
        ], $localityOpts);
      }
      else {
        $r .= '<label>' . lang::get('Select site by locality') . '</label> ';
        // No need for a locality select, so just filter to the location type.
        $localityOpts['extraParams']['location_type_id'] = $locationTypes[0];
        $localityOpts['default'] = hostsite_get_user_field('location');
      }
      $r .= data_entry_helper::location_select($localityOpts);
      $r .= data_entry_helper::location_select([
        'id' => 'location-select',
        'report' => 'library/locations/locations_for_my_sites',
        'table' => '',
        'valueField' => 'location_id',
        'captionField' => 'q',
        'extraParams' => $auth['read'] + [
          'location_type_ids' => $options['locationTypeResults'],
          'locattrs' => '',
          'user_id' => hostsite_get_user_field('indicia_user_id'),
          'person_site_attr_id' => $options['mySitesPsnAttrId'],
          'hide_existing' => 1,
        ],
        'parentControlId' => 'locality_id',
        'parentControlLabel' => lang::get('Locality to search'),
        'filterField' => 'parent_id',
        'filterIncludesNulls' => FALSE,
        'blankText' => '<' . lang::get('please select') . '>'
      ]);
      $r .= '<button id="add-site-button" type="button">' . lang::get('Add to My Sites') . '</button><br/>';
      $r .= data_entry_helper::location_autocomplete([
        'id' => 'location-search',
        'label' => lang::get('<strong>Or</strong> search for a site'),
        'report' => 'library/locations/locations_for_my_sites',
        'table' => '',
        'valueField' => 'location_id',
        'captionField' => 'q',
        'extraParams' => $auth['read'] + [
          'location_type_ids' => $options['locationTypeResults'],
          'locattrs' => '',
          'user_id' => hostsite_get_user_field('indicia_user_id'),
          'person_site_attr_id' => $options['mySitesPsnAttrId'],
          'hide_existing' => 1,
          'parent_id' => '',
        ]
      ]);
      $r .= '<button id="add-searched-site-button" type="button">' . lang::get('Add to My Sites') . '</button><br/>';
    }
    $postUrl = iform_ajaxproxy_url(NULL, 'person_attribute_value');
    $indiciaUserId = hostsite_get_user_field('indicia_user_id');
    data_entry_helper::addLanguageStringsToJs('mySites', [
      'confirmDelete' => 'Are you sure you want to remove this site from your list of sites?',
      'no' => 'No',
      'yes' => 'Yes',
    ]);
    data_entry_helper::$javascript .= <<<JS

function addSite(locationId) {
  if (!isNaN(locationId) && locationId !== '') {
    $.post('$postUrl',
      {
        website_id: $args[website_id],
        person_attribute_id: $options[mySitesPsnAttrId],
        user_id: $indiciaUserId,
        int_value: locationId
      },
      function (data) {
        if (typeof data.error === 'undefined') {
          indiciaData.reports.dynamic.grid_report_grid_0.reload(true);
        } else {
          alert(data.error);
        }
      },
      'json'
    );
  }
}
$('#add-site-button').on('click', function() {
  addSite($('#location-select').val());
  if (!isNaN($('#location-select').val())) {
    $('#location-select option:selected').remove();
  }
});
$('#add-searched-site-button').on('click', function() {addSite($('#location-search').val());});
$('#location-select, #location-search, #locality_id').change(function() {
  if (typeof indiciaData.mapdiv!=='undefined') {
    indiciaData.mapdiv.locationSelectedInInput(indiciaData.mapdiv, this.value);
  }
});

linked_site_delete = function(pav_id) {
  $.fancyDialog({
    title: null,
    message: indiciaData.lang.mySites.confirmDelete,
    okButton: indiciaData.lang.mySites.yes,
    cancelButton: indiciaData.lang.mySites.no,
    callbackOk: function () {
      $.post('$postUrl',
        {
          "website_id": $args[website_id],
          "id": pav_id,
          "deleted": "t"
        },
        function (data) {
          if (typeof data.error === 'undefined') {
            indiciaData.reports.dynamic.grid_report_grid_0.reload(true);
          } else {
            alert(data.error);
          }
        },
        'json'
      );
    }
  });

}

JS;
    $r .= '</fieldset>';
    return $r;
  }

  /**
   * A select box allowing you to pick one of your sites.
   *
   * Supply @personSiteAttrId as an option to give the ID of the person custom
   * attribute used to link people to their sites.
   */
  public function my_sites_select($auth, $args, $tabalias, $options, $path) {
    $location_list_args = array_merge_recursive([
      'extraParams' => array_merge(['orderby' => 'name'], $auth['read']),
    ], $options);
    if (!isset($location_list_args['label'])) {
      $location_list_args['label'] = lang::get('Select site');
    }
    $userId = hostsite_get_user_field('indicia_user_id');
    if (!empty($userId)) {
      if (!empty($options['personSiteAttrId'])) {
        $location_list_args['extraParams']['user_id'] = $userId;
        $location_list_args['extraParams']['person_site_attr_id'] = $options['personSiteAttrId'];
        $location_list_args['report'] = 'library/locations/my_sites_lookup';
      }
      else {
        $location_list_args['extraParams']['created_by_id'] = $userId;
      }
    }
    $location_list_args['extraParams']['view'] = 'detail';
    $location_list_args['allowCreate'] = TRUE;
    return data_entry_helper::location_autocomplete($location_list_args);
  }

  /*
   * Control allows administrators to maintain the "my sites" list for other users.
   *
   * @locationParamFromURL can be supplied as an option to hide the locations drop-down and automatically get the location id from the $_GET url parameter, this option should be set as the
   * name of the parameter when it is in use.
   * @userParamFromURL can be set in a very similar way, but this would hide the user drop down instead. This could be used in the situation where
   * several sites need to be linked to a single user on a user maintenance page.
   * @postCodeGeomParamName AND @distanceFromPostCodeParamName can be set together to names of $_GET parameters in the URL which
   * supply a post code geometry and distance to limit locations in the location drop-down parameter to
   * @fieldSetLegend can be set to override default legend text for the fieldset
   * @addbuttonLabel can be set to override default text for the button that adds the location to the list.
   * @locationDropDownLabel can be set to override the label of the Location drop-down.
   */
  public static function add_sites_to_any_user($auth, $args, $tabalias, $options, $path) {
    if (!function_exists('iform_ajaxproxy_url')) {
      return 'An AJAX Proxy module must be enabled for user sites administration to work.';
    }
    if (!empty($options['locationDropDownLabel'])) {
      $locationDropDownLabel = $options['locationDropDownLabel'] . ' :';
    }
    else {
      $locationDropDownLabel = lang::get('Location :');
    }
    if (!empty($options['addButtonLabel'])) {
      $addButtonLabel = $options['addButtonLabel'];
    }
    else {
      $addButtonLabel = lang::get('Add to this User\'s Sites List');
    }
    if (!empty($options['fieldSetLegend'])) {
      $fieldSetLegendText = $options['fieldSetLegend'];
    }
    else {
      $fieldSetLegendText = lang::get('Add locations to the sites lists for other users');
    }
    $r = "<form><fieldset><legend>$fieldSetLegendText</legend>";
    if (empty($options['locationTypes']) || !preg_match('/^([0-9]+,( )?)*[0-9]+$/', $options['locationTypes'])) {
      return 'The sites form is not correctly configured. Please provide the location type you can add.';
    }
    if (empty($options['mySitesPsnAttrId']) || !preg_match('/^[0-9]+$/', $options['mySitesPsnAttrId'])) {
      return 'The sites form is not correctly configured. Please provide the person attribute ID used to store My Sites.';
    }
    if (!empty($options['locationParamFromURL'])&&!empty($_GET[$options['locationParamFromURL']])) {
      $locationIdFromURL = $_GET[$options['locationParamFromURL']];
    }
    else {
      $locationIdFromURL = 0;
    }
    // Get the user_id from the URL if we can, this would hide the user
    // drop-down and make the control applicable to a single user.
    if (!empty($options['userParamFromURL']) && !empty($_GET[$options['userParamFromURL']])) {
      $userIdFromURL = $_GET[$options['userParamFromURL']];
    }
    // This line is here to make sure we don't brake the existing code, this was hard-coded, now
    // the param is soft-coded we still need this hard-coded param here.
    elseif (!empty($_GET['dynamic-the_user_id'])) {
      $userIdFromURL = $_GET['dynamic-the_user_id'];
    }
    else {
      $userIdFromURL = 0;
    }
    $extraParams = [
      'location_type_ids' => $options['locationTypes'],
      'user_id' => hostsite_get_user_field('indicia_user_id'),
      'my_sites_person_attr_id' => $options['mySitesPsnAttrId'],
    ];
    // Can limit results in location drop-down to certain distance of a post
    // code.
    if (!empty($options['postCodeGeomParamName']) && !empty($_GET[$options['postCodeGeomParamName']])) {
      $extraParams['post_code_geom'] = $_GET[$options['postCodeGeomParamName']];
    }
    if (!empty($options['distanceFromPostCodeParamName']) && !empty($_GET[$options['distanceFromPostCodeParamName']])) {
      $extraParams['distance_from_post_code'] = $_GET[$options['distanceFromPostCodeParamName']];
    }
    // If we don't want to automatically get the location id from the URL, then
    // display a drop-down of locations the user can select from.
    if (empty($locationIdFromURL)) {
      $r .= '<label>' . $locationDropDownLabel . '</label> ';
      // Get a list of all the locations that match the given location types
      // (in this case my sites are returned first, although this isn't a
      // requirement).
      $r .= data_entry_helper::location_select([
        'id' => 'location-select',
        'nocache' => TRUE,
        'report' => 'reports_for_prebuilt_forms/Shorewatch/locations_with_my_sites_first',
        'extraParams' => $auth['read'] + $extraParams,
        'blankText' => '<' . lang::get('please select') . '>',
      ]);
    }
    // Get the user select control if the user id isn't in the url.
    if (empty($userIdFromURL)) {
      $r .= self:: user_select_for_add_sites_to_any_user_control($auth['read'], $args['website_id'], $options);
    }

    $r .= '<input id="add-user-site-button" type="button" value="' . $addButtonLabel . '"/><br></form><br>';

    $postUrl = iform_ajaxproxy_url(NULL, 'person_attribute_value');

    // Firstly check both a uer and location have been selected.
    // Then get the current user/sites saved in the database and if the new
    // combination doesn't already exist then call a function to add it.
    data_entry_helper::$javascript .= <<<JS

function duplicateCheck(locationId, userId) {
  var userIdToAdd = userId;
  var locationIdToAdd = locationId;
  var sitesReport = indiciaData.read.url +'/index.php/services/report/requestReport?report=library/locations/all_user_sites.xml&mode=json&mode=json&callback=?';

  var sitesReportParameters = {
    'person_site_attr_id': '$options[mySitesPsnAttrId]',
    'auth_token': indiciaData.read.auth_token,
    'nonce': indiciaData.read.nonce,
    'reportSource':'local'
  };

  if (!userIdToAdd || !locationIdToAdd) {
    alert('Please select both a user and a location to add.');
  } else {
    $.getJSON (
      sitesReport,
      sitesReportParameters,
      function (data) {
        var duplicateDetected=false;
        $.each(data, function(i, dataItem) {
          if (userIdToAdd == dataItem.pav_user_id&&locationIdToAdd == dataItem.location_id) {
              duplicateDetected = true;
          }
        });
        if (duplicateDetected === true) {
          alert('The site/user combination you are adding already exists in the database.');
        } else {
          addUserSiteData(locationId, userIdToAdd);
        }
      }
    );
  }
}

JS;

    // After duplicate check is performed, add the user/site combination to the
    // person_attribute_values database table.
    data_entry_helper::$javascript .= <<<JS

function addUserSiteData(locationId, userIdToAdd) {
  if (!isNaN(locationId) && locationId!=='') {
    $.post('$postUrl',
      {
        website_id: $args[website_id],
        person_attribute_id: $options[mySitesPsnAttrId],
        user_id: userIdToAdd,
        int_value: locationId
      },
      function (data) {
        if (typeof data.error === 'undefined') {
          alert('User site configuration saved successfully');
          location.reload();
        } else {
          alert(data.error);
        }
      },
      'json'
    );
  }
}

JS;
    // Call duplicate check when administrator elects to save a user/site
    // combination.
    data_entry_helper::$javascript .= <<<JS

$('#add-user-site-button').on('click', function() {
  // We can get the location id from the url or from the locations drop-down
  // depending on the option the administrator has set.
  var locationId;
  var userId;
  if ($locationIdFromURL) {
    locationId = $locationIdFromURL;
  } else {
    locationId = $('#location-select').val();
  }
  if ($userIdFromURL) {
    userId = $userIdFromURL;
  } else {
    userId = $('#user-select').val();
  }
  duplicateCheck(locationId,userId);
});

$('#location-select, #location-search, #locality_id').change(function() {
  if (typeof indiciaData.mapdiv !== 'undefined') {
    // Zoom map as user selects locations.
    indiciaData.mapdiv.locationSelectedInInput(indiciaData.mapdiv, this.value);
  }
});

// Function for when user elects to remove sites.
user_site_delete = function(pav_id) {
  $.post('$postUrl',
    {
      website_id: $args[website_id],
      id: pav_id,
      deleted: 't'
    },
    function (data) {
      if (typeof data.error === 'undefined') {
        location.reload();
      } else {
        alert(data.error);
      }
    },
    'json'
  );
}

JS;
    return $r;
  }

  /*
   * User select drop-down for sites administation control
   *
   * @param array $readAuth
   *   Read authentication
   * @param int $website_id
   *   Website ID.
   * @param array $options
   *   Options passed to the control. Options array including the following
   *   possibilities:
   *   * userCaptionField - Override the default caption field. Optional.
   */
  private static function user_select_for_add_sites_to_any_user_control($readAuth, $website_id, $options) {
    $r = '';
    $userCaptionField = 'fullname_surname_first';
    if (!empty($options['userCaptionField'])) {
      $userCaptionField = $options['userCaptionField'];
    }
    if (!empty($options['useAutocomplete']) && $options['useAutocomplete'] == TRUE) {
      $r .= data_entry_helper::autocomplete([
        'report' => 'library/users/get_people_details_for_website_or_user',
        'extraParams' => $readAuth + ['website_id' => $website_id],
        'id' => 'user-select',
        'fieldname' => 'user-select',
        'label' => lang::get('Select a user'),
        'helpText' => lang::get('Select a location to input data for before selecting a site.'),
        'captionField' => $userCaptionField,
        'valueField' => 'id',
      ]);
    }
    else {
      $reportOptions = [
        'dataSource' => 'library/users/get_people_details_for_website_or_user',
        'readAuth' => $readAuth,
        'extraParams' => ['website_id' => $website_id],
        'valueField' => 'id',
        'captionField' => $userCaptionField,
      ];
      iform_load_helpers(['report_helper']);
      $userData = report_helper::get_report_data($reportOptions);
      $selectHtml = '<select id="user-select">\n';
      $selectHtml .= '<option value="">' . lang::get('please select') . '</option>\n';
      foreach ($userData as $userItem) {
        $selectHtml .= "<option value=\"$userItem[id]\">$userItem[fullname_surname_first]</option>";
      }
      $selectHtml .= '</select>';
      $r .= '<label>User : </label>' . $selectHtml . '<br>';
    }
    return $r;
  }

}
