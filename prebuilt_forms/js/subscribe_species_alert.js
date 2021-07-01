(function ($) {
  indiciaData.onloadFns.push(function() {
    // Add a custom required rule on the species input control, so that the rule
    //can be dependent on the species list selection control.
    $('#taxa_taxon_list_id\\:taxon').rules('add', {
      required: function() {
        return $('#species_alert\\:taxon_list_id').val()==='';
      },
      messages: {
        required: "Select either a species or a list of species to trigger the alert for in the control below."
      }
    });
  });
})(jQuery);

jQuery(document).ready(function($) {
  // Linked list handling for the location type selector. Only needed for
  // location autocomplete mode as automated if the location picker control is
  // a select.
  if ($('#imp-location\\:name').length > 0) {
    $('#location_type').change(function(e) {
      if ($('#location_type').val()) {
        $('#imp-location\\:name').setExtraParams({location_type_id: $('#location_type').val()});
        $('#imp-location\\:name').prop('disabled', false);
      } else {
        $('#imp-location\\:name').unsetExtraParams('location_type_id');
        $('#imp-location\\:name').prop('disabled', true);
      }

    });
  }

  indiciaFns.on('change', '.hierarchical-location-select', {}, function() {
    var select = this;
    var thisIndex = $(select).attr('data-index');
    $.each($('.hierarchical-location-select'), function() {
      if ($(this).attr('data-index') > thisIndex) {
        $(this).remove();
      }
    });
    if ($(select).val()) {
      // Copy the value over so it is saved.
      $('#hidden-location-id').val($(select).val());
      // Fetch children.
      $.ajax({
        dataType: 'jsonp',
        url: indiciaData.read.url + 'index.php/services/data/location',
        data: {
          parent_id: $(select).val(),
          auth_token: indiciaData.read.auth_token,
          nonce: indiciaData.read.nonce
        },
        success: function (data) {
          var newSelect;
          if (data.length > 0) {
            newSelect = $('<select class="' + select.className + '" />')
              .attr('data-index', parseInt($(select).attr('data-index'), 10) + 1)
              .attr('name', $(select).attr('name'))
              .append($('<option value="">- Please select -</option>'))
              .insertAfter($(select))
              .click(function() {
                indiciaData.mapdiv.locationSelectedInInput(indiciaData.mapdiv, $(this).val())
              });
            data.forEach(function(item) {
              newSelect.append($('<option value="' + item.id + '">' + item.name + '</option>'));
            });
          }
        }
      });
    } else {
      // Nothing selected. Need to store the parent location ID in the hidden
      // input in case the form is posted.
      if ($('.hierarchical-location-select[data-index="' + (thisIndex - 1) + '"]').length > 0) {
        $('#hidden-location-id').val($('.hierarchical-location-select[data-index="' + (thisIndex - 1) + '"]').val());
      }
    }


  });
});

