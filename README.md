#Timbrado CFDI SAT

===== DESCRIPCION ======

Librería para facilitar el timbrado via web services de Facturas, así como la descarga de los archivos XML y PDF desde el servidor remoto PAC hacia el servidor local.

===== INSTALACIÓN =====
1.- Registrar una cuenta en www.facturadigital.com.mx/registro.php y obtener las credenciales de timbrado (usuario y contraseña)
2.-Copiar las librerías directamente en tu proyecto y realizar la instancia correspondiente a la clase.

Ejemplo:

// Timbrar una factura / nota de crédito / nota de cargo / carta porte
// Nota: la variable $layout_input se deberá completar de acuerdo a la plantilla definida en el archivo “CFDI Plantilla.txt”.

  // Instancia principal
  $ws = new TimbradoFacturaDigital();
  $ws->pathBoveda = “xmls/”;


  // Timbrar CFDI
  $ws->generarCFDI(“my_username”, “my_password”, $layout_input);
  var_dump ($ws);


  // Cancelar CFDI directamente en el SAT
  $ws->cancelarCFDI(“my_username”, “my_password”, “UUID_string”);
  var_dump ($ws);


  // Consultar timbres disponibles
  $ws->consultarCreditos(“my_username”, “my_password”);
  var_dump ($ws);


  // Procesar CFDI localmente
  // array $response_ws arreglo retornado en la llamada de $ws->generarCFDI
  $ws->procesarCFDI( $array_response );
  var_dump ($ws);


===== SUPPORT =====

If you have any issue with your installation, please send me an email: adrian.ondarza[at]gmail.com
