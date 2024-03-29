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
 *
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/indicia-team/client_helpers/
 */

jQuery(document).ready(function ($) {
  // Function to be called when removing a location from a group. Uses AJAX to delete the
  // groups_locations record.
  indiciaFns.removeLocationFromGroup = function (groupsLocationId) {
    var s;
    if (confirm('Are you sure you want to remove this location from use by the group?')) {
      s = {
        website_id: indiciaData.website_id,
        'groups_location:id': groupsLocationId,
        'groups_location:deleted': 't'
      };
      $.post(indiciaData.ajaxUrlAddExisting,
        s,
        function (data) {
          if (typeof data.error === 'undefined') {
            indiciaData.reports.report_output.grid_report_output.reload(true);
          } else {
            alert(data.error);
          }
        },
        'json'
      );
    }
  };

  $('#add-existing').click(function () {
    var locationId = $('#add_existing_location_id').val();
    var s;
    if (locationId) {
      s = {
        website_id: indiciaData.website_id,
        'groups_location:group_id': indiciaData.group_id,
        'groups_location:location_id': locationId
      };
      $.post(indiciaData.ajaxUrlAddExisting,
        s,
        function (data) {
          if (typeof data.error === 'undefined') {
            indiciaData.reports.report_output.grid_report_output.reload(true);
            // remove the selected item from the select as it is added to the grid now
            $('#add_existing_location_id option:selected').remove();
            $('#add_existing_location_id').val('');
          } else {
            alert(data.error);
          }
        },
        'json'
      );
    }
  });
});
