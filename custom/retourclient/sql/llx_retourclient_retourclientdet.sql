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


create table llx_retourclientdet
(
    rowid					integer AUTO_INCREMENT PRIMARY KEY,
    fk_retourclient    		integer NOT NULL,              	                            			-- lien avec le retour
    fk_product	            integer NOT NULL,                                           			-- lien avec le produit
    batch                   varchar(128) default null,
    qty		                real not null,    		                  
    montant_ht              double(24,8) not null default 0,                           
    total_ht                double(24,8) not null default 0,  
    total_tva               double(24,8) not null default 0,
    total_ttc               double(24,8) not null default 0, 
    taux_tva                double(24,8) not null default 0,        								
    commentaire             varchar(255) DEFAULT NULL,                                  			-- commentaire
	destination				ENUM("remise en stock magasin", "remise en stock depot", "destruction")	-- destination du produit
)ENGINE=innodb;
