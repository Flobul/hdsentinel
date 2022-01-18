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
$eqLogics = hdsentinel::byType('hdsentinel');
?>

<table class="table table-condensed tablesorter" id="table_healthjailbreak">
<span class='pull-right'>
    <a class="btn btn-default pull-right" id="bt_refreshHealth"><i class="fas fa-sync-alt"></i> {{Rafraîchir}}</a>
</span>
	<thead>
		<tr>
			<th>{{Module}}</th>
			<th>{{ID}}</th>
			<th>{{IP}}</th>
			<th>{{Statut}}</th>
			<th>{{Batterie}}</th>
			<th>{{Température CPU}}</th>
			<th>{{Charge système}}</th>
			<th>{{Dernière communication}}</th>
			<th>{{Date de création}}</th>
		</tr>
	</thead>
	<tbody>
	 <?php
foreach ($eqLogics as $eqLogic) {
	echo '<tr><td><a href="' . $eqLogic->getLinkToConfiguration() . '" style="text-decoration: none;">' . $eqLogic->getHumanName(true) . '</a></td>';
	echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getId() . '</span></td>';
	echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getConfiguration('addressip') . '</span></td>';
	$status = $eqLogic->getCmd('info', 'cnx_ssh');
  	if (is_object($status)) {
		if ($status->execCmd() == 'OK') {
          	$present = '<span class="label label-success" style="font-size : 1em; cursor : default;">{{Présent}}</span>';
        }
		else {
			$present = '<span class="label label-danger" style="font-size : 1em; cursor : default;">{{Absent}}</span>';
        }
	}
	echo '<td>' . $present . '</td>';

	$batterie = $eqLogic->getCmd('info', 'battery');
	$branche = $eqLogic->getCmd('info', 'plugged');

  	if (is_object($batterie)) {
      if ($batterie->execCmd() < 30 && $batterie->execCmd() != '') {
          $battery_status = '<span class="label label-danger" style="font-size : 1em;">' . $batterie->execCmd() . '%</span>';
      } elseif ($batterie->execCmd() < 60 && $batterie->execCmd() != '') {
          $battery_status = '<span class="label label-warning" style="font-size : 1em;">' . $batterie->execCmd() . '%</span>';
      } elseif ($batterie->execCmd() > 60 && $batterie->execCmd() != '') {
          $battery_status = '<span class="label label-success" style="font-size : 1em;">' . $batterie->execCmd() . '%</span>';
      }
    }
  	if (is_object($branche)) {
      if ($branche->execCmd() == 1) {
          $branche_status = '<span class="label label-success" style="font-size : 1em;" title="{{Secteur}}"><i class="icon techno-charging"></i></span>';
      } else $branche_status = '<span class="label label-warning" style="font-size : 1em;" title="{{Secteur}}"><i class="icon techno-low2"></i></span>';
	}

	echo '<td>' . $branche_status . $battery_status . '</td>';

  	$cputemp = $eqLogic->getCmd('info', 'cpu_temp');
  	if (is_object($cputemp)) {
      if ($cputemp->execCmd() < 35 && $cputemp->execCmd() != '') {
          $cputemp_status = '<span class="label label-success" style="font-size : 1em;">' . $cputemp->execCmd() . '°C</span>';
      } elseif ($cputemp->execCmd() < 45 && $cputemp->execCmd() != '') {
          $cputemp_status = '<span class="label label-warning" style="font-size : 1em;">' . $cputemp->execCmd() . '°C</span>';
      } elseif ($cputemp->execCmd() > 45 && $cputemp->execCmd() != '') {
          $cputemp_status = '<span class="label label-danger" style="font-size : 1em;">' . $cputemp->execCmd() . '°C</span>';
      } else {
          $cputemp_status = '<span class="label label-primary" style="font-size : 1em;">-</span>';
      }
    }
  	echo '<td>' . $cputemp_status . '</td>';

    $loadavg1 = $eqLogic->getCmd('info', 'loadavg1mn');
    $loadavg5 = $eqLogic->getCmd('info', 'loadavg5mn');
    $loadavg15 = $eqLogic->getCmd('info', 'loadavg15mn');

	$loadavg1mnvertinfa = $eqLogic->getConfiguration('loadavg1mnvertinfa');
	$loadavg5mnvertinfa = $eqLogic->getConfiguration('loadavg5mnvertinfa');
	$loadavg15mnvertinfa = $eqLogic->getConfiguration('loadavg15mnvertinfa');
	$loadavg1mnorangede = $eqLogic->getConfiguration('loadavg1mnorangede');
	$loadavg5mnorangede = $eqLogic->getConfiguration('loadavg5mnorangede');
	$loadavg15mnorangede = $eqLogic->getConfiguration('loadavg15mnorangede');
 	$loadavg1mnorangea = $eqLogic->getConfiguration('loadavg1mnorangea');
	$loadavg5mnorangea = $eqLogic->getConfiguration('loadavg5mnorangea');
	$loadavg15mnorangea = $eqLogic->getConfiguration('loadavg15mnorangea');
	$loadavg1mnrougesupa = $eqLogic->getConfiguration('loadavg1mnrougesupa');
	$loadavg5mnrougesupa = $eqLogic->getConfiguration('loadavg5mnrougesupa');
	$loadavg15mnrougesupa = $eqLogic->getConfiguration('loadavg15mnrougesupa');

	$absent = $eqLogic->getCmd('info', 'cnx_ssh');

  	if (is_object($loadavg1) && $absent->execCmd() == 'OK') {
      if ($loadavg1->execCmd() < $loadavg1mnvertinfa) {
          $chargesys1min = '<span class="label label-success" style="font-size : 1em;">' . $loadavg1->execCmd() . '</span>';
      } elseif ($loadavg1->execCmd() >= $loadavg1mnorangede && $loadavg1->execCmd() <= $loadavg1mnorangea) {
          $chargesys1min = '<span class="label label-warning" style="font-size : 1em;">' . $loadavg1->execCmd() . '</span>';
      } elseif ($loadavg1->execCmd() > $loadavg1mnrougesupa && $loadavg1mnrougesupa !== '') {
          $chargesys1min = '<span class="label label-danger" style="font-size : 1em;">' . $loadavg1->execCmd() . '</span>';
      } else {
          $chargesys1min = '<span class="label label-primary" style="font-size : 1em;">' . $loadavg1->execCmd() . '</span>';
      }
    } else {
		$chargesys1min = '<span class="label label-primary" style="font-size : 1em;">-</span>';
    }

  	if (is_object($loadavg5) && $absent->execCmd() == 'OK') {
      if ($loadavg5->execCmd() < $loadavg5mnvertinfa) {
          $chargesys5min = '<span class="label label-success" style="font-size : 1em;">' . $loadavg5->execCmd() . '</span>';
      } elseif ($loadavg5->execCmd() >= $loadavg5mnorangede && $loadavg5->execCmd() <= $loadavg5mnorangea) {
          $chargesys5min = '<span class="label label-warning" style="font-size : 1em;">' . $loadavg5->execCmd() . '</span>';
      } elseif ($loadavg5->execCmd() > $loadavg5mnrougesupa && $loadavg5mnrougesupa !== '') {
          $chargesys5min = '<span class="label label-danger" style="font-size : 1em;">' . $loadavg5->execCmd() . '</span>';
      } else {
          $chargesys5min = '<span class="label label-primary" style="font-size : 1em;">' . $loadavg5->execCmd() . '</span>';
      }
    } else {
		$chargesys5min = '<span class="label label-primary" style="font-size : 1em;">-</span>';
    }
  
    if (is_object($loadavg15) && $absent->execCmd() == 'OK') {
      if ($loadavg15->execCmd() < $loadavg15mnvertinfa) {
          $chargesys15min = '<span class="label label-success" style="font-size : 1em;">' . $loadavg15->execCmd() . '</span>';
      } elseif ($loadavg15->execCmd() >= $loadavg15mnorangede && $loadavg15->execCmd() <= $loadavg15mnorangea) {
          $chargesys15min = '<span class="label label-warning" style="font-size : 1em;">' . $loadavg15->execCmd() . '</span>';
      } elseif ($loadavg15->execCmd() > $loadavg15mnrougesupa && $loadavg15mnrougesupa !== '') {
          $chargesys15min = '<span class="label label-danger" style="font-size : 1em;">' . $loadavg15->execCmd() . '</span>';
      } else {
          $chargesys15min = '<span class="label label-primary" style="font-size : 1em;">' . $loadavg15->execCmd() . '</span>';
      }
    } else {
      $chargesys15min = '<span class="label label-primary" style="font-size : 1em;">-</span>';
    }

  	echo '<td>' . $chargesys1min .'-'. $chargesys5min .'-'. $chargesys15min .'</td>';

	echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getCache('lastupdate','0') . '</span></td>';
	echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getConfiguration('createtime') . '</span></td></tr>';
}
?>
	</tbody>
</table>

<?php include_file('desktop', 'health', 'js', 'hdsentinel');?>