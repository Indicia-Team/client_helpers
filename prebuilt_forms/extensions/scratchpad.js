jQuery(document).ready(function ($) {
  'use strict';

  var processed = {};

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
    var inputClean = [];
    var inputList;
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

  $('#scratchpad-check').click(function () {
    tidyInput();
  });
});