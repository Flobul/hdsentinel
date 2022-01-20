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

try {
	require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

	include_file('core', 'authentification', 'php');

	if (!isConnect('admin')) {
		throw new Exception('401 Unauthorized');
	}

	ajax::init();

	if (init('action') == 'getImage') {
		$eqLogic = hdsentinel::byId(init('eq_id'));
        $image = "";
        if (is_object($eqLogic)) {
            $image = $eqLogic->getImage();
        }
        ajax::success($image);
	}

	if (init('action') == 'sendFile') {
		$eqLogic = hdsentinel::byId(init('id'));
		if (!is_object($eqLogic)) {
			throw new Exception(__('sendFile Remote inconnu : ', __FILE__) . init('id'), 9999);
		}
		ajax::success($eqLogic->sendFile());
    }
  
	if (init('action') == 'installDependancy') {
		$eqLogic = hdsentinel::byId(init('id'));
		if (!is_object($eqLogic)) {
			throw new Exception(__('installDependancy Remote inconnu : ', __FILE__) . init('id'), 9999);
		}
		ajax::success($eqLogic->installDependancy());
    }

	if (init('action') == 'getLogDependancy') {
		$eqLogic = hdsentinel::byId(init('id'));
		if (!is_object($eqLogic)) {
			throw new Exception(__('getLogDependancy Remote inconnu : ', __FILE__) . init('id'), 9999);
		}
		ajax::success($eqLogic->getLogDependancy());
    }

	if (init('action') == 'getLog') {
		$eqLogic = hdsentinel::byId(init('id'));
		if (!is_object($eqLogic)) {
			throw new Exception(__('getLog Remote inconnu : ', __FILE__) . init('id'), 9999);
		}
		ajax::success($eqLogic->getLog());
    }

	if (init('action') == 'statusCron') {
		$eqLogic = hdsentinel::byId(init('id'));
		if (!is_object($eqLogic)) {
			throw new Exception(__('statusCron Remote inconnu : ', __FILE__) . init('id'), 9999);
		}
		ajax::success($eqLogic->statusCron());
    }
  
	if (init('action') == 'launchCron') {
		$eqLogic = hdsentinel::byId(init('id'));
		if (!is_object($eqLogic)) {
			throw new Exception(__('launchCron Remote inconnu : ', __FILE__) . init('id'), 9999);
		}
		ajax::success($eqLogic->launchCron());
    }
  
	if (init('action') == 'removeCron') {
		$eqLogic = hdsentinel::byId(init('id'));
		if (!is_object($eqLogic)) {
			throw new Exception(__('removeCron Remote inconnu : ', __FILE__) . init('id'), 9999);
		}
		ajax::success($eqLogic->removeCron());
    }

	if (init('action') == 'stopCron') {
		$eqLogic = hdsentinel::byId(init('id'));
		if (!is_object($eqLogic)) {
			throw new Exception(__('stopCron Remote inconnu : ', __FILE__) . init('id'), 9999);
		}
		ajax::success($eqLogic->stopCron());
    }

	if (init('action') == 'createCron') {
		$eqLogic = hdsentinel::byId(init('id'));
		if (!is_object($eqLogic)) {
			throw new Exception(__('createCron Remote inconnu : ', __FILE__) . init('id'), 9999);
		}
		ajax::success($eqLogic->createCron());
    }

	if (init('action') == 'all') {
        $result = array();
		foreach (eqLogic::byType('hdsentinel') as $eqLogic) {
            if (init('make') == 'launch') {
                $result[] = $eqLogic->launchCron();
            }
            if (init('make') == 'upload') {
                $result[] = $eqLogic->sendFile();
            }
            if (init('make') == 'update') {
                $result[] = $eqLogic->installDependancy();
            }
            if (init('make') == 'stop') {
                $result[] = $eqLogic->stopCron();
            }
            if (init('make') == 'stopNdelete') {
                $result[] = $eqLogic->removeCron();
            }
        }
        ajax::success($result);
    }
  
	throw new Exception('Aucune methode correspondante');
	/*     * *********Catch exeption*************** */
} catch (Exception $e) {
	ajax::error(displayExeption($e), $e->getCode());
}
?>