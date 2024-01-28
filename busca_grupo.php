<?php
include_once('inc/odbcss_c.php');
include_once ('inc/config.php');

$c_asigna = $_POST['c_asigna'];
$seccion = $_POST['seccion'];

$conex = new ODBC_Conn($sedesUNEXPO[$sedeActiva][0],"c","c");

$gSQL = "SELECT grupo FROM tblaca004_lab WHERE inscritos<tot_cup AND lapso='".$lapsoProceso."'  ";
$gSQL.= "AND c_asigna='".$c_asigna."' AND seccion='".$seccion."' ";

@$conex->ExecSQL($gSQL);

if ($conex->filas > 0){
print <<<GRUPO_LAB
<select name="g$c_asigna" id="g$c_asigna" class="peq">
	<option value="">SELECCIONE</option>
GRUPO_LAB;
	for ($i=0;$i < $conex->filas;$i++){
		echo "<option value=\"".$conex->result[$i][0]."\">GRUPO ".$conex->result[$i][0]."</option>";
	}	
print <<<GRUPO_LAB
</select>	
GRUPO_LAB;

}else{
print <<<GRUPO_LAB
<select name="g$c_asigna" id="g$c_asigna" class="peq" disabled>
	<option value="">SIN CUPO</option>
</select>	
GRUPO_LAB;

}


//<select name="g$c_asigna" id="g$c_asigna" class="peq" disabled>
//</select>

?>