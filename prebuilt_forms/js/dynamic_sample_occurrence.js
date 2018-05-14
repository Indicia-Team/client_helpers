jQuery(document).ready(function docReady($) {
  // On selection of a taxon, load any dynamically linked attrs into the form.
  $('input#occurrence\\:taxa_taxon_list_id').change(function pickTaxon() {
    var result = $('input#occurrence\\:taxa_taxon_list_id').attr('data-result');
    var resultObj = JSON.parse(result);
    var urlSep = indiciaData.ajaxUrl.indexOf('?') === -1 ? '?' : '&';
    $.get(indiciaData.ajaxUrl + '/dynamicattrs/0' + urlSep +
        'survey_id=' + $('#survey_id').val() +
        '&taxa_taxon_list_external_key=' + resultObj.external_key, null,
      function getAttrsReportCallback(data) {
        $('#species-dynamic-attributes').html(data);
      }
    );
  });
});
