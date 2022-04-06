<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}

$plugin = plugin::byId('hdsentinel');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());

?>
<style>
  .classinfoEqlogic {
      border-radius:10px;
      padding:1px 3px;
      font-size:.75em;
			font-weight:900;
      position:absolute;
      margin-left:57px;
      color:white;
  }
</style>

<div class="row row-overflow">
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fa fa-cog"></i> {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoPrimary" data-action="add">
				<i class="fas fa-plus-circle"></i>
				<br>
				<span>{{Ajouter}}</span>
			</div>

			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br>
				<span>{{Configuration}}</span>
			</div>

			<div class="cursor logoSecondary" id="bt_healthSentinel">
				<i class="fas fa-medkit"></i>
				<br>
				<span>{{Santé}}</span>
			</div>

			<div class="cursor logoSecondary" id="bt_pageSentinel">
				<i class="fas fa-hdd"></i>
				<br>
				<span>{{Page Html}}</span>
			</div>

			<div class="cursor logoSecondary" id="bt_hdsentinelDocumentation" data-location="<?=$plugin->getDocumentation()?>">
				<i class="icon loisir-livres"></i>
				<br><br>
				<span>{{Documentation}}</span>
			</div>
		</div>


		<legend><i class="fas fa-table"></i> {{Mes serveurs Hard Disk Sentinel}}</legend>
		<?php
		if (count($eqLogics) == 0) {
			echo '<br/><div class="text-center" style="font-size:1.2em;font-weight:bold;">{{Aucun équipement Template n\'est paramétré, cliquer sur "Ajouter" pour commencer}}</div>';
		} else {
			// Champ de recherche
			echo '<div class="input-group" style="margin:5px;">';
			echo '<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic"/>';
			echo '<div class="input-group-btn">';
			echo '<a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>';
			echo '<a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>';
			echo '</div>';
			echo '</div>';
			// Liste des équipements du plugin
			echo '<div class="eqLogicThumbnailContainer">';
			foreach ($eqLogics as $eqLogic) {
                $nbDisks = $eqLogic->getNbDisksByEqLogic();
                $pourcentHealth = 0;
                for($i=0 ; $i < $nbDisks; $i++) {
                    $nbName=($nbDisks < 1)?'':' '.$i;
                    $health = $eqLogic->getCmd('info','Health'.$nbName);
                    if (is_object($health)) {
                        $pourcentHealth = ( intval($pourcentHealth) + $health->execCmd() );
                    }
                }
                $pourcentHealth = ( intval($pourcentHealth) / intval($nbDisks) );
                $colorHealth = ($pourcentHealth<90)?($pourcentHealth<75)?'red':'orange':'green';
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';

				echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
                echo '<span class="classinfoEqlogic label-info" style="margin-top:19px;" title="{{Nombre de disques}}">'.$nbDisks.'</span>';
                echo (is_nan($pourcentHealth)) ? '' : '<span class="classinfoEqlogic" style="margin-top:63px;background:'.$colorHealth.';" title="{{Santé (moyenne)}}">'.$pourcentHealth.' %</span>';
				echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
				echo '<br>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '</div>';
			}
			echo '</div>';
		}
		?>

	</div>
	<div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex;">
			<span class="input-group-btn">
				<a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
				</a><a class="btn btn-sm btn-default eqLogicAction" data-action="copy"><i class="fas fa-copy"></i><span class="hidden-xs"> {{Dupliquer}}</span>
				</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
				</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}
				</a>
			</span>
		</div>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-list"></i> {{Commandes}}</a></li>
		</ul>

		<div class="tab-content">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<form class="form-horizontal">
					<fieldset>
						<div class="col-lg-6">
							<legend><i class="fas fa-wrench"></i> {{Paramètres généraux}}</legend>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
								<div class="col-sm-7">
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}" />
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Objet parent}}</label>
								<div class="col-sm-7">
									<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
										<option value="">{{Aucun}}</option>
										<?php
										$options = '';
										foreach ((jeeObject::buildTree(null, false)) as $object) {
											$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
										}
										echo $options;
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Catégorie}}</label>
								<div class="col-sm-7">
									<?php
									foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
										echo '<label class="checkbox-inline">';
										echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
										echo '</label>';
									}
									?>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Options}}</label>
								<div class="col-sm-7">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked />{{Activer}}</label>
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked />{{Visible}}</label>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Adresse IP}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Renseignez l'adresse IP}}"></i></sup>
								</label>
								<div class="col-sm-5">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="addressip" type="text" placeholder="{{saisir l'adresse IP}}">
								</div>
								<div class="col-sm-1 automatically">
									<input type="checkbox" class="eqLogicAttr form-control" id="windows" data-l1key="configuration" data-l2key="windows" />
								</div>
								<label class="col-sm-2 control-label automatically" style="text-align:left;">{{Windows}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Cochez cette case si l'appareil fait tourner HDSentinel sous Windows (voir la documentation pour l'installation)}}"></i></sup>
								</label>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Port SSH}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Renseignez le port SSH}}"></i></sup>
								</label>
								<div class="col-sm-7">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="portssh" type="text" placeholder="{{saisir le port SSH}}">
								</div>
							</div>
							<div class="form-group underLinux">
								<label class="col-sm-3 control-label">{{Identifiant}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Renseignez l'identifiant}}"></i></sup>
								</label>
								<div class="col-sm-7">
									<input class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="user" type="text" placeholder="{{saisir l'identifiant}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label"> {{Mot de passe}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Renseignez le mot de passe}}"></i></sup>
								</label>
								<div class="col-sm-7">
									<input type="text" class="eqLogicAttr form-control inputPassword" data-l1key="configuration" data-l2key="password" />
								</div>
							</div>
							<div class="form-group" style="display:none">
								<label class="col-sm-3 control-label">{{Widget équipement}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Cochez la case pour utiliser le widget de l'appareil}}"></i></sup>
								</label>
								<div class="col-sm-7">
									<input type="checkbox" class="eqLogicAttr form-control" id="widgetTemplate" data-l1key="configuration" data-l2key="widgetTemplate" />
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Installation manuelle}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Cocher la case si vous installez manuellement le paquet HDSentinel (pas de cron, gestion via commande refresh)}}"></i></sup>
								</label>
								<div class="col-sm-7">
									<input type="checkbox" class="eqLogicAttr form-control" id="manually" data-l1key="configuration" data-l2key="manually" />
								</div>
							</div>
							<legend class="manually"><i class="fas fa-rocket"></i> {{Gestion distante}}</legend>

							<div class="form-group manually">
								<label class="col-sm-3 control-label">{{Auto-actualisation}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Fréquence de rafraîchissement du cron}}</br>{{Pensez à sauvegarder, puis Arrêter et supprimer le cron et Lancer.}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<div class="input-group">
										<input type="text" class="eqLogicAttr form-control roundedLeft" data-l1key="configuration" data-l2key="autorefresh" placeholder="{{Cliquer sur ? pour afficher l'assistant cron}}">
										<span class="input-group-btn">
											<a class="btn btn-default cursor jeeHelper roundedRight" data-helper="cron" title="Assistant cron">
												<i class="fas fa-question-circle"></i>
											</a>
										</span>
									</div>
								</div>
							</div>

                            <div class="form-group manually">
                                <label class="col-sm-3 control-label">{{Envoi des fichiers nécessaires}}</label>
                                <div class="col-sm-2">
                                    <a class="btn btn-warning hdsentinelAction" data-action="sendFiles"><i class="fas fa-upload"></i> {{Envoyer}}</a>
                                </div>
                                <label class="col-sm-3 control-label">{{Installation des dépendances}}</label>
                                <div class="col-sm-2">
                                    <a class="btn btn-warning hdsentinelAction" data-action="installDependancy"><i class="fas fa-spinner"></i> {{Installer}}</a>
                                </div>
                                <div class="col-sm-2">
                                    <a class="btn btn-success hdsentinelAction" data-action="getLogDependancy"><i class="far fa-file-alt"></i> {{Log}}</a>
                                </div>
                            </div>

                            <div class="form-group manually">
                                <label class="col-sm-3 control-label">{{Gestion du cron distant}}</label>
                                <div class="col-sm-1">
                                    <a class="hdsentinelAction" data-action="checkremotecron"></a>
                                </div>
                                <div class="col-sm-2">
                                    <a class="btn btn-success hdsentinelAction" data-action="launchCron"><i class="fas fa-play"></i> {{Lancer}}</a>
                                </div>
                                <div class="col-sm-4">
                                    <a class="btn btn-danger hdsentinelAction" data-action="stopCron"><i class="fas fa-stop"></i> {{Arrêter}}</a>
                                    <a class="btn btn-danger hdsentinelAction" data-action="removeCron"><i class="fas fa-trash-alt"></i> {{Arrêter et supprimer}}</a>
                                </div>
                                <div class="col-sm-2">
                                    <a class="btn btn-success hdsentinelAction" data-action="getLog"><i class="far fa-file-alt"></i> {{Log}}</a>
                                </div>
                            </div>
                            <div class="form-group manually">
                                <label class="col-sm-3 control-label">{{Gestion du cron automatique}}</label>
                                <div class="col-sm-2">
                                    <a class="btn btn-danger hdsentinelAction" data-action="changeAutoModeRemote"></a>
                                    <input type="hidden" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="remoteDaemonAuto"/>
                                </div>
                            </div>
                            <div class="form-group manually">
                                <label class="col-sm-3 control-label">{{Tester}}</label>
                                <div class="col-sm-2">
                                    <a class="btn btn-success hdsentinelAction" data-action="test"><i class="far fa-file-alt"></i> {{Tester}}</a>
                                </div>
                            </div>

							<div class="form-group" style="display:none">
								<label class="col-sm-2 control-label">{{URL de retour}}</label>
								<div class="col-sm-9 callback">
									<span>
										<?php
                                            echo network::getNetworkAccess('internal') . '/core/api/jeeApi.php?plugin=hdsentinel&apikey=' . jeedom::getApiKey($plugin->getId()) . '&type=cmd&id=#cmd_id#&value=#value#';
										?>
									</span>
								</div>
							</div>
						</div>

						<div class="col-lg-6">
							<legend><i class="fas fa-exclamation-circle"></i> {{Informations}}</legend>
							<table id="table_infoseqlogic" class="col-sm-7 table-bordered table-condensed" style="border-radius: 10px;">
								<thead>
								</thead>
								<tbody>
								</tbody>
							</table>
						</div>
						<div class="col-lg-6">
							<div class="form-group">
								<div class="col-sm-10">
									<center>
										<img src="core/img/no_image.gif" data-original=".jpg" id="img_device" class="img-responsive" style="max-height : 300px;" onerror="this.src='plugins/hdsentinel/plugin_info/hdsentinel_icon.png'" />
									</center>
								</div>
							</div>
						</div>
					</fieldset>
				</form>
			</div>
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<a class="btn btn-default btn-sm pull-right cmdAction" data-action="add" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Ajouter une commande}}</a>
				<br /><br />
				<div class="table-responsive">
					<table id="table_cmd" class="table table-bordered table-condensed">
						<thead>
							<tr>
								<th style="width: 65px;">{{Id}}</th>
								<th>{{Nom}}</th>
								<th>{{Afficher/Historiser}}</th>
								<th>{{Configuration}}</th>
								<th>{{Action}}</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<?php include_file('desktop', 'hdsentinel', 'js', 'hdsentinel'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
