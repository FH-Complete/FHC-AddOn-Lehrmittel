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
$typ = (isset($_GET['softwaretyp'])?$_GET['softwaretyp']:'');
$ort_kurzbz = (isset($_GET['ort_kurzbz'])?$_GET['ort_kurzbz']:'');

if(isset($_POST['countSoftware']))
{
	// Anzahl Softwareeintraege im Raum abfragen
	// Die Abfrage wirde per Ajax Request durchgefuehrt, daher wird mittels exit beendet
	$sw_ort = new software_ort();
	$sw_ort->getSoftwareZugeordnet($_POST['ort_kurzbz'], true);

	if(count($sw_ort->result)>0)
	{
		exit('true');
	}
	else
		exit('false');
}

echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title>'.$p->t("software/softwareInRaum",$ort_kurzbz).'</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	
	<link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css"/>
	<link rel="stylesheet" href="../../../skin/style.css.php" type="text/css">
	<script type="text/javascript" src="../../../include/js/jquery.js"></script> 
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
<h1>'.$p->t("software/softwareuebersichtInRaum", $ort_kurzbz).'</h1>';

echo '<a href="'.APP_ROOT.'cis/private/lvplan/stpl_week.php?type=ort&ort_kurzbz='.$ort_kurzbz.'" target="content">'.$p->t("lvplan/lvPlan").' '.$ort_kurzbz.'</a><br>';
echo '<a href="software_uebersicht.php?" target="content">'.$p->t("software/gesamtuebersicht").'</a><br><br>';

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
<input type="hidden" name="ort_kurzbz" value="'.$ort_kurzbz.'" />
<input type="submit" value="'.$p->t("software/filtern").'" />
</form>';	

echo '	<table class="tablesorter" id="t1">
		<thead>
			<tr>
				<th>'.$p->t("software/bezeichnung").'</th>
				<th>'.$p->t("software/version").'</th>
				<th>'.$p->t("software/softwaretyp").'</th>
				<th>'.$p->t("software/zugeordneteRaeume").'</th>
				<th>'.$p->t("software/details").'</th>
			</tr>
		</thead>
		<tbody>';

$softwareOrt = new software_ort();
$softwareOrt->getSoftwareZugeordnet($ort_kurzbz, true);
$rowcount = 0;
foreach($softwareOrt->result as $swOrt)
{
	$software = new software();
	$software->load($swOrt->software_id);
	if ($typ==$software->softwaretyp_kurzbz || $typ=='')
	{
		$rowcount++;
		echo '	<tr>';
		echo '		<td>'.$software->bezeichnung.'</td>';
		echo '		<td>'.$software->version.'</td>';
		echo '		<td>'.$softwaretypes_arr[$software->softwaretyp_kurzbz].'</td>';
		echo '		<td><a class="roomsToggle" href="#" onclick="return loadOrt(\''.$software->software_id.'\')"><img src="'.APP_ROOT.'skin/images/down_lvplan.png" title="anzeigen" alt="anzeigen" height="9px" border="0"> '.$p->t("software/zugeordneteRaeume").'</a>';
		echo '		<div class="rooms" id="rooms'.$software->software_id.'"></div></td>';
		echo '		<td>'.($software->content_id!=''?'<a href="'.APP_ROOT.'cms/content.php?content_id='.$software->content_id.'" target="blank">'.$p->t("software/details").'</a>':$p->t("software/details")).'</td>';
		echo '	</tr>';
	}
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