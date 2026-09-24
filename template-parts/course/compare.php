<?php
/**
 * «فرق دوره‌های اصلی و دوره‌های مکالمه چیه؟» — the two kinds of course, side by side.
 *
 * The owner asked for the difference «in a snap», so the section is built to be
 * read in three glances, each answering one question:
 *
 *   1. WHAT IS EACH ONE? The column heading and the one bold sentence under it —
 *      her own closing sentence, split in two. Someone who reads nothing else has
 *      the answer here.
 *   2. HOW DO THEY RELATE? The arrow between the columns, labelled with her own
 *      «یه قدم جلوتر». They are not rivals and this is not a «vs»: one comes
 *      after the other, and the arrow points the way a right-to-left reader
 *      travels — from the main courses to the conversation ones.
 *   3. WHICH ONE IS FOR ME? The two numbered steps underneath, from her last
 *      line. Each ends in a place to go: the other course's page, or a mark
 *      saying this is it.
 *
 *      For A2 and B1 the answer is different, and so is the drawing: the owner
 *      said a student need not finish the main course first and can take both
 *      at once. Numbered steps would say «first this, then that», which is the
 *      one thing she said is not so — so a course with `compare_path_mode`
 *      «together» gets the two courses joined by a «+», each marked by its
 *      family's icon rather than by a number.
 *
 * The six points in each column are there for whoever wants the detail, and
 * they are hers, word for word.
 *
 * DELIBERATELY NOT A CHECKMARK TABLE. A ✓/✗ grid is the usual way to draw a
 * comparison and it would have been untrue here: every ✗ in the main-courses
 * column would claim they lack something the owner never said they lack. Two
 * lists say only what she said.
 *
 * The column for this page's own family is the navy one, so the course on the
 * page is the one that stands out. Everything else is the site's palette.
 *
 * @package Zandi
 */

defined( 'ABSPATH' ) || exit;

$course         = $args['course'];
$zandi_families = zandi_course_families();
$zandi_copy     = zandi_course_compare_copy();
$zandi_current  = ! empty( $course['family'] ) ? $course['family'] : 'main';
$zandi_path     = ! empty( $course['compare_path'] ) ? (array) $course['compare_path'] : array();
$zandi_together = isset( $course['compare_path_mode'] ) && 'together' === $course['compare_path_mode'];
$zandi_path_h   = ! empty( $course['compare_path_title'] ) ? $course['compare_path_title'] : $zandi_copy['path_title'];

if ( ! $zandi_families ) {
	return;
}
?>

<section class="c-section" id="compare" aria-labelledby="compare-title">
	<div class="c-container">
		<div class="c-section__head reveal">
			<h2 class="c-section__title" id="compare-title"><?php echo zandi_bidi( $zandi_copy['title'] ); ?></h2>
		</div>

		<div class="c-compare reveal">
			<?php $zandi_index = 0; ?>
			<?php foreach ( $zandi_families as $zandi_key => $zandi_family ) : ?>
				<?php if ( $zandi_index > 0 ) : ?>
					<?php
					/*
					 * Decoration with a label: the arrow is hidden from assistive
					 * tech, the words are not — «یه قدم جلوتر» between the two
					 * headings is the relationship, said once.
					 *
					 * Two arrows, one shown per layout. Across the gutter on a
					 * wide screen it points forward in the reading direction;
					 * between stacked cards on a phone it points down. Swapping
					 * icons rather than rotating one keeps the direction out of
					 * the transform, which rtl.css would otherwise have to
					 * mirror.
					 */
					?>
					<?php if ( $zandi_together ) : ?>
						<?php
						/*
						 * On A2 and B1 an arrow would say «then», which is the
						 * one thing the owner said is not so. A «+» says «and».
						 */
						?>
						<div class="c-compare__bridge">
							<span class="c-compare__arrow c-compare__arrow--plus" aria-hidden="true">+</span>
							<span class="c-compare__bridge-label"><?php echo esc_html( $zandi_copy['bridge_together'] ); ?></span>
						</div>
					<?php else : ?>
						<div class="c-compare__bridge">
							<span class="c-compare__arrow c-compare__arrow--across" aria-hidden="true"><?php zandi_icon( zandi_arrow_forward() ); ?></span>
							<span class="c-compare__arrow c-compare__arrow--down" aria-hidden="true"><?php zandi_icon( 'arrowDown' ); ?></span>
							<span class="c-compare__bridge-label"><?php echo esc_html( $zandi_copy['bridge'] ); ?></span>
						</div>
					<?php endif; ?>
				<?php endif; ?>

				<article
					class="c-compare__col<?php echo $zandi_key === $zandi_current ? ' c-compare__col--current' : ''; ?>"
					aria-labelledby="compare-<?php echo esc_attr( $zandi_key ); ?>"
				>
					<div class="c-compare__head">
						<span class="c-compare__icon" aria-hidden="true"><?php zandi_icon( $zandi_family['icon'] ); ?></span>
						<h3 class="c-compare__title" id="compare-<?php echo esc_attr( $zandi_key ); ?>"><?php echo esc_html( $zandi_family['title'] ); ?></h3>
					</div>

					<?php if ( ! empty( $zandi_family['levels'] ) ) : ?>
						<ul class="c-compare__levels">
							<?php foreach ( $zandi_family['levels'] as $zandi_level ) : ?>
								<li><?php echo zandi_bidi( $zandi_level ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<p class="c-compare__summary"><?php echo zandi_bidi( $zandi_family['summary'] ); ?></p>

					<ul class="c-compare__list">
						<?php foreach ( $zandi_family['items'] as $zandi_item ) : ?>
							<li>
								<span class="c-compare__tick" aria-hidden="true"><?php zandi_icon( 'check' ); ?></span>
								<span><?php echo zandi_bidi( $zandi_item ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</article>
				<?php ++$zandi_index; ?>
			<?php endforeach; ?>
		</div>

		<?php if ( $zandi_path && $zandi_together ) : ?>
			<div class="c-path c-path--together reveal">
				<h3 class="c-path__title"><?php echo zandi_bidi( $zandi_path_h ); ?></h3>

				<?php
				/*
				 * Not a list: two things side by side are not a sequence, and
				 * an <ol> would have a screen reader announce «1 of 2». The «+»
				 * is decoration; the heading above already says «با هم».
				 */
				?>
				<div class="c-path__steps c-path__steps--together">
					<?php foreach ( $zandi_path as $zandi_n => $zandi_step ) : ?>
						<?php
						$zandi_here   = isset( $zandi_step['course'] ) && $zandi_step['course'] === $course['slug'];
						$zandi_target = isset( $zandi_step['course'] ) ? zandi_get_course( $zandi_step['course'] ) : null;
						$zandi_family = ( isset( $zandi_step['family'] ) && isset( $zandi_families[ $zandi_step['family'] ] ) ) ? $zandi_families[ $zandi_step['family'] ] : null;
						?>
						<?php if ( $zandi_n > 0 ) : ?>
							<span class="c-path__plus" aria-hidden="true">+</span>
						<?php endif; ?>

						<div class="c-path__step<?php echo $zandi_here ? ' c-path__step--here' : ''; ?>">
							<span class="c-path__num" aria-hidden="true">
								<?php
								if ( $zandi_family ) {
									zandi_icon( $zandi_family['icon'] );
								} else {
									echo esc_html( zandi_fa_digits( $zandi_n + 1 ) );
								}
								?>
							</span>

							<span class="c-path__text">
								<span class="c-path__when"><?php echo zandi_bidi( $zandi_step['when'] ); ?></span>
								<span class="c-path__then"><?php echo zandi_bidi( $zandi_step['then'] ); ?></span>
							</span>

							<?php if ( $zandi_here ) : ?>
								<span class="c-path__here"><?php echo esc_html( $zandi_copy['here'] ); ?></span>
							<?php elseif ( $zandi_target ) : ?>
								<a class="c-path__link" href="<?php echo esc_url( zandi_course_url( $zandi_target['slug'] ) ); ?>">
									<span aria-hidden="true"><?php echo esc_html( $zandi_copy['view'] ); ?></span>
									<span class="screen-reader-text"><?php echo esc_html( $zandi_copy['view'] . ' ' . $zandi_target['short_name'] ); ?></span>
									<?php zandi_icon( zandi_arrow_forward() ); ?>
								</a>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php elseif ( $zandi_path ) : ?>
			<div class="c-path reveal">
				<h3 class="c-path__title"><?php echo zandi_bidi( $zandi_path_h ); ?></h3>

				<?php
				/*
				 * The numbers are drawn, not a list marker: an ordered list's
				 * counter is the one place Latin digits leak into a Persian
				 * page, and these sit in a filled disc a marker cannot make.
				 * The list stays an <ol> so a screen reader still hears «1 of 2».
				 */
				?>
				<ol class="c-path__steps">
					<?php foreach ( $zandi_path as $zandi_n => $zandi_step ) : ?>
						<?php
						$zandi_here   = isset( $zandi_step['course'] ) && $zandi_step['course'] === $course['slug'];
						$zandi_target = isset( $zandi_step['course'] ) ? zandi_get_course( $zandi_step['course'] ) : null;
						?>
						<li class="c-path__step<?php echo $zandi_here ? ' c-path__step--here' : ''; ?>">
							<span class="c-path__num" aria-hidden="true"><?php echo esc_html( zandi_fa_digits( $zandi_n + 1 ) ); ?></span>

							<span class="c-path__text">
								<span class="c-path__when"><?php echo zandi_bidi( $zandi_step['when'] ); ?></span>
								<span class="c-path__then"><?php echo zandi_bidi( $zandi_step['then'] ); ?></span>
							</span>

							<?php if ( $zandi_here ) : ?>
								<span class="c-path__here"><?php echo esc_html( $zandi_copy['here'] ); ?></span>
							<?php elseif ( $zandi_target ) : ?>
								<a class="c-path__link" href="<?php echo esc_url( zandi_course_url( $zandi_target['slug'] ) ); ?>">
									<span aria-hidden="true"><?php echo esc_html( $zandi_copy['view'] ); ?></span>
									<span class="screen-reader-text"><?php echo esc_html( $zandi_copy['view'] . ' ' . $zandi_target['short_name'] ); ?></span>
									<?php zandi_icon( zandi_arrow_forward() ); ?>
								</a>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
		<?php endif; ?>
	</div>
</section>
