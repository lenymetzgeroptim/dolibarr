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
	status integer NOT NULL, 
	label varchar(255) NOT NULL, 
	fk_user_creat integer NOT NULL, 
	fk_project varchar(128) NOT NULL, 
	num_commande varchar(128), 
	site integer NOT NULL, 
	dateEmeteur date NOT NULL, 
	sujet integer NOT NULL, 
	descriptionConstat text NOT NULL, 
	impactcomm text, 
	description text, 
	typeConstat integer, 
	rubrique varchar(128), 
	processus varchar(128), 
	actionimmediate boolean, 
	actionimmediatecom text, 
	infoClient boolean, 
	commInfoClient text, 
	impact varchar(128), 
	commRespAff text, 
	analyseCauseRacine text, 
	recurent boolean, 
	accordClient boolean, 
	commAccordClient text, 
	controleClient boolean, 
	commControleClient text, 
	commRespQ3 text, 
	commServQ3 text, 
	coutTotal int, 
	last_main_doc varchar(255), 
	date_eche date, 
	dateCloture date, 
	fk_user integer NOT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
