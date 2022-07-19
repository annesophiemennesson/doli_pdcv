-- Copyright (C) 2022		Anne-Sophie Mennesson	<annesophie.mennesson@gmail.com>
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
ALTER TABLE llx_retourclient ADD INDEX idx_retourclient_retourclient_rowid (rowid);
ALTER TABLE llx_retourclient ADD INDEX idx_retourclient_retourclient_fk_facture (fk_facture);
ALTER TABLE llx_retourclient ADD INDEX idx_retourclient_retourclient_fk_user_crea (fk_user_crea);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_retourclient_retourclient ADD UNIQUE INDEX uk_retourclient_retourclient_fieldxy(fieldx, fieldy);

--ALTER TABLE llx_retourclient_retourclient ADD CONSTRAINT llx_retourclient_retourclient_fk_field FOREIGN KEY (fk_field) REFERENCES llx_retourclient_myotherobject(rowid);

