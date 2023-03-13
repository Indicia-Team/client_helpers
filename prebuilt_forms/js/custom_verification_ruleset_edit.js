jQuery(document).ready(($) => {
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
                  div.map.editLayer.removeAllFeatures();
                  div.map.editLayer.addFeatures([indiciaData.bboxFeature]);
                  bounds.transform(div.map.projection, 'epsg:4326')
                  $('#geography\\:min_lng').val(bounds.left.toFixed(5));
                  $('#geography\\:min_lat').val(bounds.bottom.toFixed(5));
                  $('#geography\\:max_lng').val(bounds.right.toFixed(5));
                  $('#geography\\:max_lat').val(bounds.top.toFixed(5));
                  // Clear the other types of geo limits.
                  $('#geography\\:grid_refs').val('');
                  // @todo higher geography.
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

  });

  /**
   * If grid refs input, clear the other entered data.
   */
  $('#geography\\:grid_refs').change(function() {
    $('.lat-lng-input').val('');
    if (indiciaData.bboxFeature) {
      indiciaData.mapdiv.map.editLayer.removeFeatures([indiciaData.bboxFeature]);
      delete indiciaData.bboxFeature;
    }
    // @todo also clear higher geography
  });

  /**
   * Lat long data entered for the limits.
   *
   * Clears other data. Draws a bounding box if all values available.
   */
  $('.lat-lng-input').change(function() {
    indiciaData.mapdiv.map.editLayer.removeAllFeatures();
    $('#geography\\:grid_refs').val('');
    // @todo also clear higher geography
    // Draw a bounding box if we have a complete one to draw.
    if ($('#geography\\:min_lng').val().trim() !== '' &&
        $('#geography\\:min_lat').val().trim() !== '' &&
        $('#geography\\:max_lng').val().trim() !== '' &&
        $('#geography\\:max_lat').val().trim()) {
      let bounds = new OpenLayers.Bounds(
        $('#geography\\:min_lng').val().trim(),
        $('#geography\\:min_lat').val().trim(),
        $('#geography\\:max_lng').val().trim(),
        $('#geography\\:max_lat').val().trim()
      );
      bounds.transform('epsg:4326', indiciaData.mapdiv.map.projection);
      indiciaData.bboxFeature = new OpenLayers.Feature.Vector(bounds.toGeometry());
      indiciaData.mapdiv.map.editLayer.addFeatures([indiciaData.bboxFeature]);
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
  $('#geography\\:location_type').change(() => {
    $('input#geography\\:location_list\\:search\\:name').setExtraParams({
      location_type_id: $('#geography\\:location_type').val()
    });
  });

  // Delete button prompt.
  $('#delete-button').click(function(e) {
    if (!confirm("Are you sure you want to delete this location?")) {
      e.preventDefault();
      return false;
    }
  });

});