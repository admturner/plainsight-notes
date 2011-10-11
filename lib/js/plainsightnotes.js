jQuery(document).ready(function($) {

	// Counting characters, based on setting on the wp-admin/options-general.php?page=ps_notes_settings page
	$('.maxlength').after("<span></span>").next().hide().end().keyup(function(e) {
		var textlimit = $("#psnscript-chars").text();
		var textlength = $(this).val().length;
		if (textlimit > 0) {
			if (textlength > textlimit) {
				$('#psnotesform span').css('color', 'red');
			} else {
				$('#psnotesform span').css('color', 'rgb(106,106,106)');
			}
			$('.remaining-char');
			$(this).next().show().text(textlimit - textlength + ' left of');
		}
	});
	
	// Now, if option is selected, prevent submission unless content has been entered
	$('#psnotesform').submit(function() {
		var error = false;
		var textlimit = $("#psnscript-chars").text();
		
		if (textlimit > 0) {
			$(this).find('.maxlength').each(function() {
				if ($('.maxlength').val().length == 0 || $('.maxlength').val().length >= textlimit) {
					$('#psnotesform').before('<div class="error"><p>Error. Either not enough, or too much text.</p></div>').prev().delay(2000).fadeOut(800);
					$(this).focus();
					error = true;
						return false; // Only exits the "each" loop
					}
				});
			if (error) {
				return false;
			}
			return true;
		}
	});
	
});