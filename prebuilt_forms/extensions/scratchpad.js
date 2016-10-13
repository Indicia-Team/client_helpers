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
    inputDirty = input.replace(/<div>/g, '').replace(/<\/div>/g, '<br>');
    // HTML whitespace clean
    inputDirty = input.replace(/&nbsp;/g, ' ');
    // The user might have pasted a comma-separated list
    inputDirty = inputDirty.replace(/,/g, '<br>');
    inputList = inputDirty.split(/<br(\/)?>/);

    $.each(inputList, function () {
      var token;
      if (typeof this !== 'undefined') {
        token = this.trim();
        if (token !== '' && inArrayCaseInsensitive(token, inputClean) === -1) {
          if (token === 'bob') {
            token = '<span style="color: red">' + token + '</span>';
          }
          inputClean.push(token);
        }
      }
    });
    $('#scratchpad-input').html(inputClean.join('<br/>'));
  }

  function matchToDb() {
    var listForDb = [];
    // convert the list of input values by adding quotes, so they can go into a report query
    $.each(inputClean, function () {
      listForDb.push("'" + this + "'");
    });
    $.ajax({
      dataType: 'jsonp',
      url: indiciaData.read.url + 'index.php/services/report/requestReport',
      data: {
        report: 'library/scratchpad/' + indiciaData.scratchpadSettings.match,
        reportSource: 'local',
        list: listForDb.join(','),
        auth_token: indiciaData.read.auth_token,
        nonce: indiciaData.read.nonce
      },
      success: function (data) {
        alert('Got it');
      }
    });
  }

  $('#scratchpad-check').click(function () {
    tidyInput();
    matchToDb();
  });
});