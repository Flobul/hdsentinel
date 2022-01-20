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

<ul class="nav nav-tabs" role="tablist">
	    <?php
            $i = 0;
            foreach ($eqLogics as $eqLogic) {
                $tabActive = ($i<1)?'class="active"':null;
                echo '<li role="presentation" '.$tabActive.'><a href="#'.$eqLogic->getId().'" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> '.$eqLogic->getName().'</a></li>';
                $i++;
            }
        ?>
</ul>

<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
    <?php
        $i = 0;
        foreach ($eqLogics as $eqLogic) {
            $tabActive = ($i<1)?'active':null;
            echo '<div role="tabpanel" class="tab-pane ' .$tabActive.'" id="'.$eqLogic->getId().'">';
            $html_result = $eqLogic->getHtmlDisksFullResult();
            $data_path = dirname(__FILE__) . '/../../core/data';
            usleep(10);
            $html = file_get_contents($data_path . '/hdsentinel_'.$eqLogic->getId().'.html');
            if ($html) {
                echo $html;
            }
            echo '</div>';
            $i++;
        }
    ?>
</div>