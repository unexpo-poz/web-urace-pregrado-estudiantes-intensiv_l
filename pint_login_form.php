<?php
include_once('inc/vImage.php'); 
include_once('inc/config.php'); 

imprima_enc();
if ($inscHabilitada){
	imprima_form();
}
else {
	print <<<x001
		<div style="font-family:arial;font-size:16px; color:red;text-align:center;">
		Las Inscripciones son los días 15, 16 y 17 de agosto 2023, Coordinación de Intensivos.
		</div><br>
x001;
	/*print <<<x001
		<div style="font-family:arial;font-size:36px; color:red;text-align:center;">
		El proceso de inscripci&oacute;n ha sido suspendido hasta nuevo aviso.
		</div><br>
x001;*/

}
imprima_final();

function imprima_enc(){
	global $tProceso, $lapsoProceso, $tLapso, $enProduccion;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<?php
	global $noCache;
	print $noCache;
?>
<title><?php echo $tProceso . $lapsoProceso; ?></title>

  <script language="Javascript" src="md5.js">
   <!--
    alert('Error con el fichero js');
    // -->
  </script>
  <script languaje="Javascript">
<!--

  function validar(f) {
	if ((f.cedula_v.value == "")||(f.contra_v.value == "")) {
		alert("Por favor, escriba su cédula y clave antes de pulsar el botón Entrar");
		return false;
	} 
	else {
		f.contra.value = hex_md5(f.contra_v.value);
		f.contra_v.value = "";
		f.cedula.value = f.cedula_v.value;
		f.cedula_v.value = "";
		f.vImageCodP.value = f.vImageCodC.value;
		f.vImageCodC.value = "";
		window.open("","planillab","left=0,top=0,width=790,height=500,scrollbars=1,resizable=1,status=1");
		<?php if ($enProduccion){ ?>
		setTimeout("location.reload()",90000);
		<?php } ?>
		return true;
	}

}
//-->
  </script>          
<style type="text/css">
<!--
#prueba {
  overflow:hidden;
  color:#00FFFF;
  background:#F7F7F7;
}

.instruc {
  font-family:Arial; 
  font-size: 13px; 
  font-weight: normal;
  background-color: #254B72;
  color: #FFFFFF
}
.normal {
  font-family:Arial; 
  font-size: 14px; 
  font-weight: normal;
  color: #242744;
}
.boton {
  text-align: center; 
  font-family:Arial; 
  font-size: 11px;
  font-weight: normal;
  background-color:#e0e0e0; 
  font-variant: small-caps;
  height: 20px;
  padding: 0px;

}

-->
</style>  

</head>


<body <?php global $botonDerecho; echo $botonDerecho; ?>>

<table id="table1" style="border-collapse: collapse;background: url(imagenes/fondo_index.png) no-repeat" border="0" cellpadding="0" cellspacing="1" width="100%" align="center"><tbody>
   
	
	<td width="750" colspan="3" align="center">
          <p align="center" style="font-family:arial; font-weight:bold; font-size:20px;color:#FFFFFF;" class="instruc">
<?php			echo $tProceso .' '. $tLapso; 
?>		  </p>
			<!-- <p align="center" style="font-family:arial; font-weight:bold; font-size:12pt;color:#FF0000;background-color:#FFFFFF;">
				FASE 2: Inscribir Materias Pre Inscritas y No Pre Inscritas<BR>
				RECUERDA QUE TU TURNO DE INSCRIPCION CAMBIA EN CADA FASE, CONSULTA ANTES DE INGRESAR. <BR>
			</p> -->
    </td>
  </tr>

  
<?php
}
function imprima_form(){
?>		
  <tr>
      <td width="777" align="center" colspan="3">
      <form method="post" name="chequeo" onSubmit="return validar(this)" action="planilla_r.php" target="planillab">
<table style="border-collapse:collapse;padding-left:350px;" border="0" cellpadding="0" cellspacing="1" width="85%" ><tr><td>
			<br><table style="padding-left:280px;"class="normal">
				<tr>
					<td>
						C&eacute;dula:
					</td>
					<td>
						<input name="cedula_v" size="15" tabindex="1" type="text">
					</td>
				<tr>
				<tr>
					<td>
						Clave:
					</td>
					<td>
						<input name="contra_v" size="15" tabindex="2" type="password">
					</td>
				<tr>
			</table>
<br>
			<table style="padding-left:280px;" class="normal" width="100%" border="0">
				<tr>
					<td width="100%">
						C&oacute;digo de Seguridad:&nbsp;&nbsp;<img src="inc/img.php?size=4" height="30px" style="vertical-align: middle;">&nbsp;&nbsp;<input name="vImageCodC" size="5" tabindex="3" type="text"> 
					</td>
				<tr>
				<tr>
					<td>
						<input value="Entrar" name="b_enviar" tabindex="3" type="submit">
					</td>
				</tr>
			</table>
<br>
</td></tr></table>
  
			   
			  <input value="x" name="cedula" type="hidden"> 
			  <input value="x" name="contra" type="hidden">
			  <input value="" name="vImageCodP" type="hidden"> 
  </form>

<?php //imprima_form
}

function imprima_final(){
	global $banco,$cuenta,$valorPreMateria,$valorMateria;
?>
	  </td>
    </tr>
    <tr>
		<td class ="instruc" style="padding-left:10px;padding-top:10px;" width="50%">
			<span style="font-size:8pt;font-weight:bold;">Notas:</span>
			<ul style="padding-right:25px;text-align:justify;font-size:8pt;">
				<li>El valor de cada asignatura es de <strong><?php echo number_format($valorMateria,0,',','.');?> $.</strong> (<?php echo number_format($valorPreMateria,0,',','.')." $. Pre Inscripcion + ";echo number_format($valorMateria,0,',','.')." $. Inscripcion";?>)</li>
				<!-- <li>Realiza un solo dep&oacute;sito por el total de asignaturas a inscribir.</li> -->
				<li>Una vez procesada la inscripci&oacute;n imprime dos (2) planillas y f&iacute;rmalas.</li>
				<li>Identifica al dorso de cada planilla de dep&oacute;sito con tus apellidos, nombres, Nro. de c&eacute;dula, tel&eacute;fono, c&oacute;digo y nombre de la asignatura.</li>
			</ul>
		</td>
	   <td class ="instruc" style="padding-left:10px;padding-top:10px;">
			<span style="font-size:8pt;font-weight:bold;">Notas:</span>
			<ul style="padding-right:25px;text-align:justify;font-size:8pt;">
				<li>Los datos de los dep&oacute;sitos ser&aacute;n verificados con el banco. En caso de existir alguna discrepancia, la preinscripci&oacute;n ser&aacute; anulada.</li>
				<li>La preinscripci&oacute;n <span style="font-weight: bold; color: #FFFF00;"> NO ASEGURA LA APERTURA DE LA ASIGNATURA</span>: Esto depende de la disponibilidad del profesor y del n&uacute;mero de preinscritos.</li>
				<li>No puedes cambiar los datos de los dep&oacute;sitos registrados en una preinscripci&oacute;n previa.</li>
				<li><strong>Consulta el Reglamento de Intensivos Vigente para aclarar las dudas.</strong> <a href="/web/descargas/pdf/Reglamento_Cursos_Intensivos.pdf" target="_blank" style="font-weight: bold; color: #FFFF00;">Haz clic aqu&iacute;</a>
				</li>
			</ul>
		</td>
  </tr></tbody></table>
</body>
<?php
//Evitar que la pagina se guarde en cache del cliente
global $noCacheFin;
print $noCacheFin;
?>
</html>
<?php
} //imprima_final	 
?>
