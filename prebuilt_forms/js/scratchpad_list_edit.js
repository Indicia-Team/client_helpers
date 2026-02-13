jQuery(document).ready(function ($) {
  'use strict';

  var inputClean = [];
  var totalCount = 0;

  var metadataProperties = (typeof indiciaData !== 'undefined' && indiciaData.scratchpadMetadataProperties) ? indiciaData.scratchpadMetadataProperties : [];

  function normaliseEntryMetadata(raw) {
    var map = {};
    if (!raw) {
      return map;
    }
    // Prefer list-of-objects format: [{entry_id: 123, metadata: {...}}, ...]
    if ($.isArray(raw)) {
      $.each(raw, function () {
        if (this && typeof this.entry_id !== 'undefined') {
          map[String(this.entry_id)] = (this.metadata && typeof this.metadata === 'object') ? this.metadata : {};
        }
      });
      return map;
    }
    // Fallback to object/map format.
    if (typeof raw === 'object') {
      return raw;
    }
    return map;
  }

  var entryMetadataById = normaliseEntryMetadata((typeof indiciaData !== 'undefined') ? indiciaData.scratchpadEntryMetadata : null);

  function hasMetadataProperties() {
    return $.isArray(metadataProperties) && metadataProperties.length > 0;
  }

  function safeId(id) {
    return String(id || '').replace(/[^a-zA-Z0-9_\-:]/g, '_');
  }

  function getMatchedEntries() {
    var entries = [];
    $.each($('span.matched').not(':empty'), function () {
      var $span = $(this);
      var entryId = $span.attr('data-id');
      if (entryId) {
        entries.push({
          id: String(entryId),
          label: $.trim($span.text())
        });
      }
    });
    return entries;
  }

  function coerceValue(datatype, raw) {
    var val = raw;
    if (val === null || typeof val === 'undefined') {
      return null;
    }
    if (typeof val === 'string') {
      val = $.trim(val);
    }
    if (val === '') {
      return null;
    }
    if (datatype === 'integer') {
      if (!String(val).match(/^-?\d+$/)) {
        return null;
      }
      return parseInt(val, 10);
    }
    if (datatype === 'float') {
      var f = parseFloat(val);
      return isNaN(f) ? null : f;
    }
    // text / lookup
    return String(val);
  }

  function ensureEntryMetadataObject(entryId) {
    if (!entryMetadataById || typeof entryMetadataById !== 'object') {
      entryMetadataById = {};
    }
    if (!entryMetadataById[entryId] || typeof entryMetadataById[entryId] !== 'object') {
      entryMetadataById[entryId] = {};
    }
    return entryMetadataById[entryId];
  }

  function buildMetadataEditor() {
    var $container = $('#scratchpad-entry-metadata');
    if (!hasMetadataProperties() || !$container.length) {
      if ($container.length) {
        $container.hide().empty();
      }
      return;
    }

    var matched = getMatchedEntries();
    if (matched.length === 0) {
      $container.hide().empty();
      return;
    }

    var html = [];
    html.push('<fieldset class="scratchpad-entry-metadata-fieldset">');
    html.push('<legend>Entry metadata</legend>');
    html.push('<div class="scratchpad-entry-metadata-help">Enter additional information for each matched entry.</div>');
    html.push('<div class="scratchpad-entry-metadata-tablewrap">');
    html.push('<table class="scratchpad-entry-metadata-table table"><thead><tr>');
    html.push('<th>Entry</th>');
    $.each(metadataProperties, function (i, prop) {
      html.push('<th>' + $('<div/>').text(prop.caption || prop.name).html() + '</th>');
    });
    html.push('</tr></thead><tbody>');

    $.each(matched, function (idx, row) {
      var entryId = row.id;
      var entryLabel = row.label || entryId;
      var current = ensureEntryMetadataObject(entryId);
      html.push('<tr data-entry-id="' + $('<div/>').text(entryId).html() + '">');
      html.push('<td class="entry-label">' + $('<div/>').text(entryLabel).html() + '</td>');
      $.each(metadataProperties, function (i, prop) {
        var propName = prop.name;
        var datatype = prop.datatype || 'text';
        var currentVal = (current && typeof current[propName] !== 'undefined' && current[propName] !== null) ? current[propName] : '';
        var controlId = 'spmd-' + safeId(entryId) + '-' + safeId(propName);
        html.push('<td>');
        if (datatype === 'lookup') {
          html.push('<select id="' + controlId + '" class="scratchpad-entry-meta" data-entry-id="' + $('<div/>').text(entryId).html() + '" data-prop-name="' + $('<div/>').text(propName).html() + '" data-datatype="lookup">');
          html.push('<option value=""></option>');
          var options = prop.lookupValues || [];
          $.each(options, function (j, opt) {
            var selected = (String(currentVal) === String(opt)) ? ' selected="selected"' : '';
            html.push('<option value="' + $('<div/>').text(opt).html() + '"' + selected + '>' + $('<div/>').text(opt).html() + '</option>');
          });
          html.push('</select>');
        } else {
          var type = 'text';
          var step = '';
          if (datatype === 'integer') {
            type = 'number';
            step = ' step="1"';
          } else if (datatype === 'float') {
            type = 'number';
            step = ' step="any"';
          }
          html.push('<input id="' + controlId + '" class="scratchpad-entry-meta" type="' + type + '"' + step +
            ' data-entry-id="' + $('<div/>').text(entryId).html() + '" data-prop-name="' + $('<div/>').text(propName).html() + '" data-datatype="' + $('<div/>').text(datatype).html() + '" value="' + $('<div/>').text(String(currentVal)).html() + '">');
        }
        html.push('</td>');
      });
      html.push('</tr>');
    });

    html.push('</tbody></table></div></fieldset>');
    $container.html(html.join('')).show();
  }

  indiciaFns.on('change input', '.scratchpad-entry-meta', {}, function (e) {
    var $ctrl = $(e.currentTarget);
    var entryId = String($ctrl.attr('data-entry-id') || '');
    var propName = String($ctrl.attr('data-prop-name') || '');
    var datatype = String($ctrl.attr('data-datatype') || 'text');
    if (!entryId || !propName) {
      return;
    }
    var current = ensureEntryMetadataObject(entryId);
    var coerced = coerceValue(datatype, $ctrl.val());
    if (coerced === null) {
      delete current[propName];
    } else {
      current[propName] = coerced;
    }
  });

  function buildEntriesMetadataJson() {
    var out = {};
    var matched = getMatchedEntries();
    $.each(matched, function (idx, row) {
      var entryId = row.id;
      var current = entryMetadataById && entryMetadataById[entryId] ? entryMetadataById[entryId] : null;
      if (current && typeof current === 'object') {
        var cleaned = {};
        $.each(metadataProperties, function (i, prop) {
          var propName = prop.name;
          if (typeof current[propName] !== 'undefined' && current[propName] !== null && String(current[propName]) !== '') {
            cleaned[propName] = current[propName];
          }
        });
        if (Object.keys(cleaned).length) {
          out[entryId] = cleaned;
        }
      }
    });
    return out;
  }

  /**
   * Simplifies an input string for matching.
   *
   * @param {string} text
   * @returns {string}
   */
  function simplify(text) {
    return text.toLowerCase().replace(/\(.+\)/g, '')
      .replace(/ae/g, 'e')
      .replace(/[^a-zA-Z0-9\+\?*]/g, '');
  }

  /**
   * Recalculates and updates the on-screen scratchpad matching statistics.
   */
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

  /**
   * Normalises the scratchpad editor contents into a clean list of tokens,
   * preserving already-matched spans.
   */
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

  /**
   * Removes any subgenus component from a taxon name.
   *
   * @param {string} name
   *   Taxoon name, possibly including a subgenus in parentheses.
   *
   * @returns {string}
   */
  function removeSubgenus(name) {
    return name.replace(/ \(.+\)/, '');
  }

  /**
   * Determines if the form is editing an existing scratchpad list.
   *
   * @returns {boolean}
   */
  function isEditMode() {
    var idCtrl = $('[name="scratchpad_list:id"]');
    return idCtrl.length > 0 && $.trim(idCtrl.val()) !== '';
  }

  /**
   * Recursively checks a DOM node (from #scratchpad-input) for any unchecked
   * free text. Matched/unmatched spans created by the checker are ignored.
   *
   * @param {Node} node
   * @returns {boolean}
   */
  function nodeContainsUncheckedText(node) {
    var $node;
    var tag;
    var hasUnchecked = false;

    if (!node) {
      return false;
    }
    // Text node.
    if (node.nodeType === 3) {
      return $.trim(node.nodeValue) !== '';
    }
    // Element nodes only.
    if (node.nodeType !== 1) {
      return false;
    }
    $node = $(node);
    tag = node.nodeName.toLowerCase();
    if (tag === 'br') {
      return false;
    }
    if (tag === 'a' && $node.hasClass('non-unique-name')) {
      // Non-unique options are created by the check, so are not unchecked text.
      return false;
    }
    if (tag === 'span') {
      // Checked content is wrapped in matched/unmatched spans.
      if ($node.hasClass('matched') || $node.hasClass('unmatched')) {
        return false;
      }
      return $.trim($node.text()) !== '';
    }
    // Any other element: recurse.
    $node.contents().each(function () {
      if (nodeContainsUncheckedText(this)) {
        hasUnchecked = true;
        return false;
      }
      return true;
    });
    return hasUnchecked;
  }

  /**
   * Returns true if the scratchpad editor contains any unchecked free text.
   *
   * @returns {boolean}
   */
  function scratchpadHasUncheckedText() {
    var unchecked = false;
    $('#scratchpad-input').contents().each(function () {
      if (nodeContainsUncheckedText(this)) {
        unchecked = true;
        return false;
      }
      return true;
    });
    return unchecked;
  }

  /**
   * Updates the Save button enabled/disabled state based on whether the
   * scratchpad contents have unchecked text.
   */
  function updateSaveButtonState() {
    var unchecked = scratchpadHasUncheckedText();
    var matchedCount = $('span.matched').not(':empty').length;
    // Allow saving an existing list without forcing a re-check, unless there
    // is unchecked text in the editor.
    if (unchecked) {
      $('#scratchpad-save').attr('disabled', 'disabled');
    } else if (isEditMode() || matchedCount > 0) {
      $('#scratchpad-save').removeAttr('disabled');
    } else {
      $('#scratchpad-save').attr('disabled', 'disabled');
    }
  }

  /**
   * Handles the response from the warehouse for a request to match the list of species provided against the species
   * listed in the database.
   *
   * @param {*} data
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
      updateSaveButtonState();
      buildMetadataEditor();
    }
    $('#scratchpad-check').removeClass('checking');
  }

  /**
   * Posts the current cleaned scratchpad token list to the warehouse to
   * attempt matching against the database.
   */
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
        'json'
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
    updateSaveButtonState();
  });

  // Any edits to the contenteditable scratchpad should update the save state.
  $('#scratchpad-input').on('input paste keyup', function () {
    updateSaveButtonState();
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
    updateSaveButtonState();
    buildMetadataEditor();
  });

  $('#scratchpad-check').on('click', function () {
    $('#scratchpad-check').addClass('checking');
    tidyInput();
    matchToDb();
  });

  $('#scratchpad-remove-duplicates').on('click', function () {
    totalCount -= $('[data-state="duplicate"]').length;
    $('[data-state="duplicate"]').next('br').remove();
    $('[data-state="duplicate"]').remove();
    recalculateStats();
    updateSaveButtonState();
    buildMetadataEditor();
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

    if (hasMetadataProperties()) {
      try {
        $('#hidden-entries-metadata').val(JSON.stringify(buildEntriesMetadataJson()));
      } catch (ex) {
        // If serialization fails for any reason, still allow the list to be saved.
        $('#hidden-entries-metadata').val('{}');
      }
    } else {
      $('#hidden-entries-metadata').val('{}');
    }
  });

  $('#scratchpad-cancel').on('click', function () {
    window.location = indiciaData.scratchpadSettings.returnPath;
  });

  recalculateStats();
  updateSaveButtonState();
  buildMetadataEditor();
});
