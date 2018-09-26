
(function(exports) {
	if (exports.FormData)
		return;
	exports.FormData = FormData;

	var ___send$rw = XMLHttpRequest.prototype.send;
	XMLHttpRequest.prototype.send = function(data) {
		if (data instanceof FormData) {
			if (!data.__endedMultipart) data.__append('--' + data.boundary + '--\r\n');
			data.__endedMultipart = true;
			this.setRequestHeader('Content-Type', 'multipart/form-data; boundary=' + data.boundary);
			data = new Uint8Array(data.data);
		}
		return ___send$rw.call(this, data);
	};

	function FormData() {
		if (!(this instanceof FormData))
			return new FormData();
		this.boundary = '------RWWorkerFormDataBoundary' + Math.random().toString(36);
		var internal_data = this.data = [];
		this.__append = function(inp) {
			var i = 0, len;
			if (typeof inp == 'string') {
				for (len = inp.length; i < len; ++i)
					internal_data.push(inp.charCodeAt(i) & 0xff);
			} else if (inp && inp.byteLength) {
				if (!('byteOffset' in inp))
					inp = new Uint8Array(inp);
				for (len = inp.byteLength; i < len; ++i)
					internal_data.push(inp[i] & 0xff);
			}
		};
	}
	FormData.prototype.append = function(name, value, filename) {
		if (this.__endedMultipart) {
			this.data.length -= this.boundary.length + 6;
			this.__endedMultipart = false;
		}
		if (arguments.length < 2)
			throw new SyntaxError('Not enough arguments');
		var part = '--' + this.boundary + '\r\n' + 'Content-Disposition: form-data; name="' + name + '"';
		if (value instanceof File || value instanceof Blob) {
			return this.append(name, new Uint8Array(new FileReaderSync().readAsArrayBuffer(value)), filename || value.name);
		} else if (typeof value.byteLength == 'number') {
			part += '; filename="' + (filename || 'blob').replace(/"/g,'%22') + '"\r\n' + 'Content-Type: application/octet-stream\r\n\r\n';
			this.__append(part);
			this.__append(value);
			part = '\r\n';
		} else {
			part += '\r\n\r\n' + value + '\r\n';
		}
		this.__append(part);
	};
})(this || self || window);

(function($){

	$('#page-wrapper').css('padding-bottom', '40px');

	function emptyFunc(){}
	function twoDigits(d) {
		if (0 <= d && d < 10) return "0" + d;
		if (-10 < d && d < 0) return "-0" + (-1 * d);
		return d.toString();
	}
	function toMysqlFormat(date) {
		return date.getUTCFullYear() + '-' + twoDigits(1 + date.getUTCMonth()) + '-' + twoDigits(date.getUTCDate()) + 'T' + twoDigits(date.getUTCHours()) + ':' + twoDigits(date.getUTCMinutes()) + ':' + twoDigits(date.getUTCSeconds());
	}
	var precompiled = [/&/g, /</g, />/g, /"/g, /'/g, /([^>\r\n]?)(\r\n|\n\r|\r|\n)/g];
	function nl2br(str) {
		return (str + '').replace(precompiled[5], '$1<br>');
	}
	function toText(html){
		return (html + '')
			.replace(precompiled[0], "&#038;")
			.replace(precompiled[1], "&#060;")
			.replace(precompiled[2], "&#062;")
			.replace(precompiled[3], "&#034;")
			.replace(precompiled[4], "&#039;");
	}
	function updateTime(){
		var x = new Date();
		$('#chat time').each(function(){
			var time = $(this);
			var datetime = new Date(time.attr('datetime'));
			if (datetime)
				time.text(moment.utc(datetime).fromNow());
		});
	}

	var autolinker = new Autolinker(),
		emoji = new EmojiConvertor(),
		data = window.appLoc;

	data.cn = null;

	var isLoadingPlayers = false,
		afterLoadPlayers = [];

	var dialog = bootbox.prompt("Please choose your chat name", function(name){
		if (name)
			data.cn = name;
	});
	dialog.init(function(){
		dialog.find('input').attr('placeholder', 'Strayboots Staff');
	});

	emoji.use_sheet = true;
	//emoji.sheet_size = 64;
	emoji.img_sets.apple.sheet = '/img/sheet_apple_64.png';
	emoji.img_sets.google.sheet = '/img/sheet_google_64.png';
	emoji.img_sets.emojione.sheet = '/img/sheet_emojione_64.png';
	emoji.img_sets.twitter.sheet = '/img/sheet_twitter_64.png';

	emoji.img_set = 'apple';
	if (typeof navigator !== 'undefined') {
		try {
			var ua = navigator.userAgent;
			if (ua.match(/iP(hone|od|ad|)/i))
				emoji.img_set = 'apple';
			else if (ua.match(/Android/i))
				emoji.img_set = 'google';
		} catch(E) {}
	}

	function addMessage(message, block){
		var $messageP = $(document.createElement('p')),
			isImg = typeof message.image == 'string';
		if (isImg)
			$messageP.append($(document.createElement('img')).attr('src', message.image));
		else 
			$messageP.html(nl2br(emoji.replace_unified(autolinker.link($messageP.text(message.content).html()))));

		var appendMessage = true;
		var x = new Date(message.timestamp);

		var $lastMessage = block.find(".message-row").last();
		if ($lastMessage.length && $lastMessage.data('pid') == message.pid && !isImg && block.find('.message.img').length === 0) {
			var time = $lastMessage.find('time');
			var datetime = new Date(time.data('timestamp')).getTime();
			if (!isNaN(datetime) && !isNaN(x) && x.getTime() - datetime < 1e4) {
				// we can just append text then
				$messageP.insertBefore($lastMessage.find('.time'));
				time.attr('datetime', toMysqlFormat(x)).data('timestamp', message.timestamp).text(moment(x).fromNow());
				appendMessage = false;
			}
		}
		if (appendMessage) {
			var player = data.players[message.pid] || {
				team: 0,
				name: ''
			};
			var messageRow = $(
				'<div class="message-row" data-pid="' + message.pid + '">' +
					'<div class="message' + (isImg ? ' img' : '') + '">' +
						'<div class="message-table clearfix' + (message.pid == data.pid && message.cn == data.cn ? ' me' : '') + '">' +
							'<div class="message-columns image-column">' +
								'<div class="image-wrapper">' +
									'<img src="' + (player.thumb || '/img/unknown.jpg') + '" class="player-thumb">' +
									//'<span class="chat-status online"></span>' + TODO add this
								'</div>' +
							'</div>' +
							'<div class="message-columns content-column">' +
								'<h2>' + (message.cn ? toText(message.cn) : player.name) + (typeof data.teams[player.team] == 'string' ? ' &nbsp;<span>' + data.teams[player.team] + '</span>' : '') + '</h2>' +
								(isNaN(x) ? '' : '<div class="time">' +
									'<i></i> <time datetime="' + toMysqlFormat(x) + '" data-timestamp="' + message.timestamp + '">' + moment(x).fromNow() + '</time>' +
								'</div>') +
							'</div>' +
						'</div>' +
					'</div>' +
				'</div>'
			).appendTo(block);
			$messageP.insertBefore(messageRow.find('.time'));
			if (typeof data.players[message.pid] === 'undefined') {
				afterLoadPlayers.push(function(){
					player = data.players[message.pid];
					if (player) {
						messageRow.find('.player-thumb').attr('src', player.thumb);
						messageRow.find('h2').html((message.cn ? toText(message.cn) : player.name) + (typeof data.teams[player.team] == 'string' ? ' &nbsp;<span>' + data.teams[player.team] + '</span>' : ''));
					}
				});
				if (!isLoadingPlayers) {
					isLoadingPlayers = true;
					$.ajax({
						url: '/clients/chat/players/' + data.orderHunt,
						cache: false,
						success: function(d) {
							if (d.success && d.teams && d.players) {
								data.teams = d.teams;
								setPlayers(d.players);
								for (var ii = 0; ii < afterLoadPlayers.length; ii++) {
									try { afterLoadPlayers[ii](); } catch(E) { }
								}
								afterLoadPlayers = [];
								isLoadingPlayers = false;
							} else {
								this.error();
							}
						},
						error: function(){
							isLoadingPlayers = false;
						}
					});
				}
			}
		}
	}

	if (typeof firebase != 'object' || typeof data != 'object') {
		document.location.href = '/clients';
		return;
	}

	data.firebase = firebase.database();

	function setPlayers(players) {
		if (typeof players === 'object' && players !== null)
			data.players = players;

		data.players[0] = {
			team: 0,
			email: "Strayboots Staff",
			fname: null,
			lname: null,
			thumb: null
		};
		if (!data.players[data.pid]) {
				data.players[data.pid] = {
				team: 0,
				email: 'Me',
				fname: null,
				lname: null,
				thumb: null
			};
		}

		for (var pid in data.players) {
			try {
				var p = data.players[pid];
				data.players[pid].name = ((p.fname || p.lname) ? toText(p.fname + ' ' + p.lname).trim() : '') || p.email;
				data.players[pid].thumb = p.thumb || '/img/unknown.jpg';
			} catch(e){}
		}
	}
	setPlayers();

	var _room = data.firebase.ref().child(window.FB_PREFIX + 'orderhuntchat/' + data.orderHunt),
		chatWrapper = $('#chat'),
		chatBox = $('#chat-textbox textarea');

	$('.chat-button').click(function(){
		var messageText = chatBox.val();
		if (messageText.length) {
			chatBox.val('');
			_room.push({
				pid: data.pid,
				cn: data.cn || data.players[data.pid].name,
				content: messageText.trim(),
				timestamp: firebase.database.ServerValue.TIMESTAMP
			});
		}
		return false;
	});

	var chatFiles = $('#chat-files'),
		uploadProgress = $('#chat-textbox .upload-progress');
	chatFiles.change(function(){
		if (this.files.length === 0)
			return;
		var formdata = new FormData();
		formdata.append("orderHunt", window.appLoc.orderHunt);
		for (var i = 0; i < this.files.length; i++)
			formdata.append("file[]", this.files[i]);
		$.ajax({
			url: "/chat/upload",
			type: "POST",
			data: formdata,
			processData: false,
			contentType: false,
			dataType: "JSON",
			xhr: function(){
				var myXhr = $.ajaxSettings.xhr();
				if (myXhr.upload) {
					myXhr.upload.addEventListener('progress', function(e){
						if (e.lengthComputable)
							uploadProgress.css('width', Math.ceil(e.loaded * 100 / e.total) + '%');
						else
							uploadProgress.width(0);
					}, false);
				}
				return myXhr;
			},
			//uploadProgress: OnProgress, 
			success: function(response){
				if (!(typeof response == 'object' && response.success === true && typeof response.files == 'object' && response.files.length))
					return this.error(response);
				for (i = 0; i < response.files.length; i++) {
					if (response.files[i]) {
						_room.push({
							pid: data.pid,
							cn: data.cn || data.players[data.pid].name,
							image: response.files[i],
							timestamp: firebase.database.ServerValue.TIMESTAMP
						});
					}
				}
				uploadProgress.width(0);
			},
			error: function(response){
				uploadProgress.width(0);
				/*debugger;
				console.log(response);*/
			},
			resetForm: true
		});
		chatFiles.replaceWith(chatFiles.val('').clone(true));
	});

	var chatContent = $(document.createElement('div')).addClass('chat-content container').prependTo(chatWrapper);
	var messagesWrapper = $(document.createElement('div')).addClass('messages').appendTo(chatContent),
		firstMsg = true;

	/*var niceScroll = chatContent.niceScroll({
		touchbehavior: true,
		cursordragontouch: true,
		preventmultitouchscrolling: false
	});*/

	var deferrs = [];

	function defer(src) {
		var img = new Image(),
			deferred = $.Deferred(), to, onload;
		onload = function(){
			img.onload = onload = emptyFunc;
			try {
				clearTimeout(to);
			} catch(E) {}
			deferred.resolve();
		};
		img.onload = onload;
		deferrs.push(deferred);
		to = setTimeout(onload, 5e3);
		img.src = src;
	}

	function scrollDown(block){
		chatContent[0].scrollTop = typeof block == 'object' ? chatContent[0].scrollTop + block.outerHeight() : chatContent[0].scrollHeight;
	}

	var pageCount, currentPageNumber = null, firstOne = true, working = true,
		pageRef = new firebase.util.Paginate(_room, 'timestamp', {
			pageSize: 20, maxCacheSize: 1e7
		});

	var handleMessage = function(msgs, reverse) {
		if (firstMsg) {
			$('#chat .app_loading').fadeOut(100, function(){
				$(this).remove();
			});
			firstMsg = false;
		}
		if (typeof msgs != 'object')
			return;
		var messages = [], m;
		if (typeof msgs.val == 'function') {
			msgs = [msgs.val()];
		} else {
			for (m in msgs)
				messages.push(msgs[m]);
			msgs = messages;
			messages = [];
		}
		for (m = 0; m < msgs.length; m++) {
			var msg = msgs[m];
			if (typeof msg == 'object' && typeof msg.pid == 'number' && typeof msg.timestamp == 'number') {
				if (typeof msg.content == 'string' && msg.content) {
					messages.push({
						pid: msg.pid,
						pname: msg.pname || null,
						cn: msg.cn || null,
						content: msg.content,
						timestamp: msg.timestamp
					});
				} else if (typeof msg.image == 'string' && msg.image) {
					defer(msg.image);
					messages.push({
						pid: msg.pid,
						pname: msg.pname || null,
						cn: msg.cn || null,
						image: msg.image,
						timestamp: msg.timestamp
					});
				}
			}
		}
		if (messages.length) {
			$.when.apply(this, deferrs).done(function(){
				var block = reverse ? $(document.createElement('div')).addClass('messages').prependTo(chatContent) : messagesWrapper;

				for (var i = 0; i < messages.length; i++)
					addMessage(messages[i], block);
				
				//niceScroll.resize();
				chatContent[0].scrollTop = reverse ? chatContent[0].scrollTop + block.outerHeight() : chatContent[0].scrollHeight;
				/*if (reverse) {
					imagesLoaded(chatContent[0], function(){
						scrollDown(block);
					});
				} else {
					imagesLoaded(chatContent[0], function(){
						scrollDown();
					});
				}*/
				working = false;
				chatContent.trigger('scroll');
			});
		}
	};

	pageRef.page.onPageCount(function(current, more){
		pageCount = current;
		if (currentPageNumber === null && pageCount > 0) {
			currentPageNumber = pageCount;
			pageRef.page.setPage(pageCount);
			pageRef.page.onPageChange(function(page){
				currentPageNumber = page;
			});
			pageRef.once('value', function(message){
				handleMessage(message.val(), true);
			});
		} else if (pageCount == 0) {
			firstOne = false;
			handleMessage();
		}
	});

	_room.orderByChild('timestamp').limitToLast(1).on('child_added', function(message){
		if (firstOne)
			firstOne = false;
		else
			handleMessage(message, false);
	});

	chatContent.scroll(function(){
		if (working)
			return;
		if (chatContent.scrollTop() > 100)
			return;
		working = true;
		var currPage = currentPageNumber;
		if (currPage > 0) {
			var p = pageRef.page.prev();
			if (currPage - 1 == p.currPage) {
				pageRef.once('value', function(message){
					handleMessage(message.val(), true);
				});
			}
		}
	});

	setTimeout(function(){
		$('#chat .app_loading').remove();
	}, 5e3);
	$(window).trigger('resize');

	setInterval(updateTime, 2e4);

	// disable scrolling

	/*$('.navbar-fixed-top,#chat-textbox,footer').on('touchmove', function(e){
		e.preventDefault();
	}, false);*/

})(jQuery);