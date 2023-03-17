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

global $custom_terms;

/**
 * Language terms for the custom_verification_ruleset_edit form.
 */
$custom_terms['Enter coordinates or drag a box on the map'] = <<<TXT
Drag a bounding box on the map to set the limit for where the ruleset will be applied, or enter the
values below. You don't need to fill in all values, for example you can just set a minimum
latitude. Enter the values in decimal format, with a negative number for a longitude in the Western
Hemisphere or a latitude in the Southern Hemisphere.
TXT;
