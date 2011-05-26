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
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */
 
require_once('helper_config.php');

/** 
 * Base class for the report and data entry helpers. Provides several generally useful methods and also includes 
 * resource management.
 */
class helper_base extends helper_config {

  /**
   * @var array Website ID, stored here to assist with caching.
   */
  protected static $website_id = null;

  /**
   * @var Array List of resources that have been identified as required by the controls used. This defines the
   * JavaScript and stylesheets that must be added to the page. Each entry is an array containing stylesheets and javascript
   * sub-arrays. This has public access so the Drupal module can perform Drupal specific resource output.
   */
  public static $required_resources=array();
  
  /**
   * @var Array List of all available resources known. Each resource is named, and contains a sub array of
   * deps (dependencies), stylesheets and javascripts.
   */
  public static $resource_list=null;
  
  /**
   * @var string Path to Indicia JavaScript folder. If not specified, then it is calculated from the Warehouse $base_url.
   * This path should be a full path on the server (starting with '/' exluding the domain).
   */
  public static $js_path = null;

  /**
   * @var string Path to Indicia CSS folder. If not specified, then it is calculated from the Warehouse $base_url.
   * This path should be a full path on the server (starting with '/' exluding the domain).
   */
  public static $css_path = null;

  /**
   * @var array List of resources that have already been dumped out, so we don't duplicate them. For example, if the 
   * site template includes JQuery set $dumped_resources[]='jquery'.
   */
  public static $dumped_resources=array();
  
  /**
   * @var string JavaScript text to be emitted after the data entry form. Each control that
   * needs custom JavaScript can append the script to this variable.
   */
  public static $javascript = '';

  /**
   * @var string JavaScript text to be emitted after the data entry form and all other JavaScript.
   */
  public static $late_javascript = '';

  /**
   * @var string JavaScript text to be emitted during window.onload.
   */
  public static $onload_javascript = '';
  
  /**
   * List of methods used to report a validation failure. Options are message, message, hint, icon, colour, inline.
   * The inline option specifies that the message should appear on the same line as the control.
   * Otherwise it goes on the next line, indented by the label width. Because in many cases, controls
   * on an Indicia form occupy the full available width, it is often more appropriate to place error
   * messages on the next line so this is the default behaviour.
   * @var array
   */
  public static $validation_mode=array('message', 'colour');
  
  /**
   * @var array Name of the form which has been set up for jQuery validation, if any.
   */
  public static $validated_form_id = null;

  /**
   * @var string Helptext positioning. Determines where the information is displayed when helpText is defined for a control.
   * Options are before, after.
   */
  public static $helpTextPos='after';

  /**
   * @var array List of all error messages returned from an attempt to save.
   */
  public static $validation_errors=null;
  
  /**
   * @var Array of default validation rules to apply to the controls on the form if the
   * built in client side validation is used (with the jQuery validation plugin). This array
   * can be replaced if required.
   * @todo This array could be auto-populated with validation rules for a survey's fields from the
   * Warehouse.
   */
  public static $default_validation_rules = array(
    'sample:date'=>array('required','date'),
    'sample:entered_sref'=>array('required'),
    'occurrence:taxa_taxon_list_id'=>array('required')
  );
  
  /**
   * @var array List of messages defined to pass to the validation plugin.
   */
  public static $validation_messages = array();
  
  /**
   * @var Boolean Are we linking in the default stylesheet? Handled sligtly different to the others so it can be added to the end of the
   * list, allowing our CSS to override other stuff.
   */
  protected static $default_styles = false;
  
  /**
   * Array of html attributes. When replacing items in a template, these get automatically wrapped. E.g.
   * a template replacement for the class will be converted to class="value". The key is the parameter name,
   * and the value is the html attribute it will be wrapped into.
   */
  protected static $html_attributes = array(
    'class' => 'class',
    'outerClass' => 'class',
    'selected' => 'selected'
  );
  
  /**
   * @var array List of error messages that have been displayed, so we don't duplicate them when dumping any
   * remaining ones at the end.
   */
  protected static $displayed_errors=array();

  /**
   * Method to link up the external css or js files associated with a set of code.
   * This is normally called internally by the control methods to ensure the required files are linked into the page so
   * does not need to be called directly. However it can be useful when writing custom code that uses one of these standard
   * libraries such as jQuery. Ensures each file is only linked once. 
   *
   * @param string $resource Name of resource to link. The following options are available:
   * <ul>
   * <li>jquery</li>
   * <li>openlayers</li>
   * <li>addrowtogrid</li>
   * <li>indiciaMap</li>
   * <li>indiciaMapPanel</li>
   * <li>indiciaMapEdit</li>
   * <li>locationFinder</li>
   * <li>autocomplete</li>
   * <li>jquery_ui</li>
   * <li>json</li>
   * <li>treeview</li>
   * <li>googlemaps</li>
   * <li>multimap</li>
   * <li>virtualearth</li>
   * <li>google_search</li>
   * <li>flickr</li>
   * <li>defaultStylesheet</li>
   * </ul>
   */
  public static function add_resource($resource)
  {
    // If this is an available resource and we have not already included it, then add it to the list
    if (array_key_exists($resource, self::get_resources()) && !in_array($resource, self::$required_resources)) {
      $resourceList = self::get_resources();
      if (isset($resourceList[$resource]['deps'])) {
        foreach ($resourceList[$resource]['deps'] as $dep) {
          self::add_resource($dep);
        }
      }
      self::$required_resources[] = $resource;
    }
  }
  
  /**
   * List of external resources including stylesheets and js files used by the data entry helper class.
   */
  public static function get_resources()
  {
    if (self::$resource_list===null) {
      $base = parent::$base_url;
      if (!self::$js_path) {
        self::$js_path =$base.'media/js/';
      } else if (substr(self::$js_path,-1)!="/") {
        // ensure a trailing slash
        self::$js_path .= "/";
      }
      if (!self::$css_path) {
        self::$css_path =$base.'media/css/';
      } else if (substr(self::$css_path,-1)!="/") {
        // ensure a trailing slash
        self::$css_path .= "/";
      }
      global $indicia_theme, $indicia_theme_path;
      if (!isset($indicia_theme)) {
        // Use default theme if page does not specify it's own.
        $indicia_theme="default";
      }
      if (!isset($indicia_theme_path)) {
        // Use default theme path if page does not specify it's own.
        $indicia_theme_path=preg_replace('/css\/$/','themes/', self::$css_path);
      }
      // ensure a trailing path
      if (substr($indicia_theme_path, -1)!=='/')
        $indicia_theme_path .= '/';
      self::$resource_list = array (
        'jquery' => array('javascript' => array(self::$js_path."jquery.js",self::$js_path."ie_vml_sizzlepatch_2.js")),
        'openlayers' => array('javascript' => array(self::$js_path."OpenLayers.js", self::$js_path."proj4js.js", self::$js_path."proj4defs.js")),
        'addrowtogrid' => array('javascript' => array(self::$js_path."addRowToGrid.js")),
        'indiciaMapPanel' => array('deps' =>array('jquery', 'openlayers', 'jquery_ui'), 'javascript' => array(self::$js_path."jquery.indiciaMapPanel.js")),
        'indiciaMapEdit' => array('deps' =>array('indiciaMap'), 'javascript' => array(self::$js_path."jquery.indiciaMap.edit.js")),
        'georeference_google_search_api' => array('javascript' => array("http://www.google.com/jsapi?key=".parent::$google_search_api_key)),
        'locationFinder' => array('deps' =>array('indiciaMapEdit'), 'javascript' => array(self::$js_path."jquery.indiciaMap.edit.locationFinder.js")),
        'autocomplete' => array('deps' => array('jquery'), 'stylesheets' => array(self::$css_path."jquery.autocomplete.css"), 'javascript' => array(self::$js_path."jquery.autocomplete.js")),
        'jquery_ui' => array('deps' => array('jquery'), 'stylesheets' => array("$indicia_theme_path$indicia_theme/jquery-ui.custom.css"), 'javascript' => array(self::$js_path."jquery-ui.custom.min.js", self::$js_path."jquery-ui.effects.js")),
        'jquery_ui_fr' => array('deps' => array('jquery_ui'), 'javascript' => array(self::$js_path."jquery.ui.datepicker-fr.js")),
        'json' => array('javascript' => array(self::$js_path."json2.js")),
        'treeview' => array('deps' => array('jquery'), 'stylesheets' => array(self::$css_path."jquery.treeview.css"), 'javascript' => array(self::$js_path."jquery.treeview.js", self::$js_path."jquery.treeview.async.js",
        self::$js_path."jquery.treeview.edit.js")),
        'googlemaps' => array('javascript' => array("http://maps.google.com/maps?file=api&amp;v=2&amp;sensor=false&amp;key=".parent::$google_api_key)),
        'multimap' => array('javascript' => array("http://developer.multimap.com/API/maps/1.2/".parent::$multimap_api_key)),
        'virtualearth' => array('javascript' => array('http://dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6.1')),
        'google_search' => array('stylesheets' => array(),
            'javascript' => array(
              "http://www.google.com/jsapi?key=".parent::$google_search_api_key,
              self::$js_path."google_search.js"
            )
        ),
        'fancybox' => array('deps' => array('jquery'), 'stylesheets' => array(self::$js_path.'fancybox/jquery.fancybox.css'), 'javascript' => array(self::$js_path.'fancybox/jquery.fancybox.pack.js')),
        'flickr' => array('deps' => array('fancybox'), 'javascript' => array(self::$js_path."jquery.flickr.js")),
        'treeBrowser' => array('deps' => array('jquery','jquery_ui'), 'javascript' => array(self::$js_path."jquery.treebrowser.js")),
        'defaultStylesheet' => array('deps' => array(''), 'stylesheets' => array(self::$css_path."default_site.css"), 'javascript' => array()),
        'validation' => array('deps' => array('jquery'), 'javascript' => array(self::$js_path.'jquery.validate.js')),
        'plupload' => array('deps' => array('jquery_ui','fancybox'), 'javascript' => array(
            self::$js_path.'jquery.uploader.js', self::$js_path.'/plupload/js/plupload.full.min.js')),
        'jqplot' => array('stylesheets' => array(self::$js_path.'jqplot/jquery.jqplot.css'), 'javascript' => array(self::$js_path.'jqplot/jquery.jqplot.min.js','[IE]'.self::$js_path.'jqplot/excanvas.min.js')),
        'jqplot_bar' => array('javascript' => array(self::$js_path.'jqplot/plugins/jqplot.barRenderer.min.js')),
        'jqplot_pie' => array('javascript' => array(self::$js_path.'jqplot/plugins/jqplot.pieRenderer.min.js')),
        'jqplot_category_axis_renderer' => array('javascript' => array(self::$js_path.'jqplot/plugins/jqplot.categoryAxisRenderer.min.js')),
        'reportgrid' => array('deps' => array('jquery_ui'), 'javascript' => array(self::$js_path.'jquery.reportgrid.js')),
        'tabs' => array('deps' => array('jquery_ui'), 'javascript' => array(self::$js_path.'tabs.js')),
        'wizardprogress' => array('deps' => array('tabs'), 'stylesheets' => array(self::$css_path."wizard_progress.css")),
      );
    }
    return self::$resource_list;
  }
  
  /**
   * Returns a span containing any validation errors active on the form for the
   * control with the supplied ID.
   *
   * @param string $fieldname Fieldname of the control to retrieve errors for.
   * @param boolean $plaintext Set to true to return just the error text, otherwise it is wrapped in a span.
   */
  public static function check_errors($fieldname, $plaintext=false)
  {
    $error='';
    if (self::$validation_errors!==null) {
       if (array_key_exists($fieldname, self::$validation_errors)) {
         $errorKey = $fieldname;
       } elseif (substr($fieldname, -4)=='date') {
          // For date fields, we also include the type, start and end validation problems
          if (array_key_exists($fieldname.'_start', self::$validation_errors)) {
            $errorKey = $fieldname.'_start';
          }
          if (array_key_exists($fieldname.'_end', self::$validation_errors)) {
            $errorKey = $fieldname.'_end';
          }
          if (array_key_exists($fieldname.'_type', self::$validation_errors)) {
            $errorKey = $fieldname.'_type';
          }
       }
       if (isset($errorKey)) {
         $error = self::$validation_errors[$errorKey];
         // Track errors that were displayed, so we can tell the user about any others.
         self::$displayed_errors[] = $error;
       }
    }
    if ($error!='') {
      if ($plaintext) {
        return $error;
      } else {
        return self::apply_error_template($error, $fieldname);
      }
    } else {
      return '';
    }
  }
  
  /**
   * Sends a POST using the cUrl library
   */
  public static function http_post($url, $postargs, $output_errors=true) {
    $session = curl_init();
    // Set the POST options.
    curl_setopt ($session, CURLOPT_URL, $url);
    if ($postargs!==null) {
      curl_setopt ($session, CURLOPT_POST, true);
      curl_setopt ($session, CURLOPT_POSTFIELDS, $postargs);
    }
    curl_setopt($session, CURLOPT_HEADER, true);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    // Do the POST and then close the session
    $response = curl_exec($session);
    // Check for an error, or check if the http response was not OK. Note that the cUrl emulator only returns connection: close.
    if (curl_errno($session) || (strpos($response, 'HTTP/1.1 200 OK')===false && strpos($response, 'Connection: close')===false)) {
      if ($output_errors) {
        echo '<div class="error">cUrl POST request failed. Please check cUrl is installed on the server and the $base_url setting is correct.<br/>';
        if (curl_errno($session)) {
          echo 'Error number: '.curl_errno($session).'<br/>';
          echo 'Error message: '.curl_error($session).'<br/>';
        }
        echo "Server response<br/>";
        echo $response.'</div>';
      }
      $return = array(
          'result'=>false,
          'output'=> curl_errno($session) ? curl_error($session) : $response,
          'errno'=>curl_errno($session));
    } else {
      $arr_response = explode("\r\n\r\n",$response);
      // last part of response is the actual data
      $return = array('result'=>true,'output'=>array_pop($arr_response));
    }
    curl_close($session);
    return $return;
  }
  
  /**
   * Calculates the folder that submitted images end up in according to the helper_config.
   */
  public static function get_uploaded_image_folder() {
    if (!isset(self::$final_image_folder) || self::$final_image_folder=='warehouse')
      return self::$base_url.(isset(self::$indicia_upload_path) ? self::$indicia_upload_path : 'upload/');
    else {
      return dirname($_SERVER['PHP_SELF']) . '/' . self::relative_client_helper_path() . self::$final_image_folder;
    }      
  }
  
  /**
   * Calculates the relative path to the client_helpers folder from wherever the current PHP script is.
   */
  public static function relative_client_helper_path() {
    // get the paths to the client helper folder and php file folder as an array of tokens
    $clientHelperFolder = explode(DIRECTORY_SEPARATOR, realpath(dirname(__FILE__)));
    $currentPhpFileFolder = explode(DIRECTORY_SEPARATOR, realpath(dirname($_SERVER['SCRIPT_FILENAME'])));
    // Find the first part of the paths that is not the same
    for($i = 0; $i<min(count($currentPhpFileFolder), count($clientHelperFolder)); $i++) {
      if ($clientHelperFolder[$i] != $currentPhpFileFolder[$i]) {
        break;
      }
    }
    // step back up the path to the point where the 2 paths differ
    $path = str_repeat('../', count($currentPhpFileFolder)-$i);
    // add back in the different part of the path to the client helper folder
    for ($j = $i; $j < count($clientHelperFolder); $j++) {
      $path .= $clientHelperFolder[$j] . '/';
    }
    return $path;
  }
  
  /**
   * Parameters forms are a quick way of specifying a simple form used to specify the input
   * parameters for a process. Returns the HTML required for a parameters form, e.g. the form
   * defined for input of report parameters or the default values for a csv import.
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>form</b><br/>
   * Associative array defining the form structure. The structure is the same as described for <em>fixed_values_form</em> in a Warehouse model.
   * @link http://code.google.com/p/indicia/wiki/SampleModelPage.
   * </li>
   * <li><b>id</b><br/>
   * When used for report output, id of the report instance on the page if relevant, so that controls can be given unique ids.
   * </li>
   * <li><b>form</b><br/>
   * Associative array defining the form content.
   * </li>
   * <li><b>readAuth</b><br/>
   * Read authorisation array.
   * </li>
   * <li><b>field_name_prefix</b><br/>
   * Optional prefix for form field names.
   * </li>
   * <li><b>defaults</b><br/>
   * Associative array of default values for each form element.
   * </li>
   * <li><b>ignoreParams</b><br/>
   * An optional array of parameter names for parameters that should be skipped in the form output despite being in the form definition.
   * </li>
   * <li><b>presetParams</b><br/>
   * Optional array of param names and values that have a fixed value and are therefore output only as a hidden control.
   * </li>
   * </ul>
   */
  public static function build_params_form($options) {
    require_once('data_entry_helper.php');
    $r = '';
    $fieldPrefix = (isset($options['field_name_prefix']) ? $options['field_name_prefix'].'-' : '') . (isset($options['id']) ? $options['id'].'-' : '');
    foreach($options['form'] as $key=>$info) {
      unset($tools);
      // Skip parameters if we have been asked to ignore them
      if (isset($options['ignoreParams']) && in_array($key, $options['ignoreParams'])) continue;
      $ctrlOptions = array(
        'label' => $info['display'],
        'helpText' => $info['description'],
        'fieldname' => $fieldPrefix.$key
      );
      // If this parameter is in the URL or post data, put it in the control instead of the original default
      if (isset($options['defaults'][$key]))
        $ctrlOptions['default'] = $options['defaults'][$key];
      elseif (isset($info['default'])) 
        $ctrlOptions['default'] = $info['default'];
      if (isset($options['presetParams']) && array_key_exists($key, $options['presetParams'])) {
        $r .= "<input type=\"hidden\" name=\"$key\" value=\"".$options['presetParams'][$key]."\" />\n";
      } elseif ($info['datatype']=='lookup' && isset($info['population_call'])) {
        // population call is colon separated, of the form direct|report:table|view|report:idField:captionField
        $popOpts = explode(':', $info['population_call']);
        $ctrlOptions = array_merge($ctrlOptions, array(
          'valueField'=>$popOpts[2],
          'captionField'=>$popOpts[3],
          'blankText'=>'<'.lang::get('please select').'>',
          'extraParams'=>$options['readAuth']
        ));
        if ($popOpts[0]=='direct') 
          $ctrlOptions['table']=$popOpts[1];
        else
          $ctrlOptions['report']=$popOpts[1];
        if (isset($info['linked_to']) && isset($info['linked_filter_field'])) {
          if (isset($options['presetParams']) && array_key_exists($info['linked_to'], $options['presetParams'])) {
            // if the control this is linked to is hidden because it has a preset value, just use that value as a filter on the 
            // population call for this control
            $ctrlOptions = array_merge($ctrlOptions, array(
              'extraParams' => array_merge($ctrlOptions['extraParams'], array($info['linked_filter_field']=>$options['presetParams'][$info['linked_to']]))
            ));
          } else {
            // otherwise link the 2 controls
            $ctrlOptions = array_merge($ctrlOptions, array(
              'parentControlId' => $fieldPrefix.$info['linked_to'],
              'filterField' => $info['linked_filter_field'],
              'parentControlLabel' => $options['form'][$info['linked_to']]['display']
            ));
          }
        }
        $r .= data_entry_helper::select($ctrlOptions);
      } elseif ($info['datatype']=='lookup' && isset($info['lookup_values'])) {
        // Convert the lookup values into an associative array
        $lookups = explode(',', $info['lookup_values']);
        $lookupsAssoc = array();
        foreach($lookups as $lookup) {
          $lookup = explode(':', $lookup);
          $lookupsAssoc[$lookup[0]] = $lookup[1];
        }
        $ctrlOptions = array_merge($ctrlOptions, array(
          'blankText'=>'<'.lang::get('please select').'>',
          'lookupValues' => $lookupsAssoc
        ));        
        $r .= data_entry_helper::select($ctrlOptions);
      } elseif ($info['datatype']=='date') {
        $r .= data_entry_helper::date_picker($ctrlOptions);
      } elseif ($info['datatype']=='geometry') {
        $tools = array('Polygon','Line','Point');
      } elseif ($info['datatype']=='polygon') {
        $tools = array('Polygon');
      } elseif ($info['datatype']=='line') {
        $tools = array('Line');
      } elseif ($info['datatype']=='point') {
        $tools = array('Point');
      } else {
        $r .= data_entry_helper::text_input($ctrlOptions);
      }
    }
    // If the form has defined any tools to add to the map, we need to create JavaScript to add them to the map.
    if (isset($tools)) {
      $r .= '<label>'.$ctrlOptions['label'].':</label>';
      $r .= '<div class="control-box">Use the following tools to define the query area.<br/>'.
      '<div id="map-tools" class="olControlEditingToolbar left"></div></div><br/>';
      $r .= '<input type="hidden" name="'.$ctrlOptions['fieldname'].'" class="hidden-wkt" />';
      // Output some JavaScript to setup a toolbar for the map drawing tools. Also JS
      // to handle getting the polygons from the edit layer into the report parameter
      // when run report is clicked.
      data_entry_helper::$javascript .= "
$.fn.indiciaMapPanel.defaults.toolbarDiv='map-tools';
mapInitialisationHooks.push(function(div) {
  // keep a global reference to the map, so we can get it later when Run Report is clicked
  mapDiv = div;
});
$('#run-report').click(function(evt) {
  var geoms=[], featureClass='', geom;
  if (mapDiv.map.editLayer.features.length===0) {
    evt.preventDefault();
    alert('Please supply a search area for the report.');
  }
  $.each(mapDiv.map.editLayer.features, function(i, feature) {
    if (i===0) {
      // grab the first feature's type
      featureClass = feature.geometry.CLASS_NAME;
    }
    if (featureClass == feature.geometry.CLASS_NAME) {
      // for subsequent features, ignore them unless the same type as the first
      geoms.push(feature.geometry);
    }
  });
  if (featureClass === 'OpenLayers.Geometry.Polygon') {
    geom = new OpenLayers.Geometry.MultiPolygon(geoms);
  } else if (featureClass === 'OpenLayers.Geometry.LineString') {
    geom = new OpenLayers.Geometry.MultiLineString(geoms);
  } else if (featureClass === 'OpenLayers.Geometry.Point') {
    geom = new OpenLayers.Geometry.MultiPoint(geoms);
  }
  $('.hidden-wkt').val(geom.toString());
});
var add_map_tools = function(opts) {\n";
      foreach ($tools as $tool) {
        data_entry_helper::$javascript .= "opts.standardControls.push('draw$tool');\n";
      }
      data_entry_helper::$javascript .= "
}
mapSettingsHooks.push(add_map_tools)
";
    }
    return $r;
  }
  
  /**
   * Utility method that returns the parts required to build a link back to the current page.
   * @return array Associative array containing path and params (itself a key/value paired associative array).
   */
  public static function get_reload_link_parts() {
    $split = strpos($_SERVER['REQUEST_URI'], '?');
    // convert the query parameters into an array
    $gets = ($split!==false && strlen($_SERVER['REQUEST_URI']) > $split+1) ? 
        explode('&', substr($_SERVER['REQUEST_URI'], $split+1)) : 
        array();
    $getsAssoc = array();
    foreach ($gets as $get) {
      list($key, $value) = explode('=', $get);
      $getsAssoc[$key] = $value;
    }
    $path = $split!==false ? substr($_SERVER['REQUEST_URI'], 0, $split) : $_SERVER['REQUEST_URI'];
    return array(
      'path'=>$path,
      'params' => $getsAssoc
    );
  }

  /**
   * Takes an associative array and converts it to a list of params for a query string. This is like
   * http_build_query but it does not url encode the & separator, and gives control over urlencoding the array values.
   */
  protected static function array_to_query_string($array, $encodeValues=false) {
    $params = array();
    if(is_array($array)) {
      arsort($array);
      foreach ($array as $a => $b)
      {
        if ($encodeValues) $b=urlencode($b);
        $params[] = "$a=$b";
      }
    }
    return implode('&', $params);
  }
  
    /**
   * Applies a output template to an array. This is used to build the output for each item in a list,
   * such as a species checklist grid or a radio group.
   *
   * @param array $item Array holding the item attributes.
   * @param string $template Name of the template to use, or actual template text if
   * $useTemplateAsIs is set to true.
   * @param boolean $useTemplateAsIs If true then the template parameter contains the actual
   * template text, otherwise it is the name of a template in the $indicia_templates array. Default false.
   * @param boolean $allowHtml If true then HTML is emitted as is from the parameter values inserted into the template,
   * otherwise they are escaped.
   * @return string HTML for the item label
   */
  public static function mergeParamsIntoTemplate($params, $template, $useTemplateAsIs=false, $allowHtml=false) {
    global $indicia_templates;
    // Build an array of all the possible tags we could replace in the template.
    $replaceTags=array();
    $replaceValues=array();
    foreach ($params as $param=>$value) {
      if (!is_array($value) && !is_object($value)) {
        array_push($replaceTags, '{'.$param.'}');
        // allow sep to have <br/>
        $value = ($param == 'sep' || $allowHtml) ? $value : htmlSpecialChars($value);
        // HTML attributes get automatically wrapped
        if (in_array($param, self::$html_attributes) && !empty($value))
          $value = " $param=\"$value\"";
        array_push($replaceValues, $value);
      }
    }
    if (!$useTemplateAsIs) $template = $indicia_templates[$template];
    return str_replace($replaceTags, $replaceValues, $template);
  }

  /**
   * Takes a file that has been uploaded to the client website upload folder, and moves it to the warehouse upload folder using the
   * data services.
   *
   * @param string $path Path to the file to upload, relative to the interim image path folder (normally the
   * client_helpers/upload folder.
   * @param boolean $persist_auth Allows the write nonce to be preserved after sending the file, useful when several files
   * are being uploaded.
   * @param array readAuth Read authorisation tokens, if not supplied then the $_POST array should contain them.
   * @param string $service Path to the service URL used. Default is data/handle_media, but could be import/upload_csv.
   * @return string Error message, or true if successful.
   */
  protected static function send_file_to_warehouse($path, $persist_auth=false, $readAuth = null, $service='data/handle_media') {
    if ($readAuth==null) $readAuth=$_POST;
    $interim_image_folder = isset(parent::$interim_image_folder) ? parent::$interim_image_folder : 'upload/';
    $uploadpath = self::relative_client_helper_path() . $interim_image_folder;
    if (!file_exists($uploadpath.$path)) 
      return "The file $uploadpath$path does not exist and cannot be uploaded to the Warehouse.";
    $serviceUrl = parent::$base_url."index.php/services/".$service;
    // This is used by the file box control which renames uploaded files using a guid system, so disable renaming on the server.
    $postargs = array('name_is_guid' => 'true');
    // attach authentication details
    if (array_key_exists('auth_token', $readAuth))
      $postargs['auth_token'] = $readAuth['auth_token'];
    if (array_key_exists('nonce', $readAuth))
      $postargs['nonce'] = $readAuth['nonce'];
    if ($persist_auth)
      $postargs['persist_auth'] = 'true';
    $file_to_upload = array('media_upload'=>'@'.realpath($uploadpath.$path));    
    $response = self::http_post($serviceUrl, $file_to_upload + $postargs);    
    $output = json_decode($response['output'], true);
    $r = true; // default is success
    if (is_array($output)) {
      //an array signals an error
      if (array_key_exists('error', $output)) {
        // return the most detailed bit of error information
        if (isset($output['errors']['media_upload']))
          $r = $output['errors']['media_upload'];
        else
          $r = $output['error'];
      }
    }
    //remove local copy
    unlink(realpath($uploadpath.$path));
    return $r;
  }

 /**
  * Internal function to find the path to the root of the site, including the trailing slash.
  */  
  protected static function getRootFolder() {
    $rootFolder = dirname($_SERVER['PHP_SELF']);
    if ($rootFolder =='\\') $rootFolder = '/'; // if no directory, then on windows may just return a single backslash.
    if (substr($rootFolder, -1)!='/') $rootFolder .= '/';
    return $rootFolder;
  }
  
  /**
  * Retrieves a token and inserts it into a data entry form which authenticates that the
  * form was submitted by this website.
  *
  * @param string $website_id Indicia ID for the website.
  * @param string $password Indicia password for the website.
  */
  public static function get_auth($website_id, $password) {
    $postargs = "website_id=$website_id";
    $response = self::http_post(parent::$base_url.'index.php/services/security/get_nonce', $postargs);
    $nonce = $response['output'];
    $result = '<input id="auth_token" name="auth_token" type="hidden" class="hidden" ' .
        'value="'.sha1("$nonce:$password").'" />'."\r\n";
    $result .= '<input id="nonce" name="nonce" type="hidden" class="hidden" ' .
        'value="'.$nonce.'" />'."\r\n";
    return $result;
  }

  /**
  * Retrieves a read token and passes it back as an array suitable to drop into the
  * 'extraParams' options for an Ajax call.
  *
  * @param string $website_id Indicia ID for the website.
  * @param string $password Indicia password for the website.
  */
  public static function get_read_auth($website_id, $password) {
    self::$website_id = $website_id; /* Store this for use with data caching */
    $postargs = "website_id=$website_id";
    $response = self::http_post(parent::$base_url.'index.php/services/security/get_read_nonce', $postargs);
    $nonce = $response['output'];
    return array(
        'auth_token' => sha1("$nonce:$password"),
        'nonce' => $nonce
    );
  }

/**
  * Retrieves read and write nonce tokens from the warehouse.
  * @param string $website_id Indicia ID for the website.
  * @param string $password Indicia password for the website.
  * @return Returns an array containing:
  * 'read' => the read authorisation array,
  * 'write' => the write authorisation input controls to insert into your form.
  * 'writeTokens' => the write authorisation array, if needed as separate tokens rather than just placing in form.
  */
  public static function get_read_write_auth($website_id, $password) {
    self::$website_id = $website_id; /* Store this for use with data caching */
    $postargs = "website_id=$website_id";
    $response = self::http_post(parent::$base_url.'index.php/services/security/get_read_write_nonces', $postargs);
    $nonces = json_decode($response['output'], true);
    $write = '<input id="auth_token" name="auth_token" type="hidden" class="hidden" ' .
        'value="'.sha1($nonces['write'].':'.$password).'" />'."\r\n";
    $write .= '<input id="nonce" name="nonce" type="hidden" class="hidden" ' .
        'value="'.$nonces['write'].'" />'."\r\n";
    return array(
      'write' => $write,
      'read' => array(
        'auth_token' => sha1($nonces['read'].':'.$password),
        'nonce' => $nonces['read']
      ),
      'write_tokens' => array(
        'auth_token' => sha1($nonces['write'].':'.$password),
        'nonce' => $nonces['write']
      ),
    );
  }
  
  /**
   * This method allows JavaScript and CSS links to be created and placed in the <head> of the
   * HTML file rather than using dump_javascript which must be called after the form is built.
   * The advantage of dump_javascript is that it intelligently builds the required links
   * depending on what is on your form. dump_header is not intelligent because the form is not
   * built yet, but placing links in the header leads to cleaner code which validates better.
   * @param $resources List of resources to include in the header. The available options are described
   * in the documentation for the add_resource method. The default for this is jquery_ui and defaultStylesheet.
   *
   * @return string Text to place in the head section of the html file.
   */
  public static function dump_header($resources=null) {
    if (!$resources) {
      $resources = array('jquery_ui',  'defaultStylesheet');
    }
    foreach ($resources as $resource) {
      self::add_resource($resource);
    }
    // place a css class on the body if JavaScript enabled. And output the resources
    return self::internal_dump_javascript('$("body").addClass("js");', '', '', self::$required_resources);
  }
  
  /**
  * Helper function to collect javascript code in a single location. Should be called at the end of each HTML
  * page which uses the data entry helper so output all JavaScript required by previous calls.
  *
  * @return string JavaScript to insert into the page for all the controls added to the page so far.
  *
  * @link http://code.google.com/p/indicia/wiki/TutorialBuildingBasicPage#Build_a_data_entry_page
  */
  public static function dump_javascript() {
    // Add the default stylesheet to the end of the list, so it has highest CSS priority
    if (self::$default_styles) self::add_resource('defaultStylesheet');
    // Jquery validation js has to be added at this late stage, because only then do we know all the messages required.
    self::setup_jquery_validation_js();
    $dump = self::internal_dump_javascript(self::$javascript, self::$late_javascript, self::$onload_javascript, self::$required_resources);
    // ensure scripted JS does not output again if recalled.
    self::$javascript = "";
    self::$late_javascript = "";
    self::$onload_javascript = "";
    return $dump;
  }
  
  /**
   * Internal implementation of the dump_javascript method which takes the javascript and resources list
   * as flexible parameters, rather that using the globals.
   * @access private
   */
  protected static function internal_dump_javascript($javascript, $late_javascript, $onload_javascript, $resources) {
    $libraries = '';
    $stylesheets = '';
    if (isset($resources)) {
      $resourceList = self::get_resources();
      foreach ($resources as $resource)
      {
        if (!in_array($resource, self::$dumped_resources)) {
          if (isset($resourceList[$resource]['stylesheets'])) {
            foreach ($resourceList[$resource]['stylesheets'] as $s) {
              $stylesheets .= "<link rel='stylesheet' type='text/css' href='$s' />\n";
            }
          }
          if (isset($resourceList[$resource]['javascript'])) {
            foreach ($resourceList[$resource]['javascript'] as $j) {
              // look out for a condition that this script is IE only.
              if (substr($j, 0, 4)=='[IE]')
                $libraries .= "<!--[if IE]><script type=\"text/javascript\" src=\"".substr($j, 4)."\"></script><![endif]-->\n";
              else
                $libraries .= "<script type=\"text/javascript\" src=\"$j\"></script>\n";
            }
          }
          // Record the resource as being dumped, so we don't do it again.
          array_push(self::$dumped_resources, $resource);
        }
      }
    }
    if (!empty($javascript) || !empty($late_javascript) || !empty($onload_javascript)) {
      $script = "<script type='text/javascript'>/* <![CDATA[ */
jQuery(document).ready(function() {
$javascript
$late_javascript
});\n";
      if (!empty($onload_javascript)) {
        $script .= "window.onload = function() {
$onload_javascript
};\n";
      }
      $script .= "/* ]]> */</script>";
    } else {
      $script='';
    }
    return $stylesheets.$libraries.$script;
  }

  /**
   * If required, setup jQuery validation. This JavaScript must be added at the end of form preparation otherwise we would
   * not know all the control messages. It will normally be called by dump_javascript automatically, but is exposed here
   * as a public method since the iform Drupal module does not call dump_javascript, but is responsible for adding JavaScript
   * to the page via drupal_add_js.
   */
  public static function setup_jquery_validation_js() {
    // In the following block, we set the validation plugin's error class to our template.
    // We also define the error label to be wrapped in a <p> if it is on a newline.
    if (self::$validated_form_id) {
      global $indicia_templates;
      self::$javascript .= "$('#".self::$validated_form_id."').validate({
        errorClass: \"".$indicia_templates['error_class']."\",
        ". (in_array('inline', self::$validation_mode) ? "\n      " : "errorElement: 'p',\n      ").
        "highlight: function(element, errorClass) {
          $(element).addClass('ui-state-error');
        },
        unhighlight: function(element, errorClass) {
          $(element).removeClass('ui-state-error');
        },
        invalidHandler: function(form, validator) {
          var tabselected=false;
          jQuery.each(validator.errorMap, function(ctrlId, error) {
            // select the tab containing the first error control
            if (!tabselected && typeof(tabs)!=='undefined') {
              tabs.tabs('select',jQuery('[name=' + ctrlId.replace(/:/g, '\\\\:') + ']').filter('input,select').parents('.ui-tabs-panel')[0].id);
              tabselected = true;
            }
            $('input[name=' + ctrlId.replace(/:/g, '\\\\:') + ']').parents('fieldset').removeClass('collapsed');
            $('input[name=' + ctrlId.replace(/:/g, '\\\\:') + ']').parents('.fieldset-wrapper').show();
          });
        },
        messages: ".json_encode(self::$validation_messages)."
      });\n";
    }
  }
  
  /**
   * Internal method to build a control from its options array and its template. Outputs the
   * prefix template, a label (if in the options), a control, the control's errors and a
   * suffix template.
   *
   * @param string $template Name of the control template, from the global $indicia_templates variable.
   * @param array $options Options array containing the control replacement values for the templates.
   * Options can contain a setting for prefixTemplate or suffixTemplate to override the standard templates.
   */
  public static function apply_template($template, $options) {
    global $indicia_templates;
    // Don't need the extraParams - they are just for service communication.
    $options['extraParams']=null;
    // Set default validation error output mode
    if (!array_key_exists('validation_mode', $options)) {
      $options['validation_mode']=self::$validation_mode;
    }
    // Decide if the main control has an error. If so, highlight with the error class and set it's title.
    $error="";
    if (self::$validation_errors!==null) {
      if (array_key_exists('fieldname', $options)) {
        $error = self::check_errors($options['fieldname'], true);
      }
    }
    // Add a hint to the control if there is an error and this option is set
    if ($error && in_array('hint', $options['validation_mode'])) {
      $options['title'] = 'title="'.$error.'"';
    } else {
      $options['title'] = '';
    }
    if (!array_key_exists('class', $options)) {
      $options['class']='';
    }
    if (!array_key_exists('disabled', $options)) {
      $options['disabled']='';
    }
    // Add an error class to colour the control if there is an error and this option is set
    if ($error && in_array('colour', $options['validation_mode'])) {
      $options['class'] .= ' ui-state-error';
      if (array_key_exists('outerClass', $options)) {
        $options['outerClass'] .= ' ui-state-error';
      } else {
        $options['outerClass'] = 'ui-state-error';
      }
    }
    // add validation metadata to the control if specified, as long as control has a fieldname
    if (array_key_exists('fieldname', $options)) {
      $validationClasses = self::build_validation_class($options);
      $options['class'] .= ' '.$validationClasses;
    }
    // replace html attributes with their wrapped versions, e.g. a class becomes class="..."
    foreach (self::$html_attributes as $name => $attr) {
      if (!empty($options[$name])) {
        $options[$name]=' '.$attr.'="'.$options[$name].'"';
      }
    }

    // If options contain a help text, output it at the end if that is the preferred position
    $r = self::get_help_text($options, 'before');
    //Add prefix
    $r .= self::apply_static_template('prefix', $options);

    // Add a label only if specified in the options array. Link the label to the inputId if available,
    // otherwise the fieldname (as the fieldname control could be a hidden control).
    if (array_key_exists('label', $options)) {
      $r .= str_replace(
          array('{label}', '{id}', '{labelClass}'),
          array(
              $options['label'],
              array_key_exists('inputId', $options) ? $options['inputId'] : $options['id'],
              array_key_exists('labelClass', $options) ? ' class="'.$options['labelClass'].'"' : '',
          ),
          $indicia_templates['label']
      );
    }
    // Output the main control
    $r .= self::apply_replacements_to_template($indicia_templates[$template], $options);

    // Add an error icon to the control if there is an error and this option is set
    if ($error && in_array('icon', $options['validation_mode'])) {
      $r .= $indicia_templates['validation_icon'];
    }
    // Add a message to the control if there is an error and this option is set
    if ($error && in_array('message', $options['validation_mode'])) {
      $r .=  self::apply_error_template($error, $options['fieldname']);
    }

    // Add suffix
    if (isset($validationClasses) && !empty($validationClasses) && strpos($validationClasses, 'required')!==false) {
      $r .= self::apply_static_template('requiredsuffix', $options);
    } else {
      $r .= self::apply_static_template('suffix', $options);
    }

    // If options contain a help text, output it at the end if that is the preferred position
    $r .= self::get_help_text($options, 'after');

    return $r;
  }
  
 /**
  * Call the enable_validation method to turn on client-side validation for any controls with
  * validation rules defined. 
  * To specify validation on each control, set the control's options array
  * to contain a 'validation' entry. This must be set to an array of validation rules in Indicia
  * validation format. For example, 'validation' => array('required', 'email').
  * @param string @form_id Id of the form the validation is being attached to.
  */
  public static function enable_validation($form_id) {
    self::$validated_form_id = $form_id;
    self::add_resource('validation');
  }
  
 /**
   * Converts the validation rules in an options array into a string that can be used as the control class,
   * to trigger the jQuery validation plugin.
   * @param $options. For validation to be applied should contain a validation entry, containing a single
   * validation string or an array of strings.
   * @return string The validation rules formatted as a class.
   */
  protected static function build_validation_class($options) {
    global $custom_terms;
    $rules = (array_key_exists('validation', $options) ? $options['validation'] : array());
    if (!is_array($rules)) $rules = array($rules);
    if (array_key_exists($options['fieldname'], self::$default_validation_rules)) {
      $rules = array_merge($rules, self::$default_validation_rules[$options['fieldname']]);
    }
    // Build internationalised validation messages for jQuery to use, if the fields have internationalisation strings specified
    foreach ($rules as $rule) {
      if (isset($custom_terms) && array_key_exists($options['fieldname'], $custom_terms))
        self::$validation_messages[$options['fieldname']][$rule] = sprintf(lang::get("validation_$rule"),
          lang::get($options['fieldname']));
    }
    // Convert these rules into jQuery format.
    return self::convert_to_jquery_val_metadata($rules);
  }
  
  /**
   * Returns templated help text for a control, but only if the position matches the $helpTextPos value, and
   * the $options array contains a helpText entry.
   * @param array $options Control's options array. Can specify the class for the help text item using option helpTextClass.
   * @param string $pos Either before or after. Defines the position that is being requested.
   * @return string Templated help text, or nothing.
   */
  protected static function get_help_text($options, $pos) {
    $options = array_merge(array('helpTextClass'=>'helpText'), $options);
    if (array_key_exists('helpText', $options) && !empty($options['helpText']) && self::$helpTextPos == $pos) {
      return str_replace('{helpText}', $options['helpText'], self::apply_static_template('helpText', $options));
    } else
      return '';
  }
  
  /**
   * Takes a template string (e.g. <div id="{id}">) and replaces the tokens with the equivalent values looked up from the $options array.
   */ 
  protected static function apply_replacements_to_template($template, $options) {
    // Build an array of all the possible tags we could replace in the template.
    $replaceTags=array();
    $replaceValues=array();
    foreach (array_keys($options) as $option) {
      if (!is_array($options[$option]) && !is_object($options[$option])) {
        array_push($replaceTags, '{'.$option.'}');
        array_push($replaceValues, $options[$option]);
      }
    }
    return str_replace($replaceTags, $replaceValues, $template);
  }
  
   /**
  * Takes a list of validation rules in Kohana/Indicia format, and converts them to the jQuery validation
  * plugin metadata format.
  * @param array $rules List of validation rules to be converted.
  * @return string Validation metadata classes to add to the input element.
  * @todo Implement a more complete list of validation rules. 
  * @todo Suspect there is a versioning issue, because the minimum/maximum rule classes don't work. 
  */
  protected static function convert_to_jquery_val_metadata($rules) {
    $converted = array();
    foreach ($rules as $rule) {
      // Detect the rules that can simply be passed through
      $rule = trim($rule);
      if    ($rule=='required'
          || $rule=='dateISO'
          || $rule=='date'
          || $rule=='email'
          || $rule=='url'
          || $rule=='time') {
        $converted[] = $rule;
      // Now any rules which need parsing or conversion
      } else if ($rule=='digit') {
        $converted[] = 'digits';
      // the next test uses a regexp named expression to find the digit in a maximum rule (maximum[10])
      } elseif (preg_match('/maximum\[(?P<val>\d+)\]/', $rule, $matches)) {
        $converted[] = '{maxValue:'.$matches['val'].'}';
      // and again for minimum rules
      } elseif (preg_match('/minimum\[(?P<val>\d+)\]/', $rule, $matches)) {
        $converted[] = '{minValue:'.$matches['val'].'}';
      }
    }
    return implode(' ', $converted);
  }
  
   /**
  * Returns a static template which is either a default template or one
  * specified in the options
  * @param string $name The static template type. e.g. prefix or suffix.
  * @param array $options Array of options which may contain a template name.
  * @return string Template value.
  */
  protected static function apply_static_template($name, $options) {
    global $indicia_templates;
    $key = $name .'Template';
    $r = '';

    if (array_key_exists($key, $options)) {
      //a template has been specified
      if (array_key_exists($options[$key], $indicia_templates))
        //the specified template exists
        $r = $indicia_templates[$options[$key]];
      else
        $r = $indicia_templates[$name] .
        '<span class="ui-state-error">Code error: suffix template '.$options[$key].' not in list of known templates.</span>';
    } else {
      //no template specified
      $r = $indicia_templates[$name];
    }
    return self::apply_replacements_to_template($r, $options);
  }
  
  /**
   * Method to format a control error message inside a templated span.
   */ 
  private static function apply_error_template($error, $fieldname) {
    if (empty($error)) 
      return '';
    global $indicia_templates;
    if (empty($error)) return '';
    $template = str_replace('{class}', $indicia_templates['error_class'], $indicia_templates['validation_message']);
    $template = str_replace('{for}', $fieldname, $template);
    return str_replace('{error}', lang::get($error), $template);
  }
  
}

?>