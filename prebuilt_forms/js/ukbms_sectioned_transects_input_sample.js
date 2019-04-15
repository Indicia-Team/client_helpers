//Javascript functions using jQuery now need to be defined inside a "(function ($) { }) (jQuery);" wrapper.
//This means they cannot normally be seen by the outside world, so in order to make a call to one of these 
//functions, we need to assign it to a global variable.

var setUpSamplesForm, setUpOccurrencesForm, saveSample, setTotals, getRowTotal,
    checkWalkLimit, walkLimitAsText, formOptions;

(function ($) {
  setUpSamplesForm = function (options) {
    formOptions = options;
    
        if(typeof formOptions.recorderNameAttrID != 'undefined') {
          bindRecorderNameAutocomplete(formOptions.recorderNameAttrID, formOptions.userID, indiciaData.warehouseUrl, formOptions.surveyID, indiciaData.read.auth_token, indiciaData.read.nonce);
        }

        $('#imp-location').change(function(evt) {
          $('#entered_sref').val(formOptions.sites[evt.target.value].centroid_sref);
          $('#entered_sref_system').val(formOptions.sites[evt.target.value].centroid_sref_system);
          if(formOptions['finishedAttrID'])
            checkFinishedStatus();
        });
        if(formOptions['finishedAttrID']) {
          $('[name=sample\\:date]').change(function(evt) {
            checkFinishedStatus();
          });
          checkFinishedStatus();
        }

    // allow deletes if delete button is present.
    $('#delete-button').click(function(){
      if(confirm(formOptions.deleteConfirm)){
          $('#delete-form').submit();
      } // else do nothing.
    });

  }
  
  checkFinishedStatus = function () {
    $('#finishedMessage').hide();
    if(formOptions['finishedAttrID'] && $('[name=sample\\:location_id]').val() !== "" && $('[name=sample\\:date]').val() !== "") {
      $('#finishedMessageYear').html($('[name=sample\\:date]').val().substring(6,10));
      // Look up all samples for the location
      $.getJSON(indiciaData.warehouseUrl + "index.php/services/data/sample" +
          "?mode=json&view=detail&location_id=" + $('[name=sample\\:location_id]').val() +
          "&auth_token=" + indiciaData.read.auth_token + "&nonce=" + indiciaData.read.nonce +
          "&callback=?&columns=id,display_date", function(sdata) {
        if(sdata.length === 0) return;
        var thisYearsSamples = [];
        var currentSampleYear = $('[name=sample\\:date]').val().substring(6,10);
        $.each(sdata, function(idx, sample) {
          if(sample.display_date.substring(0,4) == currentSampleYear)
            thisYearsSamples.push(sample.id);
        });
        if(thisYearsSamples.length === 0) return;
          var query = {"in":{"sample_id":thisYearsSamples}};
        $.getJSON(indiciaData.warehouseUrl + "index.php/services/data/sample_attribute_value" +
            "?mode=json&view=list" +
              "&query=" + JSON.stringify(query) +
              "&sample_attribute_id=" + formOptions['finishedAttrID'] +
            "&auth_token=" + indiciaData.read.auth_token + "&nonce=" + indiciaData.read.nonce +
            "&callback=?", function(adata) {
          if(adata.length === 0) return;
          $.each(adata, function(idx, attr) {
            if(attr.id != null)
              $('#finishedMessage').show();
          });
        });        
      });

    }
  }

  setUpOccurrencesForm = function (options) {
    formOptions = options;
    indiciaData.currentCell=null;
    
    $.each(formOptions.sections, function(idx, section) {
      if (typeof section.total==="undefined") {
        section.total = [];
      }
    });
    
      // Do an AJAX population of the grid rows.
    process(1);

      $('.smp-input').keydown(smp_keydown).change(input_change).blur(input_blur).focus(general_focus);

      indiciaFns.bindTabsActivate($('#tabs'), function(event, ui) {
        var target = typeof ui.newPanel==='undefined' ? ui.panel : ui.newPanel[0];;
        // first get rid of any previous tables
        $('table.sticky-header').remove();
        $('table.sticky-enabled thead.tableHeader-processed').removeClass('tableHeader-processed');
        $('table.sticky-enabled.tableheader-processed').removeClass('tableheader-processed');
        $('table.species-grid.sticky-enabled').removeClass('sticky-enabled');
        var table = $('#'+target.id+' table.species-grid');
        if(table.length > 0) {
          table.addClass('sticky-enabled');
          Drupal.behaviors.tableHeader.attach(table.parent()); // Drupal 7
        }
        // remove any hanging autocomplete select list.
        $('.ac_results').hide();
      });
      
        var table = $('#transect-input1');
        if(table.length > 0) {
          table.addClass('sticky-enabled');
          Drupal.behaviors.tableHeader.attach(table.parent()); // Drupal 7
        }

      $.each(formOptions.autoCompletes, function(idx, details){
        bindSpeciesAutocomplete('taxonLookupControl'+details.tabNum,
                    'table#transect-input'+details.tabNum,
                    indiciaData.warehouseUrl+'index.php/services/data',
                    formOptions.speciesList[details.tabNum],
                    formOptions.speciesMinRank[details.tabNum],
                    formOptions.speciesListFilterField[details.tabNum],
                    formOptions.speciesListFilterValues[details.tabNum],
                    {"auth_token" : indiciaData.read.auth_token, "nonce" : indiciaData.read.nonce},
                    formOptions.duplicateTaxonMessage,
                    25,
                    details.tabNum);
      });
      
    $('#listSelect1').change(function() {listTypeChange($(this).val(), 'table#transect-input1', 1); });

    $('#occ-form').ajaxForm({
      async: true,
      dataType:  'json',
      success:   function(data, status, form){
        var deletion = data.transaction_id.match(/:deleted$/),
          transaction_id = data.transaction_id.replace(/:deleted$/, ''),
          selector = '#'+transaction_id.replace(/:/g, '\\:');
        $(selector).removeClass('saving');
        if (checkErrors(data)) {
          if(!deletion) { // if we are deleting the entry then we do not want to add the id and attrValId fields (they will have just been removed!)
            if ($(selector +'\\:id').length===0) {
              // this is a new occurrence, so keep a note of the id in a hidden input
              $(selector).after('<input type="hidden" id="'+transaction_id +':id" value="'+data.outer_id+'"/>');
            }
            if ($(selector +'\\:attrValId').length===0) {
              // this is a new attribute, so keep a note of the id in a hidden input
              $(selector).after('<input type="hidden" id="'+transaction_id +':attrValId" value="'+data.struct.children[0].id+'"/>');
            }
          }
          $(selector).removeClass('edited');
        }
      }
    });

    $('#smp-form').ajaxForm({
      async: false, // must be synchronous, otherwise currentCell could change.
      dataType:  'json',
      complete: function() {
        var selector = '#'+indiciaData.currentCell.replace(/:/g, '\\:');
        $(selector).removeClass('saving');
      },
      success: function(data){
        if (checkErrors(data)) {
          // get the sample code from the id of the cell we are editing.
          var parts = indiciaData.currentCell.split(':');
          // we cant just check if we are going to create new attributes and fetch in this case to get the attribute ids -
          // there is a possibility we have actually deleted an existing attribute, in which the id must be removed. This can only be
          // found out by going to the database. We can't keep using the deleted attribute as it stays deleted (ie does not undelete)
          // if a new value is saved into it.
          $.each($('.smpAttr-'+parts[2]), function(idx, input) {
            // an attr value that is not saved yet is of form smpAttr:attrId, whereas one that is saved
            // is of form smpAttr:attrId:attrValId. Wo we can count colons to know if it exists already.
            if ($(input).attr('name').split(':').length<=2) {
              $(input).removeClass('edited'); // deliberately left in place for changed old attributes.
            }
          });
          // We need to copy over the information so that future changes update the existing record rather than
          // create new ones, or creates a new one if we have deleted the attribute
          // The response from the warehouse (data parameter) only includes the IDs of the attributes it created.
          // We need all the attributes.
          $.getJSON(indiciaData.warehouseUrl + "index.php/services/data/sample_attribute_value" +
              "?mode=json&view=list&sample_id=" + data.outer_id + "&auth_token=" + indiciaData.read.auth_token + "&nonce=" + indiciaData.read.nonce + "&callback=?", function(data) {
            // There is a possibility that we have just deleted an attribute (in which case it will not be in the data), so reset all the names first.
            $.each(data, function(idx, attr) {
              $('#smpAttr\\:'+attr.sample_attribute_id+'\\:'+parts[2]).attr('name', 'smpAttr:'+attr.sample_attribute_id+(parseInt(attr.id)==attr.id ? ':'+attr.id : ''));
              // we know - parts[2] = S2
              // attr.sample_attribute_id & attr.id
              // src control id=smpAttr:1:S2 (smpAttr:sample_attribute_id:sectioncode)
              // need to change src control name to
            });
          });
        }
      }
    });

    $('.species-sort-order input').change(function(){
      var table = $(this).closest('div').find('.species-grid');
      var rows = table.find('tbody.occs-body tr').removeClass('alt-row');
      var col = $(this).val();
      $(this).closest('li').find('label').addClass('working');
      rows.sort(function(a, b) {
        if(typeof $(a).data('species') == 'undefined' || typeof $(b).data('species') == 'undefined')
          return 0;
          var A = $(a).data('species')[col];
          var B = $(b).data('species')[col];
          if(A == null) A = $(a).data('species')['taxon'];
          if(B == null) B = $(b).data('species')['taxon'];
          if(A=='' || B=='' || A==null || B==null) return 0;
          A = A.toUpperCase();
          B = B.toUpperCase();
          if(A < B) return -1;
          if(A > B) return 1;
          return 0;
      });
      $.each(rows, function(index, row) {
        // this takes the rows out and inserts at the end.
        if((index+table.find('tbody:not(.occs-body) tr').length)%2 == 1) $(row).addClass('alt-row');
        table.children('tbody.occs-body').append(row);
      });
      $(this).closest('ul').find('.working').removeClass('working');
    });
    
      if(formOptions['finishedAttrID']) {
        $('.smp-finish').click(finishSample);
        $('#finished-form').ajaxForm({
          async: false,
          dataType:  'json',
          success: function(data){
            window.location.href = formOptions['return_page'];
          }
        });
      }
  }

  /**
   * Updates the main supersample, setting the finished attribute to true.
   */
  finishSample = function () {
    $('#finished-form').submit();
  }

  /**
   * Updates the sample for a section, including attributes.
   */
  saveSample = function (code) {
    var parts, id;
    $('#smpid').val(formOptions.subSamples[code]);
    $.each(formOptions.sections, function(idx, section) {
      if (section.code == code) {
        // copy the fieldname and value into the sample submission form for each sample custom attribute
        // by default all sample attributes are mandatory. Can be overridden.
        $('.smpAttr-' + section.code).each(function() {
          var mandatory = true;
          parts=this.id.split(':');
          parts.pop();
          id=parts.join('\\:');
          $('#'+id).val($(this).val());
          $('#'+id).attr('name', $(this).attr('name'));
          // remove existing error checks results.
          $(this).closest('td').find('.ui-state-error').removeClass('ui-state-error');
          $(this).closest('td').find('.inline-error').remove();
          for(var i = 0; i < formOptions.attribute_configuration.length; i++) {
            if(formOptions.attribute_configuration[i].id == parts[1] &&
                typeof formOptions.attribute_configuration[i].required != "undefined" &&
                typeof formOptions.attribute_configuration[i].required.species_grid != "undefined" &&
                formOptions.attribute_configuration[i].required.species_grid == false)
              mandatory = false;
          }
          if(mandatory && $(this).val()=='') {
            $(this).after('<p htmlfor="' + $(this).attr('id') + '" class="inline-error">' + formOptions.requiredMessage + '</p>');
          }
        });
        $('#smpsref').val(section.centroid_sref);
        $('#smpsref_system').val(section.centroid_sref_system);
        $('#smploc').val(section.id);
        // only submit if no sample errors
        if($('.smpAttr-' + section.code).closest('td').find('.inline-error').length == 0)
          $('#smp-form').submit();
        else {
          $('.smpAttr-' + section.code).addClass('ui-state-error');
          $('.smpAttr-' + section.code).closest('td').find('.saving').removeClass('saving');
        }
      }
    });
  }

  saveOccurrence = function (selector, ttlID, ssampleID, targetID) {
    // fill in occurrence stub form
    var occAttrValue = $(selector).val(), // Zero -> zero abundance; blank -> delete
        transactionId =  targetID + (occAttrValue==='' ? ':deleted' : '');

    $('#ttlid').val(ttlID);
    $('#occ_sampleid').val(ssampleID);

    $('#occdeleted').val(occAttrValue==='' ? 't' : 'f'); // blank -> delete

    // $(selector +'\\:id') will exist if occurrence already exists.
    // if no existing occurrence, we must not post the occurrence:id field.
    $('#occid').attr('disabled', ($(selector +'\\:id').length == 0))
               .val($(selector +'\\:id').length == 0 ? '' : $(selector +'\\:id').val());

    // store the actual abundance value we want to save in occattr; but this is not required if the data is being deleted.
    // by setting the attribute field name to occAttr:n where n is the occurrence attribute id, we will get a new one
    // by setting the attribute field name to occAttr:n:m where m is the occurrence attribute value id, we will update the existing one
    $('#occattr').val(occAttrValue)
                 .attr('name', 'occAttr:' + $(selector +'\\:attrId').val() + ($(selector +'\\:attrValId').length===0 ? '' : ':' + $(selector +'\\:attrValId').val()))
                 .attr('disabled', (occAttrValue==='')); // blank -> delete

    $('#occzero').val(occAttrValue==='0' ? 't' : 'f'); // Zero -> zero abundance

    $('#occSensitive').attr('disabled', $(selector +'\\:id').length>0); // existing ID - leave sensitivity as is, new data - use location sensitivity

    // Store the current cell's ID as a transaction ID, so we know which cell we were updating. Adds a tag if this is a deletion
    // so we can handle deletion logic properly when the post returns
    $('#transaction_id').val(transactionId);

    if ($(selector +'\\:id').length>0 || $('#occdeleted').val()==='f') {
      $('#occ-form').submit();
    }
    // if deleting, then must remove the occurrence and value IDs
    if (occAttrValue==='') { // blank -> delete
      $(selector +'\\:id,'+ selector +'\\:attrValId').remove();
    }
    $(selector).data('previous', occAttrValue);
  }

  getRowTotal = function (cell) {
    var row = $(cell).parents('tr:first')[0];
    var total=0, cellValue;
    $(row).find('.count-input').each(function() {
      cellValue = parseInt($(this).val());
      total += isNaN(cellValue) ? 0 : cellValue;
    });
    return total;
  }

  setTotals = function (cell) {
    var table = $(cell).closest('table')[0];
    var row = $(cell).parents('tr:first')[0];

    $(row).find('.row-total').html(getRowTotal(cell));

    // get the total for the column
    var matches = $(cell).parents('td:first')[0].className.match(/col\-\d+/);
    var colidx = matches[0].substr(4);
    total = 0;
    $(cell).closest('table').find('.occs-body').find('.col-'+colidx+' .count-input').each(function() {
      cellValue = parseInt($(this).val());
      total += isNaN(cellValue) ? 0 : cellValue;
    });
    $(table).find('td.col-total.col-'+colidx).html(total);
  }

  addSpeciesToGrid  = function (taxonList, speciesTableSelector, tabIDX){
    // this function is given a list of species from the occurrences and if they are in the taxon list
    // adds them to a table in the order they are in that taxon list
    // any that are left are swept up by another function.
    $.each(taxonList, function(idx, species) {
      var existing = false;
      $.each(formOptions.sections, function(idx, section) {
        var key = formOptions.subSamples[section.code] + ':' + species.taxon_meaning_id;
        if (typeof formOptions.existingOccurrences[key] !== "undefined")
          existing = true;
      });
      if (existing === true || species.taxon_rank_sort_order === null || species.taxon_rank_sort_order >= formOptions.speciesMinRank[tabIDX])
        addGridRow(species, speciesTableSelector, tabIDX);
    });
  }
  
  addGridRow = function (species, speciesTableSelector, tabIDX){
    var name, title, row, isNumber, rowTotal = 0;

    if($('#row-' + species.taxon_meaning_id).length>0) {
      row = $('#row-' + species.taxon_meaning_id).removeClass('possibleRemove');
      if($(speciesTableSelector+' #row-' + species.taxon_meaning_id).length == 0) // check if on another page: if so ignore
        return;
      $(speciesTableSelector+' tbody.occs-body').append(row);
      $(speciesTableSelector+' tbody.occs-body tr').each(function(index, elem){
        if((index+$(speciesTableSelector+' tbody:not(.occs-body) tr').length)%2 == 1) $(elem).addClass('alt-row');
        else $(elem).removeClass('alt-row');
      });
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
    row = $('<tr id="row-' + species.taxon_meaning_id + '"><td ' + title + '>' + name + '</td></tr>').data( 'species', species);
    if($(speciesTableSelector+ ' tbody tr').length % 2 == 1) row.addClass('alt-row');
    isNumber = formOptions.occurrence_attribute_ctrl[tabIDX].indexOf('number:true')>=0;
    $.each(formOptions.sections, function(idx, section) {
      var key, cell, myCtrl, val = '';

      if (typeof section.total[speciesTableSelector]==="undefined") {
        section.total[speciesTableSelector]=0;
      }
      // find current value if there is one - the key is the combination of sample id and ttl meaning id that an existing value would be stored as
      key=formOptions.subSamples[section.code] + ':' + species.taxon_meaning_id;
      cell = $('<td class="col-'+(idx+1)+(idx % 5 == 0 ? ' first' : '')+'"/>').appendTo(row);
      // actual control has to be first in cell for cursor keys to work.
      myCtrl = $(formOptions.occurrence_attribute_ctrl[tabIDX]).attr('name', '').appendTo(cell);
      if (typeof formOptions.existingOccurrences[key]!=="undefined") {
        formOptions.existingOccurrences[key]['processed']=true;
        val = formOptions.existingOccurrences[key]['value_'+formOptions.occurrence_attribute[tabIDX]] === null ? '' : formOptions.existingOccurrences[key]['value_'+formOptions.occurrence_attribute[tabIDX]];
        rowTotal += (isNumber && val!=='' ? parseInt(val) : 0);
        section.total[speciesTableSelector] += (isNumber && val!=='' ? parseInt(val) : 0);
        // need to use existing species ttlid (which may or may not be preferred)
        myCtrl.attr('id', 'value:'+formOptions.existingOccurrences[key]['ttl_id']+':'+section.code);
        $('<input type="hidden" id="value:'+formOptions.existingOccurrences[key]['ttl_id']+':'+section.code+':attrId" value="'+formOptions.occurrence_attribute[tabIDX]+'"/>').appendTo(cell);
        // store the ids of the occurrence and attribute we loaded, so future changes to the cell can overwrite the existing records
        $('<input type="hidden" id="value:'+formOptions.existingOccurrences[key]['ttl_id']+':'+section.code+':id" value="'+formOptions.existingOccurrences[key]['o_id']+'"/>').appendTo(cell);
        $('<input type="hidden" id="value:'+formOptions.existingOccurrences[key]['ttl_id']+':'+section.code+':attrValId" value="'+formOptions.existingOccurrences[key]['a_id_'+formOptions.occurrence_attribute[tabIDX]]+'"/>').appendTo(cell);
      } else {
        // this is always the preferred when generated from full list, may be either if from autocomplete.
        myCtrl.attr('id', 'value:'+species.id+':'+section.code);
        $('<input type="hidden" id="value:'+species.id+':'+section.code+':attrId" value="'+formOptions.occurrence_attribute[tabIDX]+'"/>').appendTo(cell);
      }
      myCtrl.addClass((isNumber ? 'count-input' : 'non-count-input')).val(val).data('previous', val);
    });
    if(isNumber) $('<td class="row-total first">'+rowTotal+'</td>').appendTo(row);
    $(speciesTableSelector+' tbody.occs-body').append(row);
    row.find('input.count-input').keydown(occ_keydown).focus(general_focus).change(input_change).blur(input_blur);
    row.find('input.non-count-input,select.non-count-input').keydown(occ_keydown).focus(general_focus).change(input_change).blur(input_blur);
    formOptions.existingOccurrences[':' + species.taxon_meaning_id] = {'processed' : true, 'taxon_meaning_id' : ''+species.taxon_meaning_id};
    if (formOptions.outOfRangeVerification.length > 0) {
      var urlSep = indiciaData.ajaxUrl.indexOf('?') === -1 ? '?' : '&';
      $.getJSON(
        indiciaData.ajaxUrl + '/check_verification_rules/' + indiciaData.nid + 
          urlSep +'location_id=' + indiciaData.parentLocId +
          '&taxon_meaning_id=' + species.taxon_meaning_id +
          '&date=' + formOptions.parentSampleDate + // Assummed YYYY-MM-DD
          '&nonce=' + indiciaData.read.nonce + '&auth_token=' + indiciaData.read.auth_token,
        null,
        function (data) {
          if (data.warnings.length > 0) {
            for (var i = 0; i < data.warnings.length; i++) {
              data.warnings[i] = data.warnings[i] + (data.warnings[i].slice(-1) === '.' ? '' : '.');
            }
            $('#row-'+data.taxon_meaning_id).addClass('range-warning');
            $('#row-'+data.taxon_meaning_id+' td:first').append('<span class="range-warning-icon" ' +
                  'title="'+data.warnings.join(' ')+'"></span>');
          }
        }
      );
    }
  }

  process = function (N) {
    var TaxonData, query = {}, sortCol;

    if (N > formOptions.maxTabs) return;
      $('#taxonLookupControlContainer'+N).show();
      $('#grid'+N+'-loading').show();
      if (formOptions.speciesList[N]>0) {
        sortCol = $('[name=species-sort-order-'+N+']:checked'); // radiobutton
        if (sortCol.length == 0) {
          sortCol = $('[name=species-sort-order-'+N+']'); // hidden field - single option
        }
        sortCol = sortCol.val();
        if (sortCol == '') {
          sortCol='taxon';
        }
        TaxonData = {
          'taxon_list_id': formOptions.speciesList[N],
          'preferred': 't',
          'auth_token': indiciaData.read.auth_token,
          'nonce': indiciaData.read.nonce,
          'mode': 'json',
          'allow_data_entry': 't',
          'view': 'cache',
          'orderby': sortCol
        };
        query = {"in":{}};
        switch (formOptions.speciesListForce[N]) {
          case 'branch': // = all in first branchlist, plus existing
            query['in'].taxon_meaning_id = formOptions.branchTaxonMeaningIDs[0].concat(formOptions.existingTaxonMeaningIDs);
            break;
          case 'full': // = all values in list: by definition will include all existing data on this sample.
            $('#taxonLookupControlContainer'+N).hide();
            break;
          case 'common': // = all in commonlist, plus existing
            query['in'].taxon_meaning_id = formOptions.commonTaxonMeaningIDs.concat(formOptions.existingTaxonMeaningIDs);
            break;
          case 'mine': // = all in mylist, plus existing
            query['in'].taxon_meaning_id = formOptions.myTaxonMeaningIDs.concat(formOptions.existingTaxonMeaningIDs);
            break;
          case 'here': // = all values entered against this transect: by definition will include all existing data on this sample.
          default:
            query['in'].taxon_meaning_id = formOptions.allTaxonMeaningIDsAtTransect;
            break;
        }
        if (typeof formOptions.speciesListFilterField[N] !== "undefined") {
          query['in'][formOptions.speciesListFilterField[N]] = formOptions.speciesListFilterValues[N];
        }
        TaxonData.query = JSON.stringify(query);
      $.ajax({
        'url': indiciaData.warehouseUrl+'index.php/services/data/taxa_taxon_list',
        'data': TaxonData,
        'dataType': 'jsonp',
        'success': function(data) {
          addSpeciesToGrid(data, 'table#transect-input'+N, N);
          // copy across the col totals
          $.each(formOptions.sections, function(idx, section) {
            $('table#transect-input'+N+' tfoot .col-total.col-'+(idx+1)).html(typeof section.total['table#transect-input'+N]==="undefined" ? 0 : section.total['table#transect-input'+N]);
          });
          $('#grid'+N+'-loading').hide();
          process(N+1);
        }
      });
    } else {
      $('#grid'+N+'-loading').hide();
      process(N+1);
    }
  }

  checkErrors = function (data) {
    if (typeof data.error!=="undefined") {
      if (typeof data.errors!=="undefined") {
        $.each(data.errors, function(idx, error) {
          alert(error);
        });
      } else {
        alert('An error occured when trying to save the data');
      }
      // data.transaction_id stores the last cell at the time of the post.
      var selector = '#'+data.transaction_id.replace(/:/g, '\\:');
      $(selector).focus();
      $(selector).select();
      return false;
    } else {
      return true;
    }
  }

  // Define event handlers.
  // TBC this should be OK to use as is.

  smp_keydown = function (evt) {
    var targetRow, cell, targetInput=[], code, parts=evt.target.id.split(':'), type='smpAttr';

    targetRow = $(evt.target).parents('tr');
    cell = $(evt.target).parents('td');
    code=parts[2];

    // down arrow or enter key
    if (evt.keyCode===13 || evt.keyCode===40) {
      targetRow = targetRow.next('tr');
      if (targetRow.length===0) {
        // moving out of sample attributes area into next tbody for counts
        targetRow = $(evt.target).parents('tbody').next('tbody').find('tr:first');
        type='value';
      }
      if (targetRow.length>0) {
        targetInput = targetRow.find("input[id^='"+type+"\:'][id$='\:"+code+"']");
      }
    }

    // up arrow to another smp attr row.
    if (evt.keyCode===38) {
      targetRow = targetRow.prev('tr');
      if (targetRow.length>0) {
        targetInput = targetRow.find("input[id^='"+type+"\:'][id$='\:"+code+"']");
      }
    }

    // right arrow - move to next cell if at end of text
    if (evt.keyCode===39 && evt.target.selectionEnd >= evt.target.value.length) {
      targetInput = cell.next('td').find('input:visible:first');
      if (targetInput.length===0) {
        // end of row, so move down to next if there is one
        targetRow = targetRow.next('tr');
        if (targetRow.length===0) {
          // moving out of sample attributes area into next tbody for counts
          targetRow = $(evt.target).parents('tbody').next('tbody').find('tr:first');
        }
        if (targetRow.length>0) {
          targetInput = targetRow.find('input:visible:first');
        }
      }
    }

    // left arrow - move to previous cell if at start of text
    if (evt.keyCode===37 && evt.target.selectionStart === 0) {
      targetInput = cell.prev('td').find('input:visible:last');
      if (targetInput.length===0) {
        // before start of row, so move up to previous if there is one
        targetRow = targetRow.prev('tr');
        if (targetRow.length>0) {
          targetInput = targetRow.find('input:visible:last');
        }
      }
    }

    if (targetInput.length > 0) {
      $(targetInput).get()[0].focus();
      return false;
    }
  }

  occ_keydown = function (evt) {
    var targetRow, targetInput=[], code, parts=evt.target.id.split(':'), type='value';

    targetRow = $(evt.target).parents('tr');
    cell = $(evt.target).parents('td');
    code=parts[2]; // holds the section code

    // down arrow or enter key
    if (evt.keyCode===13 || evt.keyCode===40) {
      targetRow = targetRow.next('tr');
      if (targetRow.length>0) {
        targetInput = targetRow.find("input[id^='"+type+"\:'][id$='\:"+code+"']");
      }
    }
    
    // up arrow
    if (evt.keyCode===38) {
      targetRow = targetRow.prev('tr');
      if (targetRow.length===0) {
        // moving out of counts area into previous tbody for sample attributes
        targetRow = $(evt.target).parents('tbody').prev('tbody').find('tr:last');
        type='smpAttr';
      }
      if (targetRow.length>0) {
        targetInput = targetRow.find("input[id^='"+type+"\:'][id$='\:"+code+"']");
      }
    }

    // right arrow - move to next cell if at end of text
    if (evt.keyCode===39 && evt.target.selectionEnd >= evt.target.value.length) {
      targetInput = cell.next('td').find('input:visible:first');
      if (targetInput.length===0) {
        // end of row, so move down to next if there is one
        targetRow = targetRow.next('tr');
        if (targetRow.length>0) {
          targetInput = targetRow.find('input:visible:first');
        }
      }
    }

    // left arrow - move to previous cell if at start of text
    if (evt.keyCode===37 && evt.target.selectionStart === 0) {
      targetInput = cell.prev('td').find('input:visible:last');
      if (targetInput.length===0) {
        // before start of row, so move up to previous if there is one
        targetRow = targetRow.prev('tr');
        if (targetRow.length===0) {
          // moving out of counts area into previous tbody for sample attributes
          targetRow = $(evt.target).parents('tbody').prev('tbody').find('tr:last');
        }
        if (targetRow.length>0) {
          targetInput = targetRow.find('input:visible:last');
        }
      }
    }
    
    if (targetInput.length > 0) {
      $(targetInput).get()[0].focus();
      return false;
    }
  }

  general_focus = function (evt) {
    // select the row
    var matches = $(evt.target).parents('td:first')[0].className.match(/col\-\d+/),
      colidx = matches[0].substr(4);
    $(evt.target).parents('table:first').find('.table-selected').removeClass('table-selected');
    $(evt.target).parents('table:first').find('.ui-state-active').removeClass('ui-state-active');
    $(evt.target).parents('div:first').find('table.sticky-header .ui-state-active').removeClass('ui-state-active');
    $(evt.target).parents('tr:first').addClass('table-selected');
    $(evt.target).parents('table:first').find('tbody .col-'+colidx).addClass('table-selected');
    $(evt.target).parents('table:first').find('thead .col-'+colidx).addClass('ui-state-active');
    $(evt.target).parents('div:first').find('table.sticky-header thead .col-'+colidx).addClass('ui-state-active');
  }

  input_change = function (evt) {
    $(evt.target).addClass('edited');
  }

  input_blur = function (evt) {
    var selector = '#'+evt.target.id.replace(/:/g, '\\:'),  
        parts = evt.target.id.split(':'),
        total,
        row = $(evt.target).parents('tr:first')[0],
        warnings = [],
        taxon_meaning_id;

    indiciaData.currentCell = evt.target.id;

    if ($(selector).hasClass('edited')) {
      $(selector).addClass('saving');
      if ($(selector).hasClass('count-input')) {
        // check for number input - don't post if not a number
        if (!$(selector).val().match(/^[0-9]*$/)) { // matches a blank field for deletion
          alert('Please enter a valid number - '+evt.target.id);
          // use a timer, as refocus during blur not reliable.
          setTimeout("jQuery('#"+evt.target.id+"').focus(); jQuery('#"+evt.target.id+"').select()", 100);
          return;
        }
      } else {
        $(selector).val($(selector).val().toUpperCase());
      }
      if ($(selector).hasClass('count-input') || $(selector).hasClass('non-count-input')) {
        if (typeof formOptions.subSamples[parts[2]] == "undefined") {
          alert('Occurrence could not be saved because of a missing sample ID');
          return;
        }
        total = getRowTotal(evt.target);
        taxon_meaning_id = parseInt($(row).attr('id').substring(4));
        if (checkSectionLimit(taxon_meaning_id, $(selector).val())) {
          warnings.push(formOptions.verificationSectionLimitMessage
                .replace('{{ value }}', $(selector).val())
                .replace('{{ limit }}', sectionLimitAsText(taxon_meaning_id)));
        }
        if (checkWalkLimit(taxon_meaning_id, total)) {
          warnings.push(formOptions.verificationWalkLimitMessage
                .replace('{{ total }}', total)
                .replace('{{ limit }}', walkLimitAsText(taxon_meaning_id)));
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
                title: formOptions.verificationTitle,
                buttons: {
                  "No": function() {
                    $(selector).val($(selector).data('previous'));
                    $(selector).removeClass('saving');
                    $(selector).removeClass('edited');
                    dialog.dialog("close");
                  },
                  "Yes": function() {
                    setTotals(evt.target);
                    // need to save the occurrence for the current cell
                    saveOccurrence(selector, parts[1], formOptions.subSamples[parts[2]], evt.target.id);
                    dialog.dialog("close");
                  }
                }
            });
        } else {
          setTotals(evt.target);
          // need to save the occurrence for the current cell
          saveOccurrence(selector, parts[1], formOptions.subSamples[parts[2]], evt.target.id);
        }
      } else if ($(selector).hasClass('smp-input')) {
        // change to just a sample attribute.
        saveSample(parts[2]);
      }
    }
  }

  removeTaggedRows = function(table) {
    var rowCount = 0;
    $(table + ' .possibleRemove').remove();
    $(table + ' tbody').find('tr').each(function(){
      if(rowCount%2===0) {
        $(this).removeClass('alt-row');
      } else {
        $(this).addClass('alt-row');
        rowCount++;
      }
    });
  }

  // Currently hardcoded for list 1 only
  listTypeChange = function(val, table, N) {
    var TaxonData = {
        'taxon_list_id': formOptions.speciesList[N],
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
        branchID;

    $('#grid'+N+'-loading').show();
    $('#taxonLookupControlContainer1').show();
    $('#listSelect'+N).addClass('working');
    $(table + ' .table-selected').removeClass('table-selected');
    $(table + ' .ui-state-active').removeClass('ui-state-active');

    // first tag all blank rows.
    $(table + ' .occs-body tr').removeClass('possibleRemove').each(function(idx, row){
      if($(row).find('input').not(':hidden').not('[value=]').length == 0)
        $(row).addClass('possibleRemove');
    });

    if(typeof formOptions.speciesListFilterField[N] != "undefined") {
      query['in'][formOptions.speciesListFilterField[N]] = formOptions.speciesListFilterValues[N];
      // WARNING if filter field = taxon_meaning_id , potential clash: not currently used in UKBMS.
    }

    if (val.slice(0,6) === "branch") {
      branchID = val;
      val = "branch";
    }
    switch(val){
      case 'branch':
        if(formOptions.branchTaxonMeaningIDs.length > 0) {
          valid = true;
          query["in"]["taxon_meaning_id"] = formOptions.branchTaxonMeaningIDs[formOptions.branchSpeciesLists[branchID]];
        }
        break;
      case 'full':
        valid = true;
        $('#taxonLookupControlContainer1').hide();
        $(table + ' .possibleRemove').removeClass('possibleRemove');
        break;
      case 'common':
        if(formOptions.commonTaxonMeaningIDs.length > 0) {
          valid = true;
          query["in"]["taxon_meaning_id"] = formOptions.commonTaxonMeaningIDs;
        }
        break;
      case 'mine':
        if(formOptions.myTaxonMeaningIDs.length > 0) {
          valid = true;
          query["in"]["taxon_meaning_id"] = formOptions.myTaxonMeaningIDs;
        }
        break;
      case 'here':
      default:
        if(formOptions.allTaxonMeaningIDsAtTransect.length > 0) {
          valid = true;
          query["in"]["taxon_meaning_id"] = formOptions.allTaxonMeaningIDsAtTransect;
        }
        break;
    }
    $(table).parent().find('.sticky-header').remove();
    $(table).find('thead.tableHeader-processed').removeClass('tableHeader-processed');
    $(table).removeClass('tableheader-processed');
    $(table).addClass('sticky-enabled');
    if(valid) {
      if(!$.isEmptyObject(query["in"])) {
        TaxonData.query = JSON.stringify(query);
      }
      $.ajax({
          'url': indiciaData.warehouseUrl+'index.php/services/data/taxa_taxon_list',
          'data': TaxonData,
          'dataType': 'jsonp',
          'success': function(data) {
              addSpeciesToGrid(data, table, N);
              // at this point only adding empty rows, so no affect on totals.
              removeTaggedRows(table); // redoes row classes
              $('#grid'+N+'-loading').hide();
              $('#listSelect'+N).removeClass('working');
              Drupal.behaviors.tableHeader.attach($(table).parent()); // Drupal 7
          }
      });
    } else {
      removeTaggedRows(table);
      $('#grid'+N+'-loading').hide();
      $('#listSelect'+N).removeClass('working');
      Drupal.behaviors.tableHeader.attach($(table).parent()); // Drupal 7
    }
  }

  //autocompletes assume ID
  bindSpeciesAutocomplete = function (selectorID, tableSelectorID, url, lookupListId, lookupMinRank, lookupListFilterField, lookupListFilterValues, readAuth, duplicateMsg, max, tabIDX) {
    // inner function to handle a selection of a taxon from the autocomplete
    var handleSelectedTaxon = function(event, data) {
      var table = $(tableSelectorID);
      if($('#row-'+data.taxon_meaning_id).length>0){
        alert(duplicateMsg);
        $(event.target).val('');
        return;
      }
      addGridRow(data, tableSelectorID, tabIDX);
      $(event.target).val('');
      table.parent().find('.sticky-header').remove();
      table.find('thead.tableHeader-processed').removeClass('tableHeader-processed');
      table.removeClass('tableheader-processed');
      table.addClass('sticky-enabled');
      Drupal.behaviors.tableHeader.attach(table.parent()); // Drupal 7
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
    if(typeof lookupListFilterField != "undefined"){
      extra_params.query = '{"in":{"'+lookupListFilterField+'":'+JSON.stringify(lookupListFilterValues)+"}}";
    };

    // Attach auto-complete code to the input
    var ctrl = $('#' + selectorID).autocomplete(url+'/taxa_taxon_list', {
        extraParams : extra_params,
        max : max,
        parse: function(data) {
          var results = [];
          $.each(data, function(i, item) {
              if (item.taxon_rank_sort_order === null || item.taxon_rank_sort_order >= lookupMinRank)
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
    setTimeout(function() { $('#' + ctrl.attr('id')).focus(); });
  }

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

  checkSectionLimit = function (taxon_meaning_id, value) {
    for (var i = 0; i < formOptions.outOfRangeVerification.length; i++) {
      if (formOptions.outOfRangeVerification[i].taxon_meaning_id == taxon_meaning_id &&
          typeof formOptions.outOfRangeVerification[i].section_limit !== "undefined") {
        return parseInt(formOptions.outOfRangeVerification[i].section_limit, 10) < parseInt(value, 10);
      }
    };
    return false;
  }

  checkWalkLimit = function (taxon_meaning_id, total) {
    for (var i = 0; i < formOptions.outOfRangeVerification.length; i++) {
      if (formOptions.outOfRangeVerification[i].taxon_meaning_id == taxon_meaning_id &&
          typeof formOptions.outOfRangeVerification[i].walk_limit !== "undefined") {
        return parseInt(formOptions.outOfRangeVerification[i].walk_limit, 10) < parseInt(total, 10);
      }
    };
    return false;
  }

  sectionLimitAsText = function (taxon_meaning_id) {
    for (var i = 0; i < formOptions.outOfRangeVerification.length; i++) {
      if (formOptions.outOfRangeVerification[i].taxon_meaning_id == taxon_meaning_id &&
          typeof formOptions.outOfRangeVerification[i].section_limit !== "undefined") {
        return formOptions.outOfRangeVerification[i].section_limit;
      }
    };
    return 'NA';
  }

  walkLimitAsText = function (taxon_meaning_id) {
    for (var i = 0; i < formOptions.outOfRangeVerification.length; i++) {
      if (formOptions.outOfRangeVerification[i].taxon_meaning_id == taxon_meaning_id &&
          typeof formOptions.outOfRangeVerification[i].walk_limit !== "undefined") {
        return formOptions.outOfRangeVerification[i].walk_limit;
      }
    };
    return 'NA';
  }

}) (jQuery);

