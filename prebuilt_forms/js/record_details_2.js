
jQuery(document).ready(function docReady($) {
  function showComment(comment, username) {
    var html = '<div class="comment">';
    var c = comment.replace(/\n/g, '<br/>');
    html += '<div class="header">';
    html += '<strong>' + username + '</strong> Now';
    html += '</div>';
    html += '<div>' + c + '</div>';
    html += '</div>';
    // Remove message that there are no comments
    $('#no-comments').hide();
    $('#comment-list').prepend(html);
  }

  indiciaFns.saveComment = function saveComment(occurrenceId) {
    var data = {
      website_id: indiciaData.website_id,
      'occurrence_comment:occurrence_id': occurrenceId,
      'occurrence_comment:comment': $('#comment-text').val(),
      'occurrence_comment:person_name': indiciaData.username,
      user_id: indiciaData.user_id
    };
    $.post(
      indiciaData.ajaxFormPostUrl.replace('occurrence', 'occ-comment'),
      data,
      function commentResponse(response) {
        if (typeof response.error === 'undefined') {
          showComment($('#comment-text').val(), indiciaData.username);
          $('#comment-text').val('');
        } else {
          alert(response.error);
        }
      },
      'json'
    );
  };

  /**
   * When the map loads, set the feature style to ensure small grid squares are
   * visible.
   */
  mapInitialisationHooks.push(function initMap(div) {
    var layer = div.map.editLayer;
    var defaultStyle = new OpenLayers.Style({
      fillColor: '#ee9900',
      strokeColor: '#ee9900',
      strokeWidth: '${getstrokewidth}',
      fillOpacity: 0.5,
      strokeOpacity: 0.8,
      pointRadius: '${getpointradius}'
    }, {
      context: {
        getstrokewidth: function getstrokewidth(feature) {
          var width = feature.geometry.getBounds().right - feature.geometry.getBounds().left;
          var strokeWidth = (width === 0) ? 1 : 12 - (width / feature.layer.map.getResolution());
          return (strokeWidth < 2) ? 2 : strokeWidth;
        },
        getpointradius: function getpointradius(feature) {
          var units;
          if (typeof indiciaData.srefPrecision === 'undefined') {
            return 5;
          }
          units = indiciaData.srefPrecision || 20;
          if (feature.geometry.getCentroid().y > 4000000) {
            units *= (feature.geometry.getCentroid().y / 8200000);
          }
          return Math.max(5, units / (feature.layer.map.getResolution()));
        }
      }
    });
    layer.style = null;
    layer.styleMap = defaultStyle;
    layer.features[0].style = null;
    layer.redraw();
  });
});

