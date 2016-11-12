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

class extension_scratchpad {

  /**
   * @param $auth
   * @param $args
   * @param $tabalias
   * @param $options
   * @param $path
   */
  public static function input_list($auth, $args, $tabalias, $options, $path) {
    iform_load_helpers(array('report_helper'));
    $options = array_merge(array(
      'match' => 'species',
      'extraParams' => array(),
      'duplicates' => 'highlight', // 'allow|highlight|warn|disallow'
      'filters' => array()
    ), $options);
    $r = '<div contenteditable="true" id="scratchpad-input" style="width: 200px; height: 200px; border: solid silver 1px;"></div>';
    $r .= '<div id="scratchpad-output"></div>';
    $r .= '<button id="scratchpad-check">Check</button>';
    report_helper::$javascript .= 'indiciaData.scratchpadSettings = ' . json_encode($options) . ";\n";
    return $r;
  }

}