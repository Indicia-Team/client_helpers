/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @package Client
 * @subpackage PrebuiltForms
 * @author  Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link    http://code.google.com/p/indicia/
 */

var grid_load;

(function ($) {
  
var layers=[];

/**
 * Callback for the grid loading on sort or pagination - resets the icon state of active layers.
 */
grid_load = function() {
  var img;
  $.each(layers, function(idx, layer) {
    img = $('tr#row'+layer.key+' img');
    if (img.length>0) {
      img.attr('src', img.attr('src').replace('add.png','delete.png'));
      $(img).addClass('on-map');
    }
  });
};

$(document).ready(function () {
  var sequence=0, remove, removeIdx, img, title, key, filter, sld;
  
  /**
   * Catch change of state on the show instructins checkbox.
   */
  $('#layerbox #checkShowInstructions').change(function () {
    if(this.checked) {
      $('#layerbox #instruct').show()
    } else {
      $('#layerbox #instruct').hide()
    }
    
  })

  /**
   * Catch change of state on the show legend checkbox.
   */
  $('#layerbox #checkShowLegend').change(function () {
    if(this.checked) {
      $('#layerbox #layers').show()
    } else {
      $('#layerbox #layers').hide()
    }
  })

  /**
   * Catch clicks on the grid icons, to add layers for the species to the map.
   */
  $('table.report-grid tbody').click(function (evt) {
    if ((evt.target.localName || evt.target.nodeName.toLowerCase())!=="img") {
      return;
    }

    // Find the taxon's key (e.g. tvk)
    key=$(evt.target).parents('tr')[0].id.substr(3);
    if ($(evt.target).hasClass('on-map')) {
      // already on the map? Remove it
      $.each(layers, function(idx, layer) {
        if (layer.key===key) {
          removeIdx=idx;
        }
      });
      if (typeof removeIdx!=="undefined") {
        remove=layers.splice(removeIdx,1);
        indiciaData.mapdiv.map.removeLayer(remove[0].layer);
        // reset icon
        $(evt.target).attr('src', $(evt.target).attr('src').replace('delete.png','add.png'));
        $(evt.target).removeClass('on-map');
      }
    } else {
      // not on the map
      title=indiciaData.indiciaSpeciesLayer.title.replace('{1}', $($(evt.target).parents('tr')[0]).find('td:first').text());
      filter=indiciaData.indiciaSpeciesLayer.cqlFilter.replace('{filterValue}', key);
      if ($('#report-ownData').attr('checked')) {
        filter += ' AND created_by_id=' + indiciaData.indiciaSpeciesLayer.userId;
        title += ' - ' + indiciaData.indiciaSpeciesLayer.myRecords;
      }
      sld=indiciaData.indiciaSpeciesLayer.slds[sequence % indiciaData.indiciaSpeciesLayer.slds.length];
      var layer = new OpenLayers.Layer.WMS(title, indiciaData.indiciaSpeciesLayer.wmsUrl, 
          {layers: indiciaData.indiciaSpeciesLayer.featureType, transparent: true, CQL_FILTER: filter, STYLES: sld},
          {isBaseLayer: false, sphericalMercator: true, singleTile: true, opacity: 0.5});
      indiciaData.mapdiv.map.addLayer(layer);
      layers.push({layer:layer,key:key});
      if (layers.length>indiciaData.indiciaSpeciesLayer.max_layers) {
        remove=layers.splice(0,1);
        indiciaData.mapdiv.map.removeLayer(remove[0].layer);
        img = $('tr#row'+remove[0].key).find('img');
        //If row not currently visible in grid, img.length will be zero
        img.length && img.attr('src', img.attr('src').replace('delete.png','add.png'));
        img.length && img.removeClass('on-map'); 
      }
      $(evt.target).attr('src', $(evt.target).attr('src').replace('add.png','delete.png'));
      $(evt.target).addClass('on-map');
      sequence++;
    }
  }); 
});

}(jQuery));