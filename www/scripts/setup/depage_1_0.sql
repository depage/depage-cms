# CocoaMySQL dump
# Version 0.5
# http://cocoamysql.sourceforge.net
#
# Host: localhost (MySQL 4.0.21 Complete MySQL by Server Logistics-log)
# Database: depage_1_0
# Generation Time: 2005-08-30 09:40:19 +0200
# ************************************************************

# Dump of table tt_auth_edits
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tt_auth_edits`;

CREATE TABLE `tt_auth_edits` (
  `sid` varchar(16) NOT NULL default '',
  `type` enum('page','colors','template','newnode','settings') NOT NULL default 'page',
  `id` int(11) default '0',
  `value` mediumtext NOT NULL,
  `changed` enum('true','false') NOT NULL default 'false',
  PRIMARY KEY  (`sid`)
) TYPE=MyISAM COMMENT='geronimo db 0.9.7 prerelease';



# Dump of table tt_auth_sessions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tt_auth_sessions`;

CREATE TABLE `tt_auth_sessions` (
  `sid` varchar(16) NOT NULL default '',
  `userid` int(11) NOT NULL default '0',
  `project` varchar(50) default NULL,
  `ip` varchar(16) default NULL,
  `last_update` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`sid`,`userid`)
) TYPE=HEAP COMMENT='depage 0.9.14';



# Dump of table tt_auth_sessions_win
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tt_auth_sessions_win`;

CREATE TABLE `tt_auth_sessions_win` (
  `sid` varchar(16) NOT NULL default '0',
  `wid` varchar(16) NOT NULL default '',
  `port` int(10) unsigned NOT NULL default '0',
  `type` enum('main') NOT NULL default 'main',
  PRIMARY KEY  (`sid`,`wid`)
) TYPE=HEAP COMMENT='depage 0.9.14';



# Dump of table tt_auth_updates
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tt_auth_updates`;

CREATE TABLE `tt_auth_updates` (
  `id` int(11) NOT NULL auto_increment,
  `sid` varchar(16) NOT NULL default '',
  `message` mediumtext NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;



# Dump of table tt_env
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tt_env`;

CREATE TABLE `tt_env` (
  `name` varchar(40) NOT NULL default '',
  `value` varchar(255) default NULL,
  PRIMARY KEY  (`name`)
) TYPE=HEAP COMMENT='depage 0.9.14';

INSERT INTO `tt_env` (`name`,`value`) VALUES ("pocket_server_running","0");


# Dump of table tt_interface_text
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tt_interface_text`;

CREATE TABLE `tt_interface_text` (
  `name` varchar(40) NOT NULL default '',
  `en` text NOT NULL,
  `de` text NOT NULL,
  PRIMARY KEY  (`name`)
) TYPE=MyISAM COMMENT='depage 0.9.14';

INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("buttontext_tree_releasetemp","release XSLT","XSLT freigeben");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("buttontip_tree_delete","delete","löschen");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("buttontip_tree_duplicate","duplicate","duplizieren");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("buttontip_tree_new","add","hinzufügen");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("buttontip_tree_newfolder","new folder","Neuer Ordner");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("buttontip_tree_releasetemp","release templates to be available for all user","Vorlagen für alle Benutzer freigeben");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("error_node_deleted","Node has been deleted by another user.","Element ist von einem anderen Benutzer gelöscht worden.");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("error_parsexml-10","An end-tag was encountered without a matching start-tag.","Ein End-Tag besitzt keine passenden Start-Tag.");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("error_parsexml-2","A CDATA section is not properly terminated.","Eine CDATA Sektion ist nicht korrekt geschlossen.");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("error_parsexml-3","The XML declaration is not properly terminated.","Die XML Deklaration ist nicht korrekt geschlossen.");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("error_parsexml-4","The DOCTYPE declaration is not properly terminated.","Die DOCTYPE Deklaration ist nicht korrekt geschlossen.");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("error_parsexml-5","A comment is not properly terminated.","Ein Kommentar ist nicht korrekt geschlossen.");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("error_parsexml-6","An XML element is malformed.","Ein XML Element ist nicht korrekt geformt.");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("error_parsexml-7","The Flashplayer is out of memory.","Dem Flashplayer steht nicht genügend Speicher zur Verfügung.");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("error_parsexml-8","An attribute value is not properly terminated.","Ein Attribut-Eintrag ist nicht korrekt geschlossen.");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("error_parsexml-9","A start-tag is not matched with an end-tag.","Ein Start-Tag besitzt keine passenden End-Tag.");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("error_prop_xslt_template","An error occured while parsing the template:","Ein Fehler ist beim Verarbeiten der Vorlage aufgetreten:");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("filetip_filedate","last changed: ","Änderungsdatum: ");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("filetip_filesize","size: ","Größe: ");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("filetip_imagesize","dimensions: ","Dimensionen: ");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("msg_delete_from_tree","Do you want to delete \"%name%\"?","Möchten Sie \"%name%\" wirklich löschen?");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("name_tree_project_settings","[project settings]","[Projekteinstellungen]");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("output_type_none","none","kein");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_page_colorscheme","colorscheme","Farbschema");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_page_file","page type","Dokumenttyp");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_page_navigation","navigation","Navigation");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_page_title","title","Titel");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_proj_colorscheme","colors","Farben");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_proj_language","short name","Kürzel");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_proj_navigation","short name","Kürzel");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_edit_plain_source","source code","Quelltext");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_pg_template","type","Typ");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_xslt_newnode","template for new elements","Vorlage für neue Elemente");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_xslt_template","xsl-template","XSL-Vorlage");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_xslt_valid_parent","valid parents","zulässige Eltern");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_page_file_file_name_auto","automatic","automatisch");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_page_file_multilang","multiple languages","mehrsprachig");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_colorscheme_newcolor","_new_color","_neue_farbe");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_xslt_active","active","aktiv");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("register_name_colors","colors","Farben");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("register_name_edit_pages","edit pages","Seiten editieren");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("register_name_files","files","Dateien");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("register_name_login"," . login"," . login");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("register_name_settings","settings","Einstellungen");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("register_name_templates","templates","Vorlagen");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("register_tip_colors","edit colors and colorschemes...","Farben und Farbschemata bearbeiten...");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("register_tip_edit_pages","add, edit and delete pages...","Seiten hinzufügen, bearbeiten und löschen...");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("register_tip_files","file-library","Datei-Bibliothek");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("register_tip_settings","edit settings...","Einstellungen bearbeiten...");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("register_tip_templates","edit templates...","Vorlagen bearbeiten...");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("start_config_loaded","configuration loaded.","Einstellungen geladen.");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("start_loaded","interface loaded.","Interface geladen.");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("start_loaded_project","project loaded.","Projekt geladen.");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("start_loading_project","loading project...","Lade Projekt...");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("start_login_button","login","Anmelden");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("start_login_explain_pass","password","Kennwort");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("start_login_explain_user","user","Benutzername");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("start_login_wrong_login","Login failed. Please try again.","Das Login ist nicht korrekt. Bitte versuchen Sie es noch einmal.");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("start_pocket_connected","connection established.","Verbindung hergestellt.");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("start_pocket_reconnect","connecting to server...","verbinde zu Server...");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("start_preload","loading interface...","Lade Interface...");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_after_copy","(copy)","(Kopie)");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_headline_colors","colorschemes","Farbschemata");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_headline_pages","site","Struktur");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_headline_page_data","document","Dokument");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_headline_files","file-library","Datei-Bibliothek");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_headline_tpl_newnodes","element-templates","Element-Vorlagen");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_headline_settings","settings","Einstellungen");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_headline_tpl_templates","XSL-templates","XSL-Vorlagen");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_name_color_global","global colors","globale Farben");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_name_new_colorscheme","colorscheme","Farbschema");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_name_new_folder","folder","Ordner");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_name_new_page","page","Seite");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_name_new_page_empty","[empty page]","[leere Seite]");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_name_new_template","template","Vorlage");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_name_separator"," "," ");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_name_settings_languages","languages","Sprachen");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_name_settings_navigation","navigation","Navigation");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_name_settings_publish","Publish","Veröffentlichen");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_nodata"," loading..."," lade...");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_edit_text_formatted","text","Text");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("buttontip_format_bold","bold","fett");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("buttontip_format_italic","italic","kursiv");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("buttontip_format_link","link","Link");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_edit_img","image","Bild");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_img_filepath","path to image","Pfad zum Bild");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_img_choose","...","...");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("auth_no_right","Sorry, you don\'t have the authentification to change \"%name%\".","Sie haben leider nicht die benötigten Benutzerrechte um \"%name%\" zu ändern.");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_edit_text_headline","headline","Überschrift");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_text_formatted_loading","please wait ... initializing text-styles","bitte warten ... initialisiere Textformate");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("buttontext_tree_upload","upload...","upload...");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("buttontip_tree_upload","upload new file","neue Datei hochladen");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("inhtml_require_title","requirements","Systemanforderungen");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("inhtml_noscript","You need to activate Javascript, to use %app_name%.","Sie müssen Javascript aktivieren, um %app_name% benutzen zu können.");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("inhtml_needed_flash","You need the Macromedia Flash Player%minversion%, to use %app_name%.","Sie benötigen den Macromedia Flash Player%minversion%, um %app_name% nutzen zu können.");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("msg_choose_img","Please, choose an image:","Bitte wählen sie ein Bild aus:");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_name_new_new_node","element","Element");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_name_untitled","(untitled)","(unbenannt)");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("inhtml_dialog_upload_text","Please choose the files, you want to upload to the file-library to \'%path%\'. <br/><br/><b>Attention: Existing file will be overwritten without confimation!</b>","Bitte wählen Sie die Dateien aus, die sie in die Datei-Bibliothek nach \'%path%\' hochladen möchten. <br/><br/><b>Achtung: Existierende Dateien werden ohne weitere Abfrage überschrieben!</b>");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("inhtml_dialog_upload_button","upload...","upload...");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("inhtml_dialog_upload_title","%app_name% upload","%app_name% upload");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("inhtml_preview_error","Error in transformation","Fehler beim Transformieren");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_name_settings_bak","backup","Backup");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_name_settings_bak_backup","backup","Sichern");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_name_settings_bak_restore","restore","Wiederherstellen");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_proj_bak_backup_auto","backup","Sichern");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_proj_bak_restore_data","database","Datenbank");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_proj_bak_backup_man","manually","Manuell");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_proj_bak_restore_lib","file-library","Datei-Bibliothek");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("date_time","h","Uhr");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_bak_date_every_day","every day","jeden Tag");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_bak_date_every_week","every week","jede Woche");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_bak_automatic","automatic Backup","automatische Sicherung");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_bak_date_every_month","every month","jeden Monat");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_bak_backup_type_all","full backup","vollständiges Backup");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_bak_backup_type_data","data only","nur Datenbank");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_bak_backup_type_lib","file-library only","nur Datei-Bibliiothek");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("date_time_at","at","um");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_bak_restore_content","documents","Dokumente");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_bak_backup_button_start","backup now","Jetzt sichern");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_bak_restore_button_start","restore","Wiederherstellen");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_bak_restore_type_replace","replace existing files","vorhandene Dateien ersetzen");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_bak_restore_type_clear","clear file-library first","Datei-Bibliothek vorher leeren");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_bak_backup_progress","%description%<br>%percent% done<br>remaining: %remaining%","%description%<br>%percent% abgeschlossen<br>Restdauer: %remaining%");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("time_sec","seconds","Sekunden");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("time_min","minutes","Minuten");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("time_calculating","(calculating)","(wird berechnet)");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("all_comment","comment","Kommentar");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_img_href","link","Verknüpfung");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_img_alt","description","Beschreibung");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_img_altdesc","alt","alt");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("msg_choose_page","Please, choose a page to link to:","Bitte wählen sie die Seite aus, die verlinkt werden soll:");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_name_metatags","Meta*","Meta*");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_page_desc","description","Beschreibung");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_page_linkdesc","linkinfo","Linkinfo");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("start_loading_version","<b>%app_name%</b><br>[%app_version%]<br><br>preloading...<br>%loading%","<b>%app_name%</b><br>[%app_version%]<br><br>lade...<br>%loading%");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("register_tip_preview","preview","Vorschau aktualisieren");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("register_preview_menu_preview","preview","Vorschau");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("register_preview_menu_feedback","enable feedback","aktiviere Feedback");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("register_preview_menu_type","template set","Vorlagen-Set");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("register_preview_menu_man","manually","manuell");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("register_preview_menu_auto_choose","after choosing","bei Auswahl");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("register_preview_menu_auto_choose_save","after choosing/changing","bei Auswahl/Änderungen");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("register_preview_menu_headline","preview","Vorschau");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_edit_a","link","Link");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_a_name","name","Name");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("buttontip_link_target","linktarget","Linkziel");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("button_link_target_self","existing window","vorhandenes Fenster");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("button_link_target_blank","new window","neues Fenster");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_page_date","last change","letzte Änderung");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("date_format","%D%, %d% %MM% %y% at %h%:%m%:%s%","%D%, %d%. %MM% %y% um %h%:%m%:%s%");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("date_month_1","Jan","Jan");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("date_month_2","Feb","Feb");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("date_month_3","Mar","Mär");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("date_month_4","Apr","Apr");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("date_month_5","May","Mai");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("date_month_6","Jun","Jun");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("date_month_7","Jul","Jul");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("date_month_8","Aug","Aug");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("date_month_9","Sep","Sep");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("date_month_10","Oct","Okt");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("date_month_11","Nov","Nov");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("date_month_12","Dec","Dez");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("date_day_0","Sun","So");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("date_day_1","Mon","Mo");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("date_day_2","Tue","Di");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("date_day_3","Wed","Mi");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("date_day_4","Thu","Do");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("date_day_5","Fri","Fr");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("date_day_6","Sat","Sa");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("tree_name_settings_template_sets","template-sets","Vorlagen-Sets");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_proj_publish","publish","Veröffentlichen");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_template_set_indent","indent source","Quelltext einrücken");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_proj_template_set_encoding","encoding","Kodierung");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_proj_template_set_method","output method","Ausgabeart");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_publish_folder_targetpath","target path","Zielpfad");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_publish_folder_user","user","Benutzername");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_publish_folder_pass","password","Kennwort");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("inhtml_connection_closed","connection to the server has closed unexpectedly.","Die Verbindung zum Server wurde unerwartet unterbrochen.");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_publish_folder_button_start","publish now","Jetzt veröffentlichen");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_publish_folder_progress","%description%<br>%percent% done<br>remaining: %remaining%","%description%<br>%percent% abgeschlossen<br>Restdauer: %remaining%");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("inhtml_connection_closed_title","connection lost","Verbindung verloren");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("inhtml_require_javascript","You have to activate javascript to use %app_name%.","Sie müssen Javascript aktivieren, um %app_name% nutzen zu können");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("task_publish_caching_templates","caching templates","bereite Templates vor");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("task_publish_caching_colorschemes","caching colorschemes","bereite Farbschemata vor");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("task_publish_caching_languages","caching languages","bereite Sprachen vor");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("task_publish_caching_navigation","caching navigation","bereite Navigation vor");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("task_publish_caching_settings","caching settings","bereite Einstellungen vor");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("task_publish_caching_pages","caching pages","bereite Seiten vor");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("task_publish_processing_pages","publishing pages","aktualisiere Seiten");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("task_publish_processing_indexes","publishing index","aktualisiere Index");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("task_publish_processing_lib","publishing library","aktualisiere Bibliothek");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("task_backup_settings","backup settings","sichere Einstellungen");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("task_backup_content","backup content","sichere Inhalte");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("task_backup_colorschemes","backup colorschemes","sichere Farbschemata");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("task_backup_templates","backup xslt templates","sichere XSLT-Vorlagen");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("task_backup_newnodes","backup element templates","sichere Element-Vorlagen");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("task_backup_lib","backup library","sichere Bibliothek");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_bak_restore_overwrite","clean up library before restoring","Dateibibliothek vor der Wiederherstellung löschen");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_bak_restore_db_settings","settings","Einstellungen");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_bak_restore_db_content","pages","Seiten");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_bak_restore_db_colorschemes","colorschemes","Farbschemata");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_tt_bak_restore_db_templates","templates","Vorlagen");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("error_ftp","Filetransfer Error:<br>","Fehler beim Dateitransfer:<br>");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("error_ftp_login","Could not login as:","Konnte nicht einloggen als:");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("error_ftp_connect","Could not connect to:","Konnte nicht verbinden zu:");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("error_ftp_write","Could not write to:","Konnte folgende Datei nicht schreiben:");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_edit_date","date","Datum");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_description","description","Beschreibung");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("prop_name_title","title","Titel");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("msg_choose_file_link","Please, choose a file to link to:","Bitte wählen sie die Datei aus, die verlinkt werden soll:");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("date_format_short","%M%/%d%/%y%","%d%.%M%.%y%");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("start_projectdata","project data","Projektdaten");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("buttontip_filelist_thumbnail","show thumbnails","");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("buttontip_filelist_detail","show details","");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("changed_by","by","von");
INSERT INTO `tt_interface_text` (`name`,`en`,`de`) VALUES ("user_unknown","(unknown)","(unbekannt)");


# Dump of table tt_tasks
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tt_tasks`;

CREATE TABLE `tt_tasks` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `depends_on` varchar(100) NOT NULL default '',
  `status` enum('planned','active','finished','error','aborted','wait_for_resume','wait_for_question','wait_for_start') NOT NULL default 'planned',
  `status_description` varchar(100) NOT NULL default '',
  `func_init_vars` mediumtext NOT NULL,
  `lang` varchar(5) NOT NULL default 'en',
  `start_by` int(11) NOT NULL default '0',
  `start_time` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM COMMENT='depage 0.9.14';


# Dump of table tt_tasks_threads
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tt_tasks_threads`;

CREATE TABLE `tt_tasks_threads` (
  `id` int(10) unsigned NOT NULL default '0',
  `id_thread` int(10) unsigned NOT NULL auto_increment,
  `func` mediumtext NOT NULL,
  `status` float(4,2) NOT NULL default '0.00',
  `error` int(10) unsigned NOT NULL default '0',
  KEY `SECONDARY` (`id`,`id_thread`)
) TYPE=MyISAM COMMENT='depage 0.9.14';

