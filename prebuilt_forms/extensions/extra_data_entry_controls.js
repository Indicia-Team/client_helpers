jQuery(document).ready(function ($) {
  indiciaFns.formatPersonAutocomplete = function (item, index, max, val, term, input) {
    var caption = item.person_name;
    if (item.email_address) {
      if ($(input).hasClass('show-email-domains')) {
        caption += ' (' + item.email_address.replace(/^.+@/, '') + ')';
      }
      if ($(input).hasClass('show-emails')) {
        caption += ' (' + item.email_address + ')';
      }
    }
    return caption;
  };
});
