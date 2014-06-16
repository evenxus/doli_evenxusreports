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
class cripto
{
        private $clave   =  '                '; // 16c
        private $semilla =  'dfNTq9wth2n2k3h1'; // 16c
        
        private $debug   = true;                // Si es true no encripta
                
        function __construct($clave,$debug)
        {
            $clave= str_pad($clave, 16, " ", STR_PAD_RIGHT);
            $this->clave   = $clave;
            $this->debug   = $debug;
        }
        /**
         * Encripta la cadena pasada como parametro
         * 
         * @param type $cadenavisible Cadena a encriptar
         * @return Cadena encriptada
         */
        function encrypt($cadenavisible) {
            if ($this->debug==false) {
                $td = mcrypt_module_open('rijndael-128', '', 'cbc', $this->semilla);
                mcrypt_generic_init($td, $this->clave, $this->semilla);
                $encrypted = mcrypt_generic($td, $cadenavisible);
                mcrypt_generic_deinit($td);
                mcrypt_module_close($td);
                return bin2hex($encrypted);
           } 
           else { 
                return $cadenavisible; 
           }
        }
        /**
         * Desencripta la cadena pasada como parametro
         * 
         * @param type $cadenaEncriptada Cadena encriptada
         * @return Cadena desencriptada
         */        
        function decrypt($cadenaEncriptada) {
            if ($this->debug==false) {            
                  $cadenaEncriptada = $this->hex2bin($cadenaEncriptada);
                  $td = mcrypt_module_open('rijndael-128', '', 'cbc', $this->semilla);
                  mcrypt_generic_init($td, $this->clave, $this->semilla);
                  $decrypted = mdecrypt_generic($td, $cadenaEncriptada);
                  mcrypt_generic_deinit($td);
                  mcrypt_module_close($td);
                  return utf8_encode(trim($decrypted));
            }
            else {
                return $cadenaEncriptada;
            }
        }

        protected function hex2bin($hexdata) {
                 $bindata = '';
                 for ($i = 0; $i < strlen($hexdata); $i += 2) {
                        $bindata .= chr(hexdec(substr($hexdata, $i, 2)));
                 }
                 return $bindata;
        }
}

