<?php
/**
 * Plugin Name:       Curly Projects
 * Plugin URI:        https://github.com/Curly-Sprout-Creative/curly-projects
 * Description:       Custom "projects" post type with project service + project type taxonomies, a links repeater meta box, and [project_links] / [project_services] shortcodes for Oxygen 6 Post Loops. Pairs with the /projects/ archive.
 * Version:           1.0.0
 * Author:            Curly Sprout Creative
 * License:           GPL-2.0-or-later
 * Text Domain:       curly-projects
 *
 * @package CurlyProjects
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CURLY_PROJECTS_VERSION', '1.0.0' );
define( 'CURLY_PROJECTS_FILE', __FILE__ );
define( 'CURLY_PROJECTS_DIR', plugin_dir_path( __FILE__ ) );
define( 'CURLY_PROJECTS_URL', plugin_dir_url( __FILE__ ) );

/*
 * Load order: the CPT + taxonomies must exist before the meta box and the
 * shortcodes (which resolve terms) are used.
 */
require_once CURLY_PROJECTS_DIR . 'includes/cpt.php';
require_once CURLY_PROJECTS_DIR . 'includes/taxonomy.php';
require_once CURLY_PROJECTS_DIR . 'includes/meta.php';
require_once CURLY_PROJECTS_DIR . 'includes/shortcodes.php';

register_activation_hook( __FILE__, 'curly_projects_activate' );

/**
 * Seed default terms and refresh rewrite rules on activation.
 */
function curly_projects_activate() {
	curly_projects_register_cpt();
	curly_projects_register_taxonomies();
	curly_projects_seed_default_terms();
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

/**
 * Plugin update checker (GitHub Releases, public repo — no auth needed).
 */
require_once CURLY_PROJECTS_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';
if ( class_exists( '\YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
	$curly_projects_updater = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/Curly-Sprout-Creative/curly-projects/',
		__FILE__,
		'curly-projects'
	);
}
