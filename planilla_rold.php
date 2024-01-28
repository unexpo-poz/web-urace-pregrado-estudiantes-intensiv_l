<?
	include_once("inc/vImage.php");
    include_once('../inc/odbcss_c.php');
	include_once ('inc/config.php');
	include_once ('../inc/activaerror.php');
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
					if ($Cdatos_p->filas == 1){ //Lo encontro en orden_inscripcion
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
			$preinscrito = ($Csecc->filas >0);
			$verificarPre = ($tipoJornada == 1);
			//muestrame($Csecc);
		}
		else {
			$preinscrito  = true;
			$verificarPre = false;
		}
		// En caracas, si un estudiante esta repitiendo y no se oferta su materia
		// a repetir, no se le puede dejar inscribir:
		if ($sedeActiva == "CCS") {
			if ($verificarPre) {
				$sSQL  = "SELECT c_asigna FROM materias_inscribir WHERE repite>2 ";
				$sSQL .= "AND exp_e='$datos_p[1]' AND c_asigna NOT in ";
				$sSQL .= "(SELECT a.c_asigna from dace006 A, ";
				$sSQL .= "tblaca004 B WHERE A.c_asigna=B.c_asigna AND ";
				$sSQL .= "A.lapso=B.lapso and A.exp_e='$datos_p[1]' AND ";
				$sSQL .= "A.lapso = '$lapso')";
			}
			else {
				$sSQL  = "SELECT c_asigna FROM materias_inscribir WHERE repite>2 ";
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
		//muestrame($Csecc);
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
                    UC cursadas</td>
                <td style="width: 85px;" class="enc_p">
                    Estatus/Secc</td>
                    
            </tr>
ENC_2
;
			$sSQL = "SELECT B.c_asigna, seccion FROM tblaca004 A, materias_inscribir B ";
			$sSQL = $sSQL."WHERE A.c_asigna=B.c_asigna AND exp_e='$datos_p[1]' AND ";
			$sSQL = $sSQL."A.lapso = '$lapso' AND inscritos<tot_cup ORDER BY 1,2";
			$Csecc->ExecSQL($sSQL);
			$tS = array(); //todas las asignaturas con sus secciones
			foreach($Csecc->result as $tmS) {
				$tS=array_merge($tS,$tmS);
			}
			//ahora buscamos si ya tiene inscritas, incluidas o retiradas:
			$sSQL = "SELECT c_asigna, seccion, status FROM dace006 WHERE ";
			$sSQL = $sSQL." exp_e='$datos_p[1]' AND lapso='$lapso' AND NOT status in ('C')";
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
				//muestrame(array(0=>$status, 1=>$m));
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
        
        global $inscribe, $sedeActiva;
        $totSecc    = count($mS);
        $noInscrita = (count($mI) == 0);
        $msgDis = '';

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

		//este codigo se usa para no habilitar las asignaturas que no
		//aparecen en la oferta academica.
        if(($totSecc == 0) && ($noInscrita || $status = 'P')) {
            $msgNoInsc = 'SIN CUPO';
			$msgDis = ' disabled="disabled" ';
        }
       
        $CBref      = "CB";


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
        print "<td><div id=\"$m[1]4\" class=\"inact\">$vRep&nbsp;\n";
        //unidades creditos de repitencia:
        print "<td><div id=\"$m[1]5\" class=\"inact\">$m[6]&nbsp;\n";
        print "<td><div id=$m[1]6 class=\"inact\">";
        //seccion://informacion: codigo, creditos, repite, cred_curs, tipo_lapso 
        if (($inscribe == '1') || $noInscrita) {
                print <<<P_SELECT0
                    <select name="$CBref" OnChange="resaltar(this)" class="peq" $msgDis>
                         <option
P_SELECT0
; 
               if ($noInscrita) print " selected=\"selected\" ";

			   if ($inscribe == '1' && $totSecc == '0' && $status == '7') {
					$seccI   = $mIns[$mI[0]+1];
					print <<<P_SELECTSCI
                      <option selected="selected" value="$m[1] $m[3] $m[5] $m[6] $m[7] 0 $vRep B 1">$seccI</option>
P_SELECTSCI
;
			   }
			   else {
               print <<<P_SELECT1
                             value="$m[1] $m[3] $m[5] $m[6] $m[7] 0 $vRep G 0"> $msgNoInsc </option>

P_SELECT1
;           
			   }
			   if ($inscribe != '3'){        
				for ($k=0; $k < $totSecc; $k++) {
					print "<option ";
					$ki = $k+1;
					// Si la seccion a colocar en la lista es igual a la inscrita
					// queda seleccionada
					if (!$noInscrita){
						if ($tS[$mS[$k]+1] == $mIns[$mI[0]+1]) print "selected=\"selected\"";
					}
					print <<<P_SELECT1
						value="$m[1] $m[3] $m[5] $m[6] $m[7] {$tS[$mS[$k]+1]} $vRep B $ki">{$tS[$mS[$k]+1]}</option>
P_SELECT1
;        
				}
			}
			else {
            print <<<P_SELECTP
                        <option value="$m[1] $m[3] $m[5] $m[6] $m[7] P $vRep B 1"> PREINSC </option>
P_SELECTP
;
			}
        }
        else if ($inscribe == '2'){

            $seccI   = $mIns[$mI[0]+1];
            $statusI = $mIns[$mI[0]+2];
            if ($statusI == '2') {
                print <<<P_SELECT2
                 <select name="$CBref" disabled="disabled" 
                    style="color:black; background-color:#FFFF99;" class="peq"> 
                    <option
                      value="$m[1] $m[3] $m[5] $m[6] $m[7] 0 $vRep X 0">RETIRADA</option>
P_SELECT2
;
            }
            else {
            print <<<P_SELECT3
                 <select name="$CBref" OnChange="resaltar(this)" class="peq"> 
                 <option value="$m[1] $m[3] $m[5] $m[6] $m[7] -1 $vRep X 0">&nbsp;&nbsp;&nbsp;RETIRAR&nbsp;</option> 
                  <option selected="selected" 
                      value="$m[1] $m[3] $m[5] $m[6] $m[7] 0 $vRep B 1">$seccI</option>
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
                print <<<P_SELECT2
                 <select name="$CBref" disabled="disabled" 
                    style="color:black; background-color:#FFFF99;" class="peq"> 
                    <option value="$m[1] $m[3] $m[5] $m[6] $m[7] 0 $vRep X 0">RETIRADA</option>

P_SELECT2
;
            }
            else {
            print <<<P_SELECT3
                 <select name="$CBref" OnChange="resaltar(this)" 
				         class="peq" style="color:black;"> 
                  <option 
                      value="$m[1] $m[3] $m[5] $m[6] $m[7] 0 $vRep G 0"> NO PREINSC </option>
                  <option selected="selected" 
                      value="$m[1] $m[3] $m[5] $m[6] $m[7] P $vRep B 1"> $seccI </option>

P_SELECT3
;
            
            }
        }
        print "</select></div></td></tr>\n";
    }

    function imprime_primera_parte($dp) {
    
	global $archivoAyuda,$raizDelSitio, $tLapso, $tProceso, $vicerrectorado;
	global $botonDerecho;

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
<SCRIPT LANGUAGE="Javascript" SRC="{$raizDelSitio}/intensivo.js">
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
		Departamento de Admisi&oacute;n y Control de Estudios</font></td>
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
    global $depositos;
	global $valorMateria;

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
          <form width="570" align="rigth" name="totales">
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
                        Monto requerido Bs:&nbsp;</font>
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
        <table align="center" border="0" cellpadding="0" 
            cellspacing="0" width="400">
          <tbody>
          <form width="400" align="center" name="f_c" method="POST" action="registrar.php">
U001
;
	// Imprime los depositos ya hechos:
	$k=0;
	foreach($depositos as $d){
		$k++;
		$monto_entero = intval($d[1],10);
        print <<<U002
                <tr bgcolor="#EFEFEF" >
                    <td class="inact" style="text-align:right;">&nbsp;Planilla
                        Nro:&nbsp;
                        <input type="text" maxlength="8" size="10" tabindex="1" name="p_depH"
							value="$d[0]" disabled
                            style="text-align: left; font-family: courier; font-size: 11px;
							       border-width: 1px; height: 16px; background: #f0f0f0;"
                            onChange="return EsNumero(this,document.f_c,false)"
                            onBlur="return EsNumero(this,document.f_c,false)"></td>
                    <td class="inact" style="text-align:right;">&nbsp;Monto Bs:&nbsp;
                        <input type="text" maxlength="7" size="10" tabindex="1" name="m_depH"
							value="$monto_entero" disabled
                            style="direction: rtl; font-family: courier; font-size: 11px;							       		       border-width: 1px; height: 16px; background: #f0f0f0;"
                            OnChange="return EsNumero(this,document.f_c,true)"
                            OnBlur="return EsNumero(this,document.f_c,true)">
                            <font style="font-family: courier; font-size: 11px;
                                        background-color: white;">,00</font> 
                   </td>
                </tr>
U002
;

	}
    print "<input type=\"hidden\" name=\"m_depH\" value=\"\">\n";
	//completa 6 depositos con depositos en blanco
	for(;$k<9;$k++){
        print <<<U002
                <tr bgcolor="#EFEFEF" >
                    <td class="inact" style="text-align:right;">&nbsp;Planilla
                        Nro:&nbsp;
                        <input type="text" maxlength="8" size="10" tabindex="1" name="p_dep"
                            style="text-align: left; font-family: courier; font-size: 11px;
							       border-width: 1px; height: 16px;"
                            onChange="return EsNumero(this,document.f_c,false)"
                            onBlur="return EsNumero(this,document.f_c,false)"></td>
                    <td class="inact" style="text-align:right;">&nbsp;Monto Bs:&nbsp;
                        <input type="text" maxlength="7" size="10" tabindex="1" name="m_dep"
                            style="direction: rtl; font-family: courier; font-size: 11px;							       		       border-width: 1px; height: 16px;"
                            OnChange="return EsNumero(this,document.f_c,true)"
                            OnBlur="return EsNumero(this,document.f_c,true)">
                            <font style="font-family: courier; font-size: 11px;
                                        background-color: white;">,00</font> 
                   </td>
                </tr>
U002
;
    }
    print <<<U003
                <tr>
                    <td class="inact">&nbsp;&nbsp;</font>
                    </td>
                    <td class="inact" 
		                style="text-align:right; background:#FFFF66; ">&nbsp;&nbsp;&nbsp;Total Bs:&nbsp;</font>
                        <input type="text" name="t_dep" maxlength="7" size="10" tabindex="1" 
                            readonly="readonly"
                            style="border-style: solid; border-width: 0px;
                                   direction: rtl; font-family: courier; font-size: 10pt;
                                   background-color: #FFFF66">
                            <font style="font-family: courier; font-size: 10pt;
                                        background-color: #FFFF66;">,00</font> 
                   </td>
                </tr>
             </tbody>
          </table>
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
                        <input type="button" value="Inscribirme/Imprimir" name="B1"
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
                        <input type="hidden" name="sedeActiva" value="$sedeActiva">
                        <input type="hidden" name="sexo" value="$dp[14]">
                        <input type="hidden" name="f_nac_e" value="$dp[15]">
                        <input type="hidden" name="c_inicial" value="0">

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
<span style="font-family:Arial; font-size:13px; font-weight:bold;
             text-align:left; background-color:#FFFF33">
	Y POR FAVOR INDICA TU SEXO Y TU FECHA DE NACIMIENTO CORRECTA PARA PODER 
		CONTINUAR CON LA INSCRIPCI&Oacute;N:</td></tr>
<tr>
  <td colspan=2 valign="middle"><p align="left">
     <font face=arial size=2><b> Clave:&nbsp;</b>
       <input type="password" name="pV" id="pV"
         style="background-color:#99CCFF" size="20">
  </td>
</tr>
<tr><td style="font-family:Arial; font-size:13px; font-weight:bold;">
		Sexo:</td><td style="font-family:Arial; font-size:13px; font-weight:bold;">
		Fecha de Nacimiento:</td>
<tr><td><select name="sexoN" id="sexoN">
              <option value="2" >Masculino</option>
              <option value="1" >Femenino</option>
              </select></td>

    <td><select name="diaN" id="diaN">
        <option > 01</option>
        <option > 02</option>
        <option > 03</option>
        <option > 04</option>
        <option > 05</option>
        <option > 06</option>
        <option > 07</option>
        <option > 08</option>
        <option > 09</option>
        <option > 10</option>
        <option > 11</option>
        <option > 12</option>
        <option > 13</option>
        <option > 14</option>
        <option > 15</option>
        <option > 16</option>
        <option > 17</option>
        <option > 18</option>
        <option > 19</option>
        <option > 20</option>
        <option > 21</option>
        <option > 22</option>
        <option > 23</option>
        <option > 24</option>
        <option > 25</option>
        <option > 26</option>
        <option > 27</option>
        <option > 28</option>
        <option > 29</option>
        <option > 30</option>
        <option > 31</option>
		</select> de
        <select name="mesN" id="mesN">
		<option value="01" >ENERO</option>
        <option value="02" >FEBRERO</option>
		<option value="03" >MARZO</option>
        <option value="04" >ABRIL</option>
        <option value="05" >MAYO</option>
		<option value="06" >JUNIO</option>
        <option value="07" >JULIO</option>
        <option value="08" >AGOSTO</option>
        <option value="09" >SEPTIEMBRE</option>
        <option value="10" >OCTUBRE</option>
        <option value="11" >NOVIEMBRE</option>
        <option value="12" >DICIEMBRE</option>
        </select> de 19
        <input name="anioN" type="text" class="inputtext" id="anioN" value="" size="2" maxlength="2">
</td></tr></span>
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
		mensaje = mensaje + "Es posible que usted deba solicitar REINGRESO\n";
		mensaje = mensaje + "si se retiro en el semestre anterior.";
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
	$suFecha = mktime($tHora, $tMin, 0, $tFecha[1], $tFecha[2], $tFecha[0],date('I'));
	return ($suFecha <= $fechaActual);
}

    // Programa principal
    //leer las variables enviadas
    //$_POST['cedula']='17583838';
    //$_POST['contra']='827ccb0eea8a706c4c34a16891f84e7b';       
    if(isset($_POST['cedula']) && isset($_POST['contra'])) {
        $cedula=$_POST['cedula'];
        $contra=$_POST['contra'];
        // limpiemos la cedula y coloquemos los ceros faltantes
        $cedula = ltrim(preg_replace("/[^0-9]/","",$cedula),'0');
        $cedula = substr("00000000".$cedula, -8);
        $fvacio = false; 
		//echo $cedula;
		//echo $contra;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">
<?php
print $noCache; 
print $noJavaScript; 
?>
<title><?php echo $tProceso .' '. $lapso; ?></title>
<?
        $cedYclave = cedula_valida($cedula,$contra);
		//muestrame($cedYclave);
		if(!$fvacio && $cedYclave[0] && $cedYclave[1] && $cedYclave[2]) {
            // Revisamos si es su turno de inscripcion:
            //if (intval($datos_p[8])<=intval($datos_p[9])) {
			// Para las preinscripciones no se chequea el rango de inscripcion
			// Asi que alumno_en_rango siempre sera 'TRUE' 
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
				
				$inscribe = '1';
				$inscrito = '0'; //intval('0'.$datos_p[13]);
                $Cmat = new ODBC_Conn($sede,"c","c");
				// echo $sede.'[cmat]';
                $mSQL = "SELECT semestre, tblaca008.c_asigna, asignatura, ";
                $mSQL = $mSQL."tblaca008.unid_credito, co_req, repite, cre_cur, tipo_lapso FROM materias_inscribir, ";
                $mSQL = $mSQL."tblaca009 , tblaca008 WHERE ";
                $mSQL = $mSQL."materias_inscribir.c_asigna=tblaca009.c_asigna AND "; 
                $mSQL = $mSQL."mencion='".$mencion."' AND pensum='".$pensum."' ";
                $mSQL = $mSQL."AND exp_e='".$exped."' AND c_uni_ca='".$c_carr."' ";
                $mSQL = $mSQL."AND tblaca008.c_asigna=tblaca009.c_asigna ORDER BY semestre";
                $Cmat->ExecSQL($mSQL);
				$lista_m=$Cmat->result;
				$mSQL = "SELECT n_planilla, monto FROM depositos WHERE exp_e='".$exped."'";
				$Cmat->ExecSQL($mSQL);
				$depositos = $Cmat->result;
				unset($Cmat);
                $carr_esp= array('.'=>"",
                                 'A'=>" (COMUNICACIONES)", 
                                 'B'=>" (COMPUTACI&Oacute;N)",
                                 'C'=>" (CONTROL)");
                $datos_p[4] = $datos_p[4].$carr_esp[$datos_p[5]];
				if ($inscribe != '0') {
					imprime_primera_parte($datos_p);
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
