<?php
/*
Plugin Name: Menubar
Plugin URI: https://dontdream.it/menubar/
Description: Configurable menus with your choice of menu templates.
Version: 5.9.3
Author: Andrea Tarantini
Author URI: https://dontdream.it/
Text Domain: menubar
*/

if (!defined ('MENUBAR_TEMPLATES'))
	if (file_exists (WP_PLUGIN_DIR. '/menubar-templates'))
		define ('MENUBAR_TEMPLATES', 'menubar-templates');
	else
		define ('MENUBAR_TEMPLATES', 'menubar/templates');

global $wpm_options;
$wpm_options = new stdClass;
$wpm_options->admin_name	= 'Menubar';
$wpm_options->menubar		= 'menubar';
$wpm_options->templates		= MENUBAR_TEMPLATES;
$wpm_options->menubar_url	= plugins_url ($wpm_options->menubar);
$wpm_options->templates_url	= plugins_url ($wpm_options->templates);
$wpm_options->templates_dir	= WP_PLUGIN_DIR. '/'. $wpm_options->templates;
$wpm_options->admin_file	= 'menubar/wpm-admin.php';
$wpm_options->form_action	= admin_url ('themes.php?page='. $wpm_options->admin_file);
$wpm_options->php_file    	= 'wpm3.php';
$wpm_options->table_name  	= 'menubar3';
$wpm_options->option_name  	= 'menubar';
$wpm_options->update_option	= true;
$wpm_options->function_name	= 'wpm_display_';
$wpm_options->menu_type   	= 'Menu';
$wpm_options->wpm_version 	= '5.9.3';

include_once ('wpm-db.php');
include_once ('wpm-menu.php');
include_once ('wpm-tree.php');

add_action ('init', 'wpm_translate');
function wpm_translate ()
{
	load_plugin_textdomain ('menubar');
}

add_action ('admin_notices', 'wpm_5_5');
function wpm_5_5 ()
{
	if (is_plugin_active ('enable-jquery-migrate-helper/enable-jquery-migrate-helper.php'))
	{
?>
	<div class="notice notice-info is-dismissible">
		<p><?php _e('Notice: <em>Menubar</em> no longer requires the <em>Enable jQuery Migrate Helper</em> plugin.', 'menubar'); ?></p>
	</div>
<?php
	}
}
	
add_action ('admin_menu', 'wpm_add_pages');
function wpm_add_pages ()
{
	global $wpm_options;

	$page = add_submenu_page ('themes.php', __('Manage Menubar', 'menubar'),
		$wpm_options->admin_name, 'manage_options', $wpm_options->admin_file);

	return true;
}

add_filter ('plugin_action_links_'. plugin_basename (__FILE__), 'wpm_row_meta', 10, 2);
function wpm_row_meta ($links, $file)
{
	global $wpm_options;

	$url = admin_url ('themes.php');
	$settings_link = '<a href="'. add_query_arg (array ('page' => $wpm_options->admin_file), $url). '">'. __('Settings', 'menubar'). '</a>';
	array_unshift ($links, $settings_link);

	return $links;
}

add_action ('wp_head', 'wpm_css', 10, 2);
function wpm_css ($template='', $css='')
{
	global $wpm_options;

	$rows = wpm_get_templates ();
	
	echo "\n<!-- WP Menubar $wpm_options->wpm_version: start CSS -->\n"; 
//	echo '<meta http-equiv="X-UA-Compatible" content="IE=9; IE=8; IE=7; IE=EDGE" />';

	if ($template) 
		wpm_include ($template, $css);
		
	foreach ($rows as $row)
		wpm_include ($row->selection, $row->cssclass);
		
	echo "<!-- WP Menubar $wpm_options->wpm_version: end CSS -->\n"; 

	return true;
}

function wpm_include ($template, $css)
{
	global $wpm_options;

	$url = $wpm_options->templates_url;
	$root = $wpm_options->templates_dir;

	if (!file_exists ("$root"))
		echo "<br /><b>WP Menubar error</b>:  Folder $root not found!<br />\n<br />Please create that folder and install at least one Menubar template.<br />\n";
	elseif (!file_exists ("$root/$template"))
		echo "<br /><b>WP Menubar error</b>:  Folder $root/$template not found!<br />\n";
	elseif ($template && !file_exists ("$root/$template/$wpm_options->php_file"))
		echo "<br /><b>WP Menubar error</b>:  File $wpm_options->php_file not found in $root/$template!<br />\n";
	elseif ($css && !file_exists ("$root/$template/$css"))
		echo "<br /><b>WP Menubar error</b>:  File $css not found in $root/$template!<br />\n";
	else
	{
		if ($css)
			echo '<link rel="stylesheet" href="'. "$url/$template/$css". '" type="text/css" media="screen" />'. "\n";
		return true;
	}

	return false;
}

add_action ('menubar', 'wpm_display', 10, 3);
add_action ('wp_menubar', 'wpm_display', 10, 3);
function wpm_display ($menuname, $template='', $css='')
{
	global $wpm_options;

	$menu = wpm_get_menu ($menuname);

	if ($template == '' && isset ($menu->selection)) $template = $menu->selection;
	if ($css == '' && isset ($menu->cssclass)) $css = $menu->cssclass;

	$version = $wpm_options->wpm_version;
	$function = $wpm_options->function_name. $template;
	$root = $wpm_options->templates_dir;
	if ($template)
		include_once "$root/$template/$wpm_options->php_file";

	if ($menu == '')
		echo "<br /><b>WP Menubar error</b>:  Menu '$menuname' not found! Please create a menu named '$menuname' and try again.<br />\n";
	elseif ($template == '') 
		echo "<br /><b>WP Menubar error</b>:  No template selected for menu $menuname!<br />\n";
	elseif (!function_exists ($function))
		echo "<br /><b>WP Menubar error</b>:  Function $function() not found!<br />\n";
	else
	{
		echo "<!-- WP Menubar $version: start menu $menuname, template $template, CSS $css -->\n";
		$function ($menu, $css);
		echo "<!-- WP Menubar $version: end menu $menuname, template $template, CSS $css -->\n";
		return true;
	}

	return false;
}

function wpm_is_descendant ($ancestor)
{
	global $wpdb;
	global $wp_query;

	if (!$wp_query->is_page)  return false;
	if (!$ancestor)  return true;
	
	$page_obj = $wp_query->get_queried_object ();
	$page = $page_obj->ID;
	if ($page == $ancestor)  return true;

	while (1)
	{
		$sql = "SELECT * FROM $wpdb->posts WHERE ID = $page";
		$post = $wpdb->get_row ($sql);

		$parent = $post->post_parent;
		if ($parent == 0)  return false;
		if ($parent == $ancestor)  return true;

		$page = $parent;
	}
}

add_shortcode ('menubar', 'wpm_shortcode');
function wpm_shortcode ($attr, $content)
{
	ob_start ();

	if (isset ($attr['menu']))
		do_action ('menubar', $attr['menu']);

	return ob_get_clean ();
}

add_action ('widgets_init', 'wpm_widget_init');
function wpm_widget_init ()
{
	register_widget ('wpm_widget');
}

class wpm_widget extends WP_Widget
{
	function __construct ()
	{
		$widget_ops = array ('description' => __('Select a menu to display', 'menubar'));
		parent::__construct ('menubar', 'Menubar', $widget_ops);
	}

	function widget ($args, $instance)
	{
		$menu = wpm_valid_menu_name ($instance['menu']);
		if (!empty ($menu))
		{
			$title = apply_filters ('widget_title', $instance['title']);

			echo $args['before_widget'];
			if ($title)
				echo $args['before_title']. $title. $args['after_title'];
			do_action ('menubar', $menu);
			echo $args['after_widget'];
		}
	}

	function update ($new_instance, $old_instance)
	{
		$instance['title'] = isset ($new_instance['title'])? $new_instance['title']: '';
		$instance['menu'] = isset ($new_instance['menu'])? $new_instance['menu']: '';
		return $instance;
	}

	function form ($instance)
	{
		$title = isset ($instance['title'])? $instance['title']: '';
		$menu = isset ($instance['menu'])? $instance['menu']: '';

		$posts = wpm_get_menus ();
?>
		<?php if (empty ($posts)) : ?>
			<p>
				<?php _e('You need to create a Menubar menu first.', 'menubar'); ?>
			</p>
		<?php else : ?>
			<p>
				<label for="<?php echo $this->get_field_id ('title'); ?>"><?php _e('Title:', 'menubar'); ?></label>
				<input type="text" class="widefat" id="<?php echo $this->get_field_id ('title'); ?>" name="<?php echo $this->get_field_name ('title'); ?>" value="<?php echo esc_attr ($title); ?>" />
			</p>
			<p>
				<label for="<?php echo $this->get_field_id ('menu'); ?>"><?php _e('Menu:', 'menubar'); ?></label>
				<select class='widefat' id='<?php echo $this->get_field_id ('menu'); ?>' name='<?php echo $this->get_field_name ('menu'); ?>'>
					<?php foreach ($posts as $post) : ?>
						<option value='<?php echo esc_attr ($post->name); ?>' <?php selected ($post->name, $menu); ?>><?php echo esc_html ($post->name); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
		<?php endif; ?>
<?php
	}
}

add_action ('wp_ajax_menubar', 'wpm_ajax');
function wpm_ajax ()
{
	$command = $_POST['command'];
	$type = $_POST['type'];
	
	switch ($command)
	{
	case 'typeargs':
		include_once ('wpm-edit.php');
		wpm_typeargs ($type);
		break;
		
	default:
		echo "-- bad ajax command received --";
		break;
	}
	
	exit;
}

add_action ('init', 'wpm_init');
function wpm_init ()
{
	wpm_init_tree ();
}

function wpm_empty_item ()
{
	$item = new stdClass;
	$item->type = null;
	$item->id = null;
	$item->name = null;
	$item->imageurl = null;
	$item->cssclass = null;
	$item->attributes = null;
	$item->selection = null;
	$item->depth = null;
	$item->exclude = null;
	$item->headings = null;

	return $item;
}
