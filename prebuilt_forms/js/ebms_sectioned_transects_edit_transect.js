/**
 * Public functions
 */
var clearSection, loadSectionDetails, confirmSelectSection, selectSection, deleteWalks,
    deleteLocation, deleteSections, updateTransectDetails, countryChange;

var defaultLayers = null,
    defaultRouteLayers = null;

MyMousePositionControl=OpenLayers.Class(
  OpenLayers.Control.MousePosition,
  {
    formatOutput: function(lonLat) {

        var digits = parseInt(this.numDigits),
            newHtml, lat, latDeg, latMin, latSec, long, longDeg, longMin, longSec,
            LLFormat = 'DMS'; // 'D' = Decimal degrees, 'DM' = Degrees + Decimal Minutes, 'DMS' = Degrees, Minutes + Decimal Seconds
        switch(this.displayProjection.projCode) {
          case 'EPSG:2169': // Lux
              newHtml =
                  'LUREF ' +
                  lonLat.lon.toFixed(0) +
                  ' ' +
                  lonLat.lat.toFixed(0);
              break;
          case 'EPSG:4326':
              latDeg = Math.abs(lonLat.lat);
              latMin = (latDeg - Math.floor(latDeg))*60
              latSec = (latMin - Math.floor(latMin))*60
              longDeg = Math.abs(lonLat.lon);
              longMin = (longDeg - Math.floor(longDeg))*60
              longSec = (longMin - Math.floor(longMin))*60
              switch (LLFormat) {
                case 'DM' :
                    lat = Math.floor(latDeg)+'&#176;' +
                          (latMin.toFixed(3) < 10 ? '0' : '') + latMin.toFixed(3);
                    long = Math.floor(longDeg)+'&#176;' +
                          (longMin.toFixed(3) < 10 ? '0' : '') + longMin.toFixed(3);
                    break;
                case 'DMS' :
                    lat = Math.floor(latDeg)+'&#176;' +
                          (latMin < 10 ? '0' : '') + Math.floor(latMin) + '&apos;' +
                          (latSec.toFixed(1) < 10 ? '0' : '') +latSec.toFixed(1) ;
                    long = Math.floor(longDeg)+'&#176;' +
                          (longMin < 10 ? '0' : '') + Math.floor(longMin) + '&apos;' +
                          (longSec.toFixed(1) < 10 ? '0' : '') +longSec.toFixed(1) ;
                    break;
                default : // 'D'
                    lat = latDeg.toFixed(5);
                    long = longtDeg.toFixed(5);
                    break;
              }
              newHtml =
                  'LatLong ' +
                  (lonLat.lat > 0 ? 'N' : 'S') +
                  lat +
                  ' ' +
                  (lonLat.lon > 0 ? 'E' : 'W') +
                  long;
              break;
          default:
              var newHtml =
                  this.prefix +
                  lonLat.lon.toFixed(digits) +
                  this.separator +
                  lonLat.lat.toFixed(digits) +
                  this.suffix;
              break;
        }
        return newHtml;
    },
    CLASS_NAME:'MyMousePositionControl'
  }
);

/**
 * i18n
 * Make so can't delete last section
 * Delete a transect
 * Special map functionality (Luxembourg)
 * Error checking in Ajax php functions
 * Location Type functionality (not really used for EBMS)
 */

(function ($) {

  /**
   * This global initialisation function must run after all the settings have been set up. With the ready functions
   * being run in the order they are defined, the call to this function has to be set up in the php form at the very
   * end, when everything else has been run.
   */
  bind_events = function() {

    var version=$.ui.version.split('.'),
          beforeActivateEvent=(version[0]==='1' && version[1]<9) ? 'select' : 'beforeActivate'
          activateEvent=(version[0]==='1' && version[1]<9) ? 'tabsshow' : 'tabsactivate'
          opts={};
    opts[beforeActivateEvent] = check_leaving_tab_handler;
    $('.ui-tabs').tabs(opts);
    $('.ui-tabs').bind(activateEvent, sort_map_on_tab_activate_handler);

    indiciaData.ignoreChanges = false;
    indiciaData.transectDetailsChanged = false;
    $('#input-form').find('input,textarea,select').change(function(evt) { indiciaData.transectDetailsChanged = true; });
    $('#'+indiciaData.settings.countryAttr.id.replace(/:/g,'\\:')).change(function(evt){ countryChange(true); });
    $('#imp-sref').blur(edit_sref_handler);

    $('#add-user').click(add_user_click_handler);
    $( document ).on( 'click', '.remove-user', remove_user_click_handler);
    $('#add-branch-coord').click(add_branch_coordinator_click_handler);
    $('#delete-site').click(delete_site_handler);

    // the following is for both the route and detail tabs: not always present
    $('#section-select-route li,#section-select li').click(section_select_change_handler);

    // the following are for the route tabs: not always present
    $('.insert-section').click(insert_section_click_handler);
    $('.erase-route').click(erase_route_click_handler);
    $('.save-route').click(save_route_click_handler);
    $('.complete-route-details').click(complete_route_details_click_handler);
    $('.remove-section').click(remove_section_click_handler);

    // the following are for the section detail tabs: not always present
    $('#section-form').find('input,textarea,select').change(function(evt) {
        indiciaData.sectionDetailsChanged = true;
    });
    $('#section-form').ajaxForm({ // pressed save on the form details page.
        dataType:  'json',
        success: section_form_ajax_success,
    });


    // locTypeChange(null);

    if($('#location-id').length >0)
      selectSection('S1', true);
  }

  /*
   * The following are the general functions, appropriate to more than one tab.
   */
  check_leaving_tab_handler=function(event, ui) {
    var section = $('#section-select-route li.selected').html();

    switch(ui.oldTab[0].id) {
      case 'site-details-tab' :
          tabinputs = $('#input-form').find('input,select,textarea').not(':disabled,[name=""],.inactive');
          if (tabinputs.length>0 && !tabinputs.valid()) {
            alert(indiciaData.langErrorsOnTab);
            return false;
          }
          if(!indiciaData.transectDetailsChanged || indiciaData.ignoreChanges) {
            indiciaData.ignoreChanges = false;
            return true;
          }
          dialog = $('<p>You have changed the data for the transect, but not saved it.</p>').dialog(
                { title: "Data changed",
                  buttons: { "Save data":  function() {
                          $('#input-form').submit(); // validation already run: non ajax form than will reload the form.
                        },
                      "Continue without saving":  function() {
                          indiciaData.ignoreChanges=true;
                          $('#' + ui.newTab[0].id + ' a').click();
                          $(this).dialog('close');
                        },
                      "Cancel":  function() {
                          $(this).dialog('close');
                        },
                    },
                  classes: {"ui-dialog": "above-map"}});
          return false;
      case 'your-route-tab' :
          var drawingChanged = (typeof indiciaData.drawFeature !== "undefined" &&
                  indiciaData.drawFeature.active &&
                  indiciaData.drawFeature.handler.line != null &&
                  indiciaData.drawFeature.handler.line.geometry.components.length > 0);
          if(typeof indiciaData.modifyFeature !== "undefined" &&
                  indiciaData.modifyFeature.active && indiciaData.modifyFeature.modified) {
            indiciaData.routeChanged = true;
          }
          if(!(indiciaData.routeChanged || drawingChanged) || indiciaData.ignoreChanges){
            indiciaData.ignoreChanges = false;
            return true;
          }
          dialog = $('<p>You have changed the route, but not saved it. Do you wish to save the route before moving to the next tab?</p>').dialog(
                { title: "Route changed",
                  buttons: { "Save route first":  function() {
                          $(this).dialog('close');
                          saveRoute(function() {
                            $('#' + ui.newTab[0].id + ' a').click();
                          });
                        },
                      "Continue without saving":  function() {
                          if(drawingChanged) // we delete any partially drawn lines.
                            indiciaData.drawFeature.cancel();
                          indiciaData.ignoreChanges=true;
                          $('#' + ui.newTab[0].id + ' a').click();
                          $(this).dialog('close');
                        },
                      "Cancel":  function() {
                          $(this).dialog('close');
                        },
                    },
                  classes: {"ui-dialog": "above-map"}});
          return false;
      default:
          break;
    }
    return true;
  }

  var sort_map_on_tab_activate_handler = function(event, ui) {
    var div,
        target = (typeof ui.newPanel==='undefined' ? ui.panel : ui.newPanel[0]),
        section = $('#section-select-route li.selected').html();
    indiciaData.ignoreChanges = false;
    if ((div = $('#'+target.id+' #route-map')).length > 0) { // Your Route map is being displayed on this tab
        div = div[0]; // equivalent of indiciaData.mapdiv
        copy_over_transects();
        resetMap(div, true, false, true) // when redisplaying route map, include the transect itself.
        // Reset all the controls
        indiciaData.navControl.deactivate(); // Nav control always exists
        if(typeof indiciaData.modifyFeature !== "undefined")
          indiciaData.modifyFeature.deactivate();
        if(typeof indiciaData.selectFeature !== "undefined") {
            indiciaData.selectFeature.deactivate();
            indiciaData.selectFeature.unselectAll();
          }
        if(typeof indiciaData.drawFeature !== "undefined") {
          indiciaData.drawFeature.activate();
        }
        indiciaData.currentFeature = null;
        indiciaData.previousGeometry = null;
        $.each(div.map.editLayer.features, function(idx, feature) {
          if (feature.attributes.section===section) {
            indiciaData.drawFeature.deactivate();
            indiciaData.currentFeature = feature;
            indiciaData.previousGeometry = feature.geometry.clone();
            if(typeof indiciaData.modifyFeature !== "undefined")
              indiciaData.modifyFeature.activate();
          }
        });
    } else if ((div = $('#'+target.id+' #map')).length > 0) { // Main map is being displayed on this tab
      div = div[0]; // equivalent of indiciaData.mapdiv
      resetMap(div, false, true, true); // when redisplaying main map, dont include the country.
    }
  };


  /*
   * The following are the functions for the Main site tab
   */
  var edit_sref_handler = function(evt) {
    var myVal = $('#imp-sref').val(),
        mainMapDiv = $('#map')[0];

    if (myVal==='') {
      mainMapDiv.map.editLayer.destroyFeatures();
    }
  }

  var add_user_click_handler = function(evt) {
    var user=($('#cmsUserId')[0]).options[$('#cmsUserId')[0].selectedIndex];
    if ($('#user-'+user.value).length===0) {
      $('#user-list').append('<tr><td id="user-'+user.value+'"><input type="hidden" name="locAttr:'+indiciaData.locCmsUsrAttr+'::'+user.value+'" value="'+user.value+'"/>'+
          user.text+'</td><td><div class="ui-state-default ui-corner-all"><span class="remove-user ui-icon ui-icon-circle-close"></span></div></td></tr>');
    } else { // undo any delete
      $('#user-'+user.value).closest('tr').removeClass('ui-state-disabled').css('text-decoration','');
      $('#user-'+user.value).find('input').val(user.value);
    }
    indiciaData.transectDetailsChanged = true;
  };

  var add_branch_coordinator_click_handler = function(evt) {
    var coordinator=($('#branchCmsUserId')[0]).options[$('#branchCmsUserId')[0].selectedIndex];
    if ($('#branch-coord-'+coordinator.value).length===0) {
      $('#branch-coord-list').append('<tr><td id="branch-coord-'+coordinator.value+'">' +
          // TODO ??? replace locBranchCmsUsrAttr??
          '<input type="hidden" name="locAttr:'+indiciaData.locBranchCmsUsrAttr+'::'+coordinator.value+'" value="'+coordinator.value+'"/>'+coordinator.text+'</td>'+
          '<td><div class="ui-state-default ui-corner-all"><span class="remove-user ui-icon ui-icon-circle-close"></span></div></td></tr>');
    } else { // undo any delete
        $('#branch-coord-'+coordinator.value).closest('tr').removeClass('ui-state-disabled').css('text-decoration','');
        $('#branch-coord-'+coordinator.value).find('input').val(coordinator.value);
    }
    indiciaData.transectDetailsChanged = true;
  };

  delete_site_handler = function(evt){
    var urlSep = indiciaData.ajaxUrl.indexOf('?') === -1 ? '?' : '&';
    if(confirm((indiciaData.settings.walks.len > 0 ? '' + indiciaData.settings.walks.len +
                  ' walks will also be deleted when you delete this location. ' : '') +
                'Are you sure you wish to delete this location? Please note, this may take some time: please ' +
                'wait until the page reloads automatically.')) {
      $.ajax({
          url: indiciaData.ajaxUrl + '/deleteSite/' + indiciaData.nid + urlSep + 'id=' + $('#location-id').val(),
          dataType: 'json',
          success: function (response) {
              window.onbeforeunload = null;
              setTimeout(function() { window.location = indiciaData.sitesListPath; });
          }
      });
    };
  };

  var remove_user_click_handler = function(evt) { // used for both normal and branch/country users
    $(evt.target).closest('tr').css('text-decoration','line-through');
    $(evt.target).closest('tr').addClass('ui-state-disabled');
    // clear the underlying value
    $(evt.target).closest('tr').find('input').val('');
  };

  countryChange = function(checkOutside) {
    var myVal = $('#'+indiciaData.settings.countryAttr.id.replace(/:/g,'\\:')).val(),
        mainMapDiv = $('#map')[0];

    _countryChangeEnd = function(div, oldMapExtent, reproject, oldProjection) {
        var infoExtent = div.map.infoLayer.getDataExtent(),
            bounds;

        if(div.map.editLayer.features.length > 0) {
          // keep map extent the same if a site already entered.
          if(reproject) {
            bounds = oldMapExtent.transform(oldProjection, div.map.projection);
            div.map.zoomToExtent(bounds, true);
            if(!mainMapDiv.map.getExtent().intersectsBounds(mainMapDiv.map.baseLayer.maxExtent))
              div.map.zoomToExtent(mainMapDiv.map.baseLayer.maxExtent, true);
          }
        } else if(infoExtent !== null)
          div.map.zoomToExtent(infoExtent);
        else
          mainMapDiv.map.setCenter(div.map.defaultLayers.centre, div.map.defaultLayers.zoom, false, true);
      }

    // empty info layer on main map: infor layer holds the country on the main map, and the transect on the route map.
    mainMapDiv.map.infoLayer.destroyFeatures();

    // record the extent of the edit layer on the main map. The route map is not being displayed and that is re-zoomed on change of tab. Possibly empty.
    mapExtent = mainMapDiv.map.getExtent();
    oldEditLayerProj = mainMapDiv.map.editLayer.projection;

    // scan config to get new maps.
    if(_setDefaultLayers(mainMapDiv)) { // returns false if no valid baselayer.
      details = _setBaseLayers(mainMapDiv); // returns object : index into country array for map data, or false if default.
      // reproject any edit layer geometries: main map.
      if(details.reproject)
        _convertGeometries(oldEditLayerProj.projCode, mainMapDiv.map.editLayer); // country infoLayer is empty
      _resetMap(mainMapDiv, true, false, details.reproject || mainMapDiv.map.editLayer.features.length == 0);
    } else return;

    // set up sref system options
    system = $('#imp-sref-system').val();
    $('#imp-sref-system option').addClass('working').attr('disabled',false);
    list = (details.index !== false ? indiciaData.settings.country_configurations[details.index].map.sref_systems.split(',') : indiciaData.settings.defaultSystems);
    $.each(list, function(idx, system){ $('#imp-sref-system option[value='+system+']').removeClass('working'); });
    $('#imp-sref-system option.working').removeClass('working').attr('disabled','disabled');
    if($('#imp-sref-system option[value='+system+']:enabled').length == 0) {
      $('#imp-sref-system').val($('#imp-sref-system option:enabled').first().val());
    } else {
      $('#imp-sref-system').val(system);
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

    if (myVal != ''){
      // first put the country on the map
      $('#'+indiciaData.settings.countryAttr.id.replace(/:/g,'\\:')).addClass("working1");
      $.getJSON(indiciaData.indiciaSvc + "index.php/services/data/location/" + myVal +
                    '?mode=json&view=detail' +
                    '&auth_token='+indiciaData.readAuth.auth_token+
                    '&reset_timeout=true&nonce='+indiciaData.readAuth.nonce,
        function(ldata) {
          $.each(ldata, function(idx, country){ // only expecting 1 record
            var parser = new OpenLayers.Format.WKT(),
                features,
                intersects = true;
            features = parser.read(country.boundary_geom);
            if (mainMapDiv.map.infoLayer.projection.projCode!='EPSG:900913' && mainMapDiv.map.infoLayer.projection.projCode!='EPSG:3857') {
              var cloned = features.geometry.clone();
              features.geometry = cloned.transform(new OpenLayers.Projection('EPSG:3857'), mainMapDiv.map.infoLayer.projection.projCode);
            }
            if (!Array.isArray(features)) features = [features];
            mainMapDiv.map.infoLayer.addFeatures(features);

            if(!checkOutside) return;

            $.each(mainMapDiv.map.editLayer.features, function(idx1, editFeature) {
                if(typeof editFeature.attributes.temp !== 'undefined' &&  editFeature.attributes.temp == true)
                    return;
                intersects = false;
                $.each(mainMapDiv.map.infoLayer.features, function(idx2, countryFeature) {
                    intersects |= countryFeature.geometry.intersects(editFeature.geometry.getCentroid());
                });
            });
            if(!intersects) {
              $('<p>The transect is outside the newly selected country.</p>').dialog(
                    { title: "Transect outside country",
                      buttons: { "OK":  function() { $(this).dialog('close'); } },
                      classes: {"ui-dialog": "above-map"}});
            }
          });
          $('#'+indiciaData.settings.countryAttr.id.replace(/:/g,'\\:')).removeClass("working1");
          _countryChangeEnd(mainMapDiv, mapExtent, details.reproject, oldEditLayerProj);
        }
      );
      // Update Code
      // Next recalculate the code: only do this if the site is new
      if($('#location-id').length==0) {
          // The calculation of the index is now transferred to the submission function on the page, as this is done
          // at the last moment: prevents duplicates.
          var code = indiciaData.autogeneratePrefix +
              $('#'+indiciaData.settings.countryAttr.id.replace(/:/g,'\\:')+' option:selected').text() +
              indiciaData.autogeneratePrefix.substring(indiciaData.autogeneratePrefix.length-1) +
              '[INDEX]';
          $('#location-code').val(code);
        }
    } else
        _countryChangeEnd(mainMapDiv, mapExtent, details.reproject, oldEditLayerProj);
  }

  /*
   * The following functions are common between the route and section details tabs.
   */
  var section_select_change_handler = function(evt) {
    var parts = evt.target.id.split('-');
    confirmSelectSection(parts[parts.length-1], true);
  };

  confirmSelectSection = function(section, doFeature) {
    var current = $('#section-select-route li.selected').html(),
        mapDiv = $('#route-map')[0];
    if(current == section) return;
    // continue to save if the route is either unchanged or user says no.
    if(indiciaData.routeChanged === true || indiciaData.sectionDetailsChanged === true) {
      var buttons = {};
      buttons[indiciaData.langStrings.Cancel] = function() {
            $(this).dialog('close');
          };
      buttons[indiciaData.langStrings.Continue] =function() {
            $(this).dialog('close');
            indiciaData.sectionDetailsChanged === false;
            indiciaData.routeChanged = false;
            mapDiv.map.editLayer.removeFeatures([indiciaData.currentFeature]);
            if(indiciaData.previousGeometry == null) {
              if(typeof indiciaData.drawFeature !== "undefined")
                  indiciaData.drawFeature.activate();
            } else {
              indiciaData.currentFeature.geometry = indiciaData.previousGeometry;
              mapDiv.map.editLayer.addFeatures([indiciaData.currentFeature]);
              if(typeof indiciaData.modifyFeature !== "undefined")
                indiciaData.modifyFeature.activate();
            }
            mapDiv.map.editLayer.redraw();
            selectSection(section, doFeature);  // overwrite the details.
          };
      // display dialog and drive from its button events.
      // TODO i18n title
      var dialog = $('<p>There are unsaved changes to the route and/or the section details for this currently selected section. Select &apos;Continue&apos; to discard these changes, and load the new section; choose &apos;Cancel&apos; to prevent the data load, and allow you to save the data first.</p>')
              .dialog({ title: "Data changed", buttons: buttons, classes: {"ui-dialog": "above-map"}});
    } else { // no unsaved changes on the route or the details page
      selectSection(section, doFeature);
    }
  };

  /**
   * This function does not check if the existing data needs to be saved: it just overwrites.
   */
  selectSection = function(section, doFeature) {
    // If doFeature is true, then all we need to do is select the feature on the route map.
    // This will trigger a featureSelected event which then calls this function again with the doFeature false.
    // We use this second call to change the data, being careful about recursion.
    indiciaData.routeChanged = false;
    indiciaData.sectionDetailsChanged = false;
    $('.section-select li').removeClass('selected');
    $('#section-select-route-'+section).addClass('selected');
    $('#section-select-'+section).addClass('selected');
    if(doFeature){
      // Deactivate the controls if active and unselect previous route feature.
      if(typeof indiciaData.modifyFeature !== "undefined")
        indiciaData.modifyFeature.deactivate();
      if(typeof indiciaData.drawFeature !== "undefined")
        indiciaData.drawFeature.deactivate();
      if(typeof indiciaData.selectFeature !== "undefined") {
        indiciaData.selectFeature.deactivate();
        indiciaData.selectFeature.unselectAll();
      }
      indiciaData.currentFeature = null;
      indiciaData.previousGeometry = null;
      // if we click a new route: no feature yet, also no data!
      $.each(indiciaData.mapdiv.map.editLayer.features, function(idx, feature) {
          if (feature.attributes.section===section) {
            indiciaData.currentFeature = feature;
            indiciaData.previousGeometry = feature.geometry.clone();
          }
      });
      if (indiciaData.currentFeature === null) { // no featureSelected event, so we load details now.
        if(typeof indiciaData.drawFeature !== "undefined")
          indiciaData.drawFeature.activate();
        loadSectionDetails(section);
        indiciaData.routeChanged = false;
        indiciaData.sectionDetailsChanged = false;
        indiciaData.currentSection=section;
      } else {
        if(typeof indiciaData.modifyFeature !== "undefined")
          indiciaData.modifyFeature.activate();
      }
    } else {
      // doFeature = false implies that this has come from a map event. i.e. select of feature using select control
        if(typeof indiciaData.selectFeature !== "undefined") {
            indiciaData.selectFeature.deactivate();
            indiciaData.selectFeature.unselectAll();
          }
        indiciaData.currentFeature = null;
        indiciaData.previousGeometry = null;
        $.each(indiciaData.mapdiv.map.editLayer.features, function(idx, feature) {
            if (feature.attributes.section===section) {
              indiciaData.currentFeature = feature;
              indiciaData.previousGeometry = feature.geometry.clone();
            }
        });
        if(typeof indiciaData.modifyFeature !== "undefined")
            indiciaData.modifyFeature.activate();
    }
    loadSectionDetails(section);
    indiciaData.currentSection=section;
  };

  /*
   * The following functions are specific to the route tab.
   */
  var insert_section_click_handler = function(evt) {
    var current = $('#section-select-route li.selected').html();
    var urlSep = indiciaData.ajaxUrl.indexOf('?') === -1 ? '?' : '&';
    var dialog1;
    var dialog2 = '<p>Please wait whilst the subsequent sections are renumbered, ' +
        'and the number of sections counter on the Transect changed. ' +
        'The page will reload automatically when complete. ' +
        '<span class="waiting"></span></p>';
    // TODO reformat for i18n.
    var buttons = {};
    buttons[indiciaData.langStrings.Before] = function() {
        $(dialog1).dialog('close');
        // dialog2 prevents user doing anything while we wait.
        $(dialog2).dialog({
          title: "Please Wait",
          modal: true,
          closeOnEscape: false,
          open: function () { $(this).parent().find(".ui-dialog-titlebar-close").hide(); },
          classes: {"ui-dialog": "above-map"},
        });
        $.ajax({
            url: indiciaData.ajaxUrl + '/insertSection/' + indiciaData.nid + urlSep + 'section=' + current +
                    '&before=true&parent_id=' + $('#location-id').val() +
                    '&parent_location_type_id=' +  $('#location_type_id').val(),
            dataType: 'json',
            success: function (response) {
                window.onbeforeunload = null;
                setTimeout(function() { window.location.reload(); });
            },
            error: function(jqXHR, textStatus, errorThrown) {
              alert('Sorry, something went wrong. Click OK and the page' +
              'will reload.');
              window.onbeforeunload = null;
              setTimeout(function() { window.location.reload(); });
            }
          });
      };
    buttons[indiciaData.langStrings.After] =  function() {
        $(dialog1).dialog('close');
        // dialog2 prevents user doing anything while we wait.
        $(dialog2).dialog({
          title: "Please Wait",
          modal: true,
          closeOnEscape: false,
          open: function () { $(this).parent().find(".ui-dialog-titlebar-close").hide(); },
          classes: {"ui-dialog": "above-map"},
        });
        $.ajax({
              url: indiciaData.ajaxUrl + '/insertSection/' + indiciaData.nid + urlSep + 'section=' + current +
                      '&after=true&parent_id=' + $('#location-id').val() +
                      '&parent_location_type_id=' +  $('#location_type_id').val(),
              dataType: 'json',
              success: function (response) {
                  window.onbeforeunload = null;
                  setTimeout(function() { window.location.reload(); });
              },
              error: function(jqXHR, textStatus, errorThrown) {
                alert('Sorry, something went wrong. Click OK and the page' +
                'will reload.');
                window.onbeforeunload = null;
                setTimeout(function() { window.location.reload(); });
              }
            });
      };
    buttons[indiciaData.langStrings.Cancel] = function() {
        $(this).dialog('close');
      };
    dialog1 = $('<p>Do you wish it insert the new route before or after the currently selected section?</p>')
        .dialog({ title: "Insert route",
            classes: {"ui-dialog": "above-map"},
            buttons: buttons,
          });

  };

  var erase_route_click_handler = function(evt) {
    var current = $('#section-select-route li.selected').html(),
        featuresToRemove = [],
        div = $('#route-map'),
        geom,
        urlSep = indiciaData.ajaxUrl.indexOf('?') === -1 ? '?' : '&';

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
        featuresToRemove.push(feature);
      }
    });
    if (featuresToRemove.length>0 && featuresToRemove[0].geometry.CLASS_NAME==="OpenLayers.Geometry.LineString") {
      // TODO i18n
      if (!confirm('Do you wish to erase the route for this section?')) {
        return;
      }
    } else return; // no existing route to clear
    indiciaData.navControl.deactivate();
    indiciaData.modifyFeature.deactivate();
    indiciaData.drawFeature.deactivate();
    indiciaData.selectFeature.deactivate();
    indiciaData.currentFeature = null;
    div.map.editLayer.removeFeatures(featuresToRemove, {});
    indiciaData.drawFeature.activate();
    if (typeof indiciaData.sections[current]=="undefined") {
      return; // not currently stored in database
    }
    indiciaData.sections[current].sectionLen = 0;
    indiciaData.routeChanged = true;
  }; // End function for erase route click

  var save_route_click_handler = function(evt) {
    // This saves the currently selected route aginst the currently selected section.
    // We assume that user has pressed button deliberately, so no confirmation.
    if(indiciaData.currentFeature === null) return; // no feature selected so don't save
    saveRoute(function() {});
  };

  var remove_section_click_handler = function(evt) {
    var current = $('#section-select-route li.selected').html();
    var urlSep = indiciaData.ajaxUrl.indexOf('?') === -1 ? '?' : '&';
    // reformat for i18n, including buttons.
    if(confirm(indiciaData.sectionDeleteConfirm + ' ' + current + '?')) {
        // Dialog prevents user doing anything while we wait.
        $('<p>Please wait whilst the section and its walk data are removed, ' +
                'the subsequent sections are renumbered, ' +
                'the number of sections counter on the Transect changed, ' +
                'and the transect length is recalculated. ' +
                'The page will reload automatically when complete.' +
                '<span class="waiting"></span></p>')
            .dialog({
              title: "Please Wait",
              modal: true,
              closeOnEscape: false,
              open: function () { $(this).parent().find(".ui-dialog-titlebar-close").hide(); },
              classes: {"ui-dialog": "above-map"},
            });

        $.ajax({
            url: indiciaData.ajaxUrl + '/deleteSection/' + indiciaData.nid + urlSep + 'section=' + current +
                    '&parent_id=' + $('#location-id').val() +
                    '&parent_location_type_id=' +  $('#location_type_id').val(),
            dataType: 'json',
            success: function (response) {
                window.onbeforeunload = null;
                setTimeout(function() { window.location.reload(); });
            },
            error: function(jqXHR, textStatus, errorThrown) {
              alert('Sorry, something went wrong. Click OK and the page' +
              'will reload.');
              window.onbeforeunload = null;
              setTimeout(function() { window.location.reload(); });
            }
        });
    }
  };

  var complete_route_details_click_handler = function(evt) {
    indiciaFns.activeTab($('#controls'), 'section-details');
  };

  /*
   * The following functions are specific to the section details tab
   */
  /**
   * Clear the section details form: errors, values, checkboxes/radios and remove any attribute value IDs
   * local function. No external data dependancies.
   */
  var clearSection = function() {
    indiciaData.sectionDetailsChanged = false;
    $('#section-location-id,#section-location-sref,#section-location-system,#section-location-system-select').val('');
    // remove exiting errors:
    $('#section-form').find('.inline-error').remove();
    $('#section-form').find('.ui-state-error').removeClass('ui-state-error');
    // loop through form controls to make sure they do not have the value id (as these will be new values)
    $.each($('#section-form').find(':input[name]'), function(idx, ctrl) {
      var nameparts = $(ctrl).attr('name').split(':');
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

  /**
   * This only loads the section data details: this is the third tab, it does not deal
   * with the route tab.
   */
  loadSectionDetails = function(section) {
    clearSection();
    $('#section-details-transaction-id').val(section);
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
    } else { // if there is no existing route (i.e. section record), can't enter details.
      $('#section-details-tab').hide();
      $('.complete-route-details').attr('disabled','disabled');
    }
  };

  // Two ways the section form is submitted: the user presses the submit button, or the user moves away from this
  // section: in the first the data will still be for the same section, in the other, the section selector is pointing
  // to the requested section. BUT there may be validation errors, so don't load the new section until the form has
  // returned successfully.
  var section_form_ajax_success = function(data) {
    var transaction_section = data.transaction_id;
    // remove any existing errors:
    $('#section-form').find('.inline-error').remove();
    $('#section-form').find('.ui-state-error').removeClass('ui-state-error');
    if(typeof data.errors !== "undefined"){
      // reset the section selectors on route and details tabs, to point to the section which has the error.
      $('.section-select li').removeClass('selected');
      $('#section-select-route-'+transaction_section).addClass('selected');
      $('#section-select-'+transaction_section).addClass('selected');
      for(field in data.errors){
        var elem = $('#section-form').find('[name='+field.replace(/:/g,'\\:')+']');
        var label = $("<label/>").attr({"for": elem[0].id, generated: true}).addClass('inline-error').html(data.errors[field]);
        var elementBefore = $(elem).next().hasClass('deh-required') ? $(elem).next() : elem;
        label.insertAfter(elementBefore);
        elem.addClass('ui-state-error');
      }
      // TODO i18n
      jQuery('<p>The information for section '+transaction_section+' has NOT been saved. There are errors which require attention, and which have been highlighted - please correct them and re-save.</p>').dialog({ title: "Section Save Failed", buttons: { "OK": function() { $(this).dialog('close'); }}, classes: {"ui-dialog": "above-map"}});
    } else {
      // fetch and store the Sref...
      // The section form is only available if there is already a section record - generated on the route tab.
      $.getJSON(indiciaData.indiciaSvc + "index.php/services/data/location?id=" + indiciaData.sections[transaction_section].id +
                "&mode=json&view=list&auth_token=" + indiciaData.readAuth.auth_token + "&nonce=" + indiciaData.readAuth.nonce,
            function(data) {
              if(data.length>0) {
                indiciaData.sections[transaction_section].sref = data[0].centroid_sref;
                indiciaData.sections[transaction_section].system = data[0].centroid_sref_system;
              }
              // need to reset any section attribute value ids, so reload for both moved and non-moved scenarios
              var current_section = $('#section-select li.selected').html();
              loadSectionDetails(current_section);
      });
      // TODO i18n
      jQuery('<p>The information for section '+transaction_section+' has been succesfully saved.</p>').dialog({ title: "Section saved", buttons: { "OK": function() { $(this).dialog('close'); }}, classes: {"ui-dialog": "above-map"}});
    }
  };

/**
 * This saves the currently selected route against the currently selected section.
 */
saveRoute = function(callback) {
    var current = $('#section-select-route li.selected').html(),
        oldSection = [],
        saveRouteDialogText,
        geom,
        handler,
        pt;

    var drawingChanged = (typeof indiciaData.drawFeature !== "undefined" &&
            indiciaData.drawFeature.active &&
            indiciaData.drawFeature.handler.line != null &&
            indiciaData.drawFeature.handler.line.geometry.components.length > 2);
              // ignore single points - these occur when control active but nothing added so far, as mouse moves control over the map.
              // 2 points are a start point and the mouse: only one vertex so not a line yet

    // If the draw control is active, save any unfinished line.
    if(drawingChanged){
      indiciaData.drawFeature.finishSketch();
      // this triggers the feature added event
      indiciaData.drawFeature.deactivate();
      if(typeof indiciaData.modifyFeature !== "undefined")
        indiciaData.modifyFeature.activate();
    }
    // If any other control (including modify control) is active, the feature geometry is upto date: geom is correct.
    // However need to unflag any modification within the modify control.
    else if (typeof indiciaData.modifyFeature !== "undefined" &&
            indiciaData.modifyFeature.active) {
        indiciaData.modifyFeature.deactivate();
        indiciaData.modifyFeature.activate();
    }
    geom = indiciaData.currentFeature.geometry.clone();

    $('.save-route').addClass('waiting-button');

    $('#section-details-tab').show();
    $('.complete-route-details').removeAttr('disabled');

    saveRouteDialogText = 'Saving the route data for section '+current+'.<br/>';

    // Leave indiciaData.currentFeature selected
    indiciaData.previousGeometry = geom.clone();
    // Prepare data to post the new or edited section to the db
    if (indiciaData.mapdiv.map.projection.projCode!='EPSG:900913' && indiciaData.mapdiv.map.projection.projCode!='EPSG:3857') {
      geom = geom.transform(indiciaData.mapdiv.map.projection, new OpenLayers.Projection('EPSG:3857'));
    }

    var postData = {
      'location:code':current,
      'location:name':$('#location-name').val() + ' - ' + current,
      'location:parent_id':$('#location-id').val(),
      'location:boundary_geom':geom.toString(), // in 3857
      'location:location_type_id':indiciaData.sectionTypeId,
      'website_id':indiciaData.website_id,
      'transaction_id':current
    };

	var newSectionDetails = {system : $('#imp-sref-system').val()};

    if (typeof indiciaData.sections[current]!=="undefined") {
      newSectionDetails.id = indiciaData.sections[current].id;
      postData['location:id'] = indiciaData.sections[current].id;
    } else {
      saveRouteDialogText = saveRouteDialogText + 'Don&apos;t forget to enter the data on the &quot;Section Details&quot; tab for this new route.<br/>';
      postData['locations_website:website_id']=indiciaData.website_id;
    }

    var saveRouteDialog = jQuery('<p>'+saveRouteDialogText+'<span class="route-status">Saving Route...</span></p>').dialog({ title: "Saving Route", buttons: { "Hide": function() { $(this).dialog('close'); }}, classes: {"ui-dialog": "above-map"}});

    // Setup centroid grid ref and centroid geometry of section.
    // Store this in the indiciaData
    if (indiciaData.defaultSectionGridRef.match(/^section(Centroid|Start)100$/) &&
        typeof indiciaData.srefHandlers!=="undefined" &&
        typeof indiciaData.srefHandlers[$('#imp-sref-system').val().toLowerCase()]!=="undefined") {
      handler = indiciaData.srefHandlers[$('#imp-sref-system').val().toLowerCase()];
      if (indiciaData.defaultSectionGridRef==='sectionCentroid100') {
        pt = indiciaData.currentFeature.geometry.getCentroid(true); // must use weighted to accurately calculate
      } else {
        pt = jQuery.extend({}, indiciaData.currentFeature.geometry.components[0]);
      }
      newSectionDetails.sref = handler.pointToGridNotation(pt.transform(indiciaData.mapdiv.map.projection, 'EPSG:'+handler.srid), 6);
    } else if (indiciaData.defaultSectionGridRef === 'sectionCentroid1' &&
            typeof indiciaData.srefHandlers!=="undefined" &&
            typeof indiciaData.srefHandlers[$('#imp-sref-system').val().toLowerCase()]!=="undefined") {
      handler = indiciaData.srefHandlers[$('#imp-sref-system').val().toLowerCase()];
      pt = indiciaData.currentFeature.geometry.getCentroid(true); // must use weighted to accurately calculate
      newSectionDetails.sref = handler.pointToGridNotation(pt.transform(indiciaData.mapdiv.map.projection, 'EPSG:'+handler.srid), 10);
    } else { // default : initially set the section Sref etc to match the parent. centroid_geom will be auto generated on the server
      newSectionDetails.sref = $('#imp-sref').val();
    }

    // Store boundary geometry in the indiciaData
    newSectionDetails.geom = geom.toString(); // in 3857.

    // autocalc the section length and store in the section POST data and indiciaData and the Section Form
    if (indiciaData.autocalcSectionLengthAttrId) {
      var sectionLen = Math.round(
        indiciaData.currentFeature.geometry.clone()
        .getGeodesicLength(indiciaData.mapdiv.map.projection)
      );
      postData[$('#locAttr\\:'+indiciaData.autocalcSectionLengthAttrId).attr('name')] = sectionLen;
      $('#locAttr\\:'+indiciaData.autocalcSectionLengthAttrId).val(sectionLen);
      newSectionDetails.sectionLen = sectionLen;
    }

    indiciaData.sections[current] = newSectionDetails;

    // Store in POST data
    postData['location:centroid_sref'] = newSectionDetails.sref; // centroid_geom not provided, so automatically created by warehouse from sref
    postData['location:centroid_sref_system'] = newSectionDetails.system;

    // Store into details form
    $('#section-location-sref').val(newSectionDetails.sref);
    $('#section-location-system,#section-location-system-select').val(newSectionDetails.system);

    $.ajax({
        type: 'POST',
        url: indiciaData.ajaxFormPostUrl,
        data: postData,
        success: function(data) {
          if (typeof(data.error)!=="undefined") {
            alert(data.error);
          } else {
              var current_section = $('#section-select-route li.selected').html();
              var transaction_section = data.transaction_id;
              indiciaData.sections[transaction_section].id = data.outer_id; // both new and old require this, see defaultSectionGridRef functionality
              $('#section-select-route-'+transaction_section+',#section-select-'+transaction_section).removeClass('missing');
              if(current_section == transaction_section) {
                $('#section-location-id').val(data.outer_id);
              }
              indiciaData.routeChanged = false;
          }
          $('.save-route').removeClass('waiting-button');
          saveRouteDialog.dialog('close');
          if(callback !== null) callback();
        },
        dataType: 'json',
    });
    // Now update the parent with the total transect length: can do this at same time as data saved, as not dependant
    updateTransectDetails();
}

updateTransectDetails = function() {
  var transectLen = 0;
  var numSections;
  var ldata = {'location:id':$('#location-id').val(), 'website_id':indiciaData.website_id};

  for (prop in indiciaData.sections) {
      if (hasOwnProperty.call(indiciaData.sections, prop) && typeof indiciaData.sections[prop].sectionLen !== "undefined") {
    	  transectLen += indiciaData.sections[prop].sectionLen;
      }
    }
  // load into form.
  $('#locAttr\\:'+indiciaData.autocalcTransectLengthAttrId).val(transectLen);
  ldata[indiciaData.autocalcTransectLengthAttrName] = ''+transectLen;

  $.ajax({
        type: 'POST',
        url: indiciaData.ajaxFormPostUrl,
        data: ldata,
        success: function(data) {
          if (typeof(data.error)!=="undefined") {
            alert(data.error);
          } else {
            if (indiciaData.autocalcTransectLengthAttrName.split(':').length === 2) {
              $.ajax({
                type: 'GET',
                url: indiciaData.indiciaSvc + "index.php/services/data/location_attribute_value?" +
                      "location_id=" + $('#location-id').val() +
                      "&location_attribute_id=" + indiciaData.autocalcTransectLengthAttrId +
                      "&mode=json&view=list&callback=?" +
                      "&auth_token=" + indiciaData.readAuth.auth_token + "&nonce=" + indiciaData.readAuth.nonce,
                success: function(data) {
                  if (data.length > 0) {
                    var attrname = 'locAttr:' +
                                   indiciaData.autocalcTransectLengthAttrId + ':' +
                                   data[0].id;
                    $('#locAttr\\:'+indiciaData.autocalcTransectLengthAttrId).attr('name', attrname);
                    indiciaData.autocalcTransectLengthAttrName = attrname;
                  }
                },
                dataType: 'json',
              });
            }
          }
        },
        dataType: 'json',
  });
}

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

  /*
   * Map Utility functions.
   */
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

    function _extendBounds(bounds, buffer, minimum) {
      var dy = (bounds.top-bounds.bottom) * buffer;
      var dx = (bounds.right-bounds.left) * buffer;
      if(dx<minimum) dx=minimum;
      if(dy<minimum) dy=minimum;
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

//      return retVal;

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
            tcLayer.layerId = layer.layerName + '.0';
            if(layer.settings.isBaseLayer) {
              if(div.map.projection.projCode != layer.settings.srs) {
                retVal.reproject = true;
                div.map.projection = new OpenLayers.Projection(layer.settings.srs);
                div.map.infoLayer.projection = new OpenLayers.Projection(layer.settings.srs);
                div.map.editLayer.projection = new OpenLayers.Projection(layer.settings.srs);
              }
              if(typeof div.map.mousePosCtrl != 'undefined')
                  div.map.mousePosCtrl.displayProjection = new OpenLayers.Projection(layer.settings.srs);
              div.map.addLayer(tcLayer);
              div.map.setBaseLayer(tcLayer);
              div.map.maxExtent = tcLayer.maxExtent;
            } else
              div.map.addLayer(tcLayer);

            if(typeof layer.setInitialVisibility != 'undefined')
              tcLayer.setVisibility(layer.setInitialVisibility);
          });
          div.map.setLayerIndex(div.map.infoLayer, div.map.layers.length);
          div.map.setLayerIndex(div.map.editLayer, div.map.layers.length);
          div.map.resetLayersZIndex()
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
          feature.attributes.converted = true; // flag that this feature is being added due to a projection conversion.
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
          _extendBounds(bounds, div.settings.maxZoomBuffer, 10);
          div.map.zoomToExtent(bounds);
        }
        if(typeof div.map.baseLayer.onMapResize !== "undefined")
          div.map.baseLayer.onMapResize();
      }
    }

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
          details = {'reproject':true};

      if(typeof div.map == 'undefined') return;
      if ($(div).filter(':visible').length == 0) return;

      editLayerProj = div.map.editLayer.projection;
      infoLayerProj = div.map.infoLayer.projection;

      if(_setDefaultLayers(div)) {
        details = _setBaseLayers(div);
        if(details.reproject) {
          _convertGeometries(infoLayerProj.projCode, div.map.infoLayer);
          _convertGeometries(editLayerProj.projCode, div.map.editLayer);
        }
      }

      // when the route map is initially created it is hidden, so is not rendered, and the calculations of the map size are wrong
      // (Width is 100 rather than 100%), so any initial zoom in to the transect by the map panel is wrong.
      // Not only that but the layers may have changed.
      _resetMap(div, incInfo, initBounds, zoom || details.reproject);
    }

  mapInitialisationHooks.push(function(div) {
    var defaultStyle = new OpenLayers.Style(),
        selectedStyle = new OpenLayers.Style(),
        baseStyle = { strokeWidth: 4, strokeDashstyle: "1 5" },
        defaultRule = new OpenLayers.Rule({ symbolizer: $.extend({strokeColor: "#0000FF"}, baseStyle) }),
        selectedRule = new OpenLayers.Rule({ symbolizer: $.extend({strokeColor: "#FFFF00"}, baseStyle) }),
        f = [];

    if (div.id==='route-map') {
      indiciaData.currentFeature = null;
      indiciaData.previousGeometry = null;
      indiciaData.routeChanged = false;
      div.map.infoLayer = new OpenLayers.Layer.Vector('Transect Square', {
        style: div.map.editLayer.styleMap.styles.default.defaultStyle,
        sphericalMercator: true,
        displayInLayerSwitcher: true
      });
      div.map.addLayer(div.map.infoLayer);
      // If there are any features in the editLayer without a section number, then this is the transect square feature, so move it to the parent (Transect Square) layer,
      // otherwise it will be selectable and will prevent the route features being clicked on to select them. Have to do this each time the tab is displayed
      // else any changes in the transect will not be reflected here.
      // copy_over_transects();

      // find the selectFeature control so we can interact with it later
      // Note these are the route-map controls.
      $.each(div.map.controls, function(idx, control) {
        if (control.CLASS_NAME==='OpenLayers.Control.SelectFeature') {
          indiciaData.selectFeature = control;
          control.events.on({'activate':function() {
              if(typeof indiciaData.navControl !== "undefined") {
                  indiciaData.navControl.deactivate();
              }
              if(typeof indiciaData.drawFeature !== "undefined") {
                  indiciaData.drawFeature.deactivate();
                }
              if(typeof indiciaData.modifyFeature !== "undefined")
                indiciaData.modifyFeature.deactivate();
              if(indiciaData.currentFeature !== null)
                      indiciaData.selectFeature.select(indiciaData.currentFeature);
              else
                	  indiciaData.selectFeature.unselectAll();
            }});
          div.map.editLayer.events.on({'featureselected': function(evt) {
            confirmSelectSection(evt.feature.attributes.section, false);
          }});
        } else if (control.CLASS_NAME==='OpenLayers.Control.DrawFeature') {
            control.events.on({'activate':function() {
                if(typeof indiciaData.navControl !== "undefined") {
                    indiciaData.navControl.deactivate();
                }
                if(typeof indiciaData.modifyFeature !== "undefined")
                  indiciaData.modifyFeature.deactivate();
                if(typeof indiciaData.selectFeature !== "undefined") {
                    indiciaData.selectFeature.deactivate();
                    if(indiciaData.currentFeature !== null)
                        indiciaData.selectFeature.select(indiciaData.currentFeature);
                    else
                  	  indiciaData.selectFeature.unselectAll();
                }
              }});
          indiciaData.drawFeature = control;
        } else if (control.CLASS_NAME==='OpenLayers.Control.ModifyFeature') {
          indiciaData.modifyFeature = control;
          control.standalone = true;
          control.events.on({'activate':function() {
            if(typeof indiciaData.navControl !== "undefined") {
                  indiciaData.navControl.deactivate();
            }
            if(typeof indiciaData.selectFeature !== "undefined") {
                    indiciaData.selectFeature.deactivate();
                    indiciaData.selectFeature.unselectAll();
            }
            if(typeof indiciaData.drawFeature !== "undefined") {
              indiciaData.drawFeature.deactivate();
            }
            if(indiciaData.currentFeature !== null)
              indiciaData.modifyFeature.selectFeature(indiciaData.currentFeature);
            div.map.editLayer.redraw();
          }});
        } else if (control.CLASS_NAME==='OpenLayers.Control.Navigation') {
            indiciaData.navControl = control;
            control.events.on({'activate':function() {
              if(typeof indiciaData.modifyFeature !== "undefined")
                indiciaData.modifyFeature.deactivate();
              if(typeof indiciaData.selectFeature !== "undefined") {
                  indiciaData.selectFeature.deactivate();
                  if(indiciaData.currentFeature !== null)
                      indiciaData.selectFeature.select(indiciaData.currentFeature);
                  else
                	  indiciaData.selectFeature.unselectAll();
              }
              if(typeof indiciaData.drawFeature !== "undefined") {
                indiciaData.drawFeature.deactivate();
              }
            }});
        }
      });

      $('.olControlEditingToolbar').append('<span id="mousePos"></span>');
      div.map.mousePosCtrl = new MyMousePositionControl({
          div: document.getElementById('mousePos'),
          displayProjection: new OpenLayers.Projection('EPSG:4326'),
          emptyString: '',
          numDigits: 0
      });
      div.map.addControl(div.map.mousePosCtrl);

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
          if(typeof evt.feature.attributes.converted != 'undefined') {
            delete evt.feature.attributes.converted;
            return;
          }
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
              if (!confirm('Select \'OK\' if you would like to replace the existing section with the newly drawn one, and \'Cancel\' to keep the previous route.')) {
                evt.feature.layer.removeFeatures([evt.feature], {}); // No so abort - remove new feature, leave draw control active.
                return;
              } else {
                evt.feature.layer.removeFeatures(oldSection, {});
              }
            }
            indiciaData.routeChanged = true;
            // make sure the feature is selected: this ensures that it can be modified straight away
            // but we dont't want it to trigger a load of attibutes etc.
            indiciaData.currentFeature = evt.feature;
            indiciaData.previousGeometry = evt.feature.geometry.clone();
            indiciaData.selectFeature.select(indiciaData.currentFeature);
            indiciaData.drawFeature.deactivate();
            indiciaData.modifyFeature.activate();
            div.map.editLayer.redraw();
          }
      }

      div.map.editLayer.events.on({'featureadded': featureRouteAddedEvent}); // called when a new route line added
      div.map.editLayer.events.on({'featuremodified': function(evt) { indiciaData.routeChanged = true; }}); // called when a vertex is dragged, and mouse released.

      resetMap(div, true, false, true);
      if($('#section-location-id').val() == '')
        $('.complete-route-details').attr('disabled','disabled'); // should be done by the section select
    } else {
      // main map
      function featureSiteAddedEvent(evt) { // check that the country is OK.
          var intersects = false,
              mainMapDiv = $('#map')[0];
          if(typeof evt.feature.attributes.temp !== 'undefined' &&  evt.feature.attributes.temp == true)
                  return;
          if(mainMapDiv.map.infoLayer.features.length === 0)
            return;
          $.each(mainMapDiv.map.infoLayer.features, function(idx2, countryFeature) {
              $.each(countryFeature.geometry.components, function(idx3, component) {
                  intersects |= component.containsPoint(evt.feature.geometry.getCentroid());
                  //intersects |= countryFeature.geometry.intersects(evt.feature.geometry.getCentroid());
              });
          });
          if(!intersects) {
            $('<p>The new transect position is outside the boundary of the previously selected country.</p>').dialog(
                  { title: "Transect outside country",
                    buttons: { "OK":  function() { $(this).dialog('close'); } },
                    classes: {"ui-dialog": "above-map"}});
          }
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

      resetMap(div, false, true, false);
      countryChange(false);
      div.map.editLayer.events.on({'featureadded': featureSiteAddedEvent});
    }
  });

}(jQuery));