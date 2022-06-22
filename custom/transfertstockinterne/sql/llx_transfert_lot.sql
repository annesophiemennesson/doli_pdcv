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

create table llx_transfert_lot
(
    rowid					integer AUTO_INCREMENT PRIMARY KEY,
    fk_product_lot          integer NOT NULL,                           -- lien avec le lot de produit
    fk_transfert_produit    integer NOT NULL,                           -- lien avec le transfert de stock
    qte_valide              real NOT NULL,                              -- quantité validée
    qte_prepa               real DEFAULT NULL,                          -- quantité préparée
    qte_reception           real DEFAULT NULL,                          -- quantité réceptionnée
    commentaire_prepa       VARCHAR(255) DEFAULT NULL,                  -- commentaire preparation
    commentaire_reception   VARCHAR(255) DEFAULT NULL                   -- commentaire reception
)ENGINE=innodb;

