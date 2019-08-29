<?php

return [
	'versions' => [
		'master' => [
			'css' => ['
.left_area { margin-left:20px; margin-right:20px; width:410px; float:left; word-wrap:break-word; }
@media (min-width:1650px) {
	.left_area { margin-left:1%; margin-right:1%; width:20%; max-width:300px; }
	.center_area { margin-left:25%; margin-right:1%; width:74%; }
}
.cssfw-bs2 .modal { width:auto; }
.tab-content { overflow:visible; }
.chzn-container { color:black; }
.portlet-content .pagination { margin-top: 0; margin-bottom: 0; }

.cssfw-bs3 .table .checkbox-inline { padding-top: 0; }
.cssfw-bs3 textarea { max-width:inherit; min-width: 100%; }

.cssfw-bs3 .stacked-item .form-control { display: inline-block; }
.cssfw-bs3 .stacked-item input[type=number], 
.cssfw-bs3 .form-group .small-number { width: 120px; }
.cssfw-bs3 .form-group select[name=order_by] { width:150px; }
.cssfw-bs3 .form-group select[name=order_direction] { width:40px; font-weight:bold; padding:0; font-size: 16px; }

.avatar_block { width: 50px !important; height: 50px !important; }
.avatar_block .avatar_image { width: 50px !important; height: 50px !important; }
.avatar_block .img-circle { margin-left: 0 !important; width: 50px !important; height: 50px !important; }

.avatar_block img { width: 50px !important; height: 50px !important; }

.form-horizontal .radio, .form-horizontal .checkbox { min-height: 10px; }

.icon-game-dota2 { background-position: -67px 2px; }
.icon-game-lol { background-position: -266px 2px; }
.icon-game-sc2 { background-position: -337px 2px; }
.icon-game-csgo { background-position: 1px 2px; }
.icon-game-wot { background-position: -468px 2px; }
.icon-game-wot.no-active { background-position: -494px 2px; }
.icon-game-hs { background-position: -200px 2px; }
.icon-game-dota2, .icon-game-lol, .icon-game-sc2, .icon-game-csgo, .icon-game-wot, .icon-game-hs {
	background-image: url("/images/ico-game-opac.png");
	background-repeat: no-repeat; overflow: hidden; height: 25px; width: 25px; zoom: 55%;
}
.table-very-condensed>thead>tr>th, .table-very-condensed>tbody>tr>th, .table-very-condensed>tfoot>tr>th, .table-very-condensed>thead>tr>td, .table-very-condensed>tbody>tr>td, .table-very-condensed>tfoot>tr>td {
    padding: 1px 5px;
    line-height: 1.3;
}
			'],
			'jquery' => [
				'var filter_timeout;
				$(".left_area form").on("change", function(e){
					var form = $(this)
					clearTimeout(filter_timeout);
					// do stuff when user has been idle for selected time
					filter_timeout = setTimeout(function() {
						console.log("filter submit")
						form.submit()
					}, 1000);
				})',
			],
		],
	],
	'require' => [
		'asset' => [
			'yf_bootstrap_fixes',
			'jq-select2',
			'css_oauth_icons',
			'yf_js_admin_ajax_user_info',
		],
	],
	'config' => [
		'no_cache' => true,
		'main_type' => 'admin',
	],
];
