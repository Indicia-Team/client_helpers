// Declare a hook for functions that call when dynamic content updated.
// For example:
// indiciaFns.hookDynamicAttrsAfterLoad.push(function(div, type) {
//   $(div).prepend('<h1>' + type + '</h1>');
// });
indiciaFns.hookDynamicAttrsAfterLoad = [];

jQuery(document).ready(function docReady($) {
  var sexStageInputSelectors = '.system-function-sex, .system-function-stage, .system-function-sex_stage';
  var taxonRestrictionInputSelectors = '#occurrence\\:taxa_taxon_list_id, ' + sexStageInputSelectors;
  var hasDynamicAttrs = $('.species-dynamic-attributes').length > 0;

  /**
   * When attributes are dynamically loaded into a div because of a selected
   * taxon, we want any attributes that share a system function with existing
   * controls on the form to replace the existing controls.
   */
  function repositionDynamicAttributes(div) {
    // Locate each dynamic attribute.
    $.each($(div).find('[class*=system-function-][class*=dynamic-attr]'), function() {
      var ctrlWrap = $(this).closest('.ctrl-wrap');
      // Find the system-function-* class.
      $(this)[0].classList.forEach(function(c) {
        var standardControl;
        if (c.match(/^system\-function/)) {
          // See if there is a control with same function already on the form.
          // If so, we need to replace this control with the dynamic one.
          standardControl = $('.' + c).not('[class*=dynamic-attr]');
          if (standardControl.length > 0) {
            // Disable the non-dynamic version, tag the wrapper so we can undo
            // this and hide it. Finally, add the dynamic version of the
            // control at this location in the form.
            standardControl
              .prop('disabled', true)
              .closest('.ctrl-wrap')
                .addClass('dynamically-replaced').hide()
                .after(ctrlWrap);
            // Tag the moved dynamic control so we can clear it out if a
            // different taxon selected.
            $(ctrlWrap).addClass('dynamically-moved');
          }
        }
      });
    });
  }

  function changeTaxonRestrictionInputs() {
    var urlSep = indiciaData.ajaxUrl.indexOf('?') === -1 ? '?' : '&';
    var sexStageAttrs = $(sexStageInputSelectors);
    var sexStageVals = [];
    if ($('#occurrence\\:taxa_taxon_list_id').val() !== '') {
      $.each(sexStageAttrs, function grabSexStageAttrVal() {
        if ($(this).val() !== '') {
          sexStageVals.push($(this).val());
        }
      });
      $.each($('.species-dynamic-attributes'), function loadAttrDiv() {
        var type = $(this).hasClass('attr-type-sample') ? 'sample' : 'occurrence';
        var div = this;
        // 0 is a fake nid, since we don't care.
        $.get(indiciaData.ajaxUrl + '/dynamicattrs/' + indiciaData.nid + urlSep +
            'survey_id=' + $('#survey_id').val() +
            '&taxa_taxon_list_id=' + $('#occurrence\\:taxa_taxon_list_id').val() +
            '&type=' + type +
            '&stage_termlists_term_ids=' + JSON.stringify(sexStageVals) +
            '&validate_against_taxa=' + (indiciaData.validateAgainstTaxa ? 't' : 'f') +
            '&language=' + indiciaData.currentLanguage3 +
            '&options=' + JSON.stringify(indiciaData['dynamicAttrOptions' + type]), null,
          function getAttrsReportCallback(data) {
            var oldVals = {};
            // Reset any controls affected by earlier loading of attrs for a
            // different taxon.
            $('.dynamically-replaced').show();
            $('.dynamically-replaced :input').prop('disabled', false);
            $('.dynamically-moved').remove();
            // Ensure page level onload functions don't run again.
            indiciaData.onloadFns = [];
            // Grab existing values so they can be reset.
            $.each($(div).find(':input'), function() {
              if ($(this).val()) {
                oldVals[$(this).attr('name')] = $(this).val();
              }
            });
            $(div).html(data);
            $.each($(div).find(':input'), function() {
              if (typeof oldVals[$(this).attr('name')] !== 'undefined') {
                $(this).val(oldVals[$(this).attr('name')]);
              }
            });
            repositionDynamicAttributes(div);
            $.each(indiciaFns.hookDynamicAttrsAfterLoad, function callHook() {
              this(div, type);
            });
          },
          'text'
        );
      });
    }
  }

  /**
   * Handler for changes in the taxon list selector.
   *
   * Updates the list of taxa suggested for recording in the species grid. The
   * list is filtered by all the taxon_meanings that match the option selected.
   * To enable this functionality, check the Client Selects Taxon Filter
   * checkbox on the edit page.
   */
   function changeTaxonList() {
    var $this = $(this);
    var listChoice = $this.val();
    gridId = Object.keys(indiciaData.speciesGrid)[0];
    var param, value;
    var getMeanings = true;

    // Add visual indicator of data loading.
    $this.addClass('working');

    // Mark potential rows for removal
    tagSpeciesNotPresent(gridId);

    // Determine the parameters/values for obtaining taxon meanings
    switch (listChoice) {
      case 'location_id':
        param = 'location_id';
        value = $('[name="sample\\:location_id"]').val();
        break;
      case 'parent_location_id':
        param = 'parent_location_id';
        value = indiciaData.parentLocationId;
        break;
      case 'indicia_user_id':
        param = 'user_id';
        value = indiciaData.user_id;
        break;
      default:
        // In other cases, we don't need to get taxon meanings.
        getMeanings = false;
    }

    var gotSpecies;
    if (getMeanings) {
      // Get a list of taxon meanings
      var gotMeanings = requestMeanings(param, value)
      // Callback when we have got meanings returns a new promise.
      gotSpecies = gotMeanings.then(function(meaningData) {
        // data is an array of objects, each with a taxon_meaning_id property.
        const meanings = meaningData.map(x => x.taxon_meaning_id);
        // Get a list of species filtered by meanings.
        return requestSpecies(meanings);
      });
      // gotSpecies.done() callback should follow in due course.
    }
    else if (listChoice == 'all') {
      // Get a list of species unfiltered by meanings.
      gotSpecies = requestSpecies();
      // gotSpecies.done() callback should follow in due course.
    }
    else {
      // When listChoice is 'none', just remove rows.
      $(`#${gridId} tr.possibleRemove`).remove();
      $this.removeClass('working');
      return;
    }

    // Callback when we have got species.
    gotSpecies.done(function(speciesData) {
      addSpeciesToGrid(speciesData, gridId);
      // Remove rows no longer required.
      $(`#${gridId} tr.possibleRemove`).remove();
      $this.removeClass('working');
    });
  }

  /**
   * Tag all rows in species table with presence box unchecked.
   *
   * These may be removed when changing the taxon list selector if they are not
   * present in the newly selected list.
   *
   * gridId
   *   The html id attribute for the species grid.
   */
  tagSpeciesNotPresent  = function (gridId) {
    $(`#${gridId} tr.added-row`).each(function(idx, row){
      if($(row).find('input.scPresence:checked').length == 0)
        $(row).addClass('possibleRemove');
    });
  }

  /**
   * Make an Ajax request for taxon meanings.
   *
   * param
   *   The name of a report parameter.
   * value
   *   The value to pass to the report for the param.
   *
   * Returns a deferred object.
   */
  requestMeanings  = function (param, value){
    var reportApi = indiciaData.warehouseUrl + 'index.php/services/report/requestReport';
    var report = 'library/occurrences/list_taxon_meanings.xml';
    return $.ajax({
      url: reportApi,
      data: {
        'auth_token': indiciaData.read.auth_token,
        'nonce': indiciaData.read.nonce,
        'mode': 'json',
        'reportSource': 'local',
        'report': report,
        [param]: value,
        'training': indiciaData.training
      },
      dataType: 'jsonp',
      crossDomain: true
    });
  }

  /**
   * Make an Ajax request for a list of species.
   *
   * meanings
   *   An array of meanings to limit the list to. If empty, no species are
   *   returned. If null, then all species in the list are returned.
   *
   * Returns a deferred object.
   */
  requestSpecies  = function (meanings = null){
    var data = {
      'taxon_list_id': indiciaData.taxonListId,
      'preferred': 't',
      'auth_token': indiciaData.read.auth_token,
      'nonce': indiciaData.read.nonce,
      'mode': 'json',
      'allow_data_entry': 't',
      'view': 'cache',
      'orderby': 'preferred_taxon'
    };

    if (meanings !== null) {
      data.query = JSON.stringify({
        'in': {
          'taxon_meaning_id': meanings
        }
      });
    }

    return $.ajax({
      url: indiciaData.warehouseUrl + 'index.php/services/data/taxa_taxon_list',
      method: 'POST',
      data: data,
      dataType: 'jsonp',
      crossDomain: true
    });
  }

  /**
   * Adds a list of taxa to the species input grid.
   *
   * If there is already a row for a species in the list that row is moved
   * so that the order of the supplied list is observed.
   *
   * taxonList
   *   An array of species where each species is an object with many fields.
   * gridId
   *   The html id attribute for the species grid.
   */
  addSpeciesToGrid  = function (taxonList, gridId){
    $.each(taxonList, function(idx, species) {
      var $autocomplete;
      var $existingPresence = $(`#${gridId} input.scPresence[value=${species.id}]`);
      if($existingPresence.length > 0) {
        // If a row already exists for this species.
        $row = $existingPresence.closest('tr');
        // Remove the tag to prevent it being deleted later.
        $row.removeClass('possibleRemove');
        // Move it to the end of the table (so we maintain species order).
        $(`#${gridId} tr.scClonableRow`).before($row);
      }
      else {
        // Add a new row
        species.taxa_taxon_list_id = species.id;
        // Locate the autocomplete control used for adding species.
        $autocomplete = $(`#${gridId} .scClonableRow .scTaxonCell input`);
        // Trigger the event in addRowToGrid.js to add the species.
        $autocomplete.trigger('result', [species, species.id]);
        // Change the newly added species to not present.
        var $newRow = $(`#${gridId} .scClonableRow`).prev();
        $newRow.find('input.scPresence').prop('checked', false);
      }
    });
    // If we have added a lot of rows there may now be a long queue of scroll
    // animations which need speeding up.
    if ($('html,body').queue('fx').length > 0) {
      $('html,body').queue('fx', [function() {
        $autocomplete = $(`#${gridId} .scClonableRow .scTaxonCell input`);
        var newTop = $autocomplete.offset().top - $(window).height() + 180;
        $(this).animate({ scrollTop: newTop }, 500);
      }]);
    }
  }

  indiciaFns.applyTaxonValidationRules = function (typeAbbr, type) {
    $.each(indiciaData[typeAbbr + 'TaxonValidationRules'], function() {
      var rule = '';
      var caption;
      var number = this.taxon_attr_data_type === 'I' || this.taxon_attr_data_type === 'F';
      var bool = this.taxon_attr_data_type === 'B';
      var text = this.taxon_attr_data_type === 'T';
      var hasValue = this.int_value !== null || this.float_value !== null || this.text_value !== null;
      var hasUpperValue = this.upper_value !== null;
      var value = this.int_value === null ? this.float_value : this.int_value;
      var range = this.taxon_attr_allow_ranges === 't';
      var wrapperId;
      if (hasValue) {
        if (text) {
          rule = value;
        } else if (number && range && hasUpperValue) {
          rule = value + ' - ' + this.upper_value;
        } else if (number) {
          rule = value;
        }
      }
      if (rule !== '') {
        caption = indiciaData.lang.dynamicattrs.expected.replace(/\{1\}/g, this.taxon_providing_values);
        wrapperId = (this.allow_ranges === 't' ? 'range' : 'ctrl') + '-wrap-' + typeAbbr + 'Attr-' + this[type + '_attribute_id'];
        $('#' + wrapperId).append(
          '<div class="taxon-rules alert alert-info">' +
          '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>' +
          caption + ': ' + rule + '</div>'
        );
      }
    });
  };

  if (hasDynamicAttrs) {
    // On selection of a taxon or change of sex/stage attribute, load any dynamically linked attrs into the form.
    $(taxonRestrictionInputSelectors).on('change', changeTaxonRestrictionInputs);
  }

  // If dynamic attrs loaded for existing record on initial page load, ensure
  // they replace existing non-dynamic attributes of the same system function.
  $.each($('.species-dynamic-attributes'), function loadAttrDiv() {
    repositionDynamicAttributes(this);
  });

  // If the Client Selects Taxon Filter option is enabled, attach an event
  // handler to the select control that is added.
  $('#taxonListSelect').on('change', changeTaxonList);

  // In single species mode need to put line through verification information to show it is no longer valid
  $('#occurrence\\:taxa_taxon_list_id\\:taxon').on('change', function() {
    $('#occurrence\\:verified_by').wrapInner('<strike>');
    $('#occurrence\\:verified_on').wrapInner('<strike>');
  });
});
