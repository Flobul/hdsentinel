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
        <legend><i class="fas fa-sliders-h"></i> {{Options du plugin}}</legend>
        <div class="form-group">
            <label class="col-lg-3 control-label">{{Mise à jour automatique des équipements distants}}
                <sup><i class="fas fa-question-circle tooltips" title="{{Lors d'une installation ou mise à jour du plugin, renvoie les scripts, réinstalle HD Sentinel et relance le cron sur les équipements distants compatibles.}}"></i></sup>
            </label>
            <div class="col-lg-2">
                <input type="checkbox" class="configKey form-control" data-l1key="autoUpdateRemote" />
            </div>
            <div class="col-lg-6">
                <span class="label label-warning">{{À activer seulement si les accès SSH/sudo sont fonctionnels sur les hôtes distants.}}</span>
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend><i class="fas fa-route"></i> {{Modes de fonctionnement}}</legend>
        <div class="alert alert-info">
            <strong>{{Linux / Raspberry / Synology}}</strong> : {{renseigner un hôte SSH Manager, envoyer les fichiers, installer HD Sentinel, puis lancer le cron distant.}}
            <br>
            <strong>{{Synology}}</strong> : {{le script détecte DSM via synoinfo.conf et mappe l'architecture Synology vers le binaire HD Sentinel compatible lorsque celui-ci existe. Les architectures PowerPC ne sont pas supportées par HD Sentinel Linux.}}
            <br>
            <strong>{{Windows}}</strong> : {{cocher Windows dans l'équipement, renseigner l'adresse IP, le port XML du service HD Sentinel et le mot de passe si nécessaire.}}
            <br>
            <strong>{{Installation manuelle}}</strong> : {{à utiliser si HD Sentinel est déjà installé et que vous souhaitez uniquement interroger l'équipement depuis Jeedom.}}
        </div>
    </fieldset>

    <fieldset>
    <legend><i class="icon loisir-darth"></i> {{Équipements distants}}</legend>
        <div class="table-responsive">
            <table class="table table-condensed table-striped">
                <thead>
                    <tr>
                        <th>{{Équipement}}</th>
                        <th>{{Mode}}</th>
                        <th>{{Hôte / IP}}</th>
                        <th>{{Version HD Sentinel}}</th>
                        <th>{{Dernière communication}}</th>
                        <th>{{Auto-actualisation}}</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    foreach (eqLogic::byType('hdsentinel') as $eqLogic) {
                        $mode = '{{Distant SSH}}';
                        $host = 'N/A';
                        if ($eqLogic->getConfiguration('windows', false)) {
                            $mode = '{{Windows XML}}';
                            $host = $eqLogic->getConfiguration('addressip', 'N/A') . ':' . $eqLogic->getConfiguration('portssh', 'N/A');
                        } else {
                            if ($eqLogic->getConfiguration('manually', false)) {
                                $mode = '{{Manuel}}';
                            }
                            $sshmanager = eqLogic::byId($eqLogic->getConfiguration('host_id'));
                            if (is_object($sshmanager)) {
                                $host = $sshmanager->getConfiguration(sshmanager::CONFIG_HOST, 'N/A');
                            }
                        }
                        echo '<tr>';
                        echo '<td><a href="' . $eqLogic->getLinkToConfiguration() . '">' . $eqLogic->getHumanName(true) . '</a></td>';
                        echo '<td><span class="label label-info">' . $mode . '</span></td>';
                        echo '<td>' . $host . '</td>';
                        echo '<td>' . $eqLogic->getConfiguration('Installed_version','N/A') . '</td>';
                        echo '<td>' . $eqLogic->getConfiguration('Current_Date_And_Time','N/A') . '</td>';
                        echo '<td>' . $eqLogic->getConfiguration('autorefresh','N/A') . '</td>';
                        echo '</tr>';
                    }
                ?>
                </tbody>
            </table>
        </div>
        <div class="alert alert-warning">
            {{Ordre recommandé pour un nouvel équipement distant : sauvegarder l'équipement, envoyer les fichiers, installer HD Sentinel, tester, puis lancer le cron. Consultez les logs de dépendances si l'installation reste bloquée.}}
        </div>
        <div class="form-group">
            <label class="col-lg-3"></label>
            <div class="col-lg-8">
                <a class="btn btn-warning allEqlogics" data-action="upload"><i class="fas fa-arrow-up"></i> {{Mettre à jour les fichiers sur tous}}</a>
                <a class="btn btn-warning allEqlogics" data-action="update"><i class="fas fa-download"></i> {{Installer HD Sentinel sur tous}}</a>
                <a class="btn btn-success allEqlogics" data-action="launch"><i class="fas fa-play"></i> {{Tout relancer}}</a>
                <a class="btn btn-danger allEqlogics" data-action="stop"><i class="fas fa-stop"></i> {{Tout arrêter}}</a>
                <a class="btn btn-danger allEqlogics" data-action="stopNdelete"><i class="fas fa-trash-alt"></i> {{Tout arrêter et supprimer}}</a>
            </div>
        </div>
    </fieldset>
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
