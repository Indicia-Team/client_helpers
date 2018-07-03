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
  $('#treatment-list tbody').click(function(evt) {
    var row = $(evt.target).parents('tr:first')[0];
    var id = $(row).attr('id').replace(/^row/, '');
    indiciaData.reports.dynamic.grid_treatment_list.highlightFeatureById(
      id, true,  indiciaData.reports.dynamic.grid_treatment_list[0]
    );
    $.ajax({
      dataType: 'jsonp',
      url: indiciaData.read.url + 'index.php/services/report/requestReport?' +
        'report=library/samples/filterable_explore_list.xml' +
        '&reportSource=local&sample_id=' + id +
        '&smpattrs=572,573,574,575,576,577,578' +
        '&nonce=' + indiciaData.read.nonce + '&auth_token=' + indiciaData.read.auth_token +
        '&mode=json&callback=?',
      success: function(data) {
        var info = {
          Date: data[0].date,
          Mapref: data[0].entered_sref
        };
        if (data[0].attr_sample_572 !== null) {
          info.Duration = data[0].attr_sample_572
          if (data[0].attr_sample_term_578 != null) {
            info.Duration += ' ' + data[0].attr_sample_term_578;
          }
        }
        if (data[0].attr_sample_term_573 !== null) {
          info.Method = data[0].attr_sample_term_573;
        }
        if (data[0].attr_sample_term_574 !== null) {
          info['Species treated'] = data[0].attr_sample_term_574;
        }
        if (data[0].attr_sample_term_575 !== null) {
          info.Removal = data[0].attr_sample_term_575;
        }
        if (data[0].attr_sample_term_576 !== null) {
          info['Treatement performed by'] = data[0].attr_sample_term_576;
        }
        if (data[0].attr_sample_577 !== null) {
          info['Materials used'] = data[0].attr_sample_577;
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
