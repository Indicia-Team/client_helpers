var clearSection, loadSectionDetails, confirmSelectSection, selectSection, syncPost, deleteWalks,
    deleteLocation, deleteSections, deleteSection, updateTransectDetails, countryChange;
var defaultLayers = null,
    defaultRouteLayers = null;

(function ($) {
/*
 * The following functions are utility functions within this file..
 */
  syncPost = function(url, data) {
    $.ajax({
      type: 'POST',
      url: url,
      data: data,
      success: function(data) {
        if (typeof(data.error)!=="undefined") {
          alert(data.error);
        }},
      dataType: 'json',
      async: false // cannot be asynchronous otherwise we navigate away from the page too early
    });
  };

clearSection = function() {
  $('#section-location-id').val('');
  $('#section-location-sref').val('');
  $('#section-location-system,#section-location-system-select').val('');
  // remove exiting errors:
  $('#section-form').find('.inline-error').remove();
  $('#section-form').find('.ui-state-error').removeClass('ui-state-error');
  var nameparts;
  // loop through form controls to make sure they do not have the value id (as these will be new values)
  $.each($('#section-form').find(':input[name]'), function(idx, ctrl) {
    nameparts = $(ctrl).attr('name').split(':');
    if (nameparts[0]==='locAttr') {
      if (nameparts.length===3) {
        $(ctrl).attr('name', nameparts[0] + ':' + nameparts[1]);
      }
      // clear the control's value
      if ($(ctrl).is(':checkbox')) {
          $(ctrl).attr('checked', false);
      } else {
          $(ctrl).val('');
      }
    } else if ($(ctrl).hasClass('hierarchy-select')) {
      $(ctrl).val('');
    }
  });
};
/*
 * This only loads the section data details: this is the third tab, it does not deal 
 * with the route tab.
 */
loadSectionDetails = function(section) {
  clearSection();
  if (typeof indiciaData.sections[section]!=="undefined") { // previously existing section.
    $('#section-details-tab').show();
    $('.complete-route-details').removeAttr('disabled');
    $('#section-location-id').val(indiciaData.sections[section].id);
    // if the systems on the section and main location do not match, copy the the system and sref from the main site.
    if(indiciaData.sections[section].system !== $('#imp-sref-system').val()) {
        $('#section-location-sref').val($('#imp-sref').val());
        $('#section-location-system,#section-location-system-select').val($('#imp-sref-system').val());
    } else {
        $('#section-location-sref').val(indiciaData.sections[section].sref);
        $('#section-location-system,#section-location-system-select').val(indiciaData.sections[section].system);
    }
    $.getJSON(indiciaData.indiciaSvc + "index.php/services/data/location_attribute_value?location_id=" + indiciaData.sections[section].id +
        "&mode=json&view=list&callback=?&auth_token=" + indiciaData.readAuth.auth_token + "&nonce=" + indiciaData.readAuth.nonce, 
        function(data) {
          var attrname;
          $.each(data, function(idx, attr) {
            attrname = 'locAttr:'+attr.location_attribute_id;
            if (attr.id!==null) {
              attrname += ':'+attr.id;
            }
            // special handling for checking radios
            if ($('input:radio#locAttr\\:'+attr.location_attribute_id+'\\:0').length>0) {
              var radioidx=0;
              // name the radios with the existing value id
              while ($('#section-form #locAttr\\:'+attr.location_attribute_id+'\\:'+radioidx).length>0) {
                $('#section-form #locAttr\\:'+attr.location_attribute_id+'\\:'+radioidx).attr('name',attrname);
                radioidx++;
              }
              radioidx=0;
              // check the correct radio
              while ($('#section-form #locAttr\\:'+attr.location_attribute_id+'\\:'+radioidx).length>0 && 
                  $('#section-form #locAttr\\:'+attr.location_attribute_id+'\\:'+radioidx).val()!==attr.raw_value) {
                radioidx++;
              }
              if ($('#section-form #locAttr\\:'+attr.location_attribute_id+'\\:'+radioidx).length>0 && 
                  $('#section-form #locAttr\\:'+attr.location_attribute_id+'\\:'+radioidx).val()===attr.raw_value) {
                $('#section-form #locAttr\\:'+attr.location_attribute_id+'\\:'+radioidx).attr('checked', true);
              }
            } else if ($('#section-form #fld-locAttr\\:'+attr.location_attribute_id).length>0) {
              // a hierarchy select outputs a fld control, which needs a special case
              $('#section-form #fld-locAttr\\:'+attr.location_attribute_id).val(attr.raw_value);              
              $('#section-form #fld-locAttr\\:'+attr.location_attribute_id).attr('name',attrname);
              // check the option is already in the drop down.
              if ($('#section-form #locAttr\\:'+attr.location_attribute_id + " option[value='"+attr.raw_value+"']").length===0) {
                // no - we'll just put it in at the top level
                // @todo - NTH should really now fetch the top level in the hierarchy then select that.
                $('#section-form #locAttr\\:'+attr.location_attribute_id).append('<option value="' + 
                    attr.raw_value + '">' + attr.value + '</option>');
              }
              $('#section-form #locAttr\\:'+attr.location_attribute_id).val(attr.raw_value);
            } else {              
              $('#section-form #locAttr\\:'+attr.location_attribute_id).val(attr.raw_value);              
              $('#section-form #locAttr\\:'+attr.location_attribute_id).attr('name',attrname);
            }
          });
        }
    );
  } else {
	  $('#section-details-tab').hide();
	  $('.complete-route-details').attr('disabled','disabled');
  }
};

updateTransectDetails = function(newNumSections) {
  var transectLen = 0;
  var numSections = parseInt($('[name='+indiciaData.settings.numSectionsAttr.replace(/:/g,'\\:')+']').val(),10);
  var ldata = {'location:id':$('#location-id').val(), 'website_id':indiciaData.website_id};
  var save = (newNumSections !== false);
  
  if (typeof indiciaData.autocalcTransectLengthAttrId != 'undefined' &&
		indiciaData.autocalcTransectLengthAttrId &&
		indiciaData.autocalcSectionLengthAttrId) {
	// add all sections lengths together
	for(var i = 1; i <= numSections; i++){
		if(typeof indiciaData.sections['S'+i] !== "undefined" && typeof indiciaData.sections['S'+i].sectionLen !== "undefined"){
			transectLen += indiciaData.sections['S'+i].sectionLen;
		}
	}
	// load into form.
    $('#locAttr\\:'+indiciaData.autocalcTransectLengthAttrId).val(transectLen);
    ldata[indiciaData.autocalcTransectLengthAttrName] = ''+transectLen;
    save = true;
  }

  if(newNumSections !== false)
    ldata[indiciaData.settings.numSectionsAttr] = ''+newNumSections;
  
  if(save)
	  syncPost(indiciaData.ajaxFormPostUrl, ldata);
  
  if(newNumSections !== false) {
	window.onbeforeunload = null;
	setTimeout(function(){
		  window.location.reload(true);
	});
  }
}

/*
 * Check if any route changes or details changes have been saved before allowing 
 * a new section to be selected.
 */
confirmSelectSection = function(section, doFeature, withCancel) {
  // continue to save if the route is either unchanged or user says no.
  if(typeof indiciaData.modifyFeature !== "undefined" &&
		  indiciaData.modifyFeature.active && indiciaData.modifyFeature.modified)
	  indiciaData.routeChanged = true;
  if(indiciaData.routeChanged === true) {
    var buttons =  { 
        "No":  function() { 
        	// replace the route with the previous one for this section.
        	// At his point, indiciaData.currentSection should point to existing, previously selected section.
        	var removeSections = [], oldSection = [], div = $('#route-map'), geom;
        	$(this).dialog('close');
        	div = div[0];
    		if(typeof indiciaData.modifyFeature !== "undefined")
    		    indiciaData.modifyFeature.deactivate();
    		if(typeof indiciaData.drawFeature !== "undefined")
    		    indiciaData.drawFeature.deactivate();
		    indiciaData.navControl.activate(); // Nav control always exists
        	$.each(div.map.editLayer.features, function(idx, feature) {
        		if (feature.attributes.section===indiciaData.currentSection) {
        			removeSections.push(feature);
        		}
        	});
        	if (removeSections.length>0) {
        		div.map.editLayer.removeFeatures(removeSections, {});
        	}
        	if(typeof indiciaData.sections[indiciaData.currentSection] !== 'undefined') {
              // .geom is stored in 3857: convert to map projection.
              geom = OpenLayers.Geometry.fromWKT(indiciaData.sections[indiciaData.currentSection].geom);
              if (indiciaData.mapdiv.map.projection.projCode!='EPSG:900913' && indiciaData.mapdiv.map.projection.projCode!='EPSG:3857') { 
                geom = geom.transform(new OpenLayers.Projection('EPSG:3857'), indiciaData.mapdiv.map.projection);
              }
              oldSection.push(new OpenLayers.Feature.Vector(geom, {section:indiciaData.currentSection, type:"boundary"}));
              div.map.editLayer.addFeatures(oldSection);
        	} // dont worry about selection.
        	indiciaData.routeChanged = false;
        	checkIfSectionChanged(section, doFeature, withCancel);
        },
        "Yes": function() { $(this).dialog('close');
        	saveRoute();
        	indiciaData.routeChanged = false;
        	checkIfSectionChanged(section, doFeature, withCancel);
        } // synchronous for bit that matters. 
    };
    if(withCancel) {
      buttons.Cancel = function() { $(this).dialog('close'); };
    }
    // display dialog and drive from its button events.
    var dialog = $('<p>'+(withCancel ? indiciaData.routeChangeConfirmCancel : indiciaData.routeChangeConfirm) +'</p>').dialog({ title: "Save Route Data?", buttons: buttons });
  } else {
	  checkIfSectionChanged(section, doFeature, withCancel);
  }
};

checkIfSectionChanged = function(section, doFeature, withCancel) {
  if(indiciaData.sectionDetailsChanged === true) {
    var buttons =  { 
        "Yes": function() { $(this).dialog('close');
        	$('#section-form').submit();
        	selectSection(section, doFeature);
        },
        "No":  function() { $(this).dialog('close');
        	selectSection(section, doFeature); }};
    if(withCancel) {
      buttons.Cancel = function() { $(this).dialog('close'); };
    }
    // display dialog and drive from its button events.
    var dialog = $('<p>'+(withCancel ? indiciaData.sectionChangeConfirmCancel : indiciaData.sectionChangeConfirm) +'</p>').dialog({ title: "Save Section Details?", buttons: buttons });
  } else {
    selectSection(section, doFeature);
  }
};

selectSection = function(section, doFeature) {
  /*
   * If doFeature is true, then we need to select the feature on the route map.
   * This will trigger a featureSelected event which then calls this function again with the doFeature false.
   * We let this second call change the data, being careful about recursion.
   * At this point the data has all been saved. routeChanged and sectionDetailsChanged are set in save functions
   */
  $('.section-select li').removeClass('selected');
  $('#section-select-route-'+section).addClass('selected');
  $('#section-select-'+section).addClass('selected');
  var oldEnableSelectReload = indiciaData.enableSelectReload;
  indiciaData.enableSelectReload = false; // don't want to recurse when the feature is selected.
  if(doFeature){
	// Deactivate the controls if active and unselect previous route feature.
	if (typeof indiciaData.mapdiv !== "undefined") {
      indiciaData.navControl.deactivate(); // Nav control always exists
      if(typeof indiciaData.modifyFeature !== "undefined")
    	  indiciaData.modifyFeature.deactivate();
      if(typeof indiciaData.drawFeature !== "undefined")
    	  indiciaData.drawFeature.deactivate();
      if(typeof indiciaData.selectFeature !== "undefined") {
    	  indiciaData.selectFeature.deactivate();
    	  indiciaData.selectFeature.unselectAll();
      }
      indiciaData.currentFeature = null;
	  // if we click a new route: no feature yet, also no data!
	  $.each(indiciaData.mapdiv.map.editLayer.features, function(idx, feature) {
	      if (feature.attributes.section===section) {
	    	  indiciaData.currentFeature = feature;
	      }
	  });
	  if (indiciaData.currentFeature === null) {
		  if(typeof indiciaData.drawFeature !== "undefined")
	    	  indiciaData.drawFeature.activate();
	  } else {
		  if(typeof indiciaData.modifyFeature !== "undefined")
	    	  indiciaData.modifyFeature.activate();
		  if(typeof indiciaData.selectFeature !== "undefined")
	    	  indiciaData.selectFeature.select(indiciaData.currentFeature);
	  }
    }
  } else { // doFeature = false implies that this has come from a map event. i.e. select of feature using select control
    // Deactivate the controls if active and unselect previous route feature.
    if (typeof indiciaData.mapdiv !== "undefined") {
    	  if(typeof indiciaData.selectFeature !== "undefined")
	    	  indiciaData.selectFeature.deactivate();
		  indiciaData.currentFeature = null;
		  $.each(indiciaData.mapdiv.map.editLayer.features, function(idx, feature) {
		      if (feature.attributes.section===section) {
		    	  indiciaData.currentFeature = feature;
		      }
		  });
		  if(typeof indiciaData.modifyFeature !== "undefined")
	    	  indiciaData.modifyFeature.activate();
	}
  }
  indiciaData.routeChanged = false;
  indiciaData.enableSelectReload = oldEnableSelectReload;
  if (indiciaData.currentSection!==section) {
    indiciaData.sectionDetailsChanged = false;
    loadSectionDetails(section);
    indiciaData.currentSection=section;
  }
};

deleteWalks = function(walkIDs) {
  $.each(walkIDs, function(i, walkID) {
    $('#delete-transect').html('Deleting Walks ' + (Math.round(i/walkIDs.length*100)+'%'));
    var data = {
      'sample:id':walkID,
      'sample:deleted':'t',
      'website_id':indiciaData.website_id
    };
    syncPost(indiciaData.ajaxFormPostSampleUrl, data);
  });
  $('#delete-transect').html('Deleting Walks 100%');
};

deleteLocation = function(ID) {
  var data = {
    'location:id':ID,
    'location:deleted':'t',
    'website_id':indiciaData.website_id
  };
  syncPost(indiciaData.ajaxFormPostUrl, data);
};

// delete a set of sections. Does not re-index the other section codes.
deleteSections = function(sectionIDs) {
  $.each(sectionIDs, function(i, sectionID) {
    $('#delete-transect').html('Deleting Sections ' + (Math.round(i/sectionIDs.length*100)+'%'));
    deleteLocation(sectionID);
  });
  $('#delete-transect').html('Deleting Sections 100%');
};

//delete a section
deleteSection = function(section) {
  var data;
  // section comes in like "S1"
  // TODO NTH Add progress bar
  $('<p>Please wait whilst the section and its walk data are removed, the subsequent sections renumbered, and the number of sections counter on the Transect changed. The page will reload automatically when complete.</p>').dialog({ title: "Please Wait",
	  	buttons: {"OK": function() {
	  		$( this ).dialog('close');}}});

  $('.remove-section').addClass('waiting-button');
  // if it has been saved, delete any subsamples lodged against it.
  if(typeof indiciaData.sections[section] !== "undefined"){
    $.getJSON(indiciaData.indiciaSvc + "index.php/services/data/sample?location_id=" + indiciaData.sections[section].id +
            "&mode=json&view=detail&callback=?&auth_token=" + indiciaData.readAuth.auth_token + "&nonce=" + indiciaData.readAuth.nonce, 
      function(sdata) {
        if (typeof sdata.error==="undefined") {
          $.each(sdata, function(idx, sample) {
            var postData = {'sample:id':sample.id,'sample:deleted':'t','website_id':indiciaData.website_id};
            syncPost(indiciaData.ajaxFormPostSampleUrl, postData);
          });
          // The getJSON is async: need all getjson to have returned list before we delete the location, otherwise DB
          // location delete trigger may cause havoc with the samples query. When a location is deleted its samples
          // are orphaned but undeleted.
          deleteLocation(indiciaData.sections[section].id);
        }
      }
    );
    indiciaData.sections[section].sectionLen = 0;
  }
  // loop through all the subsections with a greater section number
  // subsamples are attached to the location and parent, but the location_name is not filled in, so don't need to change that
  // Update the code and the name for the locations.
  // Note that the subsections may not have been saved, so may not exist.
  var numSections = parseInt($('[name='+indiciaData.settings.numSectionsAttr.replace(/:/g,'\\:')+']').val(),10);
  for(var i = parseInt(section.substr(1))+1; i <= numSections; i++){
	// don't need to update the indiciaData as form will be reloaded.
    if(typeof indiciaData.sections['S'+i] !== "undefined"){
      data = {'location:id':indiciaData.sections['S'+i].id,
                  'location:code':'S'+(i-1),
                  'location:name':$('#location-name').val() + ' - ' + 'S'+(i-1),
                  'website_id':indiciaData.website_id};
      syncPost(indiciaData.ajaxFormPostUrl, data);
    }
  }
  // update the attribute value for number of sections.
  // and finally update the total transect length on the transect.
  // reload the form when all ajax done. This will reload the new section list and total transect length into the form
  updateTransectDetails(numSections-1);
};

//insert a section
insertSection = function(section) {
  var data;
  // section comes in like "S1"
  // TODO NTH Add progress bar
  $('<p>Please wait whilst the subsequent sections are renumbered, and the number of sections counter on the Transect changed. The page will reload automatically when complete.</p>').dialog({ title: "Please Wait",
	  	buttons: {"OK": function() { $(this).dialog('close');}}});
  $('.insert-section').addClass('waiting-button');
  // loop through all the subsections with a greater section number
  // subsamples are attached to the location and parent, but the location_name is not filled in, so don't need to change that
  // Update the code and the name for the locations.
  // Note that the subsections may not have been saved, so may not exist.
  var numSections = parseInt($('[name='+indiciaData.settings.numSectionsAttr.replace(/:/g,'\\:')+']').val(),10);
  for(var i = parseInt(section.substr(1))+1; i <= numSections; i++){
    if(typeof indiciaData.sections['S'+i] !== "undefined"){
      data = {'location:id':indiciaData.sections['S'+i].id,
                  'location:code':'S'+(i+1),
                  'location:name':$('#location-name').val() + ' - ' + 'S'+(i+1),
                  'website_id':indiciaData.website_id};
      syncPost(indiciaData.ajaxFormPostUrl, data);
    }
  }
  updateTransectDetails(numSections+1);
};

var saveRouteDialog;

saveRoute = function() {
    var current, oldSection = [], saveRouteDialogText, geom = indiciaData.currentFeature.geometry.clone();
	// This saves the currently selected route aginst the currently selected section.
	$('.save-route').addClass('waiting-button');

    $('#section-details-tab').show();
    $('.complete-route-details').removeAttr('disabled');
    current = $('#section-select-route li.selected').html();
    saveRouteDialogText = 'Saving the route data for section '+current+'.<br/>';
    // Leave indiciaData.currentFeature selected
    // Prepare data to post the new or edited section to the db
    if (indiciaData.mapdiv.map.projection.projCode!='EPSG:900913' && indiciaData.mapdiv.map.projection.projCode!='EPSG:3857') { 
      geom = geom.transform(indiciaData.mapdiv.map.projection, new OpenLayers.Projection('EPSG:3857'));
    }

    var data = {
      'location:code':current,
      'location:name':$('#location-name').val() + ' - ' + current,
      'location:parent_id':$('#location-id').val(),
      'location:boundary_geom':geom.toString(), // in 3857
      'location:location_type_id':indiciaData.sectionTypeId,
      'website_id':indiciaData.website_id
    };
    if (typeof indiciaData.sections[current]!=="undefined") {
      data['location:id']=indiciaData.sections[current].id;
    } else {
      saveRouteDialogText = saveRouteDialogText + 'Don&apos;t forget to enter the data on the &quot;Section Details&quot; tab for this new route.<br/>';
      data['locations_website:website_id']=indiciaData.website_id;
    }
	saveRouteDialog = jQuery('<p>'+saveRouteDialogText+'<span class="route-status">Saving Route...</span></p>').dialog({ title: "Saving Route", buttons: { "Hide": function() { $(this).dialog('close'); }}});

    // Setup centroid grid ref and centroid geometry of section.
    // Store this in the indiciaData
    if (indiciaData.defaultSectionGridRef.match(/^section(Centroid|Start)100$/) &&
        typeof indiciaData.srefHandlers!=="undefined" &&
        typeof indiciaData.srefHandlers[$('#imp-sref-system').val().toLowerCase()]!=="undefined") {
      var handler = indiciaData.srefHandlers[$('#imp-sref-system').val().toLowerCase()], pt, sref;
      if (indiciaData.defaultSectionGridRef==='sectionCentroid100') {
        pt = indiciaData.currentFeature.geometry.getCentroid(true); // must use weighted to accurately calculate
      } else {
        pt = jQuery.extend({}, indiciaData.currentFeature.geometry.components[0]);
      }
      sref=handler.pointToGridNotation(pt.transform(indiciaData.mapdiv.map.projection, 'EPSG:'+handler.srid), 6);
      indiciaData.sections[current] = {sref : sref,	system : $('#imp-sref-system').val()};
    } else { // default : initially set the section Sref etc to match the parent. centroid_geom will be auto generated on the server
      indiciaData.sections[current] = {sref : $('#imp-sref').val(), system : $('#imp-sref-system').val()};
    }
    // Store in POST data
    data['location:centroid_sref']=indiciaData.sections[current].sref; // centroid_geom not provided, so automatically created by warehouse from sref
    data['location:centroid_sref_system']=indiciaData.sections[current].system;
    $('#section-location-sref').val(data['location:centroid_sref']);
    $('#section-location-system,#section-location-system-select').val(indiciaData.sections[current].system);
    // Store boundary geometry in the indiciaData
    indiciaData.sections[current].geom = geom.toString(); // in 3857.

    // autocalc the section length and store in the section POST data and indiciaData and the Section Form
    if (indiciaData.autocalcSectionLengthAttrId) {
  	var sectionLen = Math.round(indiciaData.currentFeature.geometry.clone().transform(indiciaData.mapdiv.map.projection, 'EPSG:27700').getLength());
      data[$('#locAttr\\:'+indiciaData.autocalcSectionLengthAttrId).attr('name')] = sectionLen;
      $('#locAttr\\:'+indiciaData.autocalcSectionLengthAttrId).val(sectionLen);
      indiciaData.sections[current].sectionLen = sectionLen;
    }
    indiciaData.routeChanged = false;

    // POST the new section details. Synchronous due to current in success function
    $.ajax({
        type: 'POST',
        url: indiciaData.ajaxFormPostUrl,
        data: data,
        success: function(data) {
          if (typeof(data.error)!=="undefined") {
            alert(data.error);
          } else {
              // Better way of doing this?
              var current = $('#section-select-route li.selected').html();
              indiciaData.sections[current].id = data.outer_id; // both new and old require this, see defaultSectionGridRef functionality
              $('#section-location-id').val(data.outer_id);
              $('#section-select-route-'+current).removeClass('missing');
              $('#section-select-'+current).removeClass('missing');
          }
          $('.save-route').removeClass('waiting-button');
          },
        dataType: 'json',
        async: false // Synchronous due to method of working out current in success function
    });
    // Now update the parent with the total transect length
    $('.route-status').empty().html('Updating total transect length...');
    updateTransectDetails(false);
    saveRouteDialog.dialog('close');
}

$(document).ready(function() {

  function _removeLayers(div) {
    var toRemove = [];
    $.each(div.map.layers, function(idx, layer){
      if(layer !== div.map.editLayer &&
          layer !== div.map.infoLayer &&
          layer.name !== 'OpenLayers.Handler.Path') {
        toRemove.push(layer);
      }
    });
    $.each(toRemove, function(idx, layer){
      layer.displayInLayerSwitcher = false;
      layer.setVisibility(false);
      div.map.removeLayer(layer);
    });
  }

    function _setDefaultLayers(div) {
      if (typeof div.map.defaultLayers === 'undefined') { // if first call, copy default layers into store.
        if (div.map.baseLayer == null) return false;
        div.map.state = "Default";
        div.map.defaultLayers = {layers: [],
          mapProjection: div.map.projection,
          editLayerProjection: div.map.editLayer.projection,
          infoLayerProjection: div.map.infoLayer.projection,
          centre: div.map.center,
          zoom: div.map.zoom
        };
        $.each(div.map.layers, function(idx, layer){
          if(layer !== div.map.editLayer && layer !== div.map.infoLayer)
            div.map.defaultLayers.layers.push(layer);
        });
      }
      return true;
    }

    function _extendBounds(bounds, buffer) {
      var dy = (bounds.top-bounds.bottom) * buffer;
      var dx = (bounds.right-bounds.left) * buffer;
      bounds.top = bounds.top + dy;
      bounds.bottom = bounds.bottom - dy;
      bounds.right = bounds.right + dx;
      bounds.left = bounds.left - dx;
      return bounds;
    }

    function _setBaseLayers(div) {
      // div.map.state holds the index in the country definitions array
      var retVal = {index: false, mapChanged: false, reproject: false},
          found = false,
          countryVal = $('#'+indiciaData.settings.countryAttr.id.replace(/:/g,'\\:')).val();

      if(countryVal!==''){
        for(i=0; i < indiciaData.settings.country_configurations.length && !found; i++) {
          if(typeof indiciaData.settings.country_configurations[i].country_names === 'undefined' ||
              typeof indiciaData.settings.country_configurations[i].map === 'undefined') continue; // skip this entry if no map defined.
          for(j=0, found=false; j < indiciaData.settings.country_configurations[i].country_names.length && !found; j++)
            if(indiciaData.settings.country_configurations[i].country_names[j].country !== "Default" &&
                indiciaData.settings.country_configurations[i].country_names[j].id == countryVal)
              found = true;
          if(!found) continue;
          retVal.index = i;
          if(div.map.state == i) return retVal; // country map has same configuration index as previous value, don't flag changed.
          _removeLayers(div);
          div.map.state = i;
          retVal.mapChanged = true;
          $.each(indiciaData.settings.country_configurations[i].map.tilecacheLayers, function(i, layer) {
            var tcLayer;

            layer.settings.maxResolution = "auto";
            layer.settings.minResolution = "auto";
//            layer.settings.units = "meters"; TODO ???
//            layer.settings.displayInLayerSwitcher = true; TODO ???
            tcLayer = new OpenLayers.Layer.TileCache(layer.caption, layer.servers, layer.layerName, layer.settings);

            if(layer.settings.isBaseLayer) {
              if(div.map.projection.projCode != layer.settings.srs) {
                retVal.reproject = true;
                div.map.projection = new OpenLayers.Projection(layer.settings.srs);
                div.map.infoLayer.projection = new OpenLayers.Projection(layer.settings.srs);
                div.map.editLayer.projection = new OpenLayers.Projection(layer.settings.srs);
              }
              div.map.addLayer(tcLayer);
              div.map.setBaseLayer(tcLayer);
              div.map.maxExtent = tcLayer.maxExtent;
            } else
              div.map.addLayer(tcLayer);

            if(typeof layer.setInitialVisibility != 'undefined')
              tcLayer.setVisibility(layer.setInitialVisibility);
          });
          return retVal;
        }
      }
      // at this point will have returned if found in list
      retVal.index = false;
      if(div.map.state != "Default") {
        retVal.mapChanged = true;
        _removeLayers(div);
        div.map.state = "Default";
        if(div.map.projection.projCode != div.map.defaultLayers.mapProjection.projCode) {
          retVal.reproject = true;
          div.map.projection = div.map.defaultLayers.mapProjection;
          div.map.infoLayer.projection = div.map.defaultLayers.infoLayerProjection;
          div.map.editLayer.projection = div.map.defaultLayers.editLayerProjection;
        }
        $.each(div.map.defaultLayers.layers, function(idx, layer){
          layer.displayInLayerSwitcher = true;
          div.map.addLayer(layer);
          div.map.setBaseLayer(layer);
          div.map.maxExtent = layer.maxExtent;
        });
      }
      return retVal;
    }

    function _convertGeometries(projCode, layer) {
      // Switch off any feature added functionality
      var eventHandler, toRemove = [];
      if(projCode != layer.projection.projCode) {
        $.each(layer.features, function(idx, feature){
          toRemove.push(feature);
        });
        layer.removeFeatures(toRemove);
        $.each(toRemove, function(idx, feature){
          var cloned = feature.geometry.clone();
          feature.geometry = cloned.transform(projCode, layer.projection.projCode);
          feature.attributes.converted = true;
          layer.addFeatures([feature]);
        });
      }
    }

    function _resetMap(div, incInfo, initBounds, zoom) {
      var bounds = null;

      div.map.updateSize();
      if(!zoom) return;
      if (div.map.editLayer.features.length>0) // Transect or Sections.
        bounds = div.map.editLayer.getDataExtent();
      if (incInfo && div.map.infoLayer.features.length>0) {
        if (bounds === null)
          bounds = div.map.infoLayer.getDataExtent();  // Country or Transect
        else
          bounds.extend(div.map.infoLayer.getDataExtent());
      }
      if (initBounds && typeof indiciaData.initialBounds !== "undefined") {
        indiciaFns.zoomToBounds(div, indiciaData.initialBounds);
        delete indiciaData.initialBounds;
      } else {
        if(bounds !== null) {
          _extendBounds(bounds, div.settings.maxZoomBuffer);
          div.map.zoomToExtent(bounds);
        }
        if(typeof div.map.baseLayer.onMapResize !== "undefined")
          div.map.baseLayer.onMapResize();
      }
    }

  var doingSelection=false; 
  
  $('#section-form').ajaxForm({ // pressed save on the form details page.
    async: false,
    dataType:  'json',
    complete: function() {
      // 
    },
    success: function(data) {
      // remove existing errors:
      $('#section-form').find('.inline-error').remove();
      $('#section-form').find('.ui-state-error').removeClass('ui-state-error');
      if(typeof data.errors !== "undefined"){
        for(field in data.errors){
          var elem = $('#section-form').find('[name='+field+']');
          var label = $("<label/>")
                    .attr({"for":  elem[0].id, generated: true})
                    .addClass('inline-error')
                    .html(data.errors[field]);
          var elementBefore = $(elem).next().hasClass('deh-required') ? $(elem).next() : elem;
          label.insertAfter(elementBefore);
          elem.addClass('ui-state-error');
        }
        jQuery('<p>The information for section '+current+' has NOT been saved. There are errors which require attention, and which have been highlighted - please correct them and re-save.</p>').dialog({ title: "Section Save Failed", buttons: { "OK": function() { $(this).dialog('close'); }}});
      } else {
        var current = $('#section-select li.selected').html();
        // store the Sref...
        indiciaData.sections[current].sref = $('#section-location-sref').val();
        indiciaData.sections[current].system = $('#section-location-system-select').val();
        jQuery('<p>The information for section '+current+' has been succesfully saved.</p>').dialog({ title: "Section Saved", buttons: { "OK": function() { $(this).dialog('close'); }}});
        indiciaData.sectionDetailsChanged = false;
      }
    }
  });  

  $('#section-select li').click(function(evt) {
    var parts = evt.target.id.split('-');
    confirmSelectSection(parts[parts.length-1], true, true);
  });

  $('#section-form').find('input,textarea,select').change(function(evt) {
    indiciaData.sectionDetailsChanged = true;
  });

  // Extra map initialisation functionality is needed if there is more than one map - i.e. if the
  // route map is displayed.
  function copy_over_transects()
  {
    var copyFeatures = [];
    var removeFeatures = [];
    var oldFeatures = [];
    var mainMapDiv = $('#map');
    var routeMapDiv = $('#route-map');

    mainMapDiv = mainMapDiv[0];
    routeMapDiv = routeMapDiv[0];

    // keep transect feature on route map upto date with main map: always copy from main.
    // first remove all parent layer features.
    $.each(routeMapDiv.map.infoLayer.features, function(idx, elem){ oldFeatures.push(elem); });
    if(oldFeatures.length>0)
      routeMapDiv.map.infoLayer.removeFeatures(oldFeatures);

    // next remove edit layer transect feature if present.
    $.each(routeMapDiv.map.editLayer.features, function(idx, elem){
      // there may be route modification circle features
      // main transect comes across as type clickpoint.
      if(typeof elem.attributes.section === "undefined" && typeof elem.attributes.type !== "undefined" && elem.attributes.type === "clickPoint"){
        removeFeatures.push(elem);
      }
    });
    if(removeFeatures.length>0)
      routeMapDiv.map.editLayer.removeFeatures(removeFeatures);

    // finally copy from main map.
    $.each(mainMapDiv.map.editLayer.features, function(idx, elem){
      var newFeature, cloned;
      if(typeof elem.attributes.type !== "undefined" && elem.attributes.type === "clickPoint"){
        newFeature = elem.clone();
        if(routeMapDiv.map.infoLayer.projection.projCode != mainMapDiv.map.editLayer.projection.projCode) {
          cloned = newFeature.geometry.clone();
          newFeature.geometry = cloned.transform(mainMapDiv.map.editLayer.projection.projCode, routeMapDiv.map.infoLayer.projection.projCode);
        }
        copyFeatures.push(newFeature);
      }
    });
    if(copyFeatures.length>0)
      routeMapDiv.map.infoLayer.addFeatures(copyFeatures);
    else if(removeFeatures.length>0)
      routeMapDiv.map.infoLayer.addFeatures(removeFeatures);
    else if(oldFeatures.length>0) // backup just in case
      routeMapDiv.map.infoLayer.addFeatures(oldFeatures);
  }

    function resetMap(div, incInfo, initBounds, zoom) {
      var editLayerProj,
          infoLayerProj,
          details;

      if(typeof div.map == 'undefined') return;
      if ($(div).filter(':visible').length == 0) return;

      editLayerProj = div.map.editLayer.projection;
      infoLayerProj = div.map.infoLayer.projection;

      if(_setDefaultLayers(div)) {
        details = _setBaseLayers(div);
        if(details.reproject) {
          _convertGeometries(editLayerProj.projCode, div.map.editLayer);
          _convertGeometries(infoLayerProj.projCode, div.map.infoLayer);
        }
      }
      // when the route map is initially created it is hidden, so is not rendered, and the calculations of the map size are wrong
      // (Width is 100 rather than 100%), so any initial zoom in to the transect by the map panel is wrong.
      // Not only that but the layers may have changed.
      _resetMap(div, incInfo, initBounds, zoom);
    }

  // Need to work around issue where standard mapTabHandler can only handle one map
  indiciaFns.bindTabsActivate($('.ui-tabs'), function(event, ui) {
      var div,
          target = (typeof ui.newPanel==='undefined' ? ui.panel : ui.newPanel[0]),
          mod = false;
      if((div = $('#'+target.id+' #route-map')).length > 0){ // Your Route map is being displayed on this tab
        mod = typeof indiciaData.modifyFeature !== "undefined" && indiciaData.modifyFeature.active;
        if(mod) indiciaData.modifyFeature.deactivate();
        copy_over_transects();
        div = div[0]; // equivalent of indiciaData.mapdiv
        resetMap(div, true, false, true) // when redisplaying route map, include the transect itself.
        if(mod) indiciaData.modifyFeature.activate();
      } else if ((div = $('#'+target.id+' #map')).length > 0){ // Main map is being displayed on this tab
        div = div[0]; // equivalent of indiciaData.mapdiv
        resetMap(div, false, true, true); // when redisplaying route map, dont include the country.
      }
    });



  mapInitialisationHooks.push(function(div) {
    var defaultStyle = new OpenLayers.Style(),
        selectedStyle = new OpenLayers.Style(),
        baseStyle = { strokeWidth: 4, strokeDashstyle: "dash" },
        defaultRule = new OpenLayers.Rule({ symbolizer: $.extend({strokeColor: "#0000FF"}, baseStyle) }),
        selectedRule = new OpenLayers.Rule({ symbolizer: $.extend({strokeColor: "#FFFF00"}, baseStyle) }),
        f = [];

    if (div.id==='route-map') {
      indiciaData.currentFeature = null;
      indiciaData.routeChanged = false;
      indiciaData.enableSelectReload = true;

      div.map.infoLayer = new OpenLayers.Layer.Vector('Transect Square', {style: div.map.editLayer.style, 'sphericalMercator': true, displayInLayerSwitcher: true});
      div.map.addLayer(div.map.infoLayer);
      // If there are any features in the editLayer without a section number, then this is the transect square feature, so move it to the parent (Transect Square) layer,
      // otherwise it will be selectable and will prevent the route features being clicked on to select them. Have to do this each time the tab is displayed
      // else any changes in the transect will not be reflected here.
      copy_over_transects();

      // find the selectFeature control so we can interact with it later
      // Note these are the route-map controls.
      $.each(div.map.controls, function(idx, control) {
        if (control.CLASS_NAME==='OpenLayers.Control.SelectFeature') {
          indiciaData.selectFeature = control;
          div.map.editLayer.events.on({'featureselected': function(evt) {
            if(indiciaData.enableSelectReload) // switched off when selecting a feature when not changing the section.
              confirmSelectSection(evt.feature.attributes.section, false, false);
          }});
        } else if (control.CLASS_NAME==='OpenLayers.Control.DrawFeature') {
          indiciaData.drawFeature = control;
        } else if (control.CLASS_NAME==='OpenLayers.Control.ModifyFeature') {
          indiciaData.modifyFeature = control;
          control.standalone = true;
          control.events.on({'activate':function() {
        	var oldEnableSelectReload = indiciaData.enableSelectReload;
        	indiciaData.enableSelectReload = false;
            indiciaData.modifyFeature.selectFeature(indiciaData.currentFeature);
            indiciaData.enableSelectReload = oldEnableSelectReload;
            div.map.editLayer.redraw();
          }});
        } else if (control.CLASS_NAME==='OpenLayers.Control.Navigation') {
            indiciaData.navControl = control;
        }
      });

      // Set up the styles
      div.map.editLayer.style = null;
      // restrict the label style to the type boundary lines, as this excludes the virtual edges created during a feature modify
      var labelRule = new OpenLayers.Rule({
        filter: new OpenLayers.Filter.Comparison({ type: OpenLayers.Filter.Comparison.EQUAL_TO, property: "type", value: "boundary" }),
        symbolizer: {
          label : "${section}",
          fontSize: "16px",
          fontFamily: "Verdana, Arial, Helvetica,sans-serif",
          fontWeight: "bold",
          fontColor: "#FF0000",
          labelAlign: "cm"
        }
      });
      defaultStyle.addRules([defaultRule, labelRule]);
      selectedStyle.addRules([selectedRule, labelRule]);
      div.map.editLayer.styleMap = new OpenLayers.StyleMap({
        'default': defaultStyle,
        'select':selectedStyle
      });

      // add the loaded section geoms to the map. Do this before hooking up to the featureadded event.
      $.each(indiciaData.sections, function(idx, section) {
        if(section.geom != '') // at this point in the initialisation, the projection is the 3857: base layers not swapped.
          f.push(new OpenLayers.Feature.Vector(OpenLayers.Geometry.fromWKT(section.geom), {section:'S'+idx.substr(1), type:"boundary"}));
      });
      div.map.editLayer.addFeatures(f);

      $('.olControlEditingToolbar').addClass('right');

      function featureRouteAddedEvent(evt) {
          if(typeof evt.feature.attributes.converted != 'undefined') return;
          // Only handle lines - as things like the sref control also trigger feature change events
          if (evt.feature.geometry.CLASS_NAME==="OpenLayers.Geometry.LineString") {
            var current, oldSection = [];
            // Find section attribute if existing, or selected section button if new
            current = (typeof evt.feature.attributes.section==="undefined") ? $('#section-select-route li.selected').html() : evt.feature.attributes.section;
            // label a new feature properly (and remove the undefined that appears)
            evt.feature.attributes = {section:current, type:"boundary"};
            $.each(evt.feature.layer.features, function(idx, feature) {
              if (feature.attributes.section===current && feature !== evt.feature) {
                oldSection.push(feature);
              }
            });
            if (oldSection.length>0) {
              if (!confirm('Would you like to replace the existing section with the new one?')) {
                evt.feature.layer.removeFeatures([evt.feature], {}); // No so abort - remove new feature
                return;
              } else {
                evt.feature.layer.removeFeatures(oldSection, {});
              }
            }
            indiciaData.routeChanged = true;
            // make sure the feature is selected: this ensures that it can be modified straight away
            // but we dont't want it to trigger a load of attibutes etc.
            // Leave the draw feature control active.
            indiciaData.currentFeature = evt.feature;
            var oldEnableSelectReload = indiciaData.enableSelectReload;
            indiciaData.enableSelectReload = false;
            indiciaData.selectFeature.select(indiciaData.currentFeature);
            indiciaData.enableSelectReload = oldEnableSelectReload;
            div.map.editLayer.redraw();
          }
      }

      div.map.editLayer.events.on({'featureadded': featureRouteAddedEvent}); 
      div.map.editLayer.events.on({'afterfeaturemodified': function() {indiciaData.routeChanged = true;}}); 

      resetMap(div, true, false, true);
      // select the first section
      locTypeChange();
      selectSection('S1', true);

      // following are for the route and detail tabs: not always present
      $('#section-select-route li').click(function(evt) {
        var parts = evt.target.id.split('-');
        confirmSelectSection(parts[parts.length-1], true, true);
      });
      $('.insert-section').click(function(evt) {
        var current = $('#section-select-route li.selected').html();
        if(confirm(indiciaData.sectionInsertConfirm + ' ' + current + '?')) insertSection(current);
      });
      $('.erase-route').click(function(evt) {
        var current = $('#section-select-route li.selected').html(),
            oldSection = [],
            div = $('#route-map'),
            geom;
        div = div[0];
        // If the draw feature control is active unwind it one point at a time, starting at the end.
        for(var i = div.map.controls.length-1; i>=0; i--)
          if(div.map.controls[i].CLASS_NAME == 'OpenLayers.Control.DrawFeature' && div.map.controls[i].active) {
            if(div.map.controls[i].handler.line){
              if(div.map.controls[i].handler.line.geometry.components.length <= 2) // start point plus current unselected position)
                div.map.controls[i].cancel();
              else 
                div.map.controls[i].undo();
              return;
            }
          }
        $.each(div.map.editLayer.features, function(idx, feature) {
          if (feature.attributes.section===current) {
            oldSection.push(feature);
          }
        });
        if (oldSection.length>0 && oldSection[0].geometry.CLASS_NAME==="OpenLayers.Geometry.LineString") {
          if (!confirm('Do you wish to erase the route for this section?')) {
            return;
          }
        } else return; // no existing route to clear
        indiciaData.navControl.deactivate();
        indiciaData.modifyFeature.deactivate();
        indiciaData.drawFeature.deactivate();
        indiciaData.selectFeature.deactivate();
        indiciaData.currentFeature = null;
        div.map.editLayer.removeFeatures(oldSection, {});
        if (typeof indiciaData.sections[current]=="undefined") {
          return; // not currently stored in database
        }
        indiciaData.drawFeature.activate();
        indiciaData.sections[current].sectionLen = 0;
        // have to leave the location in the website (data may have been recorded against it), but can't just empty the geometry as will fail validation
        geom = oldSection[0].geometry.clone();
        if (indiciaData.mapdiv.map.projection.projCode!='EPSG:900913' && indiciaData.mapdiv.map.projection.projCode!='EPSG:3857') { 
          geom = geom.transform(indiciaData.mapdiv.map.projection, new OpenLayers.Projection('EPSG:3857'));
        }
        var data = {
            'location:boundary_geom':'',
            'location:centroid_geom':geom.getCentroid().toString(),
            'location:id':indiciaData.sections[current].id,
            'website_id':indiciaData.website_id
        };
        indiciaData.routeChanged = false;
        $.post(
          indiciaData.ajaxFormPostUrl,
          data,
          function(data) {
            if (typeof(data.error)!=="undefined") {
              alert(data.error);
            } else {
              // Better way of doing this?
              var current = $('#section-select-route li.selected').html();
              $('#section-select-route-'+current).addClass('missing');
              $('#section-select-'+current).addClass('missing');
            }
            // recalculate total transect length
            updateTransectDetails(false);
          },
          'json'
        );
      }); // End function for erase route click
      $('.save-route').click(function(evt) {
        var current, oldSection = [];
        // This saves the currently selected route aginst the currently selected section.
        // We assume that user has pressed button deliberately, so no confirmation.
        if(indiciaData.currentFeature === null) return; // no feature selected so don't save
        if($('.save-route').hasClass('waiting-button')) return; // prevents double clicking.
        $('.save-route').addClass('waiting-button');

        var buttons =  { 
          "Abort changes" : function() {
            $(this).dialog('close');
            // replace the route with the previous one for this section.
            // At this point, indiciaData.currentSection should point to existing, previously selected section.
            var removeSections = [], oldSection = [], div = $('#route-map'), geom;
            div = div[0];
            if(typeof indiciaData.modifyFeature !== "undefined")
              indiciaData.modifyFeature.deactivate();
        		if(typeof indiciaData.drawFeature !== "undefined")
        		    indiciaData.drawFeature.deactivate();
    		        indiciaData.navControl.activate(); // Nav control always exists
        	    $.each(div.map.editLayer.features, function(idx, feature) {
        	        if (feature.attributes.section===indiciaData.currentSection) {
        	            removeSections.push(feature);
        	        }
        	    });
        	    if (removeSections.length>0) {
        	        div.map.editLayer.removeFeatures(removeSections, {});
        	    }
        	    if(typeof indiciaData.sections[indiciaData.currentSection] !== 'undefined' &&
        	    		typeof indiciaData.sections[indiciaData.currentSection].geom !== 'undefined') {
                    geom = OpenLayers.Geometry.fromWKT(indiciaData.sections[indiciaData.currentSection].geom);
                    if (indiciaData.mapdiv.map.projection.projCode!='EPSG:900913' && indiciaData.mapdiv.map.projection.projCode!='EPSG:3857') { 
                      geom = geom.transform(new OpenLayers.Projection('EPSG:3857'), indiciaData.mapdiv.map.projection);
                    }
        	        oldSection.push(new OpenLayers.Feature.Vector(geom, {section:indiciaData.currentSection, type:"boundary"}));
        	        div.map.editLayer.addFeatures(oldSection);
        	    } // dont worry about selection.
        	    indiciaData.routeChanged = false;
        	    $('.save-route').removeClass('waiting-button');
        	  },
        	"Don't save":  function() {
        	    $('.save-route').removeClass('waiting-button');
        	    $(this).dialog('close');
        	  },
          	"Yes": function() {
        		$(this).dialog('close');
        	    saveRoute();
        	  }
          };
          // display dialog and drive from its button events.
          var dialog = $('<p>Are you sure you wish to save this route now? Choose &quot;Yes&quot; to save the changes; &quot;Don&apos;t save&quot; to leave the route as is, but not save it just yet; and &quot;Abort changes&quot; to wind back the changes, and (if applicable) replace the new route with the previously saved version.</p>')
        	    	.dialog({ title: "Save Route Data?",
        	    		buttons: buttons,
        	    		width: 400,
        	    	    closeOnEscape: false,
        	    	    open: function(event, ui) {
        	    	        $(".ui-dialog-titlebar-close", $(this).parent()).remove();
        	    	    }});
      });
      $('.complete-route-details').click(function(evt) {
          indiciaFns.activeTab($('#controls'), 'section-details');
      });
      $('.remove-section').click(function(evt) {
        var current = $('#section-select-route li.selected').html();
        if(confirm(indiciaData.sectionDeleteConfirm + ' ' + current + '?')) deleteSection(current);
      });
      if($('#section-location-id').val() == '')
        $('.complete-route-details').attr('disabled','disabled');

    } else {
      // main map
      function featureSiteAddedEvent(evt) { // check that the country is OK.
        if(typeof evt.feature.attributes.converted != 'undefined') return;
        // get country that centroid is in: requires geoserver.
        // proxiedurl,featurePrefix,featureType,[geometryName],featureNS,srsName[,propertyNames]
        if(indiciaData.settings.country_layer_lookup.length != 5)
          return;
        if(typeof(evt.feature) == 'undefined' ||
            typeof(evt.feature.attributes) == 'undefined' ||
            (typeof(evt.feature.attributes.temp) !== 'undefined' &&
              typeof(evt.feature.attributes.temp=== true)))
          return;
        var protocolSpec = indiciaData.settings.country_layer_lookup, geom;

        var protocol = new OpenLayers.Protocol.WFS({
            url: protocolSpec[0],featurePrefix: protocolSpec[1],featureType: protocolSpec[2], geometryName:'boundary_geom',featureNS: protocolSpec[3],srsName: protocolSpec[4],version: '1.1.0',propertyNames: ['boundary_geom','name']
           ,callback: function(a1){
             // here we don't zoom

            if(a1.error && (typeof a1.error.success == 'undefined' || a1.error.success == false)){
              alert('Country lookup failed.'); // TODO NTH i18n
              return;
            }
            if(a1.features.length > 0) {
              var id = a1.features[0].fid.split('.');
              id=id[1];
              if($('#'+indiciaData.settings.countryAttr.id.replace(/:/g,'\\:')).val() == id) // country is the same
                return;
              if($('#'+indiciaData.settings.countryAttr.id.replace(/:/g,'\\:')).val() == '') { // not set yet.
                $('#'+indiciaData.settings.countryAttr.id.replace(/:/g,'\\:')).val(id).change();
                return;
              }
              var dialog = $('<p>A change in country has been detected. Do you wish to set the country to the new value? This may lead to changes in the acceptable number of sections, and/or a different set of maps.</p>').dialog(
                      {title: "Change Country?",
                       buttons: { 
                           "No":  function() { $(this).dialog('close'); },
                           "Yes": function() { $(this).dialog('close');
                               $('#'+indiciaData.settings.countryAttr.id.replace(/:/g,'\\:')).val(id);
                               countryChange(false); }}});
            } // else no country - just leave alone
          }
        });
        // convert geometry to 3857 as this is what the geoserver uses.
        geom = evt.feature.geometry.clone();
        if (indiciaData.mapdiv.map.projection.projCode!='EPSG:900913' && indiciaData.mapdiv.map.projection.projCode!='EPSG:3857') { 
          geom = geom.transform(indiciaData.mapdiv.map.projection, new OpenLayers.Projection('EPSG:3857'));
        }
        filter = new OpenLayers.Filter.Logical({type:OpenLayers.Filter.Logical.AND, filters:[
                     new OpenLayers.Filter.Spatial({type: OpenLayers.Filter.Spatial.CONTAINS,property: 'boundary_geom',value: geom.getCentroid()}),
                     new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.EQUAL_TO, property: 'location_type_id', value: indiciaData.settings.country_location_type_id})]});
        protocol.read({filter: filter});
      };

      div.map.infoLayer = new OpenLayers.Layer.Vector('Country',
          {style: { // a combination of georef, boundary and ghost. No Fill
              fillOpacity : 0,
              strokeOpacity : 0.5,
              strokeColor : '#ee0000',
              strokeDashstyle : 'dash',
              strokeWidth : 2
            },
           sphericalMercator: true,
           displayInLayerSwitcher: true});
      div.map.addLayer(div.map.infoLayer);
      countryChange(false);

      div.map.editLayer.events.on({'featureadded': featureSiteAddedEvent}); 

      resetMap(div, false, true, false);

    }
  });
  
  $('#add-user').click(function(evt) {
    var user=($('#cmsUserId')[0]).options[$('#cmsUserId')[0].selectedIndex];
    if ($('#user-'+user.value).length===0) {
      $('#user-list').append('<tr><td id="user-'+user.value+'"><input type="hidden" name="locAttr:'+indiciaData.locCmsUsrAttr+'::'+user.value+'" value="'+user.value+'"/>'+
          user.text+'</td><td><div class="ui-state-default ui-corner-all"><span class="remove-user ui-icon ui-icon-circle-close"></span></div></td></tr>');
    }
  });
  
  $('.remove-user').live('click', function(evt) {
    $(evt.target).closest('tr').css('text-decoration','line-through');
    $(evt.target).closest('tr').addClass('ui-state-disabled');
    // clear the underlying value
    $(evt.target).closest('tr').find('input').val('');
  });

  $('#add-branch-coord').click(function(evt) {
    var coordinator=($('#branchCmsUserId')[0]).options[$('#branchCmsUserId')[0].selectedIndex];
    if ($('#branch-coord-'+coordinator.value).length===0) {
      $('#branch-coord-list').append('<tr><td id="branch-coord-'+coordinator.value+'">' +
          // TODO ??? replace locBranchCmsUsrAttr??
          '<input type="hidden" name="locAttr:'+indiciaData.locBranchCmsUsrAttr+'::'+coordinator.value+'" value="'+coordinator.value+'"/>'+coordinator.text+'</td>'+
          '<td><div class="ui-state-default ui-corner-all"><span class="remove-user ui-icon ui-icon-circle-close"></span></div></td></tr>');
    }
  });

    $('#'+indiciaData.settings.countryAttr.id.replace(/:/g,'\\:')).change(function(evt){countryChange(true);});

    countryChange = function(checkOutside) { // changeCountry
      var lt = $('#location_type_id').val(),
          myVal = $('#'+indiciaData.settings.countryAttr.id.replace(/:/g,'\\:')).val(),
          found = false,
          details,
          mainMapDiv = $('#map'),
          toRemove = [],
          mapExtent,
          reproject = false,
          list,
          oldEditLayerProj,
          _countryChangeEnd;

      mainMapDiv = mainMapDiv[0];

      _countryChangeEnd = function(div, oldMapExtent, reproject, oldProjection) {
        var infoExtent = div.map.infoLayer.getDataExtent(),
            bounds, dialog, protocol, geom, protocolSpec;

        if(div.map.editLayer.features.length > 0) {
          // keep map extent the same if a site already entered.
          if(reproject) {
            bounds = oldMapExtent.transform(oldProjection, div.map.projection);
            div.map.zoomToExtent(bounds, true);
            if(!mainMapDiv.map.getExtent().intersectsBounds(mainMapDiv.map.baseLayer.maxExtent))
              div.map.zoomToExtent(mainMapDiv.map.baseLayer.maxExtent, true);
          }
          if (myVal=='')
            dialog = $('<p>Warning: you are clearing the country, after the site has been created and a country allocated.</p>').dialog(
                      {title: "Country Cleared",
                       buttons: { "OK":  function() { $(this).dialog('close'); }}});
          else if (checkOutside && indiciaData.settings.country_layer_lookup.length == 5) {
            protocolSpec = indiciaData.settings.country_layer_lookup;

            protocol = new OpenLayers.Protocol.WFS({
                url: protocolSpec[0],featurePrefix: protocolSpec[1],featureType: protocolSpec[2], geometryName:'boundary_geom',featureNS: protocolSpec[3],srsName: protocolSpec[4],version: '1.1.0',propertyNames: ['boundary_geom','name']
               ,callback: function(a1){
                  if(a1.error && (typeof a1.error.success == 'undefined' || a1.error.success == false)){
                    return;
                  }
                  if(a1.features.length > 0) {
                    var id = a1.features[0].fid.split('.');
                    id=id[1];
                    if($('#'+indiciaData.settings.countryAttr.id.replace(/:/g,'\\:')).val() == id) // country is the same
                      return;
                    var dialog = $('<p>Warning: the site you have previously entered is outside the country you have just selected.</p>').dialog(
                            {title: "Outside Country",
                             buttons: { "OK":  function() { $(this).dialog('close'); }}});
                  }
                }
              });
            // convert to 3857 as this is what the geoserver uses.
            geom = div.map.editLayer.features[0].geometry.clone();
            if (indiciaData.mapdiv.map.projection.projCode!='EPSG:900913' && indiciaData.mapdiv.map.projection.projCode!='EPSG:3857') { 
              geom = geom.transform(indiciaData.mapdiv.map.projection, new OpenLayers.Projection('EPSG:3857'));
            }

            filter = new OpenLayers.Filter.Logical({type:OpenLayers.Filter.Logical.AND, filters:[
                           new OpenLayers.Filter.Spatial({type: OpenLayers.Filter.Spatial.CONTAINS,property: 'boundary_geom',value: geom.getCentroid()}),
                           new OpenLayers.Filter.Comparison({type: OpenLayers.Filter.Comparison.EQUAL_TO, property: 'location_type_id', value: indiciaData.settings.country_location_type_id})]});
            protocol.read({filter: filter});
          }
        } else if(infoExtent !== null)
          div.map.zoomToExtent(infoExtent);
        else
          mainMapDiv.map.setCenter(div.map.defaultLayers.centre, div.map.defaultLayers.zoom, false, true);
      }

      // record the extent of the edit layer on the main map. The route map is not being displayed and that is re-zoomed on change of tab. Possibly empty.
      mapExtent = mainMapDiv.map.getExtent();
      oldEditLayerProj = mainMapDiv.map.editLayer.projection;

      // scan config to get new maps.
      if(_setDefaultLayers(mainMapDiv)) { // returns false if no valid baselayer.
        details = _setBaseLayers(mainMapDiv); // returns object : index into country array for map data, or false if default.
        // reproject any edit layer geometries: main map.
        if(details.reproject)
          _convertGeometries(oldEditLayerProj.projCode, mainMapDiv.map.editLayer); // country infoLayer is empty
        _resetMap(mainMapDiv, true, false, mainMapDiv.map.editLayer.features.length == 0);
      } else return;

      // set up sref system options
      system = $('#imp-sref-system').val();
      $('#imp-sref-system option').addClass('working').attr('disabled',false);
      list = (details.index !== false ? indiciaData.settings.country_configurations[details.index].map.sref_systems.split(',') : indiciaData.settings.defaultSystems);
      $.each(list, function(idx, system){ $('#imp-sref-system option[value='+system+']').removeClass('working'); });
      $('#imp-sref-system option.working').removeClass('working').attr('disabled','disabled');
      if($('#imp-sref-system option[value='+system+']:enabled').length == 0) {
        $('#imp-sref-system').val('');
      }
      if($('#imp-sref-system').val() != system && mainMapDiv.map.editLayer.features.length > 0)
        $.getJSON(indiciaData.indiciaSvc +
        		  'index.php/services/spatial/wkt_to_sref&wkt=' + 
        		  $('#imp-geom').val() +
        		  '&system='+$('#imp-sref-system').val()+
        		  '&precision=8&callback=?',
      	      function(data){
      	        if(typeof data.error != 'undefined')
      	          alert(data.error);
      	        else {
      	          $('#imp-sref').val(data.sref);
      	        }
      	      });


      // empty info layer on main map
      mainMapDiv.map.infoLayer.destroyFeatures();

      // If country specified, add to info layer on main map 
      if (myVal != ''){
        // first put the country on the map
        $('#'+indiciaData.settings.countryAttr.id.replace(/:/g,'\\:')).addClass("working1");
        $.getJSON(indiciaData.indiciaSvc + "index.php/services/data/location/" + myVal +
                    '?mode=json&view=detail' +
                    '&auth_token='+indiciaData.readAuth.auth_token+
                    '&reset_timeout=true&nonce='+indiciaData.readAuth.nonce,
          function(ldata) {
            var mainMapDiv = $('#map');
            mainMapDiv = mainMapDiv[0];
            $.each(ldata, function(idx, country){
              var parser = new OpenLayers.Format.WKT(),
                  features;
              features = parser.read(country.boundary_geom);
              if (mainMapDiv.map.infoLayer.projection.projCode!='EPSG:900913' && mainMapDiv.map.infoLayer.projection.projCode!='EPSG:3857') { 
                var cloned = features.geometry.clone();
                features.geometry = cloned.transform(new OpenLayers.Projection('EPSG:3857'), mainMapDiv.map.infoLayer.projection.projCode);
              }
              if (!Array.isArray(features)) features = [features];
              mainMapDiv.map.infoLayer.addFeatures(features);
            });
            $('#'+indiciaData.settings.countryAttr.id.replace(/:/g,'\\:')).removeClass("working1");
            _countryChangeEnd(mainMapDiv, mapExtent, details.reproject, oldEditLayerProj);
          }
        );
        // Update Code
        // Next recalculate the code: only do this if the site is new
        if($('#location-id').length==0) {
            $(this).addClass("working2");
            $.getJSON(indiciaData.indiciaSvc + "index.php/services/data/location" +
                      '?mode=json&view=detail&columns=code&parent_id=NULL' +
                      '&auth_token='+indiciaData.readAuth.auth_token+
                      '&reset_timeout=true&nonce='+indiciaData.readAuth.nonce,
                  function(ldata) {
                      var code = 1, thisCode,
                          prefix = indiciaData.autogeneratePrefix +
                              $('#'+indiciaData.settings.countryAttr.id.replace(/:/g,'\\:')+' option:selected').text() +
                              indiciaData.autogeneratePrefix.substring(indiciaData.autogeneratePrefix.length-1);
                      $.each(ldata, function(idx, country){
                        if(country.code !== null &&
                            country.code.substring(0, prefix.length) == prefix) {
                          thisCode = parseInt(country.code.substring(prefix.length));
                          if(code <= thisCode) { code = thisCode + 1; }
                        }});
                      $('#location-code').val(prefix+code);
                      $('#'+indiciaData.settings.countryAttr.id.replace(/:/g,'\\:')).removeClass("working2");
                  }
                );
          }
      } else
        _countryChangeEnd(mainMapDiv, mapExtent, details.reproject, oldEditLayerProj);

      // update location types to match new country definition
      if($('select#location_type_id').length) { // if not a select then it is fixed and hidden - only 1 possible value, so no need to set options.
        $('#location_type_id option[value=]').attr('disabled',false);
        $('#location_type_id option:not([value=])').attr('disabled','disabled');
        for(i=0, found=false; i < indiciaData.settings.country_configurations.length; i++) {
          if(typeof indiciaData.settings.country_configurations[i].country_names === 'undefined') continue; // find country definition
          for(j=0; j < indiciaData.settings.country_configurations[i].country_names.length && !found; j++)
            if(indiciaData.settings.country_configurations[i].country_names[j].country !== "Default" &&
               indiciaData.settings.country_configurations[i].country_names[j].id == myVal) found = true;
          if(!found) continue;
          for(j=0; j < indiciaData.settings.country_configurations[i].location_types.length; j++)
            if(indiciaData.settings.country_configurations[i].location_types[j].can_create)
              $('#location_type_id option[value='+indiciaData.settings.country_configurations[i].location_types[j].id+']').attr('disabled',false);
          break;
        }
        if(!found) // no country specific, set default 
          for(i=0; i < indiciaData.settings.country_configurations.length; i++) {
            if(typeof indiciaData.settings.country_configurations[i].country_names === 'undefined') continue; // find country definition
            for(j=0, found=false; j < indiciaData.settings.country_configurations[i].country_names.length && !found; j++)
              if(indiciaData.settings.country_configurations[i].country_names[j].country == "Default") found = true;
              if(!found) continue;
              for(j=0; j < indiciaData.settings.country_configurations[i].location_types.length; j++)
                if(indiciaData.settings.country_configurations[i].location_types[j].can_create)
                  $('#location_type_id option[value='+indiciaData.settings.country_configurations[i].location_types[j].id+']').attr('disabled',false);
          }
        if($('#location_type_id option:not([value=]):enabled').length == 1)
          $('#location_type_id').val($('#location_type_id option:not([value=]):enabled').val());
        else if(lt!='' && $('#location_type_id option[value='+lt+']:enabled').length == 1)
          $('#location_type_id').val(lt);
        else
          $('#location_type_id').val('');
        if($('#location_type_id option:not([value=]):enabled').length == 0)
          alert('You do not have permission to create locations for this country');
      }
      // trigger a location_type update: this deals with the number of sections functionality
      $('#location_type_id').change();
    }

    locTypeChange = function(evt){
      var countryVal = $('#'+indiciaData.settings.countryAttr.id.replace(/:/g,'\\:')).val(),
          myVal = $('#location_type_id').val(),
          i,j,found = false,
          min,max,value;
      if (countryVal !== '' && myVal !== ''){
        for (i = 0; i < indiciaData.settings.country_configurations.length; i++) {
          if (typeof indiciaData.settings.country_configurations[i].country_names === 'undefined') continue; // find country definition
          for (j = 0, found = false; j < indiciaData.settings.country_configurations[i].country_names.length && !found; j++)
            if (indiciaData.settings.country_configurations[i].country_names[j].country !== "Default" &&
                indiciaData.settings.country_configurations[i].country_names[j].id == countryVal) found = true;
          if (!found) continue;
          for (j = 0, found = false; j < indiciaData.settings.country_configurations[i].location_types.length && !found; j++)
            if(indiciaData.settings.country_configurations[i].location_types[j].id == myVal) found = true;
          if(!found) continue;
          j--; // ends with j pointing to location_type after found one
          break; // drops through if couldn't find the country
        }
      }
      if (!found) {
        for(i=0; i < indiciaData.settings.country_configurations.length; i++) {
          if(typeof indiciaData.settings.country_configurations[i].country_names === 'undefined') continue; // find default country definition
          for(j=0, found=false; j < indiciaData.settings.country_configurations[i].country_names.length && !found; j++)
            if(indiciaData.settings.country_configurations[i].country_names[j].country == "Default") found = true;
          if(!found) continue;
          for (j = 0, found = false; j < indiciaData.settings.country_configurations[i].location_types.length && !found; j++)
            if(indiciaData.settings.country_configurations[i].location_types[j].id == myVal) found = true;
          if(!found) j=0;
          else j--; // ends with j pointing to location_type after found one
          break;
        }
      }
      max = Math.max(indiciaData.settings.locationId !== null ? indiciaData.settings.numSectionsAttrOriginalValue : 0,
              indiciaData.settings.country_configurations[i].location_types[j].num_sections);
      if(max != indiciaData.settings.country_configurations[i].location_types[j].num_sections)
        alert('New Site definition involves a max number of sections which is less than the current value. You must remove the extra sections manually.') //TODO NTH i18n
      min = (indiciaData.settings.locationId !== null ?
              (indiciaData.settings.country_configurations[i].location_types[j].can_change_num_sections ? indiciaData.settings.numSectionsAttrOriginalValue : Math.max(indiciaData.settings.numSectionsAttrOriginalValue, indiciaData.settings.country_configurations[i].location_types[j].num_sections)) :
              (indiciaData.settings.country_configurations[i].location_types[j].can_change_num_sections ? 1 : indiciaData.settings.country_configurations[i].location_types[j].num_sections));
      value = Math.min(max, Math.max($('[name='+indiciaData.settings.numSectionsAttr.replace(/:/g,'\\:')+']').val(), min));
      $('[name='+indiciaData.settings.numSectionsAttr.replace(/:/g,'\\:')+']')
          .val(value).attr('max',max).attr('min',min).removeClass('ui-state-error')
          .closest('div').find('.inline-error').remove();
      if(min==max)
        $('[name='+indiciaData.settings.numSectionsAttr.replace(/:/g,'\\:')+']').attr('readonly','readonly').css('color','graytext').css('background-color','#d0d0d0');
      else
        $('[name='+indiciaData.settings.numSectionsAttr.replace(/:/g,'\\:')+']').removeAttr('readonly').css('color','').css('background-color','');
      // TODO NTH set numSections helptext.
      if (indiciaData.settings.canEditSections && 
          ((indiciaData.settings.country_configurations[i].location_types[0].can_change_num_sections && value > 1) ||
            (indiciaData.settings.country_configurations[i].location_types[0].num_sections < value)))
        $('.remove-section').show();
      else
        $('.remove-section').hide();
      if (indiciaData.settings.canEditSections && 
          indiciaData.settings.country_configurations[i].location_types[0].can_change_num_sections &&
          value < indiciaData.settings.country_configurations[i].location_types[0].num_sections)
        $('.insert-section').show();
      else
        $('.insert-section').hide();
    };

    locTypeChange(null);
    $('#location_type_id').change(locTypeChange);
  });
}(jQuery));