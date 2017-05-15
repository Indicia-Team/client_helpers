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
 * @package	Client
 * @subpackage PrebuiltForms
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Extension class that supplies general extension controls that can be used with any project.
 */
class extension_misc_extensions {

  /**
   * General button link control can be placed on pages to link to another page.
   * $options Options array with the following possibilities:<ul>
   * <li><b>buttonLabel</b><br/>
   * The label that appears on the button. Mandatory</li>
   * <li><b>buttonLinkPath</b><br/>
   * The page the button is linking to. Mandatory</li>
   * <li><b>paramNameToPass</b><br/>
   * The name of a static parameter to pass to the receiving page. Optional but requires paramValueToPass when in use</li>
   * <li><b>paramValueToPass</b><br/>
   * The value of the static parameter to pass to the receiving page. e.g. passing a static location_type_id. Optional but requires paramNameToPass when in use</li>
   * User can also provide a value in braces to replace with the Drupal field for current user e.g. {field_indicia_user_id}</li>
   * <li><b>onlyShowWhenLoggedInStatus</b><br/>
   * If 1, then only show button for logged in users. If 2, only show link for users who are not logged in.
   * </ul>
   */
  public static function button_link($auth, $args, $tabalias, $options, $path) {
    global $user;
    //Check if we should only show button for logged in/or none logged in users
    if ((!empty($options['onlyShowWhenLoggedInStatus'])&&
           (($options['onlyShowWhenLoggedInStatus']==1 && $user->uid!=0)||
           ($options['onlyShowWhenLoggedInStatus']==2 && $user->uid===0)||
           ($options['onlyShowWhenLoggedInStatus']==false)))||
        empty($options['onlyShowWhenLoggedInStatus'])) {
      //Only display a button if the administrator has specified both a label and a link path for the button.
      if (!empty($options['buttonLabel'])&&!empty($options['buttonLinkPath'])) {
        if (!empty($options['paramNameToPass']) && !empty($options['paramValueToPass'])) {
          //If the param value to pass is in braces, then collect the Drupal field where the name is between the braces e.g. field_indicia_user_id
          if (substr($options['paramValueToPass'], 0, 1)==='{'&&substr($options['paramValueToPass'], -1)==='}') {
            //Chop of the {}
            $options['paramValueToPass']=substr($options['paramValueToPass'], 1, -1);
            //hostsite_get_user_field doesn't want field or profile at the front.
            $prefix = 'profile_';
            if (substr($options['paramValueToPass'], 0, strlen($prefix)) == $prefix) {
              $options['paramValueToPass'] = substr($options['paramValueToPass'], strlen($prefix));
            } 
            $prefix = 'field_';
            if (substr($options['paramValueToPass'], 0, strlen($prefix)) == $prefix) {
              $options['paramValueToPass'] = substr($options['paramValueToPass'], strlen($prefix));
            } 
            $paramValueFromUserField=hostsite_get_user_field($options['paramValueToPass']);
            //If we have collected the user field from the profile, then overwrite the existing value.
            if (!empty($paramValueFromUserField))
              $options['paramValueToPass']=$paramValueFromUserField;
          }
          $paramToPass=array($options['paramNameToPass']=>$options['paramValueToPass']);
        }
        $button = '<div>';
        $button .= '  <FORM>';
        $button .= "    <INPUT TYPE=\"button\" VALUE=\"".$options['buttonLabel']."\"";
        //Button can still be used without a parameter to pass
        if (!empty($paramToPass)) {
          $button .= "ONCLICK=\"window.location.href='".url($options['buttonLinkPath'], array('query'=>$paramToPass))."'\">";
        } else { 
          $button .= "ONCLICK=\"window.location.href='".url($options['buttonLinkPath'])."'\">";
        }
        $button .= '  </FORM>';
        $button .= '</div><br>';
      } else {
        drupal_set_message('A link button has been specified without a link path or button label, please fill in the @buttonLinkPath and @buttonLabel options');
        $button = '';
      }   
      return $button;
    } else
      return '';
  }
  
  /**
   * General text link control can be placed on pages to link to another page.
   * $options Options array with the following possibilities:<ul>
   * <li><b>label</b><br/>
   * The label that appears on the link. Mandatory</li>
   * <li><b>linkPath</b><br/>
   * The page to link to. Mandatory</li>
   * <li><b>paramNameToPass</b><br/>
   * The name of a static parameter to pass to the receiving page. Optional but requires paramValueToPass when in use</li>
   * <li><b>paramValueToPass</b><br/>
   * The value of the static parameter to pass to the receiving page. e.g. passing a static location_type_id. Optional but requires paramNameToPass when in use.
   * User can also provide a value in braces to replace with the Drupal field for current user e.g. {field_indicia_user_id}</li>
   * <li><b>onlyShowWhenLoggedInStatus</b><br/>
   * If 1, then only show link for logged in users. If 2, only show link for users who are not logged in.
   * <li><b>anchorId</b><br/>
   * Optional id for anchor link. This might be useful, for example, if you want to reference the anchor with jQuery to set the path in real-time.
   * </ul>
   */
  public static function text_link($auth, $args, $tabalias, $options, $path) {
    global $user;
    //Check if we should only show link for logged in/or none logged in users
    if ((!empty($options['onlyShowWhenLoggedInStatus'])&&
           (($options['onlyShowWhenLoggedInStatus']==1 && $user->uid!=0)||
           ($options['onlyShowWhenLoggedInStatus']==2 && $user->uid===0)||
           ($options['onlyShowWhenLoggedInStatus']==false)))||
        empty($options['onlyShowWhenLoggedInStatus'])) {
      //Only display a link if the administrator has specified both a label and a link.
      if (!empty($options['label'])&&!empty($options['linkPath'])) {
        if (!empty($options['paramNameToPass']) && !empty($options['paramValueToPass'])) {
          //If the param value to pass is in braces, then collect the Drupal field where the name is between the braces e.g. field_indicia_user_id
          if (substr($options['paramValueToPass'], 0, 1)==='{'&&substr($options['paramValueToPass'], -1)==='}') {
            //Chop of the {}
            $options['paramValueToPass']=substr($options['paramValueToPass'], 1, -1);
            //hostsite_get_user_field doesn't want field or profile at the front.
            $prefix = 'profile_';
            if (substr($options['paramValueToPass'], 0, strlen($prefix)) == $prefix) {
              $options['paramValueToPass'] = substr($options['paramValueToPass'], strlen($prefix));
            } 
            $prefix = 'field_';
            if (substr($options['paramValueToPass'], 0, strlen($prefix)) == $prefix) {
              $options['paramValueToPass'] = substr($options['paramValueToPass'], strlen($prefix));
            } 
            $paramValueFromUserField=hostsite_get_user_field($options['paramValueToPass']);
            //If we have collected the user field from the profile, then overwrite the existing value.
            if (!empty($paramValueFromUserField))
              $options['paramValueToPass']=$paramValueFromUserField;
          }
          $paramToPass=array($options['paramNameToPass']=>$options['paramValueToPass']);
        }
        $button = '<div>';
        //If an id option for the anchor is supplied then set the anchor id.
        //This might be useful, for example, if you want to reference the anchor with jQuery to set the path in real-time.
        if (!empty($options['anchorId']))
          $button .= "  <a id=\"".$options['anchorId']."\" ";
        else 
          $button .= "  <a  ";
        //Button can still be used without a parameter to pass
        if (!empty($paramToPass)) {
          $button .= "href=\"".url($options['linkPath'], array('query'=>$paramToPass))."\">";
        } else { 
          $button .= "href=\"".url($options['linkPath'])."\">";
        }
        $button .= $options['label'];
        $button .= '  </a>';
        $button .= '</div><br>';
      } else {
        drupal_set_message('A text link has been specified without a link path or label, please fill in the @linkPath and @label options');
        $button = '';
      }   
      return $button;
    } else
      return '';
  }
  
  /**
   * Adds JavaScript to the page allowing detection of whether the user has a certain permission.
   * Adds a setting indiciaData.permissions[permission name] = true or false.
   * Provide a setting called permissionName to identify the permission to check.
   */
  public static function js_has_permission($auth, $args, $tabalias, $options, $path) {
    static $done_js_has_permission=false;
    if (empty($options['permissionName']))
      return 'Please provide a setting @permissionName for the js_has_permission control.';
    $val = hostsite_user_has_permission($options['permissionName']) ? 'true' : 'false';
    if (!$done_js_has_permission) {
      data_entry_helper::$javascript .= "if (typeof indiciaData.permissions==='undefined') {
  indiciaData.permissions={};
}\n";
      $done_js_has_permission=true;
    }
    data_entry_helper::$javascript .= "indiciaData.permissions['$options[permissionName]']=$val;\n";
    return '';
  }
  
  /**
   * Adds JavaScript to the page to provide the value of a field in their user profile, allowing
   * JavaScript on the page to adjust behaviour depending on the value.
   * Provide an option called fieldName to specify the field to obtain the value for.
   */
  public static function js_user_field($auth, $args, $tabalias, $options, $path) {
    static $done_js_user_field=false;
    if (empty($options['fieldName']))
      return 'Please provide a setting @fieldName for the js_user_field control.';
    if (!function_exists('hostsite_get_user_field'))
      return 'Can\'t use the js_user_field extension without a hostsite_get_user_field function.';
    $val = hostsite_get_user_field($options['fieldName']);
    if ($val===true) 
      $val='true';
    elseif ($val===false) 
      $val='false';
    elseif (is_string($val)) 
      $val="'$val'";
    if (!$done_js_user_field) {
      data_entry_helper::$javascript .= "if (typeof indiciaData.userFields==='undefined') {
  indiciaData.userFields={};
}\n";
      $done_js_user_field=true;
    }
    data_entry_helper::$javascript .= "indiciaData.userFields['$options[fieldName]']=$val;\n";
    return '';
  }
  
  public static function data_entry_helper_control($auth, $args, $tabalias, $options, $path) {
    $ctrl = $options['control'];
    if (isset($options['extraParams']))
      $options['extraParams'] = $auth['read'] + $options['extraParams'];
    return data_entry_helper::$ctrl($options);
  }

  public static function map_helper_control($auth, $args, $tabalias, $options, $path) {
    iform_load_helpers(array('map_helper'));
    $ctrl = $options['control'];
    if (isset($options['extraParams']))
      $options['extraParams'] = $auth['read'] + $options['extraParams'];
    return map_helper::$ctrl($options);
  }
  
  /**
   * Adds a Drupal breadcrumb to the page.
   * The $options array can contain the following parameters:
   * * path - an associative array of paths and captions. The paths can contain replacements
   *   wrapped in # characters which will be replaced by the $_GET parameter of the same name.
   * * includeCurrentPage - set to false to disable addition of the current page title to the end
   *   of the breadcrumb.
   */
  public static function breadcrumb($auth, $args, $tabalias, $options, $path) {
    if (!isset($options['path']))
      return 'Please set an array of entries in the @path option';
    $breadcrumb[] = l('Home', '<front>');
    foreach ($options['path'] as $path => $caption) {
      $parts = explode('?', $path, 2);
      $itemOptions = array();
      if (count($parts)>1) {
        foreach ($_GET as $key=>$value) {
          // GET parameters can be used as replacements.
          $parts[1] = str_replace("#$key#", $value, $parts[1]);
        }
        $query = array();
        parse_str($parts[1], $query);
        $itemOptions['query'] = $query;
      }
      $path = $parts[0];
      // handle links to # anchors
      $fragments = explode('#', $path, 2);
      if (count($fragments)>1) {
        $path=$fragments[0];
        $itemOptions['fragment'] = $fragments[1];
      }
      // don't use Drupal l function as a it messes with query params
      $caption = lang::get($caption);
      $breadcrumb[] = l($caption, $path, $itemOptions);
    }
    if (!isset($options['includeCurrentPage']) || $options['includeCurrentPage']!==false)
      $breadcrumb[] = drupal_get_title();
    drupal_set_breadcrumb($breadcrumb);
    return '';
  }
  
  /*
   * Simply add this extension to your form's form structure to make the page read only. Might need expanding to 
   * take into account different scenarios
   */
  public static function read_only_input_form($auth, $args, $tabalias, $options, $path) {
    data_entry_helper::$javascript .= "
    $('#entry_form').find('input, textarea, text, button').attr('readonly', true);
    $('#entry_form').find('select,:checkbox').attr('disabled', true);\n 
    $('.indicia-button').hide();\n"; 
  }

  /**
   * Sets the page title according to an option. The title can refer to the URL query
   * string parameters as tokens.
   * @param $auth
   * @param $args
   * @param $tabalias
   * @param $options
   * @param $path
   * @return string
   */
  public static function set_page_title($auth, $args, $tabalias, $options, $path) {
    if (!isset($options['title']))
      return 'Please set the template for the title in the @title parameter';
    foreach($_GET as $key => $value)
      $options['title'] = str_replace("#$key#", $value, $options['title']);
    hostsite_set_page_title($options['title']);
    return '';
  }

  /**
   * Helper method to enable jQuery tooltips.
   */
  public static function enable_tooltips() {
    drupal_add_library('system', 'ui.tooltip', true);
    data_entry_helper::$javascript .= "
$('form#entry_form').tooltip({
  open: function(event, ui) {
    $(ui.tooltip).siblings(\".ui-tooltip\").remove();
  }
});\n";
  }

  /**
   * Provides a way of linking a location ID passed in a URL parameter to a
   * sample being recorded. Outputs hidden inputs containing values derived from
   * the selected location.
   * @param $auth
   * @param $args
   * @param $tabalias
   * @param array $options Options array with the following possibilities:
   *   * param - name of the parameter passed in the URL which should contain a
   *     location ID.
   *   * info_template - template for info to display when a location is found
   *     and being linked to. Set to false to disable. Default is '<p>You are
   *     submitting a record at {name} ({centroid_sref}).</p>'
   *   * save_id_to_field - name of the field to save the location ID
   *     into. Defaults to 'sample:location_id'. Set to false to disable saving
   *     the location ID.
   *   * save_centroid_sref_to_field - name of the field to save the location's
   *     centroid_sref value to (the spatial reference of the centre of the
   *     location). Defaults to 'sample:entered_sref'. Set to false to disable
   *     saving the centroid spatial reference
   *   * save_centroid_sref_system_to_field - name of the field to save the
   *     location's centroid_sref_system value to (the system used for the
   *     centroid). Defaults to 'sample:entered_sref_system'. Set to false to
   *     disable saving the centroid spatial reference system.
   *   * save_boundary_geom_to_field - name of the field to save the location's
   *     boundary_geom value to. Set this to sample:geom to store the location
   *     boundary in the sample. Defaults to false.
   * @param $path
   */
  public static function location_from_url($auth, $args, $tabalias, $options, $path) {
    $options = array_merge(array(
      'param' => 'location_id',
      'info_template' => lang::get('<p>You are submitting a record at {name} ({centroid_sref})</p>'),
      'save_id_to_field' => 'sample:location_id',
      'save_centroid_sref_to_field' => 'sample:entered_sref',
      'save_centroid_sref_system_to_field' => 'sample:entered_sref_system',
      'save_boundary_geom_to_field' => false
    ), $options);
    if (empty($_GET[$options['param']]))
      return '';
    $locations = data_entry_helper::get_population_data(array(
      'table' => 'location',
      'extraParams' => $auth['read'] + array(
          'id' => $_GET[$options['param']],
          'view' => 'detail'
      )
    ));
    if (count($locations)===0)
      return lang::get('<p>Location not found</p>');
    $r = '';
    $location = $locations[0];
    if ($options['info_template'])
      $r .= str_replace(
        array('{name}', '{centroid_sref}'),
        array($location['name'], $location['centroid_sref']),
        $options['info_template']
      );
    if ($options['save_id_to_field'])
      $r .= data_entry_helper::hidden_text(array(
        'fieldname' => $options['save_id_to_field'],
        'default' => $location['id']
      ));
    if ($options['save_centroid_sref_to_field'])
      $r .= data_entry_helper::hidden_text(array(
        'fieldname' => $options['save_centroid_sref_to_field'],
        'default' => $location['centroid_sref']
      ));
    if ($options['save_centroid_sref_system_to_field'])
      $r .= data_entry_helper::hidden_text(array(
        'fieldname' => $options['save_centroid_sref_system_to_field'],
        'default' => $location['centroid_sref_system']
      ));
    if ($options['save_boundary_geom_to_field'])
      $r .= data_entry_helper::hidden_text(array(
        'fieldname' => $options['save_boundary_geom_to_field'],
        'default' => $location['boundary_geom']
      ));
    return $r;
  }

  /**
   * Adds code to the page for a popup box that shows the link to a group join page.
   *
   * Creates a JavaScript function that shows a FancyBox popup containing the link required to view or join a group.
   * This link can then be given out over social media etc. Having included this extension control on the page you will
   * need to call the JS indiciaFns.groupLinkPopup() function, for example by calling it from a report grid action, with
   * the following parameters:
   * * title of the group
   * * title of the parent of this group (or empty string if group has no parent)
   * * id of the group
   * * rootFolder of the site to which the page path can be appended.
   */
  public static function group_link_popup() {
    $r = '<div id="group-link-popup-container" style="display: none"><div id="group-link-popup">';
    $r .= '<p>' . lang::get('Send this link to other people to allow them to view or join this group.') . '</p>';
    $r .= '<textarea style="width: 100%;" id="share-link" rows="4"></textarea>';
    $r .= '</div></div>';
    return $r;
  }

  /**
   * An extension control that takes a scratchpad_list_id parameter in the URL and uses it to load the list onto a
   * species grid on the page. This allows a scratchpad to be used as the first step in data entry.
   * @param $auth
   * @param $args
   * @param $tabalias
   * @param $options Array of options. Set parameter to the name of the URL parameter for the scratchpad_list_id, if you
   * want to override the default.
   * @return string HTML to add to the page. Contains hidden inputs which set values required for functionality to work.
   */
  public static function load_species_list_from_scratchpad($auth, $args, $tabalias, $options) {
    $options = array_merge(
      array(
        'parameter' => 'scratchpad_list_id'
      ), $options
    );
    if (empty($_GET[$options['parameter']]))
      return ''; // no list to load
    $entries = data_entry_helper::get_population_data(array(
      'table' => 'scratchpad_list_entry',
      'extraParams' => $auth['read'] + array('scratchpad_list_id' => $_GET[$options['parameter']]),
      'caching' => false
    ));
    $r = '';
    if (count($entries)) {
      foreach ($entries as $idx => $entry) {
        if ($entry['entity'] === 'taxa_taxon_list') {
          data_entry_helper::$entity_to_load["sc:$idx::present"] = $entry['entry_id'];
        }
      }
      hostsite_show_message(lang::get('The list of species has been loaded into the form for you. ' .
        'Please fill in the other form values before saving the form.'));
      $r = data_entry_helper::hidden_text(array(
        'fieldname' => 'scratchpad_list_id',
        'default' => $_GET[$options['parameter']]
      ));
      $r .= data_entry_helper::hidden_text(array(
        'fieldname' => 'submission_extensions[]',
        'default' => 'misc_extensions.remove_scratchpad'
      ));
    }
    return $r;
  }

  /**
   * An extension for the submission building code that adds a deletion request for a scratchpad that has now been
   * converted into a list of records. Automatically called when the load_species_list_from_scratchpad control is
   * used.
   */
  public static function remove_scratchpad($values, $s_array) {
    // Convert to a list submission so we can send a scratchpad list deletion as well as the main submission.
    $s_array[0] = array(
      'id' => 'sample',
      'submission_list' => array(
        'entries' => array(
          array(
            'id' => 'scratchpad_list',
            'fields' => array(
              'id' => array('value' => $values['scratchpad_list_id']),
              'deleted' => array('value' => 't')
            )
          ),
          $s_array[0]
        )
      )
    );
  }

  /* Adds a drop down box to the page which lists areas on the maps (e.g. a list
   * of countries). When you choose an area in the drop down, the map on the
   * page can automatically pan and zoom to that area and the spatial reference
   * system control, if present, can automatically pick the best system for the
   * chosen map area.
   * @param $auth
   * @param $args
   * @param $tabalias
   * @param $options Array of control options. Pass an array of area names to
   * include in the drop down list in the @areas option. Optionally override
   * the @mapDataFile option to specify a different file defining the map areas.
   * If you use this option, copy the file mapAreaData.js from the extensions
   * folder to files/indicia/js rename and edit it there. Other options
   * available are the same as for data_entry_helper::select controls, e.g. use
   * the @label option to define the control's label.
   * @return string
   */
  public static function area_picker($auth, $args, $tabalias, $options) {
    if (empty($options['areas']) || !is_array($options['areas']))
      return 'Please specify the list of areas for the area_picker control.';
    if (isset($options['mapDataFile'])) {
      $filepath = PrivateStream::basePath();
      if (!$filepath) {
        $filepath = PublicStream::basePath();
      }
      $path = "$filepath/indicia/js/";
    } else
      $path = data_entry_helper::getRootFolder() . data_entry_helper::client_helper_path()
      . 'prebuilt_forms/extensions/';
    $options = array_merge($options, array(
      'label' => 'Area',
      'mapDataFile' => 'mapAreaData.js',
      'id' => 'area-picker',
      'lookupValues' => array_merge(array(
        '' => lang::get('<select area>')
      ), array_combine($options['areas'], $options['areas']))
    ));
    // load the data file.
    data_entry_helper::$javascript .= "$.getScript('$path$options[mapDataFile]');\n";
    return data_entry_helper::select($options);
  }

  /**
   * An extension that simply takes an @text option and passes it through lang::get. Allows free text embedded in forms
   * to be localised.
   * @param $auth
   * @param $args
   * @param $tabalias
   * @param $options
   * @return string Translated text.
   */
  public static function localised_text($auth, $args, $tabalias, $options) {
    $options = array_merge(
      array('text' => 'The misc_extensions.localised_text control needs a @text parameter'),
      $options
    );
    return lang::get($options['text']);
  }
}