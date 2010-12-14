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
 * Language terms for the pollenator insect form.
 *
 * @package	Client
 */
$custom_terms = array(
	'LANG_Invocation_Error' => "Avertissement: GET valide les param�tres dans l'URL"
	,'LANG_Insufficient_Privileges' => "Vous n'avez pas de privil�ges suffisants pour acc�der � cette page."
	,'LANG_Please_Refresh_Page' => "Une erreur s'est produite. S'il vous pla�t, actualisez la page."
	
	,'LANG_Main_Title' => 'Les Collections'
	,'LANG_Enter_Filter_Name' => 'Entrer un nom pour ce filtre'
	,'LANG_Save_Filter_Button' => 'Enregistrer'
	,'LANG_Collection' => 'Retour � la Collection'
	,'LANG_Previous' => 'Pr�c�dent'
	,'LANG_Next' => 'Suivant'
	,'LANG_Add_Preferred_Insect' => 'enregistrer dans mes insectes prefer�s'
	,'LANG_Validate' => 'Valider'
	,'LANG_Add_Preferred_Collection'  => 'Enregistrer dans mes collection prefer�s'
	,'LANG_List' => 'Retour � la Liste'
	,'LANG_No_Collection_Results' => 'Aucune collection ne correspond � cette recherche.'
	,'LANG_No_Insect_Results' => 'Aucun insecte ne correspond � cette recherche.'
	
	,'LANG_Indentification_Title' => 'Identification'
	,'LANG_Doubt' => "�mettre un doute sur l'identification"
	,'LANG_Doubt_Comment' => 'Commentez votre doute :'
	,'LANG_Default_Doubt_Comment' => "J'ai exprim� un doute sur cette identification parce que..."
	,'LANG_New_ID' => 'Proposer une nouvelle identification'
	,'LANG_Launch_ID_Key' => "Lancer la cl� d'identification"
	,'LANG_Cancel_ID' => "Abandonner la cl� d'identification"
	,'LANG_Taxa_Returned' => "Taxons retourn� par la cl� d'identification:"
	,'LANG_ID_Unrecognised' => 'Les suivants ne sont pas reconnus: '
	,'LANG_Taxa_Unknown_In_Tool' => 'Taxon inconnu de la cl�'
	,'LANG_Det_Type_Label' => 'Identification'
	,'LANG_Det_Type_C' => 'Correct, valid�'
	,'LANG_Det_Type_X' => 'Non identifi�'
	,'LANG_Choose_Taxon' => "Choisissez un taxon dans la liste"
	,'LANG_Identify_Insect' => 'Indiquer le nom de cet insecte:'
	,'LANG_More_Precise' => 'D�nomination pr�cise'
	,'LANG_ID_Comment' => 'Commentez �ventuellement votre identification :'
	,'LANG_Default_ID_Comment' => "Precisions sur ma nouvelle identification..."
	,'LANG_Flower_Species' => "Nom de la Fleur"
	,'LANG_Flower_Name' => "Nom de la Fleur"
	,'LANG_Insect_Species' => "Nom de l'insecte"
	,'LANG_Insect_Name' => "Nom de l'insecte"
	,'LANG_History_Title' => 'Ancienne identification'
	,'LANG_Last_ID' => 'Derni�re(s) identification(s)'
	,'LANG_Display' => 'Afficher'
	,'LANG_No_Determinations' => 'Aucun identifications enregistr�e.'
	,'LANG_No_Comments' => 'Aucun commentaire enregistr�.'
	
	,'LANG_Filter_Title' => 'Filtres'
	,'LANG_Name_Filter_Title' => 'Pseudo'
	,'LANG_Name' => "Pseudo"
	,'LANG_Date_Filter_Title' => 'Date'
	,'LANG_Flower_Filter_Title' => 'Fleur'
	,'LANG_Insect_Filter_Title' => 'Insecte'
	,'LANG_Conditions_Filter_Title' => "Conditions d'observation"
	,'LANG_Location_Filter_Title' => 'Localisation'
	,'LANG_Georef_Label' => '<strong>Localiser la fleur</strong> : utiliser les champs et/ou la carte ci-dessous'
	,'LANG_Georef_Notes' => "(Le nom d'un village, d'une ville, d'une r�gion, d'un d�partement ou un code postal.)"
    ,'msgGeorefSelectPlace' => "S�lectionnez dans les endroits suivants qui correspondent � vos crit�res de recherche, puis cliquez sur la carte pour indiquer l'emplacement exact"
    ,'msgGeorefNothingFound' => "Aucune ville portant ce nom n'a �t� trouv�e. Essayez le nom d'une ville proche."
	,'LANG_INSEE' => 'No INSEE.'
	,'LANG_NO_INSEE' => "Il n'ya pas de zone avec ce num�ro INSEE (neuf ou ancien)."
	,'LANG_Search_Insects' => 'Rechercher des Insectes'
	,'LANG_Search_Collections' => 'Rechercher des collections'
	,'LANG_Insects_Search_Results' => 'Insectes'
	,'LANG_Collections_Search_Results' => 'Collections'
		
	,'LANG_User_Link' => 'TOUTES SES COLLECTIONS DANS LES GALERIES'
	,'LANG_Additional_Info_Title' => 'Informations Compl�mentaires'
	,'LANG_Date' => 'Date'
	,'LANG_Time' => 'Heure'
	,'LANG_To' => ' a '
	,'Sky' => 'Ciel : couverture nuageuse '
	,'Temperature' => 'Temp�rature '
	,'Wind' => 'Vent '
	,'Shade' => "Fleur � l'ombre "
	,'Flower Type' => "Il s'agit d'une fleur "
	,'Habitat' => "Il s'agit d'une habitat "
	
	,'LANG_Comments_Title' => 'COMMENTAIRES DES INTERNAUTES'
	,'LANG_New_Comment' => 'Ajouter un commentaire'
	,'LANG_Username' => 'Pseudo'
	,'LANG_Email' => 'EMAIL'
	,'LANG_Comment' => 'Commentaire'
	,'LANG_Submit_Comment' => 'Ajouter'
	,'LANG_Comment_By' => "par : "
	,'LANG_Reset_Filter' => 'R�initialiser'
	
	,'validation_required' => "Ce champ est obligatoire"
	,'Yes' => 'Oui'
	,'No' => 'Non'
	,'close'=>'Fermer'	
  	,'search'=>'Chercher'
  	,'click here'=>'Cliquez ici'
	,'LANG_Unknown' => '?'
	,'LANG_Dubious' => '!'
	,'LANG_Confirm_Express_Doubt' => 'Etes-vous s�r de vouloir �mettre un doute au sujet de cette identification?'
	,'LANG_Doubt_Expressed' => "Cette personne a exprim� des doutes au sujet de cette identification."
	,'LANG_Determination_Valid' => 'Cette identification a �t� effectu�e par un expert. Elle est consid�r�e comme valide.'
	,'LANG_Determination_Incorrect' => 'Cette identification a �t� signal�e comme incorrecte.'
	,'LANG_Determination_Unconfirmed' => 'Cette identification a �t� marqu�e comme non confirm�es.'
	,'LANG_Determination_Unknown' => "Le taxon n'est pas connu de la cl� d'identification."
	,'LANG_Max_Features_Reached' => "Le nombre de r�sultats retourn�s a d�pass� le nombre maximal autoris�. La liste sera abr�g�e."
	,'LANG_General' => 'G�n�ral'
	,'LANG_Created_Between' => 'Cr�� entre'
	,'LANG_And' => 'et'
	,'LANG_Or' => 'ou'
	,'LANG_Indicia_Warehouse_Error' => 'Erreur renvoy�e par Indicia Warehouse'
	,'loading' => 'Chargement'
	
);