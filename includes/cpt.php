<?php
/**
 * Projects custom post type registration.
 *
 * @package CurlyProjects
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'curly_projects_register_cpt' );

/**
 * Register the "projects" post type.
 *
 * `page-attributes` is enabled so projects can be ordered by `menu_order`
 * (the Oxygen 6 loop sorts on it), and `has_archive` provides the /projects/
 * view-all route.
 */
function curly_projects_register_cpt() {
	$labels = array(
		'name'          => 'Projects',
		'singular_name' => 'Project',
		'menu_name'     => 'Projects',
		'add_new'       => 'Add New Project',
		'add_new_item'  => 'Add New Project',
		'edit_item'     => 'Edit Project',
		'new_item'      => 'New Project',
		'view_item'     => 'View Project',
		'all_items'     => 'All Projects',
		'search_items'  => 'Search Projects',
		'not_found'     => 'No projects found',
	);

	$args = array(
		'labels'        => $labels,
		'public'        => true,
		'has_archive'   => true,
		'rewrite'       => array(
			'slug'       => 'projects',
			'with_front' => false,
		),
		'menu_icon'     => 'dashicons-portfolio',
		'menu_position' => 21,
		'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
		'show_in_rest'  => true,
		'rest_base'     => 'projects',
	);

	register_post_type( 'projects', $args );
}
