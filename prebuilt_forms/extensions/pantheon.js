// retrieve a query string parameter
function getParameterByName(name) {
  // special case - dynamic-sample_id can be provided as params table=sample&id=...
  if (name==='dynamic-sample_id' && location.search.match(/table=sample/)) {
    name='id';
  }
  name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
  var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
    results = regex.exec(location.search);
  return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}

jQuery(document).ready(function($) {
  var dirty=window.location.href.match(/\?q=/),
    q=dirty ? '?q=' : '', join;
  // Fix up all pantheon links
  $.each($('.button-links a'), function() {
    join = ($(this).attr('href').match(/\?/) || q!=='') ? '&' : '?';
    $(this).attr('href', q + $(this).attr('href') + join + 'dynamic-sample_id=' + getParameterByName('dynamic-sample_id'));
  });
});