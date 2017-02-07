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
 * Authors: Manfred Kindl	manfred.kindl@technikum-wien.at>
 */
/**
 * Initialisierung des Addons
 */
require_once('../../../config/cis.config.inc.php');
require_once('../../../include/phrasen.class.php');

$sprache = getSprache();
$p=new phrasen($sprache);

?>
if(typeof addon =='undefined')
	var addon=Array();

addon.push( 
{
	init: function(page, params) 
	{		
		// Diese Funktion wird nach dem Laden der Seite im CIS aufgerufen
		switch(page)
		{
			case 'cis/private/lvplan/stpl_week.php':
				
				// Anzeige der Software im Raum
				AddonLehrmittelSoftwareShowSoftwareLink(params.ort_kurzbz);				
				break;

			default:
				break;
		}
	}
});

/**
 * Link zur Softwaruebersicht anzeigen
 */
function AddonLehrmittelSoftwareShowSoftwareLink(ort_kurzbz)
{
	data = {
				countSoftware: 'countSoftware',
				ort_kurzbz: ort_kurzbz,
			};
	$.ajax({
		data: data,
		type: "POST",
		url: '<?php echo APP_ROOT;?>addons/lehrmittel/cis/software_raum.php',
		success: function(data) 
		{
			if(data=='true')
				$('#software').html(' - <a href="<?php echo APP_ROOT;?>addons/lehrmittel/cis/software_raum.php?ort_kurzbz=' +ort_kurzbz+ '" target="content"><?php echo $p->t('software/softwareAnzeigen');?></a>');
		},
		error: function(data) 
		{
			alert(data);
		}
    });
}