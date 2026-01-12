

var selectedFeature = null;
var sectionDetailsChanged = false;
var clearSection, loadSectionDetails, confirmSelectSection, selectSection, syncPost,
    deleteLocation, deleteSections, deleteSection;

(function ($) {

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
      if (typeof ctrl.checked === "undefined") {
        $(ctrl).val('');
      } else {
        $(ctrl).attr('checked', false);
      }
    } else if ($(ctrl).hasClass('hierarchy-select')) {
      $(ctrl).val('');
    }
  });
};

loadSectionDetails = function(section) {
  clearSection();
  if (typeof indiciaData.sections[section]!=="undefined") {
    $('#section-location-id').val(indiciaData.sections[section].id);
    // if the systems on the section and main location do not match, copy the the system and sref from the main site.
    if(indiciaData.sections[section].system !== $('#imp-sref-system').val()) {
        $('#section-location-sref').val($('#imp-sref').val());
        $('#section-location-system,#section-location-system-select').val($('#imp-sref-system').val());
    } else {
        $('#section-location-sref').val(indiciaData.sections[section].sref);
        $('#section-location-system,#section-location-system-select').val(indiciaData.sections[section].system);
    }
    $.getJSON({
      url: indiciaData.read.url + 'index.php/services/data/location_attribute_value',
      data: {
        location_id: indiciaData.sections[section].id,
        mode: 'json',
        view: 'list',
        auth_token: indiciaData.read.auth_token,
        nonce: indiciaData.read.nonce
      },
      dataType: 'jsonp',
      crossDomain: true
    })
    .done(function (data) {
      var attrname;
      $.each(data, function(idx, attr) {
        attrname = 'locAttr:' + attr.location_attribute_id;
        if (attr.id !== null) {
          attrname += ':' + attr.id;
        }
        // special handling for checking radios
        if ($('input:radio#locAttr\\:' + attr.location_attribute_id + '\\:0').length>0) {
          var radioidx = 0;
          // name the radios with the existing value id
          while ($('#section-form #locAttr\\:' + attr.location_attribute_id + '\\:' + radioidx).length>0) {
            $('#section-form #locAttr\\:' + attr.location_attribute_id + '\\:' + radioidx).attr('name',attrname);
            radioidx++;
          }
          radioidx=0;
          // check the correct radio
          while ($('#section-form #locAttr\\:' + attr.location_attribute_id + '\\:' + radioidx).length>0 &&
              $('#section-form #locAttr\\:' + attr.location_attribute_id + '\\:' + radioidx).val()!==attr.raw_value) {
            radioidx++;
          }
          if ($('#section-form #locAttr\\:' + attr.location_attribute_id + '\\:' + radioidx).length>0 &&
              $('#section-form #locAttr\\:' + attr.location_attribute_id + '\\:' + radioidx).val()===attr.raw_value) {
            $('#section-form #locAttr\\:' + attr.location_attribute_id + '\\:' + radioidx).attr('checked', true);
          }
        } else if ($('#section-form #fld-locAttr\\:' + attr.location_attribute_id).length>0) {
          // a hierarchy select outputs a fld control, which needs a special case
          $('#section-form #fld-locAttr\\:' + attr.location_attribute_id).val(attr.raw_value);
          $('#section-form #fld-locAttr\\:' + attr.location_attribute_id).attr('name', attrname);
          // check the option is already in the drop down.
          if ($('#section-form #locAttr\\:' + attr.location_attribute_id + " option[value='" + attr.raw_value + "']").length === 0) {
            // no - we'll just put it in at the top level
            // @todo - should really now fetch the top level in the hierarchy then select that.
            $('#section-form #locAttr\\:' + attr.location_attribute_id).append('<option value="' +
                attr.raw_value + '">' + attr.value + '</option>');
          }
          $('#section-form #locAttr\\:' + attr.location_attribute_id).val(attr.raw_value);
        } else {
          $('#section-form #locAttr\\:' + attr.location_attribute_id).val(attr.raw_value);
          $('#section-form #locAttr\\:' + attr.location_attribute_id).attr('name', attrname);
        }
      });
    });
  }
};

confirmSelectSection = function(section, doFeature, withCancel) {
  if (indiciaData.insertingSection) {
    $.fancyDialog({ title: 'Save operation in progress...', message: 'Please wait until the previous section has been saved to the database before selecting a new section.', cancelButton: null });
    return;
  }
  var buttons =  {
    "Yes": function() {
          dialog.dialog('close');
          $('#section-form').submit(); // this is synchronous
          selectSection(section, doFeature);
          $(this).off(event);
        },
      "No":  function() {
          dialog.dialog('close');
          selectSection(section, doFeature);
        }
     };
  if(withCancel) {
    buttons.Cancel = function() { dialog.dialog('close'); };
  }

  if(sectionDetailsChanged === true) {
    var dialog = $('<p>' + indiciaData.lang.sectionedTransectsEditTransect.sectionChangeConfirm + '</p>').dialog({ title: "Save Data?", buttons: buttons });
  } else {
    selectSection(section, doFeature);
  }
};

selectSection = function(section, doFeature) {
  sectionDetailsChanged = false;
  // if the modify control is active, save any changes, unselect any currently selected feature
  // do this before changing the selection so that the previous selection is tidied up properly.
  if (typeof indiciaData.mapdiv !== "undefined") {
    if(typeof indiciaData.modifyFeature !== "undefined") {
      indiciaData.modifyFeature.deactivate();
    }
    if (doFeature && typeof indiciaData.selectFeature !== "undefined") {
      indiciaData.selectFeature.unselectAll();
    }
  }
  $('.section-select li').removeClass('selected');
  $('#section-select-route-' + section).addClass('selected');
  $('#section-select-' + section).addClass('selected');
  // Don't select the feature if this was triggered by selecting the feature
  // (as opposed to the button) otherwise we recurse.
  if (typeof indiciaData.mapdiv !== "undefined") {
    if (doFeature && typeof indiciaData.selectFeature !== "undefined") {
      $.each(indiciaData.mapdiv.map.editLayer.features, function(idx, feature) {
        if (feature.attributes.section === section && feature.attributes.type === 'boundary') {
          indiciaData.selectFeature.select(feature);
          selectedFeature = feature;
        }
      });
    }
    if (indiciaData.mapdiv.map.editLayer.selectedFeatures.length === 0 && typeof indiciaData.drawFeature !== 'undefined') {
      indiciaData.drawFeature.activate();
    }
    indiciaData.mapdiv.map.editLayer.redraw();
  }
  if (indiciaData.currentSection!==section) {
    loadSectionDetails(section);
    indiciaData.currentSection=section;
  }
};

syncPost = function(url, data) {
  $.ajax({
    type: 'POST',
    url: url,
    data: data,
    success: function(data) {
      if (typeof(data.error)!=="undefined") {
        alert(data.error);
      }
    },
    dataType: 'json',
    // cannot be synchronous otherwise we navigate away from the page too early
    async: false
  });
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
    $('#delete-transect').html('Deleting Sections ' + (Math.round(i/sectionIDs.length*100) + '%'));
    deleteLocation(sectionID);
  });
  $('#delete-transect').html('Deleting Sections 100%');
};

findTotalSectionLength = function() {
  var transectLen = 0;
  $.each(indiciaData.mapdiv.map.editLayer.features, function() {
    if (this.attributes.section) {
      transectLen += Math.round(this.geometry.clone().transform(indiciaData.mapdiv.map.projection, 'EPSG:27700').getLength());
    }
  });
  return transectLen;
};

//delete a section
deleteSection = function(section) {
  var data;
  // section comes in like "S1"
  // TODO Add progress bar
  $('.remove-section').addClass('waiting-button');
  // if it has been saved, delete any subsamples lodged against it.
  if(typeof indiciaData.sections[section] !== "undefined"){
    // Delete the section record itself
    data = {'location:id':indiciaData.sections[section].id,'location:deleted':'t','website_id':indiciaData.website_id};
    $.post(indiciaData.ajaxFormPostUrl,
          data,
          function(data) { if (typeof(data.error)!=="undefined") { alert(data.error); }},
          'json');
  }
  // loop through all the subsections with a greater section number
  // subsamples are attached to the location and parent, but the location_name is not filled in, so don't need to change that
  // Update the code and the name for the locations.
  // Note that the subsections may not have been saved, so may not exist.
  var numSections = parseInt($('[name=' + indiciaData.numSectionsAttrName.replace(/:/g,'\\:') + ']').val(),10);
  for(var i = parseInt(section.substr(1)) + 1; i <= numSections; i++){
    if(typeof indiciaData.sections['S' + i] !== "undefined"){
      data = {'location:id':indiciaData.sections['S' + i].id,
                  'location:code':'S' + (i-1),
                  'location:name':$('#location\\:name').val() + ' - ' + 'S' + (i-1),
                  'website_id':indiciaData.website_id};
      $.post(indiciaData.ajaxFormPostUrl,
            data,
            function(data) { if (typeof(data.error)!=="undefined") { alert(data.error); }},
            'json');
    }
  }
  // update the attribute value for number of sections.
  data = {'location:id':$('#location\\:id').val(), 'website_id':indiciaData.website_id};
  data[indiciaData.numSectionsAttrName] = '' + (numSections-1);
  // and finally update the total transect length on the transect.
  if (typeof indiciaData.autocalcTransectLengthAttrId != 'undefined' &&
      indiciaData.autocalcTransectLengthAttrId &&
      indiciaData.autocalcSectionLengthAttrId) {
    data['locAttr:' + indiciaData.autocalcTransectLengthAttrId] = findTotalSectionLength();
  }
  // reload the form when all ajax done. This will reload the new section list and total transect length into the form
  $( document ).ajaxStop(function() {
    window.location = window.location.href.split('#')[0]; // want to GET even if last was a POST. Plus don't want to go to the tab bookmark after #
  });
  $.post(indiciaData.ajaxFormPostUrl, data, function(data) {
    if (typeof(data.error)!=="undefined") {
      alert(data.error);
    }
  }, 'json');
};

//insert a section
insertSection = function(section) {
  var data;
  // section comes in like "S1"
  // TODO Add progress bar
  $('.insert-section').addClass('waiting-button');
  // loop through all the subsections with a greater section number
  // subsamples are attached to the location and parent, but the location_name is not filled in, so don't need to change that
  // Update the code and the name for the locations.
  // Note that the subsections may not have been saved, so may not exist.
  var numSections = parseInt($('[name=' + indiciaData.numSectionsAttrName.replace(/:/g,'\\:') + ']').val(),10);
  for(var i = parseInt(section.substr(1)) + 1; i <= numSections; i++){
    if(typeof indiciaData.sections['S' + i] !== "undefined"){
      data = {'location:id':indiciaData.sections['S' + i].id,
                  'location:code':'S' + (i + 1),
                  'location:name':$('#location\\:name').val() + ' - ' + 'S' + (i + 1),
                  'website_id':indiciaData.website_id};
      $.post(indiciaData.ajaxFormPostUrl,
            data,
            function(data) { if (typeof(data.error)!=="undefined") { alert(data.error); }},
            'json');
    }
  }
  // update the attribute value for number of sections.
  data = {'location:id':$('#location\\:id').val(), 'website_id':indiciaData.website_id};
  data[indiciaData.numSectionsAttrName] = '' + (numSections + 1);
  // no need to calculate increase in transect length.
  // reload the form when all ajax done.
  $( document ).ajaxStop(function(event){
	  setTimeout(function(){
		    window.location.reload(true);
		},100);
//    window.location = window.location.href.split('#')[0]; // want to GET even if last was a POST. Plus don't want to go to the tab bookmark after #
  });
  $.post(indiciaData.ajaxFormPostUrl,
          data,
          function(data) { if (typeof(data.error)!=="undefined") { alert(data.error); }},
          'json');
};

var numberOfSamples = 0;
var numberOfSections = 0;
var numberOfRecordsCompleted = 0;

//insert a section
reloadSection = function(section) {
  var data;
  // section comes in like "S1"
  jQuery('.reload-section').addClass('waiting-button');

  numberOfSections = parseInt(jQuery('[name=' + indiciaData.numSectionsAttrName.replace(/:/g,'\\:') + ']').val(),10) - (parseInt(section.substr(1)) + 1);

  var dialog = jQuery('<p>Please wait whilst the section is deleted (including the observations recorded against it), and the other sections are renumbered.<br/>' +
		  			'After the records are updated, the page should reload.<br/>' +
		  			'<span id="recordCounter">0 of ' + (numberOfSections + 1) + '</span></p>').dialog({ title: "Outside Site", buttons: { "OK": function() { dialog.dialog('close'); }}});
		  // plus 1 is for delete
	if(typeof indiciaData.sections[section] !== "undefined"){
    $.getJSON({
      url: indiciaData.read.url + 'index.php/services/data/sample',
      data: {
        location_id: indiciaData.sections[section].id,
        mode: 'json',
        view: 'detail',
        auth_token: indiciaData.read.auth_token,
        nonce: indiciaData.read.nonce
      },
      dataType: 'jsonp',
      crossDomain: true
    })
    .done(function(sdata) {
      numberOfSamples = sdata.length;
      $('#recordCounter').html(numberOfRecordsCompleted + ' of ' + (numberOfSamples+numberOfSections + 1));
      if (typeof sdata.error==="undefined") {
        $.each(sdata, function(idx, sample) {
          numberOfRecordsCompleted++;
          $('#recordCounter').html(numberOfRecordsCompleted + ' of ' + (numberOfSamples+numberOfSections + 1));
          // Would post the delete here
        });
      }
    });
	}

  window.onbeforeunload = null;
  setTimeout(function(){
		    window.location.reload(true);
		},10000);
};

$(document).ready(function() {

  var doingSelection = false;

  $('#section-form').ajaxForm({
    async: false,
    dataType:  'json',
    complete: function() {
      //
    },
    success: function(data) {
      // remove exiting errors:
      $('#section-form').find('.inline-error').remove();
      $('#section-form').find('.ui-state-error').removeClass('ui-state-error');
      if(typeof data.errors !== "undefined"){
        for(field in data.errors){
          var elem = $('#section-form').find('[name=' + field + ']');
          var label = $("<label/>")
					.attr({"for":  elem[0].id, generated: true})
					.addClass('inline-error')
					.html(data.errors[field]);
	      var elementBefore = $(elem).next().hasClass('deh-required') ? $(elem).next() : elem;
          label.insertAfter(elementBefore);
          elem.addClass('ui-state-error');
        }
      } else {
        var current = $('#section-select li.selected').html();
        // store the Sref...
        indiciaData.sections[current].sref = $('#section-location-sref').val();
        indiciaData.sections[current].system = $('#section-location-system-select').val();
        alert('The section information has been saved.');
        sectionDetailsChanged = false;
      }
    }
  });

  $('#section-select li').on('click', function(evt) {
    var parts = evt.target.id.split('-');
    confirmSelectSection(parts[parts.length-1], true, true);
  });
  $('#section-form').find('input,textarea,select').on('change', function(evt) {
      sectionDetailsChanged = true;
  });

  mapInitialisationHooks.push(function(div) {
    if (div.id==='route-map') {
      $('#section-select-route li').on('click', function(evt) {
        var parts = evt.target.id.split('-');
        confirmSelectSection(parts[parts.length-1], true, false);
      });
      $('.remove-section').on('click', function(evt) {
        var current = $('#section-select-route li.selected').html();
        if (confirm(indiciaData.lang.sectionedTransectsEditTransect.sectionDeleteConfirm.replace('{1}', current))) {
          deleteSection(current);
        }
      });
      $('.insert-section').on('click', function(evt) {
        var current = $('#section-select-route li.selected').html();
        if(confirm(indiciaData.lang.sectionedTransectsEditTransect.sectionInsertConfirm.replace('{1}', current))) insertSection(current);
      });
      $('.reload-section').on('click', function(evt) {
          var current = $('#section-select-route li.selected').html();
          reloadSection(current);
        });
      $('.erase-route').on('click', function(evt) {
        var current = $('#section-select-route li.selected').html(),
            oldSection = [];
        // If the draw feature control is active unwind it one point at a time.
        for(var i = div.map.controls.length-1; i>=0; i--)
            if(div.map.controls[i].CLASS_NAME == 'OpenLayers.Control.DrawFeature' && div.map.controls[i].active) {
              if(div.map.controls[i].handler.line){
                if(div.map.controls[i].handler.line.geometry.components.length == 2) // start point plus current unselected position)
                  div.map.controls[i].cancel();
                else
                  div.map.controls[i].undo();
                return;
              }
            }
        current = $('#section-select-route li.selected').html();
        // label a new feature properly (and remove the undefined that appears)
        $.each(div.map.editLayer.features, function(idx, feature) {
          if (feature.attributes.section===current) {
            oldSection.push(feature);
          }
        });
        if (oldSection.length>0 && oldSection[0].geometry.CLASS_NAME==="OpenLayers.Geometry.LineString") {
          if (!confirm('Do you wish to erase the route for this section?')) {
            return;
          } else {
            div.map.editLayer.removeFeatures(oldSection, {});
          }
        } else return; // no existing route to clear
        if (typeof indiciaData.sections[current] === 'undefined') {
          return; // not currently stored in database
        }
        indiciaData.sections[current].sectionLen = 0;
        // have to leave the location in the website (data may have been recorded against it), but can't just empty the geometry
        var data = {
          'location:boundary_geom':'',
          'location:centroid_geom':oldSection[0].geometry.getCentroid().toString(),
          'location:id':indiciaData.sections[current].id,
          'website_id':indiciaData.website_id
        };
        $.post(
          indiciaData.ajaxFormPostUrl,
          data,
          function(data) {
            if (typeof(data.error)!=="undefined") {
              alert(data.error);
            } else {
              // Better way of doing this?
              var current = $('#section-select-route li.selected').html();
              $('#section-select-route-' + current).addClass('missing');
              $('#section-select-' + current).addClass('missing');
            }
            // recalculate total transect length
            if (typeof indiciaData.autocalcTransectLengthAttrId != 'undefined' &&
            		indiciaData.autocalcTransectLengthAttrId &&
            		indiciaData.autocalcSectionLengthAttrId) {
            	// add all sections lengths together
            	var transectLen = findTotalSectionLength();
              var ldata = {'location:id':$('#location\\:id').val(), 'website_id':indiciaData.website_id};
            	// load into form.
              $('#locAttr\\:' + indiciaData.autocalcTransectLengthAttrId).val(transectLen);
              ldata['locAttr:' + indiciaData.autocalcTransectLengthAttrId] = transectLen;
              $.post(indiciaData.ajaxFormPostUrl, ldata, function(data) {
                if (typeof(data.error)!=="undefined") {
                  alert(data.error);
                }
              }, 'json');
            }
          },
          'json'
        );

      });
      div.map.parentLayer = new OpenLayers.Layer.Vector('Transect Square', {
        style: div.map.editLayer.styleMap.styles.default.defaultStyle,
        sphericalMercator: true,
        displayInLayerSwitcher: true
      });
      div.map.addLayer(div.map.parentLayer);
      // If there are any features in the editLayer without a section number, then this is the transect square feature, so move it to the parent layer,
      // otherwise it will be selectable and will prevent the route features being clicked on to select them. Have to do this each time the tab is displayed
      // else any changes in the transect will not be reflected here.
      function copy_over_transects()
      {
        $.each(div.map.editLayer.features, function(idx, elem){
          if(typeof elem.attributes.section == "undefined"){
            div.map.parentLayer.destroyFeatures();
            div.map.editLayer.removeFeatures([elem]);
            div.map.parentLayer.addFeatures([elem]);
          }
        });
      }
      copy_over_transects();
      indiciaFns.bindTabsActivate($('.ui-tabs'), function(event, ui) {
        function _extendBounds(bounds, buffer) {
            var dy = (bounds.top-bounds.bottom) * buffer;
            var dx = (bounds.right-bounds.left) * buffer;
            bounds.top = bounds.top + dy;
            bounds.bottom = bounds.bottom - dy;
            bounds.right = bounds.right + dx;
            bounds.left = bounds.left - dx;
            return bounds;
        }

        var div, target = (typeof ui.newPanel==='undefined' ? ui.panel : ui.newPanel[0]);
        if((div = $('#' + target.id + ' #route-map')).length > 0){
          copy_over_transects();
          div = div[0];
          // when the route map is initially created it is hidden, so is not rendered, and the calculations of the map size are wrong
          // (Width is 100 rather than 100%), so any initial zoom in to the transect by the map panel is wrong.
          var bounds = div.map.parentLayer.getDataExtent();
          if(div.map.editLayer.features.length>0)
            bounds.extend(div.map.editLayer.getDataExtent());
          _extendBounds(bounds,div.settings.maxZoomBuffer);
          div.map.zoomToExtent(bounds);
        }
      });
      // find the selectFeature control so we can interact with it later
      $.each(div.map.controls, function(idx, control) {
        if (control.CLASS_NAME==='OpenLayers.Control.SelectFeature') {
          indiciaData.selectFeature = control;
          div.map.editLayer.events.on({'featureselected': function(evt) {
        	  confirmSelectSection(evt.feature.attributes.section, false, false);
          }});
        } else if (control.CLASS_NAME==='OpenLayers.Control.DrawFeature') {
          indiciaData.drawFeature = control;
        } else if (control.CLASS_NAME==='OpenLayers.Control.ModifyFeature') {
          indiciaData.modifyFeature = control;
          control.standalone = true;
          control.events.on({'activate':function() {
            indiciaData.modifyFeature.selectFeature(selectedFeature);
            div.map.editLayer.redraw();
          }});
        }
      });

      div.map.editLayer.style = null;
      const baseStyle = {
        strokeWidth: 4,
        strokeDashstyle: 'dash',
        labelOutlineColor: 'white',
        labelOutlineWidth: 3,
        fontFamily: 'Verdana, Arial, Helvetica,sans-serif',
        fontColor: '#FF0000',
        fillColor: '#FF0000'
      };
      const defaultRule = new OpenLayers.Rule({
        symbolizer: $.extend({strokeColor: '#0000FF'}, baseStyle)
      });
      const selectedRule = new OpenLayers.Rule({
        symbolizer: $.extend({strokeColor: '#FFFF00'}, baseStyle)
      });
      const labelRule = new OpenLayers.Rule({
        filter: new OpenLayers.Filter.Comparison({
          type: OpenLayers.Filter.Comparison.EQUAL_TO,
          property: 'type',
          value: 'sectionMidpoint'
        }),
        symbolizer: $.extend({
          fontSize: '16px',
          fontWeight: 'bold',
          label : '${section}',
          labelAlign: 'cm',
        }, baseStyle)
      });
      const sectionEndpointRule = new OpenLayers.Rule({
        // Restrict the label style to the type boundary lines, as this
        // excludes the virtual edges created during a feature modify.
        filter: new OpenLayers.Filter.Comparison({
          type: OpenLayers.Filter.Comparison.EQUAL_TO,
          property: 'type',
          value: 'sectionEndpoint'
        }),
        symbolizer: {
          pointRadius: 4,
          strokeOpacity: 0.4,
          fillOpacity: 1,
          strokeWidth: 2,
          strokeDashstyle: 'solid'
        }
      });
      const defaultStyle = new OpenLayers.Style();
      const selectedStyle = new OpenLayers.Style();
      defaultStyle.addRules([defaultRule, labelRule, sectionEndpointRule]);
      selectedStyle.addRules([selectedRule, labelRule, sectionEndpointRule]);
      div.map.editLayer.styleMap = new OpenLayers.StyleMap({
        'default': defaultStyle,
        'select': selectedStyle
      });
      let sectionsDrawn = 0;
      // Add the loaded section geoms to the map. Do this before hooking up to the featureadded event.
      $.each(indiciaData.sections, function(idx, section) {
        const sectionFeature = new OpenLayers.Feature.Vector(OpenLayers.Geometry.fromWKT(section.geom), {
          section: 'S' + idx.substr(1),
          type: 'boundary'
        });
        div.map.editLayer.addFeatures([sectionFeature]);
        addSectionLabelFeatures(sectionFeature);
        sectionsDrawn++;
      });

      // Select the first section and zoom to show the sections.
      confirmSelectSection('S1', true, false);
      if (sectionsDrawn > 0) {
        div.map.zoomToExtent(div.map.editLayer.getDataExtent());
      }

      /**
       * Add features to attach section label and start marker to a section.
       */
      function addSectionLabelFeatures(sectionFeature) {
        let measuredLength = 0;
        // Measure each edge in a section's line to find the half-way point, so
        // we can attach the main section label.
        var geomToCentreLabelOn;
        for (var i = 0; i < sectionFeature.geometry.components.length - 1; i++) {
          var thisLineLength = sectionFeature.geometry.components[i].distanceTo(sectionFeature.geometry.components[i + 1]);
          if (measuredLength + thisLineLength >= sectionFeature.geometry.getLength() / 2) {
            // Calculate ratio along this line that the half way crossing point is.
            var ratioAlongThisLine = ((sectionFeature.geometry.getLength() / 2) - measuredLength) / thisLineLength;
            var x = sectionFeature.geometry.components[i].x + ratioAlongThisLine * (sectionFeature.geometry.components[i + 1].x - sectionFeature.geometry.components[i].x);
            var y = sectionFeature.geometry.components[i].y + ratioAlongThisLine * (sectionFeature.geometry.components[i + 1].y - sectionFeature.geometry.components[i].y);
            geomToCentreLabelOn = OpenLayers.Geometry.fromWKT('POINT(' + x + ' ' + y + ')');
            break;
          }
          measuredLength += thisLineLength;
        }
        // Main label attached to mid-point geometry.
        const label = new OpenLayers.Feature.Vector(geomToCentreLabelOn, {
          section: sectionFeature.attributes.section,
          type: 'sectionMidpoint'
        });
        // Start marker can attach to first component in section geom. End
        // marker to the last.
        const startMarker = new OpenLayers.Feature.Vector(sectionFeature.geometry.components[0], {
          section: sectionFeature.attributes.section,
          type: 'sectionEndpoint'
        });
        const endMarker = new OpenLayers.Feature.Vector(sectionFeature.geometry.components[sectionFeature.geometry.components.length - 1], {
          section: sectionFeature.attributes.section,
          type: 'sectionEndpoint'
        });
        div.map.editLayer.addFeatures([
          label,
          startMarker,
          endMarker
        ]);
      }

      /**
       * Ensure the section IDs are updated after addition of a new section.
       *
       * These are used to ensure name changes are synchronised.
       */
      function updateSectionIdsList() {
        let sectionIds = {};
        $.each(indiciaData.sections, function(code, obj) {
          sectionIds[code] = obj.id;
        });
        $('[name="section_ids"]').val(JSON.stringify(sectionIds));
      }

      function featureChangeEvent(evt) {
        // Only handle lines - as things like the sref control also trigger feature change events
        if (evt.feature.geometry.CLASS_NAME==="OpenLayers.Geometry.LineString") {
          var oldSection = [];
          // Find section attribute if existing, or selected section button if
          // new.
          const current = (typeof evt.feature.attributes.section === 'undefined') ? $('#section-select-route li.selected').html() : evt.feature.attributes.section;
          // Label a new feature properly (and remove the undefined that
          // appears).
          evt.feature.attributes = {
            section: current,
            type: 'boundary'
          };
          $.each(evt.feature.layer.features, function(idx, feature) {
            if (feature.attributes.section===current && feature !== evt.feature) {
              oldSection.push(feature);
            }
          });
          if (oldSection.length>0) {
            if (!confirm('Would you like to replace the existing section with the new one?')) {
              evt.feature.layer.removeFeatures([evt.feature], {});
              return;
            } else {
              evt.feature.layer.removeFeatures(oldSection, {});
            }
          }
          addSectionLabelFeatures(evt.feature);
          // Make sure the feature is selected: this ensures that it can be
          // modified straight away. Note that selecting or unselecting the
          // feature triggers the afterfeaturemodified event.
          if (selectedFeature != evt.feature) {
            indiciaData.selectFeature.select(evt.feature);
            selectedFeature = evt.feature;
            div.map.editLayer.redraw();
          }
          // post the new or edited section to the db
          var data = {
            'location:code':current,
            'location:name':$('#location\\:name').val() + ' - ' + current,
            'location:parent_id':$('#location\\:id').val(),
            'location:boundary_geom':evt.feature.geometry.toString(),
            'location:location_type_id':indiciaData.sectionTypeId,
            'website_id':indiciaData.website_id
          };
          if (typeof indiciaData.sections[current] === 'undefined') {
            // First save, so need to link website.
            data['locations_website:website_id'] = indiciaData.website_id;
            indiciaData.sections[current] = {};
            indiciaData.insertingSection = true;
          } else {
            data['location:id']=indiciaData.sections[current].id;
          }
          if (indiciaData.defaultSectionGridRef==='parent') {
            // Initially set the section Sref etc to match the parent. Geom
            // will be auto generated on the server.
            indiciaData.sections[current].sref = $('#imp-sref').val()
            indiciaData.sections[current].system = $('#imp-sref-system').val();
          } else if (indiciaData.defaultSectionGridRef.match(/^section(Centroid|Start)100$/)) {
            if (typeof indiciaData.srefHandlers!=="undefined" &&
                typeof indiciaData.srefHandlers[$('#imp-sref-system').val().toLowerCase()]!=="undefined") {
              var handler = indiciaData.srefHandlers[$('#imp-sref-system').val().toLowerCase()], pt, sref;
              if (indiciaData.defaultSectionGridRef==='sectionCentroid100') {
                pt = selectedFeature.geometry.getCentroid(true); // must use weighted to accurately calculate
              } else {
                pt = jQuery.extend({}, selectedFeature.geometry.components[0]);
              }
              sref = handler.pointToGridNotation(pt.transform(indiciaData.mapdiv.map.projection, 'EPSG:' + handler.srid), 6);
              indiciaData.sections[current].sref = sref;
              indiciaData.sections[current].system = $('#imp-sref-system').val();
            }
          }
          indiciaData.sections[current].geom = evt.feature.geometry.toString();
          data['location:centroid_sref'] = indiciaData.sections[current].sref;
          data['location:centroid_sref_system'] = indiciaData.sections[current].system;
          // autocalc section length
          if (indiciaData.autocalcSectionLengthAttrId) {
        	var sectionLen = Math.round(selectedFeature.geometry.clone().transform(indiciaData.mapdiv.map.projection, 'EPSG:27700').getLength());
            data[$('#locAttr\\:' + indiciaData.autocalcSectionLengthAttrId).attr('name')] = sectionLen;
            indiciaData.sections[current].sectionLen = sectionLen;
          }
          $.post(
            indiciaData.ajaxFormPostUrl,
            data,
            function(data) {
              if (typeof(data.error)!=="undefined") {
                alert(data.error);
              } else {
                // Better way of doing this?
                // @todo Check not changing if exists
                indiciaData.sections[current].id = data.outer_id;
                updateSectionIdsList();
                indiciaData.insertingSection = false;
                $('#section-location-id').val(data.outer_id);
                $('#section-select-route-' + current).removeClass('missing');
                $('#section-select-' + current).removeClass('missing');
                loadSectionDetails(current); // this will load the newly calculate section length into the form field.

                if (typeof indiciaData.autocalcTransectLengthAttrId != 'undefined' &&
                      indiciaData.autocalcTransectLengthAttrId &&
                      indiciaData.autocalcSectionLengthAttrId) {
                  // add all sections lengths together
                  var transectLen = findTotalSectionLength();
                  // set the transect length attribute on local form, in case the transect tab is saved
                  $('#locAttr\\:' + indiciaData.autocalcTransectLengthAttrId).val(transectLen);
                  // save the attribute value into the warehouse, in case transect tab is not saved.
                  transectLengthFormData = {
                    'location:id': $('#location\\:id').val(),
                    'website_id': indiciaData.website_id
                  };
                  transectLengthFormData['locAttr:' + indiciaData.autocalcTransectLengthAttrId] = transectLen;
                  $.post(indiciaData.ajaxFormPostUrl, transectLengthFormData, function(response) {
                    if (typeof(response.error)!=="undefined") {
                      alert(response.error);
                    }
                  }, 'json');
                }
              }
            },
            'json'
          );
        }
      }
      div.map.editLayer.events.on({
        'featureadded': featureChangeEvent,
        'afterfeaturemodified': featureChangeEvent
      });
    }
  });

  $('#add-user').on('click', function(evt) {
    var user=($('#cmsUserId')[0]).options[$('#cmsUserId')[0].selectedIndex];
    if ($('#user-' + user.value).length===0) {
      $('#user-list').append('<tr><td id="user-' + user.value + '"><input type="hidden" name="locAttr:' + indiciaData.locCmsUsrAttr + '::' + user.value + '" value="' + user.value + '"/>' +
          user.text + '</td><td><div class="ui-state-default ui-corner-all"><span class="remove-user ui-icon ui-icon-circle-close"></span></div></td></tr>');
    }
  });

  indiciaFns.on('click', '.remove-user', [], function(evt) {
    $(evt.target).closest('tr').css('text-decoration','line-through');
    $(evt.target).closest('tr').addClass('ui-state-disabled');
    // clear the underlying value
    $(evt.target).closest('tr').find('input').val('');
  });

  $('#add-branch-coord').on('click', function(evt) {
    var coordinator=($('#branchCmsUserId')[0]).options[$('#branchCmsUserId')[0].selectedIndex];
    if ($('#branch-coord-' + coordinator.value).length===0) {
      $('#branch-coord-list').append('<tr><td id="branch-coord-' + coordinator.value + '">' +
          '<input type="hidden" name="locAttr:' + indiciaData.locBranchCmsUsrAttr + '::' + coordinator.value + '" value="' + coordinator.value + '"/>' + coordinator.text + '</td>' +
          '<td><div class="ui-state-default ui-corner-all"><span class="remove-user ui-icon ui-icon-circle-close"></span></div></td></tr>');
    }
  });

  if (indiciaData.checkLocationNameUnique) {
    // Track location name changes and check for uniqueness.
    $('#location\\:name').on('change', function() {
      // Build report request to find duplicates.
      const reportApiUrl = indiciaData.warehouseUrl + 'index.php/services/report/requestReport';
      const report = 'library/locations/find_duplicate_names.xml';
      const params = {
        auth_token: indiciaData.read.auth_token,
        nonce: indiciaData.read.nonce,
        mode: 'json',
        reportSource: 'local',
        report: report,
        wantCount: 1,
        wantRecords: 0,
        name: $('#location\\:name').val(),
        location_type_id: $('[name="location\\:location_type_id"]').val(),
        website_id: indiciaData.website_id
      };
      if ($('#location\\:id').val()) {
        // Existing location, so no need to check self.
        params.exclude_location_id = $('#location\\:id').val();
      }
      $.ajax({
        url: reportApiUrl,
        data: params,
        dataType: 'jsonp',
        crossDomain: true
      }).done(function(data) {
        if (data.count > 0) {
          $('#input-form [type="submit"]').prop('disabled', true);
          $('<p for="location:name" class="inline-error" id="unique-warning">' + indiciaData.lang.sectionedTransectsEditTransect.duplicateNameWarning + '</p>').insertAfter($('#ctrl-wrap-location-name'));
        } else {
          $('#input-form [type="submit"]').prop('disabled', false);
          $('#unique-warning').remove();
        }
      });
    });
  }
});
}(jQuery));