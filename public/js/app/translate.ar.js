window.translate = {
	"Warning": "تحذير",
	"WARNING!<br>You are about to log-in as the LEADER of team %teamname%<br>Are you sure you want to do it?": "تحذير!<br>أنت على وشك تسجيل الدخول بصفتك قائد الفريق %teamname%<br>هل أنت متأكد من رغبتك في القيام بذلك؟",
	"OK": "حسنًا",
	"Login as a player": "تسجيل الدخول كلاعب",
	"Pick a funky name for your team and let's win this hunt!": "اختر اسمًا غير تقليدي لفريقك وهيا لتفوز بهذه المطاردة!",
	"Name is too short": "الاسم قصير جدًا",
	"Name is too long": "الاسم طويل جدًا",
	"Last chance": "آخر فرصة",
	"You got 1 last try! Would you like to take a hint?": "لقد حصلت على محاولة أخيرة! هل تود أن تأخذ تلميحا؟ ",
	"Yes": "نعم",
	"No": "لا",
	"days": "أيام",
	"Skip": "تخطٍ",
	"Cancel": "إلغاء",
	"You got 1 last try!": "لقد حصلت على محاولة أخيرة!",
	"Close": "إغلاق",
	"Please fill all the characters": "يرجى ملء جميع الحروف",
	"Are you sure you want to skip? There's no way back...": "هل أنت متأكد أنك تريد التخطي؟ ليس هناك طريقة للعودة...",
	"Using a HINT will deduct half of the points. Are you sure?": "استخدام تلميح سيخصم نصف النقاط. هل أنت متأكد؟",
	"We just successfully finished our Strayboots scavenger hunt!": "لقد انتهينا للتو بنجاح من مطاردة زبّال Strayboots!",
	"Strayboots Scavenger Hunts": "مطاردات زبّال Strayboots",
	"That was so much fun!!! #teambuilding #scavengerhunt @strayboots": "كان الأمر ممتعًا حقًا!!! #تكوين_فريق #مطاردة_زبّال @strayboots",
	"Are you sure you want to exit the hunt?": "هل أنت متأكد من أنك تريد الخروج من المطاردة؟",
	"You've got 20 minutes left for your scavenger hunt.<br>Let's get things rolling!": "تتبقى 20 دقيقة في محاولتك لمطاردة الزبّال. <br>لتواصل المطاردة!",
	"Bonus Question": "سؤال إضافي",
	"Everyone\'s Ready?": "هل أنت جاهز؟",
	"Answer": "الإجابة",
	"Submit": "تسليم",
	"Something went wrong, please try again": "حدث خطأ ما. يُرجى إعادة المحاولة",
	"For %points% points": "لـ %points% من النقاط",
	"Win a Prize": "اربح جائزة",
	'Message from %name%' : 'رسالة من %name%',
	"Heads up, a bonus question is coming up soon...": "انتبه، سيتم طرح سؤال إضافي قريبًا...",
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