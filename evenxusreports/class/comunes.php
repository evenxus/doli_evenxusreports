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

function BotoneraImprimir() {
    
    $cadena = '<button id="print" onclick="ProcesarReporte(\'print\')">Imprimir</button>
               <button id="printtool" onclick="ProcesarReporte(\'print|-d\')">Imprimir(Con opciones)</button>
               <button id="preview" onclick="ProcesarReporte(\'view\')">Vista previa</button>';
    return $cadena;
}

function BotoneraExportar() {
    $cadena='<button id="pdf" onclick="ProcesarReporte(\'pdf\')">Exportar a PDF</button>
             <button id="odt" onclick="ProcesarReporte(\'odt\')">Exportar a Open Document Text</button>
             <button id="ods" onclick="ProcesarReporte(\'ods\')">Exportar a Open Document Calc</button>
             <button id="docx" onclick="ProcesarReporte(\'docx\')">Exportar a Microsoft Word</button>
             <button id="xlsx" onclick="ProcesarReporte(\'xlsx\')">Exportar a Microsoft Excel</button>
             <button id="pptx" onclick="ProcesarReporte(\'pptx\')">Exportar a Microsoft Powepoint</button>
             <button id="csv" onclick="ProcesarReporte(\'csv\')">Exportar a CSV</button>';
    return $cadena;
}

function PiePagina() {
    $cadena = '<center>Evenxus Reports - <b><a href="http://www.evenxus.com" target="_blank">www.evenxus.com</a></b></center>';
    return $cadena;
}

function SaltaLinea($Numero) {
    $cadena="";
    $i=0;
    while ($i<$Numero) {
        $cadena=$cadena."</br>";
        $i++;
    }
    return $cadena;
}

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