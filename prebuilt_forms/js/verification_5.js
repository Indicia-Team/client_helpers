indiciaData.rowIdToReselect = false;

(function ($) {
  'use strict';

  var rowRequest = null;
  var occurrenceId = null;
  var currRec = null;
  var urlSep;
  var validator;
  var speciesLayers = [];
  var trustsCounter;
  var multimode = false;
  var email = { to: '', from: '', subject: '', body: '', type: '' };
  var loadedTabs = [];

  /**
   * A public function to allow any custom scripts to know what record is being checked.
   */
  indiciaFns.getCurrentVerificationRecord = function foo() {
    return {
      occurrenceId: occurrenceId,
      currRec: currRec
    };
  };

  /**
   * Resets to the state where no grid row is shown
   */
  function clearRow() {
    $('table.report-grid tr').removeClass('selected');
    $('#instructions').show();
    $('#record-details-content').hide();
    occurrenceId = null;
    currRec = null;
  }

  /**
   * Because we can't be sure the report layer will be visible, always show the selected record on the edit layer.
   */
  function showSelectedRecordOnMap() {
    var geom;
    var feature;
    var map = indiciaData.mapdiv.map;
    geom = OpenLayers.Geometry.fromWKT(currRec.extra.wkt);
    if (map.projection.getCode() !== indiciaData.mapdiv.indiciaProjection.getCode()) {
      geom.transform(indiciaData.mapdiv.indiciaProjection, map.projection);
    }
    feature = new OpenLayers.Feature.Vector(geom);
    feature.attributes.type = 'selectedrecord';
    feature.attributes.sref_precision = currRec.extra.sref_precision;
    indiciaData.mapdiv.removeAllFeatures(map.editLayer, 'selectedrecord');
    map.editLayer.addFeatures([feature]);
    // Force the correct style.
    feature.style = map.editLayer.styleMap.styles.defaultStyle;
    map.editLayer.redraw();
  }

  /**
   * Event handler for changes to map layers. On visibility change, store a cookie to remember the setting.
   */
  function mapLayerChanged(event) {
    if (event.property === 'visibility') {
      indiciaFns.cookie('verification-' + event.layer.name, event.layer.visibility ? 'true' : 'false');
    }
  }

  mapInitialisationHooks.push(function (div) {
    // nasty hack to fix a problem where these layers get stuck and won't reload after pan/zoom on IE & Chrome
    div.map.events.register('moveend', null, function () {
      $.each(speciesLayers, function (idx, layer) {
        div.map.removeLayer(layer);
        div.map.addLayer(layer);
      });
    });
    div.map.events.register('changelayer', null, mapLayerChanged);
    if (typeof $.cookie !== 'undefined') {
      $.each(div.map.layers, function checkLayerVisible() {
        if (!this.isBaseLayer && this !== div.map.editLayer) {
          if ($.cookie('verification-' + this.name) !== 'true') {
            this.setVisibility(false);
          }
        }
      });
    }
  });

  function selectRow(tr, callback) {
    var path = $(tr).find('.row-input-form-link').val();
    var sep = (path.indexOf('?') >= 0) ? '&' : '?';
    // The row ID is row1234 where 1234 is the occurrence ID.
    if (tr.id.substr(3) === occurrenceId) {
      if (typeof callback !== 'undefined') {
        callback(tr);
      }
      return;
    }
    loadedTabs = [];
    if (rowRequest) {
      rowRequest.abort();
    }
    // while we are loading, disable the toolbar
    $('#record-details-toolbar *').attr('disabled', 'disabled');
    occurrenceId = tr.id.substr(3);
    $(tr).addClass('selected');
    $('#btn-edit-record').attr('href', path + sep + 'occurrence_id=' + occurrenceId);
    // make it clear things are loading
    if ($('#record-details-toolbar .loading-spinner').length === 0) {
      $('#record-details-toolbar').append('<div class="loading-spinner"><div>Loading...</div></div>');
    }
    rowRequest = $.getJSON(
      indiciaData.ajaxUrl + '/details/' + indiciaData.nid + urlSep + 'occurrence_id=' + occurrenceId,
      null,
      function (data) {
        // refind the row, as $(tr) sometimes gets obliterated.
        var $row = $('#row' + data.data.Record[0].value);
        var layer;
        var thisSpLyrSettings;
        var filter;
        var workflow = (indiciaData.workflowEnabled &&
                indiciaData.workflowTaxonMeaningIDsLogAllComms.indexOf(data.extra.taxon_meaning_id) !== -1);

        rowRequest = null;
        currRec = data;
        if (currRec.extra.created_by_id === '1') {
          $('.trust-tool').hide();
        } else {
          $('.trust-tool').show();
        }
        if ($row.find('.row-input-form-raw').val() !== '') {
          $row.find('.verify-tools .edit-record').closest('li').show();
          $('#btn-edit-record').show();
        } else {
          $row.find('.verify-tools .edit-record').closest('li').hide();
          $('#btn-edit-record').hide();
        }
        if (currRec.extra.query === 'Q') {
          $('#btn-log-response').show();
        } else {
          $('#btn-log-response').hide();
        }
        $('#instructions').hide();
        $('#record-details-content').show();
        if ($row.parents('tbody').length !== 0) {
          // point the comments tabs to the correct AJAX call for the selected occurrence.
          indiciaFns.setTabHref($('#record-details-tabs'), indiciaData.detailsTabs.indexOf('comments'), 'comments-tab-tab',
            indiciaData.ajaxUrl + '/comments/' + indiciaData.nid + urlSep + 'occurrence_id=' + occurrenceId +
            (workflow ? '&allowconfidential=true' : ''));
          // reload current tabs
          $('#record-details-tabs').tabs('load', indiciaFns.activeTab($('#record-details-tabs')));
          $('#record-details-toolbar *').removeAttr('disabled');
          showTab();
          // remove any wms layers for species or the gateway data
          $.each(speciesLayers, function () {
            indiciaData.mapdiv.map.removeLayer(this);
            this.destroy();
          });
          speciesLayers = [];
          if (typeof indiciaData.wmsSpeciesLayers !== 'undefined' && data.extra.taxon_external_key !== null) {
            $.each(indiciaData.wmsSpeciesLayers, function (idx, layerDef) {
              thisSpLyrSettings = $.extend({}, layerDef.settings);
              // replace values with the external key if the token is used
              $.each(thisSpLyrSettings, function (prop, value) {
                if (typeof value === 'string' && value.trim() === '{external_key}') {
                  thisSpLyrSettings[prop] = data.extra.taxon_external_key;
                }
              });
              layer = new OpenLayers.Layer.WMS(layerDef.title, layerDef.url.replace('{external_key}', data.extra.taxon_external_key),
                thisSpLyrSettings, layerDef.olSettings);
              indiciaData.mapdiv.map.addLayer(layer);
              layer.setZIndex(0);
              speciesLayers.push(layer);
            });
          }
          if (typeof indiciaData.indiciaSpeciesLayer !== 'undefined' && data.extra[indiciaData.indiciaSpeciesLayer.filterField] !== null) {
            filter = indiciaData.indiciaSpeciesLayer.cqlFilter.replace('{filterValue}', data.extra[indiciaData.indiciaSpeciesLayer.filterField]);
            layer = new OpenLayers.Layer.WMS(indiciaData.indiciaSpeciesLayer.title, indiciaData.indiciaSpeciesLayer.wmsUrl,
              {
                layers: indiciaData.indiciaSpeciesLayer.featureType,
                transparent: true,
                CQL_FILTER: filter,
                STYLES: indiciaData.indiciaSpeciesLayer.sld
              },
              { isBaseLayer: false, sphericalMercator: true, singleTile: true, opacity: 0.5 }
            );
            indiciaData.mapdiv.map.addLayer(layer);
            layer.setZIndex(0);
            speciesLayers.push(layer);
          }
          $.each(speciesLayers, function checkLayerVisible() {
            if ($.cookie('verification-' + this.name) !== 'true') {
              this.setVisibility(false);
            }
          });
          showSelectedRecordOnMap();
        }
        // ensure the feature is selected and centred
        indiciaData.reports.verification.grid_verification_grid.highlightFeatureById(data.data.Record[0].value, false);
        if (typeof callback !== 'undefined') {
          callback(tr);
        }
        $('#record-details-toolbar .loading-spinner').remove();
      }
    );
  }

  function removeStatusClasses(selector, prefix, items) {
    $.each(items, function () {
      $(selector).removeClass(prefix + '-' + this);
    });
  }

  /**
   * Post an object containing occurrence form data into the Warehouse. Updates the
   * visual indicators of the record's status.
   */
  function postVerification(occ) {
    var status = occ['occurrence:record_status'];
    var id = occ['occurrence:id'];
    var substatus = typeof occ['occurrence:record_substatus'] === 'undefined' ? null : occ['occurrence:record_substatus'];
    $.post(
      indiciaData.ajaxFormPostUrl.replace('occurrence', 'single_verify'),
      occ,
      function () {
        var text;
        var nextRow;
        removeStatusClasses('#row' + id + ' td:first div, #details-tab td', 'status', ['V', 'C', 'R', 'I', 'T']);
        removeStatusClasses('#row' + id + ' td:first div, #details-tab td', 'substatus', [1, 2, 3, 4, 5]);
        $('#row' + id + ' td:first div, #details-tab td.status').addClass('status-' + status);
        if (substatus) {
          $('#row' + id + ' td:first div, #details-tab td.status').addClass('substatus-' + substatus);
        }
        text = indiciaData.statusTranslations[status + (substatus || '')];
        $('#details-tab td.status').html(text);
        if (indiciaData.detailsTabs[indiciaFns.activeTab($('#record-details-tabs'))] === 'details' ||
          indiciaData.detailsTabs[indiciaFns.activeTab($('#record-details-tabs'))] === 'comments') {
          $('#record-details-tabs').tabs('load', indiciaFns.activeTab($('#record-details-tabs')));
        }
        if (indiciaData.autoDiscard) {
          // remove any following footable detail row first
          $('#row' + id).next('.footable-row-detail').remove();
          nextRow = $('#row' + id).next();
          $('#row' + id).remove();
          if (nextRow.length > 0) {
            selectRow(nextRow[0]);
            indiciaData.reports.verification.grid_verification_grid.removeRecordsFromPage(1);
          } else {
            // reload the grid once empty, to get the next page
            indiciaData.reports.verification.grid_verification_grid.reload();
            clearRow();
          }
        }
      }
    );
    $('#add-comment').remove();
  }

  /**
   * Build an email to send to a verifier or the original recorder, containing the record details.
   */
  function setupRecordCheckEmail(subject, body) {
    // to create email of record details
    var record = '';
    $.each(currRec.data, function eachValue(idx, section) {
      $.each(section, function eachSection() {
        if (this.value !== null && this.value !== '') {
          record += this.caption + ': ' + this.value + '\n';
        }
      });
    });
    record += '\n\n[Photos]\n\n[Comments]';
    email.to = currRec.extra.recorder_email;
    email.subject = subject
      .replace('%taxon%', currRec.extra.taxon)
      .replace('%id%', occurrenceId);
    email.body = body
      .replace('%taxon%', currRec.extra.taxon)
      .replace('%id%', occurrenceId)
      .replace('%record%', record);
    $('#record-details-tabs').tabs('load', 0);
    email.type = 'recordCheck';
  }

  /**
   * Build an email for sending to another expert.
   */
  function buildVerifierEmail() {
    setupRecordCheckEmail(indiciaData.email_subject_send_to_verifier, indiciaData.email_body_send_to_verifier);
    // Let the user pick the recipient
    email.to = '';
    email.subtype = 'V';
    popupEmailExpert();
  }

  function recorderQueryEmailForm() {
    var r;
    setupRecordCheckEmail(indiciaData.email_subject_send_to_recorder, indiciaData.email_body_send_to_recorder);
    r = '<form id="email-form" class="popup-form"><fieldset>' +
      '<legend>' + indiciaData.popupTranslations.tab_email + '</legend>';
    r += '<div class="verify-template-container"> ' +
      '<label class="auto">' + indiciaData.popupTranslations.templateLabel + ' : </label>' +
      '<select class="verify-template" >' +
      '<option value="">' + indiciaData.popupTranslations.pleaseSelect + '</option></select></div>';
    r +=
      '<label>To:</label><input type="text" id="email-to" class="email required" value="' + email.to + '"/><br />' +
      '<label>Subject:</label><input type="text" id="email-subject" class="required" value="' + email.subject + '"/><br />' +
      '<label>Body:</label><textarea id="email-body" class="required templatable-comment">' + email.body + '</textarea><br />' +
      '<input type="submit" class="default-button" ' +
      'value="' + indiciaData.popupTranslations.sendEmail + '" />' +
      '</fieldset></form>';
    return r;
  }

  function recorderQueryCommentForm() {
    var workflow = (indiciaData.workflowEnabled &&
                    indiciaData.workflowTaxonMeaningIDsLogAllComms.indexOf(currRec.extra.taxon_meaning_id) !== -1);
    var r = '<form class="popup-form"><fieldset><legend>Add new query</legend>';
    r += '<div class="verify-template-container"> ' +
    '<label class="auto">' + indiciaData.popupTranslations.templateLabel + ' : </label>' +
    '<select class="verify-template" >' +
    '<option value="">' + indiciaData.popupTranslations.pleaseSelect + '</option></select></div>';
    r +=
      (workflow ? '<label><input type="checkbox" id="query-confidential" /> ' + indiciaData.popupTranslations.confidential + '</label><br>' : '') +
      '<textarea id="query-comment-text" rows="30" class="templatable-comment"></textarea><br>' +
      '<button type="button" class="default-button" onclick="indiciaFns.saveComment(jQuery(\'#query-comment-text\').val(), null, jQuery(\'#query-confidential:checked\').length, null, \'t\', true); jQuery.fancybox.close();">' +
      'Add query to comments log</button></fieldset></form>';
    return r;
  }

  function popupTabs(tabs) {
    var r = '<div id="popup-tabs"><ul>';
    var title;
    $.each(tabs, function eachTab(id) {
      title = indiciaData.popupTranslations['tab_' + id];
      r += '<li id="tab-' + id + '-tab"><a href="#tab-' + id + '">' + title + '</a></li>';
    });
    r += '</ul>';
    $.each(tabs, function eachTab(id, tab) {
      r += '<div id="tab-' + id + '">' + tab + '</div>';
    });
    r += '</div>';
    return r;
  }

  function popupQueryForm(html) {
    $.fancybox.open(html);
    loadVerificationTemplates('Q');
    if ($('#popup-tabs')) {
      $('#popup-tabs').tabs();
    }
  }

  function recorderQueryProbablyCantContact() {
    var html = '<p>' + indiciaData.popupTranslations.queryProbablyCantContact + '</p>';
    html += recorderQueryCommentForm();
    popupQueryForm(html);
  }

  /*
   * Saves the authorisation token for the Record Comment Quick Reply page into the database
   * so that it is saved against the occurrence id
   * @param string authorisationNumber
   * @return boolean indicates if database was successfully written to or not
   *
   */
  function saveAuthorisationNumberToDb(authorisationNumber, occurrenceId) {
    var data = {
      website_id: indiciaData.website_id,
      'comment_quick_reply_page_auth:occurrence_id': occurrenceId,
      'comment_quick_reply_page_auth:token': authorisationNumber
    };
    $.post(
      indiciaData.ajaxFormPostUrl.replace('occurrence', 'comment_quick_reply_page_auth'),
      data,
      function (data) {
        if (typeof data.error !== 'undefined') {
          alert(data.error);
        }
      },
      'json'
    );
  }

    // Use an AJAX call to get the server to send the email
  function sendEmail() {
    var authorisationWriteToDbResult;
    var autoRemoveLink = false;
    // If the email isn't for an occurrence or the setup options are missing
    // then then we won't be writing an auth to the DB, and we will want to
    // remove the link from the email body.
    if (!indiciaData.commentQuickReplyPageLinkURL || !indiciaData.commentQuickReplyPageLinkLabel || !occurrenceId) {
      autoRemoveLink = true;
    } else {
      // Setup the quick reply page link and get an authorisation number.
      var personIdentifierParam;
      // Note: The quick reply page does actually support supplying a user_id parameter to it, however we don't do that in practice here as
      // we don't actually know if the user has an account (we would also have to collect the user_id for the entered email)
      personIdentifierParam = '&email_address=' + email.to;
      // Need an authorisation unique string in URL, this is linked to the occurrence.
      // Only if correct auth and occurrence_id combination are present does the Record Comment Quick Reply page display
      var authorisationNumber = makeAuthNumber();
      var authorisationParam = '&auth=' + authorisationNumber;
      var commentQuickReplyPageLink = '<a href="' + indiciaData.commentQuickReplyPageLinkURL + '?occurrence_id=' +
          occurrenceId + personIdentifierParam + authorisationParam + '">' +
          indiciaData.commentQuickReplyPageLinkLabel + '</a>';
      // This returns true if error saving authorisation to the database.
      authorisationWriteToDbResult = saveAuthorisationNumberToDb(authorisationNumber, occurrenceId);
    }
    // To Do: Warn user if the DB authorisation write to the database failed, as we will need to auto remove the link
    // Not currently operational as the authorisation write to the database is asynchronous
    if (authorisationWriteToDbResult === 'failure') {
      alert('The email will be sent, however we have not been able to generate the link ' +
            'to the Occurrence Comment Quick Reply page because of a problem when communicating with the database.\n\n\It will be removed from the the email.')
    }
    // Replace the text token from the email with the actual link or remove the link if
    // A. We couldn't write the authorisation to the database
    // B. The system is requesting we remove the link (perhaps the required options are not filled in)
    if (authorisationWriteToDbResult !== 'failure' && autoRemoveLink === false) {
      email.body = email.body
            .replace('%commentQuickReplyPageLink%', commentQuickReplyPageLink);
    } else {
      email.body = email.body
            .replace('%commentQuickReplyPageLink%', '');
    }
    $.post(
      indiciaData.ajaxUrl + '/email/' + indiciaData.nid,
      email,
      function (response) {
        if (response === 'OK') {
          $.fancybox.close();
          alert(indiciaData.popupTranslations.emailSent);
        } else {
          $.fancybox.open('<div class="manual-email">' + indiciaData.popupTranslations.requestManualEmail +
            '<div class="ui-helper-clearfix"><span class="left">To:</span><div class="right">' + email.to + '</div></div>' +
            '<div class="ui-helper-clearfix"><span class="left">Subject:</span><div class="right">' + email.subject + '</div></div>' +
            '<div class="ui-helper-clearfix"><span class="left">Content:</span><div class="right">' + email.body.replace(/\n/g, '<br/>') + '</div></div>' +
            '</div>');
        }
      }
    );
  }

  /*
   * Create a random authorisation number to pass to the Record Comment Quick Reply page
   * (This page sits outside the Warehouse)
   * @returns string random authorisation token
   */
  function makeAuthNumber() {
    var characterSelection = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    var authNum = "";
    for (var digit = 0; digit < 16; digit++) {
      authNum += characterSelection.charAt(Math.floor(Math.random() * characterSelection.length));
    }
    return authNum;
  }

  function processEmail() {
    // Capture occurrence ID now, in case it changes before Ajax response received.
    var commentOccurrenceId = occurrenceId;
    // Complete creation of email of record details
    if (validator.numberOfInvalids() === 0) {
      email.to = $('#email-to').val();
      email.from = indiciaData.siteEmail;
      email.subject = $('#email-subject').val();
      email.body = $('#email-body').val();

      if (email.type === 'recordCheck') {
        // ensure media are loaded
        $.ajax({
          url: indiciaData.ajaxUrl + '/mediaAndComments/' + indiciaData.nid + urlSep +
          'occurrence_id=' + occurrenceId + '&sample_id=' + currRec.extra.sample_id,
          dataType: 'json',
          success: function handleResponse(response) {
            var comment = indiciaData.commentTranslations.emailed.replace(
              '{1}',
              email.subtype === 'R' ? indiciaData.commentTranslations.recorder : indiciaData.commentTranslations.expert
            );
            email.body = email.body.replace(/\[Photos]/g, response.media.replace(/data-media-info=\"(.*?)\"/g, ''));
            email.body = email.body.replace(/\[Comments]/g, response.comments);
            // save a comment to indicate that the mail was sent
            indiciaFns.saveComment(
              comment,
              null,
              ($('#email-confidential:checked').length > 0 ? 't' : 'f'),
              email,
              't',
              true,
              commentOccurrenceId
            );
            sendEmail();
          }
        });
      } else {
        sendEmail();
      }
    }
    return false;
  }

  function recorderQueryNeedsEmail() {
    var html = '<p>' + indiciaData.popupTranslations.queryNeedsEmail + '</p>';
    html += recorderQueryEmailForm();
    popupQueryForm(html);
    validator = $('#email-form').validate({});
    $('#email-form').submit(processEmail);
  }

  function recorderQueryProbablyNeedsEmail(likelihoodOfReceivingNotification) {
    var tab1;
    var tab2;
    if (likelihoodOfReceivingNotification === 'no') {
      tab1 = '<p>' + indiciaData.popupTranslations.queryProbablyNeedsEmailNo + '</p>';
    } else {
      tab1 = '<p>' + indiciaData.popupTranslations.queryProbablyNeedsEmailUnknown + '</p>';
    }
    tab1 += recorderQueryEmailForm();
    tab2 = recorderQueryCommentForm();
    popupQueryForm(popupTabs({ email: tab1, comment: tab2 }));
    validator = $('#email-form').validate({});
    $('#email-form').submit(processEmail);
  }

  function recorderQueryProbablyWillGetNotified() {
    var tab1;
    var tab2;
    tab1 = '<p>' + indiciaData.popupTranslations.queryProbablyWillGetNotified + '</p>';
    tab1 += recorderQueryCommentForm();
    tab2 = recorderQueryEmailForm();
    popupQueryForm(popupTabs({ comment: tab1, email: tab2 }));
    validator = $('#email-form').validate({});
    $('#email-form').submit(processEmail);
  }

  /**
   * Sends a query to the original recorder by the best means available.
   */
  function buildRecorderQueryMessage() {
    email.subtype = 'R';
    // Find out the best means of contact
    if (currRec.extra.created_by_id === '1') {
      // record not logged to a warehouse user account, so they definitely won't get notifications
      if (!currRec.extra.recorder_email) {
        recorderQueryProbablyCantContact();
      } else {
        recorderQueryNeedsEmail();
      }
    } else {
      // They are a logged in user. We need to know if they are receiving their notifications.
      $.ajax({
        url: indiciaData.ajaxUrl + '/do_they_see_notifications/' + indiciaData.nid + urlSep + 'user_id=' + currRec.extra.created_by_id,
        success: function (response) {
          if (response === 'yes' || response === 'maybe') {
            recorderQueryProbablyWillGetNotified();
          } else if (response === 'no' || response === 'unknown') {
            recorderQueryProbablyNeedsEmail(response);
          }
        }
      });
    }
  }

  function popupEmailExpert() {
    var workflow = (indiciaData.workflowEnabled &&
                indiciaData.workflowTaxonMeaningIDsLogAllComms.indexOf(currRec.extra.taxon_meaning_id) !== -1);
    $.fancybox.open('<form id="email-form"><fieldset class="popup-form">' +
      '<legend>' + indiciaData.popupTranslations.emailTitle + '</legend>' +
      '<p>' + indiciaData.popupTranslations.emailInstruction + '</p>' +
      (workflow ? '<label><input type="checkbox" id="email-confidential" /> ' + indiciaData.popupTranslations.confidential + '</label><br>' : '') +
      '<label>To:</label><input type="text" id="email-to" class="email required" value="' + email.to + '"/><br />' +
      '<label>Subject:</label><input type="text" id="email-subject" class="required" value="' + email.subject + '"/><br />' +
      '<label>Body:</label><textarea id="email-body" class="required">' + email.body + '</textarea><br />' +
      '<input type="submit" class="default-button" ' +
      'value="' + indiciaData.popupTranslations.sendEmail + '" />' +
      '</fieldset></form>');
    validator = $('#email-form').validate({});
    $('#email-form').submit(processEmail);
  }

  function showComment(comment, query, username) {
    var html = '<div class="comment"><div class="header">';
    // Remove message that there are no comments
    $('#no-comments').hide();
    if (query === 't') {
      html += '<img width="12" height="12" src="' + indiciaData.imgPath + 'nuvola/dubious-16px.png"/>';
    }
    html += '<strong>' + username + '</strong> Now';
    html += '</div>';
    html += '<div>' + comment + '</div>';
    html += '</div>';
    $('#comment-list').prepend(html);
  }

  indiciaFns.saveComment = function (text, reference, confidential, emailDef, query, reloadGridAfterSave, commentOccurrenceId) {
    var data;
    var q = typeof query === 'undefined' ? 'f' : query;
    var c = (typeof confidential === 'undefined' || confidential == 0 || confidential === 'f') ? 'f' : 't';
    // Default occurrence ID to the current row. It might need to be overridden
    // if calling from inside an AJAX response, in case the selected record has
    // since changed.
    commentOccurrenceId = typeof commentOccurrenceId === 'undefined' ? occurrenceId : commentOccurrenceId;
    data = {
      website_id: indiciaData.website_id,
      'occurrence_comment:occurrence_id': commentOccurrenceId,
      'occurrence_comment:comment': text,
      'occurrence_comment:reference': reference,
      'occurrence_comment:person_name': indiciaData.username,
      'occurrence_comment:query': q,
      'occurrence_comment:confidential': c
    };
    if (emailDef && indiciaData.workflowEnabled &&
        indiciaData.workflowTaxonMeaningIDsLogAllComms.indexOf(currRec.extra.taxon_meaning_id) !== -1) {
      data['occurrence_comment:correspondence_data'] = JSON.stringify({
        email: [{
          from: emailDef.from,
          to: emailDef.to,
          subject: emailDef.subject,
          body: emailDef.body
        }]
      });
    }
    $.post(
      indiciaData.ajaxFormPostUrl.replace('occurrence', 'occ-comment'),
      data,
      function handleResponse(response) {
        if (typeof response.error === 'undefined') {
          showComment(text, q, indiciaData.username);
          if ($('#comment-text')) {
            $('#comment-text').val('');
          }
          if ($('#comment-reference')) {
            $('#comment-reference').val('');
          }
          if (typeof reloadGridAfterSave !== 'undefined' && reloadGridAfterSave === true) {
            reloadGrid();
          }
        } else {
          alert(response.error);
        }
      }
    );
  };

  function postStatusComment(occId, status, substatus, comment) {
    var data = {
      website_id: indiciaData.website_id,
      'occurrence:id': occId,
      user_id: indiciaData.user_id,
      'occurrence:record_status': status,
      'occurrence_comment:comment': comment,
      'occurrence:record_decision_source': 'H'
    };
    if (substatus) {
      data['occurrence:record_substatus'] = substatus;
    }
    postVerification(data);
  }

  function statusLabel(status, substatus) {
    var labels = [];
    if (typeof indiciaData.popupTranslations[status] !== 'undefined' && (status !== 'C' || substatus !== 3)) {
      labels.push(indiciaData.popupTranslations[status]);
    }
    if (indiciaData.popupTranslations['sub' + substatus]) {
      labels.push(indiciaData.popupTranslations['sub' + substatus]);
    }
    return labels.join(' as ');
  }

  indiciaFns.saveVerifyComment = function () {
    var status = $('#set-status').val();
    var substatus = $('#set-substatus').val();
    var comment = statusLabel(status, substatus);
    // capitalise status label
    comment = comment.charAt(0).toUpperCase() + comment.slice(1);
    if ($('#verify-comment').val() !== '') {
      comment += '.\n' + $('#verify-comment').val();
    }
    $.fancybox.close();
    if (multimode) {
      $.each($('.check-row:checked'), function (idx, elem) {
        $($(elem).parents('tr')[0]).css('opacity', 0.2);
        postStatusComment($(elem).val(), status, substatus, comment);
      });
    } else {
      postStatusComment(occurrenceId, status, substatus, comment);
    }
  };

  // show the list of tickboxes for verifying multiple records quickly
  function showTickList() {
    $('.check-row').attr('checked', false);
    $('.check-row').show();
    $('#action-buttons-status label').html('With ticked records:');
    $('#btn-multiple').addClass('active').html('Review single records').after($('#action-buttons-status'));
    $('#action-buttons-status button').removeAttr('disabled');
  }

  // Callback for the report grid. Use to fill in the tickboxes if in multiple mode. Also reselects the previously
  // selected row where relevant.
  window.verificationGridLoaded = function () {
    var row;
    if (indiciaData.rowIdToReselect) {
      // Reselect current record if still in the grid
      row = $('tr#row' + indiciaData.rowIdToReselect);
      if (row.length) {
        occurrenceId = null;
        selectRow(row[0]);
      } else {
        clearRow();
      }
      indiciaData.rowIdToReselect = false;
    }
    if (multimode) {
      showTickList();
    }
  };

  function showTab() {
    var params;
    if (currRec === null) {
      return;
    }
    if ($.inArray(indiciaData.detailsTabs[indiciaFns.activeTab($('#record-details-tabs'))], loadedTabs) !== -1) {
      return;
    }
    if (indiciaData.detailsTabs[indiciaFns.activeTab($('#record-details-tabs'))] === 'details') {
      $('#details-tab').html(currRec.content);
    } else if (indiciaData.detailsTabs[indiciaFns.activeTab($('#record-details-tabs'))] === 'experience') {
      if (currRec.extra.created_by_id === '1') {
        $('#experience-div').html('No experience information available. This record does not have the required information for other records by the same recorder to be extracted.');
      } else {
        params = 'occurrence_id=' + occurrenceId + '&user_id=' + currRec.extra.created_by_id;
        // Include context in the link params.
        if ($('#context-filter').length > 0) {
          params += '&context_id=' + $('#context-filter option:selected').val();
        }
        // make it clear things are loading
        if ($('#experience-div .loading-spinner').length === 0) {
          $('#experience-div').append('<div class="loading-spinner"><div>Loading...</div></div>');
        }
        $.get(
          indiciaData.ajaxUrl + '/experience/' + indiciaData.nid + urlSep + params,
          null,
          function success(data) {
            // Note that this clears the loading spinner.
            $('#experience-div').html(data);
          }
        );
      }
    } else if (indiciaData.detailsTabs[indiciaFns.activeTab($('#record-details-tabs'))] === 'phenology') {
      // Make it clear we are loading.
      if ($('#chart-div .loading-spinner').length === 0) {
        $('#chart-div').append('<div class="loading-spinner"><div>Loading...</div></div>');
      }
      $.getJSON(
        indiciaData.ajaxUrl + '/phenology/' + indiciaData.nid + urlSep +
        'external_key=' + currRec.extra.taxon_external_key +
        '&taxon_meaning_id=' + currRec.extra.taxon_meaning_id,
        null,
        function (data) {
          $('#chart-div').empty();
          $.jqplot('chart-div', [data], {
            seriesDefaults: { renderer: $.jqplot.LineRenderer, rendererOptions: [] },
            legend: [],
            series: [],
            axes: {
              xaxis: {
                label: indiciaData.str_month,
                showLabel: true,
                renderer: $.jqplot.CategoryAxisRenderer,
                ticks: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12']
              },
              yaxis: { 'min': 0 }
            }
          });
        }
      );
    } else if (indiciaData.detailsTabs[indiciaFns.activeTab($('#record-details-tabs'))] === 'media') {
      if ($('#media-tab .loading-spinner').length === 0) {
        $('#media-tab').append('<div class="loading-spinner"><div>Loading...</div></div>');
      }
      $.get(
        indiciaData.ajaxUrl + '/media/' + indiciaData.nid + urlSep +
        'occurrence_id=' + occurrenceId + '&sample_id=' + currRec.extra.sample_id,
        null,
        function (data) {
          $('#media-tab').html(data);
          $('#media-tab [data-fancybox]').fancybox({ afterLoad: indiciaFns.afterFancyboxLoad });
        }
      );
    }
    // Remember the tab is loaded so we don't load it twice.
    loadedTabs.push(indiciaData.detailsTabs[indiciaFns.activeTab($('#record-details-tabs'))]);
    // make it clear things are loading
    if (indiciaData.mapdiv !== null) {
      $(indiciaData.mapdiv).css('opacity', currRec.extra.wkt === null ? 0.1 : 1);
    }
  }

  function reloadGrid() {
    indiciaData.rowIdToReselect = occurrenceId;
    // Reload grid to remove row if not in your current verification set
    indiciaData.reports.verification.grid_verification_grid.reload(true);
  }

  function saveRedetComment() {
    var data;
    if ($('#redet').val() === '') {
      validator.showErrors({ 'redet:taxon': 'Please type a few characters then choose a name from the list of suggestions' });
    } else if (validator.numberOfInvalids() === 0) {
      data = {
        website_id: indiciaData.website_id,
        'occurrence:id': occurrenceId,
        'occurrence:taxa_taxon_list_id': $('#redet').val(),
        user_id: indiciaData.user_id
      };
      if ($('#on-behalf-of').is(':checked')) {
        data['occurrence:determiner_id'] = currRec.extra.created_by_person_id;
      } else if ($('#no-update-determiner').is(':checked')) {
        data['occurrence:determiner_id'] = -1;
      }
      if ($('#verify-comment').val()) {
        data['occurrence_comment:comment'] = $('#verify-comment').val();
      }
      if ($('#verify-reference').val()) {
        data['occurrence_comment:reference'] = $('#verify-reference').val();
      }
      $.fancybox.close();
      $.post(
        indiciaData.ajaxFormPostUrl.replace(/sharing=[a-z_]+/, 'sharing=editing'),
        data,
        function (response) {
          if (typeof response.error !== 'undefined') {
            alert(response.error);
          } else {
            // reload current tab
            if (indiciaData.detailsTabs[indiciaFns.activeTab($('#record-details-tabs'))] === 'details' ||
              indiciaData.detailsTabs[indiciaFns.activeTab($('#record-details-tabs'))] === 'comments') {
              $('#record-details-tabs').tabs('load', indiciaFns.activeTab($('#record-details-tabs')));
            }
            reloadGrid();
          }
        }
      );
      $('#add-comment').remove();
    }
    return false;
  }

  /**
   * Loads templates from the database for a status.
   *
   * @param string status
   *   Status code to load templates for.
   */
  function loadVerificationTemplates(status) {
    var getTemplatesReport;
    var getTemplatesReportParameters;
    getTemplatesReport = indiciaData.read.url + '/index.php/services/report/requestReport?report=library/verification_templates/verification_templates_for_a_taxon.xml&mode=json&mode=json&callback=?';
    getTemplatesReportParameters = {
      auth_token: indiciaData.read.auth_token,
      nonce: indiciaData.read.nonce,
      reportSource: 'local',
      taxon_meaning_id: currRec.extra.taxon_meaning_id,
      template_status: status,
      website_id: indiciaData.website_id
    };
    $.getJSON(
      getTemplatesReport,
      getTemplatesReportParameters,
      function (data) {
        if (data.length > 0) {
          $.each(data, function() {
            $('.verify-template').append('<option value="' + this.id + '">' + this.title + '</option>');
          });
          $('.verify-template').data('data', data);
        } else {
          $('.verify-template-container').hide();
        }
      }
    );
    $('.verify-template').on('change', function () {
      var templateID = $('.verify-template').val();
      var data = $('.verify-template').data('data');
      // The currRec is populated from the details report reports_for_prebuilt_forms/verification_5/record_data
      var conversions = {
        date: currRec.extra.date,
        'entered sref': currRec.extra.entered_sref,
        species: currRec.extra.taxon,
        'common name': [currRec.extra.default_common_name, currRec.extra.preferred_taxon, currRec.extra.taxon],
        'preferred name': [currRec.extra.preferred_taxon, currRec.extra.taxon],
        action: {
          V: indiciaData.popupTranslations.V,
          V1: indiciaData.popupTranslations.V1,
          V2: indiciaData.popupTranslations.V2,
          C3: indiciaData.popupTranslations.C3,
          R: indiciaData.popupTranslations.R,
          R4: indiciaData.popupTranslations.R4,
          R5: indiciaData.popupTranslations.R5,
          DT: indiciaData.popupTranslations.DT
        }[status],
        'location name': [currRec.extra.location_name, currRec.extra.entered_sref]
      };
      $.each(data, function eachData() {
        if (this.id === templateID) {
          $('.templatable-comment').val(indiciaFns.applyVerificationTemplateSubsitutions(this.template, conversions));
        }
      });
    });
  }

  function showRedeterminationPopup() {
    // No confidential checkbox for redeterminations.
    var html = '<form id="redet-form"><fieldset class="popup-form">' +
      '<legend>' + indiciaData.popupTranslations.redetermine + '</legend>';
    html += '<div id="redet-dropdown-popup-ctnr"></div>';
    html += '<label class="auto" for="update-redeterminer">Set your name as the determiner of this record:</label><input type="radio" name="redet-mode" id="set-name-as-determiner" checked="checked" />';
    html += '<label class="auto" for="on-behalf-of">Redetermine on behalf of original recorder:</label><input type="radio" name="redet-mode" id="on-behalf-of"/>';
    html += '<p class="helpText">If you are changing the record determination on behalf of the original recorder and their name should be ' +
      'stored against the determination, please tick this box.</p>';
    html += '<label class="auto" for="no-update-determiner">Don\'t update the determiner:</label><input type="radio" name="redet-mode" id="no-update-determiner"/>';
    html += '<p class="helpText">Tick this box to leave the current determiner name stored against the determination as it is.</p>';
    html += '<div class="verify-template-container"> ' +
    '<label>' + indiciaData.popupTranslations.templateLabel + ' : </label>' +
    '<select class="verify-template" >' +
    '<option value="">' + indiciaData.popupTranslations.pleaseSelect + '</option></select></div>';
    html += '<label for="verify-comment">Comment:</label><textarea id="verify-comment" class="templatable-comment" rows="5" cols="80"></textarea><br />' +
      '<input type="submit" class="default-button" value="' +
      indiciaData.popupTranslations.redetermine + '" />' +
      '</fieldset></form>';
    $('#redet\\:taxon').setExtraParams({"taxon_list_id": currRec.extra.taxon_list_id});
    $.fancybox.open(html, {
      "beforeClose": function () {
        // hide the species dropdown if left in open state
        $('.ac_results').hide();
        $('#redet-dropdown').appendTo($('#redet-dropdown-ctnr'));
      }
    });
    loadVerificationTemplates('DT');
    // move taxon input box onto the form
    $('#redet').val('');
    $('#redet\\:taxon').val('');
    $('#redet-dropdown').appendTo($('#redet-dropdown-popup-ctnr'));
    // Hide the full list checkbox if same as current record or full list not known
    if (typeof indiciaData.mainTaxonListId === 'undefined' || parseInt(currRec.extra.taxon_list_id) === indiciaData.mainTaxonListId) {
      $('.redet-partial-list').hide();
    } else {
      $('.redet-partial-list').show();
      $('#redet-from-full-list').removeAttr('checked');
    }
    validator = $('#redet-form').validate({});
    $('#redet-form').submit(saveRedetComment);
  }

  function setStatus(status, substatus) {
    var helpText = '';
    var html;
    var verb = status === 'C' ? indiciaData.popupTranslations.verbC3 : indiciaData.popupTranslations['verb' + status];
    if (typeof substatus === 'undefined') {
      substatus = '';
    }
    if (multimode && $('.check-row:checked').length > 1) {
      helpText = '<p class="warning">' + indiciaData.popupTranslations.multipleWarning + '</p>';
    }
    html = '<fieldset class="popup-form status-form">' +
      '<legend><span class="icon status-' + status + substatus + '"></span>' +
      indiciaData.popupTranslations.title.replace('{1}', '<strong>' + statusLabel(status, substatus)) + '</strong></legend>';
    html += '<div class="verify-template-container"> ' +
      '<label class="auto">' + indiciaData.popupTranslations.templateLabel + ' : </label>' +
      '<select class="verify-template" >' +
      '<option value="">' + indiciaData.popupTranslations.pleaseSelect + '</option></select></div>';

    html += '<label class="auto">' + indiciaData.popupTranslations.commentLabel + ':</label>' +
      '<textarea id="verify-comment" class="templatable-comment" rows="5" cols="80"></textarea><br />';

    html += '<label class="auto">' + indiciaData.popupTranslations.referenceLabel + ':</label>' +
      '<input type="text" id="verify-reference" value=""><br />' +
      helpText +
      '<input type="hidden" id="set-status" value="' + status + '"/>' +
      '<input type="hidden" id="set-substatus" value="' + substatus + '"/>' +
      '<button type="button" class="default-button" onclick="indiciaFns.saveVerifyComment();">' +
      indiciaData.popupTranslations.save.replace('{1}', verb) + '</button>' +
      '</fieldset>';

    $.fancybox.open(html);
    if (multimode) {
      // Doing multiple records, so can't use templates
      $('.verify-template-container').hide();
    } else {
      loadVerificationTemplates(status + substatus);
    }
  }

  /**
   * Mouse over map displays a layers button hint.
   */
  function onMouseOverMap() {
    var myTooltip;
    var layersButton = $('.olControlLayerSwitcher .maximizeDiv.olButton');
    var btnRect = layersButton[0].getBoundingClientRect();
    var tooltipRect;
    var leftPos;
    var topPos;
    $('body').append('<div class="ui-tip below-left" id="tip-layers-button"><p>Click the blue + button to show layers</p></div>');
    myTooltip = $('#tip-layers-button');
    // Position the tip.
    if (myTooltip.width() > 300) {
      myTooltip.css({ width: '300px' });
    }
    tooltipRect = myTooltip[0].getBoundingClientRect();
    leftPos = Math.min(btnRect.left, $(window).width() - tooltipRect.width - 10);
    topPos = btnRect.bottom + 8;
    if (topPos + tooltipRect.height > $(window).height()) {
      topPos = btnRect.top - (tooltipRect.height + 4);
    }
    topPos += $(window).scrollTop();
    // Fade the tip in and out.
    myTooltip.css({
      display: 'none',
      left: leftPos,
      top: topPos
    }).fadeIn(400, function () {
      $(this).delay(2000).fadeOut('slow');
    });
    // Only do this once.
    indiciaData.mapdiv.map.events.unregister('mouseover', indiciaData.mapdiv.map, onMouseOverMap);
  }

  mapInitialisationHooks.push(function initMap(div) {
    var defaultStyle = new OpenLayers.Style({
      fillColor: '#ff0000',
      strokeColor: '#ff0000',
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
          if (typeof feature.attributes.sref_precision === 'undefined') {
            return 5;
          }
          units = feature.attributes.sref_precision || 20;
          if (feature.geometry.getCentroid().y > 4000000) {
            units *= (feature.geometry.getCentroid().y / 8200000);
          }
          return Math.max(5, units / (feature.layer.map.getResolution()));
        }
      }
    });
    div.map.editLayer.style = null;
    div.map.editLayer.styleMap = new OpenLayers.StyleMap(defaultStyle);
    showTab();
    div.map.events.register('mouseover', div.map, onMouseOverMap);
  });

  function verifyRecordSet(trusted) {
    var request;
    var params = indiciaData.reports.verification.grid_verification_grid.getUrlParamsForAllRecords();
    var substatus = $('#process-grid-substatus').length ? '&record_substatus=' + $('#process-grid-substatus').val() : '';
    var ignoreRules = $('.grid-verify-popup input[name=ignore-checks-trusted]:checked').length > 0 ? 'true' : 'false';
    // If doing trusted only, this through as a report parameter.
    if (trusted) {
      params.quality_context = 'T';
    }
    request = indiciaData.ajaxUrl + '/bulk_verify/' + indiciaData.nid;
    $.post(request,
      'report=' + encodeURIComponent(indiciaData.reports.verification.grid_verification_grid[0].settings.dataSource) +
      '&params=' + encodeURIComponent(JSON.stringify(params)) +
      '&user_id=' + indiciaData.user_id + '&ignore=' + ignoreRules + substatus +
      '&dryrun=true',
      function (proposedChanges) {
        if (confirm(proposedChanges + ' records will be affected. Are you sure you want to proceed?')) {
          $.post(request,
            'report=' + encodeURIComponent(indiciaData.reports.verification.grid_verification_grid[0].settings.dataSource) +
            '&params=' + encodeURIComponent(JSON.stringify(params)) +
            '&user_id=' + indiciaData.user_id + '&ignore=' + ignoreRules + substatus,
            function (affected) {
              indiciaData.reports.verification.grid_verification_grid.reload(true);
              alert(affected + ' records processed');
            }
          );
        }
      }
    );
    $.fancybox.close();
  }

  // There are 2 related controls, a "created by" and "verification records only"
  indiciaFns.applyCreatedByFilterToReports = function (doReload, elem) {
    var filterDef;
    var reload = (typeof doReload === 'undefined') ? true : doReload;
    var val;

    filterDef = $.extend({}, indiciaData.filter.def);

    if(elem === false)
      val = $('.radio-log-created-by input:checked').val();
    else if($(elem).filter(':checked').length == 0)
      return;
    else
      val = $(elem).val();

    if (indiciaData.reports) {
      // apply the filter to any reports on the page
      $.each(indiciaData.reports, function (i, group) {
        $.each(group, function () {
          var grid = this[0];
          // Only apply to the Log grid
          if (grid.id != 'comments-log')
            return;
          // reset to first page
          grid.settings.offset = 0;
          if(typeof grid.settings.fixedParams == 'undefined') {
            grid.settings.fixedParams = {};
          }
          grid.settings.extraParams.created_by_filter = val;
          grid.settings.fixedParams.created_by_filter = val;
          grid.settings.extraParams.user_id = indiciaData.user_id;
          grid.settings.fixedParams.user_id = indiciaData.user_id;
          if (reload) {
            // reload the report grid (but only if not already done)
            this.ajaxload();
          }
        });
      });
      if (typeof indiciaData.mapdiv !== 'undefined') {
        indiciaData.mapReportControllerGrid.mapRecords();
      }
    }
  };

  // There are 2 related controls, a "created by" and "verification records only"
  indiciaFns.applyVerificationCommentsFilterToReports = function (doReload, elem) {
    var filterDef;
    var reload = (typeof doReload === 'undefined') ? true : doReload;
    var val;

    filterDef = $.extend({}, indiciaData.filter.def);

    if(elem === false)
      val = $('input.checkbox-log-verification-comments:checked').length ? 't' : 'f';
    else
      val = $(elem).filter(':checked').length ? 't' : 'f';

    if (indiciaData.reports) {
      // apply the filter to any reports on the page
      $.each(indiciaData.reports, function (i, group) {
        $.each(group, function () {
          var grid = this[0];
          // Only apply to the Log grid
          if (grid.id != 'comments-log')
            return;
          // reset to first page
          grid.settings.offset = 0;
          if(typeof grid.settings.fixedParams == 'undefined') {
            grid.settings.fixedParams = {};
          }
          grid.settings.extraParams.verification_only_filter = val;
          grid.settings.fixedParams.verification_only_filter = val;
          if (reload) {
            // reload the report grid (but only if not already done)
            this.ajaxload();
          }
        });
      });
      if (typeof indiciaData.mapdiv !== 'undefined') {
        indiciaData.mapReportControllerGrid.mapRecords();
      }
    }
  };

  $(document).ready(function () {
    // Use jQuery to add button to the top of the verification page. Use the first button to access the popup
    // which allows you to verify all trusted records or all records. The second enabled multiple record verification checkboxes
    var verifyGridButtons = '<button type="button" class="default-button verify-grid-trusted tools-btn" id="verify-grid-trusted">Review grid</button>' +
        '<button type="button" id="btn-multiple" title="Select this tool to tick off a list of records and action all of the ticked records in one go">Review tick list</button>',
      trustedHtml;
    $('#verification-grid').height($(document).height() - $('#verification-grid').offset().top - 50);
    $('#filter-build').after(verifyGridButtons);
    $('#verify-grid-trusted').on('click', function () {
      var settings = indiciaData.reports.verification.grid_verification_grid[0].settings;
      var show;
      trustedHtml = '<div class="grid-verify-popup" style="width: 550px"><h2>Review all grid data</h2>' +
        '<p>This facility allows you to set the status of entire sets of records in one step. Before using this ' +
        'facility, you should filter the grid so that only the records you want to process are listed. ' +
        'You can then choose to either process the entire set of records from <em>all pages of the grid</em> ' +
        'or you can process only those records where the recorder is trusted based on the record\'s ' +
        'survey, taxon group and location. Before using this tool to limit to trusted recorders, set up the recorders ' +
        'you wish to trust using the ... button next to each record.</p>';
      trustedHtml += '<p>The records will only be accepted if they have been through automated checks without any rule violations. If you <em>really</em>' +
        ' trust the records are correct then you can verify them even if they fail some checks by ticking the following box.</p>' +
        '<label class="auto"><input type="checkbox" name="ignore-checks-trusted" /> Include records which fail automated checks?</label><br/>';
      if (settings.recordCount > settings.itemsPerPage) {
        trustedHtml += '<p class="warning">Remember that the following buttons will verify records from every page in the grid up to a maximum of ' +
          settings.recordCount + ' records, not just the current page.</p>';
      }
      if ($('#actions-more').is(':visible')) {
        trustedHtml += '<div><label class="auto">Accepted records will be flagged as:<select id="process-grid-substatus">' +
          '<option selected="selected" value="2">considered correct</option><option value="1">correct</option></select></label></div>';
      }
      trustedHtml += '<button type="button" class="default-button" id="verify-trusted-button">Accept trusted records</button>';
      trustedHtml += '<button type="button" class="default-button" id="verify-all-button">Accept all records</button></div>';

      $.fancybox.open(trustedHtml);
      $('#verify-trusted-button').on('click', function () {
        verifyRecordSet(true);
      });
      $('#verify-all-button').on('click', function () {
        verifyRecordSet(false);
      });
    });

    $('#verification-grid').find('tbody').dblclick(function () {
      var extent;
      var zoom;
      $.each(indiciaData.mapdiv.map.editLayer.features, function() {
        if (this.attributes.type === 'selectedrecord') {
          extent = this.geometry.getBounds();
          zoom = Math.min(
            indiciaData.reportlayer.map.getZoomForExtent(extent) - 1, indiciaData.mapdiv.settings.maxZoom);
          indiciaData.reportlayer.map.setCenter(extent.getCenterLonLat(), zoom);
        }
      });
    });

    function quickVerifyPopup() {
      var popupHtml;
      popupHtml = '<div class="quick-verify-popup" style="width: 550px"><h2>Quick verification</h2>' +
        '<p>The following options let you rapidly verify records. The only records affected are those in the grid but they can be on any page of the grid, ' +
        'so please ensure you have set the grid\'s filter correctly before proceeding. You should only proceed if you are certain that data you are verifying ' +
        'can be trusted without further investigation.</p>' +
        '<label><input type="radio" name="quick-option" value="species" /> Verify grid\'s records of <span class="quick-taxon">' + currRec.extra.taxon + '</span></label><br/>';
      // at this point, we need to know who the created_by_id recorder name is. And if it matches extra.recorder, otherwise this record may have been input by proxy
      if (currRec.extra.recorder !== '' && currRec.extra.input_by_surname !== null && currRec.extra.created_by_id !== '1'
        && (currRec.extra.recorder === currRec.extra.input_by_first_name + ' ' + currRec.extra.input_by_surname
        || currRec.extra.recorder === currRec.extra.input_by_surname + ', ' + currRec.extra.input_by_first_name)) {
        popupHtml += '<label><input type="radio" name="quick-option" value="recorder"/> Verify grid\'s records by <span class="quick-user">' + currRec.extra.recorder + '</span></label><br/>' +
          '<label><input type="radio" name="quick-option" value="species-recorder" /> Verify grid\'s records of <span class="quick-taxon">' + currRec.extra.taxon +
          '</span> by <span class="quick-user">' + currRec.extra.recorder + '</span></label><br/>';
      } else if (currRec.extra.recorder !== '' && currRec.extra.recorder !== null && currRec.extra.input_by_surname !== null && currRec.extra.created_by_id !== '1') {
        popupHtml += '<p class="helpText">Because the recorder, ' + currRec.extra.recorder + ', is not linked to a logged in user, quick verification tools cannot filter to records by this recorder.</p>';
      }
      popupHtml += '<label><input type="checkbox" name="ignore-checks" /> Include failures?</label><p class="helpText">The records will only be accepted if they do not fail ' +
        'any automated verification checks. If you <em>really</em> trust the records are correct then you can verify them even if they fail some checks by ticking this box.</p>';
      if ($('#actions-more').is(':visible')) {
        // Enable level 2 options.
        popupHtml += '<label>Choose verification status: <select id="bulk-substatus">' +
          '<option value="">Accepted</option>' +
          '<option value="1">Accepted as correct</option>' +
          '<option value="2">Accepted as considered correct</option>' +
          '</select></label><br/>';
      }
      popupHtml += '<button type="button" class="default-button verify-button">Verify chosen records</button>' +
        '<button type="button" class="default-button cancel-button">Cancel</button></p></div>';
      $.fancybox.open(popupHtml);
      $('.quick-verify-popup .verify-button').on('click', function () {
        var params = indiciaData.reports.verification.grid_verification_grid.getUrlParamsForAllRecords();
        var radio = $('.quick-verify-popup input[name=quick-option]:checked');
        var request;
        var ignoreParams;
        var substatus = $('#actions-more').is(':visible') && $('#bulk-substatus option:selected').val() !== ''
          ? '&record_substatus=' + $('#bulk-substatus option:selected').val() : '';
        if (radio.length === 1) {
          if ($(radio).val().indexOf('recorder') !== -1) {
            params.created_by_id = currRec.extra.created_by_id;
          }
          if ($(radio).val().indexOf('species') !== -1) {
            params.taxon_meaning_list = currRec.extra.taxon_meaning_id;
          }
          // We now have parameters that can be applied to a report and we know the report, so we can ask the warehouse
          // to verify the occurrences provided by the report that match the filter.
          request = indiciaData.ajaxUrl + '/bulk_verify/' + indiciaData.nid;
          ignoreParams = $('.quick-verify-popup input[name=ignore-checks]:checked').length > 0 ? 'true' : 'false';
          $.post(request,
            'report=' + encodeURI(indiciaData.reports.verification.grid_verification_grid[0].settings.dataSource) + '&params=' + encodeURI(JSON.stringify(params)) +
            '&user_id=' + indiciaData.user_id + '&ignore=' + ignoreParams + substatus +
            '&dryrun=true',
            function (proposedChanges) {
              if (confirm(proposedChanges + ' records will be affected. Are you sure you want to proceed?')) {
                $.post(request,
                  'report=' + encodeURI(indiciaData.reports.verification.grid_verification_grid[0].settings.dataSource) + '&params=' + encodeURI(JSON.stringify(params)) +
                  '&user_id=' + indiciaData.user_id + '&ignore=' + ignoreParams + substatus,
                  function (affected) {
                    indiciaData.reports.verification.grid_verification_grid.reload();
                    alert(affected + ' records processed');
                  }
                );
              }
            }
          );
          $.fancybox.close();
        }
      });
      $('.quick-verify-popup .cancel-button').on('click', function () {
        $.fancybox.close();
      });
    }

    function trustsPopup() {
      var popupHtml;
      var surveyRadio;
      var taxonGroupRadio;
      var locationInput;
      var i;
      var theDataToRemove;
      popupHtml = '<div class="quick-verify-popup" style="width: 550px"><h2>Recorder\'s trust settings</h2>';
      popupHtml += '<p>Recorders can be trusted for records from a selected region, species group or survey combination. When they add records which meet the criteria ' +
        'that the recorder is trusted for the records will not be automatically accepted. However, you can filter the grid to show only "trusted" records and use the ... button at the top ' +
        'of the grid to accept all these records in bulk. If you want to trust records from <em>' + currRec.extra.recorder + '</em> in future, you can use the following options to select the ' +
        'criteria for when their records are to be treated as trusted.</p>';
      // Call a function to draw all the existing trusts for a record.
      drawExistingTrusts();
      // The html containing the trusts is later placed into this div using innerHtml
      popupHtml += '<div id="existingTrusts"></div>';
      popupHtml += '<h3>Add new trust criteria</h3>';
      if (indiciaData.expertise_surveys) {
        popupHtml += '<label>Trust will be applied to records from survey "' + currRec.extra.survey_title + '"</label><br/>';
      } else {
        popupHtml += '<label>Trust will only be applied to records from survey:</label>' +
          '<label><input type="radio" name="trust-survey" value="all"> All </label>' +
          '<label><input type="radio" name="trust-survey" value="specific" checked>' + ' ' + currRec.extra.survey_title + '</label><br/>';
      }
      if (indiciaData.expertise_taxon_groups) {
        popupHtml += '<label>Trust will be applied to records from species group "' + currRec.extra.taxon_group + '</label><br/>';
      } else {
        popupHtml += '<label>Trust will only be applied to records from species group:</label>' +
          '<label><input type="radio" name="trust-taxon-group" value="all"> All </label>' +
          '<label><input type="radio" name="trust-taxon-group" value="specific" checked>' + ' ' + currRec.extra.taxon_group + '</label><br/>';
      }
      if (indiciaData.expertise_location) {
        // verifier can only verify in a locality
        popupHtml += '<label>Trust will be applied to records from your verification area.</label><br/>'; // @todo VERIFIERs LOCALITY NAME
        popupHtml += '<input type="hidden" name="trust-location" value="' + indiciaData.expertise_location + '" />';
      } else {
        // verifier can verify anywhere
        if (currRec.extra.locality_ids !== '') {
          popupHtml += '<label>Trust will be applied to records from locality:</label>' +
            '<label><input type="radio" name="trust-location" value="all"> All </label>';
          // the record could intersect multiple locality boundaries. So can choose which...
          var locationIds = currRec.extra.locality_ids.split('|');
          var locations = currRec.extra.localities.split('|');
          // can choose to trust all localities or record's location
          $.each(locationIds, function (idx, id) {
            popupHtml += '<label><input type="radio" name="trust-location" value="' + id + '" checked> ' + locations[idx] + '</label><br/>';
          });
        } else {
          popupHtml += '<label>Trust will be applied to records from any locality.</label>';
          popupHtml += '<input type="hidden" name="trust-location" value="all" /><br/>';
        }
      }
      popupHtml += '<button type="button" id="trust-button" class="default-button trust-button">Set trust for ' + currRec.extra.recorder + '</button>' + "</div>\n";
      $.fancybox.open(popupHtml);
      $('.quick-verify-popup .trust-button').on('click', function () {
        var theData = {
          website_id: indiciaData.website_id,
          'user_trust:user_id': currRec.extra.created_by_id,
          'user_trust:deleted': false
        };
        document.getElementById('trust-button').innerHTML = 'Please Wait……';
        // As soon as the Trust button is clicked we disable it so that the user can't keep clicking it.
        $('.trust-button').attr('disabled', 'disabled');
        // Get the user's trust settings to put in the database
        surveyRadio = $('.quick-verify-popup input[name=trust-survey]:checked');
        if (!surveyRadio.length || $(surveyRadio).val().indexOf('specific') !== -1) {
          theData['user_trust:survey_id'] = currRec.extra.survey_id;
        }
        taxonGroupRadio = $('.quick-verify-popup input[name=trust-taxon-group]:checked');
        if (!taxonGroupRadio.length || $(taxonGroupRadio).val().indexOf('specific') !== -1) {
          theData['user_trust:taxon_group_id'] = currRec.extra.taxon_group_id;
        }
        locationInput = $('.quick-verify-popup input[name=trust-location]:checked, .quick-verify-popup input[name=trust-location][type=hidden]');
        if ($(locationInput).val() !== 'all') {
          theData['user_trust:location_id'] = $(locationInput).val();
        }
        if (!theData['user_trust:survey_id'] && !theData['user_trust:taxon_group_id'] && !theData['user_trust:location_id']) {
          alert('Please review the trust settings as unlimited trust is not allowed');
          // The attempt to create the trust is over at this point.
          // We re-enable the Trust button.
          $('.trust-button').removeAttr('disabled');
          document.getElementById('trust-button').innerHTML = 'Trust';
        } else {
          var downgradeConfirmRequired = false,
            downgradeConfirmed = false,
            duplicateDetected = false,
            trustNeedsRemoval = [],
            getTrustsReport = indiciaData.read.url + '/index.php/services/report/requestReport?report=library/user_trusts/get_user_trust_for_record.xml&mode=json&mode=json&callback=?',
            getTrustsReportParameters = {
              user_id: currRec.extra.created_by_id,
              survey_id: currRec.extra.survey_id,
              taxon_group_id: currRec.extra.taxon_group_id,
              location_ids: currRec.extra.locality_ids,
              auth_token: indiciaData.read.auth_token,
              nonce: indiciaData.read.nonce,
              reportSource: 'local'
            };
          // Collect the existing trust data associated with the record so we can compare the new trust data with it
          $.getJSON(
            getTrustsReport,
            getTrustsReportParameters,
            function (data) {
              var downgradeDetect = 0;
              var upgradeDetect = 0;
              var trustNeedsRemovalIndex = 0;
              var trustNeedsDowngradeIndex = 0;
              var trustNeedsDowngrade = [];
              // Cycle through the existing trust data we need for the record
              for (i = 0; i < data.length; i++) {
                // If the new selections match an existing record then we flag it as a duplicate not be be added
                if (theData['user_trust:survey_id'] === data[i].survey_id &&
                  theData['user_trust:taxon_group_id'] === data[i].taxon_group_id &&
                  theData['user_trust:location_id'] === data[i].location_id &&
                  currRec.extra.created_by_id === data[i].user_id) {
                  duplicateDetected = true;
                }
                // If any of the 3 trust items the user has entered are smaller than the existing trust item we are looking at,
                // then we flag it as the existing row needs to be at least partially downgraded
                if ((theData['user_trust:survey_id'] && !data[i].survey_id) ||
                  (theData['user_trust:taxon_group_id'] && !data[i].taxon_group_id) ||
                  (theData['user_trust:location_id'] && !data[i].location_id)) {
                  downgradeDetect++;
                }
                // If any of the 3 trust items the user has entered are bigger than the existing trust item we are
                // looking at, then we flag it as the existing row needs to be at least partially upgraded
                if ((!theData['user_trust:survey_id'] && data[i].survey_id) ||
                  (!theData['user_trust:taxon_group_id'] && data[i].taxon_group_id) ||
                  (!theData['user_trust:location_id'] && data[i].location_id)) {
                  upgradeDetect++;
                }
                // If we have detected that there are more items to be downgraded than upgraded for an existing trust
                // then we flag it. We can then warn the user about the downgrade and remove the existing trust, e.g.
                // This means if we have a trust which is just a trust for location Dorset and the user upgrades the
                // location trust setting to "All" but downgrades the taxon_group trust from "All" to insects, then
                // although a downgrade has been performed it is actually a completely seperate trust. In this case we
                // don't want to warn the user or remove the existing trust. DowngradeDetect and upgradeDetect are both
                // 1 so the following code wouldn't run.
                if (downgradeDetect > upgradeDetect) {
                  // Save the existing trust data to be downgraded for processing
                  trustNeedsDowngrade[trustNeedsDowngradeIndex] = data[i].trust_id;
                  trustNeedsRemoval[trustNeedsRemovalIndex] = data[i].trust_id;
                  trustNeedsDowngradeIndex++;
                  trustNeedsRemovalIndex++;
                }
                // Same logic as above but we are working out which existing trusts are being upgraded.
                // The difference is that we don't warn the user about upgrades.
                if (upgradeDetect > downgradeDetect) {
                  trustNeedsRemoval[trustNeedsRemovalIndex] = data[i].trust_id;
                  trustNeedsRemovalIndex++;
                }
                downgradeDetect = 0;
                upgradeDetect = 0;
              }

              if (duplicateDetected === true) {
                alert('Your selected trust settings already exist in the database');
                $('.trust-button').removeAttr('disabled');
                document.getElementById('trust-button').innerHTML = 'Trust';
              }

              if (trustNeedsDowngrade.length !== 0 && duplicateDetected === false) {
                downgradeConfirmRequired = true;
                downgradeConfirmed = confirm('Your new trust settings will result in the existing trust rights for ' +
                  'this recorder being lowered.\n Are you sure you wish to continue?');
                // Re-enable the Trust button if the user chooses the Cancel option.
                if (downgradeConfirmed === false) {
                  $('.trust-button').removeAttr('disabled');
                  document.getElementById('trust-button').innerHTML = 'Trust';
                }
              }
              // We are going to proceed if the user has clicked ok on the downgrade confirmation message or
              // if the message was never displayed.
              if (duplicateDetected === false && (downgradeConfirmRequired === false || downgradeConfirmed === true)) {
                // Go through each trust item we need to remove from the database and do the removal
                var handlePostResponse = function (postResponse) {
                  if (typeof postResponse.error !== 'undefined') {
                    alert(postResponse.error);
                  }
                };
                for (i = 0; i < trustNeedsRemovalIndex; i++) {
                  theDataToRemove = {
                    website_id: indiciaData.website_id,
                    'user_trust:id': trustNeedsRemoval[i],
                    'user_trust:deleted': true
                  };
                  $.post(
                    indiciaData.ajaxFormPostUrl.replace('occurrence', 'user-trust'),
                    theDataToRemove,
                    handlePostResponse
                  );
                }
              }
              // Now add the new trust settings
              if (duplicateDetected === false && (downgradeConfirmRequired === false || downgradeConfirmed === true)) {
                $.post(
                  indiciaData.ajaxFormPostUrl.replace('occurrence', 'user-trust'),
                  theData,
                  function (trustResponse) {
                    if (typeof trustResponse.error === 'undefined') {
                      drawExistingTrusts();
                      alert('Trust settings successfully applied to the recorder');
                      $('.trust-button').removeAttr('disabled');
                      document.getElementById('trust-button').innerHTML = 'Trust';
                    } else {
                      alert(trustResponse.error);
                      $('.trust-button').removeAttr('disabled');
                      document.getElementById('trust-button').innerHTML = 'Trust';
                    }
                  },
                  'json'
                );
              }
            }
          );
        }
      });
    }

    function quickVerifyMenu(row) {
      // can't use User Trusts if the recorder is not linked to a warehouse user.
      if (typeof currRec !== 'undefined' && currRec !== null) {
        if (currRec.extra.created_by_id === '1') {
          $('.trust-tool').closest('li').hide();
        } else {
          $('.trust-tool').closest('li').show();
        }
        if ($(row).find('.row-input-form-raw').val() !== '') {
          $(row).find('.verify-tools .edit-record').closest('li').show();
          $('#btn-edit-record').show();
        } else {
          $(row).find('.verify-tools .edit-record').closest('li').hide();
          $('#btn-edit-record').hide();
        }
        // show the menu
        $(row).find('.verify-tools').show();
        // remove titles from the grid and store in data, so they don't overlay the menu
        $.each($(row).parents('table:first tbody').find('[title]'), function (idx, ctrl) {
          $(this).data('title', $(ctrl).attr('title')).removeAttr('title');
        });
      }
    }

    $('table.report-grid tbody').on('click', function (evt) {
      var row = $(evt.target).parents('tr:first')[0];
      $('.verify-tools').hide();
      // reinstate tooltips
      $.each($(row).parents('table:first tbody').find(':data(title)'), function (idx, ctrl) {
        $(ctrl).attr('title', $(this).data('title'));
      });
      // Find the appropriate separator for AJAX url params - depends on clean urls setting.
      urlSep = indiciaData.ajaxUrl.indexOf('?') === -1 ? '?' : '&';
      if ($(evt.target).hasClass('quick-verify')) {
        selectRow(row, quickVerifyMenu);
      } else if ($(evt.target).hasClass('quick-verify-tool')) {
        quickVerifyPopup(row);
      } else if ($(evt.target).hasClass('trust-tool')) {
        trustsPopup(row);
      } else if ($(evt.target).hasClass('edit-record')) {
        editThisRecord($(row).find('.record-id').html());
      } else {
        selectRow(row);
      }
    });

    indiciaFns.bindTabsActivate($('#record-details-tabs'), showTab);

    function showSetStatusButtons(showMore) {
      if (showMore) {
        $('#actions-less').hide();
        $('#actions-more').show();
        $('#more-status-buttons').html('[' + indiciaData.langLess + ']');
      } else {
        $('#actions-more').hide();
        $('#actions-less').show();
        $('#more-status-buttons').html('[' + indiciaData.langMore + ']');
      }
      indiciaFns.cookie('verification-status-buttons', showMore ? 'more' : 'less');
    }

    function logResponse() {
      if (validator.numberOfInvalids() === 0) {
        email.to = $('#email-to').val();
        email.from = $('#email-from').val();
        email.subject = $('#email-subject').val();
        email.body = $('#email-body').val();

        indiciaFns.saveComment(
          $('#log-response-comment').val(), null, $('#log-response-confidential:checked').length, email, 'f', false
        );
      }
      return false;
    }

    function popupLogResponse() {
      var workflow = (indiciaData.workflowEnabled &&
              indiciaData.workflowTaxonMeaningIDsLogAllComms.indexOf(currRec.extra.taxon_meaning_id) !== -1);
      var html = '<form id="log-response-form"><fieldset class="popup-form log-response ' +
        (workflow ? 'short' : '') + '">' +
        '<legend>' + indiciaData.popupTranslations.logResponseTitle + '</legend>';
      if (workflow) {
        html += '<label><input type="checkbox" id="log-response-confidential" /> ' + indiciaData.popupTranslations.confidential + '</label><br>' +
          '<textarea id="log-response-comment" rows="4"></textarea><br>' +
          '<fieldset class="">' +
          '<label>From:</label><input type="text" id="email-from" class="email required" value=""/><br />' +
          '<label>To:</label><input type="text" id="email-to" class="email required" value=""/><br />' +
          '<label>Subject:</label><input type="text" id="email-subject" class="required" value=""/><br />' +
          '<label>Body:</label><textarea id="email-body" class="required"></textarea><br />' +
          '</fieldset>';
      } else {
        html += '<input type="hidden" id="log-response-confidential" value="f"/>' +
          '<textarea id="log-response-comment" rows="4"></textarea><br>';
      }
      html += '<input type="submit" class="default-button" ' +
        'value="' + indiciaData.popupTranslations.logResponse + '" />' +
        '</fieldset></form>';
      $.fancybox.open(html);
      validator = $('#log-response-form').validate({});
      $('#log-response-form').submit(logResponse);
    }

    if (typeof $.cookie !== 'undefined') {
      if ($.cookie('verification-status-buttons') === 'less') {
        showSetStatusButtons(false);
      }
    }

    $('#more-status-buttons').on('click', function () {
      var showMore = $('#actions-less:visible').length;
      showSetStatusButtons(showMore);
    });

    // Handlers for basic status buttons
    $('#btn-accepted').on('click', function () {
      setStatus('V');
    });

    $('#btn-notaccepted').on('click', function () {
      setStatus('R');
    });

    // Handlers for advanced status buttons
    $('#btn-accepted-correct').on('click', function () {
      setStatus('V', 1);
    });

    $('#btn-accepted-considered-correct').on('click', function () {
      setStatus('V', 2);
    });

    $('#btn-plausible').on('click', function () {
      setStatus('C', 3);
    });

    $('#btn-notaccepted-unable').on('click', function () {
      setStatus('R', 4);
    });

    $('#btn-notaccepted-incorrect').on('click', function () {
      setStatus('R', 5);
    });

    $('#btn-multiple').on('click', function () {
      multimode = !multimode;
      if (multimode) {
        showTickList();
      } else {
        $('.check-row').hide();
        $('#btn-multiple').removeClass('active').html('Review tick list');
        $('#action-buttons-status label').html('Set status:');
        $('#action-buttons').prepend($('#action-buttons-status'));
        if (currRec === null) {
          $('#action-buttons-status button').attr('disabled', 'disabled');
        }
      }
    });

    $('#btn-query').on('click', function () {
      buildRecorderQueryMessage();
    });

    $('#btn-email-expert').on('click', function () {
      buildVerifierEmail();
    });

    $('#btn-redetermine').on('click', function () {
      showRedeterminationPopup();
    });

    $('#btn-log-response').on('click', function () {
      popupLogResponse();
    });

    function editThisRecord(id) {
      var $row = $('tr#row' + id);
      var path = $row.find('.row-input-form-link').val();
      var sep = (path.indexOf('?') >= 0) ? '&' : '?';
      window.location = path + sep + 'occurrence_id=' + id;
    }

    /**
     * On the redetermine popup, handle the switch to and from searching the full species lists for records which come from
     * custom species lists.
     */
    $('#redet-from-full-list').on('change', function () {
      if ($('#redet-from-full-list:checked').length) {
        $('#redet\\:taxon').setExtraParams({ taxon_list_id: indiciaData.mainTaxonListId });
      } else {
        $('#redet\\:taxon').setExtraParams({ taxon_list_id: currRec.extra.taxon_list_id });
      }
    });

    $('.radio-log-created-by input:radio').on('change', function () {
      indiciaFns.applyCreatedByFilterToReports(true, this);
    });

    indiciaFns.applyCreatedByFilterToReports(false, false);

    $('input.checkbox-log-verification-comments:checkbox').on('change', function () {
      indiciaFns.applyVerificationCommentsFilterToReports(true, this);
    });

    indiciaFns.applyVerificationCommentsFilterToReports(false, false);

    $('#details-zoom').on('click', function toggleZoom() {
      if ($('#outer-with-map').hasClass('details-zoomed')) {
        $('#outer-with-map').removeClass('details-zoomed');
        $('#details-zoom').html('&#8689;');
        $('#record-details-wrap').appendTo($('#map-and-record'));
      } else {
        $('#outer-with-map').addClass('details-zoomed');
        $('#details-zoom').html('&#8690;');
        $('#record-details-wrap').appendTo($('#outer-with-map'));
      }
    });
  });

  function removeTrust(RemoveTrustId) {
    var removeItem = {
      website_id: indiciaData.website_id,
      'user_trust:id': RemoveTrustId,
      'user_trust:deleted': true
    };

    $.post(
      indiciaData.ajaxFormPostUrl.replace('occurrence', 'user-trust'),
      removeItem,
      function (data) {
        if (typeof data.error !== 'undefined') {
          alert(data.error);
        } else {
          // If there are several trusts we remove a row
          // otherwise we remove the whole trust section
          if (trustsCounter > 1) {
            $('#trust-' + RemoveTrustId).hide();
          } else {
            $('.existingTrustSection').hide();
          }
          trustsCounter--;
        }
      }
    );
  }

  // Function to draw any existing trusts from the database
  function drawExistingTrusts() {
    var getTrustsReport = indiciaData.read.url + '/index.php/services/report/requestReport?report=library/user_trusts/get_user_trust_for_record.xml&mode=json&callback=?';
    var getTrustsReportParameters = {
      user_id: currRec.extra.created_by_id,
      survey_id: currRec.extra.survey_id,
      taxon_group_id: currRec.extra.taxon_group_id,
      location_ids: currRec.extra.locality_ids,
      auth_token: indiciaData.read.auth_token,
      nonce: indiciaData.read.nonce,
      reportSource: 'local'
    };
    var i;
    var idNum;
    // Variable holds our HTML
    var textMessage;
    $.getJSON(
      getTrustsReport,
      getTrustsReportParameters,
      function (data) {
        if (typeof data.error === 'undefined') {
          if (data.length > 0) {
            trustsCounter = data.length;
            textMessage = '<h3>Existing trust criteria</h3>';
            // If there is only one trust we put the information into a sentence, else we put it in a bullet list
            if (data.length === 1) {
              textMessage += '<div class="existingTrustSection existingTrustData">' + data[0].recorder_name + ' is trusted for the ';
            } else {
              textMessage += '<div class="existingTrustSection">This record is trusted as ' + data[0].recorder_name + ' has the following trust criteria:';
              textMessage += '<ul>';
            }
            // for each trust we build the HTML
            for (i = 0; i < data.length; i++) {
              if (data.length > 1) {
                textMessage += '<li class="existingTrustData" id="trust-' + data[i].trust_id + '">The ';
              }
              if (data[i].survey_title) {
                textMessage += '<strong>survey </strong><em>' + data[i].survey_title + '</em>, ';
              }
              if (data[i].taxon_group) {
                textMessage += '<strong> taxon group</strong><em> ' + data[i].taxon_group + '</em>, ';
              }
              if (data[i].location_name) {
                textMessage += '<strong> location</strong><em> ' + data[i].location_name + '</em>';
              }
              // Remove comma from end of trust information if there is a dangling comma because location info isn't
              // present
              if (!data[i].location_name) {
                textMessage = textMessage.substring(0, textMessage.length - 2);
              }
              textMessage += '<br/>&nbsp;&nbsp;- trust was setup by ' + data[i].trusted_by;
              textMessage += ' <a class="default-button existingTrustRemoveButton" id="deleteTrust-' +
                data[i].trust_id + '" >Remove</a><br/>';
              if (data.length > 1) {
                textMessage += '</li>';
              }
            }
            if (data.length > 1) {
              textMessage += '</ul>';
            }
            textMessage += '</div>';
            // Apply the HTML to the HTML tag
            document.getElementById('existingTrusts').innerHTML = textMessage;
            // Remove a trust if the user clicks the remove button
            $('.existingTrustRemoveButton').on('click', function (evt) {
              // We only want the number from the end of the id
              var idNumArray = evt.target.id.match(/\d+$/);

              if (idNumArray) {
                idNum = idNumArray[0];
              }
              removeTrust(idNum);
            });
          }
        } else {
          alert(data.error);
        }
      }
    );
  }

  indiciaFns.on('click', '.shrink-comment', {}, function () {
    $(this).closest('.comment-body').addClass('shrunk');
  });
  indiciaFns.on('click', '.unshrink-comment', {}, function () {
    $(this).closest('.comment-body').removeClass('shrunk');
  });
  indiciaFns.on('click', '.shrink-correspondence', {}, function () {
    $(this).closest('.correspondence').addClass('shrunk');
  });
  indiciaFns.on('click', '.unshrink-correspondence', {}, function () {
    $(this).closest('.correspondence').removeClass('shrunk');
  });
  indiciaFns.on('tabsload', '#record-details-tabs', function () {
    $('.comment-body').each(function () {
      if ($(this)[0].offsetHeight >= $(this)[0].scrollHeight) {
        $(this).find('.unshrink-comment,.shrink-comment').remove();
        $(this).removeClass('shrunk');
      }
    });
    $('.correspondence').each(function () {
      if ($(this)[0].offsetHeight >= $(this)[0].scrollHeight) {
        $(this).find('.unshrink-correspondence,.shrink-correspondence').remove();
        $(this).removeClass('shrunk');
      }
    });
  });

  /**
   * Save functionality for custom occurrence metadata fields.
   */
  indiciaFns.on('click', '#save-metadata', function saveMetadata() {
    var metadata = currRec.extra.metadata;
    var data;
    $('.metadata-msg').hide();

    $.each($('#metadata .metadata-field'), function getValue() {
      metadata[$(this).attr('data-title')] = $(this).val();
    });
    data = {
      website_id: indiciaData.website_id,
      sharing: 'editing',
      'occurrence:id': occurrenceId,
      'occurrence:metadata': JSON.stringify(metadata)
    };
    $.post(
      indiciaData.ajaxFormPostUrl,
      data,
      function handleResponse(response) {
        var json;
        try {
          json = JSON.parse(response);
          if (typeof json.success !== 'undefined') {
            $('.metadata-msg').html('saved');
            $('.metadata-msg').removeClass('changed');
            $('.metadata-msg').addClass('saved');
            $('.metadata-msg').show();
            return;
          }
        }
        catch (err) {
          // Do nothing.
        }
        alert('An error occurred when saving the value.');
      }
    );
  });

  indiciaFns.on('change', '#metadata :input', function updateMsg() {
    var msg = $(this).closest('label').find('.metadata-msg');
    $(msg).removeClass('saved');
    $(msg).addClass('changed');
    $(msg).html('changed');
    $(msg).show();
  });

})(jQuery);
