var setSectionDropDown;

jQuery(document).ready(function($) {

  indiciaData.siteChanged = false;

  /**
   * When viewing the species input tab, if the seelcted site has been changed, then update the sections input controls for
   * each row to a drop down with the available subsites.
   */
  setSectionDropDown = function(event, ui) {
    if (indiciaData.siteChanged || $('input.scSectionID').length>0) {
      var html, instance, val, id;
      html = '<select id="{id}" name="{name}"><option value="">&lt;Please select&gt;</option>';
      $.each(indiciaData.subsites, function(idx, subsite) {
        html += '<option value="' + subsite.id + '">' + subsite.name + '</option>';
      });
      html += '</select>';
      $('.scSectionID').each(function(idx, ctrl) {
        val=ctrl.value, id=ctrl.id;
        instance = $(html.replace('\{id\}', id).replace('\{name\}', ctrl.name));
        $(ctrl).replaceWith(instance);
        instance.val(val);
      });
    }
  };

  /**
   * A public method that can be fired when a location is selected in an input control, to load the location's
   * boundary onto the map.
   */
  function locationSelectedInInput(div, val) {
    var intValue = parseInt(val);
    if (!isNaN(intValue)) {
      // Change the location control requests the location's geometry to place on the map.
      $.ajax({
        url: indiciaData.read.url + 'index.php/services/data/location',
        data: {
          parent_id: val,
          orderby: 'name',
          mode: 'json',
          view: 'detail',
          auth_token: indiciaData.read.auth_token,
          nonce: indiciaData.read.nonce
        },
        dataType: 'jsonp',
        crossDomain: true
      })
      .done(function(data) {
        // Sort into numeric section order.
        data.sort(function(a, b) {
          var aCode = a.code.replace(/^S/g, '');
          var bCode = b.code.replace(/^S/g, '');
          return parseInt(aCode, 10) - parseInt(bCode, 10);
        });
        indiciaData.subsites = data;
        $('#subsites').val(JSON.stringify(data));
        indiciaData.mapdiv.removeAllFeatures(indiciaData.mapdiv.map.editLayer, 'section');
        $.each(data, function(idx, subsite) {
          var geomwkt = subsite.boundary_geom || data[0].centroid_geom;
          var parser = new OpenLayers.Format.WKT();
          var feature = parser.read(geomwkt);
          indiciaData.siteChanged = true;
          feature.attributes.type = 'section';
          if (indiciaData.mapdiv.indiciaProjection.projCode!==indiciaData.mapdiv.map.projection.projCode){
            geomwkt = feature.geometry.transform(div.indiciaProjection, indiciaData.mapdiv.map.projection).toString();
          }
          indiciaData.mapdiv.map.editLayer.addFeatures([feature]);
        });
        indiciaData.mapdiv.map.zoomToExtent(indiciaData.mapdiv.map.editLayer.getDataExtent());
      });
    }
  }

  mapInitialisationHooks.push(function() {
    if ($('#imp-location').length) {
      var locChange = function() {locationSelectedInInput(indiciaData.mapdiv, $('#imp-location').val());};
      $('#imp-location').on('change', locChange);
      // trigger change event, incase imp-location was already populated when the map loaded
      locChange();
    }
  });

});
