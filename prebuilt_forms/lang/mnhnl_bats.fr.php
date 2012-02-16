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
// 'Actions' is left unchanged
// TBD translations for report grid headings.
$custom_terms = array(
// Tab Titles
	'LANG_Main_Samples_Tab' => 'Echantillons',
	'LANG_Download' => 'Reports',
	'LANG_Locations' => 'Sites',
	'LANG_Tab_otherinformation' => 'Conditions',
	'LANG_Tab_species' => 'Esp�ces',
	'LANG_Trailer_Text' => "Coordination du programme de monitoring de la biodiversit� au Luxembourg: <a href='http://www.crpgl.lu' target='_blank'>Centre de Recherche Public - Gabriel Lippmann</a> (D�partement Environnement et Agro-biotechnologies) & <a href='http://www.environnement.public.lu' target='_blank'>Minist�re du D�veloppement durable et des Infrastructures</a> (D�partement de l'environnement)",
// Navigation
	'LANG_Edit' => '�diter',
	'Edit' => '�diter',
	'LANG_Add_Sample' => 'Ajouter nouvel �chantillon',
	'LANG_Add_Sample_Single' => 'Add single occurrence',
	'LANG_Add_Sample_Grid' => 'Ajouter plusieurs occurrences',
	'LANG_Save' => 'Enregistrer',
	'save'=>'Enregistrer',
	'LANG_Cancel' => 'Annuler',
	'LANG_Submit' => 'Enregistrer',
	'next step'=>'Suivant',
	'prev step'=>'Pr�c�dent',
// Main grid Selection
	'Site name' => 'Nom du site',
	'Actions' => 'Actions',
	'Delete'=>'Supprimer',
// Reports
	'LANG_Sites_Download' => 'Run a report to provide information on all the sites used for these surveys, plus their attributes. (CSV Format)',
	'LANG_Conditions_Download' => 'Run a report to provide information on all these surveys, including the conditions and the associated sites. This returns one row per survey, and excludes any species data. (CSV Format)',
	'LANG_Species_Download' => 'Run a report to provide information on species entered for these surveys. It includes the data for the surveys, conditions and the associated sites. This returns one row per occurrence. (CSV Format)',
	'LANG_Download_Button' => 'Download report',
// Locations
	'Existing locations' => 'Sites existants',
	'LANG_Location_Label' => 'Location',
	'LANG_Location_Name' => 'Nom du site',
	'Create New Location' => 'Cr�er un nouvel emplacement',
	'LANG_Location_Name_Blank_Text' => 'Choisissez un site',
	'SRef'=>'Coordonn�es',
	'LANG_SRef_Label' => 'Coordonn�es',
	'LANG_Location_X_Label' => 'Centre du site coordonn�es: X',
	'LANG_Location_Y_Label' => 'Y',
	'LANG_LatLong_Bumpf' => '(projection g�ographique LUREF en m�tres)',
	'LANG_Location_Code_Label' => 'Code',
	'Location Comment' => 'Commentaires',
	'LANG_CommonInstructions1'=>'Choose a square (5x5km). This square will then be displayed on the map, along with all existing sites associated with that square.',
	'LANG_CommonParentLabel'=>'Square (5x5km)',
	'LANG_CommonParentBlank'=>'Choose a square',
	'LANG_LocModTool_Instructions2'=>"Pour choisir un site, s�lectionnez l'outil de s�lection et cliquez sur le site sur la carte ou s�lectionnez le site dans la liste ci-dessous. Vous pouvez ensuite modifier des attributs de ce site ou les r�f�rences spatiales de ce site. Vous pouvez d�placer les points s�lectionn�s. Pour supprimer un point, placez la souris sur le point et pressez sur la touche � Delete � ou � d � de votre clavier.<br />Vous ne pouvez pas cr�er de nouveaux sites via ce formulaire, mais uniquement modifier des sites existant.",
	'LANG_DE_Instructions2'=>"Pour choisir un site, s�lectionnez l'outil de s�lection et cliquez sur le site sur la carte ou s�lectionnez le site dans la liste ci-dessous.<br />Vous pouvez ajouter un nouveau site : cliquez sur le bouton � Cr�er un nouveau site � sur la carte, s�lectionnez l'outil � Ajouter un/des point(s) au site � et dessinez le site sur la carte. Chaque site peut �tre compos� de plusieurs points. Vous pouvez �galement d�placer les points s�lectionn�s. Pour supprimer un point, placez la souris sur le point et pressez sur la touche � Delete � ou � d � de votre clavier.<br />Le fait de s�lectionner un site existant supprimera toutes les informations relatives � un nouveau site.<br />Il n'est possible de modifier les d�tails d'un site existant via ce formulaire d'encodage que si vous �tes administrateur ou si vous �tes la seule personne � avoir encod� des donn�es relatives � ce site.",
	'LANG_LocModTool_IDLabel'=>'Ancien nom du site',
	'LANG_DE_LocationIDLabel'=>'Site',
	'LANG_CommonChooseParentFirst'=>'Choose a square first, before picking a site.',
	'LANG_NoSitesInSquare'=>'There are no sites currently associated with this square',
	'LANG_NoSites'=>"Il n'y a actuellement aucune sites d�finis: s'il vous pla�t cr�er un nouveau.",
	'LANG_CommonEmptyLocationID'=>'Choose an existing site',
	'LANG_CommonLocationNameLabel' => 'Nom du site',
	'LANG_LocModTool_NameLabel'=>'Nouveau nom',
	'LANG_LocModTool_DeleteLabel'=>'Supprimer',
	'LANG_LocModTool_DeleteInstructions'=>'Quand un site est supprim�, toutes les donn�es relatives � ce site seront maintenues dans les rapports.',
	'LANG_TooFewPoints' => 'Il ya trop peu de points dans ce polygone - il doit y avoir au moins 3.',
	'LANG_TooFewLinePoints' => 'There are too few points in this line - there must be at least 2.',
	'LANG_CentreOutsideParent'=>'Il ya trop peu de points dans cette ligne - il doit y avoir au moins 2.',
	'LANG_PointOutsideParent'=>'Warning: the point you have created for your site is outside the square.',
	'LANG_LineOutsideParent'=>'Warning: the line you have created for your site has a centre which is outside the square.',
	'LANG_PolygonOutsideParent'=>'Warning: the polygon you have created for new site has a centre which is outside the square.',
	'LANG_ConfirmRemoveDrawnSite'=> "Cette action supprime le site existant que vous avez cr��. Voulez-vous continuer?",
	'LANG_SelectTooltip'=>'Cliquez sur la carte pour s�lectionner un site',
	'LANG_PolygonTooltip'=>'Pour dessiner des polygones pour le site',
	'LANG_LineTooltip'=>'Tracez des lignes pour le site',
	'LANG_PointTooltip'=>'Ajouter des points sur le site',
	'LANG_CancelSketchTooltip'=>'Annuler cette esquisse',
	'LANG_UndoSketchPointTooltip'=>'Annuler le dernier sommet cr��',
	'LANG_StartNewSite'=>'Cr�er un nouveau site',
	'LANG_RemoveNewSite'=>'Supprimer le site s�lectionn� nouvelle',
	'LANG_ZoomToSite'=>'Zoomer sur le site',
	'LANG_ZoomToParent'=>'Zoom to square (5x5km)',
	'LANG_ZoomToCountry'=>'Voir tout le Luxembourg',
	'LANG_Location_Type_Label'=>'Statut du site',
	'LANG_Location_Type_Primary'=>'Submitted',
	'LANG_Location_Type_Secondary'=>'Confirmed',
	'LANG_CommonLocationCodeLabel'=>'Code',
	'LANG_LocationModTool_CommentLabel'=>'Commentaires',
	'LANG_DuplicateName'=>'Attention: il ya un autre endroit de ce nom.',
	'LANG_PointsLegend'=>'Coordonn�es des points individuels',
	'LANG_Grid_X_Label'=>'X',
	'LANG_Grid_Y_Label'=>'Y',
	'Latitude' => 'Coordonn�es: X',
	'Longitude' => 'Y',
	'LANG_DeletePoint'=>'Supprimer ce point',
	'LANG_AddPoint'=>'Ajouter ce point',
	'LANG_HighlightPoint'=>'Mettez en surbrillance ce point',
	'LANG_SHP_Download_Legend'=> 'Fichiers SHP t�l�charger',
	'LANG_Shapefile_Download'=> 'Ce t�l�chargement fournir un zip de fichiers SHP pour les points dans les lieux. Cliquez pour s�lectionner:',
// Georeferencing
	'search' => 'Chercher',
	'LANG_Georef_Label'=>'Chercher la position sur la carte',
	'LANG_Georef_SelectPlace' => 'Choisissez la bonne parmi les localit�s suivantes qui correspondent � votre recherche. (Cliquez dans la liste pour voir l\'endroit sur la carte.)',
	'LANG_Georef_NothingFound' => 'Aucun endroit n\'a �t� trouv� avec ce nom. Essayez avec le nom d\'une localit� voisine.',
	'LANG_PositionOutsideCommune' => "La position que vous avez choisi est en dehors de l'ensemble des communes autoris�es. Vous ne pourrez pas enregistrer cette position.",
	'LANG_CommuneLookUpFailed' => 'Commune Lookup Failed',
// Conditions
	'General' => 'G�n�ral',
	'Physical' => 'Caract�ristiques de la cavit�',
	'Microclimate' => 'Conditions microclimatiques',
	'LANG_Date' => 'Date',
	'Recorder names' => 'Observateur(s)',
	'LANG_RecorderInstructions'=>"(Pour s�lectionner plusieurs observateurs, maintenir la touche CTRL enfonc�e)",
	'LANG_Site_Extra' => "(Num�ro de passage / Nombre de passages durant l'hiver)",
	'Overall Comment' => 'Commentaires',
// Species
	'species_checklist.species'=>'Esp�ces',
	'LANG_Duplicate_Taxon' => 'Vous avez s�lectionn� un taxon qui a d�j� une entr�e.',
	'LANG_SpeciesInstructions'=>"Les esp�ces peuvent �tre ajout�es en utilisant la case d'entr�e ci-dessous. Une seule ligne peut �tre utilis�e par esp�ce ou complexe d'esp�ces.<br />Cliquez sur la croix rouge devant une ligne pour la supprimer.",
	'Add species to list'=>'Ajouter une esp�ce � la liste',
	'Comment' => 'Commentaires',
	'Are you sure you want to delete this row?' => 'Etes-vous s�r de vouloir supprimer cette ligne?',
// Attributes
	'Village' => 'Village / Lieu-dit',
	'Site type' => 'Type de g�te',
	'Site type other' => 'If Others',
	// 'Code GSL' is unchanged in French
	'Depth' => 'Profondeur',
	'Precision' => 'Pr�cision',
	'Development' => 'D�veloppement',
	'Site followup' => 'Pertinence du site pour un suivi r�gulier',
	'Accompanied By' => 'Personne(s) accompagnante(s)',
	'Visit' => 'Visite',
	'Bat visit' => 'Visite',
	'Cavity entrance' => 'Entr�e de la cavit�',
	'Cavity entrance comment' => 'If the closure system is defective',
	'Disturbances' => 'Perturbations',
	'Disturbances other comment' => 'If Others',
	'Human frequentation' => 'Fr�quentation humaine du site',
	'Temp Exterior' => "Temp�rature � l'ext�rieur de la cavit� (Celsius)",
	'Humid Exterior' => "Humidit� relative hors de la cavit� (%)",
	'Temp Int 1' => "Temp�rature � l'int�rieur de la cavit� - A (Celsius)",
	'Humid Int 1' => "Humidit� � l'int�rieur de la cavit� - A (%)",
	'Temp Int 2' => "Temp�rature � l'int�rieur de la cavit� - B (Celsius)",
	'Humid Int 2' => "Humidit� � l'int�rieur de la cavit� - B (%)",
	'Temp Int 3' => "Temp�rature � l'int�rieur de la cavit� - C (Celsius)",
	'Humid Int 3' => "Humidit� � l'int�rieur de la cavit� - C (%)",
	'Positions marked' => 'Emplacement(s) des prises de mesures indiqu�(s) sur le relev� topographique',
	'Reliability' => "Fiabilit� (exhaustivit�) de l'inventaire",
	'Num alive' => 'Vivant(s)',
	'Num dead' => 'Mort(s)',
	'Excrement' => 'Excr�ments',
	'Occurrence reliability' => "Fiabilit�",
	'No observation' => 'Aucune observation',
// Validation
	'validation_required' => 'Veuillez entrer une valeur pour ce champ',
	'validation_max' => "S'il vous pla�t entrer une valeur inf�rieure ou �gale � {0}.",
	'validation_min' => "S'il vous pla�t entrer une valeur sup�rieure ou �gale � {0}.",
	'validation_number' => "S'il vous pla�t entrer un num�ro valide.",
	'validation_digits' => "S'il vous pla�t entrer un nombre entier positif.",
	'validation_integer' => "S'il vous pla�t entrer un nombre entier.",
	'validation_no_observation' => "Cette option doit �tre coch�e si et seulement si il n'existe aucun donn�e dans le tableau ci-dessus.",
	'validation_fillgroup'=>"S'il vous pla�t d�finissez un de ces trois options."
);