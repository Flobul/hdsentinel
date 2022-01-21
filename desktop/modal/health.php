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

if (!isConnect('admin')) {
	throw new Exception('401 Unauthorized');
}

function updateValue($_cmd) {
    if (is_object($_cmd)) {
        $js = '<script>';
        $js .= "    jeedom.cmd.update[{$_cmd->getId()}] = function (_options) { ;";
        $js .= "        var cmd = $('.label[data-cmd_id=".$_cmd->getId()."]');";
        $js .= "        var unit = cmd.attr('data-unit') || '';";
        $js .= "        if(unit == '%') {;";
        $js .= "            var label = 'label label-success';";
        $js .= "            if(_options.display_value < parseFloat(75)){;";
        $js .= "                label = 'label label-danger';";
        $js .= "            } else if(_options.display_value < parseFloat(95)){;";
        $js .= "                label = 'label label-warning';";
        $js .= "            };";
        $js .= "            cmd.removeClass('label-success').removeClass('label-warning').removeClass('label-danger').addClass(label);";
        $js .= "        };";
        $js .= "        if(unit == '°C') {;";
        $js .= "            var label = 'label label-success';";
        $js .= "            if(_options.display_value > parseFloat(60)){;";
        $js .= "                label = 'label label-danger';";
        $js .= "            } else if(_options.display_value > parseFloat(50)){;";
        $js .= "                label = 'label label-warning';";
        $js .= "            };";
        $js .= "            cmd.removeClass('label-success').removeClass('label-warning').removeClass('label-danger').addClass(label);";
        $js .= "        };";
        $js .= "        cmd.text(_options.display_value + ' ' + unit)};";
        $js .= "    jeedom.cmd.update[{$_cmd->getId()}]({ display_value: '".$_cmd->execCmd()."'});";
        $js .= '</script>';
        echo $js;
    }
}

$eqLogics = hdsentinel::byType('hdsentinel');
?>
<div id='div_hdsentinelAlert' style="display: none;"></div>
<div class="panel-group" id="accordionHdsentinel">
    <?php
    foreach ($eqLogics as $eqLogic) {
        $cmds = $eqLogic->getCmd();
        $nbDisks = $eqLogic->getNbDisksByEqLogic();
        $opacity = ($eqLogic->getIsEnable()) ? '' : ' style="opacity: 0.4;';
    ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <?php
                    echo '<a class="accordion-toggle" data-toggle="collapse" data-parent="" href="#node_' . $eqLogic->getId() . '"' . $opacity . '>';
                    echo $eqLogic->getHumanName(true) . ' - <span class="label label-info">' . $eqLogic->getConfiguration('OS_Version') . '</span>  - <span class="label label-info">' . $eqLogic->getConfiguration('Installed_version') . '</span>  - <span class="label label-info">Dernière mise à jour ' . $eqLogic->getConfiguration('Current_Date_And_Time') . '</span>';  
                    echo '</a>';
                    ?>
                </h3>
            </div>

            <div id="node_<?= $eqLogic->getId() ?>" class="panel-collapse collapse in" role="tabpanel">
                <div class="panel-body">
                <?php
                for($i=0 ; $i < $nbDisks; $i++) {
                    $nbName=($nbDisks < 1)?'':' '.$i;
                ?>
                    <div class="col-sm-3" style="min-height:300px">
                            <div>
                                <?php
                                    $hddCmd = $eqLogic->getCmd('info','Hard_Disk_Device'.$nbName);
                                    if (is_object($hddCmd)) {
                                ?>
                                <label class="control-label"><?= $hddCmd->getName() ?></label>
                                <span class="pull-right label" data-cmd_id='<?= $hddCmd->getId() ?>' data-unit=''></span>
                                <?php
                                    }
                                ?>
                            </div>
                            <div>
                                <?php
                                    $sizeCmd = $eqLogic->getCmd('info','Total_Size'.$nbName);
                                    if (is_object($sizeCmd)) {
                                ?>
                                <label class="control-label"><?= $sizeCmd->getName() ?></label>
                                <span class="pull-right label label-info cursor history" data-cmd_id='<?= $sizeCmd->getId() ?>' data-unit='Mb'></span>
                                <?php
                                    }
                                ?>
                            </div>
                            <div>
                                <?php
                                    $timeCmd = $eqLogic->getCmd('info','Power_on_time'.$nbName);
                                    if (is_object($timeCmd)) {
                                ?>
                                <label class="control-label"><?= $timeCmd->getName() ?></label>
                                <span class="pull-right label label-info" data-cmd_id='<?= $timeCmd->getId() ?>' data-unit=''></span>
                                <?php
                                    }
                                ?>
                            </div>
                            <div>
                                <?php
                                    $remainCmd = $eqLogic->getCmd('info','Estimated_remaining_lifetime'.$nbName);
                                    if (is_object($remainCmd)) {
                                ?>
                                <label class="control-label"><?= $remainCmd->getName() ?></label>
                                <span class="pull-right label label-info" data-cmd_id='<?= $remainCmd->getId() ?>' data-unit=''></span>
                                <?php
                                    }
                                ?>
                            </div>
                            <div>
                                <?php
                                    $perfCmd = $eqLogic->getCmd('info','Performance'.$nbName);
                                    if (is_object($perfCmd)) {
                                ?>
                                <label class="control-label"><?= $perfCmd->getName() ?></label>
                                <span class="pull-right label label-info cursor history" data-cmd_id='<?= $perfCmd->getId() ?>' data-unit='<?= $perfCmd->getUnite() ?>'></span>
                                <?php
                                    }
                                ?>
                            </div>
                            <div>
                                <?php
                                    $healthCmd = $eqLogic->getCmd('info','Health'.$nbName);
                                    if (is_object($healthCmd)) {
                                ?>
                                <label class="control-label"><?= $healthCmd->getName() ?></label>
                                <span class="pull-right label label-info cursor history" data-cmd_id='<?= $healthCmd->getId() ?>' data-unit='<?= $healthCmd->getUnite() ?>'></span>
                                <?php
                                    }
                                ?>
                            </div>
                            <div>
                                <?php
                                    $tempCmd = $eqLogic->getCmd('info','Current_Temperature'.$nbName);
                                    if (is_object($tempCmd)) {
                                ?>
                                <label class="control-label"><?= $tempCmd->getName() ?></label>
                                <span class="pull-right label label-info cursor history" data-cmd_id='<?= $tempCmd->getId() ?>' data-unit='<?= $tempCmd->getUnite() ?>'></span>
                                <?php
                                    }
                                ?>
                            </div>
                            <div>
                                <?php
                                    $maxtempCmd = $eqLogic->getCmd('info','Maximum_temperature_during_entire_lifespan'.$nbName);
                                    if (is_object($maxtempCmd)) {
                                ?>
                                <label class="control-label"><?= $maxtempCmd->getName() ?></label>
                                <span class="pull-right label label-info cursor history" data-cmd_id='<?= $maxtempCmd->getId() ?>' data-unit='<?= $maxtempCmd->getUnite() ?>'></span>
                                <?php
                                    }
                                ?>
                            </div>
                            <div>
                                <?php
                                    $writCmd = $eqLogic->getCmd('info','Lifetime_writes'.$nbName);
                                    if (is_object($writCmd)) {
                                ?>
                                <label class="control-label"><?= $writCmd->getName() ?></label>
                                <span class="pull-right label label-info cursor history" data-cmd_id='<?= $writCmd->getId() ?>' data-unit='<?= $writCmd->getUnite() ?>'></span>
                                <?php
                                    }
                                ?>
                            </div>
                            <div>
                                <?php
                                    $descCmd = $eqLogic->getCmd('info','Description'.$nbName);
                                    if (is_object($descCmd)) {
                                ?>
                                <label class="control-label"><?= $descCmd->getName() ?></label>
                                <span class="pull-right label label-info" data-cmd_id='<?= $descCmd->getId() ?>' data-unit='' style="white-space:break-spaces;word-break:break-word;"></span>
                                <?php
                                    }
                                ?>
                            </div>
                    </div>
                    <?php
                      updateValue($hddCmd);
                      updateValue($remainCmd);
                      updateValue($perfCmd);
                      updateValue($healthCmd);
                      updateValue($sizeCmd);
                      updateValue($tempCmd);
                      updateValue($timeCmd);
                      updateValue($descCmd);
                      updateValue($maxtempCmd);
                      updateValue($writCmd);
                    }
                  ?>
                </div>
            </div>
        </div>
    <?php
    }
    ?>
  
<table class="table table-condensed tablesorter" id="table_healthHdsentinel">
	<thead>
		<tr>
			<th>{{Module}}</th>
			<th>{{IP}}</th>
			<th>{{Version HDSentinel}}</th>
			<th>{{Démarré depuis (s)}}</th>
			<th>{{Démarré depuis}}</th>
			<th>{{Dernière communication}}</th>
			<th>{{Date de création}}</th>
		</tr>
	</thead>
	<tbody>
	 <?php
foreach ($eqLogics as $eqLogic) {
	echo '<tr><td><a href="' . $eqLogic->getLinkToConfiguration() . '" style="text-decoration: none;">' . $eqLogic->getHumanName(true) . '</a></td>';
	echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getConfiguration('addressip') . '</span></td>';

	echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getConfiguration('Installed_version') . '</span></td>';

    $exp = explode(' (',$eqLogic->getConfiguration('Uptime'));
	echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $exp[0] . '</span></td>';

    $exp2 = str_replace(')','',implode(' ', explode(',',$exp[1])));
	echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $exp2 . '</span></td>';

	echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getStatus('lastCommunication','0') . '</span></td>';
	echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getConfiguration('createtime') . '</span></td></tr>';
}
?>
	</tbody>
</table>
  <script>
    $('#accordionHdsentinel').off('click', '.history').on('click', '.history', function (event) {
        $('#md_modal2').dialog({title: "{{Historique}}"}).load('index.php?v=d&modal=cmd.history&id=' + $(this).data('cmd_id')).dialog('open')
    });
  </script>