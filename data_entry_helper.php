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
 * @package	Client
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

/**
 * Link in other required php files.
 */
require_once('helper_config.php');
require_once('helper_base.php');
require_once('submission_builder.php');

/**
 * Static helper class that provides automatic HTML and JavaScript generation for Indicia online
 * recording website data entry controls.
 * Examples include auto-complete text boxes that are populated by Indicia species lists, maps
 * for spatial reference selection and date pickers. All controls in this class support the following
 * entries in their $options array parameter:
 * <ul>
 * <li><b>label</b><br/>
 * Optional. If specified, then an HTML label containing this value is prefixed to the control HTML.</li>
 * <li><b>labelTemplate</b>
 * If you need to change the label for this control only, set this to refer to the name of an alternate template you
 * have added to the global $indicia_templates array. To change the label for all controls, you can update the value of
 * $indicia_templates['label'] before building the form.</li>
 * <li><b>helpText</b><br/>
 * Optional. Defines help text to be displayed alongside the control. The position of the text is defined by
 * helper_base::$helpTextPos, which can be set to before or after (default). The template is defined by
 * global $indicia_templates['helpText'] and can be replaced on an instance by instance basis by specifying an
 * option 'helpTextTemplate' for the control.
 * <li><b>helpTextTemplate</b>
 * If helpText is supplied but you need to change the template for this control only, set this to refer to the name of an
 * alternate template you have added to the $indicia_templates array. The template should contain a {helpText} replacement
 * string.</li>
 * <li><b>helpTextClass</b>
 * Specify helpTextClass to override the class normally applied to control help texts, which defaults to helpText.</li>
 * <li><b>tooltip</b><br/>
 * Optional. Defines help text to be displayed as a title for the input control. Display of the control's title is browser
 * dependent so you might need to enhance this functionality by adding jQuery UI tooltip to the page and calling tooltip()
 * on the $(document) object.
 * <li><b>prefixTemplate</b>
 * If you need to change the prefix for this control only, set this to refer to the name of an alternate template you
 * have added to the global $indicia_templates array. To change the prefix for all controls, you can update the value of
 * $indicia_templates['prefix'] before building the form.</li>
 * <li><b>suffixTemplate</b>
 * If you need to change the suffix for this control only, set this to refer to the name of an alternate template you
 * have added to the global $indicia_templates array. To change the suffix for all controls, you can update the value of
 * $indicia_templates['suffix'] before building the form.</li>
 * <li><b>afterControl</b>
 * Allows a piece of HTML to be specified which is inserted immediately after the control, before the suffix and
 * helpText. Ideal for inserting buttons that are to be displayed alongside a control such as a Go button
 * for a search box. Also ideal for inserting units after value input boxes (e.g. degrees, m, cm etc).</li>
 * <li><b>lockable</b>
 * Adds a padlock icon after the control which can be used to lock the control's value.
 * The value will then be remembered and redisplayed in the control each time the form is
 * shown until the control is unlocked or the end of the browser session. This option will not
 * work for password controls.</li>
 * </ul>
 *
 * @package	Client
 */
class data_entry_helper extends helper_base {

  /**
   * When reloading a form, this can be populated with the list of values to load into the controls. E.g. set it to the
   * content of $_POST after submitting a form that needs to reload.
   * @var array
   */
  public static $entity_to_load=null;

  /**
   * @var Array List of fields that are to be stored in a cookie and reloaded the next time a form is accessed. These
   * are populated by implementing a hook function called indicia_define_remembered_fields which calls set_remembered_fields.
   */
  private static $remembered_fields=null;
  /**
   *
   * @var array List of attribute ids that should be ignored when automatically drawing attributes to the page because they
   * are already output, e.g. if they are output by a radio group which shows a textbox when "other" is selected..
   */
  public static $handled_attributes=array();

  /**********************************/
  /* Start of main controls section */
  /**********************************/

  /**
   * Helper function to generate an autocomplete box from an Indicia core service query.
   * Because this generates a hidden ID control as well as a text input control, if you are outputting your own HTML label
   * then the label you associate with this control should be of the form "$id:$caption" rather than just the $id which
   * is normal for other controls. For example:
   * <code>
   * <label for='occurrence:taxa_taxon_list_id:taxon'>Taxon:</label>
   * <?php echo data_entry_helper::autocomplete(array(
   *     'fieldname' => 'occurrence:taxa_taxon_list_id',
   *     'table' => 'taxa_taxon_list',
   *     'captionField' => 'taxon',
   *     'valueField' => 'id',
   *     'extraParams' => $readAuth
   * )); ?>
   * </code>
   * Of course if you use the built in label option in the options array then this is handled for you.
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>autocomplete</b></br>
   * Defines a hidden input and a visible input, to hold the underlying database ID and to
   * allow input and display of the text search string respectively.
   * </li>
   * <li><b>autocomplete_javascript</b></br>
   * Defines the JavaScript which will be inserted onto the page in order to activate the
   * autocomplete control.
   * </li>
   * </ul>
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * Required. The name of the database field this control is bound to.</li>
   * <li><b>inputId</b><br/>
   * The ID and name given to the visible input (as opposed to the hidden input which receives
   * the looked up ID. Defaults to fieldname:captionFieldInEntity.</li>
   * <li><b>id</b><br/>
   * Optional. The id to assign to the HTML control. This should be left to its default value for
   * integration with other mapping controls to work correctly.</li>
   * <li><b>default</b><br/>
   * Optional. The default value to assign to the control. This is overridden when reloading a
   * record with existing data for this control.</li>
   * <li><b>defaultCaption</b><br/>
   * Optional. The default caption to assign to the control. This is overridden when reloading a
   * record with existing data for this control.</li>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control.</li>
   * <li><b>table</b><br/>
   * Optional. Table name to get data from for the autocomplete options.</li>
   * <li><b>report</b><br/>
   * Optional. Report name to get data from for the autocomplete options. If specified then the table option is ignored.</li>
   * <li><b>captionField</b><br/>
   * Required. Field to draw values to show in the control from.</li>
   * <li><b>captionFieldInEntity</b><br/>
   * Optional. Field to use in the loaded entity to display the caption, when reloading an existing record. Defaults
   * to the captionField.</li>
   * <li><b>valueField</b><br/>
   * Optional. Field to draw values to return from the control from. Defaults
   * to the value of captionField.</li>
   * <li><b>extraParams</b><br/>
   * Optional. Associative array of items to pass via the query string to the service. This
   * should at least contain the read authorisation array.</li>
   * <li><b>template</b><br/>
   * Optional. Name of the template entry used to build the HTML for the control. Defaults to autocomplete.</li>
   * <li><b>numValues</b><br/>
   * Optional. Number of returned values in the drop down list. Defaults to 20.</li>
   * <li><b>duplicateCheckFields</b><br/>
   * Optional. Provide an array of field names from the dataset returned from the warehouse. Any duplicates
   * based  values from this list of fields will not be added to the output.</li>
   * <li><b>simplify</b><br/>
   * Set to true to simplify the search term by removing punctuation and spaces. Use when the field
   * being searched against is also simplified.</li>
   * <li><b>warnIfNoMatch</b>
   * Should the autocomplete control warn the user if they leave the control whilst searching
   * and then nothing is matched? Default true.</li>
   * <li><b>continueOnBlur</b>
   * Should the autocomplete control continue trying to load values when the user blurs out of the control? If true
   * then tabbing out of the control will select the first match. Set to false if you intend to allow the user to enter free text which
   * is not matched to a term in the database. Default true.</li>
   * <li><b>selectMode</b>
   * Should the autocomplete simulate a select drop down control by adding a drop down arrow after the input box which, when clicked,
   * populates the drop down list with all search results to a maximum of numValues. This is similar to typing * into the box. Default false.</li>
   * <li><b>matchContains</b>
   * If true, then the search looks for matches which contain the search characters. Otherwise, the search
   * looks for matches which start with the search characters. Default false.</li>
   * </ul>
   *
   * @return string HTML to insert into the page for the autocomplete control.
   *
   * @link http://code.google.com/p/indicia/wiki/DataModel
   */
  public static function autocomplete($options) {
    global $indicia_templates;
    $options = self::check_options($options);
    if (!array_key_exists('id', $options)) $options['id']=$options['fieldname'];
    if (!array_key_exists('captionFieldInEntity', $options)) $options['captionFieldInEntity']=$options['captionField'];
    // the inputId is the id given to the text field, e.g. occurrence:taxa_taxon_list_id:taxon
    if (empty($options['inputId']))
      $options['inputId'] = $options['id'].':'.$options['captionFieldInEntity'];
    $defaultCaption = self::check_default_value($options['inputId']);

    if ( !is_null($defaultCaption) ) {
      // This computed value overrides a value passed in to the function
      $options['defaultCaption'] = $defaultCaption;
    } elseif (!isset($options['defaultCaption'])) {
      $options['defaultCaption'] = '';
    }

    if (!empty(parent::$warehouse_proxy))
      $warehouseUrl = parent::$warehouse_proxy;
    else
      $warehouseUrl = parent::$base_url;
    $options = array_merge(array(
      'template'=>'autocomplete',
      'url' => isset($options['report']) ? $warehouseUrl."index.php/services/report/requestReport" : $warehouseUrl."index.php/services/data/".$options['table'],
      // Escape the ids for jQuery selectors
      'escaped_input_id' => self::jq_esc($options['inputId']),
      'escaped_id' => self::jq_esc($options['id']),
      'max' => array_key_exists('numValues', $options) ? ', max : '.$options['numValues'] : '',
      'formatFunction' => 'function(item) { return item.{captionField}; }',
      'simplify' => (isset($options['simplify']) && $options['simplify']) ? 'true' : 'false',
      'warnIfNoMatch' => true,
      'continueOnBlur' => true,
      'selectMode' => false,
      'default' => '',
      'matchContains' => false
    ), $options);
    if (isset($options['report'])) {
      $options['extraParams']['report'] = $options['report'].'.xml';
      $options['extraParams']['reportSource'] = 'local';
    }
    $options['warnIfNoMatch'] = $options['warnIfNoMatch'] ? 'true' : 'false';
    $options['continueOnBlur'] = $options['continueOnBlur'] ? 'true' : 'false';
    $options['selectMode'] = $options['selectMode'] ? 'true' : 'false';
    $options['matchContains'] = $options['matchContains'] ? 'true' : 'false';
    self::add_resource('autocomplete');
    // Escape the id for jQuery selectors
    $escaped_id=self::jq_esc($options['id']);
    // Do stuff with extraParams
    $sParams = '';
    foreach ($options['extraParams'] as $a => $b){
      // escape single quotes
      $b = str_replace("'","\'",$b);
      $sParams .= "$a : '$b',";
    }
    // lop the comma off the end
    $options['sParams'] = substr($sParams, 0, -1);
    $options['extraParams']=null;
    if (!empty($options['duplicateCheckFields'])) {
      $duplicateCheckFields = 'item.'.implode(" + '#' + item.", $options['duplicateCheckFields']);
      $options['duplicateCheck']="$.inArray($duplicateCheckFields, done)===-1";
      $options['storeDuplicates']="done.push($duplicateCheckFields);";
      unset($options['duplicateCheckFields']);
    } else {
      // disable duplicate checking
      $options['duplicateCheck']='true';
      $options['storeDuplicates']='';
    }
    self::$javascript .= self::apply_replacements_to_template($indicia_templates['autocomplete_javascript'], $options);
    $r = self::apply_template($options['template'], $options);
    return $r;
  }

  /**
   * A control that can be used to output a multi-value text attribute where the text value holds a json record
   * structure. The control is a simple grid with each row representing a single attribute value and each column representing
   * a field in the JSON stored in the value.
   *
   * @param array $options Options array with the following possibilities:
   * * **fieldname** - The fieldname of the attribute, e.g. smpAttr:10.
   * **defaultRows** - Number of rows to show in the grid by default. An Add Another button is available to add more.
   *   Defaults to 3.
   * * **columns** - An array defining the columns available in the grid which map to fields in the JSON stored for each
   *   value. The array key is the column name and the value is a sub-array with a column definition. The column
   *   definition can contain the following:
   *   * label - The column label. Will be automatically translated.
   *   * class - A class given to the column label.
   *   * datatype - The column's data type. Currently only text and lookup is supported.
   *   * termlist_id - If datatype=lookup, then provide the termlist_id of the list to load terms for as options in the
   *     control.
   *   * unit - An optional unit label to display after the control (e.g. 'cm', 'kg').
   *   * regex - A regular expression which validates the controls input value.
   *   * default - default value for this control used for new rows
   * * **default** - An array of default values loaded for existing data, as obtained by a call to getAttributes.
   * * **rowCountControl** - Pass the ID of an input control that will contain an integer value to define the number of
   *   rows in the grid. If not set, then a button is shown allowing additional rows to be added.
   * * **encoding** - encoding used when saving the array for a row to the database. Default is json. If not json then
   *   the provided character acts as a separator used to join the value list together.
   */
  public static function complex_attr_grid($options) {
    self::add_resource('complexAttrGrid');
    $options = array_merge(array(
      'defaultRows'=>3,
      'columns'=>array('x'=>array('label'=>'x','datatype'=>'text','unit'=>'cm','regex'=>'/^[0-9]+$/'),
        'y'=>array('label'=>'y','datatype'=>'lookup','termlist_id'=>'5')),
      'default'=>array(),
      'deleteRows'=>false,
      'rowCountControl'=>'',
      'encoding'=>'json'
    ), $options);
    list($attrTypeTag, $attrId) = explode(':', $options['fieldname']);
    if (preg_match('/\[\]$/', $attrId))
      $attrId=str_replace('[]', '', $attrId);
    else
      return 'The complex attribute grid control must be used with a mult-value attribute.';
    $r = '<thead><tr>';
    $lookupData = array();
    $thRow2 = '';
    foreach ($options['columns'] as $idx => &$def) {
      // whilst we are iterating the columns, may as well do some setup.
      // apply i18n to unit now, as it will be used in JS later
      if (!empty($def['unit']))
        $def['unit'] = lang::get($def['unit']);
      if ($def['datatype']==='lookup') {
        $minified = array();
        // no matter if the lookup comes from the db, or from a local array, we want it in the same minimal format
        if (!empty($def['termlist_id'])) {
          $termlistData = self::get_population_data(array(
            'table'=>'termlists_term',
            'extraParams'=>$options['extraParams'] + array('termlist_id'=>$def['termlist_id'], 'view'=>'cache', 'orderby'=>isset($def['orderby'])?$def['orderby']:'term')
          ));
          foreach ($termlistData as $term) {
            $minified[] = array($term['id'], $term['term']);
          }
        }
        elseif (isset($def['lookupValues'])) {
          foreach ($def['lookupValues'] as $id => $term) {
            $minified[] = array($id, $term);
          }
        }
        foreach ($minified as $tokens) {
          if (isset($def['control']) && $def['control']==='checkbox_group')
            $thRow2 .= "<th>$minified[1]</th>";
        }
        $lookupData["tl$idx"] = $minified;
        self::$javascript .= "indiciaData.tl$idx=".json_encode($minified).";\n";
      }
      // checkbox groups output a second row of cells for each checkbox label
      $rowspan = isset($def['control']) && $def['control']==='checkbox_group' ? 1 : 2;
      $colspan = isset($def['control']) && $def['control']==='checkbox_group' ? count($termlistData) : 1;
      // Add default class if none provided.
      $class = isset($def['class']) ? $def['class'] : 'complex-attr-grid-col' . $idx;
      $r .= "<th rowspan=\"$rowspan\" colspan=\"$colspan\" class=\"$class\">" . lang::get($def['label']) . '</th>';
    }
    self::$javascript .= "indiciaData.langPleaseSelect='".lang::get('Please select')."'\n";
    self::$javascript .= "indiciaData.langCantRemoveEnoughRows='".lang::get('Please clear the values in some more rows before trying to reduce the number of rows further.')."'\n";
    // need to unset the variable used in &$def, otherwise it doesn't work in the next iterator.
    unset($def);
    $jsData = array('cols'=>$options['columns'],'rowCount'=>$options['defaultRows'],
      'rowCountControl'=>$options['rowCountControl'],'deleteRows'=>$options['deleteRows']);
    self::$javascript .= "indiciaData['complexAttrGrid-$attrTypeTag-$attrId']=".json_encode($jsData).";\n";
    // Add delete column and end tr.
    $r .= '<th rowspan="2" class="complex-attr-grid-col-del"></th></tr>';
    // Add second header row then end thead.
    $r .= "<tr>$thRow2</tr></thead>";
    $r .= '<tbody>';
    $rowCount = $options['defaultRows'] > count($options['default']) ? $options['defaultRows'] : count($options['default']);
    $extraCols=0;
    for ($i = 0; $i<=$rowCount-1; $i++) {
      $class=($i % 2 === 1) ? '' : ' class="odd"';
      $r .= "<tr$class>";
      if (isset($options['default'][$i]))
        $defaults = $options['encoding'] === 'json'
          ? json_decode($options['default'][$i]['default'], true)
          : explode($options['encoding'], $options['default'][$i]['default']);
      else
        $defaults = array();
      foreach ($options['columns'] as $idx => $def) {
        if (isset($options['default'][$i]))
          $fieldnamePrefix = str_replace('Attr:', 'Attr+:', $options['default'][$i]['fieldname']);
        else
          $fieldnamePrefix = "$attrTypeTag+:$attrId:";
        $fieldname="$fieldnamePrefix:$i:$idx";
        $default = isset(self::$entity_to_load[$fieldname]) ? self::$entity_to_load[$fieldname] :
          (array_key_exists($idx, $defaults) ? $defaults[$idx] :
            (isset($def['default']) ? $def['default'] : ''));
        $r .= "<td>";
        if ($def['datatype']==='lookup' && isset($def['control']) && $def['control']) {
          $checkboxes=array();
          // array field
          $fieldname .= '[]';
          foreach ($lookupData["tl$idx"] as $term) {
            $checked = is_array($default) && in_array($term[0], $default) ? ' checked="checked"' : '';
            $checkboxes[] = "<input title=\"$term[1]\" type=\"checkbox\" name=\"$fieldname\" value=\"$term[0]:$term[1]\"$checked>";
          }
          $r .= implode('</td><td>', $checkboxes);
          $extraCols .= count($checkboxes)-1;
        } elseif ($def['datatype']==='lookup') {
          $r .= "<select name=\"$fieldname\"><option value=''>&lt;".lang::get('Please select')."&gt;</option>";
          foreach ($lookupData["tl$idx"] as $term) {
            $selected = $default=="$term[0]" ? ' selected="selected"' : '';
            $r .= "<option value=\"$term[0]:$term[1]\"$selected>$term[1]</option>";
          }
          $r .= "</select>";
        } else {
          $class = empty($def['regex']) ? '' : ' class="{pattern:'.$def['regex'].'}"';
          $r .= "<input type=\"text\" name=\"$fieldname\" value=\"$default\"$class/>";
        }
        if (!empty($def['unit']))
          $r .= '<span class="unit">'.lang::get($def['unit']).'</span>';
        $r .= '</td>';
      }
      $r .= "<td><input type=\"hidden\" name=\"$fieldnamePrefix:$i:deleted\" value=\"f\" class=\"delete-flag\"/>";
      if (empty($options['rowCountControl']))
        $r .= "<span class=\"ind-delete-icon\"/>";
      $r .= "</td></tr>";
    }
    $r .= '</tbody>';
    if (empty($options['rowCountControl'])) {
      $r .= '<tfoot>';
      $r .= '<tr><td colspan="'.(count($options['columns'])+1+$extraCols).'"><button class="add-btn" type="button">Add another</button></td></tr>';
      $r .= '</tfoot>';
    } else {
      $escaped = str_replace(':', '\\\\:', $options['rowCountControl']);
      data_entry_helper::$javascript .=
        "$('#$escaped').val($rowCount);
$('#$escaped').change(function(e) {
  changeComplexGridRowCount('$escaped', '$attrTypeTag', '$attrId');
});\n";
    }
    // wrap in a table template
    global $indicia_templates;
    $r = str_replace(
      array('{class}', '{id}', '{content}'),
      array(' class="complex-attr-grid"', " id=\"complex-attr-grid-$attrTypeTag-$attrId\"", $r),
      $indicia_templates['data-input-table']);
    $r .= "<input type=\"hidden\" name=\"complex-attr-grid-encoding-$attrTypeTag-$attrId\" value=\"$options[encoding]\" />\n";
    return $r;
  }

  /**
   * Helper function to generate a sub list UI control. This control allows a user to create a new list
   * by selecting some items from the caption 'field' of an existing database table while
   * adding some new items.
   * The resulting list is submitted and the new items are added to the existing table
   * as skeleton entries while the id values for the items are stored as a custom attribute.
   *
   * An example usage would be to associate a list of people with a sample or location.
   *
   * @param array $options (deprecated argument list not supported).
   * Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * Required. The name of the database field this control is bound to. This must be a custom attributes
   * field of type integer which supports multiple values</li>
   * <li><b>table</b><br/>
   * Required. Table name to get data from for the autocomplete options. The control will
   * use the captionField from this table</li>
   * <li><b>captionField</b><br/>
   * Required if addToTable is false. Field to draw values from to show in the control from.
   * If addToTable is true, this setting will be ignored and 'caption' will always be used.</li>
   * <li><b>extraParams</b><br/>
   * Required. Associative array of items to pass via the query string to the service. This
   * should at least contain the read authorisation array.</li>
   * <li><b>id</b><br/>
   * Optional. The id to assign to the HTML control. Base value defaults to fieldname, but
   * this is a compound control and the many sub-controls have id values with additiobnal suffixes.</li>
   * <li><b>default</b><br/>
   * Optional. An array of items to load into the control on page startup. Each entry must be an associative array
   * with keys fieldname, caption and default.</li>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control.</li>
   * <li><b>numValues</b><br/>
   * Optional. Number of returned values in the drop down list. Defaults to 20.</li>
   * <li><b>addOnSelect TODO</b><br/>
   * Optional. Boolean, if true, matched items from the autocomplete control are automatically
   * added to the list when selected. Defaults to false.</li>
   * <li><b>addToTable</b><br/>
   * Optional. Boolean, if false, only existing items from the table can be selected, and no rows can be added.
   * The control then acts like a multi-value autocomplete and submits a list of ID values for the chosen items.
   * If true, the control allows new values to be added and inserts them into the source table.
   * Defaults to true.</li>
   * <li><b>selectMode</b>
   * Should the autocomplete simulate a select drop down control by adding a drop down arrow after the input box which, when clicked,
   * populates the drop down list with all search results to a maximum of numValues. This is similar to typing * into the box. Default false.
   * </li>
   * </ul>
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>sub_list</b></br>
   * Defines the search input, plus container element for the list of items which will be added.
   * </li>
   * <li><b>sub_list_item</b></br>
   * Defines the template for a single item added to the list.
   * </li>
   * <li><b>sub_list_add</b></br>
   * Defines hidden inputs to insert onto the page which contain the items to add to the
   * sublist, when loading existing records.
   * </li>
   * <li><b>sub_list_javascript</b></br>
   * Defines the JavaScript added to the page to implement the click handling for the various
   * butons.
   * </li>
   * <li><b>autocompleteControl</b></br>
   * Defines the name of the data entry helper control function used to provide the autocomplete
   * control. Defaults to autocomplete but can be swapped to species_autocomplete for species name
   * lookup for example.
   * </li>
   * </ul>
   *
   * @return string HTML to insert into the page for the sub_list control.
   *
   */
  public static function sub_list($options) {
    global $indicia_templates;
    static $sub_list_idx=0; // unique ID for all sublists
    // checks essential options, uses fieldname as id default and
    // loads defaults if error or edit
    $options = self::check_options($options);
    $options = array_merge(array(
      'addToTable' => true,
      'autocompleteControl' => 'autocomplete'
    ), $options);
    if ($options['addToTable']===true) {
      // we can only work with the caption field
      $options['captionField'] = 'caption';
    }
    // this control submits many values with the same control name so add [] to fieldname
    // so PHP puts multiple submitted values in an array
    if (substr($options['fieldname'],-2) !='[]')
      $options['fieldname'] .= '[]';

    if ($options['addToTable']===true) {
      // prepare options for updating the source table
      $options['basefieldname'] = substr($options['fieldname'], 0, strlen($options['fieldname'])-2);
      if (preg_match('/^[a-z]{3}Attr\:[1-9][0-9]*$/', $options['basefieldname'])) {
        switch (substr($options['basefieldname'],0,3)) {
          case 'loc':
            $options['mainEntity'] = 'location';
            break;
          case 'occ':
            $options['mainEntity'] = 'occurrence';
            break;
          case 'smp':
            $options['mainEntity'] = 'sample';
            break;
          case 'srv':
            $options['mainEntity'] = 'survey';
            break;
          case 'psn':
            $options['mainEntity'] = 'person';
            break;
          default:
            $options['mainEntity'] = '';
        }
      }
      if (empty($options['mainEntity'])) {
        // addToTable only works with custom attributes
        // TODO, raise an error to developer
      }
      $options['subListAdd'] = self::mergeParamsIntoTemplate($options, 'sub_list_add');
    } else {
      $options['subListAdd'] = '';
    }

    // prepare embedded search control for add bar panel
    $list_options = $options;
    unset($list_options['helpText']);
    $list_options['id'] = $list_options['id'].':search';
    $list_options['fieldname'] = $list_options['id'];
    $list_options['default']='';
    $list_options['lockable']=null;
    $list_options['label'] = null;
    $list_options['controlWrapTemplate'] = 'justControl';
    if (!empty($options['selectMode']) && $options['selectMode'])
      $list_options['selectMode']=true;
    // set up add panel
    $control = $options['autocompleteControl'];
    $options['panel_control'] = self::$control($list_options);

    // prepare other main control options
    $options['inputId'] = $options['id'].':'.$options['captionField'];
    $options = array_merge(array(
      'template' => 'sub_list',
      // Escape the ids for jQuery selectors
      'escaped_input_id' => self::jq_esc($options['inputId']),
      'escaped_id' => self::jq_esc($options['id']),
      'escaped_captionField' => self::jq_esc($options['captionField'])
    ), $options);

    // set up javascript
    $options['subListItem'] = str_replace(array('{caption}', '{value}', '{fieldname}'),
      array('\'+caption+\'', '\'+value+\'', $options['fieldname']),
      $indicia_templates['sub_list_item']);
    $options['idx']=$sub_list_idx;
    self::$javascript .= self::apply_replacements_to_template($indicia_templates['sub_list_javascript'], $options);
    // load any default values for list items into display and hidden lists
    $items = "";
    $r = '';
    if (array_key_exists('default', $options) && is_array($options['default'])) {
      foreach ($options['default'] as $item) {
        $items .= str_replace(array('{caption}', '{value}', '{fieldname}'),
          array($item['caption'], $item['default'], $item['fieldname']),
          $indicia_templates['sub_list_item']);
        // a hidden input to put a blank in the submission if it is deleted
        $r .= "<input type=\"hidden\" value=\"\" name=\"$item[fieldname]\">";
      }
    }
    $options['items'] = $items;

    // layout the control
    $r .= self::apply_template($options['template'], $options);
    $sub_list_idx++;
    return $r;
  }

  /**
   * Helper function to output an HTML checkbox control. This includes re-loading of existing values
   * and displaying of validation error messages.
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>checkbox</b></br>
   * HTML template for the checkbox.
   * </li>
   * </ul>
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * Required. The name of the database field this control is bound to.</li>
   * <li><b>id</b><br/>
   * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
   * <li><b>default</b><br/>
   * Optional. The default value to assign to the control. This is overridden when reloading a
   * record with existing data for this control.</li>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control.</li>
   * <li><b>template</b><br/>
   * Optional. Name of the template entry used to build the HTML for the control. Defaults to checkbox.</li>
   * </ul>
   *
   * @return string HTML to insert into the page for the checkbox control.
   */
  public static function checkbox($options) {
    $options = self::check_options($options);
    $default = isset($options['default']) ? $options['default'] : '';
    $value = self::check_default_value($options['fieldname'], $default);
    $options['checked'] = ($value==='on' || $value === 1 || $value === '1' || $value==='t' || $value===true) ? ' checked="checked"' : '';
    $options['template'] = array_key_exists('template', $options) ? $options['template'] : 'checkbox';
    return self::apply_template($options['template'], $options);
  }


  /**
   * Helper function to output a checkbox for controlling training mode.
   * Occurrences submitted in training mode can be kept apart from normal records.
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>training</b></br>
   * HTML template for checkbox with hidden input.
   * </li>
   * </ul>
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>id</b><br/>
   * Optional. The id to assign to the HTML control. If not assigned 'training' is used.</li>
   * <li><b>default</b><br/>
   * Optional. Boolean. The default value to assign to the control Defaults to true i.e to training mode.
   * This is overridden when reloading a record with existing data for this control.</li>
   * <li><b>disabled</b><br/>
   * Optional. Boolean. Determines whether the user is prevented from changing the value.
   * Defaults to true i.e control is disabled.</li>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control.</li>
   * <li><b>template</b><br/>
   * Optional. Name of the template entry used to build the HTML for the control. Defaults to training.</li>
   * </ul>
   *
   * @return string HTML to insert into the page for the checkbox control.
   */
  public static function training($options) {
    // The fieldname is fixed for the specific purpose of this control
    $options['fieldname'] = 'training';
    // Apply default options which may be overriden by supplied values
    $options = array_merge(array(
      'default' => true,
      'disabled' => true,
      'label' => 'Training mode',
      'helpText' => 'Records submitted in training mode are segregated from genuine records. ',
      'template'=>'training'
    ), $options);
    // Apply standard options and update default value if loading existing record
    $options = self::check_options($options);
    // Be flexible about the value to accept as meaning checked.
    $v = $options['default'];
    if ($v==='on' || $v === 1 || $v === '1' || $v === 't' || $v === true) {
      $options['checked'] = ' checked="checked"';
      $options['default'] = 1;
    } else {
      $options['checked'] = '';
      $options['default'] = 0;
    }
    // A disabled or unchecked checkbox sends no value so hidden input has to contain value.
    if ($options['disabled']) {
      $options['disabled'] = ' disabled="disabled"';
      $options['hiddenValue'] = $options['default'];
    } else {
      $options['disabled'] = '';
      $options['hiddenValue'] = 0;
    }
    return self::apply_template($options['template'], $options);
  }

  /**
   * Helper function to generate a list of checkboxes from a Indicia core service query.
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * Required. The name of the database field this control is bound to.</li>
   * <li><b>id</b><br/>
   * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
   * <li><b>default</b><br/>
   * Optional. The default value to assign to the control. This is overridden when reloading a
   * record with existing data for this control.</li>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control. Defaults to inline when not sortable.</li>
   * <li><b>table</b><br/>
   * Required. Table name to get data from for the select options.</li>
   * <li><b>captionField</b><br/>
   * Optional. Field to draw values to show in the control from. Required unless lookupValues is specified.</li>
   * <li><b>valueField</b><br/>
   * Optional. Field to draw values to return from the control from. Defaults
   * to the value of captionField. </li>
   * <li><b>otherItemId</b><br/>
   * Optional. The termlists_terms id of the checkbox_group item that will be considered as "Other".
   * When this checkbox is selected then another textbox is displayed allowing specific details relating to the
   * Other item to be entered. The otherValueAttrId and otherTextboxLabel options must be specified to use this feature.</li>
   * <li><b>otherValueAttrId</b><br/>
   * Optional. The attribute id where the "Other" text will be stored, e.g. smpAttr:10. See otherItemId option description.</li>
   * <li><b>otherTextboxLabel</b><br/>
   * Optional. The label for the "Other" textbox. See otherItemId, otherValueAttrId option descriptions.</li>
   * <li><b>extraParams</b><br/>
   * Optional. Associative array of items to pass via the query string to the service. This
   * should at least contain the read authorisation array.</li>
   * <li><b>lookupValues</b><br/>
   * If the group is to be populated with a fixed list of values, rather than via a service call, then the
   * values can be passed into this parameter as an associated array of key=>caption.</li>
   * <li><b>cachetimeout</b><br/>
   * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
   * after a request for data to the Indicia Warehouse before a new request will refetch the data,
   * rather than use a locally stored (cached) copy of the previous request. This speeds things up
   * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
   * if this is not specified then 1 hour.</li>
   * <li><b>template</b><br/>
   * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
   * for the outer control.</li>
   * <li><b>itemTemplate</b><br/>
   * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
   * for each item in the control.</li>
   * <li><b>captionTemplate</b><br/>
   * Optional and only relevant when loading content from a data service call. Specifies the template used to build the caption,
   * with each database field represented as {fieldname}.</li>
   * <li><b>sortable</b></br>
   * Set to true to allow drag sorting of the list of checkboxes. If sortable, then the layout will be a vertical
   * column of checkboxes rather than inline.
   * </li>
   * </ul>
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>check_or_radio_group</b></br>
   * Container element for the group of checkboxes.
   * </li>
   * <li><b>check_or_radio_group_item</b></br>
   * Template for the HTML element used for each item in the group.
   * </li>
   * </ul>
   *
   * @return string HTML to insert into the page for the group of checkboxes.
   */
  public static function checkbox_group($options) {
    $options = self::check_options($options);
    $options = array_merge(array(
      'class'=>empty($options['sortable']) || !$options['sortable'] ? 'inline' : ''
    ), $options);
    if (substr($options['fieldname'],-2) !='[]')
      $options['fieldname'] .= '[]';
    if (!empty($options['sortable']) && $options['sortable']) {
      self::$javascript .= "$('#$options[id]').sortable();\n";
      self::$javascript .= "$('#$options[id]').disableSelection();\n";
      // resort the available options into the saved order
      if (!empty($options['default']) && !empty($options['lookupValues'])) {
        $sorted = array();
        // First copy over the ones that are ticked, in order
        foreach ($options['default'] as $option) {
          if (!empty($options['lookupValues'][$option]))
            $sorted[$option] = $options['lookupValues'][$option];
        }
        // now the unticked ones in original order
        foreach ($options['lookupValues'] as $option => $caption) {
          if (!isset($sorted[$option]))
            $sorted[$option]=$caption;
        }
        $options['lookupValues']=$sorted;
      }

    }
    return self::check_or_radio_group($options, 'checkbox');
  }

  /**
   * Helper function to insert a date picker control.
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>date_picker</b></br>
   * HTML The output of this controlfor the text input element used for the date picker. Other functionality is added
   * using JavaScript.
   * </li>
   * </ul>
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * Required. The name of the database field this control is bound to, for example 'sample:date'.</li>
   * <li><b>id</b><br/>
   * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
   * <li><b>default</b><br/>
   * Optional. The default value to assign to the control. This is overridden when reloading a
   * record with existing data for this control.</li>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control.</li>
   * <li><b>allowFuture</b><br/>
   * Optional. If true, then future dates are allowed. Default is false.</li>
   * <li><b>dateFormat</b><br/>
   * Optional. Allows the date format string to be set, which must match a date format that can be parsed by the JavaScript Date object.
   * Default is dd/mm/yy.</li>
   * <li><b>allowVagueDates</b><br/>
   * Optional. Set to true to enable vague date input, which disables client side validation for standard date input formats.</li>
   * <li><b>showButton</b><br/>
   * Optional. Set to true to show a button which must be clicked to drop down the picker. Defaults to false unless allowVagueDates is true
   * as inputting a vague date without the button is confusing.</li>
   * <li><b>buttonText</b><br/>
   * Optional. If showButton is true, this text will be shown as the 'alt' text for the buttom image.</li>
   * <li><b>placeHolder</b><br/>
   * Optional. Control the placeholder text shown in the text box before a value has been added.</li>
   * </ul>
   *
   * @return string HTML to insert into the page for the date picker control.
   */
  public static function date_picker($options) {
    $options = self::check_options($options);
    $options = array_merge(array(
      'dateFormat'=>'dd/mm/yy',
      'allowVagueDates'=>false,
      'default'=>''
    ), $options);
    if (!isset($options['showButton']))
      // vague dates best with the button
      $options['showButton']=$options['allowVagueDates'];
    $vague=$options['allowVagueDates'] ? ' vague' : '';
    if (!isset($options['placeholder']))
      $options['placeholder'] = $options['showButton'] ? lang::get("Pick or type a$vague date") : lang::get('Click here');
    self::add_resource('jquery_ui');
    $escaped_id=str_replace(':','\\\\:',$options['id']);
    // Don't set js up for the datepicker in the clonable row for the species checklist grid
    if ($escaped_id!='{fieldname}') {
      // should include even if validated_form_id is null, as could be doing this via AJAX.
      if (!$options['allowVagueDates']) {
        self::$javascript .= "if (typeof jQuery.validator !== \"undefined\") {
  jQuery.validator.addMethod('customDate',
    function(value, element) {
      // parseDate throws exception if the value is invalid
      try{jQuery.datepicker.parseDate( '".$options['dateFormat']."', value);return true;}
      catch(e){return false;}
    }, '".lang::get('Please enter a valid date')."'
  );
}\n";
      }
      if ($options['showButton']) {
        $imgPath = empty(self::$images_path) ? self::relative_client_helper_path()."../media/images/" : self::$images_path;
        $imgPath .= 'nuvola/date-16px.png';
        if (!empty($options['buttonText'])) {
          $buttonText = $options['buttonText'];
        } else {
          $buttonText = $options['allowVagueDates'] ? lang::get('To enter an exact date, click here. To enter a vague date, type it into the text box') : lang::get('Click here to pick a date');
        }
        $button = ",\n    showOn: 'button',\n    buttonImage: '$imgPath',\n    buttonText: '$buttonText'";
      } else
        $button='';
      self::$javascript .= "jQuery('#$escaped_id').datepicker({
    dateFormat : '".$options['dateFormat']."',
    changeMonth: true,
    changeYear: true,
    constrainInput: false $button";
      // Filter out future dates
      if (!array_key_exists('allowFuture', $options) || $options['allowFuture']==false) {
        self::$javascript .= ",
    maxDate: '0'";
      }
      // If the validation plugin is running, we need to trigger it when the datepicker closes.
      if (self::$validated_form_id) {
        self::$javascript .= ",
    onClose: function() {
      $(this).valid();
    }";
      }
      self::$javascript .= "\n});\n";
      self::$javascript .= "$('button.ui-datepicker-trigger').addClass('ui-state-default');\n";
    }
    // Check for the special default value of today
    if (isset($options['default']) && $options['default']=='today')
      $options['default'] = date('d/m/Y');

    // Enforce a class on the control called date
    if (!array_key_exists('class', $options)) {
      $options['class']='';
    }
    return self::apply_template('date_picker', $options);
  }

  /**
   * Outputs a file upload control suitable for linking images to records.
   * The control allows selection of multiple files, and depending on the browser functionality it gives progress feedback.
   * The control uses Silverlight, Flash or HTML5 to enhance the functionality where available. The output of the control
   * can be configured by changing the content of the templates called file_box, file_box_initial_file_info,
   * file_box_uploaded_image and button.
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>table</b><br/>
   * Name of the image table to upload images into, e.g. occurrence_medium, location_medium, sample_medium or taxon_medium.
   * Defaults to occurrence_medium.
   * </li>
   * <li><b>loadExistingRecordKey</b><br/>
   * Optional prefix for the information in the data_entry_helper::$entity_to_load to use for loading any existing images.
   * Defaults to use the table option.
   * </li>
   * <li><b>id</b><br/>
   * Optional. Provide a unique identifier for this image uploader control if more than one are required on the page.
   * </li>
   * <li><b>SubType</b><br/>
   * Optional. The name of the image sub-type to limit the file box to e.g. Image:Local:Sketch
   * </li>
   * <li><b>caption</b><br/>
   * Caption to display at the top of the uploader box. Defaults to the translated string for "Files".
   * </li>
   * <li><b>uploadSelectBtnCaption</b><br/>
   * Set this to override the caption for the button for selecting files to upload.
   * </li>
   * <li><b>uploadStartBtnCaption</b><br/>
   * Set this to override the caption for the start upload button, which is only visible if autoUpload is false.
   * </li>
   * <li><b>useFancybox</b><br/>
   * Defaults to true. If true, then image previews use the Fancybox plugin to display a "lightbox" effect when clicked on.
   * </li>
   * <li><b>imageWidth</b><br/>
   * Defaults to 200. Number of pixels wide the image previews should be.
   * </li>
   * <li><b>resizeWidth</b><br/>
   * If set, then the file will be resized before upload using this as the maximum pixels width.
   * </li>
   * <li><b>resizeHeight</b><br/>
   * If set, then the file will be resized before upload using this as the maximum pixels height.
   * </li>
   * <li><b>resizeQuality</b><br/>
   * Defines the quality of the resize operation (from 1 to 100). Has no effect unless either resizeWidth or resizeHeight are non-zero.
   * </li>
   * <li><b>upload</b><br/>
   * Boolean, defaults to true.
   * </li>
   * <li><b>maxFileCount</b><br/>
   * Maximum number of files to allow upload for. Defaults to 4. Set to false to allow unlimited files.
   * </li>
   * <li><b>maxUploadSize</b><br/>
   * Maximum file size to allow in bytes. This limits file selection. PHP settings on
   * server may limit upload.
   * </li>
   * <li><b>autoupload</b><br/>
   * Defaults to true. If false, then a button is displayed which the user must click to initiate upload of the files
   * currently in the queue.
   * </li>
   * <li><b>msgUploadError</b><br/>
   * Use this to override the message displayed for a generic file upload error.
   * </li>
   * <li><b>msgFileTooBig</b><br/>
   * Use this to override the message displayed when the file is larger than the size limit allowed on the Warehouse.
   * </li>
   * <li><b>msgTooManyFiles</b><br/>
   * Use this to override the message displayed when attempting to upload more files than the maxFileCount allows. Use a
   * replacement string [0] to specify the maxFileCount value.
   * </li>
   * <li><b>uploadScript</b><br/>
   * Specify the script used to handle image uploads on the server (relative to the client_helpers folder). You should not
   * normally need to change this. Defaults to upload.php.
   * </li>
   * <li><b>runtimes</b><br/>
   * Array of runtimes that the file upload component will use in order of priority. Defaults to
   * array('html5','flash','silverlight','html4'), though flash is removed for Internet Explorer 6. You
   * should not normally need to change this.
   * </li>
   * <li><b>destinationFolder</b><br/>
   * Override the destination folder for uploaded files. You should not normally need to change this.
   * </li>
   * <li><b>codeGenerated</b>
   * If set to all (default), then this returns the HTML required and also inserts JavaScript in the document onload event. However, if you
   * need to delay the loading of the control until a certain event, e.g. when a radio button is checked, then this can be set
   * to php to return just the php and ignore the JavaScript, or js to return the JavaScript instead of inserting it into
   * document onload, in which case the php is ignored. this allows you to attach the JavaScript to any event you need to.
   * </li>
   * <li><b>tabDiv</b><br/>
   * If loading this control onto a set of tabs, specify the tab control's div ID here. This allows the control to
   * automatically generate code which only generates the uploader when the tab is shown, reducing problems in certain
   * runtimes. This has no effect if codeGenerated is not left to the default state of all.
   * </li>
   * </ul>
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>file_box</b></br>
   * Outputs the HTML container which will contain the upload button and images.
   * </li>
   * <li><b>file_box_initial_file_info</b></br>
   * HTML which provides the outer container for each displayed image, including the header and
   * remove file button. Has an element with class set to media-wrapper into which images
   * themselves will be inserted.
   * </li>
   * <li><b>file_box_uploaded_image</b></br>
   * Template for the HTML for each uploaded image, including the image, caption input
   * and hidden inputs to define the link to the database. Will be inserted into the
   * file_box_initial_file_info template's media-wrapper element.
   * </li>
   * <li><b>button</b></br>
   * Template for the buttons used.
   * </li>
   * <li><b>readAuth</b><br/>
   * Optional. Read authentication tokens for the Indicia warehouse if using the
   * add_link_popup.</li>
   * </ul>
   *
   * @todo select file button pointer overriden by the flash shim
   * @todo if using a normal file input, after validation, the input needs to show that the file upload has worked.
   * @todo Cleanup uploaded files that never got submitted because of validation failure elsewhere.
   */
  public static function file_box($options) {
    global $indicia_templates;
    // Upload directory defaults to client_helpers/upload, but can be overriden.
    $interim_image_folder = isset(parent::$interim_image_folder) ? parent::$interim_image_folder : 'upload/';
    $relpath = self::getRootFolder() . self::client_helper_path();
    //If a subType option is supplied, it means we only want to load a particular media type, not just any old media associated with the sample
    if (!empty($options['subType']))
      self::$upload_file_types[$options['subType']]=self::$upload_file_types['image'];
    // Allow options to be defaulted and overridden
    $protocol = empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off' ? 'http' : 'https';
    $defaults = array(
      'id' => 'default',
      'upload' => true,
      'maxFileCount' => 4,
      'autoupload' => false,
      'msgUploadError' => lang::get('upload error'),
      'msgFileTooBig' => lang::get('file too big for warehouse'),
      'runtimes' => array('html5','flash','silverlight','html4'),
      'autoupload' => true,
      'imageWidth' => 200,
      'uploadScript' => "$protocol://$_SERVER[HTTP_HOST]/" . self::getRootFolder() . self::relative_client_helper_path() . 'upload.php',
      'destinationFolder' => $relpath . $interim_image_folder,
      'finalImageFolder' => self::get_uploaded_image_folder(),
      'jsPath' => self::$js_path,
      'buttonTemplate' => $indicia_templates['button'],
      'table' => 'occurrence_medium',
      'maxUploadSize' => self::convert_to_bytes(isset(parent::$maxUploadSize) ? parent::$maxUploadSize : '4M'),
      'codeGenerated' => 'all',
      'mediaTypes' => !empty($options['subType']) ? array($options['subType']) : array('Image:Local'),
      'fileTypes' => (object)self::$upload_file_types,
      'imgPath' => empty(self::$images_path) ? self::relative_client_helper_path()."../media/images/" : self::$images_path,
      'addBtnCaption' => lang::get('Add {1}'),
      'msgPhoto' => lang::get('photo'),
      'msgFile' => lang::get('file'),
      'msgLink' => lang::get('link'),
      'msgNewImage' => lang::get('New {1}'),
      'msgDelete' => lang::get('Delete this item'),
      'msgUseAddFileBtn' => lang::get('Use the Add file button to select a file from your local disk. Files of type {1} are allowed.'),
      'msgUseAddLinkBtn' => lang::get('Use the Add link button to add a link to information stored elsewhere on the internet. You can enter links from {1}.')
    );
    $defaults['caption'] = (!isset($options['mediaTypes']) || $options['mediaTypes']===array('Image:Local')) ? lang::get('Photos') : lang::get('Media files');
    if (isset(self::$final_image_folder_thumbs))
      $defaults['finalImageFolderThumbs'] = $relpath . self::$final_image_folder_thumbs;
    $browser = self::get_browser_info();
    // Flash doesn't seem to work on IE6.
    if ($browser['name']=='msie' && $browser['version']<7)
      $defaults['runtimes'] = array_diff($defaults['runtimes'], array('flash'));
    if ($indicia_templates['file_box']!='')
      $defaults['file_boxTemplate'] = $indicia_templates['file_box'];
    if ($indicia_templates['file_box_initial_file_info']!='')
      $defaults['file_box_initial_file_infoTemplate'] = $indicia_templates['file_box_initial_file_info'];
    if ($indicia_templates['file_box_uploaded_image']!='')
      $defaults['file_box_uploaded_imageTemplate'] = $indicia_templates['file_box_uploaded_image'];
    $options = array_merge($defaults, $options);
    $options['id'] = $options['table'] .'-'. $options['id'];
    $containerId = 'container-'.$options['id'];

    if ($options['codeGenerated']!='php') {
      // build the JavaScript including the required file links
      self::add_resource('plupload');
      foreach($options['runtimes'] as $runtime) {
        self::add_resource("plupload_$runtime");
      }
      // convert runtimes list to plupload format
      $options['runtimes'] = implode(',', $options['runtimes']);

      $javascript = "\n$('#".str_replace(':','\\\\:',$containerId)."').uploader({";
      // Just pass the options array through
      $idx = 0;
      foreach($options as $option=>$value) {
        if (is_array($value) || is_object($value)) {
          $value = json_encode($value);
        }
        else
          // not an array, so wrap as string
          $value = "'$value'";
        $javascript .= "\n  $option : $value";
        // comma separated, except last entry
        if ($idx < count($options)-1) $javascript .= ',';
        $idx++;
      }
      //If the subType is specified, then this option is supplied as text by the user. So go and look up the ID to use in code.
      if (!empty($options['subType'])) {
        $typeTermData = self::get_population_data(array(
          'table'=>'termlists_term',
          'extraParams'=>$options['readAuth']+array('term'=>$options['subType'], 'columns'=>'id')
        ));
        $mediaTypeIdLimiter = $typeTermData[0]['id'];
      }
      // add in any reloaded items, when editing or after validation failure
      if (self::$entity_to_load) {
        //If we only want to display media of a particular type, then supply this as a parameter when extracting the media.
        if (!empty($mediaTypeIdLimiter)) {
          $images = self::extract_media_data(self::$entity_to_load,
            isset($options['loadExistingRecordKey']) ? $options['loadExistingRecordKey'] : $options['table'],
            false,
            false,
            $mediaTypeIdLimiter);
        } else {
          $images = self::extract_media_data(self::$entity_to_load,
            isset($options['loadExistingRecordKey']) ? $options['loadExistingRecordKey'] : $options['table']);
        }
        $javascript .= ",\n  existingFiles : ".json_encode($images);
      }
      $javascript .= "\n});\n";
    }
    if ($options['codeGenerated']=='js')
      // we only want to return the JavaScript, so go no further.
      return $javascript;
    elseif ($options['codeGenerated']=='all') {
      if (isset($options['tabDiv'])) {
        // The file box is displayed on a tab, so we must only generate it when the tab is displayed.
        $javascript =
          "var uploaderTabHandler = function(event, ui) { \n" .
          "  panel = typeof ui.newPanel==='undefined' ? ui.panel : ui.newPanel[0];\n" .
          "  if ($(panel).attr('id')==='".$options['tabDiv']."') {\n    ".
          $javascript.
          "    indiciaFns.unbindTabsActivate($($('#".$options['tabDiv']."').parent()), uploaderTabHandler);\n".
          "  }\n};\n".
          "indiciaFns.bindTabsActivate($($('#".$options['tabDiv']."').parent()), uploaderTabHandler);\n";
        // Insert this script at the beginning, because it must be done before the tabs are initialised or the
        // first tab cannot fire the event
        self::$javascript = $javascript . self::$javascript;
      }	else
        self::$onload_javascript .= $javascript;
    }
    // Output a placeholder div for the jQuery plugin. Also output a normal file input for the noscripts
    // version.
    $r = '<div class="file-box" id="'.$containerId.'"></div><noscript>'.self::image_upload(array(
        'label' => $options['caption'],
        // Convert table into a pseudo field name for the images
        'id' => $options['id'],
        'fieldname' => str_replace('_', ':', $options['table'])
      )).'</noscript>';
    $r .= self::add_link_popup($options);
    return $r;
  }

  /**
   * Generates a text input control with a search button that looks up an entered place against a georeferencing
   * web service. The control is automatically linked to any map panel added to the page.
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>georeference_lookup</b></br>
   * Template which outputs the HTML for the georeference search input, button placehold and container
   * for the list of search results. The default template uses JavaScript to write the output, so that
   * this control is removed from the page if JavaScript is disabled as it will have no functionality.
   * </li>
   * <li><b>button</b></br>
   * HTML template for the buttons used.
   * </li>
   * </ul>
   *
   * @param array $options Options array with the following possibilities:
   * <ul>
   * <li><b>fieldname</b><br/>
   * Optional. The name of the database field this control is bound to if any.</li>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control.</li>
   * <li><b>georefPreferredArea</b><br/>
   * Optional. Hint provided to the locality search service as to which area to look for the place name in. Any example usage of this
   * would be to set it to the name of a region for a survey based in that region. Note that this is only a hint, and the search
   * service may still return place names outside the region. Defaults to gb.</li>
   * <li><b>georefCountry</b><br/>
   * Optional. Hint provided to the locality search service as to which country to look for the place name in. Defaults to United Kingdom.</li>
   * <li><b>georefLang</b><br/>
   * Optional. Language to request place names in. Defaults to en-EN for English place names.</li>
   * <li><b>readAuth</b><br/>
   * Optional. Read authentication tokens for the Indicia warehouse if using the indicia_locations driver setting.</li>
   * <li><b>driver</b><br/>
   * Optional. Driver to use for the georeferencing operation. Supported options are:<br/>
   *   geoplanet - uses the Yahoo! GeoPlanet place search. This is the default.<br/>
   *   google_places_api - uses the Google Places API text search service.<br/>
   *   geoportal_lu - Use the Luxembourg specific place name search provided by geoportal.lu.
   *   indicia_locations - Use the list of locations available to the current website in Indicia as a search list.
   * </li>
   * <li><b>public</b><br/>
   * Optional. If using the indicia_locations driver, then set this to true to include public (non-website specific)
   * locations in the search results. Defaults to false.
   * </li>
   * <li><b>autoCollapseResults</b><br/>
   * Optional. If a list of possible matches are found, does selecting a match automatically fold up the results? Defaults to false.
   * </li>
   * </ul>
   *
   * @link http://code.google.com/apis/ajaxsearch/terms.html Google AJAX Search API Terms of Use.
   * @link http://code.google.com/p/indicia/wiki/GeoreferenceLookupDrivers Documentation for the driver architecture.
   * @return string HTML to insert into the page for the georeference lookup control.
   */
  public static function georeference_lookup($options) {
    $options = self::check_options($options);
    global $indicia_templates;
    $options = array_merge(array(
      'id' => 'imp-georef-search',
      'driver' => 'geoplanet',
      'searchButton' => self::apply_replacements_to_template($indicia_templates['button'],
        array('href'=>'#', 'id'=>'imp-georef-search-btn', 'class' => 'class="indicia-button"', 'caption'=>lang::get('Search'), 'title'=>'')),
      'public' => false,
      'autoCollapseResults' => false
    ), $options);
    if (($options['driver']==='geoplanet' && empty(self::$geoplanet_api_key)) ||
      ($options['driver']==='google_places' && empty(self::$google_api_key))) {
      // can't use place search without the driver API key
      return 'The georeference lookup control requires an API key configured for the place search API in use.<br/>';
    }
    self::add_resource('indiciaMapPanel');
    // dynamically build a resource to link us to the driver js file.
    self::$required_resources[] = 'georeference_default_'.$options['driver'];
    self::$resource_list['georeference_default_'.$options['driver']] = array(
      'javascript' => array(self::$js_path.'drivers/georeference/'.$options['driver'].'.js')
    );
    // We need to see if there is a resource in the resource list for any special files required by this driver. This
    // will do nothing if the resource is absent.
    self::add_resource('georeference_'.$options['driver']);
    foreach ($options as $key=>$value) {
      // if any of the options are for the georeferencer driver, then we must set them in the JavaScript.
      if (substr($key, 0, 6)=='georef') {
        self::$javascript .= "$.fn.indiciaMapPanel.georeferenceLookupSettings.$key='$value';\n";
      }
    }
    if ($options['driver']==='geoplanet')
      self::$javascript .= '$.fn.indiciaMapPanel.georeferenceLookupSettings.geoplanet_api_key=\''.self::$geoplanet_api_key."';\n";
    if ($options['driver']==='google_places')
      self::$javascript .= '$.fn.indiciaMapPanel.georeferenceLookupSettings.google_api_key=\''.self::$google_api_key."';\n";
    // If the lookup service driver uses cross domain JavaScript, this setting provides
    // a path to a simple PHP proxy script on the server.
    self::$javascript .= "$.fn.indiciaMapPanel.georeferenceLookupSettings.proxy='".
      self::getRootFolder() . self::client_helper_path() . "proxy.php';\n\n";
    self::$javascript .= "$.fn.indiciaMapPanel.georeferenceLookupSettings.autoCollapseResults='".($options['autoCollapseResults'] ? 't' : 'f')."';\n";
    // for the indicia_locations driver, pass through the read auth and url
    if ($options['driver']==='indicia_locations') {
      self::$javascript .= "$.fn.indiciaMapPanel.georeferenceLookupSettings.warehouseUrl='".self::$base_url."';\n";
      self::$javascript .= "$.fn.indiciaMapPanel.georeferenceLookupSettings.auth_token='".$options['readAuth']['auth_token']."';\n";
      self::$javascript .= "$.fn.indiciaMapPanel.georeferenceLookupSettings.nonce='".$options['readAuth']['nonce']."';\n";
      self::$javascript .= "$.fn.indiciaMapPanel.georeferenceLookupSettings.public='".($options['public'] ? 't' : 'f')."';\n";
      self::add_resource('json');
    }
    if ($options['autoCollapseResults']) {
      // no need for close button on results list
      $options['closeButton']='';
    } else {
      // want a close button on the results list
      $options['closeButton'] = self::apply_replacements_to_template($indicia_templates['button'],
        array('href'=>'#', 'id'=>'imp-georef-close-btn', 'class' => '', 'caption'=>lang::get('Close the search results'), 'title'=>''));
    }
    return self::apply_template('georeference_lookup', $options);
  }

  /**
   * A version of the select control which supports hierarchical termlist data by adding new selects to the next line
   * populated with the child terms when a parent term is selected.
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * Required. The name of the database field this control is bound to.</li>
   * <li><b>id</b><br/>
   * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
   * <li><b>default</b><br/>
   * Optional. The default value to assign to the control. This is overridden when reloading a
   * record with existing data for this control.</li>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control.</li>  *
   * <li><b>table</b><br/>
   * Table name to get data from for the select options. Should be termlists_term for termlist data.</li>
   * <li><b>report</b><br/>
   * Report name to get data from for the select options if the select is being populated by a service call using a report.
   * Mutually exclusive with the table option. The report should return a parent_id field.</li>
   * <li><b>captionField</b><br/>
   * Field to draw values to show in the control from if the select is being populated by a service call.</li>
   * <li><b>valueField</b><br/>
   * Field to draw values to return from the control from if the select is being populated by a service call. Defaults
   * to the value of captionField.</li>
   * <li><b>extraParams</b><br/>
   * Optional. Associative array of items to pass via the query string to the service. This
   * should at least contain the read authorisation array if the select is being populated by a service call. It can also contain
   * view=cache to use the cached termlists entries or view=detail for the uncached version.</li>
   * <li><b>captionTemplate</b><br/>
   * Optional and only relevant when loading content from a data service call. Specifies the template used to build the caption,
   * with each database field represented as {fieldname}.</li>
   * </ul>
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>select</b></br>
   * Template used for the HTML select element.
   * </li>
   * <li><b>select_item</b></br>
   * Template used for each option item placed within the select element.
   * </li>
   * <li><b>hidden_text</b></br>
   * HTML used for a hidden input that will hold the value to post to the database.
   * </li>
   * <li><b>autoSelectSingularChildItem</b></br>
   * When selecting parent items in the hierarchical select, then sometimes there might be only one child item.
   * Set this option to true if you want that single item to be automatically selected in that scenario.
   * </li>
   * </ul>
   */
  public static function hierarchical_select($options) {
    $options = array_merge(array(
      'id' => 'select-'.rand(0,10000),
      'blankText' => '<please select>',
      'extraParams' => array()
    ), $options);
    // If not language filtered, then limit to preferred, otherwise we could get
    // multiple children.
    // @todo This should probably be set by the caller, not here.
    if (!isset($options['extraParams']['iso']))
      $options['extraParams']['preferred']='t';
    // Get the data for the control. Not Ajax populated at the moment. We either populate the lookupValues for the top level control
    // or store in the childData for output into JavaScript
    $items = self::get_population_data($options);
    $lookupValues=array();
    $childData=array();
    foreach ($items as $item) {
      $itemValue = $item[$options['valueField']];
      if (isset($options['captionTemplate'])) {
        $itemCaption = self::mergeParamsIntoTemplate($item, $options['captionTemplate']);
      }
      else {
        $itemCaption = $item[$options['captionField']];
      }

      if (empty($item['parent_id']))
        $lookupValues[$itemValue] = $itemCaption;
      else {
        // not a top level item, so put in a data array we can store in JSON.
        if (!isset($childData[$item['parent_id']])) {
          $childData[$item['parent_id']] = array();
        }
        $childData[$item['parent_id']][] = array('id' => $itemValue, 'caption' => $itemCaption);
      }
    }
    // build an ID with just alphanumerics, that we can use to keep JavaScript function and data names unique
    $id = preg_replace('/[^a-zA-Z0-9]/', '', $options['id']);
    // dump the control population data out for JS to use
    self::$javascript .= "indiciaData.selectData$id=".json_encode($childData).";\n";

    if (isset($options['autoSelectSingularChildItem']) AND $options['autoSelectSingularChildItem']==true)
      self::$javascript .= "indiciaData.autoSelectSingularChildItem=true;\n";

    // Convert the options so that the top-level select uses the lookupValues we've already loaded rather than reloads its own.
    unset($options['table']);
    unset($options['report']);
    unset($options['captionField']);
    unset($options['valueField']);
    $options['lookupValues'] = $lookupValues;

    // as we are going to output a select using the options, but will use a hidden field for the form value for the selected item,
    // grab the fieldname and prevent the topmost select having the same name.
    $fieldname = $options['fieldname'];
    $options['fieldname'] = 'parent-'.$options['fieldname'];

    // Output a select. Use templating to add a wrapper div, so we can keep all the hierarchical selects together.
    global $indicia_templates;
    $oldTemplate = $indicia_templates['select'];
    $classes = array('hierarchical-select', 'control-box');
    if (!empty($options['class']))
      $classes[] = $options['class'];
    $indicia_templates['select'] = '<div class="'.implode(' ', $classes).'">'.$indicia_templates['select'].'</div>';
    $options['class']='hierarchy-select';
    $r = self::select($options);
    $indicia_templates['select'] = $oldTemplate;
    // jQuery safe version of the Id.
    $safeId = preg_replace('/[:]/', '\\\\\\:', $options['id']);
    // output a hidden input that contains the value to post.
    $hiddenOptions = array('id'=>'fld-'.$options['id'], 'fieldname'=>$fieldname, 'default'=>self::check_default_value($options['fieldname']));
    if (isset($options['default']))
      $hiddenOptions['default'] = $options['default'];
    $r .= self::hidden_text($hiddenOptions);
    $options['blankText']=htmlspecialchars(lang::get($options['blankText']));
    // Now output JavaScript that creates and populates child selects as each option is selected. There is also code for
    // reloading existing values.
    self::$javascript .= "
  // enclosure needed in case there are multiple on the page
  (function () {
    function pickHierarchySelectNode(select,fromOnChange) {
      select.nextAll().remove();
      if (typeof indiciaData.selectData$id [select.val()] !== 'undefined') {
        var html='<select class=\"hierarchy-select\"><option>".$options['blankText']."</option>', obj;
        $.each(indiciaData.selectData$id [select.val()], function(idx, item) {
          //If option is set then if there is only a single child item, auto select it in the list
          //Don't do this if we are initially loading the page (fromOnChange is false) as we only want to do this when the user actually changes the value.
          //We don't want to auto-select the child on page load, if that hasn't actually been saved to the database yet.
          if (indiciaData.selectData$id [select.val()].length ===1 && indiciaData.autoSelectSingularChildItem===true && fromOnChange===true) {
            html += '<option value=\"'+item.id+'\" selected>' + item.caption + '</option>';
            //Need to set the hidden value for submission, so correct value is actually saved to the database, not just shown visually on screen.
            //Make sure we escape the colon for jQuery selector also.
            $('#'+'".$hiddenOptions['id']."'.replace(':','\\\\:')).val(item.id);
          } else {
            html += '<option value=\"'+item.id+'\">' + item.caption + '</option>';
          }
        });
        html += '</select>';
        obj=$(html);
        obj.change(function(evt) {
          $('#fld-$safeId').val($(evt.target).val());
          pickHierarchySelectNode($(evt.target),true);
        });
        select.after(obj);
      }
    }

    $('#$safeId').change(function(evt) {
      $('#fld-$safeId').val($(evt.target).val());
      pickHierarchySelectNode($(evt.target),true);
    });

    pickHierarchySelectNode($('#$safeId'),false);

    // Code from here on is to reload existing values.
    function findItemParent(idToFind) {
      var found=false;
      $.each(indiciaData.selectData$id, function(parentId, items) {
        $.each(items, function(idx, item) {
          if (item.id===idToFind) {
            found=parentId;
          }
        });
      });
      return found;
    }
    var found=true, last=$('#fld-$safeId').val(), tree=[last], toselect, thisselect;
    while (last!=='' && found) {
      found=findItemParent(last);
      if (found) {
        tree.push(found);
        last=found;
      }
    }

    // now we have the tree, work backwards to select each item
    thisselect = $('#$safeId');
    while (tree.length>0) {
      toselect=tree.pop();
      $.each(thisselect.find('option'), function(idx, option) {
        if ($(option).val()===toselect) {
          if (!$(option).attr('selected')) {
            $(option).attr('selected',true);
            thisselect.trigger('change');
          }
        }
      });
      thisselect = thisselect.next();
    }
  }) ();
    ";
    return $r;
  }

  /**
   * Simple file upload control suitable for uploading images to attach to occurrences.
   * Note that when using this control, it is essential that the form's HTML enctype attribute is
   * set to enctype="multipart/form-data" so that the image file is included in the form data. For multiple
   * image support and more advanced options, see the file_box control.
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>image_upload</b></br>
   * HTML template for the file input control.
   * </li></ul>
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * Required. The name of the database field this control is bound to, e.g. occurrence:image.</li>
   * <li><b>id</b><br/>
   * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control.</li>
   * <li><b>existingFilePreset</b><br/>
   * Optional. Preset name of the file size to load from the warehouse when loading an
   * existing file. For example thumb or med, default thumb.</li>
   * </ul>
   *
   * @return string HTML to insert into the page for the file upload control.
   */
  public static function image_upload($options) {
    $options = self::check_options($options);
    $pathField = str_replace(array(':image',':medium'),'_medium:path', $options['fieldname']);
    $alreadyUploadedFile = self::check_default_value($pathField);
    $options = array_merge(array(
      'pathFieldName' => $pathField,
      'pathFieldValue' => $alreadyUploadedFile,
      'existingFilePreset' => 'thumb'
    ), $options);
    if (!empty($options['existingFilePreset']))
      $options['existingFilePreset'] .= '-';
    $r = self::apply_template('image_upload', $options);
    if ($alreadyUploadedFile) {
      if (self::$form_mode==='ERRORS') {
        // The control is being reloaded after a validation failure. So we can display a thumbnail of the
        // already uploaded file, so the user knows not to re-upload.
        $folder = isset(self::$interim_image_folder) ? self::$interim_image_folder : 'upload/';
      } else {
        // image should be already on the warehouse
        $folder = self::get_uploaded_image_folder();
        $alreadyUploadedFile = "$options[existingFilePreset]$alreadyUploadedFile";
      }

      $r .= "<img width=\"100\" src=\"$folder$alreadyUploadedFile\"/>\n";
    }
    return $r;
  }

  /**
   * A control for building JSON strings, based on http://robla.net/jsonwidget/. Dynamically
   * generates an input form for the JSON depending on a defined schema. This control
   * is not normally used for typical Indicia forms, but is used by the prebuilt
   * forms parameter entry forms for complex parameter structures such as the options
   * available for a chart.
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * The name of the database or form parameter field this control is bound to, e.g. series_options.</li>
   * <li><b>if</b>
   * The HTML id of the output div.</li>
   * <li><b>schema</b>
   * Must be supplied with a schema string that defines the allowable structure of the JSON output. Schemas can be
   * automatically built using the schema generator at
   * http://robla.net/jsonwidget/example.php?sample=byexample&user=normal.</li>
   * <li><b>class</b>
   * Additional css class names to include on the outer div.</li>
   * </ul>
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>jsonwidget</b></br>
   * HTML template for outer container. The inner content is not templatable since it is created by the
   * JavaScript control code.
   * </li></ul>
   * @return HTML string to insert in the form.
   */
  public static function jsonwidget($options) {
    $options = array_merge(array(
      'id' => 'jsonwidget_container',
      'fieldname' => 'jsonwidget',
      'schema' => '{}',
      'class'=> ''
    ), $options);
    $options['class'] = trim($options['class'].' control-box jsonwidget');

    self::add_resource('jsonwidget');
    extract($options, EXTR_PREFIX_ALL, 'opt');
    if (!isset($opt_default)) $opt_default = '';
    $opt_default = str_replace(array("\r","\n", "'"), array('\r','\n',"\'"), $opt_default);
    self::$javascript .= "$('#".$options['id']."').jsonedit({schema: $opt_schema, default: '$opt_default', fieldname: \"$opt_fieldname\"});\n";

    return self::apply_template('jsonwidget', $options);
  }

  /**
   * Outputs an autocomplete control that is dedicated to listing locations and which is bound to any map panel
   * added to the page. Although it is possible to set all the options of a normal autocomplete, generally
   * the table, valueField, captionField, id should be left uninitialised and the fieldname will default to the
   * sample's location_id field so can normally also be left.
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>autocomplete</b></br>
   * Defines a hidden input and a visible input, to hold the underlying database ID and to
   * allow input and display of the text search string respectively.
   * </li>
   * <li><b>autocomplete_javascript</b></br>
   * Defines the JavaScript which will be inserted onto the page in order to activate the
   * autocomplete control.
   * </li>
   * </ul>
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * Optional. The name of the database field this control is bound to.</li>
   * <li><b>default</b><br/>
   * Optional. The default value to assign to the control. This is overridden when reloading a
   * record with existing data for this control.</li>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control.</li>
   * <li><b>extraParams</b><br/>
   * Required. Associative array of items to pass via the query string to the service. This
   * should at least contain the read authorisation array.</li>
   * <li><b>cachetimeout</b><br/>
   * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
   * after a request for data to the Indicia Warehouse before a new request will refetch the data,
   * rather than use a locally stored (cached) copy of the previous request. This speeds things up
   * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
   * if this is not specified then 1 hour.</li>
   * <li><b>useLocationName</b>
   * Optional. If true, then inputting a place name which does not match to an existing place
   * gets stored in the location_name field. Defaults to false.
   * </li>
   * <li><b>searchUpdatesSref</b>
   * Optional. If true, then when a location is found in the autocomplete, the location's centroid spatial
   * reference is loaded into the spatial_ref control on the form if any exists. Defaults to false.
   * </li>
   * <li><b>allowcreate</b>
   * Optional. If true, if the user has typed in a non-existing location name and also supplied
   * a spatial reference, a button is displayed which lets them save a location for future
   * personal use. Defaults to false. For this to work, you must either allow the standard Indicia
   * code to handle the submission for you, or your code must handle the presence of a value called
   * save-site-flag in the form submission data and if true, it must first save the site information
   * to the locations table database then attach the location_id returned to the submitted sample data.
   * </li>
   * <li><b>fetchLocationAttributesIntoSample</b>
   * Defaults to true. Defines that when a location is picked, any sample attributes marked as for_location=true
   * will be populated with their previous values from the same site for this user. For example you might capture
   * a habitat sample attribute and expect it to default to the previously entered value when a repeat visit to a
   * site occurs.
   * </li>
   * <li><b>autofillFromLocationTypeId</b>
   * If a location type term's ID is provided here (from the termlists_terms table) then when a map reference is input
   * either by clicking on a map or direct entry into the sref_input control, attempts to find a matching boundary
   * from the locaitons of this type on the warehouse by intersecting with the boundaries then fills in this control
   * from the matching location details. If more than one are found the user is asked to resolve it, e.g. if a grid
   * ref overlays a boundary.
   * </li>
   * </ul>
   *
   * @return string HTML to insert into the page for the location select control.
   */
  public static function location_autocomplete($options) {
    if (empty($options['id']))
      $options['id'] = 'imp-location';
    $options = self::check_options($options);
    $caption = isset(self::$entity_to_load['sample:location']) ? self::$entity_to_load['sample:location'] : null;
    if (!$caption && !empty($options['useLocationName']) && $options['useLocationName'] && !empty(self::$entity_to_load['sample:location_name']))
      $caption = self::$entity_to_load['sample:location_name'];
    $options = array_merge(array(
      'table' => 'location',
      'fieldname' => 'sample:location_id',
      'valueField' => 'id',
      'captionField' => 'name',
      'defaultCaption' => $caption,
      'useLocationName' => false,
      'allowCreate' => false,
      'searchUpdatesSref' => false,
      'fetchLocationAttributesIntoSample' =>
          !isset($options['fieldname']) || $options['fieldname'] === 'sample:location_id'
    ), $options);
    // Disable warnings for no matches if the user is allowed to input a vague unmatched location name.
    $options['warnIfNoMatch']=!$options['useLocationName'];
    // this makes it easier to enter unmatched text, if allowed to do so
    $options['continueOnBlur']=!$options['useLocationName'];
    $r = self::autocomplete($options);
    // put a hidden input in the form to indicate that the location value should be
    // copied to the location_name field if not linked to a location id.
    if ($options['useLocationName'])
      $r = '<input type="hidden" name="useLocationName" value="true"/>'.$r;
    if ($options['allowCreate'] || $options['searchUpdatesSref']) {
      self::add_resource('createPersonalSites');
      if ($options['allowCreate']) {
        self::$javascript .= "indiciaData.msgRememberSite='".lang::get('Remember site')."';\n";
        self::$javascript .= "indiciaData.msgRememberSiteHint='".lang::get('Remember details of this site so you can enter records at the same location in future.')."';\n";
        self::$javascript .= "indiciaData.msgSiteWillBeRemembered='".lang::get('The site will be available to search for next time you input some records.')."';\n";
        self::$javascript .= "allowCreateSites();\n";
      }
      if ($options['searchUpdatesSref'])
        self::$javascript .= "indiciaData.searchUpdatesSref=true;\n";
    }
    $escapedId = str_replace(':', '\\\\:', $options['id']);
    // If using Easy Login, then this enables auto-population of the site related fields.
    if ($options['fetchLocationAttributesIntoSample'] &&
        function_exists('hostsite_get_user_field') && ($createdById=hostsite_get_user_field('indicia_user_id'))) {
      self::$javascript .= <<<JS
$('#$escapedId').change(function() {
  indiciaFns.locationControl.fetchLocationAttributesIntoSample('$options[id]', $createdById);
});

JS;
    }
    if (!empty($options['autofillFromLocationTypeId'])) {
      $langMoreThanOneLocationMatch = lang::get(
        'When trying to find the {1} more than one possibility was found. Please select the correct one below.',
        strtolower($options['label']));
      self::$javascript .= <<<JS
indiciaData.langMoreThanOneLocationMatch = '$langMoreThanOneLocationMatch';
$('#imp-geom').change(function() { 
  indiciaFns.locationControl.autoFillLocationFromLocationTypeId('$options[id]', $options[autofillFromLocationTypeId]);
});

JS;
    }
    return $r;
  }

  /**
   * Outputs a select control that is dedicated to listing locations and which is bound to any map panel
   * added to the page. Although it is possible to set all the options of a normal select control, generally
   * the table, valueField, captionField, id should be left uninitialised and the fieldname will default to the
   * sample's location_id field so can normally also be left. If you need to use a report to populate the list of
   * locations, for example when filtering by a custom attribute, then set the report option to the report name
   * (e.g. library/reports/locations_list) and provide report parameters in extraParams. You can also override
   * the captionField and valueField if required.
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * Optional. The name of the database field this control is bound to.</li>
   * <li><b>default</b><br/>
   * Optional. The default value to assign to the control. This is overridden when reloading a
   * record with existing data for this control.</li>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control.</li>
   * <li><b>extraParams</b><br/>
   * Required. Associative array of items to pass via the query string to the service. This
   * should at least contain the read authorisation array.</li>
   * <li><b>cachetimeout</b><br/>
   * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
   * after a request for data to the Indicia Warehouse before a new request will refetch the data,
   * rather than use a locally stored (cached) copy of the previous request. This speeds things up
   * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
   * if this is not specified then 1 hour.</li>
   * <li><b>searchUpdatesSref</b>
   * Optional. If true, then when a location is selected, the location's centroid spatial
   * reference is loaded into the spatial_ref control on the form if any exists. Defaults to false.
   * </li>
   * </ul>
   *
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>select</b></br>
   * HTML template used to generate the select element.
   * </li>
   * <li><b>select_item</b></br>
   * HTML template used to generate each option element with the select element.
   * </li>
   * </ul>
   *
   * @return string HTML to insert into the page for the location select control.
   */
  public static function location_select($options) {
    $options = self::check_options($options);
    // Apply location type filter if specified.
    if (array_key_exists('location_type_id', $options)) {
      $options['extraParams'] += array('location_type_id' => $options['location_type_id']);
    }
    $options = array_merge(array(
      'table'=>'location',
      'fieldname'=>'sample:location_id',
      'valueField'=>'id',
      'captionField'=>'name',
      'id'=>'imp-location',
      'searchUpdatesSref'=>false
    ), $options);
    $options['columns']=$options['valueField'].','.$options['captionField'];
    if ($options['searchUpdatesSref'])
      self::$javascript .= "indiciaData.searchUpdatesSref=true;\n";
    return self::select($options);
  }

  /**
   * An HTML list box control.
   * Options can be either populated from a web-service call to the Warehouse, e.g. the contents of
   * a termlist, or can be populated from a fixed supplied array. The list box can
   * be linked to populate itself when an item is selected in another control by specifying the
   * parentControlId and filterField options.
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>listbox</b></br>
   * HTML template used to generate the select element.
   * </li>
   * <li><b>listbox_item</b></br>
   * HTML template used to generate each option element with the select element.
   * </li>
   * </ul>
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * Required. The name of the database field this control is bound to.</li>
   * <li><b>id</b><br/>
   * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
   * <li><b>default</b><br/>
   * Optional. The default value to assign to the control. This is overridden when reloading a
   * record with existing data for this control.</li>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control.</li>
   * <li><b>table</b><br/>
   * Table name to get data from for the select options if the select is being populated by a service call.</li>
   * <li><b>captionField</b><br/>
   * Field to draw values to show in the control from if the select is being populated by a service call.</li>
   * <li><b>valueField</b><br/>
   * Field to draw values to return from the control from if the select is being populated by a service call. Defaults
   * to the value of captionField.</li>
   * <li><b>extraParams</b><br/>
   * Optional. Associative array of items to pass via the query string to the service. This
   * should at least contain the read authorisation array if the select is being populated by a service call.</li>
   * <li><b>lookupValues</b><br/>
   * If the select is to be populated with a fixed list of values, rather than via a service call, then the
   * values can be passed into this parameter as an associated array of key=>caption.
   * </li>
   * <li><b>size</b><br/>
   * Optional. Number of lines to display in the listbox. Defaults to 3.</li>
   * <li><b>multiselect</b><br/>
   * Optional. Allow multi-select in the list box. Defaults to false.</li>
   * <li><b>parentControlId</b><br/>
   * Optional. Specifies a parent control for linked lists. If specified then this control is not
   * populated until the parent control's value is set. The parent control's value is used to
   * filter this control's options against the field specified by filterField.</li>
   * <li><b>parentControlLabel</b><br/>
   * Optional. Specifies the label of the parent control in a set of linked lists. This allows the child list
   * to display information about selecting the parent first.</li>
   * <li><b>filterField</b><br/>
   * Optional. Specifies the field to filter this control's content against when using a parent
   * control value to set up linked lists. Defaults to parent_id though this is not active
   * unless a parentControlId is specified.</li>
   * <li><b>filterIncludesNulls</b><br/>
   * Optional. Defaults to false. If true, then null values for the filter field are included in the filter results
   * when using a linked list.</li>
   * <li><b>cachetimeout</b><br/>
   * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
   * after a request for data to the Indicia Warehouse before a new request will refetch the data,
   * rather than use a locally stored (cached) copy of the previous request. This speeds things up
   * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
   * if this is not specified then 1 hour.</li>
   * <li><b>template</b><br/>
   * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
   * for the outer control.</li>
   * <li><b>itemTemplate</b><br/>
   * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
   * for each item in the control.</li>
   * <li><b>captionTemplate</b><br/>
   * Optional and only relevant when loading content from a data service call. Specifies the template used to build the caption,
   * with each database field represented as {fieldname}.</li>
   * <li><b>listCaptionSpecialChars</b><br/>
   * Optional and only relevant when loading content from a data service call. Specifies whether to run the caption through
   * htmlspecialchars. In some cases there may be format info in the caption, and in others we may wish to keep those
   * characters as literal.
   * <li><b>selectedItemTemplate</b><br/>
   * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
   * for the selected item in the control.</li></ul>
   * </ul>
   *
   * @return string HTML to insert into the page for the listbox control.
   */
  public static function listbox($options)
  {
    $options = self::check_options($options);
    // blank text option not applicable to list box
    unset($options['blankText']);
    $options = array_merge(
      array(
        'template' => 'listbox',
        'itemTemplate' => 'listbox_item'
      ),
      $options
    );
    $r = '';
    if(isset($options['multiselect']) && $options['multiselect']!=false && $options['multiselect']!=='false') {
      $options['multiple']='multiple';
      if (substr($options['fieldname'],-2) !=='[]') {
        $options['fieldname'] .= '[]';
      }
      // ensure a blank value is posted if nothing is selected in the list, otherwise the list can't be cleared in the db.
      $r = '<input type="hidden" name="'.$options['fieldname'].'" value=""/>';
    }
    return $r . self::select_or_listbox($options);
  }

  /**
   * Helper function to list the output from a request against the data services, using an HTML template
   * for each item. As an example, the following outputs an unordered list of surveys:
   * <pre>echo data_entry_helper::list_in_template(array(
   *     'label'=>'template',
   *     'table'=>'survey',
   *     'extraParams' => $readAuth,
   *     'template'=>'<li>|title|</li>'
   * ));</pre>
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>list_in_template</b></br>
   * HTML template used to generate the outer container.
   * </li>
   * </ul>
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control.</li>
   * <li><b>table</b><br/>
   * Required. Table name to get data from for the select options.</li>
   * <li><b>extraParams</b><br/>
   * Optional. Associative array of items to pass via the query string to the service. This
   * should at least contain the read authorisation array.</li>
   * <li><b>template</b><br/>
   * Required. HTML template which will be emitted for each item. Fields from the data are identified
   * by wrapping them in ||. For example, |term| would result in the field called term's value being placed inside
   * the HTML.</li>
   * <li><b>cachetimeout</b><br/>
   * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
   * after a request for data to the Indicia Warehouse before a new request will refetch the data,
   * rather than use a locally stored (cached) copy of the previous request. This speeds things up
   * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
   * if this is not specified then 1 hour.</li>
   * </ul>
   *
   * @return string HTML to insert into the page for the generated list.
   */
  public static function list_in_template($options) {
    $options = self::check_options($options);
    $response = self::get_population_data($options);
    $items = "";
    if (!array_key_exists('error', $response)){
      foreach ($response as $row){
        $item = $options['template'];
        foreach ($row as $field => $value) {
          $value = htmlspecialchars($value, ENT_QUOTES);
          $item = str_replace("|$field|", $value, $item);
        }
        $items .= $item;
      }
      $options['items']=$items;
      return self::apply_template('list_in_template', $options);
    }
    else
      return lang::get("error loading control");
  }

  /**
   * Generates a map control, with optional data entry fields and location finder powered by the
   * Yahoo! geoservices API. This is just a shortcut to building a control using a map_panel and the
   * associated controls.
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>presetLayers</b><br/>
   * Array of preset layers to include. Options are 'google_physical', 'google_streets', 'google_hybrid',
   * 'google_satellite', 'openlayers_wms', 'nasa_mosaic', 'virtual_earth' (deprecated, use bing_aerial),
   * 'bing_aerial', 'bing_hybrid, 'bing_shaded', 'multimap_default', 'multimap_landranger',
   * 'osm' (for OpenStreetMap), 'osm_th' (for OpenStreetMap Tiles@Home).</li>
   * <li><b>edit</b><br/>
   * True or false to include the edit controls for picking spatial references.</li>
   * <li><b>locate</b><br/>
   * True or false to include the geolocate controls.</li>
   * <li><b>wkt</b><br/>
   * Well Known Text of a spatial object to add to the map at startup.</li>
   * <li><b>tabDiv</b><br/>
   * If the map is on a tab or wizard interface, specify the div the map loads on.</li>
   * </ul>
   *
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>georeference_lookup</b></br>
   * Template which outputs the HTML for the georeference search input, button placehold and container
   * for the list of search results. The default template uses JavaScript to write the output, so that
   * this control is removed from the page if JavaScript is disabled as it will have no functionality.
   * </li>
   * <li><b>button</b></br>
   * HTML template for the buttons used for the georeference_lookup.
   * </li>
   * <li><b>sref_textbox</b></br>
   * HTML template for the spatial reference input control.
   * </li>
   * <li><b>sref_textbox_latlong</b></br>
   * HTML template for the spatial reference input control used when inputting latitude
   * and longitude into separate inputs.
   * </li>
   * <li><b>select</b></br>
   * HTML template used by the select control for picking a spatial reference system, if there
   * is one.
   * </li>
   * <li><b>select_item</b></br>
   * HTML template used by the option items in the select control for picking a spatial
   * reference system, if there is one.
   * </li>
   * </ul>
   */
  public static function map($options) {
    $options = self::check_options($options);
    $options = array_merge(array(
      'div'=>'map',
      'edit'=>true,
      'locate'=>true,
      'wkt'=>null
    ), $options);
    $r = '';
    if ($options['edit']) {
      $r .= self::sref_and_system(array(
        'label'=>lang::get('spatial ref'),
      ));
    }
    if ($options['locate']) {
      $r .= self::georeference_lookup(array(
        'label'=>lang::get('search for place on map')
      ));
    }
    $mapPanelOptions = array('initialFeatureWkt' => $options['wkt']);
    if (array_key_exists('presetLayers', $options)) $mapPanelOptions['presetLayers'] = $options['presetLayers'];
    if (array_key_exists('tabDiv', $options)) $mapPanelOptions['tabDiv'] = $options['tabDiv'];
    require_once('map_helper.php');
    $r .= map_helper::map_panel($mapPanelOptions);
    return $r;
  }

  /**
   * Outputs a map panel.
   * @param array $options Refer to map_helper::map_panel documentation.
   * @param array $olOptions Refer to map_helper::map_panel documentation.
   * @deprecated Use map_helper::map_panel instead.
   */
  public static function map_panel($options, $olOptions=array()) {
    require_once('map_helper.php');
    return map_helper::map_panel($options, $olOptions);
  }

  /**
   * Helper function to output an HTML password input. For security reasons, this does not re-load existing values
   * or display validation error messages and no default can be set.
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>password_input</b></br>
   * Template which outputs the HTML for a password input control.
   * </li>
   * </ul>
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * Required. The name by which the password will be passed to the authentication system.</li>
   * <li><b>id</b><br/>
   * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control.</li>
   * </ul>
   *
   * @return string HTML to insert into the page for the text input control.
   */
  public static function password_input($options) {
    $options = self::check_options($options);
    $options['lockable']=false;
    $options = array_merge(array(
      'default'=>''
    ), $options);
    return self::apply_template('password_input', $options);
  }

  /**
   * Helper function to output a textbox for determining a locality from an entered postcode.
   *
   * <p>The textbox optionally includes hidden fields for the latitude and longitude and can
   * link to an address control for automatic generation of address information. When the focus
   * leaves the textbox, the Google AJAX Search API is used to obtain the latitude and longitude
   * so they can be saved with the record.</p>
   *
   * <p>The following example displays a postcode box and an address box, which is auto-populated
   * when a postcode is given. The spatial reference controls are "hidden" from the user but
   * are available to post into the database.</p>
   * <code>
   * <?php echo data_entry_helper::postcode_textbox(array(
   *     'label'=>'Postcode',
   *     'fieldname'=>'smpAttr:8',
   *     'linkedAddressBoxId'=>'address'
   * );
   * echo data_entry_helper::textarea(array(
   *     'label' => 'Address',
   *     'id' => 'address',
   *     'fieldname' => 'smpAttr:9'
   * ));?>
   * </code>
   * <p>The output of this control can be configured using the following templates:</p>
   * <ul>
   * <li><b>postcode_textbox</b></br>
   * Template which outputs the HTML for the text input control used. Must have an onblur event handler
   * which calls the JavaScript required to search for the post code.
   * </li>
   * </ul>
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * Required. The name of the database field this control is bound to.</li>
   * <li><b>id</b><br/>
   * Optional. The id to assign to the HTML control. This should be left to its default value for
   * integration with other mapping controls to work correctly.</li>
   * <li><b>default</b><br/>
   * Optional. The default value to assign to the control. This is overridden when reloading a
   * record with existing data for this control.</li>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control.</li>
   * <li><b>hiddenFields</b><br/>
   * Optional. Set to true to insert hidden inputs to receive the latitude and longitude. Otherwise there
   * should be separate sref_textbox and sref_system_textbox controls elsewhere on the page. Defaults to true.
   * <li><b>srefField</b><br/>
   * Optional. Name of the spatial reference hidden field that will be output by this control if hidddenFields is true.</li>
   * <li><b>systemField</b><br/>
   * Optional. Name of the spatial reference system hidden field that will be output by this control if hidddenFields is true.</li>
   * <li><b>linkedAddressBoxId</b><br/>
   * Optional. Id of the separate textarea control that will be populated with an address when a postcode is looked up.</li>
   * </ul>
   *
   * @return string HTML to insert into the page for the postcode control.
   */
  public static function postcode_textbox($options) {
    if (empty(self::$google_api_key))
      return 'The postcode textbox control requires a Google API Key in the configuration';
    // The id must be set to imp-postcode otherwise the search does not work
    $options = array_merge($options, array('id'=>'imp-postcode'));
    $options = self::check_options($options);
    // Merge in the defaults
    $options = array_merge(array(
      'srefField'=>'sample:entered_sref',
      'systemField'=>'sample:entered_sref_system',
      'hiddenFields'=>true,
      'linkedAddressBoxId'=>''
    ), $options);
    self::add_resource('postcode_search');
    $r = self::apply_template('postcode_textbox', $options);
    if ($options['hiddenFields']) {
      $defaultSref=self::check_default_value($options['srefField']);
      $defaultSystem=self::check_default_value($options['systemField'], '4326');
      $r .= "<input type='hidden' name='".$options['srefField']."' id='imp-sref' value='$defaultSref' />";
      $r .= "<input type='hidden' name='".$options['systemField']."' id='imp-sref-system' value='$defaultSystem' />";
    }
    $r .= self::check_errors($options['fieldname']);
    self::$javascript .= "indiciaData.google_api_key='".self::$google_api_key."';\n";
    self::$javascript .= "$.fn.indiciaMapPanel.georeferenceLookupSettings.proxy='".
      self::getRootFolder() . self::client_helper_path() . "proxy.php';\n\n";
    return $r;
  }

  /**
   * Helper function to generate a radio group from a Indicia core service query.
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * Required. The name of the database field this control is bound to.</li>
   * <li><b>id</b><br/>
   * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
   * <li><b>default</b><br/>
   * Optional. The default value to assign to the control. This is overridden when reloading a
   * record with existing data for this control.</li>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control.</li>
   * <li><b>table</b><br/>
   * Optional. Table name to get data from for the select options. Required unless lookupValues is specified.</li>
   * <li><b>captionField</b><br/>
   * Optional. Field to draw values to show in the control from. Required unless lookupValues is specified.</li>
   * <li><b>valueField</b><br/>
   * Optional. Field to draw values to return from the control from. Defaults
   * to the value of captionField. </li>
   * <li><b>extraParams</b><br/>
   * Optional. Associative array of items to pass via the query string to the service. This
   * should at least contain the read authorisation array.</li>
   * <li><b>lookupValues</b><br/>
   * If the group is to be populated with a fixed list of values, rather than via a service call, then the
   * values can be passed into this parameter as an associated array of key=>caption.</li>
   * <li><b>cachetimeout</b><br/>
   * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
   * after a request for data to the Indicia Warehouse before a new request will refetch the data,
   * rather than use a locally stored (cached) copy of the previous request. This speeds things up
   * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
   * if this is not specified then 1 hour.</li>
   * <li><b>template</b><br/>
   * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
   * for the outer control.</li>
   * <li><b>itemTemplate</b><br/>
   * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
   * for each item in the control.</li>
   * <li><b>captionTemplate</b><br/>
   * Optional and only relevant when loading content from a data service call. Specifies the template used to build the caption,
   * with each database field represented as {fieldname}.</li>
   * </ul>
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>check_or_radio_group</b></br>
   * Container element for the group of checkboxes.
   * </li>
   * <li><b>check_or_radio_group_item</b></br>
   * Template for the HTML element used for each item in the group.
   * </li>
   * </ul>
   *
   * @return string HTML to insert into the page for the group of radio buttons.
   */
  public static function radio_group($options) {
    $options = self::check_options($options);
    return self::check_or_radio_group($options, 'radio');
  }

  /**
   * Returns a simple HTML link to download the contents of a report defined by the options. The options arguments supported are the same as for the
   * report_grid method. Pagination information will be ignored (e.g. itemsPerPage).
   * @param array $options Refer to report_helper::report_download_link documentation.
   * @deprecated Use report_helper::report_download_link.
   */
  public static function report_download_link($options) {
    require_once('report_helper.php');
    return report_helper::report_download_link($options);
  }

  /**
   * Outputs a grid that loads the content of a report or Indicia table.
   * @param array $options Refer to report_helper::report_grid documentation.
   * @deprecated Use report_helper::report_grid.
   */
  public static function report_grid($options) {
    require_once('report_helper.php');
    return report_helper::report_grid($options);
  }

  /**
   * Outputs a chart that loads the content of a report or Indicia table.
   * @param array $options Refer to report_helper::report_chart documentation.
   * @deprecated Use report_helper::report_chart.
   */
  public static function report_chart($options) {
    require_once('report_helper.php');
    return report_helper::report_chart($options);
  }

  /**
   * Helper function to generate a select control from a Indicia core service query. The select control can
   * be linked to populate itself when an item is selected in another control by specifying the
   * parentControlId and filterField options.
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>select</b></br>
   * HTML template used to generate the select element.
   * </li>
   * <li><b>select_item</b></br>
   * HTML template used to generate each option element with the select elements.
   * </li>
   * </ul>
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * Required. The name of the database field this control is bound to.</li>
   * <li><b>id</b><br/>
   * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
   * <li><b>default</b><br/>
   * Optional. The default value to assign to the control. This is overridden when reloading a
   * record with existing data for this control.</li>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control.</li>  *
   * <li><b>table</b><br/>
   * Table name to get data from for the select options if the select is being populated by a service call.</li>
   * <li><b>report</b><br/>
   * Report name to get data from for the select options if the select is being populated by a service call using a report.
   * Mutually exclusive with the table option.</li>
   * <li><b>captionField</b><br/>
   * Field to draw values to show in the control from if the select is being populated by a service call.</li>
   * <li><b>valueField</b><br/>
   * Field to draw values to return from the control from if the select is being populated by a service call. Defaults
   * to the value of captionField.</li>
   * <li><b>extraParams</b><br/>
   * Optional. Associative array of items to pass via the query string to the service. This
   * should at least contain the read authorisation array if the select is being populated by a service call.</li>
   * <li><b>lookupValues</b><br/>
   * If the select is to be populated with a fixed list of values, rather than via a service call, then the
   * values can be passed into this parameter as an associated array of key=>caption.
   * </li>
   * <li><b>parentControlId</b><br/>
   * Optional. Specifies a parent control for linked lists. If specified then this control is not
   * populated until the parent control's value is set. The parent control's value is used to
   * filter this control's options against the field specified by filterField.</li>
   * <li><b>parentControlLabel</b><br/>
   * Optional. Specifies the label of the parent control in a set of linked lists. This allows the child list
   * to display information about selecting the parent first.</li>
   * <li><b>filterField</b><br/>
   * Optional. Specifies the field to filter this control's content against when using a parent
   * control value to set up linked lists. Defaults to parent_id though this is not active
   * unless a parentControlId is specified.</li>
   * <li><b>filterIncludesNulls</b><br/>
   * Optional. Defaults to false. If true, then null values for the filter field are included in the filter results
   * when using a linked list.</li>
   * <li><b>cachetimeout</b><br/>
   * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
   * after a request for data to the Indicia Warehouse before a new request will refetch the data,
   * rather than use a locally stored (cached) copy of the previous request. This speeds things up
   * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
   * if this is not specified then 1 hour.</li>
   * <li><b>blankText</b><br/>
   * Optional. If specified then the first option in the drop down is the blank text, used when there is no value.</li>
   * <li><b>template</b><br/>
   * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
   * for the outer control.</li>
   * <li><b>itemTemplate</b><br/>
   * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
   * for each item in the control.</li>
   * <li><b>captionTemplate</b><br/>
   * Optional and only relevant when loading content from a data service call. Specifies the template used to build the caption,
   * with each database field represented as {fieldname}.</li>
   * <li><b>listCaptionSpecialChars</b><br/>
   * Optional and only relevant when loading content from a data service call. Specifies whether to run the caption through
   * htmlspecialchars. In some cases there may be format info in the caption, and in others we may wish to keep those
   * characters as literal.
   * <li><b>selectedItemTemplate</b><br/>
   * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
   * for the selected item in the control.</li></ul>
   *
   * @return string HTML code for a select control.
   */
  public static function select($options)
  {
    $options = array_merge(
      array(
        'template' => 'select',
        'itemTemplate' => 'select_item'
      ),
      self::check_options($options)
    );
    return self::select_or_listbox($options);
  }

  /**
   * Outputs a spatial reference input box and a drop down select control populated with a list of
   * spatial reference systems for the user to select from. If there is only 1 system available then
   * the system drop down is ommitted since it is not required.
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>sref_textbox</b></br>
   * Template used for the text input box for the spatial reference.
   * </li>
   * <li><b>sref_textbox_latlong</b></br>
   * Template used for the latitude and longitude input boxes when the splitLatLong option is set
   * to true.
   * </li>
   * <li><b>select</b></br>
   * Template used for the select element which contains the spatial reference system options available
   * for input.
   * </li>
   * <li><b>select_item</b></br>
   * Template used for the option elements in the select list of spatial reference system options available
   * for input.
   * </li>
   * </ul>
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * Required. Name of the database field that spatial reference will be posted to. Defaults to
   * sample:entered_sref. The system field is automatically constructed from this.</li>
   * <li><b>systems</b>
   * Optional. List of spatial reference systems to display. Associative array with the key
   * being the EPSG code for the system or the notation abbreviation (e.g. OSGB), and the value being
   * the description to display.</li>
   * <li><b>defaultSystem</b>
   * Optional. Code for the default system value to load.</li>
   * <li><b>defaultGeom</b>
   * Optional. WKT value for the default geometry to load (hidden).</li>
   * </ul>
   *
   * @return string HTML to insert into the page for the spatial reference and system selection control.
   */
  public static function sref_and_system($options) {
    $options = array_merge(array(
      'fieldname'=>'sample:entered_sref'
    ), $options);
    // If we have more than one possible system, need a control to allow user selection
    $systemControlRequired = !(array_key_exists('systems', $options) && count($options['systems']) === 1);
    // in which case, no wrap around the 2 inner controls, just one around the outer added later
    if ($systemControlRequired)
      $options['controlWrapTemplate'] = 'justControl';
    if (array_key_exists('systems',$options) && count($options['systems']) == 1) {
      // The system select will be hidden since there is only one system
      $srefOptions = $options;
    } else {
      $srefOptions = array_merge($options);
      // Show the help text after the 2nd control
      if (isset($srefOptions['helpText'])) {
        unset($srefOptions['helpText']);
      }
    }
    // Output the sref control
    $r = self::sref_textbox($srefOptions);

    // tweak the options passed to the system selector
    $options['fieldname']=$options['fieldname']."_system";
    unset($options['label']);
    if (isset($options['defaultSystem'])) {
      $options['default']=$options['defaultSystem'];
    }
    // Output the system control
    if (!$systemControlRequired) {
      // Hidden field for the system
      $keys = array_keys($options['systems']);
      $r .= "<input type=\"hidden\" id=\"imp-sref-system\" name=\"".$options['fieldname']."\" value=\"".$keys[0]."\" />\n";
      self::include_sref_handler_js($options['systems']);
    }
    else {
      $r .= self::sref_system_select($options);
      // put an outer container to keep them together
      global $indicia_templates;
      $r = str_replace(array('{control}', '{id}'), array($r, 'imp-sref-and-system'), $indicia_templates['controlWrap']);
    }
    return $r;
  }

  /**
   * Outputs a drop down select control populated with a list of spatial reference systems
   * for the user to select from.
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>select</b></br>
   * Template used for the select element which contains the spatial reference system options available
   * for input.
   * </li>
   * <li><b>select_item</b></br>
   * Template used for the option elements in the select list of spatial reference system options available
   * for input.
   * </li>
   * </ul>
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * Required. The name of the database field this control is bound to. Defaults to sample:entered_sref_system.</li>
   * <li><b>id</b><br/>
   * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
   * <li><b>default</b><br/>
   * Optional. The default value to assign to the control. This is overridden when reloading a
   * record with existing data for this control.</li>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control.</li>
   * <li><b>systems</b>
   * Optional. List of spatial reference systems to display. Associative array with the key
   * being the EPSG code for the system or the notation abbreviation (e.g. OSGB), and the value being
   * the description to display.</li>
   * </ul>
   *
   * @return string HTML to insert into the page for the spatial reference systems selection control.
   */
  public static function sref_system_select($options) {
    global $indicia_templates;
    $options = array_merge(array(
      'fieldname'=>'sample:entered_sref_system',
      'systems'=>array('OSGB'=>lang::get('sref:OSGB'), '4326'=>lang::get('sref:4326')),
      'id'=>'imp-sref-system'
    ), $options);
    $options = self::check_options($options);
    $opts = "";
    foreach ($options['systems'] as $system=>$caption){
      $selected = ($options['default'] == $system ? 'selected' : '');
      $opts .= str_replace(
        array('{value}', '{caption}', '{selected}'),
        array($system, $caption, $selected),
        $indicia_templates['select_item']
      );
    }
    $options['items'] = $opts;
    self::include_sref_handler_js($options['systems']);
    return self::apply_template('select', $options);
  }

  /**
   * Creates a textbox for entry of a spatial reference.
   * Also generates the hidden geom field required to properly post spatial data. The
   * box is automatically linked to a map_panel if one is added to the page.
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>sref_textbox</b></br>
   * Template used for the text input box for the spatial reference.
   * </li>
   * <li><b>sref_textbox_latlong</b></br>
   * Template used for the latitude and longitude input boxes when the splitLatLong option is set
   * to true.
   * </li>
   * </ul>
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldName</b><br/>
   * Required. The name of the database field this control is bound to. Defaults to sample:entered_sref.</li>
   * <li><b>id</b><br/>
   * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
   * <li><b>default</b><br/>
   * Optional. The default value to assign to the control. This is overridden when reloading a
   * record with existing data for this control.</li>
   * <li><b>defaultGeom</b><br/>
   * Optional. The default geom (wkt) to store in a hidden input posted with the form data.</li>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control.</li>
   * <li><b>splitLatLong</b><br/>
   * Optional. If set to true, then 2 boxes are created, one for the latitude and one for the longitude.</li>
   * <li><b>geomFieldname</b><br/>
   * Optional. Fieldname to use for the geom (table:fieldname format) where the geom field is not
   * just called geom, e.g. location:centroid_geom.</li>
   * <li><b>minGridRef</b><br/>
   * Optional. Set to a number to enforce grid references to be a certain precision, e.g. provide the value 6
   * to enforce a minimum 6 figure grid reference.</li>
   * <li><b>maxGridRef</b><br/>
   * Optional. Set to a number to enforce grid references to less than a certain precision, e.g. provide the value 6
   * to enforce a maximum 6 figure grid reference.</li>
   * </ul>
   *
   * @return string HTML to insert into the page for the spatial reference control.
   * @todo This does not work for reloading data at the moment, when using split lat long mode.
   */
  public static function sref_textbox($options) {
    // get the table and fieldname
    $tokens=explode(':', $options['fieldname']);
    // Merge the default parameters
    $options = array_merge(array(
      'fieldname'=>'sample:entered_sref',
      'hiddenFields'=>true,
      'id'=>'imp-sref',
      'geomid'=>'imp-geom',
      'geomFieldname'=>$tokens[0].':geom',
      'default'=>self::check_default_value($options['fieldname']),
      'splitLatLong'=>false,
      'findMeButton'=>true
    ), $options);
    $rules = array();
    if (!empty($options['validation']))
      $rules[] = $options['validation'];
    if (!empty($options['minGridRef']))
      $rules[] = 'mingridref['.$options['minGridRef'].']';
    if (!empty($options['maxGridRef']))
      $rules[] = 'maxgridref['.$options['maxGridRef'].']';
    if (!empty($rules))
      $options['validation'] = $rules;
    if (!isset($options['defaultGeom']))
      $options['defaultGeom']=self::check_default_value($options['geomFieldname']);
    $options = self::check_options($options);
    if ($options['splitLatLong']) {
      // Outputting separate lat and long fields, so we need a few more options
      if (!empty($options['default'])) {
        preg_match('/^(?P<lat>[^,]*), ?(?P<long>.*)$/', $options['default'], $matches);
        if (isset($matches['lat']))
          $options['defaultLat'] = $matches['lat'];
        if (isset($matches['long']))
          $options['defaultLong'] = $matches['long'];
      }
      $options = array_merge(array(
        'defaultLat' => '',
        'defaultLong' => '',
        'fieldnameLat' => $options['fieldname'].'_lat',
        'fieldnameLong' => $options['fieldname'].'_long',
        'labelLat' => lang::get('Latitude'),
        'labelLong' => lang::get('Longitude'),
        'idLat'=>'imp-sref-lat',
        'idLong'=>'imp-sref-long'
      ), $options);
      unset($options['label']);
      $r = self::apply_template('sref_textbox_latlong', $options);
    } else {
      if ($options['findMeButton']) {
        if (!isset($options['class']))
          $options['class'] = 'findme';
        else
          $options['class'] .= ' findme';
        data_entry_helper::$javascript .= "indiciaFns.initFindMe('" . lang::get('Find my current location') . "');\n";
      }
      $r = self::apply_template('sref_textbox', $options);
    }
    return $r;
  }

  /**
   * Outputs hidden controls for entered_sref and sref_system. This is intended for use when
   * sample positions are to be selected from predefined locations and they are automatically
   * populated when a location shown on a map_panel is clicked or a selection is made in a location control.
   * Use in conjunction with a map_panel with, e.g.
   *   clickForSpatialRef=false
   *   locationLayerName=indicia:detail_locations
   *   locationLayerFilter=website_id=n
   * and a location_select with e.g.
   *   searchUpdatesSref=true
   *   validation="required"
   *   blankText="Select..."
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>hidden_text</b></br>
   * Template used for the hidden text HTML element.
   * </li>
   * </ul>
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldame</b><br/>
   * Required. The name of the database field the sref control is bound to. Defaults to sample:entered_sref.
   * The system field and geom field is automatically constructed from this.</li>
   * <li><b>default</b><br/>
   * Optional. The default spatial reference to assign to the control. This is overridden when reloading a
   * record with existing data for this control.</li>
   * <li><b>defaultSys</b><br/>
   * Optional. The default spatial reference system to assign to the control. This is overridden when reloading a
   * record with existing data for this control.</li>
   * </ul>
   * @return string HTML to insert into the page for the location sref control.
   */
  public static function sref_hidden($options) {

    $options = array_merge(array(
      'id' => 'imp-sref',
      'fieldname' => 'sample:entered_sref',
    ), $options);
    $options['default'] = self::check_default_value($options['fieldname'],
      array_key_exists('default', $options) ? $options['default'] : '');

    // remove validation as field will be hidden
    if (array_key_exists($options['fieldname'], self::$default_validation_rules)) {
      unset(self::$default_validation_rules[$options['fieldname']]);
    }
    if (array_key_exists('validation', $options)) {
      unset($options['validation']);
    }

    $r = self::hidden_text($options);

    $sysOptions['id'] = $options['id'] . '-system';
    $sysOptions['fieldname'] = $options['fieldname'] . '_system';
    $sysOptions['default'] = self::check_default_value($sysOptions['fieldname'],
      array_key_exists('defaultSys', $options) ? $options['defaultSys'] : '');

    $r .= self::hidden_text($sysOptions);

    return $r;
  }

  /**
   * A version of the autocomplete control preconfigured for species lookups.
   * The output of this control can be configured using the following templates:
   * * **autocomplete** - Defines a hidden input and a visible input, to hold the underlying database ID and to
   *   allow input and display of the text search string respectively.
   * * **autocomplete_javascript** - Defines the JavaScript which will be inserted onto the page in order to
   *   activate the autocomplete control.
   *
   * @param type $options Array of configuration options with the following possible entries.
   * * **cacheLookup** - Defaults to true. If true, uses cache_taxon_searchterms for species name lookup
   *   rather than detail_taxa_taxon_lists. The former is faster and tolerates punctuation and spacing errors,
   *   but the available data to lookup against may not be up to date if the cache tables are not populated.
   * * **speciesIncludeAuthorities** - include author strings in species names. Default false.
   * * **speciesIncludeBothNames** - include both latin and common names. Default false.
   * * **speciesIncludeTaxonGroup** - include the taxon group for each shown name. Default false.
   * * **speciesIncludeIdDiff** - include identification difficulty icons. Default true.
   * * **speciesNameFilterMode** - Optional. Method of filtering the available species names (both for
   * * initial population into the grid and additional rows). Options are
   *   * preferred - only preferred names
   *   * currentLanguage - only names in the language identified by the language option are included
   *   * excludeSynonyms - all names except synonyms (non-preferred latin names) are included.
   * * **extraParams** - Should contain the read authorisation array and taxon_list_id to filter against.
   * * **warnIfNoMatch** - Should the autocomplete control warn the user if they leave the control whilst
   *   searching and then nothing is matched? Default true.
   * * **>matchContains** - If true, then the search looks for matches which contain the search
   *   characters. Otherwise, the search looks for matches which start with the search characters. Default false.
   * * **outputPreferredNameToSelector** - If set, then the contents of the HTML element with the matching selector are
   *   replaced with the preferred name of the selected species when chosen. Default false.
   *
   * @return string Html for the species autocomplete control.
   */
  public static function species_autocomplete($options) {
    global $indicia_templates;
    $options = array_merge(array(
      'cacheLookup' => true,
      'selectMode' => false
    ), $options);
    if (empty($indicia_templates['format_species_autocomplete_fn']))
      self::build_species_autocomplete_item_function($options);
    $db = data_entry_helper::get_species_lookup_db_definition($options['cacheLookup']);
    // get local vars for the array
    extract($db);
    $options['extraParams']['orderby'] = $options['cacheLookup'] ? 'original,preferred,preferred_taxon' : 'taxon';
    $options['extraParams']['sortdir'] = $options['cacheLookup'] ? 'ASC,DESC,ASC' : 'ASC';
    $options = array_merge(array(
      'fieldname'=>'occurrence:taxa_taxon_list_id',
      'table'=>$tblTaxon,
      'captionField'=>$colSearch,
      'captionFieldInEntity'=>'taxon',
      'valueField'=>$colId,
      'formatFunction'=>empty($indicia_templates['format_species_autocomplete_fn']) ? $indicia_templates['taxon_label'] : $indicia_templates['format_species_autocomplete_fn'],
      'simplify'=>$options['cacheLookup'] ? 'true' : 'false',
      'outputPreferredNameToSelector' => false
    ), $options);
    if (isset($duplicateCheckFields))
      $options['duplicateCheckFields']=$duplicateCheckFields;
    $options['extraParams'] += self::get_species_names_filter($options);
    if (!empty($options['default']) && empty($options['defaultCaption'])) {
      // We've been given an attribute value but no caption for the species name in the data to load for an existing record. So look it up.
      $r = self::get_population_data(array(
        'table'=>'cache_taxa_taxon_list',
        'extraParams'=>array('nonce'=>$options['extraParams']['nonce'],'auth_token'=>$options['extraParams']['auth_token'])+
          array('id'=>$options['default'],'columns'=>"taxon")
      ));
      $options['defaultCaption']=$r[0]['taxon'];
    }
    if ($options['outputPreferredNameToSelector']) {
      self::$javascript .= "  $('#occurrence\\\\:taxa_taxon_list_id').change(function(evt, data) {
        if (typeof data!=='undefined') {
          $('$options[outputPreferredNameToSelector]').html(data.preferred_taxon);
        }
      });\n";
    }
    return self::autocomplete($options);
  }

  /**
   * Builds a JavaScript function to format the species shown in the species autocomplete.
   *
   * @param array $options Options array with the following entries:
   * * **cacheLookup** - Used cached version of lookup tables for better performance. Default true.
   * * **speciesIncludeAuthorities** - include author strings in species names. Default false.
   * * **speciesIncludeBothNames** - include both latin and common names. Default false.
   * * **speciesIncludeTaxonGroup** - include the taxon group for each shown name. Default false.
   * * **speciesIncludeIdDiff** - include identification difficulty icons. Default true.
   */
  public static function build_species_autocomplete_item_function($options) {
    global $indicia_templates;
    $options = array_merge(array(
      'cacheLookup' => TRUE,
      'speciesIncludeAuthorities' => FALSE,
      'speciesIncludeBothNames' => FALSE,
      'speciesIncludeTaxonGroup' => FALSE,
      'speciesIncludeIdDiff' => TRUE
    ), $options);
    // Need bools as strings
    $options['speciesIncludeAuthorities'] =
      $options['speciesIncludeAuthorities'] ? 'true' : 'false';
    $options['speciesIncludeBothNames'] =
      $options['speciesIncludeBothNames'] ? 'true' : 'false';
    $options['speciesIncludeTaxonGroup'] =
      $options['speciesIncludeTaxonGroup'] ? 'true' : 'false';
    $options['speciesIncludeIdDiff'] =
      $options['speciesIncludeIdDiff'] ? 'true' : 'false';
    // always include the searched name. In this JavaScript we need to behave slightly differently
    // if using the cached as opposed to the standard versions of taxa_taxon_list.
    $db = json_encode(data_entry_helper::get_species_lookup_db_definition($options['cacheLookup']));
    $fn = <<<JS
function(item) {
  var r;
  var synText;
  var nameTest;
  var db = $db;
  var speciesIncludeAuthorities = $options[speciesIncludeAuthorities];
  var speciesIncludeBothNames = $options[speciesIncludeBothNames];
  var speciesIncludeTaxonGroup = $options[speciesIncludeTaxonGroup];
  var speciesIncludeIdDiff = $options[speciesIncludeIdDiff];

  if (item[db.colLanguage]!==null && item[db.colLanguage].toLowerCase()===db.valLatinLanguage) {
    r = '<em>' + item[db.colTaxon] + '</em>';
  } else {
    r = '<span>' + item[db.colTaxon] + '</span>';
  }
  if (speciesIncludeAuthorities) {
    if (item[db.colAuthority]) {
      r += ' ' + item[db.colAuthority];
    }
  }
  // This bit optionally adds '- common' or '- latin' depending on what was being searched
  if (speciesIncludeBothNames) {
    nameTest = (speciesIncludeAuthorities && 
      (item[db.colPreferred] !== item[db.colTaxon] || item[db.colPreferredAuthority] !==item[db.colAuthority]))
      || (!speciesIncludeAuthorities &&
      item[db.colPreferred] !== item[db.colTaxon])
      
    if (item.preferred === 't' && item[db.colCommon] !== item[db.colTaxon] && item[db.colCommon]) {
      r += '<br/>' + item[db.colCommon];
    } else if (item.preferred==='f' && nameTest && item[db.colPreferred]) {
      synText = item.language_iso==='lat' ? 'syn. of' : '';
      r += '<br/>[';
      if (item.language_iso==='lat') {
        r += 'syn. of ';
      }
      r += '<em>' + item[db.colPreferred] + '</em>';
      if (speciesIncludeAuthorities) {
        if (item[db.PreferredAuthority]) {
          r += ' ' + item[db.colPreferredAuthority];
        }
      }
      r += ']';
    }
  }
  if (speciesIncludeTaxonGroup) {
    r += '<br/><strong>' + item.taxon_group + '</strong>';
  }
  if (speciesIncludeIdDiff &&
      item.identification_difficulty && item.identification_difficulty>1) {
    item.icon = ' <span ' +
        'class="item-icon id-diff id-diff-' + item.identification_difficulty + '" ' +
        'data-diff="' + item.identification_difficulty + '" ' +
        'data-rule="' + item.id_diff_verification_rule_id + '"></span>';
    r += item.icon;
  }
  return r;
}

JS;
    // Set it into the indicia templates
    $indicia_templates['format_species_autocomplete_fn'] = $fn;
  }

  /**
   * Outputs a div which will be populated with a summary of the data entered into the form.
   *
   * Best used on a tab or wizard based data entry form on the last page so that the data entered can have a final
   * check.
   *
   * @param $options
   * * *id* - the ID of the outer element, defaults to review-input.
   * * *caption* - the caption to display in the header.
   * * *class* - the CSS class to apply to the outer element, defaults to ui-widget.
   * * *headerClass* - the CSS class to apply to the header element, defaults to ui-widget-header.
   * * *contentClass* - the CSS class to apply to the element containing the form values, defaults to ui-widget-content.
   * * *exclude* - array of controls to exclude by ID, defaults to ["sample:entered_sref_system"].
   *
   */
  public static function review_input($options) {
    global $indicia_templates;
    self::add_resource('review_input');
    $options = array_merge(array(
      'id' => 'review-input',
      'caption' => 'Review information',
      'class' => 'ui-widget',
      'headerClass' => 'ui-widget-header',
      'contentClass' => 'ui-widget-content',
      'exclude' => array('sample:entered_sref_system')
    ), $options);
    $exclude = json_encode($options['exclude']);
    self::$javascript .= <<<RIJS
$('#$options[id]').reviewInput({
  exclude: $exclude
});

RIJS;
    $options['contentId'] = "$options[id]-content";
    $class = empty($options['class']) ? '' : " class=\"$options[class]\"";
    $headerClass = empty($options['headerClass']) ? '' : " class=\"$options[headerClass]\"";
    $contentClass = empty($options['contentClass']) ? '' : " class=\"$options[contentClass]\"";
    $caption = lang::get($options['caption']);
    return str_replace(
      array('{id}', '{contentId}', '{class}', '{headerClass}', '{contentClass}', '{caption}'),
      array(" id=\"$options[id]\"", " id=\"$options[contentId]\"", $class, $headerClass, $contentClass, $caption),
      $indicia_templates['review_input']
    );
  }

 /**
  * Helper function to generate a species checklist from a given taxon list.
  *
  * <p>This function will generate a flexible grid control with one row for each species
  * in the specified list. For each row, the control will display the list preferred term
  * for that species, a checkbox to indicate its presence, and a series of cells for a set
  * of occurrence attributes passed to the control.</p>
  *
  * <p>Further, the control will incorporate the functionality to add extra terms to the
  * control from the parent list of the one given. This will take the form of an autocomplete
  * box against the parent list which will add an extra row to the control upon selection.</p>
  *
  * <p>To change the format of the label displayed for each taxon in the grid
  * rows that are pre-loaded into the grid, use the global $indicia_templates
  * variable to set the value for the entry 'taxon_label'. The tags available in
  * the template are {taxon}, {preferred_name}, {authority} and {common}. This
  * can be a PHP snippet if PHPtaxonLabel is set to true.</p>
  *
  * <p>To change the format of the label displayed for each taxon in the
  * autocomplete used for searching for species to add to the grid, use the
  * global $indicia_templates variable to set the value for the entry
  * 'format_species_autocomplete_fn'. This must be a JavaScript function which
  * takes a single parameter. The parameter is the item returned from the
  * database with attributes taxon, preferred ('t' or 'f'), preferred_name,
  * common, authority, taxon_group, language. The function must return the
  * string to display in the autocomplete list.</p>
  *
  * <p>To perform an action on the event of a new row being added to the grid,
  * write a JavaScript function taking arguments (data, row) and add to the
  * array hook_species_checklist_new_row, where data is an object containing the
  * details of the taxon row as loaded from the data services.</p>
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>listId</b><br/>
  * Optional. The ID of the taxon_lists record which is to be used to obtain the
  * species or taxon list. This is equired unless lookupListId is provided.</li>
  * <li><b>occAttrs</b><br/>
  * Optional integer array, where each entry corresponds to the id of the desired attribute in the
  * occurrence_attributes table. If omitted, then all the occurrence attributes for this survey are loaded.</li>
  * <li><b>occAttrClasses</b><br/>
  * String array, where each entry corresponds to the css class(es) to apply to the corresponding
  * attribute control (i.e. there is a one to one match with occAttrs). If this array is shorter than
  * occAttrs then all remaining controls re-use the last class.</li>
  * <li><b>occAttrOptions</b><br/>
  * array, where the key to each item is the id of an attribute and the item is an array of options
  * to pass to the control for this atrtribute.</li>
  * <li><b>extraParams</b><br/>
  * Associative array of items to pass via the query string to the service calls used for taxon names lookup. This
  * should at least contain the read authorisation array.</li>
  * <li><b>lookupListId</b><br/>
  * Optional. The ID of the taxon_lists record which is to be used to select taxa from when adding
  * rows to the grid. If specified, then an autocomplete text box and Add Row button are generated
  * automatically allowing the user to pick a species to add as an extra row.</li>
  * <li><b>cacheLookup</b></li>
  * Optional. Set to true to select to use a cached version of the lookup list when
  * searching for species names using the autocomplete control, or set to false to use the
  * live version (default). The latter is slower and places more load on the warehouse so should only be
  * used during development or when there is a specific need to reflect taxa that have only
  * just been added to the list.
  * <li><b>taxonFilterField</b><br/>
  * If the list of species to be made available for recording is to be limited
  * (either by species or taxon group), allows selection of the field to filter
  * against. Options are none (default), preferred_name, taxon_meaning_id,
  * taxa_taxon_list_id, taxon_group. If filtering for a large list of taxa then
  * taxon_meaning_id or taxa_taxon_list_id is more efficient.
  * </li>
  * <li><b>taxonFilter</b><br/>
  * If taxonFilterField is not set to none, then pass an array of values to filter against, i.e. an array of
  * taxon preferred names, taxon meaning ids or taxon group titles.
  * </li>
  * <li><b>usersPreferredGroups</b><br/>
  * If the user has defined a list of taxon groups they like to record, then
  * supply an array of the taxon group IDs in this parameter. This lets the user
  * easily opt to record against their chosen groups.
  * </li>
  * <li><b>userControlsTaxonFilter</b><br/>
  * If set to true, then a filter button in the title of the species input
  * column allows the user to configure the filter applied to which taxa are
  * available to select from, e.g. which taxon groups can be picked from. Only
  * applies when lookupListId is set.
  * </li>
  * <li><b>speciesNameFilterMode</b><br/>
  * Optional. Method of filtering the available species names (both for initial
  * population into the grid and additional rows). Options are
  *   preferred - only preferred names
  *   currentLanguage - only names in the language identified by the language option are included
  *   excludeSynonyms - all names except synonyms (non-preferred latin names) are included.
  * </li>
  * <li><b>header</b><br/>
  * Include a header row in the grid? Defaults to true.</li>
  * <li><b>columns</b><br/>
  * Number of repeating columns of output. For example, a simple grid of species
  * checkboxes could be output in 2 or 3 columns.Defaults to 1.</li>
  * <li><b>rowInclusionCheck</b><br/>
  * Defines how the system determines whether a row in the grid actually
  * contains an occurrence or not. There are 4 options: <br/>
  * checkbox - a column is included in the grid containing a presence checkbox.
  * If checked then an occurrence is created for the row. This is the default
  * unless listId is not set.<br/>
  * alwaysFixed - occurrences are created for all rows in the grid. Rows cannot
  * be removed from the grid apart from newly added rows.<br/>
  * alwaysRemovable - occurrences are created for all rows in the grid. Rows can
  * always be removed from the grid. Best used with no listId so there are no
  * default taxa in the grid, otherwise editing an existing sample will re-add
  * all the existing taxa. This is the default when listId is not set, but
  * lookupListId is set.<br/>
  * hasData - occurrences are created for any row which has a data value
  * specified in at least one of its columns. <br/>
  * This option supercedes the checkboxCol option which is still recognised for
  * backwards compatibility.</li>
  * <li><b>hasDataIgnoreAttrs</b><br/>
  * Optional integer array, where each entry corresponds to the id of an
  * attribute that should be ignored when doing the hasData row inclusion check.
  * If a column has a default value, especially a gridIdAttribute, you may not
  * wantit to trigger creation of an occurrence so include it in this array.</li>
  * <li><b>class</b><br/>
  * Optional. CSS class names to add to the control.</li>
  * <li><b>cachetimeout</b><br/>
  * Optional. Specifies the number of seconds before the data cache times out - i.e. how long
  * after a request for data to the Indicia Warehouse before a new request will refetch the data,
  * rather than use a locally stored (cached) copy of the previous request. This speeds things up
  * and reduces the loading on the Indicia Warehouse. Defaults to the global website-wide value:
  * if this is not specified then 1 hour.</li>
  * <li><b>survey_id</b><br/>
  * Optional. Used to determine which attributes are valid for this website/survey combination</li>
  * <li><b>occurrenceComment</b><br/>
  * Optional. If set to true, then an occurrence comment input field is included on each row.</li>
  * <li><b>occurrenceSensitivity</b><br/>
  * Optional. If set to true, then an occurrence sensitivity drop-down selector is included on each row. Can also be set
  * to the size of a grid square as an integer and a checkbox will be made available for enabling this level of blur.
  * Options for the grid square size are in metres, e.g. 100, 1000, 2000, 10000, 100000.</li>
  * <li><b>spatialRefPerRow</b><br/>
  * Optional. If set to true, then a spatial reference column is included on each row. When submitted, each unique
  * spatial reference will cause a subsample to be included in the submission allowing more precise locations to be
  * defined for some records.</li>
  * <li><b>mediaTypes</b><br/>
  * Optional. Array of media types that can be uploaded. Choose from Audio:Local, Audio:SoundCloud, Image:Flickr,
  * Image:Instagram, Image:Local, Image:Twitpic, Pdf:Local, Social:Facebook, Social:Twitter, Video:Youtube,
  * Video:Vimeo.
  * Currently not supported for multi-column grids.</li>
  * <li><b>resizeWidth</b><br/>
  * If set, then the image files will be resized before upload using this as the maximum pixels width.
  * </li>
  * <li><b>resizeHeight</b><br/>
  * If set, then the image files will be resized before upload using this as the maximum pixels height.
  * </li>
  * <li><b>resizeQuality</b><br/>
  * Defines the quality of the resize operation (from 1 to 100). Has no effect
  * unless either resizeWidth or resizeHeight are non-zero.
  * <li><b>colWidths</b><br/>
  * Optional. Array containing percentage values for each visible column's
  * width, with blank entries for columns that are not specified. If the array
  * is shorter than the actual number of columns then the remaining columns use
  * the default width determined by the browser. Ignored if checklist is
  * responsive and hides columns.</li>
  * <li><b>attrCellTemplate</b><br/>
  * Optional. If specified, specifies the name of the template (in global $indicia_templates) to use
  * for each cell containing an attribute input control. Valid replacements are {label}, {class} and {content}.
  * Default is attribute_cell.</li>
  * <li><b>language</b><br/>
  * Language used to filter lookup list items in attributes. ISO 639:3 format. </li>
  * <li><b>PHPtaxonLabel</b></li>
  * If set to true, then the taxon_label template should contain a PHP statement
  * that returns the HTML to display for each taxon's label. Otherwise the
  * template should be plain HTML. Defaults to false.</li>
  * <li><b>useLoadedExistingRecords</b></li>
  * Optional. Defaults to false. Set to true to prevent a grid from making a web
  * service call to load existing occurrence data when reloading a sample. This
  * can be useful if there are more than one species checklist on the page such
  * as when species input is split across several tabs - the first can load all
  * the data and subsequent grids just display the appropriate records depending
  * on the species they are configured to show.</li>
  * <li><b>reloadExtraParams</b></li>
  * Set to an array of additional parameters such as filter criteria to pass to
  * the service request used to load existing records into the grid when
  * reloading a sample. Especially useful when there are more than one species
  * checklist on a single form, so that each grid can display the appropriate
  * output.</li>
  * <li><b>subSpeciesColumn</b>
  * If true and doing grid based data entry with lookupListId set so allowing
  * the recorder to add species they choose to the bottom of the grid,
  * subspecies will be displayed in a separate column so the recorder picks the
  * species first then the subspecies. The species checklist must be configured
  * as a simple 2 level list so that species are parents of the subspecies. For
  * performance reasons, this option forces the cacheLookup option to be set to
  * true therefore it requires the cache_builder module to be running on the
  * warehouse. Defaults to false.</li>
  * <li><b>subSpeciesRemoveSspRank</b>
  * Set to true to force the displayed subspecies names to remove the rank
  * (var., forma, ssp) etc. Useful if all subspecies are the same rank.
  * </li>
  * </ul>
  * The output of this control can be configured using the following templates:
  * <ul>
  * <li><b>file_box</b></br>
  * When image upload for records is enabled, outputs the HTML container which will contain the
  * upload button and images.
  * </li>
  * <li><b>file_box_initial_file_info</b></br>
  * When image upload for records is enabled, this template provides the HTML for the outer container
  * for each displayed image, including the header and remove file button. Has an element with
  * class set to media-wrapper into which images themselves will be inserted.
  * </li>
  * <li><b>file_box_uploaded_image</b></br>
  * Template for the HTML for each uploaded image, including the image, caption input
  * and hidden inputs to define the link to the database. Will be inserted into the
  * file_box_initial_file_info template's media-wrapper element.
  * </li>
  * <li><b>taxon_label_cell</b></br>
  * Generates the label shown for the taxon name for each added row.
  * </li>
  * <li><b>format_species_autocomplete_fn</b></br>
  * Can be set to an optional JavaScript function which formats the contents of the species
  * autocomplete's search list items.
  * </li>
  * <li><b>taxon_label</b></br>
  * If format_species_autocomplete_fn is not set, then this provides an HTML template for the
  * contents of the species autocomplete's search list items.
  * </li>
  * <li><b>attribute_cell</b></br>
  * HTML wrapper for cells containing attribute inputs.
  * </li>
  * <li><b>attributeIds</b><br/>
  * Provide an array of occurrence attribute IDs if you want to limit those
  * shown in the grid. The default list of attributes shown is the list
  * associated with the survey on the warehouse, but this option allows you to
  * ignore some. An example use of this might be when you have multiple grids on
  * the page each supporting a different species group with different attributes.
  * </li>
  * <li><b>gridIdAttributeId</b><br/>
  * If you have multiple grids on one input form, then you can create an
  * occurrence attribute (text) for your survey which will store the ID of the
  * grid used to create the record. Provide the attribute's ID through this
  * parameter so that the grid can automatically save the value and use it when
  * reloading records, so that the records are reloaded into the correct grid.
  * To do this, you would need to set a unique ID for each grid using the id
  * parameter. You can combine this with the attributeIds parameter to show
  * different columns for each grid.
  * </li>
  * <li><b>speciesControlToUseSubSamples</b>
  * Optional. Enables support for sub samples in the grid where input records
  * can be allocated to different sub samples, e.g. when inputting a list of
  * records at different places. Default false.
  * </li>
  * <li><b>subSamplePerRow</b>
  * Optional. Requires speciesControlToUseSubSamples to be set to true, then if
  * this is also true it generates a sub-sample per row in the grid. It is then
  * necessary to write code which processes the submission to at least a spatial
  * reference for each sub sample. This might be used when an occurrence
  * attribute in the grid can be used to calculate the sub-sample's spatial
  * reference, such as when capturing the reticules and bearing for a cetacean
  * sighting.
  * </li>
  * <li><b>subSampleSampleMethodID</b>
  * Optional. sample_method_id to use for the subsamples.
  * </li>
  * <li><b>copyDataFromPreviousRow</b>
  * Optional. When enabled, the system will copy data from the previous row into
  * new rows on the species grid. The data is copied automatically when the new
  * row is created and also when edits are made to the previous row. The columns
  * to copy are determined  by the previousRowColumnsToInclude option.
  * </li>
  * <li><b>previousRowColumnsToInclude</b>
  * Optional. Requires copyDataFromPreviousRow to be set to true. Allows the
  * user to specify which columns of data from the previous row will be copied
  * into a new row on the species grid. Comma separated list of column titles,
  * non-case or white space sensitive. Any unrecognised columns are ignored and
  * the images column cannot be copied.
  * </li>
  * <li><b>sticky</b>
  * Optional, defaults to true. Enables sticky table headers if supported by the host site (e.g. Drupal).
  * </li>
  * <li><b>numValues</b><br/>
  * Optional. Number of requested values in the species autocomplete drop down list. Defaults to 20.
  * Note that, because items with matching taxon_meaning are filtered out by the parse function in
  * addRowToGrid.js::autocompleterSettingsToReturn the list may contain fewer than numValues.
  * </li>
  * <li><b>selectMode</b>
  * Should the species autocomplete used for adding new rows simulate a select
  * drop down control by adding a drop down arrow after the input box which,
  * when clicked, populates the drop down list with all search results to a
  * maximum of numValues. This is similar to typing * into the box. Default
  * false.
  * </li>
  * <li><b>speciesColTitle</b>
  * Title for the species column which will be looked up in lang files. If not set, uses
  * species_checklist.species.
  * </li>
  * <li><b>responsive</b>
  * Set to true to enable responsive behaviour for the grid.
  * Used in conjunction with the responsiveCols and responsiveOpts options.
  * </li>
  * <li><b>responsiveOpts</b>
  * Set to an array of options to pass to FooTable to make the table responsive.
  * Used in conjunction with the responsiveCols option to determine
  * which columns are hidden at different breakpoints.
  * Supported options are
  *   - breakpoints: an array keyed by breakpoint name with values of screen
  *     width at which to apply the breakpoint. The footable defaults, which
  *     cannot be overridden, are
  *       - phone, 480
  *       - tablet, 1024
  * </li>
  * <li><b>responsiveCols</b>
  * An array, keyed by column identifier to determine the behaviour of the
  * column. Each value is an array, keyed by breakpoint name, with boolean
  * values  to indicate whether the column will be hidden when the breakpoint
  * condition is met. Only takes effect if the 'responsive' option is set.
  * Column identifiers are
  *  - sensitive
  *  - comment
  *  - media
  *  - attr<em>N</em> where <em>N</em> is an occurrence attribute id.
  * </li>
  * </ul>
  * @return string HTML for the species checklist input grid.
  */
  public static function species_checklist($options)
  {
    global $indicia_templates;
    $options = self::check_options($options);
    $options = self::get_species_checklist_options($options);
    $classlist = array('ui-widget', 'ui-widget-content', 'species-grid');
    if (!empty($options['class']))
      $classlist[] = $options['class'];
    if ($options['sticky']) {
      $stickyHeaderClass = self::add_sticky_headers($options);
      if (!empty($stickyHeaderClass))
        $classlist[] = $stickyHeaderClass;
    }
    if ($options['subSamplePerRow'])
      // we'll track 1 sample per grid row.
      $smpIdx=0;
    if ($options['columns'] > 1 && count($options['mediaTypes'])>1)
      throw new Exception('The species_checklist control does not support having more than one occurrence per row (columns option > 0) '.
        'at the same time has having the mediaTypes option in use.');
    self::add_resource('json');
    self::add_resource('autocomplete');
    $filterArray = self::get_species_names_filter($options);
    $filterNameTypes = array('all','currentLanguage', 'preferred', 'excludeSynonyms');
    //make a copy of the options so that we can maipulate it
    $overrideOptions = $options;
    //We are going to cycle through each of the name filter types
    //and save the parameters required for each type in an array so
    //that the Javascript can quickly access the required parameters
    foreach ($filterNameTypes as $filterType) {
      $overrideOptions['speciesNameFilterMode'] = $filterType;
      $nameFilter[$filterType] = self::get_species_names_filter($overrideOptions);
      $nameFilter[$filterType] = json_encode($nameFilter[$filterType]);
    }
    if (count($filterArray)) {
      $filterParam = json_encode($filterArray);
      self::$javascript .= "indiciaData['taxonExtraParams-".$options['id']."'] = $filterParam;\n";
      // Apply a filter to extraParams that can be used when loading the initial species list, to get just the correct names.
      if (isset($options['speciesNameFilterMode']) && !empty($options['listId'])) {
        $filterFields = array();
        $filterWheres = array();
        self::parse_species_name_filter_mode($options, $filterFields, $filterWheres);
        if (count($filterWheres))
          $options['extraParams'] += array('query' => json_encode(array('where' => $filterWheres)));
        $options['extraParams'] += $filterFields;
      }
    }
    self::$javascript .= "indiciaData['rowInclusionCheck-".$options['id']."'] = '".$options['rowInclusionCheck']."';\n";
    self::$javascript .= "indiciaData['copyDataFromPreviousRow-".$options['id']."'] = '".$options['copyDataFromPreviousRow']."';\n";
    self::$javascript .= "indiciaData['includeSpeciesGridLinkPage-".$options['id']."'] = '".$options['includeSpeciesGridLinkPage']."';\n";
    self::$javascript .= "indiciaData.speciesGridPageLinkUrl = '".$options['speciesGridPageLinkUrl']."';\n";
    self::$javascript .= "indiciaData.speciesGridPageLinkParameter = '".$options['speciesGridPageLinkParameter']."';\n";
    self::$javascript .= "indiciaData.speciesGridPageLinkTooltip = '".$options['speciesGridPageLinkTooltip']."';\n";
    self::$javascript .= "indiciaData['editTaxaNames-".$options['id']."'] = '".$options['editTaxaNames']."';\n";
    self::$javascript .= "indiciaData['subSpeciesColumn-".$options['id']."'] = '".$options['subSpeciesColumn']."';\n";
    self::$javascript .= "indiciaData['subSamplePerRow-".$options['id']."'] = ".($options['subSamplePerRow'] ? 'true' : 'false').";\n";
    if ($options['copyDataFromPreviousRow']) {
      self::$javascript .= "indiciaData['previousRowColumnsToInclude-".$options['id']."'] = '".$options['previousRowColumnsToInclude']."';\n";
      self::$javascript .= "indiciaData.langAddAnother='" . lang::get('Add another') . "';\n";
    }
    if (count($options['mediaTypes'])) {
      self::add_resource('plupload');
      // store some globals that we need later when creating uploaders
      $relpath = self::getRootFolder() . self::client_helper_path();
      $interim_image_folder = isset(parent::$interim_image_folder) ? parent::$interim_image_folder : 'upload/';
      self::$javascript .= "indiciaData.uploadSettings = {\n";
      self::$javascript .= "  uploadScript: '" . $relpath . "upload.php',\n";
      self::$javascript .= "  destinationFolder: '" . $relpath . $interim_image_folder."',\n";
      self::$javascript .= "  jsPath: '".self::$js_path."'";
      if (isset($options['resizeWidth'])) {
        self::$javascript .= ",\n  resizeWidth: ".$options['resizeWidth'];
      }
      if (isset($options['resizeHeight'])) {
        self::$javascript .= ",\n  resizeHeight: ".$options['resizeHeight'];
      }
      if (isset($options['resizeQuality'])) {
        self::$javascript .= ",\n  resizeQuality: ".$options['resizeQuality'];
      }
      self::$javascript .= "\n}\n";
      if ($indicia_templates['file_box']!='')
        self::$javascript .= "file_boxTemplate = '".str_replace('"','\"', $indicia_templates['file_box'])."';\n";
      if ($indicia_templates['file_box_initial_file_info']!='')
        self::$javascript .= "file_box_initial_file_infoTemplate = '".str_replace('"','\"', $indicia_templates['file_box_initial_file_info'])."';\n";
      if ($indicia_templates['file_box_uploaded_image']!='')
        self::$javascript .= "file_box_uploaded_imageTemplate = '".str_replace('"','\"', $indicia_templates['file_box_uploaded_image'])."';\n";
    }
    $occAttrControls = array();
    $occAttrs = array();
    $occAttrControlsExisting = array();
    $taxonRows = array();
    $subSampleRows = array();
    // Load any existing sample's occurrence data into $entity_to_load
    if (isset(self::$entity_to_load['sample:id']) && $options['useLoadedExistingRecords']===false) {
      self::preload_species_checklist_occurrences(self::$entity_to_load['sample:id'], $options['readAuth'],
          $options['mediaTypes'], $options['reloadExtraParams'], $subSampleRows,
          $options['speciesControlToUseSubSamples'] || $options['spatialRefPerRow'],
          (isset($options['subSampleSampleMethodID']) ? $options['subSampleSampleMethodID'] : ''),
          $options['spatialRefPerRow']);
    }
    // load the full list of species for the grid, including the main checklist plus any additional species in the reloaded occurrences.
    $taxalist = self::get_species_checklist_taxa_list($options, $taxonRows);
    // If we managed to read the species list data we can proceed
    if (! array_key_exists('error', $taxalist)) {
      $attrOptions = array(
        'id' => null
      ,'valuetable'=>'occurrence_attribute_value'
      ,'attrtable'=>'occurrence_attribute'
      ,'key'=>'occurrence_id'
      ,'fieldprefix'=>"sc:-idx-::occAttr"
      ,'extraParams'=>$options['readAuth']
      ,'survey_id'=>array_key_exists('survey_id', $options) ? $options['survey_id'] : null
      );
      if (isset($options['attributeIds'])) {
        // make sure we load the grid ID attribute
        if (!empty($options['gridIdAttributeId']) && !in_array($options['gridIdAttributeId'], $options['attributeIds']))
          $options['attributeIds'][] = $options['gridIdAttributeId'];
        $attrOptions['extraParams'] += array('query'=>json_encode(array('in'=>array('id'=>$options['attributeIds']))));
      }
      $attributes = self::getAttributes($attrOptions);
      // Merge in the attribute options passed into the control which can override the warehouse config
      if (isset($options['occAttrOptions'])) {
        foreach ($options['occAttrOptions'] as $attrId => $attr) {
          if (isset($attributes[$attrId]))
            $attributes[$attrId] = array_merge($attributes[$attrId], $attr);
        }
      }
      // Get the attribute and control information required to build the custom occurrence attribute columns
      self::species_checklist_prepare_attributes($options, $attributes, $occAttrControls, $occAttrControlsExisting, $occAttrs);
      $beforegrid = '<span style="display: none;">Step 1</span>'."\n";
      if (!empty($options['lookupListId'])) {
        $beforegrid .= self::get_species_checklist_clonable_row($options, $occAttrControls, $attributes);
      }
      $onlyImages = true;
      if ($options['mediaTypes']) {
        foreach($options['mediaTypes'] as $mediaType) {
          if (substr($mediaType, 0, 6)!=='Image:')
            $onlyImages=false;
        }
      }
      $grid = self::get_species_checklist_header($options, $occAttrs, $onlyImages);
      $rows = array();
      $imageRowIdxs = array();
      $rowIdx = 0;
      // tell the addTowToGrid javascript how many rows are already used, so it has a unique index for new rows
      self::$javascript .= "indiciaData['gridCounter-".$options['id']."'] = ".count($taxonRows).";\n";
      self::$javascript .= "indiciaData['gridSampleCounter-".$options['id']."'] = ".count($subSampleRows).";\n";
      // Loop through all the rows needed in the grid
      // Get the checkboxes (hidden or otherwise) that indicate a species is present
      if (is_array(self::$entity_to_load)) {
        $presenceValues = preg_grep("/^sc:[0-9]*:[0-9]*:present$/", array_keys(self::$entity_to_load));
      }
      // if subspecies are stored, then need to load up the parent species info into the $taxonRows data
      if ($options['subSpeciesColumn']) {
        self::load_parent_species($taxalist, $options);
        if ($options['subSpeciesRemoveSspRank'])
          // remove subspecific rank information from the displayed subspecies names by passing a regex
          self::$javascript .= "indiciaData.subspeciesRanksToStrip='".lang::get('(form[a\.]?|var\.?|ssp\.)')."';\n";
      }
      // track if there is a row we are editing in this grid
      $hasEditedRecord = false;
      if ($options['mediaTypes']) {
        $mediaBtnLabel = lang::get($onlyImages ? 'Add images' : 'Add media');
        $mediaBtnClass = 'sc' . $onlyImages ? 'Image' : 'Media' . 'Link';
      }
      foreach ($taxonRows as $txIdx => $rowIds) {
        $ttlId = $rowIds['ttlId'];
        $loadedTxIdx = isset($rowIds['loadedTxIdx']) ? $rowIds['loadedTxIdx'] : -1;
        $existingRecordId = isset($rowIds['occId']) ? $rowIds['occId'] : false;
        // Multi-column input does not work when image upload allowed
        $colIdx = count($options['mediaTypes']) ? 0 : (int)floor($rowIdx / (count($taxonRows)/$options['columns']));
        // Find the taxon in our preloaded list data that we want to output for this row
        $taxonIdx = 0;
        while ($taxonIdx < count($taxalist) && $taxalist[$taxonIdx]['id'] != $ttlId) {
          $taxonIdx += 1;
        }
        if ($taxonIdx >= count($taxalist))
          continue; // next taxon, as this one was not found in the list
        $taxon = $taxalist[$taxonIdx];
        // If we are using the sub-species column then when the taxon has a parent (=species) this goes in the
        // first column and we put the subsp in the second column in a moment.
        if (isset($options['subSpeciesColumn']) && $options['subSpeciesColumn'] && !empty($taxon['parent']))
          $firstColumnTaxon=$taxon['parent'];
        else
          $firstColumnTaxon=$taxon;
        // map field names if using a cached lookup
        if ($options['cacheLookup'])
          $firstColumnTaxon = $firstColumnTaxon + array(
              'preferred_name' => $firstColumnTaxon['preferred_taxon'],
              'common' => $firstColumnTaxon['default_common_name']
            );
        // Get the cell content from the taxon_label template
        $firstCell = self::mergeParamsIntoTemplate($firstColumnTaxon, 'taxon_label');
        // If the taxon label template is PHP, evaluate it.
        if ($options['PHPtaxonLabel']) $firstCell = eval($firstCell);
        // Now create the table cell to contain this.
        $colspan = !empty($options['lookupListId']) && $options['rowInclusionCheck']!='alwaysRemovable' ? ' colspan="2"' : '';
        $row = '';
        // Add a delete button if the user can remove rows, add an edit button if the user has the edit option set, add a page link if user has that option set.
        if ($options['rowInclusionCheck']=='alwaysRemovable') {
          $imgPath = empty(self::$images_path) ? self::relative_client_helper_path()."../media/images/" : self::$images_path;
          $speciesGridLinkPageIconSource = $imgPath."nuvola/find-22px.png";
          if ($options['editTaxaNames']) {
            $row .= '<td class="row-buttons">
                     <img class="action-button remove-row" src='.$imgPath.'nuvola/cancel-16px.png>
                     <img class="action-button edit-taxon-name" src='.$imgPath.'nuvola/package_editors-16px.png>';
            if ($options['includeSpeciesGridLinkPage']) {
              $row .= '<img class="species-grid-link-page-icon" title="'.$options['speciesGridPageLinkTooltip'].'" alt="Notes icon" src='.$speciesGridLinkPageIconSource.'>';
            }
            $row .= '</td>';
          } else {
            $row .= '<td class="row-buttons"><img class="action-button remove-row" src='.$imgPath.'nuvola/cancel-16px.png>';
            if ($options['includeSpeciesGridLinkPage']) {
              $row .= '<img class="species-grid-link-page-icon" title="'.$options['speciesGridPageLinkTooltip'].'" alt="Notes icon" src='.$speciesGridLinkPageIconSource.'>';
            }
            $row .= '</td>';
          }
        }
        // if editing a specific occurrence, mark it up
        $editedRecord = isset($_GET['occurrence_id']) && $_GET['occurrence_id']==$existingRecordId;
        $editClass = $editedRecord ? ' edited-record ui-state-highlight' : '';
        $hasEditedRecord = $hasEditedRecord || $editedRecord;
        // Verified records can be flagged with an icon
        //Do an isset check as the npms_paths form for example uses the species checklist, but doesn't use an entity_to_load
        if (isset(self::$entity_to_load["sc:$loadedTxIdx:$existingRecordId:record_status"])) {
          $status = self::$entity_to_load["sc:$loadedTxIdx:$existingRecordId:record_status"];
          if (preg_match('/[VDR]/', $status)) {
            $img = false;
            switch ($status) {
              case 'V' : $img = 'ok'; $statusLabel = 'verified'; break;
              case 'D' : $img = 'dubious'; $statusLabel = 'queried'; break;
              case 'R' : $img = 'cancel'; $statusLabel = 'rejected'; break;
            }
            if ($img) {
              $label = lang::get($statusLabel);
              $title = lang::get('This record has been {1}. Changing it will mean that it will need to be rechecked by an expert.', $label);
              $firstCell .= "<img alt=\"$label\" title=\"$title\" src=\"{$imgPath}nuvola/$img-16px.png\">";
            }
          }
        }
        $row .= str_replace(array('{content}','{colspan}','{editClass}','{tableId}','{idx}'),
          array($firstCell,$colspan,$editClass,$options['id'],$colIdx), $indicia_templates['taxon_label_cell']);
        $row .= self::speciesChecklistGetSubspCell($taxon, $txIdx, $existingRecordId, $options);
        $hidden = ($options['rowInclusionCheck']=='checkbox' ? '' : ' style="display:none"');
        // AlwaysFixed mode means all rows in the default checklist are included as occurrences. Same for
        // AlwayeRemovable except that the rows can be removed.
        // If we are reloading a record there will be an entity_to_load which will indicate whether present should be checked.
        // This has to be evaluated true or false if reloading a submission with errors.
        if ($options['rowInclusionCheck']=='alwaysFixed' || $options['rowInclusionCheck']=='alwaysRemovable' ||
          (self::$entity_to_load!=null && array_key_exists("sc:$loadedTxIdx:$existingRecordId:present", self::$entity_to_load) &&
            self::$entity_to_load["sc:$loadedTxIdx:$existingRecordId:present"] == true)) {
          $checked = ' checked="checked"';
        } else {
          $checked='';
        }
        $row .= "\n<td class=\"scPresenceCell\" headers=\"$options[id]-present-$colIdx\"$hidden>";
        $fieldname = "sc:$options[id]-$txIdx:$existingRecordId:present";
        if ($options['rowInclusionCheck']==='hasData')
          $row .= "<input type=\"hidden\" name=\"$fieldname\" id=\"$fieldname\" value=\"$taxon[id]\"/>";
        else
          // this includes a control to force out a 0 value when the checkbox is unchecked.
          $row .= "<input type=\"hidden\" class=\"scPresence\" name=\"$fieldname\" value=\"0\"/>".
            "<input type=\"checkbox\" class=\"scPresence\" name=\"$fieldname\" id=\"$fieldname\" value=\"$taxon[id]\" $checked />";
        // If we have a grid ID attribute, output a hidden
        if (!empty($options['gridIdAttributeId'])) {
          $gridAttributeId = $options['gridIdAttributeId'];
          if (empty($existingRecordId)) {
            //If in add mode we don't need to include the occurrence attribute id
            $fieldname  = "sc:$options[id]-$txIdx::occAttr:$gridAttributeId";
            $row .= "<input type=\"hidden\" name=\"$fieldname\" id=\"$fieldname\" value=\"$options[id]\"/>";
          } else {
            $search = preg_grep("/^sc:[0-9]*:$existingRecordId:occAttr:$gridAttributeId:".'[0-9]*$/', array_keys(self::$entity_to_load));
            if (!empty($search)) {
              $match = array_pop($search);
              $parts = explode(':',$match);
              //The id of the existing occurrence attribute value is at the end of the data
              $idxOfOccValId = count($parts) - 1;
              //$txIdx is row number in the grid. We cannot simply take the data from entity_to_load as it doesn't contain the row number.
              $fieldname = "sc:$options[id]-$txIdx:$existingRecordId:occAttr:$gridAttributeId:$parts[$idxOfOccValId]";
              $row .= "<input type=\"hidden\" name=\"$fieldname\" id=\"$fieldname\" value=\"$options[id]\"/>";
            }
          }
        }
        $row .= "</td>";
        if ($options['speciesControlToUseSubSamples']) {
          $row .= "\n<td class=\"scSampleCell\" style=\"display:none\">";
          $fieldname = "sc:$options[id]-$txIdx:$existingRecordId:occurrence:sampleIDX";
          $value = $options['subSamplePerRow'] ? $smpIdx : $rowIds['smpIdx'];
          $row .= "<input type=\"hidden\" class=\"scSample\" name=\"$fieldname\" id=\"$fieldname\" value=\"$value\" />";
          $row .= "</td>";
          // always increment the sample index if 1 per row.
          if ($options['subSamplePerRow'])
            $smpIdx++;
        }
        $idx = 0;
        foreach ($occAttrControlsExisting as $attrId => $control) {
          $existing_value='';
          $valId=false;
          if (!empty(data_entry_helper::$entity_to_load)) {
            // Search for the control in the data to load. It has a suffix containing the attr_value_id which we don't know, hence preg.
            $search = preg_grep("/^sc:$loadedTxIdx:$existingRecordId:occAttr:$attrId:".'[0-9]*$/', array_keys(self::$entity_to_load));
            // Does the control post an array of values? If so, we need to ensure that the existing values are handled properly.
            $isArrayControl = preg_match('/name="{?[a-z\-_]*}?\[\]"/', $control);
            if ($isArrayControl) {
              foreach ($search as $subfieldname) {
                // to link each value to existing records, we need to store the value ID in the value data.
                $valueId = preg_match('/(\d+)$/', $subfieldname, $matches);
                $control = str_replace('value="'.self::$entity_to_load[$subfieldname].'"',
                  'value="'.self::$entity_to_load[$subfieldname].':'.$matches[1].'" selected="selected"', $control);
              }
              $ctrlId = str_replace('-idx-', "$options[id]-$txIdx", $attributes[$attrId]['fieldname']);
              // remove [] from the end of the fieldname if present, as it is already in the row template
              $ctrlId = preg_replace('/\[\]$/', '', $ctrlId);
              $loadedCtrlFieldName='-';
            } elseif (count($search)>0) {
              // Got an existing value.
              // Warning - if there are multi-values in play here then it will just load one, because this is NOT an array control.
              // use our preg search result as the field name to load from the existing data array.
              $loadedCtrlFieldName = array_pop($search);
              // Convert loaded field name to our output row index
              $ctrlId = str_replace("sc:$loadedTxIdx:", "sc:$options[id]-$txIdx:", $loadedCtrlFieldName);
              // find out the loaded value record ID
              preg_match("/occAttr:[0-9]+:(?P<valId>[0-9]+)$/", $loadedCtrlFieldName, $matches);
              if (!empty($matches['valId']))
                $valId = $matches['valId'];
              else
                $valId = null;
            }
            else {
              // go for the default, which has no suffix.
              $loadedCtrlFieldName = str_replace('-idx-:', $loadedTxIdx.':'.$existingRecordId, $attributes[$attrId]['fieldname']);
              $ctrlId = str_replace('-idx-:', "$options[id]-$txIdx:$existingRecordId", $attributes[$attrId]['fieldname']);
            }
            if (isset(self::$entity_to_load[$loadedCtrlFieldName]))
              $existing_value = self::$entity_to_load[$loadedCtrlFieldName];
          } else {
            // no existing record, so use a default control ID which excludes the existing record ID.
            $ctrlId = str_replace('-idx-', "$options[id]-$txIdx", $attributes[$attrId]['fieldname']);
            $loadedCtrlFieldName='-';
          }
          if (!$existingRecordId && $existing_value==='' && array_key_exists('default', $attributes[$attrId])) {
            // this case happens when reloading an existing record
            $existing_value = $attributes[$attrId]['default'];
          }
          // inject the field name into the control HTML
          $oc = str_replace('{fieldname}', $ctrlId, $control);
          if ($existing_value<>"") {
            // For select controls, specify which option is selected from the existing value
            if (substr(trim($oc), 0, 7)==='<select') {
              $oc = str_replace('value="'.$existing_value.'"',
                'value="'.$existing_value.'" selected="selected"', $oc);
            } else if(strpos($oc, 'type="checkbox"') !== false) {
              if($existing_value=="1")
                $oc = str_replace('type="checkbox"', 'type="checkbox" checked="checked"', $oc);
            } else {
              // dates (including single day vague dates) need formatting to the local date format.
              if ($attributes[$attrId]['data_type']==='D' || $attributes[$attrId]['data_type']==='V'
                  && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $existing_value)) {
                $d = new DateTime($existing_value);
                $existing_value = $d->format(self::$date_format);
              } elseif (is_array($existing_value))
                $existing_value = implode('',$existing_value);
              $oc = str_replace('value=""', 'value="'.$existing_value.'"', $oc);
            }
          }
          $errorField = "occAttr:$attrId" . ($valId ? ":$valId" : '');
          $error = self::check_errors($errorField);
          if ($error) {
            $oc = str_replace("class='", "class='ui-state-error ", $oc);
            $oc .= $error;
          }
          $headers = $options['id']."-attr$attrId-$colIdx";
          $class = self::speciesChecklistOccAttrClass($options, $idx, $attributes[$attrId]['untranslatedCaption']);
          $class = $class . 'Cell';
          $row .= str_replace(array('{label}', '{class}', '{content}', '{headers}'), array(lang::get($attributes[$attrId]['caption']), $class, $oc, $headers),
            $indicia_templates[$options['attrCellTemplate']]);
          $idx++;
        }
        $row .= self::speciesChecklistSpatialRefPerRowCell($options, $colIdx, $txIdx, $existingRecordId);
        $row .= self::speciesChecklistCommentCell($options, $colIdx, $txIdx, $loadedTxIdx, $existingRecordId);
        $row .= self::speciesChecklistSensitivityCell($options, $colIdx, $txIdx, $existingRecordId);

        // Add a cell for the Add Media button which is hidden if there is
        // existing media.
        if ($options['mediaTypes']) {
          $existingImages = is_array(self::$entity_to_load) ? preg_grep("/^sc:$loadedTxIdx:$existingRecordId:occurrence_medium:id:[a-z0-9]*$/", array_keys(self::$entity_to_load)) : array();
          $row .= "\n<td class=\"ui-widget-content scAddMediaCell\">";
          $style = (count($existingImages)>0) ? ' style="display: none"' : '';
          $fieldname = "add-media:$options[id]-$txIdx:$existingRecordId";
          $row .= "<a href=\"\"$style class=\"add-media-link button $mediaBtnClass\" id=\"$fieldname\">" .
            "$mediaBtnLabel</a>";
          $row .= "</td>";

          // Add a cell for photos in responsive mode.
          if ($options['responsive']) {
            if (count($existingImages) == 0) {
              // The cell is empty
              $ctrlId = "container-sc:{$options['id']}-$txIdx:$existingRecordId:occurrence_medium-" . mt_rand();
              $row .= '<td class="scMediaCell"><div class="scMedia" id="' . $ctrlId . '"></div></td>';
            }
            else {
              // Create a cell containing the popula
              $row .= '<td class="scMediaCell">' . data_entry_helper::file_box(array(
                'table'=>"sc:$options[id]-$txIdx:$existingRecordId:occurrence_medium",
                'loadExistingRecordKey'=>"sc:$loadedTxIdx:$existingRecordId:occurrence_medium",
                'mediaTypes' => $options['mediaTypes'],
                'readAuth' => $options['readAuth']
              )) . '</td>';
            }
          }
        }

        // Add a cell for responsive toggle.
        if ($options['responsive']) {
          $row .= '<td class="footable-toggle-cell"></td>';
        }

        // Are we in the first column of a multicolumn grid, or doing single column grid? If so start new row.
        if ($colIdx === 0) {
          $rows[$rowIdx] = $row;
        } else {
          $rows[$rowIdx % (ceil(count($taxonRows)/$options['columns']))] .= $row;
        }
        $rowIdx++;

        // Add media in a following row when not in responsive mode.
        if ($options['mediaTypes'] && count($existingImages) > 0 && !$options['responsive']) {
          $totalCols = ($options['lookupListId'] ? 2 : 1) + 1 /*checkboxCol*/ + count($occAttrControls) +
            ($options['spatialRefPerRow'] ? 1 : 0) + ($options['occurrenceComment'] ? 1 : 0) +
            ($options['occurrenceSensitivity'] ? 1 : 0) + (count($options['mediaTypes']) ? 1 : 0);
          $rows[$rowIdx]='<td colspan="'.$totalCols.'">'.data_entry_helper::file_box(array(
              'table'=>"sc:$options[id]-$txIdx:$existingRecordId:occurrence_medium",
              'loadExistingRecordKey'=>"sc:$loadedTxIdx:$existingRecordId:occurrence_medium",
              'mediaTypes' => $options['mediaTypes'],
              'readAuth' => $options['readAuth']
            )).'</td>';
          $imageRowIdxs[]=$rowIdx;
          $rowIdx++;
        }
      }
      $grid .= "\n<tbody>\n";
      if (count($rows)>0)
        $grid .= self::species_checklist_implode_rows($rows, $imageRowIdxs);
      $grid .= "</tbody>\n";
      $grid = str_replace(
        array('{class}', '{id}', '{content}'),
        array(' class="'.implode(' ', $classlist).'"', " id=\"$options[id]\"", $grid),
        $indicia_templates['data-input-table']
      );
      // in hasData mode, the wrap_species_checklist method must be notified of the different default
      // way of checking if a row is to be made into an occurrence. This may differ between grids when
      // there are multiple grids on a page.
      if ($options['rowInclusionCheck']=='hasData') {
        $grid .= '<input name="rowInclusionCheck-' . $options['id'] . '" value="hasData" type="hidden" />';
        if (!empty($options['hasDataIgnoreAttrs']))
          $grid .= '<input name="hasDataIgnoreAttrs-' . $options['id'] . '" value="'
            . implode(',', $options['hasDataIgnoreAttrs']) . '" type="hidden" />';
      }
      self::add_resource('addrowtogrid');
      // If the lookupListId parameter is specified then the user is able to add extra rows to the grid,
      // selecting the species from this list. Add the required controls for this.
      if (!empty($options['lookupListId'])) {
        // Javascript to add further rows to the grid
        if (isset($indicia_templates['format_species_autocomplete_fn'])) {
          self::$javascript .= 'formatter = '.$indicia_templates['format_species_autocomplete_fn'];
        } else {
          self::$javascript .= "formatter = '".$indicia_templates['taxon_label']."';\n";
        }
        if (!empty(parent::$warehouse_proxy))
          $url = parent::$warehouse_proxy."index.php/services/data";
        else
          $url = parent::$base_url."index.php/services/data";
        self::$javascript .= "if (typeof indiciaData.speciesGrid==='undefined') {indiciaData.speciesGrid={};}\n";
        self::$javascript .= "indiciaData.speciesGrid['$options[id]']={};\n";
        self::$javascript .= "indiciaData.speciesGrid['$options[id]'].cacheLookup=".($options['cacheLookup'] ? 'true' : 'false').";\n";
        self::$javascript .= "indiciaData.speciesGrid['$options[id]'].numValues=".(!empty($options['numValues']) ? $options['numValues'] : 20).";\n";
        self::$javascript .= "indiciaData.speciesGrid['$options[id]'].selectMode=".(!empty($options['selectMode']) && $options['selectMode'] ? 'true' : 'false').";\n";
        self::$javascript .= "indiciaData.speciesGrid['$options[id]'].matchContains=".(!empty($options['matchContains']) && $options['matchContains'] ? 'true' : 'false').";\n";
        self::$javascript .= "addRowToGrid('$url', '".
          $options['id']."', '".$options['lookupListId']."', {'auth_token' : '".
          $options['readAuth']['auth_token']."', 'nonce' : '".$options['readAuth']['nonce']."'},".
          " formatter);\r\n";
      }
      // If options contain a help text, output it at the end if that is the preferred position
      $options['helpTextClass'] = (isset($options['helpTextClass'])) ? $options['helpTextClass'] : 'helpTextLeft';
      $r = self::get_help_text($options, 'before');
      $r .= $beforegrid . $grid;
      $r .= self::get_help_text($options, 'after');
      self::$javascript .= "$('#".$options['id']."').find('input,select').keydown(keyHandler);\n";
      //nameFilter is an array containing all the parameters required to return data for each of the
      //"Choose species names available for selection" filter types
      self::species_checklist_filter_popup($options, $nameFilter);
      if ($options['subSamplePerRow']) {
        // output a hidden block to contain sub-sample hidden input values.
        $r .= '<div id="'.$options['id'].'-blocks">'.
          self::get_subsample_per_row_hidden_inputs().
          '</div>';
      }
      if ($hasEditedRecord) {
        self::$javascript .= "$('#$options[id] tbody tr').hide();\n";
        self::$javascript .= "$('#$options[id] tbody tr td.edited-record').parent().show();\n";
        self::$javascript .= "$('#$options[id] tbody tr td.edited-record').parent().next('tr.supplementary-row').show();\n";
        $r .= '<p>'.lang::get('You are editing a single record that is part of a larger sample, so any changes to the sample\'s information such as edits to the date or map reference '.
            'will affect the whole sample.')." <a id=\"species-grid-view-all-$options[id]\">".lang::get('View all the records in this sample or add more records.').'</a></p>';
        self::$javascript .= "$('#species-grid-view-all-$options[id]').click(function(e) {
  $('#$options[id] tbody tr').show();
  $(e.currentTarget).hide();
});\n";
        self::$onload_javascript .= "
if ($('#$options[id]').parents('.ui-tabs-panel').length) {
  indiciaFns.activeTab($('#controls'), $('#$options[id]').parents('.ui-tabs-panel')[0].id);
}\n";
      }
      if ($options['mediaTypes']) {
        $r .= self::add_link_popup($options);
        // make the media types setting available to the grid row add js which has to create file uploader controls
        self::$javascript .= "indiciaData.uploadSettings.mediaTypes=".json_encode($options['mediaTypes']).";\n";
      }

      // Add responsive behaviour to table if specified in options.
      if ($options['responsive']) {
        // Add the javascript plugin.
        self::add_resource('indiciaFootableChecklist');
        // Add inline javascript to invoke the plugins on this grid.
        $footable_options = json_encode($options['responsiveOpts']);
        self::$javascript .= "jQuery('#{$options['id']}').indiciaFootableChecklist($footable_options);\n";
      }
      return $r;
    } else {
      return $taxalist['error'];
    }
  }

  /**
   * Adds HTML to the output for a popup dialog to accept input of external media link URLs
   * to attach to records in the species grid.
   *
   * @staticvar boolean $doneAddLinkPopup
   * @param type $options
   * @return string
   */
  private static function add_link_popup($options) {
    if (!isset($options['readAuth']))
      return '';
    if ($options['mediaTypes']) {
      $onlyImages = true;
      $onlyLocal = true;
      $linkMediaTypes=array();
      foreach ($options['mediaTypes'] as $mediaType) {
        $tokens = explode(':', $mediaType);
        if ($tokens[0]!=='Image')
          $onlyImages=false;
        if ($tokens[1]!=='Local') {
          $onlyLocal=false;
          $linkMediaTypes[]=$tokens[1];
        }
      }
    }
    // Output just one add link popup dialog, no matter how many grids there are
    static $doneAddLinkPopup=false;
    $typeTermData = self::get_population_data(array(
      'table'=>'termlists_term',
      'extraParams'=>$options['readAuth']+array('view'=>'cache', 'termlist_title'=>'Media types', 'columns'=>'id,term')
    ));
    $typeTermIdLookup = array();
    foreach ($typeTermData as $record) {
      $typeTermIdLookup[$record['term']]=$record['id'];
    }
    self::$javascript .= "indiciaData.mediaTypeTermIdLookup=".json_encode($typeTermIdLookup).";\n";
    if ($options['mediaTypes'] && !$onlyLocal && !$doneAddLinkPopup) {
      $doneAddLinkPopup=true;
      $readableTypes = array_pop($linkMediaTypes);
      if (count($linkMediaTypes)>0)
        $readableTypes = implode(', ', $linkMediaTypes) . ' ' . lang::get('or') . ' ' . $readableTypes;
      return '<div style="display: none"><div id="add-link-form" title="Add a link to a remote file">
<p class="validateTips">'.lang::get('Paste in the web address of a resource on {1}', $readableTypes).'.</p>
<fieldset>
<label for="name">URL</label>
<input type="text" name="link_url" id="link_url" class="text ui-widget-content ui-corner-all">
<p style="display: none" class="error"></p>
</fieldset>
</div></div>';
    }
    else {
      return '';
    }
  }

  /**
   * Add sticky table headers, if supported by the host site. Returns the class to add to the table.
   */
  private static function add_sticky_headers($options) {
    if (function_exists('drupal_add_js')) {
      drupal_add_js('misc/tableheader.js');
      return 'sticky-enabled';
    }
    return '';
  }

  /**
   * For each subsample found in the entity to load, output a block of hidden inputs which contain the required
   * values for the subsample.
   */
  public static function get_subsample_per_row_hidden_inputs() {
    $blocks = "";
    if (isset(data_entry_helper::$entity_to_load)) {
      foreach(data_entry_helper::$entity_to_load as $key => $value){
        $a = explode(':', $key, 4);
        if(count($a)==4 && $a[0] == 'sc' && $a[3] == 'sample:entered_sref'){
          $geomKey = $a[0].':'.$a[1].':'.$a[2].':sample:geom';
          $idKey = $a[0].':'.$a[1].':'.$a[2].':sample:id';
          $deletedKey = $a[0].':'.$a[1].':'.$a[2].':sample:deleted';
          $blocks .= '<div id="scm-'.$a[1].'-block" class="scm-block">'.
            '<input type="hidden" value="'.$value.'"  name="'.$key.'">'.
            '<input type="hidden" value="'.data_entry_helper::$entity_to_load[$geomKey].'" name="'.$geomKey.'">'.
            '<input type="hidden" value="'.(isset(data_entry_helper::$entity_to_load[$deletedKey]) ? data_entry_helper::$entity_to_load[$deletedKey] : 'f').'" name="'.$deletedKey.'">'.
            (isset(data_entry_helper::$entity_to_load[$idKey]) ? '<input type="hidden" value="'.data_entry_helper::$entity_to_load[$idKey].'" name="'.$idKey.'">' : '');
          $blocks .= '</div>';
        }
      }
    }
    return $blocks;
  }

  /**
   * Implode the rows we are putting into the species checklist, with application of classes to image rows.
   */
  public static function species_checklist_implode_rows($rows, $imageRowIdxs) {
    $r = '';
    foreach ($rows as $idx => $row) {
      $class = in_array($idx, $imageRowIdxs) ? ' class="supplementary-row"' : '';
      $r .= "<tr$class>$row</tr>\n";
    }
    return $r;
  }

  /**
   * Private function to retrieve the subspecies selection cell for a species_checklist,
   * when the subspeciesColumn option is enabled.
   * @param array Taxon definition as loaded from the database.
   * @param integer Index of the taxon row we are operating on.
   * @param integer If an existing record, then the record's occurrence ID.
   * @param array Options array for the species grid. Used to obtain the row inclusion check mode,
   * read authorisation and lookup list's ID.
   */
  private static function speciesChecklistGetSubspCell($taxon, $txIdx, $existingRecordId, $options) {
    if ($options['subSpeciesColumn']) {
      //Disable the sub-species drop-down if the row delete button is not displayed.
      //Also disable if we are preloading our data from a sample.
      $isDisabled=($options['rowInclusionCheck']!='alwaysRemovable' || (!empty($existingRecordId) && !empty($taxon))) ?
        'disabled="disabled"' : '';
      //if the taxon has a parent then we need to setup both a child and parent
      if (!empty($taxon['parent_id'])) {
        $selectedChildId=$taxon['id'];
        $selectedParentId=$taxon['parent']['id'];
        $selectedParentName=$taxon['parent']['taxon'];
      } else {
        //If the taxon doesn't have a parent, then the taxon is considered to be the parent we set the to be no child selected by default.
        //Children might still be present, we just aren't selecting one by default.
        $selectedChildId=0;
        $selectedParentId=$taxon['preferred_taxa_taxon_list_id'];
        $selectedParentName=$taxon['preferred_taxon'];
      }
      self::$javascript .= "createSubSpeciesList(
        '".parent::$base_url."index.php/services/data"."'
        , $selectedParentId
        , '$selectedParentName'
        , '".$options['lookupListId']."'
        , 'sc:$txIdx:$existingRecordId::occurrence:subspecies'
        , {'auth_token' : '".$options['readAuth']['auth_token']."', 'nonce' : '".$options['readAuth']['nonce']."'}
        , $selectedChildId
      );\n";
      return '<td class="ui-widget-content scSubSpeciesCell"><select class="scSubSpecies" ' .
      "id=\"sc:$txIdx:$existingRecordId::occurrence:subspecies\" name=\"sc:$txIdx:$existingRecordId::occurrence:subspecies\" ".
      "$isDisabled onchange=\"SetHtmlIdsOnSubspeciesChange(this.id);\">" .
      '</select></td>';
    }
    // default - no cell returned
    return '';
  }

  /**
   * If using a subspecies column then the list of taxa we have loaded will have a parent species
   * that must be displayed in the grid. So load them up...
   * @param array $taxalist List of taxon definitions we are loading parents for.
   * @param array $options Options array as passed to the species grid. Provides the read authorisation
   * tokens.
   */
  private static function load_parent_species(&$taxalist, $options) {
    // get a list of the species parent IDs
    $ids = array();
    foreach($taxalist as $taxon) {
      if (!empty($taxon['parent_id']))
        $ids[]=$taxon['parent_id'];
    }
    if (!empty($ids)) {
      // load each parent from the db in one go
      $loadOpts = array(
        'table'=>'cache_taxa_taxon_list',
        'extraParams'=>$options['readAuth'] + array('id'=>$ids),
      );
      $parents=data_entry_helper::get_population_data($loadOpts);
      // assign the parents back into the relevent places in $taxalist. Not sure if there is a better
      // way than a double loop?
      foreach($parents as $parent) {
        foreach($taxalist as &$taxon) {
          if ($taxon['parent_id']===$parent['id'])
            $taxon['parent']=$parent;
        }
      }
    }
  }

  /**
   * Builds an array to filter for the appropriate selection of species names, e.g. how it accepts searches for
   * common names and synonyms.
   * @param array $options Options array as passed to the species grid.
   */
  public static function get_species_names_filter(&$options) {
    // $wheres is an array for building of the filter query
    $wheres = array();
    $r = array();
    // If we are showing sub-species in a seperate column the then main species column should not include any sub-species.
    // If we had a rank field for each taxon, then this would be replaced by a rank=species filter.
    if (isset($options['subSpeciesColumn']) && $options['subSpeciesColumn'])
      $wheres[] = "(parent_id is null)";
    if (isset($options['cacheLookup']) && $options['cacheLookup'])
      $wheres[] = "(simplified='t' or simplified is null)";
    self::parse_species_name_filter_mode($options, $r, $wheres);
    if (isset($options['extraParams']))
      foreach ($options['extraParams'] as $key=>$value) {
        if ($key!=='nonce' && $key!=='auth_token')
          $r[$key] = $value;
      }
    $query=array();
    if (isset($r['query'])) {
      $query = json_decode($r['query'], true);
      unset($r['query']);
    }
    if (!empty($options['taxonFilterField']) && $options['taxonFilterField']!=='none' && !empty($options['taxonFilter'])) {
      // filter the taxa available to record
      // switch field to filter by if using cached lookup
      if ($options['cacheLookup'] && $options['taxonFilterField']==='preferred_name')
        $options['taxonFilterField']='preferred_taxon';
      $query = array('in'=>array($options['taxonFilterField'], $options['taxonFilter']));
    }
    if (!empty($wheres))
      $query['where']=array(implode(' AND ', $wheres));
    if (!empty($query))
      $r['query']=json_encode($query);
    return $r;
  }

  /**
   * Private utility function to extract the fields which need filtering against, plus any complex
   * SQL where clauses, required to do a species name filter according to the current mode (e.g.
   * preferred names only, all names etc).
   * @param array $options Species_checklist options array.
   * @param array $filterFields Pass an array in - this will be populated with the keys and values
   * of any fields than need to be filtered.
   * @param array $filterWheres Pass an array in - this will be populated with a list of any complex
   * where clauses (to put into a service request's query parameter).
   */
  private static function parse_species_name_filter_mode($options, &$filterFields, &$filterWheres) {
    if (isset($options['speciesNameFilterMode'])) {
      $colLanguage = $options['cacheLookup'] || (!empty($options['extraParams']['view']) && $options['extraParams']['view']==='detail')
          ? 'language_iso' : 'language';
      switch($options['speciesNameFilterMode']) {
        case 'preferred' :
          $filterFields += array('preferred'=>'t');
          break;
        case 'currentLanguage' :
          // look for Drupal user variable. Will degrade gracefully if it doesn't exist
          global $user;
          if (isset($options['language'])) {
            $filterFields += array($colLanguage => $options['language']);
          } elseif (isset($user) && function_exists('hostsite_get_user_field')) {
            // if in Drupal we can use the user's language
            require_once('prebuilt_forms/includes/language_utils.php');
            $filterFields += array($colLanguage => iform_lang_iso_639_2(hostsite_get_user_field('language')));
          }
          break;
        case 'excludeSynonyms':
          $filterWheres[] = "(preferred='t' or $colLanguage<>'lat')";
          break;
      }
    }
  }

  /**
   * Adds javascript to popup a config box for the current filter on the species you can add to the grid.
   * @param array $options Options array as passed to the species checklist grid.
   * @param array $nameFilter array of optional name filtering modes, with the actual filter to apply
   * as the value.
   */
  public static function species_checklist_filter_popup($options, $nameFilter) {
    self::add_resource('fancybox');
    $db=self::get_species_lookup_db_definition(isset($options['cacheLookup']) && $options['cacheLookup']);
    extract($db);
    $defaultFilterMode = (isset($options['speciesNameFilterMode'])) ? $options['speciesNameFilterMode'] : 'all';
    self::$javascript .= "var mode,  nameFilter=[];\n";
    //convert the nameFilter php array into a Javascript one
    foreach ($nameFilter as $key=>$theFilter) {
      self::$javascript .= "nameFilter['".$key."'] = ".$theFilter.";\n";
    }
    if ($options['userControlsTaxonFilter'] && !empty($options['lookupListId'])) {
      if ($options['taxonFilterField']==='none') {
        $defaultOptionLabel=lang::get('Input any species from the list available for this form');
      } else {
        $type = $options['taxonFilterField'] == 'taxon_group' ? 'species groups' : 'species';
        $defaultOptionLabel=lang::get('Input species from the form\\\'s default {1}.', lang::get($type));
      }
      self::$javascript .= "
var applyFilterMode = function(type, group_id, nameFilterMode) {
  var currentFilter;
  //get the filter we are going to use. Use a) the provided parameter, when loading from a cookie,
  // b) the default name filter, when not in cookie and loading for first time, or c) the one selected on the form.
  if (typeof nameFilterMode==='undefined') {
    nameFilterMode = $('#filter-name').length===0 ? '$defaultFilterMode' : $('#filter-name').val();
  }
  currentFilter=$.extend({}, nameFilter[nameFilterMode]);
  // decode the query part, so we can modify it
  if (typeof currentFilter.query!==\"undefined\") {
    currentFilter.query=JSON.parse(currentFilter.query);
  } else {
    currentFilter.query={};
  }
  //Extend the current query with any taxon group selections the user has made
  switch (type) {\n";
      if (!empty($options['usersPreferredGroups']))
        self::$javascript .= "    case 'user':
        currentFilter.query['in']={\"taxon_group_id\":[".implode(',', $options['usersPreferredGroups'])."]};
        break;\n";
      self::$javascript .= "    case 'selected':
      currentFilter.query['in']={\"taxon_group_id\":[group_id]};
  }
  // re-encode the query part
  currentFilter.query=JSON.stringify(currentFilter.query);
  if (type==='default') {
    $('#".$options['id']." .species-filter').removeClass('button-active');
  } else {
    $('#".$options['id']." .species-filter').addClass('button-active');
  }

  //Tell the system to use the current filter.
  indiciaData['taxonExtraParams-".$options['id']."']=currentFilter;
  $('.scTaxonCell input').setExtraParams(currentFilter);
  // Unset previous filters which are no longer wanted
  switch (nameFilterMode) {
    case 'preferred':
      $('.scTaxonCell input').unsetExtraParams(\"$colLanguage\");
      break;
    case 'all':
      $('.scTaxonCell input').unsetExtraParams(\"$colLanguage\");
    case 'currentLanguage':
    default:
      $('.scTaxonCell input').unsetExtraParams(\"name_type\");
  }
  // store in cookie
  $.cookie('user_selected_taxon_filter', JSON.stringify({\"type\":type,\"group_id\":group_id,\"name_filter\":nameFilterMode}));
};
// load the filter mode from a cookie
var userFilter=$.cookie('user_selected_taxon_filter');
if (userFilter) {
  userFilter = JSON.parse(userFilter);
  applyFilterMode(userFilter.type, userFilter.group_id, userFilter.name_filter);
}\n";
      self::$javascript .= "
$('#".$options['id']." .species-filter').click(function(evt) {
  var userFilter=$.cookie('user_selected_taxon_filter'), defaultChecked='', userChecked='', selectedChecked='', nameChecked='';

  //Select the radio button on the form depending on what is set in the cookie
  if (userFilter) {
    userFilter = JSON.parse(userFilter);
    if (userFilter.type==='user') {
      userChecked = ' checked=\"checked\"';
    } else if (userFilter.type==='selected') {
      selectedChecked = ' checked=\"checked\"';
    } else {
      defaultChecked = ' checked=\"checked\"';
    }
  } else {
    defaultChecked = ' checked=\"checked\"';
  }
  $.fancybox('<div id=\"filter-form\"><fieldset class=\"popup-form\">' +
    '<legend>".lang::get('Configure the filter applied to species names you are searching for').":</legend>' +
    '<label class=\"auto\"><input type=\"radio\" name=\"filter-mode\" id=\"filter-mode-default\"'+defaultChecked+'/>$defaultOptionLabel</label>' + \n";
      if (!empty($options['usersPreferredGroups'])) {
        self::$javascript .= "        '<label class=\"auto\"><input type=\"radio\" name=\"filter-mode\" id=\"filter-mode-user\"'+userChecked+'/>".
          lang::get('Input species from the preferred list of species groups from your user account.')."</label>' + \n";
      }
      self::$javascript .= "        '<label class=\"auto\"><input type=\"radio\" name=\"filter-mode\" id=\"filter-mode-selected\"'+selectedChecked+'/>".
        lang::get('Input species from the following species group:')."</label>' +
      '<select name=\"filter-group\" id=\"filter-group\"></select>' +
      '<label class=\"auto\" for=\"filter-name\">".
        lang::get('Choose species names available for selection:')."</label>' +
      '<select name=\"filter-name\" id=\"filter-name\">' +
         '<option id=\"filter-all\" value=\"all\">".lang::get('All names including common names and synonyms')."</option>' +
         '<option id=\"filter-common\" value=\"currentLanguage\">".lang::get('Common names only')."</option>' +
         '<option id=\"filter-common-preferred\" value=\"excludeSynonyms\">".lang::get('Common names and preferred latin names only')."</option>' +
         '<option id=\"filter-preferred\" value=\"preferred\">".lang::get('Preferred latin names only')."</option>' +
      '</select>' +
      '</fieldset><button type=\"button\" class=\"default-button\" id=\"filter-popup-apply\">".lang::get('Apply')."</button><button type=\"button\" class=\"default-button\" id=\"filter-popup-cancel\">".lang::get('Cancel')."</button></div>');
    $.getJSON(\"".self::$base_url."index.php/services/report/requestReport?report=library/taxon_groups/taxon_groups_used_in_checklist.xml&reportSource=local&mode=json".
        "&taxon_list_id=".$options['lookupListId']."&auth_token=".$options['readAuth']['auth_token']."&nonce=".$options['readAuth']['nonce']."&callback=?\", function(data) {
    var checked;
    $.each(data, function(idx, item) {
      selected = userFilter!==null && (item.id===userFilter.group_id) ? ' selected=\"selected\"' : '';
      $('#filter-group').append('<option value=\"'+item.id+'\"' + selected + '>'+item.title+'</option>');
    });
  });
  //By defult assume that the filter mode is the default one
  var filterMode = '$defaultFilterMode';
  //if the cookie is present and it holds one of the name type filters
  //it  means the last time the user used the screen they selected
  //to filter for a particular name type, so auto-select those previous
  //settings when the user opens the popup (overriding the defaultFilterMode)
  if(userFilter) {
    if (nameFilter.indexOf(userFilter.name_filter)) {
      filterMode = userFilter.name_filter;
      $('#filter-mode-name').attr('selected','selected');
    }
  }
  if (filterMode=='all')
    $('#filter-all').attr('selected','selected');
  if (filterMode=='currentLanguage')
    $('#filter-common').attr('selected','selected');
  if (filterMode=='preferred')
    $('#filter-preferred').attr('selected','selected');
  if (filterMode=='excludeSynonyms')
    $('#filter-common-preferred').attr('selected','selected');
  $('#filter-group').focus(function() {
    $('#filter-mode-selected').attr('checked','checked');
  });

  $('#filter-popup-apply').click(function() {
    if ($('#filter-mode-default:checked').length>0) {
      applyFilterMode('default');
    } else if ($('#filter-mode-selected:checked').length>0) {
      applyFilterMode('selected', $('#filter-group').val());
    }
    ";
      if (!empty($options['usersPreferredGroups']))
        self::$javascript .= " else if ($('#filter-mode-user').is(':checked')) {
      applyFilterMode('user');
    }";
      self::$javascript .= "\n    $.fancybox.close();
  });
  $('#filter-popup-cancel').click(function() {
    $.fancybox.close();
  });
});\n";
    }
  }

  /**
   * Normally, the species checklist will handle loading the list of occurrences from the database automatically.
   * However, when a form needs access to occurrence data before loading the species checklist, this method
   * can be called to preload the data. The data is loaded into data_entry_helper::$entity_to_load and an array
   * of occurrences loaded is returned.
   * @param int $sampleId ID of the sample to load
   * @param array $readAuth Read authorisation array
   * @param boolean $loadMedia Array of media type terms to load.
   * @param array $extraParams Extra params to pass to the web service call for filtering.
   * @param boolean $useSubSamples Enable loading of records from subsamples of the main sample
   * @param boolean $subSamplesOptional If using subsamples but they are optional, records are also loaded that are
   * directly attached to the main sample.
   * @return array Array with key of occurrence_id and value of $taxonInstance.
   */
  public static function preload_species_checklist_occurrences($sampleId, $readAuth, $loadMedia, $extraParams,
       &$subSamples, $useSubSamples, $subSampleMethodID='', $subSamplesOptional=false) {
    $occurrenceIds = array();
    // don't load from the db if there are validation errors, since the $_POST will already contain all the
    // data we need.
    if (is_null(self::$validation_errors)) {
      // strip out any occurrences we've already loaded into the entity_to_load, in case there are other
      // checklist grids on the same page. Otherwise we'd double up the record data.
      foreach(data_entry_helper::$entity_to_load as $key => $value) {
        $parts = explode(':', $key);
        if (count($parts) > 2 && $parts[0] == 'sc' && $parts[1]!='-idx-') {
          unset(data_entry_helper::$entity_to_load[$key]);
        }
      }
      if($useSubSamples) {
        $extraParams += $readAuth + array('view'=>'detail','parent_id'=>$sampleId,'deleted'=>'f', 'orderby'=>'id', 'sortdir'=>'ASC' );
        if($subSampleMethodID != '')
          $extraParams['sample_method_id'] = $subSampleMethodID;
        $subSamples = data_entry_helper::get_population_data(array(
          'table' => 'sample',
          'extraParams' => $extraParams,
          'nocache' => true
        ));
        $subSampleList = array();
        if ($subSamplesOptional)
          $subSampleList[] = $sampleId;
        foreach($subSamples as $idx => $subsample){
          $subSampleList[] = $subsample['id'];
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$subsample['id'].':sample:id'] = $subsample['id'];
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$subsample['id'].':sample:geom'] = $subsample['wkt'];
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$subsample['id'].':sample:wkt'] = $subsample['wkt'];
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$subsample['id'].':sample:location_id'] = $subsample['location_id'];
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$subsample['id'].':sample:entered_sref'] = $subsample['entered_sref'];
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$subsample['id'].':sample:entered_sref_system'] = $subsample['entered_sref_system'];
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$subsample['id'].':sample:date_start'] = $subsample['date_start'];
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$subsample['id'].':sample:date_end'] = $subsample['date_end'];
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$subsample['id'].':sample:date_type'] = $subsample['date_type'];
          data_entry_helper::$entity_to_load['sc:'.$idx.':'.$subsample['id'].':sample:sample_method_id'] = $subsample['sample_method_id'];
        }
        unset($extraParams['parent_id']);
        unset($extraParams['sample_method_id']);
        $extraParams['sample_id']=$subSampleList;
        $sampleCount = count($subSampleList);
      } else {
        $extraParams += $readAuth + array('view'=>'detail','sample_id'=>$sampleId,'deleted'=>'f', 'orderby'=>'id', 'sortdir'=>'ASC' );
        $sampleCount = 1;
      }
      if($sampleCount>0) {
        $occurrences = self::get_population_data(array(
          'table' => 'occurrence',
          'extraParams' => $extraParams,
          'nocache' => true
        ));
        foreach($occurrences as $idx => $occurrence){
          if($useSubSamples){
            foreach($subSamples as $sidx => $subsample){
              if($subsample['id'] == $occurrence['sample_id'])
                self::$entity_to_load['sc:'.$idx.':'.$occurrence['id'].':occurrence:sampleIDX'] = $sidx;
            }
          }
          self::$entity_to_load['sc:'.$idx.':'.$occurrence['id'].':present'] = $occurrence['taxa_taxon_list_id'];
          self::$entity_to_load['sc:'.$idx.':'.$occurrence['id'].':record_status'] = $occurrence['record_status'];
          self::$entity_to_load['sc:'.$idx.':'.$occurrence['id'].':occurrence:comment'] = $occurrence['comment'];
          self::$entity_to_load['sc:'.$idx.':'.$occurrence['id'].':occurrence:sensitivity_precision'] = $occurrence['sensitivity_precision'];
          // Warning. I observe that, in cases where more than one occurrence is loaded, the following entries in
          // $entity_to_load will just take the value of the last loaded occurrence.
          self::$entity_to_load['occurrence:record_status']=$occurrence['record_status'];
          self::$entity_to_load['occurrence:taxa_taxon_list_id']=$occurrence['taxa_taxon_list_id'];
          self::$entity_to_load['occurrence:taxa_taxon_list_id:taxon']=$occurrence['taxon'];
          // Keep a list of all Ids
          $occurrenceIds[$occurrence['id']] = $idx;
        }
        if(count($occurrenceIds)>0) {
          // load the attribute values into the entity to load as well
          $attrValues = self::get_population_data(array(
            'table' => 'occurrence_attribute_value',
            'extraParams' => $readAuth + array('occurrence_id' => array_keys($occurrenceIds)),
            'nocache' => true
          ));
          foreach($attrValues as $attrValue) {
            // vague date controls need the processed vague date put back in, not the raw parts.
            $valueField = $attrValue['data_type']==='Vague Date' ? 'value' : 'raw_value';
            self::$entity_to_load['sc:'.$occurrenceIds[$attrValue['occurrence_id']].':'.$attrValue['occurrence_id'].':occAttr:'.$attrValue['occurrence_attribute_id'].(isset($attrValue['id'])?':'.$attrValue['id']:'')]
              = $attrValue[$valueField];
          }
          if (count($loadMedia)>0) {
            // @todo: Filter to the appropriate list of media types
            $media = self::get_population_data(array(
              'table' => 'occurrence_medium',
              'extraParams' => $readAuth + array('occurrence_id' => array_keys($occurrenceIds)),
              'nocache' => true
            ));
            foreach($media as $medium) {
              self::$entity_to_load['sc:'.$occurrenceIds[$medium['occurrence_id']].':'.$medium['occurrence_id'].':occurrence_medium:id:'.$medium['id']]
                = $medium['id'];
              self::$entity_to_load['sc:'.$occurrenceIds[$medium['occurrence_id']].':'.$medium['occurrence_id'].':occurrence_medium:path:'.$medium['id']]
                = $medium['path'];
              self::$entity_to_load['sc:'.$occurrenceIds[$medium['occurrence_id']].':'.$medium['occurrence_id'].':occurrence_medium:caption:'.$medium['id']]
                = $medium['caption'];
              self::$entity_to_load['sc:'.$occurrenceIds[$medium['occurrence_id']].':'.$medium['occurrence_id'].':occurrence_medium:media_type_id:'.$medium['id']]
                = $medium['media_type_id'];
              self::$entity_to_load['sc:'.$occurrenceIds[$medium['occurrence_id']].':'.$medium['occurrence_id'].':occurrence_medium:media_type:'.$medium['id']]
                = $medium['media_type'];
            }
          }
        }
      }
    }
    return $occurrenceIds;
  }

  /**
   * Retrieve the grid header row for the species checklist grid control.
   * @param array $options Control options array.
   * @param array $occAttrs Array of custom attributes included in the grid.
   * @param bool $onlyImages True if only image media types are available to upload
   * @return string Html for the <thead> element.
   */
  public static function get_species_checklist_header($options, $occAttrs, $onlyImages) {
    $r = '';
    $visibleColIdx = 0;
    if ($options['header']) {
      $r .= '<thead class="ui-widget-header"><tr>';
      for ($i = 0; $i < $options['columns']; $i++) {
        // The colspan trick of having buttons under the species column heading
        // messes up FooTables so give the buttons their own header.
        if ($options['responsive']) {
          if (!empty($options['lookupListId']) || $options['rowInclusionCheck']=='alwaysRemovable') {
            $r .= '<th class="row-buttons"></th>';
          }
          $colspan = '';
        }
        else {
          $colspan = !empty($options['lookupListId']) || $options['rowInclusionCheck']=='alwaysRemovable' ? ' colspan="2"' : '';
        }

        // Species column - no option to hide in repsonsive mode.
        $speciesColTitle = empty($options['speciesColTitle']) ? lang::get('species_checklist.species') : lang::get($options['speciesColTitle']);
        if ($options['userControlsTaxonFilter'] && !empty($options['lookupListId'])) {
          global $indicia_templates;
          $imgPath = empty(self::$images_path) ? self::relative_client_helper_path()."../media/images/" : self::$images_path;
          $speciesColTitle .= '<button type="button" class="species-filter" class="default-button"><img src="'.
            $imgPath.'/filter.png" alt="'.lang::get('Filter').'" style="vertical-align: middle" title="'.
            lang::get('Filter the list of species you can search').'" width="16" height="16"/></button>';
        }
        $r .= self::get_species_checklist_col_header($options['id']."-species-$i", $speciesColTitle, $visibleColIdx, $options['colWidths'], $colspan);
        if ($options['subSpeciesColumn'])
          $r .= self::get_species_checklist_col_header($options['id']."-subspecies-$i", lang::get('Subspecies'), $visibleColIdx, $options['colWidths']);

        // Presence column - always hide unless rowInclusionCheck is 'checkbox'.
        // Ignored by responsive mode as it has to remain on principal row for
        // deletion code to work.
        $attrs = '';
        if ($options['rowInclusionCheck'] != 'checkbox') {
          $attrs = ' style="display:none"';
          if ($options['responsive']) {
            $attrs .= ' data-hide="all" data-ignore="true" data-editable="true"';
          }
        }
        $r .= self::get_species_checklist_col_header($options['id']."-present-$i", lang::get('species_checklist.present'),
          $visibleColIdx, $options['colWidths'], $attrs);

        // All attributes - may be hidden in responsive mode, depending upon
        // the settings in the responsiveCols array.
        foreach ($occAttrs as $idx=>$a) {
          $attrs = self::get_species_checklist_col_responsive($options, "attr$idx");
          $r .= self::get_species_checklist_col_header(
            $options['id']."-attr$idx-$i", lang::get($a), $visibleColIdx, $options['colWidths'], $attrs);
        }
        if ($options['spatialRefPerRow']) {
          $attrs = self::get_species_checklist_col_responsive($options, 'spatialref');
          $r .= self::get_species_checklist_col_header(
            $options['id']."-spatialref-$i", lang::get('Spatial ref'), $visibleColIdx, $options['colWidths'], $attrs);
        }
        if ($options['occurrenceComment']) {
          $attrs = self::get_species_checklist_col_responsive($options, 'comment');
          $r .= self::get_species_checklist_col_header(
            $options['id']."-comment-$i", lang::get('Comment'), $visibleColIdx, $options['colWidths'], $attrs);
        }
        if ($options['occurrenceSensitivity']) {
          $attrs = self::get_species_checklist_col_responsive($options, 'sensitivity');
          $r .= self::get_species_checklist_col_header(
            $options['id']."-sensitivity-$i", lang::get('Sensitivity'), $visibleColIdx, $options['colWidths'], $attrs);
        }

        // Non-responsive behaviour is to show an Add Media button in a column
        // which, when clicked, adds a row to the grid for files and hides the
        // button. Column can be hidden in responsive mode.
        if (count($options['mediaTypes'])) {
          $attrs = self::get_species_checklist_col_responsive($options, 'media');
          $r .= self::get_species_checklist_col_header($options['id']."-images-$i", lang::get($onlyImages ? 'Add photos' : 'Add media'), $visibleColIdx, $options['colWidths'], $attrs);
          // In responsive mode, add an additional column for files which is
          // always hidden so it appears in a row below.
          if ($options['responsive']) {
            $attrs = ' data-hide="all" data-editable="true"';
            $r .= self::get_species_checklist_col_header($options['id']."-files-$i", lang::get($onlyImages ? 'Photos' : 'Media'), $visibleColIdx, $options['colWidths'], $attrs);
          }
        }

        // Additional column for toggle button in responsive mode which cannot
        // be hidden.
        if ($options['responsive']) {
          $r .= '<th class="footable-toggle-col" data-toggle="true"></th>';
        }
      }
      $r .= '</tr></thead>';
      return $r;
    }
  }

  /**
   * Returns a th element for the top of a species checklist grid column. Skips any columns which are display: none.
   * @param string $id The HTML ID to use.
   * @param string $caption The caption to insert.
   * @param integer $colIdx The index of the current column. Incremented only if the header is output (so
   * hidden columns are excluded).
   * @param array $colWidths List of column percentage widths.
   * @param string $attrs CSS attributes to attach.
   */
  private static function get_species_checklist_col_header($id, $caption, &$colIdx, $colWidths, $attrs='') {
    $width = count($colWidths)>$colIdx && $colWidths[$colIdx] ? ' style="width: '.$colWidths[$colIdx].'%;"' : '';
    if (!strpos($attrs, 'display:none')) $colIdx++;
    return "<th id=\"$id\"$attrs$width>$caption</th>";
  }

  /**
   * Returns attributes to define responsive behaviour of column.
   * @param array $options Control options array.
   * @param string $column The column identifier which is the key to the
   * $options['responsiveHide'] array.
   * @return string CSS attributes to attach to column header.
   */
  private static function get_species_checklist_col_responsive($options, $column) {
    // Create a data-hide attribute for responsive tables.
    $attrs = '';
    if (isset($options['responsiveCols'][$column])) {
      $attrs = implode(',', array_keys(array_filter($options['responsiveCols'][$column])));
      if($attrs != '') {
        $attrs = " data-hide=\"$attrs\" data-editable=\"true\"";
      }
    }
    return $attrs;
  }

  /**
   * Method to build the list of taxa to add to a species checklist grid.
   * @param array $options Options array for the control
   * @param array $taxonRows Array that is modified by this method to contain a list of
   * the rows to load onto the grid. Each row contains a sub-array with ttlId entry plus
   * occId if the row represents an existing record
   * @return array The taxon list to use in the grid.
   */
  public static function get_species_checklist_taxa_list($options, &$taxonRows) {
    // Get the list of species that are always added to the grid, by first building a filter
    if (preg_match('/^(preferred_name|preferred_taxon|taxon_meaning_id|taxa_taxon_list_id|taxon_group|external_key|id)$/', $options['taxonFilterField']))  {
      if ($options['table']==='cache_taxa_taxon_list' && $options['taxonFilterField']==='taxa_taxon_list_id')
        $options['taxonFilterField']='id';
      $qry = array('in'=>array($options['taxonFilterField'], $options['taxonFilter']));
      $options['extraParams']['query']=json_encode($qry);
    }
    // load the species names that should be initially included in the grid
    if (isset($options['listId']) && !empty($options['listId'])) {
      // Apply the species list to the filter
      $options['extraParams']['taxon_list_id']=$options['listId'];
      // list in taxonomic sort order if order not already specified
      if( !isset($options['extraParams']['orderby']) )
        $options['extraParams']['orderby'] = 'taxonomic_sort_order';
      $taxalist = self::get_population_data($options);
    } else {
      $taxalist = array();
    }
    if ($options['taxonFilterField'] == 'id') { // when using an id, sort by order provided.
      foreach ($options['taxonFilter'] as $taxonFilter) {
        foreach ($taxalist as $taxon) {
          // create a list of the rows we are going to add to the grid, with the preloaded species names linked to them
          if($taxonFilter == $taxon['id'])
            $taxonRows[] = array('ttlId'=>$taxon['id']);
        }
      }
    } else {
      foreach ($taxalist as $taxon) {
        // create a list of the rows we are going to add to the grid, with the preloaded species names linked to them
        $taxonRows[] = array('ttlId'=>$taxon['id']);
      }
    }
    // If there are any existing records to add to the list from the lookup list/add rows feature, get their details
    if (self::$entity_to_load) {
      // copy the options array so we can modify it
      $extraTaxonOptions = array_merge(array(), $options);
      // force load in order as input
      unset($extraTaxonOptions['extraParams']['orderby']);
      // We don't want to filter the taxa to be added to a specific list, because if they are in the sample,
      // then they must be included whatever.
      unset($extraTaxonOptions['extraParams']['taxon_list_id']);
      unset($extraTaxonOptions['extraParams']['preferred']);
      unset($extraTaxonOptions['extraParams']['language_iso']);
      unset($extraTaxonOptions['extraParams']['query']);
      // create an array to hold the IDs, so that get_population_data can construct a single IN query, faster
      // than multiple requests. We'll populate it in a moment
      $extraTaxonOptions['extraParams']['id'] = array();
      // look through the data being loaded for the ttlIds associated with existing occurrences
      foreach(self::$entity_to_load as $key => $value) {
        $parts = explode(':', $key);
        // Is this an occurrence?
        $present = count($parts) > 2 && $parts[0] === 'sc' && $parts[1]!='-idx-' && $parts[3]==='present';
        if ($present && !empty($options['gridIdAttributeId'])) {
          // filtering records by grid ID. Skip them if from a different input grid (multiple grids on one form scenario).
          // The suffix containing attr_value_id will not be present if reloading due to error on submission
          $matches = preg_grep("/^sc:$parts[1]:$parts[2]:occAttr:$options[gridIdAttributeId](:[0-9]+)?$/", array_keys(self::$entity_to_load));
          if (count($matches)>0) {
            $match = array_pop($matches);
            if (self::$entity_to_load[$match]!==$options['id']) {
              continue;
            }
          }
        }
        if ($present) {
          $ttlId = $value;
          if ($options['speciesControlToUseSubSamples'])
            $smpIdx = self::$entity_to_load['sc:'.$parts[1].':'.$parts[2].':occurrence:sampleIDX'];
          else
            $smpIdx = null;
          // Find an existing row for this species that is not already linked to an occurrence
          $done=false;
          foreach($taxonRows as &$row) {
            if ($row['ttlId']===$ttlId && !isset($row['occId'])) {
              // the 2nd part of the loaded value's key row index we loaded from.
              $row['loadedTxIdx']=$parts[1];
              // the 3rd part of the loaded value's key is the occurrence ID.
              $row['occId']=$parts[2];
              $row['smpIdx']=$smpIdx;
              $done=true;
            }
          }
          if (!$done)
            // add a new row to the bottom of the grid
            $taxonRows[] = array('ttlId'=>$ttlId, 'loadedTxIdx'=>$parts[1], 'occId'=>$parts[2], 'smpIdx'=>$smpIdx);
          // store the id of the taxon in the array, so we can load them all in one go later
          $extraTaxonOptions['extraParams']['id'][]=$ttlId;
        }
        // Ensure the load of taxa is batched if there are lots to load
        if (count($extraTaxonOptions['extraParams']['id'])>=50 && !empty($options['lookupListId'])) {
          $taxalist = array_merge($taxalist, self::get_population_data($extraTaxonOptions));
          $extraTaxonOptions['extraParams']['id'] = array();
        }
      }
      // Load and append the remaining additional taxa to our list of taxa to use in the grid
      if (count($extraTaxonOptions['extraParams']['id']) && !empty($options['lookupListId']))
        $taxalist = array_merge($taxalist, self::get_population_data($extraTaxonOptions));
    }
    return $taxalist;
  }

  /**
   * Internal method to prepare the options array for a species_checklist control.
   *
   * @param array $options Options array passed to the control
   * @return array Options array prepared with defaults and other values required by the control.
   */
  public static function get_species_checklist_options($options) {
    // validate some options
    if (empty($options['listId']) && empty($options['lookupListId']))
      throw new Exception('Either the listId or lookupListId parameters must be provided for a species checklist.');
    // CheckBoxCol support is for backwards compatibility
    if (isset($options['checkboxCol']) && $options['checkboxCol']==false) {
      $rowInclusionCheck='hasData';
    } else {
      if (empty($options['listId']) && !empty($options['lookupListId']))
        $rowInclusionCheck = 'alwaysRemovable';
      else
        $rowInclusionCheck = 'checkbox';
    }
    // Apply default values
    $options = array_merge(array(
      'userControlsTaxonFilter'=>false,
      'header'=>'true',
      'columns'=>1,
      'rowInclusionCheck'=>$rowInclusionCheck,
      'attrCellTemplate'=>'attribute_cell',
      'PHPtaxonLabel' => false,
      'occurrenceComment' => false,
      'occurrenceSensitivity' => null,
      'spatialRefPerRow' => false,
      'id' => 'species-grid-'.rand(0,1000),
      'colWidths' => array(),
      'taxonFilterField' => 'none',
      'cacheLookup' => false,
      'reloadExtraParams' => array(),
      'useLoadedExistingRecords' => false,
      'subSpeciesColumn' => false,
      'subSpeciesRemoveSspRank' => false,
      'speciesControlToUseSubSamples' => false,
      'subSamplePerRow' => false,
      'copyDataFromPreviousRow' => false,
      'previousRowColumnsToInclude' => '',
      'editTaxaNames' => false,
      'sticky' => true,
      'includeSpeciesGridLinkPage' => false,
      'speciesGridPageLinkUrl' => '',
      'speciesGridPageLinkParameter' => '',
      'speciesGridPageLinkTooltip' => '',
      // legacy - occurrenceImages means just local image support
      'mediaTypes' => !empty($options['occurrenceImages']) && $options['occurrenceImages'] ?
        array('Image:Local') : array(),
      'responsive' => false,
    ), $options);
    // subSamplesPerRow can't be set without speciesControlToUseSubSamples
    $options['subSamplePerRow'] = $options['subSamplePerRow'] && $options['speciesControlToUseSubSamples'];
    // subspecies columns require cached lookups to be enabled.
    $options['cacheLookup'] = $options['cacheLookup'] || $options['subSpeciesColumn'];
    if (array_key_exists('readAuth', $options)) {
      $options['extraParams'] += $options['readAuth'];
    } else {
      $options['readAuth'] = array(
        'auth_token' => $options['extraParams']['auth_token'],
        'nonce' => $options['extraParams']['nonce']
      );
    }
    $options['table'] = $options['cacheLookup'] ? 'cache_taxa_taxon_list' : 'taxa_taxon_list';
    // colWidths are disabled for responsive checklists
    if ($options['responsive']) {
      $options['colWidths'] = array();
    }
    return $options;
  }

  /**
   * Internal function to prepare the list of occurrence attribute columns for a species_checklist control.
   * @param array $options Options array as passed to the species checklist grid control.
   * @param array $attributes Array of custom attributes as loaded from the database.
   * @param array $occAttrControls Empty array which will be populated with the controls required for each
   * custom attribute. This copy of the control applies for new data and is populated with defaults.
   * @param array $occAttrControlsExisting Empty array which will be populated with the controls required for each
   * custom attribute. This copy of the control applies for existing data and is not populated with defaults.
   * @param array $occAttrCaptions Empty array which will be populated with the captions for each custom attribute.
   */
  public static function species_checklist_prepare_attributes($options, $attributes, &$occAttrControls, &$occAttrControlsExisting, &$occAttrCaptions) {
    $idx=0;
    if (array_key_exists('occAttrs', $options))
      $attrs = $options['occAttrs'];
    else
      // There is no specified list of occurrence attributes, so use all available for the survey
      $attrs = array_keys($attributes);
    foreach ($attrs as $occAttrId) {
      // don't display the grid ID attribute
      if (!empty($options['gridIdAttributeId']) && $occAttrId===$options['gridIdAttributeId'])
        continue;
      // test that this occurrence attribute is linked to the survey
      if (!isset($attributes[$occAttrId]))
        throw new Exception("The occurrence attribute $occAttrId requested for the grid is not linked with the survey.");
      $attrDef = array_merge($attributes[$occAttrId]);
      $attrOpts = array();
      if (isset($options['occAttrOptions'][$occAttrId])) {
        $attrOpts = array_merge($options['occAttrOptions'][$occAttrId]);
      }

      // Build array of attribute captions
      if (isset($attrOpts['label'])) {
        // override caption from warehouse with label from client
        $occAttrCaptions[$occAttrId] = $attrOpts['label'];
        // but prevent it being added in grid
        unset($attrOpts['label']);
      } else {
        $occAttrCaptions[$occAttrId] = $attrDef['caption'];
      }

      // Build array of attribute controls
      $class = self::speciesChecklistOccAttrClass($options, $idx, $attrDef['untranslatedCaption']);
      $class .= (isset($attrDef['class']) ? ' ' . $attrDef['class'] : '');
      if (isset($attrOpts['class'])) {
        $class .=  ' ' . $attrOpts['class'];
        unset($attrOpts['class']);
      }
      $ctrlOptions = array(
        'class' => $class,
        'controlWrapTemplate' => 'justControl',
        'extraParams' => $options['readAuth'],
        'language' => $options['language'] // required for lists eg radio boxes: kept separate from options extra params as that is used to indicate filtering of species list by language
      );
      // Some undocumented checklist options that are applied to all attributes
      if(isset($options['lookUpKey'])) $ctrlOptions['lookUpKey'] = $options['lookUpKey'];
      if(isset($options['blankText'])) $ctrlOptions['blankText'] = $options['blankText'];
      // Don't want captions in the grid
      unset($attrDef['caption']);
      $attrDef['fieldname'] = '{fieldname}';
      $attrDef['id'] = '{fieldname}';
      if (isset($attrOpts)) {
        // Add in any remaining options for this control
        $ctrlOptions = array_merge_recursive($ctrlOptions, $attrOpts);
      }
      $occAttrControls[$occAttrId] = self::outputAttribute($attrDef, $ctrlOptions);
      // The controls for creating rows for existing occurrences must not use defaults.
      unset($attrDef['default']);
      unset($ctrlOptions['default']);
      $occAttrControlsExisting[$occAttrId] = self::outputAttribute($attrDef, $ctrlOptions);
      $idx++;
    }
  }

  /**
   * Returns the class to apply to a control for an occurrence attribute, identified by an index.
   * @param array $options Options array which contains the occAttrClasses item, an array of classes
   * configured for each attribute control.
   * @param integer $idx Index of the custom attribute.
   * @param string $caption Caption of the attribute used to construct a suitable CSS class.
   */
  private static function speciesChecklistOccAttrClass($options, $idx, $caption) {
    return (array_key_exists('occAttrClasses', $options) && $idx < count($options['occAttrClasses'])) ?
      $options['occAttrClasses'][$idx] :
      'sc' . preg_replace('/[^a-zA-Z0-9]/', '', ucWords($caption)); // provide a default class based on the control caption
  }

  /**
   * When the species checklist grid has a lookup list associated with it, this is a
   * secondary checklist which you can pick species from to add to the grid. As this happens,
   * a hidden table is used to store a clonable row which provides the template for new rows
   * to be added to the grid.
   * @param array $options Options array passed to the species grid.
   * @param array $occAttrControls List of the occurrence attribute controls, keyed by attribute ID.
   * @param array $attributes List of attribute definitions loaded from the database.
   */
  public static function get_species_checklist_clonable_row($options, $occAttrControls, $attributes) {
    global $indicia_templates;
    // We use the headers attribute of each td to link it to the id attribute of each th, for accessibility
    // and also to facilitate keyboard navigation. The last digit of the th id is the index of the column
    // group in a multi-column grid, or zero if the grid's columns property is set to default of 1.
    // Because the clonable row always goes in the first col, this can be always left to 0.
    $r = '<table style="display: none"><tbody><tr class="scClonableRow" id="'.$options['id'].'-scClonableRow">';
    $colspan = !empty($options['lookupListId']) || $options['rowInclusionCheck']=='alwaysRemovable' ? ' colspan="2"' : '';
    $r .= str_replace(array('{colspan}','{tableId}','{idx}','{editClass}'), array($colspan, $options['id'], 0, ''), $indicia_templates['taxon_label_cell']);
    $fieldname = "sc:$options[id]--idx-:";
    if ($options['subSpeciesColumn']) {
      $r .= '<td class="ui-widget-content scSubSpeciesCell"><select class="scSubSpecies" style="display: none" ' .
        "id=\"$fieldname:occurrence:subspecies\" name=\"$fieldname:occurrence:subspecies\" onchange=\"SetHtmlIdsOnSubspeciesChange(this.id);\">";
      $r .= '</select><span class="species-checklist-select-species">'.lang::get('Select a species first').'</span></td>';
    }
    $hidden = ($options['rowInclusionCheck']=='checkbox' ? '' : ' style="display:none"');
    $r .= '<td class="scPresenceCell" headers="'.$options['id'].'-present-0"'.$hidden.'>';
    $r .= "<input type=\"checkbox\" class=\"scPresence\" name=\"$fieldname:present\" id=\"$fieldname:present\" value=\"\" />";
    // If we have a grid ID attribute, output a hidden
    if (!empty($options['gridIdAttributeId']))
      $r .= "<input type=\"hidden\" name=\"$fieldname:occAttr:$options[gridIdAttributeId]\" id=\"$fieldname:occAttr:$options[gridIdAttributeId]\" value=\"$options[id]\"/>";
    $r .= '</td>';
    if ($options['speciesControlToUseSubSamples'])
      $r .= '<td class="scSampleCell" style="display:none"><input type="hidden" class="scSample" name="'.
        $fieldname.':occurrence:sampleIDX" id="'.$fieldname.':occurrence:sampleIDX" value="" /></td>';
    $idx = 0;
    foreach ($occAttrControls as $attrId=>$oc) {
      $class = self::speciesChecklistOccAttrClass($options, $idx, $attributes[$attrId]['caption']);
      $r .= str_replace(array('{content}', '{class}', '{headers}'),
        array(str_replace('{fieldname}', "$fieldname:occAttr:$attrId", $oc), $class.'Cell', $options['id']."-attr$attrId-0"),
        $indicia_templates['attribute_cell']
      );
      $idx++;
    }
    if ($options['spatialRefPerRow']) {
      $r .= '<td class="ui-widget-content scSpatialRefCell" headers="'.$options['id'].'-spatialref-0">' .
        '<input class="scSpatialRef" type="text" ' .
        "id=\"$fieldname:occurrence:spatialref\" name=\"$fieldname:occurrence:spatialref\" value=\"\" /></td>";
    }
    if ($options['occurrenceComment']) {
      $r .= '<td class="ui-widget-content scCommentCell" headers="'.$options['id'].'-comment-0"><input class="scComment" type="text" ' .
        "id=\"$fieldname:occurrence:comment\" name=\"$fieldname:occurrence:comment\" value=\"\" /></td>";
    }
    if (isset($options['occurrenceSensitivity']))
      $r .= self::speciesChecklistSensitivityCell($options, 0, '-idx-', '');
    if ($options['mediaTypes']) {
      $onlyLocal = true;
      $onlyImages = true;
      foreach ($options['mediaTypes'] as $mediaType) {
        if (!preg_match('/:Local$/', $mediaType))
          $onlyLocal=false;
        if (!preg_match('/^Image:/', $mediaType))
          $onlyImages=false;
      }
      $label = $onlyImages ? 'Add images' : 'Add media';
      $class = 'sc' . $onlyImages ? 'Image' : 'Media' . 'Link';
      $r .= '<td class="ui-widget-content scAddMediaCell" headers="'.$options['id'].'-images-0">' .
          '<a href="" class="add-media-link button '.$class.'" style="display: none" id="add-media:'.$options['id'].'--idx-:">'.
          lang::get($label).'</a><span class="species-checklist-select-species">'.lang::get('Select a species first').'</span></td>';

      // Extra columnn for photos in responsive mode.
      if ($options['responsive']) {
        $ctrlId = 'container-sc:' . $options['id'] . '--idx-::occurrence_medium-' . mt_rand();
        $r .= '<td class="scMediaCell"><div class="scMedia" id="' . $ctrlId . '"></div></td>';
      }
    }

    // Extra column for responsive toggle.
    if ($options['responsive']) {
      $r .= '<td class="footable-toggle-cell"></td>';
    }

    $r .= "</tr></tbody></table>\n";
    $r .= self::speciesChecklistSrefPerRowExistingIds($options);
    return $r;
  }

  /**
   * Helper function to output an HTML textarea. This includes re-loading of existing values
   * and displaying of validation error messages.
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>textareat</b></br>
   * HTML template used to generate the textarea element.
   * </li>
   * </ul>
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * Required. The name of the database field this control is bound to, e.g. occurrence:image.</li>
   * <li><b>id</b><br/>
   * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
   * <li><b>default</b><br/>
   * Optional. The default value to assign to the control. This is overridden when reloading a
   * record with existing data for this control.</li>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control.</li>
   * <li><b>rows</b><br/>
   * Optional. HTML rows attribute. Defaults to 4.</li>
   * <li><b>cols</b><br/>
   * Optional. HTML cols attribute. Defaults to 80.</li>
   * </ul>
   *
   * @return string HTML to insert into the page for the textarea control.
   */
  public static function textarea($options) {
    $options = array_merge(array(
      'cols'=>'80',
      'rows'=>'4'
    ), self::check_options($options));
    return self::apply_template('textarea', $options);
  }

  /**
   * Helper function to output an HTML text input. This includes re-loading of existing values
   * and displaying of validation error messages.
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * Required. The name of the database field this control is bound to.</li>
   * <li><b>id</b><br/>
   * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
   * <li><b>default</b><br/>
   * Optional. The default value to assign to the control. This is overridden when reloading a
   * record with existing data for this control.</li>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control.</li>
   * <li><b>readonly</b><br/>
   * Optional. can be set to 'readonly="readonly"' to set this control as read only.</li>
   * </ul>
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>text_input</b></br>
   * HTML template used to generate the input element.
   * </li>
   * </ul>
   *
   * @return string HTML to insert into the page for the text input control.
   */
  public static function text_input($options) {
    $options = array_merge(array(
      'default'=>''
    ), self::check_options($options));
    return self::apply_template('text_input', $options);
  }

  /**
   * Helper function to output an HTML hidden text input. This includes re-loading of existing values.
   * Hidden fields should not have any validation.
   * No Labels allowed, no suffix.
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>hidden_text</b></br>
   * HTML template used to generate the hidden input element.
   * </li>
   * </ul>
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * Required. The name of the database field this control is bound to.</li>
   * <li><b>id</b><br/>
   * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
   * <li><b>default</b><br/>
   * Optional. The default value to assign to the control. This is overridden when reloading a
   * record with existing data for this control.</li>
   * </ul>
   *
   * @return string HTML to insert into the page for the hidden text control.
   */
  public static function hidden_text($options) {
    $options = array_merge(array(
      'default'=>'',
      'requiredsuffixTemplate'=>'suffix', // disables output of the required *
      'controlWrapTemplate' => 'justControl'
    ), self::check_options($options));
    unset($options['label']);
    return self::apply_template('hidden_text', $options);
  }

  /**
  * Helper function to output a set of controls for handling the sensitivity of a record. Includes
  * a checkbox plus a control for setting the amount to blur the record by for public viewing.
  * The output of this control can be configured using the following templates:
  * <ul>
  * <li><b>hidden_text</b></br>
  * HTML template used to generate the hidden input element.
  * </li>
  * </ul>
  *
  * @param array $options Options array with the following possibilities:<ul>
  * <li><b>fieldname</b><br/>
  * Required. The name of the database field this control is bound to. Defaults to occurrence:sensitivity_precision.</li>
  * <li><b>defaultBlur</b><br/>
  * Optional. The initial value to blur a record by when assigning sensitivity. Defaults to 10000.</li>
  * <li><b>additionalControls</b><br/>
  * Optional. Any additional controls to include in the div which is disabled when a record is not sensitive. An example use of this
  * might be a Reason for sensitivity custom attribute. Provide the controls as an HTML string.</li>
  * <li><b>precisions</b><br/>
  * Array of precisions that are available to pick from. Defaults to [100, 1000, 2000, 1000, 10000].</li>
  * </ul>
  *
  * @return string HTML to insert into the page for the hidden text control.
  */
  public static function sensitivity_input($options) {
    $options = array_merge(array(
      'fieldname'=>'occurrence:sensitivity_precision',
      'defaultBlur' => 10000,
      'additionalControls' => '',
      'precisions' => array(100, 1000, 2000, 10000, 100000)
    ), $options);
    $r = '<fieldset><legend>'.lang::get('Sensitivity').'</legend>';
    $r .= data_entry_helper::checkbox(array(
      'id' => 'sensitive-checkbox',
      'fieldname'=>'sensitive',
      'label'=>lang::get('Is the record sensitive?')
    ));
    // Put a hidden input out, so that when the select control is disabled we get an empty value posted to clear the sensitivity
    $r .= '<input type="hidden" name="'.$options['fieldname'].'">';
    $r .= '<div id="sensitivity-controls">';
    $lookupValues = array_intersect_key(array('100'=>lang::get('Blur to 100m'), '1000'=>lang::get('Blur to 1km'), '2000'=>lang::get('Blur to 2km'),
        '10000'=>lang::get('Blur to 10km'), '100000'=>lang::get('Blur to 100km')),
        array_combine($options['precisions'], $options['precisions']));
    $r .= data_entry_helper::select(array(
      'fieldname'=>$options['fieldname'],
      'id' => 'sensitive-blur',
      'label'=>lang::get('Blur record to'),
      'lookupValues' => $lookupValues,
      'blankText' => 'none',
      'helpText' => lang::get('This is the precision that the record will be shown at for public viewing')
    ));
    // output any extra controls which should get disabled when the record is not sensitive.
    $r .= $options['additionalControls'];
    $r .= '</div></fieldset>';
    self::$javascript .= "
var doSensitivityChange = function(evt) {
  if ($('#sensitive-checkbox').is(':checked')) {
    $('#sensitivity-controls input, #sensitivity-controls select').removeAttr('disabled');
  } else {
    $('#sensitivity-controls input, #sensitivity-controls select').attr('disabled', true);
  }
  $('#sensitivity-controls').css('opacity', $('#sensitive-checkbox').is(':checked') ? 1 : .5);
  if ($('#sensitive-checkbox').is(':checked')=== true && typeof evt!=='undefined' && $('#sensitive-blur').val()==='') {
    // set a default
    $('#sensitive-blur').val('".$options['defaultBlur']."');
  }
  else if (typeof evt!=='undefined') {
    $('#sensitive-blur').val('');
  }
};
$('#sensitive-checkbox').change(doSensitivityChange);
$('#sensitive-checkbox').attr('checked', $('#sensitive-blur').val()==='' ? false : true);
doSensitivityChange();
$('#sensitive-blur').change(function() {
  if ($('#sensitive-blur').val()==='') {
    $('#sensitive-checkbox').attr('checked', false);
    doSensitivityChange();
  }
});
\n";
    return $r;
  }

  /**
   * A control for inputting a time value. Provides a text input with a spin control that allows
   * the time to be input. Reverts to a standard text input when JavaScript disabled.
   * @param array $options Options array with the following possibilities:
   * <ul>
   * <li><b>fieldname</b><br/>
   * Required. The name of the database field this control is bound to.</li>
   * <li><b>id</b><br/>
   * Optional. The id to assign to the HTML control. If not assigned the fieldname is used.</li>
   * <li><b>default</b><br/>
   * Optional. The default value to assign to the control. This is overridden when reloading a
   * record with existing data for this control.</li>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control.</li>
   * <li><b>beforeSetTime</b><br/>
   * Optional. Set this to the name of a JavaScript function which is called when the user tries to set a time value. This
   * can be used, for example, to display a warning label when an out of range time value is input. See <a '.
   * href="http://keith-wood.name/timeEntry.html">jQuery Time Entry</a> then click on the Restricting tab for more information.</li>
   * <li><b>timeSteps</b><br/>
   * Optional. An array containing 3 values for the allowable increments in time for hours, minutes and seconds respectively. Defaults to
   * 1, 15, 0 meaning that the increments allowed are in 15 minute steps and seconds are ignored.</li>
   * </ul>
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>text_input</b></br>
   * HTML template used to generate the input element. Time management aspects of this are managed
   * by JavaScript.
   * </li>
   * </ul>
   */
  public static function time_input($options) {
    $options = array_merge(array(
      'id' => $options['fieldname'],
      'default' => '',
      'timeSteps' => array(1,15,0)
    ), $options);
    self::add_resource('timeentry');
    $steps = implode(', ', $options['timeSteps']);
    $imgPath = empty(self::$images_path) ? self::relative_client_helper_path()."../media/images/" : self::$images_path;
    // build a list of options to pass through to the jQuery widget
    $jsOpts = array(
      "timeSteps: [$steps]",
      "spinnerImage: '".$imgPath."/spinnerGreen.png'"
    );
    if (isset($options['beforeSetTime']))
      $jsOpts[] = "beforeSetTime: ".$options['beforeSetTime'];
    // ensure ID is safe for jQuery selectors
    $safeId = str_replace(':','\\\\:',$options['id']);
    self::$javascript .= "$('#".$safeId."').timeEntry({".implode(', ', $jsOpts)."});\n";
    return self::apply_template('text_input', $options);
  }

  /**
   * Helper function to generate a treeview from a given list
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * Required. The name of the database field this control is bound to, for example 'occurrence:taxa_taxon_list_id'.
   * NB the tree itself will have an id of "tr$fieldname".</li>
   * <li><b>id</b><br/>
   * Optional. ID of the control. Defaults to the fieldname.</li>
   * <li><b>table</b><br/>
   * Required. Name (Kohana-style) of the database entity to be queried.</li>
   * <li><b>view</b><br/>
   * Name of the view of the table required (list, detail). This view must contain
   * the parent field so, for taxa, ensure you set this to detail.</li>
   * <li><b>captionField</b><br/>
   * Field to draw values to show in the control from.</li>
   * <li><b>valueField</b><br/>
   * Field to draw values to return from the control from. Defaults
   * to the value of $captionField.</li>
   * <li><b>parentField</b><br/>
   * Field used to indicate parent within tree for a record.</li>
   * <li><b>default</b><br/>
   * Initial value to set the control to (not currently used).</li>
   * <li><b>extraParams</b><br/>
   * Array of key=>value pairs which will be passed to the service
   * as GET parameters. Needs to specify the read authorisation key/value pair, needed for making
   * queries to the data services.</li>
   * <li><b>extraClass</b><br/>
   * main class to be added to UL tag - currently can be treeview, treeview-red,
   * treeview_black, treeview-gray. The filetree class although present, does not work properly.</li>
   * </ul>
   *
   * TODO
   * Need to do initial value.
   */
  public static function treeview($options)
  {
    global $indicia_templates;
    self::add_resource('treeview_async');
    // Declare the data service
    if (!empty(parent::$warehouse_proxy))
      $url = parent::$warehouse_proxy."index.php/services/data";
    else
      $url = parent::$base_url."index.php/services/data";
    // Setup some default values
    $options = array_merge(array(
      'valueField'=>$options['captionField'],
      'class'=>'treeview',
      'id'=>$options['fieldname'],
      'view'=>'list'
    ), self::check_options($options));
    $default = self::check_default_value($options['fieldname'],
      array_key_exists('default', $options) ? $options['default'] : null);
    // Do stuff with extraParams
    $sParams = '';
    foreach ($options['extraParams'] as $a => $b){
      $sParams .= "$a : '$b',";
    }
    // lop the comma off the end
    $sParams = substr($sParams, 0, -1);
    extract($options, EXTR_PREFIX_ALL, 'o');

    $escaped_fieldname = self::jq_esc($o_fieldname);
    self::$javascript .= "jQuery('#tr$escaped_fieldname').treeview({
      url: '$url/$o_table',
      extraParams : {
        orderby : '$o_captionField',
        mode : 'json',
        $sParams
      },
      valueControl: '$escaped_fieldname',
      valueField: '$o_valueField',
      captionField: '$o_captionField',
      view: '$o_view',
      parentField: '$o_parentField',
      dataType: 'jsonp',
      nodeTmpl: '".$indicia_templates['treeview_node']."'
    });\n";

    $tree = '<input type="hidden" class="hidden" id="'.$o_id.'" name="'.$o_fieldname.'" /><ul id="tr'.$o_id.'" class="'.$o_class.'"></ul>';
    $tree .= self::check_errors($o_fieldname);
    return $tree;
  }

  /**
   * Helper function to generate a browser control from a given list. The browser
   * behaves similarly to a treeview, except that the child lists are appended to the control
   * rather than inserted as list children. This allows controls to be created which allow
   * selection of an item, then the control is updated with the new list of options after each
   * item is clicked.
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>tree_browser</b></br>
   * HTML template used to generate container element for the browser.
   * </li>
   * <li><b>tree_browser_node</b></br>
   * HTML template used to generate each node that appears in the browser.
   * </li>
   * </ul>
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>fieldname</b><br/>
   * Required. The name of the database field this control is bound to, for example 'occurrence:taxa_taxon_list_id'.
   * NB the tree itself will have an id of "tr$fieldname".</li>
   * <li><b>id</b><br/>
   * Optional. ID of the hidden input which contains the value. Defaults to the fieldname.</li>
   * <li><b>divId</b><br/>
   * Optional. ID of the outer div. Defaults to div_ plus the fieldname.</li>
   * <li><b>table</b><br/>
   * Required. Name (Kohana-style) of the database entity to be queried.</li>
   * <li><b>view</b><br/>
   * Name of the view of the table required (list, detail).</li>
   * <li><b>captionField</b><br/>
   * Field to draw values to show in the control from.</li>
   * <li><b>valueField</b><br/>
   * Field to draw values to return from the control from. Defaults
   * to the value of $captionField.</li>
   * <li><b>parentField</b><br/>
   * Field used to indicate parent within tree for a record.</li>
   * <li><b>default</b><br/>
   * Initial value to set the control to (not currently used).</li>
   * <li><b>extraParams</b><br/>
   * Array of key=>value pairs which will be passed to the service
   * as GET parameters. Needs to specify the read authorisation key/value pair, needed for making
   * queries to the data services.</li>
   * <li><b>outerClass</b><br/>
   * Class to be added to the control's outer div.</li>
   * <li><b>class</b><br/>
   * Class to be added to the input control (hidden).</li>
   * <li><b>default</b><br/>
   * Optional. The default value for the underlying control.</li>
   * </ul>
   *
   * TODO
   * Need to do initial value.
   */
  public static function tree_browser($options) {
    global $indicia_templates;
    self::add_resource('treeBrowser');
    // Declare the data service
    $url = parent::$base_url."index.php/services/data";
    // Apply some defaults to the options
    $options = array_merge(array(
      'valueField' => $options['captionField'],
      'id' => $options['fieldname'],
      'divId' => 'div_'.$options['fieldname'],
      'singleLayer' => true,
      'outerClass' => 'ui-widget ui-corner-all ui-widget-content tree-browser',
      'listItemClass' => 'ui-widget ui-corner-all ui-state-default',
      'default' => self::check_default_value($options['fieldname'],
        array_key_exists('default', $options) ? $options['default'] : ''),
      'view'=>'list'
    ), $options);
    $escaped_divId=str_replace(':','\\\\:',$options['divId']);
    // Do stuff with extraParams
    $sParams = '';
    foreach ($options['extraParams'] as $a => $b){
      $b = str_replace("'", "\'", $b);
      $sParams .= "$a : '$b',";
    }
    // lop the comma off the end
    $sParams = substr($sParams, 0, -1);
    extract($options, EXTR_PREFIX_ALL, 'o');
    self::$javascript .= "
$('div#$escaped_divId').indiciaTreeBrowser({
  url: '$url/$o_table',
  extraParams : {
    orderby : '$o_captionField',
    mode : 'json',
    $sParams
  },
  valueControl: '$o_id',
  valueField: '$o_valueField',
  captionField: '$o_captionField',
  view: '$o_view',
  parentField: '$o_parentField',
  nodeTmpl: '".$indicia_templates['tree_browser_node']."',
  singleLayer: '$o_singleLayer',
  backCaption: '".lang::get('back')."',
  listItemClass: '$o_listItemClass',
  defaultValue: '$o_default'
});\n";
    return self::apply_template('tree_browser', $options);
  }

  /**
   * Outputs a panel and "Precheck my records" button. When clicked, the contents of the
   * current form are sent to the warehouse and run through any data cleaner verification
   * rules. The results are then displayed in the panel allowing the user to provide more
   * details for records of interest before submitting the form. Requires the data_cleaner
   * module to be enabled on the warehouse.
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>verification_panel</b></br>
   * HTML template used to generate container element for the verification panel.
   * </li>
   * </ul>
   * @global type $indicia_templates
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>readAuth</b><br/>
   * Read authorisation tokens
   * </ul>
   * <li><b>panelOnly</b><br/>
   * Default false. If true, then the button is ommited and the button's functionality
   * must be included elsewhere on the page.
   * </li>
   * </ul>
   * @return type HTML to insert onto the page for the verification panel.
   */
  public function verification_panel($options) {
    global $indicia_templates;
    $options = array_merge(array(
      'panelOnly'=>false
    ), $options);
    $button=$options['panelOnly'] ? '' :
      self::apply_replacements_to_template($indicia_templates['button'],
        array('href'=>'#', 'id'=>'verify-btn', 'class' => 'class="indicia-button"', 'caption'=>lang::get('Precheck my records'), 'title'=>''));
    $replacements = array(
      'button'=>$button
    );
    self::add_resource('verification');
    self::$javascript .= "indiciaData.verifyMessages=[];\n";
    self::$javascript .= "indiciaData.verifyMessages.nothingToCheck='".
      lang::get('There are no records on this form to check.')."';\n";
    self::$javascript .= "indiciaData.verifyMessages.completeRecordFirst='".
      lang::get('Before checking, please complete at least the date and grid reference of the record.')."';\n";
    self::$javascript .= "indiciaData.verifyMessages.noProblems='".
      lang::get('Automated verification checks did not find anything of note.')."';\n";
    self::$javascript .= "indiciaData.verifyMessages.problems='".
      lang::get('Automated verification checks resulted in the following messages:')."';\n";
    self::$javascript .= "indiciaData.verifyMessages.problemsFooter='".
      lang::get('A message not mean that there is anything wrong with the record, but if you can provide as much information '.
        'as possible, including photos, then it will help with its confirmation.')."';\n";
    return self::apply_replacements_to_template($indicia_templates['verification_panel'], $replacements);
  }

  /**
   * Insert buttons which, when clicked, displays the next or previous tab. Insert this inside the tab divs
   * on each tab you want to have a next or previous button, excluding the last tab.
   * The output of this control can be configured using the following templates:
   * <ul>
   * <li><b>button</b></br>
   * HTML template used for buttons other than the form submit button.
   * </li>
   * <li><b>submitButton</b></br>
   * HTML template used for the submit and delete buttons.
   * </li>
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>divId</b><br/>
   * The id of the div which is tabbed and whose next tab should be selected.</li>
   * <li><b>captionNext</b><br/>
   * Optional. The untranslated caption of the next button. Defaults to next step.</li>
   * <li><b>captionPrev</b><br/>
   * Optional. The untranslated caption of the previous button. Defaults to prev step.</li>
   * <li><b>class</b><br/>
   * Optional. Additional classes to add to the div containing the buttons. Use left, right or
   * centre to position the div, making sure the containing element is either floated, or has
   * overflow: auto applied to its style. Default is right.</li>
   * <li><b>buttonClass</b><br/>
   * Class to add to the button elements.</li>
   * <li><b>page</b><br/>
   * Specify first, middle or last to indicate which page this is for. Use middle (the default) for
   * all pages other than the first or last.</li>
   * <li><b>includeDeleteButton</b>
   * Set to true if allowing deletion of the record.</li>
   * <li><b>includeVerifyButton</b>
   * Defaults to false. If set to true, then a Precheck my records button is added to the
   * button set. There must be a verification_panel control added to the page somewhere
   * with the panelOnly option set to true. When this button is clicked, the verification
   * panel will be populated with the output of the automated verification check run
   * against the proposed records on the form.
   * </li>
   * </ul>
   *
   * @link http://docs.jquery.com/UI/Tabs
   */
  public static function wizard_buttons($options=array()) {
    global $indicia_templates;
    // Default captions
    $options = array_merge(array(
      'captionNext' => 'Next step',
      'captionPrev' => 'Prev step',
      'captionSave' => 'Save',
      'captionDelete'=> 'Delete',
      'buttonClass' => 'indicia-button inline-control',
      'class'       => 'right',
      'page'        => 'middle',
      'includeVerifyButton' => false,
      'includeSubmitButton' => true,
      'includeDeleteButton' => false,
      'controlWrapTemplate' => 'justControl'
    ), $options);
    $options['class'] .= ' buttons';
    // Output the buttons
    $r = '<div class="'.$options['class'].'">';
    $buttonClass=$options['buttonClass'];
    if (array_key_exists('divId', $options)) {
      if ($options['includeVerifyButton']) {
        $r .= self::apply_replacements_to_template($indicia_templates['button'],
          array('href'=>'#', 'id'=>'verify-btn', 'class' => 'class="indicia-button"', 'caption'=>lang::get('Precheck my records'), 'title'=>''));
      }
      if ($options['page']!='first') {
        $options['class']=$buttonClass." tab-prev";
        $options['id']='tab-prev';
        $options['caption']='&lt; '.lang::get($options['captionPrev']);
        $r .= str_replace('{content}', str_replace("\n", "", self::apply_template('button', $options)), $indicia_templates['jsWrap']);
      }
      if ($options['page']!='last') {
        $options['class']=$buttonClass." tab-next";
        $options['id']='tab-next';
        $options['caption']=lang::get($options['captionNext']).' &gt;';
        $r .= str_replace('{content}', str_replace("\n", "", self::apply_template('button', $options)), $indicia_templates['jsWrap']);
      } else {
        if ($options['includeSubmitButton']) {
          $options['class']=$buttonClass." tab-submit";
          $options['id']='tab-submit';
          $options['caption']=lang::get($options['captionSave']);
          $options['name']='action-submit';
          $r .= self::apply_template('submitButton', $options);
        }
        if ($options['includeDeleteButton']) {
          $options['class']=$buttonClass." tab-delete";
          $options['id']='tab-delete';
          $options['caption']=lang::get($options['captionDelete']);
          $options['name']='delete-button';
          $r .= self::apply_template('submitButton', $options);
        }
      }
    }
    $r .= '</div><div style="clear:both"></div>';
    return $r;
  }

  /********************************/
  /* End of main controls section */
  /********************************/

  /**
   * Returns an array defining various database object names and values required for the species
   * lookup filtering, depending on whether this is a cached lookup or a standard lookup. Used for
   * both species checklist and species autocompletes.
   * @param boolean $cached Set to true to use the cached taxon search tables rather than
   * standard taxa in taxon list views.
   */
  public static function get_species_lookup_db_definition($cached) {
    if ($cached) {
      return array (
        'tblTaxon'=>'cache_taxon_searchterm',
        'colId'=>'taxa_taxon_list_id',
        'colLanguage'=>'language_iso',
        'colSearch'=>'searchterm',
        'colTaxon'=>'original',
        'colCommon'=>'default_common_name',
        'colPreferred'=>'preferred_taxon',
        'colAuthority' => 'authority',
        'colPreferredAuthority' => 'preferred_authority',
        'valLatinLanguage' => 'lat',
        'duplicateCheckFields' => array('original','taxa_taxon_list_id')
      );
    } else {
      return array (
        'tblTaxon'=>'taxa_taxon_list',
        'colId'=>'id',
        'colLanguage'=>'language',
        'colSearch'=>'taxon',
        'colTaxon'=>'taxon',
        'colCommon'=>'common',
        'colPreferred'=>'preferred_name',
        'colAuthority' => 'authority',
        'colPreferredAuthority' => 'preferred_authority',
        'valLatinLanguage'=>'lat'
      );
    }
  }


  /**
   * Returns the browser name and version information
   * @param $agent Agent string, optional. If not suplied, then the http user agent is used.
   * @return array Browser information array. Contains name and version elements.
   */
  public static function get_browser_info($agent=null) {
    $browsers = array("firefox", "msie", "opera", "chrome", "safari",
      "mozilla", "seamonkey", "konqueror", "netscape",
      "gecko", "navigator", "mosaic", "lynx", "amaya",
      "omniweb", "avant", "camino", "flock", "aol");
    if (!$agent)
      $agent = $_SERVER['HTTP_USER_AGENT'];
    $agent = strtolower($agent);

    // Create a default response in case browser is not found.
    $r = array(
      'name' => '',
      'version' => '',
    );

    foreach($browsers as $browser)
    {
      if (preg_match("#($browser)[/ ]?([0-9.]*)#", $agent, $match))
      {
        $r['name'] = $match[1] ;
        $r['version'] = $match[2] ;
        break ;
      }
    }
    return $r;
  }

  /**
   * Removes any data entry values persisted into the $_SESSION by Indicia.
   *
   * @link	http://code.google.com/p/indicia/wiki/TutorialDataEntryWizard
   */
  public static function clear_session() {
    foreach ($_SESSION as $name=>$value) {
      if (substr($name, 0, 8)=='indicia:') {
        unset($_SESSION[$name]);
      }
    }
  }

  /**
   * Adds the data from the $_POST array into the session. Call this method when arriving at the second
   * and subsequent pages of a data entry wizard to keep the previous page's data available for saving later.
   *
   * @link	http://code.google.com/p/indicia/wiki/TutorialDataEntryWizard
   */
  public static function add_post_to_session () {
    foreach ($_POST as $name=>$value) {
      $_SESSION['indicia:'.$name]=$value;
    }
  }

  /**
   * Returns an array constructed from all the indicia variables that have previously been stored
   * in the session.
   *
   * @link	http://code.google.com/p/indicia/wiki/TutorialDataEntryWizard
   */
  public static function extract_session_array () {
    $result = array();
    foreach ($_SESSION as $name=>$value) {
      if (substr($name, 0, 8)=='indicia:') {
        $result[substr($name, 8)]=$value;
      }
    }
    return $result;
  }

  /**
   * Retrieves a data value from the Indicia Session data
   *
   * @param string $name Name of the session value to retrieve
   * @param string $default Default value to return if not set or empty
   * @link	http://code.google.com/p/indicia/wiki/TutorialDataEntryWizard
   */
  public static function get_from_session($name, $default='') {
    $result = '';
    if (array_key_exists("indicia:$name", $_SESSION)) {
      $result = $_SESSION["indicia:$name"];
    }
    if (!$result) {
      $result = $default;
    }
    return $result;
  }

  /**
   * Checks that an Id is supplied, if not, uses the fieldname as the id. Also checks if a
   * captionField is supplied, and if not uses a valueField if available. Finally, gets the control's
   * default value.
   * If the control is set to be remembered, then adds it to the list of remembered fields.
   * @param array $options Control's options array.
   */
  protected static function check_options($options) {
    // force some defaults to be present in the options
    $options = array_merge(array(
      'class'=>'',
      'multiple'=>''
    ), $options);
    // If fieldname is supplied but not id, then use the fieldname as the id
    if (!array_key_exists('id', $options) && array_key_exists('fieldname', $options)) {
      $options['id']=$options['fieldname'];
    }
    // If captionField is supplied but not valueField, use the captionField as the valueField
    if (!array_key_exists('valueField', $options) && array_key_exists('captionField', $options)) {
      $options['valueField']=$options['captionField'];
    }
    // Get a default value - either the supplied value in the options, or the loaded value, or nothing.
    if (array_key_exists('fieldname', $options)) {
      $options['default'] = self::check_default_value($options['fieldname'],
        array_key_exists('default', $options) ? $options['default'] : '');
    }
    return $options;
  }

  /**
   * Method which populates data_entry_helper::$entity_to_load with the values from an existing
   * record. Useful when reloading data to edit.
   * @param array $readAuth Read authorisation tokens
   * @param string $entity Name of the entity to load data from.
   * @param integer $id ID of the database record to load
   * @param string $view Name of the view to load attributes from, normally 'list' or 'detail'.
   * @param boolean $sharing Defaults to false. If set to the name of a sharing task
   * (reporting, peer_review, verification, data_flow or moderation), then the record can be
   * loaded from another client website if a sharing agreement is in place.
   * @link https://indicia-docs.readthedocs.org/en/latest/administrating/warehouse/website-agreements.html
   * @param boolean $loadImages If set to true, then image information is loaded as well.
   */
  public static function load_existing_record($readAuth, $entity, $id, $view = 'detail', $sharing = false, $loadImages = false) {
    $records = self::get_population_data(array(
      'table' => $entity,
      'extraParams' => $readAuth + array('id' => $id, 'view' => $view),
      'nocache' => true,
      'sharing' => $sharing
    ));
    if (empty($records))
      throw new exception(lang::get('The record you are trying to load does not exist.'));
    self::load_existing_record_from($records[0], $readAuth, $entity, $id, $view, $sharing, $loadImages);
  }

  /**
   * Version of load_existing_record which accepts an already queried record array from the database
   * as an input parameter.
   * @param array $record Record loaded from the db
   * @param array $readAuth Read authorisation tokens
   * @param string $entity Name of the entity to load data from.
   * @param integer $id ID of the database record to load
   * @param string $view Name of the view to load attributes from, normally 'list' or 'detail'.
   * @param boolean $sharing Defaults to false. If set to the name of a sharing task
   * (reporting, peer_review, verification, data_flow or moderation), then the record can be
   * loaded from another client website if a sharing agreement is in place.
   * @link https://indicia-docs.readthedocs.org/en/latest/administrating/warehouse/website-agreements.html
   * @param boolean $loadImages If set to true, then image information is loaded as well.
   * @throws Exception
   */
  public static function load_existing_record_from($record, $readAuth, $entity, $id, $view = 'detail', $sharing = false, $loadImages = false) {
    if (isset($record['error'])) throw new Exception($record['error']);
    // set form mode
    if (self::$form_mode===null) self::$form_mode = 'RELOAD';
    // populate the entity to load with the record data
    foreach($record as $key => $value) {
      self::$entity_to_load["$entity:$key"] = $value;
    }
    if ($entity=='sample') {
      self::$entity_to_load['sample:geom'] = self::$entity_to_load['sample:wkt']; // value received from db in geom is not WKT, which is assumed by all the code.
      // If the date is a vague date, use the string formatted by the db.
      // @todo Would allow better localisation if the vague date formatting could be applied on the client.
      self::$entity_to_load['sample:date'] = empty(self::$entity_to_load['sample:display_date']) ?
        self::$entity_to_load['sample:date_start'] : self::$entity_to_load['sample:display_date'];
      // If not a vague date, then the ISO formatted string from the db needs converting to local format.
      if (isset(self::$entity_to_load['sample:date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', self::$entity_to_load['sample:date'])) {
        $d = new DateTime(self::$entity_to_load['sample:date']);
        self::$entity_to_load['sample:date'] = $d->format(self::$date_format);
      }
    } elseif ($entity=='occurrence') {
      // prepare data to work in autocompletes
      if (!empty(self::$entity_to_load['occurrence:taxon']) && empty(self::$entity_to_load['occurrence:taxa_taxon_list:taxon']))
        self::$entity_to_load['occurrence:taxa_taxon_list_id:taxon'] = self::$entity_to_load['occurrence:taxon'];
    }
    if ($loadImages) {
      $images = self::get_population_data(array(
        'table' => $entity . '_medium',
        'extraParams' => $readAuth + array($entity . '_id' => $id),
        'nocache' => true,
        'sharing' => $sharing
      ));
      if (isset($images['error'])) throw new Exception($images['error']);
      foreach($images as $image) {
        self::$entity_to_load[$entity . '_medium:id:' . $image['id']]  = $image['id'];
        self::$entity_to_load[$entity . '_medium:path:' . $image['id']] = $image['path'];
        self::$entity_to_load[$entity . '_medium:caption:' . $image['id']] = $image['caption'];
        self::$entity_to_load[$entity . '_medium:media_type:' . $image['id']] = $image['media_type'];
        self::$entity_to_load[$entity . '_medium:media_type_id:' . $image['id']] = $image['media_type_id'];
      }
    }
  }

  /**
   * Issue a post request to get the population data required for a control either from
   * direct access to a data entity (table) or via a report query. The response will be cached
   * locally unless the caching option is set to false.
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>table</b><br/>
   * Singular table name used when loading from a database entity.
   * </li>
   * <li><b>report</b><br/>
   * Path to the report file to use when loading data from a report, e.g. "library/occurrences/explore_list",
   * excluding the .xml extension.
   * </li>
   * <li><b>orderby</b><br/>
   * Optional. For a non-default sort order, provide the field name to sort by. Can be comma separated
   * to sort by several fields in descending order of precedence.
   * </li>
   * <li><b>sortdir</b><br/>
   * Optional. Specify ASC or DESC to define ascending or descending sort order respectively. Can
   * be comma separated if several sort fields are specified in the orderby parameter.
   * </li>
   * <li><b>extraParams</b><br/>
   * Array of extra URL parameters to send with the web service request. Should include key value
   * pairs for the field filters (for table data) or report parameters (for the report data) as well
   * as the read authorisation tokens. Can also contain a parameter for:
   * orderby - for a non-default sort order, provide the field name to sort by. Can be comma separated
   * to sort by several fields in descending order of precedence.
   * sortdir - specify ASC or DESC to define ascending or descending sort order respectively. Can
   * be comma separated if several sort fields are specified in the orderby parameter.
   * limit - number of records to return.
   * offset - number of records to offset by into the dataset, useful when paginating through the
   * records.
   * view - use to specify which database view to load for an entity (e.g. list, detail, gv or cache).
   * Defaults to list.
   * </li>
   * <li><b>caching</b>
   * If true, then the response will be cached and the cached copy used for future calls. Default true.
   * If 'store' then although the response is not fetched from a cache, the response will be stored in the cache for possible
   * later use. Replaces the legacy nocache parameter.
   * </li>
   * <li><b>sharing</b><br/>
   * Optional. Set to verification, reporting, peer_review, moderation or data_flow to request
   * data sharing with other websites for the task. Further information is given in the link below.
   * </li>
   * </ul>
   * @link https://indicia-docs.readthedocs.org/en/latest/developing/web-services/data-services-entity-list.html
   * @link https://indicia-docs.readthedocs.org/en/latest/administrating/warehouse/website-agreements.html
   */
  public static function get_population_data($options) {
    if (isset($options['report']))
      $serviceCall = 'report/requestReport?report='.$options['report'].'.xml&reportSource=local&mode=json';
    elseif (isset($options['table'])) {
      $serviceCall = 'data/'.$options['table'].'?mode=json';
      if (isset($options['columns']))
        $serviceCall .= '&columns='.$options['columns'];
    }
    $request = "index.php/services/$serviceCall";
    if (array_key_exists('extraParams', $options)) {
      // make a copy of the extra params
      $params = array_merge($options['extraParams']);
      // process them to turn any array parameters into a query parameter for the service call
      $filterToEncode = array('where'=>array(array()));
      $otherParams = array();
      foreach($params as $param=>$value) {
        if (is_array($value))
          $filterToEncode['in'] = array($param, $value);
        elseif ($param=='orderby' || $param=='sortdir' || $param=='auth_token' || $param=='nonce' || $param=='view')
          // these params are not filters, so can't go in the query
          $otherParams[$param] = $value;
        else
          $filterToEncode['where'][0][$param] = $value;
      }
      // use advanced querying technique if we need to
      if (isset($filterToEncode['in']))
        $request .= '&query='.urlencode(json_encode($filterToEncode)).'&'.self::array_to_query_string($otherParams, true);
      else
        $request .= '&'.self::array_to_query_string($options['extraParams'], true);
    }
    if (isset($options['sharing']))
      $request .= '&sharing='.$options['sharing'];
    if (!isset($options['caching']))
      $options['caching'] = true; // default
    return self::_get_cached_services_call($request, $options);
  }

  /**
   * Helper function to clear the Indicia cache files.
   */
  public static function clear_cache() {
    $cacheFolder = parent::$cache_folder ? parent::$cache_folder : self::relative_client_helper_path() . 'cache/';
    if(!$dh = @opendir($cacheFolder)) {
      return;
    }
    while (false !== ($obj = readdir($dh))) {
      if($obj != '.' && $obj != '..')
        @unlink($cacheFolder . '/' . $obj);
    }
    closedir($dh);
  }

  /**
   * Internal function to output either a select or listbox control depending on the templates
   * passed.
   * @param array $options Control options array
   * @access private
   */
  private static function select_or_listbox($options) {
    global $indicia_templates;
    self::add_resource('json');
    $options = array_merge(array(
      'filterField'=>'parent_id',
      'size'=>3
    ), $options);
    if (array_key_exists('parentControlId', $options) && empty(data_entry_helper::$entity_to_load[$options['parentControlId']])) {
      // no options for now
      $options['items'] = '';
      self::init_linked_lists($options);
    } else {
      if (array_key_exists('parentControlId', $options))
        // still want linked lists, even though we will have some items initially populated
        self::init_linked_lists($options);
      $lookupItems = self::get_list_items_from_options($options, 'selected');
      $options['items'] = "";
      if (array_key_exists('blankText', $options)) {
        $options['blankText'] = lang::get($options['blankText']);
        $options['items'] = str_replace(
            array('{value}', '{caption}', '{selected}'),
            array('', htmlentities($options['blankText'], ENT_COMPAT, "UTF-8")), $indicia_templates[$options['itemTemplate']]
          ).(isset($options['optionSeparator']) ? $options['optionSeparator'] : "\n");;
      }
      $options['items'] .= implode((isset($options['optionSeparator']) ? $options['optionSeparator'] : "\n"), $lookupItems);
    }
    if (isset($response['error']))
      return $response['error'];
    else
      return self::apply_template($options['template'], $options);
  }

  /**
   * When populating a list control (select, listbox, checkbox or radio group), use either the
   * table, captionfield and valuefield to build the list of values as an array, or if lookupValues
   * is in the options array use that instead of making a database call.
   * @param array $options Options array for the control.
   * @param string $selectedItemAttribute Name of the attribute that should be set in each list element if the item is selected/checked. For
   * option elements, pass "selected", for checkbox inputs, pass "checked".
   * @return array Associative array of the lookup values and templated list items.
   */
  private static function get_list_items_from_options($options, $selectedItemAttribute) {
    $r = array();
    global $indicia_templates;
    $hints = (isset($options['optionHints']) ? (is_array($options['optionHints']) ? $options['optionHints'] : json_decode($options['optionHints'])) : array());
    if (is_object($hints)) {
      $hints = get_object_vars($hints);
    }
    if (isset($options['lookupValues'])) {
      // lookup values are provided, so run these through the item template
      foreach ($options['lookupValues'] as $key=>$value) {
        $selected=self::get_list_item_selected_attribute($key, $selectedItemAttribute, $options, $itemFieldname);
        $r[$key] = str_replace(
          array('{value}', '{caption}', '{'.$selectedItemAttribute.'}', '{title}'),
          array(htmlspecialchars($key), htmlspecialchars($value), $selected, (isset($hints[$value]) ? ' title="'.$hints[$value].'" ' : '')),
          $indicia_templates[$options['itemTemplate']]
        );
      }
    } else {
      // lookup values need to be obtained from the database. ParentControlId indicates a linked list parent control whose value
      // would filter this list.
      if (isset($options['parentControlId']) && !empty(data_entry_helper::$entity_to_load[$options['parentControlId']])) {
        $options['extraParams'][$options['filterField']] = data_entry_helper::$entity_to_load[$options['parentControlId']];
      }
      $response = self::get_population_data($options);
      // if the response is empty, and a language has been set, try again without the language but asking for the preferred values.
      if(count($response)==0 && array_key_exists('iso', $options['extraParams'])){
        unset($options['extraParams']['iso']);
        $options['extraParams']['preferred']='t';
        $response = self::get_population_data($options);
      }
      if (!array_key_exists('error', $response)) {
        foreach ($response as $record) {
          if (array_key_exists($options['valueField'], $record)) {
            if (isset($options['captionTemplate']))
              $caption = self::mergeParamsIntoTemplate($record, $options['captionTemplate']);
            else
              $caption = $record[$options['captionField']];
            if(isset($options['listCaptionSpecialChars'])) {
              $caption=htmlspecialchars($caption);
            }
            $value=$record[$options['valueField']];
            $selected=self::get_list_item_selected_attribute($value, $selectedItemAttribute, $options, $itemFieldname);
            // If an array field and we are loading an existing value, then the value needs to store the db ID otherwise we loose the link
            if ($itemFieldname)
              $value .= ":$itemFieldname";
            $item = str_replace(
              array('{value}', '{caption}', '{'.$selectedItemAttribute.'}', '{title}'),
              array($value, $caption, $selected, (isset($hints[$value]) ? ' title="'.$hints[$value].'" ' : '')),
              $indicia_templates[$options['itemTemplate']]
            );
            $r[$record[$options['valueField']]] = $item;
          }
        }
      }
    }
    return $r;
  }

  /**
   * Returns the selected="selected" or checked="checked" attribute required to set a default item in a list.
   * @param string $value The current item's value.
   * @param string $selectedItemAttribute Name of the attribute that should be set in each list element if the item is selected/checked. For
   * option elements, pass "selected", for checkbox inputs, pass "checked".
   * @param mixed $itemFieldname Will return the fieldname that must be associated with this particular value if using an array input
   * such as a listbox (multiselect select).
   * @param array $options Control options array which contains the "default" entry.
   */
  private static function get_list_item_selected_attribute($value, $selectedItemAttribute, $options, &$itemFieldname) {
    $itemFieldname=false;
    if (isset($options['default'])) {
      $default = $options['default'];
      // default value can be passed as an array or a single value
      if (is_array($default)) {
        $selected = false;
        foreach ($default as $defVal) {
          // default value array entries can be themselves an array, so that they store the fieldname as well as the value.
          // Or they can be just a plain value.
          if (is_array($defVal)){
            if ($defVal['default'] == $value) {
              $selected = true;
              // for an array field
              if (substr($options['fieldname'], -2)==='[]') {
                $itemFieldname = $defVal['fieldname'];
              }
            }
          }
          elseif ($value == $defVal)
            $selected = true;
        }
      } else
        $selected = ($default == $value);
      return $selected ? " $selectedItemAttribute=\"$selectedItemAttribute\"" : '';
    }
    else
      return '';
  }

  /**
   * Where there are 2 linked lists on a page, initialise the JavaScript required to link the lists.
   *
   * @param array Options array of the child linked list.
   */
  private static function init_linked_lists($options) {
    global $indicia_templates;
    // setup JavaScript to do the population when the parent control changes
    $parentControlId = str_replace(':','\\\\:',$options['parentControlId']);
    $escapedId = str_replace(':','\\\\:',$options['id']);
    $fn = preg_replace("/[^A-Za-z0-9]/", "", $options['id'])."_populate";
    if (!empty(parent::$warehouse_proxy))
      $base_url = parent::$warehouse_proxy;
    else
      $base_url = parent::$base_url;
    if (!empty($options['report'])) {
      $url = $base_url."index.php/services/report/requestReport";
      $request = "$url?report=".$options['report'].".xml&mode=json&reportSource=local&callback=?";
      $query = $options['filterField'] . '="+$(this).val()+"';
    }
    else {
      $url = $base_url."index.php/services/data";
      $request = "$url/".$options['table']."?mode=json&callback=?";
      $inArray = array('val');
      if (!isset($options['filterIncludesNulls']) || $options['filterIncludesNulls'])
        $inArray[] = null;
      $query = urlencode(json_encode(array('in'=>array($options['filterField'], $inArray))));
      $query = 'query=' . str_replace('%22val%22', '"+$(this).val()+"', $query);
    }
    if (isset($options['parentControlLabel']))
      $instruct = str_replace('{0}', $options['parentControlLabel'], lang::get('Please select a {0} first'));
    else
      $instruct = lang::get('Awaiting selection...');
    if (array_key_exists('extraParams', $options)) {
      $request .= '&'.self::array_to_query_string($options['extraParams']);
    }
    // store default in JavaScript so we can load the correct value after AJAX population.
    if (!empty($options['default']) && preg_match('/^[0-9]+$/', $options['default']))
      self::$javascript .= "indiciaData['default$escapedId']=$options[default];\n";
    self::$javascript .= str_replace(
      array('{fn}','{escapedId}','{request}','{query}','{valueField}','{captionField}','{filterField}','{parentControlId}', '{instruct}'),
      array($fn, $escapedId, $request,$query,$options['valueField'],$options['captionField'],$options['filterField'],$parentControlId, $instruct),
      $indicia_templates['linked_list_javascript']
    );
  }

  /**
   * Internal method to output either a checkbox group or a radio group.
   * @param array $options Control options array
   * @param string $type Name of the input element's type attribute, e.g. radio or checkbox.
   * When selected, an additional textarea attribute can be shown to capture the "Other" information.
   */
  private static function check_or_radio_group($options, $type) {
    $checkboxOtherIdx=false;
    // checkboxes are inherantly multivalue, whilst radio buttons are single value
    global $indicia_templates;
    $options = array_merge(
      array(
        'sep' => ' ', // space allows lines to flow, otherwise all one line.
        'template' => 'check_or_radio_group',
        'itemTemplate' => 'check_or_radio_group_item',
        'id' => $options['fieldname'],
        'class' => ''
      ),
      $options
    );
    // class picks up a default of blank, so we can't use array_merge to overwrite it
    $options['class'] = trim($options['class'] . ' control-box');
    // We want to apply validation to the inner items, not the outer control
    if (array_key_exists('validation', $options)) {
      $itemClass = self::build_validation_class($options);
      unset($options['validation']);
    } else {
      $itemClass='';
    }
    $lookupItems = self::get_list_items_from_options($options, 'checked');
    $items = "";
    $idx = 0;
    foreach ($lookupItems as $value => $template) {
      $fieldName = $options['fieldname'];
      $item = array_merge(
        $options,
        array(
          'disabled' => isset($options['disabled']) ? $options['disabled'] : '',
          'type' => $type,
          'value' => $value,
          'class' => $itemClass . (($idx == 0) ? ' first-item' : ''),
          'itemId' => $options['id'].':'.$idx
        )
      );
      $item['fieldname']=$fieldName;
      $items .= self::mergeParamsIntoTemplate($item, $template, true, true);
      $idx++;
      if (!empty($options['otherItemId']) && $value==$options['otherItemId'])
        $checkboxOtherIdx=$idx-1;
    }
    $options['items']=$items;
    // We don't want to output for="" in the top label, as it is not directly associated to a button
    $options['labelTemplate'] = (isset($options['label']) && substr($options['label'], -1) == '?' ? 'toplabelNoColon' : 'toplabel');
    if (isset($itemClass) && !empty($itemClass) && strpos($itemClass, 'required')!==false) {
      $options['suffixTemplate'] = 'requiredsuffix';
    }
    $r = self::apply_template($options['template'], $options);
    // reset the old template
    unset($options['labelTemplate']);
    // Is there an option for "Other", which requires an additional attribute to display to capture the other information?
    if (!empty($options['otherItemId'])&&!empty($options['otherValueAttrId'])) {
      //Code elsewhere can automatically draw attributes to the page if the user has specified the * option in the form structure.
      //However the sample attribute that holds the "other" value is already linked to the checkbox group. Save the id of the Other value
      //sample attribute so that the automatic attribute display code knows not to draw it, otherwise it would appear twice.
      self::$handled_attributes[] = $options['otherValueAttrId'];
      // find out the attr table we are concerned with
      switch (substr($options['otherValueAttrId'], 0, 3)) {
        case 'smp': $otherAttrTable='sample'; break;
        case 'occ': $otherAttrTable='occurrence'; break;
        case 'location': $otherAttrTable='location'; break;
        default: throw new exception($options['otherValueAttrId'] . ' not supported for otherValueAttrId option.');
      }
      //When in edit mode then we need to collect the Other value the user previously filled in.
      if (!empty(self::$entity_to_load["{$otherAttrTable}:id"])) {
        $readAuth['auth_token']=$options['extraParams']['auth_token'];
        $readAuth['nonce']=$options['extraParams']['nonce'];
        //Get the existing value for the Other textbox
        $otherAttributeData = data_entry_helper::get_population_data(array(
          'table' => "{$otherAttrTable}_attribute_value",
          'extraParams' => $readAuth + array("{$otherAttrTable}_id" => self::$entity_to_load["{$otherAttrTable}:id"], "{$otherAttrTable}_attribute_id"=>str_replace('smpAttr:', '', $options['otherValueAttrId'])),
          'nocache' => true,
        ));
      }
      //Finally draw the Other textbox to the screen, then use jQuery to hide/show the box at the appropriate time.
      $otherBoxOptions['id'] = $options['otherValueAttrId'];
      $otherBoxOptions['fieldname'] = $options['otherValueAttrId'];
      //When the field is populated with existing data, the name includes the sample_attribute_value id, this is used on submission.
      //Don't include it if it isn't pre-populated.
      if (isset($otherAttributeData[0]['id']))
        $otherBoxOptions['fieldname'] .= ':'.$otherAttributeData[0]['id'];
      //User can provide their own label for the textbox if they wish, otherwise default to "Other".
      if ($options['otherTextboxLabel'])
        $otherBoxOptions['label'] = $options['otherTextboxLabel'];
      else
        $otherBoxOptions['label'] = 'Other';
      //Fill in the textbox with existing value if in edit mode.
      if (isset($otherAttributeData[0]['value']))
        $otherBoxOptions['default']=$otherAttributeData[0]['value'];
      $r .= data_entry_helper::textarea($otherBoxOptions);
      // jQuery safe versions of the attribute IDs
      $mainAttributeIdSafe = str_replace(':', '\\\\:', $options['id']);
      $mainAttributeNameSafe = str_replace(':', '\\\\:', $options['fieldname']);
      $otherAttributeIdSafe = str_replace(':', '\\\\:', $options['otherValueAttrId']);
      //Set the visibility of the "Other" textbox based on the checkbox when the page loads, but also when the checkbox changes.
      self::$javascript .= '
        show_hide_other();
        $("input[name='.$mainAttributeNameSafe.']").change(function() {
          show_hide_other();
        });
      ';
      //Function that will show and hide the "Other" textbox depending on the value of the checkbox.
      self::$javascript .= '
      function show_hide_other() {
        if ($("#'.$mainAttributeIdSafe.'\\\\:'.$checkboxOtherIdx.'").is(":checked")) {
          $("#'.$otherAttributeIdSafe.'").show();
          $("[for=\"'.$otherAttributeIdSafe.'\"]").show();
        } else {
          $("#'.$otherAttributeIdSafe.'").val("");
          $("#'.$otherAttributeIdSafe.'").hide();
          $("[for=\"'.$otherAttributeIdSafe.'\"]").hide();
        }
      }';
    }
    return $r;
  }

  /**
   * Helper method to enable the support for tabbed interfaces for a div.
   * The jQuery documentation describes how to specify a list within the div which defines the tabs that are present.
   * This method also automatically selects the first tab that contains validation errors if the form is being
   * reloaded after a validation attempt.
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>divId</b><br/>
   * Optional. The id of the div which will be tabbed. If not specified then the caller is
   * responsible for calling the jQuery tabs plugin - this method just links the appropriate
   * jQuery files.</li>
   * <li><b>style</b><br/>
   * Optional. Possible values are tabs (default) or wizard. If set to wizard, then the tab header
   * is not displayed and the navigation should be provided by the tab_button control. This
   * must be manually added to each tab page div.</li>
   * <li><b>navButtons</b>
   * Are Next and Previous buttons used to move between pages? Always true for wizard style otherwise
   * navigation is impossible and defaults to false for tabs style.</li>
   * <li><b>progressBar</b><br/>
   * Optional. Set to true to output a progress header above the tabs/wizard which shows which
   * stage the user is on out of the sequence of steps in the wizard.</li>
   * </ul>
   *
   * @link http://docs.jquery.com/UI/Tabs
   */
  public static function enable_tabs($options) {
    // apply defaults
    $options = array_merge(array(
      'style' => 'tabs',
      'progressBar' => false,
      'progressBarOptions' => array()
    ), $options);
    if (empty($options['navButtons']))
      $options['navButtons'] = $options['style']==='wizard';
    // Only do anything if the id of the div to be tabified is specified
    if (!empty($options['divId'])) {
      // A jquery selector for the element which must be at the top of the page when moving to the next page.
      // Could be the progress bar or the tabbed div itself.
      $topSelector = $options['progressBar'] ? '.wiz-prog' : '#'.$options['divId'];
      $divId = $options['divId'];
      // Scroll to the top of the page. This may be required if subsequent tab pages are longer than the first one, meaning the
      // browser scroll bar is too long making it possible to load the bottom blank part of the page if the user accidentally
      // drags the scroll bar while the page is loading.
      self::$javascript .= "scroll(0,0);\n";

      // Client-side validation only works on active tabs so validate on tab change
      if ($options['style']==='wizard' || $options['navButtons']) {
        //Add javascript for moving through wizard
        self::$javascript .= "setupTabsNextPreviousButtons('$divId', '$topSelector');\n";
      }
      //Add javascript for validation on changing tabs and linking the wizard submit button to form submit
      self::$javascript .= "setupTabsBeforeActivate('$divId');\n";
      self::$javascript .= "indiciaData.langErrorsOnTab = '".lang::get('Before continuing, some of the values in the input ' .
          'boxes on this page need checking. They have been highlighted on the form for you.')."';\n";

      // We put this javascript into $late_javascript so that it can come after the other controls.
      // This prevents some obscure bugs - e.g. OpenLayers maps cannot be centered properly on hidden
      // tabs because they have no size
      $uniq = preg_replace("/[^a-z]+/", "", strtolower($divId));
      self::$late_javascript .= "var tabs$uniq = $(\"#$divId\").tabs();\n";
      // find any errors on the tabs.
      self::$late_javascript .= "var errors$uniq=$(\"#$divId .ui-state-error\");\n";
      // select the tab containing the first error, if validation errors are present
      self::$late_javascript .= "
if (errors$uniq.length>0) {
  indiciaFns.activeTab(tabs$uniq, $(errors{$uniq}[0]).parents('.ui-tabs-panel')[0].id);
  var panel;
  for (var i=0; i<errors$uniq.length; i++) {
    panel = $(errors{$uniq}[i]).parents('.ui-tabs-panel')[0];
    $('#'+panel.id+'-tab').addClass('ui-state-error');
  }
}\n";
      if (array_key_exists('active', $options)) {
        self::$late_javascript .= "else {indiciaFns.activeTab(tabs$uniq,'".$options['active']."');}\n";
      }
      if (array_key_exists('style', $options) && $options['style']=='wizard') {
        self::$late_javascript .= "$('#$divId .ui-tabs-nav').hide();\n";
      }
    }
    // add a progress bar to indicate how many steps are complete in the wizard
    if (isset($options['progressBar']) && $options['progressBar']==true) {
      data_entry_helper::add_resource('wizardprogress');
      $progressBarOptions = array_merge(array('divId' => $divId), $options['progressBarOptions']);
      data_entry_helper::$javascript .= "wizardProgressIndicator(".json_encode($progressBarOptions).");\n";
    } else {
      data_entry_helper::add_resource('tabs');
    }
  }

  /**
   * Outputs the ul element that needs to go inside a tabified div control to define the header tabs.
   * This is required for wizard interfaces as well.
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>tabs</b><br/>
   * Array of tabs, with each item being the tab title, keyed by the tab ID including the #.</li>
   */
  public static function tab_header($options) {
    $options = self::check_options($options);
    // Convert the tabs array to a string of <li> elements
    $tabs = "";
    foreach($options['tabs'] as $link => $caption) {
      $tabId=substr("$link-tab",1);
      //rel="address:..." enables use of jQuery.address module (http://www.asual.com/jquery/address/)
      if ($tabs == ""){
        $address = "";
      } else {
        $address = (substr($link, 0, 1) == '#') ? substr($link, 1) : $link;
      }
      // safe single quotes to render into JS
      $caption = str_replace("'","\'",$caption);
      $tabs .= "<li id=\"$tabId\"><a href=\"$link\" rel=\"address:/$address\"><span>$caption</span></a></li>";
    }
    $options['tabs'] = $tabs;
    return self::apply_template('tab_header', $options);
  }

  /** Insert a button which, when clicked, displays the previous tab. Insert this inside the tab divs
   * on each tab you want to have a next button, excluding the first tab.
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>divId</b><br/>
   * The id of the div which is tabbed and whose next tab should be selected.</li>
   * <li><b>caption</b><br/>
   * Optional. The untranslated caption of the button. Defaults to previous step.</li>
   *
   * @link http://docs.jquery.com/UI/Tabs
   */
  public static function tab_prev_button($options) {
    if (!array_key_exists('caption', $options)) $options['caption'] = 'previous step';
    $options['caption'] = lang::get($options['caption']);
    if (!array_key_exists('class', $options)) $options['class'] = 'ui-widget-content ui-state-default ui-corner-all indicia-button prev-tab';
    if (array_key_exists('divId', $options)) {
      return self::apply_template('tab_prev_button', $options);
    }
  }

  /**
   * <p>Allows the demarcation of the start of a region of the page HTML to be declared which will be replaced by
   * a loading message whilst the page is loading.</p>
   * <p>If JavaScript is disabled then this has no effect. Note that hiding the block is achieved by setting
   * it's left to move it off the page, rather than display: none. This is because OpenLayers won't initialise
   * properly on a div that is display none.</p>
   * <p><b>Warning.</b> To use this function, always insert a call to dump_header in the <head> element of your
   * HTML page to ensure that JQuery is loaded first. Otherwise this will not work.</p>
   *
   * @return string HTML and JavaScript to insert into the page at the start of the block
   * which is replaced by a loading panel while the page is loading.
   */
  public static function loading_block_start() {
    global $indicia_templates;
    self::add_resource('jquery_ui');
    // For clean code, the jquery_ui stuff should have gone out in the page header, but just in case.
    // Don't bother from inside Drupal, since the header is added after the page code runs
    if (!in_array('jquery_ui', self::$dumped_resources) && !defined('DRUPAL_BOOTSTRAP_CONFIGURATION')) {
      $r = self::internal_dump_resources(array('jquery_ui'));
      array_push(self::$dumped_resources, 'jquery_ui');
    } else {
      $r = '';
    }
    $r .= $indicia_templates['loading_block_start'];
    return $r;
  }

  /**
   * Allows the demarcation of the end of a region of the page HTML to be declared which will be replaced by
   * a loading message whilst the page is loading.
   *
   * @return string HTML and JavaScript to insert into the page at the start of the block
   * which is replaced by a loading panel while the page is loading.
   */
  public static function loading_block_end() {
    global $indicia_templates;
    // First hide the message, then hide the form, slide it into view, then show it.
    // This script must precede the other scripts onload, otherwise they may have problems because
    // of assumptions that the controls are visible.
    self::$onload_javascript = "$('.loading-panel').remove();\n".
      "var panel=$('.loading-hide')[0];\n".
      "$(panel).hide();\n".
      "$(panel).removeClass('loading-hide');\n".
      "$(panel).fadeIn('slow');\n" .
      self::$onload_javascript;
    return $indicia_templates['loading_block_end'];
  }

  /**
   * Either takes the passed in submission, or creates it from the post data if this is null, and forwards
   * it to the data services for saving as a member of the entity identified.
   * @param string $entity Name of the top level entity being submitted, e.g. sample or occurrence.
   * @param array $submission The wrapped submission structure. If null, then this is automatically constructer
   * from the form data in $_POST.
   * @param array $writeTokens Array containing auth_token and nonce for the write operation, plus optionally persist_auth=true
   * to prevent the authentication tokens from expiring after use. If null then the values are read from $_POST.
   */
  public static function forward_post_to($entity, $submission = null, $writeTokens = null) {
    if (self::$validation_errors==null) {
      $remembered_fields = self::get_remembered_fields();

      if ($submission == null)
        $submission = submission_builder::wrap($_POST, $entity);
      if ($remembered_fields !== null) {
        // the form is configured to remember fields
        if ( (!isset($_POST['cookie_optin'])) || ($_POST['cookie_optin'] === '1') ) {
          // if given a choice, the user opted for fields to be remembered
          $arr=array();
          foreach ($remembered_fields as $field) {
            if (!empty($_POST[$field]))
              $arr[$field]=$_POST[$field];
          }
          // put them in a cookie with a 30 day expiry
          setcookie('indicia_remembered', serialize($arr), time()+60*60*24*30);
          // cookies are only set when the page is loaded. So if we are reloading the same form after submission,
          // we need to fudge the cookie
          $_COOKIE['indicia_remembered'] = serialize($arr);
        } else {
          // the user opted out of having a cookie - delete one if present.
          setcookie('indicia_remembered', '');
        }
      }

      $media = self::extract_media_data($_POST);
      $request = parent::$base_url."index.php/services/data/$entity";
      $postargs = 'submission='.urlencode(json_encode($submission));
      // passthrough the authentication tokens as POST data. Use parameter writeTokens, or current $_POST if not supplied.
      if ($writeTokens) {
        foreach($writeTokens as $token => $value){
          $postargs .= '&'.$token.'='.($value === true ? 'true' : ($value === false ? 'false' : $value));
        } // this will do auth_token, nonce, and persist_auth
      } else {
        if (array_key_exists('auth_token', $_POST))
          $postargs .= '&auth_token='.$_POST['auth_token'];
        if (array_key_exists('nonce', $_POST))
          $postargs .= '&nonce='.$_POST['nonce'];
      }

      // pass through the user_id if hostsite_get_user_field is implemented
      if (function_exists('hostsite_get_user_field'))
        $postargs .= '&user_id='.hostsite_get_user_field('indicia_user_id');
      // look for media files attached to fields like group:logo_path (*:*_path)
      // which are not in submodels, so not picked up by the extract_media_data code.
      foreach ($_FILES as $fieldname => $file) {
        if (empty($file['error']) && preg_match('/^([a-z_]+:)?[a-z_]+_path$/', $fieldname)) {
          $media[] = array('path' => $file['name']);
        }
      }
      // if there are images, we will send them after the main post, so we need to persist the write nonce
      if (count($media)>0)
        $postargs .= '&persist_auth=true';
      $response = self::http_post($request, $postargs);
      // The response should be in JSON if it worked
      $output = json_decode($response['output'], true);
      // If this is not JSON, it is an error, so just return it as is.
      if (!$output)
        $output = $response['output'];
      if (is_array($output) && array_key_exists('success', $output))  {
        if (isset(self::$final_image_folder) && self::$final_image_folder!='warehouse') {
          // moving the files on the local machine. Find out where from and to
          $interim_image_folder = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . self::relative_client_helper_path().
            (isset(parent::$interim_image_folder) ? parent::$interim_image_folder : 'upload/');
          $final_image_folder = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . self::relative_client_helper_path().
            parent::$final_image_folder;
        }
        // submission succeeded. So we also need to move the images to the final location
        $image_overall_success = TRUE;
        $image_errors = array();
        foreach ($media as $item) {
          // no need to resend an existing image, or a media link, just local files.
          if ((empty($item['media_type']) || preg_match('/:Local$/', $item['media_type'])) && (!isset($item['id']) || empty($item['id']))) {
            if (!isset(self::$final_image_folder) || self::$final_image_folder=='warehouse') {
              // Final location is the Warehouse
              // @todo Set PERSIST_AUTH false if last file
              $success = self::send_file_to_warehouse($item['path'], true, $writeTokens);
            } else {
              $success = rename($interim_image_folder.$item['path'], $final_image_folder.$item['path']);
            }
            if ($success !== true) {
              // Record all files that fail to move successfully.
              $image_overall_success = FALSE;
              $image_errors[] = $success;
            }
          }
        }
        if (!$image_overall_success) {
          // Report any file transfer failures.
          $error = lang::get('submit ok but file transfer failed') . '<br/>';
          $error .= implode('<br/>', $image_errors);
          $output = array('error' => $error);
        }
      }
      return $output;
    }
    else
      return array('error' => 'Pre-validation failed', 'errors' => self::$validation_errors);
  }

  /**
   * Wraps data from a species checklist grid (generated by
   * data_entry_helper::species_checklist) into a suitable format for submission. This will
   * return an array of submodel entries which can be dropped directly into the subModel
   * section of the submission array. If there is a field occurrence:determiner_id, occurrence:record_status,
   * occurrence:training or occurrence:release_status in the main form data, then these values are applied to each new
   * occurrence created from the grid. For example, place a hidden field in the form named
   * "occurrence:record_status" with a value "C" to set all new occurrence records to completed
   * as soon as they are entered.
   *
   * @param array $arr Array of data generated by data_entry_helper::species_checklist method.
   * @param boolean $include_if_any_data If true, then any list entry which has any data
   * set will be included in the submission. This defaults to false, unless the grid was
   * created with rowInclusionCheck=hasData in the grid options.
   * @param array $zero_attrs Set to an array of attribute defs keyed by attribute ID that can be
   * treated as abundances. Alternatively set to true to treat all occurrence custom attributes
   * as possible zero abundance indicators.
   * @param array $zero_values Set to an array of values which are considered to indicate a
   * zero abundance record if found for one of the zero_attrs. Values are case-insensitive. Defaults to
   * array('0','None','Absent').
   */
  public static function wrap_species_checklist($arr, $include_if_any_data=false,
                                                $zero_attrs = true, $zero_values=array('0','None','Absent')){
    if (array_key_exists('website_id', $arr)){
      $website_id = $arr['website_id'];
    } else {
      throw new Exception('Cannot find website id in POST array!');
    }
    $fieldDefaults = self::speciesChecklistGetFieldDefaults($arr);
    // Set the default method of looking for rows to include - either using data, or the checkbox (which could be hidden)
    $include_if_any_data = $include_if_any_data || (isset($arr['rowInclusionCheck']) && $arr['rowInclusionCheck']=='hasData');
    // Species checklist entries take the following format.
    // sc:<grid_id>-<rowIndex>:[<occurrence_id>]:present (checkbox with val set to ttl_id
    // or
    // sc:<grid_id>-<rowIndex>:[<occurrence_id>]:occAttr:<occurrence_attribute_id>[:<occurrence_attribute_value_id>]
    // or
    // sc:<grid_id>-<rowIndex>:[<occurrence_id>]:occurrence:comment
    // or
    // sc:<grid_id>-<rowIndex>:[<occurrence_id>]:occurrence_medium:fieldname:uniqueImageId
    $records = array();
    // $records will be an array containing an entry for every row in every grid on the page
    $allRowInclusionCheck = array();
    // $allRowInclusionCheck will be an array containing an entry for every grid that specified a value of hasData
    $allHasDataIgnoreAttrs = array();
    // $allHasDataIgnoreAttrs will be an array containing an entry for every grid that specified a value
    $subModels = array();
    foreach ($arr as $key => $value){
      if (substr($key, 0, 3) == 'sc:'){
        // Don't explode the last element for occurrence attributes
        $a = explode(':', $key, 4);
        // skip the hidden clonable row for each grid, which will have -idx- instead of a row number
        if (preg_match('/-idx-$/', $a[1]))
          continue;
        // skip the extra row at the end for input of new rows
        if (!array_key_exists("$a[0]:$a[1]:$a[2]:present", $arr))
          continue;
        if (is_array($value) && count($value)>0) {
          // The value is an array, so might contain existing database ID info in the value to link to existing records
          foreach ($value as $idx=>$arrayItem) {
            // does the entry contain the value record ID (required for existing values in controls which post arrays, like multiselect selects)?
            if (preg_match("/^\d+:\d+$/", $arrayItem)) {
              $tokens=explode(':', $arrayItem);
              $records[$a[1]][$a[3].":$tokens[1]:$idx"] = $tokens[0];
            } else
              $records[$a[1]][$a[3]."::$idx"] = $arrayItem;
          }
        } else {
          $records[$a[1]][$a[3]] = $value;
          // store any id so update existing record
          if($a[2])
            $records[$a[1]]['id'] = $a[2];
        }
      }
      else if (substr($key, 0, 19) == 'hasDataIgnoreAttrs-') {
        $tableId = substr($key, 19);
        $allHasDataIgnoreAttrs[$tableId] = explode(',', $value);
      }
      else if (substr($key, 0, 18) == 'rowInclusionCheck-') {
        $tableId = substr($key, 18);
        $allRowInclusionCheck[$tableId] = $value;
      }
    }
    // get the posted data that might apply species association/interaction information
    $assocDataKeys = preg_grep('/occurrence_association:\d+:(\d+)?:from_occurrence_id/', array_keys($arr));
    $assocData = count($assocDataKeys) ?
        array_intersect_key($arr, array_combine($assocDataKeys, $assocDataKeys)) : array();
    $existingSampleIdsBySref = !empty($_POST['existingSampleIdsBySref']) ?
        json_decode($_POST['existingSampleIdsBySref'], true) : array();
    $unusedExistingSampleIds = array_values($existingSampleIdsBySref);
    foreach ($records as $id => $record) {
      // determine the id of the grid this record is from
      // $id = <grid_id>-<rowIndex> but <grid_id> could contain a hyphen
      $a = explode('-', $id);
      array_pop($a);
      $tableId = implode('-', $a);
      // determine any hasDataIgnoreAttrs for this record
      $hasDataIgnoreAttrs = array_key_exists($tableId, $allHasDataIgnoreAttrs) ?
        $allHasDataIgnoreAttrs[$tableId] : array();
      // use default value of $include_if_any_data or override with a table specific value
      $include_if_any_data = array_key_exists($tableId, $allRowInclusionCheck) && $allRowInclusionCheck[$tableId] = 'hasData' ?
          true : $include_if_any_data;
      // determine if this record is for presence, absence or nothing
      $present = self::wrap_species_checklist_record_present($record, $include_if_any_data,
        $zero_attrs, $zero_values, $hasDataIgnoreAttrs);
      if (array_key_exists('id', $record) || $present!==null) { // must always handle row if already present in the db
        if ($present===null)
          // checkboxes do not appear if not checked. If uncheck, delete record.
          $record['deleted'] = 't';
        else
          $record['zero_abundance']=$present ? 'f' : 't';
        $record['taxa_taxon_list_id'] = $record['present'];
        $record['website_id'] = $website_id;
        self::speciesChecklistApplyFieldDefaults($fieldDefaults, $record);
        // Handle subsamples indicated by a row specific map ref
        if (!empty($record['occurrence:spatialref'])) {
          $sref = trim($record['occurrence:spatialref']);
          unset($record['occurrence:spatialref']);
        } else
          $sref = null;
        $occ = data_entry_helper::wrap($record, 'occurrence');
        self::attachOccurrenceMediaToModel($occ, $record);
        self::attachAssociationsToModel($id, $occ, $assocData, $arr);
        // If we have a record-level spatial reference, then we need to attach the record to a subsample to capture the
        // exact sref.
        if ($sref) {
          if (!isset($subModels[$sref])) {
            $subSample = array(
              'website_id' => $website_id,
              'survey_id' => empty($arr['survey_id']) ? '' : $arr['survey_id'],
              'date' => empty($arr['sample:date']) ? '' : $arr['sample:date'],
              'entered_sref_system' => empty($arr['sample:entered_sref_system']) ? '' : $arr['sample:entered_sref_system'],
              'location_name' => empty($arr['sample:location_name']) ? '' : $arr['sample:location_name'],
              'input_form' => empty($arr['sample:input_form']) ? '' : $arr['sample:input_form'],
              'entered_sref' => $sref
            );
            // set an existing ID on the sample if editing
            if (!empty($existingSampleIdsBySref[strtoupper(trim($sref))])) {
              $subSample['id'] = $existingSampleIdsBySref[strtoupper(trim($sref))];
              if ($key = array_search($subSample['id'], $unusedExistingSampleIds))
                unset($unusedExistingSampleIds[$key]);
            }
            $subModels[$sref] = array(
              'fkId' => 'parent_id',
              'model' => data_entry_helper::wrap($subSample, 'sample'),
            );
            $subModels[$sref]['model']['subModels'] = array();
          }
          $subModels[$sref]['model']['subModels'][] = array(
            'fkId' => 'sample_id',
            'model' => $occ
          );
        } else {
          $subModels[$id] = array(
            'fkId' => 'sample_id',
            'model' => $occ
          );
        }
      }
    }
    // Flag any old samples for deletion that are now empty
    foreach ($unusedExistingSampleIds as $id) {
      $subModels[$sref]['model']['subModels'][] = array(
        'fkId' => 'parent_id',
        'model' => data_entry_helper::wrap(array(
          'id' => $id,
          'website_id' => $website_id,
          'deleted' => 't'
        ), 'sample'),
      );
    }
    return $subModels;
  }

  /**
   * Wraps data from a species checklist grid with subsamples (generated by
   * data_entry_helper::species_checklist) into a suitable format for submission. This will
   * return an array of submodel entries which can be dropped directly into the subModel
   * section of the submission array. If there is a field occurrence:determiner_id or
   * occurrence:record_status in the main form data, then these values are applied to each
   * occurrence created from the grid. For example, place a hidden field in the form named
   * "occurrence:record_status" with a value "C" to set all occurrence records to completed
   * as soon as they are entered.
   *
   * @param array $arr Array of data generated by data_entry_helper::species_checklist method.
   * @param boolean $include_if_any_data If true, then any list entry which has any data
   * set will be included in the submission. This defaults to false, unless the grid was
   * created with rowInclusionCheck=hasData in the grid options.
   * @param array $zero_attrs Set to an array of attribute defs keyed by attribute ID that can be
   * treated as abundances. Alternatively set to true to treat all occurrence custom attributes
   * as possible zero abundance indicators.
   * @param array $zero_values Set to an array of values which are considered to indicate a
   * zero abundance record if found for one of the zero_attrs. Values are case-insensitive. Defaults to
   * array('0','None','Absent').
   * @param array Array of grid ids to ignore when building sub-samples for occurrences, useful for creating
   * customised submissions that only need to build sub-samples for some grids. The grid id comes from the @id option given
   * to the species grid.
   */
    public static function wrap_species_checklist_with_subsamples($arr, $include_if_any_data=false,
          $zero_attrs = true, $zero_values=array('0','None','Absent'), $gridsToExclude=array()){
    if (array_key_exists('website_id', $arr)){
      $website_id = $arr['website_id'];
    } else {
      throw new Exception('Cannot find website id in POST array!');
    }
    $fieldDefaults = self::speciesChecklistGetFieldDefaults($arr);
    // Set the default method of looking for rows to include - either using data, or the checkbox (which could be hidden)
    $include_if_any_data = $include_if_any_data || (isset($arr['rowInclusionCheck']) && $arr['rowInclusionCheck']=='hasData');
    // Species checklist entries take the following format.
    // sc:<subsampleIndex>:[<sample_id>]:sample:deleted
    // sc:<subsampleIndex>:[<sample_id>]:sample:geom
    // sc:<subsampleIndex>:[<sample_id>]:sample:entered_sref
    // sc:<subsampleIndex>:[<sample_id>]:smpAttr:[<sample_attribute_id>]
    // sc:<rowIndex>:[<occurrence_id>]:occurrence:sampleIDX (val set to subsample index)
    // sc:<rowIndex>:[<occurrence_id>]:present (checkbox with val set to ttl_id
    // sc:<rowIndex>:[<occurrence_id>]:occAttr:<occurrence_attribute_id>[:<occurrence_attribute_value_id>]
    // sc:<rowIndex>:[<occurrence_id>]:occurrence:comment
    // sc:<rowIndex>:[<occurrence_id>]:occurrence_medium:fieldname:uniqueImageId
    $occurrenceRecords = array();
    $sampleRecords = array();
    $subModels = array();
    foreach ($arr as $key=>$value){
      $gridExcluded=false;
      foreach ($gridsToExclude as $gridToExclude) {
        if (substr($key, 0, strlen($gridToExclude)+3)=='sc:'.$gridToExclude) {
          $gridExcluded=true;
        }
      }
      if ($gridExcluded===false && substr($key, 0, 3)=='sc:' && substr($key, 2, 7)!=':-idx-:' && substr($key, 2, 3)!=':n:'){ //discard the hidden cloneable rows
        // Don't explode the last element for occurrence attributes
        $a = explode(':', $key, 4);
        $b = explode(':', $a[3], 2);
        if($b[0] == "sample" || $b[0] == "smpAttr"){
          $sampleRecords[$a[1]][$a[3]] = $value;
          if($a[2]) $sampleRecords[$a[1]]['id'] = $a[2];
        } else {
          $occurrenceRecords[$a[1]][$a[3]] = $value;
          if($a[2]) $occurrenceRecords[$a[1]]['id'] = $a[2];
        }
      }
    }
    foreach ($sampleRecords as $id => $sampleRecord) {
      $sampleRecords[$id]['occurrences'] = array();
    }
    foreach ($occurrenceRecords as $id => $record) {
      $sampleIDX = $record['occurrence:sampleIDX'];
      unset($record['occurrence:sampleIDX']);
      $present = self::wrap_species_checklist_record_present($record, $include_if_any_data,
        $zero_attrs, $zero_values, array());
      if (array_key_exists('id', $record) || $present!==null) { // must always handle row if already present in the db
        if ($present===null)
          // checkboxes do not appear if not checked. If uncheck, delete record.
          $record['deleted'] = 't';
        else
          $record['zero_abundance']=$present ? 'f' : 't';
        $record['taxa_taxon_list_id'] = $record['present'];
        $record['website_id'] = $website_id;
        self::speciesChecklistApplyFieldDefaults($fieldDefaults, $record);
        $occ = data_entry_helper::wrap($record, 'occurrence');
        self::attachOccurrenceMediaToModel($occ, $record);
        $sampleRecords[$sampleIDX]['occurrences'][] = array('fkId' => 'sample_id','model' => $occ);
      }
    }
    foreach ($sampleRecords as $id => $sampleRecord) {
      $occs = $sampleRecord['occurrences'];
      unset($sampleRecord['occurrences']);
      $sampleRecord['website_id'] = $website_id;
      // copy essentials down to each subsample
      if (!empty($arr['survey_id']))
        $sampleRecord['survey_id'] = $arr['survey_id'];
      if (!empty($arr['sample:date']))
        $sampleRecord['date'] = $arr['sample:date'];
      if (!empty($arr['sample:entered_sref_system']))
        $sampleRecord['entered_sref_system'] = $arr['sample:entered_sref_system'];
      if (!empty($arr['sample:location_name']) && empty($sampleRecord['location_name']))
        $sampleRecord['location_name'] = $arr['sample:location_name'];
      if (!empty($arr['sample:input_form']))
        $sampleRecord['input_form'] = $arr['sample:input_form'];
      $subSample = data_entry_helper::wrap($sampleRecord, 'sample');
      // Add the subsample/soccurrences in as subModels without overwriting others such as a sample image
      if (array_key_exists('subModels', $subSample)) {
        $subSample['subModels'] = array_merge($subSample['subModels'], $occs);
      } else {
        $subSample['subModels'] = $occs;
      }
      $subModel = array('fkId' => 'parent_id', 'model' => $subSample);
      $copyFields = array();
      if(!isset($sampleRecord['date'])) $copyFields = array('date_start'=>'date_start','date_end'=>'date_end','date_type'=>'date_type');
      if(!isset($sampleRecord['survey_id'])) $copyFields['survey_id'] = 'survey_id';
      if(count($copyFields)>0) $subModel['copyFields'] = $copyFields; // from parent->to child
      $subModels[] = $subModel;
    }
    return $subModels;
  }

  /**
   * Test whether the data extracted from the $_POST for a species_checklist grid row refers to an occurrence record.
   * @param array $record Record submission array from the form post.
   * @param boolean $includeIfAnyData If set, then records are automatically created if any of the custom
   * attributes are filled in.
   * @param mixed $zeroAttrs Optional array of attribute defs keyed by attribute ID to restrict checks for
   * zero abundance records to or pass true to check all attributes. Any lookup attributes must also have a
   * terms key, containing an array of the lookup's terms (each having at least an id and term key).
   * @param array $zeroValues Array of values to consider as zero, which might include localisations of words
   * such as "absent" and "zero" as well as "0".
   * @param array $hasDataIgnoreAttrs Array or attribute IDs to ignore when checking if record is present.
   * @access Private
   * @return boolean True if present, false if absent (zero abundance record), null if not defined in the data (no occurrence).
   */
  public static function wrap_species_checklist_record_present($record, $includeIfAnyData, $zeroAttrs, $zeroValues, $hasDataIgnoreAttrs) {
    // present should contain the ttl ID, or zero if the present box was unchecked
    $gotTtlId=array_key_exists('present', $record) && $record['present']!='0';
    // as we are working on a copy of the record, discard the ID and taxa_taxon_list_id so it is easy to check if there is any other data for the row.
    unset($record['id']);
    unset($record['present']); // stores ttl id
    // also discard any attributes we included in $hasDataIgnoreAttrs
    foreach ($hasDataIgnoreAttrs as $attrID) {
      unset($record['occAttr:' . $attrID]);
    }
    // if zero attrs not an empty array, we must proceed to check for zeros
    if ($zeroAttrs) {
      // check for zero abundance records. First build a regexp that will match the attr IDs to check. Attrs can be
      // just set to true, which means any attr will do.
      if (is_array($zeroAttrs))
        $ids = implode('|', array_keys($zeroAttrs));
      else
        $ids = '\d+';
      $zeroCount=0;
      $nonZeroCount=0;
      foreach ($record as $field=>$value) {
        // Is this a field used to trap zero abundance data, with a zero value
        if ($value!=='' && preg_match("/occAttr:(?P<attrId>$ids)(:\d+)?$/", $field, $matches)) {
          $attr = $zeroAttrs[$matches['attrId']];
          if ($attr['data_type']==='L') {
            foreach ($attr['terms'] as $term) {
              if ($term['id']==$value) {
                $value = $term['term'];
                break;
              }
            }
          }
          if (in_array($value, $zeroValues))
            $zeroCount++;
          else
            $nonZeroCount++;
        }
      }
      // return false (zero) if there are no non-zero abundance data, and at least one zero abundance indicators
      if ($zeroCount && !$nonZeroCount)
        return false;
      elseif (!$zeroCount && !$nonZeroCount && $includeIfAnyData)
        return null;
    }
    //We need to implode the individual field if the field itself is an array (multi-value attributes will be an array).
    foreach ($record as &$recordField) {
      if (is_array($recordField))
        $recordField = implode('',$recordField);
    }
    $recordData=implode('',$record);
    $record = ($includeIfAnyData && $recordData!='' && !preg_match("/^[0]*$/", $recordData)) ||       // inclusion of record is detected from having a non-zero value in any cell
      (!$includeIfAnyData && $gotTtlId); // inclusion of record detected from the presence checkbox
    // return null if no record to create
    return $record ? true : null;
  }

  /**
   * Some occurrence values (e.g. record_status) can be supplied as if being supplied for a single occurrence record
   * and these values will then be applied as defaults to the entire list of occurrences being created by the
   * species_checklist control. This method finds the values to use as defaults in the array of input values.
   * @param array $values List of form values
   * @return array List of defaults to apply to every occurrence
   */
  private static function speciesChecklistGetFieldDefaults($values) {
    // Determiner, training, sensitivity_precision and record status can have their defaults defined as occurrence:...
    // values that get copied into every individual occurrence.
    $fieldsThatAllowDefaults = array(
      'determiner_id', 'training', 'record_status', 'release_status', 'sensitivity_precision'
    );
    $fieldDefaults = array();
    foreach ($fieldsThatAllowDefaults as $field)
      if (array_key_exists("occurrence:$field", $values))
        $fieldDefaults[$field] = $values["occurrence:$field"];
    return $fieldDefaults;
  }

  private static function speciesChecklistApplyFieldDefaults($fieldDefaults, &$record) {
    // Apply default field values but don't overwrite settings for existing records.
    if (empty($record['id'])) {
      foreach ($fieldDefaults as $field => $value)
        $record[$field] = $value;
    }
  }

  /**
   * Return the HTML for the td element which allows a spatial ref to be entered seperately for each row in a
   * species checklist grid.
   * @param $options array Options passed to the control
   * @param $colIdx integer Index of the column position allowing the td to be linked to its header
   * @param $rowIdx integer Index of the grid row
   * @param $existingRecordId integer If an existing occurrence record, pass the ID
   * @return string HTML to insert into the grid
   */
  private static function speciesChecklistSpatialRefPerRowCell($options, $colIdx, $rowIdx, $existingRecordId) {
    $r = '';
    if ($options['spatialRefPerRow']) {
      $r .= "\n<td class=\"ui-widget-content scSpatialRefCell\" headers=\"$options[id]-spatialref-$colIdx\">";
      $fieldname = "sc:$options[id]-$rowIdx:$existingRecordId:occurrence:spatialref";
      $value = '';
      if (isset(self::$entity_to_load['sample:id']) &&
          isset(self::$entity_to_load["sc:$rowIdx:$existingRecordId:occurrence:sampleIDX"])) {
        $sampleIdx = self::$entity_to_load["sc:$rowIdx:$existingRecordId:occurrence:sampleIDX"];
        $keys = preg_grep("/^sc:$sampleIdx:\d+:sample:id$/", array_keys(self::$entity_to_load));
        if (count($keys)) {
          $key = array_pop($keys);
          $srefKey = preg_replace('/:id$/', ':entered_sref', $key);
          if (isset(self::$entity_to_load[$srefKey])) {
            $value = self::$entity_to_load[$srefKey];
          }
        }
      }
      $r .= "<input class=\"scSpatialRef\" type=\"text\" name=\"$fieldname\" id=\"$fieldname\" value=\"$value\" />";
      $r .= "</td>";
    }
    return $r;
  }

  /**
   * Return the HTML for the td element which allows a comment to be entered for each row in a species checklist grid.
   * @param $options array Options passed to the control
   * @param $colIdx integer Index of the column position allowing the td to be linked to its header
   * @param $rowIdx integer Index of the grid row
   * @param $loadedTxIdx integer
   * @param $existingRecordId integer If an existing occurrence record, pass the ID
   * @return string HTML to insert into the grid
   */
  private static function speciesChecklistCommentCell($options, $colIdx, $rowIdx, $loadedTxIdx, $existingRecordId) {
    $r = '';
    if ($options['occurrenceComment']) {
      $r .= "\n<td class=\"ui-widget-content scCommentCell\" headers=\"$options[id]-comment-$colIdx\">";
      $fieldname = "sc:$options[id]-$rowIdx:$existingRecordId:occurrence:comment";
      $value = isset(self::$entity_to_load["sc:$loadedTxIdx:$existingRecordId:occurrence:comment"]) ?
        self::$entity_to_load["sc:$loadedTxIdx:$existingRecordId:occurrence:comment"] : '';
      $r .= "<input class=\"scComment\" type=\"text\" name=\"$fieldname\" id=\"$fieldname\" value=\"$value\" />";
      $r .= "</td>";
    }
    return $r;
  }

  /**
   * Return the HTML for the td element which allows sensitivity to be set for each row in a species checklist grid.
   * @param $options array Options passed to the control
   * @param $colIdx integer Index of the column position allowing the td to be linked to its header
   * @param $rowIdx integer Index of the grid row
   * @param $existingRecordId integer If an existing occurrence record, pass the ID
   * @return string HTML to insert into the grid
   */
  private static function speciesChecklistSensitivityCell($options, $colIdx, $rowIdx, $existingRecordId) {
    $r = '';
    $sensitivityCtrl = '';
    $fieldname = "sc:$options[id]-$rowIdx:$existingRecordId:occurrence:sensitivity_precision";
    $fieldnameInEntity = "sc:$rowIdx:$existingRecordId:occurrence:sensitivity_precision";
    $value = empty(self::$entity_to_load[$fieldnameInEntity]) ? '' : self::$entity_to_load[$fieldnameInEntity];
    if ($options['occurrenceSensitivity'] === true) {
      $sensitivityCtrl = self::select(array(
          'fieldname' => $fieldname,
          'class' => 'scSensitivity',
          'lookupValues' => array('100'=>lang::get('Blur to 100m'), '1000'=>lang::get('Blur to 1km'), '2000'=>lang::get('Blur to 2km'),
            '10000'=>lang::get('Blur to 10km'), '100000'=>lang::get('Blur to 100km')),
          'blankText' => 'Not sensitive',
          'default' => $value ? $value : false
      ));
    } elseif  (preg_match('/\d+/', $options['occurrenceSensitivity'])) {
      // If outputting a checkbox, an existing value overrides the chosen precision for the checkbox.
      $blur = empty($value) ? $options['occurrenceSensitivity'] : $value;
      $checked = empty(self::$entity_to_load[$fieldnameInEntity]) ? '' : ' checked="checked"';
      $sensitivityCtrl = "<input type=\"checkbox\" name=\"$fieldname\" value=\"$blur\"$checked>";
    }
    $r .= '<td class="ui-widget-content scSensitivityCell" headers="'.$options['id']."-sensitivity-$colIdx\">" .
      $sensitivityCtrl . '</td>';
    return $r;
  }

  /**
   * When the species_checklist grid is in spatialRefPerRow mode and editing existing records, this method outputs any
   * existing subsample IDs into an array keyed by spatial ref, so they can be looked up and used in the submission
   * later. It also outputs geoms into an array keyed by sample ID so they can be drawn on the map.
   * @param array $options Options passed to the species_checklist control.
   * @return string HTML for a hidden input containing the existing sample data.
   */
  private static function speciesChecklistSrefPerRowExistingIds($options) {
    $r = '';
    if ($options['spatialRefPerRow'] && !empty(self::$entity_to_load)) {
      $keys = preg_grep("/^sc:\d+:\d+:sample:id$/", array_keys(self::$entity_to_load));
      $data = array();
      $geomsData = array();
      foreach ($keys as $key) {
        $srefKey = preg_replace('/:id$/', ':entered_sref', $key);
        $sref = strtoupper(self::$entity_to_load[$srefKey]);
        $data[$sref] = self::$entity_to_load[$key];
        $geomKey = preg_replace('/:id$/', ':geom', $key);
        $geom = self::$entity_to_load[$geomKey];
        $geomsData[$sref] = $geom;
      }
      $value = htmlspecialchars(json_encode($data));
      $r .= "<input type=\"hidden\" name=\"existingSampleIdsBySref\" value=\"$value\" />";
      $geomsValue = htmlspecialchars(json_encode($geomsData));
      $r .= "<input type=\"hidden\" id=\"existingSampleGeomsBySref\" value=\"$geomsValue\" />";
    }
    return $r;
  }

  private static function attachAssociationsToModel($id, &$occ, $assocData, $arr) {
    $assocs = preg_grep("/^$id$/", $assocData);
    foreach (array_keys($assocs) as $fromRecordKey) {
      // This species record has an association defined to another species record.
      // Get all the fields which define this association
      $fields = array();
      $regexp = preg_replace('/from_occurrence_id$/', '', $fromRecordKey);
      $associationDataKeys = preg_grep("/^$regexp/", array_keys($arr));
      foreach ($associationDataKeys as $thisKey) {
        if ($thisKey!==$fromRecordKey) {
          $value = $arr[$thisKey];
          // to_occurrence_id is a pointer to a key holding an object in the submission. We can't know the
          // final database ID at this point, so mark it up specially for ORM to do this on the server side.
          if (preg_match('/to_occurrence_id$/', $thisKey))
            $value = "||$value||";
          $fields[preg_replace("/^$regexp/", '', $thisKey)] = $value;
        }
      }
      // The existing record ID will be the 3rd segment in the key name.
      $keyParts = explode(':', $fromRecordKey);
      if (!empty($keyParts[2])) {
        $fields['id'] = $keyParts[2];
      }
      // Add a submodel to link them.
      if (!isset($occ['subModels'])) {
        $occ['subModels'] = array();
      }
      $occ['subModels'][] = array(
        'fkId' => 'from_occurrence_id',
        'model' => array(
          'id' => 'occurrence_association',
          'fields' => $fields
        )
      );
    }
  }

  /**
   * When wrapping a species checklist submission, scan the contents of the data for a single grid row to
   * look for attached images. If found they are attached to the occurrence model as sub-models.
   * @param array $occ Occurrence submission structure.
   * @param array $record Record information from the form post, which may contain images.
   */
  public static function attachOccurrenceMediaToModel(&$occ, $record) {
    $media = array();
    foreach ($record as $key=>$value) {
      // look for occurrence media model, or occurrence image for legacy reasons
      if (substr($key, 0, 18)==='occurrence_medium:' || substr($key, 0, 17)=='occurrence_medium:') {
        $tokens = explode(':', $key);
        // build an array of the data keyed by the unique image id (token 2)
        $media[$tokens[2]][$tokens[1]] = array('value' => $value);
      }
    }
    foreach($media as $item => $data) {
      $occ['subModels'][] = array(
        'fkId' => 'occurrence_id',
        'model' => array(
          'id' => 'occurrence_medium',
          'fields' => $data
        )
      );
    }
  }

  /**
   * Wraps an array (e.g. Post or Session data generated by a form) into a structure
   * suitable for submission.
   * <p>The attributes in the array are all included, unless they
   * named using the form entity:attribute (e.g. sample:date) in which case they are
   * only included if wrapping the matching entity. This allows the content of the wrap
   * to be limited to only the appropriate information.</p>
   * <p>Do not prefix the survey_id or website_id attributes being posted with an entity
   * name as these IDs are used by Indicia for all entities.</p>
   *
   * @param array $array Array of data generated from data entry controls.
   * @param string $entity Name of the entity to wrap data for.
   */
  public static function wrap($array, $entity)
  {
    return submission_builder::wrap($array, $entity);
  }

  /**
   * Wraps a set of values for a model into JSON suitable for submission to the Indicia data services,
   * and also grabs the custom attributes (if there are any) and links them to the model.
   *
   * @param array $values Array of form data (e.g. $_POST).
   * @param string $modelName Name of the model to wrap data for. If this is sample, occurrence or location
   * then custom attributes will also be wrapped. Furthermore, any attribute called $modelName:image can
   * contain an image upload (as long as a suitable entity is available to store the image in).
   * @deprecated
   */
  public static function wrap_with_attrs($values, $modelName) {
    return submission_builder::wrap_with_attrs($values, $modelName);
  }

  /**
   * Helper function to simplify building of a submission that contains a single sample
   * and occurrence record.
   * @param array $values List of the posted values to create the submission from. Each entry's
   * key should be occurrence:fieldname, sample:fieldname, occAttr:n, smpAttr:n, taxAttr:n
   * or psnAttr:n to be correctly identified.
   */
  public static function build_sample_occurrence_submission($values) {
    $structure = array(
      'model' => 'sample',
      'subModels' => array(
        'occurrence' => array('fk' => 'sample_id')
      )
    );
    // Either an uploadable file, or a link to an external detail means include the submodel
    if (!empty($values['occurrence:image']) || !empty($values['occurrence_medium:external_details'])) {
      $structure['subModels']['occurrence']['subModels'] = array(
        'occurrence_medium' => array('fk' => 'occurrence_id')
      );
    }
    return submission_builder::build_submission($values, $structure);
  }

  /**
   * Helper function to simplify building of a submission that contains a single sample
   * and multiple occurrences records generated by a species_checklist control.
   *
   * @param array $values List of the posted values to create the submission from.
   * @param boolean $include_if_any_data If true, then any list entry which has any data
   * set will be included in the submission. Set this to true when hiding the select checkbox
   * in the grid.
   * @param array $zero_attrs Set to an array of attribute defs keyed by attribute ID that can be
   * treated as abundances. Alternatively set to true to treat all occurrence custom attributes
   * as possible zero abundance indicators.
   * @param array $zero_values Set to an array of values which are considered to indicate a
   * zero abundance record if found for one of the zero_attrs. Values are case-insensitive. Defaults to
   * array('0','None','Absent').
   * of values that can be treated as meaning a zero abundance record. E.g.
   * array('

   * @return array Sample submission array
   */
  public static function build_sample_occurrences_list_submission($values, $include_if_any_data=false,
                                                                  $zero_attrs = true, $zero_values=array('0','None','Absent')) {
    // We're mainly submitting to the sample model
    $sampleMod = submission_builder::wrap_with_images($values, 'sample');
    $occurrences = data_entry_helper::wrap_species_checklist($values, $include_if_any_data,
      $zero_attrs, $zero_values);

    // Add the occurrences in as subModels without overwriting others such as a sample image
    if (array_key_exists('subModels', $sampleMod)) {
      $sampleMod['subModels'] = array_merge($sampleMod['subModels'], $occurrences);
    } else {
      $sampleMod['subModels'] = $occurrences;
    }

    return $sampleMod;
  }

  /**
   * Helper function to simplify building of a submission that contains a single supersample,
   * with multiple subsamples, each of which has multiple occurrences records, as generated
   * by a species_checklist control.
   *
   * @param array $values List of the posted values to create the submission from.
   * @param boolean $include_if_any_data If true, then any list entry which has any data
   * set will be included in the submission. Set this to true when hiding the select checkbox
   * in the grid.
   * @param array $zero_attrs Set to an array of attribute defs keyed by attribute ID that can be
   * treated as abundances. Alternatively set to true to treat all occurrence custom attributes
   * as possible zero abundance indicators.
   * @param array $zero_values Set to an array of values which are considered to indicate a
   * zero abundance record if found for one of the zero_attrs. Values are case-insensitive. Defaults to
   * array('0','None','Absent').
   * of values that can be treated as meaning a zero abundance record. E.g.
   * array('

   * @return array Sample submission array
   */
  public static function build_sample_subsamples_occurrences_submission($values, $include_if_any_data=false,
                                                                        $zero_attrs = true, $zero_values=array('0','None','Absent'))
  {
    // We're mainly submitting to the sample model
    $sampleMod = submission_builder::wrap_with_images($values, 'sample');
    $subModels = data_entry_helper::wrap_species_checklist_with_subsamples($values, $include_if_any_data,
      $zero_attrs, $zero_values);

    // Add the subsamples/occurrences in as subModels without overwriting others such as a sample image
    if (array_key_exists('subModels', $sampleMod)) {
      $sampleMod['subModels'] = array_merge($sampleMod['subModels'], $subModels);
    } else {
      $sampleMod['subModels'] = $subModels;
    }

    return $sampleMod;
  }

  /**
   * Helper function to simplify building of a submission. Does simple submissions that do not involve
   * species checklist grids.
   * @param array $values List of the posted values to create the submission from.
   * @param array $structure Describes the structure of the submission. The form should be:
   * array(
   *     'model' => 'main model name',
   *     'subModels' => array('child model name' =>  array(
   *         'fieldPrefix'=>'Optional prefix for HTML form fields in the sub model. If not specified then the sub model name is used.',
   *         'fk' => 'foreign key name'
   *     )),
   *     'superModels' => array('child model name' =>  array(
   *         'fieldPrefix'=>'Optional prefix for HTML form fields in the sub model. If not specified then the sub model name is used.',
   *         'fk' => 'foreign key name'
   *     )),
   *     'metaFields' => array('fieldname1', 'fieldname2', ...)
   * )
   */
  public static function build_submission($values, $structure) {
    return submission_builder::build_submission($values, $structure);
  }

  /**
   * Takes a response from a call to forward_post_to() and outputs any errors from it onto the screen.
   *
   * @param string $response Return value from a call to forward_post_to().
   * @param boolean $inline Set to true if the errors are to be placed alongside the controls rather than at the top of the page.
   * Default is true.
   * @see forward_post_to()
   * @link http://code.google.com/p/indicia/wiki/TutorialBuildingBasicPage#Build_a_data_entry_page
   */
  public static function dump_errors($response, $inline=true)
  {
    $r = "";
    if (is_array($response)) {
      // set form mode
      self::$form_mode = 'ERRORS';
      if (array_key_exists('error',$response) || array_key_exists('errors',$response)) {
        if ($inline && array_key_exists('errors',$response)) {
          // Setup an errors array that the data_entry_helper can output alongside the controls
          self::$validation_errors = $response['errors'];
          // And tell the helper to reload the existing data.
          self::$entity_to_load = $_POST;
          if (isset($response['code'])) {
            switch ($response['code']) {
              case 2003: if (function_exists('hostsite_show_message'))
                hostsite_show_message(lang::get('The data could not be saved.'), 'error');
              else
                $r .= "<div class=\"ui-widget ui-corner-all ui-state-highlight page-notice\">" . lang::get('The data could not be saved.') . "</div>\n";
            }
          }
        } else {
          $r .= "<div class=\"ui-state-error ui-corner-all\">\n";
          $r .= "<p>An error occurred when the data was submitted.</p>\n";
          if (is_array($response['error'])) {
            $r .=  "<ul>\n";
            foreach ($response['error'] as $field=>$message)
              $r .=  "<li>$field: $message</li>\n";
            $r .=  "</ul>\n";
          } else {
            $r .= "<p class=\"error_message\">".$response['error']."</p>\n";
          }
          if (array_key_exists('file', $response) && array_key_exists('line', $response)) {
            $r .= "<p>Error occurred in ".$response['file']." at line ".$response['line']."</p>\n";
          }
          if (array_key_exists('errors', $response)) {
            $r .= "<pre>".print_r($response['errors'], true)."</pre>\n";
          }
          if (array_key_exists('trace', $response)) {
            $r .= "<pre>".print_r($response['trace'], true)."</pre>\n";
          }
          $r .= "</div>\n";
        }
      }
      elseif (array_key_exists('warning',$response)) {
        if (function_exists('hostsite_show_message')) {
          hostsite_show_message(lang::get('A warning occurred when the data was submitted.').' '.$response['error'], 'error');
        } else {
          $r .= 'A warning occurred when the data was submitted.';
          $r .= '<p class="error">'.$response['error']."</p>\n";
        }
      }
      elseif (array_key_exists('success',$response)) {
        $successMessage = lang::get('Thank you for submitting your data.');
        if (function_exists('hostsite_show_message'))
          hostsite_show_message($successMessage);
        else
          $r .= "<div class=\"ui-widget ui-corner-all ui-state-highlight page-notice\">" . $successMessage . "</div>\n";
      }
    }
    else
      $r .= "<div class=\"ui-state-error ui-corner-all\">$response</div>\n";
    return $r;
  }

  /**
   * Retrieves any errors that have not been emitted alongside a form control and adds them to the page.
   * This is useful when added to the bottom of a form, because occasionally an error can be returned which is not associated with a form
   * control, so calling dump_errors with the inline option set to true will not emit the errors onto the page.
   * @return string HTML block containing the error information, built by concatenating the
   * validation_message template for each error.
   */
  public static function dump_remaining_errors()
  {
    global $indicia_templates;
    $r="";
    if (self::$validation_errors!==null) {
      foreach (self::$validation_errors as $errorKey => $error) {
        if (!in_array($error, self::$displayed_errors)) {
          $r .= str_replace('{error}', lang::get($error), $indicia_templates['validation_message']);
          $r .= "[".$errorKey."]";
        }
      }
      $r .= '<br/>';
    }
    return $r;
  }

  /**
   * Returns the default value for the control with the supplied Id.
   * The default value is taken as either the $_POST value for this control, or the first of the remaining
   * arguments which contains a non-empty value.
   *
   * @param string $id Id of the control to select the default for.
   * $param [string, [string ...]] Variable list of possible default values. The first that is
   * not empty is used.
   */
  public static function check_default_value($id) {
    $remembered_fields = self::get_remembered_fields();
    if (self::$entity_to_load!=null && array_key_exists($id, self::$entity_to_load)) {
      return self::$entity_to_load[$id];
    } else if ($remembered_fields !== null && in_array($id, $remembered_fields) && array_key_exists('indicia_remembered', $_COOKIE)) {
      $arr = unserialize($_COOKIE['indicia_remembered']);
      if (isset($arr[$id]))
        return $arr[$id];
    }

    $return = null;
    // iterate the variable arguments and use the first one with a real value
    for ($i=1; $i<func_num_args(); $i++) {
      $return = func_get_arg($i);
      if (!is_null($return) && $return != '') {
        break;
      }
    }
    return $return;
  }

  /**
   * Output a DIV which lists configuration problems and is useful for diagnostics.
   * Currently, tests the PHP version and that the cUrl library is installed.
   *
   * @param boolean $fullInfo If true, then successful checks are also output.
   */
  public static function system_check($fullInfo=true) {
    // PHP_VERSION_ID is available as of PHP 5.2.7, if our
    // version is lower than that, then emulate it
    if(!defined('PHP_VERSION_ID'))
    {
      $version = PHP_VERSION;
      define('PHP_VERSION_ID', ($version{0} * 10000 + $version{2} * 100 + $version{4}));
    }
    $r = '<div class="ui-widget ui-widget-content ui-state-highlight ui-corner-all">' .
      '<p class="ui-widget-header"><strong>System check</strong></p><ul>';
    // Test PHP version.
    if (PHP_VERSION_ID<50200) {
      $r .= '<li class="ui-state-error">Warning: PHP version is '.phpversion().' which does not support JSON communication with the Indicia Warehouse.</li>';
    } elseif ($fullInfo) {
      $r .= '<li>Success: PHP version is '.phpversion().'.</li>';
    }
    if (!function_exists('finfo_open')) {
      $r .= '<li class="ui-state-error">Warning: The Fileinfo PHP library is not installed on the server. Although it is not mandatory, installing this module is recommended as it prevents '.
        'upload of malicious files masquerading as image files.</li>';
    } elseif ($fullInfo) {
      $r .= '<li>Success: PHP Fileinfo extension is installed.</li>';
    }
    // Test cUrl library installed
    if (!function_exists('curl_exec')) {
      $r .= '<li class="ui-state-error">Warning: The cUrl PHP library is not installed on the server and is required for communication with the Indicia Warehouse.</li>';
    } else {
      if ($fullInfo) {
        $curlVersionArray = curl_version();
        if (is_array($curlVersionArray)) {
          $curlVersion = $curlVersionArray['version'];
        }
        else {
          $curlVersion = $curlVersionArray;
        }
        $r .= '<li>Success: The cUrl PHP library version ' . $curlVersion . ' is installed.</li>';
      }
      // Test we have full access to the server - it doesn't matter what website id we pass here.'
      $postargs = "website_id=0";
      $curl_check = self::http_post(parent::$base_url.'index.php/services/security/get_read_nonce', $postargs, false);
      if ($curl_check['result']) {
        if ($fullInfo) {
          $r .= '<li>Success: Indicia Warehouse URL responded to a POST request.</li>';
        }
      } else {
        // Some sort of cUrl problem occurred
        if ($curl_check['errno']) {
          $r .= '<li class="ui-state-error">Warning: The cUrl PHP library could not access the Indicia Warehouse. The error was reported as:';
          $r .= $curl_check['output'].'<br/>';
          $r .= 'Please ensure that this web server is not prevented from accessing the server identified by the ' .
            'helper_config.php $base_url setting by a firewall. The current setting is '.parent::$base_url.'</li>';
        } else {
          $r .= '<li class="ui-widget ui-state-error">Warning: A request sent to the Indicia Warehouse URL did not respond as expected. ' .
            'Please ensure that the helper_config.php $base_url setting is correct. ' .
            'The current setting is '.parent::$base_url.'<br></li>';
        }
      }
      $missing_configs = array();
      $blank_configs = array();
      // Run through the expected configuration settings, checking they are present and not empty
      self::check_config('$base_url', isset(self::$base_url), empty(self::$base_url), $missing_configs, $blank_configs);
      // don't test $indicia_upload_path and $interim_image_folder as they are assumed to be upload/ if missing.
      self::check_config('$geoserver_url', isset(self::$geoserver_url), empty(self::$geoserver_url), $missing_configs, $blank_configs);
      if (substr(self::$geoserver_url, 0, 4) != 'http') {
        $r .= '<li class="ui-widget ui-state-error">Warning: The $geoserver_url setting in helper_config.php should include the protocol (e.g. http://).</li>';
      }
      self::check_config('$geoplanet_api_key', isset(self::$geoplanet_api_key), empty(self::$geoplanet_api_key), $missing_configs, $blank_configs);
      self::check_config('$google_api_key', isset(self::$google_api_key), empty(self::$google_api_key), $missing_configs, $blank_configs);
      self::check_config('$bing_api_key', isset(self::$bing_api_key), empty(self::$bing_api_key), $missing_configs, $blank_configs);
      // Warn the user of the missing ones - the important bit.
      if (count($missing_configs)>0) {
        $r .= '<li class="ui-widget ui-state-error">Error: The following configuration entries are missing from helper_config.php : '.
          implode(', ', $missing_configs).'. This may prevent the data_entry_helper class from functioning normally.</li>';
      }
      // Also warn them of blank ones - not so important as it should only affect the one area of functionality
      if (count($blank_configs)>0) {
        $r .= '<li class="ui-widget ui-state-error">Warning: The following configuration entries are not specified in helper_config.php : '.
          implode(', ', $blank_configs).'. This means the respective areas of functionality will not be available.</li>';
      }
      // Test we have a writeable cache directory
      $cacheFolder = parent::$cache_folder ? parent::$cache_folder : self::relative_client_helper_path() . 'cache/';
      if (!is_dir($cacheFolder)) {
        $r .= '<li class="ui-state-error">The cache path setting in helper_config.php points to a missing directory. This will result in slow form loading performance.</li>';
      } elseif (!is_writeable($cacheFolder)) {
        $r .= '<li class="ui-state-error">The cache path setting in helper_config.php points to a read only directory (' . $cacheFolder . '). Please change it to writeable.</li>';
      } else {
        // need a proper test, as is_writeable can report true when the cache file can't be created.
        $handle = @fopen("$cacheFolder/test.txt", 'wb');
        if ($handle) {
          fclose($handle);
          if ($fullInfo)
            $r .= '<li>Success: Cache directory is present and writeable.</li>';
        } else
          $r .= '<li class="ui-state-error">Warning: The cache path setting in helper_config.php points to a directory that I can\'t write a file into (' . $cacheFolder . '). Please change it to writeable.</li>';

      }
      $interim_image_folder = isset(parent::$interim_image_folder) ? parent::$interim_image_folder : 'upload/';
      if (!is_writeable(self::relative_client_helper_path() . $interim_image_folder))
        $r .= '<li class="ui-state-error">The interim_image_folder setting in helper_config.php points to a read only directory (' . $interim_image_folder . '). This will prevent image uploading.</li>';
      elseif ($fullInfo)
        $r .= '<li>Success: Interim image upload directory is writeable.</li>';
    }
    $r .= '</ul></div>';
    return $r;
  }

  /**
   * Checks a configuration setting in the helper_config.php file. If it is missing or blank
   * then it is added to an array so that the caller can decide what to do.
   * @param string $name Name of the configuration parameter.
   * @param boolean $isset Is the parameter set?
   * @param boolean $empty Is the parameter empty?
   * @param array $missing_configs Configuration settings that are missing are added to this array.
   * @param array $blank_configs Configuration settings that are empty are added to this array.
   */
  private static function check_config($name, $isset, $empty, &$missing_configs, &$blank_configs) {
    if (!$isset) {
      array_push($missing_configs, $name);
    } else if ($empty) {
      array_push($blank_configs, $name);
    }
  }

  /**
   * Helper function to fetch details of attributes associated with a survey.
   * This can be used to auto-generated the forum structure for a survey for example.
   *
   * @param array $options Options array with the following possibilities:<ul>
   * <li><b>survey_id</b><br/>
   * Optional. The survey that custom attributes are to be loaded for.</li>
   * <li><b>website_ids</b><br/>
   * Optional. Used instead of survey_id, allows retrieval of all possible custom attributes
   * for a set of websites.</li>
   * <li><b>sample_method_id</b><br/>
   * Optional. Can be set to the id of a sample method when loading just the attributes that are restricted to
   * that sample method or are unrestricted, otherwise only loads unrestricted attributes. Ignored unless
   * loading sample attributes.</li>
   * <li><b>location_type_id</b><br/>
   * Optional. Can be set to the id of a location_type when loading just the attributes that are restricted to
   * that type or are unrestricted, otherwise only loads unrestricted attributes. Ignored unless
   * loading location attributes.</li>
   * <li><b>attrtable</b><br/>
   * Required. Singular name of the table containing the attributes, e.g. sample_attribute.</li>
   * <li><b>valuetable</b><br/>
   * Required. Singular name of the table containing the attribute values, e.g. sample_attribute_value.</li>
   * <li><b>fieldprefix</b><br/>
   * Required. Prefix to be given to the returned control names, e.g. locAttr:</li>
   * <li><b>extraParams</b><br/>
   * Required. Additional parameters used in the web service call, including the read authorisation.</li>
   * <li><b>multiValue</b><br/>
   * Defaults to false, in which case this assumes that each attribute only allows one value, and the response array is keyed
   * by attribute ID. If set to true, multiple values are enabled and the response array is keyed by <attribute ID>:<attribute value ID>
   * in the cases where there is any data for the attribute.
   * </ul>
   * @param optional boolean $indexedArray default true. Determines whether the return value is an array indexed by PK, or whether it
   * is ordered as it comes from the database (ie block weighting). Needs to be set false if data is to be used by get_attribute_html.
   * @param string $sharing Set to verification, peer_review, moderation, data_flow or reporting to indicate the task being performed, if
   * sharing data with other websites. If not set then only data from the current website is available.
   *
   * @return Associative array of attributes, keyed by the attribute ID (multiValue=false) or <attribute ID>:<attribute value ID> if multiValue=true.
   */
  public static function getAttributes($options, $indexedArray = true, $sharing=false) {
    $attrs = array();
    // there is a possiblility that the $options['extraParams'] already features a query entry.
    if(isset($options['extraParams']['query'])) {
      $query = json_decode($options['extraParams']['query'], true);
      unset($options['extraParams']['query']);
      if (!isset($query['in']))
        $query['in']=array();
    }
    else
      $query = array('in'=>array());
    self::add_resource('json');
    if (isset($options['website_ids'])) {
      $query['in']['website_id']=$options['website_ids'];
    } elseif ($options['attrtable']!=='person_attribute') {
      $surveys = array(NULL);
      if (isset($options['survey_id']))
        $surveys[] = $options['survey_id'];
      $query['in']['restrict_to_survey_id']=$surveys;
    }
    if ($options['attrtable']=='sample_attribute') {
      // for sample attributes, we want all which have null in the restrict_to_sample_method_id,
      // or where the supplied sample method matches the attribute's.
      $methods = array(null);
      if (isset($options['sample_method_id']))
        $methods[] = $options['sample_method_id'];
      $query['in']['restrict_to_sample_method_id'] = $methods;
    }
    if ($options['attrtable']=='location_attribute') {
      // for location attributes, we want all which have null in the restrict_to_location_type_id,
      // or where the supplied location type matches the attribute's.
      $methods = array(null);
      if (isset($options['location_type_id']))
        $methods[] = $options['location_type_id'];
      $query['in']['restrict_to_location_type_id'] = $methods;
    }

    $attrOptions = array(
      'table'=>$options['attrtable'],
      'extraParams'=> array_merge(array(
        'deleted' => 'f',
        'website_deleted' => 'f',
        'orderby'=>'weight',
        'query'=>json_encode($query),
      ), $options['extraParams'])
    );
    if ($sharing)
      $attrOptions['sharing'] = $sharing;
    $response = self::get_population_data($attrOptions);
    if (array_key_exists('error', $response))
      return $response;
    if(isset($options['id'])){
      // if an ID is set in the options extra params, then it refers to the attribute table, not the value table.
      unset($options['extraParams']['id']);
      unset($options['extraParams']['query']);
      $options['extraParams'][$options['key']] = $options['id'];
      $existingValuesOptions = array(
        'table'=>$options['valuetable'],
        'cachetimeout' => 0, // can't cache
        'extraParams'=> $options['extraParams']);
      if ($sharing)
        $existingValuesOptions['sharing'] = $sharing;
      $valueResponse = self::get_population_data($existingValuesOptions);
      if (array_key_exists('error', $valueResponse))
        return $valueResponse;
    } else
      $valueResponse = array();
    foreach ($response as $item){
      $itemId=$item['id'];
      unset($item['id']);
      $item['fieldname']=$options['fieldprefix'].':'.$itemId.($item['multi_value'] == 't' ? '[]' : '');
      $item['id']=$options['fieldprefix'].':'.$itemId;
      $item['untranslatedCaption']=$item['caption'];
      $item['caption']=lang::get($item['caption']);
      $item['default'] = self::attributes_get_default($item);
      $item['attributeId'] = $itemId;
      $item['values'] = array();
      if(count($valueResponse) > 0){
        foreach ($valueResponse as $value){
          $attrId = $value[$options['attrtable'].'_id'];
          if($attrId == $itemId && $value['id']) {
            if ($item['data_type']==='D' && isset($value['value']) && preg_match('/^(\d{4})/', $value['value'])) {
              // Date has 4 digit year first (ISO style) - convert date to expected output format
              // Note this only affects the loading of the date itself when the form initially loads, the format displayed as soon as the
              // date picker is selected is determined by Drupal's settings.
              // @todo The date format should be a global configurable option.
              $d = new DateTime($value['value']);
              $value['value'] = $d->format(helper_base::$date_format);
              //If a date, then we default to the value after formatting
              $defaultValue = $value['value'];
            } elseif ($item['data_type']==='V') {
              $defaultValue = $value['value'];
            } else {
              //If not date we need to use the raw_value, items like drop-downs won't reload correctly without this
              $defaultValue = $value['raw_value'];
            }
            // for multilanguage look ups we get > 1 record for the same attribute.
            $fieldname = $options['fieldprefix'].':'.$itemId.':'.$value['id'];
            $found = false;
            foreach ($item['values'] as $prev)
              if($prev['fieldname'] == $fieldname && $prev['default'] == $value['raw_value'])
                $found = true;
            if(!$found)
              $item['values'][] = array('fieldname' => $options['fieldprefix'].':'.$itemId.':'.$value['id'],
                                'default' => $defaultValue, 'caption'=>$value['value']);
            $item['displayValue'] = $value['value']; //bit of a bodge but not using multivalue for this at the moment.
          }
        }
      }
      if(count($item['values'])>=1 && $item['multi_value'] != 't'){
        $item['fieldname'] = $item['values'][0]['fieldname'];
        $item['default'] = $item['values'][0]['default'];
      }
      if($item['multi_value'] == 't'){
        $item['default'] = $item['values'];
      }
      unset($item['values']);
      if($indexedArray)
        $attrs[$itemId] = $item;
      else
        $attrs[] = $item;
    }
    return $attrs;
  }

  /**
   * For a single sample or occurrence attribute array loaded from the database, find the
   * appropriate default value depending on the data type.
   * @param array $item The attribute's definition array.
   * @todo Handle vague dates. At the moment we just use the start date.
   */
  private static function attributes_get_default($item) {
    switch ($item['data_type']) {
      case 'T':
        return $item['default_text_value'];
      case 'F':
        return $item['default_float_value'];
      case 'I':
      case 'L':
      case 'B':
        return $item['default_int_value'];
      case 'D':
      case 'V':
        return $item['default_date_start_value'];
      default:
        return '';
    }
  }

  /**
   * Returns the control required to implement a boolean custom attribute.
   * @param string $ctrl The control type, should be radio or checkbox.
   * @param array $options The control's options array.
   */
  private static function boolean_attribute($ctrl, $options) {
    global $indicia_templates;
    $options = array_merge(
      array(
        'sep' => '',
        'class' => 'control-box'
      ),
      $options
    );
    unset($options['validation']);
    $default = self::check_default_value($options['fieldname'],
      array_key_exists('default', $options) ? $options['default'] : '', '0');
    $options['default'] = $default;
    $options = array_merge(array('sep' => ''), $options);
    if ($options['class']=='') {
      // default class is control-box
      $options['class']='control-box';
    }
    $items = "";
    $buttonList = array(lang::get('No') => '0', lang::get('Yes') => '1');
    $disabled = isset($options['disabled']) ?  $options['disabled'] : '';
    foreach ($buttonList as $caption => $value) {
      $checked = ($default == $value) ? ' checked="checked" ' : '';
      $items .= str_replace(
        array('{type}', '{fieldname}', '{value}', '{checked}', '{caption}', '{sep}', '{disabled}', '{itemId}', '{class}'),
        array($ctrl, $options['fieldname'], $value, $checked, $caption, $options['sep'], $disabled, $options['fieldname'].':'.$value, ''),
        $indicia_templates['check_or_radio_group_item']
      );
    }
    $options['items']=$items;
    $lblTemplate = $indicia_templates['label'];
    $indicia_templates['label'] = str_replace(' for="{id}"', '', $lblTemplate);
    $r = self::apply_template('check_or_radio_group', $options);
    // reset the old template
    $indicia_templates['label'] = $lblTemplate;
    return $r;
  }

  /**
   * Helper function to output an attribute.
   *
   * @param array $item Attribute definition as returned by a call to getAttributes. The caption of the attribute
   * will be translated then output as the label.
   * @param array $options Additional options for the attribute to be output. Array entries can be:
   *    disabled
   *    suffixTemplate
   *    default
   *    class
   *    validation
   *    noBlankText
   *    extraParams
   *    booleanCtrl - radio or checkbox for boolean attribute output, default is checkbox. Can also be a checkbox_group, used to
   *    allow selection of both yes and no, e.g. on a filter form.
   *    language - iso 639:3 code for the language to output for terms in a termlist. If not set no language filter is used.
   * @return string HTML to insert into the page for the control.
   * @todo full handling of the control_type. Only works for text data at the moment.
   */
  public static function outputAttribute($item, $options=array()) {
    if (!empty($item['multi_value']) && $item['multi_value']==='t' && !empty($options['controlCount']) ) {
      // don't need an array field - we will make a unique set of control names instead
      $item['fieldname'] = preg_replace('/\[\]$/', '', $item['fieldname']);
      $r = "<label class=\"auto\">$item[caption]<br/>";
      $origFieldName = empty($item['fieldname']) ? '' : $item['fieldname'];
      $origDefault = empty($item['default']) ? array() : $item['default'];
      for ($i=1; $i<=$options['controlCount']; $i++) {
        $item['caption']=$i;
        // Might need to match to existing attribute values in entity to load here
        if (!empty($origDefault) && isset($origDefault[$i-1]) && is_array($origDefault[$i-1])) {
          $item['fieldname']=$origDefault[$i-1]['fieldname'].':';
          $item['id']=$origDefault[$i-1]['fieldname'].':';
          $item['default']=$origDefault[$i-1]['default'];
        } elseif (preg_match('/^[a-z]+Attr:[\d]+$/', $origFieldName)) {
          // make unique fieldname
          $item['fieldname']="$origFieldName::$i";
          $item['id']="$origFieldName::$i";
          unset($item['default']);
        }
        $r .= self::internalOutputAttribute($item, $options);
      }
      return "$r</label>";
    }
    return self::internalOutputAttribute($item, $options);
  }

  private static function internalOutputAttribute($item, $options) {
    $options = array_merge(array(
      'extraParams' => array()
    ), $options);
    $attrOptions = array(
      'fieldname'=>$item['fieldname'],
      'id'=>$item['id'],
      'disabled'=>'');
    if (isset($item['caption']))
      $attrOptions['label']=$item['caption']; // no need to translate, as that has already been done by getAttributes. Untranslated caption is in field untranslatedCaption
    $attrOptions = array_merge($attrOptions, $options);
    // build validation rule classes from the attribute data
    if (isset($item['validation_rules'])) {
      $validation = explode("\n", $item['validation_rules']);
      $attrOptions['validation']=array_merge(isset($attrOptions['validation'])?$attrOptions['validation']:array(), $validation);
    }
    if(isset($item['default']) && $item['default']!="")
      $attrOptions['default']= $item['default'];
    //the following two lines are a temporary fix to allow a control_type to be specified via the form's user interface form structure
    if(isset($attrOptions['control_type']) && $attrOptions['control_type']!="")
      $item['control_type']= $attrOptions['control_type'];
    unset($ctrl);
    switch ($item['data_type']) {
      case 'Text':
      case 'T':
        if (isset($item['control_type']) &&
          ($item['control_type']=='text_input' || $item['control_type']=='textarea'
            || $item['control_type']=='postcode_textbox' || $item['control_type']=='time_input'
            || $item['control_type']=='hidden_text' || $item['control_type']=='complex_attr_grid' )) {
          $ctrl = $item['control_type'];
        } else {
          $ctrl = 'text_input';
        }
        $output = self::$ctrl($attrOptions);
        break;
      case 'Integer':
      case 'I':
        // We can use integer fields to store the results of custom lookups, e.g. against species or locations...
        if (isset($item['control_type']) &&
          ($item['control_type']=='species_autocomplete' || $item['control_type']=='location_autocomplete')) {
          $ctrl = $item['control_type'];
        }
      // flow through
      case 'Float':
      case 'F':
        if (!isset($ctrl))
          $ctrl='text_input';
        $output = self::$ctrl($attrOptions);
        break;
      case 'Boolean':
      case 'B':
        // A change in template means we can now use a checkbox if desired: in fact this is now the default.
        // Can also use checkboxes (eg for filters where none selected is a possibility) or radio buttons.
        $attrOptions['class'] = array_key_exists('class', $options) ? $options['class'] : 'control-box';
        if(array_key_exists('booleanCtrl', $options) && $options['booleanCtrl']=='radio') {
          $output = self::boolean_attribute('radio', $attrOptions);
        } elseif(array_key_exists('booleanCtrl', $options) && $options['booleanCtrl']=='checkbox_group') {
          $output = self::boolean_attribute('checkbox', $attrOptions);
        } else {
          $output = self::checkbox($attrOptions);
        }
        break;
      case 'D': // Date
      case 'Specific Date': // Date
      case 'V': // Vague Date
      case 'Vague Date': // Vague Date
        if (!empty($attrOptions['displayValue']))
          $attrOptions['default'] = $attrOptions['displayValue'];
        $attrOptions['class'] = ($item['data_type'] == 'D' ? "date-picker " : "vague-date-picker ");
        if (isset($item['validation_rules']) && strpos($item['validation_rules'],'date_in_past')=== false)
          $attrOptions['allowFuture']=true;
        $output = self::date_picker($attrOptions);
        break;
      case 'Lookup List':
      case 'L':
        if(!array_key_exists('noBlankText', $options)){
          $attrOptions = $attrOptions + array('blankText' => (array_key_exists('blankText', $options)? $options['blankText'] : ''));
        }
        if (array_key_exists('class', $options))
          $attrOptions['class'] = $options['class'];
        $dataSvcParams = array('termlist_id' => $item['termlist_id'], 'view' => 'detail');
        if (array_key_exists('language', $options)) {
          $dataSvcParams = $dataSvcParams + array('iso'=>$options['language']);
        }
        if (!array_key_exists('orderby', $options['extraParams'])) {
          $dataSvcParams = $dataSvcParams + array('orderby'=>'sort_order');
        }
        // control for lookup list can be overriden in function call options
        if(array_key_exists('lookUpListCtrl', $options)){
          $ctrl = $options['lookUpListCtrl'];
        } else {
          // or specified by the attribute in survey details
          if (isset($item['control_type']) &&
            ($item['control_type']=='autocomplete' || $item['control_type']=='checkbox_group'
              || $item['control_type']=='listbox' || $item['control_type']=='radio_group' || $item['control_type']=='select'
              || $item['control_type']=='hierarchical_select')) {
            $ctrl = $item['control_type'];
          } else {
            $ctrl = 'select';
          }
        }
        if (isset($item['multi_value']) && $item['multi_value']==='t')
          $attrOptions['multiselect']=true;
        if(array_key_exists('lookUpKey', $options)){
          $lookUpKey = $options['lookUpKey'];
        } else {
          $lookUpKey = 'id';
        }
        $output = "";
        if($ctrl=='checkbox_group' && isset($attrOptions['default'])){
          // special case for checkboxes where there are existing values: have to allow them to save unclicked, so need hidden blank field
          // don't really want to put it in to the main checkbox_group control as don't know what ramifications that would have.
          if (is_array($attrOptions['default'])) {
            $checked = false;
            foreach ($attrOptions['default'] as $defVal) {
              if(is_array($defVal)){
                $output .= '<input type="hidden" value="" name="'.$defVal['fieldname'].'">';
              } // really need the field name, so ignore when not provided
            }
          }
        }
        if($ctrl=='autocomplete' && isset($attrOptions['default'])){
          // two options: we could be using the id or the meaning_id.
          if($lookUpKey=='id'){
            $attrOptions['defaultCaption'] = $item['displayValue'];
          } else {
            $termOptions = array(
              'table'=>'termlists_term',
              'extraParams'=> $options['extraParams'] + $dataSvcParams);
            $termOptions['extraParams']['meaning_id']=$attrOptions['default'];
            $response = self::get_population_data($termOptions);
            if(count($response)>0)
              $attrOptions['defaultCaption'] = $response[0]['term'];
          }
        }
        $output .= call_user_func(array(get_called_class(), $ctrl), array_merge($attrOptions, array(
          'table'=>'termlists_term',
          'captionField'=>'term',
          'valueField'=>$lookUpKey,
          'extraParams' => array_merge($options['extraParams'] + $dataSvcParams))));
        break;
      default:
        if ($item)
          $output = '<strong>UNKNOWN DATA TYPE "'.$item['data_type'].'" FOR ID:'.$item['id'].' CAPTION:'.$item['caption'].'</strong><br />';
        else
          $output = '<strong>Requested attribute is not available</strong><br />';
        break;
    }

    return $output;
  }

  /**
   * Retrieves an array of just the image data from a $_POST or set of control values.
   *
   * @param array $values Pass the $_POST data or other array of form values in this parameter.
   * @param string $modelName The singular name of the media table, e.g. location_medium or occurrence_medium etc. If
   * null, then any image model will be used.
   * @param boolean $simpleFileInputs If true, then allows a file input with name=occurrence:image (or similar)
   * to be used to point to an image file. The file is uploaded to the interim image folder to ensure that it
   * can be handled in the same way as a pre-uploaded file.
   * @param boolean $moveSimpleFiles If true, then any file uploaded by normal means to the server (via multipart form submission
   * for a field named occurrence:image[:n] or similar) will be moved to the interim image upload folder.
   * @param integer If specified, limits media data extraction to media with this media type id.
   */
  public static function extract_media_data($values, $modelName=null, $simpleFileInputs=false, $moveSimpleFiles=false, $mediaTypeIdToExtract=null) {
    $r = array();
    // legacy reasons, the model name might refer to _image model, rather than _medium.
    $modelName = preg_replace('/^([a-z_]*)_image/', '${1}_medium', $modelName);
    $legacyModelName = preg_replace('/^([a-z_]*)_medium/', '${1}_image', $modelName);
    foreach ($values as $key => $value) {
      if (!empty($value)) {
        // If the field is a path, and the model name matches or we are not filtering on model name
        $pathPos = strpos($key, ':path:');
        if ($pathPos !== false)
          // Found an image path. Anything after path is the unique id. We include the colon in this.
          $uniqueId = substr($key, $pathPos + 5);
        else {
          // look for a :path field with no suffix (i.e. a single image upload field after a validation failure,
          // when it stores the path in a hidden field so it is not lost).
          if (substr($key, -5)==':path') {
            $uniqueId = '';
            $pathPos = strlen($key)-5;
          }
        }
        if ($pathPos !==false && ($modelName === null || $modelName == substr($key, 0, strlen($modelName)) ||
            $legacyModelName == substr($key, 0, strlen($legacyModelName)))) {
          $prefix = substr($key, 0, $pathPos);
          $thisMediaTypeId=isset($values[$prefix.':media_type_id'.$uniqueId]) ? utf8_encode($values[$prefix.':media_type_id'.$uniqueId]) : '';
          //Only extract the media if we are extracting media of any type or the data matches the type we are wanting to extract
          if ($thisMediaTypeId==$mediaTypeIdToExtract||$mediaTypeIdToExtract===null) {
            $mediaValues = array(
              // Id is set only when saving over an existing record.
              'id' => array_key_exists($prefix.':id'.$uniqueId, $values) ?
                  $values[$prefix.':id'.$uniqueId] : '',
              'path' => $value,
              'caption' => isset($values[$prefix.':caption'.$uniqueId]) ?
                  utf8_encode($values[$prefix.':caption'.$uniqueId]) : ''
            );
            if (!empty($thisMediaTypeId)) {
              $mediaValues['media_type_id'] = $thisMediaTypeId;
              $mediaValues['media_type'] = isset($values[$prefix . ':media_type' . $uniqueId]) ?
                utf8_encode($values[$prefix . ':media_type' . $uniqueId]) : '';
            }
            // if deleted = 't', add it to array so image is marked deleted
            if (isset($values[$prefix.':deleted'.$uniqueId]) && $values[$prefix.':deleted'.$uniqueId]==='t') {
              $mediaValues['deleted'] = 't';
            }
            $r[] = $mediaValues;
          }
        }
      }
    }

    // Now look for image file inputs, called something like occurrence:medium[:n]
    if ($simpleFileInputs) {
      foreach($_FILES as $key => $file) {
        if (substr($key, 0, strlen($modelName))==str_replace('_', ':', $modelName)
          || substr($key, 0, strlen($legacyModelName))==str_replace('_', ':', $legacyModelName)) {
          if ($file['error']=='1') {
            // file too big error dur to php.ini setting
            if (self::$validation_errors==null) self::$validation_errors = array();
            self::$validation_errors[$key] = lang::get('file too big for webserver');
          }
          elseif (!self::check_upload_size($file)) {
            // even if file uploads Ok to interim location, the Warehouse may still block it.
            if (self::$validation_errors==null) self::$validation_errors = array();
            self::$validation_errors[$key] = lang::get('file too big for warehouse');
          }
          elseif ($file['error']=='0') {
            // no file upload error
            $fname = isset($file['tmp_name']) ? $file['tmp_name'] : '';
            if ($fname && $moveSimpleFiles) {
              // Get the original file's extension
              $parts = explode(".",$file['name']);
              $fext = array_pop($parts);
              // Generate a file id to store the image as
              $destination = time().rand(0,1000).".".$fext;
              $interim_image_folder = isset(parent::$interim_image_folder) ? parent::$interim_image_folder : 'upload/';
              $uploadpath = self::relative_client_helper_path().$interim_image_folder;
              if (move_uploaded_file($fname, $uploadpath.$destination)) {
                $r[] = array(
                  // Id is set only when saving over an existing record. This will always be a new record
                  'id' => '',
                  'path' => $destination,
                  'caption' => ''
                );
                // record the new file name, also note it in the $_POST data so it can be tracked after a validation failure
                $_FILES[$key]['name'] = $destination;
                $pathField = str_replace(array(':medium',':image'),array('_medium:path','_image:path'), $key);
                $_POST[$pathField] = $destination;
              }
            } else {
              // Not moving the file, as it should already be moved.
              $r[] = array(
                // Id is set only when saving over an existing record. This will always be a new record
                'id' => '',
                // This should be a file already in the interim image upload folder.
                'path' => $_FILES[$key]['name'],
                'caption' => ''
              );
            }
          }
        }
      }
    }
    return $r;
  }

  /**
   * Validation rule to test if an uploaded file is allowed by file size.
   * File sizes are obtained from the helper_config maxUploadSize, and defined as:
   * SB, where S is the size (1, 15, 300, etc) and
   * B is the byte modifier: (B)ytes, (K)ilobytes, (M)egabytes, (G)igabytes.
   * Eg: to limit the size to 1MB or less, you would use "1M".
   *
   * @param array $file Item from the $_FILES array.
   * @return bool True if the file size is acceptable, otherwise false.
   */
  public static function check_upload_size(array $file)
  {
    if ((int) $file['error'] !== UPLOAD_ERR_OK)
      return TRUE;

    if (isset(parent::$maxUploadSize))
      $size = parent::$maxUploadSize;
    else
      $size = '4M'; // default

    if ( ! preg_match('/[0-9]++[BKMG]/', $size))
      return FALSE;

    $size = self::convert_to_bytes($size);

    // Test that the file is under or equal to the max size
    return ($file['size'] <= $size);
  }

  /**
   * Utility method to convert a memory size string (e.g. 1K, 1M) into the number of bytes.
   *
   * @param string $size Size string to convert. Valid suffixes as G (gigabytes), M (megabytes), K (kilobytes) or nothing.
   * @return integer Number of bytes.
   */
  private static function convert_to_bytes($size) {
    // Make the size into a power of 1024
    switch (substr($size, -1))
    {
      case 'G': $size = intval($size) * pow(1024, 3); break;
      case 'M': $size = intval($size) * pow(1024, 2); break;
      case 'K': $size = intval($size) * pow(1024, 1); break;
      default:  $size = intval($size);                break;
    }
    return $size;
  }

  /**
   * Method that retrieves the data from a report or a table/view, ready to display in a chart or grid.
   * @param array $options Refer to documentation for report_helper::get_report_data.
   * @param string $extra Refer to documentation for report_helper::get_report_data.
   * @deprecated, use report_helper::get_report_data instead.
   */
  public static function get_report_data($options, $extra='') {
    require_once('report_helper.php');
    return report_helper::get_report_data($options, $extra);
  }

  /**
   * Provides access to a list of remembered field values from the last time the form was used.
   * Accessor for the $remembered_fields variable. This is a list of the fields on the form
   * which are to be remembered the next time the form is loaded, e.g. for values that do not change
   * much from record to record. This creates the list on demand, by calling a hook indicia_define_remembered_fields
   * if it exists. indicia_define_remembered_fields should call data_entry_helper::set_remembered_fields to give it
   * an array of field names.
   * Note that this hook architecture is required to allow the list of remembered fields to be made available
   * before the form is constructed, since it is used by the code which saves a submitted form to store the
   * remembered field values in a cookie.
   * @return Array List of the fields to remember.
   */
  public static function get_remembered_fields() {
    if (self::$remembered_fields == null && function_exists('indicia_define_remembered_fields')) {
      indicia_define_remembered_fields();
    }
    return self::$remembered_fields;
  }

  /**
   * Accessor to set the list of remembered fields.
   * Should only be called by the hook method indicia_define_remembered_fields.
   * @see get_rememebered_fields
   * @param $arr Array of field names
   */
  public static function set_remembered_fields($arr) {
    self::$remembered_fields = $arr;
  }

  /**
   * While cookies may be offered for the convenience of clients, an option to prevent
   * the saving of personal data should also be present.
   *
   * Helper function to output an HTML checkbox control. Defaults to false unless
   * values are loaded from cookie.
   *
   * @param array $options Options array with the following possibilities:<ul>
   * record with existing data for this control.</li>
   * <li><b>class</b><br/>
   * Optional. CSS class names to add to the control.</li>
   * <li><b>template</b><br/>
   * Optional. Name of the template entry used to build the HTML for the control. Defaults to checkbox.</li>
   * </ul>
   *
   * @return string HTML to insert into the page for the cookie optin control.
   */
  public static function remembered_fields_optin($options) {
    $options['fieldname'] = 'cookie_optin';
    $options = self::check_options($options);
    $options['checked'] = array_key_exists('indicia_remembered', $_COOKIE) ? ' checked="checked"' : '';
    $options['template'] = array_key_exists('template', $options) ? $options['template'] : 'checkbox';
    return self::apply_template($options['template'], $options);
  }

  /**
   * Includes any spatial reference handler JavaScript files that exist for the codes selected
   * for picking spatial references. If a handler file does not exist then the transform is handled
   * by a web-service request to the warehouse. Handlers are only required for grid systems, not for
   * coordinate systems that are entirely described by an EPSG code.
   * @param array $systems List of spatial reference system codes.
   */
  public static function include_sref_handler_js($systems) {
    // extract the codes and make lowercase
    $systems=unserialize(strtolower(serialize(array_keys($systems))));
    // find the systems that have client-side JavaScript handlers
    $handlers = array_intersect($systems, array('osgb','osie','4326'));
    self::get_resources();
    foreach ($handlers as $code) {
      $file = self::$js_path.strtolower("drivers/sref/$code.js");
      // dynamically build a resource to link us to the handler js file.
      self::$required_resources[] = 'sref_handlers_'.$code;
      self::$resource_list['sref_handlers_'.$code] = array(
        'javascript' => array($file)
      );
    }
  }

}
