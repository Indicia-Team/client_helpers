//Javascript functions using jQuery now need to be defined inside a "(function ($) { }) (jQuery);" wrapper.
//This means they cannot normally be seen by the outside world, so in order to make a call to one of these
//functions, we need to assign it to a global variable.

var setUpSamplesForm, setUpOccurrencesForm, saveSubSample, saveSuperSample, saveOccurrence, setTotals, getRowTotal,
  checkWalkLimit, walkLimitAsText, formOptions, bindRecorderNameAutocomplete;

// If an ID is invariant, can use jQuery ID selectors.
// If it is variant, then have to filter on the property using a function: the ID selectors use the attribute - i.e.
//   the initial value.

(function ($) {
  setUpSamplesForm = function (options) {
    formOptions = options;

    if (typeof formOptions.recorderNameAttrID !== 'undefined') {
      bindRecorderNameAutocomplete(
        formOptions.recorderNameAttrID,
        formOptions.userID,
        indiciaData.warehouseUrl,
        formOptions.surveyID,
        indiciaData.read.auth_token,
        indiciaData.read.nonce
      );
    }

    $('#imp-location').change(function (evt) {
      $('#entered_sref').val(formOptions.sites[evt.target.value].centroid_sref);
      $('#entered_sref_system').val(formOptions.sites[evt.target.value].centroid_sref_system);
    });

    // allow deletes if delete button is present.
    $('#delete-button').click(function () {
      if (confirm(formOptions.deleteConfirm)) {
        $('#delete-form').submit();
      } // else do nothing.
    });

    if (formOptions.interactingSampleAttributes.length === 2) {
      var sel1 = '#smpAttr\\:' + formOptions.interactingSampleAttributes[0],
          sel2 = '#smpAttr\\:' + formOptions.interactingSampleAttributes[1],
          val1 = $(sel1).val(),
          val2 = $(sel2).val();
      if (val1 != '' && val2 == '') {
        $(sel2).val(100 - val1);
      } else if (val1 == '' && val2 != '') {
        $(sel1).val(100 - val2);
      }
      $(sel1).change(function() {
        $(sel2).val(100 - $(sel1).val());
      });
      $(sel2).change(function() {
        $(sel1).val(100 - $(sel2).val());
      });
    }
    
    if (typeof formOptions.startTimeAttrID != 'undefined' && typeof formOptions.endTimeAttrID != 'undefined') {
      // Introduced for ABLE Issue 68
      // Start time
      $('#smpAttr\\:' + formOptions.startTimeAttrID + ',#smpAttr\\:' + formOptions.endTimeAttrID).change(function(e) {
        var both = $('#smpAttr\\:' + formOptions.startTimeAttrID + ',#smpAttr\\:' + formOptions.endTimeAttrID),
            start = $('#smpAttr\\:' + formOptions.startTimeAttrID),
            end = $('#smpAttr\\:' + formOptions.endTimeAttrID),
            startValue, endValue, sHour, sMinute, eHour, eMinute, hasError

        both.closest('.form-group').removeClass('has-warning')
        both.closest('.form-group').find('.inline-warning').remove()

        // first check both are in correct format
        startValue = start.val()
        if (startValue.match(/^((2[0-3])|([0,1][0-9])):[0-5][0-9]$/)) {
          sHour = parseInt(startValue.slice(0,2))
          sMinute = parseInt(startValue.slice(3))
          if (sHour < 8 || (sHour === 18 && sMinute > 0) || sHour > 18) {
            start.closest('.form-group').addClass('has-warning')
            start.closest('.form-group').append('<p for="smpAttr:' +
              formOptions.startTimeAttrID +
              '" generated="true" class="inline-warning ui-state-highlight page-notice ui-corner-all">' +
              formOptions.langStrings.startTimeRange + '</p>')
            hasError = true
          }
        } else hasError = true;
        endValue = end.val()
        if (endValue.match(/^((2[0-3])|([0,1][0-9])):[0-5][0-9]$/)) {
          eHour = parseInt(endValue.slice(0,2))
          eMinute = parseInt(endValue.slice(3))
          if (eHour < 8 || (eHour === 18 && eMinute > 0) || eHour > 18) {
            end.closest('.form-group').addClass('has-warning')
            end.closest('.form-group').append('<p for="smpAttr:' +
              formOptions.endTimeAttrID +
              '" generated="true" class="inline-warning ui-state-highlight page-notice ui-corner-all">' +
              formOptions.langStrings.endTimeRange + '</p>')
              hasError = true
          }
        } else hasError = true;
        if (hasError) {
          return
        }
        if (sHour > eHour || (sHour === eHour && sMinute > eMinute)) {
          both.closest('.form-group').addClass('has-warning')
          start.closest('.form-group').append('<p for="smpAttr:' +
            formOptions.startTimeAttrID +
            '" generated="true" class="inline-warning ui-state-highlight page-notice ui-corner-all">' +
            formOptions.langStrings.startTimeAfter + '</p>')
          end.closest('.form-group').append('<p for="smpAttr:' +
            formOptions.endTimeAttrID +
            '" generated="true" class="inline-warning ui-state-highlight page-notice ui-corner-all">' +
            formOptions.langStrings.endTimeBefore + '</p>')
        }
      })
      $('#smpAttr\\:' + formOptions.startTimeAttrID).change()
    }
  };

  setUpOccurrencesForm = function (options) {
    formOptions = options;

    var scrollPos = $('a#main-content').offset().top;
    window.scroll(0, scrollPos);

    $('input.subSampleInput:not(:checkbox)')
      .keydown(sub_sample_keydown)
      .focus(general_focus)
      .change(input_change)
      .blur(sub_sample_input_blur);
    $('select.subSampleInput,input.subSampleInput:checkbox')
      .keydown(sub_sample_keydown)
      .focus(general_focus)
      .change(sub_sample_immediate_save);

    $('input.superSampleInput:not(:checkbox)')
      .keydown(formOptions.format === 'complex' ? super_sample_keydown_complex : super_sample_keydown_simple)
      .change(input_change)
      .blur(super_sample_input_blur)
      .focus(general_focus);
    $('select.superSampleInput,input.superSampleInput:checkbox')
      .keydown(formOptions.format === 'complex' ? super_sample_keydown_complex : super_sample_keydown_simple)
      .change(super_sample_immediate_save)
      .focus(general_focus);

    // supersample attribute hideGrid functionality when changing the attribute
    if (formOptions.settings.species_supersample_attributes.length ) {
      for (var i = 0; i < formOptions.settings.species_supersample_attributes.length; i++) {
        if (typeof formOptions.settings.species_supersample_attributes[i].hideGridValues !== "undefined") {
          $('#superSampleAttr-' + formOptions.settings.species_supersample_attributes[i].id).change(function() {
            var parts = this.id.split('-'),
                newValue = $(this).val();
            if ($(this).attr('type') === "hidden") {
              return;
            }
            if ($(this).attr('type') === "checkbox" && $(this).filter(':checked').length === 0) {
              newValue = '0';
            }
            for (var j = 0; j < formOptions.settings.species_supersample_attributes.length; j++) {
              if (parts[1] == formOptions.settings.species_supersample_attributes[j].id) {
                var values = formOptions.settings.species_supersample_attributes[j].hideGridValues.split(',');
                var gridExistingOccs = $('#species_grid_' + formOptions.settings.species_supersample_attributes[j].grid)
                    .find('input,select')
                    .filter(function( index ) {
                        var innerParts = this.id.split('-');
                        return innerParts[0] === 'value' && innerParts[4] !== 'NEW'
                    }).length > 0;
                if (!gridExistingOccs && values.indexOf(newValue) >= 0) {
                  $('.species_grid_controls_' + formOptions.settings.species_supersample_attributes[j].grid).hide();
                  $('.species_grid_selector_' + formOptions.settings.species_supersample_attributes[j].grid).hide();
                  $('#species_grid_table_' + formOptions.settings.species_supersample_attributes[j].grid).hide();
                  if ($('tbody#global_subsample_attributes tr').length > 0) {
                    $('table.species_grid.simple').show();
                    $('table.species_grid.simple tbody.species_grid').hide();
                  } else {
                    $('table.species_grid.simple').hide();
                  }
                } else {
                  $('.species_grid_controls_' + formOptions.settings.species_supersample_attributes[j].grid).show();
                  $('.species_grid_selector_' + formOptions.settings.species_supersample_attributes[j].grid).show();
                  $('#species_grid_table_' + formOptions.settings.species_supersample_attributes[j].grid).show();
                  $('#species_grid_' + formOptions.settings.species_supersample_attributes[j].grid).show();
                  $('table.species_grid.simple').show();
                }
              }
            }
            $('table.species_grid').trigger('columnschange', [null]);
          });
        }
      }
    }
    // Do an AJAX population of the grid rows.
    process(0);
    // supersample attribute hideGrid functionality initial state can only be done at
    // the end of process as only can hide the grid if there are no occurrecnes in the grid.

    /*
    indiciaFns.bindTabsActivate($('#tabs'), function(event, ui) {
      var target = typeof ui.newPanel==='undefined' ? ui.panel : ui.newPanel[0];;
      // remove any hanging autocomplete select list.
      $('.ac_results').hide();
    });
    */
    // If the form features sticky headers and the species autocompletes are in the header,
    // then they must be set up after the sticky header is cloned. This is because the clone
    // points the sticky header AC to the field in the original header, and there is no way 
    // to reset it (see $input).
    // This code runs before the sticky headers are set up.
    if (formOptions.addSpeciesPosition === 'above') {
      var createSticky = Drupal.TableHeader.prototype.createSticky;
      Drupal.TableHeader.prototype.createSticky = function () {
        createSticky.call(this);
        this.$originalTable.find('.willAutocomplete').addClass('original');
        this.$stickyTable.find('.willAutocomplete').addClass('sticky');
        $.each(formOptions.speciesTabDefinition, function(idx, details){
            bindSpeciesAutocomplete(
              'taxonLookupControl' + idx + ".original",
              'tbody#species_grid_' + idx,
              details.taxon_list_id,
              details.taxon_min_rank,
              details.taxon_filter_field,
              details.taxon_filter,
              {"auth_token" : indiciaData.read.auth_token, "nonce" : indiciaData.read.nonce},
              formOptions.langStrings.duplicateTaxonMessage,
              25,
              idx
            );
            bindSpeciesAutocomplete(
              'taxonLookupControl' + idx + ".sticky",
              'tbody#species_grid_' + idx,
              details.taxon_list_id,
              details.taxon_min_rank,
              details.taxon_filter_field,
              details.taxon_filter,
              {"auth_token" : indiciaData.read.auth_token, "nonce" : indiciaData.read.nonce},
              formOptions.langStrings.duplicateTaxonMessage,
              25,
              idx
            );
        });
      };
    } else {
      $.each(formOptions.speciesTabDefinition, function(idx, details){
        bindSpeciesAutocomplete(
          'taxonLookupControl' + idx,
          'tbody#species_grid_' + idx,
          details.taxon_list_id,
          details.taxon_min_rank,
          details.taxon_filter_field,
          details.taxon_filter,
          {"auth_token" : indiciaData.read.auth_token, "nonce" : indiciaData.read.nonce},
          formOptions.langStrings.duplicateTaxonMessage,
          25,
          idx
        );
      });
    }
    if (formOptions.format === 'complex') {
      $('input[name=section]').change(function() {
        if (this.checked) {
          $('.occ-cell,.total-cell,.sub-sample-cell,.label-cell,.image-button-cell,.images-row,.comment-button-cell,.comments-row').hide();
          // animation deliberately short - gives a flicker so users know a change has happened.
          $('.occ-cell.section-'+$(this).val()).show(100);
          $('.total-cell.section-'+$(this).val()).show();
          $('.sub-sample-cell.section-'+$(this).val()).show();
          $('.label-cell.section-'+$(this).val()).show();
          $('.image-button-cell.section-'+$(this).val()).show();
          $('.comment-button-cell.section-'+$(this).val()).show();
        }
      })
    } else {
      $('input[name=speciesTab]').change(function() {
        var tab = $(this).val();
        // supersample attribute hideGrid functionality when changing the species tab
        var hide = false;
        if (this.checked) {
          $('.species_grid_title').hide();
          $('.species_grid_title_'+tab).show();
          $('.species_grid_controls').hide();
          $('table.species_grid').hide();
          $('table.species_grid tbody.species_grid').hide();
          $('table.species_grid .species_grid_selector').hide();
          $('.species_grid_supersample_attributes').hide();
          $('#species_grid_supersample_attributes_'+tab).show();
          for (var i = 0; i < formOptions.settings.species_supersample_attributes.length; i++) {
            if (formOptions.settings.species_supersample_attributes[i].grid == tab &&
                typeof formOptions.settings.species_supersample_attributes[i].hideGridValues !== "undefined") {
              var ssaValue = $('#superSampleAttr-'+formOptions.settings.species_supersample_attributes[i].id).val();
              var values = formOptions.settings.species_supersample_attributes[i].hideGridValues.split(',');

              if ($('#superSampleAttr-'+formOptions.settings.species_supersample_attributes[i].id).attr('type') == "checkbox"
                  && $('#superSampleAttr-'+formOptions.settings.species_supersample_attributes[i].id).filter(':checked').length === 0){
                ssaValue = "0";
              }
              var gridExistingOccs = $('#species_grid_' + formOptions.settings.species_supersample_attributes[i].grid)
                .find('input,select')
                .filter(function( index ) {
                  var innerParts = this.id.split('-');
                  return innerParts[0] === 'value' && innerParts[4] !== 'NEW'
                }).length > 0;
              if (!gridExistingOccs && values.indexOf(ssaValue) >= 0) {
                hide = true;
              }
              break;
            }
          }
          if (!hide) {
            $('table.species_grid').show();
            $('.species_grid_controls_' + tab).show();
            $('#species_grid_' + tab).show();
            $('table.species_grid .species_grid_selector_' + tab).show();
          } else {
            if ($('tbody#global_subsample_attributes tr').length > 0) {
              $('table.species_grid').show();
            }
          }
          $('table.species_grid').trigger('columnschange', [null]);
        }
      })
    }

        $('input[name^=species-list-]').change(function() {

            if (!this.checked) {
              return;
            }
            // Make sure sticky equivalent is also set.
            $('input[name=' + this.name + '][value=' + $(this).val() + ']').not(':checked').prop('checked',true).parent().addClass('active');
            $('input[name=' + this.name + ']').not(':checked').not('[value=' + $(this).val() + ']').parent('.active').removeClass('active');

            //$('input[name=' + this.name + '][value=' + $(this).val() + ']').not(':checked').closest('div').find('.active').removeClass('active');
            //$('input[name=' + this.name + '][value=' + $(this).val() + ']').not(':checked').prop('checked',true).parent().addClass('active');

            var gridNumber = this.name.substring(13);
            listTypeChange($(this).val(), 'tbody#species_grid_'+gridNumber, gridNumber);
        });

        // ID format is imagesButton-[ttlid]-[S<N>]-[SubsampleID] : invariant.
        $( "body" ).on( "click", "button[id^=imagesButton-]", function( event ) {
            var parts = event.target.id.split('-');
            var rowParts = $(event.target).closest('tr').prop('id').split('-');
            $('.comments-row').hide();
            if ($('#images-row-' + rowParts[1] + ':visible').length > 0) {
              $('.images-row').hide();
            } else {
              $('.images-row').hide();
              $('#images-row-' + rowParts[1]).show().find('.image-cell')
                .hide().filter('.section-'+parts[2]).show();
              if (!$('#images-row-' + rowParts[1] + ' button:first').hasClass('uwde-events-added')) {
                // the add image button is added async so have to attach the event handlers here, when first displayed
                $('#images-row-' + rowParts[1]).find('button')
                    .addClass('uwde-events-added')
                    .keydown(occ_keydown)
                    .focus(general_focus);
              }
            }
            
            event.preventDefault();
        });

        // ID format is commentsButton-[ttlid]-[S<N>]-[SubsampleID] : invariant.
        $( "body" ).on( "click", "button[id^=commentsButton-]", function( event ) {
            var parts = event.target.id.split('-');
            var rowParts = $(event.target).closest('tr').prop('id').split('-');
            $('.images-row').hide();
            if ($('#comments-row-' + rowParts[1] + ':visible').length > 0) {
              $('.comments-row').hide();
            } else {
              $('.comments-row').hide();
              $('#comments-row-' + rowParts[1]).show().find('.comment-cell')
                .hide().filter('.section-'+parts[2]).show();
            }
            event.preventDefault();
        });

        $('input[name^=species-sort-order-]').change(function() {
            if (!this.checked) {
                return;
            }
            var gridNumber = this.name.substring(19);
            var table = $('tbody#species_grid_' + gridNumber);
            var rows = table.find('tr.occurrence-row');
            var col = $(this).val();
            
            // Make sure sticky equivalent is also set.
            $('input[name=' + this.name + '][value=' + col + ']').not(':checked').prop('checked',true).parent().addClass('active');
            $('input[name=' + this.name + ']').not(':checked').not('[value=' + col + ']').parent('.active').removeClass('active');
            
            if (col === 'none') {
              return;
            }
            rows.sort(function(a, b) {
                if(typeof $(a).data('species') == 'undefined' || typeof $(b).data('species') == 'undefined') return 0;
                var A = $(a).data('species')[col];
                var B = $(b).data('species')[col];
                if(A == null) A = $(a).data('species')['taxon'];
                if(B == null) B = $(b).data('species')['taxon'];
                if(A=='' || B=='' || A==null || B==null) return 0;
                A = A.toUpperCase();
                B = B.toUpperCase();
                if (parseInt(A) == A && parseInt(B) == B) {
                    A = parseInt(A);
                    B = parseInt(B);
                };
                if(A < B) return -1;
                if(A > B) return 1;
                return 0;
            });
            $.each(rows, function(index, row) {
                // this takes the rows out and inserts at the end.
                table.append(row);
                var imagesRow = table.find('tr#images-row-' + $(row).data('species').taxon_meaning_id);
                if (imagesRow.length > 0) {
                    table.append(imagesRow);
                }
                var commentsRow = table.find('tr#comments-row-' + $(row).data('species').taxon_meaning_id);
                if (commentsRow.length > 0) {
                    table.append(commentsRow);
                }
            });
            var totalsRow = table.find('tr.totals-row');
            table.append(totalsRow);
        });

        $('.subSampleInput').each(function(idx, elem) {
            // id = subSmpAttr-[S<N>]-[SampleId]-[AttributeId]-[AttributeValueId]
            var parts = $(elem).attr('id').split('-'),
                opposite;
            if (formOptions.interactingSampleAttributes.length === 2 &&
                    parts[3] == formOptions.interactingSampleAttributes[0] &&
                    $(elem).val() !== '') {
                // First 4 parts of id are invariant
                opposite = '[id^=subSmpAttr-' + parts[1] + '-' + parts[2] + '-'
                    + formOptions.interactingSampleAttributes[1] + '-]';
                if ($(opposite).val() === '') {
                    $(opposite).val(100 - $(elem).val());
                    saveSubSample(elem, parts[1], parts[2]); // all attributes saved together.
                }
            } else if (formOptions.interactingSampleAttributes.length === 2 &&
                    parts[3] == formOptions.interactingSampleAttributes[1] &&
                    $(elem).val() !== '') {
                opposite = '[id^=subSmpAttr-' + parts[1] + '-' + parts[2] + '-'
                    + formOptions.interactingSampleAttributes[0] + '-]';
                if ($(opposite).val() === '') {
                    $(opposite).val(100 - $(elem).val());
                    saveSubSample(elem, parts[1], parts[2]);
                }
            }
        });

        if (formOptions.settings.occurrence_images) {
            mediaUploadAddedHooks.push(function(div, file) {
                saveOccurrence(div.id, false);
            });
        }
        
    }

    /**
     * Updates the sample for a Transect, including attributes.
     */
    saveSuperSample = function (target) {
      var superSampleAttrValue = $(target).val();

      $(target).addClass('savingPS').addClass('savingSpinner').removeClass('edited');

      if ($(target).prop('type') === 'checkbox' && $(target).filter(':checked').length === 0) {
        superSampleAttrValue = 0;
      }
      // fill in sample stub form
      $('#super_sample_transaction_id').val(target.id);
      $('#superSampleAttr').val(superSampleAttrValue).prop('name', target.name);
      $(target).data('previous', superSampleAttrValue);
      // remove existing error checks results.
      $(target).closest('td').find('.ui-state-error').removeClass('ui-state-error');
      $(target).closest('td').find('.inline-error').remove();
      $('table.species_grid').trigger('columnschange', [null]);
      // only submit if no sample errors
      if($('.superSampleInput').closest('td').find('.inline-error').length == 0) {
        var ajaxStopHandler = function() {
          $( document ).off("ajaxStop", ajaxStopHandler);
          var next = $('.species_grid_supersample_attributes').find('.queued').not('.savingPS');
          if (next.length > 0) {
            super_sample_input_blur({target:next[0]})
          }
        };
        $( document ).ajaxStop(ajaxStopHandler);
        $('#super-sample-form').ajaxSubmit({
            dataType: 'json',
            success: function(data){
                var transaction_id = data.transaction_id,
                    selector = '#' + transaction_id;
                if (checkErrors(data)) {
                    // We need to copy over the information so that future changes update the existing record rather than
                    // create new ones, or creates a new one if we have deleted the attribute
                    parts = $(selector).prop('name').split(':');
                    if (parts.length === 2) {
                        // We have created a new super sample attribute. Can't tell the value ID from response.
                        $.getJSON(indiciaData.warehouseUrl + "index.php/services/data/sample_attribute_value" +
                                "?mode=json&view=list&sample_id=" + data.outer_id + "&auth_token=" +
                                indiciaData.read.auth_token + "&nonce=" + indiciaData.read.nonce +
                                "&sample_attribute_id=" + parts[1] + "&callback=?",
                            function(data) {
                                  // There is a possibility that we have just deleted an attribute (in which case it will not be in the data), so reset all the names first.
                                  $.each(data, function(idx, attr) {
                                      parts[2] = attr.id;
                                  });
                                  $(selector).prop('name', parts.join(':')).removeClass('savingPS').removeClass('savingSpinner');
                              }
                          );
                      } else {
                        if ($(selector).val() == '') {
                          // also possiblity we have cleared an existing value (which deletes the attribute value)
                          parts.splice(2,1);
                          $(selector).prop('name', parts.join(':'));
                        }
                        $(selector).removeClass('savingPS').removeClass('savingSpinner');
                      }
                }
            },
            error: function(jqxhr, status, exception) {
                alert('Exception:', exception);
            }
        });
      }
    }

    /**
     * Updates the sample for a section, including attributes.
     * Due to possibility of interacting attributes, all attributes are saved at the same time.
     */
    saveSubSample = function (target, code, sampleId) {
        // id = subSmpAttr-[S<N>]-[SampleId]-[AttributeId]-[AttributeValueId]
        var parts = target.id.split('-'),
            error = false;

        $(target).addClass('savingCS').addClass('savingSpinner').removeClass('edited');

        // fill in sample stub form
        $('#smpid').val(sampleId);
        // Store the current cell's ID as a transaction ID, so we know which cell we were updating.
        $('#sub_sample_transaction_id').val(target.id);
        // Fill in the details from the section
        $.each(formOptions.sections, function(idx, section) {
            if (section.code == code) {
                $('#smpsref').val(section.centroid_sref);
                $('#smpsref_system').val(section.centroid_sref_system);
                $('#smploc').val(section.id);
            }
        });

        $.each(formOptions.settings.global_subsample_attributes, function(idx, globalSubsampleAttributeID) {
            var selector = 'subSmpAttr-' + parts[1] + '-' + parts[2] + '-' + globalSubsampleAttributeID + '-';
            // first 4 parts are invariant.
            $('[id^=' + selector + ']').each(function(idx, input) {
                var mandatory = true;
                var parts = input.id.split('-'),
                    subSmpAttrValue = $(input).val(),
                    name = 'smpAttr:' + globalSubsampleAttributeID + (parts[4] === "NEW" ? '' : ':' + parts[4]);
                if (typeof input.type !== "undefined" && input.type == "checkbox" && $(input).filter(':checked').length == 0) {
                    // dummy input with a value of 0 to indicate unselected in a non-ajax submit has no id
                    subSmpAttrValue = "0";
                }
                $(input).data('previous', subSmpAttrValue).addClass('savingCS').removeClass('edited');
                $('#subSmpAttr-'+globalSubsampleAttributeID).val(subSmpAttrValue).prop('name', name);
                // remove existing error checks results.
                $(this).closest('td').find('.ui-state-error').removeClass('ui-state-error');
                $(this).closest('td').find('.inline-error').remove();
                for(var i = 0; i < formOptions.attributeConfiguration.length; i++) {
                  if(formOptions.attributeConfiguration[i].id == parts[1] &&
                      typeof formOptions.attributeConfiguration[i].required != "undefined" &&
                      typeof formOptions.attributeConfiguration[i].required.species_grid != "undefined" &&
                      formOptions.attributeConfiguration[i].required.species_grid == false)
                  mandatory = false;
                }
                if(mandatory && $(this).val()=='') {
                  error = true;
                  $(this).after('<p htmlfor="' + $(this).attr('id') + '" class="inline-error">' + formOptions.langStrings.requiredMessage + '</p>');
                }
            });
        });

      // only submit if no sample errors
      if (!error) {
        var ajaxStopHandler = function() {
          $( document ).off("ajaxStop", ajaxStopHandler);
          var next = $('#global_subsample_attributes').find('.queued').not('.savingCS');
          if (next.length > 0) {
            sub_sample_input_blur({target:next[0]})
          }
        };
        $( document ).ajaxStop(ajaxStopHandler);
        $('#sub-sample-form').ajaxSubmit({
            dataType:  'json',
            success: function(data){
                // id = subSmpAttr-[S<N>]-[SampleId]-[AttributeId]-[AttributeValueId]
                if (checkErrors(data)) {
                    var transaction_id = data.transaction_id,
                        parts = transaction_id.split('-').slice(0, 3); // Cuts off the attribute and value IDs
                    for (var i = 0; i < formOptions.settings.global_subsample_attributes.length; i++) {
                        parts[3] = formOptions.settings.global_subsample_attributes[i];
                        // First 4 parts are invariant, so can use attribute rather than property.
                        $('[id^=' + parts.join('-') + '-]').each(function(index, elem) {
                            var elemParts = elem.id.split('-');
                            if (elem.tagName === "INPUT" && elem.type === "checkbox") {
                                // checkbox attributes are different: hold 0 or 1, not deleted when unchecked.
                                if (elemParts[4] == 'NEW') {
                                    $.getJSON(indiciaData.warehouseUrl + "index.php/services/data/sample_attribute_value" +
                                            "?mode=json&view=list&sample_id=" + data.outer_id + "&auth_token=" +
                                            indiciaData.read.auth_token + "&nonce=" + indiciaData.read.nonce +
                                            "&sample_attribute_id=" + elemParts[3] + "&callback=?", function(data) {
                                        $.each(data, function(idx, attr) {
                                            elemParts[4] = attr.id;
                                            $(elem).prop('id', elemParts.join('-')).removeClass('savingCS').removeClass('savingSpinner');
                                        });
                                    });
                                } else {
                                    $(elem).removeClass('savingCS').removeClass('savingSpinner');
                                }
                            } else {
                                if (elemParts[4] == 'NEW' && $(elem).val() != '') {
                                    $.getJSON(indiciaData.warehouseUrl + "index.php/services/data/sample_attribute_value" +
                                            "?mode=json&view=list&sample_id=" + data.outer_id + "&auth_token=" +
                                            indiciaData.read.auth_token + "&nonce=" + indiciaData.read.nonce +
                                            "&sample_attribute_id=" + elemParts[3] + "&callback=?", function(data) {
                                        $.each(data, function(idx, attr) {
                                            elemParts[4] = attr.id;
                                            $(elem).prop('id', elemParts.join('-')).removeClass('savingCS').removeClass('savingSpinner');
                                        });
                                    });
                                } else if (elemParts[4] != 'NEW' && $(elem).val() == '') {
                                    parts[4] = 'NEW';
                                    $(elem).prop('id', parts.join('-')).removeClass('savingCS').removeClass('savingSpinner');
                                } else {
                                    $(elem).removeClass('savingCS').removeClass('savingSpinner');
                                }
                            }
                        });
                    }
                }
            }
        });
      } else {
        $.each(formOptions.settings.global_subsample_attributes, function(idx, globalSubsampleAttributeID) {
          var selector = 'subSmpAttr-' + parts[1] + '-' + parts[2] + '-' + globalSubsampleAttributeID + '-';
          $('[id^=' + selector + ']').each(function(idx, input) {
            $(input).addClass('ui-state-error').removeClass('savingCS').removeClass('savingSpinner');
          });
        });
      }
      $('table.species_grid').trigger('columnschange', [null]);
    }

    saveOccurrence = function (targetID, isAttribute) {

        // Attr ID format value-[ttlid]-[S<N>]-[SubsampleID]-[OccurrenceID]-[OccurrenceAttributeID]-[OccurrenceAttributeValueID]
        // Comment ID format comment-[ttlid]-[S<N>]-[SubsampleID]-[OccurrenceID]
        // Images ID format is images-[ttlid]-[S<N>]-[SubsampleID] Invariant
        var parts = targetID.split('-'),
            deleteOccurrence = true,
            transactionId = targetID,
            deltaOccurrence = 'N',
            deltaAttributes = 'N',
            deltaMedia = 'N',
            deltaComment = 'N';

        $('#' + targetID).addClass('savingSpinner');

        // Remove any media records in the form.
        $('#occurrence-form').find('[name^=occurrence_medium]').remove();

        // fill in occurrence stub form
        $('#ttlid').val(parts[1]);
        $('#occ_sampleid').val(parts[3]);

        if (parts[0] === 'images') {
            // ID format is imagesTd-[ttlid]-[S<N>]-[SubsampleID]-[OccurrenceID]
            var td = $('#' + targetID).closest('td').prop('id').split('-');
            parts[4] = td[4];
        }
        if (parts[4] === "NEW") {
            $('#occid').prop('disabled', true);
            // existing ID - leave sensitivity and confidentiality as is, new data - use location data
            $('#occSensitive').prop('disabled', false);
            $('#occConfidential').prop('disabled', false);
            deltaOccurrence = 'Y';
        } else {
            $('#occid').prop('disabled', false).val(parts[4]);
            $('#occSensitive').prop('disabled', true);
            $('#occConfidential').prop('disabled', true);
        }

        $('#occzero').val('t');

        $.each(formOptions.settings.occurrence_attributes, function(idx, occurrenceAttributeID) {
            var checkId = 'value-' + parts[1] + '-' + parts[2] + '-' + parts[3] + '-' + parts[4] + '-'
                    + occurrenceAttributeID + '-';
            $('input,select')
                .filter(function( index ) {
                    return this.id.substring(0, checkId.length) == checkId;
                })
                .each(function(idx, input) {
                    var attrParts = input.id.split('-'),
                        occAttrValue = $(input).val(),
                        name = 'occAttr:' + occurrenceAttributeID + (attrParts[6] === "NEW" ? '' : ':' + attrParts[6]);
                    $(input).addClass('savingO').removeClass('edited').removeClass('queued')
                    $(input).data('previous', occAttrValue);
                    $('#occattr-' + occurrenceAttributeID).val(occAttrValue).prop('name', name);
                    if (occAttrValue != "") {
                        if (occAttrValue != "0") {
                            $('#occzero').val('f');
                            deleteOccurrence = false;
                        } else if (formOptions.zeroAbundance) {
                            deleteOccurrence = false;
                        }
                        if (attrParts[6] === "NEW") {
                            deltaAttributes = 'Y';
                        }
                    } else if (attrParts[6] !== "NEW") {
                        deltaAttributes = 'Y';
                    }
                });
        });

        // Only save media that is new.
        // ID format value-[ttlid]-[S<N>]-[SubsampleID]-[OccurrenceID]-[OccurrenceAttributeID]-[OccurrenceAttributeValueID]
        $('[id^=images-' + parts[1] + '-' + parts[2] + '-' + parts[3] + '\\:occurrence_medium\\:id\\:]') // Invariant
            .each(function(index, elem) {
                var elemParts = elem.id.split(':'),
                    elemValue = $(elem).val();
                deleteOccurrence = false;
                if (elemValue == '') { // ID not filled in, so not saved.
                    deltaMedia = 'Y';
                    $('[id^=images-' + parts[1] + '-' + parts[2] + '-' + parts[3] + '\\:occurrence_medium\\:]')
                        .each(function(index, innerElem) {
                            var innerElemParts = innerElem.id.split(':'),
                                innerElemValue = $(innerElem).val();
                            if (innerElemParts[3] == elemParts[3]) { // index values match
                                if (innerElemParts[2] == 'deleted') {
                                    if (innerElemValue == 't') {
                                        $('#occurrence-form')
                                            .append('<input type="text" name="occurrence_medium:deleted:' + innerElemParts[3] + '" value="t">');
                                    }
                                } else if (innerElemParts[2] !== 'id') {
                                    $('#occurrence-form')
                                        .append('<input type="text" name="occurrence_medium:' + innerElemParts[2] + ':' + innerElemParts[3] + '" value="' + innerElemValue + '">');
                                }
                            }
                        });
                }
            });

        var checkId = 'comment-' + parts[1] + '-' + parts[2] + '-' + parts[3] + '-' + parts[4];
        $('textarea')
            .filter(function( index ) {
                return this.id.substring(0, checkId.length) == checkId;
            })
            .each(function(idx, input) {
                $(input).addClass('savingO').removeClass('edited').removeClass('queued')
                $('#occcomment').val($(input).val());
            });

        $('#occdeleted').val(deleteOccurrence ? 't' : 'f');
        transactionId = transactionId + ':' + (deleteOccurrence ? 'deleted' : 'notdeleted') + ':' + deltaOccurrence + ':' + deltaAttributes + ':' + deltaMedia;

        // Store the current cell's ID as a transaction ID, so we know which cell we were updating. We've added a tag if
        // this is a deletion so we can handle deletion logic properly when the post returns
        $('#occurrence_transaction_id').val(transactionId);

        if (parts[4] !== "NEW" || !deleteOccurrence) { // only submit for an existing occurrence, or one with values
          var ajaxStopHandler = function() {
            $( document ).off("ajaxStop", ajaxStopHandler);
            var next = $('.occurrence-row').find('.queued').not('.savingO');
            if (next.length > 0) {
              occurrence_input_blur({target:next[0]})
//              $(next[0]).trigger('blur');
            } else {
                var next = $('.comments-row').find('.queued').not('.savingO');
                if (next.length > 0) {
                  occurrence_comment_blur({target:next[0]})
                }
            }
          };
          $( document ).ajaxStop(ajaxStopHandler);
          $('#occurrence-form').ajaxSubmit({
              dataType:  'json',
              success:   function(data, status, form){
                  // Attr ID format value-[ttlid]-[S<N>]-[SubsampleID]-[OccurrenceID]-[OccurrenceAttributeID]-[OccurrenceAttributeValueID] : variant
                  // Images ID format is images-[ttlid]-[S<N>]-[SubsampleID] : invariant
                  var transactionParts = data.transaction_id.split(':'),
                        deletion = (transactionParts[1] === 'deleted'),
                        transaction_id = transactionParts[0],
                        parts = transaction_id.split('-');

                  // In order to delete, there can be no media or attributes
                  if (checkErrors(data)) {
                      if(!deletion) { // if we are deleting the entry then we do not want to add the id and attrValId fields (they will have just been removed!)
                          if (transactionParts[2] === "Y") { // this is a new occurrence:
                              parts[4] = data.outer_id;
                              // The IDs for the attributes are variant (occurrence ID and attribute value ID change)
                              var checkId = 'value-' + parts[1] + '-' + parts[2] + '-' + parts[3] + '-NEW-';
                              $('input,select')
                                  .filter(function( index ) {
                                      return this.id.substring(0, checkId.length) == checkId;
                                  })
                                  .each(function (idx, input) {
                                      var innerParts = input.id.split('-');
                                      innerParts[4] = data.outer_id;
                                      $(input).prop('id', innerParts.join('-'));
                                  });
                              // add occurrence ID to the images td container : first part Variant
                              checkId = 'imagesTd-' + parts[1] + '-' + parts[2] + '-' + parts[3] + '-NEW';
                              $('td')
                                .filter(function( index ) {
                                    return this.id == checkId;
                                })
                                .each(function (idx, input) {
                                    var innerParts = input.id.split('-');
                                    innerParts[4] = data.outer_id;
                                    $(input).prop('id', innerParts.join('-'));
                              });
                              checkId = 'imagesButton-' + parts[1] + '-' + parts[2] + '-' + parts[3]; // invariant
                              $('button#'+checkId).prop('disabled', false);
                              // The IDs for the comments are variant (occurrence ID change)
                              var checkId = 'comment-' + parts[1] + '-' + parts[2] + '-' + parts[3] + '-NEW';
                              $('textarea')
                                  .filter(function( index ) {
                                      return this.id.substring(0, checkId.length) == checkId;
                                  })
                                  .each(function (idx, input) {
                                      var innerParts = input.id.split('-');
                                      innerParts[4] = data.outer_id;
                                      $(input).prop('id', innerParts.join('-'));
                                  });
                              checkId = 'commentsButton-' + parts[1] + '-' + parts[2] + '-' + parts[3]; // invariant
                              $('button#'+checkId).prop('disabled', false);
                          }
                          // The IDs for the comments are variant (occurrence ID change)
                          var checkId = 'comment-' + parts[1] + '-' + parts[2] + '-' + parts[3] + '-' + data.outer_id;
                          $('textarea')
                              .filter(function( index ) {
                                  return this.id.substring(0, checkId.length) == checkId;
                              })
                              .removeClass('savingO').removeClass('savingSpinner')
                              .each(function (idx, input) {
                                  if ($(input).val().length > 0)
                                      $('#commentsButton-' + parts[1] + '-' + parts[2] + '-'  +parts[3])
                                          .parent()
                                          .find('.badge')
                                          .show();
                                  else
                                      $('#commentsButton-' + parts[1] + '-' + parts[2] + '-'  +parts[3])
                                          .parent()
                                          .find('.badge')
                                          .hide();
                              });
                          if (transactionParts[3] === "Y") {
                              // this is a change in attribute status: at least one has gone from value to empty or visa versa
                              var attributes = [];
                              $.each(formOptions.settings.occurrence_attributes, function(idx, occurrenceAttributeID) {
                                  attributes[occurrenceAttributeID] = 'NEW';
                              });
                              // With multiple attributes and media, can't tell which is which from the data returned.
                              var attrData = {
                                  'auth_token': indiciaData.read.auth_token,
                                  'nonce': indiciaData.read.nonce,
                                  'mode': 'json',
                                  'view': 'list',
                                  'occurrence_id': data.outer_id
                              };
                              $.ajax({
                                  'url': indiciaData.warehouseUrl+'index.php/services/data/occurrence_attribute_value',
                                  'data': attrData,
                                  'dataType': 'jsonp',
                                  'success': function(attrData) {
                                      $.each(attrData, function (idx, attr) {
                                          attributes[attr.occurrence_attribute_id] = attr.id;
                                      });
                                      $.each(formOptions.settings.occurrence_attributes, function(idx, occurrenceAttributeID) {
                                          var checkId = 'value-' + parts[1] + '-' + parts[2] + '-' + parts[3] + '-' + parts[4] + '-' + occurrenceAttributeID + '-';
                                          $('input,select')
                                              .filter(function( index ) {
                                                    return this.id.substring(0, checkId.length) == checkId;
                                                })
                                                .removeClass('savingO').removeClass('savingSpinner')
                                                .each(function (idx, input) {
                                                  var innerParts = input.id.split('-');
                                                  if (innerParts[6] != attributes[occurrenceAttributeID]) {
                                                      innerParts[6] = attributes[occurrenceAttributeID];
                                                      $(input).prop('id', innerParts.join('-'));
                                                  }
                                              });
                                      });
                                  }
                              });
                          } else {
                              $.each(formOptions.settings.occurrence_attributes, function(idx, occurrenceAttributeID) {
                                  var checkId = 'value-' + parts[1] + '-' + parts[2] + '-' + parts[3] + '-' + parts[4] + '-' + occurrenceAttributeID + '-';
                                  $('input,select')
                                      .filter(function( index ) {
                                          return this.id.substring(0, checkId.length) == checkId;
                                      })
                                      .removeClass('savingO').removeClass('savingSpinner')
                              });
                          }
                          if (transactionParts[4] === "Y") {
                              // this is a change in media status: at least one has been added or remved
                              // With multiple attributes and media, can't tell which is which from the data returned.
                              var attrData = {
                                  'auth_token': indiciaData.read.auth_token,
                                  'nonce': indiciaData.read.nonce,
                                  'mode': 'json',
                                  'view': 'list',
                                  'occurrence_id': data.outer_id
                              };
                              $.ajax({
                                  'url': indiciaData.warehouseUrl+'index.php/services/data/occurrence_medium',
                                  'data': attrData,
                                  'dataType': 'jsonp',
                                  'success': function(mediaData) {
                                      $.each(mediaData, function (idx, media) {
                                          // The path are invariant: they are set at creation and do not change for that element.
                                          var path = $('#images-' + parts[1] + '-' + parts[2] + '-' + parts[3]
                                              + ' [value=' + media.path.replace('.', '\\.') + ']');
                                          path.parent().find('[id*=\\:occurrence_medium\\:id\\:]')
                                              .filter(function( index ) {
                                                  return this.value === '';
                                              })
                                              .val(media.id);
                                          path.parent().find('[id^=isNew-]').val('f');
                                      });
                                      $('#images-' + parts[1] + '-' + parts[2] + '-' + parts[3])
                                          .find('[id*=\\:occurrence_medium\\:deleted\\:]')
                                          .filter(function( index ) {
                                              return this.value === 't';
                                          })
                                          .closest('.mediafile')
                                          .remove();
                                      if (mediaData.length > 0)
                                          $('#imagesButton-' + parts[1] + '-' + parts[2] + '-'  +parts[3])
                                              .parent()
                                              .find('.badge')
                                              .html(mediaData.length)
                                              .show();
                                  }
                              });
                          }
                      } else { // Deleted occurrence: remove occurrence ID and attribute value ID from all attribute IDs
                          var checkId = 'value-' + parts[1] + '-' + parts[2] + '-' + parts[3] + '-';
                          $('input,select')
                              .filter(function( index ) {
                                    return this.id.substring(0, checkId.length) == checkId;
                                })
                                .each(function (idx, input) {
                                  var innerParts = input.id.split('-');
                                  innerParts[4] = "NEW";
                                  innerParts[6] = "NEW";
                                  $(input).prop('id', innerParts.join('-')).removeClass('savingO').removeClass('savingSpinner');
                                });
                          // Deleted occurrence: remove any images, remove occurrence ID from the images td container
                          $('#images-' + parts[1] + '-' + parts[2] + '-' + parts[3] + ' mediafile').remove();

                          checkId = 'imagesTd-' + parts[1] + '-' + parts[2] + '-' + parts[3] + '-';
                          $('td')
                                .filter(function( index ) {
                                    return this.id.substring(0, checkId.length) == checkId;
                                })
                                .each(function (idx, input) {
                                  var innerParts = input.id.split('-');
                                  innerParts[4] = "NEW";
                                  $(input).prop('id', innerParts.join('-'));
                                });
                          $('#imagesButton-' + parts[1] + '-' + parts[2] + '-' + parts[3]).prop('disabled', true).parent().find('.badge').html('0').hide();

                          $('#commentsButton-' + parts[1] + '-' + parts[2] + '-' + parts[3]).prop('disabled', true).parent().find('.badge').hide();
                          // The IDs for the comments are variant (occurrence ID change)
                          var checkId = 'comment-' + parts[1] + '-' + parts[2] + '-' + parts[3] + '-';
                          $('textarea')
                              .filter(function( index ) {
                                  return this.id.substring(0, checkId.length) == checkId;
                              })
                              .each(function (idx, input) {
                                  var innerParts = input.id.split('-');
                                  innerParts[4] = 'NEW';
                                  $(input).prop('id', innerParts.join('-')).val('').removeClass('savingO').removeClass('savingSpinner');
                              });
                      }
                  }
                }
          });
        } else {
          $('#' + targetID).removeClass('savingO').removeClass('savingSpinner');
          $.each(formOptions.settings.occurrence_attributes, function(idx, occurrenceAttributeID) {
              var checkId = 'value-' + parts[1] + '-' + parts[2] + '-' + parts[3] + '-NEW-'
                      + occurrenceAttributeID + '-';
              $('input,select')
                  .filter(function( index ) {
                      return this.id.substring(0, checkId.length) == checkId;
                  })
                  .each(function(idx, input) {
                      $(input).removeClass('savingO')
                  });
          });
          var checkId = 'comment-' + parts[1] + '-' + parts[2] + '-' + parts[3] + '-' + parts[4];
          $('textarea')
              .filter(function( index ) {
                  return this.id.substring(0, checkId.length) == checkId;
              })
              .each(function(idx, input) {
                  $(input).removeClass('savingO')
              });
          // No chance for state to change: all not queued or saving.
          // any other occurrences will be triggered by their own ajaxStop handler.
        }
        $('table.species_grid').trigger('columnschange', [null]);
    }


    getRowTotal = function (cell, attributeId) {
        var row = $(cell).parents('tr:first')[0];
        var total=0, cellValue;
        $(row).find('td.col-' + attributeId +' .count-input').each(function() {
            cellValue = parseInt($(this).val());
            total += isNaN(cellValue) ? 0 : cellValue;
        });
        return total;
    }

    setTotals = function (cell) {
        var tbody = $(cell).closest('tbody')[0];
        var row = $(cell).parents('tr:first')[0];
        // ID format value-[ttlid]-[S<N>]-[SubsampleID]-[OccurrenceID]-[OccurrenceAttributeID]-[OccurrenceAttributeValueID]
        var parts = cell.id.split('-');

        // row total = total for that species across all sections.
        $(row).find('.row-total-'+parts[5]).html(getRowTotal(cell, parts[5]));

        // column total = total for that section across all species.
        var total = 0;
        $(tbody).find('td.section-'+parts[2]+'.col-'+parts[5]+' .count-input').each(function() {
            cellValue = parseInt($(this).val());
            total += isNaN(cellValue) ? 0 : cellValue;
        });
        $(tbody).find('tr.totals-row td.section-' + parts[2] + '.col-' + parts[5]).html(total);
        // total total = total for all sections across all species.
        var total = 0;
        $(tbody).find('td.col-'+parts[5]+' .count-input').each(function() {
            cellValue = parseInt($(this).val());
            total += isNaN(cellValue) ? 0 : cellValue;
        });
        $(tbody).find('tr.totals-row .total-' + parts[5]).html(total);
    }

    addSpeciesToGrid  = function (taxonList, speciesTbodySelector, tabIDX){
        // this function is given a list of species from the occurrences and if they are in the taxon list
        // adds them to a table : ordering is handled by the ajaxStop handler.
        // any that are left are swept up by another function.
        $.each(taxonList, function(idx, species) {
              var existing = false;
              $.each(formOptions.sections, function(idx, section) {
                var key = formOptions.subSamples[section.code] + ':' + species.taxon_meaning_id;
                if (typeof formOptions.existingOccurrences[key] !== "undefined")
                      existing = true;
              });
              if (existing === true ||
                      species.taxon_rank_sort_order === null ||
                      typeof formOptions.speciesTabDefinition[tabIDX].taxon_min_rank === "undefined" ||
                      species.taxon_rank_sort_order >= formOptions.speciesTabDefinition[tabIDX].taxon_min_rank) {
                addGridRow(species, speciesTbodySelector, tabIDX, true);
              }
        });
        $('table.species_grid').trigger('columnschange', [null]);
      }

    addGridRow = function (species, speciesTbodySelector, tabIDX, bulk){
        var name, title, row, imagesRow, commentsRow, isNumber;

        if ($('#row-' + species.taxon_meaning_id).length > 0) {
            $('#row-' + species.taxon_meaning_id).removeClass('possibleRemove').show();
            return;
        }
        switch(formOptions.taxon_column) {
            case 'preferred_taxon':
                name = (species.preferred_language_iso==='lat' ? '<em>'+species.preferred_taxon+'</em>' : species.preferred_taxon);
                title = (species.default_common_name!==null ? ' title="'+species.default_common_name+'"' : '');
                break;
            default: // taxon
                name = (species.default_common_name!==null ? species.default_common_name : (species.preferred_language_iso==='lat' ? '<em>'+species.taxon+'</em>' : species.taxon));
                title = (name.replace(/<em>/,'').replace(/<\/em>/,'') != species.preferred_taxon ? ' title="'+species.preferred_taxon+'"' : '');
                break;
        }
        row = $('<tr id="row-' + species.taxon_meaning_id + '" class="occurrence-row"><td class="taxon" ' + title + '>' + name +
            '</td></tr>').data('species', species);
        
        $(speciesTbodySelector).append(row);
        if (formOptions.settings.occurrence_images) {
          imagesRow = $('<tr id="images-row-' + species.taxon_meaning_id + '" class="images-row" style="display: none;"><td></td></tr>');
          $(speciesTbodySelector).append(imagesRow);
        }
        if (formOptions.settings.occurrence_comments) {
          commentsRow = $('<tr id="comments-row-' + species.taxon_meaning_id + '" class="comments-row" style="display: none;"><td></td></tr>');
          $(speciesTbodySelector).append(commentsRow);
        }

        $(speciesTbodySelector).append($(speciesTbodySelector + ' tr.totals-row'));
        
        var rowContents = '';
        var rowValues = [];
        var commentsContents = '';
        var commentsValues = [];
        var imagesContents = '';
        var imagesControls = [];
        
        $.each(formOptions.sections, function(idx, section) {
              $.each(formOptions.settings.occurrence_attributes, function(idx, attributeId) {
                var attribute = formOptions.occurrenceAttributes[attributeId],
                    key = formOptions.subSamples[section.code] + ':' + species.taxon_meaning_id,
                    isNumber = formOptions.occurrenceAttributeControls[attributeId].indexOf('number:true') >= 0,
                    cell, myCtrl, occId, valId, ctrlId, val = '';

                rowContents = rowContents + '<td class="section-' + section.code + ' col-' + attributeId + ' occ-cell" '
                  + (formOptions.format === 'complex' && $('input[name=section]:checked').val() !== section.code ? ' style="display: none"' : '') +
                  '>';
                
                // find current value if there is one
                // the key is the combination of sample id and ttl meaning id that an existing value would be stored as
                // actual control has to be first in cell for cursor keys to work.
                // ID format is value-[ttlid]-[S<N>]-[SubsampleID]-[OccurrenceID]-[OccurrenceAttributeID]-[OccurrenceAttributeValueID]
                if (typeof formOptions.existingOccurrences[key] !== "undefined") {
                    formOptions.existingOccurrences[key]['processed'] = true;
                    var occId = formOptions.existingOccurrences[key]['occurrence_id'];
                    var valId = formOptions.existingOccurrences[key]['value_id_' + attributeId] === null ? 'NEW' :
                        formOptions.existingOccurrences[key]['value_id_' + attributeId];
                    val = formOptions.existingOccurrences[key]['value_' + attributeId] === null ? '' :
                        formOptions.existingOccurrences[key]['value_' + attributeId];
                    // need to use existing species ttlid (which may or may not be preferred)
                    ctrlId = 'value-' + formOptions.existingOccurrences[key]['ttl_id'] + '-' + section.code +
                        '-' + formOptions.subSamples[section.code] + '-' + occId + '-' + attributeId + '-' + valId;
                } else {
                    ctrlId = 'value-' + species.preferred_taxa_taxon_list_id + '-' + section.code +
                       '-' + formOptions.subSamples[section.code] + '-NEW-' + attributeId + '-NEW';
                    val = undefined; // use control default.
                }
                myCtrl = formOptions.occurrenceAttributeControls[attributeId].replace('occAttr:'+attributeId, ctrlId)
                        .replace('class="', isNumber ? 'class="count-input ' : 'class="non-count-input ');
                rowContents = rowContents + myCtrl + '</td>';
                rowValues.push([ctrlId, val, species.taxon_meaning_id]);
              });

            var colspan = formOptions.settings.occurrence_attributes.length +
                (formOptions.settings.occurrence_images ? 1 : 0) +
                (formOptions.settings.occurrence_comments ? 1 : 0);
            
            if (formOptions.settings.occurrence_images) {
                // ID format is imagesButton-[ttlid]-[S<N>]-[SubsampleID]
                var key = formOptions.subSamples[section.code] + ':' + species.taxon_meaning_id,
                    style = $('input[name=section]:checked').val() !== section.code ? 'style="display: none;"' : '',
                    buttonId = 'imagesButton-' + species.preferred_taxa_taxon_list_id + '-' + section.code + '-' +
                          formOptions.subSamples[section.code],
                    disabled = (typeof formOptions.existingOccurrences[key] !== "undefined") ? '' : ' disabled="disabled" ';

                rowContents = rowContents + '<td class="section-' + section.code + ' image-button-cell" ' + style + '>' +
                        '<button id="' + buttonId + '" class="btn btn-primary"' + disabled + '>Images</button>' +
                        ' <span class="badge badge-info" style="display: none;">0</span>' +
                        '</td>';
                
                // Because of the way all the IDs are generated in the file-box, it is not practical to add the occurrence id
                // to the file-box ctrlId - we don't want to have to swap the value to and from NEW within all the generated fields
                // instead we put it onto the container td
                // ID format is images-[ttlid]-[S<N>]-[SubsampleID]
                // ID format is imagesTd-[ttlid]-[S<N>]-[SubsampleID]-[OccurrenceID]
                var ctrlId = 'images-' + species.preferred_taxa_taxon_list_id + '-' + section.code + '-' +
                          formOptions.subSamples[section.code],
                    tdId = 'imagesTd-' + species.preferred_taxa_taxon_list_id + '-' + section.code + '-' +
                        formOptions.subSamples[section.code] + '-' +
                        (typeof formOptions.existingOccurrences[key] !== "undefined" ?
                          formOptions.existingOccurrences[key]['occurrence_id'] : 'NEW');

                imagesContents = imagesContents +
                    '<td id="' + tdId + '" class="section-' + section.code + ' image-cell" colspan=' + colspan + ' style="display: none;">' +
                    '<div class="file-box" id="' + ctrlId + '"></div></td>';

                imagesControls.push([ctrlId, key, buttonId]);
            }
            if (formOptions.settings.occurrence_comments) {
                var key = formOptions.subSamples[section.code] + ':' + species.taxon_meaning_id,
                    disabled = (typeof formOptions.existingOccurrences[key] !== "undefined") ? '' : ' disabled="disabled" ',
                    occId = 'NEW',
                    val = '',
                    cntrlId, ttlid, buttonId, style;

                if (typeof formOptions.existingOccurrences[key] !== "undefined") {
                    occId = formOptions.existingOccurrences[key]['occurrence_id'];
                    val = formOptions.existingOccurrences[key]['comment'] === null ? '' :
                        formOptions.existingOccurrences[key]['comment'];
                    // need to use existing species ttlid (which may or may not be preferred)
                    ttlid = formOptions.existingOccurrences[key]['ttl_id'];
                } else {
                    ttlid = species.preferred_taxa_taxon_list_id;
                }
                
                // ID format is commentsButton-[ttlid]-[S<N>]-[SubsampleID]
                buttonId = 'commentsButton-' + ttlid + '-' + section.code + '-' + formOptions.subSamples[section.code];
                style = $('input[name=section]:checked').val() !== section.code ? 'style="display: none;"' : '';

                rowContents = rowContents + '<td class="section-' + section.code + ' comment-button-cell" ' + style + '>' +
                        '<button id="' + buttonId + '" class="btn btn-primary"' + disabled + '>' +
                        formOptions.langStrings.Comments +
                        '</button> ' +
                        '<span class="badge badge-info" ' + (val === '' ? 'style="display: none;"' : '') + '> ' +
                        formOptions.langStrings.Yes + '</span>' +
                        '</td>';

                // ID format is comment-[ttlid]-[S<N>]-[SubsampleID]-[OccurrenceID]
                cntrlId = 'comment-' + ttlid + '-' + section.code + '-' + formOptions.subSamples[section.code] + '-' + occId;
                commentsContents = commentsContents +
                        '<td class="section-' + section.code + ' comment-cell" colspan=' + colspan + ' style="display: none;">' +
                        '<div id="" class="form-group ctrl-wrap">' +
                        '<label for="' + cntrlId + '">' + formOptions.langStrings.commentsLabel + ':</label>' +
                        '<textarea id="' + cntrlId + '" name="' + cntrlId + '" class="form-control" rows="4">' +
                        '</textarea>' +
                        '<p class="helpText">' +
                        formOptions.langStrings.commentsHelp + 
                        '</p></div></td>';
                if (val !== '') {
                    commentsValues.push([cntrlId, val]);
                }
            }
        });

        $.each(formOptions.settings.occurrence_attributes, function(idx, attributeId) {
            if (formOptions.occurrenceAttributeControls[attributeId].indexOf('number:true') >= 0) {
                rowContents = rowContents + '<td class="row-total-' + attributeId + '"></td>';
                imagesContents = imagesContents + '<td></td>';
                commentsContents = commentsContents + '<td></td>';
            }
        });
        

        row.append(rowContents);
        $.each(rowValues, function(idx, rowValue) {
          if (typeof rowValue[1] !==  'undefined') {
            $('#' + rowValue[0]).val(rowValue[1]);
          }
          $('#' + rowValue[0]).data('previous', $('#' + rowValue[0]).val()).data('taxon_meaning_id', rowValue[2]);
        });

        if (formOptions.settings.occurrence_images) {
	        imagesRow.append(imagesContents);
	        var mediaTypes = indiciaData.uploadSettings.mediaTypes;
	        var settingsToClone = [
	            'uploadScript', 'destinationFolder', 'relativeImageFolder',
	            'resizeWidth', 'resizeHeight', 'resizeQuality',
	            'caption', 'addBtnCaption', 'msgPhoto', 'msgFile',
	            'msgLink', 'msgNewImage', 'msgDelete'
	        ];
	        var opts = {
	            caption: (mediaTypes.length === 1 && mediaTypes[0] === 'Image:Local') ? formOptions.langStrings.Photos : formOptions.langStrings.Files,
	            autoupload: '1',
	            msgUploadError: formOptions.langStrings.msgUploadError,
	            msgFileTooBig: formOptions.langStrings.msgFileTooBig,
	            runtimes: 'html5,flash,silverlight,html4',
	            imagewidth: '250',
	            jsPath: indiciaData.uploadSettings.jsPath,
	            maxUploadSize: formOptions.maxUploadSize,
	            autopick: true,
	            mediaTypes: mediaTypes,
	            finalImageFolder : indiciaData.warehouseUrl + 'upload/'
	        };
	        if (typeof buttonTemplate!=='undefined') {
	            opts.buttonTemplate=buttonTemplate;
	        }
	        if (typeof file_boxTemplate!=='undefined') {
	            opts.file_boxTemplate=file_boxTemplate;
	        }
	        if (typeof file_box_initial_file_infoTemplate!=='undefined') {
	            opts.file_box_initial_file_infoTemplate=file_box_initial_file_infoTemplate;
	        }
	        if (typeof file_box_uploaded_imageTemplate!=='undefined') {
	            opts.file_box_uploaded_imageTemplate=file_box_uploaded_imageTemplate;
	        }
	        // Copy settings from indiciaData.uploadSettings
	        $.each(settingsToClone, function() {
	              if (typeof indiciaData.uploadSettings[this] !== 'undefined') {
	                opts[this]=indiciaData.uploadSettings[this];
	              }
	        });
	        $.each(imagesControls, function(idx, imagesControl) {
	            var mediaTable = imagesControl[0] + ':occurrence_medium';
	            var myOpts = $.extend({}, opts);
	            myOpts.table = mediaTable;
	            myOpts.container = imagesControl[0];
	            if (typeof formOptions.existingOccurrences[imagesControl[1]] !== "undefined") {
	                (function(options, key, ctrlId, buttonId) {
	                    // var options = $.extend({}, opts);
	                    var attrData = {
	                        'auth_token': indiciaData.read.auth_token,
	                        'nonce': indiciaData.read.nonce,
	                        'mode': 'json',
	                        'view': 'list',
	                        'occurrence_id': formOptions.existingOccurrences[key]['occurrence_id']
	                    };
	                    $.ajax({
	                        'url': indiciaData.warehouseUrl+'index.php/services/data/occurrence_medium',
	                        'data': attrData,
	                        'dataType': 'jsonp',
	                        'success': function(mediaData) {
	                            options.existingFiles = mediaData;
	                            $('#' + ctrlId).uploader(options);
	                            if (mediaData.length > 0) {
	                                $('#' + buttonId).parent().find('.badge').html(mediaData.length).show();
	                            }
	                        }
	                    });
	                })(myOpts, imagesControl[1], imagesControl[0], imagesControl[2]);
	            } else {
	                $('#' + imagesControl[0]).uploader(myOpts);
	            }
	        });
        }

        if (formOptions.settings.occurrence_comments) {
	        commentsRow.append(commentsContents);
	        $.each(commentsValues, function(idx, comment) {
	          $('#'+comment[0]).val(comment[1]);
	        });
        }

        $(speciesTbodySelector).append($(speciesTbodySelector + ' tr.totals-row'));
        row.find('input.count-input').each(function(idx, elem) {
            setTotals(elem);
        });
        row.find('input:not(:checkbox)')
            .keydown(occ_keydown)
            .focus(general_focus)
            .change(input_change)
            .blur(occurrence_input_blur);
        row.find('input:checkbox,select')
            .keydown(occ_keydown)
            .focus(general_focus)
            .change(occurrence_immediate_save);
        row.find('button')
            .keydown(occ_keydown)
            .focus(general_focus);
        if (formOptions.settings.occurrence_comments) {
            commentsRow.find('textarea')
                .keydown(occ_keydown)
                .focus(general_focus)
                .change(input_change)
                .blur(occurrence_comment_blur);
        }
        // images button is added async so can't be done now.
        formOptions.existingOccurrences[':' + species.taxon_meaning_id] = {'processed' : true, 'taxon_meaning_id' : ''+species.taxon_meaning_id};
        if (formOptions.serverRangeVerification) {
            var urlSep = indiciaData.ajaxUrl.indexOf('?') === -1 ? '?' : '&';

            $.getJSON(
                indiciaData.ajaxUrl + '/check_verification_rules/' + indiciaData.nid +
                    urlSep +'location_id=' + formOptions.parentLocId +
                    '&taxon_meaning_id=' + species.taxon_meaning_id +
                    '&date=' + formOptions.parentSampleDate + // Assummed YYYY-MM-DD
                    '&nonce=' + indiciaData.read.nonce + '&auth_token=' + indiciaData.read.auth_token,
                null,
                function (data) {
                    if (data.warnings.length > 0) {
                        for (var i = 0; i < data.warnings.length; i++) {
                            data.warnings[i] = data.warnings[i] + (data.warnings[i].slice(-1) === '.' ? '' : '.');
                        }
                        $('#row-' + data.taxon_meaning_id).addClass('range-warning');
                        $('#row-' + data.taxon_meaning_id+' td:first')
                            .append('<span class="range-warning-icon" title="' +
                                data.warnings.join(' ') + '"></span>');
                    }
                    $('table.species_grid').trigger('columnschange', [null]);
                }
            );
        }
        if (!bulk) {
          $('table.species_grid').trigger('columnschange', [null]);
        }
    }

    process = function (N) {
        var TaxonData,
            filterField = '',
            filter = false,
            processed = false,
            subsetFilterField = 'taxon_meaning_id',
            subsetFilter = formOptions.existingTaxonMeaningIDs,
            extraFilterField = '',
            extraFilter = false;

        $('#grid-loading').show();

        var ajaxStopHandler = function() {
            $( document ).off("ajaxStop", ajaxStopHandler);
            if (formOptions.format === 'complex' || N==0) {
              $('#species_grid_supersample_attributes_' + N).find('input:visible,select').change();
            }
            $('input[name=species-sort-order-' + N + ']:checked').change();
            process(N + 1);
        };

        var processSpecies = function (N, TaxonData, filterField, filter, subsetFilterField, subsetFilter) {
          var offset = 0,
              limit = 100,
              hasQuery = false;
          var queryData = {"in":{}};
          var ajaxData = Object.assign({}, TaxonData);


          // filter holds the tab definition
          // subset holds any restrictions like list of existing taxa meanings.
          // if fields are same do intersect here: if different, allow warehouse to do that for us.
          if (filter) {
            hasQuery = true;
            if (subsetFilter) {
              if (filterField === subsetFilterField) {
                filter = filter.filter(value => -1 !== subsetFilter.indexOf(value))
                  .filter(function (value, index, self) { return self.indexOf(value) === index });
              } else {
                queryData["in"][filterField] = filter;
                limit = 50;
                filterField = subsetFilterField;
                filter = subsetFilter;
              }
            } // else no subset filter, so filter flows through.
          } else if (subsetFilter) { // No filter
            hasQuery = true;
            filterField = subsetFilterField;
            filter = subsetFilter;
          }
          do {
            if (hasQuery) {
                queryData["in"][filterField] = filter.slice(offset, offset+limit);
                ajaxData.query = JSON.stringify(queryData);
            }
            $.ajax({
                'url': indiciaData.warehouseUrl+'index.php/services/data/taxa_taxon_list',
                'data': ajaxData,
                'dataType': 'jsonp',
                'success': function(data) {
                    addSpeciesToGrid(data, "tbody#species_grid_" + N, N);
                }
            });
            offset = offset + limit;
          } while (filter && offset < filter.length);
        }

        if (N >= formOptions.speciesTabDefinition.length) {
            $('#grid-loading').hide();
            return;
        }

        TaxonData = {
            'taxon_list_id' : formOptions.speciesTabDefinition[N].taxon_list_id,
            'preferred' : 't',
            'auth_token' : indiciaData.read.auth_token,
            'nonce' : indiciaData.read.nonce,
            'mode' : 'json',
            'allow_data_entry' : 't',
            'view' : 'cache',
            'orderby' : $('[name=species-sort-order-'+N+']:checked').val()
        };
        if(typeof formOptions.speciesTabDefinition[N].taxon_filter_field != "undefined") {
          filterField = formOptions.speciesTabDefinition[N].taxon_filter_field;
          filter = formOptions.speciesTabDefinition[N].taxon_filter;
        }
        switch ($('[name=species-list-'+N+']:checked').val()) {
          case 'branch':
            subsetFilter = formOptions.branchTaxonMeaningIDs[N].concat(formOptions.existingTaxonMeaningIDs);
            break;
          case 'full': // = all values in list: by definition will include all existing data on this sample.
            subsetFilterField = '';
            subsetFilter = false;
            break;
          case 'common': // = all in commonlist, plus existing
            subsetFilter = formOptions.commonTaxonMeaningIDs[N].concat(formOptions.existingTaxonMeaningIDs);
            break;
          case 'mine': // = all in mylist, plus existing
            subsetFilter = formOptions.myTaxonMeaningIDs.concat(formOptions.existingTaxonMeaningIDs);
            break;
          case 'here': // = all values entered against this transect: by definition will include all existing data on this sample.
                       // but just in case of caching issues...
          default:
            subsetFilter = formOptions.allTaxonMeaningIDsAtTransect.concat(formOptions.existingTaxonMeaningIDs);
            break;
        }
        if (subsetFilterField === '' || subsetFilter.length > 0) {
          $( document ).ajaxStop(ajaxStopHandler);
          processSpecies(N, TaxonData, filterField, filter, subsetFilterField, subsetFilter);
          if (extraFilter) {
            processSpecies(N, TaxonData, filterField, filter, extraFilterField, extraFilter);
          }
        } else if (extraFilter) {
          $( document ).ajaxStop(ajaxStopHandler);
          processSpecies(N, TaxonData, filterField, filter, extraFilterField, extraFilter);
        } else {
          process(N + 1);
          if (formOptions.format === 'complex' || N==0) {
            $('#species_grid_supersample_attributes_' + N).find('input:visible,select').change();
          }
        }
    }

    checkErrors = function (data) {
        if (typeof data.error !== "undefined") {
            if (typeof data.errors !== "undefined") {
                $.each(data.errors, function(idx, error) {
                    alert(error);
                });
            } else {
                alert('An error occured when trying to save the data');
            }
            // data.transaction_id stores the last cell at the time of the post.
            $('input,select')
                .filter(function( index ) {
                    return this.id == data.transaction_id;
                })
                .each(function (index, elem) {
                    $(elem).focus();
                    $(elem).select();
                })
            return false;
        } else {
            return true;
        }
    }

    // Code to cover key processing.

    // OK
    super_sample_keydown_simple = function (evt) {
      // in the simple form type, the sub sample attributes are part of the main species grid
      // above, and there is the only one table.
      // supersample attributes are single, in their own div.
      // simplified: assuming single checkbox input at the moment.
      var targetRow = $(evt.target).parents('.species_grid_supersample_attributes'),
          targetInput = [];

      if (evt.keyCode===13 || evt.keyCode===40 || evt.keyCode===39) {
        // down arrow or enter key or right arrow
        targetRow = targetRow.nextAll('.species_grid').not('.sticky-header').filter(':first');
        targetInput = targetRow.find('tbody tr').filter(':first').find("input,select").filter(":visible").filter(':first');
      } else if (evt.keyCode===38 || evt.keyCode===37) {
        // No point going up arrow or left arrow
        return false;
      }

      if (targetInput.length > 0) {
        $(targetInput).get()[0].focus();
        return false;
      }
    }

    // OK
    super_sample_keydown_complex = function (evt) {
      // in the complex form type, the sub sample attributes are separated above, and there
      // are more than one table in the form.
      // supersample attributes are single, in their own div.
      // simplified: assuming single checkbox input at the moment.
      var targetRow = $(evt.target).parents('.species_grid_supersample_attributes'),
          targetInput = [];

      if (evt.keyCode===13 || evt.keyCode===40 || evt.keyCode===39) {
        // down arrow or enter key or right arrow
        targetInput = getNextGridControls(targetRow, null, null);
      } else if (evt.keyCode===38 || evt.keyCode===37) {
        // up arrow or enter key or left arrow
        targetInput = getPrevGridTitle(targetRow, null, null);
      }

      if (targetInput.length > 0) {
        $(targetInput).get()[0].focus();
        return false;
      }
    }

    // OK
    sub_sample_keydown = function (evt) {
      // in the complex form type, the sub sample attributes are separated above, and there
      // are more than one species tables in the form.
      var targetRow = $(evt.target).closest('tr'),
          targetInput = [],
          parts=evt.target.id.split('-');

      if (evt.keyCode===13 || (evt.target.tagName != "SELECT" && evt.keyCode===40)) {
        // down arrow or enter key: Down arrow in a select special case, as steps through options
        targetInput = getNextSubSampleRow(evt.target, parts[1]);
      } else if (evt.target.tagName != "SELECT" && evt.keyCode===38) {
        // up arrow : up arrow in a select special case, as steps through options
        targetInput = getPrevSubSampleRow(evt.target, parts[1]);
      } else if (evt.keyCode===39 && (evt.target.tagName == "SELECT" ||
          evt.target.type == "checkbox" || evt.target.selectionEnd >= evt.target.value.length)) {
        // right arrow
        targetInput = getNextSubSampleAttr(evt.target);
      } else if (evt.keyCode===37 && (evt.target.tagName == "SELECT" || 
          evt.target.type == "checkbox" || evt.target.selectionStart === 0)) {
        // left arrow - move to previous cell if at start of text
        targetInput = getPrevSubSampleAttr(evt.target);
      }

      if (targetInput.length > 0) {
        $(targetInput).get()[0].focus();
        return false;
      }
    }

    occ_keydown = function (evt) {
      var targetInput=[],
          parts=evt.target.id.split('-');
          // Attr ID format value-[ttlid]-[S<N>]-[SubsampleID]-[OccurrenceID]-[OccurrenceAttributeID]-[OccurrenceAttributeValueID]

      // hack this to handle the add images button
      if ($(evt.target).filter('button.uwde-events-added').length > 0) {
        // we are in complex format: When going up/down fron images should go to the image button
        var parts2 = $(evt.target).closest('td').prop('id').split('-');
        parts[0] = 'imagesButton';
        parts[2] = parts2[2]; 
      }
      if ($(evt.target).filter('textarea').length > 0) {
        // we are in complex format: When going up/down fron comment should go to the comment button
        parts[5] = 'commentsButton';
      }

      if ((evt.target.tagName != "BUTTON" && evt.keyCode===13) ||
          (evt.target.tagName != "SELECT" && evt.keyCode===40)) {
        // down arrow or enter key (only done if not a button)
        // Down arrow in a select special case, as steps through options
        targetInput = getNextOccRow(evt.target, parts[2],
            evt.target.tagName != "BUTTON" ? parts[5] : parts[0]);
      } else if (evt.target.tagName != "SELECT" && evt.keyCode===38) {
        // up arrow : up arrow in a select special case, as steps through options
        targetInput = getPrevOccRow(evt.target, parts[2],
            evt.target.tagName != "BUTTON" ? parts[5] : parts[0]);
      } else if (evt.keyCode===39 && (evt.target.tagName == "SELECT" || evt.target.tagName == "BUTTON" ||
          evt.target.type == "checkbox" || evt.target.selectionEnd >= evt.target.value.length)) {
          // right arrow - move to next cell if at end of text
        targetInput = getNextOcc(evt.target);
      } else if (evt.keyCode===37 && (evt.target.tagName == "SELECT" || evt.target.tagName == "BUTTON" ||
          evt.target.type == "checkbox" || evt.target.selectionStart === 0)) {
        // left arrow - move to previous cell if at start of text
        targetInput = getPrevOcc(evt.target);
      }

      if (targetInput.length > 0) {
        $(targetInput).get()[0].focus();
        return false;
      }
    }

    getNextOcc = function (eventTgt) {
      var targetInput = $(eventTgt).closest('td').next('td:visible').find('input,select,button').filter(':enabled:visible').filter(':first');
      return (targetInput.length === 0 ? getNextOccRow(eventTgt, null, null) : targetInput);
    }
    getPrevOcc = function (eventTgt) {
      var targetInput = $(eventTgt).closest('td').prev('td:visible').find('input,select,button').filter(':enabled:visible').filter(':last');
      return (targetInput.length === 0 ? getPrevOccRow(eventTgt, null, null) : targetInput);
    }
    findOccElement = function(targetRow, section, attribute, direction) {
      if (targetRow.hasClass('comments-row')) {
        return targetRow.find('textarea:visible'); // only one visible
      } if (targetRow.hasClass('images-row')) {
        return targetRow.find('button:visible').filter(':first');
      } else if (section === null) {
        return targetRow.find('td:visible').find('input,select,button').filter(':enabled:visible')
            .filter(direction==='next'? ':first' : ':last');
      } else if (attribute === 'imagesButton' || attribute === 'commentsButton') {
        var targetButton = targetRow.find("button:enabled:visible")
            .filter(function(index) {
                var nextParts = this.id.split('-');
                return (nextParts[0] == attribute && nextParts[2] == section);});
        return targetButton.length > 0 ? targetButton :
            targetRow.find("input,select").filter(':enabled:visible')
           .filter(function(index) {
               var nextParts = this.id.split('-');
               return (nextParts[0] == "value" && nextParts[2] == section);})
           .first();
      } else {
        return targetRow.find("input,select")
            .filter(function(index) {
                var nextParts = this.id.split('-');
                return (nextParts[0] == "value" && nextParts[2] == section &&
                    (attribute == null || nextParts[5] == attribute));})
            .first();
      }
    }
    getNextOccRow = function (eventTgt, section, attribute) {
      var row = $(eventTgt).closest('tr'),
          targetRow = row.nextAll('tr.occurrence-row:visible,tr.comments-row:visible,tr.images-row:visible').filter(':first');
      if (targetRow.length === 0) {
        if (formOptions.format === "complex") {
          return getNextBreakRow($(eventTgt).closest('table'), section, attribute);
        }
        return [];
      } else {
        return findOccElement(targetRow, section, attribute, 'next');
      }
    }
    getPrevOccRow = function (eventTgt, section, attribute) {
      var row = $(eventTgt).closest('tr'),
          targetRow = row.prevAll('tr.occurrence-row:visible,tr.comments-row:visible,tr.images-row:visible').filter(':first');
      if (targetRow.length === 0) {
        if (formOptions.format === "complex") {
          return getPrevStickyTableHeader($(eventTgt).closest('table'), section, attribute);
        } else {
          return getPrevSubSampleSimple(eventTgt, section, attribute);
        }
      } else {
        return findOccElement(targetRow, section, attribute, 'prev');
      }
    }
    getNextOccurrence = function (current, section, attribute) {
      current = current.next();
      if (current.filter('table.species_grid').length > 0) {
        targetRow = current.find('tr.occurrence-row:visible,tr.comments-row:visible,tr.images-row:visible').filter(':first');
        if (targetRow.length === 0) {
          if (formOptions.format === "complex") {
            return getNextBreakRow(current, section, attribute);
          }
          return [];
        } else { // there will always be a occ row before a comments or images row
          return findOccElement(targetRow, section, attribute, 'next');
        }
      }
      return [];
    }
    getPrevOccurrence = function (current, section, attribute) {
      current = current.prev();
      if (current.filter('table.species_grid').length > 0) {
        targetRow = current.find('tr.occurrence-row:visible,tr.comments-row:visible,tr.images-row:visible').filter(':last');
        if (targetRow.length === 0) {
          if (formOptions.format === "complex") {
            return getPrevStickyTableHeader(current, section, attribute);
          } else {
            return [];
          }
        } else {
          return findOccElement(targetRow, section, attribute, 'next' /* sic */);
        }
      }
      return [];
    }

    getNextSubSampleAttr = function (eventTgt) { // called with the event target: input or select.
      var targetInput = $(eventTgt).closest('td').next('td:visible')
                        .find('input,select').filter(':visible').filter(':first');
      return (targetInput.length === 0 ? getNextSubSampleRow(eventTgt, null) : targetInput);
    }
    getPrevSubSampleAttr = function (eventTgt) { // called with the event target: input or select.
      var targetInput = $(eventTgt).closest('td').prev('td:visible')
                        .find('input,select').filter(':visible').filter(':last');
      return (targetInput.length === 0 ? getPrevSubSampleRow(eventTgt, null) : targetInput);
    }
    getNextSubSampleRow = function (eventTgt, section) {
      var row = $(eventTgt).closest('tr'),
          targetRow = row.nextAll('tr').filter(':first');
      if (targetRow.length === 0) {
        if (formOptions.format === "complex") {
          return getNextBreakRow($(eventTgt).closest('table').parent(), section, null);
        } else {
          return $(eventTgt).closest('tbody').nextAll(':visible')
              .filter(':first').find("input,select").filter(':visible')
              .filter(function(index) {
                var nextParts = this.id.split('-');
                return (nextParts[0] == "value" && (section === null || nextParts[2] == section));})
              .first();
        }
      } else {
        return targetRow.find("input,select")
            .filter(function(index) {
              var nextParts = this.id.split('-');
              return (nextParts[0] == "subSmpAttr" && (section === null || nextParts[1] == section));})
            .first();
      }
    }
    getPrevSubSampleRow = function (eventTgt, section) {
      var row = $(eventTgt).closest('tr'),
          targetRow = row.prevAll('tr:visible').filter(':first');
      if (targetRow.length === 0) {
        if (formOptions.format === "complex") {
          return getPrevStickyTableHeader($(eventTgt).closest('table').parent(), section, null);
        } else {
          return getPrevStickyTableHeader($(eventTgt).closest('table'), section, null);
        }
      } else {
        return targetRow.find("input,select")
              .filter(function(index) {
                  var nextParts = this.id.split('-');
                  return (nextParts[0] == "subSmpAttr" && (section === null || nextParts[1] == section));})
              .last();
      }
    }
    getNextSubSample = function (current, section, attribute) {
      current = current.next();
      if (current.find('table.species_grid').length > 0) {
        targetRow = current.find('tr:visible').filter(':first');
        if (targetRow.length === 0) {
          if (formOptions.format === "complex") {
            return getNextBreakRow(current, section, attribute);
          }
          return [];
        } else {
          if (section != null) {
            return targetRow.find("input,select")
              .filter(function(index) {
                  var nextParts = this.id.split('-');
                  return (nextParts[0] == "subSmpAttr" && nextParts[1] == section);})
              .first();
          } else {
            return targetRow.find('td:visible').find('input,select').filter(':first');
          }
        }
      }
      return [];
    }
    getPrevSubSampleComplex = function (current, section, attribute) {
      // From complex
      current = current.prev();
      if (current.find('table.species_grid').length > 0) {
        targetRow = current.find('tr:visible').filter(':last');
        if (targetRow.length === 0) {
          return [];
        } else {
          if (section != null) {
            return targetRow.find("input,select")
              .filter(function(index) {
                  var nextParts = this.id.split('-');
                  return (nextParts[0] == "subSmpAttr" && nextParts[1] == section);})
              .last();
          } else {
            return targetRow.find('td:visible').find('input,select').filter(':last');
          }
        }
      }
      return [];
    }
    getPrevSubSampleSimple = function (current, section, attribute) {
      // From simple: within the same table, up from a species grid entry.
      // we simplify by assuming that there are subsample attribute
      targetRow = $('#global_subsample_attributes tr:last');
      if (targetRow.length > 0) {
        if (section != null) {
          return targetRow.find("input,select")
            .filter(function(index) {
                var nextParts = this.id.split('-');
                return (nextParts[0] == "subSmpAttr" && nextParts[1] == section);})
            .last();
        } else {
          return targetRow.find('td:visible').find('input,select').filter(':last');
        }
      }
      return [];
    }

    getNextBreakRow = function (current, section, attribute) {
      // only used in complex format
      current = current.next();
      if (current.filter('hr').length > 0) {
        return getNextGridTitle(current, section, attribute); 
      }
      return [];
    }
    getPrevBreakRow = function (current, section, attribute) {
      // only used in complex format
      current = current.prev();
      if (current.filter('hr').length > 0) {
        if (current.prev().find('tbody.species_grid').length>0) {
          return getPrevOccurrence(current, section, attribute);
        } else {
          return getPrevSubSampleComplex(current, section, attribute);
        }
      }
      return [];
    }
    getNextGridTitle = function (current, section, attribute) {
      current = current.next();
      if (current.filter('div.species_grid_title').length > 0) {
        return getNextSuperSampleAttr(current, section, attribute); 
      }
      return [];
    }
    getPrevGridTitle = function (current, section, attribute) {
      current = current.prev();
      if (current.filter('div.species_grid_title').length > 0) {
        return getPrevBreakRow(current, section, attribute); 
      }
      return [];
    }
    getNextSuperSampleAttr = function (current, section, attribute) {
      current = current.next();
      if (current.filter('div.species_grid_supersample_attributes').length > 0) {
        if (current.find('div').length > 0) { // div.species_grid_supersample_attributes
          // next supersample exists, so use this to find targetInput
          // first parts are invariant so can use id attributes rather than props.
          return current.find("input,select").filter("[id^='superSampleAttr-']:visible").filter(':first');
        } else {
          // No supersample attributes: grid table must exist and be visible
          return getNextGridControls(current, section, attribute);
        }
      }
      return [];
    }
    getPrevSuperSampleAttr = function (current, section, attribute) {
      current = current.prev();
      if (current.filter('div.species_grid_supersample_attributes').length > 0) {
        if (current.find('div').length > 0) { // div.species_grid_supersample_attributes
          // prev supersample exists, so use this to find targetInput
          // first parts are invariant so can use id attributes rather than props.
          return current.find("input,select").filter("[id^='superSampleAttr-']").filter(":visible").filter(':last');
        } else {
          return getPrevGridTitle(current, section, attribute);
        }
      }
      return [];
    }
    getNextGridControls = function (current, section, attribute) {
      current = current.next();
      if (current.filter('div.species_grid_controls').length > 0) {
        return getNextStickyTableHeader(current, section, attribute);
      }
      return [];
    }
    getPrevGridControls = function (current, section, attribute) {
      current = current.prev();
      // in simple case, have to be careful about hidden ones.
      while (current.filter(':visible').length === 0) {
        current = current.prev();
      }
      if (current.filter('div.species_grid_controls').length > 0) {
        return getPrevSuperSampleAttr(current, section, attribute);
      }
      return [];
    }
    getNextStickyTableHeader = function (current, section, attribute) {
      current = current.next();
      if (current.filter('table.sticky-header').length > 0) {
        return getNextOccurrence(current, section, attribute);
      }
      return [];
    }
    getPrevStickyTableHeader = function (current, section, attribute) {
      current = current.prev();
      if (current.filter('table.sticky-header').length > 0) {
        return getPrevGridControls(current, section, attribute);
      }
      return [];
    }

    // TODO
    general_focus = function (evt) {
        // select the row
        /*
        var matches = $(evt.target).parents('td:first')[0].className.match(/col\-\d+/),
        colidx = matches[0].substr(4);
        $(evt.target).parents('table:first').find('.table-selected').removeClass('table-selected');
        $(evt.target).parents('table:first').find('.ui-state-active').removeClass('ui-state-active');
        $(evt.target).parents('div:first').find('table.sticky-header .ui-state-active').removeClass('ui-state-active');
        $(evt.target).parents('tr:first').addClass('table-selected');
        $(evt.target).parents('table:first').find('tbody .col-'+colidx).addClass('table-selected');
        $(evt.target).parents('table:first').find('thead .col-'+colidx).addClass('ui-state-active');
        $(evt.target).parents('div:first').find('table.sticky-header thead .col-'+colidx).addClass('ui-state-active');
        */
    }

    /*
     * When a field is changed: a class "edited" is added.
     * 
     * When a field blurs,
     * Any extra validation required is done.
     * a check is made to see if it is currently saving:
     * If yes, it is flagged as queued, a class "queued" is added, and nothing else happens
     * Else the entity is saved
     * 
     * Saving an entity:
     * The appropriate hidden form is populated.
     * All the fields included are flagged as saving, the edited and queued flags are removed
     * Hidden form is submitted
     * 
     * On successful submission of the form:
     * All fields have entity value filled in
     * If the attribute
     */
    occurrence_immediate_save = function (evt) {
      $(evt.target).addClass('edited');
      occurrence_input_blur(evt);
    }

    sub_sample_immediate_save = function (evt) {
      $(evt.target).addClass('edited');
      sub_sample_input_blur(evt);
    }

    super_sample_immediate_save = function (evt) {
      $(evt.target).addClass('edited');
      super_sample_input_blur(evt);
    }


    input_change = function (evt) {
      $(evt.target).addClass('edited');
    }

    occurrence_input_blur = function (evt) {
        var selector = '#'+evt.target.id,
            // ID format value-[ttlid]-[S<N>]-[SubsampleID]-[OccurrenceID]-[OccurrenceAttributeID]-[OccurrenceAttributeValueID]
            parts = evt.target.id.split('-'),
            warnings = [],
            taxon_meaning_id,
            value = $(evt.target).val().trim();

        if ($(evt.target).hasClass('edited')) {
            if ($(evt.target).hasClass('count-input')) {
                // check for number input - don't post if not a number
                if (!value.match(/^[0-9]*$/)) { // matches a blank field for deletion
                    alert('Please enter a valid number or a blank');
                    // use a timer, as refocus during blur not reliable.
                    setTimeout("jQuery('#" + evt.target.id + "').focus(); jQuery('#" + evt.target.id + "').select()", 100);
                    return;
                }
            }
            taxon_meaning_id = $(evt.target).data('taxon_meaning_id');
            // Don't carry out limit checks if blanking the data
            if (formOptions.outOfRangeVerification.attrId) {
              if (value != '' && !checkSectionLimit(taxon_meaning_id, value, parts[5])) {
                warnings.push(formOptions.langStrings.verificationSectionLimitMessage
                        .replace('{{ value }}', value)
                        .replace('{{ limit }}', sectionLimitAsText(taxon_meaning_id)));
              }
              total = getRowTotal(evt.target, formOptions.outOfRangeVerification.attrId);
              if (value != '' && !checkWalkLimit(taxon_meaning_id, total, parts[5])) {
                warnings.push(formOptions.langStrings.verificationWalkLimitMessage
                    .replace('{{ total }}', total)
                    .replace('{{ limit }}', walkLimitAsText(taxon_meaning_id)));
              }
            }
            if (warnings.length > 0) {
                $('#warning-dialog-list').empty()
                $.each(warnings, function(idx, elem) {
                    $('#warning-dialog-list').append('<li>' + elem + '</li>');
                });
                dialog = $('#warning-dialog')
                    .dialog({
                        width: 350,
                        modal: true,
                        title: formOptions.langStrings.verificationTitle,
                        buttons: {
                            "No": function() {
                                $(selector).val($(selector).data('previous'));
                                dialog.dialog("close");
                            },
                            "Yes": function() {
                                setTotals(evt.target);
                                dialog.dialog("close");
                                if ($(evt.target).hasClass('savingO')) {
                                  $(evt.target).addClass('queued')
                                  return
                                }
                                saveOccurrence(evt.target.id, true);
                            }
                        }
                    });
            } else {
                setTotals(evt.target);
                if ($(evt.target).hasClass('savingO')) {
                  $(evt.target).addClass('queued')
                  return
                }
                saveOccurrence(evt.target.id, true);
            }
        }
    }

    occurrence_comment_blur = function (evt) {
      if ($(evt.target).hasClass('savingO')) {
        $(evt.target).addClass('queued')
        return
      }
      if ($(evt.target).hasClass('edited')) {
        saveOccurrence(evt.target.id, true);
      }
    }

    sub_sample_input_blur = function (evt) {
        var selector = '#'+evt.target.id,
            // id = subSmpAttr-[S<N>]-[SampleId]-[AttributeId]-[AttributeValueId]
            parts = evt.target.id.split('-');

        if ($(evt.target).hasClass('savingCS')) {
            $(evt.target).addClass('queued')
            return
          }

        if ($(evt.target).hasClass('edited')) {
            if ($(evt.target).hasClass('count-input')) {
                // check for number input - don't post if not a number
                if (!$(evt.target).val().match(/^[0-9]*$/)) { // matches a blank field for deletion
                    alert('Please enter a valid number');
                    // use a timer, as refocus during blur not reliable.
                    setTimeout(
                        "jQuery('#" + evt.target.id + "').focus(); jQuery('#" + evt.target.id + "').select()",
                        100
                    );
                    return;
                }
            }

            if (formOptions.interactingSampleAttributes.length === 2 &&
                    parts[3] == formOptions.interactingSampleAttributes[0] &&
                    $(selector).val() !== '') {
                $('[id^=subSmpAttr-' + parts[1] + '-' + parts[2] + '-' + formOptions.interactingSampleAttributes[1] + '-]')
                    .val(100 - $(selector).val());
            } else if (formOptions.interactingSampleAttributes.length === 2 &&
                    parts[3] == formOptions.interactingSampleAttributes[1] &&
                    $(selector).val() !== '') {
                $('[id^=subSmpAttr-' + parts[1] + '-' + parts[2] + '-' + formOptions.interactingSampleAttributes[0] + '-]')
                    .val(100 - $(selector).val());
            }
            saveSubSample(evt.target, parts[1], parts[2]);
        }
    }

    super_sample_input_blur = function (evt) {
      if ($(evt.target).hasClass('edited')) {
        if ($(evt.target).hasClass('count-input')) {
          // check for number input - don't post if not a number
          if (!$(evt.target).val().match(/^[0-9]*$/)) { // matches a blank field for deletion
            alert('Please enter a valid number');
            // use a timer, as refocus during blur not reliable.
            setTimeout("jQuery('#" + evt.target.id + "').focus(); jQuery('#" + evt.target.id + "').select()", 100);
            return;
          }
        }
        if ($(evt.target).hasClass('savingPS')) {
          $(evt.target).addClass('queued')
          return
        }
        saveSuperSample(evt.target);
      }
    }

      removeTaggedRows = function(table) {
        var rowCount = 0;
        $(table + ' tr.possibleRemove').next('.images-row').hide().next('.comments-row').hide();
        $(table + ' tr.possibleRemove').next('.comments-row').hide();
        $(table + ' tr.possibleRemove').removeClass('possibleRemove').hide();
      }

    listTypeChange = function(val, table, N) {
        var TaxonData = {
            'taxon_list_id': formOptions.speciesTabDefinition[N].taxon_list_id,
            'preferred': 't',
            'auth_token': indiciaData.read.auth_token,
            'nonce': indiciaData.read.nonce,
            'mode': 'json',
            'allow_data_entry': 't',
            'view': 'cache',
            'orderby': $('[name=species-sort-order-'+N+']:checked').val()
        };

        var valid = false,
            query = {"in":{}},
            branchID,
            filter,
            filterField;

        $('#grid-loading').show();
        $('[name=species-list-'+N+']:checked').parent('label').addClass('working');
        $(table + ' .table-selected').removeClass('table-selected');
        $(table + ' .ui-state-active').removeClass('ui-state-active');
        $(table + ' .images-row').hide();
        $(table + ' .comments-row').hide();

        // first tag all blank rows. Ones without occurrences
        $(table + ' tr.occurrence-row').removeClass('possibleRemove').each(function(idx, row){
        if($(row).find('input,select')
                .filter(function(index) {
                    parts = this.id.split('-');
                    return parts[4] !== "NEW";
                })
                .length == 0)
            $(row).addClass('possibleRemove');
        });

        // Apply the default filter
        if(typeof formOptions.speciesTabDefinition[N].taxon_filter_field != "undefined") {
            query['in'][formOptions.speciesTabDefinition[N].taxon_filter_field] =
                formOptions.speciesTabDefinition[N].taxon_filter;
            // WARNING if filter field = taxon_meaning_id , potential clash: not currently used in UKBMS.
        }

        if (val.slice(0,6) === "branch") { // TODO
            branchID = val;
            val = "branch";
        }

        // There are problems when there are large numbers of specified taxa: the URL is too big, and we can't use POST
        switch(val){
            case 'full':
                valid = true;
                filterField = false;
                filter = false;
                break;
            case 'common':
                if(formOptions.commonTaxonMeaningIDs[N].length > 0) {
                    valid = true;
                    filterField = "taxon_meaning_id";
                    filter = formOptions.commonTaxonMeaningIDs[N];
                }
                break;
            case 'mine':
                if(formOptions.myTaxonMeaningIDs.length > 0) {
                    valid = true;
                    filterField = "taxon_meaning_id";
                    filter = formOptions.myTaxonMeaningIDs;
                }
                break;
            case 'branch':
                if(formOptions.branchTaxonMeaningIDs[N].length > 0) {
                    valid = true;
                    filterField = "taxon_meaning_id";
                    filter = formOptions.branchTaxonMeaningIDs[N];
                }
                break;
            case 'here':
            default:
                if(formOptions.allTaxonMeaningIDsAtTransect.length > 0) {
                    valid = true;
                    filterField = "taxon_meaning_id";
                    filter = formOptions.allTaxonMeaningIDsAtTransect;
                }
                break;
        }
        if(valid) {
            var offset = 0,
            limit = 100,
            ajaxStopHandler = function() {
                removeTaggedRows(table);
                $('input[name=species-sort-order-'+N+']:checked').change();
                $('[name=species-list-'+N+']:checked').parent('label').removeClass('working');
                $( document ).off("ajaxStop", ajaxStopHandler);
                $('#grid-loading').hide();
            };
            $( document ).ajaxStop(ajaxStopHandler);
            do {
                var ajaxData = Object.assign({}, TaxonData);
                var queryData = Object.assign({}, query);

                if (filter) {
                    queryData["in"][filterField] = filter.slice(offset, offset+limit);
                }
                if (!$.isEmptyObject(queryData["in"])) {
                    ajaxData.query = JSON.stringify(queryData);
                }
                $.ajax({
                    'url': indiciaData.warehouseUrl+'index.php/services/data/taxa_taxon_list',
                    'data': ajaxData,
                    'dataType': 'jsonp',
                    'success': function(data) {
                        // at this point only adding empty rows, so no affect on totals.
                        addSpeciesToGrid(data, table, N);
                    }
                });
                offset = offset + limit;
            } while (filter && offset < filter.length);
        } else {
          removeTaggedRows(table);
          $('#grid-loading').hide();
          $('[name=species-list-'+N+']:checked').parent('label').removeClass('working');
        }
    }

    //autocompletes assume ID
    bindSpeciesAutocomplete = function (selectorID, tableSelectorID, lookupListId, lookupMinRank,
            lookupListFilterField, lookupListFilterValues, readAuth, duplicateMsg, max, tabIDX) {
        // inner function to handle a selection of a taxon from the autocomplete
        var handleSelectedTaxon = function(event, data) {
            $(event.target).val('').removeClass('ui-state-highlight');
            if ($('#row-'+data.taxon_meaning_id).length === 0) {
                addGridRow(data, tableSelectorID, tabIDX, false);
                $('input[name=species-list-' + tabIDX + ']:checked').prop('checked', false).parent().removeClass('active');
                if (formOptions.addSpeciesPosition === 'above') {
                  $('input[name=species-sort-order-' + tabIDX + ']').change();
                } else {
                  $('input[name=species-sort-order-' + tabIDX + ']:checked').parent().removeClass('active');
                  $('#species-sort-order-' + tabIDX + '-none').prop('checked', true).change();
                }
            } else if ($('#row-'+data.taxon_meaning_id).closest('tbody').filter(':visible').length === 0) {
                // TODO in future, swap to appropriate grid automatically.
                alert(duplicateMsg);
                return;
            }
            $('#row-'+data.taxon_meaning_id).show();
            var elementTop = $('#row-'+data.taxon_meaning_id).offset().top,
                elementBottom = elementTop + $('#row-'+data.taxon_meaning_id).outerHeight(),
                viewportTop = $(window).scrollTop(),
                // Now remove space for the banner and toolbar.
                viewportCheckTop = viewportTop + $('table.species_grid.sticky-table thead').outerHeight() + 90,
                viewportBottom = viewportTop + $(window).height() - 10,
                scrollTo = elementTop - $('table.species_grid.sticky-table thead').outerHeight() - 100;
            scrollTo = scrollTo < 0 ? 0 : scrollTo;
            if (elementBottom > viewportBottom || elementTop < viewportCheckTop) {
              $('html, body').animate({
                scrollTop: scrollTo,
              }, 1000, function () {$('#row-'+data.taxon_meaning_id + ' input:first').focus();});
            } else {
              setTimeout(function() {
                $('#row-'+data.taxon_meaning_id + ' input:first').focus();
              });
            }
        };

        var extra_params = {
            view : 'cache',
            orderby : formOptions.taxon_column,
            mode : 'json',
            qfield : formOptions.taxon_column,
            auth_token: readAuth.auth_token,
            nonce: readAuth.nonce,
            taxon_list_id: lookupListId,
            allow_data_entry: 't'
        };
        if(typeof lookupListFilterField != "undefined" && typeof lookupListFilterValues != "undefined"){
            extra_params.query = '{"in":{"' + lookupListFilterField + '":' + JSON.stringify(lookupListFilterValues) + "}}";
        };

        // Attach auto-complete code to the input
        var ctrl = $('#' + selectorID).autocomplete(indiciaData.ajaxUrl + '/taxon_autocomplete/' + indiciaData.nid, {
            extraParams : extra_params,
            max : max,
            parse: function(data) {
                var results = [];
                $.each(data, function(i, item) {
                    if (item.taxon_rank_sort_order === null || typeof lookupMinRank === "undefined" || item.taxon_rank_sort_order >= lookupMinRank)
                        results[results.length] = {'data' : item, 'result' : item[formOptions.taxon_column], 'value' : item.id};
                });
                return results;
            },
            formatItem: function(item) {
                if (item.taxon == item.preferred_taxon) {
                    return '<em>'+item.taxon+'</em>';
                }
                if (formOptions.taxon_column === 'preferred_taxon') {
                    return '<em>'+item.preferred_taxon+'</em> &lt;'+item.taxon+'&gt;';
                }
                return item.taxon+' <em>&lt;'+item.preferred_taxon+'&gt;</em>';
            }
        });
        ctrl.bind('result', handleSelectedTaxon);
        setTimeout(function() {
            $('#' + ctrl.attr('id')).focus();
        });
    }

      // Only occurs on Front page.
      bindRecorderNameAutocomplete = function (attrID, userID, baseurl, surveyID, token, nonce) {
        $('#smpAttr\\:'+attrID).autocomplete(baseurl+'/index.php/services/report/requestReport', {
              extraParams : {
                mode : 'json',
                report : 'reports_for_prebuilt_forms/UKBMS/ukbms_recorder_names.xml',
                reportSource : 'local',
                qfield : 'name',
                auth_token: token,
                attr_id : attrID,
                survey_id : surveyID,
                user_id : userID,
                nonce: nonce
              },
              max: 50,
              mustMatch : false,
              parse: function(data) {
                var results = [];
                $.each(data, function(i, item) {
                      results[results.length] = {'data' : item,'result' : item.name,'value' : item.name};
                });
                return results;
              },
              formatItem: function(item) {return item.name;}
        });
      }

      // Returns false if check fails
      checkSectionLimit = function (taxon_meaning_id, value, attributeId) {
        if (typeof formOptions.outOfRangeVerification.section[taxon_meaning_id] != undefined &&
                formOptions.outOfRangeVerification.attrId == attributeId) {
            return parseInt(value, 10) <= parseInt(formOptions.outOfRangeVerification.section[taxon_meaning_id], 10);
        }
        return true;
      }

      // Returns false if check fails
      checkWalkLimit = function (taxon_meaning_id, total, attributeId) {
          if (typeof formOptions.outOfRangeVerification.walk[taxon_meaning_id] != undefined &&
                  formOptions.outOfRangeVerification.attrId == attributeId) {
              return parseInt(total, 10) <= parseInt(formOptions.outOfRangeVerification.walk[taxon_meaning_id], 10);
          }
        return true;
      }

      sectionLimitAsText = function (taxon_meaning_id) {
          if (typeof formOptions.outOfRangeVerification.section[taxon_meaning_id]) {
              return formOptions.outOfRangeVerification.section[taxon_meaning_id];
          }
        return 'NA';
      }

      walkLimitAsText = function (taxon_meaning_id) {
          if (typeof formOptions.outOfRangeVerification.walk[taxon_meaning_id] != undefined) {
              return formOptions.outOfRangeVerification.walk[taxon_meaning_id];
          }
        return 'NA';
      }

}) (jQuery);

