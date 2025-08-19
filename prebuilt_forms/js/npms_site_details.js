window.npmsFns = {};

(function ($) {

  let map, taxaByYearChart, optsTaxaByYear
  let squareImages, pSquareImages, squareGallery
  let squareImagesMod = []
  let toggleComment = false
  const flds = [
    ['samples', 'Plot samples'],
    ['dates', 'Number of visits'],
    ['earliest', 'Earliest visit'],
    ['latest', 'Latest visit'],
    //['records', 'Records'],
    ['reviewed', 'Reviewed records'],
    ['not_reviewed', 'Unreviewed records'],
    ['taxa', 'Taxa'],
    ['surveys', 'Survey type(s)']
  ]

  npmsFns.displayInfo = function() {
    // Do some post processing of report data
    postProcessReportData()
    // Create interface
    createMap()
    createSummaryInfo()
    updateSummaryInfo(indiciaData.npmsCoreSquareDetails.gr)
    createTaxaByYearChart()
    updateTaxaByYearChart(indiciaData.npmsCoreSquareDetails.gr)
    createTaxaByPlotTable()
    updateTaxaByPlotTable(indiciaData.npmsCoreSquareDetails.gr)
    createCoreSquareLayersReport()
    createGalleries()
  }

  function postProcessReportData() {
    //////////////////////////////////////
    // General core square and plot report
    //////////////////////////////////////


    // The report includes two types of records - the main records
    // that group the records on location and have most of
    // the summary stats, and other records which group on both
    // location and occurrence verification status. Build a new
    // array based on the first type of record, but enriched with
    // the data from the second. The main records - one for each
    // core plot/sub-plot have values in the ID column. The others
    // have null in the ID column, so we can use that the distiguish them.
    const consolidatedCoreSquare = indiciaData.npmsCoreSquareReport.records.filter(r => r.id).map(r => {
      const crec = {...r}
      delete crec.record_status
      delete crec.records
      crec.reviewed = 0
      crec.not_reviewed = 0
      return crec
    })
    indiciaData.npmsCoreSquareReport.records.filter(r => r.id === null).forEach(r => {
      let crec = consolidatedCoreSquare.find(c => c.name === r.name)
      if (r.record_status === 'V') {
        crec.reviewed += Number(r.records)
      } else {
        crec.not_reviewed += Number(r.records)
      }
    })

    // Do some sorting and general enrichment/manipulation
    consolidatedCoreSquare.sort((a,b) => {
      if (a.name.length < b.name.length) {
        return -1
      } else if (a.name.length > b.name.length) {
        return 1
      } else {
        return 0
      }
    }).forEach(p => {
      if (!p.plot_type && p.id !== null) {
        // Core square (the datum from SQL query is null)
        p.plot_type = 'Core square'
        p.comment = '(Not applicable to core squares.)'
      } else if (!p.comment && p.id !== null) {
        p.comment = '(No comment provided.)'
      }
      // Remove the word 'survey' from survey types.
      if (p.surveys) {
        p.surveys = p.surveys.replace(/ survey/g, '')
      }
    })

    indiciaData.npmsCoreSquareReport.records = consolidatedCoreSquare

    ///////////////////////
    // Consolidate Taxon by year report
    ///////////////////////
    // Need to make a single record for each plot/year combination - bringing
    // in different survey types as different columns and generally configure
    // for use with temporal chart. (The plot name becomes 'taxon' for the
    // purposes of that chart.)
    let consolidatedTaxaByYear = []
    indiciaData.npmsCoreSquareTaxaByYear.filter(d => d.year).forEach(d => {
      let match = consolidatedTaxaByYear.find(r => r.taxon === d.name && r.period === Number(d.year))
      if (!match) {
        match = {
          taxon: d.name,
          period: Number(d.year),
          all: 0,
          inventory: 0,
          indicator: 0,
          wildflower: 0
        }
        consolidatedTaxaByYear.push(match)
      }
      const survey = d.survey.replace(/ survey/g, '').toLowerCase()
      match[survey] += Number(d.taxa)
    })

    // Rename 'all' attr to 'any'
    // Instead of doing this, we could update the report to return different column name
    consolidatedTaxaByYear = consolidatedTaxaByYear.map(r => {
      const r2 = {...r}
      r2.any = r2.all
      delete r2.all
      return r2
    })

    indiciaData.npmsCoreSquareTaxaByYear = consolidatedTaxaByYear
    // console.log('consolidatedTaxaByYear', consolidatedTaxaByYear)
  }

  function createMap() {

    $('#mapid').height(400)
    map = L.map('mapid').setView([55, 0], 5)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: `&copy; <a href='https://www.openstreetmap.org/copyright'>OpenStreetMap</a> contributors`
    }).addTo(map)

    // Show the core square on the map
    const ftrsCoreSquare = new L.featureGroup;
    let lyr = L.geoJSON(bigr.getGjson(indiciaData.npmsCoreSquareDetails.gr, 'wg', 'square', 1), {
      style: {
        'color': 'blue',
        'fillColor': 'none'
      },
    }).addTo(map)
    ftrsCoreSquare.addLayer(lyr)
    indiciaData.npmsCoreSquareReport.records.find(p => p.name === indiciaData.npmsCoreSquareDetails.gr).leafletLayer = lyr


    // Show the core square sub-plots on the map
    const ftrsPlot = new L.featureGroup;
    indiciaData.npmsCoreSquareReport.records.filter(plot => plot.plot_type !== 'Core square').forEach(plot => {
      let geojsonFeature = {
        'type': 'Feature',
        'properties': {
            'name': plot.name
        },
        'geometry': JSON.parse(plot.boundary_geom)
      }
      let lyr = L.geoJSON(geojsonFeature, {
        style: {
          'color': 'magenta',
          'fillColor': 'magenta'
        },
        onEachFeature: function(feature, layer) {
          // if (feature.properties) {
          //   layer.bindPopup(`Plot name: ${feature.properties['name']}`)
          // }
          layer.on('mouseover', function () {
            this.setStyle({
              'color': '#00ff00',
              'fillColor': '#00ff00'
            })
          })
          layer.on('mouseout', function () {
            this.setStyle({
              'color': 'magenta',
              'fillColor': 'magenta'
            })
          })
          layer.on('click', function () {
            const plotName = feature.properties['name']
            $('#summary-info-select').val(plotName)
            updateSummaryInfo(plotName)
            updateTaxaByYearChart(plotName)
            updateTaxaByPlotTable(plotName)
            updateGalleries()
          })
        }
      }).addTo(map)
      ftrsPlot.addLayer(lyr)

      plot.leafletLayer = lyr
    })
  }

  function createSummaryInfo() {

    const $seldiv = $('<div>').appendTo($('#summary-info'))

    const $select = $('<select>').appendTo($seldiv)
    $select.attr('id', 'summary-info-select')
    $select.on('change', function() {
      updateSummaryInfo(this.value)
      updateTaxaByYearChart(this.value)
      updateTaxaByPlotTable(this.value)
      updateGalleries()
    })
    indiciaData.npmsCoreSquareReport.records.forEach(p => {
      $opt = $('<option>').appendTo($select)
      $opt.text(`${p.name}${p.plotnumber ? ` (${p.plotnumber})` : ''}`)
      $opt.attr('value', p.name)
    })

    const $name = $('<div>').appendTo($($seldiv))
    $name.css('display', 'inline-block')
    $name.css('margin-left', '0.5em')
    $name.css('font-size', '0.8em')
    $name.attr('id', 'summary-info-name')

    const $dl = $('<dl>').appendTo($('#summary-info'))
    $dl.addClass('dl-horizontal')
    $dl.addClass('detail-panel')

    flds.forEach(function(d) {
      const $dt=$('<dt>').appendTo($dl)
      $dt.css('width', '200px')
      $dt.css('margin-right', '20px')
      $dt.text(d[1])

      const $dd = $('<dd>').appendTo($dl)
      $dd.attr('id', `summary-info-${d[0]}`)
    })

    const $commentHeader = $('<div>').appendTo($('#summary-info'))
    $commentHeader.html(`<b>Originator's comment</b> <span id='summary-info-comment-toggle'>[show]</span>`)

    const $toggle = $('#summary-info-comment-toggle')
    $toggle.on('click', function() {
      if (!toggleComment) {
        $('#summary-info-comment-toggle').text('[hide]')
        $('#summary-info-comment').show()
      } else {
        $('#summary-info-comment-toggle').text('[show]')
        $('#summary-info-comment').hide()
      }
      toggleComment = !toggleComment
    })

    const $p = $('<div>').appendTo($('#summary-info'))
    $p.attr('id', 'summary-info-comment')
    $p.css('display', 'none')
  }

  function updateSummaryInfo(plotName) {

    const plot = indiciaData.npmsCoreSquareReport.records.find(function(p) {
      return p.name === plotName
    })
    // console.log('Display ', plot)

    $('#summary-info-name').text(`(${plot.plot_type.toLowerCase()})`)

    flds.forEach(function(d) {
      $(`#summary-info-${d[0]}`).text(plot[d[0]] == null ? 'n/a' : plot[d[0]])
    })

    $('#summary-info-comment').text(plot.comment)

    map.fitBounds(plot.leafletLayer.getBounds())
  }

  function createTaxaByYearChart() {
    // Initialise chart
    optsTaxaByYear = {
      selector: '#chart-taxa-by-year',
      width: 500,
      height: 300,
      taxa: [null],
      minMaxY: null,
      perRow: 1,
      showLegend: true,
      legendFontSize: 16,
      footerFontSize: 16,
      footerAlign: 'right',
      chartStyle: 'bar',
      axisLeftLabel: 'Number of taxa',
      axisLabelFontSize: 12,
      margin: {left: 50, right: 0, bottom: 20, top: 5},
      xPadPercent: 3,
      yPadPercent: 3,
      minPeriod: 2015,
      maxPeriod: 2023,
      // minPeriodTrans: 1975,
      // maxPeriodTrans: 2022, // Adjusted to match data later
      missingValues: 'break',
      expand: true,
      minY: 0,
      interactivity: 'mouseclick',
      overrideHighlight: true,
      metrics: [
        {
          prop: 'any',
          colour: '#A5A5A5'
        },
        {
          prop: 'inventory',
          colour: '#FDC000'
        },
        {
          prop: 'indicator',
          colour: '#5B9BD5'
        },
        {
          prop: 'wildflower',
          colour: '#70AD47',
        }
      ]
    }
    //optsTaxaByYear.metrics = metrics
    optsTaxaByYear.fullMetrics = [...optsTaxaByYear.metrics]
    // Initialise chart
    taxaByYearChart = brccharts.temporal(optsTaxaByYear)
  }

  function updateTaxaByYearChart(plotName) {
    optsTaxaByYear.data  = indiciaData.npmsCoreSquareTaxaByYear.filter(d => d.taxon === plotName)
    // Remove any unecessary inventory type metrics for this plot
    optsTaxaByYear.metrics = optsTaxaByYear.fullMetrics.filter((m)=> {
      const recsForProp = optsTaxaByYear.data.reduce((a,d) => a + d[m.prop], 0)
      return recsForProp > 0 ? true : false
    })
    // If only one iventory type, remove the 'any' metric
    if (optsTaxaByYear.metrics.length === 2) {
      optsTaxaByYear.metrics = optsTaxaByYear.metrics.filter(m => m.prop != 'any')
    }
    // Add the footer if any data
    if (optsTaxaByYear.metrics.length > 1) {
      optsTaxaByYear.footer = "Click on legend items to hide/show survey types"
    } else {
      optsTaxaByYear.footer = ""
    }
    taxaByYearChart.setChartOpts(optsTaxaByYear)

    // Move the legend items to the right a bit
    $('.brc-legend-item').attr('transform', 'translate(50 0)')
  }

  function createTaxaByPlotTable() {
    const $table = $('<table>').appendTo($('#taxa'))
    $table.attr('id', 'taxa-table')
    const $row = $('<tr>').appendTo($table)

    const $t = $('<th>').appendTo($row)
    $t.addClass('taxon-table-header')
    $t.attr('id', 'taxon-table-header-taxon')
    $t.text('Taxon')
    $t.append($('<span>'))

    const $c = $('<th>').appendTo($row)
    $c.addClass('taxon-table-header')
    $c.attr('id', 'taxon-table-header-common')
    $c.text('Common name')
    $c.append($('<span>'))

    const $r = $('<th>').appendTo($row)
    $r.addClass('taxon-table-header')
    $r.attr('id', 'taxon-table-header-records')
    $r.text('Records')
    $r.append($('<span>'))

    $row.attr('data-col', 'records')
    $row.attr('data-dir', 'down')

    const up = '&#9650;'
    const down = '&#9660;'
    $('#taxon-table-header-records span').html(down)

    $('.taxon-table-header').on('click', function(e) {
      const idTokens = $(this).attr('id').split('-')
      const col = idTokens[idTokens.length-1]

      $('.taxon-table-header span').html('')

      let sortDir, symbol
      if ($row.attr('data-col') === col && $row.attr('data-dir') === 'down') {
        sortDir = 'up'
      } else if ($row.attr('data-col') === col && $row.attr('data-dir') === 'up') {
        sortDir = 'down'
      } else if (col === 'records') {
        sortDir = 'down'
      } else {
        sortDir = 'up'
      }
      if (sortDir === 'up') {
        $(`#taxon-table-header-${col} span`).html(up)
      } else {
        $(`#taxon-table-header-${col} span`).html(down)
      }
      $row.attr('data-col', col)
      $row.attr('data-dir', sortDir)
      const plot = $('#summary-info-select').find(":selected").val()
      updateTaxaByPlotTable(plot)
    })
  }

  function updateTaxaByPlotTable(plotName) {

    const $table = $('#taxa-table')
    $('.taxa-row').remove()
    const dir = $('#taxa-table tr').attr('data-dir')
    const col = $('#taxa-table tr').attr('data-col')

    indiciaData.npmsCoreSquareTaxaByPlot
      .filter(r => {
        //console.log('r.plot', r.plot)
        //console.log('plotName', plotName)
        return r.plot === plotName
  })
      .sort((a,b) => {
        let x, y
        if (col === 'records') {
          x = Number(a.records)
          y = Number(b.records)
        } else {
          x = a[col]
          y = b[col]
        }
        if (x < y) {
          return  dir === 'up' ? -1 : 1
        }
        if (x > y) {
          return dir === 'up' ? 1 : -1
        }
        return 0
      })
      .forEach((r,i) => {
        const $row = $('<tr>').appendTo($table)
        $row.addClass('taxa-row')
        if (i%2 === 0) {
          $row.addClass('even-row')
        } else {
          $row.addClass('odd-row')
        }
        $(`<td><i>${r.taxon}</i></td>`).appendTo($row)
        $(`<td>${r.common ? r.common : ''}</td>`).appendTo($row)
        $(`<td>${r.records}</td>`).appendTo($row)
      })
  }

  function createCoreSquareLayersReport() {
    //console.log('layers', indiciaData.npmsCoreSquareLayers)
    $('#core-square-layers').html('')
    indiciaData.npmsCoreSquareLayers.forEach(l => {
      $('#core-square-layers').append(`<div>${l.name} (${l.layer_type}): ${l.area}%</div>`)
    })

  }

  async function createGalleries() {
    // It transpires that some of the images returned by the report cannot actually
    // be found in the warehouse. LightGallery doesn't deal very well with images
    // which can't be found - showing as a blank image. There doesn't seem to be
    // a way of preventing these from showing.
    // So ideally we'd like to filter the list of images before they are passed
    // to lightgallery based on whether or not they can be found by polling the warehouse
    // to see if each image can be found. I tried to do this using the fetch HEADER method
    // but this encountered cors problems. Specifying no-cors returns results that
    // do not differentiate between images which can and can't be found. So had
    // to resort to loading each image into an Image object to test if they can
    // load. But doing this synchronously delays the creation of the gallery too much
    // So the gallery is first loaded with all images, including those it can't find
    // and then updated to remove the ones that can't be found.

    pSquareImages = indiciaData.npmsCoreSquareLocationSampleMedia
      // This is used after the gallery is generated in order to remove any images
      // which cannot be found on server.
      .sort(dateSort)
      .map(img => {
        return new Promise(resolve => {
          const image = new Image()
          image.addEventListener('load', () => resolve(true))
          image.addEventListener('error', () => resolve(false))
          image.src = `https://warehouse1.indicia.org.uk/upload/${img.path}`
        })
      })

    squareImages = indiciaData.npmsCoreSquareLocationSampleMedia
      .sort(dateSort)
      .map(img => {
        let caption
        const date = img.created_on.split(" ")[0]
        const plot = `${img.plot}${img.plotnumber ? ` (${img.plotnumber})` : ''}`
        const imgcap = `${img.caption ? ` (Original caption: ${img.caption})` : ''}`
        if (img.image_type === "sample" || img.image_type=== "location") {
          caption = `${date} ${plot}${imgcap}`
        } else if (img.image_type === "occurrence") {
          let taxon = `<i>${img.preferred_taxon}</i>${img.common_name ? ` (${img.common_name})` : ''}`
          caption = `${date} ${plot} ${taxon}${imgcap}`
        }
        return {
          alt: caption,
          src: `https://warehouse1.indicia.org.uk/upload/${img.path}`,
          thumb: `https://warehouse1.indicia.org.uk/upload/${img.path}`,
          caption: caption, // We use this to implement captions outside of lightgallery
          plot: img.plot, // Not required for lightgallery but we use later for filtering
          image_type: img.image_type, // Not required for lightgallery but we use later for filtering
        }
      })


    // Add a div for image type selection and a div for the gallery itself
    const $imgSelDiv = $('<div>').appendTo($('#gallery'))
    const $galDiv = $('<div>').appendTo($('#gallery'))
    $galDiv.attr('id', 'gallery-div')
    $galDiv.css('width', '100%')
    $galDiv.css('height', '400px')

    // Add a div for the custom caption display
    const $galCapDiv = $('<div>').appendTo($('#gallery'))
    $galCapDiv.attr('id', 'gal-cap-div')
    $galCapDiv.css('padding', '0 0.3em 0.3em 0.3em')
    $galCapDiv.css('background-color', 'black')
    $galCapDiv.css('color', 'white')
    $galCapDiv.css('font-size', '0.6em')
    $galCapDiv.css('text-align', 'center')

    // Get the DOM object for the gallery div
    const lgContainer = document.getElementById('gallery-div')

    // Add an lgAfterSlide listener for our custom caption behaviour
    lgContainer.addEventListener('lgAfterSlide', function(e){
      displayCaption(e.detail.index)
    }, false)

    // Image type selector
    const $imgSel = $('<select>').appendTo($imgSelDiv)
    $imgSel.attr('id', 'gallery-type-select')
    $imgSel.on('change', function() {
      updateGalleries()
    })
    let $optImgSel = $('<option>').appendTo($imgSel)
    $optImgSel.text('Plot sample images')
    $optImgSel.attr('value', 'sample')
    $optImgSel = $('<option>').appendTo($imgSel)
    $optImgSel.text('Plot location images')
    $optImgSel.attr('value', 'location')
    $optImgSel = $('<option>').appendTo($imgSel)
    $optImgSel.text('Record images')
    $optImgSel.attr('value', 'occurrence')

    // Gallery
    // After https://www.lightgalleryjs.com/demos/inline/ & https://codepen.io/sachinchoolur/pen/zYZqaGm
    squareGallery = lightGallery(lgContainer, { // eslint-disable-line no-undef
      container: lgContainer,
      dynamic: true,
      // Turn off hash plugin in case if you are using it
      // as we don't want to change the url on slide change
      //hash: false,
      // Do not allow users to close the gallery
      closable: false,
      // Hide download button
      download: false,
      // Add maximize icon to enlarge the gallery
      showMaximizeIcon: true,
      // Add plugins
      plugins: [lgZoom, lgThumbnail], // eslint-disable-line no-undef
      dynamicEl: squareImages.length ? squareImages.map(i => {return {...i}}) : [{src: '/modules/custom/npms_vis/images/no-image.jpg'}],
      thumbWidth: 90,
      thumbHeight: "60px",
      thumbMargin: 4
    })
    // Since we are using dynamic mode, we need to programmatically open lightGallery
    setTimeout(() => {
      squareGallery.openGallery()
      updateGalleries()
    }, 500)
  }

  function updateGalleries() {

    const plotName = $('#summary-info-select').find(":selected").val();
    const imageType = $('#gallery-type-select').find(":selected").val();
    //console.log('update gallery', plotName, imageType)

    // Only run this once pSquareImages is settled which means that
    // the results of attempting to fetch all images is known.
    Promise.allSettled(pSquareImages).then(data => {
      // Dynamically update gallery to remove any images that
      // can't be loaded.
      // Also filter to currently selected subplot or no
      // filter if core square is selected.
      // console.log(squareImages)

      squareImagesMod = squareImages
        .filter((img, i) => data[i].value)
        .filter(img => img.image_type === imageType)
        .filter(img => plotName !== indiciaData.npmsCoreSquareDetails.gr ? img.plot === plotName : true)
        .map(img => {return {...img}})

      // If squareImagesMod is empty, set to standard no images image
      // otherwise gallery won't clear.
      if (!squareImagesMod.length) {
        squareImagesMod = [{
          src: '/modules/custom/npms_vis/images/no-image.jpg',
          thumb: '/modules/custom/npms_vis/images/no-image.jpg',
          caption: ''
        }]
      }
      //squareGallery.updateSlides(squareImagesMod, squareGallery.index)
      squareGallery.updateSlides(squareImagesMod, 0)
      displayCaption(0)
    })
  }

  function displayCaption(i) {
    // console.log(i)
    // console.log(squareImagesMod)
    if (squareImagesMod.length) {
      let img = squareImagesMod[i]
      $('#gal-cap-div').html(img.caption)
    } else {
      $('#gal-cap-div').html('')
    }
  }

  function dateSort(a,b) {
    da = new Date(a.created_on)
    db = new Date(b.created_on)
    return da-db
  }
})(jQuery)