function depositoRepetido(i, depo) {
	repetido = false;
	for(j=0;j < depo.length; j++){
		if (j!=i){
			repetido = repetido || (depo[i].value == depo[j].value);
		}
		if (repetido){
			depo[j].style.backgroundColor="#CCFFFF";
			break;
		}
	}
	return repetido;

}

function validar_dep(fd){
    var todo_ok = true;
	hayMontoMalo = false;
	hayDepoMalo  = false;
	hayRepetidos  = false;
    with (fd){
        for(i=0;i < m_dep.length;i++){
            depmalo = (p_dep[i].value.length < 8) && (p_dep[i].value.length > 0);
            depmalo = depmalo || (p_dep[i].value.length == 0) && (m_dep[i].value.length > 0);
            montomalo = (p_dep[i].value.length >0 ) && (parseInt("0"+m_dep[i].value,10) == 0);
			if (!p_dep[i].value.length == 0) {
				hayRepetidos = hayRepetidos || depositoRepetido(i,p_dep);
			}
			if (hayRepetidos) {
					todo_ok = false;
					break;
			}
            if (depmalo || montomalo) {
                todo_ok=false;
				depmalo = depmalo || hayRepetidos;
                if (depmalo) {
					hayDepoMalo = hayDepoMalo || depmalo ;
					p_dep[i].style.backgroundColor="#CCFFFF";
				}
                if (montomalo) {
					hayMontoMalo = hayMontoMalo || montomalo ;
					m_dep[i].style.backgroundColor="#CCFFFF";
                }                
            }
            else if ((parseInt("0"+m_dep[i].value,10) != 0)){
                p_dep[i].style.backgroundColor="#FFFFFF";
            }
        }

    }

    if (!todo_ok) {
		errMsg  = "Existen errores en los datos de los depósitos:\n";
		if (hayRepetidos){
			errMsg += "- Las planillas no pueden repetirse\n";
		}
		if (hayDepoMalo){
			errMsg += "- Las planillas deben tener OCHO digitos\n";
		}
		if (hayMontoMalo) {
			errMsg += "- El monto no puede ser CERO\n";
		}
		errMsg += "Por favor corrija los campos marcados en azul claro";
		alert(errMsg);
	}
    return (todo_ok);
}

function monto_exacto(ft,fd){
    exacto = (parseInt("0"+ft.t_monto.value,10) <= parseInt("0"+fd.t_dep.value,10));
    if (!exacto) {
		alert ("Disculpa, el monto total del depósito\n ES MENOR que el monto requerido!");
		return exacto;
	}
	exacto = (ft.t_mat.value > 0);
    if (!exacto) alert ("Disculpa, debes elegir al menos una asignatura");
    return exacto;
}

function actualizar_total_dep(fc) {
    var tdep = 0;
    with (fc){
        for(i=0;i < m_dep.length;i++){
            tdep1=parseInt("0"+m_dep[i].value,10);
            tdep+=tdep1;
			//alert('['+i+']'+m_dep[i].value);
        }
        for(i=0;i < m_depH.length - 1;i++){
            tdep1=parseInt("0"+m_depH[i].value,10);
            tdep+=tdep1;
			//alert('['+i+']'+m_depH[i].value);
        }
        t_dep.value=tdep;

    }
        return (true);
}

function habilitarDepositos(n) {

	v_materia	= parseInt(document.totales.valor_materia.value,10); //0.2 Unidades Tributarias
	fd = document.f_c; //el formulario
    with (fd) {
		i = 0;
		k = n - (m_depH.length - 1); //Toma en cuenta depositos ya registrados
		if (isNaN(k)) {
			k = n;
		};
        while(i < k){
			//m_dep[i].value = v_materia;
			p_dep[i].disabled = false;
			m_dep[i].disabled = false;
			p_dep[i].style.background = "#ffffff" ;
			m_dep[i].style.background = "#ffffff" ;
			i++;
		}
        while(i < p_dep.length){
			p_dep[i].value = "";
			m_dep[i].value = "";
			p_dep[i].disabled = true;
			m_dep[i].disabled = true;
			p_dep[i].style.background = "#f0f0f0" ;
			m_dep[i].style.background = "#f0f0f0" ;
			i++;
		}

    }
	actualizar_total_dep(fd);
}



function EsNumero(cTexto,ft, totalizar) {
        var cadena = cTexto.value;
        if((cadena.length==0) && totalizar) actualizar_total_dep(ft);
        var nums="1234567890";
        i=0;
        cl=cadena.length;
        var checkc = false;
        while(i < cl)  {
            cTemp= cadena.substring (i, i+1);
            if (nums.indexOf (cTemp, 0)==-1) {
                if (!checkc){
                    alert("Ha introducido caracteres no numéricos y se eliminarán");
                    checkc = true;
                }
                cadT = cadena.split(cTemp);
                cadena = cadT.join("");
                cTexto.value=cadena;
                i=-1;
                cl=cadena.length;
            }
            i++;
        }
        if(totalizar) {
			actualizar_total_dep(ft);
		}
		cTexto.style.backgroundColor = "#FFFFFF";
}

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
            scod_uc = cod_uc[0] + " " + cod_uc[5] + " " + cod_uc[6] + " " + cod_uc[8];
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
		aSexo   = Array('1','2','1');
		document.f_c.sexo.value = aSexo[elSexo];
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
    v_materia	= parseInt(ft.valor_materia.value,10); //0.2 Unidades Tributarias
    k =fp.CB.length - 1;
    with(fp) {
       j = 0;
       while(j < k){
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
		habilitarDepositos(6);
		return true;
    }
    else return ct_uc;
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


function calcularMaxCargaCCS() {
    
    iMateria = -1; //indica que ninguna materia genera exceso de creditos
    limite   = 10;
    obligatoria  = 0; //0=no obliga, 1=obliga pero puede ver otras, 2=sola
	seleccionada = false;
    with (document.pensum) {
        for(j=0;j < (CB.length - 1); j++){
            cod_uc  = CB[j].value.split(" ");
            uc      = parseInt(cod_uc[1],10);
            repite  = parseInt('0'+cod_uc[2],10);
            cre_cur = parseInt(cod_uc[3],10);
            t_lapso = cod_uc[4];
			//alert(cod_uc[0]+'|'+repite+'|'+limite);
            if (repite == 3) {
					limite = 9;
                    obligatoria = 1;
                    iMateria = j;
					seleccionada = (CB[j].selectedIndex !='0');
			}
			else if (repite > 3) {
                    limite = uc;
                    obligatoria = 2;
                    iMateria = j;
					seleccionada = (CB[j].selectedIndex !='0');
			}
		}
	}
    return(Array(iMateria,limite,obligatoria, seleccionada));
}


function excesoDeCreditosCCS(lSeccion) {
    
	// lSeccion es un campo que contiene la sgte informacion 
	// separada por espacios: 
	//      [0]              [1]          [2]              [3]                        [4] 
	// codigo_asignatura, creditos, veces_que_repite, cred_curs_ultima_repitencia, tipo_lapso 
    
    exceso  = false;
    cod_uc  = lSeccion.value.split(" ");               
    ucm     = parseInt(cod_uc[1],10);
    repite  = parseInt('0'+cod_uc[2],10);
    cre_cur = parseInt(cod_uc[3],10);
    t_lapso = cod_uc[4];
    total_uc= parseInt(document.totales.t_uc.value);
    indice = parseFloat(document.f_c.ind_acad.value);

    maxCarga = new Array(3) //contiene maximo de creditos, condicion que aplica 
                            //y puntero a la materia que limita.
    if (actualizarTotales(document.pensum,document.totales, false) == total_uc) {
        ucm = 0
    }
    if (lSeccion.selectedIndex == '0') {
        ucm = -ucm;
    }

	noPuedeEliminarla = (ucm < 0) && (repite > 2);

	maxCarga = calcularMaxCargaCCS(); //Array(Imateria, limite, obligatoria);

    iMateria    = maxCarga[0];
    maxCreditos = maxCarga[1];
    obligatoria = maxCarga[2];
    seleccionada = maxCarga[3];
	crAinsc  = total_uc + ucm;
	if (iMateria >= 0) {
        matLim = document.pensum.CB[iMateria].value.split(" ");
        }
    else {
         matLim = "";
    }
	//alert(matLim[0] + ' ' +maxCreditos);

	if (noPuedeEliminarla) {
		if (repite == 3) {
			maxCreditos = 9;
		}
		else if (repite > 3) {
			maxCreditos = -ucm;
		}
		matLim[0] = cod_uc[0];
	}

    if ((crAinsc > maxCreditos) || ((matLim !="") && ! seleccionada)) {
        exceso = true;
        mens1 = "    PROBLEMA DE EXCESO DE CRÉDITOS:\nNo puedes ";
        (ucm > 0) ? mAQ = "agregar" : mAQ = "borrar";
        mens1  = mens1 + mAQ + " esta asignatura.\n"
        mcausa = "Tu límite es ";
		if (matLim !="") {
			mcausa = "La condición de repitencia de la asignatura \n";
            mcausa = mcausa + matLim[0] + " te obliga a cursarla ";
			mensLC = "con un limite de " + maxCreditos + " créditos\n";
			mensCS = "";       
		}
		else {
			mensLC = maxCreditos + " créditos\n";
			mensCS = " y estas intentando inscribir " + crAinsc + " créditos.\n";      
		}
    }

    if (exceso) {
        alert(mens1 + mcausa + mensLC + mensCS);
    }
    return exceso;
}


function calcularMaxCargaBQTO() {
    
    iMateria = -1; //indica que ninguna materia genera exceso de creditos
    limite   = 21;
    veces    = '';
    with (document.pensum) {
        for(j=0;j < (CB.length - 1); j++){
            cod_uc  = CB[j].value.split(" ");
            uc      = parseInt(cod_uc[1],10);
            repite  = cod_uc[2];
            cre_cur = parseInt(cod_uc[3],10);
            t_lapso = cod_uc[4];
            if ((t_lapso !='I') && (CB[j].selectedIndex !='0')) {
                switch(repite) {
                    case '':
                            break;
                    case '0' :
                    case 'R' : //repite por primera vez
                            if (veces == '') {
                                limite = cre_cur;
                                iMateria = j;
                            }
                            else if((veces == '0')||(veces == 'R')){
                                if (limite < cre_cur) {
                                    limite = cre_cur;
                                    iMateria = j;
                                }
                            }
                            veces = repite;
                            break;
                    case '1' : //repite por 2da vez
                            if ((veces =='') || (veces =='0')) {
                                (cre_cur > 10) ? limite = 10 : limite = cre_cur;
                                iMateria = j;
                                veces = '1';
                            }
                            else if (veces == '1') {
                                if (limite < cre_cur ) {
                                    limite = cre_cur;
                                    iMateria = j;
                                }
                                if (limite > 10) {
                                    limite = 10;
                                }  

                            }
                            break;
                    case '2' : //repite por tercera vez : debe verla solita
                            if (veces != '2') {
                                limite = uc;
                                veces = '2';
                                iMateria = j
                            }
                } //switch (repite)
            }
   
        }
    }
    return(Array(iMateria,limite,veces));
}

function excesoDeCreditosBQTO(lSeccion) {
    
	// lSeccion es un campo que contiene la sgte informacion 
	// separada por espacios: 
	//      [0]              [1]          [2]              [3]                        [4] 
	// codigo_asignatura, creditos, veces_que_repite, cred_curs_ultima_repitencia, tipo_lapso 
    
	intensivo = document.f_c.lapso.value.indexOf("I") >= 0;

    exceso  = false;
    cod_uc  = lSeccion.value.split(" ");               
    ucm     = parseInt(cod_uc[1],10);
    repite  = cod_uc[2];
    cre_cur = parseInt(cod_uc[3],10);
    t_lapso = cod_uc[4];
    total_uc= parseInt(document.totales.t_uc.value);
    indice = parseFloat(document.f_c.ind_acad.value);

    maxCarga = new Array(3) //contiene maximo de creditos, condicion que aplica 
                            //y puntero a la materia que limita.
    if(indice >= 6.0) {
        CreditosAdic = 2;
    }
    else {
        CreditosAdic = 0;
    }
    //alert("seccion=" + lSeccion.selectedIndex );
    if (actualizarTotales(document.pensum,document.totales, false) == total_uc) {
        ucm = 0
    }
    if (lSeccion.selectedIndex == '0') {
        ucm = -ucm;
    }
	maxCarga = calcularMaxCargaBQTO(); //Array(Imateria, limite, veces);

    iMateria = maxCarga[0];
    limite   = maxCarga[1];
    veces    = maxCarga[2];
    crAinsc  = total_uc + ucm;

    (veces =='2') ? maxCreditos = limite : maxCreditos = limite + CreditosAdic;
    
	if ((intensivo && (maxCreditos > 10))) {
		maxCreditos = 10;
	}

	if (iMateria >= 0) {
        matLim = document.pensum.CB[iMateria].value.split(" ");
        }
    else {
         matLim = "";
    }
    if (crAinsc > maxCreditos){
        exceso = true;
        mens1 = "    PROBLEMA DE EXCESO DE CRÉDITOS:\nNo puedes ";
        (ucm > 0) ? mAQ = "agregar" : mAQ = "borrar";
        mens1  = mens1 + mAQ + " esta asignatura.\n"
        mensLC = maxCreditos + " créditos\n";
        mensCS = " y estas intentando inscribir " + crAinsc + " créditos.\n";       
        mcausa = "Tu límite es ";
        if (veces != '') {
            mcausa = "La condición de repitencia de la asignatura \n";
            mcausa = mcausa + matLim[0] + " te limita a ";
        }
    }

    if (exceso) {
        alert(mens1 + mcausa + mensLC + mensCS);
    }
    return exceso;
}

function cambiarColor(lSeccion) {
    cod_uc = lSeccion.value.split(" ");
    for(i=0;i<7;i++){
        identCol = cod_uc[0]+i; //identificador de division
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

function vrLCM(){
	 //return (document.f_c.sede.value.indexOf("CCS") >= 0);
	 return true;

}

function resaltar(lSeccion) {


	 if (vrLCM) {
		excesoC = excesoDeCreditosCCS(lSeccion);
	 }
	 else {
		excesoC = excesoDeCreditosBQTO(lSeccion);
	 }
     if (!excesoC){
             cambiarColor(lSeccion);
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
	elSexo  = parseInt('0'+document.f_c.sexo.value,10);
	aSexo   = Array('1','2','1');
	document.getElementById('sexoN').value = aSexo[elSexo];
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
