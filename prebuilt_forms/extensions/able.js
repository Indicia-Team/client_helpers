jQuery(document).ready(function ($) {
  'use strict';

  var message;

  function parseQuery(queryString) {
    var query = {};
    var pairs = queryString.split('&');
    for (var i = 0; i < pairs.length; i++) {
        var pair = pairs[i].split('=');
        query[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1] || '');
    }
    return query;
  }

  /**
   * Copy href to data-orig-href to make it easy to reset.
   *
   * For Indicia report download links.
   */
  function captureOriginalIndiciaDownloadLinkSettings() {
    $.each($('.report-download-link a'), function() {
      $(this).attr('data-orig-href', $(this).attr('href'));
    });
  }

  function setDatasetFilter() {
    var currentDataset = $('#select-dataset').val();
    // Either a filter or location ID.
    var datasetId = currentDataset.replace(/^.+:/, '');
    var filterDef = null;
    // Reset.
    $('#select-dataset-filter').val('');
    $('#select-dataset-location').val('');
    // Apply new filter.
    if (currentDataset.match(/^filter_id:/)) {
      // Apply to ES filter.
      $('#select-dataset-filter').val(datasetId);
      // Apply to Indicia filter.
      filterDef = JSON.parse($('#select-dataset option:selected').attr('data-filter-def'));
    } else if (currentDataset.match(/^location_id:/)) {
      // Apply to ES filter.
      $('#select-dataset-location').val(datasetId);
      // Apply to Indicia filter.
      filterDef = {
        indexed_location_list: datasetId
      };
    }
    if (filterDef) {
      $.each($('.report-download-link a'), function() {
        var urlParts = $(this).attr('data-orig-href').split('?', 2);
        var qryObj = parseQuery(urlParts[1]);
        $.each(filterDef, function(key, val) {
          // Original query settings take precedence over filter.
          qryObj[key + '_context'] = val;
        });
        $(this).attr('href', urlParts[0] + '?' + $.param(qryObj));
      });
    }
    $('.do-download,.report-download-link a').attr('disabled', !filterDef);
  }

  // If only 1 option, hide the control and replace with information.
  if ($('#select-dataset option[value!=""]').length === 1) {
    message = indiciaData.lang.downloadPermissionsControl.DownloadDataFor
      .replace('{1}', $('#select-dataset option[value!=""]').text());
    $('#select-dataset').closest('.form-group')
      .hide()
      .after('<div class="alert alert-info">' + message + '</div>');
    $('#select-dataset').val($('#select-dataset option[value!=""]').val());
  }
  $('#select-dataset').change(setDatasetFilter);
  captureOriginalIndiciaDownloadLinkSettings();
  setDatasetFilter();


});