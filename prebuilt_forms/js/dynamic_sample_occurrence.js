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
            '&language=' + indiciaData.userLang +
            '&options=' + JSON.stringify(indiciaData['dynamicAttrOptions' + type]), null,
          function getAttrsReportCallback(data) {
            // Ensure page level onload functions don't run again.
            indiciaData.onloadFns = [];
            $(div).html(data);
            $.each(indiciaFns.hookDynamicAttrsAfterLoad, function callHook() {
              this(div, type);
            });
          }
        );
      });
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
    $(taxonRestrictionInputSelectors).change(changeTaxonRestrictionInputs);
  }
});
