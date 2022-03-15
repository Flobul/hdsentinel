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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function hdsentinel_install() {

    message::add('hdsentinel', 'Cette mise à jour peut durer un certain temps : elle va stopper puis supprimer les cron actuels sur les appareils distants, puis renvoyer les scripts, installer les paquets le cron et le lancer.');
    foreach (eqLogic::byType('hdsentinel') as $eqLogic) {
        $result = array();
        //$result[] = $eqLogic->stopCron();
        log::add('hdsentinel', 'info', __('=> Début mise à jour des scripts', __FILE__) . $eqLogic->getName());
        $result['stop_remove'] = $eqLogic->removeCron();
        $result['send'] = $eqLogic->sendFile();
        $result['install'] = $eqLogic->installDependancy();
        $result['launch'] = $eqLogic->launchCron();
        $result['status'] = $eqLogic->statusCron();
        log::add('hdsentinel', 'info', __('=> Fin mise à jour des scripts', __FILE__) . $eqLogic->getName() . ' - resultat : ' . json_encode($result));
    }
}

function hdsentinel_update() {

    message::add('hdsentinel', 'Cette mise à jour peut durer un certain temps : elle va stopper puis supprimer les cron actuels sur les appareils distants, puis renvoyer les scripts, installer les paquets le cron et le lancer.');
    foreach (eqLogic::byType('hdsentinel') as $eqLogic) {
        $result = array();
        //$result[] = $eqLogic->stopCron();
        log::add('hdsentinel', 'info', __('=> Début mise à jour des scripts ', __FILE__) . $eqLogic->getName());
        $result['stop_remove'] = $eqLogic->removeCron();
        $result['send'] = $eqLogic->sendFile();
        $result['install'] = $eqLogic->installDependancy();
        $result['launch'] = $eqLogic->launchCron();
        $result['status'] = $eqLogic->statusCron();
        log::add('hdsentinel', 'info', __('=> Fin mise à jour des scripts ', __FILE__) . $eqLogic->getName() . ' - resultat : ' . json_encode($result));
    }
}

function hdsentinel_remove() {

    foreach (eqLogic::byType('hdsentinel') as $eqLogic) {
        log::add('hdsentinel', 'info', __('Début purge des logs vides ', __FILE__) . $eqLogic->getName());

        if (is_dir(dirname(__FILE__) . '/../../../log/')){
            shell_exec(system::getCmdSudo().' rm -f ' . dirname(__FILE__) . '/../../../log/hdsentinel_log*');
            log::add('hdsentinel', 'info', __('Logs vides supprimé pour ', __FILE__) . $eqLogic->getName());
        }
    }
}

?>
