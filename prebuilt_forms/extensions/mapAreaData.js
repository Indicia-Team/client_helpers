/**
 * Each entry in the list contains the following:
 * * Bounds is the left, bottom, right, top of the area to zoom to. Default is this should be in web mercator
 *   projection.
 * * system is the optional preferred spatial ref system code to use for this area of the globe.
 * * projection is the graticule's projection which should be displayed on the map for this area.
 *
 * If the bounds are provided in a projection other than web mercator, set indiciaData.areaPickerBoundsProjection to the
 * projection's EPSG code.
 *
 * If the area represents a box which should be drawn on the map (e.g. a grid square) then set
 * indiciaData.areaPickerDrawBounds to true.
 */
indiciaData.areaPickerMapAreaData = {
  England: {bounds: [-729000, 6410000, 208000, 7516000], system: 'OSGB', projection: 27700},
  'Channel Islands': {bounds: [-314000, 6293000, -220400, 6418000], system: 'utm30ed50', projection: 23030},
  Ireland: {bounds: [-1201000, 6671000, -608000, 7452000], system: 'OSIE', projection: 29901},
  'Isle of Man': {bounds: [-554000, 7164000, -472600, 7266000], system: 'OSGB', projection: 27700},
  'Northern Ireland': {bounds: [-924000, 7178000, -608000, 7452000], system: 'OSIE', projection: 29901},
  Scotland: {bounds: [-885000, 7240000, -17000, 8650000], system: 'OSGB', projection: 27700},
  Wales: {bounds: [-594000, 6681000, -266000, 7098000], system: 'OSGB', projection: 27700}
};
