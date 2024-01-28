<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE> New Document </TITLE>
<META NAME="Generator" CONTENT="EditPlus">
<META NAME="Author" CONTENT="">
<META NAME="Keywords" CONTENT="">
<META NAME="Description" CONTENT="">
</HEAD>

<BODY>
<?php
	
    include_once('../inc/odbcss_c.php');

	function depositos_correctos() {
        
               
        $todook = true;       
        $dep	= array();
//        $dep	= explode(" ",$_REQUEST['depositos']);
//        array_pop($dep);
//        $total_d = count($dep);
//		if ($total_d > 0) {
//			$listaPlanillas = implode("','",$dep);
//			$listaPlanillas = "('".$listaPlanillas."')";
            $Cdep = new ODBC_Conn("BQTO","c","c");
            $dSQL = "SELECT n_planilla, exp_e FROM depositos";
            $Cdep->ExecSQL($dSQL);
            if($Cdep->filas > 0) {
				$todook = false;
				$pDup = array();  
				foreach($Cdep->result as $pe) {
					$pDup = array_merge($pDup,$pe);
				}
				$pDuplicadas = implode("','",$pDup);
  				return "'".$pDuplicadas."'";
                }
 //           }            
   }

print '<pre>';
print depositos_correctos();
print '</pre>';
?>

</BODY>
</HTML>
