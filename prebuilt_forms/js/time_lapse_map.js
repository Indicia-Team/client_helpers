var iTLMOpts = {};
var iTLMData = {
  mySiteWKT: [],
  mySites: [],
  myData: [],
  mySpecies: [],
  mySpeciesIDs: [],
  last_displayed: -1,
  global_timer_function: false,
  maxDayIndex: 365, // Dec 31 on a leap year
  minDayIndex: 0, // first January
  year: '',
  species: '',
  event: '',
  advancedButtons: true
};

// for loops: Use of these is meant to prevent bugs.
// i = records
// j = days in year
// k = events
// m = species
// n = map controls
// p = features

// functions
var rgbvalue, applyJitter, setToDate, loadYear;

(function ($) {
  var stopAnimation = function () {
    if (iTLMData.global_timer_function)
      clearInterval(iTLMData.global_timer_function);
    iTLMData.global_timer_function = false;
  };

  var enableSpeciesControlOptions = function () {
    var year = iTLMOpts.yearSelector ? $(iTLMOpts.yearControlSelector).val() : 'all'; // this will never be blank.
    var oldSpecies = $(iTLMOpts.speciesControlSelector).val();
    var anySpecies = false;
    for (var m = 0; m < iTLMData.mySpeciesIDs.length; m++) {
      var validSpecies = false;
      for (var k = 0; k < iTLMOpts.triggerEvents.length; k++) {
        for (var j = 0; j < 365; j++) {
          if (typeof iTLMData.myData[year][k][j][iTLMData.mySpeciesIDs[m]] !== 'undefined') {
            validSpecies = true;
          }
        }
      }
      anySpecies |= validSpecies;
      // If this means deselecting current choice: set species control to blank.
      if (iTLMData.mySpeciesIDs[m] === oldSpecies && !validSpecies)
        $(iTLMOpts.speciesControlSelector).val('');
      $(iTLMOpts.speciesControlSelector).find('option[value=' + iTLMData.mySpeciesIDs[m] + ']').each(function (idx, elem) {
        if (validSpecies)
          $(elem).removeAttr('disabled').show();
        else
          $(elem).attr('disabled', 'disabled').hide();
      });
    }
    $('.dateErrorWarning').remove();
    if (!anySpecies)
      $(iTLMOpts.yearControlSelector).after('<img class="speciesErrorWarning" src="' + iTLMOpts.imgPath + 'warning.png" title="There is no species data for this year">');
  };

  var calculateMinAndMaxForYear = function () {
    var year = iTLMOpts.yearSelector;
    var species = $(iTLMOpts.speciesControlSelector).val();
    iTLMData.year = year;
    iTLMData.species = species;

    iTLMData.minDayIndex = 365;
    iTLMData.maxDayIndex = 0;
    if (species === '') {
      iTLMData.minDayIndex = 0;
      iTLMData.maxDayIndex = (Date.UTC(year, 12, 31) - Date.UTC(year, 1, 1)) / (24 * 60 * 60 * 1000);
    } else {
      for (var j = 0; j < 365; j++)
        if (typeof iTLMData.myData[year][event][j][species] !== 'undefined') {
          if (j < iTLMData.minDayIndex)
            iTLMData.minDayIndex = j;
          if (j > iTLMData.maxDayIndex)
            iTLMData.maxDayIndex = j;
        }
    }
    if (iTLMData.minDayIndex > 0)
      iTLMData.minDayIndex--; // allow for day before data actually starts
    if (iTLMOpts.advanced_UI) {
      var slider = $(iTLMOpts.timeControlSelector);
      $(iTLMOpts.timeControlSelector).slider('option', 'min', iTLMData.minDayIndex);
      $(iTLMOpts.timeControlSelector).slider('option', 'max', iTLMData.maxDayIndex);
      var diff = iTLMData.maxDayIndex - iTLMData.minDayIndex;
      var spacing = 100 / diff;
      slider.find('.ui-slider-tick-mark').remove();
      slider.find('.ui-slider-label').remove();
      var maxLabels = 11; // TODO ".(isset($args['numberOfDates']) && $args['numberOfDates'] > 1 ? $args['numberOfDates'] : 11).";
      var maxTicks = 100;
      var daySpacing = diff === 0 ? 1 : Math.ceil(diff / maxTicks);
      var provisionalLabelSpacing = Math.max(7, Math.ceil(diff / maxLabels));
      var actualLabelSpacing = daySpacing * Math.ceil(provisionalLabelSpacing / daySpacing);
      for (var j = iTLMData.minDayIndex; j <= iTLMData.maxDayIndex; j += daySpacing) {
        var myDate = new Date();
        myDate.setFullYear(year, 0, 1);
        if (j > 0)
          myDate.setDate(myDate.getDate() + j);
        if (j > iTLMData.minDayIndex && j < iTLMData.maxDayIndex)
          $('<span class=\"ui-slider-tick-mark' + (!((j - iTLMData.minDayIndex) % actualLabelSpacing) ? ' long' : '') + '\"></span>').css('left', Math.round(spacing * (j - iTLMData.minDayIndex) * 10) / 10 + '%').appendTo(slider);
        if (!((j - iTLMData.minDayIndex) % actualLabelSpacing) && spacing * (j - iTLMData.minDayIndex) < 95)
          $('<span class=\"ui-slider-label\"><span>' + myDate.getDate() + ' ' + iTLMOpts.monthNames[myDate.getMonth()] + '</span></span>').css('left', Math.round(spacing * (j - iTLMData.minDayIndex) * 10) / 10 + '%').appendTo(slider);
      }
    } else {
      var select = $(iTLMOpts.timeControlSelector);
      select.find('option').remove();
      for (var j = iTLMData.minDayIndex; j <= iTLMData.maxDayIndex; j++) {
        var myDate = new Date();
        myDate.setFullYear(year, 0, 1);
        if (j)
          myDate.setDate(myDate.getDate() + j);
        $('<option value="' + j + '">' + myDate.getDate() + ' ' + iTLMOpts.monthNames[myDate.getMonth()] + '</option>').appendTo(select);
      }
    }
    // if playing, leave playing, otherwise jump to end.
    if (!iTLMData.global_timer_function) {
      setToDate(-1);
      setToDate(iTLMData.maxDayIndex);
    } else
      setLastDisplayed(getLastDisplayed());
  };
  
  var calculateMinAndMaxForWholePeriod = function () {
    var species = $(iTLMOpts.speciesControlSelector).val();
    iTLMData.year = 'all';
    iTLMData.species = species;
    // Indexes will be days since Unix Epoch.
    iTLMData.minDayIndex = (new Date()).getTime() / (24 * 60 * 60 * 1000);
    iTLMData.maxDayIndex = 0;
    if (species === '') {
      iTLMData.minDayIndex = 0;
      iTLMData.maxDayIndex = (new Date()).getTime() / (24 * 60 * 60 * 1000);
    } else {
      
      // This bit can't work by cycling all days
      
      
      for (var j = 0; j < 365; j++)
        if (typeof iTLMData.myData['all'][j][species] !== 'undefined') {
          if (j < iTLMData.minDayIndex)
            iTLMData.minDayIndex = j;
          if (j > iTLMData.maxDayIndex)
            iTLMData.maxDayIndex = j;
        }
    }
    if (iTLMData.minDayIndex > 0)
      iTLMData.minDayIndex--; // allow for day before data actually starts
    if (iTLMOpts.advanced_UI) {
      var slider = $(iTLMOpts.timeControlSelector);
      $(iTLMOpts.timeControlSelector).slider('option', 'min', iTLMData.minDayIndex);
      $(iTLMOpts.timeControlSelector).slider('option', 'max', iTLMData.maxDayIndex);
      var diff = iTLMData.maxDayIndex - iTLMData.minDayIndex;
      var spacing = 100 / diff;
      slider.find('.ui-slider-tick-mark').remove();
      slider.find('.ui-slider-label').remove();
      var maxLabels = 11; // TODO ".(isset($args['numberOfDates']) && $args['numberOfDates'] > 1 ? $args['numberOfDates'] : 11).";
      var maxTicks = 100;
      var daySpacing = diff === 0 ? 1 : Math.ceil(diff / maxTicks);
      var provisionalLabelSpacing = Math.max(7, Math.ceil(diff / maxLabels));
      var actualLabelSpacing = daySpacing * Math.ceil(provisionalLabelSpacing / daySpacing);
      for (var j = iTLMData.minDayIndex; j <= iTLMData.maxDayIndex; j += daySpacing) {
        var myDate = new Date();
        myDate.setFullYear(year, 0, 1);
        if (j > 0)
          myDate.setDate(myDate.getDate() + j);
        if (j > iTLMData.minDayIndex && j < iTLMData.maxDayIndex)
          $('<span class=\"ui-slider-tick-mark' + (!((j - iTLMData.minDayIndex) % actualLabelSpacing) ? ' long' : '') + '\"></span>').css('left', Math.round(spacing * (j - iTLMData.minDayIndex) * 10) / 10 + '%').appendTo(slider);
        if (!((j - iTLMData.minDayIndex) % actualLabelSpacing) && spacing * (j - iTLMData.minDayIndex) < 95)
          $('<span class=\"ui-slider-label\"><span>' + myDate.getDate() + ' ' + iTLMOpts.monthNames[myDate.getMonth()] + '</span></span>').css('left', Math.round(spacing * (j - iTLMData.minDayIndex) * 10) / 10 + '%').appendTo(slider);
      }
    } else {
      var select = $(iTLMOpts.timeControlSelector);
      select.find('option').remove();
      for (var j = iTLMData.minDayIndex; j <= iTLMData.maxDayIndex; j++) {
        var myDate = new Date();
        myDate.setFullYear(year, 0, 1);
        if (j)
          myDate.setDate(myDate.getDate() + j);
        $('<option value="' + j + '">' + myDate.getDate() + ' ' + iTLMOpts.monthNames[myDate.getMonth()] + '</option>').appendTo(select);
      }
    }
    // if playing, leave playing, otherwise jump to end.
    if (!iTLMData.global_timer_function) {
      setToDate(-1);
      setToDate(iTLMData.maxDayIndex);
    } else
      setLastDisplayed(getLastDisplayed());
  };
  
  var calculateMinAndMax = function() {
    if (iTlMOpts.yearSelector) {
      calculateMinAndMaxForYear();
    } else {
      calculateMinAndMaxForWholePeriod();
    }
  }

  var getLastDisplayed = function () {
    return iTLMData.last_displayed;
  };

  var setLastDisplayed = function (idx) {
    iTLMData.last_displayed = idx;
    if (iTLMOpts.advanced_UI)
      $(iTLMOpts.timeControlSelector).slider('option', 'value', idx);
    else
      $(iTLMOpts.timeControlSelector).val(idx);
  };

  var resetMap = function () {
    var last = getLastDisplayed();
    setToDate(-1);
    setToDate(last);
  };

  // init must be called before the maps are initialised, as it sets up a 
  indiciaFns.initTimeLapseMap = function (options) {
    var defaults = {
      advanced_UI: false,
      firstDateRGB: {r: 0, g: 0, b: 255}, // colour of first date displayed.
      lastDateRGB: {r: 255, g: 0, b: 0}, // colour of last date displayed.
      jitterRadius: 15000,
      timerDelay: 250, // milliseconds
      yearControlSelector: '#yearControl',
      speciesControlSelector: '#speciesControl',
      eventControlSelector: '#eventControl',
      mapSelector: '#map',
      mapContainerClass: 'mapContainers',
      indicia_user_id: false,
      firstButtonSelector: '#beginning',
      lastButtonSelector: '#end',
      playButtonSelector: '#playMap',
      playButtonPlayLabel: 'play',
      playButtonPlayIcon: 'ui-icon-play',
      playButtonPauseLabel: 'pause',
      playButtonPauseIcon: 'ui-icon-pause',
      timeControlSelector: '#timeSlider',
      dotControlSelector: '#dotControl',
      dotControlMin: 2,
      dotControlMax: 5,
      dotSize: 3,
      errorDiv: '#errorMsg',
      pleaseSelectPrompt: 'Please select a Year / Species / Event combination before playing',
      waitDialogText: 'Please wait whilst the data for {year} is loaded.',
      waitDialogTitle: 'Loading Data...',
      // waitDialogOK: 'OK',
      noMappableDataError: 'The report does not output any mappable data.',
      noDateError: 'The report does not output a date.',
      sitesLayerLabel: 'My Sites', // don't need events layer label as not in switcher.
      monthNames: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
      imgPath: '/'
    };
    iTLMOpts = $.extend({}, defaults, options);
    iTLMData.dotSize = iTLMOpts.dotSize;

    // Field change events:

    $(iTLMOpts.yearControlSelector).change(function (evt) {
      var year = $(evt.target).val();
      stopAnimation();
      loadYear(year, 'lh');
    });

    $(iTLMOpts.speciesControlSelector).change(function (evt) {
      stopAnimation();
      enableEventControlOptions();
      calculateMinAndMax();
      resetMap();
    });

    $(iTLMOpts.eventControlSelector).change(function (evt) {
      stopAnimation();
      calculateMinAndMax();
      resetMap();
    });

    $(iTLMOpts.playButtonSelector).click(function () {
      if (iTLMData.year === '' || iTLMData.species === '' || iTLMData.event === '') {
        alert(iTLMOpts.pleaseSelectPrompt);
        return;
      }

      var caller = function () {
        var value = getLastDisplayed();
        if (value < iTLMData.maxDayIndex) {
          setToDate(value + 1);
        } else {
          stopAnimation();
          if (iTLMOpts.advanced_UI && iTLMData.advancedButtons)
            $(iTLMOpts.playButtonSelector).button('option', {label: iTLMOpts.playButtonPlayLabel, icons: {primary: iTLMOpts.playButtonPlayIcon}});
          else
            $(iTLMOpts.playButtonSelector).text(iTLMOpts.playButtonPlayLabel);
        }
      };

      var options;
      if (!iTLMData.global_timer_function) {
        var value = getLastDisplayed();
        if (value >= iTLMData.maxDayIndex)
          setToDate(iTLMData.minDayIndex);
        options = {label: iTLMOpts.playButtonPauseLabel, icons: {primary: iTLMOpts.playButtonPauseIcon}};
        iTLMData.global_timer_function = setInterval(caller, iTLMOpts.timerDelay);
      } else {
        stopAnimation();
        options = {label: iTLMOpts.playButtonPlayLabel, icons: {primary: iTLMOpts.playButtonPlayIcon}};
      }
      if (iTLMOpts.advanced_UI && iTLMData.advancedButtons)
        $(this).button('option', options);
      else
        $(this).text(options.label);
    });

    $(iTLMOpts.firstButtonSelector).click(function () {
      stopAnimation();
      if (iTLMOpts.advanced_UI && iTLMData.advancedButtons)
        $(iTLMOpts.playButtonSelector).button('option', {label: iTLMOpts.playButtonPlayLabel, icons: {primary: iTLMOpts.playButtonPlayIcon}});
      else
        $(iTLMOpts.playButtonSelector).text(iTLMOpts.playButtonPlayLabel);
      setToDate(iTLMData.minDayIndex);
    });

    $(iTLMOpts.lastButtonSelector).click(function () {
      stopAnimation();
      if (iTLMOpts.advanced_UI && iTLMData.advancedButtons)
        $(iTLMOpts.playButtonSelector).button('option', {label: iTLMOpts.playButtonPlayLabel, icons: {primary: iTLMOpts.playButtonPlayIcon}});
      else
        $(iTLMOpts.playButtonSelector).text(iTLMOpts.playButtonPlayLabel);
      setToDate(iTLMData.maxDayIndex);
    });

    // Time control
    if (iTLMOpts.advanced_UI) {
      $(iTLMOpts.timeControlSelector).slider();
      $(iTLMOpts.timeControlSelector).slider({change: function (event, ui) {
          setToDate($(iTLMOpts.timeControlSelector).slider('value'));
        }});
      if (iTLMData.advancedButtons) {
        $(iTLMOpts.firstButtonSelector).button({text: false, icons: {primary: 'ui-icon-seek-start'}});
        $(iTLMOpts.playButtonSelector).button({text: false, icons: {primary: 'ui-icon-play'}});
        $(iTLMOpts.lastButtonSelector).button({text: false, icons: {primary: 'ui-icon-seek-end'}});
      }
    } else {
      $(iTLMOpts.timeControlSelector).change(function (event, ui) {
        setToDate($(iTLMOpts.timeControlSelector).val());
      });
    }

    // Dot size control
    if (iTLMOpts.advanced_UI) {
      $(iTLMOpts.dotControlSelector).slider();
      $(iTLMOpts.dotControlSelector).slider('option', 'min', iTLMOpts.dotControlMin);
      $(iTLMOpts.dotControlSelector).slider('option', 'max', iTLMOpts.dotControlMax);
      $(iTLMOpts.dotControlSelector).slider('option', 'value', iTLMData.dotSize);
      $(iTLMOpts.dotControlSelector).slider({change: function (event, ui) {
          iTLMData.dotSize = $(iTLMOpts.dotControlSelector).slider('value');
          if ($(iTLMOpts.mapSelector)[0].map.sitesLayer.features.length > 0) {
            var features = $(iTLMOpts.mapSelector)[0].map.sitesLayer.features;
            $(iTLMOpts.mapSelector)[0].map.sitesLayer.removeFeatures(features);
            for (p = 0; p < features.length; p++)
              features[p].style.pointRadius = iTLMData.dotSize + 2;
            $(iTLMOpts.mapSelector)[0].map.sitesLayer.addFeatures(features);
          }
          resetMap();
        }});
    } else {
      $(iTLMOpts.dotControlSelector).val(iTLMData.dotSize);
      $(iTLMOpts.dotControlSelector).change(function (event, ui) {
        iTLMData.dotSize = $(iTLMOpts.dotControlSelector).val();
        if ($(iTLMOpts.mapSelector)[0].map.sitesLayer.features.length > 0) {
          var features = $(iTLMOpts.mapSelector)[0].map.sitesLayer.features;
          $(iTLMOpts.mapSelector)[0].map.sitesLayer.removeFeatures(features);
          for (p = 0; p < features.length; p++)
            features[p].style.pointRadius = iTLMData.dotSize + 2;
          $(iTLMOpts.mapSelector)[0].map.sitesLayer.addFeatures(features);
        }
        resetMap();
      });
    }

    mapInitialisationHooks.push(function (mapdiv) {
      var year = iTLMOpts.yearSelector ? $(iTLMOpts.yearControlSelector).val() : 'all';;
      // each map gets its own site and events layers.
      mapdiv.map.eventsLayer = new OpenLayers.Layer.Vector('Events Layer', {displayInLayerSwitcher: false});
      mapdiv.map.sitesLayer = new OpenLayers.Layer.Vector(iTLMOpts.sitesLayerLabel, {displayInLayerSwitcher: true});
      mapdiv.map.addLayer(mapdiv.map.sitesLayer);
      mapdiv.map.addLayer(mapdiv.map.eventsLayer);
      // switch off the mouse drag pan.
      for (var n = 0; n < mapdiv.map.controls.length; n++) {
        if (mapdiv.map.controls[n].CLASS_NAME === 'OpenLayers.Control.Navigation')
          mapdiv.map.controls[n].deactivate();
      }
      if ('#' + mapdiv.id === iTLMOpts.mapSelector) {
        loadYear(year, 'lh');
      }
    });
  };

  loadYear = function (year, side) {
    var dateFilter = (year === 'all' ? '&date_from=' + iTLMOpts.firstYear + '-01-01' : '&date_from=' + year + '-01-01&date_to=' + year + '-12-31');
    if (typeof iTLMData.myData[year] !== 'undefined') {
      if (side === 'lh') {
        enableSpeciesControlOptions();
        enableEventControlOptions();
      }
      calculateMinAndMax();
      resetMap();
      return; // already loaded.
    }
    iTLMData.myData[year] = {};
    /*
     for (k = 0; k < iTLMOpts.triggerEvents.length; k++) {
      iTLMData.myData[year][k] = []; // first event, array of days
      for (j = 0; j <= 365; j++)
        iTLMData.myData[year][k][j] = []; // arrays of species geometry pairs
    }
    */
    $(iTLMOpts.errorDiv).empty();
    dialog = $('<p>' + iTLMOpts.waitDialogText.replace('{year}', year) + '</p>').dialog({
      title: iTLMOpts.waitDialogTitle, 
      buttons: {OK: function () {
        dialog.dialog('close');
      }}
    });
    // Report record should have geom, sample_date, species ttl_id, attributes.
    jQuery.getJSON(iTLMOpts.base_url + '/index.php/services/report/requestReport?report=' + iTLMOpts.report_name + '.xml&reportSource=local&mode=json' +
        '&auth_token=' + iTLMOpts.auth_token + '&reset_timeout=true&nonce=' + iTLMOpts.nonce + iTLMOpts.reportExtraParams +
        '&callback=?' + dateFilter,
      function (data) {
        var canIDuser = false;
        var hasDate = false;
        var wktCol = false;
        var parser = new OpenLayers.Format.WKT();
        var year = iTLMOpts.yearSelector ? $(iTLMOpts.yearControlSelector).val() : 'all';
        var firstDate = iTLMOpts.yearSelector ? Date.UTC(parts[0], 1, 1) : Date.UTC(iTLMOpts.firstYear, 1, 1);
        var donePoints = [];
        var wkt;

        if (typeof data.records !== 'undefined') {
          if (data.records.length > 0) {
            // first isolate geometry column
            $.each(data.columns, function (column, properties) {
              if (column === 'created_by_id')
                canIDuser = true;
              if (column === 'date')
                hasDate = true;
              if (typeof properties.mappable !== 'undefined' && properties.mappable === 'true' && !wktCol)
                wktCol = column;
            });
            if (!wktCol)
              return $(iTLMOpts.errorDiv).append('<p>' + iTLMOpts.noMappableDataError + '</p>');
            if (!hasDate)
              return $(iTLMOpts.errorDiv).append('<p>' + iTLMOpts.noDateError + '</p>');
            // Date preprocess and sort
            for (var i = 0; i < data.records.length; i++) {
              var parts = data.records[i].date.split('-');
              data.records[i].recordDayIndex = Date.UTC(parts[0], parts[1], parts[2]) / (24 * 60 * 60 * 1000);
            }
            data.records.sort(function (a, b) {
              return a.recordDayIndex - b.recordDayIndex;
            });
            $.each(data.records, function() {
              // remove point stuff: don't need to convert to numbers, as that was only to save space in php.
              wkt = data.records[i][wktCol].replace(/POINT\(/, '').replace(/\)/, '');
              
              if (typeof iTLMData.mySpecies[this.species_id] === 'undefined') {
                iTLMData.myData[year]['species:' + species_id] = {};
                iTLMData.mySpeciesIDs.push(this.species_id);
                iTLMData.mySpecies[this.species_id] = {
                  id: this.species_id,
                  taxon: this.taxon
                };
                $(iTLMOpts.speciesControlSelector).append('<option value="' + this.species_id + '" >' + this.taxon + '</option>');
              }
              // Need to check that geom + species ID combination not already done at this point
              found = $.inArray(this.species_id + ':' + wkt, donePoints);
              if (found) {
                return true; // continue to next iteration of records
              } else {
                donePoints.push(this.species_id + ':' + wkt);
              }
              if (typeof iTLMData.myData[year]['species:' + species_id]['day:' + this.recordDayIndex] === "undefined") {
                iTLMData.myData[year]['species:' + species_id]['day:' + this.recordDayIndex] = [];
              }
              iTLMData.myData[year]['species:' + species_id]['day:' + this.recordDayIndex].push({
                wkt: wkt,
                record: this,
                feature: /*** TODO ***/
              });
              
              
              
              
            });






/*
 * Moving the following to the above

            for (var i = 0; i < data.records.length; i++) {
              var wkt;
              // remove point stuff: don't need to convert to numbers, as that was only to save space in php.
              wkt = data.records[i][wktCol].replace(/POINT\(/, '').replace(/\)/, '');

              if (typeof iTLMData.mySpecies[data.records[i].species_id] === 'undefined') {
                iTLMData.mySpeciesIDs.push(data.records[i].species_id);
                iTLMData.mySpecies[data.records[i].species_id] = {id: data.records[i].species_id, taxon: data.records[i].taxon};
                $(iTLMOpts.speciesControlSelector).append('<option value="' + data.records[i].species_id + '" ' + (side === 'rh' ? 'disabled="disabled" style="display:none;"' : '') + '>' + data.records[i].taxon + '</option>');
              }
              for (k = 0; k < iTLMOpts.triggerEvents.length; k++) {
                // event definition
                // check first that this hasn't happened already! we are using assumption that the data is sorted by date, so earlier records will be processed first.
                var found = false;
                for (j = 0; j < data.records[i].recordDayIndex; j++) {
                  if (typeof iTLMData.myData[year][k][j][data.records[i].species_id] !== 'undefined'
                      && iTLMData.myData[year][k][j][data.records[i].species_id].locations.indexOf(data.records[i].geom) >= 0) {
                    found = true;
                    break;
                  }
                }
                if (found) {
                  continue;
                }
                // user locations independant of event
                if (canIDuser && iTLMOpts.indicia_user_id && data.records[i].created_by_id === iTLMOpts.indicia_user_id &&
                        typeof iTLMData.mySites[data.records[i].geom] === 'undefined') {
                  iTLMData.mySites[data.records[i].geom] = true;
                  iTLMData.mySiteWKT.push(wkt);
                }
                // TODO: Dev: allow between values as rules.
                if (iTLMOpts.triggerEvents[k].type === 'presence' ||
                    (iTLMOpts.triggerEvents[k].type === 'arrayVal' &&
                      typeof data.records[i]['attr_occurrence_' + iTLMOpts.triggerEvents[k].attr] !== 'undefined' &&
                      iTLMOpts.triggerEvents[k].values.indexOf(data.records[i]['attr_occurrence_' + iTLMOpts.triggerEvents[k].attr]) >= 0)) {
                  if (typeof iTLMData.myData[year][k][data.records[i].recordDayIndex][data.records[i].species_id] === 'undefined')
                    iTLMData.myData[year][k][data.records[i].recordDayIndex][data.records[i].species_id] = {
                      mine: {'attributes': {}, 'feature': false, 'wkt': []},
                      others: {'attributes': {}, 'feature': false, 'wkt': []}, 
                      locations: []
                    };
                  if (canIDuser && iTLMOpts.indicia_user_id && data.records[i].created_by_id === iTLMOpts.indicia_user_id) {
                    iTLMData.myData[year][k][data.records[i].recordDayIndex][data.records[i].species_id].mine.wkt.push(wkt);
                  } else {
                    iTLMData.myData[year][k][data.records[i].recordDayIndex][data.records[i].species_id].others.wkt.push(wkt);
                  }
                  iTLMData.myData[year][k][data.records[i].recordDayIndex][data.records[i].species_id].locations.push(data.records[i].geom);
                }
              }
            }
            
            */
            
            
            // loop through all records in year, and convert array of WKT to features.
            for (k = 0; k < iTLMOpts.triggerEvents.length; k++) {
              for (j = 0; j <= 365; j++)
                for (m = 0; m < iTLMData.mySpeciesIDs.length; m++) {
                  if (typeof iTLMData.myData[year][k][j][iTLMData.mySpeciesIDs[m]] !== 'undefined' &&
                          iTLMData.myData[year][k][j][iTLMData.mySpeciesIDs[m]].mine.wkt.length > 0) {
                    iTLMData.myData[year][k][j][iTLMData.mySpeciesIDs[m]].mine.feature =
                            parser.read((iTLMData.myData[year][k][j][iTLMData.mySpeciesIDs[m]].mine.wkt.length === 1 ? 'POINT(' : 'MULTIPOINT(') +
                                    iTLMData.myData[year][k][j][iTLMData.mySpeciesIDs[m]].mine.wkt.join(',') + ')');
                    iTLMData.myData[year][k][j][iTLMData.mySpeciesIDs[m]].mine.feature.style =
                            {strokeWidth: 3, strokeColor: 'Yellow', graphicName: 'square', fillOpacity: 1};
                    iTLMData.myData[year][k][j][iTLMData.mySpeciesIDs[m]].mine.feature.attributes.dayIndex = j;
                  }
                  if (typeof iTLMData.myData[year][k][j][iTLMData.mySpeciesIDs[m]] !== 'undefined' &&
                          iTLMData.myData[year][k][j][iTLMData.mySpeciesIDs[m]].others.wkt.length > 0) {
                    iTLMData.myData[year][k][j][iTLMData.mySpeciesIDs[m]].others.feature =
                            parser.read((iTLMData.myData[year][k][j][iTLMData.mySpeciesIDs[m]].others.wkt.length === 1 ? 'POINT(' : 'MULTIPOINT(') +
                                    iTLMData.myData[year][k][j][iTLMData.mySpeciesIDs[m]].others.wkt.join(',') + ')');
                    iTLMData.myData[year][k][j][iTLMData.mySpeciesIDs[m]].others.feature.style = {fillOpacity: 0.8, strokeWidth: 0};
                    iTLMData.myData[year][k][j][iTLMData.mySpeciesIDs[m]].others.feature.attributes.dayIndex = j;
                  }
                }
            }

            $(iTLMOpts.mapSelector)[0].map.sitesLayer.destroyFeatures();
            if (iTLMData.mySiteWKT.length > 0) {
              var feature = parser.read((iTLMData.mySiteWKT.length === 1 ? 'POINT(' : 'MULTIPOINT(') + iTLMData.mySiteWKT.join(',') + ')');
              feature.style = {fillColor: 0, fillOpacity: 0, strokeWidth: 2, strokeColor: 'Yellow', graphicName: 'square', pointRadius: iTLMData.dotSize + 2};
              $(iTLMOpts.mapSelector)[0].map.sitesLayer.addFeatures([feature]);
            }
          }
        } else if (typeof data.error !== 'undefined') {
          $(iTLMOpts.errorDiv).html('<p>Error Returned from warehouse report:<br>' + data.error + '<br/>' +
                  (typeof data.code !== 'undefined' ? 'Code: ' + data.code + '<br/>' : '') +
                  (typeof data.file !== 'undefined' ? 'File: ' + data.file + '<br/>' : '') +
                  (typeof data.line !== 'undefined' ? 'Line: ' + data.line + '<br/>' : '') +
                  // not doing trace
                  '</p>');
        } else {
          $(iTLMOpts.errorDiv).html('<p>Internal Error: Format from report request not recognised.</p>');
        }
        if (side === 'lh') {
          enableSpeciesControlOptions();
        }
        calculateMinAndMax();
        resetMap();
        dialog.dialog('close');
      });
  };

  rgbvalue = function (dateidx) {
    var r = parseInt(iTLMOpts.lastDateRGB.r * (dateidx - iTLMData.minDayIndex) / (iTLMData.maxDayIndex - iTLMData.minDayIndex) + iTLMOpts.firstDateRGB.r * (iTLMData.maxDayIndex - dateidx) / (iTLMData.maxDayIndex - iTLMData.minDayIndex));
    r = (r < 16 ? '0' : '') + r.toString(16);
    var g = parseInt(iTLMOpts.lastDateRGB.g * (dateidx - iTLMData.minDayIndex) / (iTLMData.maxDayIndex - iTLMData.minDayIndex) + iTLMOpts.firstDateRGB.g * (iTLMData.maxDayIndex - dateidx) / (iTLMData.maxDayIndex - iTLMData.minDayIndex));
    g = (g < 16 ? '0' : '') + g.toString(16);
    var b = parseInt(iTLMOpts.lastDateRGB.b * (dateidx - iTLMData.minDayIndex) / (iTLMData.maxDayIndex - iTLMData.minDayIndex) + iTLMOpts.firstDateRGB.b * (iTLMData.maxDayIndex - dateidx) / (iTLMData.maxDayIndex - iTLMData.minDayIndex));
    b = (b < 16 ? '0' : '') + b.toString(16);
    return '#' + r + g + b;
  };

  setToDate = function (idx) {
    var displayDay = function (idx) {
      var applyJitter = function (layer, feature) {
        var X = iTLMOpts.jitterRadius + 1;
        for (var p = 0; p < layer.features.length; p++)
          X = Math.min(X, feature.geometry.distanceTo(layer.features[p].geometry));
        if (!feature.attributes.jittered && X < iTLMOpts.jitterRadius) {
          feature.attributes.jittered = true;
          var angle = Math.random() * Math.PI * 2;
          feature.geometry.move(iTLMOpts.jitterRadius * Math.cos(angle), iTLMOpts.jitterRadius * Math.sin(angle));
        }
      };

      var applyDay = function (day, layer) {
        if (typeof day !== 'undefined' && day !== false) {
          if (day.others.feature) {
            applyJitter(layer, day.others.feature);
            day.others.feature.style.pointRadius = iTLMData.dotSize;
            day.others.feature.style.fillColor = rgbvalue(idx);
            layer.addFeatures([day.others.feature]);
          }
          if (day.mine.feature) {
            // Dont apply jitter to own data as this may 
            day.mine.feature.style.pointRadius = iTLMData.dotSize + 2;
            day.mine.feature.style.fillColor = rgbvalue(idx);
            layer.addFeatures([day.mine.feature]);
          }
        }
      };

      if (iTLMData.year !== '' && iTLMData.species !== '' && iTLMData.event !== '') {
        applyDay(iTLMData.myData[iTLMData.year][iTLMData.event][idx][iTLMData.species], $(iTLMOpts.mapSelector)[0].map.eventsLayer);
      }
    };

    var rmFeatures = function (layer, dayIdx) {
      var toRemove = [];
      for (var p = 0; p < layer.features.length; p++)
        if (layer.features[p].attributes.dayIndex > dayIdx)
          toRemove.push(layer.features[p]);
      if (toRemove.length > 0)
        layer.removeFeatures(toRemove);
    };

    var displayYear = (iTLMData.year2 === '' || iTLMData.year === iTLMData.year2);
    var myDate = new Date();
    myDate.setFullYear(iTLMData.year, 0, 1);
    myDate.setDate(myDate.getDate() + idx);
    $('#displayDate').html(myDate.getDate() + '/' + (myDate.getMonth() + 1) + (displayYear ? '/' + myDate.getFullYear() : ''));

    if (iTLMData.year === '' || iTLMData.species === '' || iTLMData.event === '')
      rmFeatures($(iTLMOpts.mapSelector)[0].map.eventsLayer, -1);
    if (idx !== getLastDisplayed()) {
      if (getLastDisplayed() > idx) {
        rmFeatures($(iTLMOpts.mapSelector)[0].map.eventsLayer, idx);
      } else {
        for (var j = getLastDisplayed() + 1; j <= idx; j++)
          displayDay(j);
      }
      setLastDisplayed(idx);
    }
  };
}(jQuery));