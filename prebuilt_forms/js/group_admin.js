var approveMember,
  removeMember,
  toggleRole,
  changeAccessLevel;

(function ($) {
  "use strict";
  var dialog, form;

  approveMember=function(id) {
    var data = {
      'website_id': indiciaData.website_id,
      'groups_user:id': id,
      'groups_user:pending': 'f'
    };
    $.post(
      indiciaData.ajaxFormPostUrl,
      data,
      function (data) {
        if (typeof data.error === "undefined") {
          alert('Member approved');
          indiciaData.reports.report_output.grid_report_output.reload();
        } else {
          alert(data.error);
        }
      },
      'json'
    );
  };

  removeMember=function(id, name) {
    if (confirm('Do you really want to remove "' + name + '" from the group?')) { 
      var data = {
        'website_id': indiciaData.website_id,
        'groups_user:id': id,
        'groups_user:deleted': 't'
      };
      $.post(
        indiciaData.ajaxFormPostUrl,
        data,
        function (data) {
          if (typeof data.error === "undefined") {
            alert('Member removed');
            indiciaData.reports.report_output.grid_report_output.reload();
          } else {
            alert(data.error);
          }
        },
        'json'
      );
    }
  };
  
  toggleRole=function(id, name, makeRole) {
    var setAdministrator;
    if (makeRole==='administrator') {
      setAdministrator = true;
    } else {
      setAdministrator = false;
    }
    var data = {
      'website_id': indiciaData.website_id,
      'groups_user:id': id,
      'groups_user:administrator': setAdministrator
    };
    $.post(
      indiciaData.ajaxFormPostUrl,
      data,
      function (data) {
        if (typeof data.error === "undefined") {
          indiciaData.reports.report_output.grid_report_output.reload();
        } else {
          alert(data.error);
        }
      },
      'json'
    );    
  };

  function updateAccessLevel() {
    var data = {
      'website_id': indiciaData.website_id,
      'groups_user:id': $('#updated_access_level_user_id').val(),
      'groups_user:access_level': $('#updated_access_level').val()
    };
    $.post(
      indiciaData.ajaxFormPostUrl,
      data,
      function (data) {
        if (typeof data.error === "undefined") {
          indiciaData.reports.report_output.grid_report_output.reload();
          alert('Access level updated');
        } else {
          alert(data.error);
        }
      },
      'json'
    );
    dialog.dialog( "close" );
  }

  changeAccessLevel = function(id, access_level) {
    dialog = $( "#dialog-form" ).dialog({
      autoOpen: false,
      height: 300,
      width: 350,
      modal: true,
      buttons: {
        "Update access level": updateAccessLevel,
        Cancel: function() {
          dialog.dialog( "close" );
        }
      },
      close: function() {
        form[ 0 ].reset();
      }
    });
    $( "#updated_access_level").val(access_level);
    $( "#updated_access_level_user_id").val(id);

    form = dialog.find( "form" ).on( "submit", function( event ) {
      event.preventDefault();
      updateAccessLevel();
    });

    dialog.dialog( "open" );
  };
})(jQuery);