<?php
/* Copyright (C) 2015 fhcomplete.org
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
 * Authors: Manfred Kindl <manfred.kindl@technikum-wien.at>
 */
/**
 * Importiert CSV-Files mit lokal installierter Software
 * 
 * Format:
 * bezeichnung;version
 */
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/globals.inc.php');
require_once('../../../include/mail.class.php');
require_once('../../../include/ort.class.php');
require_once('../../../include/studiensemester.class.php');
require_once('../include/software.class.php');
require_once('../include/software_typ.class.php');
require_once('../include/software_ort.class.php');
require_once('../config.inc.php');


// Wenn das Script nicht ueber Commandline gestartet wird, muss eine
// Authentifizierung stattfinden
if(php_sapi_name() != 'cli')
{
	$user = get_uid();

	$rechte = new benutzerberechtigung();
	$rechte->getBerechtigungen($user);

	if(!$rechte->isBerechtigt('addon/lehrmittel'))
		die($rechte->errormsg);
}
$db = new basis_db();

//if (count(get_included_files()) == 1)
//	die('Diese Datei sollte nicht direkt aufgerufen werden!');

//Wenn das File direkt aufgerufen wird @todo: noch nicht fertig an den automatischen import angepasst.
if(isset($user))
{
	echo "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01//EN'
	        'http://www.w3.org/TR/html4/strict.dtd'>
	<html>
	<head>
		<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
		<link rel='stylesheet' href='../../../skin/fhcomplete.css' type='text/css'>
		<link rel='stylesheet' href='../../../skin/vilesci.css' type='text/css'>
		<title>Addon Lehrmittel Datenbank Check</title>
	</head>
	<body>
	<h1 class='page-header'>Software CSV - Import</h1>
	<span style='color: red; fonst-size 200%; font-weight: bold;'> Achtung, der manuelle CSV-Import ist noch nicht fertig implementiert </span>"; // @todo: span entfernen, wenn fertig implementiert
	
	echo '<form action="csvimport_software.php" method="POST" enctype="multipart/form-data">
	Bitte wählen Sie die CSV Datei für den Import aus:
	<input type="file" name="csvdatei" />
	<input type="hidden" name="start" />';
	
	echo '<input type="submit" value="Importieren" />
	</form>';
	
	if(isset($_POST['start']))
	{
		$anzahl_importiert=0;
		$anzahl_fehler=0;
		$anzahl_hinweis=0;
		$log='';
		
		if (is_uploaded_file($_FILES['csvdatei']['tmp_name']))
		{
			$handle = fopen ($_FILES['csvdatei']['tmp_name'],"r");
			
			//Dateiname parsen um Ort_kurzbz auszulesen. Dateinamen kommen in der Form PC-F42617.csv
			preg_match('/(\d+)/',$_FILES['csvdatei']['name'],$ort,PREG_OFFSET_CAPTURE); //Position der ersten Zahl aus Dateiname auslesen und in $ort schreiben
			$planbezeichnung = substr($_FILES['csvdatei']['name'],$ort[0][1]-1,2).'.'.substr($_FILES['csvdatei']['name'],$ort[0][1]+1,2); //Ermittelte Position aus $ort-1 + erste Nummer (zB F4) + . + Folgende zwei Zahlen = F4.26
	
			$ort_kurzbz_obj = new ort();
			$ort_kurzbz_obj->getOrtByPlanbezeichnung($planbezeichnung);
			
			$ort_kurzbz = $ort_kurzbz_obj->result[0]->ort_kurzbz;
			
			$row_count=0;
			while ( ($data = fgetcsv ($handle, 10000, ";")) !== FALSE )
			{
				$row_count++;
				
				// 1. Row = Ueberschrift -> wegwerfen
				if($row_count==1)
					continue;
	
				// Pruefen ob das CSV korrekte Spaltenanzahl (2) hat
				//if($row==2 && !isset($data[1]))
				//	die('CSV Datei hat falsche Spaltenanzahl -> Abbruch');
	
				// Letzte Zeile enthaelt Gesamtsumme und keine Personalnummer
				// Diese wird uebersprungen
				//if($data[1]=='')
				//	continue;
	
				//bezeichnung;version
	
				$bezeichnung=trim($data[0]);
				$version=trim($data[1]);
				$software_id = '';
				
				//Suche, ob es schon einen Softwareeintrag mit dieser Version und Bezeichnung gibt
				$software = new software();
				$software->search($bezeichnung, $version, true);
				if(count($software->result) == 0)
				{
					$log.= "<br>Importiere $bezeichnung Version $version";
					
					$software->softwaretyp_kurzbz = 'lokal';
					$software->content_id = null;
					$software->ansprechperson_uid = null;
					$software->bezeichnung = $bezeichnung;
					$software->version = $version;
					$software->anzahl_lizenzen = null;
					$software->lizenzkosten = null;
					$software->ablaufdatum = null;
					$software->aktiv = true;
					$software->anmerkung = null;
					$software->insertamum = date('Y-m-d H:i:s');
					$software->insertvon = 'csvimport';
					$software->updateamum = null;
					$software->updatevon = null;
	
					if(!$software->save())
					{
						$anzahl_fehler++;
						$log.="<br><span class='error'>Software-Insert: $software->errormsg</span>";
						
					}
					else
					{
						$anzahl_importiert++;
						$software_id = $software->software_id;
					}
				}
				else
				{
					$software_id = $software->result[0]->software_id;
					$anzahl_hinweis++;
					$log.= "<br><span class='warning'>Die Software $bezeichnung, Version $version ist bereits vorhanden</span>";
				}
				$ort_arr = array();
				empty($ort_arr);
				$software_ort = new software_ort();
				$software_ort->getOrteZugeordnet($software_id);
				foreach ($software_ort->result AS $row)
					$ort_arr[] = $row->ort_kurzbz;
				
				if(!in_array($ort_kurzbz, $ort_arr))
				{
					$software_ort->software_id = $software_id;
					$software_ort->ort_kurzbz = $ort_kurzbz;
					$software_ort->aktiv = true;
					$software_ort->insertamum = date('Y-m-d H:i:s');
					$software_ort->insertvon = 'csvimport';
					$software_ort->updateamum = null;
					$software_ort->updatevon = null;
					
					if(!$software_ort->save())
					{
						$anzahl_fehler++;
						$log.="<br><span class='error'>Software-Ort-Zuteilung: $software_ort->errormsg </span>";
					}
					//else
					//	$anzahl_importiert++;
	
				}
				else
				{
					$anzahl_hinweis++;
					$log.= "<br><span class='warning'>Die Software $bezeichnung, Version $version ist bereits dem Raum $ort_kurzbz zugeteilt</span>";
				}
			}
			fclose ($handle);
	
			echo '<br>Import abgeschlossen';
			echo '<br>Anzahl importierte Softwareeinträge: <span class="ok">'.$anzahl_importiert.'</span>';
			if($anzahl_fehler>0 || $anzahl_hinweis>0)
			{
				echo '<br>Anzahl Fehler: <span class="error">'.$anzahl_fehler.'</span>';
				echo '<br>Anzahl Hinweise: <span class="warning">'.$anzahl_hinweis.'</span>';
			}
	
			echo '<hr>';
			echo $log;
		}
		else
		{
			echo 'File Upload failed';
		}
	}
}
else
{
	// Commandline Parameter parsen bei Aufruf ueber Cronjob
	// zb php korrigiere_verwendung.php --mailto info@fhcomplete.org
	$longopt = array(
	  "mailto:"
	);
	$commandlineparams = getopt('', $longopt);
	if(isset($commandlineparams['mailto']))
		$mailto=$commandlineparams['mailto'];
	elseif(isset($_GET['mailto']))
		$mailto=$_GET['mailto'];
	else
		$mailto='';

	$mailmessage_html='';
	
	$dir = SOFTWARE_CSV_IMPORTPATH;
	$currentFile = glob($dir.'*.csv');
			
	//Array mit allen CSV-Einträgen, um die inaktiven Zuordnungen auslesen zu können
	$software_arr = array();
	
	$anzahl_importiert=0;
	$anzahl_raumzuteilungen=0;
	$anzahl_zuteilungen_deaktiviert=0;
	$anzahl_software_deaktiviert=0;
	$anzahl_fehler=0;
	$anzahl_hinweis=0;
	$log_importiert='';
	$log_raumzuteilungen='';
	$log_zuteilungen_deaktiviert='';
	$log_software_deaktiviert='';
	$log_hinweis='';
	$log_fehler='';

	foreach ($currentFile as $file)
	{
		$handle = fopen($file,"r");
		$filename = basename($file);
		/* Auskommentiert weil sich Dateinomenklatur geändert hat
		//Dateiname parsen um Ort_kurzbz auszulesen. Dateinamen kommen in der Form PC-F42617.csv
		preg_match('/(\d+)/',$filename,$ort,PREG_OFFSET_CAPTURE); //Position der ersten Zahl aus Dateiname auslesen und in $ort schreiben
		
		$planbezeichnung = substr($filename,$ort[0][1]-1,2); //Ermittelte Position aus $ort-1 + erste Nummer = 'F4' 
		$planbezeichnung .= '.'; // + . = 'F4.'
		$planbezeichnung .= substr($filename,$ort[0][1]+1,2); // + Folgende zwei Zahlen = 'F4.26'
		$planbezeichnung .= (ctype_alpha(substr($filename,$ort[0][1]+3,1))==true?substr($filename,$ort[0][1]+3,1):''); // + Wenn 3. Stelle nach $ort ein Buchstabe ist, diesen anfuegen = 'F4.26A'
		*/
		//Dateiname parsen um Ort_kurzbz auszulesen. Dateinamen kommen in der Form 2016-04-08_PC-F426A17.csv	
		$planbezeichnung = substr($filename,14,2); //Position 14 + erste Nummer = 'F4'
		$planbezeichnung .= '.'; // + . = 'F4.'
		$planbezeichnung .= substr($filename,16,2); // + Folgende zwei Zahlen = 'F4.26'
		$planbezeichnung .= (ctype_alpha(substr($filename,18,1))==true?substr($filename,18,1):''); // + Wenn 18. Stelle ein Buchstabe ist, diesen anfuegen = 'F4.26A'
		
		
		$ort_kurzbz_obj = new ort();
		$ort_kurzbz_obj->getOrtByPlanbezeichnung($planbezeichnung);
		if(count($ort_kurzbz_obj->result)>0)
			$ort_kurzbz = $ort_kurzbz_obj->result[0]->ort_kurzbz;
		else 
		{
			$anzahl_fehler++;
			$log_fehler .= "<br>Keine Ort_Kurzbz zu File '$filename' gefunden";
			continue;
		}
			
		$row_count=0;
		while ( ($data = fgetcsv ($handle, 10000, ";")) !== FALSE )
		{
			$row_count++;
			
			// 1. Row = Ueberschrift -> wegwerfen
			if($row_count==1)
				continue;
			
			if($data[0]=='')
				continue;

			// Pruefen ob das CSV korrekte Spaltenanzahl (2) hat
			//if($row==2 && !isset($data[1]))
			//	die('CSV Datei hat falsche Spaltenanzahl -> Abbruch');

			// Letzte Zeile enthaelt Gesamtsumme und keine Personalnummer
			// Diese wird uebersprungen
			//if($data[1]=='')
			//	continue;

			//bezeichnung;version

			$bezeichnung=trim(iconv("ISO-8859-1", "UTF-8", $data[0]));
			$version=trim($data[1]);
			$software_id = '';

			//Suche, ob es schon einen Softwareeintrag mit dieser Version und Bezeichnung gibt
			$software = new software();
			$software->search($bezeichnung, $version, true);
			if(count($software->result) == 0)
			{
				$software->softwaretyp_kurzbz = 'lokal';
				$software->content_id = null;
				$software->ansprechperson_uid = null;
				$software->bezeichnung = $bezeichnung;
				$software->version = $version;
				$software->anzahl_lizenzen = null;
				$software->lizenzkosten = null;
				$software->ablaufdatum = null;
				$software->aktiv = true;
				$software->anmerkung = null;
				$software->insertamum = date('Y-m-d H:i:s');
				$software->insertvon = 'csvimport';
				$software->updateamum = null;
				$software->updatevon = null;

				if(!$software->save())
				{
					$anzahl_fehler++;
					$log_fehler.="<br>Software-Insert: $software->errormsg";
				}
				else
				{
					$anzahl_importiert++;
					$software_id = $software->software_id;
					$log_importiert.= '<br>'.$bezeichnung.', '.($version!=''?'Version '.$version:'Keine Version');
				}
			}
			else
			{
				$software_id = $software->result[0]->software_id;
				$anzahl_hinweis++;
				$log_hinweis.= '<br>Die Software '.$bezeichnung.', '.($version!=''?'Version '.$version:'Keine Version').' ist bereits vorhanden';
			}
			$ort_arr = array();
			empty($ort_arr);
			$software_ort = new software_ort();
			$software_ort->getOrteZugeordnet($software_id);
			foreach ($software_ort->result AS $row)
			{
				$ort_arr[] = $row->ort_kurzbz;
			}
			if(!in_array($ort_kurzbz, $ort_arr))
			{
				$software_ort->software_id = $software_id;
				$software_ort->ort_kurzbz = $ort_kurzbz;
				$software_ort->aktiv = true;
				$software_ort->insertamum = date('Y-m-d H:i:s');
				$software_ort->insertvon = 'csvimport';
				$software_ort->updateamum = null;
				$software_ort->updatevon = null;
					
				if(!$software_ort->save())
				{
					$anzahl_fehler++;
					$log_fehler.="<br>Software-Ort-Zuteilung: $software_ort->errormsg ";
				}
				else
				{
					//Wenn der Softwareeintrag der neuen Zuordnung inaktiv war, aktiviere ihn wieder
					$software = new software();
					$software->load($software_id);
					if($software->aktiv==false)
					{
						$software->aktiv = true;
						$software->updateamum = date('Y-m-d H:i:s');
						$software->updatevon = 'csvimport';
						if(!$software->save())
						{
							$anzahl_fehler++;
							$log_fehler.="<br>Software-Update: $software->errormsg";
						}
					}						
					$software_arr[] = $software_id.'_'.$ort_kurzbz;
					$anzahl_raumzuteilungen++;
					$log_raumzuteilungen.= '<br>'.$bezeichnung.', '.($version!=''?'Version '.$version:'Keine Version').' in Raum <b>'.$ort_kurzbz.'</b>';
				}
			}
			else
			{
				$software_arr[] = $software_id.'_'.$ort_kurzbz;
				$anzahl_hinweis++;
				$log_hinweis.= '<br>Die Software '.$bezeichnung.', '.($version!=''?'Version '.$version:'Keine Version').' ist bereits dem Raum '.$ort_kurzbz.' zugeteilt';
			}
		}
		fclose ($handle);
	}
	//Array neue Software mit Array Bestehende vergleichen und Raumzuteilungen ggf. deaktivieren
	$software_all_arr = array();
	$software_ort_all = new software_ort();
	$software_ort_all->getAll(true);
	foreach ($software_ort_all->result AS $row)
	{
		$software_all_arr[] = $row->software_id.'_'.$row->ort_kurzbz;
	}

	$software_diff = array_diff($software_all_arr,$software_arr);
	foreach($software_diff AS $value)
	{
		$zuteilung_help = explode('_', $value,'2');
		$zuteilung_aktiv = new software_ort();
		$zuteilung_aktiv->getOrteZugeordnet($zuteilung_help[0],true,$zuteilung_help[1]);
		
		foreach($zuteilung_aktiv->result AS $row)
		{
			$deaktivieren = new software_ort();
			$deaktivieren->load($row->software_ort_id);
			
			$deaktivieren->aktiv = false;
			$deaktivieren->updateamum = date('Y-m-d H:i:s');
			$deaktivieren->updatevon = 'csvimport';
				
			if(!$deaktivieren->save())
			{
				$anzahl_fehler++;
				$log_fehler.="<br>Fehler beim deaktivieren: $deaktivieren->errormsg ";
			}
			else
			{
				$anzahl_zuteilungen_deaktiviert++;
				$log_zuteilungen_deaktiviert.= '<br>ID '.$row->software_id.' in Raum '.$row->ort_kurzbz.' deaktiviert';
			}
		}
	}
	
	//Software deaktivieren, bei der alle Raumzuordnungen inaktiv sind
	$software_deakt = new software();
	$software_deakt->getDeaktivierbare();
	foreach($software_deakt->result AS $row)
	{
		$software = new software();
		$software->load($row->software_id);

		$software->aktiv = false;
		$software->updateamum = date('Y-m-d H:i:s');
		$software->updatevon = 'csvimport';
		if(!$software->save())
		{
			$anzahl_fehler++;
			$log_fehler.="<br>Fehler beim deaktivieren der Software: $software->errormsg";
		}
		else
		{
			$anzahl_software_deaktiviert++;
			$log_software_deaktiviert.= '<br>ID '.$row->software_id.' wurde deaktiviert';
		}
	}
	
	if($anzahl_importiert>0 || $anzahl_raumzuteilungen>0 || $anzahl_fehler>0 || $anzahl_hinweis>0)
	{
		$mailmessage_html .= '<br>Anzahl importierte Softwareeinträge: <span class="ok">'.$anzahl_importiert.'</span>';
		$mailmessage_html .= '<br>Anzahl Raumzuteilungen: <span class="ok">'.$anzahl_raumzuteilungen.'</span>';
		//$mailmessage_html .= '<br>Anzahl Hinweise: <span class="warning">'.$anzahl_hinweis.'</span>';
		$mailmessage_html .= '<br>Anzahl Zuteilungen deaktiviert: <span class="warning">'.$anzahl_zuteilungen_deaktiviert.'</span>';
		$mailmessage_html .= '<br>Anzahl Software deaktiviert: <span class="warning">'.$anzahl_software_deaktiviert.'</span>';
		$mailmessage_html .= '<br>Anzahl Fehler: <span class="error">'.$anzahl_fehler.'</span>';
		if($log_importiert!='')
		{
			$mailmessage_html .= '<hr><span class="ok">Folgende Softwareeinträge sind neu:</span><br>';
			$mailmessage_html .= $log_importiert;
		}
		if($log_raumzuteilungen!='')
		{
			$mailmessage_html .= '<hr><span class="ok">Folgende Raumzuteilungen wurden vorgenommen:</span><br>';
			$mailmessage_html .= $log_raumzuteilungen;
		}
		/*if($log_hinweis!='')
		{
			$mailmessage_html .= '<hr>Folgende Hinweise sind aufgetreten:<br>';
			$mailmessage_html .= $log_hinweis;
		}*/
		if($log_zuteilungen_deaktiviert!='')
		{
			$mailmessage_html .= '<hr><span class="warning">Folgende Software-Raum-Zuteilungen wurden deaktiviert:</span><br>';
			$mailmessage_html .= $log_zuteilungen_deaktiviert;
		}
		if($log_software_deaktiviert!='')
		{
			$mailmessage_html .= '<hr><span class="warning">Folgende Software wurde deaktiviert, da alle Zuordnungen inaktiv sind:</span><br>';
			$mailmessage_html .= $log_software_deaktiviert;
		}
		if($log_fehler!='')
		{
			$mailmessage_html .= '<hr><span class="error">Folgende Fehler sind aufgetreten:</span><br>';
			$mailmessage_html .= $log_fehler;
		}
	}
	echo $mailmessage_html;
	if($mailto!='' && $mailmessage_html!='')
	{
		$mailmessage='
			<style >
			.error
			{
				color: red;
			}
			.ok
			{
				color: green;
			}
			.warning
			{
				color:  #ffa500;;
			}
			</style>';
		$mailmessage .= "Dies ist ein automatisches Mail.<br>Folgende Änderungen an den Software-Einträgen wurden vorgenommen:<br>".$mailmessage_html;
		$mailmessage = wordwrap($mailmessage,70); //Bricht den Code um, da es sonst zu Anzeigefehlern im Mail kommen kann
		$mail = new mail($mailto, 'no-reply@'.DOMAIN,'Import-Bericht Software','Bitte sehen Sie sich die Nachricht in HTML an.');
		$mail->setHTMLContent($mailmessage);
		if(!$mail->send())
			die('Fehler beim Senden des Mails!');
	}
}

?>
