jQuery(document).ready(function ($) {
  'use strict';

  var inputClean = [];

  function simplify(text) {
    return text.toLowerCase().replace(/\(.+\)/g, '')
      .replace(/ae/g, 'e').replace(/\. /g, '* ')
      .replace(/[^a-zA-Z0-9\+\?*]/g, '');
  }

  function tidyInput() {
    var input = $('#scratchpad-input')[0].innerText;
    var inputDirty;
    var inputList;
    inputClean = [];
    // HTML whitespace clean
    inputDirty = input.replace(/&nbsp;/g, ' ');
    // Remove stuff in parenthesis - left from a previous scan result, or subgenera, both are not needed.
    inputDirty = inputDirty.replace(/\([^\)]*\)/g, '');
    // The user might have pasted a comma-separated list
    inputDirty = inputDirty.replace(/,/g, '\n');
    inputList = inputDirty.split('\n');

    $.each(inputList, function () {
      var token;
      if (this) {
        token = this.trim();
        if (token !== '') {
          inputClean.push(token);
        }
      }
    });
    $('#scratchpad-input').html(inputClean.join('<br/>'));
  }

  /**
   * Handles the response from the warehouse for a request to match the list of species provided against the species
   * listed in the database.
   * @param data
   */
  function matchResponse(data) {
    // @todo Case where a species is duplicated in the search list
    // @todo Editing a row should clear the matched flag
    // @todo Running a match should only check the rows that don't have a results flag
    var matches;
    var output = [];
    var foundIds = [];
    $('#scratchpad-remove-duplicates').attr('disabled', 'disabled');
    $('#scratchpad-save').attr('disabled', 'disabled');
    if (typeof data.error !== 'undefined') {
      alert(data.error);
    } else {
      $.each(inputClean, function (idx, rowInput) {
        matches = [];
        $.each(data, function () {
          if (rowInput.toLowerCase() === this.external_key.toLowerCase()) {
            matches.push({ type: 'key', record: this });
          } else if (simplify(rowInput) === this.simplified) {
            matches.push({ type: 'term', record: this });
          }
        });
        if (matches.length === 0) {
          // Input item was not matched
          output.push('<span class="unmatched" data-state="unmatched">' + rowInput + ' (unmatched)</span>');
        } else if (matches.length === 1 && $.inArray(matches[0].record.id, foundIds) > -1) {
          // Input matched but this is the 2nd instance of same matched taxon
          output.push('<span class="unmatched" data-state="duplicate">' + rowInput + ' (duplicate species)</span>');
          $('#scratchpad-remove-duplicates').removeAttr('disabled');
        } else if (matches.length === 1) {
          foundIds.push(matches[0].record.id);
          if (matches[0].type === 'key') {
            output.push('<span class="matched" data-id="' + matches[0].record.id + '">' +
              matches[0].record.external_key + ' (' + matches[0].record.taxon + ')' +
              '</span>');
          } else {
            // @todo Check this works and is handled properly (case where a name does not give a unique match)
            output.push('<span class="matched" data-id="' + matches[0].record.id + '">' + matches[0].record.taxon + '</span>');
          }
        } else {
          output.push('<span class="unmatched" data-state="non-unique">' + rowInput +
            ' (name not specific enough - unique match could not be found)</span>');
        }
        if (foundIds.length) {
          $('#scratchpad-save').removeAttr('disabled');
        }
      });
      $('#scratchpad-input').html(output.join('<br/>'));
    }
  }

  function matchToDb() {
    var listForDb = [];
    var simplifiedListForDb = [];
    var reportParams = {
      report: 'reports_for_prebuilt_forms/scratchpad/match_' + indiciaData.scratchpadSettings.entity + '.xml',
      reportSource: 'local',
      auth_token: indiciaData.read.auth_token,
      nonce: indiciaData.read.nonce
    };
    // convert the list of input values by adding quotes, so they can go into a report query. Also create a version of
    // the list with search term simplification applied to ensure things like spacing and quotes are ignored.
    $.each(inputClean, function () {
      listForDb.push("'" + this.toLowerCase() + "'");
      simplifiedListForDb.push(simplify(this));
    });
    reportParams.list = listForDb.join(',');
    reportParams.simplified_list = simplifiedListForDb.join(',');
    $.extend(reportParams, indiciaData.scratchpadSettings.filters);
    $.ajax({
      dataType: 'jsonp',
      url: indiciaData.read.url + 'index.php/services/report/requestReport',
      data: reportParams,
      success: matchResponse
    });
  }

  $('#scratchpad-check').click(function () {
    tidyInput();
    matchToDb();
  });

  $('#scratchpad-remove-duplicates').click(function () {
    $('[data-state="duplicate"]').next('br').remove();
    $('[data-state="duplicate"]').remove();
  });

  /**
   * Convert the actual matched values into a data list to store.
   */
  $('#entry_form').submit(function () {
    var entries = [];
    $.each($('span.matched'), function () {
      entries.push($(this).attr('data-id'));
    });
    $('#hidden-entries-list').val(entries.join(';'));
  });

  $('#scratchpad-cancel').click(function() {
    window.location = indiciaData.scratchpadSettings.returnPath;
  });
});
