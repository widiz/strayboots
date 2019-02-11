<?php
define('fullName', true);
define('noFacebook', true);
$buttonText = 'Let\'s Hunt!!!';

define('APP_PATH', dirname(dirname(__DIR__)));
$config = require APP_PATH . '/config/config.php';
require APP_PATH . '/config/services.php';
$loader = new \Phalcon\Loader();
$loader->registerDirs([
	$config->application->modelsDir,
])->register();

$now = date('Y-m-d H:i:s');
$builder = $di->get('modelsManager')->createBuilder()
				->columns([
					'OrderHunts.hunt_id', 'Hunts.name as huntname', 'Hunts.city_id', 'Cities.country_id',
					'OrderHunts.id', 'Cities.name AS cityname', 'Countries.name AS countryname'
				])
				->where("OrderHunts.finish > '" . $now . "' AND OrderHunts.start <= '" . $now . "' AND OrderHunts.flags & 260 = 256")
				->from('OrderHunts') // flags & (256(B2C) + 4(CANCELED)) = 256(B2C ONLY - not canceled)
				->leftJoin('Hunts', 'Hunts.id = OrderHunts.hunt_id')
				->leftJoin('Cities', 'Cities.id = Hunts.city_id')
				->leftJoin('Countries', 'Countries.id = Cities.country_id');

$cityHunts = [];
$countries = [];
foreach ($builder->getQuery()->execute() as $oh) {
	if (!isset($countries[$oh->country_id]))
		$countries[$oh->country_id] = ['cities' => [], 'name' => $oh->countryname];
	$countries[$oh->country_id]['cities'][$oh->city_id] = $oh->cityname;
	if (!isset($cityHunts[$oh->city_id]))
		$cityHunts[$oh->city_id] = [];
	$cityHunts[$oh->city_id][$oh->hunt_id] = ['id' => $oh->id, 'text' => $oh->huntname];
}
foreach ($countries as &$c)
	ksort($c['cities']);
foreach ($cityHunts as &$c)
	$c = array_values($c);

function before_form() {
	global $countries;
?>
	<div class="row">
		<div class="grid-100">
			<select class="select2" name="city" placeholder="Choose Your City" style="width:100%" id="city-select">
				<option disabled selected></option>
<? foreach ($countries as $id => $country): 
				//<optgroup label="<?= $country['name'] ? >">
foreach ($country['cities'] as $cid => $city): ?>
					<option value="<?= $cid ?>"><?= $city ?></option>
<? endforeach;
				//</optgroup>
endforeach ?>
			</select>
			<label class="error" id="cityError"></label>
		</div>
	</div>
	<div class="row">
		<div class="grid-100">
			<select class="select2" name="id" placeholder="Choose Your hunt" style="width:100%" id="hunt-select" disabled></select>
			<label class="error" id="huntError"></label>
		</div>
	</div>
<?
}
function form_check() {
?>
	if (form.city.value === '') {
		$('#cityError').css('display', 'block').text('Please select a city');
		error = true;
	} else {
		$('#cityError').hide();
		if (form.id.value === '') {
			$('#huntError').css('display', 'block').text('Please select a hunt');
			error = true;
		} else {
			$('#huntError').hide();
		}
	}
<?
}
function before_options() {
?>
	<div class="row">
		<div class="grid-100">
			<p style="margin: 0 0 20px">
				Our team is working very hard to provide you the best scavenger hunts! At the end of your hunt you’ll have the option to pay based on your experience. Liked it? Show your appreciation!<br>(the average payment is $20 USD per person, just sayin’)
			</p>
		</div>
	</div>
<?
}
function after_body() {
	global $cityHunts;
?>
<script type="text/javascript">
	var cityHunts=<?= json_encode($cityHunts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
	$(function(){
		$('.select2').each(function(){
			$(this).select2({
				placeholder: $(this).attr('placeholder') || undefined
			});
		});
		var $huntSelect = $('#hunt-select'),
			$citySelect = $('#city-select');
		$citySelect.change(function() {
			var data = cityHunts[$citySelect.val()] || [];
			data.unshift({id: '', text: ''});
			$huntSelect.empty().prop('disabled', data.length  === 1).select2({
				data: data,
				placeholder: $huntSelect.attr('placeholder') || undefined
			});
		}).trigger('change');
	});
</script>
<?
}

require APP_PATH . '/apps/whitelabel/header.php';
?>
	<title>Strayboots tours</title>
	<link rel="stylesheet" href="/template/css/plugins/select2/select2.min.css" />
	<style type="text/css">
		html {
			height: 100%;
		}
		body {
			background: url(/img/bgn-2.jpg) 50% 50px no-repeat;
			background-size: 100%;
			-webkit-background-size: cover;
			-moz-background-size: cover;
			background-size: cover;
			background-attachment: fixed;
			min-height: 99.99%;
			padding-top: 55px;
		}
		label.error {
			color: #fff;
		}
		h1 {
			color: #fff;
			padding-bottom: 15px;
		}
		h2 {
			color: #fff;
		}
		.navbar-default {
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			background: #F39C12;
			box-shadow: none;
			border-bottom: 2px solid #000;
		}

		p, #activation-form div{
			color: #fff;
		}
		#activation-form div a{
			/*color: #482567;*/
			color: #9868c2;
		}

		.select2-container {
			margin: 0 0 15px;
			text-align: left;
		}
		.select2-search__field {
			height: 36px;
		}
		.select2-container--default .select2-selection--single .select2-selection__arrow {
			height: 50px;
			width: 30px;
		}
		.select2-container .select2-selection--single {
			height: 50px;
			border-radius: 10px;
			outline: none;
		}
		.select2-container--default .select2-selection--single .select2-selection__rendered,
		.select2-container--default .select2-selection--single .select2-selection__placeholder {
			line-height: 50px;
			font-weight: 400;
			padding: 0 20px;
			font-size: 20px;
			color: #858585;
		}
		.select2-container--default .select2-selection--single .select2-selection__placeholder {
			padding: 0;
		}
	</style>
	<script type="text/javascript" src="/template/js/jquery-2.1.1.js"></script>
	<script type="text/javascript" src="/template/js/plugins/select2/select2.full.min.js"></script>
</head>
<body>
	<div class="navbar-default navbar-fixed-top">
		<div class="container">
			<a class="navbar-brand logo" href="/">
				<img src="/img/logo.png" alt="Strayboots" height="50" width="164">
			</a>
		</div>
	</div>
	<div class="content-wrapper">
		<h1>Welcome to The Amazing World of Strayboots!</h1>
		<div id="page1">
			<div class="row">
				<div class="grid-100">
					<h2>Are you ready for your scavenger hunt adventure?</h2>
					<p>
						We love scavenger hunt, and we take pride in our quality product. This is why we are offering you something that no one has ever done - A <b>Pay What You Want</b> Scavenger Hunt!
						<br><br>
						Ready?
					</p>
					<button class="btn btn-success" onclick="$('#page1').slideUp();$('#page2').slideDown()" style="width:auto">Click here to choose your city!</button>
				</div>
			</div>
		</div>
		<div id="page2" style="display:none">
			<? require APP_PATH . '/apps/whitelabel/form.php'; ?>
		</div>
	</div>
<? require APP_PATH . '/apps/whitelabel/footer.php'; ?>