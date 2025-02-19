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


CREATE TABLE llx_c_volets(
	-- BEGIN MODULEBUILDER FIELDS
	rowid integer AUTO_INCREMENT PRIMARY KEY NOT NULL,
	numero integer, 
	label varchar(128) COLLATE utf8_unicode_ci NOT NULL,
	long_label varchar(128) COLLATE utf8_unicode_ci NOT NULL,
	nb_initial integer NOT NULL, 
	nb_recyclage integer NOT NULL, 
	nb_passerelle integer NOT NULL, 
	active int(11) DEFAULT 1
	typevolet integer NOT NULL, 
	nommage varchar(128) NOT NULL,
	model integer NOT NULL, 
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
