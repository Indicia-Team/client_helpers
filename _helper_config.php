<?php

/**
 * @file
 * An optional file that provides settings for the Client Helper PHP library.
 *
 * When the Client Helper PHP library is run as a standalone library, this file
 * should be copied to create a file called helper_config.php and the values of
 * the variables declared set to provide appropriate configuration for the
 * Indicia code. Those marked as optional can be ommitted, though if ommitting
 * an API key this will disable the relevant area of functionality.
 *
 * When the Client Helper PHP library is run from inside Drupal or another
 * content managemet system, this file is not required. Instead the CMS should
 * set each variable in helper_base before using the library code.
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
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link http://code.google.com/p/indicia/
 */

class helper_config {

  /**
   * Base URL of the warehouse we are linked to.
   *
   * @var string
   */
  public static $base_url = '';

  /**
   * Path to proxy script for calls to the warehouse. Optional.
   *
   * Allows the warehouse to sit behind a firewall only accessible from the
   * server.
   *
   * @var string
   */
  public static $warehouse_proxy = '';

  /**
   * Base URL of the GeoServer we are linked to if GeoServer is used. Optional.
   *
   * @var string
   */
  public static $geoserver_url = '';

  /**
   * A temporary location for uploaded images. Optional.
   *
   * Images are stored here when uploaded by a recording form but before they
   * are sent to the warehouse.
   *
   * @var string
   */
  public static $interim_image_folder = '';

  /**
   * Google API key for place searches. Optional.
   *
   * @var string
   */
  public static $google_api_key = '';

  /**
   * Google Maps JavaScript API key. Optional.
   *
   * @var string
   */
  public static $google_maps_api_key = '';

  /**
   * Bing Maps API key. Optional.
   *
   * @var string
   */
  public static $bing_api_key = '';

  /**
   * Ordnance Survey Maps API key. Optional.
   *
   * @var string
   */
  public static $os_api_key = '';

  /**
   * Setting which allows the host (e.g. Drupal) handle translation. Optional.
   *
   * For example, when TRUE, a call to lang::get() is delegated to Drupal's t()
   * function.
   *
   * @var bool
   */
  public static $delegate_translation_to_hostsite = FALSE;

}
