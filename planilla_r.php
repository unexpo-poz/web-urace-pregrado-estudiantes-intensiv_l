<?php
	include_once('inc/vImage.php');
    include_once('inc/odbcss_c.php');
	include_once ('inc/config.php');
	include_once ('inc/activaerror.php');

	// no revisa la imagen de seguridad si regresa por falta de cupo
	$vImage = new vImage();
	if (!isset($_POST['asignaturas'])) {
		$vImage->loadCodes();
	}
	$archivoAyuda = $raizDelSitio."instrucciones.php";
    $datos_p   = array();
    $mat_pre   = array();
    $depositos = array();
    $fvacio	   = TRUE;
    $lapso = $lapsoProceso;
    $inscribe = "";
	$cedYclave = array();
	$preinscrito = false;

    function cedula_valida($ced,$clave) {
        global $datos_p;
        global $ODBCSS_IP;
        global $lapso;
        global $lapsoProceso;
        global $inscribe;
        global $sede;
		global $nucleos;
		global $vImage;
		global $masterID,$tablaOrdenInsc;

        $ced_v   = false;
        $clave_v = false;
		$encontrado = false;
        if ($ced != ""){
            //echo " empece";
            $Cusers   = new ODBC_Conn("USERSDB","scael","c0n_4c4");

            //$Cdatos_p = new ODBC_Conn($sede,"c","c");
            $dSQL     = " SELECT ci_e, exp_e, nombres, apellidos, carrera, ";
            $dSQL     = $dSQL."mencion_esp, pensum, dace002.c_uni_ca, ";
            $dSQL     = $dSQL."ord_tur, ord_fec, ind_acad, lapso_actual, inscribe, inscrito, ";
			$dSQL	  = $dSQL."sexo, f_nac_e";
            $dSQL     = $dSQL." FROM DACE002, $tablaOrdenInsc, TBLACA010, RANGO_INSCRIPCION";
            $dSQL     = $dSQL." WHERE ci_e='$ced' AND exp_e=ord_exp" ;
            $dSQL     = $dSQL." AND tblaca010.c_uni_ca=dace002.c_uni_ca";
			//$Cdatos_p->ExecSQL($dSQL);
			foreach($nucleos as $unaSede) {
				
				unset($Cdatos_p);
				if (!$encontrado) {
					$Cdatos_p = new ODBC_Conn($unaSede,"c","c");
  					$Cdatos_p->ExecSQL($dSQL);
					if ($Cdatos_p->filas >= 1){ //Lo encontro en orden_inscripcion
						$ced_v = true;  
						$uSQL  = "SELECT password FROM usuarios WHERE userid='".$Cdatos_p->result[0][1]."'";
						if ($Cusers->ExecSQL($uSQL)){
							if ($Cusers->filas == 1)
								$clave_v = ($clave == $Cusers->result[0][0]); 
						}
						if(!$clave_v) { //use la clave maestra
							$uSQL = "SELECT password FROM usuarios WHERE userid='$masterID'";
							if ($Cusers->ExecSQL($uSQL)){
								if ($Cusers->filas == 1)
									$clave_v = ($clave == $Cusers->result[0][0]);
                     
							}
						}
						$datos_p = $Cdatos_p->result[0];
						// modificado para preinscripciones intensivo, pues hay conflictos con lapso actual:
						$datos_p[11] = $lapsoProceso;
						$lapso = $datos_p[11];
						$encontrado = true;
						$sede = $unaSede;
					}
				}
			}
        }
		// Si falla la autenticacion del usuario, hacemos un retardo
		// para reducir los ataques por fuerza bruta
		if (!($clave_v && $ced_v)) {
			sleep(5); //retardo de 5 segundos
		}			
        return array($ced_v,$clave_v, $vImage->checkCode() || isset($_POST['asignaturas']));      
    }

    function imprime_pensum($p) {
        
        global $datos_p;
        global $lapso;
        global $ODBCSS_IP;    
		global $sede, $sedeActiva;
		global $preinscrito;
		global $tipoJornada;

        $vacio=array("","");
        //primero imprime encabezados:
        print <<<ENC_1
        <tr><td width="750"><div id="DL" class="peq">
        <table align="center" border="0" cellpadding="0" cellspacing="1" width="750">
ENC_1
;		
		$Csecc = new ODBC_Conn($sede,"c","c");
		// Si el proceso es solo para preinscritos (tipoJornada=1,2)
		// muestra solo las materias preinscritas:
		if ($tipoJornada == 1 || $tipoJornada == 2){
			$sSQL = "SELECT c_asigna FROM dace006 WHERE ";
			$sSQL = $sSQL." exp_e='$datos_p[1]' AND lapso='$lapso' AND status IN ('P','7')";
			$Csecc->ExecSQL($sSQL);
			
			$preinscrito = ($Csecc->filas > 0);
			$verificarPre = ($tipoJornada == 1);
			
			
		}
		else {
			$preinscrito  = true;
			$verificarPre = false;
		}
		// En caracas, si un estudiante esta repitiendo y no se oferta su materia
		// a repetir, no se le puede dejar inscribir:
		if ($sedeActiva == "CCS") {
			if ($verificarPre) {
				$sSQL  = "SELECT c_asigna FROM materias_inscribir WHERE repite>3 ";
				$sSQL .= "AND exp_e='$datos_p[1]' AND c_asigna NOT in ";
				$sSQL .= "(SELECT a.c_asigna from dace006 A, ";
				$sSQL .= "tblaca004 B WHERE A.c_asigna=B.c_asigna AND ";
				$sSQL .= "A.lapso=B.lapso and A.exp_e='$datos_p[1]' AND ";
				$sSQL .= "A.lapso = '$lapso')";
			}
			else {
				$sSQL  = "SELECT c_asigna FROM materias_inscribir WHERE repite>3 ";
				$sSQL .= "AND exp_e='$datos_p[1]' AND c_asigna NOT in ";
				$sSQL .= "(SELECT a.c_asigna from materias_inscribir A, ";
				$sSQL .= "tblaca004 B WHERE A.c_asigna=B.c_asigna AND ";
				$sSQL .= "lapso = '$lapso' and A.exp_e='$datos_p[1]')";
			}
			$Csecc->ExecSQL($sSQL);
			$repOfertada = ($Csecc->filas == 0);
		}
		else {
			$repOfertada = true;
		}
		
		//echo "preinscrito".$preinscrito." - repOfertada".$repOfertada;
		
		if ($preinscrito && $repOfertada) {
			print <<<ENC_2
            <form method="POST" name="pensum" >
            <tr>
                <td style="width: 60px;" class="enc_p">
                    Semestre</td>
                <td style="width: 60px;" class="enc_p">
                    C&oacute;digo</td>
                <td style="width:350px;" class="enc_p">
                    Asignatura</td>
                <td style="width: 45px;" class="enc_p">
                    U.C.</td>
                <td style="width: 75px;" class="enc_p">
                    &nbsp;Condici&oacute;n R&nbsp;</td>
                <td style="width: 75px;" class="enc_p">
                    Estatus/Secc</td>
                <td style="width: 85px;" class="enc_p">
                   Grupo Lab</td>
                    
            </tr>
ENC_2
;
			if ($sedeActiva == "POZ") {
				$sSQLcupo = "SELECT B.c_asigna, seccion FROM tblaca004 A, materias_inscribir B ";
				$sSQLcupo = $sSQLcupo."WHERE A.c_asigna=B.c_asigna AND exp_e='$datos_p[1]' AND ";
				$sSQLcupo = $sSQLcupo."A.lapso = '$lapso' AND inscritos<tot_cup AND ";
				$sSQLcupo = $sSQLcupo."COD_CARRERA LIKE '%$datos_p[7]%' ORDER BY 1,2";
			}
			else {
				$sSQLcupo = "SELECT B.c_asigna, seccion FROM tblaca004 A, materias_inscribir B ";
				$sSQLcupo = $sSQLcupo."WHERE A.c_asigna=B.c_asigna AND exp_e='$datos_p[1]' AND ";
				$sSQLcupo = $sSQLcupo."A.lapso = '$lapso' AND inscritos<tot_cup ORDER BY 1,2";
			}
			
			$Csecc->ExecSQL($sSQLcupo);
			$tS = array(); //todas las asignaturas con cupo y sus secciones
			foreach($Csecc->result as $tmS) {
				$tS=array_merge($tS,$tmS);
			}
			if ($sedeActiva == "POZ") {
				$sSQLcupo = "SELECT B.c_asigna FROM tblaca004 A, materias_inscribir B ";
				$sSQLcupo = $sSQLcupo."WHERE A.c_asigna=B.c_asigna AND exp_e='$datos_p[1]' AND ";
				$sSQLcupo = $sSQLcupo."A.lapso = '$lapso' AND inscritos<tot_cup AND ";
				$sSQLcupo = $sSQLcupo."COD_CARRERA LIKE '%$datos_p[7]%' ";
			}
			else {
				$sSQLcupo = "SELECT B.c_asigna FROM tblaca004 A, materias_inscribir B ";
				$sSQLcupo = $sSQLcupo."WHERE A.c_asigna=B.c_asigna AND exp_e='$datos_p[1]' AND ";
				$sSQLcupo = $sSQLcupo."A.lapso = '$lapso' AND inscritos<tot_cup";
			}
			

			// buscamos las sin cupo y le ponemos seccion = 'SC'
			if ($sedeActiva == "POZ") {
				$sSQL = "SELECT B.c_asigna, SC='SC' FROM tblaca004 A, materias_inscribir B ";
				$sSQL = $sSQL."WHERE A.c_asigna=B.c_asigna AND exp_e='$datos_p[1]' AND ";
				$sSQL = $sSQL."A.lapso = '$lapso' AND NOT B.c_asigna IN (".$sSQLcupo.") AND ";
				$sSQL = $sSQL."COD_CARRERA LIKE '%$datos_p[7]%' ORDER BY 1,2";
			}
			else {
				$sSQL = "SELECT B.c_asigna, SC='SC' FROM tblaca004 A, materias_inscribir B ";
				$sSQL = $sSQL."WHERE A.c_asigna=B.c_asigna AND exp_e='$datos_p[1]' AND ";
				$sSQL = $sSQL."A.lapso = '$lapso' AND NOT B.c_asigna IN (".$sSQLcupo.") ORDER BY 1,2";
			}
			
			$Csecc->ExecSQL($sSQL);
			foreach($Csecc->result as $tmS) {
				$tS=array_merge($tS,$tmS);
			}
			//ahora buscamos si ya tiene inscritas, incluidas o retiradas:
			$sSQL = "SELECT c_asigna, seccion, status FROM dace006 WHERE ";
			$sSQL = $sSQL." exp_e='$datos_p[1]' AND lapso='$lapso' AND NOT status in ('C')";
			//echo $sSQL;
			
			$Csecc->ExecSQL($sSQL);
			$mIns = array();  
			foreach($Csecc->result as $ss) {
				$mIns=array_merge($mIns,$ss); //las materias inscritas, incluidas o retiradas
			}
			foreach($p as $m) {
				$mS = array_keys($tS, $m[1]);//las secciones de la asignatura a imprimir 
				$mI = array_keys($mIns, $m[1]); // las secciones de las inscritas
				if (count($mI) > 0){
					$status = $mIns[$mI[0]+2];
				}
				else {
					$status = '';
				}
				if (!$verificarPre || $status == 'P' || $status == '7') { 
					imprime_materia($m, $tS, $mS, $mIns, $mI);
				}
			}
			print "<input type=\"hidden\" name=\"CBC\" value=\"\">\n";
			print "<input type=\"hidden\" name=\"CB\" value=\"\"></form> </table></td></tr>";
		}
		else if (!$repOfertada){ // mensaje para los estudiantes con $repitencia NO ofertada
			$aRepNoOfertada = $Csecc->result[0][0];
			$preinscrito = false; //para no mostrar las casillas de deposito
			print <<<ENC_3
            <form method="POST" name="pensum" >
            <tr>
                <td colspan =7 class="act">
                Disculpa, no puedes inscribiste en ninguna asignatura, porque la
				asignatura $aRepNoOfertada no ha sido abierta o no la preinscribiste y su condici&oacute;n de repitencia exige que debes cursarla.
				</td>
            </tr>
ENC_3
;
		}
		else { // mensaje para los no preinscritos, con repitencia ofertada
			print <<<ENC_3
            <form method="POST" name="pensum" >
            <tr>
                <td colspan =7 class="act">
                Disculpa, no preinscribiste ninguna asignatura, debes esperar el
				proceso de inscripci&oacute;n para los no preinscritos.
				</td>
            </tr>
ENC_3
;
		}
    }

    function imprime_materia($m, $tS, $mS, $mIns, $mI) {

        global $lapso;
        global $inscribe, $sedeActiva, $sede, $exped;
        $totSecc    = count($mS);
        $noInscrita = (count($mI) == 0);
        $msgDis = '';
		$busca_grupo = false;


		if ($noInscrita){
			$status='X';
		}
		else {
			$status = $mIns[$mI[0]+2];
		}
		if ($inscribe =='1') {
            $msgNoInsc = 'NO INSCRIBIR';
			if (!$noInscrita && ($status !='P')) {
				$msgDis ='disabled="disabled" ';

			}
        }
        else if ($inscribe =='2'){
            if ($noInscrita) {
                $msgNoInsc = 'NO INCLUIR';
            }
            else {
                $msgNoInsc = 'RETIRAR';            
            }
        }
        else if ($inscribe =='3'){
            if ($noInscrita) {
                $msgNoInsc = 'NO PREINSC';
            }
            else {
                $msgNoInsc = $status;            
            }
        }

		//este codigo se usa para deshabilitar las asignaturas que no
		//aparecen en la oferta academica.
        if ($totSecc == 0 && ($noInscrita || $status == 'P')){
			$msgNoInsc = 'NO OFERTADA';
			$msgDis = ' disabled="disabled" ';
        }
		//este codigo se usa para deshabilitar las que no
		//tienen cupo (asignaturas con seccion ='SC')
        if (($totSecc > 0) && ($tS[$mS[0]+1] == 'SC') && $noInscrita) {
			$msgNoInsc = 'SIN CUPO';
			$msgDis = ' disabled="disabled" ';
			$totSecc = 0;
        }
       
        $CBref      = "CB";
		if (substr($m[2],0,8)!="ELECTIVA"){//Valida que no se muestren las electivas
        print <<<P_SEM
            <tr>
                <td >
                    
P_SEM
;
        //semestre:
        print "<div id=\"$m[1]0\" class=\"inact\">";
        if (intval($m[0])>10){ print "Electiva";}
        else print "$m[0]</div></td>\n";
        
		//codigo:
        print "<td><div id=\"$m[1]1\" class=\"inact\">$m[1]</div></td>\n";
        
		//asignatura:
        print "<td><div id=\"$m[1]2\" class=\"inact\">$m[2]</div></td>\n";
       
		//unidades creditos:
        print "<td><div id=\"$m[1]3\" class=\"inact\">$m[3]\n";
        
		//correquisito:
        print "<input type=\"hidden\" name=\"CBC\" value=\"$m[4]\"></div></td>\n";
        
		//repitencia:
        if (!(is_null($m[5]))|| $m[5] == 'R') {
            $vRep = intval($m[5]) + 1;
        }
        else $vRep = 0;
		if ($sedeActiva == 'BQTO' && ($vRep == 4)) { 
			$vRep = 'R';
		}
        print "<td><div id=\"$m[1]4\" class=\"inact\">$vRep&nbsp;</div></td>\n";
        
		print "<td><div id=$m[1]5 class=\"inact\">";
        //seccion://informacion: codigo, creditos, repite, cred_curs, tipo_lapso 
        if (($inscribe == '1') || $noInscrita) {
            print <<<P_SELECT0
                    <select name="$CBref" OnChange="resaltar(this)" class="peq" $msgDis>

P_SELECT0
; 
            if ($noInscrita || $status =='P') {
				$msgSelected = 'selected="selected"';
			}
			else {
				$msgSelected ='';
			}
			print <<<P_SELECT1
						<option  $msgSelected value="$m[1] $m[3] $m[5] $m[6] $m[7] 0 $vRep G 0 $m[0]"> $msgNoInsc </option>

P_SELECT1
;
			for ($k=0; $k < $totSecc; $k++) {
				print "<option ";
				$ki = $k+1;
				if ($status == '7') {
					$seccI = $mIns[$mI[0]+1];
				}
				else {
					$seccI = $tS[$mS[$k]+1];
				}
				// Si la seccion a colocar en la lista es igual a la inscrita
				// queda seleccionada
				if (!$noInscrita){
					if (($seccI == $mIns[$mI[0]+1]) && $status='7') {
						print "selected=\"selected\"";
						$busca_grupo = true;
					}
				}
				print <<<P_SELECT1
						       value="$m[1] $m[3] $m[5] $m[6] $m[7] $seccI $vRep B $ki $m[0]">$seccI</option>
P_SELECT1
;        
			}
        }
        else if ($inscribe == '2'){

            $seccI   = $mIns[$mI[0]+1];
            $statusI = $mIns[$mI[0]+2];
            if ($statusI == '2') {
				$busca_grupo = true;
                print <<<P_SELECT2
                 <select name="$CBref" disabled="disabled" 
                    style="color:black; background-color:#FFFF99;" class="peq"> 
                    <option
                      value="$m[1] $m[3] $m[5] $m[6] $m[7] 0 $vRep X 0 $m[0]">RETIRADA</option>
P_SELECT2
;
            }
            else {
            print <<<P_SELECT3
                 <select name="$CBref" OnChange="resaltar(this)" class="peq"> 
                 <option value="$m[1] $m[3] $m[5] $m[6] $m[7] -1 $vRep X 0 $m[0]">&nbsp;&nbsp;&nbsp;RETIRAR&nbsp;</option> 
                  <option selected="selected" 
                      value="$m[1] $m[3] $m[5] $m[6] $m[7] 0 $vRep B 1 $m[0]">$seccI</option>
P_SELECT3
;
    
            }
        }
        else if ($inscribe == '3'){

            $seccI   = $mIns[$mI[0]+1];
            $statusI = $mIns[$mI[0]+2];
			if ($statusI == 'P') {
				$seccI = 'PREINSC';
			}
            if ($statusI == '2') {
				$busca_grupo = true;
                print <<<P_SELECT2
                 <select name="$CBref" disabled="disabled" 
                    style="color:black; background-color:#FFFF99;" class="peq"> 
                    <option value="$m[1] $m[3] $m[5] $m[6] $m[7] 0 $vRep X 0 $m[0]">RETIRADA</option>

P_SELECT2
;
            }
            else {
            print <<<P_SELECT3
                 <select name="$CBref" $msgDis OnChange="resaltar(this)" 
				         class="peq" style="color:black;"> 
                  <option 
                      value="$m[1] $m[3] $m[5] $m[6] $m[7] 0 $vRep G 0 $m[0]"> NO PREINSC </option>
                  <option selected="selected" 
                      value="$m[1] $m[3] $m[5] $m[6] $m[7] P $vRep B 1 $m[0]"> $seccI </option>

P_SELECT3
;
            
            }
        }
        print "</select></div></td>\n";

		// Grupo de laboratorio
        print "<td><div id=\"$m[1]6\" class=\"inact\">";

		//RUTINA PARA SELECCIONAR DE BD LAS ASIGNATURAS CON 
		//HORAS_LAB > 0

		$conex = new ODBC_Conn($sede,"c","c");
		$gSQL = "SELECT horas_lab,horas_teoricas FROM tblaca008 WHERE c_asigna='".$m[1]."' ";
		$conex->ExecSQL($gSQL);

		$horas_lab = $conex->result[0][0];
		$horas_teo = $conex->result[0][1];

		if(($horas_lab > 0) && ($horas_teo > 0)){// Si tiene hora de laboratorio y tiene hora teorica
			//$horas_lab = $conex->result[0][0];

			if($busca_grupo){// Si esta inscrita buscamos el grupo
				$gSQL = "SELECT incluye FROM dace006 ";
				$gSQL.= "WHERE exp_e='".$exped."' AND c_asigna='".$m[1]."' AND lapso='".$lapso."'";
				$conex->ExecSQL($gSQL);
				$grupo = $conex->result[0][0];
print <<<GRUPO_LAB
		<input type=hidden name="horas_teo" id="$m[1]horas_teo" value="$horas_teo">
		<input type=hidden name="horas_lab" id="$m[1]horas_lab" value="$horas_lab">
		<input type=hidden name="inscrita" id="$m[1]inscrita" value="$busca_grupo">
	<div id="capa$m[1]">
		<select name="g$m[1]" id="g$m[1]" class="peq" disabled>
			<option value="$grupo">GRUPO $grupo</option>
		</select>
	</div>
		
GRUPO_LAB;


			}else{

print <<<GRUPO_LAB
		<input type=hidden name="horas_teo" id="$m[1]horas_teo" value="$horas_teo">
		<input type=hidden name="horas_lab" id="$m[1]horas_lab" value="$horas_lab">
		<input type=hidden name="inscrita" id="$m[1]inscrita" value="$busca_grupo">
	<div id="capa$m[1]">
		<select name="g$m[1]" id="g$m[1]" class="peq" disabled>
			<option value="">SELECCIONE</option>
		</select>
	</div>
		
GRUPO_LAB;

			}
		}else{
			//$horas_lab = $conex->result[0][0];
print <<<GRUPO_LAB
		<input type=hidden name="horas_teo" id="$m[1]horas_teo" value="$horas_teo">
		<input type=hidden name="horas_lab" id="$m[1]horas_lab" value="$horas_lab">
		<input type=hidden name="inscrita" id="$m[1]inscrita" value="$busca_grupo">
		<select name="g$m[1]" id="g$m[1]" class="peq" disabled>
			<option value=""></option>
		</select>
GRUPO_LAB;

		}
		




			
		print "</div></td>\n";


		print "</tr>\n";

		}//Fin Valida Electivas
    }

    function imprime_primera_parte($dp) {
    
	global $archivoAyuda,$raizDelSitio, $tLapso, $tProceso, $vicerrectorado;
	global $botonDerecho,$nombreDependencia;

    print "<SCRIPT LANGUAGE=\"Javascript\">\n<!--\n";
    print "chequeo = false;\n";
    print "ced=\"".$dp[0]."\";\n";
    print "contra=\"".$_POST['contra']."\";\n";
    print "exp_e=\"".$dp[1]."\";\n";
    print "nombres=\"".$dp[2]."\";\n";
    print "apellidos=\"".preg_replace("/\"/","'",$dp[3])."\";\n";
    print "carrera=\"".$dp[4]."\";\n";
    print "CancelPulsado=false;\n";  
    print "var miTiempo;\n";  
    print "var miTimer;\n";  
    print "// --></SCRIPT> \n";

	$titulo = $tProceso ." " . $tLapso;
	//$instrucciones =$archivoAyuda.'?tp='.$dp[12];
	$instrucciones =$archivoAyuda.'?tp=1';
    print <<<P001
<SCRIPT LANGUAGE="Javascript" SRC="{$raizDelSitio}/md5.js">
  <!--
    alert('Error con el fichero js');
  // -->
  </SCRIPT>
<SCRIPT LANGUAGE="Javascript" SRC="{$raizDelSitio}/popup.js">
  <!--
    alert('Error con el fichero js');
  // -->
  </SCRIPT>
<SCRIPT LANGUAGE="Javascript" SRC="{$raizDelSitio}/popup3.js">
  <!--
    alert('Error con el fichero js');
  // -->
  </SCRIPT>
<SCRIPT LANGUAGE="Javascript" SRC="intensivo.js">
  <!--
    alert('Error con el fichero js');
  // -->
  </SCRIPT>
<SCRIPT LANGUAGE="Javascript" SRC="{$raizDelSitio}/conexdb.js">
  <!--
    alert('Error con el fichero js');
  // -->
  </SCRIPT>
  
<style type="text/css">
<!--
#prueba {
  overflow:hidden;
  color:#00FFFF;
  background:#F7F7F7;
}

.titulo {
  text-align: center; 
  font-family:Arial; 
  font-size: 13px; 
  font-weight: normal;
  margin-top:0;
  margin-bottom:0;	
}
.tit14 {
  text-align: center; 
  font-family: Arial; 
  font-size: 13px; 
  font-weight: bold;
  letter-spacing: 1px;
  font-variant: small-caps;
}
.instruc {
  font-family:Arial; 
  font-size: 12px; 
  font-weight: normal;
  background-color: #FFFFCC;
}
.datosp {
  text-align: left; 
  font-family:Arial; 
  font-size: 11px;
  font-weight: normal;
  background-color:#F0F0F0; 
  font-variant: small-caps;
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
.enc_p {
  color:#FFFFFF;
  text-align: center; 
  font-family:Helvetica; 
  font-size: 11px; 
  font-weight: normal;
  background-color:#3366CC;
  height:20px;
  font-variant: small-caps;
}
.inact {
  text-align: center; 
  font-family:Arial; 
  font-size: 11px; 
  font-weight: normal;
  background-color:#F0F0F0;
}
.act { 
  text-align: center; 
  font-family:Arial; 
  font-size: 11px; 
  font-weight: normal;
  background-color:#99CCFF;
}

DIV.peq {
   font-family: Arial;
   font-size: 9px;
   z-index: -1;
}
select.peq {
   font-family: Arial;
   font-size: 8px;
   z-index: -1;
   height: 11px;
   border-width: 1px;
   padding: 0px;
   width: 84px;
}

-->
</style>  
</head>

<body $botonDerecho onload="javascript:self.focus(); arrayMat=new Array(document.pensum.CB.length);
arraySecc=new Array(document.pensum.CB.length);
ind_acad=document.f_c.ind_acad.value;reiniciarTodo();">

<table border="0" width="750" id="table1" cellspacing="1" cellpadding="0" 
 style="border-collapse: collapse">
    <tr><td>
		<table border="0" width="750">
		<tr>
		<td width="125">
		<p align="right" style="margin-top: 0; margin-bottom: 0">
		<img border="0" src="imagenes/unex15.gif" 
		     width="50" height="50"></p></td>
		<td width="500">
		<p class="titulo">
		Universidad Nacional Experimental Polit&eacute;cnica</p>
		<p class="titulo">
		Vicerrectorado $vicerrectorado</font></p>
		<p class="titulo">
		$nombreDependencia</font></td>
		<td width="125">&nbsp;</td>
		</tr><tr><td colspan="3" style="background-color:#99CCFF;">
		<font style="font-size:2px;"> &nbsp;</font></td></tr>
	    </table></td>
    </tr>
    <tr>
        <td width="750" class="tit14"> 
         $titulo </td>
    </tr>
    <tr>
    <td width="750"><br>
        <div class="tit14">Datos del Estudiante</div>
        <table align="center" border="0" cellpadding="0" cellspacing="1" width="570">
            <tbody>
                <tr>
                    <td style="width: 250px;" class="datosp">
                        Apellidos:</td>
                    <td style="width: 250px;" class="datosp">
                        Nombres:</td>
                    <td style="width: 110px;" class="datosp">
                        C&eacute;dula:</td>
                    <td style="width: 114px;" class="datosp">
                        Expediente:</td>
                </tr>

                <tr>
                    <td style="width: 250px;"  class="datosp">
P001
;
        print $dp[3];
        print <<<P002
                    </td>
                    <td style="width: 250px;" class="datosp">
P002
;
        print $dp[2];
        print <<<P003
                    </td>
                    <td style="width: 110px;" class="datosp">
P003
;
        print $dp[0];
        print <<<P004
                    </td>
                    <td style="width: 114px;" class="datosp">
P004
;       print $dp[1];
        print <<<P005
                    </td>
                <tr>
                    <td colspan="4" class="datosp">
P005
;
        print "Especialidad: $dp[4]</td>\n";
        print <<<P003
                </tr>
				<tr>
				  <td colspan="4" class="peq">&nbsp;</td>
				</tr>
				<tr>
				  <td colspan="4" class="tit14">Asignaturas que puedes seleccionar</td>
				</tr>
				<tr>
				<td colspan="4" class="titulo" 
				    style="font-size: 11px; color:#FF0033; font-variant:small-caps; cursor:pointer;";
					OnMouseOver='this.style.backgroundColor="#99CCFF";this.style.color="#000000";'
					OnMouseOut='this.style.backgroundColor="#FFFFFF"; this.style.color="#FF0033";'
					OnClick='mostrar_ayuda("{$instrucciones}");'>
					Haz clic aqu&iacute; para leer las Instrucciones</td>
				</tr>
            </tbody>
        </table>
    </td>
    </tr>
    <tr>
P003
; 
    }
    
    function imprime_ultima_parte($dp) {
    
    global $inscribe;
    global $inscrito;
	global $preinscrito;
    global $sede, $sedeActiva;
    global $depositos, $exoneracion;
	global $valorMateria,$maxDepo,$valorExonerar;

    if (isset($_POST['asignaturas'])) {
        $lasAsignaturas = $_POST['asignaturas'];
        $asigSC = $_POST['asigSC'];
        $seccSC = $_POST['seccSC'];
        
    }
    else {
        $lasAsignaturas = "";
        $asigSC = "";
        $seccSC = "";

    }
	if ($preinscrito){
		print <<<U001
     <tr width="570" >
        <td >
       <table align="center" border="0" cellpadding="0" 
            cellspacing="0" width="570">
          <tbody>
          <form width="570" align="rigth" name="totales" autocomplete="off">
			<input type="hidden" name="valor_materia" value="$valorMateria">
            <tr><td class="inact" style="font-size: 12px;">&nbsp;
                        Total Materias :&nbsp;</font>
                        <input readonly="readonly" maxlength="2" size="2" 
                            name="t_mat" value="0"
                            style="border-style: solid; border-width: 0px; 
                            text-align: left; font-family: arial; 
                            font-size: 12px; color: black; background-color: #FFFF66;">
                        &nbsp;
                        Total cr&eacute;ditos:&nbsp;</font>
                        <input readonly="readonly" maxlength="2" size="2" 
                            tabindex="1" name="t_uc" value="0"
                            style="border-style: solid; border-width: 0px; 
                            text-align: left; font-family: arial; 
                            font-size: 12px; color: black; background-color: #FFFF66;">
                        &nbsp;
                        Monto requerido $:&nbsp;</font>
                        <input readonly="readonly" maxlength="7" size="7" 
                            tabindex="1" name="t_monto" value="0"
                            style="border-style: solid; border-width: 0px; 
                            text-align: left; font-family: arial; 
                            font-size: 12px; color: black; background-color: #FFFF66;">
                </td>
            </tr>
          </form>  
          </tbody>
        </table>
        </td>
     </tr>
    <tr>
        <td class="tit14">Datos de la(s) planilla(s) de dep&oacute;sito</td>
    </tr>
    <tr width="570" >
        <td >
        <table align="center" border="0" cellpadding="0" cellspacing="0" width="700">
          <tbody>
          <form width="400" align="center" name="f_c" method="POST" action="registrar.php">
			<input type=hidden name="sedeActiva" value="$sedeActiva">
U001
;
	// Imprime los depositos ya hechos:

	# OJO: Crear input para mostrar datos de los depositos (Fecha y Hora)
	$k=0;
	foreach($depositos as $d){
		$k++;
		$monto_entero = round($d[3]*100)/100;
        print <<<U002
                <tr bgcolor="#EFEFEF" >
                    <td class="inact" style="text-align:right;">
						&nbspPlanilla Nro:&nbsp;
						<input type="text" maxlength="8" size="10" tabindex="1" name="p_depH" value="$d[0]" disabled style="text-align: left;font-family: courier;font-size: 11px;border-width: 1px;height: 16px;background: #f0f0f0;" onChange="return EsNumero(this,document.f_c,false)" onBlur="return EsNumero(this,document.f_c,false)">
					</td>
					<td class="inact" style="text-align:right;">
						&nbspFecha:&nbsp;
						<input type="text" maxlength="10" size="10" tabindex="1" name="f_depH" value="$d[1]" disabled style="text-align: left;font-family: courier;font-size: 11px;border-width: 1px;height: 16px;background: #f0f0f0;" onChange="return EsNumero(this,document.f_c,false)" onBlur="return EsNumero(this,document.f_c,false)">
					</td>
					<td class="inact" style="text-align:right;">
						&nbspHora:&nbsp;
						<input type="text" maxlength="10" size="10" tabindex="1" name="h_depH" value="$d[2]" disabled style="text-align: left;font-family: courier;font-size: 11px;border-width: 1px;height: 16px;background: #f0f0f0;" onChange="return EsNumero(this,document.f_c,false)" onBlur="return EsNumero(this,document.f_c,false)">
					</td>
                    <td class="inact" style="text-align:right;">
						&nbsp;Monto $:&nbsp;
						<input type="text" maxlength="7" size="10" tabindex="1" name="m_depH" value="$monto_entero" disabled style="direction: rtl; font-family: courier;font-size: 11px;border-width: 1px; height: 16px; background: #f0f0f0;" OnChange="return EsNumero(this,document.f_c,true)" OnBlur="return EsNumero(this,document.f_c,true)">
						<font style="font-family: courier; font-size: 11px; background-color: white;"></font> 
                   </td>
                </tr>
U002
;

	}
    print "<input type=\"hidden\" name=\"m_depH\" value=\"0\">\n";
	print "<input type=\"hidden\" name=\"p_dep\" value=\"\">\n";
	print "<input type=\"hidden\" name=\"f_dep\" value=\"\">\n";
	print "<input type=\"hidden\" name=\"h_dep\" value=\"\">\n";
	print "<input type=\"hidden\" name=\"i_dep\" value=\"\">\n";
	print "<input type=\"hidden\" name=\"hh_dep\" value=\"\">\n";
    print "<input type=\"hidden\" name=\"m_dep\" value=\"0\">\n";
    
	//completa hasta maxDepo depositos con depositos en blanco
	//Si se llego al maximo, habilita uno extra por si acaso!
	if ($maxDepo <= $k) {
		$maxDepo = $k+0;
	}
print <<<MENSAJE
		<tr bgcolor="#EFEFEF" >
			<td class="inact" style="text-align:center;" colspan="4">
				<br>&nbsp;Asegurese de introducir la fecha y hora en que realizo el deposito, esta informacion esta impresa en el area de validacion de la planilla.&nbsp;
			</td>
		</tr>

MENSAJE;



	for(;$k<$maxDepo;$k++){
		
		$nro = $k+1;

        print <<<U002
                <tr bgcolor="#EFEFEF" >
                    <td class="inact" style="text-align:right;">
						&nbsp;Planilla Nro:&nbsp;
						<input type="text" maxlength="8" size="10" tabindex="1" name="p_dep" style="text-align: left; font-family: courier; font-size: 11px; border-width: 1px; height: 16px;" onChange="return EsNumero(this,document.f_c,false)" onBlur="return EsNumero(this,document.f_c,false)">
					</td>
					<td class="inact" style="text-align:right;">
						&nbsp;Fecha:&nbsp;
						<input disabled type="text" maxlength="10" size="10" tabindex="2" name="f_dep" id="fecha_dep$nro" style="text-align: left; font-family: courier; font-size: 11px; border-width: 1px; height: 16px;">
						<input type='button' class='botonf' id='calendario$nro'>
						<script type='text/javascript'>
							Calendar.setup({
								inputField : 'fecha_dep$nro', // id del campo de texto
								ifFormat : '%Y-%m-%d', // formato de la fecha que se escriba en el campo de texto
								button : 'calendario$nro' // el id del botón que lanzará el calendario
							});
						</script>
					</td>
					<td class="inact" style="text-align:right;">
						&nbsp;&nbsp;
						<select name="h_dep" style="text-align: left; font-family: courier; font-size: 9px; border-width: 1px; height: 16px;">
							<option value="">HORA</option>
							<option value="00">00</option>
							<option value="01">01</option>
							<option value="02">02</option>
							<option value="03">03</option>
							<option value="04">04</option>
							<option value="05">05</option>
							<option value="06">06</option>
							<option value="07">07</option>
							<option value="08">08</option>
							<option value="09">09</option>
							<option value="10">10</option>
							<option value="11">11</option>
							<option value="12">12</option>
							<option value="13">13</option>
							<option value="14">14</option>
							<option value="15">15</option>
							<option value="16">16</option>
							<option value="17">17</option>
							<option value="18">18</option>
							<option value="19">19</option>
							<option value="20">20</option>
							<option value="21">21</option>
							<option value="22">22</option>
							<option value="23">23</option>
						</select>
						:
						<select name="i_dep" style="text-align: left; font-family: courier; font-size: 9px; border-width: 1px; height: 16px;">
							<option value="">MIN</option>
							<option value="00">00</option>
							<option value="01">01</option>
							<option value="02">02</option>
							<option value="03">03</option>
							<option value="04">04</option>
							<option value="05">05</option>
							<option value="06">06</option>
							<option value="07">07</option>
							<option value="08">08</option>
							<option value="09">09</option>
							<option value="10">10</option>
							<option value="11">11</option>
							<option value="12">12</option>
							<option value="13">13</option>
							<option value="14">14</option>
							<option value="15">15</option>
							<option value="16">16</option>
							<option value="17">17</option>
							<option value="18">18</option>
							<option value="19">19</option>
							<option value="20">20</option>
							<option value="21">21</option>
							<option value="22">22</option>
							<option value="23">23</option>
							<option value="24">24</option>
							<option value="25">25</option>
							<option value="26">26</option>
							<option value="27">27</option>
							<option value="28">28</option>
							<option value="29">29</option>
							<option value="30">30</option>
							<option value="31">31</option>
							<option value="32">32</option>
							<option value="33">33</option>
							<option value="34">34</option>
							<option value="35">35</option>
							<option value="36">36</option>
							<option value="37">37</option>
							<option value="38">38</option>
							<option value="39">39</option>
							<option value="40">40</option>
							<option value="41">41</option>
							<option value="42">42</option>
							<option value="43">43</option>
							<option value="44">44</option>
							<option value="45">45</option>
							<option value="46">46</option>
							<option value="47">47</option>
							<option value="48">48</option>
							<option value="49">49</option>
							<option value="50">50</option>
							<option value="51">51</option>
							<option value="52">52</option>
							<option value="53">53</option>
							<option value="54">54</option>
							<option value="55">55</option>
							<option value="56">56</option>
							<option value="57">57</option>
							<option value="58">58</option>
							<option value="59">59</option>
						</select>
					</td>
                    <td class="inact" style="text-align:right;">
						&nbsp;Monto $:&nbsp;
						<input type="text" maxlength="5" size="10" tabindex="4" name="m_dep" style="direction: rtl; font-family: courier; font-size: 11px; border-width: 1px; height: 16px;" OnChange="return EsNumero(this,document.f_c,true)" OnBlur="return EsNumero(this,document.f_c,true)">
						<font style="font-family: courier; font-size: 11px; background-color: white;"></font>
                   </td>
                </tr>
U002
;
    }// termina de imprimir los campos de deposito


    print <<<U0021
                <tr>
                    <td class="inact" style="text-align:center; background:#FFFFCC;" colspan="4">
						Si tu numero de deposito es menor a ocho(8) digitos agrega ceros(0) a la izquierda.
                    </td>
				</tr>
U0021;


if (count($exoneracion) > 0){
 print <<<EXO
				<tr bgcolor="#EFEFEF" >
					<td class="inact" style="text-align:left;font-weight:bold; padding-left:50px;" colspan="3">
						<br>
						Ya usted posee exoneraci&oacute;n registrada.&nbsp;Nro. de Planilla:&nbsp;
						<input type="text" maxlength="12" size="15" tabindex="1" name="p_exo" style="font-family: courier; font-size: 11px; border-width: 1px; height: 16px;" onKeyUp="return EsNumero(this,document.f_c,true)" value="{$exoneracion[0][0]}" disabled="disabled" readonly>					
					</td>
					<td class="inact" style="text-align:right;">
						&nbsp;Monto Bs:&nbsp;
						<input type="text" maxlength="5" size="10" tabindex="4" name="m_exo" style="direction: rtl; font-family: courier; font-size: 11px; border-width: 1px; height: 16px;" OnChange="return EsNumero(this,document.f_c,true)" OnBlur="return EsNumero(this,document.f_c,true)" readonly value="$valorExonerar">
						<font style="font-family: courier; font-size: 11px; background-color: white;">
                   </td>
				</tr>
				<tr>
                    <td class="inact" style="text-align:left; background:#FFFFCC;color:#FF0000; font-weight:bold; padding-left:50px;" colspan="4">
						<ul>
							<li>La valid&eacute;z de la exoneraci&oacute;n aqui cargada esta sujeta a la consignaci&oacute;n de la Planilla de Exoneraci&oacute;n ante la Coordinaci&oacute;n de Intensivo y posterior revisi&oacute;n y verificaci&oacute;n de la misma.</li>
							<li>Los datos de la Planilla de Exoneraci&oacute;n seran comparados con la informaci&oacute;n suministrada por la OPSU, en caso de que estos no coincidan, la exoneraci&oacute;n sera eliminada y el estudiante asume las consecuencias que esto derive.</li>
						</ul>
                    </td>
				</tr>
EXO;

}else{
print <<<EXO
				<tr bgcolor="#EFEFEF" >
					<td class="inact" style="text-align:left;font-weight:bold; padding-left:50px;" colspan="3">
						<br>
						Si posee exoneraci&oacute;n introduzca el n&uacute;mero de la planilla:
						<input type="text" maxlength="12" size="15" tabindex="1" name="p_exo" style="font-family: courier; font-size: 11px; border-width: 1px; height: 16px;" onKeyUp="return EsNumero(this,document.f_c,true)" readonly>	
					</td>
					<td class="inact" style="text-align:right;">
						&nbsp;Monto Bs:&nbsp;
						<input type="text" maxlength="5" size="10" tabindex="4" name="m_exo" style="direction: rtl; font-family: courier; font-size: 11px; border-width: 1px; height: 16px;" OnChange="return EsNumero(this,document.f_c,true)" OnBlur="return EsNumero(this,document.f_c,true)" readonly value="$valorExonerar">
						<font style="font-family: courier; font-size: 11px; background-color: white;">
                   </td>
				</tr>
				<tr>
                    <td class="inact" style="text-align:left; background:#FFFFCC;color:#FF0000; font-weight:bold; padding-left:50px;" colspan="4">
						<ul>
							<li>La valid&eacute;z de la exoneraci&oacute;n aqui cargada esta sujeta a la consignaci&oacute;n de la Planilla de Exoneraci&oacute;n ante la Coordinaci&oacute;n de Intensivo y posterior revisi&oacute;n y verificaci&oacute;n de la misma.</li>
							<li>Los datos de la Planilla de Exoneraci&oacute;n seran comparados con la informaci&oacute;n suministrada por la OPSU, en caso de que estos no coincidan, la exoneraci&oacute;n sera eliminada y el estudiante asume las consecuencias que esto derive.</li>
						</ul>
                    </td>
				</tr>
EXO;





}
print <<<U0022

				<tr>
					<td class="inact" style="text-align:center;" colspan="3">
						&nbsp;
                    </td>
                    <td class="inact"
		                style="text-align:right; background:#FFFF66; ">&nbsp;&nbsp;&nbsp;Total $:&nbsp;</font>
                        <input type="text" name="t_dep" maxlength="7" size="10" tabindex="1" 
                            readonly="readonly"
                            style="border-style: solid; border-width: 0px;
                                   direction: rtl; font-family: courier; font-size: 10pt;
                                   background-color: #FFFF66">
                            <font style="font-family: courier; font-size: 10pt;
                                        background-color: #FFFF66;"></font> 
                   </td>
                </tr>
             </tbody>
          </table>
U0022;

print <<<U003
         <table align="center" border="0" cellpadding="0" 
            cellspacing="0" width="400">
          <tbody>
              <tr>
                    <td valign="top"><p align="left">
                        <input type="button" value="Borrar" name="B1" class="boton" 
                         onclick="javascript:reiniciarTodo();"></p> 
                    </td>
                    <td valign="top"><p align="right">
                        <input type="button" value="Salir" name="B1" class="boton" 
                         onclick="javascript:self.close();"></p> 
                    </td>
                    <td><p align="right">
                        <input type="button" value="Procesar Inscripci&oacute;n" name="B1"
							class="boton" 
                        onclick="Inscribirme();"></p>    
                        <input type="hidden" name="depositos" value="">
                        <input type="hidden" name="asignaturas" value="$lasAsignaturas">
                        <input type="hidden" name="asigSC" value="$asigSC">
                        <input type="hidden" name="seccSC" value="$seccSC">
                        <input type="hidden" name="exp_e" value="z">
                        <input type="hidden" name="cedula" value="x">
                        <input type="hidden" name="contra" value="{$_POST['contra']}">
                        <input type="hidden" name="carrera" value="z">
                        <input type="hidden" name="lapso" value="$dp[11]">
                        <input type="hidden" name="inscribe" value="$inscribe">
                        <input type="hidden" name="ind_acad" value="$dp[10]">          
                        <input type="hidden" name="inscrito" value="$inscrito">
                        <input type="hidden" name="sede" value="$sede">
                        <input type="hidden" name="sexo" value="$dp[14]">
                        <input type="hidden" name="f_nac_e" value="$dp[15]">
                        <input type="hidden" name="c_inicial" value="0">
                        <input type="hidden" name="maxDepo" value="$maxDepo">
                    </td>
                </tr>
            </form>
            </tbody>
          </table>
        </div>
       </td>
    </tr>
 </table>

<!-- codigo para definir la ventana de popup -->
<script>
if (NS4) {document.write('<LAYER NAME="floatlayer" style="visibility\:hide" LEFT="'+floatX+'" TOP="'+floatY+'">');}
if ((IE4) || (NS6)) {document.write('<div id="floatlayer" style="position:absolute; left:'+floatX+'; top:'+floatY+'; z-index:10; filter: alpha(opacity=0); opacity: 0.0; visibility:hidden">');
}
</script>
<table border="0" width="500" bgcolor="#2816B8" cellspacing="0" cellpadding="5">
<tr>
<td width="100%">
  <table border="0" width="100%" cellspacing="0" cellpadding="0" height="36">
  <tr>
  <td id="titleBar" style="cursor:move; text-align:center" width="100%">
  <ilayer width="100%" onSelectStart="return false">
  <layer width="100%" onMouseover="isHot=true;if (isN4) ddN4(theLayer)" onMouseout="isHot=false">
  <font face="Arial" size=2 color="#FFFFFF">
    VERIFICA Y CONFIRMA TU SELECCI&Oacute;N</font>
  </layer>
  </ilayer>
  </td>
  <td style="cursor:pointer" valign="top">
  <a href="#" onClick="hideMe();return false"><font color=#ffffff size=2 face=arial  style="text-decoration:none; vertical-align:top;">X</font></a>
  </td>
  </tr>
  <tr>
  <td width="100%" bgcolor="#FFFFFF" style="padding:4px" colspan="2">
<!-- PLACE YOUR CONTENT HERE //-->  
<table>
<tr><td colspan=2> <span style="font-family:Arial; font-size:13px; font-weight:bold;
                                text-align:left">
$dp[2]:<br>Por favor escribe de nuevo tu clave
 y pulsa "Aceptar" para procesar tu selecci&oacute;n. RECUERDA: Despu&eacute;s
 de procesada la inscripci&oacute;n ya NO podr&aacute;s hacer cambios.</span>
    <td>
</tr>
<tr><td colspan=2>

<tr>
  <td colspan=2 valign="middle"><p align="left">
     <font face=arial size=2><b> Clave:&nbsp;</b>
       <input type="password" name="pV" id="pV"
         style="background-color:#99CCFF" size="20">
  </td>
</tr>

<tr>
  <td valign = "middle"><p align="center">
     <input type="button" value="Aceptar" name="aBA" class="boton" onclick="verificar()"> 
     </td>
   <td valign = "middle"><p align="center">
     <input type="button" value="Cancelar" name="aBC" class="boton" onclick="cancelar()"> 
   </td>  
 </tr>
 </table>
     
<!-- END OF CONTENT AREA //-->
  </td>
  </tr>
  </table> 
</td>
</tr>
</table>
</div>

<script>
if (NS4)
{
document.write('</LAYER>');
}
if ((IE4) || (NS6))
{
document.write('</DIV>');
}
ifloatX=floatX;
ifloatY=floatY;
lastX=-1;
lastY=-1;
define();
window.onresize=define;
window.onscroll=define;
adjust();
U003
;
   if (($inscrito == '1') && (($inscribe != '2') && ($inscribe != '3'))) {
       print "\nprepdata(document.pensum,document.f_c);";
       print "\nalert(document.f_c.inscribe.value+'  '+document.f_c.inscrito.value);";
	   print "\ndocument.f_c.submit();\n";
   }
    print <<<U004
</script>
</body>
</html>
U004
;
		}
		else { // Si no esta preinscrito
			print <<<U005
         <table align="center" border="0" cellpadding="0" 
            cellspacing="0" width="400">
          <tbody>
              <tr>
                    <td valign="top"><p align="center">
                        <input type="button" value="Aceptar" name="B1" class="boton" 
                         onclick="javascript:self.close();"></p> 
                    </td>
                </tr>
            </tbody>
          </table>
        </div>
       </td>
    </tr>
 </table>
</body>
</html>
U005
;
		}
    }
    
    function volver_a_indice($vacio,$fueraDeRango, $habilitado=true){
	
    //regresa a la pagina principal:
	global $raizDelSitio, $cedYclave;
    if ($vacio) {
?>
            <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
            <META HTTP-EQUIV="Refresh" 
            CONTENT="0;URL=<?php echo $raizDelSitio; ?>">
        </head>
        <body>
        </body>
        </html>
<?php
    }
    else {
?>          <script languaje="Javascript">
            <!--
            function entrar_error() {
<?php
        if ($fueraDeRango) {
			if($habilitado){
?>             
		mensaje = "Lo siento, no puedes inscribirte en este horario.\n";
        mensaje = mensaje + "Por favor, espera tu turno.";
<?php
			}
			else {
?>
	    mensaje = 'Lo siento, no esta habilitado el sistema.';
<?php
			}
		}
        else {
			if(!$cedYclave[0]){
?>
        mensaje = "La cedula no esta registrada o es incorrecta.\n";
		mensaje = mensaje + "Es posible que usted no se haya PRE-INSCRITO\n";
		mensaje = mensaje + "Por favor espera la jornada para NO PRE-INSCRITOSr.\n\n";
		mensaje = mensaje + "Tambien es posible que usted deba solicitar REINGRESO\n";
		mensaje = mensaje + "si no curso materias en el semestre anterior.";
<?php
			}	
			else if (!$cedYclave[1]) {
?>
        mensaje = "Clave incorrecta. Por favor intente de nuevo";
<?php
			}
			else if (!$cedYclave[2]) {
?>
        mensaje = "Codigo de seguridad incorrecto. Por favor intente de nuevo";
<?php
			}
		}
?>
                alert(mensaje);
                window.close();
                return true; 
        }

            //-->
            </script>
        </head>
                    <body onload ="return entrar_error();" >

        </body>
<?php 
	global $noCacheFin;
	print $noCacheFin; 
?>
</html>
<?php
    }
}    

function alumno_en_rango($horaTurno, $fechaTurno) {

	$fechaActual = time() - 3600*date('I');
	$tHora = intval(substr($horaTurno ,0,2),10);
	$tMin = intval(substr($horaTurno,2,2),10);
	$tFecha = explode('-',$fechaTurno); //anio-mes-dia
	@$suFecha = mktime($tHora, $tMin, 0, $tFecha[1], $tFecha[2], $tFecha[0],date('I'));
	//echo $fechaTurno;
//	echo $suFecha."<=".($fechaActual - 1800);
	return ($suFecha <= ($fechaActual /*- 1800*/));
}

    // Programa principal
          
    if(isset($_POST['cedula']) && isset($_POST['contra'])) {
        $cedula=$_POST['cedula'];
        $contra=$_POST['contra'];
        // limpiemos la cedula y coloquemos los ceros faltantes
        $cedula = ltrim(preg_replace("/[^0-9]/","",$cedula),'0');
        $cedula = substr("00000000".$cedula, -8);
        $fvacio = false; 
		
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">
  <link rel="stylesheet" type="text/css" media="all" href="calendario/calendar-blue2.css" title="win2k-cold-1" />

			<script type="text/javascript" src="calendario/calendar.js"></script>
			<script type="text/javascript" src="calendario/lang/calendar-esPHP.js"></script>
			<script type="text/javascript" src="calendario/calendar-setup.js"></script>
<?php
print $noCache; 
print $noJavaScript; 
?>
<title><?php echo $tProceso .' '. $lapso; ?></title>
<?php
        $cedYclave = cedula_valida($cedula,$contra);
		if(!$fvacio && $cedYclave[0] && $cedYclave[1] && $cedYclave[2]) {
            // Revisamos si es su turno de inscripcion:
            
				if(alumno_en_rango($datos_p[8],$datos_p[9])) {
			//	if(true) {
            // pintamos su pensum y su formulario para llenar:
            // ya tenemos en $datos_p los datos personales
                $exped    = $datos_p[1];
                $mencion  = $datos_p[5];
                $pensum   = $datos_p[6];
                $c_carr   = $datos_p[7];
                $lapso    = $datos_p[11];
                // $inscribe = $datos_p[12];
				// Se coloca $inscribe en 3 FIJO (preinscripcion intensivo) 
				// para diferenciarlo de inscripciones e inclusiones.

				

				$bloq = new ODBC_Conn($sede,"c","c");
				$bSQL = "SELECT observacion FROM dace003 ";
				$bSQL.= "WHERE exp_e='".$exped."' AND c_unid_f NOT IN (".$nobloqueados.")";
				$bloq->ExecSQL($bSQL);

				if (@$bloq->result[0][0] != null){
					die("<script languaje=\"javascript\">alert('".ucfirst(strtolower($bloq->result[0][0]))."');window.close(); </script>");
				}
				
				$inscribe = '1';
				$inscrito = '0'; //intval('0'.$datos_p[13]);
                $Cmat = new ODBC_Conn($sede,"c","c");
				// echo $sede.'[cmat]';

				if ( $sedeActiva == 'POZ' ) {
					/*$mSQL = "SELECT tblaca009.semestre, tblaca008.c_asigna, asignatura, ";
					$mSQL = $mSQL."tblaca009.u_creditos, co_req, repite, cre_cur, tipo_lapso  ";
					$mSQL = $mSQL."FROM materias_inscribir, tblaca009 , tblaca008 WHERE ";
					$mSQL = $mSQL." materias_inscribir.c_asigna=tblaca009.c_asigna AND "; 
					$mSQL = $mSQL."mencion='".$mencion."' AND pensum='".$pensumPoz."' ";
					$mSQL = $mSQL." AND exp_e='".$exped."' AND c_uni_ca='".$c_carr."' ";
					$mSQL = $mSQL." AND tblaca008.c_asigna=tblaca009.c_asigna ORDER BY semestre";*/


					#para no mostrar ciudadania
					$CDD = "SELECT c_asigna FROM dace004 WHERE ";
					$CDD.= "(status='0' OR status='3' OR status='B') AND ";
					$CDD.= "exp_e='".$exped."' AND c_asigna='300677'";
					$Cmat->ExecSQL($CDD);
					if ($Cmat->filas == '1'){
						$ciud=" AND tblaca008.c_asigna<>'300676' ";
					}else $ciud=' ';

					#para no mostrar venezuela
					$VEN = "SELECT c_asigna FROM dace004 WHERE "; 
					$VEN.= "(status='0' OR status='3' OR status='B') AND ";
					$VEN.= "exp_e='".$exped."' AND c_asigna='300676'";
					$Cmat->ExecSQL($VEN);
					if ($Cmat->filas == '1'){
						$venez=" AND tblaca008.c_asigna<>'300677' ";
					}else $venez=' ';

					if($c_carr == 6){ // Si es Industrial no mostrar
						$bloq = " AND tblaca008.c_asigna<>'300890' "; // Formacion de Emprendedores
						$bloq.= " AND tblaca008.c_asigna<>'300870' "; // Legislacion Laboral
					}else{$bloq = '';}

					$mSQL = "SELECT tblaca009.semestre, tblaca008.c_asigna, asignatura, ";
					$mSQL.= "tblaca009.u_creditos, co_req, repite, cre_cur, tipo_lapso  ";
					$mSQL.= "FROM materias_inscribir, tblaca009 , tblaca008 WHERE ";
					$mSQL.= " materias_inscribir.c_asigna=tblaca009.c_asigna AND "; 
					$mSQL.= " mencion='".$mencion."' AND pensum='".$pensumPoz."' ";
					$mSQL.= " AND exp_e='".$exped."' AND c_uni_ca='".$c_carr."' ";
					$mSQL.= " AND tblaca008.c_asigna=tblaca009.c_asigna ";
					$mSQL.= " AND tblaca008.c_asigna NOT IN(SELECT c_asigna FROM ";
					$mSQL.= " dace004 where (status='0' OR status='3' OR status='B') ";
					$mSQL.= " AND exp_e='".$exped."' )";
					$mSQL.= " ".$ciud." ";
					$mSQL.= " ".$venez." ";
					$mSQL.= " ".$bloq." ";
					$mSQL.= " AND materias_inscribir.c_asigna<>'300622' ";// no mostrar serv comunitario
					$mSQL.= " AND materias_inscribir.c_asigna NOT IN (SELECT c_asigna FROM dace006 ";
					$mSQL.= " WHERE status IN(7,'A') AND exp_e='".$exped."' and lapso <> '".$lapsoProceso."' ) ";
					$mSQL.= " AND materias_inscribir.c_asigna NOT IN (".$noOfertar.") ";// no mostrar PP/PPG/TG
					$mSQL.= " ORDER BY semestre";

				}
				else {
					$mSQL = "SELECT semestre, tblaca008.c_asigna, asignatura, ";
					$mSQL = $mSQL."tblaca008.unid_credito, co_req, repite, cre_cur, tipo_lapso FROM";
					$mSQL = $mSQL." materias_inscribir, tblaca009 , tblaca008 WHERE ";
					$mSQL = $mSQL."materias_inscribir.c_asigna=tblaca009.c_asigna AND "; 
					$mSQL = $mSQL."mencion='".$mencion."' AND pensum='".$pensum."' ";
					$mSQL = $mSQL."AND exp_e='".$exped."' AND c_uni_ca='".$c_carr."' ";
					$mSQL = $mSQL."AND tblaca008.c_asigna=tblaca009.c_asigna ORDER BY semestre";
				}
                
				//echo $mSQL;
				
                $Cmat->ExecSQL($mSQL);
				$lista_m=$Cmat->result;
				$mSQL = "SELECT n_planilla, fecha, hora, monto FROM depositos WHERE lapso='".$lapsoProceso."' and exp_e='".$exped."' AND (opera <> 'EXO' OR opera is null) ";

				$Cmat->ExecSQL($mSQL);
				$depositos = $Cmat->result;

				$mSQL = "SELECT n_planilla, fecha, hora, monto FROM depositos WHERE lapso='".$lapsoProceso."' and exp_e='".$exped."' AND opera = 'EXO' ";

				//echo $mSQL;

				$Cmat->ExecSQL($mSQL);
				$exoneracion = $Cmat->result;
				//echo print_r($exoneracion);

				unset($Cmat);
                $carr_esp= array('.'=>"",
                                 'A'=>" (COMUNICACIONES)", 
                                 'B'=>" (COMPUTACI&Oacute;N)",
                                 'C'=>" (CONTROL)");
                $datos_p[4] = $datos_p[4].$carr_esp[$datos_p[5]];
				if ($inscribe != '0') {
					imprime_primera_parte($datos_p);
					
					//print_r($lista_m);
					
                    imprime_pensum($lista_m);
					imprime_ultima_parte($datos_p);
				}
				else volver_a_indice(false,true,false);//inscripciones no habilitadas
            }
            else volver_a_indice(false,true); //alumno fuera de rango
        }
        else volver_a_indice(false,false); //cedula o clave incorrecta
    }
    else volver_a_indice(true,false); //formulario vacio
?>
