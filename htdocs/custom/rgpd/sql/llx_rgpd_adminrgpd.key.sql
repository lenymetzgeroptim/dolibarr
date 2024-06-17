-- Copyright (C) 2023 GROBON Samantha
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
ALTER TABLE llx_rgpd_adminrgpd ADD INDEX idx_rgpd_adminrgpd_rowid (rowid);
ALTER TABLE llx_rgpd_adminrgpd ADD INDEX idx_rgpd_adminrgpd_ref (ref);
ALTER TABLE llx_rgpd_adminrgpd ADD CONSTRAINT llx_rgpd_adminrgpd_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user(rowid);
ALTER TABLE llx_rgpd_adminrgpd ADD INDEX idx_rgpd_adminrgpd_status (status);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_rgpd_adminrgpd ADD UNIQUE INDEX uk_rgpd_adminrgpd_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_rgpd_adminrgpd ADD CONSTRAINT llx_rgpd_adminrgpd_fk_field FOREIGN KEY (fk_field) REFERENCES llx_rgpd_myotherobject(rowid);

