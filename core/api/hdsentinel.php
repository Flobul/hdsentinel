<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";
if (!jeedom::apiAccess(init('apikey'), 'hdsentinel')) {
	echo __('Clef API non valide, vous n\'êtes pas autorisé à effectuer cette action (hdsentinel)', __FILE__);
	die();
}

$input = file_get_contents("php://input");
$input = substr($input, strpos($input,'<?xml'));
$input = substr($input, 0, strpos($input,'</Hard_Disk_Sentinel>') + strlen('</Hard_Disk_Sentinel>'));

$xml_action = new SimpleXMLElement($input);
$result = json_decode(json_encode($xml_action), true);
log::add('hdsentinel', 'debug', 'php input : ' . json_encode($result));

hdsentinel::decodeXML($result, $_SERVER['REMOTE_ADDR']);

?>