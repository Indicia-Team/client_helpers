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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/Indicia-Team/client_helpers
 */

/**
 * Extension class that extends the functionality of the data entry of species.
 */
class extension_taxon_input_extras {

  /**
   * Control for hints on selected species.
   *
   * An extension control for dynamic forms that adds a box to output hints on any
   * species selected for addition to the species checklist grid. To use this control
   * provide a file called speciesHints.json in the Drupal file path, within the indicia
   * subfolder. This should contain a JSON object with the property names matching the
   * external keys of the taxa_taxon_list table, and the property values being the hint
   * string to show.
   *
   * Options can include:
   * * @title  - Override the title of the box displaying the hints.
   */
  public static function add_species_hints($auth, $args, $tabalias, $options, $path) {
    $options = array_merge([
      'title' => 'Hints relating to species names entered',
    ], $options);
    $filePath = hostsite_get_public_file_path();
    data_entry_helper::$javascript .= "initSpeciesHints('/$filePath/indicia/speciesHints.json');\n";
    return '<div id="species-hints-outer"><h3>' . lang::get($options['title']) . '</h3> ' .
        "<div id=\"species-hints\"></div></div>";
  }
}