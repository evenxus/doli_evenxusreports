<?php

/* Copyright (C) 2013-     Santiago Garcia      <babelsistemas@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Botonera generica de impresion
 * @return string
 */
function BotoneraImprimir() {
    global $langs;
    $cadena = '<button id="print" onclick="ImprimirReporte(\'print\')">' . $langs->trans("Imprimir") . '</button>
               <button id="printtool" onclick="ImprimirComoReporte()">' . $langs->trans("ImprimirComo") . '</button>
               <button id="preview" onclick="VistaPreviaReporte()">' . $langs->trans("VistaPrevia") . '</button>';
    return $cadena;
}

/**
 * Botonera general de exportación 
 * @return string
 */
function BotoneraExportar() {
    global $langs;
    $cadena = '<button id="pdf" onclick="ExportarReporteCarpeta(\'pdf\')">' . $langs->trans("ExportarPDF") . '</button>
             <button id="odt" onclick="ExportarReporteCarpeta(\'odt\')">' . $langs->trans("ExportarODT") . '</button>
             <button id="ods" onclick="ExportarReporteCarpeta(\'ods\')">' . $langs->trans("ExportarODS") . '</button>
             <button id="docx" onclick="ExportarReporteCarpeta(\'docx\')">' . $langs->trans("ExportarWord") . '</button>
             <button id="xlsx" onclick="ExportarReporteCarpeta(\'xlsx\')">' . $langs->trans("ExportarExcel") . '</button>
             <button id="pptx" onclick="ExportarReporteDirecto(\'pptx\')">' . $langs->trans("ExportarPowerPoint") . '</button>
             <button id="csv" onclick="ExportarReporteDirecto(\'csv\')">' . $langs->trans("ExportarCSV") . '</button>';
    return $cadena;
}

/**
 * Muestra pie de pagina
 * @return string
 */
function PiePagina() {
    $cadena = '<center>Evenxus Reports - <b><a href="http://www.evenxus.com" target="_blank">www.evenxus.com</a></b></center>';
    return $cadena;
}

/**
 * Devuelve si un reporte esta o no activo segun su variable de estado y de como esta su modulo dependiente
 * 
 * @global type $db
 * @param type $CodigoReporte
 */
function ReporteActivo($CodigoReporte) {
    $activo=false;
    $de = new DatosEvenxus();
    $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "evr_reports WHERE codigo=" . $CodigoReporte;
    $reporteactivo = $de->Valor($sql, "activo");
    $modulo = $de->Valor($sql, "modulo");
    $moduloactivo = ModuloActivo($modulo);
    if ($reporteactivo==true and $moduloactivo==true) {
        $activo=true;
    }
    return $activo;
}

/**
 * Comprueba que el módulo está activado o no
 * 
 * @param: $NombreModulo . Nombre del módulo. terceros->'societe'
 */
function ModuloActivo($NombreModulo) {
    global $conf;
    $activado = in_array($NombreModulo, $conf->modules);
    return $activado;
}
/**
 * 
 * Redireccion a error por reporte desactivado
 * 
 */
function ReporteDesactivado() {
    header("Location: reporteoff.php");
}
/**
 * 
 * Redireccion a error por reporte desactivado
 * 
 */
function ReporteProhibido() {
    header("Location: reporteprohibido.php");
}

/**
 * 
 * Devuelve ruta a la carpeta de idiomas del reporte
 * 
 * @global type $langs
 * @return string
 * 
 */
function CarpetaIdiomaReporte($NombreReporte) {
    global $langs;
    $carpeta = DOL_DOCUMENT_ROOT . "/evenxusreports/reports/$NombreReporte/" . $langs->getDefaultLang();
    return $carpeta;
}

function CargarIdiomas() {
    global $db,$langs;
    $idiomas = array();
    $idiomas[] = "evenxusreports@evenxusreports";
    $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "evr_idiomas";
    $res = $db->query($sql);

    if ($res > 0) {
        $fila = $res->fetch_array();
        $i=1;
        while ($fila) {
            $idioma = $fila[idioma];
            $nombreidioma = explode(".", $idioma);
            $idiomas[] = $nombreidioma[0] . "@evenxusreports";
            $langs->Load($idiomas[$i]);
            $i++;
            $fila = $res->fetch_array();
        }
    }
    return $idiomas;
}
/**
 * Salta N lineas en HTML
 * 
 *  * @param type $Numero
 * @return string
 */
function SaltaLinea($Numero) {
    $cadena = "";
    $i = 0;
    while ($i < $Numero) {
        $cadena = $cadena . "</br>";
        $i++;
    }
    return $cadena;
}

/**
 * Borra una carpeta(recursiva)
 * @param type $carpeta
 */
function BorrarCarpeta($carpeta) {
    foreach (glob($carpeta . "/*") as $archivos_carpeta) {
        if (is_dir($archivos_carpeta)) {
            BorrarCarpeta($archivos_carpeta);
        } else {
            unlink($archivos_carpeta);
        }
    }
    rmdir($carpeta);
}

// removes files and non-empty directories
function rrmdir($dir) {
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file)
            if ($file != "." && $file != "..")
                rrmdir("$dir/$file");
        rmdir($dir);
    }
    else if (file_exists($dir))
        unlink($dir);
}

// copies files and non-empty directories
function rcopy($src, $dst) {
    if (file_exists($dst))
        rrmdir($dst);
    if (is_dir($src)) {
        mkdir($dst);
        $files = scandir($src);
        foreach ($files as $file)
            if ($file != "." && $file != "..")
                rcopy("$src/$file", "$dst/$file");
    }
    else if (file_exists($src))
        copy($src, $dst);
}

/**
 * 
 * Lanza reporte Jasper
 * 
 * @global type $conf
 * @global type $dolibarr_main_url_root
 * @param type $reporte                     Nombre del reporte
 * @param type $actualizar_report_auto      1 si actualiza., 0 no actualiza
 * @return string                           Cadena para pintar el js
 */
function EvenxusLanzarReport($reporte,$actualizar_report_auto) {
    global $conf;
    global $dolibarr_main_url_root;
    //$LanzarReporte = "\n params[i++]  =  '\"NOMBRE_EMPRESA=".$conf->global->MAIN_INFO_SOCIETE_NOM."\"';";
    $LanzarReporte = "\n params[i++]  =  'NOMBRE_EMPRESA=".$conf->global->MAIN_INFO_SOCIETE_NOM."';";
    $LanzarReporte = $LanzarReporte."\n err=EvenxusLanzarReport(params,$actualizar_report_auto,URL_DOLI_BASE(),'$reporte');";
    return $LanzarReporte;
}