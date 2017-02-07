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
require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');

class software extends basis_db
{
	private $new=true;
	public $result = array();

	public $software_id;
	public $softwaretyp_kurzbz;
	public $content_id;
	public $ansprechperson_uid;
	public $bezeichnung;
	public $version;
	public $anzahl_lizenzen;
	public $lizenzkosten;
	public $ablaufdatum;
	public $aktiv=true;
	public $anmerkung;
	public $insertamum;
	public $insertvon;
	public $updateamum;
	public $updatevon;

    /**
	 * Konstruktor
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Laedt einen Softwareeintrag
	 * @param $software_id
	 * @return boolean true wenn ok, false im Fehlerfall
	 */
	public function load($software_id)
	{
		if(!is_numeric($software_id))
		{
			$this->errormsg = 'ID ist ungueltig';
			return false;
		}

		$qry = "SELECT
					*
				FROM
					addon.tbl_software
				WHERE
					software_id=".$this->db_add_param($software_id, FHC_INTEGER);

		if($result = $this->db_query($qry))
		{
			if($row = $this->db_fetch_object($result))
			{
				$this->software_id = $row->software_id;
				$this->softwaretyp_kurzbz = $row->softwaretyp_kurzbz;
				$this->content_id = $row->content_id;
				$this->ansprechperson_uid = $row->ansprechperson_uid;
				$this->bezeichnung = $row->bezeichnung;
				$this->version = $row->version;
				$this->anzahl_lizenzen = $row->anzahl_lizenzen;
				$this->lizenzkosten = $row->lizenzkosten;
				$this->ablaufdatum = $row->ablaufdatum;
				$this->aktiv = $this->db_parse_bool($row->aktiv);
				$this->anmerkung = $row->anmerkung;
				$this->insertamum = $row->insertamum;
				$this->insertvon = $row->insertvon;
				$this->updateamum = $row->updateamum;
				$this->updatevon = $row->updatevon;
				$this->new=false;

				return true;
			}
			else
			{
				$this->errormsg = 'Eintrag wurde nicht gefunden';
				return false;
			}
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
	}

	public function validate()
	{
		/*
		if(mb_strlen($this->ort_kurzbz)>16)
		{
			$this->errormsg = 'Ort_Kurzbz darf nicht laenger als 16 Zeichen sein';
			return false;
		}
		
		if(!is_bool($this->aktiv))
		{
			$this->errormsg='Aktiv ist ungueltig';
			return false;
		}

		if($this->codes_ausgegeben!='')
		{
			if(!is_numeric($this->codes_ausgegeben))
			{
				$this->errormsg = 'Die Anzahl ausgegebener Codes muss eine gültige Zahl sein';
				return false;
			}
		}*/
		return true;
	}

	/**
	 * Speichert einen Softwareeintrag
	 * @return boolean true wenn ok, false im Fehlerfall
	 */
	public function save()
	{
		if(!$this->validate())
			return false;

		if($this->new)
		{
			$qry = 'BEGIN;INSERT INTO addon.tbl_software(softwaretyp_kurzbz,content_id,ansprechperson_uid,bezeichnung,version,anzahl_lizenzen,lizenzkosten,ablaufdatum,aktiv,anmerkung,insertamum,insertvon,updateamum,updatevon) VALUES('.
					$this->db_add_param($this->softwaretyp_kurzbz).','.
					$this->db_add_param($this->content_id, FHC_INTEGER).','.
					$this->db_add_param($this->ansprechperson_uid).','.
					$this->db_add_param($this->bezeichnung).','.
					$this->db_add_param($this->version).','.
					$this->db_add_param($this->anzahl_lizenzen, FHC_INTEGER).','.
					$this->db_add_param($this->lizenzkosten).','.
					$this->db_add_param($this->ablaufdatum).','.
					$this->db_add_param($this->aktiv, FHC_BOOLEAN).','.
					$this->db_add_param($this->anmerkung).','.
					$this->db_add_param($this->insertamum).','.
					$this->db_add_param($this->insertvon).','.
					$this->db_add_param($this->updateamum).','.
					$this->db_add_param($this->updatevon).');';
		}
		else
		{
			$qry = 'UPDATE addon.tbl_software SET '.
					' softwaretyp_kurzbz='.$this->db_add_param($this->softwaretyp_kurzbz).','.
					' content_id='.$this->db_add_param($this->content_id, FHC_INTEGER).','.
					' ansprechperson_uid='.$this->db_add_param($this->ansprechperson_uid).','.
					' bezeichnung='.$this->db_add_param($this->bezeichnung).','.
					' version='.$this->db_add_param($this->version).','.
					' anzahl_lizenzen='.$this->db_add_param($this->anzahl_lizenzen, FHC_INTEGER).','.
					' lizenzkosten='.$this->db_add_param($this->lizenzkosten).','.
					' ablaufdatum='.$this->db_add_param($this->ablaufdatum).','.
					' aktiv='.$this->db_add_param($this->aktiv, FHC_BOOLEAN).','.
					' anmerkung='.$this->db_add_param($this->anmerkung).','.
					' updateamum='.$this->db_add_param($this->updateamum).', '.
					' updatevon='.$this->db_add_param($this->updatevon).' '.
					' WHERE software_id='.$this->db_add_param($this->software_id, FHC_INTEGER);
		}

		if($this->db_query($qry))
		{
			if($this->new)
			{
				//naechste ID aus der Sequence holen
				$qry="SELECT currval('addon.tbl_software_software_id_seq') as id;";
				if($this->db_query($qry))
				{
					if($row = $this->db_fetch_object())
					{
						$this->software_id = $row->id;
						$this->db_query('COMMIT');
						return true;
					}
					else
					{
						$this->db_query('ROLLBACK');
						$this->errormsg = "Fehler beim Auslesen der Sequence";
						return false;
					}
				}
				else
				{
					$this->db_query('ROLLBACK');
					$this->errormsg = 'Fehler beim Auslesen der Sequence';
					return false;
				}
			}
			else
			{
				return true;
			}
			
		}
		else
		{
			$this->errormsg = 'Fehler beim Speichern der Daten';
			return false;
		}
	}
	
	/**
	* Loescht einen Softwareeintrag samt Raumzuordnungen
	* @param $software_id ID der Software
	* @return true wenn ok, false im Fehlerfall
	*/
	public function delete($software_id)
	{
		$qry = "
			DELETE FROM addon.tbl_software_ort WHERE software_id=".$this->db_add_param($software_id, FHC_INTEGER).";
			DELETE FROM addon.tbl_software WHERE software_id=".$this->db_add_param($software_id, FHC_INTEGER);
	
		if($this->db_query($qry))
			return true;
		else
		{
			$this->errormsg = 'Fehler beim Löschen der Software';
			return false;
		}
	}

	/**
	 * Laedt die Software
	 * @param bool $aktiv [optional, default=null] Wenn true, werden nur aktive Eintraege geladen, wenn false, nur inaktive, wenn NULL alle
	 * @param string $softwaretyp_kurzbz [optional, default=null] Softwaretyp, der geladen werden soll
	 * @return bool
	 */
	public function getSoftware($aktiv=null, $softwaretyp_kurzbz='')
	{
		$qry = "SELECT
					*
				FROM
					addon.tbl_software
				WHERE 1=1";
		if(!is_null($aktiv))
			$qry.=" AND aktiv=".$this->db_add_param($aktiv, FHC_BOOLEAN);
		if($softwaretyp_kurzbz!='')
			$qry.=" AND softwaretyp_kurzbz=".$this->db_add_param($softwaretyp_kurzbz, FHC_STRING);

		$qry.=" ORDER BY bezeichnung";

		if($result = $this->db_query($qry))
		{
			while($row = $this->db_fetch_object($result))
			{
				$obj = new software();
				
				$obj->software_id = $row->software_id;
				$obj->softwaretyp_kurzbz = $row->softwaretyp_kurzbz;
				$obj->content_id = $row->content_id;
				$obj->ansprechperson_uid = $row->ansprechperson_uid;
				$obj->bezeichnung = $row->bezeichnung;
				$obj->version = $row->version;
				$obj->anzahl_lizenzen = $row->anzahl_lizenzen;
				$obj->lizenzkosten = $row->lizenzkosten;
				$obj->ablaufdatum = $row->ablaufdatum;
				$obj->aktiv = $this->db_parse_bool($row->aktiv);
				$obj->anmerkung = $row->anmerkung;
				$obj->insertamum = $row->insertamum;
				$obj->insertvon = $row->insertvon;
				$obj->updateamum = $row->updateamum;
				$obj->updatevon = $row->updatevon;
				$obj->new=false;

				$this->result[] = $obj;
			}
			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
	}
	
	/**
	 * Durchsucht alle Softwareeintraege
	 * @param string $bezeichung Bezeichung der Software, nach der gesucht werden soll
	 * @param string $version [optional] Die Version der Software, nach der gesucht werden soll
	 * @param bool $exact [optional, default: false] Wenn true, werden nur exakte Uebereinstimmungen gesucht (= $bezeichnung statt like $bezeichnung) 
	 * @return boolean
	 */
	public function search($bezeichnung, $version=null, $exact=false)
	{
		$qry = "SELECT
					*
				FROM
					addon.tbl_software 
				WHERE ";
		if($exact==true)
		{
			$qry.="(lower(bezeichnung) = lower('".$this->db_escape($bezeichnung)."'))";
			if(!is_null($version) && $version!='')
				$qry.=" AND (lower(version) = lower('".$this->db_escape($version)."'))";
		}
		else
		{
			$qry.="(lower(bezeichnung) like lower('%".$this->db_escape($bezeichnung)."%'))";
			if(!is_null($version) && $version!='')
				$qry.=" AND (lower(version) like lower('%".$this->db_escape($version)."%'))";
		}
			
		$qry.=" ORDER BY bezeichnung,version";
			
			if($result = $this->db_query($qry))
			{
				while($row = $this->db_fetch_object($result))
				{
					$obj = new software();
	
					$obj->software_id = $row->software_id;
					$obj->softwaretyp_kurzbz = $row->softwaretyp_kurzbz;
					$obj->content_id = $row->content_id;
					$obj->ansprechperson_uid = $row->ansprechperson_uid;
					$obj->bezeichnung = $row->bezeichnung;
					$obj->version = $row->version;
					$obj->anzahl_lizenzen = $row->anzahl_lizenzen;
					$obj->lizenzkosten = $row->lizenzkosten;
					$obj->ablaufdatum = $row->ablaufdatum;
					$obj->aktiv = $this->db_parse_bool($row->aktiv);
					$obj->anmerkung = $row->anmerkung;
					$obj->insertamum = $row->insertamum;
					$obj->insertvon = $row->insertvon;
					$obj->updateamum = $row->updateamum;
					$obj->updatevon = $row->updatevon;
					$obj->new=false;
	
					$this->result[] = $obj;
				}
				return true;
			}
			else
			{
				$this->errormsg = 'Fehler beim Laden der Daten';
				return false;
			}
	}
	
	/**
	 * Liefert die Software_IDs, bei der alle Raumzuordnungen inaktiv sind und die deshalb deaktiviert werden koennen
	 */
	public function getDeaktivierbare()
	{
		$qry = "SELECT DISTINCT 
					software_id 
				FROM 
					addon.tbl_software_ort 
				JOIN
					addon.tbl_software USING (software_id) 
				WHERE 
					tbl_software_ort.aktiv=false 
				AND 
					tbl_software.aktiv=true 
				AND 
					software_id NOT IN (SELECT software_id FROM addon.tbl_software_ort WHERE aktiv=true)
				ORDER BY
					software_id";
	
			if($result = $this->db_query($qry))
			{
				while($row = $this->db_fetch_object($result))
				{
					$obj = new software();
	
					$obj->software_id = $row->software_id;
					$obj->new=false;
	
					$this->result[] = $obj;
				}
				return true;
			}
			else
			{
				$this->errormsg = 'Fehler beim Laden der Daten';
				return false;
			}
	}
}
?>