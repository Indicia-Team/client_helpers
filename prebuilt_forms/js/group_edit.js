jQuery(document).ready(function($) {

  $('#group\\:private_records').change(function() {
    if ($('#group\\:private_records').attr('checked')) {
      $('#release-warning').hide();
    } else {
      $('#release-warning').show();
    }
  });

  /**
   * Handler for add admin or member buttons to do email checking
   */
  var addMemberByEmail = function(field) {
    var searchedValue = $('#groups_user\\:' + field + '\\:search\\:person_name_unique').val();
    if (searchedValue.match(/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/)) {
      var urlSep = indiciaData.ajaxUrl.indexOf('?') === -1 ? '?' : '&';
      $.getJSON(
        indiciaData.ajaxUrl + '/lookup_email/' + indiciaData.nid + urlSep + 'email=' + searchedValue,
        null,
        function (data) {
          if (data.length===0) {
            alert('The email address you searched for was not found');
          } else if (data.length>1) {
            alert('Error - duplicate email address found');
          } else {
            var user = data[0];
            if ($('input[name="groups_user:' + field + '[]"][value="' + user.id + '"]').length > 0) {
              alert('The email address you searched for is already in the list')
            } else {
              // add the user found by the email address to the list
              $('#groups_user\\:' + field + '\\:sublist').append(
                '<li class="ui-widget-content ui-corner-all"><span class="ind-delete-icon">' +
                '&nbsp;</span>' + user.person_name + ' (' + user.email_address + ')' +
                '<input type="hidden" name="groups_user:' + field + '[]" value="' + user.id + '"></li>'
              );
              $('#groups_user\\:' + field + '\\:search\\:person_name_unique').val('');
            }
          }
        }
      );
    }
  }

  /**
   * Hook the email check handler to the member control add buttons.
   */
  $('#groups_user\\:user_id\\:add, #groups_user\\:admin_user_id\\:add').click(function handleAddClick() {
    var field = this.id.match(/admin_user_id/) ? 'admin_user_id' : 'user_id';
    addMemberByEmail(field);
  });

  /**
   * Hook the email check handler to the enter key on the search input box for members/admins
   */
  $('#groups_user\\:user_id\\:search\\:person_name_unique, #groups_user\\:admin_user_id\\:search\\:person_name_unique').keyup(function(e) {
    var field;
    if ((e.keyCode || e.which) == 13) {
      field = this.id.match(/admin_user_id/) ? 'admin_user_id' : 'user_id';
      addMemberByEmail(field);
    }
  });

  function checkViewSensitiveAllowed() {
    // Fully public groups can't allow sensitive data to be viewed. Also sensitive data viewing only available for groups
    // that expect all records to be posted via a group form.
    if ($('input[name=group\\:joining_method]:checked').val()==='P' ||
        $('input[name=group\\:implicit_record_inclusion]:checked').val()!=='f') {
      if ($('#group\\:view_full_precision').attr('checked')==='checked') {
        $('#group\\:view_full_precision').removeAttr('checked');
        alert('The show records at full precision setting has been unticked as it cannot be used with these settings.');
      }
      $('#group\\:view_full_precision').attr('disabled', true);
    }
    else {
      $('#group\\:view_full_precision').removeAttr('disabled');
    }
  }
  $('input[name=group\\:joining_method],input[name=group\\:implicit_record_inclusion]').change(checkViewSensitiveAllowed);
  checkViewSensitiveAllowed();

  // Check all checkbox functionality
  function updateCheckallBoxState() {
    var checkedCount = $('.parent-checkbox:checked').length,
        uncheckedCount = $('.parent-checkbox:not(:checked)').length;
    $('#ctrl-wrap-check-all-groups').css('opacity', 1);
    if (checkedCount > 0 && uncheckedCount === 0) {
      $('#check-all-groups').attr('checked', 'checked');
    } else if (checkedCount > 0 && uncheckedCount > 0) {
      // mixed
      $('#check-all-groups').attr('checked', 'checked');
      $('#ctrl-wrap-check-all-groups').css('opacity', 0.4);
    } else {
      $('#check-all-groups').removeAttr('checked');
    }
  }

  updateCheckallBoxState();

  $('.parent-checkbox').change(updateCheckallBoxState);

  $('#check-all-groups').change(function() {
    $('#ctrl-wrap-check-all-groups').css('opacity', 1);
    if (this.checked) {
      $('.parent-checkbox').attr('checked', 'checked');
    } else {
      $('.parent-checkbox').removeAttr('checked');
    }
  });
});