(function ($) {
  // If there is a parent location selected we would like to 
  //  a. Show its boundary,
  //  b. Zoom to its extents,
  //  c. Ensure the child location is within the parent location.
  
  function parentChange(div, locationID) {
    // Function to handle the parent change event. 
    
    // Empty info layer on map which holds the parent location.
    div.map.infoLayer.destroyFeatures();
    
    if (locationID != ''){
      // Put the new parent location on the map.

      // Indicate to user we are thinking.
      $('#location\\:parent_id').addClass('loading');
      // Construct request to warehouse services.
      var request = indiciaData.read.url + 'index.php/services/data/location/' + 
        locationID +
        '?mode=json&view=detail' +
        '&auth_token=' + indiciaData.read.auth_token +
        '&reset_timeout=true&nonce=' + indiciaData.read.nonce;
      // Proxy the request as cross-origin requests not allowed.
      var proxyRequest = indiciaData.proxyUrl + '?url=' + request;
      // Make the request
      $.getJSON(proxyRequest, function(ldata) {
        // Callback performed on reply from warehouse.
        $.each(ldata, function(idx, parent) {

          // Make a feature from the response information.
          var parser = new OpenLayers.Format.WKT();
          var features = parser.read(parent.boundary_geom);

          // Reproject parent location if map projection is not the same as
          // default projection.
          if (
            div.map.infoLayer.projection.projCode != 'EPSG:900913' && 
            div.map.infoLayer.projection.projCode != 'EPSG:3857'
          ) { 
            var cloned = features.geometry.clone();
            features.geometry = cloned.transform(
              new OpenLayers.Projection('EPSG:3857'), 
              div.map.infoLayer.projection.projCode
            );
          }
          // Add the parent to the infoLayer.
          if (!Array.isArray(features)) features = [features];
          div.map.infoLayer.addFeatures(features);
        });

        // Test if location is within parent boundary.
        if(!locationInParent(div)) {
          // Warn if not.
          $('<p>' + indiciaData.langLocationOutsideNewParent + '</p>').dialog({
            title: indiciaData.langLocationOutsideParent,
            buttons: [
              { 
                'text': indiciaData.langOK,
                'click' : function() { $(this).dialog('close'); }
              }
            ],
            classes: {"ui-dialog": "above-map"}
          });
        }
        
        // Remove indication to user we were thinking.
        $('#location\\:parent_id').removeClass('loading');

        // Alter extents of map if there is no location yet.
        if(div.map.editLayer.features.length === 0) {
          var infoExtent = div.map.infoLayer.getDataExtent();
          if(infoExtent !== null) {
            // Zoom to the extents of the parent, if present.
            div.map.zoomToExtent(infoExtent);
          }
          else {
            // Else return to default centre and zoom.
            div.map.setCenter(div.map.defaultLayers.centre, div.map.defaultLayers.zoom, false, true);
          }
        } 
      });
    }
  }


  function locationAdded(evt) {
    // Function to handle a location added event.

    if (evt.feature.attributes.type == 'ghost') {
      // Ignore the ghost feature being added.
      return;
    }

    var intersects = false
    var div = $('#map')[0];

    if(!locationInParent(div)) {
      $('<p>' + indiciaData.langNewLocationOutsideParent + '</p>').dialog({
        title: indiciaData.langLocationOutsideParent,
        buttons: [
          { 
            'text': indiciaData.langOK,
            'click' : function() { $(this).dialog('close'); }
          }
        ],
        classes: {"ui-dialog": "above-map"}
      });
    }
  };


  function locationInParent(div) {
    // Function to test if any part of location is within parent boundary.
    // The location is in the editLayer and the parent is in the infoLayer.
    var intersects = true;

    if (
      div.map.editLayer.features.length > 0 &&
      div.map.infoLayer.features.length > 0
    ) {
      var intersects = false;
      // Though using 'each' to access parent features, there is only one.
      $.each(div.map.infoLayer.features, function(i, parentFeature) {
        $.each(div.map.editLayer.features, function(j, editFeature) {
        // Loop through all parts of the location - probably just a point
        // but allows for the possibility of multiple components.
            intersects ||= parentFeature.geometry.intersects(editFeature.geometry);
        });
      });
    }
    return intersects;
  }

  // Push function to be executed when map is initialised.
  mapInitialisationHooks.push(function(div) {
    // Initialise layer for showing a parent location.
    div.map.infoLayer = new OpenLayers.Layer.Vector(
      indiciaData.langParentLayerTitle,
      {
        style: {
          fillOpacity : 0,
          strokeOpacity : 0.5,
          strokeColor : '#ee0000',
          strokeDashstyle : 'dash',
          strokeWidth : 2
        },
        sphericalMercator: true,
        displayInLayerSwitcher: true
      }
    );
    div.map.addLayer(div.map.infoLayer);

    // Detect the feature-added event and call the locationAdded function.
    div.map.editLayer.events.on({'featureadded': locationAdded}); 

    // Detect parent-location change event and call the parentChange function.
    $('#location\\:parent_id').change(function() {
      var locationID = $(this).val();
      parentChange(div, locationID)
    });

  });


})(jQuery);