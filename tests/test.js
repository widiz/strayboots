module.exports = {
	'SB Automated test': function(browser){

		var teamName = 'Automated Test ' + Math.floor(Math.random() * 100);

		browser
			//Login
			.url(browser.launchUrl)
			.waitForElementVisible('body', 2000)
			.assert.title('Strayboots')
			.assert.visible('button.subxbtn.email')
			.click('button.subxbtn.email')
			.waitForElementVisible('#emailField', 500)
			.setValue('#emailField', 'autotest@strayboots.com')
			.setValue('#activationField', 'autotest-91801')
			.execute('$("#activation-form").submit()')
			//Name
			.waitForElementVisible('#fieldName', 2000)
			.setValue('#fieldName', teamName)
			.execute('$("#name-form").submit()')
			.waitForElementVisible('#welcome-screen .subxbtn', 2000)
			.click('#welcome-screen .subxbtn')
			.pause(500)
			.assert.containsText('#team-name', teamName)
			//Question1
			.setValue('#answerField', 'twenty')
			.execute('$("#main-form").submit()')
			.waitForElementVisible('#playground.pmessagebox', 2000)
			.click('.options .btn-success')
			.waitForElementVisible('#main-form', 2000)
			.assert.containsText('#header-score div:nth-child(3) span', "2 /")
			//Question2
			.setValue('#answerField', 'givecraft')
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
			.waitForElementVisible('body', 2000)
			.assert.containsText('.question h2', "Great job!")
			.end();

	}
};