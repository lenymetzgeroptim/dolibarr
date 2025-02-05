-- Copyright (C) 2023 FADEL Soufiane <s.fadel@optim-industries.fr>
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


CREATE TABLE llx_actions_action(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	ref varchar(128) DEFAULT '(PROV)' NOT NULL, 
	numeroo integer, 
	intervenant integer, 
	priority integer NOT NULL, 
	alert integer, 
	solde integer, 
	origins varchar(255) NOT NULL, 
	reference varchar(255), 
	action_sse integer NOT NULL, 
	action_rp integer NOT NULL, 
	date_creation date NOT NULL, 
	action_txt text NOT NULL, 
	date_eche date, 
	avancement varchar(255), 
	status integer NOT NULL, 
	date_sol date, 
	diffusion integer, 
	com text, 
	eff_act integer, 
	date_asse date, 
	assessment integer, 
	rowid_constat integer, 
	last_main_doc varchar(255)
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
