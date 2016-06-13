var initSpeciesHints;

jQuery(document).ready(function($) {
  var speciesListJson;

  initSpeciesHints = function(filename) {
    $.getJSON(filename, function (json) {
      speciesListJson = json;
    });
  }

  function showHint(taxon, key) {
    if (typeof speciesListJson[key] !== "undefined") {
      $('#species-hints').prepend('<div class="species-hint">' +
        '<div class="species-hint-label">Additional info for records of ' + taxon + '</div>' +
        '<div class="species-hint-content">' + speciesListJson[key]) + '</div></div>';
      $('#species-hints-outer').show();
    }
  }

  if (typeof hook_species_checklist_new_row!=="undefined") {
    hook_species_checklist_new_row.push(function (data, row) {
      showHint(data.taxon, data.external_key);
    });
  }

  $('#occurrence\\:taxa_taxon_list_id\\:taxon').result(function(event, data, value) {
    $('#species-hints-outer').hide();
    $('#species-hints').html('');
    showHint(data.original, data.external_key);
  });

});