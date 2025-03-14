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
ALTER TABLE llx_actions_action ADD INDEX idx_actions_action_rowid (rowid);
ALTER TABLE llx_actions_action ADD INDEX idx_actions_action_ref (ref);
ALTER TABLE llx_actions_action ADD INDEX idx_actions_action_numeroo (numeroo);
ALTER TABLE llx_actions_action ADD INDEX idx_actions_action_intervenant (intervenant);
ALTER TABLE llx_actions_action ADD INDEX idx_actions_action_alert (alert);
ALTER TABLE llx_actions_action ADD INDEX idx_actions_action_status (status);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_actions_action ADD UNIQUE INDEX uk_actions_action_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_actions_action ADD CONSTRAINT llx_actions_action_fk_field FOREIGN KEY (fk_field) REFERENCES llx_actions_myotherobject(rowid);

