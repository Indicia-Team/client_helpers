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
 * @package Client
 * @subpackage PrebuiltForms
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/indicia-team/client_helpers/
 */

/*
 * Future enhancements:
 * Allow url arguments to set default values to controls.
 */
require_once('includes/form_generation.php');
require_once('includes/report.php');
require_once('includes/user.php');

/**
 * Prebuilt Indicia data form that lists the output of any report
 *
 * @package Client
 * @subpackage PrebuiltForms
 */
class iform_ukbms_year_index_plot {

  /**
   * Return the form metadata.
   * @return string The definition of the form.
   */
  public static function get_ukbms_year_index_plot_definition() {
    return array(
      'title'=>'UKBMS Year by Year Index Plot',
      'category' => 'Reporting',
      'description'=>'This shows the year by year counts, and is able to show this for individual species as well as all-species.',
    );
  }

  /* Installation/configuration notes
   * get_css function is now reducdant: use form arguments to add link to report_calendar_summary_2.css file
   * */
  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    return
      array(
      	array(
      		'name'=>'nidvsnode',
      		'caption'=>'nid interface',
      		'description'=>'In the arguments for the get_form function, the second can be either nid or the node, depending on the client_helpers version. Check this field if nid.',
      		'type'=>'boolean',
      		'default' => false,
      		'required' => false,
        ),

      	array(
          'name'=>'manager_permission',
          'caption'=>'Drupal Permission for Manager mode',
          'description'=>'Enter the Drupal permission name to be used to determine if this user is a manager (i.e. full access to full data set). This primarily determines the functionality of the Location filter.',
          'type'=>'string',
          'required' => false,
          'group' => 'Access Control'
        ),
        array(
          'name'=>'branch_manager_permission',
          'caption'=>'Drupal Permission for Branch Coordinator mode',
          'description'=>'Enter the Drupal permission name to be used to determine if this user is a Branch Coordinator. This primarily determines the functionality of the Location filter.',
          'type'=>'string',
          'required' => false,
          'group' => 'Access Control'
        ),
      	array(
      		'name' => 'cmsLocAttrId',
      		'caption' => 'CMS User ID Attribute',
      		'description' => 'A location multivalue attribute, used to allocate sites to a user.',
      		'type'=>'select',
      		'table'=>'location_attribute',
      		'captionField'=>'caption',
      		'valueField'=>'id',
      		'siteSpecific'=>true,
      		'group' => 'Access Control'
      	),
      	array(
      		'name' => 'branchCmsLocAttrId',
      		'caption' => 'Branch Attribute',
      		'description' => 'A location attribute (potentially multivalue), used to allocate sites to a user at a branch level.',
      		'type'=>'select',
      		'table'=>'location_attribute',
      		'captionField'=>'caption',
      		'valueField'=>'id',
      		'siteSpecific'=>true,
      		'required' => false,
      		'group' => 'Access Control'
      	),
          array(
              'name' => 'branchAttributeValue',
              'caption' => 'Branch Attribute Value',
              'description' => 'The value to use for the branch attribute filtering, defaults to the CMS user Id',
              'type'=>'string',
              'siteSpecific'=>true,
              'required' => false,
              'group' => 'Access Control'
          ),
          array(
      		'name' => 'sensitivityLocAttrId',
      		'caption' => 'Location attribute used to filter out sensitive sites',
      		'description' => 'A boolean location attribute, set to true if a site is sensitive.',
      		'type'=>'select',
      		'table'=>'location_attribute',
      		'captionField'=>'caption',
      		'valueField'=>'id',
      		'siteSpecific'=>true,
      		'required' => false,
      		'group' => 'Access Control'
      	),
      	array(
      		'name' => 'sensitivityAccessPermission',
      		'caption' => 'Sensitivity access permission',
      		'description' => 'A permission, which if granted allows viewing of sensitive sites.',
      		'type' => 'string',
      		'required' => false,
      		'group' => 'Access Control'
      	),

          array(
              'name'=>'taxon_column',
              'caption'=>'Display Taxon field',
              'description'=>'When selecting the taxon to display, choose which of the report columns to use.',
              'type' => 'select',
              'lookupValues' => array('taxon'=>'Common Name',
                  'preferred_taxon'=>'Preferred Taxon (usually Latin)'),
              'required' => true,
              'default' => 'taxon',
              'group'=>'Controls'
          ),

      	array(
      		'name'=>'index_report_name',
      		'caption'=>'Index Data Report',
      		'description'=>'Select the report to provide the data when getting the index data.',
      		'type'=>'report_helper::report_picker',
      		'group'=>'Report Settings'
      	),
      	array(
      		'name'=>'index_report_count_field',
      		'caption'=>'Index Report Count Field',
      		'description'=>'Name of the field in the index report which holds the count value.',
      		'type'=>'string',
      		'group' => 'Report Settings'
      	),
        array(
      		'name' => 'index_param_presets',
      		'caption' => 'Index Report Preset Parameter Values',
      		'description' => 'Preset values for additional report parameters: will depened on report itself. '.
      		'One per line. Each parameter is followed by an equals then the value, e.g. occattrs=6. You can use {user_id} as a value which will be replaced by the '.
      		'user ID from the CMS logged in user or {username} as a value replaces with the logged in username.',
      		'type' => 'textarea',
      		'required' => false,
      		'group'=>'Report Settings'
      	),

      	array(
      		'name'=>'count_report_name',
      		'caption'=>'Count Data Report',
      		'description'=>'Select the report to provide the data when getting the count data.',
      		'type'=>'report_helper::report_picker',
      		'group'=>'Report Settings'
      	),
      	array(
      		'name'=>'count_report_count_field',
      		'caption'=>'Count Report Count Field',
      		'description'=>'Name of the field in the count report which holds the count value.',
      		'type'=>'string',
      		'group' => 'Report Settings'
      	),
        array(
          'name' => 'count_report_count_json',
          'caption' => 'Count data JSON encoded?',
          'description' => 'Is the count data JSON encoded? Is there more than one period&apos;s data stored in the field?',
          'type' => 'boolean',
          'required' => false,
          'group' => 'Report Settings'
        ),
        array(
      		'name' => 'count_param_presets',
      		'caption' => 'Count Report Preset Parameter Values',
      		'description' => 'Preset values for additional report parameters: will depened on report itself. '.
      		'One per line. Each parameter is followed by an equals then the value, e.g. occattrs=6. You can use {user_id} as a value which will be replaced by the '.
      		'user ID from the CMS logged in user or {username} as a value replaces with the logged in username.',
      		'type' => 'textarea',
      		'required' => false,
      		'group'=>'Report Settings'
      	),
      	array(
      		'name'=>'taxonList',
      		'caption'=>'Taxon List',
      		'type'=>'select',
      		'table'=>'taxon_list',
      		'captionField'=>'title',
      		'valueField'=>'id',
      		'siteSpecific'=>true,
      		'required' => false,
      		'group' => 'Report Settings'
      	),
      		array(
      		'name' => 'report_group',
      		'caption' => 'Report group',
      		'description' => 'When using several reports on a single page (e.g. <a href="http://code.google.com/p/indicia/wiki/DrupalDashboardReporting">dashboard reporting</a>) '.
      		'you must ensure that all reports that share a set of input parameters have the same report group as the parameters report.',
      		'type' => 'text_input',
      		'default' => 'report',
      		'group' => 'Report Settings'
      				), array(
      						'name' => 'remember_params_report_group',
      						'caption' => 'Remember report parameters group',
      						'description' => 'Enter any value in this parameter to allow the report to save its parameters for the next time the report is loaded. '.
      						'The parameters are saved site wide, so if several reports share the same value and the same report group then the parameter '.
      						'settings will be shared across the reports even if they are on different pages of the site. This functionality '.
      						'requires cookies to be enabled on the browser.',
      						'type'=>'text_input',
      						'required'=>false,
      						'default' => '',
      						'group'=>'Report Settings'
      				),

      	array(
      		'name'=>'locationTypesFilter',
      		'caption'=>'Restrict locations to types',
      		'description'=>'Comma separated list of the location types definitions to be included in the control, of form {Location Type Term}:{Survey ID}:{include Sref in location filter Y N}. If more than one, implies a location type selection control. Restricts the locations in the location filter to the selected location type, and restricts the data retrieved to the defined survey. The CMS User ID attribute must be defined for all location types selected or all location types.',
      		'type'=>'string',
      		'group' => 'Controls'
      	),

        array(
          'name' => 'width',
          'caption' => 'Chart Width',
          'description' => 'Width of the output chart in pixels: if not set then it will automatically to fill the space.',
          'type' => 'text_input',
          'required' => false,
          'group'=>'Chart Options'
        ),
        array(
          'name' => 'height',
          'caption' => 'Chart Height',
          'description' => 'Height of the output chart in pixels.',
          'type' => 'text_input',
          'required' => true,
          'default' => 500,
          'group'=>'Chart Options'
        ),
        array(
          'name' => 'renderer_options',
          'caption' => 'Renderer Options',
          'description' => 'Editor for the renderer options to pass to the chart. For full details of the options available, '.
              'see <a href="http://www.jqplot.com/docs/files/plugins/jqplot-barRenderer-js.html">bar chart renderer options</a> or '.
              '<a href="http://www.jqplot.com/docs/files/plugins/jqplot-lineRenderer-js.html">line charts rendered options</a>.',
          'type' => 'jsonwidget',
          'schema' => '{
  "type":"map",
  "title":"Renderer Options",
  "mapping":{
    "barPadding":{"title":"Bar Padding", "type":"int","desc":"Number of pixels between adjacent bars at the same axis value."},
    "barMargin":{"title":"Bar Margin", "type":"int","desc":"Number of pixels between groups of bars at adjacent axis values."},
    "barDirection":{"title":"Bar Direction", "type":"str","desc":"Select vertical for up and down bars or horizontal for side to side bars","enum":["vertical","horizontal"]},
    "barWidth":{"title":"Bar Width", "type":"int","desc":"Width of the bar in pixels (auto by devaul)."},
    "shadowOffset":{"title":"Bar Slice Shadow Offset", "type":"number","desc":"Offset of the shadow from the slice and offset of each succesive stroke of the shadow from the last."},
    "shadowDepth":{"title":"Bar Slice Shadow Depth", "type":"int","desc":"Number of strokes to apply to the shadow, each stroke offset shadowOffset from the last."},
    "shadowAlpha":{"title":"Bar Slice Shadow Alpha", "type":"number","desc":"Transparency of the shadow (0 = transparent, 1 = opaque)"},
    "waterfall":{"title":"Bar Waterfall","type":"bool","desc":"Check to enable waterfall plot."},
    "groups":{"type":"int","desc":"Group bars into this many groups."},
    "varyBarColor":{"type":"bool","desc":"Check to color each bar of a series separately rather than have every bar of a given series the same color."},
    "highlightMouseOver":{"type":"bool","desc":"Check to highlight slice, bar or filled line plot when mouse over."},
    "highlightMouseDown":{"type":"bool","desc":"Check to highlight slice, bar or filled line plot when mouse down."},
    "highlightColors":{"type":"seq","desc":"An array of colors to use when highlighting a bar.",
        "sequence":[{"type":"str"}]
    },
    "highlightColor":{"type":"str","desc":"A colour to use when highlighting an area on a filled line plot."}
  }
}',
          'required' => false,
          'group'=>'Chart Options'
        ),
        array(
          'name' => 'axes_options',
          'caption' => 'Axes Options',
          'description' => 'Editor for axes options to pass to the chart. Provide entries for yaxis and xaxis as required. '.
              'Applies to line and bar charts only. For full details of the options available, see '.
              '<a href="http://www.jqplot.com/docs/files/jqplot-core-js.html#Axis">chart axes options</a>. '.
              'For example, <em>{"yaxis":{"min":0,"max":100}}</em>.',
          'type' => 'jsonwidget',
          'required' => false,
          'group'=>'Chart Options',
          'schema'=>'{
  "type":"map",
  "title":"Axis options",
  "mapping":{
    "xaxis":{
      "type":"map",
      "mapping":{
        "show":{"type":"bool"},
        "tickOptions":{"type":"map","mapping":{
          "mark":{"type":"str","desc":"Tick mark type on the axis.","enum":["inside","outside","cross"]},
          "showMark":{"type":"bool"},
          "showGridline":{"type":"bool"},
          "isMinorTick":{"type":"bool"},
          "markSize":{"type":"int","desc":"Length of the tick marks in pixels.  For �cross� style, length will be stoked above and below axis, so total length will be twice this."},
          "show":{"type":"bool"},
          "showLabel":{"type":"bool"},
          "formatString":{"type":"str","desc":"Text used to construct the tick labels, with %s being replaced by the label."},
          "fontFamily":{"type":"str","desc":"CSS spec for the font-family css attribute."},
          "fontSize":{"type":"str","desc":"CSS spec for the font-size css attribute."},
          "textColor":{"type":"str","desc":"CSS spec for the color attribute."},
        }},
        "label":{"type":"str","desc":"Label for the axis."},
        "min":{"type":"number","desc":"minimum value of the axis (in data units, not pixels)."},
        "max":{"type":"number","desc":"maximum value of the axis (in data units, not pixels)."},
        "autoscale":{"type":"bool","desc":"Autoscale the axis min and max values to provide sensible tick spacing."},
        "pad":{"type":"number","desc":"Padding to extend the range above and below the data bounds.  The data range is multiplied by this factor to determine minimum '.
            'and maximum axis bounds.  A value of 0 will be interpreted to mean no padding, and pad will be set to 1.0."},
        "padMax":{"type":"number","desc":"Padding to extend the range above data bounds.  The top of the data range is multiplied by this factor to determine maximum '.
            'axis bounds.  A value of 0 will be interpreted to mean no padding, and padMax will be set to 1.0."},
        "padMin":{"type":"numer","desc":"Padding to extend the range below data bounds.  The bottom of the data range is multiplied by this factor to determine minimum '.
            'axis bounds.  A value of 0 will be interpreted to mean no padding, and padMin will be set to 1.0."},
        "numberTicks":{"type":"int","desc":"Desired number of ticks."},
        "tickInterval":{"type":"number","desc":"Number of units between ticks."},
        "showTicks":{"type":"bool","desc":"Whether to show the ticks (both marks and labels) or not."},
        "showTickMarks":{"type":"bool","desc":"Wether to show the tick marks (line crossing grid) or not."},
        "showMinorTicks":{"type":"bool","desc":"Wether or not to show minor ticks."},
        "useSeriesColor":{"type":"bool","desc":"Use the color of the first series associated with this axis for the tick marks and line bordering this axis."},
        "borderWidth":{"type":"int","desc":"Width of line stroked at the border of the axis."},
        "borderColor":{"type":"str","desc":"Color of the border adjacent to the axis."},
        "syncTicks":{"type":"bool","desc":"Check to try and synchronize tick spacing across multiple axes so that ticks and grid lines line up."},
        "tickSpacing":{"type":"","desc":"Approximate pixel spacing between ticks on graph.  Used during autoscaling.  This number will be an upper bound, actual spacing will be less."}
      }
    },
    "yaxis":{
      "type":"map",
      "mapping":{
        "show":{"type":"bool"},
        "tickOptions":{"type":"map","mapping":{
          "mark":{"type":"str","desc":"Tick mark type on the axis.","enum":["inside","outside","cross"]},
          "showMark":{"type":"bool"},
          "showGridline":{"type":"bool"},
          "isMinorTick":{"type":"bool"},
          "markSize":{"type":"int","desc":"Length of the tick marks in pixels.  For �cross� style, length will be stoked above and below axis, so total length will be twice this."},
          "show":{"type":"bool"},
          "showLabel":{"type":"bool"},
          "formatString":{"type":"str","desc":"Text used to construct the tick labels, with %s being replaced by the label."},
          "fontFamily":{"type":"str","desc":"CSS spec for the font-family css attribute."},
          "fontSize":{"type":"str","desc":"CSS spec for the font-size css attribute."},
          "textColor":{"type":"str","desc":"CSS spec for the color attribute."},
        }},
        "label":{"type":"str","desc":"Label for the axis."},
        "min":{"type":"number","desc":"minimum value of the axis (in data units, not pixels)."},
        "max":{"type":"number","desc":"maximum value of the axis (in data units, not pixels)."},
        "autoscale":{"type":"bool","desc":"Autoscale the axis min and max values to provide sensible tick spacing."},
        "pad":{"type":"number","desc":"Padding to extend the range above and below the data bounds.  The data range is multiplied by this factor to determine minimum '.
            'and maximum axis bounds.  A value of 0 will be interpreted to mean no padding, and pad will be set to 1.0."},
        "padMax":{"type":"number","desc":"Padding to extend the range above data bounds.  The top of the data range is multiplied by this factor to determine maximum '.
            'axis bounds.  A value of 0 will be interpreted to mean no padding, and padMax will be set to 1.0."},
        "padMin":{"type":"numer","desc":"Padding to extend the range below data bounds.  The bottom of the data range is multiplied by this factor to determine minimum '.
            'axis bounds.  A value of 0 will be interpreted to mean no padding, and padMin will be set to 1.0."},
        "numberTicks":{"type":"int","desc":"Desired number of ticks."},
        "tickInterval":{"type":"number","desc":"Number of units between ticks."},
        "showTicks":{"type":"bool","desc":"Whether to show the ticks (both marks and labels) or not."},
        "showTickMarks":{"type":"bool","desc":"Wether to show the tick marks (line crossing grid) or not."},
        "showMinorTicks":{"type":"bool","desc":"Wether or not to show minor ticks."},
        "useSeriesColor":{"type":"bool","desc":"Use the color of the first series associated with this axis for the tick marks and line bordering this axis."},
        "borderWidth":{"type":"int","desc":"Width of line stroked at the border of the axis."},
        "borderColor":{"type":"str","desc":"Color of the border adjacent to the axis."},
        "syncTicks":{"type":"bool","desc":"Check to try and synchronize tick spacing across multiple axes so that ticks and grid lines line up."},
        "tickSpacing":{"type":"","desc":"Approximate pixel spacing between ticks on graph.  Used during autoscaling.  This number will be an upper bound, actual spacing will be less."}
      }
    }
  }
}'
        ),
      	array(
      		'name' => 'legend_options',
      		'caption' => 'Legend Options',
      		'description' => 'Editor for the legend options to pass to the chart. For full details of the options available, '.
      				'see <a href="http://www.jqplot.com/docs/files/jqplot-core-js.html#Legend">chart legend options</a>. '.
      				'For example, set the value to <em>{"show":true,"location":"ne"}</em> to show the legend in the top-right '.
      				'(north east) corner. Note some legend options are set by this form, so are not available in this list.',
      		'type' => 'jsonwidget',
            'required' => false,
            'group'=>'Chart Options',
      		'schema'=>'{
  "type":"map",
  "title":"Legend Options",
  "mapping":{
    "show":{"type":"bool","desc":"Check to show the legend."},
    "location":{"type":"str","desc":"Placement of the legend (compass direction).","enum":["nw","n","ne","e","se","s","sw","w"]},
    "showLabels":{"type":"bool","desc":"Check to show the label text on the legend."},
    "showSwatch":{"type":"bool","desc":"Check to show the color swatches on the legend."},
    "placement":{"type":"str","desc":"insideGrid places legend inside the grid area of the plot. OutsideGrid places the legend outside the grid but inside the plot container, shrinking the '.
      				'grid to accomodate the legend. Outside places the legend ouside the grid area, but does not shrink the grid which can cause the legend to overflow the plot container.",
        "enum":["insideGrid","outsideGrid","outside"]},
    "border":{"type":"str","desc":"CSS spec for the border around the legend box."},
    "background":{"type":"str","desc":"CSS spec for the background of the legend box."},
    "textColor":{"type":"str","desc":"CSS color spec for the legend text."},
    "fontFamily":{"type":"str","desc":"CSS font-family spec for the legend text."},
    "fontSize":{"type":"str","desc":"CSS font-size spec for the legend text."},
    "rowSpacing":{"type":"str","desc":"CSS padding-top spec for the rows in the legend."},
    "marginTop":{"type":"str","desc":"CSS margin for the legend DOM element."},
    "marginRight":{"type":"str","desc":"CSS margin for the legend DOM element."},
    "marginBottom":{"type":"str","desc":"CSS margin for the legend DOM element."},
    "marginLeft":{"type":"str","desc":"CSS margin for the legend DOM element."}
  }
}'
      	)
    );
  }

  // easy login: the user restriction is on created_by_id, and refers to the Indicia id
  // The report helper does the conversion from CMS to Easy Login ID if appropriate, so the user_id passed into the
  // report helper is always the CMS one.
  // Locations are assigned by a CMS user ID attribute, not by who created them.

  private static function _set_up_survey_mapping($args, $readAuth, &$options)
  {
    $types = explode(',',$args['locationTypesFilter']);
    $types1=array();
    $types2=array();
    $options['surveyMapping']=array();
    foreach($types as $type){
      $parts = explode(':',$type);
      $types1[] = $parts[0];
      $types2[] = $parts;
    }
    $terms = self::_get_sorted_termlist_terms(array('read'=>$readAuth), 'indicia:location_types', $types1);
    for($i = 0; $i < count($terms); $i++){
        $options['surveyMapping'][$terms[$i]['id']] = array('location_type_id'=>$terms[$i]['id'],
        		'location_type_term'=>$terms[$i]['term'],
        		'survey_id'=>$types2[$i][1],
        		'includeSref' => ($types2[$i][2]=='Y'));
    }
  }

  private static function _location_control($args, $readAuth, $nid, $options)
  {
    //3 modes
    // User: all sites allocated to me via CMS User
    // Branch Manager: all sites allocated to me via CMS User or Branch CMS User
    // Admin: all sites.
    // assume if a sensitive site is allocated to a user, they have permission to see it.
  	// note that when in user specific mode it returns the list currently assigned to the user: it does not give
  	// locations which the user previously recorded data against, but is no longer allocated to.
    $userUID = hostsite_get_user_field('id');
    $superManager = (isset($args['manager_permission']) && $args['manager_permission']!="" && hostsite_user_has_permission($args['manager_permission']));
    $branchManager = (isset($args['branch_manager_permission']) && $args['branch_manager_permission']!="" && hostsite_user_has_permission($args['branch_manager_permission']));

    $ctrl = '<label class="location-select-label">'.lang::get('Site').':</label>';

    $cmsAttr = $args['cmsLocAttrId'];

    $locationListArgs=array(// 'nocache'=>true,
    		'extraParams'=>array_merge(array('website_id'=>$args['website_id'], 'location_type_id' => '',
    		    'sensattr' => '',
    		    'exclude_sensitive' => '',
    		    'locattrs'=>(!empty($args['sensitivityLocAttrId']) ? $args['sensitivityLocAttrId'] : '')),
    				$readAuth),
            'readAuth' => $readAuth,
            'caching' => true,
            'dataSource' => 'library/locations/locations_list_exclude_sensitive');
	// could use locattrs to fetch sensitive
    $attrArgs = array(
    		'valuetable'=>'location_attribute_value',
    		'attrtable'=>'location_attribute',
    		'key'=>'location_id',
    		'fieldprefix'=>'locAttr',
    		'extraParams'=>$readAuth);

    // loop through all entries in the locationTypesFilter, and build an array of locations.
    $locationTypeLookUpValues = array();
    $default = false;
    if (isset($_COOKIE['providedParams']) && !empty($args['remember_params_report_group'])) {
    	$cookieData = json_decode($_COOKIE['providedParams'], true);
    	// guard against a corrupt cookie
    	if (is_array($cookieData) && !empty($cookieData[$args['remember_params_report_group']])) {
    		$cookieParams = $cookieData[$args['remember_params_report_group']];
    		$locationTypeKey = (isset($args['report_group']) ? $args['report_group'].'-' : '').'location_type_id';
    		if (is_array($cookieParams) && isset($cookieParams[$locationTypeKey])) {
    			$default = $cookieParams[$locationTypeKey];
    		}
    	}
    }

    foreach(array_keys($options['surveyMapping']) as $location_type_id) {
      if(!$default)
      	$default = $location_type_id;
      $attrArgs['location_type_id'] = $location_type_id;
      $attrArgs['survey_id'] = $options['surveyMapping'][$location_type_id]['survey_id'];
      $locationListArgs['extraParams']['location_type_id'] = $location_type_id;
      $locationTypeLookUpValues[$location_type_id] = $options['surveyMapping'][$location_type_id]['location_type_term'];
      // first use attributes to find list of locations allocated to me
      // for an admin, we can see all sites, including sensitive.
      if($superManager) {
      	$locationListArgs['extraParams']['idlist'] = '';
  	    $locationList = report_helper::get_report_data($locationListArgs);
      } else {
      	$locationIDList=array();
      	// first get locations allocated to me
      	$attrListArgs=array(// 'nocache'=>true,
      			'extraParams'=>array_merge(array('view'=>'list', 'website_id'=>$args['website_id'],
      					'location_attribute_id'=>$cmsAttr, 'raw_value'=>$userUID),
      					$readAuth),
      			'table'=>'location_attribute_value');
      	$attrList = data_entry_helper::get_population_data($attrListArgs);
      	if (isset($attrList['error'])) return $attrList['error'];
      	if(count($attrList)>0) {
      		foreach($attrList as $attr)
      			$locationIDList[] = $attr['location_id'];
      	}
      	// then get locations allocated to me as branch
        if ($branchManager && !empty($args['branchCmsLocAttrId'])) {
            if (empty($args['branchAttributeValue'])) {
                $args['branchAttributeValue'] = '{id}';
            }
            $attrListArgs = array(// 'nocache'=>true,
                'extraParams' => array_merge(
                    array(
                        'view' => 'list',
                        'website_id' => $args['website_id'],
                        'location_attribute_id' => $args['branchCmsLocAttrId'],
                        'raw_value' => apply_user_replacements($args['branchAttributeValue'])
                    ),
                    $readAuth
                    ),
                'table'=>'location_attribute_value'
            );
            $attrList = data_entry_helper::get_population_data($attrListArgs);
            if (isset($attrList['error'])) return $attrList['error'];
            if(count($attrList)>0) {
                foreach($attrList as $attr) {
                    $locationIDList[] = $attr['location_id'];
                }
            }
        }
        $locationListArgs['extraParams']['idlist'] = implode(',', $locationIDList);

        // unless decided on the future, if allocated to you, you can see the results: i.e. no sensitivity filtering.
        // if(isset($args['sensitivityAccessPermission']) && $args['sensitivityAccessPermission']!="" &&
        //   !hostsite_user_has_permission($args['sensitivityAccessPermission']) &&
        //   !empty($args['sensitivityLocAttrId'])) {
        //   $locationListArgs['extraParams']['attr_location_'.$args['sensitivityLocAttrId']] = '0';
        // }

        if($locationListArgs['extraParams']['idlist'] != '') {
  	    	$locationList = report_helper::get_report_data($locationListArgs);
      	} else $locationList = array();
      }
      if (isset($locationList['error'])) return $locationList['error'];
      // next get select of locations.
      $sort = array();
      $locs = array();
      foreach($locationList as $location) {
      	$sort[$location['id']]=$location['name']; // locations_list report returns location_id, not id
      	$locs[$location['id']]=$location;
      }
      natcasesort($sort);
      $ctrl .='<select id="'.$options['locationSelectIDPrefix'].'-'.$location_type_id.'" class="location-select">';
      if(count($locs)>0) {
      	$ctrl .= '<option value="" class="location-select-option italic-option" >&lt;'.lang::get('Please select').' : '.$options['surveyMapping'][$location_type_id]['location_type_term'].'&gt;</option>';
      	foreach($sort as $id=>$name){
      		$ctrl .= '<option value='.$id.' class="location-select-option '.
      			(!empty($args['sensitivityLocAttrId']) && $locs[$id]['attr_location_'.$args['sensitivityLocAttrId']] === "1" ? 'sensitive' : '').
      			'" >'.
      			$name.($options['surveyMapping'][$location_type_id]['includeSref'] ? ' ('.$locs[$id]['centroid_sref'].')' : '').
      			'</option>';
      	}
      } else
      	$ctrl .= '<option value="" class="location-select-option" >&lt;'.lang::get('No sites available').'&gt;</option>';
      $ctrl .='</select>';
    }
    if(!array_key_exists($default, $locationTypeLookUpValues))
        $default=array_keys($locationTypeLookUpValues)[0];
    // default location type is the first in the list, so populate its locations as default as well.
    if(count($locationTypeLookUpValues)>1){
    	$ctrl = data_entry_helper::select(array(
    			'label' => lang::get('Site Type'),
    			'id' => $options['locationTypeSelectID'],
    			'fieldname' => 'location_type_id',
    			'lookupValues' => $locationTypeLookUpValues,
    			'default' => $default,
    			'validation' => array('required')
    	)).'</th><th>'.$ctrl;
    } else {
    	$ctrl .= '<input type="hidden" id="'.$options['locationTypeSelectID'].'" name="location_type_id" value="'.$default.'" />';
    }

    return $ctrl.'<span class="deh-required">*</span>';
  }

  private static function _species1_control($args, $readAuth, $nid, $options)
  {
  	// Species lists are populated when the data is loaded by the JS
  	// Just have blank selects.
    return '<label for="'.$options['species1SelectID'].'" class="species1-select-label">'.lang::get('Species 1').':</label>' .
    			'<select id="'.$options['species1SelectID'].'" class="species-select">' .
					'<option value="" class="location-select-option" >&lt;'.lang::get('No data loaded yet').'&gt;</option>' .
				'</select>';
  }

  private static function _species2_control($args, $readAuth, $nid, $options)
  {
  	// Species lists are populated when the data is loaded by the JS
  	// Just have blank selects.
  	return '<label for="'.$options['species2SelectID'].'" class="species2-select-label">'.lang::get('Species 2').':</label>' .
  			'<select id="'.$options['species2SelectID'].'" class="species-select">' .
  			'<option value="" class="location-select-option" >&lt;'.lang::get('No data loaded yet').'&gt;</option>' .
  			'</select>';
  }

  private static function _copy_args($args, &$options, $list){
    foreach($list as $arg){
      if(isset($args[$arg]) && $args[$arg]!="")
        $options[$arg]=$args[$arg];
      else
        $options[$arg]="";
    }
  }

  private static function _data_type_control($args, $readAuth, $nid, $options)
  {
	$r = '<label for="'.$options['dataTypeSelectID'].'">'.lang::get("Data").':</label>'.
		'<select id="'.$options['dataTypeSelectID'].'" name="data_type">'.
		'<option value="index">'.lang::get("Index").'</option>'.
		'<option value="count">'.lang::get("Count").'</option>'.
		'</select><span class="deh-required">*</span>';
    return $r;
  }

  private static function _get_sorted_termlist_terms($auth, $key, $filter){
    $terms = helper_base::get_termlist_terms($auth, $key, $filter);
    $retVal = array();
    foreach($filter as $f) { // return in order provided in filter.
      foreach($terms as $term) {
        if($f == $term['term']) $retVal[] = $term;
      }
    }
    return $retVal;
  }

  private static function _load_data_button($args, $auth, $nid, $options)
  {
    return '<input type="button" id="loadButton" class="loadButton" '.
           'value="'.lang::get('Fetch Data').'" '.
           'title="'.lang::get('Click this button to fetch the data from the database in order to display the graph. You must specify a site before you can fetch the data.').'" '.
           '/>';
  }

  private static function _trendline_control($args, $auth, $nid, $options)
  {
  	return '<label for="add-trendline" >'.lang::get("Add trendline").' </label>'.
  			'<input type="checkbox" name="add-trendline" id="add-trendline" check="checked"/>';
  }

  private static function _build_primary_toolbar($args, $auth, $nid, &$options)
  {
  	/* NB only interested in complete data picture - not user specific */
  	return '<tr>' .
		  	'<th>' . self::_location_control($args, $auth, $nid, $options) . '</th>' . // note this includes the location_type control if needed
		  	'<th>' . self::_data_type_control($args, $auth, $nid, $options) . '</th>' .
		  	'<th>' . self::_load_data_button($args, $auth, $nid, $options) . '</th>' .
		  	'</tr>';
  }

  private static function _build_secondary_toolbar($args, $auth, $nid, &$options)
  {
  	return '<tr>' .
  			'<th>' . self::_species1_control($args, $auth, $nid, $options) . '</th>' .
  			'<th>' . self::_species2_control($args, $auth, $nid, $options) . '</th>' .
  			'<th>' . self::_trendline_control($args, $auth, $nid, $options) . '</th>' .
  			'</tr>';
  }

  /**
   * Return the Indicia form code
   * @param array $args Input parameters.
   * @param array $nid Drupal node number
   * @param array $response Response from Indicia services after posting a verification.
   * @return HTML string
   */
  public static function get_form($args, $arg2, $response) {
    $retVal = '';

    if(isset($args['nidvsnode']) && $args['nidvsnode'])
    	$nid = $arg2;
    else $nid = $arg2->nid;

    if(hostsite_get_user_field('id') <= 0) { // we are assuming Drupal.
      return('<p>'.lang::get('Please log in before attempting to use this form.').'</p>');
    }

    if (!function_exists('hostsite_module_exists') || !hostsite_module_exists('easy_login')) {
      return('<p>'.lang::get('This form must be used with the easy_login module.').'</p>');
    }

    iform_load_helpers(array('report_helper'));
    data_entry_helper::add_resource('jquery_ui');
    data_entry_helper::add_resource('jqplot');
    data_entry_helper::add_resource('jqplot_bar');
    data_entry_helper::add_resource('jqplot_category_axis_renderer');
    data_entry_helper::add_resource('jquery_cookie');
    $renderer='$.jqplot.BarRenderer';

    $auth = report_helper::get_read_auth($args['website_id'], $args['password']);

    $options = array(
      'indexDataSource' => $args['index_report_name'],
      'countDataSource' => $args['count_report_name'],
      'mode' => 'report',
      'readAuth' => $auth,
      'base_url' => data_entry_helper::$base_url,
      'pleaseSelectMsg' => lang::get('Please select...'),
      'noDataMsg' => lang::get('No data available'),
      'noDataLoadedMsg' => lang::get('There is no data available for'),
      'selectPrompt' => lang::get('Select at least one species in the toolbar above in order to display the graph here.'),
      'bodyWarning' => lang::get('Please fetch the data for a different site in order to display a graph.'),
      'class' => 'ui-widget ui-widget-content report-grid',
      'extraParams' => array(),
      'countReportExtraParams' => '',
      'indexReportExtraParams' => '',
      'seriesData' => array(),
      'id' => 'uyip-chart-'.$nid,
      'locationTypeSelectID' => 'uyip-location-type-select-'.$nid,
      'locationSelectIDPrefix' => 'uyip-location-select-'.$nid,
      'dataTypeSelectID' => 'uyip-data-type-select-'.$nid,
      'dataLoadButtonID' => 'uyip-data-load-button-'.$nid,
      'species1SelectID' => 'uyip-species1-select-'.$nid,
      'species2SelectID' => 'uyip-species2-select-'.$nid,
      'allSpeciesMsg' => lang::get("All Species"),
      'reportGroup' => isset($args['report_group']) ? $args['report_group'] : '',
      'rememberParamsReportGroup' => isset($args['remember_params_report_group']) ? $args['remember_params_report_group'] : '',
      'taxon_column' => (isset($args['taxon_column']) ? $args['taxon_column'] : 'taxon')
    );

    self::_set_up_survey_mapping($args, $auth, $options);

    self::_copy_args($args, $options, array('width','height'));

    $options['countCountField']  = $args['count_report_count_field'];
    $options['countJSONEncoded'] = $args['count_report_count_json'];
    $options['countExtraParams'] = get_options_array_with_user_data($args['count_param_presets']);
    $options['indexCountField']  = $args['index_report_count_field'];
    $options['indexJSONEncoded'] = false;
    $options['indexExtraParams'] = get_options_array_with_user_data($args['index_param_presets']);

    if (function_exists('hostsite_get_user_field')) {
    	// If the host environment (e.g. Drupal module) can tell us which Indicia user is logged in, pass that
    	// to the report call as it might be required for filters.
    	if (!isset($options['extraParams']['user_id']) && $indiciaUserId = hostsite_get_user_field('indicia_user_id')) {
    		$options['countExtraParams']['user_id'] = $indiciaUserId;
    		$options['indexExtraParams']['user_id'] = $indiciaUserId;
    	}
    }
    // taxon_list_id=51
    if(isset($args['taxonList']) && $args['taxonList']!='') {
    	$options['countExtraParams']['taxon_list_id']=$args['taxonList'];
    	$options['indexExtraParams']['taxon_list_id']=$args['taxonList'];
    }

    foreach($options['countExtraParams'] as $key => $value) {
    	$options['countReportExtraParams'] .= '&'.$key.'='.$value;
    }
    foreach($options['indexExtraParams'] as $key => $value) {
    	$options['indexReportExtraParams'] .= '&'.$key.'='.$value;
    }

    data_entry_helper::add_resource('jqplot_trendline');

    $opts = array();
    $rendererOptions = trim($args['renderer_options']);
    if (!empty($rendererOptions))
      $rendererOptions = json_decode($rendererOptions, true);
    else $rendererOptions = array();
    $opts['seriesDefaults'] = array("renderer"=>$renderer, "rendererOptions" => $rendererOptions);
    $legendOptions = trim($args['legend_options']);
    if (!empty($legendOptions))
    	$opts['legend'] = json_decode($legendOptions, true);
    else
    	$opts['legend'] = array("show"=>true, 'placement'=>'outsideGrid');
    $opts['series'] = array();
//    $opts['title'] = array("text"=>"Title");
    $optsToCopyThrough = array('legend'=>'legendOptions', 'series'=>'seriesOptions', 'seriesColors'=>'seriesColors');
    foreach ($optsToCopyThrough as $key=>$settings) {
    	if (!empty($options[$settings]))
    		$opts[$key] = $options[$settings];
    }

    // X axis values are going to be section names, Y is the count.
    // Going to be 2 series max - one for each species.
    $axesOptions = trim($args['axes_options']);
    if (!empty($axesOptions))
      $axesOptions = json_decode($axesOptions, true);
    else $axesOptions = array();
    $axesOptions['xaxis']['renderer'] = '$.jqplot.CategoryAxisRenderer';

    $axesOptions['xaxis']['ticks'] = array();
    $opts['axes'] = $axesOptions;
    $options['opts'] = $opts;

    // We need to fudge the json so the renderer class is not a string
    data_entry_helper::$javascript .= "
uyipPrepChart(" . str_replace(array('"$.jqplot.CategoryAxisRenderer"','"$.jqplot.CanvasAxisLabelRenderer"','"$.jqplot.BarRenderer"'), array('$.jqplot.CategoryAxisRenderer','$.jqplot.CanvasAxisLabelRenderer','$.jqplot.BarRenderer'), json_encode($options)) . ");
";

    $heightStyle = (!empty($options['height']) ? "height: $options[height]px;" : '');
    $widthStyle = "width: 100%;";
    // Add controls first: set up a control bar

    $retVal .= "\n".'<table id="primary-controls-table" class="ui-widget ui-widget-content ui-corner-all controls-table">' .
    				'<thead class="ui-widget-header">' .
	    				self::_build_primary_toolbar($args, $auth, $nid, $options) .
    				'</thead>' .
    			'</table>'."\n".
    			'<table id="secondary-controls-table" class="ui-widget ui-widget-content ui-corner-all controls-table">' .
    				'<thead class="ui-widget-header">' .
    			    '<tr><th colspan=3><h2 id="currentlyLoaded">'.lang::get('Please fetch the data for a site.').'</h2></th></tr>' .
    					self::_build_secondary_toolbar($args, $auth, $nid, $options) .
    				'</thead>' .
    			'</table>'.
    			'<div class="'.$options['class'].'" style="'.$widthStyle.'">' .
    				'<div id="'.$options['id'].'" style="'.$heightStyle.' '.$widthStyle.'" class="jqplot-target">'.
    				  '<p class="graph-body-warning">'.lang::get('You must fetch the data for a site before you can display a graph.').'</p>'.
    				'</div>' .
    			"</div>\n";

	return $retVal;
  }

}