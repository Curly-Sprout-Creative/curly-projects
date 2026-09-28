<?php
/**
 * Project taxonomies: project_service (what was done) and project_type
 * (website / email / branding), used for admin tagging and Oxygen 6 loops.
 *
 * @package CurlyProjects
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'curly_projects_register_taxonomies', 11 );

/**
 * Register both taxonomies. Priority 11 so the CPT (init 10) exists first.
 *
 * Rewrite is disabled: these are for admin tagging + loop queries, not
 * front-end term archives, which also avoids needing a rewrite flush on
 * every plugin update.
 */
function curly_projects_register_taxonomies() {
	register_taxonomy(
		'project_service',
		array( 'projects' ),
		array(
			'labels'            => array(
				'name'          => 'Services',
				'singular_name' => 'Service',
				'search_items'  => 'Search Services',
				'all_items'     => 'All Services',
				'edit_item'     => 'Edit Service',
				'update_item'   => 'Update Service',
				'add_new_item'  => 'Add New Service',
				'menu_name'     => 'Services',
			),
			'hierarchical'      => true,
			'public'            => true,
			'rewrite'           => false,
			'query_var'         => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
		)
	);

	register_taxonomy(
		'project_type',
		array( 'projects' ),
		array(
			'labels'            => array(
				'name'          => 'Project Types',
				'singular_name' => 'Project Type',
				'search_items'  => 'Search Project Types',
				'all_items'     => 'All Project Types',
				'edit_item'     => 'Edit Project Type',
				'update_item'   => 'Update Project Type',
				'add_new_item'  => 'Add New Project Type',
				'menu_name'     => 'Project Types',
			),
			'hierarchical'      => true,
			'public'            => true,
			'rewrite'           => false,
			'query_var'         => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
		)
	);
}

/**
 * Seed starter terms on activation (idempotent).
 *
 * These mirror the labels used on the current site's portfolio section:
 * the type taxonomy is Website / Email / Branding (so non-website work can
 * be looped in or filtered out), and the service list covers the existing
 * card labels.
 */
function curly_projects_seed_default_terms() {
	$types = array( 'Website', 'Email', 'Branding' );

	$services = array(
		'Graphic Design',
		'Copywriting',
		'Websites',
		'Branding',
		'Strategy',
		'Logo',
		'Graphics',
		'Content',
		'Photos',
	);

	foreach ( $types as $term ) {
		if ( ! term_exists( $term, 'project_type' ) ) {
			wp_insert_term( $term, 'project_type' );
		}
	}

	foreach ( $services as $term ) {
		if ( ! term_exists( $term, 'project_service' ) ) {
			wp_insert_term( $term, 'project_service' );
		}
	}
}
