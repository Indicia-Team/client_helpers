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
 * Language terms for the verification_5 form.
 */

$custom_terms = [
  'Report output' => 'All records in verification grid',
];

$custom_terms['Redet partial list info'] = <<<TXT
This record was originally input using a taxon checklist which may not be a complete list of all species. If you cannot
find the species you wish to redetermine it to using the search box below, then please tick the "Search all species"
checkbox and try again.
TXT;
