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
})(jQuery);
