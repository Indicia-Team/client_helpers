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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/indicia-team/client_helpers
 */

indiciaData.unassignedLocationLayer = false;
indiciaData.myLocationLayer = false;
indiciaData.othersLocationLayer = false;
indiciaData.holidayLocationLayer = false;

(function ($) {
  'use strict';

  indiciaFns.populateFeatures = function(list) {

    var type = $('[name=location\\:location_type_id]').val();
    indiciaData.holidayLocationLayer.removeAllFeatures();
    if(type == '') return;

    indiciaData.dialog = $('<p>Please wait whilst the holiday squares are loaded.</p>').dialog({ title: 'Loading...', buttons: { 'OK': function() { indiciaData.dialog.dialog('close'); }}});

    var report_URL = indiciaData.indiciaSvc + '/index.php/services/report/requestReport?report=library/locations/locations_list.xml' +
                        '&reportSource=local' +
                        '&mode=json' +
                        '&auth_token=' + indiciaData.read.auth_token + '&nonce=' + indiciaData.read.nonce +'&reset_timeout=true' + 
                        '&callback=?' +
                        '&location_type_id=' + type +
                        '&locattrs=' + indiciaData.cms_attr_id + ',' + indiciaData.holiday_attr_id +
//                        '&attr_location_' + indiciaData.holiday_attr_id + '=1' +
                        '&orderby=name';

    $.getJSON(report_URL, function(data) {
        var parser = new OpenLayers.Format.WKT();
        $.each(data, function(_idx, site) {
          var feature = parser.read(site.geom);
          if (indiciaData.mapdiv.indiciaProjection.projCode !== indiciaData.mapdiv.map.projection.projCode){
            feature.geometry = feature.geometry.transform(div.indiciaProjection, indiciaData.mapdiv.map.projection);
          }
          data[_idx]['feature'] = feature;
          return true;
        });
        $.each(data, function(_idx, site) {

          if (site['attr_location_' + indiciaData.holiday_attr_id] === null || !site['attr_location_' + indiciaData.holiday_attr_id]) {
            return true;
          }
          var assignees = [],
              isAssignee = false;

          if (site['attr_location_' + indiciaData.cms_attr_id] !== '' && site['attr_location_' + indiciaData.cms_attr_id] !== null) {
            assignees = site['attr_location_' + indiciaData.cms_attr_id].split(',')
            for(var i = 0; i < assignees.length; i++) {
              assignees[i] = assignees[i].trim();
              if(assignees[i] == indiciaData.userID) { // CMSID
                isAssignee = true;
                break;
              }
            }
          }

          if (site['attr_location_' + indiciaData.holiday_attr_id]) {
            site['feature'].attributes.name = site.name;
            site['feature'].attributes.id = site.location_id;
            site['feature'].attributes.isAssignee = isAssignee;
            if(isAssignee) {
              site['feature'].renderIntent = 'mine';
            }
            indiciaData.holidayLocationLayer.addFeatures([site['feature']]); // features added even if too many for list
          }

          return true;
        });

        var extent = indiciaData.holidayLocationLayer.getDataExtent();
        if(extent !== null) {
          indiciaData.holidayLocationLayer.map.zoomToExtent(extent);
        }
        indiciaData.dialog.dialog('close');
    });
  }

  indiciaFns.listClosestSites = function(list) {

    var bounds = indiciaData.holidayLocationLayer.map.getExtent(); // displayed extent.
    var mapCentre = bounds.toGeometry().getCentroid().transform(indiciaData.holidayLocationLayer.map.projection, 'EPSG:27700');
    var centre = new OpenLayers.Feature.Vector(bounds.toGeometry().getCentroid(), {type: 'georef', temp: true});
    indiciaData.holidayLocationLayer.map.editLayer.destroyFeatures();
    indiciaData.holidayLocationLayer.map.editLayer.addFeatures([centre]);

    $('#featureTable').show();
    $('#featureTableBody tr').remove();
    $('input.apply-actions-button').remove();

    var features = indiciaData.holidayLocationLayer.features;
    if (features.length === 0)
      return;

    $.each(features, function(_idx, feature) {
      feature.attributes.featureDistance = Math.round(feature.geometry.clone().transform(indiciaData.holidayLocationLayer.map.projection, 'EPSG:27700').distanceTo(mapCentre));
    });
    features.sort(function(a, b) {
      return a.attributes.featureDistance - b.attributes.featureDistance;
    });
    $.each(features, function(_idx, feature) {

      var runningTotal = $('#featureTableBody tr').length,
          row = '<td>' +
                (typeof indiciaData.user_id === 'undefined' ? feature.attributes.name :
                  '<a target="_blank" href="' + indiciaData.siteDetails + '?id=' + feature.attributes.id + '">' + feature.attributes.name + '</a>') +
                '</td>';

      // make sure that we can see the nearest holiday square. Data has been sorted into nearest first.
      if(_idx === 0) { // Only zoom if the closest (first due to sorting) holiday square is outside the bounds.
        if(!bounds.containsBounds(feature.geometry.getBounds())) {
          bounds.extend(feature.geometry.getCentroid());
          indiciaData.holidayLocationLayer.map.zoomToExtent(bounds);
          bounds = indiciaData.holidayLocationLayer.map.getExtent(); // new displayed extent.
        }
      }

      var buildAction = function(command, label, feature, userID, fullControl) {
                    if(fullControl)
                        return '<td><input type="hidden" name="' + command + '_' + feature.attributes.id + '_' + userID +
                                '" value="0"><input type="checkbox" name="' + command + '_' + feature.attributes.id +'_' + userID +
                                '" id="' + command + '_' + feature.attributes.id +'_' + userID +
                                '" value="1"><label for="' + command + '_' + feature.attributes.id+'_' + userID +'">' + label + '</label></td>';
                    else
                        return '<td><input type="hidden" name="' + command + '_' + feature.attributes.id+'_' +userID +
                                '" value="0"></td>';
                }

      row = row + '<td>' + feature.attributes.featureDistance + 'm</td>';

      if(typeof indiciaData.user_id === 'undefined' || indiciaData.user_id===0) {
         row = row + /* '<td>Yes</td>' + */ '<td></td><td>Please login/register before requesting a holiday square.</td>';
      } else if(feature.attributes.isAssignee) {
         row = row + /* '<td>Yes</td>' + */ '<td>Assigned to you</td>' + buildAction('unassign_holiday', 'Unassign', feature, indiciaData.userID, true);
      } else {
         row = row + /* '<td>Yes</td>' + */ '<td>Not assigned to you</td>' + buildAction('assign_holiday', 'Assign', feature, indiciaData.userID, true);
      }
      if(runningTotal < indiciaData.limit) {
        $('#featureTableBody').append('<tr' + ($('#featureTableBody tr').length % 2 ? '' : ' class="odd"') + '>' + row + '</tr>');
      }

      return true;
    });
    if(typeof indiciaData.user_id !== 'undefined' && indiciaData.user_id!==0) {
      $('#featureTable').after('<input type="submit" class="indicia-button apply-actions-button" value="Carry out checked actions">');
    }
  }

  mapInitialisationHooks.push(function (div) {

    var baseStyle               = { strokeWidth: 4, fillOpacity: 0 },
        unassignedLocationStyle = new OpenLayers.Style(),
        unassignedLocationRule  = new OpenLayers.Rule({ symbolizer: $.extend({strokeColor: 'Yellow'}, baseStyle) }),
        unassignedLocationStyleMap,
        myLocationStyle         = new OpenLayers.Style(),
        myLocationRule          = new OpenLayers.Rule({ symbolizer: $.extend({strokeColor: 'Red'}, baseStyle) }),
        myLocationStyleMap,
        othersLocationStyle     = new OpenLayers.Style(),
        othersLocationRule      = new OpenLayers.Rule({ symbolizer: $.extend({strokeColor: 'Green'}, baseStyle) }),
        othersLocationStyleMap,
        holidayLocationStyle    = new OpenLayers.Style(),
        holidayLocationRule     = new OpenLayers.Rule({ symbolizer: $.extend({strokeColor: 'Blue'}, baseStyle) }),
        holidayLocationStyleMap,
        labelRule = new OpenLayers.Rule({
            maxScaleDenominator: 1000000,
            symbolizer: {
                label : '${name}',
                fontSize: '16px',
                fontFamily: 'Verdana, Arial, Helvetica,sans-serif',
                fontWeight: 'bold',
                fontColor: '#000000',
                labelAlign: 'cb',
                labelYOffset: '10'
            }
        });

/*
    unassignedLocationStyle.addRules([unassignedLocationRule, labelRule]);
    unassignedLocationStyleMap          = new OpenLayers.StyleMap({'default': unassignedLocationStyle});
    indiciaData.unassignedLocationLayer = new OpenLayers.Layer.Vector('Unassigned Sites', {styleMap: unassignedLocationStyleMap, displayInLayerSwitcher: true});
    div.map.addLayer(indiciaData.unassignedLocationLayer);
*/
    myLocationStyle.addRules([myLocationRule, labelRule]);
/*
    myLocationStyleMap                  = new OpenLayers.StyleMap({'default': myLocationStyle});
    indiciaData.myLocationLayer         = new OpenLayers.Layer.Vector('My Sites', {styleMap: myLocationStyleMap, displayInLayerSwitcher: true});
    div.map.addLayer(indiciaData.myLocationLayer);

    othersLocationStyle.addRules([othersLocationRule, labelRule]);
    othersLocationStyleMap              = new OpenLayers.StyleMap({'default': othersLocationStyle});
    indiciaData.othersLocationLayer     = new OpenLayers.Layer.Vector('Sites assigned to others', {styleMap: othersLocationStyleMap, displayInLayerSwitcher: true});
    div.map.addLayer(indiciaData.othersLocationLayer);
*/
    holidayLocationStyle.addRules([holidayLocationRule, labelRule]);
    holidayLocationStyleMap             = new OpenLayers.StyleMap({'default': holidayLocationStyle, 'mine': myLocationStyle});
    indiciaData.holidayLocationLayer    = new OpenLayers.Layer.Vector('Holiday Sites', {styleMap: holidayLocationStyleMap, displayInLayerSwitcher: true});
    div.map.addLayer(indiciaData.holidayLocationLayer);

    indiciaFns.populateFeatures();

  });

})(jQuery);