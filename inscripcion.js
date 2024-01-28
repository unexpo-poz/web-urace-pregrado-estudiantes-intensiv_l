function marcarAsignaturas(asignaturas,asigSC) {

    var cod_uc = new Array();
    scod_uc = "";
    asigs = asignaturas.split(" ");
    with (document.pensum) {
        i = 0; 
        j = 0;
        while (j < asignaturas.length){
            i = 0;
            while(i < (CB.length - 1)){
                cod_uc = CB[i].value.split(" ");  
                if ((cod_uc[0] == asigs[j]) && (cod_uc[0] != asigSC )){
                    CB[i].selectedIndex = parseInt(asigs[j+3],10); 
                }
                i++;
            }
            j = j + 4;
        } 
    }
}

function prepdata(fp,fd) {
    
    fd.cedula.value = ced;
    fd.exp_e.value = exp_e;
    fd.contra.value = contra;
    fd.carrera.value = carrera;

    with (fd) {
        if(asigSC.value != "") {
            marcarAsignaturas(asignaturas.value, asigSC.value);            
            scMsg = "Lo siento, ya no hay cupo en \n";
            scMsg = scMsg + "la sección: " + seccSC.value + "\nde la asignatura: " + asigSC.value;
            scMsg = scMsg + "\n Por favor, modifique su selección";
            asigSC.value ="";
            alert(scMsg);
       }
        else asignaturas.value = "";
    }
    
    var cod_uc = new Array();
    scod_uc ="";
    with(fp) {
        i = 0;
        while(i < (CB.length - 1)){
          cod_uc = CB[i].value.split(" ");  
          if (cod_uc[5] !='0'){
              //alert(CB[i].value +" seleccionado");
              scod_uc = cod_uc[0] + " " + cod_uc[5] + " " + cod_uc[6] + " " + cod_uc[8];
              //alert(scod_uc);
             fd.asignaturas.value = fd.asignaturas.value + scod_uc  + " "; 
          }
          i++;
        }
    }
    //registra sexo y fecha de nac:
	if (fd.c_inicial.value != "0"){
		laFechaS =	1900 + parseInt(document.getElementById('anioN').value,10); 
		laFechaS += '-';
		laFechaS +=	document.getElementById('mesN').selectedIndex + 1;
		laFechaS += '-';
		laFechaS +=	document.getElementById('diaN').selectedIndex + 1; 
		document.f_c.f_nac_e.value = laFechaS;
		elSexo  = parseInt(document.getElementById('sexoN').value,10);
		//aSexo   = Array('1','2','1');
		//alert(elSexo);
		document.f_c.sexo.value = elSexo;
	}
	//registra los depositos:
    with (fd) {
		i = 0;
		depositos.value = ""; 
        while(i < p_dep.length){
			if (p_dep[i].value.length == 8){
				fd.depositos.value = fd.depositos.value + p_dep[i].value +" " + m_dep[i].value  + " "; 
            }
            i++;
        }
    }

    if(fd.inscribe.value == fd.inscrito.value) {
        fd.submit();
        return true;
    }
    return true;
}

function actualizarTotales(fp,ft,$update) {
      
    ct_mat		= 0;
    ct_uc		= 0;
    ct_monto	= 0;
    v_materia	= 0.2*29400; //0.2 Unidades Tributarias
    k =fp.CB.length - 1;
    with(fp) {
       j = 0;
       while(j < k){
          //alert(k +'| '+CB[j].value+'['+CB[j].selectedIndex+']');
          if (CB[j].selectedIndex != '0'){ 
              cod_uc = CB[j].value.split(" ");               
              uc   = parseInt(cod_uc[1],10);
              ct_mat++;
              ct_uc+=uc;
              ct_monto+=v_materia;
          }
          j++;
       }
    }
    if ($update){
        with(ft){
            t_mat.value=ct_mat;
            t_uc.value =ct_uc;
            t_monto.value=ct_monto;
        }
		return true;
    }
    else return ct_uc;
}
   
function correquisitoOK(fp) {

    cOK = true;
    var matAInscr = "";
    correq    = "";
    with (fp) {
        for(j=0;j < (CB.length - 1); j++){
            if (CB[j].selectedIndex != '0'){
                cod_uc = CB[j].value.split(" ");                    
                arrayMat[j] = cod_uc[0];
            }
            else arrayMat[j] = "";
        }
        matAInscr = arrayMat.join(" ");
        for(j=0;j < (CB.length - 1); j++){
           if (CB[j].selectedIndex != '0'){
               if (matAInscr.indexOf(CBC[j].value) < 0) {
                   correq = correq + "Para poder inscribir " + arrayMat[j];
                   correq = correq + " debes inscribir " + CBC[j].value +"\n"; 
                   cOK = false;
               } 
           }

       }
    }
    if (!cOK){
        alert("Conflicto de correquisito:\n" + correq);
    }
    return(cOK);
}

function actualizarSecciones() {

    with (document.pensum) {
        for(j=0;j < (CB.length - 1); j++){             
            arraySecc[j] = CB[j].selectedIndex;
        }
    }
}

function estadoAnterior(lSeccion){

    with (document.pensum) {
        for(j=0;j < (CB.length - 1); j++){
            cod_ucSel = lSeccion.value.split(" "); 
            cod_uc    = CB[j].value.split(" ");            
            if (cod_ucSel[0] == cod_uc[0]){
                        
                lSeccion.options[arraySecc[j]].selected = true;
            }
        }

    }
}


function excesoDeCreditos(lSeccion) {
// modificado para las reglas del intensivo:
// (a) Dos asignaturas sin límite de créditos o \n";
// (b) Hasta diez créditos.\n";

    exceso  = false;
    actualizarTotales(document.pensum,document.totales,true); 
	with (document.totales) {
		tUC  = t_uc.value;
		tMat = t_mat.value;
	}
	//alert('tuc='+tUC+',tmat='+tMat);
    if (( tUC > 10 )&&(tMat>2)){
		mens= "Lo siento, no puedes seleccionar esa asignatura\n";
        mens= mens + "porque te excederás de lo estipulado en el reglamento:\n";
        mens= mens + " (a) Dos asignaturas sin límite de créditos o \n";
        mens= mens + " (b) Hasta diez créditos.\n";
        alert(mens);
		exceso = true;
	}
    return exceso;
}

function cambiarColor(lSeccion) {
    cod_uc = lSeccion.value.split(" ");
    for(i=0;i<7;i++){
        identCol = cod_uc[0]+i; //identificador de division
            //alert(identCol+' ' +cod_uc[7]);
		text_color = '#000000';
        switch (cod_uc[7]) { // de acuerdo a la seleccion y estatus, se establece el color:
            case 'G' :  lcolor='#F0F0F0'; //gris : NO SELECCIONADO
                        break;
            case 'B' :  lcolor='#99CCFF'; //azul : INSCRITO
                        break;
            case 'X' :  lcolor='#FF6666'; //rojo : RETIRO
						text_color ='#FFFFFF';
                        break;
        }
        document.getElementById(identCol).style.background = lcolor;
        document.getElementById(identCol).style.color = text_color;
    }

}

function resaltar(lSeccion) {
    
     if (correquisitoOK(document.pensum)) {
         if (!excesoDeCreditos(lSeccion)){
             cambiarColor(lSeccion);
        }
        else {
            estadoAnterior(lSeccion);
        }
        
     }   
     else {
         estadoAnterior(lSeccion);
     }
     actualizarSecciones();
     actualizarTotales(document.pensum,document.totales, true);
}

function borrar_depositos(fd) {

	with (fd) {
		i = 0;
		depositos.value = ""; 
        while(i < p_dep.length){
			p_dep[i].value = "";
			m_dep[i].value = "";
            i++;
        }
    }
}

function reiniciarTodo() {
    //return true;
    with (document) {
        ind_acad = f_c.ind_acad.value;
        pensum.reset();
        totales.reset();
        actualizarTotales(pensum,totales, true); 
		borrar_depositos(f_c);
		actualizar_total_dep(f_c);
        actualizarSecciones(); 
        prepdata(pensum,f_c);
        for(j=0;j < (pensum.CB.length - 1); j++) {
            cambiarColor(pensum.CB[j]);
        }
    }
	//Actualizamos sexo y fecha de nacimiento:
	//por cortesia, femenino primero (cambiamos M=2, F=1
	//aunque en la base de datos es al reves OJO!
	laFechaS = document.f_c.f_nac_e.value+"---"; //por si la fecha esta en blanco
	laFecha  = new Array();
	laFecha = laFechaS.split('-'); //anio,mes,dia
	//	alert('['+laFecha+']'+laFecha[2]+laFecha[1]+laFecha[0]);
	if (laFechaS != ""){
		document.getElementById('diaN').selectedIndex = laFecha[2] - 1; 
		document.getElementById('mesN').selectedIndex = laFecha[1] - 1;
		document.getElementById('anioN').value = laFecha[0].substr(2,4); 
	}
	elSexo  = parseInt(document.getElementById('sexoN').value,10);
		//aSexo   = Array('1','2','1');
		//alert(elSexo);
		document.f_c.sexo.value = elSexo;
	document.f_c.c_inicial.value = "1"; //marcamos como validada la fecha
}

function fadePopIE(speed){
	//alert(miTiempo);
	if ((miTiempo > 0) && (miTiempo <= 101)) {
		document.getElementById('floatlayer').style.filter="alpha(opacity="+miTiempo+")";
		miTiempo=miTiempo-speed;
		miTimer = setTimeout("fadePopIE("+speed+")","20");
	}
	else if (miTiempo<=0){
		document.getElementById('floatlayer').style.visibility="hidden";
		clearTimeout(miTimer);
	}
	else clearTimeout(miTimer);
}

function fadePopMOZ(speed){
	//alert(miTiempo);
	if ((miTiempo > 0) && (miTiempo <= 101)) {
		document.getElementById('floatlayer').style.opacity=miTiempo/100;
		miTiempo=miTiempo-speed;
		miTimer = setTimeout("fadePopMOZ("+speed+")","20");
	}
	else if (miTiempo<=0){
		document.getElementById('floatlayer').style.visibility="hidden";
		clearTimeout(miTimer);
	}
	else clearTimeout(miTimer);
}

function desvanecer(speed) {
	miTiempo = 100;
	if (speed < 0) {
		miTiempo = 1;
	}
	//alert(miTiempo);
	if (IE4){
		miTimer = setTimeout("fadePopIE("+speed+")","20");
	}
	else if (NS6){
		miTimer = setTimeout("fadePopMOZ("+speed+")","20");
	}
}

function verificar(){
    var dia = parseInt (document.getElementById('diaN').selectedIndex) + 1;
    var mes = parseInt (document.getElementById('mesN').selectedIndex) + 1;
    var anyo = parseInt ('0'+document.getElementById('anioN').value,10) + 1900;
	clearTimeout(miTiempo);
    if (CancelPulsado){
        return false;
    }
	if (FechaValida(dia,mes,anyo)){
		vcontra = hex_md5(document.getElementById('pV').value);
		if(vcontra == contra){
			prepdata(document.pensum,document.f_c);
			if ((document.f_c.asignaturas.value != "") || (document.f_c.inscribe.value!="1")) {
				//alert(escape(document.f_c.depositos.value));
				depositosOK = false;
				revisarDepositos(escape(document.f_c.depositos.value)+"&sede="+document.f_c.sede.value);
				//if (depositosOK){
				//	document.f_c.submit();
				//	return true;
				//}
				//else {
				//return false;
				//}
			}
			else {
				alert('Debes seleccionar al menos una materia');
				return false;
			}
		}
		else {
			alert('Clave incorrecta.\n Por favor intente de nuevo');
			document.getElementById('pV').value="";
			document.getElementById('pV').focus();
			return false;
		}
	}
}
 
 function verificarEnter() {
 
     miTiempo=setTimeout("verificar()",200);
 }

function cancelar() {
    CancelPulsado = true;
    document.getElementById('pV').value="";
    //hideMe();
	desvanecer(10);
}
function Inscribirme(){

    //if( parseInt(document.totales.t_uc.value)>0){
    prepdata(document.pensum,document.f_c)
    if ((document.f_c.asignaturas.value != "") || (document.f_c.inscribe.value!="1")) {
		if (validar_dep(document.f_c) && monto_exacto(document.totales, document.f_c)) {
			CancelPulsado = false;        
			showMe();
		}
		else {
			return false;
		}
    }
    else {
        alert('Debes seleccionar al menos una materia');
    }
}

function anyoBisiesto(anyo)
 {
  var fin = anyo;
  if (fin % 4 != 0)
    return false;
    else
     {
      if (fin % 100 == 0)
       {
        if (fin % 400 == 0)
         {
          return true;
         }
          else
           {
            return false;
           }
       }
        else
         {
          return true;
         }
     }
 }

function FechaValida(dia,mes,anyo)
 {
  var anyohoy = new Date();
  var Mensaje = "";
  var yearhoy = anyohoy.getYear();
  if (yearhoy < 1999)
    yearhoy = yearhoy + 1900;
  if(anyoBisiesto(anyo))
    febrero = 29;
    else
      febrero = 28;
   if ((mes == 2) && (dia > febrero))
    {
     Mensaje += "- Día de nacimiento inválido\r\n";
    }
   if (((mes == 4) || (mes == 6) || (mes == 9) || (mes == 11)) && (dia > 30))
    {
     Mensaje += "- Día de nacimiento inválido\r\n";
    }
   if ((anyo<1950) || (yearhoy - anyo < 15))
    {
     Mensaje += "- Año de nacimiento inválido\r\n" + anyo;
    } 
   if (Mensaje != "")
   {
	   alert(Mensaje);
	   return false;
   }
   else {
	   return true;
   }
 }
 function mostrar_ayuda(ayudaURL) {
		window.open(ayudaURL,"instruciones","left=0,top=0,width=700,height=250,scrollbars=0,resizable=0,status=0");
 }
