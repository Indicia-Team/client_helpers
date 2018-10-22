jQuery(document).ready(function ($) {
  'use strict';
  /**
   * A function to display a popup showing a link to a group's join page for sharing.
   * @param string title The title of the group.
   * @param string parentTitle The title of the parent of the group if there is one.
   * @param integer id The group ID.
   * @param string rootFolder The path of the root of the website including ?q= when required.
   */
  indiciaFns.groupLinkPopup = function (title, parentTitle, id, rootFolder) {
    var link = document.createElement('a');
    var url;
    var titlePath = title.toLowerCase().replace(/ /g, '-').replace(/[^a-z0-9\-]/g, '');
    if (parentTitle) {
      titlePath = parentTitle.toLowerCase().replace(/ /g, '-').replace(/[^a-z0-9\-]/g, '') + '/' + titlePath;
    }
    link.href = rootFolder.replace('/?q=', '') + '/join/' + titlePath;
    url = link.protocol + '//' + link.host + link.pathname + link.search + link.hash;
    $('#share-link').val(url);
    $.fancybox.close();
    $.fancybox($('#group-link-popup'));
  };

  $('#area-picker').change(function areaPickerSelect() {
    var geom;
    var data = indiciaData.areaPickerMapAreaData;
    var placeDef;
    var map = indiciaData.mapdiv.map;
    var feature;
    var lonlat;
    indiciaData.mapdiv.removeAllFeatures(map.editLayer, 'boundary');
    if (typeof data[$('#area-picker').val()] !== 'undefined') {
      placeDef = data[$('#area-picker').val()];
      geom = new OpenLayers.Bounds(placeDef.bounds).toGeometry();
      if (typeof indiciaData.areaPickerBoundsProjection !== 'undefined') {
        geom.transform('epsg:' + indiciaData.areaPickerBoundsProjection, map.projection);
      }
      map.zoomToExtent(geom.getBounds());
      if (typeof placeDef.system !== 'undefined' && $('#imp-sref-system').val() !== placeDef.system) {
        $('#imp-sref-system').val(data[$('#area-picker').val()].system);
        $('#imp-sref').val('');
        $.each(map.controls, function () {
          if (this.CLASS_NAME === 'OpenLayers.Control.Graticule') {
            this.gratLayer.setVisibility(this.projection === 'EPSG:' + placeDef.projection);
          }
        });
      }
      if (typeof indiciaData.areaPickerDrawBounds !== 'undefined' && indiciaData.areaPickerDrawBounds === true) {
        feature = new OpenLayers.Feature.Vector(geom);
        feature.attributes.type = 'boundary';
        map.editLayer.addFeatures([feature]);
      }
      if (indiciaData.areaPickerUpdatesSref) {
        lonlat = { lon: geom.getCentroid().x, lat: geom.getCentroid().y };
        indiciaData.mapdiv.processLonLatPositionOnMap(lonlat, indiciaData.mapdiv);
        if ($('#imp-boundary-geom').length > 0) {
          $('#imp-boundary-geom').val(geom.toString());
        }
      }
    }
  });

  indiciaFns.bulkDeleteOccurrences = function bulkDeleteOccurrences(importGuid) {
    var params = {
      nonce: indiciaData.write.nonce,
      auth_token: indiciaData.write.auth_token,
      import_guid: importGuid,
      user_id: indiciaData.user_id
    };
    $.post(
      indiciaData.proxyUrl + '?url=' + indiciaData.warehouseUrl + 'index.php/services/data_utils/bulk_delete_occurrences',
      $.extend({}, params, { trial: 't' }),
      function (response) {
        if (typeof response.code !== 'undefined' && response.code === 200) {
          if (confirm(response.affected.occurrences + ' records in ' + response.affected.samples + ' samples will be deleted. ' +
              'Do you want to proceed?')) {
            $.post(
              indiciaData.proxyUrl + '?url=' + indiciaData.warehouseUrl + 'index.php/services/data_utils/bulk_delete_occurrences',
              params,
              function (response) {
                if (typeof response.code !== 'undefined' && response.code === 200) {
                  alert(response.affected.occurrences + ' records in ' + response.affected.samples + ' samples were deleted.');
                  indiciaData.reports.imports_grid.grid_imports_grid.reload();
                } else {
                  if (typeof response.message === 'undefined') {
                    alert('An error occurred');
                  } else {
                    alert(response.message);
                  }
                }
              }
            );
          }
        } else {
          if (typeof response.message === 'undefined') {
            alert('An error occurred');
          } else {
            alert(response.message);
          }
        }
      }
    );
  }
});
