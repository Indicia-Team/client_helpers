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
 * Language terms for the pollenators form.
 *
 * @package	Client
 */
$custom_terms = array(
	'LANG_Insufficient_Privileges' => "Vous n'avez pas de privil�ges suffisants pour acc�der � la page 'Cr�er d'un Collection'"
	,'LANG_Collection_Name_Label' => 'Nommer votre collection '
	,'Protocol' => 'Choisir un protocole '
	,'LANG_Modify' => 'MODIFIER'
	,'LANG_Reinitialise' => 'INITIALISER'
	,'LANG_Collection_Details' => 'Nouvelle collection'
	,'LANG_Protocol_Title_Label' => 'protocole'
	,'LANG_Validate' => 'VALIDER'
	,'LANG_Unable_To_Reinit' => 'Impossible de r�initialiser parce que les valeurs existantes ne passent pas la validation'
	,'LANG_Confirm_Reinit' => 'Etes-vous s�r de vouloir r�initialiser? Toutes les donn�es contre cette collection sera supprim�.'
	
	,'LANG_Flower_Station' => "VOTRE STATION FLORALE"
	,'LANG_Upload_Flower' => "Charger l'image de la Fleur"
	,'LANG_Identify_Flower' => 'Indiquer le nom de cette fleur'
	,'LANG_ID_Flower_Later' => "Vous preferez l'identifier plus tard:"
	,'LANG_Flower_Species' => "Vous connaissez le taxon correspondant � cette fleur"
	,'LANG_Flower_ID_Key_label' => "Vous ne connaissez pas le nom de cette fleur"
	,'LANG_Launch_ID_Key' => "Lancer la cl� d'identification"
	,'LANG_Cancel_ID' => "Abandonner l'outil d'identification"
	,'LANG_Choose_Taxon' => "Choisissez un taxon dans la Liste"
	,'LANG_Upload_Environment' => "Charger l'image de son environnement"
	,'LANG_Georef_Label' => 'Nom'
	,'LANG_Georef_Notes' => '(Ce peut �tre un village ou ville, r�gion, d�partement ou code postal.)'
	,'LANG_Location_Notes' => 'Localiser la fleur : placer votre rep�re sur la carte ou utilise les champs ci-dessous :'
	,'LANG_Or' => 'ou :'
	,'LANG_INSEE' => 'INSEE No.'
	,'LANG_NO_INSEE' => "Il n'ya pas de zone avec ce num�ro INSEE (neuf ou ancien)."
	,'LANG_Lat' => 'Lat./Long.'
	,'Flower Type' => "Il s'agit d'une fleur"
	,'Habitat' => "Il s'agit d'un habitat"
	,'Nearest House' => "Distance approximative entre votre fleur et la ruche d'abeille domestique la plus proche (m�tre)"
	,'LANG_Validate_Flower' => 'VALIDER VOTRE STATION FLORALE'
	,'LANG_Must_Provide_Pictures' => "Les images doivent �tre t�l�charg�es pour la fleur et de l'environnement"
	,'LANG_Must_Provide_Location' => 'Un emplacement doit �tre choisi'
	
	,'LANG_Sessions_Title' => 'VOS SESSIONS'
	,'LANG_Session' => 'Session'
	,'LANG_Date' => 'Date'
	,'LANG_Validate_Session' => 'VALIDER LA SESSION'
	,'LANG_Add_Session' => 'AJOUTER UNE SESSION'
	,'LANG_Delete_Session' => 'supprimer'
	,'LANG_Cant_Delete_Session' => "La session ne peut pas �tre supprim� car il ya encore des insectes qui y sont associ�s."
	,'LANG_Confirm_Session_Delete' => 'Etes-vous s�r de vouloir supprimer cette session?'
	,'Start Time' => 'Heure de d�but'
	,'End Time' => 'Heure de fin'
	,'Sky' => 'Ciel'
	,'Temperature Bands' => 'Temp�rature'
	,'Wind' => 'Vent'
	,'In Shade' => "Fleur � l�ombre"
	,'Nearest Hive' => "Distance approximative entre votre fleur et la ruche d'abeilles domestiques la plus proche"
	
	,'LANG_Photos' => "VOS PHOTOS D'INSECTE"
	,'LANG_Photo_Blurb' => 'T�l�charger ou modifier vos observations.'
	,'LANG_Upload_Insect' => "Charger l'image d'insecte"
	,'LANG_Identify_Insect' => 'Indiquer le nom de cet insecte:'
	,'LANG_Insect_Species' => "Vous connaissez le taxon correspondant � cet insecte"
	,'LANG_Insect_ID_Key_label' => "Vous ne connaissez pas le nom de cet insecte"
	,'LANG_ID_Insect_Later' => "Vous preferez l'identifier plus tard:"
	,'LANG_Comment' => 'Commentaire'
	,'Number Insects' => "Nombre d'insectes de le m�me espace au moment pr�cis o� vous preniez cette photo"
	,'Foraging'=> "Cochez cette case si vous avez pris en photo cet insecte allieurs que sur la fleur, mais que vous l'y avez vu butiner"
	,'LANG_Validate_Insect' => "VALIDER L'INSECTE"
	,'LANG_Validate_Photos' => 'VALIDER VOS PHOTOS'
	,'LANG_Must_Provide_Insect_Picture' => 'Une image doit �tre t�l�charg�e pour les insectes'
	,'LANG_Confirm_Insect_Delete' => 'Etes-vous s�r de vouloir supprimer cet insecte?'
	,'LANG_Delete_Insect' => 'Supprimer des insectes'
	
	,'LANG_Can_Complete_Msg' => "Vous avez identifi� la fleur et un nombre suffisant d'insectes, vous pouvez maintenant cl�turer la collection"
	,'LANG_Cant_Complete_Msg' => "Vous avez une ou l'autre: pas identifi� la fleur, et / ou non identifi� un nombre suffisant d'insectes. Vous devez corriger avant que vous pouvez cl�turer la collection."
	,'LANG_Complete_Collection' => 'Cl�turer la collection'
	,'LANG_Trailer_Head' => 'Apr�s cl�ture'
	,'LANG_Trailer_Point_1' => "vous ne pourrez plus ajouter d'insectes � votre collection ; les avez-vous tous t�l�vers�?"
	,'LANG_Trailer_Point_2' => "vous ne pouvez plus modifier les diff�rentes valeurs d�crivant cette station floral, sessions et insectes."
	,'LANG_Trailer_Point_3' => "vous pouvez modifier l'identification des insectes dans �Mes collections�"
	,'LANG_Trailer_Point_4' => "vous pourrez cr�er une nouvelle collection"
	
	,'validation_required' => "Ce champ est obligatoire"
	,'Yes' => 'Oui'
	,'No' => 'Non'
	,'LANG_Help_Button' => '?'
	
	,'LANG_Final_1' => 'Cette collection a �t� enregistr�e et ajout�e � votre ensemble de collections'
	,'LANG_Final_2' => "Cette collection peut �tre consult�e par rubrique �Mes collections�, o� vous pouvez changer l'identification de vos insectes"
	,'LANG_Consult_Collection' => 'Consulter cette collection'
	,'LANG_Create_New_Collection' => 'Cr�er la nouvelle collection'
	
	,'LANG_Indicia_Warehouse_Error' => 'Erreur renvoy�e par Indicia Warehouse'
	,'loading' => 'Chargement'
	
);