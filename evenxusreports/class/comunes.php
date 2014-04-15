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
    
    $cadena = '<button id="print" onclick="ImprimirReporte(\'print\')">Imprimir</button>
               <button id="printtool" onclick="ImprimirComoReporte()">Imprimir(Con opciones)</button>
               <button id="preview" onclick="VistaPreviaReporte()">Vista previa</button>';
    return $cadena;
}
/**
 * Botonera general de exportación 
 * @return string
 */
function BotoneraExportar() {
    $cadena='<button id="pdf" onclick="ExportarReporteCarpeta(\'pdf\')">Exportar a PDF</button>
             <button id="odt" onclick="ExportarReporteCarpeta(\'odt\')">Exportar a Open Document Text</button>
             <button id="ods" onclick="ExportarReporteCarpeta(\'ods\')">Exportar a Open Document Calc</button>
             <button id="docx" onclick="ExportarReporteCarpeta(\'docx\')">Exportar a Microsoft Word</button>
             <button id="xlsx" onclick="ExportarReporteCarpeta(\'xlsx\')">Exportar a Microsoft Excel</button>
             <button id="pptx" onclick="ExportarReporteDirecto(\'pptx\')">Exportar a Microsoft Powepoint</button>
             <button id="csv" onclick="ExportarReporteDirecto(\'csvD\|-o|C:/Users/santi/Desktop/Directo2\')">Exportar a CSV</button>';
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
 * Devuelve si un reporte esta o no activo
 * 
 * @global type $db
 * @param type $CodigoReporte
 */
function ReporteActivo($CodigoReporte) {
    $de = new DatosEvenxus();
    $sql="SELECT * FROM ".MAIN_DB_PREFIX."evr_reports WHERE codigo=".$CodigoReporte;
    $activo=$de->Valor($sql, "activo");
    return $activo;
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
    $carpeta= DOL_DOCUMENT_ROOT."/evenxusreports/reports/$NombreReporte/".$langs->getDefaultLang();
    return $carpeta;
}
/**
 * Salta N lineas en HTML
 * 
 *  * @param type $Numero
 * @return string
 */
function SaltaLinea($Numero) {
    $cadena="";
    $i=0;
    while ($i<$Numero) {
        $cadena=$cadena."</br>";
        $i++;
    }
    return $cadena;
}
/**
 * Borra una carpeta(recursiva)
 * @param type $carpeta
 */
function BorrarCarpeta($carpeta)
{
    foreach(glob($carpeta . "/*") as $archivos_carpeta)
    {
        if (is_dir($archivos_carpeta))
        {
            BorrarCarpeta($archivos_carpeta);
        }
        else
        {
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
    if ($file != "." && $file != "..") rrmdir("$dir/$file");
    rmdir($dir);
  }
  else if (file_exists($dir)) unlink($dir);
} 

// copies files and non-empty directories
function rcopy($src, $dst) {
  if (file_exists($dst)) rrmdir($dst);
  if (is_dir($src)) {
    mkdir($dst);
    $files = scandir($src);
    foreach ($files as $file)
    if ($file != "." && $file != "..") rcopy("$src/$file", "$dst/$file"); 
  }
  else if (file_exists($src)) copy($src, $dst);
}