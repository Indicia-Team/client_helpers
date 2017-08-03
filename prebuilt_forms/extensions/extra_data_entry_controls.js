jQuery(document).ready(function () {
  indiciaFns.formatPersonAutocomplete = function (item) {
    if (item.email_address === null || item.email_address === '') {
      return item.person_name;
    }
    return item.person_name + ' (' + item.email_address.replace(/^.+@/, '') + ')';
  };
});
