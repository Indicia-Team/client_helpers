jQuery(document).ready(function($) {
  "use strict";
  // Click on a star sets the rating for the question.
  $('.hover-star').on('click', function(e) {
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
    $(ctr).removeClass('hovering');
    $.each($(ctr).find('i'), function() {
      $(this)
        .addClass($(this).data('value') <= clickedRating ? 'fas' : 'far')
        .removeClass($(this).data('value') <= clickedRating ? 'far' : 'fas');
    });
  });

  // Save button submits filled in data.
  $('#vote-save').on('click', function() {
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
    };
    s[indiciaData.entity + '_comment:' + indiciaData.entity + '_' + 'id'] = indiciaData.entity_id;
    s[indiciaData.entity + '_comment:comment'] = JSON.stringify(data);
    $.post(indiciaData.voteAjaxUrl, s)
      .done(function() {
        $(div).hide();
        indiciaFns.populateVoteSummary();
        indiciaFns.populateVoteList();
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
      var bodyEl = $(div).find('.panel-body');
      var voteFields = $(div).data('votefields');
      var textFields = $(div).data('textfields');
      var entity = $(div).data('entity');
      var offset = $(div).data('offset');
      var repliesMode = $(div).data('repliesmode');
      var repliesEnabled = (repliesMode === 'loggedIn' && typeof indiciaData.user_id !== 'undefined');
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
      // Clear in case re-loading.
      $(div).find('> .panel-body').html('');
      params[entity + '_id'] = $(div).data('id');
      if (repliesEnabled) {
        $(bodyEl).append('<div class="reply-form clearfix" style="display: none"><textarea class="form-control">Reply form</textarea>' +
          '<button type="button" class="btn btn-primary btn-xs pull-right save-reply">Save reply</button>' +
          '<button type="button" class="btn btn-default btn-xs pull-right cancel-reply">Cancel reply</button>' +
          '</div>');
      }
      $.getJSON(dataUrl, params)
        .done(function(data) {
          var allReplies = {};
          if (data.length === 0) {
            $(bodyEl).append('<p>' + indiciaData.lang.votesList.noReviewsPosted + '</p>');
          }
          $.each(data, function() {
            var commentObj;
            var reviewEl;
            var reviewHeaderEl;
            var reviewBodyEl;
            var totalScore = 0;
            var totalScoreCount = 0;
            var details;
            var footerEl;
            if (this.reply_to_id) {
              // Just remember the comment for now, will add to the UI in a moment.
              if (!allReplies[this.reply_to_id]) {
                allReplies[this.reply_to_id] = [];
              }
              allReplies[this.reply_to_id].push(this);
              // Skip to next comment.
              return true;
            }
            try {
              commentObj = JSON.parse(this.comment);
            }
            catch(e) {
              // Skip to next if invalid JSON.
              return true;
            }
            reviewEl = $('<article class="review panel panel-default" data-id="' + this.id + '">').appendTo(bodyEl);
            reviewHeaderEl = $('<header class="panel-heading">').appendTo(reviewEl);
            reviewBodyEl = $('<div class="panel-body">').appendTo(reviewEl);
            $('<div><i class="fas fa-user fa-2x"></i>' + this.person_name + '<div>').appendTo(reviewHeaderEl);
            $('<div>Reviewed on ' + this.updated_on + '</div>').appendTo(reviewHeaderEl);
            $.each(voteFields, function(key) {
              if (commentObj[key]) {
                totalScore += parseInt(commentObj[key], 10);
                totalScoreCount++;
              }
            });
            // Overall score for this review.
            if (totalScoreCount) {
              addScoreStars(reviewBodyEl, totalScore / totalScoreCount, null, null, 'fa-2x');
            }
            details = $('<div class="review-details">').appendTo(reviewBodyEl);
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
            $('<div class="replies">').appendTo(details);
            if (repliesEnabled) {
              footerEl = $('<footer class="panel-footer">').appendTo(reviewEl);
              $('<btn type="button" class="btn btn-default btn-xs show-reply-form"><i class="fas fa-reply"></i>' + indiciaData.lang.votesList.reply + '</button>').appendTo(footerEl);
              $('<div class="reply-cntr"></div>').appendTo(footerEl);
            }
          });
          $.each(allReplies, function(replyToId) {
            var replyCntr = $('article.review[data-id="' + replyToId + '"]').find('.replies');
            $.each(this, function() {
              var replyData = this;
              var replyEl = $('<article class="review-reply panel panel-default" data-id="' + this.id + '">').prependTo(replyCntr);
              var replyHeaderEl = $('<header class="panel-heading">').appendTo(replyEl);
              var replyBodyEl = $('<div class="panel-body">').appendTo(replyEl);
              $('<div><i class="fas fa-user fa-2x"></i>' + replyData.person_name + '<div>').appendTo(replyHeaderEl);
              $('<div>Replied on ' + replyData.updated_on + '</div>').appendTo(replyHeaderEl);
              $('<p>' + replyData.comment.replace('\n', '<br/>') + '</p>').appendTo(replyBodyEl);
            });
          })
        });
    });
  }

  indiciaFns.populateVoteSummary();
  indiciaFns.populateVoteList();

  indiciaFns.on('click', '.show-reply-form', {}, function onClickShowReplyForm(e) {
    var replyForm = $(e.currentTarget).closest('.vote-list').find('.reply-form');
    $(replyForm).find('textarea').html('')
    $(e.currentTarget).next('.reply-cntr').append(replyForm);
    $(replyForm).fadeIn();
  });

  indiciaFns.on('click', '.save-reply', {}, function onClickShowReplyForm(e) {
    var voteRepliedTo = $(e.currentTarget).closest('article.review');
    var replyForm = $(voteRepliedTo).find('.reply-form');
    var s = {
      website_id: indiciaData.website_id
    };
    var replyToId = $(voteRepliedTo).data('id');
    // Abort if nothing to save.
    if ($(replyForm).find('textarea').val().trim() === '') {
      return;
    }
    s[indiciaData.entity + '_comment:' + indiciaData.entity + '_' + 'id'] = indiciaData.entity_id;
    s[indiciaData.entity + '_comment:comment'] = $(replyForm).find('textarea').val();
    s[indiciaData.entity + '_comment:reply_to_id'] = replyToId;
    $.post(indiciaData.voteAjaxUrl, s)
      .done(function() {
        var replyEl = $('<article class="review-reply panel panel-default">').appendTo($('article.review[data-id="' + replyToId + '"] > .panel-body').find('.replies'));
        var replyHeaderEl = $('<header class="panel-heading">').appendTo(replyEl);
        var replyBodyEl = $('<div class="panel-body">').appendTo(replyEl);
        $('<div><i class="fas fa-user fa-2x"></i>' + indiciaData.lang.votesList.you + '<div>').appendTo(replyHeaderEl);
        $('<div>' + indiciaData.lang.votesList.repliedJustNow  + '</div>').appendTo(replyHeaderEl);
        $('<p>' + $(replyForm).find('textarea').val().replace('\n', '<br/>') + '</p>').appendTo(replyBodyEl);
        $(replyForm).find('textarea').val('');
        $(replyForm).fadeOut();
        alert(indiciaData.lang.votingCommentsForm.msgSaveReplySuccess);4
      })
      .fail(function() {
        alert(indiciaData.lang.votingCommentsForm.msgSaveReplyFail);
      });
  });

  indiciaFns.on('click', '.cancel-reply', {}, function onClickShowReplyForm(e) {
    var replyForm = $(e.currentTarget).closest('.reply-form');
    $(replyForm).fadeOut();
  });

});
