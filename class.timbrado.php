<?php
/**
 * Desarrollado por Adrian Sanchez (adrian.ondarza@gmail.com)
 * Fecha de Creación: 16 Mayo 2015 14:00:00
 * Fecha de Modificación: 19 Mayo 2015 19:00:00
 * Descripción: Clase helper para realizar el timbrado
 * de facturas electrónicas CFDI en la versión 3.2 utilizando como input
 * plantillas de texto en formato UTF-8 que están disponibles por solicitud
 * a través del correo soporte@facturadigital.com.mx
 * VERSIÓN DE LA LIBRERÍA: 1.0
 * 
 * REQUISITOS: PHP >= 5, OpenSSL
 */
 
require_once ("nusoap/nusoap.php");


class TimbradoFacturaDigital {
    
    # url WSDL remoto
    public $urlFacturaDigital = 'https://www.facturadigital.com.mx/sistemacfdi32/webservices/TimbradoWS.php?wsdl';

    # path local para almacenar los XMLs y PDFs
    public $pathBoveda        = 'boveda/'; // agregar trailslash

    # variables para uso interno
    public $cfdi;
    private $xml;
    private $ns;
    
    /**
     * Genera y timbra un archivo XML, y devuelve un arreglo conteniendo la información necesaria para proveer la factura timbrada al cliente
     * @param string $usuario nombre de usuario username
     * @param string $password password de la cuenta
     * @param string $layout texto que contiene los datos del comprobante a generar, basado en la plantilla.
     * @return boolean si el timbrado es correcto
     */
    public function generarCFDI($usuario, $password, $layout) {
        try {
    
            $client = new SoapClient( $this->urlFacturaDigital, array (
                                        'cache_wsdl' => WSDL_CACHE_NONE,
                                        'trace' => TRUE
                                    ));
            
            $cfdi = $client->generarCFDIPorTexto ( $usuario, $password, $layout );
    
            return $cfdi;
        } catch ( Exception $e ) {
            throw new Exception ( $e->getMessage(), $e->getCode() );
            return false;
        }
    }
    

    /**
     * Llama al método de cancelacion de folios UUID – La cancelación se realiza directamente en el servidor del SAT
     * @param string $usuario
     * @param string $password
     * @param string $uuid es el folio fiscal UUID del CFDI proporcionado por el SAT
     * @return boolean si la cancelación es correcta
     */
    public function cancelarCFDI($usuario, $password, $uuid) {
        try {
    
            $client = new SoapClient( $this->urlFacturaDigital, array (
                                        'cache_wsdl' => WSDL_CACHE_NONE,
                                        'trace' => TRUE
                                    ));
    
            $cancelacion = $client->cancelarCFDI( $usuario, $password, $uuid );
            
            return $cancelacion;
        } catch ( Exception $e ) {
            throw new Exception ( $e->getMessage(), $e->getCode() );
            return false;
        }
    }
    
    /**
     * Consulta la cantidad de créditos (timbres) disponibles para el usuario y contraseña proporcionados.
     * @param string $usuario
     * @param string $password
     * @return array $ws que contiene la cantidad de timbres disponibles.
     */
    public function consultarCreditos($usuario, $password) {
        try {
            $client = new SoapClient ( $this->urlFacturaDigital, array (
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => TRUE
            ) );
    
            $ws = $client->consultarCreditos( $usuario, $password );
            return $ws;
        } catch ( Exception $e ) {
            throw new Exception ( $e->getMessage(), $e->getCode() );
            return false;
        }
    }
    

    /**
     * Descarga el documento del servidor de FacturaDigital hacia el servidor local, y lo organiza.
     * @param array $response_ws arreglo retornado en la llamada de generarCFDI
     * @return array $ws que contiene la cantidad de timbres disponibles.
     */
    public function procesarCFDI( $array_response ) {
        // validamos que sea response 200
        $arr = $array_response;

        if ( $arr->codigo == "200" ) {
            $urlXML = $arr->urlDownloadXML;
            $urlPDF = $arr->urlDownloadPDF;
            $uuid   = $arr->UUID;
            $arr_archivos_locales = $this->descargarCFDI ( $urlXML, $urlPDF, $uuid );

            return $arr_archivos_locales;
        }
    }

    private function descargarCFDI( $urlXML, $urlPDF, $uuid ) {
        $temp_path  = $this->pathBoveda . $uuid;
        $temp_xml   = $temp_path . ".xml";
        $temp_pdf   = $temp_path . ".pdf";

        // descarga XML temporal
        copy( $urlXML , $temp_xml );

        // descarga PDF temporal
        copy( $urlPDF , $temp_pdf );

        // obtenemos datos del XML
        $this->xml = simplexml_load_file( $temp_xml );
        $this->ns = $this->xml->getNamespaces(true);
        $this->xml->registerXPathNamespace('c', $this->ns['cfdi']);
        $this->xml->registerXPathNamespace('t', $this->ns['tfd']);
        
        foreach ($this->xml->xpath('//cfdi:Comprobante') as $cfdi){
            $this->cfdi['fecha']                =  (string) $cfdi['fecha'];
            $this->cfdi['tipoDeComprobante']    =  (string) $cfdi['tipoDeComprobante'];
            $this->cfdi['serie']                =  (string) $cfdi['serie'];
            $this->cfdi['folio']                =  (string) $cfdi['folio'];
        }

        foreach ($this->xml->xpath('//cfdi:Comprobante//cfdi:Emisor') as $emisor ){
            $this->cfdi['emisor']['rfc']        = (string) $emisor['rfc'];
            $this->cfdi['emisor']['nombre']     = (string) $emisor['nombre'];
        }

        foreach ($this->xml->xpath('//cfdi:Comprobante//cfdi:Receptor') as $receptor){ 
            $this->cfdi['receptor']['rfc']      = (string) $receptor['rfc'];
            $this->cfdi['receptor']['nombre']   = (string) $receptor['nombre'];
        }

        $fecha_doc      = strtotime( $this->cfdi['fecha'] );
        $mes_doc        = date("F", $fecha_doc);
        $anio           = date("Y", $fecha_doc);

        $path_mover     = $this->pathBoveda . $this->cfdi['emisor']['rfc'] . "/" . $anio . "/" . $mes_doc;
        $nombre_nuevo   = $path_mover . "/" . $this->cfdi['serie'] . $this->cfdi['folio'] . "_" . $this->cfdi['receptor']['rfc'];

        if (!file_exists( $path_mover )) {
            mkdir( $path_mover , 0777, true);
        }

        // movemos el archivo XML
        rename( $temp_xml , $nombre_nuevo . ".xml" );

        // movemos el archivo PDF
        rename( $temp_pdf , $nombre_nuevo . ".pdf" );

        // retornamos una respuesta
        $arr_archivos = ['pathLocalXML' => $nombre_nuevo . ".xml" , 'pathLocalPDF' => $nombre_nuevo . ".pdf"];
        return $arr_archivos;
    }







    /**
     * Envia documento UUID (xml y pdf) por correo electronico
     * @param string $usuario
     * @param string $password
     * @param string $uuid uuid del documento que se quiere enviar
     * @param string $token token del documento generado al momento de timbrarlo
     * @param string $destinatario correo(s) de los destinatarios. Pueden ser varios separados por comas simples (,). No usar comillas dobles
     * @param string $mensajehtml mensaje que se le mostrará al destinatario en formato HTML
     * @param string $templatecode es el codigo del template personalizado proporcionado por el integrador de FacturaDigital.
     * @return array $ws
     */
    public function enviarDocumentoMail($usuario, $password, $uuid, $token, $destinatario, $mensajehtml, $templatecode) {
        try {
            $client = new SoapClient ( $this->urlFacturaDigital, array (
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => TRUE
            ) );
    
            // envia los parámetros al método de timbrado
            $ws = $client->enviarCorreo ( $usuario, $password, $uuid, $token, $destinatario, $mensajehtml, $templatecode );
            return $ws;
        } catch ( Exception $e ) {
            throw new Exception ( $e->getMessage (), $e->getCode () );
            return false;
        }
    }








    
}


