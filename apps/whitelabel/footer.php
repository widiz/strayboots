<script type="text/javascript">
(function(){
	var fbFuncs = [],
		fbLoaded = false;
	window.fbFunc = function(f) {
		if (typeof f == 'function')
			fbFuncs.push(f);
		if (fbLoaded) {
			for (var i = 0; i < fbFuncs.length; i++) {
				try {
					fbFuncs[i]();
				} catch(E) {}
			}
		}
	};
	window.fbAsyncInit = function(){
		FB.init({
			appId: '302564933413297',
			status: true,
			xfbml: true,
			version: 'v2.7'
		});
		fbLoaded = true;
		window.fbFunc();
	};
})();
</script>
<script type="text/javascript">(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');ga('create','UA-7559873-20','auto');ga('send','pageview');</script>
<script type="text/javascript" src="https://connect.facebook.net/en_US/sdk.js" id="facebook-jssdk"></script>
<script type="text/javascript">
(function(){
	function addEvent(el, e, f){
		if (el === null)
			return el;
		return el.attachEvent ? el.attachEvent('on' + e, f) : el.addEventListener(e, f, false);
	}
	var form = document.getElementById('activation-form'),
		fblogin = document.getElementById('fblogin');
	function formCheck(e){
		var error = false,
			tmp = form.email.nextElementSibling,
			names = ['first_name', 'last_name'];
		if (form.email.value.length > 120 || form.email.value.match(/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/) === null) {
			error = true;
			tmp.innerText = "Email is invalid";
			tmp.style.display = 'block';
		} else {
			tmp.style.display = 'none';
		}
		for (var i = 0; i < names.length; i++) {
			tmp = form[names[i]].nextElementSibling;
			if (form[names[i]].value.length < 2) {
				error = true;
				tmp.innerText = "Too short";
				tmp.style.display = 'block';
			} else if (form[names[i]].value.length > 20) {
				error = true;
				tmp.innerText = "Too long";
				tmp.style.display = 'block';
			} else  {
				tmp.style.display = 'none';
			}
		}
		if (error && e)
			e.preventDefault();
		return !error;
	}
	addEvent(fblogin, 'click', function(e){
		e.preventDefault();
		window.fbFunc(function(){
			try {
				FB.login(function(response) {
					if (response.authResponse) {
						FB.api('/me?fields=id,first_name,last_name,email', function(response) {
							if (!(typeof response == 'object' && response.id > 0))
								throw '';
							form.firstNameField.value = response.first_name;
							form.lastNameField.value = response.last_name;
							form.emailField.value = response.email;
							form.emailField.readonly = true;
							form.networkIdField.value = response.id;
							form.networkField.value = 1;
							if (formCheck())
								form.submit();
						});
					} else {
						throw '';
					}
				}, {
					scope: 'email,public_profile'
				});
			} catch(E) { }
		});
	});
	addEvent(form, 'submit', formCheck);
})();
</script>
</body>
</html><? ob_end_flush() ?>