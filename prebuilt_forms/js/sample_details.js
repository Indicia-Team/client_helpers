jQuery(document).ready(function ($) {
  'use strict';

  function addGeom(geom, type, id) {
    var geom;
    var feature;
    var map = indiciaData.mapdiv.map;
    geom = OpenLayers.Geometry.fromWKT(geom);
    if (map.projection.getCode() !== indiciaData.mapdiv.indiciaProjection.getCode()) {
      geom.transform(indiciaData.mapdiv.indiciaProjection, map.projection);
    }
    feature = new OpenLayers.Feature.Vector(geom);
    feature.attributes.type = type;
    feature.attributes.id = id;
    map.editLayer.addFeatures([feature]);
    // Force the correct style.
    feature.style = $.extend({}, map.editLayer.styleMap.styles.default.defaultStyle);
    feature.style.strokeWidth = 2;
    if (type === 'child') {
      feature.style.strokeColor = '#d95f02';
      feature.style.fillColor = '#d95f02';
    } else if (type === 'occurrence') {
      feature.style.strokeColor = '#7570b3';
      feature.style.fillColor = '#7570b3';
    }
  }

  function addGeomsToMap() {
    $.each(indiciaData.parentChildGeoms, function() {
      addGeom(this.geom, this.type, this.id);
    });
    indiciaData.mapdiv.map.editLayer.redraw();
  }

  if (indiciaData.parentChildGeoms) {
    mapInitialisationHooks.push(addGeomsToMap);
  }
});