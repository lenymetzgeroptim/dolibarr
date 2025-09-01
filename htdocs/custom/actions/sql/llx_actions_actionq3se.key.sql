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


-- BEGIN MODULEBUILDER INDEXES
ALTER TABLE llx_actions_actionq3se ADD INDEX idx_actions_actionq3se_rowid (rowid);
ALTER TABLE llx_actions_actionq3se ADD INDEX idx_actions_actionq3se_ref (ref);
ALTER TABLE llx_actions_actionq3se ADD INDEX idx_actions_actionq3se_status (status);
ALTER TABLE llx_actions_actionq3se ADD INDEX idx_actions_actionq3se_intervenant (intervenant);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_actions_actionq3se ADD UNIQUE INDEX uk_actions_actionq3se_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_actions_actionq3se ADD CONSTRAINT llx_actions_actionq3se_fk_field FOREIGN KEY (fk_field) REFERENCES llx_actions_myotherobject(rowid);

