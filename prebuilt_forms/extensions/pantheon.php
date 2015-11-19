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
   *
   * @param $options
   * * template
   * * param
   * @return string
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
/* NOT USED?
  public static function add_term_from_query_param_term_id_to_title($auth, $args, $tabalias, $options, $path) {
    if (arg(0) == 'node' && is_numeric(arg(1)) && !empty($_GET[$options['term_id_param']])) {
      iform_load_helpers(array('data_entry_helper'));
      $nid = arg(1);
      $terms = data_entry_helper::get_population_data(array(
        'table' => 'termlists_term',
        'extraParams' => $auth['read'] + array('id' => $_GET[$options['term_id_param']], 'view' => 'cache'),
        'columns' => 'term'
      ));
      hostsite_set_page_title(hostsite_get_page_title($nid) .
        str_replace('#value#', $terms[0]['term'], $options['template']));
    }
  }
*/
  public static function button_links($auth, $args, $tabalias, $options, $path) {
    drupal_add_js('sites/all/libraries/jqPlumb/jqPLumb.js');
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