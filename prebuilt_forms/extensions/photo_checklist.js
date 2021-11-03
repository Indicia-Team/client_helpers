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

    $.each($('.fa-camera'), function() {
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
        chunk_size: '1MB',
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
            // Change the Fancybox link and the image thumbnail to the new image.
            $(button).closest('.photo-wrap').find('a.fancybox').attr('href', indiciaData.rootFolder + indiciaData.interimImagePath + file.name);
            $(button).closest('.photo-wrap').find('img').attr('src', indiciaData.rootFolder + indiciaData.interimImagePath + file.name);
          },

          /*UploadProgress: function(up, file) {
              document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + "%</span>";
          },*/

          Error: function(up, err) {
            console.log("\nError #" + err.code + ": " + err.message);
          }
        }
      };

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

  });

});
