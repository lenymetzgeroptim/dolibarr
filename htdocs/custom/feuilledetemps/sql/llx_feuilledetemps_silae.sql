-- Copyright (C) 2023 METZGER Leny 
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


CREATE TABLE llx_feuilledetemps_silae(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	date_creation datetime NOT NULL, 
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	date date NOT NULL, 
	fk_user integer NOT NULL, 
	heure_nuit double, 
	heure_route double, 
	kilometres double(6,2), 
	repas integer, 
	indemnite_tt boolean, 
	heure_sup00 double, 
	heure_sup25 double, 
	heure_sup50 double, 
	heure_sup50ht double
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
