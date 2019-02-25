jQuery(document).ready(function docReady($) {
  var done;

  /**
   * Wind the progress spinner forward to a certain percentage.
   */
  function animateTo(progress) {
    var target = done ? 1006 : 503 + (progress * 503);
    // Stop previous animations if we are making better progress through the
    // download than 1 chunk per 0.5s. This allows the spinner to speed up.
    $('#circle').stop(true);
    $('#circle').animate({
      'stroke-dasharray': target
    }, {
      duration: 500
    });
  }

  /**
   * Updates the progress text and spinner after receiving a response.
   *
   * @param obj response
   *   Response body from the ES proxy containing progress data.
   */
  function updateProgress(response) {
    $('.progress-text').text(response.done + ' of ' + response.total);
    animateTo(response.done / response.total);
  }

  /**
   * Recurse until all the pages of a chunked download are received.
   *
   * @param obj data
   *   Response body from the ES proxy containing progress data.
   */
  function doPages(data) {
    var sep = indiciaData.ajaxUrl.match(/\?/) ? '&' : '?';
    if (data.done < data.total) {
      // Post to the ES proxy. Pass scroll_id parameter to request the next
      // chunk of the dataset.
      $.post(
        indiciaData.ajaxUrl + '/proxy/' + indiciaData.nid + sep +
          'scroll_id=' + data.scroll_id +
          '&warehouse_url=' + indiciaData.warehouseUrl,
        $('#query').val(),
        function success(response) {
          updateProgress(response);
          doPages(response);
        },
        'json'
      );
    } else {
      done = true;
      $('#files').append('<div><a href="' + data.filename + '"><span class="fas fa-file-csv"></span>Download file</div>');
      $('#files').fadeIn('med');
    }
  }

  /**
   * Download button click handler.
   */
  $('#do-download').click(function doDownload() {
    var sep = indiciaData.ajaxUrl.match(/\?/) ? '&' : '?';
    $('.progress-container').show();
    done = false;
    $('#circle').attr('style', 'stroke-dashoffset: 503px');
    $('.progress-text').text('Loading...');

    if ($('#es-settings').valid()) {
      // Post to the ES proxy. Pass scroll parameter to initiate loading the
      // dataset a chunk at a time.
      $.post(
        indiciaData.ajaxUrl + '/proxy/' + indiciaData.nid + sep + 'format=csv&scroll' +
          '&warehouse_url=' + encodeURIComponent(indiciaData.warehouseUrl),
        $('#query').val(),
        function success(data) {
          if (typeof data.code !== 'undefined' && data.code === 401) {
            alert('ElasticSearch alias configuration user or secret incorrect in the form configuration.');
            $('.progress-container').hide();
          } else {
            updateProgress(data);
            doPages(data);
          }
        },
        'json'
      );
    }
  });
});
