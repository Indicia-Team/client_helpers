var private_plots_set_precision,clear_map_features, plot_type_dropdown_change, limit_to_post_code,context_sensitive_instructions;

jQuery(document).ready(function ($) {
  //If the use selects a private plot, then we need to set the privacy precision
  //Switch off input form Occurrence Sensitivity option as this may end up overriding this code.
  private_plots_set_precision = function (privatePlots) {
    $("#tab-submit").click(function() {
      if (inArray($("#imp-location").val(),privatePlots)) {
        $('#entry_form').append('<input name="sample:privacy_precision" value="10000" type="hidden" />');
      } else {
        $('#entry_form').append('<input name="sample:privacy_precision" value="" type="hidden" />');  
      }
    });
  }
  
  clear_map_features = function clear_map_features() {
    var mapLayers = indiciaData.mapdiv.map.layers;
    for(var a = 0; a < mapLayers.length; a++ ){
      if (mapLayers[a].CLASS_NAME=='OpenLayers.Layer.Vector') {
        destroyAllFeatures(mapLayers[a], 'zoomToBoundary', true);
      }
    };
    $('#imp-boundary-geom').val('');
  }
 
  plot_type_dropdown_change = function plot_type_dropdown_change() {
    //In simple mode we don't draw a proper plot, the plot is just represented by a pre-defined (non-rotatable) square on the screen
    var simpleModePointSize=4;
    //In NPMS simple (not enahanced mode) the admin user can define a comma separated list
    //of location attributes to hide from view.
    if (indiciaData.hideLocationAttrsInSimpleMode) {
      if ($('#locAttr\\:'+indiciaData.enhancedModeCheckboxAttrId).length&&
          indiciaData.enhancedModeCheckboxAttrId&&!$('#locAttr\\:'+indiciaData.enhancedModeCheckboxAttrId).is(':checked')) { 
        $.each(indiciaData.hideLocationAttrsInSimpleMode.split(','), function(idx, attrToHide) {
          $('#container-locAttr\\:'+attrToHide).hide();
        });
      } else {
        $.each(indiciaData.hideLocationAttrsInSimpleMode.split(','), function(idx, attrToHide) {
          $('#container-locAttr\\:'+attrToHide).show();
        });
      }
    }
    indiciaData.clickMiddleOfPlot=false;
    //Some plot types use a free drawn polygon/Line as the plot.
    if (inArray($('#location\\:location_type_id option:selected').text(),indiciaData.freeDrawPlotTypeNames)) {     
      free_draw_plot_select(simpleModePointSize);
    } else {
      other_plot_select(simpleModePointSize);
    }
  }
 
  /*
   * What happens when a plot type is selected that needs free drawing onto the map
   */
  function free_draw_plot_select(simpleModePointSize) {
    free_draw_plot_additional_box_display();
    if (!$('#locAttr\\:'+indiciaData.enhancedModeCheckboxAttrId).length||$('#locAttr\\:'+indiciaData.enhancedModeCheckboxAttrId).is(':checked')) {
      show_polygon_line_tool(true);
      //If using drawPolygon/Line in enhanced mode then we don't draw a plot automatically
      indiciaData.mapdiv.settings.clickForPlot=false;
      indiciaData.mapdiv.settings.click_zoom=false;  
    } else {
      //Otherwise deactivate and hide the line/polygon tool and then select the map point clicking tool.
      show_polygon_line_tool(false);
      $.each(indiciaData.mapdiv.map.controls, function(idx, control) {
        if (control.CLASS_NAME==='OpenLayers.Control.DrawFeature'||control.CLASS_NAME==='OpenLayers.Control.Navigation') {
          control.deactivate();
        }
        if (control.CLASS_NAME==='OpenLayers.Control') {
          control.activate();
        }
      });
      //In linear mode, default to using a big plot point so people can actually see it in simple mode
      $('#locAttr\\:'+indiciaData.plotWidthAttrId).val(simpleModePointSize);
      $('#locAttr\\:'+indiciaData.plotLengthAttrId).val(simpleModePointSize);
      indiciaData.mapdiv.settings.clickForPlot=true;
      indiciaData.mapdiv.settings.click_zoom=true;  
      indiciaData.mapdiv.settings.noPlotRotation=true;
    }
  }
  
  /*
   * What happens when a plot type is selected that doesn't need free drawing onto the map, such as a square plot which has its plot automatically calculated.
   */
  function other_plot_select(simpleModePointSize) {
    other_plot_additional_box_display();
    //remove the drawPolygon/Line tool
    show_polygon_line_tool(false);
    //For some plot types the width and length used be be adjusted manually, fill in these fields if they exist. The engine of how this works is still present in case we need to go back.
    //So the attributes are still defaulted on the page, however these are currently hidden by default so can't be changed for now.
    if ($('#locAttr\\:'+indiciaData.plotWidthAttrId).length&&(!$('#locAttr\\:'+indiciaData.enhancedModeCheckboxAttrId).length||$('#locAttr\\:'+indiciaData.enhancedModeCheckboxAttrId).is(':checked'))) {
      if ($('#location\\:location_type_id').val()) {
        $('#locAttr\\:'+indiciaData.plotWidthAttrId).val(indiciaData.squareSizes[$('#location\\:location_type_id').val()][0]);
      }
      indiciaData.mapdiv.settings.noPlotRotation=false;
    } else {
      $('#locAttr\\:'+indiciaData.plotWidthAttrId).val(simpleModePointSize);
      indiciaData.mapdiv.settings.noPlotRotation=true;
    }
    if ($('#locAttr\\:'+indiciaData.plotLengthAttrId).length&&(!$('#locAttr\\:'+indiciaData.enhancedModeCheckboxAttrId).length||$('#locAttr\\:'+indiciaData.enhancedModeCheckboxAttrId).is(':checked'))) {
      if ($('#location\\:location_type_id').val()) {
        $('#locAttr\\:'+indiciaData.plotLengthAttrId).val(indiciaData.squareSizes[$('#location\\:location_type_id').val()][1]);
      }
      indiciaData.mapdiv.settings.noPlotRotation=false;
    } else {
      $('#locAttr\\:'+indiciaData.plotLengthAttrId).val(simpleModePointSize);
      indiciaData.mapdiv.settings.noPlotRotation=true;
    }
    //In non-enhanced mode in PSS mode, plots are always a set size non-rotatable square 
    //In PSS enhanced mode, their size can be configured manually on the page
    indiciaData.mapdiv.settings.clickForPlot=true;
    indiciaData.mapdiv.settings.click_zoom=true;
    $.each(indiciaData.mapdiv.map.controls, function(idx, control) {
      if (control.CLASS_NAME==='OpenLayers.Control.DrawFeature'||control.CLASS_NAME==='OpenLayers.Control.Navigation') {
        control.deactivate();
      }
      if (control.CLASS_NAME==='OpenLayers.Control') {
        control.activate();
      }
    });
    //Only PSS (NPMS) square plots should be drawn so the click point is in the middle of the plot, otherwise the south-west corner is used.
    //Check that the word linear or vertical does not appear in the selected plot type when setting the clickMiddleOfPlot option.
    if (indiciaData.pssMode
        && ($('#location\\:location_type_id option:selected').text().toLowerCase().indexOf('linear')===-1)
        && ($('#location\\:location_type_id option:selected').text().toLowerCase().indexOf('vertical')===-1)) {
      //Rectangular PSS plots have the grid reference in the middle of the plot
      indiciaData.clickMiddleOfPlot=true;
    }
    //Splash plots get their rectangle sizes from user configurable options which are not displayed on screen (NPMS used to allow configuration of these on screen, however they are now hidden,
    //although the attributes remain on screen in case we need to go back.
    if (!indiciaData.pssMode)
      indiciaData.plotWidthLength = indiciaData.squareSizes[$('#location\\:location_type_id').val()][0]+ ',' + indiciaData.squareSizes[$('#location\\:location_type_id').val()][1];  
  }
  
  /*
   * In free draw plot draw mode we need to show two extra boxes to enter linear grid references and hide the south-west corner box
   */
  function free_draw_plot_additional_box_display() {
    //When use linear (free draw) plots, we have two extra boxes to fill if for the start and end grid references
    //of the plot which are not otherwise saved because it is free draw.
    //Applies to both "normal" and enhanced modes.
    if (indiciaData.linearGridRef1 && indiciaData.linearGridRef2) {
      $('[id^=\"container-locAttr\\:'+indiciaData.linearGridRef1+'\"]').show();
      $('[id^=\"container-locAttr\\:'+indiciaData.linearGridRef2+'\"]').show();
    }
    //If using a linear plot, then hide the SW Corner for square plots.
    if (indiciaData.swGridRef) {         
      $('#locAttr\\:'+indiciaData.swGridRef).val('');
      $('[id^=\"container-locAttr\\:'+indiciaData.swGridRef+'\"]').hide();
    }
  }
  
  /*
   * In non-free draw plot draw mode we need to hide two extra boxes to enter linear grid references and show the south-west corner box
   */
  function other_plot_additional_box_display() {
    //Don't display the two extra boxes for entering linear plot start and end grid references when not in linear (free draw) mode.
    if (indiciaData.linearGridRef1 && indiciaData.linearGridRef2) {         
      $('#locAttr\\:'+indiciaData.linearGridRef1).val('');
      $('#locAttr\\:'+indiciaData.linearGridRef2).val('');
      $('[id^=\"container-locAttr\\:'+indiciaData.linearGridRef1+'\"]').hide();
      $('[id^=\"container-locAttr\\:'+indiciaData.linearGridRef2+'\"]').hide();
    }
    //When when a non-linear (square) plot is selected, we want to display an extra box for filling in the south-west corner.
    //However make sure this is not displayed when no plot type is selected at all.
    if (indiciaData.swGridRef && $('#location\\:location_type_id').val()) {
      $('[id^=\"container-locAttr\\:'+indiciaData.swGridRef+'\"]').show();
    } else {
      $('[id^=\"container-locAttr\\:'+indiciaData.swGridRef+'\"]').hide();
    }
  }
  
  /*
   * The polygon/line tool on the map needs showing or hiding depending on the plot type selected.
   */
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
  }
 
  //Return all squares instead of doing a post code search
  return_all_squares= function (indiciaUserId) {
    var url = window.location.href.toString().split('?');
    var params = '?';
    //If we are only returning squares for a single user.
    if (indiciaUserId!=0) {
      params+="dynamic-the_user_id="+indiciaUserId+'&';
    }
    params+="dynamic-return_all_squares=true";
    url[0] += params;
    //Reload screen and submit
    window.location=url[0];
    window.location.href;
    $('#entry_form').submit();
  }

  //Function allows the report to only return squares located a certain distance from a user's
  //post code.
  limit_to_post_code= function (postcode,georeferenceProxy,indiciaUserId,postCodeRequestIssueWarning) {
    if (!postcode) {
      alert('Please enter a post code to search for.');
      return false;
    }
    if (!$('#limit-value').val()) {
      alert('Please enter the number of miles you wish to search for from the post code.');
      return false;
    }
    if ($('#limit-value').val()>30) {
      alert('Please enter a maximum of 30 miles to search squares for');
      return false;
    }
    $.ajax({
      dataType: 'json',
      url: georeferenceProxy,
      data: {'url':'https://maps.googleapis.com/maps/api/place/textsearch/json','key':indiciaData.google_api_key, 'query':postcode, 'sensor':'false'},
      success: function(data) {
        var done=false;
        $.each(data.results, function() {  
          if ($.inArray('postal_code', this.types)!==-1) {
            done=true;
            return false;
          }
        });
        if (!done) {
          alert(postCodeRequestIssueWarning);
          return false;
        }
        
        //Only provide one corner of the Post Code area for the report as this
        //simplifies things and doesn't adversely affect functionality
        var southWest = OpenLayers.Layer.SphericalMercator.forwardMercator(data.results[0].geometry.viewport.southwest.lng,data.results[0].geometry.viewport.southwest.lat);
        var postCodePoint = 'POINT('+southWest.lon+' '+southWest.lat+')';
        //Get current URL
        var url = window.location.href.toString().split('?');
        var params = '?';
        if (indiciaUserId!=0) {
          params+="dynamic-the_user_id="+indiciaUserId+'&';
        }
        if (postCodePoint && $('#limit-value').val()) {        
          params+="dynamic-post_code_geom="+postCodePoint+'&';
          params+="dynamic-distance_from_post_code="+($('#limit-value').val()*1609)+'&';
        }
        //Need the post code and mieage in the url params so when the page reloads we can set the mileage and post code fields so they don't need to be re-entered.
        params += "post_code=" + postcode;
        params += "&mileage=" + $('#limit-value').val();
        //url[0] is the part of the url excluding parameters
        url[0] += params; 
        //Reload screen and submit
        window.location=url[0];
        window.location.href;
        $('#entry_form').submit();
      }
    });
  }
  
  /*
   * Show instructions depending on on what options are selected.  
   */
  context_sensitive_instructions=function() {
    //Only show Expert Mode help when that checkbox is selected.
    //Only show Expert Mode Linear Plot help if Expert Mode is selected, and the Lineaer Plot Type is selected.
    if ($('#locAttr\\:'+indiciaData.expertModeAttrId).is(':checked')) {      
      $('.expert-help').show();
      if ($('#location\\:location_type_id').val() == indiciaData.linearLocationTypeId) {
        $('.linear-expert-help').show();
      } else {
        $('.linear-expert-help').hide();
      }
    } else {
      $('.expert-help').hide();
      $('.linear-expert-help').hide();
    }
  }
    
  /*
   * Returns true if an item is found in an array
   */
  function inArray(needle, haystack) {
      var length = haystack.length;
      for(var i = 0; i < length; i++) {
          if(haystack[i] == needle) return true;
      }
      return false;
  }

  /**
   * Code for misc_extensions.query_locations_on_map_click control.
   */
  if (indiciaData.queryLocationsOnMapClickSettings) {
    mapSettingsHooks.push(function(opts) {
      opts.hintQueryDataPointsTool = 'Select this tool and click on individual squares to display information about the square, or to request the square';
      opts.hintClickSpatialRefTool = 'Select this tool to enable clicking on the map to display locational information (e.g. National Parks and National Nature Reserves)';
    });
    // Attach handler when map clicked on.
    mapClickForSpatialRefHooks.push(function onMapClick(clickInfo) {
      var reportingURL = indiciaData.read.url + 'index.php/services/report/requestReport' +
        '?report=library/locations/locations_list_3.xml&callback=?';
      var reportOptions = {
        mode: 'json',
        nonce: indiciaData.read.nonce,
        auth_token: indiciaData.read.auth_token,
        reportSource: 'local',
        intersects: clickInfo.mapwkt
      };
      $.each(indiciaData.queryLocationsOnMapClickSettings, function eachLocationType(id, data) {
        reportOptions.location_type_ids = data.locationTypeIds.join(',');
        // Find locations that intersect a click point.
        $.getJSON(reportingURL, reportOptions,
          function success(locations) {
            var output = [];
            var templateArray = [];
            // For each location in response, build HTML.
            $.each(locations, function eachLocation() {
              var template = data.template;
              $.each(this, function eachField(field, value) {
                // Empty values.
                if (value === null) {
                  value = '';
                }
                template = template.replace(new RegExp('{{ ' + field + ' }}', 'g'), value);
                if (!inArray(template,templateArray)) {
                  templateArray.push(template);
                }
              });
            });
            //Firstly sort the list of locations
            templateArray.sort();
            var previousFieldNameWithSpace = '';
            var fieldNameWithSpace = '';
            var templateArrayOptimised = [];
            var templateArrayOptimisedStringBuilder = '';
            // Cycle through each returned location
            for (var i = 0; i < templateArray.length; i++) {
              // Get the type of the location by getting everything before the colon
              fieldNameWithSpace = templateArray[i].substr(0,templateArray[i].indexOf(':'));
              // If the location is the same type as previous location, then add it on the add of the previous location
              // with a comma, don't need to display the type again so remove it
              if (fieldNameWithSpace === previousFieldNameWithSpace) {
                templateArrayOptimisedStringBuilder = templateArrayOptimisedStringBuilder + ', ' + templateArray[i].replace(fieldNameWithSpace + ':', '');
              } else {                 
                // If the type is different to the previous location
                // then push the previous location type ready for display, then add the current
                // location complete with type to the string builder (ready for more locations to be added as needed)
                templateArrayOptimised.push(templateArrayOptimisedStringBuilder);
                templateArrayOptimisedStringBuilder = templateArray[i];           
              }
              // If have reached the last location then we need to push it for display immediately as there is nothing 
              //  else to examine
              if (i === (templateArray.length - 1) && fieldNameWithSpace !== previousFieldNameWithSpace) {  
                templateArrayOptimised.push(templateArrayOptimisedStringBuilder); 
              }
              // Save the location type we just worked with so it can be compared with the next one
              previousFieldNameWithSpace = fieldNameWithSpace;
            } 
            // Output final result
            if (templateArrayOptimised && templateArrayOptimised.length > 0) {
              alert(templateArrayOptimised.join('\n'));
            }
          }
        );
      });
    });
  }
});