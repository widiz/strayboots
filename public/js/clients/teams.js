$(function(){

	$(".select2-auto").select2();

	var txtRender = $.fn.dataTable.render.text().display;

	var $playersField = $('#playersField'),
		$teams = $('#teams'),
		$teamsField = $('#teamsField'),
		$codes = $('#codes'),
		lastPlayersCount = 0,
		teamIdx = {}, i, teamNames;

	var makechanges = typeof window.huntStarted == 'undefined';

	try {
		teamNames = JSON.parse($teamsField.val());
	} catch(E) {
		teamNames = {};
	}

	for (i = 0; i < window.teams.length; i++) {
		teamIdx[window.teams[i].id] = i;
		addTeam(window.teams[i], true);
	}

	if (makechanges) {
		$(".sortable").sortable({
			connectWith: ".sortable",
			cursor: "move",
			handle: ".draggable",
			stop: function(e, ui){
				var $input = ui.item.find('input'),
					tid = ui.item.closest('.ibox').data('id')
					name = 'leader' + tid;
				if ($input.attr('name') != name) {
					$input.iCheck('uncheck').removeAttr('checked').attr('name', name);
					var info = ui.item.data('info');
					info.team_id = tid;
					ui.item.data('info', info);
				}
			}
		});
	}

	try {
		var _players = JSON.parse($playersField.val());
		for (i = 0; i < _players.length; i++)
			addPlayer(_players[i]);
		countPlayers();
	} catch(e) {}

	$teams.on('click', 'table.players .remove-player', function(){
		if (confirm("Are you sure?")) {
			$(this).closest('tr').remove();
			setPlayers();
			countPlayers();
		}
		return false;
	});

	$teams.on('click', 'table.players .remove-player', function(){
		if (confirm("Are you sure?")) {
			$(this).closest('tr').remove();
			setPlayers();
			countPlayers();
		}
		return false;
	});

	/*$teams.on('click', '.ibox-tools .show-codes', function(){
		var $ibox = $(this).closest('.ibox');
		try {
			var team = window.teams[teamIdx[$ibox.data('id')]];
			if (typeof team == 'object') {
				$codes.find('#codesLabel span').text($ibox.find('h5').html() + ' activation codes');
				$codes.find('span.leader').text(team.activation_leader);
				$codes.find('span.player').text(team.activation_player);
				$codes.modal('show');
			}
		} catch(E) {}
		return false;
	});*/

	$teams.on('click', '.ibox-tools .editname', function(){
		var $ibox = $(this).closest('.ibox');
		try {
			var team = window.teams[teamIdx[$ibox.data('id')]],
				tnum = $ibox.data('tnum');
			if (typeof team == 'object') {
				bootbox.dialog({
					title: "Rename Team #" + tnum,
					message: '<div class="row">  ' +
						'<div class="col-md-12"> ' +
							'<form class="form-horizontal" onsubmit="return false"> ' +
								'<div class="form-group" style="margin-bottom:0"> ' +
									'<label class="col-md-4 control-label" for="tname' + team.id + '">Name</label> ' +
									'<div class="col-md-6"> ' +
										'<input id="tname' + team.id + '" name="tname' + team.id + '" type="text" placeholder="Team #' + tnum + '" minlength="2" maxlength="30" class="name form-control input-md"> ' +
									'</div>' +
								'</div>' +
							'</form>' +
						'</div>' +
					'</div>',
					buttons: {
						default: {
							label: "Cancel",
							className: "btn-default"
						},
						success: {
							label: "Save",
							className: "btn-success",
							callback: function(){
								var name = $(this).find('input.name').val();
								if (name) {
									if (!(/^[a-zA-Z0-9!#$%&'*+/=?^_`{|}~-]{2,30}$/.test(name))) {
										toastr.error(null, 'Please enter a valid team name');
										return false;
									}
								} else {
									name = null;
								}
								if (team.name === name)
									delete teamNames[team.id];
								else
									teamNames[team.id] = name;
								$teamsField.val(JSON.stringify(teamNames));
								name = typeof teamNames[team.id] == 'undefined' ? team.name : teamNames[team.id];
								if (name)
									$ibox.find('h5').text(name);
								else
									$ibox.find('h5').html('Team <span>' + tnum + '</span>');
								$('#add-team option[value="' + team.id + '"]').text(name || ('Team ' + tnum)).parent().select2();
							}
						}
					}
				}).init(function(){
					$('#tname' + team.id).val((typeof teamNames[team.id] == 'undefined' ? team.name : teamNames[team.id]) || '');
				});
			}
		} catch(E) {}
		return false;
	});

	$teams.on('click', '.ibox-tools .collapse-link', function(){
		var $this = $(this);
		var ibox = $this.closest('div.ibox');
		ibox.find('div.ibox-content').toggle();
		$this.find('i').toggleClass('fa-chevron-up').toggleClass('fa-chevron-down');
		ibox.toggleClass('border-bottom');
		setTimeout(function(){
			ibox.resize();
		}, 50);
		return false;
	});

	$('#main-form').submit(function(){
		setPlayers();
		var valid = true;
		$teams.find('table.players').each(function(i){
			var $radio = $(this).find('input[type="radio"]');
			if ($radio.length && $radio.filter(':checked').length < 1) {
				var name = typeof teamNames[teamIdx[i]] == 'undefined' ? window.teams[i].name : teamNames[teamIdx[i]];
				toastr.error(null, (name || ('Team ' + (i + 1))) + ': leader is missing');
				valid = false;
			}
		});
		return valid;
	});

	$('#add-player').validate({
		submitHandler: function(form){
			if (lastPlayersCount < window.order_hunt.max_players) {
				var $form = $(form);
				var addTeam = $form.find('#add-team');
				var team_id = addTeam.val();
				addPlayer({
					id: 0,
					team_id: team_id,
					first_name: $form.find('#add-firstName').val(),
					last_name: $form.find('#add-lastName').val(),
					email: $form.find('#add-email').val(),
					leader: false
				});
				setPlayers();
				countPlayers();
				form.reset();
				addTeam.val(team_id);
			} else {
				toastr.error(null, 'Players limit reached');
			}
		}
	});

	function setPlayers() {
		$playersField.val(JSON.stringify($teams.find('table.players tbody tr[data-id]').map(function(){
			return $(this).data('info');
		}).toArray()));
	}

	function countPlayers(){
		lastPlayersCount = $('#teams table.players tbody tr[data-id]').length;
		$('.playersCount').text(lastPlayersCount);
	}

	function addPlayer(player){
		var team = window.teams[teamIdx[player.team_id]];
		if (typeof team != 'object') return;
		var $player = $(
			'<tr data-id="' + player.id + '">' +
				'<td class="draggable"><i class="fa fa-ellipsis-v"></i>&thinsp;<i class="fa fa-ellipsis-v"></i></td>' +
				'<td>' +
					'<label class="i-checks">' +
						'<input type="radio" name="leader' + team.id + '" value="' + player.id + '"' + (player.leader === true ? ' checked' : '') + (makechanges ? '' : ' disabled') + '>' +
					'</label>' +
				'</td>' +
				'<td>' + txtRender(player.first_name || '') + ' ' + txtRender(player.last_name || '') + '</td>' +
				'<td>' + txtRender(player.email || '') + '</td>' +
				'<td><a href="#" class="remove-player"><i class="fa fa-times"></i></a></td>' +
			'</tr>'
		).data('info', player);
		var x = $('#teams .ibox[data-id="' + team.id + '"] table.players tbody .o-holder');
		if (x.length)
			$player.insertBefore(x.parent());
		else
			$player.appendTo('#teams .ibox[data-id="' + team.id + '"] table.players tbody');
		$player.find('.i-checks').iCheck({
			radioClass: 'iradio_square-green'
		}).on('ifChecked ifUnchecked', updateLeader);
	}

	function updateLeader() {
		var $this = $(this);
		var $tr = $this.closest('tr');
		var info = $tr.data('info');
		info.leader = $this.prop('checked');
		$tr.data('info', info);
		setPlayers();
	}

	function addTeam(team, open){
		open = !!open;
		var tnum = $teams.children().length + 1,
			name = typeof teamNames[team.id] == 'undefined' ? team.name : teamNames[team.id];
		var $h5 = $(
			'<div class="col-md-6 col-sm-12">' +
				'<div data-tnum="' + tnum + '" data-id="' + team.id + '" class="ibox float-e-margins' + (open ? '' : ' margin-bottom') + '">' +
					'<div class="ibox-title">' +
						'<h5></h5>' +
						'<div class="ibox-tools">' +
							(makechanges ? '<a href="javascript:;" class="editname"><i class="fa fa-edit"></i></a>&nbsp;' : '') +
							//'<a href="javascript:;" class="show-codes"><i class="fa fa-code"></i></a>&nbsp;' +
							'<a href="javascript:;" class="collapse-link">' +
								'<i class="fa fa-chevron-' + (open ? 'up' : 'down') + '"></i>' +
							'</a>' +
							'<div class="ibox-codes">' +
								'Leader: ' + team.activation_leader +
							'</div> &nbsp;' +
							'<div class="ibox-codes">' +
								'Player: ' + team.activation_player +
							'</div>' +
						'</div>' +
					'</div>' +
					'<div class="ibox-content' + (open ? '' : ' collapse') + '">' +
						'<table class="table table-striped players">' +
							'<thead>' +
								'<tr>' +
									'<td></td>' +
									'<td>Leader</td>' +
									'<td>Name</td>' +
									'<td>Email</td>' +
								'</tr>' +
							'</thead>' +
							'<tbody class="sortable"><tr><td colspan="5" class="o-holder"></td></tr></tbody>' +
						'</div>' +
					'</div>' +
				'</div>' +
			'</div>'
		).appendTo($teams).find('h5');
		if (name)
			$h5.text(name);
		else
			$h5.html('Team <span>' + tnum + '</span>');
		$('#add-team option[value="' + team.id + '"]').text(name || ('Team ' + tnum)).parent().select2();
	}
});