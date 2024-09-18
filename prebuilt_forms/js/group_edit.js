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

  /**
   * Retrieve options that can't be selected when in reports only mode.
   *
   * For a container group.
   *
   * @returns array
   *   Options elements that are not for report pages.
   */
  function getUnselectableNonReportOptions() {
    const selectablePageSelectors = [];
    $.each(indiciaData.reportingPages, function(path, title) {
      selectablePageSelectors.push('[value="' + path + '\\:' + title.replace(':', '\\:') + '"]')
    });
    return $('#complex-attr-grid-group-pages tbody tr td:first-child option').not('[value=""],' + selectablePageSelectors.join(','));
  }

  /**
   * Switch UI to container group mode.
   */
  function convertToContainerGroup() {
    const joinRadios = $('[name="group:joining_method"]');
    const unselectablePageOptions = getUnselectableNonReportOptions();
    // Container project, so admins manage membership.
    joinRadios.not('[value="A"]').closest('li').slideUp();
    joinRadios.not('[value="A"]').prop('checked', false);
    joinRadios.filter('[value="A"]').prop('checked', true);
    $('#ctrl-wrap-groups_user-user_id').slideUp();
    $('#ctrl-wrap-groups_user-user_id li').remove();
    $.each(unselectablePageOptions.filter(':selected'), function() {
      $(this).closest('tr').find('.action-delete').click();
    });
    unselectablePageOptions.prop('disabled', true);
  }

  /**
   * Switch UI to normal (non-container) group mode.
   */
  function convertToNormalGroup() {
    // Not container project, revert to default options.
    $('[name="group:joining_method"]').not('[value="A"]').closest('li').slideDown();
    $('#ctrl-wrap-groups_user-user_id').slideDown();
    const unselectablePageOptions = getUnselectableNonReportOptions();
    $.each(unselectablePageOptions.filter(':selected'), function() {
      var row = $(this).closest('tr');
      $(row).css('opacity', 1);
      $(row).removeClass('row-deleted');
      $(row).find(':input:visible').css('text-decoration', 'none');
      $(row).find('.delete-flag').val('f');
      $(row).find(':input:visible').attr('disabled', false);
    });
  }

  /**
   * Container activities disallow certain form options.
   */
  $('#group\\:container').change(function() {
    const joinRadios = $('[name="group:joining_method"]');
    if ($('#group\\:container').is(':checked')) {
      let warnings = [];
      const unselectablePageOptions = getUnselectableNonReportOptions();
      // If existing non-admin members, need to warn they will be removed.
      if ($('#ctrl-wrap-groups_user-user_id li').length > 0) {
        warnings.push(indiciaData.lang.group_edit.warnMembersRemoved);
      }
      // If existing non-reporting linked pages, need to warn they will be
      // removed.
      if (unselectablePageOptions.filter(':selected').closest('select').filter(':enabled').length > 0) {
        warnings.push(indiciaData.lang.group_edit.warnNonReportPagesRemoved);
      }
      // Check that not existing group, or members list empty, otherwise warn
      // and confirm.
      if (warnings.length > 0) {
        $.fancyDialog({
          title: indiciaData.lang.group_edit.convertToContainer.replace('{1}', indiciaData.groupTypeLabel),
          message: indiciaData.lang.group_edit.areYouSureConvertToContainer.replace('{1}', indiciaData.groupTypeLabel) +
            '<ul><li>' + warnings.join('</li><li>') + '</li></ul>',
          callbackOk: convertToContainerGroup,
          callbackCancel: function() {
            $('#group\\:container').prop('checked', false);
          }
        });
      } else {
        convertToContainerGroup();
      }
    } else {
      convertToNormalGroup();
    }
  });

  if ($('#group\\:container').is(':checked')) {
    convertToContainerGroup();
  }

});