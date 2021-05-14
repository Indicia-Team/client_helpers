var approveMember,
  removeMember,
  toggleRole,
  changeAccessLevel;

(function ($) {
  'use strict';
  var dialog;
  var form;
  approveMember = function (id) {
    var data = {
      website_id: indiciaData.website_id,
      'groups_user:id': id,
      'groups_user:pending': 'f'
    };
    $.post(
      indiciaData.ajaxFormPostUrl,
      data,
      function (response) {
        if (typeof response.error === 'undefined') {
          alert('Member approved');
          indiciaData.reports.report_output.grid_report_output.reload();
        } else {
          alert(response.error);
        }
      },
      'json'
    );
  };

  removeMember = function (id, name) {
    var data;
    if (confirm('Do you really want to remove "' + name + '" from the group?')) {
      data = {
        website_id: indiciaData.website_id,
        'groups_user:id': id,
        'groups_user:deleted': 't'
      };
      $.post(
        indiciaData.ajaxFormPostUrl,
        data,
        function (response) {
          if (typeof response.error === 'undefined') {
            alert('Member removed');
            indiciaData.reports.report_output.grid_report_output.reload();
          } else {
            alert(response.error);
          }
        },
        'json'
      );
    }
  };

  toggleRole = function (id, name, makeRole) {
    var data;
    var setAdministrator;
    if (makeRole === 'administrator') {
      setAdministrator = true;
    } else {
      setAdministrator = false;
    }
    data = {
      website_id: indiciaData.website_id,
      'groups_user:id': id,
      'groups_user:administrator': setAdministrator
    };
    $.post(
      indiciaData.ajaxFormPostUrl,
      data,
      function (response) {
        if (typeof response.error === 'undefined') {
          indiciaData.reports.report_output.grid_report_output.reload();
        } else {
          alert(response.error);
        }
      },
      'json'
    );
  };

  function updateAccessLevel() {
    var data = {
      website_id: indiciaData.website_id,
      'groups_user:id': $('#updated_access_level_user_id').val(),
      'groups_user:access_level': $('#updated_access_level').val()
    };
    $.post(
      indiciaData.ajaxFormPostUrl,
      data,
      function (response) {
        if (typeof response.error === 'undefined') {
          indiciaData.reports.report_output.grid_report_output.reload();
          alert('Access level updated');
        } else {
          alert(response.error);
        }
      },
      'json'
    );
  }

  changeAccessLevel = function (id, access_level) {
    $('#updated_access_level').val(access_level);
    $('#updated_access_level_user_id').val(id);
    $.fancyDialog({
      contentElement: '#dialog-form',
      okButton: 'Update access level',
      callbackOk: updateAccessLevel
    })
  };
})(jQuery);