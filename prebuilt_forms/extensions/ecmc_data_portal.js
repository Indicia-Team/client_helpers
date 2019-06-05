var drawPoints, saveSample, deleteSample;

jQuery(document).ready(function($) {

  /* Effort/sightings radio buttons */
  $('#points-params input:radio').change(function(e) {
    if (typeof indiciaData.reports!=="undefined") {
      e.preventDefault();
      indiciaData.reports.dynamic.grid_report_grid_0[0].settings.extraParams.effort_or_sightings=$(e.currentTarget).val();
      indiciaData.reports.dynamic.grid_report_grid_0.reload(true);
      return false;
    }
  });
  $('#points-params input:radio').change();

  /* Transect selection */

  function updateSelectedTransect() {
    // Make the add new buttons link to the correct transect sample
    $('#edit-effort,#edit-sighting').attr('href', $('#edit-effort').attr('href')
        .replace(/transect_sample_id=[0-9]+/, 'transect_sample_id=' + $('#transect-param').val()));
    if (typeof indiciaData.reports!=="undefined") {
      indiciaData.reports.dynamic.grid_report_grid_0[0].settings.extraParams.transect_sample_id=$('#transect-param').val();
      indiciaData.reports.dynamic.grid_report_grid_0.reload(true);
    }
  }

  $('#transect-param').change(updateSelectedTransect);
  if ($('#transect-param').length>0) {
    updateSelectedTransect();
  }

  /* New transect button */

  $('#new-transect').click(function(e) {
    e.preventDefault();
    $('#popup-transect-id').val('');
    $.fancybox({"href":"#add-transect-popup"});
    return false;
  });

  $('#transect-popup-cancel').click(function() {
    $.fancybox.close();
  });

  $('#transect-popup-save').click(function() {
    if ($('#popup-transect-id').val()==='') {
      alert(indiciaData.langRequireTransectID);
      return;
    }
    else if ($('#popup-sample-type').val()==='') {
      alert(indiciaData.langRequireSampleType);
      return;
    }
    var sample = {"website_id": indiciaData.websiteId, "user_id": indiciaData.user_id,
        "survey_id": indiciaData.surveyId, "parent_id": indiciaData.surveySampleId,
        "date": $('#popup-transect-date').val(), "sample_method_id":indiciaData.transectSampleMethodId,
        "entered_sref":indiciaData.sampleSref, "entered_sref_system": 4326
    };
    sample['smpAttr:'+indiciaData.transectIdAttrId] = $('#popup-transect-id').val();
    sample['smpAttr:'+indiciaData.sampleTypeAttrId] = $('#popup-sample-type').val();
    $.post(indiciaData.saveSampleUrl,
      sample,
      function (data) {
        if (typeof data.error === 'undefined') {
          alert('The transect has been saved. You can now add effort and sightings points for this transect.');
          $('#transect-param').append('<option value="'+data.outer_id+'">'+$('#popup-transect-id').val()+' - '+
               $('#popup-sample-type option:selected').text()+'</option>');
          $('#transect-param').val(data.outer_id);
          $.fancybox.close();
        } else {
          alert(data.error);
        }
      },
      'json'
    );
  });

  /* Add point buttons */
  $('.edit-point').click(function(e) {
    $(e.currentTarget).attr('href', $(e.currentTarget).attr('href').replace(/transect_sample_id=\d+/, 'transect_sample_id='+$('#transect-param').val()));
  });

  function postToServer(s) {
	  $.post(indiciaData.postUrl,
		s,
		function (data) {
		  if (typeof data.error === 'undefined') {
			  indiciaData.reports.dynamic.grid_report_grid_0.reload(true);
		  } else {
			  alert(data.error);
		  }
		},
		'json'
	  );
	}
	delete_route = function(id, route) {
    if (confirm(indiciaData.langConfirmDelete.replace('{1}', route))) {
      var s = {
        "website_id": indiciaData.websiteId,
        "location:id":id,
        "location:deleted":"t"
      };
      postToServer(s);
    }
	}

  /* Lat long control stuff */
  if ($('#lat_long-lat').length>0) {
    var updateSref = function() {
      $('#sample\\:entered_sref').val(
        $('#lat_long-lat').val() + ', ' + $('#lat_long-long').val()
      );
    },
    coords = $('#sample\\:entered_sref').val().split(/, ?/);
    $('#lat_long-lat,#lat_long-long').blur(updateSref);
    // load existing value
    if (coords.length===2) {
      $('#lat_long-lat').val(coords[0]);
      $('#lat_long-long').val(coords[1]);
    }
  }

  /* Dynamic redirection stuff */
  if ($('#ecmc-redirect').length===1) {
    var setRedirect=function() {
      switch ($('#next-action').val()) {
        case 'effort':
          $('#ecmc-redirect').val('survey/points/edit-effort?parent_sample_id='+$('#parent_sample_id').val()+'&transect_sample_id='+$('#sample\\:parent_id').val());
          break;
        case 'sighting':
          $('#ecmc-redirect').val('survey/points/edit-sighting?parent_sample_id='+$('#parent_sample_id').val()+'&transect_sample_id='+$('#sample\\:parent_id').val());
          break;
        case 'surveypoints':
          $('#ecmc-redirect').val('survey/points-list');
          break;
      }
    };
    $('#next-action').change(setRedirect);
    setRedirect();
  }

  drawPoints = function() {
    var geoms=[], shadeIdx, shadeHex, style = {
      strokeWidth: 1,
      strokeColor: "#FF0000"
    };
    $.each(indiciaData.reportlayer.features, function(idx) {
      // set geometry colours so we get a gist of the sequence
      shadeIdx = Math.round(idx / indiciaData.reportlayer.features.length * 255);
      shadeHex = shadeIdx.toString(16);
      if (shadeHex.length===1) {
        shadeHex = '0' + shadeHex;
      }
      this.attributes.fill = '#FF' + shadeHex + shadeHex;
      this.attributes.stroke = '#00' + shadeHex + shadeHex;
      // store the point in the list to build the line
      geoms.push(this.geometry);
    });
    // Set a stylemap so the points colour up to show the sequence
    indiciaData.reportlayer.styleMap = new OpenLayers.StyleMap({
        default: {
          fillColor: '${fill}',
          strokeColor: '${stoke}',
          strokeWidth: 1,
          pointRadius: 6
        },
        select: {
          fillColor: '#FFFFFF',
          strokeColor: '#0000ff',
          strokeWidth: 4,
          pointRadius: 6
        }
      }
    );
    if (typeof indiciaData.routeFeature!=="undefined") {
      indiciaData.reportlayer.removeFeatures([indiciaData.routeFeature]);
    }
    indiciaData.routeFeature = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.LineString(geoms), {type: 'route'}, style);
    indiciaData.reportlayer.addFeatures([indiciaData.routeFeature]);
    indiciaData.reportlayer.redraw();
  };

  saveSample = function(sampleId) {
    var utc=$('#input-time-'+sampleId).val(),
        localTimeValId=$('#input-localtime-val-id-'+sampleId).val(),
        utcTimeValId=$('#input-utctime-val-id-'+sampleId).val(),
        lat=$('#input-lat-'+sampleId).val(),
        lng=$('#input-long-'+sampleId).val(),
        diff=$('#timediff-'+sampleId).val()
        localHour=parseInt(utc.substring(0, 2)) + parseInt(diff),
        localTime=(localHour < 10 ? '0' : '') + localHour + utc.substring(2);
    if (!lat.match(/^\-?[\d]+(\.[\d]+)?$/) || !lng.match(/^\-?[\d]+(\.[\d]+)?$/)) {
      alert('The latitude and longitude cannot be saved because values are not of the correct format.');
      return;
    }
    var data = {
      'website_id': indiciaData.website_id,
      'sample:id': sampleId,
      'sample:entered_sref': lat + ', ' + lng,
      'sample:entered_sref_system': 4326
    };
    data['smpAttr:' + indiciaData.utcTimeAttrId + ':'  + utcTimeValId] = utc;
    data['smpAttr:' + indiciaData.localTimeAttrId + ':'  + localTimeValId] = utc;
    $.post(
      indiciaData.ajaxFormPostUrl,
      data,
      function (data) {
        if (typeof data.error === "undefined") {
          $('#input-time-'+sampleId+',#input-lat-'+sampleId+',#input-long-'+sampleId).css('border-color','silver');
        } else {
          alert(data.error);
        }
      },
      'json'
    );
  };

  deleteSample = function(sampleId) {
    if (confirm('Are you sure you want to delete the selected point?')) {
      var data = {
        'website_id': indiciaData.website_id,
        'sample:id': sampleId,
        'sample:deleted': 't'
      };
      $.post(
        indiciaData.ajaxFormPostUrl,
        data,
        function (data) {
          if (typeof data.error === "undefined") {
            $('#row' + sampleId).remove();
            var toRemove = [];
            $.each(indiciaData.reportlayer.features, function() {
              if (typeof this.attributes!=="undefined" && this.attributes.sampleid==sampleId) {
                toRemove.push(this);
              }
            });
            indiciaData.reportlayer.removeFeatures(toRemove, {});
            redrawRoute();
            alert('The point has been deleted');
          } else {
            alert(data.error);
          }
        },
        'json'
      );
    }
  };

  function correctTimeSequence(sampleId) {
    var $row = $('tr#row' + sampleId),
      time = $('#input-time-' + sampleId).val(),
      pos = $('table.report-grid tbody tr').index($row);
    // shuffle the edited row up or down into correct chronological sequence
    if (pos>0) {
      while ($row.prev().length && $row.prev().find('.input-time').val() > time) {
        $row.prev().before($row);
        pos--;
      }
    }
    if (pos<$('table.report-grid tbody tr').length-1) {
      while ($row.next().length && $row.next().find('.input-time').val() < time) {
        $row.next().after($row);
        pos++;
      }
    }
  }

  function redrawRoute() {
    var geoms=[], point, style = {
      strokeWidth: 1,
      strokeColor: "#FF0000"
    };
    $.each($('table.report-grid tbody tr'), function() {
      point = new OpenLayers.Geometry.Point($(this).find('.input-long').val(), $(this).find('.input-lat').val());
      if (indiciaData.mapdiv.map.projection.getCode() != 4326) {
        point.transform('EPSG:4326', indiciaData.mapdiv.map.projection);
      }
      geoms.push(point);
    });
    indiciaData.mapdiv.removeAllFeatures(indiciaData.reportlayer, 'route')
    indiciaData.reportlayer.addFeatures([new OpenLayers.Feature.Vector(new OpenLayers.Geometry.LineString(geoms), {type: 'route'}, style)]);
  }

  // Change inputs on the transect points review screen will recolour to show they are edited.
  $('body').on('change', '.input-time,.input-lat,.input-long', function(e) {
    var sampleId = e.currentTarget.id.replace(/input\-(time|lat|long)\-/, ''),
        time=$('#input-time-'+sampleId).val(),
        lat=$('#input-lat-'+sampleId).val(),
        lng=$('#input-long-'+sampleId).val(),
        point;
    // if we have a valid time, lat and long, move the associated point
    if (time.match(/^([0-9]+):([0-5][0-9]):([0-5][0-9])$/) &&
        lat.match(/^\-?[\d]+(\.[\d]+)?$/) && lng.match(/^\-?[\d]+(\.[\d]+)?$/)) {
      $(e.currentTarget).css('border', 'solid 1px red');
      point = new OpenLayers.Geometry.Point(lng, lat);
      if (indiciaData.mapdiv.map.projection.getCode() != 4326) {
        point.transform('EPSG:4326', indiciaData.mapdiv.map.projection);
      }
      $.each(indiciaData.reportlayer.features, function () {
        if (this.id === sampleId) {
          this.move(new OpenLayers.LonLat(point.x, point.y));
        }
      });
      if ($(e.currentTarget).hasClass('input-time')) {
        correctTimeSequence(sampleId);
        redrawRoute();
      }
    }
  });

});