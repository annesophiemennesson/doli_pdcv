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


create table llx_retourclient
(
    rowid					integer AUTO_INCREMENT PRIMARY KEY,
    fk_facture         		integer NOT NULL,              	                            -- lien avec la facture
    fk_user_crea            integer NOT NULL,                                           -- lien avec l'utilisateur qui fait la demande
    statut                  ENUM("ouverte", "validée", "remboursée") Default "ouverte", -- statut de la demande d'avoir
    date_creation           datetime default NULL,                                      -- date
    montant_ht              double(24,8) not null default 0,                            -- date
    montant_tva             double(24,8) not null default 0,                            -- date
    montant_ttc             double(24,8) not null default 0,
    mode_remboursement      ENUM("espèces", "CB") default "espèces",
    model_pdf               varchar(255) default null,
    last_main_doc           varchar(255) default null
)ENGINE=innodb;

