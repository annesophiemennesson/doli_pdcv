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

create table llx_inventaire_produit
(
    rowid					integer AUTO_INCREMENT PRIMARY KEY,
    fk_inventaire           integer NOT NULL,                       -- lien avec l'inventaire
    fk_product              integer NOT NULL,                       -- produit
    stock_attendu           real DEFAULT NULL,                      -- stock attendu
    stock_reel              real DEFAULT NULL,                      -- stock reel
    stock_confirm           real DEFAULT NULL,                      -- stock confirmé
    fk_user                 integer DEFAULT NULL,                   -- utilisateur qui fait l'inventaire
    commentaire             varchar(255) DEFAULT NULL,              -- commentaire
)ENGINE=innodb;

