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


-- BEGIN MODULEBUILDER INDEXES
ALTER TABLE llx_constat_constat ADD INDEX idx_constat_constat_rowid (rowid);
ALTER TABLE llx_constat_constat ADD INDEX idx_constat_constat_ref (ref);
ALTER TABLE llx_constat_constat ADD INDEX idx_constat_constat_status (status);
ALTER TABLE llx_constat_constat ADD INDEX idx_constat_constat_site (site);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_constat_constat ADD UNIQUE INDEX uk_constat_constat_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_constat_constat ADD CONSTRAINT llx_constat_constat_fk_field FOREIGN KEY (fk_field) REFERENCES llx_constat_myotherobject(rowid);

