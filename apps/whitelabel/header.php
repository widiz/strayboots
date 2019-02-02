<?php
ob_start(function($c){
	return isset($_COOKIE['_sb_']) ? $c : str_replace(["\n", "\t"], '', $c); 
});
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
	<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
	<link href="//fonts.googleapis.com/css?family=Open+Sans:300,400" rel="stylesheet"/>
	<style type="text/css">
		* {
			-webkit-box-sizing: border-box;
			-moz-box-sizing: border-box;
			box-sizing: border-box;
		}
		html {
			margin: 0;
			padding: 0;
		}
		body {
			font-family: 'Open Sans', sans-serif;
			text-align: center;
			color: #000;
			font-weight: 300;
			padding: 0;
			margin: 0;
		}
		img {
			border: 0;
		}
		.content-wrapper  {
			max-width: 700px;
			margin: 0 auto;
			padding: 15px 0;
		}
		input {
			font-family: 'Open Sans', sans-serif;
			border-radius: 10px;
			background-color: #fff;
			border: 1px solid #ccc;
			line-height: 50px;
			height: 50px;
			margin-top:20px;
			padding: 0 20px;
			color: #000;
			font-size: 20px;
			margin: 0 0 15px;
			width: 100%;
    		vertical-align: middle;
		}
		button, input[type="submit"] {
    		-webkit-appearance: none;
    		-moz-appearance: none;
    		appearance: none;
    	}
		input:focus {
			outline: none;
		}
		h1 {
			font-weight: 300;
			margin: 10px 0;
		}
		p {
			margin: 20px 0;
			padding: 0;
		}
		label.error {
			display: none;
			margin: -7px 0 9px;
		}
		.btn {
			display: inline-block;
			height: auto;
			width: 100%;
			margin-bottom: 0;
			font-weight: 400;
			line-height: 1.42857143;
			text-align: center;
			white-space: nowrap;
			vertical-align: middle;
			cursor: pointer;
			-webkit-user-select: none;
			-moz-user-select: none;
			-ms-user-select: none;
			user-select: none;
			background-image: none;
			border: 1px solid transparent;
			-webkit-border-radius: 4px;
			-moz-border-radius: 4px;
			border-radius: 4px;
			font-size: 18px;
			padding: 10px;
			color: #fff;
			-ms-touch-action: manipulation;
			touch-action: manipulation;
		}
		a {
			text-decoration: none;
		}
		.btn-success {
			background-color: #5cb85c;
			border-color: #4cae4c;
		}
		.btn-success:hover {
			color: #fff;
			background-color: #449d44;
			border-color: #398439;
		}
		.btn-fb {
			background: #3B5998;
		}
		.row {
			overflow: hidden;
			width: 100%;
		}
		.row:after {
			clear: both;
		}
		.grid-100, .grid-50, .grid-40, .grid-60 {
			float: left;
			padding: 0 10px;
		}
		.grid-100 {
			width: 100%;
		}
		.grid-50 {
			width: 50%;
		}
		.grid-60 {
			width: 60%;
		}
		.grid-40 {
			width: 40%;
		}
		@media all and (max-width: 640px) {
			.grid-50 {
				width: 100%;
			}
			.btn {width:100%;}
		}
		.navbar-default {
			background: #F39C12;
			box-shadow: none;
			border-bottom:2px solid #000;
		}
	</style>
