<?php
/*************************************************************************************\
|* Links obuscation - add class "obfuscate" to any <a> element to obfuscate its link *|
\*************************************************************************************/

// Add this code to your child theme's functions.php file, then just add the class "obfuscate" to any <a> element to obfuscate its link by replacing it with a <span> element with no readable link.
// The obfuscated elements inherits the original <a> element's classes, along with a "akn-obf-link" class, so you might need to add CSS to style the "akn-obf-link" class so that it looks like a link to the visitor, maybe at least to add a cursor:pointer.
// On right click, the obfuscated link will be wrapped with a proper <a> element with the "akn-deobf-link" for a brief moment, so that a proper context menu appears, you can remove that behaviour by setting the "deobfucate_on_right_click" option to false in the code bellow.

// Edit 2022-04-05 - modified regex to allow for html elements and new lines into the <a> element, modified callback so the obfuscated element inherits the original link's classes, modified JS to add mousewheel click and right click options.

add_action('wp_loaded', 'buffer_start');
function buffer_start()
{
	ob_start('akn_ofbuscate_buffer');
}
add_action('shutdown', 'buffer_end');
function buffer_end()
{
	ob_end_flush();
}
function akn_ofbuscate_buffer($buffer)
{
	$result = preg_replace_callback('#<a[^>]+(href=(\"|\')([^\"\']*)(\'|\")[^>]+class=(\"|\')[^\'\"]*obfuscate[^\'\"]*(\"|\')|class=(\"|\')[^\'\"]*obfuscate[^\'\"]*(\"|\')[^>]+href=(\"|\')([^\"\']*)(\'|\"))[^>]*>(.+(?!<a))<\/a>#imUs', function ($matches) {
		preg_match('#<a[^>]+class=[\"|\\\']([^\\\'\"]+)[\"|\\\']#imUs', $matches[0], $matches_classes);
		$classes = trim(preg_replace('/\s+/', ' ', str_replace('obfuscate', '', $matches_classes[1])));
		return '<span class="akn-obf-link' . ($classes ? ' ' . $classes : '') . '" data-o="' . base64_encode($matches[3] ?: $matches[10]) . '" data-b="' . ((strpos(strtolower($matches[0]), '_blank') !== false) ? '1' : '0') . '">' . $matches[12] . '</span>';
	}, $buffer);
	return $result;
}


add_action('wp_footer', 'akn_ofbuscate_footer_js');
function akn_ofbuscate_footer_js()
{
?>
	<script>
		jQuery(document).ready(function($) {
			// options you can change
			var deobfuscate_on_right_click = true;
			// function to open link on click
			function akn_ofbuscate_clicked($el, force_blank) {
				if (typeof(force_blank) == 'undefined')
					var force_blank = false;
				var link = atob($el.data('o'));
				var _blank = $el.data('b');
				if (_blank || force_blank)
					window.open(link);
				else
					location.href = link;
			}
			// trigger link opening on click
			$(document).on('click', '.akn-obf-link', function() {
				var $el = $(this);
				if (!$el.closest('.akn-deobf-link').length)
					akn_ofbuscate_clicked($el);
			});
			// trigger link openin in new tab on mousewheel click
			$(document).on('mousedown', '.akn-obf-link', function(e) {
				if (e.which == 2) {
					var $el = $(this);
					if (!$el.closest('.akn-deobf-link').length) {
						akn_ofbuscate_clicked($el, true);
						return true;
					}
				}
			});
			// deobfuscate link on right click so the context menu is a legit menu with link options
			$(document).on('contextmenu', '.akn-obf-link', function(e) {
				if (deobfuscate_on_right_click) {
					var $el = $(this);
					if (!$el.closest('.akn-deobf-link').length) {
						e.stopPropagation();
						var link = atob($el.data('o'));
						var _blank = $el.data('b');
						$el.wrap('<a class="akn-deobf-link" href="' + link + '"' + (_blank ? ' target="_BLANK"' : '') + '></a>').parent().trigger('contextmenu');
						setTimeout(function() {
							$el.unwrap();
						}, 10);
					}
				}
			});
		});
	</script>
<?php
}
