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

$input = file_get_contents('php://input', false, null, 0, 2097153);
if ($input === false || strlen($input) > 2097152) {
	http_response_code(413);
	exit(__('Rapport trop volumineux', __FILE__));
}
$contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
$remoteAddress = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: 'unknown';

if (preg_match('/xml/', $contentType)) {
    $start = strpos($input,"<?xml");
    $end = '</Hard_Disk_Sentinel>';
    $endPosition = strpos($input, $end);
    if ($start === false || $endPosition === false) {
        log::add('hdsentinel', 'warning', 'Rapport XML invalide reçu depuis ' . $remoteAddress);
        die();
    }

    $input = substr($input, $start);
    $input = substr($input, 0, strpos($input,$end) + strlen($end));

    try {
		libxml_use_internal_errors(true);
        $xml_action = new SimpleXMLElement($input, LIBXML_NONET | LIBXML_NOCDATA);
        $result = json_decode(json_encode($xml_action), true);

        hdsentinel::getApiXmlResult($result, $remoteAddress);
    } catch (Exception $e) {
        log::add('hdsentinel', 'warning', 'Erreur de lecture XML : ' . $e->getMessage());
    }
} else if (preg_match('/html/', $contentType)) {
    $start = stripos($input,"<html");
    $end = '</html>';
    $endPosition = stripos($input, $end);
    if ($start === false || $endPosition === false) {
        log::add('hdsentinel', 'warning', 'Rapport HTML invalide reçu depuis ' . $remoteAddress);
        die();
    }

    $input = substr($input, $start);
    $result = substr($input, 0, stripos($input,$end) + strlen($end));

    hdsentinel::getApiHtmlResult($result, $remoteAddress);
} else {
    log::add('hdsentinel', 'warning', 'Type de contenu non supporté : ' . $contentType);
}

?>
