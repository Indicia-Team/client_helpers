<?php

/**
 * @file
 * Extension class for Pantheon.
 *
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
 * @package Client
 * @subpackage PrebuiltForms
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link http://code.google.com/p/indicia/
 */

if (!empty($_GET['table']) && $_GET['table'] === 'sample' && !empty($_GET['id'])) {
  $_REQUEST['dynamic-sample_id'] = $_GET['id'];
  $_GET['dynamic-sample_id'] = $_GET['id'];
}

/**
 * Extension class that supports the Pantheon species traits system.
 */
class extension_pantheon {

  /**
   * Updates the page title with dynamic values.
   *
   * Replaces the following:
   * * {sample_id} with the current ID (loaded from the URL query parameter
   *   dynamic-sample_id)
   * * {sample_name} - builds a name for the sample from the ID, sample type
   *   (list or sample), date, grid ref and location name.
   * * {term:<queryparam>} with a term from the warehouse. Replace <queryparam>
   *   with the name of a URL query string parameter that contains the value of
   *   the termlists_term record to load the term for.
   * * {attr:<id>} with the caption of the taxa taxon list attribute with the
   *   given ID.
   *
   * @return string
   *   Empty string as no control output HTML required.
   */
  public static function set_dynamic_page_title($auth, $args, $tabalias, $options, $path) {
    if (arg(0) == 'node' && is_numeric(arg(1))) {
      $nid = arg(1);
      $title = hostsite_get_page_title($nid);
    }
    else {
      $title = drupal_get_title();
    }
    if (!empty($_GET['dynamic-sample_id'])) {
      $title = str_replace('{sample_id}', $_GET['dynamic-sample_id'], $title);
      $ids = explode(',', $_GET['dynamic-sample_id']);
      if ($_GET['dynamic-sample_type'] === 'scratchpad') {
        $name = 'List ' . $_GET['dynamic-sample_id'];
        if (count($ids) <= 3) {
          $name = count($ids) === 1 ? lang::get('List') : lang::get('Lists');
          $lists = data_entry_helper::get_population_data([
            'table' => 'scratchpad_list',
            'extraParams' => $auth['read'] + ['query' => json_encode(['in' => ['id' => $ids]])],
            'columns' => 'id,title',
          ]);
          $titles = [];
          foreach ($lists as $list) {
            $titles[] = "$list[id] [$list[title]]";
          }
          $name .= ' ' . implode('; ', $titles);
        }
        else {
          $name = lang::get('Multiple lists');
        }
      }
      else {
        $name = 'Sample ' . $_GET['dynamic-sample_id'];
        if (count($ids) <= 3) {
          $samples = data_entry_helper::get_population_data(array(
            'report' => 'library/samples/filterable_explore_list',
            'extraParams' => $auth['read'] + array('sample_id' => $_GET['dynamic-sample_id']),
          ));
          if (!isset($samples['error']) && count($samples) > 0) {
            $titles = [];
            foreach ($samples as $sample) {
              $parts = [
                $sample['date'],
                $sample['entered_sref'],
              ];
              if (!empty($sample['location_name'])) {
                $parts[] = $sample['location_name'];
              }
              $titles[] = implode(', ', $parts);
            }
            $name .= ' [' . implode('; ', $titles) . ']';
          }
        }
        else {
          $name .= ' [' . lang::get('multiple samples') . ']';
        }
      }
      $title = str_replace('{sample_name}', $name, $title);
    }
    if (preg_match('/{term:(?P<param>.+)}/', $title, $matches) && !empty($_GET[$matches['param']])) {
      $terms = data_entry_helper::get_population_data(array(
        'table' => 'termlists_term',
        'extraParams' => $auth['read'] + array('id' => $_GET[$matches['param']], 'view' => 'cache'),
        'columns' => 'term',
      ));
      if (count($terms)) {
        $title = str_replace('{term:' . $matches['param'] . '}', $terms[0]['term'], $title);
      }
    }
    if (preg_match('/{attr:(?P<param>.+)}/', $title, $matches) && !empty($_GET[$matches['param']])) {
      $attrs = data_entry_helper::get_population_data(array(
        'table' => 'taxa_taxon_list_attribute',
        'extraParams' => $auth['read'] + array('id' => $_GET[$matches['param']]),
        'columns' => 'caption',
      ));
      if (count($attrs)) {
        $title = str_replace('{attr:' . $matches['param'] . '}', $attrs[0]['caption'], $title);
      }
    }
    hostsite_set_page_title($title);
    return '';
  }

  /**
   * Outputs the list of buttons to appear under a Pantheon report.
   *
   * Also enables use of the jsPlumb library for connections between report
   * output boxes. Download the latest jsPlumb JS file into
   * sites/all/libraries/jsPlumb/jsPlumb.js.
   *
   * @param array $options
   *   Array with the following options:
   *   * extras - array of additional buttons to include. Each entry contains a link
   *     parameter and a label and the current sample_id query string parameter will be added to the link.
   *   * back - set to true if this needs a Back to Summary button.
   *
   * @return string
   *   HTML to include on the page.
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
    if (!empty($options['back'])) {
      $r .= '<li><a id="summary-link" class="button" href="' . hostsite_get_url('pantheon/summary') . '">Back to Summary</a></li>';
    }
    $r .= '<li><a id="species-link" class="button" href="' . hostsite_get_url('species-for-sample') . '">Species list</a></li>
<li><a id="guilds-link" class="button" href="' . hostsite_get_url('ecological-guilds') . '">Feeding guilds</a></li>
<li><a id="habitats-resources-link" class="button" href="' . hostsite_get_url('habitats-resources') . '">Habitats &amp; resources</a></li>
<li><a id="habitats-resources-link" class="button" href="' . hostsite_get_url('habitats-resources/isis-assemblages') . '">Assemblages</a></li>
<li><a id="horus-link" class="button" href="' . hostsite_get_url('horus/quality-scores-overview') . '">Habitat scores</a></li>
<li><a id="associations-link" class="button" href="' . hostsite_get_url('associations') . '">Associations</a></li>
<li><a id="combined-summary" class="button" href="' . hostsite_get_url('combined-summary') . '">Combined summary</a></li>
</ul>';
    return $r;
  }

  /**
   * Links spans on the page to the Pantheon Lexicon.
   */
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

  /**
   * Outputs a table and controls associated with combining lists together.
   *
   * Dependencies:
   *   * Should be a tab called Quick Analysis Group.
   *   * On another tab, a list of scratchpads in a report. Set @rowId=id
   *     to ensure the IDs are available in the row classes. Add an action
   *     column with an action that calls the JavaScript
   *     indiciaFns.addToQuickAnalysisGroup({id}).
   *
   * @param array $options
   *   Options passed to the control:
   *   * analysisPath - path to the analysis page.
   */
  public static function quick_analysis_scratchpad_group($auth, $args, $tabalias, $options, $path) {
    iform_load_helpers(array('report_helper'));
    $imgPath = empty(report_helper::$images_path)
      ? report_helper::relative_client_helper_path() . "../media/images/"
      : report_helper::$images_path;
    $userId = hostsite_get_user_field('indicia_user_id');
    $auth = report_helper::get_read_write_auth(variable_get('indicia_website_id'), variable_get('indicia_password'));
    $write = json_encode($auth['write_tokens']);
    report_helper::$javascript .= <<<JS
indiciaData.imagesPath = '$imgPath';
indiciaData.write = $write;

JS;
    return <<<HTML
<table id="quick-analysis-group" class="ui-widget ui-widget-content">
  <thead class="ui-widget-header">
    <th>List</th>
    <th>Actions</th>
  </thead>
  <tbody>
  </tbody>
</table>
<div id="qa-group-actions">
  <button disabled="disabled" onclick="indiciaFns.analyseQuickAnalysisGroup('$options[analysisPath]', 'scratchpad')">Analyse all lists in group</button>
  <button disabled="disabled" onclick="indiciaFns.clearQuickAnalysisGroup()">Clear group</button>
  <label class="auto">
    Convert group to a new list named:
    <input disabled="disabled" type="text" id="new-list-name" placeholder="Enter a list name" />
  </label>
  <button disabled="disabled" onclick="indiciaFns.saveQuickAnalysisScratchpadGroup()">Convert</button>
</div>

HTML;
  }

}
