// Declare a hook for functions that call when dynamic content updated.
// For example:
// indiciaFns.hookDynamicAttrsAfterLoad.push(function(div, type) {
//   $(div).prepend('<h1>' + type + '</h1>');
// });
indiciaFns.hookDynamicAttrsAfterLoad = [];

/**
 * Plugin for the taxon associatione grid.
 */
(function taxonAssociationsPlugin() {
  'use strict';
  var $ = jQuery;

  /**
   * Place to store public methods.
   */
  var methods;

  /**
   * Declare default settings.
   */
  var defaults = {
  };

  /**
   * Table elements.
   */
  var elems = {};

  var rowCount = 0;

  function acParse(data) {
    var results = [];
    $.each(data, function() {
      results.push({
        data: this,
        result: this.searchterm,
        value: this.taxa_taxon_list_id
      });
    });
    return results;
  }

  function acFormatItem(item) {
    var r;
    var nameTest;
    if (item.language_iso !== null && item.language_iso.toLowerCase() === 'lat') {
      r = '<em>' + item.taxon + '</em>';
    } else {
      r = '<span>' + item.taxon + '</span>';
    }
    if (item.authority) {
      r += ' ' + item.authority;
    }
    // This bit adds '- common' or '- latin' depending on what was being searched
    nameTest = (item.preferred_taxon !== item.taxon || item.preferred_authority !== item.authority);
    if (item.preferred === 't' && item.default_common_name !== item.taxon && item.default_common_name) {
      r += '<br/>' + item.default_common_name;
    } else if (item.preferred === 'f' && nameTest && item.preferred_taxon) {
      synText = item.language_iso === 'lat' ? 'syn. of' : '';
      r += '<br/>[';
      if (item.language_iso === 'lat') {
        r += 'syn. of ';
      }
      r += '<em>' + item.preferred_taxon + '</em>';
      if (item.preferred_authority) {
        r += ' ' + item.preferred_authority;
      }
      r += ']';
    }
    r += '<br/><strong>' + item.taxon_group + '</strong>';
    return r;
  }

  function addSelect(name, terms, tr) {
    var select = $('<select class="form-control" name="associated-taxon-' + name + 'Id:' + rowCount + '"></select>');
    $('<option value="">&lt;Please select&gt;</option>').appendTo(select);
    $.each(terms, function eachListItem() {
      $('<option value="' + this[0] + '">' + this[1] + '</option>').appendTo(select);
    });
    return select.appendTo(
      $('<td>').appendTo(tr)
    );
  }

  function loadExistingRows(el) {
    if ($('input[name="taxon_meaning\\:id"]').length > 0 && $('input[name="taxon_meaning\\:id"]').val() !== '') {
      $.ajax({
        dataType: 'jsonp',
        url: indiciaData.read.url + 'index.php/services/data/taxon_association' +
          '?from_taxon_meaning_id=' + $('input[name="taxon_meaning\\:id"]').val() +
          '&nonce=' + indiciaData.read.nonce + '&auth_token=' + indiciaData.read.auth_token +
          '&mode=json&callback=?',
        success: function(data) {
          $.each(data, function eachData() {
            var tr = addRow(el);
            $(tr).find('.associated-taxon').val(this.to_taxon);
            $('<input type="hidden" class="associated-taxon-id" name="associated-taxon-id:' + rowCount + '" value="' + this.id + '" />')
              .appendTo($(tr).find('td:first-child'));
            $('[name="associated-taxon-tmId:' + rowCount + '"]').val(this.to_taxon_meaning_id);
            $('[name="associated-taxon-typeId:' + rowCount + '"]').val(this.association_type_id);
            if (this.part_id) {
              $('[name="associated-taxon-partId:' + rowCount + '"]').val(this.part_id);
            }
            if (this.position_id) {
              $('[name="associated-taxon-positionId:' + rowCount + '"]').val(this.position_id);
            }
            if (this.impact_id) {
              $('[name="associated-taxon-impactId:' + rowCount + '"]').val(this.impact_id);
            }

          });
          // add a blank row for new records
          addRow(el);
        }
      });
    }
    else {
      addRow(el);
    }
  }

  function addRow(el) {
    var tr;
    var thisTaxonName = $('#taxon\\:taxon').val();
    var hiddens;
    rowCount++;
    tr = $('<tr data-rowIndex="' + rowCount + '">').appendTo(elems.tbody);
    hiddens = '<input class="associated-taxon-tmId" name="associated-taxon-tmId:' + rowCount + '" type="hidden" />';
    if (!thisTaxonName) {
      thisTaxonName = indiciaData.lang.taxonassoc.taxonBeingEdited;
    }
    if (el.settings.association_type_id) {
      hiddens += '<input type="hidden" class="associated-taxon-typeId" name="associated-taxon-typeId:' + rowCount + '" value="' + el.settings.association_type_id + '" />';
    }
    $('<td class="this-taxon-name">' + thisTaxonName + '</td>').appendTo(tr);
    $('<td><input class="associated-taxon form-control" placeholder="' + indiciaData.lang.taxonassoc.taxonPlaceholder + '" />'
      + hiddens + '</td>')
      .appendTo(tr);
    if (typeof indiciaData.termlistData.association_type_termlist_id !== 'undefined') {
      addSelect('type', indiciaData.termlistData.association_type_termlist_id, tr)
        .addClass('associated-taxon-typeId');
    }
    if (typeof indiciaData.termlistData.part_termlist_id !== 'undefined') {
      addSelect('part', indiciaData.termlistData.part_termlist_id, tr);
    }
    if (typeof indiciaData.termlistData.position_termlist_id !== 'undefined') {
      addSelect('position', indiciaData.termlistData.position_termlist_id, tr);
    }
    if (typeof indiciaData.termlistData.impact_termlist_id !== 'undefined') {
      addSelect('impact', indiciaData.termlistData.impact_termlist_id, tr);
    }
    // Button to remove row.
    $('<td><span class="fas fa-trash-alt associated-taxon-remove"></span></td>').appendTo(tr);
    $(tr).find('.associated-taxon').autocomplete(indiciaData.warehouseUrl + 'index.php/services/data/taxa_search', {
      extraParams: $.extend({ preferred: 't', taxon_list_id: el.settings.taxon_list_id }, indiciaData.read),
      simplify: false,
      selectMode: false,
      warnIfNoMatch: true,
      continueOnBlur: true,
      matchContains: false,
      parse: acParse,
      formatItem: acFormatItem
    });
    $(tr).find('.associated-taxon').result(function autocompleteResult(event, data) {
      $(tr).find('.associated-taxon-tmId').attr('value', data.taxon_meaning_id);
    });
    return tr;
  }

  /**
   * Declare public methods.
   */
  methods = {
    /**
     * Initialise the esMap  plugin.
     *
     * @param array options
     */
    init: function init(options) {
      var el = this;

      el.settings = $.extend({}, defaults);
      // Apply settings passed in the HTML data-* attribute.
      if (typeof $(el).attr('data-config') !== 'undefined') {
        $.extend(el.settings, JSON.parse($(el).attr('data-config')));
      }
      // Apply settings passed to the constructor.
      if (typeof options !== 'undefined') {
        $.extend(el.settings, options);
      }
      // Tag the form submission so we know to process associations.
      $('<input type="hidden" name="process-associations" value="1" />').appendTo(el);
      elems.table = $('<table class="table">').appendTo(el);
      elems.thead = $('<thead>').appendTo(elems.table);
      elems.thr = $('<tr>').appendTo(elems.thead);
      $('<th>' + indiciaData.lang.taxonassoc.hdrTaxon + ' ' + indiciaData.templates.requiredsuffix + '</th>').appendTo(elems.thr);
      $('<th>' + indiciaData.lang.taxonassoc.hdrAssocTaxon + ' ' + indiciaData.templates.requiredsuffix + '</th>').appendTo(elems.thr);
      if (typeof indiciaData.termlistData.association_type_termlist_id !== 'undefined') {
        $('<th>' + indiciaData.lang.taxonassoc.hdrAssocType + ' ' + indiciaData.templates.requiredsuffix + '</th>')
          .appendTo(elems.thr);
      }
      if (typeof indiciaData.termlistData.part_termlist_id !== 'undefined') {
        $('<th>' + indiciaData.lang.taxonassoc.hdrAssocPart + '</th>').appendTo(elems.thr);
      }
      if (typeof indiciaData.termlistData.position_termlist_id !== 'undefined') {
        $('<th>' + indiciaData.lang.taxonassoc.hdrAssocPosition + '</th>').appendTo(elems.thr);
      }
      if (typeof indiciaData.termlistData.impact_termlist_id !== 'undefined') {
        $('<th>' + indiciaData.lang.taxonassoc.hdrAssocImpact + '</th>').appendTo(elems.thr);
      }
      $('<th/>');
      elems.tbody = $('<tbody>').appendTo(elems.table);
      indiciaFns.on('change', '#taxon\\:taxon', {}, function onTaxonChange() {
        $('.this-taxon-name').html($(this).val());
      });
      indiciaFns.on('click', '.associated-taxon-remove', {}, function removeAssoc() {
        var tr = $(this).closest('tr');
        if (tr.find('.associated-taxon-id').length > 0) {
          $(tr).find('td:first-child').append('<input type="hidden" name="associated-taxon-deleted:' +
            $(tr).attr('data-rowindex') + '" value="t" />');
          $(tr).addClass('deleted');
        }
        else {
          $(this).closest('tr').remove();
        }
      });
      loadExistingRows(el);
      elems.button = $('<button type="button" class="btn btn-primary">' + indiciaData.lang.taxonassoc.btnAddNew + '</button>')
        .insertAfter($(elems.table));
      $(elems.button).click(function addClick() {
        if ($(elems.tbody).find('tr:last-child .associated-taxon-tmId').val() !== '') {
          addRow(el);
        }
        $(elems.tbody).find('tr:last-child .associated-taxon').focus();
      });
      $(el).closest('form').submit(function formSubmit(e) {
        var scrolled = false;
        var rowsWithAssocTaxon = $('.associated-taxon-tmId')
          .filter(function() {
            return $(this).val() !== '';
          });
        // Remove existing errors.
        $('.taxon-associations .text-danger').remove();
        $('.taxon-associations td').removeClass('has-error');
        $.each(rowsWithAssocTaxon, function eachRow() {
          var tr = $(this).closest('tr');
          var typeField = $(tr).find('.associated-taxon-typeId');
          if (!typeField.val().match(/^\d+$/)) {
            $(typeField).closest('td').addClass('has-error');
            $(typeField).after('<p class="text-danger small">' + indiciaData.lang.taxonassoc.pleaseSelectType + '</p>');
            // Ensure visible.
            if (!scrolled) {
              $('html, body').animate({
                scrollTop: $(typeField).top - 50
              });
              scrolled = true;
            }
            indiciaData.formSubmitted = false;
            e.preventDefault();
          }
        });
      });
    }

  };

  /**
   * Extend jQuery to declare esMap method.
   */
  $.fn.taxonAssociations = function taxonAssociations(methodOrOptions) {
    var passedArgs = arguments;
    $.each(this, function callOnEachOutput() {
      if (methods[methodOrOptions]) {
        // Call a declared method.
        return methods[methodOrOptions].apply(this, Array.prototype.slice.call(passedArgs, 1));
      } else if (typeof methodOrOptions === 'object' || !methodOrOptions) {
        // Default to "init".
        return methods.init.apply(this, passedArgs);
      }
      // If we get here, the wrong method was called.
      $.error('Method ' + methodOrOptions + ' does not exist on jQuery.taxonAssociations');
      return true;
    });
    return this;
  };

}());

jQuery(document).ready(function docReady($) {

  function changeTaxonRestrictionInputs() {
    var urlSep = indiciaData.ajaxUrl.indexOf('?') === -1 ? '?' : '&';
    if ($('#taxa_taxon_list\\:parent_id').val() !== '') {
      $.each($('.taxon-dynamic-attributes'), function loadAttrDiv() {
        var div = this;
        // 0 is a fake nid, since we don't care.
        $.get(indiciaData.ajaxUrl + '/dynamicattrs/' + indiciaData.nid + urlSep +
            'taxon_list_id=' + $('#taxa_taxon_list\\:taxon_list_id').val() +
            '&taxa_taxon_list_id=' + $('#taxa_taxon_list\\:parent_id').val() +
            '&language=' + indiciaData.userLang +
            '&options=' + JSON.stringify(indiciaData.dynamicAttrOptions), null,
          function getAttrsReportCallback(data) {
            $(div).html(data);
            $.each(indiciaFns.hookDynamicAttrsAfterLoad, function callHook() {
              this(div);
            });
          }
        );
      });
    }
  }

  // On selection of a taxon or change of sex/stage attribute, load any dynamically linked attrs into the form.
  $('#taxa_taxon_list\\:parent_id').change(changeTaxonRestrictionInputs);
});

