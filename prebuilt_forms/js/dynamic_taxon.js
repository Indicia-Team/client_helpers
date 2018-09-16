jQuery(document).ready(function docReady($) {
  function changeTaxonRestrictionInputs() {
    var urlSep = indiciaData.ajaxUrl.indexOf('?') === -1 ? '?' : '&';
    if ($('#taxa_taxon_list\\:parent_id').val() !== '') {
      $.each($('.taxon-dynamic-attributes'), function loadAttrDiv() {
        var div = this;
        // 0 is a fake nid, since we don't care.
        $.get(indiciaData.ajaxUrl + '/dynamicattrs/0' + urlSep +
            'taxon_list_id=' + $('#taxa_taxon_list\\:taxon_list_id').val() +
            '&taxa_taxon_list_id=' + $('#taxa_taxon_list\\:parent_id').val() +
            '&language=' + indiciaData.userLang +
            '&options=' + JSON.stringify(indiciaData.dynamicAttrOptions), null,
          function getAttrsReportCallback(data) {
            $(div).html(data);
          }
        );
      });
    }
  }

  // On selection of a taxon or change of sex/stage attribute, load any dynamically linked attrs into the form.
  $('#taxa_taxon_list\\:parent_id').change(changeTaxonRestrictionInputs);
});
