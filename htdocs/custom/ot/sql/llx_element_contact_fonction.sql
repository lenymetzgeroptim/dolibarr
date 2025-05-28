-- ===================================================================
-- Copyright (C) 2024 OT
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-- ===================================================================

CREATE TABLE IF NOT EXISTS `llx_element_contact_fonction` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT,
  `element_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `function_id` int(11) NOT NULL,
  `date_creation` datetime NOT NULL,
  PRIMARY KEY (`rowid`),
  KEY `idx_element_contact_fonction_element_id` (`element_id`),
  KEY `idx_element_contact_fonction_contact_id` (`contact_id`),
  KEY `idx_element_contact_fonction_function_id` (`function_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8; 