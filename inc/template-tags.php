<?php
/**
 * Reusable output helpers — the PHP half of the design system.
 *
 * Templates compose these rather than repeating markup, which is what keeps the
 * button, badge, card and avatar treatments identical everywhere.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Converts Latin digits to Persian digits.
 *
 * Vazirmatn's `ss01` feature would do this in the font, but it applies to every
 * Latin digit on the page and corrupts CEFR codes such as A2/B2. The feature is
 * therefore off and localisation happens here, where it can be targeted.
 *
 * @param string|int $value Value to localise.
 * @return string
 */
function zandi_fa_digits( $value ) {
	return str_replace(
		array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ),
		array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ),
		(string) $value
	);
}

/**
 * Formats a number with thousands separators, then localises the digits.
 *
 * @param int $value Number to format.
 * @return string
 */
function zandi_fa_number( $value ) {
	return zandi_fa_digits( number_format_i18n( (int) $value ) );
}

/**
 * Renders a button, as a link when a URL is given and a <button> otherwise.
 *
 * @param array $args {
 *     @type string $label    Button text. Required.
 *     @type string $url      Href. Renders an <a> when present.
 *     @type string $variant  primary|secondary|on-dark|outline-on-dark. Default primary.
 *     @type string $size     sm|md|lg. Default md.
 *     @type string $class    Extra class names.
 *     @type string $icon     Optional icon name rendered after the label.
 *     @type string $icon_before Optional icon name rendered before the label.
 *     @type string $type     Button type when not a link. Default 'button'.
 *     @type string $sr_label Optional screen-reader-only label replacing $label
 *                            for assistive tech, for repeated actions in a grid.
 *     @type array  $attrs    Extra HTML attributes as name => value.
 * }
 * @return void
 */
function zandi_button( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'label'    => '',
			'url'      => '',
			'variant'  => 'primary',
			'size'     => 'md',
			'class'    => '',
			'icon'     => '',
			'icon_before' => '',
			'type'     => 'button',
			'sr_label' => '',
			'attrs'    => array(),
		)
	);

	$classes = trim( sprintf( 'btn btn--%s btn--%s %s', $args['variant'], $args['size'], $args['class'] ) );

	$extra = '';
	foreach ( $args['attrs'] as $name => $value ) {
		$extra .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( $value ) );
	}

	// A repeated action ("مشاهده دوره") needs a specific accessible name.
	$label_markup = $args['sr_label']
		? sprintf(
			'<span aria-hidden="true">%s</span><span class="screen-reader-text">%s</span>',
			esc_html( $args['label'] ),
			esc_html( $args['sr_label'] )
		)
		: esc_html( $args['label'] );

	$icon_markup = $args['icon'] ? zandi_get_icon( $args['icon'], array( 'class' => 'btn__icon' ) ) : '';

	// A leading icon reads before the label in the flex row.
	if ( $args['icon_before'] ) {
		$label_markup = zandi_get_icon( $args['icon_before'], array( 'class' => 'btn__icon' ) ) . $label_markup;
	}

	if ( $args['url'] ) {
		printf(
			'<a href="%1$s" class="%2$s"%3$s>%4$s%5$s</a>',
			esc_url( $args['url'] ),
			esc_attr( $classes ),
			$extra, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped per attribute above.
			$label_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
			$icon_markup // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed icon registry.
		);
		return;
	}

	printf(
		'<button type="%1$s" class="%2$s"%3$s>%4$s%5$s</button>',
		esc_attr( $args['type'] ),
		esc_attr( $classes ),
		$extra, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped per attribute above.
		$label_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
		$icon_markup // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed icon registry.
	);
}

/**
 * Renders a badge.
 *
 * @param string $label   Badge text.
 * @param string $variant navy|rouge|neutral|on-dark. Default navy.
 * @param array  $args    Optional. {
 *     @type string $class Extra class names.
 *     @type bool   $dot   Whether to show the leading accent dot.
 * }
 * @return void
 */
function zandi_badge( $label, $variant = 'navy', $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'class' => '',
			'dot'   => false,
		)
	);

	printf(
		'<span class="badge badge--%1$s %2$s">%3$s%4$s</span>',
		esc_attr( $variant ),
		esc_attr( $args['class'] ),
		$args['dot'] ? '<span class="badge__dot" aria-hidden="true"></span>' : '',
		esc_html( $label )
	);
}

/**
 * Renders a star rating.
 *
 * The stars are decorative; the value is announced once as text so a screen
 * reader hears "امتیاز ۵ از ۵" rather than five icon names.
 *
 * @param int    $value Filled stars.
 * @param int    $max   Total stars. Default 5.
 * @param string $class Extra class names.
 * @return void
 */
function zandi_rating( $value, $max = 5, $class = '' ) {
	$star = '<path d="M10 1.8l2.4 4.9 5.4.8-3.9 3.8.9 5.4-4.8-2.5-4.8 2.5.9-5.4L2.2 7.5l5.4-.8L10 1.8z"/>';

	printf( '<div class="rating %s">', esc_attr( $class ) );

	printf(
		'<span class="screen-reader-text">%s</span>',
		esc_html(
			sprintf(
				/* translators: 1: rating value, 2: maximum rating. */
				'امتیاز %1$s از %2$s',
				zandi_fa_digits( $value ),
				zandi_fa_digits( $max )
			)
		)
	);

	for ( $index = 0; $index < $max; $index++ ) {
		printf(
			'<svg viewBox="0 0 20 20" aria-hidden="true" class="%s">%s</svg>',
			$index < $value ? 'is-filled' : '',
			$star // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed literal path.
		);
	}

	echo '</div>';
}

/**
 * Renders an avatar, falling back to initials when there is no image.
 *
 * Arabic-script letters join when placed side by side, so two initials read as
 * a garbled word ("شیما زندی" → "شز"). Persian names therefore get a single
 * letter; Latin names keep the familiar two.
 *
 * @param string $name  Person's name.
 * @param array  $args  Optional. {
 *     @type string $image Image URL.
 *     @type string $size  sm|md|lg. Default md.
 *     @type int    $index Palette index for the initials background.
 * }
 * @return void
 */
function zandi_avatar( $name, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'image' => '',
			'size'  => 'md',
			'index' => 0,
		)
	);

	if ( $args['image'] ) {
		// Matches the rendered sizes in style.css, so the space is reserved
		// before the stylesheet lands and the row cannot jump.
		$pixels = array(
			'sm' => 40,
			'md' => 48,
			'lg' => 56,
		);
		$size   = isset( $pixels[ $args['size'] ] ) ? $pixels[ $args['size'] ] : 48;

		printf(
			'<img src="%1$s" alt="%2$s" width="%4$d" height="%4$d" loading="lazy" decoding="async" class="avatar avatar--%3$s" />',
			esc_url( $args['image'] ),
			esc_attr( $name ),
			esc_attr( $args['size'] ),
			(int) $size
		);
		return;
	}

	$words = preg_split( '/\s+/u', trim( $name ), -1, PREG_SPLIT_NO_EMPTY );

	if ( preg_match( '/\p{Arabic}/u', $name ) ) {
		$initials = mb_substr( $words[0], 0, 1, 'UTF-8' );
	} else {
		$initials = '';
		foreach ( array_slice( $words, 0, 2 ) as $word ) {
			$initials .= mb_substr( $word, 0, 1, 'UTF-8' );
		}
	}

	printf(
		'<span class="avatar avatar--%1$s avatar--tone-%2$d" aria-hidden="true">%3$s</span>',
		esc_attr( $args['size'] ),
		(int) ( $args['index'] % 4 ),
		esc_html( $initials )
	);
}

/**
 * Renders a section heading: eyebrow, title and description.
 *
 * @param array $args {
 *     @type string $id          Section id; the title gets "{$id}-title".
 *     @type string $eyebrow     Small label above the title.
 *     @type string $title       Heading text. Required.
 *     @type string $description Supporting paragraph.
 *     @type string $align       center|start. Default center.
 *     @type bool   $on_dark     Whether the heading sits on a dark panel.
 * }
 * @return void
 */
function zandi_section_heading( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'id'          => '',
			'eyebrow'     => '',
			'title'       => '',
			'description' => '',
			'align'       => 'center',
			'on_dark'     => false,
		)
	);

	$classes = 'section-heading reveal';

	if ( 'start' === $args['align'] ) {
		$classes .= ' section-heading--start';
	}

	if ( $args['on_dark'] ) {
		$classes .= ' section-heading--on-dark';
	}
	?>
	<div class="<?php echo esc_attr( $classes ); ?>">
		<?php if ( $args['eyebrow'] ) : ?>
			<?php zandi_badge( $args['eyebrow'], $args['on_dark'] ? 'on-dark' : 'rouge' ); ?>
		<?php endif; ?>

		<h2 class="section-heading__title"<?php echo $args['id'] ? ' id="' . esc_attr( $args['id'] ) . '-title"' : ''; ?>>
			<?php echo esc_html( $args['title'] ); ?>
		</h2>

		<?php if ( $args['description'] ) : ?>
			<p class="section-heading__description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Renders a section heading unless the surrounding page already states it.
 *
 * A standalone section page puts the section's name in its <h1>, so the partial
 * repeating it immediately below reads as a stutter. Passing
 * `suppress_heading` from the page template skips it; on the homepage, where
 * there is no such <h1>, the heading always renders.
 *
 * @param array $args    The partial's incoming $args.
 * @param array $heading Arguments for zandi_section_heading().
 * @return void
 */
function zandi_maybe_section_heading( $args, $heading ) {
	if ( ! empty( $args['suppress_heading'] ) ) {
		return;
	}

	zandi_section_heading( $heading );
}

/**
 * Renders the academy wordmark.
 *
 * The mark is the ز / Z ligature from the owner's logo pack: a Latin Z whose
 * tail sweeps into the Persian «ز», with the red dot above it. The dot is the
 * brand's smallest ownable unit — the dot of ز, a French accent, and a full
 * stop — which is why the pack's one hard rule is that **red is only ever the
 * dot**. It stops meaning anything the moment red spreads to buttons and
 * banners, and that is already the site's rule too.
 *
 * The mark it replaced was an abstract Z cut from a rounded navy tile with a
 * red hairline, drawn before there was a real logo.
 *
 * THE SVG IS INLINE, NOT AN <img>. CSS has to reach `stroke` and `fill` so one
 * mark serves the white header and the navy footer with a class change, and so
 * the draw-on animation can run without a second file.
 *
 * COLOUR: the pack ships #1D2E5C / #DC3327; this renders the site's own
 * --navy-700 (#1B365D) and --rouge-500 (#C8102E) instead. The pairs are within
 * a couple of percent of each other, and a logo navy that is *almost* the
 * header navy beside it reads as a mistake rather than as a second brand.
 * CLAUDE.md's «one palette» rule wins. Flagged to the owner.
 *
 * @param bool $on_dark Whether the logo sits on a dark background.
 * @return void
 */
function zandi_logo( $on_dark = false ) {
	$site = zandi_site();
	?>
	<span class="logo<?php echo $on_dark ? ' logo--on-dark' : ''; ?>">
		<?php
		/*
		 * `overflow: visible` is set on .logo__mark in style.css — without it
		 * the round stroke caps are clipped by the viewBox, which is tight to
		 * the path. The viewBox is the pack's own and must not be recropped.
		 */
		?>
		<svg class="logo__mark" viewBox="15.5 5 81 90" fill="none" aria-hidden="true" focusable="false">
			<circle class="logo__dot" cx="68" cy="13" r="8" />
			<path
				class="logo__z"
				d="M22,34 H82 L28,78 C46,93 78,93 90,70"
				stroke-width="13"
				stroke-linecap="round"
				stroke-linejoin="round"
			/>
		</svg>

		<span class="logo__text">
			<span class="logo__name"><?php echo esc_html( $site['name'] ); ?></span>
			<span class="logo__sub">FRANÇAIS</span>
		</span>
	</span>
	<?php
}

/**
 * A "there is more below" cue between two sections.
 *
 * A course page is long and every section ends on a finished-looking card, so
 * the bottom of one reads as the bottom of the page. This is the arrow that
 * says otherwise.
 *
 * Decorative on purpose. It is `aria-hidden` and not a link: a keyboard user
 * already knows the page continues, and a tab stop between every section would
 * be eight stops of nothing on the way to the enrol button.
 *
 * COST: one inline SVG from the registry — no request — and one CSS keyframe
 * that animates `transform` only. Transform-only animations run on the
 * compositor, so they cause no style recalculation, no layout and no repaint;
 * browsers also stop ticking them while the element is off-screen. Do not add
 * `will-change` here: it would promote eight permanent layers to save nothing.
 * The animation is off entirely under `prefers-reduced-motion`.
 *
 * @return void
 */
function zandi_scroll_cue() {
	?>
	<div class="c-cue" aria-hidden="true">
		<?php zandi_icon( 'arrowDown', array( 'class' => 'c-cue__arrow' ) ); ?>
	</div>
	<?php
}

/**
 * Renders a breadcrumb trail.
 *
 * Course and section pages sit one or two levels below the homepage, and until
 * this existed a visitor who landed on /courses/a1 had no way of telling where
 * they were or how to get back out.
 *
 * The separator is drawn as an icon rather than written as a › character:
 * mirrored punctuation is flipped by the bidi algorithm inside a Persian run, so
 * a literal chevron ends up pointing back the way it came.
 *
 * @param array<int,array{label:string,url?:string}> $trail Items in reading
 *                                                          order, shallowest
 *                                                          first. The last is
 *                                                          the current page and
 *                                                          is never a link.
 * @return void
 */
function zandi_breadcrumb( $trail ) {
	$trail = array_values( array_filter( (array) $trail ) );

	if ( count( $trail ) < 2 ) {
		return; // A trail of one is just the page title.
	}

	$last      = count( $trail ) - 1;
	$separator = zandi_get_icon( zandi_chevron_forward(), array( 'class' => 'breadcrumb__sep' ) );
	?>
	<nav class="breadcrumb" aria-label="مسیر صفحه">
		<ol class="breadcrumb__list">
			<?php foreach ( $trail as $index => $item ) : ?>
				<li class="breadcrumb__item">
					<?php if ( $index === $last || empty( $item['url'] ) ) : ?>
						<span class="breadcrumb__current" aria-current="page"><?php echo zandi_bidi( $item['label'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside zandi_bidi(). ?></span>
					<?php else : ?>
						<a class="breadcrumb__link" href="<?php echo esc_url( $item['url'] ); ?>"><?php echo zandi_bidi( $item['label'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside zandi_bidi(). ?></a>
						<?php echo $separator; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed icon registry. ?>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>
	<?php
}

/**
 * Renders the guilloché engraving used on the hero and final-CTA panels.
 *
 * An abstract nod to the geometry of French banknotes and Sèvres porcelain
 * rather than a landmark. Pure SVG, so it costs no image bytes and stays sharp
 * at any pixel density.
 *
 * @param string $variant 'hero'|'cta'|'thumb'.
 * @return void
 */
function zandi_engraving( $variant = 'hero' ) {
	if ( 'thumb' === $variant ) {
		?>
		<svg viewBox="0 0 320 200" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
			<g fill="none" stroke="white" stroke-opacity="0.16" stroke-width="1">
				<circle cx="255" cy="42" r="34" />
				<circle cx="255" cy="42" r="58" />
				<circle cx="255" cy="42" r="84" />
				<circle cx="255" cy="42" r="112" />
			</g>
			<path d="M0 168 C 80 140, 150 190, 320 132 L320 200 L0 200 Z" fill="white" fill-opacity="0.06" />
			<path d="M0 186 C 90 160, 170 204, 320 156 L320 200 L0 200 Z" fill="white" fill-opacity="0.05" />
		</svg>
		<?php
		return;
	}

	if ( 'cta' === $variant ) {
		?>
		<svg class="cta__engraving" viewBox="0 0 1200 480" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
			<g fill="none" stroke="white" stroke-opacity="0.09">
				<?php foreach ( array( 90, 150, 215, 285, 360, 440 ) as $radius ) : ?>
					<circle cx="1010" cy="60" r="<?php echo esc_attr( $radius ); ?>" />
				<?php endforeach; ?>
			</g>
			<g fill="none" stroke="white" stroke-opacity="0.06">
				<?php foreach ( array( 70, 130, 195 ) as $radius ) : ?>
					<circle cx="120" cy="430" r="<?php echo esc_attr( $radius ); ?>" />
				<?php endforeach; ?>
			</g>
		</svg>
		<?php
		return;
	}
	?>
	<svg class="hero-visual__engraving" viewBox="0 0 400 460" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
		<g fill="none" stroke="white" stroke-opacity="0.14">
			<?php foreach ( array( 44, 76, 110, 146, 184, 224, 266 ) as $radius ) : ?>
				<circle cx="320" cy="70" r="<?php echo esc_attr( $radius ); ?>" />
			<?php endforeach; ?>
		</g>
		<g fill="none" stroke="white" stroke-opacity="0.09">
			<?php foreach ( array( 30, 58, 90, 126, 166 ) as $radius ) : ?>
				<circle cx="60" cy="400" r="<?php echo esc_attr( $radius ); ?>" />
			<?php endforeach; ?>
		</g>
		<path d="M0 330 C 110 292, 210 372, 400 288 L400 460 L0 460 Z" fill="white" fill-opacity="0.05" />
		<path d="M0 372 C 120 336, 240 408, 400 340 L400 460 L0 460 Z" fill="white" fill-opacity="0.04" />
	</svg>
	<?php
}

/**
 * Renders the tricolore mark.
 *
 * `dir="ltr"` belongs on the inner row, not the positioned box: `inset-inline-start`
 * resolves against the element's own direction, so setting it on the wrapper
 * would flip the mark to the opposite edge of the panel.
 *
 * @return void
 */
function zandi_tricolore() {
	?>
	<div class="tricolore" aria-hidden="true">
		<div class="tricolore__row" dir="ltr">
			<span class="tricolore__blue"></span>
			<span class="tricolore__white"></span>
			<span class="tricolore__red"></span>
		</div>
	</div>
	<?php
}

/**
 * Escapes text and isolates any Latin run inside it.
 *
 * A Latin fragment sitting in a right-to-left paragraph — «دوره مکالمه A1 · A2 · B1»
 * — is reordered by the bidi algorithm, because the separators around it are
 * neutral and inherit the paragraph's direction. The visible result is
 * «B1 · A2 · A1», i.e. the wrong course order. Wrapping each run in a
 * direction-isolated span pins it to its own logical order.
 *
 * Use this instead of esc_html() for any Persian string that may contain
 * French, level codes or Latin punctuation.
 *
 * @param string $text Raw text.
 * @return string Escaped HTML, safe to echo.
 */
function zandi_bidi( $text ) {
	$escaped = esc_html( $text );

	/*
	 * A run is one or more Latin words chained by anything that can appear
	 * *between* two of them: a space, an apostrophe, a comma, a dot, a slash,
	 * a hyphen or dash, a middot, an ellipsis.
	 *
	 * WHY THE CHAIN IS THIS WIDE. It used to be `[·\-–\/]` only, so it broke on
	 * a plain space — and two adjacent Latin runs with only a neutral space
	 * between them are two separate islands, which the bidi algorithm lays out
	 * right-to-left relative to each other. «Les nombres» rendered «nombres
	 * Les», «ne … pas» rendered «pas … ne», and «L'alphabet» rendered
	 * «alphabet'L». It also matched `[A-Za-z]` only, so an accented letter
	 * split a word in half: «présenter» came out as «senterépr».
	 *
	 * `\p{Latin}` covers the accents. The chain group must END on a letter or
	 * digit, so a full stop closing the surrounding Persian sentence is left
	 * outside the run where it belongs.
	 *
	 * `&#039;` is in the list because this runs on the *escaped* string, where
	 * esc_html() has already turned a straight apostrophe into that entity —
	 * which is why «L'alphabet» still split in half after the chain was widened
	 * to include the apostrophe itself.
	 */
	$pattern = '/(\p{Latin}[\p{Latin}0-9]*(?:(?:&#0?39;|&apos;|[ \'’·,.\-–\/…])+[\p{Latin}0-9]+)*)/u';

	return preg_replace(
		$pattern,
		'<span dir="ltr" class="latin-run">$1</span>',
		$escaped
	);
}

/**
 * Renders a 16:9 video slot.
 *
 * With no `src` it draws a deliberate empty state — a hatched frame with a play
 * badge — rather than a broken embed, so the layout is final before the videos
 * exist and nothing shifts when they arrive.
 *
 * @param array $args {
 *     @type string $src     Embed URL. Renders the placeholder when empty.
 *     @type string $title   Accessible title for the iframe.
 *     @type string $caption Text under the frame.
 *     @type string $note    Text inside the placeholder.
 *     @type string $class   Extra class names.
 * }
 * @return void
 */
function zandi_video( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'src'     => '',
			'title'   => 'ویدیوی دوره',
			'caption' => '',
			'note'    => 'ویدیو به‌زودی اینجا قرار می‌گیرد',
			'class'   => '',
		)
	);
	?>
	<div class="c-video <?php echo esc_attr( $args['class'] ); ?>">
		<div class="c-video__frame">
			<?php if ( $args['src'] ) : ?>
				<iframe
					src="<?php echo esc_url( $args['src'] ); ?>"
					title="<?php echo esc_attr( $args['title'] ); ?>"
					loading="lazy"
					allowfullscreen
				></iframe>
			<?php else : ?>
				<?php /* TODO: replace with the real video embed once recorded. */ ?>
				<div class="c-video__empty">
					<span class="c-video__play" aria-hidden="true">
						<svg viewBox="0 0 24 24" fill="currentColor">
							<path d="M8 5.5v13l11-6.5-11-6.5Z" />
						</svg>
					</span>
					<span class="c-video__note"><?php echo esc_html( $args['note'] ); ?></span>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $args['caption'] ) : ?>
			<p class="c-video__caption"><?php echo esc_html( $args['caption'] ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Formats a Toman price in Persian digits.
 *
 * @param int $amount Amount in Toman.
 * @return string
 */
function zandi_price_toman( $amount ) {
	return zandi_fa_digits( number_format( (int) $amount ) );
}

/**
 * A yes/no mark for the "who it is for" lists.
 *
 * Shape carries the meaning alongside colour — a ring for yes, a slash for no —
 * so the lists still read without colour vision.
 *
 * @param string $type 'yes' or 'no'.
 * @return void
 */
function zandi_mark( $type = 'yes' ) {
	if ( 'no' === $type ) {
		?>
		<svg class="c-mark c-mark--no" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
			<circle cx="10" cy="10" r="7.5" />
			<path d="M6.8 13.2 13.2 6.8" stroke-linecap="round" />
		</svg>
		<?php
		return;
	}
	?>
	<svg class="c-mark c-mark--yes" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
		<circle cx="10" cy="10" r="7.5" />
		<path d="m6.7 10.3 2.4 2.4 4.3-4.9" stroke-linecap="round" stroke-linejoin="round" />
	</svg>
	<?php
}

/**
 * The checkmark used in the outcomes list.
 *
 * @return void
 */
function zandi_check() {
	?>
	<svg class="c-check" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
		<path d="m4 10.5 4 4 8-9" stroke-linecap="round" stroke-linejoin="round" />
	</svg>
	<?php
}

/**
 * Renders the post meta line used on archives and single posts.
 *
 * @return void
 */
function zandi_post_meta() {
	?>
	<div class="page-hero__meta">
		<span>
			<?php zandi_icon( 'calendar' ); ?>
			<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
				<?php echo esc_html( get_the_date() ); ?>
			</time>
		</span>
		<span>
			<?php zandi_icon( 'user' ); ?>
			<?php echo esc_html( get_the_author() ); ?>
		</span>
		<?php if ( has_category() ) : ?>
			<span>
				<?php zandi_icon( 'tag' ); ?>
				<?php echo get_the_category_list( '، ' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core-escaped link list. ?>
			</span>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Renders one card in the post grid.
 *
 * @return void
 */
function zandi_post_card() {
	?>
	<article <?php post_class( 'card card--interactive card--flush post-card reveal reveal--scale' ); ?>>
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="post-card__media">
				<div class="post-card__thumb">
					<a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
						<?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); ?>
					</a>
				</div>
			</div>
		<?php endif; ?>

		<div class="post-card__body">
			<p class="post-card__meta">
				<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
					<?php echo esc_html( get_the_date() ); ?>
				</time>
			</p>

			<h2 class="post-card__title">
				<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			</h2>

			<p class="post-card__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>

			<?php
			zandi_button(
				array(
					'label'    => 'ادامه مطلب',
					'sr_label' => sprintf( 'ادامه مطلب: %s', get_the_title() ),
					'url'      => get_permalink(),
					'variant'  => 'secondary',
					'size'     => 'sm',
					'class'    => 'post-card__more',
					'icon'     => zandi_arrow_forward(),
				)
			);
			?>
		</div>
	</article>
	<?php
}
