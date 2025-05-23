window.translate = {
	'Warning': 'אזהרה',
	'WARNING!<br>You are about to log-in as the LEADER of team %teamname%<br>Are you sure you want to do it?': 'אזהרה!<br>הנך מתחבר כמנהיג הקבוצה %teamname%<br>האם אתה בטוח?',
	'OK': 'אישור',
	'Login as a player': 'התחבר כשחקן',
	"Pick a funky name for your team and let's win this hunt!": "בחרו שם מצחיק לקבוצה והתחילו את המשחק!",
	"Name is too short": "השם קצר מדי",
	"Name is too long": "השם ארוך מדי",
	'Last chance': 'הזדמנות אחרונה',
	'You got 1 last try! Would you like to take a hint?': 'נשאר רק ניסיון אחד! האם תרצו רמז?',
	'Yes': 'כן',
	'No': 'לא',
	'days': 'ימים',
	'Skip': 'דלג',
	'Cancel': 'ביטול',
	'You got 1 last try!': 'נשאר רק ניסיון אחד!',
	'Close': 'סגור',
	"Please fill all the characters": "אנא מלאו את כל התווים",
	"Are you sure you want to skip? There's no way back...": "בטוח שברצונכם לדלג? אין דרך חזרה...",
	"Using a HINT will deduct half of the points. Are you sure?": "שימוש ברמז מוריד מחצית מהניקוד. להמשיך בכל זאת?",
	'We just successfully finished our Strayboots scavenger hunt!': 'סיימנו בהצלחה את המסלול!',
	'Strayboots Scavenger Hunts': 'Strayboots Scavenger Hunts',
	'That was so much fun!!! #teambuilding #scavengerhunt @strayboots': 'היה כיף!!! #teambuilding #scavengerhunt @strayboots',
	"Are you sure you want to exit the hunt?": "בטוח שברצונך להתנתק?",
	"You've got 20 minutes left for your scavenger hunt.<br>Let's get things rolling!": "נשארו 20 דקות לסיום.<br>זה הזמן לזוז!",
	'Bonus Question': 'שאלת בונוס',
	'Everyone\'s Ready?': 'מוכנים?',
	'Answer': 'תשובה',
	'Submit': 'שלח',
	"Something went wrong, please try again": "שגיאה; אנא נסה שנית",
	'For %points% points': '%points% נקודות',
	'Message from %name%' : 'הודעה מ%name%',
	'Win a Prize': 'זכה בפרס',
	"Heads up, a bonus question is coming up soon...": "שימו לב, שאלת בונוס מתקרבת...",
};
try {
	var btIval = setInterval(function(){
		if (typeof bootbox === 'object'){
			bootbox.setLocale('he');
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
				required: "השדה הזה הינו שדה חובה",
				remote: "נא לתקן שדה זה",
				email: "נא למלא כתובת דוא\"ל חוקית",
				url: "נא למלא כתובת אינטרנט חוקית",
				date: "נא למלא תאריך חוקי",
				dateISO: "נא למלא תאריך חוקי (ISO)",
				number: "נא למלא מספר",
				digits: "נא למלא רק מספרים",
				creditcard: "נא למלא מספר כרטיס אשראי חוקי",
				equalTo: "נא למלא את אותו ערך שוב",
				extension: "נא למלא ערך עם סיומת חוקית",
				maxlength: $.validator.format( ".נא לא למלא יותר מ- {0} תווים" ),
				minlength: $.validator.format( "נא למלא לפחות {0} תווים" ),
				rangelength: $.validator.format( "נא למלא ערך בין {0} ל- {1} תווים" ),
				range: $.validator.format( "נא למלא ערך בין {0} ל- {1}" ),
				max: $.validator.format( "נא למלא ערך קטן או שווה ל- {0}" ),
				min: $.validator.format( "נא למלא ערך גדול או שווה ל- {0}" )
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