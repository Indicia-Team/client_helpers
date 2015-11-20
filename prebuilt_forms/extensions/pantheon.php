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
      hostsite_set_page_title($title);
    }
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
    drupal_add_js('sites/all/libraries/jsPlumb/jsPLumb.js');
    $r = '';
    if (!empty($options['extras'])) {
      $r .= '<ul class="button-links extras">';
      foreach ($options['extras'] as $extra) {
        $r .= '<li><a id="isis-link" class="button" href="' . $extra['link'] . '">' . $extra['label'] . '</a></li>';
      }
      $r .= '</ul>';
    }
    $r .= '<ul class="button-links">';
    if (!empty($options['back']))
      $r .= '<li><a id="isis-link" class="button" href="pantheon/summary">Back to Summary</a></li>';
    $r .= '<li><a id="isis-link" class="button" href="isis/assemblages-overview">Assemblages (ISIS)</a></li>
<li><a id="osiris-link" class="button" href="osiris/ecological-divisions">Traits (OSIRIS)</a></li>
<li><a id="horus-link" class="button"href="horus/quality-scores-overview">Quality Scores (HORUS)</a></li>
</ul>';
    return $r;
  }
}