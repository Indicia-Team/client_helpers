jQuery(document).ready(function($) {

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

  // Track offset for search paging.
  var searchOffset = 0;
  // Max number returned in a single search page.
  var searchLimit = 20;

  function doSearch(append) {
    const searchWords = $('#group-search').val().trim();
    const filterMode = $('input[type=radio][name=group-scope]:checked').val();
    const limit = 1;
    if (searchWords || filterMode === 'member') {
      $('#group-list-container .loading-spinner').show();
      var reportingURL = indiciaData.read.url + 'index.php/services/report/requestReport' +
      '?report=library/groups/groups_list.xml&callback=?';
      var reportOptions = {
        mode: 'json',
        nonce: indiciaData.read.nonce,
        auth_token: indiciaData.read.auth_token,
        reportSource: 'local',
        currentUser: indiciaData.user_id,
        userFilterMode: $('#group-scope input[type="radio"]:checked').val(),
        // Add one to the limit, so we can detect if there are more pages.
        limit: searchLimit + 1,
        offset: searchOffset
      };
      if (searchWords) {
        reportOptions.search_fulltext = searchWords;
      }
      $.getJSON(reportingURL, reportOptions,
        function(data) {
          // Clear existing results, but not if appending a new page after
          // clicking show more.
          if (!append) {
            $('#search-groups .card-gallery ul li').remove();
          }
          // Show or hide the show more link depending on if there are more pages.
          if (data.length > searchLimit) {
            $('#show-more').show();
          }
          else {
            $('#show-more').hide();
          }
          if (data.length === 0) {
            $('#search-groups .card-gallery ul').append($('<li class="alert alert-info">Nothing found - try a different search</li>'));
          }
          $.each(data, function(idx) {
            const title = indiciaFns.escapeHtml(this.title);
            const description = this.description === null ? '' : this.description;
            if (idx >= searchLimit) {
              // We have more items than we want in the first page, so skip the
              // last item as it's just used as an indicator.
              return false;
            }
            // Append the group's card.
            $('#search-groups .card-gallery ul').append($(`<li>
        <a href="${indiciaData.rootFolder}groups/${indiciaFns.groupGetTitleForLink(this)}">
          <div class="panel panel-primary">
            <div class="panel-heading">${indiciaFns.groupHeadingImage(this)}</div>
            <div class="panel-body">
              <h3>${title}</h3>
              <p>${description}</p>
            </div>
          </div>
        </a>
      </li>`));
          });
          $('#group-list-container .loading-spinner').hide();
          $('#search-groups').show();
          $('#suggested-groups').hide();
          // Continue loop.
          return true;
        });
    }
    else {
      // Don't search if on the joinable filter with no search term, just show
      // the already loaded suggestions.
      $('#suggested-groups').show();
      $('#search-groups').hide();
    }
  }

  /**
   * Handle show more clicks if there are more pages of groups after a search.
   */
  $('#show-more').on('click', function() {
    searchOffset += searchLimit;
    doSearch(true);
  });

  /**
   * Start the search from the first page.
   */
  function newSearch() {
    searchOffset = 0;
    doSearch(false);
  }

  // Return key or Go button click does a search.
  $('#group-search').keyup(function(e) {
    if ((e.keyCode || e.which) == 13) {
      newSearch();
    }
  });
  $('#group-search-go').on('click', newSearch);

  // Changing the filter mode also re-triggers search.
  $('input[type=radio][name=group-scope]').change(newSearch);

});