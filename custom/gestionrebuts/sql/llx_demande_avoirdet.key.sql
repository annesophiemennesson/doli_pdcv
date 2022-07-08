-- ===================================================================
-- Copyright (C) 2022		Anne-Sophie Mennesson	<annesophie.mennesson@gmail.com>
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


ALTER TABLE llx_demande_avoirdet ADD UNIQUE INDEX uk_demande_avoirdet (fk_demande_avoir, fk_product, batch);

ALTER TABLE llx_demande_avoirdet ADD INDEX idx_demande_avoirdet_fk_demande_avoir (fk_demande_avoir);
ALTER TABLE llx_demande_avoirdet ADD INDEX idx_demande_avoirdet_fk_product (fk_product);

ALTER TABLE llx_demande_avoirdet ADD CONSTRAINT fk_demande_avoirdet_fk_demande_avoir	FOREIGN KEY (fk_demande_avoir) REFERENCES llx_demande_avoir (rowid);

