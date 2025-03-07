<?php

/**
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/indicia-team/client_helpers/
 */

use IForm\prebuilt_forms\PageType;
use IForm\prebuilt_forms\PrebuiltFormInterface;

require_once 'includes/map.php';
require_once 'includes/user.php';
require_once 'includes/form_generation.php';

function iform_timed_count_subsample_cmp($a, $b)
{
    return strcmp($a["date_start"], $b["date_start"]);
}

// BIG WARNING: in this form the sample Sref will not represent the geometry.
// Each visit the flight area is reentered: no 2 visits have the same geometry. No location records.

// TODO Check if OS Map number is to be included.
// TODO Check validation rules to be applied to each field.

class iform_timed_count implements PrebuiltFormInterface {

  /**
   * Return the form metadata.
   *
   * @return array
   *   The definition of the form.
   */
  public static function get_timed_count_definition() {
    return [
      'title' => 'Timed Count',
      'category' => 'Forms for specific surveying methods',
      'description' => 'A form for inputting the counts of species during a timed period. Can be called with sample=<id> to edit an existing sample.',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public static function getPageType(): PageType {
    return PageType::DataEntry;
  }

  /**
   * Get the list of parameters for this form.
   * @return array List of parameters that this form requires.
   */
  public static function get_parameters() {
    $params = array_merge(
      iform_map_get_map_parameters(),
      iform_map_get_georef_parameters(),
      array(
      	array(
      		'name' => 'manager_permission',
      		'caption' => 'Drupal Permission for Manager mode',
      		'description' => 'Enter the Drupal permission name to be used to determine if this user is a manager: such people may modify the shape of an existing flight area.',
      		'type' => 'string',
      		'required' => false
      	),
        array(
          'name' => 'survey_id',
          'caption' => 'Survey',
          'description' => 'The survey that data will be posted into.',
          'type' => 'select',
          'table' => 'survey',
          'captionField' => 'title',
          'valueField' => 'id',
          'siteSpecific'=>true
        ),
        array(
          'name' => 'defaults',
          'caption' => 'Default Values',
          'description' => 'Supply default values for each field as required. On each line, enter fieldname=value, e.g. occurrence:record_status. '.
              'NOTE, currently only supports occurrence:record_status, but will be extended in future.',
          'type' => 'textarea',
          'default' => 'occurrence:record_status=C',
          'siteSpecific'=>true,
          'required'=>false
        ),
        array(
          'name' => 'occurrence_attribute_id',
          'caption' => 'Occurrence Attribute',
          'description' => 'The attribute (typically an abundance attribute) that will be presented in the grid for input. Entry of an attribute value will create '.
              ' an occurrence.',
          'type' => 'select',
          'table' => 'occurrence_attribute',
          'captionField' => 'caption',
          'valueField' => 'id',
          'siteSpecific'=>true
        ),
        array(
          'name' => 'taxon_list_id',
          'caption' => 'Species List',
          'description' => 'The species checklist used for the species autocomplete.',
          'type' => 'select',
          'table' => 'taxon_list',
          'captionField' => 'title',
          'valueField' => 'id',
          'siteSpecific'=>true,
          'group' => 'Species'
        ),
        array(
          'name' => 'taxon_filter_field',
          'caption' => 'Species List: Field used to filter taxa',
          'description' => 'If you want to allow recording for just part of the selected Species List, then select which field you will '.
              'use to specify the filter by.',
          'type' => 'select',
          'options' => array(
            'taxon' => 'Taxon',
            'taxon_meaning_id' => 'Taxon Meaning ID',
            'taxon_group' => 'Taxon group title'
          ),
          'siteSpecific'=>true,
          'required'=>false,
          'group' => 'Species'
        ),
        array(
          'name' => 'taxon_filter',
          'caption' => 'Species List: Taxon filter items',
          'description' => 'When filtering the list of available taxa, taxa will not be available for recording unless they match one of the '.
              'values you input in this box. Enter one value per line. E.g. enter a list of taxon group titles if you are filtering by taxon group.',
          'type' => 'textarea',
          'siteSpecific'=>true,
          'required'=>false,
          'group' => 'Species'
        ),
        array(
          'name' => 'custom_attribute_options',
          'caption' => 'Options for custom attributes',
          'description' => 'A list of additional options to pass through to custom attributes, one per line. Each option should be specified as '.
              'the attribute name followed by | then the option name, followed by = then the value. For example, smpAttr:1|class=control-width-5.',
          'type' => 'textarea',
          'required'=>false,
          'siteSpecific'=>true
        ),
        array(
          'name' => 'summary_page',
          'caption' => 'Path to summary page',
          'description' => 'Path used to access the main page giving a summary of the entered time walks after a successful submission (e.g. a report_calendar_grid page).',
          'type' => 'text_input',
          'required'=>true,
          'siteSpecific'=>true
        ),
        array(
          'name' => 'numberOfCounts',
          'caption' => 'Min number of counts',
          'description' => 'Min number of counts to be displayed on the entry page for this location.',
          'type' => 'int',
          'required'=>true,
          'siteSpecific'=>true,
          'default'=>2
        ),
        array(
          'name' => 'numberOfSpecies',
          'caption' => 'Number of species',
          'description' => 'The number of species that can be entered per count.',
          'type' => 'int',
          'required'=>true,
          'siteSpecific'=>true,
          'default'=>2
        ),
        array(
          'name' => 'spatial_systems',
          'caption' => 'Allowed Spatial Ref Systems',
          'description' => 'List of allowable spatial reference systems, comma separated. Use the spatial ref system code (e.g. OSGB or the EPSG code number such as 4326). '.
              'Set to "default" to use the settings defined in the IForm Settings page.',
          'type' => 'string',
          'default' => 'default',
          'group' => 'Other Map Settings'
        ),
        array(
          'name' => 'precision',
          'caption' => 'Sref Precision',
          'description' => 'The precision to be applied to the polygon centroid when determining the SREF. Leave blank to not set.',
          'type' => 'int',
          'required'=>false,
          'group' => 'Other Map Settings'
        )
      )
    );
    foreach($params as $param) {
    	if($param['name']=='georefDriver')
    		$param['required']=false; // method of georef detection is to see if driver specified: allows ommision of area preferences.
    }
    return $params;
  }

  /**
   * Return the generated form output.
   * @param array $args List of parameter values passed through to the form depending on how the form has been configured.
   * This array always contains a value for language.
   * @param object $nid The Drupal node object.
   * @param array $response When this form is reloading after saving a submission, contains the response from the service call.
   * Note this does not apply when redirecting (in this case the details of the saved object are in the $_GET data).
   * @return Form HTML.
   */
  public static function get_form($args, $nid, $response=null) {
    if (isset($response['error'])){
      data_entry_helper::dump_errors($response);
    }
    if (isset($_REQUEST['page']) &&
          (($_REQUEST['page']==='site' && !isset(data_entry_helper::$validation_errors)) || // we have just saved the main sample page with no errors, so move on to the occurrences list
           ($_REQUEST['page']==='occurrences' && isset(data_entry_helper::$validation_errors)))) { // or we have just saved the occurrences page with errors, so redisplay the occurrences list
      return self::get_occurrences_form($args, $nid, $response);
    } else {
      return self::get_sample_form($args, $nid, $response);
    }
  }

  public static function get_sample_form($args, $nid, $response) {
  	global $user;
  	iform_load_helpers(array('map_helper'));
  	data_entry_helper::add_resource('indiciaMapPanel');
  	$auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
  	// either looking at existing, creating a new one, or an error occurred: no successful posts...
  	// first check some conditions are met
  	$sampleMethods = helper_base::get_termlist_terms($auth, 'indicia:sample_methods', array('Timed Count'));
  	if (count($sampleMethods)==0)
  		return 'The sample method "Timed Count" must be defined in the termlist in order to use this form.';

  	$sampleId = isset($_GET['sample_id']) ? $_GET['sample_id'] : null;
  	if ($sampleId && !isset(data_entry_helper::$validation_errors))
  		data_entry_helper::load_existing_record($auth['read'], 'sample', $sampleId);

  	$isAdmin = (isset($args['manager_permission']) && hostsite_user_has_permission($args['manager_permission']));

  	// The following is butchered from mnhnl.
	data_entry_helper::$javascript .= "
// the default edit layer is used for this sample
var editLayer = null;

convertGeom = function(geom, projection){
  if (projection.projcode!='EPSG:900913' && projection.projcode!='EPSG:3857') {
    var cloned = geom.clone();
    return cloned.transform(new OpenLayers.Projection('EPSG:900913'), projection);
  }
  return geom;
}
reverseConvertGeom = function(geom, projection){
  if (projection.projcode!='EPSG:900913' && projection.projcode!='EPSG:3857') {
    var cloned = geom.clone();
    return cloned.transform(projection, new OpenLayers.Projection('EPSG:900913'));
  }
  return geom;
}

getwkt = function(geometry, incFront, incBrackets){
  var retVal;
  	  retVal = '';
  	  switch(geometry.CLASS_NAME){
  	  case \"OpenLayers.Geometry.Point\":
  	  return((incFront!=false ? 'POINT' : '')+(incBrackets!=false ? '(' : '')+geometry.x+' '+geometry.y+(incBrackets!=false ? ')' : ''));
  	  break;
  	  case \"OpenLayers.Geometry.MultiPoint\":
      retVal = 'MULTIPOINT(';
      for(var i=0; i< geometry.components.length; i++)
        retVal += (i!=0 ? ',':'')+getwkt(geometry.components[i], false, true);
      retVal += ')';
      break;
    case \"OpenLayers.Geometry.LineString\":
      retVal = (incFront!=false ? 'LINESTRING' : '')+'(';
      for(var i=0; i< geometry.components.length; i++)
        retVal += (i!=0 ? ',':'')+getwkt(geometry.components[i], false, false);
      retVal += ')';
      break;
    case \"OpenLayers.Geometry.MultiLineString\":
      retVal = 'MULTILINESTRING(';
      for(var i=0; i< geometry.components.length; i++)
        retVal += (i!=0 ? ',':'')+getwkt(geometry.components[i], false, true);
      retVal += ')';
      break;
    case \"OpenLayers.Geometry.Polygon\": // only do outer ring
      retVal = (incFront!=false ? 'POLYGON' : '')+'((';
      for(var i=0; i< geometry.components[0].components.length; i++)
        retVal += (i!=0 ? ',':'')+getwkt(geometry.components[0].components[i], false, false);
      retVal += '))';
      break;
    case \"OpenLayers.Geometry.MultiPolygon\":
      retVal = 'MULTIPOLYGON(';
      for(var i=0; i< geometry.components.length; i++)
        retVal += (i!=0 ? ',':'')+getwkt(geometry.components[i], false, true);
      retVal += ')';
      break;
    case \"OpenLayers.Geometry.Collection\":
      retVal = 'GEOMETRYCOLLECTION(';
      for(var i=0; i< geometry.components.length; i++)
        retVal += (i!=0 ? ',':'')+getwkt(geometry.components[i], true, true);
      retVal += ')';
      break;
  }
  return retVal;
}
setSref = function(geometry){
  var centre = getCentroid(geometry);
  centre = reverseConvertGeom(centre, editLayer.map.projection); // convert to indicia internal projection
  var system = $('#imp-sref-system').val();
  jQuery.getJSON('".data_entry_helper::$base_url."/index.php/services/spatial/wkt_to_sref?wkt=POINT(' + centre.x + '  ' + centre.y + ')&system=' + system + '&precision=".(isset($args['precision']) && $args['precision'] != '' ? $args['precision'] : '8')."&callback=?',
      function(data){
        if(typeof data.error != 'undefined')
          alert(data.error);
        else
          $('#imp-sref').val(data.sref);
       });
};
hook_setGeomFields = [];
setGeomFields = function(){
  var geomstack = [];
  var completeGeom;
  for(var i=0; i<editLayer.features.length; i++)
    if(editLayer.features[i].attributes.highlighted == true)
      geomstack.push(editLayer.features[i].geometry.clone()); // needs to be a clone as we don't want to transform the original geoms.
  if(geomstack.length == 0){
    jQuery('#imp-geom').val('');
    jQuery('#imp-sref').val('');
    return;
  } else if (geomstack.length == 1)
    completeGeom = geomstack[0];
  else
    completeGeom = new OpenLayers.Geometry.Collection(geomstack);
  // the geometry is in the map projection: if this doesn't match indicia's internal one, then must convert.
  if (editLayer.map.projection.projcode!='EPSG:900913' && editLayer.map.projection.projcode!='EPSG:3857')
    completeGeom.transform(editLayer.map.projection,  new OpenLayers.Projection('EPSG:900913'));
  jQuery('#imp-geom').val(getwkt(completeGeom, true, true));
  setSref(getCentroid(completeGeom));
  if(hook_setGeomFields.length > 0)
    for(i=0; i< hook_setGeomFields.length; i++)
  	  (hook_setGeomFields[i])(completeGeom);
}
removeDrawnGeom = function(){
  var highlighted=gethighlight();
  if(highlighted.length > 0) {
    unhighlightAll();
  }
  for(var i=editLayer.features.length-1; i>=0; i--)
    editLayer.destroyFeatures([editLayer.features[i]]);
}
replaceGeom = function(feature, layer, modControl, geom, highlight, setFields){
  if(modControl.feature)
    modControl.unselectFeature(modControl.feature);
  var newfeature = new OpenLayers.Feature.Vector(geom, {});
  newfeature.attributes = feature.attributes;
  layer.destroyFeatures([feature]);
  layer.addFeatures([newfeature]);
  modControl.selectFeature(newfeature);
  selectFeature.highlight(newfeature);
  newfeature.attributes.highlighted=true;
  if(setFields) setGeomFields();
}
addAndSelectNewGeom = function(layer, modControl, geom, highlight){
  var feature = new OpenLayers.Feature.Vector(geom, {highlighted: false, ours: true});
  layer.addFeatures([feature]);
  modControl.selectFeature(feature);
  feature.attributes.highlighted=true;
  selectFeature.highlight(feature);
  setGeomFields();
  return feature;
}
addToExistingFeatureSet = function(existingFeatures, layer, modControl, geom, highlight){
  var feature = new OpenLayers.Feature.Vector(geom, {});
  feature.attributes = existingFeatures[0].attributes;
  layer.addFeatures([feature]);
  modControl.selectFeature(feature);
  selectFeature.highlight(feature);
  feature.attributes.highlighted=true;
  setGeomFields();
}
unhighlightAll = function(){
  if(modAreaFeature.feature) modAreaFeature.unselectFeature(modAreaFeature.feature);
  var highlighted = gethighlight();
  for(var i=0; i<highlighted.length; i++) {
    highlighted[i].attributes.highlighted = false;
    selectFeature.unhighlight(highlighted[i]);
  }
}
gethighlight = function(){
  var features=[];
  for(var i=0; i<editLayer.features.length; i++){
    if(editLayer.features[i].attributes.highlighted==true){
      features.push(editLayer.features[i]);
    }}
  return features;
}

addDrawnPolygonToSelection = function(geometry) {
  var points = geometry.components[0].getVertices();
  if(points.length < 3){
    alert(\"".lang::get('LANG_TooFewPoints')."\");
    return false;
  }
  var highlightedFeatures = gethighlight();
  if(highlightedFeatures.length == 0){
    // No currently selected feature. Create a new one.
    feature = addAndSelectNewGeom(editLayer, modAreaFeature, geometry, true);
  	return true;
  }
  var selectedFeature = false;
  for(var i=0; i<highlightedFeatures.length; i++){
    if(highlightedFeatures[i].geometry.CLASS_NAME == \"OpenLayers.Geometry.Polygon\" ||
        highlightedFeatures[i].geometry.CLASS_NAME == \"OpenLayers.Geometry.MultiPolygon\") {
      selectedFeature = highlightedFeatures[i];
  	        		break;
    }}
  // a site is already selected so the Drawn/Specified state stays unaltered
  if(!selectedFeature) {
      addToExistingFeatureSet(highlightedFeatures, editLayer, modAreaFeature, geometry, true);
      return true;
  }
  if(selectedFeature.geometry.CLASS_NAME == \"OpenLayers.Geometry.MultiPolygon\") {
    if(modAreaFeature.feature)
	    modAreaFeature.unselectFeature(selectedFeature);
    selectedFeature.geometry.addComponents([geometry]);
    modAreaFeature.selectFeature(selectedFeature);
    selectFeature.highlight(selectedFeature);
    selectedFeature.attributes.highlighted = true;
    setGeomFields();
  } else { // is OpenLayers.Geometry.Polygon
    var CompoundGeom = new OpenLayers.Geometry.MultiPolygon([selectedFeature.geometry, geometry]);
    replaceGeom(selectedFeature, editLayer, modAreaFeature, CompoundGeom, true, true);
  }
  return true;
}
onFeatureModified = function(evt) {
  var feature = evt.feature;
  switch(feature.geometry.CLASS_NAME){
    case \"OpenLayers.Geometry.Polygon\": // only do outer ring
      points = feature.geometry.components[0].getVertices();
      if(points.length < 3){
        alert(\"".lang::get('There are now too few vertices to make a polygon: it will now be completely removed.')."\");
        modAreaFeature.unselectFeature(feature);
        editLayer.destroyFeatures([feature]);
      }
      break;
    case \"OpenLayers.Geometry.MultiPolygon\":
  	  for(i=feature.geometry.components.length-1; i>=0; i--) {
        points = feature.geometry.components[i].components[0].getVertices();
        if(points.length < 3){
          alert(\"".lang::get('There are now too few vertices to make a polygon: it will now be completely removed.')."\");
  	      var selectedFeature = modAreaFeature.feature;
          modAreaFeature.unselectFeature(selectedFeature);
          selectFeature.unhighlight(selectedFeature);
          editLayer.removeFeatures([selectedFeature]);
          selectedFeature.geometry.removeComponents([feature.geometry.components[i]]);
  	      editLayer.addFeatures([selectedFeature]);
          modAreaFeature.selectFeature(selectedFeature);
          selectFeature.highlight(selectedFeature);
          selectedFeature.attributes.highlighted = true;
        }
      }
      if(feature.geometry.components.length == 0){
        modAreaFeature.unselectFeature(feature);
        editLayer.destroyFeatures([feature]);
      }
      break;
  }
  setGeomFields();
}
/********************************/
/* Define Map Control callbacks */
/********************************/
UndoSketchPoint = function(layer){
  for(var i = editControl.controls.length-1; i>=0; i--)
    if(editControl.controls[i].CLASS_NAME == \"OpenLayers.Control.DrawFeature\" && editControl.controls[i].active)
      editControl.controls[i].undo();
};
RemoveNewSite = function(){
  highlighted = gethighlight();
  removeDrawnGeom(highlighted[0].attributes.SiteNum);
  setGeomFields();
};
ZoomToFeature = function(feature){
  var div = jQuery('#map')[0];
  var bounds=feature.geometry.bounds.clone();
  // extend the boundary to include a buffer, so the map does not zoom too tight.
  var dy = (bounds.top-bounds.bottom) * div.settings.maxZoomBuffer;
  var dx = (bounds.right-bounds.left) * div.settings.maxZoomBuffer;
  bounds.top = bounds.top + dy;
  bounds.bottom = bounds.bottom - dy;
  bounds.right = bounds.right + dx;
  bounds.left = bounds.left - dx;
  if (div.map.getZoomForExtent(bounds) > div.settings.maxZoom) {
    // if showing something small, don't zoom in too far
    div.map.setCenter(bounds.getCenterLonLat(), div.settings.maxZoom);
  } else {
    // Set the default view to show something triple the size of the grid square
    // Assume this is within the map extent
    div.map.zoomToExtent(bounds);
  }
};
/***********************************/
/* Define Controls for use on Map. */
/***********************************/
polygonDrawActivate = function(){
  selectFeature.deactivate();
  modAreaFeature.activate();
  highlighted = gethighlight();
  if(highlighted.length == 0)
    return true;
  for(var i=0; i<editLayer.features.length; i++)
      if(editLayer.features[i].attributes.highlighted == true)
        modAreaFeature.selectFeature(editLayer.features[i]);
  return true;
};

modAreaFeature = null;
selectFeature = null;
polygonDraw = null;
editControl = null;

mapInitialisationHooks.push(function(mapdiv) {
	$('#imp-sref').unbind('change');
	editLayer=mapdiv.map.editLayer;
    var nav=new OpenLayers.Control.Navigation({displayClass: \"olControlNavigation\", \"title\":mapdiv.settings.hintNavigation+((!mapdiv.settings.scroll_wheel_zoom || mapdiv.settings.scroll_wheel_zoom===\"false\")?'': mapdiv.settings.hintScrollWheel)});
	editControl = new OpenLayers.Control.Panel({allowDepress: false, 'displayClass':'olControlEditingToolbar'});
	mapdiv.map.addControl(editControl);
".($isAdmin || $sampleId == null ?
	// can edit the shape
"  	modAreaFeature = new OpenLayers.Control.ModifyFeature(editLayer,{standalone: true});
	selectFeature = new OpenLayers.Control.SelectFeature([editLayer],{standalone: true});
	polygonDraw = new OpenLayers.Control.DrawFeature(editLayer,OpenLayers.Handler.Polygon,{'displayClass':'olControlDrawFeaturePolygon', drawFeature: addDrawnPolygonToSelection, title: '".lang::get('Select this tool to draw a polygon, clicking on the map to place the vertices of the shape, and double clicking on the final vertex to finish. You may drag and drop vertices (circles) to move them, and draw more than one polygon, i.e. input a discontinuous site.')."'});
	polygonDraw.events.on({'activate': polygonDrawActivate});
	editControl.addControls([polygonDraw
				         ,new OpenLayers.Control.Button({displayClass: \"olControlClearLayer\", trigger: RemoveNewSite, title: '".lang::get('Press this button to completely remove the currently drawn site.')."'})
    					 ,nav
				         ,new OpenLayers.Control.Button({displayClass: \"olControlUndo\", trigger: UndoSketchPoint, title: '".lang::get('If you have not completed a polygon, press this button to clear the last vertex. If you have completed a polygon but wish to remove a vertex, place the mouse over the vertex and press the Delete button: this only works for the corner vertices, not the dummy ones half way down each side.')."'})
    ]);
	mapdiv.map.addControl(modAreaFeature);
	modAreaFeature.deactivate();
	for(var i=0; i<mapdiv.map.controls.length; i++)
      mapdiv.map.controls[i].deactivate();
	editControl.activate();
	nav.activate();
	// any existing features come from the existing sites geometry: convert to ours
    // will have been zoomed as well.
	for(var i=0; i<editLayer.features.length; i++) {
	  editLayer.features[i].attributes.highlighted = true;
	  editLayer.features[i].attributes.type = 'ours';
	  editLayer.features[i].attributes.ours = true;
	  selectFeature.highlight(editLayer.features[i]);
	}
	editLayer.events.on({
		'featuremodified': onFeatureModified
	});
" :
	// non admin access to existing shape: can't modify it.
"	editControl.addControls([nav]);
	for(var i=0; i<mapdiv.map.controls.length; i++)
      mapdiv.map.controls[i].deactivate();
	editControl.activate();
	nav.activate();
").
"   delete indiciaData['zoomToAfterFetchingGoogleApiScript-' + mapdiv.map.id];
    mapdiv.map.events.triggerEvent('zoomend');
  $('.olControlEditingToolbar').append('<span id=\"mousePos\"></span>');
  mapdiv.map.mousePosCtrl = new MyMousePositionControl({
      div: document.getElementById('mousePos'),
      displayProjection: new OpenLayers.Projection('EPSG:4326'),
      emptyString: '',
      numDigits: 10 // indiciaData.formOptions.routeMapMousePositionPrecision
  });
  mapdiv.map.addControl(mapdiv.map.mousePosCtrl);
});
";

    $r = '<form method="post" id="sample">'.$auth['write'];
    // we pass through the read auth. This makes it possible for the get_submission method to authorise against the warehouse
    // without an additional (expensive) warehouse call, so it can get location details.
    $r .= '<input type="hidden" name="read_nonce" value="'.$auth['read']['nonce'].'"/>';
    $r .= '<input type="hidden" name="read_auth_token" value="'.$auth['read']['auth_token'].'"/>';
    if (isset(data_entry_helper::$entity_to_load['sample:id']))
      $r .= '<input type="hidden" name="sample:id" value="'.data_entry_helper::$entity_to_load['sample:id'].'"/>';
    // pass a param that sets the next page to display
    $r .= "<input type=\"hidden\" name=\"website_id\" value=\"".$args['website_id']."\"/>
<input type=\"hidden\" name=\"sample:survey_id\" value=\"".$args['survey_id']."\"/>
<input type=\"hidden\" name=\"page\" value=\"site\"/>";

    $attributes = data_entry_helper::getAttributes(array(
      'id' => $sampleId,
      'valuetable' => 'sample_attribute_value',
      'attrtable' => 'sample_attribute',
      'key' => 'sample_id',
      'fieldprefix' => 'smpAttr',
      'extraParams' => $auth['read'],
      'survey_id' => $args['survey_id'],
      'sample_method_id' => $sampleMethods[0]['id']
    ));
    $r .= get_user_profile_hidden_inputs($attributes, $args, isset(data_entry_helper::$entity_to_load['sample:id']), $auth['read']).
        data_entry_helper::text_input(array('label' => lang::get('Site Name'), 'fieldname' => 'sample:location_name', 'validation' => array('required') /*, 'class' => 'control-width-5' */ ))
        // .data_entry_helper::textarea(array('label'=>lang::get('Recorder names'), 'fieldname' => 'sample:recorder_names'))
        ;
    if ($sampleId == null){
      if(isset($_GET['date'])) data_entry_helper::$entity_to_load['C1:sample:date'] = $_GET['date'];
      $r .= data_entry_helper::date_picker(array('label' => lang::get('Date of first count'), 'fieldname' => 'C1:sample:date', 'validation' => array('required','date')));
      data_entry_helper::$javascript .= "
indiciaFns.on('change', '.precise-date-picker', {}, function(e) {
  var dateToSave = $(e.currentTarget).val();
  var result;
  if (result = dateToSave.trim().match(/^\\d{4}/)) {
    // Date given year first, so ISO format. That's how HTML5 date input
    // values are formatted.
    $('#sample\\\\:date').val(result);
  } else if (result = dateToSave.trim().match(/\\d{4}$/)) {
    // Date given year last
    $('#sample\\\\:date').val(result);
  }
});
if($('#C1\\\\:sample\\\\:date').val() != '') {
  $('.precise-date-picker').change();
}\n";
    }
    unset(data_entry_helper::$default_validation_rules['sample:date']);
    $help = lang::get('The Year field is read-only, and is calculated automatically from the date(s) of the Counts.');
    $r .= data_entry_helper::text_input(array('label' => lang::get('Year'), 'fieldname' => 'sample:date', 'readonly' => ' readonly="readonly" ', 'helpText'=>$help));
    data_entry_helper::$javascript .= "$('#sample\\\\:date').css('color','graytext').css('background-color','#d0d0d0');\n";

    // are there any option overrides for the custom attributes?
    if (isset($args['custom_attribute_options']) && $args['custom_attribute_options'])
      $blockOptions = get_attr_options_array_with_user_data($args['custom_attribute_options']);
    else $blockOptions=[];
    $r .= get_attribute_html($attributes, $args, array('extraParams'=>$auth['read']), null, $blockOptions);
    foreach($blockOptions as $attr => $block){
      foreach($block as $item => $value){
      	switch($item){
      		case 'suffix':
	      	  data_entry_helper::$javascript .= "$('#".str_replace(':','\\\\:',$attr)."').after(' <label style=\"width:auto\">".$value."</label>');\n";
	      	  break;
      		case 'setArea':
      		  $parts = explode(',',$value); // 0=factor,1=number decimal places
      		  data_entry_helper::$javascript .= "$('#".str_replace(':','\\\\:',$attr)."').attr('readonly','readonly').css('color','graytext').css('background-color','#d0d0d0');\nhook_setGeomFields.push(function(geom){\n$('#".str_replace(':','\\\\:',$attr)."').val(\n(geom.getArea()/".$parts[0].").toFixed(".$parts[1]."));\n});\n";
      		  break;
      	}
      }
    }
    $r .= '<input type="hidden" name="sample:sample_method_id" value="'.$sampleMethods[0]['id'].'" />';
    $help = lang::get('Now draw the flight area for the timed count on the map below. The Grid Reference is filled in automatically when the site is drawn.');
    $r .= '<p class="ui-state-highlight page-notice ui-corner-all">'.$help.'</p>';
    $options = iform_map_get_map_options($args, $auth['read']);
    $options['clickForSpatialRef'] = false;
    $olOptions = iform_map_get_ol_options($args);

    $systems=[];
    $list = explode(',', str_replace(' ', '', $args['spatial_systems']));
    foreach($list as $system) $systems[$system] = lang::get('sref:'.$system) == 'sref:'.$system ? lang::get($system) : lang::get('sref:'.$system);
    $r .= data_entry_helper::sref_and_system(array(
      'label' => lang::get('Grid Reference'),
      'fieldname' => 'sample:entered_sref',
      'findMeButton' => false,
      'systems' => $systems
    ));
    data_entry_helper::$javascript .= "$('#imp-sref').attr('readonly','readonly').css('color','graytext').css('background-color','#d0d0d0');\n";

    if(isset($args['georefDriver']) && $args['georefDriver']!='' && !isset(data_entry_helper::$entity_to_load['sample:id']))
      $r .= '<br />'.data_entry_helper::georeference_lookup(iform_map_get_georef_options($args, $auth['read']));
    iform_load_helpers(['map_helper']);
    $r .= map_helper::map_panel($options, $olOptions);

    $r .= data_entry_helper::textarea(array('label' => 'Comment', 'fieldname' => 'sample:comment', 'class' => 'wide'));
    if (lang::get('LANG_DATA_PERMISSION') !== 'LANG_DATA_PERMISSION') {
      $r .= '<p>' . lang::get('LANG_DATA_PERMISSION') . '</p>';
    }
    $r .= '<input type="submit" value="'.lang::get('Next').'" />';
    $r .= '<a href="'.$args['summary_page'].'"><button type="button" class="ui-state-default ui-corner-all" />'.lang::get('Cancel').'</button></a>';

    // allow deletes if sample id is present: i.e. existing sample.
    if (isset(data_entry_helper::$entity_to_load['sample:id'])){
      $r .= '<button id="delete-button" type="button" class="ui-state-default ui-corner-all" />'.lang::get('Delete').'</button>';
      // note we only require bare minimum in order to flag a sample as deleted.
      $r .= '</form><form method="post" id="delete-form" style="display: none;">';
      $r .= $auth['write'];
      $r .= '<input type="hidden" name="page" value="delete"/>';
      $r .= '<input type="hidden" name="website_id" value="'.$args['website_id'].'"/>';
      $r .= '<input type="hidden" name="sample:id" value="'.data_entry_helper::$entity_to_load['sample:id'].'"/>';
      $r .= '<input type="hidden" name="sample:deleted" value="t"/>';
      data_entry_helper::$javascript .= "jQuery('#delete-button').click(function(){
  if(confirm(\"".lang::get('Are you sure you want to delete this timed count?')."\"))
    jQuery('#delete-form').submit();
});\n";
    }

    $r .= '</form>';
    data_entry_helper::enable_validation('sample');
    return $r;
  }

  public static function get_occurrences_form($args, $nid, $response) {
    global $user;
    data_entry_helper::add_resource('jquery_form');
    data_entry_helper::add_resource('jquery_ui');
    data_entry_helper::add_resource('autocomplete');
    $auth = data_entry_helper::get_read_write_auth($args['website_id'], $args['password']);
    // did the parent sample previously exist? Default is no.
    $parentSampleId=null;
    $existing=false;
    if (isset($_POST['sample:id'])) {
      // have just posted an edit to the existing parent sample, so can use it to get the parent location id.
      $parentSampleId = $_POST['sample:id'];
      $existing=true;
    } else {
      if (isset($response['outer_id']))
        // have just posted a new parent sample, so can use it to get the parent location id.
        $parentSampleId = $response['outer_id'];
      else {
        $parentSampleId = $_GET['sample_id'];
        $existing=true;
      }
    }
    if(!$parentSampleId || $parentSampleId == '') return ('Could not determine the parent sample.');

    // find any attributes that apply to Timed Count Count samples.
    $sampleMethods = helper_base::get_termlist_terms($auth, 'indicia:sample_methods', array('Timed Count Count'));
    if (count($sampleMethods)==0)
      return 'The sample method "Timed Count Count" must be defined in the termlist in order to use this form.';
    $attributes = data_entry_helper::getAttributes(array(
      'valuetable' => 'sample_attribute_value',
      'attrtable' => 'sample_attribute',
      'key' => 'sample_id',
      'fieldprefix' => 'smpAttr',
      'extraParams'=>$auth['read'],
      'survey_id'=>$args['survey_id'],
      'sample_method_id'=>$sampleMethods[0]['id'],
      'multiValue'=>false // ensures that array_keys are the list of attribute IDs.
    ));
    if(!isset(data_entry_helper::$validation_errors)){
      // the parent sample and at least one sub-sample have already been created: can't cache in case a new subsample (Count) added.
      data_entry_helper::load_existing_record($auth['read'], 'sample', $parentSampleId);
      // using the report returns the attributes as well.
      $subSamples = data_entry_helper::get_population_data(array(
        'report' => 'library/samples/samples_list_for_parent_sample',
        'extraParams' => $auth['read'] + array('sample_id'=>$parentSampleId,'date_from' => '','date_to' => '', 'sample_method_id' => '', 'smpattrs'=>implode(',', array_keys($attributes))),
        'nocache'=>true
      ));
      // subssamples ordered by id desc, so reorder by date asc.
      usort($subSamples, "iform_timed_count_subsample_cmp");
      for($i = 0; $i < count($subSamples); $i++){
        data_entry_helper::$entity_to_load['C'.($i+1).':sample:id'] = $subSamples[$i]['sample_id'];
        data_entry_helper::$entity_to_load['C'.($i+1).':sample:date'] = $subSamples[$i]['date']; // this is in correct format
        foreach($subSamples[$i] as $field => $value){
          if(preg_match('/^attr_sample_/',  $field)){
            $parts=explode('_',$field);
            if(isset($subSamples[$i]['attr_id_sample_'.$parts[2]]) && $subSamples[$i]['attr_id_sample_'.$parts[2]] != null)
                data_entry_helper::$entity_to_load['C'.($i+1).':smpAttr:'.$parts[2].':'.$subSamples[$i]['attr_id_sample_'.$parts[2]]] = $value;
          }
        }
      }
    } else {
    	return "An internal error has occurred: there has been a mismatch between the validation applied in the browser and that applied by the warehouse.<br/>".
    		"Please contact the site administrator, and provide them with the following diagnostic information.<br>".
			print_r(data_entry_helper::$validation_errors, true)."<br>".
			print_r($_POST, true);
    }

    data_entry_helper::$javascript .= "indiciaData.speciesList = ".$args['taxon_list_id'].";\n";
    if (!empty($args['taxon_filter_field']) && !empty($args['taxon_filter'])) {
      data_entry_helper::$javascript .= "indiciaData.speciesListFilterField = '".$args['taxon_filter_field']."';\n";
      $filterLines = helper_base::explode_lines($args['taxon_filter']);
      data_entry_helper::$javascript .= "indiciaData.speciesListFilterValues = '".json_encode($filterLines)."';\n";
    }
    data_entry_helper::$javascript .= "
indiciaData.indiciaSvc = '".data_entry_helper::$base_url."';\n";
    data_entry_helper::$javascript .= "indiciaData.readAuth = {nonce: '".$auth['read']['nonce']."', auth_token: '".$auth['read']['auth_token']."'};\n";
    data_entry_helper::$javascript .= "indiciaData.parentSample = ".$parentSampleId.";\n";
    data_entry_helper::$javascript .= "indiciaData.occAttrId = ".$args['occurrence_attribute_id'] .";\n";

    if ($existing) {
      // Only need to load the occurrences for a pre-existing sample
      $o = data_entry_helper::get_population_data([
          'report' => 'projects/ukbms/ukbms_occurrences_list_for_parent_sample',
          'extraParams' => $auth['read'] + [
              'sample_id' => $parentSampleId,
              'survey_id' => $args['survey_id'],
              'smpattrs' => '',
              'occattrs' => $args['occurrence_attribute_id']
          ],
        // don't cache as this is live data
        'nocache' => true
      ]);
      for ($i = 0; $i < count($o); $i++) {
          $taxon = data_entry_helper::get_population_data([
              'table' => 'taxa_taxon_list',
              'extraParams' => $auth['read'] + ['id' => $o[$i]['taxa_taxon_list_id'],
                  'view' => 'cache']
          ]);
          $o[$i]['taxon'] = $taxon[0]['preferred_taxon'];
          $o[$i]['common'] = $taxon[0]['default_common_name'];
      }
      // this report is ordered id asc.
    } else $o = array(); // empty array of occurrences when no creating a new sample.

    // we pass through the read auth. This makes it possible for the get_submission method to authorise against the warehouse
    // without an additional (expensive) warehouse call.
    // pass a param that sets the next page to display
    $r = "<form method='post' id='subsamples'>".$auth['write']."
<input type='hidden' name='page' value='occurrences'/>
<input type='hidden' name='read_nonce' value='".$auth['read']['nonce']."'/>
<input type='hidden' name='read_auth_token' value='".$auth['read']['auth_token']."'/>
<input type='hidden' name='website_id' value='".$args['website_id']."'/>
<input type='hidden' name='sample:id' value='".data_entry_helper::$entity_to_load['sample:id']."'/>
<input type='hidden' name='sample:survey_id' value='".$args['survey_id']."'/>
<input type='hidden' name='sample:date' value='".data_entry_helper::$entity_to_load['sample:date']."'/>
<input type='hidden' name='sample:entered_sref' value='".data_entry_helper::$entity_to_load['sample:entered_sref']."'/>
<input type='hidden' name='sample:entered_sref_system' value='".data_entry_helper::$entity_to_load['sample:entered_sref_system']."'/>
<input type='hidden' name='sample:geom' value='".data_entry_helper::$entity_to_load['sample:geom']."'/>
";

    $defaults = isset($args['defaults']) ? helper_base::explode_lines_key_value_pairs($args['defaults']): [];
    $record_status = (isset($defaults['occurrence:record_status']) ? $defaults['occurrence:record_status'] : 'C');
    $r .= '<input type="hidden" name="occurrence:record_status" value="'.$record_status.'">'."\n";

    if (isset($args['custom_attribute_options']) && $args['custom_attribute_options'])
      $blockOptions = get_attr_options_array_with_user_data($args['custom_attribute_options']);
    else $blockOptions=[];
    for($i = 0; $i < max($args['numberOfCounts'], count($subSamples)+1); $i++){
      $subSampleId = (isset($subSamples[$i]) ? $subSamples[$i]['sample_id'] : null);
      $r .= "<fieldset id=\"count-$i\"><legend>".lang::get('Count ').($i+1)."</legend>";
      if ($subSampleId) {
        $r .= "<input type='hidden' name='C".($i+1).":sample:id' value='".$subSampleId."'/>";
      }
      $r .= '<input type="hidden" name="C'.($i+1).':sample:sample_method_id" value="'.$sampleMethods[0]['id'].'" />';
      if ($subSampleId || (isset(data_entry_helper::$entity_to_load['C'.($i+1).':sample:date']) && data_entry_helper::$entity_to_load['C'.($i+1).':sample:date'] != ''))
        $dateValidation = ['required', 'date'];
      else
        $dateValidation = ['date'];
      // The sample dates are restrained to the requisite year, but there is already a restriction on future dates, so only
      // change the upper limit if not this year. the date in data_entry_helper::$entity_to_load['sample:date'] is a vague date year
      $r .= data_entry_helper::date_picker(['label' => lang::get('Date'), 'fieldname' => 'C' . ($i+1) . ':sample:date', 'validation' => $dateValidation]);
      data_entry_helper::$javascript .= "$('#ctrl-wrap-C" . ($i+1) . "-sample-date .precise-date-picker' ).prop( 'min', '".data_entry_helper::$entity_to_load['sample:date']."-01-01' );\n";
      if (date('Y') > data_entry_helper::$entity_to_load['sample:date']) {
        data_entry_helper::$javascript .= "$('#ctrl-wrap-C" . ($i+1) . "-sample-date .precise-date-picker' ).prop( 'max', '".data_entry_helper::$entity_to_load['sample:date']."-12-31' );\n";
      }
      if (!$subSampleId && $i) {
        $r .= "<p>".lang::get('You must enter the date before you can enter any further information.').'</p>';
        data_entry_helper::$javascript .= "$('#ctrl-wrap-C" . ($i+1) . "-sample-date .precise-date-picker' ).change(function(){
  myFieldset = $(this).addClass('required').closest('fieldset');
  myFieldset.find('.smp-input,[name=taxonLookupControl]').removeAttr('disabled'); // leave the count fields as are.
});\n";
      }
      if($subSampleId && count($subSamples)>1)
        $r .= "<label for='C".($i+1).":sample:deleted'>Delete this count:</label>
<input id='C".($i+1).":sample:deleted' type='checkbox' value='t' name='C".($i+1).":sample:deleted' class='subSampleDelete'><br />
<p class='helpText'>".lang::get('Setting this will delete this count when the page is saved. Only one count can be deleted at a time.').'</p>';

      foreach ($attributes as $attr) {
        if(strcasecmp($attr['untranslatedCaption'],'Unconfirmed Individuals')==0) continue;
        // output the attribute - tag it with a class & id to make it easy to find from JS.
        $attrOpts = array_merge(
          (isset($blockOptions[$attr['fieldname']]) ? $blockOptions[$attr['fieldname']] : []),
          array(
            'class' => 'smp-input smpAttr-'.($i+1),
            'id' => 'C'.($i+1).':'.$attr['fieldname'],
            'fieldname' => 'C'.($i+1).':'.$attr['fieldname'],
            'extraParams'=>$auth['read']
          ));
        // we need process validation specially: deh expects an array, we have a string...
        if(isset($attrOpts['validation']) && is_string($attrOpts['validation']))
        	$attrOpts['validation'] = explode(';', $attrOpts['validation']);
          // if there is an existing value, set it and also ensure the attribute name reflects the attribute value id.
        if (isset($subSampleId)) {
          // but have to take into account possibility that this field has been blanked out, so deleting the attribute.
          if(isset($subSamples[$i]['attr_id_sample_'.$attr['attributeId']]) && $subSamples[$i]['attr_id_sample_'.$attr['attributeId']] != ''){
            $attrOpts['fieldname'] = 'C'.($i+1).':'.$attr['fieldname'] . ':' . $subSamples[$i]['attr_id_sample_'.$attr['attributeId']];
            $attr['default'] = $subSamples[$i]['attr_sample_'.$attr['attributeId']];
          }
        } else if($i) $attrOpts['disabled'] = "disabled=\"disabled\"";
        $r .= data_entry_helper::outputAttribute($attr, $attrOpts);
      }
      $r .= '<table id="timed-counts-input-'.$i.'" class="ui-widget">';
      $r .= '<thead><tr><th class="ui-widget-header">' . lang::get('Species') . '</th><th class="ui-widget-header">' . lang::get('Count') . '</th><th class="ui-widget-header"></th></tr></thead>';
      $r .= '<tbody class="ui-widget-content">';
      // Occurrences need subsample sample_id, all attributes, ttl_id, common  name, preferred, occurrence_id
      $occs = [];
      // not very many occurrences so no need to optimise.
      if (isset($subSampleId) && $existing && count($o)>0)
        foreach ($o as $oc)
          if ($oc['sample_id'] == $subSampleId)
            $occs[] = $oc;
      for($j = 0; $j < $args['numberOfSpecies']; $j++){
        $rowClass='';
        // O<i>:<j>:<ttlid>:<occid>:<attrid>:<attrvalid>
        if(isset($occs[$j])){
          $taxon = $occs[$j]['common'].' ('.$occs[$j]['taxon'].')';
          $fieldname = 'O'.($i+1).':'.($j+1).':'.$occs[$j]['taxa_taxon_list_id'].':'.$occs[$j]['occurrence_id'].':'.$args['occurrence_attribute_id'].':'.$occs[$j]['attr_id_occurrence_'.$args['occurrence_attribute_id']];
          $value = $occs[$j]['attr_occurrence_'.$args['occurrence_attribute_id']];
        } else {
          $taxon = '';
          $fieldname = 'O'.($i+1).':'.($j+1).':--ttlid--:--occid--:'.$args['occurrence_attribute_id'].':--valid--';
          $value = '';
        }
        $r .= '<tr '.$rowClass.'>'.
               '<td><input id="TLC-'.($i+1).'-'.($j+1).'" name="taxonLookupControl" value="'.$taxon.'" '.((!$j && (!$i||$subSampleId))||$taxon ? 'class="required"' : '').' '.(!$subSampleId && $i ? 'disabled="disabled"' : '' ).'>'.((!$j && (!$i||$subSampleId))||$taxon ? '<span class="deh-required">*</span>' : '').'</td>'.
               '<td><input name="'.$fieldname.'" id="occ-'.($i+1).'-'.($j+1).'" value="'.$value.'" class="occValField integer '.((!$j && (!$i||$subSampleId))||$taxon ? 'required' : '').'" '.((!$subSampleId && $i) || ($taxon=='' && ($i || $j)) ? 'disabled="disabled"' : '').' min=0 >'.((!$j && (!$i||$subSampleId))||$taxon ? '<span class="deh-required">*</span>' : '').'</td>'.
               '<td>'.(!$j ? '' : '<div class="ui-state-default remove-button">' . lang::get('Remove this Species entry') . '</div>').'</td>'.
               '</tr>';
        $rowClass=$rowClass=='' ? 'class="alt-row"':'';
        data_entry_helper::$javascript .= "bindSpeciesAutocomplete(\"TLC-".($i+1)."-".($j+1)."\",\"occ-".($i+1)."-".($j+1)."\",\"".data_entry_helper::$base_url."index.php/services/data\", \"".$args['taxon_list_id']."\",
  indiciaData.speciesListFilterField, indiciaData.speciesListFilterValues, {\"auth_token\" : \"".$auth['read']['auth_token']."\", \"nonce\" : \"".$auth['read']['nonce']."\"}, 25);\n";
      }
      foreach ($attributes as $attr) {
        if(strcasecmp($attr['untranslatedCaption'],'Unconfirmed Individuals')) continue;
        // output the attribute - tag it with a class & id to make it easy to find from JS.
        $attrOpts = array(
            'class' => 'smp-input smpAttr-'.($i+1),
            'id' => 'C'.($i+1).':'.$attr['fieldname'],
            'fieldname' => 'C'.($i+1).':'.$attr['fieldname'],
            'extraParams'=>$auth['read']
        );
        // if there is an existing value, set it and also ensure the attribute name reflects the attribute value id.
        if (isset($subSampleId)) {
          // but have to take into account possibility that this field has been blanked out, so deleting the attribute.
          if(isset($subSamples[$i]['attr_id_sample_'.$attr['attributeId']]) && $subSamples[$i]['attr_id_sample_'.$attr['attributeId']] != ''){
            $attrOpts['fieldname'] = 'C'.($i+1).':'.$attr['fieldname'] . ':' . $subSamples[$i]['attr_id_sample_'.$attr['attributeId']];
            $attr['default'] = $subSamples[$i]['attr_sample_'.$attr['attributeId']];
          }
        } else if($i) $attrOpts['disabled'] = "disabled=\"disabled\"";
        $r .= '<tr '.$rowClass.'>'.
               '<td>'.$attr['caption'].'</td>';
        unset($attr['caption']);
        $r .= '<td>'.data_entry_helper::outputAttribute($attr, $attrOpts).'</td>'.
               '<td></td>'.
               '</tr>';
      }

      $r .= '</tbody></table>';
      if($i && !$subSampleId) $r .= '<button type="button" class="clear-button ui-state-default ui-corner-all smp-input" disabled="disabled" />'.lang::get('Clear this count').'</button>';
      $r .= '</fieldset>';
    }
    $r .= '<p>'.lang::get('In order to enter extra counts, save these first, and then view the form again for this location. Each time you do so an extra blank count will be displayed at the bottom, ready to be entered.').'</p>';
    if (lang::get('LANG_DATA_PERMISSION') !== 'LANG_DATA_PERMISSION') {
        $r .= '<p>' . lang::get('LANG_DATA_PERMISSION') . '</p>';
    }
    $r .= '<input type="submit" value="'.lang::get('Save').'" />';
    $r .= '<a href="'.$args['summary_page'].'"><button type="button" class="ui-state-default ui-corner-all" />'.lang::get('Cancel').'</button></a></form>';
    data_entry_helper::enable_validation('subsamples');
    data_entry_helper::$javascript .= "initButtons();\nprocessDeleted();\n";
    return $r;
  }

  /**
   * Handles the construction of a submission array from a set of form values.
   * @param array $values Associative array of form data values.
   * @param array $args iform parameters.
   * @return array Submission structure.
   */
  public static function get_submission($values, $args) {
    $subsampleModels = [];
    $read = array('nonce' => $values['read_nonce'], 'auth_token' => $values['read_auth_token']);
    if (!isset($values['page']) || $values['page']=='site') {
      // submitting the first page, with top level sample details
      // keep the first count date on a subsample for use later.
      // only create the subsample if this is a new top level sample: if existing, then this will already have been done.
      if(isset($values['C1:sample:date']) && !isset($values['sample:id'])){
        $sampleMethods = helper_base::get_termlist_terms(array('read'=>$read), 'indicia:sample_methods', array('Timed Count Count'));
        $smp = array('fkId' => 'parent_id',
                   'model' => array('id' => 'sample',
                     'fields' => array('survey_id' => array('value' => $values['sample:survey_id']),
                                       'website_id' => array('value' => $values['website_id']),
                                       'date' => array('value' => $values['C1:sample:date']),
                                       'sample_method_id' => array('value' => $sampleMethods[0]['id'])
                     )),
                   'copyFields' => array('entered_sref' => 'entered_sref','entered_sref_system' => 'entered_sref_system'));
        $subsampleModels[] = $smp;
      }
    } else if($values['page']=='occurrences'){
      // at this point there is a parent supersample.
      // loop from 1 to numberOfCounts, or number of existing subsamples+1, whichever is bigger.
      $subSamples = data_entry_helper::get_population_data(array(
        'table' => 'sample',
        'extraParams' => $read + array('parent_id'=>$values['sample:id'], 'view' => 'detail', 'survey_id'=>$values['sample:survey_id']),
        'nocache'=>true
      ));
      for($i = 1; $i <= max(count($subSamples)+1, $args['numberOfCounts']); $i++){
        if(isset($values['C'.$i.':sample:id']) || (isset($values['C'.$i.':sample:date']) && $values['C'.$i.':sample:date']!='')){
          $subSample = array('website_id' => $values['website_id'],
                             'survey_id' => $values['sample:survey_id']);
          $occurrences = [];
          $occModels = [];
          // separate out the sample and occurrence details for the subsample visit
          foreach($values as $field => $value){
            $parts = explode(':',$field,2);
            if($parts[0]=='C'.$i) $subSample[$parts[1]] = $value;
            if($parts[0]=='O'.$i) $occurrences[$parts[1]] = $value;
          }
          ksort($occurrences);
          foreach($occurrences as $field => $value){
            // have taken off O<i> front so is now <j>:<ttlid>:<occid>:<attrid>:<attrvalid> - sorted in <j> order, which is the occurrence order in the table.
            $parts = explode(':',$field);
            $occurrence = array('website_id' => $values['website_id']);
            if($parts[1] != '--ttlid--') $occurrence['taxa_taxon_list_id'] = $parts[1]; // can't see situation where this is not filled in
            if($parts[2] != '--occid--') $occurrence['id'] = $parts[2]; // if an existing entry.
            if($value == '') $occurrence['deleted'] = 't';
            else if($parts[4] == '--valid--') $occurrence['occAttr:'.$parts[3]] = $value; // new attribute value
            else $occurrence['occAttr:'.$parts[3].':'.$parts[4]] = $value; // existing attribute value
            if (array_key_exists('occurrence:determiner_id', $values)) $occurrence['determiner_id'] = $values['occurrence:determiner_id'];
            if (array_key_exists('occurrence:record_status', $values)) $occurrence['record_status'] = $values['occurrence:record_status'];
            if(isset($occurrence['id']) || !isset($occurrence['deleted'])){
              $occ = submission_builder::wrap($occurrence, 'occurrence');
              $occModels[] = array('fkId' => 'sample_id', 'model' => $occ);
            }
          }
          $smp = array('fkId' => 'parent_id',
            'model' => submission_builder::wrap($subSample, 'sample'),
            'copyFields' => array('entered_sref' => 'entered_sref','entered_sref_system' => 'entered_sref_system')); // from parent->to child
          if(!isset($subSample['sample:deleted']) && count($occModels)>0) $smp['model']['subModels'] = $occModels;
          $subsampleModels[] = $smp;
        }
      }
    }
    $sampleMod = submission_builder::build_submission($values, array('model' => 'sample'));
    if(count($subsampleModels)>0) {
      // $sampleMod['subModels'] could in theory already contain subModels such as sample media.
      // Merge if already populated.
      if (!empty($sampleMod['subModels'])) {
        $sampleMod['subModels'] = array_merge($sampleMod['subModels'], $subsampleModels);
      }
      else {
        $sampleMod['subModels'] = $subsampleModels;
      }
    }
    return($sampleMod);
  }

  /**
   * Override the form redirect to go back to My Walks after the grid is submitted. Leave default redirect (current page)
   * for initial submission of the parent sample.
   */
  public static function get_redirect_on_success($values, $args) {
    return  ($values['page']==='occurrences' || $values['page']==='delete') ? $args['summary_page'] : '';
  }

}
