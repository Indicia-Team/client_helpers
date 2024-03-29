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

include_once 'dynamic.en.php';

/**
 * Additional language terms or overrides for dynamic_sample_occurrence form.
 */
$custom_terms = array_merge($custom_terms, array(
    'LANG_Site_Location_Label' => 'Site',
    'LANG_Add_Sample' => 'Add New Observation',
    'Tree Observation' => 'How much of the tree did you observe on this visit',
    'No Understorey Observed' => 'None of the above flowering species observed',
    'Snow Cover' => 'Understorey area not visible due to snow cover'
  )
);