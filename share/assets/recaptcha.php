<?php

return function() {

	$api_key    = conf('RECAPTCHA_API_KEY');
	$api_secret = conf('RECAPTCHA_API_SECRET');
	$theme      = 'light';
	$selector   = 'captcha';
	$lang       = conf('language');

	return [
		'versions' => [
			'master' => [
				'js' => [
					'https://www.google.com/recaptcha/api.js?onload=recaptcha&render=explicit&hl='.$lang,
 				],
			],
		],
		'config' => [
			'before' => '<script type="text/javascript">
			var recaptcha = function() {
				try{
					grecaptcha.render("'.$selector.'", {
						sitekey : "'.$api_key.'",
						theme   : "'.$theme.'",
					});
				} catch(err){
					console.log(err);
				}
			};
			</script>',
			'inline_cache' => true,
			// 	'no_cache' => true,
			'main_type' => 'user',
		],
	];

};
