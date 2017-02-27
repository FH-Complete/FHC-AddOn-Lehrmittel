<?php
/* Copyright (C) 2013 FH Technikum-Wien
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
 */
/**
 * FH-Complete Addon Lehrmittel Datenbank Check
 *
 * Prueft und aktualisiert die Datenbank
 */
require_once("../../config/system.config.inc.php");
require_once("../../include/basis_db.class.php");
require_once("../../include/functions.inc.php");
require_once("../../include/benutzerberechtigung.class.php");

echo "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01//EN'
        'http://www.w3.org/TR/html4/strict.dtd'>
<html>
<head>
	<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
	<link rel='stylesheet' href='../../skin/fhcomplete.css' type='text/css'>
	<link rel='stylesheet' href='../../skin/vilesci.css' type='text/css'>
	<title>Addon Lehrmittel Datenbank Check</title>
</head>
<body>
<h1>Addon Datenbank Check</h1>";

echo '<form action="dbcheck.php" name="file" method="GET">';
echo '<input type="hidden" name="start"><br/>';
echo '<input type="submit" value="Datenbankupdate starten">';
echo '</form>';

if (!isset($_GET['start']))
	exit;

// Datenbank Verbindung
$db = new basis_db();

$uid = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);

if(!$rechte->isBerechtigt("basis/addon", null, 'suid'))
{
	exit("Sie haben keine Berechtigung für die Verwaltung von Addons");
}

echo "<h2>Aktualisierung der Datenbank</h2>";

// Code fuer die Datenbankanpassungen

//Neue Berechtigung für das Addon hinzufügen
if($result = $db->db_query("SELECT * FROM system.tbl_berechtigung WHERE berechtigung_kurzbz='addon/lehrmittel'"))
{
	if($db->db_num_rows($result)==0)
	{
		$qry = "INSERT INTO system.tbl_berechtigung(berechtigung_kurzbz, beschreibung)
				VALUES('addon/lehrmittel','AddOn Lehrmittel verwalten');";

		if(!$db->db_query($qry))
			echo '<strong>Berechtigung: '.$db->db_last_error().'</strong><br>';
			else
				echo 'Neue Berechtigung addon/lehrmittel hinzugefuegt!<br>';
	}
}

// Pruefung, ob Schema addon vorhanden ist
if($result = $db->db_query("SELECT schema_name FROM information_schema.schemata WHERE schema_name = 'addon'"))
{
	if($db->db_num_rows($result)==0)
	{
		$qry = "CREATE SCHEMA addon;
				GRANT USAGE ON SCHEMA addon TO vilesci;
				GRANT USAGE ON SCHEMA addon TO web;
				";

		if(!$db->db_query($qry))
			echo "<strong>Schema addon: ".$db->db_last_error()."</strong><br>";
			else
				echo "<br>Neues Schema addon hinzugefügt";
	}
}
// Anlegen der Tabelle tbl_software_typ
if(!$result = @$db->db_query('SELECT 1 FROM addon.tbl_software_typ'))
{

	$qry = "CREATE TABLE addon.tbl_software_typ
			(
				softwaretyp_kurzbz varchar(32),
				bezeichnung varchar(256)
			);

			ALTER TABLE addon.tbl_software_typ ADD CONSTRAINT pk_softwaretyp_kurzbz PRIMARY KEY (softwaretyp_kurzbz);

			COMMENT ON TABLE addon.tbl_software_typ IS 'Hier wird definiert, welche Typen von Software es gibt';

			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_software_typ TO vilesci;
			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_software_typ TO web;
			";

	if(!$db->db_query($qry))
		echo "<strong>addon.tbl_software_typ: ".$db->db_last_error()."</strong><br>";
	else
		echo " addon.tbl_software_typ: Tabelle addon.tbl_software_typ hinzugefuegt!<br>";

}

// Anlegen der Tabelle tbl_software
if(!$result = @$db->db_query('SELECT 1 FROM addon.tbl_software'))
{

	$qry = "CREATE TABLE addon.tbl_software
			(
				software_id integer NOT NULL,
				softwaretyp_kurzbz varchar(32),
				content_id integer,
				ansprechperson_uid varchar(32),
				bezeichnung varchar(512),
				version varchar(128),
				anzahl_lizenzen integer,
				lizenzkosten numeric(30,6),
				ablaufdatum date,
				aktiv boolean NOT NULL,
				anmerkung text,
				insertamum timestamp,
				insertvon varchar(32),
				updateamum timestamp,
				updatevon varchar(32)
			);

			CREATE SEQUENCE addon.tbl_software_software_id_seq
			INCREMENT BY 1
			NO MAXVALUE
			NO MINVALUE
			CACHE 1;

			ALTER TABLE addon.tbl_software ADD CONSTRAINT pk_software_id PRIMARY KEY (software_id);
			ALTER TABLE addon.tbl_software ALTER COLUMN software_id SET DEFAULT nextval('addon.tbl_software_software_id_seq');
			ALTER TABLE addon.tbl_software ADD CONSTRAINT uk_software_id UNIQUE (software_id);
			ALTER TABLE addon.tbl_software ADD CONSTRAINT fk_softwaretyp_kurzbz FOREIGN KEY (softwaretyp_kurzbz) REFERENCES addon.tbl_software_typ(softwaretyp_kurzbz) ON DELETE RESTRICT ON UPDATE CASCADE;
			ALTER TABLE addon.tbl_software ADD CONSTRAINT fk_content_id FOREIGN KEY (content_id) REFERENCES campus.tbl_content(content_id) ON DELETE SET NULL ON UPDATE CASCADE;
			ALTER TABLE addon.tbl_software ADD CONSTRAINT fk_ansprechperson_uid FOREIGN KEY (ansprechperson_uid) REFERENCES public.tbl_benutzer(uid) ON DELETE RESTRICT ON UPDATE CASCADE;

			GRANT SELECT, UPDATE ON addon.tbl_software_software_id_seq TO vilesci;
			GRANT SELECT, UPDATE ON addon.tbl_software_software_id_seq TO web;

			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_software TO vilesci;
			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_software TO web;
			";

	if(!$db->db_query($qry))
		echo "<strong>addon.tbl_software: ".$db->db_last_error()."</strong><br>";
		else
			echo " addon.tbl_software: Tabelle addon.tbl_software hinzugefuegt!<br>";

}

// Anlegen der Tabelle tbl_software_ort
if(!$result = @$db->db_query('SELECT 1 FROM addon.tbl_software_ort'))
{

	$qry = "CREATE TABLE addon.tbl_software_ort
			(
				software_ort_id integer NOT NULL,
				software_id integer NOT NULL,
				ort_kurzbz varchar(16) NOT NULL,
				aktiv boolean NOT NULL,
				insertamum timestamp,
				insertvon varchar(32),
				updateamum timestamp,
				updatevon varchar(32)
			);

			CREATE SEQUENCE addon.tbl_software_ort_software_ort_id_seq
			INCREMENT BY 1
			NO MAXVALUE
			NO MINVALUE
			CACHE 1;

			ALTER TABLE addon.tbl_software_ort ADD CONSTRAINT pk_software_ort_id PRIMARY KEY (software_ort_id);
			ALTER TABLE addon.tbl_software_ort ALTER COLUMN software_ort_id SET DEFAULT nextval('addon.tbl_software_ort_software_ort_id_seq');
			ALTER TABLE addon.tbl_software_ort ADD CONSTRAINT fk_software_id FOREIGN KEY (software_id) REFERENCES addon.tbl_software(software_id) ON DELETE RESTRICT ON UPDATE CASCADE;
			ALTER TABLE addon.tbl_software_ort ADD CONSTRAINT fk_ort_kurzbz FOREIGN KEY (ort_kurzbz) REFERENCES public.tbl_ort(ort_kurzbz) ON DELETE RESTRICT ON UPDATE CASCADE;

			GRANT SELECT, UPDATE ON addon.tbl_software_ort_software_ort_id_seq TO vilesci;
			GRANT SELECT, UPDATE ON addon.tbl_software_ort_software_ort_id_seq TO web;

			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_software_ort TO vilesci;
			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_software_ort TO web;
			";

	if(!$db->db_query($qry))
		echo "<strong>addon.tbl_software_ort: ".$db->db_last_error()."</strong><br>";
		else
			echo " addon.tbl_software_ort: Tabelle addon.tbl_software_ort hinzugefuegt!<br>";

}


echo "<br>Aktualisierung abgeschlossen<br><br>";
echo "<h2>Gegenprüfung</h2>";


// Liste der verwendeten Tabellen / Spalten des Addons
$tabellen=array(
	'addon.tbl_software_typ'  => array('softwaretyp_kurzbz','bezeichnung'),
	'addon.tbl_software'  => array('softwaretyp_kurzbz','content_id','ansprechperson_uid','bezeichnung','version','anzahl_lizenzen','lizenzkosten','ablaufdatum','aktiv','anmerkung','insertamum','insertvon','updateamum','updatevon'),
	'addon.tbl_software_ort'  => array('software_ort_id','software_id','ort_kurzbz','aktiv','insertamum','insertvon','updateamum','updatevon'),
);


$tabs=array_keys($tabellen);
$i=0;
foreach ($tabellen AS $attribute)
{
	$sql_attr="";
	foreach($attribute AS $attr)
		$sql_attr.=$attr.",";
	$sql_attr=substr($sql_attr, 0, -1);

	if (!@$db->db_query("SELECT ".$sql_attr." FROM ".$tabs[$i]." LIMIT 1;"))
		echo "<BR><strong>".$tabs[$i].": ".$db->db_last_error()." </strong><BR>";
	else
		echo $tabs[$i].": OK <br>";
	flush();
	$i++;
}
?>
