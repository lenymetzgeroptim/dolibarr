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


CREATE TABLE llx_fod_fod(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	ref varchar(128) NOT NULL, 
	note_public text, 
	note_private text, 
	date_creation datetime NOT NULL, 
	tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
	fk_user_creat integer NOT NULL, 
	fk_user_modif integer, 
	last_main_doc varchar(255), 
	status smallint NOT NULL, 
	fk_user_pcr integer NOT NULL, 
	fk_user_rsr integer NOT NULL, 
	fk_user_raf integer NOT NULL, 
	debit_dose_estime double(5,3) NOT NULL, 
	duree_intervention double(10,1) NOT NULL, 
	effectif integer NOT NULL, 
	date_debut date NOT NULL, 
	date_fin date NOT NULL, 
	coef_exposition double(24,8) NOT NULL, 
	debit_dose_max double(5,3), 
	prop_radiologique integer NOT NULL, 
	fk_project integer NOT NULL, 
	indice varchar(1) NOT NULL, 
	risques integer NOT NULL, 
	ri integer NOT NULL, 
	rex integer NOT NULL, 
	aoa smallint DEFAULT 1 NOT NULL, 
	ded_optimise smallint DEFAULT 2, 
	ded_max_optimise smallint DEFAULT 2, 
	dc_optimise smallint DEFAULT 2, 
	cdd_optimise smallint DEFAULT 2, 
	prop_rad_optimise smallint DEFAULT 2, 
	debit_dose_estime_optimise double(5,3), 
	prop_radiologique_optimise integer, 
	q1_doses_individuelles integer, 
	q2_doses_individuelles integer, 
	q3_doses_individuelles integer, 
	q1_dose_collective integer, 
	q2_dose_collective integer, 
	q1_contamination integer, 
	q2_contamination integer, 
	q1_siseri integer, 
	rex_rsr integer, 
	rex_ra integer, 
	rex_pcr integer, 
	rex_rd integer, 
	q1_radiopotection integer, 
	date_fin_prolong date, 
	ref_rex varchar(128), 
	objectif_proprete integer NOT NULL, 
	com_objectif_proprete varchar(128), 
	epi_specifique integer, 
	consignes_rp integer, 
	commentaire_aoa text, 
	effectif_optimise integer, 
	duree_intervention_optimise double(10,1), 
	historique text, 
	commentaire_risque varchar(256), 
	commentaire_ri varchar(256), 
	commentaire_fod text, 
	client_site integer, 
	installation varchar(128), 
	etat_installation integer, 
	commentaire_etat_installation varchar(128), 
	activite varchar(128), 
	ref_doc_client varchar(128), 
	debit_dose_max_optimise double(5,3), 
	com_rex_rsr varchar(255), 
	com_rex_ra varchar(255), 
	com_rex_pcr varchar(255), 
	com_rex_rd varchar(255), 
	date_cloture date
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
