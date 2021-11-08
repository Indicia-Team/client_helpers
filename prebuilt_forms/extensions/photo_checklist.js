jQuery(document).ready(function($) {

  $.each($('.photo-checklist'), function() {
    var id = $(this).attr('id');
    var config = indiciaData['photo-checklist-' + id];
    var resize = null;
    if (config.resizeWidth && config.resizeHeight) {
      resize = {
        width: config.resizeWidth,
        height: config.resizeHeight,
        quality: config.resizeQuality
      };
    }

    // Apply the expandSections option.
    if (config.expandSections === 'first') {
      $(this).find('.photo-checklist-section:first-child .panel-collapse').collapse('show');
    } else if (config.expandSections === 'all') {
      $(this).find('.photo-checklist-section .panel-collapse').collapse('show');
    }

    // Hook up the photo uploader.
    $.each($('.upload-photo'), function() {
      var button = this;
      var dropEl;
      var uploadOptions = {
        runtimes : 'html5',
        browse_button : this.id,
        container: $(this).closest('.panel-body').find('.photo-wrap')[0],
        url : indiciaData.uploadScript + '?destination=' + indiciaData.imageRelativePath,
        filters : {
          max_file_size : '10mb',
          mime_types: [
            {title : "Image files", extensions : "jpg,png"},
          ]
        },
        resize: resize,
        chunk_size: '500KB',
        init: {

          FilesAdded: function(up, files) {
            // Auto-start the upload.
            up.start();
            $.each(files, function() {
              var ext = this.name.split('.').pop();
              // Change the file name to be unique & lowercase.
              this.name = (plupload.guid()+'.'+ext).toLowerCase();
            });
          },

          FileUploaded: function(up, file, response) {
            var panel = $(button).closest('.photo-checklist-item');
            var countInput = panel.find('input[type="number"]');
            var img = panel.find('img');
            // Change the Fancybox link and the image thumbnail to the new image.
            panel.find('a.fancybox').attr('href', indiciaData.rootFolder + indiciaData.interimImagePath + file.name);
            img.attr('src', indiciaData.rootFolder + indiciaData.interimImagePath + file.name);
            // Store the path to save to the database.
            panel.find('.photo-checklist-media-path').val(file.name);
            // Update classes to reflect photo upload done.
            panel.find('.photo-wrap').removeClass('dragover');
            // Auto-set the count to 1 if a photo uploaded.
            if (!$(countInput).val()) {
              $(countInput).val(1);
            }
            setPanelStyle(panel);
          },

          UploadProgress: function(up, file) {
            var progress = $(button).closest('.photo-checklist-item').find('progress');
            if (file.percent === 100) {
              $(progress).hide();
            } else {
              $(progress)
                .val(file.percent)
                .text(file.percent + '%')
                .show();
            }

          },

          Error: function(up, err) {
            console.log("\nError #" + err.code + ": " + err.message);
          }
        }
      };

      // Enable drag and drop of photos on desktop browser.
      if (!/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        dropEl = $(button).closest('.photo-wrap');
        dropEl.addClass('image-drop');
        dropEl[0].addEventListener('dragenter', function(e) {
          $(dropEl).addClass('dragover');
        });
        dropEl[0].addEventListener('dragleave', function(e) {
          $(dropEl).removeClass('dragover');
        });
        uploadOptions.drop_element = dropEl[0];
      }

      var uploader = new plupload.Uploader(uploadOptions);

      uploader.init();
    });

    /**
     * Sets photo panel style.
     *
     * Adds panel-success if the panel has a count (i.e. is a record) and adds
     * user-photo class if a photo uploaded.
     */
    function setPanelStyle(panel) {
      var countInput = $(panel).find('input[type="number"]');
      var mediaPathInput = $(panel).find('.photo-checklist-media-path');
      var mediaDeletedInput = $(panel).find('.photo-checklist-media-deleted');
      if ($(countInput).val() === '' || $(countInput).val() < 1) {
        $(countInput).val('');
        $(panel).removeClass('panel-success');
        $(panel).addClass('panel-default');
      } else {
        $(panel).removeClass('panel-default');
        $(panel).addClass('panel-success');
      }
      if ($(mediaPathInput).val() === '' || $(mediaDeletedInput).val() === 't') {
        $(panel).removeClass('user-photo');
      } else {
        $(panel).addClass('user-photo');
      }
    }

    // Set a class to visually indicate which photo panes have a count value.
    $(this).find('input[type="number"]').change(function(e) {
      var panel = $(e.currentTarget).closest('.photo-checklist-item');
      setPanelStyle(panel);
    });

    $(this).find('.delete-photo').click(function(e) {
      var panel = $(e.currentTarget).closest('.photo-checklist-item');
      var img = panel.find('img');
      var a = panel.find('a.fancybox');
      img.attr('src', img.attr('data-orig-src'));
      a.attr('href', a.attr('data-orig-href'));
      panel.find('.photo-checklist-media-deleted').val('t');
      setPanelStyle(panel);
    });

    // Initial load of existing sample must style panels to show which are counted.
    $.each($('.photo-checklist-item'), function() {
      setPanelStyle(this);
    });

  });

});
