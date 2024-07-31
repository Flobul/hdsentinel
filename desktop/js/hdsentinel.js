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

prettyPrintDisplayAsTable();

$('body').delegate('#bt_pluginDisplayAsTable[data-coreSupport="1"]', 'click', function() {
  prettyPrintDisplayAsTable();
});

function prettyPrintDisplayAsTable() {

  if (getCookie('jeedom_displayAsTable') == 'true' || jeedom.theme.theme_displayAsTable == 1){
    $('#accordionObject > div.objectSortable').removeClass(function (index, css) {
      return (css.match (/\bcol-xs-\S+/g) || []).join(' ');
    });
    $('#accordionObject > div.objectSortable').addClass('col-xs-4');
  } else {
    $('#accordionObject > div.objectSortable').removeClass('col-xs-4').addClass('col-xs-2'); // addClass
  }
}

$('.bt_hdsentinelDocumentation').off('click').on('click',function(){
    window.open($(this).attr("data-location"), "_blank", null);
});

$('#bt_healthSentinel').on('click', function () {
  $('#md_modal').dialog({title: "{{Santé Hard Disk Sentinel}}"});
  $('#md_modal').load('index.php?v=d&plugin=hdsentinel&modal=health').dialog('open');
});

$('#bt_pageSentinel').on('click', function () {
  $('#md_modal').dialog({title: "{{Page Html Hard Disk Sentinel}}"});
  $('#md_modal').load('index.php?v=d&plugin=hdsentinel&modal=page').dialog('open');
});

$("#table_cmd").sortable({
    axis: "y",
    cursor: "move",
    items: ".cmd",
    placeholder: "ui-state-highlight",
    tolerance: "intersect",
    forcePlaceholderSize: true
});

function refreshStatusMode(_id) {

    $.ajax({
        type: "POST",
        url: "plugins/hdsentinel/core/ajax/hdsentinel.ajax.php",
        data: {
            action: "statusCron",
            id: _id
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error,$('#div_alert'));
        },
        success: function (data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
          console.log('status', data)

            if (data.result && data.result == '1') {
                $('.hdsentinelAction[data-action=checkremotecron]').removeClass('btn-danger').addClass('btn-success');
                $('.hdsentinelAction[data-action=checkremotecron]').html('<span class="label label-success btn-xs">{{Lancé}}</span>');
                $('.hdsentinelAction[data-action=launchCron]').hide();
                $('.hdsentinelAction[data-action=stopCron]').show();
                $('.hdsentinelAction[data-action=removeCron]').show();
            } else {
                $('.hdsentinelAction[data-action=checkremotecron]').removeClass('btn-success').addClass('btn-danger');
                $('.hdsentinelAction[data-action=checkremotecron]').html('<span class="label label-danger btn-xs">NOK</span>');
                $('.hdsentinelAction[data-action=launchCron]').show();
                $('.hdsentinelAction[data-action=stopCron]').hide();
                $('.hdsentinelAction[data-action=removeCron]').hide();
            }
        }
    });
}

$('.hdsentinelAction[data-action=sendFiles]').on('click', function () {
    $.ajax({
        type: "POST",
        url: "plugins/hdsentinel/core/ajax/hdsentinel.ajax.php",
        data: {
            action: "sendFile",
            id: $('.eqLogicAttr[data-l1key=id]').value()
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error,$('#div_alert'));
        },
        success: function (data) { // data.result: {publish: "1", install: "1"}}
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            if (data.result != '') {
                if (data.result.publish != "1" || data.result.install != "1") {
                    $('#div_alert').showAlert({message: '{{Envoi des scripts échoué :}}'+JSON.stringify(data.result), level: 'danger'});
                    return;
                }
                $('#div_alert').showAlert({message: '{{Envoi des scripts réalisé avec succès.}}', level: 'success'});
            }
        }
    });
});

$('.hdsentinelAction[data-action=installDependancy]').on('click', function () {
    $.ajax({
        type: "POST",
        url: "plugins/hdsentinel/core/ajax/hdsentinel.ajax.php",
        data: {
            action: "installDependancy",
            id: $('.eqLogicAttr[data-l1key=id]').value()
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error,$('#div_alert'));
        },
        success: function (data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $('#div_alert').showAlert({message: '{{Installation lancée}}', level: 'success'});
        }
    });
});

$('.hdsentinelAction[data-action=getLogDependancy]').on('click', function () {
    $.ajax({
        type: "POST",
        url: "plugins/hdsentinel/core/ajax/hdsentinel.ajax.php",
        data: {
            action: "getLogDependancy",
            id: $('.eqLogicAttr[data-l1key=id]').value()
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error,$('#div_alert'));
        },
        success: function (data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }

          console.log(data)
        }
    });
});

$('.hdsentinelAction[data-action=getLog]').on('click', function () {
    $.ajax({
        type: "POST",
        url: "plugins/hdsentinel/core/ajax/hdsentinel.ajax.php",
        data: {
            action: "getLog",
            id: $('.eqLogicAttr[data-l1key=id]').value()
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error,$('#div_alert'));
        },
        success: function (data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
        }
    });
});

$('.hdsentinelAction[data-action=launchCron]').on('click', function () {
    var id = $('.eqLogicAttr[data-l1key=id]').value();
    $.ajax({
        type: "POST",
        url: "plugins/hdsentinel/core/ajax/hdsentinel.ajax.php",
        data: {
            action: "launchCron",
            id: id
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error,$('#div_alert'));
        },
        success: function (data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            refreshStatusMode(id);
          console.log('launch', data)

            if ((1 - data.result) != "1") {
                $('#div_alert').showAlert({message: '{{Lancement du cron échoué :}}'+data.result, level: 'danger'});
                return;
            }
            $('#div_alert').showAlert({message: '{{Lancement du cron réussi.}}', level: 'success'});
        }
    });
});

$('.hdsentinelAction[data-action=removeCron]').on('click', function () {
    var id = $('.eqLogicAttr[data-l1key=id]').value();
    $.ajax({
        type: "POST",
        url: "plugins/hdsentinel/core/ajax/hdsentinel.ajax.php",
        data: {
            action: "removeCron",
            id: id
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error,$('#div_alert'));
        },
        success: function (data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            refreshStatusMode(id);
          console.log('remove', data)

            if ((1 - data.result) != '1') {
                $('#div_alert').showAlert({message: '{{Suppression du cron échouée :}}'+data.result, level: 'danger'});
                return;
            }
            $('#div_alert').showAlert({message: '{{Suppression du cron réussie.}}', level: 'success'});
        }
    });
});

$('.hdsentinelAction[data-action=stopCron]').on('click', function () {
    var id = $('.eqLogicAttr[data-l1key=id]').value();
    $.ajax({
        type: "POST",
        url: "plugins/hdsentinel/core/ajax/hdsentinel.ajax.php",
        data: {
            action: "stopCron",
            id: id
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error,$('#div_alert'));
        },
        success: function (data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            refreshStatusMode(id);
          console.log('stop', data)
            if ((1 - data.result) != '1') {
                $('#div_alert').showAlert({message: '{{Arrêt du cron échoué :}}'+data.result, level: 'danger'});
                return;
            }
            $('#div_alert').showAlert({message: '{{Arrêt du cron réussie.}}', level: 'success'});
        }
    });
});

$('.hdsentinelAction[data-action=changeAutoModeRemote]').on('click',function(){
    var auto = 1 - $('.eqLogicAttr[data-l2key="remoteDaemonAuto"]').value();
    $('.eqLogicAttr[data-l2key="remoteDaemonAuto"]').val(auto);
    $('.eqLogicAction[data-action=save]').click();
});

$('.hdsentinelAction[data-action=test]').on('click', function () {
    var id = $('.eqLogicAttr[data-l1key=id]').value();
    $.ajax({
        type: "POST",
        url: "plugins/hdsentinel/core/ajax/hdsentinel.ajax.php",
        data: {
            action: "test",
            id: id
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error,$('#div_alert'));
        },
        success: function (data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            console.log(data)
        }
    });
});

$('#windows').off('click').on('click', function() {
  if($('#windows').prop("checked")) {
    $('.underLinux').hide();
  } else {
    $('.underLinux').show();
  }
});

$('#manually').off('click').on('click', function() {
  if($('#manually').prop("checked")) {
    $('.manually').hide();
    $('.automatically').show();
    if($('#windows').prop("checked")) {
      $('.underLinux').hide();
    } else {
      $('.underLinux').show();
    }
  } else {
    $('.manually').show();
    $('.automatically').hide();
    $('.underLinux').show();
  }
});

$(".eqLogicAttr[data-l2key='maitreesclave']").on('change', function () {
	if (this.selectedIndex == 0) {
	  $(".distant").show();
	  $(".distant-password").show();
	  $(".distant-key").hide();
	} else if (this.selectedIndex == 1) {
		$(".distant").show();
		$(".distant-password").hide();
		$(".distant-key").show();
	} else {
		$(".distant").hide();
	}
});

function printEqLogic(_eqLogic) {

  $('#table_infoseqlogic tbody').empty();

  if(_eqLogic.configuration.manually && _eqLogic.configuration.manually == 1) {
    $('.manually').hide();
    $('.automatically').show();
    if(_eqLogic.configuration.windows && _eqLogic.configuration.windows == 1) {
      $('.underLinux').hide();
    } else {
      $('.underLinux').show();
    }
  } else {
    $('.manually').show();
    $('.automatically').hide();
  }

  //affichage des configurations du device
  printEqLogicHelper("{{Version de HDSentinel installée}}", "Installed_version", _eqLogic);
  printEqLogicHelper("{{Adresse MAC}}", "MAC_Address", _eqLogic);
  printEqLogicHelper("{{Date du dernier rapport}}", "Current_Date_And_Time", _eqLogic);
  printEqLogicHelper("{{Date de création du rapport}}", "Report_Creation_Time", _eqLogic);
  printEqLogicHelper("{{Version du système}}", "OS_Version", _eqLogic);
  printEqLogicHelper("{{ID du processus}}", "Process_ID", _eqLogic);
  printEqLogicHelper("{{Démarré depuis}}", "Uptime", _eqLogic);
  printEqLogicHelper("{{Démarré depuis}}", "System_Uptime", _eqLogic);

  if ($('.eqLogicAttr[data-l1key=configuration][data-l2key=addressip]').value() == ''
      || $('.eqLogicAttr[data-l1key=configuration][data-l2key=portssh]').value() == ''
      || $('.eqLogicAttr[data-l1key=configuration][data-l2key=user]').value() == ''
      || $('.eqLogicAttr[data-l1key=configuration][data-l2key=password]').value() == '' ) {
      $('.hdsentinelAction[data-action=checkremotecron]').removeClass('btn-success').addClass('btn-danger');
      $('.hdsentinelAction[data-action=checkremotecron]').html('<span class="label label-danger btn-sm">NOK</span>');
      return;
  } else {
      refreshStatusMode(_eqLogic.id);
  }

  if(_eqLogic.configuration.remoteDaemonAuto == 1){
    $('.hdsentinelAction[data-action=stopremote]').hide();
    $('.hdsentinelAction[data-action=changeAutoModeRemote]').removeClass('btn-success').addClass('btn-danger');
    $('.hdsentinelAction[data-action=changeAutoModeRemote]').html('<i class="fas fa-times"></i> {{Désactiver}}');
  } else{
    $('.hdsentinelAction[data-action=stopremote]').show();
    $('.hdsentinelAction[data-action=changeAutoModeRemote]').removeClass('btn-danger').addClass('btn-success');
    $('.hdsentinelAction[data-action=changeAutoModeRemote]').html('<i class="fas fa-magic"></i> {{Activer}}');
  }

  $.ajax({// fonction permettant de faire de l'ajax
      type: "POST", // methode de transmission des données au fichier php
      url: "plugins/hdsentinel/core/ajax/hdsentinel.ajax.php", // url du fichier php
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
              $('#img_device').attr("src", 'plugins/hdsentinel/plugin_info/hdsentinel_icon.png');
              return;
          }
          $('#img_device').attr("src", data.result);
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

		tr += '<td>';
		tr += '<span class="cmdAttr" data-l1key="id" ></span>';
		tr += '</td>';

		tr += '<td>';
		tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon" style="display:inline"><i class="fa fa-flag"></i> Icone</a>';
		tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width:180px;display:inline">';
		tr += '<span class="cmdAttr cmdAction" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
		tr += '<span class="cmdAttr" data-l1key="display" data-l2key="generic_type" style="display:none;"></span>';
		tr += '<span><input class="cmdAttr form-control" data-l1key="configuration" data-l2key="type" style="display: none" ></input></span>';
		tr += '<span><input class="cmdAttr form-control" data-l1key="logicalId" style="display: none" ></input></span>';
		tr += '</td>';

        tr += '<td style="min-width:120px;width:140px;">';
        tr += '    <span><input type="checkbox" class="cmdAttr" data-size="mini" data-l1key="isVisible" checked/> {{Afficher}}<br/></span>';
        tr += '    <span><input type="checkbox" class="cmdAttr" data-l1key="isHistorized"/> {{Historiser}}</span>';
        tr += '</td>';

		tr += '<td>';
        if (init(_cmd.subType) == 'numeric') {
          tr += '    <input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="display:inline-block;width: 75px;"></input>';
          tr += '    <input class="cmdAttr form-control input-sm" data-l1key="unite" placeholder="{{Unité}}" title="{{Unité}}" style="display:inline-block;width: 50px;"></input>';
          tr += '    <style>.select {}</style>';
          tr += '    <input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width: 75px;"></input>';
        }
		tr += '</td>';

        tr += '<td style="min-width:100px;width:150px;">';
        tr += '<div class="input-group">';
        if (is_numeric(_cmd.id) && _cmd.id != '') {
          tr += '<a class="btn btn-default btn-xs cmdAction roundedLeft" data-action="configure" title="{{Configuration de la commande}} ' + _cmd.type + '"><i class="fa fa-cogs"></i></a>';
          tr += '<a class="btn btn-success btn-xs cmdAction" data-action="test" title="{{Tester}}"><i class="fa fa-rss"></i> {{Tester}}</a>';
        }
        tr += '<a class="btn btn-danger btn-xs cmdAction roundedRight" data-action="remove" title="{{Suppression de la commande}} ' + _cmd.type + '"><i class="fas fa-minus-circle"></i></a>';
        tr += '</div>';
        tr += '</td>';
		tr += '</tr>';

		$('#table_cmd tbody').append(tr);
		$('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
		jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));

}

function printEqLogicHelper(_label, _name, _eqLogic) {

  if (isset(_eqLogic.result)) {
    var eqLogic = _eqLogic.result;
  } else {
    var eqLogic = _eqLogic;
  }
  if (isset(eqLogic.configuration[_name])) {
    if (eqLogic.configuration[_name] !== undefined) {
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
      $('#table_infoseqlogic tbody').append(trm);
      $('#table_infoseqlogic tbody tr:last').setValues(eqLogic, '.eqLogicAttr');
    }
  }
}
