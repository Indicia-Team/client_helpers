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
 * Extension class that supplies new controls to support the original Splash project.
 */
class extension_original_splash_extensions {
  
  /* 
   * If no options are supplied then the only validation applied is a check to make sure the plot is filled in.
   * 
   * $options Options array with the following possibilities:<ul>
   * <li><b>treeCountMode</b><br/>
   * If true then an additional check is made to make sure at least 1 tree has been entered.</li>
   * <li><b>treeGridRefAndEpiphyteMode</b><br/>
   * If true then all validation applies</li>
   * 
   * Validator for Splash Epiphyte survey input forms, validates the following:
   * - That a plot is filled in.
   * - The details of at least one tree have been entered (treeCountMode and treeGridRefAndEpiphyteMode)
   * - The user hasn't entered an Epiphyte presence for any trees that don't exist (treeGridRefAndEpiphyteMode)
   * - The user has filled in grid references for all trees (this doesn't use the built in mandatory field functionality of Indicia
   * as the system would flag the Epiphytes as not containing a grid references when they actually shouldn't) - (treeGridRefAndEpiphyteMode)
   */
  public static function splash_validate($auth, $args, $tabAlias, $options) {
    if (empty($options['treeOccurrenceAttrIds']) && !empty($options['treeGridRefAndEpiphyteMode']) && $options['treeGridRefAndEpiphyteMode']===true) {
      drupal_set_message('Please fill in the @treeOccurrenceAttrIds option for the splash_validate control.
                          This should be a comma seperated list of attribute ids that hold the Epiphyte counts for trees.');
      return '';
    }
    
    //The validator that makes sure the user hasn't entered a Epiphyte presence for a tree that doesn't exist works as follows.
    //- Cycle through each the occurrence attribute that holds the presence boolean for trees that haven't been entered on the trees grid (taking into account trees can be deleted)
    //- Use jQuery to cycle through each instance of the attribute on the page (effectively check all rows on both grids)
    //- Make a count of all attributes that are found as present (checked), taking into account rows can be deleted. As we are only checking the cells for trees not on the trees grid
    //if the error count is above 0 then we know there are problems on the page
    data_entry_helper::$javascript .= "
    $('<span class=\"deh-required\">*</span>').insertAfter('.scGridRef\\\\/Accuracy');
    $('#entry_form').submit(function() {     
      if ($('#imp-location').val()==='<Please select>'||$('#squares-select-list').val()==='<Please select>'||
          $('#imp-location').val()===''||$('#squares-select-list').val()==='') {
        alert('Please select a plot before submitting.');
        return false;
      }";
    if ((!empty($options['treeCountMode']) && $options['treeCountMode']===true)||
        (!empty($options['treeGridRefAndEpiphyteMode']) && $options['treeGridRefAndEpiphyteMode']===true)) {
      data_entry_helper::$javascript .= "    
      //Take 1 off because there is an empty row on the grid.
      var treesCount = $('#trees').find('.scTaxonCell:not([disabled])').length - 1;
      if (treesCount < 1) {
        alert('Please enter the details of at least 1 tree.');
        return false;
      }";
    }
    if (!empty($options['treeGridRefAndEpiphyteMode']) && $options['treeGridRefAndEpiphyteMode']===true) {
      $treeOccurrenceAttrIds=explode(',',$options['treeOccurrenceAttrIds']);
      data_entry_helper::$javascript .= "
      var treeOccurrenceAttrIds = ".json_encode($treeOccurrenceAttrIds).";
      if ($('.scGridRef\\\\/Accuracy[value=]').length>=3) {
        alert('Please fill in the grid reference field for all trees.');
        return false;
      }
      var epiphyteValidateResult;
      epiphyteValidateResult = runValidateOnEpiphyteGrid(treesCount,treeOccurrenceAttrIds);
      if (epiphyteValidateResult>0) {
        alert('You have entered an Epiphyte presence for a tree that doesn\'t exist in the trees grid. ' +
        'Number of problems found = ' + epiphyteValidateResult);
        return false;
      }";
    }
    data_entry_helper::$javascript .= "
    });";
    
    if (!empty($options['treeGridRefAndEpiphyteMode']) && $options['treeGridRefAndEpiphyteMode']===true) {
    data_entry_helper::$javascript .= "
    function runValidateOnEpiphyteGrid(treesCount,treeOccurrenceAttrIds) {
      var treeIdxToCheck; 
      var issueCount=0;
      for (treeIdxToCheck=treesCount; treeIdxToCheck<treeOccurrenceAttrIds.length;treeIdxToCheck++) {
        var result = $('[id*=occAttr\\\\:'+treeOccurrenceAttrIds[treeIdxToCheck]+']').each(function(){
          //Need to check if parent is not disabled as we want to check if the row has been deleted by the user, only
          //count issue if the row not deleted. Don't check cell itself as it is re-enabled just before submission to
          //allow value to be submitted.
          if ($(this).is(':checked') && $(this).parent().attr('disabled')!=='disabled') {
            issueCount++;
          }
        });
      }  
      return issueCount;
    }
    ";
    }
  }
  
  /**
   * Get a location select control pair, first the user must select a square then a plot associated with a square.
   * Only squares that are associated with the user and also have plots are displayed
   * When a plot is selected, then a mini report about the plot is displayed.
   * 
   * $options Options array with the following possibilities:<ul>
   * <li><b>coreSquareLocationTypeId</b><br/>
   * The location type id of a core square</li>
   * <li><b>additionalSquareLocationTypeId</b><br/>
   * The location type id of an additional square</li>
   * <li><b>viceCountyLocationAttributeId</b><br/>
   * The attribute ID that holds the vice counties associated with a square</li>
   * <li><b>noViceCountyFoundMessage</b><br/>
   * A square's vice country makes up part of its name, however if it doesn't have a vice county then display this replacement text instead</li>
   * <li><b>userSquareAttrId</b><br/>
   * The ID of the person attribute that holds the user squares.</li>
   * <li><b>orientationAttributeId</b><br/>
   * The location attribute id that holds a plot's Orientation</li>
   * <li><b>aspectAttributeId</b><br/>
   * The location attribute id that holds a plot's Aspect</li>
   * <li><b>slopeAttributeId</b><br/>
   * The location attribute id that holds a plot's Slope</li>
   * <li><b>ashAttributeId</b><br/>
   * The location attribute id that holds a plot's % Ash Coverage</li>
   * </ul>
   */
  public static function splash_location_select($auth, $args, $tabAlias, $options) {
    if (empty($options['coreSquareLocationTypeId'])) {
      drupal_set_message('Please fill in the @coreSquareLocationTypeId option for the splash_location_select control');
      return '';
    }
    if (empty($options['additionalSquareLocationTypeId'])) {
      drupal_set_message('Please fill in the @additionalSquareLocationTypeId option for the splash_location_select control');
      return '';
    }
    if (empty($options['viceCountyLocationAttributeId'])) {
      drupal_set_message('Please fill in the @viceCountyLocationAttributeId option for the splash_location_select control');
      return '';
    }
    if (empty($options['noViceCountyFoundMessage'])) {
      drupal_set_message('Please fill in the @noViceCountyFoundMessage option for the splash_location_select control');
      return '';
    }
    $coreSquareLocationTypeId=$options['coreSquareLocationTypeId'];
    $additionalSquareLocationTypeId=$options['additionalSquareLocationTypeId'];
    $currentUserId=hostsite_get_user_field('indicia_user_id');
    $viceCountyLocationAttributeId=$options['viceCountyLocationAttributeId'];
    $noViceCountyFoundMessage=$options['noViceCountyFoundMessage'];
    $userSquareAttrId=$options['userSquareAttrId'];
    $reportOptions = array(
      'dataSource'=>'reports_for_prebuilt_forms/Splash/original_splash_site_only/get_my_squares_that_have_plots',
      'readAuth'=>$auth['read'],
      'mode'=>'report',
      'extraParams' => array('core_square_location_type_id'=>$coreSquareLocationTypeId,
                             'additional_square_location_type_id'=>$additionalSquareLocationTypeId,
                             'current_user_id'=>$currentUserId,
                             'vice_county_location_attribute_id'=>$viceCountyLocationAttributeId,
                             'no_vice_county_found_message'=>$noViceCountyFoundMessage)
    );
    $rawData = data_entry_helper::get_report_data($reportOptions);
    if (empty($rawData)) {
      //If the user doesn't have any plots, then hide the map and disable the Spatial Ref field so they can't continue
      drupal_set_message('Note: You have not been allocated any squares to input data for, or the squares you have been allocated do not have plots.');
      drupal_set_message('You cannot enter data without having a plot to select.');
      data_entry_helper::$javascript .= "$('#map').hide();";
      data_entry_helper::$javascript .= "$('#imp-sref').attr('disabled','disabled');";
      return '<b>You have not been allocated any Squares that contain plots</b></br>';
    } else {
      //Convert the raw data in the report into array format suitable for the Select drop-down to user (an array of ID=>Name pairs)
      foreach($rawData as $rawRow) {
          $squaresData[$rawRow['id']]=$rawRow['name'];        
      }
      //Need a report to collect the square to default the Location Select to in edit mode, as this is not stored against the sample directly.
      if (!empty($_GET['sample_id'])) {
        $squareData = data_entry_helper::get_report_data(array(
          'dataSource'=>'reports_for_prebuilt_forms/Splash/get_square_for_sample',
          'readAuth'=>$auth['read'],
          'extraParams'=>array('sample_id'=>$_GET['sample_id'])
        ));
        $defaultSquareSelection=$squareData[0]['id'];
      } else {
        $defaultSquareSelection='';
      }
      $r = data_entry_helper::select(array(
        'id' => 'squares-select-list',
        'blankText'=>'<Please select>',
        'fieldname'=> 'squares-select-list',
        'label' => lang::get('Select a Square'),
        'helpText' => lang::get('Select a square to input data for before selecting a plot.'),
        'lookupValues' => $squaresData, 
        'default' => $defaultSquareSelection
      ));
      //This code is same as standard lookup control
      if (isset($options['extraParams'])) {
        foreach ($options['extraParams'] as $key => &$value)
          $value = apply_user_replacements($value);
        $options['extraParams'] = array_merge($auth['read'], $options['extraParams']);
      } else 
        $options['extraParams'] = array_merge($auth['read']);
      if (empty($options['reportProvidesOrderBy'])||$options['reportProvidesOrderBy']==0) {
        $options['extraParams']['orderby'] = 'name';
      }
      //Setup the Plot drop-down which uses the Suqare selection the user makes.
      $options['parentControlId']= 'squares-select-list';
      $options['filterField']= 'square_id';
      $options['reportProvidesOrderBy']=true;
      $options['searchUpdatesSref']=true;
      $options['label']='Plot';
      $options['report']='reports_for_prebuilt_forms/Splash/original_splash_site_only/get_plots_for_square_id';
      $options['extraParams']['current_user_id']=$currentUserId;
      $options['extraParams']['user_square_attr_id']=$userSquareAttrId;
      //Create the drop-down for the plot
      $location_list_args = array_merge(array(
          'label'=>lang::get('LANG_Location_Label'),
          'view'=>'detail'
      ), $options);
      $r .= data_entry_helper::location_select($location_list_args);
      //Create the mini report
      $r .= self::plot_report_panel($auth,$options);
      return $r;
    }
  }
  
  /*
   * Display a mini report when the user selects a plot
   */
  private static function plot_report_panel($auth,$options) {
    iform_load_helpers(array('report_helper'));
    $reportOptions = array(
      'linkOnly'=>'true',
      'dataSource'=>'reports_for_prebuilt_forms/Splash/get_square_details_for_square_id',
      'readAuth'=>$auth['read']
    );  
    //Report that will return the type of the square selected by the user
    data_entry_helper::$javascript .= "indiciaData.squareReportRequest='".
       report_helper::get_report_data($reportOptions)."';\n";
    
    $reportOptions = array(
      'linkOnly'=>'true',
      'dataSource'=>'reports_for_prebuilt_forms/Splash/original_splash_site_only/get_plot_details',
      'readAuth'=>$auth['read']
    );  
    data_entry_helper::$javascript .= "indiciaData.plotReportRequest='".
       report_helper::get_report_data($reportOptions)."';\n";
    //The html to place the data into using jQuery
    $htmlTemplate = "
    </br><div id='plot_report_panel'>
      </br>
      <h5>Details</h5>
      <div id='field ui-helper-clearfix'>
        <span><b>Square Type: </b></span><span id='square-type-value'></span></br>
        <span><b>Plot Type: </b></span><span id='plot-type-value'></span></br>
        <span><b>Plot Description: </b></span><span id='plot-description-value'></span></br>
        <span><b>Vice County: </b></span><span id='vice-county-value'></span></br>
        <span><b>Orientation: </b></span><span id='orientation-value'></span></br>
        <span><b>Aspect: </b></span><span id='aspect-value'></span></br>
        <span><b>Slope: </b></span><span id='slope-value'></span></br>
        <span><b>% Ash cover: </b></span><span id='ash-cover-value'></span></br>
      </div>
    </div></br>";
    //When the square or plot is changed or the page is loaded then get the data about the square/plot from reports and then 
    //place it into the mini report html template using jQuery.
    data_entry_helper::$javascript .= "
    $('#squares-select-list').ready(function() {
      loadMiniSquareReport();
    });
    $('#squares-select-list').change(function() {
      loadMiniSquareReport();
    });
    function loadMiniSquareReport() {
      var squareReportRequest = indiciaData.squareReportRequest
      + '&square_id=' + $('#squares-select-list').val()
      + '&core_square_location_type_id=' + ".$options['coreSquareLocationTypeId']."
      + '&callback=?';
      $.getJSON(squareReportRequest,
        null,
        function(response, textStatus, jqXHR) {
          if (response[0].type) {
            $('#square-type-value').text(response[0].type);
          } else {
            $('#square-type-value').text('');
          }
        }
      );
    }
    $('#imp-location').ready(function() {
      loadMiniPlotReport();
    });
    $('#imp-location').change(function() {
      loadMiniPlotReport();
    });
    function loadMiniPlotReport() {
      if ($('#imp-location').val()==='<Please select>') {
        $('#plot-type-value').text('');
        $('#plot-description-value').text('');
        $('#vice-county-value').text('');
        $('#orientation-value').text('');
        $('#aspect-value').text('');
        $('#slope-value').text('');
        $('#ash-value').text('');
      } else {
        var reportRequest = indiciaData.plotReportRequest
        + '&vice_county_name_attribute_id=' + ".$options['viceCountyLocationAttributeId']."
        + '&orientation_attribute_id=' + ".$options['orientationAttributeId']."
        + '&aspect_attribute_id=' + ".$options['aspectAttributeId']."
        + '&slope_attribute_id='+ ".$options['slopeAttributeId']."
        + '&ash_attribute_id=' + ".$options['ashAttributeId']."
        + '&plot_id='+ $('#imp-location').val() + '&callback=?';
        $.getJSON(reportRequest,
          null,
          function(response, textStatus, jqXHR) {
            $.each(response, function (idx, obj) {         
              if (obj.type) {
                $('#plot-type-value').text(obj.type);
              } else {
                $('#plot-type-value').text('');
              }
              if (obj.description) {
                $('#plot-description-value').text(obj.description);
              } else {
                $('#plot-description-value').text('');
              }
              if (obj.county) {
                $('#vice-county-value').text(obj.county);
              } else {
                $('#vice-county-value').text('');
              }
              if (obj.orientation) {
                $('#orientation-value').text(obj.orientation);
              } else {
                $('#orientation-value').text('');
              }
              if (obj.aspect) {
                $('#aspect-value').text(obj.aspect);
              } else {
                $('#aspect-value').text('');
              }
              if (obj.slope) {
                $('#slope-value').text(obj.slope);
              } else {
                $('#slope-value').text('');
              }
              if (obj.ash) {
                $('#ash-value').text(obj.ash);
              } else {
                $('#ash-value').text('');
              }
            });
          }
        );
      }
    }";
    
    return $htmlTemplate;
  }
  /*
   * When creating a plot, we need the plot location record to hold its parent square in location.parent_id.
   * To do this, the calling page provides the square id in the $_GET which we then place in a hidden field on the page to be 
   * processed during submission.
   * 
   */
  public static function insert_parent_square_id_into_location_record($auth, $args, $tabalias, $options, $path) {
    //Don't run the code unless the page in in add mode.
    if (!empty($_GET['parent_square_id'])) {
      //Save the hidden field for processing during submission
      $hiddenField = '<div>';
      $hiddenField  .= "  <INPUT TYPE=\"hidden\" VALUE=\"".$_GET['parent_square_id']." id=\"location:parent_id\" name=\"location:parent_id\">";
      $hiddenField  .= '</div></br>';
      return $hiddenField;
    }
  }
  
  /*
   * This function performs two tasks,
   * 1. In view mode (summary mode) it allows the page to be displayed with read-only data.
   * 2. When the user is creating a plot, it copies the grid reference of the plot into the location name field to be saved as the plot name.
   */
  public static function grid_ref_as_location_name_and_make_summary_mode($auth, $args, $tabalias, $options, $path) {
    iform_load_helpers(array('data_entry_helper'));
    global $indicia_templates;
    // put each param control in a div, this allows us to set the fields on the page to read-only when in view mode.
    $indicia_templates['prefix']='<div id="container-{fieldname}" class="param-container read-only-capable">';
    $indicia_templates['suffix']='</div>';
    //Hide the location name field as this will be auto-populated with the grid reference when the user submits
    data_entry_helper::$javascript .= "$('#container-location\\\\:name').hide();\n";
    data_entry_helper::$javascript .= "$('#entry_form').submit(function() { $('#location\\\\:name').val($('#imp-sref').val());});\n";
    //Make the page read-only in summary mode
    if (!empty($_GET['summary_mode']) && $_GET['summary_mode']==true) {
      data_entry_helper::$javascript .= "$('.read-only-capable').find('input, textarea, text, button, select').attr('disabled','disabled');\n"; 
      data_entry_helper::$javascript .= "$('.page-notice, .indicia-button').hide();\n"; 
    }
  }
  
  /*
   * When the plot details page is in edit/view mode we display a list of species recorded against the plot.
   */
  public static function known_taxa_summary($auth, $args, $tabalias, $options, $path) {
    if (!empty($_GET['location_id'])) {
      iform_load_helpers(array('report_helper'));
      return report_helper::report_grid(array(
        'id'=>'taxa-summary',
        'readAuth' => $auth['read'],
        'itemsPerPage'=>10,
        'dataSource'=>'library/taxa/filterable_explore_list',
        'rowId'=>'id',
        'ajax'=>true,
        'columns'=>array(array('fieldname'=>'taxon_group','visible'=>false),array('fieldname'=>'taxon_group_id','visible'=>false),
                      array('fieldname'=>'first_date','visible'=>false),array('fieldname'=>'last_date','visible'=>false)),
        'mode'=>'report',
        'extraParams'=>array(
            'location_list'=>$_GET['location_id'],
            'website_id'=>$args['website_id']),
      ));
    }
  }
  
  /*
   * When the plot details or square/user administration pages are displayed then we need to display the name of the square.
   * As the square display name is made from the name of the square plus its vice counties, then we need to collect this information from a report.
   */
  public static function get_square_name($auth, $args, $tabalias, $options, $path) {
    //The plot details page use's location_id as its parameter in edit mode
    if (!empty($_GET['location_id'])) {
      $reportOptions = array(
        'dataSource'=>'reports_for_prebuilt_forms/Splash/get_square_name_for_plot_id',
        'readAuth'=>$auth['read'],
        'extraParams' => array('website_id'=>$args['website_id'], 
            'vice_county_location_attribute_id'=>$options['viceCountyLocationAttributeId'], 
            'no_vice_county_found_message'=>$options['noViceCountyFoundMessage'],
            'plot_id'=>$_GET['location_id']),
        'valueField'=>'id',
        'captionField'=>'name'
      );
    }
    //The square/user admin page use's dynamic-location_id as its parameter. Only perform code for this 
    //page if this is present.
    //In add mode, the Plot Details page is given its parent square in the parent_square_id parameter, so use this to get the parent square name.
    if (!empty($_GET['dynamic-location_id'])||!empty($_GET['parent_square_id'])) {
      $reportOptions = array(
        'dataSource'=>'reports_for_prebuilt_forms/Splash/get_square_details_for_square_id',
        'readAuth'=>$auth['read'],
        'extraParams' => array('website_id'=>$args['website_id'], 
            'vice_county_location_attribute_id'=>$options['viceCountyLocationAttributeId'], 
            'no_vice_county_found_message'=>$options['noViceCountyFoundMessage']),
        'valueField'=>'id',
        'captionField'=>'name'
      );
      if (!empty($_GET['dynamic-location_id'])) 
        $reportOptions['extraParams']['square_id']= $_GET['dynamic-location_id'];
      if (!empty($_GET['parent_square_id'])) 
        $reportOptions['extraParams']['square_id']= $_GET['parent_square_id'];
    }
    
    if (!empty($reportOptions)) {
      $squareNameData = data_entry_helper::get_report_data($reportOptions);
      if (!empty($squareNameData[0]['name']))
        return '<div><label>Square name:</label>'.$squareNameData[0]['name'].'</div>';
    }
  }
  
  /*
   * When the user clicks on the map on the plot details page, for most plot types we calculate a plot square on the map where the south-west corner is the clicked point.
   * For two of the plot types, the user draws a free shape using the polygon drawing tool
   * $options Options array with the following possibilities:
   * <ul>
   * <li><b>squareSizes</b><br/>
   * The length of the plot square associated with each plot type. Mandatory. Comma seperated list in the following format,
   * plot_location_type_id|length_in_metres,plot_location_type_id|length_in_metres.....e.g. 2543|10,2544|10,2545|20,2546|20</li>
   * <li><b>freeDrawPlotTypeNames</b><br/>
   * Comma separated list of plot names for which the user will use the polygon drawing tool for (rather than an auto-generated square).
   * Note: for this option to work, the drawPolygon tool must be available on the map.</li>
   * </ul>
   * 
   */
  public static function draw_map_plot($auth, $args, $tabalias, $options, $path) {
    if (empty($options['squareSizes'])) {
      drupal_set_message('Please fill in the @squareSizes option for the draw_map_plot control');
      return '';
    }
    iform_load_helpers(array('map_helper')); 
    //Some Splash plot types use the polygon tool to draw the plot as any shape, specify the plot types and pass to Javascript.
    if (!empty($options['freeDrawPlotTypeNames']))
      map_helper::$javascript .= "indiciaData.freeDrawPlotTypeNames=".json_encode(explode(',',$options['freeDrawPlotTypeNames'])).";";
    //The user provides the square sizes associated with the various plot types as a comma seperated option list.
    $squareSizesOptionsSplit=explode(',',$options['squareSizes']);
    //Eash option consists of the following format <plot type id>|<square side lengh>
    //So these options need splitting into an array for use
    foreach ($squareSizesOptionsSplit as $squareSizeOption) {
      $squareSizeSingleOptionSplit = explode('|',$squareSizeOption);
      //The user can supply the options for the plot in two formats, either a square like this,
      //<location_type_id>|<plot side length>,<location_type_id>|<plot side length>...
      //Or a rectangle like this
      //<location_type_id>|<plot width>|<plot length>,<location_type_id>|<plot width>|<plot length>...
      //In code, both formats are treated the same way, if we find the is length missing then we know
      //all sides will be the same length (a square)
      if (empty($squareSizeSingleOptionSplit[2]))
        $squareSizesArray[$squareSizeSingleOptionSplit[0]]=array($squareSizeSingleOptionSplit[1],$squareSizeSingleOptionSplit[1]);
     else
        $squareSizesArray[$squareSizeSingleOptionSplit[0]]=array($squareSizeSingleOptionSplit[1],$squareSizeSingleOptionSplit[2]);
    }
    $squareSizesForJavascript=json_encode($squareSizesArray);
    map_helper::$javascript .= "indiciaData.squareSizes=$squareSizesForJavascript;\n";
    map_helper::$javascript .= "
    $(window).load(function() {
      setup_plot_type();
    });
    $('#location\\\\:location_type_id').change(function() {
       clear_map_features();
       setup_plot_type();
    });
    
    //When plot type is changed or screen is loaded then setup whether we are drawing square plots or manually drawing them to the screen.
    function setup_plot_type() {
      indiciaData.clickMiddleOfPlot=false;
      if ($('#location\\\\:location_type_id').val()) {
        indiciaData.plotWidthLength = indiciaData.squareSizes[$('#location\\\\:location_type_id').val()][0]+ ',' + indiciaData.squareSizes[$('#location\\\\:location_type_id').val()][1];
      }
      if ($('#location\\\\:location_type_id option:selected').text() && inArray($('#location\\\\:location_type_id option:selected').text(),indiciaData.freeDrawPlotTypeNames)) {  
        free_draw_plot_select();
      } else {
        square_draw_plot_select();
      }
    };
    
    function free_draw_plot_select() {
      show_polygon_line_tool(true);
      indiciaData.mapdiv.settings.clickForPlot=false;
      indiciaData.mapdiv.settings.click_zoom=false;  
    }
    
    function square_draw_plot_select() {
      show_polygon_line_tool(false);
      indiciaData.mapdiv.settings.clickForPlot=true;
      indiciaData.mapdiv.settings.click_zoom=true;  
    }
    
    //When doing things like changing plot type, we need to clear the map
    function clear_map_features() {
      var mapLayers = indiciaData.mapdiv.map.layers;
      for(var a = 0; a < mapLayers.length; a++ ){
        if (mapLayers[a].CLASS_NAME=='OpenLayers.Layer.Vector') {
          destroyAllMapFeatures(mapLayers[a], 'zoomToBoundary', true);
        }
      };
      $('#imp-boundary-geom').val('');
    }
    
    //Show polygon tool in manual draw mode, else hide it
    function show_polygon_line_tool(show) {
      if (show===true) {
        $('.olControlDrawFeaturePolygonItemActive').show();
        $('.olControlDrawFeaturePathItemActive').show();
        $('.olControlDrawFeaturePolygonItemInactive').show();
        $('.olControlDrawFeaturePathItemInactive').show();
      } else {
        $('.olControlDrawFeaturePolygonItemActive').hide();
        $('.olControlDrawFeaturePathItemActive').hide();
        $('.olControlDrawFeaturePolygonItemInactive').hide();
        $('.olControlDrawFeaturePathItemInactive').hide();
      }
     
      //Activate/deactivate the map icons when changing plot type. For instance if the draw tool is selected and then hidden,
      //it mkes sense to auto-select the point tool
      $.each(indiciaData.mapdiv.map.controls, function(idx, control) {
        if (control.CLASS_NAME==='OpenLayers.Control.DrawFeature'||control.CLASS_NAME==='OpenLayers.Control.Navigation') {
          control.deactivate();
        }
        if (control.CLASS_NAME==='OpenLayers.Control') {
          control.activate();
        }
      });
    }
    
    /*
     * Destroy features version of removeAllFeatures function. Once destroyed features cannot be added back to the layer.
     */
    function destroyAllMapFeatures(layer, type, inverse) {
      var toRemove = [];
      if (typeof inverse==='undefined') {
        inverse=false;
      }
      $.each(layer.features, function() {
        //Annotations is a special separate mode added after original code was written, so do not interfere with annotations even in inverse mode.
        if ((!inverse && this.attributes.type===type) || (inverse && this.attributes.type!==type && this.attributes.type!=='annotation')) {
          toRemove.push(this);
        }
      });
      layer.destroyFeatures(toRemove, {});
    }

    //Javascript doesn't have inArray, so write our own function
    function inArray(needle, haystack) {
      var length = haystack.length;
      for(var i = 0; i < length; i++) {
          if(haystack[i] == needle) return true;
      }
      return false;
    };\n";
    //Do not allow submission if there is no plot set
    data_entry_helper::$javascript .= "$('#entry_form').submit(function() { if (!$('#imp-boundary-geom').val()) {alert('Please use the map control to create a plot before continuing.'); return false; }});\n";
  }
}
?>