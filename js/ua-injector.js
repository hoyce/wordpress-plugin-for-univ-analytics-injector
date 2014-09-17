jQuery(document).ready(function ($) {
  $('.cBox').change(function(event) {
	$(event.target).closest('.uaOption').find('.label, .categoryText').toggleClass('disabled');
	var input = $(event.target).closest('.uaOption').find('input:text');
	$(input).prop("disabled",!$(input).prop("disabled"));
	$(input).toggleClass('disabled');
  });
});
