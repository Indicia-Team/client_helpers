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
 * @package	Client
 * @author	Indicia Team
 * @license	http://www.gnu.org/licenses/gpl.html GPL 3.0
 * @link 	http://code.google.com/p/indicia/
 */

global $custom_terms;

/**
 * Language terms for the survey_reporting_form_2 form.
 *
 * @package	Client
 */
$custom_terms = array(
	'LANG_Main_Samples_Tab' => 'Surveys',
	'LANG_Download' => 'Reports and Downloads',
	'LANG_Locations' => 'Sites',
	'LANG_Sites_Download' => 'Run a report to provide information on all the sites used for these surveys, plus their attributes. (CSV Format)',
	'LANG_Conditions_Download' => 'Run a report to provide information on all these surveys, including the conditions and the associated sites. This returns one row per survey, and excludes any species data. (CSV Format)',
	'LANG_Species_Download' => 'Run a report to provide information on species entered for these surveys. It includes the data for the surveys, conditions and the associated sites. This returns one row per occurrence. (CSV Format)',
	'LANG_Download_Button' => 'Download Report',
	'Edit' => '�diter',
	// 'Actions' is left unchanged
	// TBD translations for report grid headings.
	'SRef'=>'Coordonn�es',
	// TBD Translations for species grid headings, species tab header, species comment header, conditions block headers.
	'LANG_Edit' => '�diter',
	'LANG_Add_Sample' => 'Ajouter nouvel �chantillon',
	'LANG_Add_Sample_Single' => 'Add Unique',
	'LANG_Add_Sample_Grid' => 'Ajouter plusieurs occurrences',
	'LANG_Save' => 'Enregistrer',
	'save'=>'Enregistrer',
	'LANG_Cancel' => 'Annuler',
	'next step'=>'Suivant',
	'prev step'=>'Pr�c�dente',

	// 'Site' tab heading left alone
	'Existing Locations' => 'Sites existants',
	'LANG_Location_Code_Label' => 'Code',
	'LANG_Location_Code_Blank_Text' => 'Choisissez un emplacement existant par le code',
	'LANG_Location_Name_Label' => 'Nom du site',
	'LANG_Location_Name_Blank_Text' => 'Choisissez un site',
	'Create New Location' => 'Cr�er un nouvel emplacement',
	'village' => 'Village / Lieu-dit',
	'commune' => 'Commune',
	'LANG_PositionOutsideCommune' => 'The position you have choosen is outside the set of allowable Communes. You will not be able to save this position.',
	'site type' => 'Type de g�te',
	'site followup' => 'Pertinence du site pour un suivi r�gulier',
	'LANG_Georef_Label'=>'Chercher la position sur la carte',
	'LANG_Georef_SelectPlace' => 'Choisissez la bonne parmi les localit�s suivantes qui correspondent � votre recherche. (Cliquez dans la liste pour voir l\'endroit sur la carte.)',
	'LANG_Georef_NothingFound' => 'Aucun endroit n\'a �t� trouv� avec ce nom. Essayez avec le nom d\'une localit� voisine.',
	'Latitude' => 'Coordonn�es : X ',
	'Longitude' => 'Y ',
	'LANG_LatLong_Bumpf' => '(projection g�ographique LUREF en m�tres)',
	'precision' => 'Pr�cision',
	'codegsl' => 'Code GSL',
	'profondeur' => 'Profondeur',
	'development' => 'D�veloppement',
	'search' => 'Chercher',
	'Location Comment' => 'Commentaires',
	'Clear Position' => 'Effacer les coordonn�es',
	'View All Luxembourg' => 'Voir tout le Luxembourg',

	'LANG_Tab_otherinformation' => 'Conditions',
	'LANG_Date' => 'Date',
	'Recorder names' => 'Observateur(s)',
	'LANG_RecorderInstructions'=>"(Pour s�lectionner plusieurs observateurs, maintenir la touche CTRL enfonc�e)",
	'General' => 'G�n�ral',
	'Physical' => 'Caract�ristiques de la cavit�',
	'Microclimate' => 'Conditions microclimatiques',
	'Visit' => 'Visite',
	'Bat Visit' => 'Visite',
	'LANG_Site_Extra' => "(Num�ro de passage / Nombre de passages durant l'hiver)",
	'cavity entrance' => 'Entr�e de la cavit�',
	'disturbances' => 'Perturbations',
	'Human Frequentation' => 'Fr�quentation humaine du site',
	'Bats Temp Exterior' => "Temp�rature � l'ext�rieur de la cavit� (Celsius)",
	'Bats Humid Exterior' => "Humidit� relative hors de la cavit� (%)",
	'Bats Temp Int 1' => "Temp�rature � l'int�rieur de la cavit� - A (Celsius)",
	'Bats Humid Int 1' => "Humidit� � l'int�rieur de la cavit� - A (%)",
	'Bats Temp Int 2' => "Temp�rature � l'int�rieur de la cavit� - B (Celsius)",
	'Bats Humid Int 2' => "Humidit� � l'int�rieur de la cavit� - B (%)",
	'Bats Temp Int 3' => "Temp�rature � l'int�rieur de la cavit� - C (Celsius)",
	'Bats Humid Int 3' => "Humidit� � l'int�rieur de la cavit� - C (%)",
	'Positions Marked' => 'Emplacement(s) des prises de mesures indiqu�(s) sur le relev� topographique',
	'Reliability' => "Fiabilit� (exhaustivit�) de l'inventaire",
	'Overall Comment' => 'Commentaires',

	'LANG_Tab_species' => 'Esp�ces',
	'species_checklist.species'=>'Esp�ces',
	'Bats Obs Type' => "Type d'observation",
	'SCLabel_Col1' => "Nombre d'individus",
	'SCLabel_Row1' => 'Vivant(s)',
	'SCLabel_Row2' => 'Mort(s)',
	'Excrement' => 'Excr�ments', 
	'Occurrence Reliability' => "Fiabilit� de la determination",
	'No observation' => 'Aucune observation',
	'Comment' => 'Commentaires',
	'LANG_Duplicate_Taxon' => 'Vous avez s�lectionn� un taxon qui a d�j� une entr�e.',
	'Are you sure you want to delete this row?' => 'Etes-vous s�r de vouloir supprimer cette ligne?',

	'validation_required' => 'Veuillez entrer une valeur pour ce champ',
	'validation_max' => "S'il vous pla�t entrer une valeur inf�rieure ou �gale � {0}.",
	'validation_min' => "S'il vous pla�t entrer une valeur sup�rieure ou �gale � {0}.",
	'validation_number' => "S'il vous pla�t entrer un num�ro valide.",
	'validation_digits' => "S'il vous pla�t entrer un nombre entier positif.",
	'validation_integer' => "S'il vous pla�t entrer un nombre entier.",
	'validation_no_observation' => "Cette option doit �tre coch�e si et seulement si il n'existe aucun donn�e dans le tableau ci-dessus.",
	'validation_fillgroup'=>"S'il vous pla�t entrer un de ces deux champs."
);