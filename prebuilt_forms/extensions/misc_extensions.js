jQuery(document).ready(function ($) {
  'use strict';
  /**
   * A function to display a popup showing a link to a group's join page for sharing.
   * @param string title The title of the group.
   * @param string parentTitle The title of the parent of the group if there is one.
   * @param integer id The group ID.
   * @param string rootFolder The path of the root of the website including ?q= when required.
   */
  indiciaFns.groupLinkPopup = function (title, parentTitle, id, rootFolder) {
    var link = document.createElement('a');
    var url;
    var titlePath = title.toLowerCase().replace(/ /g, '-').replace(/[^a-z0-9\-]/g, '');
    if (parentTitle) {
      titlePath = parentTitle.toLowerCase().replace(/ /g, '-').replace(/[^a-z0-9\-]/g, '') + '/' + titlePath;
    }
    link.href = rootFolder.replace('/?q=', '') + '/join/' + titlePath;
    url = link.protocol + '//' + link.host + link.pathname + link.search + link.hash;
    $('#share-link').val(url);
    $.fancybox.close();
    $.fancybox($('#group-link-popup'));
  };
});
