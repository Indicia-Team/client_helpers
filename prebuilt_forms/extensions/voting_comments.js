jQuery(document).ready(function($) {
  // Click on a star sets the rating for the question.
  $('.hover-star').click(function(e) {
    var hiddenInput = $(this).closest('.hover-ratings').find('.clicked-rating');
    $(hiddenInput).val($(this).data('value'));
  });

  // Hovering highlights the rating you will give if clicked.
  $('.hover-star').hover(function() {
    var ctr = $(this).closest('.hover-ratings');
    var hoverRating = $(this).data('value');
    $(ctr).addClass('hovering');
    $.each(ctr.find('i'), function() {
      $(this)
        .addClass($(this).data('value') <= hoverRating ? 'fas' : 'far')
        .removeClass($(this).data('value') <= hoverRating ? 'far' : 'fas');
    });
  });

  // Mouse leaving the set of stars for a question resets the highlighted stars.
  $('.hover-star').hover(function() {}, function(e) {
    var ctr = $(e.currentTarget).closest('.hover-ratings');
    var clickedRating = $(ctr).find('.clicked-rating').val();
    console.log('leave' + clickedRating);
    $(ctr).removeClass('hovering');
    $.each($(ctr).find('i'), function() {
      $(this)
        .addClass($(this).data('value') <= clickedRating ? 'fas' : 'far')
        .removeClass($(this).data('value') <= clickedRating ? 'far' : 'fas');
    });
  });

  // Save button submits filled in data.
  $('#vote-save').click(function() {
    var data = {};
    var div = $(this).closest('.voting-form');
    var s;
    $.each($(div).find('.clicked-rating, textarea'), function() {
      if ($(this).val()) {
        data[$(this).attr('name')] = $(this).val();
      }
    });
    if (data === {}) {
      alert(indiciaData.lang.votingCommentsForm.msgNothingToSave);
      return;
    }
    s = {
      website_id: indiciaData.website_id
    }
    s[indiciaData.entity + '_comment:' + indiciaData.entity + '_' + 'id'] = indiciaData.entity_id;
    s[indiciaData.entity + '_comment:comment'] = JSON.stringify(data);
    console.log(s);
    $.post(indiciaData.voteAjaxUrl, s)
      .done(function() {
        $(div).hide();
        indiciaFns.populateVoteSummary();
        alert(indiciaData.lang.votingCommentsForm.msgSaveSuccess);
      })
      .fail(function() {
        alert(indiciaData.lang.votingCommentsForm.msgSaveFail);
      });
  });

  function addScoreStars(el, score, count, title, modifier) {
    var i;
    // Font Awesome solid/regular class;
    var faClass;
    // Tolerance for showing a full star.
    var buffer = 0.2;
    var ctr = $('<div>').appendTo(el);
    if (title !== null) {
      $(ctr).append($('<h4>' + title + '</h4>'))
    }
    for (i = 1; i <= 5; i++) {
      if (score >= i - buffer) {
        // Solid.
        faClass = 'fas fa-star';
      } else if (score  < i - buffer && score  > i - 1 + buffer) {
        // Fractional vote.
        faClass = 'fas fa-star-half-alt';
      } else {
        // Hollow star.
        faClass = 'far fa-star';
      }
      if (modifier) {
        faClass += ' ' + modifier;
      }
      $(ctr).append('<i class="' + faClass + '" data-value="' + i + '"></i>');
    }
    if (count !== null) {
      $(ctr).append('&nbsp;<span>' + count + '</span>');
    }
  }

  // Populate the summary.
  indiciaFns.populateVoteSummary = function populateVoteSummary() {
    $.each($('.vote-summary'), function() {
      var div = this;
      var voteFields = $(div).data('votefields');
      var voteKeys = Object.keys(voteFields);
      var entity = $(div).data('entity');
      var reportingUrl = indiciaData.read.url
        + 'index.php/services/report/requestReport'
        + '?report=library/' + entity + 's/vote_comments_summary.xml&callback=?';
      var params = {
        mode: 'json',
        nonce: indiciaData.read.nonce,
        auth_token: indiciaData.read.auth_token,
        reportSource: 'local',
      };
      params[entity + '_id'] = $(div).data('id');
      voteKeys.forEach(function(key, idx) {
        params['key' + (idx + 1)] = key;
      });
      $.getJSON(reportingUrl, params)
        .done(function(data) {
          var heading = $(div).find('.panel-heading a');
          var body = $(div).find('.panel-body');
          // @todo i18n
          heading.children().fadeOut();
          body.children().fadeOut();
          addScoreStars(heading, data[0].vote_avg, data[0].vote_count, null, 'fa-2x');
          voteKeys.forEach(function(key, idx) {
            addScoreStars(body, data[0]['key' + (idx + 1) + '_avg'], data[0]['key' + (idx + 1) + '_count'], voteFields[key]);
          });
        });
    });
  }

  // Populate the vote details list.
  indiciaFns.populateVoteList = function populateVoteList() {
    $.each($('.vote-list'), function() {
      var div = this;
      var voteFields = $(div).data('votefields');
      var textFields = $(div).data('textfields');
      var entity = $(div).data('entity');
      var offset = $(div).data('offset');
      var dataUrl = indiciaData.read.url
        + 'index.php/services/data/' + entity + '_comment?callback=?';
      var params = {
        nonce: indiciaData.read.nonce,
        auth_token: indiciaData.read.auth_token,
        orderby: 'updated_on',
        sortdir: 'DESC',
        limit: 500,
        offset: offset
      };
      params[entity + '_id'] = $(div).data('id');
      $.getJSON(dataUrl, params)
        .done(function(data) {
          $.each(data, function() {
            var item = $('<section class="review">').appendTo(div);
            var commentObj = JSON.parse(this.comment);
            var totalScore = 0;
            var totalScoreCount = 0;
            var details;
            $('<div><i class="fas fa-user fa-2x"></i>' + this.person_name + '<div>').appendTo(item);
            $('<div>Reviewed on ' + this.updated_on + '</div>').appendTo(item);
            $.each(voteFields, function(key) {
              if (commentObj[key]) {
                totalScore += parseInt(commentObj[key], 10);
                totalScoreCount++;
              }
            });
            // Overall score for this review.
            addScoreStars(item, totalScore / totalScoreCount, null, null, 'fa-2x');
            details = $('<div class="review-details">').appendTo(item);
            // Individual scores.
            $.each(voteFields, function(key, title) {
              if (commentObj[key]) {
                addScoreStars(details, commentObj[key], null, title);
              }
            });
            // Text answers.
            $.each(textFields, function(key, title) {
              if (commentObj[key]) {
                $('<h4>' + title + '</h4>').appendTo(details);
                $('<p>' + commentObj[key].replace('\n', '<br/>') + '</p>').appendTo(details);
              }
            });
          });
        });
    });
  }

  indiciaFns.populateVoteSummary();
  indiciaFns.populateVoteList();

});
