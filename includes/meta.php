<?php
/**
 * Project meta: the links repeater meta box + save handler, and REST
 * registration so the Oxygen 6 agent can read/write the fields.
 *
 * @package CurlyProjects
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'curly_projects_register_meta', 20 );

/**
 * Register the project meta keys for the REST API + Oxygen 6 dynamic data.
 */
function curly_projects_register_meta() {
	register_post_meta(
		'projects',
		'_project_url',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'esc_url_raw',
			'auth_callback'     => 'curly_projects_meta_auth',
		)
	);

	register_post_meta(
		'projects',
		'_project_links',
		array(
			'type'              => 'array',
			'single'            => true,
			'default'           => array(),
			'show_in_rest'      => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'label'  => array( 'type' => 'string' ),
							'url'    => array( 'type' => 'string' ),
							'target' => array( 'type' => 'string' ),
						),
					),
				),
			),
			'sanitize_callback' => 'curly_projects_sanitize_links',
			'auth_callback'     => 'curly_projects_meta_auth',
		)
	);
}

/**
 * REST auth callback for the project meta.
 *
 * @return bool
 */
function curly_projects_meta_auth() {
	return current_user_can( 'edit_posts' );
}

/**
 * Sanitize a links array: drop rows without a URL, clean each field.
 *
 * @param mixed $value Raw value.
 * @return array
 */
function curly_projects_sanitize_links( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$clean = array();
	foreach ( $value as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$url = isset( $row['url'] ) ? esc_url_raw( trim( (string) $row['url'] ) ) : '';
		if ( '' === $url ) {
			continue;
		}

		$target = ( isset( $row['target'] ) && '_self' === $row['target'] ) ? '_self' : '_blank';

		$clean[] = array(
			'label'  => isset( $row['label'] ) ? sanitize_text_field( $row['label'] ) : '',
			'url'    => $url,
			'target' => $target,
		);
	}

	return $clean;
}

add_action( 'add_meta_boxes', 'curly_projects_add_meta_boxes' );

/**
 * Register the Project Links meta box.
 */
function curly_projects_add_meta_boxes() {
	add_meta_box(
		'curly_projects_links',
		'Project Links',
		'curly_projects_render_links_meta_box',
		'projects',
		'normal',
		'default'
	);
}

/**
 * Render the links repeater + optional primary URL.
 *
 * @param WP_Post $post Current post.
 */
function curly_projects_render_links_meta_box( $post ) {
	wp_nonce_field( 'curly_projects_save_meta', 'curly_projects_nonce' );

	$primary = get_post_meta( $post->ID, '_project_url', true );
	$links   = get_post_meta( $post->ID, '_project_links', true );
	if ( ! is_array( $links ) ) {
		$links = array();
	}
	if ( empty( $links ) ) {
		$links = array(
			array(
				'label'  => '',
				'url'    => '',
				'target' => '_blank',
			),
		);
	}
	?>
	<p class="description">
		Add one row per link shown under the project card (e.g. <em>Visit website</em>,
		<em>Buyer's guide</em>). The optional primary URL is used only when no rows are filled in.
	</p>

	<div id="curly-projects-links-rows">
		<?php foreach ( $links as $i => $link ) : ?>
			<div class="curly-project-link-row">
				<input type="text" data-name="label" name="project_links[<?php echo (int) $i; ?>][label]"
					value="<?php echo esc_attr( isset( $link['label'] ) ? $link['label'] : '' ); ?>"
					placeholder="Label" class="curly-project-input-label" />
				<input type="url" data-name="url" name="project_links[<?php echo (int) $i; ?>][url]"
					value="<?php echo esc_attr( isset( $link['url'] ) ? $link['url'] : '' ); ?>"
					placeholder="https://" class="curly-project-input-url" />
				<label class="curly-project-target">
					<input type="checkbox" data-name="target" name="project_links[<?php echo (int) $i; ?>][target]"
						value="_blank" <?php checked( ! isset( $link['target'] ) || '_blank' === $link['target'] ); ?> />
					New tab
				</label>
				<button type="button" class="button-link curly-project-remove-link">Remove</button>
			</div>
		<?php endforeach; ?>
	</div>

	<p><button type="button" class="button" id="curly-projects-add-link">+ Add link</button></p>

	<template id="curly-projects-link-template">
		<div class="curly-project-link-row">
			<input type="text" data-name="label" placeholder="Label" class="curly-project-input-label" />
			<input type="url" data-name="url" placeholder="https://" class="curly-project-input-url" />
			<label class="curly-project-target">
				<input type="checkbox" data-name="target" value="_blank" checked />
				New tab
			</label>
			<button type="button" class="button-link curly-project-remove-link">Remove</button>
		</div>
	</template>

	<hr />

	<p>
		<label for="curly_project_url"><strong>Primary URL (optional)</strong></label><br />
		<input type="url" id="curly_project_url" name="project_url" class="widefat"
			value="<?php echo esc_attr( $primary ); ?>" placeholder="https://" />
	</p>
	<?php
}

add_action( 'save_post_projects', 'curly_projects_save_meta' );

/**
 * Persist the project meta on save.
 *
 * @param int $post_id Post ID.
 */
function curly_projects_save_meta( $post_id ) {
	if ( ! isset( $_POST['curly_projects_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['curly_projects_nonce'] ) ), 'curly_projects_save_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['project_url'] ) ) {
		update_post_meta( $post_id, '_project_url', esc_url_raw( wp_unslash( $_POST['project_url'] ) ) );
	}

	$rows = array();
	if ( isset( $_POST['project_links'] ) && is_array( $_POST['project_links'] ) ) {
		foreach ( wp_unslash( $_POST['project_links'] ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$rows[] = array(
				'label'  => isset( $row['label'] ) ? $row['label'] : '',
				'url'    => isset( $row['url'] ) ? $row['url'] : '',
				'target' => ( isset( $row['target'] ) && '_blank' === $row['target'] ) ? '_blank' : '_self',
			);
		}
	}

	update_post_meta( $post_id, '_project_links', curly_projects_sanitize_links( $rows ) );
}

add_action( 'admin_enqueue_scripts', 'curly_projects_admin_assets' );

/**
 * Enqueue the repeater UI assets on the project edit screen only.
 *
 * @param string $hook_suffix Current admin page.
 */
function curly_projects_admin_assets( $hook_suffix ) {
	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'projects' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_style( 'curly-projects-admin', CURLY_PROJECTS_URL . 'assets/css/admin.css', array(), CURLY_PROJECTS_VERSION );
	wp_enqueue_script( 'curly-projects-admin', CURLY_PROJECTS_URL . 'assets/js/admin.js', array(), CURLY_PROJECTS_VERSION, true );
}
