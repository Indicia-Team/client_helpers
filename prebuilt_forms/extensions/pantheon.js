(function ($) {

  window.formatConservation = function () {
    $.each($('.conservation-status.unprocessed'), function () {
      var list = $(this).html().split('|');
      var countup = {};
      var output = [];
      var display;
      var countLink;
      $.each(list, function () {
        if (this.length) {
          if (typeof countup[this] === 'undefined') {
            countup[this] = 0;
          }
          countup[this]++;
        }
      });
      $.each(countup, function (status, count) {
        output.push(count + ' <span>' + status + '</span>');
      });
      display = output.join('; ');
      countLink = $(this).closest('tr').find('td.col-count a');
      if (countLink.length) {
        display = '<a href="' + countLink.attr('href') + '&dynamic-has_any_designation=t">' + display + '</a>';
      }
      $(this).html(display);
      $(this).removeClass('unprocessed');
    });
    if (typeof indiciaFns.applyLexicon !== 'undefined') {
      indiciaFns.applyLexicon();
    }
    formatSqiWarning();
  };

  window.formatOsirisResources = function () {
    $.each($('td.col-resource'), function () {
      if ($(this).html().trim === '' || $(this).html().substr(0, 1) !== '[') {
        return;
      }
      var flat = JSON.parse($(this).html());
      var n;
      var i;
      var lookupListById = {};
      var allParentIds = [];
      var leafList = [];
      var html = '<ul>';

      // Convert the list of nodes in the report output to lists we can easily lookup against
      for (i = 0; i < flat.length; i++) {
        n = {
          id: flat[i][0],
          name: flat[i][2],
          parent_id: (flat[i][1] === 0) ? null : flat[i][1],
          children: []
        };
        lookupListById[n.id] = n;
        // Need a list of all parents so we can easily detect leaf nodes
        if (n.parent_id !== null) {
          allParentIds.push(n.parent_id);
        }
      }
      // Find the leaf nodes
      $.each(lookupListById, function () {
        if ($.inArray(this.id, allParentIds) === -1) {
          leafList.push(this);
        }
      });
      // output each leaf and its chain of parents
      $.each(leafList, function () {
        html += '<li>';
        html += recurseNodes(this, lookupListById);
        html += '<span>' + this.name + '</span>';
        html += '</li>';
      });
      html += '</ul>';
      $(this).html(html);
    });
    if (typeof indiciaFns.applyLexicon !== 'undefined') {
      indiciaFns.applyLexicon();
    }
  };

}(jQuery));

jQuery(document).ready(function ($) {
  var typeParam;

  // retrieve a query string parameter
  function getParameterByName(name) {
    var regex;
    var results;
    var searchname = name;
    // special case - dynamic-sample_id can be provided as params table=sample&id=...
    if (name === 'dynamic-sample_id' && location.search.match(/table=sample/)) {
      searchname = 'id';
    }
    searchname = searchname.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    regex = new RegExp('[\\?&]' + searchname + '=([^&#]*)');
    results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
  }

  typeParam = getParameterByName('dynamic-sample_type');

  // Fix up all pantheon links
  $.each($('.button-links a, .buttons-list a'), function () {
    // grab the URL, excluding the query parameters, but including the ?q= if dirty URLs under D7.
    var join = ($(this).attr('href').match(/\?/)) ? '&' : '?';
    var parts = $(this).attr('href').split(join);
    var href = parts[0] + join + 'dynamic-sample_id=' + getParameterByName('dynamic-sample_id');
    if (typeParam) {
      href += '&dynamic-sample_type=' + typeParam;
    }
    $(this).attr('href', href);
  });

  // Trim function for dashes.
  function dashTrim(str) {
    let start = 0;
    let end = str.length;
    while (start < end && str[start] === '-') {
      ++start;
    }
    while (end > start && str[end - 1] === '-') {
      --end;
    }
    return (start > 0 || end < str.length) ? str.substring(start, end) : str;
  }

  indiciaFns.applyLexicon = function () {
    var termAlias;
    // apply the Lexicon
    $.each($('.lexicon span, .lexicon th a').not('.processed'), function () {
      var summary;
      if (typeof indiciaData.lexicon[$(this).html().replace(/&amp;/g, '&')] !== 'undefined') {
        summary = indiciaData.lexicon[$(this).html().replace(/&amp;/g, '&')];
        $(this).attr('title', summary);
        $(this).addClass('lexicon-term');
        termAlias = dashTrim($(this).html().replace(/&amp;/g, '&')
          .replace(/[^a-zA-Z0-9]+/g, '-')
          .toLowerCase());
        $(this).after('<a class="lexicon-info skip-text" target="_blank" title="' + summary + '" ' +
            'href="/lexicon/' + termAlias + '">i</a>');
        $(this).addClass('processed');
      }
    });
    // Forces sorting click handlers added to grid column th to not override the link on lexicon items
    $('.lexicon th a.lexicon-info').click(function () {
      window.location = $(this).attr('href');
    });
  };

  // So that it can be called from report grid callback.
  window.applyLexicon = indiciaFns.applyLexicon;

  function recurseNodes(node, lookupListById) {
    var html = '';
    var parent = '';
    if (node.parent_id && typeof lookupListById[node.parent_id] !== 'undefined') {
      parent = lookupListById[node.parent_id];
      html = recurseNodes(parent, lookupListById);
      html += '<span>' + parent.name + '</span> &gt;&gt; ';
    }
    return html;
  }



  function formatSqiWarning() {
    $.each($('tbody .col-sqi').not('.processed,:empty'), function () {
      if ($(this).closest('tr').find('.col-count').text() < 15) {
        $(this).prepend(
          '<img title="Warning, this index was calculated from less than 15 species so may not be reliable." ' +
          'alt="Warning icon" src="/modules/custom/iform/media/images/warning.png"/>'
        );
      }
      $(this).addClass('processed');
    });
  }

  /**
   * Retrieve an array of lists in the current Quick Analysis Group.
   */
  function listsInCurrentGroup() {
    var lists = [];
    $.each($('#quick-analysis-group tbody tr'), function addRow() {
      lists.push(this.id.replace(/^row/, ''));
    });
    return lists;
  }

  indiciaFns.addToQuickAnalysisGroup = function (id) {
    var row;
    var display;
    var description;
    if ($('#quick-analysis-group tr#row' + id).length > 0) {
      alert('This list is already in the current Quick Analysis Group.');
    } else {
      display = '<strong>' + $('#my-lists tr#row' + id + ' td.col-title').text() + '</strong>';
      description = $('#my-lists tr#row' + id + ' td.col-description').text();
      if (description) {
        display += '<br/>' + description;
      }
      row = '<tr id="row' + id + '">' +
        '<td>' + display + '</td>' +
        '<td><img alt="Remove" title="Remove this list from the group" src="' + indiciaData.imagesPath + '/nuvola/cancel-16px.png" onclick="indiciaFns.removeFromQuickAnalysisGroup(' + id + ');" /></td>' +
        '</tr>';
      $('#quick-analysis-group tbody').append(row);
      // Animate the tab to show where it has gone.
      $('#tab-subsamplegrouping-tab span').animate({
        'background-color': 'yellow'
      }, 200, function resetBackground() {
        $('#tab-subsamplegrouping-tab span').animate({
          'background-color': 'transparent'
        }, 1000);
      });
      alert('The list has been added to the sub-sample grouping tab.');
      $('#qa-group-actions button,#qa-group-actions input').removeAttr('disabled');
    }
  };

  indiciaFns.removeFromQuickAnalysisGroup = function (id) {
    $('#quick-analysis-group tbody tr#row' + id).remove();
    if ($('#quick-analysis-group tbody tr').length === 0) {
      $('#qa-group-actions button,#qa-group-actions input').attr('disabled', 'disabled');
    }
  };

  indiciaFns.clearQuickAnalysisGroup = function () {
    $('#quick-analysis-group tbody tr').remove();
    $('#qa-group-actions button,#qa-group-actions input').attr('disabled', 'disabled');
  };

  indiciaFns.analyseQuickAnalysisGroup = function (path, type) {
    var lists = listsInCurrentGroup();
    window.location = path + '?dynamic-sample_type=' + type + '&dynamic-sample_id=' + lists.join(',');
  };

  indiciaFns.saveQuickAnalysisScratchpadGroup = function () {
    var lists;
    var params;
    if ($('#new-list-name').val().trim() === '') {
      alert('Please specify the name of the list to save the group as.');
      return;
    }
    lists = listsInCurrentGroup();
    params = [
      encodeURIComponent($('#new-list-name').val().trim()),
      indiciaData.user_id,
      encodeURIComponent('{' + lists.join(',') + '}')
    ];
    $.ajax({
      url: indiciaData.warehouseUrl + 'index.php/services/data_utils/combine_scratchpad_lists/' +
        params.join('/') + '?nonce=' + indiciaData.write.nonce + '&auth_token=' + indiciaData.write.auth_token,
      dataType: 'jsonp',
      success: function (response) {
        alert('The combined list has been saved');
      }
    });
  };

  /**
   * Tables can request additional pager in header with a class.
   */
  $.each($('table.double-pager'), function() {
    let cloned = $(this).find('tfoot tr').clone();
    cloned.addClass('ui-widget-content')
    cloned.appendTo($(this).find('thead'))
    cloned.find('div.pager').show();
  });
});
