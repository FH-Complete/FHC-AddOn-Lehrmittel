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

class software_ort extends basis_db
{
	private $new=true;
	public $result = array();

	public $software_ort_id;
	public $software_id;
	public $ort_kurzbz;
	public $aktiv=true;
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
	 * Laedt einen Software-Ort-Eintrag
	 * @param $software_ort_id
	 * @return boolean true wenn ok, false im Fehlerfall
	 */
	public function load($software_ort_id)
	{
		if(!is_numeric($software_ort_id))
		{
			$this->errormsg = 'ID ist ungueltig';
			return false;
		}

		$qry = "SELECT
					*
				FROM
					addon.tbl_software_ort
				WHERE
					software_ort_id=".$this->db_add_param($software_ort_id, FHC_INTEGER);

		if($result = $this->db_query($qry))
		{
			if($row = $this->db_fetch_object($result))
			{
				$this->software_ort_id = $row->software_ort_id;
				$this->software_id = $row->software_id;
				$this->ort_kurzbz = $row->ort_kurzbz;
				$this->aktiv = $this->db_parse_bool($row->aktiv);
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
	 * Speichert einen Software-Ort-Eintrag
	 * @return boolean true wenn ok, false im Fehlerfall
	 */
	public function save()
	{
		if(!$this->validate())
			return false;

		if($this->new)
		{
			$qry = 'BEGIN;INSERT INTO addon.tbl_software_ort(software_id,ort_kurzbz,aktiv,insertamum,insertvon,updateamum,updatevon) VALUES('.
					$this->db_add_param($this->software_id, FHC_INTEGER).','.
					$this->db_add_param($this->ort_kurzbz).','.
					$this->db_add_param($this->aktiv, FHC_BOOLEAN).','.
					$this->db_add_param($this->insertamum).','.
					$this->db_add_param($this->insertvon).','.
					$this->db_add_param($this->updateamum).','.
					$this->db_add_param($this->updatevon).');';
		}
		else
		{
			$qry = 'UPDATE addon.tbl_software_ort SET '.
					' software_id='.$this->db_add_param($this->software_id, FHC_INTEGER).','.
					' ort_kurzbz='.$this->db_add_param($this->ort_kurzbz).','.
					' aktiv='.$this->db_add_param($this->aktiv, FHC_BOOLEAN).','.
					' updateamum='.$this->db_add_param($this->updateamum).', '.
					' updatevon='.$this->db_add_param($this->updatevon).' '.
					' WHERE software_ort_id='.$this->db_add_param($this->software_ort_id, FHC_INTEGER);
		}

		if($this->db_query($qry))
		{
			if($this->new)
			{
				//naechste ID aus der Sequence holen
				$qry="SELECT currval('addon.tbl_software_ort_software_ort_id_seq') as id;";
				if($this->db_query($qry))
				{
					if($row = $this->db_fetch_object())
					{
						$this->software_ort_id = $row->id;
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
	* Loescht einen Software-Ort-Eintrag
	* @param $software_ort_id ID der Software
	* @return true wenn ok, false im Fehlerfall
	*/
	public function delete($software_ort_id)
	{
		$qry = "
			DELETE FROM addon.tbl_software_ort WHERE software_ort_id=".$this->db_add_param($software_ort_id, FHC_INTEGER);
	
		if($this->db_query($qry))
			return true;
		else
		{
			$this->errormsg = 'Fehler beim Löschen der Software-Ort Zuordnung';
			return false;
		}
	}
	
	/**
	 * Loescht alle Software-Ort-Eintraege einer bestimmten software_id
	 * @param $software_id ID der Software
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function deleteAll($software_id)
	{
		$qry = "
			DELETE FROM addon.tbl_software_ort WHERE software_id=".$this->db_add_param($software_id, FHC_INTEGER);
	
		if($this->db_query($qry))
			return true;
		else
		{
			$this->errormsg = 'Fehler beim Löschen der Software-Ort Zuordnungen';
			return false;
		}
	}
	
	/**
	 * Laedt alle Ort-Raum-Zuordnungen
	 * @param $aktiv boolean Wenn true, werden nur aktive Eintraege geladen
	 */
	public function getAll($aktiv=null, $order='software_id, ort_kurzbz')
	{
		$qry = "SELECT
					*
				FROM
					addon.tbl_software_ort WHERE 1=1 ";
		if(!is_null($aktiv))
			$qry.=" AND aktiv=".$this->db_add_param($aktiv, FHC_BOOLEAN);
	
			$qry.=" ORDER BY $order";
	
			if($result = $this->db_query($qry))
			{
				while($row = $this->db_fetch_object($result))
				{
					$obj = new software_ort();
	
					$obj->software_ort_id = $row->software_ort_id;
					$obj->software_id = $row->software_id;
					$obj->ort_kurzbz = $row->ort_kurzbz;
					$obj->aktiv = $this->db_parse_bool($row->aktiv);
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
	 * Laedt alle einer Software zugeordneten Raeume
	 * @param $software_id integer Die ID der Software
	 * @param $aktiv boolean Wenn true, werden nur aktive Eintraege geladen
	 * @param $ort_kurzbz string
	 */
	public function getOrteZugeordnet($software_id, $aktiv=null, $ort_kurzbz=null)
	{
		$qry = "SELECT
					*
				FROM
					addon.tbl_software_ort WHERE software_id=".$this->db_add_param($software_id, FHC_INTEGER);
		if(!is_null($aktiv))
			$qry.=" AND aktiv=".$this->db_add_param($aktiv, FHC_BOOLEAN);
		if(!is_null($ort_kurzbz))
			$qry.=" AND ort_kurzbz=".$this->db_add_param($ort_kurzbz);

		$qry.=" ORDER BY ort_kurzbz";

		if($result = $this->db_query($qry))
		{
			while($row = $this->db_fetch_object($result))
			{
				$obj = new software_ort();
				
				$obj->software_ort_id = $row->software_ort_id;
				$obj->software_id = $row->software_id;
				$obj->ort_kurzbz = $row->ort_kurzbz;
				$obj->aktiv = $this->db_parse_bool($row->aktiv);
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
	 * Laedt die einem Raum zugeordnete Software
	 * @param $ort_kurzbz string Kurzbz des Raums, dessen Softwareeintraege zurueckgegeben werden sollen
	 * @param $aktiv boolean Wenn true, werden nur aktive Eintraege geladen
	 */
	public function getSoftwareZugeordnet($ort_kurzbz, $aktiv=null)
	{
		$qry = "SELECT
					*
				FROM
					addon.tbl_software_ort WHERE ort_kurzbz=".$this->db_add_param($ort_kurzbz);
		if(!is_null($aktiv))
			$qry.=" AND aktiv=".$this->db_add_param($aktiv, FHC_BOOLEAN);
	
			$qry.=" ORDER BY ort_kurzbz";
	
			if($result = $this->db_query($qry))
			{
				while($row = $this->db_fetch_object($result))
				{
					$obj = new software_ort();
	
					$obj->software_ort_id = $row->software_ort_id;
					$obj->software_id = $row->software_id;
					$obj->ort_kurzbz = $row->ort_kurzbz;
					$obj->aktiv = $this->db_parse_bool($row->aktiv);
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
	 * Ordnet einem Softwareeintrag alle Raeume zu, die den Parametern entsprechen (und diesem noch nicht zugeordnet sind)
	 * @return boolean true wenn ok, false im Fehlerfall
	 */
	public function assignAll($software_id, $ort_aktiv=null, $ort_lehre=null)
	{
		if(!$this->validate())
			return false;

		$qry = 'INSERT INTO addon.tbl_software_ort (software_id,ort_kurzbz,aktiv,insertamum,insertvon)
				SELECT '.
				$this->db_add_param($software_id, FHC_INTEGER).',ort_kurzbz,true,'.
				$this->db_add_param($this->insertamum).','.
				$this->db_add_param($this->insertvon).'				
				FROM public.tbl_ort 
				WHERE ort_kurzbz NOT IN (SELECT ort_kurzbz FROM addon.tbl_software_ort WHERE software_id='.$this->db_add_param($software_id, FHC_INTEGER).') ';
				
		if(!is_null($ort_aktiv))			
			$qry .= ' AND tbl_ort.aktiv='.$this->db_add_param($ort_aktiv, FHC_BOOLEAN);
		
		if(!is_null($ort_lehre))			
			$qry .= ' AND tbl_ort.lehre='.$this->db_add_param($ort_lehre, FHC_BOOLEAN);
		
			$qry .= ' LIMIT 10;';

		if($this->db_query($qry))
		{
			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Speichern der Daten';
			return false;
		}
	}
}
?>