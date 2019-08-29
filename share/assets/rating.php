<?php

return [
'versions' => ['master' => [
	'js' => [
		WEB_PATH. 'js/rating/jquery.raty.js',
		'$(".rating-medal").raty({
			hints: [1, 2, 3, 4, 5],
			readOnly: function() { return $(this).attr("data-readOnly") == "true"; }, 
			halfShow: true,
			score: function() { return $(this).attr("data-score"); }, 
			starType : "i"
		});',
	],
	'css' => ['content' => '
		.star-on-png {
			padding: 0;
			position: relative;
			top: 5px;
			display: inline-block;
			width: 14px;
			height: 25px;
			background: transparent url(/theme/default/icon/medal-orange.svg) center top no-repeat;
			background-size: cover;
		}
		.star-off-png {
			padding: 0;
			position: relative;
			top: 5px;
			display: inline-block;
			width: 14px;
			height: 25px;
			background: transparent url(/theme/default/icon/medal-gray.svg) center top no-repeat;
			background-size: cover;
		}
		.star-half-png {
			padding: 0;
			position: relative;
			top: 5px;
			display: inline-block;
			width: 14px;
			height: 25px;
			background: transparent url(/theme/default/icon/medal-half-grey.svg) center top no-repeat;
			background-size: cover;
		}
	'],
]],
'require' => ['asset' => 'jquery'],
];
