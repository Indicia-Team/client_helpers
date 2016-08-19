var popupShareLink;

jQuery(document).ready(function($) {
  groupLinkPopup = function(title, parentTitle, id, rootFolder) {
    var link = document.createElement("a"), url, markup,
      titlePath = title.toLowerCase().replace(/ /g, '-').replace(/[^a-z0-9\-]/g, '');
    if (parentTitle) {
      titlePath = parentTitle.toLowerCase().replace(/ /g, '-').replace(/[^a-z0-9\-]/g, '') + '/' + titlePath;
    }
    link.href = rootFolder.replace('/?q=', '') + "/join/" + titlePath;
    url = link.protocol+"//"+link.host+link.pathname+link.search+link.hash;
    $('#share-link').val(url);
    $.fancybox.close();
    $.fancybox($('#group-link-popup'));
  };
});