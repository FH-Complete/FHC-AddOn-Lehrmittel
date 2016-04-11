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

class software_typ extends basis_db
{
	private $new=true;
	public $result = array();

	public $softwaretyp_kurzbz;
	public $bezeichnung;

    /**
	 * Konstruktor
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Laedt einen Softwaretyp
	 * @param $software_id
	 * @return boolean true wenn ok, false im Fehlerfall
	 */
	public function load($softwaretyp_kurzbz)
	{
		$qry = "SELECT
					*
				FROM
					addon.tbl_software_typ
				WHERE
					softwaretyp_kurzbz=".$this->db_add_param($softwaretyp_kurzbz, FHC_STRING);

		if($result = $this->db_query($qry))
		{
			if($row = $this->db_fetch_object($result))
			{
				$this->softwaretyp_kurzbz = $row->softwaretyp_kurzbz;
				$this->bezeichnung = $row->bezeichnung;
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
			$qry = 'INSERT INTO addon.tbl_software_typ(softwaretyp_kurzbz,bezeichnung) VALUES('.
					$this->db_add_param($this->softwaretyp_kurzbz).','.
					$this->db_add_param($this->bezeichnung).');';
		}
		else
		{
			$qry = 'UPDATE addon.tbl_software_typ SET '.
					' bezeichnung='.$this->db_add_param($this->bezeichnung).' '.
					' WHERE softwaretyp_kurzbz='.$this->db_add_param($this->softwaretyp_kurzbz, FHC_INTEGER);
		}

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

	/**
	 * Laedt alle Softwaretypen
	 */
	public function getAllTypes()
	{
		$qry = "SELECT
					*
				FROM
					addon.tbl_software_typ ";
		
		$qry.=" ORDER BY bezeichnung";

		if($result = $this->db_query($qry))
		{
			while($row = $this->db_fetch_object($result))
			{
				$obj = new software_typ();
				
				$obj->softwaretyp_kurzbz = $row->softwaretyp_kurzbz;
				$obj->bezeichnung = $row->bezeichnung;
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