<?php

return function() {

$css_tpl = 'background: url("//s3.eu-central-1.amazonaws.com/assetgeek/social_%name.png"); width: 15px; height: 15px; background-size: 15px; margin-top: 3px;';

return ['versions' => ['master' => ['css' => '
	.icon-oauth-vk			{ '.str_replace('%name', 'vk', $css_tpl).' }
	.icon-oauth-facebook	{ '.str_replace('%name', 'fb', $css_tpl).' }
	.icon-oauth-ok			{ '.str_replace('%name', 'ok', $css_tpl).' }
	.icon-oauth-google		{ '.str_replace('%name', 'gpl', $css_tpl).' }
	.icon-oauth-youtube		{ '.str_replace('%name', 'youtube', $css_tpl).' }
	.icon-oauth-twitter		{ '.str_replace('%name', 'tw', $css_tpl).' }
	.icon-oauth-odnoklassniki { '.str_replace('%name', 'ok', $css_tpl).' }
	.icon-oauth-steamcommunity { '.str_replace('%name', 'steam', $css_tpl).' }
']]];

};
