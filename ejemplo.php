<?php
error_reporting(E_ALL); // esta linea la podemos comentar al acabar las pruebas
ini_set('display_errors', 1); // esta linea la podemos comentar al acabar las pruebas

// incluimos la clase generada para suspiros
include_once("class.timbrado.php");

// Formamos la plantilla con la información de la factura
// Nota 1. La plantilla debe contener los saltos de linea al final de cada renglón, mismo que se puede lograr con el caracter "\n" ó "\r".
// Nota 2. No se permiten comas ni signos de pesos en los montos. Se aceptan máximo 6 dígitos decimales.
// Nota 3. Nunca utilizar el caracter PIPE => | <= en la descripción de algún concepto, ya que es el caracter que define las posiciones de la plantilla.
// Nota 4. No utilizar caracteres especiales fuera del esquema UTF-8.

# definimos el template input
$plantilla = "FB|2501|FECHA|San Pedro Garza García, Nuevo Leon|egreso|Pago en una sola Exhibición|SPEI|0009 Banamex|100.8870|0.00|116.9950|MXN|1|OC123|2013-07-20|TEXTO DE PAGARÉ, ETC. OPCIONAL.|NC|NUMERO_CLIENTE|ENTREGA_NOMBRE|ENTREGA_CALLE|ENTREGA_NOEXT|ENTREGA_NOINT|ENTREGA_COL|ENTREGA_MUNI|ENTREGA_ESTADO|ENTREGA_PAIS|ENTREGA_CP|ENTREGA_REFER|ENTREGA_TELEFONO|NUM_PEDIDO|FECHA_PEDIDO|DIA_EMBARQUE|VIA_EMBARQUE|NUM_GUIA|ORDEN_SALIDA|COMPRADOR CARLOS RAMIREZ
Calzada del Valle (Sucursal)|90|int-10|Col. Del Valle||San Pedro Garza Garcia.|Nuevo León|México|76888
TLM920315N41|TRANSPORT LOGISTICS DE MEXICO SA DE CV
Av. Ricardo Margain|1043|Piso 4, Int-2|Del Valle||San Pedro Garza Garcia|Nuevo Leon|México|62268
CONCEPTOS|2
1122337843701|Pieza|Caja de Chocolates|1.00|50.00|550.45
1122337843701|Pieza|Bolsa de Dulces|1.00|50.00|50.00
IMPUESTOS_TRASLADADOS|2
IVA|16.00|160.4085
IEPS|8.00|100.3047
IMPUESTOS_RETENIDOS|0
ISR|0|0";

# definimos usuario y contraseña del Web Service
$usuario 	= "demo2014";
$password 	= "demo";


try {
	$timbrado 	= new TimbradoSuspiros(); // instanciamos la clase
	$cfdi 		= $timbrado->generarCFDI( $usuario, $password, $plantilla ); // solicitamos timbrado

	// validamos si el codigo de respuesta es 200, el timbre es exitoso.
	if ( $cfdi->codigo == "200" ):

		// debemos guardar en una tabla de timbres, los datos contenidos en el siguiente arreglo: $cfdi
		// por ejemplo:
		// $uuid = $cfdi->UUID;
		// $selloSAT = $cfdi->selloSAT;
		// 
		// Recomendación 1. Guardar las variables del arreglo $cfdi en una tabla de la base de datos, haciendo referencia a la factura (orden) timbrada.
		// Recomendación 2. Asignar "Unique ID" en el campo UUID de la tabla donde almacenarán los timbres.
		// para consultar todas las variables disponibles en $cfdi, descomentar la siguiente linea:
		// debug info: 
		// var_dump($cfdi);

		// procesamos y organizamos en carpetas los archivos XML y PDF
		$archivos_cfdi = $timbrado->procesarCFDI( $cfdi );

		// debug info:
		var_dump( $archivos_cfdi );

	else:

		// mostramos mensaje de error
		echo "No se pudo timbrar. " . $cfdi->mensajeError . " - código del error: " . $cfdi->codigo;

	endif;

} catch (Exception $e) {
	echo $e->getMessage();
}