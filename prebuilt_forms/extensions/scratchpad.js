jQuery(document).ready(function ($) {
  'use strict';

  var inputClean = [];

  /**
   * A case insensitive inArray function.
   * @param needle
   * @param haystackArray
   * @returns {number}
   */
  function inArrayCaseInsensitive(needle, haystackArray) {
    var result = -1;
    $.each(haystackArray, function (index, value) {
      if (value.toLowerCase() === needle.toLowerCase()) {
        result = index;
        return;
      }
    });
    return result;
  }

  function simplify(text) {
    return text.toLowerCase().replace(/\(.+\)/g, '')
      .replace(/ae/g, 'e').replace(/\. /g, '* ')
      .replace(/[^a-zA-Z0-9\+\?*]/g, '');
  }

  function tidyInput() {
    var input = $('#scratchpad-input').html();
    var inputDirty;
    var inputList;
    inputClean = [];
    // clear out contenteditable divs (used for CRLF)
    inputDirty = input.replace(/<div>/g, '<br>').replace(/<\/div>/g, '<br>');
    // HTML whitespace clean
    inputDirty = inputDirty.replace(/&nbsp;/g, ' ');
    // Remove error spans
    inputDirty = inputDirty.replace(/<span[^>]*>/g, '').replace(/<\/span>/g, '');
    // Remove stuff in parenthesis - left from a previous scan results?
    inputDirty = inputDirty.replace(/\([^\)]*\)/g, '');
    // The user might have pasted a comma-separated list
    inputDirty = inputDirty.replace(/,/g, '<br>');
    inputList = inputDirty.split(/<br(\/)?>/);

    $.each(inputList, function () {
      var token;
      if (typeof this !== 'undefined') {
        token = this.trim();
        if (token !== '' && inArrayCaseInsensitive(token, inputClean) === -1) {
          inputClean.push(token);
        }
      }
    });
    $('#scratchpad-input').html(inputClean.join('<br/>'));
  }

  function matchToDb() {
    var listForDb = [];
    var simplifiedListForDb = [];
    var reportParams = {
      report: 'reports_for_prebuilt_forms/scratchpad/match_' + indiciaData.scratchpadSettings.match + '.xml',
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
      success: function (data) {
        // @todo Case where a species is duplicated in the search list
        // @todo Editing a row should clear the matched flag
        // @todo Running a match should only check the rows that don't have a results flag
        var matches;
        var output = [];
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
              output.push('<span class="unmatched">' + rowInput + ' (unmatched)</span>');
            } else if (matches.length === 1) {
              if (matches[0].type === 'key') {
                output.push('<span class="matched"data-id="' + this.id + '">' +
                  matches[0].record.external_key + ' (' + matches[0].record.taxon + ')' +
                  '</span>');
              } else {
                // @todo Check this works and is handled properly (case where a name does not give a unique match)
                output.push('<span class="matched" data-id="' + this.id + '">' + matches[0].record.taxon + '</span>');
              }
            } else {
              output.push('<span class="unmatched">' + rowInput + ' (no unique match)</span>');
            }
          });
          $('#scratchpad-input').html(output.join('<br/>'));
        }
      }
    });
  }

  $('#scratchpad-check').click(function () {
    tidyInput();
    matchToDb();
  });
});
