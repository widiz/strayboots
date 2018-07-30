module.exports = {
	'SB Automated test': function(browser, teamName){

		var teamName = "Automated Test " + Math.floor(Math.random() * 100);

		browser
			//Login
			.url(browser.launchUrl)
			.waitForElementVisible('body', 2000)
			.assert.title('Strayboots')
			.assert.visible('button.subxbtn.email')
			.click('button.subxbtn.email')
			.waitForElementVisible('#emailField', 500)
			.execute('$("#emailField").val("autotest@strayboots.com")')
			// .setValue('#emailField', 'autotest@strayboots.com')
			.execute('$("#activationField").val("autotest-91801")')
			// .setValue('#activationField', 'autotest-91801')
			.execute('$("#activation-form").submit()')
			//Name
			.waitForElementVisible('#fieldName', 2000)
			.execute('$("#fieldName").val("Automated Test 70")')
			// .setValue('#fieldName', teamName)
			.execute('$("#name-form").submit()')
			.waitForElementVisible('#welcome-screen .subxbtn', 2000)
			.click('#welcome-screen .subxbtn')
			.pause(500)
			.assert.containsText('#team-name', 'Automated Test 70')
			// .assert.containsText('#team-name', teamName)
			//Question1
			// .setValue('#answerField', 'twenty')
			.execute('$("#answerField").val("twenty")')
			.execute('$("#main-form").submit()')
			.waitForElementVisible('#playground.pmessagebox', 2000)
			.click('.options .btn-success')
			.waitForElementVisible('#main-form', 2000)
			.assert.containsText('#header-score div:nth-child(3) span', "2 /")
			//Question2
			// .setValue('#answerField', 'givecraft')
			.execute('$("#answerField").val("givecraft")')
			.execute('$("#main-form").submit()')
			.waitForElementVisible('#playground.pmessagebox', 2000)
			.click('.options .btn-success')
			.waitForElementVisible('#main-form', 2000)
			.assert.containsText('#header-score div:nth-child(3) span', "3 /")
			//Question3
			.execute('$("#skip-form").submit()')
			.waitForElementVisible('#playground.pmessagebox', 2000)
			.click('.options .btn-success')
			.waitForElementVisible('#main-form', 2000)
			.assert.containsText('#header-score div:nth-child(3) span', "4 /")
			//Question4
			.execute('$("#main-form").submit()')
			.waitForElementVisible('body', 2000)
			.waitForElementVisible('.options .btn-danger', 2000)
			.assert.containsText('#header-score div:nth-child(3) span', "5 /")
			.execute('$("#answerField").val("test")')
			.execute('$("#main-form").submit()')
			.assert.containsText('.container h2', "Great!")
			//Question5
			.execute('$("#r1").prop("checked",true);$("#main-form").submit()')
			.waitForElementVisible('#playground.pmessagebox', 2000)
			.click('.options .btn-success')
			.waitForElementVisible('#main-form', 2000)
			.assert.containsText('#header-score div:nth-child(3) span', "6 /")
			//Question6
			.execute('$("#skip-form").submit()')
			.waitForElementVisible('#playground.pmessagebox', 2000)
			.click('.options .btn-success')
			//Question7
			.assert.containsText('#header-score div:nth-child(3) span', "7 /")
			.execute('$("#skip-form").submit()')
			.click('.options .btn-success')
			.waitForElementVisible('body', 2000)
			.assert.containsText('.question h2', "Great job!")
			.click('#navbar > ul > li:nth-child(7) > a')
			.pause(2000)
			.end();

	}
};