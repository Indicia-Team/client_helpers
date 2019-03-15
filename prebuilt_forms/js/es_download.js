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
    var filterClauses = [];
    var filter;
    var date;
    var hours;
    var minutes;
    if (data.done < data.total) {
      // Post to the ES proxy. Pass scroll_id parameter to request the next
      // chunk of the dataset.
      $.ajax({
        url: indiciaData.ajaxUrl + '/proxy/' + indiciaData.nid,
        type: 'post',
        data: {
          warehouse_url: indiciaData.warehouseUrl,
          scroll_id: data.scroll_id
        },
        success: function success(response) {
          updateProgress(response);
          doPages(response);
        },
        dataType: 'json'
      });
    } else {
      if ($('#query').val().trim() !== '') {
        filterClauses.push($('#query').val().trim());
      }
      if ($('#higher_geography').val().trim() !== '') {
        filterClauses.push('location: ' + $('#higher_geography').val().trim());
      }
      date = new Date();
      date.setTime(date.getTime() + (45 * 60 * 1000));
      hours = '0' + date.getHours();
      hours = hours.substr(hours.length - 2);
      minutes = '0' + date.getMinutes();
      minutes = minutes.substr(minutes.length - 2);
      filter = filterClauses.length === 0 ? '. ' : ' for query: <pre>' + filterClauses.join('\n') + '</pre>';
      $('#files').append('<div><a href="' + data.filename + '">' +
        '<span class="fas fa-file-archive"></span>' +
        'Download .zip file</a><br/>' +
        'File containing ' + data.total + ' occurrences' + filter + 'Available until ' + hours + ':' + minutes + '</div>');
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
      $.ajax({
        url: indiciaData.ajaxUrl + '/proxy/' + indiciaData.nid,
        type: 'post',
        data: {
          warehouse_url: indiciaData.warehouseUrl,
          scroll: 'true',
          query: $('#query').val(),
          higher_geography: $('#higher_geography').val()
        },
        success: function success(data) {
          if (typeof data.code !== 'undefined' && data.code === 401) {
            alert('Elasticsearch alias configuration user or secret incorrect in the form configuration.');
            $('.progress-container').hide();
          } else {
            updateProgress(data);
            doPages(data);
          }
        },
        dataType: 'json'
      });
    }
  });
});
