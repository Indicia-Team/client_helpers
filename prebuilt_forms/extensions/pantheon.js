var applyLexicon;

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

jQuery(document).ready(function ($) {
  var dirty = window.location.href.match(/\?q=/);
  var q = dirty ? '?q=' : '';
  var join;

  applyLexicon = function () {
    // apply the Lexicon
    $.each($('.lexicon span'), function () {
      if (typeof indiciaData.lexicon[$(this).html().replace(/&amp;/g, '&')] !== 'undefined') {
        $(this).attr('title', indiciaData.lexicon[$(this).html().replace(/&amp;/g, '&')]);
        $(this).addClass('lexicon-term');
      }
    });
  };

  // Fix up all pantheon links
  $.each($('.button-links a'), function () {
    join = ($(this).attr('href').match(/\?/) || q !== '') ? '&' : '?';
    $(this).attr('href', q + $(this).attr('href') + join + 'dynamic-sample_id=' + getParameterByName('dynamic-sample_id'));
  });

  applyLexicon();
});
