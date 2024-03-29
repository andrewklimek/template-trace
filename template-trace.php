<?php
/*
Plugin Name: Template Trace
Description: Shows you what templates are being used to make up the current page (in an admin bar item).  Useful for understanding crazy themes.
Version: 1.0
Author: Andrew J Klimek
Author URI: https://github.com/andrewklimek/
License: GPL
*/

/* Save the loaded template to a global variable */
add_filter( 'template_include', 'ajk_template_trace_var_template_include', 1000 );
function ajk_template_trace_var_template_include( $t ) {
	$GLOBALS['ajk_current_template'] = $t;
	return $t;
}

add_action( 'wp_before_admin_bar_render', 'ajk_template_trace_admin_bar' );
function ajk_template_trace_admin_bar()
{
	if ( is_admin() || !is_admin_bar_showing() || !current_user_can('manage_options') ) return;

	global $wp_admin_bar, $ajk_current_template;

	$root_cutoff = strpos( $ajk_current_template, '/wp-content/' ) + 1;// for showing an abbreviated path. full path is on hover
	$cutoff = $root_cutoff + 18;// /wp-content/themes/

	$wp_admin_bar->add_menu([
		'parent' => false,
		'id' => 'template-trace',
		'title' => basename($ajk_current_template),
		'meta' => ['title' => $ajk_current_template]
	]);
	
	/* ITEM DROP DOWN: Get includes that are in the themes folder and that were called after the base template file */
	$included_templates = [];
	$reached_base = false;
	$i = 0;
	$included_files = get_included_files();
	foreach ( $included_files as $path )
	{
		if ( $path === $ajk_current_template ) $reached_base = true;
		if ( ! $reached_base ) continue;
		// ok we've hit the base template in the includes array, now start looking for stuff in the themes folder
		
		if ( strpos($path, '/themes/') ) $in_themes = true;
		elseif ( strpos($path, 'template') ) $in_themes = false;
		else continue;

		$wp_admin_bar->add_menu([
			'parent' => 'template-trace',
			'id' => 'template-trace-sub-' . ++$i,
			'title' => $in_themes ? substr($path, $cutoff) : substr($path, $root_cutoff),
			'meta' => ['title' => $path]
		]);
	}
}
