<?php

return function() {

return ['versions' => ['master' => [
'js' => [
	tpl()->parse_string('
		$(document).on("click", ".add_service", function(){
			var add_element = \'
<div class="control-group form-group stacked-row form-group-id-row_services">
	<div class="controls input_form col-md-12">
		<span class="stacked-item">
			<input name="service_name[]" class="form-control name-service" placeholder="{t(servise)}" autocomplete="off" type="text">
		</span>
		<span class="stacked-item">
			<div class="input-group input-prepend input-append">
				<span class="add-on input-group-addon"><span class="price-prepend">$</span></span>
				<input name="service_price[]" class="form-control input-small price-service" placeholder="{t(Price)}" maxlength="8" autocomplete="off" min="1" step="1" type="number">
				<span class="add-on input-group-addon"><span class="price-append">{t(в минуту)}</span></span>
			</div>
		</span>
		<span class="stacked-item"><a href="#" class="del_service btn"><i class="fa fa-trash-o"></i></a></span>
	</div>
</div>
			\';
			$(this).parent().parent().parent().before(add_element);
			return false;
		});
		$(document).on("click", ".del_service", function(){
			var _this = $(this);
			_this.parent().parent().parent().remove();
			/*	_this.parent().parent().parent().parent().parent().remove();*/
			return false;
		});
	')],
]],
'config' => [
	'inline_cache' => true,
	// 'no_cache' => true,
	'main_type' => 'user',
],
];

};
