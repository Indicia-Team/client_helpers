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
 */

var bindSpeciesAutocomplete, initButtons, _getCentroid, getCentroid, processDeleted, MyMousePositionControl;

(function ($) {

  if (typeof OpenLayers != 'undefined') {
    MyMousePositionControl=OpenLayers.Class(
      OpenLayers.Control.MousePosition,
      {
        formatOutput: function(lonLat) {

          var digits = parseInt(this.numDigits),
              newHtml, lat, latDeg, latMin, latSec, long, longDeg, longMin, longSec; // 'D' = Decimal degrees, 'DM' = Degrees + Decimal Minutes, 'DMS' = Degrees, Minutes + Decimal Seconds TODO as form option

          // Get the system for the main sref
          var system = original_system = $("#imp-sref-system").val().toLowerCase();
          if (typeof indiciaData.srefHandlers[original_system.toLowerCase()] !== 'undefined') {
            system = indiciaData.srefHandlers[original_system.toLowerCase()].srid.toString(10);
          }
          system = "EPSG:" + system;
          // Convert the lonlat to a geometry: srefHandlers pointToGridNotation needs a Geom {x, y}
          geom = new OpenLayers.Geometry.Point(lonLat.lon, lonLat.lat);
          if (this.displayProjection.getCode() !== system) {
            geom.transform(this.displayProjection.getCode(), system);
          }

          if (original_system == '4326') {
            latDeg = Math.abs(geom.y);
            latMin = (latDeg - Math.floor(latDeg))*60;
            latSec = (latMin - Math.floor(latMin))*60;
            longDeg = Math.abs(geom.x);
            longMin = (longDeg - Math.floor(longDeg))*60;
            longSec = (longMin - Math.floor(longMin))*60;
            // Approx 1 m resolution
            switch ('DM' /* mapMousePositionFormat */) {
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
                (geom.y > 0 ? 'N' : 'S') +
                lat +
                ' ' +
                (geom.x > 0 ? 'E' : 'W') +
                long;
          } else if (typeof indiciaData.srefHandlers[original_system.toLowerCase()] !== 'undefined') {
            newHtml = indiciaData.srefHandlers[original_system.toLowerCase()].pointToGridNotation(geom, 10 /* mapMousePositionPrecision */);
          } else {
            newHtml =  system + ' ' + geom.x + ', ' + geom.y;
          }
          return newHtml;
        },
        CLASS_NAME:'MyMousePositionControl'
      }
    );
  };

bindSpeciesAutocomplete = function (selectorID, target, url, lookupListId, lookupListFilterField, lookupListFilterValues, readAuth, max) {
  // inner function to handle a selection of a taxon from the autocomplete
  var handleSelectedTaxon = function(event, data) {
    var name = $('#'+target).attr('name').split(':');
    name[2] = data.id;
    $('#'+target).attr('name', name.join(':')).addClass('required').removeAttr('disabled').removeAttr('readonly');
    if($('#'+target).val() == '') $('#'+target).val(0);
    var parent = $('#'+target).parent();
    if(parent.find('.deh-required').length == 0) parent.append('<span class="deh-required">*</span>');
  };

  var extra_params = {
        view : 'detail',
        orderby : 'taxon',
        mode : 'json',
        qfield : 'taxon',
        auth_token: readAuth.auth_token,
        nonce: readAuth.nonce,
        taxon_list_id: lookupListId,
        allow_data_entry: 't'
  };

  if(typeof lookupListFilterField != 'undefined'){
    extra_params.query = '{"in":{"'+lookupListFilterField+'":'+lookupListFilterValues+'}}';
  };

  // Attach auto-complete code to the input
  ctrl = $('#' + selectorID).autocomplete(url+'/taxa_taxon_list', {
      extraParams : extra_params,
      max : max,
      mustMatch : true,
      parse: function(data) {
        var results = [];
        jQuery.each(data, function(i, item) { results[results.length] = {'data' : item, 'result' : item.taxon, 'value' : item.id}; });
        return results;
      },
      formatItem: function(item) { return item.taxon; }
  });
  ctrl.on('result', handleSelectedTaxon);
  setTimeout(function() { $('#' + ctrl.attr('id')).focus(); });
};

initButtons = function(){
  $('.remove-button').click(function(){
    var myRow = $(this).closest('tr');
    // we leave the field names the same, so that the submission builder can delete the occurrence.
    // need to leave as enabled, so set as readonly.
    myRow.find('input').val('').filter('.occValField').attr('readonly','readonly').removeClass('required');
    myRow.find('.deh-required').remove();
  });

  $('.clear-button').click(function(){
    var myFieldset = $(this).closest('fieldset');
    myFieldset.find('.hasDatepicker').val('').removeClass('required');
    myFieldset.find('.occValField,.smp-input,[name=taxonLookupControl]').val('').attr('disabled','disabled').removeClass('required'); // leave the count fields as are.
    myFieldset.find('table .deh-required').remove();
  });
}

// not happy about centroid calculations: lines and multipoints seem to take first vertex
// mildly recursive.
_getCentroid = function(geometry){
  var retVal;
  retVal = {sumx: 0, sumy: 0, count: 0};
  switch(geometry.CLASS_NAME){
    case 'OpenLayers.Geometry.Point':
      retVal = {sumx: geometry.x, sumy: geometry.y, count: 1};
      break;
    case 'OpenLayers.Geometry.MultiPoint':
    case 'OpenLayers.Geometry.MultiLineString':
    case 'OpenLayers.Geometry.LineString':
    case 'OpenLayers.Geometry.MultiPolygon':
    case 'OpenLayers.Geometry.Collection':
      var retVal = {sumx: 0, sumy: 0, count: 0};
      for(var i=0; i< geometry.components.length; i++){
        var point=_getCentroid(geometry.components[i]);
        retVal = {sumx: retVal.sumx+point.sumx, sumy: retVal.sumy+point.sumy, count: retVal.count+point.count};
      }
      break;
    case 'OpenLayers.Geometry.Polygon': // only do outer ring
      var point=geometry.getCentroid();
      retVal = {sumx: point.x*geometry.components[0].components.length, sumy: point.y*geometry.components[0].components.length, count: geometry.components[0].components.length};
      break;
  }
  return retVal;
}
getCentroid=function(geometry){
  var oddball=_getCentroid(geometry);
  return new OpenLayers.Geometry.Point(oddball.sumx/oddball.count, oddball.sumy/oddball.count);
}
// Only allow one delete at a time: prevents removal of last subsample.
processDeleted=function(){
  $('.subSampleDelete').change(function(){
    if($(this).attr('checked'))
      $('.subSampleDelete').not(this).attr('disabled','disabled');
    else
      $('.subSampleDelete').removeAttr('disabled');
  })
}


}) (jQuery);
