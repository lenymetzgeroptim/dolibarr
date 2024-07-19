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


CREATE TABLE llx_fod_user(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	date_creation datetime NOT NULL, 
	tms timestamp, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	fk_user integer NOT NULL, 
	fk_fod integer NOT NULL, 
	duree_contrat integer, 
	contrat integer NOT NULL, 
	date_entree date NOT NULL, 
	date_sortie date, 
	statut integer NOT NULL, 
	visa integer NOT NULL, 
	date_visa date, 
	rex_intervenant integer, 
	com_rex varchar(255), 
	prise_en_compte_fin integer NOT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
