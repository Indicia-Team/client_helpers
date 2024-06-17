jQuery(document).ready(function($) {
  /*
   * Templates for tokens in a freeform report template.
   */

  /**
   * A heading image for a group or activity.
   *
   * Replaced by a default icon if no logo.
   */
  indiciaFns.groupHeadingImage = function(row) {
    if (!row.logo_path) {
      return '<i class="fas fa-user-friends fa-2x"></i>';
    } else {
      return '<img src="' + indiciaData.warehouseUrl + 'upload/' + row.logo_path +'" title="' + row.title + '" alt="' + row.title + ' logo" />'
    }
  };

  /**
   * Converts group title to format suitable for a URL.
   */
  indiciaFns.groupGetTitleForLink = function(row) {
    return row.title
      .toLowerCase()
      .replace(/ /g, '-')
      .replace(/[^a-z0-9\-]/g, '')
      .replace(/^\-+/, '').replace(/\-+$/, '');
  }

});
