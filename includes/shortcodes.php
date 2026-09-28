<?php
/**
 * Curly Projects shortcodes: [project_links] and [project_services].
 *
 * Designed for use inside an Oxygen 6 Post Loop item (they default to the
 * current loop post), but they work anywhere a post id is available.
 *
 * @package CurlyProjects
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'project_links', 'curly_projects_links_shortcode' );
add_shortcode( 'project_services', 'curly_projects_services_shortcode' );

/**
 * Resolve the post id a shortcode should read from.
 *
 * @param array $atts Shortcode attributes.
 * @return int
 */
function curly_projects_shortcode_post_id( $atts ) {
	if ( ! empty( $atts['id'] ) ) {
		return (int) $atts['id'];
	}

	$current = get_the_ID();
	return $current ? (int) $current : 0;
}

/**
 * Return a project's links, falling back to the primary URL.
 *
 * @param int  $post_id       Post id.
 * @param bool $with_primary  Include `_project_url` when the repeater is empty.
 * @return array[] Each item: label, url, target.
 */
function curly_projects_get_links( $post_id, $with_primary = true ) {
	$links = get_post_meta( $post_id, '_project_links', true );

	// Normalize a JSON string (e.g. written by a CLI/import that stored the
	// raw string) into the array shape the shortcode renders.
	if ( is_string( $links ) && '' !== trim( $links ) ) {
		$decoded = json_decode( $links, true );
		$links   = is_array( $decoded ) ? $decoded : array();
	}

	if ( ! is_array( $links ) ) {
		$links = array();
	}

	if ( empty( $links ) && $with_primary ) {
		$primary = get_post_meta( $post_id, '_project_url', true );
		if ( $primary ) {
			$links = array(
				array(
					'label'  => '',
					'url'    => $primary,
					'target' => '_blank',
				),
			);
		}
	}

	return $links;
}

/**
 * [project_links] — render the links repeater as a row of mini links.
 *
 * Attributes:
 *   id             Post id (defaults to the current post).
 *   class          Container class. Default `mini-link-row`.
 *   link_class     Anchor class. Default `mini-link hover-slide-up`.
 *   fallback_label Label used when a row has no label. Default `Visit website`.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function curly_projects_links_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'id'             => 0,
			'class'          => 'mini-link-row',
			'link_class'     => 'mini-link hover-slide-up',
			'fallback_label' => 'Visit website',
		),
		$atts,
		'project_links'
	);

	$post_id = curly_projects_shortcode_post_id( $atts );
	if ( ! $post_id ) {
		return '';
	}

	$links = curly_projects_get_links( $post_id );
	if ( empty( $links ) ) {
		return '';
	}

	$out = '';
	foreach ( $links as $link ) {
		$url = isset( $link['url'] ) ? $link['url'] : '';
		if ( '' === $url ) {
			continue;
		}

		$label = isset( $link['label'] ) && '' !== trim( (string) $link['label'] )
			? $link['label']
			: $atts['fallback_label'];

		$target = ( isset( $link['target'] ) && '_self' === $link['target'] ) ? '_self' : '_blank';
		$rel    = '_blank' === $target ? ' rel="noopener"' : '';

		$out .= sprintf(
			'<a class="%1$s" href="%2$s" target="%3$s"%4$s>%5$s</a>',
			esc_attr( $atts['link_class'] ),
			esc_url( $url ),
			esc_attr( $target ),
			$rel,
			esc_html( $label )
		);
	}

	if ( '' === $out ) {
		return '';
	}

	return sprintf( '<div class="%s">%s</div>', esc_attr( $atts['class'] ), $out );
}

/**
 * [project_services] — render a project's terms, joined by a separator.
 *
 * Attributes:
 *   id        Post id (defaults to the current post).
 *   taxonomy  `project_service` (default) or `project_type`.
 *   sep       Separator between terms. Default ` • `.
 *   link      `1` to link each term to its (rewrite-disabled, so rarely used)
 *             term archive. Default `0`.
 *   class     Wrapper class.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function curly_projects_services_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'id'       => 0,
			'taxonomy' => 'project_service',
			'sep'      => ' • ',
			'link'     => 0,
			'class'    => 'project-services',
		),
		$atts,
		'project_services'
	);

	$post_id = curly_projects_shortcode_post_id( $atts );
	if ( ! $post_id ) {
		return '';
	}

	$taxonomy = in_array( $atts['taxonomy'], array( 'project_service', 'project_type' ), true )
		? $atts['taxonomy']
		: 'project_service';

	$terms = get_the_terms( $post_id, $taxonomy );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return '';
	}

	$names = array();
	foreach ( $terms as $term ) {
		if ( $atts['link'] ) {
			$names[] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( get_term_link( $term ) ),
				esc_html( $term->name )
			);
		} else {
			$names[] = esc_html( $term->name );
		}
	}

	return sprintf(
		'<span class="%s">%s</span>',
		esc_attr( $atts['class'] ),
		implode( esc_html( $atts['sep'] ), $names )
	);
}
