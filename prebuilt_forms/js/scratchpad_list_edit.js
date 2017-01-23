jQuery(document).ready(function ($) {
  'use strict';

  var inputClean = [];

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
    // HTML whitespace clean
    inputDirty = input.replace(/&nbsp;/g, ' ');
    // Remove stuff in parenthesis - left from a previous scan result, or subgenera, both are not needed.
    inputDirty = inputDirty.replace(/\([^\)]*\)/g, '');
    // Comma separated or line separated should both work
    inputDirty = inputDirty.replace(/,/g, '<br>');
    inputList = inputDirty.split(/<br(\\)?>/g);
    $.each(inputList, function () {
      var token;
      var $el;
      var tokenText;
      if (this) {
        token = this.trim();
        // simple way to strip HTML, even if unbalanced
        tokenText = token.replace(/<(.+?)>/g, '');
        if (tokenText) {
          $el = $(token);
          if ($el.length && $el[0].localName === 'span' && $el.hasClass('matched')) {
            // matched strings are kept as they are and not changed
            inputClean.push(token);
          } else if ($el.length && $el[0].localName === 'button' && $el.hasClass('non-unique-name')) {
            // skip the matching options for a taxon that could not match a unique name
          } else {
            inputClean.push(tokenText);
          }
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
    var nonUniqueBatchIdx = 0;
    $('#scratchpad-remove-duplicates').attr('disabled', 'disabled');
    $('#scratchpad-save').attr('disabled', 'disabled');
    if (typeof data.error !== 'undefined') {
      alert(data.error);
    } else {
      $.each(inputClean, function (idx, rowInput) {
        matches = [];
        if ($(rowInput).length && $(rowInput)[0].localName === 'span' && $(rowInput).hasClass('matched')) {
          output.push(rowInput);
          return true; // to continue $.each
        }
        $.each(data, function () {
          if (rowInput.toLowerCase() === this.external_key.toLowerCase()) {
            matches.push({ type: 'key', record: this });
          } else if (simplify(rowInput) === this.simplified) {
            matches.push({ type: 'term', record: this });
          }
        });
        if (matches.length === 0) {
          // Input item was not matched
          output.push('<span class="unmatched" data-state="unmatched">' + rowInput + ' (match not found)</span>');
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
            output.push('<span class="matched" data-id="' + matches[0].record.id + '">' + matches[0].record.taxon + '</span>');
          }
        } else {
          // Name does not give a unique match
          output.push('<span class="unmatched" data-state="non-unique" data-batch="' + nonUniqueBatchIdx + '">' +
              rowInput + ' (unique match could not be found - click on the correct option below)</span>');
          $.each(matches, function () {
            output.push('<button type="button" class="non-unique-name" data-batch="' + nonUniqueBatchIdx +
              '" data-id="' + this.record.id + '">' + this.record.unambiguous + '</button>');
          });
          nonUniqueBatchIdx++;
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
    var token;
    var $el;
    // convert the list of input values by adding quotes, so they can go into a report query. Also create a version of
    // the list with search term simplification applied to ensure things like spacing and quotes are ignored.
    $.each(inputClean, function () {
      token = this.trim();
      if (token) {
        $el = $(token);
        if (!$el.length || $el[0].localName !== 'span') {
          listForDb.push("'" + token.toLowerCase() + "'");
          simplifiedListForDb.push(simplify(token));
        }
      }
    });
    // Check there is something to do
    if (listForDb.length) {
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
  }
  /*
  If click key inside a span.matched, remove the matched class -OK
  If return at last char of span.matched, put cursor after the span first so the span is not affected -CAN"T DO
  When matching against db, skip the matched spans
   */

  indiciaFns.on('keypress', '#scratchpad-input', {}, function () {
    // Find where the cursor is
    var el = getSelection().getRangeAt(0).commonAncestorContainer.parentNode;
    if (el.localName === 'span' && ($(el).hasClass('matched') || $(el).hasClass('ummatched'))) {
      $(el).removeClass('matched');
      $(el).removeClass('unmatched');
      $(el).removeAttr('data-id');
    }
  });

  indiciaFns.on('click', 'button.non-unique-name', {}, function (e) {
    var button = e.currentTarget;
    var speciesName = $(button).text();
    var nonUniqueBatchIdx = $(button).attr('data-batch');
    var elementsToRemove = $('[data-batch=' + nonUniqueBatchIdx + '],[data-batch=' + nonUniqueBatchIdx + '] + br');
    $(button).after('<span class="matched" data-id="' + $(button).attr('data-id') + '">' + speciesName + '</span><br/>');
    $.each(elementsToRemove, function () {
      $(this).remove();
    });
  });

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

  $('#scratchpad-cancel').click(function () {
    window.location = indiciaData.scratchpadSettings.returnPath;
  });
});
