jQuery(document).ready(function($) {

  /**
   * Hook up the photo uploader to a photo panel.
   *
   * @param DOM photoPanel
   *   Photo panel element.
   * @param array resize
   *   Resize configuration.
   */
  function enableUpload(photoPanel, resize) {
    var button = $(photoPanel).find('.upload-photo');
    var dropEl;
    var uploadOptions = {
      runtimes : 'html5',
      browse_button : button[0].id,
      container: $(photoPanel).find('.photo-wrap')[0],
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
            this.name = (plupload.guid() + '.' + ext).toLowerCase();
          });
        },

        FileUploaded: function(up, file, response) {
          var countInput = photoPanel.find('input[type="number"]');
          var img = photoPanel.find('img');
          // Change the Fancybox link and the image thumbnail to the new image.
          photoPanel.find('a.photo-popup').attr('href', indiciaData.rootFolder + indiciaData.interimImagePath + file.name);
          img.attr('src', indiciaData.rootFolder + indiciaData.interimImagePath + file.name);
          // Store the path to save to the database.
          photoPanel.find('.photo-checklist-media-path').val(file.name);
          // Update classes to reflect photo upload done.
          photoPanel.find('.photo-wrap').removeClass('dragover');
          // Auto-set the count to 1 if a photo uploaded.
          if (!$(countInput).val()) {
            $(countInput).val(1);
          }
          setPanelStyle(photoPanel);
        },

        UploadProgress: function(up, file) {
          var progress = $(photoPanel).find('progress');
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
      dropEl = $(photoPanel).find('.photo-wrap');
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
  }

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

  /**
   * Retrieves a taxon label.
   *
   * @param array taxon
   *   Taxonomic data read from the database.
   * @param bool italicise
   *   Should scientific species (or sub-species) names be italicised?
   *
   * @return string
   *   Returns the common name, if available, or the scientific name otherwise
   *   (italicised where appropriate).
   */
   function getFormattedTaxonLabel(taxon, italicise) {
    if (taxon.default_common_name) {
      return taxon.default_common_name;
    }
    else if (italicise && $taxon['taxon_rank_sort_order'] >= 300) {
      return '<em>' + taxom.preferred_taxon + '</em>';
    } else {
      return taxon.preferred_taxon;
    }
  }

  /**
   * Returns the taxon image to add to each panel.
   *
   * Calculates the file name using the taxon name and builds a FancyBox
   * thumbnail.
   *
   * @param array config
   *   Control configuration.
   * @param array taxon
   *   Taxonomic data read from the database.
   * @param array|null existingOccurrence
   *   If editing a sample, the existing occurrence data loaded for this panel.
   *
   * @return string
   *   HTML for the image (including wrapping anchor element).
   */
   function getTaxonImage(config, taxon, existingOccurrence) {
    var path = config.imagesPath;
    var origfilename;
    var origThumbFilename;
    var filename;
    var thumbFilename;
    var taxonLabel = getFormattedTaxonLabel(taxon, false);
    var machineName = taxon.taxon.toLowerCase().replace(/[^a-z0-9]/g, '-');
    // Ensure trailing slash.
    if (path.substr(path.length - 1) !== '/') {
      path += '/';
    }
    // Get the original default image details.
    origfilename = path + machineName + '.jpg';
    origThumbFilename = path + 'thumb-' + machineName + '.jpg';
    if (existingOccurrence && existingOccurrence.media_path) {
      filename = indiciaData.read.url + '/upload/' + existingOccurrence.media_path;
      thumbFilename = indiciaData.read.url + '/upload/thumb-' + existingOccurrence.media_path;
    } else {
      filename = origfilename;
      thumbFilename = origThumbFilename;
    }
    return '<a class="photo-popup" data-fancybox="' + machineName + '" href="' + filename + '" data-orig-href="' + origfilename + '" data-caption="' + taxonLabel + '">' +
      '<img src="' + thumbFilename + '" data-orig-src="' + origThumbFilename + '" title="' + taxonLabel + '" alt="' + taxonLabel + '" class="img-rounded">' +
      '</a>';
  }

  /**
   * Returns the species info link for each species panel.
   *
   * @param array config
   *   Control options.
   * @param array $taxon
   *   Taxonomic data read from the database.
   *
   * @return string
   *   HTML for the link.
   */
  function getSpeciesInfoLink(config, taxon) {
    if (config.speciesInfoLink) {
      return config.speciesInfoLinkTemplate
        .replace(/{{ species-info-link }}/g, config['speciesInfoLink'])
        .replace(/{{ link-common-name }}/g, taxon['default_common_name'].toLowerCase().replace(/[^a-z0-9]/, '-'));
    }
    return '';
  }

  function addSpeciesPanelsFromList(config, sectionIdx, section, taxaList) {
    var sectionBody = $(section).find('.panel-body');
    var resize = null;
    if (config.resizeWidth && config.resizeHeight) {
      resize = {
        width: config.resizeWidth,
        height: config.resizeHeight,
        quality: config.resizeQuality
      };
    }
    $.each(taxaList, function(itemIdx, taxon) {
      var existingOccurrence = config.occurrences[taxon.preferred_taxa_taxon_list_id] ? config.occurrences[taxon.preferred_taxa_taxon_list_id] : null;
      var photoPanel = $(config.itemTemplate
        .replace(/{{ taxon }}/g, taxon.taxon)
        .replace(/{{ preferred_taxon }}/g, taxon.preferred_taxon)
        .replace(/{{ default_common_name }}/g, taxon.default_common_name)
        .replace(/{{ formatted_taxon }}/g, getFormattedTaxonLabel(taxon, true))
        .replace(/{{ image }}/g, getTaxonImage(config, taxon, existingOccurrence))
        .replace(/{{ speciesInfoLink }}/g, getSpeciesInfoLink(config, taxon))
        .replace(/{{ section_idx }}/g, sectionIdx)
        .replace(/{{ item_idx }}/g, itemIdx)
        .replace(/{{ occ_id }}/g, existingOccurrence ? existingOccurrence.occurrence_id : '')
        .replace(/{{ media_id }}/g, existingOccurrence ? existingOccurrence.media_id : '')
        .replace(/{{ media_path }}/g, existingOccurrence ? existingOccurrence.media_path : '')
        .replace(/{{ ttl_id }}/g, taxon.taxa_taxon_list_id)
        .replace(/{{ count }}/g, existingOccurrence ? existingOccurrence.count : '')
      ).appendTo(sectionBody);
      // Initial load of existing sample must style panels to show which are counted.
      setPanelStyle(photoPanel);
      enableUpload(photoPanel, resize);
    });
    // Set a class to visually indicate which photo panes have a count value.
    $(sectionBody).find('input[type="number"]').change(function(e) {
      var panel = $(e.currentTarget).closest('.photo-checklist-item');
      setPanelStyle(panel);
    });
    // Hook up the delete buttons.
    $(sectionBody).find('.delete-photo').click(function(e) {
      var panel = $(e.currentTarget).closest('.photo-checklist-item');
      var img = panel.find('img');
      var a = panel.find('a.photo-popup');
      img.attr('src', img.attr('data-orig-src'));
      a.attr('href', a.attr('data-orig-href'));
      panel.find('.photo-checklist-media-deleted').val('t');
      setPanelStyle(panel);
    });
  }

  function addSpeciesPanels(config, sectionIdx, section, sectionInfo) {
    var query;
    if (sectionInfo.taxaList) {
      addSpeciesPanelsFromList(config, sectionIdx, section, sectionInfo.taxaList);
    }
    else {
      query = new URLSearchParams();
      $.each(sectionInfo.params, function(key, val) {
        query.append(key, val);
      });
      request = indiciaData.read.url + 'index.php/services/data/taxa_search?mode=json' +
          '&nonce=' + indiciaData.read.nonce +
          '&auth_token=' + indiciaData.read.auth_token +
          '&' + query.toString() + '&callback=?';
      $.getJSON(request,
        null,
        function(response) {
          addSpeciesPanelsFromList(config, sectionIdx, section, response);
        }
      );
    }
  }

  /**
   * A public function for populating a photo checklist.
   */
  indiciaFns.populatePhotoChecklist = function populatePhotoChecklist(el, speciesSections) {
    var id = $(el).attr('id');
    var config = indiciaData['photo-checklist-' + id];
    var sectionIdx = 0;
    $(el).find('.photo-checklist-section').remove();
    $.each(speciesSections, function(title, sectionInfo) {
      var sectionId;
      var section;
      sectionIdx++;
      sectionId = id + '-section-' + sectionIdx;
      section = $(config.sectionTemplate
        .replace(/{{ section_title }}/g, title)
        .replace(/{{ section_id }}/g, sectionId)
        ).appendTo(el);
      addSpeciesPanels(config, sectionIdx, section, sectionInfo);
    });
    // Apply the expandSections option.
    if (config.expandSections === 'first') {
      $(el).find('.photo-checklist-section:first-child .panel-collapse').collapse('show');
    } else if (config.expandSections === 'all') {
      $(el).find('.photo-checklist-section .panel-collapse').collapse('show');
    }
  }

  /**
   * Initial population.
   */
  $.each($('.photo-checklist'), function() {
    var id = $(this).attr('id');
    var config = indiciaData['photo-checklist-' + id];
    indiciaFns.populatePhotoChecklist(this, config.speciesSections);
  });

  /**
   * Uses a location attribute value (e.g. region) to populate the checklist.
   *
   * @param string attrValue
   *   Attribute value to populate from.
   */
  function populateFromPhotoChecklistData(attrValue) {
    if (indiciaData.photoChecklistData[attrValue]) {
      $.each($('.photo-checklist'), function() {
        indiciaFns.populatePhotoChecklist(this, indiciaData.photoChecklistData[attrValue]);
      });
    }
  }

  /**
   * Hook photo checklist population up to the location select.
   *
   * If photo_checklist_by_location_attr control in use.
   */
  if (indiciaData.useLocAttrToPopulatePhotoChecklist) {
    $('#imp-location').change(function() {
      if ($('#imp-location').val() !== '') {
        // Find the selected location's appropriate attribute value.
        request = indiciaData.read.url + 'index.php/services/data/location_attribute_value?mode=json' +
          '&nonce=' + indiciaData.read.nonce +
          '&auth_token=' + indiciaData.read.auth_token +
          '&location_id=' + $('#imp-location').val() +
          '&location_attribute_id=' + indiciaData.useLocAttrToPopulatePhotoChecklist +
          '&callback=?';
        $.getJSON(request,
          null,
          function(attrValResponse) {
            if (attrValResponse.length > 0) {
              // For first time use, we load the data file that relates
              // attribute values to the checklist data.
              if (indiciaData.photoChecklistData) {
                populateFromPhotoChecklistData(attrValResponse[0].value);
              } else {
                $.getJSON(indiciaData.photoChecklistDataFile, function(dataFile) {
                  indiciaData.photoChecklistData = dataFile;
                  populateFromPhotoChecklistData(attrValResponse[0].value);
                });
              }
            }
          }
        );
      }
    });
    mapInitialisationHooks.push(function() {
      // If only one location option, then select it.
      if ($('#imp-location option:not([value=""])').length === 1) {
        $('#imp-location').val($('#imp-location option:not([value=""])').val());
      }
      // Force initial entry to load list.
      $('#imp-location').change();
    });
  }

});
