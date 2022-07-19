-- Copyright (C) 2022   Anne-Sophie MENNESSON   <annesophie.mennesson@gmail.com>
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
ALTER TABLE llx_retourclientdet ADD INDEX idx_retourclientdet_rowid (rowid);
ALTER TABLE llx_retourclientdet ADD INDEX idx_retourclientdet_fk_retourclient (fk_retourclient);
ALTER TABLE llx_retourclientdet ADD INDEX idx_retourclientdet_fk_product (fk_product);
ALTER TABLE llx_retourclientdet ADD INDEX idx_retourclientdet_destination (destination);
ALTER TABLE llx_retourclientdet ADD CONSTRAINT llx_retourclientdet_fk_retourclient FOREIGN KEY (fk_retourclient) REFERENCES llx_retourclient(rowid);
-- END MODULEBUILDER INDEXES

