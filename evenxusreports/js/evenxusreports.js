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
 * Crea los parametros comunes
 * 
 * @param {type} Reporte Nombre del fichero.jasper del reporte
 * @param {type} Modo Modo de uso...
 * @param {type} Fijado Debe de tomar alguna decision implicita...
 * @returns {Array}
 */
function ParametrosComunesReporte(Reporte,Modo,Idioma,Salida) {
            var params = new Array();
            var i=0;
            if (Salida===0) {    params[i++]  =  'pr'; }    // salida directa
            if (Salida===1) {    params[i++]  =  'pr1'; }   // salida pidiendo carpeta
            params[i++]  =  Reporte;
            params[i++]  =  '-f';
            // Si es print y tiene parametros secundarios
            if (Modo.indexOf('print')>-1) {
                params[i++]  =  'print';
                if (Modo.indexOf('|')>-1) {
                    var sec_param = Modo.split('|');
                    // Los añado
                    for (var j=1; j < sec_param.length; j++) {
                        params[i++]  =  sec_param[j];
                    }
                }
            }
            else {
                // Si no es ni print ni view comprobamos si se esta pasando carpeta de exportacion
                if (Modo.indexOf('print')===-1 && Modo.indexOf('view')===-1) {
                    if (Modo.indexOf('|')>-1) {
                        var sec_param = Modo.split('|');
                        // Los añado
                        for (var j=0; j < sec_param.length; j++) {
                            params[i++]  =  sec_param[j];
                        }
                    }                
                    else {
                         params[i++]  =  Modo;
                    }
                }
                else {
                    params[i++]  =  Modo;
                }
            }  
            params[i++]  =  '-r';
            params[i++]  =  Idioma;
            return params;
}
function EvenxusElegirCarpeta(idioma,origen) {
        try {
        var element = document.createElement("EvenxusLocal");
        element.setAttribute("param0", idioma);
        element.setAttribute("param1", origen);
        document.documentElement.appendChild(element);
        element.setAttribute("errorMessage", "Unexpected error");
        var ev = document.createEvent("Events");
	ev.initEvent("Evenxus_Elegir_Carpeta", true, false); // Lanzo evento de impresion
        element.dispatchEvent(ev);
        // Error de instalacion del complemento
        if (!element.hasAttribute("evenxus_load_ok")) {
            return "El plugin Evenxus Reports(Firefox) no esta instalado";
        }
        document.documentElement.removeChild(element);
        } catch(e) {
            setTimeout(function() { throw e; }, 0);
            return "Error no controlado";
        }
        return element.getAttribute("errorMessage");
}
/**
 * Funcion EvenxusLanzarReport
 * 
 * @param params Parametros del reporte
 * @param actualizar Si es 1, actualiza desde el server
 * @param rutadescarga Es la ruta http del jasper para descargar
 * @param jasper Nombre local del jasper
 */
function EvenxusLanzarReport(params,actualizar,rutadescarga,reporte)
{
      if (actualizar===1) {
            // Actualizo reporte desde el server
            var jasper = reporte+'.jasper';
            var rutadescargajasper=rutadescarga +'/evenxusreports/reports/';
            var err = EvenxusActualizarReport(rutadescargajasper,jasper);            
                if (err!==null) { alert(err);   return err; }
            // Idiomas
            var properties=reporte+'.properties';
            var rutadescargaidiomas = rutadescarga + '/evenxusreports/reports';
            // Español-España
            var err = EvenxusActualizarReportIdioma(rutadescargaidiomas+'/es_ES/',properties,'es_ES\\');            
                if (err!==null) { alert(err);   return err; }
            // Ingles
            var err = EvenxusActualizarReportIdioma(rutadescargaidiomas+'/en/',properties,'en\\');            
                if (err!==null) { alert(err);   return err; }            
      }
      try {
        var element = document.createElement("EvenxusLocal");
        for (var i=0; i<params.length; i++) {
            element.setAttribute("param"+i, params[i]);
        }
        document.documentElement.appendChild(element);
        element.setAttribute("errorMessage", "Unexpected error");
        var ev = document.createEvent("Events");
	ev.initEvent("Evenxus_LanzarJasper", true, false); // Lanzo evento de impresion
        element.dispatchEvent(ev);
        // Error de instalacion del complemento
        if (!element.hasAttribute("evenxus_load_ok")) {
            return "El plugin Evenxus Reports(Firefox) no esta instalado";
        }
        document.documentElement.removeChild(element);
        } catch(e) {
            setTimeout(function() { throw e; }, 0);
            return "Error no controlado";
        }
        return element.getAttribute("errorMessage");
}
/**
 * Funcion EvenxusActualizarReport
 */
function EvenxusActualizarReport()
{
      try {
        var element = document.createElement("EvenxusLocal");
        for (var i=0; i<arguments.length; i++) {
            element.setAttribute("param"+i, arguments[i]);
        }
        document.documentElement.appendChild(element);
        element.setAttribute("errorMessage", "Unexpected error");
        var ev = document.createEvent("Events");
	ev.initEvent("Evenxus_Actualizar_Reportes", true, false); // Lanzo evento de impresion
        element.dispatchEvent(ev);
        // Error de instalacion del complemento
        if (!element.hasAttribute("evenxus_load_ok")) {
            return "El plugin Evenxus Reports(Firefox) no esta instalado";
        }
        document.documentElement.removeChild(element);
      } catch(e) {
        setTimeout(function() { throw e; }, 0);
        return "Error no controlado";
      }
      return element.getAttribute("errorMessage");
}
/**
 * Funcion EvenxusActualizarReport
 */
function EvenxusActualizarReportIdioma()
{
      try {
        var element = document.createElement("EvenxusLocal");
        for (var i=0; i<arguments.length; i++) {
            element.setAttribute("param"+i, arguments[i]);
        }
        document.documentElement.appendChild(element);
        element.setAttribute("errorMessage", "Unexpected error");
        var ev = document.createEvent("Events");
	ev.initEvent("Evenxus_Actualizar_Reportes_Idiomas", true, false); // Lanzo evento de impresion
        element.dispatchEvent(ev);
        // Error de instalacion del complemento
        if (!element.hasAttribute("evenxus_load_ok")) {
            return "El plugin Evenxus Reports(Firefox) no esta instalado";
        }
        document.documentElement.removeChild(element);
      } catch(e) {
        setTimeout(function() { throw e; }, 0);
        return "Error no controlado";
      }
      return element.getAttribute("errorMessage");
}
// Exportacion con pantalla de seleccion de carpeta
function ExportarReporteCarpeta(Modo) {
    err=EvenxusElegirCarpeta('1',null); // 1 Español - 2 Ingles - 3 Frances , 2 parametro es la carpeta que abrira(null = Documentos usuario)
    ProcesarReporte(Modo,1);
}
// Exportacion con o sin parametros pero sin seleccion de carpeta
// P.E. : \'csv\|-o|C:/Users/santi/Desktop/Directo2\' Este parametro exporta en formato csv a la carpeta 
function ExportarReporteDirecto(Modo) {
    ProcesarReporte(Modo,0);
}
// Muestra pantalla de vista previa del reporte
function VistaPreviaReporte() {
    ProcesarReporte('view',0);
}
// Imprimir en impresora seleccionada en el plugin
// O en otra mediante parametro : \'print\|-N|PDFCreator\' 
function ImprimirReporte(Modo) {
    if (arguments.length === 0) { Modo="print";}
    ProcesarReporte(Modo,0);
}
// Imprimir con cuadro de seleccion de impresora
function ImprimirComoReporte() {
    ProcesarReporte('print|-d',0);
}


