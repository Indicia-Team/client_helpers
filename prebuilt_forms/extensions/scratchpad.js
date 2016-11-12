jQuery(document).ready(function ($) {
  'use strict';

  var processed = {};
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
    // The user might have pasted a comma-separated list
    inputDirty = inputDirty.replace(/,/g, '<br>');
    inputList = inputDirty.split(/<br(\/)?>/);

    $.each(inputList, function () {
      var token;
      if (typeof this !== 'undefined') {
        token = this.trim();
        if (token !== '' && inArrayCaseInsensitive(token, inputClean) === -1) {
          if (token === 'bob') {
            token = '<span style="color:red">bob</span>';
          }
          inputClean.push(token);
        }
      }
    });
    $('#scratchpad-input').html(inputClean.join('<br/>'));
  }

  function matchToDb() {
    var listForDb = [];
    var reportParams = {
      report: 'library/scratchpad/match_' + indiciaData.scratchpadSettings.match + '.xml',
      reportSource: 'local',
      auth_token: indiciaData.read.auth_token,
      nonce: indiciaData.read.nonce
    };
    // convert the list of input values by adding quotes, so they can go into a report query
    $.each(inputClean, function () {
      listForDb.push("'" + this.toLowerCase() + "'");
    });
    reportParams.list = listForDb.join(',');
    $.extend(reportParams, indiciaData.scratchpadSettings.filters);
    $.ajax({
      dataType: 'jsonp',
      url: indiciaData.read.url + 'index.php/services/report/requestReport',
      data: reportParams,
      success: function (data) {
        if (typeof data.error !== 'undefined') {
          alert(data.error);
        } else {
          $('#scratchpad-output').html('');
          $.each(data, function () {
            $('#scratchpad-output').append('<div data-id="' + this.id + '">' + this.label + '</div>');
          });
        }
      }
    });
  }

  $('#scratchpad-check').click(function () {
    tidyInput();
    matchToDb();
  });
});