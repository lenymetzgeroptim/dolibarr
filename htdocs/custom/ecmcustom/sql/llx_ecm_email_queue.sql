-- Copyright (C) 2024 FADEL Soufiane <s.fadel@optim-industries.fr>
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


CREATE TABLE llx_ecm_email_queue(
	-- BEGIN MODULEBUILDER FIELDS
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    name VARCHAR(100),
    subject TEXT NOT NULL,
    message TEXT NOT NULL,
    attachments TEXT,
    sent TINYINT DEFAULT 0,
    received TINYINT DEFAULT 0,
    error TEXT,
    sent_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
	-- END MODULEBUILDER FIELDS
) ENGINE=innodb;
