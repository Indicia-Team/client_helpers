jQuery(document).ready(function docReady($) {
  // On page load, put the user's regions into the drop down.
  indiciaData.onloadFns.push(function() {
    if (indiciaData.userInnsRegions.length === 0) {
      $('#ctrl-wrap-dynamic-region_location_id').after('<div class="alert alert-warning">Awaiting registration with your RAPID LIFE regional team.</div>');
      $('#ctrl-wrap-dynamic-region_location_id').remove();
    } else if (indiciaData.userInnsRegions.length === 1) {
      $('#ctrl-wrap-dynamic-region_location_id').after(
        '<input type="hidden" name="dynamic-region_location_id" value="' + indiciaData.userInnsRegions[0]['location_id'] + '" />'
      );
      $('#ctrl-wrap-dynamic-region_location_id').remove();
      if (typeof indiciaData.regionLocationId === 'undefined') {
        $('form#dynamic-params').submit();
      }
    } else {
      $('#dynamic-region_location_id option').remove();
      $.each(indiciaData.userInnsRegions, function() {
        $('#dynamic-region_location_id').append(
          '<option value="' + this.location_id + '">' + this.location_name + '</option>'
        );
      });
      if (typeof indiciaData.regionLocationId !== 'undefined') {
        $('#dynamic-region_location_id option[value=' + indiciaData.regionLocationId + ']').attr('selected', 'selected');
      }
    }
  });

  // Handle display of treatment details.
  $('#treatment-list tbody').on('click', function(evt) {
    var row = $(evt.target).parents('tr:first')[0];
    var id = $(row).attr('id').replace(/^row/, '');
    indiciaData.reports.dynamic.grid_treatment_list.highlightFeatureById(
      id, true,  indiciaData.reports.dynamic.grid_treatment_list[0]
    );
    $.ajax({
      dataType: 'jsonp',
      url: indiciaData.read.url + 'index.php/services/report/requestReport?' +
        'report=projects/inns_mapper/treatment_info.xml' +
        '&reportSource=local&sample_id=' + id +
        '&nonce=' + indiciaData.read.nonce + '&auth_token=' + indiciaData.read.auth_token +
        '&mode=json&callback=?',
      success: function(data) {
        var info = {
          Date: data[0].date,
          Mapref: data[0].entered_sref,
          Source: data[0].source
        };
        if (data[0].duration !== null) {
          info.Duration = data[0].duration;
          if (data[0].duration_units != null) {
            info.Duration += ' ' + data[0].duration_units;
          }
        }
        if (data[0].method !== null) {
          info.Method = data[0].method;
        }
        if (data[0].species !== null) {
          info['Species treated'] = data[0].species;
        }
        if (data[0].removal !== null) {
          info.Removal = data[0].removal;
        }
        if (data[0].treatment !== null) {
          info['Treatment performed by'] = data[0].treatment;
        }
        if (data[0].materials !== null) {
          info['Materials used'] = data[0].materials;
        }
        if (data[0].comment !== null) {
          info['Other comments'] = data[0].comment;
        }
        var output = '<h3>Selected treatment details</h3><ul>';
        $.each(info, function(key, val) {
          output += '<li><strong>' + key + ':</strong> ' + val + '</li>';
        });
        output += '</ul>';
        $('#treatment-details').html(output);
      }
    });
  });
});
