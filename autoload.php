<?php

/**
 * @file
 * Autoloader for namespaced Indicia classes.
 *
 * Indicia, the online wildlife recording toolkit.
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
 * @link https://github.com/indicia-team/client_helpers
 */

spl_autoload_register('iform_psr4_autoloader');

/**
 * IForm autoloader implementation.
 *
 * @param string $class
 *   The fully-qualified class name.
 */
function iform_psr4_autoloader($class) {
  if (substr($class, 0, 6) === 'IForm\\') {
    $class_path = str_replace('\\', '/', substr($class, 6));
    $file = __DIR__ . '/' . $class_path . '.php';
    if (file_exists($file)) {
      require_once $file;
    }
  }
}
