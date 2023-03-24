jQuery(document).ready(function($) {

  // Removes the current uploaded file.
  function clearExistingUploadedFileInfo() {
    $('#uploaded-files').html('');
    $('#uploaded-file').val('');
    $('#upload-rules').attr('disabled', true);
  }

  // Click handler for the remove file x button.
  indiciaFns.on('click', '#uploaded-files .remove-file', {}, function(e) {
    clearExistingUploadedFileInfo();
  });

  // Enable the file upload handler.
  $(indiciaData.importerDropArea).dmUploader({
    url: indiciaData.uploadFileUrl,
    multiple: false,
    extFilter: ['csv','xls','xlsx'],
    headers: {'Authorization': 'IndiciaTokens ' + indiciaData.write.auth_token + '|' + indiciaData.write.nonce},
    onDragEnter: function() {
      // Happens when dragging something over the DnD area
      this.addClass('active');
    },
    onDragLeave: function() {
      // Happens when dragging something OUT of the DnD area
      this.removeClass('active');
    },
    onInit: function() {
      this.find('input[type="text"]').val('');
    },
    onBeforeUpload: function() {
      clearExistingUploadedFileInfo();
    },
    onUploadProgress: function(id, percent) {
      // Don't show the progress bar if it goes to 100% in 1 chunk.
      if (percent !== 100) {
        // Updating file progress
        $('#file-progress').show();
        $('#file-progress').val(percent);
      }
    },
    onFileExtError() {
      $.fancyDialog({
        title: 'Upload error',
        message: indiciaData.lang.custom_verification_rules_upload.invalidType,
        cancelButton: null
      });
    },
    onUploadError(id, xhr, status, errorThrown) {
      $.fancyDialog({
        title: 'Upload error',
        message: indiciaData.lang.custom_verification_rules_upload.uploadFailedWithError.replace('{1}', errorThrown),
        cancelButton: null
      });
    },
    onUploadSuccess: function(id, data) {
      $('#file-progress').val(100);
      // IForm proxy code doesn't set header correctly.
      if (typeof data === 'string') {
        data = JSON.parse(data);
      }
      indiciaData.upload = {
        interimFile: data.interimFile
      };
      // Show an icon indicating the file has been uploaded.
      $('#uploaded-files').append($('<i class="far fa-file-alt fa-7x"></i>'));
      $('#uploaded-files').append($('<i class="far fa-trash-alt remove-file" title="' + indiciaData.lang.custom_verification_rules_upload.removeUploadedFileHint + '"></i>'));
      $('#uploaded-files').append($('<p>' + data.originalName + '</p>'));
      $('#upload-rules').removeAttr('disabled');
      // Hide any previous progress info as we are starting afresh.
      $('#progress-output-cntr').hide();
    }
  });

  /**
   * Perform the next step of the upload of a rules file.
   */
  function uploadNextStep() {
    const urlSep = indiciaData.uploadRulesStepUrl.indexOf('?') === -1 ? '?' : '&';
    let url = indiciaData.uploadRulesStepUrl;
    let data = {
      taxon_list_id: indiciaData.taxon_list_id,
      custom_verification_ruleset_id: indiciaData.custom_verification_ruleset_id
    };
    if (typeof indiciaData.upload.nextState === 'undefined') {
      // Startin from the first step.
      data.interimFile = indiciaData.upload.interimFile;
      $('<p>' + indiciaData.lang.custom_verification_rules_upload.uploadingFile + '</p>').appendTo($('#progress-output'));
    } else if (indiciaData.upload.nextState === 'done') {
      // Upload completed.
      $('<p><i class="fas fa-check"></i>' + indiciaData.lang.custom_verification_rules_upload.done + '</p>').appendTo($('#progress-output'));
      return;
    } else {
      data.uploadedFile = indiciaData.upload.uploadedFile;
      data.state = indiciaData.upload.nextState;
      $('<p>' + indiciaData.lang.custom_verification_rules_upload[data.state] + '</p>').appendTo($('#progress-output'));
    }

    $('#progress-info').show();
    // Send request for the next step to be performed via the proxy to the warehouse.
    $.ajax({
      type: "POST",
      url: url,
      data: data,
      dataType: 'json',
      headers: {'Authorization': 'IndiciaTokens ' + indiciaData.write.auth_token + '|' + indiciaData.write.nonce}
    })
    .done((data) => {
      indiciaData.upload.nextState = data.nextState;
      if (data.uploadedFile) {
        indiciaData.upload.uploadedFile = data.uploadedFile;
      }
      if (data.status === 'ok') {
        // All OK, so proceed to next step.
        uploadNextStep();
      }
      else if (data.error) {
        $('<div id="errors-output">' +
          indiciaData.templates.warningBox.replace('{message}', indiciaData.lang.custom_verification_rules_upload.problemsFound) +
          '<ul><li>' + data.error + '</li></ul></div>')
          .appendTo($('#progress-output'))
          .fadeIn();
      }
      else if (data.errorList) {
        $('<div id="errors-output">' +
          indiciaData.templates.warningBox.replace('{message}', indiciaData.lang.custom_verification_rules_upload.problemsFound) +
          '<ul><li>' +
          data.errorList.join('</li><li>')
          + '</li></ul></div>')
          .appendTo($('#progress-output'))
          .fadeIn();
      }
    })
    .fail(() => {
      alert('An error occurred whilst uploading the rules.');
    });
  }

  /**
   * Button handler which initiates the upload process.
   */
  $('#upload-rules').click(function() {
    // Prevent re-uploading the same file twice which doesn't work.
    $('#upload-rules').attr('disabled', true);
    // Remove any messages from previous upload attempt.
    $('#progress-output *').remove();
    $('#progress-output-cntr').show();
    uploadNextStep();
  });
});