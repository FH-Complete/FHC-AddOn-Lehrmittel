<?php
/* Copyright (C) 2012 FH Technikum-Wien
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
 * Authors: Manfred Kindl	< manfred.kindl@technikum-wien.at >
 */
require_once('../../../config/cis.config.inc.php');
require_once('../../../include/datum.class.php');
require_once('../../../include/phrasen.class.php');
require_once('../include/software.class.php');
require_once('../include/software_typ.class.php');
require_once('../include/software_ort.class.php');
require_once('../config.inc.php');

$user = get_uid();
$sprache = getSprache();
$p=new phrasen($sprache);

$datum_obj = new datum();

if(isset($_POST['software_id']))
{
	// Zugewiesene Raeume abfragen
	// Die Abfrage wirde per Ajax Request durchgefuehrt, daher wird mittels exit beendet
	$sw_ort = new software_ort();
	$sw_ort_arr = array();

	if($sw_ort->getOrteZugeordnet($_POST['software_id'], true))
	{
		foreach ($sw_ort->result as $row)
		{
			$sw_ort_arr[] = $row->ort_kurzbz;
		}

		exit(json_encode($sw_ort_arr));
	}
	else
		exit('Fehler beim Speichern:'.$sw_ort->errormsg);
}

echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title>'.$p->t("software/softwareUebersicht").'</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

	<link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css"/>
	<link rel="stylesheet" href="../../../skin/style.css.php" type="text/css">

	<script type="text/javascript" src="../../../vendor/jquery/jqueryV1/jquery-1.12.4.min.js"></script>
	<script type="text/javascript" src="../../../vendor/christianbach/tablesorter/jquery.tablesorter.min.js"></script>
	<script type="text/javascript" src="../../../vendor/components/jqueryui/jquery-ui.min.js"></script>
	<script type="text/javascript" src="../../../include/js/jquery.ui.datepicker.translation.js"></script>
	<script type="text/javascript" src="../../../vendor/jquery/sizzle/sizzle.js"></script>

	<script type="text/javascript">

	$(document).ready(function()
		{
		    $("#t1").tablesorter(
			{
				sortList: [[0,0],[1,0]],
				widgets: [\'zebra\']
			});

			$(".rooms").hide();
			$( "a.roomsToggle" ).click(function() {
			    $(this).next().slideToggle(200);
			});
		}
	);

	function ContentPopUp (Adresse)
	{
	  Content = window.open(Adresse, "Content", "width=800,height=500,scrollbars=yes");
	  Content.focus();
	}

	function loadOrt(software_id)
	{
		// value=document.getElementById(name+software_ort_id).value;
		var divelement = "rooms"+software_id;
		var dataObj = {};
		dataObj["software_id"]=software_id;

		$.ajax({
			type: "POST",
			url: "software_uebersicht.php",
			data: dataObj,
			dataType: "json",
			success: function(data)
			{
				//var htmlOutput = $.parseJSON(data);
				listData = "<ul style=\"margin-top: 0px; margin-bottom: 0px;\">";
				$.each(data, function(i, item)
				{
					listData += "<li><a href=\"software_raum.php?ort_kurzbz=" + item + "\" target=\"content\">" + item + "</a></li>";
				});
				listData += "</ul>";
				$("#"+divelement).html(listData);

				// $("#"+divelement).html(data);

				//var data = $.parseJSON($("#json").html());
				//$.each(data.data, function(index, value) { $(".data").append(value.a+"<br />"); } );
			},
			error: function(data) { alert("ERROR:"+data); }
		});
		return false;
	}
	</script>
</head>
<body>
<h1>'.$p->t("software/uebersichtUeberSoftware").'</h1>';

$typ = (isset($_GET['softwaretyp'])?$_GET['softwaretyp']:'');

echo '<form action="'.$_SERVER['PHP_SELF'].'" method="GET">';
echo $p->t("software/softwaretyp").': ';
echo '<SELECT name="softwaretyp">
<OPTION value="">-- '.$p->t("global/alle").' --</OPTION>';

$softwaretyp = new software_typ();
$softwaretyp->getAllTypes();
$softwaretypes_arr = array();
foreach($softwaretyp->result as $row)
{
	$softwaretypes_arr[$row->softwaretyp_kurzbz] = $row->bezeichnung;
	if($row->softwaretyp_kurzbz==$typ)
		$selected='selected';
	else
		$selected='';

	echo '<OPTION value="'.$row->softwaretyp_kurzbz.'" '.$selected.'>'.$row->bezeichnung.'</OPTION>';
}
echo '</SELECT>
<input type="submit" value="'.$p->t("software/filtern").'" />
</form>';

echo '	<table class="tablesorter" id="t1">
		<thead>
			<tr>
				<th>'.$p->t("software/bezeichnung").'</th>
				<th>'.$p->t("software/version").'</th>
				<th>'.$p->t("software/softwaretyp").'</th>
				<!--<th>'.$p->t("software/ansprechperson").'</th>
				<th>'.$p->t("software/anzahlLizenzen").'</th>
				<th>'.$p->t("software/lizenzkosten").'</th>
				<th>'.$p->t("software/ablaufdatum").'</th>-->
				<th>'.$p->t("software/zugeordneteRaeume").'</th>
				<!--<th>'.$p->t("software/details").'</th>-->
			</tr>
		</thead>
		<tbody>';

$software = new software();
$software->getSoftware(true, $typ);
$rowcount = 0;

foreach($software->result as $row)
{
	$rowcount++;
	echo '	<tr>';
	echo '		<td>'.$row->bezeichnung.'</td>';
	echo '		<td>'.$row->version.'</td>';
	echo '		<td>'.(isset($softwaretypes_arr[$row->softwaretyp_kurzbz])?$softwaretypes_arr[$row->softwaretyp_kurzbz]:'').'</td>';
	//echo '		<td>'.$row->ansprechperson_uid.'</td>';
	//echo '		<td>'.$row->anzahl_lizenzen.'</td>';
	//echo '		<td>€ '.number_format($row->lizenzkosten,2,",",".").'</td>';
	//echo '		<td>'.$row->ablaufdatum.'</td>';
	echo '		<td><a class="roomsToggle" href="#" onclick="return loadOrt(\''.$row->software_id.'\')"><img src="'.APP_ROOT.'skin/images/down_lvplan.png" title="anzeigen" alt="anzeigen" height="9px" border="0"> '.$p->t("software/zugeordneteRaeume").'</a>';
	echo '		<div class="rooms" id="rooms'.$row->software_id.'"></div></td>';
// 	echo '		<td><a href="'.APP_ROOT.'cms/content.php?content_id='.$row->content_id.'" target="blank">'.$p->t("software/details").'</a></td>';
	echo '	</tr>';
}
echo $rowcount.' '.$p->t("software/ergebnisse");
if ($rowcount==0)
{
	echo '<tr><td colspan="5">'.$p->t("software/keineSoftwareVomTypVorhanden", $softwaretypes_arr[$typ]).'</td></tr>';
}
echo '</tbody>
</table>
</body>
</html>';
?>
