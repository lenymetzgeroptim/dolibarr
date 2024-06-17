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


CREATE TABLE llx_gpec_matricecompetence(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	date_creation datetime NOT NULL, 
	anciennete_metier integer, 
	fk_user integer NOT NULL, 
	diplome varchar(128), 
	classification_pro integer NOT NULL, 
	is_td integer NOT NULL, 
	is_t integer NOT NULL, 
	is_te integer NOT NULL, 
	is_tc integer NOT NULL, 
	is_id integer NOT NULL, 
	is_i integer NOT NULL, 
	is_ic integer NOT NULL, 
	is_ie integer NOT NULL, 
	chef_projet integer NOT NULL, 
	pilote_affaire integer NOT NULL, 
	ingenieur_confirme integer NOT NULL, 
	preparateur_charge_affaire integer NOT NULL, 
	preparateur_methodes integer NOT NULL, 
	charge_affaires_elec_auto integer NOT NULL, 
	electricien integer NOT NULL, 
	charge_affaires_mecanique integer NOT NULL, 
	mecanicien integer NOT NULL, 
	robinettier integer NOT NULL, 
	pcr_operationnel integer NOT NULL, 
	technicien_rp integer NOT NULL, 
	charge_affaires_multi_specialites integer NOT NULL, 
	mec_machine_tournante integer NOT NULL, 
	robinetterie integer NOT NULL, 
	chaudronnerie integer NOT NULL, 
	tuyauterie_soudage integer NOT NULL, 
	automatisme integer NOT NULL, 
	electricite integer NOT NULL, 
	ventilation integer NOT NULL, 
	logistique integer NOT NULL, 
	securite integer NOT NULL, 
	soudage integer NOT NULL
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
