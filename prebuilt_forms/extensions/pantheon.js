jQuery(document).ready(function ($) {
  var dirty = window.location.href.match(/\?q=/);
  var q = dirty ? '?q=' : '';
  var join;

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


  function recurseNodes(nodes) {
    var html = '';
    $.each(nodes, function () {
      html += '<li><span>' + this.name + '</span>';
      if (this.children.length > 0) {
        html += '<ul>';
        html += recurseNodes(this.children);
        html += '</ul>';
      }
      html += '</li>';
    });
    return html;
  }

  // Fix up all pantheon links
  $.each($('.button-links a, .buttons-list a'), function () {
    join = ($(this).attr('href').match(/\?/) || q !== '') ? '&' : '?';
    $(this).attr('href', q + $(this).attr('href') + join + 'dynamic-sample_id=' + getParameterByName('dynamic-sample_id'));
  });

  indiciaFns.applyLexicon = function () {
    var termAlias;
    // apply the Lexicon
    $.each($('.lexicon span').not('.processed'), function () {
      if (typeof indiciaData.lexicon[$(this).html().replace(/&amp;/g, '&')] !== 'undefined') {
        $(this).attr('title', indiciaData.lexicon[$(this).html().replace(/&amp;/g, '&')]);
        $(this).addClass('lexicon-term');
        termAlias = $(this).html().replace(/&amp;/g, '&')
            .replace(/[^a-zA-Z0-9]+/g, '-')
            .toLowerCase();
        $(this).after('<a class="lexicon-info" href="/pantheon/lexicon/' + termAlias + '">i</a>');
        $(this).addClass('processed');
      }
    });
  };

  window.formatOsirisResources = function () {
    var flat;
    var nodes;
    var toplevelNodes;
    var lookupList;
    var n;
    var i;
    var html;
    $.each($('td.col-resource'), function () {
      flat = JSON.parse($(this).html());
      nodes = [];
      toplevelNodes = [];
      lookupList = {};

      for (i = 0; i < flat.length; i++) {
        n = {
          id: flat[i][0],
          name: flat[i][2],
          parent_id: ((flat[i][1] === 0) ? null : flat[i][1]),
          children: []
        };
        lookupList[n.id] = n;
        nodes.push(n);
        if (n.parent_id == null) {
          toplevelNodes.push(n);
        }
      }

      $.each(nodes, function () {
        if (!(this.parent_id == null)) {
          if (typeof lookupList[this.parent_id] === 'undefined') {
            lookupList[this.parent_id] = {
              id: this.parent_id,
              name: '<em>unknown</em>',
              parent_id: null,
              children: []
            };
            toplevelNodes.push(lookupList[this.parent_id]);
          }
          lookupList[this.parent_id].children = lookupList[this.parent_id].children.concat([this]);
        }
      });
      html = '<ul>';
      $.each(toplevelNodes, function () {
        html += '<li><span>' + this.name + '</span>';
        if (this.children.length > 0) {
          html += '<ul>';
          html += recurseNodes(this.children);
          html += '</ul>';
        }
        html += '</li>';
      });
      html += '</ul>';
      $(this).html(html);
    });
    if (typeof indiciaFns.applyLexicon !== 'undefined') {
      indiciaFns.applyLexicon();
    }
  };

  window.formatConservation = function () {
    $.each($('.conservation-status.unprocessed'), function () {
      var list = $(this).html().split('|');
      var countup = {};
      var output = [];
      $.each(list, function () {
        if (this.length) {
          if (typeof countup[this] === 'undefined') {
            countup[this] = 0;
          }
          countup[this]++;
        }
      });
      $.each(countup, function (status, count) {
        output.push(count + ' ' + status);
      });
      $(this).html(output.join('; '));
      $(this).removeClass('unprocessed');
    });
    if (typeof indiciaFns.applyLexicon !== 'undefined') {
      indiciaFns.applyLexicon();
    }
  };
});
