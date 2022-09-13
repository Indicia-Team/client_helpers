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
 * @license http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link https://github.com/Indicia-Team/client_helpers
 */

/**
 * Additional language terms or overrides for es_download form.
 */
$custom_terms['LANG_Helptext_query'] = <<<TXT
E.g. "Lasius", "2007" or to search within a specific field "taxon.family:Apidae". Combine values with OR or AND (in
capitals), e.g. "Lasius AND 2007" or "taxon.genus:Lasius OR taxon.genus:Myrmica".
<a href="https://github.com/Indicia-Team/support_files/blob/master/Elasticsearch/document-structure.md" target="_top">Available fields...</a>
TXT;
$custom_terms['LANG_Helptext_higher_geography'] = <<<TXT
Name of an indexed location to limit the records to (e.g. a Vice County name). To search for a location that is not
indexed on the warehouse, use the Query option instead (e.g. set the query to "location.name:Minsmere").
TXT;
