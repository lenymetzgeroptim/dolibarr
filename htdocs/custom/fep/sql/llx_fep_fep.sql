-- Copyright (C) ---Put here your own copyright and developer email---
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.


CREATE TABLE llx_fep_fep(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	ref varchar(128) NOT NULL, 
	note_public text, 
	note_private text, 
	date_creation datetime NOT NULL, 
	tms timestamp, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	date_debut date NOT NULL, 
	date_fin date NOT NULL, 
	question1_1 integer, 
	question1_2 integer, 
	question1_3 integer, 
	note_theme1 integer, 
	question2_1 integer, 
	question2_2 integer, 
	question2_3 integer, 
	question2_4 integer, 
	question2_5 integer, 
	note_theme2 integer, 
	question3_1 integer, 
	question3_2 integer, 
	question3_3 integer, 
	question3_4 integer, 
	question3_5 integer, 
	question3_6 integer, 
	question3_7 integer, 
	question3_8 integer, 
	note_theme3 integer, 
	question4_1 integer, 
	question4_2 integer, 
	question4_3 integer, 
	question4_4 integer, 
	note_theme4 integer, 
	question5_1 integer, 
	question5_2 integer, 
	note_theme5 integer, 
	question6_1 integer, 
	question6_2 integer, 
	question6_3 integer, 
	question6_4 integer, 
	note_theme6 integer, 
	question7_1 integer, 
	question7_2 integer, 
	note_theme7 integer, 
	note_globale integer, 
	type integer NOT NULL, 
	contrat integer, 
	date_transmission date NOT NULL, 
	date_reponse date, 
	constat1 text, 
	prop_amelioration1 text, 
	constat2 text, 
	prop_amelioration2 text, 
	constat3 text, 
	prop_amelioration3 text, 
	constat4 text, 
	prop_amelioration4 text, 
	date_publication date, 
	status integer NOT NULL, 
	domaine_activite varchar(128), 
	irregularite_cfsi boolean
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
