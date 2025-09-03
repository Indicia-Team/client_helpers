jQuery(document).ready(($) => {

  // Track the grid refs on the map.
  indiciaData.gridRefsOnMap = [];

  // Configure Font Awesome icons on the selection drop-down options.
  $('#custom_verification_ruleset\\:fail_icon, #custom_verification_ruleset\\:fail_icon option').addClass('fa');
  const iconClasses = {
    'bar-chart': '\uf080',
    'bug': '\uf188',
    'calendar': '\uf073',
    'calendar-cross': '\uf273',
    'calendar-tick': '\uf274',
    'clock': '\uf017',
    'count': '\uf885',
    'cross': '\uf00d',
    'exclamation': '\uf12a',
    'flag': '\uf024',
    'globe': '\uf0ac',
    'history': '\uf1da',
    'leaf': '\uf06c',
    'spider': '\uf717',
    'tick': '\uf00c',
    'tick-in-box': '\uf14a',
    'warning-triangle': '\uf071'
  };
  $.each(iconClasses, (iconClass, char) => {
    $('#custom_verification_ruleset\\:fail_icon option[value="' + iconClass + '"]').text(
      char + ' ' + $('#custom_verification_ruleset\\:fail_icon option[value="' + iconClass + '"]').text()
    );
  });

  // Create a control for dragging a bounding box.
  mapInitialisationHooks.push(function(div) {
    indiciaData.displayLayer = new OpenLayers.Layer.Vector('Ruleset geography limits', {
      style: { strokeColor: 'blue', strokeWidth: 2, fillColor: 'blue', fillOpacity: 0.3 },
      sphericalMercator: true
    });
    div.map.addLayer(indiciaData.displayLayer);
    indiciaData.bboxCtrl = new OpenLayers.Control({
      displayClass: 'todo',
      title: 'Define limit by bounding box',
      activate: function() {
        if (!this.handlers) {
          this.handlers = {
            box: new OpenLayers.Handler.Box(
              this,
              {
                done: (position) => {
                  const minXY = div.map.getLonLatFromPixel(
                    new OpenLayers.Pixel(position.left, position.bottom)
                  );
                  const maxXY = div.map.getLonLatFromPixel(
                    new OpenLayers.Pixel(position.right, position.top)
                  );
                  const bounds = new OpenLayers.Bounds(
                    minXY.lon, minXY.lat, maxXY.lon, maxXY.lat
                  );
                  const geom = bounds.toGeometry();
                  indiciaData.bboxFeature = new OpenLayers.Feature.Vector(geom);
                  indiciaData.displayLayer.removeAllFeatures();
                  indiciaData.displayLayer.addFeatures([indiciaData.bboxFeature]);
                  bounds.transform(div.map.projection, 'epsg:4326')
                  $('#geography\\:min_lng').val(bounds.left.toFixed(5));
                  $('#geography\\:min_lat').val(bounds.bottom.toFixed(5));
                  $('#geography\\:max_lng').val(bounds.right.toFixed(5));
                  $('#geography\\:max_lat').val(bounds.top.toFixed(5));
                  $('#invalid-bbox-message').hide();
                  $('#save-button').removeAttr('disabled');
                  // Clear the other types of geo limits.
                  $('#geography\\:grid_refs').val('');
                  $('#geography\\:location_list\\:sublist *').remove();
                }
              },
            )
          };
        }
        this.handlers.box.activate();
        OpenLayers.Control.prototype.activate.call(this);
      },
      deactivate: function() {
        indiciaData.bboxCtrl.handlers.box.deactivate();
        OpenLayers.Control.prototype.deactivate.call(this);
      }
    });
    div.map.addControl(indiciaData.bboxCtrl);
    // Activate immediately if loading existing record which has lat long limits.
    if ($("#latlng-collapse").hasClass('in')) {
      indiciaData.bboxCtrl.activate();
    }
    // On startup, draw any grid refs.
    if ($('#geography\\:grid_refs').val() !== '') {
      $('#geography\\:grid_refs').trigger('change');
    }
  });

  /**
   * Removes a grid ref or location boundary identified by ID.
   */
  function removeDisplayFeature(id) {
    let feature = indiciaData.displayLayer.getFeatureById(id);
    if (feature) {
      indiciaData.displayLayer.removeFeatures([feature]);
    }
  }

  /**
   * Handle when grid ref(s) input into the list box.
   */
  $('#geography\\:grid_refs').on('change', function() {
    const gridRefs = $('#geography\\:grid_refs').val().toUpperCase().split(/\n/);
    // Clear other types of geography limit so we only have one.
    $('.lat-lng-input').val('');
    $('#invalid-bbox-message').hide();
    $('#save-button').removeAttr('disabled');
    if (indiciaData.bboxFeature) {
      indiciaData.displayLayer.removeFeatures([indiciaData.bboxFeature]);
      delete indiciaData.bboxFeature;
    }
    $('#geography\\:location_list\\:sublist *').remove();
    indiciaData.mapdiv.removeAllFeatures(indiciaData.displayLayer, 'higherGeo');
    // Remove deleted grid refs from the map. Walk backwards so we can safely
    // remove items.
    for (let i = indiciaData.gridRefsOnMap.length - 1; i >= 0; i--) {
      const gridRef = indiciaData.gridRefsOnMap[i];
      if (gridRefs.indexOf(gridRef) === -1) {
        removeDisplayFeature('gridRef:' + gridRef);
        var index = indiciaData.gridRefsOnMap.indexOf(gridRef);
        if (index > -1) {
          indiciaData.gridRefsOnMap.splice(index, 1);
        }
      }
    }
    // Add new grid refs to the map.
    $.each(gridRefs, function(idx, gridRef) {
      if (gridRef.trim() !== '' && indiciaData.gridRefsOnMap.indexOf(gridRef) === -1) {
        $.ajax({
          dataType: 'jsonp',
          crossDomain: true,
          url: indiciaData.warehouseUrl + 'index.php/services/spatial/sref_to_wkt',
          data: 'sref=' + gridRef +
            '&system=' + $('#imp-sref-system').val() +
            '&mapsystem=' + indiciaFns.projectionToSystem(indiciaData.mapdiv.map.projection, false)
        })
        .done((data) => {
          const parser = new OpenLayers.Format.WKT();
          const feature = parser.read(data.mapwkt);
          feature.id = 'gridRef:' + gridRef;
          feature.attributes.type = 'gridRef';
          indiciaData.displayLayer.addFeatures([feature]);
          indiciaData.gridRefsOnMap.push(gridRef);
        });
      }
    });
  });

  /**
   * Returns true if there is a fully specified bounding box.
   */
  function hasValidBoundingBox() {
    const minLng = $('#geography\\:min_lng').val().trim();
    const minLat = $('#geography\\:min_lat').val().trim();
    const maxLng = $('#geography\\:max_lng').val().trim();
    const maxLat = $('#geography\\:max_lat').val().trim();
    const hasBbox = minLng !== '' && minLat !== '' && maxLng !== '' && maxLat !== '';
    let isValid = true;
    isValid = isValid && (minLng === '' || !isNaN(minLng));
    isValid = isValid && (minLat === '' || !isNaN(minLat));
    isValid = isValid && (maxLng === '' || !isNaN(maxLng));
    isValid = isValid && (maxLat === '' || !isNaN(maxLat));
    isValid = isValid && (minLng === '' || parseFloat(minLng) >= -180);
    isValid = isValid && (maxLng === '' || parseFloat(maxLng) <= 180);
    isValid = isValid && (minLat === '' || parseFloat(minLat) >= -90);
    isValid = isValid && (maxLat === '' || parseFloat(maxLat) <= 90);
    isValid = isValid && (minLng === '' || maxLng === '' || parseFloat(minLng) < parseFloat(maxLng));
    isValid = isValid && (minLat === '' || maxLat === '' || parseFloat(minLat) < parseFloat(maxLat));
    if (isValid) {
      // Hide the validation message.
      $('#invalid-bbox-message').hide();
      $('#save-button').removeAttr('disabled');
    } else {
      $('#invalid-bbox-message').show();
      $('#save-button').attr('disabled', true);
    }
    return hasBbox && isValid;
  }

  /**
   * Lat long data entered for the limits.
   *
   * Clears other data. Draws a bounding box if all values available.
   */
  $('.lat-lng-input').on('change', function() {
    indiciaData.displayLayer.removeAllFeatures();
    // Clear other types of geography limit so we only have one.
    $('#geography\\:grid_refs').val('');
    $('#geography\\:location_list\\:sublist *').remove();
    // Draw a bounding box if we have a complete one to draw.
    if (hasValidBoundingBox()) {
      let bounds = new OpenLayers.Bounds(
        $('#geography\\:min_lng').val().trim(),
        $('#geography\\:min_lat').val().trim(),
        $('#geography\\:max_lng').val().trim(),
        $('#geography\\:max_lat').val().trim()
      );
      bounds.transform('epsg:4326', indiciaData.mapdiv.map.projection);
      indiciaData.bboxFeature = new OpenLayers.Feature.Vector(bounds.toGeometry());
      indiciaData.displayLayer.addFeatures([indiciaData.bboxFeature]);
    }
  });

  // Activate the bounding box control when that panel shown.
  $("#latlng-collapse").on("show.bs.collapse", function() {
    indiciaData.bboxCtrl.activate();
  });

  // De-activate the bounding box control when that panel hidden.
  $("#latlng-collapse").on("hide.bs.collapse", function() {
    indiciaData.bboxCtrl.deactivate();
  });

  // Location type filter for higher geo areas.
  $('#geography\\:location_type').on('change', () => {
    $('input#geography\\:location_list\\:search\\:name').setExtraParams({
      location_type_id: $('#geography\\:location_type').val()
    });
  });

  // Addition of a location for a higher geography limit on the rule.
  $('#geography\\:location_list\\:add').click(() => {
    // Clear other types of geography limit so we only have one.
    $('#geography\\:grid_refs').val('');
    indiciaData.mapdiv.removeAllFeatures(indiciaData.displayLayer, 'gridRef');
    $('.lat-lng-input').val('');
    $('#invalid-bbox-message').hide();
    $('#save-button').removeAttr('disabled');
    // Draw the location on the map.
    const locationId = $('#geography\\:location_list\\:search').val();
    let found = false;
    $.each(indiciaData.displayLayer.features, function() {
      if (this.id === 'higherGeo:' + locationId) {
        found = true;
        return false;
      }
    });
    if (locationId && !found) {
      $.ajax({
        url: indiciaData.read.url + 'index.php/services/data/location/' + locationId,
        data: {
          mode: 'json',
          view: 'detail',
          auth_token: indiciaData.read.auth_token,
          nonce: indiciaData.read.nonce
        },
        dataType: 'jsonp',
        crossDomain: true
      })
      .done(function (data) {
        if (data.length > 0) {
          const parser = new OpenLayers.Format.WKT();
          const geomwkt = data[0].boundary_geom || data[0].centroid_geom;
          const feature = parser.read(geomwkt);
          feature.id = 'higherGeo:' + locationId;
          feature.attributes.type = 'higherGeo';
          if (indiciaData.mapdiv.indiciaProjection.getCode() !== indiciaData.mapdiv.map.projection.getCode()) {
            feature.geometry.transform(indiciaData.mapdiv.indiciaProjection, indiciaData.mapdiv.map.projection);
          }
          indiciaData.displayLayer.addFeatures([feature]);
        }
      });
    }
  });

  /**
   * Delete a higher geography location also removes the boundary.
   */
  indiciaFns.on('click', '#geography\\:location_list\\:sublist .ind-delete-icon', {}, function() {
    const locationId = $(this).closest('li').find('input').val();
    removeDisplayFeature('higherGeo:' + locationId);
  });

  // @todo Remove a higher geo

  // Delete button prompt.
  $('#delete-button').click(function(e) {
    if (!confirm(indiciaData.lang.customVerificationRulesetEdit.areYouSureDelete)) {
      e.preventDefault();
      return false;
    }
  });

});