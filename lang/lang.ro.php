<?php

	$about = array(
		'name' => 'Romana',
		'author' => array(
			'name' => 'Vlad Ghita',
			'email' => 'vlad_micutul@yahoo.com',
			'website' => 'http://www.xanderadvertising.com'
		),
		'release-date' => '2012-05-14'
	);

	/**
	 * Frontend Localisation
	 */
	$dictionary = array(

		'%1$s %2$s at %3$s. <a href="%4$s" accesskey="c">Create another?</a> <a href="%5$s" accesskey="a">%6$s</a>' => 
		'%1$s %2$s la %3$s. <a href="%4$s" accesskey="c">Creaţi alta?</a> <a href="%5$s" accesskey="a">%6$s</a>',

		'<code>%1$s</code>: Column `translations` for `tbl_pages` already exists. Uninstall extension and re-install it after.' => 
		'<code>%1$s</code>: Coloana `translations` din `tbl_pages` există deja. Dezinstalaţi extensia şi reinstalaţi.',

		'<code>%1$s</code>: Failed to remove <code>%2$s</code> folder.' => 
		'<code>%1$s</code>: Ştergerea directorului <code>%2$s</code> nu a reuşit.',

		'<code>%1$s</code>: MySQL error %d occured when adding column `translation` to `tbl_pages`. Installation aborted.' => 
		'<code>%1$s</code>: Eroarea MySQL %d a apărut la adăugarea coloanei `translation` la tabelul `tbl_pages`. Instalare anulată.',

		'<code>%1$s</code>: No languages have been set on Preferences page. Please <a href="%2$s">review them</a>.' => 
		'<code>%1$s</code>: Nu au fost setate limbi pe pagina de Preferinte. <a href="%2$s">Verificati-le</a>.',

		'<code>%1$s</code>: Translation folders not found.' => 
		'<code>%1$s</code>: Folderele cu traduceri lipsesc..',

		'An error occurred while processing this form. <a href="#error">See below for details.</a>' => 
		'O eroare a intervenit la procesarea formularului. <a href="#error">Vedeti mai jos pentru detalii.</a>',

		'Are you sure you want to delete the selected translations?' => 
		'Sigur doriti stergerea traducerilor selectate?',

		'Comma separated list of supported language codes.' => 
		'Codurile limbilor separate prin virgula.',

		'Consolidate translations' => 
		'Consolideză traducerile',

		'Context: ' => 
		'Context: ',

		'Create Translation' => 
		'Crează traducere',

		'Create a new translation file' => 
		'Crează o noua traducere',

		'Default storage format' => 
		'Metoda de stocare implicită',

		'Frontend Translations' => 
		'Traduceri Frontend',

		'Handle is a required field' => 
		'Handle este un câmp obligatoriu',

		'Handle is a required field.' => 
		'Handle este câmp obligatoriu',

		'In FLPageManager it died trying to get a list of Pages from Database. Poor fellow.' => 
		'A murit în FLPageManager încercând sa aducă o listă de Pagini din Baza de date. Sărăcuţul.',

		'Language codes' => 
		'Codurile de limba',

		'Main language' => 
		'Limba principala',

		'No Pages Found' => 
		'Lipsă Pagini',

		'No translations found. <a href="%s">Create new?</a>' => 
		'Lipsa traduceri. <a href="%s">Creati una?</a>',

		'Normal' => 
		'Normal',

		'Page' => 
		'Pagină',

		'Please fill at least one valid language code.' => 
		'Introduceti cel putin un cod valid.',

		'Reference language' => 
		'Limba de referinţă',

		'Reference value' => 
		'Referinţă',

		'Save changes' => 
		'Salvează modificările',

		'Select the main language of the site.' => 
		'Selectati limba principala a siteului',

		'Set type of Translation. <b>%1$s</b> for Symphony Pages, <b>%2$s</b> otherwise.' => 
		'Selectare tip Traducere. <b>%1$s</b> pentru Paginile Symphony, altfel <b>%2$s</b>.',

		'Storage format' => 
		'Metoda de stocare',

		'Storage format to use for translations.' => 
		'Metoda de stocare folosită pentru traduceri.',

		'The translation file you requested to edit does not exist.' => 
		'Traducerea pe care aţi cerut-o nu există.',

		'Translation' => 
		'Traducere',

		'Translation could not be created.' => 
		'Traducerea nu poate fi creată.',

		'Translation not found' => 
		'Traducere inexistentă',

		'Translations' => 
		'Traduceri',

		'Translations synchronisation failed. Please contact site administrator.' => 
		'Sincronizarea Traducerilor a eşuat. Contactaţi administratorul siteului.',

		'Unknown Lang' => 
		'Limbă necunoscută',

		'Update Translations' => 
		'Actualizează traducerile',

		'View all Translations' => 
		'Vedeţi toate Traducerile',

		'You asked to create a Translation but there are not languages set by your Language Driver.' => 
		'Aţi cerut crearea unei traduceri dar nu există nicio limbă setată de către Driverul de Limbă.',

		'created' => 
		'creată',

		'updated' => 
		'actualizată',

		'<code>%1$s</code>: Failed to remove `translation` column from `tbl_pages`. Perhaps it didn\'t existed at all.' =>
		'<code>%1$s</code>: Eroare la inlaturarea coloanei `translations` din `tbl_pages`. Posibil nici sa nu fi existat.',

		'<code>%1$s</code>: Reference language code <code>%2$s</code> is not supported.' => 
		'<code>%1$s</code>: Limba de referinta <code>%2$s</code> nu este suportata.',

		'Please review settings' => 
		'Verificati setarile',

		'<code>%1$s</code>: Translation folder couldn\'t be created at <code>%2$s</code>.' => 
		'<code>%1$s</code>: Eroare la crearea folderului cu traduceri la <code>%2$s</code>.',

		'<code>%1$s</code>: Storage directory <code>%2$s</code> for <code>%3$s</code> storage format doesn\'t exist.' => 
		'<code>%1$s</code>: Biblioteca <code>%2$s</code> pentru formatul <code>%3$s</code> nu exista.',

		'Language translations that will be used as reference when updating other languages\' translations.' => 
		'Traducerile care vor fi folosite ca referinta la sincronizarea celorlalte traduceri.',

		'Check this to preserve Translations for removed languages.' => 
		'Bifaţi aceasta pentru a păstra traducerile limbilor înlăturate.',

		'Invalid language code.' => 
		'Cod de limba invalid.',

		'Invalid storage format.' => 
		'Format de stocare invalid.',

	);
