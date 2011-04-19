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
 
/**
 * Link in other required php files.
 */
require_once('lang.php');
require_once('helper_base.php');

/**
 * Static helper class that provides methods for dealing with reports.
 */
class report_helper extends helper_base {

  /**
   * Returns a simple HTML link to download the contents of a report defined by the options. The options arguments supported are the same as for the 
   * report_grid method. Pagination information will be ignored (e.g. itemsPerPage).
   */
  public static function report_download_link($options) {
    $options = self::get_report_grid_options($options);
    $options['itemsPerPage'] = 10000; // a reasonable maximum
    $currentParamValues = self::get_report_grid_current_param_values($options);
    $sortAndPageUrlParams = self::get_report_grid_sort_page_url_params($options);
    // don't want to paginate the download link
    unset($sortAndPageUrlParams['page']);
    $extraParams = self::get_report_sorting_paging_params($options, $sortAndPageUrlParams);
    $options['linkOnly']=true;
    return '<a href="'.self::get_report_data($options, $extraParams.'&'.self::array_to_query_string($currentParamValues, true), true). '&mode=csv">'.lang::get('Download this report').'</a>';
  }
  
  /**
  * <p>Outputs a grid that loads the content of a report or Indicia table.</p>
  * <p>The grid supports a simple pagination footer as well as column title sorting through PHP. If
  * used as a PHP grid, note that the current web page will reload when you page or sort the grid, with the
  * same $_GET parameters but no $_POST information. If you need 2 grids on one page, then you must define a different
  * id in the options for each grid.</p>
  * <p>The grid operation will be handled by AJAX calls when possible to avoid reloading the web page.</p>
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>id</b><br/>
  * Optional unique identifier for the grid's container div. This is required if there is more than
  * one grid on a single web page to allow separation of the page and sort $_GET parameters in the URLs
  * generated.</li>
  * <li><b>paramsFormId</b><br/>
  * When joining multiple reports together, this can be used on a report that has autoParamsForm set to false to bind the report to the
  * parameters form from a different report. This will only work when all parameters required by this report are covered by the other report's
  * parameters form.</li>
  * <li><b>mode</b><br/>
  * Pass report for a report, or direct for an Indicia table or view. Default is report.</li>
  * <li><b>readAuth</b><br/>
  * Read authorisation tokens.</li>
  * <li><b>dataSource</b><br/>
  * Name of the report file or table/view.</li>
  * <li><b>itemsPerPage</b><br/>
  * Number of rows to display per page. Defaults to 20.</li>
  * <li><b>columns</b><br/>
  * Specify a list of the columns you want to output if you need more control over the columns, for example to
  * specify the order, change the caption or build a column with a configurable data display using a template.
  * Pass an array to this option, with each array entry containing an associative array that specifies the
  * information about the column represented by the position within the array. The associative array for the column can contain
  * the following keys:
  *  - fieldname: name of the field to output in this column. Does not need to be specified when using the template option.
  *  - display: caption of the column, which defaults to the fieldname if not specified
  *  - actions: list of action buttons to add to each grid row. Each button is defined by a sub-array containing
  *      values for caption, url, urlParams, class and javascript. The javascript, url and urlParams values can all use the
  *      field names from the report in braces as substitutions, for example {id} is replaced by the value of the field
  *      called id in the respective row. In addition, the url can use {currentUrl} to represent the current page's URL,
  *      {rootFolder} to represent the folder on the server that the current PHP page is running from, and 
  *      {imageFolder} for the image upload folder.
  *  - visible: true or false, defaults to true
  *  - template: allows you to create columns that contain dynamic content using a template, rather than just the output
  *  of a field. The template text can contain fieldnames in braces, which will be replaced by the respective field values.
  *  Note that template columns cannot be sorted by clicking grid headers.
  * An example array for the columns option is:
  * array(
  *   array('fieldname' => 'survey', 'display' => 'Survey Title'),
  *   array('display' => 'action', 'template' => '<a href="www.mysite.com\survey\{id}\edit">Edit</a>'),
  *   array('display' => 'Actions', 'actions' => array(
  *     array('caption' => 'edit', 'url'=>'{currentUrl}', 'urlParams'=>array('survey_id'=>'{id}'))
  *   ))
  *
  * )
  * </li>
  * <li><b>rowId</b>
  * Optional. Names the field in the data that contains the unique identifier for each row. If set, then the &lt;tr&gt; elements have their id attributes
  * set to row + this field value, e.g. row37.</li>
  * <li><b>IncludeAllColumns</b>
  * Defaults to true. If true, then any columns in the report, view or table which are not in the columns
  * option array are automatically added to the grid after any columns specified in the columns option array.
  * Therefore the default state for a report_grid control is to include all the report, view or table columns
  * in their default state, since the columns array will be empty.</li>
  * <li><b>headers</b>
  * Should a header row be included? Defaults to true.
  * <li><b>galleryColCount</b>
  * If set to a value greater than one, then each grid row will contain more than one record of data from the database, allowing
  * a gallery style view to be built. Defaults to 1.
  * <li><b>autoParamsForm</b>
  * Defaults to true. If true, then if a report requires parameters, a parameters input form will be auto-generated
  * at the top of the grid. If set to false, then it is possible to manually build a parameters entry HTML form if you
  * follow the following guidelines. First, you need to specify the id option for the report grid, so that your
  * grid has a reproducable id. Next, the form you want associated with the grid must itself have the same id, but with
  * the addition of params on the end. E.g. if the call to report_grid specifies the option 'id' to be 'my-grid' then
  * the parameters form must be called 'my-grid-params'. Finally the input controls which define each parameter must have
  * the name 'param-id-' followed by the actual parameter name, replacing id with the grid id. So, in our example,
  * a parameter called survey will need an input or select control with the name attribute set to 'param-my-grid-survey'.
  * The submit button for the form should have the method set to "get" and should post back to the same page.
  * As a final alternative, if parameters are required by the report but some can be hard coded then
  * those may be added to the extraParams array.</li>
  * <li><b>paramDefaults</b>
  * Optional associative array of parameter default values.</li>
  * <li><b>paramsOnly</b>
  * Defaults to false. If true, then this method will only return the parameters form, not the grid content. autoParamsForm
  * is ignored if this flag is set.</li>
  * <li><b>ignoreParams</b>
  * Array that can be set to a list of the report parameter names that should not be included in the parameters form. Useful
  * when using paramsOnly=true to display a parameters entry form, but the system has default values for some of the parameters
  * which the user does not need to be asked about.</li>
  * <li><b>completeParamsForm</b>
  * Defaults to true. If false, the control HTML is returned for the params form without being wrapped in a <form> and
  * without the Run Report button, allowing it to be embedded into another form.</li>
  * <li><b>paramsFormButtonCaption</b>
  * Caption of the button to run the report on the report parameters form. Defaults to Run Report. This caption
  * is localised when appropriate.
  * </ul>
  * @todo Allow additional params to filter by table column or report parameters
  * @todo Display a filter form for direct mode
  * @todo For report mode, provide an AJAX/PHP button that can load the report from parameters
  * in a form on the page.
  */
  public static function report_grid($options) {
    self::add_resource('fancybox');
    self::$javascript .= "jQuery('a.fancybox').fancybox();\n";
    $options = self::get_report_grid_options($options);
    // Output a div to keep the grid and pager together
    $r = '<div id="'.$options['id'].'">';
    $sortAndPageUrlParams = self::get_report_grid_sort_page_url_params($options);
    $extraParams = self::get_report_sorting_paging_params($options, $sortAndPageUrlParams);
    // request the report data using the preset values in extraParams but not any parameter defaults or entries in the URL. This is because the preset
    // values cause the parameter not to be shown, whereas defaults and URL params still show the param in the parameters form. So here we are asking for the 
    // parameters form if needed, else the report data. 
    $response = self::get_report_data($options, $extraParams);
    if (isset($response['error'])) return $response['error'];
    if (isset($response['parameterRequest'])) {
      $currentParamValues = self::get_report_grid_current_param_values($options);
      $r .= self::get_report_grid_parameters_form($response, $options, $currentParamValues);
      // if we have a complete set of parameters in the URL, we can re-run the report to get the data
      if (count($currentParamValues)==count($response['parameterRequest'])) {
        $response = self::get_report_data($options, $extraParams.'&'.self::array_to_query_string($currentParamValues, true));
        if (isset($response['error'])) return $response['error'];
        $records = $response['records'];
      }
    } else {
      $records = $response['records'];
    }
    // return the params form, if that is all that is being requested.
    if ($options['paramsOnly']) return $r;
    
    self::report_grid_get_columns($response, $options);
    $pageUrl = self::report_grid_get_reload_url($sortAndPageUrlParams);
    $thClass = $options['thClass'];
    $r .= "\n<table class=\"".$options['class']."\">";
    if ($options['headers']!==false) {
      $r .= "\n<thead class=\"$thClass\"><tr>\n";
      // build a URL with just the sort order bit missing, so it can be added for each table heading link
      $sortUrl = $pageUrl . ($sortAndPageUrlParams['page']['value'] ?
          $sortAndPageUrlParams['page']['name'].'='.$sortAndPageUrlParams['page']['value'].'&' :
          ''
      );
      $sortdirval = $sortAndPageUrlParams['sortdir']['value'] ? strtolower($sortAndPageUrlParams['sortdir']['value']) : 'asc';
      // Output the headers. Repeat if galleryColCount>1;
      for ($i=0; $i<$options['galleryColCount']; $i++) {
        foreach ($options['columns'] as $field) {
          if (isset($field['visible']) && ($field['visible']=='false' || $field['visible']===false))
            continue; // skip this column as marked invisible
          // allow the display caption to be overriden in the column specification
          $caption = lang::get(empty($field['display']) ? $field['fieldname'] : $field['display']);
          if (isset($field['fieldname'])) {
            if (empty($field['orderby'])) $field['orderby']=$field['fieldname'];
            $sortLink = $sortUrl.$sortAndPageUrlParams['orderby']['name'].'='.$field['orderby'];
            // reverse sort order if already sorted by this field in ascending dir
            if ($sortAndPageUrlParams['orderby']['value']==$field['orderby'] && $sortAndPageUrlParams['sortdir']['value']!='DESC')
              $sortLink .= '&'.$sortAndPageUrlParams['sortdir']['name']."=DESC";
            if (!isset($field['img']) || $field['img']!='true')
              $caption = "<a href=\"$sortLink\" title=\"Sort by $caption\">$caption</a>";
            // set a style for the sort order
            $orderStyle = ($sortAndPageUrlParams['orderby']['value']==$field['orderby']) ? ' '.$sortdirval : '';
            $orderStyle .= ' sortable';
            $fieldId = ' id="' . $options['id'] . '-th-' . $field['orderby'] . '"';
          } else {
            $orderStyle = '';
            $fieldId = '';
          }
          $r .= "<th$fieldId class=\"$thClass$orderStyle\">$caption</th>\n";
        }
      }
      $r .= "</tr></thead>\n";
    }
    $r .= "<tbody>\n";
    $rowClass = '';
    $outputCount = 0;
    $imagePath = self::get_uploaded_image_folder();
    $currentUrl = self::get_reload_link_parts();
    $relpath = self::relative_client_helper_path();
    if (count($records)>0) {
      foreach ($records as $rowIdx => $row) {
        // Don't output the additional row we requested just to check if the next page link is required.
        if ($outputCount>=$options['itemsPerPage'])
          break;
        // Put some extra useful paths into the row data, so it can be used in the templating
        $row = array_merge($row, array(
            'rootFolder'=>dirname($_SERVER['PHP_SELF']) . '/',
            'imageFolder'=>$imagePath,
            // allow the current URL to be replaced into an action link. We extract url parameters from the url, not $_GET, in case
            // the url is being rewritten.
            'currentUrl' => $currentUrl['path']
        ));
        // set a unique id for the row if we know the identifying field.
        $rowId = isset($options['rowId']) ? ' id="row'.$row[$options['rowId']].'"' : '';
        if ($rowIdx % $options['galleryColCount']==0) {
          $r .= "<tr $rowClass$rowId>";
          $rowInProgress=true;
        }
        foreach ($options['columns'] as $field) {
          $classes=array();
          if (isset($field['visible']) && ($field['visible']=='false' || $field['visible']===false))
            continue; // skip this column as marked invisible
          if (isset($field['actions'])) {
            $value = self::get_report_grid_actions($field['actions'],$row);
            $classes[]='actions';
          } elseif (isset($field['template'])) {
            $value = self::mergeParamsIntoTemplate($row, $field['template'], true, true);
          }
          else {
            $value = isset($field['fieldname']) && isset($row[$field['fieldname']]) ? $row[$field['fieldname']] : '';
            // The verification_1 form depends on the tds in the grid having a class="data fieldname".
            $classes[]='data';
            $classes[]=$field['fieldname'];
          }
          if (isset($field['class']))
            $classes[] = $field['class'];
          if (count($classes)>0)
            $class = ' class="'.implode(' ', $classes).'"';
          else
            $class = '';
          if (isset($field['img']) && $field['img']=='true' && !empty($value))
            $value = "<a href=\"$imagePath$value\" class=\"fancybox\"><img src=\"$imagePath"."thumb-$value\" /></a>";
          $r .= "<td$class>$value</td>\n";
        }
        if ($rowIdx % $options['galleryColCount']==$options['galleryColCount']-1) {
          $rowInProgress=false;
          $r .= '</tr>';
        }
        $rowClass = empty($rowClass) ? ' class="'.$options['altRowClass'].'"' : '';
        $outputCount++;
      }
      if ($rowInProgress)
        $r .= '</tr>';
    }
    
    $r .= "</tbody></table>\n";
    // Output pagination links
    $pagLinkUrl = $pageUrl . ($sortAndPageUrlParams['orderby']['value'] ? $sortAndPageUrlParams['orderby']['name'].'='.$sortAndPageUrlParams['orderby']['value'].'&' : '');
    $pagLinkUrl .= $sortAndPageUrlParams['sortdir']['value'] ? $sortAndPageUrlParams['sortdir']['name'].'='.$sortAndPageUrlParams['sortdir']['value'].'&' : '';
    $r .= "<div class=\"pager\">\n";
    // If not on first page, we can go back.
    if ($sortAndPageUrlParams['page']['value']>0) {
      $prev = max(0, $sortAndPageUrlParams['page']['value']-1);
      $r .= "<a class=\"prev\" href=\"$pagLinkUrl".$sortAndPageUrlParams['page']['name']."=$prev\">&#171 previous</a> \n";
    }
    // pagination separator if both links are present
    if ($sortAndPageUrlParams['page']['value']>0 && count($records)>$options['itemsPerPage'])
      $r .= ' &#166; ';
    // if the service call returned more records than we are displaying (because we asked for 1 more), then we can go forward
    if (count($records)>$options['itemsPerPage']) {
      $next = $sortAndPageUrlParams['page']['value'] + 1;
      $r .= "<a class=\"next\" href=\"$pagLinkUrl".$sortAndPageUrlParams['page']['name']."=$next\">next &#187</a> \n";
    }
    $r .= "</div></div>\n";

    // Now AJAXify the grid
    self::add_resource('reportgrid');
    self::$javascript .= "$('#".$options['id']."').reportgrid({
  mode: '".$options['mode']."',
  dataSource: '".str_replace('\\','/',$options['dataSource'])."',
  itemsPerPage: ".$options['itemsPerPage'].",
  auth_token: '".$options['readAuth']['auth_token']."',
  nonce: '".$options['readAuth']['nonce']."',
  extraParams: ".json_encode($options['extraParams']).",
  callback: '".$options['callback']."',
  url: '".parent::$base_url."',
  paramsFormId: '".$options['paramsFormId']."',
  rootFolder: '".dirname($_SERVER['PHP_SELF'])."/',
  imageFolder: '".self::get_uploaded_image_folder()."',
  currentUrl: '".$currentUrl['path']."',
  galleryColCount: ".$options['galleryColCount'].",
  altRowClass: '".$options['altRowClass']."'";
    if (isset($orderby))
      self::$javascript .= ",
  orderby: '".$orderby."'";
    if (isset($sortdir))
      self::$javascript .= ",
  sortdir: '".$sortdir."'";
    if (isset($options['columns']))
      self::$javascript .= ",\n  columns: ".json_encode($options['columns'])."
});\n";
    return $r;
  }
  
 /**
  * Function to output a report onto a map rather than a grid.
  * Because there are many options for the map, this method does not generate the
  * map itself, rather it sends the output of the report onto a map_panel output
  * elsewhere on the page. Like the report_grid, this can output a parameters
  * form or can be set to use the parameters form from another output report (e.g.
  * another call to report_grid, allowing both a grid and map of the same data
  * to be generated). The report definition must contain a single column which is
  * configured as a mappable column or the report must specify a parameterised
  * CQL query to draw the map using WMS.
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>id</b><br/>
  * Optional unique identifier for the report. This is required if there is more than
  * one different report (grid, chart or map) on a single web page to allow separation
  * of the page and sort $_GET parameters in the URLs
  * generated.</li>
  * <li><b>paramsFormId</b><br/>
  * When joining multiple reports together, this can be used on a report that has autoParamsForm set to false to bind the report to the
  * parameters form from a different report. This will only work when all parameters required by this report are covered by the other report's
  * parameters form.</li>
  * <li><b>mode</b><br/>
  * Pass report for a report, or direct for an Indicia table or view. Default is report.</li>
  * <li><b>readAuth</b><br/>
  * Read authorisation tokens.</li>
  * <li><b>dataSource</b><br/>
  * Name of the report file or table/view.</li>
  * <li><b>autoParamsForm</b>
  * Defaults to true. If true, then if a report requires parameters, a parameters input form will be auto-generated
  * at the top of the grid. If set to false, then it is possible to manually build a parameters entry HTML form if you
  * follow the following guidelines. First, you need to specify the id option for the report grid, so that your
  * grid has a reproducable id. Next, the form you want associated with the grid must itself have the same id, but with
  * the addition of params on the end. E.g. if the call to report_grid specifies the option 'id' to be 'my-grid' then
  * the parameters form must be called 'my-grid-params'. Finally the input controls which define each parameter must have
  * the name 'param-id-' followed by the actual parameter name, replacing id with the grid id. So, in our example,
  * a parameter called survey will need an input or select control with the name attribute set to 'param-my-grid-survey'.
  * The submit button for the form should have the method set to "get" and should post back to the same page.
  * As a final alternative, if parameters are required by the report but some can be hard coded then
  * those may be added to the extraParams array.</li>
  * <li><b>paramDefaults</b>
  * Optional associative array of parameter default values.</li>
  * <li><b>paramsOnly</b>
  * Defaults to false. If true, then this method will only return the parameters form, not the grid content. autoParamsForm
  * is ignored if this flag is set.</li>
  * <li><b>ignoreParams</b>
  * Array that can be set to a list of the report parameter names that should not be included in the parameters form. Useful
  * when using paramsOnly=true to display a parameters entry form, but the system has default values for some of the parameters
  * which the user does not need to be asked about.</li>
  * <li><b>completeParamsForm</b>
  * Defaults to true. If false, the control HTML is returned for the params form without being wrapped in a <form> and
  * without the Run Report button, allowing it to be embedded into another form.</li>
  * <li><b>paramsFormButtonCaption</b>
  * Caption of the button to run the report on the report parameters form. Defaults to Run Report. This caption
  * is localised when appropriate.
  * </ul>
   */
  public static function report_map($options) {
    $options = self::get_report_grid_options($options);
    // request the report data using the preset values in extraParams but not any parameter defaults or entries in the URL. This is because the preset
    // values cause the parameter not to be shown, whereas defaults and URL params still show the param in the parameters form. So here we are asking for the 
    // parameters form if needed, else the report data. 
    $response = self::get_report_data($options, $extraParams);
    if (isset($response['error'])) return $response['error'];
    if (isset($response['parameterRequest'])) {
      $currentParamValues = self::get_report_grid_current_param_values($options);
      $r .= self::get_report_grid_parameters_form($response, $options, $currentParamValues);
      // if we have a complete set of parameters in the URL, we can re-run the report to get the data
      if (count($currentParamValues)==count($response['parameterRequest'])) {
        $response = self::get_report_data($options, $extraParams.'&'.self::array_to_query_string($currentParamValues, true).'&wantColumns=1&wantParameters=1');
        if (isset($response['error'])) return $response['error'];
        $records = $response['records'];
      }
    } else {
      $records = $response['records'];
    }
    
    if (!isset($response['records']))
      return $r;
    // find the geom column
    foreach($response['columns'] as $col=>$cfg) {
      if ($cfg['mappable']=='true') {
        $wktCol = $col;
        break;
      }
    }
  
    if (!isset($wktCol))
      $r .= "<p>".lang::get("The report does not contain any mappable data")."</p>";

    report_helper::$javascript.= "
/**
 * Selecting a feature on a vector reporting layer displays a popup.
 */
onFeatureSelect = function(feature) {
  selectedFeature = feature;
  var content='';
  $.each(feature.data, function(name, value) {
    if (name.substr(0, 5)!=='date_') {
      content += '<tr><td style=\"font-weight:bold;\">' + name + '</td><td>' + value + '</td></tr>';
    }
  });
  popup = new OpenLayers.Popup.FramedCloud('popup', 
                           feature.geometry.getBounds().getCenterLonLat(),
                           null,
                           '<table style=\"font-size:.8em\">' + content + '</table>',
                           null, true);
  feature.popup = popup;
  feature.layer.map.addPopup(popup);
};
    
function addDistPoint(features, record, wktCol) {
  var geom=OpenLayers.Geometry.fromWKT(record[wktCol]);
  delete record[wktCol];
  features.push(new OpenLayers.Feature.Vector(geom, record));
}\n\n";
  report_helper::$javascript.= "
mapInitialisationHooks.push(function(div) {
  var layer = new OpenLayers.Layer.Vector('Report output');
  features = [];\n";
  foreach ($records as $record) {
    report_helper::$javascript.= "  addDistPoint(features, ".json_encode($record).", '".$wktCol."');\n";
  }
  report_helper::$javascript.= "  layer.addFeatures(features);
  div.map.addLayer(layer);
  if (layer.getDataExtent()!==null)
    div.map.zoomToExtent(layer.getDataExtent());
  /*
  @todo Implement clicking on vectors
  // create a control for selecting features and displaying popups
  var selectControl = new OpenLayers.Control.SelectFeature(layer,
    {clickout: true, toggle: false,
                        multiple: false, hover: false,
                        toggleKey: \"ctrlKey\", // ctrl key removes from selection
                        multipleKey: \"shiftKey\", // shift key adds to selection
                        box: true, onSelect: onFeatureSelect
  });
  div.map.addControl(selectControl);
  selectControl.activate();
  */
  
});\n";
    return $r;
  }
  
  /**
   * Method that retrieves the data from a report or a table/view, ready to display in a chart or grid.
   * @param array $options Options array for the control. Can contain a dataSource (report or table/view name),
   * mode (direct or report) and readAuth entries. Pass linkOnly=true to return just a link to the report data
   * rather than the data.
   * @param string $extra Any additional parameters to append to the request URL, for example orderby, limit or offset.
   * @return object If linkOnly is set in the options, returns the link string, otherwise returns the response as an array. 
   */
  public static function get_report_data($options, $extra='') {
    if ($options['mode']=='report') {
      $serviceCall = 'report/requestReport?report='.$options['dataSource'].'.xml&reportSource=local&';
    } elseif ($options['mode']=='direct') {
      $serviceCall = 'data/'.$options['dataSource'].'?';
    } else {
      throw new Exception('Invalid mode parameter for call to report_grid');
    }
    // We request limit 1 higher than actually needed, so we know if the next page link is required.
    $request = parent::$base_url.'index.php/services/'.
        $serviceCall.
        'mode=json&nonce='.$options['readAuth']['nonce'].
        '&auth_token='.$options['readAuth']['auth_token'].
        $extra;
    if (isset($options['extraParams'])) {
    	foreach ($options['extraParams'] as $key=>$value)
    	  $request .= "&$key=".urlencode($value);
    }
    if (isset($options['linkOnly']) && $options['linkOnly'])
      return $request;
    else {
      $response = self::http_post($request, null);
      $decoded = json_decode($response['output'], true);
      if (empty($decoded)) 
        return array('error'=>print_r($response, true));
      else
        return $decoded;
    }
  }
  
  /**
   * Generates the extra URL parameters that need to be appended to a report service call request, in order to 
   * include the sorting and pagination parameters.
   * @param array @options Options array sent to the report.
   * @param array @sortAndPageUrlParams Paging and sorting info returned from a call to get_report_grid_sort_page_url_params.
   * @return string Snippet of URL containing the required URL parameters.
   */
  private static function get_report_sorting_paging_params($options, $sortAndPageUrlParams) {
    // Work out the names and current values of the params we expect in the report request URL for sort and pagination    
    $page = ($sortAndPageUrlParams['page']['value'] ? $sortAndPageUrlParams['page']['value'] : 0);
    // set the limit to one higher than we need, so the extra row can trigger the pagination next link
    $extraParams = '&limit='.($options['itemsPerPage']+1).'&wantColumns=1&wantParameters=1';
    $extraParams .= '&offset=' . $page * $options['itemsPerPage'];

    // Add in the sort parameters
    foreach ($sortAndPageUrlParams as $param => $content) {
      if ($content['value']!=null) {
        if ($param != 'page')
          $extraParams .= '&' . $param .'='. $content['value'];
      }
    }
    return $extraParams;
  }

  /**
   * Works out the orderby, sortdir and page URL param names for this report grid, and also gets their
   * current values.
   * @param $options Control options array
   * @return array Contains the orderby, sortdir and page params, as an assoc array. Each array value
   * is an array containing name & value.
   */
  private static function get_report_grid_sort_page_url_params($options) {
    $orderbyKey = 'orderby' . (isset($options['id']) ? '-'.$options['id'] : '');
    $sortdirKey = 'sortdir' . (isset($options['id']) ? '-'.$options['id'] : '');
    $pageKey = 'page' . (isset($options['id']) ? '-'.$options['id'] : '');
    return array(
      'orderby' => array(
        'name' => $orderbyKey,
        'value' => isset($_GET[$orderbyKey]) ? $_GET[$orderbyKey] : null
      ),
      'sortdir' => array(
        'name' => $sortdirKey,
        'value' => isset($_GET[$sortdirKey]) ? $_GET[$sortdirKey] : null
      ),
      'page' => array(
        'name' => $pageKey,
        'value' => isset($_GET[$pageKey]) ? $_GET[$pageKey] : null
      )
    );
  }


  /**
   * Build a url suitable for inclusion in the links for the report grid column headings or pagination
   * bar. This effectively re-builds the current page's URL, but drops the query string parameters that
   * indicate the sort order and page number.
   * @param array $sortAndPageParams List of the sorting and pagination parameters which should be excluded.
   * @return unknown_type
   */
  private static function report_grid_get_reload_url($sortAndPageUrlParams) {
    // get the url parameters. Don't use $_GET, because it contains any parameters that are not in the
    // URL when search friendly URLs are used (e.g. a Drupal path node/123 is mapped to index.php?q=node/123
    // using Apache mod_alias but we don't want to know about that)
    $reloadUrl = self::get_reload_link_parts();
    // Build a basic URL path back to this page, but with the page, sortdir and orderby removed
    $pageUrl = $reloadUrl['path'].'?';
    // find the names of the params we must not include
    $excludedParams = array();
    foreach($sortAndPageUrlParams as $param) {
      $excludedParams[] = $param['name'];
    }
    foreach ($reloadUrl['params'] as $key => $value) {
      if (!in_array($key, $excludedParams))
        $pageUrl .= "$key=$value&";
    }
    return $pageUrl;
  }

  /**
   * Private function that builds a parameters form according to a parameterRequest recieved
   * when calling a report. If the autoParamsForm is false then an empty string is returned.
   * @param $response
   * @param $options
   * @param $params
   * @return string HTML for the form.
   */
  private static function get_report_grid_parameters_form($response, $options, $params) {
    if ($options['autoParamsForm'] || $options['paramsOnly']) {
      // get the url parameters. Don't use $_GET, because it contains any parameters that are not in the
      // URL when search friendly URLs are used (e.g. a Drupal path node/123 is mapped to index.php?q=node/123
      // using Apache mod_alias but we don't want to know about that)
      $reloadUrl = self::get_reload_link_parts();
      $r = '';
      if ($options['completeParamsForm']==true) {
        $r .= '<form action="'.$reloadUrl['path'].'" method="get" id="'.$options['id'].'-params">'."\n";
        $r .= '<fieldset><legend>'.lang::get('Report Parameters').'</legend>';
      }
      // Output any other get parameters from our URL as hidden fields
      foreach ($reloadUrl['params'] as $key => $value) {
        // since any param will be from a URL it could be encoded. So we don't want to double encode if they repeatedly
        // run the report.
        $value = urldecode($value);
        // ignore any parameters that are going to be in the grid parameters form
        if (substr($key,0,6)!='param-')
          $r .= "<input type=\"hidden\" value=\"$value\" name=\"$key\" />\n";
      }
      $r .= self::build_params_form(array_merge(array('form'=>$response['parameterRequest'], 'field_name_prefix'=>'param', 'defaults'=>$params), $options));
      if ($options['completeParamsForm']==true) {
        $r .= '<input type="submit" value="'.lang::get($options['paramsFormButtonCaption']).'" id="run-report"/>'."\n";
        $r .= "</fieldset></form>\n";
      }
      return $r;
    } else {
      return $r;
    }
  }

  /**
   * Add any columns that don't have a column definition to the end of the columns list, by first
   * building an array of the column names of the columns we did specify, then adding any missing fields
   * from the results to the end of the options['columns'] array.
   * @param $response
   * @param $options
   * @return unknown_type
   */
  private static function report_grid_get_columns($response, &$options) {
    if ($options['includeAllColumns'] && isset($response['columns'])) {
      $specifiedCols = array();
      $actionCols = array();
      $idx=0;
      foreach ($options['columns'] as $col) {
        if (isset($col['fieldname'])) $specifiedCols[] = $col['fieldname'];
        // action columns need to be removed and added to the end
        if (isset($col['actions'])) {
          // remove the action col from its current location, store it so we can add it to the end
          unset($options['columns'][$idx]);
          $actionCols[] = $col;
        }
        $idx++;
      }
      foreach ($response['columns'] as $resultField => $value) {
        if (!in_array($resultField, $specifiedCols)) {
          $options['columns'][] = array_merge(
            $value,
            array('fieldname'=>$resultField)
          );
        }
      }
      // add the actions columns back in at the end
      $options['columns'] = array_merge($options['columns'], $actionCols);
    }
  }

  private static function get_report_grid_actions($actions, $row) {
    $links = array();
    $currentUrl = self::get_reload_link_parts(); // needed for params
    foreach ($actions as $action) {
      if (isset($action['url'])) {        
        // include any $_GET parameters to reload the same page, except the parameters that are specified by the action
        if (isset($action['urlParams'])) 
          $urlParams = array_merge($currentUrl['params'], $action['urlParams']);
        else if (substr($action['url'], 0, 1)=='#')
          // if linking to an internal bookmark, no need to attach the url parameters
          $urlParams = array();
        else
          $urlParams = array_merge($currentUrl['params']);
        if (count($urlParams)>0) {
          $action['url'].= (strpos($action['url'], '?')===false) ? '?' : '&';
        }
        $href=' href="'.self::mergeParamsIntoTemplate($row, $action['url'].self::array_to_query_string($urlParams), true).'"';
      } else {
        $href='';
      }
      if (isset($action['javascript'])) {
        $onclick=' onclick="'.self::mergeParamsIntoTemplate($row,$action['javascript'],true).'"';
      } else {
        $onclick = '';
      }
      $class=(isset($action['class'])) ? ' '.$action['class'] : '';
      $links[] = "<a class=\"action-button$class\"$href$onclick>".$action['caption'].'</a>';
    }
    return implode('<br/>', $links);
  }

  private static function get_report_grid_options($options) {
    // Generate a unique number for this grid, in case there are 2 on a page.
    $uniqueId = rand(0,10000);
    $options = array_merge(array(
      'mode' => 'report',
      'id' => 'grid-'.$uniqueId,
      'itemsPerPage' => 20,
      'class' => 'ui-widget ui-widget-content report-grid',
      'thClass' => 'ui-widget-header',
      'altRowClass' => 'odd',
      'columns' => array(),
      'galleryColCount' => 1,
      'headers' => true,
      'includeAllColumns' => true,
      'autoParamsForm' => true,
      'paramsOnly' => false,
      'extraParams' => array(),
      'completeParamsForm' => true,
      'callback' => '',
      'paramsFormButtonCaption' => 'Run Report'
    ), $options);
    if ($options['galleryColCount']>1) $options['class'] .= ' gallery';
    // use the current report as the params form by default
    if (!isset($options['paramsFormId'])) $options['paramsFormId'] = $options['id'];
    return $options;
  }


  /**
   * Returns the query string describing additional sort query params for a
   * data request to populate the report grid.
   */
  private static function get_report_grid_data_request_sort_params($options, $paramKeyNames) {
    $r = '';
    if (isset($_GET[$paramKeyNames['orderby']]))
      $orderby = $_GET[$paramKeyNames['orderby']];
    else
      $orderby = null;
    if ($orderby)
      $r .= "&orderby=$orderby";
    if (isset($_GET[$paramKeyNames['sortdir']]))
      $sortdir = $_GET[$paramKeyNames['sortdir']];
    else
      $sortdir = 'ASC';
    if ($sortdir && $orderby)
      $r .= "&sortdir=$sortdir";
    return $r;
  }

  /**
   * Returns the parameters for the report grid data services call which are embedded in the query string or 
   * default param value data.
   * @param $options
   * @return Array Associative array of parameters.
   */
  private static function get_report_grid_current_param_values($options) {
    $params = array();
    // get defaults first
    if (isset($options['paramDefaults'])) {
      foreach ($options['paramDefaults'] as $key=>$value) {
        // trim data to ensure blank lines are not handled.
        $key = trim($key);
        $value = trim($value);
        // We have found a parameter, so put it in the request to the report service
        if (!empty($key))
          $params[$key]=$value;
      }
    }
    // Are there any parameters embedded in the URL, e.g. after submitting the params form?
    $paramKey = 'param-' . (isset($options['paramsFormId']) ? $options['paramsFormId'] : '').'-';
    foreach ($_GET as $key=>$value) {
      if (substr($key, 0, strlen($paramKey))==$paramKey) {
        // We have found a parameter, so put it in the request to the report service
        $param = substr($key, strlen($paramKey));
        $params[$param]=$value;
      }
    }
    return $params;
  }

}

?>