<?php

/**
 * @file
 * Exception class for validation errors in Indicia forms.
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
 * @link https://github.com/Indicia-Team/client_helpers
 */

namespace IForm;

/**
 * Exception class for validation errors in Indicia forms.
 *
 * For validation errors, the message should be safe to show to the user and
 * the fieldname of the input control it is associated with is supplied.
 */
class ValidationException extends \Exception {

  /**
   * Associated input's field name.
   *
   * @var string
   */
  public $fieldname;

  /**
   * Constructor for ValidationException.
   *
   * @param string $message
   *   Validation error message.
   * @param string $fieldname
   *   Associated input's field name.
   */
  public function __construct($message, $fieldname) {
    parent::__construct($message);
    $this->fieldname = $fieldname;
  }

}
