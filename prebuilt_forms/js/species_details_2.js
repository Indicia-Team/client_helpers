jQuery(document).ready(function($) {

  // Reset GET form URL parameters before submission (species selector)
  window.indiciaFns.speciesDetailsSub = () => {
    $('#taxon-search-form #taxa_taxon_list_id').val($('#occurrence\\:taxa_taxon_list_id').val())
    document.forms["taxon-search-form"].submit()
  }

  var hectadData;
  var brcmap, brcyearly, brcphenology;

  // Translate any attribute names
  fieldTransations = {};
  if ($('#species-details-fields-translate').val()) {
    $('#species-details-fields-translate').val().split(';').forEach(function(t) {
      var ft = t.split('|');
      if (ft.length === 2) {
        fieldTransations[ft[0].trim()] = ft[1].trim();
      }
    })
  }
  // Show non-duplicate attr names in species details table
  // and do any translation
  var prevAttr;
  $('.species-details-fields dt').each(function(i) {
    if (!prevAttr || prevAttr !== $(this).text()) {
      prevAttr = $(this).text();
      if (fieldTransations[$(this).text().trim()]) {
        $(this).text(fieldTransations[$(this).text().trim()]);
      }
      $(this).show();
    };
  });

  // Create hectad map
  var legendInteractivity = $('#brc-hectad-map-legendmouse').val();
  var highlightStyle = $('#brc-hectad-map-highstyle').val();
  var lowlightStyle = $('#brc-hectad-map-lowstyle').val();

  if (typeof(brcatlas) !== "undefined") {
    brcmap = brcatlas.svgMap({
      selector: ".brc-hectad-map",
      height: 700,
      expand: true,
      transOptsControl: false,
      transOptsKey: 'BI4',
      mapTypesSel: {
        'hectad-colour': getHectadsColour,
        'year-banded': getBandedColour
      },
      mapTypesKey: 'year-banded',
      captionId: 'brc-hectad-map-dot-details',
      legendOpts: {
        display: true,
        scale: 1,
        x: 10,
        y: 5,
        data: null
      },
      legendInteractivity: legendInteractivity,
      highlightClass: 'brc-atlas-highlight-hectad-map',
      highlightStyle: highlightStyle,
      lowlightStyle: lowlightStyle,
    });
    brcmap.showBusy(true);
  };

  // Attach hectad map priority event handlers
  $('#brc-hectad-map-priority').change(e => {
    brcmap.redrawMap();
  })

  // Attach hectad map threshold event handlers
  $('#brc-hectad-map-thresh1').change(e => {
    thresholdChanged(1);
  })
  $('#brc-hectad-map-thresh2').change(e => {
    thresholdChanged(2);
  })
  $('#brc-hectad-map-thresh1').on('keyup', e => {
    if ($('#brc-hectad-map-thresh1').val().length > 3) {
      thresholdChanged(1);
    }
  })
  $('#brc-hectad-map-thresh2').on('keyup', e => {
    if ($('#brc-hectad-map-thresh2').val().length > 3) {
      thresholdChanged(2);
    }
  })

  // Attach download button event handlers
  $('.brc-hectad-map-image-download').on('click', e => {

    var defaultName = replaceTags($('#species-details-preferred-name').val())

    var txt = 'Hectad distribution map for ' + defaultName
    if ($('#species-details-default-common-name').val()) {
      txt += ' (' + $('#species-details-default-common-name').val() + ')'
    }
    txt += '. Generated from ' + location.href + ' on ' + getDateString() + '. '

    if ($('#unverified-checkbox-form #xu').is(":checked")) {
      txt += 'Rejected and unverified records are excluded from this map.'
    } else {
      txt += 'Rejected records are excluded from this map. Unverified records are included.'
    }

    brcmap.saveMap(false, {
      text: txt,
      //img: '/sites/default/files/irecord_logo.png',
      margin: 5
    }, 'irecord-species-details-map');
  })


  function thresholdChanged(n) {
    if (n === 1 && getThresh(1) >= getThresh(2)) {
      setThresh(2, getThresh(1)+1);
    }
    if (n === 2 && getThresh(2) <= getThresh(1)) {
      setThresh(1, getThresh(2)-1);
    }
    var currentYear = new Date().getFullYear();
    if (getThresh(2) >= currentYear) {
      setThresh(2, currentYear-1);
    }
    if (getThresh(1) >= getThresh(2)) {
      setThresh(1, getThresh(2)-1);
    }
    brcmap.redrawMap();
  }
  function getThresh(n) {
    return Number($('#brc-hectad-map-thresh' + n).val())
  }
  function setThresh(n, v) {
    $('#brc-hectad-map-thresh' + n).val(v)
  }

  // Create yearly record accumulation chart
  if (typeof(brccharts) !== "undefined") {
    brcyearly = brccharts.yearly({
      selector: ".brc-recsbyyear-chart",
      legendFontSize: 14,
      data: [],
      metrics: [{ prop: 'n', label: 'Records per year', opacity: 1, colour: '#66b3ff'}],
      taxa: ['taxon'],
      minYear: 2000,
      maxYear: new Date().getFullYear(),
      width: 500,
      height: 300,
      perRow: 1,
      expand: true,
      showTaxonLabel: false,
      showLegend: false,
      margin: {left: 60, right: 0, top: 10, bottom: 20},
      axisLeftLabel: 'Records per year'
    });
  };

  // Create phenology chart
  var periods = [
    {
      id: 'week',
      yAxisLabel: 'Records per week'
    },
    {
      id: 'month',
      yAxisLabel: 'Records per month'
    }
  ];
  periods.forEach(function(p) {
    var selector = ".brc-recsby" + p.id + "-chart";
    if (typeof(brccharts) !== "undefined" && $(selector).length) {
      brcphenology = brccharts.phen1({
        selector: selector,
        taxa: ['taxon'],
        width: 500,
        height: 300,
        perRow: 1,
        expand: true,
        showTaxonLabel: false,
        showLegend: false,
        margin: {left: 60, right: 0, top: 10, bottom: 20},
        axisLeftLabel: p.yAxisLabel
      });
    };
  });

  // Attach toggle button handler for dualmap control
  $('input[name="dualMap-button-group"]').on('change', function(e) {
    var sel = $('input[name="dualMap-button-group"]:checked').val();
    if (sel === "hectad") {
      $('#dualMap-hectad-map').show();
      $('#dualMap-explore-map').hide();
    } else {
      $('#dualMap-hectad-map').hide();
      $('#dualMap-explore-map').show();
      $('#leaflet-explore-map').idcLeafletMap('invalidateSize');
    };
  });

  // ES custom script definition for hectad map
  indiciaFns.populateHectadMap = function (el, sourceSettings, response) {

    hectadData = response.aggregations._rows.buckets
      .filter(function(h){return h.key['location-grid_square-10km-centre']})
      .map(function(h) {
      var latlon = h.key['location-grid_square-10km-centre'].split(' ');
      var hectad = bigr.getGrFromCoords(Number(latlon[0]), Number(latlon[1]), 'wg', '', [10000]);
      return {
        gr: hectad.p10000,
        recs: h.doc_count,
        minYear: new Date(h.minYear.value_as_string).getFullYear(),
        maxYear: new Date(h.maxYear.value_as_string).getFullYear(),
      };
    });

    // Turns out that sometimes more than one lat/lon combo is returned for a single hectad, so
    // can't just do a simple map. Need to reduce to single values for each hectad.
    // Also filter out any values with null hectads
    hectadData = hectadData.filter(function(h){return h.gr}).reduce(function(a,h) {
      var existing = a.find(function(ah){return ah.gr === h.gr});
        if (existing) {
          existing.recs += h.recs;
          existing.minYear = h.minYear < existing.minYear ? h.minYear : existing.minYear;
          existing.maxYear = h.maxYear > existing.maxYear ? h.maxYear : existing.maxYear;
        } else {
          a.push({gr: h.gr, recs: h.recs, minYear: h.minYear, maxYear: h.maxYear});
        }
        return a;
      }, []);

    brcmap.redrawMap();
    brcmap.showBusy(false);
  }

  // ES custom script for records by year
  indiciaFns.populateRecsByYearChart = function(el, sourceSettings, response) {

    var yearlyData = response.aggregations._rows.buckets.filter(function(w) {return w.key['event-year']}).map(function(y) {
      return {
        taxon: 'taxon',
        year: y.key['event-year'],
        n: y.doc_count
      };
    });
    var opts = {data: yearlyData};
    brcyearly.setChartOpts(opts);
  }

  // ES custom script for records through year
  indiciaFns.populateRecsThroughYearChart = function(el, sourceSettings, response) {

    var period, periodKey;
    if (sourceSettings.uniqueField === 'event.week') {
      period = 'week';
      periodKey = 'event-week';
    } else {
      period = 'month';
      periodKey = 'event-month';
    };
    var phenData = response.aggregations._rows.buckets.filter(function(w) {return w.key[periodKey]}).map(function(y) {
      var val = {
        taxon: 'taxon',
        records: y.doc_count
      };
      val[period] = y.key[periodKey];
      return val;
    });
    var opts = {
      data: phenData,
      metrics: [{ prop: 'records', label: '', colour: 'blue' },],
    };
    brcphenology.setChartOpts(opts);
  }

  function getBandedColour() {

    var colour1 = $('#brc-hectad-map-colour1').val();
    var colour2 = $('#brc-hectad-map-colour2').val();
    var colour3 = $('#brc-hectad-map-colour3').val();
    var thresh1 = Number($('#brc-hectad-map-thresh1').val());
    var thresh2 = Number($('#brc-hectad-map-thresh2').val());

    function getBandColour(minYear, maxYear) {

      var priority = $('#brc-hectad-map-priority').find(':selected').val();
      var year = priority === 'recent' ? maxYear : minYear;
      if (year > thresh2) {
        return colour3;
      } else if (year > thresh1) {
        return colour2;
      } else {
        return colour1;
      }
    }

    function getKey(minYear, maxYear) {

      var priority = $('#brc-hectad-map-priority').find(':selected').val();
      var year = priority === 'recent' ? maxYear : minYear;
      if (year > thresh2) {
        return 'group3';
      } else if (year > thresh1) {
        return 'group2';
      } else {
        return 'group1';
      }
    }

    return new Promise(function (resolve, reject) {

      // At this stage, there might be some records without a
      // resolved hectad (possibly outside UK?) so filter these out.
      var recs = hectadData.filter(function(h){return h.gr}).map(function(h) {
        var minYear = h.minYear ? h.minYear : 'no info';
        var maxYear = h.maxYear ? h.maxYear : 'no info';
        return {
          gr: h.gr,
          id: h.gr,
          colour: getBandColour(h.minYear, h.maxYear),
          caption: 'Hectad: <b>' + h.gr + '</b>; Recs: <b>' + h.recs + '</b>; Earliest: <b>' + minYear + '</b>; Latest: <b>' + maxYear + '</b>',
          noCaption: 'Move mouse cursor over dot for info',
          legendKey: getKey(h.minYear, h.maxYear),
        };
      });

      var currentYear = new Date().getFullYear()
      var legendText1 = thresh1 + ' and before';
      var legendText2 = thresh2 === thresh1+1 ? thresh2 : (thresh1+1) + "-" + thresh2;
      var legendText3 = currentYear === thresh2+1 ? currentYear : (thresh2+1) + "-" + currentYear;

      var priority = $('#brc-hectad-map-priority').find(':selected').val();

      resolve({
        records: recs,
        size: 1,
        precision: 10000,
        shape: 'circle',
        opacity: 1,
        legend: {
          display: true,
          title: priority === 'recent' ? 'Year of latest record in 10 km square' : 'Year of first record in 10 km square',
          size: 1,
          shape: 'circle',
          precision: 10000,
          x: 100,
          y: 100,
          scale: 1,
          lines: [
            {colour: colour1, text: legendText1, key: 'group1'},
            {colour: colour2, text: legendText2, key: 'group2'},
            {colour: colour3, text: legendText3, key: 'group3'}
          ]
        }
      });
    });
  }

  function getHectadsColour() {

    return new Promise(function (resolve, reject) {

      var viridis = ["#440154","#440256","#450457","#450559","#46075a","#46085c","#460a5d","#460b5e","#470d60","#470e61","#471063","#471164","#471365","#481467","#481668","#481769","#48186a","#481a6c","#481b6d","#481c6e","#481d6f","#481f70","#482071","#482173","#482374","#482475","#482576","#482677","#482878","#482979","#472a7a","#472c7a","#472d7b","#472e7c","#472f7d","#46307e","#46327e","#46337f","#463480","#453581","#453781","#453882","#443983","#443a83","#443b84","#433d84","#433e85","#423f85","#424086","#424186","#414287","#414487","#404588","#404688","#3f4788","#3f4889","#3e4989","#3e4a89","#3e4c8a","#3d4d8a","#3d4e8a","#3c4f8a","#3c508b","#3b518b","#3b528b","#3a538b","#3a548c","#39558c","#39568c","#38588c","#38598c","#375a8c","#375b8d","#365c8d","#365d8d","#355e8d","#355f8d","#34608d","#34618d","#33628d","#33638d","#32648e","#32658e","#31668e","#31678e","#31688e","#30698e","#306a8e","#2f6b8e","#2f6c8e","#2e6d8e","#2e6e8e","#2e6f8e","#2d708e","#2d718e","#2c718e","#2c728e","#2c738e","#2b748e","#2b758e","#2a768e","#2a778e","#2a788e","#29798e","#297a8e","#297b8e","#287c8e","#287d8e","#277e8e","#277f8e","#27808e","#26818e","#26828e","#26828e","#25838e","#25848e","#25858e","#24868e","#24878e","#23888e","#23898e","#238a8d","#228b8d","#228c8d","#228d8d","#218e8d","#218f8d","#21908d","#21918c","#20928c","#20928c","#20938c","#1f948c","#1f958b","#1f968b","#1f978b","#1f988b","#1f998a","#1f9a8a","#1e9b8a","#1e9c89","#1e9d89","#1f9e89","#1f9f88","#1fa088","#1fa188","#1fa187","#1fa287","#20a386","#20a486","#21a585","#21a685","#22a785","#22a884","#23a983","#24aa83","#25ab82","#25ac82","#26ad81","#27ad81","#28ae80","#29af7f","#2ab07f","#2cb17e","#2db27d","#2eb37c","#2fb47c","#31b57b","#32b67a","#34b679","#35b779","#37b878","#38b977","#3aba76","#3bbb75","#3dbc74","#3fbc73","#40bd72","#42be71","#44bf70","#46c06f","#48c16e","#4ac16d","#4cc26c","#4ec36b","#50c46a","#52c569","#54c568","#56c667","#58c765","#5ac864","#5cc863","#5ec962","#60ca60","#63cb5f","#65cb5e","#67cc5c","#69cd5b","#6ccd5a","#6ece58","#70cf57","#73d056","#75d054","#77d153","#7ad151","#7cd250","#7fd34e","#81d34d","#84d44b","#86d549","#89d548","#8bd646","#8ed645","#90d743","#93d741","#95d840","#98d83e","#9bd93c","#9dd93b","#a0da39","#a2da37","#a5db36","#a8db34","#aadc32","#addc30","#b0dd2f","#b2dd2d","#b5de2b","#b8de29","#bade28","#bddf26","#c0df25","#c2df23","#c5e021","#c8e020","#cae11f","#cde11d","#d0e11c","#d2e21b","#d5e21a","#d8e219","#dae319","#dde318","#dfe318","#e2e418","#e5e419","#e7e419","#eae51a","#ece51b","#efe51c","#f1e51d","#f4e61e","#f6e620","#f8e621","#fbe723","#fde725"];

      var colour = d3.scaleQuantize()
        .domain(d3.extent(hectadData, function(h) {return Math.log(h.recs)}))
        .range(viridis);

      // At this stage, there might be some records without a
      // resolved hectad (possibly outside UK?) so filter these out.
      var recs = hectadData.filter(function(h){return h.gr}).map(function(h) {
        return {
          gr: h.gr,
          id: h.gr,
          colour: colour(Math.log(h.recs)),
          caption: h.recs
        };
      });

      resolve({
        records: recs,
        size: 1,
        precision: 10000,
        shape: 'circle',
        opacity: 1,
      });
    });
  }

  function getDateString() {
    var today = new Date();
    var yyyy = today.getFullYear();
    var mm = today.getMonth() + 1; // Months start at 0!
    var dd = today.getDate();

    if (dd < 10) dd = '0' + dd;
    if (mm < 10) mm = '0' + mm;

    return dd + '/' + mm + '/' + yyyy;
  }

  function replaceTags(name) {
    // Replace em tags with i tags. All white space within
    // i tagged text must be replaced by '</i> <i>' in order
    // to facilitate word wrapping in SVG.
    var nameNew = ''
    var i=0
    var tagOpen = false
    while (i<name.length){
      var partName = name.substr(i)
      if (partName.length >= 4 && partName.substr(0,4) === '<em>') {
        tagOpen = true
        nameNew += '<i>'
        i += 4
      } else if (partName.length >= 5 && partName.substr(0,5) === '</em>') {
        tagOpen = false
        nameNew += '</i>'
        i += 5
      } else if (name[i] === ' ' && tagOpen) {
        nameNew += '</i> <i>'
        i += 1
      } else {
        nameNew += name[i]
        i += 1
      }
    }
    return nameNew
  }
});