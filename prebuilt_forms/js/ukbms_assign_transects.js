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

  indiciaFns.displayFeatures = function(list) {

    var bounds = indiciaData.unassignedLocationLayer.map.getExtent(); // displayed extent.
    var type = $('[name=location\\:location_type_id]').val();
    indiciaData.unassignedLocationLayer.removeAllFeatures();
    indiciaData.myLocationLayer.removeAllFeatures();
    indiciaData.othersLocationLayer.removeAllFeatures();
    indiciaData.holidayLocationLayer.removeAllFeatures();
    $('#featureTableBody tr').remove();
    if(type == '') return;

    var report_URL = indiciaData.indiciaSvc + '/index.php/services/report/requestReport?report=library/locations/locations_list.xml' +
                        '&reportSource=local' +
                        '&mode=json' +
                        '&auth_token=' + indiciaData.read.auth_token + '&nonce=' + indiciaData.read.nonce +'&reset_timeout=true' + 
                        '&callback=?' +
                        '&location_type_id=' + type +
                        '&locattrs=' + indiciaData.cms_attr_id + ',' + indiciaData.holiday_attr_id +
                        '&orderby=name';
    $.getJSON(report_URL, function(data) {
        $.each(data, function(_idx, site) {

                var parser = new OpenLayers.Format.WKT(),
                    feature = parser.read(site.geom),
                    assignees = [],
                    isAssignee = false,
                    runningTotal = $('#featureTableBody tr').length,
                    row = '<td><a target="_blank" href="' + indiciaData.siteDetails + '?id=' + site.location_id + '">' + site.name + '</a></td>';

                if(list !== null && list.length > 0 && $.inArray(site.location_id, list) < 0)
                    return;

                var buildAction = function(command, label, site, userID, fullControl) {
                    if(fullControl)
                        return '<td><input type="hidden" name="' + command + '_' + site.location_id+'_' +userID +
                                '" value="0"><input type="checkbox" name="' + command + '_' + site.location_id+'_' +userID +
                                '" id="' + command + '_' + site.location_id+'_' + userID +
                                '" value="1"><label for="' + command + '_' + site.location_id+'_' + userID +'">' + label + '</label></td>';
                    else
                        return '<td><input type="hidden" name="' + command + '_' + site.location_id+'_' +userID +
                                '" value="0"></td>';
                }

                if (site['attr_location_' + indiciaData.cms_attr_id] !== '' && site['attr_location_' + indiciaData.cms_attr_id] !== null) {
                    assignees = site['attr_location_' + indiciaData.cms_attr_id].split(',')
                    for(var i = 0; i < assignees.length; i++) {
                        assignees[i] = assignees[i].trim();
                        if(assignees[i] == indiciaData.userID) {
                            isAssignee = true;
                            break;
                        }
                    }
                }

                if (indiciaData.mapdiv.indiciaProjection.projCode !== indiciaData.mapdiv.map.projection.projCode){
                    feature.geometry = feature.geometry.transform(div.indiciaProjection, indiciaData.mapdiv.map.projection);
                }
                if(feature.geometry.getBounds().intersectsBounds(bounds)) {
                    feature.attributes.name = site.name;
                    feature.attributes.id = site.location_id;
                    if (site['attr_location_' + indiciaData.holiday_attr_id] !== null && site['attr_location_' + indiciaData.holiday_attr_id]) {
                        if(isAssignee) {
                            indiciaData.holidayLocationLayer.addFeatures([feature]); // features added even if too many for list
                            row = row + '<td>Yes</td><td>Assigned to you</td>' + buildAction('unassign_holiday', 'Unassign', site, indiciaData.userID, true);
                        } else {
                            indiciaData.holidayLocationLayer.addFeatures([feature]); // features added even if too many for list
                            row = row + '<td>Yes</td><td>Not assigned to you</td>' + buildAction('assign_holiday', 'Assign', site, indiciaData.userID, true);
                        }
                    } else if (assignees.length === 0) {
                        indiciaData.unassignedLocationLayer.addFeatures([feature]); // features added even if too many for list
                        row = row + '<td>No</td><td>Unassigned</td>' + buildAction('request_assign', 'Request assignment', site, indiciaData.userID, false);
                    } else {
                        if(isAssignee) {
                            indiciaData.myLocationLayer.addFeatures([feature]); // features added even if too many for list
                            row = row + '<td>No</td><td>Assigned to you</td>' + buildAction('request_deassign', 'Request de-assignment', site, indiciaData.userID, false);
                        } else {
                            indiciaData.othersLocationLayer.addFeatures([feature]); // features added even if too many for list
                            row = row + '<td>No</td><td>Assigned to another</td>' + buildAction('request_assign', 'Request assignment', site, indiciaData.userID, false);
                        }
                    }
                    if(runningTotal < indiciaData.limit) {
                        $('#featureTableBody').append('<tr>' + row + '</tr>');
                    } else if(runningTotal === indiciaData.limit) {
                        $('#featureTableBody').append('<tr><td colspan=4>Simultaneous features limit reached.</td></tr>');
                    }
                }
                return true;
        });
        if(list !== null && list.length > 0) {
                var extent = indiciaData.unassignedLocationLayer.getDataExtent(),
                    nextextent = indiciaData.myLocationLayer.getDataExtent();
                if(extent === null)
                    extent = nextextent;
                else if(nextextent !== null)
                    extent.extend(nextextent);
                nextextent = indiciaData.othersLocationLayer.getDataExtent();
                if(extent === null)
                    extent = nextextent;
                else if(nextextent !== null)
                    extent.extend(nextextent);
                nextextent = indiciaData.holidayLocationLayer.getDataExtent();
                if(extent === null)
                    extent = nextextent;
                else if(nextextent !== null)
                    extent.extend(nextextent);
                if(extent !== null)
                    indiciaData.unassignedLocationLayer.map.zoomToExtent(extent);
        }
        if($('#featureTableBody tr').length > 0) {
            $('#featureTableBody').append('<tr><td></td><td></td><td></td><td><input type="submit" value="Carry out checked actions"></td></tr>');
        }
    });
  }

  mapInitialisationHooks.push(function (div) {

    var baseStyle               = { strokeWidth: 4, fillOpacity: 0 },
        unassignedLocationStyle = new OpenLayers.Style(),
        unassignedLocationRule  = new OpenLayers.Rule({ symbolizer: $.extend({strokeColor: 'Red'}, baseStyle) }),
        unassignedLocationStyleMap,
        myLocationStyle         = new OpenLayers.Style(),
        myLocationRule          = new OpenLayers.Rule({ symbolizer: $.extend({strokeColor: 'Green'}, baseStyle) }),
        myLocationStyleMap,
        othersLocationStyle     = new OpenLayers.Style(),
        othersLocationRule      = new OpenLayers.Rule({ symbolizer: $.extend({strokeColor: 'Blue'}, baseStyle) }),
        othersLocationStyleMap,
        holidayLocationStyle    = new OpenLayers.Style(),
        holidayLocationRule     = new OpenLayers.Rule({ symbolizer: $.extend({strokeColor: 'Yellow'}, baseStyle) }),
        holidayLocationStyleMap,
        labelRule = new OpenLayers.Rule({ symbolizer: {
                label : '${name}',
                fontSize: '16px',
                fontFamily: 'Verdana, Arial, Helvetica,sans-serif',
                fontWeight: 'bold',
                fontColor: '#000000',
                labelAlign: 'cb',
                labelYOffset: '10'
        }
      });

    unassignedLocationStyle.addRules([unassignedLocationRule, labelRule]);
    unassignedLocationStyleMap          = new OpenLayers.StyleMap({'default': unassignedLocationStyle});
    indiciaData.unassignedLocationLayer = new OpenLayers.Layer.Vector('Unassigned Sites', {styleMap: unassignedLocationStyleMap, displayInLayerSwitcher: true});
    div.map.addLayer(indiciaData.unassignedLocationLayer);

    myLocationStyle.addRules([myLocationRule, labelRule]);
    myLocationStyleMap                  = new OpenLayers.StyleMap({'default': myLocationStyle});
    indiciaData.myLocationLayer         = new OpenLayers.Layer.Vector('My Sites', {styleMap: myLocationStyleMap, displayInLayerSwitcher: true});
    div.map.addLayer(indiciaData.myLocationLayer);

    othersLocationStyle.addRules([othersLocationRule, labelRule]);
    othersLocationStyleMap              = new OpenLayers.StyleMap({'default': othersLocationStyle});
    indiciaData.othersLocationLayer     = new OpenLayers.Layer.Vector('Sites assigned to others', {styleMap: othersLocationStyleMap, displayInLayerSwitcher: true});
    div.map.addLayer(indiciaData.othersLocationLayer);

    holidayLocationStyle.addRules([holidayLocationRule, labelRule]);
    holidayLocationStyleMap             = new OpenLayers.StyleMap({'default': holidayLocationStyle});
    indiciaData.holidayLocationLayer    = new OpenLayers.Layer.Vector('Holiday Sites', {styleMap: holidayLocationStyleMap, displayInLayerSwitcher: true});
    div.map.addLayer(indiciaData.holidayLocationLayer);

    if(indiciaData.initFeatureIds.length > 0) {
        indiciaFns.displayFeatures(indiciaData.initFeatureIds);
    }
  });

})(jQuery);