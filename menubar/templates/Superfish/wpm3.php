<?php
/* 
	WordPress Menubar Plugin
	PHP script for the Superfish template

	Credits:
	Superfish v1.7.10
	The jQuery menu plugin by Joel Birch
	https://superfish.joelbirch.design/
*/

global $wpm_html_ssf;

$wrap_ssf = '
<script type="text/javascript">
// <![CDATA[
jQuery(document).ready(function($) {
$("ul.%menuclass").superfish ({
    hoverClass:    "wpm-hover",        
    delay:         500,                
    animation:     {opacity:"show"}, 
    speed:         "slow",         
    autoArrows:    true,               
    dropShadows:   true,               
    disableHI:     true
});
}); 
// ]]>
</script>

<div class="%menuclass-wrap">
<ul class="%menuclass">%items</ul>
</div>
<div class="%menuclass-after"></div>
';

$default_ssf = '<li%class><a href="%url" %attr>%name</a>%list</li>';

$search_ssf = '
<li%class><form method="get" action="%home">
<input class="wpm-text" type="text" name="s" value="%name"
onblur="if (this.value == \'\') this.value = \'%name\';"
onfocus="if (this.value == \'%name\') this.value = \'\';" />
%submit
</form></li>
';

$wpm_html_ssf = array
(
'active' 	=> 'selected',
'nourl'		=> '#',
'list' 		=> '<ul>%items</ul>',
'submit' 	=> '<input class="wpm-submit" type="submit" value="" />',
'items'		=> array
	(
	'Menu'			=>  $wrap_ssf,
	'Home'			=>  $default_ssf,
	'FrontPage'		=>  $default_ssf,
	'Heading'		=>  '<li%class><a style="cursor:default;" %attr>%name</a>%list</li>',
	'Tag'			=>  $default_ssf,
	'TagList'		=>  '<li%class><a style="cursor:default;" %attr>%name</a>%list</li>',
	'Category'		=>  $default_ssf,
	'CategoryTree'	=>	'',
	'Page'			=>  $default_ssf,
	'PageTree'		=>	'',
	'Post'			=>  $default_ssf,
	'SearchBox'		=>  $search_ssf,
	'External'		=>  $default_ssf,
	'Custom'		=>  '',
	),
);

function wpm_display_Superfish ($menu, $css)
{
	global $wpm_html_ssf, $wpm_options;

	$url = $wpm_options->templates_url. '/Superfish/superfish.js';
	wp_enqueue_script ('superfish', $url, array ('jquery'));

	$r = wpm_out41 ($menu->id, $wpm_html_ssf, $css);
	echo $r['output'];
}
