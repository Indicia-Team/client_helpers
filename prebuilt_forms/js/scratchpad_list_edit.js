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
    // Remove stuff in square brackets - normally common names left from a previous scan result.
    inputDirty = inputDirty.replace(/\[[^\]]*\]/g, '');
    // Comma separated, line separated or table rows should both work. Convert all to a <br> element so we can split
    // each name into a separate token.
    inputDirty = inputDirty
      .replace(/,/g, '<br>')
      .replace(/<\/p>/g, '</p><br>')
      .replace(/<\/td>(\s)*<\/tr>/g, '<br></td></tr>');
    inputList = inputDirty.split(/<br\/?>/g);
    $.each(inputList, function () {
      var token;
      var $el;
      var tokenText;
      if (this) {
        token = this.trim();
        // simple way to strip HTML, even if unbalanced
        tokenText = token.replace(/<(.+?)>/g, '').trim();
        if (tokenText) {
          $el = $(token);
          if ($el.length && $el[0].localName === 'span' && $el.hasClass('matched')) {
            // matched strings are kept as they are and not changed
            inputClean.push(token);
          } else if ($el.length && $el[0].localName === 'a' && $el.hasClass('non-unique-name')) {
            // skip the matching option buttons for a list item that that could not match a unique name
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
    var matches;
    var output = [];
    var foundIds = [];
    var foundPreferredMatches = [];
    var nonUniqueBatchIdx = 0;
    var preferred;

    $('#scratchpad-remove-duplicates').hide();
    $('#scratchpad-save').attr('disabled', 'disabled');
    if (typeof data.error !== 'undefined') {
      alert(data.error);
    } else {
      // Loop through the input rows so we can check against the results from the db to see which are matched
      $.each(inputClean, function (idx, rowInput) {
        matches = [];
        // Stuff that's already matched can just go straight into the output
        if ($(rowInput).length && $(rowInput)[0].localName === 'span' && $(rowInput).hasClass('matched')) {
          foundIds.push($(rowInput).attr('data-id'));
          output.push(rowInput);
          return true; // to continue $.each
        }
        // Find if the current row in the input matches either the external key or term of a match in the db.
        $.each(data, function () {
          if (rowInput.toLowerCase() === this.external_key.toLowerCase()) {
            // there is a match against the input external key
            matches.push({ type: 'key', record: this });
          } else if (simplify(rowInput) === this.simplified) {
            // there is a match against an input name
            if (this.preferred === 't') {
              foundPreferredMatches.push(this.external_key + '|' + this.name);
            }
            // The next line skips non-preferred matches that look identical to their preferred names, e.g. taxa
            // with minor author variations
            if (this.preferred === 't' || $.inArray(this.external_key + '|' + this.name, foundPreferredMatches) === -1) {
              matches.push({ type: 'term', record: this });
            }
          }
        });
        if (matches.length === 0) {
          // Input item was not matched
          output.push('<span class="unmatched" data-state="unmatched">' + rowInput + ' (match not found)</span>');
        } else if (matches.length === 1 && $.inArray(matches[0].record.id, foundIds) > -1) {
          // Input matched but this is the 2nd instance of same matched name
          output.push('<span class="unmatched" data-state="duplicate">' + rowInput + ' (duplicate species)</span>');
          $('#scratchpad-remove-duplicates').show();
        } else if (matches.length === 1) {
          foundIds.push(matches[0].record.id);
          if (matches[0].type === 'key') {
            output.push('<span class="matched" data-id="' + matches[0].record.id + '">' +
              matches[0].record.external_key + ' (' + matches[0].record.name + ')' +
              '</span>');
          } else {
            output.push('<span class="matched" data-id="' + matches[0].record.id + '">' + matches[0].record.name + '</span>');
          }
        } else {
          // Name does not give a unique match
          output.push('<span class="unmatched" data-state="non-unique" data-batch="' + nonUniqueBatchIdx + '">' +
              rowInput + ' (unique match could not be found - click on the correct option below)</span>');
          $.each(matches, function () {
            preferred = typeof this.record.preferred !== 'undefined' && this.record.preferred === 't';
            output.push('<a contenteditable="false" class="non-unique-name' +
                (preferred ? ' preferred' : '') +
                '" data-batch="' + nonUniqueBatchIdx +
                '" data-id="' + this.record.id + '">' +
                this.record.unambiguous +
              '</a>');
          });
          nonUniqueBatchIdx++;
        }
        if (foundIds.length) {
          $('#scratchpad-save').removeAttr('disabled');
        }
        return true;
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
    var postParams = {};
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
      postParams.list = listForDb.join(',');
      postParams.simplified_list = simplifiedListForDb.join(',');
      $.extend(postParams, indiciaData.scratchpadSettings.filters);
      $.post(indiciaData.read.url + 'index.php/services/report/requestReport?' + $.param(reportParams),
        { params: JSON.stringify(postParams) },
        matchResponse,
        'jsonp'
      );
    }
  }

  indiciaFns.on('keypress', '#scratchpad-input', {}, function () {
    // Find where the cursor is
    var el = getSelection().getRangeAt(0).commonAncestorContainer.parentNode;
    if (el.localName === 'span' && ($(el).hasClass('matched') || $(el).hasClass('ummatched'))) {
      $(el).removeClass('matched');
      $(el).removeClass('unmatched');
      $(el).removeAttr('data-id');
    }
  });

  /**
   * Click on an option provided when a non-unique name found, selects that name and removes the other options.
   */
  indiciaFns.on('click', 'a.non-unique-name', {}, function (e) {
    var button = e.currentTarget;
    var speciesName = $(button).text();
    var nonUniqueBatchIdx = $(button).attr('data-batch');
    var elementsToRemove = $('[data-batch=' + nonUniqueBatchIdx + '],[data-batch=' + nonUniqueBatchIdx + '] + br');
    $(button).after('<span class="matched" data-id="' + $(button).attr('data-id') + '">' + speciesName + '</span><br/>');
    $.each(elementsToRemove, function () {
      $(this).remove();
    });
    $('#scratchpad-save').removeAttr('disabled');
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
