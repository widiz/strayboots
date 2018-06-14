(function(exports){
	var initialized = false,
		callbacks = [];
	exports.gmapInit = function(){
		initialized = true;
		for (var i = 0; i < callbacks.length; i++) {
			try {
				callbacks[i]();
			} catch(e) {}
		}
	};
	exports.gmap = function(callback){
		if (typeof callback != 'function')
			throw "Callback is not a function";
		if (initialized)
			callback();
		else
			callbacks.push(callback);
	};
})(window);