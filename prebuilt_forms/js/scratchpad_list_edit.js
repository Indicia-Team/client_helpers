jQuery(document).ready(function ($) {
  'use strict';

  var inputClean = [];
  var totalCount = 0;

  function simplify(text) {
    return text.toLowerCase().replace(/\(.+\)/g, '')
      .replace(/ae/g, 'e')
      .replace(/[^a-zA-Z0-9\+\?*]/g, '');
  }

  function recalculateStats() {
    var matched = $('span.matched')
        .not(':empty').length;
    var queried = $('span.unmatched[data-state="non-unique"]')
        .not(':empty').length;
    var unmatched = $('span.unmatched[data-state="unmatched"],span.unmatched[data-state="duplicate"]')
        .not(':empty').length;

    // Cleanup code should remove empty spans? Or should above filter out :empty
    $('#scratchpad-stats-total span.stat').html(totalCount);
    $('#scratchpad-stats-matched span.stat').html(matched);
    $('#scratchpad-stats-queried span.stat').html(queried);
    $('#scratchpad-stats-unmatched span.stat').html(unmatched);
    $('#scratchpad-stats').show();
    if (totalCount === matched) {
      $('#scratchpad-stats-total span.all-done').show();
    } else {
      $('#scratchpad-stats-total span.all-done').hide();
    }
  }

  function tidyInput() {
    var input;
    var inputDirty;
    var inputList;
    // Tidy up and remove the options available for non-unique name resolution.
    $('#scratchpad-input a.non-unique-name,#scratchpad-input span:empty').remove();
    input = $('#scratchpad-input').html();
    inputClean = [];
    // HTML and duplicate whitespace clean
    inputDirty = input.replace(/&nbsp;/g, ' ');
    inputDirty = inputDirty.replace(/ +/g, ' ');
    // Remove stuff in parenthesis - left from a previous scan result, or subgenera, both are not needed.
    inputDirty = inputDirty.replace(/\([^\)]*\)/g, '');
    // Remove stuff in square brackets - normally common names left from a previous scan result.
    inputDirty = inputDirty.replace(/\[[^\]]*\]/g, '');
    // Comma separated, line separated or table rows should both work. Convert all to a <br> element so we can split
    // each name into a separate token.
    inputDirty = inputDirty
      .replace(/(,)(?!([^<]+?)>)/g, '<br>')
      .replace(/<(\/)?font[^>]*>/g, '')
      .replace(/<!--([\s\S]+?)-->/g, '')
      .replace(/<\/p>/g, '</p><br>')
      .replace(/<\/div>/g, '</div><br>')
      .replace(/<div>/g, '<br><div>')
      .replace(/<\/td>(\s)*<\/tr>/g, '<br></td></tr>');
    inputList = inputDirty.split(/<br\/?>/g);
    $.each(inputList, function () {
      var token;
      var $el;
      var tokenText;
      if (this) {
        token = this.trim();
        // simple way to strip HTML, even if unbalanced
        tokenText = token.replace(/<([\s\S]+?)>/g, '').trim();
        if (tokenText) {
          try {
            $el = $(token);
          } catch (e) {
            $el = null;
          }
          if ($el && $el.length && $el[0].localName === 'span' && $el.hasClass('matched')) {
            // matched strings are kept as they are and not changed
            inputClean.push(token);
          } else {
            inputClean.push(tokenText);
          }
        }
      }
    });
    totalCount = inputClean.length;
    $('#scratchpad-input').html(inputClean.join('<br/>'));
  }

  function removeSubgenus(name) {
    return name.replace(/ \(.+\)/, '');
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
    var disallowDataEntry;
    var $el;

    $('#scratchpad-remove-duplicates').hide();
    $('#scratchpad-save').attr('disabled', 'disabled');
    if (typeof data.error !== 'undefined') {
      alert(data.error);
    } else if (typeof data.parameterRequest !== 'undefined') {
      alert('The check failed, possibly due to non-standard hidden characters in the pasted text.');
    } else {
      // Loop through the input rows so we can check against the results from the db to see which are matched
      $.each(inputClean, function (idx, rowInput) {
        matches = [];
        try {
          $el = $(rowInput);
        } catch (e) {
          $el = null;
        }
        // Stuff that's already matched can just go straight into the output
        if ($el && $el.length && $(rowInput)[0].localName === 'span' && $(rowInput).hasClass('matched')) {
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
              foundPreferredMatches.push(this.external_key + '|' + removeSubgenus(this.name));
            }
            // The next line skips non-preferred matches that look identical to their preferred names, e.g. taxa
            // with minor author variations
            if (this.preferred === 't' ||
                $.inArray(this.external_key + '|' + removeSubgenus(this.name), foundPreferredMatches) === -1) {
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
            disallowDataEntry = typeof this.record.allow_data_entry !== 'undefined' && this.record.allow_data_entry === 'f';
            preferred = typeof this.record.preferred !== 'undefined' && this.record.preferred === 't' && !disallowDataEntry;
            output.push('<a contenteditable="false" class="non-unique-name' +
                (preferred ? ' preferred' : '') +
                (disallowDataEntry ? ' disallow-data-entry' : '') +
                '" data-batch="' + nonUniqueBatchIdx +
                '" data-id="' + this.record.id + '" data-name="' + this.record.name + '">' +
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
      recalculateStats();
      $('#scratchpad-stats')[0].scrollIntoView();
    }
    $('#scratchpad-check').removeClass('checking');
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
    var sep;
    // convert the list of input values by adding quotes, so they can go into a report query. Also create a version of
    // the list with search term simplification applied to ensure things like spacing and quotes are ignored.
    $.each(inputClean, function () {
      token = this.trim();
      if (token) {
        try {
          $el = $(token);
        } catch (e) {
          $el = null;
        }
        if (!$el || !$el.length || $el[0].localName !== 'span') {
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
      sep = indiciaData.ajaxUrl.match(/\?/) ? '&' : '?';
      $.post(indiciaData.ajaxUrl + '/check/' + indiciaData.nid + sep + $.param(reportParams),
        { params: JSON.stringify(postParams) },
        matchResponse,
        'jsonp'
      );
    } else {
      $('#scratchpad-check').removeClass('checking');
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
    var speciesName = $(button).attr('data-name');
    var nonUniqueBatchIdx = $(button).attr('data-batch');
    var elementsToRemove = $('[data-batch=' + nonUniqueBatchIdx + '],[data-batch=' + nonUniqueBatchIdx + '] + br');
    if ($('span[data-id="' + $(button).attr('data-id') + '"]').length) {
      $(button).after('<span class="unmatched" data-state="duplicate">' + speciesName + ' (duplicate species)</span><br/>');
      $('#scratchpad-remove-duplicates').show();
    } else {
      $(button).after('<span class="matched" data-id="' + $(button).attr('data-id') + '">' + speciesName + '</span><br/>');
    }
    $.each(elementsToRemove, function () {
      $(this).remove();
    });
    $('#scratchpad-save').removeAttr('disabled');
    recalculateStats();
  });

  $('#scratchpad-check').click(function () {
    $('#scratchpad-check').addClass('checking');
    tidyInput();
    matchToDb();
  });

  $('#scratchpad-remove-duplicates').click(function () {
    totalCount -= $('[data-state="duplicate"]').length;
    $('[data-state="duplicate"]').next('br').remove();
    $('[data-state="duplicate"]').remove();
    recalculateStats();
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

  recalculateStats();
});
