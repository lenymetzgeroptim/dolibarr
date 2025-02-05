-- Copyright (C) 2023 Faure Louis
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


CREATE TABLE llx_constat_constat(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	ref varchar(128) DEFAULT '(PROV)' NOT NULL, 
	label varchar(255) DEFAULT 'CONSTAT : ' NOT NULL, 
	status integer NOT NULL, 
	fk_user_creat integer NOT NULL, 
	fk_project integer, 
	site integer, 
	dateEmeteur  timestamp, 
	sujet integer, 
	typeConstat integer, 
	actionimmediate boolean, 
	actionimmediatecom text, 
	analyseCauseRacine text, 
	coutTotal int, 
	descriptionConstat text, 
	description text, 
	infoClient boolean, 
	commInfoClient text, 
	accordClient boolean, 
	commAccordClient text, 
	controleClient boolean, 
	commControleClient text, 
	commRespAff text, 
	commRespQ3 text, 
	commServQ3 text, 
	last_main_doc varchar(255), 
	date_eche date, 
	dateCloture date
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
