jQuery(document).ready(function($) {

  console.log('Page JS loaded')
  if (typeof(d3)=== 'undefined') return // Prevent code running when edit form displayed

  let brcmap_e, mapData
  let week = 1
  let pp = false
  let dataLoaded = false

  // Reset page title
  // const pageTitle = d3.select('#preferred-name').property('value')
  // d3.select('.page-header span').text(pageTitle)

  if (typeof(brcatlas_e) !== "undefined") {

    const mapOpts = {
      selector: "#ebms-tracemap",
      outputWidth: 900,
      outputHeight: 400,
      mapBB: [1000000, 800000, 6000000, 5500000], // [minx, miny, maxx, maxy]
      expand: true,
      fillEurope: '#1a1a20',
      fillWorld: '#1a1a20',
      fillOcean: '#3a3d4a',
      strokeEurope: '#27272d',
      fillDot: d3.select('#dot-colour').property('value'),
      dotSize1: 1,
      dotSize2: 3,
      dotSize3: 6,
      dotOpacity1: 1,
      dotOpacity2: 0.4,
      dotOpacity3: 0.1,
      hightlightAllEurrope: true,
      aggregate: false
    }

    brcmap_e = brcatlas_e.eSvgMap(mapOpts)

    console.log('external-key', d3.select('#external-key').property('value'))

    if (d3.select('#external-key').property('value')) {
      brcmap_e.showBusy(true)
    }
  }

  indiciaFns.processTraceMapData2 = function (el, sourceSettings, response) {
    console.log(response.aggregations.aggs.buckets)
    const mapData = []
    response.aggregations.aggs.buckets.forEach(wb => {
      const week = Number(wb.key)
      wb.geohash.buckets.forEach (ghb => {
        const latlon = geohashToWkt(ghb.key)
        mapData.push({
          week: week,
          // Getting lat/lon from ES aggregation doesn't seem
          // to give centroid of geohash as espected, so instead
          // we use one worked out directly from geohash.
          //lat: Number(ghb.centroid.location.lat),
          //lon: Number(ghb.centroid.location.lon)
          lat: latlon.lat,
          lon: latlon.lon
        })
      })
    })
    console.log('data', mapData)
    brcmap_e.loadData(mapData)
    brcmap_e.mapData(1)
    brcmap_e.showBusy(false)
    dataLoaded = true
  }

  indiciaFns.processTraceMapData = function (el, sourceSettings, response) {

    //console.log('ES response', response)
    mapData = response.hits.hits.map(d => {
      return {
        year: Number(d._source.event.year.replace(',','')),
        week: Number(d._source.event.week),
        lat: Number(d._source.location.point.split(',')[0]),
        lon: Number(d._source.location.point.split(',')[1])
      }
    })
    console.log('data', mapData)
    brcmap_e.loadData(mapData)
    brcmap_e.mapData(1)
    brcmap_e.showBusy(false)
    dataLoaded = true
  }

  indiciaFns.playPause = function () {
    pp = !pp
    if (pp) {
      d3.select('#playPause').text("||")
    } else {
      d3.select('#playPause').text(">")
    }
  }

  // If initplay is set, start the animation
  if (d3.select('#initplay').property('value') === 'yes') {
    indiciaFns.playPause()
  }

  indiciaFns.displayWeek = function (e) {
    week = Number(e)
    brcmap_e.mapData(week)
    d3.select("#weekNo").text(brcmap_e.getWeekDates(week))
  }

  indiciaFns.allYearsCheckboxClicked = function() {

    if (d3.select('#data-year-allyears').property('checked')) {
      currentVal = d3.select('#data-year-filter').property('value')
      d3.select('#data-year-filter').property('value', '')
      d3.select('#data-year-filter').property('disabled', true)
    } else {
      d3.select('#data-year-filter').property('value', d3.select('#data-year-filter').property('max'))
      d3.select('#data-year-filter').property('disabled', false)
    }

  }

  // Reset GET form URL parameters before submission (species selector)
  indiciaFns.speciesDetailsSub = () => {
    brcmap_e.showBusy(true)

    let taxa_taxon_list_id
    if ($('#occurrence\\:taxa_taxon_list_id').val() != '') {
      taxa_taxon_list_id = $('#occurrence\\:taxa_taxon_list_id').val()
    } else if ($('#taxa_taxon_list_id').val() != '') {
      taxa_taxon_list_id = $('#taxa_taxon_list_id').val()
    }

    $('#ebms-atlas-map-form #taxa_taxon_list_id').val(taxa_taxon_list_id)
    if ($('#data-year-filter').val()) {
      $('#ebms-atlas-map-form #data_year_filter').val($('#data-year-filter').val())
    }

    document.forms["ebms-atlas-map-form"].submit()
  }

  function geohashToWkt(geohash) {
    var minLat =  -90;
    var maxLat =  90;
    var minLon = -180;
    var maxLon = 180;
    var shift;
    var isForMin;
    var isForLon = true;
    var centreLon;
    var centreLat;
    var mask;
    // The geohash alphabet.
    const ghs32 = '0123456789bcdefghjkmnpqrstuvwxyz';
    for (var i = 0; i < geohash.length; i++) {
      const chr = geohash.charAt(i);
      const idx = ghs32.indexOf(chr);
      if (idx === -1) {
        throw new Error('Invalid character in geohash');
      }
      for (shift = 4; shift >= 0; shift--) {
        // Test bit at position shift. If 1, then for min, else for max.
        mask = 1 << shift;
        isForMin = idx & mask;
        // Bits extracted from characters toggle between x & y.
        if (isForLon) {
          centreLon = (minLon + maxLon) / 2;
          if (isForMin) {
            minLon = centreLon;
          } else {
            maxLon = centreLon;
          }
        } else {
          centreLat = (minLat + maxLat) / 2;
          if (isForMin) {
            minLat = centreLat;
          } else {
            maxLat = centreLat;
          }
        }
        isForLon = !isForLon;
      }
    }
    return {
      lat: minLat + (maxLat - minLat) / 2,
      lon: minLon + (maxLon - minLon) / 2
    }
    //return 'POLYGON((' + minLon + ' ' + minLat + ',' + maxLon + ' ' + minLat + ', ' + maxLon + ' ' + maxLat + ', ' + minLon + ' ' + maxLat + ', ' + minLon + ' ' + minLat + '))';
  }

  // Kick of slider animation setInterval
  setInterval(function() {
    if (pp && dataLoaded) {
      week = week === 52 ? 1 : week+1
      document.querySelector('.slider').value = week
      indiciaFns.displayWeek(week)
    }
  }, 300)
})