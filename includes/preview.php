<?php
/**
 * Project scroll preview: the two-screen mockup shown on the portfolio grid.
 *
 * Each project stores its own desktop + mobile full-page screenshots plus the
 * card's background and device-frame colours, so the preview is editable from
 * the project edit screen without touching the builder. Renders through
 * [project_preview]; falls back to the featured image when no preview is set.
 *
 * @package CurlyProjects
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CURLY_PROJECTS_PREVIEW_META = array(
	'desktop'  => '_project_preview_desktop',
	'mobile'   => '_project_preview_mobile',
	'bg'       => '_project_preview_bg',
	'frame'    => '_project_preview_frame',
	'featured' => '_project_featured',
);

add_action( 'init', 'curly_projects_register_preview_meta', 21 );

/**
 * Register the preview meta keys for REST + dynamic data.
 */
function curly_projects_register_preview_meta() {
	foreach ( array( 'desktop', 'mobile' ) as $key ) {
		register_post_meta(
			'projects',
			CURLY_PROJECTS_PREVIEW_META[ $key ],
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => 'curly_projects_meta_auth',
			)
		);
	}

	foreach ( array( 'bg', 'frame' ) as $key ) {
		register_post_meta(
			'projects',
			CURLY_PROJECTS_PREVIEW_META[ $key ],
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_hex_color',
				'auth_callback'     => 'curly_projects_meta_auth',
			)
		);
	}

	register_post_meta(
		'projects',
		CURLY_PROJECTS_PREVIEW_META['featured'],
		array(
			'type'          => 'boolean',
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => 'curly_projects_meta_auth',
		)
	);
}

add_action( 'add_meta_boxes', 'curly_projects_add_preview_meta_box' );

/**
 * Register the Scroll Preview meta box.
 */
function curly_projects_add_preview_meta_box() {
	add_meta_box(
		'curly_projects_preview',
		'Scroll Preview',
		'curly_projects_render_preview_meta_box',
		'projects',
		'normal',
		'high'
	);
}

/**
 * Render the media pickers and colour inputs.
 *
 * @param WP_Post $post Current post.
 */
function curly_projects_render_preview_meta_box( $post ) {
	$desktop = (int) get_post_meta( $post->ID, CURLY_PROJECTS_PREVIEW_META['desktop'], true );
	$mobile  = (int) get_post_meta( $post->ID, CURLY_PROJECTS_PREVIEW_META['mobile'], true );
	$bg      = (string) get_post_meta( $post->ID, CURLY_PROJECTS_PREVIEW_META['bg'], true );
	$frame   = (string) get_post_meta( $post->ID, CURLY_PROJECTS_PREVIEW_META['frame'], true );
	$featured = (bool) get_post_meta( $post->ID, CURLY_PROJECTS_PREVIEW_META['featured'], true );

	$fields = array(
		array(
			'key'   => 'desktop',
			'label' => 'Desktop screenshot',
			'hint'  => 'Full-height capture of the site, 1440px wide. Shown in the browser frame.',
			'id'    => $desktop,
			'meta'  => CURLY_PROJECTS_PREVIEW_META['desktop'],
		),
		array(
			'key'   => 'mobile',
			'label' => 'Mobile screenshot',
			'hint'  => 'Full-height capture at 390px wide. Shown in the phone frame.',
			'id'    => $mobile,
			'meta'  => CURLY_PROJECTS_PREVIEW_META['mobile'],
		),
	);
	?>
	<p class="description">
		Both screenshots scroll inside the device frames as the card crosses the viewport,
		so use full-page captures rather than a single screen. Leave them empty to fall back
		to the featured image.
	</p>

	<div class="curly-preview-fields">
		<?php foreach ( $fields as $field ) : ?>
			<?php
			$url = $field['id'] ? wp_get_attachment_image_url( $field['id'], 'medium' ) : '';
			$name = $field['id'] ? get_the_title( $field['id'] ) : '';
			?>
			<div class="curly-preview-image" data-curly-preview-image>
				<span class="curly-preview-image__label"><?php echo esc_html( $field['label'] ); ?></span>
				<div class="curly-preview-image__thumb">
					<img src="<?php echo esc_url( $url ); ?>" alt="" <?php echo $url ? '' : 'hidden'; ?> />
				</div>
				<input type="hidden" name="<?php echo esc_attr( $field['meta'] ); ?>"
					value="<?php echo esc_attr( $field['id'] ? $field['id'] : '' ); ?>" data-curly-preview-input />
				<p class="curly-preview-image__actions">
					<button type="button" class="button" data-curly-preview-select>
						<?php echo $field['id'] ? 'Replace image' : 'Select image'; ?>
					</button>
					<button type="button" class="button-link" data-curly-preview-clear <?php echo $field['id'] ? '' : 'hidden'; ?>>Remove</button>
				</p>
				<span class="curly-preview-image__name"><?php echo esc_html( $name ); ?></span>
				<p class="description"><?php echo esc_html( $field['hint'] ); ?></p>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="curly-preview-colours">
		<?php
		$colours = array(
			array(
				'key'   => 'bg',
				'label' => 'Card background',
				'hint'  => 'Usually the site\'s dominant brand colour. Defaults to the theme mid colour.',
				'meta'  => CURLY_PROJECTS_PREVIEW_META['bg'],
				'value' => $bg,
			),
			array(
				'key'   => 'frame',
				'label' => 'Device frame',
				'hint'  => 'Border colour of both screens. Defaults to the theme light colour.',
				'meta'  => CURLY_PROJECTS_PREVIEW_META['frame'],
				'value' => $frame,
			),
		);
		foreach ( $colours as $colour ) :
			$value = $colour['value'] ? $colour['value'] : '';
			?>
			<p class="curly-preview-colour">
				<label for="<?php echo esc_attr( $colour['meta'] ); ?>"><?php echo esc_html( $colour['label'] ); ?></label>
				<span class="curly-preview-colour__inputs">
					<input type="color" value="<?php echo esc_attr( $value ? $value : '#2a715e' ); ?>"
						data-curly-preview-swatch aria-label="<?php echo esc_attr( $colour['label'] ); ?>" />
					<input type="text" id="<?php echo esc_attr( $colour['meta'] ); ?>"
						name="<?php echo esc_attr( $colour['meta'] ); ?>"
						value="<?php echo esc_attr( $value ); ?>" placeholder="#2a715e"
						class="code" data-curly-preview-hex />
					<button type="button" class="button-link" data-curly-preview-reset>Clear</button>
				</span>
				<span class="description"><?php echo esc_html( $colour['hint'] ); ?></span>
			</p>
		<?php endforeach; ?>
	</div>

	<p class="curly-preview-featured">
		<label>
			<input type="checkbox" name="<?php echo esc_attr( CURLY_PROJECTS_PREVIEW_META['featured'] ); ?>"
				value="1" <?php checked( $featured ); ?> />
			<strong>Feature on the home page</strong>
		</label>
		<span class="description">
			The home page shows featured projects only; the Projects page lists every published
			project regardless of this setting.
		</span>
	</p>

	<p class="description">
		The preview renders wherever <code>[project_preview]</code> is placed — the project card
		component on the home page and the projects archive both use it.
	</p>
	<?php
}

add_action( 'save_post_projects', 'curly_projects_save_preview_meta' );

/**
 * Persist the preview fields.
 *
 * @param int $post_id Post ID.
 */
function curly_projects_save_preview_meta( $post_id ) {
	if ( ! isset( $_POST['curly_projects_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['curly_projects_nonce'] ) ), 'curly_projects_save_meta' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( array( 'desktop', 'mobile' ) as $key ) {
		$meta = CURLY_PROJECTS_PREVIEW_META[ $key ];
		if ( ! isset( $_POST[ $meta ] ) ) {
			continue;
		}
		$value = absint( wp_unslash( $_POST[ $meta ] ) );
		if ( $value ) {
			update_post_meta( $post_id, $meta, $value );
		} else {
			delete_post_meta( $post_id, $meta );
		}
	}

	foreach ( array( 'bg', 'frame' ) as $key ) {
		$meta = CURLY_PROJECTS_PREVIEW_META[ $key ];
		if ( ! isset( $_POST[ $meta ] ) ) {
			continue;
		}
		$value = sanitize_hex_color( wp_unslash( $_POST[ $meta ] ) );
		if ( $value ) {
			update_post_meta( $post_id, $meta, $value );
		} else {
			delete_post_meta( $post_id, $meta );
		}
	}

	// The checkbox is absent from $_POST when unchecked, so it is always written.
	$featured = CURLY_PROJECTS_PREVIEW_META['featured'];
	if ( ! empty( $_POST[ $featured ] ) ) {
		update_post_meta( $post_id, $featured, 1 );
	} else {
		delete_post_meta( $post_id, $featured );
	}
}

add_filter( 'manage_projects_posts_columns', 'curly_projects_featured_column' );

/**
 * Add a "Home page" column to the projects list.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function curly_projects_featured_column( $columns ) {
	$out = array();
	foreach ( $columns as $key => $label ) {
		$out[ $key ] = $label;
		if ( 'title' === $key ) {
			$out['project_featured'] = 'Home';
		}
	}
	return $out;
}

add_action( 'manage_projects_posts_custom_column', 'curly_projects_featured_column_value', 10, 2 );

/**
 * Render the featured marker.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post id.
 */
function curly_projects_featured_column_value( $column, $post_id ) {
	if ( 'project_featured' !== $column ) {
		return;
	}
	echo get_post_meta( $post_id, CURLY_PROJECTS_PREVIEW_META['featured'], true )
		? '<span aria-label="Featured" title="Featured on the home page">★</span>'
		: '<span style="color:#a7aaad" title="Not on the home page">—</span>';
}

add_action( 'admin_enqueue_scripts', 'curly_projects_preview_admin_assets' );

/**
 * Media modal + picker script on the project edit screen.
 *
 * @param string $hook_suffix Current admin page.
 */
function curly_projects_preview_admin_assets( $hook_suffix ) {
	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'projects' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script(
		'curly-projects-preview-admin',
		CURLY_PROJECTS_URL . 'assets/js/preview-admin.js',
		array(),
		CURLY_PROJECTS_VERSION,
		true
	);
}

add_shortcode( 'project_preview', 'curly_projects_preview_shortcode' );
/**
 * [project_preview] — the two-screen scrolling mockup.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function curly_projects_preview_shortcode( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'id'       => 0,
			'ratio'    => '',
			'featured' => 'yes',
		),
		$atts,
		'project_preview'
	);

	$post_id = (int) $atts['id'] ? (int) $atts['id'] : (int) get_the_ID();

	return curly_projects_render_preview( $post_id, $atts );
}

/**
 * Build the preview markup for a project.
 *
 * @param int   $post_id Project id.
 * @param array $atts    Shortcode attributes.
 * @return string
 */
function curly_projects_render_preview( $post_id, $atts = array() ) {
	if ( ! $post_id ) {
		return '';
	}

	$desktop = (int) get_post_meta( $post_id, CURLY_PROJECTS_PREVIEW_META['desktop'], true );
	$mobile  = (int) get_post_meta( $post_id, CURLY_PROJECTS_PREVIEW_META['mobile'], true );

	$desktop_url = $desktop ? wp_get_attachment_image_url( $desktop, 'full' ) : '';
	$mobile_url  = $mobile ? wp_get_attachment_image_url( $mobile, 'full' ) : '';

	// No screenshots yet: show the featured image so the card is never empty.
	if ( ! $desktop_url ) {
		$featured = (int) get_post_thumbnail_id( $post_id );
		$url      = $featured ? wp_get_attachment_image_url( $featured, 'large' ) : '';
		if ( ! $url ) {
			return '';
		}
		return sprintf(
			'<div class="csc-preview csc-preview--fallback"><img class="csc-preview__fallback" src="%1$s" alt="%2$s" loading="lazy" /></div>',
			esc_url( $url ),
			esc_attr( get_the_title( $post_id ) )
		);
	}

	$bg    = (string) get_post_meta( $post_id, CURLY_PROJECTS_PREVIEW_META['bg'], true );
	$frame = (string) get_post_meta( $post_id, CURLY_PROJECTS_PREVIEW_META['frame'], true );

	$style = '';
	if ( $bg ) {
		$style .= '--csc-preview-bg:' . $bg . ';';
	}
	if ( $frame ) {
		$style .= '--csc-preview-frame:' . $frame . ';';
	}
	if ( ! empty( $atts['ratio'] ) ) {
		$style .= '--csc-preview-ratio:' . $atts['ratio'] . ';';
	}

	$title = get_the_title( $post_id );

	$html  = '<div class="csc-preview"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . '>';
	$html .= '<div class="csc-preview__shot csc-preview__shot--desktop"><div class="csc-preview__pan">';
	$html .= sprintf(
		'<img src="%1$s" alt="%2$s" loading="lazy" decoding="async" />',
		esc_url( $desktop_url ),
		esc_attr( sprintf( '%s — desktop home page', $title ) )
	);
	$html .= '</div></div>';

	if ( $mobile_url ) {
		$html .= '<div class="csc-preview__shot csc-preview__shot--phone"><div class="csc-preview__pan">';
		$html .= sprintf(
			'<img src="%1$s" alt="%2$s" loading="lazy" decoding="async" />',
			esc_url( $mobile_url ),
			esc_attr( sprintf( '%s — mobile home page', $title ) )
		);
		$html .= '</div></div>';
	}

	$html .= '</div>';

	return $html;
}
