<?php
/**
 * Inline icon set — 24×24, stroke-based, drawn with `currentColor`.
 *
 * Kept in the theme rather than pulled from an icon font or sprite so the pages
 * ship no extra request and every glyph shares the same weight and corner
 * treatment. Icons are decorative by default: they carry `aria-hidden` and the
 * meaning lives in the adjacent text.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

/**
 * The icon path registry.
 *
 * @return array<string,string> Map of icon name to inner SVG markup.
 */
function zandi_icon_paths() {
	static $paths = null;

	if ( null !== $paths ) {
		return $paths;
	}

	$paths = array(
		'users'       => '<path d="M16 19v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 17.5V19"/><circle cx="10" cy="8" r="3.25"/><path d="M20 19v-1.5a3.5 3.5 0 0 0-2.6-3.38M15.5 5.2a3.25 3.25 0 0 1 0 5.6"/>',
		'graduation'  => '<path d="M12 4 3 8.5l9 4.5 9-4.5L12 4Z"/><path d="M6.5 10.8v4.4c0 1.5 2.46 2.8 5.5 2.8s5.5-1.3 5.5-2.8v-4.4"/><path d="M21 8.5v5"/>',
		'calendar'    => '<rect x="3.5" y="5" width="17" height="15.5" rx="3"/><path d="M3.5 9.75h17M8.25 3.5v3M15.75 3.5v3"/>',
		'heart'       => '<path d="M12 20s-7.5-4.35-7.5-9.25A4.25 4.25 0 0 1 12 8a4.25 4.25 0 0 1 7.5 2.75C19.5 15.65 12 20 12 20Z"/>',
		'sparkles'    => '<path d="M12 3.5 13.6 8.4 18.5 10l-4.9 1.6L12 16.5l-1.6-4.9L5.5 10l4.9-1.6L12 3.5Z"/><path d="M18.5 15.5l.7 2 2 .7-2 .7-.7 2-.7-2-2-.7 2-.7.7-2Z"/>',
		'chat'        => '<path d="M20.5 12.2c0 4-3.8 7.2-8.5 7.2a9.9 9.9 0 0 1-2.6-.34L4.5 20.5l1.2-3.4A6.9 6.9 0 0 1 3.5 12.2C3.5 8.2 7.3 5 12 5s8.5 3.2 8.5 7.2Z"/><path d="M9 11.5h.01M12 11.5h.01M15 11.5h.01"/>',
		'lifebuoy'    => '<circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="3.5"/><path d="m6 6 3.5 3.5M18 6l-3.5 3.5M6 18l3.5-3.5M18 18l-3.5-3.5"/>',
		'route'       => '<circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/><path d="M9 6.5h4.5A3.5 3.5 0 0 1 17 10v0a3.5 3.5 0 0 1-3.5 3.5h-3A3.5 3.5 0 0 0 7 17v.5"/>',
		'clipboard'   => '<path d="M9 4.5h6a1.5 1.5 0 0 1 1.5 1.5v.5h1A2 2 0 0 1 19.5 8.5v10a2 2 0 0 1-2 2h-11a2 2 0 0 1-2-2v-10a2 2 0 0 1 2-2h1V6A1.5 1.5 0 0 1 9 4.5Z"/><path d="M9 12.5h6M9 16h4"/>',
		'target'      => '<circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="4.5"/><circle cx="12" cy="12" r="1" fill="currentColor" stroke="none"/>',
		'play'        => '<path d="M9 7.5 17 12l-8 4.5v-9Z"/>',
		'repeat'      => '<path d="M4.5 10.5A5 5 0 0 1 9.5 5.5h9"/><path d="m16 3 2.75 2.5L16 8"/><path d="M19.5 13.5a5 5 0 0 1-5 5h-9"/><path d="m8 16-2.75 2.5L8 21"/>',
		'trending'    => '<path d="m4 16.5 5-5 3.5 3.5L20 7"/><path d="M15.5 7H20v4.5"/>',
		'clock'       => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 1.75"/>',
		'layers'      => '<path d="m12 3.5 8 4-8 4-8-4 8-4Z"/><path d="m4 12 8 4 8-4M4 16.5l8 4 8-4"/>',
		'chevronDown' => '<path d="m6.5 9.5 5.5 5 5.5-5"/>',
		'chevronLeft' => '<path d="m14 6.5-5 5.5 5 5.5"/>',
		'chevronRight' => '<path d="m10 6.5 5 5.5-5 5.5"/>',
		'arrowLeft'   => '<path d="M19 12H5"/><path d="m10.5 6.5-5.5 5.5 5.5 5.5"/>',
		'arrowRight'  => '<path d="M5 12h14"/><path d="m13.5 6.5 5.5 5.5-5.5 5.5"/>',
		'check'       => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
		'menu'        => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'close'       => '<path d="m6 6 12 12M18 6 6 18"/>',
		'phone'       => '<path d="M6.2 4.5h2.4l1.3 3.3-1.7 1.3a10.5 10.5 0 0 0 4.7 4.7l1.3-1.7 3.3 1.3v2.4a2 2 0 0 1-2.2 2A14.5 14.5 0 0 1 4.2 6.7a2 2 0 0 1 2-2.2Z"/>',
		'mail'        => '<rect x="3.5" y="5.5" width="17" height="13" rx="3"/><path d="m4.5 8 6.6 4.4a1.6 1.6 0 0 0 1.8 0L19.5 8"/>',
		'pin'         => '<path d="M12 21s6-5.2 6-9.5a6 6 0 1 0-12 0C6 15.8 12 21 12 21Z"/><circle cx="12" cy="11.2" r="2.3"/>',
		'instagram'   => '<rect x="4" y="4" width="16" height="16" rx="4.5"/><circle cx="12" cy="12" r="3.4"/><path d="M16.6 7.4h.01"/>',
		'telegram'    => '<path d="M20.5 5 3.8 11.4c-.8.3-.8 1.4.02 1.66l4.2 1.3 1.6 4.6c.24.7 1.14.86 1.6.3l2.2-2.6 4.2 3.1c.6.44 1.45.1 1.6-.62L21.6 6a1 1 0 0 0-1.1-1ZM8.4 14l9.5-6.2-7.6 7.3-.2 3.1"/>',
		'whatsapp'    => '<path d="M20 11.7a8 8 0 0 1-11.9 7L4 20l1.4-4a8 8 0 1 1 14.6-4.3Z"/><path d="M9.3 9c.3-.7.6-.7.9-.7h.6l.8 2-.9.8a6 6 0 0 0 2.6 2.6l.8-.9 2 .8v.6c0 .3 0 .6-.7.9-1 .4-2.6-.2-4-1.4s-2.5-2.9-2.1-4.7Z"/>',
		'linkedin'    => '<rect x="4" y="4" width="16" height="16" rx="4"/><path d="M8.2 10.6v5.6M8.2 8.2h.01M12 16.2v-5.6M12 12.6c0-1.1.9-2 2-2s2 .9 2 2v3.6"/>',
		'youtube'     => '<rect x="3" y="6" width="18" height="12" rx="4"/><path d="m10.5 9.8 4.2 2.2-4.2 2.2V9.8Z"/>',
		'globe'       => '<circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17M12 3.5c2.2 2.3 3.4 5.3 3.4 8.5s-1.2 6.2-3.4 8.5c-2.2-2.3-3.4-5.3-3.4-8.5S9.8 5.8 12 3.5Z"/>',
		'user'        => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20v-1a5 5 0 0 1 5-5h4a5 5 0 0 1 5 5v1"/>',
		'tag'         => '<path d="M4 12.5V5.5a1.5 1.5 0 0 1 1.5-1.5h7a2 2 0 0 1 1.4.6l6 6a2 2 0 0 1 0 2.8l-5.6 5.6a2 2 0 0 1-2.8 0l-6-6A2 2 0 0 1 4 12.5Z"/><path d="M8.5 8.5h.01"/>',
	);

	/**
	 * Filters the icon registry so a child theme can add or replace glyphs.
	 *
	 * @param array<string,string> $paths Icon name to inner SVG markup.
	 */
	$paths = apply_filters( 'zandi_icon_paths', $paths );

	return $paths;
}

/**
 * Returns an inline SVG icon.
 *
 * @param string $name  Icon name from the registry.
 * @param array  $args  Optional. {
 *     @type string $class       Extra class names for the <svg>.
 *     @type float  $stroke      Stroke width. Default 1.6.
 *     @type string $fill        Fill colour. Default 'none'.
 *     @type string $label       Accessible label. When set the icon is exposed
 *                               as an image instead of being hidden.
 * }
 * @return string Escaped SVG markup, or an empty string for an unknown name.
 */
function zandi_get_icon( $name, $args = array() ) {
	$paths = zandi_icon_paths();

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	$args = wp_parse_args(
		$args,
		array(
			'class'  => '',
			'stroke' => 1.6,
			'fill'   => 'none',
			'label'  => '',
		)
	);

	$accessibility = $args['label']
		? sprintf( ' role="img" aria-label="%s"', esc_attr( $args['label'] ) )
		: ' aria-hidden="true" focusable="false"';

	return sprintf(
		'<svg viewBox="0 0 24 24" fill="%1$s" stroke="currentColor" stroke-width="%2$s" stroke-linecap="round" stroke-linejoin="round" class="%3$s"%4$s>%5$s</svg>',
		esc_attr( $args['fill'] ),
		esc_attr( (string) $args['stroke'] ),
		esc_attr( $args['class'] ),
		$accessibility,
		$paths[ $name ]
	);
}

/**
 * Echoes an inline SVG icon.
 *
 * @param string $name Icon name.
 * @param array  $args Icon arguments. See zandi_get_icon().
 * @return void
 */
function zandi_icon( $name, $args = array() ) {
	echo zandi_get_icon( $name, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup assembled from a fixed registry and escaped above.
}

/**
 * The direction-aware "forward" arrow.
 *
 * Reading forward means leftwards in Persian and rightwards in a Latin locale,
 * so the glyph follows the document direction rather than being hard-coded.
 *
 * @return string Icon name.
 */
function zandi_arrow_forward() {
	return zandi_is_rtl() ? 'arrowLeft' : 'arrowRight';
}

/**
 * The direction-aware "back" arrow.
 *
 * @return string Icon name.
 */
function zandi_arrow_back() {
	return zandi_is_rtl() ? 'arrowRight' : 'arrowLeft';
}

/**
 * The direction-aware "forward" chevron, for breadcrumb separators.
 *
 * A literal › cannot be used: it is a mirrored character, so the bidi algorithm
 * flips it inside a Persian run and the trail appears to point backwards.
 *
 * @return string Icon name.
 */
function zandi_chevron_forward() {
	return zandi_is_rtl() ? 'chevronLeft' : 'chevronRight';
}
