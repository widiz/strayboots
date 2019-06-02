window.translate = {
	"Warning": "Atenção",
	"WARNING!<br>You are about to log-in as the LEADER of team %teamname%<br>Are you sure you want to do it?": "ATENÇÃO!<br>Está prestes a aceder como LÍDER da equipa %teamname%<br>Tem certeza que deseja continuar?",
	"OK": "OK",
	"Login as a player": "Aceder como jogador",
	"Pick a funky name for your team and let's win this hunt!": "Escolha um nome engraçado para a sua equipa e vamos lá ganhar essa competição!",
	"Name is too short": "O nome é muito curto",
	"Name is too long": "O nome é muito longo",
	"Last chance": "Última hipótese",
	"You got 1 last try! Would you like to take a hint?": "Ainda tem uma última tentativa! Não quer utilizar a ajuda?",
	"Yes": "Sim",
	"No": "Não",
	"days": "dias",
	"Skip": "Passar",
	"Cancel": "Cancelar",
	"You got 1 last try!": "Ainda tem uma última tentativa!",
	"Close": "Fechar",
	"Please fill all the characters": "Por favor preencha todos os caracteres",
	"Are you sure you want to skip? There's no way back...": "Tem a certeza que deseja passar? Não há volta atrás...",
	"Using a HINT will deduct half of the points. Are you sure?": "Ao utilizar uma AJUDA irá reduzir os pontos para metada. Deseja continuar?",
	"We just successfully finished our Strayboots scavenger hunt!": "Acabaram de terminar o Peddy Paper Strayboots!",
	"Strayboots Scavenger Hunts": "Peddy Paper Strayboots",
	"That was so much fun!!! #teambuilding #scavengerhunt @strayboots": "Divertimo-nos ao máximo!!! #teambuilding #cacaaotesouro #peddypaper #scavengerhunt @strayboots",
	"Are you sure you want to exit the hunt?": "Tem a certeza que deseja sair do jogo?",
	"You've got 20 minutes left for your scavenger hunt.<br>Let's get things rolling!": "Já só tem 20 minutos para terminar o jogo.<br>Vamos lá avançar rapidamente!",
	"Bonus Question": "Pergunta Bónus",
	"Everyone\'s Ready?": "Está pronto?",
	"Answer": "Resposta",
	"Submit": "Enviar",
	"Something went wrong, please try again": "Alguma coisa correu mal. Por favor tente novamente",
	"For %points% points": "Para %points% pontos",
	"Win a Prize": "Ganhe um prémio",
	'Message from %name%' : 'Mensagem de %name%',
	"Heads up, a bonus question is coming up soon...": "Atenção, uma pergunta vem já a seguir...",
};
try {
	var btIval = setInterval(function(){
		if (typeof bootbox === 'object'){
			bootbox.setLocale('pt');
			clearInterval(btIval);
			btIval = null;
		}
	}, 100);
	setTimeout(function(){
		if (btIval !== null)
			clearInterval(btIval);
	}, 1e4);
} catch(E) {}