window.translate = {
	"Warning": "تنبيه",
	"WARNING!<br>You are about to log-in as the LEADER of team %teamname%<br>Are you sure you want to do it?": "تنبيه!<br>أنت على وشك تسجيل الدخول بصفتك قائد الفريق %teamname%<br>هل أنت متأكد من رغبتك؟",
	"OK": "حسناً",
	"Login as a player": "تسجيل الدخول كلاعب",
	"Pick a funky name for your team and let's win this hunt!": "اختر اسماً مميزاً لفريقك ودعنا نربح هذا البحث عن الكنز!",
	"Name is too short": "الاسم قصير جداً",
	"Name is too long": "الاسم أطول من الحد المسموح",
	"Last chance": "الفرصة الأخيرة",
	"You got 1 last try! Would you like to take a hint?": "لديك محاولة أخيرة! هل ترغب في استخدم التلميح؟",
	"Yes": "نعم",
	"No": "لا",
	"days": "أيام",
	"Skip": "تخطي",
	"Cancel": "إلغاء",
	"You got 1 last try!": "لديك محاولة أخيرة!",
	"Close": "إغلاق",
	"Please fill all the characters": "يرجى تعبئة كافة الأحرف",
	"Are you sure you want to skip? There's no way back...": "هل أنت متأكد من رغبتك في التخطي؟ ليس هناك طريقة للعودة...",
	"Using a HINT will deduct half of the points. Are you sure?": " سيتم خصم نصف النقاط في حال استخدام التلميحات. هل أنت متأكد؟",
	"We just successfully finished our Strayboots scavenger hunt!": "لقد انتهينا بنجاح من لعبة Straybootsللبحث عن الكنز!",
	"Strayboots Scavenger Hunts": "لعبة Strayboots  للبحث عن الكنز",
	"That was so much fun!!! #teambuilding #scavengerhunt @strayboots": "كان الأمر ممتعًا حقًا!!! #تكوين_فريق #لعبة_تفتيكان أمراً ممتعاً للغاية!!! #تكوين_الفريق #لعبة_البحث عن الكنز @strayboots",
	"Are you sure you want to exit the hunt?": "هل أنت متأكد من رغبتك في تسجيل الخروج من البحث عن الكنز؟",
	"You've got 20 minutes left for your scavenger hunt.<br>Let's get things rolling!": "تبقى لديك 20 دقيقة للبحث عن الكنز . <br>لنواصل البحث!",
	"Bonus Question": "سؤال بمكافأة إضافية",
	"Everyone\'s Ready?": "هل أنت جاهز؟",
	"Answer": "الإجابة",
	"Submit": "إرسال",
	"Something went wrong, please try again": "حدث خطأ ما. يُرجى إعادة المحاولة",
	"For %points% points": "لعدد %points% نقطة",
	"Win a Prize": "اربح جائزة",
	'Message from %name%' : 'رسالة من %name%',
	'Team Member Name': 'اسم عضو الفريق',
	'Team Member ID Number': 'رقم هوية عضو الفريق',
	'Team Member Email': 'البريد الالكتروني لعضو الفريق',
	"Heads up, a bonus question is coming up soon...": "انتبه، قريباً سيتم طرح سؤال بمكافأة إضافية..."
};

try {
	var btIval = setInterval(function(){
		if (typeof bootbox === 'object'){
			bootbox.setLocale('ar');
			clearInterval(btIval);
			btIval = null;
		}
	}, 100);
	setTimeout(function(){
		if (btIval !== null)
			clearInterval(btIval);
	}, 1e4);
} catch(E) {}
try {
	var validatorIval = setInterval(function(){
		if (typeof $.validator !== 'undefined'){
			$.extend( $.validator.messages, {
				required: "هذا الحقل إلزامي",
				remote: "يرجى تصحيح هذا الحقل للمتابعة",
				email: "رجاء إدخال عنوان بريد إلكتروني صحيح",
				url: "رجاء إدخال عنوان موقع إلكتروني صحيح",
				date: "رجاء إدخال تاريخ صحيح",
				dateISO: "رجاء إدخال تاريخ صحيح (ISO)",
				number: "رجاء إدخال عدد بطريقة صحيحة",
				digits: "رجاء إدخال أرقام فقط",
				creditcard: "رجاء إدخال رقم بطاقة ائتمان صحيح",
				equalTo: "رجاء إدخال نفس القيمة",
				extension: "رجاء إدخال ملف بامتداد موافق عليه",
				maxlength: $.validator.format( "الحد الأقصى لعدد الحروف هو {0}" ),
				minlength: $.validator.format( "الحد الأدنى لعدد الحروف هو {0}" ),
				rangelength: $.validator.format( "عدد الحروف يجب أن يكون بين {0} و {1}" ),
				range: $.validator.format( "رجاء إدخال عدد قيمته بين {0} و {1}" ),
				max: $.validator.format( "رجاء إدخال عدد أقل من أو يساوي {0}" ),
				min: $.validator.format( "رجاء إدخال عدد أكبر من أو يساوي {0}" )
			} );
			clearInterval(validatorIval);
			validatorIval = null;
		}
	}, 100);
	setTimeout(function(){
		if (validatorIval !== null)
			clearInterval(validatorIval);
	}, 1e4);
} catch(E) {}