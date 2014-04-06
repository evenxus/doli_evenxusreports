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
 * Funcionaes js para marcado de checks
 * 
 */

    print '
            <script>        
            function jsMarcarTodas(objForm)
            {
                    for (i=0;i<document.forms[objForm].elements.length;i++)       
                        if(document.forms[objForm].elements[i].type == "checkbox")
                        document.forms[objForm].elements[i].checked=true
            }    
            function jsDesMarcarTodas(objForm)
            {
                    for (i=0;i<document.forms[objForm].elements.length;i++)       
                        if(document.forms[objForm].elements[i].type == "checkbox")
                        document.forms[objForm].elements[i].checked=false
            }    
            function jsInvertirMarcaje(objForm)
            {
                    for (i=0;i<document.forms[objForm].elements.length;i++)       
                        if(document.forms[objForm].elements[i].type == "checkbox")
                        if (document.forms[objForm].elements[i].checked == true) 
                        {
                            document.forms[objForm].elements[i].checked = false
                        }
                        else
                        {
                            document.forms[objForm].elements[i].checked = true
                        }
            }    
            </script>            
          ';


?>
