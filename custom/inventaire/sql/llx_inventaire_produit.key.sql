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


ALTER TABLE llx_inventaire_produit ADD UNIQUE INDEX uk_inventaire_produit (fk_inventaire, fk_product);

ALTER TABLE llx_inventaire_produit ADD INDEX idx_inventaire_produit_fk_transfert (fk_inventaire);
ALTER TABLE llx_inventaire_produit ADD INDEX idx_inventaire_produit_fk_product (fk_product);
ALTER TABLE llx_inventaire_produit ADD INDEX idx_inventaire_produit_fk_user (fk_user);

ALTER TABLE llx_inventaire_produit ADD CONSTRAINT fk_inventaire_produit_fk_inventaire	FOREIGN KEY (fk_inventaire) REFERENCES llx_inventaire (rowid);

