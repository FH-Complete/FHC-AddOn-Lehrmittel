<?php
/* Copyright (C) 2006 Technikum-Wien
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
 * Authors: Manfred Kindl 	< manfred.kindl@technikum-wien.at >
 */
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../include/software.class.php');
require_once('../include/software_typ.class.php');
require_once('../config.inc.php');

//require_once('../../../include/ort.class.php');

if (!$db = new basis_db())
	die('Es konnte keine Verbindung zum Server aufgebaut werden.');

$user = get_uid();

$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($user);

if(!$rechte->isBerechtigt('addon/lehrmittel', null, 's'))
	die($rechte->errormsg);

// Speichern der Daten
if(isset($_POST['software_id']))
{
	// Die Aenderungen werden per Ajax Request durchgefuehrt,
	// daher wird nach dem Speichern mittels exit beendet

	//Aktiv Feld setzen
	if(isset($_POST['aktiv']))
	{
		if(!$rechte->isBerechtigt('addon/lehrmittel', null, 'sui'))
			die($rechte->errormsg);
		$sw_obj = new software();
		if($sw_obj->load($_POST['software_id']))
		{
			$sw_obj->aktiv=($_POST['aktiv']=='true'?false:true);
			$sw_obj->updateamum = date('Y-m-d H:i:s');
			$sw_obj->updatevon = $user;
			if($sw_obj->save())
				exit('true');
			else
				exit('Fehler beim Speichern:'.$sw_obj->errormsg);
		}
		else
			exit('Fehler beim Speichern der Software:'.$sw_obj->errormsg);
	}
}

// Loeschen eines Softwareeintrags
if(isset($_GET['delete']) && isset($_GET['software_id']))
{

	if(!$rechte->isBerechtigt('addon/lehrmittel', null, 'suid'))
		die($rechte->errormsg);

	$sw_obj = new software();
	if(!$sw_obj->delete($_GET['software_id']))
		$sw_obj->errormsg;
}

$sw = new software();
if(!$sw->getSoftware())
	die($sw->errormsg);

$softwaretypes = new software_typ();
$softwaretypes->getAllTypes();
$softwaretypes_arr = array();
foreach ($softwaretypes->result AS $row)
	$softwaretypes_arr[$row->softwaretyp_kurzbz] = $row->bezeichnung;

$htmlstr = "
<table class='tablesorter' id='t1'>
<thead>
	<tr>
		<th>ID</th>
		<th>Bezeichnung</th>
		<th>Version</th>
		<th>Softwaretyp</th>
		<th>Content_ID</th>
		<th>Ansprechperson</th>
		<th>Anzahl Lizenzen</th>
		<th>Lizenzkosten</th>
		<th>Ablaufdatum</th>
		<th>Aktiv</th>
		<th>Anmerkung</th>
		<th></th>
		<th></th>
		<th></th>
	</tr>
</thead>
<tbody>\n";

foreach ($sw->result as $software)
{
	$htmlstr .= "	<tr>\n";
	$htmlstr .= "		<td>".$software->software_id."</td>\n";
	$htmlstr .= "		<td><a href='software_details.php?type=software&software_id=".$software->software_id."' target='detail_software'>".$software->bezeichnung."</a></td>\n";
	$htmlstr .= "		<td>".$software->version."</td>\n";
	$htmlstr .= "		<td>" . (isset($softwaretypes_arr[$software->softwaretyp_kurzbz]) ? $softwaretypes_arr[$software->softwaretyp_kurzbz] : '') . "</td>\n";
	$htmlstr .= "		<td><a href='".APP_ROOT."cms/admin.php?content_id=".$software->content_id."&action=content&sprache=".DEFAULT_LANGUAGE."&filter=".(defined('SOFTWARE_CONTENT_TEMPLATE')?SOFTWARE_CONTENT_TEMPLATE:$software->content_id)."' target='blank'>".$software->content_id."</a></td>\n";
	$htmlstr .= "		<td>".$software->ansprechperson_uid."</td>\n";
	$htmlstr .= "		<td>".$software->anzahl_lizenzen."</td>\n";
	$htmlstr .= "		<td>€ ".number_format($software->lizenzkosten,2,',','.')."</td>\n";
	$htmlstr .= "		<td>".$software->ablaufdatum."</td>\n";

	// Aktiv boolean setzen

	$htmlstr .= "		<td align='center'><a href='#Aktiv' onclick='changeboolean(\"".$software->software_id."\",\"aktiv\"); return false'>";
	$htmlstr .= "		<input type='hidden' id='aktiv".$software->software_id."' value='".($software->aktiv==true?"true":"false")."'>";
	$htmlstr .= "		<img id='aktivimg".$software->software_id."' alt='Aktiv' title='Aktiv' src='../skin/images/".($software->aktiv==true?"true.png":"false.png")."' style='margin:0;' height='20'>";
	$htmlstr .= "		</a></td>";

	$htmlstr .= "		<td>".$software->anmerkung."</td>\n";
	$htmlstr .= '		<td><a href="software_details.php?type=software&software_id='.$software->software_id.'" target="detail_software" title="Bearbeiten" ><img src="../skin/images/pen.png" height="22px"/></a></td>';
	$htmlstr .= '		<td><a href="software_details.php?type=software_ort&software_id='.$software->software_id.'" target="detail_software" title="Raum zuteilen" ><img src="../skin/images/computer.png" height="22px"/></a></td>';
	$htmlstr .= '		<td><a href="software_uebersicht.php?delete&software_id='.$software->software_id.'"><img src="../skin/images/trash.png" height="22px" onclick="return confirm(\'Wollen Sie den Softwareeintrag mit der ID '.$software->software_id.' inklusive allen Raumzuordnungen wirklich löschen?\')" /></a></td>';

	$htmlstr .= "	</tr>\n";
}
$htmlstr .= "</tbody></table>\n";


?>
<html>
<head>
	<title>R&auml;ume &Uuml;bersicht</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" href="../../../skin/vilesci.css" type="text/css">

	<script type="text/javascript" src="../../../vendor/jquery/jquery1/jquery-1.12.4.min.js"></script>
	<script type="text/javascript" src="../../../vendor/christianbach/tablesorter/jquery.tablesorter.min.js"></script>
	<script type="text/javascript" src="../../../vendor/components/jqueryui/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../../../include/js/jquery.ui.datepicker.translation.js"></script>
	<script type="text/javascript" src="../../../vendor/jquery/sizzle/sizzle.js"></script>

	<link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css"/>
	<style>
	table.tablesorter tbody td
	{
		margin: 0;
		padding: 0;
		vertical-align: middle;
	}
	</style>
	<script type="text/javascript">
	$(document).ready(function()
	{
		$("#t1").tablesorter(
		{
			sortList: [[1,0]],
			widgets: ["zebra"],
			headers: {11: {sorter: false}, 12: {sorter: false}, 13: {sorter: false}}
		});
	});

	function changeboolean(software_id, name)
	{
		value=document.getElementById(name+software_id).value;

		var dataObj = {};
		dataObj["software_id"]=software_id;
		dataObj[name]=value;

		$.ajax({
			type:"POST",
			url:"software_uebersicht.php",
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
					document.getElementById(name+software_id).value=value;
					document.getElementById(name+"img"+software_id).src="../skin/images/"+value+".png";
				}
				else
					alert("ERROR:"+data)
			},
			error: function() { alert("error"); }
		});
	}

	</script>
</head>
<body>
<h2>Software Übersicht</h2>
<a href="csvimport_software.php" target="_blank">Software CSV-Import</a><br>
<a href="software_details.php" target="detail_software">Neue Software</a>

<?php
	echo $htmlstr;
?>

</body>
</html>
