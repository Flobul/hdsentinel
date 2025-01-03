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

include_file('core', 'authentification', 'php');
if (!isConnect()) {
	include_file('desktop', '404', 'php');
	die();
}

$plugin = plugin::byId('hdsentinel');
sendVarToJS('version', hdsentinel::$_hdsentinelVersion);

?>
<form class="form-horizontal">
    <fieldset>
		<legend>
		<i class="fa fa-list-alt"></i> {{Général}}
		</legend>
		<div class="form-group">
				<?php
					$update = $plugin->getUpdate();
					if (is_object($update)) {
						$version = $update->getConfiguration('version');
						echo '<div class="col-lg-3">';
						echo '<div>';
						echo '<label>{{Branche}} :</label> '. $update->getConfiguration('version', 'stable');
						echo '</div>';
						echo '<div>';
						echo '<label>{{Source}} :</label> ' . $update->getSource();
						echo '</div>';
						echo '<div>';
						echo '<label>{{Version}} :</label> v' . ((hdsentinel::$_hdsentinelVersion)?hdsentinel::$_hdsentinelVersion:' '). ' (' . $update->getLocalVersion() . ')';
						echo '</div>';
						echo '</div>';
					}
				?>
				<div class="col-lg-5">
				<div>
					<i><a class="btn btn-success btn-xs" target="_blank" href="<?=$plugin->getDocumentation()?>"><i class="fas fa-book"></i><strong> Présentation et documentation du plugin</strong></a></i>
				</div>
				<div>
					<i> Les dernières actualités du plugin <a class="btn btn-label btn-xs" target="_blank" href="https://community.jeedom.com/t/plugin-hdsentinel-documentation-et-actualites/39994"><i class="icon jeedomapp-home-jeedom icon-hdsentinel"></i><strong> sur le community</strong></a>.</i>
				</div>
				<div>
					<i> Les dernières discussions autour du plugin <a class="btn btn-label btn-xs" target="_blank" href="https://community.jeedom.com/tags/plugin-hdsentinel"><i class="icon jeedomapp-home-jeedom icon-hdsentinel"></i><strong> sur le community</strong></a>.</i></br>
					<i> Pensez à mettre le tag <strong style="font-size:1.5em">#plugin-hdsentinel</strong> et à fournir les log dans les balises préformatées.</i>
				</div>
				<style>
					.icon-hdsentinel {
						font-size: 1.3em;
						color: #94CA02;
					}
				</style>
			</div>
		</div>
	</fieldset>

    <fieldset>
    <legend><i class="icon loisir-darth"></i> {{Distant}}</legend>
		<?php
            foreach (eqLogic::byType('hdsentinel') as $eqLogic) {
                if ($eqLogic->getConfiguration('manually', true)) {
                    echo '<div class="form-group">';
                    echo '<label class="col-lg-4 control-label">{{Version installée sur}} ' . $eqLogic->getName() . '</label>';
                    echo '<div class="col-lg-6">';
                    echo '<span>' . $eqLogic->getConfiguration('Installed_version','N/A') . ' </span>';
                    echo '<label> {{Dernière communication}} </label>';
                    echo '<span> (' . $eqLogic->getConfiguration('Current_Date_And_Time','N/A') . ')</span>';
                    echo '</div>';
                    echo '</div>';
                }
			}
		?>
    <div class="form-group">
        <label class="col-lg-3"></label>
        <div class="col-lg-8">
            <a class="btn btn-warning allEqlogics" data-action="upload"><i class="fas fa-arrow-up"></i> {{Mettre à jour les fichiers sur tous}}</a>
            <a class="btn btn-warning allEqlogics" data-action="update"><i class="fas fa-arrow-up"></i> {{Installer HD Sentinel sur tous}}</a>
            <a class="btn btn-success allEqlogics" data-action="launch"><i class="fas fa-play"></i> {{Tout relancer}}</a>
            <a class="btn btn-danger allEqlogics" data-action="stop"><i class="fas fa-stop"></i> {{Tout arrêter}}</a>
            <a class="btn btn-danger allEqlogics" data-action="stopNdelete"><i class="fas fa-stop"></i> {{Tout arrêter et supprimer}}</a>
        </div>
	</div>
</form>
<script>
document.querySelectorAll('.allEqlogics').forEach(function(button) {
    button.addEventListener('click', function() {
        var action = $(this).attr('data-action');
        domUtils.ajax({
            type: "POST",
            url: "plugins/hdsentinel/core/ajax/hdsentinel.ajax.php",
            data: {
                action: 'all',
                make: action
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
                if (data.state != 'ok') {
                    jeedomUtils.showAlert({message: data.result, level: 'danger'});
                    return;
                }
                console.log(data)
                jeedomUtils.showAlert({message: '{{Réussie}}', level: 'success'});
            }
        });
    });
});

</script>