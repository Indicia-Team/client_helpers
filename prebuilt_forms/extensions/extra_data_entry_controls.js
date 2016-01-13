var format_person_autocomplete;

jQuery(document).ready(function($) {
  format_person_autocomplete = function(item) {
    return item.person_name + ' (' + item.email_address.replace(/^.+@/, '') + ')';
  }
});