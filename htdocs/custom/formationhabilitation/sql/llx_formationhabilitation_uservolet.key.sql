-- Copyright (C) 2024 METZGER Leny <l.metzger@optim-industries.fr>
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
ALTER TABLE llx_formationhabilitation_uservolet ADD INDEX idx_formationhabilitation_uservolet_rowid (rowid);
ALTER TABLE llx_formationhabilitation_uservolet ADD INDEX idx_formationhabilitation_uservolet_ref (ref);
ALTER TABLE llx_formationhabilitation_uservolet ADD INDEX idx_formationhabilitation_uservolet_status (status);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_formationhabilitation_uservolet ADD UNIQUE INDEX uk_formationhabilitation_uservolet_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_formationhabilitation_uservolet ADD CONSTRAINT llx_formationhabilitation_uservolet_fk_field FOREIGN KEY (fk_field) REFERENCES llx_formationhabilitation_myotherobject(rowid);

