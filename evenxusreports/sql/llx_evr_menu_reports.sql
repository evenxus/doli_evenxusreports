CREATE TABLE llx_evr_menu_reports (
	rowid INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id',
	codigoreporte INT(11) UNSIGNED NOT NULL DEFAULT '0',
	nombrereporte VARCHAR(50) NOT NULL DEFAULT '0',
	codigomenu INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'ID del menu',
	codigomenupadre INT(11) NOT NULL COMMENT 'ID del padre',
	idactual INT(11) NOT NULL,
	orden VARCHAR(11) NOT NULL DEFAULT '' COMMENT 'Orden del menu',
	filtros VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'PHP link',
	titulo VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'Titulo',
	PRIMARY KEY (rowid)
)
ENGINE=InnoDB