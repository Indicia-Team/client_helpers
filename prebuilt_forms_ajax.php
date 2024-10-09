<?php

/**
 * @file
 * Ajax script to retrieve a prebuilt form config form for the Edit tab.
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
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/indicia-team/warehouse/
 */

/**
 * A simple script to return the params form for an input prebuilt form name.
 */

require_once 'autoload.php';
// Use iform to load the helpers, so it can set the configuration variables if
// running in Drupal.
require_once 'form_helper.php';
// Let params forms internationalise.
require_once 'lang.php';

// Set the path to JS and CSS files. This script runs standalone, so has to do
// this itself.
$link = form_helper::get_reload_link_parts();
$path = dirname(dirname($link['path'])) . '/media';
form_helper::$js_path = "$path/js/";
form_helper::$css_path = "$path/css/";

form_helper::$is_ajax = TRUE;

form_helper::$base_url = $_POST['base_url'];
$readAuth = form_helper::get_read_auth($_POST['website_id'], $_POST['password']);

echo form_helper::prebuiltFormParamsForm([
  'form' => $_POST['form'],
  'readAuth' => $readAuth,
  'expandFirst' => TRUE,
  'generator' => (isset($_POST['generator'])) ? $_POST['generator'] : 'No generator metatag posted',
]);
data_entry_helper::$dumped_resources[] = 'jquery';
data_entry_helper::$dumped_resources[] = 'jquery_ui';
echo form_helper::dump_javascript(TRUE);
