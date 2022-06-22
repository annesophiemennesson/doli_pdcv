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


ALTER TABLE llx_transfert_stock ADD INDEX idx_transfert_fk_entrepot_depart (fk_entrepot_depart);
ALTER TABLE llx_transfert_stock ADD INDEX idx_transfert_fk_entrepot_arrivee (fk_entrepot_arrivee);
ALTER TABLE llx_transfert_stock ADD INDEX idx_transfert_fk_demande (fk_user_demande);
ALTER TABLE llx_transfert_stock ADD INDEX idx_transfert_fk_valide (fk_user_valide);
ALTER TABLE llx_transfert_stock ADD INDEX idx_transfert_fk_prepa (fk_user_prepa);
ALTER TABLE llx_transfert_stock ADD INDEX idx_transfert_fk_recep (fk_user_reception);


