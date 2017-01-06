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
 * @subpackage PrebuiltForms
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

if (!empty($_GET['table']) && $_GET['table']==='sample' && !empty($_GET['id'])) {
  $_REQUEST['dynamic-sample_id']=$_GET['id'];
  $_GET['dynamic-sample_id']=$_GET['id'];
}


/**
 * Extension class that supports the Pantheon species traits system.
 */
class extension_pantheon {

  /**
   * Updates the page title with dynamic values.
   * Replaces the following:
   * * {sample_id} with the current ID (loaded from the URL query parameter dynamic-sample_id)
   * * {term:<queryparam>} with a term from the warehouse. Replace <queryparam> with the
   *   name of a URL query string parameter that contains the value of the termlists_term
   *   record to load the term for.
   * @param $options Array with the following options:
   * * auth - Must contain a read entry for read authorisation tokens
   * @return string Empty string
   */
  public static function set_dynamic_page_title($auth, $args, $tabalias, $options, $path) {
    if (arg(0) == 'node' && is_numeric(arg(1))) {
      $nid = arg(1);
      $title = hostsite_get_page_title($nid);
    } else {
      $title = drupal_get_title();
    }
    if (!empty($_GET['dynamic-sample_id']))
      $title = str_replace('{sample_id}', $_GET['dynamic-sample_id'], $title);
    if (preg_match('/{term:(?P<param>.+)}/', $title, $matches) && !empty($_GET[$matches['param']])) {
      $terms = data_entry_helper::get_population_data(array(
        'table' => 'termlists_term',
        'extraParams' => $auth['read'] + array('id' => $_GET[$matches['param']], 'view' => 'cache'),
        'columns' => 'term'
      ));
      if (count($terms))
        $title = str_replace('{term:' . $matches['param'] . '}', $terms[0]['term'], $title);

    }
    if (preg_match('/{attr:(?P<param>.+)}/', $title, $matches) && !empty($_GET[$matches['param']])) {
      $attrs = data_entry_helper::get_population_data(array(
        'table' => 'taxa_taxon_list_attribute',
        'extraParams' => $auth['read'] + array('id' => $_GET[$matches['param']]),
        'columns' => 'caption'
      ));
      if (count($attrs))
        $title = str_replace('{attr:' . $matches['param'] . '}', $attrs[0]['caption'], $title);

    }
    hostsite_set_page_title($title);
    return '';
  }

  /**
   * Outputs the list of buttons to appear under a Pantheon report.
   * Also enables use of the jsPlumb library for connections between report output boxes.
   * Download the latest jsPlumb JS file into sites/all/libraries/jsPlumb/jsPlumb.js.
   * @param array $options Array with the following options:
   * * extras - array of additional buttons to include. Each entry contains a link
   *   parameter and a label and the current sample_id query string parameter will be added to the link.
   * * back - set to true if this needs a Back to Summary button.
   * @return string HTML to include on the page.
   */
  public static function button_links($auth, $args, $tabalias, $options, $path) {
    drupal_add_js(str_replace('modules/iform', 'libraries/jsPlumb', drupal_get_path('module', 'iform')) . '/jsPlumb.js');
    $r = '';
    if (!empty($options['extras'])) {
      $r .= '<ul class="button-links extras">';
      foreach ($options['extras'] as $extra) {
        $r .= '<li><a id="isis-link" class="button" href="' . hostsite_get_url($extra['link']) . '">' . $extra['label'] . '</a></li>';
      }
      $r .= '</ul>';
    }
    $r .= '<ul class="button-links">';
    if (!empty($options['back']))
      $r .= '<li><a id="summary-link" class="button" href="' . hostsite_get_url('pantheon/summary') . '">Back to Summary</a></li>';
    $r .= '<li><a id="species-link" class="button" href="' . hostsite_get_url('species-for-sample') . '">Species list</a></li>
<li><a id="guilds-link" class="button" href="' . hostsite_get_url('ecological-guilds') . '">Feeding guilds</a></li>
<li><a id="osiris-link" class="button" href="' . hostsite_get_url('osiris') . '">Habitats &amp; resources</a></li>
<li><a id="horus-link" class="button" href="' . hostsite_get_url('horus/quality-scores-overview') . '">Habitat scores</a></li>
<li><a id="associations-link" class="button" href="' . hostsite_get_url('associations') . '">Associations</a></li>
<li><a id="combined-summary" class="button" href="' . hostsite_get_url('combined-summary') . '">Combined summary</a></li>
</ul>';
    return $r;
  }

  public static function assemblages_chart($auth, $args, $tabalias, $options, $path) {
    iform_load_helpers(array('report_helper'));
    if (empty($_GET['dynamic-sample_id']))
      return '';
    $presets = report_helper::explode_lines_key_value_pairs($args['param_presets']);
    $rows = report_helper::get_report_data(array(
      'dataSource' => 'reports_for_prebuilt_forms/pantheon/traits_of_type_in_sample',
      'readAuth' => $auth['read'],
      'extraParams' => array('sample_id' => $_GET['dynamic-sample_id'], 'orderby' => 'trait_attr_id') + $presets
    ));
    $attrIds = explode(',', $presets['trait_attr_ids']);
    // an array to restructure the parent/child heirarchy into, with a space for parentless specific
    // assemblages to start
    $struct = array('term-'=>array('specific'=>array()));
    foreach ($rows as $row) {
      if ($row['trait_attr_id']===$attrIds[0]) {
        // a broad assemblage
        $struct["term-$row[trait_term_id]"] = array('broad'=>$row,'specific'=>array());
      } else {
        $struct["term-$row[parent_term_id]"]['specific'][] = $row;
      }
    }
    // now convert the structure to HTML output
    $r = '<div class="output-panes-panel">';
    if (empty($rows)) {
      $r .= '<p>No assemblages information available for this sample.</p>';
    }
    else {
      foreach ($struct as $id => $broad_and_children) {
        $broad = empty($broad_and_children['broad']) ? FALSE : $broad_and_children['broad'];
        if ($broad || !empty($broad_and_children['specific'])) {
          $rootFolder = report_helper::getRootFolder(TRUE);
          $sep = strpos($rootFolder, '?') === FALSE ? '?' : '&';
          $r .= '<div class="output-panes-row clearfix">';
          if (!empty($broad)) {
            $r .= "<div class=\"broad-assemblage trait\">" .
              "<span id=\"trait-$broad[trait_term_id]\">" .
              "$broad[trait]</span><a href=\"{$rootFolder}species-for-trait{$sep}dynamic-sample_id=$broad[sample_id]" .
              "&dynamic-trait_term_id=$broad[trait_term_id]&dynamic-trait_attr_id=$broad[trait_attr_id]\">$broad[count]</a></div>";
          }
          $r .= "<div class=\"specific-assemblages\">";
          foreach ($broad_and_children['specific'] as $specific) {
            $r .= "<div class=\"specific-assemblage trait\"><span id=\"trait-$specific[trait_term_id]\">" .
              "$specific[trait]</span><a href=\"{$rootFolder}species-for-trait{$sep}dynamic-sample_id=$specific[sample_id]" .
              "&dynamic-trait_term_id=$specific[trait_term_id]&dynamic-trait_attr_id=$specific[trait_attr_id]\">$specific[count]</a></div>";
          }
          $r .= "</div></div>";
        }
      }
    }
    $r .= '</div>';

    return $r;
  }

  /**
   * Tabular output by broad biotope, specific biotiope and resources for Osiris.
   * @param $auth
   * @param $args
   * @param $tabalias
   * @param $options
   * @param $path
   */
  public static function osiris_tabular($auth, $args, $tabalias, $options, $path) {
    iform_load_helpers(array('report_helper'));
    $filter = array(
      'sample_id' => $_GET['dynamic-sample_id'],
      'bb_attr_id' => $options['bb_attr_id'],
      'sb_attr_id' => $options['sb_attr_id'],
      'r_attr_id' => $options['r_attr_id']
    );
    if (!empty($_GET['dynamic-conservation_group_id']))
      $filter['conservation_group_id'] = $_GET['dynamic-conservation_group_id'];
    $data = report_helper::get_report_data(array(
      'dataSource' => 'reports_for_prebuilt_forms/pantheon/osiris_tabular_hierarchy',
      'readAuth' => $auth['read'],
      'extraParams' => $filter,
      'caching' => true
    ));
    $recordsByCat = [];

    foreach ($data as $record) {
      if (!isset($recordsByCat[$record['category']]))
        $recordsByCat[$record['category']] = [];
      $recordsByCat[$record['category']][] = $record;
    }
    $i = 0;
    $rows = [];
    // assign colours
    $kelly_sequence = [
      "FFB300", # Vivid Yellow
      "803E75", # Strong Purple
      "FF6800", # Vivid Orange
      "A6BDD7", # Very Light Blue
      "C10020", # Vivid Red
      "CEA262", # Grayish Yellow
      "817066", # Medium Gray

      # The following don't work well for people with defective color vision
      "007D34", # Vivid Green
      "F6768E", # Strong Purplish Pink
      "00538A", # Strong Blue
      "FF7A5C", # Strong Yellowish Pink
      "53377A", # Strong Violet
      "FF8E00", # Vivid Orange Yellow
      "B32851", # Strong Purplish Red
      "F4C800", # Vivid Greenish Yellow
      "7F180D", # Strong Reddish Brown
      "93AA00", # Vivid Yellowish Green
      "593315", # Deep Yellowish Brown
      "F13A13", # Vivid Reddish Orange
      "232C16" # Dark Olive Green
    ];
    $bbColors = [];
    foreach ($recordsByCat['1.bb'] as $idx => $bb) {
      $bbColors[$bb['broad_biotope']] = $kelly_sequence[$idx % 22];
    }
    while (true) {
      $row = '';
      if (!(isset($recordsByCat['1.bb'][$i]) || isset($recordsByCat['2.sb'][$i]) || isset($recordsByCat['3.r'][$i]))) {
        break; // if no rows left
      }
      if (isset($recordsByCat['1.bb'][$i])) {
        $record = $recordsByCat['1.bb'][$i];
        $color = $bbColors[$record['broad_biotope']];
        $row .= "<td><span>$record[broad_biotope]</span></td><td style=\"background-color: #$color\"></td>" .
          "<td>$record[count]</td><td>$record[return]</td>";
      } else {
        $row .= '<td colspan="4"></td>';
      }
      if (isset($recordsByCat['2.sb'][$i])) {
        $record = $recordsByCat['2.sb'][$i];
        $color = $bbColors[$record['broad_biotope']];
        $row .= "<td><span>$record[specific_biotope]</span></td><td style=\"background-color: #$color\"></td>" .
          "<td>$record[count]</td><td>$record[return]</td>";
      } else {
        $row .= '<td colspan="4"></td>';
      }
      if (isset($recordsByCat['3.r'][$i])) {
        $record = $recordsByCat['3.r'][$i];
        $color = $bbColors[$record['broad_biotope']];
        // indent children
        if (!empty($record['parent_r_id']))
          $record['resource'] = ' &gt;&gt; ' . $record['resource'];
        $row .= "<td><span>$record[resource]</span></td><td style=\"background-color: #$color\"></td>" .
          "<td>$record[count]</td><td>$record[return]</td>";
      } else {
        $row .= '<td colspan="4"></td>';
      }
      $rows[] = "<tr>$row</tr>";
      $i++;
    }
    $r = '<table class="lexicon">' .
      '<thead><tr>' .
      '<th>Broad biotope</th><th>Type</th><th>No.</th><th>% rep.</th>' .
      '<th>Specific biotope</th><th>Type</th><th>No.</th><th>% rep.</th>' .
      '<th>Resource</th><th>Type</th><th>No.</th><th>% rep.</th>' .
      '</tr></thead>' .
      '<tbody>' . implode("\n", $rows) . '</tbody></table>';
    return $r;
  }

  public static function lexicon($auth, $args, $tabalias, $options, $path) {
    iform_load_helpers(array('report_helper'));
    $query = new EntityFieldQuery();
    $query
      ->entityCondition('entity_type', 'node')
      ->entityCondition('bundle', 'lexicon')
      ->propertyCondition('status', 1);
    $result = $query->execute();
    $nids = array_keys($result['node']);
    $nodes = node_load_multiple($nids);
    $list = [];
    foreach ($nodes as $node) {
      $list[$node->title] = $node->field_summary[LANGUAGE_NONE][0]['value'];
    }
    report_helper::$javascript .= "indiciaData.lexicon = " . json_encode($list) . ";\n";
    report_helper::$javascript .= "indiciaFns.applyLexicon();\n";
  }
}