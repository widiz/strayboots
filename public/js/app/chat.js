
//!function(t,e){"function"==typeof define&&define.amd?define("ev-emitter/ev-emitter",e):"object"==typeof module&&module.exports?module.exports=e():t.EvEmitter=e()}(this,function(){function t(){}var e=t.prototype;return e.on=function(t,e){if(t&&e){var i=this._events=this._events||{},n=i[t]=i[t]||[];return-1==n.indexOf(e)&&n.push(e),this}},e.once=function(t,e){if(t&&e){this.on(t,e);var i=this._onceEvents=this._onceEvents||{},n=i[t]=i[t]||[];return n[e]=!0,this}},e.off=function(t,e){var i=this._events&&this._events[t];if(i&&i.length){var n=i.indexOf(e);return-1!=n&&i.splice(n,1),this}},e.emitEvent=function(t,e){var i=this._events&&this._events[t];if(i&&i.length){var n=0,o=i[n];e=e||[];for(var r=this._onceEvents&&this._onceEvents[t];o;){var s=r&&r[o];s&&(this.off(t,o),delete r[o]),o.apply(this,e),n+=s?0:1,o=i[n]}return this}},t}),function(t,e){"use strict";"function"==typeof define&&define.amd?define(["ev-emitter/ev-emitter"],function(i){return e(t,i)}):"object"==typeof module&&module.exports?module.exports=e(t,require("ev-emitter")):t.imagesLoaded=e(t,t.EvEmitter)}(window,function(t,e){function i(t,e){for(var i in e)t[i]=e[i];return t}function n(t){var e=[];if(Array.isArray(t))e=t;else if("number"==typeof t.length)for(var i=0;i<t.length;i++)e.push(t[i]);else e.push(t);return e}function o(t,e,r){return this instanceof o?("string"==typeof t&&(t=document.querySelectorAll(t)),this.elements=n(t),this.options=i({},this.options),"function"==typeof e?r=e:i(this.options,e),r&&this.on("always",r),this.getImages(),h&&(this.jqDeferred=new h.Deferred),void setTimeout(function(){this.check()}.bind(this))):new o(t,e,r)}function r(t){this.img=t}function s(t,e){this.url=t,this.element=e,this.img=new Image}var h=t.jQuery,a=t.console;o.prototype=Object.create(e.prototype),o.prototype.options={},o.prototype.getImages=function(){this.images=[],this.elements.forEach(this.addElementImages,this)},o.prototype.addElementImages=function(t){"IMG"==t.nodeName&&this.addImage(t),this.options.background===!0&&this.addElementBackgroundImages(t);var e=t.nodeType;if(e&&d[e]){for(var i=t.querySelectorAll("img"),n=0;n<i.length;n++){var o=i[n];this.addImage(o)}if("string"==typeof this.options.background){var r=t.querySelectorAll(this.options.background);for(n=0;n<r.length;n++){var s=r[n];this.addElementBackgroundImages(s)}}}};var d={1:!0,9:!0,11:!0};return o.prototype.addElementBackgroundImages=function(t){var e=getComputedStyle(t);if(e)for(var i=/url\((['"])?(.*?)\1\)/gi,n=i.exec(e.backgroundImage);null!==n;){var o=n&&n[2];o&&this.addBackground(o,t),n=i.exec(e.backgroundImage)}},o.prototype.addImage=function(t){var e=new r(t);this.images.push(e)},o.prototype.addBackground=function(t,e){var i=new s(t,e);this.images.push(i)},o.prototype.check=function(){function t(t,i,n){setTimeout(function(){e.progress(t,i,n)})}var e=this;return this.progressedCount=0,this.hasAnyBroken=!1,this.images.length?void this.images.forEach(function(e){e.once("progress",t),e.check()}):void this.complete()},o.prototype.progress=function(t,e,i){this.progressedCount++,this.hasAnyBroken=this.hasAnyBroken||!t.isLoaded,this.emitEvent("progress",[this,t,e]),this.jqDeferred&&this.jqDeferred.notify&&this.jqDeferred.notify(this,t),this.progressedCount==this.images.length&&this.complete(),this.options.debug&&a&&a.log("progress: "+i,t,e)},o.prototype.complete=function(){var t=this.hasAnyBroken?"fail":"done";if(this.isComplete=!0,this.emitEvent(t,[this]),this.emitEvent("always",[this]),this.jqDeferred){var e=this.hasAnyBroken?"reject":"resolve";this.jqDeferred[e](this)}},r.prototype=Object.create(e.prototype),r.prototype.check=function(){var t=this.getIsImageComplete();return t?void this.confirm(0!==this.img.naturalWidth,"naturalWidth"):(this.proxyImage=new Image,this.proxyImage.addEventListener("load",this),this.proxyImage.addEventListener("error",this),this.img.addEventListener("load",this),this.img.addEventListener("error",this),void(this.proxyImage.src=this.img.src))},r.prototype.getIsImageComplete=function(){return this.img.complete&&void 0!==this.img.naturalWidth},r.prototype.confirm=function(t,e){this.isLoaded=t,this.emitEvent("progress",[this,this.img,e])},r.prototype.handleEvent=function(t){var e="on"+t.type;this[e]&&this[e](t)},r.prototype.onload=function(){this.confirm(!0,"onload"),this.unbindEvents()},r.prototype.onerror=function(){this.confirm(!1,"onerror"),this.unbindEvents()},r.prototype.unbindEvents=function(){this.proxyImage.removeEventListener("load",this),this.proxyImage.removeEventListener("error",this),this.img.removeEventListener("load",this),this.img.removeEventListener("error",this)},s.prototype=Object.create(r.prototype),s.prototype.check=function(){this.img.addEventListener("load",this),this.img.addEventListener("error",this),this.img.src=this.url;var t=this.getIsImageComplete();t&&(this.confirm(0!==this.img.naturalWidth,"naturalWidth"),this.unbindEvents())},s.prototype.unbindEvents=function(){this.img.removeEventListener("load",this),this.img.removeEventListener("error",this)},s.prototype.confirm=function(t,e){this.isLoaded=t,this.emitEvent("progress",[this,this.element,e])},o.makeJQueryPlugin=function(e){e=e||t.jQuery,e&&(h=e,h.fn.imagesLoaded=function(t,e){var i=new o(this,t,e);return i.jqDeferred.promise(h(this))})},o.makeJQueryPlugin(),o});

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
				time.text(moment(datetime).fromNow());
		});
	}

	var autolinker = new Autolinker(),
		emoji = new EmojiConvertor(),
		data = window.appLoc;

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
			var player = data.players[message.pid];
			$messageP.insertBefore($(
				'<div class="message-row" data-pid="' + message.pid + '">' +
					'<div class="message' + (isImg ? ' img' : '') + '">' +
						'<div class="message-table clearfix' + (message.pid == data.pid ? ' me' : '') + '">' +
							'<div class="message-columns image-column">' +
								'<div class="image-wrapper">' +
									'<img src="' + player.thumb + '">' +
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
			).appendTo(block).find('.time'));
		}
	}

	if (typeof firebase != 'object' || typeof data != 'object') {
		document.location.href = '/';
		return;
	}

	data.firebase = firebase.database();

	data.players[0] = {
		team: 0,
		email: "Strayboots Staff",
		fname: null,
		lname: null,
		thumb: null
	};

	for (var pid in data.players) {
		try {
			var p = data.players[pid];
			data.players[pid].name = ((p.fname || p.lname) ? toText(p.fname + ' ' + p.lname).trim() : '') || p.email;
			data.players[pid].thumb = p.thumb || '/img/unknown.jpg';
		} catch(e){}
	}

	var _room = data.firebase.ref().child(window.FB_PREFIX + 'orderhuntchat/' + data.orderHunt),
		chatWrapper = $('#chat'),
		chatBox = $('#chat-textbox textarea');

	$('.chat-button').click(function(){
		var messageText = chatBox.val();
		if (messageText.length) {
			chatBox.val('');
			_room.push({
				pid: data.pid,
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

	var chatContent = $(document.createElement('div')).addClass('chat-content').prependTo(chatWrapper);
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
						cn: msg.cn || null,
						content: msg.content,
						timestamp: msg.timestamp
					});
				} else if (typeof msg.image == 'string' && msg.image) {
					defer(msg.image);
					messages.push({
						pid: msg.pid,
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