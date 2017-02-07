<?php
/* Copyright (C) 2006 fhcomplete.org
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, USA.
 *
 * Authors:	Manfred Kindl 	< manfred.kindl@technikum-wien.at >
 */
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/globals.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/ort.class.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../include/software.class.php');
require_once('../include/software_typ.class.php');
require_once('../include/software_ort.class.php');
require_once('../config.inc.php');

if (!$db = new basis_db())
	die('Es konnte keine Verbindung zum Server aufgebaut werden.');

$user = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

if(!$rechte->isBerechtigt('addon/lehrmittel', null, 's'))
	die($rechte->errormsg);

$reloadstr = '';  // neuladen der liste im oberen frame
$htmlstr = '';
$errorstr = ''; //fehler beim insert

$software_id = (isset($_REQUEST["software_id"])?$_REQUEST["software_id"]:'');
$softwaretyp_kurzbz = (isset($_POST["softwaretyp_kurzbz"])?$_POST["softwaretyp_kurzbz"]:'');
$content_id = (isset($_POST["content_id"])?$_POST["content_id"]:'');
$ansprechperson_uid = (isset($_POST["ansprechperson_uid"])?$_POST["ansprechperson_uid"]:'');
$bezeichnung = (isset($_POST["bezeichnung"])?$_POST["bezeichnung"]:'');
$version = (isset($_POST["version"])?$_POST["version"]:'');
$anzahl_lizenzen = (isset($_POST["anzahl_lizenzen"])?$_POST["anzahl_lizenzen"]:'');
$lizenzkosten = (isset($_POST["lizenzkosten"])?str_replace(',','.',$_POST["lizenzkosten"]):'');
$ablaufdatum = (isset($_POST["ablaufdatum"])?$_POST["ablaufdatum"]:'');
$aktiv = isset($_POST["aktiv"])?true:false;
$anmerkung = (isset($_POST["anmerkung"])?$_POST["anmerkung"]:'');

$neu = isset($_POST['neu'])?$_POST['neu']:'true';

if(isset($_POST["save"]))
{
	if(!$rechte->isBerechtigt('addon/lehrmittel', null, 'suid'))
		die($rechte->errormsg);

	$sw = new software();
	if ($neu=='true')
	{
		$sw->insertamum = date('Y-m-d H:i:s');
		$sw->insertvon = $user;
	}
	else
	{
		if(!$sw->load($software_id))
			die('Fehler beim Laden der Software');
	}		
	
	$sw->softwaretyp_kurzbz = $softwaretyp_kurzbz;
	$sw->content_id = $content_id;
	$sw->ansprechperson_uid = $ansprechperson_uid;
	$sw->bezeichnung = $bezeichnung;
	$sw->version = $version;
	$sw->anzahl_lizenzen = $anzahl_lizenzen;
	$sw->lizenzkosten = $lizenzkosten;
	$sw->ablaufdatum = $ablaufdatum;
	$sw->aktiv = $aktiv;
	$sw->anmerkung = $anmerkung;

	if(!$sw->save())
	{
		$errorstr .= $sw->errormsg;
	}
	$reloadstr .= "<script type='text/javascript'>\n";
	$reloadstr .= "	parent.uebersicht_software.location.href='software_uebersicht.php';";
	$reloadstr .= "</script>\n";
	
	$software_id = $sw->software_id;
}

if ($software_id!='')
{
	$sw = new software();
	$sw->load($software_id);
	if ($sw->errormsg!='')
		die($sw->errormsg);
	$software_id = $sw->software_id;
	$softwaretyp_kurzbz = $sw->softwaretyp_kurzbz;
	$content_id = $sw->content_id;
	$ansprechperson_uid = $sw->ansprechperson_uid;
	$bezeichnung = $sw->bezeichnung;
	$version = $sw->version;
	$anzahl_lizenzen = $sw->anzahl_lizenzen;
	$lizenzkosten = number_format($sw->lizenzkosten,2,',','');
	$ablaufdatum = $sw->ablaufdatum;
	$aktiv = $sw->aktiv;
	$anmerkung = $sw->anmerkung;
	
	$neu = 'false';
}

if(isset($_GET['type']) && $_GET['type']=='software_ort')
{
	if(isset($_GET['method']))
	{
		if(!$rechte->isBerechtigt('addon/lehrmittel',null,'suid'))
			die($rechte->errormsg);

		switch($_GET['method'])
		{
			case 'delete':
				//Zuordnung zu einem Raum entfernen
				$sw_ort = new software_ort();
				$sw_ort->delete($_GET['software_ort_id']);
				break;
			case 'deleteAll':
				//Alle Raumzuordnungen entfernen
				$sw_ort = new software_ort();
				$sw_ort->deleteAll($software_id);
				break;
			case 'add':
				//Zuordnung zu einem oder mehreren Raeumen
				foreach ($_POST['ort_kurzbz'] as $selectedOption)
				{
					$sw_ort = new software_ort();
					$sw_ort->software_id = $software_id;
					$sw_ort->ort_kurzbz = $selectedOption;
					$sw_ort->aktiv = true;
					$sw_ort->insertamum = date('Y-m-d H:i:s');;
					$sw_ort->insertvon = $user;
	
					if(!$sw_ort->save())
						$htmlstr.='Fehler beim Speichern '.$sw_ort->errormsg;
				}
				break;
			case 'assignAll':
				//Zuordnung aller Unterrichsraeume zur Software
				$sw_ort = new software_ort();
				$sw_ort->insertamum = date('Y-m-d H:i:s');;
				$sw_ort->insertvon = $user;

				if(!$sw_ort->assignAll($software_id,true,true))
					$htmlstr.='Fehler beim Speichern '.$sw_ort->errormsg;
				break;
			case 'changeAktiv':
				//Aktiv-Attribut aendern
				//Die Aenderungen werden per Ajax Request durchgefuehrt, daher wird nach dem Speichern mittels exit beendet
				$sw_ort = new software_ort();
				if($sw_ort->load($_GET['software_ort_id']))
				{
					$sw_ort->aktiv=($_GET['aktiv']=='true'?false:true);
					$sw_ort->updateamum = date('Y-m-d H:i:s');
					$sw_ort->updatevon = $user;
	
					if($sw_ort->save())
						exit('true');
					else
						exit('Fehler beim Speichern:'.$sw_ort->errormsg);
				}
				else
					exit('Fehler beim Speichern:'.$sw_ort->errormsg);
		}
	}

	$htmlstr.='<h2>Raumzuordnung ID '.$software_id.' ( '.StringCut($bezeichnung,30,false,'...').' )</h2>';

	$sw_ort = new software_ort();
	$sw_ort->getOrteZugeordnet($software_id);

	$htmlstr.='
		<script>
		$(document).ready(function() 
		{ 
			$("#raumtyptable").tablesorter(
			{
				sortList: [[1,0]],
				widgets: ["zebra"]
			}); 
		});
		function changeboolean(software_ort_id, name)
		{
			value=document.getElementById(name+software_ort_id).value;
		
			var dataObj = {};
			dataObj["software_ort_id"]=software_ort_id;
			dataObj[name]=value;
			dataObj["type"]="software_ort";
			dataObj["method"]="changeAktiv";
	
			$.ajax({
				type:"GET",
				url:"software_details.php", 
				data:dataObj,
				success: function(data) 
				{
					if(data=="true")
					{
						//Image und Value aendern
						if(value=="true")
							value="false";
						else
							value="true";
						document.getElementById(name+software_ort_id).value=value;
						document.getElementById(name+"img"+software_ort_id).src="../../../skin/images/"+value+".png";
					}
					else 
						alert("ERROR:"+data)
				},
				error: function() { alert("error"); }
			});
		}
		</script>
		<table class="tablesorter2" id="raumtyptable">
		<thead>
			<th>ID</th>
			<th>Raum</th>
			<th>Aktiv</th>
			<th></th>
		</thead>
		<tbody>';
	$zugeordnete = array();
	foreach($sw_ort->result as $row)
	{
		$zugeordnete[] .= $row->ort_kurzbz;
		$htmlstr.= '
			<tr>
				<td>'.$row->software_ort_id.'</td>
				<td>'.$row->ort_kurzbz.'</td>
				<td>
					<a href="#Aktiv" onclick="changeboolean(\''.$row->software_ort_id.'\',\'aktiv\'); return false">
					<input type="hidden" id="aktiv'.$row->software_ort_id.'" value="'.($row->aktiv==true?'true':'false').'">
					<img id="aktivimg'.$row->software_ort_id.'" alt="Aktiv" title="Aktiv" src="../../../skin/images/'.($row->aktiv==true?'true.png':'false.png').'" style="margin:0;" height="20">
					</a>
				</td>
				<td><a href="software_details.php?type=software_ort&software_id='.$software_id.'&method=delete&software_ort_id='.$row->software_ort_id.'">Entfernen</a></td>
			</tr>';
	}
	$htmlstr.='</tbody></table>
	<form action="software_details.php?type=software_ort&method=add" method="POST">
	<span style="vertical-align: top">Raum:</span>
	<SELECT name="ort_kurzbz[]" multiple="multiple" style="height: 200px">';
	
	$ort = new ort();
	$ort->getActive(true,true);
	foreach($ort->result as $row)
	{
		if (!in_array($row->ort_kurzbz, $zugeordnete))
			$htmlstr.= '<OPTION value="'.$row->ort_kurzbz.'">'.$row->ort_kurzbz.' ('.$row->bezeichnung.')</OPTION>';
	}
	$htmlstr.='</SELECT>
	<input type="hidden" name="software_id" value="'.$software_id.'" />
	<input type="submit" value="Hinzufügen" style="vertical-align: top">
	</form>';
	
	$htmlstr.='<br><hr>
	<a href="software_details.php?type=software_ort&software_id='.$software_id.'&method=deleteAll" onclick="return confirm(\'Wollen Sie alle zugeordneten Räume entfernen?\');">Alle entfernen</a><br>
	<a href="software_details.php?type=software_ort&software_id='.$software_id.'&method=assignAll">Alle Lehrsäle hinzufügen</a>';

}
else
{	
	if(isset($_GET['method']))
	{
		if(!$rechte->isBerechtigt('addon/lehrmittel',null,'suid'))
			die('Sie haben keine Berechtigung fuer diese Seite');

		switch($_GET['method'])
		{
			case 'typ_neu':
				//Neuen Softwaretyp hinzufuegen
				$sw_typ = new software_typ();
				$sw_typ->softwaretyp_kurzbz = $_POST['softwaretyp_kurzbz'];
				$sw_typ->bezeichnung = $_POST['softwaretyp_bezeichnung'];

				if(!$sw_typ->save())
					$htmlstr.='Fehler beim Speichern '.$sw_typ->errormsg;
				break;
		}
	}
	
	if($software_id != '')
		$htmlstr .= '<br><div class="kopf">Software <b>'.$software_id.'</b></div>';
	else
	{
		$htmlstr .='<br><div class="kopf">Neue Software</div>'; 
		$aktiv = true;
	}
	$htmlstr .= '
		<form action="software_details.php" method="POST" name="softwareform" id="softwareform">
			<table class="detail">
				<tr><td colspan="3">&nbsp;</tr>
				<tr>';

	$htmlstr .= '
		<td valign="top">
			<table id="softwaretable">
				<tr>
					<td align="right">Bezeichnung</td>
					<td colspan="3"><input type="text" name="bezeichnung" id="bezeichnung" size="48" maxlength="512" value="'.$bezeichnung.'" ></td>
					<td align="right">Version</td>
					<td><input type="text" name="version" size="15" maxlength="128" value="'.$version.'" ></td>
					<td width="500px"></td>
				</tr>
				<tr>
					<td align="right">Softwaretyp</td>
					<td><SELECT name="softwaretyp_kurzbz">
							<OPTION value="">-- keine Auswahl --</OPTION>';
						$typ = new software_typ();
						if($typ->getAllTypes())
						{
							foreach($typ->result as $row)
							{
								if($row->softwaretyp_kurzbz==$softwaretyp_kurzbz)
									$selected='selected';
								else 
									$selected='';
							
								$htmlstr.='<OPTION value="'.$row->softwaretyp_kurzbz.'" '.$selected.'>'.$row->bezeichnung.'</OPTION>';
							}
						}
	$htmlstr .= '	</SELECT></td>
					<td align="right">Content ID ';
					if($content_id != '')
						$htmlstr .= '<a href="'.APP_ROOT.'cms/admin.php?content_id='.$content_id.'&action=content&sprache='.DEFAULT_LANGUAGE.'&filter='.(defined('SOFTWARE_CONTENT_TEMPLATE')?SOFTWARE_CONTENT_TEMPLATE:$content_id).'" target="blank"><img src="../skin/images/pen.png" height="16px"></a>';
					else 
						$htmlstr .= '<a href="#" onclick="addNewContent(\''.$bezeichnung.'\')"><img src="../skin/images/add.png" height="16px"></a>';
					$htmlstr .= '</td>
					<td><input type="text" name="content_id" id="content_id" size="10" maxlength="8" value="'.$content_id.'" ></td>
					<td align="right">Aktiv</td>
					<td><input type="checkbox" name="aktiv" '.($aktiv?'checked':'').' ></td>
					<td></td>
				</tr>
				<tr>
					<td align="right">Ansprechperson</td>
					<td><input id="username" type="text" name="ansprechperson_uid" size="10" maxlength="50" value="'.$ansprechperson_uid.'" ></td>
					<td align="right">Anz. Lizenzen</td>
					<td><input type="text" name="anzahl_lizenzen" size="10" value="'.$anzahl_lizenzen.'" ></td>
					<td align="right">Lizenzkosten</td>
					<td><input type="text" name="lizenzkosten" size="15" maxlength="20" value="'.$lizenzkosten.'" ></td>
					<td></td>
				</tr>
				<tr>
					<td align="right" valign="top">Ablaufdatum</td>
					<td valign="top"><input class="datepicker_datum" type="text" name="ablaufdatum" size="10" maxlength="10" value="'.$ablaufdatum.'" ></td>
					<td align="right" valign="top">Anmerkung</td>
					<td colspan="4"><textarea name="anmerkung" cols="50" rows="5" >'.$anmerkung.'</textarea></td>
				</tr>
				<tr>
					<td colspan="6" align="right">
						<br>
						<input type="hidden" name="software_id" value="'.$software_id.'" />
						
						<input type="hidden" name="neu" value="'.$neu.'">
						<input type="hidden" name="save" value="Save" />
						<input type="submit" value="Speichern" name="save">
					</td>
				</tr>
			</table>
			<span id="submsg" style="color:red; visibility:hidden;">Datensatz geändert!&nbsp;&nbsp;</span>
		</form>
		<div class="inserterror">'.$errorstr.'</div>';
	
	$htmlstr.='<hr><h3 style="padding-top: 10px;">Neuer Softwaretyp</h3><br>
		<form action="software_details.php?type=software&software_id='.$software_id.'&method=typ_neu" method="POST">
			Kurzbz: <input type="text" name="softwaretyp_kurzbz" size="20" maxlength="32">
			Bezeichnung: <input type="text" name="softwaretyp_bezeichnung" size="50" maxlength="256">
			<br><br>
			<input type="submit" value="Neuen Softwaretyp anlegen">
		</form><br>';
}
?>
<!DOCTYPE HTML>
<html>
<head>
	<title>Software - Details</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" href="../../../skin/vilesci.css" type="text/css">
	<link rel="stylesheet" href="../../../skin/jquery.css" type="text/css">
	<link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css"/>
	<link rel="stylesheet" href="../../../skin/jquery-ui-1.9.2.custom.min.css" type="text/css">
	<script src="../../../include/js/jquery1.9.min.js" type="text/javascript"></script>	

<script type="text/javascript">
function addNewContent(bezeichnung)
{
	if(bezeichnung == '')
	{
		if($( "#bezeichnung" ).val() != '')
			var bez = $( "#bezeichnung" ).val();
		else
			var bez = "Neuer Softwareeintrag";
	}
	else
		var bez = bezeichnung;

	data = {
				NewContent: "NewContent",
				titel: bez,
				templateContent: <?php echo (defined('SOFTWARE_CONTENT_TEMPLATE') && SOFTWARE_CONTENT_TEMPLATE != ''?SOFTWARE_CONTENT_TEMPLATE:0); ?>			
			};

	$.ajax({
		url: "<?php echo APP_ROOT ?>cms/admin.php",
		data: data,
		type: "POST",
		dataType: "json",
		success: function(data) 
		{
			$( "#content_id" ).val( data );
			//document.getElementById("softwareform").submit();
		},
		error: function(data) 
		{
			alert("ERROR:"+data);
		}
	});
}
$(document).ready(function() 
{ 
	$( ".datepicker_datum" ).datepicker({
		 changeMonth: true,
		 changeYear: true, 
		 dateFormat: 'yy-mm-dd'
 	});

	$("#username").autocomplete({
		source: "software_autocomplete.php?work=benutzer",
		minLength:2,
		response: function(event, ui)
		{
			//Value und Label fuer die Anzeige setzen
			for(i in ui.content)
			{
				ui.content[i].value=ui.content[i].uid;
				ui.content[i].label=ui.content[i].titelpre+" "+ui.content[i].nachname+" "+ui.content[i].vorname+" "+ui.content[i].titelpost+" ("+ui.content[i].uid+")";
			}
		},
		select: function(event, ui)
		{
			ui.item.value=ui.item.uid;
		}
	});
});
</script>
<style type="text/css">
#softwaretable td
{
	/*text-align: right;*/
	padding: 3px;
}
table.tablesorter2 {
	font-family:arial;
	/*background-color: white;*/
	margin:10px 0pt 15px;
	font-size: 8pt;
	width: 100%;
	text-align: left;
}
table.tablesorter2 thead tr th, table.tablesorter tfoot tr th {
    background:#DCE4EF;
	border: 1px solid #FFF;
	font-size: 8pt;
	padding: 4px;
}
table.tablesorter2 thead tr .header {
	background-image: url(../../skin/images/bg_sort.gif);
	background-repeat: no-repeat;
	background-position: center left;
	padding-left: 20px; 
	cursor: pointer;
}
table.tablesorter2 tbody td {	
	padding: 1px;
	background-color: #EEEEEE;
	vertical-align: center;
}
table.tablesorter2 tbody tr.odd td {
	background-color:lightgray;
}
table.tablesorter2 thead tr .headerSortUp {
	background-image: url(../../skin/images/asc.gif);
}
table.tablesorter2 thead tr .headerSortDown {
	background-image: url(../../skin/images/desc.gif);
}
table.tablesorter2 thead tr .headerSortDown, table.tablesorter2 thead tr .headerSortUp {
background-color: #8dbdd8;
}
table.tablesorter2 tr:hover td {
background-color: rgb(226, 255, 226) !important;
</style>
</head>
<body>

<?php
	echo $htmlstr;
	echo $reloadstr;
?>

</body>
</html>
