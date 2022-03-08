
// Functions
var uspPrepChart, _get_week_number;
// Global Data
var reportOptions;

(function ($) {

  // Initially the chart is blank.
  uspPrepChart = function(options) {

    reportOptions = options;

    $('#'+reportOptions.species1SelectID+',#'+reportOptions.species2SelectID+',#'+reportOptions.weekNumSelectID).attr('disabled',true);

    $('#loadButton').click(function(){
      var locationType = $('#'+ reportOptions.locationTypeSelectID).val(),
          location = $('#'+ reportOptions.locationSelectIDPrefix+'-'+locationType).val(),
          weekstart_date,
          weekOne_date,
          cookieParams = $.cookie('providedParams'),
          cookieData = {};

      if(locationType == '' || location == '' || $(this).hasClass('waiting-button'))
        return;

      $('#'+reportOptions.id).empty();
      $('#'+reportOptions.id).data('jqplot','N');
      $(this).addClass('waiting-button');
      $('#'+reportOptions.species1SelectID+',#'+reportOptions.species2SelectID+',#'+reportOptions.weekNumSelectID).attr('disabled',true);

      reportOptions.loadedYear = $('#'+ reportOptions.yearSelectID).val();
      reportOptions.loadedLocationType = locationType;

      if(typeof reportOptions.reportGroup != 'undefined' && typeof reportOptions.rememberParamsReportGroup != 'undefined' ) {
        // attempt to store the location type in a cookie.
        if (cookieParams == null) {
          cookieData[reportOptions.rememberParamsReportGroup] = {};
        } else {
          cookieData = JSON.parse(cookieParams);
          if (typeof cookieData[reportOptions.rememberParamsReportGroup] == 'undefined') {
            cookieData[reportOptions.rememberParamsReportGroup] = {};
          }
        }
        cookieData[reportOptions.rememberParamsReportGroup][reportOptions.reportGroup+'-location_type_id'] = locationType;
        indiciaFns.cookie('providedParams', JSON.stringify(cookieData));
      }

      reportOptions.loadedSurveyID = reportOptions.surveyMapping[locationType].survey_id;
      reportOptions.loadedLocation = $('#'+ reportOptions.locationSelectIDPrefix+'-'+locationType+' option:selected').text();

      $('#currentlyLoaded').empty().append('LOADING : ' + reportOptions.loadedYear + ' : ' + reportOptions.loadedLocation);

      if(reportOptions.weekstart[0]=='date'){
        weekstart_date = new Date(reportOptions.loadedYear + "/" + reportOptions.weekstart[1]); // assuming correct format
        reportOptions.weekStartDay=weekstart_date.getDay() + 1;
      } // weekstart should be in range 1=Monday to 7=Sunday
      else reportOptions.weekStartDay = reportOptions.weekstart[1];

      if(reportOptions.weekOneContains != "")
        weekOne_date = new Date(reportOptions.loadedYear + '/' + reportOptions.weekOneContains);
      else
        weekOne_date = new Date(reportOptions.loadedYear + '/Jan/01');
      weekOne_date_weekday = weekOne_date.getDay() + 1; // in range 1=Monday to 7=Sunday
      reportOptions.zeroWeekStartDate = new Date(weekOne_date.getTime());

      reportOptions.allWeeksMax = parseInt(reportOptions.allWeeksMax);

      // scan back to start of week one, then back 7 days to start of week zero
      // these work across month & year boundaries
      if(weekOne_date_weekday >= reportOptions.weekStartDay)
        reportOptions.zeroWeekStartDate.setDate(weekOne_date.getDate() - (weekOne_date_weekday-reportOptions.weekStartDay) - 7);
      else
        reportOptions.zeroWeekStartDate.setDate(weekOne_date.getDate() - (7+weekOne_date_weekday-reportOptions.weekStartDay) - 7);

      // get the child locations - the sections. We then populate the X labels.
      $.getJSON(indiciaData.read.url + "index.php/services/data/location?parent_id="+location +
                '&mode=json&view=detail&auth_token=' + indiciaData.read.auth_token +
                '&nonce=' + indiciaData.read.nonce + "&callback=?",
        function(sdata) {
          var chartLabels = [];
          reportOptions.sectionIDMapping = [];
          reportOptions.sectionCodeMapping = [];
          $.each(sdata, function(idx, section) {
            reportOptions.sectionIDMapping[section.id] = section;
            reportOptions.sectionCodeMapping[section.code] = section;
            chartLabels.push(section.code);
          });
          chartLabels.sort(function (a, b) {
            return (a.charAt(0) == 'S' && b.charAt(0) == 'S') ? (a.substr(1) - b.substr(1)) : 0;
          });
          $.each(chartLabels, function(idx,lbl){
            reportOptions.sectionIDMapping[reportOptions.sectionCodeMapping[lbl].id].idx = idx;
          });
          reportOptions.opts.axes.xaxis.ticks = chartLabels;
          // convert the location_type into a survey_id

          $.getJSON(reportOptions.base_url+'/index.php/services/report/requestReport?report='+reportOptions.dataSource+'.xml' +
                    '&reportSource=local&mode=json' +
                    '&auth_token='+indiciaData.read.auth_token+'&reset_timeout=true&nonce='+indiciaData.read.nonce +
                    reportOptions.reportExtraParams +
                    '&callback=?' +
                    '&year='+reportOptions.loadedYear+'&date_from='+reportOptions.loadedYear+'-01-01&date_to='+reportOptions.loadedYear+'-12-31' +
                    '&location_type_id='+reportOptions.loadedLocationType+'&location_id='+location+
                    '&survey_id='+reportOptions.loadedSurveyID+
                    '&locattrs=',
            function(rdata){
              reportOptions.values = []; // taxon->weeknumber->section
              reportOptions.species = [];
              reportOptions.parentSampleList = [];
              for(var i=reportOptions.allWeeksMin; i<=reportOptions.allWeeksMax+2; i++) {
                reportOptions.parentSampleList[i] = [];
              }
              if (rdata.length) {
                $('#'+reportOptions.id).append('<p class="graph-body-warning">'+reportOptions.selectPrompt+'</p>');
                $('#currentlyLoaded').empty().append(reportOptions.loadedYear + ' : ' + reportOptions.loadedLocation);
              } else {
                $('#'+reportOptions.id).append('<p class="graph-body-warning">'+reportOptions.bodyWarning+'</p>');
                $('#currentlyLoaded').empty().append(reportOptions.noDataLoadedMsg + ' ' + reportOptions.loadedLocation + ', ' + reportOptions.loadedYear);
              }
              $.each(rdata, function(idx, occurrence){
                var weekNum = _get_week_number(occurrence);
                if(weekNum < reportOptions.allWeeksMin || weekNum > reportOptions.allWeeksMax)
                  return;
                if(typeof reportOptions.values[occurrence.taxon_meaning_id] == 'undefined') {
                  reportOptions.species.push({'taxon': occurrence.taxon, 'preferred_taxon': occurrence.preferred_taxon, 'taxon_meaning_id':occurrence.taxon_meaning_id});
                  reportOptions.values[occurrence.taxon_meaning_id] = [];
                  // NB all weeks max + 1 = All weeks, all weeks max + 2 = in season weeks
                  for(var i=reportOptions.allWeeksMin; i<=reportOptions.allWeeksMax+2; i++) {
                    reportOptions.values[occurrence.taxon_meaning_id][i] = [];
                    for(var j=0; j<reportOptions.opts.axes.xaxis.ticks.length; j++) {
                      reportOptions.values[occurrence.taxon_meaning_id][i][j] = 0;
                    }
                  }
                }
                sectionNum = reportOptions.sectionIDMapping[occurrence.section_id].idx;
                if(parseInt(occurrence[reportOptions.countOccAttr]) != occurrence[reportOptions.countOccAttr]) occurrence[reportOptions.countOccAttr] = 0;
                  switch(reportOptions.dataCombining){
                    case 'max':
                      reportOptions.values[occurrence.taxon_meaning_id][weekNum][sectionNum] = Math.max(reportOptions.values[occurrence.taxon_meaning_id][weekNum][sectionNum], parseInt(occurrence[reportOptions.countOccAttr]));
                      break;
                    default : // location and add
                    if(reportOptions.parentSampleList[weekNum].indexOf(occurrence.parent_sample_id) < 0)
                      reportOptions.parentSampleList[weekNum].push(occurrence.parent_sample_id);
                      reportOptions.values[occurrence.taxon_meaning_id][weekNum][sectionNum] += parseInt(occurrence[reportOptions.countOccAttr]);
                      break;
                  }
              });

              // Next sort out the species list drop downs.
              $('#'+reportOptions.species1SelectID+' option,#'+reportOptions.species2SelectID+' option').remove();
              if(reportOptions.species.length) {
                $('#'+reportOptions.species1SelectID+',#'+reportOptions.species2SelectID).append('<option value="" class="italic-option">&lt;'+reportOptions.pleaseSelectMsg+'&gt;</option><option value="0">'+reportOptions.allSpeciesMsg+'</option>');
                reportOptions.species.sort(function (a, b) { return a[reportOptions.taxon_column].localeCompare(b[reportOptions.taxon_column]); } );
                $('#'+reportOptions.species1SelectID+',#'+reportOptions.species2SelectID+',#'+reportOptions.weekNumSelectID).removeAttr('disabled');
                $.each(reportOptions.species, function(idx, species){
                  if(reportOptions.dataCombining == 'location')
                    for(var i=reportOptions.allWeeksMin; i<=reportOptions.allWeeksMax; i++) {
                      for(var j=0; j<reportOptions.opts.axes.xaxis.ticks.length; j++) {
                        if(reportOptions.parentSampleList[i].length > 1) {
                          reportOptions.values[species.taxon_meaning_id][i][j] = reportOptions.values[species.taxon_meaning_id][i][j] / reportOptions.parentSampleList[i].length;
                          switch(reportOptions.dataRound){
                            case 'nearest':
                              reportOptions.values[species.taxon_meaning_id][i][j] = parseInt(Math.round(reportOptions.values[species.taxon_meaning_id][i][j]));
                              break;
                            case 'up':
                              reportOptions.values[species.taxon_meaning_id][i][j] = parseInt(Math.ceil(reportOptions.values[species.taxon_meaning_id][i][j]));
                              break;
                            case 'down':
                              reportOptions.values[species.taxon_meaning_id][i][j] = parseInt(Math.floor(reportOptions.values[species.taxon_meaning_id][i][j]));
                              break;
                            case 'none':
                            default : break;
                          }
                        }
                      }
                    }
                  for(var i=reportOptions.allWeeksMin; i<=reportOptions.allWeeksMax; i++) {
                    for(var j=0; j<reportOptions.opts.axes.xaxis.ticks.length; j++) {
                      reportOptions.values[species.taxon_meaning_id][reportOptions.allWeeksMax+1][j] += reportOptions.values[species.taxon_meaning_id][i][j];
                      if(i >= reportOptions.seasonWeeksMin && i <= reportOptions.seasonWeeksMax) {
                        reportOptions.values[species.taxon_meaning_id][reportOptions.allWeeksMax+2][j] += reportOptions.values[species.taxon_meaning_id][i][j];
                      }
                    }
                  }
                  $('#'+reportOptions.species1SelectID+',#'+reportOptions.species2SelectID)
                      .append('<option value="'+species.taxon_meaning_id+'" title="'+
                        (species.taxon != species.preferred_taxon ? (reportOptions.taxon_column === 'taxon' ? species.preferred_taxon : species.taxon) : '')+
                        '">'+species[reportOptions.taxon_column]+'</option>');
                });
                // have to do all species calcs after the data rounding. special taxon_meaning_id of 0
                // reportOptions.species.unshift({'taxon': reportOptions.allSpeciesMsg, 'preferred_taxon': reportOptions.allSpeciesMsg, 'taxon_meaning_id':0});
                reportOptions.values[0] = [];
                for(var i=reportOptions.allWeeksMin; i<=reportOptions.allWeeksMax+2; i++) {
                  reportOptions.values[0][i] = [];
                  for(var j=0; j<reportOptions.opts.axes.xaxis.ticks.length; j++) {
                    reportOptions.values[0][i][j] = 0;
                    $.each(reportOptions.species, function(idx, species){
                      reportOptions.values[0][i][j] += reportOptions.values[species.taxon_meaning_id][i][j];
                    });
                  }
                }
              } else {
                $('#'+reportOptions.species1SelectID+',#'+reportOptions.species2SelectID).append('<option value="">&lt;'+reportOptions.noDataMsg+'&gt;</option>');
              }
              $('#loadButton').removeClass('waiting-button');
          });
      });
    });

    $('#'+reportOptions.species1SelectID+',#'+reportOptions.species2SelectID+',#'+reportOptions.weekNumSelectID).change(function(){
      var week = $('#'+reportOptions.weekNumSelectID).val(),
          seriesData = [],
          yMax = 0,
          yTickinterval;

      reportOptions.opts.series = [];
      reportOptions.opts.axes.yaxis.ticks = [];
      $('#'+reportOptions.id).empty(); // can we destroy?
      $('#'+reportOptions.id).data('jqplot','N');

//      reportOptions.opts.title.text = reportOptions.loadedYear + ' : ' + reportOptions.loadedLocation + ' : ';
      if(week == 'all') {
        week = reportOptions.allWeeksMax+1;
//        reportOptions.opts.title.text += reportOptions.allWeeksDescription + ' (' + reportOptions.allWeeksMin + ' &gt ' + reportOptions.allWeeksMax + ')';
      } else if(week == 'season') {
        week = reportOptions.allWeeksMax+2;
//        reportOptions.opts.title.text += reportOptions.seasonWeeksDescription + ' (' + reportOptions.seasonWeeksMin + ' &gt ' + reportOptions.seasonWeeksMax + ')';
      } else if(week == '') {
        return;
//      } else {
//        reportOptions.opts.title.text += reportOptions.weekLabel + ' ' + week;
      }
      if($('#'+reportOptions.species1SelectID).val() != '') {
        $.each(reportOptions.values[$('#'+reportOptions.species1SelectID).val()][week], function(idx, val) {
          yMax = Math.max(yMax, val);
        });
        reportOptions.opts.series.push({"show":true,"label":$('#'+reportOptions.species1SelectID+' option:selected').text(),"showlabel":true});
        seriesData.push(reportOptions.values[$('#'+reportOptions.species1SelectID).val()][week]);
      }
      if($('#'+reportOptions.species2SelectID).val() != '') {
        $.each(reportOptions.values[$('#'+reportOptions.species2SelectID).val()][week], function(idx, val) {
          yMax = Math.max(yMax, val);
        });
        reportOptions.opts.series.push({"show":true,"label":$('#'+reportOptions.species2SelectID+' option:selected').text(),"showlabel":true});
        seriesData.push(reportOptions.values[$('#'+reportOptions.species2SelectID).val()][week]);
      }
      var yTickinterval;
      if (yMax < 10) {
        yTickinterval = 1;
        yMax = yMax < 4 ? 4 : yMax;
      } else {
        var scale = Math.ceil(Math.log10(yMax));
        if (yMax/Math.pow(10, scale) > 0.5) {
          yTickinterval = Math.pow(10, scale-1);
        } else if (yMax/Math.pow(10, scale) > 0.2) {
          yTickinterval = 0.5*Math.pow(10,scale-1);
        } else {
          yTickinterval = 0.2*Math.pow(10,scale-1);
        }
      }
      for(i=0; i<=yMax+yTickinterval; i += yTickinterval) {
        reportOptions.opts.axes.yaxis.ticks.push(i);
      }
      if(seriesData.length == 0) {
        $('#'+reportOptions.id).append('<p class="graph-body-warning">'+reportOptions.selectPrompt+'</p>');
        return;
      }
      reportOptions.seriesData = seriesData;
      reportOptions.opts.seriesColors = ['#ff6060', '#6060ff'];
      $.jqplot(reportOptions.id, reportOptions.seriesData, reportOptions.opts);
      $('#'+reportOptions.id).data('jqplot','Y');
    });

    $('[id^='+ reportOptions.locationSelectIDPrefix + '-]').change(function(){
      if($(this).filter(":visible").length > 0) {
        if($(this).val()=='')
          $('#loadButton').attr('disabled','disabled');
        else
          $('#loadButton').removeAttr('disabled');
      }
    });

    $('#'+ reportOptions.locationTypeSelectID).change(function(){
      $('.location-select').hide();
      $('#'+ reportOptions.locationSelectIDPrefix + '-' + $(this).val()).show().change();
    });
    $('#'+ reportOptions.locationTypeSelectID).change();
    $('[id^='+ reportOptions.locationSelectIDPrefix + '-]').change();

    $(window).resize(function(){
        // Calculate scaling factor to alter dimensions according to width.
      if(typeof $('#'+reportOptions.id).data('jqplot') == 'undefined' ||
          $('#'+reportOptions.id).data('jqplot') != 'Y')
        return;
      var scaling = 1,
          shadow = true,
          location = 'ne', // leave placement as defined by form.
          width = $(window).width()
      if (width < 480) {
        scaling = 0;
        shadow = false;
        location = (reportOptions.opts.legend.placement == 'outsideGrid' ? 's' : 'n');
      } else if (width < 1024) {
        scaling = (width - 480) / (1024 - 480);
        location = (reportOptions.opts.legend.placement == 'outsideGrid' ? 's' : 'n');
      }
      reportOptions.opts.legend.location = location;
      reportOptions.opts.seriesDefaults.rendererOptions.shadow = shadow;
      reportOptions.opts.seriesDefaults.rendererOptions.shadowWidth = scaling * 3;
      reportOptions.opts.seriesDefaults.rendererOptions.barMargin = 8 * scaling + 2;

      $('#'+reportOptions.id).empty(); // can we destroy?
      // can't use replot as moving the legend about.
      $.jqplot(reportOptions.id, reportOptions.seriesData, reportOptions.opts);
    });
  }

  _get_week_number = function(o) { // occurrence object
    var d = new Date (o.date),
        yn = d.getFullYear(),
        mn = d.getMonth(),
        dn = d.getDate(),
        yz = reportOptions.zeroWeekStartDate.getFullYear(),
        mz = reportOptions.zeroWeekStartDate.getMonth(),
        dz = reportOptions.zeroWeekStartDate.getDate(),
        zeroDay = new Date(yz,mz,dz,12,0,0), // noon on Week zero start date
        d2 = new Date(yn,mn,dn,12,0,0), // noon on input date
        ddiff = Math.round((d2-zeroDay)/8.64e7); // gets around Daylight Saving.
    return Math.floor(ddiff/7);
  }

}) (jQuery);