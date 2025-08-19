jQuery(document).ready(function($) {

  let mapData, conservationData
  let overviewmap, zoommap, brcyearly, brcphenology
  const urlParams = new URLSearchParams(window.location.search)
  const mapArea = sessionStorage.getItem('bas-atlas-map-area')
  const vcId = mapArea ? Number(mapArea.split('-')[0]) : 0
  const vcCode = mapArea ? mapArea.split('-')[1] : ''
  const baseRatio = 0.8644 // Based on aspect ration for British atlas map with insets
  console.log('vcId', vcId, 'vcCode', vcCode)

  // Add click handler to taxon group selection and set initial value
  const currentTaxonGroup = urlParams.get('tg') ? urlParams.get('tg') : 'spider'
  $("#taxon-list-selection").val(currentTaxonGroup)
  $("#taxon-list-selection").on('change', function() {
    // Get selected value
    const currentTaxonGroup = $('#taxon-list-selection').find(":selected").val()
    //sessionStorage.setItem('bas-atlas-taxon-group', currentTaxonGroup)
    // Submit form
    $('#taxon-search-form #tg').val(currentTaxonGroup)
    document.forms["taxon-search-form"].submit()
  })

  // Add click handler to species/group switch as set initial value
  const currentSpgrp = sessionStorage.getItem('bas-atlas-spgrp') ? sessionStorage.getItem('bas-atlas-spgrp') : 'species'
  $(`#switch-${currentSpgrp}`).prop('checked', true)
  if (currentSpgrp === 'species') {
    $('#ctrl-wrap-occurrence-taxa_taxon_list_id').show()
  } else {
    $('#ctrl-wrap-occurrence-taxa_taxon_list_id').hide()
  }
  $('input[name="species-group-switch"]').change(function() {
    const spgrp = $('input[name="species-group-switch"]:checked').val()
    sessionStorage.setItem('bas-atlas-spgrp', spgrp)
    if (spgrp === 'species') {
      $('#ctrl-wrap-occurrence-taxa_taxon_list_id').show()
    } else {
      $('#ctrl-wrap-occurrence-taxa_taxon_list_id').hide()
    }
  })

  // Map type constants
  let mapTypes = [
    {val: 'standard', caption: 'Standard atlas map', fn: mapStandard},
    {val: 'timeslice', caption: 'Occurrence by year', fn: mapDataTimeBanded},
    {val: 'tetradfreq', caption: 'Tetrad frequency', fn: mapTetradFrequency},
    {val: 'density', caption: 'Record density', fn: mapDataRecDensity}
  ]

  mapTypes = mapTypes.filter(t => t.val !== 'tetradfreq' || vcCode === '')
  let mapTypesSel = {}
  let mapTypesKey

  window.indiciaFns.speciesDetailsSub = () => {

    // VC area
    const mapArea = sessionStorage.getItem('bas-atlas-map-area')
    const vcId = mapArea ? Number(mapArea.split('-')[0]) : 0
    $('#taxon-search-form #vc').val(vcId)

    // Taxon group
    const taxonListType = $('#taxon-list-selection').find(":selected").val()
    $('#taxon-search-form #tg').val(taxonListType)

    // Dataset
    const srs = $('#dataset-srs').prop('checked') ? '1' : '0'
    const irec = $('#dataset-irec').prop('checked') ? '1' : '0'
    const inat = $('#dataset-inat').prop('checked') ? '1' : '0'

    // Taxon list id
    let ttlid
    if ($('input[name="species-group-switch"]:checked').val() === 'species') {
      ttlid = $('#occurrence\\:taxa_taxon_list_id').val()
    } else {
      // If searching on a group, set the ttlid to the group
      ttlid = 'grp'
    }

    if (ttlid === '') {
      // No taxon selected, so warn user and do not submit.
      $('#dataset-warning').html(`You must first select a taxon.`)
    } else if  (srs === '0' && irec === '0' && inat === '0') {
      // No datasets selected, so warn user and do not submit.
      $('#dataset-warning').html(`You must first specify at least one dataset.`)
    } else {
      $('#dataset-warning').html('')
      $('#taxon-search-form #taxa_taxon_list_id').val(ttlid)
      $('#taxon-search-form #ds').val(`${srs}${irec}${inat}`)
      document.forms["taxon-search-form"].submit()
    }
  }

  $(window).resize(function() {
    resizeTabs()
    resizeZoomMap()
  })

  indiciaFns.createAtlas = function () {

    console.log('indiciaData.basAtlas', indiciaData.basAtlas)

    // Initialise taxon selection control to be same as previous submission
    $('#occurrence\\:taxa_taxon_list_id\\:taxon').val(indiciaData.basAtlas.taxon.preferredPlain)
    $('#occurrence\\:taxa_taxon_list_id').val(indiciaData.basAtlas.taxon.taxaTaxonListId)

    // Filter the map types based on page parameters
    const paramMapTypes = indiciaData.basAtlas.map.mapTypes.replace(/\s+/g, ' ').split(' ')
    mapTypes = mapTypes.filter(mt => paramMapTypes.includes(mt.val))
    // Create constants for initialising maps
    mapTypes.forEach(mt => {
      mapTypesSel[mt.val] = mt.fn
    })
    mapTypesKey = sessionStorage.getItem('bas-atlas-map-type') ? sessionStorage.getItem('bas-atlas-map-type') : mapTypes[0].val
    if (mapTypesKey === 'tetradfreq' && vcCode) {
      mapTypesKey = mapTypes[0].val
    }

    // Create tabbed display
    const paramOptionalTabs = indiciaData.basAtlas.other.tab_types.replace(/\s+/g, ' ').split(' ')
    let tabs = [
      {
        tab: 'overview',
        caption: 'Overview',
        controlid: 'ctrl-map',
        optional: false,
      },
      {
        tab: 'zoom',
        caption: 'Zoomable',
        controlid: 'ctrl-map',
        optional: false,
      },
      {
        tab: 'account',
        caption: 'Account',
        controlid: null,
        optional: false,
      },
      {
        tab: 'conservation',
        caption: 'Designations',
        controlid: null,
        optional: false,
      },
      {
        tab: 'temporal',
        caption: 'Temporal',
        controlid: null,
        optional: true,
      },
      {
        tab: 'dev',
        caption: 'Dev',
        controlid: null,
        optional: true,
      }
    ].filter(t => !t.optional || paramOptionalTabs.includes(t.tab))

    createTabs(tabs)
    createAtlasMap()
    createZoomMap()
    createAtlasControls()
    createAccount()
    createConservation()
    createTemporal()

    resizeTabs()
    const currentTab = sessionStorage.getItem('bas-atlas-tab') ? sessionStorage.getItem('bas-atlas-tab') : 'overview'
    showHideMapControls(currentTab)

    // TODO for dev only
    $('#atlas-tab-dev').html(`tvk: <span style="font-size: 22">${indiciaData.basAtlas.taxon.externalKey}</span>`)
  }

  function createTabs(tabs) {
    //https://getbootstrap.com/docs/3.4/javascript/#tabs
    const $ul = $('<ul class="nav nav-tabs" role="tablist">').appendTo($('#bas-atlas-tabs'))

    // Tabs
    const currentTab = sessionStorage.getItem('bas-atlas-tab') ? sessionStorage.getItem('bas-atlas-tab') : 'overview'
    tabs.forEach((t,i) => {
      const $li = $('<li role="presentation">').appendTo($ul)
      //if (i===0) {
      if (t.tab === currentTab) {
        $li.addClass('active')
      }
      const $a = $(`<a role="tab" data-toggle="tab" href="#atlas-tab-${t.tab}" aria-controls="atlas-tab-${t.tab}" data-tab="${t.tab}">`).appendTo($li)
      $a.text(t.caption ? t.caption : t.tab)

      // Selected tab
      $a.on('shown.bs.tab', function (e) {
        // e.target // newly activated tab
        // e.relatedTarget // previous active tab
        showHideMapControls($(e.target).attr('data-tab'))
        sessionStorage.setItem('bas-atlas-tab', $(e.target).attr('data-tab'))
      })
    })

    // Tab panels
    const $div = $('<div class="tab-content">').appendTo($('#bas-atlas-tabs'))
    tabs.forEach((t,i) => {
      const $tdiv = $(`<div role="tabpanel" class="tab-pane" id="atlas-tab-${t.tab}">`).appendTo($div)
      //if (i===0) {
      if (t.tab === currentTab) {
        $tdiv.addClass('active')
      }
    })

    // Tab panel activated event
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
      e.target // newly activated tab
      e.relatedTarget // previous active tab
    })
  }

  function createAtlasControls() {

    // Enhance data selection controls
    createDatasetCheckboxes()

    // Map controls
    const $ctrlMap = $(`<div id="ctrl-map">`).appendTo($('#bas-atlas-other-controls'))
    $ctrlMap.attr('id', `ctrl-map`)

    // Map area selector
    // VC select control created by PHP but not populated
    // since easier to do here.
    $selMapArea = $('#map-area-selector')
    $selMapArea.addClass('form-control')
    $selMapArea.on('change', function() {
      // Get selected value
      const mapArea = $('#map-area-selector').find(":selected").val()
      // Store value in local storage
      sessionStorage.setItem('bas-atlas-map-area', mapArea)
    })
    let vcs = indiciaData.basAtlas.other.vcs
      .filter(vc => !vc.code.startsWith('H'))
      .sort((a,b) => Number(a.code)-(Number(b.code)))
    vcs.pop()
    vcs = [{id: 0, code: '', name: 'National atlas'}, ...vcs] // Don't include CI
    vcs.forEach(vc => {
      const $opt = $('<option>')
      $opt.attr('value', `${vc.id}-${vc.code}`)
      $opt.html(`${vc.code ? 'VC' : ''}${vc.code} ${vc.name}`).appendTo($selMapArea)
      if (sessionStorage.getItem('bas-atlas-map-area') === `${vc.id}-${vc.code}`) {
        $opt.attr('selected', 'selected')
      }
    })

    // Map type selector
    const $selMapType = $('<select>').appendTo($ctrlMap)
    $selMapType.attr('id', 'map-type-selector')
    $selMapType.addClass('form-control')
    $selMapType.on('change', function() {
      // Get selected value
      const mapType = $('#map-type-selector').find(":selected").val()
      // Store value in local storage
      sessionStorage.setItem('bas-atlas-map-type', mapType)
      // Show/hide supplementary controls
      if (mapType === 'timeslice') {
        $('#timeslice-controls').show()
      } else {
        $('#timeslice-controls').hide()
      }

      overviewmap.setMapType(mapType)
      overviewmap.redrawMap()
      overviewmap.showBusy(false)
      zoommap.setMapType(mapType)
      zoommap.redrawMap()
    })

    mapTypes.forEach(function(t){
      const $opt = $('<option>')
      $opt.attr('value', t.val)
      $opt.html(t.caption).appendTo($selMapType)
      if (sessionStorage.getItem('bas-atlas-map-type') === t.val) {
        $opt.attr('selected', 'selected')
      }
    })

    // Timeslice controls
    createTimeSliceControls($ctrlMap, 'timeslice-controls')

    // Map backdrop control
    createMapBackdropControl($ctrlMap)

    // Insets control
    if (!vcCode) {
      createInsetsControl($ctrlMap)
    }

    // Create grid control
    createGridControl($ctrlMap)

    // Create boundary control
    createBoundaryControl($ctrlMap)

    // Dot shape control
    createDotShapeControl($ctrlMap)

    // Opacity control
    createDotOpacityControl($ctrlMap)

    // Download control
    createDownloadControl($ctrlMap)
  }

  function createDatasetCheckboxes() {

    const $div = $('<div>').appendTo($('#bas-atlas-species'))
    $div.attr('id', 'dataset-cbs')
    $div.css('display', 'inline-block')

    const $divWarning = $('<div>').appendTo($('#bas-atlas-species'))
    $divWarning.attr('id', 'dataset-warning')

    checkbox('srs', 'SRS')
    checkbox('irec', 'iRec')
    checkbox('inat', 'iNat')

    function checkbox(type, caption) {

      let checked = sessionStorage.getItem(`bas-atlas-dataset-${type}`)
      if (checked === null) {
        checked = type === 'srs' ? true : false
      } else {
        checked = checked === 'true'
      }

      const $dcb = $('<div>').appendTo($div)
      $dcb.css('display', 'inline-block')

      const $cb = $('<input type="checkbox">').appendTo($dcb)
      $cb.attr('id', `dataset-${type}`)
      $cb.prop('checked', checked)
      const $label = $('<label>').appendTo($dcb)
      $label.attr('for', `dataset-${type}`)
      $label.text(caption)
      $cb.on('click',  () => {
        sessionStorage.setItem(`bas-atlas-dataset-${type}`, $(`#dataset-${type}`).is(':checked'))
      })
    }
  }

  function createTimeSliceControls($ctrlMap, id) {

    const $div0 = $('<div>').appendTo($ctrlMap)
    $div0.attr('id', id)

    const $div1 = $('<div>').appendTo($div0)
    $div1.css('margin-top', '0.5em')
    const $label = $('<div>').appendTo($div1)
    $label.css('display', 'inline-block')
    $label.css('margin-left', '0.5em')
    $label.text('Thresholds:')

    const $thresh1 = $('<input>').appendTo($div1)
    $thresh1.attr('type', 'number')
    $thresh1.attr('id', `timeslice-thresh1`)
    $thresh1.attr('value', sessionStorage.getItem('bas-atlas-timeslice-thresh1') ? sessionStorage.getItem('bas-atlas-timeslice-thresh1') : indiciaData.basAtlas.map.thresh1)
    $thresh1.css('width', '4em')
    $thresh1.css('margin-left', '0.5em')
    $thresh1.on('change', function() {
      const thresh1 = $(`#timeslice-thresh1`).val()
      // Store value in session storage
      sessionStorage.setItem('bas-atlas-timeslice-thresh1', thresh1)
      // Redraw map
      overviewmap.redrawMap()
      overviewmap.showBusy(false)
      zoommap.redrawMap()
    })

    const $thresh2 = $('<input>').appendTo($div1)
    $thresh2.attr('type', 'number')
    $thresh2.attr('id', `timeslice-thresh2`)
    $thresh2.attr('value', sessionStorage.getItem('bas-atlas-timeslice-thresh2') ? sessionStorage.getItem('bas-atlas-timeslice-thresh2') : indiciaData.basAtlas.map.thresh2)
    $thresh2.css('width', '4em')
    $thresh2.css('margin-left', '0.5em')
    $thresh2.on('change', function() {
      const thresh2 = $(`#timeslice-thresh2`).val()
      // Store value in session storage
      sessionStorage.setItem('bas-atlas-timeslice-thresh2', thresh2)
      // Redraw map
      overviewmap.redrawMap()
      overviewmap.showBusy(false)
      zoommap.redrawMap()
    })

    const $sel = $('<select>').appendTo($div0)
    $sel.css('margin-top', '0.5em')
    $sel.attr('id', `timeslice-order-selector`)
    $sel.addClass('form-control')
    $sel.on('change', function() {
      // Get selected value
      const order = $(`#timeslice-order-selector`).find(":selected").val()
      // Store value in session storage
      sessionStorage.setItem('bas-atlas-timeslice-order', order)
      // Redraw map
      overviewmap.redrawMap()
      overviewmap.showBusy(false)
      zoommap.redrawMap()
    })

    const scliceTypes = [
      {
        id: 'recent',
        text: 'Recent on top'
      },
      {
        id: 'oldest',
        text: 'Oldest on top'
      }
    ]
    scliceTypes.forEach(function(t,i){
      const $opt = $('<option>')
      $opt.attr('value', t.id)
      $opt.html(t.text).appendTo($sel)
      if (sessionStorage.getItem('bas-atlas-timeslice-order') && sessionStorage.getItem('bas-atlas-timeslice-order') === t.id) {
        $opt.attr('selected', 'selected')
      } else if (!sessionStorage.getItem('bas-atlas-timeslice-order') && i === 0) {
        $opt.attr('selected', 'selected')
      }
    })

    if (sessionStorage.getItem('bas-atlas-map-type') !== 'timeslice') {
      $div0.hide()
    }
  }

  function createDotShapeControl($ctrlMap) {

    const currentVal = sessionStorage.getItem('bas-atlas-dot-shape') ? sessionStorage.getItem('bas-atlas-dot-shape') : 'circle'

    const $dotTypeDiv=$('<div>').appendTo($ctrlMap)
    $dotTypeDiv.attr('id', 'dot-type-control')
    makeRadio(`dot-type`, 'Circles', 'circle', currentVal === 'circle', 'bas-atlas-dot-shape', $dotTypeDiv, dotShapeChanged)
    makeRadio(`dot-type`, 'Squares', 'square', currentVal === 'square', 'bas-atlas-dot-shape', $dotTypeDiv, dotShapeChanged)

    function dotShapeChanged() {
      // Redraw map
      overviewmap.redrawMap()
      overviewmap.showBusy(false)
      zoommap.redrawMap()
    }
  }

  function createDotOpacityControl($ctrlMap) {

    const $div = $('<div>').appendTo($ctrlMap)

    const $label = $('<label>').appendTo($div)
    $label.attr('for', `opacity-slider`)
    $label.text('Opacity:')
    $label.css('margin', '0 0.5em 0.5em 0.5em')
    $label.css('vertical-align', 'middle')
    $label.css('font-weight', '500')
    $label.css('width', '50px')

    const $slider = $('<input>').appendTo($div)
    $slider.attr('type', 'range')
    $slider.attr('min', 0)
    $slider.attr('max', 1)
    $slider.attr('step', 0.1)
    $slider.attr('value', sessionStorage.getItem('bas-atlas-dot-opacity') ? sessionStorage.getItem('bas-atlas-dot-opacity') : 1)
    $slider.attr('id', 'opacity-slider')
    $slider.css('display', 'inline-block')
    $slider.css('width', '100px')
    $slider.on('input', function() {
      // Get selected value
      const opacity = $(`#opacity-slider`).val()
      // Store value in session storage
      sessionStorage.setItem('bas-atlas-dot-opacity', opacity)
      // Redraw map
      overviewmap.redrawMap()
      overviewmap.showBusy(false)
      zoommap.redrawMap()
    })
  }

  function createMapBackdropControl($ctrlMap) {
    const rasterRoot = '/sites/default/files/images/'
    const currentVal = sessionStorage.getItem('bas-atlas-backdrop') ? sessionStorage.getItem('bas-atlas-backdrop') : 'none'
    if (currentVal !== 'none') {
      overviewmap.basemapImage(currentVal, true, rasterRoot + currentVal + '.png', rasterRoot + currentVal + '.pgw')
    }

    // Backdrops
    const backdrops = [
      {
        caption: 'No backdrop',
        val: 'none'
      },
      {
        caption: 'Colour elevation',
        val: 'colour_elevation'
      },
      {
        caption: 'Grey elevation',
        val: 'grey_elevation_300'
      },
    ]

    // Main type selector
    const $sel = $('<select>').appendTo($ctrlMap)
    $sel.css('margin-top', '0.5em')
    $sel.attr('id', `backdrop-selector`)
    $sel.addClass('form-control')
    $sel.addClass('overview-only')

    $sel.on('change', function() {

      // Remove all backdrops
      backdrops.forEach(function(b){
        if (b.val !== 'none') {
          overviewmap.basemapImage(b.val, false, rasterRoot + b.val + '.png', rasterRoot + b.val + '.pgw')
        }
      })
      // Display selected backdrop
      const backdrop = $(this).val()
      sessionStorage.setItem('bas-atlas-backdrop', backdrop)

      if (backdrop !== 'none') {
        overviewmap.basemapImage(backdrop, true, rasterRoot + backdrop + '.png', rasterRoot + backdrop + '.pgw')
      }
    })
    backdrops.forEach(function(b){
      const $opt = $('<option>')
      $opt.attr('value', b.val)
      $opt.html(b.caption).appendTo($sel)
      if (currentVal === b.val) {
        $opt.attr('selected', 'selected')
      }
    })
  }

  function createInsetsControl ($ctrlMap) {

    const currentVal = sessionStorage.getItem('bas-atlas-inset') ? sessionStorage.getItem('bas-atlas-inset') : 'BI4'
    overviewmap.setTransform(currentVal)

    const insets = [
      {
        caption: 'No insets',
        val: 'BI1'
      },
      {
        caption: 'Channel Isles inset',
        val: 'BI2'
      },
      {
        caption: 'Northern and Channel Isles inset',
        val: 'BI4'
      },
    ]

    // Main type selector
    const $sel = $('<select>').appendTo($ctrlMap)
    $sel.css('margin-top', '0.5em')
    $sel.attr('id', `inset-selector`)
    $sel.addClass('form-control')
    $sel.addClass('overview-only')

    $sel.on('change', function() {

      insetType = $(this).val()
      sessionStorage.setItem('bas-atlas-inset', insetType)
      overviewmap.setTransform(insetType)

      resizeLegend()
    })

    insets.forEach(function(i){
      const $opt = $('<option>')
      $opt.attr('value', i.val)
      if (currentVal === i.val) {
        $opt.attr('selected', 'selected')
      }
      $opt.html(i.caption).appendTo($sel)
    })
  }

  function createGridControl($ctrlMap) {

    const currentVal = sessionStorage.getItem('bas-atlas-grid') ? sessionStorage.getItem('bas-atlas-grid') : 'solid'
    overviewmap.setGridLineStyle(currentVal)

    const gridStyles = [
      {
        caption: 'Solid grid lines',
        val: 'solid'
      },
      {
        caption: 'Dashed grid lines',
        val: 'dashed'
      },
      {
        caption: 'No grid lines',
        val: 'none'
      }
    ]

    // Main type selector
    const $sel = $('<select>').appendTo($ctrlMap)
    $sel.css('margin-top', '0.5em')
    $sel.attr('id', `grid-selector`)
    $sel.addClass('form-control')
    $sel.addClass('overview-only')

    $sel.on('change', function() {
      const gridStyle = $(this).val()
      sessionStorage.setItem('bas-atlas-grid', gridStyle)
      overviewmap.setGridLineStyle(gridStyle)
    })

    gridStyles.forEach(function(s){
      const $opt = s.selected  ? $('<option>') : $('<option>')
      $opt.attr('value', s.val)
      if (currentVal === s.val) {
        $opt.attr('selected', 'selected')
      }
      $opt.html(s.caption).appendTo($sel)
    })
  }

  function createBoundaryControl($ctrlMap) {

    const currentVal = sessionStorage.getItem('bas-atlas-boundaries') ? sessionStorage.getItem('bas-atlas-boundaries') : 'none'
    setBoundary(currentVal)

    const boundaries = [
      {
        caption: 'Country boundaries',
        val: 'country'
      },
      {
        caption: 'Vice-county boundaries',
        val: 'vc'
      },
      {
        caption: 'No boundaries',
        val: 'none'
      }
    ]

    // Main type selector
    const $sel = $('<select>').appendTo($ctrlMap)
    $sel.css('margin-top', '0.5em')
    $sel.attr('id', `boundary-selector`)
    $sel.addClass('form-control')

    $sel.on('change', function() {
      boundaryType = $(this).val()
      sessionStorage.setItem('bas-atlas-boundaries', boundaryType)
      setBoundary(boundaryType)
    })

    boundaries.forEach(function(b){
      const $opt = b.selected  ? $('<option>') : $('<option>')
      $opt.attr('value', b.val)
      if (currentVal === b.val) {
        $opt.attr('selected', 'selected')
      }
      $opt.html(b.caption).appendTo($sel)
    })

    function setBoundary(boundaryType) {
      if (boundaryType === 'none') {
        if (!vcCode){
          overviewmap.setVcLineStyle('none')
          overviewmap.setCountryLineStyle('none')
          overviewmap.setBoundaryColour('#7C7CD3')
        }
        zoommap.setShowVcs(false)
        zoommap.setShowCountries(false)
      } else if (boundaryType === 'vc') {
        if (!vcCode) {
          overviewmap.setVcLineStyle('')
          overviewmap.setCountryLineStyle('none')
          overviewmap.setBoundaryColour('white')
        }
        zoommap.setShowVcs(true)
        zoommap.setShowCountries(false)
      } else if (boundaryType === 'country') {
        if (!vcCode) {
          overviewmap.setVcLineStyle('none')
          overviewmap.setCountryLineStyle('')
          overviewmap.setBoundaryColour('white')
        }
        zoommap.setShowVcs(false)
        zoommap.setShowCountries(true)
      }
    }
  }

  function createDownloadControl($ctrlMap) {

    const $downloadDiv=$('<div>').appendTo($ctrlMap)
    $downloadDiv.attr('id', 'download-controls')
    $downloadDiv.addClass('overview-only')

    if (indiciaData.basAtlas.map.download) {
      $downloadDiv.css('margin-top', '1em')
      const $downloadButton = $('<button>').appendTo($downloadDiv)
      $downloadButton.text('Download')
      $downloadButton.on('click', downloadMapImage)

      const currentDownloadType = sessionStorage.getItem('bas-atlas-download-type') ? sessionStorage.getItem('bas-atlas-download-type') : 'png'

      makeRadio(`download-type`, 'SVG', 'svg', currentDownloadType === 'svg', 'bas-atlas-download-type', $downloadDiv, downloadTypeChanged)
      makeRadio(`download-type`, 'PNG', 'png', currentDownloadType === 'png', 'bas-atlas-download-type', $downloadDiv, downloadTypeChanged)

      function downloadTypeChanged() {
        // Do nothing
      }
    }
  }

  function downloadMapImage() {
    let info = null

    const dInfo = indiciaData.basAtlas.map.downloadinfo
    const dText = indiciaData.basAtlas.map.downloadtext

    if (dInfo || dText) {
      info = {margin: 10,text: ''}
    }

    if (dInfo) {
      let taxon = markUp(indiciaData.basAtlas.taxon.preferred)

      if (indiciaData.basAtlas.taxon.defaultCommonName) {
        taxon = `${taxon} (${indiciaData.basAtlas.taxon.defaultCommonName})`
      }

      // Create a string indicating selected datasets. Note that this should be
      // constructed from the URL ds param - not the checkboxes as the user could
      // alter these before creating the image.
      const ds = urlParams.get('ds')
      const dsa = ['SRS', 'iRecord', 'iNaturalist'].filter((d,i) => ds.substring(i).startsWith('1'))
      let datasets = dsa.reduce((a,d,i) => {
        if (a) {
          if (i === dsa.length-1) {
            a = `${a} and ${d}`
          } else {
            a = `${a}, ${d}`
          }
        } else {
          a = d
        }
        return a
      }, '')

      let vc = ''
      if (vcCode) {
        vc = ` and vice county ${$("#map-area-selector option:selected").text()}`
      }

      info.text = `
        Distribution map for ${taxon}${vc}. Generated at ${location.href} on ${getDateString()}.
        BRC Indicia datasets used to generate these data: ${datasets}.
      `
      if (indiciaData.basAtlas.data.exclude_rejected) {
        info.text = `${info.text} Rejected records are excluded from the map.`
      } else {
        info.text = `${info.text} Rejected records are included on the map.`
      }
      if (indiciaData.basAtlas.data.exclude_unverified) {
        info.text = `${info.text} Unverified records are excluded from the map.`
      } else {
        info.text = `${info.text} Unverified records are included on the map.`
      }
    }
    if (dText) {
      info.text = `${info.text} ${dText}`
    }

    const asSvg = $("input[name='bas-atlas-control-download-type']:checked").val() ==='svg'
    overviewmap.saveMap(asSvg, info, 'map')
  }

  function makeRadio(id, label, val, checked, ss, $container, callback) {

    const $div = $('<div>').appendTo($container)
    $div.css('display', 'inline-block')
    $div.css('margin-left', '0.5em')
    $div.attr('class', 'radio')
    const $label = $('<label>').appendTo($div)
    const $radio = $('<input>').appendTo($label)
    const $span = $('<span>').appendTo($label)
    $span.text(label)
    $span.css('padding', '0 10px 0 5px')
    $radio.attr('type', 'radio')
    $radio.attr('name', `bas-atlas-control-${id}`)
    $radio.attr('value', val)
    //$radio.css('margin-left', 0)
    if (checked) $radio.prop('checked', true)

    $radio.change(function (e) {
      // Store value in local storage
      sessionStorage.setItem(ss, val)
      // Callbacks
      callback()
    })
  }

  async function createConservation() {
    let html = ''
    $('#atlas-tab-conservation').html('')

    const ptvk = indiciaData.basAtlas.taxon.externalKey
    if (ptvk) {

      if (!conservationData) {
        const file = `${indiciaData.basAtlas.other.conservation_csv}`
        try {
          conservationData = await d3.csv(file)
        } catch(e) {
          $('#atlas-tab-account').html(`There was an error JNSS conservation CSV: '${file}'.`)
        }
      }
      taxonConservation = conservationData.filter(cd => cd['Recommended taxon version'] === ptvk)

      //console.log('taxonConservation', taxonConservation)

      if (!taxonConservation.length) {
        const $noDesignations = $('<p>').appendTo($('#atlas-tab-conservation'))
        $noDesignations.text(`There are no reported conservation designations for this taxon.`)
      }

      taxonConservation.forEach((tc,i) => {
        const $divMain = $('<div>').appendTo($('#atlas-tab-conservation'))
        $divMain.addClass('div-desgination')

        const $title = $('<div>').appendTo($divMain)
        $title.addClass('div-title')
        const $des = $('<span>').appendTo($title)
        $des.addClass('span-designation')
        $des.text(tc['Designation'])
        const $desabbrv = $('<span>').appendTo($title)
        $desabbrv.addClass('span-designation-abbrv')
        $desabbrv.text(` (${tc['Designation abbreviation']}) `)

        const $showhide = $('<span>').appendTo($title)
        $showhide.addClass('span-showhide')
        $showhide.text('[Show details]')

        const $details = $('<div>').appendTo($divMain)
        $details.attr('id', `div-details-${i}`)
        $details.hide()

        $showhide.on('click', function() {
          if ($details.css('display') === 'none') {
            $details.show()
            $showhide.text('[Hide details]')
          } else {
            $details.hide()
            $showhide.text('[Show details]')
          }
        })

        const $table = $('<table>').appendTo($details)
        $table.attr('id', 'table-conservation')

        addRow($table, tc, 'Source')
        addRow($table, tc, 'Source description')
        addRow($table, tc, 'URL source')
        addRow($table, tc, 'designation description')
        //addRow($table, tc, 'Date designated')
        addRow($table, tc, 'Reporting category')
        addRow($table, tc, 'Criteria description')
        addRow($table, tc, 'IUCN version')
        addRow($table, tc, 'Comments')
      })
    }
    function addRow($table, tc, field) {
      if (tc[field]) {
        const $row = $('<tr>').appendTo($table)
        const $td1 = $('<td>').appendTo($row)
        $td1.addClass('row-conservation-field')
        $td1.text(field.charAt(0).toUpperCase() + field.slice(1))
        const $td2 = $('<td>').appendTo($row)
        $td2.addClass('row-conservation-info')
        if (field.startsWith('URL')) {
          $td2.html(`<a href="${tc[field]}" target="_blank">${tc[field]}</a>`)
        } else {
          $td2.text(tc[field])
        }
      }
    }
  }

  function createAccount() {

    $('#atlas-tab-account').html('')
    const preferredPlain = indiciaData.basAtlas.taxon.preferredPlain
    let firstFile
    if (preferredPlain) {

      let matchName
      if (preferredPlain.endsWith('sensu stricto')) {
        matchName = `${preferredPlain.substring(0, preferredPlain.length-14)} sens. str.`
      } else if (preferredPlain.endsWith('sensu lato')) {
        matchName = `${preferredPlain.substring(0, preferredPlain.length-11)} sens. lat.`
      } else {
        matchName = preferredPlain
      }
      getAccount(matchName)
      async function getAccount(name) {
        const file = `${indiciaData.basAtlas.other.accounts_location}/${name}.txt`
        if (!firstFile) {
          firstFile = file
        }
        try {
          ret = await $.ajax({
            url: file,
            contentType: "application/text;charset=utf-8",
            cache: false
          }).then(data => {
            $('#atlas-tab-account').html(marked.parse(data))
          })
        } catch(e) {
          // If name is just two tokens, try adding sens. str.
          const sname = name.split(' ')
          if (sname.length == 2) {
            const name2 =`${name} sens. str.`
            getAccount(name2)
          } else {
            // If the name includes any of the following, output fixed message:
            // - sensu lato
            // - sens. lat.
            // - sens.lat.
            // - in part
            // At request from Richard Gallon 05/11/2024
            if (name.includes('sensu lato') || name.includes('sens. lat.') || name.includes('sens.lat.') || name.includes(' in part')) {
              $('#atlas-tab-account').html('For the account, please refer to the two segregate species involved in this aggregate.')
            } else {
              $('#atlas-tab-account').html(`There was an error reading file '${firstFile}'.`)
            }
          }
        }
      }
    }
  }

  function createZoomMap () {

    const basemapConfigs = [
      {
        name: 'Open Street Map',
        type: 'tileLayer',
        selected: true,
        url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        opts: {
          maxZoom: 19,
          attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }
      },
      {
        name: 'Open Topo Map',
        type: 'tileLayer',
        selected: false,
        url: 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
        opts: {
          maxZoom: 17,
          attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)'
        }
      },
    ]

    if (typeof(brcatlas) !== "undefined") {
      // Create zoom map
      zoommap = brcatlas.leafletMap({
        selector: "#atlas-tab-zoom",
        mapTypesSel: mapTypesSel,
        mapTypesKey: mapTypesKey,
        basemapConfigs: basemapConfigs
      })
    }

    if (vcCode) {
      // If working with VC data, centre map on VC
      gjsonFile = `/sites/default/files/atlas/geo/vc-gb-simp-100/vc-gb-${vcCode}.geojson`
      let gjson = $.ajax({
        url: gjsonFile,
        cache: true,
        async:  false
      }).responseText
      gjson = JSON.parse(gjson)
      const props = gjson.features[0].properties
      const easting = props.xmin+(props.xmax-props.xmin)/2
      const northing = props.ymin+(props.ymax-props.ymin)/2
      const gr = bigr.getGrFromCoords(easting, northing, 'gb', '', [1])
      const c = bigr.getCentroid(gr.p1, 'wg')
      const lon = c.centroid[0]
      const lat = c.centroid[1]
      zoommap.lmap.setView({lat: lat, lng: lon}, 9)
    }
  }

  function showHideMapControls(type) {
    if (type === 'zoom') {
      resizeZoomMap()
      $('.overview-only').hide()
    } else {
      $('.overview-only').show()
    }
    if (type === 'zoom' || type === 'overview') {
      $('#ctrl-map').show()
    } else {
      $('#ctrl-map').hide()
    }
    if (type === 'overview' && vcCode) {
      $('#boundary-selector').hide()
    } else {
      $('#boundary-selector').show()
    }
  }

  function createAtlasMap () {

    // Create atlas map
    if (typeof(brcatlas) !== "undefined") {
      const opts = {
        selector: "#atlas-tab-overview",
        height: indiciaData.basAtlas.map.mapheight,
        expand: true,
        transOptsControl: false,
        transOptsKey: 'BI4',
        mapTypesSel: mapTypesSel,
        mapTypesKey: mapTypesKey,
        seaFill: '#F7F7F7',
        captionId: 'brc-square-map-dot-details',
        legendInteractivity: indiciaData.basAtlas.map.legendmouse,
        highlightClass: 'brc-atlas-highlight-square-map',
        highlightStyle: indiciaData.basAtlas.map.highstyle,
        lowlightStyle: indiciaData.basAtlas.map.lowstyle,
      }

      // If a VC selcted, set the relevant options
      if (vcCode) {
        gjsonFile = `/sites/default/files/atlas/geo/vc-gb-simp-100/vc-gb-${vcCode}.geojson`
        grid = `/sites/default/files/atlas/geo/vc-gb-hectad-grids/vc-gb-${vcCode}-hectads.geojson`
        let gjson = $.ajax({
          url: gjsonFile,
          cache: true,
          async:  false
        }).responseText
        gjson = JSON.parse(gjson)
        const props = gjson.features[0].properties
        const buffer=10000
        opts.transOptsSel = {
          boundary: {
            id: 'boundary',
            bounds: {
              xmin: props.xmin - buffer,
              ymin: props.ymin - buffer,
              xmax: props.xmax + buffer,
              ymax: props.ymax + buffer
            }
          }
        }
        opts.transOptsKey = 'boundary'
        opts.boundaryGjson = gjsonFile
        opts.gridGjson = grid
      }
      overviewmap = brcatlas.svgMap(opts)
      if (indiciaData.basAtlas.taxon.preferred) {
        overviewmap.showBusy(true)
      }
    }
  }

  function resizeLegend() {
    // The global constant 'baseRatio' represents the
    // svg width/height ratio for the British all inset
    // map at which the legend size (at scale 0.8) is goog.
    // This routine is responsible for resizing and repositioning
    // the legend to keep it conistent in the face of the aspect
    // ratio of the maps changing due to inset changes or
    // area of interest changes (i.e. VC maps).

    // In addition, for some VC maps, the default position of
    // top left for the legend is no good - it overlaps the
    // map data, so here we have an array with custom positions.

    const vcLegend = [
      {no: 4, x: 440, y: 470},
      {no: 5, x: 650, y: -5},
      {no: 7, x: 430, y: 495},
      {no: 8, x: null, y: -5},
      {no: 11, x: null, y: -5},
      {no: 12, x: null, y: -5},
      {no: 14, x: 525, y: null},
      {no: 15, x: 420, y: 495},
      {no: 16, x: 20, y: 500},
      {no: 18, x: 630, y: 440},
      {no: 19, x: 550, y: 460},
      {no: 22, x: 460, y: null},
      {no: 23, x: null, y: 450},
      {no: 26, x: null, y: 470},
      {no: 27, x: 270, y: null},
      {no: 30, x: 270, y: null},
      {no: 31, x: null, y: -5},
      {no: 35, x: 270, y: null},
      {no: 38, x: 255, y: -5},
      {no: 40, x: null, y: -5},
      {no: 41, x: null, y: -5},
      {no: 42, x: null, y: -5},
      {no: 47, x: 405, y: 475},
      {no: 48, x: 385, y: 475},
      {no: 50, x: 405, y: null},
      {no: 51, x: 380, y: null},
      {no: 52, x: 1, y: -5},
      {no: 53, x: 360, y: null},
      {no: 54, x: 360, y: -5},
      {no: 56, x: null, y: -5},
      {no: 57, x: 220, y: null},
      {no: 58, x: 233, y: null},
      {no: 62, x: 360, y: null},
      {no: 63, x: 380, y: null},
      {no: 64, x: null, y: 460},
      {no: 65, x: null, y: -5},
      {no: 67, x: null, y: -5},
      {no: 72, x: null, y: 500},
      {no: 73, x: null, y: -5},
      {no: 74, x: null, y: 510},
      {no: 75, x: 260, y: null},
      {no: 76, x: null, y: 450},
      {no: 77, x: null, y: 480},
      {no: 81, x: null, y: 5},
      {no: 84, x: null, y: -5},
      {no: 86, x: 300, y: null},
      {no: 87, x: 500, y: -5},
      {no: 88, x: 480, y: -5},
      {no: 89, x: null, y: 480},
      {no: 92, x: null, y: -5},
      {no: 97, x: 480, y: -5},
      {no: 103, x: 360, y: -5},
      {no: 104, x: 230, y: -5},
      {no: 107, x: null, y: -5},
      {no: 108, x: 400, y: 440},
    ]

    let vcl
    if (vcCode) {
      vcl = vcLegend.find(vc => vc.no === Number(vcCode))
    }
    const x = vcl ? vcl.x : null
    const y = vcl ? vcl.y : null

    const $svg = $('#svgMap svg')
    const thisRatio = $svg.width()/$svg.height()
    // console.log('thisRatio', thisRatio)
    // console.log('baseRatio', baseRatio)
    // console.log('thisRatio/baseRatio', thisRatio / baseRatio)
    overviewmap.setLegendOpts({
      display: true,
      scale: 0.8 * thisRatio / baseRatio,
      x: x ? x : 10 * thisRatio / baseRatio,
      y: y ? y : 10 * thisRatio / baseRatio
    })
  }

  function resizeTabs() {
    // Drupal displays does funny tricks when display the right-hand bar (which I think should be
    // displayed on the atlas tab). It seems to float it for some screen sizes.
    let width
    if (window.innerWidth > 767) {
      width = $("#bas-atlas-main").width() - $("#bas-atlas-controls").width()
    } else {
      width = $("#bas-atlas-tabs").width()
    }
    $('.tab-pane').css('width', width)
  }

  function resizeZoomMap() {
    if (zoommap) {
      // Expected to simply be able to set the width to the width of the tab
      // containing the zoom map, but that does not always work because of the
      // way that drupal displays the right-hand bar (which I think should be
      // displayed on the atlas tab). It seems to float it for some screen sizes.
      // let width
      // if (window.innerWidth > 767) {
      //   width = $("#bas-atlas-main").width() - $("#bas-atlas-controls").width()
      // } else {
      //   width = $("#bas-atlas-tabs").width()
      // }

      let width = $('#atlas-tab-overview').width() // Any tab will do

      zoommap.setSize(width, indiciaData.basAtlas.map.mapheight)
      zoommap.invalidateSize()
    }
  }

  function createTemporal() {

    // Create yearly record accumulation chart
    if (typeof(brccharts) !== "undefined") {
      brcyearly = brccharts.yearly({
        selector: "#atlas-tab-temporal",
        legendFontSize: 14,
        data: [],
        metrics: [{ prop: 'n', label: 'Records per year', opacity: 1, colour: '#66b3ff'}],
        taxa: ['taxon'],
        minYear: 1970,
        maxYear: new Date().getFullYear(),
        width: 500,
        height: 300,
        perRow: 1,
        expand: true,
        showTaxonLabel: false,
        showLegend: false,
        margin: {left: 60, right: 0, top: 10, bottom: 20},
        axisLeftLabel: 'Records per year',
        axisLabelFontSize: 12
      })
    }

    // Create phenology chart
    if (typeof(brccharts) !== "undefined") {
      brcphenology = brccharts.phen1({
        selector: "#atlas-tab-temporal",
        taxa: ['taxon'],
        width: 500,
        height: 300,
        perRow: 1,
        expand: true,
        showTaxonLabel: false,
        showLegend: false,
        margin: {left: 60, right: 0, top: 10, bottom: 20},
        axisLeftLabel: 'Records per week',
        axisLabelFontSize: 12
      })
    }
  }

  // ES custom script definition for map
  indiciaFns.populateMap = function (el, sourceSettings, response) {

    console.log('ES mapping response', response)

    $('.page-header').html(indiciaData.basAtlas.taxon.preferred)

    const mapArea = $('#map-area-selector').find(":selected").val()
    const vcMap = mapArea.split('-')[0] !== '0'

    mapData = response.aggregations._rows.buckets
      .filter(function(s){return s.key[`location-grid_square-${vcMap?'2km':'10km'}-centre`]})
      .map(function(s) {
      const latlon = s.key[`location-grid_square-${vcMap?'2km':'10km'}-centre`].split(' ')
      const gr = bigr.getGrFromCoords(Number(latlon[0]), Number(latlon[1]), 'wg', '', [2000, 10000])
      return {
        gr: vcMap ? gr.p2000 : gr.p10000,
        recs: s.doc_count,
        minYear: new Date(s.minYear.value_as_string).getFullYear(),
        maxYear: new Date(s.maxYear.value_as_string).getFullYear(),
        tetrads: vcMap ? 0 : s.tetrads.value
      }
    })

    //console.log('mapData', JSON.parse(JSON.stringify(mapData)))

    // Turns out that sometimes more than one lat/lon combo is returned for a single square, so
    // can't just do a simple map. Need to reduce to single values for each square.
    // Also filter out any values with null squares
    mapData = mapData.filter(function(s){return s.gr}).reduce(function(a,s) {
      const existing = a.find(function(as){return as.gr === s.gr})
      if (existing) {
        existing.recs += s.recs
        existing.minYear = s.minYear < existing.minYear ? s.minYear : existing.minYear
        existing.maxYear = s.maxYear > existing.maxYear ? s.maxYear : existing.maxYear
        existing.tetrads = s.tetrads > existing.tetrads ? s.tetrads : existing.tetrads // Not perfect, but best we can do
      } else {
        a.push({gr: s.gr, recs: s.recs, minYear: s.minYear, maxYear: s.maxYear, tetrads: s.tetrads})
      }
      return a
    }, [])

    //console.log('mapData', JSON.parse(JSON.stringify(mapData)))

    overviewmap.redrawMap()
    overviewmap.showBusy(false)
    zoommap.redrawMap()

    resizeLegend()
  }

  // ES custom script for records by year
  indiciaFns.populateRecsByYearChart = function(el, sourceSettings, response) {

    //console.log('Yearly ES response', response)

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

    //console.log('Weekly ES response', response)

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

  function mapDataTimeBanded() {

    const colour1 = indiciaData.basAtlas.map.colour1
    const colour2 = indiciaData.basAtlas.map.colour2
    const colour3 = indiciaData.basAtlas.map.colour3
    const thresh1 = Number($('#timeslice-thresh1').val())
    const thresh2 = Number($('#timeslice-thresh2').val())

    function getBandColour(minYear, maxYear) {

      const priority = $(`#timeslice-order-selector`).find(":selected").val()

      const year = priority === 'recent' ? maxYear : minYear;
      if (year > thresh2) {
        return colour3;
      } else if (year > thresh1) {
        return colour2;
      } else {
        return colour1;
      }
    }

    function getKey(minYear, maxYear) {

      const priority = $(`#timeslice-order-selector`).find(":selected").val()
      var year = priority === 'recent' ? maxYear : minYear;
      if (year > thresh2) {
        return 'group3';
      } else if (year > thresh1) {
        return 'group2';
      } else {
        return 'group1';
      }
    }

    zoommap.setLegendOpts({
      display: true,
      scale: 0.8,
      x: 10,
      y: 10,
      width: 240,
      height: 90
    })

    return new Promise(function (resolve, reject) {

      // At this stage, there might be some records without a
      // resolved square (possibly outside UK?) so filter these out.
      var recs = mapData.filter(function(s){return s.gr}).map(function(s) {
        var minYear = s.minYear ? s.minYear : 'no info';
        var maxYear = s.maxYear ? s.maxYear : 'no info';
        return {
          gr: s.gr,
          id: s.gr,
          colour: getBandColour(s.minYear, s.maxYear),
          caption: 'Square: <b>' + s.gr + '</b>; Recs: <b>' + s.recs + '</b>; Earliest: <b>' + minYear + '</b>; Latest: <b>' + maxYear + '</b>',
          noCaption: 'Move mouse cursor over dot for info',
          legendKey: getKey(s.minYear, s.maxYear),
        };
      });

      var currentYear = new Date().getFullYear()
      var legendText1 = thresh1 + ' and before';
      var legendText2 = thresh2 === thresh1+1 ? thresh2 : (thresh1+1) + "-" + thresh2;
      var legendText3 = currentYear === thresh2+1 ? currentYear : (thresh2+1) + "-" + currentYear;

      const priority = $(`#timeslice-order-selector`).find(":selected").val()
      const shape = $('input[name="bas-atlas-control-dot-type"]:checked').val()
      const opacity = $(`#opacity-slider`).val()

      //console.log(shape, opacity)

      const lfText = priority === 'recent' ? 'latest' : 'first'
      const sqText = vcCode ? '2 km' : '10 km'
      const legendSizeFact = 0.8
      resolve({
        records: recs,
        precision: vcCode ? 2000 : 10000,
        size: 1,
        shape: shape,
        opacity: opacity,
        legend: {
          size: legendSizeFact,
          display: true,
          title: `Year of ${lfText} record in ${sqText} square`,
          shape: shape,
          opacity: opacity,
          precision: vcCode ? 2000 : 10000,
          x: 100,
          y: 100,
          lines: [
            {colour: colour1, text: legendText1, key: 'group1'},
            {colour: colour2, text: legendText2, key: 'group2'},
            {colour: colour3, text: legendText3, key: 'group3'}
          ]
        }
      })
    })
  }

  function mapDataRecDensity() {

    zoommap.setLegendOpts({
      display: true,
      scale: 0.8,
      x: 10,
      y: 10,
      width: 110,
      height: 90
    })

    return new Promise(function (resolve, reject) {

      var viridis = ["#440154","#440256","#450457","#450559","#46075a","#46085c","#460a5d","#460b5e","#470d60","#470e61","#471063","#471164","#471365","#481467","#481668","#481769","#48186a","#481a6c","#481b6d","#481c6e","#481d6f","#481f70","#482071","#482173","#482374","#482475","#482576","#482677","#482878","#482979","#472a7a","#472c7a","#472d7b","#472e7c","#472f7d","#46307e","#46327e","#46337f","#463480","#453581","#453781","#453882","#443983","#443a83","#443b84","#433d84","#433e85","#423f85","#424086","#424186","#414287","#414487","#404588","#404688","#3f4788","#3f4889","#3e4989","#3e4a89","#3e4c8a","#3d4d8a","#3d4e8a","#3c4f8a","#3c508b","#3b518b","#3b528b","#3a538b","#3a548c","#39558c","#39568c","#38588c","#38598c","#375a8c","#375b8d","#365c8d","#365d8d","#355e8d","#355f8d","#34608d","#34618d","#33628d","#33638d","#32648e","#32658e","#31668e","#31678e","#31688e","#30698e","#306a8e","#2f6b8e","#2f6c8e","#2e6d8e","#2e6e8e","#2e6f8e","#2d708e","#2d718e","#2c718e","#2c728e","#2c738e","#2b748e","#2b758e","#2a768e","#2a778e","#2a788e","#29798e","#297a8e","#297b8e","#287c8e","#287d8e","#277e8e","#277f8e","#27808e","#26818e","#26828e","#26828e","#25838e","#25848e","#25858e","#24868e","#24878e","#23888e","#23898e","#238a8d","#228b8d","#228c8d","#228d8d","#218e8d","#218f8d","#21908d","#21918c","#20928c","#20928c","#20938c","#1f948c","#1f958b","#1f968b","#1f978b","#1f988b","#1f998a","#1f9a8a","#1e9b8a","#1e9c89","#1e9d89","#1f9e89","#1f9f88","#1fa088","#1fa188","#1fa187","#1fa287","#20a386","#20a486","#21a585","#21a685","#22a785","#22a884","#23a983","#24aa83","#25ab82","#25ac82","#26ad81","#27ad81","#28ae80","#29af7f","#2ab07f","#2cb17e","#2db27d","#2eb37c","#2fb47c","#31b57b","#32b67a","#34b679","#35b779","#37b878","#38b977","#3aba76","#3bbb75","#3dbc74","#3fbc73","#40bd72","#42be71","#44bf70","#46c06f","#48c16e","#4ac16d","#4cc26c","#4ec36b","#50c46a","#52c569","#54c568","#56c667","#58c765","#5ac864","#5cc863","#5ec962","#60ca60","#63cb5f","#65cb5e","#67cc5c","#69cd5b","#6ccd5a","#6ece58","#70cf57","#73d056","#75d054","#77d153","#7ad151","#7cd250","#7fd34e","#81d34d","#84d44b","#86d549","#89d548","#8bd646","#8ed645","#90d743","#93d741","#95d840","#98d83e","#9bd93c","#9dd93b","#a0da39","#a2da37","#a5db36","#a8db34","#aadc32","#addc30","#b0dd2f","#b2dd2d","#b5de2b","#b8de29","#bade28","#bddf26","#c0df25","#c2df23","#c5e021","#c8e020","#cae11f","#cde11d","#d0e11c","#d2e21b","#d5e21a","#d8e219","#dae319","#dde318","#dfe318","#e2e418","#e5e419","#e7e419","#eae51a","#ece51b","#efe51c","#f1e51d","#f4e61e","#f6e620","#f8e621","#fbe723","#fde725"];

      var colour = d3.scaleQuantize()
        .domain(d3.extent(mapData, function(s) {return Math.log(s.recs)}))
        .range(viridis);

      const shape = $('input[name="bas-atlas-control-dot-type"]:checked').val()
      const opacity = $(`#opacity-slider`).val()
      // At this stage, there might be some records without a
      // resolved squares (possibly outside UK?) so filter these out.
      var recs = mapData.filter(function(s){return s.gr}).map(function(s) {
        return {
          gr: s.gr,
          id: s.gr,
          colour: colour(Math.log(s.recs)),
          caption: s.recs
        }
      })

      const legendSizeFact = 0.8
      resolve({
        records: recs,
        size: 1,
        precision: vcCode ? 2000 : 10000,
        shape: shape,
        opacity: opacity,
        legend: {
          title: 'Record density',
          size: legendSizeFact,
          shape: shape,
          colour: 'black',
          precision: vcCode ? 2000 : 10000,
          opacity: opacity,
          lines: [{
            text: 'Low',
            colour: viridis[0],
          }, {
            text: 'Intermediate',
            colour: viridis[Math.floor(viridis.length/2)],
          },{
            text: 'High',
            colour: viridis[viridis.length-1],
          }]
        }
      })
    })
  }

  function mapTetradFrequency() {

    zoommap.setLegendOpts({
      display: true,
      scale: 0.8,
      x: 10,
      y: 10,
      width: 125,
      height: 120
    })

    return new Promise(function (resolve, reject) {

      const shape = $('input[name="bas-atlas-control-dot-type"]:checked').val()
      const opacity = $(`#opacity-slider`).val()

      // At this stage, there might be some records without a
      // resolved hectads (possibly outside UK?) so filter these out.
      var recs = mapData.filter(function(h){return h.gr}).map(function(h) {

        const tetround = Math.ceil(h.tetrads/5)*5
        const size = Math.sqrt(tetround)/5
        return {
          gr: h.gr,
          id: h.gr,
          colour: 'black',
          caption: h.recs,
          size: size
        }
      })

      const legendSizeFact = 0.8
      resolve({
        records: recs,
        precision: vcCode ? 2000 : 10000,
        shape: shape,
        opacity: opacity,
        legend: {
          title: 'Tetrad frequency',
          size: 1,
          shape: shape,
          colour: 'black',
          precision: vcCode ? 2000 : 10000,
          opacity: opacity,
          lines: [{
            text: '1–5',
            size: Math.sqrt(5)/5 * legendSizeFact,
          }, {
            text: '6–10',
            size: Math.sqrt(10)/5 * legendSizeFact,
          },{
            text: '11–15',
            size: Math.sqrt(15)/5 * legendSizeFact,
          }, {
            text: '16–20',
            size: Math.sqrt(20)/5 * legendSizeFact,
          }, {
            text: '21–25',
            size: Math.sqrt(25)/5 * legendSizeFact,
          }]
        }
      })
    })
  }

  function mapStandard() {

    const shape = $('input[name="bas-atlas-control-dot-type"]:checked').val()
    const opacity = $(`#opacity-slider`).val()

    return new Promise(function (resolve, reject) {

      // At this stage, there might be some records without a
      // resolved squares (possibly outside UK?) so filter these out.
      var recs = mapData.filter(function(s){return s.gr}).map(function(s) {
        return {
          gr: s.gr,
          id: s.gr,
          colour: 'black', // TODO set colour by form parameter
          caption: s.recs
        };
      });

      resolve({
        records: recs,
        size: 1,
        precision: vcCode ? 2000 : 10000,
        shape: shape,
        opacity: opacity,
      });
    });
  }

  function getDateString() {
    const today = new Date()
    const yyyy = today.getFullYear()
    let mm = today.getMonth() + 1 // Months start at 0!
    let dd = today.getDate()

    if (dd < 10) dd = '0' + dd
    if (mm < 10) mm = '0' + mm

    return dd + '/' + mm + '/' + yyyy
  }

  function markUp(taxon) {
    // Looks for <em> markup tags in a string and replace with *each word* within the
    // tag marked up individually with <i> tags. This is required for the SVG image
    // download functionality.
    let taxonNew = ''
    let remainder = taxon
    let ems

    do {
      ems = remainder.indexOf('<em>')
      if (ems > -1) {
        const eme = remainder.indexOf('</em>')
        const italicised = remainder.substring(ems+4, eme)
        const standard = remainder.substring(0, ems)
        remainder = remainder.substring(eme+5)
        const iMarkup = italicised.split(' ').reduce((i, w) => `${i} <i>${w}</i>`, '').trim()
        taxonNew = `${taxonNew}${standard}${iMarkup}`
      }
    } while (ems > -1)
    taxonNew = `${taxonNew}${remainder}`

    return taxonNew
  }
});