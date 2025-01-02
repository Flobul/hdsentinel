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

document.getElementById('div_hdsentinel').addEventListener('click', function(event) {
    var _target = null
    if (_target = event.target.closest('#bt_hdsentinelDocumentation')) {
        window.open(_target.getAttribute('data-location'), '_blank');
    }
    if (_target = event.target.closest('.pluginAction[data-action=openLocation]')) {
        window.open(_target.getAttribute('data-location'), '_blank');
	}
    if (_target = event.target.closest('#bt_healthSentinel')) {
        jeeDialog.dialog({
            title: '{{Santé Hard Disk Sentinel}}',
            contentUrl: 'index.php?v=d&plugin=hdsentinel&modal=health'
        });
    }
    if (_target = event.target.closest('#bt_pageSentinel')) {
        jeeDialog.dialog({
            title: '{{Page html Hard Disk Sentinel}}',
            contentUrl: 'index.php?v=d&plugin=hdsentinel&modal=page'
        });
    }
    if (_target = event.target.closest('.hdsentinelAction[data-action=sendFiles]')) {
        domUtils.ajax({
            type: "POST",
            url: "plugins/hdsentinel/core/ajax/hdsentinel.ajax.php",
            data: {
                action: "sendFile",
                id: document.querySelector('.eqLogicAttr[data-l1key=id]').value
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // data.result: {publish: "1", install: "1"}}
                if (data.state != 'ok') {
                    jeedomUtils.showAlert({message: data.result, level: 'danger'});
                    return;
                }
                if (data.result != '') {
                    if (data.result.publish != "1" || data.result.install != "1") {
                        jeedomUtils.showAlert({message: '{{Envoi des scripts échoué :}}'+JSON.stringify(data.result), level: 'danger'});
                        return;
                    }
                    jeedomUtils.showAlert({message: '{{Envoi des scripts réalisé avec succès.}}', level: 'success'});
                }
            }
        });
    }
    if (_target = event.target.closest('.hdsentinelAction[data-action=installDependancy]')) {
        domUtils.ajax({
            type: "POST",
            url: "plugins/hdsentinel/core/ajax/hdsentinel.ajax.php",
            data: {
                action: "installDependancy",
                id: document.querySelector('.eqLogicAttr[data-l1key=id]').value
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) {
                if (data.state != 'ok') {
                    jeedomUtils.showAlert({message: data.result, level: 'danger'});
                    return;
                }
                jeedomUtils.showAlert({message: '{{Installation lancée}}', level: 'success'});
            }
        });
    }
    if (_target = event.target.closest('.hdsentinelAction[data-action=getLogDependancy]')) {
        domUtils.ajax({
            type: "POST",
            url: "plugins/hdsentinel/core/ajax/hdsentinel.ajax.php",
            data: {
                action: "getLogDependancy",
                id: document.querySelector('.eqLogicAttr[data-l1key=id]').value
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) {
                if (data.state != 'ok') {
                    jeedomUtils.showAlert({message: data.result, level: 'danger'});
                    return;
                }
            }
        });
    }
    if (_target = event.target.closest('.hdsentinelAction[data-action=getLog]')) {
        domUtils.ajax({
            type: "POST",
            url: "plugins/hdsentinel/core/ajax/hdsentinel.ajax.php",
            data: {
                action: "getLog",
                id: document.querySelector('.eqLogicAttr[data-l1key=id]').value
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) {
                if (data.state != 'ok') {
                    jeedomUtils.showAlert({message: data.result, level: 'danger'});
                    return;
                }
            }
        });
    }
    if (_target = event.target.closest('.hdsentinelAction[data-action=launchCron]')) {
        var id = document.querySelector('.eqLogicAttr[data-l1key=id]').value;
        domUtils.ajax({
            type: "POST",
            url: "plugins/hdsentinel/core/ajax/hdsentinel.ajax.php",
            data: {
                action: "launchCron",
                id: id
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) {
                if (data.state != 'ok') {
                    jeedomUtils.showAlert({message: data.result, level: 'danger'});
                    return;
                }
                refreshStatusMode(id);
                if ((1 - data.result) != "1") {
                    jeedomUtils.showAlert({message: '{{Lancement du cron échoué :}}'+data.result, level: 'danger'});
                    return;
                }
                jeedomUtils.showAlert({message: '{{Lancement du cron réussi.}}', level: 'success'});
            }
        });
    }
    if (_target = event.target.closest('.hdsentinelAction[data-action=removeCron]')) {
        var id = document.querySelector('.eqLogicAttr[data-l1key=id]').value;
        domUtils.ajax({
            type: "POST",
            url: "plugins/hdsentinel/core/ajax/hdsentinel.ajax.php",
            data: {
                action: "removeCron",
                id: id
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) {
                if (data.state != 'ok') {
                    jeedomUtils.showAlert({message: data.result, level: 'danger'});
                    return;
                }
                refreshStatusMode(id);
                if ((1 - data.result) != '1') {
                    jeedomUtils.showAlert({message: '{{Suppression du cron échouée :}}'+data.result, level: 'danger'});
                    return;
                }
                jeedomUtils.showAlert({message: '{{Suppression du cron réussie.}}', level: 'success'});
            }
        });
    }
    if (_target = event.target.closest('.hdsentinelAction[data-action=stopCron]')) {
        var id = document.querySelector('.eqLogicAttr[data-l1key=id]').value;
        domUtils.ajax({
            type: "POST",
            url: "plugins/hdsentinel/core/ajax/hdsentinel.ajax.php",
            data: {
                action: "stopCron",
                id: id
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) {
                if (data.state != 'ok') {
                    jeedomUtils.showAlert({message: data.result, level: 'danger'});
                    return;
                }
                refreshStatusMode(id);
                if ((1 - data.result) != '1') {
                    jeedomUtils.showAlert({message: '{{Arrêt du cron échoué :}}'+data.result, level: 'danger'});
                    return;
                }
                jeedomUtils.showAlert({message: '{{Arrêt du cron réussie.}}', level: 'success'});
            }
        });
    }
    if (_target = event.target.closest('.hdsentinelAction[data-action=changeAutoModeRemote]')) {
        var auto = 1 -  document.querySelector('.eqLogicAttr[data-l2key="remoteDaemonAuto"]').value;
        document.querySelector('.eqLogicAttr[data-l2key="remoteDaemonAuto"]').innerValue = auto;
        document.querySelector('.eqLogicAction[data-action=save]').click();
    }
    if (_target = event.target.closest('.hdsentinelAction[data-action=test]')) {
        var id = document.querySelector('.eqLogicAttr[data-l1key=id]').value;
        domUtils.ajax({
            type: "POST",
            url: "plugins/hdsentinel/core/ajax/hdsentinel.ajax.php",
            data: {
                action: "test",
                id: id
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) {
                if (data.state != 'ok') {
                    jeedomUtils.showAlert({message: data.result, level: 'danger'});
                    return;
                }
            }
        });
    }
    if ((_target = event.target.closest('#manually')) || (_target = event.target.closest('#windows'))) {
        manageCheckBoxManualWindows();
    }
});

function manageCheckBoxManualWindows() {
    var windowsCheckbox = document.getElementById('windows');
    var manuallyCheckbox = document.getElementById('manually');
    var windowsElement = document.querySelectorAll('.windows');
    var sshHostsElement = document.querySelectorAll('.sshHosts');
    var manuallyElement = document.querySelectorAll('.manually');
    var automaticallyElement = document.querySelectorAll('.automatically');
    if (windowsCheckbox.checked) {
        windowsElement.forEach(function(element) {
            element.style.display = 'block';
        });
        sshHostsElement.forEach(function(element) {
            element.style.display = 'none';
        });
        manuallyElement.forEach(function(element) {
            element.style.display = 'none';
        });
        automaticallyElement.forEach(function(element) {
            element.style.display = 'block';
        });
    } else {
        windowsElement.forEach(function(element) {
            element.style.display = 'none';
        });
        if (manuallyCheckbox.checked) {
            manuallyElement.forEach(function(element) {
                element.style.display = 'none';
            });
            automaticallyElement.forEach(function(element) {
                element.style.display = 'block';
            });
        } else {
            manuallyElement.forEach(function(element) {
                element.style.display = 'block';
            });
            automaticallyElement.forEach(function(element) {
                element.style.display = 'none';
            });
            windowsElement.forEach(function(element) {
                element.style.display = 'none';
            });
        }
    } 
}

function refreshStatusMode(_id) {

    domUtils.ajax({
        type: "POST",
        url: "plugins/hdsentinel/core/ajax/hdsentinel.ajax.php",
        data: {
            action: "statusCron",
            id: _id
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            if (data.state != 'ok') {
                jeedomUtils.showAlert({message: data.result, level: 'danger'});
                return;
            }
            if (data.result && data.result === "1") {
                document.querySelector('.hdsentinelAction[data-action=checkremotecron]').classList.remove('btn-danger', 'btn-warning');
                document.querySelector('.hdsentinelAction[data-action=checkremotecron]').classList.add('btn-success');
                document.querySelector('.hdsentinelAction[data-action=checkremotecron]').innerHTML = '<span class="label label-success btn-xs">{{Lancé}}</span>';
                document.querySelector('.hdsentinelAction[data-action=launchCron]').unseen();
                document.querySelector('.hdsentinelAction[data-action=stopCron]').seen();
                document.querySelector('.hdsentinelAction[data-action=removeCron]').seen();
            } else if (data.result && data.result === "0") {
                document.querySelector('.hdsentinelAction[data-action=checkremotecron]').classList.remove('btn-success', 'btn-warning');
                document.querySelector('.hdsentinelAction[data-action=checkremotecron]').classList.add('btn-danger');
                document.querySelector('.hdsentinelAction[data-action=checkremotecron]').innerHTML = '<span class="label label-danger btn-xs">NOK</span>';
                document.querySelector('.hdsentinelAction[data-action=launchCron]').seen();
                document.querySelector('.hdsentinelAction[data-action=stopCron]').unseen();
                document.querySelector('.hdsentinelAction[data-action=removeCron]').unseen();
            } else {
                document.querySelector('.hdsentinelAction[data-action=checkremotecron]').classList.remove('btn-success', 'btn-danger');
                document.querySelector('.hdsentinelAction[data-action=checkremotecron]').classList.add('btn-warning');
                document.querySelector('.hdsentinelAction[data-action=checkremotecron]').innerHTML = '<span class="label label-warning btn-xs">{{Hors ligne}}</span>';
                document.querySelector('.hdsentinelAction[data-action=launchCron]').unseen();
                document.querySelector('.hdsentinelAction[data-action=stopCron]').unseen();
                document.querySelector('.hdsentinelAction[data-action=removeCron]').unseen();
            }
        }
    });
}



function printEqLogic(_eqLogic) {

    buildSelectHost(_eqLogic.configuration.host_id);
    document.querySelector('#table_infoseqlogic tbody').innerHTML = '';
    manageCheckBoxManualWindows();

    printEqLogicHelper("{{Version de HDSentinel installée}}", "Installed_version", _eqLogic);
    printEqLogicHelper("{{Adresse MAC}}", "MAC_Address", _eqLogic);
    printEqLogicHelper("{{Date du dernier rapport}}", "Current_Date_And_Time", _eqLogic);
    printEqLogicHelper("{{Date de création du rapport}}", "Report_Creation_Time", _eqLogic);
    printEqLogicHelper("{{Version du système}}", "OS_Version", _eqLogic);
    printEqLogicHelper("{{ID du processus}}", "Process_ID", _eqLogic);
    printEqLogicHelper("{{Démarré depuis}}", "Uptime", _eqLogic);
    printEqLogicHelper("{{Démarré depuis}}", "System_Uptime", _eqLogic);

    if (_eqLogic.configuration.host_id == '') {
        document.querySelector('.hdsentinelAction[data-action=checkremotecron]').classList.remove('btn-success', 'btn-warning');
        document.querySelector('.hdsentinelAction[data-action=checkremotecron]').classList.add('btn-danger');
        document.querySelector('.hdsentinelAction[data-action=checkremotecron]').innerHTML = '<span class="label label-danger btn-xs">NOK</span>';
        return;
    } else {
        refreshStatusMode(_eqLogic.id);
    }

    if (_eqLogic.configuration.remoteDaemonAuto === "1"){
        document.querySelector('.hdsentinelAction[data-action=stopCron]').unseen();
        document.querySelector('.hdsentinelAction[data-action=changeAutoModeRemote]').innerHTML = '<i class="fas fa-times"></i> {{Désactiver}}';
        document.querySelector('.hdsentinelAction[data-action=changeAutoModeRemote]').classList.remove('btn-success', 'btn-warning');
        document.querySelector('.hdsentinelAction[data-action=changeAutoModeRemote]').classList.add('btn-danger');
    } else{
        document.querySelector('.hdsentinelAction[data-action=stopCron]').seen();
        document.querySelector('.hdsentinelAction[data-action=changeAutoModeRemote]').innerHTML = '<i class="fas fa-magic"></i> {{Activer}}';
        document.querySelector('.hdsentinelAction[data-action=changeAutoModeRemote]').classList.remove('btn-danger', 'btn-warning');
        document.querySelector('.hdsentinelAction[data-action=changeAutoModeRemote]').classList.add('btn-success');
    }

    domUtils.ajax({
        type: "POST",
        url: "plugins/hdsentinel/core/ajax/hdsentinel.ajax.php",
        data: {
            action: "getImage",
            eq_id: _eqLogic.id
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok' || !data.result) {
                document.getElementById('img_device').setAttribute("src", 'plugins/hdsentinel/plugin_info/hdsentinel_icon.png');
                return;
            }
            document.getElementById('img_device').setAttribute("src", data.result);
        }
    });
}

function addCmdToTable(_cmd) {
	if (!isset(_cmd)) {
		var _cmd = {configuration: {}};
	}
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td class="hidden-xs">'
    tr += '<span class="cmdAttr" data-l1key="id"></span>'
    tr += '</td>'
    tr += '<td>'
    tr += '<div class="input-group">'
    tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">'
    tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>'
    tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>'
    tr += '</div>'
    tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display:none;margin-top:5px;" title="{{Commande info liée}}">'
    tr += '<option value="">{{Aucune}}</option>'
    tr += '</select>'
    tr += '</td>'

    tr += '<td>';
    tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
    tr += '</td>';
  
    tr += '<td>'
    tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> '
    tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label> '
    tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label> '
    tr += '<div style="margin-top:7px;">'
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="{{Unité}}" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
    tr += '</div>'
    tr += '</td>'

    tr += '<td>';
    if (init(_cmd.type) == 'info') {
      tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>';
    }
	tr += '</td>';

    tr += '<td style="min-width:80px;width:200px;">';
    tr += '<div class="input-group">';
    if (is_numeric(_cmd.id) && _cmd.id != '') {
        tr += '<a class="btn btn-default btn-xs cmdAction roundedLeft" data-action="configure" title="{{Configuration de la commande}} ' + _cmd.type + '"><i class="fa fa-cogs"></i></a>';
        tr += '<a class="btn btn-success btn-xs cmdAction" data-action="test" title="{{Tester}}"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    tr += '<a class="btn btn-danger btn-xs cmdAction roundedRight" data-action="remove" title="{{Suppression de la commande}} ' + _cmd.type + '"><i class="fas fa-minus-circle"></i></a>';
    tr += '</tr>';

    let newRow = document.createElement('tr')
    newRow.innerHTML = tr
    newRow.addClass('cmd')
    newRow.setAttribute('data-cmd_id', init(_cmd.id))
    document.getElementById('table_cmd').querySelector('tbody').appendChild(newRow)

    jeedom.eqLogic.buildSelectCmd({
        id: document.querySelector('.eqLogicAttr[data-l1key="id"]').jeeValue(),
        filter: { type: 'info' },
        error: function(error) {
            jeedomUtils.showAlert({ message: error.message, level: 'danger' })
        },
        success: function(result) {
            newRow.querySelector('.cmdAttr[data-l1key="value"]').insertAdjacentHTML('beforeend', result)
            newRow.querySelector('.cmdAttr[data-l1key="configuration"][data-l2key="updateCmdToValue"]')?.insertAdjacentHTML('beforeend', result)
            newRow.setJeeValues(_cmd, '.cmdAttr')
            jeedom.cmd.changeType(newRow, init(_cmd.subType))
        }
    });
}

function printEqLogicHelper(_label, _name, _eqLogic) {
    var eqLogic = _eqLogic.result ? _eqLogic.result : _eqLogic;
    if (eqLogic.configuration && eqLogic.configuration[_name] !== undefined) {
        var trm = '<tr>';
        trm += '	<td class="col-sm-4" style="min-width:119px !important">';
        trm += '		<span style="font-size : 1em;">' + _label + '</span>';
        trm += '	</td>';
        trm += '	<td>';
        trm += '		<span class="label label-default" style="font-size : 1em;">';
        trm += '			<span class="eqLogicAttr" data-l1key="configuration" data-l2key="'+_name+'">';
        trm += '			</span>';
        trm += '		</span>';
        trm += '	</td>';
        trm += '</tr>';
        var newRow = document.createElement('tr');
        newRow.innerHTML = trm;
        document.querySelector('#table_infoseqlogic tbody').appendChild(newRow);
        newRow.setJeeValues(eqLogic, '.eqLogicAttr');
    }
}