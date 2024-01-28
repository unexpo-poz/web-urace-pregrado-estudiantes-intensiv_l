  <?php
$enProduccion = true; //cambiar luego a true, esto es para que no sea necesario recargar el codigo
$raizDelSitio		= 'http://'.$_SERVER['SERVER_NAME'].'/web/urace/pregrado/estudiantes/intensiv_l/';
$urlDelSitio		= 'http://www.poz.unexpo.edu.ve/web/urace/';

$tProceso			= 'Inscripci&oacuten de Asignaturas ';
//$tProceso			= 'Agregado de Asignaturas ';

$lapsoProceso		= '2023-1I';


// * * * * * OJO OJO OJO OJO * * * * * 
// Cambiar esto manualmente de acuerdo a la jornada.
// Tipo de jornada
//	0 : deshabilitado 
//	1 : solo preinscritos en las materias preinscritas.
//	2 : solo preinscritos, pero pueden cambiar las materias.
//	3 : todos preinscritos o no preinscritos
$tipoJornada = 3;
$tablaOrdenInsc = 'ORDEN_INSCRIPCION';

# Bloque de asignaturas para no mostrar
$noOfertar = "'322939','322040',";// Mecanica
$noOfertar.= "'311939','311040',";// Electrica
$noOfertar.= "'333939','333040','333563',";// Metalurgica
$noOfertar.= "'355959','355069',";// Electronica
$noOfertar.= "'344939','344040'" ;// Industrial

# valores no bloqueados en dace003
$nobloqueados = '16'; // separar con coma(,) multiples valores. Ej 15,16,120

$tLapso				= 'Lapso Intensivo '.$lapsoProceso;
$laBitacora			= $_SERVER[DOCUMENT_ROOT].'/log/pregrado/estudiantes/intensivo/FASE'.$tipoJornada.'_intensivo_'.$lapsoProceso.'.log';

$inscHabilitada		= false;
$sedesUNEXPO = array (	'BQTO' => array('BQTO', 'CARORA'), 
						'CCS'  => array('DACECCS'),
						//'POZ'  => array('DACEPOZ')
						'POZ'  => array('CENTURA-DACE')
				);

//$sedeActiva = 'BQTO';
//$sedeActiva = 'CCS';
$sedeActiva = 'POZ';
$pensumPoz = '5';

$nucleos = $sedesUNEXPO[$sedeActiva];

//$vicerrectorado		= "Luis Caballero Mej&iacute;as";
//$vicerrectorado		= "Barquisimeto";
$vicerrectorado		= "Puerto Ordaz";
$nombreDependencia = 'Unidad Regional de Admisi&oacute;n y Control de Estudios';

//Unidad Tributaria y Costo de las materias:
//$unidadTributaria	= 12.00;
//$valorPreMateria	= 5.00/*0.2*$unidadTributaria*/;
//$valorMateria		= 20.00+$valorPreMateria;
$valorMateria		= 30.00+$valorPreMateria; //Para Intensivo 2023-1I costo de materia  30 $.
$valorExonerar	= 0.00;
//$banco	= "Venezuela;";
//$cuenta	= "01020529800000000479";

// Maximo numero de depositos a presentar:
$maxDepo			= 0;
//Usuario maestro
$masterID		  = 'master';
// Proteccion de las paginas contra boton derecho, no javascript y navegadores no soportados:
if ($enProduccion){
	$botonDerecho = 'oncontextmenu="return false"';
	$noJavaScript = '<noscript><meta http-equiv="REFRESH" content="0;URL=no-javascript.php"></noscript>';
	$noCache	  = "<meta http-equiv=\"Pragma\" content=\"no-cache\">\n";
	$noCache	 .= '<meta http-equiv="Expires" content="-1">';
	$noCacheFin	  = '<head><meta http-equiv="Pragma" content="no-cache"></head>';
}
else {
	$botonDerecho = '';
	$noJavaScript = '';
	$noCache	  = '';
	$noCacheFin	  = '';
}
?>