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


require_once '../../main.inc.php';

require_once '../../evenxus/class/barraprogreso.php';
require_once '../../evenxus/class/datos.php';

require_once '../class/comunes.php';
require_once '../class/instalarreportes.php';


global $db;

$de = new DatosEvenxus();

$langs->load("evenxusreports@evenxus");

if (!$user->rights->evenxusreports->cargarreporte->use) { accessforbidden(); }

$Reporte = $_FILES['reporte']['name'];

$c =    '<link rel=stylesheet href="../css/estilos.css" type="text/css">'.
        '<script src="../js/comunes.js" type="text/javascript"></script>';

llxHeader($c,"",$langs->trans("")); 


print_fiche_titre($langs->trans("CargarReporte"),"","../img/cargarreporte.png",1);

// Peticion de fichero de reporte
if ($Reporte=="") {
    PedirFichero();
}

// Proceso de fichero de reporte
if ($Reporte!="") {
    ProcesarSubida();
}

print '<br><br>';
print PiePagina();


llxFooter();

function PedirFichero() {
    print  'Aqui puedes cargar un nuevo reporte al sistema de Evenxus Reports, solo tienes que escoger el fichero ZIP del reporte y pulsar en Enviar
            <br><br>
            <center>
            <form action="cargarreporte.php" method="post" enctype="multipart/form-data">
            Elige reporte: <input type="file" name="reporte" size="25" />
            <input type="submit" name="submit" value="Enviar" />
            </form></center>';    
}
function ProcesarSubida() {
    // Se ha enviado un fichero
    if($_FILES['reporte']['name'])
    {
            $valid_file=true;
            $error=0;
            if(!$_FILES['reporte']['error'])
            {
                    $new_file_name = strtolower($_FILES['reporte']['tmp_name']); //rename file
                    if($_FILES['reporte']['size'] > (1024000)) // Tamaño maximo
                    {
                            $valid_file = false;
                            $error = -1; // Muy grande
                    }
                    if($valid_file)
                    {
                            move_uploaded_file($_FILES['reporte']['tmp_name'], DOL_DOCUMENT_ROOT."/evenxusreports/upload/".$_FILES['reporte']['name']);
                            $error = 1; // Exito
                    }
            }
            //if there is an error...
            else
            {
                    $error=-2; // Otro error -> $_FILES['reporte']['error']
            }
            if ($error==1) {
                CargarReporte($_FILES['reporte']['name']);
            }
    }    
}
function CargarReporte($Reporte) {
    global $NombreFiltros;
    $BP = new barraprogreso();
    print "Descomprimiendo reporte...<br><br>";
    $BP->ActualizarPantalla();
    $zip = new ZipArchive;
    $res = $zip->open("../upload/$Reporte");
    if ($res === TRUE) {
        $zip->extractTo("../upload/");
        $zip->close();
        print "Instalando reporte...<br><br>";
        $BP->ActualizarPantalla();
        $NombreReporte = basename($Reporte, ".zip");
        $id_menu_superior=ObtenerIDMenuSuperior();
        // Obtiene menu superior(Menu evenxusreports) e instala
        if ($id_menu_superior>-1) {
            include("../upload/".$NombreReporte."/install.php");
            // Limpieza final
            print "Limpieza de temporales...<br><br>";
            $BP->ActualizarPantalla();
            //BorrarCarpeta("../upload/$NombreReporte");
            //unlink ("../upload/$Reporte");
            print "Reporte instalado correctamente...redirigiendo al reporte<br><br>";
            $BP->ActualizarPantalla();
            sleep(1);
            $Redirigir=DOL_MAIN_URL_ROOT."/evenxusreports/frontend/".$NombreFiltros;
            print "<script language='javascript'>window.location='$Redirigir'</script>;";
        }
        else {
            print "Error en la instalacion del modulo. No se puede encontrar el menu principal para instalar submenus.";            
        }
    } else {
        print "Error al descomprimir el instalador. Es posible que no sea un ZIP o este corrupto.";
    }
}
