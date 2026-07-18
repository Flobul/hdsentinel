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

require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class hdsentinel extends eqLogic
{
    public static $_hdsentinelVersion = '1.02.01';
    public static $_widgetPossibility = array('custom' => true);

    public static function getApiXmlResult($_xml, $_ip)
    {
        /**
         * Charge le fichier de configuration des commandes
         *
         * @param			$_xml     string     Tableau venant de HDSentinel en XML
         * @param			$_ip      string     Adresse IP
         * @return			          array      Tableau des commandes
         */
        log::add(__CLASS__, 'debug', 'Début getApiXmlResult'. json_encode(isset($_xml['General_Information']) ? $_xml['General_Information'] : array()));

        $array = array();
        if (!isset($_xml['General_Information'])) {
            log::add(__CLASS__, 'warning', 'Rapport XML ignoré : section General_Information absente');
            return false;
        }

        $general = $_xml['General_Information'];
        $computer = isset($general['Computer_Information']) ? $general['Computer_Information'] : array();
        $application = isset($general['Application_Information']) ? $general['Application_Information'] : array();
        $system = isset($general['System_Information']) ? $general['System_Information'] : array();
        $mac = isset($computer['MAC_Address']) ? $computer['MAC_Address'] : $_ip;
        $computerName = isset($computer['Computer_Name']) ? $computer['Computer_Name'] : 'HDSentinel';

        $array['name'] = trim($computerName . ' ' . $mac);
        $array['logicalId'] = $mac;
        if (isset($application['Current_Date_And_Time'])) {
            $application['Current_Date_And_Time'] = self::convertCurrentDateAndTime($application['Current_Date_And_Time']);
        }
        if (!isset($computer['Uptime']) && isset($computer['System_Uptime'])) {
            $computer['Uptime'] = $computer['System_Uptime'];
        }

        $array['configuration'] = array_merge(
            array('addressip' => $_ip),
            $computer,
            $application,
            $system
        );
        log::add(__CLASS__, 'debug', 'Début equipement');
        $eqLogic = self::searchEqLogic($array['logicalId'], $_ip);
        if (!is_object($eqLogic)) {
            log::add(__CLASS__, 'info', 'Creation hdsentinel : '.$array['logicalId']);
            $eqLogic = new hdsentinel();
            $eqLogic->setEqType_name(__CLASS__);
            $eqLogic->setIsEnable(1);
            $name = $array['name'];
        } else {
            $name = $eqLogic->getName();
        }
        log::add(__CLASS__, 'debug', 'Début a2o' .print_r($array,true));
        utils::a2o($eqLogic, $array);
        if (is_object($eqLogic) && $name != '') {
            $eqLogic->setName($name);
        }
        log::add(__CLASS__, 'debug', 'Fin a2o');

        try {
            $eqLogic->save();
        } catch(Exception $e) {
            $eqLogic->setName($eqLogic->getName().' '.config::genKey(3));
            $eqLogic->save();
        }
        $disk = array();
        $summaryKeys = array(
            'Hard_Disk_Number',
            'Hard_Disk_Device',
            'Hard_Disk_Serial_Number',
            'Total_Size',
            'Current_Temperature',
            'Maximum_temperature_during_entire_lifespan',
            'Power_on_time',
            'Estimated_remaining_lifetime',
            'Health',
            'Performance',
            'Description',
            'Lifetime_writes',
        );
        foreach ($_xml as $xmlKey => $xmlValue) {
            if (!preg_match('/^Physical_Disk_Information_Disk_(\d+)$/', $xmlKey, $matches)) {
                continue;
            }
            if (!isset($xmlValue['Hard_Disk_Summary']) || !is_array($xmlValue['Hard_Disk_Summary'])) {
                continue;
            }
            $diskIndex = intval($matches[1]);
            foreach ($summaryKeys as $summaryKey) {
                if (!isset($xmlValue['Hard_Disk_Summary'][$summaryKey])) {
                    continue;
                }
                $value = $xmlValue['Hard_Disk_Summary'][$summaryKey];
                if ($summaryKey == 'Power_on_time') {
                    $value = self::translatePowerOnTime($value);
                }
                if ($summaryKey == 'Estimated_remaining_lifetime') {
                    $value = self::translateEstimatedRemainingLifetime($value);
                }
                $disk[$diskIndex][$summaryKey] = $value;
            }
        }

        log::add(__CLASS__, 'debug', 'Début commandes');
        $all_cmds = self::loadCmdFromConf();
        if (!is_array($all_cmds)) {
            return false;
        }
        foreach ($disk as $nb => $summaries) {
            if (!isset($summaries['Hard_Disk_Number'])) {
                $summaries['Hard_Disk_Number'] = $nb;
            }
            foreach ($summaries as $summary => $value) {
                if ($value != '' && $value != '?' && !preg_match('/^Unknown/', $value)) {
                    $cmd = $eqLogic->searchCmd($summary . " " . $summaries['Hard_Disk_Number'], $summary . " " . $summaries['Hard_Disk_Number']);
                    if (!is_object($cmd)) {
                        if (isset($all_cmds[$summary])) {
                            $eqLogic->createCmdsFromConfig($all_cmds[$summary], $summaries['Hard_Disk_Number']);
                            $cmd = $eqLogic->searchCmd($summary . " " . $summaries['Hard_Disk_Number'], $summary . " " . $summaries['Hard_Disk_Number']);
                        }
                    }
                    if (is_object($cmd)) {
                        $unite = '';
                        if ($cmd->getSubType() == 'numeric') {
                            $split = explode(' ', $value);
                            $value = $split[0];
                            $unite = isset($split[1]) ? $split[1] : '';
                            if ($unite != '') {
                                $cmd->setUnite($unite)->save();
                            }
                        }
                        log::add(__CLASS__, 'debug', 'Mise à jour de la commande de ' . $eqLogic->getName() . ' : ' .  $summary . ' - value: ' . $value . ' - unite: ' . $unite);
                        $cmd->event($value);
                    }
                }
            }
        }
    }

    public static function translatePowerOnTime($_string)
    {
        /**
         * Traduit le temps sous tension en français
         *
         * @param			$_string      string       Valeur en anglais
         * @return			              string       Valeur en français
         */
        $arrEng = array('days','day','hours','hour');
        $arrFra = array(__('jours', __FILE__),__('jour', __FILE__),__('heures', __FILE__),__('heure', __FILE__));
        return str_replace($arrEng, $arrFra, $_string);
    }

    public static function translateEstimatedRemainingLifetime($_string)
    {
        /**
         * Traduit le temps restant estimé en français
         *
         * @param			$_string      string       Valeur en anglais
         * @return			              string       Valeur en français
         */
        $arrEng = array('more than','days','hours','hour');
        $arrFra = array(__('plus de', __FILE__),__('jours', __FILE__),__('heures', __FILE__),__('heure', __FILE__));
        return str_replace($arrEng, $arrFra, $_string);
    }

    public static function convertCurrentDateAndTime($_string)
    {
        /**
         * Converti le format de date et heure du dernier rapport en temps conventionel jeedom
         *
         * @param			$_string      string       Temps reçu
         * @return			              string       Temps converti
         */
        $datetime = DateTime::createFromFormat("d-n-y H:i:s", $_string);
        if($datetime === false) {
            $datetime = DateTime::createFromFormat("d/m/Y H:i:s", $_string);
        }
        if($datetime === false) {
            $datetime = DateTime::createFromFormat("d-m-Y H:i:s", $_string);
        }
        if($datetime === false) {
            log::add(__CLASS__, 'debug', 'Format de date HDSentinel non reconnu : ' . $_string);
            return $_string;
        }
        return $datetime->format('Y-m-d H:i:s');
    }

    public function decrypt() {
        $this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
    }

    public function encrypt() {
        $this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
    }

  
    public static function cronHourly()
    {
        /**
         * Cron démarré toutes les heures par jeedom
         * Récupère les logs distants et si démon auto redémarre le cron si inactif
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         * @return			|*Cette fonction ne retourne pas de valeur*|
         */
        foreach (eqLogic::byType(__CLASS__) as $eqLogic) {
            if (!$eqLogic->getIsEnable()) continue;
            if ($eqLogic->getConfiguration('windows', false)) {

                $url = 'http://'.$eqLogic->getConfiguration('addressip').':'. $eqLogic->getConfiguration('portssh').'/xml';
                $request_http = new com_http($url, ' ', $eqLogic->getConfiguration('password'));
                $result = $request_http->exec();

                $start = strpos($result,"<?xml");
                $end = '</Hard_Disk_Sentinel>';

                $result = substr($result, $start);
                $result = substr($result, 0, strpos($result,$end) + strlen($end));
                log::add(__CLASS__, 'debug', __('Resultat XML', __FILE__) . $result);

                try {
                    $xml_action = new SimpleXMLElement($result);
                    $result = json_decode(json_encode($xml_action), true);
                    self::getApiXmlResult($result, $eqLogic->getConfiguration('addressip'));
                } catch (Exception $e) {
                    log::add(__CLASS__, 'info', __('Erreur XML', __FILE__));
                }

            } else {
                
              //$this->getConfiguration('autorefresh', '03 00 * * *')
                /*if ($eqLogic->getConfiguration('remoteDaemonAuto', '0') == 1) {
                    log::add(__CLASS__, 'info', 'Redémarrage cron remote ' . $eqLogic->getName());
                    $eqLogic->launchCron($eqLogic->getId());
                }
                $eqLogic->getLog();*/
            }
        }
    }

    public static function searchEqLogic($_logicalId, $_ip)
    {
        /**
         * Trouve la commande associée au logicalId ou au nom donné
         *
         * @param			$_logicalId		string		LogicalId de commande à trouver
         * @param			$_name			string		Nom de commande à trouver
         * @return			$cmd			object		Commande trouvée
         */
        $return = null;
        foreach (eqLogic::byType(__CLASS__) as $eqLogic) {
            if ($_logicalId != '' && $eqLogic->getLogicalId() == $_logicalId) {
                $return = $eqLogic;
                break;
            }
            $sshmanager = eqLogic::byId($eqLogic->getConfiguration('host_id'));
            if (is_object($sshmanager)
                && $sshmanager->getConfiguration(sshmanager::CONFIG_HOST) == $_ip) {
                $return = $eqLogic;
                break;
            }
        }
        return $return;
    }

    private static function loadCmdFromConf()
    {
        /**
         * Charge le fichier de configuration des commandes
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         * @return			      array      Tableau des commandes
         */
        if (!is_file(dirname(__FILE__) . '/../../core/config/all_cmds.json')) {
            log::add(__CLASS__, 'debug', 'Fichier introuvable : all_cmds.json');
            return;
        }
        $content = file_get_contents(dirname(__FILE__) . '/../../core/config/all_cmds.json');
        if (!is_json($content)) {
            log::add(__CLASS__, 'debug', 'JSON invalide : all_cmds.json');
            return;
        }
        return json_decode($content, true);
    }

    private static function translate($word)
    {
        /**
         * Traduction des informations. (plus utilisé car langue selectionnée dans la requete)
         *
         * @param	$word		string		Mot en anglais.
         * @return	$word		string		Mot en Français (ou anglais, si traduction inexistante).
         */

        $translate = array(
            'Hard_Disk_Number' => __("Numérotation du disque", __FILE__),
            'Hard_Disk_Device' => __("Emplacement du disque", __FILE__),
            'Hard_Disk_Serial_Number' => __("Numéro de série du disque", __FILE__),
            'Total_Size' => __("Taille totale", __FILE__),
            'Current_Temperature' => __("Température actuelle", __FILE__),
            'Maximum_temperature_during_entire_lifespan' => __("Température maximale atteinte", __FILE__),
            'Power_on_time' => __("Temps sous tension", __FILE__),
            'Estimated_remaining_lifetime' => __("Durée de vie estimée", __FILE__),
            'Health' => __("Santé", __FILE__),
            'Lifetime_writes' => __("Écriture totale", __FILE__),
            'Performance' => __("Performance", __FILE__),
            'Description' => __("Déscription", __FILE__),
        );
        (array_key_exists($word, $translate)) ? $word = $translate[$word] : null;
        return $word;
    }

    public static function getApiHtmlResult($_html, $_ip)
    {
        /**
         * Crée le fichier html pour la page
         *
         * @param			$_html    string     Html du résultat
         * @param			$_ip      string     Adresse IP
         * @return			          array      Tableau des commandes
         */
        $data_path = dirname(__FILE__) . '/../../core/data';
        $eqLogic = self::searchEqLogic('', $_ip);
        if (is_object($eqLogic)) {
            if (!is_dir($data_path)) {
                mkdir($data_path, 0755, true);
            }
            file_put_contents($data_path . '/hdsentinel_'.$eqLogic->getId().'.html', $_html);
            return true;
        }
        return false;
    }
  
  
    public function postSave()
    {
        $cmdRefresh = $this->getCmd('action', 'refresh');
        if (!is_object($cmdRefresh)) {
            log::add(__CLASS__, 'debug', 'Créaction de la commande Rafraîchir');
            $cmdRefresh = new hdsentinelCmd();
            $cmdRefresh->setLogicalId('refresh');
            $cmdRefresh->setEqLogic_id($this->getId());
            $cmdRefresh->setName(__('Rafraîchir',__FILE__));
            $cmdRefresh->setIsVisible(true);
            $cmdRefresh->setType('action');
            $cmdRefresh->setSubType('other');
            $cmdRefresh->setGeneric_type('DONT');
            $cmdRefresh->save();
        }
        if (trim($this->getConfiguration('autorefresh')) != '') {
            log::add(__CLASS__, 'debug', $this->getName() . ' cronEqLogic (AutoRefresh) :: ' . $this->getConfiguration('autorefresh'));

            $cron = cron::byClassAndFunction(__CLASS__, 'cronEqLogic', array('HDSentinel_id' => intval($this->getId())));
            if (!is_object($cron)) {
                $cron = new cron();
                $cron->setClass(__CLASS__);
                $cron->setFunction('cronEqLogic');
                $cron->setOption(array('HDSentinel_id' => intval($this->getId())));
                $cron->setDeamon(0);
            }
            if ($this->getIsEnable()) {
                $cron->setEnable(1);
            } else {
                $cron->setEnable(0);
            }

            $_cronPattern = $this->getConfiguration('autorefresh');
            $cron->setSchedule($_cronPattern);

            if ($_cronPattern == '* * * * *') {
                $cron->setTimeout(1);
                log::add(__CLASS__, 'debug', $this->getName() . ' cronEqLogic Timeout 1min');
            } else {
                $_ExpMatch = array();
                $_ExpResult = preg_match('/^([0-9,]+|\*)\/([0-9]+)/', $_cronPattern, $_ExpMatch);
                if ($_ExpResult === 1) {
                    $cron->setTimeout(intval($_ExpMatch[2]));
                    log::add(__CLASS__, 'debug', $this->getName() . ' cronEqLogic Timeout '. $_ExpMatch[2] .'min');
                } else {
                    $cron->setTimeout(15);
                    log::add(__CLASS__, 'debug', $this->getName() . ' cronEqLogic Timeout Default 15min');
                }
            }
            $cron->save();
        } else {
            $cron = cron::byClassAndFunction(__CLASS__, 'cronEqLogic', array('HDSentinel_id' => intval($this->getId())));
            if (is_object($cron)) {
                $cron->remove();
                log::add(__CLASS__, 'debug', $this->getName() . ' Remove cronEqLogic');
            }
        }
    }

    public static function cronEqLogic($_options) {
        $eqLogic = eqLogic::byId($_options['HDSentinel_id']);
        if (is_object($eqLogic)) {
            try {
                $eqLogic->refresh();
            } catch (Exception $exc) {
                log::add(__CLASS__, 'debug', $eqLogic->getName() . ' cronEqLogic Exception ' . $exc->getMessage());
            }
        }
    }

    public function preRemove() {
        $cron = cron::byClassAndFunction(__CLASS__, 'cronEqLogic', array('HDSentinel_id' => intval($this->getId())));
        if (is_object($cron)) {
            $cron->remove();
        }
    }

    public function getHtmlDisksFullResult()
    {
        if ($this->getConfiguration('windows', false)) {
            return false;
        }
        $sshmanager = eqLogic::byId($this->getConfiguration('host_id'));
        if (!is_object($sshmanager)) {
            return false;
        }
        if (!$this->ensureRemoteScripts()) {
            log::add(__CLASS__, 'warning', __('Lancement annulé : scripts distants indisponibles. ', __FILE__) . $this->getRemoteScriptsDiagnostic());
            return false;
        }
        $plugin = plugin::byId(__CLASS__);
        $cmd = $this->getSudoCmd();
        $cmd .='bash ' . $this->getRemotePublishScript();
        $cmd .= ' -a ' . jeedom::getApiKey($plugin->getId());
        $cmd .= ' -i \'' . network::getNetworkAccess('internal') . '\'';
        $cmd .= ' -o html';
        return $this->executeCmds($cmd);
    }

    public function createCmdsFromConfig($_cmd, $_diskNb)
    {
        /**
         * Crée la/les commande/s fournies par le fichier de conf et l'info qui arrive du disque
         *
         * @param			$_cmd       array       Array de commande
         * @param			$_diskNb    string      Numéro du disque pour saisie de logicalId et Nom
         * @return			|*Cette fonction ne retourne pas de valeur*|
         */
        foreach ($_cmd as $cmdDef) {
            $cmd = $this->getCmd('info', $cmdDef['logicalId'] . " " . $_diskNb);
            if (!is_object($cmd)) {
                log::add(__CLASS__, 'debug', 'Création : ' . $cmdDef["logicalId"] . ' - nom : ' . $cmdDef['name']);
                $cmd = new hdsentinelCmd();
                $cmd->setLogicalId($cmdDef['logicalId'] . " " . $_diskNb);
                $cmd->setEqLogic_id($this->getId());
                $cmd->setName($cmdDef['name'] . " " . $_diskNb);
                if (isset($cmdDef['isHistorized'])) {
                    $cmd->setIsHistorized($cmdDef["isHistorized"]);
                }
                if (isset($cmdDef['isVisible'])) {
                    $cmd->setIsVisible($cmdDef['isVisible']);
                }
                if (isset($cmdDef['template'])) {
                    foreach ($cmdDef['template'] as $key => $value) {
                        $cmd->setTemplate($key, $value);
                    }
                }
                $cmd->setType($cmdDef["type"]);
                $cmd->setSubType($cmdDef["subtype"]);
                if (isset($cmdDef["generic_type"])) {
                    $cmd->setGeneric_type($cmdDef["generic_type"]);
                }
                if (isset($cmdDef["unite"])) {
                    $cmd->setUnite($cmdDef["unite"]);
                }
                if (isset($cmdDef['configuration'])) {
                    foreach ($cmdDef['configuration'] as $key => $value) {
                        $cmd->setConfiguration($key, $value);
                    }
                }
                $cmd->save();
            }
        }
    }

    public function getNbDisksByEqLogic()
    {
        $i = 0;
        foreach ($this->getCmd('info') as $allCmd) {
            if (preg_match('/^Health$|^Health \d{1,2}$/', $allCmd->getLogicalId())) {
                $i++;
            }
        }
        return $i;
    }

    public function getImage()
    {
        /**
         * Renvoie l'url de l'image à partir de l'objet
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         * @return			$return		string		Url de l'image
         */
        $path = 'plugins/hdsentinel/core/config/images/';
        $files = ls(__DIR__ . '/../../../../'. $path, '*.png', false, array('files', 'quiet'));
        $return = "";
        foreach ($files as $file) {
            if (!preg_match('/'.strtr($file, array('.png' => '')).'/', $this->getConfiguration('groupName'))) {
                continue;
            }
            if (!preg_match('/'.strtr($file, array('.png' => '')).'/', $this->getName())) {
                continue;
            }
            $return = $path . rawurlencode($file);
        }
        return $return;
    }

    public function searchCmd($_logicalId, $_name)
    {
        /**
         * Trouve la commande associée au logicalId ou au nom donné
         *
         * @param			$_logicalId		string		LogicalId de commande à trouver
         * @param			$_name			string		Nom de commande à trouver
         * @return			$cmd			object		Commande trouvée
         */
        $cmd = null;
        foreach ($this->getCmd() as $liste_cmd) {
            if ($liste_cmd->getLogicalId() == $_logicalId || $liste_cmd->getName() == $_name) {
                $cmd = $liste_cmd;
                break;
            }
        }
        return $cmd;
    }

    public function createCron()
    {
        /**
         * Crée le cron distant dans crontab
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         * @return			$return			string		Retour de la commande
         */
        log::add(__CLASS__, 'info', __('Début création du cron distant', __FILE__));
        $return = false;
        //$cmd1 = $this->getSudoCmd() . 'touch /etc/cron.daily/hdsentinel; echo $?';
        //$cmd2 = $this->getSudoCmd();
        if ($this->getConfiguration('windows', false)) {
            return false;
        }
        $sshmanager = eqLogic::byId($this->getConfiguration('host_id'));
        if (!is_object($sshmanager)) {
            return false;
        }
        $cmd2 = $this->getWriteCronCommand();
        $return = $this->executeCmds($cmd2);

        $cmdLog = $this->sanitizeCommandForLog($cmd2);
        log::add(__CLASS__, 'info', __('Fin création du cron distant cmd1: ', __FILE__) . ' + cmd: ' . $cmdLog . ' = ' . $return);
        return $return;
    }

    public function getSudoCmd()
    {
        /**
         * Si non root, demande les privilège avant une commande
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         * @return			$return			string		Commande pour élever les privilèges de la commande à envoyer
         */
        $cmd = '';
        $sshmanager = eqLogic::byId($this->getConfiguration('host_id'));
        if (is_object($sshmanager)) {
            $user = utils::decrypt($sshmanager->getConfiguration(sshmanager::CONFIG_USERNAME));
            if ($user != 'root') {
                $password = '';
                if (defined('sshmanager::CONFIG_PASSWORD')) {
                    $password = utils::decrypt($sshmanager->getConfiguration(sshmanager::CONFIG_PASSWORD));
                }
                if ($password == '') {
                    $password = utils::decrypt($sshmanager->getConfiguration('password'));
                }
                if ($password == '') {
                    $password = $user;
                }
                $cmd .= 'echo ' . escapeshellarg($password) . ' | sudo -S ';
            }
        }
        return $cmd;
    }

    private function getRemoteDir()
    {
        return '/usr/local/hdsentinel-jeedom';
    }

    private function getRemotePublishScript()
    {
        return $this->getRemoteDir() . '/ressources/hdsentinel_to_jeedom_pub.sh';
    }

    private function getRemoteInstallScript()
    {
        return $this->getRemoteDir() . '/ressources/install_apt.sh';
    }

    private function getCronLine()
    {
        $plugin = plugin::byId(__CLASS__);
        return trim($this->getConfiguration('autorefresh', '03 00 * * *')) . ' ' .
            'bash ' . $this->getRemotePublishScript() .
            ' -a ' . jeedom::getApiKey($plugin->getId()) .
            ' -i ' . escapeshellarg(network::getNetworkAccess('internal')) .
            ' -o xml >> /tmp/hdsentinel_log 2>&1';
    }

    private function getWriteCronCommand()
    {
        $shell = 'printf "%s\n" ' . escapeshellarg($this->getCronLine()) . ' > /etc/cron.daily/hdsentinel; echo $?';
        return $this->getSudoCmd() . 'sh -c ' . escapeshellarg($shell);
    }

    private function getCrontabShell($_action)
    {
        $prefix = 'CRONTAB=/usr/bin/crontab; [ -x /opt/bin/crontab ] && CRONTAB=/opt/bin/crontab; ';
        if ($_action == 'install') {
            return $prefix . '$CRONTAB /etc/cron.daily/hdsentinel; echo $?';
        }
        if ($_action == 'remove') {
            return $prefix . '$CRONTAB -u root -l 2>/dev/null | grep -v "hdsentinel_to_jeedom_pub" | $CRONTAB -u root -; echo $?';
        }
        return $prefix . '$CRONTAB -u root -l 2>/dev/null | grep "hdsentinel_to_jeedom_pub" | wc -l';
    }

    private function getCrontabCommand($_action)
    {
        return $this->getSudoCmd() . 'sh -c ' . escapeshellarg($this->getCrontabShell($_action));
    }

    private function sanitizeCommandForLog($_cmd)
    {
        $cmd = is_array($_cmd) ? json_encode($_cmd) : $_cmd;
        $cmd = preg_replace("/echo\s+'[^']*'\s+\|\s+sudo\s+-S/", "echo 'PASSWORD' | sudo -S", $cmd);
        $cmd = preg_replace('/echo\s+"[^"]*"\s+\|\s+sudo\s+-S/', 'echo "PASSWORD" | sudo -S', $cmd);
        $plugin = plugin::byId(__CLASS__);
        if (is_object($plugin)) {
            $cmd = str_replace(jeedom::getApiKey($plugin->getId()), 'APIKEY', $cmd);
        }
        return $cmd;
    }

    private function normalizeCmdResult($_result)
    {
        if (is_array($_result)) {
            $_result = implode("\n", $_result);
        }
        return trim(preg_replace('/(^|\n)\s*Password:\s*/', '$1', strval($_result)));
    }

    private function cmdResultIsOne($_result)
    {
        return preg_match('/(^|\s)1(\s|$)/', $this->normalizeCmdResult($_result)) === 1;
    }

    private function cmdResultIsZero($_result)
    {
        return preg_match('/(^|\s)0(\s|$)/', $this->normalizeCmdResult($_result)) === 1;
    }

    private function remoteFileExists($_path)
    {
        $check = 'test -f ' . escapeshellarg($_path) . ' && echo 1 || echo 0';
        $cmd = $this->getSudoCmd() . 'sh -c ' . escapeshellarg($check);
        return $this->cmdResultIsOne($this->executeCmds($cmd));
    }

    private function remoteIsSynology()
    {
        $check = '[ -f /etc/synoinfo.conf ] || [ -f /etc.defaults/synoinfo.conf ] && echo 1 || echo 0';
        $cmd = $this->getSudoCmd() . 'sh -c ' . escapeshellarg($check);
        return $this->cmdResultIsOne($this->executeCmds($cmd));
    }

    private function remoteScriptsReady()
    {
        $check = '[ -f ' . escapeshellarg($this->getRemotePublishScript()) . ' ] && [ -f ' . escapeshellarg($this->getRemoteInstallScript()) . ' ] && echo 1 || echo 0';
        $cmd = $this->getSudoCmd() . 'sh -c ' . escapeshellarg($check);
        $result = $this->normalizeCmdResult($this->executeCmds($cmd));
        log::add(__CLASS__, 'debug', __('Vérification scripts distants : ', __FILE__) . $result);
        return $this->cmdResultIsOne($result);
    }

    private function getRemoteScriptsDiagnostic()
    {
        $cmd = $this->getSudoCmd() . 'sh -c ' . escapeshellarg(
            'id; ls -ld ' . escapeshellarg($this->getRemoteDir()) . ' ' . escapeshellarg($this->getRemoteDir() . '/ressources') . ' 2>&1; ls -l ' . escapeshellarg($this->getRemoteDir() . '/ressources') . ' 2>&1'
        );
        return $this->normalizeCmdResult($this->executeCmds($cmd));
    }

    private function ensureRemoteScripts()
    {
        if ($this->remoteScriptsReady()) {
            return true;
        }

        log::add(__CLASS__, 'info', __('Scripts distants absents, envoi automatique', __FILE__));
        $result = $this->sendFile();
        if (!is_array($result)) {
            log::add(__CLASS__, 'warning', __('Envoi automatique des scripts impossible', __FILE__));
            return false;
        }

        $installOk = isset($result['install']) && $this->cmdResultIsOne($result['install']);
        $publishOk = isset($result['publish']) && $this->cmdResultIsOne($result['publish']);
        if ((!$installOk || !$publishOk) && !$this->remoteScriptsReady()) {
            log::add(__CLASS__, 'warning', __('Scripts distants non trouvés après envoi : ', __FILE__) . json_encode($result));
            log::add(__CLASS__, 'warning', __('Diagnostic scripts distants : ', __FILE__) . $this->getRemoteScriptsDiagnostic());
            return false;
        }
        return true;
    }

    public function launchCron()
    {
        /**
         * Crée le cron distant dans crontab
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         * @return			$return			string		Retour de la commande
         */
        log::add(__CLASS__, 'info', __('Début lancement du cron distant', __FILE__));
        if ($this->getConfiguration('windows', false)) {
            return false;
        }
        $sshmanager = eqLogic::byId($this->getConfiguration('host_id'));
        if (!is_object($sshmanager)) {
            return false;
        }
        if (!$this->ensureRemoteScripts()) {
            log::add(__CLASS__, 'warning', __('Lancement annulé : scripts distants indisponibles. ', __FILE__) . $this->getRemoteScriptsDiagnostic());
            return false;
        }
        if (!$this->remoteFileExists('/etc/cron.daily/hdsentinel')) {

            $this->executeCmds($this->getSudoCmd() . 'mkdir -p /etc/cron.daily/;');
            log::add(__CLASS__, 'info', __('Création du cron distant ', __FILE__));

            if ($this->remoteIsSynology()) {
                log::add(__CLASS__, 'info', __('Création du cron distant pour synology ', __FILE__));
            }

            $createResult = $this->createCron();
            if (!$this->cmdResultIsZero($createResult)) {
                log::add(__CLASS__, 'warning', __('Création du cron distant échouée : ', __FILE__) . $this->normalizeCmdResult($createResult));
                return $createResult;
            }
        }
        if (!$this->cmdResultIsOne($this->executeCmds($this->getCrontabCommand('status')))) {
            log::add(__CLASS__, 'info', __('Lancement du cron distant', __FILE__));
            return $this->executeCmds($this->getCrontabCommand('install'));
        }
        return false;
    }

    public function removeCron()
    {
        /**
         * Arrête le cron distant dans crontab (et supprime le fichier pour le relancer)
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         *       			|*Cette fonction ne retourne pas de valeur*|
         */
        log::add(__CLASS__, 'info', __('Suppression du cron distant', __FILE__));
        if ($this->getConfiguration('windows', false)) {
            return false;
        }
        $cmd1 = $this->getCrontabCommand('remove');
        $cmd2 = $this->getSudoCmd() . "rm -f /etc/cron.daily/hdsentinel; echo $?;";
        return $this->executeCmds([$cmd1,$cmd2]);
    }

    public function stopCron()
    {
        /**
         * Arrête le cron distant dans crontab (garde le fichier pour le relancer)
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         *       			|*Cette fonction ne retourne pas de valeur*|
         */
        log::add(__CLASS__, 'info', __('Arrêt du cron distant', __FILE__));
        if ($this->getConfiguration('windows', false)) {
            return false;
        }
        return $this->executeCmds($this->getCrontabCommand('remove'));
    }


    public function statusCron()
    {
        /**
         * Renvoi le status du cron dans le crontab
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         *       			|*Cette fonction ne retourne pas de valeur*|
         */
        log::add(__CLASS__, 'info', __('Statut du cron distant', __FILE__));
        if ($this->getConfiguration('windows', false)) {
            return false;
        }
        $cmd = $this->getCrontabCommand('status');
        log::add(__CLASS__, 'info', __('Statut du cron distant : ', __FILE__) . $this->sanitizeCommandForLog($cmd));
        return $this->executeCmds($cmd);
    }

    public function installDependancy()
    {
        /**
         * Envoie de commandes à l'appareil distant
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         *       			|*Cette fonction ne retourne pas de valeur*|
         */
        log::add(__CLASS__, 'info', __('Installation des dépendances', __FILE__));
        if ($this->getConfiguration('windows', false)) {
            return false;
        }
        $sshmanager = eqLogic::byId($this->getConfiguration('host_id'));
        if (!is_object($sshmanager)) {
            return false;
        }
        if (!$this->ensureRemoteScripts()) {
            log::add(__CLASS__, 'warning', __('Installation annulée : scripts distants indisponibles. ', __FILE__) . $this->getRemoteScriptsDiagnostic());
            return false;
        }
        $cmd = $this->getSudoCmd() . 'sh -c ' . escapeshellarg('bash ' . $this->getRemoteInstallScript() . ' >> /tmp/hdsentinel_dependancy 2>&1 & echo 1');
        return $this->executeCmds($cmd);
    }

    public function executeCmds($_cmd) {
      
        try {
            if (func_num_args() > 1) {
                $_cmd = func_get_args();
            }
            $result = sshmanager::executeCmds($this->getConfiguration('host_id'), $_cmd);
        } catch (RuntimeException $ex) {
            log::add(__CLASS__, 'debug', $this->getName() . __(' Erreur Runtime du cron distant', __FILE__) . $ex->getMessage());
            $result = false;
        } catch (Throwable $th) {
            log::add(__CLASS__, 'debug', $this->getName() . __(' Erreur générale du cron distant', __FILE__) . $th->getMessage());
            $result = false;
        }
        return $result;
    }

    private function sendArchiveFallback($_localArchive, $_remoteArchive)
    {
        if (!is_file($_localArchive)) {
            return false;
        }

        $encoded = base64_encode(file_get_contents($_localArchive));
        if ($encoded == '') {
            return false;
        }

        log::add(__CLASS__, 'info', __('Transfert SSH Manager impossible, tentative en base64', __FILE__));
        $remoteBase64 = $_remoteArchive . '.b64';
        $commands = array('rm -f ' . escapeshellarg($remoteBase64) . ' ' . escapeshellarg($_remoteArchive) . '; echo $?');
        foreach (str_split($encoded, 700) as $chunk) {
            $commands[] = 'printf %s ' . escapeshellarg($chunk) . ' >> ' . escapeshellarg($remoteBase64) . '; echo $?';
        }
        $commands[] = '(base64 -d ' . escapeshellarg($remoteBase64) . ' > ' . escapeshellarg($_remoteArchive) . ' 2>/dev/null || base64 --decode ' . escapeshellarg($remoteBase64) . ' > ' . escapeshellarg($_remoteArchive) . '); echo $?';
        $commands[] = 'rm -f ' . escapeshellarg($remoteBase64) . '; test -s ' . escapeshellarg($_remoteArchive) . ' && echo 1 || echo 0';

        $result = $this->executeCmds($commands);
        log::add(__CLASS__, 'debug', __('Résultat transfert base64 : ', __FILE__) . json_encode($result));
        return trim($this->executeCmds('test -s ' . escapeshellarg($_remoteArchive) . ' && echo 1 || echo 0')) == '1';
    }

    private function getRemoteFileWithFallback($_local, $_remote)
    {
        try {
            if (sshmanager::getFile($this->getConfiguration('host_id'), $_local, $_remote)) {
                return true;
            }
        } catch (Throwable $th) {
            log::add(__CLASS__, 'debug', __('Erreur récupération fichier SSH Manager : ', __FILE__) . $th->getMessage());
        }

        log::add(__CLASS__, 'info', __('Récupération de fichier via commande SSH', __FILE__));
        $cmd = $this->getSudoCmd() . 'sh -c ' . escapeshellarg('test -f ' . escapeshellarg($_remote) . ' && cat ' . escapeshellarg($_remote) . ' || true');
        $content = $this->normalizeCmdResult($this->executeCmds($cmd));
        if ($content === '') {
            return false;
        }

        if (!is_dir(dirname($_local))) {
            mkdir(dirname($_local), 0755, true);
        }
        return file_put_contents($_local, $content) !== false;
    }

    public function getLogDependancy($_dependancy = '')
    {
        /**
         * Récupère le log d'installation des dépendances sur l'appareil distant
         *
         * @param			$_dependancy     string       Pour attriber un nom au log
         * @return			                 bool         Retour de la commande
         */
        if ($this->getConfiguration('windows', false)) {
            return false;
        }
        $name = $this->getName();
        $local = dirname(__FILE__) . '/../../../../log/hdsentinel_'.str_replace(' ', '-', $name).$_dependancy;
        log::add(__CLASS__, 'info', __('Suppression de la log ', __FILE__) . $local);
        if (is_file($local)) {
            @unlink($local);
        }
        log::add(__CLASS__, 'info', __('Récupération de la log distante', __FILE__));
        if ($this->getRemoteFileWithFallback($local, '/tmp/hdsentinel_dependancy'.$_dependancy)) {
            $this->executeCmds('cat /dev/null > /tmp/hdsentinel_dependancy'.$_dependancy);
            return true;
        }
        return false;
    }

    public function getLog($_dependancy = '')
    {
        /**
         * Récupère le log du cron sur l'appareil distant
         *
         * @param			$_dependancy     string       Pour attriber un nom au log
         * @return			                 bool         Retour de la commande
         */
        if ($this->getConfiguration('windows', false)) {
            return false;
        }
        $name = $this->getName();
        $local = dirname(__FILE__) . '/../../../../log/hdsentinel_log_'.str_replace(' ', '-', $name).$_dependancy;
        log::add(__CLASS__, 'info', __('Suppression de la log ', __FILE__) . $local);
        if (is_file($local)) {
            @unlink($local);
        }
        log::add(__CLASS__, 'info', __('Récupération de la log distante', __FILE__));
        if ($this->getRemoteFileWithFallback($local, '/tmp/hdsentinel_log'.$_dependancy)) {
            $this->executeCmds('cat /dev/null > /tmp/hdsentinel_log'.$_dependancy);
            return true;
        }
        return false;
    }

    public function sendFile()
    {
        /**
         * Envoi les scripts du cron et d'installation
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         * @return			$result        array         Résultat des 2 scripts envoyés
         */
        $result = array();
        $cmd = $this->getSudoCmd();
        $sshmanager = eqLogic::byId($this->getConfiguration('host_id'));
        if (!is_object($sshmanager)) {
            return false;
        }
        log::add(__CLASS__, 'debug', __('Envoi de fichier ', __FILE__) . $this->getName());
        $script_path = realpath(dirname(__FILE__) . '/../../ressources/');
        $localArchive = '/tmp/folder-hdsentinel.tar.gz';
        $remoteArchive = '/tmp/folder-hdsentinel.tar.gz';
        exec('tar -zcf ' . escapeshellarg($localArchive) . ' -C ' . escapeshellarg(dirname($script_path)) . ' ressources', $tarOutput, $tarReturn);
        $result['archive'] = is_file($localArchive) ? filesize($localArchive) : 0;
        if ($tarReturn !== 0 || !is_file($localArchive)) {
            log::add(__CLASS__, 'warning', __('Création archive locale impossible : ', __FILE__) . json_encode($tarOutput));
            return $result;
        }

        log::add(__CLASS__, 'info', __('Création du dossier des scripts', __FILE__));
        $result['dir'] = $this->executeCmds(array(
            $cmd . 'rm -Rf ' . $this->getRemoteDir() . '; echo $?',
            $cmd . 'mkdir -p ' . $this->getRemoteDir() . '; echo $?'
        ));
        log::add(__CLASS__, 'info', 'Envoi du fichier /tmp/folder-hdsentinel.tar.gz');

        $sendOk = false;
        try {
            $sendOk = sshmanager::sendFile($this->getConfiguration('host_id'), $localArchive, $remoteArchive);
        } catch (Throwable $th) {
            log::add(__CLASS__, 'debug', __('Erreur transfert SSH Manager : ', __FILE__) . $th->getMessage());
            $sendOk = false;
        }
        if (!$sendOk) {
            $sendOk = $this->sendArchiveFallback($localArchive, $remoteArchive);
        }
        $result['send'] = $sendOk ? 1 : 0;

        if ($sendOk) {
			log::add(__CLASS__,'info',__('Décompression du dossier distant',__FILE__));
            $result['uncompress'] = $this->executeCmds(array(
                $cmd . 'tar -zxf ' . $remoteArchive . ' -C ' . $this->getRemoteDir() . '; echo $?',
                $cmd . 'chmod +x ' . $this->getRemoteDir() . '/ressources/*.sh; echo $?',
                'rm -f ' . $remoteArchive . '; echo $?'
            ));
            $result['install'] = $this->executeCmds($cmd . 'sh -c ' . escapeshellarg('test -f ' . escapeshellarg($this->getRemoteInstallScript()) . ' && echo 1 || echo 0'));
            $result['publish'] = $this->executeCmds($cmd . 'sh -c ' . escapeshellarg('test -f ' . escapeshellarg($this->getRemotePublishScript()) . ' && echo 1 || echo 0'));
            $result['install'] = $this->cmdResultIsOne($result['install']) ? 1 : 0;
            $result['publish'] = $this->cmdResultIsOne($result['publish']) ? 1 : 0;
            log::add(__CLASS__, 'debug', __('Résultat envoi scripts : ', __FILE__) . json_encode($result));
        } else {
            log::add(__CLASS__, 'warning', __('Envoi de l archive distante impossible', __FILE__));
        }

        log::add(__CLASS__, 'info', __('Suppression des anciens log', __FILE__));
        $result['removeLog'] = $this->executeCmds($cmd . 'rm -f /tmp/hdsentinel_*');
        return $result;
    }

    public function test()
    {
        /**
         * Teste un envoie du XML complet, du coup, permet de générer les commandes
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         *       			|*Cette fonction ne retourne pas de valeur*|
         */
        $return = false;
      
        if ($this->getConfiguration('windows', false)) {
            return false;
        }
        $sshmanager = eqLogic::byId($this->getConfiguration('host_id'));
        if (is_object($sshmanager)) {
            if (!$this->ensureRemoteScripts()) {
                return false;
            }

            log::add(__CLASS__, 'info', __('Test de la commande', __FILE__));
            $plugin = plugin::byId(__CLASS__);
            $cmd = $this->getSudoCmd();
            $cmd .= 'bash ' . $this->getRemotePublishScript();
            $cmd .= ' -a ' . jeedom::getApiKey($plugin->getId());
            $cmd .= ' -i \'' . network::getNetworkAccess('internal') . '\'';
            $cmd .= ' -o xml';
            //$cmd .= ' >> /tmp/hdsentinel_log 2>&1 &"';
            $return = $this->executeCmds($cmd);
        }
        log::add(__CLASS__, 'info', __('Test de la commande : ', __FILE__) . (isset($cmd) ? $this->sanitizeCommandForLog($cmd) : '') . __(', résultat : ', __FILE__) . $return);
        return $return;
    }

    public function refresh()
    {
        /**
         * Teste un envoie du XML complet, du coup, permet de générer les commandes
         *
         * @param			|*Cette fonction ne retourne pas de valeur*|
         *       			|*Cette fonction ne retourne pas de valeur*|
         */
        log::add(__CLASS__, 'info', __('Refresh commande', __FILE__));
        if ($this->getConfiguration('windows', false)) {
            return false;
        }
        $cmd = "HDSENTINEL=$(which hdsentinel);
        if [ -f '/usr/local/bin/hdsentinel' ];
          then HDSENTINEL='/usr/local/bin/hdsentinel';
        elif [ -f '/usr/bin/hdsentinel' ]; then
          HDSENTINEL='/usr/bin/hdsentinel';
        elif [ -f '/bin/hdsentinel' ]; then
          HDSENTINEL='/bin/hdsentinel';
        elif [ -f '/sbin/hdsentinel' ]; then
          HDSENTINEL='/sbin/hdsentinel';
        fi; ";
        $cmd .= "DISK='';
        if [ -b '/dev/mmcblk0' ];
          then DISK='-dev /dev/mmcblk0';
        fi; ";
        $cmd .= $this->getSudoCmd();
        $cmd .= '$HDSENTINEL -dump -xml $DISK';
        $return = $this->executeCmds($cmd);

        try {
            $xml_action = new SimpleXMLElement($return);
            $result = json_decode(json_encode($xml_action), true);
          
            $sshmanager = eqLogic::byId($this->getConfiguration('host_id'));
            if (is_object($sshmanager)) {
                self::getApiXmlResult($result, $sshmanager->getConfiguration(sshmanager::CONFIG_HOST));
            }
        } catch (Exception $e) {
            log::add(__CLASS__, 'info', __('Erreur XML', __FILE__));
        }
        return $return;
    }

    public function getHtmlDisksFullResultManually()
    {
        if ($this->getConfiguration('windows', false)) {
            return false;
        }
        $cmd = "HDSENTINEL=$(which hdsentinel);
        if [ -f '/usr/local/bin/hdsentinel' ];
          then HDSENTINEL='/usr/local/bin/hdsentinel';
        elif [ -f '/usr/bin/hdsentinel' ]; then
          HDSENTINEL='/usr/bin/hdsentinel';
        elif [ -f '/bin/hdsentinel' ]; then
          HDSENTINEL='/bin/hdsentinel';
        elif [ -f '/sbin/hdsentinel' ]; then
          HDSENTINEL='/sbin/hdsentinel';
        fi; ";
        $cmd .= $this->getSudoCmd();
        $cmd .= '$HDSENTINEL -html -r /tmp/hdsentinel.html >/dev/null 2>&1 && cat /tmp/hdsentinel.html';
        return $this->executeCmds($cmd);
    }
}

class hdsentinelCmd extends cmd
{
    public static $_widgetPossibility = array('custom' => true);

    public function execute($_options = null)
    {
        $eqLogic = $this->getEqLogic();
        if ($this->getLogicalId() == '') {
            $paramaction = $this->getId();
        } else {
            $paramaction = $this->getLogicalId();
        }

        switch ($this->getType()) {
            case 'info':
                log::add('hdsentinel', 'debug', __('TYPE info ', __FILE__));
                break;
            case 'action':
                log::add('hdsentinel', 'debug', __('TYPE action ', __FILE__). $paramaction . ' avec option : '. json_encode($_options));
                if ($this->getLogicalId() == 'refresh') {
                    $eqLogic->refresh();
                }
                break;
            default:
                log::add('hdsentinel', 'debug', __('TYPE autre : ', __FILE__));
                break;
        }
        return true;
    }
}
