/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 */

var store_notification_ids;
var remove_message;
var reply;
var createNotifications;

(function ($) {
  // Setup confirmation for Remove Notifications buttons.
  // If user elects to continue, set the hidden field that indicates
  // they want to continue with the removal.
  indiciaFns.acknowledgeNotificationsList = function(id) {
    // Version of the ID which matches how some controls or data values are named.
    const idWithUnderscores = id.replace(/[^a-z0-9]+/, '_');
    var visibleCount = $('#notifications-' + id + ' tbody tr').length,
        recordCount = indiciaData.reports['notifications_' + idWithUnderscores]['grid_notifications_' + idWithUnderscores][0].settings.recordCount;
    const currentFilter = $('#notifications_' + idWithUnderscores + '-source_filter').val();
    if (visibleCount > 0) {
      var msg='Are you sure you want to acknowledge this list of ' + recordCount + ' notifications';
      // Only change the confirmation message depending on the user's filter drop-down selection
      // if that drop-down is actually displayed (it isn't displayed if the grid has been setup
      // to preload particular source types).
      if (currentFilter !== 'all' && currentFilter !== '' && !indiciaData.preloaded_source_types) {
        msg += ' for ';
        if (currentFilter === 'record_cleaner') {
          msg += 'Record Cleaner';
        } else {
          msg += $('#notifications_' + idWithUnderscores + '-source_filter option:selected').html().toLowerCase();
        }
      }
      msg += '?';
      if (recordCount > visibleCount) {
        msg += ' Note that all pages of the list of notifications will be acknowledged, not just the visible page.';
      }
      var confirmation = confirm(msg);
      if (confirmation) {
        $('.acknowledge-notifications').val(1);
      } else {
        return false;
      }
    } else {
      alert('There are no notifications to remove');
      return false;
    }
  };

  //javascript to support removal of a single message.
  remove_message = function(id) {
    data = {
      'website_id': indiciaData.website_id,
      'notification:id': id,
      'acknowledged': 't'
    };
    $.post(
      `${indiciaData.notification_proxy_url}&user_id=${indiciaData.user_id}`,
      data,
      function (response) {
        if (typeof response.success==='undefined') {
          alert('An error occurred whilst removing the message.');
          alert(JSON.stringify(response));
        } else {
          //reload grid after notification is deleted
          indiciaData.reports.notifications_notifications_grid.grid_notifications_notifications_grid.removeRecordsFromPage(1);
          indiciaData.reports.notifications_notifications_grid.grid_notifications_notifications_grid.reload(true);
        }
      },
      'json'
    );
  };

  //javascript to support posting of a reply.
  reply = function (occurrence_id, notification_id, continued) {
    if (continued===false) {
      $('#reply-row-'+occurrence_id).remove();
      $('tr#row'+notification_id+' .action-button').show();
      return false;
    }
    if (!$('#reply-'+occurrence_id).val()) {
      alert('Please provide a comment');
      return false;
    }
    var data = {
      'website_id': indiciaData.website_id,
      'occurrence_comment:occurrence_id': occurrence_id,
      'occurrence_comment:comment': $('#reply-'+occurrence_id).val(),
      'user_id': indiciaData.user_id
    };

    $.post(
      indiciaData.occurrence_comment_proxy_url+'&sharing=reporting',
      data,
      function (response) {
        if (typeof response.success==='undefined') {
          alert('An error occurred. Comment could not be saved.');
          alert(JSON.stringify(response));
        } else {
          alert('Your comment has been saved');
        }
      },
      'json'
    );
    $('#reply-row-'+occurrence_id).remove();
    $('tr#row'+notification_id+' .action-button').show();
  }

  $.each($('.notifications-cntr'), function() {
    const cntr = this;
    const gridId = $(cntr).find('.report-grid-container').attr('id');

    // Also store any grid column filters so the acknowledge button can use the
    // same filter.
    let endRegex = new RegExp('-' + gridId + '$');
    $(cntr).find('.col-filter').on('change', function() {
      let data = {};
      $.each($(cntr).find('.col-filter'), function() {
        if ($(this).val()) {
          let colName = $(this).attr('id').replace(/^col-filter-/, '').replace(endRegex, '')
          data[colName] = $(this).val();
        }
      });
      $(cntr).find('form [name="filter-row-data"]').val(JSON.stringify(data));
    });
  });

}) (jQuery);