<?php
    include_once('inc/vImage.php');
    include_once('inc/odbcss_c.php');
	include_once ('inc/config.php');
	include_once ('inc/activaerror.php');

    $datos_p = array();
    $asignat = array();
	$depo    = array();
    $errstr  = "";
	$sede    = "";

	//$Cmat    = new ODBC_Conn($sede,"usuario2","usuario2",$ODBCC_conBitacora,'insc.log');
    $fecha  = date('Y-m-d', time() - 3600*date('I'));
    $hora   = date('h:i:s', time() - 3600*date('I'));
    $ampm   = date('A', time() - 3600*date('I'));
    $todoOK = true;
    $secc   =  "";
    $statusI = array();
    $inscrito = 0;

    function print_error($f,$sqlerr){
    
    print "<pre>".$f."\n".$sqlerr."</pre>";
    }
    
	function leer_datos_p($exp_e) {
        global $datos_p;
        global $errstr;
        global $E;
		global $sede;
		global $ODBCC_sinBitacora;
		global $masterID;
    
		if ($exp_e != ""){
            $Cusers = new ODBC_Conn("USERSDB","scael","c0n_4c4");
			$uSQL	= "SELECT userid FROM usuarios WHERE userid='".$exp_e."' ";
			$uSQL  .= "AND password='".$_POST['contra']."'";
			$Cusers->ExecSQL($uSQL);
			$claveOK = $Cusers->filas == 1; 
			if (!$claveOK) { //use la clave maestra
				$uSQL  = "SELECT password FROM usuarios WHERE userid='$masterID' ";
				$uSQL .= "AND password='".$_POST['contra']."'";
				$Cusers->ExecSQL($uSQL);
				$claveOK = $Cusers->filas == 1;
			}
			if ($claveOK) {		
				$Cdatos_p = new ODBC_Conn($sede,"c","c",$ODBCC_sinBitacora);
				$dSQL = " SELECT ci_e, exp_e, nombres, apellidos,c_uni_ca, nombres2, apellidos2 ";
				$dSQL = $dSQL."FROM DACE002 WHERE exp_e='".$exp_e."'";
				$Cdatos_p->ExecSQL($dSQL);
				$datos_p = $Cdatos_p->result[0];
				return ($Cdatos_p->filas == 1);
			}
            else return (false);
        }
        else return(false);      
    }
    
    function reportarError($errstr,$impmsg = true) {
	//global $errstr;
    if($impmsg) {
       print <<<E001
   
    <tr><td><pre> 
            Disculpe, Existen problemas con la conexi&oacute;n al servidor, 
            por favor contacte al personal de DACE e intente m&aacute;s tarde
    </pre></td></tr>
E001
;
    }
    $error_log=date('h:i:s A [d/m/Y]').":\n".$errstr."\n";
//    file_put_contents('errores.log', $error_log, FILE_APPEND);
}
    function consultarDatos($sinCupo) {
        
        global $ODBCSS_IP;
        global $datos_p; 
        global $asignat;
        global $errstr;
        global $lapso;
        global $inscribe;
        global $sede;
		global $Cmat;
		global $inscrito;
		global $depo, $lapsoProceso;
        
		$actBitacora = (intval('0'.$inscrito) != 1 || intval('0'.$inscribe)==2 ); 
		//actualiza bitacora si no es solo reporte;
        $todoOK = true;       
        //$Cdep = new ODBC_Conn($sede,"usuario2","usuario2", $ODBCC_conBitacora, $laBitacora);
        $dSQL  = "SELECT A.c_asigna, asignatura, unid_credito, seccion||'-'||incluye, status FROM tblaca008 A, dace006 B ";
        $dSQL .= "WHERE exp_e='".$datos_p[1]."' AND lapso='$lapso' AND A.c_asigna = B.c_asigna ";
		$dSQL .= "AND NOT status IN('C')"; 
        $Cmat->ExecSQL($dSQL,__LINE__); 
        if ($todoOK) {
            $asignat = $Cmat->result;
			$dSQL = "SELECT n_planilla, monto FROM depositos WHERE lapso='".$lapsoProceso."' and exp_e='".$datos_p[1]."' AND (opera <> 'EXO' OR opera is null) ";
			//echo $dSQL;
			$Cmat->ExecSQL($dSQL);
            $depo =$Cmat->result;
			//print '<pre>';
			//print $dSQL;
			//print_r($asignat);
			//print '</pre>';
            if (!$sinCupo && $actBitacora) {
                $dSQL = "UPDATE orden_inscripcion set inscrito='1'";
                $dSQL = $dSQL." WHERE ord_exp='$datos_p[1]'";
                $Cmat->ExecSQL($dSQL, __LINE__); 
				//actualizamos sexo y fecha de nacimiento:
                $dSQL = "UPDATE dace002 set ";
				$dSQL = $dSQL."f_nac_e='".$_POST['f_nac_e']."'"; 
                $dSQL = $dSQL." WHERE exp_e='$datos_p[1]'";
                $Cmat->ExecSQL($dSQL, __LINE__,$actBitacora); 
            }
         }
        return($todoOK);        
    }

    function reportarInscripcion() {
        
        global $asignat, $datos_p, $depo, $lapsoProceso, $Cmat;
        $tot_dep = 0;
		$firma = "";        
		$total = count($depo);
        for ($i=0; $i<$total;$i++){
            $tot_dep += $depo[$i][1];
		}
        $tot_uc = 0;
        $total = count($asignat);
        for ($i=0; $i<$total;$i++){
            $tot_uc += intval($asignat[$i][2]);
		}

        print <<<R001
    <tr><td>&nbsp;</td>
    </tr>
        <tr><td width="750">
        <TABLE align="center" border="1" cellpadding="3" cellspacing="1" width="550"
				style="border-collapse: collapse;">
        <TR><TD>
        <table align="center" border="0" cellpadding="0" cellspacing="1" width="550">
            <tr>
                <td style="width: 60px;" nowrap="nowrap" bgcolor="#FFFFFF">
                    <div class="matB">C&Oacute;DIGO</div></td>
                <td style="width: 300px;" bgcolor="#FFFFFF">
                    <div class="matB">ASIGNATURA</div></td>
                <td style="width: 60px;" nowrap="nowrap" bgcolor="#FFFFFF">
                    <div class="matB">U.C.</div></td>
                <td style="width: 60px;" nowrap="nowrap" bgcolor="#FFFFFF">
                    <div class="matB">SECCI&Oacute;N</div></td>
                <td style="text-align:center; width: 70px;" nowrap="nowrap" bgcolor="#FFFFFF">
                    <div class="matB">ESTATUS</div></td>
            </tr>

R001
;
        $total=count($asignat);
        for ($i=0;$i<$total;$i++) {
           $sEstatus = array(2=>'RETIRADA', 7=>'INSCRITA', 9=>'INCLUIDA','C'=>'CENSADA', 'P' =>'PREINSCR','A'=>'AGREGADA','Y'=>'EN COLA','R'=>'RET. REGL.','T'=>'RET. TEMP.');
			if ($asignat[$i][4] !='C' || $asignat[$i][4] !='C'){
				$firma .= $asignat[$i][0].$asignat[$i][3].$asignat[$i][4]." ";
				if ($asignat[$i][3] == '') {
					$asignat[$i][3] = '-';
				}

				if(strlen($asignat[$i][3]) < 4){
					$asignat[$i][3] = substr($asignat[$i][3],0,2);				
				}

				print <<<R002
            <tr>
                <td nowrap="nowrap" bgcolor="#FFFFFF">
                    <div class="mat">{$asignat[$i][0]}</div></td>
                <td bgcolor="#FFFFFF">
                    <div class="mat">{$asignat[$i][1]}</div></td>
                <td nowrap="nowrap" bgcolor="#FFFFFF">
                    <div class="mat">{$asignat[$i][2]}</div></td>
                <td nowrap="nowrap" bgcolor="#FFFFFF">
                    <div class="mat">{$asignat[$i][3]}</div></td>
                <td nowrap="nowrap" bgcolor="#FFFFFF">
                    <div class="mat">{$sEstatus[$asignat[$i][4]]}</div></td>
            </tr>

R002
;
			}
        }
        print <<<R0031
        </table>
        </TR></TD></TABLE>
R0031
;
		// imprime los depositos:

        $total=count($depo);
        if ($total > 1){
            $ptitulo = " las planillas ";
}
        else $ptitulo = " la planilla ";
        print <<<R006
        <tr><td>&nbsp;</td>
        </tr>
        <tr> <td class="tit14">Datos de
R006
;
        print $ptitulo."de dep&oacute;sito";
        print <<<R007
            </td>
        </tr>
        <tr><td width="750">              
        <TABLE align="center" border="1" cellpadding="3" cellspacing="1" width="550"
				style="border-collapse: collapse;">
        <TR><TD>
            <table align="center" border="0" cellpadding="0" cellspacing="1" width="360">
             <tr class="matB" style="width: 150px;" nowrap="nowrap">
                <td width="50%">
                    Planilla No.</td>
                <td width="50%" style="text-align: right;">Monto Bs.</td>
                         
            </tr>
R007
;
        for ($i=0;$i<$total;$i++) {
            print <<<R008
            <tr>
                <td class="depo">
R008
;        
            print $depo[$i][0];// No. planilla
            print <<<R009
                </td>
                <td class="depo" style="text-align: right;">
R009;
            //print $depo[$i][1]."</td>";// monto 
			echo number_format($depo[$i][1],2,',','.')."</td>";
            print <<<R010
                    
                    
            </tr>
R010
;       }
        print <<<R011
        <tr>
			<td class="matB" style="text-align: right;">
                Total dep&oacute;sito:</td>
            <td class="depo" style="text-align: right;">
R011
;
        echo number_format($tot_dep,2,',','.')."</td>";
        print <<<R012
			
        </tr>
R012
;

		print <<<R0121

        </table>
        </TD></TR></TABLE></td></tr>
R0121
;

$msg_exo = "";

$dSQL = " SELECT n_planilla, monto FROM depositos WHERE lapso='".$lapsoProceso."' AND exp_e='".$datos_p[1]."' AND opera = 'EXO' ";
		$Cmat->ExecSQL($dSQL);
		if ($Cmat->filas > 0) {
			//echo "<tr><td width=\"750\">123</td></tr>";
print <<<R007
		</td>
        </tr>
		<tr> <td class="tit14">Datos de la Exoneracion</td></tr>
        <tr><td width="750">            
        <TABLE align="center" border="1" cellpadding="3" cellspacing="1" width="550"
				style="border-collapse: collapse;">
        <TR><TD>
            <table align="center" border="0" cellpadding="0" cellspacing="1" width="360">
             <tr class="matB" style="width: 150px;" nowrap="nowrap">
                <td width="50%">
                    Planilla No.</td>
                <td width="50%" style="text-align: right;">Monto Bs.</td>                         
            </tr>
R007
;

print <<<EXO1
			<tr class="depo" style="width: 150px;" nowrap="nowrap">
                <td>
EXO1
;
			echo $_POST['p_exo']."</td><td style=\"text-align: right;\">";
			echo number_format($_POST['m_exo'],2,',','.')."</td></tr>";

			
print <<<R0121

			</table>
        </TD></TR>
		</TABLE>
		</td></tr>
R0121
;
			$msg_exo = "<br><br>
					Los datos de la Planilla de Exoneraci&oacute;n seran comparados con la informaci&oacute;n suministrada por el MPPEU y la OPSU, en caso de no coincidir la exoneraci&oacute;n sera eliminada y el estudiante asume las consecuencias que esto derive.";

		}


        /*
	$dSQL = " SELECT n_planilla, monto FROM depositos WHERE lapso='".$lapsoProceso."' AND exp_e='".$datos_p[1]."' AND opera = 'EXO' ";
		$Cmat->ExecSQL($dSQL);
		if ($Cmat->filas > 0) {
			print <<<EXO2
			
			<tr>
				<td class="matB" style="text-align: right;" >
					Total General:
				</td>
            <td class="depo" style="text-align: right;">
EXO2
;

			echo number_format($tot_dep+$_POST['m_exo'],2)."</td><td></td></tr>";
		}

		*/


		$key1 = substr(md5("$datos_p[0]"),0,16);
		$key2 = substr(md5("$datos_p[1]"),0,16);

        print <<<R003
		<tr><td>
        <table align="center" border="0" cellpadding="0" cellspacing="1" width="550">
          <tr style="font-size: 2px;">
             <td colspan="2" > &nbsp; </td>
          </tr>
          <tr><form name="imprime" action="">
               <td valign="bottom"><p align="left">
                    <input type="button" value=" Imprimir " name="bimp"
                         style="background:#FFFF33; color:black; font-family:arial; font-weight:bold;" onclick="imprimir(document.imprime)"></p> 
               </td>
               <td valign="bottom"><p align="left">
                       <input type="button" value="Finalizar" name="bexit"
                        onclick="verificarSiImprimio()"></p> 
                </td></form>
          </tr>
          <tr style="font-size: 2px;">
             <td>&nbsp;</td>
             <td>&nbsp;<br>
                </td>
		<tr>
                <td colspan="2" class="nota">
                    IMPORTANTE:<br>
                    Los datos de los dep&oacute;sitos ser&aacute;n verificados con el banco.
                    En caso de existir alguna discrepancia, la inscripci&oacute;n
                    ser&aacute; anulada.$msg_exo
                </td>
		</tr>
		<tr>
                <td colspan="2" class="nota"><br>
                La carga acad&eacute;mica inscrita por  el estudiante en esta
                planilla est&aacute; sujeta a control posterior por parte de Control de Estudios
                en relaci&oacute;n al cumplimiento de los prerrequisitos y 
                correquisitos sustentados en los pensa vigentes y a las cargas
                acad&eacute;micas m&aacute;ximas establecidas en el
                Reglamento de Evaluaci&oacute;n y Rendimiento Estudiantil vigente.
                La violaci&oacute;n de los requisitos y normativas antes mencionados
                conllevar&aacute; a la eliminaci&oacute;n de las asignaturas que no
                los cumplan.
                </td>
          </tr>
		  <tr><td colspan="2" class="matB"><br>C&Oacute;DIGO DE VALIDACI&Oacute;N:<br></td></tr>
		  <tr><td colspan="2" class="dp1"><br>$key1$key2<br></td></tr>
		  <tr><td colspan="2" class="matB">
			<IMG SRC="inc/barcode.php?barcode={$key1}&width=350&height=25&text=0" align="center">
		    </td>
		  </tr>
		  <tr><td colspan="2" class="nota">&nbsp;</td></tr>
          <tr><td colspan="2" class="matB">
			<IMG SRC="inc/barcode.php?barcode={$key2}&width=350&height=25&text=0" align="center">
		    </td>
		  </tr>
			<tr class="mat">
                <td  ><br><br>
					____________________________<br>
							Firma del alumno
				</td>
				<td><br><br>
					____________________________<br>
					Firma y Sello Control de Estudios
				</td>
			</tr>
          </table>
        </tr>
        </table>
    </td>
    </tr>

R003
;
        
    }
       
    function asignaturasCorrectas() {
    // Revisa si las asignaturas que pretende inscribir son legales
	// es decir, si estan en su lista de materias_inscribir
		global $lapso, $datos_p;
		$correctas = true;       
        $asig	= array();
        $asig	= explode(" ",$_POST['asignaturas']);
        array_pop($asig);
        $total_a = count($asig);
		$total_mat = 0;
		if ($total_a > 0) {
			$listaAsig = '';
			$i = 0;
			while ($i<$total_a) {
				$listaAsig .= $asig[$i] . "','";
				$i=$i+4;
				$total_mat++;
			}
			$listaAsig = "('".$listaAsig."')";
            $Cdep  = new ODBC_Conn($_POST['sede'],"c","c",true);
            $dSQL  = "SELECT  c_asigna FROM materias_inscribir WHERE c_asigna in ".$listaAsig;
			$dSQL .= " AND exp_e='$datos_p[1]'";
            $Cdep->ExecSQL($dSQL,__LINE__,true);
            $correctas = ($Cdep->filas == $total_mat); 
		}            
		return ($correctas);
	}


    function registrarDepositos() {
        
        global $datos_p;
		global $fecha;
		global $Cmat;
		global $lapsoProceso;
               
        $pded = array();
        $mdep = array();       
        $dep = array();
        $dep = explode(" ",$_POST['depositos']);
        array_pop($dep);
		//print_r($dep);
        $total_d = count($dep);
        if ($total_d > 0) {
			$Cmat->iniciarTransaccion("usuario: ".$datos_p[1]." - Inicia transaccion.");
            $i=0;
            while($i<$total_d){
                $pdep[]=$dep[$i];
				$fdep[]=$dep[++$i];
				$hdep[]=$dep[++$i];
                $mdep[]=$dep[++$i];
                ++$i;  
            } 
            $total_d = count($mdep);
            for($i=0;$i<$total_d;$i++) {
				$sSQL = "SELECT * FROM depositos WHERE n_planilla='".$pdep[$i]."' ";
				$Cmat->ExecSQL($sSQL,__LINE__,true);
				if($Cmat->filas == 0){// Si la planilla no esta registrada previamente.
					$dSQL = " INSERT INTO depositos (n_planilla, monto, exp_e, fecha, hora, lapso) ";
					$dSQL = $dSQL."VALUES ('".$pdep[$i]."','".$mdep[$i]."','".$datos_p[1]."','".$fdep[$i]."', '".$hdep[$i]."', '".$lapsoProceso."' )";
					$Cmat->ExecSQL($dSQL, __LINE__, true);
					/*print '<pre>';
					print $dSQL;
					print '</pre>';*/				
				}
			}// Fin for
        }// Fin total depositos

		if (strlen($_POST['p_exo']) == 12) {

			$sSQL = " SELECT * FROM depositos WHERE n_planilla = '".$_POST['p_exo']."' AND lapso='".$lapsoProceso."' ";
			$Cmat->ExecSQL($sSQL, __LINE__, true);

			if ($Cmat->filas == 0){ // Si no esta registrada la exoneracion previamente.
				$dSQL = " INSERT INTO depositos (n_planilla,monto,exp_e,fecha,hora,lapso,opera) ";
				$dSQL.= " VALUES ('".$_POST['p_exo']."','".$_POST['m_exo']."','".$datos_p[1]."',";
				$dSQL.= "'".date('Y-m-d')."','".date('h:i')."','".$lapsoProceso."','EXO')";
				$Cmat->ExecSQL($dSQL, __LINE__, true);
			}			
		}

    }// Fin funcion

    function asigYaInscrita($asig, $lapso, $i, $deshacer){
            
        global $Cmat;
        global $todoOK;
        global $datos_p;
        global $errstr;
        global $secc;
        global $statusI;
           
        $dSQL   = "SELECT A.seccion||'-'||incluye, status from dace006 A, ";
        $dSQL   = $dSQL . "tblaca004 B WHERE A.exp_e='$datos_p[1]' AND A.c_asigna='$asig' AND ";
        $dSQL   = $dSQL . "A.c_asigna=B.c_asigna AND A.seccion=B.seccion ";
        $dSQL   = $dSQL . "AND A.lapso=B.lapso AND A.lapso='$lapso'";
        $Cmat->ExecSQL($dSQL,__LINE__);
        $Yainsc = ($Cmat->filas == 1);
        if ($Yainsc) {
           // $secc   = $Cmat->result[0][0];
			(strlen($Cmat->result[0][0]) > 3) ? $secc = $Cmat->result[0][0] : $secc = substr($Cmat->result[0][0],0,2);

            if (!$deshacer){
                $statusI[$i] = $Cmat->result[0][1];
            }                              
        }
        else {
            if (!$deshacer) {
                $statusI[$i] = '0'; //No inscrita;
            }
            $secc = '';
        }
        return $Yainsc;            
    }
    
    function eliminarAsignatura($asig, $secc, $lapso, $status, $retiro){
            
        global $Cmat;
        global $todoOK;
        global $datos_p;
        global $errstr; 

		$seccion = explode("-",$secc);
            
        $sm ='';
        if ($retiro || $status != '0') {
            // la marcamos como retirada o con el estatus anterior
            if ($retiro) { 
                $sm = '2';
            }
            else {
                $sm = $status;
            }
            $dSQL   = "UPDATE dace006 SET status='$sm' WHERE c_asigna='$asig' ";
            $dSQL   = $dSQL . "AND exp_e='$datos_p[1]' AND lapso='$lapso'";
            $Cmat->ExecSQL($dSQL,__LINE__, true);
        }
        else {// lo borramos de la seccion ...
            
            $dSQL   = "DELETE FROM dace006 where c_asigna='$asig' ";
            $dSQL   = $dSQL . "AND exp_e='$datos_p[1]' AND lapso='$lapso'";
            $Cmat->ExecSQL($dSQL,__LINE__,true);
        }
        // Luego actualizamos los inscritos...
        if (($sm == '7') || ($sm == 'A')) {
            $actInscritos='inscritos+1'; //hemos deshecho un retiro
            $condInscritos='inscritos>=0';
        }
        else {
            $actInscritos='inscritos-1'; //hemos deshecho una inscripcion o inclusion
            $condInscritos='inscritos>0';
        }
        if ($todoOK && ($Cmat->fmodif == 1)){
            if ($status !='2') {
                $dSQL = "UPDATE tblaca004 SET inscritos=$actInscritos WHERE ";
                $dSQL.= "c_asigna='$asig' AND seccion='$seccion[0]' AND lapso='$lapso' AND $condInscritos";
				$Cmat->ExecSQL($dSQL,__LINE__,true);

				if(($Cmat->fmodif == 1) && (isset($seccion[1]))){
					$dSQL = "UPDATE tblaca004_lab SET inscritos=$actInscritos WHERE ";
					$dSQL.= "c_asigna='$asig' AND seccion='$seccion[0]' AND grupo='$seccion[1]' AND lapso='$lapso' AND $condInscritos";
					$Cmat->ExecSQL($dSQL,__LINE__,true);
				}
            }
        }
    }
 
     function borrarTodas($lapso){
        
        global $Cmat;
        global $todoOK;
        global $datos_p;
        global $errstr; 
            
        $dSQL   = "SELECT A.c_asigna, A.seccion||'-'||incluye from dace006 A, ";
        $dSQL   = $dSQL . "tblaca004 B WHERE A.exp_e='$datos_p[1]' AND ";
        $dSQL   = $dSQL . "A.c_asigna=B.c_asigna AND A.seccion=B.seccion ";
        $dSQL   = $dSQL . "AND A.lapso=B.lapso AND A.lapso='$lapso'";
        $Cmat->ExecSQL($dSQL,__LINE__);
        $hayQueBorrar = ($Cmat->filas > 0);

        if ($todoOK && $hayQueBorrar) {
            foreach($Cmat->result as $bAsig) {
                eliminarAsignatura($bAsig[0], $bAsig[1], $lapso, '0', false);
                if (!$todoOK) {
                    break;
                }    
            }
        }
        return $todoOK;            

    }

    function deshacerTodo($dAsig, $i, $lapso){
        
        global $datos_p;
        global $secc;
        global $statusI;

        $secc = "";
        $k=0;
        while ($k<$i) {
            $asig = $dAsig[$k];
            $iSec = $dAsig[$k+1];
            $iRep = $dAsig[$k+2];
            if (asigYaInscrita($asig, $lapso, $k, true)) {
                eliminarAsignatura($asig, $secc, $lapso, $statusI[$k], false);
            }
            $k=$k+4;
        }    
    }
 
	function asignaturaPreinscrita($asig, $lapso, $exp) {

		global $Cmat;

		$pSQL  = "SELECT exp_e from dace006 where c_asigna='$asig' AND ";
		$pSQL .= "lapso='$lapso' AND exp_e='$exp' and status='P'";
        $Cmat->ExecSQL($pSQL,__LINE__,true);
        return ($Cmat->filas == 1);
	}

    function inscribirAsignatura($asig, $iSecc, $repite, $lapso){
            
        global $Cmat;
        global $datos_p;
        global $errstr;
        global $E;
        global $inscribe; 
        global $fecha;
        
        $inscrita = false;

		$seccion = explode("-",$iSecc);
        
		//Buscar nro de acta
        $dSQL   = "SELECT acta FROM tblaca004 WHERE c_asigna='$asig' ";
        $dSQL   = $dSQL . "AND seccion='$seccion[0]' AND lapso='$lapso'";
        $Cmat->ExecSQL($dSQL,__LINE__);
        $acta = $Cmat->result[0][0];

        if ($inscribe == 1) {
			$iStatus = 'A'; //modo inscripcion
        }
        else {
			$iStatus = 'A';//modo inclusion
        }

		
		
		//Sumar un inscrito y si lo hace entonces proceder a insertar
        $dSQL = "UPDATE tblaca004 SET inscritos=inscritos+1 WHERE ";
        $dSQL.= "c_asigna='$asig' AND seccion='$seccion[0]' AND lapso='$lapso' ";
        $dSQL.= "AND inscritos<tot_cup";
        $Cmat->ExecSQL($dSQL,__LINE__,true);
        if ($Cmat->fmodif == 1){ //se sumo un inscrito, proceder a insertarlo
				
				//actualizar la tabla de grupos
				if(isset($seccion[1])){
					$dSQL = "UPDATE tblaca004_lab SET inscritos=inscritos+1 WHERE ";
					$dSQL.= "c_asigna='$asig' AND seccion='$seccion[0]' ";
					$dSQL.= "AND grupo='$seccion[1]' ";	
					$dSQL.= "AND lapso='$lapso' AND inscritos<tot_cup";
					$Cmat->ExecSQL($dSQL,__LINE__,true);
				}
				

			if (asignaturaPreinscrita($asig, $lapso, $datos_p[1])){

				//OJO Modificar para hacer UPDATE con campo de grupo
				$dSQL  = "UPDATE dace006 SET acta='$acta', seccion='$seccion[0]', ";

				if(isset($seccion[1])){
					$dSQL.= "incluye='$seccion[1]', ";	
				}

				$dSQL .= "status='$iStatus', status_c_nota='$repite', ";
				$dSQL .= "fecha='$fecha' WHERE lapso='$lapso' ";
				$dSQL .= "AND c_asigna='$asig' AND exp_e='$datos_p[1]' ";
			}else {

				//OJO Modificar para hacer INSERT con campo de grupo
				$dSQL  = "INSERT INTO dace006 (acta, lapso, c_asigna, seccion, ";
				
				if(isset($seccion[1])){
					$dSQL.= "incluye, ";	
				}
				
				$dSQL .= "exp_e,status, status_c_nota, fecha) VALUES ('$acta','$lapso','$asig', ";
				$dSQL .= "'$seccion[0]',";
				
				if(isset($seccion[1])){
					$dSQL.= "'$seccion[1]', ";	
				}

				$dSQL .= "'$datos_p[1]','$iStatus','$repite','$fecha')";
			}
			$Cmat->ExecSQL($dSQL,__LINE__,true);
			$inscrita = ($Cmat->fmodif == 1);
        }
 		return($inscrita);
    }
    
    function registrar_asig() {
        
        global $ODBCSS_IP;
        global $datos_p;
        global $errstr;
        global $lapso;
        global $todoOK;
        global $secc;
        global $inscribe;
        global $Cmat;

        $todoOK    = true;
        $aInscrita = false; 
        $dAsig     = array();
        // $_POST['asignaturas'] trae : CODIGO1 SECCION1 condREP1 CODIGO2 SECCION2 condREP2...    

        $dAsig   = explode(" ",$_POST['asignaturas']);
        array_pop($dAsig);
        $total_a = count($dAsig);
        $secc    = "";
        $cupo    = 0;
        $acta    = "";
        $noInscritas ="";
        $i = 0;
        //if ($inscribe == '1') {         //si estamos en inscripciones y vuelve a entrar
        //  $todoOK = borrarTodas($lapso);//ocurrio antes un error. Borrar todo lo inscrito.
        //}         
		$Cmat->iniciarTransaccion("\nInicio Transaccion");
		registrarDepositos();
		while ($i<$total_a) {
			// Iniciar transaccion
            $asig = $dAsig[$i];
            $iSec = $dAsig[$i+1];
            $iRep = $dAsig[$i+2];
            //print_r($dAsig);
            $retiro = ($iSec == '-1');
            if (asigYaInscrita($asig, $lapso, $i, false)){
                if ($iSec != $secc) {
                    //eliminar la asignatura con status='0' (borrarla completa)
                    eliminarAsignatura($asig, $secc, $lapso,'0', $retiro);
                    //print "ya inscrita y eliminada $asig $secc<br>";
                }
            }
            if ($todoOK) {
                $aInscrita = ($iSec == $secc);
                if (!$aInscrita && !$retiro) {
                    $aInscrita = inscribirAsignatura($asig, $iSec, $iRep, $lapso);
                    //print "Inscrita $asig $secc<br>";
    
                    if (!$aInscrita) {
                        //print "No inscrita, deshacer $asig $secc<br>";
                        //deshacerTodo($dAsig, $i, $lapso);
						$Cmat->deshacerTransaccion("Rollback Transaccion");
                        return array($todoOK, true, $asig, $iSec);
                    }
                }
            }

			// Busca nro de acta
			$aSQL = "SELECT acta FROM tblaca004 WHERE c_asigna='".$asig."' ";
			$aSQL.= "AND seccion='".substr($iSec,0,2)."' AND lapso='".$lapso."' ";
			$Cmat->ExecSQL($aSQL,__LINE__);
			@$acta = $Cmat->result[0][0];

			// Cuento los inscritos/agregados
			$dSQL = " SELECT DISTINCT exp_e FROM dace006 WHERE lapso='".$lapso."' ";
			$dSQL.= " AND c_asigna='".$asig."' AND acta='".$acta."' AND seccion='".substr($iSec,0,2)."' ";
			$dSQL.= " AND status IN (7,'A') ";
			$Cmat->ExecSQL($dSQL,__LINE__);
			$total = $Cmat->filas;
	
			//Actualizo total de inscritos
			$uSQL = " UPDATE tblaca004 SET inscritos='".$total."' WHERE lapso='".$lapso."' ";
			$uSQL.= " AND c_asigna='".$asig."' AND acta='".$acta."' AND seccion='".substr($iSec,0,2)."' ";
			$Cmat->ExecSQL($uSQL,__LINE__);

            $i=$i+4;
        }
		if ($Cmat->finalizarTransaccion("Fin Transaccion")) {
			return array($todoOK, false, '','');
		}
		else {
			$Cmat->deshacerTransaccion("Rollback Transaccion");
            return array($todoOK, true, $asig, $iSec);
		}
    }


     
	 function imprimeH() {
        
        global $hora;
        global $ampm;
        global $datos_p;
        global $tLapso;
        global $inscribe;
		
		$foto = $datos_p[0].".jpg";
        
        $fecha = date('d/m/Y', time() - 3600*date('I'));
        if ($inscribe == '1') {
            $titulo = "Inscripci&oacute;n";
        }
        else if ($inscribe == '2'){
            $titulo = "Inscripci&oacute;n";
        }
        print <<<TITULO
    <tr>
        <td width="750">
        <p class="tit14">
        Planilla de $titulo $tLapso</p></td>
    </tr>
TITULO
;
?>
    <tr><td width="750">
        <table align="center" border="0" cellpadding="0" cellspacing="1" width="550"> 
            <tr><td class="tit14" style="text-align: right;"> 
<?php 
        print "Fecha:&nbsp; $fecha &nbsp; Hora: $hora $ampm </td></tr>";
?>   
 	   </table>
       </td>
    </tr>
    <tr>
		<td width="750" class="tit14">
        Datos del Estudiante
		</td>
	</tr>
    <tr><td class="dp">&nbsp;</td><tr> 
	<tr>
		<td>
        <table align="center" border="0" cellpadding="0" cellspacing="1" width="550"
				style="border-collapse: collapse;">
            <tbody>
				<tr>
					<td rowspan="3">
						<img border=1 width=115 height=150 alt="Foto" title="foto" src='/img/fotos/<?php echo $foto; ?>' >
					</td>
                    <td style="width: 250px; padding-left: 20px;" bgcolor="#FFFFFF">
                        <div class="dp">Apellidos:</div>
						<div class="dp"><?php echo $datos_p[3]." ".$datos_p[6]; ?></div>
					</td>
                    <td style="width: 250px; " bgcolor="#FFFFFF">
                        <div class="dp">Nombres:</div>
						<div class="dp"><?php echo $datos_p[2]." ".$datos_p[5]; ?></div>
					</td>
				</tr>
				<tr>
                    <td style="width: 110px;padding-left: 20px;" bgcolor="#FFFFFF">
                        <div class="dp">C&eacute;dula:</div>
						<div class="dp"><?php echo $datos_p[0]; ?></div>
					</td>
                    <td style="width: 114px;" bgcolor="#FFFFFF">
                        <div class="dp">Expediente:</div>
						<div class="dp"><?php echo $datos_p[1]; ?></div>
					</td>
                </tr>
				<tr>
                    <td style="width: 570px;padding-left: 20px;" bgcolor="#FFFFFF" colspan=2>
                        <div class="dp">Especialidad: <?php echo $_POST['carrera']; ?> </div></td>
				</tr>
			</tbody>
        </table>
    </td>
    </tr>
                   
<?php
        print <<<P002
                
P002
; 
    } //imprime_h   
?>
    
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>

<head>
<?php    
    $formOK = false;
	$inscribeN = 0;
	if (isset($_SERVER['HTTP_REFERER'])) {
		$formOK = ($_SERVER['HTTP_REFERER'] == $raizDelSitio .'planilla_r.php');
	}

    if (isset($_POST['inscribe'])){
       $inscribe = $_POST['inscribe'];
       $inscribeN = intval('0'.$inscribe);
    }
    if($formOK && isset($_POST['exp_e']) && ($inscribeN>0)) {
		$lapso		= $_POST['lapso'];    
		$inscrito	= intval($_POST['inscrito']);
		$sede		= $_POST['sede'];
	    $Cmat		= new ODBC_Conn($sede,"usuario2","usuario2",$ODBCC_conBitacora, $laBitacora);
		/*if (leer_datos_p($_POST['exp_e'])) {
			$formOK	= asignaturasCorrectas();
		}*/

$formOK		= leer_datos_p($_POST['exp_e']);


/////////////////////////////////////////////////////////////////////////////
# Consulta de datos necesarios
$Cdat = new ODBC_Conn($sede,"usuario2","usuario2", $ODBCC_conBitacora, $laBitacora);
$Cdat->iniciarTransaccion("Inicio Validacion: ".$_POST['exp_e']." - ");
$mSQL = "SELECT pensum,c_uni_ca FROM dace002 WHERE exp_e='".$_POST['exp_e']."'";
$Cdat->ExecSQL($mSQL, __LINE__,true);

$e=$_POST['exp_e'];
$p=$Cdat->result[0][0];
$c= $Cdat->result[0][1];

# Tomamos la nuevas materias a agregar
	$materias	= array();

    $materias	= explode(" ",$_POST['asignaturas']);
	//print_r($materias);
    array_pop($materias);
    $total_ag = count($materias)/4;
	
	$agregadas = array();
	$i=0;
	$j=$i;
	
	while ($i<$total_ag) {
		$agregadas[] = $materias[$j];
		$j=$j+4;
		$i++;
	}

	$colores = array();
	$i=0;
	$j=3;
	while ($i<$total_ag) {
		$colores[] = $materias[$j];
		$j=$j+4;
		$i++;
	}

	$secciones = array();
	$i=0;
	$j=1;
	while ($i<$total_ag) {
		$secciones[] = $materias[$j];
		$j=$j+4;
		$i++;
	}

@$colores = array_combine($agregadas,$colores);
//print_r($colores);

@$secciones = array_combine($agregadas,$secciones);
//print_r($secciones);

# Fin. >>> el array $agregadas contiene los codigos de las asignaturas a agregar.



# Consulta de inscritas
$Cm = new ODBC_Conn($sede,"c","c", $ODBCC_conBitacora, $laBitacora);
$mSQL = "SELECT c_asigna FROM dace006 WHERE exp_e='".$_POST['exp_e']."' ";
$mSQL .= "and lapso='".$lapsoProceso."' and (status='7' or status='A' or status='Y')";
$Cm->ExecSQL($mSQL, __LINE__,true);

$inscritas=$Cm->result;

if (count($inscritas) > 0){
	# Tomamos la materias ya inscritas para armarlas en el array.
	$k=0;
	$ins="";
	while ($k<count($inscritas)){
		$ins.=implode($inscritas[$k])." ";
		$k++;
	}
	$inscritas	= explode(" ",$ins);
	array_pop($inscritas);
	# Fin. >>> el array $inscritas contiene los codigos de las asignaturas inscritas.	
}//Fin count($inscritas)>0*/

# Unimos los dos arrays ($agregadas+$inscritas) para validarlas todas.
	$todas = array();
	$todas=array_merge($agregadas,$inscritas);
	
# Quitamos los duplicados
	$todas = array_unique($todas);
	
#contamos las asignaturas
	$todas_mat=count($todas);

	$todas = array_values($todas);

	/*print_r($agregadas);
	echo "<br><br>";*/

# Fin. >>> el array $todas contiene los codigos de todas asignaturas (inscritas y por agregar).

# Consulta para Repitencia
	$repitencias = Array();
	$x=0;
	while ($x < $todas_mat) {
		if (in_array($todas[$x], $agregadas)) {
			//echo $todas[$x]." esta en agregadas";
			$color = $colores[$todas[$x]];
		}else if (in_array($todas[$x], $inscritas)){
			$color = 'B';		
		}
		
		if($color != 'G'){
			//echo "validar repitencia: ".$todas[$x]." - ".$color."<br><br>";
			$Crep = new ODBC_Conn($sede,"c","c", $ODBCC_conBitacora, $laBitacora);
			$mSQL = "SELECT repite FROM materias_inscribir ";
			@$mSQL.= "WHERE exp_e='".$e."' AND c_asigna='".$todas[$x]."'";
			$Crep->ExecSQL($mSQL, __LINE__,true);
			if(isset($Crep->result[0]))$repitencias[$x]=$Crep->result[0];
		}
		$x++;
	}
# Fin. >>> el array $repitencias contiene los valores de rep_sta para cada asignatura.
	
	@$maxRep=max($repitencias);
	$repite=$maxRep[0];
	
# $repite contiene el valor maximo de repitencia para validar la cantidad de UC a cursar.
	if (($repite == 1) or ($repite == 4)) $maxUC = 18;
	elseif (($repite > 1) && ($repite != 4) ) $maxAsig = 2;

# Validacion para mas de dos repitencias
	if (isset($maxAsig)){
		if($todas_mat > $maxAsig){// Intenta inscribir mas asignaturas de lo permitido.
			#echo "SOLO PUEDE VER DOS ASIGNATURAS <BR>";
			$formOK=false;
			echo '<script languaje=\"javacript\">alert("Lo siento, estas intentando inscribir mas asignaturas de lo permitido.\n\nIngrese nuevamente al sistema y asegurese de seleccionar correctamente sus asignaturas.");window.close();</script>';
		}
	}
	
# Consulta de unidades de Credito y Tres Semestre Consecutivos.
	$y=0; // iterador
	$sem_alto = $y; // el mas bajo posible
	$sem_bajo = 15; // el mas alto (arbitrariamente)
	$uc_ins = $y; // cero UC inscritas

# Contamos las unidades de credito y tomamos los semestres
	while ($y < $todas_mat) {
		if (in_array($todas[$y], $agregadas)){
			$color = $colores[$todas[$y]];
		}else if (in_array($todas[$y], $inscritas)){
			$color = 'B';		
		}

		if($color != 'G'){
			//echo "validar UC permitidas: ".$todas[$y]." <br><br>";
			$Crep = new ODBC_Conn($sede,"c","c", $ODBCC_conBitacora, $laBitacora);
			$mSQL = "SELECT semestre,u_creditos from tblaca009 ";
			$mSQL = $mSQL."WHERE pensum='".$p."' AND c_uni_ca='".$c."' ";
			@$mSQL = $mSQL."AND c_asigna='".$todas[$y]."'"; 
			$Crep->ExecSQL($mSQL, __LINE__,true);
			
		#Almacenamos los resultados en variables
			@$sem=$Crep->result[0][0]; // semestre de la asignaturas
			@$uc=$Crep->result[0][1]; // unidades de credito de la asignaturas

		#Para las electivas (semestre 11) restamos 2 para que deje inscribirle
			if($sem > 10){
				$sem = $sem-2; // convierte $sem = 11 en $sem = 9
			}
			
		# Capturamos el semestre mas bajo
			if($sem <= $sem_bajo){
				$sem_bajo = $sem;
			}
			
		# Capturamos el semestre mas alto
			elseif($sem >= $sem_alto){
				$sem_alto = $sem;
			}

		# Acumulamos las unidades de credito
			$uc_ins+= $uc;
		}//fin color != G
		
		$y++; // iterador + 1
	} // fin while
	
# Validacion para una repitencia (18 Unidades de Credito como Maximo)
	if (isset($maxUC)){ 
		if(($uc_ins > $maxUC) or ($uc_ins > 22)){ // Intenta inscribir mas UC de lo permitido
			#echo "SOLO PUEDE VER 18 UNIDADES DE CREDITO <BR>";
			$formOK=false;
			echo '<script languaje=\"javacript\">alert("Lo siento, estas intentando inscribir mas creditos de los permitido.\n\nIngrese nuevamente al sistema y asegurese de seleccionar correctamente sus asignaturas.");window.close();</script>';
		}
	}

	# Validacion para tres semestres consecutivos (DESACTIVADO DESDE 2011-2)
	$dif=$sem_alto-$sem_bajo;
	/*if (isset($sem_bajo)&&isset($sem_alto)){
		if($dif>=3){
			#echo "VIOLA TRES SEMESTRES CONSECUTIVOS <BR>";
			$formOK=false;
			echo '<script languaje=\"javacript\">alert("Lo siento, estas intentando inscribir asignaturas con mas de tres semestres de separacion.\n\nIngresa de nuevo al sistema e intentalo de nuevo.");window.close();</script>';
		}
	}*/

# Validacion para pre-requisitos
	$conex = new ODBC_Conn($sede,"c","c", $ODBCC_conBitacora, /*$laBitacora*/'test.log');
	$y = 0;
	while ($y < $todas_mat) {
		if (in_array($todas[$y], $agregadas)){
			$color = $colores[$todas[$y]];
		}else if (in_array($todas[$y], $inscritas)){
			$color = 'B';		
		}

		if($color != 'G'){
			//echo "validar pre-requisitos: ".$todas[$y]." <br><br>";
			$mSQL = "SELECT pre_cod_asig1,pre_cod_asig2,pre_cod_asig3,pre_cod_asig4,";
			$mSQL.= "pre_cod_asig5,pre_cod_asig6,pre_cod_asig7 ";
			$mSQL.= "FROM tblaca009 ";
			$mSQL.= "WHERE pensum='".$p."' AND c_uni_ca='".$c."' ";
		   @$mSQL.= "AND c_asigna='".$todas[$y]."'"; 
			$conex->ExecSQL($mSQL, __LINE__,true);

			$pre_req = $conex->result;

			@$pre_req = array_values(array_diff($pre_req[0], array('')));

			# >>> el array $pre_req contiene los pre-requisitos para la asignatura $todas[$y].
			for ($i=0; $i < count($pre_req); $i++){
				# Buscar si cumple pre-requisitos para $todas[$y]
				$mSQL = "SELECT c_asigna ";
				$mSQL.= "FROM dace004 ";
				$mSQL.= "WHERE exp_e='".$e."' AND c_asigna='".$pre_req[$i]."' ";
				$mSQL.= "AND status IN ('0','3','B','C')";
				$conex->ExecSQL($mSQL, __LINE__,true);
				
				@$cumple = ($conex->result[0][0] == $pre_req[$i]);

				if (!$cumple){
					$formOK=false;
					echo '<script languaje=\"javacript\">alert("Lo siento, para poder inscribir '.$todas[$y].' debe aprobar '.$pre_req[$i].'.\n\nIngrese nuevamente al sistema y asegurese de seleccionar correctamente sus asignaturas.");window.close();</script>';
				}
			}
		}//fin color != G
		$y++;
	}
# Fin validacion para pre-requisitos

# Validacion para co-requisitos
	$conex = new ODBC_Conn($sede,"c","c", $ODBCC_conBitacora, /*$laBitacora*/'test.log');
	$y = 0;
	while ($y < $todas_mat) {

		if (in_array($todas[$y], $agregadas)){
			$color = $colores[$todas[$y]];
		}else if (in_array($todas[$y], $inscritas)){
			$color = 'B';		
		}

		if($color != 'G'){
						
			$mSQL = "SELECT par_cod_asig1,par_cod_asig2,par_cod_asig3 ";
			$mSQL.= "FROM tblaca009 ";
			$mSQL.= "WHERE pensum='".$p."' AND c_uni_ca='".$c."' ";
		   @$mSQL.= "AND c_asigna='".$todas[$y]."'"; 
			$conex->ExecSQL($mSQL, __LINE__,true);

			$co_req = $conex->result;
						
			@$co_req = array_values(array_diff($co_req[0], array('')));

			# >>> el array $co_req contiene los co-requisitos para la asignatura $todas[$y].
			for ($i=0; $i < count($co_req); $i++){
								
				# Busco si esta aprobada.
				$mSQL = "SELECT c_asigna ";
				$mSQL.= "FROM dace004 ";
				$mSQL.= "WHERE exp_e='".$e."' AND c_asigna='".$co_req[$i]."' ";
				$mSQL.= "AND status IN ('0','3','B','C')";
				$conex->ExecSQL($mSQL, __LINE__,true);

				$aprobada = ($conex->filas == 1);

				if (!$aprobada){
					# Busco si esta en la seleccion de materias o inscrita en el lapso actual
					//if((!in_array($co_req[$i], $todas)) and (!$cumple)){
					if (in_array($todas[$y], $agregadas)){
						$colorA = $colores[$todas[$y]];
					}else if (in_array($todas[$y], $inscritas)){
						$colorA = 'B';		
					}


					if (in_array($co_req[$i], $agregadas)){
						$colorB = $colores[$co_req[$i]];
					}else if (in_array($co_req[$i], $inscritas)){
						$colorB = 'B';		
					}else{
						$colorB = 'G';
					}

						# Busco si esta inscrita en el lapso
						
						//if (($colorA != 'B') or ($color == 'Y')){
					if ((($colorA == 'B') or ($colorA == 'Y')) and ($colorB == 'G')){
						$formOK = false;
						echo '<script languaje=\"javacript\">alert("Lo siento, para poder inscribir '.$todas[$y].' debe inscribir '.$co_req[$i].'.\n\nIngrese nuevamente al sistema y asegurese de seleccionar correctamente sus asignaturas.");window.close();</script>';			
					}
				}// fin no la ha aprobado			
			}
		}// Fin color != G
		//echo "<br><br>";
		$y++;
	}
# Fin validacion para co-requisitos

# Validacion para co-requisitos FUERA DE COLA (Misma a utilizar en retiros)
	$conex = new ODBC_Conn($sede,"c","c", $ODBCC_conBitacora, /*$laBitacora*/'test.log');
	$y = 0;
	while ($y < $todas_mat) {

		if (in_array($todas[$y], $agregadas)){
			$color = $colores[$todas[$y]];
		}else if (in_array($todas[$y], $inscritas)){
			$color = 'B';		
		}

		if ($color == 'G'){

			# Busco si es co-requisito de alguna materia
			$mSQL = "SELECT c_asigna ";
			$mSQL.= "FROM tblaca009 ";
			$mSQL.= "WHERE pensum='".$p."' AND c_uni_ca='".$c."' ";
			$mSQL.= "AND ((par_cod_asig1='".$todas[$y]."') ";
			$mSQL.= "OR (par_cod_asig2='".$todas[$y]."') ";
			$mSQL.= "OR (par_cod_asig3='".$todas[$y]."')) ";
			$conex->ExecSQL($mSQL, __LINE__,true);

			$esprerreq = ($conex->filas == 1);

			if ($esprerreq){ // Si es co-requisito
				$c_asigna = $conex->result[0][0];// materia de la cual $todas[$y] es co-requisito

				# Busco si ya aprobo la materia de la cual es co-requisito
				$mSQL = "SELECT c_asigna ";
				$mSQL.= "FROM dace004 ";
				$mSQL.= "WHERE exp_e='".$e."' AND c_asigna='".$c_asigna."' ";
				$mSQL.= "AND status IN ('0','3','B')";
				$conex->ExecSQL($mSQL, __LINE__,true);

				$aprobada = ($conex->filas == 1);
				
				if(!$aprobada){// si no la ha aprobado

					# OJO BUSCAR SI ESTA EN DACE006 y COLOR de $agregadas != G

					if (in_array($c_asigna, $agregadas)){
						$color = $colores[$c_asigna];
					}else if (in_array($c_asigna, $inscritas)){
						$color = 'B';		
					}



					# Busco si esta inscrita en el lapso
					
					if ($color != 'G'){
						//echo $e." no ha seleccionado ".$c_asigna;
						$formOK = false;
						echo '<script languaje=\"javacript\">alert("Lo siento, para poder retirar de cola '.$todas[$y].' debe retirar '.$c_asigna.'.\n\nIngrese nuevamente al sistema y asegurese de seleccionar correctamente sus asignaturas.");window.close();</script>';
					}// fin si no esta inscrita
				}// fin si no la ha aprobado
			}// fin esprerreq
		} // fin color ==  G
		
		$y++;
	}// fin while

# Fin Validacion para co-requisitos FUERA DE COLA

# Validacion para seleccion de grupo
$conex = new ODBC_Conn($sede,"c","c", $ODBCC_conBitacora, /*$laBitacora*/'test.log');
	$y = 0;
	while ($y < $todas_mat) {
		if (in_array($todas[$y], $agregadas)){
			$color = $colores[$todas[$y]];
		}else /*if (in_array($todas[$y], $inscritas))*/{
			$color = 'G';		
		}

		if( ($color != 'G') && ($color != 'Y') ){
			# Busco si la asignatura tiene laboratorio
			$mSQL = "SELECT horas_lab ";
			$mSQL.= "FROM tblaca008 ";
			$mSQL.= "WHERE c_asigna='".$todas[$y]."' AND horas_teoricas > 0";
			$conex->ExecSQL($mSQL, __LINE__,true);

			@$tienelab = ($conex->result[0][0] > 0);

			if ($tienelab) {

				/*echo "sec ".strlen($secciones[$todas[$y]]);
				die();*/

				$selgrupo = (strlen($secciones[$todas[$y]]) == 5);

				if (!$selgrupo) {// si la seccion viene sin grupo
					$formOK = false;
					echo '<script languaje=\"javacript\">alert("Debe seleccionar un grupo de laboratorio para la asignatura: '.$todas[$y].'.\n\nIngrese nuevamente al sistema y asegurese de seleccionar correctamente sus asignaturas.");window.close();</script>';
				}
			}
		}
		$y++;
	}



# Fin Validacion para seleccion de grupo

$Cdat->FinalizarTransaccion("Finaliza Validacion: ".$_POST['exp_e']." - ");

#OJO eliminar o comentar
//print_r($_POST);

/*$formOK=false;
die();*/
/////////////////////////////////////////////////////////////////////////////

		if ($formOK) {
			$formOK	= asignaturasCorrectas();
		}

	}
	if ($formOK) {
?>  

		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
		<?php
			print $noCache; 
			print $noJavaScript; 
		?>
		<title>Planilla de Inscripci&oacute;n Lapso <?php print $lapso; ?></title>
  		<script language="Javascript" src="md5.js">
		<!--
		alert('Error con el fichero js');
		// -->
        </script>
		<script languaje="Javascript">
		<!--
<?php
        //print "Estudiante = '$datos_p[2]';";
		print "Estudiante = \"".$datos_p[2]."\";\n";
?>
        var Imprimio = false;
        
        function imprimir(fi) {
            with (fi) {
                bimp.style.display="none";
                bexit.style.display="none";
                window.print();
                Imprimio = true;
                msgI = Estudiante + ':\nSi mandaste a imprimir tu planilla\n';
                msgI = msgI + "pulsa el botón 'Finalizar' y ve a retirar tu planilla por la impresora,\n";
                msgI = msgI + 'de lo contrario vuelve a pulsar Imprimir\n';
                //alert(msgI);
                bimp.style.display="block";
                bexit.style.display="block";
            }
        }
        function verificarSiImprimio(){
            window.status = Estudiante + ': NO TE VAYAS SIN IMPRIMIR TU PLANILLA';
            if (Imprimio){
                window.close();
            }
            else {
                msgI = '            ATENCION!\n' + Estudiante;
                alert(msgI +':\nNo te vayas sin imprimir tu planilla');
            }
        }
		<!--
        document.writeln('</font>');
		//-->
        </script>
		<style type="text/css">
		<!--
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

		.nota {
			text-align: justify; 
			font-family: Arial; 
			font-size: 9px; 
			font-weight: normal;
			color: black;
		}
		.mat {
			text-align: center; 
			font-family: Arial; 
			font-size: 10px; 
			font-weight: normal;
			color: black;
			vertical-align: top;
		}
		.tot {
			text-align: left; 
			font-family: Arial; 
			font-size: 10px; 
			font-weight: normal;
			color: black;
			vertical-align: top;
		}
		.matB {
			font-family:Arial; 
			font-size: 10px; 
			font-weight: bold;
			color: black; 
			text-align: center;
			vertical-align: top;
			height:20px;
			font-variant: small-caps;
		}
		.dp {
			text-align: left; 
			font-family: Arial; 
			font-size: 14px;
			font-weight: bold;
			background-color: #FFFFFF; 
			font-variant: small-caps;
		}
		.dp1 {
			text-align: center; 
			font-family: Arial; 
			font-size: 11px;
			font-weight: normal;
			background-color: #FFFFFF; 
			font-variant: small-caps;
		}
		.depo {
			text-align: center; 
			width: 150px;
			background-color: #FFFFFF;
            font-size: 12px;
			color: black;
			font-family: courier;
		}
		-->
		</style>
		</head>
        <body  <?php global $botonDerecho; echo $botonDerecho; ?> onload="javascript:self.focus();" 
		      onclose="return false">
		<table align="left" border="0" width="750" id="table1" cellspacing="1" cellpadding="0" 
			   style="border-collapse: collapse">
    <tr><td>
		<table border="0" width="750" cellpadding="0">
		<tr>
		<td width="125">
		<p align="right" style="margin-top: 0; margin-bottom: 0">
		<img border="0" src="/img/logo_unexpo.png" width="90" height="68"></p></td>
		<td width="500">
		<p class="titulo">
		Universidad Nacional Experimental Polit&eacute;cnica</p>
		<p class="titulo">
		Antonio Jos&eacute; de Sucre</p>
		<p class="titulo">
		Vicerrectorado <?php echo $vicerrectorado; ?></font></p>
		<p class="titulo">
		<?php echo $nombreDependencia; ?></font></td>
		<td width="125">&nbsp;</td>
		</tr><tr><td colspan="3" style="background-color:#D0D0D0;">
		<font style="font-size:1pt;"> &nbsp;</font></td></tr>
	    </table></td>
    </tr>
<?php
        if (intval('0'.$inscrito) != 1 || $inscribeN=2){
            list ($inscOK, $sinCupo, $asig, $seccion) = registrar_asig();
			//$Cmat->escribirBitacora();
//            reportar_error($errstr,false);
        }
        else {
            $inscOK = true;
            $sinCupo = false;
        }
        if ($inscOK){
            $datosOK = consultarDatos($sinCupo);
            if (!$sinCupo){
                imprimeH();
                reportarInscripcion();
                reportarError($errstr,false);
            print <<<FINAL0
        </td></tr>
        </table>
        </body>
        </html>
FINAL0
;        
            }
            else if (!$datosOK) {
                imprimeH();
                reportarError($errstr);
                print <<<FINAL1
        </td></tr>
        </table>
        </body>
        </html>
FINAL1
;
                exit;
            }
            if ($sinCupo) { //reportar el error de sin cupo
            reportarError($errstr,false);    
            print <<<ERRORSC
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <title>Asignatura sin cupo : $asig, Secci&oacute;n: $seccion</title>
        </head>
        <body   onload="javascript:self.focus()">
        <form name ="sincupo" method="POST" action="planilla_r.php">
            <input type="hidden" name="cedula" value="{$_POST['cedula']}">
            <input type="hidden" name="contra" value="{$_POST['contra']}">
            <input type="hidden" name="asignaturas" value="{$_POST['asignaturas']}">
            <input type="hidden" name="asigSC" value="$asig">
            <input type="hidden" name="seccSC" value="$seccion">
        </form>
        <script languaje="Javascript">
        <!--
        with (document){
    //alert('No hay cupo en la'+ sincupo.asigSC.value +' ' + sincupo.seccSC.value + '\\n' + sincupo.asignaturas.value);
           sincupo.submit();
        }
        -->
        </script>
        </body>
</html>

ERRORSC
;        
            } //if($sinCupo)
        
        }//if insc_ok
        else {
            imprimeH();
            reportarError($errstr);
            print <<<FINAL2
        </td></tr>
        </table>
        </body>
        </html>
FINAL2
;        
        }
    } //if $formOK
    else {
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

?>
