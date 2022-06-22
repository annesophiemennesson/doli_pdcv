CREATE TABLE IF NOT EXISTS `llx_mbi_tpe` (
  `rowid` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `entity` int(11) NOT NULL DEFAULT '1',
  `object` int(11) NOT NULL,
  `primary_account_number` varchar(255) NOT NULL,
  `private_data` varchar(255) NOT NULL,
  `tms` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE llx_mbi_tpe ADD COLUMN full_data varchar(255) NOT NULL;
