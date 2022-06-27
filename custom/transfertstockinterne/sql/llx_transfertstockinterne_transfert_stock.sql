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

create table llx_transfert_stock
(
    rowid					 integer AUTO_INCREMENT PRIMARY KEY,
    label                    varchar(255),                          -- libellé
    temperature_depart       double(7,4) DEFAULT NULL,              -- temperature au depart
    temperature_arrivee      double(7,4)  DEFAULT NULL,             -- temperature a l'arrivée
    fk_entrepot_depart       integer NOT NULL,                      -- entrepot de depart du transfert
    fk_entrepot_arrivee      integer NOT NULL,                      -- entrepot d'arrivée du transfert
    fk_user_demande          integer NOT NULL,                      -- utilisateur qui fait la demande
    fk_user_valide           integer DEFAULT NULL,                  -- utilisateur qui valide la demande
    fk_user_prepa            integer DEFAULT NULL,                  -- utilisateur qui prepare le transfert
    fk_user_reception        integer DEFAULT NULL,                  -- utilisateur qui receptionne le transfert
    date_creation            datetime NOT NULL,                     -- date de creation de la demande de transfert
    date_valide              datetime DEFAULT NULL,                 -- date de validation de la demande
    date_prepa               datetime DEFAULT NULL,                 -- date de preparation du transfert
    date_reception           datetime DEFAULT NULL                  -- date de reception du transfert
)ENGINE=innodb;

