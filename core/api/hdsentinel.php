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
$contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

if (preg_match('/xml/', $contentType)) {
    $start = strpos($input,"<?xml");
    $end = '</Hard_Disk_Sentinel>';
    $endPosition = strpos($input, $end);
    if ($start === false || $endPosition === false) {
        log::add('hdsentinel', 'warning', 'Rapport XML invalide reçu depuis ' . $_SERVER['REMOTE_ADDR']);
        die();
    }

    $input = substr($input, $start);
    $input = substr($input, 0, strpos($input,$end) + strlen($end));

    try {
        $xml_action = new SimpleXMLElement($input);
        $result = json_decode(json_encode($xml_action), true);

        hdsentinel::getApiXmlResult($result, $_SERVER['REMOTE_ADDR']);
    } catch (Exception $e) {
        log::add('hdsentinel', 'warning', 'Erreur de lecture XML : ' . $e->getMessage());
    }
} else if (preg_match('/html/', $contentType)) {
    $start = stripos($input,"<html");
    $end = '</html>';
    $endPosition = stripos($input, $end);
    if ($start === false || $endPosition === false) {
        log::add('hdsentinel', 'warning', 'Rapport HTML invalide reçu depuis ' . $_SERVER['REMOTE_ADDR']);
        die();
    }

    $input = substr($input, $start);
    $result = substr($input, 0, stripos($input,$end) + strlen($end));

    hdsentinel::getApiHtmlResult($result, $_SERVER['REMOTE_ADDR']);
} else {
    log::add('hdsentinel', 'warning', 'Type de contenu non supporté : ' . $contentType);
}

?>
