CREATE TABLE llx_evr_idiomas (
	rowid INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id',
	codigoreporte INT(11) UNSIGNED NOT NULL DEFAULT '0',
	nombrereporte VARCHAR(50) NOT NULL DEFAULT '0',
	idioma VARCHAR(50) NOT NULL DEFAULT '0',
	PRIMARY KEY (rowid)
)
ENGINE=InnoDB