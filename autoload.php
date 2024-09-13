<?php

/**
 * @file
 * Autoloaded for namespaced Indicia classes.
 */

spl_autoload_register('iform_psr4_autoloader');

/**
 * An example of a project-specific implementation.
 *
 * @param string $class
 *   The fully-qualified class name.
 */
function iform_psr4_autoloader($class) {
  if (substr($class, 0, 6) === 'IForm\\') {
    $class_path = str_replace('\\', '/', substr($class, 6));
    $file = __DIR__ . '/' . $class_path . '.php';
    if (file_exists($file)) {
      require $file;
    }
  }
}
